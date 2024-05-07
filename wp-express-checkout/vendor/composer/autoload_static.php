<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitecb54881939c5593641ad428bda777ea
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Sample\\' => 7,
        ),
        'P' => 
        array (
            'PayPalHttp\\' => 11,
            'PayPalCheckoutSdk\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Sample\\' => 
        array (
            0 => __DIR__ . '/..' . '/paypal/paypal-checkout-sdk/samples',
        ),
        'PayPalHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/paypal/paypalhttp/lib/PayPalHttp',
        ),
        'PayPalCheckoutSdk\\' => 
        array (
            0 => __DIR__ . '/..' . '/paypal/paypal-checkout-sdk/lib/PayPalCheckoutSdk',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'WPEC_Admin_User_Feedback' => __DIR__ . '/../..' . '/admin/includes/class-admin-user-feedback.php',
        'WP_Express_Checkout\\Admin\\Admin' => __DIR__ . '/../..' . '/admin/class-admin.php',
        'WP_Express_Checkout\\Admin\\Admin_Order_Summary_Table' => __DIR__ . '/../..' . '/admin/includes/class-admin-order-summary-table.php',
        'WP_Express_Checkout\\Admin\\Coupons_List' => __DIR__ . '/../..' . '/admin/includes/class-coupons-list.php',
        'WP_Express_Checkout\\Admin\\Orders_List' => __DIR__ . '/../..' . '/admin/includes/class-orders-list.php',
        'WP_Express_Checkout\\Admin\\Orders_Meta_Boxes' => __DIR__ . '/../..' . '/admin/includes/class-orders-meta-boxes.php',
        'WP_Express_Checkout\\Admin\\Products_List' => __DIR__ . '/../..' . '/admin/includes/class-products-list.php',
        'WP_Express_Checkout\\Admin\\Products_Meta_Boxes' => __DIR__ . '/../..' . '/admin/includes/class-products-meta-boxes.php',
        'WP_Express_Checkout\\Admin\\Tools' => __DIR__ . '/../..' . '/admin/class-tools.php',
        'WP_Express_Checkout\\Blocks\\Dynamic_Block' => __DIR__ . '/../..' . '/includes/blocks/class-dynamic-block.php',
        'WP_Express_Checkout\\Blocks\\Product_Block' => __DIR__ . '/../..' . '/includes/blocks/product-block/class-product-block.php',
        'WP_Express_Checkout\\Categories' => __DIR__ . '/../..' . '/includes/class-categories.php',
        'WP_Express_Checkout\\Coupons' => __DIR__ . '/../..' . '/includes/class-coupons.php',
        'WP_Express_Checkout\\Debug\\Logger' => __DIR__ . '/../..' . '/includes/class-logger.php',
        'WP_Express_Checkout\\Emails' => __DIR__ . '/../..' . '/includes/class-emails.php',
        'WP_Express_Checkout\\Init' => __DIR__ . '/../..' . '/includes/class-init.php',
        'WP_Express_Checkout\\Integrations' => __DIR__ . '/../..' . '/includes/class-integrations.php',
        'WP_Express_Checkout\\Integrations\\Emember' => __DIR__ . '/../..' . '/includes/integrations/emember/class-emember.php',
        'WP_Express_Checkout\\Integrations\\License_Manager' => __DIR__ . '/../..' . '/includes/integrations/license-manager/class-license-manager.php',
        'WP_Express_Checkout\\Integrations\\Simple_WP_Membership' => __DIR__ . '/../..' . '/includes/integrations/simple-wp-membership/class-simple-wp-membership.php',
        'WP_Express_Checkout\\Integrations\\WooCommerce_Gateway' => __DIR__ . '/../..' . '/includes/integrations/woocommerce/class-woocommerce-gateway.php',
        'WP_Express_Checkout\\Integrations\\WooCommerce_Payment_Button' => __DIR__ . '/../..' . '/includes/integrations/woocommerce/class-woocommerce-payment-button.php',
        'WP_Express_Checkout\\Integrations\\WooCommerce_Payment_Processor' => __DIR__ . '/../..' . '/includes/integrations/woocommerce/class-woocommerce-payment-processor.php',
        'WP_Express_Checkout\\Main' => __DIR__ . '/../..' . '/public/class-main.php',
        'WP_Express_Checkout\\Order' => __DIR__ . '/../..' . '/includes/class-order.php',
        'WP_Express_Checkout\\Order_Summary_Plain' => __DIR__ . '/../..' . '/includes/class-order-summary-plain.php',
        'WP_Express_Checkout\\Order_Summary_Table' => __DIR__ . '/../..' . '/includes/class-order-summary-table.php',
        'WP_Express_Checkout\\Order_Tags_Html' => __DIR__ . '/../..' . '/public/includes/class-order-tags-html.php',
        'WP_Express_Checkout\\Order_Tags_Plain' => __DIR__ . '/../..' . '/public/includes/class-order-tags-plain.php',
        'WP_Express_Checkout\\Orders' => __DIR__ . '/../..' . '/includes/class-orders.php',
        'WP_Express_Checkout\\PayPal\\Client' => __DIR__ . '/../..' . '/includes/paypal-client/class-client.php',
        'WP_Express_Checkout\\PayPal\\Request' => __DIR__ . '/../..' . '/includes/paypal-client/class-request.php',
        'WP_Express_Checkout\\PayPal_Payment_Button_Ajax_Handler' => __DIR__ . '/../..' . '/includes/class-paypal-button-ajax-handler.php',
        'WP_Express_Checkout\\Payment_Processor' => __DIR__ . '/../..' . '/includes/class-payment-processor.php',
        'WP_Express_Checkout\\Payment_Processor_Free' => __DIR__ . '/../..' . '/includes/class-payment-processor-free.php',
        'WP_Express_Checkout\\Post_Type_Content_Handler' => __DIR__ . '/../..' . '/includes/class-post-type-content-handler.php',
        'WP_Express_Checkout\\Products' => __DIR__ . '/../..' . '/includes/class-products.php',
        'WP_Express_Checkout\\Products\\Donation_Product' => __DIR__ . '/../..' . '/includes/products/class-donation-product.php',
        'WP_Express_Checkout\\Products\\One_Time_Product' => __DIR__ . '/../..' . '/includes/products/class-one-time-product.php',
        'WP_Express_Checkout\\Products\\Product' => __DIR__ . '/../..' . '/includes/products/class-product.php',
        'WP_Express_Checkout\\Products\\Stub_Product' => __DIR__ . '/../..' . '/includes/products/class-stub-product.php',
        'WP_Express_Checkout\\Shortcodes' => __DIR__ . '/../..' . '/public/includes/class-shortcodes.php',
        'WP_Express_Checkout\\Tags' => __DIR__ . '/../..' . '/includes/class-tags.php',
        'WP_Express_Checkout\\Utils' => __DIR__ . '/../..' . '/includes/class-utils.php',
        'WP_Express_Checkout\\Utils_Downloads' => __DIR__ . '/../..' . '/includes/class-utils-downloads.php',
        'WP_Express_Checkout\\Variations' => __DIR__ . '/../..' . '/includes/class-variations.php',
        'WP_Express_Checkout\\View_Downloads' => __DIR__ . '/../..' . '/includes/class-view-downloads.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitecb54881939c5593641ad428bda777ea::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitecb54881939c5593641ad428bda777ea::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitecb54881939c5593641ad428bda777ea::$classMap;

        }, null, ClassLoader::class);
    }
}
