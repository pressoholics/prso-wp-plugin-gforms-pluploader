<?php
/**
 * PrsoGformsPluploaderBootstrap
 *
 * Instantiates all required classes for the Pressoholics theme plugin framework
 *
 * E.G Instantiates models, views
 *
 * PHP versions 4 and 5
 *
 * @copyright     Pressoholics (http://pressoholics.com)
 * @link          http://pressoholics.com
 * @package       pressoholics theme framework
 * @since         Pressoholics v 0.1
 *
 * Contents:
 *		__construct()			- Magic method constuct
 *		boot()					- Called in construct, detects if app_controller class is successfully loaded then loads plugin view classes and functions class
 *		scan_views()			- Scans the plugin views dir to find any plugin view files
 *		load_admin_views()		- Loops through results of $this->scan_views() and tries to include view files and instantiate view classes
 *		load_app_controller()	- Tries to include and instatiate the plugin app_controller which contains helpers and methods for use within plugin view files
 *		load_app_functions()	- Tries to include and instatiate the plugins functions class, contains methods not directly related to one single view class
 *
 */
 
 class PrsoGformsPluploaderBootstrap extends PrsoGformsPluploaderConfig {
 	
 	private $views_scan		= array(); //Cache all views in views dir (does not include views for request_router)
 	
 	function __construct( $args = array() ) {
 		//Ensure vars set in config are available
 		parent::__construct();
 		
 		//$this->boot(); //axed this as need to wait for prso core to be loaded
 		add_action( 'plugins_loaded', array( $this, 'boot' ) );
 	}
 	
 	/**
	* boot
	* 
	* Calls methods to scan models dir and load instances of all valid models found
	* 
	*/
 	public function boot() {
 		
 		//Load common app functions
 		if( $this->load_app_controller() ) {
 			
 			//Scan the views dir
	 		$this->views_scan = $this->scan_views();
 			
 			//Load general app functions
 			$this->load_app_functions();
 			
 			//If user is admin load wp admin views
	 		$this->load_admin_views();
 		
 		} else {
 		
 			$user_msg = 'Sorry, There appears to be a problem with the ' . $this->page_title_parent . ' plugin app controller.';
 			
 			wp_die( __( $user_msg, 'prso_core' ) );
 			
 		}
 		
 	}
 	
 	/**
	* scan_views
	* 
	* Scans theme framework views dir, caches any files found in
	* $this->views_scan array.
	*
	* Returns false on error
	* 
	*/
 	private function scan_views() {
 			
 		//Init vars
 		$result = false;
 		$args	= array(
			'plugin_views_dir' => $this->plugin_views
		);
 		 		
 		$result = apply_filters( 'prso_core_scan_plugin_views', $result, $args );
 		
 		return $result;
 	}
 	
 	/**
	* load_admin
	* 
	* Detects if user is logged in, if so then it detects the theme framework admin view
	* file in PRSO_PLUGIN_VIEWS dir and creates an instance of the class.
	* 
	*/
 	private function load_admin_views() {
 		
 		$args = array(
			'views_scan' 		=> $this->views_scan,
			'plugin_class_slug'	=> $this->plugin_class_slug,
			'plugin_views_dir'	=> $this->plugin_views
		);
 		
 		do_action( 'prso_core_load_plugin_views', $args );
 		
 	}
 	
 	/**
	* load_app_controller
	* 
	* Loads the app_controller class, which contains common methods shared by all presso plugins
	* 
	*/
 	private function load_app_controller() {
		
		//Init vars
		$result = false;
		$args	= array(
			'plugin_root_dir' 	=> $this->plugin_root,
			'plugin_class_slug'	=> $this->plugin_class_slug
		);
		
		$result = apply_filters( 'prso_core_load_plugin_app_controller', $result, $args );
 		
 		return $result;
 	}
 	
 	/**
	* load_app_functions
	* 
	* Loads the app_functions class, which contains all custom methods for this app
	* 
	*/
 	private function load_app_functions() {
 		
 		//Init vars
 		$args 	= array(
			'plugin_root_dir' 	=> $this->plugin_root,
			'plugin_class_slug'	=> $this->plugin_class_slug
		);
 		
 		do_action( 'prso_core_load_plugin_functions', $args );
 		
 	}
 	
 }