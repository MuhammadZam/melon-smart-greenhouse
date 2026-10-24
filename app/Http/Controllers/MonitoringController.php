<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitoringRequest;
use App\Http\Requests\UpdateMonitoringRequest;
use App\Models\Monitoring;
use App\Models\MonitoringParameter;
use App\Models\Plant;
use App\Services\MonitoringAlarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Menampilkan daftar data monitoring dengan fitur filter, pencarian, dan pagination.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function index(Request $request): View
    {
        $filters = [
            'plant_id'  => $request->query('plant_id'),
            'condition' => $request->query('condition'),
            'search'    => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
        ];

        $monitorings = Monitoring::with('plant')
            ->filter($filters)
            ->latest('monitored_at')
            ->paginate(10)
            ->withQueryString();

        $plants = Plant::orderBy('name')->get();
        $parameters = MonitoringParameter::all();

        $totalRecords = Monitoring::count();
        $filteredCount = $monitorings->total();

        // Ambil daftar kondisi unik untuk opsi filter dropdown
        $conditions = Monitoring::distinct()->pluck('plant_condition')->filter()->values();

        return view('monitorings.index', compact(
            'monitorings',
            'plants',
            'parameters',
            'filters',
            'totalRecords',
            'filteredCount',
            'conditions'
        ));
    }

    /**
     * Menampilkan form untuk menambahkan data monitoring baru.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function create(): View
    {
        $plants = Plant::orderBy('name')->get();
        $parameters = MonitoringParameter::all();
        $defaultTime = now()->format('Y-m-d\TH:i');

        return view('monitorings.create', compact('plants', 'parameters', 'defaultTime'));
    }

    /**
     * Menyimpan data monitoring baru ke dalam database.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function store(StoreMonitoringRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $monitoring = Monitoring::create($validated);
        $monitoring->load('plant');

        // Phase 12: Evaluasi alarm threshold setelah data disimpan
        app(MonitoringAlarmService::class)->evaluate($monitoring);

        // Phase 15: Eksekusi inferensi fuzzy logic (failure-isolated)
        app(\App\Services\FuzzyLogic\FuzzyPipelineService::class)->process($monitoring, 'web_form');

        $plantName = $monitoring->plant->name ?? 'Tanaman';

        return redirect()
            ->route('monitorings.index')
            ->with('success', "Data monitoring untuk {$plantName} berhasil disimpan ke database.");
    }

    /**
     * Menampilkan detail lengkap data monitoring tertentu.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function show(Monitoring $monitoring): View
    {
        $monitoring->load(['plant', 'latestFuzzyInference']);
        $parameters = MonitoringParameter::all()->keyBy('parameter_name');

        return view('monitorings.show', compact('monitoring', 'parameters'));
    }

    /**
     * Menampilkan form edit data monitoring.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function edit(Monitoring $monitoring): View
    {
        // Otorisasi: Pengamat dilarang mengedit data monitoring
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk mengedit data monitoring.');
        }

        $monitoring->load('plant');
        $plants = Plant::orderBy('name')->get();
        $parameters = MonitoringParameter::all();

        return view('monitorings.edit', compact('monitoring', 'plants', 'parameters'));
    }

    /**
     * Memperbarui data monitoring yang telah ada di database.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function update(UpdateMonitoringRequest $request, Monitoring $monitoring): RedirectResponse
    {
        // Otorisasi: Pengamat dilarang mengedit data monitoring
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk mengedit data monitoring.');
        }

        $validated = $request->validated();
        $monitoring->update($validated);
        $monitoring->refresh()->load('plant');

        // Phase 12: Re-evaluasi alarm threshold setelah data diperbarui
        app(MonitoringAlarmService::class)->evaluate($monitoring);

        // Phase 15: Re-evaluasi inferensi fuzzy logic (failure-isolated)
        app(\App\Services\FuzzyLogic\FuzzyPipelineService::class)->process($monitoring, 'web_form');

        return redirect()
            ->route('monitorings.index')
            ->with('success', "Data monitoring #{$monitoring->id} berhasil diperbarui.");
    }

    /**
     * Menghapus data monitoring dari database.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function destroy(Monitoring $monitoring): RedirectResponse
    {
        // Otorisasi: Pengamat dilarang menghapus data monitoring
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk menghapus data monitoring.');
        }

        $monitoringId = $monitoring->id;
        $plantName = $monitoring->plant->name ?? 'Tanaman';

        $monitoring->delete();

        return redirect()
            ->route('monitorings.index')
            ->with('success', "Data monitoring #{$monitoringId} ({$plantName}) berhasil dihapus.");
    }
}
