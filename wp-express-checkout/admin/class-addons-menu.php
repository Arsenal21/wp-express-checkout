<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Debug\Logger;

class Addons_Admin_Menu {

	public static $instance = null;

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function render_addons_menu_page() {
		$output = "";

		echo '<div class="wrap">';
		echo '<h1>' . (__("WP Express Checkout Extensions", "wp-express-checkout")) . '</h1>';
	
		echo '<div id="poststuff"><div id="post-body">';
		?>
	
		<?php
		$addons_data = array();
	
		$addon_1 = array(
			"name"		 => __( "Subscription Payments Addon", 'wp-express-checkout' ),
			"thumbnail"	 => WPEC_PLUGIN_URL . "/admin/includes/images/wpec-subscriptions.png",
			"description"	 => __( "This addon allows you to charge subscriptions or recurring payments.", 'wp-express-checkout' ),
			"page_url"	 => "https://wp-express-checkout.com/subscription-payments-addon-wp-express-checkout/",
		);
		array_push( $addons_data, $addon_1 );

		$addon_2 = array(
			"name"		 => __( "Custom Fields Addon", 'wp-express-checkout' ),
			"thumbnail"	 => WPEC_PLUGIN_URL . "/admin/includes/images/wpec-custom-fields.png",
			"description"	 => __( "Collect information from customers before they checkout within the payment window.", 'wp-express-checkout' ),
			"page_url"	 => "https://wp-express-checkout.com/custom-fields-addon/",
		);
		array_push( $addons_data, $addon_2 );

		$addon_3 = array(
			"name"		 => __( "Reset Settings and Data", 'wp-express-checkout' ),
			"thumbnail"	 => WPEC_PLUGIN_URL . "/admin/includes/images/wpec-reset-settings.png",
			"description"	 => __( "Allows you to reset the entire settings configuration and data of the WP Express Checkout Plugin.", 'wp-express-checkout' ),
			"page_url"	 => "https://wp-express-checkout.com/wp-express-checkout-reset-settings-and-data/",
		);
		array_push( $addons_data, $addon_3 );

		$addon_4 = array(
			"name"		 => __( "WP eMember Integration", 'wp-express-checkout' ),
			"thumbnail"	 => WPEC_PLUGIN_URL . "/admin/includes/images/wpec-wp-emember-integration.png",
			"description"	 => __( "Integrate with the WP eMember plugin to accept membership payments.", 'wp-express-checkout' ),
			"page_url"	 => "https://www.tipsandtricks-hq.com/wordpress-membership/membership-payments-using-the-wp-express-checkout-plugin-1987",
		);
		array_push( $addons_data, $addon_4 );	

		$addon_5 = array(
			"name"		 => __( "Simple Membership Integration", 'wp-express-checkout' ),
			"thumbnail"	 => WPEC_PLUGIN_URL . "/admin/includes/images/wpec-simple-membership-integration.png",
			"description"	 => __( "Integrate with the Simple Membership plugin to accept membership payments.", 'wp-express-checkout' ),
			"page_url"	 => "https://wp-express-checkout.com/simple-membership-integration-with-wp-express-checkout-plugin/",
		);
		array_push( $addons_data, $addon_5 );

		$addon_6 = array(
			"name"		 => __( "MailChimp Integration", 'wp-express-checkout' ),
			"thumbnail"	 => WPEC_PLUGIN_URL . "/admin/includes/images/wpec-mailchimp.png",
			"description"	 => __( "Automatically add customers to your MailChimp list after a purchase.", 'wp-express-checkout' ),
			"page_url"	 => "https://wp-express-checkout.com/wp-express-checkout-mailchimp-integration/",
		);
		array_push( $addons_data, $addon_6 );		

	
		/* Show the addons list */
		foreach ( $addons_data as $addon ) {
			$output .= '<div class="wpec_addon_item_canvas">';
	
			$output .= '<div class="wpec_addon_item_thumb">';
	
			$img_src = $addon[ 'thumbnail' ];
			$output	 .= '<img src="' . $img_src . '" alt="' . $addon[ 'name' ] . '">';
			$output	 .= '</div>'; //end thumbnail
	
			$output	 .= '<div class="wpec_addon_item_body">';
			$output	 .= '<div class="wpec_addon_item_name">';
			$output	 .= '<a href="' . $addon[ 'page_url' ] . '" target="_blank">' . $addon[ 'name' ] . '</a>';
			$output	 .= '</div>'; //end name
	
			$output	 .= '<div class="wpec_addon_item_description">';
			$output	 .= $addon[ 'description' ];
			$output	 .= '</div>'; //end description
	
			$output	 .= '<div class="wpec_addon_item_details_link">';
			$output	 .= '<a href="' . $addon[ 'page_url' ] . '" class="wpec_addon_view_details" target="_blank">' . __( 'View Details', 'wp-express-checkout' ) . '</a>';
	
			$output	 .= '</div>'; //end detils link
			$output	 .= '</div>'; //end body
	
			$output .= '</div>'; //end canvas
		}
	
		echo $output;
	
		echo '</div></div>';//End of poststuff and post-body
		echo '</div>';//End of wrap
	}

}