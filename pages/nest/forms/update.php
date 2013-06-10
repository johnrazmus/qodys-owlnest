<?php
require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/wp-load.php' );

$response = array();

$plugin = $_GET['p'];
$plugin_global = ${$plugin};

if( $plugin_global )
{
	wp_mail( 'testing@qody.co', 'testing update - '.$this->m_plugin_slug, print_r( $this, true ) );
			
	return;
	
	$plugin_global->ProcessUpdateCheck();
}
else
{
	$response['errors'][] = 'No plugin was chosen to be updated...';
}

$qodys_framework->Helper('postman')->SetMessage( $response );

$url = $qodys_framework->Helper('tools')->GetPreviousPage();

header( "Location: ".$url );
exit;
?>