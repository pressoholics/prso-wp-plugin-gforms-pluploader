<?php
/**
 * Contains all methods required by the plugin as a whole.
 * These include methods to interact with Prso Framework Core via custom Wordpress action/filter hooks
 * 
 * CUSTOM ACTIONS FOR PLUGIN ACTIVATION - Add your custom method into this class then call it in
 * $this->on_plugin_activation()
 *
 * Contents:
 *		__construct()			- Magic method construct
 *		on_plugin_activation()	- Call methods to be run during plugin activation here
 *		prso_core_active()		- Confirms that the PrsoCore framework plugin is active
 *		get_options()			- Helper to get option data from WP Options table and cache in $this->data array for all classes to use
 *		request_router()		- Used for multi page plugins, calls PrsoCore framework to detect controller and action requests loading the required files from /views/request_router dir
 *		form_action()			- Helper to create a form action url, used for multi page plugins using the request_router - note: creates a nounce for url
 *		plugin_redirect()		- Calls PrsoCore framework to redirect the user to requested page either by wp_redirect or meta redirect
 *		__set()					- Magic method set, stores vars in protected array $data
 *		__get()					- Magic method get, gets vars from protected array $data
 * 
 *
 */
class PrsoGformsPluploaderAppController extends PrsoGformsPluploaderConfig {
	
	protected $data = array(); //Master store of all data for plugin actions - options data, _GET, Overload data from magic methods
	
	function __construct() {
		//Ensure vars set in config are available
 		parent::__construct();

 		//Register actions to be performed on plugin activation
 		register_activation_hook( parent::$plugin_file_path, array($this, 'on_plugin_activation') );
 		
	}
	
	public function on_plugin_activation() {
		
		//Check that the PrsoCore framework plugin has been installed
		$this->prso_core_active( false );
		
	}
	
	/**
	* prso_core_active
	* 
	* Confirm that the PrsoCore framework plugin has been activated
	* 
	* @access 	protected
	* @author	Ben Moody
	*/
	public function prso_core_active( $on_plugin_page = true ) {
		
		//Init vars
		$user_msg = 'Sorry, it appears that you need to install the PrsoCore Plugin before you can use ' . $this->page_title_parent;
		$plugin_menu_slug 	= NULL;
		$current_page		= NULL;
		
		//Cache the current page
		if( isset($_GET['page']) ) {
			$current_page = esc_attr( $_GET['page'] );
		}
		
		//Should we make sure user is on the plugin options page before we conduct the check?
		if( $on_plugin_page ) {
			
			if( !defined('PrsoCoreActive') && isset($current_page) ) {
				//Check this is the plugin admin page
				$plugin_menu_slug = filter_var( $current_page, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
				if( $plugin_menu_slug == $this->menu_slug_parent ) {
					if( function_exists('deactivate_plugins') ) {
						deactivate_plugins( parent::$plugin_file_path );
					}
					wp_die( __( $user_msg, 'prso_core' ) );	
				}
			}
			
		} else {
			
			if( !defined('PrsoCoreActive') ) {
				if( function_exists('deactivate_plugins') ) {
					deactivate_plugins( parent::$plugin_file_path );
				}
				
				echo __( $user_msg, 'prso_core' );	
				exit;
			}
			
		}
		
	}
	
	/**
	* get_options
	* 
	* Little helper to get data from the wordpress options database table
	* and cache it for later use.
	* 
	* @access 	protected
	* @author	Ben Moody
	*/
	public function get_options( $option_slug = NULL, $multi_array_slug = NULL ) {
		
		//Init vars
		$option_data = array();
		$temp_data = array();
		
		if(isset( $option_slug )) {
			
			//Call custom wp filter '' to get options data, pass $option_slug, $multi_array_slug
			$option_data = apply_filters( 'prso_core_get_options', $option_data, $option_slug, $multi_array_slug );
			
			$this->data[ $option_slug ] = $option_data;
			return true;
			
		}
		
		return false;
	}
	
	/**
	* get_slug
	* 
	* Little helper to prepend any slug vars with the framework slug constant PRSO_SLUG
	* helps to avoid any name conflicts with options slugs
	* 
	* @access 	protected
	* @author	Ben Moody
	*/
 	protected function get_slug( $var = NULL ) {
 		
 		//Init vars
 		$slug = false;
 		$plugin_slug = parent::plugin_slug;
 		
 		if(isset( $plugin_slug, $var )) {
 			//Pass wordpress filter 'prso_core_get_slug' plugin_slug, $var, and Current Object $this
 			$slug = apply_filters( 'prso_core_get_slug', $slug, $var, $plugin_slug, $this  );	
 		}
 		
 		return $slug;
 	}
 	
 	/**
	* Request Router
	*
	* Detects any plugin specific controller and action requested in the admin url
	* finds the action and calls the specific method passing any params.
	* 
	*
	*/
	public function request_router( ) {
		
		//Init vars
		$args = array();
		
		if( isset($this->menu_slug_parent, $this->plugin_class_slug, $this->plugin_views) ) {
			//Call wordpress action hook 'prso_core_request_router' to hook into PrsoCore app controller
			$args = array(
				'plugin_menu_slug' 		=> $this->menu_slug_parent,
				'plugin_class_slug'		=> $this->plugin_class_slug,
				'plugin_views_dir'		=> $this->plugin_views
			);
			
			do_action( 'prso_core_request_router', $args );
			
		}
		
	}
	
	/**
	* form_action
	*
	* Creates a form action url based on the $controller and $action params provided.
	* The method will also create a Nonce based on page_slug-action-controller and append it
	* to the url using add_query_arg
	*
	*/
	protected function form_action( $action = null, $page_slug = NULL, $controller = NULL ) {
		
		//Init vars
		$action_url = NULL;
		
		//Build the form action url
		if(isset( $controller, $page_slug )) {
			
			//Call custom wp filter 'prso_core_form_action' passing $action, $page_slug, $controller
			$action_url = apply_filters( 'prso_core_form_action', $action_url, $action, $page_slug, $controller );
			
		}
		
		return $action_url;
	}
	
	protected function plugin_redirect( $args = array(), $meta_redirect = false ) {
		
		if( !empty($args) ) {
			//Call Wordpress action hook 'prso_core_plugin_redirect' to hook into PrsoCore app controller
			do_action( 'prso_core_plugin_redirect', $meta_redirect, $args );
		}
		
	}
	
	
	//Magic methods set and get
	public function __set( $name, $value ) {
		if( isset($this->data) ) {
			$this->data[$name] = $value;
		}
	}
	
	public function __get( $name ) {
		if( isset($this->data) && array_key_exists( $name, $this->data ) ) {
			return $this->data[$name];
		}
		return null;
	}

}