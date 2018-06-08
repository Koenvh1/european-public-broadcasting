var gulp = require("gulp");
var nunjucks = require("gulp-nunjucks");
var less = require("gulp-less");
var wrap = require("gulp-wrap-file");
var concat = require("gulp-concat");
var ext_replace = require("gulp-ext-replace");
var uglify_es = require("uglify-es");
var uglify_composer = require("gulp-uglify/composer");
var uglify = uglify_composer(uglify_es, console);

gulp.task("app-css", function() {
    return gulp.src(["src/css/style.less"])
        .pipe(less())
        .pipe(concat("app.css"))
        .pipe(gulp.dest("dist/css"))
});

gulp.task("vendor-css", function() {
    return gulp.src([
        "node_modules/bootstrap/dist/css/bootstrap.css",
        "node_modules/nprogress/nprogress.css",
        "node_modules/font-awesome/css/font-awesome.css",
        //"node_modules/video.js/dist/video-js.css",
    ])
        .pipe(concat("vendor.css"))
        .pipe(gulp.dest("dist/css"))
});

gulp.task("app-js", function() {
    return gulp.src([
        "src/js/npo.js",
        "src/js/index.js",
        "src/js/search.js",
        "src/js/Guide.js",
        "src/js/Programmes.js",
        "src/js/Player.js",
        "src/js/browser-warning.js",
        "src/js/language-switcher.js",
    ])
        .pipe(concat("app.js"))
        .pipe(gulp.dest("dist/js"))
});

gulp.task("vendor-js", function() {
    return gulp.src([
        "node_modules/jquery/dist/jquery.js",
        "node_modules/bootstrap/dist/js/bootstrap.bundle.js",
        "node_modules/nprogress/nprogress.js",
        "node_modules/l20n/dist/web/l20n.js",
        //"node_modules/video.js/dist/video.js",
    ])
        .pipe(concat("vendor.js"))
        .pipe(gulp.dest("dist/js"))
});

gulp.task("uglify-vendor-js", ["vendor-js"], function () {
    return gulp.src("dist/js/vendor.js")
        .pipe(uglify())
        .pipe(gulp.dest("dist/js"))
});

gulp.task("uglify-app-js", ["app-js"], function () {
    return gulp.src("dist/js/app.js")
        .pipe(uglify())
        .pipe(gulp.dest("dist/js"))
});

gulp.task("nunjucks", function () {
   gulp.src("src/*.twig")
       .pipe(nunjucks.compile())
       .pipe(ext_replace(".html"))
       .pipe(gulp.dest("dist"))
});

// Copy vendor libraries from /node_modules into /vendor
gulp.task("fonts", function() {
    gulp.src([
        "node_modules/font-awesome/fonts/*"
    ])
        .pipe(gulp.dest("dist/fonts"));
    gulp.src([
        "src/img/*"
    ])
        .pipe(gulp.dest("dist/img"))
    gulp.src([
        "src/locales/*"
    ])
        .pipe(gulp.dest("dist/locales"))
});

gulp.task("watch", function () {
   gulp.watch("src/css/style.less", ["app-css"]);
   gulp.watch("src/js/*.js", ["app-js"]);
   gulp.watch("src/*.twig", ["nunjucks"]);
   gulp.watch("src/img/*", ["fonts"]);
   gulp.watch("src/locales/*", ["fonts"]);
   gulp.watch("src/components/*.twig", ["nunjucks"]);
});

// Run everything
gulp.task("default", ["app-css", "vendor-css", "app-js", "vendor-js", "nunjucks", "fonts", "watch"]);
gulp.task("release", ["app-css", "vendor-css", "uglify-app-js", "uglify-vendor-js", "nunjucks", "fonts"]);