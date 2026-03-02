<?php
/**
 * Template Name: Шаблон Кастомного Архива
 * Template Post Type: page
 */

defined('ABSPATH') || exit;
get_header();

global $post;
$page_slug = $post ? $post->post_name : '';

$tax_to_filter = '';
if (function_exists('get_field')) {
    $tax_to_filter = (string) get_field('archive_taxonomy', $post->ID);
}
if ($tax_to_filter === '') {
    $tax_to_filter = (string) get_post_meta($post->ID, 'archive_taxonomy', true);
}
$tax_to_filter = sanitize_text_field($tax_to_filter);
$term_to_filter = $page_slug;
$query = null;

$paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);

if ($tax_to_filter && $term_to_filter && taxonomy_exists($tax_to_filter)) {
    $args = [
        'post_type'      => 'models',
        'post_status'    => 'publish',
        'posts_per_page' => 24,
        'paged'          => (int) $paged,
        'tax_query'      => [
            [
                'taxonomy' => $tax_to_filter,
                'field'    => 'slug',
                'terms'    => $term_to_filter,
            ],
        ],
    ];
    $query = new WP_Query($args);
}
?>

<main class="w-full max-w-6xl mx-auto text-black p-2 md:p-0 lg:!mt-[60px]">
    <header class="px-4 xl:px-0 mt-4">
        <h1 class="text-2xl md:text-4xl font-semibold text-black"><?php the_title(); ?></h1>
    </header>

    <?php if (get_the_content()): ?>
        <div class="page-content prose max-w-none px-4 py-6">
            <?php the_content(); ?>
        </div>
    <?php endif; ?>

    <?php get_template_part('components/filter'); ?>

    <section class="py-6 border-t border-zinc-200 bg-white text-black">
        <div class="max-w-6xl mx-auto px-4">
            <div class="h-0.5 w-10 bg-[#b50202] rounded-sm mt-2 mb-4"></div>
            <?php
            if ($query instanceof WP_Query) {
                $card_template = 'model_card_archive.php';
                $grid_context_tax = $tax_to_filter;
                $grid_context_term = $term_to_filter;
                include get_template_directory() . '/components/model_grid.php';
            } else {
                echo '<p class="text-sm text-zinc-500">Неверная конфигурация страницы архива.</p>';
            }
            ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
