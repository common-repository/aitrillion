<?php 

// create product review shortcode and show on product detail page  
add_action( 'woocommerce_single_product_summary', 'aitrillion_product_review_rating', 5 );

// create product reivew, related product, new arrivals, trending product, recent view and show on product detail page
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_list_review');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_product_detail_reviews_rating');
add_action( 'woocommerce_before_shop_loop_item_title', 'aitrillion_product_category_reviews_rating' , 10);
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_list_review');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_related_product');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_new_arrival');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_trending_product');
add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_recent_view');
//add_action( 'woocommerce_after_single_product_summary', 'aitrillion_ait_product_page');

// create coupon shortcode and show on cart page
add_action( 'woocommerce_cart_coupon', 'aitrillion_coupon_widget');

// create new arrival, trending product, recent view shortcode and show on cart page
add_action( 'woocommerce_after_cart', 'aitrillion_new_arrival');
add_action( 'woocommerce_after_cart', 'aitrillion_trending_product');
add_action( 'woocommerce_after_cart', 'aitrillion_recent_view');

// create new arrival, trending product, recent view shortcode and show on shop page
add_action( 'woocommerce_after_main_content', 'aitrillion_after_shop_new_arrival');
add_action( 'woocommerce_after_main_content', 'aitrillion_after_shop_trending_product');
add_action( 'woocommerce_after_main_content', 'aitrillion_after_shop_recent_view');

// create referral hidden field and blocked customer field and show at footer
add_action( 'wp_footer', 'aitrillion_referral_hidden_fields' );
add_action( 'wp_footer', 'aitrillion_lyt_blocked_customer' );
add_action( 'wp_footer', 'aitrillion_ait_product_page' );

// include aitrillion js script
add_action('wp_enqueue_scripts', 'aitrillion_script');

// create shortcode
add_shortcode('aitrillion_product_featured_reviews', 'aitrillion_product_featured_reviews_shortcode');
add_shortcode('aitrillion_site_reviews', 'aitrillion_site_reviews_shortcode');
add_shortcode('aitrillion_new_arrival', 'aitrillion_new_arrival_shortcode');
add_shortcode('aitrillion_trending_product', 'aitrillion_trending_product_shortcode');
add_shortcode('aitrillion_recent_view', 'aitrillion_recent_view_shortcode');
add_shortcode('aitrillion_affiliate', 'aitrillion_affiliate_shortcode');
add_shortcode('aitrillion_loyalty', 'aitrillion_loyalty_shortcode');
add_shortcode('aitrillion_list_review', 'aitrillion_list_review_shortcode');
add_shortcode('aitrillion_product_detail_reviews_rating', 'aitrillion_product_detail_reviews_rating_shortcode');
add_shortcode('aitrillion_product_category_reviews_rating', 'aitrillion_product_category_reviews_rating_shortcode');
add_shortcode('aitrillion_related_product', 'aitrillion_ait_related_product_shortcode');
add_shortcode('aitrillion_coupon_widget', 'aitrillion_coupon_widget_shortcode');


function aitrillion_product_review_rating() {

    echo '<span class="egg-product-reviews-rating" data-id="'.wc_get_product()->get_id().'" id="'.wc_get_product()->get_id().'"></span>';
}

function aitrillion_list_review() {
    echo do_shortcode('[aitrillion_list_review]');
}

function aitrillion_product_detail_reviews_rating() {
    echo do_shortcode('[aitrillion_product_detail_reviews_rating]');
}

function aitrillion_product_category_reviews_rating() {
    echo do_shortcode('[aitrillion_product_category_reviews_rating]');
}

function aitrillion_related_product() {
    echo do_shortcode('[aitrillion_related_product]');
}

function aitrillion_ait_new_arrival() {
    echo do_shortcode('[aitrillion_new_arrival]');
}

function aitrillion_ait_trending_product() {
    echo do_shortcode('[aitrillion_trending_product]');
}

function aitrillion_ait_recent_view() {
    echo do_shortcode('[aitrillion_recent_view]');
}

function aitrillion_ait_product_page() {
    if(is_product()){
        echo '<div id="ait_product_page"></div>';
    }
    
}

