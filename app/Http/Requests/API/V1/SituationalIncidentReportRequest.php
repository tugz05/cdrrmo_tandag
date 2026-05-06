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
            'action_taken' => ['nullable', 'string', 'max:65535'],
            'refer_to_hospital' => ['nullable', 'string', Rule::in(['yes', 'no'])],
            'time_transported' => ['nullable', 'string', 'max:255'],
            'name_of_hospital' => ['nullable', 'string', 'max:255'],
            'name_of_responders' => ['nullable', 'string', 'max:65535'],
            'name_of_response_vehicle' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
