<?php
class qodyPage_FrameworkUpdates extends QodyPage
{
	function __construct()
	{
		$owner = func_get_args();
		if( $owner[0]->GetPre() != 'frwk' || is_multisite() )
			return;
			
		$this->SetOwner( func_get_args() );
		
		$this->m_raw_file = __FILE__;
		
		$this->SetParent( 'nest' );
		$this->SetTitle( "Check for updates" );
		$this->SetPriority( 101 );
		
		parent::__construct();
	}
	
	function WhenOnPage()
	{
		if( !parent::WhenOnPage() )
			return;
		
		// clear the current update check so it fetches em again
		update_option( '_site_transient_update_plugins', '' );
		
		//echo admin_url( 'update-core.php' );exit;
		header( "Location: ".admin_url( 'update-core.php' ) );
		exit;
	}
}
?>