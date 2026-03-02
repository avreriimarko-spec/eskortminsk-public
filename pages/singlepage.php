<?php
/* Template Name: Страница модели
 * Template Post Type: models
 */
get_header();

$ID = get_the_ID();

/** ACF поля */
$gallery = get_field('photo', $ID) ?: [];
$video_file = get_field('video', $ID); 

$name    = get_field('name',  $ID) ?: get_the_title($ID);
$age     = (int) get_field('age',   $ID);
$height  = trim((string) get_field('height', $ID));
$weight  = trim((string) get_field('weight', $ID));
$bust    = trim((string) get_field('bust',   $ID));

// Цены
$price   = (int) get_field('price', $ID);
$price_outcall = (int) get_field('price_outcall', $ID);
$price2h = (int) get_field('price_2h', $ID);
$priceN  = (int) get_field('price_night', $ID);

if (!$price2h && $price) $price2h = $price * 2;
if (!$priceN  && $price) $priceN  = $price * 8;

$has_incall  = ($price > 0);
$has_outcall = ($price_outcall > 0);

$telegram = get_theme_mod('contact_telegram');
$whatsapp = get_theme_mod('contact_whatsapp');
$phone    = get_theme_mod('contact_number');
$tg_channel = get_theme_mod('contact_telegram_channel');

// Хелперы URL
if (!function_exists('kz_model_param_slug_by_value')) {
    function kz_model_param_slug_by_value($type, $value) {
        $value = (int) preg_replace('/\D+/', '', (string) $value);
        if ($value <= 0) return '';
        $maps = [
            'age' => [['min' => 18, 'max' => 20, 'slug' => 'vozrast-18-20'], ['min' => 21, 'max' => 25, 'slug' => 'vozrast-21-25'], ['min' => 26, 'max' => 29, 'slug' => 'vozrast-26-29'], ['min' => 30, 'max' => 39, 'slug' => 'vozrast-30-39']],
            'height' => [['min' => 156, 'max' => 160, 'slug' => 'rost-156-160-sm'], ['min' => 161, 'max' => 165, 'slug' => 'rost-161-165-sm'], ['min' => 166, 'max' => 170, 'slug' => 'rost-166-170-sm'], ['min' => 171, 'max' => 175, 'slug' => 'rost-171-175-sm']],
            'weight' => [['min' => 46, 'max' => 50, 'slug' => 'ves-46-50-kg'], ['min' => 51, 'max' => 55, 'slug' => 'ves-51-55-kg'], ['min' => 56, 'max' => 60, 'slug' => 'ves-56-60-kg'], ['min' => 61, 'max' => 65, 'slug' => 'ves-61-65-kg']],
        ];
        if ($type === 'bust') {
            if ($value <= 1) return 'razmer-grudi-1';
            if ($value === 2) return 'razmer-grudi-2';
            if ($value === 3) return 'razmer-grudi-3';
            return 'razmer-grudi-bolshe-4';
        }
        if (!isset($maps[$type])) return '';
        foreach ($maps[$type] as $range) { if ($value >= $range['min'] && $value <= $range['max']) return $range['slug']; }
        return '';
    }
}
if (!function_exists('kz_model_param_url')) {
    function kz_model_param_url($slug) { return $slug ? user_trailingslashit(home_url('/' . trim($slug, '/'))) : ''; }
}

$age_slug    = kz_model_param_slug_by_value('age', $age);
$height_slug = kz_model_param_slug_by_value('height', $height);
$weight_slug = kz_model_param_slug_by_value('weight', $weight);
$bust_slug   = kz_model_param_slug_by_value('bust', $bust);

$services_terms = wp_get_post_terms($ID, 'uslugi_tax');
$district_terms = wp_get_post_terms($ID, 'rayony');
$metro_terms = wp_get_post_terms($ID, 'metro');
$hair_terms     = wp_get_post_terms($ID, 'cvet_volos_tax');

