
// make sure all .js files are valid
// run `grunt jshint` or `grunt test` to trigger
module.exports = function(grunt) {

	grunt.config('jshint', {

		dist: [
			'<%= globals.js %>/*.js',
			'!<%= globals.js %>/*.min.js',
			'!<%= globals.js %>/lib/**/*.js',
		]

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );

};
