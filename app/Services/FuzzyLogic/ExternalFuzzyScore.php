<?php

namespace App\Services\FuzzyLogic;

use Illuminate\Validation\ValidationException;

class ExternalFuzzyScore
{
    public static function normalize(mixed $value): ?float
    {
        if ($value === null) return null;
        if ((!is_int($value) && !is_float($value) && !is_string($value)) || !is_numeric($value)) {
            throw ValidationException::withMessages(['fuzzy_score' => 'Skor fuzzy harus numerik terbatas pada 0–100.']);
        }
        $score = (float) $value;
        if (!is_finite($score) || $score < 0 || $score > 100) {
            throw ValidationException::withMessages(['fuzzy_score' => 'Skor fuzzy harus bernilai finite dalam rentang 0–100.']);
        }

        return round($score, 2);
    }
}
