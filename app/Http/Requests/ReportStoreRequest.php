<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportStoreRequest extends FormRequest
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
            'id' => 'nullable|integer',
            'user_id' => 'prohibited',
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'nullable|string',
            'details' => 'nullable|string',
            'type' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,png,jpeg|max:10240',
        ];
    }
}
