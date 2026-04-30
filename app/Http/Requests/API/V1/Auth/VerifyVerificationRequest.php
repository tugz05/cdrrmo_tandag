<?php

namespace App\Http\Requests\API\V1\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyVerificationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required_without:phone|string|email',
            'phone' => 'required_without:email|string',
            'code' => 'required|string',
        ];
    }

    protected function prepareForValidation()
    {
        $userIdentifier = $this->input('user_id');

        if (filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) 
            $this->merge(['email' => $userIdentifier]);
        else 
            $this->merge(['phone' => $userIdentifier]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors())
        );
    }
}
