<?php
/**
 * Template Name: Blog (AJAX)
 */

defined('ABSPATH') || exit;
get_header();

/** ---------- Мета ---------- */
$page_id   = get_queried_object_id();
$page_meta = get_post_meta($page_id);
$page_h1   = !empty($page_meta['h1'][0]) ? $page_meta['h1'][0] : get_the_title($page_id);
$page_p    = isset($page_meta['p'][0]) ? $page_meta['p'][0] : '';

// Получаем чистый URL страницы без слеша в конце
global $wp;
$current_url = home_url(add_query_arg([], $wp->request));
$current_url = untrailingslashit(preg_replace('~/page/\d+/?$~', '', $current_url));

/** ---------- Запрос (SSR) ---------- */
$post_type = post_type_exists('blog') ? 'blog' : 'post';
// Логика получения страницы
if (get_query_var('paged')) {
    $paged = get_query_var('paged');
} elseif (get_query_var('page')) {
    $paged = get_query_var('page');
} else {
    $paged = 1;
}
$posts_per_page = 9;

$query = new WP_Query([
    'post_type'           => $post_type,
    'post_status'         => 'publish',
    'posts_per_page'      => $posts_per_page, 
    'paged'               => $paged,
    'ignore_sticky_posts' => true,
]);
?>

