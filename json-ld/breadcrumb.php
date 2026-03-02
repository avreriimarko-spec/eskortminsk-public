<?php
// breadcrumb.php — JSON-LD разметка хлебных крошек
defined('ABSPATH') || exit;

if (is_front_page()) return;

$site_url = home_url('/');
$url      = get_permalink();
$id       = get_queried_object_id();

$current_name = '';
if (is_singular()) {
    $current_name = get_the_title($id);
} elseif (is_category() || is_tag() || is_tax()) {
    $current_name = single_term_title('', false);
} else {
    $current_name = wp_get_document_title();
}

$breadcrumb = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    '@id'             => rtrim($url, '/') . '#breadcrumb',
    'itemListElement' => [
        [
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Главная',
            'item'     => $site_url,
        ],
        [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => wp_strip_all_tags($current_name),
            'item'     => $url,
        ],
    ],
];

echo '<script type="application/ld+json">',
wp_json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
'</script>';