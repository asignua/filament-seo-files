<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Sitenin tüm URL’lerinin tam listesi',
    ],
    'actions' => [
        'run' => 'Çalıştır',
        'edit' => 'Düzenle',
        'reset' => 'Şablona sıfırla',
        'saved' => 'Kaydedildi',
        'finished' => 'Komut çalıştırıldı',
        'failed' => 'Komut başarısız oldu',
    ],
    'fields' => [
        'language' => 'Dil',
        'title' => 'Başlık',
        'title_help' => 'Yalnızca yönetim paneli için bir etiket; site haritasına girmez.',
        'url' => 'Adres',
        'url_help' => 'Dil öneki olmadan site kökünden bir yol (search, feeds/news) veya https:// ile tam bir adres.',
        'active' => 'Etkin',
        'updated' => 'Güncellendi',
        'link' => 'Ortaya çıkan adres',
    ],
    'resource' => [
        'navigation' => 'Site haritası URL’leri',
        'singular' => 'Site haritası URL’si',
        'plural' => 'Site haritası URL’leri',
        'general' => 'Genel',
    ],
    'validation' => [
        'spaces' => 'Adres boşluk içeremez.',
        'invalid' => 'Geçersiz adres.',
        'scheme_or_path' => 'Şemalı tam bir adres veya şemasız bir yol girin.',
        'home' => 'Ana sayfa zaten site haritasında.',
        'language_prefix' => 'Dil önekini eklemeyin, otomatik olarak eklenir.',
        'owned' => 'Bu adres zaten sitenin bir sayfasına ait.',
    ],
    'page' => [
        'navigation' => 'SEO dosyaları',
        'title' => 'SEO dosyaları',
        'sitemap_help' => 'Arama motorları için dil sürümleriyle (hreflang) sitenin sayfa listesi.',
        'robots_help' => 'Tarayıcılar için kurallar. Dosya kaydedilene kadar önerilen şablon sunulur.',
        'llms_help' => 'Yapay zekâ ajanları için sitenin kısa ve tam açıklaması, her dil için bir dosya çifti (:locales).',
        'generated_at' => 'Oluşturuldu: :time',
        'url_count' => 'URL sayısı: :count',
        'part_count' => 'parça: :count',
        'not_generated' => 'Henüz oluşturulmadı.',
        'file_exists' => 'Dosya diske kaydedildi.',
        'template_served' => 'Dosya henüz yok — önerilen şablon sunuluyor.',
    ],
];
