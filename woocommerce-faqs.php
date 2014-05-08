<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   WooCommerce_FAQs
 * @author    Josh Levinson <joshalevinson@gmail.com>
 * @license   GPL-2.0+
 * @link      http://joshlevinson.me
 * @copyright 2014 Josh Levinson
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Product FAQs
 * Plugin URI:        http://redactweb.com/woocommerce-faqs
 * Description:       Enables your WooComerce powered site to utilize a FAQ
 * Version:           2.0.4
 * Author:            Josh Levinson
 * Author URI:        http://joshlevinson.me
 * Text Domain:       woocommerce-faqs
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Common Functions
 *----------------------------------------------------------------------------*/
require_once( plugin_dir_path( __FILE__ ) . '/functions.php' );

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-woocommerce-product-faqs.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'WooCommerce_FAQs', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WooCommerce_FAQs', 'deactivate' ) );

/*
 * Get the plugin instance
 */
add_action( 'plugins_loaded', array( 'WooCommerce_FAQs', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * Get the plugin admin instance
 */
if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-woocommerce-product-faqs-admin.php' );
	
	add_action( 'plugins_loaded', array( 'WooCommerce_FAQs_Admin', 'get_instance' ) );

}
