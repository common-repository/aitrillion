<?php

/**
* write messages into log file
*
* @param string $message The log text
*
* @return null
*/
function aitrillion_api_log($message = ''){

    $log = '------------'.date('Y-m-d H:i:s').'---------------'.PHP_EOL;
    $log .= $message;
    
    file_put_contents(AITRILLION_PATH.'aitrillion-log.txt', $log.PHP_EOL, FILE_APPEND);
}


/**
* prepare woocommerce customer detail by customer id
*
* @param int $customer_id customer id
*
* @return array customer detail
*/
function aitrilltion_get_customer( $customer_id ){

        // initialize customer object from woocommerce customer class
        $customer = new WC_Customer( $customer_id );

        $c = array();

        $c['id'] = $customer_id;
        $c['first_name'] = $customer->get_first_name();
        $c['last_name'] = $customer->get_last_name();
        $c['email'] = $customer->get_email();
        $c['verified_email'] = true;
        $c['phone'] = $customer->get_billing_phone();
        $c['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
        $c['accepts_marketing'] = true;

        // if no customer edit date available, assign created date as updated date
        if(!empty($customer->get_date_modified())){
            $c['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
        }else{
            $c['updated_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
        }

        $modified_date = $customer->get_date_modified();

        $c['orders_count'] = $customer->get_order_count();
        $c['total_spent'] = $customer->get_total_spent();

        $last_order = $customer->get_last_order();

        if(!empty($last_order)){
            $last_order_id = $last_order->get_id();
            $c['last_order_name'] = $last_order_id;
            $c['last_order_id'] = $last_order_id;

        }else{
            $c['last_order_name'] = null;
            $c['last_order_id'] = null;
        }

        $c['default_address'] = array(
                    'id' => 1,
                    'customer_id' => $customer_id,
                    'first_name' => $customer->get_billing_first_name(),
                    'last_name' => $customer->get_billing_last_name(),
                    'company' => $customer->get_billing_company(),
                    'address1' => $customer->get_billing_address_1(),
                    'city' => $customer->get_billing_city(),
                    'province' => $customer->get_billing_state(),
                    'zip' => $customer->get_billing_postcode(),
                    'phone' => $customer->get_billing_phone(),
                    'name' => $customer->get_billing_first_name().' '.$customer->get_billing_last_name(),
                    'country_code' => $customer->get_billing_country(),
                    'default' => true,

                );

        if(method_exists($customer, 'get_shipping_phone')){
            $shipping_phone = $customer->get_shipping_phone();
        }else{
            $shipping_phone = '';
        }

        $address[] = array(
                    'id' => 2,
                    'customer_id' => $customer_id,
                    'first_name' => $customer->get_shipping_first_name(),
                    'last_name' => $customer->get_shipping_last_name(),
                    'company' => $customer->get_shipping_company(),
                    'address1' => $customer->get_shipping_address_1(),
                    'city' => $customer->get_shipping_city(),
                    'province' => $customer->get_shipping_state(),
                    'zip' => $customer->get_shipping_postcode(),
                    'phone' => $shipping_phone,
                    'name' => $customer->get_shipping_first_name().' '.$customer->get_shipping_last_name(),
                    'country_code' => $customer->get_shipping_country(),
                    'default' => false,

                );

        $c['addresses'] = $address;
        $c['type'] = null;

        $address = array();

        return $c;

}


/**
* prepare woocommerce product detail by product object
*
* @param object $product product object
*
* @return array product detail
*/
function aitrillion_get_product( $product )
{

    $p['id'] = $product->get_id();
    $p['title'] = $product->get_title();
    $p['vendor'] = '';
    $p['product_type'] = $product->get_type();
    $p['created_at'] = ($product->get_date_created() ? $product->get_date_created()->date('Y-m-d H:i:s') : NULL);
    $p['handle'] = get_permalink( $product->get_id() );
    $p['updated_at'] = ( $product->get_date_modified() ? $product->get_date_modified()->date('Y-m-d H:i:s') : NULL);
    $p['published_at'] = $p['created_at'];

    // get product keywords/tags
    $terms = wp_get_post_terms( $product->get_id(), 'product_tag' );
 
    $tags = null;
    // Loop through each product tag for the current product
    if ( count( $terms ) > 0 ) {
 
        foreach( $terms as $term ) {
            $tags_array[] = $term->name;
        }
 
        // Combine all of the product tags into one string for output
        $tags = implode( ',', $tags_array );
    }

    $p['tags'] = $tags;

    // check if product have variant, otherwise assign main product detail as variant
    if($product->get_type() == 'variable'){

        $available_variations = $product->get_available_variations();

        $attributes = array();

        $position = 1;

        foreach ($available_variations as $key => $variations) 
        { 
            $a = array();

            $a['id'] = $variations['variation_id'];
            $a['product_id'] = $p['id'];
            $a['title'] = $p['title'];
            $a['price'] = $variations['display_regular_price'];
            $a['sku'] = $variations['sku'];
            $a['position'] = $position;
            $a['inventory_policy'] = null;
            $a['compare_at_price'] = $variations['display_price'];
            $a['fulfillment_service'] = null;
            $a['inventory_management'] = null;

            $a['created_at'] = $p['created_at'];
            $a['updated_at'] = $p['updated_at'];
            $a['taxable'] = wc_prices_include_tax() ? true : false;
            $a['barcode'] = null;
            $a['grams'] = null;
            $a['image_id'] = $variations['image_id'];

            $a['weight'] = $variations['weight'];
            $a['weight_unit'] = get_option('woocommerce_weight_unit');

            $a['inventory_item_id'] = null;
            $a['inventory_quantity'] = $variations['max_qty'];

            $a['old_inventory_quantity'] = null;
            $a['presentment_prices'] = null;
            $a['requires_shipping'] = $product->get_virtual() ? false : true;

            // get product variant options
            $option_count = 1;
            foreach($variations['attributes'] as $key => $val){

                if(isset($val) && !empty($val)){

                    // variant options are stored with prefix attribute_pa_*, remove prefix and get option name
                    $option_name = substr($key, 9); // $key is attribute_pa_* or attribute_*
                    $a['title'] = $option_name;
                    $a['option'.$option_count] = $val;

                    $option_count++;
                }

            }

            $position++;

            $attributes[] = $a;

        }

        $p['variants'] = $attributes;

    }else{

        $a['id'] = $p['id'];
        $a['product_id'] = $p['id'];
        $a['title'] = $p['title'];
        $a['price'] = $product->get_regular_price();
        $a['sku'] = $product->get_sku();
        $a['position'] = 1;
        $a['inventory_policy'] = null;
        $a['compare_at_price'] = $product->get_sale_price();
        $a['fulfillment_service'] = null;
        $a['inventory_management'] = null;
        $a['option1'] = null;
        $a['option2'] = null;
        $a['option3'] = null;
        $a['created_at'] = $p['created_at'];
        $a['updated_at'] = $p['updated_at'];
        $a['taxable'] = wc_prices_include_tax() ? true : false;
        $a['barcode'] = null;
        $a['grams'] = null;
        $a['image_id'] = $product->get_image_id();
        $a['weight'] = $product->get_weight();
        $a['weight_unit'] = get_option('woocommerce_weight_unit');

        $a['inventory_item_id'] = null;
        $a['inventory_quantity'] = $product->get_stock_quantity();

        $a['old_inventory_quantity'] = null;
        $a['presentment_prices'] = null;
        $a['requires_shipping'] = $product->get_virtual() ? false : true;

        $attributes[] = $a;

        $p['variants'] = $attributes;
        
    }

    // get product main image
    $image_id        = $product->get_image_id();
    $img = array();
    if ( $image_id ) {
        $image_url = wp_get_attachment_image_url( $image_id, 'full' );

        $img[] = array('id' => $image_id, 
                        'product_id' => $p['id'], 
                        'src' => $image_url, 
                        'position' => 1, 
                        'created_at' => $p['created_at'], 
                        'updated_at' => $p['updated_at'],
                        'alt' => null,
                        'width' => null,
                        'height' => null,
                        'variant_ids' => array()

                    );
    }

    // get product additional images
    $attachment_ids  = $product->get_gallery_image_ids();
    $position = 2;

    foreach ( $attachment_ids as $attachment_id ) {
        $image_url = wp_get_attachment_url( $attachment_id );

        $img[] = array('id' => $attachment_id, 
                        'product_id' => $p['id'], 
                        'src' => $image_url, 
                        'position' => $position, 
                        'created_at' => $p['created_at'], 
                        'updated_at' => $p['updated_at'],
                        'alt' => null,
                        'width' => null,
                        'height' => null,
                        'variant_ids' => array()
                    );

        $position++;
    }

    $p['images'] = $img;

    $p['options'] = array();   

    return $p;
}


/**
* prepare woocommerce order detail by order object
*
* @param object $order order object
*
* @return array order detail
*/
function aitrillion_get_order( $order ){


    if ( is_a( $order, 'WC_Order_Refund' ) ) {

        return false;
    }

    // map wooocommerce order status with shopify
    if($order->get_status() == 'completed'){

        $c_date = $order->get_date_completed();
        $date_completed = $c_date->date_i18n('Y-m-d H:i:s');    

        $o['fulfillment_status'] = 'fulfilled'; 
        $o['financial_status'] = 'paid';
        
    }elseif($order->get_status() == 'cancelled'){

        $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');

        $o['fulfillment_status'] = 'unfulfilled'; 
        $o['financial_status'] = 'voided';

    }elseif($order->get_status() == 'refunded'){
        
        $date_completed = $order->get_date_modified()->date('Y-m-d H:i:s');

        $o['fulfillment_status'] = 'unfulfilled'; 
        $o['financial_status'] = 'refunded';

    }else{

        $date_completed = null;

        $o['fulfillment_status'] = 'unfulfilled'; 
        
        if($order->get_status() == 'processing' || $order->get_status() == 'on-hold'){
            $o['financial_status'] = 'paid';
        } else {
            $o['financial_status'] = 'unpaid';
        }
        
    }

    $o['id'] = $order->get_id();
    $o['email'] = $order->get_billing_email();
    $o['created_at'] = $order->get_date_created()->date('Y-m-d H:i:s');
    $o['updated_at'] = $order->get_date_modified()->date('Y-m-d H:i:s');
    $o['note'] = $order->get_customer_note();
    $o['closed_at'] = $date_completed;
    $o['confirmed'] = true;
    $o['number'] = $order->get_id();
    $o['gateway'] = $order->get_payment_method();
    $o['currency'] = $order->get_currency();
    $o['browser_ip'] = $order->get_customer_ip_address();
    $o['cart_token'] = $order->get_meta('_aio_card_id');
    $o['token'] = '';
    $o['tags'] = '';
    $o['landing_site'] = '';
    $o['landing_site_ref'] = '';
    $o['location_id'] = '';
    $o['payment_gateway_names'] = array($order->get_payment_method());
    $o['location_id'] = '';
    $o['buyer_accepts_marketing'] = false;
    $o['app_id'] = '';
    $o['source_name'] = 'web';
    $o['total_price'] = $order->get_total();
    $o['total_price_usd'] = '';
    $o['subtotal_price'] = $order->get_subtotal();
    $o['total_tax'] = $order->get_tax_totals();
    $o['total_discounts'] = $order->get_discount_total();
    $o['total_line_items_price'] = $order->get_subtotal();
    $o['referring_site'] = '';
    $o['order_status_url'] = $order->get_view_order_url();

    // if discount coupon applied to order, get the details
    if($order->get_coupon_codes()){

        $coupon_codes = $order->get_coupon_codes();

        foreach($coupon_codes as $code){

            $c = new WC_Coupon($code); 
            $o['discount_codes'][] = array('code' => $code, 'amount' => $c->get_amount(), 'type' => $c->get_discount_type());

        }

    }else{
        $o['discount_codes'] = array();
    }

    $o['user_id'] = $order->get_user_id();
    $o['processing_method'] = 'direct';
    $o['processed_at'] = $date_completed;
    $o['phone'] = $order->get_billing_phone();
    $o['order_number'] = $order->get_id();
    $o['name'] = $order->get_id();

    if($order->get_status() == 'cancelled'){
        $o['cancelled_at'] = $order->get_date_modified()->date('Y-m-d H:i:s');
    }else{
        $o['cancelled_at'] = '';    
    }
    
    $o['cancel_reason'] = '';
    $o['contact_email'] = $order->get_billing_email();

    $o['note_attributes'] = array();  
    $note_attribute = array();

    $sales_cookie = $order->get_meta('_aio_shopify_ref');

    if($sales_cookie){
        $note_attribute[] = array('name' => 'aio_shopify_ref', 'value' => $sales_cookie);

        $o['note_attributes'] = $note_attribute;
    }

    $affiliate_cookie = $order->get_meta('_aio_affiliate_code');

    if($affiliate_cookie){
        $note_attribute[] = array('name' => 'aio_affiliate_code', 'value' => $affiliate_cookie);

        $o['note_attributes'] = $note_attribute;
    }

    $line_items = array();

    foreach ( $order->get_items() as $item_id => $item ) {

        $i['id'] = $item_id;

        $product = $item->get_product(); 

        $i['type_id'] = $product->get_type();
        $i['variant_id'] = $item->get_variation_id();
        $i['product_id'] = $item->get_product_id();
        $i['title'] = $item->get_name();
        $i['quantity'] = $item->get_quantity();
        $i['sku'] = $product->get_sku();
        $i['price'] = $product->get_price();
        $i['total_discount'] = '';
        $i['fulfillment_status'] = ($order->get_status() == 'completed') ? 'shipped' : 'unshipped';
        $i['gift_card'] = false;

        $line_items[] = $i;
    }

    $o['line_items'] = $line_items;

    $o['billing_address']['first_name'] = $order->get_billing_first_name();
    $o['billing_address']['last_name'] = $order->get_billing_last_name();
    $o['billing_address']['address1'] = $order->get_billing_address_1();
    $o['billing_address']['address2'] = $order->get_billing_address_2();
    $o['billing_address']['phone'] = $order->get_billing_phone();
    $o['billing_address']['city'] = $order->get_billing_city();
    $o['billing_address']['zip'] = $order->get_billing_postcode();
    $o['billing_address']['province'] = $order->get_billing_state();
    $o['billing_address']['country'] = $order->get_billing_country();
    $o['billing_address']['company'] = $order->get_billing_company();
    $o['billing_address']['latitude'] = '';
    $o['billing_address']['longitude'] = '';
    $o['billing_address']['name'] = $order->get_formatted_billing_full_name();
    $o['billing_address']['country_code'] = $order->get_billing_country();
    $o['billing_address']['province_code'] = $order->get_billing_state();

    $o['shipping_address']['first_name'] = $order->get_shipping_first_name();
    $o['shipping_address']['last_name'] = $order->get_shipping_last_name();
    $o['shipping_address']['address1'] = $order->get_shipping_address_1();
    $o['shipping_address']['address2'] = $order->get_shipping_address_2();
    $o['shipping_address']['phone'] = $order->get_billing_phone();
    $o['shipping_address']['city'] = $order->get_shipping_city();
    $o['shipping_address']['zip'] = $order->get_shipping_postcode();
    $o['shipping_address']['province'] = $order->get_shipping_state();
    $o['shipping_address']['country'] = $order->get_shipping_country();
    $o['shipping_address']['company'] = $order->get_shipping_company();
    $o['shipping_address']['latitude'] = '';
    $o['shipping_address']['longitude'] = '';
    $o['shipping_address']['name'] = $order->get_formatted_shipping_full_name();
    $o['shipping_address']['country_code'] = $order->get_shipping_country();
    $o['shipping_address']['province_code'] = $order->get_shipping_state();

    $o['shipping_lines']['id'] = 1;
    $o['shipping_lines']['title'] = $order->get_shipping_method();
    $o['shipping_lines']['price'] = $order->get_shipping_total();
    $o['shipping_lines']['code'] = null;
    $o['shipping_lines']['source'] = 'wordpress';
    $o['shipping_lines']['phone'] = null;
    $o['shipping_lines']['requested_fulfillment_service_id'] = null;
    $o['shipping_lines']['delivery_category'] = null;
    $o['shipping_lines']['carrier_identifier'] = null;
    $o['shipping_lines']['discounted_price'] = null;
    $o['shipping_lines']['tax_lines'] = array();

    $o['customer']['id'] = $order->get_customer_id();
    $o['customer']['email'] = $order->get_billing_email();

    
    $customer_id = $order->get_customer_id();
    $user_id = $order->get_user_id();

    $o['customer']['guest'] = $order->get_user_id() == 0 ? 'yes' : 'no';
    $o['customer']['phone'] = $order->get_billing_phone();


    // if order is placed by registered user, get customer detail, otherwise, get guest user detail
    if($order->get_customer_id()){

        $customer = new WC_Customer( $order->get_customer_id() );
        $o['customer']['first_name'] = $customer->get_first_name();
        $o['customer']['last_name'] = $customer->get_last_name();
        $o['customer']['created_at'] = $customer->get_date_created()->date('Y-m-d H:i:s');
        $o['customer']['updated_at'] = $customer->get_date_modified()->date('Y-m-d H:i:s');
        $o['customer']['verified_email'] = true;

        $last_order = $customer->get_last_order();

       if(!empty($last_order)){
            
            $last_order_id = $last_order->get_id();

            $o['customer']['last_order_name'] = $last_order_id;
            $o['customer']['last_order_id'] = $last_order_id;

       }else{

            $o['customer']['last_order_name'] = null;
            $o['customer']['last_order_id'] = null;
       }

        $o['customer']['orders_count'] = $customer->get_order_count();
        $o['customer']['total_spent'] = $customer->get_total_spent();

        $o['customer']['default_address'] = array(
                                            'id' => 1,
                                            'customer_id' => $order->get_customer_id(),
                                            'first_name' => $customer->get_billing_first_name(),
                                            'last_name' => $customer->get_billing_last_name(),
                                            'company' => $customer->get_billing_company(),
                                            'address1' => $customer->get_billing_address_1(),
                                            'address2' => $customer->get_billing_address_2(),
                                            'city' => $customer->get_billing_city(),
                                            'province' => $customer->get_billing_state(),
                                            'zip' => $customer->get_billing_postcode(),
                                            'phone' => $customer->get_billing_phone(),
                                            'name' => $customer->get_billing_first_name().' '.$customer->get_billing_last_name(),
                                            'country_code' => $customer->get_billing_country(),
                                            'default' => true,

                                        );

    }else{

        $o['customer']['first_name'] = $order->get_billing_first_name();
        $o['customer']['last_name'] = $order->get_billing_last_name();
        $o['customer']['created_at'] = '';
        $o['customer']['updated_at'] = '';
        $o['customer']['verified_email'] = false;

        $o['customer']['last_order_name'] = null;  
        $o['customer']['last_order_id'] = null;    

        $o['customer']['orders_count'] = 0;
        $o['customer']['total_spent'] = 0;

        $o['customer']['default_address'] = array(
                                            'id' => 1,
                                            'customer_id' => $order->get_customer_id(),
                                            'first_name' => $order->get_billing_first_name(),
                                            'last_name' => $order->get_billing_last_name(),
                                            'company' => $order->get_billing_company(),
                                            'address1' => $order->get_billing_address_1(),
                                            'city' => $order->get_billing_city(),
                                            'province' => $order->get_billing_state(),
                                            'zip' => $order->get_billing_postcode(),
                                            'phone' => $order->get_billing_phone(),
                                            'name' => $order->get_formatted_billing_full_name(),
                                            'country_code' => $order->get_billing_country(),
                                            'default' => true
                                        );
    }

    $o['fulfillments'] = array();

    // if order is refunded, get order refund detail
    $order_refunds = $order->get_refunds();
    if($order_refunds){

        $order_refund = $order_refunds[0];

        $o['refunds']['id'] = $order_refund->get_id();
        $o['refunds']['order_id'] = $order->get_id();
        $o['refunds']['created_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
        $o['refunds']['note'] = $order_refund->get_refund_reason();
        $o['refunds']['user_id'] = $order->get_user_id();
        $o['refunds']['processed_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
        $o['refunds']['refund_line_items'] = $line_items;

        $transactions['id'] = $order_refund->get_id();
        $transactions['amount'] = $order_refund->get_amount();
        $transactions['created_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');
        $transactions['currency'] = $order->get_currency();
        $transactions['currency'] = $order->get_currency();
        $transactions['order_id'] = $order->get_id();
        $transactions['processed_at'] = $order_refund->get_date_created()->date('Y-m-d H:i:s');

        $o['refunds']['transactions'] = $transactions;
        $o['refunds']['order_adjustments'] = array();

    }else{
        $o['refunds'] = array();    
    }

    return $o;
}


/**
* prepare woocommerce category detail by category object
*
* @param object $category category object
*
* @return array category detail
*/
function aitrilltion_get_category( $category ){

    $c['id'] = $category->cat_ID;
    $c['parent_id'] = $category->parent;
    $c['handle'] = get_term_link( $category->cat_ID, 'product_cat' );
    $c['title'] = $category->name;
    $c['relative_title'] = $category->name;
    $c['products_count'] = $category->count;
    $c['updated_at'] = '';
    $c['body_html'] = $category->category_description;
    $c['published_at'] = '';

    // get category images
    $thumbnail_id = get_woocommerce_term_meta( $category->cat_ID, 'thumbnail_id', true );

    if($thumbnail_id){

        $image        = wp_get_attachment_url( $thumbnail_id );
        $image_meta        = wp_get_attachment_metadata( $thumbnail_id );

        $c['images'] = array(
                            'created_at' => get_the_date( 'Y-m-d H:i:s', $thumbnail_id ),
                            'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
                            'width' => $image_meta['width'],
                            'height' => $image_meta['height'],
                            'src' => $image
                        );
    }else{
        $c['images'] = array();
    }

    return $c;
}
?>