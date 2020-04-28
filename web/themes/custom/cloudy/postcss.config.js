const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

const isProd = process.env.NODE_ENV === 'production';

module.exports = {
  plugins: [
    autoprefixer,
    isProd &&
      cssnano({
        preset: 'default',
      }),
  ].filter(Boolean),
};
