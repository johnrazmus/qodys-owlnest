<?php
/**
 * Plugin Name: Qody's Owl Nest
 * Plugin URI: http://qody.co
 * Description: Framework required for all plugins managed by Qody's owls.
 * Version: 3.9.5
 * Author: Qody LLC
 * Author URI: http://qody.co
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
$framework_problem = false;
$framework_file = dirname(__FILE__).'/frwk.php';

// load up the main Qody framework
require_once( $framework_file );

if( !class_exists('QodyPlugin') )
{
	$framework_problem = true;
}
else if( !class_exists('QodysOwlNest') )
{
	class QodysOwlNest extends QodyPlugin
	{
		// general plugin variables
		//var $m_plugin_name;
	
		function __construct()
		{
			$this->m_pre = 'frwk';
			$this->m_owl_name = 'Qody';
			$this->m_owl_image = 'https://qody.s3.amazonaws.com/qodys-pinner/owl1.png';
			$this->m_owl_buy_url = 'http://qody.co/';
			$this->m_plugin_version = '1.0.0';
			$this->m_plugin_name = 'Owl Nest';
			$this->m_plugin_slug = 'qodys-owlnest';
			$this->m_plugin_file = plugin_basename(__FILE__);
			$this->m_plugin_folder = dirname(__FILE__);
			$this->m_raw_file = __FILE__;
			
			// Set plugin name, slug, file, and folder
			parent::__construct();
		}
		
		function LoadDefaultOptions()
		{
			$this->GetOverseer()->LoadDefaultOptions();
			
			parent::LoadDefaultOptions();
		}
	}
	
	// just have to do this for the Framework plugin for when it's the only Qody plugin
	global $qodys_framework;
	
	// create an instance of the main class to start the plugin's system.
	$qodys_framework = new QodysOwlNest();
	
	// Register the plugin with Wordpress
	$qodys_framework->RegisterPlugin();
}

if( !function_exists('qody_framework_warning') )
{
	function qody_framework_warning()
	{
		$data = "
<div class='updated fade'>
	<p><strong>Your plugin by Qody is almost ready.</strong> You must 
	<a href=\"http://plugins.qody.co/api/?p=qodys-owlnest\">install/update the framework plugin</a> for it to work properly.</p>
</div>";
		echo $data;
	}
}

if( $framework_problem )
	add_action('admin_notices', 'qody_framework_warning'); 
?>