<?php

namespace App\Services;

use App\Events\LiveLocationUpdated;
use App\Models\Report;
use App\Models\ReportLocationPoint;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Latest positions live in the cache; only a thinned path reaches the database.
 * Never log coordinates here, and never log a raw exception message: a failed
 * insert's message contains its bound values.
 */
class LiveLocationService
{
    /** Lowercase, because existing data mixes "Rescued" and "completed". */
    public const CLOSED_STATUSES = ['completed', 'resolved', 'rescued'];

    private const EARTH_RADIUS_METERS = 6371000;

    public static function forgetReport(int $reportId): void
    {
        Cache::forget("live_location:report:{$reportId}");
    }

    public static function isClosed(?string $rescueStatus, ?string $status): bool
    {
        return $rescueStatus === 'completed'
            || in_array(strtolower((string) $status), self::CLOSED_STATUSES, true);
    }

    /**
     * @return array{user_id: ?int, rescuer_user_id: ?int, rescue_status: ?string, closed: bool}|null
     */
    public function reportContext(int $reportId): ?array
    {
        $key = "live_location:report:{$reportId}";

        if ($context = Cache::get($key)) {
            return $context;
        }

        $report = Report::query()->find($reportId, ['id', 'user_id', 'rescuer_user_id', 'rescue_status', 'status']);
        if (! $report) {
            return null;
        }

        $context = [
            'user_id' => $report->user_id === null ? null : (int) $report->user_id,
            'rescuer_user_id' => $report->rescuer_user_id === null ? null : (int) $report->rescuer_user_id,
            'rescue_status' => $report->rescue_status,
            'closed' => self::isClosed($report->rescue_status, $report->status),
        ];

        Cache::put($key, $context, (int) config('live_location.report_cache_seconds'));

        return $context;
    }

