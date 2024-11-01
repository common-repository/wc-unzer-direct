<?php

if ( ! function_exists( 'filter_wc_unzer_direct_wpml_language' ) ) {
	/**
	 * Automatically sets the payment window language to the WPML user language, if available.
	 *
	 * @param $language
	 *
	 * @return mixed
	 */
	function filter_wc_unzer_direct_wpml_language( $language ) {
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$language = ICL_LANGUAGE_CODE;
		}

		return $language;
	}

	add_filter( 'wc_unzer_direct_language', 'filter_wc_unzer_direct_wpml_language' );
}