if (is_wp_error($services_terms)) $services_terms = [];
if (is_wp_error($district_terms)) $district_terms = [];
if (is_wp_error($metro_terms)) $metro_terms = [];
if (is_wp_error($hair_terms)) $hair_terms = [];

$age_str = '';
if ($age > 0) {
    if ($age % 10 === 1 && $age % 100 !== 11)       $age_str = "$age год";
    elseif (in_array($age % 10, [2, 3, 4], true) && !in_array($age % 100, [12, 13, 14], true)) $age_str = "$age года";
    else                                            $age_str = "$age лет";
}

// ------------------------------------------------------------------
// ПОДГОТОВКА МЕДИА (Видео + Фото)
// ------------------------------------------------------------------
$media_items = [];

// 1. ВИДЕО
if ($video_file) {
    $vid_url = '';
    if (is_numeric($video_file)) {
        $vid_url = wp_get_attachment_url($video_file);
    } elseif (is_array($video_file)) {
        $vid_url = $video_file['url'] ?? '';
    } else {
        $vid_url = $video_file;
    }

    if ($vid_url) {
        $media_items[] = [
            'type' => 'video',
            'src'  => $vid_url,
            'alt'  => "Видео {$name}"
        ];
    }
}

// 2. ФОТО
if ($gallery) {
    foreach ($gallery as $img) {
        $img_id = is_array($img) ? $img['ID'] : (int)$img;
        $url_full = wp_get_attachment_image_url($img_id, 'full');
        $url_thumb = wp_get_attachment_image_url($img_id, 'thumbnail'); 
        $url_large = wp_get_attachment_image_url($img_id, 'large');
        if (!$url_large) $url_large = $url_full;
        if (!$url_thumb) $url_thumb = $url_large ?: $url_full;
        
        $parts  = array_filter([
            "{$name} - Эскортница Астаны",
            $bust ? "грудь {$bust}" : '',
            $height ? "рост {$height} см" : '',
            $weight ? "вес {$weight} кг" : '',
        ]);
        $alt = implode(', ', $parts);

        if ($url_full) {
            $media_items[] = [
                'type'      => 'image',
                'src'       => $url_full,
                'src_large' => $url_large ?: $url_full,
                'thumb'     => $url_thumb ?: $url_full,
                'alt'       => $alt
            ];
        }
    }
}

$js_gallery_data = array_map(function($item) {
    return [
        'type' => $item['type'],
        'src'  => $item['src']
    ];
}, $media_items);

// Текст H1
$h1_text = "Эскорт модель {$name} — ID: {$ID}";
$h1_classes = "text-2xl sm:text-3xl md:text-4xl font-bold flex items-center gap-3";

ob_start();
?>
    <span><?= esc_html($h1_text) ?></span>
    <button type="button" class="js-fav mr-2 inline-flex items-center justify-center w-10 h-8 rounded-full border border-[#b50202] text-[#b50202] hover:bg-[#b50202]/10 focus:outline-none focus:ring-1 focus:ring-[#b50202] transition" data-model-id="<?= (int)$ID; ?>" aria-label="Добавить в избранное">
        <svg class="heart-outline w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61c-1.54-1.36-3.97-1.21-5.44.3L12 8.11 8.6 4.91c-1.47-1.51-3.9-1.66-5.44-.3-1.79 1.58-1.9 4.31-.24 6.02C5.1 13.06 8.55 15.94 12 19c3.45-3.06 6.9-5.94 9.08-8.37 1.66-1.71 1.55-4.44-.24-6.02z" /></svg>
        <svg class="heart-solid w-5 h-5 hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 3.75 7 3.75c1.9 0 3.37.9 4.5 2.25C12.63 4.65 14.1 3.75 16 3.75c3 0 5 2.25 5 4.75 0 3.78-3.4 6.86-8.55 11.55L12 21.35z" /></svg>
    </button>
<?php
$h1_inner_html = ob_get_clean();
?>

