=== Simple Membership ===
Contributors: smp7, wp.insider
Donate link: https://simple-membership-plugin.com/
Tags: member, members, members only, membership, memberships, register, WordPress membership plugin, content, content protection, paypal, restrict, restrict access, Restrict content, admin, access control, subscription, teaser, protection, profile, login, bbpress, stripe
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 4.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple membership plugin adds membership functionality to your site. Protect members only content using content protection easily.

== Description ==

= A flexible, well-supported, and easy-to-use WordPress membership plugin for offering free and premium content from your WordPress site =

The simple membership plugin lets you protect your posts and pages so only your members can view the protected content.

= Unlimited Membership Access Levels =
Set up unlimited membership levels (example: free, silver, gold etc) and protect your posts and pages using the membership levels you create.

= User Friendly Interface for Content Protection =
When you are editing a post or page in the WordPress editor, you can select to protect that post or page for your members.

Non-members viewing a protected page will be prompted to log in or become a member.

= Have Free and Paid Memberships =
You can configure it to have free and/or paid memberships on your site. Paid membership payment is handled securely via PayPal. Membership payment can also be accepted using Stripe or Braintree payment gateways.

Both one time and recurring/subscription payments are supported for PayPal and Stripe.

You can accept one time membership payment via Braintree payment gateway.

Option to make membership payment buttons using the new PayPal Checkout API.

There is also option to use PayPal smart buttons for membership payment.

You can enable email activation or email confirmation for the free memberships.

= Membership Payments Log =
All the payments from your members are recorded in the plugin. You can view them anytime by visiting the payments menu from the admin dashboard.

= Developer API =

There are lots of action and filter hooks that a developer can use to customize the plugin.

There is also an API that can be used to query, create, update member accounts.

= Member Login Widget on The Sidebar =
You can easily add a member login widget on the sidebar of your site. Simply use the login form shortcode in the sidebar widget.

You can also customize the member login widget by creating a custom template file in your theme (or child theme) folder.

Option to show a password visibility toggle option in the login form.

= Documentation =

