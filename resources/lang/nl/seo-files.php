<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Volledige lijst van de URL’s van de site',
    ],
    'actions' => [
        'generate' => 'Genereren',
        'edit' => 'Bewerken',
        'edit_file' => ':file bewerken',
        'reset' => 'Terugzetten naar sjabloon',
        'save' => 'Opslaan',
        'saved' => 'Opgeslagen',
        'finished' => 'Opdracht uitgevoerd',
        'failed' => 'Opdracht mislukt',
        'generate_llms_warning' => 'llms.txt en llms-full.txt worden voor elke taal opnieuw opgebouwd uit de bronnen. Wijzigingen in de editor worden vervangen.',
        'scheduled_overwrite' => 'De bestanden worden ook dagelijks om :time opnieuw opgebouwd, waarbij wijzigingen hier worden vervangen.',
    ],
    'fields' => [
        'language' => 'Taal',
        'title' => 'Titel',
        'title_help' => 'Alleen een label voor het beheer; het komt niet in de sitemap.',
        'url' => 'Adres',
        'url_help' => 'Een pad vanaf de root van de site zonder taalvoorvoegsel (search, feeds/news) of een volledig adres met https://.',
        'active' => 'Actief',
        'updated' => 'Bijgewerkt',
        'link' => 'Resulterend adres',
    ],
    'resource' => [
        'navigation' => 'Sitemap-URL’s',
        'singular' => 'Sitemap-URL',
        'plural' => 'Sitemap-URL’s',
        'general' => 'Algemeen',
    ],
    'validation' => [
        'spaces' => 'Het adres mag geen spaties bevatten.',
        'invalid' => 'Ongeldig adres.',
        'scheme_or_path' => 'Voer een volledig adres met schema in of een pad zonder schema.',
        'language_prefix' => 'Voeg geen taalvoorvoegsel toe, het wordt automatisch toegevoegd.',
        'owned' => 'Dit adres hoort al bij een pagina van de site.',
        'duplicate' => 'Een andere handmatige URL heeft dit adres al in deze taal.',
    ],
    'page' => [
        'navigation' => 'SEO-bestanden',
        'title' => 'SEO-bestanden',
        'sitemap_help' => 'De lijst met pagina’s van de site voor zoekmachines, met taalversies (hreflang).',
        'robots_help' => 'De regels voor crawlers. Het is een statisch bestand in public/: zolang het niet is opgeslagen, heeft de site geen robots.txt.',
        'llms_help' => 'Korte en volledige beschrijving van de site voor AI-agents, een bestandspaar per taal (:locales).',
        'generated_at' => 'Gegenereerd: :time',
        'url_count' => 'URL’s: :count',
        'part_count' => 'delen: :count',
        'not_generated' => 'Nog niet gegenereerd.',
        'file_exists' => 'Het bestand is op schijf opgeslagen.',
        'robots_missing' => 'Het bestand bestaat nog niet: sla het op om het aanbevolen sjabloon te publiceren.',
        'llms_languages' => 'Talen: :locales',
    ],
];
