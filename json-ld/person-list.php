<?php
// person-list.php — Безопасная генерация ItemList
defined('ABSPATH') || exit;

// Используем локальную переменную, чтобы не трогать глобальную $posts
$items_to_process = [];

if (is_front_page()) {
    // Получаем только ID и нужные данные, не меняя глобальный цикл
    $items_to_process = get_posts([
        'post_type'      => 'models',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'suppress_filters' => true // ускоряет и делает запрос чище
    ]);
} else {
    global $wp_query;
    $items_to_process = $wp_query->posts;
}

if (empty($items_to_process)) {
    return;
}

$items = [];
$position = 1;

foreach ($items_to_process as $item_obj) {
    // Работаем напрямую с ID объекта, не используя get_the_ID()
    $item_id = $item_obj->ID;
    
    // Получаем данные анкеты по конкретному ID
    $name  = get_field('name', $item_id) ?: get_the_title($item_id);
    $url   = get_permalink($item_id);
    $desc  = get_field('description', $item_id) ?: get_the_excerpt($item_id);
    
    // Фото
    $gallery = get_field('photo', $item_id) ?: [];
    $image_url = get_stylesheet_directory_uri() . '/assets/img/placeholder-thumb.webp';
    
    if (!empty($gallery)) {
        $first = is_array($gallery) ? $gallery[0] : $gallery;
        if (is_array($first) && !empty($first['sizes']['large'])) {
            $image_url = $first['sizes']['large'];
        } elseif (is_numeric($first)) {
            $src = wp_get_attachment_image_src((int)$first, 'large');
            if ($src) $image_url = $src[0];
        }
    }

    $items[] = [
        "@type" => "ListItem",
        "position" => $position,
        "item" => [
            "@type" => "Person",
            "name" => esc_attr($name),
            "url" => esc_url($url),
            "image" => esc_url($image_url),
            "description" => wp_trim_words(wp_strip_all_tags((string)$desc), 20)
        ]
    ];
    $position++;
}

// Выводим только если список не пуст
if (!empty($items)) {
    $list_schema = [
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "itemListElement" => $items
    ];

    echo '<script type="application/ld+json">',
    wp_json_encode($list_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
    '</script>';
}

// На всякий случай сбрасываем, хотя get_posts не должен ломать, если не было setup_postdata
wp_reset_postdata();