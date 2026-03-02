<?php
defined('ABSPATH') || exit;

$query = $query ?? ($wp_query ?? null);
if (!($query instanceof WP_Query)) {
    return;
}

$card_template = $card_template ?? 'model_card_archive.php';
$grid_classes = $grid_classes ?? 'models-grid-list grid grid-cols-1 md:grid-cols-2 gap-6';
$grid_base_filters = $grid_base_filters ?? [];
$grid_sort_by = $grid_sort_by ?? 'date';
$grid_sort_order = $grid_sort_order ?? 'DESC';
$grid_context_tax = $grid_context_tax ?? '';
$grid_context_term = $grid_context_term ?? '';
$grid_view_context = $grid_view_context ?? (is_front_page() ? 'home' : 'archive');

$paged = (int) max(1, $query->get('paged') ?: 1);
$per_page = (int) ($query->get('posts_per_page') ?: 0);
if ($per_page <= 0) {
    $per_page = 24;
}

$max_pages = (int) $query->max_num_pages;
$ld_ids = [];

$grid_data_attrs = [
    'data-current-page' => $paged,
    'data-total-pages' => $max_pages,
    'data-per-page' => $per_page,
    'data-base-filters' => wp_json_encode($grid_base_filters, JSON_UNESCAPED_UNICODE) ?: '{}',
    'data-sort-by' => $grid_sort_by,
    'data-sort-order' => $grid_sort_order,
    'data-view-context' => $grid_view_context,
];

if ($grid_context_tax && $grid_context_term) {
    $grid_data_attrs['data-context-tax'] = $grid_context_tax;
    $grid_data_attrs['data-context-term'] = $grid_context_term;
}

$grid_attr_string = '';
foreach ($grid_data_attrs as $a => $v) {
    $grid_attr_string .= sprintf(' %s="%s"', esc_attr($a), esc_attr($v));
}

$initial_pagination_html = my_theme_get_pagination_html($paged, $max_pages);
$pagination_classes = 'mt-10 flex justify-center' . (empty($initial_pagination_html) ? ' hidden' : '');
?>

<div id="modelsGrid"<?php echo $grid_attr_string; ?>>
    <ul class="<?php echo esc_attr($grid_classes); ?>">
        <?php if ($query->have_posts()):
            $card_path = get_stylesheet_directory() . '/components/' . $card_template;
            if (!file_exists($card_path)) {
                $card_path = get_stylesheet_directory() . '/components/model_card_archive.php';
            }
            $i = 0;
            while ($query->have_posts()): $query->the_post();
                $ID = get_the_ID();
                $ld_ids[] = $ID;
                $card_variant = 'light';
                $card_hover_zoom = true;
                $index = $i;
                echo '<li>';
                if (file_exists($card_path)) {
                    include $card_path;
                }
                echo '</li>';
                $i++;
            endwhile;
            wp_reset_postdata();
        else: ?>
            <li class="col-span-full text-center text-sm text-zinc-500 py-10">По вашему запросу моделей не найдено.</li>
        <?php endif; ?>
    </ul>

    <div class="<?php echo esc_attr($pagination_classes); ?>" id="modelsPagination">
        <?php echo $initial_pagination_html; ?>
    </div>
</div>

<?php
if (!empty($ld_ids)) {
    $pos = 1 + (($paged - 1) * $per_page);
    $itemList = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => []];
    foreach ($ld_ids as $mid) {
        $itemList['itemListElement'][] = [
            '@type' => 'ListItem', 'position' => $pos++,
            'item' => ['@type' => 'Person', 'name' => get_the_title($mid), 'url' => get_permalink($mid)]
        ];
    }
    echo '<script type="application/ld+json">' . wp_json_encode($itemList) . '</script>';
}
?>
