<?php
/**
 * Handles various AJAX calls
 */

class SwpmAjax {

    public static function validate_email_ajax() {
        global $wpdb;
        $field_value = isset($_GET['fieldValue']) ? sanitize_text_field($_GET['fieldValue']) : '';
        $field_value = stripslashes($field_value);//Clean the email address value (so it can work with values like: test.de'souz@exaple.com)
        
        $field_id = isset($_GET['fieldId']) ? sanitize_text_field($_GET['fieldId']) : '';
        $member_id = isset($_GET['member_id']) ? sanitize_text_field($_GET['member_id']) : '';
        if (!check_ajax_referer( 'swpm-rego-form-ajax-nonce', 'nonce', false )) {
            echo '[ "' . esc_attr($field_id) .  '",false, "'.esc_html__( 'Nonce check failed. Please reload the page.', 'simple-membership' ).'" ]' ;
            exit;
        }
        if (!is_email($field_value)){
            echo '[ "' . esc_attr($field_id) .  '",false, "'.esc_html__( 'Invalid Email Address', 'simple-membership' ).'" ]' ;
            exit;
        }
        $table = $wpdb->prefix . "swpm_members_tbl";
        
        $db_id = $wpdb->get_var($wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}swpm_members_tbl WHERE email = %s AND user_name != ''", $field_value)) ;
        $exists = ($db_id > 0) && $db_id != $member_id;
        echo '[ "' . esc_attr($field_id) . (($exists) ? '",false, "&chi;&nbsp;'.esc_html__( 'Already taken', 'simple-membership' ).'"]' : '",true, "&radic;&nbsp;'.esc_html__( 'Available', 'simple-membership' ). '"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values are escaped; markup is a fixed JSON response for the validation engine.
        exit;
    }

    public static function validate_user_name_ajax() {
        global $wpdb;
        $field_value = isset($_GET['fieldValue']) ? sanitize_text_field($_GET['fieldValue']) : '';
        $field_id = isset($_GET['fieldId']) ? sanitize_text_field($_GET['fieldId']) : '';
        if (!check_ajax_referer( 'swpm-rego-form-ajax-nonce', 'nonce', false )) {
            echo '[ "' . esc_attr($field_id) .  '",false, "'.esc_html__( 'Nonce check failed. Please reload the page.', 'simple-membership' ).'" ]' ;
            exit;
        }
        //Addons can answer this check themselves (for example when the username is generated automatically):
        //return true (available) or false (not available) to skip the checks below, or null to keep them.
        $override = apply_filters( 'swpm_validate_user_name_ajax_override', null, $field_value, $field_id );
        if ( null !== $override ) {
            echo '[ "' . esc_attr($field_id) . ( $override ? '",true,"&radic;&nbsp;'.esc_html__( 'Available', 'simple-membership' ). '"]' : '",false,"&chi;&nbsp;'. esc_html__( 'Already taken', 'simple-membership' ). '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values are escaped; markup is a fixed JSON response for the validation engine.
            exit;
        }
        if (!SwpmMemberUtils::is_valid_user_name($field_value)){
            echo '[ "' . esc_attr($field_id) . '",false,"&chi;&nbsp;'. esc_html__( 'Name contains invalid character', 'simple-membership' ). '"]';
            exit;
        }
        $table = $wpdb->prefix . "swpm_members_tbl";
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}swpm_members_tbl WHERE user_name = %s", $field_value)) > 0;
        echo '[ "' . esc_attr($field_id) . (($exists) ? '",false,"&chi;&nbsp;'. esc_html__( 'Already taken', 'simple-membership' ). '"]' : // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values are escaped; markup is a fixed JSON response for the validation engine.
            '",true,"&radic;&nbsp;'.esc_html__( 'Available', 'simple-membership' ). '"]');
        exit;
    }

	public static function old_stripe_api_notice_dismiss() {
		if (!check_admin_referer( 'swpm_admin_script_nonce' )) {
			wp_send_json_error(
				array(
					'message' => __('Nonce verification failed.', 'simple-membership'),
				)
			);
		}

		delete_option( 'swpm_stripe_received_api_old_version' );

		wp_send_json_success(
			array(
				'message' => __('Notice dismissed successfully!', 'simple-membership'),
			)
		);
	}

}