function aitrillion_coupon_widget(){
    echo do_shortcode('[aitrillion_coupon_widget]');
}

function aitrillion_new_arrival() {
        echo do_shortcode('[aitrillion_new_arrival]');
}

function aitrillion_trending_product() {
    echo do_shortcode('[aitrillion_trending_product]');
}

function aitrillion_recent_view () {
    echo do_shortcode('[aitrillion_recent_view]');
}
function aitrillion_after_shop_new_arrival() {
    echo do_shortcode('[aitrillion_new_arrival]');
}
function aitrillion_after_shop_trending_product() {
    echo do_shortcode('[aitrillion_trending_product]');
}

function aitrillion_after_shop_recent_view() {
    echo do_shortcode('[aitrillion_recent_view]');
}

function aitrillion_script() {

    $username = get_current_user();
    $userid = get_current_user_id();
    $current_user = wp_get_current_user();

    // get aitrillion js script
    $aitrilltion_script = get_option('_aitrillion_script_url');

    if($aitrilltion_script){

        // get aitrillion script version
        $script_version = get_option('_aitrillion_script_version');

        if(empty($script_version)){
            $script_version = 1;
        }

        if($userid){

            $script = "
                var aioMeta = {
                    meta_e: '".$current_user->user_email."',
                    meta_i: '".$userid."',
                    meta_n: '".$username."',
                } ";
        }else{

            $script = "
                var aioMeta = {
                    meta_e: '',
                    meta_i: '',
                    meta_n: '',
                } ";
        }

        $url = explode('?', $aitrilltion_script);

        wp_enqueue_script( 'aitrillion-script', $url[0].'?v='.$script_version.'&'.$url[1], array(), null);

        wp_add_inline_script('aitrillion-script', $script, 'after');     

//        wp_enqueue_style( 'bootstrapcss','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css', false, null );

//        wp_enqueue_style( 'fontawesomecss','http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css', false, null );
    }

    
}

function aitrillion_product_featured_reviews_shortcode() {

    $message ='<div id="egg-product-featured-reviews"></div>'; 
    return $message;
}

function aitrillion_site_reviews_shortcode() {

    $message ='<div class="egg-site-all-reviews"></div>'; 
    return $message;
}

function aitrillion_new_arrival_shortcode() {

    $message ='<div id="aionewarrival" class="aionewarrival"></div>'; 
    return $message;
}

function aitrillion_trending_product_shortcode() {

    $message ='<div id="aiotrendingproducts" class="aiotrendingproducts"></div>'; 
    return $message;
}

function aitrillion_recent_view_shortcode() {

    $message ='<div id="aiorecentview" class="aiorecentview" ></div>'; 
    return $message;
}

function aitrillion_affiliate_shortcode() {

    $message ='<div id="aio-affiliate-dashboard" style="text-align:center"><img id="aft-loader" src="https://static.aitrillion.com/review/src/assets/images/loader.gif"></div>'; 
    return $message;
}

function aitrillion_loyalty_shortcode() {

    $message ='<span class="aaa-ly-cus-available-p"> </span>
    <span class="aaa-ly-cus-spent-p"> </span>
    <span class="aaa-ly-cus-lifetime-p"> </span>'; 
    return $message;
}

function aitrillion_list_review_shortcode() {

    $content =  '<div id="aio-product-detial-reviews" class="page-width" attr-value="{{product_id}}"></div>';

    $content = str_replace('{{product_id}}', wc_get_product()->get_id(), $content);

    return $content;
}

function aitrillion_product_detail_reviews_rating_shortcode() {

    return '<span class="egg-product-reviews-rating" data-id="' . wc_get_product()->get_id() . '" id="' . wc_get_product()->get_id() . '"></span>';

}

function aitrillion_product_category_reviews_rating_shortcode() {
  
    echo '<span class="egg-product-reviews-rating" data-id="' . wc_get_product()->get_id() . '" id="' . wc_get_product()->get_id() . '"></span>';

}

function aitrillion_ait_related_product_shortcode() {

    $message ='<div id="aiorelatedproducts" class="aiorelatedproducts"></div>'; 
    return $message;
}

