<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Pages\SeoFilesPage;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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

    public function test_a_large_generated_llms_txt_can_be_saved_unchanged(): void
    {
        // A generated index of a large site must not trip the editor's length limit.
        $large = '# Big'."\n\n".str_repeat("- [Page](https://site.test/page)\n", 3000);
        $this->assertGreaterThan(50000, strlen($large));

        Livewire::test(SeoFilesPage::class)
            ->callAction('editLlms', ['locale' => 'en', 'llms' => $large])
            ->assertHasNoActionErrors();

        $this->assertSame(rtrim($large)."\n", File::get(public_path('llms.txt')));
    }

    public function test_generating_llms_warns_that_editor_changes_are_replaced(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->assertActionExists('generateLlms', fn (Action $action): bool => $action->getModalDescription()
                === __('filament-seo-files::seo-files.actions.generate_llms_warning'))
            ->assertActionExists('editLlms', fn (Action $action): bool => $action->getModalDescription() === null);
    }

    public function test_with_the_schedule_on_both_llms_actions_name_the_nightly_overwrite(): void
    {
        config(['filament-seo-files.schedule.enabled' => true, 'filament-seo-files.schedule.times.llms' => '03:15']);
        $note = __('filament-seo-files::seo-files.actions.scheduled_overwrite', ['time' => '03:15']);

        Livewire::test(SeoFilesPage::class)
            ->assertActionExists('generateLlms', fn (Action $action): bool => str_ends_with((string) $action->getModalDescription(), ' '.$note))
            ->assertActionExists('editLlms', fn (Action $action): bool => $action->getModalDescription() === $note);
    }

    public function test_the_llms_editor_requires_text(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->callAction('editLlms', ['locale' => 'en', 'llms' => ''])
            ->assertHasActionErrors(['llms']);
    }

    /**
     * robots.txt is a static file: the page must not claim a template is served while it is
     * missing — nothing serves it.
     */
    public function test_a_missing_robots_file_is_reported_as_missing(): void
    {
        Livewire::test(SeoFilesPage::class)
            ->assertSee(__('filament-seo-files::seo-files.page.robots_missing'));
    }

    public function test_the_llms_section_shows_when_and_for_which_languages_it_was_generated(): void
    {
        $this->makePost('a', 'Alpha');
        $this->artisan('seo-files:llms')->assertExitCode(0);

        Livewire::test(SeoFilesPage::class)
            ->assertSee(__('filament-seo-files::seo-files.page.llms_languages', ['locales' => 'en, uk']));
    }

    /**
     * The reset control is a hint ACTION only — a plain hint with the same text drew it twice.
     */
    public function test_the_editors_show_one_reset_control(): void
    {
        foreach (['editRobots' => 'robots', 'editLlms' => 'llms'] as $action => $field) {
            $page = Livewire::test(SeoFilesPage::class)->mountAction($action)->instance();
            $schema = $page->getSchema((string) $page->getMountedActionSchemaName());
            $textarea = $schema?->getComponent(fn ($component): bool => $component instanceof Textarea && $component->getName() === $field);

            $this->assertInstanceOf(Textarea::class, $textarea, $action);
            $this->assertNull($textarea->getHint(), $action);
            $this->assertCount(1, $textarea->getHintActions(), $action);
        }
    }
}
