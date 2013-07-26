<?php

class PrsoGformsPluploaderFunctions extends PrsoGformsPluploaderAppController {
	
	public $prso_pluploader_args 					= NULL;
	private static $prso_pluploader_tmp_dir_name 	= 'prso-pluploader-tmp';
	private static $submit_nonce_key 				= 'prso-pluploader-loader-submit-nonce';
	private static $encrypt_key						= '4ddRp]4X5}R-WU';
	private $move_div = array();
	
	//Gforms meta keys
	private static $delete_files_meta_key		= 'prso-pluploader-delete-files';
	
	//*** PRSO PLUGIN FRAMEWORK METHODS - Edit at your own risk (go nuts if you just want to add to them) ***//
	
	function __construct() {
		
		//Ensure vars set in config are available
 		parent::__construct();
 		
 		//Hook into WP admin_init
 		$this->admin_init();
 		
	}
	
	/**
	* admin_init
	* 
	* Called in __construct() to fire any methods for
	* WP Action Hook 'admin_init'
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function admin_init() {
		
		//*** PRSO PLUGIN CORE ACTIONS ***//
		
		//Register scripts
		add_action( 'init', array($this, 'register_scripts') );
		
		//Enqueue any custom scripts or styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		//Add any custom actions
		add_action( 'init', array( $this, 'add_actions' ) );
		
		//Add any custom filter
		add_action( 'init', array( $this, 'add_filters' ) );
		
		
		//*** ADD CUSTOM ACTIONS HERE ***//
		
