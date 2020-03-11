const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

/* eslint-disable-next-line no-unused-vars */
module.exports = ({ file, options, env }) => {
  return {
    plugins: [
      autoprefixer,
      cssnano({
        preset: 'default'
      })
    ]
  };
};
