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
    public static function gender_dropdown($selected = 'not specified'){
         return  '<option ' . ((strtolower($selected) == 'male')? 'selected="selected"' :"") . ' value="male">Male</option>' .
                 '<option ' . ((strtolower($selected) == 'female')? 'selected="selected"' :"") . ' value="female">Female</option>' .
                 '<option ' . ((strtolower($selected) == 'not specified')? 'selected="selected"' :"") . ' value="not specified">Not Specified</option>' ;
    }
    public static function subscription_unit_dropdown($selected = 'days'){
         return  '<option ' . ((strtolower($selected) == 'days')? 'selected="selected"' :"") . ' value="days">Days</option>' .
                 '<option ' . ((strtolower($selected) == 'weeks')? 'selected="selected"' :"") . ' value="weeks">Weeks</option>' .
                 '<option ' . ((strtolower($selected) == 'months')? 'selected="selected"' :"") . ' value="months">Months</option>' .
                 '<option ' . ((strtolower($selected) == 'years')? 'selected="selected"' :"") . ' value="years">Years</option>' ;
    }
}
