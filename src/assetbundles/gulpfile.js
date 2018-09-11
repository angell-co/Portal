'use strict';

var gulp = require('gulp'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify'),
    sass = require('gulp-sass');

gulp.task('livepreview_js', function() {
    return gulp.src('src/LivePreview.js')
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest('livepreview/dist/'));
});

gulp.task('livepreview_sass', function() {
    return gulp.src('src/LivePreview.scss')
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(rename({ extname: '.min.css' }))
        .pipe(gulp.dest('livepreview/dist/'));
});

gulp.task('watch', function(){
    gulp.watch('src/**/*.js', ['livepreview_js']);
    gulp.watch('src/**/*.scss', ['livepreview_sass']);
});

gulp.task('default', ['livepreview_js','livepreview_sass']);