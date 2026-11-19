<?php
/**
 * The WP user profile sync (SimpleWpMembership::sync_with_wp_profile(), on the profile_update hook).
 * It copies the values WordPress saved to the member record. It must not copy the raw (slashed) input,
 * and it must not run while the plugin itself updates the WP user (example: a password reset).
 */
class Test_Profile_Sync extends WP_UnitTestCase {

	private $user_id;
	private $member_id;

	public function set_up() {
		parent::set_up();
		$login           = SWPM_Test_Factory::unique( 'syncuser' );
		$this->user_id   = self::factory()->user->create( array( 'user_login' => $login, 'user_email' => $login . '@example.com', 'role' => 'subscriber' ) );
		$this->member_id = SWPM_Test_Factory::create_member( array( 'user_name' => $login, 'email' => $login . '@example.com', 'password' => 'member-password-hash' ) );
	}

	public function test_names_with_apostrophes_are_saved_without_backslashes() {
		//WordPress's own profile forms pass slashed values (O\'Connor), and WordPress unslashes them when it saves them.
		wp_update_user( array( 'ID' => $this->user_id, 'first_name' => "O\\'Connor", 'last_name' => "D\\'Angelo" ) );

		$member = SWPM_Test_Factory::get_member( $this->member_id );
		$this->assertSame( "O'Connor", $member->first_name );
		$this->assertSame( "D'Angelo", $member->last_name );
	}

	public function test_email_and_password_changes_are_synced() {
		global $wpdb;
		wp_update_user( array( 'ID' => $this->user_id, 'user_email' => 'changed-email@example.com', 'user_pass' => 'NewPassword!123' ) );

		$member         = SWPM_Test_Factory::get_member( $this->member_id );
		$saved_wp_hash  = $wpdb->get_var( $wpdb->prepare( "SELECT user_pass FROM {$wpdb->users} WHERE ID = %d", $this->user_id ) );
		$this->assertSame( 'changed-email@example.com', $member->email );
		$this->assertSame( $saved_wp_hash, $member->password, 'The member record must get the password hash WordPress saved.' );
	}

	public function test_the_sync_does_not_run_while_the_plugin_updates_the_wp_user() {
		$member = SWPM_Test_Factory::get_member( $this->member_id );

		//Example: a password reset. The plugin saves the member record itself, then updates the WP user.
		SwpmUtils::update_wp_user( $member->user_name, array( 'plain_password' => 'ResetPassword!123' ) );

		$this->assertSame( 'member-password-hash', SWPM_Test_Factory::get_member( $this->member_id )->password, 'The sync must not overwrite the member record during a plugin update.' );
		$this->assertFalse( SwpmUtils::$is_updating_wp_user, 'The "plugin is updating the WP user" flag must be reset after the update.' );
	}

	public function test_other_member_fields_are_not_changed_by_the_sync() {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'swpm_members_tbl', array( 'account_state' => 'expired', 'notes' => 'Keep this note.' ), array( 'member_id' => $this->member_id ) );

		wp_update_user( array( 'ID' => $this->user_id, 'first_name' => 'Changed' ) );

		$member = SWPM_Test_Factory::get_member( $this->member_id );
		$this->assertSame( 'Changed', $member->first_name );
		$this->assertSame( 'expired', $member->account_state );
		$this->assertSame( 'Keep this note.', $member->notes );
	}
}
