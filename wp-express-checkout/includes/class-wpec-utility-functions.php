<?php

namespace WP_Express_Checkout;

class Utils {

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
	 * @param string $text     The text to be processed.
	 * @param int    $order_id The order ID to retrieve default values.
	 * @param array  $args     The custom arguments to replace default order data.
	 *
	 * @return string
	 */
	public static function replace_dynamic_order_tags( $text, $order_id, $args = array() ) {
		$order           = Orders::retrieve( $order_id );
		$product_details = self::get_product_details( $order ) . "\n";
		$payer_details   = $order->get_data( 'payer' );
		$coupon_item     = $order->get_item( 'coupon' );

		$tags_vals = array(
			'first_name'      => $payer_details['name']['given_name'],
			'last_name'       => $payer_details['name']['surname'],
			'product_details' => $product_details,
			'payer_email'     => $payer_details['email_address'],
			'transaction_id'  => $order->get_resource_id(),
			'purchase_amt'    => $order->get_total(),
			'purchase_date'   => date( 'Y-m-d' ),
			'coupon_code'     => ! empty( $coupon_item['mata']['code'] ) ? $coupon_item['mata']['code'] : 0,
			'address'         => '', // Not implemented yet.
			'order_id'        => $order_id,
		);

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

		$white_list = array(
			'first_name',
			'last_name',
			'product_details',
			'payer_email',
			'transaction_id',
			'purchase_amt',
			'purchase_date',
			'coupon_code',
			'address',
			'order_id',
		);

		$tags = array();
		$vals = array();

		foreach ( $white_list as $item ) {
			$tags[] = "{{$item}}";
			$vals[] = ( isset( $args[ $item ] ) ) ? $args[ $item ] : '';
		}

		$body = stripslashes( str_replace( $tags, $vals, $text ) );

		return $body;
	}

	/**
	 * Generates plain product details for Order summary and emails by given
	 * order details.
	 *
	 * @since 2.0
	 *
	 * @param Order $order The order details stored in the
	 *
	 * @return string
	 */
	public static function get_product_details( $order ) {
		$output   = '';

		/* translators: {Order Summary Item Name}: {Value} */
		$template = __( '%1$s: %2$s', 'wp-express-checkout' );

		$items = $order->get_items();

		$product_items = array();
		$other_items   = array();

		foreach ( $items as $item ) {
			if ( $item['type'] === Products::$products_slug ) {
				$product_item = $item;
				continue;
			}
			$ptype_obj = get_post_type_object( get_post_type( $item['post_id'] ) );
			if ( $ptype_obj->public ) {
				$product_items[] = $item;
			} else {
				$other_items[] = $item;
			}
		}

		$output .= sprintf( $template, __( 'Product Name', 'wp-express-checkout' ), $product_item['name'] ) . "\n";
		$output .= sprintf( $template, __( 'Quantity', 'wp-express-checkout' ), $product_item['quantity'] ) . "\n";
		$output .= sprintf( $template, __( 'Price', 'wp-express-checkout' ), self::price_format( $product_item['price'] ) ) . "\n";

		$subtotal = $product_item['price'] * $product_item['quantity'];

		foreach ( $product_items as $item ) {
			$amnt_str  = self::price_format( $item['price'] );
			$subtotal += $item['price'] * $item['quantity'];
			$output   .= sprintf( $template, $item['name'], $amnt_str ) . "\n";
		}

		if ( $subtotal !== $product_item['price'] ) {
			$output .= '--------------------------------' . "\n";
			$output .= sprintf( $template, __( 'Subtotal', 'wp-express-checkout' ), self::price_format( $subtotal ) ) . "\n";
			$output .= '--------------------------------' . "\n";
		}

		foreach ( $other_items as $item ) {
			$amnt_str  = self::price_format( $item['price'] );
			$subtotal += $item['price'] * $item['quantity'];
			$output   .= sprintf( $template, $item['name'], $amnt_str ) . "\n";
		}

		$output .= '--------------------------------' . "\n";
		$output .= sprintf( $template, __( 'Total Amount', 'wp-express-checkout' ), self::price_format( $order->get_total() ) ) . "\n";

		return $output;
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

	public static function get_countries_untranslated() {
		$countries = array(
			''   => '',
			'AF' => 'Afghanistan',
			'AX' => 'Aland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BQ' => 'Bonaire',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'VG' => 'British Virgin Islands',
			'BN' => 'Brunei',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CD' => 'Congo, Democratic Republic of',
			'CG' => 'Congo, Republic of',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CW' => 'Curacao',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern and Antarctic Lands',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and McDonald Islands',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'CI' => 'Ivory Coast',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KP' => "Korea, Democratic People's Republic of",
			'KR' => 'Korea, Republic of',
			'XK' => 'Kosovo',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Laos',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macau',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestine',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn Islands',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthelemy',
			'SH' => 'Saint Helena, Ascension, and Tristan da Cunha',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SX' => 'Sint Maarten',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia',
			'SS' => 'South Sudan',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syria',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Minor Outlying Islands',
			'VI' => 'United States Virgin Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican City',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);
		return $countries;
	}

}
