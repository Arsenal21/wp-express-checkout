<?php
/**
 * Deprecated classes added here as aliases to actual ones to ensure backward
 * compatibility.
 *
 * @since 2.0.0
 */

class_alias( 'WP_Express_Checkout\Admin\Admin', 'WPEC_Admin' );
class_alias( 'WP_Express_Checkout\Admin\Products_Meta_Boxes', 'PPECProductsMetaboxes' );
class_alias( 'WP_Express_Checkout\Admin\Admin_Order_Summary_Table', 'WPEC_Admin_Order_Summary_Table' );
//class_alias( 'WP_Express_Checkout\Admin\Coupons_List', 'WPEC_Coupons_Table' );
class_alias( 'WP_Express_Checkout\Admin\Orders_List', 'WPEC_Order_List' );
class_alias( 'WP_Express_Checkout\Admin\Orders_Meta_Boxes', 'WPEC_Orders_Metaboxes' );
class_alias( 'WP_Express_Checkout\Blocks', 'WPEC_Blocks' );
class_alias( 'WP_Express_Checkout\Coupons', 'WPEC_Coupons_Admin' );
class_alias( 'WP_Express_Checkout\Debug\Logger', 'WPEC_Debug_Logger' );
class_alias( 'WP_Express_Checkout\Init', 'WPEC_Init_Time_Tasks' );
class_alias( 'WP_Express_Checkout\Integrations', 'WPEC_Integrations' );
class_alias( 'WP_Express_Checkout\Main', 'WPEC_Main' );
class_alias( 'WP_Express_Checkout\Order', 'WPEC_Order' );
class_alias( 'WP_Express_Checkout\Order_Summary_Table', 'WPEC_Order_Summary_Table' );
class_alias( 'WP_Express_Checkout\Orders', 'OrdersWPEC' );
class_alias( 'WP_Express_Checkout\Payment_Processor_Free', 'WPEC_Process_IPN_Free' );
class_alias( 'WP_Express_Checkout\Payment_Processor', 'WPEC_Process_IPN' );
class_alias( 'WP_Express_Checkout\PayPal\Client', 'WPEC\PayPal\Client' );
class_alias( 'WP_Express_Checkout\PayPal\Request', 'WPEC\PayPal\Request' );
class_alias( 'WP_Express_Checkout\Post_Type_Content_Handler', 'WPEC_Post_Type_Content_Handler' );
class_alias( 'WP_Express_Checkout\Products', 'PPECProducts' );
class_alias( 'WP_Express_Checkout\Shortcodes', 'WPECShortcode' );
class_alias( 'WP_Express_Checkout\Utils', 'WPEC_Utility_Functions' );
class_alias( 'WP_Express_Checkout\Variations', 'WPEC_Variations' );
class_alias( 'WP_Express_Checkout\View_Downloads', 'WPEC_View_Download' );