<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;

class Products_Meta_Boxes {

	var $WPECAdmin;
	var $WPEC_Main;

	public function __construct() {
		$this->WPECAdmin = Admin::get_instance();
		$this->WPEC_Main = Main::get_instance();
		remove_post_type_support( Products::$products_slug, 'editor' );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 9 );
		// products post save action.
		add_action( 'save_post_' . Products::$products_slug, array( $this, 'save_product_handler' ), 10, 3 );
		// set custom messages on post save\update etc.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
	}

	function add_meta_boxes() {
		add_meta_box( 'wsp_content', __( 'Description', 'wp-express-checkout' ), array( $this, 'display_description_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'ppec_price_meta_box', __( 'Price', 'wp-express-checkout' ), array( $this, 'display_price_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_variations_meta_box', __( 'Variations', 'wp-express-checkout' ), array( $this, 'display_variations_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'ppec_quantity_meta_box', __( 'Quantity', 'wp-express-checkout' ), array( $this, 'display_quantity_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_shipping_tax_meta_box', __( 'Shipping & Tax', 'wp-express-checkout' ), array( $this, 'display_shipping_tax_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'ppec_upload_meta_box', __( 'Download URL', 'wp-express-checkout' ), array( $this, 'display_upload_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_thumbnail_meta_box', __( 'Product Thumbnail', 'wp-express-checkout' ), array( $this, 'display_thumbnail_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_thankyou_page_meta_box', __( 'Thank You Page URL', 'wp-express-checkout' ), array( $this, 'display_thankyou_page_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'ppec_shortcode_meta_box', __( 'Shortcode', 'wp-express-checkout' ), array( $this, 'display_shortcode_meta_box' ), Products::$products_slug, 'side', 'high' );
		add_meta_box( 'wpec_appearance_meta_box', __( 'Appearance Related', 'wp-express-checkout' ), array( $this, 'display_appearance_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_coupons_meta_box', __( 'Coupons Settings', 'wp-express-checkout' ), array( $this, 'display_coupons_meta_box' ), Products::$products_slug, 'normal', 'high' );

		// check if eMember installed
		if ( function_exists( 'wp_eMember_install' ) ) {
			//if it is, let's add metabox where admin can select membership level
			add_meta_box( 'wpec_emember_meta_box', __( 'WP eMember Membership Level', 'wp-express-checkout' ), array( $this, 'display_emember_meta_box' ), Products::$products_slug, 'normal', 'default' );
		}

		// check if Simple Membership installed
		if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
			//if it is, let's add metabox where admin can select membership level
			add_meta_box( 'wpec_swpm_meta_box', __( 'Simple Membership Level', 'wp-express-checkout' ), array( $this, 'display_swpm_meta_box' ), Products::$products_slug, 'normal', 'default' );
		}
	}

	function display_description_meta_box( $post ) {
		esc_html_e( 'Add a description for your product.', 'wp-express-checkout' );
		echo '<br /><br />';
		wp_editor( $post->post_content, 'content', array( 'textarea_name' => 'content' ) );
	}

	function display_price_meta_box( $post ) {
		$product_types             = array();
		$product_types['one_time'] = __( 'One-time payment', 'wp-express-checkout' );

		$product_types = apply_filters( 'wpec_product_types', $product_types, $post );
		$product_type  = get_post_meta( $post->ID, 'wpec_product_type', true );
		$product_type  = empty( $product_type ) ? 'one_time' : $product_type;
		$default_content = '';

		// Unknown type.
		if ( ! isset( $product_types[ $product_type ] ) ) {
			$product_types[ $product_type ] = $product_type;
			$default_content = sprintf( '<strong>' . __( 'A product type "%s" is not registered. Please activate appropriate addon or change the product type.', 'wp-express-checkout' )  . '</strong>', $product_type );
		}

		$current_price       = get_post_meta( $post->ID, 'ppec_product_price', true );
		$allow_custom_amount = get_post_meta( $post->ID, 'wpec_product_custom_amount', true );
		$step                = pow( 10, -intval( $this->WPEC_Main->get_setting( 'price_decimals_num' ) ) );

		$cont = '';

		echo '<p class="wpec_product_type_select_cont">';

		foreach ( $product_types as $type => $name ) {
			if ( 2 > count( $product_types ) ) {
				?>
					<input type="hidden" class="wpec_product_type_radio" name="wpec_product_type_radio" value="<?php echo $type; ?>">
				<?php
			} else {
				?>
				<label>
					<input type="radio" class="wpec_product_type_radio" name="wpec_product_type_radio" value="<?php echo $type; ?>"<?php echo $type === $product_type ? ' checked' : ''; ?>><?php echo $name; ?>
				</label>
				<?php
			}
			$cont .= sprintf( '<div class="wpec_product_type_cont%s" data-wpec-product-type="%s">', $type === $product_type ? ' wpec_product_type_active' : '', $type );
			ob_start();
			switch ( $type ) {
				case 'one_time':
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
					break;
				default:
					echo $default_content;

					do_action( 'wpec_form_product_type_' . $type, $post );
					break;
			}
			$cont .= ob_get_clean();
			$cont .= '</div>';
		}
		echo '</p>';
		echo $cont;
		?>
			<script>
				( function ( $ ) {
					$('.wpec_product_type_radio').change(function(e) {
						$('.wpec_product_type_cont').removeClass('wpec_product_type_active');
						$('.wpec_product_type_cont[data-wpec-product-type="'+$(this).val()+'"]').addClass('wpec_product_type_active');
					});
					$('.wpec_product_type_radio:checked').trigger('change');
				}( jQuery ) );
			</script>
		<?php
	}

	public function display_variations_meta_box( $post ) {
		$price_mod_help  = __( 'Enter price modification - amount that will be added to product price if particular variation is selected.', 'wp-express-checkout' );
		$price_mod_help .= '<br><br>';
		$price_mod_help .= __( 'Put negative value if you want to substract the amount instead.', 'wp-express-checkout' );
		?>
<p>
		<?php
		// translators: %s is a link to documentation page
		echo sprintf( __( 'You can find documentation on variations <a href="%s" target="_blank">here</a>.', 'wp-express-checkout' ), 'https://wp-express-checkout.com/wp-express-checkout-plugin-documentation/' );
		?>
</p>
		<?php
		$current_hide_amount_input = get_post_meta( $post->ID, 'wpec_product_hide_amount_input', true );
		?>
<label>
	<input type="checkbox" name="wpec_product_hide_amount_input" value="1" <?php echo esc_attr( ! empty( $current_hide_amount_input ) ? ' checked' : '' ); ?>> <?php esc_html_e( 'Use variations only to construct final product price', 'wp-express-checkout' ); ?>
</label>
<p class="description">
		<?php esc_html_e( 'When enabled, the total product price will be calculated by using the variation prices only. Useful if you do not want to have a base price for this product.', 'wp-express-checkout' ); ?>
	<br />
		<?php esc_html_e( 'Note: To enable this option, you will need to set the product price to 0.', 'wp-express-checkout' ); ?>
</p>
<br />
		<?php
			$variations_str    = '';
			$variations_groups = get_post_meta( $post->ID, 'wpec_variations_groups', true );
			$variations_names  = get_post_meta( $post->ID, 'wpec_variations_names', true );
			$variations_prices = get_post_meta( $post->ID, 'wpec_variations_prices', true );
			$variations_urls   = get_post_meta( $post->ID, 'wpec_variations_urls', true );
			$variations_opts   = get_post_meta( $post->ID, 'wpec_variations_opts', true );
		if ( empty( $variations_groups ) ) {
			$variations_str = __( 'No variations configured for this product.', 'wp-express-checkout' );
		}
		?>
<div id="wpec-variations-cont-main">
	<div id="wpec-variations-cont">
		<span class="wpec-variations-no-variations-msg"><?php echo $variations_str; ?></span>
	</div>
	<button type="button" class="button" id="wpec-create-variations-group-btn"><span class="dashicons dashicons-welcome-add-page"></span> <?php esc_html_e( 'Create Group', 'wp-express-checkout' ); ?></button>
</div>
<div class="wpec-html-tpl wpec-html-tpl-variations-group">
	<div class="wpec-variations-group-cont">
		<div class="wpec-variations-group-title">
			<span><?php esc_html_e( 'Group Name:', 'wp-express-checkout' ); ?> </span>
			<input type="text" value="" class="wpec-variations-group-name">
			<button type="button" class="button wpec-variations-delete-group-btn wpec-btn-small">
				<span class="dashicons dashicons-trash" title="<?php esc_html_e( 'Delete group', 'wp-express-checkout' ); ?>"></span>
			</button>
			<div class="wpec-variations-display-type-cont">
				<label><?php esc_html_e( 'Display As:', 'wp-express-checkout' ); ?> </label>
				<select class="wpec-variations-display-type">
					<option value="0"><?php esc_html_e( 'Dropdown', 'wp-express-checkout' ); ?></option>
					<option value="1"><?php esc_html_e( 'Radio Buttons', 'wp-express-checkout' ); ?></option>
				</select>
			</div>
		</div>
		<table class="widefat fixed wpec-variations-tbl">
			<tr>
				<th width="40%"><?php echo esc_html( _x( 'Name', 'Variation name', 'wp-express-checkout' ) ); ?></th>
				<th width="20%"><?php esc_html_e( 'Price Mod', 'wp-express-checkout' ); ?> <?php echo Admin::gen_help_popup( $price_mod_help ); ?></th>
				<th width="30%"><?php esc_html_e( 'Product URL', 'wp-express-checkout' ); ?></th>
			</tr>
		</table>
		<div class="wpec-variations-buttons-cont">
			<button type="button" class="button wpec-variations-add-variation-btn"><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Variation', 'wp-express-checkout' ); ?></button>
		</div>
	</div>
</div>
<table class="wpec-html-tpl wpec-html-tpl-variation-row">
	<tbody>
		<tr>
			<td><input type="text" value="" class="wpec-variation-name"></td>
			<td><input type="text" value="" class="wpec-variation-price"></td>
			<td style="position: relative;">
				<input type="text" value="" class="wpec-variation-url">
				<button type="button" class="button wpec-variations-select-from-ml-btn wpec-btn-small"><span class="dashicons  dashicons-admin-media" title="<?php echo esc_attr( __( 'Select from Media Library', 'wp-express-checkout' ) ); ?>"></span></button>
			</td>
			<td>
				<button type="button" class="button wpec-variations-delete-variation-btn wpec-btn-small"><span class="dashicons dashicons-trash" title="<?php echo esc_attr( __( 'Delete variation', 'wp-express-checkout' ) ); ?>"></span></button>
			</td>
		</tr>
	</tbody>
</table>
		<?php
			wp_localize_script(
				'wpec-admin-edit-product-js',
				'wpecEditProdData',
				array(
					'varGroups' => ! empty( $variations_groups ) ? $variations_groups : '',
					'varNames'  => $variations_names,
					'varPrices' => $variations_prices,
					'varUrls'   => $variations_urls,
					'varOpts'   => $variations_opts,
					'str'       => array(
						'groupDeleteConfirm' => __( 'Are you sure you want to delete this group?', 'wp-express-checkout' ),
						'varDeleteConfirm'   => __( 'Are you sure you want to delete this variation?', 'wp-express-checkout' ),
					),
				)
			);
			wp_enqueue_script( 'wpec-admin-edit-product-js' );
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
		$enable_shipping  = get_post_meta( $post->ID, 'wpec_product_shipping_enable', true );
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
		<label>
			<input type="checkbox" name="wpec_product_shipping_enable" value="1" <?php checked( $enable_shipping ); ?>>
			<?php esc_html_e( 'This is a Physical Product', 'wp-express-checkout' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When checked, shipping address will be collected at the time of checkout.', 'wp-express-checkout' ); ?></p>
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

	public function display_thankyou_page_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_thankyou_page', true );
		?>
<input type="text" name="wpec_product_thankyou_page" style="width: 100%;" value="<?php echo esc_attr( $current_val ); ?>" placeholder="https://..." />
<p class="description">
	<?php _e( 'Enter the Thank You page URL for this product. Leave it blank if you want to use the default Thank You page created by the plugin.', 'wp-express-checkout' ); ?>
</p>
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
		$button_txt = get_post_meta( $post->ID, 'wpec_product_button_text', true );
		?>
		<fieldset>
			<label><?php _e( 'Popup/Modal Trigger Button Text',  'wp-express-checkout' ); ?></label>
			<br />
			<input type="text" name="wpec_product_button_text" size="50" value="<?php echo esc_attr( $button_txt ); ?>">
			<p class="description"><?php _e( 'Specify the text to be displayed on the button that triggers the payment popup/modal window. Leave it blank to use the text specified in General Settings page.',  'wp-express-checkout' ); ?></p>

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
			<p class="description"><?php esc_html_e( 'Select a button type/style for the paypal payment button for this product. By default, it will use the type specified in the General settings of this plugin.', 'wp-express-checkout' ); ?></p>
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

		if ( !function_exists( 'emember_get_all_membership_levels_list' )){
			_e( 'Notice: You need to update your copy of the WP eMember plugin before this feature can be used.', 'wp-express-checkout' );
			return;
		}

		$all_levels = emember_get_all_membership_levels_list();
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

	public function display_swpm_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_swpm_level', true );
		?>
<p><?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?></p>
<select name="wpec_product_swpm_level">
<option value=""><?php esc_html_e( 'None', 'wp-express-checkout' ); ?></option>
		<?php
		echo \SwpmUtils::membership_level_dropdown( $current_val );
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

		//handle variations
		$variations_groups = filter_input( INPUT_POST, 'wpec-variations-group-names', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $variations_groups ) && is_array( $variations_groups ) ) {
			//we got variations groups. Let's process them
			update_post_meta( $post_id, 'wpec_variations_groups', $variations_groups );
			$variations_names = filter_input( INPUT_POST, 'wpec-variation-names', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
			update_post_meta( $post_id, 'wpec_variations_names', $variations_names );
			$variations_prices = filter_input( INPUT_POST, 'wpec-variation-prices', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
			update_post_meta( $post_id, 'wpec_variations_prices', $variations_prices );
			$variations_urls = filter_input( INPUT_POST, 'wpec-variation-urls', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
			update_post_meta( $post_id, 'wpec_variations_urls', $variations_urls );
			$variations_opts = filter_input( INPUT_POST, 'wpec-variations-opts', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
			update_post_meta( $post_id, 'wpec_variations_opts', $variations_opts );
		} else {
			//we got no variations groups. Let's clear meta values
			update_post_meta( $post_id, 'wpec_variations_groups', false );
			update_post_meta( $post_id, 'wpec_variations_names', false );
			update_post_meta( $post_id, 'wpec_variations_prices', false );
			update_post_meta( $post_id, 'wpec_variations_urls', false );
			update_post_meta( $post_id, 'wpec_variations_opts', false );
		}

		$hide_amount_input = filter_input( INPUT_POST, 'wpec_product_hide_amount_input', FILTER_SANITIZE_STRING );
		$hide_amount_input = ! empty( $hide_amount_input ) ? true : false;
		update_post_meta( $post_id, 'wpec_product_hide_amount_input', $hide_amount_input );

		// download url.
		$product_url = filter_input( INPUT_POST, 'ppec_product_upload', FILTER_SANITIZE_URL );
		if ( empty( $product_url ) ) {
			// URL is empty. Maybe not a digital product.
			delete_post_meta( $post_id, 'ppec_product_upload' );
		} else {
			update_post_meta( $post_id, 'ppec_product_upload', esc_url( $product_url, array( 'http', 'https', 'dropbox' ) ) );
		}

		// product type.
		$product_type = filter_input( INPUT_POST, 'wpec_product_type_radio', FILTER_SANITIZE_STRING );
		update_post_meta( $post_id, 'wpec_product_type', $product_type );

		// product thumbnail.
		$thumb_url_raw = filter_input( INPUT_POST, 'wpec_product_thumbnail', FILTER_SANITIZE_URL );
		$thumb_url     = esc_url( $thumb_url_raw, array( 'http', 'https' ) );
		update_post_meta( $post_id, 'wpec_product_thumbnail', $thumb_url );

		// Thank you page
		$thank_url_raw = filter_input( INPUT_POST, 'wpec_product_thankyou_page', FILTER_SANITIZE_URL );
		$thank_url     = esc_url( $thank_url_raw, array( 'http', 'https' ) );
		update_post_meta( $post_id, 'wpec_product_thankyou_page', $thank_url );

		// price.
		$price = filter_input( INPUT_POST, 'ppec_product_price', FILTER_SANITIZE_STRING );
		$price = ! empty( $price ) ? floatval( $price ) : 0;

		update_post_meta( $post_id, 'ppec_product_price', $price );

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
		// allow custom quantity.
		$enable_shipping = filter_input( INPUT_POST, 'wpec_product_shipping_enable', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'wpec_product_shipping_enable', $enable_shipping );

		$tax = filter_input( INPUT_POST, 'wpec_product_tax', FILTER_SANITIZE_STRING );
		$tax = floatval( $tax );
		$tax = empty( $tax ) ? '' : $tax;
		update_post_meta( $post_id, 'wpec_product_tax', $tax );

		$button_text = filter_input( INPUT_POST, 'wpec_product_button_text', FILTER_SANITIZE_STRING );
		update_post_meta( $post_id, 'wpec_product_button_text', sanitize_text_field( $button_text ) );

		$button_type = filter_input( INPUT_POST, 'wpec_product_button_type', FILTER_SANITIZE_STRING );
		update_post_meta( $post_id, 'wpec_product_button_type', sanitize_text_field( $button_type ) );

		update_post_meta( $post_id, 'wpec_product_coupons_setting', isset( $_POST['wpec_product_coupons_setting'] ) ? sanitize_text_field( $_POST['wpec_product_coupons_setting'] ) : '0' );
		update_post_meta( $post_id, 'wpec_product_emember_level', ! empty( $_POST['wpec_product_emember_level'] ) ? intval( $_POST['wpec_product_emember_level'] ) : '' );
		update_post_meta( $post_id, 'wpec_product_swpm_level', ! empty( $_POST['wpec_product_swpm_level'] ) ? intval( $_POST['wpec_product_swpm_level'] ) : '' );
	}

	public function post_updated_messages( $messages ) {
		$post      = get_post();
		$post_type = get_post_type( $post );
		$slug      = Products::$products_slug;

		if ( $post_type === Products::$products_slug ) {
			$permalink = get_permalink( $post->ID );
			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View product', 'wp-express-checkout' ) );

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview product', 'wp-express-checkout' ) );

			$messages[ $slug ]     = $messages['post'];
			$messages[ $slug ][1]  = __( 'Product updated.', 'wp-express-checkout' ) . $view_link;
			$messages[ $slug ][4]  = __( 'Product updated.', 'wp-express-checkout' );
			$messages[ $slug ][6]  = __( 'Product published.', 'wp-express-checkout' ) . $view_link;
			$messages[ $slug ][7]  = __( 'Product saved.', 'wp-express-checkout' );
			$messages[ $slug ][8]  = __( 'Product submitted.', 'wp-express-checkout' ) . $preview_link;
			$messages[ $slug ][10] = __( 'Product draft updated.', 'wp-express-checkout' ) . $preview_link;
		}
		return $messages;
	}

}
