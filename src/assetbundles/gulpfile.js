'use strict';

var gulp = require('gulp'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    sass = require('gulp-sass');

gulp.task('livepreview_js', function() {
    return gulp.src(['node_modules/js-cookie/src/js.cookie.js','src/LivePreview.js'])
        .pipe(uglify())
        .pipe(concat('LivePreview.min.js'))
        .pipe(gulp.dest('livepreview/dist/js/'));
});

gulp.task('livepreview_sass', function() {
    return gulp.src('src/LivePreview.scss')
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(rename({ extname: '.min.css' }))
        .pipe(gulp.dest('livepreview/dist/css/'));
});

gulp.task('livepreview_svg', function() {
    return gulp.src('src/svg/**/*.svg')
        .pipe(gulp.dest('livepreview/dist/svg/'));
});

gulp.task('watch', function(){
    gulp.watch('src/**/*.js', ['livepreview_js']);
    gulp.watch('src/**/*.scss', ['livepreview_sass']);
});

gulp.task('default', ['livepreview_svg','livepreview_js','livepreview_sass']);