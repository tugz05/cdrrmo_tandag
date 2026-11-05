<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IncidentNumberGenerator
{
    /** APP_TIMEZONE is UTC, but a report filed just after midnight on New Year's Day belongs to the new year. */
    public const TIMEZONE = 'Asia/Manila';

    public static function format(int $year, int $number): string
    {
        return sprintf('INC-%d-%04d', $year, $number);
    }

    /**
     * Call inside the transaction that saves the report: the row lock is what
     * keeps concurrent saves from sharing a number, and it lasts until commit.
     */
    public function next(): string
    {
        $year = now(self::TIMEZONE)->year;
        $sequences = DB::table('incident_number_sequences');

        // INSERT IGNORE takes a shared lock on an existing row, and two saves holding
        // that lock deadlock on the FOR UPDATE below. So only insert when it's missing.
        if (! (clone $sequences)->where('year', $year)->exists()) {
            (clone $sequences)->insertOrIgnore(['year' => $year, 'last_number' => 0]);
        }

        $number = (int) (clone $sequences)->where('year', $year)->lockForUpdate()->value('last_number') + 1;

        (clone $sequences)->where('year', $year)->update(['last_number' => $number]);

        return self::format($year, $number);
    }
}
