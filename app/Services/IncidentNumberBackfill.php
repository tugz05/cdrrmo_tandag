<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One-time migration step. Uses the query builder so soft-deleted rows are included.
 */
class IncidentNumberBackfill
{
    private const TABLE = 'situational_incident_reports';

    /** Case-insensitive because MySQL's unique index would treat "inc-2026-0005" as a duplicate. */
    private const STANDARD_FORMAT = '/^INC-(\d{4})-(\d{4,})$/i';

    public function run(): void
    {
        DB::transaction(function () {
            $counters = $this->highestExistingNumbers();

            $blanks = DB::table(self::TABLE)
                ->where('form_version', 2)
                ->where(fn ($query) => $query->whereNull('incident_no')->orWhereRaw("TRIM(incident_no) = ''"))
                ->orderBy('id')
                ->get(['id', 'created_at']);

            foreach ($blanks as $report) {
                $year = $this->manilaYear($report->created_at);
                $counters[$year] = ($counters[$year] ?? 0) + 1;

                DB::table(self::TABLE)
                    ->where('id', $report->id)
                    ->update(['incident_no' => IncidentNumberGenerator::format($year, $counters[$year])]);
            }

            foreach ($counters as $year => $lastNumber) {
                DB::table('incident_number_sequences')->updateOrInsert(
                    ['year' => $year],
                    ['last_number' => $lastNumber],
                );
            }
        });
    }

    /**
     * Grouped by the database, so its collation decides what counts as a duplicate,
     * exactly as the unique index will.
     *
     * @return Collection<int, string>
     */
    public function duplicates(): Collection
    {
        return DB::table(self::TABLE)
            ->whereNotNull('incident_no')
            ->groupBy('incident_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('incident_no');
    }

    /**
     * Every form version and soft-deleted rows count: all of them can collide.
     *
     * @return array<int, int> Highest number per year
     */
    private function highestExistingNumbers(): array
    {
        $highest = [];

        $numbers = DB::table(self::TABLE)->whereNotNull('incident_no')->pluck('incident_no');

        foreach ($numbers as $number) {
            if (preg_match(self::STANDARD_FORMAT, trim($number), $matches)) {
                $year = (int) $matches[1];
                $highest[$year] = max($highest[$year] ?? 0, (int) $matches[2]);
            }
        }

        return $highest;
    }

    private function manilaYear(?string $createdAt): int
    {
        $created = $createdAt ? Carbon::parse($createdAt, config('app.timezone')) : now();

        return $created->setTimezone(IncidentNumberGenerator::TIMEZONE)->year;
    }
}
