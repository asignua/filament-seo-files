<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Liste complète des URL du site',
    ],
    'actions' => [
        'run' => 'Lancer',
        'edit' => 'Modifier',
        'reset' => 'Réinitialiser le modèle',
        'saved' => 'Enregistré',
        'finished' => 'Commande exécutée',
        'failed' => 'La commande a échoué',
    ],
    'fields' => [
        'language' => 'Langue',
        'title' => 'Titre',
        'title_help' => 'Un libellé réservé à l’administration ; il n’apparaît pas dans le sitemap.',
        'url' => 'Adresse',
        'url_help' => 'Un chemin depuis la racine du site sans préfixe de langue (search, feeds/news) ou une adresse complète avec https://.',
        'active' => 'Actif',
        'updated' => 'Mis à jour',
        'link' => 'Adresse obtenue',
    ],
    'resource' => [
        'navigation' => 'URL du sitemap',
        'singular' => 'URL du sitemap',
        'plural' => 'URL du sitemap',
        'general' => 'Général',
    ],
    'validation' => [
        'spaces' => 'L’adresse ne peut pas contenir d’espaces.',
        'invalid' => 'Adresse non valide.',
        'scheme_or_path' => 'Saisissez une adresse complète avec schéma ou un chemin sans schéma.',
        'home' => 'La page d’accueil figure déjà dans le sitemap.',
        'language_prefix' => 'N’ajoutez pas le préfixe de langue, il est ajouté automatiquement.',
        'owned' => 'Cette adresse appartient déjà à une page du site.',
    ],
    'page' => [
        'navigation' => 'Fichiers SEO',
        'title' => 'Fichiers SEO',
        'sitemap_help' => 'La liste des pages du site pour les moteurs de recherche, avec les versions linguistiques (hreflang).',
        'robots_help' => 'Les règles pour les robots d’exploration. Tant que le fichier n’est pas enregistré, le modèle recommandé est servi.',
        'llms_help' => 'Description courte et complète du site pour les agents IA, une paire de fichiers par langue (:locales).',
        'generated_at' => 'Généré : :time',
        'url_count' => 'URL : :count',
        'part_count' => 'parties : :count',
        'not_generated' => 'Pas encore généré.',
        'file_exists' => 'Le fichier est enregistré sur le disque.',
        'template_served' => 'Le fichier n’existe pas encore : le modèle recommandé est servi.',
    ],
];
