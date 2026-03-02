<?php
// PHP-часть фильтра (восстановление галочек из Cookie)
$current_req_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$current_req_path = preg_replace('~/page/\d+/?$~', '', $current_req_path);
$current_req_path = untrailingslashit($current_req_path);
if ($current_req_path === '') {
    $current_req_path = '/';
}

$saved_filters = [];
if (isset($_COOKIE['models_filter_state'])) {
    parse_str(stripslashes($_COOKIE['models_filter_state']), $saved_filters);
    if (empty($saved_filters['_path'])) {
        $saved_filters = [];
    } else {
        $cookie_path = untrailingslashit($saved_filters['_path']);
        if ($cookie_path === '') $cookie_path = '/';
        if ($cookie_path !== $current_req_path) {
            $saved_filters = [];
        }
    }
    unset($saved_filters['_path']);
}
function is_filter_checked($name, $value, $saved) {
    if (empty($saved[$name])) return false;
    if (is_array($saved[$name])) return in_array($value, $saved[$name]);
    return $saved[$name] == $value;
}
$hair_tax_slug = 'cvet_volos_tax'; 
function get_meta_bounds_safe($meta_key, $def_min, $def_max) {
    global $wpdb;
    $cache_key = 'filter_bounds_v3_' . $meta_key;
    $bounds = wp_cache_get($cache_key);
    if (false === $bounds) {
        $sql = "SELECT MIN(CAST(pm.meta_value AS UNSIGNED)) as min_val, MAX(CAST(pm.meta_value AS UNSIGNED)) as max_val FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.post_type = 'models' AND p.post_status = 'publish' AND pm.meta_key = %s AND pm.meta_value REGEXP '^[0-9]+'";
        $row = $wpdb->get_row($wpdb->prepare($sql, $meta_key));
        if ($row && $row->max_val > 0) {
            $db_min = (int)$row->min_val; $db_max = (int)$row->max_val;
            $bounds = ($db_max > $db_min) ? ['min' => $db_min, 'max' => $db_max] : ['min' => floor($db_min * 0.8), 'max' => ceil($db_max * 1.2)];
        } else { $bounds = false; }
        wp_cache_set($cache_key, $bounds, '', 300);
    }
    return (!$bounds || $bounds['max'] == 0) ? ['min' => $def_min, 'max' => $def_max] : $bounds;
}

$districts = get_terms(['taxonomy' => 'rayony', 'hide_empty' => false]);
$services  = get_terms(['taxonomy' => 'uslugi_tax', 'hide_empty' => false]);
$metro_stations = get_terms(['taxonomy' => 'metro', 'hide_empty' => false]);
$hair_colors = taxonomy_exists($hair_tax_slug) ? get_terms(['taxonomy' => $hair_tax_slug, 'hide_empty' => false]) : [];

$age_b = get_meta_bounds_safe('age', 18, 60);
$weight_b = get_meta_bounds_safe('weight', 40, 100);
$height_b = get_meta_bounds_safe('height', 150, 200);
$breast_b = get_meta_bounds_safe('bust', 1, 8);
$api_url = get_rest_url(null, 'custom/v1/filter');

