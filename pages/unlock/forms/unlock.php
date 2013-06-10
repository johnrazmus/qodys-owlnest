<?php
require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/wp-load.php' );

$post_data = array_merge( $_GET, $_POST );
$response = array();

if( $post_data )
{	
	$license_key = $_POST['license_key'];
	$action = $_POST['action'];
	
	if( $action == 'clock_out' )
	{
		$qodys_framework->Overseer()->DeleteLicenseKey();
		$response['results'][] = 'That O.I.N has been removed';
	}
	else if( !$license_key )
	{
		$response['errors'][] = 'You must enter an O.I.N to begin';
	}
	else
	{
		$data = $qodys_framework->Overseer()->UpdateLicense( $license_key, true, true );
		
		if( $data['status'] == 'good' )
			$response['results'][] = $data['data']['message'];
		else
			$response['errors'][] = $data['data']['message'];
	}
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