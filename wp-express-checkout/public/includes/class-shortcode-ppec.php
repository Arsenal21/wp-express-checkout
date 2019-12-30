<?php

class WPECShortcode {

    var $ppdg	 = null;
    var $paypaldg = null;

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance		 = null;
    protected static $payment_buttons	 = array();

    function __construct() {
	$this->ppdg = WPEC_Main::get_instance();

	//handle single product page display
	add_filter( 'the_content', array( $this, 'filter_post_type_content' ) );

	add_shortcode( 'wp_express_checkout', array( $this, 'shortcode_wp_express_checkout' ) );
	add_shortcode( 'wpec_thank_you', array( $this, 'shortcode_wpec_thank_you' ) );

	if ( ! is_admin() ) {
	    add_filter( 'widget_text', 'do_shortcode' );
	}
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

	// If the single instance hasn't been set, set it now.
	if ( null == self::$instance ) {
	    self::$instance = new self;
	}

	return self::$instance;
    }

    public static function filter_post_type_content( $content ) {
	global $post;
	if ( isset( $post ) ) {
	    if ( $post->post_type === PPECProducts::$products_slug ) {//Handle the content for product type post
		return do_shortcode( '[wp_express_checkout product_id="' . $post->ID . '" is_post_tpl="1" in_the_loop="' . +in_the_loop() . '"]' );
	    }
	}
	return $content;
    }

    private function show_err_msg( $msg ) {
	return sprintf( '<div class="wp-ppec-error-msg" style="color: red;">%s</div>', $msg );
    }

    function shortcode_wp_express_checkout( $atts ) {
	if ( empty( $atts[ 'product_id' ] ) ) {
	    $error_msg	 = __( "Error: product ID is invalid.", 'wp-express-checkout' );
	    $err		 = $this->show_err_msg( $error_msg );
	    return $err;
	}
	$post_id = intval( $atts[ 'product_id' ] );
	$post	 = get_post( $post_id );
	if ( ! $post || get_post_type( $post_id ) !== PPECProducts::$products_slug ) {
	    $error_msg	 = sprintf( __( "Can't find product with ID %s", 'wp-express-checkout' ), $post_id );
	    $err		 = $this->show_err_msg( $error_msg );
	    return $err;
	}

	$title		 = get_the_title( $post_id );
	$price		 = get_post_meta( $post_id, 'ppec_product_price', true );
	$quantity	 = get_post_meta( $post_id, 'ppec_product_quantity', true );
	$custom_quantity = get_post_meta( $post_id, 'ppec_product_custom_quantity', true );
	$url		 = get_post_meta( $post_id, 'ppec_product_upload', true );
	$content	 = $post->post_content;

	$output = '';

	//output content if needed
	if ( ! empty( $content ) ) {
	    global $wp_embed;
	    if ( isset( $wp_embed ) && is_object( $wp_embed ) ) {
		if ( method_exists( $wp_embed, 'autoembed' ) ) {
		    $content = $wp_embed->autoembed( $content );
		}
		if ( method_exists( $wp_embed, 'run_shortcode' ) ) {
		    $content = $wp_embed->run_shortcode( $content );
		}
	    }
	    $content = wpautop( do_shortcode( $content ) );
	    $output	 .= $content;
	}

        $args = array('name' => $title, 'price' => $price, 'quantity' => $quantity, 'custom_quantity' => $custom_quantity, 'url' => $url );
	$output .= $this->generate_pp_express_checkout_button($args);
	return $output;
    }

    function generate_pp_express_checkout_button( $args ) {

	extract( shortcode_atts( array(
	    'name'		 => 'Item Name',
	    'price'		 => '0',
	    'quantity'		 => 1,
	    'url'		 => '',
	    'custom_quantity'	 => 0,
	    'currency'		 => $this->ppdg->get_setting( 'currency_code' ),
	    'btn_shape'		 => $this->ppdg->get_setting( 'btn_shape' ) !== false ? $this->ppdg->get_setting( 'btn_shape' ) : 'pill',
	    'btn_type'		 => $this->ppdg->get_setting( 'btn_type' ) !== false ? $this->ppdg->get_setting( 'btn_type' ) : 'checkout',
	    'btn_height'	 => $this->ppdg->get_setting( 'btn_height' ) !== false ? $this->ppdg->get_setting( 'btn_height' ) : 'small',
	    'btn_width'		 => $this->ppdg->get_setting( 'btn_width' ) !== false ? $this->ppdg->get_setting( 'btn_width' ) : 0,
	    'btn_layout'	 => $this->ppdg->get_setting( 'btn_layout' ) !== false ? $this->ppdg->get_setting( 'btn_layout' ) : 'horizontal',
	    'btn_color'		 => $this->ppdg->get_setting( 'btn_color' ) !== false ? $this->ppdg->get_setting( 'btn_color' ) : 'gold',
	), $args ) );

        //Lets check the digital item URL.
	if ( ! empty( $url ) ) {
	    $url = base64_encode( $url );
	}

        //The button ID
	$button_id = 'paypal_button_' . count( self::$payment_buttons );
	self::$payment_buttons[] = $button_id;

	$quantity = empty( $quantity ) ? 1 : $quantity;

	$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); //Create key using the item name.

