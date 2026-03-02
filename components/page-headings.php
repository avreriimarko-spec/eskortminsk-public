<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kz_get_page_headings')) {
    /**
     * Возвращает массив заголовков для текущей страницы.
     */
    function kz_get_page_headings($post = null) {
        if (!$post instanceof WP_Post) {
            $post = get_queried_object();
        }

        $post_id = $post instanceof WP_Post ? $post->ID : get_queried_object_id();

        $default_h1 = $post_id && function_exists('get_field') ? (string) get_field('h1', $post_id) : '';
        if ($default_h1 === '' && $post instanceof WP_Post) {
            $default_h1 = get_the_title($post_id);
        }
        $default_h2 = $post_id && function_exists('get_field') ? (string) get_field('h2_models', $post_id) : '';

        $headings = [
            'h1' => $default_h1,
            'h2' => $default_h2,
        ];

        if (!$post instanceof WP_Post) {
            return $headings;
        }

        $name = trim(get_the_title($post_id));
        switch ($post->post_type) {
            case 'rayonu':
                $headings['h1'] = sprintf('Проститутки район %s', $name);
                $headings['h2'] = sprintf('Анкеты района %s', $name);
                break;

            case 'uslugi':
                $headings['h1'] = sprintf('%s в Минске', $name);
                $headings['h2'] = sprintf('Анкеты с услугой %s', $name);
                break;
        }

        $page_number = function_exists('kz_get_current_page_number') ? kz_get_current_page_number() : 1;
        $headings['base_h1'] = $headings['h1'];
        if ($page_number > 1 && $headings['h1']) {
            $headings['h1'] = rtrim($headings['h1']) . ' | Страница ' . $page_number;
        }

        return $headings;
    }
}
