<?php
/**
 * WooCommerce Product FAQs
 *
 * @package   WooCommerce Product FAQs
 * @author    Josh Levinson <josh@joshlevinson.me>
 * @license   GPL-2.0+
 * @link      http://redactweb.com/
 * @copyright 2013 Josh Levinson
 */

/**
 * Plugin class.
 *
 *
 * @package WooCommerce Product FAQs
 * @author  Josh Levinson <josh@joshlevinson.me>
 */
class WooCommerce_FAQs {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.6';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'woocommerce-faqs';

	/**
	 * Path of the plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $path = 'woocommerce-product-faqs/woocommerce-faqs.php';

	/**
	 * Prefix for this plugin's options
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $option_prefix = 'woocommerce_faqs_';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 * pretty sure we don't need this...
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = 'woocommerce_faqs';

	/**
	 * Slug of the plugin's post type.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $post_type = 'woo_faq';

	/**
	 * Settings array
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $settings;

	/**
	 * Location of the recaptcha library.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $antispam_lib = 'includes/ayah/ayah.php';

	/**
	 * Antispam class instance
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $antispam_class = null;
	


	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add the options page and menu item.
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'admin_menu' ) );
		add_action( 'woocommerce_settings_tabs_faqs', array( $this, 'display_settings' ) );

		$this->woocommerce_actions();

		// Load admin JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		//not used as of 1.0.0, but keep it in for later
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1000 );

		//register faq post type
		add_action( 'init', array($this, 'register_pt' ) );

		//filter the redirect to take us back to the product after an admin has replied to a FAQ
		add_filter('comment_post_redirect', array( $this, 'redirect_comment_form' ), 10, 2);

		//filter post row actions to add reply tab
		add_filter( 'post_row_actions', array( $this,'action_row'), 10, 2);

		//filter for preview post link to link to product
		add_filter( 'preview_post_link', array( $this, 'preview_link' ) );

		//action for notifying asker about posted answer
		add_action( 'wp_insert_comment', array( $this, 'answer_posted'), 99, 2);

		//add ajax for approving faqs quickly from the post row
		add_action('wp_ajax_approve_woo_faq', array( $this, 'approve_woo_faq' ) );

		//post type icons
		add_action( 'admin_head', array( $this, 'post_type_icons' ) );

		//custom post table columns
		add_filter( 'manage_edit-'.$this->post_type.'_columns', array( $this, 'set_custom_edit_columns' ) );
		
		//custom post table columns content
		add_action( 'manage_'.$this->post_type.'_posts_custom_column' , array( $this, 'custom_column' ), 1, 2 );

		//meta boxes
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );

		//save meta
		add_action( 'save_post', array( $this, 'save_meta' ) );

		//filter for meta boxes' text
		add_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );

		add_action('edit_form_after_title', array( $this, 'view_link') );

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
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		//dependencies
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		$wc_version = get_woocommerce_version();
		 
		if (!is_plugin_active( 'woocommerce/woocommerce.php' ) || !meets_min_wc_version()  )
		{
			deactivate_plugins('woocommerce-product-faqs/woocommerce-faqs.php');

			exit( 'Hey there! This plugin requires <a target="_blank" href="'. admin_url( 'plugin-install.php?tab=search&s=woocommerce' ) . '">WooCommerce</a> 1.6.6 or greater (and prefers the latest version - 2.x!). Please install and/or active it first!' );

		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		// TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );

		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 * the filter that calls this function is commented out 
	 * as of 1.0.0, but this function should stay here for 
	 * reference for later use
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		//we aren't using the typical 'plugin' screen, so we don't need to hook this on our plugin's options screen
		/*if ( ! isset( $this->plugin_screen_hook_suffix ) ) {

			return;

		}
		*/

		$screen = get_current_screen();

