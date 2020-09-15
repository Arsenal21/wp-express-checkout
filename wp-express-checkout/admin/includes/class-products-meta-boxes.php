<?php

class PPECProductsMetaboxes {

	var $WPECAdmin;
	var $WPEC_Main;

	public function __construct() {
		$this->WPECAdmin = WPEC_Admin::get_instance();
		$this->WPEC_Main = WPEC_Main::get_instance();
		remove_post_type_support( PPECProducts::$products_slug, 'editor' );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		// products post save action.
		add_action( 'save_post_' . PPECProducts::$products_slug, array( $this, 'save_product_handler' ), 10, 3 );
	}

	function add_meta_boxes() {
		add_meta_box( 'wsp_content', __( 'Description', 'wp-express-checkout' ), array( $this, 'display_description_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'ppec_price_meta_box', __( 'Price', 'wp-express-checkout' ), array( $this, 'display_price_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'ppec_quantity_meta_box', __( 'Quantity', 'wp-express-checkout' ), array( $this, 'display_quantity_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'wpec_shipping_tax_meta_box', __( 'Shipping & Tax', 'wp-express-checkout' ), array( $this, 'display_shipping_tax_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'ppec_upload_meta_box', __( 'Download URL', 'wp-express-checkout' ), array( $this, 'display_upload_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'wpec_thumbnail_meta_box', __( 'Product Thumbnail', 'wp-express-checkout' ), array( $this, 'display_thumbnail_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'ppec_shortcode_meta_box', __( 'Shortcode', 'wp-express-checkout' ), array( $this, 'display_shortcode_meta_box' ), PPECProducts::$products_slug, 'side', 'default' );
		add_meta_box( 'wpec_appearance_meta_box', __( 'Appearance Related', 'wp-express-checkout' ), array( $this, 'display_appearance_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		add_meta_box( 'wpec_coupons_meta_box', __( 'Coupons Settings', 'wp-express-checkout' ), array( $this, 'display_coupons_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );

		// check if eMember installed
		if ( function_exists( 'wp_eMember_install' ) ) {
			//if it is, let's add metabox where admin can select membership level
			add_meta_box( 'wpec_emember_meta_box', __( 'WP eMember Membership Level', 'wp-express-checkout' ), array( $this, 'display_emember_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
		}
	}

	function display_description_meta_box( $post ) {
		esc_html_e( 'Add a description for your product.', 'wp-express-checkout' );
		echo '<br /><br />';
		wp_editor( $post->post_content, 'content', array( 'textarea_name' => 'content' ) );
	}

	function display_price_meta_box( $post ) {
		$current_price       = get_post_meta( $post->ID, 'ppec_product_price', true );
		$allow_custom_amount = get_post_meta( $post->ID, 'wpec_product_custom_amount', true );
		$step                = pow( 10, -intval( $this->WPEC_Main->get_setting( 'price_decimals_num' ) ) );
		?>
		<label><?php esc_html_e( 'Price', 'wp-express-checkout' ); ?></label>
		<br/>
		<input type="number" name="ppec_product_price" step="<?php echo esc_attr( $step ); ?>" min="0" value="<?php echo esc_attr( $current_price ); ?>">
		<p class="description"><?php esc_html_e( 'Item price. Enter numbers only, no need to put currency symbol. Example: 39.95', 'wp-express-checkout' ); ?></p>
		<label>
			<input type="checkbox" name="wpec_product_custom_amount" value="1" <?php checked( $allow_custom_amount ); ?>>
			<?php esc_html_e( 'Allow customers to enter amount', 'wp-express-checkout' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When checked, customers can change the amount they want to pay. You can set the initial amount using the field above.', 'wp-express-checkout' ); ?></p>
		<?php
	}

	function display_quantity_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'ppec_product_quantity', true );
		$current_val = empty( $current_val ) ? 1 : $current_val;

		$allow_custom_quantity = get_post_meta( $post->ID, 'ppec_product_custom_quantity', true );
		?>
		<label><?php esc_html_e( 'Quantity', 'wp-express-checkout' ); ?></label>
		<br/>
		<input type="number" name="ppec_product_quantity" value="<?php echo esc_attr( $current_val ); ?>">
		<p class="description"><?php esc_html_e( 'Item quantity.', 'wp-express-checkout' ); ?></p>
		<label>
			<input type="checkbox" name="ppec_product_custom_quantity" value="1"<?php echo $allow_custom_quantity ? ' checked' : ''; ?>>
			<?php esc_html_e( 'Allow customers to specify quantity', 'wp-express-checkout' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When checked, customers can enter quantity they want to buy. You can set initial quantity using field above.', 'wp-express-checkout' ); ?></p>
		<?php
	}

	public function display_shipping_tax_meta_box( $post ) {
		$current_shipping = get_post_meta( $post->ID, 'wpec_product_shipping', true );
		$current_tax      = get_post_meta( $post->ID, 'wpec_product_tax', true );
		$step             = pow( 10, -intval( $this->WPEC_Main->get_setting( 'price_decimals_num' ) ) );
		?>
		<div id="wpec_shipping_cost_container">
			<label><?php esc_html_e( 'Shipping Cost', 'wp-express-checkout' ); ?></label>
			<br />
			<input type="number" name="wpec_product_shipping" step="<?php echo esc_attr( $step ); ?>" min="0" value="<?php echo esc_attr( $current_shipping ); ?>">
			<p class="description">
		<?php
		esc_html_e( 'Enter numbers only. Example: 5.50', 'wp-express-checkout' );
		echo '<br>';
		esc_html_e( 'Leave it empty if you are not charging shipping cost.', 'wp-express-checkout' );
		?>
			</p>
		</div>
		<label><?php esc_html_e( 'Tax (%)', 'wp-express-checkout' ); ?></label>
		<br />
		<input type="number" min="0" step="any" name="wpec_product_tax" value="<?php echo esc_attr( $current_tax ); ?>">
		<p class="description">
		<?php
		esc_html_e( 'Enter tax (in percent) which will be added to the product price.', 'wp-express-checkout' );
		echo '<br>';
		esc_html_e( 'Leave it empty if you don\'t want to apply tax.', 'wp-express-checkout' );
		?>
		</p>
		<?php
	}

	public function display_upload_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'ppec_product_upload', true );
		?>
		<p><?php esc_html_e( 'URL of your product.', 'wp-express-checkout' ); ?></p>

		<div>
			<input id="ppec_product_upload" type="text" style="width: 100%" name="ppec_product_upload" value="<?php echo esc_attr( $current_val ); ?>" placeholder="https://..." />

			<p class="description">
				<?php esc_html_e( 'Manually enter a valid URL of the file in the text box below, or click "Select File" button to upload (or choose) the downloadable file.', 'wp-express-checkout' ); ?>
			</p>
		</div>
		<p>
			<input id="ppec_select_upload_btn" type="button" class="button" value="<?php esc_html_e( 'Select File', 'wp-express-checkout' ); ?>" />
			<?php do_action( 'wpec_product_upload_metabox_after_button', $post ); ?>
		</p>
		<div>
			<?php esc_html_e( 'Steps to upload a file or choose one from your media library:', 'wp-express-checkout' ); ?>
			<ol>
				<li><?php esc_html_e( 'Hit the "Select File" button.', 'wp-express-checkout' ); ?></li>
				<li><?php esc_html_e( 'Upload a new file or choose an existing one from your media library.', 'wp-express-checkout' ); ?></li>
				<li><?php esc_html_e( 'Click the "Insert" button, this will populate the uploaded file\'s URL in the above text field.', 'wp-express-checkout' ); ?></li>
			</ol>
		</div>
		<script>
			jQuery( document ).ready( function( $ ) {
				var ppec_selectFileFrame;
				// Run media uploader for file upload
				$( '#ppec_select_upload_btn' ).click( function( e ) {
					e.preventDefault();
					ppec_selectFileFrame = wp.media( {
						title: "<?php echo esc_js( __( 'Select File', 'wp-express-checkout' ) ); ?>",
						button: {
							text: "<?php echo esc_js( __( 'Insert', 'wp-express-checkout' ) ); ?>"
						},
						multiple: false
					} );
					ppec_selectFileFrame.open();
					ppec_selectFileFrame.on( 'select', function() {
						var attachment = ppec_selectFileFrame.state().get( 'selection' ).first().toJSON();

						$( '#ppec_product_upload' ).val( attachment.url );
					} );
					return false;
				} );
			} );
		</script>
		<?php
	}

	public function display_thumbnail_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_thumbnail', true );
		?>
<div>
	<input id="wpec_product_thumbnail" type="text" style="width: 100%" name="wpec_product_thumbnail" value="<?php echo esc_attr( $current_val ); ?>" placeholder="https://..." />

	<p class="description">
		<?php esc_html_e( 'Manually enter a valid URL, or click "Select Image" to upload (or choose) the file thumbnail image.', 'wp-express-checkout' ); ?>
	</p>
</div>
<p>
	<input id="wpec_select_thumbnail_btn" type="button" class="button" value="<?php esc_html_e( 'Select Image', 'wp-express-checkout' ); ?>" />
	<input id="wpec_remove_thumbnail_button" class="button" value="<?php esc_html_e( 'Remove Image', 'wp-express-checkout' ); ?>" type="button">
</p>
<div>
	<span id="wpec_admin_thumb_preview">
		<?php if ( $current_val ) { ?>
		<img id="wpec_thumbnail_image" src="<?php echo esc_url( $current_val ); ?>" style="max-width:200px;" />
		<?php } ?>
	</span>
</div>
<script>
jQuery(document).ready(function($) {
	var wpec_selectFileFrame;
	$('#wpec_select_thumbnail_btn').click(function(e) {
		e.preventDefault();
		wpec_selectFileFrame = wp.media({
			title: "<?php echo esc_js( __( 'Select Image', 'wp-express-checkout' ) ); ?>",
			button: {
				text: "<?php echo esc_js( __( 'Insert', 'wp-express-checkout' ) ); ?>"
			},
			multiple: false,
			library: {
				type: 'image'
			}
		});
		wpec_selectFileFrame.open();
		wpec_selectFileFrame.on('select', function() {
			var attachment = wpec_selectFileFrame.state().get('selection').first().toJSON();
			$('#wpec_thumbnail_image').remove();
			$('#wpec_admin_thumb_preview').html('<img id="wpec_thumbnail_image" src="' + attachment.url + '" style="max-width:200px;" />');
			$('#wpec_product_thumbnail').val(attachment.url);
		});
		return false;
	});
	$('#wpec_remove_thumbnail_button').click(function(e) {
		e.preventDefault();
		$('#wpec_thumbnail_image').remove();
		$('#wpec_product_thumbnail').val('');
	});
});
</script>
		<?php
	}


	function display_shortcode_meta_box( $post ) {
		?>
		<input type="text" name="ppec_product_shortcode" style="width: 100%;" class="ppec-select-on-click" readonly value="[wp_express_checkout product_id=&quot;<?php echo $post->ID; ?>&quot;]">
		<p class="description"><?php esc_html_e( 'Use this shortcode to display button for your product.', 'wp-express-checkout' ); ?></p>
		<?php
	}

	public function display_appearance_meta_box( $post ) {
		$button_type = get_post_meta( $post->ID, 'wpec_product_button_type', true );
		?>
		<fieldset>
			<legend><?php esc_html_e( 'Button Options', 'wp-express-checkout' ); ?></legend>
			<label><?php _e( 'Button Type', 'wp-express-checkout' ); ?></label>
			<br />
			<select name="wpec_product_button_type" id="wpec_product_button_type">
				<option value=""><?php esc_html_e( '-- Default --', 'wp-express-checkout' ); ?></option>
				<?php
				$options = array(
					'checkout' => __( 'Checkout', 'wp-express-checkout' ),
					'pay'      => __( 'Pay', 'wp-express-checkout' ),
					'paypal'   => __( 'PayPal', 'wp-express-checkout' ),
					'buynow'   => __( 'Buy Now', 'wp-express-checkout' ),
				);

				foreach ( $options as $key => $value ) {
					echo '<option value="' . $key . '"'. selected( $key, $button_type, false ) .'>' . $value . '</option>';
				}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'Select a button type for this product. By default, it will use the type specified in the General settings of this plugin.', 'wp-express-checkout' ); ?></p>
		</fieldset>
		<?php
	}

	public function display_coupons_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_coupons_setting', true );
		?>
<p><?php _e( 'Select how Coupons should be handled for this product.', 'wp-express-checkout' ); ?></p>
<label><input type="radio" name="wpec_product_coupons_setting" value="2" <?php echo ( $current_val === '2' || $current_val === '' ) ? ' checked' : ''; ?>><?php echo __( 'Use Global Setting', 'wp-express-checkout' ); ?> </label>
<label><input type="radio" name="wpec_product_coupons_setting" value="1" <?php echo ( $current_val === '1' ) ? ' checked' : ''; ?>><?php echo __( 'Enabled', 'wp-express-checkout' ); ?> </label>
<label><input type="radio" name="wpec_product_coupons_setting" value="0" <?php echo ( $current_val === '0' ) ? ' checked' : ''; ?>><?php echo __( 'Disabled', 'wp-express-checkout' ); ?> </label>
		<?php
	}

	public function display_emember_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_emember_level', true );

		$all_levels = dbAccess::findAll( WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE, ' id != 1 ', ' id DESC ' );
		$levels_str = '<option value="">(' . __( 'None', 'wp-express-checkout' ) . ')</option>' . "\r\n";

		foreach ( $all_levels as $level ) {
			$levels_str .= '<option value="' . $level->id . '"' . ( $level->id === $current_val ? ' selected' : '' ) . '>' . stripslashes( $level->alias ) . '</option>' . "\r\n";
		}
		?>
<p><?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?></p>
<select name="wpec_product_emember_level">
		<?php
		echo wp_kses(
			$levels_str,
			array(
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
			)
		);
		?>
</select>
		<?php
	}


