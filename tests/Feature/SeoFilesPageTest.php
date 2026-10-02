<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Pages\SeoFilesPage;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

class SeoFilesPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
        $this->registerPosts();
        $this->actingAs($this->admin());
    }

    public function test_the_page_renders_with_the_four_actions(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->assertOk()
            ->assertSee('sitemap.xml')
            ->assertSee('robots.txt')
            ->assertSee('llms.txt')
            ->assertSee(__('filament-seo-files::seo-files.page.not_generated'))
            ->assertActionExists('generateSitemap')
            ->assertActionExists('editRobots')
            ->assertActionExists('generateLlms')
            ->assertActionExists('editLlms');
    }

    public function test_generate_sitemap_runs_the_command(): void
    {
        $this->makePost('a', 'Alpha');

        Livewire::test(SeoFilesPage::class)
            ->callAction('generateSitemap')
            ->assertNotified(__('filament-seo-files::seo-files.actions.finished'));

        $this->assertStringContainsString('/posts/a', $this->sitemapXml());
    }

    public function test_the_page_shows_the_url_and_part_counts(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 1]);
        $this->makePost('a', 'Alpha');
        $this->makePost('b', 'Beta');
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        Livewire::test(SeoFilesPage::class)
            ->assertSee(__('filament-seo-files::seo-files.page.url_count', ['count' => 2]))
            ->assertSee(__('filament-seo-files::seo-files.page.part_count', ['count' => 2]));
    }

    public function test_generate_llms_writes_both_files(): void
    {
        $this->makePost('a', 'Alpha', 'Альфа');

        Livewire::test(SeoFilesPage::class)
            ->callAction('generateLlms')
            ->assertNotified(__('filament-seo-files::seo-files.actions.finished'));

        $this->assertFileExists(public_path('llms.txt'));
        $this->assertFileExists(public_path('llms-full.txt'));
        $this->assertFileExists(public_path('.llms/uk.txt'));
    }

    public function test_the_robots_editor_starts_from_the_template_and_saves(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->mountAction('editRobots')
            ->assertSchemaStateSet(['robots' => (new \Asignua\FilamentSeoFiles\Support\RobotsFile)->template()])
            ->callMountedAction()
            ->assertNotified(__('filament-seo-files::seo-files.actions.saved'));

        Livewire::test(SeoFilesPage::class)
            ->callAction('editRobots', ['robots' => "User-agent: *\nDisallow: /private"])
            ->assertNotified();

        $this->assertSame("User-agent: *\nDisallow: /private\n", File::get(public_path('robots.txt')));
    }

    public function test_the_llms_editor_saves_the_chosen_language(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->callAction('editLlms', ['locale' => 'uk', 'llms' => '# Hand made uk'])
            ->assertNotified();

        $this->assertSame("# Hand made uk\n", File::get(public_path('.llms/uk.txt')));
        $this->assertFileDoesNotExist(public_path('llms.txt'));
    }

    public function test_the_llms_editor_requires_text(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->callAction('editLlms', ['locale' => 'en', 'llms' => ''])
            ->assertHasActionErrors(['llms']);
    }
}
