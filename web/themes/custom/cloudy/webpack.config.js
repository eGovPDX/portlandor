const path = require('path');
const webpack = require('webpack');
const CopyPlugin = require('copy-webpack-plugin');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const RTLCSSPlugin = require('rtlcss-webpack-plugin');

const isProd = process.env.NODE_ENV === 'production';

const config = {
  entry: {
    cloudy: ['./src/cloudy.scss', './src/cloudy.js'],
    'cloudy-ckeditor': ['./src/cloudy-ckeditor.scss'],
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
    maxAssetSize: 60000000,
  },
  devtool: isProd ? 'source-map' : 'cheap-module-source-map',
  mode: isProd ? 'production' : 'development',
  stats: {
    assets: true,
    assetsSpace: 50,
    excludeAssets: [
      /\.ttf/,
      /\.woff2/,
    ],
    groupAssetsByChunk: false,
    groupAssetsByEmitStatus: false,
    modules: false,
  },
  watchOptions: {
    ignored: [
      'images/**/*.*',
      'dist/**/*.*',
      'templates/**/*.*',
      'node_modules',
    ]
  },
  plugins: [
    new webpack.ProgressPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].bundle.css',
      chunkFilename: '[id].bundle.css',
    }),
    new CopyPlugin({
      patterns: [
        { from: 'node_modules/@fortawesome/fontawesome-free/webfonts' },
      ],
    }),
    new RTLCSSPlugin('cloudy-rtl.bundle.css'),
  ],
  optimization: {
    minimizer: [
      new CssMinimizerPlugin(),
    ],
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: {
          loader: 'swc-loader',
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
                  'postcss-preset-env',
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sassOptions: {
                // Needed to preserve comments for RTLCSS
                outputStyle: 'expanded',
              },
            },
          },
        ],
      },
    ],
  },
};

module.exports = config;
