<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTrackingPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'ticket' => 'required|string|exists:reports,ticket_number',
            'phone' => ['required', 'string', 'regex:/^(?:\+?62|0)8[0-9]{7,11}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticket.required' => 'Nomor tiket wajib diisi.',
            'ticket.exists' => 'Nomor tiket tidak ditemukan.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.regex' => 'Format nomor HP tidak valid.',
        ];
    }
}
