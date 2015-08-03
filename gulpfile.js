var gulp = require('gulp'),
    browserify = require('browserify'),
    source = require('vinyl-source-stream'),
    stylus = require('gulp-stylus'),
    rename = require('gulp-rename'),
    mincss = require('gulp-minify-css'),
    buffer = require('vinyl-buffer'),
    uglify = require('gulp-uglify'),
    sourcemaps = require('gulp-sourcemaps'),
    app = require('./package.json');

gulp.task('css', function() {
    return gulp.src(['app/resources/styles/app.styl'])
        .pipe(sourcemaps.init())
        .pipe(stylus())
        .pipe(mincss())
        .pipe(rename(app.name+'.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('public/css/'));
});

gulp.task('js', function() {
    browserify({
            entries: 'app/resources/javascript/app.js',
            debug: true
        })
        .bundle()
        .pipe(source(app.name+'.min.js'))
        .pipe(buffer())
        .pipe(sourcemaps.init())
            .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('public/js/'));
});

gulp.task('watch', function() {
    gulp.watch(['app/resources/styles/**/*.styl'], ['css']);
    gulp.watch(['app/resources/javascript/**/*.js'], ['js']);
});

gulp.task('default', ['watch', 'css', 'js']);