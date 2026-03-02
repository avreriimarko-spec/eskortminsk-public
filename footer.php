<?php 
/* Template Name: Footer */
get_header();

/* Определение текущего пути для подсветки активных ссылок */
$currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if ($currentPath === '') $currentPath = '/';

/**
 * Функция рендера ссылки
 */
$renderNav = function (string $path, string $text) use ($currentPath) {
    $p = rtrim($path, '/');
    $p = $p === '' ? '/' : $p;
    $href = $p === '/' ? '/' : $p . '/';

    if ($currentPath === $p) {
        echo '<span class="text-[#b50202] font-semibold cursor-default">' . $text . '</span>';
    } else {
        echo '<a href="' . $href . '" class="text-zinc-700 hover:text-black transition-colors">' . $text . '</a>';
    }
};
?>

<footer id="siteFooter" class="bg-white text-black border-t border-zinc-200 py-8">
    <div class="max-w-6xl mx-auto px-4 flex flex-col items-center justify-center gap-4">
        
        <!-- Навигация -->
        <ul class="flex flex-wrap items-center justify-center gap-6 text-sm font-medium">
            <li><?php $renderNav('/politika-konfidencialnosti', 'Политика конфиденциальности'); ?></li>
            <li><?php $renderNav('/kontakty', 'Контакты'); ?></li>
            <li><?php $renderNav('/sitemap', 'Карта сайта'); ?></li>
        </ul>

        <!-- Копирайт -->
        <div class="text-xs text-zinc-500">
            © <?= date('Y') ?> Escort Minsk. Все права защищены.
        </div>
        
    </div>

    <?php wp_footer(); ?>
</footer>

<?php 
// Подключаем мобильный компонент (если нужен)
get_template_part('components/under-block-contact'); 
?>

</body>
</html>
