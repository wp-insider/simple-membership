<?php

/**
 * This class handles all active login limits feture related operations.
 */

class SwpmLimitActiveLogin {

	public function __construct() {}

	/**
	 * Check if active login limit enabled or not.
	 */
	public static function is_enabled() {
		return ! empty( SwpmSettings::get_instance()->get_value( 'enable-login-limiter' ) );
	}

	/**
	 * Get the max allowed active logins.
	 */
	public static function allowed_max_active_logins() {
		return intval( SwpmSettings::get_instance()->get_value( 'maximum-active-logins', 3 ) );
	}

	/**
	 * Get the logic settings for restricting login limits.
	 *
	 * TODO: At the moment we only offer the 'allow' option for this feature.
	 *
	 * Options:
	 * 'allow': Allow new login by terminating all other old sessions when the limit is reached.
	 * 'block': Do not allow new login if the limit is reached. Users need to wait for the old login sessions to expire.
	 */
	public static function login_limit_logic() {
		//Note: At the moment we only offer the 'allow' option for this feature.
		// return SwpmSettings::get_instance()->get_value( 'login-logic', 'allow' );
		return 'allow';
	}

	/**
	 * Prepare a session token array data.
	 */
	public static function create_new_session_token_array( $remember_me ) {
		if ( $remember_me ) {
			$expiration = time() + 14 * DAY_IN_SECONDS;
		} else {
			$expiration = time() + 2 * DAY_IN_SECONDS;
		}

		return array(
			'expiration' => $expiration,
			'ip'         => SwpmUtils::get_user_ip_address(),
			'ua'         => $_SERVER['HTTP_USER_AGENT'],
			'login'      => time(),
		);
	}

	/**
	 * Clear expired session token data and append new one for a member.
	 */
	public static function refresh_member_session_tokens( $member_id, $token_key, $new_session_token ) {
		// Get valid session tokens.
		$session_tokens = self::get_all_valid_session_tokens_of_member( $member_id );

		$session_tokens[ hash( 'sha256', $token_key ) ] = $new_session_token;

		SwpmMembersMeta::update( $member_id, 'session_tokens', $session_tokens );
	}

	/**
	 * Check if a member has a session token with specific token_key.
	 */
	public static function is_member_session_token_valid( $member_id, $token_key ) {
		$valid_tokens = self::get_all_valid_session_tokens_of_member( $member_id );

		return array_key_exists( hash( 'sha256', $token_key ), $valid_tokens );
	}

	/**
	 * Get only the valid session tokens.
	 */
	public static function get_all_valid_session_tokens_of_member( $member_id ) {
		$session_tokens = SwpmMembersMeta::get( $member_id, 'session_tokens', true );
		if ( ! is_array( $session_tokens ) ) {
			return array();
		}

		return array_filter( $session_tokens, 'SwpmLimitActiveLogin::is_token_still_valid' );
	}

	/**
	 * Re-initialize session_tokens meta with new fresh session token.
	 */
	public static function reinitialize_session_tokens_metadata($member_id, $auth_cookie) {
		$token_key = hash( 'sha256', $auth_cookie );
		$new_session_tokens = array();
		$new_session_tokens[$token_key] = SwpmLimitActiveLogin::create_new_session_token_array(!empty($remember));

		SwpmMembersMeta::update( $member_id, 'session_tokens', $new_session_tokens );
	}

	/**
	 * Check if the 'expiration' field exceeds current time.
	 */
	public static function is_token_still_valid( $session_token ) {
		if ( ! is_array( $session_token ) ) {
			return false;
		}

		return $session_token['expiration'] >= time();
	}

	public static function clear_logged_in_member_session_token() {
		$logged_in_member_id = SwpmMemberUtils::get_logged_in_members_id();
		if (self::is_enabled()){
			//We only clear the specific session token of this user's current session. We don't clear all session tokens since the user may have multiple devices logged in.
			if (isset($_COOKIE[SIMPLE_WP_MEMBERSHIP_SEC_AUTH])){
				$token_key =  $_COOKIE[SIMPLE_WP_MEMBERSHIP_SEC_AUTH];
			} else if (isset($_COOKIE[SIMPLE_WP_MEMBERSHIP_AUTH])) {
				$token_key =  $_COOKIE[SIMPLE_WP_MEMBERSHIP_AUTH];
			} else {
				$token_key = '';
			}

			if (!empty($token_key)){
				SwpmLimitActiveLogin::clear_specific_session_token($logged_in_member_id, $token_key);
			}
		}
	}
	/**
	 * Clear session token of a member.
	 * If a session_token token_key provided, only delete that, else clear all.
	 */
	public static function clear_specific_session_token( $member_id, $token_key ) {
		if ( empty( $member_id ) || empty( $token_key ) ) {
			return;
		}

		// Check if 'session_token' meta is empty.
		$session_tokens = SwpmMembersMeta::get( $member_id, 'session_tokens', true );
		if ( empty( $session_tokens ) || ! is_array( $session_tokens ) ) {
			return;
		}

		$token_key = hash( 'sha256', $token_key ); // The session_token key was saved as sha256 hash.

		// Check and remove target session token.
		if ( array_key_exists( $token_key, $session_tokens ) ) {
			unset( $session_tokens[ $token_key ] );
		}

		// Update member's session tokens.
		SwpmMembersMeta::update( $member_id, 'session_tokens', $session_tokens );
	}

	/**
	 * Deletes all session tokens of a member.
	 */
	public static function delete_session_tokens( $member_id ) {
		if ( empty( $member_id ) ) {
			return;
		}
		// Clear all session tokens.
		SwpmMembersMeta::delete( $member_id, 'session_tokens' );
	}

	/**
	 * Deletes all expired session tokens of a member from the members meta table.
	 */
	public static function delete_expired_session_tokens( $member_id ) {
		// Get valid session tokens.
		$session_tokens = self::get_all_valid_session_tokens_of_member( $member_id );
		// Update member's session tokens.
		SwpmMembersMeta::update( $member_id, 'session_tokens', $session_tokens );
	}

	/**
	 * Check if login limit has reached for a member.
	 */
	public static function reached_active_login_limit( $member_id ) {
		//Filter hook to override the login limit check using custom code.
		$limit_overridden = apply_filters( 'swpm_override_login_limit', false, $member_id );
		if ( $limit_overridden ) {
			// Login limit is overridden, so we do not restrict the login.
			return false;
		}

		// Get all valid session tokens for the member.
		// If the member has no session tokens, then the limit is not reached.
		$valid_tokens = self::get_all_valid_session_tokens_of_member( $member_id );
		$valid_tokens_count = count( $valid_tokens );
		if ( $valid_tokens_count >= self::allowed_max_active_logins() ) {
			// login limit reached. Return true which means the limit is reached.
			return true;
		}

		// login limit not reached. Return false, which means the user can log in.
		return false;
	}
}