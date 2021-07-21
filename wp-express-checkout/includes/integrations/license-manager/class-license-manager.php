<?php

namespace WP_Express_Checkout\Integrations;

use Exception;
use WP_Express_Checkout\Orders;
use WP_Express_Checkout\Products;

class License_Manager {

	public function __construct() {
		add_filter( 'wpec_buyer_notification_email_body', array( $this, 'email_body_filter' ), 10, 3 );

		if ( is_admin() ) {
			$this->admin();
		}
	}

	public function email_body_filter( $body, $payment, $args ) {
		global $slm_debug_logger;

		$slm_debug_logger->log_debug("WP Express Checkout integration - checking if a license key needs to be created for this transaction.");

		$slm_data = "";

		try {
			$order = Orders::retrieve( $args['order_id'] );
		} catch ( Exception $exc ) {
			return $body;
		}

		$product = $order->get_item( Products::$products_slug );

		if ( empty( $product ) ) {
			return $body;
		}

		$prod_id   = $product['post_id'];
		$item_name = $product['name'];
		$quantity  = $product['quantity'];

		$slm_debug_logger->log_debug( 'License Manager - Item Number: ' . $prod_id . ', Quantity: ' . $quantity . ', Item Name: ' . $item_name );

		$retrieved_product = get_post( $prod_id );

		$slm_debug_logger->log_debug( 'Checking license key generation for single item product.' );

		if ( ! $retrieved_product->wpec_slm_license_enabled ) {
			$slm_debug_logger->log_debug( 'Don\'t need to create a license key for this product (' . $prod_id . ')' );
			return $body;
		}

		for ( $i = 0; $i < $quantity; $i++ ) {
			$slm_debug_logger->log_debug( 'Need to create a license key for this product (' . $retrieved_product->id . ')' );
			$slm_key = $this->create_license( $retrieved_product, $payment );
			$license_data = "\n" . __( 'Item Name: ', 'wp-express-checkout' ) . $item_name . " - " . __( 'License Key: ', 'wp-express-checkout' ) . $slm_key;
			$slm_debug_logger->log_debug( 'Liense data: ' . $license_data );
			$license_data = apply_filters( 'wpec_item_license_data', $license_data );
			$slm_data .= $license_data;
		}

		$body = str_replace( "{wpec_slm_data}", $slm_data, $body );
		return $body;
	}

	public function create_license( $retrieved_product, $payment_data ) {
		global $slm_debug_logger;

		//Retrieve the default settings values.
		$options        = get_option( 'slm_plugin_options' );
		$lic_key_prefix = $options['lic_prefix'];
		$max_domains    = $options['default_max_domains'];

		//Lets check any product specific configuration.
		if ( $retrieved_product->wpec_slm_max_allowed_domains ) {
			//Found product specific SLM config data.
			$max_domains = $retrieved_product->wpec_slm_max_allowed_domains;
		}
		//Lets check if any product specific expiry date is set
		if ( $retrieved_product->wpec_slm_date_of_expiry ) {
			//Found product specific SLM config data.
			$slm_date_of_expiry = date( 'Y-m-d', strtotime( '+' . $retrieved_product->wpec_slm_date_of_expiry . ' days' ) );
		} else {
			//Use the default value (1 year from today).
			$slm_date_of_expiry = date( 'Y-m-d', strtotime( '+1 year' ) );
		}

		$fields = array();
		$fields['license_key'] = uniqid( $lic_key_prefix );
		$fields['lic_status'] = 'pending';
		$fields['first_name'] = ! empty( $payment_data['payer']['name']['given_name'] ) ? $payment_data['payer']['name']['given_name'] : '';
		$fields['last_name'] = ! empty( $payment_data['payer']['name']['surname'] ) ? $payment_data['payer']['name']['surname'] : '';
		$fields['email'] = $payment_data['payer']['email_address'];
		$fields['company_name'] = ''; // Not implemented
		$fields['txn_id'] = $payment_data['id'];
		$fields['max_allowed_domains'] = $max_domains;
		$fields['date_created'] = date( "Y-m-d" ); //Today's date
		$fields['date_expiry'] = $slm_date_of_expiry;
		$fields['product_ref'] = $retrieved_product->ID; //WPEC product ID
		$fields['subscr_id'] = isset( $payment_data['subscription']['id'] ) ? $payment_data['subscription']['id'] : '';

		$slm_debug_logger->log_debug( 'Inserting license data into the license manager DB table.' );

		\SLM_API_Utility::insert_license_data_internal( $fields );

		do_action( 'wpec_license_created', $payment_data, $fields );

		return $fields['license_key'];
	}

	public function admin() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'wpec_save_product_handler', array( $this, 'save_product_handler' ) );
	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_slm_meta_box', __( 'Software License Manager Plugin', 'wp-express-checkout' ), array( $this, 'display_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	public function display_meta_box( $post ) {
		$enabled     = get_post_meta( $post->ID, 'wpec_slm_license_enabled', true );
		$max_allowed = get_post_meta( $post->ID, 'wpec_slm_max_allowed_domains', true );
		$date_expiry = get_post_meta( $post->ID, 'wpec_slm_date_of_expiry', true );
		?>
		<label>
			<input type="checkbox" name="wpec_slm_license_enabled" value="1" <?php checked( $enabled ); ?>>
			<?php esc_html_e( 'Create License', 'wp-express-checkout' ); ?>
		</label><br />
		<p class="description"><?php esc_html_e( 'If this product is a piece of software that has been integrated with the Software License Manager plugin then checking this box will create a license for the customer who purchase this product.', 'wp-express-checkout' ); ?></p>

		<label><?php esc_html_e( 'Maximum Allowed Domains', 'wp-express-checkout' ); ?></label><br />
		<input name="wpec_slm_max_allowed_domains" type="text" id="wpec_slm_max_allowed_domains" value="<?php echo esc_attr( $max_allowed ); ?>" size="10" />
		<p class="description"><?php esc_html_e( 'Number of domains/installs in which this license can be used. Leave blank if you wish to use the default value set in the license manager plugin settings.', 'wp-express-checkout' ); ?></p>

		<label><?php esc_html_e( 'Number of Days before Expiry', 'wp-express-checkout' ); ?></label><br />
		<input name="wpec_slm_date_of_expiry" type="text" id="wpec_slm_date_of_expiry" value="<?php echo esc_attr( $date_expiry ); ?>" size="10" />
		<p class="description"><?php esc_html_e( 'Number of days before expiry. The expiry date of the license will be set based on this value. For example, if you want the key to expire in 6 months then enter a value of 180.', 'wp-express-checkout' ); ?></p>
		<?php
	}

	function save_product_handler( $post_id ) {
		update_post_meta( $post_id, 'wpec_slm_license_enabled', ! empty( $_POST['wpec_slm_license_enabled'] ) ? intval( $_POST['wpec_slm_license_enabled'] ) : '' );
		update_post_meta( $post_id, 'wpec_slm_max_allowed_domains', ! empty( $_POST['wpec_slm_max_allowed_domains'] ) ? intval( $_POST['wpec_slm_max_allowed_domains'] ) : '' );
		update_post_meta( $post_id, 'wpec_slm_date_of_expiry', ! empty( $_POST['wpec_slm_date_of_expiry'] ) ? intval( $_POST['wpec_slm_date_of_expiry'] ) : '' );
	}

}
