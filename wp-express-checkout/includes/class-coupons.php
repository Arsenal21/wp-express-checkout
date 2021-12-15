<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Admin\Coupons_List;

class Coupons {

	var $POST_SLUG = 'wpec_coupons';

	function __construct() {
		add_action( 'init', array( $this, 'init_handler' ) );
		add_action( 'wpec_create_order', array( $this, 'add_discount_to_order' ), 30, 3 );
		add_action( 'wpec_payment_completed', array( $this, 'redeem_coupon' ), 10, 2 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			if ( wp_doing_ajax() ) {
				add_action( 'wp_ajax_wpec_check_coupon', array( $this, 'frontend_check_coupon' ) );
				add_action( 'wp_ajax_nopriv_wpec_check_coupon', array( $this, 'frontend_check_coupon' ) );
			}
		}
	}

	static function get_coupon( $coupon_code ) {
		$out = array(
			'code'  => $coupon_code,
			'valid' => true,
		);
		//let's find coupon
		$coupon = get_posts(
			array(
				'meta_key'       => 'wpec_coupon_code',
				'meta_value'     => $coupon_code,
				'posts_per_page' => 1,
				'offset'         => 0,
				'post_type'      => 'wpec_coupons',
			)
		);
		wp_reset_postdata();
		if ( empty( $coupon ) ) {
			//coupon not found
			$out['valid']   = false;
			$out['err_msg'] = __( 'Coupon not found.', 'wp-express-checkout' );
			return $out;
		}
		$coupon = $coupon[0];
		//check if coupon is active
		if ( ! get_post_meta( $coupon->ID, 'wpec_coupon_active', true ) ) {
			$out['valid']   = false;
			$out['err_msg'] = __( 'Coupon is not active.', 'wp-express-checkout' );
			return $out;
		}
		//check if coupon start date has come
		$start_date = get_post_meta( $coupon->ID, 'wpec_coupon_start_date', true );
		if ( empty( $start_date ) || strtotime( $start_date ) > time() ) {
			$out['valid']   = false;
			$out['err_msg'] = __( 'Coupon is not available yet.', 'wp-express-checkout' );
			return $out;
		}
		//check if coupon has expired
		$exp_date = get_post_meta( $coupon->ID, 'wpec_coupon_exp_date', true );
		if ( ! empty( $exp_date ) && strtotime( $exp_date ) < time() ) {
			$out['valid']   = false;
			$out['err_msg'] = __( 'Coupon has expired.', 'wp-express-checkout' );
			return $out;
		}
		//check if redemption limit is reached
		$red_limit = get_post_meta( $coupon->ID, 'wpec_coupon_red_limit', true );
		$red_count = get_post_meta( $coupon->ID, 'wpec_coupon_red_count', true );
		if ( ! empty( $red_limit ) && intval( $red_count ) >= intval( $red_limit ) ) {
			$out['valid']   = false;
			$out['err_msg'] = __( 'Coupon redemption limit is reached.', 'wp-express-checkout' );
			return $out;
		}
		$out['id']           = $coupon->ID;
		$out['discount']     = get_post_meta( $coupon->ID, 'wpec_coupon_discount', true );
		$out['discountType'] = get_post_meta( $coupon->ID, 'wpec_coupon_discount_type', true );
		return $out;
	}

	static function is_coupon_allowed_for_product( $coupon_id, $prod_id ) {
		//check if coupon is only availabe for specific products
		$only_for_allowed_products = get_post_meta( $coupon_id, 'wpec_coupon_only_for_allowed_products', true );
		if ( $only_for_allowed_products ) {
			$allowed_products = get_post_meta( $coupon_id, 'wpec_coupon_allowed_products', true );
			if ( is_array( $allowed_products ) && ! in_array( $prod_id, $allowed_products ) ) {
				return false;
			}
		}
		return true;
	}

