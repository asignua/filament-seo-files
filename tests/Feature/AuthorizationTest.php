<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Pages\SeoFilesPage;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\SitemapUrlResource;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_everyone_in_the_panel_is_allowed_by_default(): void
    {
        $this->assertTrue(SeoFilesPlugin::allows());
        $this->assertTrue(SeoFilesPage::canAccess());
        $this->assertTrue(SitemapUrlResource::canAccess());
    }

    public function test_the_gate_decides_when_it_is_defined(): void
    {
        Gate::define(SeoFilesPlugin::GATE, fn (): bool => false);

        $this->assertFalse(SeoFilesPlugin::allows());
        $this->assertFalse(SeoFilesPage::canAccess());
        $this->assertFalse(SitemapUrlResource::canAccess());

        Gate::define(SeoFilesPlugin::GATE, fn (): bool => true);

        $this->assertTrue(SeoFilesPage::canAccess());
    }

    public function test_an_authorize_closure_wins_over_the_gate(): void
    {
        Gate::define(SeoFilesPlugin::GATE, fn (): bool => true);
        SeoFilesPlugin::get()->authorize(fn (): bool => false);

        $this->assertFalse(SeoFilesPage::canAccess());
        $this->assertFalse(SitemapUrlResource::canAccess());
    }

    public function test_an_unauthorised_user_cannot_open_the_page_or_the_resource(): void
    {
        SeoFilesPlugin::get()->authorize(fn (): bool => false);

        $this->get('/admin/seo-files')->assertForbidden();
        $this->get('/admin/sitemap-urls')->assertForbidden();
    }

    public function test_an_authorised_user_can(): void
    {
        $this->get('/admin/seo-files')->assertOk();
        $this->get('/admin/sitemap-urls')->assertOk();
    }

    public function test_the_closure_can_look_at_the_user(): void
    {
        SeoFilesPlugin::get()->authorize(fn (): bool => auth()->user()?->email === 'allowed@example.com');

        $this->assertFalse(SeoFilesPage::canAccess());

        $this->actingAs($this->admin()->forceFill(['email' => 'allowed@example.com']));

        $this->assertTrue(SeoFilesPage::canAccess());
    }

    public function test_the_page_and_the_resource_can_be_left_out_of_a_panel(): void
    {
        $panel = Panel::make()->id('plain')->plugin(SeoFilesPlugin::make()->page(false)->resource(false));

        $this->assertNotContains(SeoFilesPage::class, $panel->getPages());
        $this->assertNotContains(SitemapUrlResource::class, $panel->getResources());

        $full = Filament::getPanel('admin');
        $this->assertContains(SeoFilesPage::class, $full->getPages());
        $this->assertContains(SitemapUrlResource::class, $full->getResources());
    }

    public function test_navigation_comes_from_the_plugin(): void
    {
        SeoFilesPlugin::get()->navigationGroup('SEO')->navigationSort(9);

        $this->assertSame('SEO', SeoFilesPage::getNavigationGroup());
        $this->assertSame('SEO', SitemapUrlResource::getNavigationGroup());
        $this->assertSame(9, SeoFilesPage::getNavigationSort());
        $this->assertSame(9, SitemapUrlResource::getNavigationSort());
    }
}
