<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Resources\SitemapUrls;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages\CreateSitemapUrl;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages\EditSitemapUrl;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Pages\ListSitemapUrls;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Schemas\SitemapUrlForm;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\Tables\SitemapUrlsTable;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Manual addresses of sitemap.xml: pages no registered source knows about.
 */
class SitemapUrlResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    public static function getModel(): string
    {
        return app(SitemapUrlRepository::class)->modelClass();
    }

    public static function canAccess(): bool
    {
        return SeoFilesPlugin::allows() && parent::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-seo-files::seo-files.resource.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-seo-files::seo-files.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-seo-files::seo-files.resource.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return self::plugin()?->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return self::plugin()?->getNavigationSort() ?? 5;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return self::plugin()?->getNavigationIcon() ?? static::$navigationIcon;
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof SitemapUrl ? $record->label() : null;
    }

    public static function form(Schema $schema): Schema
    {
        return SitemapUrlForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitemapUrlsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSitemapUrls::route('/'),
            'create' => CreateSitemapUrl::route('/create'),
            'edit' => EditSitemapUrl::route('/{record}/edit'),
        ];
    }

    private static function plugin(): ?SeoFilesPlugin
    {
        return filament()->hasPlugin(SeoFilesPlugin::ID) ? SeoFilesPlugin::get() : null;
    }
}
