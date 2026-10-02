<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Lista completa de las URL del sitio',
    ],
    'actions' => [
        'run' => 'Ejecutar',
        'generate' => 'Generar',
        'edit' => 'Editar',
        'edit_file' => 'Editar :file',
        'reset' => 'Restablecer la plantilla',
        'save' => 'Guardar',
        'saved' => 'Guardado',
        'finished' => 'Comando ejecutado',
        'failed' => 'El comando ha fallado',
    ],
    'fields' => [
        'language' => 'Idioma',
        'title' => 'Título',
        'title_help' => 'Una etiqueta solo para el panel; no llega al sitemap.',
        'url' => 'Dirección',
        'url_help' => 'Una ruta desde la raíz del sitio sin prefijo de idioma (search, feeds/news) o una dirección completa con https://.',
        'active' => 'Activo',
        'updated' => 'Actualizado',
        'link' => 'Dirección resultante',
    ],
    'resource' => [
        'navigation' => 'URL del sitemap',
        'singular' => 'URL del sitemap',
        'plural' => 'URL del sitemap',
        'general' => 'General',
    ],
    'validation' => [
        'spaces' => 'La dirección no puede contener espacios.',
        'invalid' => 'Dirección no válida.',
        'scheme_or_path' => 'Introduzca una dirección completa con esquema o una ruta sin él.',
        'home' => 'La página de inicio ya está en el sitemap.',
        'language_prefix' => 'No añada el prefijo de idioma, se añade automáticamente.',
        'owned' => 'Esta dirección ya pertenece a una página del sitio.',
    ],
    'page' => [
        'navigation' => 'Archivos SEO',
        'title' => 'Archivos SEO',
        'sitemap_help' => 'La lista de páginas del sitio para los buscadores, con versiones por idioma (hreflang).',
        'robots_help' => 'Las reglas para los rastreadores. Es un archivo estático en public/: mientras no se guarde, el sitio no tiene robots.txt.',
        'llms_help' => 'Descripción breve y completa del sitio para agentes de IA, un par de archivos por idioma (:locales).',
        'generated_at' => 'Generado: :time',
        'url_count' => 'URL: :count',
        'part_count' => 'partes: :count',
        'not_generated' => 'Aún no generado.',
        'file_exists' => 'El archivo está guardado en el disco.',
        'robots_missing' => 'El archivo aún no existe: guárdelo para publicar la plantilla recomendada.',
        'llms_languages' => 'Idiomas: :locales',
    ],
];
