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
     * @return list<string>
     */
    public function voiceReadyOperatorIdentities(): array
    {
        $ids = $this->operatorUserIds();
        if ($ids === []) {
            return [];
        }

        $ttl = max(15, (int) config('call.staff_heartbeat_ttl', 90));
        $cutoff = now()->subSeconds($ttl);
        $requireVoiceClient = (bool) config('call.require_voice_client_ready', true);

        $q = StaffPresence::query()
            ->whereIn('user_id', $ids)
            ->where('state', self::STATE_AVAILABLE)
            ->whereNotNull('last_heartbeat_at')
            ->where('last_heartbeat_at', '>=', $cutoff);

        if ($requireVoiceClient) {
            $q->whereNotNull('voice_client_ready_at')
                ->where('voice_client_ready_at', '>=', $cutoff);
        }

        $rawIds = $q->orderBy('user_id')->pluck('user_id')->all();

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

    public function availableOperatorCount(): int
    {
        return count($this->voiceReadyOperatorIdentities());
    }

    public function isCallRoutingAllowed(): bool
    {
        if ($this->totalOperatorCount() === 0) {
            return false;
        }

        return $this->availableOperatorCount() > 0;
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
        $adminClientIdentity = TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity'));
        $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
        $requireVoice = (bool) config('call.require_voice_client_ready', true);

        $resolutionHint = match ($blockReason) {
            'NO_ADMIN_ROLE_USERS' => 'Assign the admin or super_admin role to at least one user in the database.',
            'NO_OPERATOR_ONLINE' => ($requireVoice
                ? 'No dispatch operator is voice-ready: each operator opens /admin so Twilio Voice JS SDK registers with their user id, and heartbeats send twilio_voice_ready after Device emits registered. Outbound calls use connect param To='.$dispatchRing.' (TWILIO_DISPATCH_RING_GROUP); TwiML rings all ready operators. Legacy ADMIN_IDENTITY ('.$adminClientIdentity.') is only a fallback if none are listed at dial time. Flutter: POST twilio_voice_ready when Voice registers, or set CALL_REQUIRE_VOICE_CLIENT_READY=false only if you accept possible 31603.'
                : 'No operator has a fresh presence heartbeat. Web: keep /admin open. Mobile: POST /api/v1/staff/heartbeat.'),
            default => '',
        };

        return [
            'can_connect' => $canConnect,
            'available_operators' => $available,
            'total_operators' => $total,
            'block_reason' => $blockReason,
            'heartbeat_ttl_seconds' => $ttl,
            /** Value for device.connect({ params: { To } }) — expanded server-side to all voice-ready operator Client identities. */
            'operator_twilio_client_identity' => $dispatchRing,
            'dispatch_twilio_client_identity' => $dispatchRing,
            'voice_ready_operator_twilio_identities' => $voiceReadyIds,
            'legacy_admin_twilio_client_identity' => $adminClientIdentity,
            'require_voice_client_ready' => $requireVoice,
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
