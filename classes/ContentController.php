<?php
class QodyContentController extends QodyOwnable
{
	var $m_option_slug = '';
	var $m_shortcode_attributes;
	
	function __construct()
	{
		add_action( 'admin_init', array( $this, 'AdminInit' ) );
		add_filter( 'template_redirect', array( $this, 'WhenOnPage' ) );
		
		add_action( 'save_post', array( $this, 'MaintainPageList_HandlePostUpdate' ) );
		add_action( 'before_delete_post', array( $this, 'MaintainPageList_HandlePostDelete' ) );
		add_action( 'init', array( $this, 'CreateShortcode' ) );
		add_action( 'wp', array( $this, 'CheckForShortcode' ) );
		
		parent::__construct();
	}
	
	function AdminInit()
	{
		
	}
	
	function CreateShortcode()
	{
		add_shortcode( $this->GetShortcodeSlug(), array( $this, 'DoShortcode' ) );
	}
	
	function GetShortcodeSlug()
	{
		return $this->GetPre().'-'.$this->m_option_slug;
	}
	
	function DoShortcode( $atts, $content = null )
	{
		if( isset( $atts['theme'] ) )
			$this->Owner()->m_active_theme = $atts['theme'];
		
		return $this->AddContentFilter( $action='do', '', $atts );
	}
	
	function CheckForShortcode()
	{
		global $post;
		
		if( !$post )
			return;
		
		// this is so we can set the appropriate theme in time to register the scripts of the theme chosen in a shortcode
		if( $this->PostHasMyShortcode( $post->ID ) )
		{
			$pattern = get_shortcode_regex();
			$matches = array();
			preg_match_all("/$pattern/s", $post->post_content, $matches);
			
			if( $matches )
			{
				foreach( $matches as $key => $value )
				{
					if( stripos($value[0], '[' . $this->GetShortcodeSlug()) !== false )
					{
						$data = shortcode_parse_atts( str_replace( ']', '', str_replace( '[', '', $value[0] ) ) );
						
						if( $data['theme'] && $data['theme'] != 'default' )
						{
							$this->Owner()->m_active_theme = $data['theme'];
						}
						
						break;
					}
				}
			}
		}
	}
	
	function GetFirstPostWithMyShortcode()
	{
		$data = $this->get_option( 'has_shortcode' );
		
		if( !$data )
			return;
		//$this->ItemDebug( $data );
		$shortcode = $this->GetShortcodeSlug();
		$data = $data[ $shortcode ];
		
		if( !$data )
			return;
		
		foreach( $data as $key => $value )
		{
			$post_data = get_post( $value );
			
			if( !$post_data )
				continue;
			
			if( $post_data->post_status != 'publish' && $post_data->post_status != 'private' )
				continue;
			
			return $post_data;
		}
		
		return;
	}
	
	function PostHasMyShortcode( $post_id = '' )
	{
		global $post;
		
		if( !$post_id && $post )
			$post_id = $post->ID;
		
		if( !$post_id )
			return false;
		
		$post_data = get_post( $post_id );
		
		if( !$post_data->post_content )
			return false;
			
		if( stripos($post_data->post_content, '[' . $this->GetShortcodeSlug()) !== false )
			return true;
		
		return false;
	}
	
	function SetSlug( $slug )
	{
		$this->m_option_slug = $slug;
	}
	
	function MakeFakePage( $post_to_clone = '' )
	{
		global $wp_query, $post, $datatype;
		
		$post = new stdClass();
		$post->ID = -1;
		$post->post_category = array('uncategorized'); //Add some categories. an array()???
		$post->post_content = $post_to_clone ? $post_to_clone->post_content : '';
		$post->post_excerpt = $post_to_clone ? $post_to_clone->post_excerpt : '';
		$post->post_status = 'publish'; //Set the status of the new post.
		$post->post_title = $post_to_clone ? $post_to_clone->post_title : 'Empty';
		$post->post_type = 'page'; //Sometimes you might want to post a page.
		
		$wp_query->queried_object = $post;
		$wp_query->post = $post;
		$wp_query->found_posts = 1;
		$wp_query->post_count = 1;
		$wp_query->max_num_pages = 1;
		$wp_query->is_single = 1;
		$wp_query->is_404 = false;
		$wp_query->is_posts_page = 1;
		$wp_query->posts = array( $post );
		$wp_query->page = false;
		$wp_query->is_post = true;
		$wp_query->page = false;
		
		$datatype = $post_to_clone;
	}
	
