jQuery(document).ready(function(){
					
	var file_checkbox	= null;
	var trash_onclick 	= null;
	var checkbox_value	= null;
	var publish_action	= null;
	
	file_checkbox = '<div style="padding:10px 10px 10px 0;"><input id="prso_fineup_delete_uploads" type="checkbox" onclick="" name="prso_fineup_delete_uploads"><label for="prso_fineup_delete_uploads"> Delete Fine Uploader Files</label></div>';
	
	//Check if we are on the trash edit page
	publish_action = jQuery('div#publishing-action .button-primary');
	
	//Prepend checkbox html above trash link
	jQuery('div#major-publishing-actions').prepend(file_checkbox);
	
	//If NOT trash edit view
	if( publish_action.length !== 0 ) {
		
		//Remove the default onclick
		jQuery('a.submitdelete').removeAttr('onclick');
		
		//Intercept trash click
		jQuery('a.submitdelete').click(function(event){
			//Warn user that not deleting files unless checbox is checked
			if( !jQuery('#prso_fineup_delete_uploads').is(':checked') ) {
				
				if( !confirm(prso_gforms_fineup.file_delete_message) ) {
					event.preventDefault();
				} else {
					//Carry out gform default actions
					prsoGformsTrashActions();
				}
				
			} else {
				//Carry out gform default actions
				prsoGformsTrashActions();
			}
			
		})
		
	}
	
	//Get delete file meta for this entry and set the delete checkbox
	if( prso_gforms_fineup.file_delete_meta === 'checked' ) {
		jQuery('#prso_fineup_delete_uploads').attr('checked', 'checked');
	} else {
		jQuery('#prso_fineup_delete_uploads').removeAttr('checked');
	}
	
	//Carry out gform default actions
	function prsoGformsTrashActions() {
		
		jQuery('#action').val('trash'); 
		jQuery('#entry_form').submit();
		
	}
	
})