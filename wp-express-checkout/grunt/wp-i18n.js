
// generates the POT language file
// run `grunt makepot` or `grunt build` to trigger this task
module.exports = function(grunt) {

	grunt.config('makepot2', {

		dist: {
			options: {
				type: '<%= globals.type %>',
				potFilename: '<%= pkg.name %>.pot',
				domainPath: '<%= globals.languages %>',
				potHeaders: {
					poedit: true,
					'Report-Msgid-Bugs-To': '',
					'Language-Team': '<%= pkg.author.name %>',
					'Last-Translator': '<%= pkg.author.name %>',
				},
				exclude: [
					'apigen/.*',
					'bower_components/.*',
					'docs/.*',
					'.*examples/.*',
					'tests/.*',
					'vendor/.*',
				]
			}
		},

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

};
