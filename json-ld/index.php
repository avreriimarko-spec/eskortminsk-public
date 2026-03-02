<?php
// index.php — главный диспетчер разметки

// 1. Общие компоненты (всегда в начале)
get_template_part('json-ld/webpage');
get_template_part('json-ld/breadcrumb');

// 2. Организация — только на главной
if (is_front_page()) {
    get_template_part('json-ld/organization');
}

// 3. Список анкет (ItemList) — для главной и всех архивов/категорий
if (is_front_page() || (!is_singular() && (is_post_type_archive('models') || is_tax()))) {
    get_template_part('json-ld/person-list');
}

// 4. Одиночная карточка модели
if (is_singular('models')) {
    get_template_part('json-ld/person');
}