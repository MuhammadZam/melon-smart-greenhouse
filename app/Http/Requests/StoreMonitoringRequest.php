<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonitoringRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan untuk membuat request ini.
     * Admin dan Pengamat memiliki izin untuk mencatat hasil monitoring.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plant_id'        => ['required', 'integer', 'exists:plants,id'],
            'monitored_at'    => ['required', 'date'],
            'temperature'     => ['required', 'numeric', 'between:0,60'],
            'ph'              => ['required', 'numeric', 'between:0,14'],
            'humidity'        => ['required', 'numeric', 'between:0,100'],
            'plant_height'    => ['nullable', 'numeric', 'min:0', 'max:500'],
            'plant_density'   => ['nullable', 'numeric', 'between:0,100'],
            'plant_condition' => ['required', 'string', 'max:100'],
            'plant_color'     => ['required', 'string', 'max:100'],
            'notes'           => ['nullable', 'string', 'max:2000'],
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
            'plant_id'        => 'Tanaman',
            'monitored_at'    => 'Waktu Monitoring',
            'temperature'     => 'Suhu Lingkungan (°C)',
            'ph'              => 'pH Nutrisi/Media',
            'humidity'        => 'Kelembapan Udara (%)',
            'plant_height'    => 'Tinggi Tanaman (cm)',
            'plant_density'   => 'Kerapatan Kanopi (%)',
            'plant_condition' => 'Kondisi Tanaman',
            'plant_color'     => 'Warna Daun/Tanaman',
            'notes'           => 'Catatan Observasi',
        ];
    }

    /**
     * Pesan kesalahan khusus untuk validasi input monitoring.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plant_id.required'        => 'Silakan pilih tanaman yang dimonitor.',
            'plant_id.exists'          => 'Tanaman yang dipilih tidak terdaftar di sistem.',
            'monitored_at.required'    => 'Waktu monitoring wajib diisi.',
            'monitored_at.date'        => 'Format waktu monitoring tidak valid.',
            'temperature.required'     => 'Nilai suhu wajib diisi.',
            'temperature.numeric'      => 'Nilai suhu harus berupa angka numerik.',
            'temperature.between'      => 'Nilai suhu harus berada di antara :min°C dan :max°C.',
            'ph.required'              => 'Nilai pH wajib diisi.',
            'ph.numeric'               => 'Nilai pH harus berupa angka numerik.',
            'ph.between'               => 'Nilai pH harus berada di antara :min dan :max.',
            'humidity.required'        => 'Nilai kelembapan wajib diisi.',
            'humidity.numeric'         => 'Nilai kelembapan harus berupa angka numerik.',
            'humidity.between'         => 'Nilai kelembapan harus berada di antara :min% dan :max%.',
            'plant_height.numeric'     => 'Tinggi tanaman harus berupa angka numerik.',
            'plant_height.min'         => 'Tinggi tanaman tidak boleh bernilai negatif.',
            'plant_density.numeric'    => 'Kerapatan kanopi harus berupa angka numerik.',
            'plant_density.between'    => 'Kerapatan kanopi harus di antara :min% dan :max%.',
            'plant_condition.required' => 'Kondisi fisik tanaman wajib dipilih atau diisi.',
            'plant_color.required'     => 'Warna daun/tanaman wajib dipilih atau diisi.',
        ];
    }
}
