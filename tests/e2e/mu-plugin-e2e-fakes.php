<?php
/**
 * Endpoint tests only (installed as a must-use plugin on the GitHub Actions test site, never on a real site).
 *
 * Fake Stripe and PayPal APIs, so the checkout requests can run without Stripe and PayPal:
 * - Stripe: the Stripe PHP SDK (lib/stripe) gets a custom HTTP client (\Stripe\ApiRequestor::setHttpClient()).
 *   It answers the checkout session (create, retrieve with the expanded PaymentIntent and line items) and tax rate requests.
 *   There is no browser, so a checkout session is paid as soon as it is created; the runner changes a session's state directly.
 * - PayPal: the PayPal SDK (vendor/paypal) makes its requests with the PayPalHttp\Curl class. This file declares that class
 *   before the plugin's autoloader would load it (must-use plugins load before plugins), so the SDK uses this one. It answers
 *   the access token, create order and capture order requests. A capture is completed for the amount of the created order.
 *
 * The fakes keep their data (sessions, tax rates, PayPal orders, a log of the requests) as JSON in the 'wpec_e2e_fakes' option,
 * so the test runner can read and change it. The emails the site sends are kept in the 'wpec_e2e_emails' option (JSON) instead.
 */

namespace PayPalHttp {

	/**
	 * Replaces the PayPal SDK's curl wrapper (same methods). The request goes to wpec_e2e_paypal_handle() instead of PayPal.
	 */
	class Curl {

		protected $opts = array();

		protected $status = 0;

		public function __construct( $curl = null ) {
		}

		public function setOpt( $option, $value ) {
			$this->opts[ $option ] = $value;
			return $this;
		}

		public function close() {
			return $this;
		}

		public function exec() {
			$method = isset( $this->opts[ CURLOPT_CUSTOMREQUEST ] ) ? $this->opts[ CURLOPT_CUSTOMREQUEST ] : 'GET';
			$body   = isset( $this->opts[ CURLOPT_POSTFIELDS ] ) ? $this->opts[ CURLOPT_POSTFIELDS ] : '';

			list( $this->status, $response ) = \wpec_e2e_paypal_handle( $method, $this->opts[ CURLOPT_URL ], $body );

			if ( isset( $this->opts[ CURLOPT_HEADERFUNCTION ] ) ) {
				call_user_func( $this->opts[ CURLOPT_HEADERFUNCTION ], null, "Content-Type: application/json\r\n" );
				call_user_func( $this->opts[ CURLOPT_HEADERFUNCTION ], null, "Paypal-Debug-Id: e2e-debug-id\r\n" );
			}
			return wp_json_encode( $response );
		}

		public function errNo() {
			return 0;
		}

		public function getInfo( $option ) {
			return CURLINFO_HTTP_CODE === $option ? $this->status : null;
		}

		public function error() {
			return '';
		}
	}
}

namespace {

	function wpec_e2e_state() {
		$state = json_decode( (string) get_option( 'wpec_e2e_fakes', '' ), true );
		if ( ! is_array( $state ) ) {
			$state = array(
				'next'     => 1,
				'sessions' => array(),
				'tax'      => array(),
				'orders'   => array(),
				'requests' => array(),
			);
		}
		return $state;
	}

	function wpec_e2e_save_state( $state ) {
		update_option( 'wpec_e2e_fakes', wp_json_encode( $state ), false );
	}

	// ---- Stripe ----

	function wpec_e2e_stripe_error( $status, $message ) {
		return array(
			$status,
			array(
				'error' => array(
					'type'    => 'invalid_request_error',
					'code'    => 'resource_missing',
					'message' => $message,
				),
			),
		);
	}

