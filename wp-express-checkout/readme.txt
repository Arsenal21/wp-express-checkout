=== WP Express Checkout (Accept PayPal Payments) ===
Contributors: Tips and Tricks HQ, dikiy_forester, alexanderfoxc, mbrsolution, Ivy2120, chanelstone
Donate link: https://wp-express-checkout.com/
Tags: paypal, express checkout, payment, instant payment, digital downloads, e-commerce
Requires at least: 5.0
Tested up to: 5.7
Stable tag: 1.9.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use the PayPal Express Checkout API to accept payment quickly for a product or service in a payment popup window.

== Description ==

This plugin provides you a shortcode to generate a customizable PayPal payment button that allows a customer to pay for an item instantly via a popup using the PayPal's Express Checkout API/Gateway. The checkout process is quick and easy.

The full checkout takes place in a payment popup window and the customer never leaves your site.

This is ideal for selling a product or service via PayPal.

This plugin also works with PayPal's Pay in 4 feature. It lets your customers pay later in 4 installments via PayPal.
https://www.paypal.com/us/webapps/mpp/pay-in-4

View configuration and usage details on the [WP Express Checkout](https://wp-express-checkout.com/wp-express-checkout-plugin-documentation/) plugin's documentation page

= Checkout Demonstration =

https://www.youtube.com/watch?v=KF32ZOgsb2U

= Basic Setup and Usage Video =

https://www.youtube.com/watch?v=RHVgGQWhCT0

= Features =

* Sell products or services using a quick checkout process.
* Sell files, digital goods or downloads.
* Sell music, video, ebook, PDF or any other digital media files.
* Allow the customers to automatically download the file once the purchase is complete via PayPal.
* View the transactions from your WordPress admin dashboard.
* Option to configure a notification email to be sent to the buyer and seller after the purchase.
* Ability to set a product thumbnail for a product.
* Use a simple shortcode to add a payment button anywhere on your site.
* Create a PayPal payment button widget and add it to your sidebar.
* Ability for a customer to enter an amount and pay what they want for a product.
* Ability to configure variable products. You can charge different amount for different options of the product.
* Can be used to accept donation.
* Option to customize the currency formatting.
* Option to charge shipping for your items. Ability to set a shipping cost for each item separately.
* Option to charge tax for your items.
* Option to configure discount coupon codes.
* Option to configure terms and conditions before checkout.

The setup is very easy. Once you have installed the plugin, all you need to do is enter your PayPal Express Checkout API credentials in the plugin settings and your website will be ready to accept PayPal and credit card payments.

= Shortcode Attributes =

This plugin adds the following shortcode to your site:

[wp_express_checkout id="123"]

Or use the following shortcode to output product details and the express checkout payment button:

[wp_express_checkout id="123" template="1"]

You can also test it on PayPal Sandbox mode before going Live.

== Usage ==

View [this usage documentation](https://wp-express-checkout.com/basic-installation-and-setup-of-wp-express-checkout/) page for additional info.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'wp-express-checkout'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading via WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `wp-express-checkout.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==

= Can I have multiple payment buttons on a single page? =

Yes, you can have any number of buttons on a single page.

= Can I use it in a WordPress widget? =

Yes, you can.

= Can I customize the payment button? =

Yes, you can customize it in the settings menu of this plugin.

= Can I test it using PayPal sandbox? =

Yes, you can enable the sandbox mode from the settings menu of the plugin.


== Screenshots ==
See the following page for screenshots
https://wp-express-checkout.com/

== Upgrade Notice ==
None

== Changelog ==

= WIP since 1.9.9 to 2021-06-23 =
* Added column 'PayPal Transaction ID' to the Orders list. Added ability to search by PayPal Transaction ID. Added order meta field wpec_order_resource_id to use instead transaction_id item of wpec_order_data meta field. #26
* Added 'Thank You Page URL' Product option and shortcode attribute 'thank_you_url' to override global setting. Closes #25
* Updated readme and POT file
* Updated autoloader
* improved the help text with a link to documentation explaining what to do if the thank you page has been deleted accidentally
* Replace namespace WPEC with WP_Express_Checkout for better stability

= END WIP =

= 1.9.9 =
* Renamed the "Show in a Modal Window" field to "Show in a Popup/Modal Window" to make it easier to understand.
* Renamed the "Open Modal Button Text" field to "Popup/Modal Trigger Button Text" make it easier to understand.

= 1.9.8 =
* Added Product option "Open Modal Button Text".
* The open modal button text can be used to customize the button text that triggers the modal/popup window.
* Save empty Price as 0. Generate Price tag on the Products list in admin dashboard.
* updated the help text of the "Open Modal Button Text" field.

= 1.9.7 =
* Triggering wpec_paypal_sdk_loaded event not early than jQuery ready. Fixes issue when event triggered before callback registered and buttons don't appear.
* Added hooks 'wpec_settings_tabs' and wpec_settings_tab_{$action}" for using in addons.
* load PayPal script asynchronously with a trigger for loading buttons.
* Added parameter $mode to WPEC\PayPal\Client methods
* Do not show TOS error on page load
* Save and validate product price only for One Time payment type.
* Changed default value for Button Text optoion from "Buy Now" to "Pay"
* Process orders with 0 total.
* Added checkbox to the Products list
* New usage video updated in the readme file
* readme file updated with new checkout demonstration video

= 1.9.6 =
* The billing address and shipping address is shown (if available) on the order page or an order.
* Added Product option 'This is a Physical Product' to enable shipping address collection at the time of checkout
* Added product id reference to all top level elements in templates. Closes #23
* Fixed an issue with the global post variable when no template is used in the shortcode.
* Fixed an issue with variation radio options names.
* Added Grunt task to generate WIP log from the latest commits log
* Display product metaboxes above the Yoast SEO metabox
* updated readme to set wp compatible to wp5.7

= 1.9.5 =
* There is a new checkout form option to show the PayPal buy now button to be on a modal window. If you don't like this new option, you can turn off the "Show in a Modal Window" checkbox in the settings menu of the plugin.
* Added a new parameter modal="0" to the shortcode. It can be used to disable the use of modal/popup window.
* Use SVG spinner on Coupon redemption.
* Minor CSS changes for Buy Now button.
* Updated the POT file.

= 1.9.4 =
* Added a feature to enable Terms and Conditions. When enabled, customers have to accepts terms before they can make a payment.

= 1.9.3 =
* Fixed an issue with the variation feature.

= 1.9.2 =
* Added a new feature to allow configuration of variable products.
* Fixed a small bug with the "Allow customers to enter quantity" feature.

= 1.9.1 =
* Fixed an issue with the single post page not generating the buy now button on some installs.
* Added integration for WP eMember plugin:
https://www.tipsandtricks-hq.com/wordpress-membership/membership-payments-using-the-wp-express-checkout-plugin-1987

= 1.9 =
* Added discount coupon feature. Admins can configure discount coupon that can be used by your customers.
* The HTML email type will work with seller email also.

= 1.8 =
* Added a new feature to allow sending of HTML emails (for the buyer notification email). Thanks to Pierre.
* Added a product block inserter.

= 1.7 =
* Added feature to handle shipping cost.
* Added feature to handle tax.
* Fixed a bug with currency (when non USD currency is used).

= 1.6 =
* Added "Button Type" style in the individual product configuration interface. So the button type style can be set on a per product basis.
* Added an action hook for the download request processing. Can be used to override the download request processing via an addon plugin.

= 1.5 =
* Added a new feature that allows the admin to configure products where the customer can specify the amount. Useful for accepting donations.
* Added settings link in the plugin's menu listing page.
* Added new product download handing via a unique URL.
* The product thumbnail is now shown in the products listing in the admin menu.

= 1.4 =
* Added new settings to allow customization of currency formatting.
* Added a new settings option to specify the currency symbol.
* There is a "Product Thumbnail" field that you can use to specify a thumbnail image of a product.

= 1.3 =
* Added a new template to display the product with item details and a PayPal express checkout payment button.

= 1.2 =
* Removed the requirement to have to specify a download URL for products.
* Plugin's language text domain updated.

= 1.1 =
* Minor improvements to the settings menu.

= 1.0 =
* First Release