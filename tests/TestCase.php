<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // pgsql_app (runtime role) is intentionally not wrapped in the
        // RefreshDatabase transaction, so its committed rows are cleaned here,
        // after RefreshDatabase's rollback callback has run.
        $this->beforeApplicationDestroyed(function () {
            try {
                DB::select('TRUNCATE books, borrowings, users RESTART IDENTITY CASCADE');
            } catch (\Throwable) {
                // DB may be unavailable late in shutdown; nothing to clean then.
            }
        });

        parent::tearDown();
    }
}
