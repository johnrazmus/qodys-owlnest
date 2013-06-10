<?php
class QodyPinterestApi
{        
	var $m_token;
	var $m_caller = null;
	var $ch;
	
	function __construct( $access_token = '' )
	{
		$this->ch = curl_init();
		//verbose	
		$verbose=fopen('verbose.txt', 'w');
		curl_setopt($this->ch, CURLOPT_VERBOSE , 1);
		curl_setopt($this->ch, CURLOPT_STDERR, $verbose);
		
		curl_setopt($this->ch, CURLOPT_HEADER,0);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($this->ch, CURLOPT_TIMEOUT,20);
		curl_setopt($this->ch, CURLOPT_REFERER, 'http://www.bing.com/');
		curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 2); // Good leeway for redirections.
	//    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
		curl_setopt($this->ch, CURLOPT_COOKIEJAR , "cookie.txt"); 	
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
	}
	
	function Login( $email, $pass, $fetch_boards = false )
	{
		if( !$email )
		{
			if( $this->m_caller ) $this->m_caller->Log( 'missing pinterest login email; quitting', 'error' );
			return;
		}
		else if( !$pass )
		{
			if( $this->m_caller ) $this->m_caller->Log( 'missing pinterest login password; quitting', 'error' );
			return;
		}
		
		// GET: https://pinterest.com/login/?next=%2Flogin%2F
		$url = "https://pinterest.com/login/?next=%2Flogin%2F";
		//$url='http://coredue.com/php/test.php';
		
		curl_setopt( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt( $this->ch, CURLOPT_URL, trim($url) );
		curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER, false );
		
		$exec = curl_exec( $this->ch );
		
		//extract ' name='csrfmiddlewaretoken' value='9dd872d04d23903c8cd1287998b9ea5d'
		preg_match_all( "{name='csrfmiddlewaretoken' value='(.*?)'}", $exec, $matches, PREG_PATTERN_ORDER );
		$res = $matches[1];
		$token = $res[0];
		
		if( trim($token) == '' )
		{
			if( $this->m_caller )
				$this->m_caller->Log( 'Failed to fetch Pinterest token', 'error' );
				
			return false;
		}
		
		$this->m_token = $token;
		
		//extract 
		preg_match_all( "{_pinterest_sess=\"(.*?)\"}", $exec, $matches, PREG_PATTERN_ORDER );
		$res = $matches[1];
		$sess = $res[0];
		
		if( trim($sess) == '' )
		{
			if( $this->m_caller )
				$this->m_caller->Log( 'Failed to fetch Pinterest session number', 'error' );
				
			return false;
		}
		
		//Post login email=sweetheatmn%40yahoo.com&password=01292 &next=%2F&csrfmiddlewaretoken=8e0371f9dac6d39b1fe26e00a0595606
		$email = urlencode($email);
		$pass = urlencode($pass);
		
		$curlurl = "https://pinterest.com/login/?next=%2Flogin%2F";
		$curlpost = "email=$email&password=$pass&next=/&csrfmiddlewaretoken=$this->m_token";
		
		curl_setopt( $this->ch, CURLOPT_REFERER, "https://pinterest.com/login/?next=%2F" );
		//curl_setopt($this->ch, CURLOPT_HTTPHEADER, "HOST:pinterest.com");
		curl_setopt( $this->ch, CURLOPT_COOKIE, "_pinterest_sess=\"$sess\";__utma=229774877.1960910657.1333904477.1333904477.1333904477.1; __utmb=229774877.1.10.1333904477; __utmc=229774877; __utmz=229774877.1333904477.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmv=229774877.|2=page_name=login_screen=1" );
		curl_setopt( $this->ch, CURLOPT_URL, $curlurl );
		curl_setopt( $this->ch, CURLOPT_POST, true );
		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $curlpost ); 
		
		$exec = curl_exec($this->ch);
		
		$url = "https://pinterest.com/";
		//$url='http://coredue.com/php/test.php';
			
		//Get
		//extract 
		/*ItemDebug( $curlurl );
		ItemDebug( $curlpost );
		ItemDebug( $exec );exit;*/
		preg_match_all( "{_pinterest_sess=\"(.*?)\"}", $exec, $matches, PREG_PATTERN_ORDER );
		$res = $matches[1];
		$sess = $res[0];
		
		//echo $exec;
		if( trim($sess) == '')
		{
			if( $this->m_caller )
				$this->m_caller->Log( 'Failed to fetch Pinterest session number 2', 'error' );
			
			return false;
		}
		
		curl_setopt( $this->ch,CURLOPT_COOKIE, "_pinterest_sess=\"$sess\";__utma=229774877.1960910657.1333904477.1333904477.1333904477.1; __utmb=229774877.1.10.1333904477; __utmc=229774877; __utmz=229774877.1333904477.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmv=229774877.|2=page_name=login_screen=1" );
		curl_setopt( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt( $this->ch, CURLOPT_URL, trim($url) );
		curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $this->ch, CURLOPT_MAXREDIRS, 1 );
		
		$exec = curl_exec( $this->ch );
		
		if (stristr($exec,'Add') )
		{ 	
			if( $this->m_caller )
				$this->m_caller->Log( 'Logged into Pinterest', 'success' );
			
			if( $fetch_boards )
			{
				$data = array();
				
				$bits = explode( '<div class="BoardList">', $exec );
				$bits = explode( '</ul>', $bits[1] );
				$bits = explode( 'data=', $bits[0] );
				
				if( $bits )
				{
					foreach( $bits as $key => $value )
					{
						// <ul><li 
						if( $key == 0 )
							continue;
						
						$item = array();
						
						$chunks = explode( '>', $value );
						$item['id'] = str_replace( '"', '', $chunks[0] );
						
						$chunks = explode( '<span>', $value );
						$chunks = explode( '</span>', $chunks[1] );
						$item['name'] = str_replace( '"', '', $chunks[0] );
						
						$data[] = $item;
					}
				}
				
				return $data;
			}
			
			return $this->m_token;
			
		}
		else
		{
			if( $this->m_caller )
				$this->m_caller->Log( 'Failed to login to Pinterest', 'error' );
				
			return false;
		}
	}
	
	function GetBoardIdFromUrl( $board_url )
	{
		$board_url = trim( $board_url );
		
		if( !$board_url )
		{
			if( $this->m_caller ) $this->m_caller->Log( 'tried to fetch the pin board id, but no url was provided', 'error' );
			return;
		}
		
		//curl ini
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT,20);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Good leeway for redirections.
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
		curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$safety_iter = 0;
		$x = 'error';
		
		while( trim($x) != '' && $safety_iter < 100 )
		{
			$safety_iter++;
			
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, $board_url );
			
			$exec = curl_exec($ch);
			$x = curl_error($ch);
		}
		
		curl_close( $ch );
		
		preg_match_all( "{var board = (.*?);}", $exec, $matches, PREG_PATTERN_ORDER );
		
		$res = $matches[1];
		$id = $res[0];
		
		return $id;
	}
	
	function Pin( $fields )
	{
		$new_fields = array();
		$new_fields['media_url'] 	= $fields['img_url'];
		$new_fields['url'] 			= $fields['link'];
		$new_fields['caption'] 		= $fields['details'];
		$new_fields['board'] 		= $fields['board'];
		
		$new_fields['csrfmiddlewaretoken'] = $this->m_token;
		
		//curl post:http://pinterest.com/pin/create/
		//$curlurl='http://pinterest.com/pin/create/';
		$curlurl = 'http://pinterest.com/pin/create/button/';

		//curl post
	 	curl_setopt( $this->ch, CURLOPT_URL, $curlurl );
		curl_setopt( $this->ch, CURLOPT_POST, true );
		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, http_build_query( $new_fields ) ); 
		
		$exec = curl_exec($this->ch);
		
		if( strpos( $exec, 'pinSuccess' ) !== false )
		{
			//extract pin url
			preg_match_all( "{/pin/(.*?)/}", $exec, $matches, PREG_PATTERN_ORDER );
			
			$res = $matches[0];
			$pin = $res[0];
			
			if( $this->m_caller )
				$this->m_caller->Log( 'New content pinned at <a href="http://pinterest.com'.$pin.'">http://pinterest.com'.$pin.'</a>', 'success' );
			
			return 'http://pinterest.com'.$pin;		
		}
		else
		{
			$lines = explode( "\n", $exec );
			
			$error_data = json_decode( $lines[ count( $lines ) - 1 ] );
			
			if( $this->m_caller )
				$this->m_caller->Log( 'Failed at pinning content - '.$error_data->message, 'error' );
		}
	}
	
} // end class Pinterest_API









?>