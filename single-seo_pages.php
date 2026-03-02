<?php
/*
Template Name: Сетка анкет (SEO)
Template Post Type: seo_pages
*/
defined('ABSPATH') || exit;

$template = locate_template('tpl-seo-models-grid.php', false, false);
if ($template) {
    require $template;
    return;
}

get_header();
?>
<main class="max-w-6xl mx-auto px-4 md:px-0 py-10 text-black">
    <h1 class="text-2xl md:text-4xl font-semibold mb-4"><?php the_title(); ?></h1>
    <div class="prose max-w-none">
        <?php the_content(); ?>
    </div>
</main>
<?php get_footer(); ?>
