<?php

namespace Woo_Faqs\CoreShared;

/**
 * Bootstraps shared functionality
 *
 * Common pattern that takes care of adding filters and actions
 * Utilizing a time-saving namespace abstraction
 *
 * @since 3.0.0
 */
function hooks() {
	$n = function ( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init', $n( 'init' ) );
}

/**
 * Initialize common behavior
 *
 * Currently:
 * - Loads translations
 * - Registers post types
 *
 * @since 3.0.0
 */
function init() {
	load_translations();
	register_post_types();
}

/**
 * Load translations
 *
 * @since 3.0.0
 */
function load_translations() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-faqs' );
	load_textdomain( 'woocommerce-faqs', WP_LANG_DIR . '/woocommerce-faqs/woocommerce-faqs-' . $locale . '.mo' );
	load_plugin_textdomain( 'woocommerce-faqs', false, WOOFAQS_PLUGIN_DIR . '/languages/' );
}

/**
 * Registers this plugin's post type
 *
 * Note that the link is placed under edit.php?post_type=product
 *
 * @since 3.0.0
 */
function register_post_types() {
	$labels = array(

		'name'               => 'FAQs',
		'singular_name'      => 'FAQ',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New FAQ',
		'edit_item'          => 'Edit FAQ',
		'edit_item'          => 'Edit FAQ',
		'new_item'           => 'New FAQ',
		'all_items'          => 'FAQs',
		'view_item'          => 'View FAQ',
		'search_items'       => 'Search FAQs',
		'not_found'          => 'No FAQs found',
		'not_found_in_trash' => 'No FAQs found in Trash',
		'parent_item_colon'  => '',
		'menu_name'          => 'FAQs',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'edit.php?post_type=product',
		'query_var'          => true,
		'rewrite'            => array(
			'slug' => 'woo_faq'
		),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array(
			'title',
			'editor',
			'author',
			'comments',
		),
	);

	register_post_type( WOOFAQS_POST_TYPE, $args );
}

/**
 * Upgrade logic
 *
 * @since 3.0.0
 */
function upgrade() {

	$current_version = get_option( WOOFAQS_OPTIONS_PREFIX . '_plugin_version', '3.0.0' );

	if ( WOOFAQS_VERSION !== $current_version ) {

		switch ( $current_version ) {
			case '1.0.9':
				/**
				 * When upgrading from v1.0.9,
				 * There is a need to update the comment statuses of existing posts
				 * to "open" to allow commenting; otherwise, admins can't answer the questions.
				 */
				global $wpdb;
				$table = $wpdb->posts;
				$data = array( 'comment_status' => 'open' );
				$where = array( 'post_type' => WOOFAQS_POST_TYPE );
				$format = array( '%s' );
				$where_format = array( '%s' );

				$wpdb->update( $table, $data, $where, $format, $where_format );
				break;
		}

		update_option( WOOFAQS_OPTIONS_PREFIX . '_plugin_version', WOOFAQS_VERSION );

	}
}

/**
 * Runs on activation
 *
 * Currently:
 * - Calls `init`
 * - Calls `upgrade`
 * - Flushes the rewrite rules so this plugin's PT permalinks
 * are registered with WP
 *
 * @since 3.0.0
 */
function activate() {
	init();
	upgrade();
	flush_rewrite_rules();
}