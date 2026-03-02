<?php
/**
 * Компонент: Плавающий док (ПК) + Нижняя панель (Мобайл)
 * Единый стиль кнопок (Иконка + Текст) и анимации стрелки.
 */

// 1. Настройки и переменные
$tg = trim((string) get_theme_mod('contact_telegram'));
$wa = trim((string) get_theme_mod('contact_whatsapp'));
$tg_user  = ltrim($tg, '@');

// Для попапа
$tg_channel  = trim((string) get_theme_mod('contact_telegram_channel'));
$tg_channel_href = $tg_channel ? ('https://t.me/' . $tg_channel) : '';
$has_ch = !empty($tg_channel_href);

// --- ИЗМЕНЕНИЕ ЗДЕСЬ ---
// Добавили is_archive() и is_search(), чтобы кнопка была в каталоге, поиске и категориях
$show_filter = is_front_page() || is_home() || is_page_template('pages/home.php') || is_archive() || is_search() || is_page(); 
// -----------------------

// Проверка на страницу избранного (для подсветки кнопки)
$is_fav = (strpos($_SERVER['REQUEST_URI'] ?? '', 'favorites') !== false);
$favTag = $is_fav ? 'div' : 'a';
$favAttr = $is_fav ? '' : 'href="/favorites/"';
?>


<div id="pcFloatingDock" 
     class="hidden md:flex fixed z-[80] right-8 bottom-8 
            bg-white/95 backdrop-blur-md border border-zinc-200 shadow-2xl rounded-2xl overflow-hidden
            items-stretch transition-all duration-300">

   
    <button id="scrollTopPc" 
            onclick="window.scrollTo({top: 0, behavior: 'smooth'});"
            class="group flex flex-col items-center justify-center gap-1 w-0 overflow-hidden opacity-0 transition-all duration-300 ease-in-out cursor-pointer hover:bg-zinc-50 border-r border-zinc-200/70">
        <span class="text-zinc-600 group-hover:text-black transition-colors mt-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
        </span>
        <span class="text-[10px] font-medium text-zinc-600 group-hover:text-black mb-2">Наверх</span>
    </button>

    
    <<?= $favTag ?> <?= $favAttr ?>
       class="group flex flex-col items-center justify-center w-20 py-2 gap-1 hover:bg-zinc-50 transition-colors cursor-pointer border-r border-zinc-200/70">
        <div class="<?php echo $is_fav ? 'text-[#b50202]' : 'text-zinc-600'; ?> group-hover:text-[#b50202] transition-colors">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </div>
        <span class="text-[10px] font-medium <?php echo $is_fav ? 'text-black' : 'text-zinc-600 group-hover:text-black'; ?>">Избранное</span>
    </<?= $favTag ?>>

    
    <div data-go="wa"
       class="group flex flex-col items-center justify-center w-20 py-2 gap-1 hover:bg-zinc-50 transition-colors cursor-pointer">
        <div class="text-[#25D366] group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
        </div>
        <span class="text-[10px] font-medium text-zinc-600 group-hover:text-black">WhatsApp</span>
    </div>

    
    <?php if ($tg_user): ?>
        <div data-go="tg"
           class="group flex flex-col items-center justify-center w-20 py-2 gap-1 hover:bg-zinc-50 transition-colors cursor-pointer border-l border-zinc-200/70">
            <div class="text-[#229ED9] group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
            </div>
            <span class="text-[10px] font-medium text-zinc-600 group-hover:text-black">Telegram</span>
        </div>
    <?php endif; ?>

</div>


