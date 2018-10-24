var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var cssVariables = require('postcss-custom-properties');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('autoprefixer');
var runSequence = require('run-sequence');

// Compile sass into CSS & auto-inject into browsers
gulp.task('sass', function() {
  return gulp.src('scss/style.scss')
    .pipe(sass({
      includePaths: ['node_modules'],
      outputStyle: 'compressed'
    })).on('error', sass.logError)
    .pipe(gulp.dest('./css'))
    .pipe(browserSync.stream());
});

// Apply postcss processors to generated CSS, write source maps, then output
gulp.task('css', ['sass'], function() {
  var processors = [
    autoprefixer({browsers: ['last 2 version', 'safari 5', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4']}),
    cssVariables()
  ];
  return gulp.src('css/style.css')
    .pipe(sourcemaps.init())
    .pipe(postcss(processors))
    .pipe(sourcemaps.write('./maps'))
    .pipe(gulp.dest('./css'))
})

// Move the javascript files into our js folder
gulp.task('js', function() {
    return gulp.src(['node_modules/bootstrap/dist/js/bootstrap.min.js', 'node_modules/jquery/dist/jquery.min.js', 'node_modules/popper.js/dist/umd/popper.min.js'])
        .pipe(gulp.dest("js"))
        .pipe(browserSync.stream());
});

// Static Server + watching for scss file changes
gulp.task('serve', ['sass', 'css'], function() {

    browserSync.init({
        proxy: "https://portlandor.lndo.site/",
    });

    gulp.watch(['scss/**/*.scss'], ['sass', 'css']).on('change', browserSync.reload);
});

// Default task for generating js and CSS files
gulp.task('default', function() {
	runSequence(
    'js',
    'sass',
		'css',
	);
});
