<?php
/* 
Template Name: Шаблон статьи
Template Post Type: blog
*/
defined('ABSPATH') || exit;
get_header();

// === 1) Данные записи (минимум запросов) ===
$post_id = get_the_ID();
$F       = get_fields($post_id) ?: [];

$item_h1 = !empty($F['h1']) ? $F['h1'] : get_the_title($post_id);
$item_p  = !empty($F['p'])  ? $F['p']  : get_the_excerpt($post_id);
$seo     = !empty($F['seo']) ? $F['seo'] : '';

// === 2) Обложка: ACF photo_id|photo -> featured ===
$img_id = null;
if (!empty($F['photo_id']) && is_numeric($F['photo_id'])) {
    $img_id = (int) $F['photo_id'];
} elseif (!empty($F['photo'])) {
    if (is_numeric($F['photo'])) {
        $img_id = (int) $F['photo'];
    } elseif (is_array($F['photo']) && !empty($F['photo']['ID'])) {
        $img_id = (int) $F['photo']['ID'];
    }
}
if (!$img_id) {
    $thumb_id = get_post_thumbnail_id($post_id);
    if ($thumb_id) $img_id = (int) $thumb_id;
}

$img_src    = $img_id ? wp_get_attachment_image_url($img_id, 'medium_large') : '';
$img_srcset = $img_id ? wp_get_attachment_image_srcset($img_id, 'medium_large') : '';
$img_alt    = $img_id ? (get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: $item_h1) : $item_h1;

// === 3) Ещё 3 записи ===
$post_type = post_type_exists('blog') ? 'blog' : get_post_type($post_id);
$related_q = new WP_Query([
    'post_type'           => $post_type,
    'post_status'         => 'publish',
    'posts_per_page'      => 3,
    'post__not_in'        => [$post_id],
    'ignore_sticky_posts' => true,
    'orderby'             => 'date',
    'order'               => 'DESC',
]);

