<?php

/**
 * Asking users for their experience with the plugin.
 */
class WPEC_Admin_User_Feedback {

	/**
	 * The wp option for notice dismissal data.
	 */
	const OPTION_NAME = 'wpec_plugin_user_feedback_notice';

	/**
	 * How many days after activation it should display the user feedback notice.
	 */
	const DELAY_NOTICE = 14;

	/**
	 * Initialize user feedback notice functionality.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'maybe_display' ) );
		add_action( 'wp_ajax_wpec_feedback_notice_dismiss', array( $this, 'feedback_notice_dismiss' ) );
	}

	/**
	 * Maybe display the user feedback notice.
	 */
	public function maybe_display() {

		// Only admin users should see the feedback notice.
		if ( ! is_super_admin() ) {
			return;
		}

		$options = get_option( self::OPTION_NAME );

		// Set default options.
		if ( empty( $options ) ) {
			$options = array(
				'time'      => time(),
				'dismissed' => false,
			);
			update_option( self::OPTION_NAME, $options );
		}

		// Check if the feedback notice was not dismissed already.
		if ( isset( $options['dismissed'] ) && ! $options['dismissed'] ) {
			$this->display();
		}
		
	}

	/**
	 * Display the user feedback notice.
	 */
	private function display() {

		// Skip if plugin is not being utilized.
		if ( ! $this->is_plugin_configured() ) {
			return;
		}

		// Fetch when plugin was initially activated.
		$activated = get_option( 'wpec_plugin_activated_time' );
		if(empty($activated)){
			add_option( 'wpec_plugin_activated_time', time() );
		}

		// Skip if the plugin is active for less than a defined number of days.
		if ( empty( $activated ) || ( $activated + ( DAY_IN_SECONDS * self::DELAY_NOTICE ) ) > time() ) {
			// Not enough time;
			return;
		}

		?>
		<div class="notice notice-info is-dismissible wpec-plugin-review-notice">
			<div class="wpec-plugin-review-step wpec-plugin-review-step-1">
				<p><?php esc_html_e( 'Are you enjoying the WP Express Checkout plugin?', 'wp-express-checkout' ); ?></p>
				<p>
					<a href="#" class="wpec-plugin-review-switch-step" data-step="3"><?php esc_html_e( 'Yes', 'wp-express-checkout' ); ?></a><br />
					<a href="#" class="wpec-plugin-review-switch-step" data-step="2"><?php esc_html_e( 'Not Really', 'wp-express-checkout' ); ?></a>
				</p>
			</div>
			<div class="wpec-plugin-review-step wpec-plugin-review-step-2" style="display: none">
				<p><?php esc_html_e( 'We\'re sorry to hear you aren\'t enjoying the WP Express Checkout plugin. We would love a chance to improve. Could you take a minute and let us know what we can do better by using our contact form? ', 'wp-express-checkout' ); ?></p>
				<p>
					<?php
					printf(
						'<a href="https://wp-express-checkout.com/contact-us/" class="wpec-plugin-dismiss-review-notice wpec-plugin-review-out" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_html__( 'Give Feedback', 'wp-express-checkout' )
					);
					?>
					<br>
					<a href="#" class="wpec-plugin-dismiss-review-notice" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'No thanks', 'wp-express-checkout' ); ?>
					</a>
				</p>
			</div>
			<div class="wpec-plugin-review-step wpec-plugin-review-step-3" style="display: none">
				<p><?php esc_html_e( 'That\'s great! Could you please do me a big favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'wp-express-checkout' ); ?></p>
				<p><strong><?php esc_html_e( '~ WP Express Checkout Plugin Team', 'wp-express-checkout' ) ?></strong></p>
				<p>
					<a href="https://wordpress.org/support/plugin/wp-express-checkout/reviews/?filter=5#new-post" class="wpec-plugin-dismiss-review-notice wpec-plugin-review-out" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'OK, you deserve it', 'wp-express-checkout' ); ?>
					</a><br>
					<a href="#" class="wpec-plugin-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Nope, maybe later', 'wp-express-checkout' ); ?></a><br>
					<a href="#" class="wpec-plugin-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'I already did', 'wp-express-checkout' ); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( document ).on( 'click', '.wpec-plugin-dismiss-review-notice, .wpec-plugin-review-notice button', function( e ) {
					if ( ! $( this ).hasClass( 'wpec-plugin-review-out' ) ) {
						e.preventDefault();
					}
					$.post( ajaxurl, { action: 'wpec_feedback_notice_dismiss' } );
					$( '.wpec-plugin-review-notice' ).remove();
				} );

				$( document ).on( 'click', '.wpec-plugin-review-switch-step', function( e ) {
					e.preventDefault();
					var target = parseInt( $( this ).attr( 'data-step' ), 10 );

					if ( target ) {
						var $notice = $( this ).closest( '.wpec-plugin-review-notice' );
						var $review_step = $notice.find( '.wpec-plugin-review-step-' + target );

						if ( $review_step.length > 0 ) {
							$notice.find( '.wpec-plugin-review-step:visible' ).fadeOut( function() {
								$review_step.fadeIn();
							} );
						}
					}
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Check if the crucial plugin setting are configured.
	 *
	 * @return bool
	 */
	public function is_plugin_configured() {
		$loop = new WP_Query(
			array(
				'post_type'      => 'ppec-products',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		if( $loop->have_posts() ){
			//Products have been configured.
			return true;
		}
		return false;
	}

	/**
	 * Dismiss the user feedback admin notice.
	 */
	public function feedback_notice_dismiss() {

		$options = get_option( self::OPTION_NAME, array() );
		$options['time'] = time();
		$options['dismissed'] = true;

		update_option( self::OPTION_NAME, $options );

		if ( is_super_admin() && is_multisite() ) {
			$site_list = get_sites();
			foreach ( (array) $site_list as $site ) {
				switch_to_blog( $site->blog_id );

				update_option( self::OPTION_NAME, $options );

				restore_current_blog();
			}
		}

		wp_send_json_success();
	}
}
