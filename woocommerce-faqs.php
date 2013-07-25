<?php
/*
 * @package   WooCommerce Product FAQs
 * @author    Josh Levinson <josh@joshlevinson.me>
 * @license   GPL-2.0+
 * @link      http://redactweb.com
 * @copyright 2013 Josh Levinson
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce Product FAQs
 * Plugin URI:  http://redactweb.com/woocommerce-faqs
 * Description: Enables your WooComerce powered site to utilize a FAQ
 * (Frequently Asked Questions) product-specific section that enables
 * customers to ask questions, get responeses, and see other question threads
 * Version:     1.0.5
 * Author:      Josh Levinson
 * Author URI:  http://joshlevinson.me
 * Text Domain: woocommerce-faqs
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//include common functions
require_once( plugin_dir_path( __FILE__ ) . 'functions.php' );

//include main plugin class
require_once( plugin_dir_path( __FILE__ ) . 'class-woocommerce-faqs.php' );

//activation/deactivation hooks
register_activation_hook( __FILE__, array( 'WooCommerce_FAQs', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WooCommerce_FAQs', 'deactivate' ) );

//get it!
WooCommerce_FAQs::get_instance();