<?php

/**
 * Block class.
 *
 * Configures all gutenberg blocks for this plugin.
 *
 * @since 4.3.3
 */
class SWPM_Blocks {

	/**
	 * @var array Required dependencies for the block scripts.
	 */
	protected $deps = array();

	/**
	 * Initiate Block Class.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers SWPM blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		$this->deps = array( 'wp-blocks', 'wp-element', 'wp-components' );

		if ( version_compare( get_bloginfo( 'version' ), '5.8.0', '<' ) ) {
			$this->deps[] = 'wp-editor';
		}

		// Register all blocks.
		$this->register_payment_button_block();
	}

	/**
	 * Registers payment button block.
	 *
	 * @return void
	 */
	public function register_payment_button_block() {
		wp_register_script(
			'swpm_payment_button_block',
			SIMPLE_WP_MEMBERSHIP_URL . '/js/payment-button-block.js',
			$this->deps,
			SIMPLE_WP_MEMBERSHIP_VER,
			true
		);

		$swpm_button_options   = 'const swpm_button_options = ' . wp_json_encode( $this->get_payment_buttons_array() );
		$swpm_block_button_str = 'const swpm_block_button_str = ' . wp_json_encode(
			array(
				'title'         => 'Simple Membership Payment Button',
				'description'   => __( 'Prompt visitors to take action with a simple membership payment button.', 'simple-membership' ),
				'paymentButton' => __( 'Payment button', 'simple-membership' ),
			)
		);
		wp_add_inline_script( 'swpm_payment_button_block', $swpm_button_options, 'before' );
		wp_add_inline_script(
			'swpm_payment_button_block',
			$swpm_block_button_str,
			'before'
		);

		register_block_type(
			'simple-membership/payment-button',
			array(
				'attributes'      => array(
					'btnId' => array(
						'type'    => 'string',
						'default' => 0,
					),
				),
				'editor_script'   => 'swpm_payment_button_block',
				'render_callback' => array( $this, 'render_payment_button_block' ),
			)
		);
	}

	/**
	 * Renders the block ui.
	 *
	 * @param array $atts A specific ID for the panel.
	 *
	 * @return string
	 */
	public function render_payment_button_block( $atts ) {
		$button_id = ! empty( $atts['btnId'] ) ? intval( $atts['btnId'] ) : 0;

		if ( empty( $button_id ) ) {
			return '<p>' . __( 'Select an item to view', 'simple-membership' ) . '</p>';
		}

		$is_backend = defined( 'REST_REQUEST' ) && REST_REQUEST === true && filter_input( INPUT_GET, 'context', FILTER_SANITIZE_STRING ) === 'edit';

		if ( $is_backend ) {
			$button_type = get_post_meta( $button_id, 'button_type', true );

			$button_placeholder_image = '';
			switch ( $button_type ) {
				case 'pp_subscription':
				case 'pp_buy_now':
					$button_placeholder_image = 'https://www.citypng.com/public/uploads/preview/download-paypal-pay-now-blue-button-png-21635276603kcfpcsgr8p.png';
					break;

				case 'pp_subscription_new':
				case 'pp_buy_now_new':
					$button_placeholder_image = 'https://www.nicepng.com/png/detail/24-242977_paypal-credit-button.png';
					break;

				case 'stripe_sca_buy_now':
					$button_placeholder_image = 'https://user-images.githubusercontent.com/157270/38515749-f53f8392-3be9-11e8-8917-61ef78dd354a.png';
					break;

				case 'stripe_sca_subscription':
					$button_placeholder_image = 'https://www.pngitem.com/pimgs/m/291-2918799_stripe-payment-icon-png-transparent-png.png';
					break;
			}

			if ( $button_placeholder_image ) {
				$placeholder = '<img src="' . $button_placeholder_image . '" alt="Button Image" />';
			} else {
				$placeholder = '<p> Payment Button id: ' . $button_id . '</p>';
			}

			return $placeholder;
		}

		$sc_str = 'swpm_payment_button id="%d"';
		$sc_str = sprintf( $sc_str, $button_id );

		return do_shortcode( '[' . $sc_str . ']' );
	}

	/**
	 * Retrieves payment buttons.
	 *
	 * @return array[]
	 */
	private function get_payment_buttons_array() {
		$q             = get_posts(
			array(
				'post_type'      => 'swpm_payment_button',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$buttons_array = array(
			array(
				'label' => __( '(Select Item)', 'simple-membership' ),
				'value' => 0,
			),
		);
		foreach ( $q as $post ) {
			$title           = html_entity_decode( $post->post_title );
			$buttons_array[] = array(
				'label' => esc_attr( $title ),
				'value' => $post->ID,
			);
		}
		wp_reset_postdata();

		return $buttons_array;
	}

}

new SWPM_Blocks();
