<?php

namespace WP_Express_Checkout;

class Self_Hooks_Handler {
	public function __construct() {
		// TODO: For addon backward compatibility.
		add_filter( 'wpec_button_js_data', array( $this, 'handle_button_js_data' ), 9999999 );

		// TODO: For addon backward compatibility.
		add_filter( 'wpec_show_stripe_checkout_option_backward_compatible', array( $this, 'handle_show_stripe_checkout_option_backward_compatible' ), 10, 2 );

		// TODO: For addon backward compatibility.
		add_filter( 'wpec_js_data', array( $this, 'handle_js_data_backward_compatible' ), 9999999, 2 );
	}

	public function handle_button_js_data( $data ) {
		if ( isset( $data['subscription']['recur_price'] ) ) {
			$data['price'] = $data['subscription']['recur_price'];
		}

		return $data;
	}

	public function handle_js_data_backward_compatible($data, $sc_args) {
		if ( empty( $sc_args['product_id'] ) ) {
			return $data;
		}

		$product = get_post( $data['product_id'] );

		if ( empty( $product ) || 'subscription' !== $product->wpec_product_type ) {
			return $data;
		}

		$data['subscription'] = array(
			'trial_price'       => $product->wpec_sub_trial_price,
			'trial_period'      => $product->wpec_sub_trial_period,
			'trial_period_type' => $product->wpec_sub_trial_period_type,
			'recur_price'       => $product->wpec_sub_recur_price,
			'recur_period'      => $product->wpec_sub_recur_period,
			'recur_period_type' => $product->wpec_sub_recur_period_type,
			'recur_count'       => $product->wpec_sub_recur_count,
			'recur_reattempt'   => $product->wpec_sub_recur_reattempt,
			'plan_id'           => $product->wpec_sub_plan_id,
			'plan_desc'         => $product->wpec_sub_plan_desc,
		);

		$data['price'] = $data['subscription']['recur_price'];

		return $data;
	}

	public function handle_show_stripe_checkout_option_backward_compatible($is_enabled, $product_type) {
		if ($product_type == 'subscription'){
			return false;
		}

		return $is_enabled;
	}
}
