<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Emails;

class Tools_Admin_Menu {

	public static $instance = null;

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function render_tools_menu_page() {
		?>

		<div class="wrap">
			<h1><?php esc_html_e( get_admin_page_title() ); ?></h1>

			<?php
			/**
			 * Filters the plugin tools tabs
			 *
			 * @param array $tabs An array of tabs titles keyed with the tab slug.
			 */
			$wpec_plugin_tabs = apply_filters( 'wpec_tools_tabs', array(
				'general' => __( 'General Tools', 'wp-express-checkout' ),
			) );

			$current = "general";
			if ( isset( $_GET['tab'] ) ) {
				$current = sanitize_text_field( $_GET['tab'] );
			}
			?>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $wpec_plugin_tabs as $tab => $tab_name ) {
					$class = ( $current == $tab ) ? ' nav-tab-active' : '';
					?>
					<a class="nav-tab<?php esc_attr_e( $class ); ?>"
					   href="<?php echo esc_url( WPEC_MENU_PARENT_SLUG . '&page=wpec-tools&tab=' . $tab ); ?>">
						<?php esc_attr_e( $tab_name ); ?>
					</a>
					<?php
				}
				?>
			</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-2" class="postbox-container">
						<?php
						$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';

						switch ( $tab ) {
							default:
								//General tab
								$this->render_general_tools_tab();
								break;
						}
						?>
					</div>

					<div id="postbox-container-1" class="postbox-container">
						<?php include WPEC_PLUGIN_PATH . 'admin/views/sidebar.php'; ?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	function render_general_tools_tab() {
		//General tab
		//At the moment, we only have one tool in the general tab.
		$this->send_email_tools_postbox();
	}

	function send_email_tools_postbox() {
		$default_from_email = Main::get_instance()->get_setting( 'buyer_from_email' );

		$tools_settings = get_option('wpec-tools', array());

		if ( isset( $_POST['wpec-send-custom-email-submit'] ) ){

			if( ! check_admin_referer('wpec-send-custom-email-nonce-action')){
				wp_die(__('Nonce Verification Failed!', 'wp-express-checkout'));
			}

			$to      = isset($_POST['customer_email_to']) ? sanitize_email($_POST['customer_email_to']) : '';
			$from    = isset($_POST['customer_email_from']) ? $_POST['customer_email_from'] : $default_from_email;
			$subject = isset($_POST['customer_email_subject']) ? sanitize_text_field($_POST['customer_email_subject']) : '';
			$body    = isset($_POST['customer_email_body']) ? sanitize_textarea_field($_POST['customer_email_body']) : '';

			if (empty($to) || empty($from) || empty($subject) || empty($body)){
				echo '<div class="notice notice-error"><p>'. __('There are some missing fields. Email could not be sent!', 'wp-express-checkout') .'</p></div>';
			} else {
				$result = Emails::send( $to, $from, $subject, $body );
				if ( $result ) {
					echo '<div class="notice notice-success"><p>'. __('Email successfully sent!', 'wp-express-checkout') .'</p></div>';
					Logger::log( 'Tools menu - Email sent to: ' . $to );
				} else {
					echo '<div class="notice notice-error"><p>'. __('Something went wrong, email is not sent!', 'wp-express-checkout') .'</p></div>';
				}
			}

			$tools_settings['customer_email_from'] = $from;
			$tools_settings['customer_email_to'] = $to;
			$tools_settings['customer_email_subject'] = $subject;
			$tools_settings['customer_email_body'] = $body;

			update_option('wpec-tools', $tools_settings);
		}

		$customer_email_from = isset($tools_settings['customer_email_from']) ? $tools_settings['customer_email_from'] : $default_from_email;
		$customer_email_to = isset($tools_settings['customer_email_to']) ? sanitize_email($tools_settings['customer_email_to']) : '';
		$customer_email_subject = isset($tools_settings['customer_email_subject']) ? sanitize_text_field($tools_settings['customer_email_subject']) : '';
		$customer_email_body = isset($tools_settings['customer_email_body']) ? sanitize_textarea_field($tools_settings['customer_email_body']) : '';

		?>

		<div class="postbox">
			<h3 class='hndle'><label for='title'><?php _e( 'Send Email to Customers', 'wp-express-checkout' ) ?></label></h3>
			<div class="inside">

				<p class="description">
					<?php _e('You can use this feature to send a quick email to your customers. If you want to re-send a download link for an order, first get the download link(s) from the Orders menu of the order in question, then email it to them using the following option.', 'wp-express-checkout') ?>
				</p>

				<form method="post" action="">
					<table class="form-table" role="presentation">
						<tbody>
						<tr>
							<th scope="row">
								<label for="wp-ppdg-customer_email_from"><?php _e('From Email Address', 'wp-express-checkout') ?></label>
							</th>
							<td>
								<input type="text"
								       id="wp-ppdg-customer_email_from"
								       name="customer_email_from"
								       value="<?php esc_attr_e($customer_email_from); ?>"
								       size="40"
								       required
								>
								<p class="description"><?php _e('This email will appear in the from field of the email.', 'wp-express-checkout'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wp-ppdg-customer_email_to"><?php _e('To Email Address', 'wp-express-checkout') ?></label>
							</th>
							<td>
								<input type="email"
								       id="wp-ppdg-customer_email_to"
								       name="customer_email_to"
								       value="<?php esc_attr_e($customer_email_to); ?>"
								       size="40"
								       required
								>
								<p class="description"><?php _e('This is the email address where the email with be sent to.', 'wp-express-checkout') ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wp-ppdg-customer_email_subject"><?php _e('Email Subject', 'wp-express-checkout') ?></label>
							</th>
							<td>
								<input type="text"
								       id="wp-ppdg-customer_email_subject"
								       name="customer_email_subject"
								       value="<?php esc_attr_e($customer_email_subject); ?>"
								       size="40"
								       required
								>
								<p class="description"><?php _e('This is the email subject', 'wp-express-checkout') ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wp-ppdg-customer_email_body"><?php _e('Email Body', 'wp-express-checkout') ?></label>
							</th>
							<td>
                            <textarea name="customer_email_body"
                                      id="wp-ppdg-customer_email_body"
                                      style="width:100%;"
                                      rows="7"
                                      required
                            ><?php echo esc_textarea($customer_email_body); ?></textarea>
								<p class="description"><?php _e('Type your email and hit the Send Email button.', 'wp-express-checkout') ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"></th>
							<td>
								<button type="submit" class="button"><?php _e('Send Email', 'wp-express-checkout') ?> &gt;&gt;</button>
							</td>
						</tr>
						</tbody>
					</table>

					<input type="hidden" name="wpec-send-custom-email-submit">
					<?php wp_nonce_field('wpec-send-custom-email-nonce-action'); ?>

				</form>
			</div>
		</div>
		<?php
	}
}