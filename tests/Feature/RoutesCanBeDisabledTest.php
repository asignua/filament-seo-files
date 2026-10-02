<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Tests\TestCase;

class RoutesCanBeDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filament-seo-files.routes.register', false);
        $app->booting(function (): void {
            SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: 'en');
        });
    }

    public function test_nothing_is_registered_until_the_site_asks_for_it(): void
    {
        $this->twoLanguages();

        $this->get('/uk/llms.txt')->assertNotFound();

        SeoFiles::routes();

        $this->get('/uk/llms.txt')->assertOk();
    }
}
