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

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

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
		?>
        <link rel="stylesheet" href="<?php echo $styleFrontEnd ?>" />

        <script type="text/javascript">
			var ppecFrontVars = <?php echo json_encode( $localVars ) ?>
        </script>

        <script src="<?php echo $scriptFrontEnd ?>"></script>

        <style>
			.wpec-modal-overlay {
                pointer-events: none;
            }
            .wpec-modal-content {
                pointer-events: auto !important;
            }
        </style>
	</head>
	<body>
		<?php
		$class_main_inst = WP_Express_Checkout\Main::get_instance();

		//The product exists. Lets render the payment box.
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

		$output = '';

		$args = array(
			'name' => get_the_title( $post_id ),
			'price' => $product->get_price(),
			'shipping' => $product->get_shipping(),
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

		$args = shortcode_atts(
				array(
			'name' => 'Item Name',
			'price' => 0,
			'shipping' => 0,
			'shipping_enable' => 0,
			'tax' => 0,
			'quantity' => 1,
			'url' => '',
			'product_id' => '',
			'thumbnail_url' => '',
			'custom_amount' => 0,
			'custom_quantity' => 0,
			'currency' => $class_main_inst->get_setting( 'currency_code' ), // Maybe useless option, the shortcode doesn't send this parameter.
			'btn_shape' => $class_main_inst->get_setting( 'btn_shape' ),
			'btn_type' => $btn_type ? $btn_type : $class_main_inst->get_setting( 'btn_type' ),
			'btn_height' => !empty( $btn_sizes[$btn_height] ) ? $btn_sizes[$btn_height] : 25,
			'btn_width' => $class_main_inst->get_setting( 'btn_width' ) !== false ? $class_main_inst->get_setting( 'btn_width' ) : 0,
			'btn_layout' => $class_main_inst->get_setting( 'btn_layout' ),
			'btn_color' => $class_main_inst->get_setting( 'btn_color' ),
			'coupons_enabled' => $class_main_inst->get_setting( 'coupons_enabled' ),
			'button_text' => $button_text ? $button_text : $class_main_inst->get_setting( 'button_text' ),
			'use_modal' => !isset( $atts['modal'] ) ? $class_main_inst->get_setting( 'use_modal' ) : $atts['modal'],
			'thank_you_url' => $thank_you_url ? $thank_you_url : $class_main_inst->get_setting( 'thank_you_url' ),
			'variations' => array(),
			'stock_enabled' => $product->is_stock_control_enabled(),
			'stock_items' => $product->get_stock_items(),
			'price_class' => isset( $atts['price_class'] ) ? $atts['price_class'] : 'wpec-price-' . substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 10 ),
				), $args
		);

		extract( $args );

		if ( $stock_enabled && empty( $stock_items ) ) {
			return '<div class="wpec-out-of-stock">' . esc_html( 'Out of stock', 'wp-express-checkout' ) . '</div>';
		}

		// The button ID.
		$button_id = 'paypal_button_0';

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.

		$trans_data = array(
			'price' => $price,
			'currency' => $currency,
			'quantity' => $quantity,
			'tax' => $tax,
			'shipping' => $shipping,
			'shipping_enable' => $shipping_enable,
			'url' => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount' => $custom_amount,
			'product_id' => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url' => $thank_you_url,
		);

		set_transient( $trans_name, $trans_data, WEEK_IN_SECONDS );

		$is_live = $class_main_inst->get_setting( 'is_live' );

		if ( $is_live ) {
			$env = 'production';
			$client_id = $class_main_inst->get_setting( 'live_client_id' );
		} else {
			$env = 'sandbox';
			$client_id = $class_main_inst->get_setting( 'sandbox_client_id' );
		}

		if ( empty( $client_id ) ) {
			$err_msg = sprintf( __( "Please enter %s Client ID in the settings.", 'wp-express-checkout' ), $env );
			$err = $this->show_err_msg( $err_msg, 'client-id' );
			return $err;
		}

		/* ---Output the main payment form --- */
		$payment_form = '';
		ob_start();
		?>
		<div style="position: relative;" class="wp-ppec-shortcode-container wpec-shortcode-container-product-<?php echo esc_attr( $product_id ); ?>" data-ppec-button-id="<?php echo esc_attr( $button_id ); ?>" data-price-class="<?php echo esc_attr( $price_class ); ?>">

			<div class="wp-ppec-overlay" data-ppec-button-id="<?php echo esc_attr( $button_id ); ?>">
				<div class="wp-ppec-spinner">
					<div></div>
					<div></div>
					<div></div>
					<div></div>
				</div>
			</div>

			<?php if ( $custom_amount ) { ?>
				<?php
				$step = pow( 10, -intval( $class_main_inst->get_setting( 'price_decimals_num' ) ) );
				$min = get_post_meta( $product_id, 'wpec_product_min_amount', true );
				?>
				<div class="wpec-custom-amount-section">
					<span class="wpec-custom-amount-label-field">
						<label><?php echo esc_html( sprintf( __( 'Enter Amount (%s): ', 'wp-express-checkout' ), $currency ) ); ?></label>
					</span>
					<span class="wpec-custom-amount-input-field">
						<input id="wp-ppec-custom-amount" data-ppec-button-id="<?php echo esc_attr( $button_id ); ?>" type="number" step="<?php echo esc_attr( $step ); ?>" name="custom-amount" class="wp-ppec-input wp-ppec-custom-amount" min="<?php echo esc_attr( $min ); ?>" value="<?php echo esc_attr( max( $price, $min ) ); ?>">
						<div class="wp-ppec-form-error-msg"></div>
					</span>
				</div>
			<?php } ?>

			<?php if ( $custom_quantity ) { ?>
				<div class="wpec-custom-number-input-wrapper">
					<label><?php esc_html_e( 'Quantity:', 'wp-express-checkout' ); ?></label>
					<div class="wpec-custom-number-input">
						<button data-action="decrement">
							<span>&minus;</span>
						</button>
						<input id="wp-ppec-custom-quantity" data-ppec-button-id="<?php echo esc_attr( $button_id ); ?>" type="number" name="custom-quantity" class="wp-ppec-input wp-ppec-custom-quantity" min="1" value="<?php echo esc_attr( $quantity ); ?>">
						<button data-action="increment">
							<span>+</span>
						</button>
					</div>
					<div class="wp-ppec-form-error-msg"></div>
				</div>
			<?php } ?>

			<?php do_action( 'wpec_payment_form_before_variations', $args, $button_id ); ?>

			<?php
			// Variations.
			if ( !empty( $variations['groups'] ) ) {
				?>
				<div class="wpec-product-variations-wrapper">
					<?php
					// we got variations for this product.
					foreach ( $variations['groups'] as $grp_id => $group ) {
						?>
						<div class="wpec-product-variations-cont">
							<label class="wpec-product-variations-label"><?php echo esc_attr( $group ); ?></label>
							<?php
							if ( !empty( $variations[$grp_id]['opts'] ) ) {
								// radio buttons output.
								foreach ( $variations[$grp_id]['names'] as $var_id => $var_name ) {
									?>
									<label class="wpec-product-variations-select-radio-label">
										<input class="wpec-product-variations-select-radio" data-wpec-variations-group-id="<?php echo esc_attr( $grp_id ); ?>" name="wpecVariations[<?php echo esc_attr( $button_id ); ?>][<?php echo esc_attr( $grp_id ); ?>][]" type="radio" value="<?php echo esc_attr( $var_id ); ?>" <?php checked( $var_id === 0 ); ?>>
										<?php echo esc_html( $var_name ); ?>
										<?php echo esc_html( WP_Express_Checkout\Utils::price_modifier( $variations[$grp_id]['prices'][$var_id], $currency ) ); ?>
									</label>
									<?php
								}
							} else {
								// drop-down output.
								?>
								<select class="wpec-product-variations-select" data-wpec-variations-group-id="<?php echo esc_attr( $grp_id ); ?>" name="wpecVariations[<?php echo esc_attr( $button_id ); ?>][<?php echo esc_attr( $grp_id ); ?>][]">
									<?php
									foreach ( $variations[$grp_id]['names'] as $var_id => $var_name ) {
										?>
										<option value="<?php echo esc_attr( $var_id ); ?>"><?php echo esc_html( $var_name ); ?> <?php echo esc_html( WP_Express_Checkout\Utils::price_modifier( $variations[$grp_id]['prices'][$var_id], $currency ) ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>

			<?php if ( $coupons_enabled ) { ?>
				<div class="wpec_product_coupon_input_container">
					<label class="wpec_product_coupon_field_label"><?php esc_html_e( 'Coupon Code:', 'wp-express-checkout' ); ?> </label>
					<div class="wpec_product_coupon_input_wrap">
						<input id="wpec-coupon-field-<?php echo esc_attr( $button_id ); ?>" class="wpec_product_coupon_field_input" type="text" name="wpec_coupon">
						<button id="wpec-redeem-coupon-btn-<?php echo esc_attr( $button_id ); ?>" type="button" class="wpec_coupon_apply_btn">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
							</svg>
							<?php esc_html_e( 'Apply', 'wp-express-checkout' ); ?>
						</button>
					</div>
					<div id="wpec-coupon-info-<?php echo esc_attr( $button_id ); ?>" class="wpec_product_coupon_info"></div>
				</div>
			<?php } ?>
			<?php
			/**
			 * Hide billing info for donation product type.
			 */
			$hide_billing_info = apply_filters( 'wpec_hide_billing_info_fields', $custom_amount );
			?>
			<div id="wpec_billing_<?php
			echo esc_attr( $button_id );
			echo $hide_billing_info ? '_hide' : '';
			?>" class="wpec_billing_container" style="display: none;">

				<div class="wpec_billing_user_info">
					<div class="wpec_billing_first_name">
						<label class="wpec_billing_first_name_label"><?php esc_html_e( 'First Name', 'wp-express-checkout' ); ?></label>
						<input id="wpec_billing_first_name-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_first_name_field_input wpec_required" type="text" name="wpec_billing_first_name"/>
						<div class="wp-ppec-form-error-msg"></div>
					</div>

					<div class="wpec_billing_last_name">
						<label class="wpec_billing_last_name_label"><?php esc_html_e( 'Last Name', 'wp-express-checkout' ); ?></label>
						<input id="wpec_billing_last_name-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_last_name_field_input wpec_required" type="text" name="wpec_billing_last_name"/>
						<div class="wp-ppec-form-error-msg"></div>
					</div>

					<div class="wpec_billing_email">
						<label class="wpec_billing_email_label"><?php esc_html_e( 'Email', 'wp-express-checkout' ); ?></label>
						<input id="wpec_billing_email-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_email_field_input wpec_required" type="email" name="wpec_billing_email"/>
						<div class="wp-ppec-form-error-msg"></div>
					</div>
				</div>

				<div class="wpec_address_wrap">

					<?php if ( $shipping_enable ) { ?>
						<label class="wpec_product_shipping_handle">
							<input class="wpec_product_shipping_enable" type="checkbox" checked="checked"> <?php esc_html_e( 'Same billing and shipping info', 'wp-express-checkout' ); ?>
						</label>
					<?php } ?>


					<div class="wpec_billing_address_container">

						<label class="wpec_billing_label"><?php esc_html_e( 'Billing Info', 'wp-express-checkout' ); ?></label>

						<div class="wpec_billing_address">
							<label class="wpec_billing_address_label"><?php esc_html_e( 'Address', 'wp-express-checkout' ); ?></label>
							<input id="wpec_billing_address-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_address_field_input wpec_required" type="text" name="wpec_billing_address"/>
							<div class="wp-ppec-form-error-msg"></div>
						</div>

						<div class="wpec_billing_city">
							<label class="wpec_billing_city_label"><?php esc_html_e( 'City', 'wp-express-checkout' ); ?></label>
							<input id="wpec_billing_city-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_city_field_input wpec_required" type="text" name="wpec_billing_city"/>
							<div class="wp-ppec-form-error-msg"></div>
						</div>

						<div class="wpec_billing_country">
							<label class="wpec_billing_country_label"><?php esc_html_e( 'Country', 'wp-express-checkout' ); ?></label>
							<select id="wpec_billing_country-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_country_field_input wpec_required" name="wpec_billing_country">
								<?php
								$countries = \WP_Express_Checkout\Utils::get_countries();
								foreach ( $countries as $code => $country ) {
									?>
									<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $country ); ?></option>
									<?php
								}
								?>
							</select>
							<div class="wp-ppec-form-error-msg"></div>
						</div>

						<div class="wpec_billing_state">
							<label class="wpec_billing_state_label"><?php esc_html_e( 'State', 'wp-express-checkout' ); ?></label>
							<input id="wpec_billing_state-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_state_field_input" type="text" name="wpec_billing_state"/>
							<div class="wp-ppec-form-error-msg"></div>
						</div>

						<div class="wpec_billing_postal_code">
							<label class="wpec_billing_postal_code_label"><?php esc_html_e( 'Postal Code', 'wp-express-checkout' ); ?></label>
							<input id="wpec_billing_postal_code-<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_postal_code_field_input" type="text" name="wpec_billing_postal_code"/>
							<div class="wp-ppec-form-error-msg"></div>
						</div>

					</div>

					<?php if ( $shipping_enable ) { ?>

						<div class="wpec_shipping_address_container" style="display:none;">

							<label class="wpec_shipping_label"><?php esc_html_e( 'Shipping Info', 'wp-express-checkout' ); ?></label>

							<div class="wpec_shipping_address">
								<label class="wpec_shipping_address_label"><?php esc_html_e( 'Address', 'wp-express-checkout' ); ?></label>
								<input id="wpec_shipping_address-<?php echo esc_attr( $button_id ); ?>" class="wpec_shipping_address_field_input wpec_required" type="text" name="wpec_shipping_address"/>
								<div class="wp-ppec-form-error-msg"></div>
							</div>

							<div class="wpec_shipping_city">
								<label class="wpec_shipping_city_label"><?php esc_html_e( 'City', 'wp-express-checkout' ); ?></label>
								<input id="wpec_shipping_city-<?php echo esc_attr( $button_id ); ?>" class="wpec_shipping_city_field_input wpec_required" type="text" name="wpec_shipping_city"/>
								<div class="wp-ppec-form-error-msg"></div>
							</div>

							<div class="wpec_shipping_country">
								<label class="wpec_shipping_country_label"><?php esc_html_e( 'Country', 'wp-express-checkout' ); ?></label>
								<select id="wpec_shipping_country-<?php echo esc_attr( $button_id ); ?>" class="wpec_shipping_country_field_input wpec_required" name="wpec_shipping_country">
									<?php
									$countries = \WP_Express_Checkout\Utils::get_countries();
									foreach ( $countries as $code => $country ) {
										?>
										<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $country ); ?></option>
										<?php
									}
									?>
								</select>
								<div class="wp-ppec-form-error-msg"></div>
							</div>

							<div class="wpec_shipping_state">
								<label class="wpec_shipping_state_label"><?php esc_html_e( 'State', 'wp-express-checkout' ); ?></label>
								<input id="wpec_shipping_state-<?php echo esc_attr( $button_id ); ?>" class="wpec_shipping_state_field_input" type="text" name="wpec_shipping_state"/>
								<div class="wp-ppec-form-error-msg"></div>
							</div>

							<div class="wpec_shipping_postal_code">
								<label class="wpec_shipping_postal_code_label"><?php esc_html_e( 'Postal Code', 'wp-express-checkout' ); ?></label>
								<input id="wpec_shipping_postal_code-<?php echo esc_attr( $button_id ); ?>" class="wpec_shipping_postal_code_field_input" type="text" name="wpec_shipping_postal_code"/>
								<div class="wp-ppec-form-error-msg"></div>
							</div>

						</div>

					<?php } ?>

				</div>

			</div>

			<div class = "wp-ppec-button-container">

				<?php if ( $class_main_inst->get_setting( 'tos_enabled' ) ) { ?>
					<div class="wpec_product_tos_input_container">
						<label class="wpec_product_tos_label">
							<input id="wpec-tos-<?php echo esc_attr( $button_id ); ?>" class="wpec_product_tos_input" type="checkbox"> <?php echo html_entity_decode( $class_main_inst->get_setting( 'tos_text' ) ); ?>
						</label>
						<div class="wp-ppec-form-error-msg"></div>
					</div>
				<?php } ?>

				<?php if ( $stock_enabled ) { ?>
					<label class="wpec_stock_items_label"><?php printf( __( 'Available Quantity: %d', 'wp-express-checkout' ), $stock_items ); ?></label>
				<?php } ?>

				<?php if ( $use_modal ) { ?>
					<div class="wpec-price-container <?php echo esc_attr( $price_class ); ?>">
						<?php echo WP_Express_Checkout\Shortcodes::get_instance()->generate_price_tag( $args ); ?>
					</div>
				<?php } ?>

				<div id="place-order-<?php
				echo esc_attr( $button_id );
				echo $hide_billing_info ? '_hide' : '';
				?>" style="display:none;">
					<button class="wpec-place-order-btn"><?php esc_html_e( 'Place Order', 'wp-express-checkout' ); ?></button>
				</div>

				<div id="<?php echo esc_attr( $button_id ); ?>" style="max-width:<?php echo esc_attr( $btn_width ? $btn_width . 'px;' : ''  ); ?>"></div>

				<div class="wpec-button-placeholder" style="display: none; border: 1px solid #E7E9EB; padding:1rem;">
					<i><?php esc_html_e( 'This is where the Express Checkout Button will show. View it on the front-end to see how it will look to your visitors', 'wp-express-checkout' ); ?></i>
				</div>

			</div>

		</div>		
		<?php
		$payment_form .= ob_get_clean();
		/* --- End of payment form output --- */
		?>
		<!--Payment Box with the payment form-->
		<div id="wpec-modal-<?php echo esc_attr( $button_id ); ?>" class="wpec-modal wpec-pointer-events-none wpec-modal-product-<?php echo esc_attr( $product_id ); ?>">

			<div class="wpec-modal-overlay"></div>

			<div class="wpec-modal-container">
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
								<?php echo wp_trim_words( get_the_content(), 30 ); ?>
							</div>
						</div>
					<?php } ?>
					<?php echo $payment_form; ?>
				</div>
			</div>
		</div>

		<?php
		$data = apply_filters( 'wpec_button_js_data', array(
			'id' => $button_id,
			'nonce' => wp_create_nonce( $button_id . $product_id ),
			'env' => $env,
			'client_id' => $client_id,
			'price' => $price,
			'quantity' => $quantity,
			'tax' => $tax,
			'shipping' => $shipping,
			'shipping_enable' => $shipping_enable,
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
			'btnStyle' => array(
				'height' => $btn_height,
				'shape' => $btn_shape,
				'label' => $btn_type,
				'color' => $btn_color,
				'layout' => $btn_layout,
			),
		) );

		echo '<script type="text/javascript">var wpec_' . $button_id . '_data=' . json_encode( $data ) . ';jQuery( function( $ ) {$( document ).on( "wpec_paypal_sdk_loaded", function() { new ppecHandler(wpec_' . $button_id . '_data) } );} );</script>';

		WP_Express_Checkout\Main::get_instance()->load_paypal_sdk();
		?>
	</body>
</html>