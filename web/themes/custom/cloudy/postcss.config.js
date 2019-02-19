const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

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
