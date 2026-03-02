<?php
/**
 * Component: model_card_home.php
 * Ожидает: $ID (int)
 * Опции:
 *  - $index           = int (Порядковый номер карточки в цикле, начинается с 0)
 */

defined('ABSPATH') || exit;

$ID = isset($ID) ? (int)$ID : 0;
if ($ID <= 0) return;

$index = $index ?? $args['index'] ?? 999;
$imgHover = 'transition-transform duration-300 will-change-transform md:group-hover:scale-105';

$loading_attr   = 'lazy';
$fetch_priority = 'auto';

if ($index === 0) {
    $loading_attr   = 'eager';
    $fetch_priority = 'high';
} elseif ($index < 4) {
    $loading_attr   = 'eager';
    $fetch_priority = 'auto';
}

$get_meta = static function(string $key) use ($ID) {
    $value = null;
    if (function_exists('get_field')) {
        $value = get_field($key, $ID);
    }
    if ($value === null || $value === '') {
        $value = get_post_meta($ID, $key, true);
    }
    return $value;
};

$name = trim(wp_strip_all_tags((string) $get_meta('name')));
$age  = trim(wp_strip_all_tags((string) $get_meta('age')));

$district_terms = wp_get_post_terms($ID, 'rayony');
$district = (!is_wp_error($district_terms) && !empty($district_terms)) ? $district_terms[0]->name : '';

$img_id = 0;
if (function_exists('get_field')) {
    $gallery = get_field('photo', $ID) ?: [];
    $first   = is_array($gallery) ? reset($gallery) : null;
    $img_id  = is_array($first) ? (int) ($first['ID'] ?? 0) : (int) $first;
}
if (!$img_id && has_post_thumbnail($ID)) {
    $img_id = (int) get_post_thumbnail_id($ID);
}

$alt_name  = trim((string) ($name ?: get_the_title($ID)));
$alt_parts = [];
if ($age !== '') $alt_parts[] = $age . ' лет';
if ($district !== '') $alt_parts[] = $district;
$alt = $alt_name . ' - Эскортница Астана' . ($alt_parts ? ', ' . implode(', ', $alt_parts) : '');
?>
<article class="group h-full rounded-lg border border-zinc-200 bg-white overflow-hidden">
    <a href="<?= esc_url(get_permalink($ID)) ?>" class="block focus:outline-none focus:ring-2 focus:ring-[#b50202]">
        <figure class="relative aspect-[3/4] w-full overflow-hidden bg-zinc-100">
            <?php
            if ($img_id) {
                $src_info = wp_get_attachment_image_src($img_id, 'medium_large');
                $srcset   = wp_get_attachment_image_srcset($img_id, 'medium_large');
                $sizes    = '(max-width: 768px) 50vw, 25vw';
                if (!empty($src_info[0])) {
                    $src = $src_info[0];
                    $w   = isset($src_info[1]) ? (int)$src_info[1] : null;
                    $h   = isset($src_info[2]) ? (int)$src_info[2] : null;
            ?>
                    <img
                        src="<?= esc_url($src) ?>"
                        <?= $srcset ? 'srcset="' . esc_attr($srcset) . '"' : '' ?>
                        <?= $srcset ? 'sizes="' . esc_attr($sizes) . '"' : '' ?>
                        <?= $w ? 'width="' . esc_attr($w) . '"' : '' ?>
                        <?= $h ? 'height="' . esc_attr($h) . '"' : '' ?>
                        alt="<?= esc_attr($alt) ?>"
                        loading="<?= esc_attr($loading_attr) ?>"
                        fetchpriority="<?= esc_attr($fetch_priority) ?>"
                        decoding="async"
                        class="w-full h-full object-cover <?= esc_attr($imgHover) ?>" />
            <?php
                } else {
                    echo '<div class="w-full h-full flex items-center justify-center text-sm text-zinc-500">Без фото</div>';
                }
            } else {
                echo '<div class="w-full h-full flex items-center justify-center text-sm text-zinc-500">Без фото</div>';
            }
            ?>
        </figure>

        <div class="p-3">
            <h3 class="text-base font-semibold text-black leading-snug">
                <?= esc_html($name ?: get_the_title($ID)) ?>
                <?php if ($age !== ''): ?>
                    <span class="text-[#b50202]">, <?= esc_html($age) ?></span>
                <?php endif; ?>
            </h3>
            <?php if ($district !== ''): ?>
                <p class="mt-1 text-sm text-zinc-600"><?= esc_html($district) ?></p>
            <?php endif; ?>
        </div>
    </a>
</article>
