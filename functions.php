<?php
/**
 * Return the current version of WC
 *
 * @since     1.0.5
 *
 * @return    string    Version of WooCommerce plugin
 */
function get_woocommerce_version(){

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( ! is_file( $dir = WPMU_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {

		if ( ! is_file( $dir = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) )

    	$dir = null;

	}
	
	$wc_version = get_plugin_data($dir);

	$wc_version = $wc_version['Version'];

	return $wc_version;

}

/**
 * Returns the status of current WC version
 * meeting this plugin's requirements
 *
 * @since     1.0.5
 *
 * @return    boolean	True if it meets, false if not
 */
function meets_min_wc_version(){

	$wc_version = get_woocommerce_version();

	$wc_version = explode('.', $wc_version);

	$multiply = 10000;

	$divide = 1;

	foreach( $wc_version as &$subdigit ){

		$subdigit *= $multiply / $divide;

		$divide *= 10;

	}

	$wc_version = array_sum($wc_version);

	if( $wc_version < 16600 ) {

		return false;

	}

	return true;

}