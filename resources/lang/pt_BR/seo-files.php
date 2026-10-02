<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Lista completa das URLs do site',
    ],
    'actions' => [
        'run' => 'Executar',
        'generate' => 'Gerar',
        'edit' => 'Editar',
        'edit_file' => 'Editar :file',
        'reset' => 'Restaurar o modelo',
        'save' => 'Salvar',
        'saved' => 'Salvo',
        'finished' => 'Comando executado',
        'failed' => 'O comando falhou',
    ],
    'fields' => [
        'language' => 'Idioma',
        'title' => 'Título',
        'title_help' => 'Um rótulo apenas para o painel; não vai para o sitemap.',
        'url' => 'Endereço',
        'url_help' => 'Um caminho a partir da raiz do site sem prefixo de idioma (search, feeds/news) ou um endereço completo com https://.',
        'active' => 'Ativo',
        'updated' => 'Atualizado',
        'link' => 'Endereço resultante',
    ],
    'resource' => [
        'navigation' => 'URLs do sitemap',
        'singular' => 'URL do sitemap',
        'plural' => 'URLs do sitemap',
        'general' => 'Geral',
    ],
    'validation' => [
        'spaces' => 'O endereço não pode conter espaços.',
        'invalid' => 'Endereço inválido.',
        'scheme_or_path' => 'Informe um endereço completo com esquema ou um caminho sem esquema.',
        'home' => 'A página inicial já está no sitemap.',
        'language_prefix' => 'Não adicione o prefixo de idioma, ele é adicionado automaticamente.',
        'owned' => 'Este endereço já pertence a uma página do site.',
    ],
    'page' => [
        'navigation' => 'Arquivos SEO',
        'title' => 'Arquivos SEO',
        'sitemap_help' => 'A lista de páginas do site para os mecanismos de busca, com versões por idioma (hreflang).',
        'robots_help' => 'As regras para os rastreadores. É um arquivo estático em public/: enquanto não for salvo, o site não tem robots.txt.',
        'llms_help' => 'Descrição curta e completa do site para agentes de IA, um par de arquivos por idioma (:locales).',
        'generated_at' => 'Gerado: :time',
        'url_count' => 'URLs: :count',
        'part_count' => 'partes: :count',
        'not_generated' => 'Ainda não gerado.',
        'file_exists' => 'O arquivo está salvo no disco.',
        'robots_missing' => 'O arquivo ainda não existe: salve-o para publicar o modelo recomendado.',
        'llms_languages' => 'Idiomas: :locales',
    ],
];
