<?php 


// If a cron job interval does not already exist, create one.
add_filter( 'cron_schedules', 'check_every_minute' );

function check_every_minute( $schedules ) {
    $schedules['every_minute'] = array(
        'interval' => 60, // in seconds
        'display'  => __( 'Every Minute' ),
    );

    return $schedules;
}

// Unless an event is already scheduled, create one.
add_action( 'init', 'aitrillion_data_sync_cron' );
 
function aitrillion_data_sync_cron() {

    if ( ! wp_next_scheduled( 'aitrillion_data_sync_schedule' ) ) {
        //wp_schedule_event( time(), 'every_minute', 'aitrillion_data_sync_schedule' );
        wp_schedule_single_event( time(), 'aitrillion_data_sync_schedule' );
    }
}

// call sync function on cron action 
add_action( 'aitrillion_data_sync_schedule', 'aitrillion_data_sync_action' );


/**
* cron data sync function, execute on each cron call
* 
*/
function aitrillion_data_sync_action() { 

    sync_new_customers();
    sync_updated_customers();  
    sync_deleted_customers();  

    sync_new_categories();
    sync_updated_categories();
    sync_deleted_categories();

    sync_new_products();
    sync_updated_products();  
    sync_deleted_products(); 

    sync_new_orders();
    sync_updated_orders();
    sync_deleted_orders();

    sync_shop_update();

}

