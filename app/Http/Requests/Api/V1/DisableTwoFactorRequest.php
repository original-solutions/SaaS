<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class DisableTwoFactorRequest extends FormRequest
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
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Additional validation for password confirmation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! Hash::check($this->input('password'), $this->user()->password)) {
                $validator->errors()->add('password', 'The password is incorrect.');
            }
        });
    }
}
