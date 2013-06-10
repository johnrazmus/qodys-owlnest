function CustomGroupToggle( thing_to_show, group_to_hide )
{
	jQuery( group_to_hide ).each( function(e) {
		
		if( jQuery(this).attr('id') != jQuery(thing_to_show).attr('id') )
			jQuery(this).slideUp();
		else
			jQuery(thing_to_show).slideDown();
	} );
}

jQuery(document).ready( function()
{
	var popover_title = 'Unlockable Feature';
	var popover_content = '<span style="font-size:13px;">This feature is only available to Pro members of Qody\'s Nexus.</span> <p style="text-align:left; font-weight:bold;"><a target="_blank" href="http://qody.co/owl/nexus-membership/">Learn how to unlock</a><p>';
	var popover_trigger = 'hover';
	var popover_delay = { show: 0, hide: 8000 };
	
	jQuery('.nexus_member_only').popover( {
		title : popover_title,
		html: true,
		content : popover_content,
		placement : 'right',
		trigger : popover_trigger,
		delay: popover_delay
	} );
	
	jQuery('.btn').attr( 'data-loading-text', 'processing...' );
} );