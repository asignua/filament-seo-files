<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\SeoFilesServiceProvider;
use Asignua\FilamentSeoFiles\Sources\ModelSource;
use Asignua\FilamentSeoFiles\Support\GenerationInProgress;
use Asignua\FilamentSeoFiles\Support\LlmsPaths;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Asignua\FilamentSeoFiles\Support\PublicContext;
use Asignua\FilamentSeoFiles\Support\SitemapFile;
use Asignua\FilamentSeoFiles\Support\SitemapGenerator;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Filament\Facades\Filament;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Workbench\App\Models\Post;

class PublicGenerationTest extends TestCase
{
    public function test_generation_runs_without_the_panel_the_tenant_and_the_user(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $panel = Filament::getCurrentPanel();
        $this->assertNotNull($panel);

        $seen = PublicContext::run(fn (): array => [Filament::getCurrentPanel(), Auth::user()]);

        $this->assertSame([null, null], $seen);
        $this->assertSame($panel, Filament::getCurrentPanel());
        $this->assertTrue($admin->is(Auth::user()));
    }

    public function test_a_user_logged_in_through_the_session_stays_hidden(): void
    {
        $admin = $this->admin();
        Auth::login($admin);
        // A new guard instance has no user in memory: it can only find one in the session.
        Auth::forgetGuards();

        $seen = PublicContext::run(fn (): mixed => Auth::user());

        $this->assertNull($seen);
        $this->assertTrue($admin->is(Auth::user()));
    }

    public function test_the_context_is_restored_when_the_callback_throws(): void
    {
        $this->actingAs($admin = $this->admin());

        try {
            PublicContext::run(fn () => throw new \RuntimeException('boom'));
        } catch (\RuntimeException) {
        }

        $this->assertTrue($admin->is(Auth::user()));
        $this->assertNotNull(Filament::getCurrentPanel());
    }

