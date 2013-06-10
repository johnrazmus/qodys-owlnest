<?php
class QodyOwnable
{
	var $m_owner = null;
	
	var $m_assets = array();
	var $m_asset_folder;
	var $m_asset_url;
	
	var $m_metaboxes = array();
	var $m_priority = 1;
	
	function __construct()
	{
		add_action( 'init', array( $this, 'Init' ) );
	}
	
	function Init()
	{
		// Register the styles included with this page
		$this->RegisterStyles();
		$this->RegisterScripts();
	}
	
	function Owner()
	{
		return $this->m_owner;
	}
	
	function SetOwner( $constructor_args )
	{
		$owner = $constructor_args[0];
		
		if( !$owner )
			return;
			
		$this->m_owner = $owner;
	}
	
	function SetPriority( $priority, $spikeit = true )
	{
		$this->m_priority = $priority + 100;
		
		// if it's below 9, the home page for a plugin is overwritten by the first item
		if( $spikeit )
			$this->m_priority += 100;
	}
	
	function LoadMetaboxes()
	{
		// empty
	}
	
	function BootstrapTheForm( $bootstrap_type = 'admin' )
	{
		add_action( 'post_edit_form_tag' , array( $this, 'PrintBootstrapFormClass' ) );
		
		$this->EnqueueStyle( $bootstrap_type.'-bootstrap');
	}
	
	function PrintBootstrapFormClass()
	{
		echo ' class="form-horizontal"';
	}
	
	// run when a post is being added/edited of this type
	function WhenEditingPost( $post_type = 'post' )
    {
        global $post, $typenow, $pagenow;
        if( $post_type == 'post' )
		
		// if we aren't on the admin screen
        if( !is_admin() )
            return false;
       
		if( $pagenow != 'post-new.php' && $pagenow != 'post.php' )
			return false;
			
		// incase we are on the media edit screen
		if( $_GET['post_id'] )
			$post_content = get_post( $_GET['post_id'] );
		
		// inside an actual post edit screen
		if( !$post_content )
		{
			if( $_GET['post'] )
				$post_content = get_post( $_GET['post'] );
		}
		
		// if there's not a post being shown
		if( !$post_content && $pagenow != 'post-new.php' )
			return false;
		
		
		// if it's not the right post type
        if( $typenow != $post_type && $post->post_type != $post_type )
            return false;
		
		return true;
    }
		
