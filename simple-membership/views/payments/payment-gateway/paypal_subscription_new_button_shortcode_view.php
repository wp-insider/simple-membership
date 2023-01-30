<?php

/**************************************************
 * PayPal Subscription New button shortcode handler
 *************************************************/
add_filter('swpm_payment_button_shortcode_for_pp_subscription_new', 'swpm_render_pp_subscription_new_button_sc_output', 10, 2);

function swpm_render_pp_subscription_new_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? $args['id'] : '';
    if (empty($button_id)) {
        return '<p class="swpm-red-box">Error! swpm_render_pp_subscription_new_button_sc_output() function requires the button ID value to be passed to it.</p>';
    }

    $output = '';

    $output .= '<div class="swpm-payment-button-wrapper">';
    $output .= 'The NEW button code will go here!!!';
    $output .= '</div>';

    //TODO - NEED TO COMPLETE THIS BY LOOKING AT THE EXAMPLE CODE FROM MY TEST CODE.

    return $output;
}
