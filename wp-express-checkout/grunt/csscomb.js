
// sorting CSS properties into a specific order
// run `grunt csscomb` or `grunt dist` to trigger this task
module.exports = function(grunt) {

	grunt.config('csscomb', {

		options: {
			config: '<%= globals.scss %>/.csscomb.json'
		},

		dynamic_mappings: {
			expand: true,
			cwd: '<%= globals.scss %>',
			src: [
				'mixins/**/*.scss',
				'modules/**/*.scss',
				'partials/**/*.scss'
			],
			dest: '<%= globals.scss %>'
		}

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-csscomb');

};
