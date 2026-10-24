<?php

namespace Tests;

use App\Models\User;

/** Explicit authorized client fixture for legacy positive API contract tests. */
abstract class ApiTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('role', 'user')->first());
        $this->authenticateDevice();
    }
}
