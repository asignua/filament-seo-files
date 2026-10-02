<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Volledige lijst van de URL’s van de site',
    ],
    'actions' => [
        'run' => 'Uitvoeren',
        'edit' => 'Bewerken',
        'reset' => 'Terugzetten naar sjabloon',
        'saved' => 'Opgeslagen',
        'finished' => 'Opdracht uitgevoerd',
        'failed' => 'Opdracht mislukt',
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
        'home' => 'De homepage staat al in de sitemap.',
        'language_prefix' => 'Voeg geen taalvoorvoegsel toe, het wordt automatisch toegevoegd.',
        'owned' => 'Dit adres hoort al bij een pagina van de site.',
    ],
    'page' => [
        'navigation' => 'SEO-bestanden',
        'title' => 'SEO-bestanden',
        'sitemap_help' => 'De lijst met pagina’s van de site voor zoekmachines, met taalversies (hreflang).',
        'robots_help' => 'De regels voor crawlers. Zolang het bestand niet is opgeslagen, wordt het aanbevolen sjabloon geleverd.',
        'llms_help' => 'Korte en volledige beschrijving van de site voor AI-agents, een bestandspaar per taal (:locales).',
        'generated_at' => 'Gegenereerd: :time',
        'url_count' => 'URL’s: :count',
        'part_count' => 'delen: :count',
        'not_generated' => 'Nog niet gegenereerd.',
        'file_exists' => 'Het bestand is op schijf opgeslagen.',
        'template_served' => 'Het bestand bestaat nog niet: het aanbevolen sjabloon wordt geleverd.',
    ],
];
