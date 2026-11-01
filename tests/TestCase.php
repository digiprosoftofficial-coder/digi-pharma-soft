<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! TestResponse::hasMacro('assertAppDialogRedirect')) {
            TestResponse::macro('assertAppDialogRedirect', function (?string $message = null) {
                $this->assertRedirect();
                $this->assertSessionHas('app_dialog');

                if ($message !== null) {
                    \PHPUnit\Framework\Assert::assertSame(
                        $message,
                        session('app_dialog')['message'] ?? null,
                    );
                }

                return $this;
            });
        }
    }
}
