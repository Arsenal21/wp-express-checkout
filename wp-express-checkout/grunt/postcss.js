
// adds vendor prefixes to css rules using the "Can I Use" database
// run `grunt postcss` or `grunt dist` to trigger this task
module.exports = function(grunt) {

	grunt.config('postcss', {

		options: {
			processors: [
				require( 'tailwindcss' )( {
					plugins: [
						require( '@tailwindcss/forms' )
					]
				} ),
				require( 'autoprefixer' )( {
					cascade: false,
					overrideBrowserslist: [
						'last 2 versions',
						'> 10%'
					]
				} )
			]
		},

		dist: {
			src: [
				'<%= globals.css %>/*.css',
				'<%= globals.css %>/public-rtl.css',
				'<%= globals.css %>/admin.css'
			]
		}

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-postcss');

};
