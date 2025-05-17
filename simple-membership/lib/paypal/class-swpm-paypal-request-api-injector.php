<?php

/*
 * Contains functions that can be used to inject certain API requests using the SWPM_PayPal_Request_API class.
 */
class SWPM_PayPal_Request_API_Injector {
        protected $paypal_req_api;
        protected $mode;
        protected $live_client_id;
        protected $live_secret;
        protected $sandbox_client_id;
        protected $sandbox_secret;

        public function __construct() {
            //Setup the PayPal API request object so that the injector can use it to make pre-made API requests easily.
            $settings = SwpmSettings::get_instance();
            $this->live_client_id = $settings->get_value('paypal-live-client-id');
            $this->live_secret = $settings->get_value('paypal-live-secret-key');    
            $this->sandbox_client_id = $settings->get_value('paypal-sandbox-client-id');
            $this->sandbox_secret = $settings->get_value('paypal-sandbox-secret-key');
            $sandbox_enabled = $settings->get_value('enable-sandbox-testing');
            $this->mode = $sandbox_enabled ? 'sandbox' : 'production';
            $paypal_req_api = SWPM_PayPal_Request_API::get_instance();
            $paypal_req_api->set_mode_and_api_credentials( $this->mode, $this->live_client_id, $this->live_secret, $this->sandbox_client_id, $this->sandbox_secret );            
            $this->paypal_req_api = $paypal_req_api;
        }

        /**
         * Sets the webhook mode. Used to override/set the mode (if needed) after the object is created.
         */
        public function set_mode_and_api_creds_based_on_mode( $mode ) {
            //Set the mode.
            $this->mode = $mode;

            //Set the API credentials for the Req_API object based on the mode.
            $paypal_req_api = SWPM_PayPal_Request_API::get_instance();
            $paypal_req_api->set_mode_and_api_credentials( $mode, $this->live_client_id, $this->live_secret, $this->sandbox_client_id, $this->sandbox_secret );
            $this->paypal_req_api = $paypal_req_api; 
        }

        public function get_last_error_from_api_call(){
            return $this->paypal_req_api->get_last_error();
        }
                
        public function set_paypal_req_api( $paypal_req_api ){
            //Set a particular request ojbect for API call. Useful for testing purposes.
            $this->paypal_req_api = $paypal_req_api;
        }

