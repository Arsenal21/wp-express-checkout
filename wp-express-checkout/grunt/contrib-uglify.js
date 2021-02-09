
// combine and create min versions of all non lib js files
// run `grunt uglify` or `grunt dist` to trigger this task
module.exports = function(grunt) {

	grunt.config('uglify', {

		dist: {
			options: {
				report: 'gzip'
			},
			files: [{
				expand: true,
				src: [
					'<%= globals.js %>/public.js'
				],
				ext: '.min.js',
				extDot: 'last'
			}]
		},

		// process the theme js files. combine them into one.
//		public: {
//			options: {
//				beautify: true,
//				mangle: false,
//				compress: false,
//				preserveComments: 'some'
//			},
//			files: [{
//				src: [
//					'<%= globals.js %>/src/**/*.js',
//					'!<%= globals.js %>/src/admin.js',
//					'!<%= globals.js %>/src/**/*.min.js'
//				],
//				dest: '<%= globals.js %>/public.js'
//			}]
//		}
	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );

};
