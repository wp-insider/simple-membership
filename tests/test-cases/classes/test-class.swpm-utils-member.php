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
    // check_and_die_if_existing_wp_user_exists()
    // =========================================================================

    public function test_check_and_die_if_existing_wp_user_exists_does_nothing_when_no_wp_user_match(): void
    {
        SwpmMemberUtils::check_and_die_if_existing_wp_user_exists([
            'email' => 'no-match@example.com',
            'user_name' => 'no-match-user',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_check_and_die_if_existing_wp_user_exists_dies_with_email_message_by_default(): void
    {
        self::factory()->user->create([
            'user_login' => 'test-existing-email-user',
            'user_email' => 'test-user@example.com',
            'role' => 'subscriber',
        ]);

        $this->expectException(WPDieException::class);
        $this->expectExceptionMessageMatches('/test-user@example\\.com/');

        SwpmMemberUtils::check_and_die_if_existing_wp_user_exists([
            'email' => 'test-user@example.com',
            'user_name' => 'some-other-user',
        ]);
    }

    public function test_check_and_die_if_existing_wp_user_exists_dies_with_username_message_by_default(): void
    {
        self::factory()->user->create([
            'user_login' => 'test-existing-username',
            'user_email' => 'test-existing-username@example.com',
            'role' => 'subscriber',
        ]);

        $this->expectException(WPDieException::class);
        $this->expectExceptionMessageMatches('/test-existing-username/');

        SwpmMemberUtils::check_and_die_if_existing_wp_user_exists([
            'email' => 'different-email@example.com',
            'user_name' => 'test-existing-username',
        ]);
    }

    public function test_check_and_die_if_existing_wp_user_exists_does_nothing_when_filter_allows_binding(): void
    {
        self::factory()->user->create([
            'user_login' => 'test-filter-user',
            'user_email' => 'test-filter-user@example.com',
            'role' => 'subscriber',
        ]);

        add_filter('swpm_allow_existing_wp_user_registration', '__return_true');

        SwpmMemberUtils::check_and_die_if_existing_wp_user_exists([
            'email' => 'test-filter-user@example.com',
            'user_name' => 'some-other-user',
        ]);

        remove_filter('swpm_allow_existing_wp_user_registration', '__return_true');

        $this->addToAssertionCount(1);
    }
}
