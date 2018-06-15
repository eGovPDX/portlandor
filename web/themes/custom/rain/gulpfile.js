/* globals require */

// eslint-disable-next-line strict
'use strict';

const gulp = require('gulp');
const sass = require('gulp-sass');
const browserSync = require('browser-sync');
const reload = browserSync.reload;
const sassGlob = require('gulp-sass-glob');
const sourcemaps = require('gulp-sourcemaps');
const stylelint = require('gulp-stylelint');
const prefix = require('gulp-autoprefixer');
const cached = require('gulp-cached');
const flatten = require('gulp-flatten');
const gulpif = require('gulp-if');
const cleanCSS = require('gulp-clean-css');
const del = require('del');

const cssConfig = {
  enabled: true,
  src: `./source/_patterns/**/*.scss`,
  dest: `./dist/`,
  pl: './source/css',
  flattenDestOutput: true,
  lint: {
    enabled: false,
    failOnError: true,
  },
  sourceComments: false,
  sourceMapEmbed: false,
  outputStyle: 'expanded',
  autoPrefixerBrowsers: ['last 2 versions', 'IE >= 10'],
  includePaths: ['./node_modules'],
};

gulp.task('css', function(done) {
  gulp.src('./source/_patterns/style.scss')
    .pipe(sassGlob())
    .pipe(stylelint({
      failAfterError: false,
      reporters: [{
        formatter: 'string', console: true,
      }],
    }))
    .pipe(sass({
      outputStyle: cssConfig.outputStyle,
      sourceComments: cssConfig.sourceComments,
      // eslint-disable-next-line global-require
      includePaths: require('node-normalize-scss').with(cssConfig.includePaths),
    }).on('error', sass.logError))
    .pipe(prefix(cssConfig.autoPrefixerBrowsers))
    .pipe(sourcemaps.init())
    .pipe(cleanCSS())
    .pipe(sourcemaps.write((cssConfig.sourceMapEmbed) ? null : './'))
    .pipe(gulpif(cssConfig.flattenDestOutput, flatten()))
    .pipe(gulp.dest(cssConfig.dest))
    .pipe(gulp.dest(cssConfig.pl))
    .on('end', () => {
      reload('*.css');
      done();
    });
  });

gulp.task('clean:css', function(done) {
  del([
    `${cssConfig.dest}*.{css,css.map}`,
  ]).then(() => {
    done();
  });
});

gulp.task('validate:css', function() {
  let src = cssConfig.src;
  if (cssConfig.lint.extraSrc) {
    src = src.concat(cssConfig.lint.extraSrc);
  }
  return gulp.src(src)
    .pipe(cached('validate:css'))
    .pipe(stylelint({
      reporters: [{
        formatter: 'string', console: true,
      }],
    }));
});

gulp.task('watch:css', function() {
  const tasks = ['css'];
  if (cssConfig.lint.enabled) {
    tasks.push('validate:css');
  }
  return gulp.watch(cssConfig.src, tasks);
});

// const cssDeps = [];

// g

// watch Sass files for changes, run the Sass preprocessor with the 'sass' task and reload
gulp.task('serve', ['css'], function() {
  browserSync({
    server: {
      baseDir: 'dist'
    }
  });

  gulp.watch('./source/_patterns/style.scss', ['css']);
});
