<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoringApiRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->headers->has('Idempotency-Key')) {
            $header = $this->header('Idempotency-Key');
            if ($this->has('idempotency_key') && $this->input('idempotency_key') !== $header) {
                throw \Illuminate\Validation\ValidationException::withMessages(['idempotency_key' => 'Header dan body idempotency key harus sama.']);
            }
            $this->merge(['idempotency_key' => $header]);
        }
        // Use raw JSON to distinguish an empty string from explicit null after Laravel's
        // ConvertEmptyStringsToNull middleware. Zero is normalized without truthy checks.
        $raw = json_decode($this->getContent(), true);
        $values = is_array($raw) ? $raw : $this->all();
        if (array_key_exists('fuzzy_score', $values)) {
            $this->merge(['fuzzy_score' => \App\Services\FuzzyLogic\ExternalFuzzyScore::normalize($values['fuzzy_score'])]);
        }
        if ($this->has('event_id') && $this->has('idempotency_key') && $this->input('event_id') !== $this->input('idempotency_key')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['idempotency_key' => 'event_id dan Idempotency-Key harus merujuk identitas yang sama.']);
        }
    }
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
            'plant_id'             => ['required', 'integer', 'exists:plants,id'],
            'monitored_at'         => ['required', 'date'],
            'temperature'          => ['required', 'numeric', 'between:0,60'],
            'nutrient_temperature' => ['nullable', 'numeric', 'between:0,60'],
            'ph'                   => ['required', 'numeric', 'between:0,14'],
            'ec'                   => ['nullable', 'numeric', 'between:0,20'],
            'humidity'             => ['required', 'numeric', 'between:0,100'],
            'soil_moisture'        => ['nullable', 'numeric', 'between:0,100'],
            'light_intensity'      => ['nullable', 'numeric', 'between:0,200000'],
            'tank_level'           => ['nullable', 'numeric', 'between:0,100'],
            'plant_height'         => ['required', 'numeric', 'min:0', 'max:500'],
            'canopy_density'       => ['nullable', 'numeric', 'between:0,100'],
            'plant_density'        => ['required_without:canopy_density', 'numeric', 'between:0,100'],
            'condition'            => ['nullable', 'string', 'max:100', Rule::in($allowedConditions)],
            'plant_condition'      => ['required_without:condition', 'string', 'max:100', Rule::in($allowedConditions)],
            'leaf_color'           => ['nullable', 'string', 'max:100'],
            'plant_color'          => ['nullable', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'device_id'            => ['nullable', 'string', 'max:100'],
            'event_id'             => ['sometimes', 'required', 'string', 'max:128', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/'],
            'idempotency_key'      => ['sometimes', 'required', 'string', 'max:128', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/'],
            'fuzzy_score'          => ['nullable', 'numeric', 'between:0,100'],
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
            'plant_id'             => 'ID Tanaman',
            'monitored_at'         => 'Waktu Monitoring',
            'temperature'          => 'Suhu Lingkungan',
            'nutrient_temperature' => 'Suhu Larutan Nutrisi',
            'ph'                   => 'Derajat Keasaman pH',
            'ec'                   => 'Konduktivitas Listrik (EC)',
            'humidity'             => 'Kelembapan Udara',
            'soil_moisture'        => 'Kelembapan Media Tanam',
            'light_intensity'      => 'Intensitas Cahaya',
            'tank_level'           => 'Level Tandon Nutrisi',
            'plant_height'         => 'Tinggi Tanaman',
            'canopy_density'       => 'Kerapatan Kanopi',
            'plant_density'        => 'Kerapatan Tanaman',
            'condition'            => 'Kondisi Tanaman',
            'plant_condition'      => 'Kondisi Tanaman',
            'leaf_color'           => 'Warna Daun',
            'plant_color'          => 'Warna Daun',
            'notes'                => 'Catatan Sensor',
        ];
    }
}
