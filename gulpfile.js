const gulp = require('gulp');
const fs = require("fs");
const path = require('path');
const browserify = require('browserify');
const source = require('vinyl-source-stream');
const stylus = require('gulp-stylus');
const rename = require('gulp-rename');
const mincss = require('gulp-clean-css');
const buffer = require('vinyl-buffer');
const uglify = require('gulp-uglify');
const sourcemaps = require('gulp-sourcemaps');
const browserSync = require('browser-sync').create();
const php = require('gulp-connect-php');
const nib = require('nib');
const fontAwesome = require('fa-stylus');
const app = require('./package.json');
const filesToMove = getModulePath() + '/node_modules/fa-stylus/fonts/**/*.*';
const pathScripts = 'app/scripts/';
const pathStyles = 'app/styles/';
const inject = require('gulp-js-html-inject');

function getModulePath() {
  const cumulativePath = (acc) => (value) => (acc += value + path.sep)
  let folder = __dirname;
  return ['/'].concat(
    folder.split(path.sep).filter(Boolean).map(cumulativePath(path.sep))
  ).reverse().filter((currentPath) => fs.existsSync(currentPath + 'node_modules'));
}

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
  .pipe(browserSync.stream());
});

gulp.task('js', () => {
  return browserify({
    'entries': pathScripts + 'app.js',
    'debug': true
  })
  .transform('babelify', {
    presets: ['@babel/preset-env'],
    plugins: [
      ["@babel/plugin-proposal-decorators", { "legacy" : true }]
    ]
  })
  .bundle()
  .pipe(source(app.name + '.min.js'))
  .pipe(buffer())
  .pipe(sourcemaps.init())
  .pipe(inject({basepath: pathScripts, pattern: /'import:([a-zA-Z0-9\-_.\/]+)'/g}))
  .pipe(uglify())
  .pipe(sourcemaps.write('.'))
  .pipe(gulp.dest('public/js/'))
  .pipe(browserSync.stream());
});

gulp.task('move', () => {
  return gulp.src(filesToMove)
  .pipe(gulp.dest('public/fonts'));
});

gulp.task('default', gulp.parallel('css', 'js', 'move'));

gulp.task('dev', gulp.parallel('default', () => {
  php.server({
    router: './app/router.php',
    hostname: '0.0.0.0'
  }, function (){
    browserSync.init(['**/*.php'], {
      proxy: process.env.PHP_HOST || 'http://localhost:8000',
      port: 8001
    });
  });
  gulp.watch(pathStyles + '**/*', {interval: 1000, usePolling: true}, gulp.series('css'));
  gulp.watch(pathScripts + '**/*', {interval: 1000, usePolling: true}, gulp.series('js'));
}));
