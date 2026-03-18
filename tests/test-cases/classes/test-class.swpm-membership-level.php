<?php

class SwpmMembershipLevelTest extends WP_UnitTestCase_Custom
{

    /** @var SwpmMembershipLevel */
    private $instance;

    // =========================================================================
    // Setup / Teardown
    // =========================================================================

    public function setUp(): void
    {
        parent::setUp();

        // Reset singleton so each test starts fresh
        $prop = new ReflectionProperty(SwpmMembershipLevel::class, '_instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->instance = SwpmMembershipLevel::get_instance();

        $this->set_admin_screen();
        $this->_allow_php_exit(false);

        // $this->_skip_if_table_missing('swpm_membership_tbl');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->unset_current_screen();
        $this->_allow_php_exit(true);
    }

    // =========================================================================
    // create_level() — Security Guards
    // =========================================================================

    public function test_create_level_dies_for_non_admin_user(): void
    {
        wp_set_current_user(WP_UnitTestCase_Base::factory()->user->create(['role' => 'subscriber']));

        $this->expectException(WPDieException::class);
        $this->instance->create_level();
    }

    public function test_create_level_dies_when_nonce_key_is_absent(): void
    {
        $this->_set_admin();
        // No nonce set in $_POST at all

        $this->expectException(WPDieException::class);
        $this->instance->create_level();
    }

    public function test_create_level_dies_when_nonce_is_invalid(): void
    {
        $this->_set_admin();
        $_POST['_wpnonce_create_swpmlevel_admin_end'] = 'not_a_real_nonce';

        $this->expectException(WPDieException::class);
        $this->instance->create_level();
    }

    // =========================================================================
    // create_level() — Successful Insertion
    // =========================================================================

    public function test_create_level_inserts_row_into_database(): void
    {
        global $wpdb;
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post(['alias' => 'test-insert-row', 'role' => 'subscriber']);
        // $_POST = $this->_valid_membership_level_post(['alias' => 'test-insert-row']);
        // $_POST = [
        //     'alias'                          => 'test-insert-row',
        //     'role'                           => 'subscriber',
        //     'subscription_period' => 1,
        //     'subscription_duration_type'     => '3',   // MONTHS
        //     'protect_older_posts' => 0
        // ];

        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        // $level = SwpmTransfer::$default_level_fields;
        // $form = new SwpmLevelForm($level);
        // $level_info = $form->get_sanitized();
        // // $level_info = $_POST;

        // $inserted = $wpdb->insert($wpdb->prefix . "swpm_membership_tbl", $level_info);
        // $id = $wpdb->insert_id;


        // $row_arr = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE id = %d", $id), ARRAY_A);

        // $this->assertSame($inserted, null, 'Expected the number of inserted row. ID: '. $id . " Data: ". print_r($row_arr, true));
        // return;

        $row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-insert-row'", ARRAY_A);
        $this->assertNotNull($row, 'Expected a new row in swpm_membership_tbl.');
        // $this->assertNotNull($row, 'Expected a new row in swpm_membership_tbl. ' . print_r($level_info, true));
        $this->assertSame('subscriber', $row['role']);
    }

    public function test_create_level_sets_succeeded_true_in_transfer(): void
    {
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post(['alias' => 'test-transfer-true']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $this->assertTrue($this->_get_transfer_status()['succeeded']);
    }

    public function test_create_level_stores_email_activation_option(): void
    {
        global $wpdb;
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post(['alias' => 'test-email-act', 'email_activation' => '1']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $id = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-email-act'");
        $this->assertGreaterThan(0, $id);
        $value = get_option("swpm_email_activation_lvl_{$id}");
        $this->assertSame(1, $value);
    }

    public function test_create_level_stores_after_activation_redirect_url(): void
    {
        global $wpdb;
        $this->_set_admin();
        $url   = 'https://example.com/welcome';
        $_POST = $this->_valid_membership_level_post([
            'alias'                          => 'test-redirect-url',
            'after_activation_redirect_page' => $url,
        ]);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $id     = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-redirect-url'");
        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $this->assertSame($url, $custom->get('after_activation_redirect_page'));
    }

    public function test_create_level_stores_default_account_status(): void
    {
        global $wpdb;
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post([
            'alias'                  => 'test-acct-status',
            'default_account_status' => 'inactive',
        ]);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $id     = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-acct-status'");
        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $this->assertSame('inactive', $custom->get('default_account_status'));
    }

    public function test_create_level_stores_annual_fixed_date_min_period(): void
    {
        global $wpdb;
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post([
            'alias'                        => 'test-min-period',
            'annual_fixed_date_min_period' => '30',
        ]);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $id     = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-min-period'");
        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $this->assertSame(30, (int) $custom->get('annual_fixed_date_min_period'));
    }

    public function test_create_level_with_no_expiry_sets_empty_subscription_period(): void
    {
        global $wpdb;
        $this->_set_admin();

        // NO_EXPIRY = 0: SwpmLevelForm short-circuits and writes subscription_period = ""
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-no-expiry',
            'subscription_duration_type' => '0',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $row = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-no-expiry'",
            ARRAY_A
        );
        $this->assertNotNull($row);
        $this->assertSame('', $row['subscription_period']);
    }

    public function test_create_level_with_fixed_date_type_stores_date_string(): void
    {
        global $wpdb;
        $this->_set_admin();

        // FIXED_DATE = 5: subscription_period_5 is a date string
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-fixed-date',
            'subscription_duration_type' => '5',
            'subscription_period_5'      => '2026-12-31',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $row = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-fixed-date'",
            ARRAY_A
        );
        $this->assertNotNull($row);
        $this->assertSame('2026-12-31', $row['subscription_period']);
    }

    public function test_create_level_sanitizes_negative_annual_min_period_via_absint(): void
    {
        global $wpdb;
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post([
            'alias'                        => 'test-absint',
            'annual_fixed_date_min_period' => '-7',
        ]);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $id = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias = 'test-absint'");
        // if (! $id) { // TODO
        //     $this->markTestSkipped('Level not inserted — form validation may have blocked it.');
        // }
        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        // absint(-7) === 7
        $this->assertSame(7, (int) $custom->get('annual_fixed_date_min_period'));
    }

    public function test_create_level_empty_optional_fields_do_not_block_creation(): void
    {
        $this->_set_admin();
        $_POST = $this->_valid_membership_level_post([
            'alias'                          => 'test-empty-optionals',
            'after_activation_redirect_page' => '',
            'default_account_status'         => '',
            'annual_fixed_date_min_period'   => '',
        ]);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $this->assertTrue($this->_get_transfer_status()['succeeded']);
    }

    // =========================================================================
    // create_level() — Validation Failure (driven by SwpmLevelForm::is_valid())
    // =========================================================================

    public function test_create_level_fails_when_subscription_period_is_non_numeric(): void
    {
        $this->_set_admin();

        // DAYS (type 1) with a non-numeric period → SwpmLevelForm adds subscription_period error
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-invalid-period',
            'subscription_duration_type' => '1',
            'subscription_period_1'      => 'abc',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $status = $this->_get_transfer_status();
        $this->assertFalse($status['succeeded']);
        $this->assertArrayHasKey('subscription_period', $status['extra']);
    }

    public function test_create_level_fails_when_fixed_date_value_is_invalid(): void
    {
        $this->_set_admin();

        // FIXED_DATE (type 5) with an unparseable string
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-bad-date',
            'subscription_duration_type' => '5',
            'subscription_period_5'      => 'not-a-date',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $status = $this->_get_transfer_status();
        $this->assertFalse($status['succeeded']);
        $this->assertArrayHasKey('subscription_period', $status['extra']);
    }

    public function test_create_level_fails_when_annual_fixed_date_is_not_an_array(): void
    {
        $this->_set_admin();

        // ANNUAL_FIXED_DATE (type 6) expects subscription_period_6 to be ['m' => ..., 'd' => ...]
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-annual-not-array',
            'subscription_duration_type' => '6',
            'subscription_period_6'      => 'not-an-array',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_create_swpmlevel_admin_end', 'create_swpmlevel_admin_end');

        $this->instance->create_level();

        $status = $this->_get_transfer_status();
        $this->assertFalse($status['succeeded']);
        $this->assertArrayHasKey('subscription_period', $status['extra']);
    }

    // =========================================================================
    // edit_level() — Security Guards
    // =========================================================================

    public function test_edit_level_dies_for_non_admin_user(): void
    {
        wp_set_current_user($this->factory->user->create(['role' => 'subscriber']));

        $this->expectException(WPDieException::class);
        $this->instance->edit_level(1);
    }

    public function test_edit_level_dies_when_nonce_key_is_absent(): void
    {
        $this->_set_admin();

        $this->expectException(WPDieException::class);
        $this->instance->edit_level(1);
    }

    public function test_edit_level_dies_when_nonce_is_invalid(): void
    {
        $this->_set_admin();
        $_POST['_wpnonce_edit_swpmlevel_admin_end'] = 'invalid_nonce_value';

        $this->expectException(WPDieException::class);
        $this->instance->edit_level(1);
    }

    // =========================================================================
    // edit_level() — Successful Update
    // =========================================================================

    public function test_edit_level_updates_role_in_database(): void
    {
        global $wpdb;
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-role', 'role' => 'subscriber']);

        $_POST = $this->_valid_membership_level_post(['alias' => 'test-edit-role', 'role' => 'author']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM {$wpdb->prefix}swpm_membership_tbl WHERE id = %d",
            $id
        ));
        $this->assertSame('author', $role);
    }

