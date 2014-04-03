<?php

/* Plugin Name.
* @package   WooCommerce_FAQs_Admin
* @author    Josh Levinson <joshalevinson@gmail.com>
* @license   GPL-2.0+
* @link      http://joshlevinson.me
* @copyright 2014 Josh Levinson
*/

/* Plugin class. This class should ideally be used to work with the
* administrative side of the WordPress site.
* If you're interested in introducing public-facing
* functionality, then refer to `class-plugin-name.php`
* @TODO: Rename this class to a proper name for your plugin.
* @package WooCommerce_FAQs_Admin
* @author  Your Name <email@example.com>
*/

class WooCommerce_FAQs_Admin {

	/**
	* Instance of this class.
	* @since    1.0.0
	* @var      object
	*/

	protected static $instance = null;

	/**
	* Instance of main plugin class.
	* @since    1.0.0
	* @var      object
	*/

	protected static $plugin = null;

	/**
	* Settings array
	* @since    1.0.0
	* @var      string
	*/

	protected $settings;

	/**
	* 
	* Unique identifier for your plugin.
	* The variable name is used as the text domain when internationalizing strings
	* of text. Its value should match the Text Domain file header in the main
	* plugin file.
	* @since    1.0.0
	* @var      string
	*/

	protected $plugin_slug = '';

	/**
	* Initialize the plugin by loading admin scripts & styles and adding a
	* settings page and menu.
	* @since     1.0.0
	*/

	private function __construct() {

		/*
		* Call $plugin_slug from public plugin class.

		*/

		$this::$plugin = WooCommerce_FAQs::get_instance();

		$this->plugin_slug = $this::$plugin->p('plugin_slug');

		//load upgrade functions

		add_action('admin_init', array( $this, 'upgrade_actions' ) );

		$this->woocommerce_actions();

		// Load admin JavaScript.

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		//filter post row actions to add reply tab

		add_filter( 'post_row_actions', array( $this, 'action_row'), 10, 2);

		//filter for preview post link to link to product

		//add_filter( 'preview_post_link', array( $this, 'preview_link' ) );

		//post type icons

		add_action( 'admin_head', array( $this, 'post_type_icons' ) );

		//custom post table columns

		add_filter( 'manage_edit-'.$this::$plugin->p('post_type').'_columns', array( $this, 'set_custom_edit_columns' ) );

		

		//custom post table columns content

		add_action( 'manage_'.$this::$plugin->p('post_type').'_posts_custom_column' , array( $this, 'custom_column' ), 1, 2 );

		//meta boxes

		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );

		//save meta

		add_action( 'save_post', array( $this, 'save_meta' ) );

		//filter for meta boxes' text

