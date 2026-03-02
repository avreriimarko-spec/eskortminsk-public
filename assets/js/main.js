/* ===== Скролл-наверх ===== */
document.addEventListener("DOMContentLoaded", () => {
  const scrollTopBtn = document.getElementById("scrollTopBtn");
  if (scrollTopBtn) {
    const SHOW_AT = 300;

    const updateBtn = () => {
      if (window.scrollY > SHOW_AT) {
        scrollTopBtn.classList.remove("hidden");
      } else {
        scrollTopBtn.classList.add("hidden");
      }
    };

    window.addEventListener("scroll", updateBtn, { passive: true });
    updateBtn(); // обновим сразу при загрузке

    scrollTopBtn.addEventListener("click", (e) => {
      e.preventDefault();
      const reduce = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
      ).matches;
      window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
    });
  }

  /* ===== Скрытие части хедера при скролле (без ошибок, если бургера нет) ===== */
  const header = document.getElementById("siteHeader");
  const headerMain = document.querySelector(".header-main");
  const burger = document.getElementById("burgerOpen");
  const closeBtn = document.getElementById("closeMobileMenu");
  const overlay = document.getElementById("overlay");
  const mobileMenu = document.getElementById("mobileMenu");

  // Мобильное меню — вешаем обработчики только если все элементы существуют
  if (burger && closeBtn && overlay && mobileMenu) {
    burger.addEventListener("click", () => {
      mobileMenu.classList.remove("-translate-x-full");
      overlay.classList.remove("hidden");
    });
    closeBtn.addEventListener("click", () => {
      mobileMenu.classList.add("-translate-x-full");
      overlay.classList.add("hidden");
    });
    overlay.addEventListener("click", () => {
      mobileMenu.classList.add("-translate-x-full");
      overlay.classList.add("hidden");
    });
  }

  let lastY = window.scrollY || 0;

  window.addEventListener(
    "scroll",
    () => {
      if (!header) return;
      if (!headerMain) return;

      const y = window.scrollY;

      if (y > lastY && y > 50) {
        headerMain.classList.add("hidden");
      } else {
        headerMain.classList.remove("hidden");
      }
      lastY = y;
    },
    { passive: true }
  );

  window.addEventListener("resize", () => {
    if (header) header.classList.remove("hidden");
    if (headerMain) headerMain.classList.remove("hidden");
  });
});
