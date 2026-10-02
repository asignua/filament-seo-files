<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * llms.txt of a prefixed language is served by a ROUTE, not a static file: a real
 * `public/{locale}/llms.txt` would shadow the language's home page.
 */
class LlmsRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
        $this->registerPosts();
        $this->makePost('a', 'Alpha', 'Альфа', ['body_uk' => '<p>Текст</p>']);
        SeoFiles::routes();
    }

    public function test_a_prefixed_language_llms_txt_is_served_as_plain_text(): void
    {
        $response = $this->get('/uk/llms.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        // Even before the first generation the route serves the document (built once).
        $response->assertSee('Альфа', escape: false);
    }

    public function test_the_full_file_is_served_too(): void
    {
        $response = $this->get('/uk/llms-full.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Текст', escape: false);
    }

    public function test_a_missing_file_is_built_once_and_stored(): void
    {
        // Building walks every source (the whole table for a ModelSource): an anonymous
        // request must not be able to make the server do that over and over.
        $this->get('/uk/llms-full.txt')->assertOk()->assertSee('Текст', escape: false);
        $this->assertFileExists(public_path('.llms/uk-full.txt'));

        // A record added afterwards does not show up until the next generation: the
        // second request read the stored file instead of rebuilding the document.
        $this->makePost('b', 'Beta', 'Бета', ['body_uk' => '<p>Новий</p>']);

        $this->get('/uk/llms-full.txt')->assertOk()->assertDontSee('Новий', escape: false);

        $this->get('/uk/llms.txt')->assertOk();
        $this->assertFileExists(public_path('.llms/uk.txt'));
    }

    public function test_a_file_being_built_elsewhere_answers_503_at_once(): void
    {
        // Another request holds the build lock: this one must not wait for it (a burst
        // would tie up the PHP worker pool) but answer 503 right away.
        $path = app(LlmsTxtFile::class)->path('uk');
        $lock = Cache::lock('filament-seo-files:'.md5($path), 60);
        $this->assertTrue($lock->get());

        $started = microtime(true);

        try {
            $this->get('/uk/llms.txt')->assertStatus(503)->assertHeader('Retry-After', '30');
        } finally {
            $lock->release();
        }

        $this->assertLessThan(2.0, microtime(true) - $started);
        $this->assertFileDoesNotExist($path);

        // Once the lock is free the file is built as usual.
        $this->get('/uk/llms.txt')->assertOk();
    }

    public function test_the_routes_start_no_session(): void
    {
        // A plain text file needs no session or cookies, and a cookie keeps a CDN from
        // caching the response.
        $this->get('/uk/llms.txt')->assertOk()->assertCookieMissing(config('session.cookie'));
    }

    public function test_a_stored_file_wins_over_the_template(): void
    {
        (new LlmsTxtFile)->write('uk', '# Hand made');

        $this->get('/uk/llms.txt')->assertOk()->assertSee('# Hand made');
    }

    public function test_the_unprefixed_language_has_no_route(): void
    {
        // Its file lives in the web root and is served by the web server.
        $this->get('/en/llms.txt')->assertNotFound();
    }

    public function test_an_unknown_language_has_no_route(): void
    {
        $this->get('/de/llms.txt')->assertNotFound();
    }

    public function test_routes_are_registered_once(): void
    {
        SeoFiles::routes();
        SeoFiles::routes();

        $matching = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route): bool => $route->uri() === '{locale}/llms.txt');

        $this->assertCount(1, $matching);
    }

    public function test_a_single_language_site_registers_nothing(): void
    {
        SeoFiles::flush();
        $before = count(app('router')->getRoutes()->getRoutes());

        SeoFiles::routes();

        $this->assertCount($before, app('router')->getRoutes()->getRoutes());
    }
}
