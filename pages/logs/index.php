<?php
class qodyPage_FrameworkLogs extends QodyPage
{
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		$this->m_raw_file = __FILE__;
		
		$owner = func_get_args();
		if( $owner[0]->GetPre() == 'frwk' )
		{
			$this->SetParent( 'nest' );
			$this->SetTitle( 'logs' );
			$this->SetPriority( 98 );
		}
		else
		{
			// if we've entered the OIN, show it as the OIN management page
			if( $this->PassApiCheck() )
			{
				$this->SetParent( 'home' );
				$this->SetTitle( 'logs' );
				$this->SetPriority( 98 );
			}
			else
			{
				return;
			}
		}
		
		parent::__construct();
	}
	
	function LoadMetaboxes()
	{
		$this->AddMetabox( 'general', 'Logs' );
	}
	
	function WhenOnPage()
	{
		if( !parent::WhenOnPage() )
			return;
		
		$this->EnqueueStyle( 'restricted-bootstrap' );
		
		$this->EnqueueScript( 'jquery-ui' );		
	}
	
	function GetLogs()
	{
		$data = $this->Helper('db')->Select( 'logs', '', $this, ' ORDER BY date DESC, id DESC' );
		
		return $data;
	}
}
?>