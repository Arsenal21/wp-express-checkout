<?php
/**
 * Endpoint tests: sends the checkout requests to a running test site (with fake Stripe and PayPal APIs), then checks the database.
 * Run by the GitHub Actions "Plugin Tests" workflow (the "Endpoint tests" job sets up the site). Environment variables:
 * E2E_URL (example: http://127.0.0.1:8080), E2E_DB_HOST, E2E_DB_NAME, E2E_DB_USER, E2E_DB_PASS, E2E_IDS (the JSON setup-site.php printed),
 * E2E_DEBUG_LOG (optional: the site's wp-content/debug.log, checked for PHP errors at the end).
 *
 * A checkout in the browser does this, and the tests send the same requests:
 * - The product page shows the product (the shortcode saves the product's price data in a transient) with the nonces and the JS data.
 * - Stripe: the checkout session request (admin-ajax.php, wpec_stripe_create_checkout_session), then the buyer pays on Stripe
 *   (here the fake marks the session as paid) and Stripe sends the buyer back (/?wpec_process_stripe_ipn=1&ref_id=<session ID>).
 * - PayPal: the create order request (wpec_pp_create_order), the buyer approves in the PayPal popup, then the capture request
 *   (wpec_pp_capture_order); the plugin captures the order with the PayPal API and processes the payment.
 * - The free checkout (a 100% coupon, wpec_process_empty_payment) and the manual checkout (wpec_process_manual_checkout).
 * - The Thank You page (the redirect URL) and the download link.
 */
require_once dirname( __DIR__ ) . '/phpunit/includes/class-wpec-test-factory.php';

$url      = rtrim( getenv( 'E2E_URL' ), '/' );
$db       = new mysqli( getenv( 'E2E_DB_HOST' ), getenv( 'E2E_DB_USER' ), getenv( 'E2E_DB_PASS' ), getenv( 'E2E_DB_NAME' ), (int) ( getenv( 'E2E_DB_PORT' ) ? getenv( 'E2E_DB_PORT' ) : 3306 ) );
$ids      = json_decode( getenv( 'E2E_IDS' ), true );
$prefix   = 'wp_';
$failures = 0;
$known    = 0;

if ( ! is_array( $ids ) ) {
	echo "E2E_IDS is missing or isn't JSON (the last line setup-site.php prints).\n";
	exit( 1 );
}

//Known issues that aren't fixed (see "Known issues" in tests/TESTS.md). Their checks still run, but don't fail the tests:
//the output shows "KNOWN ISSUE (still happens)" or "KNOWN ISSUE NOW PASSES". The commit that fixes one removes it from this list,
//so its check becomes a normal check.
const KNOWN_ISSUES = array(
	'coupons-turned-off'            => 'Coupons turned off (in the settings, or for the product) are not checked on the server: the coupon check request accepts a valid code.',
);

// ---- Helpers ----

function check_known( $issue, $label, $condition, $details = '' ) {
	global $known;
	if ( ! isset( KNOWN_ISSUES[ $issue ] ) ) {
		return check( $label, $condition, $details );
	}
	$known++;
	if ( $condition ) {
		echo "KNOWN ISSUE NOW PASSES: {$label}\n    Remove '{$issue}' from KNOWN_ISSUES if it is fixed: " . KNOWN_ISSUES[ $issue ] . "\n";
	} else {
		echo "KNOWN ISSUE (still happens): {$label}\n    " . KNOWN_ISSUES[ $issue ] . "\n";
		if ( '' !== $details ) {
			echo '    ' . $details . "\n";
		}
	}
	return $condition;
}

function check( $label, $condition, $details = '' ) {
	global $failures;
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		$failures++;
		echo "FAIL: {$label}\n";
		if ( '' !== $details ) {
			echo '    ' . $details . "\n";
		}
	}
	return $condition;
}

function section( $title ) {
	echo "\n== {$title} ==\n";
}

/**
 * Sends a request (redirects are not followed).
 *
 * @return array The HTTP status code, the body and the Location header.
 */
function http_request( $method, $endpoint, $fields = null ) {
	global $url;
	$location = '';
	$curl     = curl_init( 0 === strpos( $endpoint, 'http' ) ? $endpoint : $url . $endpoint );
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_USERAGENT      => 'WPEC-E2E-Tests/1.0',
			CURLOPT_HEADERFUNCTION => function ( $curl, $header ) use ( &$location ) {
				if ( 0 === stripos( $header, 'Location:' ) ) {
					$location = trim( substr( $header, 9 ) );
				}
				return strlen( $header );
			},
		)
	);
	if ( null !== $fields ) {
		curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $fields ) );
	}
	$body   = (string) curl_exec( $curl );
	$status = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	unset( $curl );
	return array(
		'status'   => $status,
		'body'     => $body,
		'location' => $location,
	);
}

function ajax( $fields ) {
	return http_request( 'POST', '/wp-admin/admin-ajax.php', $fields );
}

function json_of( $response ) {
	$json = json_decode( $response['body'], true );
	return null === $json ? array() : $json;
}

/** The response text without the HTML, shortened (printed when a check fails). */
function response_text( $response ) {
	$text = trim( preg_replace( '/\s+/', ' ', strip_tags( $response['body'] ) ) );
	return 'HTTP ' . $response['status'] . ': ' . substr( $text, 0, 400 );
}

function contains( $haystack, $needle ) {
	return false !== strpos( (string) $haystack, (string) $needle );
}