function aitrillion_coupon_widget_shortcode() {

    $message = '<div class="aaa-loyalty-cartredeem-widget"></div>'; 
    return $message;
}

function aitrillion_referral_hidden_fields() {
    if (is_user_logged_in()) {

        $userid = get_current_user_id();

        $current_user = wp_get_current_user();

        ?>
        <input type="hidden" name="is_customer_logged_in" value="1" class="is_customer_logged_in"> 
        <input type="hidden" name="referral_customer_logged_id" value="<?php echo $userid?>" class="referral_customer_logged_id"> 
        <input type="hidden" name="referral_customer_email" value="<?php echo $current_user->user_email?>" class="referral_customer_email">

        <input type="hidden" name="referral_shop_currency" value="<?php echo get_woocommerce_currency_symbol()?>" class="referral_shop_currency">
        <?php 
    }
    else { 
        
        ?>
        <input type="hidden" name="is_customer_logged_in" value="0" class="is_customer_logged_in">
        <input type="hidden" name="referral_customer_logged_id" value="0" class="referral_customer_logged_id">

    <?php }

}

function aitrillion_lyt_blocked_customer(){

    if (is_user_logged_in()) {

        $userid = get_current_user_id();

        $lyt_blocked_customers = get_option('_aitrillion_block_loyalty_members');

        if(!empty($lyt_blocked_customers)){
            $customer_ids = explode(',', $lyt_blocked_customers);

            if(in_array($userid, $customer_ids)){
                echo '<input type="hidden" name="aio_lyt_blocked_customer" value="'.$userid.'" class="aio_lyt_blocked_customer">';
            }
        }

    }
}

/*
*
*   Create affiliate menu in woocommerce account page
*/

// create new route for affiliate menu
add_action( 'init', 'aitrillion_affiliate_endpoint' );
function aitrillion_affiliate_endpoint() {
    add_rewrite_endpoint( 'affiliate', EP_PAGES );
}

// add affiliate menu
add_filter ( 'woocommerce_account_menu_items', 'aitrillion_affiliate_link', 40 );
function aitrillion_affiliate_link( $menu_links ){

    $affiliate_module = get_option('_aitrillion_affiliate_module');

    // add affiliate menu of affiliate module is active
    if($affiliate_module == 1){
        $menu_links = array_slice( $menu_links, 0, 5, true ) 
                        + array( 'affiliate' => 'Affiliate Program' )
                        + array_slice( $menu_links, 5, NULL, true );
    }
    
    return $menu_links;
}


// show affiliate dashboard from shortcode
add_action( 'woocommerce_account_affiliate_endpoint', 'aitrillion_affiliate_content' );
function aitrillion_affiliate_content() {
    echo do_shortcode('[aitrillion_affiliate]');
}

// end of affiliate menu and dashboard page


/*
* 
* Create sync status custom column into products list
*/
add_filter( 'manage_edit-product_columns', 'aitrillion_sync_status',15 );
function aitrillion_sync_status($columns){

    unset( $columns['tags'] );

   //add column
   $columns['ait_status'] = __( 'AiT Sync Status'); 

   return $columns;
}

add_action( 'manage_product_posts_custom_column', 'aitrillion_product_column_sync', 10, 2 );
function aitrillion_product_column_sync( $column, $postid ) {
    if ( $column == 'ait_status' ) {
        echo get_post_meta( $postid, '_aitrillion_product_sync', true );
    }
}

add_filter( 'manage_edit-product_sortable_columns', 'aitrillion_product_sync_sort_columns' );
function aitrillion_product_sync_sort_columns( $columns )
{
    $columns['ait_status'] = '_aitrillion_product_sync';
    return $columns;
}
// end of product list sync status custom column


/*
* 
* Create sync status custom column into order list
*/

add_filter( 'manage_edit-shop_order_columns', 'aitrillion_order_sync_status_column',15 );
function aitrillion_order_sync_status_column($columns){

   //add column
   $columns['ait_status'] = __( 'AiT Sync Status'); 

   return $columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'aitrillion_order_column_sync', 10, 2 );
function aitrillion_order_column_sync( $column, $postid ) {
    if ( $column == 'ait_status' ) {
        echo get_post_meta( $postid, '_aitrillion_order_sync', true );
    }
}

