/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.{html,php}",
    "./src/**/*.{html,php}",
  ],
  theme: {
    extend: {
      fontFamily: {
        'figtree': ['Figtree', 'system-ui', '-apple-system', 'sans-serif'],
      },
      colors: {
        'sky-primary': '#0ea5e9',
        'sky-hover': '#0284c7',
        'light-blue': '#00B4D8',
      },
      backgroundImage: {
        'hero-pattern': "url('./assets/images/background.png')",
        // 'footer-texture': "url('/src/assets/texture.png')",
      }
    },
  },
  plugins: [],
}

