<?php

namespace App\Http\Controllers;

use App\Models\MonitoringAlarm;
use App\Models\MonitoringParameter;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AlarmController — Phase 12
 *
 * Menangani:
 *  - Halaman riwayat alarm dengan filter dan paginasi
 *  - Halaman detail alarm tunggal
 *  - Endpoint JSON untuk badge notifikasi navbar (polling)
 *  - Manajemen threshold parameter (Admin only)
 */
class AlarmController extends Controller
{
    /**
     * Halaman riwayat alarm dengan filter dan paginasi.
     * Dapat diakses oleh semua pengguna terautentikasi.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'plant_id'      => ['nullable', 'integer'],
            'parameter_key' => ['nullable', 'string', 'max:60'],
            'severity'      => ['nullable', 'string', 'in:all,warning,critical'],
            'status'        => ['nullable', 'string', 'in:all,active,resolved'],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date'],
        ]);

        $filters = [
            'plant_id'      => $request->query('plant_id', 'all'),
            'parameter_key' => $request->query('parameter_key', 'all'),
            'severity'      => $request->query('severity', 'all'),
            'status'        => $request->query('status', 'all'),
            'date_from'     => $request->query('date_from'),
            'date_to'       => $request->query('date_to'),
        ];

        $alarms = MonitoringAlarm::with('plant')
            ->filter($filters)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('first_detected_at')
            ->paginate(15)
            ->withQueryString();

        $plants         = Plant::orderBy('name')->get();
        $parameterKeys  = MonitoringAlarm::PARAMETER_LABELS;

        // Summary counts for header cards
        $totalActive   = MonitoringAlarm::active()->count();
        $totalCritical = MonitoringAlarm::active()->critical()->count();
        $totalResolved = MonitoringAlarm::resolved()->count();

        return view('alarms.index', compact(
            'alarms',
            'filters',
            'plants',
            'parameterKeys',
            'totalActive',
            'totalCritical',
            'totalResolved',
        ));
    }

    /**
     * Detail satu alarm.
     */
    public function show(MonitoringAlarm $alarm): View
    {
        $alarm->load(['plant', 'triggeringMonitoring.plant']);

        return view('alarms.show', compact('alarm'));
    }

    /**
     * JSON endpoint untuk polling badge notifikasi navbar.
     * Mengembalikan jumlah alarm aktif dan terbaru.
     * Dapat diakses semua pengguna yang terautentikasi.
     */
    public function notificationStatus(): \Illuminate\Http\JsonResponse
    {
        $activeCount   = MonitoringAlarm::active()->count();
        $criticalCount = MonitoringAlarm::active()->critical()->count();

        $latest = MonitoringAlarm::with('plant')
            ->active()
            ->orderByDesc('first_detected_at')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'plant_name'       => $a->plant?->name ?? 'N/A',
                'parameter'        => $a->parameter_display_name,
                'severity'         => $a->severity,
                'sensor_value'     => $a->sensor_value,
                'unit'             => $a->unit,
                'violation'        => $a->violation_direction === 'below_minimum' ? 'Di Bawah Min' : 'Di Atas Maks',
                'first_detected'   => $a->first_detected_at?->diffForHumans() ?? '-',
                'alarm_url'        => route('alarms.show', $a->id),
            ]);

        return response()->json([
            'active_count'   => $activeCount,
            'critical_count' => $criticalCount,
            'latest'         => $latest,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Threshold Management (Admin Only)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Halaman daftar & form edit threshold parameter.
     * Hanya Administrator.
     */
    public function thresholds(): View
    {
        $parameters = MonitoringParameter::orderBy('parameter_name')->get();

        return view('alarms.thresholds', compact('parameters'));
    }

    /**
     * Update threshold untuk satu parameter.
     * Hanya Administrator.
     */
    public function updateThreshold(Request $request, MonitoringParameter $parameter): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'minimum_value'     => ['required', 'numeric', 'min:-9999', 'max:9999'],
            'maximum_value'     => ['required', 'numeric', 'min:-9999', 'max:9999',
                function ($attribute, $value, $fail) use ($request) {
                    if ((float) $value <= (float) $request->input('minimum_value')) {
                        $fail('Nilai maksimum harus lebih besar dari nilai minimum.');
                    }
                },
            ],
            'critical_min_value' => ['nullable', 'numeric', 'min:-9999', 'max:9999'],
            'critical_max_value' => ['nullable', 'numeric', 'min:-9999', 'max:9999',
                function ($attribute, $value, $fail) use ($request) {
                    if (!is_null($value) && !is_null($request->input('critical_min_value'))) {
                        if ((float) $value <= (float) $request->input('critical_min_value')) {
                            $fail('Nilai critical maksimum harus lebih besar dari critical minimum.');
                        }
                    }
                },
            ],
            'parameter_key'     => ['nullable', 'string', 'max:60'],
            'description'       => ['nullable', 'string', 'max:500'],
        ]);

        $parameter->update($validated);

        return redirect()
            ->route('alarms.thresholds')
            ->with('success', "Threshold untuk \"{$parameter->parameter_name}\" berhasil diperbarui.");
    }
}
