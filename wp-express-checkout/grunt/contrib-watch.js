
// watch and rebuild files when changes are detected
// run `grunt watch` to start this task
module.exports = function(grunt) {

	grunt.config('watch', {

		// compile any scss files when changed
		scss: {
			files: [
				'<%= globals.scss %>/**/*.scss',
			],
			tasks: [
				'sass:dev',
				'fixindent:dev',
			]
		},

		// compile any js files when changed
		js: {
			files: [
				'<%= globals.js %>/src/**/*.js',
				'!<%= globals.js %>/**/*.min.js',
			],
			tasks: [
				'uglify',
				'jsbeautifier',
			]
		},

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

};
