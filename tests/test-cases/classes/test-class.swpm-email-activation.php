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

    public function test_metadata_is_stored_without_allowing_token_or_password_overrides() {
        $data = SwpmEmailActivation::get_or_create( $this->member_id, array(
            'fb_form_id' => 73, 'plain_password' => 'secret', 'act_code' => 'unsafe', 'timestamp' => 1,
        ) );
        $this->assertSame( 73, $data['fb_form_id'] );
        $this->assertArrayNotHasKey( 'plain_password', $data );
        $this->assertNotSame( 'unsafe', $data['act_code'] );
        $this->assertTrue( SwpmEmailActivation::is_valid( $data ) );
        $this->assertSame( $data, SwpmEmailActivation::get_or_create( $this->member_id, array( 'fb_form_id' => 99 ) ) );
    }

    public function test_completed_cleanup_is_autoloaded() {
        global $wpdb;
        delete_option( 'swpm_activation_password_cleanup' );
        update_option( $this->key, array( 'timestamp' => time(), 'act_code' => 'old-addon-token', 'plain_password' => 'old-addon-password' ), false );
        SwpmEmailActivation::remove_legacy_passwords();
        $this->assertArrayNotHasKey( 'plain_password', get_option( $this->key ) );
        wp_cache_flush();
        $alloptions = wp_load_alloptions();
        $this->assertSame( 'done', $alloptions['swpm_activation_password_cleanup'] );
        $queries = $wpdb->num_queries;
        SwpmEmailActivation::remove_legacy_passwords();
        $this->assertSame( $queries, $wpdb->num_queries, 'Finished cleanup must not issue its own database query.' );
    }

    public function test_failed_cleanup_defers_retry_and_keeps_progress() {
        global $wpdb;
        delete_option( 'swpm_activation_password_cleanup' );
        uopz_set_return( 'wpdb', 'get_results', function () { return null; }, true );
        try {
            SwpmEmailActivation::remove_legacy_passwords();
        } finally {
            uopz_unset_return( 'wpdb', 'get_results' );
        }
        $progress = get_option( 'swpm_activation_password_cleanup' );
        $this->assertSame( 0, $progress['cursor'] );
        $this->assertGreaterThan( time(), $progress['retry_after'] );
        $queries = $wpdb->num_queries;
        SwpmEmailActivation::remove_legacy_passwords();
        $this->assertSame( $queries, $wpdb->num_queries );
    }

    public function test_invalid_link_message_offers_a_resend_for_the_same_member() {
        $message = SwpmEmailActivation::invalid_link_message( $this->member_id );
        $this->assertStringContainsString( 'invalid or has expired', $message );
        $this->assertStringContainsString( 'swpm_resend_activation_email=1', $message );
        $this->assertStringContainsString( 'swpm_member_id=' . $this->member_id, $message );
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
