<?php
// Обработка отправки формы через AJAX
add_action('wp_ajax_send_tg_contact', 'handle_tg_contact_form');
add_action('wp_ajax_nopriv_send_tg_contact', 'handle_tg_contact_form');

function handle_tg_contact_form() {
    // 1. Конфиденциальные данные (скрыты от браузера)
    $bot_token = '8263372828:AAF6Hj5DpQ5rsU3RKYacPs4OIycC8Wjs4mI';
    $chat_id   = '7666504229';

    // 2. Проверка антиспама (Honeypot)
    if (!empty($_POST['website'])) {
        wp_send_json_error(['description' => 'Spam detected']);
    }

    $name  = sanitize_text_field($_POST['name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $page  = esc_url($_POST['page_url'] ?? '');

    if (!$name || !$phone) {
        wp_send_json_error(['description' => 'Fill all fields']);
    }

    // 3. Формируем текст
    $text = "📩 <b>Новая заявка с контактов</b>\n";
    $text .= "👤 Имя: " . htmlspecialchars($name) . "\n";
    $text .= "📞 Телефон: " . htmlspecialchars($phone) . "\n";
    $text .= "🔗 Страница: " . $page . "\n";
    $text .= "🕒 Время: " . current_time('mysql');

    // 4. Отправка через сервер
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $response = wp_remote_post($url, [
        'body' => [
            'chat_id'                  => $chat_id,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => '1'
        ]
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_wp_error($response) || !($body['ok'] ?? false)) {
        wp_send_json_error(['description' => $body['description'] ?? 'Server error']);
    }

    wp_send_json_success();
}
add_action('wp_enqueue_scripts', function () {
    $dir = get_template_directory_uri();

    // База — всегда
    wp_enqueue_style('tailwind', "$dir/assets/css/output.css", [], null);

    // main.js нужен везде (без зависимостей)
    wp_enqueue_script('main-js', "$dir/assets/js/main.js", [], null, true);
    wp_enqueue_script('filter-js', "$dir/assets/js/filter.js", [], null, true);

    // Swiper — только на single модели
    if (is_singular('models')) {
        wp_enqueue_style('swiper-css', "$dir/assets/css/swiper-bundle.min.css", [], null);

        wp_enqueue_script('swiper-js', "$dir/assets/js/swiper-bundle.min.js", [], null, true);
        // Деферим Swiper
        if (function_exists('wp_script_add_data')) {
            wp_script_add_data('swiper-js', 'defer', true);
        }
    }
});



// Ускорение первой картинки в сетке (исправление для LCP)
add_filter( 'wp_get_attachment_image_attributes', function( $attr, $attachment, $size ) {
    global $wp_query;

    // Проверяем: это Главная и это ПЕРВЫЙ пост в списке?
    if ( (is_front_page() || is_home()) && isset($wp_query->current_post) && $wp_query->current_post === 0 && !is_admin() ) {
        // Убираем ленивую загрузку
        unset( $attr['loading'] );
        
        // Добавляем максимальный приоритет
        $attr['fetchpriority'] = 'high';
        
        // Декодируем синхронно (чтобы сразу показать)
        $attr['decoding'] = 'sync';
    }
    
    return $attr;
}, 10, 3 );


add_action('template_redirect', function () {
    ob_start(function ($buffer) {
        // Удаляем блок <script type="speculationrules">...</script>
        return preg_replace(
            '/<script[^>]*type="speculationrules"[^>]*>.*?<\/script>/is',
            '',
            $buffer
        );
    });
});




add_theme_support('post-thumbnails');
add_theme_support('title-tag');
add_theme_support('custom-logo');

add_action('init', function () {
    if (!post_type_exists('models')) {
        register_post_type('models', [
            'labels' => [
                'name'          => 'Модели',
                'singular_name' => 'Модель',
                'add_new'       => 'Добавить модель',
                'add_new_item'  => 'Добавить модель',
                'edit_item'     => 'Редактировать модель',
                'new_item'      => 'Новая модель',
                'view_item'     => 'Смотреть модель',
                'search_items'  => 'Искать модель',
                'menu_name'     => 'Модели',
            ],
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'has_archive'        => true,
            'rewrite'            => ['slug' => 'models', 'with_front' => false],
            'publicly_queryable' => true,
            'query_var'          => true,
        ]);
    }

    $taxonomies = [
            'rayony' => ['label' => 'Районы', 'slug' => 'rayony'],
            'metro'  => ['label' => 'Метро', 'slug' => 'metro'],
            
            // --- ИЗМЕНЕНИЕ НАЧАЛО: Добавляем настройки для Услуг ---
            'uslugi_tax' => [
                'label'   => 'Услуги',
                'slug'    => 'filter-uslugi',
                'public'  => false,
                'rewrite' => false,
            ],
            // --- ИЗМЕНЕНИЕ КОНЕЦ ---

            'cvet_volos_tax'     => ['label' => 'Цвет волос', 'slug' => 'cvet-volos'],
            'razmer_grydi_tax'   => ['label' => 'Размер груди', 'slug' => 'razmer-grudi'],
            'dostupnost_tax'     => ['label' => 'Доступность', 'slug' => 'dostupnost'],
            'drygoe_tax'         => ['label' => 'Другое', 'slug' => 'drygoe'],
            'vozrast_tax'        => ['label' => 'Возраст', 'slug' => 'vozrast'],
            'rost_tax'           => ['label' => 'Рост', 'slug' => 'rost'],
            'ves_tax'            => ['label' => 'Вес', 'slug' => 'ves'],
            'nacionalnost_tax'   => ['label' => 'Национальность', 'slug' => 'nacionalnost'],
            'tip_vneshnosti_tax' => ['label' => 'Тип внешности', 'slug' => 'tip-vneshnosti'],
        ];

    foreach ($taxonomies as $taxonomy => $data) {
        // Проверяем, есть ли индивидуальные настройки, иначе берем стандартные (скрытые)
        $is_public = isset($data['public']) ? $data['public'] : false;
        $rewrite   = isset($data['rewrite']) ? $data['rewrite'] : false;

        register_taxonomy($taxonomy, ['models'], [
            'labels' => [
                'name'          => $data['label'],
                'singular_name' => $data['label'],
            ],
            // Используем переменные:
            'public'             => $is_public,
            'publicly_queryable' => $is_public,
            
            'show_in_nav_menus' => false,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            
            // Вставляем правило rewrite:
            'rewrite'           => $rewrite,
        ]);
    }
}, 5);

add_action('init', function () {
    if (post_type_exists('dostupnost')) {
        return;
    }

    register_post_type('dostupnost', [
        'labels' => [
            'name'          => 'Доступность',
            'singular_name' => 'Доступность',
            'menu_name'     => 'Доступность',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новая запись',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-clock',
        'hierarchical'       => false,
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'dostupnost', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);
}, 6);

add_action('init', function () {
    add_rewrite_rule('^escort-priem/page/([0-9]+)/?$', 'index.php?dostupnost=escort-priem&paged=$matches[1]', 'top');
    add_rewrite_rule('^escort-priem/?$', 'index.php?dostupnost=escort-priem', 'top');
    add_rewrite_rule('^escort-na-vyyezd/page/([0-9]+)/?$', 'index.php?dostupnost=escort-na-vyyezd&paged=$matches[1]', 'top');
    add_rewrite_rule('^escort-na-vyyezd/?$', 'index.php?dostupnost=escort-na-vyyezd', 'top');
}, 7);

add_filter('post_type_link', function ($permalink, $post, $leavename, $sample) {
    if ($post->post_type !== 'dostupnost') {
        return $permalink;
    }
    $slug = $post->post_name ?: (string) $post->ID;
    return home_url(user_trailingslashit($slug));
}, 10, 4);

add_action('init', function () {
    if (get_option('dostupnost_pages_restored')) {
        return;
    }

    $pages_to_restore = [
        'escort-priem'     => 'Эскорт прием (Апартаменты)',
        'escort-na-vyyezd' => 'Эскорт на выезд',
    ];

    foreach ($pages_to_restore as $slug => $title) {
        $existing = get_page_by_path($slug, OBJECT, 'dostupnost');
        if ($existing) {
            continue;
        }

        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page) {
            wp_update_post([
                'ID' => $page->ID,
                'post_type' => 'dostupnost',
                'post_status' => 'publish',
            ]);
            update_post_meta($page->ID, '_wp_page_template', 'pages/home.php');
            continue;
        }

        $post_id = wp_insert_post([
            'post_type'   => 'dostupnost',
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_name'   => $slug,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', 'pages/home.php');
        }
    }

    update_option('dostupnost_pages_restored', 1);
}, 8);

add_action('init', function () {
    if (get_option('dostupnost_terms_cleaned')) {
        return;
    }

    $taxonomy = 'dostupnost_tax';
    $targets = [
        ['slug' => 'apartments', 'name' => 'Апартаменты'],
        ['slug' => 'outcall', 'name' => 'Выезд'],
    ];

    foreach ($targets as $item) {
        $term = get_term_by('slug', $item['slug'], $taxonomy);
        if (!$term || is_wp_error($term)) {
            $term = get_term_by('name', $item['name'], $taxonomy);
        }
        if ($term && !is_wp_error($term)) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }

    update_option('dostupnost_terms_cleaned', 1);
}, 9);

add_action('init', function () {
    if (get_option('dostupnost_rewrite_flushed')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('dostupnost_rewrite_flushed', 1);
}, 10);

add_action('init', function () {
    if (get_option('dostupnost_paged_rewrite_flushed')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('dostupnost_paged_rewrite_flushed', 1);
}, 11);

add_action('init', function () {
    register_post_type('seo_pages', [
        'labels' => [
            'name'          => 'Услуги',
            'singular_name' => 'Лендинг услуги',
            'menu_name'     => 'Лендинги услуг',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'services', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_rayony_pages', [
        'labels' => [
            'name'          => 'Районы',
            'singular_name' => 'Лендинг района',
            'menu_name'     => 'Лендинги районов',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'rayony', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_metro_pages', [
        'labels' => [
            'name'          => 'Метро',
            'singular_name' => 'Лендинг метро',
            'menu_name'     => 'Лендинги метро',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'metro', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_hair_pages', [
        'labels' => [
            'name'          => 'Цвет волос',
            'singular_name' => 'Лендинг цвета волос',
            'menu_name'     => 'Лендинги волос',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'cvet-volos', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_rost_pages', [
        'labels' => [
            'name'          => 'Рост',
            'singular_name' => 'Лендинг роста',
            'menu_name'     => 'Лендинги роста',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'rost', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_ves_pages', [
        'labels' => [
            'name'          => 'Вес',
            'singular_name' => 'Лендинг веса',
            'menu_name'     => 'Лендинги веса',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'ves', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_vozrast_pages', [
        'labels' => [
            'name'          => 'Возраст',
            'singular_name' => 'Лендинг возраста',
            'menu_name'     => 'Лендинги возраста',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'vozrast', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);

    register_post_type('seo_bust_pages', [
        'labels' => [
            'name'          => 'Размер груди',
            'singular_name' => 'Лендинг размера груди',
            'menu_name'     => 'Лендинги груди',
            'add_new'       => 'Добавить',
            'add_new_item'  => 'Добавить лендинг',
            'edit_item'     => 'Редактировать',
            'new_item'      => 'Новый лендинг',
            'view_item'     => 'Посмотреть',
            'search_items'  => 'Найти',
        ],
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'razmer-grudi', 'with_front' => false],
        'publicly_queryable' => true,
        'query_var'          => true,
    ]);
}, 6);

add_filter('theme_page_templates', function ($templates, $theme, $post, $post_type) {
    if (in_array($post_type, ['seo_pages', 'seo_rayony_pages', 'seo_metro_pages', 'seo_hair_pages', 'seo_rost_pages', 'seo_ves_pages', 'seo_vozrast_pages', 'seo_bust_pages'], true)) {
        $templates['tpl-seo-models-grid.php'] = 'Сетка анкет (SEO)';
    }
    return $templates;
}, 10, 4);

add_action('init', function () {
    if (get_option('seo_services_pages_seeded')) {
        return;
    }

    $services = [
        'analnyj-seks' => 'Анальный секс',
        'anilingus' => 'Анилингус',
        'vetka-sakury' => 'Ветка сакуры',
        'glubokij-minet' => 'Глубокий минет',
        'gospozha' => 'Госпожа',
        'gruppovoj-seks' => 'Групповой секс',
        'dvojnoe-proniknovenie' => 'Двойное проникновение',
        'zolotoj-dozhd-priem' => 'Золотой дождь прием',
        'igrushki' => 'Игрушки',
        'klassicheckij-seks' => 'Классичеcкий секс',
        'kopro' => 'Копро',
        'kunilingus' => 'Кунилингус',
        'lyogkoe-dominirovanie' => 'Лёгкое доминирование',
        'lesbi' => 'Лесби',
        'massazh-v-chetyre-ruki' => 'Массаж в четыре руки',
        'massazh-professionalnyj' => 'Массаж профессиональный',
        'minet-bez-prezervativa' => 'Минет без презерватива',
        'minet-v-avto' => 'Минет в авто',
        'minet-v-prezervative' => 'Минет в презервативе',
        'okonchanie-v-rot' => 'Окончание в рот',
        'okonchanie-na-grud' => 'Окончание на грудь',
        'oralnyj-seks' => 'Оральный секс',
        'pipshou' => 'Пипшоу',
        'pomyvka-v-dushe' => 'Помывка в душе',
        'rabynya' => 'Рабыня',
        'rasslablyayushchij-massazh' => 'Расслабляющий массаж',
        'rimming' => 'Римминг',
        'rolevye-igry' => 'Ролевые игры',
        'sadomazo' => 'Садомазо',
        'semejnym-param' => 'Семейным парам',
        'strapon' => 'Страпон',
        'striptiz' => 'Стриптиз',
        'tajskij-massazh' => 'Тайский массаж',
        'tanec-zhivota' => 'Танец живота',
        'urologicheskij-massazh' => 'Урологический массаж',
        'fetish' => 'Фетиш',
        'fisting' => 'Фистинг',
        'ehroticheskij-massazh' => 'Эротический массаж',
    ];

    foreach ($services as $slug => $title) {
        $existing = get_page_by_path($slug, OBJECT, 'seo_pages');
        if ($existing) {
            continue;
        }

        $post_id = wp_insert_post([
            'post_type' => 'seo_pages',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $slug,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
        }
    }

    update_option('seo_services_pages_seeded', 1);
}, 20);

add_action('init', function () {
    if (get_option('seo_rayony_pages_seeded')) {
        return;
    }

    $rayony = [
        'zavodskoj' => 'Заводской',
        'leninskij' => 'Ленинский',
        'moskovskij' => 'Московский',
        'oktyabrskij' => 'Октябрьский',
        'partizanskij' => 'Партизанский',
        'pervomajskij' => 'Первомайский',
        'sovetskij' => 'Советский',
        'frunzenskij' => 'Фрунзенский',
        'centralnyj' => 'Центральный',
    ];

    foreach ($rayony as $slug => $title) {
        $existing = get_page_by_path($slug, OBJECT, 'seo_rayony_pages');
        if ($existing) {
            continue;
        }

        $post_id = wp_insert_post([
            'post_type' => 'seo_rayony_pages',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $slug,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
        }
    }

    update_option('seo_rayony_pages_seeded', 1);
}, 21);

add_action('init', function () {
    if (get_option('seo_metro_pages_seeded')) {
        return;
    }

    $metro = [
        'avtozavodskaya' => 'Автозаводская',
        'akademiya-nauk' => 'Академия Наук',
        'aehrodromnaya' => 'Аэродромная',
        'borisovskij-trakt' => 'Борисовский тракт',
        'vokzalnaya' => 'Вокзальная',
        'vostok' => 'Восток',
        'grushevka' => 'Грушевка',
        'institut-kultury' => 'Институт Культуры',
        'kamennaya-gorka' => 'Каменная горка',
        'kovalskaya-sloboda' => 'Ковальская Слобода',
        'kuncevshchina' => 'Кунцевщина',
        'kupalovskaya' => 'Купаловская',
        'malinovka' => 'Малиновка',
        'mihalovo' => 'Михалово',
        'mogilevskaya' => 'Могилевская',
        'molodezhnaya' => 'Молодежная',
        'moskovskaya' => 'Московская',
        'nemiga' => 'Немига',
        'nemorshanskij-sad' => 'Неморшанский Сад',
        'oktyabrskaya' => 'Октябрьская',
        'park-chelyuskincev' => 'Парк Челюскинцев',
        'partizanskaya' => 'Партизанская',
        'pervomajskaya' => 'Первомайская',
        'petrovshchina' => 'Петровщина',
        'ploshchad-lenina' => 'Площадь Ленина',
        'ploshchad-pobedy' => 'Площадь Победы',
        'ploshchad-frantishka-bogushevicha' => 'Площадь Франтишка Богушевича',
        'ploshchad-yakuba-kolasa' => 'Площадь Якуба Коласа',
        'proletarskaya' => 'Пролетарская',
        'pushkinskaya' => 'Пушкинская',
        'sluckij-gostinec' => 'Слуцкий Гостинец',
        'sportivnaya' => 'Спортивная',
        'traktornyj-zavod' => 'Тракторный завод',
        'uruche' => 'Уручье',
        'frunzenskaya' => 'Фрунзенская',
        'yubilejnaya-ploshchad' => 'Юбилейная площадь',
    ];

    foreach ($metro as $slug => $title) {
        $existing = get_page_by_path($slug, OBJECT, 'seo_metro_pages');
        if ($existing) {
            continue;
        }

        $post_id = wp_insert_post([
            'post_type' => 'seo_metro_pages',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $slug,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
        }
    }

    update_option('seo_metro_pages_seeded', 1);
}, 22);

add_action('init', function () {
    if (get_option('seo_hair_pages_seeded')) {
        return;
    }

    $terms = get_terms([
        'taxonomy' => 'cvet_volos_tax',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $existing = get_page_by_path($term->slug, OBJECT, 'seo_hair_pages');
            if ($existing) {
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'seo_hair_pages',
                'post_status' => 'publish',
                'post_title' => $term->name,
                'post_name' => $term->slug,
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            }
        }
    }

    update_option('seo_hair_pages_seeded', 1);
}, 23);

add_action('init', function () {
    if (get_option('seo_rost_pages_seeded')) {
        return;
    }

    $terms = get_terms([
        'taxonomy' => 'rost_tax',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $existing = get_page_by_path($term->slug, OBJECT, 'seo_rost_pages');
            if ($existing) {
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'seo_rost_pages',
                'post_status' => 'publish',
                'post_title' => $term->name,
                'post_name' => $term->slug,
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            }
        }
    }

    update_option('seo_rost_pages_seeded', 1);
}, 24);

add_action('init', function () {
    if (get_option('seo_ves_pages_seeded')) {
        return;
    }

    $terms = get_terms([
        'taxonomy' => 'ves_tax',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $existing = get_page_by_path($term->slug, OBJECT, 'seo_ves_pages');
            if ($existing) {
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'seo_ves_pages',
                'post_status' => 'publish',
                'post_title' => $term->name,
                'post_name' => $term->slug,
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            }
        }
    }

    update_option('seo_ves_pages_seeded', 1);
}, 25);

add_action('init', function () {
    if (get_option('seo_vozrast_pages_seeded')) {
        return;
    }

    $terms = get_terms([
        'taxonomy' => 'vozrast_tax',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $existing = get_page_by_path($term->slug, OBJECT, 'seo_vozrast_pages');
            if ($existing) {
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'seo_vozrast_pages',
                'post_status' => 'publish',
                'post_title' => $term->name,
                'post_name' => $term->slug,
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            }
        }
    }

    update_option('seo_vozrast_pages_seeded', 1);
}, 26);

add_action('init', function () {
    if (get_option('seo_bust_pages_seeded')) {
        return;
    }

    $terms = get_terms([
        'taxonomy' => 'razmer_grydi_tax',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $existing = get_page_by_path($term->slug, OBJECT, 'seo_bust_pages');
            if ($existing) {
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'seo_bust_pages',
                'post_status' => 'publish',
                'post_title' => $term->name,
                'post_name' => $term->slug,
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            }
        }
    }

    update_option('seo_bust_pages_seeded', 1);
}, 27);

add_action('init', function () {
    if (get_option('seo_extra_ranges_seeded')) {
        return;
    }

    $extra_terms = [
        [
            'taxonomy' => 'rost_tax',
            'slug' => 'rost-176-plus-sm',
            'name' => '176+ см',
            'post_type' => 'seo_rost_pages',
            'post_title' => 'Рост 176+ см',
        ],
        [
            'taxonomy' => 'ves_tax',
            'slug' => 'ves-66-plus-kg',
            'name' => '66+ кг',
            'post_type' => 'seo_ves_pages',
            'post_title' => 'Вес 66+ кг',
        ],
        [
            'taxonomy' => 'vozrast_tax',
            'slug' => 'vozrast-40-plus',
            'name' => '40+ лет',
            'post_type' => 'seo_vozrast_pages',
            'post_title' => 'Возраст 40+ лет',
        ],
    ];

    foreach ($extra_terms as $item) {
        $term = get_term_by('slug', $item['slug'], $item['taxonomy']);
        if (!$term || is_wp_error($term)) {
            $created = wp_insert_term($item['name'], $item['taxonomy'], ['slug' => $item['slug']]);
            if (!is_wp_error($created)) {
                $term = get_term($created['term_id'], $item['taxonomy']);
            }
        }

        $existing = get_page_by_path($item['slug'], OBJECT, $item['post_type']);
        if (!$existing) {
            $post_id = wp_insert_post([
                'post_type' => $item['post_type'],
                'post_status' => 'publish',
                'post_title' => $item['post_title'],
                'post_name' => $item['slug'],
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            }
        }
    }

    update_option('seo_extra_ranges_seeded', 1);
}, 28);

add_action('init', function () {
    if (get_option('seo_geo_slugs_fixed')) {
        return;
    }

    $rayony = [
        'zavodskoj' => 'Заводской',
        'leninskij' => 'Ленинский',
        'moskovskij' => 'Московский',
        'oktyabrskij' => 'Октябрьский',
        'partizanskij' => 'Партизанский',
        'pervomajskij' => 'Первомайский',
        'sovetskij' => 'Советский',
        'frunzenskij' => 'Фрунзенский',
        'centralnyj' => 'Центральный',
    ];

    foreach ($rayony as $slug => $title) {
        $post = get_page_by_path($slug, OBJECT, 'seo_rayony_pages');
        if (!$post) {
            $post = get_page_by_title($title, OBJECT, 'seo_rayony_pages');
        }
        if ($post && $post->post_name !== $slug) {
            wp_update_post([
                'ID' => $post->ID,
                'post_name' => $slug,
            ]);
        }
    }

    $metro = [
        'avtozavodskaya' => 'Автозаводская',
        'akademiya-nauk' => 'Академия Наук',
        'aehrodromnaya' => 'Аэродромная',
        'borisovskij-trakt' => 'Борисовский тракт',
        'vokzalnaya' => 'Вокзальная',
        'vostok' => 'Восток',
        'grushevka' => 'Грушевка',
        'institut-kultury' => 'Институт Культуры',
        'kamennaya-gorka' => 'Каменная горка',
        'kovalskaya-sloboda' => 'Ковальская Слобода',
        'kuncevshchina' => 'Кунцевщина',
        'kupalovskaya' => 'Купаловская',
        'malinovka' => 'Малиновка',
        'mihalovo' => 'Михалово',
        'mogilevskaya' => 'Могилевская',
        'molodezhnaya' => 'Молодежная',
        'moskovskaya' => 'Московская',
        'nemiga' => 'Немига',
        'nemorshanskij-sad' => 'Неморшанский Сад',
        'oktyabrskaya' => 'Октябрьская',
        'park-chelyuskincev' => 'Парк Челюскинцев',
        'partizanskaya' => 'Партизанская',
        'pervomajskaya' => 'Первомайская',
        'petrovshchina' => 'Петровщина',
        'ploshchad-lenina' => 'Площадь Ленина',
        'ploshchad-pobedy' => 'Площадь Победы',
        'ploshchad-frantishka-bogushevicha' => 'Площадь Франтишка Богушевича',
        'ploshchad-yakuba-kolasa' => 'Площадь Якуба Коласа',
        'proletarskaya' => 'Пролетарская',
        'pushkinskaya' => 'Пушкинская',
        'sluckij-gostinec' => 'Слуцкий Гостинец',
        'sportivnaya' => 'Спортивная',
        'traktornyj-zavod' => 'Тракторный завод',
        'uruche' => 'Уручье',
        'frunzenskaya' => 'Фрунзенская',
        'yubilejnaya-ploshchad' => 'Юбилейная площадь',
    ];

    foreach ($metro as $slug => $title) {
        $post = get_page_by_path($slug, OBJECT, 'seo_metro_pages');
        if (!$post) {
            $post = get_page_by_title($title, OBJECT, 'seo_metro_pages');
        }
        if ($post && $post->post_name !== $slug) {
            wp_update_post([
                'ID' => $post->ID,
                'post_name' => $slug,
            ]);
        }
    }

    update_option('seo_geo_slugs_fixed', 1);
}, 23);

add_action('init', function () {
    if (get_option('seo_geo_terms_fixed')) {
        return;
    }

    $rayony = [
        'zavodskoj' => 'Заводской',
        'leninskij' => 'Ленинский',
        'moskovskij' => 'Московский',
        'oktyabrskij' => 'Октябрьский',
        'partizanskij' => 'Партизанский',
        'pervomajskij' => 'Первомайский',
        'sovetskij' => 'Советский',
        'frunzenskij' => 'Фрунзенский',
        'centralnyj' => 'Центральный',
    ];

    foreach ($rayony as $slug => $title) {
        $term = get_term_by('slug', $slug, 'rayony');
        if (!$term) {
            $term = get_term_by('name', $title, 'rayony');
        }
        if ($term && !is_wp_error($term) && $term->slug !== $slug) {
            wp_update_term($term->term_id, 'rayony', ['slug' => $slug]);
        }
    }

    $metro = [
        'avtozavodskaya' => 'Автозаводская',
        'akademiya-nauk' => 'Академия Наук',
        'aehrodromnaya' => 'Аэродромная',
        'borisovskij-trakt' => 'Борисовский тракт',
        'vokzalnaya' => 'Вокзальная',
        'vostok' => 'Восток',
        'grushevka' => 'Грушевка',
        'institut-kultury' => 'Институт Культуры',
        'kamennaya-gorka' => 'Каменная горка',
        'kovalskaya-sloboda' => 'Ковальская Слобода',
        'kuncevshchina' => 'Кунцевщина',
        'kupalovskaya' => 'Купаловская',
        'malinovka' => 'Малиновка',
        'mihalovo' => 'Михалово',
        'mogilevskaya' => 'Могилевская',
        'molodezhnaya' => 'Молодежная',
        'moskovskaya' => 'Московская',
        'nemiga' => 'Немига',
        'nemorshanskij-sad' => 'Неморшанский Сад',
        'oktyabrskaya' => 'Октябрьская',
        'park-chelyuskincev' => 'Парк Челюскинцев',
        'partizanskaya' => 'Партизанская',
        'pervomajskaya' => 'Первомайская',
        'petrovshchina' => 'Петровщина',
        'ploshchad-lenina' => 'Площадь Ленина',
        'ploshchad-pobedy' => 'Площадь Победы',
        'ploshchad-frantishka-bogushevicha' => 'Площадь Франтишка Богушевича',
        'ploshchad-yakuba-kolasa' => 'Площадь Якуба Коласа',
        'proletarskaya' => 'Пролетарская',
        'pushkinskaya' => 'Пушкинская',
        'sluckij-gostinec' => 'Слуцкий Гостинец',
        'sportivnaya' => 'Спортивная',
        'traktornyj-zavod' => 'Тракторный завод',
        'uruche' => 'Уручье',
        'frunzenskaya' => 'Фрунзенская',
        'yubilejnaya-ploshchad' => 'Юбилейная площадь',
    ];

    foreach ($metro as $slug => $title) {
        $term = get_term_by('slug', $slug, 'metro');
        if (!$term) {
            $term = get_term_by('name', $title, 'metro');
        }
        if ($term && !is_wp_error($term) && $term->slug !== $slug) {
            wp_update_term($term->term_id, 'metro', ['slug' => $slug]);
        }
    }

    update_option('seo_geo_terms_fixed', 1);
}, 24);

/**
 * 2. Исправляем пагинацию для SEO страниц (Фикс "Non-canonical" ошибок)
 * Добавляем правила с /page/([0-9]+)/
 */
add_action('init', function () {
    // Районы
    add_rewrite_rule('^rayony/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_rayony_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^rayony/([^/]+)/?$', 'index.php?seo_rayony_pages=$matches[1]', 'top');

    // Метро
    add_rewrite_rule('^metro/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_metro_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^metro/([^/]+)/?$', 'index.php?seo_metro_pages=$matches[1]', 'top');

    // Услуги (Сервисы) - если используются
    add_rewrite_rule('^services/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^services/([^/]+)/?$', 'index.php?seo_pages=$matches[1]', 'top');

    // Цвет волос
    add_rewrite_rule('^cvet-volos/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_hair_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^cvet-volos/([^/]+)/?$', 'index.php?seo_hair_pages=$matches[1]', 'top');

    // Рост
    add_rewrite_rule('^rost/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_rost_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^rost/([^/]+)/?$', 'index.php?seo_rost_pages=$matches[1]', 'top');

    // Вес
    add_rewrite_rule('^ves/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_ves_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^ves/([^/]+)/?$', 'index.php?seo_ves_pages=$matches[1]', 'top');

    // Возраст
    add_rewrite_rule('^vozrast/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_vozrast_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^vozrast/([^/]+)/?$', 'index.php?seo_vozrast_pages=$matches[1]', 'top');

    // Размер груди
    add_rewrite_rule('^razmer-grudi/([^/]+)/page/([0-9]+)/?$', 'index.php?seo_bust_pages=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^razmer-grudi/([^/]+)/?$', 'index.php?seo_bust_pages=$matches[1]', 'top');
}, 25);

add_filter('sm_sitemap_exclude_taxonomy', function (array $taxonomies): array {
    $exclude = [
        'rayony',
        'metro',
        'uslugi_tax',
        'cvet_volos_tax',
        'razmer_grydi_tax',
        'dostupnost_tax',
        'drygoe_tax',
        'vozrast_tax',
        'rost_tax',
        'ves_tax',
        'nacionalnost_tax',
        'tip_vneshnosti_tax',
    ];
    return array_values(array_unique(array_merge($taxonomies, $exclude)));
});

add_action('init', function () {
    if (get_option('seo_geo_rewrite_flushed')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('seo_geo_rewrite_flushed', 1);
}, 26);

add_action('init', function () {
    if (get_option('seo_body_rewrite_flushed')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('seo_body_rewrite_flushed', 1);
}, 27);

add_action('init', function () {
    if (get_option('seo_age_rewrite_flushed')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('seo_age_rewrite_flushed', 1);
}, 28);

add_action('init', function () {
    if (get_option('seo_bust_rewrite_flushed')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('seo_bust_rewrite_flushed', 1);
}, 29);


add_filter('single_template', function ($template) {
    if (!is_singular(['seo_pages', 'seo_rayony_pages', 'seo_metro_pages', 'seo_hair_pages', 'seo_rost_pages', 'seo_ves_pages', 'seo_vozrast_pages', 'seo_bust_pages'])) {
        return $template;
    }
    $custom = locate_template('tpl-seo-models-grid.php', false, false);
    return $custom ?: $template;
});

add_filter('pre_get_document_title', function ($title) {
    if (!is_singular(['seo_pages', 'seo_rayony_pages', 'seo_metro_pages', 'seo_hair_pages', 'seo_rost_pages', 'seo_ves_pages', 'seo_vozrast_pages', 'seo_bust_pages'])) {
        return $title;
    }
    $custom_title = '';
    if (function_exists('get_field')) {
        $custom_title = (string) get_field('title');
    }
    if ($custom_title === '') {
        $custom_title = (string) get_post_meta(get_queried_object_id(), 'title', true);
    }
    $custom_title = trim($custom_title);
    $title_out = $custom_title !== '' ? $custom_title : $title;
    $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
    if ($paged > 1) {
        $title_out .= ' — Страница ' . $paged;
    }
    return $title_out;
}, 10, 1);


function remove_unused_scripts()
{
    wp_dequeue_script('jquery'); // Отключаем jQuery, если не нужен
}
add_action('wp_enqueue_scripts', 'remove_unused_scripts', 100);


function disable_classic_theme_styles()
{
    remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');
}
add_action('wp_enqueue_scripts', 'disable_classic_theme_styles', 1);

remove_action('wp_head', 'wp_generator');
// Удаление генерации ссылок на API
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('template_redirect', 'rest_output_link_header', 11, 0);
// Удаление RSD ссылки
remove_action('wp_head', 'rsd_link');
// Удаление shortlink
remove_action('wp_head', 'wp_shortlink_wp_head', 10);
remove_action('template_redirect', 'wp_shortlink_header', 11);
// Удаление oEmbed ссылок
remove_action('wp_head', 'wp_oembed_add_host_js');
// Удаление meta-тегов, связанных с Windows Tiles
remove_action('wp_head', 'wp_site_icon', 99);
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');


function move_jquery_to_footer()
{
    if (!is_admin()) {
        wp_deregister_script('jquery'); // Отключаем стандартное подключение
        wp_register_script(
            'jquery',
            includes_url('/js/jquery/jquery.min.js'),
            array(),
            null,
            true // Подключаем в футере
        );
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'move_jquery_to_footer');

function disable_global_styles()
{
    wp_dequeue_style('global-styles'); // Отключаем стили
    wp_dequeue_style('wp-block-library'); // Отключаем базовые стили блоков
    wp_dequeue_style('wp-block-library-theme'); // Отключаем стили темы
}
add_action('wp_enqueue_scripts', 'disable_global_styles', 100);

function remove_block_css()
{
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style'); // Для WooCommerce
}
add_action('wp_enqueue_scripts', 'remove_block_css', 100);

add_filter('use_block_editor_for_post', '__return_false', 10);
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
}, 100);
/**
 * 1. Восстанавливаем удаленные страницы (Фикс 404)
 * Эти страницы создаются как обычные 'page' в корне.
 */
add_action('init', function () {
    if (get_option('seo_lost_pages_restored')) {
        return;
    }

    $pages_to_restore = [
        'escort-priem'     => 'Эскорт прием (Апартаменты)',
        'escort-na-vyyezd' => 'Эскорт на выезд',
        'ehlitnyj-escort'  => 'Элитный эскорт', // Тоже было в списке 301
        'prostitutki-minska' => 'Проститутки Минска', // Было в списке
        'deshevye-prostitutki' => 'Дешевые проститутки',
    ];

    foreach ($pages_to_restore as $slug => $title) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing) {
            continue;
        }

        $post_id = wp_insert_post([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_name'   => $slug,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            // Назначаем им SEO шаблон, чтобы там работала сетка анкет
            update_post_meta($post_id, '_wp_page_template', 'tpl-seo-models-grid.php');
            
            // Если нужно, тут можно проставить мета-поля для фильтрации
            // update_post_meta($post_id, 'filter_param', 'value');
        }
    }

    update_option('seo_lost_pages_restored', 1);
}, 50);


function custom_contact_settings($wp_customize)
{
    // Добавляем раздел в настройках
    $wp_customize->add_section('contact_section', array(
        'title' => __('Контактные данные', 'textdomain'),
        'description' => __('Здесь вы можете настроить контактные данные', 'textdomain'),
        'priority' => 30,
    ));

    $wp_customize->add_section('model_card_section', array(
        'title' => __('Карточка анкеты', 'textdomain'),
        'description' => __('Настройки отображения карточки анкеты', 'textdomain'),
        'priority' => 31,
    ));

    // Добавляем настройку для имени сайта (site_name)
    $wp_customize->add_setting('contact_site_name', array(
        'default' => get_bloginfo('name'),
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_site_name', array(
        'label' => __('Название сайта (РУС)', 'textdomain'),
        'section' => 'contact_section',
        'type' => 'text',
        'description' => __('Введите отображаемое имя сайта (на русском)', 'textdomain'),
    ));


    // Добавляем настройку для Telegram
    $wp_customize->add_setting('contact_telegram', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_telegram', array(
        'label' => __('Telegram', 'textdomain'),
        'section' => 'contact_section',
        'type' => 'text',
        'description' => __('Введите ваш Telegram-ник или ссылку, например: username', 'textdomain'),
    ));

    $wp_customize->add_setting('contact_telegram_channel', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_telegram_channel', array(
        'label'       => __('Telegram-канал (юзернейм)', 'textdomain'),
        'section'     => 'contact_section',
        'type'        => 'text',
        'description' => __('Введите юзернейм канала: @mychannel или mychannel', 'textdomain'),
    ));

    // Добавляем настройку для WhatsApp
    $wp_customize->add_setting('contact_whatsapp', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_whatsapp', array(
        'label' => __('WhatsApp', 'textdomain'),
        'section' => 'contact_section',
        'type' => 'text',
        'description' => __('Введите номер телефона для WhatsApp в формате: 1234567890', 'textdomain'),
    ));

    // Добавляем настройку для Number

    $wp_customize->add_setting('contact_number', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_number', array(
        'label' => __('Number', 'textdomain'),
        'section' => 'contact_section',
        'type' => 'text',
        'description' => __('Введите номер телефона формате: +1234567890', 'textdomain'),
    ));


    // Добавляем настройку для EMAIL

    $wp_customize->add_setting('contact_email', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_email', array(
        'label' => __('Email', 'textdomain'),
        'section' => 'contact_section',
        'type' => 'text',
        'description' => __('Введите вашу почту', 'textdomain'),
    ));


    // Добавляем настройку для STREET

    $wp_customize->add_setting('contact_street', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('contact_street', array(
        'label' => __('Street', 'textdomain'),
        'section' => 'contact_section',
        'type' => 'text',
        'description' => __('Введите улицы вашей агенции', 'textdomain'),
    ));

    $wp_customize->add_setting('model_card_desc_length', array(
        'default' => 180,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('model_card_desc_length', array(
        'label' => __('Длина описания в карточке (символы)', 'textdomain'),
        'section' => 'model_card_section',
        'type' => 'number',
        'description' => __('Рекомендуемый диапазон: 160–200 символов', 'textdomain'),
        'input_attrs' => array(
            'min' => 160,
            'max' => 200,
            'step' => 5,
        ),
    ));
}

add_action('customize_register', 'custom_contact_settings');

function get_contact_whatsapp()
{
    $number = get_theme_mod('contact_number');
    if ($number) {
        // Удаляем "+" в начале, если он есть
        $number = ltrim($number, '+');
        return 'https://wa.me/' . esc_attr($number);
    }
}
add_shortcode('whatsapp_button', 'get_contact_whatsapp');


function get_contact_telegram()
{
    $telegram = get_theme_mod('contact_telegram');
    if ($telegram) {
        return 'https://t.me/' . esc_attr($telegram);
    }
}
add_shortcode('telegram_button', 'get_contact_telegram');


remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('template_redirect', 'rest_output_link_header', 11);
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical');
// REST API: allow for authenticated users (admin/media), deny for guests.
add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }
    if (is_user_logged_in()) {
        return $result;
    }
    return new WP_Error('rest_forbidden', 'REST API restricted.', ['status' => 401]);
});
remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('template_redirect', 'rest_output_link_header', 11);
remove_action('auth_cookie_malformed', 'rest_cookie_collect_status');
remove_action('auth_cookie_expired', 'rest_cookie_collect_status');
remove_action('auth_cookie_bad_username', 'rest_cookie_collect_status');
remove_action('auth_cookie_bad_hash', 'rest_cookie_collect_status');
remove_action('auth_cookie_valid', 'rest_cookie_collect_status');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('template_redirect', 'rest_output_link_header', 11);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', '_wp_render_title_tag', 1);
add_filter('xmlrpc_enabled', '__return_false');



if (!function_exists('kz_get_current_page_number')) {
    function kz_get_current_page_number(): int {
        $paged = (int) get_query_var('paged');
        if ($paged < 1) {
            $page = (int) get_query_var('page');
            $paged = $page > 1 ? $page : 1;
        }
        return $paged > 0 ? $paged : 1;
    }
}

if (!function_exists('kz_is_pagination_page')) {
    function kz_is_pagination_page(): bool {
        return kz_get_current_page_number() > 1;
    }
}

/**
 * Добавляем rewrite-правило для страницы блога, чтобы /blog/page/2 открывался без 404.
 * Основано на шаблоне pages/blog.php (если такой назначен странице).
 */
add_action('init', function () {
    $blog_pages = get_posts([
        'post_type'      => 'page',
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'pages/blog.php',
    ]);

    if (empty($blog_pages)) {
        return;
    }

    $page_id = (int) $blog_pages[0];
    $slug    = trim(get_page_uri($page_id), '/');
    if ($slug === '') {
        return;
    }

    add_rewrite_rule("^{$slug}/page/([0-9]+)/?$", "index.php?pagename={$slug}&paged=\$matches[1]", 'top');
});

add_action('save_post', function ($post_id) {
    if (get_post_type($post_id) !== 'page')
        return;

    // Принудительно обновляем `content`, даже если он есть
    $acf_data = get_post_meta($post_id, 'content', true);
    update_post_meta($post_id, 'content', $acf_data);
}, 10, 1);




/**
 * Проставляет таксономии и районы/метро для CPT "models", с подробным логированием.
 */

// Используем ACF-хук, чтобы точно получить сохранённые поля
add_action('acf/save_post', 'assign_model_taxonomies_and_location', 20);
function assign_model_taxonomies_and_location($post_id)
{
    // 1) Пропускаем автосохранения и ревизии
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    // 2) Только для CPT 'models'
    if (get_post_type($post_id) !== 'models') {
        return;
    }

    //
    // ────── ЧАСТЬ A: Случайные районы + метро ──────
    //
    $existing_rayony = wp_get_object_terms($post_id, 'rayony', ['fields' => 'ids']);
    $existing_metro  = wp_get_object_terms($post_id, 'metro',  ['fields' => 'ids']);

    if (empty($existing_rayony)) {
        $rayony_terms = get_terms(['taxonomy' => 'rayony', 'hide_empty' => false]);
        if (!is_wp_error($rayony_terms) && $rayony_terms) {
            $count = min(3, count($rayony_terms));
            $picked = (array) array_rand($rayony_terms, $count);
            $rayony_ids = [];
            foreach ($picked as $idx) {
                $term = $rayony_terms[$idx];
                $rayony_ids[] = (int) $term->term_id;
            }
            if ($rayony_ids) {
                wp_set_object_terms($post_id, array_unique($rayony_ids), 'rayony', false);
            }
        }
    }

    if (empty($existing_metro)) {
        $metro_terms = get_terms(['taxonomy' => 'metro', 'hide_empty' => false]);
        if (!is_wp_error($metro_terms) && $metro_terms) {
            $count = min(5, count($metro_terms));
            $picked = (array) array_rand($metro_terms, $count);
            $metro_ids = [];
            foreach ($picked as $idx) {
                $term = $metro_terms[$idx];
                $metro_ids[] = (int) $term->term_id;
            }
            if ($metro_ids) {
                wp_set_object_terms($post_id, array_unique($metro_ids), 'metro', false);
            }
        }
    }

    //
    // ────── ЧАСТЬ B: Таксономии по полям модели ──────
    //

    // Получаем поля модели
    $price_raw = get_field('price', $post_id);
    $price     = (float) $price_raw;

    $age    = (int)   get_field('age',    $post_id);
    $height = (int)   get_field('height', $post_id);
    $weight = (int)   get_field('weight', $post_id);
    $bust   = (float) get_field('bust',   $post_id);

    // Helper: поиск слуга по диапазону
    $pick = function ($value, $map) {
        foreach ($map as $slug => $rng) {
            if ($value >= $rng[0] && $value <= $rng[1]) {
                return $slug;
            }
        }
        return null;
    };

    $age_map = [
        '18-22'     => [18, 22],
        '22-25'     => [22, 25],
        '25-28'     => [25, 28],
        'bolshe-28' => [29, 999],
    ];
    $height_map = [
        'menshe-160' => [0, 160],
        '160-165'    => [160, 165],
        '165-170'    => [165, 170],
        '170-175'    => [170, 175],
        'bolshe-175' => [176, 999],
    ];
    $weight_map = [
        'menshe-50' => [0, 50],
        '50-55'     => [50, 55],
        '55-60'     => [55, 60],
        'bolshe-60' => [61, 999],
    ];
    $bust_map = [
        'menshe-1' => [0, 1],
        '1-2'      => [1, 2],
        '2-3'      => [2, 3],
        '4-5'      => [4, 5],
        'bolshe-5' => [5, 999],
    ];

    // Собираем слуги
    $tax_args = [];
    if ($slug = $pick($age,    $age_map))    $tax_args['vozrast_tax'][]   = $slug;
    if ($slug = $pick($height, $height_map)) $tax_args['rost_tax'][]      = $slug;
    if ($slug = $pick($weight, $weight_map)) $tax_args['ves_tax'][]       = $slug;
    if ($slug = $pick($bust,   $bust_map))   $tax_args['razmer-grudi'][] = $slug;

    // Проставляем термины
    foreach ($tax_args as $taxonomy => $slugs) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }
        // Получаем уже назначенные слуги
        $existing = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
        if (is_wp_error($existing)) {
            continue;
        }

        // Для services_tax: добавляем новые, не трогая старые
        if ($taxonomy === 'services_tax') {
            // Убираем из слуг те, что уже есть
            $new_slugs = array_diff($slugs, $existing);
            if (empty($new_slugs)) {
                continue;
            }
            // Получаем term_id для новых слуг
            $term_ids = [];
            foreach ($new_slugs as $s) {
                if ($t = get_term_by('slug', $s, $taxonomy)) {
                    $term_ids[] = (int)$t->term_id;
                }
            }
            // Append = true, чтобы не перезаписать старые
            wp_set_object_terms($post_id, $term_ids, $taxonomy, true);
        }
        // Для остальных таксономий — только если раньше пусто
        else {
            if (!empty($existing)) {
                continue;
            }
            $term_ids = [];
            foreach ($slugs as $s) {
                if ($t = get_term_by('slug', $s, $taxonomy)) {
                    $term_ids[] = (int)$t->term_id;
                }
            }
            if ($term_ids) {
                wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
            }
        }
    }
}


/**
 * Блокировка обновлений ACF Pro и исправление ошибки 403
 */
// 1. Отключаем настройку показа обновлений внутри самого ACF
add_filter('acf/settings/show_updates', '__return_false', 100);

// 2. Скрываем вкладку "Обновления" в настройках ACF (если есть)
add_filter('acf/settings/show_admin', '__return_true'); // Оставляем админку, но...
add_filter('acf/settings/license', '__return_null');   // ...удаляем лицензию из кода

// 3. Удаляем плагин из общего списка обновлений WordPress (transient)
add_filter('site_transient_update_plugins', function ($value) {
    // Список возможных путей к файлу плагина (у вас advanced-custom-fields-pro-main)
    $plugins_to_disable = [
        'advanced-custom-fields-pro/acf.php',
        'advanced-custom-fields-pro-main/acf.php', // Ваша папка
        'acf-pro/acf.php'
    ];

    if (isset($value->response) && is_array($value->response)) {
        foreach ($plugins_to_disable as $plugin) {
            if (isset($value->response[$plugin])) {
                unset($value->response[$plugin]);
            }
        }
    }
    
    // Также чистим no_update, чтобы не висело в "проверенных"
    if (isset($value->no_update) && is_array($value->no_update)) {
        foreach ($plugins_to_disable as $plugin) {
            if (isset($value->no_update[$plugin])) {
                unset($value->no_update[$plugin]);
            }
        }
    }

    return $value;
}, 999);

// 4. ЖЕСТКАЯ БЛОКИРОВКА ЗАПРОСА (Решение ошибки 403)
// Если WP попытается стукнуться на connect.advancedcustomfields.com, мы прерываем запрос.
add_filter('pre_http_request', function ($pre, $args, $url) {
    if (strpos($url, 'connect.advancedcustomfields.com') !== false || strpos($url, 'advancedcustomfields.com') !== false) {
        // Возвращаем пустой успешный ответ или ошибку, чтобы WP не ждал таймаута
        return [
            'headers'  => [],
            'body'     => '',
            'response' => [
                'code'    => 200,
                'message' => 'OK',
            ],
            'cookies'  => [],
            'filename' => null,
        ];
    }
    return $pre;
}, 10, 3);



/**
 * Глобальное отключение редиректа для пагинации (/page/X)
 */
add_filter('redirect_canonical', 'disable_global_pagination_redirect', 10, 2);

function disable_global_pagination_redirect($redirect_url, $requested_url) {
    // 1. Проверяем переменные запроса WordPress (самый надежный способ)
    if (get_query_var('paged') > 1 || get_query_var('page') > 1) {
        return false; // Отменяем редирект
    }

    // 2. Дополнительная проверка по самой строке URL (страховка)
    // Если в ссылке встречается "/page/" и за ней идут цифры
    if (preg_match('#/page/[0-9]+#', $requested_url)) {
        return false; // Отменяем редирект
    }

    return $redirect_url;
}

add_action('init', function () {
    if (get_option('services_elite_term_removed')) {
        return;
    }

    $taxonomy = 'services_tax';
    if (taxonomy_exists($taxonomy)) {
        $term = get_term_by('slug', 'elitnyy-escort', $taxonomy);
        if (!$term || is_wp_error($term)) {
            $term = get_term_by('name', 'Элитный эскорт', $taxonomy);
        }
        if ($term && !is_wp_error($term)) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }

    update_option('services_elite_term_removed', 1);
}, 99);

add_action('init', function () {
    if (get_option('drygoe_elite_term_removed')) {
        return;
    }

    $taxonomy = 'drygoe_tax';
    if (taxonomy_exists($taxonomy)) {
        $term = get_term_by('slug', 'ehlitnyj-escort', $taxonomy);
        if (!$term || is_wp_error($term)) {
            $term = get_term_by('name', 'Элитный эскорт', $taxonomy);
        }
        if ($term && !is_wp_error($term)) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }

    update_option('drygoe_elite_term_removed', 1);
}, 100);
