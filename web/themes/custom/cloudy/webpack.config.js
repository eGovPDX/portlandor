const sass = require('sass');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const isProd = process.env.NODE_ENV === 'production';

const config = {
  entry: {
    cloudy: ['./src/cloudy.scss', './src/cloudy.js'],
    bootstrap: './src/js/bootstrap.js',
    'back-to-top': './src/js/back-to-top.js',
    'header-dropdown-toggle': './src/js/header-dropdown-toggle.js',
    'search-field': './src/js/search-field.js',
    'search-autocomplete': './src/js/search-autocomplete.js'
  },
  output: {
    path: path.resolve(__dirname, './dist'),
    publicPath: '/themes/custom/cloudy/dist/',
    filename: '[name].bundle.js',
  },
  /**
   * The externals configuration option provides a way of excluding dependencies from the output bundles.
   * Instead, the created bundle relies on that dependency to be present in the consumer's environment.
   * So basically we expect these to be present on the `window` ahead of time.
   * @link https://webpack.js.org/configuration/externals/
   */
  externals: {
    jquery: 'jQuery',
    Drupal: 'Drupal',
    drupal: 'Drupal',
  },
  performance: {
    // Disable performance hints. Currently not anything we can do to reduce bundle size.
    hints: false,
  },
  devtool: isProd ? 'source-map' : 'cheap-module-source-map',
  mode: isProd ? 'production' : 'development',
  watchOptions: {
    ignored: [
      'images/**/*.*',
      'dist/**/*.*',
      'templates/**/*.*',
      'node_modules',
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].bundle.css',
      chunkFilename: '[id].bundle.css',
    }),
  ],
  module: {
    rules: [
      {
        test: /\.js$/,
        use: {
          loader: 'swc-loader'
        },
      },
      {
        test: /\.(sa|sc)ss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  "postcss-preset-env",
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
          },
        ],
      },
    ],
  },
};

module.exports = config;
