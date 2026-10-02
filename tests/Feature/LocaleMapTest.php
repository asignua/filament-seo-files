<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\DB;

class LocaleMapTest extends TestCase
{
    public function test_it_writes_unescaped_unicode_and_slashes(): void
    {
        $this->twoLanguages();

        $row = app(SitemapUrlRepository::class)->create([
            'url' => ['en' => 'https://x.org/a/b', 'uk' => 'пошук'],
            'title' => ['uk' => 'Дякуємо'],
        ]);

        $raw = (string) DB::table('seo_sitemap_urls')->where('id', $row->id)->value('url');

        $this->assertSame('{"en":"https://x.org/a/b","uk":"пошук"}', $raw);
        $this->assertSame('{"en":"","uk":"Дякуємо"}', (string) DB::table('seo_sitemap_urls')->where('id', $row->id)->value('title'));
    }

    public function test_it_reads_both_escaped_and_unescaped_rows(): void
    {
        DB::table('seo_sitemap_urls')->insert([
            ['url' => '{"uk":"пошук"}', 'active' => true],
            ['url' => '{"uk":"пошук"}', 'active' => true],
        ]);

        $rows = SitemapUrl::query()->orderBy('id')->get();

        $this->assertSame(['uk' => 'пошук'], $rows[0]->url);
        $this->assertSame(['uk' => 'пошук'], $rows[1]->url);
    }

    public function test_garbage_reads_as_an_empty_map(): void
    {
        DB::table('seo_sitemap_urls')->insert([
            ['url' => 'not json', 'active' => true],
            ['url' => null, 'active' => true],
            ['url' => '"scalar"', 'active' => true],
        ]);

        foreach (SitemapUrl::query()->get() as $row) {
            $this->assertSame([], $row->url);
        }
    }

    public function test_an_unchanged_map_is_not_dirty(): void
    {
        $this->twoLanguages();
        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'a', 'uk' => 'б']]);

        $row = SitemapUrl::query()->findOrFail($row->id);
        $row->url = ['en' => 'a', 'uk' => 'б'];

        $this->assertFalse($row->isDirty('url'));
    }

    public function test_every_language_is_stored_with_empty_ones_as_empty_strings(): void
    {
        $this->twoLanguages();

        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => '/search/']]);

        $this->assertSame(['en' => 'search', 'uk' => ''], $row->url);
    }

    public function test_the_label_prefers_the_current_language_and_marks_a_borrowed_one(): void
    {
        $this->twoLanguages();
        $row = app(SitemapUrlRepository::class)->create([
            'url' => ['en' => 'search'],
            'title' => ['uk' => 'Пошук'],
        ]);

        app()->setLocale('uk');
        $this->assertSame('Пошук', $row->label());

        app()->setLocale('en');
        $this->assertSame('[uk] Пошук', $row->label());
    }

    public function test_the_label_falls_back_to_the_address_and_then_to_the_id(): void
    {
        $this->twoLanguages();
        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'search']]);

        $this->assertSame('search', $row->label());

        $empty = new SitemapUrl;
        $empty->id = 7;
        $this->assertSame('#7', $empty->label());
    }

    public function test_table_and_model_come_from_the_config(): void
    {
        config(['filament-seo-files.tables.sitemap_urls' => 'renamed']);

        $this->assertSame('renamed', (new SitemapUrl)->getTable());
    }

    public function test_the_model_has_no_mass_assignment(): void
    {
        $this->expectException(MassAssignmentException::class);

        (new SitemapUrl)->fill(['active' => false, 'url' => ['en' => 'x']]);
    }

    public function test_the_repository_resolves_a_replaced_model(): void
    {
        config(['filament-seo-files.models.sitemap_url' => CustomSitemapUrl::class]);
        $this->twoLanguages();

        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'x']]);

        $this->assertInstanceOf(CustomSitemapUrl::class, $row);
    }
}

class CustomSitemapUrl extends SitemapUrl {}
