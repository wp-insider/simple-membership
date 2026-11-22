<?php
/**
 * Endpoint tests only (installed as a must-use plugin on the GitHub Actions test site, never on a real site).
 * Fakes PayPal's IPN verification request, so the PayPal Standard IPN handler can run without PayPal.
 * The answer is VERIFIED, or the value of the 'swpm_e2e_ipn_answer' option (example: INVALID, to test a rejected IPN).
 * It also saves the member ID of the last refund (the swpm_payment_refunded action) in the 'swpm_e2e_last_refund_member_id' option.
 */
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		if ( false === strpos( $url, 'paypal.com/cgi-bin/webscr' ) ) {
			return $pre;
		}
		return array(
			'headers'  => array(),
			'body'     => get_option( 'swpm_e2e_ipn_answer', 'VERIFIED' ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	10,
	3
);

//Saves the member ID of the last swpm_payment_refunded action, so the tests can check what the refund passes to other plugins.
add_action(
	'swpm_payment_refunded',
	function ( $refund_data ) {
		update_option( 'swpm_e2e_last_refund_member_id', isset( $refund_data['member_id'] ) ? (string) $refund_data['member_id'] : '' );
	}
);
