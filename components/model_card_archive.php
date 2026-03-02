<?php
/**
 * Component: model_card_archive.php
 * Ожидает: $ID (int)
 * Опции:
 *  - $card_variant    = 'light'|'dark'
 *  - $card_hover_zoom = bool
 *  - $index           = int (Порядковый номер карточки в цикле, начинается с 0)
 */

defined('ABSPATH') || exit;

$ID = isset($ID) ? (int)$ID : 0;
if ($ID <= 0) return;

// Получаем переданные параметры или берем значения по умолчанию
// Поддержка передачи через include или через $args (get_template_part)
$card_variant    = $card_variant    ?? $args['card_variant']    ?? 'light';
$card_hover_zoom = $card_hover_zoom ?? $args['card_hover_zoom'] ?? true;
$index           = $index           ?? $args['index']           ?? 999;

$cardClasses = 'group h-full flex flex-col rounded-lg border overflow-hidden';
$cardClasses .= ($card_variant === 'dark')
    ? ' bg-black text-white border-zinc-800'
    : ' bg-white text-black border-zinc-200';

$columnClasses = ($card_variant === 'dark') ? 'bg-black text-white' : 'bg-white text-black';
$dividerBorder = ($card_variant === 'dark') ? 'border-zinc-800' : 'border-zinc-200';
$badgeClasses  = ($card_variant === 'dark') ? 'bg-zinc-900 text-white border-zinc-700' : 'bg-white/90 text-black border-zinc-200';
$labelMuted    = ($card_variant === 'dark') ? 'text-zinc-500' : 'text-zinc-600';
$labelStrong   = ($card_variant === 'dark') ? 'text-white' : 'text-black';
$chipText      = ($card_variant === 'dark') ? 'text-zinc-300' : 'text-zinc-700';
$chipBorder    = ($card_variant === 'dark') ? 'border-zinc-700' : 'border-zinc-200';
$buttonBase    = ($card_variant === 'dark') ? 'bg-zinc-900 text-white border-zinc-700' : 'bg-white text-black border-zinc-200';
$imgHover = $card_hover_zoom ? 'transition-transform duration-300 will-change-transform md:group-hover:scale-105' : '';
$focusRing = ($card_variant === 'dark') ? 'focus:ring-red-600' : 'focus:ring-[#b50202]';

// ===== ЛОГИКА ОПТИМИЗАЦИИ ЗАГРУЗКИ (Lazy Load) =====
$loading_attr   = 'lazy';
$fetch_priority = 'auto';

if ($index === 0) {
    $loading_attr   = 'eager';
    $fetch_priority = 'high';
} elseif ($index < 4) {
    $loading_attr   = 'eager';
    $fetch_priority = 'auto';
}

// ===== Данные =====
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

$name   = trim(wp_strip_all_tags((string) $get_meta('name')));
$age    = trim(wp_strip_all_tags((string) $get_meta('age')));
$height = trim(wp_strip_all_tags((string) $get_meta('height')));
$weight = trim(wp_strip_all_tags((string) $get_meta('weight')));
$bust   = trim(wp_strip_all_tags((string) $get_meta('bust')));

$price         = (int) $get_meta('price');
$price_outcall = (int) $get_meta('price_outcall');
$price_2h      = (int) $get_meta('price_2h');
$online        = (bool) $get_meta('online');

// VIP
$vip = false;
if (function_exists('get_field')) {
    $vip = (bool) get_field('vip_model', $ID);
}
if (!$vip) {
    $vip = (bool) get_post_meta($ID, 'vip_model', true);
}

// Описание (укороченное)
$raw_description = '';
if (function_exists('get_field')) {
    $raw_description = (string) get_field('text', $ID);
    if ($raw_description === '') {
        $raw_description = (string) get_field('description', $ID);
    }
}
if ($raw_description === '') {
    $raw_description = (string) get_post_meta($ID, 'text', true);
}
if ($raw_description === '') {
    $raw_description = (string) get_post_meta($ID, 'description', true);
}
if ($raw_description === '') {
    $raw_description = (string) get_post_field('post_content', $ID);
}

$raw_description = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($raw_description)));
$desc_length = (int) get_theme_mod('model_card_desc_length', 180);
if ($desc_length < 160) $desc_length = 160;
if ($desc_length > 200) $desc_length = 200;
$description = $raw_description !== '' ? wp_html_excerpt($raw_description, $desc_length, '') : '';

