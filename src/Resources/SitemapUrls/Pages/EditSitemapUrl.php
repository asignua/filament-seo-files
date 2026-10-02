<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\SitemapUrlResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSitemapUrl extends EditRecord
{
    protected static string $resource = SitemapUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SitemapUrl $record */
        return app(SitemapUrlRepository::class)->update($record, $data);
    }
}
