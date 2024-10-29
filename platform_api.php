<?php 

    // create action for abandoned cart API
    add_action("wp_ajax_ait_abandonedcart", "aitrillion_abandonedcart");
    add_action("wp_ajax_nopriv_ait_abandonedcart", "aitrillion_abandonedcart");

    // create filter for authentication check on each API call
    add_filter( 'rest_authentication_errors', 'aitrillion_auth_check', 99 );

    // crete API end point
    add_action('rest_api_init', function () {
        
        // register rest API end point and callback functions
        register_rest_route( 'aitrillion/v1', 'getshopinfo',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getStoreDetail',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getcustomers',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getCustomers',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getcategories',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getCategories',
                    'permission_callback' => '__return_true' 
        ));

        register_rest_route( 'aitrillion/v1', 'getproducts',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getProducts',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getorders',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getOrders',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getcategorycollection',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getCategoryCollection',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'searchcategory',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_searchCategory',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'getproduct',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_getproductbyid',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'updatescriptversion',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_updateScriptVersion',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'blockloyaltymember',array(
                    'methods'  => 'GET',
                    'callback' => 'aitrillion_blockLoyaltyMember',
                    'permission_callback' => '__return_true'
        ));

        register_rest_route( 'aitrillion/v1', 'createcoupon',array(
                    'methods'  => 'POST',
                    'callback' => 'aitrillion_createcoupon',
                    'permission_callback' => '__return_true'
        ));

        
    });


/* 
* Check header authentication
*/

function aitrillion_auth_check(){
    
    $request_user = '';
    $request_pw = '';

    $routes = ltrim( $GLOBALS['wp']->query_vars['rest_route'], '/' );
    if(strpos($routes, "aitrillion/v1") === false){ // If request not for Aitrillion plugin then just return true.
        return true;
    }
    // get header auth username and password
    if(isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])){
        $request_user = $_SERVER["PHP_AUTH_USER"];
        $request_pw = $_SERVER["PHP_AUTH_PW"];
    }

    // get aitrillion key and password from store settings
    $api_key = get_option('_aitrillion_api_key');
    $api_pw = get_option('_aitrillion_api_password');

    if($api_key && $api_pw){

        $domain = preg_replace("(^https?://)", "", site_url() );

        $url = AITRILLION_END_POINT.'validate?shop_name='.$domain;

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $api_key.':'.$api_pw )
            )
        ));

        //echo '<br>response: <pre>'; print_r($response); echo '</pre>';

        // if error in response, return error message
        if( is_wp_error( $response ) ) {
            
            $error_message = $response->get_error_message();

            $return['result'] = false;
            $return['message'] = $error_message;
            
            echo json_encode($return);
            exit;

        }else{
            $r = json_decode($response['body']);    
        }

        if(isset($r->status) && $r->status != 'sucess'){

            $return['result'] = false;
            $return['message'] = 'Invalid api username or password';
            
            echo json_encode($return);
            exit;
        }

        // if header auth key and store key are not matched, throw error message
        if(($request_user != $api_key) || ($request_pw != $api_pw)){

            $return['result'] = false;
            $return['message'] = 'Invalid api username or password';
            
            echo json_encode($return);
            exit;

        }else{

            // if API key are valid, return success
            return true;
        }
    }else{
        $return['result'] = false;
        $return['message'] = 'API key not defined in AiTrillion settings';
        
        echo json_encode($return);
        exit;
    }
}


