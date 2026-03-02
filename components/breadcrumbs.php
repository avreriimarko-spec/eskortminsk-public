<?php

/**
 * Breadcrumbs by URL path — red links, grey chevrons, home icon
 */
defined('ABSPATH') || exit;

$home_label = 'Главная';

/** "my-slug" -> "My Slug" */
$humanize = static function (string $slug): string {
    $slug = urldecode($slug);
    $slug = preg_replace('~[-_]+~u', ' ', $slug);
    if (strtolower($slug) === 'blog') {
        return 'Блог';
    }
    return function_exists('mb_convert_case')
        ? mb_convert_case($slug, MB_CASE_TITLE, 'UTF-8')
        : ucwords(strtolower($slug));
};

$crumbs = [];

// Home
$crumbs[] = ['label' => $home_label, 'url' => home_url('/')];

// Segments from current URL
$path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', trim((string)$path, '/'))));

$accum = '';
$total = count($segments);
foreach ($segments as $i => $seg) {
    $accum  .= '/' . $seg;
    $is_last = ($i === $total - 1);
    $label   = $is_last && is_singular() ? get_the_title() : $humanize($seg);
    $crumbs[] = ['label' => $label, 'url' => $is_last ? '' : home_url($accum . '/')];
}
?>
<nav class="breadcrumbs text-sm mt-4" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2 list-none p-0 m-0">
        <?php foreach ($crumbs as $i => $c): ?>
            <?php if ($i > 0): ?>
                <!-- grey chevron separator -->
                <li aria-hidden="true" class="text-zinc-400">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </li>
            <?php endif; ?>

            <li class="inline-flex items-center">
                <?php if (!empty($c['url'])): ?>
                    <a href="<?= esc_url($c['url']) ?>"
                        class="inline-flex items-center gap-1.5 text-[#b50202] hover:text-[#8d0202] font-semibold transition-colors">
                        <?php if ($i === 0): ?>
                            <!-- home icon -->
                            <svg class="w-4 h-4 -mt-px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 10.5L12 3l9 7.5" />
                                <path d="M5 10v10h14V10" />
                            </svg>
                        <?php endif; ?>
                        <span><?= esc_html($c['label']) ?></span>
                    </a>
                <?php else: ?>
                    <!-- last item -->
                    <span class="text-[#b50202] font-bold"><?= esc_html($c['label']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
