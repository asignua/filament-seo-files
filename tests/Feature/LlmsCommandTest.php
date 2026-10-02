<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Facades\File;

class LlmsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
        $this->registerPosts();
        $this->makePost('a', 'Alpha', 'Альфа', ['body_en' => '<p>Text</p>', 'body_uk' => '<p>Текст</p>']);
    }

    public function test_it_writes_both_files_for_every_language(): void
    {
        $this->artisan('seo-files:llms')
            ->expectsOutputToContain('llms.txt: en → public/llms.txt')
            ->expectsOutputToContain('llms-full.txt: uk → public/.llms/uk-full.txt')
            ->assertExitCode(0);

        $this->assertStringContainsString('[Alpha](https://site.test/posts/a)', File::get(public_path('llms.txt')));
        $this->assertStringContainsString('[Альфа](https://site.test/uk/posts/a)', File::get(public_path('.llms/uk.txt')));
        $this->assertStringContainsString('Source: https://site.test/posts/a', File::get(public_path('llms-full.txt')));
        $this->assertStringContainsString('Текст', File::get(public_path('.llms/uk-full.txt')));
    }

    public function test_the_locale_option_limits_generation(): void
    {
        $this->artisan('seo-files:llms', ['--locale' => ['uk']])->assertExitCode(0);

        $this->assertFileExists(public_path('.llms/uk.txt'));
        $this->assertFileDoesNotExist(public_path('llms.txt'));
    }

    public function test_an_unknown_language_is_skipped_without_failing(): void
    {
        $this->artisan('seo-files:llms', ['--locale' => ['xx', 'en']])
            ->expectsOutputToContain('Unknown language: xx')
            ->assertExitCode(0);

        $this->assertFileExists(public_path('llms.txt'));
    }

    public function test_generation_overwrites_manual_edits(): void
    {
        File::put(public_path('llms.txt'), 'edited by hand');

        $this->artisan('seo-files:llms')->assertExitCode(0);

        $this->assertStringNotContainsString('edited by hand', File::get(public_path('llms.txt')));
    }

    public function test_it_counts_links_and_entries(): void
    {
        $this->artisan('seo-files:llms', ['--locale' => ['en']])
            ->expectsOutputToContain('(2 links)')
            ->expectsOutputToContain('(1 entries)')
            ->assertExitCode(0);
    }
}
