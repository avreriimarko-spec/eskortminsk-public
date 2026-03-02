<?php
// Вставьте в single-models.php (после the_content или в <head> через get_template_part)
defined('ABSPATH') || exit;

if (!is_singular('models')) {
    return;
}

$id     = get_the_ID();
$name   = get_field('name', $id) ?: get_the_title($id);
$url    = get_permalink($id);
$gender = 'https://schema.org/Female';

// --- 1) Фото модели (large) ---
$placeholder = get_stylesheet_directory_uri() . '/assets/img/placeholder-thumb.webp';
$gallery     = get_field('photo', $id) ?: [];
$images      = [];

if (is_array($gallery) && !empty($gallery)) {
    foreach ($gallery as $img) {
        if (is_array($img)) {
            if (!empty($img['sizes']['large'])) {
                $images[] = esc_url($img['sizes']['large']);
            } elseif (!empty($img['url'])) {
                $images[] = esc_url($img['url']);
            }
        } elseif (is_numeric($img)) {
            $src = wp_get_attachment_image_src((int)$img, 'large');
            if (!empty($src[0])) {
                $images[] = esc_url($src[0]);
            } else {
                $u = wp_get_attachment_url((int)$img);
                if ($u) $images[] = esc_url($u);
            }
        }
    }
}
if (empty($images)) {
    $images[] = esc_url($placeholder);
}
$images = array_values(array_unique(array_filter($images)));

// --- 2) Описание ---
$raw_desc    = get_the_excerpt($id) ?: get_field('description', $id);
$description = html_entity_decode(wp_strip_all_tags((string)$raw_desc), ENT_QUOTES | ENT_HTML5, 'UTF-8');

// --- 3) birthDate по возрасту (если есть) ---
$birthDate = null;
$age_field = get_field('age', $id);
if ($age_field && is_numeric($age_field)) {
    $y = date('Y') - (int)$age_field;
    $m = str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT);
    $d = str_pad((string)rand(1, 28), 2, '0', STR_PAD_LEFT);
    $birthDate = "{$y}-{$m}-{$d}";
}

// --- 4) Параметры ---
$height = get_field('height', $id);
$weight = get_field('weight', $id);
$bust   = get_field('bust',   $id);

// --- 5) Цены (Br) ---
$price1h = (float)(get_field('price', $id) ?: 0);
$offers  = [];
if ($price1h > 0) {
    $offers[] = [
        "@type" => "Offer",
        "itemOffered" => [
            "@type" => "Service",
            "name"  => "Стоимость услуг за 1 час",
            "description" => "Стоимость одного часа."
        ],
        "priceSpecification" => [
            "@type"         => "UnitPriceSpecification",
            "price"         => (string)$price1h,
            "priceCurrency" => "Br",
            "unitText"      => "час"
        ],
        "url" => "{$url}#1h"
    ];

    $offers[] = [
        "@type" => "Offer",
        "itemOffered" => [
            "@type" => "Service",
            "name"  => "Цена услуг 2 часа",
            "description" => "Стоимость двух часов."
        ],
        "priceSpecification" => [
            "@type"         => "UnitPriceSpecification",
            "price"         => (string)($price1h * 2),
            "priceCurrency" => "Br",
            "unitText"      => "2 часа"
        ],
        "url" => "{$url}#2h"
    ];
}

// --- 6) Похожие (knowsAbout) — до 4 случайных ---
$related_json = [];
$related_query = new WP_Query([
    'post_type'      => 'models',
    'post__not_in'   => [$id],
    'posts_per_page' => 8,
    'orderby'        => 'rand',
    'post_status'    => 'publish',
    'no_found_rows'  => true,
    'fields'         => 'ids',
]);
if ($related_query->have_posts()) {
    foreach ($related_query->posts as $ri) {
        $rg = get_field('photo', $ri) ?: [];
        $rimg = '';
        if (is_array($rg) && !empty($rg[0])) {
            $first = $rg[0];
            if (is_array($first) && !empty($first['sizes']['large'])) {
                $rimg = esc_url($first['sizes']['large']);
            } elseif (is_array($first) && !empty($first['url'])) {
                $rimg = esc_url($first['url']);
            } elseif (is_numeric($first)) {
                $rsrc = wp_get_attachment_image_src((int)$first, 'large');
                if (!empty($rsrc[0])) $rimg = esc_url($rsrc[0]);
            }
        }
        if ($rimg === '') $rimg = esc_url($placeholder);

        $related_json[] = [
            "@type" => "Person",
            "@id"   => get_permalink($ri) . "#person",
            "name"  => get_field('name', $ri) ?: get_the_title($ri),
            "url"   => get_permalink($ri),
            "image" => $rimg,
        ];
    }
    wp_reset_postdata();
}

// --- 7) Сборка Person ---
$schema = [
    "@context"    => "https://schema.org",
    "@type"       => "Person",
    "@id"         => "{$url}#person",
    "name"        => $name,
    "url"         => $url,
    "image"       => $images,
    "description" => $description,
    "gender"      => $gender,
];

// Опциональные поля
if (!empty($birthDate)) {
    $schema["birthDate"] = $birthDate;
}
if (!empty($height)) {
    $schema["height"] = [
        "@type"    => "QuantitativeValue",
        "value"    => (string)$height,
        "unitCode" => "CMT",
    ];
}
if (!empty($weight)) {
    $schema["weight"] = [
        "@type"    => "QuantitativeValue",
        "value"    => (string)$weight,
        "unitCode" => "KGM",
    ];
}
$additional = [];
if (!empty($bust)) {
    $additional[] = [
        "@type" => "PropertyValue",
        "name"  => "Размер груди",
        "value" => (string)$bust,
    ];
}
if (!empty($additional)) {
    $schema["additionalProperty"] = $additional;
}
if (!empty($offers)) {
    $schema["hasOfferCatalog"] = [
        "@type"           => "OfferCatalog",
        "name"            => "Услуги и цены",
        "description"     => "Перечень услуг с указанием тарифов.",
        "itemListElement" => $offers,
    ];
}
if (!empty($related_json)) {
    $schema["knowsAbout"] = $related_json;
}

// --- 8) Вывод JSON-LD ---
echo '<script type="application/ld+json">',
wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
'</script>';