function db_value( $sql, $types = '', $params = array() ) {
	global $db;
	$stmt = $db->prepare( $sql );
	if ( $types ) {
		$stmt->bind_param( $types, ...$params );
	}
	$stmt->execute();
	$row = $stmt->get_result()->fetch_row();
	return $row ? $row[0] : null;
}

function db_column( $sql, $types = '', $params = array() ) {
	global $db;
	$stmt = $db->prepare( $sql );
	if ( $types ) {
		$stmt->bind_param( $types, ...$params );
	}
	$stmt->execute();
	return array_map(
		function ( $row ) {
			return $row[0];
		},
		$stmt->get_result()->fetch_all()
	);
}

function db_write( $sql, $types, $params ) {
	global $db;
	$stmt = $db->prepare( $sql );
	$stmt->bind_param( $types, ...$params );
	$stmt->execute();
}

function option_value( $name ) {
	global $prefix;
	return db_value( "SELECT option_value FROM {$prefix}options WHERE option_name = ?", 's', array( $name ) );
}

function set_option_value( $name, $value ) {
	global $prefix;
	db_write( "UPDATE {$prefix}options SET option_value = ? WHERE option_name = ?", 'ss', array( $value, $name ) );
}

function post_meta( $post_id, $key ) {
	global $prefix;
	return db_value( "SELECT meta_value FROM {$prefix}postmeta WHERE post_id = ? AND meta_key = ?", 'is', array( $post_id, $key ) );
}

function set_post_meta( $post_id, $key, $value ) {
	global $prefix;
	db_write( "UPDATE {$prefix}postmeta SET meta_value = ? WHERE post_id = ? AND meta_key = ?", 'sis', array( $value, $post_id, $key ) );
}

/** The fake APIs' data (see tests/e2e/mu-plugin-e2e-fakes.php). */
function fakes() {
	$state = json_decode( (string) option_value( 'wpec_e2e_fakes' ), true );
	return is_array( $state ) ? $state : array( 'next' => 1, 'sessions' => array(), 'tax' => array(), 'orders' => array(), 'requests' => array() );
}

function save_fakes( $state ) {
	global $prefix;
	if ( null === option_value( 'wpec_e2e_fakes' ) ) {
		db_write( "INSERT INTO {$prefix}options (option_name, option_value, autoload) VALUES ('wpec_e2e_fakes', ?, 'off')", 's', array( json_encode( $state ) ) );
	} else {
		set_option_value( 'wpec_e2e_fakes', json_encode( $state ) );
	}
}

/** The number of requests the fake APIs have received (to check that a rejected request made no API call). */
function api_request_count( $api ) {
	return count(
		array_filter(
			fakes()['requests'],
			function ( $r ) use ( $api ) {
				return $api === $r['api'];
			}
		)
	);
}

/** The emails the site has sent (kept by the fake instead of being sent). */
function emails() {
	$emails = json_decode( (string) option_value( 'wpec_e2e_emails' ), true );
	return is_array( $emails ) ? $emails : array();
}

function emails_since( $count ) {
	return array_slice( emails(), $count );
}

function email_to( $emails, $to ) {
	foreach ( $emails as $email ) {
		if ( contains( $email['to'], $to ) ) {
			return $email;
		}
	}
	return null;
}

/** The order IDs, oldest first. */
function order_ids() {
	global $prefix;
	return array_map( 'intval', db_column( "SELECT ID FROM {$prefix}posts WHERE post_type = 'ppdgorder' ORDER BY ID" ) );
}

function new_order_ids( $before ) {
	return array_values( array_diff( order_ids(), $before ) );
}

/** An order, read from the database like the Order class reads it. */
function order( $order_id ) {
	$items = unserialize( (string) post_meta( $order_id, 'wpec_order_items' ) );
	$items = is_array( $items ) ? array_filter( $items ) : array();
	$data  = unserialize( (string) post_meta( $order_id, 'wpec_order_data' ) );
	$total = 0;
	foreach ( $items as $item ) {
		$total += (float) $item['price'] * (int) $item['quantity'];
	}
	return array(
		'id'          => $order_id,
		'status'      => (string) post_meta( $order_id, 'wpec_order_state' ),
		'gateway'     => (string) post_meta( $order_id, 'wpec_order_payment_gateway' ),
		'resource_id' => (string) post_meta( $order_id, 'wpec_order_resource_id' ),
		'capture_id'  => (string) post_meta( $order_id, 'wpec_order_capture_id' ),
		'currency'    => (string) post_meta( $order_id, 'wpec_currency' ),
		'email'       => (string) post_meta( $order_id, 'wpec_order_customer_email' ),
		'items'       => $items,
		'data'        => is_array( $data ) ? $data : array(),
		'total'       => number_format( max( 0, $total ), 2, '.', '' ),
	);
}

function order_item( $order, $type ) {
	foreach ( $order['items'] as $item ) {
		if ( $type === $item['type'] ) {
			return $item;
		}
	}
	return null;
}

/**
 * Opens a product's page and reads what the checkout scripts get: the JS data (ppecHandler) and the nonces.
 */
function product_page( $key ) {
	global $ids;
	$response = http_request( 'GET', '/?page_id=' . $ids['pages'][ $key ] );
	$page     = array(
		'response' => $response,
		'js_data'  => null,
		'nonces'   => array(),
	);
	if ( preg_match( '/new ppecHandler\((\{.*?\})\);/s', $response['body'], $m ) ) {
		$page['js_data'] = json_decode( $m[1], true );
	}
	foreach ( array( 'wpec_create_order_vars', 'wpec_on_approve_vars', 'wpec_stripe_frontend_vars' ) as $var ) {
		if ( preg_match( '/' . $var . ' = (\{.*?\});/s', $response['body'], $m ) ) {
			$page['nonces'][ $var ] = json_decode( $m[1], true )['nonce'];
		}
	}
	return $page;
}

