<?php
/*
Template Name: Favorites
*/
defined('ABSPATH') || exit;
get_header();

$ajax_url = admin_url('admin-ajax.php');
$card_path = locate_template('components/model_card_archive.php', false, false);
?>
<main class="max-w-6xl mx-auto px-4 md:px-0 py-8 text-black">
    <header class="mb-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold">Избранные модели</h1>
    </header>

    <div class="mb-6 flex items-center justify-center gap-4">
        <button id="favCopyLink" class="text-sm text-zinc-600 hover:text-black underline decoration-dotted">
            Скопировать ссылку на избранное
        </button>
        <button id="favClear" class="text-sm text-zinc-600 hover:text-[#b50202] underline decoration-dotted">
            Очистить избранное
        </button>
    </div>

    <section aria-label="Избранные модели"
        id="favoritesSection"
        data-ajax="<?php echo esc_url($ajax_url); ?>"
        data-nonce="<?php echo esc_attr(wp_create_nonce('fav_cards')); ?>">
        <ul id="favoritesGrid" class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mt-4 list-none p-0"></ul>
        <p id="favoritesEmpty" class="text-center text-zinc-500 mt-8">Пока пусто.</p>
        <div id="favoritesLoading" class="text-center text-zinc-500 mt-6 hidden">Загружаем…</div>
    </section>
</main>

<script>
    (function() {
        const KEY = 'ek_fav_models';
        const $ = s => document.querySelector(s);

        const section = $('#favoritesSection');
        const grid = $('#favoritesGrid');
        const empty = $('#favoritesEmpty');
        const loading = $('#favoritesLoading');

        function readFavs() {
            try {
                return JSON.parse(localStorage.getItem(KEY) || '[]').map(Number).filter(Boolean);
            } catch (e) {
                return [];
            }
        }

        function writeFavs(list) {
            localStorage.setItem(KEY, JSON.stringify(Array.from(new Set(list.map(Number))).filter(Boolean)));
        }

        function removeFromFav(id) {
            const list = readFavs();
            const i = list.indexOf(id);
            if (i > -1) list.splice(i, 1);
            writeFavs(list);
        }

        function updateEmpty() {
            const hasCards = !!grid.children.length;
            empty.style.display = hasCards ? 'none' : '';
        }

        async function loadCards(ids) {
            if (!ids.length) {
                grid.innerHTML = '';
                updateEmpty();
                return;
            }
            loading.classList.remove('hidden');
            try {
                const res = await fetch(section.dataset.ajax, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        action: 'fav_cards',
                        nonce: section.dataset.nonce,
                        ids: ids.join(',')
                    })
                });
                const html = await res.text();
                grid.innerHTML = html;
                // привяжем хэндлеры на сердечки внутри карточек, если они есть
                grid.querySelectorAll('.js-fav[data-model-id]').forEach(btn => {
                    const id = parseInt(btn.dataset.modelId, 10);
                    btn.addEventListener('click', () => {
                        // если после клика элемент удалён из избранного — убираем карточку
                        setTimeout(() => {
                            if (!readFavs().includes(id)) {
                                const li = grid.querySelector(`li[data-id="${id}"]`);
                                if (li) li.remove();
                                updateEmpty();
                            }
                        }, 0);
                    });
                });
            } catch (e) {
                grid.innerHTML = '<li class="col-span-full text-center text-zinc-400">Не удалось загрузить избранное.</li>';
            } finally {
                loading.classList.add('hidden');
                updateEmpty();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // первичная загрузка
            loadCards(readFavs());

            // «Очистить»
            const clear = $('#favClear');
            if (clear) clear.addEventListener('click', () => {
                writeFavs([]);
                loadCards([]);
            });

            // «Скопировать ссылку» — копируем ЧИСТЫЙ URL страницы, без параметров
            const copy = $('#favCopyLink');
            if (copy) copy.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(location.origin + location.pathname);
                    copy.textContent = 'Ссылка скопирована';
                    setTimeout(() => copy.textContent = 'Скопировать ссылку на избранное', 2000);
                } catch (e) {}
            });

            // синхронизация между вкладками
            window.addEventListener('storage', (e) => {
                if (e.key !== KEY) return;
                loadCards(readFavs());
            });

            // Удаление по клику на крест/сердце из уже отрисованных карточек (делегирование)
            grid.addEventListener('click', (ev) => {
                const btn = ev.target.closest('.js-fav[data-model-id]');
                if (!btn) return;
                const id = parseInt(btn.dataset.modelId, 10);
                // если сняли избранное — карточку удалим
                setTimeout(() => {
                    if (!readFavs().includes(id)) {
                        removeFromFav(id);
                        const li = grid.querySelector(`li[data-id="${id}"]`);
                        if (li) li.remove();
                        updateEmpty();
                    }
                }, 0);
            });
        });
    })();
</script>

<?php get_footer(); ?>