// === 4) ОБРАБОТЧИК КОММЕНТАРИЕВ (кастомная форма) ===
$comment_sent   = false;
$comment_errors = [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_comment_nonce'])
    && wp_verify_nonce($_POST['add_comment_nonce'], 'add_comment_form')
) {
    if (!empty($_POST['website'])) {
        $comment_errors[] = 'Проверка антиспама не пройдена.';
    } else {
        $author  = sanitize_text_field($_POST['author'] ?? '');
        $email   = sanitize_email($_POST['email'] ?? '');
        $content = wp_kses_post($_POST['comment'] ?? '');

        if ($author === '')                                   $comment_errors[] = 'Укажите имя.';
        if (!is_email($email))                                $comment_errors[] = 'Укажите корректный Email.';
        if (trim(wp_strip_all_tags((string)$content)) === '') $comment_errors[] = 'Напишите комментарий.';

        if (empty($comment_errors)) {
            $data = [
                'comment_post_ID'      => $post_id,
                'comment_author'       => $author,
                'comment_author_email' => $email,
                'comment_content'      => $content,
                'user_id'              => get_current_user_id() ?: 0,
                'comment_approved'     => 0, // всегда на модерацию
            ];

            $new = wp_new_comment(wp_slash($data), true);

            if (is_wp_error($new)) {
                if ($new->get_error_code() === 'comment_duplicate') {
                    // Считаем отправленным: такой же комментарий уже есть (скорее всего, ждёт модерации)
                    $comment_sent = true;
                } elseif ($new->get_error_code() === 'comment_flood') {
                    $comment_errors[] = 'Вы отправляете комментарии слишком часто. Попробуйте позже.';
                } else {
                    $comment_errors[] = 'Не удалось отправить: ' . $new->get_error_message();
                }
            } else {
                $comment_sent = true;
                if (!is_user_logged_in()) {
                    $c = get_comment($new);
                    do_action('set_comment_cookies', $c, wp_get_current_user());
                }
            }
        }
    }
}
?>
<main class="max-w-6xl mx-auto px-4 md:px-0 py-8 text-black">
    <?php if (file_exists(get_template_directory() . '/components/breadcrumbs.php')) { ?>
        <div class="-mt-3">
            <?php include get_template_directory() . '/components/breadcrumbs.php'; ?>
        </div>
    <?php } ?>
    <!-- H1 и вводный абзац -->
    <header class="mt-6 mb-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold text-black"><?= esc_html($item_h1); ?></h1>
        <?php if (!empty($item_p)) : ?>
            <p class="mt-4 text-base md:text-lg text-zinc-600"><?= wp_kses_post($item_p); ?></p>
        <?php endif; ?>
    </header>

    <!-- Фото записи -->
    <?php if ($img_src) : ?>
        <figure class="mb-10">
            <div class="relative aspect-[16/9] w-full overflow-hidden rounded-xl bg-zinc-100 border border-zinc-200 shadow-sm">
                <img
                    src="<?= esc_url($img_src); ?>"
                    srcset="<?= esc_attr($img_srcset); ?>"
                    sizes="(max-width: 1024px) 100vw, 1024px"
                    alt="<?= esc_attr($img_alt); ?>"
                    loading="eager"
                    class="absolute inset-0 w-full h-full object-cover" />
            </div>
        </figure>
    <?php endif; ?>

    <!-- SEO-текст (тёмная тема) -->
    <?php if (!empty($seo)) : ?>
        <section class="mt-8">
            <div class="max-w-none seo-dark space-y-4 text-[15px] md:text-[16px] leading-relaxed text-zinc-700 seo">
                <?= wp_kses_post($seo); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Ещё статьи -->
    <?php if ($related_q->have_posts()) : ?>
        <section aria-label="Другие статьи" class="mt-12">
            <h2 class="text-2xl md:text-3xl font-semibold text-black mb-5">Другие статьи</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($related_q->have_posts()) : $related_q->the_post();
                    $rid = get_the_ID();
                    $RF  = get_fields($rid) ?: [];

                    $rh1 = !empty($RF['h1']) ? $RF['h1'] : get_the_title($rid);
                    $rp  = !empty($RF['p'])  ? $RF['p']  : get_the_excerpt($rid);

                    // Картинка карточки
                    $r_img_id = get_post_thumbnail_id($rid);
                    if (!$r_img_id) {
                        if (!empty($RF['photo']) && is_numeric($RF['photo'])) {
                            $r_img_id = (int) $RF['photo'];
                        } elseif (!empty($RF['photo']['ID'])) {
                            $r_img_id = (int) $RF['photo']['ID'];
                        } elseif (!empty($RF['photo_id']) && is_numeric($RF['photo_id'])) {
                            $r_img_id = (int) $RF['photo_id'];
                        }
                    }
                    $r_src    = $r_img_id ? wp_get_attachment_image_url($r_img_id, 'large') : '';
                    $r_srcset = $r_img_id ? wp_get_attachment_image_srcset($r_img_id, 'large') : '';
                    $r_alt    = $r_img_id ? (get_post_meta($r_img_id, '_wp_attachment_image_alt', true) ?: $rh1) : $rh1;
                ?>
                    <article class="rounded-xl border border-zinc-200 bg-white overflow-hidden hover:bg-zinc-50 transition">
                        <a href="<?php the_permalink($rid); ?>" class="block group">
                            <div class="relative aspect-[16/10] w-full overflow-hidden bg-zinc-100">
                                <?php if ($r_src) : ?>
                                    <img
                                        src="<?= esc_url($r_src); ?>"
                                        srcset="<?= esc_attr($r_srcset); ?>"
                                        sizes="(max-width: 1024px) 100vw, 33vw"
                                        alt="<?= esc_attr($r_alt); ?>"
                                        loading="lazy"
                                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                <?php else : ?>
                                    <div class="absolute inset-0 w-full h-full bg-zinc-100"></div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-black leading-snug">
                                <a href="<?php the_permalink($rid); ?>" class="hover:text-[#b50202] transition-colors"><?= esc_html($rh1); ?></a>
                            </h3>
                            <?php if (!empty($rp)) : ?>
                                <p class="mt-2 text-sm text-zinc-600">
                                    <?= wp_kses_post(wp_trim_words($rp, 36, '…')); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile;
                wp_reset_postdata(); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- КОММЕНТАРИИ -->
    <section id="comments" class="max-w-3xl mx-auto mt-12">
        <?php
        $approved_count = get_comments([
            'post_id' => $post_id,
            'status'  => 'approve',
            'count'   => true,
        ]);
        $approved_comments = get_comments([
            'post_id' => $post_id,
            'status'  => 'approve',
            'orderby' => 'comment_date_gmt',
            'order'   => 'ASC',
        ]);
        ?>

        <h2 class="text-2xl md:text-3xl font-semibold text-black mb-5">
            Комментарии (<?= (int) $approved_count; ?>)
        </h2>

        <?php if (!empty($approved_comments)) : ?>
            <ol class="space-y-4">
                <?php foreach ($approved_comments as $comment) : ?>
                    <li id="comment-<?= (int)$comment->comment_ID; ?>" class="rounded-lg border border-zinc-200 bg-white p-4">
                        <div class="text-sm text-zinc-500 mb-1">
                            <strong class="text-black"><?= esc_html($comment->comment_author); ?></strong>
                            <span> • </span>
                            <time datetime="<?= esc_attr(get_comment_time('c', true, $comment)); ?>">
                                <?= esc_html(get_comment_date('d.m.Y', $comment)); ?> в <?= esc_html(get_comment_time('H:i', false, $comment)); ?>
                            </time>
                        </div>
                        <div class="prose max-w-none text-zinc-700">
                            <?= wpautop(esc_html($comment->comment_content)); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else : ?>
            <p class="text-zinc-500">Пока нет комментариев — будьте первыми.</p>
        <?php endif; ?>

        <!-- Сообщения отправки -->
        <?php if ($comment_sent): ?>
            <div class="mb-6 rounded border border-green-700 bg-green-900/30 text-green-300 px-4 py-3">
                Спасибо! Комментарий отправлен и появится после модерации.
            </div>
        <?php elseif (!empty($comment_errors)): ?>
            <div class="mb-6 rounded border border-red-700 bg-red-900/30 text-red-300 px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($comment_errors as $e): ?><li><?= esc_html($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Форма -->
        <form method="post" action="#comments" class="mt-6 space-y-4">
            <?php wp_nonce_field('add_comment_form', 'add_comment_nonce'); ?>
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

            <div>
                <label for="c_author" class="block text-sm text-zinc-600 mb-1">Имя*</label>
                <input id="c_author" name="author" type="text" required
                    class="w-full appearance-none bg-white text-black placeholder-zinc-400
                              border border-zinc-200 rounded-md px-3 py-2
                              focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none" />
            </div>

            <div>
                <label for="c_email" class="block text-sm text-zinc-600 mb-1">Email*</label>
                <input id="c_email" name="email" type="email" required
                    class="w-full appearance-none bg-white text-black placeholder-zinc-400
                              border border-zinc-200 rounded-md px-3 py-2
                              focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none" />
            </div>

            <div>
                <label for="c_comment" class="block text-sm text-zinc-600 mb-1">Комментарий*</label>
                <textarea id="c_comment" name="comment" rows="5" required
                    class="w-full appearance-none bg-white text-black placeholder-zinc-400
                                 border border-zinc-200 rounded-md p-3
                                 focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                    placeholder="Ваш комментарий..."></textarea>
            </div>

            <button type="submit"
                class="inline-flex items-center justify-center rounded-md bg-[#b50202] px-6 py-2 text-white font-semibold hover:bg-[#8d0202] transition">
                Отправить комментарий
            </button>

            <p class="text-xs text-zinc-500 mt-2">
                Комментарий отправится на модерацию и после будет опубликован.
            </p>
        </form>
    </section>
</main>


<?php get_footer();