/**
 * The purchase data the browser builds (ppecHandler.data after calcTotal()), from the page's JS data and what the buyer chose.
 *
 * @param array $choices quantity, amount (donations), variations, coupon (a coupon check response: code, discount, discountType).
 */
function purchase( $page, $choices = array() ) {
	$data             = $page['js_data'];
	$data['quantity'] = isset( $choices['quantity'] ) ? $choices['quantity'] : $data['quantity'];
	$data['orig_price'] = floatval( $data['price'] );
	if ( isset( $choices['amount'] ) ) {
		$data['price']      = $choices['amount'];
		$data['orig_price'] = $choices['amount'];
	}
	return WPEC_Test_Factory::apply_buyer_choices( $data, $choices );
}

/**
 * jQuery.post() sends nested objects as form fields, with true/false as text.
 */
function jquery_fields( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'jquery_fields', $value );
	}
	if ( true === $value ) {
		return 'true';
	}
	if ( false === $value ) {
		return 'false';
	}
	return null === $value ? '' : $value;
}

function payer( $email = 'buyer@example.com' ) {
	return array(
		'name'          => array(
			'given_name' => 'Jane',
			'surname'    => 'Buyer',
		),
		'email_address' => $email,
		'address'       => array(
			'address_line_1' => '1 Test Street',
			'admin_area_1'   => 'CA',
			'admin_area_2'   => 'Test City',
			'postal_code'    => '12345',
			'country_code'   => 'US',
		),
	);
}

// ---- The checkout requests ----

function stripe_session( $page, $wpec_data, $order_overrides = array() ) {
	$order_data = array_merge(
		array(
			'total'         => $wpec_data['total'],
			'quantity'      => $wpec_data['quantity'],
			'currency_code' => $wpec_data['currency'],
		),
		$order_overrides
	);
	return ajax(
		array(
			'action'    => 'wpec_stripe_create_checkout_session',
			'wpec_data' => json_encode( $wpec_data ),
			'data'      => json_encode( $order_data ),
			'nonce'     => $page['nonces']['wpec_stripe_frontend_vars'],
		)
	);
}

/** The checkout session ID from a session request's response (the redirect URL ends with it). */
function session_id_of( $response ) {
	$json = json_of( $response );
	return isset( $json['data']['redirect_url'] ) ? substr( $json['data']['redirect_url'], strrpos( $json['data']['redirect_url'], '/' ) + 1 ) : '';
}

/** Stripe sends the buyer back to the site after the payment. */
function stripe_return( $session_id ) {
	return http_request( 'GET', '/?wpec_process_stripe_ipn=1&ref_id=' . rawurlencode( $session_id ) );
}

function paypal_create( $page, $wpec_data, $order_data = null ) {
	return ajax(
		array(
			'action'    => 'wpec_pp_create_order',
			'data'      => json_encode( null === $order_data ? WPEC_Test_Factory::paypal_order_data( $wpec_data ) : $order_data ),
			'wpec_data' => json_encode( $wpec_data ),
			'_wpnonce'  => $page['nonces']['wpec_create_order_vars'],
		)
	);
}

function paypal_capture( $page, $wpec_data, $paypal_order_id ) {
	return ajax(
		array(
			'action'    => 'wpec_pp_capture_order',
			'data'      => json_encode( array( 'order_id' => $paypal_order_id ) ),
			'wpec_data' => json_encode( $wpec_data ),
			'_wpnonce'  => $page['nonces']['wpec_on_approve_vars'],
		)
	);
}

/** ppecHandler.processPayment(): the free checkout and the manual checkout. */
function process_payment( $action, $wpec_data, $payer = null ) {
	return ajax(
		array(
			'action'          => $action,
			'wp_ppdg_payment' => jquery_fields( array( 'payer' => null === $payer ? payer() : $payer ) ),
			'data'            => jquery_fields( $wpec_data ),
			'nonce'           => $wpec_data['nonce'],
		)
	);
}

function check_coupon( $product_id, $code ) {
	return json_of(
		ajax(
			array(
				'action'      => 'wpec_check_coupon',
				'product_id'  => $product_id,
				'coupon_code' => $code,
				'curr'        => 'USD',
			)
		)
	);
}

function coupon( $product_id, $code ) {
	$response = check_coupon( $product_id, $code );
	return array(
		'code'         => $response['code'],
		'discount'     => $response['discount'],
		'discountType' => $response['discountType'],
	);
}

/**
 * Checks a successful payment request's response (the redirect URL to the Thank You page) and returns the new order.
 */