<div id="ekPromoPopup" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="ekPromoTitle">
    <button id="ekPromoBackdrop" class="absolute inset-0 bg-black/60" aria-label="Закрыть"></button>
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[92vw] max-w-sm rounded-2xl bg-white text-black shadow-[0_20px_60px_-10px_rgba(0,0,0,.2)] overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3">
            <p id="ekPromoTitle" class="text-base text-center md:text-lg font-semibold">Телеграмм канал</p>
            <button id="ekPromoClose" class="px-2 py-1 text-zinc-500 hover:text-black" aria-label="Закрыть">×</button>
        </div>
        <div class="h-[2px] bg-[#b50202] mx-4"></div>
        <div class="px-4 py-4 space-y-3">
            <p class="text-sm text-zinc-600 text-center mb-2">Ежедневное добавление анкет актуальных девочек</p>
            <?php if ($has_ch): ?>
                <div data-go="tg" class="block w-full text-center rounded-lg px-5 py-2.5 font-semibold bg-[#b50202] text-white hover:bg-[#8d0202] cursor-pointer">Перейти на канал</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function() {
    const PAGE_VIEWS_THRESHOLD = 15; const MINUTES_INTERVAL = 5; const SNOOZE_MINUTES = 2; const FORCE_SHOW = /[?&]ekpopup=1\b/.test(location.search);
    const K = { startTs: 'ek_pp_start_ts', views: 'ek_pp_page_views', snoozeTs: 'ek_pp_snooze_ts', shownTs: 'ek_pp_last_shown' };
    const $popup = document.getElementById('ekPromoPopup'); const $backdrop = document.getElementById('ekPromoBackdrop'); const $close = document.getElementById('ekPromoClose');
    if (!$popup) return;
    const now = () => Date.now(); const minutes = (n) => n * 60 * 1000;
    const ssGet = (k, d = null) => { try { return sessionStorage.getItem(k) ?? d; } catch (e) { return d; } }
    const ssSet = (k, v) => { try { sessionStorage.setItem(k, String(v)); } catch (e) {} }
    if (!ssGet(K.startTs)) ssSet(K.startTs, String(now()));
    const cur = parseInt(ssGet(K.views, '0'), 10) || 0; ssSet(K.views, String(cur + 1));
    function show() { const snoozeUntil = parseInt(ssGet(K.snoozeTs, '0'), 10) || 0; if (!FORCE_SHOW && now() < snoozeUntil) return; $popup.classList.remove('hidden'); ssSet(K.shownTs, String(now())); document.documentElement.style.overflow = 'hidden'; }
    function hide() { $popup.classList.add('hidden'); ssSet(K.snoozeTs, String(now() + minutes(SNOOZE_MINUTES))); document.documentElement.style.overflow = ''; }
    $backdrop && $backdrop.addEventListener('click', hide); $close && $close.addEventListener('click', hide);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !$popup.classList.contains('hidden')) hide(); });
    function shouldShow() { if (FORCE_SHOW) return true; const start = parseInt(ssGet(K.startTs, String(now())), 10) || now(); const views = parseInt(ssGet(K.views, '0'), 10) || 0; const snoozeUntil = parseInt(ssGet(K.snoozeTs, '0'), 10) || 0; if (now() < snoozeUntil) return false; return ((now() - start) >= minutes(MINUTES_INTERVAL)) || (views >= PAGE_VIEWS_THRESHOLD); }
    if (shouldShow()) { show(); } else { const i = setInterval(() => { if (shouldShow()) { clearInterval(i); show(); } }, 10000); }
})();
</script>

<script>
document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-go]');
    if (target) {
        const type = target.getAttribute('data-go');
        const url = '/' + ['g', 'o'].join('') + '/' + type;
        window.open(url, '_blank', 'noopener,noreferrer');
    }
});
</script>


