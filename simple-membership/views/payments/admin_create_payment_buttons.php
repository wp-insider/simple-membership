<?php
//Render the create new payment button tab

require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_paypal_buy_now_new_button.php';//PayPal Buy Now button (New API)
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_paypal_subscription_new_button.php';//PayPal Subscription button (New API)
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_paypal_buy_now_button.php';//Standard
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_paypal_subscription_button.php';//Standard
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_paypal_smart_checkout_button.php';//Legacy
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_stripe_buy_now_button.php';
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_stripe_sca_buy_now_button.php';
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_stripe_subscription_button.php';
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_stripe_sca_subscription_button.php';
require_once SIMPLE_WP_MEMBERSHIP_PATH . 'views/payments/payment-gateway/admin_braintree_buy_now_button.php';

do_action( 'swpm_create_new_button_process_submission' ); //Addons can use this hook to save the data after the form submit then redirect to the "edit" interface of that newly created button.
?>

<div class="swpm-grey-box">
<?php echo SwpmUtils::_( 'You can create a new payment button for your memberships using this interface.' ); ?>
</div>

<?php
if ( ! isset( $_REQUEST['swpm_button_type_selected'] ) ) {
	//Button type hasn't been selected. Show the selection option.
	?>
	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php echo SwpmUtils::_( 'Select Payment Button Type' ); ?></label></h3>
		<div class="inside">
			<form action="" method="post">
			<table class="form-table" role="presentation">
			<tr>
			<td>
			<fieldset>
				<label><input type="radio" name="button_type" value="pp_buy_now_new" checked /> <?php _e( 'PayPal Buy Now (New API)(Recommended)', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="pp_subscription_new" /> <?php _e( 'PayPal Subscription (New API)(Recommended)', 'simple-membership' ); ?></label>
				<br />				
				<label><input type="radio" name="button_type" value="pp_buy_now" /> <?php _e( 'PayPal Buy Now', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="pp_subscription" /> <?php _e( 'PayPal Subscription', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="braintree_buy_now" /> <?php _e( 'Braintree Buy Now', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="stripe_sca_buy_now" /> <?php _e( 'Stripe Buy Now (Recommended)', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="stripe_sca_subscription" /> <?php _e( 'Stripe Subscription (Recommended)', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="stripe_buy_now" /> <?php _e( 'Stripe Legacy Buy Now (Deprecated)', 'simple-membership' ); ?></label>
				<br />
				<label><input type="radio" name="button_type" value="stripe_subscription" /> <?php _e( 'Stripe Legacy Subscription (Deprecated)', 'simple-membership' ); ?></label>
				<br />
			</fieldset>
			</td>
			</tr>
			</table>
	<?php
	apply_filters( 'swpm_new_button_select_button_type', '' );
	wp_nonce_field( 'swpm_admin_create_btns', 'swpm_admin_create_btns' );
	?>

				<br />
				<input type="submit" name="swpm_button_type_selected" class="button-primary" value="<?php echo SwpmUtils::_( 'Next' ); ?>" />
			</form>

		</div>
	</div><!-- end of .postbox -->

	<div class="swpm-grey-box">
	<?php echo SwpmUtils::_( 'You can also use payment buttons from the following plugins to accept payments for your memberships.' ); ?>
	<p>
		<a href="https://wordpress.org/plugins/wp-express-checkout/" target="_blank">WP Express Checkout</a>
		&nbsp;|&nbsp;
		<a href="https://wordpress.org/plugins/stripe-payments/" target="_blank">Accept Stripe Payments</a>
	</p>
	</div>

	<?php
} else {
	//Button type has been selected. Show the payment button configuration option.
	//check the nonce first
	check_admin_referer( 'swpm_admin_create_btns', 'swpm_admin_create_btns' );
	//Fire the action hook. The addons can render the payment button configuration option as appropriate.
	$button_type = sanitize_text_field( $_REQUEST['button_type'] );
	do_action( 'swpm_create_new_button_for_' . $button_type );
	//The payment addons will create the button from then redirect to the "edit" interface of that button after save.
}
