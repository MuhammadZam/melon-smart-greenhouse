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

    'rule_version' => env('FUZZY_RULE_VERSION', 'v1.0-provisional'),

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

        'humidity' => [
            'name' => 'Kelembapan Udara',
            'unit' => '%',
            'min'  => 0.0,
            'max'  => 100.0,
            'sets' => [
                'kering' => [
                    'type'   => 'trapezoidal',
                    'params' => [0.0, 0.0, 50.0, 65.0],
                ],
                'optimal' => [
                    'type'   => 'triangular',
                    'params' => [58.0, 70.0, 82.0],
                ],
                'lembap' => [
                    'type'   => 'trapezoidal',
                    'params' => [75.0, 85.0, 100.0, 100.0],
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

        'ph' => [
            'name' => 'pH Nutrisi/Media',
            'unit' => 'pH',
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
    | Rule Base (IF-THEN Rules) - Versi 1.0 Provisional
    |--------------------------------------------------------------------------
    | Catatan: Menggunakan 16 aturan terkurasi untuk skenario pengujian software.
    | Tidak mengarang 81 kombinasi tanpa validasi elektro.
    */
    'rules' => [
        [
            'id' => 'R01',
            'name' => 'Kondisi Sempurna Seimbang',
            'antecedents' => [
                'temperature'   => 'optimal',
                'humidity'      => 'optimal',
                'soil_moisture' => 'ideal',
                'ph'            => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'baik',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Seluruh parameter iklim mikro dan media tumbuh melon berada dalam rentang ideal.',
            'recommendation' => 'Pertahankan rutinitas sirkulasi dan irigasi nutrisi saat ini.',
        ],
        [
            'id' => 'R02',
            'name' => 'Stres Panas dan Kekeringan Ekstrem',
            'antecedents' => [
                'temperature'   => 'panas',
                'soil_moisture' => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Suhu lingkungan tinggi disertai kelembapan media yang kering memicu stres air akut pada melon.',
            'recommendation' => 'Segera aktifkan sirkulasi udara (exhaust fan) dan pertimbangkan irigasi tambahan.',
        ],
        [
            'id' => 'R03',
            'name' => 'Panas dan Sangat Lembap (Potensi Jamur)',
            'antecedents' => [
                'temperature' => 'panas',
                'humidity'    => 'lembap',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Kombinasi suhu panas dan udara lembap menghambat transpirasi tanaman melon serta memicu risiko jamur/embun tepung.',
            'recommendation' => 'Buka ventilasi samping greenhouse atau aktifkan kipas ventilasi untuk menurunkan kelembapan relatif.',
        ],
        [
            'id' => 'R04',
            'name' => 'Dingin dan Sangat Lembap',
            'antecedents' => [
                'temperature' => 'dingin',
                'humidity'    => 'lembap',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Suhu dingin dan udara lembap memperlambat metabolisme serta penyerapan nutrisi tanaman.',
            'recommendation' => 'Kurangi penyiraman dan periksa sirkulasi udara greenhouse.',
        ],
        [
            'id' => 'R05',
            'name' => 'Media Asam dan Kekeringan Tanah',
            'antecedents' => [
                'ph'            => 'asam',
                'soil_moisture' => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Kondisi media asam bersamaan dengan tanah kering berisiko merusak perakaran dan mengunci unsur hara makro.',
            'recommendation' => 'Lakukan pengenceran larutan nutrisi dengan air ber-pH netral dan naikkan kelembapan media.',
        ],
        [
            'id' => 'R06',
            'name' => 'Media Basa dan Media Jenuh Air',
            'antecedents' => [
                'ph'            => 'basa',
                'soil_moisture' => 'jenuh',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Media terlalu jenuh air dan basa memicu asfiksia akar (kurang oksigen) serta defisiensi zat besi/mikro.',
            'recommendation' => 'Hentikan penyiraman sementara hingga drainase membaik, lalu koreksi pH nutrisi ke rentang 5.8 - 6.8.',
        ],
        [
            'id' => 'R07',
            'name' => 'Iklim Mikro Baik tapi Tanah Kering',
            'antecedents' => [
                'temperature'   => 'optimal',
                'humidity'      => 'optimal',
                'soil_moisture' => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Suhu dan kelembapan udara optimal, namun kelembapan tanah rendah sehingga tanaman berisiko layu ringan.',
            'recommendation' => 'Lakukan irigasi terjadwal untuk mengembalikan kelembapan tanah ke level ideal.',
        ],
        [
            'id' => 'R08',
            'name' => 'Iklim Mikro Baik tapi Tanah Jenuh',
            'antecedents' => [
                'temperature'   => 'optimal',
                'humidity'      => 'optimal',
                'soil_moisture' => 'jenuh',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Suhu dan kelembapan udara stabil, namun media tumbuh terlalu basah.',
            'recommendation' => 'Tunda siklus irigasi berikutnya dan cek lubang drainase polybag/bedengan.',
        ],
        [
            'id' => 'R09',
            'name' => 'Udara Terlalu Kering pada Suhu Normal',
            'antecedents' => [
                'temperature' => 'optimal',
                'humidity'    => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Kelembapan udara rendah meningkatkan defisit tekanan uap air (VPD) dan mempercepat penguapan.',
            'recommendation' => 'Cek kabut misting atau basahi lantai greenhouse untuk menaikkan kelembapan relatif.',
        ],
        [
            'id' => 'R10',
            'name' => 'Suhu Panas Moderat dengan Tanah Ideal',
            'antecedents' => [
                'temperature'   => 'panas',
                'soil_moisture' => 'ideal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Suhu udara greenhouse menghangat di atas ambang optimal melon, meskipun ketersediaan air tanah masih mencukupi.',
            'recommendation' => 'Pantau suhu kanopi dan persiapkan naungan/shading net jika radiasi matahari terus meningkat.',
        ],
        [
            'id' => 'R11',
            'name' => 'Suhu Dingin Moderat dengan Tanah Ideal',
            'antecedents' => [
                'temperature'   => 'dingin',
                'soil_moisture' => 'ideal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Suhu udara di bawah standar tumbuh aktif tanaman melon.',
            'recommendation' => 'Tutup tirai samping greenhouse untuk menahan panas di dalam ruangan.',
        ],
        [
            'id' => 'R12',
            'name' => 'Penyimpangan pH Asam Moderat',
            'antecedents' => [
                'ph'          => 'asam',
                'temperature' => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Nilai pH larutan nutrisi berada di bawah batas nyaman penyerapan fosfat dan kalsium melon.',
            'recommendation' => 'Lakukan kalibrasi probe pH sensor dan tambahkan larutan basa (pH Up) bertahap.',
        ],
        [
            'id' => 'R13',
            'name' => 'Penyimpangan pH Basa Moderat',
            'antecedents' => [
                'ph'          => 'basa',
                'temperature' => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'perlu_perhatian',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Nilai pH larutan nutrisi terlalu basa, berpotensi menyebabkan pengendapan mikro nutrien.',
            'recommendation' => 'Tambahkan larutan pengatur asam (pH Down) pada tandon nutrisi hingga mencapai 6.0 - 6.5.',
        ],
        [
            'id' => 'R14',
            'name' => 'Kombinasi Kering Komprehensif (Panas + Udara Kering + Tanah Kering)',
            'antecedents' => [
                'temperature'   => 'panas',
                'humidity'      => 'kering',
                'soil_moisture' => 'kering',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Kondisi lingkungan kering menyeluruh berisiko tinggi mematikan pucuk tanaman dan menggugurkan bakal buah melon.',
            'recommendation' => 'Tindakan darurat: lakukan pengabutan (misting), siram tanah, dan aktifkan peneduh kanopi.',
        ],
        [
            'id' => 'R15',
            'name' => 'Kombinasi Basah Dingin Komprehensif (Dingin + Lembap + Tanah Jenuh)',
            'antecedents' => [
                'temperature'   => 'dingin',
                'humidity'      => 'lembap',
                'soil_moisture' => 'jenuh',
            ],
            'operator'    => 'and',
            'consequent'  => 'buruk',
            'weight'      => 1.0,
            'enabled'     => true,
            'conclusion'  => 'Lingkungan dingin berkelembapan jenuh memicu pembusukan leher akar (damping off) dan layu bakteri.',
            'recommendation' => 'Keringkan media, perbaiki sirkulasi udara greenhouse secara maksimal.',
        ],
        [
            'id' => 'R16',
            'name' => 'Kondisi Optimal Suhu dan Kelembapan Udara',
            'antecedents' => [
                'temperature' => 'optimal',
                'humidity'    => 'optimal',
            ],
            'operator'    => 'and',
            'consequent'  => 'baik',
            'weight'      => 0.9,
            'enabled'     => true,
            'conclusion'  => 'Kondisi mikroklimat udara greenhouse sangat nyaman untuk fotosintesis tanaman melon.',
            'recommendation' => 'Pertahankan set-point lingkungan dan lanjutkan pemantauan berkala.',
        ],
    ],
];
