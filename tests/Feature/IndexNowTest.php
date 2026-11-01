<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Jobs\PingIndexNow;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\IndexNow;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class IndexNowTest extends TestCase
{
    private const string KEY = 'abcdef0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        File::delete(IndexNow::statePath());
    }

    protected function tearDown(): void
    {
        File::delete(IndexNow::statePath());

        parent::tearDown();
    }

    private function enable(array $extra = []): void
    {
        config(['filament-seo-files.indexnow' => [...config('filament-seo-files.indexnow'), 'enabled' => true, 'key' => self::KEY, ...$extra]]);
    }

    public function test_off_by_default_nothing_is_dispatched_or_written(): void
    {
        Bus::fake();
        $this->makePost('a');
        $this->registerPosts();
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        Bus::assertNothingDispatched();
        $this->assertFileDoesNotExist(IndexNow::statePath());
    }

    public function test_key_file_is_served_by_the_route_only_when_enabled(): void
    {
        $this->enable();
        SeoFiles::routes();

        $this->get('/'.self::KEY.'.txt')->assertOk()->assertSee(self::KEY, false);
    }

    public function test_an_invalid_key_registers_no_route(): void
    {
        $this->enable(['key' => 'short']);
        SeoFiles::routes();

        $this->get('/short.txt')->assertNotFound();
        $this->artisan('seo-files:indexnow')->assertExitCode(1);
    }

    public function test_first_run_records_a_baseline_then_only_changes_are_pinged(): void
    {
        $this->enable();
        Bus::fake();
        $post = $this->makePost('a');
        $this->makePost('b');
        $this->registerPosts();

        $this->artisan('seo-files:sitemap');
        Bus::assertNothingDispatched();
        $this->assertCount(2, IndexNow::readState());

        // A new page and an edited one.
        $this->makePost('c');
        $post->updated_at = now()->addDay();
        $post->saveQuietly();
        Cache::flush();

        $this->artisan('seo-files:sitemap');

        Bus::assertDispatched(PingIndexNow::class, function (PingIndexNow $job): bool {
            sort($job->urls);

            return $job->urls === ['https://site.test/posts/a', 'https://site.test/posts/c'];
        });
    }

    public function test_throttle_defers_changes_to_a_later_run(): void
    {
        $this->enable(['throttle_seconds' => 3600]);
        Bus::fake();
        $this->makePost('a');
        $this->registerPosts();
        $this->artisan('seo-files:sitemap'); // baseline

        $this->makePost('b');
        $this->artisan('seo-files:sitemap');
        Bus::assertDispatchedTimes(PingIndexNow::class, 1);

        $this->makePost('c');
        $this->artisan('seo-files:sitemap');
        Bus::assertDispatchedTimes(PingIndexNow::class, 1); // throttled

        Cache::flush();
        $this->artisan('seo-files:sitemap');
        Bus::assertDispatchedTimes(PingIndexNow::class, 2); // c was not lost
    }

    public function test_the_job_posts_the_protocol_payload_and_remembers_the_urls(): void
    {
        $this->enable();
        Http::fake(['*' => Http::response('', 200)]);

        (new PingIndexNow(['https://site.test/x'], ['https://site.test/x' => '5']))->handle();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['host'] === 'site.test'
            && $request['key'] === self::KEY
            && $request['keyLocation'] === 'https://site.test/'.self::KEY.'.txt'
            && $request['urlList'] === ['https://site.test/x']);
        $this->assertSame(['https://site.test/x' => '5'], IndexNow::readState());
    }

    public function test_a_rate_limit_is_retried_and_a_bad_key_fails_without_recording(): void
    {
        $this->enable();
        Http::fake(['*' => Http::response('', 429)]);

        try {
            (new PingIndexNow(['https://site.test/x'], ['https://site.test/x' => '1']))->handle();
            $this->fail('429 must throw so the queue retries');
        } catch (\RuntimeException) {
            $this->assertNull(IndexNow::readState());
        }
    }

    public function test_on_demand_command_sends_only_urls_of_this_host(): void
    {
        $this->enable();
        Http::fake(['*' => Http::response('', 202)]);

        $this->artisan('seo-files:indexnow', ['urls' => ['https://site.test/a', 'https://evil.test/b'], '--now' => true])
            ->expectsOutputToContain('1 URL(s) sent')
            ->assertExitCode(0);

        Http::assertSent(fn (Request $request): bool => $request['urlList'] === ['https://site.test/a']);
    }
}
