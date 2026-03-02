<?php /* Template name: Главная страница
 * Template Post Type: page, uslugi, rayonu, razmer_grydi, vozrast, rost, ves, cvet_volos, dostupnost
*/
 
$kz_home_partial = defined('KZ_HOME_PARTIAL') && KZ_HOME_PARTIAL;
if (!$kz_home_partial) {
    get_header();
}
$headings = [];
$headings_file = get_template_directory() . '/components/page-headings.php';
if (file_exists($headings_file)) {
    require_once $headings_file;
    if (function_exists('kz_get_page_headings')) {
        $headings = kz_get_page_headings();
    }
}

$h1 = $headings['h1'] ?? get_field('h1');
$h1_base = $headings['base_h1'] ?? $h1;
$p = get_field('p');
$bottom_p = get_field('bottom_p');
$img_id = get_field('img');
$img_url = wp_get_attachment_image_url($img_id, 'full');

$h2_models = $headings['h2'] ?? get_field('h2_models');

$seo = get_field('seo');
$current_page = function_exists('kz_get_current_page_number') ? kz_get_current_page_number() : 1;
$is_pagination_page = $current_page > 1;
$allowed_sort_fields = ['date', 'price'];
$current_sort_by = isset($_GET['sort_by']) ? sanitize_key($_GET['sort_by']) : 'date';
if (!in_array($current_sort_by, $allowed_sort_fields, true)) {
    $current_sort_by = 'date';
}
$current_sort_order = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'DESC';
if (!in_array($current_sort_order, ['ASC', 'DESC'], true)) {
    $current_sort_order = 'DESC';
}

$whatsapp = get_theme_mod('contact_whatsapp', '#'); ?>

