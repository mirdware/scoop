const gulp = require('gulp');
const browserify = require('browserify');
const source = require('vinyl-source-stream');
const stylus = require('gulp-stylus');
const rename = require('gulp-rename');
const mincss = require('gulp-clean-css');
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

gulp.task('css', () => {
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

gulp.task('js', () => {
  return browserify({
    'entries': pathScripts + 'app.js',
    'debug': true
  })
  .transform('babelify', {
    'presets': ['@babel/preset-env']
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

gulp.task('move', () => {
  return gulp.src(filesToMove)
  .pipe(gulp.dest('public/fonts'));
});

gulp.task('default', gulp.series(gulp.parallel('css', 'js'), 'move'));

gulp.task('dev', gulp.parallel('default', () => {
  livereload.listen();
  gulp.watch(pathStyles + '**/*', gulp.parallel('css'));
  gulp.watch(pathScripts + '**/*', gulp.parallel('js'));
  gulp.watch('src/**/*.php').on('change', livereload.changed);
  gulp.watch('app/**/*.php').on('change', livereload.changed);
}));
