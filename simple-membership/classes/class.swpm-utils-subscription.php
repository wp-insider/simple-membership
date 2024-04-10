<?php
/**
 * Utility class for handling subscription related tasks.
 * 
 * Loads and manages subscriptions associated with a member through different gateways.
 */
class SWPM_Utils_Subscriptions
{

	private $member_id;
	public static $active_statuses   = array('trialing', 'active');

	private $active_subs_count = 0;
	private $subs_count        = 0;

	private $subs              = array();
	private $active_subs       = array();

	public $stripe_sca_api_key_error = "";
	public $paypal_ppcp_api_key_error = "";

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
		$settings = SwpmSettings::get_instance();
		
		$subscr_id = SwpmMemberUtils::get_member_field_by_id($this->member_id, 'subscr_id');

		//Get any swpm_transactions CPT posts that are associated with the given member ID OR the given subscr_id.
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
						'value'   => 'stripe-sca-subs', // Stripe SCA
						'compare' => '=',
					),
					array(
						'key'     => 'gateway',
						'value'   => 'paypal_subscription_checkout', // PayPal PPCP
						'compare' => '=',
					),
				),
			),
		));

		$this->subs_count = count($subscriptions);

		foreach ($subscriptions as $subscription) {
			$sub            = array();
			$post_id        = $subscription->ID;
			
			$sub['post_id'] = $post_id;
			$sub_id         = get_post_meta($post_id, 'subscr_id', true);

			$sub['sub_id'] = $sub_id;

			$is_live        = get_post_meta($post_id, 'is_live', true);
			$is_live        = empty($is_live) ? false : true;
			$sub['is_live'] = $is_live;

			$payment_button_id = get_post_meta($post_id, 'payment_button_id', true);
			$sub['payment_button_id'] = $payment_button_id;

			$sub['gateway'] = get_post_meta($post_id, 'gateway', true);

			$status = '';

			// Check and get the subscription status based on the gateways.
			switch($sub['gateway']){
				case 'stripe-sca-subs':
					$stripe_sca_api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button($sub['payment_button_id'], $sub['is_live']);
					if (isset($stripe_sca_api_keys['secret']) && !empty($stripe_sca_api_keys['secret'])) {
						$status = get_post_meta($post_id, 'subscr_status', true); //This can be replaced with api call.
					}else{
						$this->stripe_sca_api_key_error = __( 'Error: Stripe API keys are not configured on your site!', 'simple-membership' );
					}
					
					break;
				case 'paypal_subscription_checkout':
					$paypal_ppcp_api_keys = array();
					if ( $is_live ) {
						$paypal_ppcp_api_keys['secret'] =  $settings->get_value('paypal-live-secret-key');
					} else {
						$paypal_ppcp_api_keys['secret'] =  $settings->get_value('paypal-sandbox-secret-key');
					}

					if (isset($paypal_ppcp_api_keys['secret']) && !empty($paypal_ppcp_api_keys['secret'])) {
						$sub_details = (new SWPM_PayPal_Request_API_Injector())->get_paypal_subscription_details( $sub_id );
						if( $sub_details !== false ){
							$status = strtolower($sub_details->status);
						}
					}else{
						$this->paypal_ppcp_api_key_error = __( 'Error: PayPal PPCP API keys are not configured on your site!', 'simple-membership' );
					}

					break;
			}
			$sub['status'] = $status;

			$cancel_token = get_post_meta($post_id, 'subscr_cancel_token', true);

			if (empty($cancel_token)) {
				$cancel_token = md5($post_id . $sub_id . uniqid());
				update_post_meta($post_id, 'subscr_cancel_token', $cancel_token);
			}

			$sub['cancel_token'] = $cancel_token;

			$sub['plan'] = get_the_title($payment_button_id);

			if ($this->is_active($status)) {
				$this->active_subs_count++;
				$this->active_subs[$sub_id] = $sub;
			}

			$this->subs[$sub_id] = $sub;
			$this->subs_count++;
		}

		return $this;
	}


	/**
	 * Load stripe subscriptions only. (Old method that is used by the stripe subscription cancel shortcode)
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

	/**
	 * Get the lists of active subscriptions' details only.
	 *
	 * @return array
	 */
	public function get_active_subscriptions()
	{
		return $this->active_subs;
	}

	/**
	 * Get the lists of all subscriptions' details.
	 *
	 * @return array
	 */
	public function get_all_subscriptions()
	{
		return $this->subs;
	}

	/**
	 * Get the active subscriptions count.
	 *
	 * @return int
	 */
	public function get_active_subs_count()
	{
		return $this->active_subs_count;
	}

	/**
	 * Get subscriptions count.
	 *
	 * @return int
	 */
	public function get_all_subs_count()
	{
		return $this->subs_count;
	}

	/**
	 * Checks if subscription status of active.
	 *
	 * @param string $status Subscription status.
	 * 
	 * @return boolean True if 'active' or 'trialing', false otherwise.
	 */
	public static function is_active($status)
	{
		return in_array($status, self::$active_statuses, true);
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

	/**
	 * Generates HTML form for the 'swpm_stripe_subscription_cancel_link' shortcode.
	 *
	 * @param array $args
	 * @param boolean $sub_id The subscription ID.
	 * 
	 * @return string HTML as string for string sca subscription cancel form.
	 */
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

	/**
	 * Searches subscription details by token
	 *
	 * @param string $token Subscription cancel token.
	 * 
	 * @return array Subscription details.
	 */
	public function find_by_token($token)
	{
		foreach ($this->subs as $sub_id => $sub) {
			if ($sub['cancel_token'] === $token) {
				return $sub;
			}
		}
	}

	/**
	 * Handles subscription cancellation task after the subscription cancel from is submitted.
	 *
	 * @return void
	 */
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
				$res = $this->cancel_subscription_paypal( $sub['sub_id'] );
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

	/**
	 * Triggers the subscription cancellation api for Stripe SCA.
	 *
	 * @param string $sub_id The subscription ID.
	 * 
	 * @return bool|string True on success, Error message string on failure.
	 */
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

	/**
	 * Triggers the subscription cancellation api for both PayPal PPCP and PayPal Standard.
	 *
	 * @param string $subscription_id The subscription ID.
	 * 
	 * @return bool|string True on success, Error message string on failure.
	 */
	public function cancel_subscription_paypal($subscription_id){
		$api_injector = new SWPM_PayPal_Request_API_Injector();
		$sub_details = $api_injector->get_paypal_subscription_details( $subscription_id );
		if( $sub_details !== false ){
			//Log debug that we found a subscription with the given subscription_id
			SwpmLog::log_simple_debug("PayPal PPCP subscription details found for subscription ID: " . $subscription_id, true);
			//Make the API call to cancel the PPCP subscription
			$cancel_succeeded = $api_injector->cancel_paypal_subscription( $subscription_id );
			if( $cancel_succeeded ){
				SwpmLog::log_simple_debug("PayPal PPCP subscription cancelled successfully.", true);
				return true;
			}else{
				SwpmLog::log_simple_debug("PayPal PPCP subscription cancellation failed.", false);
				return __("PayPal PPCP subscription cancellation failed!", 'simple-membership');
			}
		}

		//Could not find the subscription details for the given subscription ID
		$not_found_error_msg = __("Error! PayPal PPCP subscription details could not be found for subscription ID: ", 'simple-membership') . $subscription_id;
		SwpmLog::log_simple_debug($not_found_error_msg, false);

		return $not_found_error_msg;
	}

	/**
	 * Shows feedback message after subscription cancel request. 
	 *
	 * @param string $msg Success or error message.
	 * @param boolean $is_error Whether it is an error message or not.
	 * 
	 * @return void
	 */
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

	/**
	 * The HTML form for subscription cancellation of all gateways.
	 * Used by the 'swpm_show_active_subscription_and_cancel_button' shortcode.
	 * 
	 * @param array $subscription Subscription Details.
	 * 
	 * @return string HTML of cancel form as string.
	 */
	public static function get_cancel_subscription_form(&$subscription){		
		if (self::is_active($subscription['status'])) {
			// subscription is active!
			$token = $subscription['cancel_token'];
			ob_start();
			?>
			<form method="post" class="swpm_cancel_subscription_form">
				<?php echo wp_nonce_field( $token, 'swpm_cancel_sub_nonce', false, false );?>
				<input type="hidden" name="swpm_cancel_sub_token" value="<?php echo $token ?>">
				<input type="hidden" name="swpm_cancel_sub_gateway" value="<?php echo esc_attr($subscription['gateway']) ?>">
				<button type="submit" class="swpm_cancel_subscription_button swpm_cancel_subscription_button_active" name="swpm_do_cancel_sub" value="1" onclick="return confirm(' <?php _e( 'Are you sure that you want to cancel the subscription?', 'simple-membership' )?> ')">
					<?php _e('Cancel Subscription', 'simple-membership') ?>
				</button>
			</form>
			<?php
			return ob_get_clean();
		}

		// subscription is inactive!
		ob_start();
		?>
		<button 
			type="button" 
			class="swpm_cancel_subscription_button swpm_cancel_subscription_button_inactive" 
			title="<?php _e('This subscription is currently inactive.', 'simple-membership') ?>"
			disabled>
			<?php echo esc_attr(ucfirst($subscription['status'])) ?>
		</button>
		<?php
		return ob_get_clean();
	}

	public function get_any_stripe_sca_api_key_error(){
		return $this->stripe_sca_api_key_error;
	}

	public function get_any_paypal_ppcp_api_key_error(){
		return $this->paypal_ppcp_api_key_error;
	}
}
