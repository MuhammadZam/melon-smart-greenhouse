<?php

namespace App\Services\FuzzyLogic;

use App\Services\FuzzyLogic\Contracts\MembershipFunctionInterface;

class MamdaniInferenceService
{
    protected FuzzyMembershipService $membershipService;
    protected FuzzyRuleEvaluationService $ruleEvaluationService;
    protected array $config;

    public function __construct(
        FuzzyMembershipService $membershipService,
        FuzzyRuleEvaluationService $ruleEvaluationService,
        ?array $config = null
    ) {
        $this->membershipService = $membershipService;
        $this->ruleEvaluationService = $ruleEvaluationService;
        $this->config = $config ?? config('fuzzy', []);
    }

    /**
     * Jalankan inferensi Mamdani lengkap:
     * 1. Fuzzifikasi
     * 2. Evaluasi aturan (Rule evaluation)
     * 3. Implikasi (Minimum)
     * 4. Agregasi (Maksimum)
     * 5. Defuzzifikasi (Centroid)
     *
     * @param array<string, float|null> $inputs ['temperature' => ..., 'humidity' => ..., 'soil_moisture' => ..., 'ph' => ...]
     * @param float|null $customStep
     * @param float|null $customZMin
     * @param float|null $customZMax
     * @return array
     */
    public function infer(
        array $inputs,
        ?float $customStep = null,
        ?float $customZMin = null,
        ?float $customZMax = null
    ): array {
        // 1. Fuzzifikasi
        $membershipSnapshot = $this->membershipService->fuzzify($inputs);

        // 2. Evaluasi Aturan
        $ruleEval = $this->ruleEvaluationService->evaluateRules($membershipSnapshot);
        $activatedRules = $ruleEval['activated_rules'];

        // Jika tidak ada aturan yang aktif sama sekali
        if (empty($activatedRules) || $ruleEval['max_firing_strength'] <= 0.0) {
            return [
                'status'                  => 'INSUFFICIENT_RULE_COVERAGE',
                'score'                   => null,
                'raw_score'               => null,
                'numerator'               => 0.0,
                'denominator'             => 0.0,
                'category'                => null,
                'input_snapshot'          => $inputs,
                'membership_snapshot'     => $membershipSnapshot,
                'rule_activation_snapshot' => $ruleEval,
                'activated_rules'         => [],
                'consequent_alphas'       => [],
                'aggregated_curve'        => [],
            ];
        }

        // 3. Implikasi & Agregasi
        // Cari firing strength maksimum untuk setiap kategori himpunan output
        $consequentMaxAlpha = [];
        foreach ($activatedRules as $rule) {
            $consequent = $rule['consequent'];
            $alpha = (float) $rule['firing_strength'];
            if (!isset($consequentMaxAlpha[$consequent]) || $alpha > $consequentMaxAlpha[$consequent]) {
                $consequentMaxAlpha[$consequent] = $alpha;
            }
        }

        // Output universe
        $outputConfig = $this->config['output'] ?? [];
        $zMin = $customZMin !== null ? (float) $customZMin : (float) ($outputConfig['min'] ?? 0.0);
        $zMax = $customZMax !== null ? (float) $customZMax : (float) ($outputConfig['max'] ?? 100.0);
        $step = $customStep !== null ? (float) $customStep : (float) ($outputConfig['step'] ?? 1.0);
        if ($step <= 0) {
            $step = 1.0;
        }

        $outputFunctions = $this->membershipService->getOutputFunctions();

        // 4. Hitung kurva agregasi terdistribusi pada universe [zMin, zMax]
        $numerator = 0.0;
        $denominator = 0.0;
        $aggregatedCurve = [];

        $stepsCount = (int) round(($zMax - $zMin) / $step);
        for ($i = 0; $i <= $stepsCount; $i++) {
            $z = $zMin + ($i * $step);
            $muZ = 0.0;

            foreach ($consequentMaxAlpha as $setKey => $alpha) {
                if (isset($outputFunctions[$setKey])) {
                    $muSet = $outputFunctions[$setKey]->calculate((float) $z);
                    // Implikasi Mamdani: min(alpha, mu_set(z))
                    $implication = min($alpha, $muSet);
                    // Agregasi: max antar aturan/himpunan
                    if ($implication > $muZ) {
                        $muZ = $implication;
                    }
                }
            }

            $aggregatedCurve[(string) $z] = round($muZ, 4);

            $numerator += $z * $muZ;
            $denominator += $muZ;
        }

        // 5. Defuzzifikasi Centroid
        if ($denominator <= 0.0) {
            return [
                'status'                  => 'INSUFFICIENT_RULE_COVERAGE',
                'score'                   => null,
                'raw_score'               => null,
                'numerator'               => $numerator,
                'denominator'             => $denominator,
                'category'                => null,
                'input_snapshot'          => $inputs,
                'membership_snapshot'     => $membershipSnapshot,
                'rule_activation_snapshot' => $ruleEval,
                'activated_rules'         => $activatedRules,
                'consequent_alphas'       => $consequentMaxAlpha,
                'aggregated_curve'        => $aggregatedCurve,
            ];
        }

        $rawScore = $numerator / $denominator;
        $defuzzifiedScore = round($rawScore, 2);

        // 6. Penentuan Kategori Berdasarkan Skor Defuzzifikasi
        $category = $this->resolveCategory($defuzzifiedScore);

        return [
            'status'                  => 'AVAILABLE',
            'score'                   => $defuzzifiedScore,
            'raw_score'               => $rawScore,
            'numerator'               => $numerator,
            'denominator'             => $denominator,
            'category'                => $category,
            'input_snapshot'          => $inputs,
            'membership_snapshot'     => $membershipSnapshot,
            'rule_activation_snapshot' => $ruleEval,
            'activated_rules'         => $activatedRules,
            'consequent_alphas'       => $consequentMaxAlpha,
            'aggregated_curve'        => $aggregatedCurve,
        ];
    }

    /**
     * Tentukan kategori ('BAIK', 'PERLU_PERHATIAN', 'BURUK') berdasarkan skor defuzzifikasi.
     */
    public function resolveCategory(float $score): string
    {
        $categories = $this->config['categories'] ?? [];

        foreach ($categories as $catKey => $catData) {
            $min = (float) ($catData['min_score'] ?? 0.0);
            $max = (float) ($catData['max_score'] ?? 100.0);

            if ($score >= $min && $score <= $max) {
                return $catKey;
            }
        }

        // Fallback berdasarkan rentang
        if ($score >= 65.0) {
            return 'BAIK';
        } elseif ($score >= 40.0) {
            return 'PERLU_PERHATIAN';
        }

        return 'BURUK';
    }
}