/**
* get store detail API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response store detail
*/
function aitrillion_getStoreDetail(WP_REST_Request $request){

    $endpoint = $request->get_route();

    $return['shop_name'] = DOMAIN;
    $return['shop_type'] = 'woocommerce';
    $return['shop_owner'] = '';

    $super_admins = get_super_admins();

    // if there are more than one super admin, select first super admin as shop owner
    if($super_admins){
        $admin = $super_admins[0];
        $admin_user = get_user_by('login', $admin);

        if($admin_user){
            $return['shop_owner'] = $admin_user->display_name;
        }
    }
    
    $store_city        = get_option( 'woocommerce_store_city' );
    $store_postcode    = get_option( 'woocommerce_store_postcode' );

    // The country/state
    $store_raw_country = get_option( 'woocommerce_default_country' );

    // Split the country/state
    $split_country = explode( ":", $store_raw_country );

    // Country and state separated:
    $store_country = $split_country[0];
    $store_state   = $split_country[1];

    $return['country'] = $store_country;
    $return['city'] = $store_city;
    $return['zip'] = $store_postcode;
    $return['phone'] = '';
    $return['store_name'] = get_bloginfo('name');
    $return['email'] = get_bloginfo( 'admin_email' );

    $return['shop_currency'] = get_woocommerce_currency();
    $return['money_format'] = html_entity_decode(get_woocommerce_currency_symbol()).'{{amount}}';

    //$return['install_date'] = '';
    $return['created_at'] = date('Y-m-d H:i:s');

    $response = new WP_REST_Response($return);
    $response->set_status(200);


    $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
    $log_message .= 'Get Shop Info '.$endpoint.PHP_EOL.'return: '.print_r($return, true);

    aitrillion_api_log($log_message);

    return $response;
}

/**
* get customers API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response store detail
*/
function aitrillion_getCustomers(WP_REST_Request $request){

    // get API params
    $params = $request->get_query_params();

    // set default result type as row, if result type not provided
    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    $updated_at = array();
    
    // filter result on updated at, if parameter passed
    if(isset($params['updated_at']) && !empty($params['updated_at'])){
        $updated_at = array( 
                            array( 'after' => $params['updated_at'], 'inclusive' => true )  
                        );
    }

    // if result type count, return total customer count
    if($params['result_type'] == 'count'){

        $customer_query = new WP_User_Query(
          array(
             'fields' => 'ID',
             'role' => 'customer',    
             'date_query' => $updated_at,      
          )
        );

        $customers = $customer_query->get_results();

        $return = array();

        $return['result'] = true;
        $return['customers']['count'] = count($customers);


        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Customer API: result_type Count .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }

    // if result type row, return total customer data
    if($params['result_type'] == 'row'){

        // define result filter variables
        if(isset($params['page'])  && !empty($params['page'])){
            $paged = $params['page'];
        }else{
            $paged = 1;
        }

        if(isset($params['limit'])  && !empty($params['limit'])){
            $limit = $params['limit'];
        }else{
            $limit = 10;
        }

        if($paged == 1){
            $offset = 0;  
        }else {
            $offset = ($paged-1) * $limit;
        }
        
        $customer_query = new WP_User_Query(
          array(
             'fields' => 'ID',
             'role' => 'customer',
             'paged' => $paged,
             'number' => $limit,
             'offset' => $offset,
             'date_query' => $updated_at, 
             /*'meta_query' => array(
                    array(
                        'key' => '_aitrillion_sync',
                        'compare' => 'NOT EXISTS' // this should work...
                    ),
                )*/
          )
        );

        $customers = $customer_query->get_results();

        if(count($customers) > 0){

            $return = array();

            foreach ( $customers as $customer_id ) {

                // get customer data from common function
                $c = aitrilltion_get_customer( $customer_id );

                $return['customers'][] = $c;

                update_user_meta($customer_id, '_aitrillion_user_sync', 'true');
                update_user_meta($customer_id, '_aitrillion_sync_date', date('Y-m-d H:i:s'));
            }

            $return['result'] = true;

            $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
            $log_message .= 'Get Customer API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

            aitrillion_api_log($log_message);

            $response = new WP_REST_Response($return);
            $response->set_status(200);

            return $response;

        }else{
            $return = array();

            $return['status'] = false;
            $return['msg'] = 'No Customer found';

            $response = new WP_REST_Response($return);
            $response->set_status(200);

            return $response;
        }

       
    }
   
}

