<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Vollständige Liste der Website-URLs',
    ],
    'actions' => [
        'generate' => 'Generieren',
        'edit' => 'Bearbeiten',
        'edit_file' => ':file bearbeiten',
        'reset' => 'Auf Vorlage zurücksetzen',
        'save' => 'Speichern',
        'saved' => 'Gespeichert',
        'finished' => 'Befehl ausgeführt',
        'failed' => 'Befehl fehlgeschlagen',
        'generate_llms_warning' => 'llms.txt und llms-full.txt werden für jede Sprache neu aus den Quellen erstellt. Änderungen im Editor werden ersetzt.',
        'scheduled_overwrite' => 'Die Dateien werden außerdem täglich um :time neu erstellt; dabei werden hier vorgenommene Änderungen ersetzt.',
        'indexnow' => 'An IndexNow senden',
        'indexnow_warning' => 'Alle URLs der erzeugten Sitemap werden jetzt an die IndexNow-Suchmaschinen (Bing, Yandex …) gesendet. Erzeugen Sie zuerst die Sitemap.',
    ],
    'fields' => [
        'language' => 'Sprache',
        'title' => 'Titel',
        'title_help' => 'Nur eine Bezeichnung für den Admin-Bereich; sie gelangt nicht in die Sitemap.',
        'images' => 'Bilder',
        'images_help' => 'Eine Bild-URL pro Zeile (absolut oder Pfad ab der Wurzel). Sie gelangen in die Bild-Sitemap.',
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
        'language_prefix' => 'Fügen Sie kein Sprachpräfix hinzu, es wird automatisch ergänzt.',
        'owned' => 'Diese Adresse gehört bereits zu einer Seite der Website.',
        'duplicate' => 'Eine andere manuelle URL hat diese Adresse in dieser Sprache bereits.',
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
        'indexnow_help' => 'Meldet geänderte Seiten sofort den Suchmaschinen, statt zu warten, bis diese die Sitemap erneut lesen.',
        'indexnow_on' => 'IndexNow ist aktiv.',
        'indexnow_off' => 'IndexNow ist aus: in der Konfiguration aktivieren und einen Schlüssel setzen.',
    ],
    'signals' => [
        'heading' => 'Content-Signal',
        'description' => 'Eine Präferenz für Crawler, keine Sperre: seriöse Bots halten sich daran, alle anderen müssen auf CDN- oder Serverebene blockiert werden. Siehe contentsignals.org.',
        'keys' => [
            'search' => [
                'label' => 'Suche',
                'help' => 'Aufbau eines Suchindex sowie Anzeige von Links und Snippets in Suchergebnissen (keine KI-generierten Zusammenfassungen).',
            ],
            'ai-input' => [
                'label' => 'KI-Antworten',
                'help' => 'Nutzung der Seiten als Echtzeitquelle für KI-Antworten: KI-Übersichten, Retrieval-Augmented Generation, Chat-Assistenten und Agenten, die die Website lesen. Wer es ausschaltet, hindert KI-Assistenten auch daran, die Anweisungen aus llms.txt zu nutzen.',
            ],
            'ai-train' => [
                'label' => 'KI-Training',
                'help' => 'Training oder Feintuning von KI-Modellen mit den Inhalten.',
            ],
        ],
        'options' => [
            'yes' => 'Erlaubt',
            'no' => 'Verboten',
            'unset' => 'Nicht angegeben',
        ],
    ],
];
