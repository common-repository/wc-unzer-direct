<?php

if ( ! function_exists( 'wc_unzer_direct_get_template' ) ) {
	/**
	 * Convenience wrapper based on the wc_get_template method
	 *
	 * @param        $template_name
	 * @param array  $args
	 * @param string $template_path
	 * @param string $default_path
	 */
	function wc_unzer_direct_get_template( $template_name, $args = [] ) {
		$template_path = 'wc-unzer-direct/';
		$default_path = WC_UNZER_DIRECT_PATH . 'templates/';

		wc_get_template( $template_name, $args, $template_path, $default_path );
	}
}
