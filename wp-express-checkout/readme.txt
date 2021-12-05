=== WP Express Checkout (Accept PayPal Payments) ===
Contributors: Tips and Tricks HQ, dikiy_forester, alexanderfoxc, mbrsolution, Ivy2120, chanelstone
Donate link: https://wp-express-checkout.com/
Tags: paypal, payment, express checkout, instant payment, digital downloads, e-commerce
Requires at least: 5.6
Tested up to: 5.8
Stable tag: 2.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use the PayPal Express Checkout API to accept payment quickly for a product or service in a payment popup window.

== Description ==

This plugin provides you a shortcode to generate a customizable PayPal payment button that allows a customer to pay for an item instantly via a popup using the PayPal's Express Checkout API/Gateway. The checkout process is quick and easy.

The full checkout takes place in a payment popup window and the customer never leaves your site.

This is ideal for selling a product or service via PayPal.

This plugin also works with PayPal's Pay in 4 feature (Buy Now, Pay Later). It lets your customers pay later in 4 installments via PayPal.

View configuration and usage details on the [WP Express Checkout](https://wp-express-checkout.com/wp-express-checkout-plugin-documentation/) plugin's documentation page

= Checkout Demonstration =

https://www.youtube.com/watch?v=KF32ZOgsb2U

= Basic Setup and Usage Video =

https://www.youtube.com/watch?v=RHVgGQWhCT0

= Features =

* Sell products or services using a quick and easy checkout process.
* Accept PayPal donations with minimum donation amount limit.
* Sell downloads, files, or any digital goods.
* Sell music, video, ebook, PDF or any other digital media files.
* Allow the customers to automatically download the file once the purchase is completed via PayPal.
* You can deliver the digital downloads using encrypted download links that expire automatically.
* Offer Buy Now Pay Later payment option to your customers.
* View the transactions from your WordPress admin dashboard.
* Option to configure a notification email to be sent to the buyer and the seller after the purchase.
* Ability to set a product thumbnail for a product.
* Use a simple shortcode to add a payment button anywhere on your site.
* Create a PayPal payment button widget and add it to your site's sidebar.
* Ability for a customer to enter an amount and pay what they want for a product.
* Ability to configure variable products. You can charge different amount for different options of the product.
* Can be used to accept donations.
* Option to configure a minimum donation amount so the customers have to pay a minimum amount for donation.
* Option to customize the currency formatting.
* Option to charge shipping for your items. Ability to set a shipping cost for each item separately.
* Option to charge tax for your items.
* Option to configure discount coupon codes.
* Option to configure terms and conditions before checkout.
* You can see all the orders within your WordPress admin dashboard.
* Option to customize the Thank You page.
* Ability to configure the download links to expire after X number of hours.
* Ability to configure the download links to expire after X number of clicks.
* Can be integrated with WooCommerce to offer product checkout via PayPal's new express checkout system.

The setup is very easy. Once you have installed the plugin, all you need to do is enter your PayPal Express Checkout API credentials in the plugin settings and your website will be ready to accept PayPal and credit card payments.

You can also accept payment using [PayPal's Pay in 4](https://www.paypal.com/us/webapps/mpp/pay-in-4) feature (buy now pay later offering). Read the [Buy Now, Pay Later Tutorial](https://wp-express-checkout.com/paypal-pay-in-4-wp-express-checkout-plugin/) to learn more.

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

= 2.1.6 =
* Added Venmo payments support. Your customers can pay using the Venmo option from the PayPal checkout popup window.
* Added a new advanced settings option to allow other WP user roles to be able to access the admin dashboard of the plugin.
* Fixed a decimal precision error.
* Added Place Order button instead of the PayPal button for orders with total $0 (when 100% discount coupon is applied).
* Added Billing and Shipping fields on the purchase form for orders with total $0 (when 100% discount coupon is applied).
* Update the language POT file. Fixes #54

= 2.1.5 =
- New feature for download link expiry option.
- Increased the transient expiry time to improve the Error Code 3004.
- Added Spanish language translation file.
- Fixed a typo in the file download field's description text.
- Improved the products and orders list table display for mobile device screens.
- The Order search feature has been revamped. The revamped search feature will work on new orders going forward.

= 2.1.4 =
* Added gateway option 'Checkout Popup Title' for the WooCommerce integration.
* Load payment popup form on the WooCommerce checkout page.
* Fixed paypal actions on input change.
* Added filter 'wpec_render_custom_order_tag' that allows to render custom fields via Order Tags renderer.
* Custom Fields Addon integration related:
- Added action hook 'wpec_payment_form_before_variations' on the product purchase form before variations.
- Incapsulated fields validation and errors display. Fields now validated independently and addons can add own fields to the form validation process.

= 2.1.3 =
* Added a feature to "Resend sale notification email" action. You can find it in the "Order Actions" section of the Order in question.
* Added class Emails to handle all plugin email notifications. Moved all emails related code to new Emails class.
* Only allow admin users to be able to create and edit Products.
* The debug log file name is auto generated to a unique name.
* Small text change in the woocommerce integration settings.
* Changed Filters 'wpec_buyer_notification_email_body' and 'wpec_seller_notification_email_body'. Replaced parameter $payment with $order.

= 2.1.2 =
* Added a section in the Orders menu to show the download link for the item that was purchased.
* Added Tools menu that will allow the site admin to send an email to a customer. Useful for re-sending download links to a customer.
* Woocommerce checkout integration. We will add usage documentation for it soon.

= 2.1.1 =
* Added new product type called "Donation".
* The donation type product can be used to specify a minimum donation amount. This forces the customers to enter a minimum donation amount.
* The documentation for the new Thank You page customization shortcode is now available. [View the documentation here](https://wp-express-checkout.com/thank-you-page-customization/).
* Fixed and issue with the 'Allow customers to specify quantity' option.

= 2.1.0 =
* Added some missing translations strings.
* Fixed WP 5.0 backward incompatibility issue.
* Added `Order_Tags_Html` class to generate order tags with HTML output. Later it will used as the parent class for email tags generator with plain text output.
* Added Thank You parts shortcodes that will allow the customization of the Thank you page.
* Allow the use of $content parameter in wpec_thank_you shortcode callback.
* Changed to using "require_once" instead of "require" for the inclusion of the "swpm_handle_subsc_ipn.php" file to prevent fatal error.
* Integrated License Manager plugin. 100% Covered by unit tests.
* Refactor integrations: - Created Integrations namespace - Moved integrations code to separated folders - 100% cover integrations by unit tests
* Improved Payment processors.

= 2.0.1 =
* Added filter 'wpec_product_template' - to allow plugins override templates.
* Moved arguments parsing logic from Shortcodes::generate_pp_express_checkout_button() to Shortcodes::shortcode_wp_express_checkout()
* Added Integration with Simple Membership Plugin. We will create a usage documentation for it in the upcoming days.
* Added fallback to a Product Price metabox for disabled addons.
* Use original Variations array structure in payment-form.php
* Handle Order create/retrieve errors using exceptions.
* Fixed WP eMember integration.
* Move back-end methods from Products class to appropriate classes in admin section

= 2.0.0 =
* Save payer's PayPal email address in the order meta field. Ensures order search by customer email.
* Added a new column 'PayPal Transaction ID' to the Orders list.
* Added the ability to search an order using the PayPal Transaction ID.
* Added order meta field wpec_order_resource_id to use instead of transaction_id item of wpec_order_data meta field.
* Added 'Thank You Page URL' on a per-product basis.
* Added shortcode attribute 'thank_you_url' to override global setting via the shortcode.
* Improved the help text with a link to the documentation page explaining what to do if the thank you page has been deleted accidentally
* Replaced namespace WPEC with WP_Express_Checkout for better stability
* Refactored files according to WP standards and new classes names
* Use namespace WP_Express_Checkout for all classes.
* Updated the POT files

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