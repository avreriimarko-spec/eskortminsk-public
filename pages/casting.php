<?php
/*
Template Name: Кастинг
*/
defined('ABSPATH') || exit;

get_header();

$ID   = get_queried_object_id();
$meta = get_post_meta($ID);

$meta_val = static function (string $key) use ($meta) {
    return isset($meta[$key][0]) ? maybe_unserialize($meta[$key][0]) : '';
};

$h1   = trim((string) $meta_val('h1'));
if ($h1 === '') $h1 = get_the_title($ID);
$lead = (string) $meta_val('p');
$seo  = get_field('seo');
$current_page = function_exists('kz_get_current_page_number') ? kz_get_current_page_number() : 1;
$is_pagination_page = $current_page > 1;
$h1_display = $h1;
if ($is_pagination_page) {
    $h1_display = rtrim($h1_display) . ' | Страница ' . $current_page;
}
$current_page = function_exists('kz_get_current_page_number') ? kz_get_current_page_number() : 1;
$is_pagination_page = $current_page > 1;

// Токен/чат — можно вынести в wp-config.php, а здесь просто прочитать через constants.
$TG_BOT_TOKEN = defined('CAST_TG_BOT_TOKEN') ? CAST_TG_BOT_TOKEN : '8075271759:AAGyDVs-XOj4uAwUP9p3NESWxT6-ftnX3eg';
$TG_CHAT_ID   = defined('CAST_TG_CHAT_ID')   ? CAST_TG_CHAT_ID   : '7475106470';
?>
<main class="max-w-6xl mx-auto px-4 md:px-0 py-8 text-black">
    <?php
    $breadcrumbs = locate_template(['components/breadcrumbs.php'], false, false);
    if ($breadcrumbs) include $breadcrumbs;
    ?>

    <header class="text-center max-w-3xl mx-auto mb-8">
        <h1 class="text-3xl md:text-5xl font-bold text-black"
            data-base-heading="<?= esc_attr($h1); ?>"><?= esc_html($h1_display) ?></h1>
        <?php if ($lead !== ''): ?>
            <p class="mt-4 text-base md:text-lg text-zinc-600"
                data-hide-on-pagination="<?php echo $is_pagination_page ? 'true' : 'false'; ?>"
                <?php echo $is_pagination_page ? 'hidden' : ''; ?>>
                <?= wp_kses_post($lead) ?>
            </p>
        <?php endif; ?>
    </header>

    <section>
        <!-- Сообщение об успехе/ошибке -->
        <p id="cast_message" class="mb-4 text-center text-sm text-zinc-500"></p>

        <!-- ВАЖНО: id + data-* для JS -->
        <form id="castingForm"
            class="rounded-xl border border-zinc-200 bg-white p-5 md:p-6"
            enctype="multipart/form-data"
            data-tg-token="<?= esc_attr($TG_BOT_TOKEN) ?>"
            data-tg-chat="<?= esc_attr($TG_CHAT_ID) ?>">

            <!-- honeypot -->
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

            <!-- Контакты -->
            <h2 class="text-xl font-semibold mb-4 border-l-4 border-[#b50202] pl-3 text-black">Контакты</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <label class="block">
                    <span class="block text-sm text-zinc-600 mb-1">Имя*</span>
                    <input type="text" name="name" required
                        class="w-full appearance-none bg-white text-black placeholder-zinc-400
                    border border-zinc-200 rounded-md px-3 py-2
                    focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                        placeholder="Ваше имя">
                </label>
                <label class="block">
                    <span class="block text-sm text-zinc-600 mb-1">Номер телефона*</span>
                    <input type="tel" name="phone" required
                        class="w-full appearance-none bg-white text-black placeholder-zinc-400
                    border border-zinc-200 rounded-md px-3 py-2
                    focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                        placeholder="+7…">
                </label>
            </div>

            <!-- Параметры -->
            <h2 class="text-xl font-semibold mt-8 mb-4 border-l-4 border-[#b50202] pl-3 text-black">Параметры</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                <label class="block">
                    <span class="block text-sm text-zinc-600 mb-1">Возраст</span>
                    <input type="number" name="age" min="18" max="80"
                        class="w-full appearance-none bg-white text-black placeholder-zinc-400
                    border border-zinc-200 rounded-md px-3 py-2
                    focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                        placeholder="25">
                </label>
                <label class="block">
                    <span class="block text-sm text-zinc-600 mb-1">Рост (см)</span>
                    <input type="number" name="height" min="120" max="220"
                        class="w-full appearance-none bg-white text-black placeholder-zinc-400
                    border border-zinc-200 rounded-md px-3 py-2
                    focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                        placeholder="170">
                </label>
                <label class="block">
                    <span class="block text-sm text-zinc-600 mb-1">Вес (кг)</span>
                    <input type="number" name="weight" min="30" max="150"
                        class="w-full appearance-none bg-white text-black placeholder-zinc-400
                    border border-zinc-200 rounded-md px-3 py-2
                    focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                        placeholder="55">
                </label>
                <label class="block">
                    <span class="block text-sm text-zinc-600 mb-1">Размер груди</span>
                    <input type="text" name="bust"
                        class="w-full appearance-none bg-white text-black placeholder-zinc-400
                    border border-zinc-200 rounded-md px-3 py-2
                    focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                        placeholder="34C">
                </label>
            </div>

            <!-- Фото -->
            <h2 class="text-xl font-semibold mt-8 mb-4 border-l-4 border-[#b50202] pl-3 text-black">Фото</h2>
            <label class="block">
                <span class="block text-sm text-zinc-600 mb-1">
                    Загрузите до 8 фото (jpg, png, webp, до 5 МБ каждое)
                </span>
                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
                    class="block w-full text-sm text-zinc-600 bg-white border border-zinc-200 rounded-md
                  file:mr-4 file:rounded-md file:border-0 file:bg-[#b50202] file:px-4 file:py-2 file:text-white
                  hover:file:bg-[#8d0202]
                  focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none">
            </label>

            <!-- Комментарий -->
            <h2 class="text-xl font-semibold mt-8 mb-4 border-l-4 border-[#b50202] pl-3 text-black">Комментарий</h2>
            <label class="block">
                <textarea name="comment" rows="4"
                    class="w-full appearance-none bg-white text-black placeholder-zinc-400
                     border border-zinc-200 rounded-md p-3
                     focus:border-[#b50202] focus:ring-1 focus:ring-[#b50202] outline-none"
                    placeholder="Коротко о себе..."></textarea>
            </label>

            <div class="mt-8 flex items-center gap-4">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-[#b50202] px-6 py-2
                   text-white text-sm font-semibold hover:bg-[#8d0202] transition">
                    Отправить заявку
                </button>
                <p class="text-xs text-zinc-500">
                    Нажимая «Отправить заявку», вы соглашаетесь с нашей
                    <a href="/politika-konfidencialnosti/" class="text-[#b50202] hover:text-[#8d0202] underline">
                        политикой конфиденциальности
                    </a>.
                </p>
            </div>
        </form>

    </section>

    <!-- SEO-текст -->
    <?php if (!empty($seo)) : ?>
        <section class="mt-8 px-4 xl:px-0"
            data-hide-on-pagination="<?php echo $is_pagination_page ? 'true' : 'false'; ?>"
            <?php echo $is_pagination_page ? 'hidden' : ''; ?>>
            <div class="max-w-6xl mx-auto seo">
                <?= wp_kses_post($seo); ?>
            </div>
        </section>
    <?php endif; ?>
</main>


<script>
    (function() {
        const form = document.getElementById('castingForm');
        if (!form) return;

        const msgEl = document.getElementById('cast_message');
        const btn = form.querySelector('button[type="submit"]');

        const TOKEN = form.dataset.tgToken;
        const CHAT = form.dataset.tgChat;
        const API = `https://api.telegram.org/bot${TOKEN}`;

        function setMsg(text, ok) {
            msgEl.textContent = text || '';
            msgEl.className = 'mb-4 text-center text-sm ' + (ok ? 'text-green-600' : 'text-[#b50202]');
        }

        async function tg(method, payload, isForm = false) {
            const url = `${API}/${method}`;
            const opts = isForm ?
                {
                    method: 'POST',
                    body: payload
                } :
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: new URLSearchParams(payload)
                };
            const res = await fetch(url, opts);
            let data;
            try {
                data = await res.json();
            } catch (e) {
                throw new Error(`Bad JSON [${res.status}]`);
            }
            if (!data.ok) throw new Error(`${data.description || 'Telegram API error'} [${res.status}]`);
            return data;
        }

        // обрезаем caption (лимит 1024)
        function fitCaption(s) {
            const MAX = 1024;
            if (s.length <= MAX) return s;
            return s.slice(0, MAX - 1) + '…';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // honeypot
            if (form.website && form.website.value.trim() !== '') return;

            const name = (form.name?.value || '').trim();
            const phone = (form.phone?.value || '').trim();
            const age = (form.age?.value || '').trim();
            const height = (form.height?.value || '').trim();
            const weight = (form.weight?.value || '').trim();
            const bust = (form.bust?.value || '').trim();
            const comment = (form.comment?.value || '').trim();

            if (!name || !phone) {
                setMsg('Заполните имя и телефон.', false);
                return;
            }

            const lines = [
                '📩 <b>Новая заявка на кастинг</b>',
                '👤 Имя: ' + name,
                '📞 Телефон: ' + phone,
                '🎂 Возраст: ' + (age || '—'),
                '📏 Рост: ' + (height ? height + ' см' : '—'),
                '⚖️ Вес: ' + (weight ? weight + ' кг' : '—'),
                '💗 Размер груди: ' + (bust || '—'),
                '📝 Комментарий: ' + (comment || '—'),
                '🔗 Страница: ' + window.location.href,
                '🕒 Время: ' + new Date().toLocaleString()
            ];
            const fullText = lines.join('\n');
            const caption = fitCaption(fullText);

            // UI
            btn.disabled = true;
            const oldBtn = btn.textContent;
            btn.textContent = 'Отправляем…';
            setMsg('', true);

            try {
                const input = form.querySelector('input[name="photos[]"]');
                const allFiles = input?.files ? Array.from(input.files) : [];

                // только фото для альбома (jpg/png), до 8 шт, <= 5 МБ
                const albumPhotos = allFiles
                    .filter(f => /^image\/(jpe?g|png)$/i.test(f.type) && f.size <= 5 * 1024 * 1024)
                    .slice(0, 8);

                // прочие файлы (например webp) отправим документами после альбома — по желанию
                const otherFiles = allFiles.filter(f => !albumPhotos.includes(f) && f.size <= 5 * 1024 * 1024);

                if (albumPhotos.length >= 2) {
                    // альбом (sendMediaGroup), caption на первой
                    const fd = new FormData();
                    fd.append('chat_id', CHAT);

                    const media = albumPhotos.map((file, i) => {
                        const field = `photo${i}`;
                        fd.append(field, file, file.name);
                        const obj = {
                            type: 'photo',
                            media: `attach://${field}`
                        };
                        if (i === 0) {
                            obj.caption = caption;
                            obj.parse_mode = 'HTML';
                        }
                        return obj;
                    });

                    fd.append('media', JSON.stringify(media));
                    await tg('sendMediaGroup', fd, true);

                } else if (albumPhotos.length === 1) {
                    // одиночное фото с caption
                    const fd = new FormData();
                    fd.append('chat_id', CHAT);
                    fd.append('photo', albumPhotos[0], albumPhotos[0].name);
                    fd.append('caption', caption);
                    fd.append('parse_mode', 'HTML');
                    await tg('sendPhoto', fd, true);

                } else {
                    // без фото — просто текст
                    await tg('sendMessage', {
                        chat_id: CHAT,
                        text: fullText,
                        parse_mode: 'HTML',
                        disable_web_page_preview: '1'
                    });
                }

                // отправим "нестандартные" файлы как документы (при наличии)
                for (const file of otherFiles) {
                    const fd = new FormData();
                    fd.append('chat_id', CHAT);
                    fd.append('document', file, file.name);
                    try {
                        await tg('sendDocument', fd, true);
                    } catch (e) {
                        console.warn('sendDocument error:', e.message);
                    }
                }

                // если caption урезался — дублируем полный текст отдельным сообщением
                if (caption !== fullText && albumPhotos.length) {
                    await tg('sendMessage', {
                        chat_id: CHAT,
                        text: fullText,
                        parse_mode: 'HTML',
                        disable_web_page_preview: '1'
                    });
                }

                setMsg('Спасибо! Ваша заявка отправлена. Мы свяжемся с вами.', true);
                form.reset();
            } catch (err) {
                console.error(err);
                const m = String(err.message || '');
                if (m.includes('chat not found') || m.includes('bot was blocked')) {
                    setMsg('Бот не может написать в этот чат. Откройте диалог с ботом и нажмите Start.', false);
                } else if (m.includes('Unauthorized')) {
                    setMsg('Неверный токен бота. Проверьте TOKEN.', false);
                } else if (m.includes('Failed to fetch') || m.includes('TypeError')) {
                    setMsg('Запрос заблокирован (CORS/сеть). Нужен серверный прокси.', false);
                } else {
                    setMsg('Ошибка: ' + m, false);
                }
            } finally {
                btn.disabled = false;
                btn.textContent = oldBtn;
            }
        });
    })();
</script>



<?php get_footer(); ?>