function check_paid_order( $label, $before, $redirect_url, $expected ) {
	global $ids;
	$new = new_order_ids( $before );
	if ( ! check( "{$label}: one order is created", 1 === count( $new ), 'New orders: ' . json_encode( $new ) ) ) {
		return null;
	}
	$order = order( $new[0] );
	check( "{$label}: the order is {$expected['status']}", $expected['status'] === $order['status'], 'Status: ' . $order['status'] );
	check( "{$label}: the order total is {$expected['total']}", $expected['total'] === $order['total'], 'Total: ' . $order['total'] . ', items: ' . json_encode( $order['items'] ) );
	check( "{$label}: the payment gateway is {$expected['gateway']}", $expected['gateway'] === $order['gateway'], 'Gateway: ' . $order['gateway'] );
	check(
		"{$label}: redirected to the Thank You page with the order ID",
		contains( $redirect_url, 'page_id=' . $ids['thank_you_page'] ) && contains( $redirect_url, 'order_id=' . $order['id'] ) && contains( $redirect_url, '_wpnonce=' ),
		'Redirect: ' . $redirect_url
	);
	return $order;
}

// ---- The tests ----

//The tests can run again on the same site: reset what they change (the coupon redemptions, the stock).
foreach ( $ids['coupons'] as $coupon_id ) {
	set_post_meta( $coupon_id, 'wpec_coupon_red_count', '' );
}
set_post_meta( $ids['stock'], 'wpec_product_stock_items', '2' );

$tenoff_id = $ids['coupons']['TENOFF'];

section( 'Product page' );
$page = product_page( 'basic' );
check( 'The product page loads', 200 === $page['response']['status'], response_text( $page['response'] ) );
check( 'The product page has the checkout data and the nonces', is_array( $page['js_data'] ) && 3 === count( $page['nonces'] ), json_encode( $page['nonces'] ) );
check( 'The product page shows the PayPal, Stripe and manual checkout buttons', contains( $page['response']['body'], 'new WPECPayPalHandler(' ) && contains( $page['response']['body'], 'new WPECStripeHandler(' ) && contains( $page['response']['body'], 'new WPECManualCheckout(' ) );

section( 'Coupon check request' );
$response = check_coupon( $ids['basic'], 'tenoff' );
check( 'A valid coupon is accepted (the code in any case) with its discount', true === $response['success'] && 'TENOFF' === $response['code'] && '10' === $response['discount'] && 'perc' === $response['discountType'], json_encode( $response ) );
$response = check_coupon( $ids['basic'], 'NOSUCHCODE' );
check( 'An unknown coupon is rejected', false === $response['success'] && 'Coupon not found.' === $response['msg'], json_encode( $response ) );
$response = check_coupon( $ids['basic'], 'ONLYDL' );
check( 'A coupon only for another product is rejected', false === $response['success'] && 'Coupon is not allowed for this product.' === $response['msg'], json_encode( $response ) );
$response = check_coupon( $ids['download'], 'ONLYDL' );
check( 'A coupon only for this product is accepted', true === $response['success'], json_encode( $response ) );
$response = check_coupon( $ids['nocoupon'], 'TENOFF' );
check_known( 'coupons-turned-off', 'A coupon for a product with coupons turned off is rejected', false === $response['success'], json_encode( $response ) );

// ---- Stripe ----

section( 'Stripe: a purchase with a coupon' );
$page      = product_page( 'basic' );
$data      = purchase( $page, array( 'quantity' => 2, 'coupon' => coupon( $ids['basic'], 'TENOFF' ) ) );
$emails    = count( emails() );
$response  = stripe_session( $page, $data );
$json      = json_of( $response );
$session   = session_id_of( $response );
check( 'The checkout session request succeeds', ! empty( $json['success'] ) && '' !== $session, response_text( $response ) );
$fake_session = isset( fakes()['sessions'][ $session ] ) ? fakes()['sessions'][ $session ] : null;
check(
	'Stripe is asked to charge 2 x $9.00 in USD',
	$fake_session && 900 === $fake_session['e2e_line_items'][0]['price_data']['unit_amount'] && 2 === (int) $fake_session['e2e_line_items'][0]['quantity'] && 'usd' === $fake_session['currency'],
	json_encode( $fake_session ? $fake_session['e2e_line_items'] : null )
);
$before   = order_ids();
$response = stripe_return( $session );
$order    = check_paid_order( 'Stripe', $before, $response['location'], array( 'status' => 'paid', 'total' => '18.00', 'gateway' => 'stripe' ) );
if ( $order ) {
	check( 'Stripe: the order has the session ID and the charge ID', $session === $order['resource_id'] && 'ch_' . substr( $session, 3 ) === $order['capture_id'], json_encode( array( $order['resource_id'], $order['capture_id'] ) ) );
	check( 'Stripe: the order has the coupon discount', null !== order_item( $order, 'coupon' ) && -2.0 === (float) order_item( $order, 'coupon' )['price'], json_encode( $order['items'] ) );
	check( 'Stripe: the buyer email address is saved', 'stripe-buyer@example.com' === $order['email'], $order['email'] );
	check( 'Stripe: the coupon is redeemed', '1' === (string) post_meta( $tenoff_id, 'wpec_coupon_red_count' ), (string) post_meta( $tenoff_id, 'wpec_coupon_red_count' ) );
	$sent = emails_since( $emails );
	check( 'Stripe: the buyer and seller emails are sent', null !== email_to( $sent, 'stripe-buyer@example.com' ) && null !== email_to( $sent, 'seller@example.com' ), json_encode( array_column( $sent, 'to' ) ) );

	$thank_you = http_request( 'GET', $response['location'] );
	check( 'Stripe: the Thank You page shows the order', 200 === $thank_you['status'] && contains( $thank_you['body'], 'E2E Basic' ) && ! contains( $thank_you['body'], 'wpec-error-message' ), response_text( $thank_you ) );

	//A reload of the return page, or the back button (fixed after 2.5.5: it created another paid order).
	$emails = count( emails() );
	$before = order_ids();
	$again  = stripe_return( $session );
	check( 'Stripe: opening the return URL again says the order is already processed', contains( $again['body'], 'The order has captured already' ), response_text( $again ) );
	check( 'Stripe: opening the return URL again does not create another order', 0 === count( new_order_ids( $before ) ), 'New orders: ' . json_encode( new_order_ids( $before ) ) );
	check( 'Stripe: opening the return URL again does not send the emails again', 0 === count( emails_since( $emails ) ) );
	check( 'Stripe: opening the return URL again does not redeem the coupon again', '1' === (string) post_meta( $tenoff_id, 'wpec_coupon_red_count' ), (string) post_meta( $tenoff_id, 'wpec_coupon_red_count' ) );
}

