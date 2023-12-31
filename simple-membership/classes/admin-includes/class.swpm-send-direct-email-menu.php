<?php

class SWPM_Send_Direct_Email_Menu{

    public function __construct() {

    }
    
	public function handle_send_direct_email_menu()
	{
		//Check current_user_can() or die.
		SwpmMiscUtils::check_user_permission_and_is_admin('Send Direct Email Admin Menu');

		//Triger action hook
        do_action('swpm_send_direct_email_menu_start');

		// Get saved data
		$settings = SwpmSettings::get_instance();
		$send_email_menu_data = $settings->get_value('send_email_menu_data');
		if (empty($send_email_menu_data)) {
			// No saved data found. initialize the array.
			$send_email_menu_data = array();
		}

		// Get WP user's info
		$logged_in_user = wp_get_current_user();
		$logged_in_user_email = isset($logged_in_user->user_email) ? $logged_in_user->user_email : '';
		$logged_in_user_name = isset($logged_in_user->user_login) ? $logged_in_user->user_login : '';
		// $logged_in_user_id = $logged_in_user->ID;		

		if (isset($_POST['send_email_submit']) || isset($_POST['list_recipient_list']) ) {
			$swpm_send_email_nonce = filter_input(INPUT_POST, 'swpm_send_email_nonce');
			if (!wp_verify_nonce($swpm_send_email_nonce, 'swpm_send_email_nonce_action')) {
				// Nonce check failed.
				wp_die(__('Error! Nonce security verification failed for Bulk Change Membership Level action. Clear cache and try again.', 'simple-membership'));
			}
			$target_recipients = isset( $_POST['send_email_menu_target_recipients'] ) ? sanitize_text_field($_POST['send_email_menu_target_recipients']) : 'membership_level';
			$send_email_menu_data['send_email_selected_target_recipients'] = $target_recipients;
			$send_email_menu_data['send_email_membership_level'] = (isset($_POST['send_email_membership_level']) && $_POST['send_email_membership_level'] != '' && $target_recipients === 'membership_level') ? sanitize_text_field($_POST['send_email_membership_level']) : '';
			$send_email_menu_data['send_email_account_state'] = (isset($_POST['send_email_account_state']) && $_POST['send_email_account_state'] != '' && $target_recipients === 'membership_level') ? sanitize_text_field($_POST['send_email_account_state']) : '';
			$send_email_menu_data['send_email_copy_author'] = (isset($_POST['send_email_copy_author']) && $_POST['send_email_copy_author'] === 'on') ? 'on' : '';
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

			$found_logged_in_user_in_receipients = false; // Set true if if logged in user is in the recipient list.
			$list_of_recipients_to_display = array();

			// Validate recipients
			if ( $target_recipients === 'membership_level' && !empty($_POST['send_email_membership_level'])) {
				// send mail to specified members by membership level and account status.
				$members = SwpmMemberUtils::get_all_members_of_a_level_and_a_state(sanitize_text_field($_POST['send_email_membership_level']), sanitize_text_field($_POST['send_email_account_state']));
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
				$error_msg_array[] = __('No recipient selected. Please select email recipient(s).', 'simple-membership');
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
					
					if($recipient->email == $logged_in_user_email) { 
						$found_logged_in_user_in_receipients = true; // Added by Dennis. No need to copy author
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

					if ( isset($_POST['list_recipient_list']))  {
						// Get the membership level name
						$membership_level_name = SwpmMembershipLevelUtils::get_membership_level_name_of_a_member($recipient->member_id);
						$list_of_recipients_to_display[] = array(
							'id' => $recipient->member_id,
							'user_name' => $recipient->user_name,
							'email' => $recipient->email,
							'membership_level' => $membership_level_name,
							'account_state' => $recipient->account_state ? SwpmUtils::get_account_state_options()[$recipient->account_state] : 'N/A',
						);
					}
					else {
						// Send the Emails
						wp_mail($recipient->email, $subject , $body, $headers);
						SwpmLog::log_simple_debug( 'Sending direct email. Email sent to : '.$recipient->email, true );
 				     }

				} // end of foreach loop

				// Check if need to include currently logged in user as recipient.
				// If the author of the emails is not in the selected recipients, but a copy is requested, then send a copy.
			    if(	$found_logged_in_user_in_receipients == false && isset($_POST['send_email_copy_author']) && sanitize_text_field($_POST['send_email_copy_author']) === 'on' ){
					if ( isset($_POST['list_recipient_list']))  {
						$list_of_recipients_to_display[] = array(
							'id' => 'N/A',
							'user_name' => $logged_in_user_name,
							'email' => $logged_in_user_email,
							'membership_level' => 'N/A',
							'account_state' => 'N/A',
						);
					}
					else {
						// add a warning message that this is a sample email at the top of the email body to prevent confusion
						$authors_copy_warning_message = __('Note: Below is a sample of the email content sent to the user.', 'simple-membership');
						$body = $authors_copy_warning_message . "\n-----------------------------\n\n" . $body; 
						wp_mail($logged_in_user_email, $subject , $body, $headers);
						SwpmLog::log_simple_debug( '[Send Me a Copy] Sending email to logged in WordPress user: '.$logged_in_user_email, true );
 				     }
				}

				if( isset($_POST['list_recipient_list']) ) {
					$this->output_recipient_list_table($list_of_recipients_to_display);
				}
				else {
					echo '<div id="response-message" class="updated fade"><p>';
					 _e('Email Sent Successfully!', 'simple-membership');
					echo '</p></div>';
			    }

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
		$this->display_send_direct_email_admin_menu();
	}
	
	private function display_send_direct_email_admin_menu(){		
		$settings = SwpmSettings::get_instance();
		$send_email_menu_data = $settings->get_value('send_email_menu_data');

		$send_email_selected_target_recipients = isset( $send_email_menu_data['send_email_selected_target_recipients'] ) ? sanitize_text_field($send_email_menu_data['send_email_selected_target_recipients']) : 'membership_level';
		$send_email_recipient_membership_level = isset( $send_email_menu_data['send_email_membership_level'] ) ? sanitize_text_field( $send_email_menu_data['send_email_membership_level'] ) : '';
		$send_email_recipient_members_id = isset( $send_email_menu_data['send_email_members_id'] ) ? sanitize_text_field( $send_email_menu_data['send_email_members_id'] ) : '';
		$send_email_recipient_account_state = isset( $send_email_menu_data['send_email_account_state'] ) ? sanitize_text_field( $send_email_menu_data['send_email_account_state'] ) : '';
		$send_email_copy_author = isset( $send_email_menu_data['send_email_copy_author'] ) && sanitize_text_field( $send_email_menu_data['send_email_copy_author'] ) === 'on' ? 'checked="checked"' : '';
		$send_email_enable_html = isset( $send_email_menu_data['send_email_enable_html'] ) && sanitize_text_field( $send_email_menu_data['send_email_enable_html'] ) === 'on' ? 'checked="checked"' : '';
		$send_email_subject = isset( $send_email_menu_data['send_email_subject'] ) ? sanitize_text_field( $send_email_menu_data['send_email_subject'] ) : '';
		$send_email_body = isset( $send_email_menu_data['send_email_body'] ) ? wp_kses_post( $send_email_menu_data['send_email_body'] ) : '';
		
		// Get WP user's info
		$logged_in_user = wp_get_current_user();
		$logged_in_user_email = isset($logged_in_user->user_email) ? $logged_in_user->user_email : '';
		?>

		<div id="poststuff">
			<div id="post-body">
				<div class="postbox">
					<h3 class="hndle"><label
							for="title"><?php _e( 'Send Direct Email to Members', 'simple-membership' ); ?></label></h3>
					<div class="inside">
						<div class="swpm-grey-box">
						<p>
							<?php _e( 'This feature allows you to send emails to a group of members based on their membership level and account status or by individual member IDs.', 'simple-membership' ); ?>
							<?php _e( 'Refer to <a href="https://simple-membership-plugin.com/send-a-quick-notification-email-to-your-members/" target="_blank">this documentation page</a> for more details.', 'simple-membership' ); ?>
						</p>
						</div>
						<form method="post" action="" class="form-table">
							<table width="100%" border="0" cellspacing="0" cellpadding="6">
								<tbody>
								<tr valign="top">
									<th width="25%" align="left"><?php _e( 'Target Recipients', 'simple-membership' ); ?></th>
									<td align="left">
										<div>
											<label>
												<input type="radio" id="target-recipients-option-1" name="send_email_menu_target_recipients" value="membership_level" <?php echo $send_email_selected_target_recipients === "membership_level" ? 'checked' : '';?>>
												<?php _e( 'Send to Membership Level & Account Status', 'simple-membership' );?>
											</label>
											<label style="margin-left: 12px">
												<input type="radio" id="target-recipients-option-2" name="send_email_menu_target_recipients" value="members_id" <?php echo $send_email_selected_target_recipients === "members_id" ? 'checked' : '';?>>
												<?php _e( 'Send to Member IDs', 'simple-membership' ); ?>
											</label>
										</div>
										<div style="margin-top: 14px">
											<div id="send-email-field-membership-level" style="<?php echo $send_email_selected_target_recipients == 'members_id' ? 'display: none' : ''; ?>">  
												<select name="send_email_membership_level">
													<option value=""> <?php _e( 'Select a Level', 'simple-membership' ); ?></option>
													<?php echo SwpmUtils::membership_level_dropdown( esc_attr($send_email_recipient_membership_level)); ?>
													<option value="-1" <?php echo ($send_email_recipient_membership_level == -1 ? 'selected="selected"' : ''); ?>><?php _e( 'All Levels', 'simple-membership' ); ?></option>
												</select>
												<p class="description"><?php _e( 'Choose the membership level for email recipients.', 'simple-membership' ); ?></p>
												<br>
											</div>
											<div id="send-email-field-account-state" style="<?php echo $send_email_selected_target_recipients == 'members_id' ? 'display: none' : ''; ?>">
												<select name="send_email_account_state">
													<option value=""> <?php _e( 'Select Account Status', 'simple-membership' ); ?></option>
													<?php echo SwpmUtils::account_state_dropdown( esc_attr($send_email_recipient_account_state), true );  ?>
													<option value="all" <?php echo ($send_email_recipient_account_state == 'all' ? 'selected="selected"' : ''); ?>><?php _e( 'All Status', 'simple-membership' ); ?></option>
												</select>
												<p class="description"><?php _e( 'Choose the account status for email recipients.', 'simple-membership' ); ?></p>
												<br>
											</div>
											<div id="send-email-field-members-id" style="<?php echo $send_email_selected_target_recipients !== 'members_id' ? 'display: none' : ''; ?>">  
												<input type="text" name="send_email_members_id" size="50" value="<?php echo esc_attr($send_email_recipient_members_id);?>" />
												<p class="description"><?php _e( "Enter member IDs separated by comma to specify the recipients.", "simple-membership" ); ?></p>
												<br>
											</div>
											<div id="send-email-copy-author-checkbox">
												<label>			
													<input type="checkbox" name="send_email_copy_author" <?php echo esc_attr( $send_email_copy_author); ?> />
													<?php _e( 'Also Send Me a Copy', 'simple-membership' ); ?> 
												</label>
												<p class="description">
													<?php 
													_e('Check this if you want to send a copy of the email to your own email address', 'simple-membership');
													echo ' (';
													_e('Your current WP user account email address is: ', 'simple-membership');
													echo (isset($logged_in_user_email) ? $logged_in_user_email : '');
													echo ').';
													?>
												</p>
											</div>
										</div>
									</td>
								</tr>

								<tr valign="top">
									<th width="25%" align="left">
										<?php _e( 'Email Subject', 'simple-membership' ); ?>
									</th>
									<td align="left">
										<input type="text" name="send_email_subject" size="50" value="<?php echo esc_attr( $send_email_subject ); ?>" />
										<p class="description"><?php _e( 'Enter the subject for the email.', 'simple-membership' ); ?></p>
									</td>
								</tr>

								<tr valign="top" id="send-email-html">
									<th width="25%" align="left">
										<?php _e( 'Allow HTML', 'simple-membership' ); ?>
									</th>
									<td align="left">
										<input type="checkbox" name="send_email_enable_html" <?php echo esc_attr( $send_email_enable_html ); ?> />
										<p class="description"> <?php _e( 'Enables HTML support in the email. For optimal email delivery rate, we suggest using plain text (non-HTML) email.', 'simple-membership' ); ?> </p>
									</td>	
								</tr>

								<tr valign="top">
									<th width="25%" align="left">
										<?php _e( 'Email Body', 'simple-membership' ); ?>
									</th>
									<td align="left">
										<?php
										$send_email_body_settings = array(
											'textarea_name'  => 'send_email_body',
											'teeny'          => true,
											'default_editor' => ! empty( $send_email_enable_html ) ? 'QuickTags' : '',
											'textarea_rows'  => 15,
										);
										//Trigger a filter to allow plugins/addons to modify the editor settings.
										$send_email_body_settings = apply_filters( 'swpm_send_direct_email_body_settings', $send_email_body_settings );
										//Render the editor
										wp_editor( wp_kses_post( $send_email_body ), 'send_email_body', $send_email_body_settings );
										?>
										<p class="description">
											<?php _e( 'Enter the email content that will be sent to members. You can utilize the following email merge tags in this message (click to copy tags to clipboard).', 'simple-membership' ); ?>
										</p>
										<ul class="description">
											<?php foreach ( SwpmUtils::email_merge_tags() as $tag => $desc ) { ?>
												<li>
													<span style="background-color: #eee; padding: 2px 4px; color: #6c6c6c; cursor: pointer;" onclick="copyMergeTag(event)" >{<?php echo $tag; ?>}</span> - <span> <?php echo $desc; ?> </span>
												</li>
											<?php } ?>
										</ul>
										<p class="description"><?php _e( 'Please note that the following merge tag does not work in this email.', 'simple-membership' ); ?></p>
										<ul clear="description">
											<li>
												<span style="background-color: #eee; padding: 2px 4px; color: #6c6c6c; cursor: pointer;">{password}</span> - 
												<span><?php _e( 'This tag will not work in this email since the password is stored in the database using a one-way hash, which means that the plugin cannot retrieve the plain text password once the account has been created.', 'simple-membership' ); ?></span>
											</li>
										</ul>
									</td>
									</td>
								</tr>

								<tr valign="top">
									<th width="25%" align="left">
									</th>
									<td align="left">
										<input type="submit" class="button-primary" name="send_email_submit" value="<?php _e( 'Send Direct Email', 'simple-membership' ); ?>" />
										<p class="description"><?php _e('This option will send the email if there are no validation errors.', 'simple-membership');?></p>
									</td>
								</tr>

								<tr valign="top">
									<th width="25%" align="left">
									</th>
									<td align="left">
										<input type="submit" class="button-secondary" name="list_recipient_list" value="<?php _e( 'View Recipient List', 'simple-membership' ); ?>"/>
										<p class="description"><?php _e('You can use this option to display a list of selected members as recipients for cross-checking email addresses before sending.', 'simple-membership');?></p>
									</td>
								</tr>

								<input type="hidden" name="swpm_send_email_nonce" value="<?php echo wp_create_nonce( 'swpm_send_email_nonce_action' ); ?>" />
								</tbody>
							</table>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
			const recipientGroupRadio = document.querySelectorAll('input[name="send_email_menu_target_recipients"]');
			const membershipLevelField = document.getElementById('send-email-field-membership-level');
			const membershipAccountField = document.getElementById('send-email-field-account-state');
			const membersIdField = document.getElementById('send-email-field-members-id');

			// Add event listener to each radio button
			recipientGroupRadio.forEach((item) => {
				item.addEventListener('change', toggleRecipientFieldType);
			});

			function toggleRecipientFieldType(event) {
				if (event.target.value === 'members_id') {
					membershipLevelField.style.display = 'none';
					membershipAccountField.style.display = 'none';
					membersIdField.style.display = null;
				} else {
					membershipLevelField.style.display = null;
					membershipAccountField.style.display = null;
					membersIdField.style.display = 'none';
				}
			}

			function copyMergeTag(event) {
				const element = event.target;
				const text = element.innerText;
				const range = document.createRange();
				range.selectNodeContents(element);
				const selection = window.getSelection();
				navigator.clipboard.writeText(text);
				selection.removeAllRanges();
				selection.addRange(range);
			}
		</script>
		<?php
	}

	private function output_recipient_list_table($recipient_list){
		$recipients_count = count($recipient_list);
		echo '<div class="postbox" style="margin:10px 0px 0px">';
		echo '<div class="inside">';
		echo '<h3 style="font-size: 14px;">'. __('Email Recipient List', 'simple-membership'). '</h3>' ;
		// echo '<div style="max-height: 300px; overflow: scroll;">'; // table wrap
		echo '<table class="widefat">';
		echo '<thead>
				<tr>
					<th style="padding-bottom: 4px;">'.__('Recipient\'s Email', 'simple-membership').'</th>
					<th style="padding-bottom: 4px;">'.__('Username', 'simple-membership').'</th>
					<th style="padding-bottom: 4px;">'.__('Member ID', 'simple-membership').'</th>
					<th style="padding-bottom: 4px;">'.__('Membership Level', 'simple-membership').'</th>
					<th style="padding-bottom: 4px;">'.__('Account State', 'simple-membership').'</th>
				</tr>
			</thead>';
		echo '<tbody>';
		foreach ($recipient_list as $recipient) {
			echo '<tr>
					<td>'.esc_attr($recipient['email']).'</td>
					<td>'.esc_attr($recipient['user_name']).'</td>
					<td>'.esc_attr($recipient['id']).'</td>
					<td>'.esc_attr($recipient['membership_level']).'</td>
					<td>'.esc_attr($recipient['account_state']).'</td>
				</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		// echo '</div>'; // end of table wrap
		echo '<p>' . __('Total email recipients: ','simple-membership') . $recipients_count . '</p>';
		echo '<p>'.__('Note: No emails have been sent; this is only a display of the recipient list.', 'simple-membership').'</p>';
		echo '</div>';
		echo '</div>';
	}

}
