<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;

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
        // Even before the first generation the route serves a freshly built template.
        $response->assertSee('Альфа', escape: false);
    }

    public function test_the_full_file_is_served_too(): void
    {
        $response = $this->get('/uk/llms-full.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Текст', escape: false);
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
