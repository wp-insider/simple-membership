<?php

class SwpmUtilsTest extends WP_UnitTestCase_Custom
{
    public function setUp(): void
    {
        parent::setUp();

        $this->_allow_php_exit(false);
    }

    public function tearDown(): void
    {
        $this->_allow_php_exit(true);

        parent::tearDown();
    }

    public function test_is_paid_registration() {
        $_GET['member_id'] = rand(1, 9);
        $_GET['code'] = md5('test-code');

        $result = SwpmUtils::is_paid_registration();
        
        $this->assertTrue($result, print_r($_GET, true));
        
        unset($_GET['member_id']);
        
        $result = SwpmUtils::is_paid_registration();
        $this->assertFalse($result);
    }

    public function test_wp_user_has_admin_role_detects_current_site_admin() {
        $user_id = self::factory()->user->create([
            'role' => 'administrator',
        ]);

        $this->assertTrue(SwpmMemberUtils::wp_user_has_admin_role($user_id));
    }

    public function test_wp_user_has_admin_role_returns_false_for_non_admin_user() {
        $user_id = self::factory()->user->create([
            'role' => 'subscriber',
        ]);

        $this->assertFalse(SwpmMemberUtils::wp_user_has_admin_role($user_id));
    }

    // =========================================================================
    // create_wp_user()
    // =========================================================================

    private function _wp_user_data_for_new_user(array $overrides = []): array
    {
        $base = [
            'user_nicename'   => 'test-new-wp-user',
            'display_name'    => 'test-new-wp-user',
            'user_email'      => 'test-new-wp-user@example.com',
            'nickname'        => 'test-new-wp-user',
            'first_name'      => '',
            'last_name'       => '',
            'user_login'      => 'test-new-wp-user',
            'password'        => 'test-pass',
            'role'            => 'subscriber',
            'user_registered' => date('Y-m-d H:i:s'),
        ];

        return array_merge($base, $overrides);
    }

    public function test_create_wp_user_creates_new_wp_user_when_no_existing_match() {
        $wp_user_data = $this->_wp_user_data_for_new_user();

        $wp_user_id = SwpmUtils::create_wp_user($wp_user_data);

        $this->assertIsInt($wp_user_id);
        $this->assertGreaterThan(0, $wp_user_id);

        $wp_user = get_user_by('id', $wp_user_id);
        $this->assertSame('test-new-wp-user', $wp_user->user_login);
        $this->assertSame('test-new-wp-user@example.com', $wp_user->user_email);
    }

    public function test_create_wp_user_dies_when_email_belongs_to_admin_user() {
        $wp_user_data = $this->_wp_user_data_for_new_user([
            'user_login' => 'test-new-wp-user-admin-email',
            'user_email' => WP_TESTS_EMAIL,
        ]);

        $this->expectException(WPDieException::class);

        SwpmUtils::create_wp_user($wp_user_data);
    }

    public function test_create_wp_user_dies_when_username_belongs_to_admin_user() {
        self::factory()->user->create([
            'user_login' => 'test-existing-admin-username',
            'user_email' => 'test-existing-admin-username@example.com',
            'role'       => 'administrator',
        ]);

        $wp_user_data = $this->_wp_user_data_for_new_user([
            'user_login' => 'test-existing-admin-username',
            'user_email' => 'test-new-wp-user-admin-username@example.com',
        ]);

        $this->expectException(WPDieException::class);

        SwpmUtils::create_wp_user($wp_user_data);
    }

    public function test_create_wp_user_returns_existing_wp_user_id_when_email_matches_existing_non_admin_user() {
        $existing_wp_user_id = self::factory()->user->create([
            'user_login' => 'test-existing-subscriber-for-email-bind',
            'user_email' => 'test-existing-subscriber-for-email-bind@example.com',
            'role'       => 'subscriber',
        ]);

        $wp_user_data = $this->_wp_user_data_for_new_user([
            'user_login' => 'test-new-registrant-for-email-bind',
            'user_email' => 'test-existing-subscriber-for-email-bind@example.com',
        ]);

        // Binding to a pre-existing non-admin WP user is no longer blocked inside create_wp_user() itself
        // (the caller is expected to have already checked this via SwpmMemberUtils::check_and_die_if_existing_wp_user_exists()).
        $wp_user_id = SwpmUtils::create_wp_user($wp_user_data);

        $this->assertSame($existing_wp_user_id, $wp_user_id);
    }

    public function test_create_wp_user_returns_existing_wp_user_id_when_username_matches_existing_non_admin_user() {
        $existing_wp_user_id = self::factory()->user->create([
            'user_login' => 'test-existing-subscriber-for-username-bind',
            'user_email' => 'test-existing-subscriber-for-username-bind@example.com',
            'role'       => 'subscriber',
        ]);

        $wp_user_data = $this->_wp_user_data_for_new_user([
            'user_login' => 'test-existing-subscriber-for-username-bind',
            'user_email' => 'test-new-registrant-for-username-bind@example.com',
        ]);

        $wp_user_id = SwpmUtils::create_wp_user($wp_user_data);

        $this->assertSame($existing_wp_user_id, $wp_user_id);
    }
}
