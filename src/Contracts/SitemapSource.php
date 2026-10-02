<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Contracts;

use Asignua\FilamentSeoFiles\Data\SitemapEntry;

/**
 * A provider of pages for `sitemap.xml`.
 */
interface SitemapSource
{
    /**
     * One entry per page (not per language): the page's default-language URL plus its
     * language alternates. Return a lazy generator for large tables.
     *
     * @return iterable<SitemapEntry>
     */
    public function sitemapEntries(): iterable;
}
