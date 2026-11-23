<?php
/**
 * PHPUnit bootstrap for the WP Express Checkout plugin tests.
 *
 * It needs the WordPress test library (installed by tests/bin/install-wp-tests.sh) and a test database.
 * The GitHub Actions "Plugin Tests" workflow sets this up. WP_TESTS_DIR is the folder of the WordPress test library.
 */

require __DIR__ . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/vendor/yoast/phpunit-polyfills' );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run tests/bin/install-wp-tests.sh first." . PHP_EOL;
	exit( 1 );
}

//Gives access to tests_add_filter().
require_once "{$_tests_dir}/includes/functions.php";

//Load the plugin. It loads its classes on 'plugins_loaded', which runs after 'muplugins_loaded'.
tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__, 2 ) . '/wp-express-checkout/wp-express-checkout.php';
	}
);

//Then run what the plugin's activation does (save the default settings, create the Thank You and Shop pages).
//It runs on 'init' (after the plugin's post types are registered), like a real activation that happens after WordPress is loaded.
//Note: the test library deletes all posts after WordPress loads, so the pages created here are gone when the tests run
//(the settings stay). The smoke test runs the activation again to check the pages.
tests_add_filter(
	'init',
	function () {
		\WP_Express_Checkout\Main::activate( false );
	},
	99
);

//Start the WordPress test environment.
require "{$_tests_dir}/includes/bootstrap.php";

//Test helpers.
require_once __DIR__ . '/includes/class-wpec-test-factory.php';
require_once __DIR__ . '/includes/class-wpec-ajax-test-case.php';
