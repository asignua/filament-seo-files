<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Asignua\FilamentSeoFiles\Schedule;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * A modal editor of llms.txt. Unlike robots.txt there is one file per language, so the
 * form has a language select that re-reads the content on the fly.
 */
class EditLlmsAction extends Action
{
    /**
     * Characters the editor accepts. Generous on purpose: a generated llms.txt of a large
     * site must stay savable unchanged.
     */
    public const int MAX_LENGTH = 1000000;

    public static function getDefaultName(): ?string
    {
        return 'editLlms';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            // The plugin's policy travels with the action: a host page with weaker access (a
            // shared Tools page, a dashboard) must not open a back door to the web root.
            ->authorize(static fn (): bool => SeoFilesPlugin::allows())
            ->label(__('filament-seo-files::seo-files.actions.edit'))
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->modalHeading(__('filament-seo-files::seo-files.actions.edit_file', ['file' => 'llms.txt']))
            // A scheduled `seo-files:llms` rewrites the files every night: an edit made here
            // lasts until then, and the editor must know it before spending time on it.
            ->modalDescription(static fn (): ?string => Schedule::llmsTime() !== null
                ? (string) __('filament-seo-files::seo-files.actions.scheduled_overwrite', ['time' => Schedule::llmsTime()])
                : null)
            ->modalSubmitActionLabel(__('filament-seo-files::seo-files.actions.save'))
            ->fillForm(fn (): array => [
                'locale' => SeoFiles::defaultLocale(),
                'llms' => app(LlmsTxtFile::class)->read(SeoFiles::defaultLocale()),
            ])
            ->schema([
                Select::make('locale')
                    ->label(__('filament-seo-files::seo-files.fields.language'))
                    ->options(array_combine(SeoFiles::allLocales(), array_map(strtoupper(...), SeoFiles::allLocales())))
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, Set $set) => $set('llms', app(LlmsTxtFile::class)->read((string) $state))),
                Textarea::make('llms')
                    ->label('llms.txt')
                    ->rows(18)
                    ->required()
                    ->maxLength(self::MAX_LENGTH)
                    ->hintAction(
                        Action::make('resetLlms')
                            ->label(__('filament-seo-files::seo-files.actions.reset'))
                            ->action(fn (Set $set, Get $get) => $set(
                                'llms',
                                app(LlmsTxtFile::class)->template((string) $get('locale')),
                            )),
                    ),
            ])
            ->action(function (array $data): void {
                app(LlmsTxtFile::class)->write((string) $data['locale'], (string) $data['llms']);

                Notification::make()->title(__('filament-seo-files::seo-files.actions.saved'))->success()->send();
            });
    }
}
