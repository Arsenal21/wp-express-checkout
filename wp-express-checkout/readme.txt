=== WP Express Checkout ===
Contributors: Tips and Tricks HQ, dikiy_forester
Donate link: https://wp-express-checkout.com/
Tags: paypal, express checkout, payment, instant payment, digital downloads, e-commerce
Requires at least: 5.0
Tested up to: 5.4
Stable tag: 1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use the PayPal Express Checkout API to accept payment quickly for a product or service in a payment popup window.

== Description ==

This plugin provides you a shortcode to generate a customizable PayPal payment button that allows a customer to pay for an item instantly via a popup using the PayPal's Express Checkout API/Gateway. The checkout process is quick and easy.

The full checkout takes place in a payment popup window and the customer never leaves your site.

This is ideal for selling a product or service via PayPal.

View configuration and usage details on the [WP Express Checkout](https://wp-express-checkout.com/wp-express-checkout-plugin-documentation/) plugin's documentation page

= Checkout Demonstration =

https://www.youtube.com/watch?v=jdeZcs-90DY

= Basic Setup and Usage Video =

https://www.youtube.com/watch?v=3LsfLd03VnE

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
* Can be used to accept donation.
* Option to customize the currency formatting.
* Option to charge shipping for your items. Ability to set a shipping cost for each item separately.
* Option to charge tax for your items.

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