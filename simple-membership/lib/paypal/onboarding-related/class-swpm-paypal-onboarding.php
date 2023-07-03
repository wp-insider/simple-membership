<?php

/**
 * A Helper class for PPCP Onboarding.
 */
class SWPM_PayPal_PPCP_Onboarding {

	public static $live_partner_id = '3FWGC6LFTMTUG';//Same as the merchant id of the live account.
	public static $sandbox_partner_id = 'USVAEAM3FR5E2';//Same as the merchant id of the sandbox account.

	public static $live_partner_client_id = 'TODO';
	public static $sandbox_partner_client_id = 'AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX';

	public function __construct() {
		//add_action( 'wp_loaded', array(&$this, 'handle_paypal_stuff' ) );
	}

	public static function get_sandbox_signup_link(){
		$seller_nonce = wp_create_nonce('swpm_ppcp_sandbox_signup');

		$query_args = array();
		$query_args['partnerId'] = self::$sandbox_partner_id;
		$query_args['product'] = 'ppcp';
		$query_args['integrationType'] = 'FO';
		$query_args['features'] = 'PAYMENT,REFUND';
		$query_args['partnerClientId'] = self::$sandbox_partner_client_id;
		$query_args['returnToPartnerUrl'] = '';
		$query_args['partnerLogoUrl'] = '';
		$query_args['displayMode'] = 'minibrowser';
		$query_args['sellerNonce'] = $seller_nonce;

		$base_url = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
		$sandbox_singup_link = add_query_arg( $query_args, $base_url );
		//Example URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry?partnerId=USVAEAM3FR5E2&product=ppcp&integrationType=FO&features=PAYMENT,REFUND&partnerClientId=AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX&returnToPartnerUrl=&partnerLogoUrl=&displayMode=minibrowser&sellerNonce=a575ab0ee0';
		return $sandbox_singup_link;
	}

	public static function output_sandbox_onboarding_link_code() {
		$sandbox_singup_link = self::get_sandbox_signup_link();

		?>
		<script>
			function swpm_ppcp_testmode_onboardedCallback(authCode, sharedId) {
				console.log('SWPM PayPal Testmode onboardedCallback');
				console.log(authCode);
				console.log(sharedId);

				//TODO - Send the authCode and sharedId to your server and do the next steps.

			}
		</script>
		<a class="button button-primary direct" target="_blank"
			data-paypal-onboard-complete="swpm_ppcp_testmode_onboardedCallback"
			href="<?php echo esc_url_raw($sandbox_singup_link); ?>"
			data-paypal-button="true">Activate PayPal Sandbox</a>
		<script id="paypal-js" src="https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>
		<?php

	}
}