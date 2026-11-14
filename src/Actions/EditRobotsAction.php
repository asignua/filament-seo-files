<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Support\ContentSignal;
use Asignua\FilamentSeoFiles\Support\RobotsFile;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
            ->fillForm(function (): array {
                $robots = app(RobotsFile::class)->read();

                return ['robots' => $robots] + self::switchesFrom($robots);
            })
            ->schema([
                Section::make(__('filament-seo-files::seo-files.signals.heading'))
                    ->description(__('filament-seo-files::seo-files.signals.description'))
                    ->schema(array_map(self::switchFor(...), ContentSignal::KEYS))
                    ->compact(),
                Textarea::make('robots')
                    ->label('robots.txt')
                    ->rows(14)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::syncSwitches($set, (string) $get('robots')))
                    ->maxLength(20000)
                    ->hintAction(
                        Action::make('reset')
                            ->label(__('filament-seo-files::seo-files.actions.reset'))
                            ->action(function (Set $set): void {
                                $template = app(RobotsFile::class)->template();

                                $set('robots', $template);
                                self::syncSwitches($set, $template);
                            }),
                    ),
            ])
            ->action(function (array $data): void {
                app(RobotsFile::class)->write((string) $data['robots']);

                Notification::make()->title(__('filament-seo-files::seo-files.actions.saved'))->success()->send();
            });
    }

    private static function field(string $key): string
    {
        return 'signal_'.str_replace('-', '_', $key);
    }

    private static function switchFor(string $key): ToggleButtons
    {
        return ToggleButtons::make(self::field($key))
            ->label(__("filament-seo-files::seo-files.signals.keys.{$key}.label"))
            ->helperText(__("filament-seo-files::seo-files.signals.keys.{$key}.help"))
            ->options([
                'yes' => __('filament-seo-files::seo-files.signals.options.yes'),
                'no' => __('filament-seo-files::seo-files.signals.options.no'),
                'unset' => __('filament-seo-files::seo-files.signals.options.unset'),
            ])
            ->colors(['yes' => 'success', 'no' => 'danger', 'unset' => 'gray'])
            ->inline()
            ->live()
            ->dehydrated(false)
            ->afterStateUpdated(function (Get $get, Set $set): void {
                $signals = [];

                foreach (ContentSignal::KEYS as $each) {
                    $signals[$each] = match ($get(self::field($each))) {
                        'yes' => true,
                        'no' => false,
                        default => null,
                    };
                }

                $set('robots', ContentSignal::apply((string) $get('robots'), $signals));
            });
    }

    /**
     * @return array<string, string>
     */
    private static function switchesFrom(string $robots): array
    {
        $state = [];

        foreach (ContentSignal::parse($robots) as $key => $value) {
            $state[self::field($key)] = match ($value) {
                true => 'yes',
                false => 'no',
                null => 'unset',
            };
        }

        return $state;
    }

    private static function syncSwitches(Set $set, string $robots): void
    {
        foreach (self::switchesFrom($robots) as $field => $value) {
            $set($field, $value);
        }
    }
}
