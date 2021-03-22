
// copies the contents of the wip-temp.txt to the changelog section of readme.txt
// run `grunt wip` to trigger this task.
module.exports = function(grunt) {

	grunt.config('usebanner', {

		// global options
		options: {
			position: 'replace',
			linebreak: true
		},

		wip: {
			options: {
				replace: '(== Changelog ==((.|\n)*)= END WIP =)|(== Changelog ==)',
				process: function( filepath ) {
					return grunt.file.read( 'wip-temp.txt' ) || '';
				}
			},
			files: {
				src: ['readme.txt']
			}
		}

	});


	// load the plugin
	grunt.loadNpmTasks('grunt-banner');

};
