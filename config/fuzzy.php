<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status & Metrik Mesin Inferensi Fuzzy Logic
    |--------------------------------------------------------------------------
    |
    | CATATAN PENTING ARSITEKTUR:
    | Konfigurasi ini berstatus 'PROVISIONAL_FOR_SOFTWARE_TESTING'. Nilai batas
    | fungsi keanggotaan dan rule base belum disahkan secara formal oleh Tim
    | Teknik Elektro maupun pakar agronomi melon. Preset ini murni untuk
    | verifikasi fungsional perangkat lunak dan tidak boleh dianggap diagnosis agronomis resmi.
    |
    */
    'enabled' => env('FUZZY_INFERENCE_ENABLED', true),

    'rule_version' => 'v2.0-three-input-PROVISIONAL',

    'validation_status' => 'PROVISIONAL_FOR_SOFTWARE_TESTING',

    'method' => 'Mamdani',

    'operators' => [
        'and' => 'min',
        'or' => 'max',
        'implication' => 'min',
        'aggregation' => 'max',
        'defuzzification' => 'centroid',
    ],

    /*
    | Ambang batas usia data untuk penetapan status STALE (dalam jam).
    */
    'stale_threshold_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Universe of Discourse & Variabel Input
    |--------------------------------------------------------------------------
    */
    'inputs' => [
        'temperature' => [
            'name' => 'Suhu Udara',
            'unit' => '°C',
            'min'  => 10.0,
            'max'  => 50.0,
            'sets' => [
                'dingin' => [
                    'type'   => 'trapezoidal',
                    'params' => [10.0, 10.0, 20.0, 24.0],
                ],
                'optimal' => [
                    'type'   => 'triangular',
                    'params' => [22.0, 27.0, 32.0],
                ],
                'panas' => [
                    'type'   => 'trapezoidal',
                    'params' => [29.0, 34.0, 50.0, 50.0],
                ],
            ],
        ],


        'soil_moisture' => [
            'name' => 'Kelembapan Tanah',
            'unit' => '%',
            'min'  => 0.0,
            'max'  => 100.0,
            'sets' => [
                'kering' => [
                    'type'   => 'trapezoidal',
                    'params' => [0.0, 0.0, 50.0, 65.0],
                ],
                'ideal' => [
                    'type'   => 'triangular',
                    'params' => [58.0, 70.0, 82.0],
                ],
                'jenuh' => [
                    'type'   => 'trapezoidal',
                    'params' => [75.0, 85.0, 100.0, 100.0],
                ],
            ],
        ],

        'water_ph' => [
            'name' => 'pH air',
            'unit' => '',
            'min'  => 0.0,
            'max'  => 14.0,
            'sets' => [
                'asam' => [
                    'type'   => 'trapezoidal',
                    'params' => [0.0, 0.0, 5.5, 6.0],
                ],
                'optimal' => [
                    'type'   => 'triangular',
                    'params' => [5.8, 6.5, 7.2],
                ],
                'basa' => [
                    'type'   => 'trapezoidal',
                    'params' => [6.8, 7.5, 14.0, 14.0],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Variabel Output (Greenhouse Condition Score 0 - 100)
    |--------------------------------------------------------------------------
    */
    'output' => [
        'name' => 'Skor Kondisi Greenhouse',
        'unit' => 'Index (0-100)',
        'min'  => 0.0,
        'max'  => 100.0,
        'step' => 1.0, // Discrete step resolution for Centroid Defuzzification
        'sets' => [
            'buruk' => [
                'type'   => 'trapezoidal',
                'params' => [0.0, 0.0, 25.0, 45.0],
            ],
            'perlu_perhatian' => [
                'type'   => 'triangular',
                'params' => [35.0, 55.0, 75.0],
            ],
            'baik' => [
                'type'   => 'trapezoidal',
                'params' => [65.0, 80.0, 100.0, 100.0],
            ],
        ],
    ],

    /*
    | Klasifikasi Kategori Skor Akhir
    */
    'categories' => [
        'BAIK' => [
            'min_score' => 65.0,
            'max_score' => 100.0,
            'color'     => 'emerald',
            'badge'     => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'label'     => 'Kondisi Baik & Optimal',
        ],
        'PERLU_PERHATIAN' => [
            'min_score' => 40.0,
            'max_score' => 64.99,
            'color'     => 'amber',
            'badge'     => 'bg-amber-100 text-amber-800 border-amber-300',
            'label'     => 'Perlu Perhatian / Moderat',
        ],
        'BURUK' => [
            'min_score' => 0.0,
            'max_score' => 39.99,
            'color'     => 'rose',
            'badge'     => 'bg-rose-100 text-rose-800 border-rose-300',
            'label'     => 'Kondisi Kritis / Buruk',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Base (IF-THEN Rules) - Versi 2.0 PROVISIONAL
    |--------------------------------------------------------------------------
    | Catatan: Menggunakan 8 aturan terkurasi untuk skenario pengujian software.
    | Cakupan 27 kombinasi belum lengkap; lihat FUZZY_THREE_INPUT_REDESIGN.md.
    */
    'rules' => [
        [
            'id' => 'T3-R01',
            'name' => 'Kondisi Sempurna Seimbang',
            'antecedents' => [
                'temperature'   => 'optimal',
                'soil_moisture' => 'ideal',
                'water_ph'            => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'baik',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R02',
            'name' => 'Stres Panas dan Kekeringan Ekstrem',
            'antecedents' => [
                'temperature'   => 'panas',
                'soil_moisture' => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R05',
            'name' => 'pH air asam dan kelembapan tanah rendah',
            'antecedents' => [
                'water_ph'            => 'asam',
                'soil_moisture' => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R06',
            'name' => 'pH air basa dan tanah jenuh',
            'antecedents' => [
                'water_ph'            => 'basa',
                'soil_moisture' => 'jenuh',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R10',
            'name' => 'Suhu Panas Moderat dengan Tanah Ideal',
            'antecedents' => [
                'temperature'   => 'panas',
                'soil_moisture' => 'ideal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R11',
            'name' => 'Suhu Dingin Moderat dengan Tanah Ideal',
            'antecedents' => [
                'temperature'   => 'dingin',
                'soil_moisture' => 'ideal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R12',
            'name' => 'Penyimpangan pH air asam',
            'antecedents' => [
                'water_ph'          => 'asam',
                'temperature' => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
        [
            'id' => 'T3-R13',
            'name' => 'Penyimpangan pH air basa',
            'antecedents' => [
                'water_ph'          => 'basa',
                'temperature' => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion' => 'PROVISIONAL: aktivasi aturan perangkat lunak; memerlukan validasi Tim Elektro.',
            'recommendation' => 'Verifikasi sensor dan konsultasikan tindakan kepada Tim Elektro. Pompa hanya tersedia sebagai simulasi.',
        ],
    ],
];
