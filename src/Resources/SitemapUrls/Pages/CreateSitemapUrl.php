<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages;

use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\SitemapUrlResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSitemapUrl extends CreateRecord
{
    protected static string $resource = SitemapUrlResource::class;

    /**
     * The model has no mass assignment; the repository is the only door for writes.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SitemapUrlRepository::class)->create($data);
    }
}
