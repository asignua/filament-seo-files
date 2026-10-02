<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Actions\EditLlmsAction;
use Asignua\FilamentSeoFiles\Actions\EditRobotsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateLlmsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateSitemapAction;
use Asignua\FilamentSeoFiles\Pages\SeoFilesPage;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\SitemapUrlResource;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Tests\Fixtures\ToolsPage;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());

        // The workbench panel opts in with ->authorize(true); these tests start from the
        // plugin's own default.
        SeoFilesPlugin::get()->authorize(null);
    }

    public function test_nobody_is_allowed_by_default(): void
    {
        // Fail closed: the plugin writes robots.txt and llms files into the web root, so a
        // panel with several roles must not hand that to its lowest-privileged editor.
        $this->assertFalse(SeoFilesPlugin::allows());
        $this->assertFalse(SeoFilesPage::canAccess());
        $this->assertFalse(SitemapUrlResource::canAccess());
        $this->assertFalse((new SeoFilesPlugin)->isAllowed());
    }

    public function test_authorize_true_allows_everyone_in_the_panel(): void
    {
        SeoFilesPlugin::get()->authorize(true);

        $this->assertTrue(SeoFilesPage::canAccess());
        $this->assertTrue(SitemapUrlResource::canAccess());
    }

    public function test_every_public_action_carries_the_plugin_policy(): void
    {
        $actions = [
            GenerateSitemapAction::make(),
            EditRobotsAction::make(),
            GenerateLlmsAction::make(),
            EditLlmsAction::make(),
        ];

        foreach ($actions as $action) {
            $this->assertFalse($action->isAuthorized(), $action->getName());
        }

        SeoFilesPlugin::get()->authorize(true);

        foreach ($actions as $action) {
            $this->assertTrue($action->isAuthorized(), $action->getName());
        }
    }

    public function test_an_embedded_action_refuses_an_unauthorised_user(): void
    {
        // A host page anyone can open embeds EditRobotsAction; the plugin's policy says no.
        File::put(public_path('robots.txt'), "User-agent: *\n");

        $page = Livewire::test(ToolsPage::class)->assertActionHidden('editRobots');

        // A forged request: mount, then submit the form state directly, past the test
        // helpers that refuse a hidden action.
        $page->call('mountAction', 'editRobots');
        $this->assertSame([], $page->get('mountedActions'));

        $page->set('mountedActions', [['name' => 'editRobots', 'arguments' => [], 'context' => [], 'data' => ['robots' => "User-agent: *\nDisallow: /"]]])
            ->call('callMountedAction');

        $this->assertSame("User-agent: *\n", File::get(public_path('robots.txt')));

        // The same page, the policy allowing: the action works.
        SeoFilesPlugin::get()->authorize(true);

        Livewire::test(ToolsPage::class)
            ->callAction('editRobots', ['robots' => "User-agent: *\nDisallow: /"]);

        $this->assertSame("User-agent: *\nDisallow: /\n", File::get(public_path('robots.txt')));
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
        SeoFilesPlugin::get()->authorize(true);

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
