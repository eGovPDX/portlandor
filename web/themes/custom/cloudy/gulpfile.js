var gulp = require('gulp'),
    browserSync = require('browser-sync').create(),
    sass = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    concat = require("gulp-concat"),
    minifyCss = require("gulp-minify-css"),
    uglify = require("gulp-uglify"),
    mode = require('gulp-mode')({
      modes: ["production", "development"],
      default: "development",
      verbose: false
    });

const localConfig = {
  sass:   {
    src: [
      './scss/style.scss'
    ],
    dest: './css',
  },
  js: {
    src: [
      'node_modules/bootstrap/dist/js/bootstrap.min.js',
      'node_modules/jquery/dist/jquery.min.js',
      'node_modules/popper.js/dist/umd/popper.min.js'
    ],
    dest: './js',
  }
};

// Compile sass into CSS & auto-inject into browsers
gulp.task('style', function() {
  return gulp.src(localConfig.sass.src)
    .pipe(mode.development(sourcemaps.init()))
    .pipe(sass({
      includePaths: ['node_modules'],
      outputStyle: 'compressed'
    })).on('error', sass.logError)
    .pipe(mode.development(sourcemaps.write('./maps')))
    .pipe(gulp.dest(localConfig.sass.dest))
    .pipe(mode.development(browserSync.stream()));
});

// Move the javascript files into our js folder
gulp.task('js', function() {
    return gulp.src(localConfig.js.src)
        .pipe(gulp.dest(localConfig.js.dest))
        .pipe(mode.development(browserSync.stream()));
});

// Static Server + watching scss/html files
gulp.task('serve', ['style'], function() {

    browserSync.init({
        proxy: "https://portlandor.lndo.site/",
    });

    gulp.watch(['scss/**/*.scss'], ['style']).on('change', browserSync.reload);
});

gulp.task('default', ['js', 'serve']);