<main class="max-w-6xl mx-auto px-4 md:px-0 bg-white text-black pb-10" style="margin-top: var(--site-header-h, 110px);">
  
    <?php include get_template_directory() . '/components/breadcrumbs.php'; ?>

    <div class="md:hidden mt-4">
        <header class="mb-4">
            <h1 class="<?= $h1_classes ?>">
                <?= $h1_inner_html ?>
            </h1>
            <?php if ($price): ?>
                <div class="mt-2 text-lg font-semibold md:hidden">
                    <span class="text-[#b50202]"><?= esc_html(number_format_i18n($price)) ?> Br</span>
                    <span class="text-zinc-600">/ час</span>
                </div>
            <?php endif; ?>
        </header>
    </div>

   
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
        
        
        <section class="model-gallery">
            <?php if (!empty($media_items)): ?>
                <div class="relative group">
                    <div class="swiper main-slider mb-3" style="aspect-ratio: 4 / 5; width: 100%;">
                        <div class="swiper-wrapper">
                            <?php foreach ($media_items as $i => $item): 
                                $is_first = ($i === 0);
                                $loading_attr = $is_first ? 'eager' : 'lazy';
                                $fetch_priority = $is_first ? 'high' : 'auto';
                            ?>
                                <div class="swiper-slide h-full cursor-zoom-in relative bg-black" onclick="openLightbox(<?= $i ?>)">
                                    
                                    <?php if ($item['type'] === 'video'): ?>
                                      
                                        <div class="w-full h-full flex items-center justify-center bg-zinc-900 rounded-lg overflow-hidden">
                                            <video 
                                                src="<?= esc_url($item['src']) ?>" 
                                                class="w-full h-full object-contain pointer-events-none"
                                                muted 
                                                playsinline
                                                loop
                                                preload="metadata"
                                                <?php if($is_first) echo 'autoplay'; ?> 
                                            ></video>
                                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                <div class="w-16 h-16 bg-[#b50202]/80 rounded-full flex items-center justify-center shadow-lg backdrop-blur-sm">
                                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                </div>
                                            </div>
                                        </div>

                                    <?php else: ?>
                                   
                                        <img 
                                            src="<?= esc_url($item['src_large']) ?>" 
                                            alt="<?= esc_attr($item['alt']) ?>"
                                            loading="<?= $loading_attr ?>"
                                            fetchpriority="<?= $fetch_priority ?>"
                                            class="w-full h-full object-cover rounded-lg select-none"
                                            style="object-position: 50% 20%;"
                                        >
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition duration-300 bg-black/20 pointer-events-none">
                                             <svg class="w-12 h-12 text-white/80 drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-button-prev !text-[#b50202]"></div>
                        <div class="swiper-button-next !text-[#b50202]"></div>
                    </div>

                    
                    <button id="orderOpen" type="button" class="hidden absolute right-3 top-3 md:right-5 md:top-5 z-10 inline-flex items-center gap-2 rounded-full border border-[#b50202] bg-white/80 backdrop-blur px-3 py-1.5 text-sm md:text-base text-[#b50202] hover:bg-[#b50202] hover:text-white transition">
                        <svg class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-9M20 12h-9M20 17h-9M7 7h.01M7 12h.01M7 17h.01" /></svg>
                        Заказать сейчас
                    </button>
                </div>

               
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <?php foreach ($media_items as $i => $item): ?>
                        <div data-index="<?= esc_attr($i) ?>" 
                             class="thumb-image relative w-20 h-20 rounded-md cursor-pointer border border-zinc-200 hover:ring-1 hover:ring-[#b50202] transition overflow-hidden bg-zinc-100 flex-shrink-0">
                            <?php if ($item['type'] === 'video'): ?>
                                <div class="w-full h-full flex items-center justify-center bg-zinc-800">
                                    <svg class="w-8 h-8 text-white/70" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            <?php else: ?>
                                <img src="<?= esc_url($item['thumb']) ?>" class="w-full h-full object-cover" alt="thumb">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="w-full h-[460px] bg-zinc-100 text-zinc-500 flex items-center justify-center rounded-lg">Нет фотографий</div>
            <?php endif; ?>
        </section>

        
        <section class="space-y-6">
            <div class="hidden md:block">
                <header class="mb-0">
                    <div class="<?= $h1_classes ?>" role="heading" aria-level="1">
                        <?= $h1_inner_html ?>
                    </div>
                    <?php if ($price): ?>
                        <div class="mt-2 text-lg font-semibold">
                            <span class="text-[#b50202]"><?= esc_html(number_format_i18n($price)) ?> Br</span>
                            <span class="text-zinc-600">/ час</span>
                        </div>
                    <?php endif; ?>
                </header>
            </div>

            
            <section class="border border-zinc-200 rounded-lg p-4 bg-white">
                <h2 class="text-xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3">Параметры</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <?php if ($has_incall || $has_outcall): ?>
                        <div class="col-span-1 sm:col-span-2 flex items-start gap-2 border-b border-zinc-200 pb-3 mb-1">
                             <span class="text-zinc-600 min-w-[90px]">Доступность:</span>
                             <div class="flex flex-wrap gap-2 font-medium text-black">
                                <?php if ($has_incall): ?><a href="/escort-priem/" class="inline-flex items-center gap-1 text-[#b50202] hover:text-[#8d0202] underline decoration-dotted">Апартаменты</a><?php endif; ?>
                                <?php if ($has_incall && $has_outcall): ?><span class="text-zinc-500">/</span><?php endif; ?>
                                <?php if ($has_outcall): ?><a href="/escort-na-vyyezd/" class="inline-flex items-center gap-1 text-[#b50202] hover:text-[#8d0202] underline decoration-dotted">Выезд</a><?php endif; ?>
                             </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($age_str): ?><div><span class="text-zinc-600">Возраст:</span> <span class="font-medium text-black"><?= ($age_slug && ($seo_page = get_page_by_path($age_slug, OBJECT, 'seo_vozrast_pages'))) ? '<a href="'.esc_url(get_permalink($seo_page)).'" class="text-[#b50202] hover:text-[#8d0202] underline decoration-dotted decoration-1">'.esc_html($age_str).'</a>' : esc_html($age_str) ?></span></div><?php endif; ?>
                    <?php if ($bust): ?><div><span class="text-zinc-600">Размер груди:</span> <span class="font-medium text-black"><?= ($bust_slug && ($seo_page = get_page_by_path($bust_slug, OBJECT, 'seo_bust_pages'))) ? '<a href="'.esc_url(get_permalink($seo_page)).'" class="text-[#b50202] hover:text-[#8d0202] underline decoration-dotted decoration-1">'.esc_html($bust).'</a>' : esc_html($bust) ?></span></div><?php endif; ?>
                    <?php if ($height): ?><div><span class="text-zinc-600">Рост:</span> <span class="font-medium text-black"><?= ($height_slug && ($seo_page = get_page_by_path($height_slug, OBJECT, 'seo_rost_pages'))) ? '<a href="'.esc_url(get_permalink($seo_page)).'" class="text-[#b50202] hover:text-[#8d0202] underline decoration-dotted decoration-1">'.esc_html($height).' см</a>' : esc_html($height).' см' ?></span></div><?php endif; ?>
                    <?php if ($weight): ?><div><span class="text-zinc-600">Вес:</span> <span class="font-medium text-black"><?= ($weight_slug && ($seo_page = get_page_by_path($weight_slug, OBJECT, 'seo_ves_pages'))) ? '<a href="'.esc_url(get_permalink($seo_page)).'" class="text-[#b50202] hover:text-[#8d0202] underline decoration-dotted decoration-1">'.esc_html($weight).' кг</a>' : esc_html($weight).' кг' ?></span></div><?php endif; ?>
                </div>
            </section>

            
            <?php if ($services_terms || $district_terms || $metro_terms || $hair_terms): ?>
                <section class="border border-zinc-200 rounded-lg p-4 bg-white">
                    <h2 class="text-xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3">Категории</h2>
                    <div class="space-y-3 text-sm">
                        <?php
                        if ($services_terms) {
                            foreach ($services_terms as $term) {
                                $seo_page = get_page_by_path($term->slug, OBJECT, 'seo_pages');
                                $term_link = $seo_page ? get_permalink($seo_page) : home_url('/services/' . $term->slug . '/');
                                echo '<a href="' . esc_url($term_link) . '" class="inline-flex items-center px-3 py-1 rounded-full bg-[#b50202]/10 text-[#b50202] hover:bg-[#b50202]/20 transition mr-2 mb-2">' . esc_html($term->name) . '</a>';
                            }
                        }
                        ?>
                        <?php if ($district_terms): foreach ($district_terms as $term) {
                            $seo_page = get_page_by_path($term->slug, OBJECT, 'seo_rayony_pages');
                            $term_link = $seo_page ? get_permalink($seo_page) : home_url('/rayony/' . $term->slug . '/');
                            echo '<a href="' . esc_url($term_link) . '" class="inline-flex items-center px-3 py-1 rounded-full bg-[#b50202]/10 text-[#b50202] hover:bg-[#b50202]/20 transition mr-2 mb-2">' . esc_html($term->name) . '</a>';
                        } endif; ?>
                        <?php if ($metro_terms): foreach ($metro_terms as $term) {
                            $seo_page = get_page_by_path($term->slug, OBJECT, 'seo_metro_pages');
                            $term_link = $seo_page ? get_permalink($seo_page) : home_url('/metro/' . $term->slug . '/');
                            echo '<a href="' . esc_url($term_link) . '" class="inline-flex items-center px-3 py-1 rounded-full bg-[#b50202]/10 text-[#b50202] hover:bg-[#b50202]/20 transition mr-2 mb-2">' . esc_html($term->name) . '</a>';
                        } endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="border border-zinc-200 rounded-lg p-4 bg-white">
                <h2 class="text-xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3">Контакты</h2>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <?php if ($phone): ?><a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)) ?>" class="inline-flex items-center gap-2 px-3 py-2 border border-[#b50202] rounded-full hover:bg-[#b50202] hover:text-white transition"><span class="font-medium"><?= esc_html($phone) ?></span></a><?php endif; ?>
                    <?php if ($telegram): ?><span data-go="tg" class="inline-flex items-center justify-center w-10 h-10 rounded-full text-[#b50202] hover:bg-[#b50202]/10 transition">TG</span><?php endif; ?>
                    <?php if ($whatsapp): ?><span data-go="wa" class="inline-flex items-center justify-center w-10 h-10 rounded-full text-[#b50202] hover:bg-[#b50202]/10 transition">WA</span><?php endif; ?>
                </div>
            </section>

           
            <?php if ($price || $price2h || $priceN): ?>
                <section class="border border-zinc-200 rounded-lg p-4 bg-white">
                <h2 class="text-xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3">Цены</h2>
                    <table class="w-full text-sm border-collapse">
                        <thead><tr class="bg-zinc-100"><th class="text-left font-medium px-3 py-2 border border-zinc-200 text-zinc-700">Длительность</th><th class="text-left font-medium px-3 py-2 border border-zinc-200 text-zinc-700">Стоимость</th></tr></thead>
                        <tbody>
                            <?php if ($price): ?><tr><td class="px-3 py-2 border border-zinc-200 text-zinc-600">Час</td><td class="px-3 py-2 border border-zinc-200"><span class="font-semibold text-[#b50202]"><?= esc_html(number_format_i18n($price)) ?> Br</span></td></tr><?php endif; ?>
                            <?php if ($price2h): ?><tr><td class="px-3 py-2 border border-zinc-200 text-zinc-600">Два часа</td><td class="px-3 py-2 border border-zinc-200"><span class="font-semibold text-[#b50202]"><?= esc_html(number_format_i18n($price2h)) ?> Br</span></td></tr><?php endif; ?>
                            <?php if ($priceN): ?><tr><td class="px-3 py-2 border border-zinc-200 text-zinc-600">Ночь</td><td class="px-3 py-2 border border-zinc-200"><span class="font-semibold text-[#b50202]"><?= esc_html(number_format_i18n($priceN)) ?> Br</span></td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

            
            <?php if ($descr = get_field('text', $ID)): ?>
                <?php
                $descr_plain = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $descr)));
                $descr_short = wp_html_excerpt($descr_plain, 320, '');
                $has_more = mb_strlen($descr_plain, 'UTF-8') > mb_strlen($descr_short, 'UTF-8');
                ?>
                <section class="border border-zinc-200 rounded-lg p-4 bg-white">
                <h2 class="text-xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3">Описание</h2>
                    <div id="modelDescShort" class="prose max-w-none prose-p:mb-3 prose-ul:list-disc prose-li:mb-1 prose-a:text-[#b50202] hover:prose-a:text-[#8d0202] text-zinc-700">
                        <?= esc_html($descr_short) ?>
                    </div>
                    <div id="modelDescFull" class="prose max-w-none prose-p:mb-3 prose-ul:list-disc prose-li:mb-1 prose-a:text-[#b50202] hover:prose-a:text-[#8d0202] text-zinc-700 hidden">
                        <?= wp_kses_post(wpautop($descr)) ?>
                    </div>
                    <?php if ($has_more): ?>
                        <button type="button" id="modelDescToggle" class="mt-3 inline-flex items-center gap-2 text-sm text-[#b50202] hover:text-[#8d0202] transition">
                            Показать больше
                        </button>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
    </div>
    
    
    <?php
    $all_ids = get_posts(['post_type'=>'models','posts_per_page'=>-1,'orderby'=>'date','order'=>'ASC','fields'=>'ids']);
    if (count($all_ids) > 1) {
        $current_id = $ID; $pos = array_search($current_id, $all_ids, true);
        $next_id = ($pos === false || $pos === count($all_ids) - 1) ? $all_ids[0] : $all_ids[$pos + 1];
        $prev_id = ($pos === false || $pos === 0) ? end($all_ids) : $all_ids[$pos - 1];
    ?>
        <nav class="mt-8">
            <ul class="flex justify-between">
                <li><a class="px-4 py-2 border border-zinc-200 rounded hover:bg-zinc-50 transition" href="<?= esc_url(get_permalink($prev_id)) ?>">← Назад</a></li>
                <li><a class="px-4 py-2 border border-zinc-200 rounded hover:bg-zinc-50 transition" href="<?= esc_url(get_permalink($next_id)) ?>">Вперёд →</a></li>
            </ul>
        </nav>
    <?php } ?>

    <?php $related = get_posts(['post_type'=>'models','posts_per_page'=>8,'post__not_in'=>[$ID],'orderby'=>'rand','fields'=>'ids']); ?>
    <?php if ($related): ?>
        <section class="mt-12 mb-6">
            <h2 class="text-2xl font-bold mb-4">Другие эскортницы</h2>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 list-none p-0 m-0">
                <?php
                $card_path = get_stylesheet_directory() . '/components/model_card_archive.php';
                foreach ($related as $rid) {
                    echo '<li>'; $ID = $rid; $card_variant = 'light'; $card_hover_zoom = true;
                    if(file_exists($card_path)) include $card_path;
                    echo '</li>';
                }
                ?>
            </ul>
        </section>
    <?php endif; ?>
