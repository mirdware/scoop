const gulp = require('gulp');
const browserify = require('browserify');
const source = require('vinyl-source-stream');
const stylus = require('gulp-stylus');
const rename = require('gulp-rename');
const mincss = require('gulp-cssnano');
const buffer = require('vinyl-buffer');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const livereload = require('gulp-livereload');
const nib = require('nib');
const fontAwesome = require('fa-stylus');
const app = require('./package.json');
const filesToMove = './node_modules/fa-stylus/fonts/**/*.*';
const pathScripts = 'app/scripts/';
const pathStyles = 'app/styles/';

gulp.task('css', function () {
  return gulp.src(pathStyles + 'app.styl')
    .pipe(sourcemaps.init())
    .pipe(stylus({
      'use': [nib(), fontAwesome()],
      'import': ['nib'],
      'include css': true
    }))
    .pipe(mincss())
    .pipe(rename(app.name + '.min.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('public/css/'))
    .pipe(livereload());
});

gulp.task('js', function () {
  browserify({
      'entries': pathScripts + 'app.js',
      'debug': true
    })
    .transform('babelify', {
      'presets': ['env']
    })
    .bundle()
    .pipe(source(app.name + '.min.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init())
    .pipe(uglify())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('public/js/'))
    .pipe(livereload());
});

gulp.task('move', function () {
  return gulp.src(filesToMove)
  .pipe(gulp.dest('public/fonts'));
});

gulp.task('default', ['css', 'js', 'move'], function () {
  livereload.listen();
  gulp.watch(pathStyles + '**/*', ['css']);
  gulp.watch(pathScripts + '**/*', ['js']);
  gulp.watch('./**/*.php').on('change', livereload.changed);
});
