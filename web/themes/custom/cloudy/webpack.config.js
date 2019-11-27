const path = require('path');
const globby = require('globby');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => ({
  entry: {
    main: ['./src/js/main.js', './src/css/style.scss'],
    'search-field': './src/js/search-field.js'
  },
  devtool: 'source-map',
  mode: process.env.NODE_ENV,
  output: {
    path: path.resolve(__dirname),
    filename: 'dist/[name].bundle.js'
  },
  watchOptions: {
    ignored: ['images/**/*.*', 'dist/**/*.*', 'templates/**/*.*', 'node_modules']
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'dist/style.bundle.css',
      chunkFilename: 'dist/[id].bundle.css'
    }),
  ],
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
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
  externals: {
    jquery: 'jQuery',
    Drupal: 'Drupal',
  },
});
