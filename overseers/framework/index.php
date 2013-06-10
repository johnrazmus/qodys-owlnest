<?php
class qodyOverseer_Framework extends QodyOverseer
{
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		$this->SetSalt( 'MTM1NDQyMzQxMGEyODI=' );
		
		$this->m_raw_file = __FILE__;
		
		// load global styles & scripts. No other overseer does this
		add_action( 'init', array( $this, 'GlobalEnqueue' ), 999 );
		add_action( 'init', array( $this, 'CheckPluginsForUpdate' ) );
		
		// this makes our update feed not cache the changelogs for 12 hours
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'GetFeedCacheTime' ) );
		
		parent::__construct();
	}
	
	function GlobalEnqueue()
	{
		$this->EnqueueStyles();
		$this->EnqueueScripts();
	}
	
	function LoadDefaultOptions()
	{	
		//$this->CreateTables();
	}
	
	function GetFeedCacheTime( $whatever = '' )
	{
		return 1800;
	}
	
	function CheckPluginsForUpdate()
	{
		global $ran_LoadPluginUpdateNotes;
		
		if( !is_admin() || $ran_LoadPluginUpdateNotes )
			return;
			
		$qody_plugins = $this->GetQodyPlugins();
		
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');						
		$installed_plugins = get_plugins();
		
		if( !$installed_plugins )
			return;
		
		foreach( $installed_plugins as $key => $value )
		{
			$real_key = str_replace('/plugin.php', '', $key);
			
			$global_slug = isset( $qody_plugins[ $real_key ] ) ? $qody_plugins[ $real_key ]['global'] : '';
			
			if( !$global_slug )
				continue;
			
			$this->Helper('updater')->SetupChecker( $value['Version'], $key );
		}
		
		$ran_LoadPluginUpdateNotes = true;
	}
	
	function GetQodyPlugins()
	{
		$data = array();
		
		$fields = array();
		$fields['global'] = 'qodys_framework';
		$fields['image'] = 'https://qody.s3.amazonaws.com/owls/qody/qody.png';
		$data['qodys-owlnest'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_affiliator';
		$fields['image'] = '';
		$data['qodys-affiliator'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_buttoner';
		$fields['image'] = 'https://qody.s3.amazonaws.com/owls/judy/a.png';
		$data['qodys-buttoner'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_fbmeta';
		$fields['image'] = 'https://qody.s3.amazonaws.com/owls/penelope/a.png';
		$data['qodys-fb-meta'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_mapper';
		$fields['image'] = '';
		$data['qodys-mapper'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_optiner';
		$fields['image'] = 'https://qody.s3.amazonaws.com/owls/odin/a.png';
		$data['qodys-optiner'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_pinner';
		$fields['image'] = 'https://qody.s3.amazonaws.com/owls/pierre/a.png';
		$data['qodys-pinner'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_pitcher';
		$fields['image'] = '';
		$data['qodys-pitcher'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_redirector';
		$fields['image'] = 'https://qody.s3.amazonaws.com/owls/alejandro/a.png';
		$data['qodys-redirector'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_rotator';
		$fields['image'] = '';
		$data['qodys-rotator'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_shopper';
		$fields['image'] = '';
		$data['qodys-shopper'] = $fields;
		
		$fields = array();
		$fields['global'] = 'qodys_contestor';
		$fields['image'] = '';
		$data['qodys-contestor'] = $fields;
		
		return $data;
	}
	
	function ActivatePlugin( $plugin )
	{
		$current = get_option( 'active_plugins' );
		$plugin = plugin_basename( trim( $plugin ) );
		
		if( !in_array( $plugin, $current ) )
		{
			$current[] = $plugin;
			sort( $current );
			
			do_action( 'activate_plugin', trim( $plugin ) );
			update_option( 'active_plugins', $current );
			
			do_action( 'activate_' . trim( $plugin ) );
			do_action( 'activated_plugin', trim( $plugin) );
		}
	}
	
	function RegisterStyles()
	{
		if( function_exists('wp_get_theme') )
		{
			$the_theme = wp_get_theme();
			$theme_name = $the_theme->Name;
		}		
		
		// this overrides the default wordpress registration
		$this->RegisterStyle( 'jquery-ui', 				$this->GetAsset( 'includes', 'jquery_ui_theme', 'jquery-ui-1-8-16-custom', 'url' ) );
		
		// these are global framework-wide registrations
		//$this->RegisterStyle( 'jquery-ui-overcast', 	$this->GetAsset( 'includes', 'custom-bootstrap-jquery', 'jquery-ui-1.8.16.custom', 'url' ) );
		$this->RegisterStyle( 'jquery-ui-bootstrap', 	$this->GetAsset( 'includes', 'bootstrap_jquery_ui_theme', 'jquery-ui-1-8-16-custom', 'url' ) );
		
		// this theme calls chosen not on domready, so breaks when overrides their version of it
		if( !defined('PREMIUMPRESS_SYSTEM') && $theme_name != 'directorypress' )
			$this->RegisterStyle( 'chosen', $this->GetAsset( 'includes', 'chosen', 'chosen', 'url' ) );
			
		$this->RegisterStyle( 'miniColors', 			$this->GetAsset( 'includes', 'miniColors', 'jquery.miniColors-style', 'url' ) );
		$this->RegisterStyle( 'nicer-tables', 			$this->GetAsset( 'css', 'nicer-tables', 'url' ) );
		$this->RegisterStyle( 'smaller-pagination', 	$this->GetAsset( 'css', 'smaller-pagination', 'url' ) );
		
		//$this->RegisterStyle( 'qody-bootstrap', 		$this->GetAsset( 'includes', 'twitter-bootstrap', 'css', 'bootstrap', 'url' ) );
		$this->RegisterStyle( 'bootstrap-modal', 		$this->GetAsset( 'includes', 'restricted-bootstrap', 'css', 'modal-only', 'url' ) );
		$this->RegisterStyle( 'bootstrap-popover', 		$this->GetAsset( 'includes', 'restricted-bootstrap', 'css', 'popover-only', 'url' ) );
		//$this->RegisterStyle( 'admin-bootstrap', 		$this->GetAsset( 'includes', 'admin-bootstrap', 'css', 'bootstrap_min', 'url' ) );
		$this->RegisterStyle( 'restricted-bootstrap',	$this->GetAsset( 'includes', 'restricted-bootstrap', 'css', 'bootstrap', 'url' ) );
		
		parent::RegisterStyles();
	}
	
	// This loads the framework stylesheets
	function EnqueueStyles()
	{
		if( is_admin() )
		{
			// Load framework-specific styles
			$this->EnqueueStyle( 'qody-global' );
		}
	}
	
	function RegisterScripts()
	{
		if( function_exists('wp_get_theme') )
		{
			$the_theme = wp_get_theme();
			$theme_name = $the_theme->Name;
		}	
		
		// this overrides the default wordpress registration
		//$this->RegisterScript( 'newest-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js' );
		//$this->RegisterScript( 'jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js', array('jquery') );
		
		$this->RegisterScript( 'jquery-safety-set', $this->GetAsset( 'js', 'jquery-compat', 'js', 'url' ), array('jquery') );		
		$this->RegisterScript( 'jquery-scrollto', $this->GetAsset( 'js', 'jquery-scrollto', 'js', 'url' ), array('jquery', 'jquery-ui-core') );	
		//$this->RegisterScript( 'jquery-datepicker', $this->GetAsset( 'js', 'datepicker', 'url' ), array('jquery-ui') );	
		
		// these are global framework-wide registrations
		$this->RegisterScript( 'miniColors', $this->GetAsset( 'includes', 'miniColors', 'jquery.miniColors', 'url' ), array('jquery') );
		
		// this theme calls chosen not on domready, so breaks when overrides their version of it
		if( !defined('PREMIUMPRESS_SYSTEM') && $theme_name != 'directorypress' )
			$this->RegisterScript( 'chosen', $this->GetAsset( 'includes', 'chosen', 'chosen.jquery', 'url' ), array('jquery') );
		
		//$this->RegisterScript( 'bootstrap-tab', $this->GetAsset( 'includes', 'twitter-bootstrap', 'js', 'bootstrap-tab', 'url' ), array('jquery', 'jquery-ui') );
		//$this->RegisterScript( 'bootstrap-dropdown', $this->GetAsset( 'includes', 'twitter-bootstrap', 'js', 'bootstrap-dropdown', 'url' ), array('jquery', 'jquery-ui') );
		
		$this->RegisterScript( 'bootstrap-everything', $this->GetAsset( 'includes', 'restricted-bootstrap', 'js', 'bootstrap_min', 'url' ), array('jquery', 'jquery-ui-core') );	
		$this->RegisterScript( 'bootstrap-typeahead', $this->GetAsset( 'includes', 'restricted-bootstrap', 'js', 'bootstrap-typeahead', 'url' ), array('jquery', 'jquery-ui-core') );
		
		parent::RegisterScripts();
	}
	
	// This loads the framework scripts
	function EnqueueScripts()
	{
		if( is_admin() )
		{
			$this->EnqueueScript( 'jquery' );
			//$this->EnqueueScript( 'newest-jquery' );
			//$this->EnqueueScript( 'jquery-safety-set' ); // breaks some themes doin this without noConflict on
			
			$this->EnqueueScript( 'bootstrap-everything' );
			$this->EnqueueScript( 'qody-global' );
			//wp_enqueue_script('jquery-ui-sortable', false, array(), false, true);
			
			// These are required for metaboxes to do their fancy bits
			$this->EnqueueScript( 'common' );
			$this->EnqueueScript( 'wp-lists' );
			$this->EnqueueScript( 'postbox' );
		}
		else
		{
			// Loads up javascript files for non-admin users (site visitors?)
		}
	}
}
?>