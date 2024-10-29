<?php 

// create action on user register, edit and delete
add_action( 'user_register', 'aitrillion_sync_user_register', 10, 1 );
add_action( 'profile_update', 'aitrillion_sync_user_update', 10, 2 );
add_action( 'delete_user', 'aitrillion_sync_user_delete' );

// create action on product create, edit and delete
add_action( 'woocommerce_new_product', 'aitrillion_sync_product_create', 10, 1 );
add_action( 'woocommerce_update_product', 'aitrillion_sync_product_updated', 10, 1 );
add_action( 'wp_trash_post', 'aitrillion_sync_product_delete', 99 );
add_action( 'delete_post', 'aitrillion_sync_product_delete', 99 );

// create action on category create, edit and delete
add_action('create_product_cat', 'aitrillion_sync_product_category_create', 10, 1);
add_action('edit_product_cat', 'aitrillion_sync_product_category_update', 10, 1);
add_action('delete_product_cat', 'aitrillion_sync_product_category_delete', 10, 1);

// create action on order create, edit and delete
add_action( 'woocommerce_new_order', 'aitrillion_sync_order_create',  1, 1  );
add_action( 'woocommerce_process_shop_order_meta', 'aitrillion_sync_order_update');
add_action( 'wp_trash_post', 'aitrillion_sync_order_delete', 99 );

// create action on shop detail edit
add_action( 'updated_option', 'aitrillion_sync_store_detail', 10, 3 );


/**
* flag new user for sync and add into cron queue
*
* @param int $user_id user id
*
* @return false
*/
function aitrillion_sync_user_register( $user_id ) {

    // flag this user as not synced
    update_user_meta($user_id, '_aitrillion_user_sync', 'false');

    // get new registered unsynced users id
    $new_users = get_option( '_aitrillion_created_users' );

    // add this user into new user sync queue
    $new_users[] = $user_id;
    update_option('_aitrillion_created_users', $new_users, false);

    return false;
}

/**
* flag modified user for sync and add into cron queue
*
* @param int $user_id user id
* 
* @param array $old_user_data user data before modify
*
* @return false
*/
function aitrillion_sync_user_update( $user_id, $old_user_data ) {

    // flag this user as not synced
    update_user_meta($user_id, '_aitrillion_user_sync', 'false');

    // get modified unsynced users id
    $updated_users = get_option( '_aitrillion_updated_users' );

    // add this user into updated user sync queue
    $updated_users[] = $user_id;
    update_option('_aitrillion_updated_users', $updated_users, false);

    return false;
    
}

/**
* flag deleted user for sync and add into cron queue
*
* @param int $user_id user id
*
* @return false
*/
function aitrillion_sync_user_delete( $user_id ) {

    // get deleted unsynced users id
    $deleted_users = get_option( '_aitrillion_deleted_users' );

    // add this user into deleted user sync queue
    $deleted_users[] = $user_id;
    update_option('_aitrillion_deleted_users', $deleted_users, false);

    aitrillion_api_log('user deleted: '.$user_id.PHP_EOL);

    return false;
}


/**
* flag new product for sync and add into cron queue
*
* @param int $product_id product id
*
* @return false
*/
function aitrillion_sync_product_create( $product_id ) {

    $product = wc_get_product( $product_id );

    $product_status = $product->get_status();

    if($product_status != 'publish'){
        return false;
    }

    // flag this product as not synced
    update_post_meta($product_id, '_aitrillion_product_sync', 'false');

    // get new unsynced products id
    $created_products = get_option( '_aitrillion_created_products' );

    // add this product into new product sync queue
    $created_products[] = $product_id;
    update_option('_aitrillion_created_products', $created_products, false);

    return true;
}

/**
* flag modified product for sync and add into cron queue
*
* @param int $product_id product id
*
* @return false
*/
function aitrillion_sync_product_updated( $product_id ) {

    $product = wc_get_product( $product_id );

    $product_status = $product->get_status();

    if($product_status != 'publish'){
        return false;
    }

    // flag this product as not synced
    update_post_meta($product_id, '_aitrillion_product_sync', 'false');

    // get modified unsynced products id
    $updated_products = get_option( '_aitrillion_updated_products' );

    // add this product into modified product sync queue
    $updated_products[] = $product_id;
    update_option('_aitrillion_updated_products', $updated_products, false);

    return false;
}

