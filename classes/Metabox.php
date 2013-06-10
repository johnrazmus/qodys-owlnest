<?php
class QodyMetabox extends QodyOwnable
{
	var $m_id;
	var $m_title;
	var $m_post_type;
	var $m_position = 'normal';
	var $m_priority = 'low';
	var $m_file_slug = '';
	
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		parent::__construct();
	}
	
	function add_meta_box()
	{
		add_meta_box( $this->m_id, $this->m_title, array( $this, "Show" ), $this->m_post_type, $this->m_position, $this->m_priority );
	}
	
	function get_post_custom( $post_id )
	{
		return $this->Owner()->get_post_custom( $post_id );
	}
	
	function Show()
	{
		$box_file = $this->Owner()->GetAsset( 'metaboxes', $this->m_file_slug, 'dir' );
		
		if( file_exists( $box_file ) )	
			include( $box_file );
		else
		{
			$this->Log( 'metabox file not found '.$box_file );
			echo 'metabox file not found';
		}
	}
	
	function GetAsset( $folder, $file, $format = '' )
	{
		return $this->Owner()->GetAsset( $folder, $file, $format );
	}
}
?>