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
            echo '[ "' . esc_attr($field_id) .  '",false, "'.SwpmUtils::_('Nonce check failed. Please reload the page.').'" ]' ;
            exit;
        }
        if (!is_email($field_value)){
            echo '[ "' . esc_attr($field_id) .  '",false, "'.SwpmUtils::_('Invalid Email Address').'" ]' ;
            exit;
        }
        $table = $wpdb->prefix . "swpm_members_tbl";
        $query = $wpdb->prepare("SELECT member_id FROM $table WHERE email = %s AND user_name != ''", $field_value);
        $db_id = $wpdb->get_var($query) ;
        $exists = ($db_id > 0) && $db_id != $member_id;
        echo '[ "' . esc_attr($field_id) . (($exists) ? '",false, "&chi;&nbsp;'.SwpmUtils::_('Already taken').'"]' : '",true, "&radic;&nbsp;'.SwpmUtils::_('Available'). '"]');
        exit;
    }

    public static function validate_user_name_ajax() {
        global $wpdb;
        $field_value = isset($_GET['fieldValue']) ? sanitize_text_field($_GET['fieldValue']) : '';
        $field_id = isset($_GET['fieldId']) ? sanitize_text_field($_GET['fieldId']) : '';
        if (!check_ajax_referer( 'swpm-rego-form-ajax-nonce', 'nonce', false )) {
            echo '[ "' . esc_attr($field_id) .  '",false, "'.SwpmUtils::_('Nonce check failed. Please reload the page.').'" ]' ;
            exit;
        }
        //Addons can answer this check themselves (for example when the username is generated automatically):
        //return true (available) or false (not available) to skip the checks below, or null to keep them.
        $override = apply_filters( 'swpm_validate_user_name_ajax_override', null, $field_value, $field_id );
        if ( null !== $override ) {
            echo '[ "' . esc_attr($field_id) . ( $override ? '",true,"&radic;&nbsp;'.SwpmUtils::_('Available'). '"]' : '",false,"&chi;&nbsp;'. SwpmUtils::_('Already taken'). '"]' );
            exit;
        }
        if (!SwpmMemberUtils::is_valid_user_name($field_value)){
            echo '[ "' . esc_attr($field_id) . '",false,"&chi;&nbsp;'. SwpmUtils::_('Name contains invalid character'). '"]';
            exit;
        }
        $table = $wpdb->prefix . "swpm_members_tbl";
        $query = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_name = %s", $field_value);
        $exists = $wpdb->get_var($query) > 0;
        echo '[ "' . esc_attr($field_id) . (($exists) ? '",false,"&chi;&nbsp;'. SwpmUtils::_('Already taken'). '"]' :
            '",true,"&radic;&nbsp;'.SwpmUtils::_('Available'). '"]');
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
