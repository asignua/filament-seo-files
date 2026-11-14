<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Liste complète des URL du site',
    ],
    'actions' => [
        'generate' => 'Générer',
        'edit' => 'Modifier',
        'edit_file' => 'Modifier :file',
        'reset' => 'Réinitialiser le modèle',
        'save' => 'Enregistrer',
        'saved' => 'Enregistré',
        'finished' => 'Commande exécutée',
        'failed' => 'La commande a échoué',
        'generate_llms_warning' => 'Les fichiers llms.txt et llms-full.txt de chaque langue seront reconstruits à partir des sources. Les modifications faites dans l’éditeur seront remplacées.',
        'scheduled_overwrite' => 'Les fichiers sont aussi reconstruits chaque jour à :time, ce qui remplace les modifications faites ici.',
    ],
    'fields' => [
        'language' => 'Langue',
        'title' => 'Titre',
        'title_help' => 'Un libellé réservé à l’administration ; il n’apparaît pas dans le sitemap.',
        'images' => 'Images',
        'images_help' => 'Une URL d\'image par ligne (absolue ou chemin depuis la racine). Elles vont dans le sitemap d\'images.',
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
        'language_prefix' => 'N’ajoutez pas le préfixe de langue, il est ajouté automatiquement.',
        'owned' => 'Cette adresse appartient déjà à une page du site.',
        'duplicate' => 'Une autre URL manuelle a déjà cette adresse dans cette langue.',
    ],
    'page' => [
        'navigation' => 'Fichiers SEO',
        'title' => 'Fichiers SEO',
        'sitemap_help' => 'La liste des pages du site pour les moteurs de recherche, avec les versions linguistiques (hreflang).',
        'robots_help' => 'Les règles pour les robots d’exploration. C’est un fichier statique dans public/ : tant qu’il n’est pas enregistré, le site n’a pas de robots.txt.',
        'llms_help' => 'Description courte et complète du site pour les agents IA, une paire de fichiers par langue (:locales).',
        'generated_at' => 'Généré : :time',
        'url_count' => 'URL : :count',
        'part_count' => 'parties : :count',
        'not_generated' => 'Pas encore généré.',
        'file_exists' => 'Le fichier est enregistré sur le disque.',
        'robots_missing' => 'Le fichier n’existe pas encore : enregistrez-le pour publier le modèle recommandé.',
        'llms_languages' => 'Langues : :locales',
    ],
    'signals' => [
        'heading' => 'Content-Signal',
        'description' => 'Une préférence indiquée aux robots, pas un verrou : les robots honnêtes la respectent, les autres doivent être bloqués au niveau du CDN ou du serveur. Voir contentsignals.org.',
        'keys' => [
            'search' => [
                'label' => 'Recherche',
                'help' => 'Construire un index de recherche et afficher des liens et des extraits dans les résultats (pas des résumés générés par IA).',
            ],
            'ai-input' => [
                'label' => 'Réponses de l\'IA',
                'help' => 'Utiliser les pages comme source en temps réel pour les réponses de l\'IA : aperçus IA, génération augmentée par récupération, assistants de chat et agents qui lisent le site. Le désactiver empêche aussi les assistants IA d\'utiliser les instructions de llms.txt.',
            ],
            'ai-train' => [
                'label' => 'Entraînement de l\'IA',
                'help' => 'Entraîner ou affiner des modèles d\'IA avec le contenu.',
            ],
        ],
        'options' => [
            'yes' => 'Autorisé',
            'no' => 'Interdit',
            'unset' => 'Non précisé',
        ],
    ],
];
