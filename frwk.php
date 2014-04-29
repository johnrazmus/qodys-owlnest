<?php
if( !function_exists('qody_framework_update_warning') )
{
	function qody_framework_update_warning()
	{
		$data = "
	<div class='updated fade'>
	<p><strong>Owl Nest notice: out of sync Qody plugins have been deactivated; please update them to resume normal operation.</strong></p>
	</div>";
		echo $data;
	}
	
	function qody_clear_plugin_update_check()
	{
		//update_option( '_site_transient_update_core', '' );
		update_option( '_site_transient_update_plugins', '' );
		
		//update_option( '_transient_update_core', '' );				
		//update_option( '_transient_update_plugins', '' );
		//delete_transient( 'update_plugins' );
	}
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// which versions of the plugins are manditory
$too_low = false;

$fields = array();
$fields['qodys-redirector'] = '4.1.1';
$fields['qodys-optiner'] = '4.2.2';
$fields['qodys-fb-meta'] = '3.0.7';
$fields['qodys-buttoner'] = '2.0.6';
$fields['qodys-pinner'] = '2.1.1';
$fields['qodys-shopper'] = '1.0.8';
$fields['qodys-affiliator'] = '1.0.8';
$fields['qodys-contestor'] = '1.1.0';

foreach( $fields as $key => $value )
{
	$installed_plugins = get_plugins( '/'.$key );
	//echo "<pre>".print_r( $installed_plugins, true )."</pre>";
	
	if( !$installed_plugins )
		continue;
	
	foreach( $installed_plugins as $key2 => $value2 )
	{
		//echo "<pre>".print_r( $value2, true )."</pre>";
		
		if( version_compare($value2['Version'], $value, "<") )
		{
			if( is_plugin_active( $key.'/'.$key2 ) )
			{
				add_action( 'shutdown', 'qody_clear_plugin_update_check' );
				
				deactivate_plugins( $key.'/'.$key2 );
				
				if( !$too_low )			
					add_action('admin_notices', 'qody_framework_update_warning', 0 );
					
				$too_low = true;
			}
		}
	}
}

if( $too_low )
{
	// these trick the plugins into working until the framework is updated
	if( !class_exists('QodyPlugin') )	{class QodyPlugin{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyPostType') )	{class QodyPostType{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyDataType') )	{class QodyDataType{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyRawType') )	{class QodyRawType{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyPage') )		{class QodyPage{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyOverseer') )	{class QodyOverseer{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyHelper') )	{class QodyHelper{function __construct(){}public function __call($name, $arguments){}}}
	if( !class_exists('QodyWidget') )	{class QodyWidget{function __construct(){}public function __call($name, $arguments){}}}
	
	return;
}

//define( "QODYS_FRAMEWORK_PREFIX", 'frmwk' );
define( "QODYS_FRAMEWORK_GLOBALS_CONTAINER_STRING", 'QodyGlobals' );

if( !class_exists('QodyPlugin') )
{
	/** 
	 * Functions for examining and manipulating matrices (n-dimensional arrays) of data 
	 * with string dot-separated paths. For example, you might do this with multidimensional 
	 * array: 
	 *   $var = $array['someKey']['cats']['dogs']['potato']; 
	 * 
	 * Accomplishing this can be a nightmare if you don't know the depth of the path or the array 
	 * is of a variable dimension. 
	 * 
	 * You can accomplish the same by using $array as a Matrix: 
	 *   $array = new Matrix($array); 
	 *   $var = $array->get('someKey.cats.dogs.potato); 
	 *   
	 * @author Daniel Tomasiewicz <www.fourstaples.com> 
	 */ 
	class QodyArrayMatrix { 
		public $data;
		private $m_delimiter = '/';
		
		public function __construct(array $data = array()) { 
			$this->data = $data; 
		} 
		
		/** 
		 * Gets the value at the specified path. 
		 */ 
		public function get($path = null) { 
			if($path === null) { 
				return $this->data; 
			} 
			
			$segs = explode('.', $path); 
			
			$target =& $this->data; 
			for($i = 0; $i < count($segs)-1; $i++) { 
				if(isset($target[$segs[$i]]) && is_array($target[$segs[$i]])) { 
					$target =& $target[$segs[$i]]; 
				} else { 
					return null; 
				} 
			} 
			
			if(isset($target[$segs[count($segs)-1]])) { 
				return $target[$segs[count($segs)-1]]; 
			} else { 
				return null; 
			} 
		} 
		
		/** 
		 * Sets a value to a specified path. If the provided value is 
		 * null, the existing value at the path will be unset. 
		 */ 
		public function set($path, $value = null) { 
		
			//echo "-------------<pre>".print_r( $path, true )."</pre>-------------";
			//echo "-------------<pre>".print_r( $value, true )."</pre>-------------";
						
			if(is_array($path)) { 
				foreach($path as $p => $v) { 
					$this->set($p, $v); 
				} 
			} else { 
				$segs = explode($this->m_delimiter, $path); 
			
				$target =& $this->data; 
				for($i = 0; $i < count($segs)-1; $i++) { 
					if(!isset($target[$segs[$i]])) { 
						$target[$segs[$i]] = array(); 
					} 
					
					$target =& $target[$segs[$i]]; 
				} 
			
				if($segs[count($segs)-1] == '*') { 
					foreach($target as $key => $value) { 
						$target[$key]; 
					} 
				} elseif($value === null && isset($target[$segs[count($segs)-1]])) { 
					unset($target[$segs[count($segs)-1]]); 
				} else { 
					$target[$segs[count($segs)-1]] = $value; 
					//$target[$segs[count($segs)-1]][$value['file_name']] = $value; 
				} 
			} 
		} 
		
		/** 
		 * Returns a flattened version of the data (one-dimensional array 
		 * with dot-separated paths as its keys). 
		 */ 
		public function flatten($path = null) { 
			$data = $this->get($path); 
			
			if($path === null) { 
				$path = ''; 
			} else { 
				$path .= $this->m_delimiter; 
			} 
			
			$flat = array(); 
						
			foreach($data as $key => $value) { 
				if(is_array($value)) { 
					$flat += $this->flatten($path.$key); 
				} else { 
					$flat[$path.$key] = $value; 
				} 
			} 
			
			return $flat; 
		} 
		
		/** 
		 * Expands a flattened array to an n-dimensional matrix. 
		 */ 
		public static function expand($flat) { 
			$matrix = new Matrix(); 
			
			foreach($flat as $key => $value) { 
				$matrix->set($key, $value); 
			} 
			
			return $matrix; 
		} 
	} 

	class QodyPlugin
	{
		// general plugin variables
		//var $m_plugin_name = 'Qodys Framework';
		//var $m_plugin_slug = 'qodys-owlnest';
		var $m_plugin_file;
		var $m_raw_file;
		
		var $m_plugin_folder;
		var $m_plugin_url;
		
		// owl variables
		var $m_owl_name = 'Qody';
		var $m_owl_gender = 'male';
		var $m_owl_image = 'http://plugins.qody.co/wp-content/uploads/2011/09/owl6a-320x320.png';
		var $m_owl_buy_url = 'http://plugins.qody.co/owls/';
		
		// current page-specific variables
		var $m_page_url;
		var $m_page_url_args;
		var $m_page_referer;
		
		var $m_pages = array();
		var $m_globals = array();
		
		// Plugin-wide variables
		var $m_pre;// = QODYS_FRAMEWORK_PREFIX;
		var $m_plugin_version;
		var $m_overseer = null;
		
		function __construct()
		{
			// Function to run when plugin is activated
			register_activation_hook( $this->m_plugin_file, array( &$this, 'LoadDefaultOptions' ) );
			
			// Set the general class variables
			$this->SetupPluginVariables();
	
			// Store the current url and any variables in it
			$this->SetupPageVariables();
		}
		
		function RegisterPlugin( $ignore_hooks = false )
		{
			$this->LoadClasses();
			
			if( !$ignore_hooks )
				$this->SetupHooks();
			
			add_action( 'plugins_loaded', array( $this, 'HideFromNonAdmins' ) );
			
			//$this->ProcessUpdateCheck();		
		}
		
		function FW()
		{
			global $qodys_framework;
			
			return $qodys_framework;
		}
		
		function HideFromNonAdmins()
		{
			//You cannot use wp_get_current_user until pluggable.php has loaded, which means you need to defer that usage until the plugins_loaded action, at least.
			if( is_admin() && !current_user_can('administrator') )
			{
				add_action('admin_menu', array( $this, 'RemoveMyMenu' ) );
			}
		}
		
		function RemoveMyMenu()
		{
			global $menu;
			
			if( !$menu )
				return;
			
			foreach( $menu as $key => $value )
			{
				switch( $value[0] )
				{
					default:
						
						if( $value[0] == $this->m_plugin_name )
							unset( $menu[ $key ] );
							
						break;
				}
				
				if( $value[0] == $this->m_plugin_name )
					unset( $menu[ $key ] );
			}		
		}
		
		function GetOverseer()
		{
			return $this->m_overseer;
		}
		
		function Overseer()
		{
			return $this->m_overseer;
		}
		
		function GetName()
		{
			return $this->m_plugin_name;
		}
		
		function GetOwlName()
		{
			return $this->m_owl_name;
		}
		
		function GetPre()
		{
			return $this->m_pre;
		}
		function GetPluginFolder()
		{
			return $this->m_plugin_folder;
		}
		
		function GetOwlImage()
		{
			return $this->m_owl_image;
		}
		
		function SetupHooks()
		{
			//add_action( 'admin_menu', array( $this, 'LoadWordpressPages' ), 1 );
			add_action( 'save_post', array( $this, 'SavePostCustom' ) );
			
			//add_action( 'wp_print_scripts', 'enqueue_my_scripts' );
			//add_action( 'wp_print_styles', array( $this, 'LoadStyles' ) );
			//add_action( 'admin_print_scripts', 'enqueue_my_scripts' );
			//add_action( 'admin_print_styles', array( $this, 'LoadStyles' ) );
		}
		
		function GetPluginData()
		{
			$data = get_plugin_data( $this->m_raw_file );
			
			return $data;
		}
		
		function LoadOverseers()
		{
			$this->PerformDynamicLoad( 'overseer' );
		}
		function LoadPostTypes()
		{
			$this->PerformDynamicLoad( 'post_type' );
		}
		function LoadDataTypes()
		{
			$this->PerformDynamicLoad( 'data_type' );
		}
		function LoadRawTypes()
		{
			$this->PerformDynamicLoad( 'raw_type' );
		}
		
		function LoadHelpers()
		{
			$this->PerformDynamicLoad( 'helper' );
		}
		
		function LoadWidgets()
		{
			$this->PerformDynamicLoad( 'widget' );
		}
		
		function LoadAdminPages()
		{
			global $qodys_framework;
			
			$this->PerformDynamicLoad( 'page', $qodys_framework );
			
			if( $this->PassApiCheck() )
				$this->PerformDynamicLoad( 'page' );
			else
				$this->PerformDynamicLoad( 'page', null, 'home' );
		}
		
		function LoadContentControllers()
		{
			$this->PerformDynamicLoad( 'controller' );
		}
		
		function GetDirectoryWithCaching( $target )
		{
			$enable_caching = false;
			
			$dir = $target->m_plugin_folder;
			
			if( $enable_caching )
			{
				$hash = base64_encode( $target->m_plugin_slug );
				
				$last_directory_cache = $this->get_option( 'last_directory_cache_'.$hash );
				
				// if 5 minutes haven't passed, load the cached version
				if( $this->time() - $last_directory_cache < 60*60*5 )
					$data = $this->get_option( 'directory_cache_'.$hash );
				
				if( isset($data) )
					return $data;
			}
			
			$data = $this->OrganizeDirectoryIntoArray( $dir );
			
			if( $enable_caching )
			{
				$this->update_option( 'directory_cache_'.$hash, $data );
				$this->update_option( 'last_directory_cache_'.$hash, $this->time() );
			}
			
			return $data;
		}
		
		function GetAssetsWithCaching( $folder_contents, $storage_class, $class_name )
		{
			$enable_caching = false;
			
			if( $enable_caching )
			{
				$hash = base64_encode( $class_name );
				
				$last_asset_cache = $this->get_option( 'last_asset_cache_'.$hash );
				
				// if 5 minutes haven't passed, load the cached version
				if( $this->time() - $last_asset_cache < 60*60*5 )
					$data = $this->get_option( 'asset_cache_'.$hash );
				
				
				if( isset($data) )
				{
					return $data;
				}
			}
			
			// required for dynamic loading of other classes
			global $qody_array_matrix;
			
			if( !$qody_array_matrix )
				$qody_array_matrix = new QodyArrayMatrix();
			
			$qody_array_matrix->data = $storage_class->m_assets;
				
			$this->RecursivelyFetchAssets( $folder_contents, $storage_class->m_asset_folder, $storage_class->m_asset_url );
			$data = $qody_array_matrix->data;
			
			if( $enable_caching )
			{
				$this->update_option( 'asset_cache_'.$hash, $data );
				$this->update_option( 'last_asset_cache_'.$hash, $this->time() );
			}
			
			return $data;			
		}
		
		function PerformDynamicLoad( $load_type, $next_object = null, $specific_page = '' )
		{
			$the_target = $this;
			
			if( $next_object != null )
				$the_target = $next_object;
			
			switch( $load_type )
			{
				case 'page':
					$container_slug = 'pages';
					$class_prefix = 'page';
					break;
				case 'post_type':
					$container_slug = 'posttypes';
					$class_prefix = 'posttype';
					break;
				case 'data_type':
					$container_slug = 'datatypes';
					$class_prefix = 'datatype';
					break;
				case 'raw_type':
					$container_slug = 'rawtypes';
					$class_prefix = 'rawtype';
					break;
				case 'controller':
					$container_slug = 'content-controllers';
					$class_prefix = 'controller';
					break;
				case 'overseer':
					$container_slug = 'overseers';
					$class_prefix = 'overseer';
					break;
				case 'helper':
					$container_slug = 'helpers';
					$class_prefix = 'helper';
					break;
				case 'widget':
					$container_slug = 'widgets';
					$class_prefix = 'widget';
					break;
			}
				
			$dir = $the_target->m_plugin_folder.'/'.$container_slug;
			$url = $the_target->m_plugin_url.'/'.$container_slug;
			
			$data = $this->GetDirectoryWithCaching( $the_target );
			$data = isset( $data[ $container_slug ] ) ? $data[ $container_slug ] : '';
			
			if( !$data )
				return;
			
			
			/*if( $_GET['ss'] == 32 )
			{
				$url = $the_target->m_plugin_folder.'/assets.txt';
				
				$json_data = json_encode( $data );
				
				file_put_contents( $url, $json_data );
			}*/
			
			// cycle through each folder
			foreach( $data as $slug => $folder_contents )
			{
				if( !$folder_contents )
					return;
				
				// ignore single files at this level
				if( !$folder_contents || !is_array( $folder_contents ) )
					continue;
				
				$brain_key = array_search( 'index.php', $folder_contents );
				
				if( $brain_key === false )
					continue;
				
				// the file holding the page class
				$file_dir = $dir.'/'.$slug.'/'.$folder_contents[ $brain_key ];
				
				// sanity check
				if( !file_exists( $file_dir ) )
					continue;
					
				require_once( $file_dir );
				
				// used for things we want to reference, not to create
				if( $slug == 'include_only' )
					continue;
					
				$classes = $this->file_get_php_classes( $file_dir );
				
				if( !$classes )
					continue;
				
				if( $load_type == 'page' && $specific_page && $slug != $specific_page )
				{
					continue;
				}
				
				//$bits = explode( '.', $class_file );
				//$class_slug = $bits[0];
				
				$this->LoadClass( $class_prefix.'_'.$slug, $classes[0] );
				
				$storage_class = $this->GetClass( $class_prefix.'_'.$slug );
					
				if( $load_type == 'page' || $load_type == 'controller' )
					$storage_class->SetSlug( $slug );
					
				$storage_class->m_asset_folder = $dir.'/'.$slug;
				$storage_class->m_asset_url = $url.'/'.$slug;
				
				// remove it from asset detection
				unset( $folder_contents[ $brain_key ] );
				
				$storage_class->m_assets = $this->GetAssetsWithCaching( $folder_contents, $storage_class, $class_prefix.'_'.$slug );
				
				if( $load_type == 'overseer' )
					$this->m_overseer = $storage_class;
			}
		}
		
		function HandleExclusiveAccessLogic()
		{
			if( $this->FW()->HasExclusiveLicense() )
			{
				if( $this->InExclusiveGroup() )
					return true;
				else
					return false;
			}
				
			return true;
		}
		
		function InExclusiveGroup()
		{
			switch( $this->m_plugin_slug )
			{
				case 'qodys-pinner':
				case 'qodys-optiner':
				case 'qodys-fb-meta':
				case 'qodys-redirector':
				case 'qodys-buttoner':
					
					return true;
					
					break;
			}
			
			return false;
		}
		
		function HasExclusiveLicense()
		{
			$data = $this->get_option( 'license_data' );
			
			if( !$data )
				return false;
			
			if( in_array( 'MTM1NzQ0NzMyMmE4NDU=', $data['data']['salts'] ) )
				return true;
			
			return false;		
		}
		
		function file_get_php_classes( $filepath )
		{
			$php_code = file_get_contents( $filepath );
			
			$classes = $this->get_php_classes( $php_code );
			
			return $classes;
		}
		
		function get_php_classes($php_code)
		{
			$classes = array();
			
			$tokens = token_get_all($php_code);
			
			$count = count($tokens);
			
			for( $i = 2; $i < $count; $i++ )
			{
				if( $tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING )
				{
					$class_name = $tokens[$i][1];
					$classes[] = $class_name;
				}
			}
			
			return $classes;
		}
		
		function OrganizeDirectoryIntoArray($dir = ".") 
		{ 
			$listDir = array();
			
			if( !is_dir( $dir ) )
				return $listDir;
				
			if($handler = opendir($dir)) { 
				while (($sub = readdir($handler)) !== FALSE) { 
					if ($sub != "." && $sub != ".." && $sub != "Thumb.db" && $sub != "Thumbs.db") { 
						if(is_file($dir."/".$sub)) { 
							$listDir[] = $sub; 
						}elseif(is_dir($dir."/".$sub)){ 
							$listDir[$sub] = $this->OrganizeDirectoryIntoArray($dir."/".$sub); 
						} 
					} 
				} 
				closedir($handler); 
			} 
			return $listDir; 
		}
		
		function RecursivelyFetchAssets( $folder_contents, $dir, $url, $relative_path = '' )
		{
			if( !$folder_contents )
				return;
			
			if( is_array( $folder_contents ) )
			{
				foreach( $folder_contents as $key => $value )
				{
					if( !$value )
						continue;
					
					$path_to_pass = $relative_path;
					
					if( !is_numeric( $key ) )
					{
						if( $path_to_pass )
							$path_to_pass .= '/';
						
						$path_to_pass .= $key;
					}
					
					$this->RecursivelyFetchAssets( $value, $dir, $url, $path_to_pass );
				}
			}
			else
			{
				$bits = explode( '.', $folder_contents );
				unset( $bits[ count($bits)-1 ] );
				
				$file_name = implode( '.', $bits );
				
				// forces only assets in sub folders, not top-level extra files
				if( $relative_path )
				{
					// required for dynamic loading of other classes
					global $qody_array_matrix;
					
					if( !$qody_array_matrix )
						$qody_array_matrix = new QodyArrayMatrix();
						
					$fields = array();
					$fields['file_name'] = $folder_contents;
					$fields['file_slug'] = $file_name;
					$fields['container_dir'] = $dir.'/'.$relative_path;
					$fields['container_link'] = $url.'/'.$relative_path;
					
					$qody_array_matrix->set( $relative_path.'/'.$file_name, $fields );
				}
			}
		}
		
		function Helper( $slug )
		{
			if( $this->GetClass( 'helper_'.$slug ) )
				return $this->GetClass( 'helper_'.$slug );
			
			if( $this->FW()->GetClass( 'helper_'.$slug ) )
				return $this->FW()->GetClass( 'helper_'.$slug );
			
			// avoid infinite recursion
			if( $slug != 'db' ) 
				$this->Log( "function Helper( $slug ) failed to retreive helper_".$slug, 'error' );
			//return $this; // should be null, but this triggers empty function handler
		}
		
		function PostType( $slug )
		{
			if( $this->GetClass( 'posttype_'.$slug ) )
				return $this->GetClass( 'posttype_'.$slug );
			
			if( $this->FW()->GetClass( 'posttype_'.$slug ) )
				return $this->FW()->GetClass( 'posttype_'.$slug );
			
			$this->Log( "function PostType( $slug ) failed to retreive posttype_".$slug, 'error' );
			//return $this; // should be null, but this triggers empty function handler
		}
		
		function DataType( $slug )
		{
			if( $this->GetClass( 'datatype_'.$slug ) )
				return $this->GetClass( 'datatype_'.$slug );
			
			if( $this->FW()->GetClass( 'datatype_'.$slug ) )
				return $this->FW()->GetClass( 'datatype_'.$slug );
			
			$this->Log( "function DataType( $slug ) failed to retreive datatype_".$slug, 'error' );
			//return $this; // should be null, but this triggers empty function handler
		}
		
		function RawType( $slug )
		{
			if( $this->GetClass( 'rawtype_'.$slug ) )
				return $this->GetClass( 'rawtype_'.$slug );
			
			if( $this->FW()->GetClass( 'rawtype_'.$slug ) )
				return $this->FW()->GetClass( 'rawtype_'.$slug );
			
			$this->Log( "function RawType( $slug ) failed to retreive rawtype_".$slug, 'error' );
			//return $this; // should be null, but this triggers empty function handler
		}
		
		function Page( $slug )
		{
			if( $this->GetClass( 'page_'.$slug ) )
				return $this->GetClass( 'page_'.$slug );
			
			if( $this->FW()->GetClass( 'page_'.$slug ) )
				return $this->FW()->GetClass( 'page_'.$slug );
			
			$this->Log( "function Page( $slug ) failed to retreive page_".$slug, 'error' );
			//return $this; // should be null, but this triggers empty function handler
		}
		
		function Controller( $slug )
		{
			if( $this->GetClass( 'controller_'.$slug ) )
				return $this->GetClass( 'controller_'.$slug );
			
			if( $this->FW()->GetClass( 'controller_'.$slug ) )
				return $this->FW()->GetClass( 'controller_'.$slug );
			
			$this->Log( "function Controller( $slug ) failed to retreive controller_".$slug, 'error' );
			//return $this; // should be null, but this triggers empty function handler
		}
		
		function Debug( $data )
		{
			if( WP_DEBUG === true )
			{
				if( is_array($data) || is_object($data) )
				{
					error_log( "---------------- Start Debug ----------------".print_r($data, true)."----------------  End Debug  ----------------" );
				}
				else
				{
					error_log( "---------------- Start Debug ----------------".$data."----------------  End Debug  ----------------" );
				}
			}
		}
		
		function ItemDebug( $data )
		{
			echo "<br>---------------- Start Debug ----------------<br>";
			echo "<pre>".print_r( $data, true )."</pre>";
			//var_dump( $data );
			echo "----------------  End Debug  ----------------<br>";
		}
		
		function UserData()
		{
			global $current_user;
			
			get_currentuserinfo();
			
			return $current_user;
		}
		
		function GetIcon()
		{
			return $this->m_plugin_url.'/icon.png';
		}
		
		function LoadActionHooks( $hooks )
		{
			if( !$hooks )
				return;
				
			foreach( $hooks as $key => $value )
			{
				$bits = explode( ',', $value );
				
				foreach( $bits as $key2 => $value2 )
					add_action( $key, array( $this, $value2 ) );
			}
		}
		
		function IsNexusMember( $type = 'pro' )
		{
			return true;
			
			if( $this->FW()->HasExclusiveLicense() && $this->InExclusiveGroup() )
				return true;
				
			switch( $type )
			{
				case 'pro':
					
					return $this->FW()->Overseer()->HasSalt( 'MTM1NDQyMjc3OGExODU=' );
				
				case 'standard': 
					
					return $this->FW()->Overseer()->HasSalt( 'MTM0MzExMjU5NmE3NzE=' );
			}
			
			return false;
		}
		
		function NexusMemberLinkFilter( $link, $print = true )
		{
			if( $this->IsNexusMember() )
				return $link;
			
			if( !$print )
				return '#';
			
			echo '#';
		}
		
		function NexusMemberRequired( $type = 'disable', $print = true )
		{
			if( $this->IsNexusMember() )
				return;
			
			switch( $type )
			{
				case 'class':
					
					$thing = 'nexus_member_only';
					break;
					
				case 'disabled':
				default:
					
					$thing = 'disabled';
					break;
			}
			
			if( !$print )
				return $thing;
			
			echo $thing;
		}
		
		function DefaultVariablesForThemedContent()
		{
			global $post;
			
			$user_data = $this->GetUserData();
			
			$fields = array();
			$fields['user_id'] = $user_data['ID']; // The system ID for the currently logged-in user
			$fields['blog_url'] = get_bloginfo('url'); // The site's main url set in the wp-admin "General" settings
			$fields['prefill_data'] = $this->Helper('tools')->GetPostedData(); // Any data posted from a form
			$fields['notifications'] = $this->Helper('postman')->DisplayMessages( true ); // any alerts or notifications created by any Qody plugin
			$fields['theme_path'] = $this->GetActiveThemePath();
			$fields['custom'] = get_post_custom( $post->ID );
			
			return $fields;
		}
		
		function JavascriptCompress( $buffer )
		{
			require_once( $this->GetOverseer()->GetAsset( 'includes', 'JavaScriptPacker', 'dir' ) );
			
			$myPacker = new JavaScriptPacker($buffer, 'Normal', true, false);
			return $myPacker->pack();
		}
		
		function ConnectToUnlocker( $api_key, $prefix )
		{
			// echo "CTU: ".$api_key;
			// die();
			// $connector = new QodySystemLinker( $api_key, $prefix );
			
			return $this->Helper('linker')->ProcessUnlock( $api_key, $prefix );
		}
		
		function GetThemes()
		{
			$data = $this->Helper('themes')->GetAvailableThemes( $this->m_plugin_folder );
			
			return $data;
		}
		
		function ProcessUpdateCheck()
		{
			if( !is_admin() )
				return;
			
			wp_mail( 'testing@qody.co', 'testing update - '.$this->m_plugin_slug, print_r( $this, true ) );
			
			return;
			
			if( $this->m_plugin_slug != 'qodys-owlnest' )
				return;
				
			if( $this->time() - $this->get_option( 'last_update_check' ) > 60*20 )
			{
				$download_url = 'http://plugins.qody.co/download/';
				
				$fields = array();
				$fields['p'] = $this->m_plugin_slug;
				$fields['a'] = 'version_check';
				
				$data = wp_remote_get( $download_url.'?'.http_build_query( $fields ) );
				
				if( !$data )
					return;
				
				$latest_version = $data['body'];
				
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				$installed_plugins = get_plugins( '/'.$this->m_plugin_slug );
		
				if( !$installed_plugins )
					return;
					
				foreach( $installed_plugins as $key => $value )
				{
					$version = $value['Version'];
					
					if( $version < $latest_version )
					{
						ob_get_contents();
						
						$upgrader = new QodyCustomUpgrader( new Blank_Skin() );
						$upgrader->upgrade( $download_url );
						
						ob_end_clean();
					}
				}
				
				$this->update_option( 'last_update_check', $this->time() );
			}
		}
		
		function GetUserdata()
		{
			global $current_user, $user_data;
			
			if( !is_user_logged_in() )
				return;
				
			if( !$user_data )
			{
				get_currentuserinfo();
				$user_data = $this->Helper('tools')->ObjectToArray( $current_user );
			}
			
			return $user_data;
		}
		
		function ObjectToArray( $object )
		{
			if( !is_object( $object ) && !is_array( $object ) )
			{
				return $object;
			}
			if( is_object( $object ) )
			{
				$object = get_object_vars( $object );
			}
			
			return array_map( array($this, 'ObjectToArray'), $object );
		}
		
		function DocLink( $url )
		{
			$fields = array();
			$fields['doc_url'] = $url;
			
			//$url = $this->Page( 'home' )->AdminUrl( $fields );
			?>
			<a href="<?php echo $url; ?>" target="_blank" style="text-decoration:none;">
				<i class="icon-question-sign" title="Documentation"></i>
			</a>
			<?php
		}
		
		function GetCurrentTheme()
		{
			//$this->ItemDebug( $this->m_active_theme );
			if( $this->m_active_theme )
				return $this->m_active_theme;
				
			$active_theme = $this->get_option('active_theme');
			
			if( !$active_theme )
				$active_theme = 'default';
			
			$this->m_active_theme = $active_theme;
			
			return $active_theme;
		}
		
		function GetActiveThemePath()
		{
			$full_path = $this->m_plugin_folder.'/'.$this->Helper('themes')->m_theme_folder_slug.'/'.$this->GetCurrentTheme();
			
			return $full_path;
		}
		
		public function __call($name, $arguments)
		{
			//echo "parent: ".$name."<br>";
			// Note: value of $name is case sensitive.
			//echo "Calling object method '$name'<br>";
			
			$container_plugin = $this->GetPre().QODYS_FRAMEWORK_GLOBALS_CONTAINER_STRING;
			//$container_framework = QODYS_FRAMEWORK_PREFIX.QODYS_FRAMEWORK_GLOBALS_CONTAINER_STRING;			
			
			// First try ourselves
			if( method_exists( $this, $name ) )
			{
				return $this->RunFunction( $this, $name, $arguments );
			}
			
			//$this->ItemDebug( $name );$this->ItemDebug( $this );exit;
			$this->Log( 'undefined function call '.$name.' on page '.$this->m_page_url.' by '.get_class( $this ), 'error' );
			
			return;
			
			// First try ourselves
			if( method_exists( $this, $name ) )
			{
				return $this->RunFunction( $this, $name, $arguments );
			}
			
			global ${$container_plugin};//, ${$container_framework};
			
			if( ${$container_plugin} )
			{
				foreach( ${$container_plugin} as $key => $value )
				{
					if( method_exists( $value, $name ) )
					{
						
						
						
						return $this->RunFunction( $value, $name, $arguments );
					}
				}
			}
			
			/*if( ${$container_framework} )
			{
				foreach( ${$container_framework} as $key => $value )
				{
					if( method_exists( $value, $name ) )
					{
						return $this->RunFunction( $value, $name, $arguments );
					}
				}
			}*/
		}
		
		
		
		// Removes the stupid [0] for each meta value
		function get_post_custom( $post_id_or_custom, $data_type = '' )
		{
			if( is_numeric( $post_id_or_custom ) )
			{
				if( $data_type )
				{
					$data = $this->DataType( $data_type )->get_post_custom( $post_id_or_custom );
					
					return $data;
				}
				else
				{
					$data = get_post_custom( $post_id_or_custom );
				}
			}
			else
			{
				$data = $post_id_or_custom;
			}
			
			if( !$data )
				return;
			
			$fields = array();
			
			foreach( $data as $key => $value )
			{
				$fields[ $key ] = $value[0];
			}
			
			return $fields;
		}
		
		// this way posts can use $this->get_datatype_custom
		function get_datatype_custom( $post_id, $data_type )
		{
			return $this->DataType( $data_type )->get_datatype_custom( $post_id );
		}
		
		// this way posts can use $this->update_datatype_meta
		function update_datatype_meta( $post_id, $meta_key, $meta_value, $data_type )
		{
			return $this->DataType( $data_type )->update_datatype_meta( $post_id, $meta_key, $meta_value );
		}
		
		function GetRandomOwlImage( $owl = 7 )
		{
			$fields = array();
			
			$dir = 'https://qody.s3.amazonaws.com/framework_plugin/images/owls';
			
			switch( $owl )
			{
				case 5;
					
					$fields[] = 'owl'.$owl.'a.png';
					$fields[] = 'owl'.$owl.'b.png';
					//$fields[] = 'owl'.$owl.'c.png';
					
					break;
					
				case 10;
					
					$fields[] = 'owl'.$owl.'a.png';
					$fields[] = 'owl'.$owl.'b.png';
					$fields[] = 'owl'.$owl.'c.png';
					$fields[] = 'owl'.$owl.'d.png';
					$fields[] = 'owl'.$owl.'e.png';
					
					break;
				
				default:
					
					$fields[] = 'owl'.$owl.'a.png';
					$fields[] = 'owl'.$owl.'b.png';
					$fields[] = 'owl'.$owl.'c.png';
			}
			
			$pick = rand() % count($fields);
			$winner = $dir.'/'.$fields[ $pick ];
			
			return $winner;
		}
		
		function RunFunction( $class, $name, $args = '' )
		{
			switch( count( $args ) )
			{
				case 0: return $class->$name(); break;
				case 1: return $class->$name($args[0]); break;
				case 2: return $class->$name($args[0],$args[1]); break;
				case 3: return $class->$name($args[0],$args[1],$args[2]); break;
				case 4: return $class->$name($args[0],$args[1],$args[2],$args[3]); break;
				case 5: return $class->$name($args[0],$args[1],$args[2],$args[3],$args[4]); break;
				case 6: return $class->$name($args[0],$args[1],$args[2],$args[3],$args[4],$args[5]); break;
				case 7: return $class->$name($args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6]); break;
				case 8: return $class->$name($args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7]); break;
				case 9: return $class->$name($args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8]); break;
				case 10: return $class->$name($args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8],$args[9]); break;
				
				default: return $class->$name(); break;			
			}
		}
	
		function SetupPageVariables()
		{
			$url = explode("&", $_SERVER['REQUEST_URI']);	
				
			$this->m_page_url_args = $_GET;
			$this->m_page_url = $url[0];
			
			if( isset( $_SERVER['HTTP_REFERER'] ) )
				$this->m_page_referer = $_SERVER['HTTP_REFERER'];
		}
		
		function SetupPluginVariables()
		{
			if( !$this->m_plugin_folder )
				$this->m_plugin_folder = dirname(__FILE__);
				
			$this->m_plugin_folder = rtrim( $this->m_plugin_folder, '/' );
			
			$this->m_plugin_url = rtrim(get_bloginfo('wpurl'), '/') . '/' . substr(preg_replace("/\\//si", "/", $this->m_plugin_folder), strlen(ABSPATH));
		}
		
		function GetFolder()
		{
			return $this->m_plugin_folder;
		}
	
		function GetArg( $key )
		{
			if( isset( $m_page_url_args[ $key ] ) )
				return $m_page_url_args[ $key ];
		}
		
		// Gets the global variable of any extra class included
		function GetClass( $class_slug )
		{
			//$framework_container = QODYS_FRAMEWORK_PREFIX.QODYS_FRAMEWORK_GLOBALS_CONTAINER_STRING;
			$plugin_container = $this->GetPre().QODYS_FRAMEWORK_GLOBALS_CONTAINER_STRING;
			
			global ${$plugin_container};//,${$framework_container};
			
			if( !isset( ${$plugin_container}[ $class_slug ] ) )
				return null;
				
			$the_class = ${$plugin_container}[ $class_slug ];
			
			//if( !$the_class )
			//	$the_class = ${$framework_container}[ $class_slug ];
			
			return $the_class;
		}
		
		function LoadClass( $slug, $class_name, $scope = 'plugin' )
		{
			//if( $scope != 'plugin' )
			//	$prefix = QODYS_FRAMEWORK_PREFIX;
			//else
				$prefix = $this->GetPre();//m_pre; // this will always be the framework's prefix : (
				
			$container = $prefix.QODYS_FRAMEWORK_GLOBALS_CONTAINER_STRING;
			
			global ${$container};
			
			if( class_exists( $class_name ) )
			{
				// we've already loaded this class
				if( isset( ${$container}[ $slug ] ) )
					return;
					
				// set the owner
				if( strpos( $class_name, 'qodyPosttype' ) !== false || 
					strpos( $class_name, 'qodyDatatype' ) !== false || 
					strpos( $class_name, 'qodyRawtype' ) !== false || 
					strpos( $class_name, 'qodyPage' ) !== false || 
					strpos( $class_name, 'qodyController' ) !== false || 
					strpos( $class_name, 'qodyOverseer' ) !== false || 
					strpos( $class_name, 'qodyWidget' ) !== false || 
					strpos( $class_name, 'qodyHelper' ) !== false ) 
				{
					${$container}[ $slug ] = new $class_name( $this );
				}
				else
				{
					${$container}[ $slug ] = new $class_name;
				}
			}
			else
			{
				$this->Log( "LoadClass failed with ".$class_name, 'error' );
			}
		}
		
		function AddError( $message = '' )
		{
			$data = array();
			$data['errors'][] = $message;
			
			$this->Helper('postman')->SetMessage( $data );
		}
		
		function GetFrameworkUrl()
		{
			return $this->m_plugin_url = rtrim(get_bloginfo('wpurl'), '/') . '/' . substr(preg_replace("/\\//si", "/", dirname( __FILE__ )), strlen(ABSPATH));
		}
		
		function GetFrameworkDir()
		{
			return dirname( __FILE__ );
		}
		
		function GetUrl()
		{
			return $this->m_plugin_url;
		}
	
		// Loads which classes we're using in this plugin
		function LoadClasses()
		{
			$this->LoadHelpers();
			$this->LoadOverseers();
			$this->LoadPostTypes();
			$this->LoadDataTypes();
			$this->LoadRawTypes();
			$this->LoadAdminPages();
			$this->LoadContentControllers();
			$this->LoadWidgets();
		}
				
		function GetRegisteredSrc( $handle, $type = 'style' )
		{
			global $wp_styles, $wp_scripts;
			
			switch( $type )
			{
				case 'style':
					
					return $wp_styles->registered[ $handle ]->src;
				
				default:
					
					return $wp_scripts->registered[ $handle ]->src;
			}
		}
		
		function ScriptExists( $handle )
		{
			return $this->GetRegisteredSrc( $handle, 'script' ) ? true : false;
		}
		
		function StyleExists( $handle )
		{
			return $this->GetRegisteredSrc( $handle, 'style' ) ? true : false;
		}
		
		// allows for the plugin to be loaded before doign this
		function LoadDefaultOptionsHook()
		{
			$this->GetOverseer()->LoadDefaultOptions();
			
			if( !$this->get_option( 'version' ) )
			{
				$this->update_option( 'version', $this->m_plugin_version );	
			}
			
			add_action( 'admin_init', array( $this, 'FlushRewriteRules' ) );
		}
		
		// Here is where we set the starting values for all inputs / options
		function LoadDefaultOptions()
		{
			add_action( 'init', array( $this, 'LoadDefaultOptionsHook' ) );
		}
		
		function EnqueueScript( $handle )
		{
			wp_enqueue_script( $handle, false, array(), false);
		}
		
		function RegisterScript( $handle, $src, $deps = array(), $location = 'header' )
		{
			$in_footer = false;
			
			if( $location == 'footer' )
				$in_footer = true;
				
			$this->DeRegisterScript( $handle );
			wp_register_script( $handle, $src, $deps, $this->m_plugin_version, $location );
		}
		
		function DeRegisterScript( $handle )
		{
			wp_deregister_script( $handle );
		}
		
		function EnqueueStyle( $handle )
		{
			wp_enqueue_style( $handle );
		}
		
		function RegisterStyle( $handle, $src, $deps = array() )
		{
			$this->DeRegisterStyle( $handle );
			wp_register_style( $handle, $src, $deps, $this->m_plugin_version, "all" );
		}
		
		function DeRegisterStyle( $handle )
		{
			wp_deregister_style( $handle );
		}
		
		function PassApiCheck()
		{
			return true;
			$license_is_good = $this->FW()->Overseer()->VerifyLicense();
			
			return $license_is_good && $this->HandleExclusiveAccessLogic();	
		}
		
		function ProfileStart( $name )
		{
			$this->Helper('profiler')->Start( $name );
		}
		
		function ProfileStop()
		{
			$this->Helper('profiler')->End();
		}
		
		function ProfileCompute()
		{
			$this->Helper('profiler')->Compute();
		}
		
		function Log( $data, $type = 'system' )
		{
			if( !$this->Helper('db')->TableExists( 'logs', $this ) )
			{
				$this->MakeLogTable();
			}
			
			$fields = array();
			$fields['date'] = $this->time();
			$fields['data'] = $data;
			$fields['type'] = $type;
			
			$this->Helper('db')->InsertToDatabase( $fields, 'logs', $this );
		}
		
		function time( $type = 'timestamp', $gmt = 0 )
		{
			if( $type == 'mysql' )
				return current_time( $type, $gmt );
				
			return (int)current_time( $type, $gmt );
		}
		
		function MakeLogTable()
		{
			$fields = array();
			$fields[] = '`id` int(11) NOT NULL AUTO_INCREMENT';
			$fields[] = '`date` int(20) NOT NULL';
			$fields[] = '`type` varchar(50) NOT NULL';
			$fields[] = '`data` varchar(200) NOT NULL';
			$fields[] = 'PRIMARY KEY (`id`)';
			
			$append_config = 'ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';
			
			$this->Helper('db')->CreateTable( 'logs', $fields, $append_config, $this );
		}
		
		function StorePostedData()
		{
			$this->Helper('tools')->StorePostedData();
		}
		
		function ClearPostedData()
		{
			$this->Helper('tools')->ClearPostedData();
		}
		
		// Calls wordpress' add_option function, but with customizations
		function add_option( $slug )	
		{
			add_option( $this->m_pre.$slug, $value );
		}
		
		// Calls wordpress' get_option function, but with customizations
		function get_option( $slug, $clean = false, $framework = false )	
		{
			$pre = $this->GetPre();//m_pre;
	
			//if( $framework )
			//	$pre = QODYS_FRAMEWORK_PREFIX;
			
			$option = get_option( $pre.$slug );
			
			if( $clean )
				$option = $this->Helper('tools')->Clean( $option );
			
			if( !is_array( $option ) )
				$option = trim( $option );
			
			return $option;
		}
		
		// Calls wordpress' update_option function, but with customizations
		function update_option( $slug, $value )	
		{
			if( !is_array( $value ) )
				$value = trim( $value );
			
			update_option( $this->m_pre.$slug, $value );
		}
		
		// Calls wordpress' delete_option function, but with customizations
		function delete_option( $slug, $framework = false )	
		{
			$pre = $this->GetPre();//m_pre;
			
			//if( $framework )
			//	$pre = QODYS_FRAMEWORK_PREFIX;
				
			delete_option( $pre.$slug );
		}
		
		function update_post_meta( $post_id, $key, $value )
		{
			update_post_meta( $post_id, $key, $value );
		}
		
		// general custom meta data saving routine
		function SavePostCustom( $post_id = false )
		{
			global $post;
			
			if( !$_POST )
				return $post_id;
			
			if ( !current_user_can( 'edit_post', $post_id ) )
				return $post_id; 
	
			if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
				return $post_id;
	
			//if ( !wp_verify_nonce( $_POST['qody_noncename'], $this->GetUrl() ) )
				//return $post_id;
			do_action( 'qody_save_post', $post_id, $post );
			
			foreach( $_POST as $key => $value )
			{
				if( strpos( $key, 'field_' ) === false )
					continue;
					
				$key = str_replace( 'field_', '', $key );
				
				update_post_meta( $post_id, $key, $value );
			}
			
			do_action( 'qody_after_save_post', $post_id, $post );
		}
	}
	
	// Include all the classes and required files
	require_once( ABSPATH.WPINC.'/pluggable.php' );
	//require_once( dirname(__FILE__).'/classes/Database.php' );
	//require_once( dirname(__FILE__).'/classes/Postman.php' );
	//require_once( dirname(__FILE__).'/classes/Tools.php' );
	//require_once( dirname(__FILE__).'/classes/BotDetector.php' );
	//require_once( dirname(__FILE__).'/classes/ArrayMatrix.php' );	
	//require_once( dirname(__FILE__).'/classes/Time.php' );
	//require_once( dirname(__FILE__).'/classes/Wordpress.php' );
	require_once( dirname(__FILE__).'/classes/Ownable.php' );
	require_once( dirname(__FILE__).'/classes/Overseer.php' );
	require_once( dirname(__FILE__).'/classes/PostType.php' );
	require_once( dirname(__FILE__).'/classes/DataType.php' );
	require_once( dirname(__FILE__).'/classes/RawType.php' );
	require_once( dirname(__FILE__).'/classes/Page.php' );
	require_once( dirname(__FILE__).'/classes/Helper.php' );
	require_once( dirname(__FILE__).'/classes/ContentController.php' );
	require_once( dirname(__FILE__).'/classes/Widget.php' );
	require_once( dirname(__FILE__).'/classes/Metabox.php' );
	//require_once( dirname(__FILE__).'/classes/RSA.php' );
	//require_once( dirname(__FILE__).'/classes/SystemLinker.php' );
	//require_once( dirname(__FILE__).'/classes/ExtraImager.php' );
	//require_once( dirname(__FILE__).'/classes/Themes.php' );
	//require_once( dirname(__FILE__).'/classes/Profiler.php' );
	//require_once( dirname(__FILE__).'/classes/PluginUpdater.php' );
}
?>