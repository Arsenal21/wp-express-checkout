<?php

class PPECProductsMetaboxes {

    var $PPECAdmin;

    public function __construct() {
	$this->PPECAdmin = PPDG_Admin::get_instance();
	remove_post_type_support( PPECProducts::$products_slug, 'editor' );
	add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	//products post save action
	add_action( 'save_post_' . PPECProducts::$products_slug, array( $this, 'save_product_handler' ), 10, 3 );
    }

    function add_meta_boxes() {
	add_meta_box( 'wsp_content', __( 'Description', 'paypal-express-checkout' ), array( $this, 'display_description_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
	add_meta_box( 'ppec_price_meta_box', esc_html( __( 'Price', 'paypal-express-checkout' ) ), array( $this, 'display_price_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
	add_meta_box( 'ppec_quantity_meta_box', esc_html( __( 'Quantity', 'paypal-express-checkout' ) ), array( $this, 'display_quantity_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
	add_meta_box( 'ppec_upload_meta_box', __( 'Download URL', 'paypal-express-checkout' ), array( $this, 'display_upload_meta_box' ), PPECProducts::$products_slug, 'normal', 'default' );
	add_meta_box( 'ppec_shortcode_meta_box', __( 'Shortcode', 'paypal-express-checkout' ), array( $this, 'display_shortcode_meta_box' ), PPECProducts::$products_slug, 'side', 'default' );
    }

    function display_description_meta_box( $post ) {
	_e( 'Add a description for your product.', 'paypal-express-checkout' );
	echo '<br /><br />';
	wp_editor( $post->post_content, "content", array( 'textarea_name' => 'content' ) );
    }

    function display_price_meta_box( $post ) {
	$current_price = get_post_meta( $post->ID, 'ppec_product_price', true );
	?>
	<label><?php _e( 'Price', 'paypal-express-checkout' ); ?></label>
	<br/>
	<input type="text" name="ppec_product_price" value="<?php echo $current_price; ?>">
	<p class="description"><?php echo __( 'Item price. Numbers only, no need to put currency symbol. Example: 99.95', 'paypal-express-checkout' ); ?></p>
	<?php
    }

    function display_quantity_meta_box( $post ) {
	$current_val		 = get_post_meta( $post->ID, 'ppec_product_quantity', true );
	$current_val		 = empty( $current_val ) ? 1 : $current_val;
	$allow_custom_quantity	 = get_post_meta( $post->ID, 'ppec_product_custom_quantity', true );
	?>
	<label><?php _e( 'Quantity', 'stripe-payments' ); ?></label>
	<br/>
	<input type="number" name="ppec_product_quantity" value="<?php echo $current_val; ?>">
	<p class="description"><?php echo __( 'Item quantity.', 'paypal-express-checkout' ); ?></p>
	<label>
	    <input type="checkbox" name="ppec_product_custom_quantity" value="1"<?php echo $allow_custom_quantity ? ' checked' : ''; ?>>
	    <?php echo __( 'Allow customers to specify quantity', 'paypal-express-checkout' ); ?>
	</label>
	<p class="description"><?php echo __( "When checked, customers can enter quantity they want to buy. You can set initial quantity using field above.", 'paypal-express-checkout' ); ?></p>
	<?php
    }

    public function display_upload_meta_box( $post ) {
	$current_val = get_post_meta( $post->ID, 'ppec_product_upload', true );
	?>
	<p><?php echo __( 'URL of your product.', 'paypal-express-checkout' ); ?></p>

	<div>
	    <input id="ppec_product_upload" type="text" style="width: 100%" name="ppec_product_upload" value="<?php echo esc_attr( $current_val ); ?>" placeholder="https://..." />

	    <p class="description">
		<?php _e( 'Manually enter a valid URL of the file in the text box below, or click "Select File" button to upload (or choose) the downloadable file.', 'paypal-express-checkout' ); ?>
	    </p>
	</div>
	<p>
	    <input id="ppec_select_upload_btn" type="button" class="button" value="<?php echo __( 'Select File', 'paypal-express-checkout' ); ?>" />
	    <?php do_action( 'ppec_product_upload_metabox_after_button', $post ); ?>
	</p>
	<div>
	    <?php _e( 'Steps to upload a file or choose one from your media library:', 'paypal-express-checkout' ); ?>
	    <ol>
		<li><?php _e( 'Hit the "Select File" button.', 'paypal-express-checkout' ); ?></li>
		<li><?php _e( 'Upload a new file or choose an existing one from your media library.', 'paypal-express-checkout' ) ?></li>
		<li><?php _e( 'Click the "Insert" button, this will populate the uploaded file\'s URL in the above text field.', 'paypal-express-checkout' ) ?></li>
	    </ol>
	</div>
	<script>
	    jQuery(document).ready(function ($) {
		var ppec_selectFileFrame;
		// Run media uploader for file upload
		$('#ppec_select_upload_btn').click(function (e) {
		    e.preventDefault();
		    ppec_selectFileFrame = wp.media({
			title: "<?php echo __( 'Select File', 'paypal-express-checkout' ); ?>",
			button: {
			    text: "<?php echo __( 'Insert', 'paypal-express-checkout' ); ?>"
			},
			multiple: false
		    });
		    ppec_selectFileFrame.open();
		    ppec_selectFileFrame.on('select', function () {
			var attachment = ppec_selectFileFrame.state().get('selection').first().toJSON();

			$('#ppec_product_upload').val(attachment.url);
		    });
		    return false;
		});
	    });
	</script>
	<?php
    }

    function display_shortcode_meta_box( $post ) {
	?>
	<input type="text" name="ppec_product_shortcode" style="width: 100%;" class="ppec-select-on-click" readonly value="[paypal_express_checkout product_id=&quot;<?php echo $post->ID; ?>&quot;]">
	<p class="description"><?php _e( 'Use this shortcode to display button for your product.', 'paypal-express-checkout' ); ?></p>
	<?php
    }

    function save_product_handler( $post_id, $post, $update ) {
	if ( ! isset( $_POST[ 'action' ] ) ) {
	    //this is probably not edit or new post creation event
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
	    //Display error message of product name is empty
	    $text = __( 'Please specify product name.', 'paypal-express-checkout' );
	    $this->PPECAdmin->add_admin_notice( $text, 'error' );
	}

	//download url
	$product_url = filter_input( INPUT_POST, 'ppec_product_upload', FILTER_SANITIZE_URL );
	if ( empty( $product_url ) ) {
	    //url is empty
	    $text = __( 'Please specify product download URL.', 'paypal-express-checkout' );
	    $this->PPECAdmin->add_admin_notice( $text, 'error' );
	} else {
	    update_post_meta( $post_id, 'ppec_product_upload', esc_url( $product_url, array( 'http', 'https', 'dropbox' ) ) );
	}

	//price
	$price	 = filter_input( INPUT_POST, 'ppec_product_price', FILTER_SANITIZE_STRING );
	$price	 = floatval( $price );
	if ( ! empty( $price ) ) {
	    //price seems to be valid, let's save it
	    update_post_meta( $post_id, 'ppec_product_price', $price );
	} else {
	    //invalid price
	    $text = __( 'Ivalid product price.', 'paypal-express-checkout' );
	    $this->PPECAdmin->add_admin_notice( $text, 'error' );
	}

	//quantity
	$quantity	 = filter_input( INPUT_POST, 'ppec_product_quantity', FILTER_SANITIZE_NUMBER_INT );
	$quantity	 = empty( $quantity ) ? 1 : $quantity;
	update_post_meta( $post_id, 'ppec_product_quantity', $quantity );

	//allow custom quantity
	$quantity = filter_input( INPUT_POST, 'ppec_product_custom_quantity', FILTER_SANITIZE_NUMBER_INT );
	update_post_meta( $post_id, 'ppec_product_custom_quantity', $quantity );
    }

}

new PPECProductsMetaboxes();
