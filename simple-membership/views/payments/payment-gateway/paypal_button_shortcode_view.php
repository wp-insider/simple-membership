<?php

add_filter('swpm_payment_button_shortcode_for_pp_buy_now', 'swpm_render_pp_buy_now_button_sc_output', 10, 2);

function swpm_render_pp_buy_now_button_sc_output($button_code, $args)
{
    //TODO implement the button HTML code
    $button_code = "TODO! PayPal buy now button shortcode goes here";
            
    return $button_code;
}