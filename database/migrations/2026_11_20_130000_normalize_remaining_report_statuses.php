<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Catches the remaining spellings: "in progress" in any case, plus "resolved" and lower-case
     * "rescued", which some older rows hold. The admin pages only recognise ReportStatusEnum values.
     */
    public function up(): void
    {
        DB::table('reports')
            ->whereRaw("LOWER(status) IN ('in_progress', 'in progress')")
            ->where('status', '!=', 'In Progress')
            ->update(['status' => 'In Progress']);

        DB::table('reports')
            ->whereRaw("LOWER(status) IN ('completed', 'resolved', 'rescued')")
            ->where('status', '!=', 'Rescued')
            ->update(['status' => 'Rescued']);
    }

    public function down(): void {}
};
