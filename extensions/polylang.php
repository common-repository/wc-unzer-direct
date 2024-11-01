<?php

if ( ! function_exists( 'filter_wc_unzer_direct_polylang_language' ) ) {
	/**
	 * Automatically sets the payment window language to the Polylang user language, if available.
	 *
	 * @param $language
	 *
	 * @return mixed
	 */
	function filter_wc_unzer_direct_polylang_language( $language ) {
		if ( function_exists( 'pll_current_language' ) ) {
			$language = pll_current_language();
		}

		return $language;
	}

	add_filter( 'wc_unzer_direct_language', 'filter_wc_unzer_direct_polylang_language' );
}
