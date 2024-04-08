=== Simple Membership ===
Contributors: smp7, wp.insider
Donate link: https://simple-membership-plugin.com/
Tags: member, members, members only, membership, memberships, register, WordPress membership plugin, content, content protection, paypal, restrict, restrict access, Restrict content, admin, access control, subscription, teaser, protection, profile, login, login page, bbpress, stripe, braintree
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 6.5
Stable tag: 4.4.3
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

If you have any issue with this plugin, please visit the plugin site and post it on the support forum or send us a contact:
https://simple-membership-plugin.com/

You can create a free forum user account and ask your questions.

= Miscellaneous =

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
* Option to automatically logout the members when they close the browser.
* Ability to forward the payment notification to an external URL for further processing.
* Option to configure whitelisting for user email addresses to allow registration only from specific email addresses or email domains.
* Option to configure blacklisting for user email addresses to block registration from certain email addresses or email domains.
* Option to configure PayPal payment buttons for memberships (one-time and recurring payments).
* Option to configure Stripe payment buttons for memberships (one-time and recurring payments).
* Option to configure Braintree payment buttons for memberships (one-time payments).

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

1. Upload the 'simple-wp-membership.zip' file from the Plugins->Add New page in the WordPress administration panel.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Where can I find complete documentation for this plugin? =
You can find the full documentation for this plugin on the [Simple Membership plugin documentation](https://simple-membership-plugin.com/simple-membership-documentation/) page.

== Screenshots ==

Please visit the membership plugin page to view screenshots:
https://simple-membership-plugin.com/

== Changelog ==

= WIP =
- TODO - <the new WIP subscription cancel shortcode work>
- Cancel URL configuration option added for Stripe SCA Subscription  type buttons.
- Some options (related to hiding the wp admin bar) of the general settings menu has been moved to the advanced settings tab.
- A warning message is displayed when editing membership levels if both manual approval and email activation settings are enabled simultaneously.
- New shortcode 'swpm_show_active_subscription_and_cancel_button' for displaying list of active subscription and their cancel form.

= 4.4.3 =
- The accepted payment method types can now be controlled from your Stripe account settings. This will allow you to enable/disable certain payment methods.
- Updated the documentation link for the Stripe Subscription button configuration.
- Enhanced the auto-login feature's redirect URL handling for better compatibility with some servers.
- New registration and profile form UI and validation is the default UI for all new installs. The old UI can be enabled from the advanced settings menu.
- Added Arabic translation files to the plugin. Thanks to @Adham.
- Added output escaping to the new registration and edit profile forms.

= 4.4.2 =
- Added an option to specify a cancel URL for Stripe buy now button.
- The PayPal order ID is also passed to the PayPal payment capture API call's header.
- Added a check for the PayPal Buy Now payment capture status in the IPN handling script.
- Updated the Spanish language translation file.
- Minor spelling mistake fixed.

= 4.4.1 =
- Added 'Cayman Islands' to the country dropdown list.
- The unique session ID generation process improved.
- The PayPal Token cache will be deleted automatically if the Live/Test mode option is changed in the settings menu.
- Fixed an issue with the PayPal test/live mode toggle issue with the new API.

= 4.4.0 =
- Added a new feature in the 'Bulk Operation' menu tab to allow bulk update members account status.
- Improved the email validation in the new registration form UI.
- Updated the Spanish language translation file.
- Changed the aciton hook name 'swpm_login' to 'swpm_after_login_authentication' to describe the hook better.
- The after login redirection feature won't be application when the login form originates from the WP Login form. 
- This will remove confusion for some users when they login from the standard WP login form (not the simple membership's login form) and then the page redirects to the after login redirection URL.

= 4.3.9 = 
- Note: Significant updates have been made to the PayPal's new API related code in this release. Please take a backup of your site before updating.
- The 'Payment Settings' tab has been moved to the 'Payments' menu. Allowing all payment configuration related functions to be under one menu.
- The 'Payment Settings' menu has been divided into multiple sub-menus for better organization.
- Added a new option in the PayPal API tab to allow manual deletion of the PayPal API access token cache.
- The PayPal buy now (New API) button's JavaScript code has been updated to reflect the latest PayPal API related changes.
- If WP Login form is used, our plugin will let WP handle the post-login redirection.
- Honor the 'redirect_to' parameter in the post login redirection function.
- Added an empty check to the Stripe buy now IPN handling function.
- Translation improvement for 'activation-required' account status display in the user's profile.
- Better formatting for the admin edit interface error message.
- Added output escaping in the new PayPal API settings tab.
- Added a new filter 'swpm_send_direct_email_body_settings'.
- The following new options has been added in the 'Send Direct Email' feature. Thanks to Dennis.
- Send Direct Email -> Send email based on member's account status.
- Send Direct Email -> Send a copy of email to the site admin.
- Send Direct Email -> List email recipients as a preview.

= 4.3.8 =
- Minor translation related update in the admin edit member interface.
- Fixed an issue with the new PayPal buy now type button not rendering correctly with the item description.

= 4.3.7 =
- Added new form and validation Interface for registration and edit profile forms. 
- New settings field added to turn on/off the new UI for the registration and profile forms. This option is located in the Advanced Settings menu.
- The goal with this new option is to offer a more mobile responsive UI for the registration and profile forms.
- Added a new action hook (swpm_before_login_form_widget) in the login.php file.
- Added a new action hook (swpm_before_loggedin_widget) in the loggedin.php file.
- The edit membership level interface shows the currently editing membership level's ID.
- Added a new action hook (swpm_front_end_reset_password_using_link_completed). Thanks to @MedTRGit.
- Updated the translation POT file.
- Refactored the Stripe session create code to a separate class.
- Filter hooks updated to to customize password validation rules and messages for the new form UI.
- Updated the Swedish translation files.

= 4.3.6 =
- Added output escaping to the 'list_type' parameter in the 'Post and Page Protection' menu tab.

= 4.3.5 =
- Updated the German language translation file. Thanks to Stefan.
- Show strong password requirement message on the password reset page (if the feature is enabled). Thanks to Darwin for submitting this update.
- After submitting the password reset form, a message displaying "Processing request" is shown. Thanks to Darwin for submitting this update.
- Added a new filter hook for the Thank You page message.
- Fixed a small bug with the newly added "Send Direct Email" feature. It was not setting the "From Email Address" field's value.
- Added a new option labeled "Default Account Status After Payment". This should be helpful with certain types of manual approval configuration.
- Updated the code so it stops going forwared if the update user command fails.
- Added validation to the password reset by link feature. Thanks to Rafie for the report.

= 4.3.4 =
- Readability improvement for the 'remember me' checkbox field's code.
- Spanish language translation file updated.
- Added more debug logging text to the Stripe webhook handling script.
- Added CSS class to the notice message output.
- Allow any field with class 'swpm-date-picker' to use the datepicker function in the members menu.
- Added a new hook that gets triggered when the account status is updated to expired in the daily cronjob.
- Added a new hook that gets triggered when an existing member pays for a membership and the account status is refreshed.
- The original transaction post ID is saved with the user profile for Stripe subscription transactions.

= 4.3.3 =
- Added new feature to confiugre an "after email activation redirection" for any membership level.
- Renamed the SimpleWpMembership::wp_logout() function to SimpleWpMembership::wp_logout_handler().
- The auth cookie will be set to session cookie if the 'force-wp-user-sync' feature is enable when 'remember me' is unchecked.
- Added a silent logout option so the logout function can be called without triggering the action hook.
- Added the 'swpm_subscription_payment_cancelled' hook to the cancel stripe subscription via URL feature.

= 4.3.2 =
- Added CSS to highlight the order status in the payments menu.
- Added a new utility function compare_url_without_http(). This function is used for matching the system generated pages.
- Added a new CSS div for the activation required error message.
- New Gutenberg Block for Payment Buttons.
- Updated the system page URL check function to include the edit profile, join and the password reset pages.
- New feature to send direct email to a group of members (for example: send an email to all members of a membership level).
- Minor PHP 8.2 related deprecation notice fixes.

Full changelog available at [change-log-of-old-versions.txt](https://plugins.svn.wordpress.org/simple-membership/trunk/change-log-old-versions.txt)

== Upgrade Notice ==
If you are using the form builder addon, then that addon will need to be upgraded to v1.1 also.

== Arbitrary section ==
None