		//Include Terms of Service plugin
		$tos_path = $this->plugin_includes . '/inc_gforms_terms.php';
		if( file_exists($tos_path) ) {
			include_once( $tos_path );
			new PrsoGformsTermsFunctions();
		}
		
	}
	
	/**
	* register_scripts
	* 
	* Called by $this->admin_init() to queue any custom scripts or stylesheets
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function register_scripts() {
		
		//Init vars
		$plugin_file_path 			= NULL;
		$plupload_i18n_script		= NULL;
		$plupload_i18n_script_path	= NULL;
		
		if( isset(PrsoGformsPluploaderConfig::$plugin_file_path) ){
			$plugin_file_path = PrsoGformsPluploaderConfig::$plugin_file_path;
		}
		
		//Register Plupload scripts
		wp_register_script( 'plupload-jquery-ui-core', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js', array('jquery'), '1.8.9' );
		
		wp_register_script( 'plupload-jquery-ui', plugins_url('js/jquery.ui.plupload/jquery.ui.plupload.js', $plugin_file_path), array('plupload-all'), '1.0', TRUE );
		
		//i18n Scripts
		$plupload_i18n_script = apply_filters( 'prso_gform_pluploader_i18n_script', $plupload_i18n_script );
		
		//Register request plupload i18n script if found
		if( isset($this->plugin_root, $plupload_i18n_script) ) {
			
			$plupload_i18n_script_path = $this->plugin_root. '/' . 'js/i18n/' . $plupload_i18n_script . '.js';
			
			if( file_exists($plupload_i18n_script_path)) {
				wp_register_script( "plupload-i18n", plugins_url("js/i18n/{$plupload_i18n_script}.js", $plugin_file_path), array('plupload-jquery-ui-core'), NULL, TRUE );
			}
			
		}
		
		//Register custom scripts for use with gforms
		wp_register_script( 'prso-pluploader-entries', plugins_url('js/gforms-entries.js', $plugin_file_path), array('jquery'), '1.0', TRUE );
		
		//Register plupload init script
		wp_register_script( 'prso-pluploader-init', plugins_url('js/init_plupload.js', $plugin_file_path), array('plupload-jquery-ui'), '1.0', TRUE );
		
		//Register Fine Uploader Styles
		wp_register_style( 'plupload-jquery-ui-core', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css', NULL, '1.8.9', 'screen' );
		wp_register_style( 'plupload-jquery-ui', plugins_url('js/jquery.ui.plupload/css/jquery.ui.plupload.css', $plugin_file_path), array('plupload-jquery-ui-core'), '1.0', 'screen' );
		
	}
	
	/**
	* enqueue_scripts
	* 
	* Called by $this->admin_init() to queue any custom scripts or stylesheets
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function enqueue_scripts( $activate_fine_uploader = FALSE ) {
		
		//Init vars
		$google_jquery_url = FALSE;
		
		if( !is_admin() ) {
		
			//Try to enqueue google api jquery ui
			$google_jquery_url = @fopen( 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js', 'r' ); //Test google jquery file
			
	 		if( $google_jquery_url !== FALSE ) {
	 			wp_enqueue_script('plupload-jquery-ui-core');
	 		} else {
	 			//Switch to wordpress core versions as backup
	 			wp_enqueue_script('jquery-ui-core');
	 			wp_enqueue_script('jquery-ui-widget');
	 		}
	 		
			//Enqueue scripts for Plupload
			wp_enqueue_script('plupload-all');
			wp_enqueue_script('plupload-jquery-ui');
			
			//i18n if requested
			wp_enqueue_script('plupload-i18n');
			
		}
		
		//Enqueue script for gforms admin customization
		if( is_admin() && isset($_GET['page']) && $_GET['page'] === 'gf_entries' ) {
			wp_enqueue_script('prso-pluploader-entries');
			//Call method to set js object for 'prso-pluploader-entries'
			$this->localize_script_prso_pluploader_entries();
		}
		
		//Enqueue fine uploader activate script
		if( $activate_fine_uploader === TRUE ) {
			//Enqueue plugin plupload init script
			wp_enqueue_script('prso-pluploader-init');
			
			//Call helper to cache and localize vars requied for init
			$this->localize_pluploader_init_vars();
		}
		
		//Enqueue styles for Fine Uploader
		wp_enqueue_style('plupload-jquery-ui-core');
		wp_enqueue_style('plupload-jquery-ui');
		
	}
	
	/**
	* add_actions
	* 
	* Called in $this->admin_init() to add any custom WP Action Hooks
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function add_actions() {
		
		
		//Execute javascript required to add custom field settings menu
		add_action( "gform_editor_js", array($this, 'pluploader_editor_js') );
		
		//Add Standard custom settings to field menu
		add_action( 'gform_field_standard_settings', array($this, 'pluploader_standard_field_settings'), 10, 2 );
		
		//Add Advanced custom settings to field menu
		add_action( 'gform_field_advanced_settings', array($this, 'pluploader_advanced_field_settings'), 10, 2 );
		
		//Enqueue scripts only when custom field is in the selected form
		add_action( 'gform_enqueue_scripts' , array($this, 'pluploader_enqueue_scripts') , 10 , 2 );
		
		//Actions to handle ajax upload requests
		add_action( 'wp_ajax_nopriv_prso-plupload-submit', array($this,'plupload_ajax_submit') );
		add_action( 'wp_ajax_prso-plupload-submit', array($this,'plupload_ajax_submit') );
		
		//Save any uploads as wp attachements in wp media library
		add_action( 'gform_after_submission', array($this, 'save_uploads_as_wp_attachments'), 10, 2 );
		
		//When an entry is moved to the trash detect is user wants to keep or delete files
		add_action( 'gform_update_status', array($this, 'pluploader_trash_checkbox'), 10, 3 );
		
		//Take actions when a lead is deleted
		add_action( 'gform_delete_lead', array($this, 'pluploader_delete_lead'), 10, 1 );
		
	}
	
	/**
	* add_filters
	* 
	* Called in $this->admin_init() to add any custom WP Filter Hooks
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function add_filters() {
		
		//Add a custom field button to the advanced gform field selector menu
		add_filter( 'gform_add_field_buttons', array($this, 'add_field_buttons') );
		
		//Assign a title for the custom gform field editor window
		add_filter( 'gform_field_type_title' , array($this, 'pluploader_field_type_title') );
		
		//Add the custom field html for form rendering
		add_filter( "gform_field_input" , array($this, 'pluploader_field_input'), 10, 5 );
		
		//Filter field's entry data in form entry view - wp admin side
		add_filter( 'gform_entry_field_value', array($this, 'pluploader_entry_field_value'), 10, 4 );
		add_filter( 'gform_get_input_value', array($this, 'pluploader_entry_index_table_value'), 10, 4 );
		
		//Filter the html used to create the entry index table bulk action button
		add_filter( 'gform_entry_apply_button', array($this, 'pluploader_entry_apply_button'), 10, 1 );
		
	}
	
	
	//*** CUSTOM METHODS SPECIFIC TO THIS PLUGIN ***//
	
	/**
	* add_field_buttons
	* 
	* Called by 'gform_add_field_buttons' gravity forms filter.
	* Adds a custom field button to the gravity forms fields menu
	*
	* Options:
	*	$group['name'] = 'advacned_fields'/'standard_fields'/'post_fields'
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function add_field_buttons( $field_groups = array() ) {
	    
	    //Init vars
	    $group = array();
	    
	    if( !empty($field_groups) && is_array($field_groups) ) {
		   foreach( $field_groups as &$group ){
		        if( $group["name"] == "advanced_fields" ){ // to add to the Advanced Fields
		            $group["fields"][] = array(
		                "class"=>"button",
		                "value" => __("Pluploader", "prso_gform_pluploader"),
		                "onclick" => "StartAddField('prso_gform_pluploader');"
		            );
		            break;
		        }
		    } 
	    }
	    
	    return $field_groups;
	}
	
	/**
	* pluploader_field_type_title
	* 
	* Called by 'gform_field_type_title' gravity forms filter.
	* Assigns a title to the custom field edit window
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_field_type_title( $type ) {
	    if ( $type == 'prso_gform_pluploader' ) {
	        return __( 'Pluploader' , 'prso_gform_pluploader' );
	    }
	}
	
	/**
	* pluploader_field_input
	* 
	* Called by 'gform_field_input' gravity forms action.
	* Add html required to render custom field
	* 
	* @access 	public
	* @author	Ben Moody
	*/	
	public function pluploader_field_input ( $input, $field, $value, $lead_id, $form_id ){
	 	
	 	//Init vars
	 	$max_chars 	= NULL;
	 	
	    if ( $field["type"] == "prso_gform_pluploader" ) {
	        
	        if( !empty($field["maxLength"]) && is_numeric($field["maxLength"]) ) {
		        
		        $max_chars = self::get_counter_script($form_id, $field_id, $field["maxLength"]);
		        
	        }
	            
	        $input_name = $form_id .'_' . $field["id"];
	        
	        $tabindex = GFCommon::get_tabindex();
	        
			$css = isset( $field['cssClass'] ) ? $field['cssClass'] : '';
			
			//Cache the hidden field taht will store data on uploaded files
			$input = sprintf(
	        	"<div class='ginput_container'><input name='input_%s' id='%s' type='hidden' /></div>", 
	        	$field["id"], 
	        	'prso_form_pluploader_'.$field['id']
	        );
			
			//Cache the div element used by Fine Uploader jquery plugin
			$plupload_container = sprintf(
				"<div id='pluploader_%s'></div>",
				$field["id"]
			);
			
			//Run through filter to allow devs to move the div outside the form it they wish
			$input.= apply_filters( 'prso_gform_pluploader_container', $plupload_container, $field, $form_id );
			
	    }
	    
	    return $input;
	}
	
	/**
	* pluploader_editor_js
	* 
	* Called by 'gform_editor_js' gravity forms action.
	* Add field setting options via maniplulating the fieldSettings javascript array
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_editor_js(){
	
		?>
		<script type='text/javascript'>
		 
		    jQuery(document).ready(function($) {
		 
		        //View forms.js for examples of options
		        fieldSettings["prso_gform_pluploader"] = ".label_setting, .description_setting, .admin_label_setting, .css_class_setting, .prso_pluploader_file_extensions_setting, .prso_pluploader_file_size_setting, .prso_pluploader_file_chunk_setting, .prso_pluploader_auto_upload_setting, .prso_pluploader_max_files_setting";
		        
		        //Hook into gform load field settings to initialize file extension settings
		        jQuery(document).bind( "gform_load_field_settings", function(event, field, form){
		        
			        //Populate file extensions with data if set
			        jQuery("#prso_pluploader_file_extensions").val( field["prso_pluploader_file_extensions"] );
			        
			        //Populate file size with data if set
			        if( field["prso_pluploader_file_size"] === '' || typeof field["prso_pluploader_file_size"] == 'undefined' ) {
				        field["prso_pluploader_file_size"] = 1;
			        }
			        jQuery("#prso_pluploader_file_size").val( field["prso_pluploader_file_size"] );
			        
			        //Populate file chunking with data
			        if( field["prso_pluploader_file_chunk"] === '' || typeof field["prso_pluploader_file_chunk"] == 'undefined' ) {
				        field["prso_pluploader_file_chunk"] = 'true';
			        }
			        jQuery("#prso_pluploader_file_chunk").val( field["prso_pluploader_file_chunk"] );
			        
			        //Populate auto upload field with data
			        console.log(field["prso_pluploader_auto_upload"]);
			        if( field["prso_pluploader_auto_upload"] === '' || typeof field["prso_pluploader_auto_upload"] == 'undefined' ) {
				        field["prso_pluploader_auto_upload"] = 'true';
			        }
			        jQuery("#prso_pluploader_auto_upload").val( field["prso_pluploader_auto_upload"] );
			        
			        //Populate max file numbers
			        if( field["prso_pluploader_max_files"] === '' || typeof field["prso_pluploader_max_files"] == 'undefined' ) {
				        field["prso_pluploader_max_files"] = '2';
			        }
			        jQuery("#prso_pluploader_max_files").val( field["prso_pluploader_max_files"] );
			        
		        });
		        
		    });
		 
		</script>
		<?php
	}
	
	public function pluploader_standard_field_settings( $position, $form_id ) {
		
		if( isset($position) && $position == 50 ) {
		?>
			<li class="prso_pluploader_file_extensions_setting field_setting" style="display: list-item;">
               <label for="prso_pluploader_file_extensions">Allowed file extensions</label>
               <input type="text" onkeyup="SetFieldProperty('prso_pluploader_file_extensions', this.value);" size="40" id="prso_pluploader_file_extensions">
               <div><small>Separated with commas (i.e. jpg, gif, png, pdf)</small></div>
            </li>
		<?php
		}
		
	}
	
	public function pluploader_advanced_field_settings( $position, $form_id ) {
		
		if( isset($position) && $position == 0 ) {
		?>
			<li class="prso_pluploader_file_size_setting field_setting" style="display: list-item;">
               <label for="prso_pluploader_file_size">Maximum file size (MB)</label>
               <input type="text" onkeyup="SetFieldProperty('prso_pluploader_file_size', this.value);" size="40" id="prso_pluploader_file_size" >
               <div><small>Max file size in MB (defaults to 1MB)</small></div>
            </li>
            <li class="prso_pluploader_max_files_setting field_setting" style="display: list-item;">
               <label for="prso_pluploader_max_files">Max number of files</label>
               <input type="text" onkeyup="SetFieldProperty('prso_pluploader_max_files', this.value);" size="40" id="prso_pluploader_max_files" >
               <div><small>Number of files users can upload (defaults to 2)</small></div>
            </li>
            <li class="prso_pluploader_auto_upload_setting field_setting" style="display: list-item;">
               <label for="prso_pluploader_auto_upload">Auto Uploading</label>
               <input type="text" onkeyup="SetFieldProperty('prso_pluploader_auto_upload', this.value);" size="40" id="prso_pluploader_auto_upload" >
               <div><small>Files start uploading as they are added to queue (true/false)</small></div>
            </li>
            <li class="prso_pluploader_file_chunk_setting field_setting" style="display: list-item;">
               <label for="prso_pluploader_file_chunk">Activate file chunking</label>
               <input type="text" onkeyup="SetFieldProperty('prso_pluploader_file_chunk', this.value);" size="40" id="prso_pluploader_file_chunk" >
               <div><small>Allows large file uploads (true/false)</small></div>
            </li>
		<?php
		}
		
	}
	
	/**
	* pluploader_enqueue_scripts
	* 
	* Called by 'gform_enqueue_scripts' gravity forms action.
	* Used to enqueue any scripts before form is loaded in front end
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_enqueue_scripts( $form, $ajax ) {
		
		//Init vars
		$load_pluploader = FALSE;
		
		if( isset($form['fields']) ) {
			// cycle through fields to see if prso_gform_pluploader is being used
		    foreach ( $form['fields'] as $field ) {
		    	
		    	//Confirm prso_gform_pluploader custom field is in form
		        if( $field['type'] == 'prso_gform_pluploader') {
					
					//Cache that form contains custom field
					$load_pluploader = TRUE;
					
					//Process field args for Fine Uploader javascript
					$this->activate_plupload_uploader( $form, $field );
					            
		        }
		        
		    }
		}
	    
	    
	    //If form contains field enqueue scripts
	    if( $load_pluploader ) {
		    
		    //Enqueue Fine Uploader core scripts and styles
			$this->enqueue_scripts( TRUE ); 
		    
	    }
	    
	}
	
	/**
	* activate_plupload_uploader
	* 
	* Called during 'gform_enqueue_scripts' action via $this->pluploader_enqueue_scripts()
	* Loops through any custom field options and caches the vars required to
	* activate the Fine Uploader in a class global array - 'prso_pluploader_args'
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function activate_plupload_uploader( $form = array(), $field = array() ) {
		
		//Init vars
		$args = array();
		$output = NULL;
		
		//Set defaults for uploader args
		$defaults['basic']	= array(
			'debug'							=>	'false',
			'button'						=>	'null',
			'multiple'						=>	'true',
			'maxConnections'				=>	3,
			'disableCancelForFormUploads'	=>	'false',
			'autoUpload'					=>	'true'
		);
		
		//Set defaults for validation args
		$defaults['validation']	= array(
			'allowedExtensions'			=>	array(),
			'acceptFiles'				=>	'null',
			'sizeLimit'					=>	0,
			'minSizeLimit'				=>	0,
			'stopOnFirstInvalidFile'	=>	'true'
		);
		
		//Set defaults for chunking args
		$defaults['chunking']	= array(
			'enabled'			=>	false,
			'partSize'			=>	''
		);
		
		if( !empty($field) && isset($field['id']) ) {
			
			//Cache any validation settings for this field
			$args['validation']['allowedExtensions'] = 'jpeg,bmp,png,gif';
			if( isset($field['prso_pluploader_file_extensions']) && !empty($field['prso_pluploader_file_extensions']) ) {
				$file_ext_validation = array();
				
				//Explode comma separated values
				$file_ext_validation = explode( ',', esc_attr($field['prso_pluploader_file_extensions']) );
				
				//Loop array of extensions and form a string for javascript array
				if( !empty($file_ext_validation) && is_array($file_ext_validation) ) {
					
					$args['validation']['allowedExtensions'] = NULL;
					
					foreach( $file_ext_validation as $ext ) {
						$args['validation']['allowedExtensions'].= "{$ext},";
					}
					
				}
				
			}
			
			//Cache max file size validation option
			if( isset($field['prso_pluploader_file_size']) && !empty($field['prso_pluploader_file_size']) ) {
				$size_limit_mb = (int) $field['prso_pluploader_file_size'];
				
				$args['validation']['sizeLimit'] = $size_limit_mb;
				
			} else {
				$args['validation']['sizeLimit'] = 1;
			}
			
			//Cache max number of files option
			$args['max_files'] = 2;
			if( isset($field['prso_pluploader_max_files']) && !empty($field['prso_pluploader_max_files']) ) {
				$max_files	 		= (int) $field['prso_pluploader_max_files'];
				$args['max_files'] 	= $max_files;
			}
			
			//Cache the file chunking options
			$args['chunking']['enabled'] = FALSE;
			if( isset($field['prso_pluploader_file_chunk']) && !empty($field['prso_pluploader_file_chunk']) ) {
				$enable_chunking = $field['prso_pluploader_file_chunk'];
				
				if( !empty($enable_chunking) && $enable_chunking != 'false' ) {
					$args['chunking']['enabled'] = '1mb';
				}
				
			}
			
			//Cache auto upload option
			$args['auto_upload'] = true;
			if( isset($field['prso_pluploader_auto_upload']) && !empty($field['prso_pluploader_auto_upload']) ) {
				
				$auto_upload = esc_attr( $field['prso_pluploader_auto_upload'] );
				
				if( $auto_upload == 'true' ) {
					$auto_upload = true;
				} elseif( $auto_upload == 'false' ) {
					$auto_upload = false;
				}
				
				$args['auto_upload'] 	= $auto_upload;
			}
			
			//Cache the unique field identifier Fine Uploader action
			$args['element'] = 'pluploader_' . $field['id'];
			
			//Cache the form Id of the form this field belongs to
			if( isset($form['id']) ) {
				$args['form_id'] = (int) $form['id'];
			}
			
			//Cache the args
			$this->prso_pluploader_args[$field['id']] = $args;
			
		}
		
	}
	
	/**
	* localize_pluploader_init_vars
	* 
	* Called during 'enqueue_scripts' action if displaying a gform which contains the pluploader custom field
	* Loops through any custom field options and localizes the variables required by the init javascript file to
	* activate the Fine Uploader
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	private function localize_pluploader_init_vars() {
		
		//Init vars
		$pluploader_code 	= NULL;
		$nonce				= NULL;
		$local_vars			= array();
		$local_obj_name		= 'WpPrsoPluploadPluginVars';
		
		if( !empty($this->prso_pluploader_args) && is_array($this->prso_pluploader_args) ) {
			
			//First create nonce string
			$nonce = wp_create_nonce( self::$submit_nonce_key );
			
			//Loop each pluploader field and cache vars required to activate each one
			foreach( $this->prso_pluploader_args as $field_id => $uploader_args ){
				
				//Check for minimum args
				if( isset($uploader_args['element']) ) {
					
					//Plupload element id
					$local_vars[$field_id]['element'] 				= $uploader_args['element'];
					
					//Cache max unmber of file allowed
					$local_vars[$field_id]['max_files'] 			= $uploader_args['max_files'];
					
					//Auto upload when files added
					$local_vars[$field_id]['auto_upload'] 			= $uploader_args['auto_upload'];
					
					//Runtimes
					$local_vars[$field_id]['runtimes'] 				= 'flash,html5,browserplus,silverlight,gears,html4';
					
					//Request url - wp ajax request
					$local_vars[$field_id]['wp_ajax_url'] 			= admin_url('admin-ajax.php');
					
					//Max file size 
					$local_vars[$field_id]['max_file_size'] 		= $uploader_args['validation']['sizeLimit'] . 'mb';
					
					//Enable chunking
					$local_vars[$field_id]['chunking'] 				= $uploader_args['chunking']['enabled'];
					
					//Cache params - Gravity forms Form ID
					$local_vars[$field_id]['params']['form_id'] 	= $uploader_args['form_id'];
					
					//Cache params - Gravity forms Field ID
					$local_vars[$field_id]['params']['field_id'] 	= $field_id;
					
					//Cache params - WP Nonce value
					$local_vars[$field_id]['params']['nonce'] 		= $nonce;
					
					//Cache filter - allowed filesize
					$local_vars[$field_id]['filters']['files'] 		= $uploader_args['validation']['allowedExtensions'];
					
					//Cache url to Flash file
					$local_vars[$field_id]['flash_url'] 			= includes_url('/js/plupload/plupload.flash.swf');
					
					//Cache url to Silverlight url
					$local_vars[$field_id]['silverlight_url'] 		= includes_url('/js/plupload/plupload.silverlight.xap');
					
				}
			}
			
			if( !empty($local_vars) ) {
				//Locallize vars for plupload script
				wp_localize_script( 'prso-pluploader-init', $local_obj_name, $local_vars );
			}
			
		}

	}
	
	/**
	* pluploader_ajax_submit
	* 
	* Called during 'wp_ajax_nopriv_prso-pluploader-submit' && 'wp_ajax_prso-pluploader-submit' ajax actions
	* Handles ajax request from fine uploader script, checks nonce, grabs validation options from gforms form meta
	* then passes the validation options to the main File Uploader php script to process and move to server
	*
	* NOTE:: If validation options are not set in gforms for this field the script will default to just images <= 0.5mb
	*		Script will not accept any .js or .php ot .html extensions regardless of validation settings.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function plupload_ajax_submit() {
		
		//Init vars
		$validation_args 	= array();
		$nonce_value		= NULL;
		
		//Cache the nonce for this action
		if( isset($_REQUEST['nonce']) ) {
			$nonce_value = esc_attr( $_REQUEST['nonce'] );
		}
		
		//First check nonce field
		if( !isset($nonce_value) || !wp_verify_nonce($nonce_value, self::$submit_nonce_key) ) {
			
			$result['error'] = 'Server error.';
			
			header("Content-Type: text/plain");
			echo json_encode($result);

			exit;
			
		}
		
		//Cache any validation settings passed from gforms
		$validation_args = $this->pluploader_file_validation_settings();
		
		// Include the uploader class
		require_once $this->plugin_includes . '/qqFileUploader.php';
		
		$uploader = new qqFileUploader();
		
		// Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
		if( isset($validation_args['allowedExtensions']) && is_array($validation_args['allowedExtensions']) ) {
			$uploader->allowedExtensions = $validation_args['allowedExtensions'];
		} else {
			//Set security default - images only
			$uploader->allowedExtensions = array('jpeg', 'bmp', 'png', 'gif');
		}
		
		// Specify max file size in bytes.
		if( isset($validation_args['sizeLimit']) && !empty($validation_args['sizeLimit']) ) {
			$uploader->sizeLimit = $validation_args['sizeLimit'];
		} else {
			//Set security default - 0.5 MB
			$uploader->sizeLimit = 0.5 * 1024 * 1024;
		}
		
		//Activate file chunking
		if( isset($validation_args['enable_chunked']) ) {
			$uploader->enable_chunked = $validation_args['enable_chunked'];
		}
		
		
		
		//Get wordpress uploads dir path
		$wp_uploads 		= wp_upload_dir();
		$wp_uploads_path 	= NULL;
		$tmp_upload_path	= NULL;
		
		if( isset($wp_uploads['basedir']) ) {
			$wp_uploads_path = $wp_uploads['basedir'];
			$tmp_upload_path = $wp_uploads_path . '/' . self::$prso_pluploader_tmp_dir_name;
			
			// If you want to use resume feature for uploader, specify the folder to save parts.
			$uploader->chunksFolder = $tmp_upload_path . '/chunks';
		}
		
		// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
		$result = $uploader->handleUpload($tmp_upload_path);
		
		// To save the upload with a specified name, set the second parameter.
		// $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());
		
		//Encrypt filename before passing back to the dom
		if( isset($result['success']['file_id']) ) {
			$result['success']['file_id'] = $this->name_encrypt( $result['success']['file_id'] );
		}
		
		header("Content-Type: text/plain");
		die( json_encode($result) );
	}
	
	/**
	* pluploader_file_validation_settings
	* 
	* Called by $this->pluploader_ajax_submit()
	* Gets validation options for current field from the gform form meta data
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_file_validation_settings() {
		
		//Init vars
		$form = array();
		$current_form_id = NULL;
		$current_field_id = NULL;
		$validation_args = array();
		
		//Cache the current form ID
		if( isset($_REQUEST['currentFormID']) && isset($_REQUEST['currentFieldID']) ) {
			
			//Allow devs to hook before we get the form's validation settings
			do_action('prso_gform_pluploader_pre_get_server_validation', TRUE);
			
			$current_form_id = (int) $_REQUEST['currentFormID'];
			
			$current_field_id = (int) $_REQUEST['currentFieldID'];
			
			//Get the form meta from gforms table
			$form = RGFormsModel::get_form_meta($current_form_id);
			
			if( isset($form['fields']) && !empty($form['fields']) ) {
				
				//Loop form fields and find our field
				foreach( $form['fields'] as $form_field ) {
					
					//Detect and confirm field ID
					if( isset($form_field['id']) && $form_field['id'] === $current_field_id ) {
						
						//This is our field, detect and cache the validation options
						if( isset($form_field['prso_pluploader_file_extensions']) && !empty($form_field['prso_pluploader_file_extensions']) ) {
							$validation_args['allowedExtensions'] = explode(',', $form_field['prso_pluploader_file_extensions']);
						}
						
						if( isset($form_field['prso_pluploader_file_size']) && !empty($form_field['prso_pluploader_file_size']) ) {
							$validation_args['sizeLimit'] = $this->toBytes( $form_field['prso_pluploader_file_size'] . 'm' );
						}
						
						if( isset($form_field['prso_pluploader_file_chunk']) && !empty($form_field['prso_pluploader_file_chunk']) ) {
							$validation_args['enable_chunked'] = (bool) $form_field['prso_pluploader_file_chunk'];
						}
						
					}
					
				}
				
			}
			
		}
		
		//Allow devs to hook before we get the form's validation settings
		$validation_args = apply_filters('prso_gform_pluploader_server_validation_args', $validation_args, $form);
		
		return $validation_args;
		
	}
	
	/**
	* save_uploads_as_wp_attachments
	* 
	* Called by 'gform_after_submission' gravity forms action.
	* Detects any pluploader fields, then calls $this->process_uploads
	* to add a wp media library (attachment) post for each file as well as
	* move said file into the wp uploads folder on the server. Then saves
	* a serialized array of wp attachment post id's for each file uploaded
	* into the gforms entry's details table.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function save_uploads_as_wp_attachments( $entry, $form ) {
		
		//Init vars
		$is_pluploader 			= FALSE;
		$pluploader_field_data	= array();
		$wp_attachment_data		= array();
		
		//First check that this form is using fine uploader to upload files
		if( isset($form['fields']) && !empty($form['fields']) ) {
			
			//Loop each field and try to find a 'prso_gform_pluploader' custom field
			foreach( $form['fields'] as $field ) {
				if( isset($field['type']) && $field['type'] === 'prso_gform_pluploader' ) {
					//We have found at least one pluploader field
					$is_pluploader = TRUE;
					break;
				}
			}
			
		}
		
		//If this forms contains a fine uploader custom field lets process the uploaded files
		if( $is_pluploader === TRUE ) {
			
			//First let's cache file upload data for each of our pluploader fields
			if( isset($_POST['plupload']) && !empty($_POST['plupload']) ) {
				
				//Sanitize
				if( is_array($_POST['plupload']) ) {
					foreach( $_POST['plupload'] as $key => $uploaded_files_array ) {
						$pluploader_field_data[$key] = array_map( 'esc_attr', $uploaded_files_array );
					}
				}
				
				//If there is some field data to process let's process it!
				$wp_attachment_data = $this->process_uploads( $pluploader_field_data, $entry );
				
				//Action hook for successfully completed uploads
				do_action( 'prso_gform_pluploader_processed_uploads', $wp_attachment_data, $entry, $form );
				
			}

		}
		
	}
	
	/**
	* process_uploads
	* 
	* Called by $this->save_uploads_as_wp_attachments()
	* Loops the encoded file names of each upload from the form
	* calling methods to create a wp attachment and move the file into the uploads dir
	* Also calls a method to add a serialized array of all upload attachment post id's into
	* the gforms entry details table.
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function process_uploads( $pluploader_field_data = array(), $entry = array() ) {
		
		//Init vars
		$pluploader_wp_attachment_data = array(); //Cache the attachement post id's of each uploaded file for each field 
		
		if( !empty($pluploader_field_data) && is_array($pluploader_field_data) &&  isset($entry['id'], $entry['form_id']) ) {
			
			//Loop each pluploader field and process it's files
			foreach( $pluploader_field_data as $field_id => $file_uploads_array ) {
				
				//Loop each uploaded file for this pluploader field and process them
				if( is_array($file_uploads_array) && !empty($file_uploads_array) ) {
					
					foreach( $file_uploads_array as $upload_id => $uploaded_file ) {
						
						//Init vars
						$file_base_name = NULL;
						$attach_id		= NULL;
						
						//Decrypt file id to get the true file name
						$file_base_name = $this->name_decrypt( esc_attr($uploaded_file) );
						
						//Call function to add this file to wp media library and cache it's post id 
						if( $attach_id = $this->insert_attachment( $upload_id, $file_base_name, $entry ) ) {
							$pluploader_wp_attachment_data[$field_id][] = $attach_id;
						}
						
					}
					
				}
				
			}
			
			//Finally loop the array of pluploader_wp_attachment_data as update each gform entry
			if( !empty($pluploader_wp_attachment_data) ) {
				
				foreach( $pluploader_wp_attachment_data as $field_id => $field_upload_ids ) {
					$result = FALSE;
					if( !empty($field_upload_ids) ) {
						$result = $this->gform_update_lead_details( $entry['id'], $entry['form_id'],$field_id, maybe_serialize($field_upload_ids) );
					}
				}
				
			}
			
		}
		
		return $pluploader_wp_attachment_data;
	}
	
	/**
	* insert_attachment
	* 
	* Called by $this->process_uploads().
	* Moves uploaded file out of fine uploads tmp dir and into wp uploads dir
	* Then creates a wp attachment post for the file returning it's attachment post id
	* 
	* @access 	public
	* @returns	int		$attach_id - WP attachment post id for file
	* @author	Ben Moody
	*/
	private function insert_attachment( $upload_id = NULL, $file_base_name = NULL, $entry = array() ) {
		
		//Init vars
		$pluploader_tmp_dir		= NULL;
		$uploaded_file_path	= NULL;
		$wp_dest_file_path	= NULL;
		$wp_upload_dir 		= NULL;
		$wp_filetype		= array();
		$attachment			= array();
		$attach_id			= FALSE;
		$attach_data		= array();
		$post_title			= NULL;
		$move_status		= FALSE;
		
		if( isset($upload_id, $file_base_name, $entry['id'], $entry['form_id']) ) {
			
			//Allow devs to hook into the functio before getting wp info
			do_action( 'prso_gform_pluploader_pre_insert_attachment' );
			
			//Cache info on the wp uploads dir
			$wp_upload_dir = wp_upload_dir();
			
			//Cache path to fine uploader tmp directory
			$pluploader_tmp_dir = $wp_upload_dir['basedir'] . '/' . self::$prso_pluploader_tmp_dir_name . '/';
			
			//FILTER - Allow devs to filter the wp_upload_dir array before inserting attachment
			$wp_upload_dir = apply_filters( 'prso_gform_pluploader_wp_upload_dir', $wp_upload_dir );
			
			//Cache tmp location of file on server
			$uploaded_file_path = $pluploader_tmp_dir . $file_base_name;
			
			//Cache destination file path
			$wp_dest_file_path = $wp_upload_dir['path'] . '/' . $file_base_name;
			
			//First let's move this file into the wp uploads dir structure
			$move_status = $this->move_file( $uploaded_file_path, $wp_dest_file_path );
			
			//Check that the file we wish to add exsists
			if( $move_status === TRUE ) {
				
				//Cache file type
				$wp_filetype = wp_check_filetype( $wp_dest_file_path, null );
				
				//Error check
				if( !empty($wp_filetype) && is_array($wp_filetype) ) {
				
					//Create a unique and descriptive post title - associate with form and entry
					$post_title = 'Form ' . esc_attr($entry['form_id']) . ' Entry ' . esc_attr($entry['id']) . ' Fileupload ' . ($upload_id + 1);
					
					//Create the attachment array required for wp_insert_attachment()
					$attachment = array(
						'guid'				=>	$wp_upload_dir['url'] . '/' . basename($wp_dest_file_path),
						'post_mime_type'	=>	$wp_filetype['type'],
						'post_title'		=>	$post_title,
						'post_content'		=>	'',
						'post_status'		=> 'inherit'
					);
					
					//Insert attachment
					$attach_id = wp_insert_attachment( $attachment, $wp_dest_file_path );
					
					//Error check
					if( $attach_id !== 0 ) {
						
						//Generate wp attachment meta data
						if( file_exists(ABSPATH . 'wp-admin/includes/image.php') ) {
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$attach_data = wp_generate_attachment_metadata( $attach_id, $wp_dest_file_path );
							wp_update_attachment_metadata( $attach_id, $attach_data );
						}
						
					} else {
						$attach_id = FALSE;
					}
					
				}
				
			}			
			
		}
		
		//Error detected with file attachment, delete file upload from server
		if( $attach_id === FALSE ) {
			if( file_exists($uploaded_file_path) ) {
				unlink( $uploaded_file_path );
			} elseif( file_exists($wp_dest_file_path) ) {
				unlink( $wp_dest_file_path );
			}
		}
		
		return $attach_id;
	}
	
	/**
	* move_file
	* 
	* Helper to move a file from one path to another
	* Paths are full paths to a file including filename and ext
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function move_file( $current_path = NULL, $destination_path = NULL ) {
		
		//Init vars
		$result = FALSE;
		
		if( isset($current_path) && file_exists($current_path) ) {
			
			//First check if destination dir exists if not make it
			if( !file_exists(dirname($destination_path)) ) {		
		        mkdir( dirname($destination_path) );
	        }
			
			if( file_exists(dirname($destination_path)) ) {
			        
		        //Move file into dir
		        if( copy($current_path, $destination_path) ) {
			        unlink($current_path);
			        
			        if( file_exists($destination_path) ) {
				        $result = TRUE;
			        }
			        
		        }
		        
	        }
			
		}
		
		return $result;
	}
	
	/**
	* gform_update_lead_details
	* 
	* Called by $this->process_uploads()
	* Updates the gforms details with a serilized array of all the files uploaded
	* into this form entry.
	* Note that the serialized array is inserted into both the lead_details_table and lead_details_long_table
	* It's ok that the string may get truncated in the std details table as gforms will then grab the full string
	* from the long details table.
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function gform_update_lead_details( $lead_id = NULL, $form_id = NULL,$field_id = NULL, $value = NULL ) {
		
		//Init vars
		global $wpdb;
		$results = array();
		
		if( isset($lead_id, $form_id, $field_id, $value) ) {
			
			$lead_details_table_name =  RGFormsModel::get_lead_details_table_name();
	        $lead_details_long_table_name =  RGFormsModel::get_lead_details_long_table_name();
	        
	        $results = $wpdb->get_results(
	        	"SELECT lead_detail_id FROM {$lead_details_table_name} d
	        	 INNER JOIN {$lead_details_long_table_name} l ON d.id = l.lead_detail_id
	        	 WHERE lead_id = {$lead_id} AND field_number = {$field_id}"
	        );
	        
	        //Insert file upload data
	        if( empty($results) ) {
		        
		        //Get lead details id
		        $results = $wpdb->get_results(
		        	"SELECT id FROM {$lead_details_table_name} 
		        	 WHERE lead_id = {$lead_id} AND field_number = {$field_id}"
		        );
		        
		        //Insert value into long details table
		        if( isset($results[0]->id) ) {
		        
			        //As gforms only looks at details long table if value maxs out the std table
			        //Update std table before long table - value will be truncated by mysql
			        //No probs as it will be stored ok in long table next
			        $wpdb->query(
			        	$wpdb->prepare(
			        		"UPDATE $lead_details_table_name SET value = %s 
			        		 WHERE id = %d", $value, $results[0]->lead_detail_id
			        	)
			        );
			        
			        $wpdb->query(
			        	$wpdb->prepare(
			        		"INSERT INTO $lead_details_long_table_name (lead_detail_id, value) 
			        		 VALUES(%d, %s)", $results[0]->id, $value
			        	)
			        );
			        
		        } elseif( empty($results) ) { //Insert a value into lead_detail table then detail_long tbl
			        
			        //First insert a new value into lead_detail table
			        $wpdb->query(
			        	$wpdb->prepare(
			        		"INSERT INTO $lead_details_table_name (lead_id, form_id, field_number, value) 
			        		 VALUES(%d, %s, %s, %s)", $lead_id, $form_id, $field_id, $value
			        	)
			        );
			        
			        $results = $wpdb->insert_id;
			        
			        if( isset($results) && $results !== 0 ) {
				        
				        //Now lets insert the array of upload ids into long table
				        $wpdb->query(
				        	$wpdb->prepare(
				        		"INSERT INTO $lead_details_long_table_name (lead_detail_id, value) 
				        		 VALUES(%d, %s)", $results, $value
				        	)
				        );
				        
			        } else {
				        return FALSE;
			        }
			        
		        }
		        
		        
	        } elseif( isset($results[0]->lead_detail_id) ) { //Update upload details
		        
		        //As gforms only looks at details long table if value maxs out the std table
		        //Update std table before long table - value will be truncated by mysql
		        //No probs as it will be stored ok in long table next
		        $wpdb->query(
		        	$wpdb->prepare(
		        		"UPDATE $lead_details_table_name SET value = %s 
		        		 WHERE id = %d", $value, $results[0]->lead_detail_id
		        	)
		        );
		        
		        $wpdb->query(
		        	$wpdb->prepare(
		        		"UPDATE $lead_details_long_table_name SET value = %s 
		        		 WHERE lead_detail_id = %d", $value, $results[0]->lead_detail_id
		        	)
		        );
		        
	        }
			
			return TRUE;
			
		} 
		
		return FALSE;
	}
	
	/**
	* pluploader_entry_index_table_value
	* 
	* Called by 'gform_get_input_value' gravity forms filter.
	* Detects a pluploader field, and overrides the serialized field data with a message.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_entry_index_table_value( $value, $lead, $field, $input_id ) {
		
		//First detect one of our pluploader fields
		if( !isset($_GET['lid']) ) {
			
			if( isset($field['type']) &&  $field['type'] === 'prso_gform_pluploader' ) {
				return __("View form entry for file details.", "prso-gforms-plupload");
			}
			
		}
		
		return $value;
	}
	
	/**
	* pluploader_entry_field_value
	* 
	* Called by 'gform_entry_field_value' gravity forms filter.
	* Detects a pluploader field, unserializes the attachment id's array
	* uses id's to create a link to the file's wp media library post edit page
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_entry_field_value( $value, $field, $lead, $form ) {
		
		//Init vars
		$post_edit_url			= NULL;
		$file_attachment_ids 	= array();
		$file_attachment_urls	= array();
		$output					= NULL;
		
		//First detect one of our pluploader uploader fields
		if( isset($field['type']) &&  $field['type'] === 'prso_gform_pluploader' ) {
			
			//Ok, this is one of our fields, lets process the value
			
			//First cache the base url for the wp post edit page
			$post_edit_url = get_admin_url(NULL, '/post.php');
			
			//Unserialize the array of attachment post id's
			if( !empty($value) ) {
				
				$file_attachment_ids = maybe_unserialize( $value );
				
				//Loop the array of file attachments and cache the url to each attchments edit view
				foreach( $file_attachment_ids as $key => $file_id ) {
					
					//Init vars
					$post 		= NULL;
					$path_info	= NULL;
					
					//Get the file's ext via post info
					$post = get_post($file_id);
					if( isset($post->guid) ) {
						$path_info = pathinfo( $post->guid );
						
						//Cache the file's extension in the urls array
						if( isset($path_info['extension']) ) {
							$file_attachment_urls[$key]['ext'] = '.' . $path_info['extension'];
						} else {
							$file_attachment_urls[$key]['ext'] = 'N/A';
						}
						
					}
					
					$file_attachment_urls[$key]['url'] = add_query_arg( 
						array(
							'post'		=>	(int) $file_id,
							'action'	=>	'edit'
						),
						esc_url($post_edit_url)
					);
					
					//Filter hook for wp attachment link
					$file_attachment_urls[$key]['url'] = apply_filters( 'prso_gform_pluploader_entry_attachment_links', $file_attachment_urls[$key]['url'], $file_id, $post );
					
				}
			}
			
			//Convert each file attachment url into a link for the user to click
			if( !empty($file_attachment_urls) ) {
				
				foreach( $file_attachment_urls as $key => $file_info ) {
					
					if( isset($file_info['url'], $file_info['ext']) ) {
						
						//Cache the file upload number
						$file_number = $key + 1;
						
						$output.= "<a title='Click to view file #{$file_number}' href='{$file_info['url']}' target='_blank'>View File #{$file_number} ({$file_info['ext']}), </a>";
						
					} elseif( isset($file_info['url']) ) {
						
						//Cache the file upload number
						$file_number = $key + 1;
						
						$output.= "<a title='Click to view file #{$file_number}' href='{$file_info['url']}' target='_blank'>View File #{$file_number} (External), </a>";
						
					}
					
					
				}
				
				//Return our list of files to the user
				$value = $output;
				
			}
			
		}
		
		return $value;
	}
	
	/**
	* pluploader_delete_lead
	* 
	* Called by 'gform_delete_lead' gravity forms action.
	* Called when a gform entry is deleted.
	* Detects if the form contains any plupload fields, gets the wp attachment post id's
	* for each upload. then calls wp_delete_attachment to remove file from media library & server
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_delete_lead( $lead_id ) {
		
		//Init vars
		$delete_files 			= FALSE;
		$attachments			= array();
		$file_attachment_ids 	= array();
		$form_id				= NULL;
		$form 					= array();
		$field_ids				= array();
			
		if( isset($lead_id) && isset($_GET['id']) ) {
			
			//First check if we should delete entry files
			$delete_files = $this->delete_files_check( $lead_id );
			
			if( $delete_files === TRUE ) {
				
				$form_id = (int) $_GET['id'];
			
				$form = RGFormsModel::get_form_meta($form_id);
				
				if( isset($form['fields']) ) {
					
					//Loop fields and see if this entry's form has a pluploader field
					foreach( $form['fields'] as $field ) {
						if( $field['type'] === 'prso_gform_pluploader' ) {
							$field_ids[] = $field['id'];
						}
					}
					
				}
				
				//Loop any pluploader fields and get any uploads, then delete the wp attachements for each
				if( !empty($field_ids) ) {
					foreach( $field_ids as $field_id ) {
						$attachments[] = $this->get_lead_detail_long_value( $lead_id, $field_id );
					}
				}
				
				//Now loop through each file attachment id and force delete them
				if( !empty($attachments) ) {
					foreach( $attachments as $attachment ) {
						if( is_array($attachment) ) {
							foreach( $attachment as $attachment_id ) {
								wp_delete_attachment( $attachment_id, TRUE );
							}
						}
					}
				}
				
			}
			
		}
		
	}
	
	/**
	* delete_files_check
	* 
	* Helper to check if plupload files should be deleted for
	* an entry
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	private function delete_files_check( $lead_id = NULL ) {
		
		//Init vars
		$delete_files = FALSE;
		
		if( isset($lead_id) ) {
			//Get entry meta for delete files option
			$delete_files = gform_get_meta( $lead_id, self::$delete_files_meta_key );
			
			if( $delete_files === 'checked' ) {
				return TRUE;
			}
			
		}
		
		return FALSE;
	}
	
	/**
	* get_lead_detail_long_value
	* 
	* Helper to get the 'long value' for a specific entry field
	* 
	* @param	int			$lead_id	Entry ID
	* @param	int			$field_id	Field ID
	* @return	string		$results	Value from 'long value' gforms table
	* @access 	public
	* @author	Ben Moody
	*/
	private function get_lead_detail_long_value( $lead_id = NULL, $field_id = NULL ) {
		
		//Init vars
		global $wpdb;
		$lead_detail_id = NULL;
		$results 		= NULL;
		
		if( isset($lead_id, $field_id) ) {
			
			$lead_details_table_name =  RGFormsModel::get_lead_details_table_name();
	        $lead_details_long_table_name =  RGFormsModel::get_lead_details_long_table_name();
	        
	        $lead_detail_id = $wpdb->get_results(
	        	"SELECT lead_detail_id FROM {$lead_details_table_name} d
	        	 INNER JOIN {$lead_details_long_table_name} l ON d.id = l.lead_detail_id
	        	 WHERE lead_id = {$lead_id} AND field_number = {$field_id}"
	        );
			
			if( isset($lead_detail_id[0]->lead_detail_id) ) {
			
				$lead_detail_id = $lead_detail_id[0]->lead_detail_id;
				
				$results = $wpdb->get_results(
		        	"SELECT $lead_details_long_table_name.value FROM {$lead_details_long_table_name}
		        	 WHERE lead_detail_id = {$lead_detail_id}"
		        );
			}
			
			if( isset($results[0]->value) ) {
				$results = maybe_unserialize( $results[0]->value );
			}
			
		}
		
		return $results;
	}
	
	/**
	* pluploader_trash_checkbox
	* 
	* Called by 'gform_update_status' gravity forms action.
	* Handles the meta data for an entry relating to the deletion
	* of any plupload files attached the entry.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_trash_checkbox( $lead_id, $property_value, $previous_value ) {
		
		if( isset($property_value) && $property_value === 'trash' ) {
			
			if( isset($_POST['prso_pluploader_delete_uploads']) && $_POST['prso_pluploader_delete_uploads'] === 'on' ) {
				//Update delete file meta for this entry
				gform_update_meta( $lead_id, self::$delete_files_meta_key, 'checked' );
			} else {
				//Update delete file meta for this entry
				gform_delete_meta( $lead_id, self::$delete_files_meta_key );
			}
			
		}
		
	}
	
	/**
	* localize_script_prso_pluploader_entries
	* 
	* Called by $this->enqueue_scripts().
	* Localizes some variables for use in a js script that adds a file delete option
	* to the entry post edit page and warns users of file deletion when sending an entry to the trash
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	private function localize_script_prso_pluploader_entries() {
		
		//Init vars
		$input_field	= NULL;
		$message		= __("NOT deleting uploaded files. To delete any uploaded files, be sure to check Delete Uploads", "prso-gforms-plupload");	
		$delete_files 	= NULL;
		$entry_id 		= NULL;
		
		//Set the checkbox input field html
		$input_field = '<div style="padding:10px 10px 10px 0;"><input id="prso_pluploader_delete_uploads" type="checkbox" onclick="" name="prso_pluploader_delete_uploads"><label for="prso_fineup_delete_uploads">&nbsp;'. __("Delete Plupload Uploaded Files", "prso-gforms-plupload") .'</label></div>';
		
		//Get entry meta for delete files option
		if( isset($_GET['lid']) ) {
			$entry_id = (int) $_GET['lid'];
		}
		
		$delete_files = gform_get_meta( $entry_id, self::$delete_files_meta_key );
		
		wp_localize_script( 
			'prso-pluploader-entries', 
			'prso_gforms_pluploader', 
			array('file_delete_message' => $message, 'file_delete_meta' => esc_attr($delete_files), 'input_field_html' => $input_field) 
		);
		
	}
	
	/**
	* pluploader_entry_apply_button
	* 
	* Called by 'gform_entry_apply_button' gravity forms filter.
	* Fitlers the entry index view bulk action apply button adding a js confirm dialog box for onclick event
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function pluploader_entry_apply_button( $apply_button_html = NULL ) {
		
		//Init vars
		$output 	= NULL;
		$message	= __("Are You sure? Uploaded files will be deleted! To keep files for an entry, delete that specific entry only, don\'t use Bulk Action.", "prso-gforms-plupload");
		$on_click	= "if(jQuery('#bulk_action').val() === 'trash'){ if(!confirm('". $message ."')){return false;} }return handleBulkApply('bulk_action');";
		
		$output = $apply_button_html;
		
		if( isset($apply_button_html) ) {
			
			//Cache the html
			ob_start();
			?>
			<input type="submit" class="button" value="<?php _e("Apply", "gravityforms"); ?>" onclick="<?php echo $on_click; ?>" />
			<?php
			$output = ob_get_contents();
			ob_end_clean();

		}
		
		return $output;
	}
	
	/**
	* name_encrypt
	* 
	* Called by $this->pluploader_ajax_submit()
	* Used to encrypt the file name before sending back to DOM to be stored in input field
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	private function name_encrypt( $file_name = NULL ) {
		
		$key = self::$encrypt_key;
		
		if( isset($file_name, $key) ) {
			return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $file_name, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
		}
		
	}
	
	/**
	* name_decrypt
	* 
	* Decrytps file names that have been store in form input fields
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	private function name_decrypt( $file_name = NULL ) {
		
		$key = self::$encrypt_key;
		
		if( isset($file_name, $key) ) {
			return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($file_name), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
		}
		
	}
	
	/**
     * Converts a given size with units to bytes.
     * @param string $str
     */
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
	
}