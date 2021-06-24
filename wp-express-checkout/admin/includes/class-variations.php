<?php

namespace WP_Express_Checkout;

class Variations {

	public $groups     = array();
	public $variations = array();
	public $prod_id    = false;

	public function __construct( $prod_id ) {
		$this->prod_id = $prod_id;
		$this->_get_variations();
	}

	private function _get_variations() {
		$variations_groups = get_post_meta( $this->prod_id, 'wpec_variations_groups', true );
		if ( empty( $variations_groups ) ) {
			return false;
		}
		$this->groups = $variations_groups;
		foreach ( $variations_groups as $grp_id => $group ) {
			$variations_names = get_post_meta( $this->prod_id, 'wpec_variations_names', true );
			if ( ! empty( $variations_names ) ) {
				$variations_prices_orig                = get_post_meta( $this->prod_id, 'wpec_variations_prices', true );
				$variations_prices                     = apply_filters( 'wpec_variations_prices_filter', $variations_prices_orig, $this->prod_id );
				$variations_urls                       = get_post_meta( $this->prod_id, 'wpec_variations_urls', true );
				$variations_opts                       = get_post_meta( $this->prod_id, 'wpec_variations_opts', true );
				$this->variations[ $grp_id ]['names']  = $variations_names[ $grp_id ];
				$this->variations[ $grp_id ]['prices'] = $variations_prices[ $grp_id ];
				$this->variations[ $grp_id ]['urls']   = $variations_urls[ $grp_id ];
				$this->variations[ $grp_id ]['opts']   = isset( $variations_opts[ $grp_id ] ) ? $variations_opts[ $grp_id ] : 0;
			}
		}
	}

	public function get_variation( $grp_id, $var_id ) {

		if ( empty( $this->variations[ $grp_id ] ) ) {
			return false;
		}
		if ( empty( $this->variations[ $grp_id ]['names'][ $var_id ] ) ) {
			return false;
		}
		$var = array(
			'grp_id'     => $grp_id,
			'id'         => $var_id,
			'group_name' => $this->groups[ $grp_id ],
			'name'       => $this->variations[ $grp_id ]['names'][ $var_id ],
			'price'      => $this->variations[ $grp_id ]['prices'][ $var_id ],
			'url'        => $this->variations[ $grp_id ]['urls'][ $var_id ],
			'opts'       => isset( $this->variations[ $grp_id ]['opts'][ $var_id ] ) ? $this->variations[ $grp_id ]['opts'][ $var_id ] : array(),
		);
		return $var;
	}

	/**
	 * Adds callback to create a new order event.
	 */
	public static function init() {
		add_action( 'wpec_create_order', array( __CLASS__, 'add_variations_to_order' ), 20, 3 );
	}

	/**
	 * Adds purchased variation to the order.
	 *
	 * @param Order $order   The order object.
	 * @param array      $payment The raw order data retrieved via API.
	 * @param array      $data    The purchase data generated on a client side.
	 */
	public static function add_variations_to_order( $order, $payment, $data ) {
		if ( empty( $data['variations']['applied'] ) ) {
			return;
		}

		// Variations depended on the product.
		$product_item = $order->get_item( Products::$products_slug );

		// we got variations posted. Let's get variations from product.
		$v = new self( $product_item['post_id'] );
		if ( ! empty( $v->variations ) && $product_item ) {
			// there are variations configured for the product.
			$posted_v = $data['variations']['applied'];
			foreach ( $posted_v as $grp_id => $var_id ) {
				$var = $v->get_variation( $grp_id, $var_id );
				if ( ! empty( $var ) ) {
					$order->add_item(
						'variation',
						$var['group_name'] . ' - ' . $var['name'],
						$var['price'],
						$product_item['quantity'],
						$product_item['post_id'],
						false,
						array(
							'id'     => $var['id'],
							'grp_id' => $var['grp_id'],
							'url'    => $var['url'],
						)
					);
				}
			}
		}
	}

}
