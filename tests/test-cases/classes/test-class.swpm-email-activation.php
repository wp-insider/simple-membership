<?php

/** Integration coverage for the real WordPress options API, database and cache. */
class SwpmEmailActivationTest extends WP_UnitTestCase {
    private $member_id = 987654;
    private $key = 'swpm_email_activation_data_usr_987654';

    public function test_new_tokens_do_not_store_passwords_and_resends_preserve_links() {
        add_filter( 'swpm_email_activation_data', function ( $data ) {
            $data['fb_form_id'] = 42;
            $data['plain_password'] = 'must-not-persist';
            return $data;
        } );
        $data = SwpmEmailActivation::get_or_create( $this->member_id );
        $this->assertTrue( SwpmEmailActivation::is_valid( $data ) );
        $this->assertSame( 32, strlen( $data['act_code'] ) );
        $this->assertArrayNotHasKey( 'plain_password', $data );
        $this->assertSame( 42, $data['fb_form_id'] );
        $this->assertSame( $data, get_option( $this->key ) );
        $this->assertSame( $data, SwpmEmailActivation::get_or_create( $this->member_id ) );
    }

    public function test_legacy_link_survives_password_removal() {
        $data = array( 'act_code' => md5( 'legacy-token' ), 'timestamp' => time() - 60, 'fb_form_id' => 17, 'plain_password' => 'encrypted-legacy-password' );
        update_option( $this->key, $data, false );
        unset( $data['plain_password'] );
        $this->assertSame( $data, SwpmEmailActivation::get_or_create( $this->member_id ) );
        $this->assertSame( $data, get_option( $this->key ) );
    }

    public function test_expired_token_is_replaced_without_losing_addon_metadata() {
        $expired = array( 'act_code' => 'expired', 'timestamp' => time() - DAY_IN_SECONDS, 'fb_form_id' => 17 );
        update_option( $this->key, $expired, false );
        $this->assertFalse( SwpmEmailActivation::is_valid( $expired ) );
        $fresh = SwpmEmailActivation::get_or_create( $this->member_id );
        $this->assertNotSame( 'expired', $fresh['act_code'] );
        $this->assertSame( 17, $fresh['fb_form_id'] );
        $this->assertTrue( SwpmEmailActivation::is_valid( $fresh ) );
    }

    public function test_token_consumption_is_conditional_and_invalidates_wordpress_cache() {
        $data = SwpmEmailActivation::get_or_create( $this->member_id );
        $stale = $data;
        $stale['act_code'] = 'different-token';
        $this->assertFalse( SwpmEmailActivation::consume( $this->member_id, $stale ) );
        $this->assertSame( $data, get_option( $this->key ) );
        $this->assertTrue( SwpmEmailActivation::consume( $this->member_id, $data ) );
        $this->assertFalse( get_option( $this->key ) );
        $this->assertFalse( SwpmEmailActivation::consume( $this->member_id, $data ) );
    }

    public function test_resend_does_not_recreate_a_concurrently_deleted_record() {
        update_option( $this->key, array( 'act_code' => 'expired', 'timestamp' => time() - DAY_IN_SECONDS ), false );
        add_filter( 'swpm_email_activation_data', function ( $data ) {
            delete_option( $this->key );
            return $data;
        } );
        $this->assertFalse( SwpmEmailActivation::get_or_create( $this->member_id ) );
        $this->assertFalse( get_option( $this->key ) );
    }

    public function test_resend_does_not_overwrite_a_concurrently_replaced_token() {
        update_option( $this->key, array( 'act_code' => 'expired', 'timestamp' => time() - DAY_IN_SECONDS ), false );
        $replacement = array( 'act_code' => 'other-request-token', 'timestamp' => time() );
        add_filter( 'swpm_email_activation_data', function ( $data ) use ( $replacement ) {
            update_option( $this->key, $replacement, false );
            return $data;
        } );
        $this->assertFalse( SwpmEmailActivation::get_or_create( $this->member_id ) );
        $this->assertSame( $replacement, get_option( $this->key ) );
    }

    public function test_initial_creation_does_not_overwrite_a_concurrent_insert() {
        $replacement = array( 'act_code' => 'other-request-token', 'timestamp' => time() );
        add_filter( 'swpm_email_activation_data', function ( $data ) use ( $replacement ) {
            add_option( $this->key, $replacement, '', false );
            return $data;
        } );
        $this->assertFalse( SwpmEmailActivation::get_or_create( $this->member_id ) );
        $this->assertSame( $replacement, get_option( $this->key ) );
    }

    public function test_consumption_also_invalidates_legacy_autoloaded_option_cache() {
        $data = array( 'act_code' => 'legacy', 'timestamp' => time() );
        add_option( $this->key, $data, '', true );
        wp_load_alloptions();
        $this->assertTrue( SwpmEmailActivation::consume( $this->member_id, $data ) );
        $this->assertFalse( get_option( $this->key ) );
    }

    public function test_migration_removes_passwords_without_changing_links_or_unrelated_options() {
        delete_option( 'swpm_activation_password_cleanup' );
        $data = array( 'act_code' => 'legacy', 'timestamp' => time(), 'plain_password' => 'old-secret', 'fb_form_id' => 19 );
        update_option( $this->key, $data, false );
        update_option( 'unrelated_activation_test_option', $data, false );
        SwpmEmailActivation::remove_legacy_passwords();
        $this->assertSame( $data, get_option( 'unrelated_activation_test_option' ) );
        unset( $data['plain_password'] );
        $this->assertSame( $data, get_option( $this->key ) );
        $this->assertSame( 'done', get_option( 'swpm_activation_password_cleanup' ) );
    }
}
