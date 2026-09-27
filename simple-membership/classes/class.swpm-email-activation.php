<?php

/** Shared activation token handling for registration and both resend paths. */
class SwpmEmailActivation {

	public static function is_valid( $data ) {
		return is_array( $data ) && ! empty( $data['act_code'] ) && is_string( $data['act_code'] )
			&& isset( $data['timestamp'] ) && is_numeric( $data['timestamp'] )
			&& $data['timestamp'] <= time() && $data['timestamp'] > time() - DAY_IN_SECONDS;
	}

	public static function get_or_create( $member_id ) {
		global $wpdb;
		$key = 'swpm_email_activation_data_usr_' . $member_id;
		$data = get_option( $key, false );
		$original = $data;
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		// A resend must not invalidate a usable link or extend its lifetime.
		if ( ! self::is_valid( $data ) ) {
			$code = wp_generate_password( 32, false, false );
			$timestamp = time();
			unset( $data['plain_password'], $data['password'] );
			$data['act_code'] = $code;
			$data['timestamp'] = $timestamp;
			$data = (array) apply_filters( 'swpm_email_activation_data', $data );
			// Preserve addon metadata (including fb_form_id), but enforce token security.
			$data['act_code'] = $code;
			$data['timestamp'] = $timestamp;
		}
		unset( $data['plain_password'], $data['password'] );
		if ( $data !== $original ) {
			if ( $original === false ) {
				// Unlike add_option(), this cannot overwrite a concurrent insert.
				$saved = $wpdb->query( $wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
					$key, maybe_serialize( $data )
				) );
			} else {
				// Do not overwrite a newer token or resurrect a consumed record.
				$saved = $wpdb->update( $wpdb->options,
					array( 'option_value' => maybe_serialize( $data ) ),
					array( 'option_name' => $key, 'option_value' => maybe_serialize( $original ) ),
					array( '%s' ), array( '%s', '%s' )
				);
			}
			self::clear_option_cache( $key );
			if ( $saved !== 1 ) {
				return false;
			}
		}
		return $data;
	}

	/** Delete only the record we validated, so simultaneous requests cannot reuse it. */
	public static function consume( $member_id, $data ) {
		global $wpdb;
		$key = 'swpm_email_activation_data_usr_' . $member_id;
		$deleted = $wpdb->delete( $wpdb->options, array(
			'option_name' => $key,
			'option_value' => maybe_serialize( $data ),
		), array( '%s', '%s' ) );
		self::clear_option_cache( $key );
		return $deleted === 1;
	}

	private static function clear_option_cache( $key ) {
		wp_cache_delete( $key, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/** Public resends: one per member per minute, ten per source per ten minutes. */
	public static function allow_public_resend( $member_id ) {
		// Do not trust client-supplied forwarding headers for this limit.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		// Sites behind trusted proxies can supply their verified client address here.
		$ip = (string) apply_filters( 'swpm_activation_resend_source', $ip );
		$source_limit = max( 1, (int) apply_filters( 'swpm_activation_resend_source_limit', 10 ) );
		$member_interval = max( 1, (int) apply_filters( 'swpm_activation_resend_member_interval', MINUTE_IN_SECONDS ) );
		$source_key = 'swpm_activation_source_' . hash( 'sha256', $ip );
		$source = get_transient( $source_key );
		$now = time();
		if ( ! is_array( $source ) || $source['expires'] <= $now ) {
			$source = array( 'count' => 0, 'expires' => $now + 10 * MINUTE_IN_SECONDS );
		}
		if ( $source['count'] >= $source_limit ) {
			return false;
		}
		$source['count']++;
		set_transient( $source_key, $source, $source['expires'] - $now );
		$member_key = 'swpm_activation_resend_' . $member_id;
		if ( get_transient( $member_key ) ) {
			return false;
		}
		set_transient( $member_key, 1, $member_interval );
		return true;
	}

	public static function password_email_notice() {
		return SwpmUtils::_( 'For security, passwords are not sent by email. Use the password reset option on the login page if needed.' );
	}

	/** Remove legacy recoverable passwords in bounded batches without expiring links. */
	public static function remove_legacy_passwords() {
		global $wpdb;
		$progress_key = 'swpm_activation_password_cleanup';
		$progress = get_option( $progress_key, 0 );
		if ( $progress === 'done' ) {
			return;
		}
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_id > %d ORDER BY option_id LIMIT 100",
			$wpdb->esc_like( 'swpm_email_activation_data_usr_' ) . '%', (int) $progress
		) );
		if ( ! is_array( $rows ) || ! empty( $wpdb->last_error ) ) {
			return;
		}
		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->option_value );
			if ( is_array( $data ) && ( array_key_exists( 'plain_password', $data ) || array_key_exists( 'password', $data ) ) ) {
				unset( $data['plain_password'], $data['password'] );
				// Do not resurrect a consumed token or overwrite a concurrent resend.
				$result = $wpdb->update( $wpdb->options,
					array( 'option_value' => maybe_serialize( $data ) ),
					array( 'option_id' => $row->option_id, 'option_value' => $row->option_value ),
					array( '%s' ), array( '%d', '%s' )
				);
				self::clear_option_cache( $row->option_name );
				if ( $result === false ) {
					return;
				}
			}
			$progress = $row->option_id;
		}
		update_option( $progress_key, count( $rows ) < 100 ? 'done' : $progress, false );
	}
}
