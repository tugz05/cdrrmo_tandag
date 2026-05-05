<?php

namespace App\Http\Requests\API\V1\Auth;

use App\Traits\JResponseApiTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\JsonResponse;

class GoogleIdTokenRequest extends FormRequest
{
    use JResponseApiTrait;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id_token' => 'required|string|min:10',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->responseError('Validation errors', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
