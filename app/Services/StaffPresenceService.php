<?php

namespace App\Services;

use App\Models\StaffPresence;
use App\Models\User;
use App\Support\TwilioClientIdentity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class StaffPresenceService
{
    public const STATE_AVAILABLE = 'available';

    public const STATE_BUSY = 'busy';

    public function operatorUserIds(): array
    {
        return User::query()
            ->voiceDispatchOperators()
            ->pluck('id')
            ->all();
    }

    public function totalOperatorCount(): int
    {
        return count($this->operatorUserIds());
    }

    /**
     * Twilio Client identities (sanitized user ids) for operators who may receive
     * an inbound emergency VoIP call right now.
     *
     * @param  bool  $forTwimlDial  When true, applies {@code call.twiml_operator_ttl_multiplier} to the
     *                              heartbeat / voice-ready cutoffs so /twilio/voice matches live presence
     *                              slightly better than the strict availability API window.
     * @return list<string>
     */
    public function voiceReadyOperatorIdentities(bool $forTwimlDial = false): array
    {
        $ids = $this->operatorUserIds();
        if ($ids === []) {
            return [];
        }

        $heartbeatTtlSec = $this->operatorPresenceTtlSeconds($forTwimlDial);
        $heartbeatCutoff = now()->subSeconds($heartbeatTtlSec);
        $requireVoiceClient = (bool) config('call.require_voice_client_ready', true);

        $q = StaffPresence::query()
            ->whereIn('user_id', $ids)
            ->where('state', self::STATE_AVAILABLE)
            ->whereNotNull('last_heartbeat_at')
            ->where('last_heartbeat_at', '>=', $heartbeatCutoff);

        if ($requireVoiceClient) {
            if ($forTwimlDial) {
                $grace = max(60, (int) config('call.twiml_voice_ready_grace_seconds', 900));
                $voiceWindow = max($heartbeatTtlSec, $grace);
                $voiceCutoff = now()->subSeconds($voiceWindow);
            } else {
                $voiceCutoff = $heartbeatCutoff;
            }
            $q->whereNotNull('voice_client_ready_at')
                ->where('voice_client_ready_at', '>=', $voiceCutoff);
        }

        $rawIds = $q->orderBy('user_id')->pluck('user_id')->all();

        if ($rawIds === [] && $forTwimlDial && (bool) config('call.twiml_fallback_heartbeat_only_operators', true)) {
            $rawIds = $this->heartbeatOnlyOperatorIdsForTwimlDial();
        }

        $seen = [];
        $out = [];
        foreach ($rawIds as $id) {
            $san = TwilioClientIdentity::sanitize((string) $id);
            if (isset($seen[$san])) {
                continue;
            }
            $seen[$san] = true;
            $out[] = $san;
        }

        $max = max(1, (int) config('call.max_simultaneous_client_dials', 20));

        return array_slice($out, 0, $max);
    }

    /**
     * Last-resort dial targets for TwiML: same admin/super_admin operators as the main query,
     * fresh heartbeat + available, without requiring {@code voice_client_ready_at}.
     *
     * @return list<int>
     */
    protected function heartbeatOnlyOperatorIdsForTwimlDial(): array
    {
        $ids = $this->operatorUserIds();
        if ($ids === []) {
            return [];
        }

        $heartbeatCutoff = now()->subSeconds($this->operatorPresenceTtlSeconds(true));

        return StaffPresence::query()
            ->whereIn('user_id', $ids)
            ->where('state', self::STATE_AVAILABLE)
            ->whereNotNull('last_heartbeat_at')
            ->where('last_heartbeat_at', '>=', $heartbeatCutoff)
            ->orderBy('user_id')
            ->pluck('user_id')
            ->all();
    }

    /**
     * Heartbeat / voice-ready cutoff length in seconds (Twilio webhook may use a wider window).
     */
    public function operatorPresenceTtlSeconds(bool $forTwimlDial = false): int
    {
        $base = max(15, (int) config('call.staff_heartbeat_ttl', 90));
        if (! $forTwimlDial) {
            return $base;
        }

        $mult = (float) config('call.twiml_operator_ttl_multiplier', 1.0);
        if ($mult < 1.0) {
            $mult = 1.0;
        }
        if ($mult > 4.0) {
            $mult = 4.0;
        }

        return (int) round($base * $mult);
    }

    public function availableOperatorCount(bool $forTwimlDial = false): int
    {
        return count($this->voiceReadyOperatorIdentities($forTwimlDial));
    }

    public function isCallRoutingAllowed(bool $forTwimlDial = false): bool
    {
        if ($this->totalOperatorCount() === 0) {
            return false;
        }

        return $this->availableOperatorCount($forTwimlDial) > 0;
    }

    /**
     * Value for {@code device.connect({ params: { To } })}: when exactly one operator is reachable
     * under TwiML rules, return that sanitized user id; otherwise the ring-group token (e.g. {@code dispatch}).
     * For {@code call.voice_outbound_dial_mode=single} with multiple operators ready, callers still use
     * the ring token here; {@code /twilio/voice} collapses to one {@code <Client>} so only one admin rings.
     * Admins register the SDK as their own user id — never as {@code dispatch}.
     */
    public function voiceOutboundDialToIdentity(): string
    {
        $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
        $twimlDialIds = $this->voiceReadyOperatorIdentities(true);

        if (count($twimlDialIds) === 1) {
            return $twimlDialIds[0];
        }

        return $dispatchRing;
    }

    /**
     * How many {@code <Client>} nouns TwiML will emit for this operator pool (1 when single-target mode).
     *
     * @param  list<string>  $orderedSanitizedIdentities
     */
    public function outboundTwilioClientLegCount(array $orderedSanitizedIdentities): int
    {
        $n = count($orderedSanitizedIdentities);
        if ($n <= 1) {
            return $n;
        }

        $mode = strtolower(trim((string) config('call.voice_outbound_dial_mode', 'single')));

        return $mode === 'single' ? 1 : $n;
    }

    /**
     * Collapse a multi-operator dial list to one identity when {@code call.voice_outbound_dial_mode=single}.
     * Round-robin advances once per call (real Twilio webhook), not on availability polls.
     *
     * @param  list<string>  $orderedSanitizedIdentities
     * @return list<string>
     */
    public function applyVoiceOutboundDialMode(array $orderedSanitizedIdentities): array
    {
        if ($orderedSanitizedIdentities === [] || count($orderedSanitizedIdentities) === 1) {
            return $orderedSanitizedIdentities;
        }

        $mode = strtolower(trim((string) config('call.voice_outbound_dial_mode', 'single')));
        if ($mode !== 'single') {
            return $orderedSanitizedIdentities;
        }

        $strategy = strtolower(trim((string) config('call.voice_outbound_single_pick_strategy', 'round_robin')));
        if ($strategy === 'lowest_user_id') {
            return [$orderedSanitizedIdentities[0]];
        }

        $n = count($orderedSanitizedIdentities);
        $key = 'call:voice_outbound_dial_round_robin';
        $idx = 0;
        try {
            $raw = Cache::increment($key);
            if ($raw === false || $raw === null) {
                throw new \RuntimeException('cache_increment_unsupported');
            }
            $seq = (int) $raw;
            if ($seq < 1) {
                $seq = 1;
            }
            $idx = ($seq - 1) % $n;
            if ($idx < 0) {
                $idx += $n;
            }
        } catch (Throwable) {
            $idx = random_int(0, $n - 1);
        }

        $picked = $orderedSanitizedIdentities[$idx] ?? $orderedSanitizedIdentities[0];
        if (! is_string($picked) || $picked === '') {
            return [$orderedSanitizedIdentities[0]];
        }

        return [$picked];
    }

    /**
     * Shared copy for GET /api/v1/call/availability and GET /api/v1/voice/token (Flutter / mobile contract).
     */
    public function twilioDialContractNote(): string
    {
        return 'Opaque string for Twilio device.connect params.To: one operator’s Client id when exactly one is voice-ready; otherwise the ring-group token (dispatch) — /twilio/voice resolves it. With CALL_VOICE_OUTBOUND_DIAL_MODE=single and several operators ready, TwiML rings only one operator per attempt (round_robin or lowest_user_id); outbound_twilio_client_leg_count is 1. Operator pool = Laratrust staff, admin, super_admin (sanitized users.id as Client identity).';
    }

    public function getAvailabilitySnapshot(): array
    {
        $total = $this->totalOperatorCount();
        $voiceReadyIdsStrict = $this->voiceReadyOperatorIdentities(false);
        $twimlDialIds = $this->voiceReadyOperatorIdentities(true);
        $strictCount = count($voiceReadyIdsStrict);
        $twimlCount = count($twimlDialIds);
        /*
         * Voice uses TwiML rules (wider window / heartbeat fallback). can_connect must match what
         * /twilio/voice will dial or first-time callers get 503 from set-location even when an admin tab is open.
         */
        $canConnect = $total > 0 && $twimlCount > 0;

        $blockReason = null;
        if ($total === 0) {
            $blockReason = 'NO_VOICE_DISPATCH_USERS';
        } elseif (! $canConnect) {
            $blockReason = 'NO_OPERATOR_ONLINE';
        }

        $ttl = max(15, (int) config('call.staff_heartbeat_ttl', 90));
        $adminRaw = trim((string) config('services.twilio.admin_identity'));
        $adminClientIdentity = TwilioClientIdentity::sanitize($adminRaw !== '' ? $adminRaw : (string) config('services.twilio.admin_identity'));
        $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
        $requireVoice = (bool) config('call.require_voice_client_ready', true);
        $dialTo = $this->voiceOutboundDialToIdentity();
        $dialMode = strtolower(trim((string) config('call.voice_outbound_dial_mode', 'single')));
        $legCount = $this->outboundTwilioClientLegCount($twimlDialIds);

        $resolutionHint = match ($blockReason) {
            'NO_VOICE_DISPATCH_USERS' => 'Assign the Laratrust staff, admin, or super_admin role to at least one user who should answer dispatch VoIP.',
            'NO_OPERATOR_ONLINE' => ($requireVoice
                ? 'No operator is reachable for voice within the heartbeat window: staff must POST /api/v1/staff/heartbeat with twilio_voice_ready true after the Twilio Voice SDK registers. To is the ring-group '.$dispatchRing.' unless exactly one operator is online (then To is their Client identity). ADMIN_IDENTITY ('.$adminClientIdentity.') is only a TwiML fallback when nobody is listed.'
                : 'No operator has a fresh presence heartbeat. Web: keep /admin open. Mobile: POST /api/v1/staff/heartbeat.'),
            default => '',
        };

        return [
            'can_connect' => $canConnect,
            /** Operators /twilio/voice would try to ring (mobile contract: primary “lines available”). */
            'available_operators' => $twimlCount,
            /** Stricter heartbeat + voice-ready window (diagnostics / dashboards). */
            'available_operators_strict' => $strictCount,
            /** @deprecated Use available_operators (same as TwiML dial count). Kept for older clients. */
            'available_operators_for_voice' => $twimlCount,
            'total_operators' => $total,
            'block_reason' => $blockReason,
            'heartbeat_ttl_seconds' => $ttl,
            /**
             * Suggested device.connect To: one operator user id when exactly one is reachable; otherwise the
             * ring-group token (not a registered Client — the server expands it to real operator Client ids).
             */
            'operator_twilio_client_identity' => $dialTo,
            'dispatch_twilio_client_identity' => $dispatchRing,
            'voice_ready_operator_twilio_identities' => $voiceReadyIdsStrict,
            'twiml_dial_operator_identities' => $twimlDialIds,
            'twiml_dial_operator_count' => $twimlCount,
            /** Actual {@code <Client>} legs for this outbound dial (1 when single-target mode). */
            'outbound_twilio_client_leg_count' => $legCount,
            'voice_outbound_dial_mode' => $dialMode,
            'voice_outbound_single_pick_strategy' => strtolower(trim((string) config('call.voice_outbound_single_pick_strategy', 'round_robin'))),
            'legacy_admin_twilio_client_identity' => $adminClientIdentity,
            'require_voice_client_ready' => $requireVoice,
            'strict_presence_ttl_seconds' => $this->operatorPresenceTtlSeconds(false),
            'twiml_presence_ttl_seconds' => $this->operatorPresenceTtlSeconds(true),
            'resolution_hint' => $resolutionHint,
        ];
    }

    public function getCachedAvailabilitySnapshot(): array
    {
        $seconds = max(0, (int) config('call.availability_cache_seconds', 2));

        if ($seconds === 0) {
            return $this->getAvailabilitySnapshot();
        }

        return Cache::remember('call:availability_snapshot', $seconds, function () {
            return $this->getAvailabilitySnapshot();
        });
    }

    public function forgetAvailabilityCache(): void
    {
        Cache::forget('call:availability_snapshot');
    }

    public function touchHeartbeat(User $user, ?bool $twilioVoiceReady = null): void
    {
        if (! $user->isVoiceDispatchOperator()) {
            return;
        }

        DB::transaction(function () use ($user, $twilioVoiceReady) {
            $presence = StaffPresence::query()->firstOrNew(['user_id' => $user->id]);
            if ($presence->state !== self::STATE_BUSY) {
                $presence->state = self::STATE_AVAILABLE;
            }
            $presence->last_heartbeat_at = now();
            if ($twilioVoiceReady === true) {
                $presence->voice_client_ready_at = now();
            } elseif ($twilioVoiceReady === false) {
                $presence->voice_client_ready_at = null;
            }
            $presence->save();
        });

        $this->forgetAvailabilityCache();
    }

    public function markBusy(User $user): void
    {
        if (! $user->isVoiceDispatchOperator()) {
            return;
        }

        StaffPresence::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'state' => self::STATE_BUSY,
                'last_heartbeat_at' => now(),
            ]
        );

        $this->forgetAvailabilityCache();
    }

    public function markAvailable(User $user): void
    {
        if (! $user->isVoiceDispatchOperator()) {
            return;
        }

        StaffPresence::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'state' => self::STATE_AVAILABLE,
                'last_heartbeat_at' => now(),
            ]
        );

        $this->forgetAvailabilityCache();
    }
}
