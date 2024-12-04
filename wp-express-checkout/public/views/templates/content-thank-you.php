<?php
/**
 * Thank You page template
 *
 * @package wp-express-checkout
 */

use WP_Express_Checkout\Utils;
use WP_Express_Checkout\View_Downloads;

$payer            = $order->get_data( 'payer' );

$billing_address = '';
if ( isset( $payer['address'] ) && is_array( $payer['address'] ) && count( $payer['address'] ) > 1 ) {
	$billing_address = implode( ', ', (array) $payer['address'] );
}

$shipping_address = $order->get_data( 'shipping_address' );
$subtotal         = 0;
$product          = $order->get_item( 'ppec-products' );
$coupon           = $order->get_item( 'coupon' );
$variations       = $order->get_selected_variations();
$tax_amount       = ! empty( $order->get_item( 'tax' ) ) ? $order->get_item( 'tax' )['price'] : 0;
$shipping_amount  = ! empty( $order->get_item( 'shipping' ) ) ? $order->get_item( 'shipping' )['price'] : 0;
$downloads = View_Downloads::get_order_downloads_list( $order->get_id() );

$trials = array();

$trial_payment_discount = $order->get_item( 'trial_discount' );
if (!empty($trial_payment_discount)){
	$trials[] = $trial_payment_discount;
}

$trial_payment = $order->get_item( 'trial' );
if (!empty($trial_payment)){
	$trials[] = $trial_payment;
}

?>