// Услуги (3-4 позиции)
$services_terms = wp_get_post_terms($ID, 'uslugi_tax');
$services = [];
if (!is_wp_error($services_terms)) {
    foreach ($services_terms as $term) {
        $seo_page = get_page_by_path($term->slug, OBJECT, 'seo_pages');
        $term_link = $seo_page ? get_permalink($seo_page) : home_url('/services/' . $term->slug . '/');
        $services[] = [
            'name' => $term->name,
            'url' => $term_link,
        ];
    }
}
$services = array_slice($services, 0, 4);

// Фото
$img_id = 0;
if (function_exists('get_field')) {
    $gallery = get_field('photo', $ID) ?: [];
    $first   = is_array($gallery) ? reset($gallery) : null;
    $img_id  = is_array($first) ? (int) ($first['ID'] ?? 0) : (int) $first;
}
if (!$img_id && has_post_thumbnail($ID)) {
    $img_id = (int) get_post_thumbnail_id($ID);
}

$photo_count = 0;
if (isset($gallery) && is_array($gallery)) {
    $photo_count = count($gallery);
}
if ($photo_count === 0 && $img_id) {
    $photo_count = 1;
}

$comments_count = (int) get_comments_number($ID);
$show_comments = comments_open($ID) || $comments_count > 0;

$tg = trim((string) get_theme_mod('contact_telegram'));
$wa = trim((string) get_theme_mod('contact_whatsapp'));
$tg_user = ltrim($tg, '@');
$wa_digits = preg_replace('/\D+/', '', $wa);
$wa_link = $wa_digits ? ('https://wa.me/' . $wa_digits) : '';

$incall_prices = [
    'hour' => $price,
    'two' => $price_2h ? $price_2h : ($price ? $price * 2 : 0),
];
$outcall_prices = [
    'hour' => $price_outcall,
    'two' => $price_2h ? $price_2h : ($price_outcall ? $price_outcall * 2 : 0),
];
$has_incall = (bool) array_filter($incall_prices);
$has_outcall = (bool) array_filter($outcall_prices);
$show_prices = $has_incall || $has_outcall;

