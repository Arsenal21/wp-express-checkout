=== PayPal for Digital Goods ===
Contributors: Tips and Tricks HQ
Donate link: https://www.tipsandtricks-hq.com/paypal-for-digital-goods-wordpress-plugin
Tags: paypal, payment, express checkout, digital goods, payment gateway, instant payment, digital downloads, e-commerce
Requires at least: 3.5
Tested up to: 5.2
Stable tag: 1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use the PayPal Express Checkout API to accept payment for a digital item in a popup.

== Description ==

This plugin provides you a shortcode to generate a customizable PayPal payment button that allows a user to pay for an item instantly in a popup using the PayPal's Express Checkout API/Gateway.

The full checkout takes place in an overlay/popup window and the customer never leaves your site.

This is ideal for selling file downloads via PayPal.

View configuration and usage details on the [paypal for digital goods plugin](https://www.tipsandtricks-hq.com/paypal-for-digital-goods-wordpress-plugin) page

= Checkout Process Demo =

https://www.youtube.com/watch?v=BgzDx0kuNR8

= Basic Setup and Usage Video =

https://www.youtube.com/watch?v=nIv8D-qTY4g

= This plugin will be ideal for you if you are selling the following =

* eBooks (PDF, epub, mobi etc.)
* Audio files (mp3, wav, ogg etc.)
* Video files (mp4, mov, wmv etc.)
* Image files (jpg, jpeg, png, gif etc.)
* Excel documents
* PDF documents
* MS Word documents

= Features =

* Sell files, digital goods or downloads using PayPal for digital goods gateway.
* Sell music, video, ebook, PDF or any other digital media files.
* Allow a user to automatically download the file once the purchase is complete via paypal.
* View the transactions from your WordPress admin dashboard.

= Shortcode Attributes =

This plugin adds the following shortcode to your site:

[paypal_for_digital_goods]

It supports the following attributes in the shortcode -

    name:
    (string) (required) Name of the product
    Possible Values: 'Awesome Script', 'My Ebook', 'Wooden Table' etc.


    price:
    (number) (required) Price of the product or item
    Possible Values: '9.90', '29.95', '50' etc.

    quantity:
    (number) (optional) Number of products to be charged.
    Possible Values: '1', '5' etc.
    Default: 1

    currency:
    (string) (optional) Currency of the price specified.
    Possible Values: 'USD', 'GBP' etc
    Default: The one set up in Settings area.
    
    url:
    (URL) (optional) URL of the downloadable file.
    Possible Values: http://example.com/my-downloads/product.zip


Please visit Settings -> PayPal for Digital Goods admin area to configure default options. 

You can also test it on PayPal Sandbox before going Live.

== Usage ==

[paypal_for_digital_goods name="Cool Script" price="50" url="http://example.com/downloads/my-script.zip"]

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'paypal-for-digital-goods'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading via WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `paypal-for-digital-goods.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `paypal-for-digital-goods.zip`
2. Extract the `paypal-for-digital-goods` directory to your computer
3. Upload the `paypal-for-digital-goods` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==

= Can I have multiple payment buttons on a single page? =

Yes, you can have any number of buttons on a single page.

= Can I use it in a WordPress Widgets? =

Yes, you can.

= Can I specify quantity of the item? =

Yes, please use "quantity" attribute.

= Can I change the button? =

Yes, you can customize it in the settings menu of this plugin.

= Can I test it on PayPal sandbox? =

Yes, please visit Settings > PayPal for Digital Goods screen for options.


== Screenshots ==
See the following page for screenshots
https://www.tipsandtricks-hq.com/paypal-for-digital-goods-wordpress-plugin

== Upgrade Notice ==
None

== Changelog ==

= 1.6 =
* Updated the plugin's code to use the new PayPal's express checkout API.
* Added a new filter to allow customization of the Thank You message.

= 1.4 and 1.5 =
* Re-worked the price hashing code to remove any chance of any kind of price field manipulation.

= 1.3 =
* Updated the return and cancel URL construction to use the "add_query_arg()" function for better compatibility.

= 1.2 =
* Added extra validation in the plugin.
* Also tested on WordPress 4.3.

= 1.1 =
* Added session start call when the plugin initializes.

= 1.0 =
* First Release