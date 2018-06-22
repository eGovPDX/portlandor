// webpack v4

const path = require('path');
const globby = require('globby');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = {
  entry: { 
    main: globby.sync([
      './source/_patterns/**/*.js'
    ])
    .map(function(filePath) {
      return filePath;
    })
  },
  output: {
    path: path.resolve(__dirname, 'source'),
    filename: 'js/main.js'
  },
  module: {
    rules: [
      {
        enforce: 'pre',
        test: /\.scss$/,
        loader: 'import-glob-loader'
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.scss$/,
        use: ExtractTextPlugin.extract(
          {
            fallback: 'style-loader',
            use: [
              {
                loader: 'css-loader'
              },
              {
                loader: 'postcss-loader',
                options: {
                  config: {
                    path: './postcss.config.js'
                  }
                }
              },
              {
                loader: 'sass-loader',
                options: {
                  includePaths: require('node-normalize-scss').with(['./node_modules'])
                }
              }],
          })
      }
    ]
  },
  plugins: [
    new ExtractTextPlugin(
      {
        filename: 'css/style.css'
      }
    )
  ],
  watch: process.env.NODE_ENV == 'development'
};