<?php

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * PWI payment IPN handle
 */
class PWI_Payment_IPN_handle
{
    function __construct()
    {
        if (isset($_GET['pay-with-iota-ipn']) && $_GET['pay-with-iota-ipn'] == 1) {
            $response = [
                'status'  => 0,
                'message' => ''
            ];
            // Get settings
            $this->settings = get_option('woocommerce_pay_with_iota_settings');

            // Get data from ipn request
            $requestedData = json_decode(file_get_contents('php://input'), true);

            // Verify the ipn request
            if (isset($requestedData['ipn_verify_code']) && $requestedData['ipn_verify_code']
                && isset($this->settings['ipn_verify_code']) && $this->settings['ipn_verify_code'] == $requestedData['ipn_verify_code']
                && isset($requestedData['status']) && $requestedData['status'] == 1
            ) {
                // Get order detail by invoice id
                $order = wc_get_order($requestedData['invoice_id']);

                // Check order amount and currency
                if ($order->get_total() == $requestedData['custom']['price'] && strtoupper(trim($order->get_currency())) == strtoupper($requestedData['custom']['currency'])) {

                    // Check if order has cancelled
                    if ($order->has_status('cancelled')) {
                        $this->send_ipn_email_notification(
                            sprintf(__('Payment for cancelled order %s received', 'woocommerce'),
                                '<a class="link" href="' . esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')) . '">' . $order->get_order_number() . '</a>'),
                            sprintf(__('Order #%1$s has been marked paid by Pay with IOTA, but was previously cancelled. Admin handling required.',
                                'woocommerce'),
                                $order->get_order_number())
                        );
                    }

                    // Add note for order
                    $order->add_order_note(__('Paid by Pay with IOTA', 'woocommerce'));

                    // Make order complete
                    if ($order->payment_complete()) {
                        $response = [
                            'status'  => 1,
                            'message' => 'Order status has been updated'
                        ];
                    }
                }
            }

            echo json_encode($response);
            die;
        }
    }

    /**
     * Send ipn email notification
     *
     * @param $subject
     * @param $message
     */
    function send_ipn_email_notification($subject, $message)
    {
        $new_order_settings = get_option('woocommerce_new_order_settings', array());
        $mailer = WC()->mailer();
        $message = $mailer->wrap_message($subject, $message);
        $mailer->send(! empty($new_order_settings['recipient']) ? $new_order_settings['recipient'] : get_option('admin_email'), strip_tags($subject), $message);
    }
}