section( 'Stripe: tax and shipping' );
$page     = product_page( 'physical' );
$data     = purchase( $page, array( 'quantity' => 2 ) );
$response = stripe_session( $page, $data );
$session  = session_id_of( $response );
$fake_session = isset( fakes()['sessions'][ $session ] ) ? fakes()['sessions'][ $session ] : null;
check(
	'Stripe is asked to charge 2 x $20.00 with a tax rate and $7.00 shipping',
	$fake_session && 2000 === $fake_session['e2e_line_items'][0]['price_data']['unit_amount'] && ! empty( $fake_session['e2e_line_items'][0]['tax_rates'] ) && 700 === $fake_session['total_details']['amount_shipping'],
	response_text( $response )
);
check( 'Stripe: the total is $51.00 (40 + 10% tax + 7 shipping)', $fake_session && 5100 === $fake_session['amount_total'], json_encode( $fake_session ? $fake_session['total_details'] : null ) );
$before   = order_ids();
$response = stripe_return( $session );
$order    = check_paid_order( 'Stripe with tax and shipping', $before, $response['location'], array( 'status' => 'paid', 'total' => '51.00', 'gateway' => 'stripe' ) );
if ( $order ) {
	check( 'Stripe: the order has the tax and the shipping', 4.0 === (float) order_item( $order, 'tax' )['price'] && 7.0 === (float) order_item( $order, 'shipping' )['price'], json_encode( $order['items'] ) );
}
$tax_rates = count( fakes()['tax'] );
stripe_session( $page, $data );
check( 'Stripe: the saved tax rate is used again (no new tax rate)', count( fakes()['tax'] ) === $tax_rates, json_encode( fakes()['tax'] ) );

section( 'Stripe: stock and donations' );
$page     = product_page( 'stock' );
$before   = order_ids();
$response = stripe_return( session_id_of( stripe_session( $page, purchase( $page, array( 'quantity' => 2 ) ) ) ) );
check_paid_order( 'Stripe stock product', $before, $response['location'], array( 'status' => 'paid', 'total' => '10.00', 'gateway' => 'stripe' ) );
check( 'Stripe: the stock goes from 2 to 0', '0' === (string) post_meta( $ids['stock'], 'wpec_product_stock_items' ), (string) post_meta( $ids['stock'], 'wpec_product_stock_items' ) );
check( 'The product page shows "Out of stock" and no buttons', contains( product_page( 'stock' )['response']['body'], 'Out of stock' ) && null === product_page( 'stock' )['js_data'] );
set_post_meta( $ids['stock'], 'wpec_product_stock_items', '2' );

$page     = product_page( 'donation' );
$before   = order_ids();
$response = stripe_return( session_id_of( stripe_session( $page, purchase( $page, array( 'amount' => 25 ) ) ) ) );
check_paid_order( 'Stripe donation', $before, $response['location'], array( 'status' => 'paid', 'total' => '25.00', 'gateway' => 'stripe' ) );
$stripe_calls = api_request_count( 'stripe' );
$response     = stripe_session( $page, purchase( $page, array( 'amount' => 1 ) ) );
check( 'Stripe: a donation below the $5 minimum is rejected', contains( json_of( $response )['data']['message'], 'less than the minimum donation amount' ), response_text( $response ) );
check( 'Stripe: the rejected request makes no Stripe API call', api_request_count( 'stripe' ) === $stripe_calls );

section( 'Stripe: rejected requests and returns' );
$page         = product_page( 'basic' );
$data         = purchase( $page );
$data['newPrice'] = 0.01;
$stripe_calls = api_request_count( 'stripe' );
$response     = stripe_session( $page, $data );
check( 'Stripe: a lower unit price is rejected (fixed in 2.5.5)', contains( json_of( $response )['data']['message'], 'Price validation failed' ), response_text( $response ) );
check( 'Stripe: no checkout session is created for it', api_request_count( 'stripe' ) === $stripe_calls );
$data             = purchase( $page );
$data['currency'] = 'JPY';
$response         = stripe_session( $page, $data, array( 'currency_code' => 'USD' ) );
check( 'Stripe: another currency in the charged data is rejected (fixed in 2.5.5)', contains( json_of( $response )['data']['message'], 'inconsistent' ), response_text( $response ) );
$page_wrong              = $page;
$page_wrong['nonces']['wpec_stripe_frontend_vars'] = 'wrong';
$response                = stripe_session( $page_wrong, purchase( $page ) );
check( 'Stripe: a wrong nonce is rejected', contains( json_of( $response )['data']['message'], 'Nonce Verification Failed' ), response_text( $response ) );

