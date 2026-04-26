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
}
