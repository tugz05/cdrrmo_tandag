<?php

namespace App\Http\Requests\API\V1\Auth;

use App\Traits\JResponseApiTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class NewPasswordPutRequest extends FormRequest
{
    use JResponseApiTrait;
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
            'id' => 'required',
            'new_password' => 'required',
            'confirm_new_password' => 'required|same:new_password'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors())
        );
    }
}
