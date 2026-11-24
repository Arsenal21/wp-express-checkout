<?php
/**
 * Buyer-entered order data shown on the site (stored XSS, fixed after 2.5.5).
 *
 * The manual checkout and the free checkout save the buyer details from the browser (wp_ppdg_payment[payer]); a visitor
 * who isn't logged in can send any text there. The [wpec_ty field=...] Thank You page shortcode showed it without escaping.
 * Now the buyer details are saved as text only, and the HTML order tags (Order_Tags_Html) are escaped;
 * the plain text order tags (Order_Tags_Plain, used for the emails) are not.
 */
class Test_Buyer_Data_Escaping extends WPEC_Ajax_Test_Case {

	const PAYLOAD = '<img src=x onerror=alert(document.domain)>';

	public function set_up() {
		parent::set_up();
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'                => 0,
				'sandbox_client_id'      => 'test-client-id',
				'enable_manual_checkout' => 1,
				'currency_code'          => 'USD',
				'price_decimals_num'     => '2',
				'thank_you_url'          => 'http://example.org/thank-you/',
				'send_buyer_email'       => 0,
				'send_seller_email'      => 0,
			)
		);
	}

	/**
	 * An order whose buyer details contain HTML (for example an order saved before the fix).
	 */
	private function order_with_html( $product_id ) {
		$order = \WP_Express_Checkout\Orders::create();
		$order->add_item( 'ppec-products', get_the_title( $product_id ), 10, 1, $product_id, true );
		$order->add_item( 'variation', 'Size - ' . self::PAYLOAD, 0, 1, $product_id );
		$order->add_item( 'coupon', 'Coupon', -1, 1, 0, false, array( 'code' => self::PAYLOAD ) );
		$order->add_data( 'state', 'COMPLETED' );
		$order->add_data(
			'payer',
			array(
				'name'          => array(
					'given_name' => self::PAYLOAD,
					'surname'    => "O'Brien <b>",
				),
				'email_address' => 'buyer@example.com',
				'phone'         => self::PAYLOAD,
			)
		);
		$order->add_data( 'shipping_address', self::PAYLOAD );
		return \WP_Express_Checkout\Orders::retrieve( $order->get_id() );
	}

	private function assert_no_html( $value, $label ) {
		$this->assertStringNotContainsString( '<img', $value, $label );
		$this->assertStringNotContainsString( '<b>', $value, $label );
	}

	public function test_the_html_order_tags_are_escaped() {
		$product_id = WPEC_Test_Factory::create_product( '10' );
		$tags       = new \WP_Express_Checkout\Order_Tags_Html( $this->order_with_html( $product_id ) );

		foreach ( array( 'first_name', 'last_name', 'payer_phone', 'address', 'coupon_code', 'selected_variations' ) as $tag ) {
			$this->assert_no_html( $tags->$tag(), $tag );
		}
		$this->assertSame( esc_html( self::PAYLOAD ), $tags->first_name() );
		$this->assertSame( 'O&#039;Brien &lt;b&gt;', $tags->last_name() );
	}

	public function test_the_plain_text_order_tags_are_not_escaped() {
		//The emails are plain text: an apostrophe stays an apostrophe.
		$product_id = WPEC_Test_Factory::create_product( '10' );
		$tags       = new \WP_Express_Checkout\Order_Tags_Plain( $this->order_with_html( $product_id ) );

		$this->assertSame( "O'Brien <b>", $tags->last_name() );
		$this->assertSame( self::PAYLOAD, $tags->first_name() );
	}

	public function test_the_thank_you_page_part_shortcode_escapes_the_buyer_details() {
		$order            = $this->order_with_html( WPEC_Test_Factory::create_product( '10' ) );
		$_GET['order_id'] = (string) $order->get_id();
		$_GET['_wpnonce'] = wp_create_nonce( 'thank_you_url' . $order->get_id() );

		foreach ( array( 'first_name', 'last_name', 'payer_phone', 'address', 'coupon_code', 'selected_variations' ) as $field ) {
			$this->assert_no_html( do_shortcode( '[wpec_ty field=' . $field . ']' ), "[wpec_ty field={$field}]" );
		}

		unset( $_GET['order_id'], $_GET['_wpnonce'] );
	}

	public function test_a_custom_order_tag_keeps_safe_html_but_not_scripts() {
		$order = $this->order_with_html( WPEC_Test_Factory::create_product( '10' ) );
		add_filter(
			'wpec_render_custom_order_tag',
			function ( $output, $name ) {
				return 'e2e_custom' === $name ? '<strong>Field:</strong> ' . self::PAYLOAD . '<script>alert(1)</script>' : $output;
			},
			10,
			2
		);

		$html = ( new \WP_Express_Checkout\Order_Tags_Html( $order ) )->e2e_custom();

		$this->assertStringContainsString( '<strong>Field:</strong>', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '<script>', ( new \WP_Express_Checkout\Order_Tags_Plain( $order ) )->e2e_custom(), 'Plain text: not changed.' );
	}

	public function test_the_download_link_tag_escapes_the_item_name_and_the_shortcode_arguments() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'ppec_product_upload' => 'http://example.org/file.zip' ), 'File <i>Product</i>' );
		$order      = $this->order_with_html( $product_id );

		$html = ( new \WP_Express_Checkout\Order_Tags_Html( $order ) )->download_link(
			array(
				'anchor_text' => 'Get it <script>x</script>',
				'target'      => '"><script>x</script>',
			)
		);

		$this->assertStringContainsString( 'wpec_download_file=' . $product_id, $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( '<i>', $html );
	}

	// ---- The order item name (fixed after 2.5.5: it was the name sent by the browser) ----

	/**
	 * The purchase data with HTML added to the product name. The plugin finds the product's price data by the name
	 * (sanitize_title_with_dashes() removes the HTML), so the request still finds the product.
	 */
	private function data_with_html_name( $product_id, $args = array() ) {
		do_shortcode( '[wp_express_checkout product_id="' . $product_id . '"]' );
		$data          = WPEC_Test_Factory::frontend_data( $product_id, $args );
		$data['name']  = $data['name'] . self::PAYLOAD;
		$data['nonce'] = wp_create_nonce( $data['id'] . $product_id );
		return $data;
	}

	private function order_from_response() {
		$response = $this->response();
		$this->assertArrayHasKey( 'redirect_url', $response, 'The checkout succeeds. Response: ' . $this->response_body );
		preg_match( '/order_id=(\d+)/', $response['redirect_url'], $m );
		return \WP_Express_Checkout\Orders::retrieve( (int) $m[1] );
	}

	private function assert_order_has_the_product_name( $order, $name ) {
		$this->assertSame( $name, $order->get_item( 'ppec-products' )['name'] );
		$this->assertStringNotContainsString( '<img', $order->get_description() );
	}

	public function test_the_paypal_capture_saves_the_product_name_not_the_name_sent_by_the_browser() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'PayPal Product' );
		$data       = $this->data_with_html_name( $product_id );
		$payment    = array(
			'id'             => 'PAYPAL-ORDER-1',
			'status'         => 'COMPLETED',
			'payer'          => array( 'email_address' => 'buyer@example.com' ),
			'purchase_units' => array(
				array(
					'payments' => array(
						'captures' => array(
							array(
								'id'     => 'CAPTURE-1',
								'amount' => array(
									'value'         => '10.00',
									'currency_code' => 'USD',
								),
							),
						),
					),
				),
			),
		);

		$this->run_request(
			function () use ( $payment, $data ) {
				\WP_Express_Checkout\Payment_Processor::get_instance()->wpec_server_side_process_payment( $payment, $data );
			}
		);

		$this->assert_order_has_the_product_name( $this->order_from_response(), 'PayPal Product' );
	}

	public function test_the_free_checkout_saves_the_product_name_not_the_name_sent_by_the_browser() {
		WPEC_Test_Factory::update_settings( array( 'coupons_enabled' => 1 ) );
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'ppec_product_upload' => 'http://example.org/file.zip' ), 'Free Product' );
		WPEC_Test_Factory::create_coupon( 'FREE100', 100, 'perc' );
		$data = $this->data_with_html_name( $product_id, array( 'coupon' => array( 'code' => 'FREE100', 'discount' => '100', 'discountType' => 'perc' ) ) );

		$this->send_ajax(
			wp_slash(
				array(
					'action'          => 'wpec_process_empty_payment',
					'wp_ppdg_payment' => array( 'payer' => array( 'email_address' => 'buyer@example.com' ) ),
					'data'            => $data,
					'nonce'           => $data['nonce'],
				)
			)
		);

		$order = $this->order_from_response();
		$this->assert_order_has_the_product_name( $order, 'Free Product' );
		//The report's sink: the download link on the Thank You page.
		$this->assertStringNotContainsString( '<img', ( new \WP_Express_Checkout\Order_Tags_Html( $order ) )->download_link() );
	}

	public function test_the_manual_checkout_saves_the_product_name_not_the_name_sent_by_the_browser() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Manual Product' );
		$data       = $this->data_with_html_name( $product_id );

		$this->send_ajax(
			wp_slash(
				array(
					'action'          => 'wpec_process_manual_checkout',
					'wp_ppdg_payment' => array( 'payer' => array( 'email_address' => 'buyer@example.com' ) ),
					'data'            => $data,
					'nonce'           => $data['nonce'],
				)
			)
		);

		$this->assert_order_has_the_product_name( $this->order_from_response(), 'Manual Product' );
	}

	/**
	 * The manual checkout request (assets/js/wpec-manual-checkout.js) with HTML in the buyer details.
	 */
	public function test_the_manual_checkout_saves_the_buyer_details_as_text() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Manual Product' );
		do_shortcode( '[wp_express_checkout product_id="' . $product_id . '"]' );
		$data          = WPEC_Test_Factory::frontend_data( $product_id );
		$data['nonce'] = wp_create_nonce( $data['id'] . $product_id );

		$result = $this->send_ajax(
			wp_slash(
				array(
					'action'          => 'wpec_process_manual_checkout',
					'wp_ppdg_payment' => array(
						'payer' => array(
							'name'          => array(
								'given_name' => self::PAYLOAD,
								'surname'    => 'Buyer<script>alert(1)</script>',
							),
							'email_address' => 'buyer@example.com',
							'phone'         => self::PAYLOAD,
							'address'       => array( 'address_line_1' => self::PAYLOAD ),
						),
					),
					'data'            => $data,
					'nonce'           => $data['nonce'],
				)
			)
		);

		$this->assertSame( 'die', $result );
		$response = $this->response();
		$this->assertArrayHasKey( 'redirect_url', $response, 'The manual checkout succeeds. Response: ' . $this->response_body );
		preg_match( '/order_id=(\d+)/', $response['redirect_url'], $m );
		$payer = \WP_Express_Checkout\Orders::retrieve( (int) $m[1] )->get_data( 'payer' );

		$this->assertStringNotContainsString( '<', $payer['name']['given_name'] );
		$this->assertSame( 'Buyer', $payer['name']['surname'] );
		$this->assertStringNotContainsString( '<', $payer['phone'] );
		$this->assertStringNotContainsString( '<', $payer['address']['address_line_1'] );
		$this->assertSame( 'buyer@example.com', $payer['email_address'] );
	}
}
