<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiveLocationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_id' => 'nullable|integer',
            'mode' => 'required|in:navigating,watching,active_report,on_duty',
            'samples' => 'present|array|max:50',
            'samples.*.lat' => 'required|numeric|between:-90,90',
            'samples.*.lng' => 'required|numeric|between:-180,180',
            'samples.*.recorded_at' => 'required|date',
            'samples.*.accuracy' => 'nullable|numeric',
            'samples.*.speed' => 'nullable|numeric',
            'samples.*.heading' => 'nullable|numeric',
        ];
    }
}
