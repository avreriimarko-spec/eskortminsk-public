module.exports = {
  content: ["./**/*.php", "./**/*.html", "./**/*.js", "!./node_modules/**/*"],
  theme: {
    extend: {
      colors: {
        paper: "#ffffff",
        ink: "#000000",
        "primary-red": "#b50202",
        "border-light": "#e5e7eb",
        red: {
          500: "#cc1c1c",
          600: "#b50202",
          700: "#8d0202",
        },
      },
      fontFamily: {
        montserrat: ["Montserrat", "sans-serif"], // основной текст
        playfair: ["Playfair Display", "serif"], // заголовки
      },
    },
  },
  plugins: [],
};
