=== Simple Membership ===
Contributors: smp7, wp.insider, amijanina
Donate link: https://simple-membership-plugin.com/
Tags: member, members, members only, membership, memberships, register, WordPress membership plugin, content, content protection, paypal, restrict, restrict access, Restrict content, admin, access control, subscription, teaser, protection, profile, login, login page, bbpress, stripe
Requires at least: 3.3
Tested up to: 4.5
Stable tag: 3.3.0
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

Non-members viewing a protected page will be prompted to login or become a member.

= Have Free and Paid Memberships =
You can configure it to have free and/or paid memberships on your site. Paid membership payment is handled securely via PayPal. 

Both one time and recurring/subscription payments are supported.

You can also accept one time membership payment via Stripe payment gateway.

= Membership Payments Log = 
All the payments from your members are recorded in the plugin. You can view them anytime by visiting the payments menu from the admin dashboard.

= Member Login Widget on The Sidebar =
You can easily add a member login widget on the sidebar of your site. Simply use the login form shortcode in the sidebar widget.

You can also customize the member login widget by creating a custom template file in your theme (or child theme) folder.

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
* Ability to use merge vars in the membership email notification.
* Membership management side is handled by the plugin.
* Ability to manually approve your members.
* Ability to import WordPress users as members.
* Filter members list by account status.
* Can be translated to any language.
* Hide the admin toolbar from the frontend of your site.
* Allow your members to delete their membership accounts.
* Send quick notification email to your members.
* Customize the password reset email for members.
* Use Google reCAPTCHA on your member registration form.
* The login and registration widgets will be responsive if you are using a responsive theme.
* Ability to restrict the commenting feature on your site to your members only.
* Front-end member registration page.
* Front-end member profiles.
* Front-end member login page.

= Language Translations =

The following language translations are already available:

* English
* Spanish
* German
* French
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

