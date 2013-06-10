<?php
class qodyPage_FrameworkUnlock extends QodyPage
{
	function __construct()
	{
		$owner = func_get_args();
		if( $owner[0]->GetPre() != 'frwk' )
			return;
			
		$this->SetOwner( func_get_args() );
		
		$this->m_raw_file = __FILE__;
		
		if( $owner[0]->GetPre() == 'frwk' )
		{
			$this->SetParent( 'nest' );
			$this->SetTitle( 'O.I.N' );
			$this->SetPriority( 99 );
		}
		else
		{
			//$this->SetParent( 'nest' );
			$this->SetTitle( 'O.I.N' );
			$this->SetPriority( 1 );
		}
		
		parent::__construct();
	}
	
	function LoadMetaboxes()
	{
		$this->AddMetabox( 'unlock', 'O.I.N Required' );
	}
}
?>