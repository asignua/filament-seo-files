<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\ServiceProvider;

class ConfigurationTest extends TestCase
{
    public function test_the_published_tags_exist(): void
    {
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'filament-seo-files-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'filament-seo-files-migrations'));
    }

    public function test_the_migration_is_a_stub_so_that_the_host_owns_its_timestamp(): void
    {
        $paths = array_keys(ServiceProvider::pathsToPublish(null, 'filament-seo-files-migrations'));

        $this->assertNotEmpty($paths);
        $this->assertStringEndsWith('create_seo_sitemap_urls_table.php.stub', $paths[0]);
    }

    public function test_the_config_defaults(): void
    {
        $this->assertSame('seo_sitemap_urls', config('filament-seo-files.tables.sitemap_urls'));
        $this->assertSame(50000, config('filament-seo-files.sitemap.max_urls'));
        $this->assertSame('auto', config('filament-seo-files.sitemap.split'));
        $this->assertSame('sitemap-{n}.xml', config('filament-seo-files.sitemap.chunk_name'));
        $this->assertTrue(config('filament-seo-files.routes.register'));
        $this->assertFalse(config('filament-seo-files.schedule.enabled'));
        $this->assertSame(200, config('filament-seo-files.llms.description_limit'));
    }

    public function test_every_config_key_is_commented(): void
    {
        $lines = explode("\n", (string) file_get_contents(dirname(__DIR__, 2).'/config/filament-seo-files.php'));

        foreach (['tables', 'models', 'sitemap', 'robots', 'llms', 'routes', 'schedule', 'max_urls', 'split', 'chunk_name', 'max_bytes', 'description_limit', 'register', 'enabled', 'times', 'path', 'full_path', 'directory'] as $key) {
            $index = null;

            foreach ($lines as $number => $line) {
                if (preg_match("~^\\s*'{$key}' =>~", $line) === 1) {
                    $index = $number;

                    break;
                }
            }

            $this->assertNotNull($index, "{$key} is not in the config");

            $previous = trim($lines[$index - 1]);

            $this->assertTrue(
                str_starts_with($previous, '//') || str_ends_with($previous, '*/'),
                "{$key} has no comment above it",
            );
        }
    }
}
