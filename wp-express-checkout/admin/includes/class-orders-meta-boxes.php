<?php

class WPEC_Orders_Metaboxes {

	var $WPECAdmin;
	var $WPEC_Main;

	public function __construct() {
		$this->WPECAdmin = WPEC_Admin::get_instance();
		$this->WPEC_Main = WPEC_Main::get_instance();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );
		add_action( 'save_post_' . OrdersWPEC::PTYPE, array( $this, 'save' ), 10, 3 );
	}

	function add_meta_boxes() {
		add_meta_box( 'wpec_order_items', __( 'Order Summary', 'wp-express-checkout' ), array( $this, 'display_summary_meta_box' ), OrdersWPEC::PTYPE, 'normal', 'high' );
	}

	function display_summary_meta_box( $post ) {
		global $post;

		if ( OrdersWPEC::PTYPE != $post->post_type ) {
			return;
		}

		$order = OrdersWPEC::retrieve( $_GET['post'] );

		?>
		<style type="text/css">
			#admin-order-summary tbody td{
				padding-top: 10px;
				padding-bottom: 10px;
			}
			#admin-order-summary{
				margin-bottom: 20px;
			}
		</style>
		<?php

		$table = new WPEC_Admin_Order_Summary_Table( $order );
		$table->show( array(
			'class' => 'widefat',
			'id' => 'admin-order-summary'
		) );
	}

	function save( $post_id, $post, $update ) {
		if ( ! isset( $_POST['action'] ) ) {
			// this is probably not edit or new post creation event.
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $post_id ) ) {
			return;
		}

	}

	public function remove_meta_boxes() {
		remove_meta_box( 'submitdiv', OrdersWPEC::PTYPE, 'side' );
		remove_meta_box( 'slugdiv', OrdersWPEC::PTYPE, 'normal' );
		remove_meta_box( 'authordiv', OrdersWPEC::PTYPE, 'normal');
	}

}

class WPEC_Admin_Order_Summary_Table extends WPEC_Order_Summary_Table{

	protected function header( $data ){

		$cells = array(
			__( 'Order Summary', 'wp-express-checkout' ),
			__( 'Price', 'wp-express-checkout' ),
			__( 'Affects', 'wp-express-checkout' ),
		);

		return $this->html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}

	protected function footer( $data ){

		$cells = array(
			__( 'Total', 'wp-express-checkout' ),
			WPEC_Utility_Functions::price_format( $this->order->get_total(), $this->currency ),
			''
		);

		return $this->html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}

	protected function row( $item ){
		$ptype_obj = get_post_type_object( get_post_type( $item['post_id'] ) );
		$item_link = '';
		$quantity  = '';

		if ( $ptype_obj->public ) {
			$item_link = '<a href="' . esc_url( get_edit_post_link( $item['post_id'] ) ) . '">' . get_the_title( $item['post_id'] ) . '</a>';
		}

		if ( $item['quantity'] > 1 ) {
			$quantity = $this->html( 'strong', sprintf( __( 'x %s', 'wp-express-checkout' ), $item['quantity'] ) );
		}

		$cells = array(
			$item['name'] . '&nbsp;' . $quantity,
			WPEC_Utility_Functions::price_format( $item['price'] * $item['quantity'], $this->currency ),
			$item_link
		);

		return $this->html( 'tr', array(), $this->cells( $cells ) );
	}

}

/**
 * Used to construct and display an order summary table for an order
 */
class WPEC_Order_Summary_Table {

	protected $order, $currency;

	public function __construct( $order, $args = array() ) {

		$this->order = $order;
		$this->currency = $order->get_currency();

		$this->args = wp_parse_args( $args, array(
			'wrapper_html' => 'table',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
			'row_html' => 'tr',
			'cell_html' => 'td',
		) );

	}

	protected function table( $items, $attributes = array(), $args = array() ) {

		$args = wp_parse_args( $args, array(
			'wrapper_html' => 'table',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
		) );

		extract( $args );

		$table_body = '';

		$product_items = array();
		$other_items   = array();

		foreach ( $items as $item ) {
			$ptype_obj = get_post_type_object( get_post_type( $item['post_id'] ) );
			if ( $ptype_obj->public ) {
				$product_items[] = $item;
			} else {
				$other_items[] = $item;
			}
		}

		$table_body .= $this->html( $header_wrapper, array(), $this->header( $items ) );
		$table_body .= $this->html( $body_wrapper, array(), $this->rows( $product_items ) );
		$table_body .= $this->html( $header_wrapper, array(), $this->subtotal( $product_items ) );
		$table_body .= $this->html( $body_wrapper, array(), $this->rows( $other_items ) );
		$table_body .= $this->html( $footer_wrapper, array(), $this->footer( $items ) );

		return $this->html( $wrapper_html, $attributes, $table_body );

	}

	protected function header( $data ) {}

	protected function rows( array $items ) {

		$table_body = '';
		foreach ( $items as $item ) {
			$table_body .= $this->row( $item );
		}

		return $table_body;

	}

	protected function cells( $cells, $type = 'td' ) {

		$output = '';
		foreach ( $cells as $value ) {
			$output .= $this->html( $type, array(), $value );
		}
		return $output;

	}

	public function show( $attributes = array() ) {
		$items = $this->order->get_items();
		echo $this->table( $items, $attributes, $this->args );
	}

	protected function subtotal( $items ) {
		$subtotal = 0;
		foreach ( $items as $item ) {
			$subtotal += $item['price'] * $item['quantity'];
		}

		$cells = array(
			__( 'Subtotal', 'wp-express-checkout' ),
			WPEC_Utility_Functions::price_format( $subtotal, $this->currency ),
			''
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

	protected function footer( $items ) {

		$cells = array(
			__( 'Total', 'wp-express-checkout' ),
			WPEC_Utility_Functions::price_format( $this->order->get_total(), $this->currency ),
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

	protected function row( $item ) {
		$quantity  = '';
		if ( $item['quantity'] > 1 ) {
			$quantity = $this->html( 'strong', sprintf( __( 'x %s', 'wp-express-checkout' ), $item['quantity'] ) );
		}

		$cells = array(
			$item['name'] . '&nbsp;' . $quantity,
			WPEC_Utility_Functions::price_format( $item['price'] * $item['quantity'], $this->currency ),
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells ) );
	}

	protected function html( $tag ) {
		static $SELF_CLOSING_TAGS = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta' );

		$args = func_get_args();

		$tag = array_shift( $args );

		if ( is_array( $args[0] ) ) {
			$closing = $tag;
			$attributes = array_shift( $args );
			foreach ( $attributes as $key => $value ) {
				if ( false === $value ) {
					continue;
				}

				if ( true === $value ) {
					$value = $key;
				}

				$tag .= ' ' . $key . '="' . esc_attr( $value ) . '"';
			}
		} else {
			list( $closing ) = explode( ' ', $tag, 2 );
		}

		if ( in_array( $closing, $SELF_CLOSING_TAGS ) ) {
			return "<{$tag} />";
		}

		$content = implode( '', $args );

		return "<{$tag}>{$content}</{$closing}>";
	}

}
