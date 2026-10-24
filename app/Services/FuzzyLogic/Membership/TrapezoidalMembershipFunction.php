<?php

namespace App\Services\FuzzyLogic\Membership;

use App\Services\FuzzyLogic\Contracts\MembershipFunctionInterface;
use InvalidArgumentException;

/**
 * Fungsi Keanggotaan Trapesium (Trapezoidal Membership Function).
 * Parameter: [a, b, c, d] di mana a <= b <= c <= d
 * Mendukung left-shoulder (a == b) dan right-shoulder (c == d).
 */
class TrapezoidalMembershipFunction implements MembershipFunctionInterface
{
    protected float $a;
    protected float $b;
    protected float $c;
    protected float $d;

    public function __construct(float $a, float $b, float $c, float $d)
    {
        if ($a > $b || $b > $c || $c > $d || $a == $d) {
            throw new InvalidArgumentException("Parameter kurva trapesium tidak valid: harus a <= b <= c <= d dan a < d. Diterima: a={$a}, b={$b}, c={$c}, d={$d}");
        }

        if ($a == $b && $c == $d) {
            throw new InvalidArgumentException("Parameter kurva trapesium tidak valid: tidak boleh left-shoulder dan right-shoulder sekaligus (a == b dan c == d). Diterima: a={$a}, b={$b}, c={$c}, d={$d}");
        }

        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
    }

    public function calculate(float $x): float
    {
        // Kasus left-shoulder: a == b, berlaku 1.0 untuk semua x <= b
        if ($this->a == $this->b && $x <= $this->b) {
            return 1.0;
        }

        // Kasus right-shoulder: c == d, berlaku 1.0 untuk semua x >= c
        if ($this->c == $this->d && $x >= $this->c) {
            return 1.0;
        }

        // Di luar batas trapesium
        if ($x <= $this->a || $x >= $this->d) {
            return 0.0;
        }

        // Lereng naik (a < x < b)
        if ($x > $this->a && $x < $this->b) {
            $val = ($x - $this->a) / ($this->b - $this->a);
            return max(0.0, min(1.0, $val));
        }

        // Bagian datar atas (b <= x <= c)
        if ($x >= $this->b && $x <= $this->c) {
            return 1.0;
        }

        // Lereng turun (c < x < d)
        if ($x > $this->c && $x < $this->d) {
            $val = ($this->d - $x) / ($this->d - $this->c);
            return max(0.0, min(1.0, $val));
        }

        return 0.0;
    }

    public function getParameters(): array
    {
        return [$this->a, $this->b, $this->c, $this->d];
    }

    public function getType(): string
    {
        return 'trapezoidal';
    }
}
