<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'btc_wallet_address' => ['nullable', 'string', 'max:255'],
            'usdt_wallet_address' => ['nullable', 'string', 'max:255'],
            'bank_transfer_details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
