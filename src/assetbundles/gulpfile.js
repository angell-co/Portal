'use strict';

var gulp = require('gulp'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify');

gulp.task('livepreview', function() {
    return gulp.src('src/LivePreview.js')
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest('livepreview/dist/'));
});

gulp.watch('src/**/*.js', ['default']);

gulp.task('default', ['livepreview']);