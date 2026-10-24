<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MonitoringReportExportService — Phase 16
 *
 * Bertanggung jawab atas pembuatan ekspor laporan format CSV (streamed, UTF-8 BOM,
 * sanitasi CSV injection) dan PDF profesional standar akademik (A4 landscape DomPDF).
 */
class MonitoringReportExportService
{
    protected MonitoringReportService $reportService;

    public function __construct(MonitoringReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Sanitasi sel teks terhadap potensi CSV Formula Injection (CWE-1236).
     * Jika diawali '=', '+', '-', '@', tab, atau carriage return, tambahkan single quote (').
     */
    public static function sanitizeCsvCell(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Ekspor Laporan Monitoring ke format CSV Streaming dengan UTF-8 BOM.
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $query = $this->reportService->buildQuery($filters);
        $dateStr = now()->format('Ymd_His');
        $fileName = "laporan_monitoring_melon_{$dateStr}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // 1. Tulis UTF-8 BOM agar kompatibel dengan Microsoft Excel di Windows
            fwrite($handle, "\xEF\xBB\xBF");

            // 2. Baris Header CSV
            fputcsv($handle, [
                'No',
                'Waktu Monitoring',
                'ID Tanaman',
                'Nama Tanaman',
                'Varietas',
                'Lokasi',
                'Sumber Data',
                'ID Perangkat',
                'Suhu Udara (°C)',
                'Kelembapan Udara (%)',
                'Kelembapan Tanah (%)',
                'pH Nutrisi/Media',
                'EC Nutrisi (mS/cm)',
                'Intensitas Cahaya (lux)',
                'Suhu Nutrisi (°C)',
                'Level Tandon Nutrisi (%)',
                'Tinggi Tanaman (cm)',
                'Kerapatan Kanopi (%)',
                'Kondisi Tanaman',
                'Warna Daun',
                'Skor Fuzzy',
                'Kategori Fuzzy',
                'Catatan Observasi',
            ]);

            // 3. Tulis Baris Data dalam Chunks (hemat memori)
            $counter = 1;
            $query->chunk(250, function ($monitorings) use ($handle, &$counter) {
                foreach ($monitorings as $m) {
                    $fuzzy = $m->latestFuzzyInference;

                    fputcsv($handle, [
                        $counter++,
                        $m->monitored_at ? $m->monitored_at->format('Y-m-d H:i:s') : '',
                        $m->plant_id,
                        self::sanitizeCsvCell($m->plant->name ?? 'N/A'),
                        self::sanitizeCsvCell($m->plant->variety ?? '-'),
                        self::sanitizeCsvCell($m->plant->location ?? '-'),
                        self::sanitizeCsvCell($m->source ?? 'manual'),
                        self::sanitizeCsvCell($m->device_id ?? '-'),
                        $m->temperature !== null ? number_format($m->temperature, 2, '.', '') : 'N/A',
                        $m->humidity !== null ? number_format($m->humidity, 2, '.', '') : 'N/A',
                        $m->soil_moisture !== null ? number_format($m->soil_moisture, 2, '.', '') : 'N/A',
                        $m->ph !== null ? number_format($m->ph, 2, '.', '') : 'N/A',
                        $m->ec !== null ? number_format($m->ec, 2, '.', '') : 'N/A',
                        $m->light_intensity !== null ? number_format($m->light_intensity, 1, '.', '') : 'N/A',
                        $m->nutrient_temperature !== null ? number_format($m->nutrient_temperature, 2, '.', '') : 'N/A',
                        $m->tank_level !== null ? number_format($m->tank_level, 1, '.', '') : 'N/A',
                        $m->plant_height !== null ? number_format($m->plant_height, 2, '.', '') : 'N/A',
                        $m->plant_density !== null ? number_format($m->plant_density, 1, '.', '') : 'N/A',
                        self::sanitizeCsvCell($m->plant_condition ?? '-'),
                        self::sanitizeCsvCell($m->plant_color ?? '-'),
                        $fuzzy && $fuzzy->fuzzy_score !== null ? number_format($fuzzy->fuzzy_score, 2, '.', '') : ($m->fuzzy_score !== null ? number_format($m->fuzzy_score, 2, '.', '') : 'N/A'),
                        self::sanitizeCsvCell($fuzzy->fuzzy_category ?? ($m->fuzzy_label ?? 'N/A')),
                        self::sanitizeCsvCell($m->notes ?? ''),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Ekspor Laporan Monitoring ke format PDF Standar Akademik (A4 Landscape DomPDF).
     */
    public function exportPdf(array $filters): Response
    {
        $baseQuery = $this->reportService->buildQuery($filters);

        // Hitung total record sebelum batasan keamanan memori PDF
        $totalRecords = (clone $baseQuery)->count();
        $safeLimit = 500;
        $isTruncated = $totalRecords > $safeLimit;

        // Ambil data monitoring dibatasi batas aman 500 baris
        $monitorings = (clone $baseQuery)->take($safeLimit)->get();

        // Hitung statistik dan ringkasan alarm & fuzzy
        $statistics = $this->reportService->calculateStatistics($baseQuery, $filters);
        $alarmSummary = $this->reportService->calculateAlarmSummary($filters);
        $fuzzySummary = $this->reportService->calculateFuzzySummary($filters);

        // Tanaman terpilih
        $selectedPlant = null;
        if (!empty($filters['plant_id']) && $filters['plant_id'] !== 'all' && is_numeric($filters['plant_id'])) {
            $selectedPlant = \App\Models\Plant::find((int) $filters['plant_id']);
        }

        $printedBy = auth()->user() ? auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')' : 'Sistem Otomatis';
        $printedAt = now()->format('d M Y, H:i:s \W\I\B');

        $viewData = [
            'monitorings'    => $monitorings,
            'totalRecords'   => $totalRecords,
            'safeLimit'      => $safeLimit,
            'isTruncated'    => $isTruncated,
            'statistics'     => $statistics,
            'alarmSummary'   => $alarmSummary,
            'fuzzySummary'   => $fuzzySummary,
            'selectedPlant'  => $selectedPlant,
            'filters'        => $filters,
            'printedBy'      => $printedBy,
            'printedAt'      => $printedAt,
        ];

        $pdf = Pdf::loadView('reports.monitoring_pdf', $viewData)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);

        $dateStr = now()->format('Ymd_His');
        $fileName = "laporan_monitoring_melon_{$dateStr}.pdf";

        return $pdf->download($fileName);
    }
}
