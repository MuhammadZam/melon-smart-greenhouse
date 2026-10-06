<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan untuk memperbarui data tanaman.
     * HANYA Administrator yang memiliki wewenang mengedit data tanaman.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'location'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['required', 'string', 'in:active,harvested,inactive'],
        ];
    }

    /**
     * Nama atribut yang mudah dibaca dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'        => 'Nama Tanaman / Plot',
            'location'    => 'Lokasi Greenhouse',
            'description' => 'Deskripsi Plot',
            'status'      => 'Status Tanaman',
        ];
    }

    /**
     * Pesan kesalahan khusus untuk validasi data tanaman melon.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Nama tanaman wajib diisi.',
            'name.string'       => 'Nama tanaman harus berupa teks string.',
            'name.max'          => 'Nama tanaman maksimal 255 karakter.',
            'location.required' => 'Lokasi tanaman wajib diisi.',
            'location.string'   => 'Lokasi tanaman harus berupa teks string.',
            'location.max'      => 'Lokasi tanaman maksimal 255 karakter.',
            'description.max'   => 'Deskripsi plot maksimal 1000 karakter.',
            'status.required'   => 'Status tanaman wajib dipilih.',
            'status.in'         => 'Status tanaman yang dipilih tidak valid.',
        ];
    }
}
