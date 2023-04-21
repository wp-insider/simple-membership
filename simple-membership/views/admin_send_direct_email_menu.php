<?php
$send_email_selected_target_recipients = isset( $send_email_menu_data['send_email_selected_target_recipients'] ) ? sanitize_text_field($send_email_menu_data['send_email_selected_target_recipients']) : 'membership_level';
$send_email_recipient_membership_level = isset( $send_email_menu_data['send_email_membership_level'] ) ? sanitize_text_field( $send_email_menu_data['send_email_membership_level'] ) : 0;
$send_email_recipient_members_id = isset( $send_email_menu_data['send_email_members_id'] ) ? sanitize_text_field( $send_email_menu_data['send_email_members_id'] ) : '';
$send_email_enable_html = isset( $send_email_menu_data['send_email_enable_html'] ) && sanitize_text_field( $send_email_menu_data['send_email_enable_html'] ) === 'on' ? 'checked="checked"' : '';
$send_email_subject = isset( $send_email_menu_data['send_email_subject'] ) ? sanitize_text_field( $send_email_menu_data['send_email_subject'] ) : '';
$send_email_body = isset( $send_email_menu_data['send_email_body'] ) ? wp_kses_post( $send_email_menu_data['send_email_body'] ) : '';
?>

<div id="poststuff">
	<div id="post-body">
		<div class="postbox">
			<h3 class="hndle"><label
					for="title"><?php _e( 'Send Direct Email to Members', 'simple-membership' ); ?></label></h3>
			<div class="inside">
				<div class="swpm-grey-box">
				<p>
					<?php _e( 'This feature allows you to send emails to a group of members based on their membership level or individual member IDs.', 'simple-membership' ); ?>
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
										<?php _e( 'Send to Membership Level', 'simple-membership' ); ?>
									</label>
									<label style="margin-left: 12px">
										<input type="radio" id="target-recipients-option-2" name="send_email_menu_target_recipients" value="members_id" <?php echo $send_email_selected_target_recipients === "members_id" ? 'checked' : '';?>>
										<?php _e( 'Send to Member IDs', 'simple-membership' ); ?>
									</label>
								</div>
								<div style="margin-top: 14px">
									<div id="send-email-field-membership-level" style="<?php echo $send_email_selected_target_recipients !== 'membership_level' ? 'display: none' : ''; ?>">
										<select name="send_email_membership_level">
											<option
												value=""><?php _e( 'Select a level', 'simple-membership' ); ?></option>
											<?php echo SwpmUtils::membership_level_dropdown( esc_attr($send_email_recipient_membership_level)); ?>
										</select>
										<p class="description"><?php _e( 'Choose the membership level for email recipients.', 'simple-membership' ); ?></p>
									</div>
									<div id="send-email-field-members-id" style="<?php echo $send_email_selected_target_recipients !== 'members_id' ? 'display: none' : ''; ?>">
										<input type="text" name="send_email_members_id" size="50" value="<?php echo esc_attr($send_email_recipient_members_id);?>" />
										<p class="description"><?php _e( "Enter member IDs separated by comma to specify the recipients.", "simple-membership" ); ?></p>
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
	const membersIdField = document.getElementById('send-email-field-members-id');
	// Add event listener to each radio button
	recipientGroupRadio.forEach((item) => {
		item.addEventListener('change', toggleRecipientFieldType);
	});

	function toggleRecipientFieldType(event) {
		if (event.target.value === 'members_id') {
			membershipLevelField.style.display = 'none';
			membersIdField.style.display = null;
		} else {
			membershipLevelField.style.display = null;
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
