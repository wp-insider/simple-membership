<?php

$send_email_enable_html = isset( $send_email_menu_data['send_email_enable_html'] ) && esc_attr( $send_email_menu_data['send_email_enable_html'] ) === 'on' ? 'checked="checked"' : '';
$send_email_subject     = isset( $send_email_menu_data['send_email_subject'] ) ? esc_attr( $send_email_menu_data['send_email_subject'] ) : '';
$send_email_body        = isset( $send_email_menu_data['send_email_body'] ) ? wp_kses_post( $send_email_menu_data['send_email_body'] ) : '';

?>

<div id="poststuff">
	<div id="post-body">
		<div class="postbox">
			<h3 class="hndle"><label
					for="title"><?php _e( 'Send Email to Members', 'simple-membership' ); ?></label></h3>
			<div class="inside">
				<p><?php _e( 'Use the form to send email to group of members by membership level or by IDs of individual members.', 'simple-membership' ); ?></p>
				<form method="post" action="" class="form-table">
					<table width="100%" border="0" cellspacing="0" cellpadding="6">
						<tbody>
						<tr valign="top">
							<th width="25%"
								align="left"><?php _e( 'Target Recipients', 'simple-membership' ); ?></th>
							<td align="left">
								<div>
									<label>
										<input type="radio"
											   id="target-recipients-option-1"
											   name="send_email_menu_target_recipients"
											   value="membership_level"
											   checked>
										<?php _e( 'Particular Membership level', 'simple-membership' ); ?>
									</label>
									<label style="margin-left: 12px">
										<input type="radio"
											   id="target-recipients-option-2"
											   name="send_email_menu_target_recipients"
											   value="members_id">
										<?php _e( 'Individual Members', 'simple-membership' ); ?>
									</label>
								</div>
								<div style="margin-top: 14px">
									<div id="send-email-field-membership-level">
										<select name="send_email_membership_level">
											<option
												value=""><?php _e( 'Select a level', 'simple-membership' ); ?></option>
											<?php echo SwpmUtils::membership_level_dropdown(); ?>
										</select>
										<p class="description"><?php _e( 'Select the membership level to whom the mail will be send to.', 'simple-membership' ); ?></p>
									</div>
									<div id="send-email-field-members-id" style="display: none">
										<input type="text" name="send_email_members_id" size="50"
											   value=""/>
										<p class="description"><?php _e( 'Enter member\'s ID separated by comma, to whom the mail will be send to.', 'simple-membership' ); ?></p>
									</div>
								</div>
							</td>
						</tr>

						<tr valign="top">
							<th width="25%" align="left">
								<?php _e( 'Email Subject', 'simple-membership' ); ?>
							</th>
							<td align="left">
								<input type="text" name="send_email_subject" size="50"
									   value="<?php echo esc_attr( $send_email_subject ); ?>"
									   style="max-width: 100%;"/>
								<p class="description"><?php _e( 'Enter email subject', 'simple-membership' ); ?></p>
							</td>
						</tr>

						<tr valign="top" id="send-email-html">
							<th width="25%" align="left">
								<?php _e( 'Allow HTML', 'simple-membership' ); ?>
							</th>
							<td align="left">
								<input type="checkbox"
									   name="send_email_enable_html" <?php echo esc_attr( $send_email_enable_html ); ?> />
								<p class="description"> <?php _e( 'Enables HTML support in emails. We recommend using plain text (non HTML) email as it has better email delivery rate.', 'simple-membership' ); ?> </p>
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
								<p class="description">Specify the email body that will be sent to the members.
									You can use the following email merge tags in this email (Click to copy the tags to
									clipboard):</p>
								<ul class="description">
									<?php foreach ( SwpmUtils::email_merge_tags() as $tag => $desc ) { ?>
										<li><span style="
														background-color: #eee;
														padding: 2px 4px;
														color: #6c6c6c;
														cursor: pointer;"
												  onclick="copyMergeTag(event)"
											>{<?php echo $tag; ?>}</span> - <span> <?php echo $desc; ?> </span>
										</li>
									<?php } ?>
								</ul>
								<p class="description"><?php _e( 'Please note that the following merge tag does not work in for this email.', 'simple-membership' ); ?></p>
								<ul clear="description">
									<li><span style="
														background-color: #eee;
														padding: 2px 4px;
														color: #6c6c6c;
														cursor: pointer;">{password}</span> -
										<span> This won't work </span>
									</li>
								</ul>
							</td>
							</td>
						</tr>

						<tr valign="top">
							<th width="25%" align="left">
							</th>
							<td align="left">
								<input type="submit" class="button-primary" name="send_email_submit"
									   value="<?php _e( 'Send Email', 'simple-membership' ); ?>"/>
							</td>
						</tr>
						<input type="hidden" name="swpm_send_email_nonce"
							   value="<?php echo wp_create_nonce( 'swpm_send_email_nonce_action' ); ?>"/>
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
