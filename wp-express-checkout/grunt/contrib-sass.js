
// compile all .scss files
// run `grunt sass` or `grunt dist` to trigger this task
module.exports = function(grunt) {

	grunt.config('sass', {

		// global options
		options: {
			sourcemap: 'none',
		},

		// compress & create min version of styles for production use
		dist: {
			options: {
				style: 'compressed',
				loadPath: require('node-bourbon').includePaths
			},
			files: {
				'<%= globals.css %>/public.min.css': '<%= globals.css %>/public.css',
				'<%= globals.css %>/admin.min.css': '<%= globals.css %>/admin.css',
				'<%= globals.css %>/public-rtl.min.css': '<%= globals.css %>/public-rtl.css'
			}
		},

		// create clean version of styles for dev use
		dev: {
			options: {
				style: 'expanded',
				loadPath: require('node-bourbon').includePaths
			},
			files: {
				'<%= globals.css %>/public.css': '<%= globals.scss %>/public.scss',
				'<%= globals.css %>/admin.css': '<%= globals.scss %>/admin.scss',
				'<%= globals.css %>/blocks.css': '<%= globals.scss %>/blocks.scss',
				'<%= globals.css %>/public-rtl.css': '<%= globals.scss %>/public-rtl.scss'
			}
		}

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-contrib-sass');

};
