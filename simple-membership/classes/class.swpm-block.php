<?php

class SWPMBlocks {
	function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		$deps = array( 'wp-blocks', 'wp-element', 'wp-components' );

		$wp_version = get_bloginfo( 'version' );

		if ( version_compare( $wp_version, '5.8.0', '<' ) ) {
			array_push( $deps, 'wp-editor' );
		}

		wp_register_script(
			'swpm_payment_button_block',
			SIMPLE_WP_MEMBERSHIP_URL . '/js/block.js',
			$deps,
			SIMPLE_WP_MEMBERSHIP_VER,
			true
		);

		$swpmBtnOpts     = "const swpmBtnOpts = " . json_encode( $this->get_products_array() );
		$swpmBlockBtnStr = "const swpmBlockBtnStr = " . json_encode( array(
				'title'         => 'Simple Membership Payment Button',
				'description'   => __( 'Prompt visitors to take action with a simple membership payment button.', 'simple-membership' ),
				'paymentButton' => __( 'Payment button', 'simple-membership' ),
			) );
		wp_add_inline_script( 'swpm_payment_button_block', $swpmBtnOpts, 'before' );
		wp_add_inline_script(
			'swpm_payment_button_block',
			$swpmBlockBtnStr,
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

	function render_payment_button_block( $atts ) {

		$btnId = ! empty( $atts['btnId'] ) ? intval( $atts['btnId'] ) : 0;

		if ( empty( $btnId ) ) {
			return '<p>' . __( 'Select an item to view', 'simple-membership' ) . '</p>';
		}

		$sc_str = 'swpm_payment_button id="%d"';
		$sc_str = sprintf( $sc_str, $btnId );

		return do_shortcode( '[' . $sc_str . ']' );
	}

	private function get_products_array() {
		$q       = get_posts(
			array(
				'post_type'      => 'swpm_payment_button',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$prodArr = array(
			array(
				'label' => __( '(Select Item)', 'simple-membership' ),
				'value' => 0,
			),
		);
		foreach ( $q as $post ) {
			$title     = html_entity_decode( $post->post_title );
			$prodArr[] = array(
				'label' => esc_attr( $title ),
				'value' => $post->ID,
			);
		}
		wp_reset_postdata();

		return $prodArr;
	}

}

new SWPMBlocks();