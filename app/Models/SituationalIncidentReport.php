<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SituationalIncidentReport extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const FORM_VERSION_RUN_SHEET = 2;

    protected $fillable = [
        'user_id',
        'incident_type',
        'caller_source_of_information',
        'receiver',
        'date_time_received',
        'time_of_response',
        'location',
        'landmark',
        'details_of_incident',
        'vehicles_involved',
        'is_alert_response',
        'is_verbal_response',
        'is_pain_response',
        'is_unconscious',
        'has_deformity',
        'has_contusion',
        'has_abrasion',
        'has_puncture_penetration',
        'has_tenderness',
        'has_laceration',
        'has_swelling',
        'examination_notes',
        'injury_diagram_path',
        'injury_markers',
        'action_taken',
        'refer_to_hospital',
        'time_transported',
        'name_of_hospital',
        'name_of_responders',
        'name_of_response_vehicle',
        'form_version',
        'incident_date',
        'response_team',
        'time_informed',
        'time_arrival_at_scene',
        'time_departure_from_scene',
        'time_arrival_at_hospital',
        'time_back_in_service',
        'incident_types',
        'incident_type_others',
        'airway',
        'blood_type',
        'skin',
        'skin_others',
        'address_of_incident',
        'victim_name',
        'victim_age',
        'victim_sex',
        'victim_status',
        'victim_address',
        'consciousness',
        'blood_pressure',
        'pupils',
        'pupil_left',
        'pupil_right',
        'transport_vehicle',
        'patient_disposition',
        'is_work_related',
        'suspected_alcohol_use',
        'suspected_drug_use',
        'patient_refused_care',
        'patient_refused_transport',
        'injury_natures',
        'injury_nature_others',
        'actions_taken',
        'actions_taken_others',
        'sample_signs_symptoms',
        'sample_allergies',
        'sample_medications',
        'sample_past_history',
        'sample_last_intake',
        'sample_events',
        'equipment_used',
        'equipment_others',
        'responders',
        'patient_belongings',
        'team_leader_name',
        'recorder_name',
        'received_by',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['injury_diagram_url'];

    protected function casts(): array
    {
        return [
            'injury_markers' => 'array',
            'date_time_received' => 'datetime',
            'is_alert_response' => 'boolean',
            'is_verbal_response' => 'boolean',
            'is_pain_response' => 'boolean',
            'is_unconscious' => 'boolean',
            'has_deformity' => 'boolean',
            'has_contusion' => 'boolean',
            'has_abrasion' => 'boolean',
            'has_puncture_penetration' => 'boolean',
            'has_tenderness' => 'boolean',
            'has_laceration' => 'boolean',
            'has_swelling' => 'boolean',
            'form_version' => 'integer',
            'incident_date' => 'date:Y-m-d',
            'incident_types' => 'array',
            'skin' => 'array',
            'injury_natures' => 'array',
            'actions_taken' => 'array',
            'equipment_used' => 'array',
            'responders' => 'array',
            'pupil_left' => 'boolean',
            'pupil_right' => 'boolean',
            'is_work_related' => 'boolean',
            'suspected_alcohol_use' => 'boolean',
            'suspected_drug_use' => 'boolean',
            'patient_refused_care' => 'boolean',
            'patient_refused_transport' => 'boolean',
        ];
    }

    /**
     * Public URL of the marked body diagram PNG uploaded from the mobile app.
     */
    public function getInjuryDiagramUrlAttribute(): ?string
    {
        return $this->injury_diagram_path
            ? asset('storage/'.$this->injury_diagram_path)
            : null;
    }

    public function isRunSheet(): bool
    {
        return (int) $this->form_version === self::FORM_VERSION_RUN_SHEET;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
