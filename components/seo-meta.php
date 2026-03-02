<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kz_trim_meta_excerpt')) {
    function kz_trim_meta_excerpt($text, $length = 170) {
        if ($text === null) {
            return '';
        }

        $clean = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $text)));
        return wp_html_excerpt($clean, $length, '');
    }
}

if (!function_exists('kz_get_taxonomy_for_post_type')) {
    function kz_get_taxonomy_for_post_type($post_type) {
        $map = [
            'rayonu' => 'rayony',
            'uslugi' => 'uslugi_tax',
        ];

        return $map[$post_type] ?? null;
    }
}

if (!function_exists('kz_get_models_count_for_post')) {
    function kz_get_models_count_for_post($post) {
        if (!$post instanceof WP_Post) {
            return 0;
        }

        $taxonomy = kz_get_taxonomy_for_post_type($post->post_type);
        if (!$taxonomy) {
            return 0;
        }

        $term = get_term_by('slug', $post->post_name, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return 0;
        }

        return (int) $term->count;
    }
}

if (!function_exists('kz_get_default_meta_title')) {
    function kz_get_default_meta_title($post_id) {
        $meta_title = (string) get_post_meta($post_id, 'title', true);
        if ($meta_title !== '') {
            return $meta_title;
        }

        return get_the_title($post_id);
    }
}

if (!function_exists('kz_get_default_meta_descr')) {
    function kz_get_default_meta_descr($post_id) {
        $meta_descr = (string) get_post_meta($post_id, 'descr', true);
        if ($meta_descr !== '') {
            return $meta_descr;
        }

        $content = (string) get_post_field('post_content', $post_id);
        return kz_trim_meta_excerpt($content);
    }
}

if (!function_exists('kz_build_model_title')) {
    function kz_build_model_title($post_id) {
        $name = trim((string) get_post_meta($post_id, 'name', true));
        if ($name === '') {
            $name = get_the_title($post_id);
        }

        $age    = (int) get_post_meta($post_id, 'age', true);
        $height = (int) get_post_meta($post_id, 'height', true);

        $age_str = '';
        if ($age > 0) {
            $n10  = $age % 10;
            $n100 = $age % 100;
            if ($n10 === 1 && $n100 !== 11) {
                $age_str = $age . ' год';
            } elseif (in_array($n10, [2, 3, 4], true) && !in_array($n100, [12, 13, 14], true)) {
                $age_str = $age . ' года';
            } else {
                $age_str = $age . ' лет';
            }
        }

        $title_parts   = [];
        $title_parts[] = sprintf('Эскортница %s в Минске', $name);
        $facts         = [];
        if ($age_str) {
            $facts[] = 'Возраст — ' . $age_str;
        }
        if ($height > 0) {
            $facts[] = 'Рост — ' . $height . ' см';
        }
        if (!empty($facts)) {
            $title_parts[] = implode(', ', $facts);
        }
        $title_parts[] = 'Анонимно 24/7';

        return implode(' | ', $title_parts);
    }
}

if (!function_exists('kz_build_model_description')) {
    function kz_build_model_description($post_id) {
        $text = (string) get_post_meta($post_id, 'text', true);
        if ($text === '') {
            $text = (string) get_post_field('post_content', $post_id);
        }

        return kz_trim_meta_excerpt($text);
    }
}

if (!function_exists('kz_generate_meta_data')) {
    function kz_generate_meta_data($post_id = 0) {
        $site_name = get_bloginfo('name') ?: 'Site';
        $post_id   = $post_id ?: get_queried_object_id();

        $title = '';
        $descr = '';

        if ($post_id) {
            $post = get_post($post_id);
            if ($post instanceof WP_Post) {
                switch ($post->post_type) {
                    case 'models':
                        $title = kz_build_model_title($post_id);
                        $descr = kz_build_model_description($post_id);
                        break;

                    case 'rayonu':
                        $name  = trim(get_the_title($post_id));
                        $count = kz_get_models_count_for_post($post);
                        $title = sprintf(
                            'Проститутки район %s | %d - свобоных анкет | Закажи конфиденциально 24/7',
                            $name,
                            $count
                        );
                        $descr = kz_get_default_meta_descr($post_id);
                        break;

                    case 'uslugi':
                        $name  = trim(get_the_title($post_id));
                        $count = kz_get_models_count_for_post($post);
                        $title = sprintf(
                            'Заказать проститутку в Минске с услугой %s. %d - доступных анкет. Конфиденциально 24/7!',
                            $name,
                            $count
                        );
                        $text_after_h1 = function_exists('get_field') ? (string) get_field('p', $post_id) : '';
                        $descr         = kz_trim_meta_excerpt($text_after_h1 !== '' ? $text_after_h1 : kz_get_default_meta_descr($post_id));
                        break;

                    default:
                        $title = kz_get_default_meta_title($post_id);
                        $descr = kz_get_default_meta_descr($post_id);
                        break;
                }
            }
        }

        if ($title === '') {
            $title = $post_id ? (get_the_title($post_id) ?: $site_name) : $site_name;
        }
        if ($descr === '') {
            $descr = get_bloginfo('description') ?: '';
        }

        return [
            'title'       => $title,
            'description' => $descr,
        ];
    }
}
