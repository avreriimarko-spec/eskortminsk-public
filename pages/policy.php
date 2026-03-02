<!-- 
Template Name: Политика/Условия
-->

<?php get_header();
$h1   = get_field('h1');
$html = get_field('html');
?>

<main class="max-w-6xl mx-auto px-4 md:px-0 py-8 bg-white text-black">
    <!-- Заголовок -->
    <h1 class="text-3xl md:text-4xl font-bold text-center mb-6">
        <?= esc_html($h1); ?>
    </h1>

    <!-- Контентная секция -->
    <section class="border border-[#b50202] rounded-lg p-6 md:p-8 bg-white">
        <div class="prose max-w-none seo
                prose-headings:text-black
                prose-p:text-black
                prose-strong:text-black
                prose-li:text-black
                prose-a:text-[#b50202] hover:prose-a:text-[#8d0202]">
            <?= wp_kses_post($html); ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
