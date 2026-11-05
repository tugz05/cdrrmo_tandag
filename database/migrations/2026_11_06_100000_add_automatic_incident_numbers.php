<?php

use App\Services\IncidentNumberBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    private const INDEX = 'sir_incident_no_unique';

    public function up(): void
    {
        Schema::create('incident_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number');
        });

        $backfill = new IncidentNumberBackfill;
        $backfill->run();

        $duplicates = $backfill->duplicates();

        if ($duplicates->isNotEmpty()) {
            $message = 'Skipped the unique index on incident_no. Duplicated values: '.$duplicates->implode(', ');
            Log::warning($message);
            (new ConsoleOutput)->writeln("<comment>WARNING: {$message}</comment>");

            return;
        }

        Schema::table('situational_incident_reports', function (Blueprint $table) {
            $table->unique('incident_no', self::INDEX);
        });
    }

    /**
     * Assigned numbers stay on the reports.
     */
    public function down(): void
    {
        if (Schema::hasIndex('situational_incident_reports', self::INDEX)) {
            Schema::table('situational_incident_reports', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }

        Schema::dropIfExists('incident_number_sequences');
    }
};
