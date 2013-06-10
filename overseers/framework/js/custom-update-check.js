jQuery(document).ready( function($) {
	
	jQuery('.version_to_check').each( function(e) {
		
		var check_url 			= jQuery(this).find('.check_url').val();
		var the_slug 			= jQuery(this).find('.folder_slug').val();
		var current_version 	= jQuery(this).find('.current_version').val();
		var the_name 			= jQuery(this).find('.plugin_name').val();
		var the_nest			= jQuery(this).find('.nest_url').val();
		
		var the_container 		= jQuery(this);
		
		check_url = check_url + '?p=' + the_slug + '&cv=' + current_version + '&n=' + the_name + '&nest=' + the_nest;
		
		$.ajax( 
		{
			url: check_url,
			beforeSend:function()
			{
				the_container.html('checking for updates ...');
			},			
			success:function( result )
			{
				the_container.html(result);
			}
		});
		
	} );
	  
});