<?php if ( wp_is_mobile() ) : ?>
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-[90] bg-white border-t border-zinc-200 pb-[env(safe-area-inset-bottom)]">
        
        <div class="flex items-center h-[60px] w-full">
            
          
            <button id="mobileScrollTopBtn" 
                    onclick="window.scrollTo({top: 0, behavior: 'smooth'});"
                    class="group flex flex-col items-center justify-center gap-1 h-full w-0 overflow-hidden opacity-0 transition-all duration-300 ease-in-out cursor-pointer hover:bg-zinc-50 border-r border-zinc-200/70">
                <div class="text-zinc-600 group-active:text-black transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                </div>
                <span class="text-[10px] font-medium text-zinc-600 group-active:text-black whitespace-nowrap">Наверх</span>
            </button>

          
            <?php if ($show_filter): ?>
            <button id="mobileNavFilterBtn" class="flex-1 flex flex-col items-center justify-center gap-1 group h-full min-w-0">
                <div class="text-zinc-600 group-active:text-[#b50202] transition-colors">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                </div>
                <span class="text-[10px] font-medium text-zinc-600 group-active:text-black whitespace-nowrap">Фильтр</span>
            </button>
            <?php endif; ?>

           
            <<?= $favTag ?> <?= $favAttr ?> class="flex-1 flex flex-col items-center justify-center gap-1 group h-full min-w-0 cursor-pointer">
                <div class="<?php echo $is_fav ? 'text-[#b50202]' : 'text-zinc-600'; ?> group-active:text-[#b50202] transition-colors">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <span class="text-[10px] font-medium <?php echo $is_fav ? 'text-black' : 'text-zinc-600'; ?> whitespace-nowrap">Избранное</span>
            </<?= $favTag ?>>

           
            <div data-go="wa" class="flex-1 flex flex-col items-center justify-center gap-1 group h-full min-w-0">
                <div class="text-[#25D366] hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-medium text-zinc-600 whitespace-nowrap">WhatsApp</span>
            </div>

          
            <?php if ($tg_user): ?>
            <div data-go="tg" class="flex-1 flex flex-col items-center justify-center gap-1 group h-full min-w-0">
                <div class="text-[#229ED9] hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-medium text-zinc-600 whitespace-nowrap">Telegram</span>
            </div>
            <?php endif; ?>

        </div>
    </div>

   
    <div id="mobileFilterOverlay" class="fixed inset-0 z-[100] bg-black/80 hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm"></div>
    <div id="mobileFilterSheet" class="fixed bottom-0 left-0 right-0 z-[101] bg-white rounded-t-2xl transform translate-y-full transition-transform duration-300 flex flex-col max-h-[90vh] shadow-[0_-5px_20px_rgba(0,0,0,0.15)] border-t border-zinc-200">
        
        <div class="relative flex items-center justify-between px-4 py-3 border-b border-zinc-200 shrink-0 bg-white rounded-t-2xl">
            <button id="backFilterBtn" class="p-2 -ml-2 text-zinc-600 hover:text-black transition-colors" aria-label="Вернуть назад">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <h3 class="text-black font-semibold text-lg absolute left-1/2 -translate-x-1/2">Фильтр</h3>
            <button id="closeFilterBtn" class="p-2 -mr-2 text-zinc-500 hover:text-black transition-colors" aria-label="Закрыть">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div id="mobileFilterContent" class="overflow-y-auto p-4 pb-20 bg-white"></div>
    </div>
    
    <style>body { padding-bottom: 70px; } #mobileFilterContent::-webkit-scrollbar { width: 4px; } #mobileFilterContent::-webkit-scrollbar-track { background: #f3f4f6; } #mobileFilterContent::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtn = document.getElementById('mobileNavFilterBtn'); 
        const closeBtn = document.getElementById('closeFilterBtn');
        const backBtn = document.getElementById('backFilterBtn');
        const overlay = document.getElementById('mobileFilterOverlay'); 
        const sheet = document.getElementById('mobileFilterSheet');
        const sheetContent = document.getElementById('mobileFilterContent');
        
        if (filterBtn) {
            let originalFilter = document.querySelector('.pc-filter-wrapper'); 
            let placeholder = document.createComment("Filter Placeholder"); 
            let isFilterMoved = false;

            function openSheet() {
                if (!originalFilter) { originalFilter = document.querySelector('.pc-filter-wrapper'); if(!originalFilter) return; }
                if (!isFilterMoved) { originalFilter.parentNode.insertBefore(placeholder, originalFilter); sheetContent.appendChild(originalFilter); isFilterMoved = true; }
                overlay.classList.remove('hidden'); setTimeout(() => overlay.classList.remove('opacity-0'), 10);
                sheet.classList.remove('translate-y-full'); document.body.classList.add('overflow-hidden');
            }

            function closeSheet() {
                overlay.classList.add('opacity-0'); sheet.classList.add('translate-y-full');
                setTimeout(() => { overlay.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }, 300);
            }

            filterBtn.addEventListener('click', openSheet); 
            if (closeBtn) closeBtn.addEventListener('click', closeSheet); 
            if (backBtn) backBtn.addEventListener('click', closeSheet);
            if (overlay) overlay.addEventListener('click', closeSheet);
        }
    });
    </script>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const pcBtn = document.getElementById('scrollTopPc');
    const mobileBtn = document.getElementById('mobileScrollTopBtn');
    const SCROLL_THRESHOLD = 300; 

    function toggleScrollTop() {
        const scrolled = window.scrollY > SCROLL_THRESHOLD;
        const classesToShow = ['w-20', 'opacity-100']; // Для ПК (ширина 20 tailwind = 80px)
        const classesToHide = ['w-0', 'opacity-0'];

        // 1. Для ПК
        if (pcBtn) {
            if (scrolled) {
                pcBtn.classList.remove(...classesToHide);
                pcBtn.classList.add(...classesToShow);
            } else {
                pcBtn.classList.remove(...classesToShow);
                pcBtn.classList.add(...classesToHide);
            }
        }

        // 2. Для Мобайл (там flex-1 для резины)
        if (mobileBtn) {
            if (scrolled) {
                mobileBtn.classList.remove('w-0', 'opacity-0');
                mobileBtn.classList.add('flex-1', 'w-auto', 'opacity-100');
            } else {
                mobileBtn.classList.remove('flex-1', 'w-auto', 'opacity-100');
                mobileBtn.classList.add('w-0', 'opacity-0');
            }
        }
    }

    window.addEventListener('scroll', toggleScrollTop, { passive: true });
    toggleScrollTop();
});
</script>
