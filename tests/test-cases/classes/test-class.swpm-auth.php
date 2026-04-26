<?php

class SwpmAuthTest extends WP_UnitTestCase_Custom
{
    /** 
     * @var SwpmAuth 
     */
    private $instance;

    private $auth_mock;

    private $level_id;

    private $member;

    public function setUp(): void
    {
        parent::setUp();

        // $this->set_admin_screen();
        $this->_allow_php_exit(false);

        $this->_skip_if_table_missing('swpm_members_tbl');

        // Reset singleton so each test starts fresh
        $prop = new ReflectionProperty(SwpmAuth::class, '_this');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->instance = SwpmAuth::get_instance();

        // Mock SwpmAuth singleton
        // $this->auth_mock = $this->createMock(SwpmAuth::class);
        // $this->_inject_auth_mock($this->auth_mock);

        // $member_id = self::_insert_member([
        //     'user_name' => 'test-reset-pass-using-link',
        //     'password' => 'test-pass',
        //     'email' => 'test-reset-pass-using-link@example.com',
        //     'membership_level' => $this->level_id,
        //     'reg_code' => md5('abcd'),
        // ]);

        // $this->member = SwpmMemberUtils::get_user_by_id($member_id);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // $this->unset_current_screen();
        $this->_allow_php_exit(true);

        // $this->_reset_auth_singleton();
    }

    // -------------------------------------------------------------------------
    // Helper — inject mock into SwpmAuth singleton
    // -------------------------------------------------------------------------

    private function _inject_auth_mock(object $mock): void
    {
        $ref      = new ReflectionClass(SwpmAuth::class);
        $prop     = $ref->getProperty('_this');
        $prop->setAccessible(true);
        $prop->setValue(null, $mock);
    }

    private function _reset_auth_singleton(): void
    {
        $ref  = new ReflectionClass(SwpmAuth::class);
        $prop = $ref->getProperty('_this');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_get_instance(): void
    {
        $this->assertInstanceOf(SwpmAuth::class, SwpmAuth::get_instance());
    }

    /**
     * @dataProvider member_auth_credentials_data
     *
     * @return void
     */
    public function test_authenticate_error_check_with_post_request_data($post_data, $expected, $lastStatusMsg = ''): void {
        $_POST = $post_data;

        $result = $this->_call_private_method($this->instance, 'authenticate');

		if (!empty($lastStatusMsg)){
			$this->assertStringContainsString($lastStatusMsg, $this->instance->get_message());
		}

        $this->assertEquals($expected, $result);

    }

    /**
     * @dataProvider member_auth_credentials_data
     *
     * @return void
     */
    public function test_authenticate_error_check_with_function_params($creds, $expected, $lastStatusMsg = ''): void {
	    $_POST = $creds;

        $params_data = [
            'user' => $creds['swpm_user_name'],
            'pass' => $creds['swpm_password'],
        ];

        $result = $this->_call_private_method($this->instance, 'authenticate', $params_data);

	    if (!empty($lastStatusMsg)){
		    $this->assertStringContainsString($lastStatusMsg, $this->instance->get_message());
	    }

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    protected function member_auth_credentials_data(): array {
        return [
            'Empty Username' => [
                [
                    'swpm_user_name' => '',
                    'swpm_password' => 'test-authenticate-password',
                ],
                false,
	            'Username field cannot be empty.',
            ],

            'Empty Password' => [
                [
                    'swpm_user_name' => 'test-authenticate-user-name',
                    'swpm_password' => '',
                ],
                false,
	            'Password field cannot be empty.',
            ],

            'Empty Username and Password' => [
                [
                    'swpm_user_name' => '',
                    'swpm_password' => '',
                ],
                false,
	            '',
            ],

             'With Non-existing Username and Password' => [
                 [
                     'swpm_user_name' => 'test-authenticate-user-name',
                     'swpm_password' => 'test-authenticate-password',
                 ],
                 false,
	             'No user found with that username or email.'
             ],
        ];
    }
}
