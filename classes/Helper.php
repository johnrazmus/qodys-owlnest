<?php
class QodyHelper extends QodyOwnable
{
	function __construct()
	{
		parent::__construct();
	}
	
	function get_option( $slug )
	{
		return $this->Owner()->get_option( $slug );
	}
}
?>