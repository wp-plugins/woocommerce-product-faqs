<?php
/**
 * Plugin Name.
 *
 * @package   WooCommerce_FAQs
 * @author    Josh Levinson <joshalevinson@gmail.com>
 * @license   GPL-2.0+
 * @link      http://joshlevinson.me
 * @copyright 2014 Josh Levinson
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-woocommerce-product-faqs-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package WooCommerce_FAQs
 * @author  Your Name <email@example.com>
 */
class WooCommerce_FAQs {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '2.0.4';

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
	public static $plugin_slug = 'woocommerce-faqs';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Path of the plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public static $path = 'woocommerce-product-faqs/woocommerce-product-faqs.php';

	/**
	 * Slug of the plugin screen.
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public static $plugin_screen_hook_suffix = 'woocommerce_faqs';

	/**
	 * Prefix for this plugin's options
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public static $option_prefix = 'woocommerce_faqs_';

	/**
	 * Slug of the plugin's post type.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public static $post_type = 'woo_faq';

	/**
	 * Location of the recaptcha library.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public static $antispam_lib = 'includes/ayah/ayah.php';

	/**
	 * Antispam class instance
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $antispam_class = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		//do woo actions
		$this->woocommerce_actions();

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1000 );

		//add ajax for approving faqs quickly from the post row
		add_action('wp_ajax_approve_woo_faq', array( $this, 'approve_woo_faq' ) );

		//register faq post type
		add_action( 'init', array( $this, 'register_pt' ) );

		//filter the redirect to take us back to the product after an admin has replied to a FAQ
		add_filter('comment_post_redirect', array( $this, 'redirect_comment_form' ), 10, 2);

		//action for notifying asker about posted answer
		add_action( 'wp_insert_comment', array( $this, 'answer_posted'), 99, 2);

		//woocommerce tab titles and priorities
		add_filter( self::$option_prefix . 'tab_title', array( $this, 'tab_title' ) );
		add_filter( self::$option_prefix . 'tab_priority', array( $this, 'tab_priority' ) );

		add_filter( 'post_type_link', array( $this, 'faq_link_filter' ), 1, 3 );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {

		return self::$plugin_slug;

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
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
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 * Checks WC compat, registers PT's and flushes rewrite rules.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		//dependencies
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		$wc_version = get_woocommerce_version();
		 
		if (!is_plugin_active( 'woocommerce/woocommerce.php' ) || !meets_min_wc_version()  )
		{
			deactivate_plugins('woocommerce-product-faqs/woocommerce-faqs.php');

			exit( 'Hey there! This plugin requires <a target="_blank" href="'. admin_url( 'plugin-install.php?tab=search&s=woocommerce' ) . '">WooCommerce</a> 1.6.6 or greater (and prefers the latest version - 2.x!). Please install and/or active it first!' );

		}

		self::register_pt();

		flush_rewrite_rules();

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = self::$plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if( is_product() ) {

			wp_enqueue_style( self::$plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), $this->get_version() );

		}

	}

	/**
	 * Get the plugin version
	 *
	 * @since    2.0
	 *
	 * @return    int    Plugin version
	 */
	public function get_version(){

		return self::VERSION;

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if( is_product() ) {

			wp_enqueue_script( self::$plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), $this->get_version(), true );

			$localize = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'spinner' => admin_url('images/wpspin_light.gif'),
				);

			wp_localize_script( self::$plugin_slug . '-plugin-script', 'spinner', admin_url('images/wpspin_light.gif') );

			//if we are view/previewing a faq, localize that so it is available to our javascript

			if(isset($_GET['faq-view']) || isset($_GET['faq-preview'])){

				$faq_to_highlight = ( isset( $_GET['faq-view'] ) ? intval( $_GET['faq-view'] ) : intval( $_GET['faq-preview'] ) );

				$localize['faq_highlight'] = $faq_to_highlight;

				//and localize the color with a filter, so it can be changed either by user or maybe later as a settings option

				$localize['faq_highlight_color'] = apply_filters( self::$option_prefix . 'front_faq_highlight_color', '#9ED1D6' );

			}

