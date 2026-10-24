<?php

namespace App\Services\FuzzyLogic;

use App\Services\FuzzyLogic\Contracts\MembershipFunctionInterface;
use App\Services\FuzzyLogic\Membership\TrapezoidalMembershipFunction;
use App\Services\FuzzyLogic\Membership\TriangularMembershipFunction;
use InvalidArgumentException;

class FuzzyMembershipService
{
    /**
     * @var array<string, array<string, MembershipFunctionInterface>>
     */
    protected array $inputFunctions = [];

    /**
     * @var array<string, MembershipFunctionInterface>
     */
    protected array $outputFunctions = [];

    public function __construct(?array $config = null)
    {
        $config = $config ?? config('fuzzy');
        $this->loadFunctions($config);
    }

    /**
     * Inisialisasi kurva keanggotaan dari konfigurasi.
     */
    protected function loadFunctions(array $config): void
    {
        // Load Input Functions
        $inputs = $config['inputs'] ?? [];
        foreach ($inputs as $varKey => $varData) {
            $this->inputFunctions[$varKey] = [];
            foreach ($varData['sets'] ?? [] as $setKey => $setData) {
                $this->inputFunctions[$varKey][$setKey] = $this->createFunctionInstance($setData);
            }
        }

        // Load Output Functions
        $outputSets = $config['output']['sets'] ?? [];
        foreach ($outputSets as $setKey => $setData) {
            $this->outputFunctions[$setKey] = $this->createFunctionInstance($setData);
        }
    }

    /**
     * Buat instance MembershipFunctionInterface berdasarkan tipe kurva.
     */
    public function createFunctionInstance(array $setData): MembershipFunctionInterface
    {
        $type = strtolower($setData['type'] ?? '');
        $params = $setData['params'] ?? [];

        return match ($type) {
            'triangular' => new TriangularMembershipFunction(
                (float) ($params[0] ?? 0),
                (float) ($params[1] ?? 0),
                (float) ($params[2] ?? 0)
            ),
            'trapezoidal' => new TrapezoidalMembershipFunction(
                (float) ($params[0] ?? 0),
                (float) ($params[1] ?? 0),
                (float) ($params[2] ?? 0),
                (float) ($params[3] ?? 0)
            ),
            default => throw new InvalidArgumentException("Tipe fungsi keanggotaan fuzzy tidak didukung: {$type}"),
        };
    }

    /**
     * Jalankan proses Fuzzifikasi untuk seluruh variabel input yang diberikan.
     *
     * @param array<string, float> $crispInputs [varKey => floatValue]
     * @return array<string, array<string, float>> Snapshot derajat keanggotaan per himpunan
     */
    public function fuzzify(array $crispInputs): array
    {
        $result = [];

        foreach ($this->inputFunctions as $varKey => $sets) {
            $result[$varKey] = [];
            $value = $crispInputs[$varKey] ?? null;

            if ($value === null || !is_numeric($value)) {
                foreach ($sets as $setKey => $fn) {
                    $result[$varKey][$setKey] = 0.0;
                }
                continue;
            }

            $floatVal = (float) $value;
            foreach ($sets as $setKey => $fn) {
                $mu = $fn->calculate($floatVal);
                $result[$varKey][$setKey] = (float) $mu;
            }
        }

        return $result;
    }

    /**
     * Ambil derajat keanggotaan untuk variabel input dan himpunan tertentu.
     */
    public function getMembershipDegree(string $variable, string $set, float $crispVal): float
    {
        if (!isset($this->inputFunctions[$variable][$set])) {
            throw new InvalidArgumentException("Himpunan fuzzy input tidak ditemukan: {$variable}.{$set}");
        }

        return $this->inputFunctions[$variable][$set]->calculate($crispVal);
    }

    /**
     * Ambil seluruh fungsi keanggotaan output.
     *
     * @return array<string, MembershipFunctionInterface>
     */
    public function getOutputFunctions(): array
    {
        return $this->outputFunctions;
    }

    /**
     * Ambil fungsi keanggotaan output tertentu.
     */
    public function getOutputFunction(string $set): MembershipFunctionInterface
    {
        if (!isset($this->outputFunctions[$set])) {
            throw new InvalidArgumentException("Himpunan fuzzy output tidak ditemukan: {$set}");
        }

        return $this->outputFunctions[$set];
    }
}
