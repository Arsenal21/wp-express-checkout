<?php

namespace WP_Express_Checkout;

class Utils_Kses {

	public static function wp_kses_common_attributes() {
		$common_attributes = array(
			'id'       => true,
			'class'    => true,
			'style'    => true,
			'title'    => true,
			'disabled' => true,
			'aria-*'   => true,
			'data-*'   => true,
		);
		return apply_filters( 'wpec_kses_common_attributes', $common_attributes );
	}

	public static function wp_kses_select_option_tags(){

		$common_attributes = self::wp_kses_common_attributes();

		$allowed_tags = array(
			'option' => array_merge( $common_attributes, array(
				'value'    => true,
				'label'    => true,
				'selected' => true,
			) ),

			'optgroup' => array_merge( $common_attributes, array(
				'label' => true,
			) ),
		);

		return apply_filters( 'wpec_kses_select_option_tags', $allowed_tags );
	}

	/**
	 * Returns allowed select field tags and attributes for wp_kses().
	 *
	 * @return array Allowed HTML tags and attributes.
	 */
	public static function wp_kses_select_tags() {
		$allowed_tags = array(
			'select' => array_merge( self::wp_kses_common_attributes(), array(
				'name'         => true,
				'multiple'     => true,
				'required'     => true,
				'size'         => true,
				'form'         => true,
				'autocomplete' => true,
				'autofocus'    => true,
				'tabindex'     => true,
			) ),
		);
		$allowed_tags = array_merge( $allowed_tags, self::wp_kses_select_option_tags() );

		return apply_filters( 'wpec_kses_select_tags', $allowed_tags );
	}

	public static function wp_kses_post_tags_with_form() {
		// Array of common standard and custom form attributes
		$all_attributes = array(
			'action'       => true,
			'method'       => true,
			'type'         => true,
			'name'         => true,
			'value'        => true,
			'placeholder'  => true,
			'id'           => true,
			'class'        => true,
			'style'        => true,
			'checked'      => true,
			'selected'     => true,
			'required'     => true,
			'readonly'     => true,
			'disabled'     => true,
			'multiple'     => true,
			'rows'         => true,
			'cols'         => true,
			'for'          => true,
			'autocomplete' => true,
			'min'          => true,
			'max'          => true,
			'step'         => true,
			'pattern'      => true,
			'aria-*'       => true, // WP support for ARIA attributes
			'data-*'       => true, // WP support for Data attributes
		);

		$allowed_form_tags = array(
			'form'     => $all_attributes,
			'input'    => $all_attributes,
			'select'   => $all_attributes,
			'option'   => $all_attributes,
			'textarea' => $all_attributes,
			'button'   => $all_attributes,
			'label'    => $all_attributes,
		);

		// Merge with standard allowed post tags so paragraph tags, divs, etc. are still kept
		$allowed_tags = array_merge_recursive( wp_kses_allowed_html( 'post' ), $allowed_form_tags );

		return apply_filters( 'wpec_kses_post_tags_with_form', $allowed_tags );
	}

}