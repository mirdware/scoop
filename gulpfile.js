var gulp = require('gulp'),
    browserify = require('browserify'),
    source = require('vinyl-source-stream'),
    stylus = require('gulp-stylus'),
    rename = require('gulp-rename'),
    mincss = require('gulp-minify-css'),
    buffer = require('vinyl-buffer'),
    uglify = require('gulp-uglify'),
    sourcemaps = require('gulp-sourcemaps'),
    livereload = require('gulp-livereload'),
    app = require('./package.json');

gulp.task('css', function() {
    return gulp.src(['resources/styles/app.styl'])
        .pipe(sourcemaps.init())
        .pipe(stylus())
        .pipe(mincss())
        .pipe(rename(app.name+'.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('public/css/'))
        .pipe(livereload());
});

gulp.task('js', function() {
    browserify({
            entries: 'resources/javascript/app.js',
            debug: true
        })
        .bundle()
        .pipe(source(app.name+'.min.js'))
        .pipe(buffer())
        .pipe(sourcemaps.init())
            .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('public/js/'))
        .pipe(livereload());
});

gulp.task('default', ['css', 'js'], function() {
    livereload.listen();
    gulp.watch('resources/styles/**/*.styl', ['css']);
    gulp.watch('resources/javascript/**/*.js', ['js']);
    gulp.watch('./**/*.php').on('change', livereload.changed);
});
