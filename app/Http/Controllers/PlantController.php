<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlantRequest;
use App\Http\Requests\UpdatePlantRequest;
use App\Models\Plant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlantController extends Controller
{
    /**
     * Menampilkan daftar data tanaman/plot melon dengan pencarian, filter status, dan pagination.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];

        $plants = Plant::withCount('monitorings')
            ->with('latestMonitoring')
            ->filter($filters)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $totalPlants     = Plant::count();
        $activePlants    = Plant::where('status', 'active')->count();
        $harvestedPlants = Plant::where('status', 'harvested')->count();
        $inactivePlants  = Plant::where('status', 'inactive')->count();
        $filteredCount   = $plants->total();

        return view('plants.index', compact(
            'plants',
            'filters',
            'totalPlants',
            'activePlants',
            'harvestedPlants',
            'inactivePlants',
            'filteredCount'
        ));
    }

    /**
     * Menampilkan form untuk menambahkan data tanaman baru.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function create(): View
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk menambah data tanaman.');
        }

        return view('plants.create');
    }

    /**
     * Menyimpan data tanaman baru ke dalam database.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function store(StorePlantRequest $request): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk menambah data tanaman.');
        }

        $validated = $request->validated();
        Plant::create($validated);

        return redirect()
            ->route('plants.index')
            ->with('success', 'Data tanaman berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail lengkap tanaman beserta statistik dan riwayat monitoring terakhir.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function show(Plant $plant): View
    {
        $plant->loadCount('monitorings');
        $plant->load(['monitorings' => function ($query) {
            $query->latest('monitored_at')->take(10);
        }]);

        $latestMonitoring = $plant->latestMonitoring;

        return view('plants.show', compact('plant', 'latestMonitoring'));
    }

    /**
     * Menampilkan form edit data tanaman.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function edit(Plant $plant): View
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk mengedit data tanaman.');
        }

        return view('plants.edit', compact('plant'));
    }

    /**
     * Memperbarui data tanaman di dalam database.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function update(UpdatePlantRequest $request, Plant $plant): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk mengedit data tanaman.');
        }

        $validated = $request->validated();
        $plant->update($validated);

        return redirect()
            ->route('plants.index')
            ->with('success', 'Data tanaman berhasil diperbarui.');
    }

    /**
     * Menghapus data tanaman dari database dengan proteksi relasi monitoring.
     * Hak Akses: Admin ✅, Pengamat ❌ (403 Forbidden)
     */
    public function destroy(Plant $plant): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator yang memiliki hak akses untuk menghapus data tanaman.');
        }

        // Proteksi: Tanaman yang masih memiliki data riwayat monitoring dilarang dihapus
        if ($plant->monitorings()->exists()) {
            return redirect()
                ->route('plants.index')
                ->with('error', 'Tanaman tidak dapat dihapus karena masih memiliki data monitoring.');
        }

        $plant->delete();

        return redirect()
            ->route('plants.index')
            ->with('success', 'Data tanaman berhasil dihapus.');
    }
}
