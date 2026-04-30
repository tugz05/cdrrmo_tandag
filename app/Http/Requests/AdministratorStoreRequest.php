<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdministratorStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
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
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|in:admin,super_admin',
        ];
    }
}
