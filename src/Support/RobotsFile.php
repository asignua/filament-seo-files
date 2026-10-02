<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\SeoFiles;
use Illuminate\Support\Facades\File;

/**
 * Reading and writing `robots.txt` plus the recommended template. All bots are allowed
 * by default; the editor lets the site add `Disallow` lines later.
 */
class RobotsFile
{
    public function path(): string
    {
        return (string) (config('filament-seo-files.robots.path') ?? public_path('robots.txt'));
    }

    public function read(): string
    {
        return File::exists($this->path())
            ? (string) File::get($this->path())
            : $this->template();
    }

    public function write(string $content): void
    {
        AtomicFile::put($this->path(), rtrim($content)."\n");
    }

    public function template(): string
    {
        $sitemap = (new SitemapFile)->url();

        // Content-Signal (contentsignals.org) declares the policy for using the content with
        // AI. Default: search and agent answers are allowed (they bring traffic), model
        // training is not (the site's content stays out of datasets). The directive lives
        // INSIDE the User-agent group, not next to Sitemap: it concerns that group of bots.
        $default = <<<TXT
        User-agent: *
        Content-Signal: search=yes, ai-input=yes, ai-train=no
        Allow: /

        Sitemap: {$sitemap}
        TXT;

        return SeoFiles::robotsTemplate($default);
    }
}
