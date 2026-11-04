<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('situational_incident_reports', function (Blueprint $table) {
            $table->string('injury_diagram_path')->nullable()->after('examination_notes');
            $table->json('injury_markers')->nullable()->after('injury_diagram_path');
        });
    }

    public function down(): void
    {
        Schema::table('situational_incident_reports', function (Blueprint $table) {
            $table->dropColumn(['injury_diagram_path', 'injury_markers']);
        });
    }
};
