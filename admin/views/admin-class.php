<?php
/**
 * WooCommerce General Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_FAQs' ) ) :

/**
 * WC_Admin_Settings_General
 */
class WC_Settings_FAQs extends WC_Settings_Page {

	/**
	 * 
	 * Unique identifier for your plugin.
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = '';

	/**
	 * Instance of main plugin class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $plugin = null;

	/**
	 * Constructor.
	 */
	public function __construct() {

		self::$plugin = WooCommerce_FAQs_Admin::get_instance();

		$this->id    = 'faqs';
		$this->label = __( 'FAQs', 'woocommerce_faqs' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {

		return self::$plugin->get_settings();

	}

	/**
	 * Save settings
	 */
	public function save() {
		
		$settings = $this->get_settings();

		WC_Admin_Settings::save_fields( $settings );

	}

}

endif;

return new WC_Settings_FAQs();
