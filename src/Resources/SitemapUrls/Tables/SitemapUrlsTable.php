<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Resources\SitemapUrls\Tables;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\SeoFiles;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SitemapUrlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->alignEnd(),
                // The title falls back to another language (marked `[en]`). Search covers
                // BOTH maps in ALL languages: an editor looks a record up by its label or by
                // the address itself and should not have to guess the language.
                TextColumn::make('title')
                    ->label(__('filament-seo-files::seo-files.fields.title'))
                    ->state(fn (SitemapUrl $record): string => $record->label())
                    ->searchable(['title', 'url']),
                ...array_map(
                    static fn (string $locale): TextColumn => TextColumn::make('url.'.$locale)
                        ->label(__('filament-seo-files::seo-files.fields.url').' ('.strtoupper($locale).')')
                        ->placeholder('—')
                        ->toggleable(),
                    SeoFiles::allLocales(),
                ),
                IconColumn::make('active')
                    ->label(__('filament-seo-files::seo-files.fields.active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('filament-seo-files::seo-files.fields.updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')->label(__('filament-seo-files::seo-files.fields.active')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
