<?php
require_once( dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))).'/wp-load.php' );

if( $_POST )
{
	require_once(ABSPATH . 'wp-admin/includes/plugin.php'); //for plugins_api..
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/template.php');
	require_once(ABSPATH . 'wp-admin/includes/misc.php');
	require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
	
	if( !class_exists('Blank_Skin') )
	{
		class Blank_Skin extends Bulk_Plugin_Upgrader_Skin {
		
			function __construct($args = array()) {
				parent::__construct($args);
			}
			
			function header() {}
			function footer() {}
			function error($errors) {}
			function feedback($string) {}
			function before() {}
			function after() {}
			function bulk_header() {}
			function bulk_footer() {}
			function show_message() {}
			
			function flush_output() {
				ob_end_clean();
			}
		}
	}
	
	$plugin_url = $_POST['plugin_url'];
	$plugin_file = $_POST['plugin_file'];
	
	if( !$plugin_url )
	{
		$qodys_framework->Log( "Failed to install plugin; missing it's download url", 'error' );
	}
	else
	{
		ob_get_contents();
		$upgrader = new Plugin_Upgrader( new Blank_Skin() );
		$upgrader->install( $plugin_url );
		ob_end_clean();
	}
	
	if( file_exists( WP_PLUGIN_DIR.'/'.$plugin_file ) )
	{
		$qodys_framework->Log( "Installed new plugin!", 'success' );
		$response = 'success';
	}
	else
	{
		$qodys_framework->Log( "Failed to install plugin ".$plugin_file."; you can always try the manual ol' fashion way", 'error' );
	}
}

echo json_encode( $response );
?>