	function frontend_check_coupon() {
		$out = array();
		if ( empty( $_POST['coupon_code'] ) ) {
			$out['success'] = false;
			$out['msg']     = __( 'Empty coupon code', 'wp-express-checkout' );
			wp_send_json( $out );
		}
		$coupon_code = strtoupper( $_POST['coupon_code'] );

		$coupon = self::get_coupon( $coupon_code );

		if ( ! $coupon['valid'] ) {
			$out['success'] = false;
			$out['msg']     = $coupon['err_msg'];
			wp_send_json( $out );
		}

		$prod_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $prod_id ) ) {
			$out['success'] = false;
			$out['msg']     = __( 'No product ID specified.', 'wp-express-checkout' );
			wp_send_json( $out );
		}
		if ( ! self::is_coupon_allowed_for_product( $coupon['id'], $prod_id ) ) {
			$out['success'] = false;
			$out['msg']     = __( 'Coupon is not allowed for this product.', 'wp-express-checkout' );
			wp_send_json( $out );
		}

		$curr = isset( $_POST['curr'] ) ? $_POST['curr'] : '';

		$discount      = $coupon['discount'];
		$discount_type = $coupon['discountType'];

		$out['success']      = true;
		$out['code']         = $coupon_code;
		$out['discount']     = $discount;
		$out['discountType'] = $discount_type;
		$out['discountStr']  = $coupon_code . ': - ' . ( $discount_type === 'perc' ? $discount . '%' : Utils::price_format( $discount, $curr ) );
		wp_send_json( $out );
	}

	function init_handler() {
		$args = array(
			'supports'            => array( '' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
		);
		register_post_type( $this->POST_SLUG, $args );

		if ( isset( $_POST['wpec_coupon'] ) ) {
			$this->save_coupon();
		}
	}

	function add_menu() {
		add_submenu_page( 'edit.php?post_type=' . Products::$products_slug, __( 'Coupons', 'wp-express-checkout' ), __( 'Coupons', 'wp-express-checkout' ), Main::get_instance()->get_setting( 'access_permission' ), 'wpec-coupons', array( $this, 'display_coupons_menu_page' ) );
	}

	function save_settings() {
		check_admin_referer( 'wpec-coupons-settings' );
		$settings                    = get_option( 'ppdg-settings' );
		$opts                        = $_POST['wpec_coupons_opts'];
		$settings['coupons_enabled'] = isset( $opts['coupons_enabled'] ) ? 1 : 0;
		unregister_setting( 'ppdg-settings-group', 'ppdg-settings' );
		update_option( 'ppdg-settings', $settings );
		set_transient( 'wpec_coupons_admin_notice', __( 'Settings updated.', 'wp-express-checkout' ), 60 * 60 );
	}

	function display_coupons_menu_page() {

		if ( isset( $_POST['wpec_coupons_opts'] ) ) {
			$this->save_settings();
		}

		if ( isset( $_GET['action'] ) ) {
			$action = $_GET['action'];
			if ( $action === 'wpec_add_edit_coupon' ) {
				//coupon add or edit content
				$this->display_coupon_add_edit_page();
				return;
			}
			if ( $action === 'wpec_delete_coupon' ) {
				//coupon delete action
				$this->delete_coupon();
			}
		}

		$msg = get_transient( 'wpec_coupons_admin_notice' );

		if ( $msg !== false ) {
			delete_transient( 'wpec_coupons_admin_notice' );
			?>
		<div class="notice notice-success">
			<p><?php echo $msg; ?></p>
		</div>
			<?php
		}

		$msg = get_transient( 'wpec_coupons_admin_error' );

		if ( $msg !== false ) {
			delete_transient( 'wpec_coupons_admin_error' );
			?>
		<div class="notice notice-error">
			<p><?php echo $msg; ?></p>
		</div>
			<?php
		}

		$wpec_main       = Main::get_instance();
		$coupons_enabled = $wpec_main->get_setting( 'coupons_enabled' );

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		$coupons_tbl = new Coupons_List();
		$coupons_tbl->prepare_items();
		?>
	<style>
		th#id {
		width: 10%;
		}
	</style>
	<div class="wrap">
		<h2><?php _e( 'Coupons', 'wp-express-checkout' ); ?></h2>
				<div id="poststuff"><div id="post-body">
			<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e( 'Coupon Settings', 'wp-express-checkout' ); ?></label></h3>
			<div class="inside">
				<form method="post">
				<input type="hidden" name="wpec_coupons_opts[_save-settings]" value="1">
				<table class="form-table">
					<tr>
					<th scope="row"><?php _e( 'Enable Coupons', 'wp-express-checkout' ); ?></th>
					<td>
						<input type="checkbox" name="wpec_coupons_opts[coupons_enabled]"<?php echo $coupons_enabled ? ' checked' : ''; ?>>
						<p class="description"><?php _e( 'Enables the discount coupon functionality.', 'wp-express-checkout' ); ?></p>
					</td>
					</tr>
				</table>
				<?php
				wp_nonce_field( 'wpec-coupons-settings' );
				submit_button( __( 'Save Settings', 'wp-express-checkout' ) );
				?>
				</form>
			</div>
			</div>
		</div></div>
		<h2><?php _e( 'Coupons', 'wp-express-checkout' ); ?> <a class="page-title-action" href="?post_type=<?php echo esc_attr( Products::$products_slug ); ?>&page=wpec-coupons&action=wpec_add_edit_coupon"><?php _e( 'Add a Coupon', 'wp-express-checkout' ); ?></a></h2>
		<?php $coupons_tbl->display(); ?>
	</div>
		<?php
	}

	function display_coupon_add_edit_page() {

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_register_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui' );

		$coupon_id = isset( $_GET['wpec_coupon_id'] ) ? absint( $_GET['wpec_coupon_id'] ) : false;
		$is_edit   = $coupon_id ? true : false;
		if ( $is_edit ) {
			if ( is_null( get_post( $coupon_id ) ) ) {
				echo 'error';
				return false;
			}
			$coupon = array(
				'id'                        => $coupon_id,
				'code'                      => get_post_meta( $coupon_id, 'wpec_coupon_code', true ),
				'active'                    => get_post_meta( $coupon_id, 'wpec_coupon_active', true ),
				'discount'                  => get_post_meta( $coupon_id, 'wpec_coupon_discount', true ),
				'discount_type'             => get_post_meta( $coupon_id, 'wpec_coupon_discount_type', true ),
				'red_limit'                 => get_post_meta( $coupon_id, 'wpec_coupon_red_limit', true ),
				'red_count'                 => get_post_meta( $coupon_id, 'wpec_coupon_red_count', true ),
				'start_date'                => get_post_meta( $coupon_id, 'wpec_coupon_start_date', true ),
				'exp_date'                  => get_post_meta( $coupon_id, 'wpec_coupon_exp_date', true ),
				'only_for_allowed_products' => get_post_meta( $coupon_id, 'wpec_coupon_only_for_allowed_products', true ),
				'allowed_products'          => get_post_meta( $coupon_id, 'wpec_coupon_allowed_products', true ),
			);
		}
		//generate array with all products
		$posts = get_posts(
			array(
				'post_type'   => untrailingslashit( Products::$products_slug ),
				'post_status' => 'publish',
				'numberposts' => -1,
			// 'order'    => 'ASC'
			)
		);
		$prod_inputs = '';
		$input_tpl   = '<label><input type="checkbox" name="wpec_coupon[allowed_products][]" value="%s"%s> %s</label>';
		if ( $posts ) {
			foreach ( $posts as $the_post ) {
				$checked = '';
				if ( ! empty( $coupon ) && is_array( $coupon['allowed_products'] ) ) {
					if ( in_array( $the_post->ID, $coupon['allowed_products'] ) ) {
						$checked = ' checked';
					}
				}
				$prod_inputs .= sprintf( $input_tpl, $the_post->ID, $checked, $the_post->post_title );
				$prod_inputs .= '<br>';
			}
		} else {
			$prod_inputs = __( 'No products created yet.', 'wp-express-checkout' );
		}
		wp_reset_postdata();
		?>
	<div class="wrap">
		<h2><?php empty( $coupon_id ) ? _e( 'Add Coupon', 'wp-express-checkout' ) : _e( 'Edit Coupon', 'wp-express-checkout' ); ?></h2>
		<form method="post">
		<table class="form-table">
			<tr>
			<th scope="row"><?php _e( 'Active', 'wp-express-checkout' ); ?></th>
			<td>
				<input type="checkbox" name="wpec_coupon[active]"<?php echo ( ! $is_edit ) || ( $is_edit && $coupon['active'] ) ? 'checked' : ''; ?>>
				<p class="description"><?php _e( 'Use this to enable/disable this coupon.', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<?php if ( $is_edit ) { ?>
				<tr>
				<th scope="row"><?php _e( 'Coupon ID', 'wp-express-checkout' ); ?></th>
				<td>
					<input type="hidden" name="wpec_coupon_id" value="<?php echo $coupon_id; ?>">
				<?php echo $coupon_id; ?>
					<p class="description"><?php _e( 'Coupon ID. This value cannot be changed.', 'wp-express-checkout' ); ?></p>
				</td>
				</tr>
			<?php } ?>
			<tr>
			<th scope="row"><?php _e( 'Coupon Code', 'wp-express-checkout' ); ?></th>
			<td>
				<input type="text" name="wpec_coupon[code]" value="<?php echo $is_edit ? $coupon['code'] : ''; ?>">
				<p class="description"><?php _e( 'Coupon code that you can share with your customers. Example: GET10OFF', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e( 'Discount', 'wp-express-checkout' ); ?></th>
			<td>
				<input style="vertical-align: middle;" type="text" name="wpec_coupon[discount]" value="<?php echo $is_edit ? $coupon['discount'] : ''; ?>">
				<select name="wpec_coupon[discount_type]">
				<option value="perc"<?php echo $is_edit && $coupon['discount_type'] === 'perc' ? ' selected' : ''; ?>><?php _e( 'Percent (%)', 'wp-express-checkout' ); ?></option>
				<option value="fixed"<?php echo $is_edit && $coupon['discount_type'] === 'fixed' ? ' selected' : ''; ?>><?php _e( 'Fixed amount', 'wp-express-checkout' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Select discount amount and type. Enter a numeric value only. Example: 25', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e( 'Redemption Limit', 'wp-express-checkout' ); ?></th>
			<td>
				<input type="number" name="wpec_coupon[red_limit]"value="<?php echo $is_edit ? $coupon['red_limit'] : 0; ?>">
				<p class="description"><?php _e( 'Set max number of coupons available for redemption. Put 0 to make it unlimited.', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e( 'Redemption Count', 'wp-express-checkout' ); ?></th>
			<td>
				<input type="number" name="wpec_coupon[red_count]"value="<?php echo $is_edit ? $coupon['red_count'] : 0; ?>">
				<p class="description"><?php _e( 'Number of already redeemed coupons.', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e( 'Start Date', 'wp-express-checkout' ); ?></th>
			<td>
				<input class="datepicker-input" type="text" name="wpec_coupon[start_date]"value="<?php echo $is_edit ? $coupon['start_date'] : date( 'Y-m-d' ); ?>">
				<p class="description"><?php _e( 'Start date when this coupon can be used.', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e( 'Expiry Date', 'wp-express-checkout' ); ?></th>
			<td>
				<input class="datepicker-input" type="text" name="wpec_coupon[exp_date]"value="<?php echo $is_edit ? $coupon['exp_date'] : 0; ?>">
				<p class="description"><?php _e( 'Date when this coupon will expire. Put 0 to disable expiry check.', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e( 'Coupon Availabe For:', 'wp-express-checkout' ); ?></th>
			<td>
				<label><input type="radio" name="wpec_coupon[only_for_allowed_products]" value="0"<?php echo ! $is_edit || ( $is_edit && ! $coupon['only_for_allowed_products'] ) ? ' checked' : ''; ?>> <?php _e( 'All products', 'wp-express-checkout' ); ?></label>
				<br>
				<label><input type="radio" name="wpec_coupon[only_for_allowed_products]" value="1"<?php echo $is_edit && $coupon['only_for_allowed_products'] ? ' checked' : ''; ?>> <?php _e( 'Specific Products Only', 'wp-express-checkout' ); ?></label>
				<p class="wpec-coupons-available-products"<?php echo ( $is_edit && ! $coupon['only_for_allowed_products'] ) || ( ! $is_edit ) ? ' style="display: none;"' : ''; ?>>
				<?php echo $prod_inputs; ?>
				</p>
				<p class="description"><?php _e( 'Choose availability of the coupon. You can specify which products coupon is available when "Specific Products Only" is selected.', 'wp-express-checkout' ); ?></p>
			</td>
			</tr>
			<?php
			do_action( 'wpec_admin_add_edit_coupon', $coupon_id );
			?>
		</table>
		<?php
		wp_nonce_field( 'wpec-add-edit-coupon' );
		submit_button( $is_edit ? __( 'Update Coupon', 'wp-express-checkout' ) : __( 'Create Coupon', 'wp-express-checkout' ) );
		?>
		</form>
	</div>
	<script>
		jQuery(document).ready(function ($) {
		$('.datepicker-input').datepicker({
			dateFormat: 'yy-mm-dd'
		});
		$('input[name="wpec_coupon[only_for_allowed_products]"]').change(function () {
			if ($(this).val() === "1") {
			$('.wpec-coupons-available-products').show();
			} else {
			$('.wpec-coupons-available-products').hide();
			}
		});
		});
	</script>
		<?php
	}

	function delete_coupon() {
		$coupon_id = isset( $_GET['wpec_coupon_id'] ) ? absint( $_GET['wpec_coupon_id'] ) : false;

		if ( ! $coupon_id ) {
			set_transient( 'wpec_coupons_admin_error', __( 'Can\'t delete coupon: coupon ID is not provided.', 'wp-express-checkout' ), 60 * 60 );
			return false;
		}
		$the_post = get_post( $coupon_id );
		if ( is_null( $the_post ) ) {
			// translators: %d is coupon ID
			set_transient( 'wpec_coupons_admin_error', sprintf( __( 'Can\'t delete coupon: coupon #%d not found.', 'wp-express-checkout' ), $coupon_id ), 60 * 60 );
			return false;
		}
		if ( $the_post->post_type !== $this->POST_SLUG ) {
			// translators: %d is coupon ID
			set_transient( 'wpec_coupons_admin_error', sprintf( __( 'Can\'t delete coupon: post #%d is not a coupon.', 'wp-express-checkout' ), $coupon_id ), 60 * 60 );
			return false;
		}
		check_admin_referer( 'delete-coupon_' . $coupon_id );
		wp_delete_post( $coupon_id, true );
		// translators: %d is coupon ID
		set_transient( 'wpec_coupons_admin_notice', sprintf( __( 'Coupon #%d has been deleted.', 'wp-express-checkout' ), $coupon_id ), 60 * 60 );
	}

	function save_coupon() {
		$coupon = $_POST['wpec_coupon'];

		$coupon_id = isset( $_POST['wpec_coupon_id'] ) ? absint( $_POST['wpec_coupon_id'] ) : false;

		$is_edit = $coupon_id ? true : false;

		check_admin_referer( 'wpec-add-edit-coupon' );

		$err_msg = array();

		$coupon['active'] = isset( $coupon['active'] ) ? 1 : 0;

		if ( empty( $coupon['code'] ) ) {
			$err_msg[] = __( 'Please enter coupon code.', 'wp-express-checkout' );
		}

		if ( empty( $coupon['discount'] ) ) {
			$err_msg[] = __( 'Please enter discount.', 'wp-express-checkout' );
		}

		if ( ! empty( $err_msg ) ) {
			foreach ( $err_msg as $msg ) {
				?>
		<div class="notice notice-error">
			<p><?php echo $msg; ?></p>
		</div>
				<?php
			}
			return false;
		}
		if ( ! $is_edit ) {
			$post                = array();
			$post['post_title']  = '';
			$post['post_status'] = 'publish';
			$post['content']     = '';
			$post['post_type']   = $this->POST_SLUG;
			$coupon_id           = wp_insert_post( $post );
		}

		if ( empty( $coupon['allowed_products'] ) ) {
			$coupon['allowed_products'] = array();
		}

		foreach ( $coupon as $key => $value ) {
			update_post_meta( $coupon_id, 'wpec_coupon_' . $key, $value );
		}
		do_action( 'wpec_admin_save_coupon', $coupon_id, $coupon );
		// translators: %s is coupon code
		set_transient( 'wpec_coupons_admin_notice', sprintf( $is_edit ? __( 'Coupon "%s" has been updated.', 'wp-express-checkout' ) : __( 'Coupon "%s" has been created.', 'wp-express-checkout' ), $coupon['code'] ), 60 * 60 );

		wp_safe_redirect( 'edit.php?post_type=' . Products::$products_slug . '&page=wpec-coupons' );
		exit;
	}

	/**
	 * Adds coupon discount to the order.
	 *
	 * @param Order $order   The order object.
	 * @param array      $payment The raw order data retrieved via API.
	 * @param array      $data    The purchase data generated on a client side.
	 */
	public function add_discount_to_order( $order, $payment, $data ) {
		// For some reason PayPal is missing 'discount' items from breakdown...
		// So code below does not work.
		//if ( isset( $payment['purchase_units'][0]['amount']['breakdown']['discount']['value'] ) ) {
			//$discount = $payment['purchase_units'][0]['amount']['breakdown']['discount']['value'];
		//}
		// Use JS data array instead.
		if ( empty( $data['couponCode'] ) ) {
			return;
		}
		$product_item = $order->get_item( Products::$products_slug );
		$item_id      = $product_item['post_id'];
		// Check the coupon code.
		$coupon = self::get_coupon( $data['couponCode'] );
		if ( true === $coupon['valid'] && self::is_coupon_allowed_for_product( $coupon['id'], $item_id ) ) {
			// Get the discount amount.
			if ( $coupon['discountType'] === 'perc' ) {
				$discount = Utils::round_price( $order->get_total() * ( $coupon['discount'] / 100 ) );
			} else {
				$discount = $coupon['discount'];
			}
			$coupon_code = $data['couponCode'];
			$order->add_item( 'coupon', sprintf( __( 'Coupon Code: %s', 'wp-express-checkout' ), $coupon_code ), abs( $discount ) * -1, 1, 0, false, array( 'code' => $coupon_code ) );
		}
	}

	/**
	 * Redeems coupon added to the order.
	 *
	 * @param array $payment  The raw payment data.
	 * @param int   $order_id The order ID.
	 */
	public function redeem_coupon( $payment, $order_id ) {
		try {
			$order = Orders::retrieve( $order_id );
		} catch ( Exception $exc ) {
			return;
		}
		$coupon_item = $order->get_item( 'coupon' );
		if ( $coupon_item ) {
			// Check the coupon code.
			$coupon = self::get_coupon( $coupon_item['meta']['coupon_code'] );
			// Redeem coupon count if needed.
			if ( $coupon && $coupon['valid'] ) {
				$curr_redeem_cnt = get_post_meta( $coupon['id'], 'wpec_coupon_red_count', true );
				$curr_redeem_cnt++;
				update_post_meta( $coupon['id'], 'wpec_coupon_red_count', $curr_redeem_cnt );
			}
		}
	}

}
