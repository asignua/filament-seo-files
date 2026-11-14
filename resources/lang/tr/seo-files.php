<?php

declare(strict_types=1);

return [
    'llms' => [
        'sitemap_description' => 'Sitenin tüm URL’lerinin tam listesi',
    ],
    'actions' => [
        'generate' => 'Oluştur',
        'edit' => 'Düzenle',
        'edit_file' => ':file düzenle',
        'reset' => 'Şablona sıfırla',
        'save' => 'Kaydet',
        'saved' => 'Kaydedildi',
        'finished' => 'Komut çalıştırıldı',
        'failed' => 'Komut başarısız oldu',
        'generate_llms_warning' => 'Tüm dillerin llms.txt ve llms-full.txt dosyaları kaynaklardan yeniden oluşturulur. Düzenleyicide yapılan değişikliklerin yerine yenileri yazılır.',
        'scheduled_overwrite' => 'Dosyalar ayrıca her gün :time saatinde yeniden oluşturulur ve burada yapılan değişikliklerin yerine yenileri yazılır.',
    ],
    'fields' => [
        'language' => 'Dil',
        'title' => 'Başlık',
        'title_help' => 'Yalnızca yönetim paneli için bir etiket; site haritasına girmez.',
        'images' => 'Görseller',
        'images_help' => 'Satır başına bir görsel URL\'si (mutlak veya kökten yol). Görsel site haritasına girer.',
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
        'language_prefix' => 'Dil önekini eklemeyin, otomatik olarak eklenir.',
        'owned' => 'Bu adres zaten sitenin bir sayfasına ait.',
        'duplicate' => 'Başka bir manuel URL bu dilde zaten bu adrese sahip.',
    ],
    'page' => [
        'navigation' => 'SEO dosyaları',
        'title' => 'SEO dosyaları',
        'sitemap_help' => 'Arama motorları için dil sürümleriyle (hreflang) sitenin sayfa listesi.',
        'robots_help' => 'Tarayıcılar için kurallar. public/ içinde statik bir dosyadır: kaydedilene kadar sitede robots.txt yoktur.',
        'llms_help' => 'Yapay zekâ ajanları için sitenin kısa ve tam açıklaması, her dil için bir dosya çifti (:locales).',
        'generated_at' => 'Oluşturuldu: :time',
        'url_count' => 'URL sayısı: :count',
        'part_count' => 'parça: :count',
        'not_generated' => 'Henüz oluşturulmadı.',
        'file_exists' => 'Dosya diske kaydedildi.',
        'robots_missing' => 'Dosya henüz yok — önerilen şablonu yayımlamak için kaydedin.',
        'llms_languages' => 'Diller: :locales',
    ],
    'signals' => [
        'heading' => 'Content-Signal',
        'description' => 'Tarayıcı botlara bildirilen bir tercihtir, kilit değildir: dürüst botlar buna uyar, diğerlerinin CDN veya sunucu düzeyinde engellenmesi gerekir. Bkz. contentsignals.org.',
        'keys' => [
            'search' => [
                'label' => 'Arama',
                'help' => 'Arama dizini oluşturma ve arama sonuçlarında bağlantı ile özet gösterme (yapay zekâ özetleri değil).',
            ],
            'ai-input' => [
                'label' => 'Yapay zekâ yanıtları',
                'help' => 'Sayfaları yapay zekâ yanıtları için gerçek zamanlı kaynak olarak kullanma: yapay zekâ özetleri, geri getirmeyle zenginleştirilmiş üretim, sohbet asistanları ve siteyi okuyan ajanlar. Kapatmak, yapay zekâ asistanlarının llms.txt yönergelerini kullanmasını da engeller.',
            ],
            'ai-train' => [
                'label' => 'Yapay zekâ eğitimi',
                'help' => 'Yapay zekâ modellerini içerikle eğitme veya ince ayarlama.',
            ],
        ],
        'options' => [
            'yes' => 'İzin verildi',
            'no' => 'Yasak',
            'unset' => 'Belirtilmedi',
        ],
    ],
];
