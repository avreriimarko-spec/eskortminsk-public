<?php
/*
Template Name: HTML Sitemap
*/
defined('ABSPATH') || exit;
get_header();
?>

<main class="max-w-6xl mx-auto px-4 md:px-0 py-8 bg-white text-black">
    <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-6 md:p-8 shadow-inner shadow-black/5">
        <h1 class="text-3xl md:text-5xl font-bold text-center mb-8 tracking-tight text-black">
            Карта сайта
        </h1>

        <?php
        // 1) Все страницы
        $pages = get_pages(['sort_column' => 'menu_order, post_title']);
        $exclude_slugs = ['services'];
        if ($pages) {
            $pages = array_values(array_filter($pages, function ($page) use ($exclude_slugs) {
                $template = get_post_meta($page->ID, '_wp_page_template', true);
                if ($template === 'tpl-services.php') {
                    return false;
                }
                return !in_array($page->post_name, $exclude_slugs, true);
            }));
        }
        if ($pages): ?>
            <section aria-labelledby="sitemap-pages-heading" class="mb-10">
                <h2 id="sitemap-pages-heading"
                    class="text-xl md:text-2xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3 text-black">
                    Страницы
                </h2>
                <ul class="md:columns-2 gap-6 [column-fill:_balance] list-disc marker:text-[#b50202] text-zinc-700 space-y-1">
                    <?php foreach ($pages as $page): ?>
                        <li class="break-inside-avoid">
                            <a href="<?= esc_url(get_permalink($page->ID)); ?>"
                                class="inline-flex items-center gap-2 text-zinc-700 hover:text-[#b50202] underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-[#b50202] rounded">
                                <svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                                <span><?= esc_html($page->post_title ?: ('Страница #' . $page->ID)); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php
        // 2) Публичные кастомные типы (кроме встроенных)
        $post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');

        foreach ($post_types as $pt):
            $items = get_posts([
                'post_type'                => $pt->name,
                'posts_per_page'           => -1,
                'orderby'                  => 'title',
                'order'                    => 'ASC',
                // микро-оптимизации
                'no_found_rows'            => true,
                'update_post_meta_cache'   => false,
                'update_post_term_cache'   => false,
                'suppress_filters'         => true,
                'fields'                   => 'ids',
            ]);
            if (empty($items)) continue; ?>

            <section aria-labelledby="sitemap-<?= esc_attr($pt->name); ?>-heading" class="mb-10">
                <h2 id="sitemap-<?= esc_attr($pt->name); ?>-heading"
                    class="text-xl md:text-2xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3 text-black">
                    <?= esc_html($pt->label); ?>
                </h2>
                <ul class="md:columns-2 gap-6 [column-fill:_balance] list-disc marker:text-[#b50202] text-zinc-700 space-y-1">
                    <?php foreach ($items as $item_id): ?>
                        <li class="break-inside-avoid">
                            <a href="<?= esc_url(get_permalink($item_id)); ?>"
                                class="inline-flex items-center gap-2 text-zinc-700 hover:text-[#b50202] underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-[#b50202] rounded">
                                <svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                                <span><?= esc_html(get_the_title($item_id)); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
</main>


<?php get_footer();
