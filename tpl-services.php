<?php
/*
Template Name: Услуга (Вывод моделей)
*/

get_header(); 

// 1. Получаем слаг текущей страницы (например, 'minet-bez-prezervativa')
$current_slug = $post->post_name;

// 2. Ищем термин таксономии 'uslugi_tax', который совпадает со слагом страницы
$term = get_term_by('slug', $current_slug, 'uslugi_tax');

// Подготовка аргументов для запроса
$args = [
    'post_type'      => 'models',
    'posts_per_page' => 20, // Сколько анкет выводить
    'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
    'tax_query'      => [
        'relation' => 'AND',
    ]
];

// Если такой термин есть в базе, фильтруем по нему
if ($term) {
    $args['tax_query'][] = [
        'taxonomy' => 'uslugi_tax',
        'field'    => 'slug',
        'terms'    => $current_slug,
    ];
} else {
    // Если термина нет, можно вывести все модели или ничего (на ваше усмотрение)
    // Сейчас выведет просто всех моделей, если совпадения нет.
}

$query = new WP_Query($args);
?>

<main class="container mx-auto px-4 py-8">
    
    <h1 class="text-3xl font-bold mb-4"><?php the_title(); ?></h1>
    
    <div class="prose max-w-none mb-8">
        <?php the_content(); ?>
    </div>

    <div class="filters-block mb-8">
       </div>

    <?php if ($query->have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                
                <div class="model-card border p-4 rounded">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium', ['class' => 'w-full h-64 object-cover mb-4']); ?>
                        <h3 class="text-xl font-bold"><?php the_title(); ?></h3>
                        </a>
                </div>

            <?php endwhile; ?>
        </div>

        <div class="pagination mt-8">
            <?php 
            echo paginate_links([
                'total' => $query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
            ]); 
            ?>
        </div>

        <?php wp_reset_postdata(); ?>

    <?php else : ?>
        <p>Анкеты по данному запросу не найдены.</p>
    <?php endif; ?>

</main>

<?php get_footer(); ?>