
// properly format all non min js files
// run `grunt jsbeautifier` or `grunt dist` to trigger
module.exports = function(grunt) {

	grunt.config('jsbeautifier', {

		dist: {
			options: {
				js: {
					indentWithTabs: true,
					spaceInParen: true
				}
			},
			files: [{
				src: [
					'<%= globals.js %>/**/*.js',
					'!<%= globals.js %>/**/*.min.js',
					'!<%= globals.js %>/lib/**/*.js'
				]
			}]
		},

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-jsbeautifier' );

};
