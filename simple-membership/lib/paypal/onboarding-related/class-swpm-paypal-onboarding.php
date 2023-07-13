<?php

/**
 * A Helper class for PPCP Onboarding.
 */
class SWPM_PayPal_PPCP_Onboarding {
	protected static $instance;
	public static $live_partner_id = '3FWGC6LFTMTUG';//Same as the partner's merchant id of the live account.
	public static $sandbox_partner_id = 'USVAEAM3FR5E2';//Same as the partner's merchant id of the sandbox account.

	public static $live_partner_client_id = 'TODO';
	public static $sandbox_partner_client_id = 'AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX';

	public static $account_connect_string = 'swpm_ppcp_account_connect';

	public function __construct() {
		//NOP
	}

	/*
	 * This needs to be a Singleton class. To make sure that the object and data is consistent throughout the onboarding process.
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function generate_seller_nonce() {
		// Generate a random string of 40 characters.
		$random_string = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 5)), 0, 40);

		// Hash the string using sha256
		$hashed_string = hash('sha256', $random_string);

		// Trim or pad the hashed string to ensure it is between 40 to 64 characters
		$output_string = substr($hashed_string, 0, 64);
		$output_string = str_pad($output_string, 64, '0');

		$seller_nonce = $output_string;
		return $seller_nonce;
	}

	public static function get_sandbox_signup_link(){

		$seller_nonce = self::generate_seller_nonce();

		$query_args = array();
		$query_args['partnerId'] = self::$sandbox_partner_id;
		$query_args['product'] = 'PPCP';
		$query_args['integrationType'] = 'FO';
		$query_args['features'] = 'PAYMENT,REFUND';
		$query_args['partnerClientId'] = self::$sandbox_partner_client_id;
		//$query_args['returnToPartnerUrl'] = '';
		//$query_args['partnerLogoUrl'] = '';
		$query_args['displayMode'] = 'minibrowser';
		$query_args['sellerNonce'] = $seller_nonce;

		$base_url = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
		$sandbox_singup_link = add_query_arg( $query_args, $base_url );
		//Example URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry?partnerId=USVAEAM3FR5E2&product=ppcp&integrationType=FO&features=PAYMENT,REFUND&partnerClientId=AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX&returnToPartnerUrl=&partnerLogoUrl=&displayMode=minibrowser&sellerNonce=a575ab0ee0';
		
		update_option('swpm_ppcp_sandbox_connect_query_args', $query_args);

		return $sandbox_singup_link;
	}

	public function output_sandbox_onboarding_link_code() {
		$sandbox_singup_link = self::get_sandbox_signup_link();
		$wp_nonce = wp_create_nonce( self::$account_connect_string );
		$ajax_post_url = admin_url('admin-ajax.php');
		?>
		<script>
			function swpm_ppcp_sandbox_onboardedCallback(authCode, sharedId) {
				console.log('SWPM PayPal Sandbox onboardedCallback');
				console.log(authCode);
				console.log(sharedId);
				console.log('<?php echo $wp_nonce; ?>');
				console.log('<?php echo $ajax_post_url; ?>')

				data = JSON.stringify({
						authCode: authCode,
						sharedId: sharedId,
						environment: 'sandbox',
				});

				const formData = new FormData();
				formData.append('action', 'swpm_handle_onboarded_callback_data');
				formData.append('data', data);
				formData.append('_wpnonce', '<?php echo $wp_nonce; ?>');

				//Post the AJAX request to the server.
				fetch('<?php echo $ajax_post_url; ?>', {
					method: 'POST',
					body: formData,
				}).then(response => response.json())
				.then(result => {
					//The AJAX post request was successful. Need to check if the processing was successful.
					//The response.json() method is used to parse the response as JSON. Then, the result object contains the parsed JSON response.
					if(result.success){
						//All good.
						console.log('Successfully processed the handle_onboarded_callback_data.');
					} else {
						alert("Error: " + result.msg);
					}
				}).catch(function(err) {
					console.error(err);
					alert("Something went wrong with the AJAX request on this server! See the console log for more details.");
				})

				return false;
				//TODO - Send the authCode and sharedId to your server and do the next steps.
				//The get_option('swpm_ppcp_sandbox_connect_query_args') will give you the query args that you sent to the PayPal onboarding page
				//You can use the sellerNonce to identify the user.
			}
		</script>
		<a class="button button-primary direct" target="_blank"
			data-paypal-onboard-complete="swpm_ppcp_sandbox_onboardedCallback"
			href="<?php echo esc_url_raw($sandbox_singup_link); ?>"
			data-paypal-button="true">Activate PayPal Sandbox</a>
		<script id="paypal-js" src="https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>

		<?php
		//TODO remove the ajax test link.

	}
}