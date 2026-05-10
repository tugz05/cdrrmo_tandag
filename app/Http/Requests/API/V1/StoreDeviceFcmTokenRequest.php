<?php

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'min:1', 'max:4096'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ];
    }
}
