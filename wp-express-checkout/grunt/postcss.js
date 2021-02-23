
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
				require( 'postcss-nested' ),
				require( 'postcss-css-variables' )( {
					preserve: false,
					preserveAtRulesOrder: true,
					preserveInjectedVariables: false
				} ),
				require( 'autoprefixer' )( {
					cascade: false,
					overrideBrowserslist: [
						'> 0.5%',
						'last 2 versions'
					]
				} ),
				require( 'postcss-rem-to-pixel' )( {
					rootValue: 16,
					propList: ['*']
				} )
			]
		},

		dist: {
			src: [
				'<%= globals.css %>/*.css'
			]
		}

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-postcss');

};
