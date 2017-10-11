<?php
/**
 * Plugin Name: WooCommerce Pay with IOTA
 * Plugin URI: https://paywithiota.com
 * Description: Pay with IOTA. Now user can pay with IOTA
 * Version: 1.0
 * Author: Centire Inc
 * Author URI: https://centire.in/
 * Tested up to: 4.8.1
 *
 */

// Make sure WooCommerce is active
if ( ! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Call this hook when plugins loaded
add_action('plugins_loaded', 'pwi_init', 11);

function pwi_init()
{
    require_once 'inc/pwi_payment.php';
}

add_filter('woocommerce_payment_gateways', 'pwi_add_pay_with_IOTA_into_woocommerce');

// Added payment gateway
function pwi_add_pay_with_IOTA_into_woocommerce($gateways)
{
    $gateways[] = 'PWI_Payment';

    return $gateways;
}

add_action('wp', function (){

    require_once 'inc/pwi-payment-ipn-handle.php';
    new PWI_Payment_IPN_handle();
});