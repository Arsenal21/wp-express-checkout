<?php
/**
 * Variables from parent scope:
 *
 * @var string $button_id
 * @var bool $is_digital_product
 * @var bool $shipping_enable
 * @var array $args
 */

$manual_checkout_instructions = \WP_Express_Checkout\Main::get_instance()->get_setting( 'manual_checkout_instructions' );
$manual_checkout_btn_text     = \WP_Express_Checkout\Main::get_instance()->get_setting( 'manual_checkout_btn_text' );

$manual_checkout_hide_country_field = \WP_Express_Checkout\Main::get_instance()->get_setting( 'manual_checkout_hide_country_field' );

?>
<div id="wpec-manual-checkout-section-<?php echo esc_attr( $button_id ); ?>">
    <form class="wpec-manual-checkout-form" id="wpec-manual-checkout-form-<?php echo esc_attr( $button_id ); ?>" style="display:none;">
        <?php if ( ! empty( $manual_checkout_instructions ) ) {
            echo wpautop(wp_kses_post( $manual_checkout_instructions ));
        } ?>

        <div id="" class="wpec_billing_container">
            <div class="wpec_billing_user_info">
                <div class="wpec_billing_first_name">
                    <label class="wpec_billing_first_name_label"><?php esc_html_e( 'First Name', 'wp-express-checkout' ); ?></label>
                    <input id="wpec_billing_first_name-<?php echo esc_attr( $button_id ); ?>"
                           class="wpec_billing_first_name_field_input wpec_required" type="text"
                           name="wpec_billing_first_name"/>
                    <div class="wp-ppec-form-error-msg"></div>
                </div>

                <div class="wpec_billing_last_name">
                    <label class="wpec_billing_last_name_label"><?php esc_html_e( 'Last Name', 'wp-express-checkout' ); ?></label>
                    <input id="wpec_billing_last_name-<?php echo esc_attr( $button_id ); ?>"
                           class="wpec_billing_last_name_field_input wpec_required" type="text"
                           name="wpec_billing_last_name"/>
                    <div class="wp-ppec-form-error-msg"></div>
                </div>

                <div class="wpec_billing_email">
                    <label class="wpec_billing_email_label"><?php esc_html_e( 'Email', 'wp-express-checkout' ); ?></label>
                    <input id="wpec_billing_email-<?php echo esc_attr( $button_id ); ?>"
                           class="wpec_billing_email_field_input wpec_required"
                           type="email"
                           name="wpec_billing_email"/>
                    <div class="wp-ppec-form-error-msg"></div>
                </div>
                <div class="wpec_billing_phone">
                    <label class="wpec_billing_phone_label"><?php esc_html_e( 'Phone', 'wp-express-checkout' ); ?></label>
                    <input class="wpec_billing_phone_field_input"
                           type="text"
                           name="wpec_billing_phone"/>
                    <div class="wp-ppec-form-error-msg"></div>
                </div>

            </div>
			<?php
			// Check if the product is digital or not. If it is not digital, then show the address fields. By default, hide the address fields for digital products.
			$hide_billing_address_fields = $is_digital_product;
			//Trigger a filter that can be used to override the hiding of the billing address fields.
			$hide_billing_address_fields = apply_filters( 'wpec_hide_billing_address_fields', $hide_billing_address_fields );

			if ( ! $hide_billing_address_fields ) {
				//Output/show the billing address fields.
				?>
                <div class="wpec_address_wrap">
					<?php if ( $shipping_enable ) { ?>
                        <label class="wpec_product_shipping_handle">
                            <input type="checkbox"
                                   class="wpec_same_billing_shipping_enable"
                                   name="wpec_same_billing_shipping_enable"
                                   checked="checked"> <?php esc_html_e( 'Same billing and shipping info', 'wp-express-checkout' ); ?>
                        </label>
					<?php } ?>

                    <div class="wpec_billing_address_container">
                        <label class="wpec_billing_label"><?php esc_html_e( 'Billing Info', 'wp-express-checkout' ); ?></label>

                        <div class="wpec_billing_address">
                            <label class="wpec_billing_address_label"><?php esc_html_e( 'Address', 'wp-express-checkout' ); ?></label>
                            <input id="wpec_billing_address-<?php echo esc_attr( $button_id ); ?>"
                                   class="wpec_billing_address_field_input wpec_required" type="text"
                                   name="wpec_billing_address"/>
                            <div class="wp-ppec-form-error-msg"></div>
                        </div>

                        <div class="wpec_billing_city">
                            <label class="wpec_billing_city_label"><?php esc_html_e( 'City', 'wp-express-checkout' ); ?></label>
                            <input id="wpec_billing_city-<?php echo esc_attr( $button_id ); ?>"
                                   class="wpec_billing_city_field_input wpec_required" type="text"
                                   name="wpec_billing_city"/>
                            <div class="wp-ppec-form-error-msg"></div>
                        </div>

                        <?php if(empty($manual_checkout_hide_country_field)){ ?>
                        <div class="wpec_billing_country">
                            <label class="wpec_billing_country_label"><?php esc_html_e( 'Country', 'wp-express-checkout' ); ?></label>
                            <select id="wpec_billing_country-<?php echo esc_attr( $button_id ); ?>"
                                    class="wpec_billing_country_field_input wpec_required" name="wpec_billing_country">
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
                        <?php } ?>

                        <div class="wpec_billing_state">
                            <label class="wpec_billing_state_label"><?php esc_html_e( 'State', 'wp-express-checkout' ); ?></label>
                            <input id="wpec_billing_state-<?php echo esc_attr( $button_id ); ?>"
                                   class="wpec_billing_state_field_input" type="text" name="wpec_billing_state"/>
                            <div class="wp-ppec-form-error-msg"></div>
                        </div>

                        <div class="wpec_billing_postal_code">
                            <label class="wpec_billing_postal_code_label"><?php esc_html_e( 'Postal Code', 'wp-express-checkout' ); ?></label>
                            <input id="wpec_billing_postal_code-<?php echo esc_attr( $button_id ); ?>"
                                   class="wpec_billing_postal_code_field_input" type="text"
                                   name="wpec_billing_postal_code"/>
                            <div class="wp-ppec-form-error-msg"></div>
                        </div>

                    </div>

					<?php if ( $shipping_enable ) { ?>

                        <div class="wpec_shipping_address_container" style="display:none;">

                            <label class="wpec_shipping_label"><?php esc_html_e( 'Shipping Info', 'wp-express-checkout' ); ?></label>

                            <div class="wpec_shipping_address">
                                <label class="wpec_shipping_address_label"><?php esc_html_e( 'Address', 'wp-express-checkout' ); ?></label>
                                <input id="wpec_shipping_address-<?php echo esc_attr( $button_id ); ?>"
                                       class="wpec_shipping_address_field_input wpec_required" type="text"
                                       name="wpec_shipping_address"/>
                                <div class="wp-ppec-form-error-msg"></div>
                            </div>

                            <div class="wpec_shipping_city">
                                <label class="wpec_shipping_city_label"><?php esc_html_e( 'City', 'wp-express-checkout' ); ?></label>
                                <input id="wpec_shipping_city-<?php echo esc_attr( $button_id ); ?>"
                                       class="wpec_shipping_city_field_input wpec_required" type="text"
                                       name="wpec_shipping_city"/>
                                <div class="wp-ppec-form-error-msg"></div>
                            </div>

	                        <?php if(empty($manual_checkout_hide_country_field)){ ?>
                            <div class="wpec_shipping_country">
                                <label class="wpec_shipping_country_label"><?php esc_html_e( 'Country', 'wp-express-checkout' ); ?></label>
                                <select id="wpec_shipping_country-<?php echo esc_attr( $button_id ); ?>"
                                        class="wpec_shipping_country_field_input wpec_required"
                                        name="wpec_shipping_country">
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
	                        <?php } ?>

                            <div class="wpec_shipping_state">
                                <label class="wpec_shipping_state_label"><?php esc_html_e( 'State', 'wp-express-checkout' ); ?></label>
                                <input id="wpec_shipping_state-<?php echo esc_attr( $button_id ); ?>"
                                       class="wpec_shipping_state_field_input" type="text" name="wpec_shipping_state"/>
                                <div class="wp-ppec-form-error-msg"></div>
                            </div>

                            <div class="wpec_shipping_postal_code">
                                <label class="wpec_shipping_postal_code_label"><?php esc_html_e( 'Postal Code', 'wp-express-checkout' ); ?></label>
                                <input id="wpec_shipping_postal_code-<?php echo esc_attr( $button_id ); ?>"
                                       class="wpec_shipping_postal_code_field_input" type="text"
                                       name="wpec_shipping_postal_code"/>
                                <div class="wp-ppec-form-error-msg"></div>
                            </div>

                        </div>

					<?php } ?>

                </div><!-- end of billing address fields wrap -->
				<?php
			}//end of digital product check
			?>
        </div><!-- end of billing info fields container -->
        <br>

        <?php do_action('wpec_before_manual_checkout_submit_button', $args, $button_id) ?>

        <div class="wpec-place-order-btn-section">
            <button class="wpec-place-order-btn" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <?php esc_html_e( 'Place Order', 'wp-express-checkout' ); ?></button>
            <button class="wpec-place-order-btn" type="reset" style="background-color: #e8e4e3; color: black"><?php esc_html_e( 'Cancel', 'wp-express-checkout' ); ?></button>
        </div>
    </form>
    <button class="wpec-place-order-btn" id="wpec-proceed-manual-checkout-<?php echo esc_attr( $button_id ) ?>">
        <?php esc_html_e( ! empty( $manual_checkout_btn_text ) ? $manual_checkout_btn_text : __( 'Proceed to Manual Checkout', 'wp-express-checkout' ) ); ?>
    </button>
</div>