$session = session_id_of( stripe_session( $page, purchase( $page ) ) );
$fakes   = fakes();
$fakes['sessions'][ $session ]['payment_status'] = 'unpaid';
$fakes['sessions'][ $session ]['status']         = 'open';
save_fakes( $fakes );
$before   = order_ids();
$response = stripe_return( $session );
check( 'Stripe: returning from an unpaid session shows an error and creates no order', contains( $response['body'], 'been paid already' ) && 0 === count( new_order_ids( $before ) ), response_text( $response ) );
$before   = order_ids();
$response = stripe_return( 'cs_test_doesnotexist' );
check( 'Stripe: returning with an unknown session ID shows an error and creates no order', contains( $response['body'], 'Stripe payment data could not be processed' ) && 0 === count( new_order_ids( $before ) ), response_text( $response ) );

// ---- PayPal ----

section( 'PayPal: a purchase' );
$page     = product_page( 'basic' );
$data     = purchase( $page, array( 'quantity' => 3 ) );
$response = paypal_create( $page, $data );
$json     = json_of( $response );
check( 'PayPal: the create order request succeeds', ! empty( $json['success'] ) && ! empty( $json['order_id'] ), response_text( $response ) );
$paypal_order_id = isset( $json['order_id'] ) ? $json['order_id'] : '';
$fake_order      = isset( fakes()['orders'][ $paypal_order_id ] ) ? fakes()['orders'][ $paypal_order_id ] : null;
check( 'PayPal: the order sent to PayPal is 3 x $10.00 = $30.00', $fake_order && '30' === (string) $fake_order['purchase_units'][0]['amount']['value'] && 3 === (int) $fake_order['purchase_units'][0]['items'][0]['quantity'], json_encode( $fake_order ) );
$emails   = count( emails() );
$before   = order_ids();
$response = paypal_capture( $page, $data, $paypal_order_id );
$json     = json_of( $response );
$order    = check_paid_order( 'PayPal', $before, isset( $json['redirect_url'] ) ? $json['redirect_url'] : response_text( $response ), array( 'status' => 'paid', 'total' => '30.00', 'gateway' => 'paypal' ) );
if ( $order ) {
	check( 'PayPal: the order has the PayPal order ID and the capture ID', $paypal_order_id === $order['resource_id'] && 'CAPTURE-' . $paypal_order_id === $order['capture_id'], json_encode( array( $order['resource_id'], $order['capture_id'] ) ) );
	$sent = emails_since( $emails );
	check( 'PayPal: the buyer and seller emails are sent', null !== email_to( $sent, 'paypal-buyer@example.com' ) && null !== email_to( $sent, 'seller@example.com' ), json_encode( array_column( $sent, 'to' ) ) );
	$buyer_email = email_to( $sent, 'paypal-buyer@example.com' );
	check( 'PayPal: the buyer email has the merge tags replaced', $buyer_email && contains( $buyer_email['message'], 'Dear Jane PayPal' ) && contains( $buyer_email['message'], 'E2E Basic' ) && ! contains( $buyer_email['message'], '{' ), $buyer_email ? $buyer_email['message'] : '' );

	$thank_you = http_request( 'GET', $json['redirect_url'] );
	check( 'PayPal: the Thank You page shows the order', 200 === $thank_you['status'] && contains( $thank_you['body'], 'E2E Basic' ) && ! contains( $thank_you['body'], 'wpec-error-message' ), response_text( $thank_you ) );
}
$before   = order_ids();
$response = paypal_capture( $page, $data, $paypal_order_id );
check( 'PayPal: capturing the same order again fails and creates no order', contains( json_of( $response )['err_msg'], 'capture-order API call failed' ) && 0 === count( new_order_ids( $before ) ), response_text( $response ) );

section( 'PayPal: variations, coupons and donations' );
$page     = product_page( 'variations' );
$data     = purchase( $page, array( 'variations' => array( 1 ) ) );
$before   = order_ids();
$json     = json_of( paypal_capture( $page, $data, json_of( paypal_create( $page, $data ) )['order_id'] ) );
$order    = check_paid_order( 'PayPal with a variation', $before, isset( $json['redirect_url'] ) ? $json['redirect_url'] : json_encode( $json ), array( 'status' => 'paid', 'total' => '15.00', 'gateway' => 'paypal' ) );
if ( $order ) {
	check( 'PayPal: the order has the variation', null !== order_item( $order, 'variation' ) && 'Size - Large' === order_item( $order, 'variation' )['name'], json_encode( $order['items'] ) );
}

$once_id = $ids['coupons']['ONCE'];
$page    = product_page( 'basic' );
$data    = purchase( $page, array( 'coupon' => coupon( $ids['basic'], 'ONCE' ) ) );
$before  = order_ids();
$json    = json_of( paypal_capture( $page, $data, json_of( paypal_create( $page, $data ) )['order_id'] ) );
check_paid_order( 'PayPal with a $1 coupon', $before, isset( $json['redirect_url'] ) ? $json['redirect_url'] : json_encode( $json ), array( 'status' => 'paid', 'total' => '9.00', 'gateway' => 'paypal' ) );
$response = check_coupon( $ids['basic'], 'ONCE' );
check( 'A coupon used up to its redemption limit is rejected', false === $response['success'] && 'Coupon redemption limit is reached.' === $response['msg'], json_encode( $response ) . ' Redemptions: ' . post_meta( $once_id, 'wpec_coupon_red_count' ) );

