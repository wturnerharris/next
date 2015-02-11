var gulp = require('gulp'),
    sass = require('gulp-sass'),
    copy = require('gulp-copy');

gulp.task('default', function() {
    gulp.watch('./Nearest/www/css/*.scss', ['sass']);
    gulp.src('./jquery.js').pipe( copy('./Nearest/www/js') );
});


gulp.task('sass', function() {
    gulp.src('./Nearest/www/css/*.scss')
    .pipe( sass() )
    .pipe( gulp.dest('./Nearest/www/css') );
});