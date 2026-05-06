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
        'action_taken',
        'refer_to_hospital',
        'time_transported',
        'name_of_hospital',
        'name_of_responders',
        'name_of_response_vehicle',
    ];

    protected function casts(): array
    {
        return [
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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
