<?php

/**
 * Asking users for their experience with the plugin.
 */
class SWPM_Admin_User_Feedback {

	/**
	 * The wp option for notice dismissal data.
	 */
	const OPTION_NAME = 'swpm_plugin_user_feedback_notice';

	/**
	 * How many days after activation it should display the user feedback notice.
	 */
	const DELAY_NOTICE = 14;

	/**
	 * Initialize user feedback notice functionality.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'maybe_display' ) );
		add_action( 'wp_ajax_swpm_feedback_notice_dismiss', array( $this, 'feedback_notice_dismiss' ) );
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
		$activated = get_option( 'swpm_plugin_activated_time' );
		if(empty($activated)){
			add_option( 'swpm_plugin_activated_time', time() );
		}

		// Skip if the plugin is active for less than a defined number of days.
		if ( empty( $activated ) || ( $activated + ( DAY_IN_SECONDS * self::DELAY_NOTICE ) ) > time() ) {
			// Not enough time;
			return;
		}

		?>
		<div class="notice notice-info is-dismissible swpm-plugin-review-notice">
			<div class="swpm-plugin-review-step swpm-plugin-review-step-1">
				<p><?php esc_html_e( 'Are you enjoying the Simple Membership plugin?', 'simple-membership' ); ?></p>
				<p>
					<a href="#" class="swpm-plugin-review-switch-step" data-step="3"><?php esc_html_e( 'Yes', 'simple-membership' ); ?></a><br />
					<a href="#" class="swpm-plugin-review-switch-step" data-step="2"><?php esc_html_e( 'Not Really', 'simple-membership' ); ?></a>
				</p>
			</div>
			<div class="swpm-plugin-review-step swpm-plugin-review-step-2" style="display: none">
				<p><?php esc_html_e( 'We\'re sorry to hear you aren\'t enjoying the Simple Membership plugin. We would love a chance to improve. Could you take a minute and let us know what we can do better by using our contact form? ', 'simple-membership' ); ?></p>
				<p>
					<?php
					printf(
						'<a href="https://simple-membership-plugin.com/contact/" class="swpm-plugin-dismiss-review-notice swpm-plugin-review-out" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_html__( 'Give Feedback', 'simple-membership' )
					);
					?>
					<br>
					<a href="#" class="swpm-plugin-dismiss-review-notice" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'No thanks', 'simple-membership' ); ?>
					</a>
				</p>
			</div>
			<div class="swpm-plugin-review-step swpm-plugin-review-step-3" style="display: none">
				<p><?php esc_html_e( 'That\'s great! Could you please do me a big favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'simple-membership' ); ?></p>
				<p><strong><?php esc_html_e( '~ Simple Membership Plugin Team', 'simple-membership' ) ?></strong></p>
				<p>
					<a href="https://wordpress.org/support/plugin/simple-membership/reviews/?filter=5#new-post" class="swpm-plugin-dismiss-review-notice swpm-plugin-review-out" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'OK, you deserve it', 'simple-membership' ); ?>
					</a><br>
					<a href="#" class="swpm-plugin-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Nope, maybe later', 'simple-membership' ); ?></a><br>
					<a href="#" class="swpm-plugin-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'I already did', 'simple-membership' ); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( document ).on( 'click', '.swpm-plugin-dismiss-review-notice, .swpm-plugin-review-notice button', function( e ) {
					if ( ! $( this ).hasClass( 'swpm-plugin-review-out' ) ) {
						e.preventDefault();
					}
					$.post( ajaxurl, { action: 'swpm_feedback_notice_dismiss' } );
					$( '.swpm-plugin-review-notice' ).remove();
				} );

				$( document ).on( 'click', '.swpm-plugin-review-switch-step', function( e ) {
					e.preventDefault();
					var target = parseInt( $( this ).attr( 'data-step' ), 10 );

					if ( target ) {
						var $notice = $( this ).closest( '.swpm-plugin-review-notice' );
						var $review_step = $notice.find( '.swpm-plugin-review-step-' + target );

						if ( $review_step.length > 0 ) {
							$notice.find( '.swpm-plugin-review-step:visible' ).fadeOut( function() {
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
		$all_levels_ary = SwpmMembershipLevelUtils::get_all_membership_levels_in_array();
		if(!empty($all_levels_ary)){
			//Membership level has been configured.
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
