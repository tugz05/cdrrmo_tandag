<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('situational_incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('incident_type')->nullable();
            $table->text('caller_source_of_information')->nullable();
            $table->string('receiver')->nullable();
            $table->dateTime('date_time_received')->nullable();
            $table->string('time_of_response')->nullable();
            $table->text('location')->nullable();
            $table->text('landmark')->nullable();
            $table->text('details_of_incident')->nullable();
            $table->text('vehicles_involved')->nullable();

            $table->boolean('is_alert_response')->default(false);
            $table->boolean('is_verbal_response')->default(false);
            $table->boolean('is_pain_response')->default(false);
            $table->boolean('is_unconscious')->default(false);

            $table->boolean('has_deformity')->default(false);
            $table->boolean('has_contusion')->default(false);
            $table->boolean('has_abrasion')->default(false);
            $table->boolean('has_puncture_penetration')->default(false);
            $table->boolean('has_tenderness')->default(false);
            $table->boolean('has_laceration')->default(false);
            $table->boolean('has_swelling')->default(false);
            $table->text('examination_notes')->nullable();

            $table->text('action_taken')->nullable();
            $table->string('refer_to_hospital', 8)->nullable();
            $table->string('time_transported')->nullable();
            $table->string('name_of_hospital')->nullable();
            $table->text('name_of_responders')->nullable();
            $table->string('name_of_response_vehicle')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('situational_incident_reports');
    }
};
