
jQuery(document).ready(function()
{
	var install_script_url = jQuery('#install_script_url').val();
	var activate_script_url = jQuery('#activate_script_url').val();
	
	jQuery('.plugin_install').click( function()
	{
		var nextItem = jQuery(this);
		var nextParent = jQuery(this).parents('.install_status_container');
		
	  	jQuery.ajax(
		{
			type: "POST",
			url: install_script_url,
			data: "plugin_url=" + nextItem.attr( 'rel' ) + "&plugin_file=" + nextItem.attr( 'alt' ),
			beforeSend: function()
			{
				nextParent.html( '<span class="label">processing...</span>' );
			},
			success: function( result, status, xhr )
			{
				var data = jQuery.parseJSON( result );
				
				if( data == 'success' )
				{
					nextParent.html( '<span class="label label-success">complete</span>' );
					nextParent.parent('tr').find('.plugin_activate').css( 'display', 'block' );
				}
				else
				{
					nextParent.html( '<span class="label label-important">problem - check logs : (</span>' );
				}
			},
			complete: function( msg, status )
			{
				if( status == 'error' )
				{
					nextParent.html( '<span class="label label-important">problem - check logs : (</span>' );
				}
			}
		});
	});	
	
	jQuery('.plugin_activate').click( function()
	{
		var nextItem = jQuery(this);
		var nextParent = nextItem.parent();
		
	  	jQuery.ajax(
		{
			type: "POST",
			url: activate_script_url,
			data: "plugin_url=" + nextItem.attr( 'rel' ),
			beforeSend: function()
			{
				nextParent.html( '<span class="label">processing...</span>' );
			},
			success: function( result, status, xhr )
			{
				var data = jQuery.parseJSON( result );
				
				if( data == 'success' )
				{
					nextParent.html( '<span class="label label-success">complete</span>' );
				}
				else
				{
					nextParent.html( '<span class="label label-important">problem - check logs : (</span>' );
				}
			},
			complete: function( msg, status )
			{
				if( status == 'error' )
				{
					nextParent.html( '<span class="label label-important">problem - check logs : (</span>' );
				}
			}
		});
	});	
});