	/**
	 * A checkout session as Stripe returns it. With 'expand', the PaymentIntent (with its latest charge) and the line items are objects.
	 */
	function wpec_e2e_stripe_session_response( $session, $expand ) {
		$response = $session;
		unset( $response['e2e_line_items'] );

		if ( in_array( 'payment_intent', $expand, true ) ) {
			$response['payment_intent'] = array(
				'id'             => $session['payment_intent'],
				'object'         => 'payment_intent',
				'amount'         => $session['amount_total'],
				'currency'       => $session['currency'],
				'status'         => 'paid' === $session['payment_status'] ? 'succeeded' : 'requires_payment_method',
				'shipping'       => null,
				'latest_charge'  => 'paid' === $session['payment_status'] ? array(
					'id'       => 'ch_' . substr( $session['id'], 3 ),
					'object'   => 'charge',
					'amount'   => $session['amount_total'],
					'currency' => $session['currency'],
					'paid'     => true,
					'status'   => 'succeeded',
				) : null,
			);
		}
		if ( in_array( 'line_items', $expand, true ) ) {
			$items = array();
			foreach ( $session['e2e_line_items'] as $index => $item ) {
				$items[] = array(
					'id'           => 'li_' . $index . '_' . $session['id'],
					'object'       => 'item',
					'quantity'     => (int) $item['quantity'],
					'amount_total' => (int) $item['price_data']['unit_amount'] * (int) $item['quantity'],
					'price'        => array(
						'id'          => 'price_' . $index . '_' . $session['id'],
						'object'      => 'price',
						'unit_amount' => (int) $item['price_data']['unit_amount'],
						'currency'    => $session['currency'],
						'product'     => 'prod_' . $index,
					),
				);
			}
			$response['line_items'] = array(
				'object'   => 'list',
				'data'     => $items,
				'has_more' => false,
			);
		}
		return $response;
	}

	/**
	 * Answers a Stripe API request.
	 *
	 * @return array The HTTP status code and the response body (array).
	 */
	function wpec_e2e_stripe_handle( $method, $url, $params ) {
		$state = wpec_e2e_state();
		$path  = wp_parse_url( $url, PHP_URL_PATH );
		$query = array();
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$params = is_array( $params ) ? array_merge( $query, $params ) : $query;

		$state['requests'][] = array(
			'api'    => 'stripe',
			'method' => strtoupper( $method ),
			'path'   => $path,
			'params' => $params,
		);

		$result = wpec_e2e_stripe_error( 404, 'Unknown request: ' . $method . ' ' . $path );

		if ( 'post' === strtolower( $method ) && '/v1/checkout/sessions' === $path ) {
			$id       = 'cs_test_e2e_' . $state['next']++;
			$currency = strtolower( $params['line_items'][0]['price_data']['currency'] );
			$subtotal = 0;
			$tax      = 0;
			foreach ( $params['line_items'] as $item ) {
				$amount    = (int) $item['price_data']['unit_amount'] * (int) $item['quantity'];
				$subtotal += $amount;
				if ( ! empty( $item['tax_rates'] ) ) {
					foreach ( $item['tax_rates'] as $tax_rate_id ) {
						$tax += (int) round( $amount * floatval( $state['tax'][ $tax_rate_id ]['percentage'] ) / 100 );
					}
				}
			}
			$shipping = isset( $params['shipping_options'][0]['shipping_rate_data']['fixed_amount']['amount'] ) ? (int) $params['shipping_options'][0]['shipping_rate_data']['fixed_amount']['amount'] : 0;

			$session = array(
				'id'                  => $id,
				'object'              => 'checkout.session',
				'url'                 => 'https://checkout.stripe.test/c/pay/' . $id,
				'mode'                => $params['mode'],
				//No browser: the buyer "pays" as soon as the session is created.
				'status'              => 'complete',
				'payment_status'      => 'paid',
				'currency'            => $currency,
				'amount_subtotal'     => $subtotal,
				'amount_total'        => $subtotal + $tax + $shipping,
				'total_details'       => array(
					'amount_discount' => 0,
					'amount_shipping' => $shipping,
					'amount_tax'      => $tax,
				),
				'client_reference_id' => isset( $params['client_reference_id'] ) ? $params['client_reference_id'] : null,
				'success_url'         => $params['success_url'],
				'cancel_url'          => isset( $params['cancel_url'] ) ? $params['cancel_url'] : null,
				'payment_intent'      => 'pi_e2e_' . substr( $id, 12 ),
				'customer_details'    => array(
					'email'   => 'stripe-buyer@example.com',
					'name'    => 'Jane Stripe',
					'phone'   => null,
					'address' => array(
						'line1'       => '1 Test Street',
						'line2'       => null,
						'city'        => 'Test City',
						'state'       => 'CA',
						'postal_code' => '12345',
						'country'     => 'US',
					),
				),
				'metadata'            => new stdClass(),
				'e2e_line_items'      => $params['line_items'],
			);

			$state['sessions'][ $id ] = $session;
			$result                   = array( 200, wpec_e2e_stripe_session_response( $session, array() ) );
		} elseif ( 'get' === strtolower( $method ) && preg_match( '#^/v1/checkout/sessions/([^/]+)$#', $path, $m ) ) {
			if ( isset( $state['sessions'][ $m[1] ] ) ) {
				$expand = isset( $params['expand'] ) ? array_values( (array) $params['expand'] ) : array();
				$result = array( 200, wpec_e2e_stripe_session_response( $state['sessions'][ $m[1] ], $expand ) );
			} else {
				$result = wpec_e2e_stripe_error( 404, "No such checkout.session: '{$m[1]}'" );
			}
		} elseif ( 'post' === strtolower( $method ) && '/v1/tax_rates' === $path ) {
			$id                 = 'txr_e2e_' . $state['next']++;
			$state['tax'][ $id ] = array(
				'id'           => $id,
				'object'       => 'tax_rate',
				'active'       => true,
				'display_name' => $params['display_name'],
				'percentage'   => floatval( $params['percentage'] ),
				'inclusive'    => false,
			);
			$result = array( 200, $state['tax'][ $id ] );
		} elseif ( 'get' === strtolower( $method ) && preg_match( '#^/v1/tax_rates/([^/]+)$#', $path, $m ) ) {
			$result = isset( $state['tax'][ $m[1] ] ) ? array( 200, $state['tax'][ $m[1] ] ) : wpec_e2e_stripe_error( 404, "No such tax rate: '{$m[1]}'" );
		}

		wpec_e2e_save_state( $state );
		return $result;
	}