$page   = product_page( 'donation' );
$data   = purchase( $page, array( 'amount' => 1 ) );
$before = order_ids();
$create = json_of( paypal_create( $page, $data ) );
check( 'PayPal: a donation below the minimum passes the create order request (it is checked after the capture)', ! empty( $create['order_id'] ), json_encode( $create ) );
$response = paypal_capture( $page, $data, $create['order_id'] );
check( 'PayPal: the donation below the $5 minimum is rejected after the capture', 'Payment amount mismatch with the original price.' === json_of( $response ) && 0 === count( array_filter( array_map( 'order', new_order_ids( $before ) ), function ( $o ) { return 'paid' === $o['status']; } ) ), response_text( $response ) );

section( 'PayPal: rejected requests' );
$page       = product_page( 'basic' );
$data       = purchase( $page );
$order_data = WPEC_Test_Factory::paypal_order_data( $data );
$order_data['purchase_units'][0]['amount']['value'] = 0.01;
$paypal_calls = api_request_count( 'paypal' );
$response     = paypal_create( $page, $data, $order_data );
check( 'PayPal: a lower total is rejected', contains( json_of( $response )['err_msg'], 'Price validation failed' ), response_text( $response ) );
check( 'PayPal: no PayPal order is created for it', api_request_count( 'paypal' ) === $paypal_calls );

//A PayPal order for $0.01 that wasn't created by the plugin (for example with the PayPal JavaScript SDK and the site's public client ID).
$fakes = fakes();
$fakes['orders']['E2E-ORDER-FOREIGN'] = array(
	'id'             => 'E2E-ORDER-FOREIGN',
	'status'         => 'CREATED',
	'intent'         => 'CAPTURE',
	'purchase_units' => array( array( 'amount' => array( 'currency_code' => 'USD', 'value' => '0.01' ) ) ),
);
save_fakes( $fakes );
$before   = order_ids();
$response = paypal_capture( $page, $data, 'E2E-ORDER-FOREIGN' );
check( 'PayPal: capturing an order for $0.01 made outside the plugin is rejected (amount mismatch)', 'Payment amount mismatch with the original price.' === json_of( $response ), response_text( $response ) );
check( 'PayPal: no paid order is created for it', 0 === count( array_filter( array_map( 'order', new_order_ids( $before ) ), function ( $o ) { return 'paid' === $o['status']; } ) ) );
$page_wrong = $page;
$page_wrong['nonces']['wpec_on_approve_vars'] = 'wrong';
$response   = paypal_capture( $page_wrong, $data, 'E2E-ORDER-FOREIGN' );
check( 'PayPal: a capture request with a wrong nonce is rejected', contains( json_of( $response )['err_msg'], 'Nonce check failed' ), response_text( $response ) );

// ---- Free checkout ----

section( 'Free checkout (100% coupon)' );
$page     = product_page( 'download' );
$data     = purchase( $page, array( 'coupon' => coupon( $ids['download'], 'FREE100' ) ) );
check( 'The total with the 100% coupon is 0', 0.0 === (float) $data['total'], json_encode( $data['total'] ) );
$before   = order_ids();
$response = process_payment( 'wpec_process_empty_payment', $data );
$json     = json_of( $response );
$order    = check_paid_order( 'Free checkout', $before, isset( $json['redirect_url'] ) ? $json['redirect_url'] : response_text( $response ), array( 'status' => 'paid', 'total' => '0.00', 'gateway' => 'paypal' ) );
if ( $order ) {
	$thank_you = http_request( 'GET', $json['redirect_url'] );
	check( 'Free checkout: the Thank You page shows the download link', preg_match( '/href="([^"]*wpec_download_file=[^"]*)"/', $thank_you['body'], $m ) === 1, response_text( $thank_you ) );
	if ( ! empty( $m[1] ) ) {
		$download = http_request( 'GET', html_entity_decode( $m[1] ) );
		$body     = $download['body'];
		if ( '' !== $download['location'] ) {
			$body = http_request( 'GET', $download['location'] )['body'];
		}
		check( 'Free checkout: the download link delivers the file', contains( $body, 'E2E downloaded file content' ), response_text( $download ) );
		$wrong = http_request( 'GET', preg_replace( '/key=[^&]+/', 'key=wrongkey', html_entity_decode( $m[1] ) ) );
		check( 'A download link with a wrong key does not deliver the file', ! contains( $wrong['body'], 'E2E downloaded file content' ) && ! contains( $wrong['location'], 'e2e-download.txt' ), response_text( $wrong ) );
	}
}
$page     = product_page( 'basic' );
$before   = order_ids();
$response = process_payment( 'wpec_process_empty_payment', purchase( $page ) );
check( 'Free checkout without a coupon is rejected and creates no order', 'Payment amount mismatch with the original price.' === json_of( $response ) && 0 === count( new_order_ids( $before ) ), response_text( $response ) );
$data          = purchase( $page, array( 'coupon' => coupon( $ids['basic'], 'FREE100' ) ) );
$data['nonce'] = 'wrong';
$response      = process_payment( 'wpec_process_empty_payment', $data );
check( 'Free checkout with a wrong nonce is rejected', contains( json_of( $response ), 'Nonce verification failed' ), response_text( $response ) );

// ---- Manual checkout ----

