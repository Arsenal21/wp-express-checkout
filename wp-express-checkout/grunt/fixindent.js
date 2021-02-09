
// replace space indentation with a tab
// run `grunt fixindent` or `grunt dist` to trigger this task
module.exports = function(grunt) {

	grunt.config('fixindent', {

		// global options
		options: {
			style: 'tab',
			size: 1,
		},

		dist: {
			src: [
			'<%= globals.css %>/**/*.css',
			'!<%= globals.css %>/**/*.min.css',
			],
			dest: '<%= globals.css %>/'
		},

		// only fix main stylesheet during dev so we
		// don't waste time and add noise to our commits
		dev: {
			src: [
			'<%= globals.css %>/style.css'
			],
			dest: '<%= globals.css %>/'
		}

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-fixindent');

};
