<?php
require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/wp-load.php' );

$response = array();

if( $_POST )
{
	$qodys_framework->Page('configuration')->HandleMembershipKeySave();
	
	foreach( $_POST as $key => $value )
	{
		$qodys_framework->update_option( $key, $value );
	}
	
	$response['results'][] = 'Settings have been saved';
}
else
{
	$response['errors'][] = 'Any unexpected error occured; please try again';
}

$qodys_framework->Helper('postman')->SetMessage( $response );

$url = $qodys_framework->Helper('tools')->GetPreviousPage();

header( "Location: ".$url );
exit;
?>