	// Run when we are on this page on the public site
	function WhenOnPage( $enforce_post_type = true, $override = false )
	{
		global $post;
		
		if( $this->PostHasMyShortcode() )
		{
			return true;
		}
			
		if( !$override )
		{
			// if we are on any admin screen
			if( is_admin() )
				return false;
			
			$page_id = $this->GetPageID();
			
			// if a page, and this isn't that page, skip
			if( $page_id && $post && $page_id != $post->ID )
				return false;
			
			// if a single post type, and not on that post type, skip
			if( $enforce_post_type )
			{
				if( get_post_type( $post ) != 'page' )
					return false;
			}
		}
		
		
		$this->AddContentFilter();
		
		return true;
	}
	
	function GetThemeFile( $relative_path, $allow_default = true )
	{
		$plugin_folder = $this->GetPluginFolder();
		$theme = $this->GetCurrentTheme();
		
		$full_path = $this->Helper('themes')->GetRealPath( $plugin_folder, $theme, $relative_path, $allow_default );
		
		return $full_path;
	}
	
	function LoadVisibleAssets()
	{
		$style_src = $this->GetThemeFile( 'style.css', false );
		
		if( $style_src )
		{
			$style_src = str_replace( WP_CONTENT_DIR, get_bloginfo('url').'/wp-content', $style_src );
			
			$this->DeRegisterStyle( $this->AssetPrefix().'controller_style' );
			$this->RegisterStyle( $this->AssetPrefix().'controller_style', $style_src );
			$this->EnqueueStyle( $this->AssetPrefix().'controller_style' );
		}
		
		$script_src = $this->GetThemeFile( 'script.js', false );
		
		if( $script_src )
		{
			$script_src = str_replace( WP_CONTENT_DIR, get_bloginfo('url').'/wp-content', $script_src );
			
			$this->DeRegisterScript( $this->AssetPrefix().'controller_script' );
			$this->RegisterScript( $this->AssetPrefix().'controller_script', $script_src );
			$this->EnqueueScript( $this->AssetPrefix().'controller_script' );
		}
			
		$this->EnqueueStyle( $this->AssetPrefix().'post_showing' );
		$this->EnqueueScript( $this->AssetPrefix().'post_showing' );
		
		$this->EnqueueStyle( $this->AssetPrefix().'post_view' );
		$this->EnqueueScript( $this->AssetPrefix().'post_view' );
		
		$this->EnqueueStyle( 'restricted-bootstrap' );
	}
	
	function GetPage( $attribute = 'data' )
	{
		$page_id = $this->Owner()->get_option( $this->m_option_slug.'_page' );
		
		if( $page_id )
			$page_data = get_post( $page_id );
		
		if( !$page_data )
			$page_data = $this->GetFirstPostWithMyShortcode();
		
		if( !$page_data )
			return;
		
		switch( $attribute )
		{
			case 'url':
				
				return get_permalink( $page_data->ID );
				
			case 'post_id':
				
				return $page_data->ID;
				
			case 'data':
			default:
				
				return $page_data;
		}
	}
	
	function MaintainPageList_HandlePostUpdate( $post_id )
	{
		$post_to_check = get_post( $post_id );
		
		if( !$post_to_check )
			return;
		
		if( $post_to_check->post_status != 'publish' && $post_to_check->post_status != 'private' )
			return;
		
		$shortcode = $this->GetShortcodeSlug();
		$data = $this->get_option( 'has_shortcode' );
		
		if( !is_array($data[ $shortcode ]) )
			$data[ $shortcode ] = array();
				
		if( $this->PostHasMyShortcode( $post_id ) )
		{
			if( !in_array( $post_id, $data[ $shortcode ] ) )
				$data[ $shortcode ][] = $post_id;
		}
		else
		{
			if( ($key = array_search($post_id, $data[ $shortcode ]) ) !== false )
			{
				unset( $data[ $shortcode ][ $key ] );
			}
		}
		
		$this->update_option( 'has_shortcode', $data );
	}
	
