<?php
/**
 * The key that signs the membership level of a level-specific registration form (the swpm_level_hash form field).
 * Security report (4.8.9): the key was made with uniqid() (based on the time, so it can be found by brute force),
 * and the form showed md5( key . '|' . level ), so a visitor could sign another level (example: one with the Editor role).
 */
class Test_Registration_Level_Key extends WP_UnitTestCase {

	/** A strong key: 64 hex characters (32 random bytes). A uniqid() key is 23 characters with a dot. */
	private function assert_strong_key( $key, $message ) {
		$this->assertIsString( $key, $message );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $key, $message );
	}

	/** The swpm_level_hash value of the registration form in this output. */
	private function get_form_level_hash( $output ) {
		preg_match_all( '/<input\b[^>]*>/i', $output, $inputs );
		foreach ( $inputs[0] as $input ) {
			if ( false !== strpos( $input, 'name="swpm_level_hash"' ) && preg_match( '/\bvalue="([^"]*)"/', $input, $value ) ) {
				return $value[1];
			}
		}
		return '';
	}

	public function test_activation_creates_a_strong_key() {
		delete_option( 'swpm_private_key_one' );
		SimpleWpMembership::activate();
		$this->assert_strong_key( get_option( 'swpm_private_key_one' ), 'The key made at activation is not a strong random key.' );
	}

	public function test_an_old_uniqid_key_is_replaced_when_a_registration_form_is_shown() {
		$old_key = uniqid( '', true );
		update_option( 'swpm_private_key_one', $old_key );
		$level_id = SWPM_Test_Factory::create_level();

		$output = do_shortcode( '[swpm_registration_form level=' . $level_id . ']' );

		$new_key = get_option( 'swpm_private_key_one' );
		$this->assertNotSame( $old_key, $new_key, 'The old uniqid() key was not replaced.' );
		$this->assert_strong_key( $new_key, 'The replacement key is not a strong random key.' );
		$this->assertNotSame( '', $this->get_form_level_hash( $output ), 'The registration form has no level hash.' );
		$this->assertNotSame( md5( $old_key . '|' . $level_id ), $this->get_form_level_hash( $output ), 'The form still uses the old key.' );
	}

	public function test_the_form_level_hash_is_not_an_md5_of_the_key_and_the_level() {
		$level_id = SWPM_Test_Factory::create_level();
		$output   = do_shortcode( '[swpm_registration_form level=' . $level_id . ']' );
		$hash     = $this->get_form_level_hash( $output );
		$this->assertNotSame( '', $hash, 'The registration form has no level hash.' );
		$this->assertNotSame( md5( get_option( 'swpm_private_key_one' ) . '|' . $level_id ), $hash, 'The level hash is still an MD5 of the key and the level (it can be used to find the key by brute force).' );
	}

	public function test_a_strong_key_is_kept() {
		$key = str_repeat( 'ab', 32 );
		update_option( 'swpm_private_key_one', $key );
		$level_id = SWPM_Test_Factory::create_level();
		do_shortcode( '[swpm_registration_form level=' . $level_id . ']' );
		$this->assertSame( $key, get_option( 'swpm_private_key_one' ), 'A strong key was replaced (it would break the forms already shown to visitors).' );
	}
}