You can translate the plugin using the language [translation documentation](https://simple-membership-plugin.com/translate-simple-membership-plugin/).

== Installation ==

Do the following to install the membership plugin:

1. Upload the 'simple-wp-membership.zip' file from the Plugins->Add New page in the WordPress administration panel.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

None.

== Screenshots ==

Please visit the memberhsip plugin page to view screenshots:
https://simple-membership-plugin.com/

== Changelog ==
= 3.3.0 =
- Updated the Hungarian language file.
- Improved input sanitization.

= 3.2.9 =
- Lowered the priority of "the_content" filter processing (this should be helpful for compatibility with some of the content builder type plugins).
- Added Slovak language translation file. The translation was submitted by Marek Kucak.
- XSS vulnerability fix for page request parameter.

= 3.2.8 =
- Added Stripe Buy Now option for membership payment.
  Stripe payment usage documentation: https://simple-membership-plugin.com/create-stripe-buy-now-button-for-membership-payment/
- Added a notice in the admin interface to notify you when you keep the sandbox payment mode enabled.
- Added a check in the authentication system to stop login request processing if the user is already logged into the site as ADMIN.
- The payment button shortcode will now check to make sure you entered a valid button ID in the shortcode.
- Fixed a couple of minor debug notice warnings.
- Bugfix: Admin Dashboard Access Permission setting not saving correctly.

= 3.2.7 =
- Added a new option in the plugin settings so you can specify other WP user role (example: editor) to be able to use/see the plugin's admin interface.
- Added a "user profile delete" option in the admin profile edit interface of the plugin. Admins can use it to delete a user record while in the member edit interface.
- Added a new option so the member registration complete email notification can be sent to multiple site admins.
- Added Bosnian language translation file. The translation was submitted by Rejhan Puskar.
- Updated the Japanese language file.
- Updated the Dutch language file. Thanks to R.H.J. Roelofsen.

= 3.2.6 =
- Added Hungarian language translation file. The translation was submitted by Laura Szitar.
- Improved the members menu navigation menu so the tabs are always visible (even when you go to the add or edit members screen).
- Added 2 new action hooks (They are triggered when subscription is cancelled and when a recurring payment is received).
- Improved the membership levels navigation menu tabs.
- The "Edit Member" interface now shows the member ID of the currently editing member.

= 3.2.5 =
- Added a new feature to enable redirection to the last page after login (where they clicked the login link). 
This new option is available in the after login redirection addon.
https://wordpress.org/plugins/simple-membership-after-login-redirection/

= 3.2.4 =
- Fixed a bug with attachment protection showing an error message.

= 3.2.3 =
- Added a new option so you can configure a membership account renewal page in the plugin.
- The account expiry message will include the renewal page link (if you configure the renewal page).
- Removed login link from the comment protection message. You can customize the comment protection message using the custom message addon.
- Updated the Russian language file. Thanks to @dimabuko for updating the language file.
- Updated the Portuguese language file. Thanks to @Juan for updating the language file.
- Added a new addon for better custom post type protection.
- Made an improvement to the wp user delete function.
- More tag protection check improvements.
- Account with "inactive" status can also log into the site if the "Allows expired login" feature is enabled.
- Updated the PayPal IPN validation code so it is compatible with the upcoming PayPal changes.

= 3.2.2 =
- New feature to only allow the members of the site to be able to post a comment.
- Moved the "Allow Account Deletion" option to the Advanced Settings tab of the plugin.
- Moved the "Auto Delete Pending Account" option to the Advanced Settings tab of the plugin.
- WordPress 4.5 compatibility.

= 3.2.1 =
- Added a new filter (swpm_transactions_menu_items_per_page) that can be used to customize the number of items that is listed in the transactions menu.
- Added more sorting option in the transactions table.
- Added sanitization for the sort inputs in the member transactions table.
- Fixed an issue with the auto delete pending account settings.
- Changed admin heading structure from h2 to h1.

= 3.2.0 =
- Added Catalan language translation file. The translation was submitted by Josep Ramon.
- Custom post type categories are also listed in the category protection menu.
- Added a new filter (swpm_members_menu_items_per_page) that can be used to customize the number of items that is listed in the members menu.
- The default number of items listed in the members menu by default has been increased to 50.
- Comment protection fix for posts using "more" tag.
- Comments of protected posts are also protected.
- Added CSS classes for all the field rows in the standard membership registration form.
- Added CSS classes for all the field rows in the edit profile form.

= 3.1.9 =
- Added new merge vars that can be used in the registration complete email. These are {member_id}, {account_state}, {email}, {member_since}
- Added trailingslashit() to the after logout redirect URL.
- Created a new extension to show member info. [usage documentation](https://simple-membership-plugin.com/simple-membership-addon-show-member-info/)
- A new cookie is dropped when a member logs into the site. It can be used for caching plugin compatibility.
- Added a new function to load the template for login widget and password reset form. This will allow customization of the login widget by adding the custom template to the theme folder.

= 3.1.8 =
- Improved the members and payments menu rendering for smaller screen devices.
- Added a utility function to easily output a formatted date in the plugin according to the WordPress's date format settings.
- Fixed a bug in the wp username and email validation functionality. Thanks to Klaas van der Linden for pointing it out.
- The membership password reset form has been restructured (the HTML table has been removed).

= 3.1.7 =
- Added debug logging for after a password is reset successfully.
- The plugin will prevent WordPress's default password reset email notification from going out when a member resets the password.
- Added a new bulk action item. Activate account and notify members in bulk. Customize the activation email from the email settings menu of the plugin.
- Added validation in the bulk operation function to check and make sure that multiple records were selected before trying the bulk action.
- Updated the Portuguese (Brazil) language translation file. The translation was updated by Fernando Telles.
- Updated the Tools interface of the plugin.
- The members list can now be filtered by account status (from the members interface)
- The members list now shows "incomplete" keyword in the username field for the member profiles that are incomplete.
- Added an "Add Member" tab in the members menu.

= 3.1.6 =
- Added a new feature to show the admin toolbar to admin users only.
- Added CSS for membership buy buttons to force their width and height to be auto.
- Added a few utility functions to retrieve a member's record from custom PHP code (useful for developers).
- Added the free Google recaptcha addon for registration forms.

= 3.1.5 =
- Added a new shortcode [swpm_show_expiry_date] to show the logged-in member's expiry details.
- The search feature in the members menu will search the company name, city, state, country fields also.
- The subscription profile ID (if any) for subscription payment is now shown in the "payments" interface of the plugin.
- Added new filter hook so additional fields can be added to the payment button form (example: specify country or language code).
- Updated the language POT file.

= 3.1.4 =
- Added an option in the "Payments" menu to link a payment to the corresponding membership profile (when applicable).
- Fixed an issue with the subscriber ID not saving with the member profile (for PayPal subscription payments).
- Added Hebrew language translation file. The translation was submitted by Merom Harpaz.

= 3.1.3 =
- Added Indonesian language translation file. The translation was submitted by Hermanudin.
- Removed a couple of "notice" warnings from the installer.
- Added option to bulk change members account status.
- Updated the CSS class for postbox h3 elements.
- The member search feature (in the admin side) can now search the list based on email address.

= 3.1.2 =
- Added more sortable columns in the members menu.
- Adjusted the CSS for the registration and edit profile forms so they render better in small screen devices.
- Changed the "User name" string to "Username"

= 3.1.1 =
- Fix for some special characters in the email not getting decoded correctly.
- Updated the membership upgrade email header to use the "from email address" value from the email settings.

= 3.1.0 =
- Fixed an email validation issue for when the plugin is used with the form builder addon.

= 3.0.9 =
- Updated the Spanish language translation file.
- Updated the POT file for language translation.
- Added Dutch (Belgium) language translation file. The translation was submitted by Johan Calu.
- Fixed an email validation issue.

= 3.0.8 =
- Added Latvian language translation file. The translation was submitted by Uldis Kalnins.
- Updated the POT file for language translation.
- Added a placeholder get_real_ip_addr() function for backwards compatibility.

= 3.0.7 =
- Fixed a typo in the password reset message.
- Removed the get_real_ip_addr() function (using get_user_ip_address() from the "SwpmUtils" class).
- Simplified the message class interaction.
- Added CSS classes to the registration, edit profile and login submit buttons.
- Added confirmation in the member's menu bulk operation function.
- Fixed the bulk delete and delete functionality in the members list menu.
- Fixed the category protection confirmation message.
- Added Greek language translation file. The translation was submitted by Christos Papafilopoulos.

= 3.0.6 =
- Corrected the Danish language file name.
- Fixed an issue with the profile update success message sticking.

= 3.0.5 =
- Added a fix to prevent an error from showing when a member record is edited from the admin side.

= 3.0.4 =
- Added a new utility function so a member's particular info can be retrieved using this function.
- Added extra guard to prevent the following error "Call to member function get () on a non object".
- Updated the langguage POT file.

= 3.0.3 =
- Increased the database character limit size of the user_name field.
- Refactored the 'swpm_registration_form_override' filter.
- Added integration with iDevAffiliate.
- Added integration with Affiliate Platform plugin.

= 3.0.2 =
- Added a new shortcode that can be used on your thank you page. This will allow your users to complete paid registration from the thank you page after payment.
- The last accessed from IP address of a member is shown to the admin in the member edit screen.
- The debug log (if enabled) for authentication request is written to the "log-auth.txt" file.
- Fixed a bug with the bulk member delete option from the bottom bulk action form.
- Fixed a bug with the bulk membership level delete option from the bottom bulk action form.

= 3.0.1 =
- Added a new CSS class to the registration complete message.
- Added Portuguese (Portugal) language translation file. The translation was submitted by Edgar Sprecher.
- Replaced mysql_real_escape_string() with esc_sql()
- Members list in the admin is now sorted by member_id by default.
- Added a new filter in the registration form so Google recaptcha can be added to it.

= 3.0 =
- Updated the swedish langauge translation
- Added a new option to enable opening of the PayPal buy button in a new window (using the "new_window" parameter in the shortcode).
- You can now create and configure PayPal Subscription button for membership payment from the payments menu.

= 2.2.9 =
- Added a new feature to customize the password reset email.
- Added a new feature to customize the admin notification email address.
- Improved the help text for a few of the email settings fields.
- Updated the message that gets displayed after a member updates the profile.

= 2.2.8 =
- Updated the swedish language translation file.
- Code refactoring: moved all the init hook tasks to a separate class.
- Increased the size of admin nav tab menu items so they are easy to see.
- Made all the admin menu title size consistent accross all the menus.
- Updated the admin menu dashicon icon to a nicer looking one.
- You can now create and configure PayPal buy now button for membership payment from the payments menu.

= 2.2.7 =
- Added Japanese language translation to the plugin. The translation was submitted by Mana.
- Added Serbian language translation to the plugin. The translation was submitted by Zoran Milijanovic.
- All member fields will be loaded in the edit page (instead of just two).

= 2.2.6 =
- Fixed an issue with the category protection menu after the class refactoring work.
- Fixed the unique key in the DB table

= 2.2.5 =
- Refactored all the class names to use the "swpm" slug to remove potential conflict with other plugins with similar class names.

= 2.2.4 =
- Fixed an issue with not being able to unprotect the category protection.
- Minor refactoring work with the classes.

= 2.2.3 =
- Updated the category protection interface to use the get_terms() function.
- Added a new Utility class that has some helpful functions (example: check if a member is logged into the site). 

= 2.2.2 =
- All the membership payments are now recorded in the payments table.
- Added a new menu item (Payments) to show all the membership payments and transactions.
- Added Lithuanian language translation to the plugin. The translation was submitted by Daiva Pakalne.
- Fixed an invalid argument error.

= 2.2.1 =
- Added a new table for logging the membership payments/transactions in the future.
- Made some enhancements in the installer class so it can handle both the WP Multi-site and single site setup via the same function.

= 2.2 =
- Added a new feature to allow expired members to be able to log into the system (to allow easy account renewal).
- The email address value of a member is now editable from the admin dashboard and in the profile edit form.
- Added CSS classes around some of the messages for styling purpose.
- Some translation updates.

= 2.1.9 =
- Improved the password reset functionality.
- Improved the message that gets displayed after the password reset functionality is used.
- Updated the Portuguese (Brazil) language file.
- Improved the user login handling code.

= 2.1.8 =
- Improved the after logout redirection so it uses the home_url() value.
- Fixed a bug in the member table sorting functionality.
- The members table can now be sorted using ID column.


= 2.1.7 =
- Added a new feature to automatically delete pending membership accounts that are older than 1 or 2 months.
- Fixed an issue with the send notification to admin email settings not saving.

= 2.1.6 =
- Fixed a bug with new membership level creation with a number of days or weeks duration value.

= 2.1.5 =
- Improved the attachment protection so it doesn't protect when viewing from the admin side also.
- Removed a dubug dump statement.

= 2.1.4 =
- Improved the login authentication handler logic.
- Fixed the restricted image icon URL.
- Updated the restricted attachment icon to use a better one.

= 2.1.3 =
- Added a new feature to allow the members to delete their accounts.

= 2.1.2 =
- Updated the membership subscription payment cancellation handler and made it more robust.
- Added an option in the settings to reset the debug log files.

= 2.1.1 =
- Enhanced the username exists function query.
- Updated one of the notice messages.

= 2.1 =
- Changed the PHP short tags to the standard tags
- Updated a message in the settings to make the usage instruction clear.
- Corrected a version number value.

= 2.0 =
- Improved some of the default content protection messages.
- Added Danish language translation to the plugin. The translation was submitted by Niels Boje Lund.

= 1.9.9 =
- WP Multi-site network activation error fix.

= 1.9.8 =
- Fixed an issue with the phone number not saving.
- Fixed an issue with the new fixed membership expiry date feature.

= 1.9.7 =
- Minor UI fix in the add new membership level menu.

= 1.9.6 =
- Added a new feature to allow fixed expiry date for membership levels.
- Added Russian language translation to the plugin. The translation was submitted by Vladimir Vaulin.
- Added Dutch language translation to the plugin. The translation was submitted by Henk Rostohar.
- Added Romanian language translation to the plugin. The translation was submitted by Iulian Cazangiu.
- Some minor code refactoring.

= 1.9.5 =
- Added a check to show the content of a protected post/page if the admin is previewing the post or page.
- Fixed an issue with the quick notification email feature not filtering the email shortcodes.
- Improved the login form's HTML and CSS.

= 1.9.4 =
- Added a new feature to send an email notification to a member when you edit a user's record. This will be helpful to notify members when you activate their account.
- Fixed an issue with "pending" member account getting set to active when the record is edited from admin side.

= 1.9.3 =
- Fixed an issue with the featured image not showing properly for some protected blog posts.

= 1.9.2 =
- Fixed the edit link in the member search interface.

= 1.9.1 =
- Added Turkish language translation to the plugin. The translation was submitted by Murat SEYISOGLU.
- WordPrss 4.1 compatibility.

= 1.9.0 =
- Fixed a bug in the default account setting option (the option to do manual approval for membership).
- Added Polish language translation to the plugin. The translation was submitted by Maytki.
- Added Macedonian language translation to the plugin. The translation was submitted by I. Ivanov.

= 1.8.9 =
- Added a new feature so you can set the default account status of your members. This can useful if you want to manually approve members after they signup.

= 1.8.8 =
- Fixed an issue with the account expiry when it is set to 1 year.

= 1.8.7 =
- Updated the registration form validation code to not accept apostrophe character in the username field.
- Added a new tab for showing addon settings options (some of the addons will be able to utilize this settings tab).
- Added a new action hook in the addon settings tab.
- Moved the plugin's main class initialization code outside of the plugins_loaded hook.

= 1.8.6 =
- Fixed an email validation issue with paid membership registration process.
- Added a new free addon to customize the protected content message.

= 1.8.5 =
- Added category protection feature under the membership level menu.
- Fixed a bug with paid membership paypal IPN processing code.

= 1.8.4 =
- The Password field won't use the browser's autofill option in the admin interface when editing a member info.

= 1.8.3 =
- Added Swedish language translation to the plugin. The translation was submitted by Geson Perry.
- There is now a cronjob in the plugin to expire the member profiles in the background.
- Released a new addon - https://simple-membership-plugin.com/simple-membership-registration-form-shortcode-generator/
- Added a menu called "Add-ons" for listing all the extensions of this plugin.

= 1.8.2 =
- Updated the members expiry check code at the time of login and made it more robust.

= 1.8.1 =
- MySQL database character set and collation values are read from the system when creating the tables.
- Added German language translation file to the plugin.
- Some code refactoring work.
- Added a new feature to allow admins to create a registration form for a particular membership level.

= 1.8.0 =
- Added a new feature called "more tag protection" to enable teaser content. Read the [teaser content documentation](https://simple-membership-plugin.com/creating-teaser-content-membership-site/) for more info.
- Added Portuguese (Brazil) language translation to the plugin. The translation was submitted by Rachel Oakes.
- Added cookiehash definition check (in case it is not defined already).

= 1.7.9 =
- Added Spanish language translation to the plugin. The translation was submitted by David Sanchez.
- Removed some hardcoded path from the auth class.
- WordPress 4.0 compatibility

= 1.7.8 =
- Architecture improvement for the [WP User import addon](https://simple-membership-plugin.com/import-existing-wordpress-users-simple-membership-plugin/)
- Updated the POT file with the new translation strings

= 1.7.7 =
- The plugin will now show the member account expiry date in the login widget (when a user is logged into the site).
- Added a couple of filters to the plugin.

= 1.7.6 =
- Fixed an issue with hiding the admin-bar. It will never be shown to non-members.
- Renamed the chinese language file to correct the name.
- Removed a lot of fields from the front-end registration form (after user feedback). The membership registration form is now a lot simpler with just a few fields.
- Fixed a bug with the member search option in the admin dashboard.
- Added a few new action hooks and filters.
- Fixed a bug with the media attachment protection.

= 1.7.5 = 
- Fixed an issue with language file loading.

= 1.7.4 =
- Added capability to use any of the shortcodes (example: Login widget) in the sidebar text widget.

= 1.7.3 =
- Added french language translation to the plugin. The translation was submitted by Zeb.
- Fixed a few language textdomain issue.
- Fixed an issue with the the registration and login page shortcode (On some sites the registration form wasn't visible.)
- Added simplified Chinese language translation to the plugin. The translation was submitted by Ben.

= 1.7.2 =
- Added a new hook after the plugin's admin menu is rendered so addons can hook into the main plugin menu.
- Fixed another PHP 5.2 code compatibility issue.
- Fixed an issue with the bulk member delete functionality.

= 1.7.1 =
- Fixed another PHP 5.2 code compatibility issue.
- Updated the plugin's language file template.

= 1.7 = 
- Tweaked code to make it compatible with PHP 5.2 (previously PHP 5.3 was the requirement).
- Added checks for checking if a WP user account already exists with the chosen username (when a member registers).
- Fixed a few translation strings.

= 1.6 =
- Added comment protection. Comments on your protected posts will also be protected automatically.
- Added a new feature to hide the admin toolbar for logged in users of the site.
- Bug fix: password reset email not sent correctly
- Bug fix: page rendering issue after the member updates the profile.

= 1.5.1 = 
- Compatibility with the after login redirection addon:
http://wordpress.org/plugins/simple-membership-after-login-redirection/

= 1.5 =
- Fixed a bug with sending member email when added via admin dashboard.
- Fixed a bug with general settings values resetting.
- Added a few action hooks to the plugin.

= 1.4 =
- Refactored some code to enhance the architecture. This will help us add some good features in the future.
- Added debug logger to help troubleshoot after membership payment tasks.
- Added a new action hook for after paypal IPN is processed.

= 1.3 =
- Fixed a bug with premium membership registration.

= 1.2 =
- First commit to WordPress repository.

== Upgrade Notice ==
If you are using the form builder addon, then that addon will need to be upgraded to v1.1 also.

== Arbitrary section ==
None
