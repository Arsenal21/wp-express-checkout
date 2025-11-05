<?php

/** @var \WP_Express_Checkout\Admin\Admin $wpec_admin */

wp_enqueue_style( 'wpec-stripe-styles', WPEC_PLUGIN_URL . "/assets/css/wpec-stripe-related.css", array(), WPEC_PLUGIN_VER );

$wpec_pp_settings_subtab = array(
	'general'  => __( 'General Settings', 'wp-express-checkout' ),
	'api-credentials' => __( 'API Credentials', 'wp-express-checkout' ),
	'btn-appearance'  => __( 'Button Appearance', 'wp-express-checkout' ),
);
?>
<h3 class="nav-tab-wrapper">
	<?php
	$current_subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( $_GET['subtab'] ) : '';
	if ( empty( $current_subtab ) ) {
		$current_subtab = 'general';
	}
	foreach ( $wpec_pp_settings_subtab as $subtab => $subtab_name ) {
		$class = ( $current_subtab == $subtab ) ? ' nav-tab-active' : '';
		?>
        <a
            class="nav-tab<?php echo esc_attr( $class ); ?>"
            href="<?php echo esc_url( WPEC_MENU_PARENT_SLUG . '&page=ppec-settings-page&action=' . $_GET['action'] . '&subtab=' . $subtab ); ?>"
        >
			<?php echo esc_html( $subtab_name ); ?>
        </a>
		<?php
	}
	?>
</h3>
<br>
<?php
switch ( $current_subtab ) {
	case 'api-credentials':
		$wpec_admin->do_settings_sections( 'wpec-stripe-api-credentials' );
		echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'wpec-stripe-api-credentials' ) . "' />";
		break;
	case 'btn-appearance':
		$wpec_admin->do_settings_sections( 'wpec-stripe-btn-appearance' );
		echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'wpec-stripe-btn-appearance' ) . "' />";
		break;
    case 'general':
    default:
		$wpec_admin->do_settings_sections( 'wpec-stripe-settings' );
		echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'wpec-stripe-settings' ) . "' />";
		break;
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const previewBtn = document.getElementById('stripe-preview-button');
        previewBtn.addEventListener('click', function (e) {
            e.preventDefault();
        })

        // const stripeButtonContainer = document.getElementById('stripe-button-container');

        const btnStyleInputs = {
            textInput: document.getElementById('wp-ppdg-stripe_btn_text'),
            shapeInput: document.getElementById('wp-ppdg-stripe_btn_shape'),
            heightInput: document.getElementById('wp-ppdg-stripe_btn_height'),
            widthInput: document.getElementById('wp-ppdg-stripe_btn_width'),
            colorInput: document.getElementById('wp-ppdg-stripe_btn_color'),
        }

        wpec_render_stripe_btn_preview(previewBtn, btnStyleInputs);

        Object.values(btnStyleInputs).forEach((input) => {
            input.addEventListener('change', function (e) {
                wpec_render_stripe_btn_preview(previewBtn, btnStyleInputs)
            })
        })

        function wpec_render_stripe_btn_preview(previewBtn, btnStyleInputs) {
            previewBtn.className = 'wpec-stripe-btn';
            const text = btnStyleInputs.textInput.value || 'Stripe';
            const width = btnStyleInputs.widthInput.value + 'px' || 'auto';
            const colorClass = 'wpec-stripe-btn-color-' + String(btnStyleInputs.colorInput.value).toLowerCase();
            const sizeClass = 'wpec-stripe-btn-height-' + String(btnStyleInputs.heightInput.value).toLowerCase();

            previewBtn.innerText = text;
            previewBtn.style.width = width;
            previewBtn.classList.add(colorClass);
            previewBtn.classList.add(sizeClass);

            if (String(btnStyleInputs.shapeInput.value).toLowerCase() === 'pill') {
                previewBtn.classList.add('wpec-stripe-btn-pill');
            }
        }
    })
</script>