			wp_localize_script( self::$plugin_slug . '-plugin-script', self::$option_prefix . 'data', $localize );

		}	

	}

	/**
	 * Returns the url of the plugin's root folder
	 * Stole this from Gravity Forms
	 *
	 * @since    1.0.0
	 */
    public static function get_base_url(){

        $folder = basename( dirname( __FILE__ ) );

        return plugins_url( $folder );

    }

    /**
	 * Returns the physical path of the plugin's root folder
	 * Stole this from Gravity Forms
	 *
	 * @since    1.0.0
	 */
    public static function get_base_path(){

        $folder = basename( dirname( __FILE__ ) );

        return WP_PLUGIN_DIR . "/" . $folder;

    }

    /**
	 * Registers our post type
	 * cuz WP doesn't support custom comment types :(
	 *
	 * @since    1.0.0
	 */
	static function register_pt(){

		add_rewrite_rule( "^product-faq/([^/]+)/?", 'index.php?post_type=' . self::$post_type . '&book=$matches[1]', 'top' );

		$labels = array(

	    'name' => __( 'WooFAQs', self::$plugin_slug ),

	    'singular_name' => __( 'WooFAQ', self::$plugin_slug ),

	    'add_new' => __( 'Add New', self::$plugin_slug ),

	    'add_new_item' => __( 'Add New WooFAQ', self::$plugin_slug ),

	    'edit_item' => __( 'Edit WooFAQ', self::$plugin_slug ),
	    
	    'edit_item' => __( 'Edit WooFAQ', self::$plugin_slug ),

	    'new_item' => __( 'New WooFAQ', self::$plugin_slug ),

	    'all_items' => __( 'All WooFAQs', self::$plugin_slug ),

	    'view_item' => __( 'View WooFAQ', self::$plugin_slug ),

	    'search_items' => __( 'Search WooFAQs', self::$plugin_slug ),

	    'not_found' =>  __( 'No WooFAQs found', self::$plugin_slug ),

	    'not_found_in_trash' => __( 'No WooFAQs found in Trash', self::$plugin_slug ),

	    'parent_item_colon' => '',

	    'menu_name' => __( 'WooFAQs', self::$plugin_slug )

	  );

		$args = array(

	    'labels' => $labels,

	    'public' => true,

	    'publicly_queryable' => true,

	    'show_ui' => true, 

	    'show_in_menu' => true, 

	    'query_var' => true,

	    'rewrite' => array('slug' => 'product-faq', 'with_front' => false ),

	    'capability_type' => 'post',

	    'has_archive' => false, 

	    'hierarchical' => false,

	    'menu_position' => null,

	    'supports' => array( 'title', 'editor', 'comments', 'page-attributes')

	  );

	  register_post_type( self::$post_type, $args );

	}

	/**
	 * Returns the convoluted permalink to the 
	 * possible products this faq can lie on.
	 *
	 * @since    2.0
	 * @return   string		product link
	 */
	function faq_link_filter( $post_link, $post = 0, $leavename = FALSE ){

		//if we don't have a post object, bail
		if(!$post) return $post_link;

		//if this isn't the right post type, bail
		if( $post->post_type != self::$post_type ) {

			return $post_link;

		}

		//get the product this faq is associated with
		$product = get_post_meta( $post->ID, '_' . self::$post_type . '_product', true );

		$category = (int)get_post_meta( $post->ID, '_' . self::$post_type . '_categories', true );

		//by default, we are "viewing" the faq; we preview unpublished faqs
		$view = $post->post_status == 'publish' ? 'view' : 'preview';

		//if there's nothing in the post meta, we haven't assigned yet and don't have a valid url.
		if( !$product && !$category ) return '#product-not-assigned-yet';

		//if the faq is for all products, just use the product archive (like /shop)
		if( $product == 'all' ) return get_post_type_archive_link( 'product' );

		if( $category ) return get_term_link( $category, 'product_cat' );

		//if we're here, we should have a valid product ID
		$product = get_permalink( $product );

		//so return the link of that product, with the highlighted faq query string and tab hash
		return $product . "?faq-$view=$post->ID#tab-faqs";

	}

	/**
	 * Adds our FAQs tab to Woo's product tabs
	 *
	 * @since    1.0.0
	 */
	function faq_tab($tabs) {

		$tabs['faqs'] = array(

			//we allow for filtering based on our WC options
			'title' => __( apply_filters( self::$option_prefix . 'tab_title', 'FAQs' ), self::$plugin_slug ),

			'priority' => apply_filters( self::$option_prefix . 'tab_priority', 100 ),

			'callback' => array( $this, 'faq_tab_content' )

		);

		return $tabs;

	}

	/**
	 * requires the antispam lib
	 * but only if it's not already included elsewhere
	 *
	 * @since    1.0.0
	 */
	function require_antispam(){

		//include the AYAH library
		require_once( self::$antispam_lib );

		// Instantiate the AYAH object.
		$this->ayah = new AYAH(
			//we are bypassing AYAH's sample _config.php file,
			//and instead getting the keys from the options table
			//and instantiating the object with them
			array(

				'publisher_key' => get_option( self::$option_prefix . 'publisher_key' ),

				'scoring_key'	=> get_option( self::$option_prefix . 'scoring_key' )

				)

			);

	}

	/**
	 * Displays the content for the FAQs tab
	 * the form and the faq loop
	 *
	 * @since    1.0.0
	 */
	function faq_tab_content() {
		
		$html = '';

		//the faqs loop
		include( plugin_dir_path(__FILE__) . '/views/loop-faqs.php' );

		$disable_ask = get_option( self::$option_prefix . 'disable_ask', false );

		if( !$disable_ask || $disable_ask === 'no' ){
			//the faq form
			include( plugin_dir_path(__FILE__) . '/views/faq-form.php' );

		}
		
	}

	/**
	 * Returns 'error' for the form inputs 
	 * whose input resulted in an error
	 * or just an empty string
	 *
	 * @since    1.0.0
	 */
	function should_display_error( $result, $key ) {

		if( isset( $result['errors'] ) && is_array( $result['errors'] ) ) {

			if( array_key_exists( $key, $result['errors'] ) ) {

				return 'error';

			}

		}

		return '';

	}

	/**
	 * Filters the comments template to use our template
	 *
	 * @since    1.0.0
	 */
	function comments_template_loader(){

		$file = '/comments.php';

		if ( file_exists( $this->get_base_path() . '/includes' . $file ) )

			return( $this->get_base_path() . '/includes' . $file );

		elseif ( file_exists( STYLESHEETPATH . $file ) )

			return( STYLESHEETPATH . $file );

		elseif ( file_exists( TEMPLATEPATH . $file ) )

			return( TEMPLATEPATH . $file );

		else // Backward compat code will be removed in a future release

			return( ABSPATH . WPINC . '/theme-compat/comments.php' );

	}

	/**
	 * Handle the submission of a FAQ
	 *
	 * @since    1.0.0
	 */
	function handle_submission( $use_antispam = null ) {

		//this $post variable is for the PRODUCT
		global $post;

		//if this function was called without specifiying whether or not to
		//use antispam, we get that value here
		if( empty( $use_antispam ) ) $use_antispam = $this->use_antispam();

		//create errors and result arrays
		$errors = array();

		$result = array();

		//put post data into an array
		if( isset( $_POST['faq_author_name'] ) ) $input['faq_author_name'] = sanitize_text_field( $_POST['faq_author_name'] );

		if( isset( $_POST['faq_author_email'] ) ) $input['faq_author_email'] = sanitize_email( $_POST['faq_author_email'] );

		if( isset( $_POST['faq_content'] ) ) $input['faq_content'] = esc_textarea( stripslashes( $_POST['faq_content'] ) );

		//very simple validation for content, name, and email
		//TODO - make this validation more stringent
		if( empty( $input['faq_content'] ) ) {

			$errors['faq_content'] = __('Please enter a question!', self::$plugin_slug);

		}

		if( empty($input['faq_author_name'] ) ) {

			$errors['faq_author_name'] = __('Please enter your name!', self::$plugin_slug);

		}

		if( empty( $input['faq_author_email'] ) || ( !empty( $input['faq_author_email'] ) && !is_email( $input['faq_author_email'] ) ) ) {

			$errors['faq_author_email'] = __('Please enter a valid email!', self::$plugin_slug);

		}

		//antispam handler, with or without AYAH enabled
		$result = $this->handle_antispam();

		//if antispam returned a error type result, asker failed antispam check
		if( $result['type'] == 'error' ) {

			$errors[] = $result['message'];

		}

		//passed all checks
		if( empty( $errors ) ) {

			$post_info = array(

				'post_title' => __('Question for ', self::$plugin_slug) . $post->post_title,

				'post_content' => wp_strip_all_tags( $input['faq_content'] ),

				'post_type' => self::$post_type,

				'post_status' => 'pending',

				'comment_status'=>'open'

			);

			//create the post
			$post_id = wp_insert_post( $post_info );

			//add post meta
			update_post_meta( $post_id, '_' . self::$post_type . '_product', $post->ID );

			update_post_meta( $post_id, '_' . self::$post_type . '_author_name', $input['faq_author_name'] );

			update_post_meta( $post_id, '_' . self::$post_type . '_author_email', $input['faq_author_email'] );

			//data for elsewhere (like the notifications)
			$input['product_title'] = $post->post_title;

			$input['question_title'] = $post_info['post_title'];

			$input['question_content'] = $post_info['post_content'];

			$input['post_id'] = $post_id;

			$input['product_author_id'] = $post->post_author;

			//result for the form (success)
			$result['type'] = 'success';

			$result['message']= __( 'FAQ Successfully Posted. Your question will be reviewed and answered soon!', self::$plugin_slug );

			//send the notification to the answerer
			$this->send_notifications( 'answerer', $input );

		} else {

			//result for the form (error)
			$result['type'] = 'error';

			$result['errors'] = $errors;

		}

		return $result;

	}

	/**
	 * Handle the recaptcha submission
	 *
	 * @since    1.0.0
	 */
	function handle_antispam() {

		//we need the result array one way or the other
		$result = array();

		//check if we are using 'antispam'
		if( $this->use_antispam() ) {

			$this->require_antispam();

	        // Use the AYAH object to get the score.
	        $score = $this->ayah->scoreResult();

	        // Check the score to determine what to do.
	        //if score is boolean true, the user passed
	        if ( $score ) {

                $result['type'] = 'success';

	        }

	        //otherwise, they failed
	        else {

                $result['type'] = 'error';

				$result['message'] = __('The antispam wasn\'t entered correctly. Go back and try it again.', self::$plugin_slug );

	        }

		}

		//if we aren't, still use honeypot to check
		else {

			//this is a honeypot!!!
			//if primary_email is set/not empty, we've failed the honeypot
			if( isset( $_POST['primary_email'] ) && $_POST['primary_email'] != '' ) {
				
				$result['type'] = 'error';
				
				$result['message'] = __('You\'ve triggered our anti-spam filter. If you have a form-filling application/extension, please disable it temporarily.', self::$plugin_slug );
			}

			else {

				$result['type'] = 'success';

			}

		}

		if( $result['type'] == 'error' ) {

			//allow the error message to be filtered
			apply_filters( self::$option_prefix . 'antispam_error_message', $result['message'], $_POST );

		}

		return $result;

	}

	/**
	 * Handler for building and sending out
	 * asker and answerer emails
	 * TODO - make this behavior based off of the product author instead of admin
	 *
	 * @since    1.0.0
	 */
	function send_notifications( $to_whom = false, $post_data = null ) {
		//required info for both email
		//TO DO - make this actually used by the function to force requirements
		//especially when filter addition is complete, so this function will fail w/o required data
		$answerer_email_required = apply_filters( self::$option_prefix . 'answerer_email_required', array( 'question_title', 'faq_author_name', 'product_title', 'question_content' ), $post_data );

		$asker_email_required = apply_filters( self::$option_prefix . 'asker_email_required', array( 'question_title', 'product_title', 'post_id' ), $post_data );

		add_filter( self::$option_prefix . 'answerer_email', array( $this, 'answerer_email' ) );

		//who to send the emails to
		if( isset( $post_data['product_author_id'] ) ) {

			$author = $post_data['product_author_id'];

		}
		else if( isset( $post_data['product_id']) ) {

			$author = get_post_field( 'post_author', $post_data['product_id'] );

		}

		$answerer_email = apply_filters( self::$option_prefix . 'answerer_email', get_the_author_meta( 'user_email', $author ), $post_data );

		$from_name = apply_filters( self::$option_prefix . 'from_name', get_option( self::$option_prefix . 'from_name', false ), $post_data );		

		$asker_email = apply_filters( self::$option_prefix . 'asker_email', $post_data['faq_author_email'], $post_data );

		$to = '';

		$subject = '';

		$message = '';

		$headers = array();

		$success = false;

		//we need to know who to send to!
		if($to_whom){

			//filter wp mail to html
			add_filter( 'wp_mail_content_type' , array( $this, 'set_html_content_type' ) );

			$from = '';
			//taken from wp_mail()
			if( $from_name) {

				$from .= 'From: ' . $from_name;

			}
			else{

				$from .= 'From: WordPress';

			}

			$from .= ' <';

			if( $set_email = $this->answerer_email('') ) {

				$from .= $set_email;

			}
			else {

				$from .= get_bloginfo( 'admin_email' );

			}

			$from .= '>';

			$headers[] = $from;

			switch( $to_whom ) {

				case 'answerer':

					$headers[] = 'Reply-To: ' . $post_data['faq_author_name'] . ' <' . $post_data['faq_author_email'] . '>';

					$to = $answerer_email;

					$subject = __('New ', self::$plugin_slug) . $post_data['question_title'];

					//allow the subject to be filtered
					$subject = apply_filters( self::$option_prefix . 'answerer_email_subject', $subject, $post_data );

					$message = '<p>' . $post_data['faq_author_name'] . __( ' asked the following question about ', self::$plugin_slug ) . $post_data['product_title'] . ':</p>';

					$message .= '<p>"' . $post_data['question_content'] . '"</p>';

					$message .= '<p>' . __( 'The question can be administered ', self::$plugin_slug) . '<a href="';

					$message .= admin_url( '/edit.php?post_type=' ) . self::$post_type . '&highlight=' . $post_data['post_id'].'">'. __('here', self::$plugin_slug) . '.</a>';

					$message .= '<p>' . __( 'If the question asker left a valid email, you can reply directly to them from this email.
						Note this will not post the reply on your website. ', self::$plugin_slug) . '</p>';

					//allow the final message to be filtered
					$message = apply_filters( self::$option_prefix . 'answerer_email_message', $message, $post_data );

					break;

				case 'asker':

					$to = $asker_email;

					$subject = __('Response to ', self::$plugin_slug) . $post_data['question_title'];

					//allow the subject to be filtered
					$subject = apply_filters( self::$option_prefix . 'asker_email_subject', $subject, $post_data );

					$message = '<p>' . __('A reply to your question about ', self::$plugin_slug) . $post_data['product_title'] . __(' has been posted!', self::$plugin_slug) . '</p>';

					$product_link = get_permalink( $post_data['product_id'] );

					$message .= '<p>' . __('View the answer', self::$plugin_slug) . ' <a href="' . add_query_arg( 'faq-view', $post_data['post_id'] . '#tab-faqs', $product_link ) . '">'. __('here', self::$plugin_slug) . '</a></p>';

					//allow the final message to be filtered
					$message = apply_filters( self::$option_prefix . 'asker_email_message', $message, $post_data );

					break;

			}
			if( !empty( $to ) ) {
				
				$success = wp_mail( $to, $subject, $message, $headers );

			}

			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		}

		//we may want to check on this later
		return $success;

	}

	/**
	 * Filter WP emails to be HTML (for the ones we send)
	 *
	 * @since    1.0.0
	 */
	function set_html_content_type() {

		return 'text/html';

	}

	/**
	 * Redirect the answerer back to the product page
	 * after they interact with the FAQ section
	 *
	 * @since    1.0.0
	 */
	function redirect_comment_form( $location, $comment ) {

		$faq = $comment->comment_post_ID;

		if( $product = get_post_meta( $faq,'_'. self::$post_type .'_product',true ) ) {

			$location = get_permalink( $product ) . '#tab-faqs';

		}

		return $location;

	}

	/**
	 * Our comment (answer) loop
	 *
	 * @since    1.0.0
	 */
	function comment_callback( $comment, $args, $depth ) {

		$GLOBALS['comment'] = $comment;

		$comment_count = (int)$comment->comment_count;

		?> 

		<div <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>" class="clearfix">

			<div id="comment-<?php comment_ID(); ?>" class="comment-body clearfix">

				<div class="wrapper">

					<?php if ( $comment->comment_approved == '0' ) : ?>

					<em><?php echo theme_locals( "your_comment" ) ?></em>

					<?php endif; ?>

					<div class="extra-wrap">

					<span><?php _e( 'A: ', self::$plugin_slug ); ?><?php echo get_comment_text(); ?></span>

					<?php

					/*
					* old, maybe use in the future for logged in customers

					if ( $comment_count <= 1 ) {
						

					}

					else {

						$comment_author = (int)$comment->user_id;

						$question_author = (int)get_post_field( 'post_author', (int)$comment->comment_post_ID );

						if( $comment_author == $question_author ) {

							_e( 'Asker: ', self::$plugin_slug );

						}

						else {
							
							_e( 'Answerer: ', self::$plugin_slug );

						}

					}
					*/

					?>

					</div>

					<div class="comment-author vcard">

					<?php echo get_avatar( $comment->comment_author_email, 65 ); ?>

					<?php printf( '<span class="author">— %1$s</span>', get_comment_author_link() ); ?>

					</div>

				</div>

				<div class="wrapper">

				<div class="reply">

				</div>

				<div class="comment-meta commentmetadata"><?php printf( '%1$s', get_comment_date( 'F j, Y' ) ); ?></div>

				</div>

			</div>

		</div>

		<?php
	}

	/**
	 * Notifies the visitor/customer that an 
	 * answer has been posted to their question
	 *
	 * @since    1.0.0
	 */
	function answer_posted( $comment_id, $comment_object ) {

		$post_id = (int)$comment_object->comment_post_ID;

		if( get_post_type( $post_id ) == self::$post_type ) {

			$product_id = get_post_meta( $post_id, '_' . self::$post_type . '_product', true );

			$post_data = array(

				'post_id'=> $post_id,

				'question_title'=> get_the_title( $post_id ),

				'product_title' => get_the_title( $product_id ),

				'product_id'=> $product_id,

				'faq_author_email'=>get_post_meta($post_id, '_' . self::$post_type . '_author_email', true )

				);

			$this->send_notifications( 'asker', $post_data );

		}

	}

	/**
	 * Turns the use_antispam option into a boolean
	 *
	 * @since    1.0.0
	 */
	function use_antispam(){

		//this gets the 'yes' or 'no' stored in the option and turns it into a boolean
		return ( get_option( self::$option_prefix . 'use_antispam' ) == 'yes' ? true : false );

	}

	/**
	 * Ajax handler for quickly approving faqs
	 *
	 * @since    1.0.0
	 */
	function approve_woo_faq(){

		//the posted post id
		$post_id = $_POST['post_id'];

		//initialize our results array
		$result = array();

		//ensure the current user can edit the current post (faq)
		//todo: move this cap to product author
		if ( !current_user_can( 'edit_post', $post_id ) ) {

			$result['type'] = 'error';

			$result['message'] = __('Current user does not have permissions over this post', self::$plugin_slug );

			echo json_encode($result);

			die();

		}

		//verify the posted nonce
		if ( !wp_verify_nonce( $_POST['nonce'], 'publish-post_' . $post_id ) ) {

	        $result['type'] = 'error';

			$result['message'] = __( 'Cheatin, eh?', self::$plugin_slug );

			echo json_encode($result);

			die();

	    }

	    //if we got this far, publish the post and generate the success
	    wp_publish_post($post_id);

	    $result['type'] = 'success';

	    $result['message'] = __('Approved...reloading now.', self::$plugin_slug );

	    $result['redirect'] = admin_url('edit.php?post_type=' . self::$post_type);

	    echo json_encode($result);

		die();

	}

	/**
	 * Add actions to WC settings based on WC version
	 *
	 * @since     1.0.4
	 *
	 * @return    null
	 */
	function woocommerce_actions(){

		$wc_version = get_woocommerce_version();

		if( $wc_version < 2.0 ){

			add_action( 'woocommerce_product_tabs', array( $this, 'woocommerce_faqs_tab' ), apply_filters( self::$option_prefix . 'tab_priority', 40 ) );

			add_action( 'woocommerce_product_tab_panels', array($this, 'faq_tab_content' ), apply_filters( self::$option_prefix . 'tab_priority', 40 ) );

		}

		else{

			//filter woo's tabs to add FAQs
			add_filter( 'woocommerce_product_tabs', array( $this, 'faq_tab' ) );

		}

	}

	/**
	 * Title tab for WC < 2.0
	 *
	 * @since     1.0.5
	 *
	 * @return    null
	 */
	function woocommerce_faqs_tab(){ ?>
		<li class="faqs_tab"><a href="#tab-faqs"><?php _e( apply_filters( self::$option_prefix . 'tab_title', 'FAQs' ), self::$plugin_slug ); ?></a></li>
		<?php
	}

	/**
	 * Filters the tab title if the setting is set in the Dashboard.
	 *
	 * @since    1.0.9
	 *
	 * @param    string    $title    the title before this filter
	 *
	 * @return    string    $title    the title after this filter
	 */
	function tab_title($title){

		$user_title = get_option( self::$option_prefix . 'tab_title' );

		if( $user_title ) {

			return $user_title;

		}

		else{

			return $title;

		}

	}

	/**
	 * Filters the tab priority if the setting is set in the Dashboard.
	 *
	 * @since    1.0.9
	 *
	 * @param    string    $priority    the priority before this filter
	 *
	 * @return    string    $priority    the priority after this filter
	 */
	function tab_priority($priority){

		$user_priority = get_option( self::$option_prefix . 'tab_priority' );

		if( $user_priority ) {

			return $user_priority;

		}

		else{

			return $priority;

		}

	}

	/**
	 * Filters the answerer email in case it was set in the Dashboard.
	 *
	 * @since    1.0.9
	 *
	 * @param    string    $email    the email before this filter
	 *
	 * @return    string    $email    the email after this filter
	 */

	function answerer_email($email){

		$e = get_option( self::$option_prefix . 'answerer_email' );

		if($e) return $e;

		return $email;

	}

	function p($var){
		return self::$$var;
	}

}
