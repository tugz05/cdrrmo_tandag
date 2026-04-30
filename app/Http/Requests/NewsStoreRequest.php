<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsStoreRequest extends FormRequest
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
            'is_published' => 'boolean',
            'title' => 'required|string|min:2|max:200',
            'type' => 'required|string',
            'content' => 'nullable|string',
            'bg_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
