<?php

namespace App\Services\FuzzyLogic\Membership;

use App\Services\FuzzyLogic\Contracts\MembershipFunctionInterface;
use InvalidArgumentException;

/**
 * Fungsi Keanggotaan Segitiga (Triangular Membership Function).
 * Parameter: [a, b, c] di mana a <= b <= c
 */
class TriangularMembershipFunction implements MembershipFunctionInterface
{
    protected float $a;
    protected float $b;
    protected float $c;

    public function __construct(float $a, float $b, float $c)
    {
        if ($a > $b || $b > $c || $a == $c) {
            throw new InvalidArgumentException("Parameter kurva segitiga tidak valid: harus a <= b <= c dan a < c. Diterima: a={$a}, b={$b}, c={$c}");
        }

        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }

    public function calculate(float $x): float
    {
        // Di luar batas segitiga
        if ($x <= $this->a || $x >= $this->c) {
            // Khusus kasus titik puncak di tepi batas (misal a == b atau b == c)
            if ($x === $this->b) {
                return 1.0;
            }
            return 0.0;
        }

        // Tepat di titik puncak
        if ($x == $this->b) {
            return 1.0;
        }

        // Sisi lereng naik (a < x < b)
        if ($x > $this->a && $x < $this->b) {
            if ($this->b == $this->a) {
                return 1.0;
            }
            $val = ($x - $this->a) / ($this->b - $this->a);
            return max(0.0, min(1.0, $val));
        }

        // Sisi lereng turun (b < x < c)
        if ($x > $this->b && $x < $this->c) {
            if ($this->c == $this->b) {
                return 1.0;
            }
            $val = ($this->c - $x) / ($this->c - $this->b);
            return max(0.0, min(1.0, $val));
        }

        return 0.0;
    }

    public function getParameters(): array
    {
        return [$this->a, $this->b, $this->c];
    }

    public function getType(): string
    {
        return 'triangular';
    }
}
