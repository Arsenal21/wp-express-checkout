<?php
/**
 * Endpoint tests: creates the test data on the test site. Run with: wp eval-file tests/e2e/setup-site.php
 * Prints a JSON line with the IDs the tests need (the last line of the output).
 */
require_once dirname( __DIR__ ) . '/phpunit/includes/class-wpec-test-factory.php';

//The plugin's activation (the Thank You and Shop pages, the default settings) already ran when the plugin was activated.
$settings = get_option( 'ppdg-settings' );

//Plugin settings: PayPal and Stripe in test mode with test credentials (the fake APIs don't check them, but the SDKs need them),
//manual checkout, coupons, the emails, debug logging (the log helps when a test fails).
WPEC_Test_Factory::update_settings(
	array(
		'enable_paypal_checkout'   => 1,
		'is_live'                  => 0,
		'sandbox_client_id'        => 'e2e-sandbox-client-id',
		'sandbox_secret_key'       => 'e2e-sandbox-secret',
		'enable_stripe_checkout'   => 1,
		'stripe_is_live'           => 0,
		'stripe_test_publishable_key' => 'pk_test_e2e',
		'stripe_test_secret_key'   => 'sk_test_e2e',
		'enable_manual_checkout'   => 1,
		'manual_checkout_btn_text' => 'Pay Manually',
		'enable_manual_checkout_buyer_instruction_email'   => 1,
		'enable_manual_checkout_seller_notification_email' => 1,
		'manual_checkout_seller_notification_email_address' => 'seller@example.com',
		'currency_code'            => 'USD',
		'price_decimals_num'       => '2',
		'coupons_enabled'          => 1,
		'send_buyer_email'         => 1,
		'buyer_from_email'         => 'E2E Shop <shop@example.com>',
		'buyer_email_subj'         => 'Thank you for the purchase',
		'buyer_email_body'         => "Dear {first_name} {last_name}\n\nYou ordered:\n{product_details}",
		'send_seller_email'        => 1,
		'notify_email_address'     => 'seller@example.com',
		'seller_email_subj'        => 'Notification of product sale',
		'seller_email_body'        => "Sale to {payer_email}\n\n{product_details}",
		'enable_debug_logging'     => 1,
		'use_modal'                => 0,
	)
);

//The file a downloadable product delivers.
$uploads = wp_upload_dir();
file_put_contents( $uploads['basedir'] . '/e2e-download.txt', 'E2E downloaded file content' );

$ids = array(
	//$10, the buyer can change the quantity.
	'basic'    => WPEC_Test_Factory::create_product( '10', array( 'ppec_product_custom_quantity' => 1 ), 'E2E Basic' ),
	//$20 + 10% tax + $5 shipping (+ $1 per item), a physical product.
	'physical' => WPEC_Test_Factory::create_product(
		'20',
		array(
			'wpec_product_tax'                   => '10',
			'wpec_product_shipping'              => '5',
			'wpec_product_shipping_per_quantity' => '1',
			'wpec_product_shipping_enable'       => 1,
			'ppec_product_custom_quantity'       => 1,
		),
		'E2E Physical'
	),
	//$5, 2 in stock.
	'stock'    => WPEC_Test_Factory::create_product(
		'5',
		array(
			'wpec_product_enable_stock'    => 1,
			'wpec_product_stock_items'     => 2,
			'ppec_product_custom_quantity' => 1,
		),
		'E2E Stock'
	),
	//A donation with a $5 minimum.
	'donation' => WPEC_Test_Factory::create_donation_product( 5, array(), 'E2E Donation' ),
	//$15 with a download link.
	'download' => WPEC_Test_Factory::create_product( '15', array( 'ppec_product_upload' => $uploads['baseurl'] . '/e2e-download.txt' ), 'E2E Download' ),
	//$10 with coupons turned off for the product.
	'nocoupon' => WPEC_Test_Factory::create_product( '10', array( 'wpec_product_coupons_setting' => '0' ), 'E2E No Coupons' ),
);
//$12 with a download link, and its own Thank You page that shows the downloads with [wpec_ty_downloads]
//(bought with the manual checkout: the order is pending until the admin marks it as paid).
$downloads_page = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'E2E downloads Thank You page',
		'post_content' => '[wpec_ty field=first_name] [wpec_ty_downloads]',
	)
);
$ids['manualdl'] =WPEC_Test_Factory::create_product(
	'12',
	array(
		'ppec_product_upload'        => $uploads['baseurl'] . '/e2e-download.txt',
		'wpec_product_thankyou_page' => get_permalink( $downloads_page ),
	),
	'E2E Manual Download'
);

//$10, Size: Small (+$0) or Large (+$5).
$ids['variations'] = WPEC_Test_Factory::create_product( '10', array(), 'E2E Variations' );
WPEC_Test_Factory::add_variations( $ids['variations'], array( 'Size' => array( 'Small' => 0, 'Large' => 5 ) ) );

$ids['coupons'] = array(
	'TENOFF'  => WPEC_Test_Factory::create_coupon( 'TENOFF', 10, 'perc' ),
	'FREE100' => WPEC_Test_Factory::create_coupon( 'FREE100', 100, 'perc' ),
	'ONCE'    => WPEC_Test_Factory::create_coupon( 'ONCE', 1, 'fixed', array( 'red_limit' => '1' ) ),
	'ONLYDL'  => WPEC_Test_Factory::create_coupon(
		'ONLYDL',
		50,
		'perc',
		array(
			'only_for_allowed_products' => 1,
			'allowed_products'          => array( (string) $ids['download'] ),
		)
	),
);

//A page with the product shortcode for each product.
$ids['pages'] = array();
foreach ( $ids as $key => $product_id ) {
	if ( is_int( $product_id ) ) {
		$ids['pages'][ $key ] = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'E2E page ' . $key,
				'post_content' => '[wp_express_checkout product_id="' . $product_id . '"]',
			)
		);
	}
}

$ids['thank_you_page']           = (int) $settings['thank_you_page_id'];
$ids['downloads_thank_you_page'] = $downloads_page;

echo wp_json_encode( $ids ) . "\n";
