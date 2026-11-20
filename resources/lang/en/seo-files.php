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
        'indexnow' => 'Send to IndexNow',
        'indexnow_warning' => 'Every URL of the generated sitemap is sent to the IndexNow engines (Bing, Yandex…) right now. Generate the sitemap first.',
    ],
    'fields' => [
        'language' => 'Language',
        'title' => 'Title',
        'title_help' => 'A label for the admin only; it does not reach the sitemap.',
        'images' => 'Images',
        'images_help' => 'One image URL per line (absolute, or a path from the site root). They go into the image sitemap.',
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
        'indexnow_help' => 'Tells search engines about changed pages right away instead of waiting for them to re-read the sitemap.',
        'indexnow_on' => 'IndexNow is on.',
        'indexnow_off' => 'IndexNow is off: enable it and set a key in the config.',
    ],
    'signals' => [
        'heading' => 'Content-Signal',
        'description' => 'A preference you state for crawlers, not a lock: honest bots follow it, the others have to be blocked at the CDN or server level. See contentsignals.org.',
        'keys' => [
            'search' => [
                'label' => 'Search',
                'help' => 'Building a search index and showing links and snippets in search results (not AI-generated summaries).',
            ],
            'ai-input' => [
                'label' => 'AI answers',
                'help' => 'Using pages as a real-time source for AI answers: AI overviews, retrieval-augmented generation, chat assistants and agents reading the site. Turning it off also stops AI assistants from using llms.txt instructions.',
            ],
            'ai-train' => [
                'label' => 'AI training',
                'help' => 'Training or fine-tuning AI models on the content.',
            ],
        ],
        'options' => [
            'yes' => 'Allowed',
            'no' => 'Disallowed',
            'unset' => 'Not specified',
        ],
    ],
];
