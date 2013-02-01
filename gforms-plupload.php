<?php
/*
Plugin Name: Gravity Forms Pluploader
Version: 1.0
Author URI: http://Pressoholics.com
Plugin URI: http://Pressoholics.com
Description:  Adds a custom jQuery file uploader to Gravity Forms using Pluploader plugin
Author: Benjamin Moody
*/

/**
 * gforms-jupload
 *
 * PHP versions 4 and 5
 *
 * @copyright     Pressoholics (http://pressoholics.com)
 * @link          http://pressoholics.com
 * @package       pressoholics theme framework
 * @since         Pressoholics v 1.0
 */

	
/**
* Include config file to set core definitions
*
*/
if( file_exists( dirname(__FILE__) . '/config.php' ) ) {
	
	include( dirname(__FILE__) . '/config.php' );
	
	if( class_exists('PrsoGformsPluploaderConfig') ) {
		
		new PrsoGformsPluploaderConfig( __FILE__ );
		
		//Core loaded, load rest of plugin core
		include( dirname(__FILE__) . '/bootstrap.php' );

		//Instantiate bootstrap class
		if( class_exists('PrsoGformsPluploaderBootstrap') ) {
			new PrsoGformsPluploaderBootstrap();
		}
		
	}
	
}