/**
* flag deleted product for sync and add into cron queue
*
* @param int $post_id product id
*
* @return false
*/
function aitrillion_sync_product_delete( $post_id ){

    // check if deleted item is a product, if not exit
    if( get_post_type( $post_id ) != 'product' ) return;

    // get deleted unsynced products id
    $deleted_products = get_option( '_aitrillion_deleted_products' );

    // add this product into deleted product sync queue
    $deleted_products[] = $post_id;
    update_option('_aitrillion_deleted_products', $deleted_products, false);

    aitrillion_api_log('product deleted: '.$post_id.PHP_EOL);

    return false;
    
}


/**
* flag new category for sync and add into cron queue
*
* @param int $category_id category id
*
* @return false
*/
function aitrillion_sync_product_category_create( $category_id ){

    // flag this category as not synced
    add_term_meta($category_id, '_aitrillion_category_sync', 'false');

    // get new unsynced categories id
    $created_categories = get_option( '_aitrillion_created_categories' );

    // add this category into new category sync queue
    $created_categories[] = $category_id;
    update_option('_aitrillion_created_categories', $created_categories, false);

    return true;
}

/**
* flag modified category for sync and add into cron queue
*
* @param int $category_id category id
*
* @return false
*/
function aitrillion_sync_product_category_update( $category_id ){

    // flag this category as not synced
    update_term_meta($category_id, '_aitrillion_category_sync', 'false');

    // get modified unsynced categories id
    $updated_categories = get_option( '_aitrillion_updated_categories' );

    // add this category into modified category sync queue
    $updated_categories[] = $category_id;
    update_option('_aitrillion_updated_categories', $updated_categories, false);

    return true;
}

/**
* flag deleted category for sync and add into cron queue
*
* @param int $category_id category id
*
* @return false
*/
function aitrillion_sync_product_category_delete( $category_id ){

    // get deleted unsynced categories id
    $deleted_categories = get_option( '_aitrillion_deleted_categories' );

    // add this category into deleted category sync queue
    $deleted_categories[] = $category_id;
    update_option('_aitrillion_deleted_categories', $deleted_categories, false);

    aitrillion_api_log('category deleted: '.$category_id.PHP_EOL);

    return true;
}


/**
* flag new order for sync and add into cron queue
*
* @param int $order_id order id
*
* @return false
*/
function aitrillion_sync_order_create($order_id){

    // flag this order as not synced
    update_post_meta($order_id, '_aitrillion_order_sync', 'false');

    // get new unsynced order ids
    $created_orders = get_option( '_aitrillion_created_orders' );

    // add this order into new order sync queue
    $created_orders[] = $order_id;
    update_option('_aitrillion_created_orders', $created_orders, false);

    return false;
}

/**
* flag modified order for sync and add into cron queue
*
* @param int $order_id order id
*
* @return false
*/
function aitrillion_sync_order_update ( $order_id )
{

    // flag this order as not synced
    update_post_meta($order_id, '_aitrillion_order_sync', 'false');

    // get modified unsynced order ids
    $updated_orders = get_option( '_aitrillion_updated_orders' );

    // add this order into modified order sync queue
    $updated_orders[] = $order_id;
    update_option('_aitrillion_updated_orders', $updated_orders, false);

    return false;
    
}

/**
* flag deleted order for sync and add into cron queue
*
* @param int $post_id order id
*
* @return false
*/
function aitrillion_sync_order_delete( $post_id ){

    // get deleted unsynced order ids
    $deleted_orders = get_option( '_aitrillion_deleted_orders' );

    // add this order into deleted order sync queue
    $deleted_orders[] = $post_id;
    update_option('_aitrillion_deleted_orders', $deleted_orders, false);

    aitrillion_api_log('order deleted: '.$post_id.PHP_EOL);

    return false;
}

/**
* flag shop update if modified
*
* @param string $option_name which option is modified
* @param string $old_value option value before modify
* @param string $value option value after modify
*
* @return false
*/
function aitrillion_sync_store_detail( $option_name, $old_value, $value ) {

    // shop detail info
    $store_details = array('woocommerce_store_address', 
                            'woocommerce_store_address_2', 
                            'woocommerce_store_city', 
                            'woocommerce_default_country',
                            'woocommerce_store_postcode',
                            'woocommerce_currency'
                        );

    // check if edited option is one of shop detail
    if( in_array($option_name, $store_details) ){

        // flag shop detail is modified
        update_option('_aitrillion_shop_updated', 'true', false);
    }

}

?>