Read the [setup documentation](https://simple-membership-plugin.com/simple-membership-documentation/) after you install the plugin to get started.

= Plugin Support =

If you encounter any issues with this plugin, please visit our website to post on the support forum or contact us directly.
https://simple-membership-plugin.com/

You can create a free forum account to ask your questions.

= Additional Features =

* Works with any WordPress theme.
* Ability to protect photo galleries.
* Ability to protect attachment pages.
* Show teaser content to convert visitors into members.
* Comments on your protected posts will also be protected automatically.
* There is an option to enable debug logging so you can troubleshoot membership payment related issues easily (if any).
* Ability to customize the content protection message that gets shown to non-members.
* Ability to partially protect post or page content.
* You can apply protection to posts and pages in bulk.
* Ability to use merge vars in the membership email notification.
* Membership management side is handled by the plugin.
* Ability to manually approve your members.
* Ability to import WordPress users as members.
* Search for a member's profile in your WP admin dashboard.
* Filter members list by account status.
* Filter members list by membership level.
* Site admins can save private notes about members, providing a convenient way to keep track of important information.
* Can be translated to any language.
* Hide the admin toolbar from the frontend of your site.
* Allow your members to delete their membership accounts.
* Send quick notification email to your members.
* Email all members by membership level, with an option to filter by account status.
* Customize the password reset email for members.
* Use Google reCAPTCHA on your member registration form.
* Use Google reCAPTCHA on your member login and password reset form.
* The login and registration widgets will be responsive if you are using a responsive theme.
* Ability to restrict the commenting feature on your site to your members only.
* Front-end member registration page.
* Front-end member profiles.
* Front-end member login page.
* Option to configure after login redirection for members.
* Option to configure after registration redirect for members.
* Option to configure after logout redirection for members.
* Option force the members to use strong password.
* Option to make the users agree to your terms and conditions before they can register for a member account.
* Option to make the users agree to your privacy policy before they can register for a member account.
* Option to hide the registration/join option. Useful if you prefer visitors not to see the registration option on your site.
* Option to automatically logout the members when they close the browser.
* Ability to forward the payment notification to an external URL for further processing.
* Option to configure whitelisting for user email addresses to allow registration only from specific email addresses or email domains.
* Option to configure blacklisting for user email addresses to block registration from certain email addresses or email domains.
* Allows you to set an active login limit for members, helping to prevent account sharing.
* Option to enable and set failed login attempt limit, which helps to protect against brute force attacks.
* Option to enable login event tracking, allowing you to view the history of member logins.
* Option to reset the settings and data of the plugin to start fresh.
* Option to configure PayPal payment buttons for memberships (one-time and recurring payments).
* Option to configure Stripe payment buttons for memberships (one-time and recurring payments).
* Option to configure Braintree payment buttons for memberships (one-time payments).
* Free Social Login addon that lets users log in with their Google or Facebook accounts.
* The plugin is actively maintained and we are working on new features for the plugin.
* Browse the [plugin documentation](https://simple-membership-plugin.com/simple-membership-documentation/) to learn more about the features of this plugin.

= Language Translations =

The following language translations are already available:

* English
* German
* French
* Spanish
* Spanish (Venezuela)
* Chinese
* Portuguese (Brazil)
* Portuguese (Portugal)
* Swedish
* Macedonian
* Polish
* Turkish
* Russian
* Dutch (Netherlands)
* Dutch (Belgium)
* Romanian
* Danish
* Lithuanian
* Serbian
* Japanese
* Greek
* Latvian
* Indonesian
* Hebrew
* Catalan
* Hungarian
* Bosnian (Bosnia and Herzegovina)
* Slovak
* Italian
* Norwegian
* Mexican
* Arabic
* Czech
* Finnish

You can translate the plugin using the language [translation documentation](https://simple-membership-plugin.com/translate-simple-membership-plugin/).

== Installation ==

Do the following to install the membership plugin:

1. Upload the 'simple-membership.zip' file from the Plugins -> Add New page in the WordPress administration panel.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Where can I find complete documentation for this plugin? =
You can find the full documentation for this plugin on the [Simple Membership plugin documentation](https://simple-membership-plugin.com/simple-membership-documentation/) page.

== Screenshots ==
1. Example of the member registration page.
2. Example of the member login page.
3. Example of the membership payment landing page.
4. Example of the settings menu.
5. Example of membership level management.

== Changelog ==

= 4.7.0 =
- Improved the validation JavaScript for the Add/Edit Membership Level screen to simplify the interface.
- Added extra validation checks to the front-end profile update process to improve security.

= 4.6.9 =
- Added a new {expiry_date} email merge tag.
- Bulk account activate notification email body now supports email merge tags.
- Spanish language translation files updated.
- Added center text align CSS to the WooCommerce protection message.
- Romanian Leu currency can now be used for stripe buy now buttons.

= 4.6.8 =
- Added a new free social login addon to allow users to log in using their social media accounts. Refer to [this documentation](https://simple-membership-plugin.com/simple-membership-social-login-addon/) for more information.
- Added a payment button shortcode field in the button edit interface for easy copy-and-paste.
- Introduced a new filter hook 'swpm_after_login_form_output' to display custom content below the login form on the login page.
- Updated the French language translation file. Thanks to David Ramery for the update.
- Updated the code to remove a minor PHP notice issue.
- Improved the WooCommerce page protection message HTML.
- Enhanced the date selector UI with a button to quickly return to the current month.
- Added a new filter hook 'swpm_edit_profile_form_before_submit' to allow custom code to display content before the submit button in the profile form of admin dashboard.

= 4.6.7 =
- Improved the renewal logic to include a check for account inactivity so access starts date is calculated correctly.
- WooCommerce product pages can now be protected using the standard content protection feature.
- Added a new email settings section for the manual approval notification email.
- Added a button in the Edit/View Member profile page to let admins easily approve accounts using the manual approval feature.
- Fixed an issue with the Stripe button when using a custom button image.

= 4.6.6 =
- Added a new filter hook 'swpm_override_login_limit' to allow custom code to override the active login limit check.
- The default subject for the account upgrade email has been changed to "Account Upgrade Confirmation Email" for clarity.
- The default subject for the account renewal email has been changed to "Account Renewal Confirmation Email" for clarity.
- Added debug logging for after logout redirection URL.
- Changed the call to wp_logout() function to use a custom logout method to prevent the 'wp_logout' action hook from being triggered, which causes our plugin's after logout redirection to not work properly.
- Added a new action hook 'swpm_wp_user_logout_complete' that is triggered after the wp_destroy_current_session() and wp_clear_auth_cookie() functions are called in the custom logout method.
- The plugin now hooks to the 'swpm_after_logout_function_executed' hook instead of the 'swpm_logout' hook to handle after logout tasks. This allows for better clarity with the action name.
- Fixed the description of the "Disable Access to WP Dashboard" setting.
- Added new filter hook 'swpm_email_prompt_to_complete_registration_body' to allow custom code to modify the body of the email sent to members prompting them to complete their registration.
- Added new function 'swpm_dummy_country_names_for_translation' to include dummy country names to the POT file for translation purposes.
- Replaced the jQuery code for the Stripe button with vanilla JavaScript to resolve fatal errors on certain themes.
- Updated the code to handle the new Stripe webhook API format for subscriptions.
- Improved the account access start date updating issue when recurring subscription charge attempt fails.
- All setcookie function has updated for secure http only protocols.
- Some translation related improvements also regenerated a new POT file for the plugin.
- Spanish language translation updated.

= 4.6.5 =
- Added a new feature to send separate email notifications for account upgrades and renewals.
- New feature added to display warning message above subscription payment buttons if there is already an active subscription.
- Translation string updated for the 'Auto Delete Pending Account' field.
- Added a new option to resend member account activation email in the Tools menu.
- Renamed the 'has_tag' function to 'has_email_merge_tag' to avoid confusion.
- Added a new filter hook 'swpm_payment_button_note_msg_output' to allow custom code to insert a message or note before the payment button.
- Added a new filter hook 'swpm_hide_payment_button' to allow custom code to hide the payment button based on custom logic.
- Added a validation for the PayPal client ID to ensure it is not empty when using the PayPal PPCP checkout.
- For PayPal PPCP guest checkout, it will query the subscription or order details from the PayPal API to retrieve the email address (if needed).
- Updated the plugin name to "Simple Membership" in the main PHP file to match the plugin slug and readme file.

= 4.6.4 =
- Added a new feature to limit failed login attempts. Read the [failed login limit documentation](https://simple-membership-plugin.com/configuring-the-failed-login-attempt-limit-feature/) for more information.
- Improved user experience by disabling browser auto-fill on the password field in the new registration form.
- Added a debug log entry for successful user logouts.
- Refactored Stripe-related code for the client reference ID.
- Added new filter hook: 'swpm_paypal_ppcp_order_shipping_preference' to customize the shipping preference in PayPal PPCP checkout.
- Added new filter hook: 'swpm_paypal_ppcp_order_item_category' to customize the item category in PayPal PPCP checkout.
- For PayPal PPCP checkout, if no Thank You page URL is set, a default success message will now appear above the PayPal button.
- Introduced a new feature: users are redirected to the paid registration page after successful payment to complete their account setup.
- Added 'redirect_to_after_cancel' parameter to the 'swpm_show_subscriptions_and_cancel_link' shortcode to allow redirection to a custom URL after subscription cancellation.
- Added escaping functions to improve security on the payment button configuration admin screen.
- Introduced two new utility functions: `apply_protection_to_post` and `apply_protection_to_posts`.
- Addressed minor PHP notices and warning-related issues.

= 4.6.3 =
- WordPress 6.8 compatibility related changes.
- Added a new feature to set 'default content protection' settings.
- Password reset processing code refactored to be in 'wp' hook.
- Modified the 'Tools -> System Info' menu to accurately display the language translation directory path: /wp-content/languages/plugins/
- Updated the Hungarian language translation file.
- WordPress 6.8 uses new password hashing. We have updated the function that is used to check the password so it is compatible with WP 6.8.

= 4.6.2 =
- Added a new Reports menu to display various membership-related statistics and reports.
- Added a new DB table for storing the member's login history.
- Added a new filter hook 'swpm_replace_dynamic_tags' to allow addons to replace dynamic tags in the email notification.
- Renamed the function "email_activation()" to "handle_email_activation()" for clarity.
- Fixed a translation issue in the account delete feature's confirmation message.
- Added debug logging statements in the calculate_access_start_date_for_account_update() function.
- Updated the admin menu page title to maintain consistency with other menu items.
- New option added for Stripe webhook event verification.
- Added support for Stripe's 100% discount code feature.
- Stripe API version updated to the latest version.

= 4.6.1 =
- This release primarily includes optimizations and enhancements for the user login process with the 'Remember Me' option.
- Note: After updating to this version, members will need to log in again.
- The SWPM auth cookie structure now includes the 'Remember Me' value.
- When "remember me" is not checked, we use a session cookie to match with WordPress's cookie expiration.
- The 'Remember Me' option is now respected for SWPM logins originating from WordPress.
- After changing or updating the password from the edit profile page, the cookie is reset using the original remember-me flag.
- Updated the Dutch language translation file.

= 4.6.0 =
- Added a new option to bulk delete all member accounts with a specific account status.
- Updated the reference to the tools menu in the admin dashboard to point to the new location.
- Front-end registration form now renders via the 'swpm_load_template()' method.
- A getter method added to the SwpmAccessControl class.
- Improved the handling of the password reset request form to prevent resubmission on page reload.
- Added an extra check in the user delete function to check if the user has administrator role.
- Updated the system to display an error message when a password request is made for an incomplete account.
- New 'System Info' tab added in the Tools menu.
- Fixed a minor PHP notice issue.
- Braintree SDK updated to v6.23.0.
- Minor improvement to the category protection UI.
- Added the [Cloudflare Turnstile CAPTCHA integration](https://simple-membership-plugin.com/simple-membership-and-cloudflare-turnstile-integration-addon/).

= 4.5.9 =
- The "Tools" tab has been relocated to its own standalone menu item.
- Added a new action hook 'swpm_admin_account_status_updated' that will be triggered when the account status of a member is changed in the admin dashboard.
- Added a new action hook 'swpm_account_status_updated' that will be triggered when the account status update function is called for a member.
- Backwards compatibility for the 'profile_update' action hook.
- Updated the Stripe subscription payment button configuration interface to include the word 'Price' ID to reflect the changes made by Stripe.
- Small refactoring of the shortcode handler class to make it more efficient.
- Introduced a new settings option to hide membership level field on the registration form.
- Options related to the WP toolbar and admin dashboard have been grouped together in the advanced settings menu.
- First and Last name values can be set to empty in the member's profile edit interface.

= 4.5.8  =
- Allow promo code feature added to stripe subscription payment buttons.
- Added a new utility function 'get_all_protected_post_ids_list_from_db' to retrieve all the protected post IDs from the database.
- Updated the French language translation file.
- Active login limit feature conflict issue fixed for password reset event.
- Added a new filter hook 'swpm_override_protected_post_exclusion_from_search' that can be used to override the protected post exclusion from the search query.
- The asterisk character (*) is not allowed in the username field to maintain consistency with WordPress username character restrictions.

= 4.5.7 =
- Enhanced the efficiency of the 'pre_get_posts' filter hook handling function to address issues encountered on some sites during page saving and publishing.

= 4.5.6 =
- New free addon for resetting the settings and data of the plugin. Refer to [this documentation](https://simple-membership-plugin.com/simple-membership-reset-settings-and-data-addon/) for more information.
- The 'load_plugin_textdomain' function call has been moved to init hook with a higher priority for better compatibility with other plguins. This seem to work better for most languages.
- Regenerated the language POT file for the plugin to include the latest changes.
- Protected posts are now excluded from WP search query if the user doesn't have access to the post.

= 4.5.5 =
- New 'Active Login Limit' feature added. Refer to [this documentation](https://simple-membership-plugin.com/configuring-active-login-limit/) for more information.
- PayPal standard subscription canceled status will also be shown in the 'subscription created' transaction details page.
- Do not execute the after_login_redirection and the wp_signon function when the login originates from an external login form (example: WP, WooCommerce etc). This creates a better user experience.
- Minor Update to the swpm-orange-box CSS class to make it more readable.
- Added a new auto-redirect feature to the 'Full Page Protection Addon' for when a visitor attempts to access a protected page.
- Updated the password reset shortcode's email field to use a size of 30.
- Added members meta database table to store additional member data.
- Added a new option to bulk delete all members from a specific membership level.
- Added a twice daily cron job event that will be used to do various cleanup tasks in the future.
- Updated settings menu help text CSS to use the 'description' class for improved readability.
- Updated the Italian language translation file.

Full changelog available at [change-log-of-old-versions.txt](https://plugins.svn.wordpress.org/simple-membership/trunk/change-log-old-versions.txt)

== Upgrade Notice ==
None.
