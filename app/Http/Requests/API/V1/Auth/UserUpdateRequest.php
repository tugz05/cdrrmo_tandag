<?php

namespace App\Http\Requests\API\V1\Auth;

use App\Traits\JResponseApiTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
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
            'fname'         => 'required|max:255',
            'mname'         => 'nullable|max:255',
            'lname'         => 'required|max:255',
            'suffix'        => 'nullable|max:255',
            'email'         => 'required|email',
            'campus_id'     => 'required',
            'course_id'     => 'required',
            'gender'        => 'required',
            'phone'         => 'required',
            'graduated_at'  => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'fname' => 'first name',
            'lname' => 'last name',
            'mname' => 'middle name',
            'phone' => 'phone number'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors())
        );
    }
}