		add_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );

		// @deprecated as of 2.0

		//add_action('edit_form_after_title', array( $this, 'view_link') );

	}

	/**
	* Return an instance of this class.
	* @since     1.0.0
	* @return    object    A single instance of this class.
	*/

	public static function get_instance() {

		// If the single instance hasn't been set, set it now.

		if ( null == self::$instance ) {

			self::$instance = new self;

		}

		return self::$instance;

	}

	/**
	* Render the settings page for this plugin.
	* @since    1.0.0
	*/

	public function admin_options() {

		include_once( 'views/admin.php' );

	}

	/**
	* Render the settings page for this plugin.
	* Uses WC 2.1's settings pages API
	* @since    1.0.0
	*/

	public function admin_options_class( $settings ) {

		$settings[] = include_once( 'views/admin-class.php' );

		return $settings;

	}

	/**
	* Run upgrade options
	* @since    1.1.0
	*/

	function upgrade_actions() {

		$current_version = get_option( $this::$plugin->p('option_prefix') . 'plugin_version', '1.0.9' );

		if($current_version != $this::$plugin->get_version() ) {

			switch ($current_version) {

				case '1.0.9':

					//update comment statuses

					global $wpdb;

					$wpdb->update($table = $wpdb->posts, $data = array('comment_status'=>'open'), $where = array( 'post_type'=>$this::$plugin->p('post_type') ), $format = array('%s'), $where_format = array('%s') );

					break;

			}

			update_option( $this::$plugin->p('option_prefix') . 'plugin_version', $this::$plugin->get_version() );

		}

	}

	/**
	* Register and enqueue admin-specific style sheet.
	* the filter that calls this function is commented out 
	* as of 1.0.0, but this function should stay here for 
	* reference for later use
	* @since     1.0.0
	* @return    null    Return early if no settings page is registered.
	*/

	public function enqueue_admin_styles() {

		$screen = get_current_screen();

		if ( $screen->id == $this::$plugin->p('plugin_screen_hook_suffix') ) {

			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), $this::$plugin->get_version() );

		}

	}

	/**
	* Outputs CSS for post type icons
	* @since     1.0.0
	* @return    null
	*/

	function post_type_icons() {

	    echo '<style type="text/css" media="screen">

	        #menu-posts-' . $this::$plugin->p('post_type') . ' .wp-menu-image {

	            background: url(' . plugins_url( '/assets/images/icon.png', __FILE__ ) . ') no-repeat 0 -32px !important;

	        }

		#menu-posts-' . $this::$plugin->p('post_type') . ':hover .wp-menu-image, #menu-posts-' . $this::$plugin->p('post_type') . '.wp-has-current-submenu .wp-menu-image {

	            background-position:0 0 !important;

	        }

		#icon-edit.icon32-posts-' . $this::$plugin->p('post_type') . ' {background: url(' . plugins_url( '/assets/images/full-32x32.png', __FILE__ ) . ') no-repeat 0px 0px !important;}

	    </style>';

	}

	/**
	* Register and enqueue admin-specific JavaScript.
	* @since     1.0.0
	* @return    null    Return early if we aren't on the post type edit scren
	*/

	public function enqueue_admin_scripts() {

		$screen = get_current_screen();

		//we need to load this script on the edit page for our post type

		if ( $screen->id == 'edit-' . $this::$plugin->p('post_type') ) {

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( '/assets/js/admin.js', __FILE__ ), array( 'jquery' ), $this::$plugin->get_version() );

			$localize = array(

				'spinner' => admin_url('images/wpspin_light.gif'),

				);

			//if we are administering a faq, localize that so it is available to our javascript

			if( isset( $_GET['highlight'] ) ) {

				$highlight = intval( $_GET['highlight'] );

				$localize['faq_highlight'] = $highlight;

				//and localize the color with a filter, so it can be changed either by user or maybe later as a settings option

				$localize['faq_highlight_color'] = apply_filters( $this::$plugin->p('option_prefix') . 'admin_faq_highlight_color', '#9ED1D6' );

			}

			wp_localize_script( $this->plugin_slug . '-admin-script', $this::$plugin->p('option_prefix') . 'data', $localize );

		}

	}

	/**
	* Register the administration menu for this plugin into the WordPress Dashboard menu.
	* @since    1.0.0
	*/

	public function admin_menu($tabs) {

		$tabs['faqs'] = __( 'FAQs', 'woocommerce' );

		return $tabs;

	}

	/**
	* Render the settings fields for the settings page.
	* @since    1.0.0
	*/

	function display_settings() {

		woocommerce_admin_fields( $this->settings );

	}

	/**
	* Changes up the action row for custom behavior
	* @since    1.0.0
	*/

	function action_row( $actions, $post ) {

		//check for our post type

		if ( $post->post_type == $this::$plugin->p('post_type') ) {

			$post_type_object = get_post_type_object( $post->post_type );

			$post_type_label = $post_type_object->labels->singular_name;

			if( $post->post_status == 'draft' || $post->post_status == 'pending' ) {

				/* $actions['pre_view'] = "<a title='" . esc_attr( __( 'Preview this', $this->plugin_slug ) ) . $post_type_label .

				"' href='" . $this->preview_link() . "'>" . __( 'Preview', $this->plugin_slug ) . "</a>";*/

				$actions['publish'] = "<a href='#' class='submitpublish' data-id='" . $post->ID."' title='" . esc_attr( __( 'Approve this ' , $this->plugin_slug ) ) .

				$post_type_label . "' data-nonce='" . wp_create_nonce( 'publish-post_' . $post->ID ) . "'>" . __( 'Approve', $this->plugin_slug) . "</a>";

			}

			/*else{

				

				$actions['view'] = "<a title='" . esc_attr( __( 'View this ', $this->plugin_slug ) ) . $post_type_label . "' href='" . $this->preview_link() . "'>" . __( 'View', $this->plugin_slug ) . "</a>";

			}*/

		}

		return $actions;

	}

	/**
	* Generates the preview/view link for our FAQs
	* @since    1.0.0
	*/

	function preview_link( $preview_link = '' ) {

    	global $post;

    	if( $post->post_type == $this::$plugin->p('post_type') ) {

    		$preview_link = get_permalink( (int)get_post_meta( $post->ID, '_' . $this::$plugin->p('post_type') . '_product', true ) );

    		$publish = ( $post->post_status == 'publish' ? 'view' : 'preview' );

    		$preview_link = add_query_arg( 'faq-' . $publish, $post->ID . '#tab-faqs', $preview_link);

    	}

    	return $preview_link;

	}

	/**
	* Add actions to WC settings based on WC version
	* @since     1.0.4
	* @return    null
	*/

	function woocommerce_actions() {

		$wc_version = get_woocommerce_version();

		$this->settings = $this->get_settings();

		//2.1 compat

		if( $wc_version >= 2.1 ) {

			//action for settings tab content

			add_filter( 'woocommerce_get_settings_pages', array( $this, 'admin_options_class' ) );

		}

		else{

			// Add the options page and menu item.

			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'admin_menu' ) );

			//2.0 compat

			if( $wc_version >= 2.0 ) {

				//action for settings tab content

				add_action( 'woocommerce_settings_start', array( $this, 'admin_options' ) );

				add_action( 'woocommerce_settings_tabs_faqs', array( $this, 'display_settings' ) );

			}

			//we're less than 2.0, but more than min (1.6)

			else{

				//action for settings tab content

				add_action( 'woocommerce_settings_tabs', array( $this, 'admin_options' ) );

				//we have to manually update settings

				add_action('woocommerce_update_options_faqs', array( $this, 'update_old_wc_options' ) );

			}

		}

	}

	function get_settings(){

		$wc_version = get_woocommerce_version();

		if( $wc_version < 2.0 ) {

			$title = 'name';

		}

		else{

			$title = 'title';

		}

		$publisher = get_option( $this::$plugin->p('option_prefix') . 'publisher_key' );

		$scoring = get_option( $this::$plugin->p('option_prefix') . 'scoring_key' );

		return array(

				array(

					$title 		=> __('General Settings', $this->plugin_slug ),

					'type' 		=> 'title',

					'id' 		=> $this::$plugin->p('option_prefix') . 'general',

					'desc'		=> ''

				),

				array(

					$title		=> __('Expand FAQ content by default', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'expand_faqs',

					'type'		=> 'checkbox',

					'default'	=> 'no',

					'desc'		=> __('If this is checked, all FAQs will expand when the tab is visible to show the question and answer.',

						$this->plugin_slug )

				),

				array(

					$title		=> __('Disable asking functionality', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'disable_ask',

					'type'		=> 'checkbox',

					'default'	=> 'no',

					'desc'		=> __('If this is checked, asking/answering can only be done by someone with priveleges to edit the product.',

						$this->plugin_slug )

				),

				array(

					$title		=> __('FAQ notification email address', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'answerer_email',

					'type'		=> 'text',

					'desc'		=> __('Default (left blank), new FAQ email is sent to the product author.',

						$this->plugin_slug )

				),

				array(

					$title		=> __('FAQ notification from name', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'from_name',

					'type'		=> 'text',

					'desc'		=> __('Default\'s to WordPress default from name.',

						$this->plugin_slug )

				),

				array(

					'type'		=> 'sectionend',

					'id'		=> $this::$plugin->p('option_prefix') . 'general'

				),

				array(

					$title 		=> __('Anti-Spam Settings', $this->plugin_slug ),

					'type' 		=> 'title',

					'id' 		=> $this::$plugin->p('option_prefix') . 'antispam',

					'desc'		=> __('Please choose your Anti-Spam settings.' .

									' If you choose to disable AYAH antispam, an invisible "honeypot" antispam method will be used.', $this->plugin_slug ) .

									sprintf( __( ' Get your API keys %shere%s', $this->plugin_slug ),

										'<a target="_blank" href="http://portal.areyouahuman.com/signup/basic">', '</a>.' )

				),

				array(

					$title		=> __('Use Are You A Human antispam?', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'use_antispam',

					'type'		=> 'checkbox',

					'default'	=> 'no'

				),

				array(

					$title		=> __('Publisher Key', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'publisher_key',

					'type'		=> 'text',

					'default'	=> $publisher

				),

				array(

					$title		=> __('Public/Scoring Key', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'scoring_key',

					'type'		=> 'text',

					'default'	=> $scoring

				),

				array(

					'type'		=> 'sectionend',

					'id'		=> $this::$plugin->p('option_prefix') . 'antispam'

				),

				array(

					$title 		=> __('Tab Settings', $this->plugin_slug ),

					'type' 		=> 'title',

					'id' 		=> $this::$plugin->p('option_prefix') . 'tab_settings'

				),

				array(

					$title		=> __('Tab Title', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'tab_title',

					'type'		=> 'text'

				),

				array(

					$title		=> __('Tab Priority', $this->plugin_slug ),

					'id'		=> $this::$plugin->p('option_prefix') . 'tab_priority',

					'type'		=> 'text'

				),

				array(

					'type'		=> 'sectionend',

					'id'		=> $this::$plugin->p('option_prefix') . 'tab_settings'

				),

			);

	}

	/**
	* Manually save WC options for old versions
	* @since     1.0.5
	* @return    null
	*/

	function update_old_wc_options() {

		update_option( $this::$plugin->p('option_prefix') . 'publisher_key', sanitize_text_field( $_POST[$this::$plugin->p('option_prefix') . 'publisher_key'] ) );

		update_option( $this::$plugin->p('option_prefix') . 'faqs_scoring_key', sanitize_text_field( $_POST[$this::$plugin->p('option_prefix') . 'scoring_key'] ) );

		$use_antispam = ( sanitize_text_field( $_POST[$this::$plugin->p('option_prefix') . 'use_antispam'] ) == 1 ? 'yes' : 'no');

		update_option( $this::$plugin->p('option_prefix') . 'use_antispam', $use_antispam );

		update_option( $this::$plugin->p('option_prefix') . 'tab_title', sanitize_text_field( $_POST[$this::$plugin->p('option_prefix') . 'tab_title'] ) );

		update_option( $this::$plugin->p('option_prefix') . 'tab_priority', sanitize_text_field( $_POST[$this::$plugin->p('option_prefix') . 'tab_priority'] ) );

	}

	/**
	* Return columns for the post table of this post type
	* @since     1.0.6
	* @return    array    Array of the columns
	*/

	function set_custom_edit_columns($columns) {

	    $columns = array(

		'cb' => '<input type="checkbox" />',

		'title' => __( 'Question', $this->plugin_slug ),

		'asker' => __( 'Asker', $this->plugin_slug ),

		'asker_email' => __( 'Asker Email', $this->plugin_slug ),

		'comments' => __( 'Answers', $this->plugin_slug ),

		'date' => __( 'Date Asked', $this->plugin_slug )

		);

	    return $columns;

	}

	/**
	* Echo the columns' content
	* @since     1.0.6
	* @return    null
	*/

	function custom_column( $column, $post_id ) {

	    switch ( $column ) {

	        case 'asker' :

	        	echo get_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_author_name', true );

	            break;

	        case 'asker_email' :

	            echo get_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_author_email', true );

	            break;

	    }

	}

	/**
	* Add meta boxes for this post type
	* @since     1.0.6
	* @return    null
	*/

	function meta_boxes() {

		add_meta_box( $this::$plugin->p('post_type') . '_product', __( 'FAQ Details', $this->plugin_slug ), array( $this, 'metabox' ), $this::$plugin->p('post_type'), 'normal', 'high' );

	}

	/**
	* Meta box content
	* @since     1.0.6
	* @return    null
	*/

	function metabox($post) {

		//get current value

		$current_product = get_post_meta( $post->ID, '_' . $this::$plugin->p('post_type') . '_product', true );

		//get current value

		$category = get_post_meta( $post->ID, '_' . $this::$plugin->p('post_type') . '_categories', true );

		$author_name = get_post_meta( $post->ID, '_' . $this::$plugin->p('post_type') . '_author_name', true );

		$author_email = get_post_meta( $post->ID, '_' . $this::$plugin->p('post_type') . '_author_email', true );

		//nonce

		wp_nonce_field( plugin_basename( __FILE__ ), $this::$plugin->p('post_type') . 'meta_nonce' );

		//get all products

		$args = array(

			'post_type' => 'product',

			'numberposts' => -1

			);

		$products = get_posts( $args );

		if( $products ) {

			//Product relationship label

			echo '<p><label for="_' . '_' . $this::$plugin->p('post_type') . '_product">';

			_e( 'Product this question is shown on.', $this->plugin_slug );

			echo '</label></p>';

			//Product relationship select

			echo '<p><select name="' . '_' . $this::$plugin->p('post_type') . '_product">';

			echo '<option ' . selected( $current_product, '0', false ) . ' value="0">' . __( 'No product selection (use category only)', $this->plugin_slug ) . '</option>';

			echo '<option ' . selected( $current_product, 'all', false ) . ' value="' . 'all' . '">' . __( 'All products', $this->plugin_slug ) . '</option>';

			foreach($products as $product) {

				echo '<option ' . selected( $current_product, $product->ID, false ) .' value="' . $product->ID . '">' . $product->post_title . '</option>';

			}

			echo '</select></p>';

		}

		//otherwise, just say there are no products

		else {

			echo '<p>';

			_e( 'No Products Found', $this->plugin_slug );

			echo '</p>';

		}

		//Product relationship label

			echo '<p><label for="_' . '_' . $this::$plugin->p('post_type') . '_categories">';

			_e( 'Categories this question is shown on.', $this->plugin_slug );

			echo '<br />';

			_e( 'If changed from default, this will display on any products in specified categories in addition to the product selection chosen above.', $this->plugin_slug );

			echo '</label></p>';

			$args = array(

				'orderby'            => 'NAME',

				'show_option_all'    => 'All categories',

				'show_option_none'    => 'Default - no category filters',

				'order'              => 'ASC',

				'show_count'         => 0,

				'hide_empty'         => 1,

				'echo'               => 1,

				'selected'           => $category !== FALSE ? $category : -1,

				'hierarchical'       => 0, 

				'name'               => '_' . $this::$plugin->p('post_type') . '_categories',

				'id'                 => '_' . $this::$plugin->p('post_type') . '_categories',

				'class'              => 'postform',

				'depth'              => 0,

				'tab_index'          => 0,

				'taxonomy'           => 'product_cat',

				'hide_if_empty'      => false,

				'walker'             => ''

			);

			wp_dropdown_categories( $args );

			echo '</p>';

		//question author info

		echo '<p>';

		_e( 'It is best to leave the fields below blank if you are adding a FAQ manually.', $this->plugin_slug );

		echo '</p>';

		//author's name

		echo '<p><label for="_' . $this::$plugin->p('post_type') . '_author_name">';

		_e('Author: ', $this->plugin_slug );

		echo '</label>';

		echo '<input type="text" name="_' . $this::$plugin->p('post_type') . '_author_name" value="' . $author_name . '"/></p>';

		//author's email

		echo '<p><label for="_' . $this::$plugin->p('post_type') . '_author_email">';

		_e('Author Email: ', $this->plugin_slug );

		echo '</label>';

		echo '<input type="email" name="_' . $this::$plugin->p('post_type') . '_author_email" value="' . $author_email . '"/></p>';

	}

	/**
	* Save meta info
	* @since     1.0.6
	* @param    int    $post_id    the post id of the currently saving post
	* @return    null
	*/

	function save_meta($post_id) {

		// First we need to check if the current user is authorised to do this action. 

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;

		}

		// Secondly we need to check if the user intended to change this value.

		if ( ! isset( $_POST[$this::$plugin->p('post_type') . 'meta_nonce'] ) || ! wp_verify_nonce( $_POST[$this::$plugin->p('post_type') . 'meta_nonce'], plugin_basename( __FILE__ ) ) ) {

      		return;

      	}

      	$author_name = sanitize_text_field( $_POST['_' . $this::$plugin->p('post_type') . '_author_name'] );

      	$author_email = sanitize_text_field( $_POST['_' . $this::$plugin->p('post_type') . '_author_email'] );

      	$product = sanitize_text_field( $_POST['_' . $this::$plugin->p('post_type') . '_product'] );

      	$category = intval( $_POST['_' . $this::$plugin->p('post_type') . '_categories'] );

      	if($author_name) update_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_author_name', $author_name );

      	if($author_email) update_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_author_email', $author_email );

		if( isset( $product ) ) {

			

			update_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_product', $product );

		}

		else { 

			

			delete_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_product' );

		}

		if( isset( $category ) && $category >= 0) update_post_meta( $post_id, '_' . $this::$plugin->p('post_type') . '_categories', $category );

	}

	/**
	* Filter the comment text on the edit screen
	* to be more sensible
	* @since     1.0.6
	* @return    object full translations object
	*/

	function filter_gettext( $translated, $original, $domain ) {

		remove_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );

		if( ( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == $this::$plugin->p('post_type') ) || ( isset($_REQUEST['post']) && get_post_type( $_REQUEST['post'] ) == $this::$plugin->p('post_type') ) ) {

			$strings = array(

			__('Comments', $this->plugin_slug ) => __('Answers', $this->plugin_slug ),

			__('Add comment', $this->plugin_slug ) => __('Add answer', $this->plugin_slug ),

			__('Add Comment', $this->plugin_slug ) => __('Add Answer', $this->plugin_slug ),

			__('Add new Comment', $this->plugin_slug ) => __('Add new Answer', $this->plugin_slug ),

			__('No comments yet.', $this->plugin_slug ) => __('No answers yet.', $this->plugin_slug ),

			__('Show comments', $this->plugin_slug ) => __('Show answers', $this->plugin_slug ),

			__('No more comments found.', $this->plugin_slug ) => __('No more answers found.', $this->plugin_slug )

			);

			if ( isset( $strings[$original] ) ) {

				$translations = get_translations_for_domain( $domain );

				$translated = $translations->translate( $strings[$original] );

			}

		}

		add_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );

		return $translated;

	}

	/**
	* Create a link to view the FAQ
	* from the edit screen
	* @since     1.0.6
	* @deprecated 2.0
	* @return    null
	*/

	function view_link() {

		global $post;

		if( $post->post_status == 'publish') {

			$link = get_permalink( $post->ID );

			?>

			<div class="inside">

				<div id="edit-slug-box" class="hide-if-no-js">

					<strong><?php _e( 'FAQ Link:', $this->plugin_slug ); ?></strong>

			<span id="sample-permalink" tabindex="-1"><?php echo $link; ?></span>

			<span id="view-post-btn"><a target="_blank" href="<?php echo $link; ?>" class="button button-small"><?php _e( 'View', $this->plugin_slug ); ?></a></span>

				</div>

			</div>

			<?php

		}

	}

}