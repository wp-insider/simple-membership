<?php

class SwpmUtilsTest extends WP_UnitTestCase_Custom
{
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
}
