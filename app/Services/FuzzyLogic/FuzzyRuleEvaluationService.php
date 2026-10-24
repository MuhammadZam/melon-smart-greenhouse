<?php

namespace App\Services\FuzzyLogic;

class FuzzyRuleEvaluationService
{
    protected array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? config('fuzzy.rules', []);
    }

    /**
     * Set rule base baru secara dinamis (untuk audit/pengujian).
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * Evaluasi semua aturan aktif terhadap snapshot derajat keanggotaan.
     *
     * @param array<string, array<string, float>> $membershipSnapshot
     * @return array{
     *     evaluations: array,
     *     activated_rules: array,
     *     max_firing_strength: float,
     *     active_count: int
     * }
     */
    public function evaluateRules(array $membershipSnapshot): array
    {
        $evaluations = [];
        $activatedRules = [];
        $maxFiringStrength = 0.0;

        foreach ($this->rules as $rule) {
            // Abaikan rule yang dinonaktifkan
            if (isset($rule['enabled']) && !$rule['enabled']) {
                continue;
            }

            $ruleId = $rule['id'] ?? 'unknown';
            $antecedents = $rule['antecedents'] ?? [];
            $operator = strtolower($rule['operator'] ?? 'and');
            $consequent = strtolower($rule['consequent'] ?? 'perlu_perhatian');
            $weight = (float) ($rule['weight'] ?? 1.0);

            $antecedentDegrees = [];
            foreach ($antecedents as $var => $set) {
                $degree = $membershipSnapshot[$var][$set] ?? 0.0;
                $antecedentDegrees["{$var}.{$set}"] = (float) $degree;
            }

            // Hitung Firing Strength (alpha)
            if (empty($antecedentDegrees)) {
                $rawAlpha = 0.0;
            } elseif ($operator === 'or') {
                $rawAlpha = max($antecedentDegrees);
            } else {
                // Default operator: AND (minimum)
                $rawAlpha = min($antecedentDegrees);
            }

            // Terapkan bobot rule (rule weight)
            $firingStrength = (float) ($rawAlpha * $weight);

            $evalResult = [
                'id'                 => $ruleId,
                'name'               => $rule['name'] ?? $ruleId,
                'operator'           => $operator,
                'antecedents'        => $antecedents,
                'antecedents_mu'     => $antecedentDegrees,
                'consequent'         => $consequent,
                'weight'             => $weight,
                'raw_alpha'          => (float) $rawAlpha,
                'firing_strength'    => $firingStrength,
                'conclusion'         => $rule['conclusion'] ?? null,
                'recommendation'     => $rule['recommendation'] ?? null,
            ];

            $evaluations[] = $evalResult;

            if ($firingStrength > 0.0) {
                $activatedRules[] = $evalResult;
                if ($firingStrength > $maxFiringStrength) {
                    $maxFiringStrength = $firingStrength;
                }
            }
        }

        // Urutkan activated rules berdasarkan firing strength tertinggi
        usort($activatedRules, fn($a, $b) => $b['firing_strength'] <=> $a['firing_strength']);

        return [
            'evaluations'         => $evaluations,
            'activated_rules'     => $activatedRules,
            'max_firing_strength' => $maxFiringStrength,
            'active_count'        => count($activatedRules),
        ];
    }
}
