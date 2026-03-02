<?php
/*
Template Name: Сетка анкет (SEO)
Template Post Type: seo_pages, seo_rayony_pages, seo_metro_pages, seo_hair_pages, seo_rost_pages, seo_ves_pages, seo_vozrast_pages, seo_bust_pages
*/

defined('ABSPATH') || exit;
get_header();

$post = get_post();
$slug = $post ? $post->post_name : '';
$post_type = $post ? $post->post_type : '';
$taxonomy_map = [
    'seo_pages' => 'uslugi_tax',
    'seo_rayony_pages' => 'rayony',
    'seo_metro_pages' => 'metro',
    'seo_hair_pages' => 'cvet_volos_tax',
    'seo_rost_pages' => 'rost_tax',
    'seo_ves_pages' => 'ves_tax',
    'seo_vozrast_pages' => 'vozrast_tax',
    'seo_bust_pages' => 'razmer_grydi_tax',
];
$filter_taxonomy = $taxonomy_map[$post_type] ?? 'uslugi_tax';

$h1 = get_field('h1');
if (!$h1) {
    $h1 = get_the_title();
}
$p = get_field('p');
$bottom_p = get_field('bottom_p');
$h2_models = get_field('h2_models');
$seo = get_field('p_section');
if (empty($seo)) {
    $seo = get_field('seo');
}

$img = get_field('img');
$img_id = 0;
$img_url = '';
$img_alt = '';
if (is_array($img)) {
    $img_id = (int) ($img['ID'] ?? 0);
    $img_url = (string) ($img['url'] ?? '');
    $img_alt = (string) ($img['alt'] ?? '');
} elseif (!empty($img)) {
    $img_id = (int) $img;
}
if ($img_id && $img_url === '') {
    $img_url = (string) wp_get_attachment_image_url($img_id, 'full');
}
if ($img_alt === '') {
    $img_alt = wp_strip_all_tags((string) $h1);
}

$paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
$paged_num = max(1, (int) $paged);
if ($paged_num > 1) {
    $h1 .= ' — Страница ' . $paged_num;
}
$term = $filter_taxonomy ? get_term_by('slug', $slug, $filter_taxonomy) : null;
$has_term = $term && !is_wp_error($term);

if ($has_term) {
    $args = [
        'post_type'      => 'models',
        'post_status'    => 'publish',
        'posts_per_page' => 24,
        'paged'          => (int) $paged,
        'tax_query'      => [
            [
                'taxonomy' => $filter_taxonomy,
                'field'    => 'slug',
                'terms'    => $slug,
            ],
        ],
    ];
} else {
    $base_params = [
        'page' => (int) $paged,
        'per_page' => 24,
    ];
    if (preg_match('~(na-vyezd|vyezd|vyiezd|outcall)~i', $slug)) {
        $base_params['dostupnost'] = 'outcall';
    } elseif (preg_match('~(apart|apartments|incall)~i', $slug)) {
        $base_params['dostupnost'] = 'apartments';
    }
    $args = get_models_query_args($base_params);
}
$query = new WP_Query($args);
$max_pages = (int) $query->max_num_pages;
$pagination_html = my_theme_get_pagination_html($paged, $max_pages);
$pagination_classes = 'mt-10 flex justify-center' . (empty($pagination_html) ? ' hidden' : '');
?>

<main class="w-full max-w-6xl mx-auto text-black p-2 md:p-0 lg:!mt-[60px]">
    <section class="bg-white text-black py-6 px-4 xl:px-0 border-b border-zinc-200">
        <div class="max-w-6xl mx-auto flex flex-col gap-4">
            <div class="h-1 w-12 bg-[#b50202] rounded-sm"></div>
            <div>
                <h1 class="text-2xl md:text-4xl font-semibold mb-3 text-black"><?php echo esc_html($h1); ?></h1>
                <?php if (!empty($p)) : ?>
                    <div class="space-y-3 text-base leading-relaxed text-zinc-700">
                        <?php echo wp_kses_post($p); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($bottom_p)) : ?>
            <div class="max-w-6xl mx-auto mt-6 text-sm leading-relaxed border-l-2 border-[#b50202] pl-4 text-zinc-700">
                <?php echo esc_html($bottom_p); ?>
            </div>
        <?php endif; ?>

        <?php if ($img_url) : ?>
            <div class="max-w-6xl mx-auto mt-6">
                <?php
                if ($img_id) {
                    echo wp_get_attachment_image($img_id, 'full', false, [
                        'class' => 'w-full h-auto rounded-lg',
                        'alt' => $img_alt,
                    ]);
                } else {
                    echo '<img class="w-full h-auto rounded-lg" src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '">';
                }
                ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (have_rows('sections')): ?>
        <section class="px-4 xl:px-0 mt-6">
            <div class="max-w-6xl mx-auto space-y-6">
                <?php while (have_rows('sections')): the_row(); ?>
                    <?php $section_content = get_sub_field('section_content'); ?>
                    <?php if ($section_content): ?>
                        <div class="prose max-w-none">
                            <?php echo wp_kses_post($section_content); ?>
                        </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="py-6 border-t border-zinc-200 bg-white text-black">
        <div class="max-w-6xl mx-auto px-4">
            <?php if ($h2_models): ?>
                <div class="flex items-center justify-between gap-2 text-sm">
                    <h2 class="text-2xl md:text-3xl font-semibold"><?php echo esc_html($h2_models); ?></h2>
                </div>
                <div class="h-0.5 w-10 bg-[#b50202] rounded-sm mt-2 mb-4"></div>
            <?php endif; ?>

            <div id="modelsGrid"
                data-current-page="<?php echo esc_attr($paged); ?>"
                data-total-pages="<?php echo esc_attr($max_pages); ?>"
                data-per-page="24"
                data-base-filters="{}"
                data-sort-by="date"
                data-sort-order="DESC"
                data-view-context="archive"
                <?php if ($has_term): ?>
                    data-context-tax="<?php echo esc_attr($filter_taxonomy); ?>"
                    data-context-term="<?php echo esc_attr($slug); ?>"
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
                <?php if (!empty($seo)) : ?>
        <section class=" bg-white">
            <div class="max-w-6xl mx-auto seo">
                <?php echo wp_kses_post($seo); ?>
            </div>
        </section>
    <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
