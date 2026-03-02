<?php
$title    = get_field('title');
$descr    = get_field('descr');
$keywords = get_field('keywords');

$telegram  = get_theme_mod('contact_telegram', '');
$whatsapp  = get_theme_mod('contact_whatsapp', '');
?>
<!DOCTYPE html>
<html prefix="og: https://ogp.me/ns#" lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="index, follow">
    <?php
    if (!defined('ABSPATH')) exit;
    $post_id   = get_queried_object_id();
    $site_name = get_bloginfo('name') ?: 'Site';

    $meta_file = get_template_directory() . '/components/seo-meta.php';
    if (file_exists($meta_file)) {
        require_once $meta_file;
    }

    $meta_data = function_exists('kz_generate_meta_data') ? kz_generate_meta_data($post_id) : [];
    $title_out = $meta_data['title'] ?? '';
    $descr_out = $meta_data['description'] ?? '';

    if ($title_out === '') {
        $title_out = $post_id ? (get_the_title($post_id) ?: $site_name) : $site_name;
    }
    if ($descr_out === '') {
        $descr_out = get_bloginfo('description') ?: '';
    }

    $base_title_out = $title_out;
    $base_descr_out = $descr_out;

    $current_page = function_exists('kz_get_current_page_number') ? kz_get_current_page_number() : 1;
    $request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $has_page_segment = (bool) preg_match('~/page/\d+/?$~', $request_path);
    $is_pagination_page = $current_page > 1 || $has_page_segment;
    if ($is_pagination_page) {
        $suffix = ' | Страница ' . $current_page;
        $title_out = rtrim($title_out) . $suffix;
        $descr_out = '';
    }

    $canonical_url = get_pagenum_link($current_page);
    ?><title><?php echo esc_html($title_out); ?></title>
    <?php if (!$is_pagination_page): ?>
        <meta name="description" content="<?php echo esc_attr($descr_out); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?php echo esc_url($canonical_url); ?>">
    <?php if (!empty($keywords)) : ?>
        <meta name="keywords" content="<?php echo esc_attr($keywords); ?>" />
    <?php endif; ?>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <meta property="og:title" content="<?php echo esc_attr($title_out); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="/apple-touch-icon.png" />
    <meta property="og:url" content="<?php echo esc_url($canonical_url); ?>" />
    <meta property="og:locale" content="ru" />
    <?php if (!$is_pagination_page): ?>
        <meta property="og:description" content="<?php echo esc_attr($descr_out); ?>" />
    <?php endif; ?>
    <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>" />
    <meta property="og:image:width" content="180" />
    <meta property="og:image:height" content="180" />
    <meta property="og:image:type" content="image/png" />
    <meta property="og:image:alt" content="<?php echo esc_attr($site_name); ?>" />

    <?php
    // Пробрасываем в глобалы, если где-то используются
    $GLOBALS['seo_title'] = $title_out;
    $GLOBALS['seo_descr'] = $descr_out;

    // JSON-LD
    get_template_part('json-ld/index');

    wp_head();
    ?>
</head>


