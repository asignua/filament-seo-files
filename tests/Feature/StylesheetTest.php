<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Pages\SeoFilesPage;
use Asignua\FilamentSeoFiles\SeoFilesServiceProvider;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Filament\Support\Facades\FilamentAsset;

class StylesheetTest extends TestCase
{
    public function test_the_compiled_stylesheet_is_shipped_and_registered(): void
    {
        $this->assertFileExists(__DIR__.'/../../resources/dist/filament-seo-files.css');

        $href = FilamentAsset::getStyleHref(SeoFilesServiceProvider::STYLESHEET, SeoFilesServiceProvider::PACKAGE);

        $this->assertStringContainsString('filament-seo-files', $href);
    }

    public function test_the_stylesheet_declares_the_layer_order(): void
    {
        $css = (string) file_get_contents(__DIR__.'/../../resources/dist/filament-seo-files.css');

        $this->assertStringContainsString('@layer theme,base,components', $css);
        $this->assertStringNotContainsString('box-sizing', $css, 'no preflight');
    }

    public function test_the_panel_links_the_stylesheet_when_the_plugin_is_on(): void
    {
        $this->actingAs($this->admin());

        $html = $this->get(SeoFilesPage::getUrl())->assertOk()->getContent();

        $this->assertStringContainsString(
            'rel="stylesheet" href="'.e(FilamentAsset::getStyleHref(SeoFilesServiceProvider::STYLESHEET, SeoFilesServiceProvider::PACKAGE)).'"',
            (string) $html,
        );
    }
}