	function MaintainPageList_HandlePostDelete( $post_id )
	{
		$shortcode = $this->GetShortcodeSlug();
		$data = $this->get_option( 'has_shortcode' );
		
		if( ($key = array_search($post_id, $data[ $shortcode ]) ) !== false )
		{
			unset( $data[ $shortcode ][ $key ] );
		}
		
		$this->update_option( 'has_shortcode', $data );
	}
	
	function GetPageID()
	{
		// first search if there's a setting setting a particular page to be this one
		$page_id = $this->Owner()->get_option( $this->m_option_slug.'_page' );
		
		// next, try seeing if this page has a shortcode in it, and the right one
		
		// probably because we're on a single page of a post type
		if( !$page_id )
			return -1;
			
		return $page_id;
	}
	
	function GetFormattedContentText( $text )
	{
		$this->RemoveContentFilter();
		
		$text = apply_filters( 'the_content', $text );
		
		$this->AddContentFilter();
		
		return $text;
	}
	
	function AddContentFilter( $action = 'load', $old_post_content = '', $passed_variables = array() )
	{
		$data = '';
		
		if( $action == 'do' )
		{
			$data = $this->ContentFunction( $old_post_content, $passed_variables );
		}
		else
		{	
			add_filter( 'the_content', array( $this, 'ContentFunction' ) );
		}
		
		return $data;
	}
	
	function RemoveContentFilter()
	{
		remove_filter( 'the_content', array( $this, 'ContentFunction' ) );
	}
	
	function ManualQuickPrint( $calling_controller = null )
	{
		if( $calling_controller != null && !$calling_controller->PostHasMyShortcode() )
			$calling_controller->RemoveContentFilter();
		
		echo $this->AddContentFilter( $action='do' );
		
		if( $calling_controller != null && !$calling_controller->PostHasMyShortcode() )
			$calling_controller->AddContentFilter();
	}
	
	function RemoveImpurities()
	{
		// adds facebook comment meta twice
		if( function_exists( 'addSimpleFaceBookCommentsForWordpressOptionsPage' ) )
		{
			remove_filter( 'the_content', array( CommentsView::getInstance(), 'add_facebook_comments_to_end_of_the_content') );
		}
	}
	
	function ReAddImpurities()
	{
		// adds facebook comment meta twice
		if( function_exists( 'addSimpleFaceBookCommentsForWordpressOptionsPage' ) )
		{
			add_filter( 'the_content', array( CommentsView::getInstance(), 'add_facebook_comments_to_end_of_the_content') );
		}
	}
	
	function LoadThemedContent( $file_path, $echo = false, $plugin_folder = '', $theme = '' )
	{
		if( !$plugin_folder )
			$plugin_folder = $this->GetPluginFolder();
		
		if( !$theme )
			$theme = $this->GetCurrentTheme();
		
		$data = $this->Helper('themes')->LoadThemeContent( $plugin_folder, $theme, $file_path );
		
		if( $echo )
			echo $data;
		else
			return $data;
	}
	
	function LoadPage( $old_content = '', $alternative_view = false, $passed_variables = array() )
	{
		global $post;
		
		$user_data = $this->GetUserData();
		
		$this->LoadVisibleAssets();
		$defaults = $this->DefaultVariablesForThemedContent();
		
		$defaults = array_merge( $defaults, $passed_variables );
		
		if( $defaults )
			extract( $defaults );
		//if( $_GET['ss'] == 3 ) $this->ItemDebug( $defaults );
		// does the theme loading and server-side page processing
		include( $this->m_asset_folder.'/content.php' );
		
		return $content;
	}
	
	function ContentFunction( $old_content = '', $passed_variables = array() )
	{
		$this->RemoveImpurities();
		
		$data = $this->LoadPage( $old_content, $alternative_view=false, $passed_variables );
		
		$this->ReAddImpurities();
		
		return $data;
	}
	

}
?>