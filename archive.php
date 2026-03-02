<?php
defined('ABSPATH') || exit;
get_header();

$title = post_type_archive_title('', false);
if ($title === '') {
    $title = 'Каталог';
}
?>

<main class="w-full max-w-6xl mx-auto text-black p-2 md:p-0 lg:!mt-[60px]">
    <header class="px-4 xl:px-0 mt-4">
        <h1 class="text-2xl md:text-4xl font-semibold text-black"><?php echo esc_html($title); ?></h1>
    </header>

    <?php get_template_part('components/filter'); ?>

    <section class="py-6 border-t border-zinc-200 bg-white text-black">
        <div class="max-w-6xl mx-auto px-4">
            <div class="h-0.5 w-10 bg-[#b50202] rounded-sm mt-2 mb-4"></div>
            <?php
            $query = $wp_query;
            $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
            $per_page = (int) $query->get('posts_per_page');
            if ($per_page <= 0) {
                $per_page = (int) get_option('posts_per_page');
            }
            if ($per_page <= 0) {
                $per_page = 24;
            }
            $max_pages = (int) $query->max_num_pages;
            $pagination_html = my_theme_get_pagination_html($paged, $max_pages);
            $pagination_classes = 'mt-10 flex justify-center' . (empty($pagination_html) ? ' hidden' : '');
            ?>
            <div id="modelsGrid"
                data-current-page="<?php echo esc_attr($paged); ?>"
                data-total-pages="<?php echo esc_attr($max_pages); ?>"
                data-per-page="<?php echo esc_attr($per_page); ?>"
                data-base-filters="{}"
                data-sort-by="date"
                data-sort-order="DESC"
                data-view-context="archive">
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
</main>

<?php get_footer(); ?>
