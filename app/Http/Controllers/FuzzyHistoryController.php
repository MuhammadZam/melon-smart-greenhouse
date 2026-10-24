<?php

namespace App\Http\Controllers;

use App\Models\FuzzyInferenceResult;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuzzyHistoryController extends Controller
{
    /**
     * Menampilkan daftar riwayat inferensi logika fuzzy dengan filter dan statistik.
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'plant_id'  => ['nullable', 'string'],
            'category'  => ['nullable', 'string', 'in:all,BAIK,PERLU_PERHATIAN,BURUK'],
            'status'    => ['nullable', 'string', 'in:all,AVAILABLE,INSUFFICIENT_DATA,INSUFFICIENT_RULE_COVERAGE,ERROR,STALE'],
            'source'    => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $query = FuzzyInferenceResult::with(['plant', 'monitoring'])
            ->filter($validated)
            ->latest('inferred_at');

        $inferences = (clone $query)->paginate(15)->withQueryString();

        // Statistik agregat untuk kartu ringkasan
        $totalEvaluations = FuzzyInferenceResult::count();
        $countBaik = FuzzyInferenceResult::where('fuzzy_category', 'BAIK')->count();
        $countPerhatian = FuzzyInferenceResult::where('fuzzy_category', 'PERLU_PERHATIAN')->count();
        $countBuruk = FuzzyInferenceResult::where('fuzzy_category', 'BURUK')->count();
        $avgScore = FuzzyInferenceResult::whereNotNull('fuzzy_score')->avg('fuzzy_score');

        // Master tanaman untuk dropdown filter
        $plants = Plant::orderBy('name')->get();

        // Data untuk grafik tren 20 inferensi terakhir
        $trendData = FuzzyInferenceResult::with('plant')
            ->whereNotNull('fuzzy_score')
            ->latest('inferred_at')
            ->take(20)
            ->get()
            ->reverse()
            ->values();

        $chartLabels = $trendData->map(fn($item) => $item->inferred_at ? $item->inferred_at->format('d/m H:i') : '')->all();
        $chartScores = $trendData->pluck('fuzzy_score')->all();

        return view('fuzzy.history', compact(
            'inferences',
            'plants',
            'validated',
            'totalEvaluations',
            'countBaik',
            'countPerhatian',
            'countBuruk',
            'avgScore',
            'chartLabels',
            'chartScores'
        ));
    }

    /**
     * Menampilkan detail lengkap suatu hasil inferensi fuzzy (audit trail & snapshot).
     * Hak Akses: Admin ✅, Pengamat ✅
     */
    public function show(FuzzyInferenceResult $fuzzyInference): View
    {
        $fuzzyInference->load(['plant', 'monitoring']);

        return view('fuzzy.show', compact('fuzzyInference'));
    }
}
