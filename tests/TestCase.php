<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests;

use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\SeoFilesServiceProvider;
use Asignua\FilamentSeoFiles\Sources\ModelSource;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;
use Workbench\App\Providers\AdminPanelProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** A throw-away public/ so no test ever writes into the real one. */
    protected string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        SeoFiles::flush();

        $this->publicPath = sys_get_temp_dir().'/seo-files-public-'.uniqid();
        File::ensureDirectoryExists($this->publicPath);
        $this->app->usePublicPath($this->publicPath);

        Filament::setCurrentPanel('admin');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicPath);
        SeoFiles::flush();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            SeoFilesServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.url', 'https://site.test');
        $app['config']->set('app.name', 'Site');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');

        $migration = include __DIR__.'/../database/migrations/create_seo_sitemap_urls_table.php.stub';
        $migration->up();
    }

    /**
     * Two languages: `en` without a URL prefix, `uk` with one.
     */
    protected function twoLanguages(): void
    {
        SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: 'en');
    }

    protected function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * A published post. Titles per language decide in which languages it has a URL: the
     * Ukrainian version exists only when `title_uk` is set.
     *
     * @param array<string, mixed> $attributes
     */
    protected function makePost(string $slug, string $titleEn = 'A post', ?string $titleUk = null, array $attributes = []): Post
    {
        $post = new Post;
        $post->slug = $slug;
        $post->title_en = $titleEn;
        $post->title_uk = $titleUk;
        $post->published = true;

        foreach ($attributes as $key => $value) {
            $post->setAttribute($key, $value);
        }

        $post->save();

        return $post;
    }

    /**
     * Registers the published posts as a source: `/posts/{slug}`, in a language only when
     * the post has a title in it.
     *
     * @return ModelSource<Post>
     */
    protected function registerPosts(): ModelSource
    {
        $source = ModelSource::make(Post::class)
            ->query(fn ($query) => $query->where('published', true))
            ->url(fn (Post $post, string $locale): ?string => ($locale === 'uk' ? $post->title_uk : $post->title_en) !== null
                ? SeoFiles::localizedUrl($locale, 'posts/'.$post->slug)
                : null)
            ->title(fn (Post $post, string $locale): string => (string) ($locale === 'uk' ? $post->title_uk : $post->title_en))
            ->description(fn (Post $post, string $locale): ?string => $post->excerpt_en)
            ->body(fn (Post $post, string $locale): ?string => $locale === 'uk' ? $post->body_uk : $post->body_en);

        SeoFiles::source($source);

        return $source;
    }

    /**
     * @param array<string, string> $url
     */
    protected function manualUrl(array $url, bool $active = true): void
    {
        app(SitemapUrlRepository::class)->create(['url' => $url, 'active' => $active]);
    }

    protected function sitemapXml(): string
    {
        return (string) file_get_contents(public_path('sitemap.xml'));
    }
}
