<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualReportStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'string|nullable',
            'details' => 'required|string|min:2|max:1000',
            'status' => 'required|string',
            'created_at' => 'required|string',
            'address' => 'required|string|min:2|max:255',
            'reported_by' => 'string|nullable|min:2|max:255',
            'reporters_address' => 'string|nullable|min:2|max:255',
            'is_manually_added' => 'boolean'
        ];
    }
}
