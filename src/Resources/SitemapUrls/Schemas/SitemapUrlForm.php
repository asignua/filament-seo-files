<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Resources\SitemapUrls\Schemas;

use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\SitemapLocation;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class SitemapUrlForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = SeoFiles::allLocales();

        return $schema->components([
            Section::make(__('filament-seo-files::seo-files.resource.general'))
                ->schema([
                    // The switch sits OUTSIDE the tabs: it covers the whole record. Inside a
                    // tab it would read as "this language is active" although it switches
                    // off the entire <url>.
                    Toggle::make('active')
                        ->label(__('filament-seo-files::seo-files.fields.active'))
                        ->default(true),

                    count($locales) > 1
                        ? Tabs::make('locales')
                            ->tabs(array_map(
                                static fn (string $locale): Tab => Tab::make(strtoupper($locale))
                                    ->schema(self::localeFields($locale)),
                                $locales,
                            ))
                            ->columnSpanFull()
                        : Group::make(self::localeFields($locales[0]))
                            ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * The fields of one language: the address, a live preview of the resulting URL and the
     * label.
     *
     * @return array<int, mixed>
     */
    private static function localeFields(string $locale): array
    {
        $others = self::otherLocaleFields($locale);

        return [
            TextInput::make("url.{$locale}")
                ->label(__('filament-seo-files::seo-files.fields.url'))
                ->maxLength(500)
                ->live(onBlur: true)
                ->helperText(__('filament-seo-files::seo-files.fields.url_help'))
                ->dehydrateStateUsing(static fn (?string $state): string => SitemapLocation::normalize((string) $state))
                ->rules(['nullable', 'string', 'max:500'])
                // "At least one language is filled": without it a record with no address at
                // all would be saved and silently give nothing in the sitemap. On a
                // SINGLE-language site the list of other languages is empty, and Filament
                // attaches requiredWithoutAll only when that list is non-empty — the rule
                // would silently not apply there, and the guarantee would disappear along
                // with the second language. So the single-language case is a plain required.
                ->when(
                    $others === [],
                    static fn (TextInput $field): TextInput => $field->required(),
                    static fn (TextInput $field): TextInput => $field->requiredWithoutAll($others),
                )
                ->rule(static fn (?Model $record): Closure => self::validator($locale, $record)),

            // A preview, not just helper text: the rule "a path without the language prefix,
            // the generator adds the host and the prefix" does not get across in a
            // description — the editor must see the resulting address as they type it.
            Text::make(static function (Get $get) use ($locale): HtmlString {
                $href = SitemapLocation::forCustom($locale, (string) ($get("url.{$locale}") ?? ''));

                return new HtmlString(
                    e(__('filament-seo-files::seo-files.fields.link')).': '
                    .($href === null ? '<span class="text-gray-400">—</span>' : '<code>'.e($href).'</code>'),
                );
            }),

            TextInput::make("title.{$locale}")
                ->label(__('filament-seo-files::seo-files.fields.title'))
                ->maxLength(255)
                ->helperText(__('filament-seo-files::seo-files.fields.title_help')),
        ];
    }

    /**
     * The address fields of the other languages — for requiredWithoutAll.
     *
     * @return array<int, string>
     */
    private static function otherLocaleFields(string $locale): array
    {
        return array_values(array_map(
            static fn (string $other): string => "url.{$other}",
            array_diff(SeoFiles::allLocales(), [$locale]),
        ));
    }

    /**
     * Checks of one address. The hybrid format: a full address with a scheme, or a path from
     * the root WITHOUT the language prefix.
     */
    private static function validator(string $locale, ?Model $record): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($locale, $record): void {
            $value = trim((string) $value);

            if ($value === '') {
                return;
            }

            // A space inside an address is always a typo, and it breaks not the form but
            // the consumer of the XML, which would be noticed late.
            if (preg_match('/\s/u', $value) === 1) {
                $fail(__('filament-seo-files::seo-files.validation.spaces'));

                return;
            }

            if (SitemapLocation::isAbsolute($value)) {
                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail(__('filament-seo-files::seo-files.validation.invalid'));
                }

                return;
            }

            // `ftp://`, `HTTP:/` with one slash and a protocol-relative `//host` are not
            // paths; the sitemap library's `url()` would turn them into something else
            // instead of reporting an error.
            if (str_contains($value, '://') || str_starts_with($value, '//')) {
                $fail(__('filament-seo-files::seo-files.validation.scheme_or_path'));

                return;
            }

            if (SitemapLocation::startsWithLanguagePrefix($value)) {
                $fail(__('filament-seo-files::seo-files.validation.language_prefix'));

                return;
            }

            // The only check that actually catches a duplicate of a real page: the site
            // knows its own paths, and the generator takes its first pass from there. The
            // root path is asked about as `''` — the home page is no exception: it is in the
            // sitemap only when a source emits it, and then the site says it owns it.
            if (SeoFiles::ownsPath($locale, trim(SitemapLocation::normalize($value), '/'))) {
                $fail(__('filament-seo-files::seo-files.validation.owned'));

                return;
            }

            // Another manual record with the same address in this language.
            $repository = app(SitemapUrlRepository::class);

            if ($repository->existsInLocale($locale, SitemapLocation::normalize($value), $record?->getKey())) {
                $fail(__('filament-seo-files::seo-files.validation.duplicate'));
            }
        };
    }
}
