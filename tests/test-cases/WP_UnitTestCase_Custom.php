<?php

// tests/TestCase.php

class WP_UnitTestCase_Custom extends WP_UnitTestCase
{
    private array $mocked_static_methods = [];
    private array $mocked_functions = [];

    private $original_wpdb;

    public function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $this->original_wpdb = $wpdb;

        add_filter('pre_option_timezone_string', fn() => 'UTC');
        // add_filter('pre_option_swpm-messages', fn() => ['succeeded' => true]);
        // add_filter('pre_option_swpm-messages',      fn() => array());
        // add_filter('pre_option_gmt_offset',      fn() => -5);

        // update_option('timezone_string', 'America/New_York');
        // update_option('gmt_offset', -5);
        // update_option('swpm-messages', array());

        $this->suppress_wp_functions();

        $_POST = [];
        $_GET = [];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function tearDown(): void
    {
        $this->restore_wp_functions();
        $this->_restore_mocked_functions();
        $this->_restore_mocked_static_methods();

        $_POST = [];
        $_GET = [];

        // delete_option('timezone_string');
        // delete_option('gmt_offset');

        remove_all_filters('pre_option_timezone_string');
        // remove_all_filters('pre_option_swpm-messages');

        global $wpdb;
        $wpdb = $this->original_wpdb;

        $wpdb->query("DELETE FROM {$wpdb->prefix}swpm_members_tbl WHERE user_name LIKE 'test-%' OR email LIKE 'test-%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}swpm_membership_tbl WHERE alias LIKE 'test-%'");

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }

    private function suppress_wp_functions(): void
    {
        // Suppress wp_redirect — returns false so no headers are sent
        // add_filter('wp_redirect', '__return_false', PHP_INT_MAX);

        // Make wp_die() throw a catchable exception instead of calling exit()
        // (WP test suite already does this, but explicitly here for clarity)
        add_filter('wp_die_handler', array($this, '_wp_die_handler'), PHP_INT_MAX);

        $this->_mock_static_method('SwpmMiscUtils', 'redirect_to_url', function ($url) {
            throw new \RuntimeException('Redirected to: ' . $url);
        });

        $this->_mock_static_method('SwpmMiscUtils', 'get_current_page_url', function () {
            return 'http://example.com';
        });

	    $this->_mock_function('wp_redirect', function ($uri){
		    throw new \RuntimeException('Redirected to: ' . $uri);
	    });

		$this->_mock_function('filter_input', function ($type, $var_name, $filter = null, $options = 0){
			if ($type == INPUT_POST){
				$result = isset($_POST[$var_name]) ? $_POST[$var_name] : null;
			} else if ($type == INPUT_GET){
				$result = isset($_GET[$var_name]) ? $_GET[$var_name] : null;
			}

			switch ($filter){
				// case FILTER_VALIDATE_INT:
				case FILTER_SANITIZE_NUMBER_INT:
					if ( !empty($result) ){
						$result = (string) intval($result);
					}
					break;
				case FILTER_SANITIZE_EMAIL:
					if ( !empty($result) ){
						$result = sanitize_email($result);
					}
					break;
				case FILTER_DEFAULT:
				case FILTER_UNSAFE_RAW:
				default:
					if ( empty($result) ){
						$result = '';
					}
					break;
			}

			return $result;
	    });
    }

    protected function _mock_static_method(string $class, string $method, callable $replacement): void
    {
        uopz_set_return($class, $method, $replacement, true);
        $this->mocked_static_methods[] = [$class, $method]; // track for cleanup
    }

    protected function _restore_mocked_static_methods() : void {
        foreach ($this->mocked_static_methods as [$class, $method]) {
            uopz_unset_return($class, $method);
        }
        $this->mocked_static_methods = [];
    }

    protected function _mock_function(string $function, callable $replacement): void
    {
        uopz_set_return($function, $replacement, true);
        $this->mocked_functions[] = $function;
    }

    protected function _restore_mocked_functions(): void
    {
        foreach ($this->mocked_functions as $function) {
            uopz_unset_return($function);
        }
        $this->mocked_functions = [];
    }

    public function _wp_die_handler()
    {
        return function ($message) {
            throw new \WPDieException($message);
        };
    }

