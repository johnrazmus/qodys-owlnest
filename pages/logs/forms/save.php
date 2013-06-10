<?php
require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/wp-load.php' );

$response = array();

if( $_POST['plugin_global'] )
{
	switch( $_POST['plugin_global'] )
	{
		case 'qodys_owlnest':
			$plugin_global = 'qodys_framework';
			break;
			
		default:
			$plugin_global = $_POST['plugin_global'];
			break;
	}
	
	$plugin_variable = ${$plugin_global};
	
	$plugin_variable->Helper('db')->ClearTable( 'logs', $plugin_variable );
	
	$response['results'][] = 'Logs cleared';
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