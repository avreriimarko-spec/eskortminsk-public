<!-- 
Template Name: 404
-->

<?php
status_header(404);
nocache_headers();
get_header();
?>

<main class="min-h-screen bg-white text-black flex items-center justify-center">
    <div class="text-center px-4">
        <!-- Большой код 404 с мягким красным свечением -->
        <div class="relative inline-block">
            <span class="pointer-events-none absolute -inset-8 rounded-full bg-[#b50202]/15 blur-3xl"></span>
            <span class="relative block text-[120px] md:text-[160px] leading-none font-black text-[#b50202] tracking-tight select-none">
                404
            </span>
        </div>

        <!-- Надписи -->
        <h1 class="mt-4 text-3xl md:text-4xl font-bold text-black">
            Страница не найдена
        </h1>
        <p class="mt-3 text-zinc-600">
            Такой страницы нет или она была перемещена.
        </p>

        <a
            href="<?= esc_url(home_url('/')); ?>"
            rel="home"
            class="group inline-flex items-center justify-center gap-2 mt-6 px-6 py-3 rounded-md
                   border border-[#b50202] text-[#b50202] font-semibold
                   hover:bg-[#b50202] hover:text-white transition
                   focus:outline-none focus:ring-2 focus:ring-[#b50202] focus:ring-offset-2 focus:ring-offset-white
                   active:scale-[.99]">
            <span>Вернуться на главную</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    d="M5 12h14m-6-6l6 6-6 6" />
            </svg>
        </a>
    </div>
</main>


<?php get_footer(); ?>