    protected function _expect_redirection()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Redirected to:/i');
    }

    /**
     * Assert that $wpdb->update() is called on a specific table with specific args.
     * Other update() calls on different tables are ignored.
     */
    protected function _expect_wpdb_update_on_table(object $wpdb, string $table, array $expected_data = [], array $expected_where = []): void
    {
        $called = false;

        $wpdb->method('update')
            ->willReturnCallback(
                function ($actual_table, $actual_data, $actual_where)
                use ($table, $expected_data, $expected_where, &$called) {

                    if ($actual_table === $table) {
                        $called = true;

                        if (!empty($expected_data)) {
                            $this->assertEquals(
                                $expected_data,
                                $actual_data,
                                // "wpdb->update() data mismatch on table: $table"
                                'Expected: ' . print_r($expected_data, true) . PHP_EOL . ' Actual: ' . print_r($actual_data, true)
                            );
                        }
                        if (!empty($expected_where)) {
                            $this->assertEquals(
                                $expected_where,
                                $actual_where,
                                "wpdb->update() where mismatch on table: $table"
                            );
                        }
                    }

                    return 1; // all updates succeed
                }
            );

        // Register a shutdown assertion to verify the call happened
        $this->addToAssertionCount(1);
        register_shutdown_function(function () use (&$called, $table) {
            if (!$called) {
                $this->fail("Expected wpdb->update() to be called on table: $table");
            }
        });
    }

    // protected function _swpm_transfer_mock_instance()
    // {
    //     return new class {
    //         public function set($value): void
    //         {
    //             return;
    //         }
    //     };
    // }

    private function restore_wp_functions(): void
    {
        remove_all_filters('wp_redirect');
        remove_all_filters('wp_die_handler');
    }


    protected function _allow_php_exit(bool $supporess)
    {
        // Fall back to the runtime call in bootstrap.php instead
        if (function_exists('uopz_allow_exit')) {
            uopz_allow_exit($supporess);
        }
    }

    /**
     * Simulate being on an admin screen
     *
     * @return void
     */
    protected function set_admin_screen(): void
    {
        set_current_screen('dashboard');  // any valid screen ID works
    }

    protected function _set_admin(): void
    {
        wp_set_current_user(WP_UnitTestCase_Base::factory()->user->create(['role' => 'administrator']));
    }

    protected function unset_current_screen(): void
    {
        unset($GLOBALS['current_screen']);
    }

    protected function _skip_if_table_missing($table_name): void
    {
        global $wpdb;
        // $wpdb->prefix = TEST_WPDB_TABLE_PREFIX;
        $table = $wpdb->prefix . $table_name;
        if (empty($wpdb->get_var("SHOW TABLES LIKE '%$table%'"))) {

            // $all_table = $wpdb->get_result("SHOW TABLES");
            // self::_debug($all_table);

            $this->markTestSkipped("Table $table missing — run plugin activation first.");
        }
    }

    protected function _nonce(string $post_key, string $action): void
    {
        $_POST[$post_key] = wp_create_nonce($action);
    }

    /** Insert a level row directly, bypassing SwpmMembershipLevel::create_level(), and return its ID. */
    protected static function _insert_membership_level(array $overrides = [], array $custom_fields = []): int
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'swpm_membership_tbl',
            array_merge([
                'alias'                    => 'test-direct',
                'role'                     => 'subscriber',
                'subscription_period'      => '1',
                'subscription_duration_type' => '3',
            ], $overrides)
        );
        $level_id = (int) $wpdb->insert_id;

        if (!empty($custom_fields)) {
            $custom_obj = SwpmMembershipLevelCustom::get_instance_by_id($level_id);
            foreach ($custom_fields as $item) {
                $item['id'] = $level_id;
                // $custom_obj->set($item);
            }
        }

        return $level_id;
    }

    protected static function _insert_member(array $overrides = array()): int
    {
        $member = SwpmTransfer::$default_fields;

        $data = wp_parse_args($overrides, $member);
        unset($data['accept_terms']);

        global $wpdb;
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'swpm_members_tbl',
            $data
        );

        // self::_debug('Is inserted: ' . $inserted . ' Data:' . print_r($data, true));
        if (empty($inserted)) {
            throw new \Exception('Member data could not be inserted!');
        }

        if (! empty($wpdb->last_error)) {
            self::_debug('Database Error: ' . $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    /** Suppress wp_redirect() so it does not halt test execution. */
    // private function _run_with_redirect_suppressed(callable $fn): void {
    //     add_filter('wp_redirect', '__return_false');
    //     try {
    //         $fn();
    //     } catch (\Exception $e) {
    //         // wp_redirect() may throw in test environments — expected.
    //     } finally {
    //         remove_filter('wp_redirect', '__return_false');
    //     }
    // }

    protected function _get_transfer_status()
    {
        return SwpmTransfer::get_instance()->get('status');
    }

    /**
     * Build a valid $_POST payload that matches what SwpmLevelForm actually reads.
     *
     * SwpmLevelForm::subscription_period() reads:
     *   $_POST['subscription_duration_type']       e.g. '3' (MONTHS)
     *   $_POST['subscription_period_3']            the numeric duration value
     *
     * For NO_EXPIRY (type 0) no subscription_period_* key is needed.
     */
    protected function _valid_membership_level_post(array $overrides = []): array
    {
        $base = [
            'alias'                          => 'test-level',
            'role'                           => 'subscriber',
            'subscription_duration_type'     => '3',   // MONTHS
            'subscription_period_3'          => '1',
            'email_activation'               => '0',
            'after_activation_redirect_page' => '',
            'default_account_status'         => 'active',
            'annual_fixed_date_min_period'   => '',
        ];
        return array_merge($base, $overrides);
    }

    protected function _valid_members_post(array $overrides = []): array
    {
        // $base = SwpmTransfer::$default_fields;
        $base = [
            'accept_terms' => 1,
            'accept_pp' => 1,
            'swpm_level_hash' => '',
            'swpm_membership_level' => '',
            'swpm_profile_edit_nonce_val' => '',
            // 'swpm_registration_submit' => 1,
        ];

        return array_merge($base, $overrides);
    }

    protected function _call_private_method(object $obj, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    public static function _debug($msg)
    {
        if (is_array($msg) || is_object($msg)) {
            echo PHP_EOL;
            print_r($msg);
            echo PHP_EOL;
            return;
        }

        echo PHP_EOL . ">>>>> " . $msg . PHP_EOL;
    }
}