	$trans_data = array(
	    'price'			 => $price,
	    'currency'		 => $currency,
	    'quantity'		 => $quantity,
	    'url'			 => $url,
	    'custom_quantity'	 => $custom_quantity,
	);

	set_transient( $trans_name, $trans_data, 2 * 3600 );

	$is_live = $this->ppdg->get_setting( 'is_live' );

	if ( $is_live ) {
	    $env		 = 'production';
	    $client_id	 = $this->ppdg->get_setting( 'live_client_id' );
	} else {
	    $env		 = 'sandbox';
	    $client_id	 = $this->ppdg->get_setting( 'sandbox_client_id' );
	}

	if ( empty( $client_id ) ) {
	    $err_msg = sprintf( __( "Please enter %s Client ID in the settings.", 'wp-express-checkout' ), $env );
	    $err	 = $this->show_err_msg( $err_msg );
	    return $err;
	}

	$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );

	if ( isset( $btn_sizes[ $btn_height ] ) ) {
	    $btn_height = $btn_sizes[ $btn_height ];
	} else {
	    $btn_height = 25;
	}

	$output = '';

	$output .= '<div style="position: relative;" class="wp-ppec-shortcode-container" data-ppec-button-id="' . $button_id . '">'
	. '<div class="wp-ppec-overlay" data-ppec-button-id="' . $button_id . '">'
	. '<div class="wp-ppec-spinner">'
	. '<div></div>'
	. '<div></div>'
	. '<div></div>'
	. '<div></div>'
	. '</div>'
	. '</div>';

	if ( count( self::$payment_buttons ) <= 1 ) {
	    // insert the below only once on a page
	    ob_start();

		$frontVars = array(
			'str' => array(
				'errorOccurred'    => __( 'Error occurred', 'wp-express-checkout' ),
				'paymentFor'       => __( 'Payment for', 'wp-express-checkout' ),
				'enterQuantity'    => __( 'Please enter valid quantity', 'wp-express-checkout' ),
				'paymentCompleted' => __( 'Payment Completed', 'wp-express-checkout' ),
				'redirectMsg'      => __( 'You are now being redirected to the order summary page.', 'wp-express-checkout' ),
			),
			'ajaxUrl' => get_admin_url() . 'admin-ajax.php'
		);
		?>
	    <script>var ppecFrontVars = <?php echo json_encode( $frontVars ); ?>;</script>
	    <?php
	    $args			 = array();
	    $args[ 'client-id' ]	 = $client_id;
	    $args[ 'intent' ]	 = 'capture';
	    $disabled_funding	 = $this->ppdg->get_setting( 'disabled_funding' );
	    if ( ! empty( $disabled_funding ) ) {
		$arg = '';
		foreach ( $disabled_funding as $funding ) {
		    $arg .= $funding . ',';
		}
		$arg				 = rtrim( $arg, ',' );
		$args[ 'disable-funding' ]	 = $arg;
	    }
	    //check if cards aren't disabled globally first
	    if ( ! in_array( 'card', $disabled_funding ) ) {
		$disabled_cards = $this->ppdg->get_setting( 'disabled_cards' );
		if ( ! empty( $disabled_cards ) ) {
		    $arg = '';
		    foreach ( $disabled_cards as $card ) {
			$arg .= $card . ',';
		    }
		    $arg			 = rtrim( $arg, ',' );
		    $args[ 'disable-card' ]	 = $arg;
		}
	    }
	    $script_url	 = add_query_arg( $args, 'https://www.paypal.com/sdk/js' );
	    printf( '<script src="%s"></script>', $script_url );
	    ?>
	    <div id="wp-ppdg-dialog-message" title="">
	        <p id="wp-ppdg-dialog-msg"></p>
	    </div>
	    <?php
	    $output		 .= ob_get_clean();
	}

	//custom quantity
	if ( $custom_quantity ) {
	    $output	 .= '<label>Quantity:</label>';
	    $output	 .= '<input id="wp-ppec-custom-quantity" data-ppec-button-id="' . $button_id . '" type="number" name="custom-quantity" class="wp-ppec-input wp-ppec-custom-quantity" min="1" value="' . $quantity . '">';
	    $output	 .= '<div class="wp-ppec-form-error-msg"></div>';
	}

	$output .= '<div class = "wp-ppec-button-container">';

	$output .= sprintf( '<div id="%s" style="max-width:%s"></div>', $button_id, $btn_width ? $btn_width . 'px;' : '' );

	$output .= '</div>';

	$data = array(
	    'id'		 => $button_id,
	    'env'		 => $env,
	    'client_id'		 => $client_id,
	    'price'		 => $price,
	    'quantity'		 => $quantity,
	    'custom_quantity'	 => $custom_quantity,
	    'currency'		 => $currency,
	    'name'		 => $name,
	    'btnStyle'		 => array(
		'height' => $btn_height,
		'shape'	 => $btn_shape,
		'label'	 => $btn_type,
		'color'	 => $btn_color,
		'layout' => $btn_layout,
	    ),
	);


	$output .= '<script>jQuery(document).ready(function() {new ppecHandler(' . json_encode( $data ) . ')});</script>';

	$output .= '</div>';

	return $output;
    }

	/**
	 * Thank You page shortcode.
	 *
	 * @return string
	 */
	public function shortcode_wpec_thank_you() {

		$error_message = '';

		if ( !isset ( $_GET['order_id'] ) ){
			$error_message .= '<p>' . __( 'This page is used to show the transaction result after a customer makes a payment.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p>' . __( 'It will dynamically show the order details to the customers when they are redirected here after a payment. Do not access this page directly.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p class="wpec-error-message">' . __( 'Error! Order ID value is missing in the URL.', 'wp-express-checkout' ) . '</p>';
			return $error_message;
		}

		if ( !isset ( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'thank_you_url' . $_GET['order_id'] ) ) {
			$error_message .= '<p>' . __( 'This page is used to show the transaction result after a customer makes a payment.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p>' . __( 'It will dynamically show the order details to the customers when they are redirected here after a payment. Do not access this page directly.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p class="wpec-error-message">' . __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' ) . '</p>';
			return $error_message;
		}

		//Retrieve the order data.
		$order_id = (int) $_GET['order_id'];
		$order = get_post_meta( $order_id, 'ppec_payment_details', true );

		if ( empty( $order ) ) {
			return __( 'Error! Incorrect order ID. Could not find that order in the orders table.', 'wp-express-checkout' );
		}

		if ( 'COMPLETED' !== $order['state'] ) {
			return printf( __( 'Payment is not approved. Status: %s', 'wp-express-checkout' ), $order['state'] );
		}

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $order['item_name'] );
		$trans      = get_transient( $trans_name );
		$url        = $trans['url'];

		$thank_you_msg = '';
		$thank_you_msg  .= '<div class="wpec_thank_you_message">';
		$thank_you_msg  .= '<p>' . __( 'Thank you for your purchase.', 'wp-express-checkout' ) . '</p>';

		$thank_you_msg  .= '<p>' . __( 'Your purchase details are below:', 'wp-express-checkout' ) . '</p>';
		$thank_you_msg  .= '<p>' . '{product_details}' . '</p>';
		$thank_you_msg  .= '<p>' . __( 'Transaction ID: ', 'wp-express-checkout' ) . '{transaction_id}' . '</p>';

		if (!empty ($url)){
			$click_here_str = sprintf( __( 'Please <a href="%s">click here</a> to download the file.', 'wp-express-checkout' ), base64_decode( $url ) );
			$thank_you_msg .= '<p>' . $click_here_str . '</p>';
		}

		$thank_you_msg .= '</div>';//end .wpec_thank_you_message

		//Apply the dynamic tags
		$thank_you_msg = WPEC_Utility_Functions::replace_dynamic_order_tags($thank_you_msg, $order_id);

		//Trigger the filter
		$thank_you_msg  = apply_filters( 'wpec_thank_you_message', $thank_you_msg );

		return $thank_you_msg;
	}
}
