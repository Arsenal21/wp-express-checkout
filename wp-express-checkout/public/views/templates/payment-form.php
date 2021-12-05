<?php
/**
 * Payment form template
 *
 * @package wp-express-checkout
 */
?>

<div style="position: relative;" class="wp-ppec-shortcode-container wpec-shortcode-container-product-<?php echo esc_attr( $product_id ); ?>" data-ppec-button-id="<?php echo esc_attr( $button_id ); ?>">

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
		$step = pow( 10, -intval( $this->ppdg->get_setting( 'price_decimals_num' ) ) );
		$min  = get_post_meta( $product_id, 'wpec_product_min_amount', true );
		?>
		<div class="wpec-custom-amount-section">
			<span class="wpec-custom-amount-label-field">
				<label><?php echo esc_html(  sprintf( __( 'Enter Amount (%s): ', 'wp-express-checkout' ), $currency ) ); ?></label>
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
	if ( ! empty( $variations['groups'] ) ) {
		?>
		<div class="wpec-product-variations-wrapper">
		<?php
		// we got variations for this product.
		foreach ( $variations['groups'] as $grp_id => $group ) {
			?>
			<div class="wpec-product-variations-cont">
				<label class="wpec-product-variations-label"><?php echo esc_attr( $group ); ?></label>
				<?php
				if ( ! empty( $variations[ $grp_id ]['opts'] ) ) {
					// radio buttons output.
					foreach ( $variations[ $grp_id ]['names'] as $var_id => $var_name ) {
						?>
						<label class="wpec-product-variations-select-radio-label">
							<input class="wpec-product-variations-select-radio" data-wpec-variations-group-id="<?php echo esc_attr( $grp_id ); ?>" name="wpecVariations[<?php echo esc_attr( $button_id ); ?>][<?php echo esc_attr( $grp_id ); ?>][]" type="radio" value="<?php echo esc_attr( $var_id ); ?>" <?php checked( $var_id === 0 ); ?>>
							<?php echo esc_html( $var_name ); ?>
							<?php echo esc_html( WP_Express_Checkout\Utils::price_modifier( $variations[ $grp_id ]['prices'][ $var_id ], $currency ) ); ?>
						</label>
					<?php
					}

				} else {
					// drop-down output.
					?>
					<select class="wpec-product-variations-select" data-wpec-variations-group-id="<?php echo esc_attr( $grp_id ); ?>" name="wpecVariations[<?php echo esc_attr( $button_id ); ?>][<?php echo esc_attr( $grp_id ); ?>][]">
					<?php
					foreach ( $variations[ $grp_id ]['names'] as $var_id => $var_name ) {
						?>
						<option value="<?php echo esc_attr( $var_id ); ?>"><?php echo esc_html( $var_name ); ?> <?php echo esc_html( WP_Express_Checkout\Utils::price_modifier( $variations[ $grp_id ]['prices'][ $var_id ], $currency ) ); ?></option>
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

	<div id="wpec_billing_<?php echo esc_attr( $button_id ); ?>" class="wpec_billing_container" style="display: none;">

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

		<?php if ( $this->ppdg->get_setting( 'tos_enabled' ) ) { ?>
			<div class="wpec_product_tos_input_container">
				<label class="wpec_product_tos_label">
					<input id="wpec-tos-<?php echo esc_attr( $button_id ); ?>" class="wpec_product_tos_input" type="checkbox"> <?php echo html_entity_decode( $this->ppdg->get_setting( 'tos_text' ) ); ?>
				</label>
				<div class="wp-ppec-form-error-msg"></div>
			</div>
		<?php } ?>

		<?php if ( $use_modal ) { ?>
			<div class="wpec-price-container">
				<?php echo WP_Express_Checkout\Shortcodes::get_instance()->generate_price_tag( $args ); ?>
			</div>
		<?php } ?>

		<div id="place-order-<?php echo esc_attr( $button_id ); ?>" style="display:none;">
			<button class="wpec-place-order-btn"><?php esc_html_e( 'Place Order', 'wp-express-checkout' ); ?></button>
		</div>

		<div id="<?php echo esc_attr( $button_id ); ?>" style="max-width:<?php echo esc_attr( $btn_width ? $btn_width . 'px;' : '' ); ?>"></div>

		<div class="wpec-button-placeholder" style="display: none; border: 1px solid #E7E9EB; padding:1rem;">
			<i><?php esc_html_e( 'This is where the Express Checkout Button will show. View it on the front-end to see how it will look to your visitors', 'wp-express-checkout' ); ?></i>
		</div>

	</div>

</div>
