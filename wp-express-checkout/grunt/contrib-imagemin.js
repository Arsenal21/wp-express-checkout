
// minimize and compress all images
// run `grunt imagemin` or `grunt build` to trigger this task
module.exports = function(grunt) {

	grunt.config('imagemin', {

		dist: {
			options: {
				optimizationLevel: 5,
				progressive: true,
				interlaced: true,
			},
			files: [{
				expand: true,
				src: [
					'<%= globals.images %>/**/*.{png,jpg,gif}',
					'images/*.{png,jpg,gif}',
					'screenshot-1.jpg',
					'screenshot-2.jpg',
					'screenshot-3.png'
				]
			}]
		}

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-contrib-imagemin' );

};
