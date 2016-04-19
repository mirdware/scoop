var gulp = require("gulp"),
    browserify = require("browserify"),
    source = require("vinyl-source-stream"),
    stylus = require("gulp-stylus"),
    rename = require("gulp-rename"),
    mincss = require("gulp-cssnano"),
    buffer = require("vinyl-buffer"),
    uglify = require("gulp-uglify"),
    sourcemaps = require("gulp-sourcemaps"),
    livereload = require("gulp-livereload"),
    nib = require("nib"),
    fontAwesome = require("fa-stylus"),
    app = require("./package.json"),
    filesToMove = [
      "./node_modules/fa-stylus/fonts/**/*.*",
    ];

gulp.task("css", function() {
    return gulp.src("app/styles/app.styl")
        .pipe(sourcemaps.init())
        .pipe(stylus({
            "use": [
                nib(),
                fontAwesome()
            ],
            "import": ["nib"],
            "include css": true
        }))
        .pipe(mincss())
        .pipe(rename(app.name+".min.css"))
        .pipe(sourcemaps.write("."))
        .pipe(gulp.dest("public/css/"))
        .pipe(livereload());
});

gulp.task("js", function() {
    browserify({
            "entries": "app/javascript/app.js",
            "debug": true
        })
        .bundle()
        .pipe(source(app.name+".min.js"))
        .pipe(buffer())
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(sourcemaps.write("."))
        .pipe(gulp.dest("public/js/"))
        .pipe(livereload());
});

gulp.task("move", function () {
  return gulp.src(filesToMove)
    .pipe(gulp.dest("public/fonts"));
});

gulp.task("default", ["css", "js", "move"], function() {
    livereload.listen();
    gulp.watch("app/styles/**/*", ["css"]);
    gulp.watch("app/javascript/**/*", ["js"]);
    gulp.watch("./**/*.php").on("change", livereload.changed);
});
