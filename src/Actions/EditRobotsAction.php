<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Support\RobotsFile;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

/**
 * A modal editor of robots.txt with a "reset to template" button.
 */
class EditRobotsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'editRobots';
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
            ->modalHeading(__('filament-seo-files::seo-files.actions.edit_file', ['file' => 'robots.txt']))
            ->modalSubmitActionLabel(__('filament-seo-files::seo-files.actions.save'))
            ->fillForm(fn (): array => ['robots' => app(RobotsFile::class)->read()])
            ->schema([
                Textarea::make('robots')
                    ->label('robots.txt')
                    ->rows(14)
                    ->required()
                    ->maxLength(20000)
                    ->hintAction(
                        Action::make('reset')
                            ->label(__('filament-seo-files::seo-files.actions.reset'))
                            ->action(fn (Set $set) => $set('robots', app(RobotsFile::class)->template())),
                    ),
            ])
            ->action(function (array $data): void {
                app(RobotsFile::class)->write((string) $data['robots']);

                Notification::make()->title(__('filament-seo-files::seo-files.actions.saved'))->success()->send();
            });
    }
}
