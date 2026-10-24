<?php

namespace App\Services\FuzzyLogic;

class FuzzyConclusionService
{
    /**
     * Hasilkan kesimpulan human-readable dan rekomendasi tindakan budidaya berdasarkan hasil inferensi.
     *
     * @param array $inferenceResult Hasil dari MamdaniInferenceService
     * @param string $ruleVersion
     * @return array{conclusion: string, recommendation: string}
     */
    public function generate(array $inferenceResult, string $ruleVersion = 'v1.0-provisional'): array
    {
        $status = $inferenceResult['status'] ?? 'AVAILABLE';

        if ($status === 'INSUFFICIENT_DATA') {
            return [
                'conclusion'     => 'Data sensor lingkungan tidak lengkap untuk evaluasi fuzzy logic (diperlukan suhu, kelembapan, kelembapan tanah, dan pH).',
                'recommendation' => 'Pastikan seluruh sensor IoT (suhu, kelembapan udara, kelembapan media, dan probe pH) aktif dan terhubung.',
            ];
        }

        if ($status === 'INSUFFICIENT_RULE_COVERAGE') {
            return [
                'conclusion'     => "Kombinasi nilai sensor saat ini tidak memicu aturan aktif pada rule-base provisional ({$ruleVersion}).",
                'recommendation' => 'Laporkan matriks sensor ini kepada Tim Elektro untuk perumusan kurva dan aturan baru.',
            ];
        }

        if ($status === 'ERROR') {
            return [
                'conclusion'     => 'Terjadi kesalahan sistem saat memproses inferensi logika fuzzy.',
                'recommendation' => 'Periksa log sistem backend dan integritas data sensor.',
            ];
        }

        $activatedRules = $inferenceResult['activated_rules'] ?? [];
        $score = $inferenceResult['score'] ?? null;
        $category = $inferenceResult['category'] ?? 'UNKNOWN';

        if (empty($activatedRules)) {
            return [
                'conclusion'     => "Kondisi greenhouse terklasifikasi sebagai {$category} dengan skor {$score}/100.",
                'recommendation' => 'Lakukan pemantauan rutin terhadap kondisi fisik tanaman melon.',
            ];
        }

        // Ambil aturan terkuat (firing strength tertinggi)
        $topRule = $activatedRules[0];
        $ruleConclusion = $topRule['conclusion'] ?? null;
        $ruleRecommendation = $topRule['recommendation'] ?? null;

        // Jika ada lebih dari satu aturan aktif yang signifikan, sebutkan aturan pendukung
        $additionalNotes = [];
        for ($i = 1; $i < min(3, count($activatedRules)); $i++) {
            if ($activatedRules[$i]['firing_strength'] >= 0.2 && !empty($activatedRules[$i]['conclusion'])) {
                $additionalNotes[] = $activatedRules[$i]['conclusion'];
            }
        }

        $finalConclusion = $ruleConclusion ?: "Kondisi lingkungan greenhouse melon terklasifikasi {$category} (Skor {$score}/100).";
        if (!empty($additionalNotes)) {
            $finalConclusion .= ' Catatan tambahan: ' . implode(' ', $additionalNotes);
        }

        $finalRecommendation = $ruleRecommendation ?: 'Pertahankan kontrol iklim mikro sesuai panduan budidaya.';

        return [
            'conclusion'     => $finalConclusion,
            'recommendation' => $finalRecommendation,
        ];
    }
}