/**
* sync new customers
*
*/
function sync_new_customers(){
    
    // get ids of new customer registered since last cron call
    $users = get_option( '_aitrillion_created_users' );

    aitrillion_api_log('new user sync log '.print_r($users, true).PHP_EOL);

    // variable to store failed sync users id
    $failed_sync_users = array();

    if(!empty($users)){

        // remove duplicate ids
        $users = array_unique($users);

        $synced_users = array();

        foreach($users as $user_id){

            aitrillion_api_log('user id '.$user_id.PHP_EOL);

            $c = array();

            // get customer data from common function
            $c = aitrilltion_get_customer( $user_id );
            
            $json_payload = json_encode($c);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $url = AITRILLION_END_POINT.'customers/create';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed user into separate variable
            if(!isset($r->status) && $r->status != 'success'){

                $failed_sync_users_data[$user_id][] = array('user_id' => $user_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                
                $failed_sync_users[] = $user_id;

            }else{

                // flag this user as synced successfully
                update_user_meta($user_id, '_aitrillion_user_sync', 'true');
                $synced_users[] = $user_id;
            }

            aitrillion_api_log('API Response for user id: '.$user_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_users)){
            // if there are failed sync users, add them into next cron queue
            update_option('_aitrillion_created_users', $failed_sync_users, false);
            update_option('_aitrillion_failed_sync_users', $failed_sync_users_data, false);

        }else{

            // all user synced successfully, clear queue
            delete_option('_aitrillion_created_users');    
        }
    }
}


/**
* sync modified customers
*
*/
function sync_updated_customers(){

    // get ids of modified customers since last cron call
    $users = get_option( '_aitrillion_updated_users' );

    aitrillion_api_log('udpated users sync log '.print_r($users, true).PHP_EOL);

    $failed_sync_users = array();

    if(!empty($users)){

        // remove duplicate ids
        $users = array_unique($users);

        $synced_users = array();

        foreach($users as $user_id){

            $c = array();

            // get customer data from common function
            $c = aitrilltion_get_customer( $user_id );

            //aitrillion_api_log('customer '.print_r($c, true).PHP_EOL);
            
            $json_payload = json_encode($c);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'customers/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed user into separate variable
            if(!isset($r->status) && $r->status != 'success'){
                $failed_sync_users_data[$user_id][] = array('user_id' => $user_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_users[] = $user_id;
            }else{
                // flag this user as synced successfully
                update_user_meta($user_id, '_aitrillion_user_sync', 'true');
                $synced_users[] = $user_id;
            }

            aitrillion_api_log('API Response for user id: '.$user_id.PHP_EOL.print_r($r, true));
        }
    }

    if(!empty($failed_sync_users)){
        
        // if there are failed sync users, add them into next cron queue
        update_option('_aitrillion_updated_users', $failed_sync_users, false);
        update_option('_aitrillion_failed_sync_users', $failed_sync_users_data, false);

    }else{

        // all user synced successfully, clear queue
        delete_option('_aitrillion_updated_users');    
    }
}

/**
* sync deleted customers
*
*/
function sync_deleted_customers(){

    // get ids of deleted customers since last cron call
    $deleted_users = get_option( '_aitrillion_deleted_users' );

    aitrillion_api_log('deleted users sync log: '.print_r($deleted_users, true).PHP_EOL);

    $failed_sync_users = array();

    if(!empty($deleted_users)){

        // remove duplicate ids
        $deleted_users = array_unique($deleted_users);

        foreach($deleted_users as $k => $user_id){

            //aitrillion_api_log('user delete id: '.$user_id.PHP_EOL);

            $json_payload = json_encode(array('id' => $user_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'customers/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed user into separate variable
            if(!isset($r->status) && $r->status != 'success'){
                $failed_sync_users_data[$user_id][] = array('user_id' => $user_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_users[] = $user_id;
            }

            aitrillion_api_log('Delete customer API Response for user id: '.$user_id.PHP_EOL.print_r($r, true));

        }

        if(!empty($failed_sync_users)){
        
            // if there are failed sync users, add them into next cron queue
            update_option('_aitrillion_deleted_users', $failed_sync_users, false);
            update_option('_aitrillion_failed_sync_users', $failed_sync_users_data, false);

        }else{

            // all user synced successfully, clear queue
            delete_option('_aitrillion_deleted_users');    
        }
    }

}

/**
* sync new products
*
*/
function sync_new_products(){

    // get ids of new products created since last cron call
    $products = get_option( '_aitrillion_created_products' );

    aitrillion_api_log('new product sync log '.print_r($products, true).PHP_EOL);

    // variable to store failed sync product id
    $failed_sync_products = array();

    if(!empty($products)){

        // remove duplicate ids
        $products = array_unique($products);

        foreach($products as $product_id){

            $p = array();

            $product = wc_get_product( $product_id );

            $p = aitrillion_get_product( $product ); 

            //aitrillion_api_log('Product: '.$p.PHP_EOL);

            $json_payload = json_encode($p);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'products/create';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed product into separate variable
            if(!isset($r->status) && $r->status != 'success'){
                $failed_sync_products_data[$product_id][] = array('product_id' => $product_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_products[] = $product_id;
            }else{

                // flag this product as synced successfully
                update_post_meta($product_id, '_aitrillion_product_sync', 'true');
                $synced_products[] = $product_id;
            }

            aitrillion_api_log('API Response for product id: '.$product_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_products)){
        
            // if there are failed sync products, add them into next cron queue
            update_option('_aitrillion_created_products', $failed_sync_products);
            update_option('_aitrillion_failed_sync_products', $failed_sync_products_data);

        }else{

            // all products synced successfully, clear queue
            delete_option('_aitrillion_created_products');    
        }
    }

}

/**
* sync modified products
*
*/
function sync_updated_products(){

    // get ids of modified products created since last cron call
    $products = get_option( '_aitrillion_updated_products' );

    aitrillion_api_log('updated product sync log '.print_r($products, true).PHP_EOL);

    // variable to store failed sync product id
    $failed_sync_products = array();

    if(!empty($products)){

        // remove duplicate ids
        $products = array_unique($products);

        aitrillion_api_log('unique products: '.print_r($products, true).PHP_EOL);

        foreach($products as $product_id){

            aitrillion_api_log('Product id: '.$product_id.PHP_EOL);

            $p = array();

            $product = wc_get_product( $product_id );

            $product_status = $product->get_status();

            if($product_status != 'publish'){
                continue;
            }

            $p = aitrillion_get_product( $product ); 

            $json_payload = json_encode($p);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'products/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed product into separate variable
            if(!isset($r->status) && $r->status != 'success'){
                $failed_sync_products_data[$product_id][] = array('product_id' => $product_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_products[] = $product_id;
            }else{

                // flag this product as synced successfully
                update_post_meta($product_id, '_aitrillion_product_sync', 'true');
                $synced_products[] = $product_id;
            }

            aitrillion_api_log('API Response for product id: '.$product_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_products)){
        
            // if there are failed sync products, add them into next cron queue
            update_option('_aitrillion_updated_products', $failed_sync_products);
            update_option('_aitrillion_failed_sync_products', $failed_sync_products_data);

        }else{

            // all products synced successfully, clear queue
            delete_option('_aitrillion_updated_products');    
        }
    }
}


/**
* sync deleted products
*
*/
function sync_deleted_products(){

    // get ids of deleted products since last cron call
    $deleted_products = get_option( '_aitrillion_deleted_products' );

    aitrillion_api_log('deleted product sync log: '.print_r($deleted_products, true).PHP_EOL);

    // variable to store failed sync product id
    $failed_sync_products = array();

    if(!empty($deleted_products)){

        // remove duplicate ids
        $deleted_products = array_unique($deleted_products);

        aitrillion_api_log('deleted product not empty: '.PHP_EOL);

        foreach($deleted_products as $k => $post_id){

            if( get_post_type( $post_id ) != 'product' ) return;

            $json_payload = json_encode(array('id' => $post_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'products/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed product into separate variable
            if(!isset($r->status) && $r->status != 'success'){
                $failed_sync_products_data[$product_id][] = array('product_id' => $post_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_products[] = $post_id;
            }

            aitrillion_api_log('Product Delete: product id: '.$post_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_products)){
        
            // if there are failed sync products, add them into next cron queue
            update_option('_aitrillion_deleted_products', $failed_sync_products, false);
            update_option('_aitrillion_failed_sync_products', $failed_sync_products_data, false);

        }else{

            // all user synced successfully, clear queue
            delete_option('_aitrillion_deleted_products');    
        }

        aitrillion_api_log('after product delete, delete option : '.PHP_EOL);
    }
}

/**
* sync new categories
*
*/
function sync_new_categories(){

    // get ids of new categories since last cron call
    $categories = get_option( '_aitrillion_created_categories' );

    aitrillion_api_log('new category sync log '.print_r($categories, true).PHP_EOL);

    $failed_sync_categories = array();

    if(!empty($categories)){

        // remove duplicate ids
        $categories = array_unique($categories);
        $synced_categories = array();

        foreach($categories as $category_id){

            //echo '<br>category_id: '.$category_id;

            $c = array();

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
                 'hide_empty'   => $empty,
                 'term_taxonomy_id'   => $category_id
            );

            $category = get_categories( $args );

            //echo '<pre>'; print_r($category[0]); echo '</pre>';

            if(!$category[0]){
                continue;
            }

            $c = aitrilltion_get_category( $category[0] );

            //aitrillion_api_log('category '.print_r($c, true).PHP_EOL);
            
            $json_payload = json_encode($c);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $url = AITRILLION_END_POINT.'collections/create';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            if(!isset($r->status) && $r->status != 'success'){
                // if sync failed, store failed categories into separate variable
                $failed_sync_category_data[$category_id][] = array('category_id' => $category_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_categories[] = $category_id;
            }else{
                // flag this category as synced successfully
                update_term_meta($category_id, '_aitrillion_category_sync', 'true');
                $synced_categories[] = $category_id;
            }

            aitrillion_api_log('API Response for category id: '.$category_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_categories)){
            // if there are failed sync category, add them into next cron queue
            update_option('_aitrillion_created_categories', $failed_sync_categories, false);
            update_option('_aitrillion_failed_sync_categories', $failed_sync_category_data, false);

        }else{

            // all categories synced successfully, clear queue
            delete_option('_aitrillion_created_categories');    
        }
    }
}

/**
* sync modified categories
*
*/
function sync_updated_categories(){
    
    // get ids of modified categories since last cron call
    $categories = get_option( '_aitrillion_updated_categories' );

    aitrillion_api_log('update category sync log '.print_r($categories, true).PHP_EOL);

    $failed_sync_categories = array();

    if(!empty($categories)){

        // remove duplicate ids
        $categories = array_unique($categories);
        $synced_categories = array();

        foreach($categories as $category_id){

            //echo '<br>category_id: '.$category_id;

            $c = array();

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
                 'hide_empty'   => $empty,
                 'term_taxonomy_id'   => $category_id
            );

            $category = get_categories( $args );

            //echo '<pre>'; print_r($category[0]); echo '</pre>';

            if(!$category[0]){
                continue;
            }

            $c = aitrilltion_get_category( $category[0] );

            //aitrillion_api_log('category '.print_r($c, true).PHP_EOL);
            
            $json_payload = json_encode($c);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $url = AITRILLION_END_POINT.'collections/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            if(!isset($r->status) && $r->status != 'success'){
                // if sync failed, store failed categories into separate variable
                $failed_sync_category_data[$category_id][] = array('category_id' => $category_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_categories[] = $category_id;
            }else{
                // flag this category as synced successfully
                update_term_meta($category_id, '_aitrillion_category_sync', 'true');
                $synced_categories[] = $category_id;
            }

            aitrillion_api_log('API Response for category id: '.$category_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_categories)){
            // if there are failed sync category, add them into next cron queue
            update_option('_aitrillion_updated_categories', $failed_sync_categories);
            update_option('_aitrillion_failed_sync_categories', $failed_sync_category_data);

        }else{

            // all categories synced successfully, clear queue
            delete_option('_aitrillion_updated_categories');    
        }
    }
}


/**
* sync deleted categories
*
*/
function sync_deleted_categories(){

    // get ids of deleted categories since last cron call
    $deleted_categories = get_option( '_aitrillion_deleted_categories' );

    aitrillion_api_log('deleted categories sync log: '.print_r($deleted_categories, true).PHP_EOL);

    $failed_sync_categories = array();

    if(!empty($deleted_categories)){

        $deleted_categories = array_unique($deleted_categories);

        foreach($deleted_categories as $k => $category_id){

            //aitrillion_api_log('user delete id: '.$user_id.PHP_EOL);

            $json_payload = json_encode(array('id' => $category_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'collections/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            if(!isset($r->status) && $r->status != 'success'){
                // if sync failed, store failed categories into separate variable
                $failed_sync_category_data[$category_id][] = array('category_id' => $category_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_categories[] = $category_id;
            }

            aitrillion_api_log('API Response for category id: '.$category_id.PHP_EOL.print_r($r, true));

        }

        if(!empty($failed_sync_categories)){
        
            // if there are failed sync category, add them into next cron queue
            update_option('_aitrillion_deleted_categories', $failed_sync_categories, false);
            update_option('_aitrillion_failed_sync_categories', $failed_sync_category_data, false);

        }else{

            // all user synced successfully, clear queue
            delete_option('_aitrillion_deleted_categories');    
        }
    }

}

/**
* sync new orders
*
*/
function sync_new_orders(){

    // get ids of new order created since last cron call
    $orders = get_option( '_aitrillion_created_orders' );

    aitrillion_api_log('new order sync log '.print_r($orders, true).PHP_EOL);

    // variable to store failed sync order id
    $failed_sync_order = array();

    if(!empty($orders)){

        // remove duplicate ids
        $orders = array_unique($orders);

        foreach($orders as $order_id){

            $order = wc_get_order( $order_id );
            
            if(!is_object($order)){
                continue;
            }

            $o = array();

            $o = aitrillion_get_order($order);

            if(!$o){
                continue;
            }

            $json_payload = json_encode($o);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'orders/create';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed order into separate variable
            if(!isset($r->status) && $r->status != 'success'){
                $failed_sync_orders_data[$order_id][] = array('order_id' => $order_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_order[] = $order_id;
            }else{

                // flag this order as synced successfully
                update_post_meta($order_id, '_aitrillion_order_sync', 'true');
                $synced_orders[] = $order_id;
                removeValueFromSession($order_id,'_aitrillion_created_orders');
            }

            aitrillion_api_log('API Response for order id: '.$order_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_order)){
        
            // if there are failed sync orders, add them into next cron queue
            update_option('_aitrillion_created_orders', $failed_sync_order, false);
            update_option('_aitrillion_failed_sync_orders', $failed_sync_orders_data, false);

        }else{

            // all order synced successfully, clear queue
            delete_option('_aitrillion_created_orders');   
             
            update_post_meta($order_id, '_aitrillion_order_sync', 'true');
        }
    }
}

/**
* sync modified orders
*
*/
function sync_updated_orders(){

    // get ids of modified orders created since last cron call
    $orders = get_option( '_aitrillion_updated_orders' );

    aitrillion_api_log('updated order sync log '.print_r($orders, true).PHP_EOL);

    // variable to store failed sync order id
    $failed_sync_order = array();

    if(!empty($orders)){

        // remove duplicate ids
        $orders = array_unique($orders);

        foreach($orders as $order_id){
            
            $order = wc_get_order( $order_id );

            $o = aitrillion_get_order($order);

            if(!$o){
                continue;
            }

            $json_payload = json_encode($o);

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'orders/update';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed order into separate variable
            if(!isset($r->status) && $r->status != 'success'){

                $failed_sync_orders_data[$order_id][] = array('order_id' => $order_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_order[] = $order_id;

            }else{

                // flag this order as synced successfully
                update_post_meta($order_id, '_aitrillion_order_sync', 'true');
                $synced_orders[] = $order_id;
            }

            aitrillion_api_log('order updated: order id '.$order_id.PHP_EOL.print_r($r, true));
        }

        if(!empty($failed_sync_order)){
        
            // if there are failed sync orders, add them into next cron queue
            update_option('_aitrillion_updated_orders', $failed_sync_order, false);
            update_option('_aitrillion_failed_sync_orders', $failed_sync_orders_data, false);

        }else{

            // all order synced successfully, clear queue
            delete_option('_aitrillion_updated_orders');
        }
    }
}

function removeValueFromSession($id, $sessionName){
    $valueArray = array();
    $valueArray[] = $id;
    
    $existingArray = get_option($sessionName);
    if(!empty($existingArray)){
        $existingArray = array_unique($existingArray);
    }
    $arrDiff = array_diff($existingArray, $valueArray);
    update_option($sessionName, $arrDiff, false);
}

/**
* sync deleted orders
*
*/
function sync_deleted_orders(){

    // get ids of deleted orders created since last cron call
    $deleted_orders = get_option( '_aitrillion_deleted_orders' );

    aitrillion_api_log('deleted order sync log: '.print_r($deleted_orders, true).PHP_EOL);

    if(!empty($deleted_orders)){

        // remove duplicate ids
        $deleted_orders = array_unique($deleted_orders);

        foreach($deleted_orders as $k => $post_id){

            if( get_post_type( $post_id ) != 'shop_order' ) return;

            $json_payload = json_encode(array('id' => $post_id));

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

            $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
            $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

            $url = AITRILLION_END_POINT.'orders/delete';

            $response = wp_remote_post( $url, array(
                'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                        ),
                'body' => $json_payload
            ));

            $r = json_decode($response['body']);

            // if sync failed, store failed order into separate variable
            if(!isset($r->status) && $r->status != 'success'){

                $failed_sync_orders_data[$order_id][] = array('order_id' => $order_id, 'error' => $r->message, 'date' => date('Y-m-d H:i:s'));
                $failed_sync_order[] = $order_id;

            }

            aitrillion_api_log('Order Delete: Order id: '.$post_id.PHP_EOL.print_r($r, true));

        }

        if(!empty($failed_sync_order)){
        
            // if there are failed sync orders, add them into next cron queue
            update_option('_aitrillion_deleted_orders', $failed_sync_order, false);
            update_option('_aitrillion_failed_sync_order', $failed_sync_orders_data, false);

        }else{

            // all user synced successfully, clear queue
            delete_option('_aitrillion_deleted_orders');    
        }
    }
}

/**
* sync shop detail
*
*/
function sync_shop_update(){

    // get flag if shop updated since last cron call
    $shop_update = get_option( '_aitrillion_shop_updated' );

    if($shop_update){

        $return['shop_name'] = DOMAIN;

        $return['shop_type'] = 'woocommerce';
        $return['shop_owner'] = '';
        $return['status'] = 1;
        
        $store_city        = get_option( 'woocommerce_store_city' );
        $store_postcode    = get_option( 'woocommerce_store_postcode' );

        // The country/state
        $store_raw_country = get_option( 'woocommerce_default_country' );

        // Split the country/state
        $split_country = explode( ":", $store_raw_country );

        // Country and state separated:
        $store_country = $split_country[0];
        $store_state   = $split_country[1];

        $return['address1'] = get_option( 'woocommerce_store_address' );
        $return['address2'] = get_option( 'woocommerce_store_address_2' );
        $return['country'] = $store_country;
        $return['city'] = $store_city;
        $return['zip'] = $store_postcode;
        $return['phone'] = '';
        $return['store_name'] = get_bloginfo('name');
        $return['email'] = get_bloginfo( 'admin_email' );

        $return['shop_currency'] = get_woocommerce_currency();
        $return['money_format'] = html_entity_decode(get_woocommerce_currency_symbol()).'{{amount}}';

        $json_payload = json_encode($return);

        $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
        $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

        $bearer = base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password );

        $url = AITRILLION_END_POINT.'shops/update';

        $response = wp_remote_post( $url, array(
            'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                    ),
            'body' => $json_payload
        ));

        $r = json_decode($response['body']);

        aitrillion_api_log('Store updated: '.PHP_EOL.print_r($return, true).PHP_EOL.print_r($r, true));

        update_option('_aitrillion_shop_updated', false);
    }
}
?>