	/**
	 * Loads the Stripe SDK (the same way the plugin loads it) and gives it the fake HTTP client.
	 * The class is declared after the SDK is loaded, because it implements the SDK's interface.
	 */
	function wpec_e2e_install_stripe_client() {
		if ( ! class_exists( '\WP_Express_Checkout\Utils' ) ) {
			return;
		}
		\WP_Express_Checkout\Utils::load_stripe_sdk();
		if ( ! class_exists( 'WPEC_E2E_Fake_Stripe_Http_Client', false ) ) {
			class WPEC_E2E_Fake_Stripe_Http_Client implements \Stripe\HttpClient\ClientInterface {
				public function request( $method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null ) {
					list( $status, $body ) = wpec_e2e_stripe_handle( $method, $absUrl, $params );
					return array( wp_json_encode( $body ), $status, new \Stripe\Util\CaseInsensitiveArray( array( 'request-id' => 'req_e2e' ) ) );
				}
			}
		}
		\Stripe\ApiRequestor::setHttpClient( new WPEC_E2E_Fake_Stripe_Http_Client() );
	}
	//Only for the requests that use Stripe: the checkout session request and the return from Stripe (the plugin processes it on 'init' priority 999).
	add_action(
		'init',
		function () {
			$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
			if ( 'wpec_stripe_create_checkout_session' === $action || isset( $_REQUEST['wpec_process_stripe_ipn'] ) ) {
				wpec_e2e_install_stripe_client();
			}
		},
		1
	);

	// ---- PayPal ----

	function wpec_e2e_paypal_error( $status, $name, $issue, $description ) {
		return array(
			$status,
			array(
				'name'    => $name,
				'message' => $description,
				'details' => array(
					array(
						'issue'       => $issue,
						'description' => $description,
					),
				),
			),
		);
	}

