function QodyThumbSetHTML(html, id, post_type, the_action )
{
	jQuery('.featured-' + post_type + '-' + id).each( function(e)
	{
		jQuery(this).parent().parent().html(html);
	} );
	
	// this is only for the optiner
	if( jQuery('.optiner_product_image') )
	{
		if( id == 2 )
		{
			AlterProductImage( jQuery(html).find('img').attr( 'src' ) );
		}
		else if( id == 3 )
		{
			AlterSubmitButtonImage( jQuery(html).find('img').attr( 'src' ) );
		}
	}
};

function QodyThumbSetThumbnailID(thumb_id, id, post_type)
{
	var field = jQuery('input[value=_qody_' + post_type + '_' + id + '_thumbnail_id]', '#list-table');
	if ( field.size() > 0 ) {
		jQuery('#meta\\[' + field.attr('id').match(/[0-9]+/) + '\\]\\[value\\]').text(thumb_id);
	}
};

function QodyThumbRemoveThumbnail(id, post_type, nonce)
{
	jQuery.post( ajaxurl,
	{
		action:'set-qody-' + post_type + '-' + id + '-thumbnail', post_id: jQuery('#post_ID').val(), thumbnail_id: -1, _ajax_nonce: nonce, cookie: encodeURIComponent(document.cookie)
	},
	function(str)
	{
		if( str == '0' )
		{
			alert( setPostThumbnailL10n.error );
		}
		else
		{
			QodyThumbSetHTML(str, id, post_type, 'remove');
			
			// this is only for the optiner
			if( jQuery('.optiner_product_image') )
			{
				AlterProductImage( '' );
			}
		}
	} );
};


function QodyThumbSetThumbnail(thumb_id, id, post_type, nonce)
{
	var $link = jQuery('a#qody-' + post_type + '-' + id + '-thumbnail-' + thumb_id);
	
	$link.text( setPostThumbnailL10n.saving );
	jQuery.post(ajaxurl, {
		action:'set-qody-' + post_type + '-' + id + '-thumbnail', post_id: post_id, thumbnail_id: thumb_id, _ajax_nonce: nonce, cookie: encodeURIComponent(document.cookie)
	}, function(str){
		var win = window.dialogArguments || opener || parent || top;
		$link.text( setPostThumbnailL10n.setThumbnail );
		if ( str == '0' ) {
			alert( setPostThumbnailL10n.error );
		} else {
			$link.show();
			$link.text( setPostThumbnailL10n.done );
			$link.fadeOut( 2000, function() {
				jQuery('tr.qody-' + post_type + '-' + id + '-thumbnail').hide();
			});
			win.QodyThumbSetThumbnailID(thumb_id, id, post_type);
			win.QodyThumbSetHTML(str, id, post_type, 'add');
		}
	}
	);
}