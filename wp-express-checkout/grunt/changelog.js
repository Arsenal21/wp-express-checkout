
// generates a template-based changelog with repository changes and write them to a destination file.
// run `grunt changelog` to trigger this task
module.exports = function(grunt) {

	grunt.config('changelog', {
		wip: {
			options: {
				after: '<%= pkg.version %>',
				//insertType: 'prepend',
				dest: 'wip-temp.txt',
				//featureRegex: /^(.*)closes #\d+:?(.*)$/gim,
				//fixRegex: /^(.*)fixes #\d+:?(.*)$/gim,
				featureRegex: /^(.*)$/gim,
				template: '== Changelog ==\n\n= WIP since <%= pkg.version %> to {{date}} =\n{{> features}}= END WIP =',
				partials: {
					features: '{{#if features}}{{#each features}}{{> feature}}{{/each}}\n{{/if}}',
					//fixes: '{{#if fixes}}fixes:\n{{#each fixes}}{{> fix}}{{/each}}\n\n{{/if}}',
					feature: '* {{{this}}}\n',
					//fix: ' * {{{this}}}\n',
				}
			}
		}

	});

	// load the plugin
	grunt.loadNpmTasks('grunt-changelog');

};
