<?php
class bUtils{
    public static function calculate_subscription_period($subcript_period,$subscript_unit){
        if(($subcript_period == 0)  && !empty($subscript_unit)) //will expire after a fixed date.
            return date(get_option('date_format'), strtotime($subscript_unit));
        switch($subscript_unit){
            case 'Days':
            break;
            case 'Weeks':
            $subcript_period = $subcript_period*7;
            break;
            case 'Months':
            $subcript_period = $subcript_period*30;
            break;
            case 'Years':
            $subcript_period = $subcript_period*365;
            break;
        }
        if($subcript_period==0)// its set to no expiry until cancelled
            return 'noexpire';
        return $subcript_period . ' ' . $subscript_unit;
    }
}
