<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Monitoring Greenhouse Melon</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.2cm 1.2cm 1.5cm 1.2cm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #1a202c;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header Akademik */
        .header {
            border-bottom: 2pt solid #2d3748;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1a202c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .header-subtitle {
            font-size: 10pt;
            font-weight: 600;
            color: #4a5568;
            margin: 2px 0 0 0;
        }
        .header-meta {
            font-size: 7.5pt;
            color: #718096;
            margin-top: 3px;
        }

        /* Metadata & Filter Box */
        .meta-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
        }
        .meta-box td {
            padding: 4px 8px;
            font-size: 8pt;
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            color: #4a5568;
            width: 15%;
        }
        .meta-val {
            color: #1a202c;
            width: 35%;
        }

        /* Section Headings */
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #2d3748;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e0;
            padding-bottom: 3px;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 7.5pt;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #cbd5e0;
            padding: 4px 5px;
            text-align: left;
        }
        table.data-table th {
            background-color: #edf2f7;
            font-weight: bold;
            color: #2d3748;
            text-transform: uppercase;
            font-size: 7pt;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: bold; }

        /* Summary 2-Column Grid */
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .summary-grid td {
            vertical-align: top;
            padding: 0 4px;
        }

        /* Disclaimer & Footer */
        .disclaimer-box {
            border: 1px dashed #cbd5e0;
            background-color: #fcfcfc;
            padding: 6px 8px;
            font-size: 7pt;
            color: #4a5568;
            margin-top: 8px;
            margin-bottom: 12px;
        }
        .disclaimer-box strong {
            color: #2d3748;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            font-size: 8pt;
            vertical-align: top;
        }
        .sig-space {
            height: 45px;
        }

        /* Footer Halaman */
        .page-footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 15px;
            font-size: 7pt;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen Akademik -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-title">Laporan Dokumentasi &amp; Analisis Monitoring Greenhouse</div>
                    <div class="header-subtitle">Sistem Web Monitoring Tanaman Melon (Smart Greenhouse IoT)</div>
                    <div class="header-meta">
                        Laboratorium Agroteknologi &amp; Instrumentasi Cerdas &bull; Universitas / Institusi Penelitian
                    </div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="font-size: 8pt; font-weight: bold; color: #2d3748;">DOKUMEN RESMI</div>
                    <div style="font-size: 7.5pt; color: #718096;">Dicetak: {{ $printedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Parameter & Metadata Laporan -->
    <table class="meta-box">
        <tr>
            <td class="meta-label">Periode Observasi:</td>
            <td class="meta-val">
                @if(!empty($filters['date_from']) || !empty($filters['date_to']))
                    {{ $filters['date_from'] ?? 'Awal' }} s.d. {{ $filters['date_to'] ?? 'Sekarang' }}
                @else
                    Semua Periode Terdata di Database
                @endif
            </td>
            <td class="meta-label">Tanaman Terpilih:</td>
            <td class="meta-val">
                {{ $selectedPlant ? $selectedPlant->name . ' (' . ($selectedPlant->location ?? 'Greenhouse') . ')' : 'Semua Tanaman (Agregasi Seluruh Blok)' }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Filter Sumber Data:</td>
            <td class="meta-val">
                {{ (!empty($filters['source']) && $filters['source'] !== 'all') ? strtoupper($filters['source']) : 'Semua Sumber (Web, API, Google Sheets, Simulator)' }}
            </td>
            <td class="meta-label">Operator / Pencetak:</td>
            <td class="meta-val">{{ $printedBy }}</td>
        </tr>
        <tr>
            <td class="meta-label">Total Data Tercatat:</td>
            <td class="meta-val">
                <strong>{{ number_format($totalRecords) }} entri observasi</strong>
                @if($isTruncated)
                    <span style="color: #c53030;">(Tabel dibatasi {{ $safeLimit }} baris pertama untuk keamanan render PDF)</span>
                @endif
            </td>
            <td class="meta-label">Lingkup Agregasi:</td>
            <td class="meta-val">{{ $statistics['aggregation_scope'] }}</td>
        </tr>
    </table>

    <!-- Ringkasan Statistik 8 Parameter Sensor -->
    <div class="section-title">1. Ringkasan Statistik 8 Parameter Sensor Greenhouse</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Parameter Sensor Lingkungan</th>
                <th style="width: 10%;" class="text-center">Satuan</th>
                <th style="width: 20%;" class="text-right">Rata-Rata (AVG)</th>
                <th style="width: 20%;" class="text-right">Minimum (MIN)</th>
                <th style="width: 20%;" class="text-right">Maksimum (MAX)</th>
                <th style="width: 5%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statRows = [
                    'temperature'          => 'Suhu Udara Greenhouse',
                    'humidity'             => 'Kelembapan Relatif Udara',
                    'soil_moisture'        => 'Kelembapan Media/Tanah',
                    'ph'                   => 'pH Nutrisi Fertigasi',
                    'ec'                   => 'Konduktivitas Listrik (EC)',
                    'light_intensity'      => 'Intensitas Cahaya Matahari',
                    'nutrient_temperature' => 'Suhu Larutan Tandon Nutrisi',
                    'tank_level'           => 'Level Volume Tandon Nutrisi',
                ];
            @endphp
            @foreach($statRows as $pKey => $pLabel)
                @php
                    $pStat = $statistics[$pKey] ?? null;
                    $avg = $pStat['avg'] ?? null;
                    $min = $pStat['min'] ?? null;
                    $max = $pStat['max'] ?? null;
                    $unit = $pStat['unit'] ?? '-';
                @endphp
                <tr>
                    <td><strong>{{ $pLabel }}</strong></td>
                    <td class="text-center font-mono">{{ $unit }}</td>
                    <td class="text-right font-mono font-bold">{{ $avg !== null ? number_format($avg, 2) : 'N/A' }}</td>
                    <td class="text-right font-mono">{{ $min !== null ? number_format($min, 2) : 'N/A' }}</td>
                    <td class="text-right font-mono">{{ $max !== null ? number_format($max, 2) : 'N/A' }}</td>
                    <td class="text-center font-mono" style="font-size: 6.5pt;">
                        {{ $avg !== null ? 'TERDATA' : 'N/A' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Ringkasan Alarm & Fuzzy (2 Kolom) -->
    <table class="summary-grid">
        <tr>
            <td style="width: 50%; padding-right: 5px;">
                <div class="section-title">2. Ringkasan Insiden Alarm (4 Parameter Utama)</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kategori Alarm</th>
                            <th class="text-center">Jumlah Kasus</th>
                            <th>Keterangan Operasional</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Total Insiden Terdeteksi</strong></td>
                            <td class="text-center font-mono font-bold">{{ $alarmSummary['total'] }}</td>
                            <td>Insiden deviasi nilai ambang batas</td>
                        </tr>
                        <tr>
                            <td>Tingkat Kritis (Critical)</td>
                            <td class="text-center font-mono" style="color: #c53030; font-weight: bold;">{{ $alarmSummary['critical'] }}</td>
                            <td>Membutuhkan tindakan darurat</td>
                        </tr>
                        <tr>
                            <td>Tingkat Peringatan (Warning)</td>
                            <td class="text-center font-mono">{{ $alarmSummary['warning'] }}</td>
                            <td>Deviasi ringan di luar rentang optimal</td>
                        </tr>
                        <tr>
                            <td>Status Selesai (Resolved)</td>
                            <td class="text-center font-mono" style="color: #276749; font-weight: bold;">{{ $alarmSummary['resolved'] }}</td>
                            <td>Nilai sensor telah kembali normal</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 50%; padding-left: 5px;">
                <div class="section-title">3. Ringkasan Analisis Logika Fuzzy Mamdani</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Metrik Fuzzy Mamdani</th>
                            <th class="text-center">Nilai / Jumlah</th>
                            <th>Status Klasifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Rata-Rata Skor Kondisi</strong></td>
                            <td class="text-center font-mono font-bold">
                                {{ $fuzzySummary['avg_score'] !== null ? number_format($fuzzySummary['avg_score'], 2) : 'N/A' }}
                            </td>
                            <td>Rentang skor kontinu [0 – 100]</td>
                        </tr>
                        <tr>
                            <td>Kondisi BAIK [65 – 100]</td>
                            <td class="text-center font-mono" style="color: #276749; font-weight: bold;">
                                {{ $fuzzySummary['by_category']['BAIK'] }}
                            </td>
                            <td>Lingkungan mikro optimal bagi melon</td>
                        </tr>
                        <tr>
                            <td>Kondisi PERLU PERHATIAN [40 – 70]</td>
                            <td class="text-center font-mono">
                                {{ $fuzzySummary['by_category']['PERLU_PERHATIAN'] }}
                            </td>
                            <td>Terjadi deviasi ringan parameter</td>
                        </tr>
                        <tr>
                            <td>Kondisi BURUK [0 – 45]</td>
                            <td class="text-center font-mono" style="color: #c53030; font-weight: bold;">
                                {{ $fuzzySummary['by_category']['BURUK'] }}
                            </td>
                            <td>Stres mikroklimat atau media tanam</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Data Monitoring -->
    <div class="section-title">4. Tabel Rincian Rekaman Monitoring Terverifikasi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">No</th>
                <th style="width: 11%;">Waktu</th>
                <th style="width: 12%;">Tanaman</th>
                <th style="width: 6%;">Sumber</th>
                <th style="width: 7%;" class="text-right">Suhu (°C)</th>
                <th style="width: 7%;" class="text-right">Lembap (%)</th>
                <th style="width: 7%;" class="text-right">Tanah (%)</th>
                <th style="width: 6%;" class="text-right">pH</th>
                <th style="width: 6%;" class="text-right">EC</th>
                <th style="width: 7%;" class="text-right">Cahaya</th>
                <th style="width: 7%;" class="text-right">Suhu Nut</th>
                <th style="width: 6%;" class="text-right">Tandon</th>
                <th style="width: 8%;">Kondisi</th>
                <th style="width: 7%;" class="text-center">Fuzzy</th>
            </tr>
        </thead>
        <tbody class="font-mono">
            @forelse($monitorings as $idx => $m)
                @php
                    $fuzzy = $m->latestFuzzyInference;
                    $fScore = $fuzzy?->fuzzy_score ?? $m->fuzzy_score;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-size: 7pt;">{{ $m->monitored_at ? $m->monitored_at->format('d/m/y H:i') : '-' }}</td>
                    <td style="font-family: sans-serif; font-size: 7pt;">{{ $m->plant?->name ?? 'N/A' }}</td>
                    <td style="font-family: sans-serif; font-size: 6.5pt;">{{ $m->source ?? 'web' }}</td>
                    <td class="text-right">{{ $m->temperature !== null ? number_format($m->temperature, 1) : '-' }}</td>
                    <td class="text-right">{{ $m->humidity !== null ? number_format($m->humidity, 1) : '-' }}</td>
                    <td class="text-right">{{ $m->soil_moisture !== null ? number_format($m->soil_moisture, 1) : '-' }}</td>
                    <td class="text-right">{{ $m->ph !== null ? number_format($m->ph, 2) : '-' }}</td>
                    <td class="text-right">{{ $m->ec !== null ? number_format($m->ec, 2) : '-' }}</td>
                    <td class="text-right">{{ $m->light_intensity !== null ? number_format($m->light_intensity, 0) : '-' }}</td>
                    <td class="text-right">{{ $m->nutrient_temperature !== null ? number_format($m->nutrient_temperature, 1) : '-' }}</td>
                    <td class="text-right">{{ $m->tank_level !== null ? number_format($m->tank_level, 0) : '-' }}</td>
                    <td style="font-family: sans-serif; font-size: 7pt;">{{ $m->plant_condition ?? '-' }}</td>
                    <td class="text-center font-bold">
                        {{ $fScore !== null ? number_format($fScore, 1) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center" style="padding: 15px; color: #718096; font-family: sans-serif;">
                        Tidak ada rekaman data monitoring yang memenuhi kriteria filter yang ditentukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Catatan Integritas Akademik & Batasan Sistem -->
    <div class="disclaimer-box">
        <strong>Catatan Integritas Akademik &amp; Batasan Sistem:</strong>
        <br>
        1. <strong>Status Kontrol Aktuator:</strong> Seluruh modul aktuator (Pompa Irigasi PUMP-01 dan Kipas Exhaust FAN-01) beroperasi dalam model <em>Simulasi Database</em> untuk tujuan validasi perangkat lunak akademik dan <u>bukan</u> merupakan saklar fisik tertutup (*closed-loop physical relay*).
        <br>
        2. <strong>Logika Fuzzy Mamdani:</strong> Aturan fuzzy menggunakan versi <code>PROVISIONAL_FOR_SOFTWARE_TESTING</code> (16 aturan) yang telah divalidasi deterministik secara matematis. Rekomendasi tindakan merupakan panduan awal dan membutuhkan supervisi agronomis sebelum diaplikasikan langsung ke lapangan.
        <br>
        3. <strong>Kebijakan Alarm 4 Parameter:</strong> Sistem peringatan aktif hanya berlaku untuk Suhu Udara, Kelembapan Udara, Kelembapan Tanah, dan pH sesuai tinjauan tim Teknik Elektro (Phase 13). Parameter EC, Cahaya, Suhu Nutrisi, dan Level Tandon dicatat sebagai monitoring visual.
    </div>

    <!-- Blok Pengesahan -->
    <table class="signatures">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Penanggung Jawab Greenhouse / Dosen Pembimbing</strong>
                <div class="sig-space"></div>
                ______________________________________<br>
                NIP / Identitas Akademik
            </td>
            <td>
                Dibuat Oleh,<br>
                <strong>Operator Pemantau / Tim Peneliti</strong>
                <div class="sig-space"></div>
                <strong>{{ $printedBy }}</strong><br>
                Sistem Web Monitoring Tanaman Melon
            </td>
        </tr>
    </table>

    <!-- Footer Otomatis -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT} — Sistem Web Monitoring Tanaman Melon (Phase 16 Final)";
            $size = 7;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text(792 - $width - 34, 575, $text, $font, $size, array(0.5, 0.5, 0.5));
        }
    </script>

</body>
</html>
