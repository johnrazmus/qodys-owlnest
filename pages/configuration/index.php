<?php
class qodyPage_FrameworkConfiguration extends QodyPage
{
	function __construct()
	{
		return;
		
		$owner = func_get_args();
		if( $owner[0]->GetPre() != 'frwk' )
			return;
			
		$this->SetOwner( func_get_args() );
		
		$this->m_raw_file = __FILE__;
		$this->m_icon_url = $this->GetIcon();
		
		$this->SetParent( 'nest' );
		$this->SetTitle( 'Configuration' );
		$this->SetPriority( 3 );
		
		parent::__construct();
	}
	
	function LoadMetaboxes()
	{
		$this->AddMetabox( 'data', 'General' );
		
		$this->AddMetabox( 'save', 'Save Settings', 'side' );
	}
	
	function HandleMembershipKeySave()
	{
		$posted_key = trim( $_POST['membership_key'] );
		$current_key = trim( $this->get_option('membership_key') );
		
		if( !$posted_key )
		{
			// handle clearing membership
			$this->update_option( 'membership_key', '' );
			return;
		}
		
		if( $current_key == $posted_key )
		{
			unset( $_POST['membership_key'] ); // don't save it again, just in case it messes up
			return;
		}
		
		$result = $this->Helper('linker')->VerifyNexusMembership( $_POST['membership_key'] );
		
		if( $result == 1 )
		{
			$result = array();
			$result['results'][] = 'Nexus membership key successfully verified! All Pro member-only features have been enabled.';
		}
		else
		{
			$_POST['membership_key'] = ''; // clear it
		}
		
		$this->Helper('postman')->SetMessage( $result );
		
		//$this->ItemDebug( $_POST );
		//$this->ItemDebug( $result );exit;
	}
}
?>