<body class="bg-white"
    data-base-title="<?php echo esc_attr($base_title_out); ?>"
    data-base-description="<?php echo esc_attr($base_descr_out); ?>"
    data-description-fallback="<?php echo esc_attr($site_name); ?>">

    <?php
    // Подсветка активного пункта
    $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($currentPath === '') $currentPath = '/';

    $phone = get_theme_mod('contact_number');

    // Структура меню (массив данных)
    $menuStructure = [
        [
            'type' => 'link',
            'title' => 'Проститутки',
            'url'   => '/prostitutki-minska/'
        ],
        [
            'type' => 'dropdown',
            'title' => 'Доступность',
            'items' => [
                ['title' => 'Эскорт на выезд', 'url' => '/escort-na-vyyezd/'],
                ['title' => 'Апартаменты',     'url' => '/escort-priem/'],
            ]
        ],
        [
            'type' => 'dropdown',
            'title' => 'Цена',
            'items' => [
                ['title' => 'Дешевые', 'url' => '/deshevye-prostitutki/'],
                ['title' => 'VIP',     'url' => '/ehlitnyj-escort/'],
            ]
        ],
        [
            'type' => 'link',
            'title' => 'Услуги',
            'url'   => '/escort-uslugi/'
        ],
        [
            'type' => 'link',
            'title' => 'Кастинг',
            'url'   => '/casting/'
        ],
        [
            'type' => 'link',
            'title' => 'Блог',
            'url'   => '/blog/'
        ],
    ];

    /**
     * Helper для рендера одной ссылки (используется внутри циклов)
     */
    $renderLink = function ($url, $text, $classes = '') use ($currentPath) {
        $u = rtrim($url, '/');
        $u = $u === '' ? '/' : $u;
        $isActive = ($currentPath === $u);

        if ($isActive) {
            echo '<span class="text-[#b50202] font-semibold cursor-default ' . $classes . '">' . $text . '</span>';
        } else {
            echo '<a href="' . $url . '" class="text-black hover:text-[#b50202] transition-colors ' . $classes . '">' . $text . '</a>';
        }
    };
    ?>

    <header id="siteHeader" class="fixed inset-x-0 top-0 z-50 bg-white text-black border-b border-zinc-200">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-[auto_1fr_auto] items-center h-[60px] md:h-auto">

            <!-- Бургер (моб) -->
            <button id="burgerOpen"
                class="md:hidden -ml-2 inline-flex items-center justify-center w-10 h-10"
                aria-controls="mobileMenu" aria-expanded="false" aria-label="Открыть меню">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M3 6h18M3 12h18M3 18h18" />
                </svg>
            </button>

            <!-- Лого -->
            <?php
            $isHome   = ($currentPath === '/');
            $logoTag  = $isHome ? 'div' : 'a';
            $logoAttr = $isHome ? '' : 'href="/" aria-label="Home"';
            $cursor   = $isHome ? 'cursor-default' : 'cursor-pointer';
            ?>
            <?php
            $logo_src = get_stylesheet_directory_uri() . '/assets/icons/logo.png';
            $logo_path = get_stylesheet_directory() . '/assets/icons/logo.png';
            $logo_w = null;
            $logo_h = null;
            if (is_readable($logo_path)) {
                $logo_size = @getimagesize($logo_path);
                if (is_array($logo_size)) {
                    $logo_w = (int) $logo_size[0];
                    $logo_h = (int) $logo_size[1];
                }
            }
            ?>
            <<?= $logoTag ?> <?= $logoAttr ?> class="justify-self-center md:justify-self-start inline-flex items-center <?= $cursor ?>">
                <img
                    src="<?php echo esc_url($logo_src); ?>"
                    <?= $logo_w ? 'width="' . esc_attr($logo_w) . '"' : '' ?>
                    <?= $logo_h ? 'height="' . esc_attr($logo_h) . '"' : '' ?>
                    alt="Logo"
                    class="h-[30px] w-auto"
                    fetchpriority="high">
            </<?= $logoTag ?>>

            <!-- Десктоп-меню (Центр) -->
            <nav class="hidden md:flex justify-center">
                <div class="px-6 py-3 flex items-center justify-center gap-6 text-base">
                    <?php foreach ($menuStructure as $item) : ?>
                        <?php if ($item['type'] === 'link') : ?>
                            <!-- Простая ссылка -->
                            <?php $renderLink($item['url'], $item['title'], 'whitespace-nowrap font-medium'); ?>

                        <?php elseif ($item['type'] === 'dropdown') : ?>
                            <!-- Выпадающий список (HOVER) -->
                            <div class="relative group py-2">
                                <button class="flex items-center gap-1 text-black hover:text-[#b50202] transition-colors font-medium">
                                    <?= esc_html($item['title']) ?>
                                    <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <!-- Dropdown Body -->
                                <div class="absolute left-1/2 -translate-x-1/2 top-full pt-2 hidden group-hover:block min-w-[200px]">
                                    <div class="bg-white border border-zinc-200 rounded shadow-xl flex flex-col py-2">
                                        <?php foreach ($item['items'] as $subItem) : ?>
                                            <?php $renderLink($subItem['url'], $subItem['title'], 'block px-4 py-2 hover:bg-zinc-50'); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </nav>

            <!-- Правый блок ДЕСКТОП (Только телефон, мессенджеры убраны в мобильное меню) -->
            <div class="hidden md:flex items-center gap-4 justify-self-end">
                <?php if (!empty($phone)): ?>
                    <a href="tel:<?= esc_html($phone) ?>"
                        class="items-center gap-2 text-[#b50202] hover:text-[#8d0202] border border-[#b50202] rounded-lg px-3 py-2 transition hidden lg:inline-flex">
                        <span><?= esc_html($phone) ?></span>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Пустой блок для выравнивания сетки на мобильных справа -->
            <div class="md:hidden w-8"></div>
        </div>

        <!-- Мобильный оверлей-меню -->
        <div id="mobileMenu" class="md:hidden fixed inset-0 z-50 hidden overflow-y-auto bg-white">
            <!-- Хедер мобильного меню -->
            <div class="px-4 py-3 flex items-center justify-between border-b border-zinc-200">
                <div class="text-xl font-bold text-black">Меню</div>
                <button id="burgerClose" class="inline-flex w-10 h-10 items-center justify-center text-black" aria-label="Закрыть меню">
                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6l-12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex flex-col px-6 py-4 space-y-4 text-lg">
                
                <?php foreach ($menuStructure as $item) : ?>
                    <?php if ($item['type'] === 'link') : ?>
                         <!-- Обычная ссылка -->
                         <div class="border-b border-zinc-200 last:border-0">
                            <?php $renderLink($item['url'], $item['title'], 'block py-3 font-medium'); ?>
                         </div>
                    
                    <?php elseif ($item['type'] === 'dropdown') : ?>
                        <!-- Раскрывающийся список (Аккордеон) -->
                        <div class="border-b border-zinc-200 last:border-0">
                            <button type="button" class="js-mobile-accordion-trigger w-full flex items-center justify-between py-3 text-black font-medium hover:text-[#b50202] transition-colors">
                                <span><?= esc_html($item['title']) ?></span>
                                <!-- Иконка стрелки -->
                                <svg class="w-5 h-5 transition-transform duration-300 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <!-- Скрытый контент -->
                            <div class="hidden pl-4 pb-3 space-y-2">
                                <?php foreach ($item['items'] as $subItem) : ?>
                                    <?php $renderLink($subItem['url'], $subItem['title'], 'block py-1 text-zinc-600 hover:text-black text-base'); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>


            </nav>
        </div>
    </header>
    

    <script>
        (function() {
            // ЛОГИКА БУРГЕРА
            const open = document.getElementById('burgerOpen');
            const close = document.getElementById('burgerClose');
            const menu = document.getElementById('mobileMenu');

            if (open && menu) {
                function lockScroll(lock) {
                    document.documentElement.classList.toggle('overflow-hidden', lock);
                    document.body.classList.toggle('overflow-hidden', lock);
                }
                function show() {
                    menu.classList.remove('hidden');
                    open.setAttribute('aria-expanded', 'true');
                    lockScroll(true);
                }
                function hide() {
                    menu.classList.add('hidden');
                    open.setAttribute('aria-expanded', 'false');
                    lockScroll(false);
                }

                open.addEventListener('click', show);
                if (close) close.addEventListener('click', hide);
                
                window.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !menu.classList.contains('hidden')) hide();
                });
            }

            // ЛОГИКА АККОРДЕОНА (РАСКРЫТИЕ МЕНЮ)
            const accordions = document.querySelectorAll('.js-mobile-accordion-trigger');
            accordions.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Находим блок с контентом (следующий элемент)
                    const content = this.nextElementSibling;
                    // Находим стрелку внутри кнопки
                    const arrow = this.querySelector('svg');

                    // Переключаем видимость
                    content.classList.toggle('hidden');
                    
                    // Крутим стрелку
                    if (arrow) {
                        arrow.classList.toggle('rotate-180');
                    }
                });
            });

            const headerEl = document.getElementById('siteHeader');
            if (headerEl) {
                const setHeaderHeight = () => {
                    document.documentElement.style.setProperty('--site-header-h', headerEl.offsetHeight + 'px');
                };
                setHeaderHeight();
                window.addEventListener('resize', setHeaderHeight);
            }

        })();
    </script>
