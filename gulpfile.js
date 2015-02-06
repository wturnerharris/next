var gulp = require('gulp'),
    sass = require('gulp-sass');

gulp.task('default', function() {
    gulp.watch('./Nearest/www/css/*.scss', ['sass']);
});


gulp.task('sass', function() {
    gulp.src('./Nearest/www/css/*.scss')
    .pipe( sass() )
    .pipe( gulp.dest('./Nearest/www/css') );
});