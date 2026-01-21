<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'ticket' => 'required|string|exists:reports,ticket_number',
            'description' => 'nullable|string',
            'location_name' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
            'evidence' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|required_without_all:description,location_name,latitude,longitude,evidence',
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
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'latitude.required_with' => 'Latitude wajib diisi jika longitude diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'longitude.required_with' => 'Longitude wajib diisi jika latitude diisi.',
            'evidence.image' => 'Bukti foto harus berupa gambar.',
            'evidence.max' => 'Bukti foto maksimal 5MB.',
            'notes.required_without_all' => 'Isi catatan atau lengkapi data laporan yang diminta.',
        ];
    }
}
