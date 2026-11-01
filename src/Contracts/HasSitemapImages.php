<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Contracts;

/**
 * Implemented by an Eloquent model that knows the images of its page: a `ModelSource`
 * picks them up for the image sitemap when no `->images()` closure is set.
 */
interface HasSitemapImages
{
    /**
     * Absolute image URLs, or paths starting with `/` (the base URL is prepended).
     *
     * @return iterable<string>
     */
    public function sitemapImages(): iterable;
}
