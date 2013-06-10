<?php
class qodyHelper_FrameworkProfiler extends QodyHelper
{
	var $m_start_node = null;
	
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		//$this->m_start_node = new QodyRecordNode( 'Profile Start' );
		
		parent::__construct();
	}
	
	function Start( $name )
	{
		//$this->AddNewNode( $name, $this->m_start_node );
	}
	
	function End()
	{
		//$this->CloseEndNode( $this->m_start_node );
	}
	
	function AddNewNode( $name, $parent )
	{
		/*if( $parent->m_child == null )
			$parent->m_child = new QodyRecordNode( $name );
		else
			$this->AddNewNode( $name, $parent->m_child );*/
	}
	
	function CloseEndNode( $parent )
	{
		/*if( $parent->m_child == null )
			$parent->m_end = microtime();
		else
			$this->CloseEndNode( $parent->m_child );*/
	}
	
	function Compute()
	{
		
	}
}

class QodyRecordNode
{
	var $m_name;
	var $m_start;
	var $m_end;
	var $m_child = null;
	
	function __construct( $name, $child = null )
	{
		$this->m_name = $name;
		$this->m_child = $child;		
		$this->m_start = microtime();
	}
}
?>