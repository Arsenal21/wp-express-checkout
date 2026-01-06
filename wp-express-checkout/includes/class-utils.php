<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;
class Utils {

	public static function get_user_ip_address() {
		//This is a good WP recommended way to get the IP address.
		$user_ip = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$user_ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$user_ip = $_SERVER['REMOTE_ADDR'];
		}

		if (strstr($user_ip, ',')) {
			// Return first IP if X-Forwarded-For contains multiple IPs.
			$ip_values = explode(',', $user_ip);
			$user_ip = $ip_values['0'];
		}

		return apply_filters('wpec_get_user_ip_address', $user_ip);
	}

	/**
	 * Returns the price given the arguments in settings
	 *
	 * @param  int    $price             The numerical value to format.
	 * @param  string $override_currency The currency the value is in.
	 * @param  string $override_position The currency the position is in.
	 * @return string                    The formatted price.
	 */
	public static function price_format( $price, $override_currency = '', $override_position = '' ) {
		$ppdg = Main::get_instance();

		$decimals        = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
		$formatted_price = number_format( $price, $decimals, $ppdg->get_setting( 'price_decimal_sep' ), $ppdg->get_setting( 'price_thousand_sep' ) );
		$position        = ( empty( $override_position ) ) ? $ppdg->get_setting( 'price_currency_pos' ) : $override_position;

		if ( ! empty( $override_currency ) && $override_currency !== $ppdg->get_setting( 'currency_code' ) ) {
			$currency_code = $override_currency;
		} elseif ( ! empty( $ppdg->get_setting( 'currency_symbol' ) ) ) {
			$currency_code = $ppdg->get_setting( 'currency_symbol' );
		} else {
			$currency_code = $ppdg->get_setting( 'currency_code' );
		}

		if ( empty( $position ) ) {
			$position = 'right_space';
		}

		$formats = array(
			'left'        => '{symbol}{price}',
			'left_space'  => '{symbol} {price}',
			'right'       => '{price}{symbol}',
			'right_space' => '{price} {symbol}',
		);

		$search  = array( '{price}', '{symbol}' );
		$replace = array( $formatted_price, $currency_code );

		return str_replace( $search, $replace, $formats[ $position ] );
	}

	/**
	 * Generates price modifier string.
	 *
	 * @param string $price_mod
	 * @param string $currency
	 * @return string
	 */
	public static function price_modifier( $price_mod, $currency ) {
		if ( ! empty( $price_mod ) ) {
			$fmt_price = self::price_format( abs( $price_mod ), $currency );
			$price_mod = '(' . ( $price_mod < 0 ? ' - ' . $fmt_price : ' + ' . $fmt_price ) . ')';
		} else {
			$price_mod = '';
		}
		return $price_mod;
	}

	public static function get_tax_amount( $price, $tax ) {
		$ppdg = Main::get_instance();

		if ( ! empty( $tax ) ) {
			$prec       = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
			$tax_amount = round( ( $price * $tax / 100 ), $prec );
			return $tax_amount;
		} else {
			return 0;
		}
	}

	/**
	 * Calculates the total shipping amount from provided product arguments.
	 * It calculates the total per quantity shipping cost (if specified) and 
	 * adds that to the base shipping cost. If the base shipping cose is not
	 * specified, it returns zero.
	 * 
	 * @param array $product_args The product shipping costs data.
	 * @return float Total shipping cost
	 */
	public static function get_total_shipping_cost($product_args){
		$total = 0;
		$quantity = 1;
		$base_shipping = 0;
		$shipping_per_quantity = 0;
	
		if ( isset($product_args['shipping_per_quantity']) && ! empty( $product_args['shipping_per_quantity'] )) {
			$shipping_per_quantity = floatval( esc_attr($product_args['shipping_per_quantity']));

			// To calculate total shipping per quantity cost.
			if ( isset($product_args['quantity']) && ! empty( $product_args['quantity'] )) {
				$quantity = intval($product_args['quantity']);
			}
		}
		
		// Check if product specific shipping cost is specified or not.
		if (isset($product_args['shipping']) && !empty( $product_args['shipping'] )){
			// Get the base shipping cost specified for a particular product.
			$base_shipping = floatval(esc_attr( $product_args['shipping'] ));
		} else {
			// Use global base shipping settings only if the base shipping is explicitly set to '' (empty) in the product add/edit page.
			$global_settings = get_option( 'ppdg-settings');
			if ( isset( $global_settings->shipping ) && !empty( isset( $global_settings->shipping ) ) ) {
				$base_shipping = floatval(esc_attr($global_settings->shipping));
			}
		}
		
		$total = $base_shipping + ($shipping_per_quantity * $quantity);
		
		return $total;
	}

	public static function round_price( $price ) {
		$ppdg = Main::get_instance();

		$prec  = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
		$price = round( $price, $prec );

		return $price;
	}

	public static function apply_tax( $price, $tax ) {
		$ppdg = Main::get_instance();

		if ( ! empty( $tax ) ) {
			$prec       = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
			$tax_amount = round( ( $price * $tax / 100 ), $prec );
			$price     += $tax_amount;
		}
		return $price;
	}

	public static function apply_shipping( $price, $shipping ) {
		$ppdg = Main::get_instance();

		if ( ! empty( $shipping ) ) {
			$prec   = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
			$price += floatval( $shipping );
			$price  = round( $price, $prec );
		}
		return $price;
	}

	/**
	 * Replaces the dynamic tags with order details (given the order_id).
	 *
	 * @param string $text  The text to be processed.
	 * @param int    $order The order object to retrieve default values.
	 * @param array  $args  The custom arguments to replace default order data.
	 *
	 * @return string
	 */
	public static function replace_dynamic_order_tags( $text, $order, $args = array() ) {

		$tags   = array_keys( self::get_dynamic_tags_white_list() );
		$render = new Order_Tags_Plain( $order );

		foreach ( $tags as $tag ) {
			$tags_vals[ $tag ] = $render->$tag();
		}

		$tags_vals = array_merge( $tags_vals, $args );

		$text = self::apply_dynamic_tags( $text, $tags_vals );
		return $text;
	}

	/**
	 * Replaces tags in the text with appropriate values.
	 *
	 * @since 2.0
	 *
	 * @param string $text The text with tags to be replaced.
	 * @param array  $args The array of the tags values.
	 *
	 * @return string
	 */
	public static function apply_dynamic_tags( $text, $args ) {

		$tags = array();
		$vals = array();

		foreach ( $args as $item => $value ) {
			$tags[] = "{{$item}}";
			$vals[] = $value;
		}

		$body = stripslashes( str_replace( $tags, $vals, $text ) );

		return $body;
	}

	/**
	 * Retrieves the list of supported dynamic tags and their descriptions
	 *
	 * @return array
	 */
	public static function get_dynamic_tags_white_list() {
		$tags = apply_filters( 'wpec_dynamic_tags_white_list', array(
			'first_name' => __( 'First name of the buyer', 'wp-express-checkout' ),
			'last_name' => __( 'Last name of the buyer', 'wp-express-checkout' ),
			'payer_email' => __( 'Email Address of the buyer', 'wp-express-checkout' ),
			'address' => __( 'Address of the buyer', 'wp-express-checkout' ),
			'product_details' => __( 'The item details of the purchased product (this will include the download link for digital items).', 'wp-express-checkout' ),
			'transaction_id' => __( 'The unique transaction ID of the purchase', 'wp-express-checkout' ),
			'order_id' => __( 'The order ID reference of this transaction in the cart orders menu', 'wp-express-checkout' ),
			'purchase_amt' => __( 'The amount paid for the current transaction', 'wp-express-checkout' ),
			'purchase_date' => __( 'The date of the purchase', 'wp-express-checkout' ),
			'coupon_code' => __( 'Coupon code applied to the purchase', 'wp-express-checkout' ),
			'currency_code' => __( 'Order currency code', 'wp-express-checkout' ),			
			'product_id' => __( 'Product ID', 'wp-express-checkout' ),
			'product_name' => __( 'Product Name', 'wp-express-checkout' ),
			'quantity_ordered' => __( 'Quantity ordered', 'wp-express-checkout' ),
			'download_link' => __( 'Download link of the product (if any)', 'wp-express-checkout' ),
			'download_link_url_only' => __( 'Download link that contains the URL only, omitting any associated product name (if any).', 'wp-express-checkout' ),
			'selected_variations' => __( 'Selected variations of the order', 'wp-express-checkout' ),
		) );

		return $tags;
	}

	/**
	 * Retrieves the list of supported dynamic tags and their descriptions
	 *
	 * @return array
	 */
	public static function get_dynamic_tags_white_list_for_manual_checkout() {
		$tags = apply_filters( 'wpec_dynamic_tags_white_list', array(
			'first_name' => __( 'First name of the buyer', 'wp-express-checkout' ),
			'last_name' => __( 'Last name of the buyer', 'wp-express-checkout' ),
			'payer_email' => __( 'Email address of the buyer', 'wp-express-checkout' ),
			'payer_phone' => __( 'Phone number of the buyer', 'wp-express-checkout' ),
			'address' => __( 'Address of the buyer', 'wp-express-checkout' ),
			'transaction_id' => __( 'The unique transaction ID of the purchase', 'wp-express-checkout' ),
			'order_id' => __( 'The order ID reference of this transaction in the cart orders menu', 'wp-express-checkout' ),
			'purchase_amt' => __( 'The amount paid for the current transaction', 'wp-express-checkout' ),
			'purchase_date' => __( 'The date of the purchase', 'wp-express-checkout' ),
			'coupon_code' => __( 'Coupon code applied to the purchase', 'wp-express-checkout' ),
			'currency_code' => __( 'Order currency code', 'wp-express-checkout' ),
			'product_id' => __( 'Product ID', 'wp-express-checkout' ),
			'product_name' => __( 'Product name', 'wp-express-checkout' ),
			'quantity_ordered' => __( 'Quantity ordered', 'wp-express-checkout' ),
			'selected_variations' => __( 'Selected variations of the order', 'wp-express-checkout' ),
		) );

		return $tags;
	}

	public static function get_tags_desc($tags) {
		$tags_desc = '';

		foreach ( $tags as $tag => $desc ) {
			$tags_desc .= "<br /><code>{{$tag}}</code> - {$desc}";
		}

		return $tags_desc;
	}

	/*
	 * Use this function to redirect to a URL
	 */
	public static function redirect_to_url( $url, $delay = '0', $exit = '1' ) {
		$url = apply_filters( 'wpec_before_redirect_to_url', $url );
		if ( empty( $url ) ) {
			echo '<strong>';
			_e( 'Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'wp-express-checkout' );
			echo '</strong>';
			exit;
		}
		if ( ! headers_sent() ) {
			header( 'Location: ' . $url );
		} else {
			echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
		}

		if ( $exit == '1' ) {//exit
			exit;
		}
	}
	
	// There is no need in untranslated countries list currently, so let's use
	// translated.
	public static function get_countries_untranslated() {
		return self::get_countries();
	}

	public static function get_countries() {
		$countries = array(
			''   => '—',
			'AF' => __( 'Afghanistan', 'wp-express-checkout' ),
			'AX' => __( 'Aland Islands', 'wp-express-checkout' ),
			'AL' => __( 'Albania', 'wp-express-checkout' ),
			'DZ' => __( 'Algeria', 'wp-express-checkout' ),
			'AS' => __( 'American Samoa', 'wp-express-checkout' ),
			'AD' => __( 'Andorra', 'wp-express-checkout' ),
			'AO' => __( 'Angola', 'wp-express-checkout' ),
			'AI' => __( 'Anguilla', 'wp-express-checkout' ),
			'AQ' => __( 'Antarctica', 'wp-express-checkout' ),
			'AG' => __( 'Antigua and Barbuda', 'wp-express-checkout' ),
			'AR' => __( 'Argentina', 'wp-express-checkout' ),
			'AM' => __( 'Armenia', 'wp-express-checkout' ),
			'AW' => __( 'Aruba', 'wp-express-checkout' ),
			'AU' => __( 'Australia', 'wp-express-checkout' ),
			'AT' => __( 'Austria', 'wp-express-checkout' ),
			'AZ' => __( 'Azerbaijan', 'wp-express-checkout' ),
			'BS' => __( 'Bahamas', 'wp-express-checkout' ),
			'BH' => __( 'Bahrain', 'wp-express-checkout' ),
			'BD' => __( 'Bangladesh', 'wp-express-checkout' ),
			'BB' => __( 'Barbados', 'wp-express-checkout' ),
			'BY' => __( 'Belarus', 'wp-express-checkout' ),
			'BE' => __( 'Belgium', 'wp-express-checkout' ),
			'BZ' => __( 'Belize', 'wp-express-checkout' ),
			'BJ' => __( 'Benin', 'wp-express-checkout' ),
			'BM' => __( 'Bermuda', 'wp-express-checkout' ),
			'BT' => __( 'Bhutan', 'wp-express-checkout' ),
			'BO' => __( 'Bolivia', 'wp-express-checkout' ),
			'BQ' => __( 'Bonaire', 'wp-express-checkout' ),
			'BA' => __( 'Bosnia and Herzegovina', 'wp-express-checkout' ),
			'BW' => __( 'Botswana', 'wp-express-checkout' ),
			'BV' => __( 'Bouvet Island', 'wp-express-checkout' ),
			'BR' => __( 'Brazil', 'wp-express-checkout' ),
			'IO' => __( 'British Indian Ocean Territory', 'wp-express-checkout' ),
			'VG' => __( 'British Virgin Islands', 'wp-express-checkout' ),
			'BN' => __( 'Brunei', 'wp-express-checkout' ),
			'BG' => __( 'Bulgaria', 'wp-express-checkout' ),
			'BF' => __( 'Burkina Faso', 'wp-express-checkout' ),
			'BI' => __( 'Burundi', 'wp-express-checkout' ),
			'KH' => __( 'Cambodia', 'wp-express-checkout' ),
			'CM' => __( 'Cameroon', 'wp-express-checkout' ),
			'CA' => __( 'Canada', 'wp-express-checkout' ),
			'CV' => __( 'Cape Verde', 'wp-express-checkout' ),
			'KY' => __( 'Cayman Islands', 'wp-express-checkout' ),
			'CF' => __( 'Central African Republic', 'wp-express-checkout' ),
			'TD' => __( 'Chad', 'wp-express-checkout' ),
			'CL' => __( 'Chile', 'wp-express-checkout' ),
			'CN' => __( 'China', 'wp-express-checkout' ),
			'CX' => __( 'Christmas Island', 'wp-express-checkout' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'wp-express-checkout' ),
			'CO' => __( 'Colombia', 'wp-express-checkout' ),
			'KM' => __( 'Comoros', 'wp-express-checkout' ),
			'CD' => __( 'Congo, Democratic Republic of', 'wp-express-checkout' ),
			'CG' => __( 'Congo, Republic of', 'wp-express-checkout' ),
			'CK' => __( 'Cook Islands', 'wp-express-checkout' ),
			'CR' => __( 'Costa Rica', 'wp-express-checkout' ),
			'HR' => __( 'Croatia', 'wp-express-checkout' ),
			'CU' => __( 'Cuba', 'wp-express-checkout' ),
			'CW' => __( 'Curacao', 'wp-express-checkout' ),
			'CY' => __( 'Cyprus', 'wp-express-checkout' ),
			'CZ' => __( 'Czech Republic', 'wp-express-checkout' ),
			'DK' => __( 'Denmark', 'wp-express-checkout' ),
			'DJ' => __( 'Djibouti', 'wp-express-checkout' ),
			'DM' => __( 'Dominica', 'wp-express-checkout' ),
			'DO' => __( 'Dominican Republic', 'wp-express-checkout' ),
			'EC' => __( 'Ecuador', 'wp-express-checkout' ),
			'EG' => __( 'Egypt', 'wp-express-checkout' ),
			'SV' => __( 'El Salvador', 'wp-express-checkout' ),
			'GQ' => __( 'Equatorial Guinea', 'wp-express-checkout' ),
			'ER' => __( 'Eritrea', 'wp-express-checkout' ),
			'EE' => __( 'Estonia', 'wp-express-checkout' ),
			'ET' => __( 'Ethiopia', 'wp-express-checkout' ),
			'FK' => __( 'Falkland Islands', 'wp-express-checkout' ),
			'FO' => __( 'Faroe Islands', 'wp-express-checkout' ),
			'FJ' => __( 'Fiji', 'wp-express-checkout' ),
			'FI' => __( 'Finland', 'wp-express-checkout' ),
			'FR' => __( 'France', 'wp-express-checkout' ),
			'GF' => __( 'French Guiana', 'wp-express-checkout' ),
			'PF' => __( 'French Polynesia', 'wp-express-checkout' ),
			'TF' => __( 'French Southern and Antarctic Lands', 'wp-express-checkout' ),
			'GA' => __( 'Gabon', 'wp-express-checkout' ),
			'GM' => __( 'Gambia', 'wp-express-checkout' ),
			'GE' => __( 'Georgia', 'wp-express-checkout' ),
			'DE' => __( 'Germany', 'wp-express-checkout' ),
			'GH' => __( 'Ghana', 'wp-express-checkout' ),
			'GI' => __( 'Gibraltar', 'wp-express-checkout' ),
			'GR' => __( 'Greece', 'wp-express-checkout' ),
			'GL' => __( 'Greenland', 'wp-express-checkout' ),
			'GD' => __( 'Grenada', 'wp-express-checkout' ),
			'GP' => __( 'Guadeloupe', 'wp-express-checkout' ),
			'GU' => __( 'Guam', 'wp-express-checkout' ),
			'GT' => __( 'Guatemala', 'wp-express-checkout' ),
			'GG' => __( 'Guernsey', 'wp-express-checkout' ),
			'GN' => __( 'Guinea', 'wp-express-checkout' ),
			'GW' => __( 'Guinea-Bissau', 'wp-express-checkout' ),
			'GY' => __( 'Guyana', 'wp-express-checkout' ),
			'HT' => __( 'Haiti', 'wp-express-checkout' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'wp-express-checkout' ),
			'HN' => __( 'Honduras', 'wp-express-checkout' ),
			'HK' => __( 'Hong Kong', 'wp-express-checkout' ),
			'HU' => __( 'Hungary', 'wp-express-checkout' ),
			'IS' => __( 'Iceland', 'wp-express-checkout' ),
			'IN' => __( 'India', 'wp-express-checkout' ),
			'ID' => __( 'Indonesia', 'wp-express-checkout' ),
			'IR' => __( 'Iran', 'wp-express-checkout' ),
			'IQ' => __( 'Iraq', 'wp-express-checkout' ),
			'IE' => __( 'Ireland', 'wp-express-checkout' ),
			'IM' => __( 'Isle of Man', 'wp-express-checkout' ),
			'IL' => __( 'Israel', 'wp-express-checkout' ),
			'IT' => __( 'Italy', 'wp-express-checkout' ),
			'CI' => __( 'Ivory Coast', 'wp-express-checkout' ),
			'JM' => __( 'Jamaica', 'wp-express-checkout' ),
			'JP' => __( 'Japan', 'wp-express-checkout' ),
			'JE' => __( 'Jersey', 'wp-express-checkout' ),
			'JO' => __( 'Jordan', 'wp-express-checkout' ),
			'KZ' => __( 'Kazakhstan', 'wp-express-checkout' ),
			'KE' => __( 'Kenya', 'wp-express-checkout' ),
			'KI' => __( 'Kiribati', 'wp-express-checkout' ),
			'KP' => __( "Korea, Democratic People's Republic of", 'wp-express-checkout' ),
			'KR' => __( 'Korea, Republic of', 'wp-express-checkout' ),
			'XK' => __( 'Kosovo', 'wp-express-checkout' ),
			'KW' => __( 'Kuwait', 'wp-express-checkout' ),
			'KG' => __( 'Kyrgyzstan', 'wp-express-checkout' ),
			'LA' => __( 'Laos', 'wp-express-checkout' ),
			'LV' => __( 'Latvia', 'wp-express-checkout' ),
			'LB' => __( 'Lebanon', 'wp-express-checkout' ),
			'LS' => __( 'Lesotho', 'wp-express-checkout' ),
			'LR' => __( 'Liberia', 'wp-express-checkout' ),
			'LY' => __( 'Libya', 'wp-express-checkout' ),
			'LI' => __( 'Liechtenstein', 'wp-express-checkout' ),
			'LT' => __( 'Lithuania', 'wp-express-checkout' ),
			'LU' => __( 'Luxembourg', 'wp-express-checkout' ),
			'MO' => __( 'Macau', 'wp-express-checkout' ),
			'MK' => __( 'Macedonia', 'wp-express-checkout' ),
			'MG' => __( 'Madagascar', 'wp-express-checkout' ),
			'MW' => __( 'Malawi', 'wp-express-checkout' ),
			'MY' => __( 'Malaysia', 'wp-express-checkout' ),
			'MV' => __( 'Maldives', 'wp-express-checkout' ),
			'ML' => __( 'Mali', 'wp-express-checkout' ),
			'MT' => __( 'Malta', 'wp-express-checkout' ),
			'MH' => __( 'Marshall Islands', 'wp-express-checkout' ),
			'MQ' => __( 'Martinique', 'wp-express-checkout' ),
			'MR' => __( 'Mauritania', 'wp-express-checkout' ),
			'MU' => __( 'Mauritius', 'wp-express-checkout' ),
			'YT' => __( 'Mayotte', 'wp-express-checkout' ),
			'MX' => __( 'Mexico', 'wp-express-checkout' ),
			'FM' => __( 'Micronesia', 'wp-express-checkout' ),
			'MD' => __( 'Moldova', 'wp-express-checkout' ),
			'MC' => __( 'Monaco', 'wp-express-checkout' ),
			'MN' => __( 'Mongolia', 'wp-express-checkout' ),
			'ME' => __( 'Montenegro', 'wp-express-checkout' ),
			'MS' => __( 'Montserrat', 'wp-express-checkout' ),
			'MA' => __( 'Morocco', 'wp-express-checkout' ),
			'MZ' => __( 'Mozambique', 'wp-express-checkout' ),
			'MM' => __( 'Myanmar', 'wp-express-checkout' ),
			'NA' => __( 'Namibia', 'wp-express-checkout' ),
			'NR' => __( 'Nauru', 'wp-express-checkout' ),
			'NP' => __( 'Nepal', 'wp-express-checkout' ),
			'NL' => __( 'Netherlands', 'wp-express-checkout' ),
			'NC' => __( 'New Caledonia', 'wp-express-checkout' ),
			'NZ' => __( 'New Zealand', 'wp-express-checkout' ),
			'NI' => __( 'Nicaragua', 'wp-express-checkout' ),
			'NE' => __( 'Niger', 'wp-express-checkout' ),
			'NG' => __( 'Nigeria', 'wp-express-checkout' ),
			'NU' => __( 'Niue', 'wp-express-checkout' ),
			'NF' => __( 'Norfolk Island', 'wp-express-checkout' ),
			'MP' => __( 'Northern Mariana Islands', 'wp-express-checkout' ),
			'NO' => __( 'Norway', 'wp-express-checkout' ),
			'OM' => __( 'Oman', 'wp-express-checkout' ),
			'PK' => __( 'Pakistan', 'wp-express-checkout' ),
			'PW' => __( 'Palau', 'wp-express-checkout' ),
			'PS' => __( 'Palestine', 'wp-express-checkout' ),
			'PA' => __( 'Panama', 'wp-express-checkout' ),
			'PG' => __( 'Papua New Guinea', 'wp-express-checkout' ),
			'PY' => __( 'Paraguay', 'wp-express-checkout' ),
			'PE' => __( 'Peru', 'wp-express-checkout' ),
			'PH' => __( 'Philippines', 'wp-express-checkout' ),
			'PN' => __( 'Pitcairn Islands', 'wp-express-checkout' ),
			'PL' => __( 'Poland', 'wp-express-checkout' ),
			'PT' => __( 'Portugal', 'wp-express-checkout' ),
			'PR' => __( 'Puerto Rico', 'wp-express-checkout' ),
			'QA' => __( 'Qatar', 'wp-express-checkout' ),
			'RE' => __( 'Reunion', 'wp-express-checkout' ),
			'RO' => __( 'Romania', 'wp-express-checkout' ),
			'RU' => __( 'Russia', 'wp-express-checkout' ),
			'RW' => __( 'Rwanda', 'wp-express-checkout' ),
			'BL' => __( 'Saint Barthelemy', 'wp-express-checkout' ),
			'SH' => __( 'Saint Helena, Ascension, and Tristan da Cunha', 'wp-express-checkout' ),
			'KN' => __( 'Saint Kitts and Nevis', 'wp-express-checkout' ),
			'LC' => __( 'Saint Lucia', 'wp-express-checkout' ),
			'MF' => __( 'Saint Martin', 'wp-express-checkout' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'wp-express-checkout' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'wp-express-checkout' ),
			'WS' => __( 'Samoa', 'wp-express-checkout' ),
			'SM' => __( 'San Marino', 'wp-express-checkout' ),
			'ST' => __( 'Sao Tome and Principe', 'wp-express-checkout' ),
			'SA' => __( 'Saudi Arabia', 'wp-express-checkout' ),
			'SN' => __( 'Senegal', 'wp-express-checkout' ),
			'RS' => __( 'Serbia', 'wp-express-checkout' ),
			'SC' => __( 'Seychelles', 'wp-express-checkout' ),
			'SL' => __( 'Sierra Leone', 'wp-express-checkout' ),
			'SG' => __( 'Singapore', 'wp-express-checkout' ),
			'SX' => __( 'Sint Maarten', 'wp-express-checkout' ),
			'SK' => __( 'Slovakia', 'wp-express-checkout' ),
			'SI' => __( 'Slovenia', 'wp-express-checkout' ),
			'SB' => __( 'Solomon Islands', 'wp-express-checkout' ),
			'SO' => __( 'Somalia', 'wp-express-checkout' ),
			'ZA' => __( 'South Africa', 'wp-express-checkout' ),
			'GS' => __( 'South Georgia', 'wp-express-checkout' ),
			'SS' => __( 'South Sudan', 'wp-express-checkout' ),
			'ES' => __( 'Spain', 'wp-express-checkout' ),
			'LK' => __( 'Sri Lanka', 'wp-express-checkout' ),
			'SD' => __( 'Sudan', 'wp-express-checkout' ),
			'SR' => __( 'Suriname', 'wp-express-checkout' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'wp-express-checkout' ),
			'SZ' => __( 'Swaziland', 'wp-express-checkout' ),
			'SE' => __( 'Sweden', 'wp-express-checkout' ),
			'CH' => __( 'Switzerland', 'wp-express-checkout' ),
			'SY' => __( 'Syria', 'wp-express-checkout' ),
			'TW' => __( 'Taiwan', 'wp-express-checkout' ),
			'TJ' => __( 'Tajikistan', 'wp-express-checkout' ),
			'TZ' => __( 'Tanzania', 'wp-express-checkout' ),
			'TH' => __( 'Thailand', 'wp-express-checkout' ),
			'TL' => __( 'Timor-Leste', 'wp-express-checkout' ),
			'TG' => __( 'Togo', 'wp-express-checkout' ),
			'TK' => __( 'Tokelau', 'wp-express-checkout' ),
			'TO' => __( 'Tonga', 'wp-express-checkout' ),
			'TT' => __( 'Trinidad and Tobago', 'wp-express-checkout' ),
			'TN' => __( 'Tunisia', 'wp-express-checkout' ),
			'TR' => __( 'Turkey', 'wp-express-checkout' ),
			'TM' => __( 'Turkmenistan', 'wp-express-checkout' ),
			'TC' => __( 'Turks and Caicos Islands', 'wp-express-checkout' ),
			'TV' => __( 'Tuvalu', 'wp-express-checkout' ),
			'UG' => __( 'Uganda', 'wp-express-checkout' ),
			'UA' => __( 'Ukraine', 'wp-express-checkout' ),
			'AE' => __( 'United Arab Emirates', 'wp-express-checkout' ),
			'GB' => __( 'United Kingdom', 'wp-express-checkout' ),
			'US' => __( 'United States', 'wp-express-checkout' ),
			'UM' => __( 'United States Minor Outlying Islands', 'wp-express-checkout' ),
			'VI' => __( 'United States Virgin Islands', 'wp-express-checkout' ),
			'UY' => __( 'Uruguay', 'wp-express-checkout' ),
			'UZ' => __( 'Uzbekistan', 'wp-express-checkout' ),
			'VU' => __( 'Vanuatu', 'wp-express-checkout' ),
			'VA' => __( 'Vatican City', 'wp-express-checkout' ),
			'VE' => __( 'Venezuela', 'wp-express-checkout' ),
			'VN' => __( 'Vietnam', 'wp-express-checkout' ),
			'WF' => __( 'Wallis and Futuna', 'wp-express-checkout' ),
			'EH' => __( 'Western Sahara', 'wp-express-checkout' ),
			'YE' => __( 'Yemen', 'wp-express-checkout' ),
			'ZM' => __( 'Zambia', 'wp-express-checkout' ),
			'ZW' => __( 'Zimbabwe', 'wp-express-checkout' ),
		);
		return $countries;
	}

	public static function get_country_name_by_country_code( $country_code ) {
		$countries = self::get_countries();
		$country_code = isset( $country_code ) ? strtoupper( $country_code ) : '';
		$country = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
		return $country;
	}

	/**
	 * Get order data as an associative array.
	 *
	 * @param int $order_id Order ID.
	 * 
	 * @return array Order data as array.
	 */
	public static function get_order_data_in_array_format($order_id){
		try {
			$order = Orders::retrieve( $order_id );
		} catch ( Exception $exc ) {
			return array();
		}

		return $order->get_order_data_in_array();
	}

	public static function get_paypal_checkout_locale_code( $default = '' ) {
		$locale = Main::get_instance()->get_setting( 'default_locale' );
		$locale = str_replace( '-', '_',  sanitize_text_field( $locale ) );
		if ( empty( $locale ) ) {
			return $default;
		}

		return $locale;
	}

	/**
	 * Compare two monetary amounts for equality within a reasonable tolerance.
	 * Uses a tolerance of 0.01 (1 cent) to account for floating-point rounding differences.
	 * This is particularly important when percentage-based discounts are applied (e.g., 50% off $49.99).
	 */
	public static function almost_equal($a, $b) {
		// Use 0.01 tolerance for currency (acceptable rounding difference of 1 cent)
		$tolerance = 0.01;
		return abs($a - $b) < $tolerance;
	}

	/**
	 * Converts price amount to cents.
	 *
	 * It uses round and then number_format to ensure that there is always 2 decimal places (even if the trailing zero is dropped by the round function).
	 * This is to avoid issues with Stripe's zero decimal currencies.
	 *
	 * @param int|float $amount The price amount to convert.
	 *
	 * @return int Price in cents
	 */
	public static function amount_in_cents($amount) {
		$amountFormatted = round( $amount, 2 );
		$amountFormatted = number_format( $amountFormatted, 2 );

		$centsAmount = $amountFormatted;

		//if amount is not decimal. multiply by 100
		if (strpos($amountFormatted, '.') === false) {
			$amountUnformatted = str_replace(['.', ','], '', $amountFormatted);
			$centsAmount = intval($amountUnformatted) * 100;
		}
		else{
			//if amount is decimal, remove the period and comma.
			$amountUnformatted = str_replace(['.', ','], '', $amountFormatted);
			$centsAmount = intval($amountUnformatted);
		}
		return $centsAmount;
	}

	/**
	 * Covert amount to normal value form cents value.
	 *
	 * @param $amount
	 * @param $currency string Currency code.
	 *
	 * @return float|int|mixed
	 */
	public static function amount_from_cents($amount, $currency){
		if (self::is_zero_cents_currency($currency)){
			return $amount;
		}

		return self::round_price($amount / 100, 2);
	}

	public static function is_zero_cents_currency($payment_currency){
		$zero_cents_currencies = array( 'JPY', 'MGA', 'VND', 'KRW' ) ;
		return in_array( strtoupper($payment_currency), $zero_cents_currencies ) ;
	}

	public static function is_completed_status($status) {
		return in_array(strtoupper($status), array('COMPLETED', 'COMPLETE'));
	}

	public static function get_saved_stripe_tax_rate_id($tax_percentage){
		$tax_rate_option_key = sanitize_text_field("tax_rate_" .$tax_percentage);
		$saved_tax_rates = get_option('wpec_saved_stripe_tax_rates', array());
		if (isset($saved_tax_rates[$tax_rate_option_key])){
			return $saved_tax_rates[$tax_rate_option_key];
		}

		return '';
	}

	public static function save_stripe_tax_rate_id($tax_percentage, $tax_rate_id){
		$tax_rate_option_key = sanitize_text_field("tax_rate_" .$tax_percentage);
		$saved_tax_rates = get_option('wpec_saved_stripe_tax_rates', array());

		$saved_tax_rates[$tax_rate_option_key] = $tax_rate_id;

		update_option('wpec_saved_stripe_tax_rates', $saved_tax_rates);
	}
	
	public static function get_stripe_secret_key() {
		$is_live = Main::get_instance()->get_setting( 'stripe_is_live' );
		if ( $is_live ) {
			$secret_key = Main::get_instance()->get_setting( 'stripe_live_secret_key' );
		} else {
			$secret_key = Main::get_instance()->get_setting( 'stripe_test_secret_key' );
		}

		return $secret_key;
	}

	public static function get_stripe_api_version() {
		return "2025-09-30.clover";
	}

	/**
	 * Constructs and returns a stripe client object for doing api calls.
	 *
	 * @return \Stripe\StripeClient
	 */
	public static function get_stripe_client() {
		$secret_key = self::get_stripe_secret_key();
		$api_version = self::get_stripe_api_version();

		return new \Stripe\StripeClient(array(
			'api_key' => $secret_key,
			'stripe_version' => $api_version,
		));
	}

	public static function get_stripe_address_str_from_array( $address ) {
		$city        = isset( $address['city'] ) ? $address['city'] : '';
		$state       = isset( $address['state'] ) ? $address['state'] : '';
		$postal_code = isset( $address['postal_code'] ) ? $address['postal_code'] : '';
		$country     = isset( $address['country'] ) ? Utils::get_country_name_by_country_code( $address['country'] ) : '';
		$line1       = isset( $address['line1'] ) ? $address['line1'] : '';
		$line2       = isset( $address['line2'] ) ? $address['line2'] : '';

		$address_array = array_filter( array(
			$line1,
			$line2,
			$city,
			$state,
			$postal_code,
			$country
		) );

		return implode( ', ', $address_array );
	}

	public static function is_order_edit_screen(){
		if (isset($_GET['post'])) {
			return true;
		}

		return false;
	}

	public static function wpec_load_template( $template, $args = array(), $load_once = false ) {
		$template       = strval( $template );
		$template_name  = $template . '.php';
		$template_files = array(
			'wp-express-checkout/'. $template_name,
		);

		//Filter hook to allow overriding of the template file path
		$template_files = apply_filters( 'wpec_load_template_files', $template_files, $template_name);

		$located = locate_template($template_files);

		$plugin_template_path = WPEC_PLUGIN_PATH . 'public/views/templates/'. $template_name;

		if ( empty($located) && file_exists( $plugin_template_path ) ) {
			$located = $plugin_template_path;
		}

		$tpl_html = '';

		if ( ! empty( $located ) ) {
			// Template file found in theme. Load it.
			ob_start();

			if ($load_once) {
				include_once $located;
			} else {
				include $located;
			}

			$tpl_html = ob_get_clean();
		} else {
			$tpl_html = '<p class="wpec-error-message">'.__('Template not found!', 'wp-express-checkout').'</p>';
		}

		return $tpl_html;
	}
}
