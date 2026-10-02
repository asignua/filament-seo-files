<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages;

use Asignua\FilamentSeoFiles\Resources\SitemapUrls\SitemapUrlResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSitemapUrls extends ListRecords
{
    protected static string $resource = SitemapUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
