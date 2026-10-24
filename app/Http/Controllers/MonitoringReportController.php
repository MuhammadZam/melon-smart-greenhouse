<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Services\Reports\MonitoringReportExportService;
use App\Services\Reports\MonitoringReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MonitoringReportController — Phase 16
 *
 * Mengelola antarmuka laporan pemantauan greenhouse, filter terpadu,
 * ringkasan statistik komprehensif, dan ekspor ke format CSV & PDF.
 * Hak Akses: Admin ✅, Pengamat ✅
 */
class MonitoringReportController extends Controller
{
    protected MonitoringReportService $reportService;
    protected MonitoringReportExportService $exportService;

    public function __construct(
        MonitoringReportService $reportService,
        MonitoringReportExportService $exportService
    ) {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    /**
     * Menampilkan dashboard modul Laporan Monitoring Greenhouse.
     */
    public function index(Request $request): View
    {
        $validated = $this->validateReportRequest($request);

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }

        $reportData = $this->reportService->getReportData($validated, $perPage);

        return view('reports.monitoring', $reportData);
    }

    /**
     * Mengunduh ekspor CSV data monitoring dengan filter yang sedang aktif.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $this->validateReportRequest($request);

        return $this->exportService->exportCsv($validated);
    }

    /**
     * Mengunduh ekspor PDF laporan akademik A4 dengan filter yang sedang aktif.
     */
    public function exportPdf(Request $request): Response
    {
        $validated = $this->validateReportRequest($request);

        return $this->exportService->exportPdf($validated);
    }

    /**
     * Validasi input filter laporan secara konsisten.
     */
    protected function validateReportRequest(Request $request): array
    {
        return $request->validate([
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'plant_id'       => ['nullable', 'string', function ($attr, $val, $fail) {
                if ($val !== null && $val !== 'all' && !Plant::where('id', $val)->exists()) {
                    $fail('Tanaman yang dipilih tidak valid.');
                }
            }],
            'source'         => ['nullable', 'string', 'in:all,web,api,google_sheets,simulator'],
            'condition'      => ['nullable', 'string', 'max:100'],
            'sort_by'        => ['nullable', 'string', 'in:monitored_at,temperature,humidity,soil_moisture,ph,ec,light_intensity,nutrient_temperature,tank_level'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'range'          => ['nullable', 'string', 'in:today,7d,30d,all'],
        ]);
    }
}
