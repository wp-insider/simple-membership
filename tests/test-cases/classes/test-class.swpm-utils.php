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

    // =========================================================================
    // get_expiration_timestamp() — ANNUAL_FIXED_DATE
    // =========================================================================

    /**
     * Creates an ANNUAL_FIXED_DATE level. The stored subscription_period is a full
     * Y-m-d string (that is how SwpmLevelForm saves it); only the month/day matter
     * at runtime, so the year here is deliberately arbitrary.
     */
    private function _make_annual_fixed_date_level(string $month_day, int $min_period_days): int
    {
        $level_id = self::_insert_membership_level([
            'alias'                      => 'test-annual-fixed-' . uniqid(),
            'subscription_period'        => '2000-' . $month_day,
            'subscription_duration_type' => (string) SwpmMembershipLevel::ANNUAL_FIXED_DATE,
        ]);

        SwpmMembershipLevelCustom::get_instance_by_id($level_id)->set([
            'meta_key'     => 'annual_fixed_date_min_period',
            'meta_value'   => (string) $min_period_days,
            'meta_context' => 'default',
        ]);

        return $level_id;
    }

    private function _member_on_level(int $level_id, string $subscription_starts): stdClass
    {
        $user = new stdClass();
        $user->member_id          = 123;
        $user->membership_level   = $level_id;
        $user->subscription_starts = $subscription_starts;

        return $user;
    }

    private function _expiration_date(stdClass $user): string
    {
        return date('Y-m-d', SwpmUtils::get_expiration_timestamp($user));
    }

    /**
     * Regression test for the calendar-year instability bug: the expiration must be
     * derived from the member's subscription_starts year, never from date('Y'). The
     * subscription below started years before "now", so any dependence on the current
     * year would produce a different (and drifting) result.
     */
    public function test_annual_fixed_date_expiration_is_anchored_to_subscription_start_year() {
        $level_id = $this->_make_annual_fixed_date_level('03-31', 91);

        // Started after the fixed date in the start year (2020-03-31), so the first
        // expiry is the fixed date in the following year.
        $user = $this->_member_on_level($level_id, '2020-12-31');

        $this->assertSame('2021-03-31', $this->_expiration_date($user));
    }

    /** The same member/level must resolve to the same expiration no matter when it is evaluated. */
    public function test_annual_fixed_date_expiration_is_stable_across_repeated_calls() {
        $level_id = $this->_make_annual_fixed_date_level('03-31', 91);
        $user     = $this->_member_on_level($level_id, '2020-12-31');

        $first  = SwpmUtils::get_expiration_timestamp($user);
        $second = SwpmUtils::get_expiration_timestamp($user);

        $this->assertSame($first, $second);
    }

    public function test_annual_fixed_date_expiration_uses_fixed_date_in_start_year_when_far_enough_ahead() {
        $level_id = $this->_make_annual_fixed_date_level('06-30', 30);
        $user     = $this->_member_on_level($level_id, '2021-01-15');

        $this->assertSame('2021-06-30', $this->_expiration_date($user));
    }

    public function test_annual_fixed_date_expiration_rolls_to_next_year_when_started_after_fixed_date() {
        $level_id = $this->_make_annual_fixed_date_level('06-30', 30);
        $user     = $this->_member_on_level($level_id, '2021-08-01');

        $this->assertSame('2022-06-30', $this->_expiration_date($user));
    }

    public function test_annual_fixed_date_expiration_rolls_forward_when_below_min_period() {
        $level_id = $this->_make_annual_fixed_date_level('06-30', 30);

        // Only 10 days between the subscription start and the fixed date -> below the 30 day minimum.
        $user = $this->_member_on_level($level_id, '2021-06-20');

        $this->assertSame('2022-06-30', $this->_expiration_date($user));
    }
}
