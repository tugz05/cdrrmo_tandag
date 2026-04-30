<?php

namespace App\Http\Requests\API\V1\Auth;

use App\Traits\JResponseApiTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class RegisterPostRequest extends FormRequest
{
    Use JResponseApiTrait;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'fname'             => 'required|max:200|min:2',
            'lname'             => 'required|max:200|min:2',
            'mname'             => 'nullable|max:200|min:1',
            'suffix'            => 'nullable|max:2|min:1',
            'email'             => 'required|email|max:255',
            'address'             => 'required|string|min:2|max:255',
            'phone'             => 'nullable|max:11|min:11',
            'password'          => 'required|string|min:6|max:255',
            'confirm_password'  => 'required|same:password',
            // 'img_valid_id'      => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            // 'img_selfie'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ];
    }

    public function attributes(): array
    {
        return [
            'fname' => 'first name',
            'lname' => 'last name',
            'mname' => 'middle name',
            'phone' => 'phone number',
        ];
    }
    
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors(), 208)
        );
    }
}