</main>


<div id="orderModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="orderTitle">
    <button id="orderBackdrop" class="absolute inset-0 bg-black/60 z-0" aria-label="Закрыть"></button>
    <div class="absolute inset-0 z-10 flex items-center justify-center p-4 md:p-6">
        <div class="w-full max-w-[420px] md:max-w-[560px] rounded-2xl bg-white text-black shadow-2xl overflow-hidden max-h-[70vh]">
            <div class="flex items-center justify-between px-4 py-3 md:px-6 md:py-4">
                <p id="orderTitle" class="text-base md:text-xl font-bold text-black">Забронировать встречу c <?= esc_html($name) ?></p>
                <button id="orderClose" class="text-zinc-600 hover:text-black px-2 py-1" aria-label="Закрыть">Закрыть ×</button>
            </div>
            <div class="h-[2px] bg-[#b50202] mx-4 md:mx-6"></div>
            <div class="px-4 md:px-6 py-4 md:py-5 overflow-y-auto">
                <div class="mx-auto max-w-[440px] space-y-2.5">
                    <?php if (!empty($whatsapp)): ?><button type="button" data-go="wa" class="block w-full text-center rounded-lg px-5 py-2 font-semibold bg-emerald-500 hover:bg-emerald-600 text-white">WhatsApp</button><?php endif; ?>
                    <?php if (!empty($telegram)): ?><button type="button" data-go="tg" class="block w-full text-center rounded-lg px-5 py-2 font-semibold bg-sky-500 hover:bg-sky-600 text-white">Telegram</button><?php endif; ?>
                    <?php if (!empty($tg_channel)): ?><button type="button" data-go="tg" class="block w-full text-center rounded-lg px-5 py-2 font-semibold bg-sky-500 hover:bg-sky-600 text-white">Telegram канал</button><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="lightbox" class="fixed inset-0 z-[60] bg-black hidden flex-col items-center justify-center opacity-0 transition-opacity duration-300">
    <div class="absolute top-4 right-4 z-10"><button onclick="closeLightbox()" class="text-white hover:text-red-500 p-2"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>
    <div class="relative w-full h-full flex items-center justify-center px-2">
        <button onclick="lbPrev()" class="absolute left-2 md:left-4 p-2 text-white/70 hover:text-white z-10"><svg class="w-10 h-10 md:w-12 md:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>
        
        <div id="lbContent" class="max-h-[95vh] max-w-[95vw] flex items-center justify-center">
            <img id="lbImage" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="gallery" class="hidden max-h-[95vh] max-w-[95vw] object-contain rounded shadow-2xl">
            
            <video id="lbVideo" class="hidden max-h-[95vh] max-w-[95vw] object-contain rounded shadow-2xl" controls playsinline></video>
        </div>

        <button onclick="lbNext()" class="absolute right-2 md:right-4 p-2 text-white/70 hover:text-white z-10"><svg class="w-10 h-10 md:w-12 md:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSwiper = new Swiper('.main-slider', { 
            loop: true, 
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' } 
        });
        document.querySelectorAll('.thumb-image').forEach((thumb) => { 
            thumb.addEventListener('click', () => { 
                const idx = parseInt(thumb.dataset.index, 10); 
                if (!isNaN(idx)) mainSwiper.slideToLoop(idx, 0); 
            }); 
        });

        const ctaBtn = document.getElementById('orderOpen'); 
        const modal = document.getElementById('orderModal'); 
        const closeBtn = document.getElementById('orderClose'); 
        const backdrop = document.getElementById('orderBackdrop');
        function toggleOrder(show) { if(!modal) return; modal.classList.toggle('hidden', !show); document.body.style.overflow = show ? 'hidden' : ''; }
        if(ctaBtn) ctaBtn.addEventListener('click', () => toggleOrder(true)); 
        if(closeBtn) closeBtn.addEventListener('click', () => toggleOrder(false)); 
        if(backdrop) backdrop.addEventListener('click', () => toggleOrder(false));
        
        function setCtaVisibility() { if (!ctaBtn) return; ctaBtn.classList.toggle('hidden', mainSwiper.realIndex !== 0); } 
        mainSwiper.on('slideChange', setCtaVisibility); 
        setCtaVisibility();

        const toggleBtn = document.getElementById('modelDescToggle');
        const shortDesc = document.getElementById('modelDescShort');
        const fullDesc = document.getElementById('modelDescFull');
        if (toggleBtn && shortDesc && fullDesc) {
            toggleBtn.addEventListener('click', () => {
                const isOpen = !fullDesc.classList.contains('hidden');
                fullDesc.classList.toggle('hidden', isOpen);
                shortDesc.classList.toggle('hidden', !isOpen);
                toggleBtn.textContent = isOpen ? 'Показать больше' : 'Скрыть';
            });
        }
    });

    const galleryData = <?= json_encode($js_gallery_data) ?>; 
    let currentLbIndex = 0; 
    const lightbox = document.getElementById('lightbox'); 
    const lbImg = document.getElementById('lbImage');
    const lbVideo = document.getElementById('lbVideo');

    function openLightbox(index) { 
        if(galleryData.length === 0) return; 
        currentLbIndex = index; 
        updateLbContent(); 
        lightbox.classList.remove('hidden'); 
        setTimeout(() => lightbox.classList.remove('opacity-0'), 10); 
        document.body.style.overflow = 'hidden'; 
    }

    function closeLightbox() { 
        lightbox.classList.add('opacity-0'); 
        if(lbVideo) { lbVideo.pause(); }
        setTimeout(() => { 
            lightbox.classList.add('hidden'); 
            document.body.style.overflow = ''; 
        }, 300); 
    }

    function updateLbContent() { 
        const item = galleryData[currentLbIndex];
        
        lbImg.classList.add('hidden');
        lbVideo.classList.add('hidden');
        lbVideo.pause(); 

        if (item.type === 'video') {
            lbVideo.src = item.src; // JS сам поставит src
            lbVideo.classList.remove('hidden');
            lbVideo.play().catch(e => console.log('Autoplay blocked', e)); 
        } else {
            lbImg.src = item.src;
            lbImg.classList.remove('hidden');
        }
    }

    function lbPrev() { 
        currentLbIndex = (currentLbIndex === 0) ? galleryData.length - 1 : currentLbIndex - 1; 
        updateLbContent(); 
    }

    function lbNext() { 
        currentLbIndex = (currentLbIndex === galleryData.length - 1) ? 0 : currentLbIndex + 1; 
        updateLbContent(); 
    }
    
    window.addEventListener('keydown', (e) => { 
        if (lightbox && !lightbox.classList.contains('hidden')) { 
            if (e.key === 'Escape') closeLightbox(); 
            if (e.key === 'ArrowLeft') lbPrev(); 
            if (e.key === 'ArrowRight') lbNext(); 
        } 
    });

    (function() { const KEY = 'ek_fav_models'; function readFavs() { try { return JSON.parse(localStorage.getItem(KEY) || '[]').map(Number).filter(Boolean); } catch (e) { return []; } } function writeFavs(list) { localStorage.setItem(KEY, JSON.stringify(Array.from(new Set(list.map(Number))).filter(Boolean))); } function has(id) { return readFavs().includes(id); } function toggle(id) { const list = readFavs(); const i = list.indexOf(id); if (i > -1) list.splice(i, 1); else list.push(id); writeFavs(list); return list.includes(id); } function applyState(btn, active) { btn.classList.toggle('text-white', active); btn.classList.toggle('bg-[#b50202]', active); btn.classList.toggle('text-[#b50202]', !active); const o = btn.querySelector('.heart-outline'); const s = btn.querySelector('.heart-solid'); if (o && s) { o.classList.toggle('hidden', active); s.classList.toggle('hidden', !active); } } function bindHeart(btn) { if (!btn || btn._favBound) return; btn._favBound = true; const id = parseInt(btn.dataset.modelId, 10); if (!id) return; applyState(btn, has(id)); btn.addEventListener('click', () => { const active = toggle(id); applyState(btn, active); }); } document.addEventListener('DOMContentLoaded', () => { document.querySelectorAll('.js-fav[data-model-id]').forEach(bindHeart); }); })();
</script>

<?php get_footer(); ?>
