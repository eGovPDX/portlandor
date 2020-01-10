const path = require('path');
const globby = require('globby');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const babelConfig = require('./babel.config');

const isProd = process.env.NODE_ENV === 'production';

module.exports = (env, argv) => ({
  entry: {
    cloudy: [
      './src/cloudy.js',
      './src/cloudy.scss',
    ],
    'search-field': './src/js/search-field.js',
    'bootstrap': './src/js/bootstrap.js',
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
  devtool: 'source-map',
  mode: isProd ? 'production' : 'development',
  watchOptions: {
    ignored: ['images/**/*.*', 'dist/**/*.*', 'templates/**/*.*', 'node_modules']
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css'
    }),
    // new BrowserSyncPlugin(
    //   // BrowserSync options
    //   {
    //     // browse to https://localhost:3000/ during development
    //     host: 'localhost',
    //     port: 3000,
    //     // proxy the Lando endpoint
    //     // through BrowserSync
    //     proxy: 'https://portlandor.lndo.site/',
    //     // Open the proxied site
    //     open: 'local'
    //   },
    //   // plugin options
    //   {}
    // )
  ],
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: babelConfig,
        }
      },
      {
        test: /\.(sa|sc)ss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
              url: false
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: true,
              config: {
                path: './postcss.config.js',
                ctx: {
                  mode: argv.mode
                }
              }
            }
          },
          {
            loader: 'resolve-url-loader'
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true
            }
          }
        ]
      }
    ]
  },
  performance: {
    assetFilter: function (assetFilename) {
      // only give warnings about size for javascript files
      return assetFilename.endsWith('.js');
    }
  },
});
