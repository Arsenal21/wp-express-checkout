<?php
/**
 * This class is used to process the payments with 0 total.
 *
 * Sends notification emails.
 * Triggers after payment processed hook: wpec_payment_completed
 * Sends to Thank You page.
 */

/**
 * Process IPN class
 */
class WPEC_Process_IPN_Free extends WPEC_Process_IPN {

	private $order_data;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpec_process_empty_payment', array( $this, 'wpec_process_payment' ) );
		add_action( 'wp_ajax_nopriv_wpec_process_empty_payment', array( $this, 'wpec_process_payment' ) );
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_process_payment() {
		$this->order_data = $this->get_order_data();
		parent::wpec_process_payment();
	}

	protected function get_payment_data() {
		$current_user = wp_get_current_user();
		$payment = array(
			'id'     => $this->get_transaction_id( array() ),
			'intent' => 'CAPTURE',
			'status' => $this->get_transaction_status( array() ),
			'payer'  => array(
				'name' => array(
					'given_name' => $current_user->display_name ? $current_user->display_name : __ ( 'Anonymous', 'wp-express-checkout' ),
					'surname' => ''
				),
				'email_address' => $current_user->user_email ? $current_user->user_email : '',
				'address' => array(),
			),
			'create_time' => current_time( 'mysql' ),
			'update_time' => current_time( 'mysql' ),
		);

		return $payment;
	}

	/**
	 * Retrieves the item name from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_item_name( $payment ) {
		return $this->order_data['name'];
	}

	/**
	 * Retrieves peoduct queantity from transaction data.
	 *
	 * @param array $payment
	 * @return int
	 */
	protected function get_quantity( $payment ) {
		return $this->order_data['quantity'];
	}

	/**
	 * Retrieves order total from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_total( $payment ) {
		return $this->order_data['total'];
	}

	/**
	 * Retrieves currency from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_currency( $payment ) {
		return $this->order_data['currency'];
	}

	/**
	 * Retrieves transaction id.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_id( $payment ) {
		return substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 );
	}

	/**
	 * Retrieves transaction status.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_status( $payment ) {
		return 'COMPLETED';
	}

}
