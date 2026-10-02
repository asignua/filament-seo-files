<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages\CreateSitemapUrl;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages\EditSitemapUrl;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages\ListSitemapUrls;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Livewire\Livewire;

/**
 * The manual-URL form. The key thing here is that the value REACHES the database: the
 * model has `$guarded = ['*']` and the form state is nested (`url.en`), so without the
 * repository a submit would succeed and store nothing.
 */
class SitemapUrlResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
        $this->actingAs($this->admin());
    }

    public function test_create_stores_the_maps_and_normalises_the_path(): void
    {
        Livewire::test(CreateSitemapUrl::class)
            ->fillForm([
                'active' => true,
                'url' => ['en' => '/search/', 'uk' => 'poshuk'],
                'title' => ['uk' => 'Пошук по сайту'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $row = SitemapUrl::query()->firstOrFail();

        $this->assertSame(['en' => 'search', 'uk' => 'poshuk'], $row->url);
        $this->assertSame(['en' => '', 'uk' => 'Пошук по сайту'], $row->title);
        $this->assertTrue($row->active);
    }

    public function test_the_form_is_validated_in_the_documented_order(): void
    {
        SeoFiles::ownedPathUsing(fn (string $locale, string $path): bool => $path === 'about');

        $cases = [
            'a space wins over the language prefix' => ['en/two words', 'filament-seo-files::seo-files.validation.spaces'],
            'with a space' => ['two words', 'filament-seo-files::seo-files.validation.spaces'],
            'invalid absolute URL' => ['https://', 'filament-seo-files::seo-files.validation.invalid'],
            'non-http scheme' => ['ftp://x.org/a', 'filament-seo-files::seo-files.validation.scheme_or_path'],
            'protocol-relative' => ['//x.org/a', 'filament-seo-files::seo-files.validation.scheme_or_path'],
            'the root' => ['/', 'filament-seo-files::seo-files.validation.home'],
            'language prefix' => ['uk/katalog', 'filament-seo-files::seo-files.validation.language_prefix'],
            'owned path' => ['/about/', 'filament-seo-files::seo-files.validation.owned'],
        ];

        foreach ($cases as $label => [$value, $message]) {
            Livewire::test(CreateSitemapUrl::class)
                ->fillForm(['url' => ['en' => $value, 'uk' => '']])
                ->call('create')
                ->assertHasFormErrors(['url.en'])
                ->assertSee(__($message));

            $this->assertSame(0, SitemapUrl::query()->count(), $label);
        }
    }

    public function test_a_valid_absolute_address_and_a_plain_path_pass(): void
    {
        Livewire::test(CreateSitemapUrl::class)
            ->fillForm(['url' => ['en' => 'HTTPS://Partner.org/landing', 'uk' => 'katalog/nova']])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['en' => 'https://Partner.org/landing', 'uk' => 'katalog/nova'],
            SitemapUrl::query()->firstOrFail()->url,
        );
    }

    public function test_a_record_empty_in_every_language_is_rejected(): void
    {
        Livewire::test(CreateSitemapUrl::class)
            ->fillForm(['url' => ['en' => '', 'uk' => '']])
            ->call('create')
            ->assertHasFormErrors(['url.en']);

        $this->assertSame(0, SitemapUrl::query()->count());
    }

    public function test_one_filled_language_is_enough(): void
    {
        Livewire::test(CreateSitemapUrl::class)
            ->fillForm(['url' => ['en' => '', 'uk' => 'tilky-uk']])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, SitemapUrl::query()->count());
    }

    public function test_a_single_language_site_still_requires_the_address(): void
    {
        // "At least one language" rests on requiredWithoutAll, which Filament attaches only
        // when the list of other languages is non-empty — on a single-language site it
        // would silently not apply and an empty record would be saved.
        SeoFiles::flush();

        Livewire::test(CreateSitemapUrl::class)
            ->fillForm(['url' => ['en' => '']])
            ->call('create')
            ->assertHasFormErrors(['url.en']);

        $this->assertSame(0, SitemapUrl::query()->count());
    }

    public function test_edit_clears_a_language_instead_of_keeping_the_old_value(): void
    {
        // The stored map must say explicitly that the language is gone, otherwise the
        // sitemap would keep serving a version the editor removed.
        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'search', 'uk' => 'poshuk']]);

        Livewire::test(EditSitemapUrl::class, ['record' => $row->getKey()])
            ->fillForm(['url' => ['en' => 'search', 'uk' => '']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('{"en":"search","uk":""}', $row->fresh()?->getRawOriginal('url'));
    }

    public function test_edit_form_is_filled_from_the_record(): void
    {
        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'search', 'uk' => 'poshuk'], 'active' => false]);

        Livewire::test(EditSitemapUrl::class, ['record' => $row->getKey()])
            ->assertFormSet(['url.en' => 'search', 'url.uk' => 'poshuk', 'active' => false]);
    }

    public function test_the_table_lists_records_with_a_language_fallback_label(): void
    {
        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'search'], 'title' => ['uk' => 'Пошук']]);
        app(SitemapUrlRepository::class)->create(['url' => ['en' => 'archive'], 'active' => false]);

        Livewire::test(ListSitemapUrls::class)
            ->assertCanSeeTableRecords(SitemapUrl::query()->get())
            ->assertSee('[uk] Пошук')
            ->searchTable('poshuk-not-there')
            ->assertCountTableRecords(0)
            ->searchTable('search')
            ->assertCanSeeTableRecords([$row])
            ->assertCountTableRecords(1);
    }

    public function test_the_table_can_be_filtered_by_active(): void
    {
        $active = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'on']]);
        $inactive = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'off'], 'active' => false]);

        Livewire::test(ListSitemapUrls::class)
            ->filterTable('active', false)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_the_table_row_actions_sit_in_one_group(): void
    {
        $row = app(SitemapUrlRepository::class)->create(['url' => ['en' => 'on']]);

        Livewire::test(ListSitemapUrls::class)
            ->assertTableActionExists('edit', record: $row)
            ->assertTableActionExists('delete', record: $row);
    }
}
