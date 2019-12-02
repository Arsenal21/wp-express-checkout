<?php

/* 
 * This class is used to prcoess the payment after successful charge.
 * 
 * Inserts the payment date to the orders menu
 * Sends notification emails.
 * Triggers after payment processed hook: wpec_payment_completed
 * Sends to Thank You page.
 */

class WPEC_Process_IPN {

    protected static $instance = null;

    function __construct() {
        add_action('wp_ajax_wpec_process_payment', array($this, 'wpec_process_payment'));
        add_action('wp_ajax_nopriv_wpec_process_payment', array($this, 'wpec_process_payment'));
    }

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function wpec_process_payment() {
        if (!isset($_POST['wp_ppdg_payment'])) {
            //no payment data provided
            _e('No payment data received.', 'paypal-express-checkout');
            exit;
        }
        $payment = $_POST['wp_ppdg_payment'];

        if (strtoupper($payment['status']) !== 'COMPLETED') {
            //payment is unsuccessful
            printf(__('Payment is not approved. Status: %s', 'paypal-express-checkout'), $payment['status']);
            exit;
        }

        // get item name
        $item_name = $payment['purchase_units'][0]['description'];
        // let's check if the payment matches transient data
        $trans_name = 'wp-ppdg-' . sanitize_title_with_dashes($item_name);
        $trans = get_transient($trans_name);
        if (!$trans) {
            //no price set
            _e('No transaction info found in transient.', 'paypal-express-checkout');
            exit;
        }
        $price = $trans['price'];
        $quantity = $trans['quantity'];
        $currency = $trans['currency'];
        $url = $trans['url'];

        if ($trans['custom_quantity']) {
            //custom quantity enabled. let's take quantity from PayPal results
            $quantity = $payment['purchase_units'][0]['items'][0]['quantity'];
        }

        $amount = $payment['purchase_units'][0]['amount']['value'];

        //check if amount paid matches price x quantity
        if ($amount != $price * $quantity) {
            //payment amount mismatch
            _e('Payment amount mismatch original price.', 'paypal-express-checkout');
            exit;
        }

        //check if payment currency matches
        if ($payment['purchase_units'][0]['amount']['currency_code'] !== $currency) {
            //payment currency mismatch
            _e('Payment currency mismatch.', 'paypal-express-checkout');
            exit;
        }

        //If code execution got this far, it means everything is ok with payment
        //let's insert order
        $order = OrdersWPEC::get_instance();

        $order->insert(array(
            'item_name' => $item_name,
            'price' => $price,
            'quantity' => $quantity,
            'amount' => $amount,
            'currency' => $currency,
            'state' => $payment['status'],
            'id' => $payment['id'],
            'create_time' => $payment['create_time'],
        ), $payment['payer']);

        //TODO - Send email notifications.
        
        //Trigger the action hook
        do_action('wpec_payment_completed', $payment);

        //Thank you message
        $res = array();
        $res['title'] = __('Payment Completed', 'paypal-express-checkout');

        $thank_you_msg = '<div class="wp_ppdg_thank_you_message"><p>' . __('Thank you for your purchase.', 'paypal-express-checkout') . '</p>';
        $click_here_str = sprintf(__('Please <a href="%s">click here</a> to download the file.', 'paypal-express-checkout'), base64_decode($url));
        $thank_you_msg .= '<br /><p>' . $click_here_str . '</p></div>';
        $thank_you_msg = apply_filters('wp_ppdg_thank_you_message', $thank_you_msg);
        $res['msg'] = $thank_you_msg;

        echo json_encode($res);

        exit;
    }

}
