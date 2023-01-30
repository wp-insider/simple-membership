<?php

/*
 * Contains functions that can be used to inject certain API requests using the SWPM_PayPal_Request_API class.
 */
class SWPM_PayPal_Request_API_Injector {
        protected $paypal_req_api;
    
        public function __construct( $paypal_req_api ) {
            $this->paypal_req_api = $paypal_req_api;
        }
        
        /*
         * Creates a product then uses that product to create a billing plan.
         * @return - plan_id if successful. false if any of the steps fail.
         */
        public function create_product_and_billing_plan( $product_params, $subsc_args ){
            //First create a paypal product
            $product_name = isset($product_params['name']) ? $product_params['name'] : '';
            
            $paypal_product_id = $this->create_paypal_product_for_plan($product_params);
            if( $paypal_product_id === false ){
                //Failed to create product. Do debug logging. Error checking will be done by the fn caller.
                return false;
            }
            
            //Create billing plan
            $pp_subsc_billing_plan = new SWPM_PayPal_Subsc_Billing_Plan();
            $billing_cycles = $pp_subsc_billing_plan->construct_billing_cycles_param( $subsc_args );
    
            $plan_args = array(
                'plan_name' => 'Billing plan for ' . $product_name,
                'paypal_product_id' => $paypal_product_id,
                'billing_cycles' => $billing_cycles,
                'sub_recur_reattemp' => 1,
            );
            
            $plan_api_params = $pp_subsc_billing_plan->construct_create_billing_plan_api_params($plan_args);
            
            $created_plan_id = $this->create_paypal_billing_plan($plan_api_params);
            if( $created_plan_id === false ){
                //Failed to create billing plan. Do debug logging. Error checking will be done by the fn caller.
                return false;
            }
            return $created_plan_id;
        }
        
        /*
         * Gets a list of all the existing paypal products.
         * @return - an array of products object on success. false on error.
         */
        public function get_paypal_products_list(){
            $endpoint = '/v1/catalogs/products';
            $params = array();
            $response = $this->paypal_req_api->get($endpoint, $params);
            if ( $response !== false){
                $products = $response->products;
                //foreach($products as $product){
                //    echo '<br />Product ID: ' . $product->id;
                //}
                return $products;
            } else {
                return false;
            }
        }
        
        /*
         * Creates a PayPal product so it can be used with a subscription plan.
         */
        public function create_paypal_product_for_plan( $params ){
            $endpoint = '/v1/catalogs/products';
            $response = $this->paypal_req_api->post($endpoint, $params);
            if ( $response !== false){
                //Response is a success!
                $created_product_id = $response->id;
                return $created_product_id;
            } else {
                return false;
            }
        }
        
        /*
         * Gets a list of all the existing paypal billing plans.
         * @return - an array of plans object on success. false on error.
         */
        public function get_paypal_billing_plans_list(){
            $endpoint = '/v1/billing/plans';
            $params = array();
            $response = $this->paypal_req_api->get($endpoint, $params);
            if ( $response !== false){
                $plans = $response->plans;
                //foreach($plans as $plan){
                //    echo '<br />Plan ID: ' . $plan->id;
                //}
                return $plans;
            } else {
                return false;
            }
        }
        
        /*
         * Creates a PayPal billing plan for subscription.
         */
        public function create_paypal_billing_plan( $params ){
            $endpoint = '/v1/billing/plans';
            $response = $this->paypal_req_api->post($endpoint, $params);
            if ( $response !== false){
                //Response is a success!
                $created_plan_id = $response->id;
                //echo '<br />Plan ID: ' . $created_plan_id;
                //echo '<br />Plan Name: ' . $response->name;
                return $created_plan_id;
            } else {
                return false;
            }
        }
        
}