<main class="w-full max-w-6xl mx-auto text-black p-2 md:p-0 lg:!mt-[60px]">

    <!-- Banner -->
    <section class="bg-white text-black py-6 px-4 xl:px-0 border-b border-zinc-200">
        <div class="max-w-6xl mx-auto flex flex-col gap-4">
            <!-- Акцентная линейка -->
            <div class="h-1 w-12 bg-[#b50202] rounded-sm"></div>

            <!-- Заголовок + текст -->
            <div>
                <h1 class="text-2xl md:text-4xl font-semibold mb-3 text-black"
                    data-base-heading="<?php echo esc_attr($h1_base); ?>">
                    <?php echo esc_html($h1); ?>
                </h1>

                <?php if (!empty($p)) : ?>
                    <div class="space-y-3 text-base leading-relaxed text-zinc-700"
                        data-hide-on-pagination="<?php echo $is_pagination_page ? 'true' : 'false'; ?>"
                        <?php echo $is_pagination_page ? 'hidden' : ''; ?>>
                        <?php echo esc_html($p); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Нижний текст -->
        <?php if (!empty($bottom_p)) : ?>
            <div class="max-w-6xl mx-auto mt-6 text-sm leading-relaxed border-l-2 border-[#b50202] pl-4 text-zinc-700"
                data-hide-on-pagination="<?php echo $is_pagination_page ? 'true' : 'false'; ?>"
                <?php echo $is_pagination_page ? 'hidden' : ''; ?>>
                <?php echo esc_html($bottom_p); ?>
            </div>
        <?php endif; ?>
    </section>

    <?php get_template_part('components/filter'); ?>

    <!-- Блок моделей -->
    <section class="py-6 border-t border-zinc-200 bg-white text-black">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex items-center justify-between gap-2 text-sm" id="sortControls">
                                    <h2 class="text-2xl md:text-3xl font-semibold">
                <?php echo esc_html($h2_models); ?>
            </h2>
            <div class="flex justify-center items-center">
            <span class="text-zinc-500 mr-2 hidden md:inline">Сортировать:</span>
            
            <!-- Кнопка ДАТА -->
            <button type="button" class="sort-btn flex items-center gap-1 px-3 py-1.5 rounded border transition-colors group" 
                data-sort="date" 
                data-active="<?php echo $current_sort_by === 'date' ? 'true' : 'false'; ?>"
                data-order="<?php echo ($current_sort_by === 'date') ? $current_sort_order : 'DESC'; ?>">
                <span>Новые</span>
                <span class="sort-arrow text-[10px] w-3 text-center"><?php if ($current_sort_by === 'date' && $current_sort_order === 'ASC'): ?>▲<?php else: ?>▼<?php endif; ?></span>
            </button>

            <!-- Кнопка ЦЕНА -->
            <button type="button" class="sort-btn flex items-center gap-1 px-3 py-1.5 rounded border transition-colors group" 
                data-sort="price"
                data-active="<?php echo $current_sort_by === 'price' ? 'true' : 'false'; ?>"
                data-order="<?php echo ($current_sort_by === 'price') ? $current_sort_order : 'DESC'; ?>">
                <span>Цена</span>
                <span class="sort-arrow text-[10px] w-3 text-center"><?php if ($current_sort_by === 'price' && $current_sort_order === 'ASC'): ?>▲<?php else: ?>▼<?php endif; ?></span>
            </button>
            </div>

        </div>
            <div class="h-0.5 w-10 bg-[#b50202] rounded-sm mt-2 mb-4"></div>

            <?php
            $count = 36;
            $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);

            $base_filters = [];
            $context_tax = '';
            $context_term = '';
            $qo = get_queried_object();
            if ($qo instanceof WP_Post && isset($qo->post_type)) {
                $cpt_map = [
                    'uslugi' => 'uslugi_tax',
                    'rayonu' => 'rayony',
                    'vozrast' => 'vozrast_tax',
                    'rost' => 'rost_tax',
                    'ves' => 'ves_tax',
                    'cvet_volos' => 'cvet_volos_tax',
                    'drygoe' => 'drygoe_tax',
                ];

                if (isset($cpt_map[$qo->post_type])) {
                    $context_tax = $cpt_map[$qo->post_type];
                    $context_term = $qo->post_name;
                } elseif ($qo->post_type === 'dostupnost') {
                    if ($qo->post_name === 'escort-na-vyyezd') {
                        $base_filters['na_vyezd'] = 1;
                    } elseif ($qo->post_name === 'escort-priem') {
                        $base_filters['incall'] = 1;
                    }
                } elseif ($qo->post_type === 'page') {
                    if ($qo->post_name === 'ehlitnyj-escort') {
                        $base_filters['price_min'] = 1201;
                    } elseif ($qo->post_name === 'escort-na-vyezd') {
                        $base_filters['na_vyezd'] = 1;
                    } elseif ($qo->post_name === 'escort-priem') {
                        $base_filters['incall'] = 1;
                    } elseif ($qo->post_name === 'deshevye-prostitutki') {
                        $base_filters['cheap'] = 1;
                        $base_filters['price_max'] = 800;
                    } elseif (term_exists($qo->post_name, 'drygoe_tax')) {
                        $context_tax = 'drygoe_tax';
                        $context_term = $qo->post_name;
                    }
                }
            }

            $base_params = array_merge([
                'page' => (int) $paged,
                'per_page' => (int) $count,
                'orderby' => $current_sort_by ?? 'date',
                'order' => $current_sort_order ?? 'DESC',
            ], $base_filters);

            if ($context_tax && $context_term) {
                $base_params['context_tax'] = $context_tax;
                $base_params['context_term'] = $context_term;
            }

            $args = get_models_query_args($base_params);
            $query = new WP_Query($args);
            $max_pages = (int) $query->max_num_pages;
            $pagination_html = my_theme_get_pagination_html($paged, $max_pages);
            $pagination_classes = 'mt-10 flex justify-center' . (empty($pagination_html) ? ' hidden' : '');
            ?>
            <div id="modelsGrid"
                data-current-page="<?php echo esc_attr($paged); ?>"
                data-total-pages="<?php echo esc_attr($max_pages); ?>"
                data-per-page="<?php echo esc_attr($count); ?>"
                data-base-filters="<?php echo esc_attr(wp_json_encode($base_filters, JSON_UNESCAPED_UNICODE) ?: '{}'); ?>"
                data-sort-by="<?php echo esc_attr($current_sort_by ?? 'date'); ?>"
                data-sort-order="<?php echo esc_attr($current_sort_order ?? 'DESC'); ?>"
                data-view-context="archive"
                <?php if ($context_tax && $context_term): ?>
                    data-context-tax="<?php echo esc_attr($context_tax); ?>"
                    data-context-term="<?php echo esc_attr($context_term); ?>"
                <?php endif; ?>>
                <ul class="models-grid-list grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-4 list-none p-0">
                    <?php if ($query->have_posts()): ?>
                        <?php $i = 0; ?>
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <?php
                            $ID = get_the_ID();
                            $card_variant = 'light';
                            $card_hover_zoom = true;
                            $index = $i;
                            echo '<li>';
                            include get_template_directory() . '/components/model_card_archive.php';
                            echo '</li>';
                            $i++;
                            ?>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php else: ?>
                        <li class="col-span-full text-center text-sm text-zinc-500 py-10">По вашему запросу моделей не найдено.</li>
                    <?php endif; ?>
                </ul>

                <div class="<?php echo esc_attr($pagination_classes); ?>" id="modelsPagination">
                    <?php echo $pagination_html; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- SEO-текст -->
    <?php if (!empty($seo)) : ?>
        <section class="mt-8 px-4 xl:px-0 bg-white"
            data-hide-on-pagination="<?php echo $is_pagination_page ? 'true' : 'false'; ?>"
            <?php echo $is_pagination_page ? 'hidden' : ''; ?>>
            <div class="max-w-6xl mx-auto seo">
                <?= wp_kses_post($seo); ?>
            </div>
        </section>
    <?php endif; ?>

</main>


<?php
if (!$kz_home_partial) {
    get_footer();
}
?>
