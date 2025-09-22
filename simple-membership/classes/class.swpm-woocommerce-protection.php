<?php

class SwpmWooCommerceProtection {

	public function __construct() {
		$this->protect_wc_single_page();
	}

	public function protect_wc_single_page() {
		// Applying woo single product page based on theme type.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$this->protect_in_fse_theme();
		} else {
			$this->protect_in_classic_theme();
		}
	}

	public function protect_in_classic_theme() {
		//add_action( 'woocommerce_before_single_product', array( $this, 'handle_woocommerce_before_single_product') );
		//add_action( 'woocommerce_after_single_product', array( $this, 'handle_woocommerce_after_single_product') );

		add_filter('wc_get_template_part', array($this, 'override_woo_product_single_page_content'), 9999, 3);
	}

	public function protect_in_fse_theme() {
		add_filter( 'render_block', array( $this, 'handle_wc_blocks_render_fse' ), 10, 2 );
		add_action( 'woocommerce_before_single_product', array($this, 'handle_woocommerce_before_single_product_fse'));
		add_action( 'woocommerce_after_single_product', array( $this, 'handle_woocommerce_after_single_product_fse') );
	}

	//	public function handle_woocommerce_before_single_product() {
	//		$post_id                          = get_the_ID();
	//		$access_control                   = SwpmAccessControl::get_instance();
	//		$is_post_visible_for_current_user = $access_control->can_i_read_post_by_post_id( $post_id );
	//		if ( ! $is_post_visible_for_current_user ) {
	//			ob_start();
	//		}
	//	}

	//	public function handle_woocommerce_after_single_product() {
	//		$post_id                          = get_the_ID();
	//		$access_control                   = SwpmAccessControl::get_instance();
	//		$is_post_visible_for_current_user = $access_control->can_i_read_post_by_post_id( $post_id );
	//		if ( ! $is_post_visible_for_current_user ) {
	//			ob_end_clean();
	//			$protection_msg = $access_control->get_lastError();
	//
	//			echo wpautop( wp_kses_post( $protection_msg ) );
	//		}
	//	}

	public function override_woo_product_single_page_content($template, $slug, $name) {
		if ($slug != 'content' || $name != 'single-product'){
			return $template;
		}

		$post_id                          = get_the_ID();
		$access_control                   = SwpmAccessControl::get_instance();
		$is_post_visible_for_current_user = $access_control->can_i_read_post_by_post_id( $post_id );
		if ( ! $is_post_visible_for_current_user ) {
			$template = SIMPLE_WP_MEMBERSHIP_PATH . '/views/template-overrides/woocommerce/content-single-product.php';
		}

		return $template;
	}

	public function handle_wc_blocks_render_fse( $block_content, $block ) {
		if (!is_single()){
			return $block_content;
		}

		if ( function_exists( 'is_product' ) && !is_product() ) {
			return $block_content;
		}

		$post_id                          = get_the_ID();
		$access_control                   = SwpmAccessControl::get_instance();
		$is_post_visible_for_current_user = $access_control->can_i_read_post_by_post_id( $post_id );
		if ( ! $is_post_visible_for_current_user ) {
			if ( isset($block['blockName']) && ! empty( $block['blockName'] )
			     && (
				     stripos( $block['blockName'], 'woocommerce' ) !== false
				     && stripos( $block['blockName'], 'woocommerce/mini-cart' ) === false
				     && ! in_array( $block['blockName'], array(
					     'woocommerce/customer-account',
					     'woocommerce/breadcrumbs',
					     'woocommerce/empty-mini-cart-contents-block',
					     'woocommerce/filled-mini-cart-contents-block',
				     ) )
			     )
			) {
				return '';
			}
		}

		return $block_content;
	}

	public function handle_woocommerce_before_single_product_fse() {
		$post_id                          = get_the_ID();
		$access_control                   = SwpmAccessControl::get_instance();
		$is_post_visible_for_current_user = $access_control->can_i_read_post_by_post_id( $post_id );
		if ( ! $is_post_visible_for_current_user ) {
		?>
			<style>
				.woocommerce .wp-block-columns .wp-block-column:first-child{
					display: none;
				}
                .woocommerce .wp-block-post-excerpt{
                    display: none;
                }
			</style>
		<?php
		}
	}

	public function handle_woocommerce_after_single_product_fse() {
		$post_id                          = get_the_ID();
		$access_control                   = SwpmAccessControl::get_instance();
		$is_post_visible_for_current_user = $access_control->can_i_read_post_by_post_id( $post_id );
		if ( ! $is_post_visible_for_current_user ) {
			$protection_msg = $access_control->get_lastError();

			echo '<div class="swpm-woocommerce-page-protection-msg">';
			echo '<div class="wp-block-group has-global-padding">';
			echo wpautop( wp_kses_post( $protection_msg ) );
			echo '</div>';
			echo '</div>';
		}
	}
}

new SwpmWooCommerceProtection();
