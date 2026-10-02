<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Pełna lista adresów URL witryny',
    ],
    'actions' => [
        'run' => 'Uruchom',
        'edit' => 'Edytuj',
        'reset' => 'Przywróć szablon',
        'saved' => 'Zapisano',
        'finished' => 'Polecenie wykonane',
        'failed' => 'Polecenie zakończyło się błędem',
    ],
    'fields' => [
        'language' => 'Język',
        'title' => 'Tytuł',
        'title_help' => 'Etykieta tylko dla panelu; nie trafia do mapy witryny.',
        'url' => 'Adres',
        'url_help' => 'Ścieżka od katalogu głównego witryny bez prefiksu języka (search, feeds/news) lub pełny adres z https://.',
        'active' => 'Aktywny',
        'updated' => 'Zaktualizowano',
        'link' => 'Wynikowy adres',
    ],
    'resource' => [
        'navigation' => 'Adresy mapy witryny',
        'singular' => 'Adres mapy witryny',
        'plural' => 'Adresy mapy witryny',
        'general' => 'Ogólne',
    ],
    'validation' => [
        'spaces' => 'Adres nie może zawierać spacji.',
        'invalid' => 'Nieprawidłowy adres.',
        'scheme_or_path' => 'Podaj pełny adres ze schematem albo ścieżkę bez schematu.',
        'home' => 'Strona główna jest już w mapie witryny.',
        'language_prefix' => 'Nie dodawaj prefiksu języka, jest dodawany automatycznie.',
        'owned' => 'Ten adres należy już do strony witryny.',
    ],
    'page' => [
        'navigation' => 'Pliki SEO',
        'title' => 'Pliki SEO',
        'sitemap_help' => 'Lista stron witryny dla wyszukiwarek, z wersjami językowymi (hreflang).',
        'robots_help' => 'Reguły dla robotów indeksujących. Dopóki plik nie zostanie zapisany, serwowany jest zalecany szablon.',
        'llms_help' => 'Krótki i pełny opis witryny dla agentów AI, para plików na język (:locales).',
        'generated_at' => 'Wygenerowano: :time',
        'url_count' => 'Adresów: :count',
        'part_count' => 'części: :count',
        'not_generated' => 'Jeszcze nie wygenerowano.',
        'file_exists' => 'Plik jest zapisany na dysku.',
        'template_served' => 'Plik jeszcze nie istnieje — serwowany jest zalecany szablon.',
    ],
];
