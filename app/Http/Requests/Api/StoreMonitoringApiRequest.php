<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoringApiRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan untuk membuat request ini.
     * Untuk prototipe sensor ingestion API, otorisasi diizinkan bagi klien sensor terkonfigurasi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi untuk penerimaan data sensor monitoring.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedConditions = [
            'optimal',
            'warning',
            'critical',
            'good',
            'Subur & Baik',
            'Perlu Perhatian',
            'Baik',
            'Cukup',
            'Kurang Baik',
            'Kritis',
            'Optimal',
            'Warning',
            'Critical',
            'Normal',
        ];

        return [
            'plant_id'        => ['required', 'integer', 'exists:plants,id'],
            'monitored_at'    => ['required', 'date'],
            'temperature'     => ['required', 'numeric', 'between:0,60'],
            'ph'              => ['required', 'numeric', 'between:0,14'],
            'humidity'        => ['required', 'numeric', 'between:0,100'],
            'plant_height'    => ['required', 'numeric', 'min:0', 'max:500'],
            'canopy_density'  => ['nullable', 'numeric', 'between:0,100'],
            'plant_density'   => ['required_without:canopy_density', 'numeric', 'between:0,100'],
            'condition'       => ['nullable', 'string', 'max:100', Rule::in($allowedConditions)],
            'plant_condition' => ['required_without:condition', 'string', 'max:100', Rule::in($allowedConditions)],
            'leaf_color'      => ['nullable', 'string', 'max:100'],
            'plant_color'     => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Nama atribut yang mudah dibaca dalam respons validasi.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plant_id'        => 'ID Tanaman',
            'monitored_at'    => 'Waktu Monitoring',
            'temperature'     => 'Suhu Lingkungan',
            'ph'              => 'Derajat Keasaman pH',
            'humidity'        => 'Kelembapan Udara',
            'plant_height'    => 'Tinggi Tanaman',
            'canopy_density'  => 'Kerapatan Kanopi',
            'plant_density'   => 'Kerapatan Tanaman',
            'condition'       => 'Kondisi Tanaman',
            'plant_condition' => 'Kondisi Tanaman',
            'leaf_color'      => 'Warna Daun',
            'plant_color'     => 'Warna Daun',
            'notes'           => 'Catatan Sensor',
        ];
    }
}
