<?php

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;

?>

<style>
    #wp-ppdg-preview-container {
        margin-top: 10px; width: 500px; padding: 10px;
        position: relative;
    }
    #wp-ppdg-preview-protect {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1000;
    }
    .wp-ppdg-button-style {
        min-width: 150px;
    }
</style>

<?php
$wpec_pp_settings_subtab = array(
	'api-connection'  => __( 'PayPal API Connection', 'wp-express-checkout' ),
	'api-credentials' => __( 'API Credentials', 'wp-express-checkout' ),
	'btn-appearance'  => __( 'Button Appearance', 'wp-express-checkout' ),
);
?>
<h3 class="nav-tab-wrapper">
    <?php
    $current_subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( $_GET['subtab'] ) : '';
    if (empty($current_subtab)){
        $current_subtab = 'api-connection';
    }
    foreach ( $wpec_pp_settings_subtab as $subtab => $subtab_name ) {
        $class = ( $current_subtab == $subtab ) ? ' nav-tab-active' : '';
        ?>
        <a
            class="nav-tab<?php echo esc_attr( $class ); ?>"
            href="<?php echo esc_url( WPEC_MENU_PARENT_SLUG . '&page=ppec-settings-page&action='.$_GET['action']. '&subtab=' . $subtab ); ?>"
        >
            <?php echo esc_html( $subtab_name ); ?>
        </a>
        <?php
    }
    ?>
</h3>
<br>
<?php
switch ( $current_subtab ){
	case 'api-credentials':
		$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-pp-api-credentials' );
        echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-pp-api-credentials' ) . "' />";
		break;
	case 'btn-appearance':
		$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-pp-btn-appearance' );
        echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-pp-btn-appearance' ) . "' />";
		break;
	default:
		$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-pp-api-connection' );
        echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-pp-api-connection' ) . "' />";
		break;
}

$ppdg			 = Main::get_instance();
$args			 = array();
$disabled_funding	 = $ppdg->get_setting( 'disabled_funding' );
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
	$disabled_cards = $ppdg->get_setting( 'disabled_cards' );
	if ( ! empty( $disabled_cards ) ) {
		$arg = '';
		foreach ( $disabled_cards as $card ) {
			$arg .= $card . ',';
		}
		$arg			 = rtrim( $arg, ',' );
		$args[ 'disable-card' ]	 = $arg;
	}
}
$script_url = add_query_arg( $args, 'https://www.paypal.com/sdk/js?client-id=test' );
printf( '<script src="%s"></script>', $script_url );

?>

<script>
    var wp_ppdg = {
        btn_container: jQuery('#paypal-button-container'),
        btn_height: 25,
        btn_color: 'gold',
        btn_type: 'checkout',
        btn_shape: 'pill',
        btn_layout: 'vertical',
        btn_sizes: {small: 25, medium: 35, large: 45, xlarge: 55}
    };
    var ppbutton = null;

    function wp_ppdg_render_preview() {
        if (ppbutton) {
            ppbutton.hide();
        }
        var styleOpts = {
            layout: wp_ppdg.btn_layout,
            shape: wp_ppdg.btn_shape,
            label: wp_ppdg.btn_type,
            height: wp_ppdg.btn_height,
            color: wp_ppdg.btn_color,
        };
        if (styleOpts.layout === 'horizontal') {
            styleOpts.tagline = false;
        }
        ppbutton = paypal.Buttons({
            style: styleOpts,

            client: {
                sandbox: '123',
            },
            funding: 'paypal',

        });
        ppbutton.render('#paypal-button-container');
    }

    jQuery('.wp-ppdg-button-style').change(function () {
            var btn_height = jQuery('#wp-ppdg-btn_height').val();
            wp_ppdg.btn_height = wp_ppdg.btn_sizes[btn_height];

            var btn_width = jQuery('#wp-ppdg-btn_width').val();
            if (btn_width) {
                if (btn_width < 150) {
                    btn_width = 150;
                    jQuery('#wp-ppdg-btn_width').val(btn_width);
                }
                wp_ppdg.btn_container.css('width', btn_width);
            } else {
                wp_ppdg.btn_container.css('width', 'auto');
                jQuery('#wp-ppdg-btn_width').val('');
            }

            wp_ppdg.btn_layout = jQuery('#wp-ppdg-btn_layout:checked').val();
            wp_ppdg.btn_color = jQuery('#wp-ppdg-btn_color').val();
            wp_ppdg.btn_type = jQuery('#wp-ppdg-btn_type').val();
            wp_ppdg.btn_shape = jQuery('#wp-ppdg-btn_shape').val();
            wp_ppdg_render_preview();
        }
    );
    jQuery('#wp-ppdg-btn_height').change();
</script>
