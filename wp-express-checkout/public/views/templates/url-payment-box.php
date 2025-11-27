<?php
/**
 * URL payment box template
 *
 * @package wp-express-checkout
 */

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Shortcodes;

$is_paypal_checkout_enabled = Main::get_instance()->get_setting('enable_paypal_checkout');
$is_stripe_checkout_enabled = Main::get_instance()->get_setting('enable_stripe_checkout');
$is_manual_checkout_enabled = Main::get_instance()->get_setting('enable_manual_checkout');

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="utf-8">
		<?php
		$url_payment_box_title = apply_filters( 'wpec_url_payment_box_title', get_the_title( $product_id ) );
		?>
		<title><?php echo esc_html( $url_payment_box_title ); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
		global $wp_scripts;

		$wp_scripts->print_scripts( "jquery" );

		$min = ( defined( 'WPEC_LOAD_NON_MINIFIED' ) && WPEC_LOAD_NON_MINIFIED ) ? '' : '.min';

		$scriptFrontEnd = WPEC_PLUGIN_URL . "/assets/js/public{$min}.js";
		$styleFrontEnd = WPEC_PLUGIN_URL . "/assets/css/public{$min}.css";
		$localVars = array(
			'str' => array(
				'errorOccurred' => __( 'Error occurred', 'wp-express-checkout' ),
				'paymentFor' => __( 'Payment for', 'wp-express-checkout' ),
				'enterQuantity' => __( 'Please enter a valid quantity', 'wp-express-checkout' ),
				'stockErr' => __( 'You cannot order more items than available: %d', 'wp-express-checkout' ),
				'enterAmount' => __( 'Please enter a valid amount', 'wp-express-checkout' ),
				'acceptTos' => __( 'Please accept the terms and conditions', 'wp-express-checkout' ),
				'paymentCompleted' => __( 'Payment Completed', 'wp-express-checkout' ),
				'redirectMsg' => __( 'You are now being redirected to the order summary page.', 'wp-express-checkout' ),
				'strRemoveCoupon' => __( 'Remove coupon', 'wp-express-checkout' ),
				'strRemove' => __( 'Remove', 'wp-express-checkout' ),
				'required' => __( 'This field is required', 'wp-express-checkout' ),
			),
			'ajaxUrl' => get_admin_url() . 'admin-ajax.php',
		);
		//Allow other plugins to add their own local vars
		$localVars = apply_filters('wpec_url_payment_box_script_local_vars', $localVars );
		?>
        <link rel="stylesheet" href="<?php echo $styleFrontEnd ?>" />

        <script type="text/javascript">
			var ppecFrontVars = <?php echo json_encode( $localVars ) ?>;
        </script>
        <script src="<?php echo $scriptFrontEnd ?>"></script>

        <?php if (!empty($is_paypal_checkout_enabled)) {
	        $wpec_create_order_vars = array(
		        'nonce' => wp_create_nonce('wpec-create-order-js-ajax-nonce'),
	        );
	        $wpec_on_approve_vars = array(
		        'nonce' => wp_create_nonce('wpec-onapprove-js-ajax-nonce'),
		        'return_url' => Main::get_instance()->get_setting( 'thank_you_url' ),
		        'txn_success_message' => __('Transaction completed successfully!', 'wp-express-checkout'),
		        'txn_success_extra_msg' => __('Feel free to browse our site further for your next purchase.', 'wp-express-checkout'),
	        );
        ?>
        <script type="text/javascript">
            const wpec_create_order_vars = <?php echo json_encode( $wpec_create_order_vars ) ?>;
            const wpec_on_approve_vars = <?php echo json_encode( $wpec_on_approve_vars ) ?>;
        </script>
        <script src="<?php echo WPEC_PLUGIN_URL . "/assets/js/wpec-paypal.js" ?>"></script>
        <?php } ?>

		<?php if (!empty($is_stripe_checkout_enabled)) {
            $wpec_stripe_frontend_vars = array(
	            'nonce' => wp_create_nonce('wpec-stripe-create-order-ajax-nonce'),
            );
        ?>
        <link rel="stylesheet" href="<?php echo WPEC_PLUGIN_URL . "/assets/css/wpec-stripe-related.css" ?>" />
        <script type="text/javascript">
            const wpec_stripe_frontend_vars = <?php echo json_encode( $wpec_stripe_frontend_vars ) ?>;
        </script>
        <script src="<?php echo WPEC_PLUGIN_URL . "/assets/js/wpec-stripe.js" ?>"></script>
        <?php } ?>

		<?php if (!empty($is_manual_checkout_enabled)) { ?>
        <script src="<?php echo WPEC_PLUGIN_URL . "/assets/js/wpec-manual-checkout.js" ?>"></script>
        <?php } ?>

        <style>
			.wpec-modal-overlay {
                pointer-events: none;
            }
            .wpec-modal-content {
                pointer-events: auto !important;
            }
        </style>
		<?php 
		//Trigger action to allow other plugins to load their scripts
		do_action( 'wpec_url_payment_box_before_head_close', $product_id );
		?>
	</head>
	<body>
		<?php
		//Trigger action hook
		do_action( 'wpec_url_payment_box_after_body_open', $product_id );

		$class_main_inst = WP_Express_Checkout\Main::get_instance();
		//$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();

		//The product exists. Lets create the product object.
		try {
			$product = WP_Express_Checkout\Products::retrieve( intval( $product_id ) );
		} catch (Exception $exc) {
			wp_die( $exc->getMessage() );
		}

		$atts = array(
			'product_id' => $product_id,
		);

		$post_id = intval( $atts['product_id'] );
		$post = get_post( $post_id );

		$quantity = $product->get_quantity();
		$url = $product->get_download_url();
		$button_text = $product->get_button_text();
		$thank_you_url = !empty( $atts['thank_you_url'] ) ? $atts['thank_you_url'] : $product->get_thank_you_url();
		$btn_type = $product->get_button_type();
		$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
		$btn_height = $class_main_inst->get_setting( 'btn_height' );

		$sc_args = array(
			'name' => get_the_title( $post_id ),
			'price' => $product->get_price(),
			'shipping' => $product->get_shipping(),
			'shipping_per_quantity' => $product->get_shipping_per_quantity(),
			'shipping_enable' => $product->is_physical(),
			'tax' => $product->get_tax(),
			'custom_amount' => 'donation' === $product->get_type(), // Temporary, until we remove custom_amount parameter.
			'quantity' => max( intval( $quantity ), 1 ),
			'custom_quantity' => $product->is_custom_quantity(),
			'url' => base64_encode( $url ),
			'product_id' => $post_id,
			'thumbnail_url' => $product->get_thumbnail_url(),
			'coupons_enabled' => $product->get_coupons_setting(),
			'variations' => $product->get_variations()
		);

		$sc_args = shortcode_atts(
			array(
			'name' => 'Item Name',
			'price' => 0,
			'shipping' => 0,
			'shipping_per_quantity' => 0,
			'shipping_enable' => 0,
			'is_digital_product' => $product->is_digital_product(),
			'tax' => 0,
			'quantity' => 1,
			'url' => '',
			'product_id' => '',
			'thumbnail_url' => '',
			'custom_amount' => 0,
			'custom_quantity' => 0,
			'currency' => $class_main_inst->get_setting( 'currency_code' ), // Maybe useless option, the shortcode doesn't send this parameter.
			'btn_width' => $class_main_inst->get_setting( 'btn_width' ) !== false ? $class_main_inst->get_setting( 'btn_width' ) : 0,
            'coupons_enabled' => $class_main_inst->get_setting( 'coupons_enabled' ),
			'button_text' => $button_text ? $button_text : $class_main_inst->get_setting( 'button_text' ), // modal trigger button text
			'use_modal' => !isset( $atts['modal'] ) ? $class_main_inst->get_setting( 'use_modal' ) : $atts['modal'],
			'thank_you_url' => $thank_you_url ? $thank_you_url : $class_main_inst->get_setting( 'thank_you_url' ),
			'variations' => array(),
			'stock_enabled' => $product->is_stock_control_enabled(),
			'stock_items' => $product->get_stock_items(),
			'price_class' => isset( $atts['price_class'] ) ? $atts['price_class'] : 'wpec-price-' . substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 10 ),
				), $sc_args
		);

		extract( $sc_args );

		if ( $stock_enabled && empty( $stock_items ) ) {
			wp_die( '<div class="wpec-out-of-stock">' . esc_html( 'Out of stock', 'wp-express-checkout' ) . '</div>' );
		}

		$shortcode_id = 'wp_express_checkout_0';

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.

		$trans_data = array(
			'price' => $price,
			'currency' => $currency,
			'quantity' => $quantity,
			'tax' => $tax,
			'shipping' => $shipping,
			'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_enable' => $shipping_enable,
			'url' => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount' => $custom_amount,
			'product_id' => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url' => $thank_you_url,
		);

		set_transient( $trans_name, $trans_data, WEEK_IN_SECONDS );

		$data = apply_filters( 'wpec_js_data', array(
			'id' => $shortcode_id,
			'nonce' => wp_create_nonce($shortcode_id . $product_id),
			'price' => $price,
			'quantity' => $quantity,
			'tax' => $tax,
			'shipping' => $shipping,
			'shipping_enable' => $shipping_enable,
			'shipping_per_quantity' => $shipping_per_quantity,
			'dec_num' => intval( $class_main_inst->get_setting( 'price_decimals_num' ) ),
			'thousand_sep' => $class_main_inst->get_setting( 'price_thousand_sep' ),
			'dec_sep' => $class_main_inst->get_setting( 'price_decimal_sep' ),
			'curr_pos' => $class_main_inst->get_setting( 'price_currency_pos' ),
			'tos_enabled' => $class_main_inst->get_setting( 'tos_enabled' ),
			'custom_quantity' => $custom_quantity,
			'custom_amount' => $custom_amount,
			'currency' => $currency,
			'currency_symbol' => !empty( $class_main_inst->get_setting( 'currency_symbol' ) ) ? $class_main_inst->get_setting( 'currency_symbol' ) : $currency,
			'coupons_enabled' => $coupons_enabled,
			'product_id' => $product_id,
			'name' => $name,
			'stock_enabled' => $stock_enabled,
			'stock_items' => $stock_items,
			'variations' => $variations,
        ) , $sc_args);

		?>
        <script type="text/javascript">
            document.addEventListener( "DOMContentLoaded", function() {
                window['<?php echo esc_js($shortcode_id) ?>'] = new ppecHandler(<?php echo json_encode($data) ?>);
            });
        </script>
		<?php

        // for paypal
		if (!empty($is_paypal_checkout_enabled)){
			$paypal_button_id = 'paypal_button_0';
			echo Shortcodes::get_instance()->generate_pp_express_checkout_button($paypal_button_id, $sc_args, $shortcode_id);
			WP_Express_Checkout\Main::get_instance()->load_paypal_sdk();
		}

        // for stripe
		$is_stripe_checkout_enabled = apply_filters('wpec_show_stripe_checkout_option_backward_compatible', $is_stripe_checkout_enabled, $product->get_type());  // TODO: For addon backward compatibility.
		if (!empty($is_stripe_checkout_enabled)){
			$stripe_button_id = 'stripe_button_0';
			echo Shortcodes::get_instance()->generate_stripe_express_checkout_button($stripe_button_id, $sc_args, $shortcode_id);
		}

        // for manual checkout
		if (!empty($is_manual_checkout_enabled) && $product->get_type() != 'subscription'){
			$manual_checkout_button_id = 'manual_checkout_button_0';
			echo Shortcodes::get_instance()->generate_manual_checkout_button($manual_checkout_button_id, $sc_args, $shortcode_id);
		}

		?>
		<!-- Render the payment box with the payment form -->
		<div id="wpec-modal-<?php echo esc_attr( $shortcode_id ); ?>" class="wpec-modal wpec-pointer-events-none wpec-modal-product-<?php echo esc_attr( $product_id ); ?>">

			<div class="wpec-modal-overlay"></div>

			<div class="wpec-modal-container wpec-pointer-events-all">
				<div class="wpec-modal-content">
					<!--Title-->
					<div class="wpec-modal-content-title">
						<p><?php echo esc_html( $url_payment_box_title ); ?></p>
					</div>
					<?php if ( !empty( $thumbnail_url ) ) { ?>
						<div class="wpec-modal-item-info-wrap">
							<div class="wpec-modal-item-thumbnail">
								<img width="150" height="150" src="<?php echo esc_url( $thumbnail_url ); ?>" class="attachment-thumbnail size-thumbnail wp-post-image" alt="">
							</div>
							<div class="wpec-modal-item-excerpt">
								<?php 
								$url_payment_box_prod_desc = wp_trim_words( $post->post_content, 55 );
								echo apply_filters( 'wpec_url_payment_box_product_description', $url_payment_box_prod_desc, $product_id); 
								?>
							</div>
						</div>
					<?php } ?>
					<?php 
					/* ---Load the template for the main payment form --- */
					$located = WP_Express_Checkout\Shortcodes::locate_template( 'payment-form.php' );
					if ( $located ) {
						require $located;
					}
					/* --- End of main payment form loading --- */
					?>
				</div>
			</div>
		</div>
		<!-- End of payment box rendering -->
		
		<?php

		//Trigger action hook
		do_action( 'wpec_url_payment_box_before_body_close', $product_id );
		?>
	</body>
</html>
