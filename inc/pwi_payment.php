<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * PWI Payment
 *
 * @class       PWI Payment
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce/Classes/Payment
 * @author      WooThemes
 */
class PWI_Payment extends WC_Payment_Gateway
{

    /** @var array Array of locales */
    public $locale;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {

        $this->id = 'pay_with_iota';
        $this->icon = apply_filters('woocommerce_pay_with_iota_icon', '');
        $this->has_fields = false;
        $this->method_title = __('Pay with IOTA', 'woocommerce');
        $this->method_description = __('Pay with IOTA. Now user can pay with IOTA', 'woocommerce');


        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->api_host = $this->get_option('api_host');
        $this->api_path = $this->get_option('api_path');
        $this->api_ipn_url = $this->get_option('api_ipn_url');
        $this->api_token = $this->get_option('api_token');
        $this->ipn_verify_code = $this->get_option('ipn_verify_code');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled'   => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Pay with IOTA', 'woocommerce'),
                'default' => 'no',
            ),
            'api_host'  => array(
                'title'       => __('API HOST', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Define API host for example https://paywithiota.com', 'woocommerce'),
                'desc_tip'    => true,
                'default'     => 'https://paywithiota.com',
            ),
            'api_path'  => array(
                'title'       => __('API PATH', 'woocommerce'),
                'type'        => 'text',
                'description' => __('API access point', 'woocommerce'),
                'desc_tip'    => true,
                'default'     => 'api',
            ),
            'api_token' => array(
                'title'       => __('API TOKEN', 'woocommerce'),
                'type'        => 'text',
                'description' => sprintf(__('<a target="_blank" href="%s">' . __('How to get API Token', 'woocommerce') . '</a>.', 'woocommerce'),
                    'https://paywithiota.com/settings#/api'),
            ),

            'ipn_verify_code' => array(
                'title'       => __('IPN Verify Code', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Verify that code in IPN request', 'woocommerce'),
                'desc_tip'    => true
            ),

        );
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {

        require_once 'pwi-payment-request.php';

        $response = array(
            'result' => 'fail'
        );

        $this->order = wc_get_order($order_id);
        $requestPayment = new PWI_Payment_Request($this);
        $paymentId = $requestPayment->create_payment_id();

        if ($paymentId) {
            $return_url = $this->get_return_url($this->order);
            $response = array(
                'result'   => 'success',
                'redirect' => $this->api_host . '/pay?return_url=' . $return_url . '&payment_id=' . $paymentId
            );
        }

        return $response;
    }

    /**
     * Get gateway icon.
     * @return string
     */
    public function get_icon()
    {
        $icon_html = __('Pay with IOTA', 'woocommerce') . '<a target="_blank" href="https://paywithiota.com/">' . __('What is IOTA',
                'woocommerce') . '</a><img src="' . plugins_url('woocommerce-Pay-with-IOTA\images\iota-logo.png',
                'woocommerce-Pay-with-IOTA') . '" alt="' . esc_attr__('Pay with IOTA acceptance mark', 'woocommerce') . '" />';

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

}
