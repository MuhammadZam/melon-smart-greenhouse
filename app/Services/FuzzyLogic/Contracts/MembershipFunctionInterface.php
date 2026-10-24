<?php

namespace App\Services\FuzzyLogic\Contracts;

interface MembershipFunctionInterface
{
    /**
     * Hitung derajat keanggotaan (degree of membership) mu(x) dalam rentang [0.0, 1.0].
     *
     * @param float $x Nilai crisp input
     * @return float Derajat keanggotaan antara 0.0 hingga 1.0
     */
    public function calculate(float $x): float;

    /**
     * Ambil parameter batas kurva.
     *
     * @return array<float>
     */
    public function getParameters(): array;

    /**
     * Ambil nama tipe kurva (triangular/trapezoidal).
     *
     * @return string
     */
    public function getType(): string;
}
