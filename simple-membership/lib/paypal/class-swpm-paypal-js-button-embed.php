<?php

class SWPM_PayPal_JS_Button_Embed {
	protected static $instance;

    protected static $on_page_payment_buttons = array();
    public $button_id_prefix = 'swpm_paypal_button_';
	public $settings_args = array();

	function __construct() {
		// add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		// add_action('wp_footer', array($this, 'render_buttons'));
	}

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/*
	Set the settings args that will be used to generate the PayPal JS SDK arguments.
	*/
	public function set_settings_args( $settings_args ) {
		//Example settings args array
		/*
		$settings_args = array(
			'is_live_mode' => 0,
			'live_client_id' => 'THE LIVE CLIENT ID',
			'sandbox_client_id' => 'THE SANDBOX CLIENT ID',
			'currency' => 'USD',
			'disable-funding' => '', //array('card', 'credit', 'venmo')
			'is_subscription' => 0,
		);
		*/
		$this->settings_args = $settings_args;
	}

	public function get_next_button_id() {
		$next_button_id = $this->button_id_prefix . count(self::$on_page_payment_buttons);
		self::$on_page_payment_buttons[] = $next_button_id;
		return $next_button_id;
	}

	/*
	 * Generate the arguments for the PayPal JS SDK. It will be used to load the SDK script.
	 */
	public function generate_paypal_js_sdk_args(){

		//Reference - https://developer.paypal.com/sdk/js/configuration/
		$sdk_args = array();
		$sdk_args['client-id'] = $this->settings_args['is_live_mode'] ? $this->settings_args['live_client_id'] : $this->settings_args['sandbox_client_id'];
		$sdk_args['intent'] = 'capture';
		$sdk_args['currency'] = $this->settings_args['currency'];

		if ( isset( $this->settings_args['is_subscription'] ) && ! empty( $this->settings_args['is_subscription'] ) ) {
			//Enable vault for subscription payments.
			$sdk_args['vault'] = 'true';
		}

		// Enable Venmo by default (could be disabled by 'disable-funding' option).
		$sdk_args['enable-funding']  = 'venmo';
		// Required for Venmo in sandbox.
		if ( ! $this->settings_args['is_live_mode'] ) {
			$sdk_args['buyer-country']  = 'US';
		}

		//Check disable funding options.
		$disabled_funding = isset( $this->settings_args['disable-funding'] ) ? $this->settings_args['disable-funding'] : '';
		if ( is_array( $disabled_funding ) && ! empty( $disabled_funding ) ) {
			// Convert array to comma separated string.
			$disable_funding_arg = '';
			foreach ( $disabled_funding as $funding ) {
				$disable_funding_arg .= $funding . ',';
			}
			$disable_funding_arg = rtrim( $disable_funding_arg, ',' );
			$sdk_args['disable-funding'] = $disable_funding_arg;
		}

		//Trigger filter hook so the PayPal SDK arguments can be modified.
		$sdk_args = apply_filters( 'swpm_generate_paypal_js_sdk_args', $sdk_args );
		return $sdk_args;
	}

	/**
	 * Load the PayPal JS SDK Script in the footer.
	 * 
	 * It will be called from the button's shortcode (using a hook) if at least one button is present on the page.
	 * The button's JS code needs to be executed after the SDK is loaded. Check for 'swpm_paypal_sdk_loaded' event.
	 */
	public function load_paypal_sdk() {
		$sdk_args = $this->generate_paypal_js_sdk_args();

		$script_url = add_query_arg( $sdk_args, 'https://www.paypal.com/sdk/js' );
		?>
		<script type="text/javascript">
			var script = document.createElement( 'script' );
			script.type = 'text/javascript';
			script.setAttribute( 'data-partner-attribution-id', 'TipsandTricks_SP' );
			script.async = true;
			script.src = '<?php echo esc_url_raw( $script_url ); ?>';
			script.onload = function() {
				jQuery( function( $ ) { $( document ).trigger( 'swpm_paypal_sdk_loaded' ) } );
			};
			document.getElementsByTagName( 'head' )[0].appendChild( script );
		</script>
		<?php
	}

	/**
	 * Generate the PayPal JS SDK Script.
	 * 
	 * It can be called to get the SDK script that can be used right where you want to output it.
	 */
	public function generate_paypal_sdk_script_output() {
		$sdk_args = $this->generate_paypal_js_sdk_args();
		$script_url = add_query_arg( $sdk_args, 'https://www.paypal.com/sdk/js' );

		$output = '<script src="' . esc_url_raw( $script_url ) . '" data-partner-attribution-id="TipsandTricks_SP"></script>';
		return $output;
	}

}