/**
* get category API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response category detail
*/
function aitrillion_getCategories(WP_REST_Request $request){

    // get API params
    $params = $request->get_query_params();

    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    // define query parameters
    $taxonomy     = 'product_cat';
    $orderby      = 'name';  
    $show_count   = 0;      // 1 for yes, 0 for no
    $pad_counts   = 0;      // 1 for yes, 0 for no
    $hierarchical = 1;      // 1 for yes, 0 for no  
    $title        = '';  
    $empty        = 0;

    $args = array(
         'taxonomy'     => $taxonomy,
         'orderby'      => $orderby,
         'show_count'   => $show_count,
         'pad_counts'   => $pad_counts,
         'hierarchical' => $hierarchical,
         'title_li'     => $title,
         'hide_empty'   => $empty
    );


    // define result filter variables
    if(isset($params['updated_at']) && !empty($params['updated_at'])){
        $args['date_created'] = '>'.$params['updated_at'];
    }

    if(isset($params['page']) && !empty($params['page'])){
        $paged = $params['page'];
    }else{
        $paged = 1;
    }

    if($paged == 1){
        $offset = 0;  
    }else {
        $offset = ($paged-1) * $limit;
    }

    $args['offset'] = $offset;

    if(isset($params['limit']) && !empty($params['limit'])){
        $args['number'] = $params['limit'];
    }else{
        $args['number'] = 10;
    }

    //echo '<br>args: <pre>'; print_r($args); echo '</pre>';

    // get categories from wordpress standard function
    $all_categories = get_categories( $args );

    if($params['result_type'] == 'count'){

        $total_categories = count($all_categories);

        $return['result'] = true;
        $return['collections']['count'] = $total_categories;

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response; 
        exit;

    }

    //echo '<br>all_categories: <pre>'; print_r($all_categories); echo '</pre>';

    if(count($all_categories) > 0){

        $return['collections'] = array();

        foreach($all_categories as $category){

            $c = array();

            $c = aitrilltion_get_category($category);

            $return['collections'][] = $c;
        }

        //echo '<br>return: <pre>'; print_r($return); echo '</pre>';
    }

    $return['result'] = true;

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response; 
    exit;
}


