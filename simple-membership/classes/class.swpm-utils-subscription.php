<?php

class SWPM_Utils_Subscriptions
{

	private $member_id;
	private $active_statuses   = array('trialing', 'active');
	private $active_subs_count = 0;
	private $subs_count        = 0;
	private $subs              = array();
	private $active_subs       = array();

	public function __construct($member_id)
	{
		$this->member_id = $member_id;
	}

	/**
	 * Load all types of subscriptions.
	 *
	 * @return SWPM_Utils_Subscriptions
	 */
	public function load()
	{
		$subscr_id = SwpmMemberUtils::get_member_field_by_id($this->member_id, 'subscr_id');

		$subscriptions = get_posts(array(
			'post_type'  => 'swpm_transactions',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => 'member_id',
						'value'   => $this->member_id,
						'compare' => '=',
					),
					array(
						'key'     => 'subscr_id',
						'value'   => $subscr_id,
						'compare' => '=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'gateway',
						'value'   => 'stripe-sca-subs',
						'compare' => '=',
					),
					array(
						'key'     => 'gateway',
						'value'   =>  'paypal',
						'compare' => '=',
					),
					array(
						'key'     => 'gateway',
						'value'   => 'paypal_subscription_checkout',
						'compare' => '=',
					),
				),
			),
		));

		$this->subs_count = count($subscriptions);

		foreach ($subscriptions as $subscription) {
			// echo "<pre> Subscription ID: " . $subscription->ID . "</pre>";
			// echo "<pre>" . print_r( $subscription , true) . "</pre>";
			// echo "<pre>" . print_r( get_post_meta( $subscription->ID ) , true) . "</pre>";

			$sub            = array();
			$post_id        = $subscription->ID;
			
			$sub['post_id'] = $post_id;
			$sub_id         = get_post_meta($post_id, 'subscr_id', true);

			$sub['sub_id'] = $sub_id;

			$sub['gateway'] = get_post_meta($post_id, 'gateway', true);

			// TODO: Need to fix this, assuming all pp subs active
			$status = $sub['gateway'] == 'paypal_subscription_checkout' ? 'active' : get_post_meta($post_id, 'subscr_status', true);

			$sub['status'] = $status;

			$cancel_token = get_post_meta($post_id, 'subscr_cancel_token', true);

			if (empty($cancel_token)) {
				$cancel_token = md5($post_id . $sub_id . uniqid());
				update_post_meta($post_id, 'subscr_cancel_token', $cancel_token);
			}

			$sub['cancel_token'] = $cancel_token;

			$is_live        = get_post_meta($post_id, 'is_live', true);
			$is_live        = empty($is_live) ? false : true;
			$sub['is_live'] = $is_live;

			$payment_button_id = get_post_meta($post_id, 'payment_button_id', true);
			$sub['payment_button_id'] = $payment_button_id;

			$sub['plan'] = get_the_title($payment_button_id);
			// echo "<pre> Payment button id: " . $payment_button_id . "</pre>";
			// echo "<pre> Subscription plan: " . $sub['plan'] . "</pre>";
			// $sub['type'] = get_post_meta($payment_button_id, 'button_type', true);

			if ($this->is_active($status)) {
				$this->active_subs_count++;
				$this->active_subs[$sub_id] = $sub;
			}

			$this->subs[$sub_id] = $sub;
		}

		// return $this->subs;

		// $this->recheck_status_if_needed();

		return $this;
	}


	/**
	 * Load stripe subscriptions only. (Old Method)
	 *
	 * @return SWPM_Utils_Subscriptions
	 */
	public function load_stripe_subscriptions()
	{
		$subscr_id = SwpmMemberUtils::get_member_field_by_id($this->member_id, 'subscr_id');

		$query_args = array(
			'post_type'  => 'swpm_transactions',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => 'member_id',
						'value'   => $this->member_id,
						'compare' => '=',
					),
					array(
						'key'     => 'subscr_id',
						'value'   => $subscr_id,
						'compare' => '=',
					),
				),
				array(
					'key'     => 'gateway',
					'value'   => 'stripe-sca-subs',
					'compare' => '=',
				),
			),
		);

		$found_subs = new WP_Query($query_args);

		$this->subs_count = $found_subs->post_count;

		foreach ($found_subs->posts as $found_sub) {
			$sub            = array();
			$post_id        = $found_sub->ID;
			$sub['post_id'] = $post_id;
			$sub_id         = get_post_meta($post_id, 'subscr_id', true);

			$sub['sub_id'] = $sub_id;

			$status = get_post_meta($post_id, 'subscr_status', true);

			$sub['status'] = $status;

			$cancel_token = get_post_meta($post_id, 'subscr_cancel_token', true);

			if (empty($cancel_token)) {
				$cancel_token = md5($post_id . $sub_id . uniqid());
				update_post_meta($post_id, 'subscr_cancel_token', $cancel_token);
			}

			$sub['cancel_token'] = $cancel_token;

			$is_live        = get_post_meta($post_id, 'is_live', true);
			$is_live        = empty($is_live) ? false : true;
			$sub['is_live'] = $is_live;

			$sub['payment_button_id'] = get_post_meta($post_id, 'payment_button_id', true);

			if ($this->is_active($status)) {
				$this->active_subs_count++;
				$this->active_subs[$sub_id] = $sub;
			}

			$this->subs[$sub_id] = $sub;
		}

		$this->recheck_status_if_needed();

		return $this;
	}

	public function get_active_subscriptions()
	{
		return $this->active_subs;
	}

	public function get_active_subs_count()
	{
		return $this->active_subs_count;
	}

	public function is_active($status)
	{
		return  in_array($status, $this->active_statuses, true);
	}

	private function recheck_status_if_needed()
	{
		foreach ($this->subs as $sub_id => $sub) {
			if (!empty($sub['status'])) {
				continue;
			}
			try {
				$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button($sub['payment_button_id'], $sub['is_live']);

				SwpmMiscUtils::load_stripe_lib();

				\Stripe\Stripe::setApiKey($api_keys['secret']);

				$stripe_sub = \Stripe\Subscription::retrieve($sub_id);

				$this->subs[$sub_id]['status'] = $stripe_sub['status'];

				if ($this->is_active($stripe_sub['status'])) {
					$this->active_subs_count++;
				}

				update_post_meta($sub['post_id'], 'subscr_status', $stripe_sub['status']);
			} catch (\Exception $e) {
				return false;
			}
		}
	}

	public function get_stripe_subs_cancel_url($args, $sub_id = false)
	{
		if (empty($this->active_subs_count)) {
			return SwpmUtils::_('No active subscriptions');
		}
		if (false === $sub_id) {
			$sub_id = array_key_first($this->subs);
		}
		$sub = $this->subs[$sub_id];

		$token = $sub['cancel_token'];

		$nonce = wp_nonce_field($token, 'swpm_cancel_sub_nonce', false, false);

		$anchor_text = isset($args['anchor_text']) ? $args['anchor_text'] : SwpmUtils::_('Cancel Subscription');
		$out = '<form method="POST">%s<input type="hidden" name="swpm_cancel_sub_token" value="%s"></input>
		<button type="submit" name="swpm_do_cancel_sub" value="1" onclick="return confirm(\'' . esc_js(SwpmUtils::_('Are you sure that you want to cancel the subscription?')) . '\');">' . $anchor_text . '</button></form>';

		$out = sprintf($out, $nonce, $token);

		return $out;
	}

	public function find_by_token($token)
	{
		foreach ($this->subs as $sub_id => $sub) {
			if ($sub['cancel_token'] === $token) {
				return $sub;
			}
		}
	}

	public function handle_cancel_sub() {
		
		$token = isset( $_POST['swpm_cancel_sub_token'] ) ? sanitize_text_field( stripslashes ( $_POST['swpm_cancel_sub_token'] ) ) : '';
		if ( empty( $token ) ) {
			//no token
			self::cancel_msg( __( 'No token provided.', 'simple-membership' ) );
		}
		
		$nonce = isset( $_POST['swpm_cancel_sub_nonce'] ) ? sanitize_text_field( stripslashes ( $_POST['swpm_cancel_sub_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $token ) ) {
			// nonce check failed
			self::cancel_msg( __( 'Nonce check failed.', 'simple-membership' ) );
		}

		$gateway = isset( $_POST['swpm_cancel_sub_gateway'] ) ? sanitize_text_field( stripslashes ( $_POST['swpm_cancel_sub_gateway'] ) ) : '';
		if ( empty( $gateway ) ) {
			//no gateway
			self::cancel_msg( __( 'No gateway not specified.', 'simple-membership' ) );
		}

		$sub = $this->find_by_token( $token );

		if ( empty( $sub ) ) {
			// no subscription found
			return false;
		}

		switch($gateway){
			case 'stripe-sca-subs':
				$res = $this->cancel_subscription_stripe_sca( $sub['sub_id'] );
				break;
			case 'paypal_subscription_checkout':
				$res = $this->cancel_subscription_paypal_ppcp( $sub['sub_id'] );
				break;
			case 'paypal':
				$res = $this->cancel_subscription_paypal_standard( $sub['sub_id'] );
				break;
			default:
				$res = false;
			break;
		}

		if ( $res !== true ) {
			self::cancel_msg( $res );
		}

		$ipn_data = array();
		$ipn_data['member_id'] = $this->member_id;

		do_action( 'swpm_subscription_payment_cancelled', $ipn_data ); // Hook for subscription payment cancelled.

		self::cancel_msg( __( 'Subscription has been cancelled.', 'simple-membership' ), false );

	}

	public function cancel_subscription_stripe_sca($sub_id)
	{
		$sub = $this->subs[$sub_id];

		try {
			$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button($sub['payment_button_id'], $sub['is_live']);

			SwpmMiscUtils::load_stripe_lib();

			\Stripe\Stripe::setApiKey($api_keys['secret']);

			$stripe_sub = \Stripe\Subscription::retrieve($sub_id);

			if ($this->is_active($stripe_sub['status'])) {
				$stripe_sub->cancel();
			}

			update_post_meta($sub['post_id'], 'subscr_status', $stripe_sub['status']);
		} catch (\Exception $e) {
			return $e->getMessage();
		}
		return true;
	}

	public function cancel_subscription_paypal_ppcp($sub_id){
		// TODO

		return true;
	}

	public function cancel_subscription_paypal_standard($sub_id){
		// TODO

		return true;
	}

	public static function cancel_msg( $msg, $is_error = true ) {
		echo $msg;
		echo '<br><br>';
		echo __( 'You will be redirected to the previous page in a few seconds. If not, please <a href="">click here</a>.', 'simple-membership' );
		echo '<script>function toPrevPage(){window.location = window.location.href;}setTimeout(toPrevPage,5000);</script>';
		if ( ! $is_error ) {
			wp_die( '', __( 'Success!', 'simple-membership' ), array( 'response' => 200 ) );
		}
		wp_die();
	}

	public static function get_cancel_subscription_form(&$atts, &$subscription){
		$output = '';

		switch($subscription['gateway']){
			case 'stripe-sca-subs':
				$output = self::get_stripe_sca_cancel_form($atts, $subscription);
				break;
			case 'paypal_subscription_checkout':
				$output = self::get_paypal_ppcp_cancel_form($atts, $subscription);
				break;
			case 'paypal':
				$output = self::get_paypal_standard_cancel_form($atts, $subscription);
				break;
		}

		return $output;
	}

	public static function get_stripe_sca_cancel_form(&$atts, &$subscription){
		$token = $subscription['cancel_token'];
		ob_start();
		?>
		<form method="post" class="swpm_cancel_subscription_form">
			<?php echo wp_nonce_field( $token, 'swpm_cancel_sub_nonce', false, false );?>
			<input type="hidden" name="swpm_cancel_sub_token" value="<?php echo $token ?>">
			<input type="hidden" name="swpm_cancel_sub_id" value="<?php echo esc_attr($subscription['sub_id']) ?>">
			<input type="hidden" name="swpm_cancel_sub_gateway" value="<?php echo esc_attr($subscription['gateway']) ?>">
			<button type="submit" class="swpm_cancel_subscription_button" name="swpm_do_cancel_sub" value="1" onclick="return confirm(' <?php _e( 'Are you sure that you want to cancel the subscription?' )?> ')">
				<?php _e('Cancel Subscription') ?>
			</button>
		</form>
		<?php
		$output = ob_get_clean();

		return $output;
	}

	public static function get_paypal_standard_cancel_form(&$atts, &$subscription){
		// TODO
	}

	public static function get_paypal_ppcp_cancel_form(&$atts, &$subscription){
		$settings = SwpmSettings::get_instance();
		$sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );
		$merchant_id = $atts['merchant_id'];

		ob_start(); ?>
		
		<?php if ( empty( $atts['merchant_id'] ) ) { ?>
			<button type="submit" class="swpm_cancel_subscription_button" style="background-color: gray !important;" disabled="disabled" title="<?php _e('Merchant ID Could not be found! Specify it in the shortcode parameter first!', 'simple-membership') ?>">
				<?php _e( 'Not configured', 'simple-membership') ?>
			</button>
		<?php } else { ?>
			<div class="swpm-paypal-subscription-cancel-link">
				<?php if ( $sandbox_enabled ) { ?>
					<a class="swpm_cancel_subscription_button" href="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=<?php echo esc_attr($merchant_id) ?>" _fcksavedurl="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=<?php echo esc_attr($merchant_id) ?>">
						<?php _e('Cancel Subscription', 'simple-membership')?>
					</a>
				<?php } else { ?>
					<a class="swpm_cancel_subscription_button" href="https://www.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=<?php echo esc_attr($merchant_id) ?>" _fcksavedurl="https://www.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=<?php echo esc_attr($merchant_id) ?>">
						<?php _e('Cancel Subscription', 'simple-membership')?>
					</a>
				<?php } ?>
			</div>
		<?php }

		$output = ob_get_clean();

		return $output;
	}
}