function render_multi_select($name, $placeholder, $terms, $icon_svg) {
    global $saved_filters;
    if (empty($terms) || is_wp_error($terms)) return;
    ?>
    <div class="relative group pf-multiselect" data-name="<?php echo esc_attr($name); ?>">
        <div class="pf-ms-trigger flex items-center bg-white border border-zinc-200 rounded-lg h-12 w-full hover:border-zinc-300 transition-colors cursor-pointer relative select-none">
            <span class="absolute left-4 text-zinc-500 pointer-events-none"><?php echo $icon_svg; ?></span>
            <div class="pf-ms-label w-full pl-12 pr-10 text-sm text-zinc-600 truncate"><?php echo esc_html($placeholder); ?></div>
            <span class="absolute right-4 text-[10px] text-zinc-500 pointer-events-none">▼</span>
        </div>
        <div class="pf-ms-dropdown hidden absolute top-full left-0 right-0 mt-2 bg-white border border-zinc-200 rounded-lg shadow-[0_10px_40px_rgba(0,0,0,0.12)] max-h-60 overflow-y-auto z-[60] p-2">
            <?php foreach($terms as $term): 
                $isChecked = is_filter_checked($name, $term->slug, $saved_filters) ? 'checked' : ''; ?>
                <label class="flex items-center gap-3 px-2 py-2 hover:bg-zinc-50 rounded cursor-pointer transition-colors group/item">
                    <input type="checkbox" name="<?php echo esc_attr($name); ?>[]" value="<?php echo esc_attr($term->slug); ?>" <?php echo $isChecked; ?> class="pf-ms-checkbox w-4 h-4 rounded border-zinc-300 bg-white text-[#b50202] focus:ring-0 focus:ring-offset-0 checked:bg-[#b50202] checked:border-[#b50202] cursor-pointer">
                    <span class="text-sm text-zinc-700 group-hover/item:text-black"><?php echo esc_html($term->name); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<section class="hidden md:block bg-white border-b border-zinc-200 py-6 px-4 xl:px-0">
    <div class="pc-filter-wrapper max-w-6xl mx-auto">
        <form action="<?php echo home_url('/'); ?>" method="GET" id="ajaxFilterForm" data-api-url="<?php echo esc_url($api_url); ?>" class="flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_minmax(0,0.9fr)] gap-4 relative z-10">
                <?php render_multi_select('rayony', 'Выбрать районы', $districts, '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>'); ?>
                <?php render_multi_select('metro', 'Метро', $metro_stations, '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 20l-2 2"/><path d="M17 20l2 2"/><path d="M5 14h14"/><path d="M6 4h12a2 2 0 0 1 2 2v9a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V6a2 2 0 0 1 2-2Z"/><circle cx="8.5" cy="14.5" r="1.5"/><circle cx="15.5" cy="14.5" r="1.5"/></svg>'); ?>
                <?php render_multi_select('uslugi_tax', 'Выбрать услуги', $services, '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>'); ?>
                <?php render_multi_select('cvet_volos_tax', 'Цвет волос', $hair_colors, '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'); ?>
                <div class="flex bg-white border border-zinc-200 rounded-lg p-1 h-12" id="typeToggle">
                    <?php $dost_val = isset($saved_filters['dostupnost']) ? $saved_filters['dostupnost'] : 'apartments'; ?>
                    <label class="flex-1 flex items-center justify-center cursor-pointer rounded text-sm font-medium transition-colors text-zinc-700 hover:text-black <?php echo ($dost_val !== 'outcall') ? 'active' : ''; ?>">
                        <input type="radio" name="dostupnost" value="apartments" class="hidden" <?php echo ($dost_val !== 'outcall') ? 'checked' : ''; ?>>Апарт.
                    </label>
                    <label class="flex-1 flex items-center justify-center cursor-pointer rounded text-sm font-medium transition-colors text-zinc-700 hover:text-black <?php echo ($dost_val === 'outcall') ? 'active' : ''; ?>">
                        <input type="radio" name="dostupnost" value="outcall" class="hidden" <?php echo ($dost_val === 'outcall') ? 'checked' : ''; ?>>Выезд
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_minmax(0,0.8fr)] gap-4 items-stretch relative z-0">
                <?php function render_slider($key, $label, $b, $icon) { 
                    global $saved_filters;
                    $min = $b['min']; $max = $b['max'];
                    $cur_min = isset($saved_filters[$key . '_min']) ? (int)$saved_filters[$key . '_min'] : $min;
                    $cur_max = isset($saved_filters[$key . '_max']) ? (int)$saved_filters[$key . '_max'] : $max;
                    ?>
                    <div class="pf-slider-card pf-slider-wrapper flex flex-col justify-between bg-white border border-zinc-200 rounded-lg p-4 h-[88px]" data-min="<?php echo $min; ?>" data-max="<?php echo $max; ?>">
                        <div class="flex justify-between items-center mb-1">
                            <div class="flex items-center gap-2 text-sm font-semibold text-black"><span class="text-zinc-500 w-[18px] h-[18px] flex"><?php echo $icon; ?></span><?php echo $label; ?></div>
                            <div class="text-xs text-zinc-500 font-mono"><span class="val-min"><?php echo $cur_min; ?></span>-<span class="val-max"><?php echo $cur_max; ?></span></div>
                        </div>
                        <div class="relative w-full h-5 flex items-center">
                            <div class="absolute w-full h-[2px] bg-zinc-200 rounded-full z-0"></div><div class="pf-track-fill absolute h-[2px] bg-[#b50202] z-10"></div>
                            <input type="range" class="pf-range-input min-range" name="<?php echo $key; ?>_min" min="<?php echo $min; ?>" max="<?php echo $max; ?>" value="<?php echo $cur_min; ?>" step="1">
                            <input type="range" class="pf-range-input max-range" name="<?php echo $key; ?>_max" min="<?php echo $min; ?>" max="<?php echo $max; ?>" value="<?php echo $cur_max; ?>" step="1">
                        </div>
                        <div class="flex justify-between text-[10px] text-zinc-500 mt-[-5px]"><span><?php echo $min; ?></span><span><?php echo $max; ?></span></div>
                    </div>
                <?php } 
                render_slider('vozrast', 'Возраст', $age_b, '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>');
                render_slider('ves', 'Вес', $weight_b, '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="13" rx="2"/><path d="M16 3h-8"/></svg>');
                render_slider('rost', 'Рост', $height_b, '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22v-6"/><path d="M20 22v-6"/><path d="M12 2v20"/><path d="M12 6h8"/><path d="M12 10h8"/><path d="M12 14h8"/></svg>');
                render_slider('bust', 'Грудь', $breast_b, '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="12" r="5"/><circle cx="17" cy="12" r="5"/></svg>');
                ?>
                <div class="flex flex-col gap-2 h-[88px] justify-center">
                    <button type="submit" class="pf-btn-find w-full bg-[#b50202] text-white font-semibold text-sm py-3 px-6 rounded-sm hover:bg-[#8d0202] transition active:scale-95">Найти</button>
                    <button type="button" id="resetFilterBtn" class="text-xs text-zinc-500 hover:text-black transition underline decoration-zinc-300 hover:decoration-black">Сбросить фильтр</button>
                </div>
            </div>
        </form>
    </div>
</section>
