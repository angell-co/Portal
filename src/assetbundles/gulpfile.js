const gulp = require('gulp'),
      rename = require('gulp-rename'),
      uglify = require('gulp-uglify'),
      concat = require('gulp-concat'),
      sass = require('gulp-sass');

function livepreview_js() {
    return gulp.src([
            'node_modules/js-cookie/src/js.cookie.js',
            'node_modules/arrive/src/arrive.js',
            'src/LivePreview.js'
        ])
        .pipe(uglify())
        .pipe(concat('LivePreview.min.js'))
        .pipe(gulp.dest('livepreview/dist/js/'));
}

function livepreview_sass() {
    return gulp.src('src/LivePreview.scss')
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(rename({ extname: '.min.css' }))
        .pipe(gulp.dest('livepreview/dist/css/'));
}

function livepreview_svg() {
    return gulp.src('src/svg/**/*.svg')
        .pipe(gulp.dest('livepreview/dist/svg/'));
}

function watch() {
    gulp.watch('src/**/*.js', ['livepreview_js']);
    gulp.watch('src/**/*.scss', ['livepreview_sass']);
}

exports.default = gulp.series(livepreview_svg, livepreview_js, livepreview_sass);