$format_price = static function(int $value): string {
    return $value > 0 ? number_format_i18n($value) . ' Br' : '—';
};
?>
<article class="<?= esc_attr($cardClasses) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 h-full">
        <!-- Левая колонка -->
        <div class="flex flex-col h-full <?= esc_attr($columnClasses) ?>">
            <a href="<?= esc_url(get_permalink($ID)) ?>" class="block focus:outline-none focus:ring-2 <?= esc_attr($focusRing) ?>">
                <figure class="relative aspect-[3/4] w-full <?= esc_attr($columnClasses) ?> overflow-hidden">
                    <?php
                    $alt_name  = trim((string) ($name ?: get_the_title($ID)));
                    $alt_parts = [];
                    if ($age !== '')  $alt_parts[] = $age . ' лет';
                    if ($bust !== '') $alt_parts[] = 'размер груди ' . $bust;
                    $alt = $alt_name . ' - Эскортница Астана' . ($alt_parts ? ', ' . implode(', ', $alt_parts) : '');

                    if ($img_id) {
                        $src_info = wp_get_attachment_image_src($img_id, 'medium_large');
                        $srcset   = wp_get_attachment_image_srcset($img_id, 'medium_large');
                        $sizes    = '(max-width: 768px) 100vw, 50vw';
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

                    <?php if ($photo_count > 0): ?>
                        <span class="absolute top-2 left-2 text-xs font-semibold <?= esc_attr($badgeClasses) ?> px-2 py-1 rounded border">
                            Фото: <?= esc_html($photo_count) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($show_comments): ?>
                        <span class="absolute bottom-2 left-2 text-xs font-semibold <?= esc_attr($badgeClasses) ?> px-2 py-1 rounded border">
                            Отзывы: <?= esc_html($comments_count) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($online): ?>
                        <span class="absolute top-2 right-2 h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-black/10"></span>
                    <?php endif; ?>

                    <?php if ($vip): ?>
                        <span class="absolute bottom-3 left-1/2 -translate-x-1/2 inline-flex items-center whitespace-nowrap px-6 py-1 text-xs font-semibold bg-[#b50202] text-white rounded-full">
                            VIP&nbsp;Модель
                        </span>
                    <?php endif; ?>
                </figure>
            </a>

            <?php if ($wa_link || $tg_user): ?>
                <div class="px-3 py-3 border-t <?= esc_attr($dividerBorder) ?> <?= esc_attr($columnClasses) ?>">
                    <div class="flex gap-2">
                        <?php if ($wa_link): ?>
                            <div data-go="wa" class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border <?= esc_attr($buttonBase) ?> px-3 py-2 text-xs font-semibold">
                                <span class="text-[#25D366]">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                </span>
                                WhatsApp
                            </div>
                        <?php endif; ?>

                        <?php if ($tg_user): ?>
                            <div data-go="tg" class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border <?= esc_attr($buttonBase) ?> px-3 py-2 text-xs font-semibold">
                                <span class="text-[#229ED9]">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                    </svg>
                                </span>
                                Telegram
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Правая колонка -->
        <div class="p-3 flex flex-col gap-3 <?= esc_attr($columnClasses) ?> h-full">
            <a href="<?= esc_url(get_permalink($ID)) ?>" class="block focus:outline-none focus:ring-2 <?= esc_attr($focusRing) ?>">
                <div class="border-b <?= esc_attr($dividerBorder) ?> pb-2">
                    <h3 class="text-base font-semibold leading-snug">
                        <?= esc_html($name ?: get_the_title($ID)) ?>
                    </h3>
                </div>
            </a>

            <?php
            $info_items = [];
            if ($height !== '') $info_items[] = ['Рост', $height . ' см'];
            if ($age !== '')    $info_items[] = ['Возраст', $age];
            if ($weight !== '') $info_items[] = ['Вес', $weight . ' кг'];
            if ($bust !== '')   $info_items[] = ['Грудь', $bust];
            ?>
            <?php if ($info_items): ?>
                <div class="grid grid-cols-2 gap-2 text-sm <?= esc_attr($labelMuted) ?>">
                    <?php foreach ($info_items as [$label, $value]): ?>
                        <div class="flex items-center justify-between border-b <?= esc_attr($dividerBorder) ?> pb-1">
                            <span class="<?= esc_attr($labelMuted) ?>"><?= esc_html($label) ?>:</span>
                            <span class="font-semibold <?= esc_attr($labelStrong) ?>"><?= esc_html($value) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($services): ?>
                <div class="border-b <?= esc_attr($dividerBorder) ?> pb-2">
                    <div class="text-sm font-semibold <?= esc_attr($labelStrong) ?>">Услуги</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php foreach ($services as $service): ?>
                            <a href="<?= esc_url($service['url']) ?>" class="text-xs px-2 py-1 rounded border <?= esc_attr($chipBorder) ?> <?= esc_attr($chipText) ?>">
                                <?= esc_html($service['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($show_prices): ?>
                <div class="border-b <?= esc_attr($dividerBorder) ?> pb-2">
                    <div class="text-sm font-semibold <?= esc_attr($labelStrong) ?>">Стоимость</div>
                    <div class="mt-2 flex text-xs <?= esc_attr($labelMuted) ?>">
                        <div class="flex-1"></div>
                        <div class="flex-1 text-center">Час</div>
                        <div class="flex-1 text-center">Два</div>
                    </div>

                    <?php if ($has_outcall): ?>
                        <div class="mt-1 flex text-sm">
                            <div class="flex-1 <?= esc_attr($labelMuted) ?>">Выезд</div>
                            <div class="flex-1 text-center"><?= esc_html($format_price($outcall_prices['hour'])) ?></div>
                            <div class="flex-1 text-center"><?= esc_html($format_price($outcall_prices['two'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($has_incall): ?>
                        <div class="mt-1 flex text-sm border-t <?= esc_attr($dividerBorder) ?> pt-1">
                            <div class="flex-1 <?= esc_attr($labelMuted) ?>">У себя</div>
                            <div class="flex-1 text-center"><?= esc_html($format_price($incall_prices['hour'])) ?></div>
                            <div class="flex-1 text-center"><?= esc_html($format_price($incall_prices['two'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($description !== ''): ?>
                <details class="text-sm <?= esc_attr($labelMuted) ?>">
                    <summary class="cursor-pointer text-sm font-semibold <?= esc_attr($labelStrong) ?>">Обо мне</summary>
                    <p class="mt-2"><?= esc_html($description) ?></p>
                </details>
            <?php endif; ?>
        </div>
    </div>
</article>
