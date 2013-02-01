<?php
/**
 * Config
 *
 * Sets all constant definitions for the Pressoholics theme plugin framwork
 *
 * PHP versions 4 and 5
 *
 * @copyright     Pressoholics (http://pressoholics.com)
 * @link          http://pressoholics.com
 * @package       pressoholics theme framework
 * @since         Pressoholics v 0.1
 */
class PrsoGformsPluploaderConfig {
	
	//***** CHANGE PLUGIN OPTIONS HERE *****//
		
	/**
	* VERY IMPORTANT
	*
	* Define a unique slug to prepend to all wordpress database keys to ensure
	* there are no conflicts
	*
	* Effects Class Names and Keys for saved options
	*
	* Be sure to Prepend all Class names with this slug (convert to CamelCase - E.G. foo_bar_ => FooBar)
	*
	* If you need a string to be unique say with an option key call $this->get_slug('your_string'), it will return
	* your_string with the plugin slug prepended to it.
	*
	*/
	const plugin_slug = 'prso_gforms_pluploader_';
	
	
	//***** END -- PLUGIN OPTIONS *****//
	
	
	
	
	/**
	* The full path to the directory which holds "presso_framework", WITHOUT a trailing DS.
	*
	*/
	protected $plugin_root 		= NULL;
	
	/**
	* The full path to the master plugin file - PLUGIN_NAME.php
	* Declared as static so that the __FILE__ val from the master plugin file can be passed
	* to the config file and stored for use within the app_controller by calling parent::$plugin_file_path
	*/
	protected static $plugin_file_path = NULL;
	
	/**
	* The full path to the directory which holds "views", WITHOUT a trailing DS.
	*
	*/
	protected $plugin_views = NULL;
	
	/**
	* The full path to the directory which holds "includes", WITHOUT a trailing DS.
	*
	*/
	protected $plugin_includes = NULL;
	
	/**
	* The full path to the Presso Field Plugin Config File.
	*
	*/
	protected $plugin_config = NULL;
	
	/**
	* Unique slug prepended to all class names, based on var $plugin_slug set at top of this file
	*
	*/
	protected $plugin_class_slug = NULL;
	
	function __construct( $plugin_file_path = NULL ) {
			
		//Cache the plugin root path - minus trailing slash
 		$this->plugin_root = rtrim( plugin_dir_path(__FILE__), '/' );
		
		//Set plugin filename - note this is a static var
		if( !empty($plugin_file_path) ) {
			self::$plugin_file_path = $plugin_file_path;
		}
		
		$this->plugin_file_path = $plugin_file_path;
		
		//Set plugin views folder
		$this->plugin_views = $this->plugin_root . '/views';
		
		//Set plugin includes folder
		$this->plugin_includes = $this->plugin_root . '/includes';
		
		//Set path to plugin config file
		$this->plugin_config = $this->plugin_root . '/config.php';
		
		//Set plugin Class slug to be prepended to class names making them unique
		$this->plugin_class_slug = str_replace(' ', '', ucwords(str_replace('_', ' ', self::plugin_slug)));
			
	}

}