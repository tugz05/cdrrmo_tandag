<?php

namespace App\Services;

use App\Models\StaffPresence;
use App\Models\User;
use App\Support\TwilioClientIdentity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StaffPresenceService
{
    public const STATE_AVAILABLE = 'available';

    public const STATE_BUSY = 'busy';

    public function operatorUserIds(): array
    {
        return User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
            })
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

    public function getAvailabilitySnapshot(): array
    {
        $total = $this->totalOperatorCount();
        $voiceReadyIds = $this->voiceReadyOperatorIdentities();
        $available = count($voiceReadyIds);
        $canConnect = $total > 0 && $available > 0;

        $blockReason = null;
        if ($total === 0) {
            $blockReason = 'NO_ADMIN_ROLE_USERS';
        } elseif (! $canConnect) {
            $blockReason = 'NO_OPERATOR_ONLINE';
        }

        $ttl = max(15, (int) config('call.staff_heartbeat_ttl', 90));
        $adminRaw = trim((string) config('services.twilio.admin_identity'));
        $adminClientIdentity = TwilioClientIdentity::sanitize($adminRaw !== '' ? $adminRaw : (string) config('services.twilio.admin_identity'));
        $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
        $requireVoice = (bool) config('call.require_voice_client_ready', true);

        $resolutionHint = match ($blockReason) {
            'NO_ADMIN_ROLE_USERS' => 'Assign the admin or super_admin role to at least one user in the database.',
            'NO_OPERATOR_ONLINE' => ($requireVoice
                ? 'No dispatch operator is voice-ready: each operator opens /admin so Twilio Voice registers with their user id, and heartbeats send twilio_voice_ready after Device emits registered. Outbound VoIP uses connect param To='.$dispatchRing.' (TWILIO_DISPATCH_RING_GROUP); TwiML rings every ready operator. ADMIN_IDENTITY ('.$adminClientIdentity.') is used only if none are listed at dial time. Flutter: POST twilio_voice_ready when Voice registers, or set CALL_REQUIRE_VOICE_CLIENT_READY=false only if you accept possible 31603.'
                : 'No operator has a fresh presence heartbeat. Web: keep /admin open. Mobile: POST /api/v1/staff/heartbeat.'),
            default => '',
        };

        return [
            'can_connect' => $canConnect,
            'available_operators' => $available,
            'total_operators' => $total,
            'block_reason' => $blockReason,
            'heartbeat_ttl_seconds' => $ttl,
            /** Value for device.connect({ params: { To } }) — expanded on `/twilio/voice` to all voice-ready operator Client identities. */
            'operator_twilio_client_identity' => $dispatchRing,
            'dispatch_twilio_client_identity' => $dispatchRing,
            'voice_ready_operator_twilio_identities' => $voiceReadyIds,
            'twiml_dial_operator_count' => count($this->voiceReadyOperatorIdentities(true)),
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
        if (! $user->hasRole(['admin', 'super_admin'])) {
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
        if (! $user->hasRole(['admin', 'super_admin'])) {
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
        if (! $user->hasRole(['admin', 'super_admin'])) {
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
