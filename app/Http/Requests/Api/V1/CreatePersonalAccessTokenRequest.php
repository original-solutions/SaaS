<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreatePersonalAccessTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
}