    public function test_edit_level_updates_subscription_duration_type(): void
    {
        global $wpdb;
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-dtype', 'subscription_duration_type' => '3']);

        // Change from MONTHS (3) → YEARS (4)
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-edit-dtype',
            'subscription_duration_type' => '4',
            'subscription_period_4'      => '2',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $dtype = $wpdb->get_var($wpdb->prepare(
            "SELECT subscription_duration_type FROM {$wpdb->prefix}swpm_membership_tbl WHERE id = %d",
            $id
        ));
        $this->assertSame('4', $dtype);
    }

    public function test_edit_level_updates_email_activation_option(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-email-act']);
        update_option("swpm_email_activation_lvl_{$id}", '0', false);

        $_POST = $this->_valid_membership_level_post(['alias' => 'test-edit-email-act', 'email_activation' => '1']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $this->assertSame(1, get_option("swpm_email_activation_lvl_{$id}"));
    }

    public function test_edit_level_updates_after_activation_redirect_url(): void
    {
        $this->_set_admin();
        $id  = $this->_insert_membership_level(['alias' => 'test-edit-redirect']);
        $url = 'https://example.com/updated';

        $_POST = $this->_valid_membership_level_post([
            'alias'                          => 'test-edit-redirect',
            'after_activation_redirect_page' => $url,
        ]);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $this->assertSame($url, $custom->get('after_activation_redirect_page'));
    }

    public function test_edit_level_updates_default_account_status(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-status']);

        $_POST = $this->_valid_membership_level_post([
            'alias'                  => 'test-edit-status',
            'default_account_status' => 'expired',
        ]);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $this->assertSame('expired', $custom->get('default_account_status'));
    }

    public function test_edit_level_updates_annual_fixed_date_min_period(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-min-period']);

        $_POST = $this->_valid_membership_level_post([
            'alias'                        => 'test-edit-min-period',
            'annual_fixed_date_min_period' => '45',
        ]);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $custom = SwpmMembershipLevelCustom::get_instance_by_id($id);
        $this->assertSame(45, (int) $custom->get('annual_fixed_date_min_period'));
    }

    public function test_edit_level_sets_succeeded_true_in_transfer(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-success']);

        $_POST = $this->_valid_membership_level_post(['alias' => 'test-edit-success']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $this->assertTrue($this->_get_transfer_status()['succeeded']);
    }

    // =========================================================================
    // edit_level() — Validation Failure
    // =========================================================================

    public function test_edit_level_fails_when_subscription_period_is_non_numeric(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-invalid-period']);

        // WEEKS (type 2) with a non-numeric value
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-edit-invalid-period',
            'subscription_duration_type' => '2',
            'subscription_period_2'      => 'xyz',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $status = $this->_get_transfer_status();
        $this->assertFalse($status['succeeded']);
        $this->assertArrayHasKey('subscription_period', $status['extra']);
    }

    public function test_edit_level_fails_when_fixed_date_value_is_invalid(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-bad-date']);

        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-edit-bad-date',
            'subscription_duration_type' => '5',
            'subscription_period_5'      => 'not-a-date',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $status = $this->_get_transfer_status();
        $this->assertFalse($status['succeeded']);
        $this->assertArrayHasKey('subscription_period', $status['extra']);
    }

    public function test_edit_level_fails_when_annual_fixed_date_is_not_an_array(): void
    {
        $this->_set_admin();
        $id = $this->_insert_membership_level(['alias' => 'test-edit-annual-not-array']);

        // ANNUAL_FIXED_DATE (type 6) expects ['m' => ..., 'd' => ...] array
        $_POST = $this->_valid_membership_level_post([
            'alias'                      => 'test-edit-annual-not-array',
            'subscription_duration_type' => '6',
            'subscription_period_6'      => 'string-not-array',
        ]);
        unset($_POST['subscription_period_3']);
        $this->_nonce('_wpnonce_edit_swpmlevel_admin_end', 'edit_swpmlevel_admin_end');

        $this->instance->edit_level($id);

        $status = $this->_get_transfer_status();
        $this->assertFalse($status['succeeded']);
        $this->assertArrayHasKey('subscription_period', $status['extra']);
    }
}
