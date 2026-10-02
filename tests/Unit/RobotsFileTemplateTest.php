<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Unit;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\RobotsFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;

/**
 * The default robots.txt (served until the site saves its own file).
 *
 * The test pins the policy STRING: Content-Signal is a declaration read by external
 * crawlers, so "accidentally reworded" is expensive here.
 */
class RobotsFileTemplateTest extends TestCase
{
    private function template(): string
    {
        SeoFiles::baseUrlUsing(fn (): string => 'https://example.test/');

        return (new RobotsFile)->template();
    }

    public function test_declares_content_signal_policy(): void
    {
        $this->assertStringContainsString(
            'Content-Signal: search=yes, ai-input=yes, ai-train=no',
            $this->template(),
        );
    }

    /**
     * Content-Signal concerns a specific group of bots, so it must sit INSIDE the
     * `User-agent` group, not next to Sitemap after a blank line: there it belongs to
     * nobody.
     */
    public function test_content_signal_sits_inside_the_user_agent_group(): void
    {
        $lines = explode("\n", $this->template());

        $this->assertSame('User-agent: *', $lines[0]);
        $this->assertSame('Content-Signal: search=yes, ai-input=yes, ai-train=no', $lines[1]);
        $this->assertSame('Allow: /', $lines[2]);
        $this->assertSame('', $lines[3]);
    }

    public function test_keeps_allow_and_sitemap(): void
    {
        $template = $this->template();

        $this->assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $template);
        $this->assertStringNotContainsString('Disallow', $template);
    }

    public function test_the_template_can_be_replaced(): void
    {
        SeoFiles::robotsTemplateUsing(fn (string $base, string $default): string => $default."\nDisallow: /admin # {$base}");

        $this->assertStringContainsString('Disallow: /admin # https://site.test', (new RobotsFile)->template());
    }
}
