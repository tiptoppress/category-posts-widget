var gulp = require('gulp');
var uglify = require('gulp-uglify');
var rename = require("gulp-rename");

gulp.task( 'default', [ 'scripts' ] )

// Gulp task to minify JavaScript files
gulp.task('scripts', function() {
  return gulp.src(['./js/**/*.js', '!./js/**/*.min.js'])
    // Minify the file
    .pipe(uglify())
	.pipe(rename({suffix: '.min'}))
	.pipe(gulp.dest(function(file) {
		 return file.base;
	}));
});