section( 'Manual checkout' );
$page     = product_page( 'basic' );
$data     = purchase( $page, array( 'quantity' => 2 ) );
$emails   = count( emails() );
$before   = order_ids();
$response = process_payment( 'wpec_process_manual_checkout', $data, payer( 'manual-buyer@example.com' ) );
$json     = json_of( $response );
$order    = check_paid_order( 'Manual checkout', $before, isset( $json['redirect_url'] ) ? $json['redirect_url'] : response_text( $response ), array( 'status' => 'pending', 'total' => '20.00', 'gateway' => 'manual_checkout' ) );
if ( $order ) {
	$sent = emails_since( $emails );
	check( 'Manual checkout: the buyer gets the payment instructions and the seller is notified', null !== email_to( $sent, 'manual-buyer@example.com' ) && null !== email_to( $sent, 'seller@example.com' ), json_encode( array_column( $sent, 'to' ) ) );
}
$data['total'] = 1;
$before        = order_ids();
$response      = process_payment( 'wpec_process_manual_checkout', $data );
check( 'Manual checkout with a lower total is rejected', 'Payment amount mismatch with the original price.' === json_of( $response ), response_text( $response ) );
check( 'Manual checkout: no pending order is created for it', 0 === count( array_filter( array_map( 'order', new_order_ids( $before ) ), function ( $o ) { return 'pending' === $o['status']; } ) ) );

section( 'Manual checkout: downloads only after the payment (fixed after 2.5.6)' );
//The product's own Thank You page shows the downloads with [wpec_ty_downloads]. The order is pending until the admin marks it as paid
//(here the runner changes the order's status in the database, like the admin's "Set paid and send email" button does).
$page     = product_page( 'manualdl' );
$before   = order_ids();
$response = process_payment( 'wpec_process_manual_checkout', purchase( $page ), payer( 'manual-dl-buyer@example.com' ) );
$json     = json_of( $response );
$new      = new_order_ids( $before );
if ( check( 'Manual checkout of a download product: a pending order is created', 1 === count( $new ) && ! empty( $json['redirect_url'] ) && 'pending' === order( $new[0] )['status'], response_text( $response ) ) ) {
	$order_id  = $new[0];
	$thank_you = http_request( 'GET', $json['redirect_url'] );
	check( 'The Thank You page shows the pending order without a download link', contains( $thank_you['body'], 'Jane' ) && ! contains( $thank_you['body'], 'wpec_download_file=' ), response_text( $thank_you ) );

	set_post_meta( $order_id, 'wpec_order_state', 'paid' );
	$thank_you = http_request( 'GET', $json['redirect_url'] );
	$link      = preg_match( '/href="([^"]*wpec_download_file=[^"]*)"/', $thank_you['body'], $m ) ? html_entity_decode( $m[1] ) : '';
	check( 'When the order is paid, the Thank You page shows the download link', '' !== $link, response_text( $thank_you ) );

	if ( '' !== $link ) {
		foreach ( array( 'pending', 'refunded' ) as $status ) {
			set_post_meta( $order_id, 'wpec_order_state', $status );
			$download = http_request( 'GET', $link );
			check(
				"The download link of a {$status} order is rejected",
				contains( $download['body'], 'payment for this order has not been completed' ) && ! contains( $download['body'], 'E2E downloaded file content' ) && ! contains( $download['location'], 'e2e-download.txt' ),
				response_text( $download )
			);
		}

		set_post_meta( $order_id, 'wpec_order_state', 'paid' );
		$download = http_request( 'GET', $link );
		$body     = '' !== $download['location'] ? http_request( 'GET', $download['location'] )['body'] : $download['body'];
		check( 'The download link of the paid order delivers the file', contains( $body, 'E2E downloaded file content' ), response_text( $download ) );
	}
}

// ---- Thank You page ----

section( 'Thank You page' );
global $ids;
$response = http_request( 'GET', '/?page_id=' . $ids['thank_you_page'] );
check( 'The Thank You page without an order ID shows the "Order ID value is missing" error', contains( $response['body'], 'Order ID value is missing' ), response_text( $response ) );
$order_ids = order_ids();
$response  = http_request( 'GET', '/?page_id=' . $ids['thank_you_page'] . '&order_id=' . end( $order_ids ) . '&_wpnonce=wrong' );
check( 'The Thank You page with a wrong nonce shows the nonce error and no order', contains( $response['body'], 'Nonce verification failed' ) && ! contains( $response['body'], 'E2E Basic' ), response_text( $response ) );

// ---- PHP errors ----

section( 'PHP errors on the test site' );
$debug_log = getenv( 'E2E_DEBUG_LOG' );
if ( $debug_log && file_exists( $debug_log ) ) {
	$log    = (string) file_get_contents( $debug_log );
	$fatals = preg_match_all( '/PHP Fatal error.*/', $log, $fatal_lines );
	check( 'No PHP fatal errors', 0 === $fatals, implode( "\n    ", array_slice( $fatal_lines[0], 0, 5 ) ) );
	preg_match_all( '/PHP (Warning|Notice|Deprecated):.*wp-express-checkout.*/', $log, $plugin_lines );
	$plugin_lines = array_unique( $plugin_lines[0] );
	echo count( $plugin_lines ) . " different PHP warnings/notices/deprecations from the plugin" . ( $plugin_lines ? ":\n    " . implode( "\n    ", array_slice( $plugin_lines, 0, 20 ) ) : '.' ) . "\n";
} else {
	echo "E2E_DEBUG_LOG isn't set or the file doesn't exist: not checked.\n";
}

echo "\n" . ( 0 === $failures ? 'All endpoint tests passed.' : "{$failures} endpoint test(s) failed." ) . ( $known ? " {$known} known issue check(s) reported (see above)." : '' ) . "\n";
exit( 0 === $failures ? 0 : 1 );
