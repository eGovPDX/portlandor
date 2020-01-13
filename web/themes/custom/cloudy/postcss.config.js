const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');
const tailwind = require('tailwindcss');

// @todo figure out if we can use purge at some point, or remove officially from the dependencies if not
// I'm guessing this was introduced with Tailwind as they suggest its use. However, without a comprehensive list of templates to point at, and potentially classes coming from Drupal, we cannot effectively determine what css to remove based on only the templates dir. Perhaps we can point this to all template sources (include modules), not sure.
// const purgecss = require('@fullhuman/postcss-purgecss')({
//   // Specify the paths to all of the template files in your project
//   content: [
//     './source/**/*.twig',
//   ],
//   // Include any special characters you're using in this regular expression
//   defaultExtractor: content => content.match(/[A-Za-z0-9-_:/]+/g) || []
// });

/* eslint-disable-next-line no-unused-vars */
module.exports = ({ file, options, env }) => {
  return {
    plugins: [
      tailwind,
      autoprefixer,
      // ...options.mode === 'production'
      //   ? [purgecss]
      //   : [],
      cssnano({
        preset: 'default'
      })
    ]
  };
};
