<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Tests\TestCase;

/**
 * The service provider registers the language routes by itself, AFTER the application has
 * booted — so the languages configured in an AppServiceProvider (which boots after the
 * package) are the ones that count.
 */
class AutoRegisteredRoutesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->booting(function (): void {
            SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: 'en');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The base class flushes the registry after boot; the routes are already there.
        $this->twoLanguages();
    }

    public function test_the_routes_exist_without_calling_the_registry(): void
    {
        $this->get('/uk/llms.txt')->assertOk();
        $this->get('/uk/llms-full.txt')->assertOk();
    }
}
