<?php

namespace App\Http\Requests\API\V1;

use App\Traits\JResponseApiTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class SituationalIncidentReportRequest extends FormRequest
{
    use JResponseApiTrait;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'incident_type' => ['nullable', 'string', 'max:500'],
            'caller_source_of_information' => ['nullable', 'string', 'max:65535'],
            'receiver' => ['nullable', 'string', 'max:255'],
            'date_time_received' => ['nullable', 'date'],
            'time_of_response' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:65535'],
            'landmark' => ['nullable', 'string', 'max:65535'],
            'details_of_incident' => ['nullable', 'string', 'max:65535'],
            'vehicles_involved' => ['nullable', 'string', 'max:65535'],
            'is_alert_response' => ['sometimes', 'boolean'],
            'is_verbal_response' => ['sometimes', 'boolean'],
            'is_pain_response' => ['sometimes', 'boolean'],
            'is_unconscious' => ['sometimes', 'boolean'],
            'has_deformity' => ['sometimes', 'boolean'],
            'has_contusion' => ['sometimes', 'boolean'],
            'has_abrasion' => ['sometimes', 'boolean'],
            'has_puncture_penetration' => ['sometimes', 'boolean'],
            'has_tenderness' => ['sometimes', 'boolean'],
            'has_laceration' => ['sometimes', 'boolean'],
            'has_swelling' => ['sometimes', 'boolean'],
            'examination_notes' => ['nullable', 'string', 'max:65535'],
            // Base64 PNG (optionally a data URI) of the body diagram with injury markers.
            'injury_diagram' => ['nullable', 'string', 'max:8000000'],
            // Sending an empty array clears the markers and the stored diagram.
            'injury_markers' => ['sometimes', 'nullable', 'array', 'max:50'],
            'injury_markers.*.x' => ['required', 'numeric', 'between:0,1'],
            'injury_markers.*.y' => ['required', 'numeric', 'between:0,1'],
            'injury_markers.*.label' => ['nullable', 'string', 'max:255'],
            'injury_markers.*.nature' => ['nullable', 'integer', Rule::in($this->optionKeys('injury_natures'))],
            'action_taken' => ['nullable', 'string', 'max:65535'],
            'refer_to_hospital' => ['nullable', 'string', Rule::in(['yes', 'no'])],
            'time_transported' => ['nullable', 'string', 'max:255'],
            'name_of_hospital' => ['nullable', 'string', 'max:255'],
            'name_of_responders' => ['nullable', 'string', 'max:65535'],
            'name_of_response_vehicle' => ['nullable', 'string', 'max:255'],

            // Run sheet (form version 2). Apps that omit form_version stay on version 1.
            'form_version' => ['sometimes', 'integer', Rule::in([1, 2])],
            'incident_no' => ['nullable', 'string', 'max:100'],
            'incident_date' => ['nullable', 'date'],
            'response_team' => ['nullable', 'string', Rule::in($this->optionKeys('teams'))],
            'time_informed' => ['nullable', 'string', 'max:20'],
            'time_arrival_at_scene' => ['nullable', 'string', 'max:20'],
            'time_departure_from_scene' => ['nullable', 'string', 'max:20'],
            'time_arrival_at_hospital' => ['nullable', 'string', 'max:20'],
            'time_back_in_service' => ['nullable', 'string', 'max:20'],
            'incident_types' => ['nullable', 'array'],
            'incident_types.*' => ['string', Rule::in($this->optionKeys('incident_types'))],
            'incident_type_others' => ['nullable', 'string', 'max:255'],
            'airway' => ['nullable', 'string', Rule::in($this->optionKeys('airway'))],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'skin' => ['nullable', 'array'],
            'skin.*' => ['string', Rule::in($this->optionKeys('skin'))],
            'skin_others' => ['nullable', 'string', 'max:255'],
            'address_of_incident' => ['nullable', 'string', 'max:2000'],
            'victim_name' => ['nullable', 'string', 'max:255'],
            'victim_age' => ['nullable', 'string', 'max:20'],
            'victim_sex' => ['nullable', 'string', Rule::in($this->optionKeys('sex'))],
            'victim_status' => ['nullable', 'string', 'max:50'],
            'victim_address' => ['nullable', 'string', 'max:2000'],
            'consciousness' => ['nullable', 'string', Rule::in($this->optionKeys('consciousness'))],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'pupils' => ['nullable', 'string', Rule::in($this->optionKeys('pupils'))],
            'pupil_left' => ['sometimes', 'boolean'],
            'pupil_right' => ['sometimes', 'boolean'],
            'transport_vehicle' => ['nullable', 'string', Rule::in($this->optionKeys('transport_vehicles'))],
            'patient_disposition' => ['nullable', 'string', Rule::in($this->optionKeys('dispositions'))],
            'is_work_related' => ['sometimes', 'boolean'],
            'suspected_alcohol_use' => ['sometimes', 'boolean'],
            'suspected_drug_use' => ['sometimes', 'boolean'],
            'patient_refused_care' => ['sometimes', 'boolean'],
            'patient_refused_transport' => ['sometimes', 'boolean'],
            'injury_natures' => ['nullable', 'array'],
            'injury_natures.*' => ['integer', Rule::in($this->optionKeys('injury_natures'))],
            'injury_nature_others' => ['nullable', 'string', 'max:255'],
            'actions_taken' => ['nullable', 'array'],
            'actions_taken.*' => ['string', Rule::in($this->optionKeys('actions_taken'))],
            'actions_taken_others' => ['nullable', 'string', 'max:255'],
            'sample_signs_symptoms' => ['nullable', 'string', 'max:1000'],
            'sample_allergies' => ['nullable', 'string', 'max:1000'],
            'sample_medications' => ['nullable', 'string', 'max:1000'],
            'sample_past_history' => ['nullable', 'string', 'max:1000'],
            'sample_last_intake' => ['nullable', 'string', 'max:1000'],
            'sample_events' => ['nullable', 'string', 'max:1000'],
            'equipment_used' => ['nullable', 'array'],
            'equipment_used.*' => ['string', Rule::in($this->optionKeys('equipment'))],
            'equipment_others' => ['nullable', 'string', 'max:255'],
            // Names, not ids: a saved sheet must keep who responded after the roster changes.
            'responders' => ['nullable', 'array', 'max:40'],
            'responders.*' => ['string', 'max:120'],
            'patient_belongings' => ['nullable', 'string', 'max:5000'],
            'team_leader_name' => ['nullable', 'string', 'max:255'],
            'recorder_name' => ['nullable', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<int|string>
     */
    private function optionKeys(string $option): array
    {
        return array_keys(config("run_sheet.{$option}"));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
