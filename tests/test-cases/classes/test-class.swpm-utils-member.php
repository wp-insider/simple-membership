<?php

class SwpmUtilsMemberTest extends WP_UnitTestCase_Custom
{
    public function setUp(): void
    {
        parent::setUp();

        $this->_allow_php_exit(false);
    }

    public function tearDown(): void
    {
        // Make sure the setting doesn't leak into other tests.
        SwpmSettings::get_instance()->set_value('allow-existing-wp-user-registration', '');

        $this->_allow_php_exit(true);

        parent::tearDown();
    }

    // =========================================================================
    // find_wp_user_by_email_or_username()
    // =========================================================================

    public function test_find_wp_user_by_email_or_username_returns_empty_array_when_no_match(): void
    {
        $result = SwpmMemberUtils::find_wp_user_by_email_or_username([
            'email' => 'test-no-match@example.com',
            'user_name' => 'test-no-match-user',
        ]);

        $this->assertSame([], $result);
    }

    public function test_find_wp_user_by_email_or_username_returns_empty_array_when_both_values_are_empty(): void
    {
        $result = SwpmMemberUtils::find_wp_user_by_email_or_username([
            'email' => '',
            'user_name' => '',
        ]);

        $this->assertSame([], $result);
    }

    public function test_find_wp_user_by_email_or_username_finds_existing_user_by_email(): void
    {
        $wp_user_id = self::factory()->user->create([
            'user_login' => 'test-existing-user',
            'user_email' => 'test-existing-user@example.com',
            'role' => 'subscriber',
        ]);

        $result = SwpmMemberUtils::find_wp_user_by_email_or_username([
            'email' => 'test-existing-user@example.com',
            'user_name' => 'test-some-other-username',
        ]);

        $this->assertSame($wp_user_id, $result['wp_user_id']);
        $this->assertSame('email', $result['identified_by']);
        $this->assertSame('test-existing-user@example.com', $result['identifier_value']);
    }

    public function test_find_wp_user_by_email_or_username_finds_existing_user_by_username_when_email_does_not_match(): void
    {
        $wp_user_id = self::factory()->user->create([
            'user_login' => 'test-existing-username',
            'user_email' => 'test-existing-username@example.com',
            'role' => 'subscriber',
        ]);

        $result = SwpmMemberUtils::find_wp_user_by_email_or_username([
            'email' => 'test-no-match@example.com',
            'user_name' => 'test-existing-username',
        ]);

        $this->assertSame($wp_user_id, $result['wp_user_id']);
        $this->assertSame('username', $result['identified_by']);
        $this->assertSame('test-existing-username', $result['identifier_value']);
    }

    public function test_find_wp_user_by_email_or_username_prioritizes_email_match_over_username(): void
    {
        $wp_user_id_by_email = self::factory()->user->create([
            'user_login' => 'test-user-matched-by-email',
            'user_email' => 'test-user-matched-by-email@example.com',
            'role' => 'subscriber',
        ]);

        $wp_user_id_by_username = self::factory()->user->create([
            'user_login' => 'test-user-matched-by-username',
            'user_email' => 'test-user-matched-by-username@example.com',
            'role' => 'subscriber',
        ]);

        $result = SwpmMemberUtils::find_wp_user_by_email_or_username([
            'email' => 'test-user-matched-by-email@example.com',
            'user_name' => 'test-user-matched-by-username',
        ]);

        $this->assertSame($wp_user_id_by_email, $result['wp_user_id']);
        $this->assertSame('email', $result['identified_by']);
        $this->assertNotEquals($wp_user_id_by_username, $result['wp_user_id']);
    }

    // =========================================================================
    // is_existing_wp_user_binding_allowed()
    // =========================================================================

    public function test_is_existing_wp_user_binding_allowed_returns_false_by_default(): void
    {
        SwpmSettings::get_instance()->set_value('allow-existing-wp-user-registration', '');

        $this->assertFalse(SwpmMemberUtils::is_existing_wp_user_binding_allowed());
    }

    public function test_is_existing_wp_user_binding_allowed_returns_true_when_setting_enabled(): void
    {
        SwpmSettings::get_instance()->set_value('allow-existing-wp-user-registration', '1');

        $this->assertTrue(SwpmMemberUtils::is_existing_wp_user_binding_allowed());
    }

    // =========================================================================
    // check_and_die_if_existing_wp_user_binding_not_allowed()
    // =========================================================================

    public function test_check_and_die_if_existing_wp_user_binding_not_allowed_does_nothing_when_wp_user_id_is_empty(): void
    {
        SwpmMemberUtils::check_and_die_if_existing_wp_user_binding_not_allowed(0, 'email', 'test-user@example.com');

        // wp_die() was not triggered — reaching here means the assertion passed.
        $this->addToAssertionCount(1);
    }

    public function test_check_and_die_if_existing_wp_user_binding_not_allowed_does_nothing_when_binding_allowed(): void
    {
        SwpmSettings::get_instance()->set_value('allow-existing-wp-user-registration', '1');

        SwpmMemberUtils::check_and_die_if_existing_wp_user_binding_not_allowed(123, 'email', 'test-user@example.com');

        // wp_die() was not triggered — reaching here means the assertion passed.
        $this->addToAssertionCount(1);
    }

    public function test_check_and_die_if_existing_wp_user_binding_not_allowed_dies_with_email_message(): void
    {
        $this->expectException(WPDieException::class);
        $this->expectExceptionMessageMatches('/test-user@example\\.com/');

        SwpmMemberUtils::check_and_die_if_existing_wp_user_binding_not_allowed(123, 'email', 'test-user@example.com');
    }

    public function test_check_and_die_if_existing_wp_user_binding_not_allowed_dies_with_username_message(): void
    {
        $this->expectException(WPDieException::class);
        $this->expectExceptionMessageMatches('/test-existing-username/');

        SwpmMemberUtils::check_and_die_if_existing_wp_user_binding_not_allowed(123, 'username', 'test-existing-username');
    }
}
