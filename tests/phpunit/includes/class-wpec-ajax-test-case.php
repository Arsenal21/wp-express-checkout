<?php
/**
 * Thrown by wp_die() (and so by wp_send_json()) during an AJAX test request: the plugin's AJAX handlers end with it.
 */
class WPEC_Test_Ajax_Die extends Exception {}

/**
 * Thrown by a test hook to stop a request right before the plugin calls the Stripe or PayPal API.
 * It extends Error (not Exception), so the plugin's catch ( \Exception $e ) blocks don't catch it.
 */
class WPEC_Test_Stop_Before_Api extends Error {}

/**
 * Base class for tests that send the plugin's AJAX requests.
 *
 * Unlike WP_Ajax_UnitTestCase, it doesn't turn off PHP warnings: a warning in the plugin code fails the test.
 */
abstract class WPEC_Ajax_Test_Case extends WP_UnitTestCase {

	/**
	 * What the request printed (the JSON response).
	 *
	 * @var string
	 */
	protected $response_body = '';

	/**
	 * The Stripe checkout session options the request would have sent to Stripe (when it was stopped before the API call).
	 *
	 * @var array|null
	 */
	protected $stripe_session_opts = null;

	/**
	 * The result of the pre-submission validation (when the request was stopped after it passed).
	 *
	 * @var bool|null
	 */
	protected $validation_result = null;

	public function set_up() {
		parent::set_up();
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		$this->response_body       = '';
		$this->stripe_session_opts = null;
		$this->validation_result   = null;
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	public function die_handler( $message ) {
		throw new WPEC_Test_Ajax_Die( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * Stops the Stripe checkout session request right before the plugin creates the session, and saves the session options.
	 * Only for products without tax (for a product with tax, the plugin calls the Stripe API for the tax rate before that).
	 */
	protected function stop_before_stripe_session() {
		add_filter(
			'wpec_stripe_checkout_session_opts',
			function ( $opts ) {
				$this->stripe_session_opts = $opts;
				throw new WPEC_Test_Stop_Before_Api( 'Stopped before the Stripe API call.' );
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Stops the request right after the pre-submission validation passed (before the Stripe or PayPal API call).
	 * When the validation fails, the request continues and sends the error response.
	 */
	protected function stop_after_validation_passes() {
		add_filter(
			'wpec_pre_api_submission_validation',
			function ( $validated ) {
				$this->validation_result = $validated;
				if ( $validated ) {
					throw new WPEC_Test_Stop_Before_Api( 'Stopped after the validation passed.' );
				}
				return $validated;
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Sends an AJAX request as a visitor who isn't logged in (wp_ajax_nopriv_{action}), like admin-ajax.php does.
	 *
	 * @param array $post The POST fields (with 'action').
	 *
	 * @return string 'die' (the handler ended with wp_die()/wp_send_json()), 'stopped' (stopped before the API call)
	 *                or 'returned' (the handler returned without ending the request).
	 */
	protected function send_ajax( $post ) {
		$_POST    = $post;
		$_GET     = array();
		$_REQUEST = $post;

		return $this->run_request(
			function () use ( $post ) {
				do_action( 'wp_ajax_nopriv_' . $post['action'] );
			}
		);
	}

	/**
	 * Runs code that ends the request with wp_die()/wp_send_json() (for example a payment processing method), and saves what it printed.
	 *
	 * @param callable $callback The code to run.
	 *
	 * @return string 'die', 'stopped' or 'returned' (see send_ajax()).
	 */
	protected function run_request( $callback ) {
		$this->response_body = '';
		ob_start();
		try {
			call_user_func( $callback );
			$result = 'returned';
		} catch ( WPEC_Test_Ajax_Die $e ) {
			$result = 'die';
		} catch ( WPEC_Test_Stop_Before_Api $e ) {
			$result = 'stopped';
		} finally {
			$this->response_body = ob_get_clean();
		}
		return $result;
	}

	/**
	 * The JSON response of the last request, decoded.
	 */
	protected function response() {
		$decoded = json_decode( $this->response_body, true );
		$this->assertNotNull( $decoded, 'The response is not JSON: ' . $this->response_body );
		return $decoded;
	}

	/**
	 * Asserts that the last request ended with an error response that contains the given text.
	 * The Stripe handler sends { success: false, data: { message } }, the PayPal handler { success: false, err_msg }.
	 */
	protected function assertErrorResponse( $result, $contains = '' ) {
		$this->assertSame( 'die', $result, 'The request was expected to be rejected. Response: ' . $this->response_body );
		$response = $this->response();
		$this->assertFalse( $response['success'], 'The request was expected to be rejected. Response: ' . $this->response_body );
		if ( '' !== $contains ) {
			$message = isset( $response['data']['message'] ) ? $response['data']['message'] : ( isset( $response['err_msg'] ) ? $response['err_msg'] : '' );
			$this->assertStringContainsString( $contains, $message );
		}
	}
}
