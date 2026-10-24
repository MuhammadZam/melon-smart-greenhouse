<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetsSyncConfig;
use App\Models\GoogleSheetsSyncLog;
use App\Models\IotDevice;
use App\Models\Monitoring;
use App\Services\GoogleSheets\GoogleSheetsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoogleSheetsIntegrationController extends Controller
{
    protected GoogleSheetsSyncService $syncService;

    public function __construct(GoogleSheetsSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Halaman Dashboard Integrasi Google Sheets & Keamanan Perangkat IoT.
     */
    public function index(): View
    {
        $config = $this->syncService->getOrCreateDefaultConfig();
        $logs = GoogleSheetsSyncLog::orderByDesc('created_at')->paginate(10);
        $devices = IotDevice::orderByDesc('is_active')->orderBy('device_id')->get();

        $totalImported = Monitoring::where('source', 'google_sheets')->count();
        $totalLogsImported = (int) GoogleSheetsSyncLog::sum('records_imported');
        $totalLogsSkipped  = (int) GoogleSheetsSyncLog::sum('records_skipped');
        $totalLogsFailed   = (int) GoogleSheetsSyncLog::sum('records_failed');

        $activeDevicesCount = IotDevice::active()->count();
        $revokedDevicesCount = IotDevice::revoked()->count();

        // Evaluasi status kesegaran telemetri
        $freshnessStatus = $config->freshnessStatus();
        $freshnessLabel = $config->freshnessLabel();

        return view('admin.integrations.google_sheets', compact(
            'config',
            'logs',
            'devices',
            'totalImported',
            'totalLogsImported',
            'totalLogsSkipped',
            'totalLogsFailed',
            'activeDevicesCount',
            'revokedDevicesCount',
            'freshnessStatus',
            'freshnessLabel'
        ));
    }

    /**
     * Pemicu Sinkronisasi Manual oleh Administrator.
     */
    public function syncNow(Request $request): RedirectResponse
    {
        $config = $this->syncService->getOrCreateDefaultConfig();
        if ($request->boolean('reset_cursor')) {
            $config->update(['last_synced_row' => 1]);
        }
        $batchLimit = (int) $request->input('batch_limit', 100);

        $log = $this->syncService->sync($config, 'manual_admin', $batchLimit);

        if ($log->status === GoogleSheetsSyncConfig::STATUS_SUCCESS) {
            return redirect()->route('admin.integrations.google-sheets.index')
                ->with('success', "Sinkronisasi berhasil! {$log->records_imported} record diimpor, {$log->records_skipped} record dilewati (duplikat).");
        }

        if ($log->status === GoogleSheetsSyncConfig::STATUS_PARTIAL) {
            return redirect()->route('admin.integrations.google-sheets.index')
                ->with('warning', "Sinkronisasi selesai sebagian: {$log->records_imported} diimpor, {$log->records_failed} gagal. Periksa log detail error.");
        }

        return redirect()->route('admin.integrations.google-sheets.index')
            ->with('error', "Sinkronisasi gagal: " . ($log->error_details ?? 'Terjadi kesalahan komunikasi dengan Google Sheets.'));
    }

    /**
     * Daftarkan token perangkat IoT baru (Phase 14 Device Security).
     */
    public function storeDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:50', 'unique:iot_devices,device_id'],
            'name'      => ['required', 'string', 'max:100'],
            'type'      => ['required', 'in:esp32_hardware,python_simulator,google_sheets_gateway'],
        ]);

        [$device, $plainTextToken] = IotDevice::createWithToken(
            deviceId: $validated['device_id'],
            name: $validated['name'],
            type: $validated['type']
        );

        return redirect()->route('admin.integrations.google-sheets.index')
            ->with('success', "Perangkat {$device->device_id} berhasil didaftarkan!")
            ->with('generated_token', $plainTextToken)
            ->with('generated_device_id', $device->device_id);
    }

    /**
     * Cabut (Revoke) token perangkat IoT.
     */
    public function revokeDevice(Request $request, IotDevice $device): RedirectResponse
    {
        $reason = $request->input('reason', 'Dicabut secara manual oleh Administrator.');
        $device->revoke($reason);

        return redirect()->route('admin.integrations.google-sheets.index')
            ->with('warning', "Token perangkat {$device->device_id} telah dicabut.");
    }

    /**
     * Aktifkan kembali perangkat yang sebelumnya dicabut.
     */
    public function activateDevice(IotDevice $device): RedirectResponse
    {
        $device->update([
            'is_active'      => true,
            'revoked_at'     => null,
            'revoked_reason' => null,
        ]);

        return redirect()->route('admin.integrations.google-sheets.index')
            ->with('success', "Perangkat {$device->device_id} berhasil diaktifkan kembali.");
    }
}
