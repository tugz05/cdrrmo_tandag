<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipStoreRequest extends FormRequest
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
            'id' => 'nullable',
            'user_id' => 'nullable',
            'title' => 'nullable|string',
            'content' => 'string|required',
            'disabled_at' => 'nullable|date',
            'type' => 'string|required'  
        ];
    }
}
