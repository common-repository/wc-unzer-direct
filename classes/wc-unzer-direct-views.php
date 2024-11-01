<?php

/**
 * Class WC_UnzerDirect_Views
 */
class WC_UnzerDirect_Views
{
    /**
     * Fetches and shows a view
     *
     * @param string $path
     * @param array $args
     */
    public static function get_view( $path, $args = [] )
    {
        if (is_array($args) && ! empty($args)) {
            extract($args);
        }

        $file = WC_UNZER_DIRECT_PATH . 'views/' . trim($path);

        if (file_exists($file)) {
            include $file;
        }
    }

	/**
	 * @param $path
	 *
	 * @return string
	 */
	public static function asset_url($path) {
		return WC_UNZER_DIRECT()->plugin_url( 'assets/' . $path);
	}
}
