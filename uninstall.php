<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WooCommerce Product FAQs
 * @author    Josh Levinson <josh@joshlevinson.me>
 * @license   GPL-2.0+
 * @link      http://redactweb.com/
 * @copyright 2013 Josh Levinson
 */

// If uninstall, not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option('woocommerce_faqs_use_antispam');
delete_option('woocommerce_faqs_publisher_key');
delete_option('woocommerce_faqs_scoring_key');