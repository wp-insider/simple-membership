<?php
/**
 * The "WordPress Role After Expiry" option of a membership level: the member's WP role changes to the level's expiry role when
 * the member expires, and the level's default role is restored after a payment or an admin reactivation.
 */
class Test_Role_After_Expiry extends WP_UnitTestCase {

	private function member_with_wp_user( $level_id, $role ) {
		$login     = SWPM_Test_Factory::unique( 'roleuser' );
		$user_id   = self::factory()->user->create( array( 'user_login' => $login, 'user_email' => $login . '@example.com', 'role' => $role ) );
		$member_id = SWPM_Test_Factory::create_member( array( 'user_name' => $login, 'email' => $login . '@example.com', 'membership_level' => $level_id ) );
		return array( $member_id, $user_id );
	}

	private function roles_of( $user_id ) {
		return array_values( ( new WP_User( $user_id ) )->roles );
	}

	public function test_expiry_role_is_applied_and_the_level_role_is_restored() {
		$level_id = SWPM_Test_Factory::create_level( array( 'role' => 'contributor' ) );
		SWPM_Test_Factory::set_level_field( $level_id, 'role_after_expiry', 'subscriber' );
		list( $member_id, $user_id ) = $this->member_with_wp_user( $level_id, 'contributor' );

		SwpmMemberUtils::apply_expiry_role( $member_id );
		$this->assertSame( array( 'subscriber' ), $this->roles_of( $user_id ) );

		SwpmMemberUtils::restore_level_role( $member_id );
		$this->assertSame( array( 'contributor' ), $this->roles_of( $user_id ) );
	}

	public function test_nothing_changes_for_a_level_without_the_option() {
		$level_id = SWPM_Test_Factory::create_level( array( 'role' => 'contributor' ) );
		list( $member_id, $user_id ) = $this->member_with_wp_user( $level_id, 'author' );

		SwpmMemberUtils::apply_expiry_role( $member_id );
		$this->assertSame( array( 'author' ), $this->roles_of( $user_id ) );

		SwpmMemberUtils::restore_level_role( $member_id );
		$this->assertSame( array( 'author' ), $this->roles_of( $user_id ) );
	}

	public function test_an_administrator_is_never_changed() {
		$level_id = SWPM_Test_Factory::create_level( array( 'role' => 'contributor' ) );
		SWPM_Test_Factory::set_level_field( $level_id, 'role_after_expiry', 'subscriber' );
		list( $member_id, $user_id ) = $this->member_with_wp_user( $level_id, 'administrator' );

		SwpmMemberUtils::apply_expiry_role( $member_id );
		$this->assertSame( array( 'administrator' ), $this->roles_of( $user_id ) );
	}

	public function test_admin_account_status_changes_apply_and_restore_the_roles() {
		$level_id = SWPM_Test_Factory::create_level( array( 'role' => 'contributor' ) );
		SWPM_Test_Factory::set_level_field( $level_id, 'role_after_expiry', 'subscriber' );
		list( $member_id, $user_id ) = $this->member_with_wp_user( $level_id, 'contributor' );

		do_action( 'swpm_admin_account_status_updated', array( 'member_id' => $member_id, 'from_status' => 'active', 'to_status' => 'expired' ) );
		$this->assertSame( array( 'subscriber' ), $this->roles_of( $user_id ), 'Setting the member to expired must apply the expiry role.' );

		do_action( 'swpm_admin_account_status_updated', array( 'member_id' => $member_id, 'from_status' => 'expired', 'to_status' => 'active' ) );
		$this->assertSame( array( 'contributor' ), $this->roles_of( $user_id ), 'Reactivating an expired member must restore the level role.' );
	}

	public function test_activating_a_member_that_was_not_expired_keeps_its_role() {
		$level_id = SWPM_Test_Factory::create_level( array( 'role' => 'contributor' ) );
		SWPM_Test_Factory::set_level_field( $level_id, 'role_after_expiry', 'subscriber' );
		list( $member_id, $user_id ) = $this->member_with_wp_user( $level_id, 'subscriber' );

		do_action( 'swpm_admin_account_status_updated', array( 'member_id' => $member_id, 'from_status' => 'pending', 'to_status' => 'active' ) );
		$this->assertSame( array( 'subscriber' ), $this->roles_of( $user_id ) );
	}
}
