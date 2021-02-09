
// transforms style.css from LTR to RTL and creates style-rtl.css
// run `grunt rtlcss` or `grunt build` to trigger this task
module.exports = function(grunt) {

	grunt.config('rtlcss', {

		dist: {
			options: {
				autoRename: false,
				autoRenameStrict: false,
				blacklist:{},
				clean: true,
				greedy: false,
				processUrls: false,
			},
			files: {
				'<%= globals.css %>/public-rtl.css': '<%= globals.css %>/public-rtl.css',
				'<%= globals.css %>/public-rtl.min.css': '<%= globals.css %>/public-rtl.min.css'
			}
		},

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-rtlcss');

};
