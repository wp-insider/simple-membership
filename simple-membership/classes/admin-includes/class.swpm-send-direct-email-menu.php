<?php

class SWPM_Send_Direct_Email_Menu{

    public function __construct() {

    }
    
	public function handle_send_direct_email_menu()
	{
        do_action('swpm_send_direct_email_menu_start');

		$settings = SwpmSettings::get_instance();
		$send_email_menu_data = $settings->get_value('send_email_menu_data');
		if (empty($send_email_menu_data)) {
			// No saved data found. initialize the array.
			$send_email_menu_data = array();
		}

		if (isset($_POST['send_email_submit'])) {
			$swpm_send_email_nonce = filter_input(INPUT_POST, 'swpm_send_email_nonce');
			if (!wp_verify_nonce($swpm_send_email_nonce, 'swpm_send_email_nonce_action')) {
				// Nonce check failed.
				wp_die(__('Error! Nonce security verification failed for Bulk Change Membership Level action. Clear cache and try again.', 'simple-membership'));
			}
			$target_recipients = isset( $_POST['send_email_menu_target_recipients'] ) ? sanitize_text_field($_POST['send_email_menu_target_recipients']) : 'membership_level';
			$send_email_menu_data['send_email_selected_target_recipients'] = $target_recipients;
			$send_email_menu_data['send_email_membership_level'] = (isset($_POST['send_email_membership_level']) && $_POST['send_email_membership_level'] != '' && $target_recipients === 'membership_level') ? sanitize_text_field($_POST['send_email_membership_level']) : '';
			$send_email_menu_data['send_email_members_id'] = (isset($_POST['send_email_members_id']) && $_POST['send_email_members_id'] != '' && $target_recipients === 'members_id') ? sanitize_text_field($_POST['send_email_members_id']) : '';
			$send_email_menu_data['send_email_enable_html'] = (isset($_POST['send_email_enable_html']) && $_POST['send_email_enable_html'] === 'on') ? 'on' : '';
			$send_email_menu_data['send_email_subject'] = (isset($_POST['send_email_subject']) && $_POST['send_email_subject'] != '') ? sanitize_text_field($_POST['send_email_subject']) : '';
			$send_email_menu_data['send_email_body'] = (isset($_POST['send_email_body']) && $_POST['send_email_body'] != '') ? stripslashes(wp_kses_post($_POST['send_email_body'])) : '';

			// Save the values for future
			SwpmSettings::get_instance()->set_value('send_email_menu_data', $send_email_menu_data);
			SwpmSettings::get_instance()->save();

			$all_validations_passed = true;

			$error_msg_array = array();
			$recipients = array();

			// Validate recipients
			if ( $target_recipients === 'membership_level' && !empty($_POST['send_email_membership_level'])) {
				// send mail to specified members by membership level.
				$members = SwpmMemberUtils::get_all_members_of_a_level(sanitize_text_field($_POST['send_email_membership_level']));
				foreach ($members as $member) {
					$recipients[] = $member;
				}
			} elseif ( $target_recipients === 'members_id' && !empty($_POST['send_email_members_id'])) {
				// send mail to specified members by IDs
				$ids_str = sanitize_text_field(stripslashes($_POST['send_email_members_id']));
				$ids = explode(',', $ids_str);
				foreach ($ids as $id) {
					$member_id = (int)trim($id);
					if ($member_id) {
						$member = SwpmMemberUtils::get_user_by_id($member_id);
						if (!$member) {
							//echo '<br />Invalid ID. Continuing to the next item';
							continue;
						}
						//echo '<br />Adding member row to the recipients list';
						$recipients[] = $member;
					}
				}
			} else {
				$all_validations_passed = false;
				$error_msg_array[] = __('No recipient selected. Plaese select email recipeint(s).', 'simple-membership');
			}


			// Validate email subject
			if (empty ($send_email_menu_data['send_email_subject'])) {
				$all_validations_passed = false;
				$error_msg_array[] = __('The email subject field is empty. Please enter a value in the email subject field.', 'simple-membership');
			}

			// Validate email body
			if (empty ($send_email_menu_data['send_email_body'])) {
				$all_validations_passed = false;
				$error_msg_array[] = __('The email body field is empty. Please enter a value in the email body field.', 'simple-membership');
			}

			// Validate recipient list
			if (empty ($recipients)) {
				$all_validations_passed = false;
				$error_msg_array[] = __('The recipients list is currently empty. There must be at least one recipient.', 'simple-membership');
			}


			if ($all_validations_passed) {
				//All passed. Go ahead with the processing.
				foreach ($recipients as $recipient) {
					if( !isset( $recipient->email ) || empty( $recipient->email) ){
						SwpmLog::log_simple_debug( 'Error sending direct email! Email value is empty for this member record.', false );
						continue;
					}

					// Send the email
					$from_address = $settings->get_value( 'email-from' );
					$headers = 'From: ' . $from_address . "\r\n";

					$body = SwpmMiscUtils::replace_dynamic_tags($send_email_menu_data['send_email_body'], $recipient->member_id);
					$subject = $send_email_menu_data['send_email_subject'];

					//Check if HTML email checkbox enabled for the direct email option.
					if (!empty($send_email_menu_data['send_email_enable_html'])) {
						$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
						$body = nl2br($body);
					}
					wp_mail($recipient->email, $subject , $body, $headers);
					SwpmLog::log_simple_debug( 'Sending direct email. Email sent to : '.$recipient->email, true );
				}
				echo '<div id="response-message" class="updated fade"><p>';
				_e('Email Sent Successfully!', 'simple-membership');
				echo '</p></div>';

			} else {
				echo '<div id="response-message" class="updated error"><p>';
				_e('The following validation failed. Please correct it and try again.', 'simple-membership');
				echo '</p><ol>';
				foreach ($error_msg_array as $error_msg) {
					echo '<li>' . $error_msg . '</li>';
				}
				echo '</ol>';
				echo '</div>';
			}
		}

		// render view.
		include_once(SIMPLE_WP_MEMBERSHIP_PATH . "views/admin_send_direct_email_menu.php");
	}

}