<main class="max-w-6xl mx-auto px-4 md:px-0 py-4 text-black">
    <?php if (file_exists(get_template_directory() . '/components/breadcrumbs.php')) { ?>
        <div class="-mt-3">
            <?php include get_template_directory() . '/components/breadcrumbs.php'; ?>
        </div>
    <?php } ?>
    <!-- Заголовок -->
    <header class="mt-6 mb-8 text-center main-blog-header">
        <h1 class="text-3xl md:text-4xl font-bold mb-3 text-black"><?= esc_html($page_h1); ?></h1>
        <?php if (!empty($page_p)) : ?>
            <p class="text-base md:text-lg text-zinc-600"><?= wp_kses_post($page_p); ?></p>
        <?php endif; ?>
    </header>

    <!-- Обертка -->
    <div id="blogAjaxWrapper" class="relative transition-opacity duration-300 min-h-[400px]" data-base-url="<?= esc_url($current_url); ?>">
        
        <!-- СЕТКА ПОСТОВ -->
        <section id="postsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($query->have_posts()) : 
                while ($query->have_posts()) : $query->the_post();
                    // Верстка карточки
                    $pid = get_the_ID(); $m = get_post_meta($pid);
                    $item_h1 = !empty($m['h1'][0]) ? $m['h1'][0] : get_the_title($pid);
                    $item_p  = !empty($m['p'][0])  ? $m['p'][0]  : get_the_excerpt($pid);
                    $img_id = 0; if (isset($m['photo'][0]) && $m['photo'][0] !== '') { $raw = maybe_unserialize($m['photo'][0]); if (is_numeric($raw)) $img_id = (int) $raw; elseif (is_array($raw) && !empty($raw['ID'])) $img_id = (int) $raw['ID']; } if (!$img_id) $img_id = (int) get_post_thumbnail_id($pid);
                    $img_src = $img_id ? wp_get_attachment_image_url($img_id, 'large') : '';
            ?>
                <article class="border border-zinc-200 rounded-sm overflow-hidden bg-white hover:bg-zinc-50 transition">
                    <a href="<?php the_permalink(); ?>" class="block group">
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <?php if ($img_src): ?>
                                <img src="<?= esc_url($img_src); ?>" loading="lazy" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            <?php else: ?>
                                <div class="absolute inset-0 bg-zinc-100"></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="p-4">
                        <h2 class="text-lg font-semibold leading-snug">
                            <a href="<?php the_permalink(); ?>" class="text-black hover:text-[#b50202] transition-colors"><?= esc_html($item_h1); ?></a>
                        </h2>
                        <?php if (!empty($item_p)) : ?><p class="mt-2 text-sm text-zinc-600"><?= wp_kses_post(wp_trim_words($item_p, 40, '…')); ?></p><?php endif; ?>
                        <div class="mt-4">
                            <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 text-[#b50202] hover:text-[#8d0202] text-sm font-semibold transition-colors">Читать <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7" /></svg></a>
                        </div>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); else: ?>
                <p class="text-zinc-500 text-center py-20 col-span-full">Записей пока нет.</p>
            <?php endif; ?>
        </section>

        <!-- ПАГИНАЦИЯ -->
        <div id="paginationContainer" class="mt-12 flex justify-center">
            <?php if ($query->max_num_pages > 1) : ?>
                <nav class="pagination-container flex flex-wrap gap-2 justify-center">
                    <?php
                    // Формируем ссылки БЕЗ слеша
                    $clean_base = untrailingslashit(preg_replace('~/page/\d+/?$~', '', $current_url));
                    
                    $links = paginate_links([
                        'base'      => $clean_base . '/page/%#%', // Формат: /page/2 (без слеша)
                        'format'    => '',
                        'current'   => max(1, $paged),
                        'total'     => $query->max_num_pages,
                        'prev_text' => '←',
                        'next_text' => '→',
                        'type'      => 'array',
                        'end_size'  => 1,
                        'mid_size'  => 2,
                    ]);
                    
                    $common_cls  = 'flex items-center justify-center min-w-[40px] h-[40px] px-3 rounded-sm text-sm font-medium transition-all duration-200 border';
                    $active_cls  = 'bg-[#b50202] border-[#b50202] text-white shadow-md select-none hover:bg-[#8d0202]';
                    $default_cls = 'bg-white border-zinc-200 text-zinc-600 hover:border-zinc-300 hover:text-black hover:bg-zinc-50';
                    
                    if ($links) {
                        foreach ($links as $link) {
                            // Удаляем слеш в ссылке вручную, если WP добавил
                            $link = preg_replace('~/page/(\d+)/"~', '/page/$1"', $link);

                            if (strpos($link, 'current') !== false) {
                                echo str_replace('page-numbers', 'page-numbers ' . $common_cls . ' ' . $active_cls, $link);
                            } else {
                                echo str_replace('page-numbers', 'page-numbers ajax-link ' . $common_cls . ' ' . $default_cls, $link);
                            }
                        }
                    }
                    ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('blogAjaxWrapper');
    const postsContainer = document.getElementById('postsContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    const apiUrl = '/wp-json/custom/v1/blog';

    let baseUrl = wrapper ? wrapper.dataset.baseUrl : window.location.href;
    // Убираем слеш в конце базового URL для чистоты
    baseUrl = baseUrl.replace(/\/$/, "");

    function handlePaginationClick(e) {
        const link = e.target.closest('.pagination-container a');
        if (!link) return;
        
        e.preventDefault();
        
        const href = link.getAttribute('href');
        
        // Достаем номер страницы
        let page = 1;
        const match = href.match(/\/page\/(\d+)/);
        if (match && match[1]) {
            page = parseInt(match[1], 10);
        }

        // 1. Меняем URL в браузере (БЕЗ СЛЕША)
        // Если page = 1 -> site.com/blog
        // Если page = 2 -> site.com/blog/page/2
        const pushUrl = (page === 1) ? baseUrl : (baseUrl + '/page/' + page);
        
        history.pushState({ page: page }, '', pushUrl);

        // 2. Грузим данные
        loadPostsApi(page);
    }

    async function loadPostsApi(page) {
        if (!wrapper) return;
        wrapper.style.opacity = '0.4';
        wrapper.style.pointerEvents = 'none';
        
        try {
            const fetchUrl = new URL(window.location.origin + apiUrl);
            fetchUrl.searchParams.set('page', page);
            fetchUrl.searchParams.set('base_url', baseUrl);

            const response = await fetch(fetchUrl);
            const data = await response.json();

            if (postsContainer) postsContainer.innerHTML = data.html || '';
            if (paginationContainer) paginationContainer.innerHTML = data.pagination || '';

            const header = document.querySelector('.main-blog-header');
            if (header) header.scrollIntoView({ behavior: 'smooth' });

        } catch (error) {
            console.error(error);
        } finally {
            wrapper.style.opacity = '1';
            wrapper.style.pointerEvents = 'auto';
        }
    }

    if (wrapper) wrapper.addEventListener('click', handlePaginationClick);
    window.addEventListener('popstate', () => window.location.reload());
});
</script>

<?php get_footer(); ?>
