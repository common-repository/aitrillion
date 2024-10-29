<?php 

/**
 * Plugin Name:       AiTrillion
 * Plugin URI:        https://wordpress.org/plugins/aitrillion/
 * Description:       Exclusive Loyalty Program with Email Marketing, SMS, Push, Product Reviews, Membership & 11+ feature
 * Version:           1.0.12
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            AiTrillion
 * Author URI:        https://www.aitrillion.com
 */

if (!defined('ABSPATH')) {
    return;
}


// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Test to see if WooCommerce is active (including network activated).
$woocommerce_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (in_array( $woocommerce_plugin_path, wp_get_active_and_valid_plugins() )) 
{

    include_once $woocommerce_plugin_path;   // Include woocommerce library

    // Defines the path to the main plugin file.
    define( 'AITRILLION_FILE', __FILE__ );

    // Defines the path to be used for includes.
    define( 'AITRILLION_PATH', plugin_dir_path( AITRILLION_FILE ) );

    // Defines the URL to the plugin.
    define( 'AITRILLION_URL', plugin_dir_url( AITRILLION_FILE ) );

    // Define end point of Ai Trillion 
    define('AITRILLION_END_POINT', 'https://connector-api.aitrillion.com/');

    $domain = preg_replace("(^https?://)", "", site_url() );

    define('DOMAIN', $domain);

    include AITRILLION_PATH . 'common_functions.php';
    include AITRILLION_PATH . 'cron-jobs.php';
    include AITRILLION_PATH . 'platform_api.php';
    include AITRILLION_PATH . 'data_sync.php';
    include AITRILLION_PATH . 'shortcodes.php';

        // add the admin options page
        
        add_action('admin_menu', 'aitrillion_admin_menu');

        function aitrillion_admin_menu() {

            //create new top-level menu
            add_menu_page(
                'AiTrillion', 
                'AiTrillion', 
                'manage_options', 
                'aitrillion.php',
                'aitrillion_options_page'
            );

            add_submenu_page(
                'aitrillion.php',
                'AiTrillion Settings',
                'Settings',
                'manage_options',
                'aitrillion.php',
                'aitrillion_options_page'
            );

            /*add_submenu_page(
                'aitrillion.php',
                'Aitrillion Shortcode Widgets',
                'Shortcode Widgets',
                'manage_options',
                'aitrillion_shortcode',
                'aitrillion_shortcode'
            );*/

        }

        add_action('admin_init', 'aitrillion_admin_init');

        function aitrillion_admin_init(){

            register_setting( 'aitrillion_options', '_aitrillion_api_key');
            register_setting( 'aitrillion_options', '_aitrillion_api_password');
            register_setting( 'aitrillion_options', '_aitrillion_script_url' );
            register_setting( 'aitrillion_options', '_aitrillion_affiliate_module' );
        }

        add_action('init', 'start_session', 1);

        function start_session() {
            if(!session_id()) {
                session_start();
            }
        }

        add_action( 'admin_action_aitrillion_clear_log', 'aitrillion_clear_log' );
        function aitrillion_clear_log()
        {
            file_put_contents(AITRILLION_PATH.'aitrillion-log.txt', '');

            add_action( 'admin_notices', 'aitrillion_clear_log_notice' );

            $_SESSION['msg'] = 'Log Cleared';

            wp_redirect( admin_url( 'admin.php' ).'?page=aitrillion.php' );
            exit();
        }

        function aitrillion_clear_log_notice() {
        echo '<div class="notice notice-warning is-dismissible">
              <p><strong>AiTrillion log cleared</p>
              </div>'; 
        }
        

     // display the admin options page
        function aitrillion_options_page() {
    ?>
            <div class="wrap">
                <h1>AiTrillion Settings</h1>

                <form method="post" action="options.php">

                    <?php settings_errors('aitrillion_options'); ?>

                    <?php settings_fields( 'aitrillion_options' ); ?>

                    <?php do_settings_sections( 'aitrillion_options' ); ?>

                    <div class="card" style="max-width: 700px">
                        Login to <a href="https://app.aitrillion.com/" target="_blank">AiTrillion</a>
                    </div>

                    <?php 
                        if(isset($_SESSION['msg']) && !empty($_SESSION['msg'])){
                    ?>
                        <div class="card" style="max-width: 700px; color: green;">Log file cleared</div>
                    <?php
                            unset($_SESSION['msg']);
                        }
                    ?>

                    <div class="card" style="max-width: 700px">

                        <table class="form-table">
                            <tr valign="top">
                            <th scope="row">AiTrillion API Key</th>
                            <td><input type="text" name="_aitrillion_api_key" value="<?php echo esc_attr( get_option('_aitrillion_api_key') ); ?>" /></td>
                            </tr>
                             
                            <tr valign="top">
                            <th scope="row">AiTrillion API Password</th>
                            <td>
                                <input type="password" name="_aitrillion_api_password" value="<?php echo esc_attr( get_option('_aitrillion_api_password') ); ?>" />
                            </td>
                            </tr>

                            <tr valign="top">
                            <th scope="row">AiTrillion script URL</th>
                            <td>
                                
                                <textarea rows="5" cols="50" name="_aitrillion_script_url"><?php echo esc_attr( get_option('_aitrillion_script_url') ); ?></textarea>
                            </td>
                            </tr>

                            <tr valign="top">
                            <th scope="row">AiTrillion Affiliate Module</th>
                            <td>
                                <?php $checked = esc_attr( get_option('_aitrillion_affiliate_module') ) ? 'checked="checked"' : ''; ?>

                                <input type="checkbox" name="_aitrillion_affiliate_module" value="1" <?=$checked?> />
                            </td>
                            </tr>

                            <?php 

                                $_aitrillion_api_key = get_option( '_aitrillion_api_key' );
                                $_aitrillion_api_password = get_option( '_aitrillion_api_password' );

                                if($_aitrillion_api_key && $_aitrillion_api_password){
                            ?>

                            <tr>
                                <th scope="row">AiTrillion connection</th>
                                <td>
                                    <?php 

                                        $domain = preg_replace("(^https?://)", "", site_url() );

                                        $url = AITRILLION_END_POINT.'validate?shop_name='.$domain;

                                        $response = wp_remote_get( $url, array(
                                            'headers' => array(
                                                'Authorization' => 'Basic ' . base64_encode( $_aitrillion_api_key.':'.$_aitrillion_api_password )
                                            )
                                        ));


                                        if( !is_wp_error( $response['body'] ) ) {

                                            $r = json_decode($response['body']);

                                            if(isset($r->status) && $r->status == 'sucess'){

                                                echo '<strong style="color: green">Active</strong>';

                                            }else{

                                                echo '<strong style="color: red">In-active</strong>';

                                                if(isset($r->status) && $r->status == 'error'){

                                                     echo ' <strong style="color: red">('.$r->msg.')</strong>';

                                                }elseif(isset($r->message)){

                                                    echo ' <strong style="color: red">('.$r->message.')</strong>';
                                                }

                                            }
                                        }else{
                                            echo ' <strong style="color: red">('.$response['body']->get_error_message().')</strong>';   
                                        }

                                        
                                    ?>
                                </td>
                            </tr>

                            <?php 
                                }
                            ?>
                        </table>    
                    </div>
                    
                    
                    <?php submit_button(); ?>

                </form>

                <div class="card" style="max-width: 700px">
                    <table cellpadding="2" cellspacing="2">
                        <tr>
                            <td colspan="2">
                                <h3>AiTrillion syncing status logs</h3>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Failed products sync:</strong></td>
                            <td>
                                <?php 
                                    $params = array(
                                        'post_type' => 'product',
                                        'meta_query' => array(
                                            array('key' => '_aitrillion_product_sync', //meta key name here
                                                  'value' => 'false', 
                                                  'compare' => '=',
                                            )
                                        )
                                    );
                                    $wc_query = new WP_Query($params);

                                    $failed_products = $wc_query->found_posts;

                                    $failed_products = 0;

                                    $orders = get_option( '_aitrillion_created_orders' );
                                    if($orders){
                                        // remove duplicate ids
                                        $orders = array_unique($orders);

                                        $failed_products = count($orders);
                                    }

                                    $orders = get_option( '_aitrillion_updated_orders' );
                                    if($orders){
                                        // remove duplicate ids
                                        $orders = array_unique($orders);

                                        $failed_products = $failed_products + count($orders);
                                    }
                                    

                                    echo $failed_products;
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Failed order sync:</strong></td>
                            <td>
                                <?php 
                                    $orders = wc_get_orders( array(
                                                            'limit'        => -1, // Query all orders
                                                            'meta_key'     => '_aitrillion_order_sync', 
                                                            'meta_value'     => 'false', 
                                                            'meta_compare' => '=', // The comparison argument
                                                        ));

                                    if(!empty($orders)){
                                        echo count($orders);
                                    }else{
                                        echo '0';
                                    }
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Failed categories sync:</strong></td>
                            <td>
                                <?php 
                                    $args = array(
                                                'hide_empty' => false, // also retrieve terms which are not used yet
                                                'meta_query' => array(
                                                    array(
                                                       'key'       => '_aitrillion_category_sync',
                                                       'value'     => 'false',
                                                       'compare'   => '='
                                                    )
                                                ),
                                                'taxonomy'  => 'product_cat',
                                                );

                                    $terms = get_terms( $args );

                                    if(!empty($terms)){
                                        echo count($terms);
                                    }else{
                                        echo '0';
                                    }
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Failed customers sync:</strong></td>
                            <td>
                                <?php 
                                    $customers = get_users(array(
                                                    'meta_key' => '_aitrillion_user_sync',
                                                    'meta_value' => 'false'
                                                ));

                                    if(!empty($customers)){
                                        echo count($customers);
                                    }else{
                                        echo '0';
                                    }

                                    
                                ?>
                            </td>
                        </tr>
                        
                    </table>

                    <a href="<?php echo get_site_url().'/wp-cron.php';?>" target="_blank">Resync</a>&nbsp;&nbsp;
                    <a href="<?php echo get_site_url().'/wp-content/plugins/aitrillion/aitrillion-log.txt';?>" target="_blank">View Log File</a>&nbsp;&nbsp;

                    <?php 
                        $filesize = filesize(AITRILLION_PATH.'aitrillion-log.txt'); // bytes
                        $filesize = round($filesize / 1024 / 1024, 1); // megabytes with 1 digit
                    ?>
                    <a href="<?php echo admin_url( 'admin.php' ).'?action=aitrillion_clear_log'; ?>" onclick="return confirm('Are you sure?')">Clear Log (File size: <?=$filesize?> MB)</a>&nbsp;&nbsp;
                </div>
            </div>
     
    <?php
        }

        function aitrillion_shortcode(){
        ?>

            <div class="wrap">
                <h1>AiTrillion Shortcode</h1>

                <p>Use below shortcode and place on any page to display Aitrillion widget</p>

                <table width="80%">
                    <tr>
                        <td><strong>List review + Write a review section</strong></td>
                        <td>[aitrillion_list_review]</td>
                    </tr>

                    <tr>
                        <td><strong>Product Featured Review Shortcode</strong></td>
                        <td>[aitrillion_product_featured_reviews]</td>
                    </tr>

                    <tr>
                        <td><strong>Site Reviews Shortcode</strong></td>
                        <td>[aitrillion_site_reviews]</td>
                    </tr>

                    <tr>
                        <td><strong>New Arrivals Shortcode</strong></td>
                        <td>[aitrillion_new_arrival]</td>
                    </tr>

                    <tr>
                        <td><strong>Trending Product Shortcode</strong></td>
                        <td>[aitrillion_trending_product]</td>
                    </tr>

                    <tr>
                        <td><strong>Recent View Shortcode</strong></td>
                        <td>[aitrillion_recent_view]</td>
                    </tr>

                    <tr>
                        <td><strong>Affiliate Shortcode</strong></td>
                        <td>[aitrillion_affiliate]</td>
                    </tr>

                    <tr>
                        <td><strong>Loyalty Shortcode</strong></td>
                        <td>[aitrillion_loyalty]</td>
                    </tr>

                </table>
            </div>
        <?php
        }

        add_action('woocommerce_thankyou', 'aitrillion_aff_tracking_code', 10, 1);
        function aitrillion_aff_tracking_code( $order_id ) {

            if ( ! $order_id )
                return;

            if(isset($_COOKIE['aio_shopify_ref'])){
                $order = wc_get_order( $order_id );
                $order->update_meta_data( '_aio_shopify_ref', sanitize_text_field($_COOKIE['aio_shopify_ref']) );
                $order->save();
            }

            if(isset($_COOKIE['aio_affiliate_code'])){
                $order = wc_get_order( $order_id );
                $order->update_meta_data( '_aio_affiliate_code', sanitize_text_field($_COOKIE['aio_affiliate_code']) );
                $order->save();
            }
        }

        add_action('woocommerce_add_to_cart', 'aitrillion_generate_cart_id');
        function aitrillion_generate_cart_id() {
            
            $cart_id = WC()->session->get('cart_id');

            if( is_null($cart_id) ) {

                $cart_id = uniqid();

                WC()->session->set('cart_id', $cart_id);

                // set a cookie for 1 year
                setcookie('quoteid', $cart_id, time()+31556926, '/');
            }  else {
                if($cart_id){
                    setcookie('quoteid', $cart_id, time()+31556926, '/');
                }
                
            }
        }


        add_action('woocommerce_thankyou', 'aitrillion_update_order_cart_id', 10, 1);
        function aitrillion_update_order_cart_id( $order_id ) {

            if ( ! $order_id )
                return;

            $cart_id = WC()->session->get('cart_id');

            if( !is_null($cart_id) ) {

                $order = wc_get_order( $order_id );
                $order->update_meta_data( '_aio_card_id', $cart_id );
                $order->save();

                WC()->session->__unset( 'cart_id' );
            }
        }
    
}else{
    //echo 'This plugin works with woocommerce only. Please install and activate woocommerce first.';

    function aitrillion_admin_notice() {
    echo '<div class="notice notice-warning is-dismissible">
          <p><strong>AiTrillion requires woocommerce.</strong> Please install and activate woocommerce.</p>
          </div>'; 
    }
    add_action( 'admin_notices', 'aitrillion_admin_notice' );
}

