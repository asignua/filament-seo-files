<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Elenco completo degli URL del sito',
    ],
    'actions' => [
        'run' => 'Esegui',
        'generate' => 'Genera',
        'edit' => 'Modifica',
        'edit_file' => 'Modifica :file',
        'reset' => 'Ripristina il modello',
        'save' => 'Salva',
        'saved' => 'Salvato',
        'finished' => 'Comando eseguito',
        'failed' => 'Comando non riuscito',
    ],
    'fields' => [
        'language' => 'Lingua',
        'title' => 'Titolo',
        'title_help' => 'Un’etichetta solo per l’amministrazione; non finisce nella sitemap.',
        'url' => 'Indirizzo',
        'url_help' => 'Un percorso dalla radice del sito senza prefisso di lingua (search, feeds/news) oppure un indirizzo completo con https://.',
        'active' => 'Attivo',
        'updated' => 'Aggiornato',
        'link' => 'Indirizzo risultante',
    ],
    'resource' => [
        'navigation' => 'URL della sitemap',
        'singular' => 'URL della sitemap',
        'plural' => 'URL della sitemap',
        'general' => 'Generale',
    ],
    'validation' => [
        'spaces' => 'L’indirizzo non può contenere spazi.',
        'invalid' => 'Indirizzo non valido.',
        'scheme_or_path' => 'Inserisci un indirizzo completo con schema oppure un percorso senza schema.',
        'home' => 'La home page è già nella sitemap.',
        'language_prefix' => 'Non aggiungere il prefisso di lingua, viene aggiunto automaticamente.',
        'owned' => 'Questo indirizzo appartiene già a una pagina del sito.',
    ],
    'page' => [
        'navigation' => 'File SEO',
        'title' => 'File SEO',
        'sitemap_help' => 'L’elenco delle pagine del sito per i motori di ricerca, con le versioni linguistiche (hreflang).',
        'robots_help' => 'Le regole per i crawler. È un file statico in public/: finché non viene salvato, il sito non ha un robots.txt.',
        'llms_help' => 'Descrizione breve e completa del sito per gli agenti IA, una coppia di file per lingua (:locales).',
        'generated_at' => 'Generato: :time',
        'url_count' => 'URL: :count',
        'part_count' => 'parti: :count',
        'not_generated' => 'Non ancora generato.',
        'file_exists' => 'Il file è salvato su disco.',
        'robots_missing' => 'Il file non esiste ancora: salvalo per pubblicare il modello consigliato.',
        'llms_languages' => 'Lingue: :locales',
    ],
];
