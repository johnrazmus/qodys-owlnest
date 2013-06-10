<?php
class qodyPage_FrameworkHome extends QodyPage
{
	function __construct()
	{
		$owner = func_get_args();
		if( $owner[0]->GetPre() != 'frwk' )
			return;
			
		$this->SetOwner( func_get_args() );
		
		$this->m_raw_file = __FILE__;
		$this->m_icon_url = $this->GetIcon();
		
		//$this->SetParent( 'home' );
		$this->SetTitle( "Owl Nest" );
		$this->SetSlug( 'home' );
		$this->SetPriority( 1, false );
		
		parent::__construct();
	}
	
	function LoadMetaboxes()
	{
		//$this->AddMetabox( 'logs', 'Logs' );
	}
	
	function WhenOnPage()
	{
		if( !parent::WhenOnPage() )
			return;
		
		$this->EnqueueStyle( 'restricted-bootstrap' );
		
		$this->EnqueueScript( 'bootstrap-everything' );
	}
	
	function PluginLatestVersion( $plugin_slug = '' )
	{
		$download_url = 'http://qody.co/api/';
				
		$fields = array();
		$fields['p'] = $plugin_slug;
		$fields['a'] = 'version_check';
		
		$data = wp_remote_get( $download_url.'?'.http_build_query( $fields ) );
		
		if( !$data )
			return;
		
		$latest_version = $data['body'];
		
		return $latest_version;
	}
	
	function ShouldCheckForUpdates()
	{
		global $qodys_framework;
		
		if( time() - $qodys_framework->get_option( 'last_update_check' ) > 60*5 )
			return true;
		
		return false;
	}
	
	function MarkUpdateCheckTime()
	{
		global $qodys_framework;
		
		$qodys_framework->update_option( 'last_update_check', time() );
	}
	
	function GetInstallableOwls()
	{
		$data = array();
		
		$fields = array();
		$fields['owl_name'] = 'Qody';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-owlnest';		
		$fields['image_url'] = 'https://qody.s3.amazonaws.com/owls/qody/qody.png';
		$fields['plugin_name'] = 'Qody\'s Framework Plugin';
		$fields['plugin_url'] = 'qodys-owlnest/plugin.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Alex';
		$fields['owl_url'] = 'http://qody.co/owl/alex/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-contestor';
		$fields['image_url'] = 'https://s3.amazonaws.com/qody/owls/alex/a.png';
		$fields['plugin_name'] = 'Qody\'s Contestor';
		$fields['plugin_url'] = 'qodys-contestor/plugin.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Melody';
		$fields['owl_url'] = 'http://qody.co/owl/melody/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-shopper';
		$fields['image_url'] = 'https://s3.amazonaws.com/qody/owls/melody/a.png';
		$fields['plugin_name'] = 'Qody\'s Shopper';
		$fields['plugin_url'] = 'qodys-shopper/plugin.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Pierre';
		$fields['owl_url'] = 'http://qody.co/owl/pierre/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-pinner';
		//$fields['download_url'] = 'http://downloads.wordpress.org/plugin/yd-profile-visitor-tracker.zip';
		$fields['image_url'] = 'https://qody.s3.amazonaws.com/owls/pierre/a.png';
		$fields['plugin_name'] = 'Qody\'s Pinner';
		$fields['plugin_url'] = 'qodys-pinner/plugin.php';
		//$fields['plugin_url'] = 'yd-profile-visitor-tracker/yd-profile-visitor-tracker.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Judy';
		$fields['owl_url'] = 'http://qody.co/owl/judy/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-buttoner';
		$fields['image_url'] = 'https://qody.s3.amazonaws.com/owls/judy/a.png';
		$fields['plugin_name'] = 'Qody\'s Buttoner';
		$fields['plugin_url'] = 'qodys-buttoner/plugin.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Odin';
		$fields['owl_url'] = 'http://qody.co/owl/odin/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-optiner';
		$fields['image_url'] = 'https://s3.amazonaws.com/qody/logos/owls/odin/owl9a-300x300.png';
		$fields['plugin_name'] = 'Qody\'s Optiner';
		$fields['plugin_url'] = 'qodys-optiner/plugin.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Alejandro';
		$fields['owl_url'] = 'http://qody.co/owl/alejandro/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-redirector';
		$fields['image_url'] = 'https://s3.amazonaws.com/qody/logos/owls/alejandro/owl5a-300x300.png';
		$fields['plugin_name'] = 'Qody\'s Redirector';
		$fields['plugin_url'] = 'qodys-redirector/plugin.php';
		$data[] = $fields;
		
		$fields = array();
		$fields['owl_name'] = 'Penelope';
		$fields['owl_url'] = 'http://qody.co/owl/penelope/';
		$fields['download_url'] = 'http://qody.co/api/?p=qodys-fb-meta';
		$fields['image_url'] = 'https://s3.amazonaws.com/qody/logos/owls/penelope/owl6a-300x300.png';
		$fields['plugin_name'] = 'Qody\'s FB Meta';
		$fields['plugin_url'] = 'qodys-fb-meta/plugin.php';
		$data[] = $fields;
		
		return $data;
	}
}
?>