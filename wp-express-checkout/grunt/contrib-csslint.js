
// validate and point out problems with css
// run `grunt csslint` or `grunt test` to trigger this task
module.exports = function(grunt) {

	grunt.config('csslint', {

		options: {
			csslintrc: '<%= globals.css %>/.csslintrc'
		},

		src: [
			'<%= globals.css %>/**/*.css',
			'!<%= globals.css %>/public-rtl.css',
			'!<%= globals.css %>/**/*.min.css'
		]

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-contrib-csslint');

};