		if ( $screen->id == $this->plugin_screen_hook_suffix ) {

			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );

		}

	}

	function post_type_icons() {
	    echo '<style type="text/css" media="screen">
	        #menu-posts-'.$this->post_type.' .wp-menu-image {
	            background: url('.$this->get_base_url().'/includes/images/icon.png) no-repeat 0 -32px !important;
	        }
		#menu-posts-'.$this->post_type.':hover .wp-menu-image, #menu-posts-'.$this->post_type.'.wp-has-current-submenu .wp-menu-image {
	            background-position:0 0 !important;
	        }
		#icon-edit.icon32-posts-'.$this->post_type.' {background: url('.$this->get_base_url().'/includes/images/full-32x32.png) no-repeat 0px 0px !important;}
	    </style>';
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		//we aren't using the typical 'plugin' screen, so we don't need to hook this on our plugin's options screen
		/*if ( ! isset( $this->plugin_screen_hook_suffix ) ) {

			return;

		}
		*/

		$screen = get_current_screen();

		//we need to load this script on the edit page for our post type
		if ( $screen->id == 'edit-' . $this->post_type ) {

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );

			wp_localize_script( $this->plugin_slug . '-admin-script', 'spinner', admin_url('images/wpspin_light.gif') );

			//if we are administering a faq, localize that so it is available to our javascript
			if(isset($_GET['highlight'])){

				wp_localize_script( $this->plugin_slug . '-admin-script', 'faq_highlight', $_GET['highlight']);

				//and localize the color with a filter, so it can be changed either by user or maybe later as a settings option

				wp_localize_script( $this->plugin_slug . '-admin-script', 'faq_highlight_color', apply_filters($this->option_prefix.'admin_faq_highlight_color','#9ED1D6'));

			}

		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if( is_product() ) {

			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );

		}

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if( is_product() ) {

			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), $this->version, true );

			wp_localize_script( $this->plugin_slug . '-plugin-script', 'ajaxurl', admin_url( 'admin-ajax.php' ) );

			wp_localize_script( $this->plugin_slug . '-plugin-script', 'spinner', admin_url('images/wpspin_light.gif') );

			//if we are view/previewing a faq, localize that so it is available to our javascript

			if(isset($_GET['faq-view']) || isset($_GET['faq-preview'])){

				$faq_to_highlight = (isset($_GET['faq-view']) ? $_GET['faq-view'] : $_GET['faq-preview']);

				wp_localize_script( $this->plugin_slug . '-plugin-script', 'faq_highlight', $faq_to_highlight);

				//and localize the color with a filter, so it can be changed either by user or maybe later as a settings option

				wp_localize_script( $this->plugin_slug . '-plugin-script', 'faq_highlight_color', apply_filters($this->option_prefix.'front_faq_highlight_color','#9ED1D6'));

			}

		}	

	}
	
	/**
	 * Returns the url of the plugin's root folder
	 * Stole this from Gravity Forms
	 *
	 * @since    1.0.0
	 */
    public static function get_base_url(){

        $folder = basename(dirname(__FILE__));

        return plugins_url($folder);

    }

    /**
	 * Returns the physical path of the plugin's root folder
	 * Stole this from Gravity Forms
	 *
	 * @since    1.0.0
	 */
    public static function get_base_path(){

        $folder = basename(dirname(__FILE__));

        return WP_PLUGIN_DIR . "/" . $folder;

    }

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function admin_menu($tabs) {

		$tabs['faqs']=__( 'FAQs', 'woocommerce' );

		return $tabs;

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	function admin_options() {

		include($this->get_base_path().'/views/admin.php');

	}
	/**
	 * Render the settings fields for the settings page.
	 *
	 * @since    1.0.0
	 */
	function display_settings(){

		woocommerce_admin_fields($this->settings);

	}

	/**
	 * Registers our post type
	 * cuz WP doesn't support custom comment types :(
	 *
	 * @since    1.0.0
	 */
	function register_pt(){

		$labels = array(

	    'name' => 'WooFAQs',

	    'singular_name' => 'WooFAQ',

	    'add_new' => 'Add New',

	    'add_new_item' => 'Add New WooFAQ',

	    'edit_item' => 'Edit WooFAQ',
	    
	    'edit_item' => 'Edit WooFAQ',

	    'new_item' => 'New WooFAQ',

	    'all_items' => 'All WooFAQs',

	    'view_item' => 'View WooFAQ',

	    'search_items' => 'Search WooFAQs',

	    'not_found' =>  'No WooFAQs found',

	    'not_found_in_trash' => 'No WooFAQs found in Trash', 

	    'parent_item_colon' => '',

	    'menu_name' => 'WooFAQs'

	  );

		$args = array(

	    'labels' => $labels,

	    'public' => false,

	    'publicly_queryable' => false,

	    'show_ui' => true, 

	    'show_in_menu' => true, 

	    'query_var' => true,

	    'rewrite' => array( 'slug' => 'woo_faq' ),

	    'capability_type' => 'post',

	    'has_archive' => false, 

	    'hierarchical' => false,

	    'menu_position' => null,

	    'supports' => array( 'title', 'editor', 'comments', 'page-attributes')

	  );

	  register_post_type( $this->post_type, $args );

	}

	/**
	 * Adds our FAQs tab to Woo's product tabs
	 *
	 * @since    1.0.0
	 */
	function faq_tab($tabs) {

		$tabs['faqs'] = array(

			'title' => __( 'FAQs', 'woocommerce' ),

			'priority' => 100,

			'callback' => array($this,'faq_tab_content')

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
		require_once($this->antispam_lib);

		// Instantiate the AYAH object.
		$this->ayah = new AYAH(
			//we are bypassing AYAH's sample _config.php file,
			//and instead getting the keys from the options table
			//and instantiating the object with them
			array(

				'publisher_key'=>get_option($this->option_prefix.'publisher_key'),

				'scoring_key'=>get_option($this->option_prefix.'scoring_key')

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
		include($this->get_base_path().'/views/loop-faqs.php');

		//the faq form
		include($this->get_base_path().'/views/faq-form.php');
		
	}

	/**
	 * Returns 'error' for the form inputs 
	 * whose input resulted in an error
	 * or just an empty string
	 *
	 * @since    1.0.0
	 */
	function should_display_error($result,$key){

		if(isset($result['errors']) && is_array($result['errors'])){

			if(array_key_exists($key, $result['errors'])){

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

		if (file_exists($this->get_base_path() . '/includes' . $file))

			return($this->get_base_path() . '/includes' . $file);

		elseif (file_exists(STYLESHEETPATH . $file))

			return(STYLESHEETPATH . $file );

		elseif ( file_exists( TEMPLATEPATH . $file ) )

			return( TEMPLATEPATH . $file );

		else // Backward compat code will be removed in a future release

			return( ABSPATH . WPINC . '/theme-compat/comments.php');

	}

	/**
	 * Handle the submission of a FAQ
	 *
	 * @since    1.0.0
	 */
	function handle_submission($use_antispam=null){

		//this $post variable is for the PRODUCT
		global $post;

		//if this function was called without specifiying whether or not to
		//use antispam, we get that value here
		if(empty($use_antispam)) $use_antispam = $this->use_antispam();

		//create errors and result arrays
		$errors = array();

		$result=array();

		//put post data into an array
		if(isset($_POST['faq_author_name'])) $input['faq_author_name'] =$_POST['faq_author_name'];

		if(isset($_POST['faq_author_email'])) $input['faq_author_email'] = $_POST['faq_author_email'];

		if(isset($_POST['faq_content'])) $input['faq_content']=$_POST['faq_content'];

		//very simple validation for content, name, and email
		//TODO - make this validation more stringent
		if(empty($input['faq_content'])) {

			$errors['faq_content']='Please enter a question!';

		}

		if(empty($input['faq_author_name'])){

			$errors['faq_author_name']='Please enter your name!';

		}

		if(empty($input['faq_author_email']) || (!empty($input['faq_author_email']) && !filter_var($input['faq_author_email'], FILTER_VALIDATE_EMAIL))){

			$errors['faq_author_email']='Please enter a valid email!';

		}

		//antispam handler, with or without AYAH enabled
		$result = $this->handle_antispam();

		//if antispam returned a error type result, asker failed antispam check
		if($result['type'] == 'error'){

			$errors[] = $result['message'];

		}

		//passed all checks
		if(empty($errors)) {

			$post_info = array(

				'post_title' => 'Question for '.$post->post_title,

				'post_content' => wp_strip_all_tags($input['faq_content']),

				'post_type' => $this->post_type,

				'post_status' => 'pending',

				'comment_status'=>'open'

			);

			//create the post
			$post_id = wp_insert_post($post_info);

			//add post meta
			update_post_meta($post_id,'_'.$this->post_type.'_product',$post->ID);

			update_post_meta($post_id,'_'.$this->post_type.'_author_name',$input['faq_author_name']);

			update_post_meta($post_id,'_'.$this->post_type.'_author_email',$input['faq_author_email']);

			//data for elsewhere (like the notifications)
			$input['product_title'] = $post->post_title;

			$input['question_title'] = $post_info['post_title'];

			$input['question_content'] = $post_info['post_content'];

			$input['post_id'] = $post_id;

			//result for the form (success)
			$result['type']='success';

			$result['message']='FAQ Successfully Posted. Your question will be reviewed and answered soon!';

			//send the notification to the answerer
			$this->send_notifications('answerer',$input);

		} else{

			//result for the form (error)
			$result['type']='error';

			$result['errors'] = $errors;

		}

		return $result;

	}

	/**
	 * Handle the recaptcha submission
	 *
	 * @since    1.0.0
	 */
	function handle_antispam(){

		//we need the result array one way or the other
		$result=array();

		//check if we are using 'antispam'
		if($this->use_antispam()){

			$this->require_antispam();

	        // Use the AYAH object to get the score.
	        $score = $this->ayah->scoreResult();

	        // Check the score to determine what to do.
	        //if score is boolean true, the user passed
	        if ($score){

                $result['type']='success';

	        }

	        //otherwise, they failed
	        else{

                $result['type']='error';

				$result['message']='The antispam wasn\'t entered correctly. Go back and try it again.';

	        }
		}

		//if we aren't, still use honeypot to check
		else{

			//this is a honeypot!!!
			//if primary_email is set/not empty, we've failed the honeypot
			if( isset( $_POST['primary_email'] ) && $_POST['primary_email'] != ''){
				
				$result['type'] = 'error';
				
				$result['message'] = 'You\'ve triggered our anti-spam filter. If you have a form-filling application/extension, please disable it temporarily.';
			}

			else{

				$result['type'] = 'success';

			}

		}

		if($result['type'] == 'error'){

			//allow the error message to be filtered
			apply_filters( $this->option_prefix . 'antispam_error_message', $result['message'], $_POST );

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
		$answerer_email_required = apply_filters( $this->option_prefix . 'answerer_email_required', array( 'question_title', 'faq_author_name', 'product_title', 'question_content' ), $post_data );

		$asker_email_required = apply_filters( $this->option_prefix . 'asker_email_required', array( 'question_title', 'product_title', 'post_id' ), $post_data );

		//who to send the emails to
		$answerer_email = apply_filters( $this->option_prefix . 'answerer_email', get_option( 'admin_email' ), $post_data );

		$asker_email = apply_filters( $this->option_prefix . 'asker_email', $post_data['faq_author_email'], $post_data );

		$to = '';

		$subject = '';

		$message = '';

		$success = false;

		//we need to know who to send to!
		if($to_whom){

			//filter wp mail to html
			add_filter( 'wp_mail_content_type' , array( $this, 'set_html_content_type' ) );

			switch($to_whom){

				case 'answerer':

					$to = $answerer_email;

					$subject = 'New ' . $post_data['question_title'];

					//allow the subject to be filtered
					$subject = apply_filters( $this->option_prefix . 'answerer_email_subject', $subject, $post_data );

					$message = '<p>' . $post_data['faq_author_name'] . ' asked the following question about ' . $post_data['product_title'] . ':</p>';

					$message .= '<p>"' . $post_data['question_content'] . '"</p>';

					$message .= '<p>The question can be administered <a href="' . admin_url('/edit.php?post_type=') . $this->post_type . '&highlight='.$post_data['post_id'].'">here.</a>';

					//allow the final message to be filtered
					$message = apply_filters( $this->option_prefix . 'answerer_email_message', $message, $post_data );

					break;

				case 'asker':

					$to = $asker_email;

					$subject = 'Response to ' . $post_data['question_title'];

					//allow the subject to be filtered
					$subject = apply_filters( $this->option_prefix . 'asker_email_subject', $subject, $post_data );

					$message = '<p>A reply to your question about ' . $post_data['product_title'] . ' has been posted!</p>';

					$product_link = get_permalink( $post_data['product_id'] );

					$message .= '<p>View the answer <a href="' . $product_link . $this->andor($product_link) . 'faq-view=' . $post_data['post_id'] . '#tab-faqs">here</a></p>';

					//allow the final message to be filtered
					$message = apply_filters( $this->option_prefix . 'asker_email_message', $message, $post_data );

					break;

			}
			if(!empty($to)){
				
				$success = wp_mail( $to, $subject, $message);

			}

			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		}

		//we may check on this later
		return $success;

	}

	/**
	 * Filter WP emails to be HTML (for the ones we send)
	 *
	 * @since    1.0.0
	 */
	function set_html_content_type(){

		return 'text/html';

	}

	/**
	 * Redirect the answerer back to the product page
	 * after they interact with the FAQ section
	 *
	 * @since    1.0.0
	 */
	function redirect_comment_form($location,$comment){

		$faq = $comment->comment_post_ID;

		if($product = get_post_meta($faq,'_woo_faq_product',true)){

			$link = get_permalink($product).'#tab-faqs';

		}

		return $link;

	}

	/**
	 * Our comment (answer) loop
	 *
	 * @since    1.0.0
	 */
	function comment_callback($comment, $args, $depth) {

		$GLOBALS['comment'] = $comment;

		$comment_count = (int)$comment->comment_count;

		?> 

		<div <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>" class="clearfix">

			<div id="comment-<?php comment_ID(); ?>" class="comment-body clearfix">

				<div class="wrapper">

					<?php if ($comment->comment_approved == '0') : ?>

					<em><?php echo theme_locals("your_comment") ?></em>

					<?php endif; ?>

					<div class="extra-wrap">

					<h4><?php if ($comment_count <= 1) {

					echo 'A: ';

					}else{

					$comment_author = (int)$comment->user_id;

					$question_author = (int)get_post_field('post_author',(int)$comment->comment_post_ID);

					if($comment_author == $question_author){

					echo 'Asker: ';

					}else echo 'Answerer: ';

					} ?><?php echo get_comment_text() ?></h4>     	

					</div>

					<div class="comment-author vcard">

					<?php echo get_avatar( $comment->comment_faq_author_email, 65 ); ?>

					<?php printf('<span class="author">— %1$s</span>', get_comment_author_link()) ?>

					</div>

				</div>

				<div class="wrapper">

				<div class="reply">

				</div>

				<div class="comment-meta commentmetadata"><?php printf('%1$s', get_comment_date('F j, Y')) ?></div>

				</div>

			</div>

		</div>

		<?php }

	/**
	 * Changes up the action row for custom behavior
	 *
	 * @since    1.0.0
	 */
	function action_row($actions, $post){

		//check for our post type
		if ($post->post_type == $this->post_type){

			$post_type_object = get_post_type_object( $post->post_type );

			$post_type_label = $post_type_object->labels->singular_name;

			if($post->post_status == 'draft' || $post->post_status == 'pending'){

				$actions['pre_view'] = "<a title='" . esc_attr( __( 'Preview this' ) ) . $post_type_label . "' href='" . $this->preview_link() . "'>" . __( 'Preview' ) . "</a>";

				$actions['publish'] = "<a href='#' class='submitpublish' data-id='".$post->ID."' title='" . esc_attr( __( 'Approve this ' ) ) .

				$post_type_label . "' data-nonce='" . wp_create_nonce( 'publish-post_' . $post->ID ) . "'>" . __( 'Approve' ) . "</a>";

			}

			else{
				
				$actions['view'] = "<a title='" . esc_attr( __( 'View this ' ) ) . $post_type_label . "' href='" . $this->preview_link() . "'>" . __( 'View' ) . "</a>";

			}

		}

		return $actions;
	}

	/**
	 * Generates the preview/view link for our FAQs
	 *
	 * @since    1.0.0
	 */
	function preview_link($preview_link='') {

    	global $post;

    	if($post->post_type == $this->post_type){

    		$preview_link = get_permalink( (int)get_post_meta( $post->ID, '_' . $this->post_type . '_product', true) );

    		$publish = ( $post->post_status == 'publish' ? 'view' : 'preview' );

    		$andor = $this->andor($preview_link);

    		$preview_link .= $andor . 'faq-' . $publish . '=' . $post->ID . '#tab-faqs';

    	}

    	return $preview_link;

	}

	/**
	 * Returns the correct url addition based on 
	 * if there is a query string present already
	 *
	 * @since    1.0.0
	 */
	function andor($link){

		$test = '/';

		return (substr($link, -strlen($test)) === $test ? '?' : '&' );

	}

	/**
	 * Notifies the visitor/customer that an 
	 * answer has been posted to their question
	 *
	 * @since    1.0.0
	 */
	function answer_posted( $comment_id, $comment_object ) {

		$post_id = (int) $comment_object->comment_post_ID;

		if( get_post_type( $post_id ) == $this->post_type ){

			$product_id = get_post_meta( $post_id, '_' . $this->post_type . '_product', true );

			$post_data = array(

				'post_id'=> $post_id,

				'question_title'=> get_the_title( $post_id ),

				'product_title' => get_the_title( $product_id ),

				'product_id'=> $product_id,

				'faq_author_email'=>get_post_meta($post_id, '_' . $this->post_type . '_author_email', true )

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
		return ( get_option( $this->option_prefix . 'use_antispam' ) == 'yes' ? true : false );

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

			$result['message'] = 'Current user does not have permissions over this post';

			echo json_encode($result);

			die();

		}

		//verify the posted nonce
		if ( !wp_verify_nonce( $_POST['nonce'], 'publish-post_' . $post_id ) ) {

	        $result['type'] = 'error';

			$result['message'] = 'Cheatin, eh?';

			echo json_encode($result);

			die();

	    }

	    //if we got this far, publish the post and generate the success
	    wp_publish_post($post_id);

	    $result['type'] = 'success';

	    $result['message'] = 'Approved...reloading now.';

	    $result['redirect'] = admin_url('edit.php?post_type=' . $this->post_type);

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

			add_action( 'woocommerce_settings_tabs', array( $this, 'admin_options' ) );

			//we have to manually update settings
			add_action('woocommerce_update_options_faqs', array( $this, 'update_old_wc_options' ) );

			add_action( 'woocommerce_product_tabs', array( $this, 'woocommerce_faqs_tab' ), 40 );

			add_action( 'woocommerce_product_tab_panels', array($this, 'faq_tab_content' ), 40 );

		}

		else{

			//filter woo's tabs to add FAQs
			add_filter( 'woocommerce_product_tabs', array( $this, 'faq_tab' ) );

			//action for settings tab content
			add_action( 'woocommerce_settings_start', array( $this, 'admin_options' ) );

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
		<li class="faqs_tab"><a href="#tab-faqs">FAQs</a></li>
		<?php
	}

	/**
	 * Manually save WC options for old versions
	 *
	 * @since     1.0.5
	 *
	 * @return    null
	 */
	function update_old_wc_options(){

		update_option('woocommerce_faqs_publisher_key',sanitize_text_field($_POST[$this->option_prefix . 'publisher_key']));

		update_option('woocommerce_faqs_scoring_key',sanitize_text_field($_POST[$this->option_prefix . 'scoring_key']));

		$use_antispam = (sanitize_text_field($_POST[$this->option_prefix . 'use_antispam']) == 1 ? 'yes' : 'no');

		update_option('woocommerce_faqs_use_antispam',$use_antispam);

	}

	/**
	 * Return columns for the post table of this post type
	 *
	 * @since     1.0.6
	 *
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
	 *
	 * @since     1.0.6
	 *
	 * @return    null
	 */
	function custom_column( $column, $post_id ) {
	    switch ( $column ) {

	        case 'asker' :
	        	echo get_post_meta( $post_id, '_' . $this->post_type . '_author_name', true );
	            break;

	        case 'asker_email' :
	            echo get_post_meta( $post_id, '_' . $this->post_type . '_author_email', true );
	            break;

	    }
	}

	/**
	 * Add meta boxes for this post type
	 *
	 * @since     1.0.6
	 *
	 * @return    null
	 */
	function meta_boxes(){

		add_meta_box( $this->post_type . '_product', __( 'FAQ Details', $this->plugin_slug ), array( $this, 'metabox' ), $this->post_type, 'normal', 'high' );

		remove_meta_box( 'commentstatusdiv', $this->post_type, 'normal' );

	}

	/**
	 * Meta box content
	 *
	 * @since     1.0.6
	 *
	 * @return    null
	 */
	function metabox($post){
		//get current value
		$current_product = (int)get_post_meta( $post->ID, '_' . $this->post_type . '_product', true );

		$author_name = get_post_meta( $post->ID, '_' . $this->post_type . '_author_name', true );

		$author_email = get_post_meta( $post->ID, '_' . $this->post_type . '_author_email', true );

		//get all products
		$args = array(

			'post_type'=>'product',

			);

		$products = get_posts($args);

		if($products){

			//nonce
			wp_nonce_field( plugin_basename( __FILE__ ), $this->post_type . 'meta_nonce' );

			//Product relationship label
			echo '<p><label for="_' . '_' . $this->post_type . '_product">';

			_e('Product this question is shown on: ', $this->plugin_slug );

			echo '</label>';

			//Product relationship select
			echo '<select name="' . '_' . $this->post_type . '_product">';

			foreach($products as $product){

				echo '<option '. selected( $current_product, $product->ID, false ) .' value="' . $product->ID . '">' . $product->post_title . '</option>';

			}

			echo '</select></p>';

		}

		//otherwise, just say there are no products
		else{

			echo '<p>';

			_e( 'No Products Found', $this->plugin_slug );

			echo '</p>';

		}

		//question author info
		echo '<p>';

		_e( 'It is best to leave the fields below blank if you are adding a FAQ manually.', $this->plugin_slug );

		echo '</p>';

		//author's name
		echo '<p><label for="_' . $this->post_type . '_author_name">';

		_e('Author: ', $this->plugin_slug );

		echo '</label>';

		echo '<input type="text" name="_' . $this->post_type . '_author_name" value="' . $author_name . '"/></p>';

		//author's email
		echo '<p><label for="_' . $this->post_type . '_author_email">';

		_e('Author Email: ', $this->plugin_slug );

		echo '</label>';

		echo '<input type="email" name="_' . $this->post_type . '_author_email" value="' . $author_email . '"/></p>';

	}

	/**
	 * Save meta info
	 *
	 * @since     1.0.6
	 *
	 * @return    null
	 */
	function save_meta($post_id){

		// First we need to check if the current user is authorised to do this action. 
		if ( ! current_user_can( 'edit_post', $post_id ) ){

			return;

		}

		// Secondly we need to check if the user intended to change this value.
		if ( ! isset( $_POST[$this->post_type . 'meta_nonce'] ) || ! wp_verify_nonce( $_POST[$this->post_type . 'meta_nonce'], plugin_basename( __FILE__ ) ) ){

      		return;

      	}

      	$author_name = sanitize_text_field($_POST['_'.$this->post_type.'_author_name']);

      	$author_email = sanitize_text_field($_POST['_'.$this->post_type.'_author_email']);

      	$product = sanitize_text_field( $_POST['_' . $this->post_type . '_product'] );

      	update_post_meta($post_id,'_'.$this->post_type.'_author_name', $author_name);

      	update_post_meta($post_id,'_'.$this->post_type.'_author_email',$author_email);

		update_post_meta( $post_id, '_' . $this->post_type . '_product', $product );

	}

	/**
	 * Filter the comment text on the edit screen
	 * to be more sensible
	 *
	 * @since     1.0.6
	 *
	 * @return    object full translations object
	 */
	function filter_gettext( $translated, $original, $domain ) {

		if( ( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == $this->post_type ) || ( isset($_REQUEST['post']) && get_post_type( $_REQUEST['post'] ) == $this->post_type ) ){

			$strings = array(

			'Comments' => 'Answers',

			'Add comment' => 'Add answer',

			'Add Comment' => 'Add Answer',

			'Add new Comment' => 'Add new Answer',

			'No comments yet.' => 'No answers yet.',

			'Show comments' => 'Show answers',

			'No more comments found.' => 'No more answers found.',

			);

			if ( isset( $strings[$original] ) ) {

			$translations = &get_translations_for_domain( $domain );

			$translated = $translations->translate( $strings[$original] );

			}

		}

		return $translated;
	}

	/**
	 * Create a link to view the FAQ
	 * from the edit screen
	 *
	 * @since     1.0.6
	 *
	 * @return    null
	 */
	function view_link(){

		global $post;

		if($post->post_status == 'publish'){

			$link = $this->preview_link();

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

}//end class