<div class="wpec-order-details-wrap">
    <h4><?php _e( 'Thank you for your purchase.', 'wp-express-checkout' ); ?></h4>
    <div class="wpec-order-data-box">
        <div class="wpec-order-data-box-col wpec-order-data-box-col-date">
            <div class="wpec-order-data-box-col-label"><?php _e( "Date", "wp-express-checkout" ); ?></div>
            <div class="wpec-order-data-box-col-value"><?php esc_attr_e(get_post_time( 'F j, Y', false, $order->get_id() ));?></div>
        </div>
        <div class="wpec-order-data-box-col wpec-order-data-box-col-email">
            <div class="wpec-order-data-box-col-label"><?php _e( "Email", "wp-express-checkout" ); ?></div>
            <div class="wpec-order-data-box-col-value"><?php esc_attr_e($order->get_email_address()); ?></div>
        </div>
        <div class="wpec-order-data-box-col wpec-order-data-box-col-txn-id">
            <div class="wpec-order-data-box-col-label"><?php _e( "Transaction ID", "wp-express-checkout" ); ?></div>
            <div class="wpec-order-data-box-col-value"><?php esc_attr_e($order->get_capture_id()); ?></div>
        </div>
    </div>

    <h4 class="wpec-order-details-heading"><?php _e( "Order Details", "wp-express-checkout" ); ?></h4>

    <table class="wpec-order-details-table">
        <thead>
            <tr>
                <th style="text-align: start"><?php _e( "Item", "wp-express-checkout" ); ?></th>
                <th style="text-align: end"><?php _e( "Total", "wp-express-checkout" ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( is_array( $product ) && ! empty( $product ) ) {
                $price_amount           = $product['price'] * $product['quantity'];
                $formatted_price_amount = Utils::price_format( $price_amount, $order->get_currency() );
                $product_name_str = $product['name'];
                if (intval($product['quantity']) > 1){
                    $product_name_str = $product_name_str  . ' x' . $product['quantity'];
                }

                echo '<tr><td style="text-align: start">' . esc_attr( $product_name_str ) . '</td><td style="text-align: end">' . esc_attr( $formatted_price_amount ) . '</td></tr>';

                $subtotal += $price_amount;
            } ?>

            <?php if ( is_array( $variations ) && ! empty( $variations ) ) {
                foreach ( $variations as $variation ) {
                    $price_amount           = $variation['price'] * $variation['quantity'];
                    $formatted_price_amount = Utils::price_format( $price_amount, $order->get_currency() );
	                $variation_name_str = $variation['name'];
	                if (intval($variation['quantity']) > 1){
		                $variation_name_str = $variation_name_str  . ' x' . $variation['quantity'];
	                }
                    echo '<tr><td style="text-align: start">' . esc_attr( $variation_name_str ) .'</td><td style="text-align: end">' . esc_attr( $formatted_price_amount ) . '</td></tr>';

                    $subtotal += $price_amount;
                }
            } ?>

            <tr>
                <th style="text-align: start"><?php _e( "Subtotal: ", "wp-express-checkout" ); ?></th>
                <th style="text-align: end"><?php echo Utils::price_format( $subtotal, $order->get_currency() ) ?></th>
            </tr>

            <?php if ( is_array( $trials ) && ! empty( $trials ) ) {
                foreach ( $trials as $trial ) {
                    $price_amount           = $trial['price'] * $trial['quantity'];
                    $formatted_price_amount = Utils::price_format( $price_amount, $order->get_currency() );
	                $trial_name_str = $trial['name'];
	                if (intval($trial['quantity']) > 1){
		                $trial_name_str = $trial_name_str  . ' x' . $trial['quantity'];
	                }
                    echo '<tr><td style="text-align: start">' . esc_attr( $trial_name_str ) .'</td><td style="text-align: end">' . esc_attr( $formatted_price_amount ) . '</td></tr>';

                    $subtotal += $price_amount;
                }
            } ?>

            <?php if ( is_array( $coupon ) && ! empty( $coupon ) ) {
                $price_amount           = $coupon['price'];
                $formatted_price_amount = Utils::price_format( $price_amount, $order->get_currency() );
                echo '<tr><td style="text-align: start">' . esc_attr( $coupon['name'] ) . '</td><td style="text-align: end">' . esc_attr( $formatted_price_amount ) . '</td></tr>';
            } ?>

            <?php if ( ! empty( floatval( $tax_amount ) ) ) { ?>
                <tr>
                    <td style="text-align: start"><?php _e( "Tax: ", "wp-express-checkout" ); ?></td>
                    <td style="text-align: end"><?php echo Utils::price_format( $tax_amount, $order->get_currency() ) ?></td>
                </tr>
            <?php } ?>

            <?php if ( ! empty( floatval( $shipping_amount ) ) ) { ?>
                <tr>
                    <td style="text-align: start"><?php _e( "Shipping: ", "wp-express-checkout" ); ?></td>
                    <td style="text-align: end"><?php echo Utils::price_format( $shipping_amount, $order->get_currency() ) ?></td>
                </tr>
            <?php } ?>

            <tr>
                <th style="text-align: start"><?php _e( "Total Amount: ", "wp-express-checkout" ); ?></th>
                <th style="text-align: end"><?php echo Utils::price_format( $order->get_total(), $order->get_currency() ) ?></th>
            </tr>
        </tbody>
    </table>

	<?php if ( is_array( $downloads ) && ! empty( $downloads ) ) { ?>
        <h4><?php _e( "Downloads", "wp-express-checkout" ); ?></h4>
        <table class="wpec-order-downloads-table">
            <thead>
                <tr>
                    <th style="text-align: start"><?php _e( "Item", "wp-express-checkout" ); ?></th>
                    <th style="text-align: end"><?php _e( "Download Link", "wp-express-checkout" ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $downloads as $dl_name => $dl_url ) { ?>
                <tr>
                    <td><?php echo esc_attr( $dl_name ) ?></td>
                    <td style="text-align: end"><a class="wpec-order-downloadable-item-link" href="<?php echo esc_url( $dl_url ) ?>"
                           target="_blank"><?php _e( "Download", "wp-express-checkout" ) ?></a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
	<?php } ?>

	<?php if ( ! empty( $shipping_address ) ) { ?>
        <div class="wpec-order-additional-data-box wpec-order-additional-data-box-shipping-address">
            <h4><?php _e( "Shipping Address", "wp-express-checkout" ); ?></h4>
            <div class="wpec-order-shipping-address"><?php esc_attr_e($shipping_address); ?></div>
        </div>
	<?php } ?>

	<?php if ( ! empty( $billing_address ) ) { ?>
        <div class="wpec-order-additional-data-box wpec-order-additional-data-box-billing-address">
            <h4><?php _e( "Billing Address", "wp-express-checkout" ); ?></h4>
            <div class="wpec-order-billing-address"><?php esc_attr_e( $billing_address ); ?></div>
        </div>
	<?php } ?>
</div>