	function save_product_handler( $post_id, $post, $update ) {
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

		$title = get_the_title( $post_id );
		if ( empty( $title ) ) {
			// Display error message of product name is empty.
			$text = __( 'Please specify product name.', 'wp-express-checkout' );
			$this->WPECAdmin->add_admin_notice( $text, 'error' );
		}

		// download url.
		$product_url = filter_input( INPUT_POST, 'ppec_product_upload', FILTER_SANITIZE_URL );
		if ( empty( $product_url ) ) {
			// URL is empty. Maybe not a digital product.
			delete_post_meta( $post_id, 'ppec_product_upload' );
		} else {
			update_post_meta( $post_id, 'ppec_product_upload', esc_url( $product_url, array( 'http', 'https', 'dropbox' ) ) );
		}

		// product thumbnail.
		$thumb_url_raw = filter_input( INPUT_POST, 'wpec_product_thumbnail', FILTER_SANITIZE_URL );
		$thumb_url     = esc_url( $thumb_url_raw, array( 'http', 'https' ) );
		update_post_meta( $post_id, 'wpec_product_thumbnail', $thumb_url );

		// price.
		$price = filter_input( INPUT_POST, 'ppec_product_price', FILTER_SANITIZE_STRING );
		$price = floatval( $price );
		if ( ! empty( $price ) ) {
			// price seems to be valid, let's save it.
			update_post_meta( $post_id, 'ppec_product_price', $price );
		} else {
			// invalid price.
			$text = __( 'Invalid product price.', 'wp-express-checkout' );
			$this->WPECAdmin->add_admin_notice( $text, 'error' );
		}

		// allow custom amount.
		$custom_amount = filter_input( INPUT_POST, 'wpec_product_custom_amount', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'wpec_product_custom_amount', $custom_amount );

		// quantity.
		$quantity = filter_input( INPUT_POST, 'ppec_product_quantity', FILTER_SANITIZE_NUMBER_INT );
		$quantity = empty( $quantity ) ? 1 : $quantity;
		update_post_meta( $post_id, 'ppec_product_quantity', $quantity );

		// allow custom quantity.
		$quantity = filter_input( INPUT_POST, 'ppec_product_custom_quantity', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'ppec_product_custom_quantity', $quantity );

		// shipping & tax.
		$shipping = filter_input( INPUT_POST, 'wpec_product_shipping', FILTER_SANITIZE_STRING );
		$shipping = ! empty( $shipping ) ? floatval( $shipping ) : $shipping;
		update_post_meta( $post_id, 'wpec_product_shipping', $shipping );

		$tax = filter_input( INPUT_POST, 'wpec_product_tax', FILTER_SANITIZE_STRING );
		$tax = floatval( $tax );
		$tax = empty( $tax ) ? '' : $tax;
		update_post_meta( $post_id, 'wpec_product_tax', $tax );

		$button_type = filter_input( INPUT_POST, 'wpec_product_button_type', FILTER_SANITIZE_STRING );
		update_post_meta( $post_id, 'wpec_product_button_type', sanitize_text_field( $button_type ) );

		update_post_meta( $post_id, 'wpec_product_coupons_setting', isset( $_POST['wpec_product_coupons_setting'] ) ? sanitize_text_field( $_POST['wpec_product_coupons_setting'] ) : '0' );
		update_post_meta( $post_id, 'wpec_product_emember_level', ! empty( $_POST['wpec_product_emember_level'] ) ? intval( $_POST['wpec_product_emember_level'] ) : '' );
	}

}

new PPECProductsMetaboxes();
