<?php
/**
 * Endpoint tests only (installed as a must-use plugin on the GitHub Actions test site, never on a real site).
 * Fakes PayPal's IPN verification request, so the PayPal Standard IPN handler can run without PayPal.
 * The answer is VERIFIED, or the value of the 'swpm_e2e_ipn_answer' option (example: INVALID, to test a rejected IPN).
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
