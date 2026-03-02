<?php
get_header(); ?>

<main>
    <?php
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            if (is_attachment()) {
                get_template_part('pages/attachment');
            } else {
                if (!defined('KZ_HOME_PARTIAL')) {
                    define('KZ_HOME_PARTIAL', true);
                }
                get_template_part('pages/home');
            }
        }
    } else {
        get_template_part('pages/404');
    }
    ?>
</main>

<?php get_footer(); ?>
