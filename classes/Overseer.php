<?php
class QodyOverseer extends QodyOwnable
{
	var $m_salt_key;
	
	function __construct()
	{
		global $wp_version;
		
		$fields = array();
		
		// Standard WP
		$fields['admin_init'] = 'AdminInit';
		
		$this->LoadActionHooks( $fields );
		
		add_filter('plugin_action_links', array( $this, 'AddCustomActionLinks' ), 10, 2);
		
		if( version_compare('2.8', $wp_version, '<=') )
		{
			add_action( 'in_plugin_update_message-'.$this->Owner()->m_plugin_file, array( $this , 'LoadPluginUpdateNotes' ), null, 2 );
		}
		
		parent::__construct();
	}
	
	function AdminInit()
	{
		$this->LoadMetaboxes();
	}

	function AddCustomActionLinks( $links, $file )
	{
		$this_plugin = $this->Owner()->m_plugin_file;
		
		if( $file == $this_plugin && $this->Page('home') )
		{
			// The "page" query string value must be equal to the slug
			// of the Settings admin page we defined earlier, which in
			// this case equals "myplugin-settings".
			$settings_link = '<a href="'.$this->Page('home')->AdminUrl().'">Access, use & configure</a>';
			array_unshift( $links, $settings_link );
		}
		
		
	
		return $links;
	}
	
	function LoadPluginUpdateNotes( $plugin_data = '', $update_data = '' )
	{
		global $wp_version;
		
		$columns = substr($wp_version, 0, 3) == "2.8" ? 3 : 5;
		
		$real_key = str_replace( '/plugin.php', '', $this->Owner()->m_plugin_file );
		
		$forum_root = 'http://qody.co/nexus/forum/changelogs';
		
		switch( $real_key )
		{
			case 'qodys-owlnest': 		$url = $forum_root.'/qody-the-owl-nest-plugin/rss'; break;
			case 'qodys-fb-meta': 		$url = $forum_root.'/penelope-qodys-fb-meta-plugin/rss'; break;
			case 'qodys-redirector': 	$url = $forum_root.'/alejandro-qodys-redirector-plugin/rss'; break;
			case 'qodys-optiner': 		$url = $forum_root.'/odin-qodys-optiner-plugin/rss'; break;
			case 'qodys-buttoner': 		$url = $forum_root.'/judy-qodys-buttoner-plugin/rss'; break;
			case 'qodys-pinner': 		$url = $forum_root.'/pierre-qodys-pinner-plugin/rss'; break;
			case 'qodys-shopper': 		$url = $forum_root.'/melody-qodys-shopper-plugin/rss'; break;
			
			default:
				return;
		}
		
		$feed = fetch_feed( $url );
		
		if( is_wp_error( $feed ) )
			return;
		
		$maxitems = 10;
		$data = $feed->get_items(0, $maxitems); 
		
		$update = "";						
		$update .= '<div class="qody_plugin_update_notes">';
		
		$iter = 0;
		foreach( $data as $key => $value )
		{
			$iter++;
			$the_title = $value->get_title();
			
			$bits = explode( 'version', $the_title );
			$version = trim( $bits[1] );		
			
			if( version_compare( $version, $plugin_data['Version'], '<=' ) )
			{
				if( $iter > 1 )
					break;
				else
					return;
			}
				
			$update .= '<a target="_blank" href="'.$value->get_permalink().'">'.$version."</a><br>";
			$update .= $value->get_content();
			//$update .= "<pre>".print_r( $value->get_title(), true )."</pre>";
		}
			
		$update .= '</div>';
		?>
		<style>
		.qody_plugin_update_notes {
			margin-top:10px;
		}
		.qody_plugin_update_notes ul { 
			list-style-type:disc;
		}
		.qody_plugin_update_notes li { 
			list-style-type:disc;
			margin-left:30px;
			font-weight:normal;
		}
		</style>
		<?php
		echo $update;
	}
	
	function SetSalt( $salt )
	{
		$this->m_salt_key = $salt;
	}
	
	function HasSalt( $salt )
	{
		$data = $this->get_option( 'license_data' );
		
		if( $data['status'] != 'good' )
			return false;
		
		if( !$salt )
			return false;
			
		if( !$data['data']['salts'] || !is_array( $data['data']['salts'] ) )
			return false;
			
		if( !in_array( $salt, $data['data']['salts'] ) )
			return false;
		
		return true;
	}
	
	function UpdateLicense( $license_key = '' )
	{
		if( !$license_key )
			$license_key = $this->GetLicenseKey();
		
		$result = $this->Helper('linker')->QueryLicense( $this->m_salt_key, $license_key );
		
		if( $result['status'] == 'good' )
		{
			$result['license_key'] = $license_key;
		}
		
		$this->update_option( 'license_data', $result );
		
		return $result;
	}
	
	function VerifyLicense()
	{
		$data = $this->get_option( 'license_data' );
		
		if( !$data )
			return false;
			
		return $data['status'] == 'good' ? true : false;
	}
	
	function GetLicenseKey()
	{
		$data = $this->get_option( 'license_data' );
		
		return $data['license_key'];
	}
	
	function DeleteLicenseKey()
	{
		$this->delete_option( 'license_data' );
	}
}
?>