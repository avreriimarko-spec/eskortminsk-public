<?php
// webpage.php — Разметка WebPage для связи всех сущностей
defined('ABSPATH') || exit;

$site_url = home_url('/');
$url      = get_permalink(); 
$id       = get_queried_object_id();

// SEO-данные (используем ваши глобальные переменные)
$seo_title = (string) ($GLOBALS['seo_title'] ?? get_the_title($id));
$seo_descr = (string) ($GLOBALS['seo_descr'] ?? '');

// Даты для индексации
$date_published = $id ? get_post_time('c', true, $id) : '';
$date_modified  = $id ? get_post_modified_time('c', true, $id) : '';

$webpage = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebPage',
    '@id'      => rtrim($url, '/') . '#webpage',
    'url'      => $url,
    'isPartOf' => ['@type' => 'WebSite', '@id' => $site_url . '#website'],
    'publisher' => ['@id' => $site_url . '#organization'],
    'inLanguage' => get_bloginfo('language') ?: 'ru-RU',
];

if ($seo_title !== '') {
    $webpage['name'] = $seo_title;
}

if ($seo_descr !== '') {
    $webpage['description'] = wp_strip_all_tags($seo_descr);
}

if ($date_published) {
    $webpage['datePublished'] = $date_published;
}

if ($date_modified) {
    $webpage['dateModified'] = $date_modified;
}

// Критически важная связка: если это анкета, указываем, что Person — главная сущность этой страницы
if (is_singular('models')) {
    $webpage['mainEntity'] = ['@id' => rtrim($url, '/') . '#person'];
}

echo '<script type="application/ld+json">',
wp_json_encode($webpage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
'</script>';