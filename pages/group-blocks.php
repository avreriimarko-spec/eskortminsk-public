    <!-- Блоки секций из шаблона главной страницы после каталога моделей -->
    <?php
    // Получаем все секции
    $sections = get_field('sections');

    ?>
    <?php foreach ($sections as $row):
        // каждый $row — массив с ключом section_fields
        $sec   = $row['section_fields'];
        $title = $sec['section_title']    ?? '';
        $subt  = $sec['section_subtitle'] ?? '';
        $cards = $sec['section_cards']    ?? [];
        if (empty($cards)) {
            continue;
        }
    ?>
        <section class="py-12 text-[#F6CD7E]">
            <div class="container mx-auto px-4">
                <?php if ($title): ?>
                    <h2 class="text-3xl font-bold mb-6"><?= esc_html($title) ?></h2>
                <?php endif; ?>
                <?php if ($subt): ?>
                    <p class="mb-8 text-white"><?= esc_html($subt) ?></p>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($cards as $card): ?>
                        <div class="bg-[#3b3b3b] px-4 py-8 rounded-sm">
                            <?php if (!empty($card['card_title'])): ?>
                                <h3 class="text-xl font-semibold mb-2"><?= esc_html($card['card_title']) ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['card_text'])): ?>
                                <p class="text-sm text-white"><?= esc_html($card['card_text']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>


    <?php
    // Проверяем, есть ли наша группа
    if (have_rows('section_content')):
        the_row(); // «входим» в group

        $title = get_sub_field('section_title');
        $text  = get_sub_field('section_text');
    ?>
        <section class="py-12 text-[#F6CD7E]">
            <div class="container mx-auto px-4">
                <?php if ($title): ?>
                    <h2 class="text-3xl font-bold mb-4 text-[#F6CD7E]"><?= esc_html($title) ?></h2>
                <?php endif; ?>
                <?php if ($text): ?>
                    <p class="mb-8 text-white"><?= esc_html($text) ?></p>
                <?php endif; ?>

                <?php if (have_rows('section_items')): ?>
                    <div class="space-y-8">
                        <?php while (have_rows('section_items')): the_row(); ?>
                            <div class="flex items-start">
                                <span class="w-8 h-8 flex items-center justify-center rounded-full border border-[#F6CD7E] text-lg font-semibold mr-4">
                                    <?= get_row_index() ?>
                                </span>
                                <div>
                                    <?php if ($st = get_sub_field('item_title')): ?>
                                        <h3 class="text-xl font-semibold mb-1"><?= esc_html($st) ?></h3>
                                    <?php endif; ?>
                                    <?php if ($sd = get_sub_field('item_text')): ?>
                                        <p class="text-sm text-white"><?= esc_html($sd) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php
    endif;
    ?>