    public function test_a_language_file_is_built_in_its_own_locale(): void
    {
        $this->twoLanguages();
        $this->makePost('a', 'A', 'А');
        $locales = [];

        SeoFiles::source(ModelSource::make(Post::class)
            ->url(fn (Post $post, string $locale): string => '/p/'.$post->slug)
            ->title(function (Post $post, string $locale) use (&$locales): string {
                $locales[] = app()->getLocale();

                return 'x';
            }));

        app()->setLocale('en');
        app(LlmsTxtFile::class)->template('uk');

        $this->assertSame(['uk'], $locales);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_title_follows_the_locale_of_the_file_for_a_translatable_model(): void
    {
        $this->twoLanguages();
        $record = new class extends Post
        {
            protected $table = 'posts';

            public function isTranslatableAttribute(string $key): bool
            {
                return $key === 'title';
            }

            public function getTranslation(string $key, string $locale, bool $fallback = true): string
            {
                return $key.'-'.$locale;
            }
        };
        $this->makePost('a', 'A', 'А');

        SeoFiles::source(ModelSource::make($record::class)->url(fn ($post): string => '/p/'.$post->slug));

        $links = iterator_to_array(SeoFiles::llmsIndexSources()[0]->llmsSections('uk'), false)[0]->links;

        $this->assertSame('title-uk', $links[0]->title);
    }

    public function test_a_second_run_is_refused_while_the_first_holds_the_lock(): void
    {
        $lock = Cache::lock('filament-seo-files:generate:sitemap', 60);
        $this->assertTrue($lock->get());

        try {
            $this->expectException(GenerationInProgress::class);

            app(SitemapGenerator::class)->generate();
        } finally {
            $lock->release();
        }
    }

    public function test_the_commands_fail_cleanly_while_a_run_is_in_progress(): void
    {
        $this->twoLanguages();
        $lock = Cache::lock('filament-seo-files:generate:llms', 60);
        $this->assertTrue($lock->get());

        try {
            $this->artisan('seo-files:llms')->assertFailed();
        } finally {
            $lock->release();
        }

        $this->artisan('seo-files:llms')->assertSuccessful();
    }

    public function test_the_lock_is_released_after_a_run(): void
    {
        app(SitemapGenerator::class)->generate();
        app(SitemapGenerator::class)->generate();

        $this->assertTrue(true);
    }

    public function test_generation_time_is_shown_in_the_app_timezone(): void
    {
        config(['app.timezone' => 'Europe/Kyiv']);
        app(SitemapGenerator::class)->generate();

        $generated = (new SitemapFile)->generatedAt();

        $this->assertSame('Europe/Kyiv', $generated?->getTimezone()->getName());
    }

    public function test_descriptions_are_decoded_to_plain_text(): void
    {
        $this->twoLanguages();
        $this->makePost('a', 'A', null, ['excerpt_en' => "<p>Grants&nbsp;for&nbsp;NGOs &amp; CSOs\u{00A0}today</p>"]);

        $links = iterator_to_array($this->registerPosts()->llmsSections('en'), false)[0]->links;

        $this->assertSame('Grants for NGOs & CSOs today', $links[0]->description);
    }

    public function test_a_locale_restricted_source_does_not_read_the_table_for_other_languages(): void
    {
        $this->twoLanguages();
        $this->makePost('a', 'A', 'А');
        $source = $this->registerPosts()->locales(['en']);
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->assertSame([], $source->llmsSections('uk'));
        $this->assertSame([], iterator_to_array($source->llmsDocuments('uk'), false));
        $this->assertSame(0, $queries);
    }

    private function sitemapSource(): SitemapSource
    {
        return new class implements SitemapSource
        {
            public function sitemapEntries(): iterable
            {
                return [];
            }
        };
    }

    public function test_the_source_registry_starts_empty_when_the_provider_registers_in_a_new_application(): void
    {
        SeoFiles::source($this->sitemapSource());
        $this->assertCount(1, SeoFiles::sitemapSources());

        (new SeoFilesServiceProvider(app()))->register();

        $this->assertSame([], SeoFiles::sitemapSources());
    }

    public function test_sources_survive_an_octane_style_cloned_sandbox_application(): void
    {
        $source = $this->sitemapSource();
        SeoFiles::source($source);

        $original = Container::getInstance();
        $sandbox = clone $original;
        Container::setInstance($sandbox);

        try {
            $this->assertSame([$source], SeoFiles::sitemapSources());

            // and a source registered later in the sandbox is still seen with the first one
            $late = $this->sitemapSource();
            SeoFiles::source($late);
            $this->assertSame([$source, $late], SeoFiles::sitemapSources());
        } finally {
            Container::setInstance($original);
        }
    }

    public function test_every_guard_is_empty_inside_a_run_and_restored_after(): void
    {
        config(['auth.guards.api' => ['driver' => 'session', 'provider' => 'users']]);
        $admin = $this->admin();
        $this->actingAs($admin);
        Auth::guard('api')->setUser($admin);
        $panelGuard = Filament::getCurrentPanel()?->getAuthGuard();
        $this->assertNotNull($panelGuard);

        $seen = PublicContext::run(fn (): array => [
            Filament::auth()->user(),
            auth($panelGuard)->user(),
            auth('api')->user(),
            auth()->user(),
            Auth::guard('web')->check(),
        ]);

        $this->assertSame([null, null, null, null, false], $seen);
        $this->assertTrue($admin->is(Filament::auth()->user()));
        $this->assertTrue($admin->is(auth('api')->user()));
        $this->assertTrue($admin->is(auth()->user()));
    }

    public function test_a_session_user_is_hidden_from_filament_auth_even_after_guards_are_forgotten(): void
    {
        $admin = $this->admin();
        Auth::guard(Filament::getCurrentPanel()?->getAuthGuard())->login($admin);
        Auth::forgetGuards();

        $seen = PublicContext::run(fn (): mixed => Filament::auth()->user());

        $this->assertNull($seen);
    }

    public function test_bcp47_locales_name_a_file_and_a_trailing_newline_does_not(): void
    {
        $this->assertStringEndsWith('es-419.txt', LlmsPaths::for('index', 'es-419'));
        $this->assertStringEndsWith('zh-Hant-TW-full.txt', LlmsPaths::for('full', 'zh-Hant-TW'));

        foreach (["uk\n", '../etc', 'uk/../x', 'a'] as $bad) {
            try {
                LlmsPaths::for('index', $bad);
                $this->fail("Accepted {$bad}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