        public function get_paypal_req_api(){
            return $this->paypal_req_api;
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
         * Show the details of an existing paypal billing plan.
         * https://developer.paypal.com/docs/api/subscriptions/v1/#plans_get
         */
        public function get_paypal_billing_plan_details( $plan_id ){
            $endpoint = '/v1/billing/plans/' . $plan_id;
            $params = array();
            $response = $this->paypal_req_api->get($endpoint, $params);
            if ( $response !== false){
                $plan_details = $response;
                //echo '<br />Plan ID: ' . $plan_details->id;
                //echo '<br />Product ID: ' . $plan_details->product_id;
                return $plan_details;
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

        /*
         * Creates a PayPal subscription for a user (for the given plan_id).
         */
        public function create_paypal_subscription_for_billing_plan( $plan_id, $data = array(), $additional_args = array()){
            //https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_create

            $endpoint = '/v1/billing/subscriptions';

            $params = array(
                'plan_id' => $plan_id,
                'application_context' => array(
                    'user_action' => 'SUBSCRIBE_NOW', //SUBSCRIBE_NOW will activate the subscription immediately.
                    'payment_method' => array(
                        'payer_selected' => 'PAYPAL',
                        'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
                    ),
                ),
            );

            //Simple params (useulf for testing)
            // $params = array(
            //     'plan_id' => $plan_id
            // );            

            //Do the API call.
            $response = $this->paypal_req_api->post($endpoint, $params, $additional_args);

            //Check if we need to return the body or raw response instead of just the order ID.
            if( isset($additional_args['return_raw_response']) ||  isset( $additional_args['return_response_body'] ) ){
                //Instead of just the subscription ID; return the raw response or the response body (that came back from the post method) 
                return $response;
            }

            //Return the standard response.
            if ( $response !== false){
                //Response is a success!
                //JSON decode the response body to an object.
                $json_response_body = json_decode( wp_remote_retrieve_body( $response ) );
                $created_sub_id = $json_response_body->id;
                SwpmLog::log_simple_debug('Create-subscription response. Subscription ID: '. $created_sub_id, true);
                return $created_sub_id;
            } else {
                //There was a WP Error with the remote request. Enable debug logging to get more details from the log file.             
                return false;
            }

        }

        /*
         * Show the details of a paypal subscription.
         * https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_get
         */
        public function get_paypal_subscription_details( $sub_id ){
            $endpoint = '/v1/billing/subscriptions/' . $sub_id;
            $params = array();
            $response = $this->paypal_req_api->get($endpoint, $params);
            if ( $response !== false){
                $sub_details = $response;
                //$sub_details->billing_info contains the useful info
                //https://developer.paypal.com/docs/api/subscriptions/v1/#definition-subscription_billing_info
                
                return $sub_details;
            } else {
                return false;
            }
        }
        

        /*
         * Gets a list of all the transactions of a paypal subscription.
         * @return - an array of transactions object on success.
         */
        public function get_paypal_subscription_transactions_list( $sub_id, $start_time, $end_time = '' ){
            //https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_transactions

            $endpoint = '/v1/billing/subscriptions/'.$sub_id.'/transactions';

            //If end_time is not provided, then use the current time.
            if( empty($end_time) ){
                $end_time = date('c');//Current time in ISO 8601 format (Example: 2024-02-27T03:28:34+00:00)
            }

            $params = array(
                'start_time' => $start_time,
                'end_time' => $end_time,
            );

            //Do the API call.
            $response = $this->paypal_req_api->get($endpoint, $params);

            if ( $response !== false){
                //Get the array of transactions.
                $transactions = $response->transactions;
                // foreach($transactions as $txn){
                //    echo '<br />Txn ID: ' . $txn->id;
                // }
                return $transactions;
            } else {
                return false;
            }
        }

        /*
         * Cancel a paypal subscription (for the given subscription ID).
         * https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_cancel
         */
        public function cancel_paypal_subscription( $sub_id ){
            $endpoint = '/v1/billing/subscriptions/' . $sub_id . '/cancel';

            //A successful cancel request returns the HTTP '204 No Content' status code with no JSON response body.
            //We need to setup the additional args to return the raw response (so the post function doesn't try to process the response using the usual method).
            $additional_args = array('return_raw_response' => true);
            //We also need to pass the reason for the cancellation.
            $params = array('reason' => 'User requested to cancel the subscription.');

            //Do the API call.
            $response = $this->paypal_req_api->post($endpoint, $params, $additional_args);

            if(isset($response['response']['code']) && $response['response']['code'] == 204){
                //The subscription was successfully cancelled.
                return true;
            } else {
                //Failed to cancel the subscription.
                return false;
            }
        }

        /*
         * Show the details of a paypal order/transaction.
         * https://developer.paypal.com/docs/api/orders/v2/#orders_get
         */
        public function get_paypal_order_details( $order_id ){
            $endpoint = '/v2/checkout/orders/' . $order_id;
            $params = array();
            $response = $this->paypal_req_api->get($endpoint, $params);
            if ( $response !== false){
                $order_details = $response;
                //https://developer.paypal.com/docs/api/orders/v2/#orders-get-response
                return $order_details;
            } else {
                return false;
            }
        }
        
        /*
        * Creates a PayPal order. Returns the order ID if successful.
        * The $additional_args array can be used to pass additional arguments to the function to return the raw response or response body.
        */
        public function create_paypal_order_by_url_and_args( $data, $additional_args = array()){
            $payment_amount = isset($data['payment_amount']) ? $data['payment_amount'] : '';
            $quantity = isset($data['quantity']) ? $data['quantity'] : 1;
            $currency = isset($data['currency']) ? $data['currency'] : 'USD';
            $item_name = isset($data['item_name']) ? $data['item_name'] : '';
            $digital_goods_enabled = isset($data['digital_goods_enabled']) ? $data['digital_goods_enabled'] : 1;

            //Digital Goods value. Trigger a filter to allow other plugins to modify the digital goods value.
            $item_category = $digital_goods_enabled ? 'DIGITAL_GOODS' : 'PHYSICAL_GOODS';
            $item_category = apply_filters('swpm_paypal_ppcp_order_item_category', $item_category, $data);
            
            //Set the shipping preference. Trigger a filter to allow other plugins to modify the shipping preference.
            $shipping_preference = isset($data['shipping_preference']) ? $data['shipping_preference'] : 'GET_FROM_FILE';
            $shipping_preference = apply_filters('swpm_paypal_ppcp_order_shipping_preference', $shipping_preference, $data);

            //Preparation/normalization of any order data (suitable for PayPal API).
            $item_amount_formatted = number_format((float)$payment_amount, 2, '.', '');
            $grand_total_formatted = number_format((float)$payment_amount, 2, '.', '');
            //In the context of our SWPM plugin, the item total is the same as the grand total (since we don't have any quantity, shipping or tax at the moment).
            $sub_total_formatted = number_format((float)$payment_amount, 2, '.', '');

            //https://developer.paypal.com/docs/api/orders/v2/#orders_create
            $order_data = [
                "intent" => "CAPTURE",
                "payment_source" => [
                    "paypal" => [
                        "experience_context" => [
                            "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                            "shipping_preference" => $shipping_preference,
                            "user_action" => "PAY_NOW",
                        ]
                    ]
                ],
                "purchase_units" => [
                    [
                        "amount" => [
                            "value" => $grand_total_formatted,/* The grand total that will be charged for the transaction */
                            "currency_code" => $currency,
                            "breakdown" => [
                                "item_total" => [
                                    "currency_code" => $currency,
                                    "value" => $sub_total_formatted,
                                ]
                            ]
                        ],
                        "items" => [
                            [
                                "name" => $item_name,
                                "quantity" => $quantity,
                                "category" => $item_category,
                                "unit_amount" => [
                                    "value" => $item_amount_formatted,
                                    "currency_code" => $currency,
                                ]
                            ]
                        ],
                        "description" => $item_name,
                    ]
                ]
            ];

            //A simple order data for testing            
            // $order_data = [
            //     "intent" => "CAPTURE",
            //     "purchase_units" => [
            //         [
            //             amount => [
            //             currency_code => "USD",
            //             value => "100.00",
            //             ],
            //         ],
            //     ],
            // ];

            //Get the environment mode.
            $environment_mode = SWPM_PayPal_Utility_Functions::get_api_environment_mode_from_settings();

            //Get the bearer/access token.
            $bearer = SWPM_PayPal_Bearer::get_instance();
            $bearer_token = $bearer->get_bearer_token( $environment_mode );
            $access_token = $bearer_token;
            
            //Args
            $args = array(
                    'method'  => 'POST',
                    'headers' => array(
                            'Authorization' => 'Bearer ' . $access_token,
                            'Content-Type'  => 'application/json',
                            'PayPal-Partner-Attribution-Id' => 'TipsandTricks_SP_PPCP',
                    ),
            );

            $args['body'] = wp_json_encode( $order_data );
        
            //PayPal create-order using URL and Args
            //Get the API base URL.
            $api_base_url = SWPM_PayPal_Utility_Functions::get_api_base_url_by_environment_mode( $environment_mode );            
            $url = trailingslashit( $api_base_url ) . 'v2/checkout/orders';
            
            SwpmLog::log_simple_debug('Executing order create (v2/checkout/orders) using URL and args.', true);
            $response = SWPM_PayPal_Request_API::send_request_by_url_and_args( $url, $args );

            //Check if we need to return the body or raw response instead of just the order ID.
            if( isset($additional_args['return_raw_response']) && $additional_args['return_raw_response'] ){
                //Return the raw response instead of just the order ID.
                return $response;
            } else if ( isset( $additional_args['return_response_body'] ) && $additional_args['return_response_body'] ){
                //Return the response body instead of just the order ID.
                return wp_remote_retrieve_body( $response );
            }
            
            //Return the standard response.
            if ( $response !== false){
                //Response is a success!
                $json_response_body = json_decode( wp_remote_retrieve_body( $response ) );
                $created_order_id = $json_response_body->id;
                SwpmLog::log_simple_debug('Order-create response. Order ID: '. $created_order_id, true);
                return $created_order_id;
            } else {
                //There was a WP Error with the remote request. Enable debug logging to get more details from the log file.             
                return false;
            }
        }

        public function capture_paypal_order( $order_id, $additional_args = array() ){
            if( empty($order_id) ){
                SwpmLog::log_simple_debug('Empty PayPal order ID received. cannot process this request.', false);
                return false;
            }
            $order_data = array( 'order_id' => $order_id );

            //For the capture request, we need to pass the PayPal-Request-Id header.
            $additional_args['PayPal-Request-Id'] = $order_id;

            //https://developer.paypal.com/docs/api/orders/v2/#orders_capture
            $endpoint = '/v2/checkout/orders/' . $order_id . '/capture';
            $response = $this->paypal_req_api->post($endpoint, $order_data, $additional_args);

            //Check if we need to return the raw response or body (set in additional args).
            if( isset($additional_args['return_raw_response']) || $additional_args['return_response_body'] ){
                //Return whatever we got from the API call according to the additional args.
                return $response;
            }

            //Return the standard response.
            if ( $response !== false){
                //Response is a success!
                $capture_id = $response->id;//Capture ID/Transaction ID.
                return $capture_id;
            } else {
                //Failed to capture the order. The process_request_result() function has debug lines to reveal more details.
                return false;
            }

        }        
        
}
