/*!
 * Gruntfile
 * https://www.tipsandtricks-hq.com/
 * @author Tips and Tricks HQ
 */

'use strict';

/**
 * Grunt Module
 */
module.exports = function(grunt) {

	grunt.initConfig({

		pkg: grunt.file.readJSON( 'package.json' ),

		// set global variables
		globals: {
			type: 'wp-plugin',
			textdomain: 'wp-express-checkout',
			js: 'assets/js',
			css: 'assets/css',
			scss: 'assets/scss',
			images: 'assets/images',
			languages: 'languages'
		}

	});



	/**
	 * Grunt Tasks
	 */

	// load plugin configs from grunt folder
	grunt.loadTasks( 'grunt' );


	// default task when you run 'grunt' that runs every task
	grunt.registerTask( 'default', [
		'build'
	]);


	// main task to run 'grunt dist'
	grunt.registerTask( 'dist', [
		'csscomb',
		'sass:dev',
		'postcss',
		'sass:dist',
		'rtlcss',
		'uglify',
		'jsbeautifier',
		'fixindent'
	]);


	// css task to run 'grunt css'
	grunt.registerTask( 'css', [
		'csscomb',
		'sass:dev',
		'postcss',
		'sass:dist',
		'rtlcss',
		'fixindent'
	]);


	// js task to run 'grunt js'
	grunt.registerTask( 'js', [
		'uglify:public',
		'uglify:admin',
		'jsbeautifier'
	]);


	// custom task when you run 'grunt test'
	grunt.registerTask( 'test', [
		'csslint',
		'jshint',
		'checktextdomain'
	]);


	// custom task when you run 'grunt misc'
	grunt.registerTask( 'misc', [
		'makepot',
		'imagemin'
	]);

	// custom task when you run 'grunt build'
	grunt.registerTask( 'build', [
		'dist',
		'misc',
		'test'
	]);


};
