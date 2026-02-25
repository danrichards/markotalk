module.exports = {
  content: [
    './app/*/resources/views/**/*.latte',
    './resources/views/**/*.latte',
    './src/css/**/*.css',
  ],
  theme: {
    extend: {
      colors: {
        brand: { DEFAULT: '#4f46e5', light: '#818cf8', dark: '#3730a3' },
      },
      width: { sidebar: '260px' },
      height: { header: '56px' },
    },
  },
}
