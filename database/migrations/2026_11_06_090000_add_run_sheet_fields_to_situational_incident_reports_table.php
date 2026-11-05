<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every column is nullable or has a default: existing reports stay on form
     * version 1, and released apps that don't know these fields keep working.
     */
    public function up(): void
    {
        Schema::table('situational_incident_reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('form_version')->default(1)->after('user_id');
            $table->string('incident_no', 100)->nullable()->after('form_version');
            $table->date('incident_date')->nullable()->after('incident_no');
            $table->string('response_team', 20)->nullable()->after('incident_date');

            $table->string('time_informed', 20)->nullable();
            $table->string('time_arrival_at_scene', 20)->nullable();
            $table->string('time_departure_from_scene', 20)->nullable();
            $table->string('time_arrival_at_hospital', 20)->nullable();
            $table->string('time_back_in_service', 20)->nullable();

            $table->json('incident_types')->nullable();
            $table->string('incident_type_others')->nullable();
            $table->string('airway', 30)->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->json('skin')->nullable();
            $table->string('skin_others')->nullable();
            $table->text('address_of_incident')->nullable();

            $table->string('victim_name')->nullable();
            $table->string('victim_age', 20)->nullable();
            $table->string('victim_sex', 10)->nullable();
            $table->string('victim_status', 50)->nullable();
            $table->text('victim_address')->nullable();
            $table->string('consciousness', 20)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->string('pupils', 20)->nullable();
            $table->boolean('pupil_left')->default(false);
            $table->boolean('pupil_right')->default(false);

            $table->string('transport_vehicle', 20)->nullable();
            $table->string('patient_disposition', 30)->nullable();
            $table->boolean('is_work_related')->default(false);
            $table->boolean('suspected_alcohol_use')->default(false);
            $table->boolean('suspected_drug_use')->default(false);
            $table->boolean('patient_refused_care')->default(false);
            $table->boolean('patient_refused_transport')->default(false);

            $table->json('injury_natures')->nullable();
            $table->string('injury_nature_others')->nullable();
            $table->json('actions_taken')->nullable();
            $table->string('actions_taken_others')->nullable();

            $table->text('sample_signs_symptoms')->nullable();
            $table->text('sample_allergies')->nullable();
            $table->text('sample_medications')->nullable();
            $table->text('sample_past_history')->nullable();
            $table->text('sample_last_intake')->nullable();
            $table->text('sample_events')->nullable();

            $table->json('equipment_used')->nullable();
            $table->string('equipment_others')->nullable();
            $table->json('responders')->nullable();
            $table->text('patient_belongings')->nullable();

            $table->string('team_leader_name')->nullable();
            $table->string('recorder_name')->nullable();
            $table->string('received_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('situational_incident_reports', function (Blueprint $table) {
            $table->dropColumn([
                'form_version', 'incident_no', 'incident_date', 'response_team',
                'time_informed', 'time_arrival_at_scene', 'time_departure_from_scene',
                'time_arrival_at_hospital', 'time_back_in_service',
                'incident_types', 'incident_type_others', 'airway', 'blood_type', 'skin', 'skin_others',
                'address_of_incident', 'victim_name', 'victim_age', 'victim_sex', 'victim_status',
                'victim_address', 'consciousness', 'blood_pressure', 'pupils', 'pupil_left', 'pupil_right',
                'transport_vehicle', 'patient_disposition', 'is_work_related', 'suspected_alcohol_use',
                'suspected_drug_use', 'patient_refused_care', 'patient_refused_transport',
                'injury_natures', 'injury_nature_others', 'actions_taken', 'actions_taken_others',
                'sample_signs_symptoms', 'sample_allergies', 'sample_medications', 'sample_past_history',
                'sample_last_intake', 'sample_events', 'equipment_used', 'equipment_others', 'responders',
                'patient_belongings', 'team_leader_name', 'recorder_name', 'received_by',
            ]);
        });
    }
};
