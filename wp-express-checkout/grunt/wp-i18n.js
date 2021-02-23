
// generates the POT language file
// run `grunt makepot` or `grunt build` to trigger this task
module.exports = function(grunt) {

	grunt.config('makepot', {

		dist: {
			options: {
				type: '<%= globals.type %>',
				potFilename: '<%= globals.textdomain %>.pot',
				domainPath: '/<%= globals.languages %>',
				potHeaders: {
					poedit: true,
					'Report-Msgid-Bugs-To': '',
					'Language-Team': '<%= pkg.author.name %>',
					'Last-Translator': '<%= pkg.author.name %>'
				},
				exclude: [
					'tests/.*',
					'vendor/.*'
				]
			}
		},

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

};
