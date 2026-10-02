<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Vollständige Liste der Website-URLs',
    ],
    'actions' => [
        'run' => 'Ausführen',
        'generate' => 'Generieren',
        'edit' => 'Bearbeiten',
        'edit_file' => ':file bearbeiten',
        'reset' => 'Auf Vorlage zurücksetzen',
        'save' => 'Speichern',
        'saved' => 'Gespeichert',
        'finished' => 'Befehl ausgeführt',
        'failed' => 'Befehl fehlgeschlagen',
    ],
    'fields' => [
        'language' => 'Sprache',
        'title' => 'Titel',
        'title_help' => 'Nur eine Bezeichnung für den Admin-Bereich; sie gelangt nicht in die Sitemap.',
        'url' => 'Adresse',
        'url_help' => 'Ein Pfad ab der Website-Wurzel ohne Sprachpräfix (search, feeds/news) oder eine vollständige Adresse mit https://.',
        'active' => 'Aktiv',
        'updated' => 'Aktualisiert',
        'link' => 'Resultierende Adresse',
    ],
    'resource' => [
        'navigation' => 'Sitemap-URLs',
        'singular' => 'Sitemap-URL',
        'plural' => 'Sitemap-URLs',
        'general' => 'Allgemein',
    ],
    'validation' => [
        'spaces' => 'Die Adresse darf keine Leerzeichen enthalten.',
        'invalid' => 'Ungültige Adresse.',
        'scheme_or_path' => 'Geben Sie eine vollständige Adresse mit Schema oder einen Pfad ohne Schema ein.',
        'home' => 'Die Startseite ist bereits in der Sitemap.',
        'language_prefix' => 'Fügen Sie kein Sprachpräfix hinzu, es wird automatisch ergänzt.',
        'owned' => 'Diese Adresse gehört bereits zu einer Seite der Website.',
    ],
    'page' => [
        'navigation' => 'SEO-Dateien',
        'title' => 'SEO-Dateien',
        'sitemap_help' => 'Die Liste der Seiten für Suchmaschinen, mit Sprachversionen (hreflang).',
        'robots_help' => 'Die Regeln für Crawler. Eine statische Datei in public/: solange sie nicht gespeichert ist, hat die Website keine robots.txt.',
        'llms_help' => 'Kurze und vollständige Beschreibung der Website für KI-Agenten, ein Dateipaar pro Sprache (:locales).',
        'generated_at' => 'Erzeugt: :time',
        'url_count' => 'URLs: :count',
        'part_count' => 'Teile: :count',
        'not_generated' => 'Noch nicht erzeugt.',
        'file_exists' => 'Die Datei ist auf dem Datenträger gespeichert.',
        'robots_missing' => 'Die Datei existiert noch nicht – speichern Sie sie, um die empfohlene Vorlage zu veröffentlichen.',
        'llms_languages' => 'Sprachen: :locales',
    ],
];