    public function latestPosition(int $userId): ?array
    {
        return Cache::get("live_location:user:{$userId}");
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, array<string, mixed>> Keyed by user id; users with no position are left out
     */
    public function latestPositions(array $userIds): array
    {
        $positions = [];

        foreach ($userIds as $userId) {
            if ($position = $this->latestPosition((int) $userId)) {
                $positions[(int) $userId] = $position;
            }
        }

        return $positions;
    }

    /**
     * Each person's last stored point on these reports.
     *
     * @param  array<int, int>  $reportIds
     * @return Collection<string, array<string, mixed>> Keyed by "reportId:userId"
     */
    public function lastStoredPositions(array $reportIds): Collection
    {
        if ($reportIds === []) {
            return collect();
        }

        $latest = ReportLocationPoint::query()
            ->selectRaw('report_id, user_id, MAX(recorded_at) AS latest_recorded_at')
            ->whereIn('report_id', $reportIds)
            ->groupBy('report_id', 'user_id');

        return ReportLocationPoint::query()
            ->joinSub($latest, 'latest', function ($join) {
                $join->on('report_location_points.report_id', '=', 'latest.report_id')
                    ->on('report_location_points.user_id', '=', 'latest.user_id')
                    ->on('report_location_points.recorded_at', '=', 'latest.latest_recorded_at');
            })
            ->get(['report_location_points.*'])
            ->mapWithKeys(fn (ReportLocationPoint $point) => [
                "{$point->report_id}:{$point->user_id}" => [
                    'lat' => $point->lat,
                    'lng' => $point->lng,
                    'accuracy' => $point->accuracy,
                    'speed' => $point->speed,
                    'heading' => $point->heading,
                    'recorded_at' => $point->recorded_at->toIso8601String(),
                ],
            ]);
    }

    public static function ageSeconds(array $position): int
    {
        return (int) max(0, floor((now()->getTimestampMs() - $position['recorded_ms']) / 1000));
    }

    /**
     * @param  array<int, array<string, mixed>>  $samples
     * @param  array<string, mixed>|null  $context  Null when sharing on duty without a report
     */
    public function ingest(User $user, ?int $reportId, ?array $context, string $mode, array $samples): void
    {
        $positions = $this->normalize($samples);
        if ($positions === []) {
            return;
        }

        $newest = $this->updateLatest($user->id, $positions);

        if ($reportId !== null && $mode !== 'on_duty') {
            $this->storeThinned($user->id, $reportId, $positions);
        }

        if ($newest !== null) {
            $this->broadcast($user->id, $reportId, $context, $newest);
        }
    }

    public function recordRequest(): void
    {
        $key = $this->loadKey(now());
        Cache::add($key, 0, 180);
        Cache::increment($key);
    }

    public function nextIntervalSeconds(string $mode): int
    {
        // The current minute is only partly counted, so also look at the last full one.
        $load = max(
            (int) Cache::get($this->loadKey(now()), 0),
            (int) Cache::get($this->loadKey(now()->subMinute()), 0)
        );

        $column = $mode === 'navigating' ? 'navigating' : 'other';
        $interval = (int) config('live_location.interval_seconds');

        foreach (config('live_location.load_tiers') as $tier) {
            if ($load >= $tier['requests_per_minute']) {
                $interval = (int) $tier[$column];
            }
        }

        return min($interval, (int) config("live_location.max_interval_seconds.{$column}"));
    }

    /**
     * Positions for the staff map: the cache first, then the last stored point.
     *
     * @return array<string, mixed>
     */
    public function mapState(Report $report): array
    {
        return [
            'resident' => $this->mapPosition($report->user_id, $report->id),
            'rescuer' => $this->mapPosition($report->rescuer_user_id, $report->id),
            'rescue_status' => $report->rescue_status,
            'status' => $report->status,
            'session_active' => ! self::isClosed($report->rescue_status, $report->status),
            'server_time' => now()->toIso8601String(),
        ];
    }

    /**
     * Stored path for the people shown on the map. Other staff who shared on this
     * report are left out, so the line only ever follows the resident and rescuer.
     *
     * @return array{resident: array<int, array{0: float, 1: float}>, rescuer: array<int, array{0: float, 1: float}>}
     */
    public function mapPaths(Report $report): array
    {
        return [
            'resident' => $this->storedPath($report->id, $report->user_id),
            'rescuer' => $this->storedPath($report->id, $report->rescuer_user_id),
        ];
    }

    /**
     * Clamps future timestamps to now and drops anything too old.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalize(array $samples): array
    {
        $now = now();
        $oldest = $now->copy()->subSeconds((int) config('live_location.max_sample_age_seconds'));
        $positions = [];

        foreach ($samples as $sample) {
            $recorded = Carbon::parse($sample['recorded_at'])->utc();

            if ($recorded->lt($oldest)) {
                continue;
            }
            if ($recorded->gt($now)) {
                $recorded = $now->copy()->utc();
            }

            $positions[] = [
                'lat' => (float) $sample['lat'],
                'lng' => (float) $sample['lng'],
                'accuracy' => isset($sample['accuracy']) ? (float) $sample['accuracy'] : null,
                'speed' => isset($sample['speed']) ? (float) $sample['speed'] : null,
                'heading' => isset($sample['heading']) ? (float) $sample['heading'] : null,
                'recorded_at' => $recorded->toIso8601String(),
                'recorded_ms' => $recorded->getTimestampMs(),
            ];
        }

        return $positions;
    }

    /**
     * Offline batches arrive late, so only a newer position replaces the cached one.
     *
     * @return array<string, mixed>|null The position when the cache changed
     */
    private function updateLatest(int $userId, array $positions): ?array
    {
        $newest = null;
        foreach ($positions as $position) {
            if ($newest === null || $position['recorded_ms'] >= $newest['recorded_ms']) {
                $newest = $position;
            }
        }

        $current = $this->latestPosition($userId);
        if ($current !== null && $newest['recorded_ms'] <= $current['recorded_ms']) {
            return null;
        }

        Cache::put("live_location:user:{$userId}", $newest, (int) config('live_location.position_ttl_seconds'));

        return $newest;
    }

    private function storeThinned(int $userId, int $reportId, array $positions): void
    {
        $key = "live_location:last_stored:{$reportId}:{$userId}";
        $last = Cache::get($key) ?? $this->lastStoredFromDatabase($userId, $reportId);
        $minDistance = (float) config('live_location.store_min_distance_meters');
        $minMilliseconds = (int) config('live_location.store_min_seconds') * 1000;
        $timezone = config('app.timezone');
        $rows = [];

        foreach ($positions as $position) {
            if ($last !== null
                && abs($position['recorded_ms'] - $last['recorded_ms']) < $minMilliseconds
                && $this->distanceMeters($last, $position) < $minDistance) {
                continue;
            }

            $rows[] = [
                'report_id' => $reportId,
                'user_id' => $userId,
                'lat' => $position['lat'],
                'lng' => $position['lng'],
                'accuracy' => $position['accuracy'],
                'speed' => $position['speed'],
                'heading' => $position['heading'],
                // insert() does not convert timezones, so match how Eloquent reads this column.
                'recorded_at' => Carbon::parse($position['recorded_at'])->setTimezone($timezone)->format('Y-m-d H:i:s'),
            ];
            $last = $position;
        }

        if ($rows === []) {
            return;
        }

        ReportLocationPoint::query()->insert($rows);

        Cache::put($key, [
            'lat' => $last['lat'],
            'lng' => $last['lng'],
            'recorded_ms' => $last['recorded_ms'],
        ], (int) config('live_location.max_sample_age_seconds'));
    }

    private function lastStoredFromDatabase(int $userId, int $reportId): ?array
    {
        $point = ReportLocationPoint::query()
            ->where('report_id', $reportId)
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->first(['lat', 'lng', 'recorded_at']);

        return $point ? [
            'lat' => $point->lat,
            'lng' => $point->lng,
            'recorded_ms' => $point->recorded_at->getTimestampMs(),
        ] : null;
    }

    /**
     * Sends at most one event per user and report per window. The lock is left to
     * expire on purpose; the next event carries the newer position anyway.
     */
    private function broadcast(int $userId, ?int $reportId, ?array $context, array $position): void
    {
        $role = $this->broadcastRole($userId, $context);
        if ($role === null) {
            return;
        }

        try {
            $scope = $reportId ?? 'duty';
            $lock = Cache::lock(
                "live_location:broadcast:{$userId}:{$scope}",
                (int) config('live_location.broadcast_min_seconds')
            );

            if ($lock->get()) {
                LiveLocationUpdated::dispatch($reportId, $userId, $role, $position);
            }
        } catch (Throwable $exception) {
            Log::warning('Live location broadcast failed', [
                'report_id' => $reportId,
                'exception' => $exception::class,
            ]);
        }
    }

    private function broadcastRole(int $userId, ?array $context): ?string
    {
        if ($context === null) {
            return 'rescuer';
        }
        if ($context['user_id'] === $userId) {
            return 'resident';
        }

        return $context['rescuer_user_id'] === $userId ? 'rescuer' : null;
    }

    private function mapPosition(?int $userId, int $reportId): ?array
    {
        if ($userId === null) {
            return null;
        }

        $position = $this->latestPosition($userId);

        if ($position === null) {
            $point = ReportLocationPoint::query()
                ->where('report_id', $reportId)
                ->where('user_id', $userId)
                ->orderByDesc('recorded_at')
                ->first();

            if (! $point) {
                return null;
            }

            $position = [
                'lat' => $point->lat,
                'lng' => $point->lng,
                'accuracy' => $point->accuracy,
                'speed' => $point->speed,
                'heading' => $point->heading,
                'recorded_at' => $point->recorded_at->toIso8601String(),
                'recorded_ms' => $point->recorded_at->getTimestampMs(),
            ];
        }

        return [
            'lat' => $position['lat'],
            'lng' => $position['lng'],
            'accuracy' => $position['accuracy'],
            'speed' => $position['speed'],
            'heading' => $position['heading'],
            'recorded_at' => $position['recorded_at'],
        ];
    }

    private function storedPath(int $reportId, ?int $userId): array
    {
        if ($userId === null) {
            return [];
        }

        return ReportLocationPoint::query()
            ->where('report_id', $reportId)
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->limit((int) config('live_location.map_path_limit'))
            ->get(['lat', 'lng'])
            ->reverse()
            ->map(fn (ReportLocationPoint $point) => [$point->lat, $point->lng])
            ->values()
            ->all();
    }

    private function distanceMeters(array $from, array $to): float
    {
        $fromLat = deg2rad($from['lat']);
        $toLat = deg2rad($to['lat']);
        $deltaLat = $toLat - $fromLat;
        $deltaLng = deg2rad($to['lng'] - $from['lng']);

        $a = sin($deltaLat / 2) ** 2 + cos($fromLat) * cos($toLat) * sin($deltaLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(min(1, sqrt($a)));
    }

    private function loadKey(Carbon $time): string
    {
        return 'live_location:load:'.$time->copy()->utc()->format('YmdHi');
    }
}
