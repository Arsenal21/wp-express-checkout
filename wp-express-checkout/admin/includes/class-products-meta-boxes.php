<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Utils;

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
		add_meta_box( 'ppec_quantity_meta_box', __( 'Quantity & Stock', 'wp-express-checkout' ), array( $this, 'display_quantity_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_shipping_tax_meta_box', __( 'Shipping & Tax', 'wp-express-checkout' ), array( $this, 'display_shipping_tax_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'ppec_upload_meta_box', __( 'Download URL', 'wp-express-checkout' ), array( $this, 'display_upload_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_thumbnail_meta_box', __( 'Product Thumbnail', 'wp-express-checkout' ), array( $this, 'display_thumbnail_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_thankyou_page_meta_box', __( 'Thank You Page URL', 'wp-express-checkout' ), array( $this, 'display_thankyou_page_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'ppec_shortcode_meta_box', __( 'Shortcode', 'wp-express-checkout' ), array( $this, 'display_shortcode_meta_box' ), Products::$products_slug, 'side', 'default' );
		add_meta_box( 'ppec_link_meta_box', __( 'Link URL', 'wp-express-checkout' ), array( $this, 'display_link_meta_box' ), Products::$products_slug, 'side', 'default' );
		add_meta_box( 'wpec_appearance_meta_box', __( 'Appearance Related', 'wp-express-checkout' ), array( $this, 'display_appearance_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_coupons_meta_box', __( 'Coupons Settings', 'wp-express-checkout' ), array( $this, 'display_coupons_meta_box' ), Products::$products_slug, 'normal', 'high' );
		add_meta_box( 'wpec_custom_email_meta_box', __( 'Per-Product Email Customization', 'wp-express-checkout' ), array( $this, 'display_custom_email_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	function display_description_meta_box( $post ) {
		esc_html_e( 'Add a description for your product.', 'wp-express-checkout' );
		echo '<br /><br />';
		wp_editor( $post->post_content, 'content', array( 'textarea_name' => 'content' ) );
	}

	function display_price_meta_box( $post ) {
		$product_types             = array();
		$product_types['one_time'] = __( 'One-time payment', 'wp-express-checkout' );
		$product_types['donation'] = __( 'Donation', 'wp-express-checkout' );

		$product_types = apply_filters( 'wpec_product_types', $product_types, $post );
		$product_type  = get_post_meta( $post->ID, 'wpec_product_type', true );

		// Legacy option. Added for backward compatibility.
		$allow_custom_amount = get_post_meta( $post->ID, 'wpec_product_custom_amount', true );

		if ( $allow_custom_amount ) {
			$product_type = 'donation';
		} elseif ( empty( $product_type ) ) {
			$product_type = 'one_time';
		}

		$default_content = '';

		// Unknown type.
		if ( ! isset( $product_types[ $product_type ] ) ) {
			$product_types[ $product_type ] = $product_type;
			$default_content = sprintf( '<strong>' . __( 'A product type "%s" is not registered. Please activate the appropriate addon to use this product.', 'wp-express-checkout' )  . '</strong>', $product_type );
		}

		$current_price = get_post_meta( $post->ID, 'ppec_product_price', true );
		$min_amount    = get_post_meta( $post->ID, 'wpec_product_min_amount', true );
		$min_amount    = ! $min_amount ? $current_price : $min_amount;
		$step          = pow( 10, -intval( $this->WPEC_Main->get_setting( 'price_decimals_num' ) ) );

		$cont = '';

		echo '<p class="wpec_product_type_select_cont">';

		foreach ( $product_types as $type => $name ) {
			?>
			<label>
				<input type="radio" class="wpec_product_type_radio" name="wpec_product_type_radio" value="<?php echo $type; ?>"<?php echo $type === $product_type ? ' checked' : ''; ?>><?php echo $name; ?>
			</label>
			<?php
			$cont .= sprintf( '<div class="wpec_product_type_cont%s" data-wpec-product-type="%s">', $type === $product_type ? ' wpec_product_type_active' : '', $type );
			ob_start();
			switch ( $type ) {
				case 'one_time':
					?>
					<label><?php esc_html_e( 'Price', 'wp-express-checkout' ); ?></label>
					<br/>
					<input type="number" name="ppec_product_price" step="<?php echo esc_attr( $step ); ?>" min="0" value="<?php echo esc_attr( $current_price ); ?>">
					<p class="description"><?php esc_html_e( 'Item price. Enter numbers only, no need to put currency symbol. Example: 39.95', 'wp-express-checkout' ); ?></p>
					<?php
					break;
				case 'donation':
					?>
					<p class="description"><?php esc_html_e( 'Donation type product allows the customers to change the amount that they want to pay. You can set a minimum donation amount using the field below.', 'wp-express-checkout' ); ?></p>
					<label><?php esc_html_e( 'Minimum Donation Amount', 'wp-express-checkout' ); ?></label>
					<br/>
					<input type="number" name="wpec_product_min_amount" step="<?php echo esc_attr( $step ); ?>" min="0" value="<?php echo esc_attr( $min_amount ); ?>">
					<p class="description"><?php esc_html_e( 'Specify a minimum donation amount. Enter numbers only, no need to put currency symbol. Example: 5.00', 'wp-express-checkout' ); ?></p>
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
		echo sprintf( __( 'You can find documentation on variations <a href="%s" target="_blank">here</a>.', 'wp-express-checkout' ), 'https://wp-express-checkout.com/creating-product-with-variations/' );
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
		$current_val  = get_post_meta( $post->ID, 'ppec_product_quantity', true );
		$current_val  = empty( $current_val ) ? 1 : $current_val;
		$enable_stock = get_post_meta( $post->ID, 'wpec_product_enable_stock', true );
		$stock_items  = get_post_meta( $post->ID, 'wpec_product_stock_items', true );

		$allow_custom_quantity = get_post_meta( $post->ID, 'ppec_product_custom_quantity', true );
		?>
		<label>
			<input type="checkbox" name="ppec_product_custom_quantity" value="1" <?php checked( $allow_custom_quantity ); ?>>
			<?php esc_html_e( 'Allow customers to specify quantity', 'wp-express-checkout' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When checked, customers can enter quantity they want to buy. You can set initial quantity using field above.', 'wp-express-checkout' ); ?></p>
		<label><?php esc_html_e( 'Set Quantity', 'wp-express-checkout' ); ?></label>
		<br/>
		<input type="number" name="ppec_product_quantity" value="<?php echo esc_attr( $current_val ); ?>">
		<p class="description"><?php esc_html_e( 'If you want to use a set quantity for this item then enter the value in this field.', 'wp-express-checkout' ); ?></p>

		<hr />

		<label>
			<input type="checkbox" name="wpec_product_enable_stock" value="1" <?php checked( $enable_stock ); ?>>
			<?php esc_html_e( 'Enable stock control', 'wp-express-checkout' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, you can specify the quantity available for this product. It will be decreased each time the item is purchased. When stock reaches zero, an "Out of stock" message will be displayed instead of the buy button.', 'wp-express-checkout' ); ?></p>

		<label><?php esc_html_e( 'Quantity Available:', 'wp-express-checkout' ); ?>	</label>
		<br />
		<input type="number" min="0" step="1" name="wpec_product_stock_items" value="<?php echo esc_attr( ! $stock_items ? 0 : $stock_items ); ?>">
		<p class="description"><?php esc_html_e( 'Specify the quantity available for this product.', 'wp-express-checkout' ); ?></p>

		<?php
	}

	public function display_shipping_tax_meta_box( $post ) {
		$current_shipping = get_post_meta( $post->ID, 'wpec_product_shipping', true );
		$shipping_cost_per_quantity = get_post_meta( $post->ID, 'wpec_product_shipping_per_quantity', true );
		$current_tax      = get_post_meta( $post->ID, 'wpec_product_tax', true );
		$step             = pow( 10, -intval( $this->WPEC_Main->get_setting( 'price_decimals_num' ) ) );
		$enable_shipping  = get_post_meta( $post->ID, 'wpec_product_shipping_enable', true );

		// translators: %s is a link to documentation page
		echo '<p>';
		echo sprintf( __( 'You can find documentation on shipping and tax <a href="%s" target="_blank">here</a>.', 'wp-express-checkout' ), 'https://wp-express-checkout.com/shipping-tax-for-express-checkout/' );
		echo '</p>';
		?>
		<label>
			<input type="checkbox" name="wpec_product_shipping_enable" value="1" <?php checked( $enable_shipping ); ?>>
			<?php esc_html_e( 'This is a Physical Product', 'wp-express-checkout' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When checked, shipping address will be collected at the time of checkout.', 'wp-express-checkout' ); ?></p>

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

		<div id="wpec_additional_shipping_cost_container">
			<label><?php esc_html_e( 'Shipping Cost Per Quantity', 'wp-express-checkout' ); ?></label>
			<br />
			<input type="number" name="wpec_product_shipping_per_quantity" step="<?php echo esc_attr( $step ); ?>" min="0" value="<?php echo esc_attr( $shipping_cost_per_quantity ); ?>">
			<p class="description">
			<?php
			esc_html_e( 'Additional shipping cost per quantity', 'wp-express-checkout' );
			echo '<br>';
			esc_html_e( 'Leave it empty if you are not charging any additional shipping cost per quantity.', 'wp-express-checkout' );
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

	}

	public function display_upload_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'ppec_product_upload', true );
		?>
		<p><?php esc_html_e( 'URL of your product.', 'wp-express-checkout' ); ?></p>

		<div>
			<input id="ppec_product_upload" type="text" style="width: 100%" name="ppec_product_upload" value="<?php echo esc_attr( $current_val ); ?>" placeholder="https://..." />

			<p class="description">
				<?php esc_html_e( 'Manually enter a valid URL of the file in the text box, or click "Select File" button to upload (or choose) the downloadable file.', 'wp-express-checkout' ); ?>
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
		<hr />
		<strong><?php esc_html_e( 'Force Download', 'wp-express-checkout' ); ?></strong>
		<br />		
		<p>
			<?php _e('Read the', 'wp-express-checkout') ?> <a href="https://wp-express-checkout.com/force-download-option-for-digital-products/" target="_blank"><?php _e('force download tutorial', 'wp-express-checkout') ?></a> <?php _e('to learn how this feature works.', 'wp-express-checkout') ?>
		</p>
		<input type="checkbox" value="1" name="wpec_force_download" <?php echo ($post->wpec_force_download == 1) ? "checked":""; ?> ><span><?php esc_html_e( 'Enable Force Download', 'wp-express-checkout' ); ?></span>
		<hr />
		<strong><?php esc_html_e( 'Download Link Expiry Settings (Optional)', 'wp-express-checkout' ); ?></strong>
		<br />
		<p>
			<?php _e('Read the', 'wp-express-checkout') ?> <a href="https://wp-express-checkout.com/limiting-product-download-links/" target="_blank"><?php _e('download link expiry tutorial', 'wp-express-checkout') ?></a> <?php _e('to learn how the feature works.', 'wp-express-checkout') ?>
		</p>
		<label><?php esc_html_e( 'Duration of Download Link', 'wp-express-checkout' ); ?></label>
		<br/>
		<input type="number" name="wpec_download_duration" value="<?php echo esc_attr( $post->wpec_download_duration ); ?>"> <span><?php esc_html_e( 'Hours', 'wp-express-checkout' ); ?></span>
		<p class="description"><?php esc_html_e( 'This is the duration of time the download links will remain active for a customer. After this amount of time the link will expire. Example value: 48. Leave empty to use global settings or set to 0 to disable link expiry.', 'wp-express-checkout' ); ?></p>

		<label><?php esc_html_e( 'Download Limit Count', 'wp-express-checkout' ); ?></label>
		<br/>
		<input type="number" name="wpec_download_count" value="<?php echo esc_attr( $post->wpec_download_count ); ?>"> <span><?php esc_html_e( 'Times', 'wp-express-checkout' ); ?></span>
		<p class="description"><?php esc_html_e( 'Number of times an item can be downloaded before the download link expires. Example value: 3. Leave empty to use global settings or set to 0 if you do not want to limit downloads by download count.', 'wp-express-checkout' ); ?></p>
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
		<input type="text" name="ppec_product_shortcode" style="width: 100%;" class="wpec-select-on-click large-text" onfocus="this.select();" readonly value="[wp_express_checkout product_id=&quot;<?php echo $post->ID; ?>&quot;]">
		<p class="description">
			<?php esc_html_e( 'Use this shortcode to display button for your product.', 'wp-express-checkout' ); ?>
			<?php esc_html_e( ' Read the ', 'wp-express-checkout' ); ?>
			<a href="https://wp-express-checkout.com/creating-and-display-a-product/" target="_blank"><?php esc_html_e( 'documentation', 'wp-express-checkout' ); ?></a>
			<?php esc_html_e( ' to learn more.', 'wp-express-checkout' ); ?>
			
		</p>
		<?php
	}

	function display_link_meta_box( $post ) {
		?>
		<input type="text" name="ppec_product_link" style="width: 100%;" class="wpec-select-on-click large-text" onfocus="this.select();" readonly value="<?php echo home_url('/wpec-payment-box/?product_id='.$post->ID) ?>">
		<p class="description"><?php esc_html_e( 'This URL can be used to create a custom payment button using a text or image link.', 'wp-express-checkout' ); ?></p>
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

	public function display_custom_email_meta_box ($post) {
		$wpec = Main::get_instance();
		if ( $wpec->get_setting( 'enable_per_product_email_customization' ) != 1 ) {
			_e( 'Product specific email customization is disabled. It must be enabled <a href="'.WPEC_MENU_PARENT_SLUG.'&page=ppec-settings-page&action=email-settings#wp-ppdg-enable_per_product_email_customization" target="_blank">in the settings</a> before you can configure it for this product.', 'wp-express-checkout' );
			return false;
		}

		$email_tags = Utils::get_dynamic_tags_white_list();
		$email_tags_desc = '';
		foreach ( $email_tags as $tag => $desc ) {
			$email_tags_desc .= "<br /><code>{{$tag}}</code> - {$desc}";
		}

        $is_html_email_type = $wpec->get_setting( 'buyer_email_type' ) == 'html';

		$email_enabled = get_post_meta( $post->ID, 'custom_buyer_email_enabled', true );

		$buyer_email_subj    = get_post_meta( $post->ID, 'custom_buyer_email_subj', true );
		$buyer_email_from    = get_post_meta( $post->ID, 'custom_buyer_email_from', true );
		$buyer_email_body    = get_post_meta( $post->ID, 'custom_buyer_email_body', true );

		$buyer_email_subj = empty( $buyer_email_subj ) ? $wpec->get_setting( 'buyer_email_subj' ) : $buyer_email_subj;
		$buyer_email_from = empty( $buyer_email_from ) ? $wpec->get_setting( 'buyer_from_email' ) : $buyer_email_from;
		$buyer_email_body = empty( $buyer_email_body ) ? $wpec->get_setting( 'buyer_email_body' ) : $buyer_email_body;
		?>
		<p class="description">
			<a href="https://wp-express-checkout.com/per-product-email-customization-feature/" target="_blank"><?php _e('Read the documentation', 'wp-express-checkout')?></a>
			<?php _e(' to learn how to use the per-product email customization feature.', 'wp-express-checkout')?>
		</p>		
		<div class="nav-tab-wrapper">
			<a href="#" data-tab-name="buyer-email" class="wpec-custom-email-nav nav-tab nav-tab-active"><?php _e('Buyer Email', 'wp-express-checkout') ?></a>
			<a href="#" data-tab-name="seller-email" class="wpec-custom-email-nav nav-tab"><?php _e('Seller Email', 'wp-express-checkout') ?></a>
		</div>
		<div data-tab-name="buyer-email" style="padding-top: 10px;">
			<fieldset>
				<label><input type="checkbox" name="custom_buyer_email_enabled" value="1"<?php echo ! empty( $email_enabled ) ? ' checked' : ''; ?>><?php _e('Send customized email to buyers of this product', 'wp-express-checkout')?></label>
				<br>
				<p class="description"><?php _e( 'Enable this to send email which you can configure below to buyers of this product.', 'wp-express-checkout' ); ?></p>
				
				<label>
					<?php _e( 'From Email', 'wp-express-checkout' ); ?>
					<br>
					<input type="text" name="custom_buyer_email_from" size="50" value="<?php echo esc_attr($buyer_email_from); ?>">
				</label>
				<p class="description"><?php _e( 'Enter from email address.', 'wp-express-checkout' ); ?></p>

				<label>
					<?php _e( 'Email Subject', 'wp-express-checkout' ); ?>
					<br>
					<input type="text" name="custom_buyer_email_subj" size="50" value="<?php echo esc_attr($buyer_email_subj); ?>">
				</label>
				<p class="description"><?php _e( 'Enter subject of the email.', 'wp-express-checkout' ); ?></p>

				<label>
					<?php _e( 'Email Body', 'wp-express-checkout' ); ?>
					<br>
                    <?php if ($is_html_email_type) {
                        add_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
                        wp_editor(
                            html_entity_decode( $buyer_email_body ),
                            'custom_buyer_email_body',
                            array(
                                'textarea_name' => "custom_buyer_email_body",
                                'teeny'         => true,
                            )
                        );
                        remove_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
                    } else { ?>
					    <textarea cols="70" rows="7" name="custom_buyer_email_body"><?php echo esc_attr($buyer_email_body); ?></textarea>
                    <?php } ?>
				</label>
				<p class="description">
					<?php _e( 'This is the body of the email that will be sent to the buyer.', 'wp-express-checkout' ); ?>
				</p>
			</fieldset>
		</div>

		<?php
		$seller_email_enabled = get_post_meta( $post->ID, 'custom_seller_email_enabled', true );

		$seller_email_subj = get_post_meta( $post->ID, 'custom_seller_email_subj', true );
		$seller_notification_email  = get_post_meta( $post->ID, 'custom_seller_notification_email', true );
		$seller_email_body = get_post_meta( $post->ID, 'custom_seller_email_body', true );

		$seller_email_subj = empty( $seller_email_subj ) ? $wpec->get_setting( 'seller_email_subj' ) : $seller_email_subj;
		$seller_notification_email = empty( $seller_notification_email ) ? $wpec->get_setting( 'notify_email_address' ) : $seller_notification_email;
		$seller_email_body = empty( $seller_email_body ) ? $wpec->get_setting( 'seller_email_body' ) : $seller_email_body;
		?>
		<div data-tab-name="seller-email" style="padding-top: 10px; display: none;">
			<fieldset>
				<label><input type="checkbox" name="custom_seller_email_enabled" value="1"<?php echo ! empty( $seller_email_enabled ) ? ' checked' : ''; ?>><?php _e('Send a customized notification email to the seller', 'wp-express-checkout') ?></label>
				<br>
				<p class="description"><?php _e( 'Enable this to send an email which you can configure below to the seller of this product.', 'wp-express-checkout' ); ?></p>
				
				<label>
					<?php _e( 'Notification Email Address', 'wp-express-checkout' ); ?>
					<br>
					<input type="text" name="custom_seller_notification_email" size="50" value="<?php echo $seller_notification_email; ?>">
				</label>
				<p class="description"><?php _e( 'Enter notification email address.', 'wp-express-checkout' ); ?></p>
				
				<label>
					<?php _e( 'Email Subject', 'wp-express-checkout' ); ?>
					<br>
					<input type="text" name="custom_seller_email_subj" size="50" value="<?php echo $seller_email_subj; ?>">
				</label>
				<p class="description"><?php _e( 'Enter subject of the email.', 'wp-express-checkout' ); ?></p>

				<label>
					<?php _e( 'Email Body', 'wp-express-checkout' ); ?>
					<br>
                    <?php if ($is_html_email_type) {
                        add_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
                        wp_editor(
                            html_entity_decode( $seller_email_body ),
                            'custom_seller_email_body',
                            array(
                                'textarea_name' => "custom_seller_email_body",
                                'teeny'         => true,
                            )
                        );
                        remove_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
                    } else { ?>
					    <textarea cols="70" rows="7" name="custom_seller_email_body"><?php echo $seller_email_body; ?></textarea>
                    <?php } ?>
				</label>
				<p class="description">
					<?php _e( 'This is the body of the email that will be sent to the seller.', 'wp-express-checkout' ); ?>
				</p>
			</fieldset>
		</div>
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				let wpec_custom_email_tab = "buyer-email";
				document.querySelectorAll('a.wpec-custom-email-nav').forEach(function(navLink) {
					navLink.addEventListener('click', function(e) {
						e.preventDefault();
						if (this.getAttribute('data-tab-name') !== wpec_custom_email_tab) {
							const currentTab = document.querySelector('div[data-tab-name="' + wpec_custom_email_tab + '"]');
							currentTab.style.display = 'none';

							const nextTab = document.querySelector('div[data-tab-name="' + this.getAttribute('data-tab-name') + '"]');
							nextTab.style.display = 'block';

							document.querySelector('a.nav-tab[data-tab-name="' + wpec_custom_email_tab + '"]').classList.remove('nav-tab-active');
							wpec_custom_email_tab = this.getAttribute('data-tab-name');
							this.classList.add('nav-tab-active');
						}
					});
				});
			});

		</script>
		<?php
	}

    public function set_default_editor( $r ) {
        $r = 'html';
        return $r;
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

		$hide_amount_input = isset( $_POST['wpec_product_hide_amount_input'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_hide_amount_input'] ) ) : '';
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
		$force_download = filter_input( INPUT_POST, 'wpec_force_download', FILTER_SANITIZE_NUMBER_INT );				
		update_post_meta( $post_id, 'wpec_force_download', $force_download );
		
		$download_duration = filter_input( INPUT_POST, 'wpec_download_duration', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'wpec_download_duration', $download_duration );
		$download_count = filter_input( INPUT_POST, 'wpec_download_count', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'wpec_download_count', $download_count );

		// product type.
		$product_type = isset( $_POST['wpec_product_type_radio'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_type_radio'] ) ) : '';
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
		$price = isset( $_POST['ppec_product_price'] ) ? sanitize_text_field( stripslashes ( $_POST['ppec_product_price'] ) ) : '';
		$price = ! empty( $price ) ? floatval( $price ) : 0;

		update_post_meta( $post_id, 'ppec_product_price', $price );

		// min amount.
		$min_amount = isset( $_POST['wpec_product_min_amount'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_min_amount'] ) ) : '';
		$min_amount = ! empty( $min_amount ) ? floatval( $min_amount ) : 0;

		update_post_meta( $post_id, 'wpec_product_min_amount', $min_amount );

		// quantity.
		$quantity = filter_input( INPUT_POST, 'ppec_product_quantity', FILTER_SANITIZE_NUMBER_INT );
		$quantity = empty( $quantity ) ? 1 : $quantity;
		update_post_meta( $post_id, 'ppec_product_quantity', $quantity );

		// allow custom quantity.
		$quantity = filter_input( INPUT_POST, 'ppec_product_custom_quantity', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'ppec_product_custom_quantity', $quantity );

		// Stock control
		update_post_meta( $post_id, 'wpec_product_enable_stock', ! empty( $_POST['wpec_product_enable_stock'] ) ? true : false );
		update_post_meta( $post_id, 'wpec_product_stock_items', absint( $_POST['wpec_product_stock_items'] ) );

		// shipping & tax.
		$shipping = isset( $_POST['wpec_product_shipping'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_shipping'] ) ) : '';
		$shipping = ! empty( $shipping ) ? floatval( $shipping ) : $shipping;
		update_post_meta( $post_id, 'wpec_product_shipping', $shipping );
		// allow custom quantity.
		$enable_shipping = filter_input( INPUT_POST, 'wpec_product_shipping_enable', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, 'wpec_product_shipping_enable', $enable_shipping );

		$shipping_per_quantity = isset( $_POST['wpec_product_shipping_per_quantity'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_shipping_per_quantity'] ) ) : '';
		$shipping_per_quantity = ! empty( $shipping_per_quantity ) ? floatval( $shipping_per_quantity ) : $shipping_per_quantity;
		update_post_meta( $post_id, 'wpec_product_shipping_per_quantity', $shipping_per_quantity );

		$tax = isset( $_POST['wpec_product_tax'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_tax'] ) ) : '';
		$tax = floatval( $tax );
		$tax = empty( $tax ) ? '' : $tax;
		update_post_meta( $post_id, 'wpec_product_tax', $tax );

		$button_text = isset( $_POST['wpec_product_button_text'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_button_text'] ) ) : '';
		update_post_meta( $post_id, 'wpec_product_button_text', sanitize_text_field( $button_text ) );

		$button_type = isset( $_POST['wpec_product_button_type'] ) ? sanitize_text_field( stripslashes ( $_POST['wpec_product_button_type'] ) ) : '';
		update_post_meta( $post_id, 'wpec_product_button_type', sanitize_text_field( $button_type ) );

		update_post_meta( $post_id, 'wpec_product_coupons_setting', isset( $_POST['wpec_product_coupons_setting'] ) ? sanitize_text_field( $_POST['wpec_product_coupons_setting'] ) : '0' );

		$this->save_custom_email_data($post_id, $post, $update);

		do_action( 'wpec_save_product_handler', $post_id, $post, $update );
	}

	private function save_custom_email_data( $post_id, $post, $update ) {
		// Buyer data
		$buyer_email_enabled = isset( $_POST['custom_buyer_email_enabled'] ) && sanitize_text_field($_POST['custom_buyer_email_enabled']) == 1 ? '1' : 0;
		update_post_meta( $post_id, 'custom_buyer_email_enabled', $buyer_email_enabled );

		$buyer_email_subj = isset( $_POST['custom_buyer_email_subj'] ) ? sanitize_text_field( stripslashes ( $_POST['custom_buyer_email_subj'] ) ) : '';
		update_post_meta( $post_id, 'custom_buyer_email_subj', $buyer_email_subj );

		//Need to use 'htmlentities' on the from email address to allow the format: 'Your Name <hello@yourdomain.com>'
		$buyer_email_from = isset( $_POST['custom_buyer_email_from'] ) ? sanitize_text_field(htmlentities($_POST['custom_buyer_email_from'])) : '';
		update_post_meta( $post_id, 'custom_buyer_email_from', $buyer_email_from );

		$buyer_email_body = isset($_POST['custom_buyer_email_body']) ? wp_kses_post($_POST['custom_buyer_email_body']) : '';
		update_post_meta( $post_id, 'custom_buyer_email_body', $buyer_email_body );

		// Seller data
		$seller_email_enabled = isset( $_POST['custom_seller_email_enabled'] ) && sanitize_text_field($_POST['custom_seller_email_enabled']) == 1 ? '1' : 0;
		update_post_meta( $post_id, 'custom_seller_email_enabled', $seller_email_enabled );

		$seller_email_subj = isset( $_POST['custom_seller_email_subj'] ) ? sanitize_text_field( stripslashes ( $_POST['custom_seller_email_subj'] ) ) : '';
		update_post_meta( $post_id, 'custom_seller_email_subj', $seller_email_subj );

		$seller_notification_emails = isset( $_POST['custom_seller_notification_email'] ) ? sanitize_text_field($_POST['custom_seller_notification_email']) : '';
        $seller_notification_emails_array = array_map('sanitize_email', explode(',' , $seller_notification_emails)); // Sanitize each email separated by comma.
        $seller_notification_emails_array = array_filter($seller_notification_emails_array); // Remove empty value.
        $seller_notification_emails_sanitized = implode(', ', $seller_notification_emails_array);

		update_post_meta( $post_id, 'custom_seller_notification_email', $seller_notification_emails_sanitized );

		$seller_email_body = isset($_POST['custom_seller_email_body']) ? wp_kses_post($_POST['custom_seller_email_body']) : '';
		update_post_meta( $post_id, 'custom_seller_email_body', $seller_email_body );
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
