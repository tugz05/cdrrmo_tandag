<?php

namespace App\Services;

use App\Models\StaffPresence;
use App\Models\User;
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
     * Online = fresh heartbeat, not in an active call (state available).
     */
    public function availableOperatorCount(): int
    {
        $ids = $this->operatorUserIds();
        if ($ids === []) {
            return 0;
        }

        $ttl = max(15, (int) config('call.staff_heartbeat_ttl', 90));
        $cutoff = now()->subSeconds($ttl);

        return (int) StaffPresence::query()
            ->whereIn('user_id', $ids)
            ->where('state', self::STATE_AVAILABLE)
            ->whereNotNull('last_heartbeat_at')
            ->where('last_heartbeat_at', '>=', $cutoff)
            ->count();
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
        $available = $this->availableOperatorCount();
        $canConnect = $total > 0 && $available > 0;

        $blockReason = null;
        if ($total === 0) {
            $blockReason = 'NO_ADMIN_ROLE_USERS';
        } elseif (! $canConnect) {
            $blockReason = 'NO_OPERATOR_ONLINE';
        }

        $ttl = max(15, (int) config('call.staff_heartbeat_ttl', 90));
        $adminClientIdentity = (string) config('services.twilio.admin_identity');

        $resolutionHint = match ($blockReason) {
            'NO_ADMIN_ROLE_USERS' => 'Assign the admin or super_admin role to at least one user in the database.',
            'NO_OPERATOR_ONLINE' => 'No operator is available for voice. Web: open /admin and click or tap once so Twilio Voice registers; staff heartbeat (and "available" status) starts only after that. Mobile: POST /api/v1/staff/heartbeat does not register a browser Client by itself; for web callers, a dispatch browser must still be online with the same identity as .env ADMIN_IDENTITY or calls return 31603.',
            default => '',
        };

        return [
            'can_connect' => $canConnect,
            'available_operators' => $available,
            'total_operators' => $total,
            'block_reason' => $blockReason,
            'heartbeat_ttl_seconds' => $ttl,
            'operator_twilio_client_identity' => $adminClientIdentity,
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

    public function touchHeartbeat(User $user): void
    {
        if (! $user->hasRole(['admin', 'super_admin'])) {
            return;
        }

        DB::transaction(function () use ($user) {
            $presence = StaffPresence::query()->firstOrNew(['user_id' => $user->id]);
            if ($presence->state !== self::STATE_BUSY) {
                $presence->state = self::STATE_AVAILABLE;
            }
            $presence->last_heartbeat_at = now();
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
