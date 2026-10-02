<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Full list of site URLs',
    ],
    'actions' => [
        'generate' => 'Generate',
        'edit' => 'Edit',
        'edit_file' => 'Edit :file',
        'reset' => 'Reset to template',
        'save' => 'Save',
        'saved' => 'Saved',
        'finished' => 'Command finished',
        'failed' => 'Command failed',
        'generate_llms_warning' => 'Every language\'s llms.txt and llms-full.txt is rebuilt from the sources. Changes made in the editor are replaced.',
        'scheduled_overwrite' => 'The files are also rebuilt daily at :time, which replaces changes made here.',
    ],
    'fields' => [
        'language' => 'Language',
        'title' => 'Title',
        'title_help' => 'A label for the admin only; it does not reach the sitemap.',
        'url' => 'Address',
        'url_help' => 'A path from the site root without the language prefix (search, feeds/news), or a full address with https://.',
        'active' => 'Active',
        'updated' => 'Updated',
        'link' => 'Resulting address',
    ],
    'resource' => [
        'navigation' => 'Sitemap URLs',
        'singular' => 'Sitemap URL',
        'plural' => 'Sitemap URLs',
        'general' => 'General',
    ],
    'validation' => [
        'spaces' => 'The address cannot contain spaces.',
        'invalid' => 'Invalid address.',
        'scheme_or_path' => 'Enter a full address with a scheme or a path without one.',
        'language_prefix' => 'Do not add the language prefix, it is added automatically.',
        'owned' => 'This address already belongs to a page of the site.',
        'duplicate' => 'Another manual URL already has this address in this language.',
    ],
    'page' => [
        'navigation' => 'SEO files',
        'title' => 'SEO files',
        'sitemap_help' => 'The list of site pages for search engines, with language versions (hreflang).',
        'robots_help' => 'The rules for crawlers. It is a static file in public/: until it is saved, the site has no robots.txt.',
        'llms_help' => 'Short and full site descriptions for AI agents, one pair of files per language (:locales).',
        'generated_at' => 'Generated: :time',
        'url_count' => 'URLs: :count',
        'part_count' => 'parts: :count',
        'not_generated' => 'Not generated yet.',
        'file_exists' => 'The file is saved on disk.',
        'robots_missing' => 'The file does not exist yet — save it to publish the recommended template.',
        'llms_languages' => 'Languages: :locales',
    ],
];
