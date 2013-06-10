<?php
class qodyHelper_FrameworkThemes extends QodyHelper
{
	var $m_theme_folder_slug = 'themes';
	var $m_custom_theme_folder_slug = 'qody_themes';
	
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		parent::__construct();
	}
	
	function GetAvailableThemes( $plugin_dir )
	{
		$folder_contents = $this->OrganizeDirectoryIntoArray( $plugin_dir.'/'.$this->m_theme_folder_slug.'/' );
		
		if( !$folder_contents )
			return;
		
		$fields = array();
		
		foreach( $folder_contents as $key => $value )
		{
			$fields[] = $key;
		}
		
		$data = $this->GetCustomThemes();
		
		if( $data )
			$fields = array_merge( $fields, $data );
		
		return $fields;
	}
	
	function GetCustomThemes()
	{
		$folder_contents = $this->OrganizeDirectoryIntoArray( WP_CONTENT_DIR.'/'.$this->m_custom_theme_folder_slug.'/' );
		
		if( !$folder_contents )
			return;
		
		$fields = array();
		
		foreach( $folder_contents as $key => $value )
		{
			$fields[] = $key;
		}
		
		return $fields;
	}
	
	function GetRealPath( $plugin_dir, $theme, $relative_path, $allow_default = true )
	{
		// first check normal theme folder
		$path = $plugin_dir.'/'.$this->m_theme_folder_slug.'/'.$theme.'/'.$relative_path;
		
		if( file_exists( $path ) )
			return $path;
		
		// then check custom theme folder
		$path = WP_CONTENT_DIR.'/'.$this->m_custom_theme_folder_slug.'/'.$theme.'/'.$relative_path;
		
		if( file_exists( $path ) )
			return $path;
		
		if( !$allow_default )
			return '';
			
		// then check default theme
		$path = $plugin_dir.'/'.$this->m_theme_folder_slug.'/default/'.$relative_path;
		
		if( file_exists( $path ) )
			return $path;
		
		return '';
	}
	
	function LoadThemeContent( $plugin_dir, $theme, $file_path )
	{
		$full_path = $this->GetRealPath( $plugin_dir, $theme, $file_path );
		//$full_path = $plugin_dir.'/'.$this->m_theme_folder_slug.'/'.$theme.'/'.$file_path;
		
		if( !file_exists( $full_path ) )
			return '<em>theme file '.$file_path.' not found</em>';
			
		$stream = fopen( $full_path, "r" );
		
		$data = stream_get_contents( $stream );
		
		fclose( $stream );
		
		return $data;
	}
}
?>