add_filter( 'manage_edit-shop_order_sortable_columns', 'aitrillion_order_sync_sort_columns' );
function aitrillion_order_sync_sort_columns( $columns )
{
    $columns['ait_status'] = '_aitrillion_order_sync';
    return $columns;
}
// end of order list sync status custom column


/*
* 
* Create sync status custom column into user list
*/
add_filter('manage_users_columns', 'aitrillion_user_sync_column');
function aitrillion_user_sync_column($columns) {
    $columns['ait_status'] = 'AiT Sync Status';
    return $columns;
}    

add_action('manage_users_custom_column',  'aitrillion_user_sync_status', 10, 3);
function aitrillion_user_sync_status( $output, $column_key, $user_id ) {
    
    switch ( $column_key ) {
        case 'ait_status':
            $value = get_user_meta( $user_id, '_aitrillion_user_sync', true );

            return $value;
            break;
        default: break;
    }

    // if no column slug found, return default output value
    return $output;
}
// end of user list sync status custom column



/*
* 
* Create sync status custom column into category list
*/
function aitrillion_category_sync_column($columns) { 

    $columns['ait_status'] = 'AiT Sync Status';

    return $columns; 
} 
add_filter('manage_edit-product_cat_columns', 'aitrillion_category_sync_column'); 

function aitrillion_category_sync_status( $columns, $column, $term_id ) { 

    if ($column == 'ait_status') {
        $foo = get_term_meta( $term_id, '_aitrillion_category_sync', true );
        return $foo;
    }
}
add_filter('manage_product_cat_custom_column', 'aitrillion_category_sync_status', 10, 3);
// end of category list sync status custom column


// create shortcode for price drop and show after add to cart button on product detail page
add_action( 'woocommerce_after_add_to_cart_button', 'aitrillion_price_drop_button' );
function aitrillion_price_drop_button(){

    echo '<div class="aioPriceDrop" id="aioPriceDrop"></div>';

    if(!function_exists("wc_get_product")){
        return;
    }
    $productObj = wc_get_product();
    if(!is_object($productObj)){
        return;
    }

    // get current product id
    $product_id = wc_get_product()->get_id();

    // get current product detail
    $product = wc_get_product($product_id);

    // get product variant
    $current_products = $product->get_children();

    echo '<select name="aio-variantid" style="display:none!important;">';

    if(!empty($current_products)){

        foreach($current_products as $child_product){

            $variant = wc_get_product($child_product);

            if($variant->is_in_stock()){
                echo '<option id="aio-variant-id-'.$variant->get_id().'" value="1">'.$variant->get_name().'</option>';
            }else{
                echo '<option id="aio-variant-id-'.$variant->get_id().'" value="0">'.$variant->get_name().'</option>';
            }
            
        }
    }else{
        echo '<option id="aio-variant-id-'.$product_id.'">'.$product->get_name().'</option>';
    }

    echo '</select>';
}


// create shortcode for back in stock and show after add to cart button on product detail page
add_action( 'woocommerce_after_add_to_cart_button', 'aitrillion_back_in_stock_button' );
function aitrillion_back_in_stock_button(){

    echo '<div class="aioBackInStock" id="aioBackInStock"></div>';

    if(!function_exists("wc_get_product")){
        return;
    }
    $productObj = wc_get_product();
    if(!is_object($productObj)){
        return;
    }
    // get current product id
    $product_id = wc_get_product()->get_id();

    // get current product detail
    $product = wc_get_product($product_id);

    // get product variant
    $current_products = $product->get_children();

    echo '<select name="aio-variantid" style="display:none!important;">';

    if(!empty($current_products)){

        foreach($current_products as $child_product){

            $variant = wc_get_product($child_product);

            if($variant->is_in_stock()){
                echo '<option id="aio-variant-id-'.$variant->get_id().'" value="1">'.$variant->get_name().'</option>';
            }else{
                echo '<option id="aio-variant-id-'.$variant->get_id().'" value="0">'.$variant->get_name().'</option>';
            }
            
        }
    }else{
        echo '<option id="aio-variant-id-'.$product_id.'">'.$product->get_name().'</option>';
    }

    echo '</select>';
}

?>