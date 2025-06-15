<?php
/**
 * Utility class for handling subscription related tasks.
 * 
 * Loads and manages subscriptions associated with a member through different gateways.
 * 
 * Transaction types (txn_type) for the various subscriptions are: 
 * stripe_subscription_new, pp_subscription_new, pp_std_subscription_new
 */
class SWPM_Utils_Subscriptions
{
	private $member_id;
	public static $active_statuses = array('trialing', 'active');

	public static $last_active_sub;

	private $active_subs_count = 0;
	private $active_subs = array();//Used to store active subscriptions data only.
	private $subs_count = 0;
	private $subs = array();//Used to store all subscriptions data.

	public $stripe_sca_api_key_error = "";
	public $paypal_ppcp_api_key_error = "";

	public $subscr_id_attached_to_profile = "";

    public $settings;

    private $is_stripe_lib_loaded = false;

	public function __construct($member_id)
	{
		$this->member_id = $member_id;

        $this->settings = SwpmSettings::get_instance();

        // Get the subscr_id (the value of 'subscr_id' field in members table) currently attached to this member's profile.
        $this->subscr_id_attached_to_profile = SwpmMemberUtils::get_member_field_by_id($member_id, 'subscr_id');
	}

	/**
	 * Load the types of subscriptions that we want to show in the subscriptions list.
	 */
	public function load_subs_data()
	{
		//Get any swpm_transactions CPT posts that are associated with the given member ID OR the given subscr_id.
		$subscriptions = get_posts(array(
			'post_type'  => 'swpm_transactions',
            'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',/* We are looking for subscriptions that are associated with the given member ID OR the given subscr_id */
					array(
						'key'     => 'member_id',
						'value'   => $this->member_id,
						'compare' => '=',
					),
					array(
						'key'     => 'subscr_id',
						'value'   => $this->subscr_id_attached_to_profile,
						'compare' => '=',
					),
				),
				array(
					'relation' => 'OR',/* We are looking for subscriptions that are created using Stripe SCA or PayPal PPCP */
					array(
						'key'     => 'gateway',
						'value'   => 'stripe-sca-subs',
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

		//Loop through the found subscriptions and get the details to create a curated list of subscriptions. 
		//It will be used to show the subscriptions list later.
		foreach ($subscriptions as $subscription) {
			if ( !is_numeric($subscription->ID) ) {
				continue;
			}

			$sub = $this->create_subscription_data_array($subscription);

            if (is_null($sub)){
                continue;
            }

            $sub_id = isset($sub['sub_id']) ? $sub['sub_id'] : '';

            $status = isset($sub['status']) ? $sub['status'] : '';

			if ($this->is_active_status($status)) {
				$this->active_subs[$sub_id] = $sub;
			}

			$this->subs[$sub_id] = $sub;
		}
	}

    public function load_subs_data_by_sub_id($subscr_id )
    {
        // Get any swpm_transactions CPT posts that are associated with the given member ID AND the given subscr_id.
        $subscriptions = get_posts(array(
            'post_type'  => 'swpm_transactions',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'AND', // We are looking for subscriptions that are associated with the given member ID AND the given subscr_id
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
                    'relation' => 'OR',// We are looking for subscriptions that are created using Stripe SCA or PayPal PPCP.
                    array(
                        'key'     => 'gateway',
                        'value'   => 'stripe-sca-subs',
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

        // Loop through the found subscriptions and get the details to create a curated list of subscriptions.
        foreach ($subscriptions as $subscription) {
            if ( !is_numeric($subscription->ID) ) {
                continue;
            }

            $sub = $this->create_subscription_data_array($subscription);

            if (is_null($sub)){
                continue;
            }

            $sub_id = $sub['sub_id'];

            $this->subs[$sub_id] = $sub;
        }

    }

    /**
     * Takes a subscription Transaction CPT and then creates an array with the useful data.
     *
     * @param WP_Post $subscription
     *
     * @return array|null
     */
    public function create_subscription_data_array( $subscription )
    {
		/*
		//Example of data that will be returned.
		[sub_1PyVdBFP7UDYJ9v5E81jJq8A] => Array
        (
            [payment_button_id] => 210
            [post_id] => 1230
            [sub_id] => sub_1PyVdBFP7UDYJ9v5E81jJq8A
            [gateway] => stripe-sca-subs
            [is_live] => 
            [status] => active
            [cancel_token] => 50635e21dc084589182aa23e16ec1dac
            [plan] => Stripe SCA Subscription
        )
		*/

        $sub = array();
        $post_id = $subscription->ID;

        $payment_button_id = get_post_meta($post_id, 'payment_button_id', true);
        $sub['payment_button_id'] = $payment_button_id;

        $sub['post_id'] = $post_id;
        $sub_id = get_post_meta($post_id, 'subscr_id', true);

        $sub['sub_id'] = $sub_id;

        //Check if this subscription is the one that is currently attached to the member's profile.
        if ( $sub_id == $this->subscr_id_attached_to_profile ) {
            //This is the subscription that is currently attached to the member's profile.
            //We will use it to show a msg for the subscription that is currently being used for membership access of this member.
            $sub['is_attached_to_profile'] = 'yes';
        }

        //Get the environment mode (live or sandbox) of the subscription.
        $is_live = get_post_meta($post_id, 'is_live', true);

        //Get the gateway that was used to create this subscription.
        $sub['gateway'] = get_post_meta($post_id, 'gateway', true);
        if( !isset($sub['gateway']) || empty($sub['gateway']) ){
            //Gateway is not set. This is an invalid subscription. Skip it.
            return null;
        }

        // Check and get the subscription status based on the gateways.
        $status = '';
        switch($sub['gateway']){
            case 'stripe-sca-subs':
                //Check if this is a valid stripe sca subscription created entry. Also check backward compatibility (when the status postmenta used to save as 'completed').
                $txn_status = get_post_meta($post_id, 'status', true);
                $statuses_for_actual_sub_txn = array('subscription created', 'completed');
                if( !in_array($txn_status, $statuses_for_actual_sub_txn)){
                    //This is not a stripe sca subscription created entry. Nothing to do here. Go to the next entry.
                    return null;
                }

                // In case of Stripe, is_live value is saved as '1' or '' in the post meta.
                $sub['is_live'] = empty($is_live) ? false : true;

                $stripe_sca_api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button($sub['payment_button_id'], $sub['is_live']);

                if (isset($stripe_sca_api_keys['secret']) && !empty($stripe_sca_api_keys['secret'])) {
                    // $status = get_post_meta($post_id, 'subscr_status', true); //This has replaced by api call.

                    // Check if stripe lib loaded once to prevent loading on every iteration.
                    if (!$this->is_stripe_lib_loaded){
                        SwpmMiscUtils::load_stripe_lib();
                        $this->is_stripe_lib_loaded = true;
                    }

                    \Stripe\Stripe::setApiKey($stripe_sca_api_keys['secret']);

                    try {
                        $stripe_sub = \Stripe\Subscription::retrieve($sub_id);
                        $status = $stripe_sub['status'];
                    } catch ( \Stripe\Exception\ApiErrorException $e){
                        $this->stripe_sca_api_key_error = __( 'Error: Subscription details for subscription id: '. $sub_id .' could not be retrieved from Stripe.', 'simple-membership' );
                    }

                }else{
                    $this->stripe_sca_api_key_error = __( 'Error: Stripe API keys are not configured on your site!', 'simple-membership' );
                }

                break;
            case 'paypal_subscription_checkout':
                //Check if this is a valid PayPal PPCP subscription created entry.
                $txn_status = get_post_meta($post_id, 'status', true);
                if( $txn_status != 'subscription created' ){
                    //This is not a PPCP subscription created entry. Nothing to do here. Go to the next entry.
                    return null;
                }

                // In case of PayPal PPCP, is_live value is saved as 'yes' or 'no'. We will use this value to determine the environment mode.
                if(isset($is_live) && $is_live == 'yes') {
                    $sub['is_live'] = true;
                } else if (isset($is_live) && $is_live == 'no'){
                    $sub['is_live'] = false;
                } else {
                    // In the older version, the 'is_live' postmeta wasn't set. So as a fallback, use the currently set environment mode.
                    $sub['is_live'] = empty($this->settings->get_value('enable-sandbox-testing'));
                }

                //Get the PayPal PPCP API keys based on the environment mode that this subscription was created in.
                $paypal_ppcp_api_keys = array();
                if ( $sub['is_live'] ) {
                    $paypal_ppcp_api_keys['secret'] =  $this->settings->get_value('paypal-live-secret-key');
                } else {
                    $paypal_ppcp_api_keys['secret'] =  $this->settings->get_value('paypal-sandbox-secret-key');
                }

                //Get the subscription details from PayPal.
                $environment_mode = $sub['is_live'] ? 'production': 'sandbox';
                if (isset($paypal_ppcp_api_keys['secret']) && !empty($paypal_ppcp_api_keys['secret'])) {
                    $pp_api_injector = new SWPM_PayPal_Request_API_Injector();
                    $pp_api_injector->set_mode_and_api_creds_based_on_mode( $environment_mode );
                    $sub_details = $pp_api_injector->get_paypal_subscription_details( $sub_id );
                    if( !empty($sub_details) ){
                        $status = strtolower($sub_details->status);
                    } else {
                        $this->paypal_ppcp_api_key_error = __( 'Error: Subscription details for subscription id: '. $sub_id .' could not be retrieved from PayPal.', 'simple-membership' );
                    }
                }else{
                    $this->paypal_ppcp_api_key_error = __( 'Error: PayPal PPCP API credentials are not configured in the settings menu.', 'simple-membership' );
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

        if ($this->is_active_status($status)) {
            $this->active_subs[$sub_id] = $sub;
        }

        return $sub;
    }

	/**
	 * Load stripe subscriptions only. (Old method that is used by the stripe subscription cancel shortcode).
	 * FYI - In the future, when the stripe only sub cancel shortcode is removed, this method will be removed.
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

			$is_live = get_post_meta($post_id, 'is_live', true);
			$is_live = empty($is_live) ? false : true;
			$sub['is_live'] = $is_live;

			$sub['payment_button_id'] = get_post_meta($post_id, 'payment_button_id', true);

			if ($this->is_active_status($status)) {
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
     * Get the subscription data for a specific subscription.
     *
     * @param $subscr_id string The subscription ID.
     *
     * @return array|null
     */
    public function get_subscription_data($subscr_id)
    {
        if (array_key_exists($subscr_id, $this->subs)){
            return $this->subs[$subscr_id];
        }

        return null;
    }

	/**
	 * Get the active subscriptions count.
	 *
	 * @return int
	 */
	public function get_active_subs_count()
	{
		return count($this->active_subs);
	}

	/**
	 * Get subscriptions count.
	 *
	 * @return int
	 */
	public function get_all_subs_count()
	{
		return count($this->subs);
	}

	/**
	 * Checks if subscription status of active.
	 *
	 * @param string $status Subscription status.
	 * 
	 * @return boolean True if 'active' or 'trialing', false otherwise.
	 */
	public static function is_active_status($status)
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

				if ($this->is_active_status($stripe_sub['status'])) {
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
	 * (Used by the old stripe subscription cancel shortcode). It will be removed in the future.
	 *
	 * @param array $args
	 * @param boolean $sub_id The subscription ID.
	 * 
	 * @return string HTML as string for string sca subscription cancel form.
	 */
	public function get_stripe_subs_cancel_url($args, $sub_id = false)
	{
		if (empty($this->active_subs_count)) {
			return __('No active subscriptions', 'simple-membership');
		}
		if (false === $sub_id) {
			$sub_id = array_key_first($this->subs);
		}
		$sub = $this->subs[$sub_id];

		$token = $sub['cancel_token'];

		$nonce = wp_nonce_field($token, 'swpm_cancel_sub_nonce', false, false);

		$anchor_text = isset($args['anchor_text']) ? $args['anchor_text'] : __('Cancel Subscription', 'simple-membership');

		$out = '<form method="POST">';
		$out .= '%s';
		$out .= '<input type="hidden" name="swpm_cancel_sub_token" value="%s"></input>';
		$out .= '<input type="hidden" name="swpm_cancel_sub_gateway" value="stripe-sca-subs">';
		$out .= '<button type="submit" name="swpm_do_cancel_stripe_sub" value="1" onclick="return confirm(\'' . esc_js(__('Are you sure that you want to cancel the subscription?', 'simple-membership')) . '\');">' . esc_attr($anchor_text) . '</button>';
		$out .= '</form>';

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
		return null;
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
			return;
		}

		$res = $this->dispatch_subscription_cancel_request($sub['sub_id'], $gateway);

		if ( $res !== true ) {
			self::cancel_msg( $res );
		}

		$ipn_data = array();
		$ipn_data['member_id'] = $this->member_id;

		do_action( 'swpm_subscription_payment_cancelled', $ipn_data ); // Hook for subscription payment cancelled.

        $redirect_url = isset($_POST['swpm_cancel_sub_return_url']) && !empty($_POST['swpm_cancel_sub_return_url']) ? sanitize_url($_POST['swpm_cancel_sub_return_url']) : '';

		self::cancel_msg( __( 'Subscription has been cancelled.', 'simple-membership' ), false, $redirect_url);
	}

    /**
     * Send the subscription cancel request to the corresponding gateway api.
     *
     * @param $subscr_id string The subscription ID of the subscription to cancel.
     * @param $gateway string The payment gateway.
     *
     * @return bool|string True on success, Error message string on failure.
     */
    public function dispatch_subscription_cancel_request($subscr_id, $gateway)
    {
        if (empty($subscr_id)){
            return '';
        }

        switch($gateway){
            case 'stripe-sca-subs':
                $res = $this->cancel_subscription_stripe_sca( $subscr_id );
                break;
            case 'paypal_subscription_checkout':
                $res = $this->cancel_subscription_paypal( $subscr_id );
                break;
            default:
                $res = false;
                break;
        }

        return $res;
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

			if ($this->is_active_status($stripe_sub['status'])) {
				$stripe_sub->cancel();
			}

            update_post_meta($sub['post_id'], 'subscr_status', $stripe_sub['status']);
			SwpmLog::log_simple_debug("Stripe SCA subscription canceled successfully.", true);
		} catch (\Exception $e) {
			SwpmLog::log_simple_debug("Stripe SCA subscription cancellation failed.", false);
			SwpmLog::log_simple_debug($e->getMessage(), false);
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
				SwpmLog::log_simple_debug("PayPal PPCP subscription canceled successfully.", true);
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
	 * @param string $redirect_url After subscription cancel redirect url.
	 * @param boolean $is_error Whether it is an error message or not.
	 *
	 * @return void
	 */
	public static function cancel_msg( $msg, $is_error = true, $redirect_url = '' ) {
        echo $msg;
		echo '<br><br>';
		echo __( 'You will be redirected to the previous page in a few seconds. If not, please <a href="">click here</a>.', 'simple-membership' );
		echo '<script>function toPrevPage(){window.location = '.(!empty($redirect_url) ? '"'.esc_url($redirect_url).'"' : 'window.location.href' ).';}setTimeout(toPrevPage,5000);</script>';
		if ( ! $is_error ) {
			wp_die( '', __( 'Success!', 'simple-membership' ), array( 'response' => 200 ) );
		}
		wp_die();
	}

	/**
	 * The HTML form for subscription cancellation of all gateways.
	 * Used by the 'swpm_show_subscriptions_and_cancel_link' shortcode.
	 * 
	 * @param array $subscription Subscription Details.
	 * @param string $redirect_to_url Optional. Redirect url after subscription cancel.
	 *
	 * @return string HTML of cancel form as string.
	 */
	public static function get_cancel_subscription_output( $subscription, $redirect_to_url = ''){
		if (self::is_active_status($subscription['status'])) {
			// Subscription is active.
			$token = $subscription['cancel_token'];
			$cancel_form_output = '';
			ob_start();
			?>
			<form method="post" class="swpm-cancel-subscription-form">
				<?php echo wp_nonce_field( $token, 'swpm_cancel_sub_nonce', false, false );?>
				<input type="hidden" name="swpm_cancel_sub_token" value="<?php echo esc_attr($token) ?>">
				<input type="hidden" name="swpm_cancel_sub_gateway" value="<?php echo esc_attr($subscription['gateway']) ?>">
				<button type="submit" class="swpm-cancel-subscription-button swpm-cancel-subscription-button-active" name="swpm_do_cancel_sub" onclick="return confirm(' <?php _e( 'Are you sure that you want to cancel the subscription?', 'simple-membership' )?> ')">
					<?php _e('Cancel Subscription', 'simple-membership') ?>
				</button>

                <?php if ( !empty($redirect_to_url) ){ ?>
                    <input type="hidden" name="swpm_cancel_sub_return_url" value="<?php echo esc_url_raw($redirect_to_url) ?>">
                <?php } ?>
			</form>
			<?php
			$cancel_form_output = ob_get_clean();
			return $cancel_form_output;
		}

		// Subscription is inactive.
		$inactive_output = '';
		ob_start();
		?>
		<div class="swpm_subscription_inactive">
			<?php _e('Subscription Inactive', 'simple-membership'); ?>
		</div>
		<?php
		$inactive_output = ob_get_clean();
		return $inactive_output;
	}

	public function get_any_stripe_sca_api_key_error(){
		return $this->stripe_sca_api_key_error;
	}

	public function get_any_paypal_ppcp_api_key_error(){
		return $this->paypal_ppcp_api_key_error;
	}

    /**
     * Retrieves the cpt id of a subscription agreement transaction record by a subscription ID.
     *
     * It utilizes the 'status' post meta as a filter. It checks whether the 'status' is set to 'subscription created' or not.
     *
     * @param $subscr_id string Subscription ID.
     *
     * @return int|null
     */
    public static function get_subscription_agreement_cpt_id_by_subs_id($subscr_id)
    {
        if (empty($subscr_id)) {
            return null;
        }

        $subscription_records = get_posts(array(
            'post_type' => 'swpm_transactions',
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'subscr_id',
                    'value' => $subscr_id,
                    'compare' => '=',
                ),
            ),
        ));

        $sub_agreement_record_id = null;
        foreach ($subscription_records as $key => $record_id) {
            $status = get_post_meta($record_id, 'status', true);

            if (strtolower($status) == 'subscription created') {
                $sub_agreement_record_id = $record_id;
                break;
            }
        }

        return $sub_agreement_record_id;
    }

    /**
     * Updates a meta value of a subscription agreement cpt record.
     *
     * First it retrieves the cpt id of a subscription agreement record using a subscription ID,
     * then it updated the specified meta field.
     *
     * @param $subscr_id string Subscription ID
     * @param $meta_key string Key of the meta field to update.
     * @param $meta_value string Value of the meta field to update.
     *
     * @return void
     */
    public static function update_subscription_agreement_record_meta_by_sub_id($subscr_id, $meta_key, $meta_value)
    {
        if (empty($subscr_id) || empty($meta_key)) {
            // Invalid parameters.
            return;
        }

        // Retrieves the cpt id of a subscription agreement transaction record.
        $cpt_id = self::get_subscription_agreement_cpt_id_by_subs_id($subscr_id);

        // Check if record exits.
        if (empty($cpt_id)) {
            SwpmLog::log_simple_debug("Subscription agreement record not found for subscription ID: " . $subscr_id . ". Nothing to update.", false);
            return;
        }

        // Update the 'subscr_status' post meta.
        SwpmLog::log_simple_debug("Updating subscription agreement record. Post meta [" . $meta_key . "] updated to [" . $meta_value . "]. Subscription ID: " . $subscr_id, true);
        update_post_meta($cpt_id, $meta_key, $meta_value);
    }

    /**
     * Updates the subscription agreement record to 'canceled' by updating the 'subscr_status' post meta.
     */
    public static function update_subscription_agreement_record_status_to_cancelled($subscr_id){
		//Note: it is important not to update the 'status' post meta as that one is used in the cancel shortcode at the moment.
		//In the future when we update that cancel shortcode to use the 'txn_type' post meta, then we can change/update the 'status' column.
        self::update_subscription_agreement_record_meta_by_sub_id($subscr_id, 'subscr_status', 'canceled');
    }

	/**
	 * Retrieve the last active sub data of a member.
	 *
	 */
	public function get_last_active_sub_if_any(){
		/*
		 * NOTE: This method stores the last sub info in a static property to make the data persistent,
		 * So when this method gets called next time, we can skip the sub data finding process reducing time complexity significantly.
		 */

		if ( isset(static::$last_active_sub) ) {
			return static::$last_active_sub;
		}

		//Get any swpm_transactions CPT posts that are associated with the given member ID OR the given subscr_id.
		$subscriptions = get_posts(array(
			'post_type'  => 'swpm_transactions',
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',/* We are looking for subscriptions that are associated with the given member ID OR the given subscr_id */
					array(
						'key'     => 'member_id',
						'value'   => $this->member_id,
						'compare' => '=',
					),
					array(
						'key'     => 'subscr_id',
						'value'   => $this->subscr_id_attached_to_profile,
						'compare' => '=',
					),
				),
				array(
					'relation' => 'OR',/* We are looking for subscriptions that are created using Stripe SCA or PayPal PPCP */
					array(
						'key'     => 'gateway',
						'value'   => 'stripe-sca-subs',
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

		/*
		 * Loop through the found subscriptions and get the last active subscription data.
		 */

		foreach ($subscriptions as $subscription) {
			if ( !is_numeric($subscription->ID) ) {
				continue;
			}

            // Check if subscription status post meta was explicitly set to 'canceled'.
            $saved_subscription_status = get_post_meta($subscription->ID, 'subscr_status', true);
            if ( in_array($saved_subscription_status, array('canceled', 'cancelled') ) ){
                continue;
            }

			$sub = $this->create_subscription_data_array($subscription);

			if (is_null($sub)){
				continue;
			}

			$status = isset($sub['status']) ? $sub['status'] : '';

			if ($this->is_active_status($status)) {
                // An active subscription record found.
				static::$last_active_sub = $sub;
				break;
			}
		}

		return static::$last_active_sub;
	}
}
