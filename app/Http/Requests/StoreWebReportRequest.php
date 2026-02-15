<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebReportRequest extends FormRequest
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
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:10',
            'phone' => ['required', 'string', 'regex:/^(?:\\+?62|0)8[0-9]{7,11}$/'],
            'location_name' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'evidence' => 'nullable|image|max:5120',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul laporan wajib diisi.',
            'title.min' => 'Judul laporan minimal :min karakter.',
            'title.max' => 'Judul laporan maksimal :max karakter.',
            'description.required' => 'Detail laporan wajib diisi.',
            'description.min' => 'Detail laporan minimal :min karakter.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.regex' => 'Format nomor HP tidak valid.',
            'location_name.required' => 'Lokasi kejadian wajib diisi.',
            'location_name.max' => 'Lokasi kejadian maksimal :max karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'evidence.image' => 'Bukti foto harus berupa gambar.',
            'evidence.max' => 'Bukti foto maksimal 5MB.',
        ];
    }
}