	public function __call($name, $arguments)
	{
		if( $this->Owner() )
			return $this->Owner()->RunFunction( $this->Owner(), $name, $arguments );
		
		//echo "<pre>".print_r( $this, true )."</pre>";
		/*if( !$this->Owner() )
		{
			echo "ownable: ".$name."<br>";
			echo "---------------<pre>".print_r( $name, true )."</pre>---------------";
			echo "---------------<pre>".print_r( $this, true )."</pre>---------------";
			
			return;
		}*/
		
		// if the function doesn't belong to us, maybe it belongs to our owner?
		//return $this->Owner()->__call( $name, $arguments );
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
	
	function get_post_custom( $post_id = '' )
	{
		global $post;
		
		if( !$post_id )
			$post_id = $post->ID;
		
		$custom = $this->Custom_get_post_custom( $post_id );
		
		$defaults = $this->CustomDefaults();
		
		$custom = wp_parse_args( $custom, $defaults );
		
		return $custom;		
	}
	
	function CustomDefaults()
	{
		// inheritance
		return array();
	}
	
	function Custom_get_post_custom( $post_id_or_custom = '' )
	{
		if( is_numeric( $post_id_or_custom ) )
			$data = get_post_custom( $post_id_or_custom );
		else
			$data = $post_id_or_custom;
		
		if( !$data )
			return;
		
		$fields = array();
		
		foreach( $data as $key => $value )
		{
			$fields[ $key ] = $value[0];
		}
		
		return $fields;
	}
	
	function GetCoreMetaboxes()
	{
		$fields = array();
		$fields[] = 'submitdiv';
		$fields[] = 'slugdiv';
		
		return $fields;
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
	
	function AddMetabox( $file_slug, $title, $position = 'normal', $type_slug = '', $priority = 'low', $output_directly = false, $owner = null )
	{
		if( !$owner )
			$owner = $this;
		
		$file_slug = str_replace( ' ', '-', strtolower($file_slug) );
		
		$box = new QodyMetabox( $owner );
		
		$box->m_id = $this->GetPre().'-'.$file_slug;
		$box->m_title = $title;
		$box->m_file_slug = $file_slug;
		$box->m_position = $position;
		$box->m_priority = $priority;
		
		if( !$type_slug && $this->Owner() )
			$type_slug = $this->m_type_slug;
		
		if( $type_slug )
			$box->m_post_type = $type_slug;
		
		$this->m_metaboxes[ $file_slug ] = $box;
		
		if( $output_directly )
		{
			$box->Show();
		}
		else
		{
			$box->add_meta_box();
		}
	}
	
	function GetMetabox( $slug )
	{
		return $this->m_metaboxes[ $slug ];
	}
	
	function OutputMetabox( $file_slug )
	{
		$this->AddMetabox( $file_slug, '', '', '', '', true );
	}
	
	function GetAssetFolder()
	{
		$asset_folder = dirname( $this->m_raw_file );
		
		return $asset_folder;
	}
	
	function GetAssets( $folder = '', $file = '' )
	{
		if( !$folder )
			return $this->m_assets;
		
		if( !$file )
			return isset( $this->m_assets[ $folder ] ) ? $this->m_assets[ $folder ] : '';
		
		$data = $this->m_assets[ $folder ][ $file ];
		
		return $data;
	}
	
	function GetAsset()
	{
		// $folder, $file, $format
		$args = func_get_args();
		
		if( !$args )
			return;
			
		$folder = $args[0];
		
		$nextFolder = $this->GetAssets( $folder );
		
		foreach( $args as $key => $value )
		{
			// skip the first since it's our mail folder
			if( $key == 0 )
				continue;
			
			if( isset( $nextFolder[ $value ] ) && is_array( $nextFolder[ $value ] ) )
				$nextFolder = $nextFolder[ $value ];
			
			// we've reached the end; either the format identifyer or the last file name
			if( $key == (count($args)-1) )
			{
				switch( $value )
				{
					case 'dir':
						
						return $nextFolder['container_dir'].'/'.$nextFolder['file_name'];
						break;
						
					case 'url':
					default:
						
						return $nextFolder['container_link'].'/'.$nextFolder['file_name'];
						break;
				}
			}
		}
		
	}
	
	function AssetPrefix()
	{
		$prefix = '';
		
		if( isset( $this->m_menu_slug ) )
		{
			$prefix = $this->m_menu_slug;
		}
		else if( isset( $this->m_type_slug ) )
		{
			$prefix = $this->m_type_slug;
		}
		else if( isset( $this->m_option_slug ) )
		{
			$prefix = $this->m_option_slug;
		}
		//else
		//	$prefix = $this->GetPre();
		
		if( $prefix )
			$prefix .= '_';
			
		return $prefix;
	}
	
	function RegisterStyles()
	{
		$styles = $this->GetAssets('css');
		
		if( !$styles )
			return;
		
		foreach( $styles as $key => $value )
		{
			$this->RegisterStyle( $this->AssetPrefix().$value['file_slug'], $value['container_link'].'/'.$value['file_name'] );
		}
	}
	
	function RegisterScripts()
	{
		$scripts = $this->GetAssets('js');
		
		if( !$scripts )
			return;
		
		foreach( $scripts as $key => $value )
		{
			$this->RegisterScript( $this->AssetPrefix().$value['file_slug'], $value['container_link'].'/'.$value['file_name'] );
		}
	}
	
	function RemoveAllMetaboxesButMine( $exceptions = array(), $keep_core = true )
	{
		//if( !$this->m_metaboxes )
		//	return;
		
		$fields = array();
		
		if( $this->m_metaboxes )
		{
			foreach( $this->m_metaboxes as $key => $value )
			{
				$fields[] = $value->m_id;
			}
		}
		
		if( $keep_core )
		{
			$exceptions[] = 'postimagediv';
			$exceptions[] = 'submitdiv';
			$exceptions[] = 'postexcerpt';
			$exceptions[] = 'postdivrich';
			$exceptions[] = 'postdiv';
			$exceptions[] = 'pageparentdiv';
		}
		
		$fields = array_merge( $fields, $exceptions );
		
		$this->RemoveMetaboxes( $this->m_type_slug, $fields );
	}
	
	function RemoveMetaboxes( $post_type, $allowed = array(), $excluded = array() )
	{
		global $wp_meta_boxes;
		
		// if we're doin an exclusive list, remove hackish plugin hooks
		if( $allowed )
		{
			remove_all_actions( 'edit_form_advanced' );
			remove_all_actions( 'dbx_post_sidebar' );				
		}
		
		$relevant_boxes = $wp_meta_boxes[ $post_type ];
		
		if( !$relevant_boxes )
			return;
		
		foreach( $relevant_boxes as $key => $value )
		{
			$context = $key; // normal
			
			foreach( $value as $key2 => $value2 )
			{
				$group = $key2; // low, core
				
				if( $include_core && $group == 'core' )
					continue;
				
				foreach( $value2 as $key3 => $value3 )
				{
					$metabox_id = $value3['id']; // Campaigns-campaign_general
					
					if( $allowed && in_array( $metabox_id, $allowed ) )
						continue;
					
					if( $excluded && !in_array( $metabox_id, $excluded ) )
						continue;
						
					remove_meta_box( $metabox_id, $post_type, $context );
				}
			}
		}
	}
}
?>