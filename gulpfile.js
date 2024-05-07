var project = 'stop-user-enumeration'; // Project Name.


var gulp = require('gulp');
var zip = require('gulp-zip');
var del = require('del');
var rename = require('gulp-rename');
var gutil = require('gulp-util');
var dirSync = require('gulp-directory-sync');
var removeLines = require('gulp-remove-lines');
var wpPot = require('gulp-wp-pot');
var sort = require('gulp-sort');
var notify = require("gulp-notify");

gulp.task('zip', (done) => {
    gulp.src('dist/**/*')
        .pipe(rename(function (file) {
            file.dirname = project + '/' + file.dirname;
        }))
        .pipe(zip(project + '-free.zip'))
        .pipe(gulp.dest('zipped'))
    done()
});

gulp.task('clean', () => {
    return del([
        'dist/**/sass/',
        'dist/**/*.css.map',
        'dist/**/*.scss',
        'dist/composer.*',
        'dist/includes/vendor/bin/',
        'dist/includes/vendor/**/.git*',
        'dist/includes/vendor/**/.git*',
        'dist/includes/vendor/**/.travis.yml',
        'dist/includes/vendor/**/.codeclimate.yml',
        'dist/includes/vendor/**/composer.json',
        'dist/includes/vendor/**/package.json',
        'dist/includes/vendor/**/composer.lock',
        'dist/includes/vendor/**/package-lock.json',
        'dist/includes/vendor/**/gulpfile.js',
        'dist/includes/vendor/**/*.md',
        'dist/includes/vendor/squizlabs',
        'dist/includes/vendor/wp-coding-standards',
    ]);
});


gulp.task('sync', () => {
    return gulp.src('.', {allowEmpty: true})
        .pipe(dirSync('stop-user-enumeration', 'dist', {printSummary: true}))
        .on('error', gutil.log);
});

gulp.task('translate', () => {
    return gulp.src(['stop-user-enumeration/**/*.php', '!stop-user-enumeration/{vendor,vendor/**}'])
        .pipe(sort())
        .pipe(wpPot({
            domain: project,
            package: project
        }))
        .on('error', gutil.log)
        .pipe(gulp.dest('stop-user-enumeration/languages/' + project + '.pot'))
        .pipe(gulp.dest('dist/languages/' + project + '.pot'))
        .pipe(notify({message: 'TASK: "translate" Completed! 💯', onLast: true}));

});


gulp.task('build', gulp.series('sync', 'clean', 'translate', 'zip'));