	/**
	 * Answers a PayPal API request.
	 *
	 * @return array The HTTP status code and the response body (array).
	 */
	function wpec_e2e_paypal_handle( $method, $url, $body ) {
		$state = wpec_e2e_state();
		$path  = wp_parse_url( $url, PHP_URL_PATH );
		$data  = json_decode( (string) $body, true );

		$state['requests'][] = array(
			'api'    => 'paypal',
			'method' => strtoupper( $method ),
			'path'   => $path,
			'params' => is_array( $data ) ? $data : (string) $body,
		);

		$result = wpec_e2e_paypal_error( 404, 'RESOURCE_NOT_FOUND', 'INVALID_RESOURCE_ID', 'Unknown request: ' . $method . ' ' . $path );

		if ( '/v1/oauth2/token' === $path ) {
			$result = array(
				200,
				array(
					'scope'        => 'https://uri.paypal.com/services/payments/payment',
					'access_token' => 'A21_e2e_access_token',
					'token_type'   => 'Bearer',
					'app_id'       => 'APP-E2E',
					'expires_in'   => 32400,
					'nonce'        => 'e2e',
				),
			);
		} elseif ( 'POST' === strtoupper( $method ) && '/v2/checkout/orders' === $path ) {
			$id                    = 'E2E-ORDER-' . $state['next']++;
			$state['orders'][ $id ] = array(
				'id'             => $id,
				'status'         => 'CREATED',
				'intent'         => $data['intent'],
				'purchase_units' => $data['purchase_units'],
			);
			$result = array(
				201,
				array(
					'id'     => $id,
					'status' => 'CREATED',
					'links'  => array(),
				),
			);
		} elseif ( 'POST' === strtoupper( $method ) && preg_match( '#^/v2/checkout/orders/([^/]+)/capture$#', $path, $m ) ) {
			if ( ! isset( $state['orders'][ $m[1] ] ) ) {
				$result = wpec_e2e_paypal_error( 404, 'RESOURCE_NOT_FOUND', 'INVALID_RESOURCE_ID', 'Specified resource ID does not exist. Please check the resource ID and try again.' );
			} elseif ( 'COMPLETED' === $state['orders'][ $m[1] ]['status'] ) {
				$result = wpec_e2e_paypal_error( 422, 'UNPROCESSABLE_ENTITY', 'ORDER_ALREADY_CAPTURED', 'Order already captured. If intent is CAPTURE only one capture per order is allowed.' );
			} else {
				$order           = $state['orders'][ $m[1] ];
				$order['status'] = 'COMPLETED';
				$amount          = $order['purchase_units'][0]['amount'];
				$order['capture'] = array(
					'id'             => $order['id'],
					'status'         => 'COMPLETED',
					'payer'          => array(
						'name'          => array(
							'given_name' => 'Jane',
							'surname'    => 'PayPal',
						),
						'email_address' => 'paypal-buyer@example.com',
						'payer_id'      => 'E2EPAYERID',
						'address'       => array( 'country_code' => 'US' ),
					),
					'purchase_units' => array(
						array(
							'reference_id' => 'default',
							'shipping'     => array(
								'name'    => array( 'full_name' => 'Jane PayPal' ),
								'address' => array(
									'address_line_1' => '1 Test Street',
									'admin_area_2'   => 'Test City',
									'admin_area_1'   => 'CA',
									'postal_code'    => '12345',
									'country_code'   => 'US',
								),
							),
							'payments'     => array(
								'captures' => array(
									array(
										'id'     => 'CAPTURE-' . $order['id'],
										'status' => 'COMPLETED',
										'amount' => array(
											'currency_code' => $amount['currency_code'],
											'value'         => (string) $amount['value'],
										),
									),
								),
							),
						),
					),
				);
				$state['orders'][ $m[1] ] = $order;
				$result                   = array( 201, $order['capture'] );
			}
		}

		wpec_e2e_save_state( $state );
		return $result;
	}

	//Nothing else may reach Stripe or PayPal from the test site (for example a request made with the WordPress HTTP API).
	add_filter(
		'pre_http_request',
		function ( $pre, $args, $url ) {
			$host = (string) wp_parse_url( $url, PHP_URL_HOST );
			if ( preg_match( '/(^|\.)(stripe\.com|paypal\.com)$/', $host ) ) {
				$state               = wpec_e2e_state();
				$state['requests'][] = array(
					'api'    => 'blocked',
					'method' => isset( $args['method'] ) ? $args['method'] : 'GET',
					'path'   => $url,
					'params' => array(),
				);
				wpec_e2e_save_state( $state );
				return new WP_Error( 'wpec_e2e_blocked', 'The endpoint tests block requests to ' . $host );
			}
			return $pre;
		},
		10,
		3
	);

	//Keep the emails instead of sending them.
	add_filter(
		'pre_wp_mail',
		function ( $return, $atts ) {
			$emails   = json_decode( (string) get_option( 'wpec_e2e_emails', '' ), true );
			$emails   = is_array( $emails ) ? $emails : array();
			$emails[] = array(
				'to'      => is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'],
				'subject' => $atts['subject'],
				'message' => $atts['message'],
			);
			update_option( 'wpec_e2e_emails', wp_json_encode( $emails ), false );
			return true;
		},
		10,
		2
	);
}
