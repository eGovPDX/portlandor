const sass = require('sass');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const babelConfig = require('./babel.config');

const isProd = process.env.NODE_ENV === 'production';

const config = {
  entry: {
    cloudy: ['./src/cloudy.js', './src/cloudy.scss'],
    'search-field': './src/js/search-field.js',
    bootstrap: './src/js/bootstrap.js',
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
  devtool: isProd ? 'source-map' : 'cheap-module-source-map',
  mode: isProd ? 'production' : 'development',
  watchOptions: {
    ignored: [
      'images/**/*.*',
      'dist/**/*.*',
      'templates/**/*.*',
      'node_modules',
    ],
    poll: 100,
    aggregateTimeout: 100,
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css',
    }),
  ],
  stats: {
    // removes needless `mini-css-extract-plugin` noisy output
    children: false,
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: {
          loader: 'babel-loader',
          options: babelConfig,
        },
      },
      {
        test: /\.(sa|sc)ss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
              url: false,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: true,
            },
          },
          {
            loader: 'resolve-url-loader',
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
              implementation: sass,
              sassOptions: {
                fiber: false,
              },
            },
          },
        ],
      },
    ],
  },
};

if (isProd) {
  config.performance = {
    maxAssetSize: 250000,
    assetFilter(assetFilename) {
      // don't warn about Source Maps
      if (assetFilename.endsWith('.map')) return false;
      return true;
    },
  };

  config.optimization = {
    minimize: true,
  };
}

module.exports = config;
