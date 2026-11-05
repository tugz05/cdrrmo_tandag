<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything the admin Live Map shows: open incidents and field rescuers.
 * Never log what this returns; it holds people's coordinates.
 */
class LiveMapService
{
    private const ACTIVE_RESCUE_STATUSES = ['en_route', 'arrived'];

    public function __construct(private LiveLocationService $liveLocation) {}

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        $query = $this->openReports();
        $total = (clone $query)->count();

        $reports = $query
            ->with('user:id,name,fname,mname,lname,suffix,phone')
            ->orderByRaw("CASE WHEN rescue_status IN ('en_route', 'arrived') THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->limit((int) config('live_location.overview_report_limit'))
            ->get();

        $assignments = $this->assignments($reports);
        $roster = $this->roster($assignments->keys()->all());

        $cached = $this->liveLocation->latestPositions(
            $roster->pluck('id')->merge($reports->pluck('user_id'))->filter()->unique()->all()
        );
        $stored = $this->liveLocation->lastStoredPositions($reports->pluck('id')->all());

        return [
            'rescuers' => $this->rescuers($roster, $assignments, $cached, $stored),
            'incidents' => $this->incidents($reports, $cached, $stored),
            'incidents_total' => $total,
            'recent_days' => (int) config('live_location.overview_recent_days'),
            'server_time' => now()->toIso8601String(),
        ];
    }

    /**
     * Open reports from recent days, plus any rescue still under way however old it is.
     */
    private function openReports(): Builder
    {
        $since = now()->subDays((int) config('live_location.overview_recent_days'));

        return Report::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('rescue_status')
                ->orWhere('rescue_status', '!=', 'completed'))
            ->whereRaw(
                'LOWER(status) NOT IN ('.implode(', ', array_fill(0, count(LiveLocationService::CLOSED_STATUSES), '?')).')',
                LiveLocationService::CLOSED_STATUSES
            )
            ->where(fn (Builder $query) => $query
                ->where('created_at', '>=', $since)
                ->orWhereIn('rescue_status', self::ACTIVE_RESCUE_STATUSES));
    }

    /**
     * @return Collection<int, Report> Newest active rescue per rescuer, keyed by rescuer id
     */
    private function assignments(Collection $reports): Collection
    {
        return $reports
            ->filter(fn (Report $report) => $report->rescuer_user_id !== null
                && in_array($report->rescue_status, self::ACTIVE_RESCUE_STATUSES, true))
            ->unique('rescuer_user_id')
            ->keyBy('rescuer_user_id');
    }

    /**
     * @param  array<int, int>  $assignedIds
     * @return Collection<int, User>
     */
    private function roster(array $assignedIds): Collection
    {
        return User::query()
            ->where(fn (Builder $query) => $query
                ->voiceDispatchOperators()
                ->orWhereIn('id', $assignedIds))
            ->with('roles:id,name')
            ->get(['id', 'name', 'fname', 'mname', 'lname', 'suffix', 'phone']);
    }

    /**
     * Field staff always appear. Admins usually work from a desk, so they only
     * appear once they are sharing a position or are assigned to a rescue.
     */
    private function rescuers(Collection $roster, Collection $assignments, array $cached, Collection $stored): array
    {
        return $roster
            ->map(function (User $user) use ($assignments, $cached, $stored) {
                $assignment = $assignments->get($user->id);
                $position = $cached[$user->id]
                    ?? ($assignment ? $stored->get("{$assignment->id}:{$user->id}") : null);

                if (! $assignment && ! $position && ! $user->roles->contains('name', 'staff')) {
                    return null;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'phone' => $user->phone,
                    'position' => $this->publicPosition($position),
                    'assignment' => $assignment ? [
                        'report_id' => $assignment->id,
                        'rescue_status' => $assignment->rescue_status,
                    ] : null,
                ];
            })
            ->filter()
            ->sortBy(fn (array $rescuer) => [
                $rescuer['assignment'] ? 0 : 1,
                $rescuer['position'] ? 0 : 1,
                $rescuer['name'],
            ])
            ->values()
            ->all();
    }

    private function incidents(Collection $reports, array $cached, Collection $stored): array
    {
        // A person's cached position has no report on it, so it belongs to their newest report only.
        $newestByUser = $reports->sortByDesc('id')->unique('user_id')->pluck('id', 'user_id');

        return $reports
            ->map(function (Report $report) use ($newestByUser, $cached, $stored) {
                $position = $newestByUser->get($report->user_id) === $report->id
                    ? ($cached[$report->user_id] ?? null)
                    : null;

                return [
                    'id' => $report->id,
                    'type' => $report->type,
                    'status' => $report->status,
                    'rescue_status' => $report->rescue_status,
                    'rescuer_user_id' => $report->rescuer_user_id,
                    'reporter' => $report->user?->full_name ?? $report->reported_by,
                    'phone' => $report->user?->phone,
                    'address' => $report->address,
                    'created_at' => $report->created_at?->toIso8601String(),
                    'reported_location' => $this->reportedLocation($report),
                    'resident' => $this->publicPosition($position ?? $stored->get("{$report->id}:{$report->user_id}")),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Reports store coordinates as free strings, and some are empty or zero.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function reportedLocation(Report $report): ?array
    {
        if (! is_numeric($report->latitude) || ! is_numeric($report->longitude)) {
            return null;
        }

        $lat = (float) $report->latitude;
        $lng = (float) $report->longitude;

        if (abs($lat) > 90 || abs($lng) > 180 || ($lat === 0.0 && $lng === 0.0)) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function publicPosition(?array $position): ?array
    {
        if ($position === null) {
            return null;
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
}
