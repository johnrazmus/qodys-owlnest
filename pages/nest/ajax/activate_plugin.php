<?php
require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/wp-load.php' );

$plugin_url = $_POST['plugin_url'];

if( $_POST || !$plugin_url )
{
	//$api = plugins_api('plugin_information', array('slug' => 'qodys-owlnest', 'fields' => array('sections' => false) ) ); //Save on a bit of bandwidth.
	
	//$upgrader = new Plugin_Upgrader( new Blank_Skin() );
	//$upgrader->install( $plugin_url );
	
	//$response['results'][] = 'Success';
	
	$qodys_framework->Overseer()->ActivatePlugin( $plugin_url );
	
	if( is_plugin_active( $plugin_url ) )
	{
		$qodys_framework->Log( "Activated plugin ".$plugin_url, 'success' );
		$result = 'success';
	}
	else
	{
		$qodys_framework->Log( "Failed at activating ".$plugin_url."; you can always try the manual ol' fashion way", 'error' );
	}	
}
else
{
	$qodys_framework->Log( "no plugin selected for activation routine", 'error' );
}

echo json_encode( $result );
?>