/**
* get product API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response product detail
*/
function aitrillion_getProducts(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    if(isset($params['updated_at']) && !empty($params['updated_at'])){
        $args['date_created'] = '>'.$params['updated_at'];
    }

    if(isset($params['page']) && !empty($params['page'])){
        $args['page'] = $params['page'];
    }else{
        $args['page'] = 1;
    }

    if(isset($params['limit']) && !empty($params['limit'])){
        $args['limit'] = $params['limit'];
    }else{
        $args['limit'] = 10;
    }

    if($params['result_type'] == 'count'){

        unset($args['page']);
        $args['limit'] = '-1';

    }

    $args['status'] = 'publish';
    $products = wc_get_products( $args );

    if($params['result_type'] == 'count'){

        $return = array();

        $return['result'] = true;
        $return['products']['count'] = count($products);

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Product API: result_type Count .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response; 
        exit;

    }

    if($params['result_type'] == 'row'){

        $return = array();

        foreach ( $products as $product ){ 

            $p = array();

            $p = aitrillion_get_product($product);

            $return['products'][] = $p;

            $synced = get_post_meta( $product->get_id(), '_aitrillion_product_sync' );

            if ( !empty($synced) ) {
                update_post_meta($product->get_id(), '_aitrillion_product_sync', 'true');
            }else{
                add_post_meta( $product->get_id(), '_aitrillion_product_sync', 'true' );
            }

        }

        $return['result'] = true;

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Product API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

}

/**
* get order API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response order detail
*/

function aitrillion_getOrders(WP_REST_Request $request){

    $params = $request->get_query_params();

    if(!isset($params['result_type']) || empty($params['result_type'])){

        $params['result_type'] = 'row';
    }

    if($params['result_type'] == 'count'){
        
        // include all order status result
        $args['status'] = array_keys( wc_get_order_statuses() );
        // include all order result
        $args['limit'] = -1;

        if(isset($params['updated_at']) && !empty($params['updated_at'])){
            $args['date_created'] = '>'.$params['updated_at'];
        }

        $args['return'] = 'ids';
        $orders = wc_get_orders( $args );

        $return = array();

        $return['result'] = true;
        $return['orders']['count'] = count($orders);

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Order API: result_type Count .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }


    if($params['result_type'] == 'row'){

        // include all order status result
        $args['status'] = array_keys( wc_get_order_statuses() );

        if(isset($params['updated_at']) && !empty($params['updated_at'])){
            $args['date_modified'] = '>'.$params['updated_at'];
        }

        if(isset($params['page']) && !empty($params['page'])){
            $args['page'] = $params['page'];
        }else{
            $args['page'] = 1;
        }

        if(isset($params['limit']) && !empty($params['limit'])){
            $args['limit'] = $params['limit'];
        }else{
            $args['limit'] = 10;
        }

        $orders = wc_get_orders( $args );

        $o = array();

        foreach($orders as $order){

            $o = array();

            $o = aitrillion_get_order($order);

            if(!$o){
                continue;
            }

            $return['orders'][] = $o;

            update_post_meta($order->get_id(), '_aitrillion_order_sync', 'false');
            
        }

        $return['result'] = true;

        $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
        $log_message .= 'Get Order API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

        aitrillion_api_log($log_message);

        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;
    }

}

/**
* get products by category API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response product detail
*/
function aitrillion_getCategoryCollection( WP_REST_Request $request ){

    $params = $request->get_query_params();

    if(!isset($params['id']) || empty($params['id'])){

        $params['result_type'] = 'row';
    }

    $cat_id = $params['id'];

    if(isset($params['limit']) && !empty($params['limit'])){
        $limit = $params['limit'];
    }else{
        $limit = 10;
    }

    // prepare query parameter
    $args = array(
        'post_type'             => 'product',
        'post_status'           => 'publish',
        'posts_per_page'        => $limit,
        'tax_query'             => array(
            array(
                'taxonomy'      => 'product_cat',
                'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                'terms'         => $cat_id,
                'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
            )
        )
    );

    $products = new WP_Query($args);

    $return = array();

    foreach ($products->posts as $key => $post) {

        $product = wc_get_product( $post->ID );

        $p = aitrillion_get_product( $product );

        $return['products'][] = $p;

    }

    $return['result'] = true;

    $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
    $log_message .= 'Get Product By Category API: result_type row .'.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

    aitrillion_api_log($log_message);

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;

}

/**
* search categories by name API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response category detail
*/
function aitrillion_searchCategory(WP_REST_Request $request){

    $params = $request->get_query_params();

    $cat_name = $params['title'];

    // prepare query parameter
    $args['taxonomy'] = 'product_cat';
    $args['hide_empty'] = 0;
    $args['name__like'] = $cat_name;

    $categories = get_categories($args);

    $return = array();

    global $title;

    foreach($categories as $category){

        $cat['id'] = $category->term_id;
        $cat['handle'] = get_term_link( $category->term_id, 'product_cat' );
        $cat['body_html'] = $category->description;
        $cat['updated_at'] = '';
        $cat['published_at'] = '';
        $cat['product_count'] = $category->category_count;

        $name = array();

        if($category->parent > 0){
            // recursive function to get parent category name
            $name = hierarchical_category_tree($category->parent);
        }

        $name[] = $category->name;

        $cat['title'] = implode(' > ', $name);

        $title = array();

        $return[]['collections'] = $cat;
    }

    $log_message = '------------------------'.date('Y-m-d H:i:s').'----------------------------------'.PHP_EOL;
    $log_message .= 'Category search API: '.PHP_EOL.'params: '.print_r($params, true).PHP_EOL.'response: '.print_r($return, true);

    //aitrillion_api_log($log_message);

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;
}

/**
* recursive function to get category parent title
*
* @param int $cat parent category id
*
* @return string category name
*/
function hierarchical_category_tree( $cat ) {

    global $title;

        $args['taxonomy'] = 'product_cat';
        $args['hide_empty'] = 0;
        $args['term_taxonomy_id'] = $cat;

        $categories = get_categories($args);

        foreach($categories as $category){

            $title[] = $category->name;

            if($category->parent > 0){
                return hierarchical_category_tree($category->parent);
            }else{
                return array_reverse($title);
            }
        }
}

/**
* get product detail by id API function
*
* @param WP_REST_Request $request The log text
*
* @return WP_REST_Response product detail
*/
function aitrillion_getproductbyid(WP_REST_Request $request){

    $params = $request->get_query_params();

    $product_id = $params['id'];

    if(!$product_id){

        $return['error'] = true;
        $return['msg'] = 'No product id found';
        $response = new WP_REST_Response($return);
        $response->set_status(200);

        return $response;

    }else{

        $product = wc_get_product( $product_id );

        if(!$product){

            $return['error'] = true;
            $return['msg'] = 'No product found with this id';
            $response = new WP_REST_Response($return);
            $response->set_status(200);

            return $response;
        }

        $p = array();

        $p = aitrillion_get_product($product);

        //aitrillion_api_log('Product: '.$p.PHP_EOL);

        $json_payload = json_encode($p);

        $response = new WP_REST_Response($p);
        $response->set_status(200);

        return $response;
    }
    
}

/**
* update script version API function
*
*/
function aitrillion_updateScriptVersion(){

    // get current script verstion
    $script_version = get_option('_aitrillion_script_version');

    // increment in previous version if available or set as 1
    if(empty($script_version)){
        $script_version = 1;
    }else{
        $script_version++;    
    }

    update_option('_aitrillion_script_version', $script_version, false);

    $script_version = get_option('_aitrillion_script_version');

    $return['result'] = false;
    $return['script_version'] = $script_version;


    $log_message = 'Script version updated '.$script_version.PHP_EOL;

    aitrillion_api_log($log_message);

    echo json_encode($return);
    exit;

}

/**
* update blocked loyalty members id API function
*
* @param WP_REST_Request $request The log text
*
*/
function aitrillion_blockLoyaltyMember(WP_REST_Request $request){

    $params = $request->get_query_params();

    $member_ids = $params['member_ids'];

    update_option('_aitrillion_block_loyalty_members', $member_ids, false);

    $return['result'] = false;
    $return['message'] = 'ID Updated';

    $log_message = 'Loyalty block member updated '.$member_ids.PHP_EOL;

    aitrillion_api_log($log_message);

    echo json_encode($return);
    exit;

}

/**
* get abandoned cart detail API function
*
*
*/
function aitrillion_abandonedcart(){
 
    // get quote id from query string   
    $cart_id = sanitize_text_field($_GET['quoteid']);

    global $wpdb;

    // get all cart sessions
    $order_sessions = $wpdb->get_results( "SELECT * FROM ". $wpdb->prefix."woocommerce_sessions");

    $items = array();

    $abandonedcart = false;

    foreach ( $order_sessions as $order ) {

        $session_value = unserialize($order->session_value);

        // check if quote id is same as cart id, prepare abandoned cart detail
        if(isset($session_value['cart_id']) && $session_value['cart_id'] == $cart_id){

            $abandonedcart = true;

            $cart = unserialize($session_value['cart']);

            // if cart have items
            if(is_array($cart) && !empty($cart)){

                $i = 1;
                $total_price = 0;

                foreach($cart as $key => $cart_item){

                    $line_item['id'] = $i;
                    $line_item['product_id'] = $cart_item['product_id'];

                    // if item has variant, get variant detail
                    if(empty($cart_item['variation_id'])){
                        $line_item['variant_id'] = $cart_item['product_id'];

                        $product = wc_get_product( $cart_item['product_id'] );
                    }else{
                        $line_item['variant_id'] = $cart_item['variation_id'];

                        $product = wc_get_product( $cart_item['variation_id'] );
                    }

                    $line_item['quantity'] = $cart_item['quantity'];

                    $line_item['name'] = $product->get_name();
                    $line_item['title'] = $product->get_name();
                    $line_item['sku'] = $product->get_sku();
                    $line_item['price'] = $product->get_price();
                    $line_item['url'] = get_permalink( $product->get_id() );

                    $total_price = $line_item['price'] + $total_price;

                    $image_id        = $product->get_image_id();

                    $img = array();

                    if ( $image_id ) {
                        $image_url = wp_get_attachment_image_url( $image_id, 'full' );

                        $line_item['image'] = $image_url;
                    }else{
                        $line_item['image'] = '';
                    }

                    $items[] = $line_item;

                    $line_item = array();
                    $i++;
                }

                if($i == 1){
                    echo json_encode(array('msg' => 'no item found in abandoned cart'));
                    exit;
                }else{
                    $data['token'] = $cart_id;
                    $data['item_count'] = $i-1;
                    $data['items'] = $items;
                    $data['total_price'] = $total_price;

                    echo json_encode($data);
                    exit;

                }

            }
        }
        
    }

    if(!isset($i) && empty($i)){

        $return['result'] = false;
        $return['message'] = 'No data found';

        echo json_encode($return);
        exit;
    }

}

/**
* create discount coupon API function
*
* @param WP_REST_Request $request The log text
*
*/
function aitrillion_createCoupon(WP_REST_Request $request){

    $body = $request->get_body();

    $params = json_decode($body);

    $coupon = new WC_Coupon();

    if(isset($params->coupon_code) && !empty($params->coupon_code)){
        $code = $params->coupon_code;
        $coupon->set_code( $params->coupon_code );
    }else{
        $code = raitrillion_andom_strings(8);
        $coupon->set_code( $code );
    }
    
    if(isset($params->coupon_description) && !empty($params->coupon_description)){
        $coupon->set_description( $params->coupon_description );    
    }

    // discount type can be 'fixed_cart', 'percent' or 'fixed_product', defaults to 'fixed_cart'
    $coupon->set_discount_type( $params->discount_type );

    // discount amount
    $coupon->set_amount( $params->discount_amount );

    if(isset($params->allow_free_shipping) && !empty($params->allow_free_shipping)){
        // allow free shipping
        $coupon->set_free_shipping( true );
    }

    if(isset($params->coupon_expiry) && !empty($params->coupon_expiry)){
        // coupon expiry date
        $coupon->set_date_expires( $params->coupon_expiry );    
    }

    if(isset($params->cart_minimum_amount) && !empty($params->cart_minimum_amount)){
        // minimum spend
        $coupon->set_minimum_amount( $params->cart_minimum_amount );  
    }

    if(isset($params->cart_maximum_amount) && !empty($params->cart_maximum_amount)){
        // maximum spend
        $coupon->set_maximum_amount( $params->cart_maximum_amount );
    }

    if(isset($params->is_individual_use) && !empty($params->is_individual_use)){
        // individual use only
        $coupon->set_individual_use( $params->is_individual_use );
    }

    if(isset($params->product_ids) && !empty($params->product_ids)){
        // products
        $products = explode(',', $params->product_ids);
        $coupon->set_product_ids( $products );
    }    

    if(isset($params->exclude_product_ids) && !empty($params->exclude_product_ids)){
        $exclude_products = explode(',', $params->exclude_product_ids);

        // exclude products
        $coupon->set_excluded_product_ids( $exclude_products );
    } 

    if(isset($params->uses_limit) && !empty($params->uses_limit)){
        // usage limit per coupon
        $coupon->set_usage_limit( $params->uses_limit );
    }

    if(isset($params->per_user_limit) && !empty($params->per_user_limit)){
        // usage limit per user
        $coupon->set_usage_limit_per_user( $params->per_user_limit );
    }    

    if(isset($params->user_id) && !empty($params->user_id)){

        $user_info = get_userdata($params->user_id);

        if($user_info){

            // allowed emails
            $coupon->set_email_restrictions( 
                array( 
                    $user_info->user_email
                )
            );     
        }
    }
    

    $coupon->save();

    $return['result'] = true;
    $return['coupon_codes'] = array($code);

    $response = new WP_REST_Response($return);
    $response->set_status(200);

    return $response;
}

// This function will return a random
// string of specified length
function aitrillion_random_strings($length_of_string)
{
    // String of all alphanumeric character
    $str_result = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';

    // Shuffle the $str_result and returns substring
    // of specified length
    return substr(str_shuffle($str_result), 0, $length_of_string);
}

?>