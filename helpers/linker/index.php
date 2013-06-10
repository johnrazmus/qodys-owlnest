<?php
class qodyHelper_FrameworkSystemLinker extends QodyHelper
{
	var $m_api_key;
	var $m_prefix;
	
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		// echo "construct: ".$api_key;
		// die();
		// if( $api_key )
			// $this->m_api_key = $api_key;
		// else
			// $this->m_api_key = $this->get_option('api_key');
		
		// if( $prefix )
			// $this->m_prefix = $prefix;
		parent::__construct();
	}
	
	function GetOutsideLinkContent( $url )
	{
		$data = $this->DoCurl( $url );
		
		if( !$data )
			$data = file_get_contents( $url );
			
		return $data;
	}
	
	function DecodeResponse( $data )
	{
		return $this->Helper('tools')->ObjectToArray( json_decode($data) );
	}
	
	function PackArray( $data )
	{
		return base64_encode( serialize( $data ) );
	}
	
	function UnpackArray( $data )
	{
		return base64_decode( unserialize( $data ) );
	}
	
	function DoCurl( $url )
	{
		global $post;
		
		if( !$url )
			return;
		
		 // is cURL installed yet?
		if( !function_exists('curl_init') )
			return;
		
		if( is_object( $post ) )
			$ref_url = get_permalink( $post->ID );
		else
			$ref_url = get_bloginfo('url');
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// Set a referer
		curl_setopt( $ch, CURLOPT_REFERER, $ref_url );
		
		// User agent
		curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
		
		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		// Download the given URL, and return output
		$output = curl_exec($ch);
		
		// Close the cURL resource, and free system resources
		curl_close($ch);
		
		return $output;
	}
	
	function VerifyNexusMembership( $license_key )
	{
		return $this->QueryLicense( 'MTM0MzExMjU5NmE3NzE=', $license_key, true );
	}
	
	function QueryLicense( $salt_key, $license_key, $set_key = true )
	{
		$fields = array();
		$fields['sk'] = $salt_key;
		$fields['lk'] = $license_key;
		
		if( $set_key )
		{
			$fields['a'] = 'set';
			$fields['url'] = urlencode( get_bloginfo('url') );
		}
		
		$url = "http://nexus.qody.co/wp-content/plugins/qodys-licensor/overseers/licensor/api/verify.php?".http_build_query( $fields );
		
		$response = $this->GetOutsideLinkContent( $url );
		
		if( !$response )
			$response = file_get_contents( $url );
		
		$data = $this->DecodeResponse( $response );
		
		return $data;
	}
	
	function SendCommand( $fields )
	{
		// echo "key: ".$this->get_option('api_key');
		// ItemDebug($fields);
		// die();
		if( !$fields['api'] )
			$fields['api'] = $this->get_option( 'api_key' );
		
		if( !$fields['domain'] )
			$fields['domain'] = get_bloginfo('url');
			
		if( !$fields['product_id'] )
			$fields['product_id'] = $this->GetProductID( $fields['prefix'] );
		
		$hash = urlencode( base64_encode( serialize( $fields ) ) );
		
		$url = "http://plugins.qody.co/connector/?hash=".$hash;
		//$this->ItemDebug( $url );exit;
		$response = $this->GetOutsideLinkContent( $url );
		
		if( !$response )
			$response = file_get_contents( $url );
		
		return $response;
	}
}
?>