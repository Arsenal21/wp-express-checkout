<?php
/**
 * Registers all deprecated class names in autoloader. On a call, it will load
 * deprecated.php file, where old names registered as aliases for actual names.
 *
 * @since 2.0.0
 */

namespace WP_Express_Checkout;

class Autoload_Deprecated {

	/**
	 * The class map array.
	 *
	 * @var array
	 */
	private static $class_map = array(
		''
	);

	/**
	 * Adds class map to the end of registered.
	 *
	 * Overrides already been mapped classes.
	 *
	 * @param array $class_map The class map array.
	 *                         Keys - class names, values - class file path.
	 */
	static function add_class_map( array $class_map ) {
		self::$class_map = array_merge( self::$class_map, $class_map );
	}

	/**
	 * Registers autoloader in the system.
	 */
	static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * Checks the class map and loads file if it has been mapped.
	 *
	 * @param string $class Class name.
	 */
	static function autoload( $class ) {

		if ( '\\' === $class[0] ) {
			$class = substr( $class, 1 );
		}

		if ( isset( self::$class_map[ $class ] ) && is_file( self::$class_map[ $class ] ) ) {
			require self::$class_map[ $class ];
		}
	}
}

Autoload_Deprecated::add_class_map( array(
    'OrdersWPEC' => __DIR__ . '/deprecated.php',
    'PPECProducts' => __DIR__ . '/deprecated.php',
    'PPECProductsMetaboxes' => __DIR__ . '/deprecated.php',
    'WPECShortcode' => __DIR__ . '/deprecated.php',
    'WPEC_Admin' => __DIR__ . '/deprecated.php',
    'WPEC_Admin_Order_Summary_Table' => __DIR__ . '/deprecated.php',
    'WPEC_Blocks' => __DIR__ . '/deprecated.php',
    'WPEC_Coupons_Admin' => __DIR__ . '/deprecated.php',
    //'WPEC_Coupons_Table' => __DIR__ . '/deprecated.php',
    'WPEC_Debug_Logger' => __DIR__ . '/deprecated.php',
    'WPEC_Init_Time_Tasks' => __DIR__ . '/deprecated.php',
    'WPEC_Integrations' => __DIR__ . '/deprecated.php',
    'WPEC_Main' => __DIR__ . '/deprecated.php',
    'WPEC_Order' => __DIR__ . '/deprecated.php',
    'WPEC_Order_List' => __DIR__ . '/deprecated.php',
    'WPEC_Order_Summary_Table' => __DIR__ . '/deprecated.php',
    'WPEC_Orders_Meta_Boxes' => __DIR__ . '/deprecated.php',
    'WPEC_Post_Type_Content_Handler' => __DIR__ . '/deprecated.php',
    'WPEC_Process_IPN' => __DIR__ . '/deprecated.php',
    'WPEC_Process_IPN_Free' => __DIR__ . '/deprecated.php',
    'WPEC_Utility_Functions' => __DIR__ . '/deprecated.php',
    'WPEC_Variations' => __DIR__ . '/deprecated.php',
    'WPEC_View_Download' => __DIR__ . '/deprecated.php',
) );

Autoload_Deprecated::register();
