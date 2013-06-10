<?php
class QodyPostType extends QodyOwnable
{
	var $m_type_slug 			= 'product';
	var $m_show_in_menu			= true;
	var $m_supports				= array();
	var $m_rewrite				= true;
	var $m_hierarchical			= false;
	
	var $m_list_columns 		= array();
	
	var $m_name 				= 'Products';
	var $m_singular_name 		= 'Product';
	var $m_add_new 				= 'Add New Product';
	var $m_add_new_item 		= 'Add New Product';
	var $m_edit_item 			= 'Edit Product';
	var $m_new_item 			= 'New Product';
	var $m_view_item 			= 'View Product';
	var $m_search_items 		= 'Search Products';
	var $m_not_found 			= 'No products found';
	var $m_not_found_in_trash 	= 'No products found in Trash';
	
	var $m_script_dependencies 	= array();
	
	function __construct()
	{
		$fields = array();
		
		// Standard WP
		$fields['admin_init'] = 'AdminInit,WhenViewingPostList';
		$fields['template_redirect'] = 'WhenShowing';
		$fields['admin_enqueue_scripts'] = 'WhenEditing';
		
		// Custom
		$fields['qody_save_post'] = 'AppendToPostSave';
		$fields['qody_after_save_post'] = 'AppendToAfterPostSave';
		
		$this->LoadActionHooks( $fields );
		
		parent::__construct();
	}
	
	function Init()
	{
		$this->register_post_type();
		
		parent::Init();
	}
	
	function AdminInit()
	{
		$this->LoadMetaboxes();
		$this->BuildColumns();
	}
	
	// run when a post is being added/edited of this type
	function WhenEditing()
    {
		if( $this->WhenEditingPost( $this->m_type_slug ) )
		{
			$this->EnqueueStyle( $this->AssetPrefix().'post_edit' );
			$this->EnqueueScript( $this->AssetPrefix().'post_edit' );
			
			// for member-only popovers
			$this->EnqueueStyle('bootstrap-popover');
			
			return true;
		}
		
		return false;
    }
	
	function Create( $title = 'Blank', $content = 'Blank' )
	{
		$fields = array();
		
		$fields['post_title'] = $title;
		$fields['post_status'] = 'publish';
		$fields['post_author'] = 1;
		$fields['post_type'] = $this->m_type_slug;
		
		$post_id = wp_insert_post( $fields );
		
		return $post_id;
	}
	
	function Get( $how_many = -1, $meta_key = '', $meta_value = '', $order_by = '', $order = '' )
	{
		$fields = array();
		$fields['numberposts'] = $how_many;
		$fields['post_type'] = $this->m_type_slug;
		
		if( $meta_key )
			$fields['meta_key'] = $meta_key;
		
		if( $meta_value )
			$fields['meta_value'] = $meta_value;
		
		if( $order_by )
			$fields['order_by'] = $order_by;
		
		if( $order )
			$fields['order'] = $order;
		
		$data = get_posts( $fields );
		
		return $data;
	}
	
	function Query( $query, $how_many = -1 )
	{
		$fields = array();
		$fields['numberposts'] = $how_many;
		$fields['post_type'] = $this->m_type_slug;
		$fields['meta_query'] = $query;
		
		$data = get_posts( $fields );
		
		return $data;
	}
	
	function StartMetaboxesClosed( $exceptions = array() )
	{
		if( !$this->m_metaboxes )
			return;
		
		$fields = array();
		
		foreach( $this->m_metaboxes as $key => $value )
		{
			if( in_array( $value->m_id, $exceptions ) )
				continue;
				
			$fields[] = $value->m_id;
		}
		
		update_user_option( $this->UserData()->ID, "closedpostboxes_".$this->m_type_slug, $fields );
	}
	
	// Run when a post on the main site of this post type is being shown
	function WhenShowing()
	{
		global $post;
		
		// if we are on any admin screen
		if( is_admin() )
			return false;
		
		// if it's not the right post type
		if( get_post_type( $post ) != $this->m_type_slug )
			return false;
		
		$this->EnqueueStyle( $this->AssetPrefix().'post_showing' );
		$this->EnqueueScript( $this->AssetPrefix().'post_showing' );
		
		return true;
	}
	
	// run when looking at the list of posts of this type
	function WhenViewingPostList()
    {
		global $pagenow, $typenow;
		
		if( $typenow != $this->m_type_slug || $pagenow != 'edit.php' )
            return false;
		
		$this->EnqueueStyle( $this->m_type_slug.'_post_list' );
		
		if( method_exists( $this, 'GetListColumns' ) )
			add_filter( "manage_".$this->m_type_slug."_posts_columns", array( $this, "GetListColumns" ) );
		
		if( method_exists( $this, 'DisplayListColumns' ) )
			add_action( 'manage_posts_custom_column', array( $this, 'DisplayListColumns' ) );
		
		return true;
    }
	
	function MakeThisSearchableByMeta()
	{
		if( method_exists( $this, 'AdminPostListRequest' ) )
			add_filter( "pre_get_posts", array( $this, "AdminPostListRequest" ) );
		
		if( method_exists( $this, 'AdminPostListMetaKey' ) )
			add_action( 'restrict_manage_posts', array( $this, 'AdminPostListMetaKey' ) );
	}
	
	function RemoveQueryFilter()
	{
		remove_filter( "pre_get_posts", array( $this, "AdminPostListRequest" ) );
	}
	
	function GetListColumns( $current_columns = '' )
	{
		if( !$this->m_list_columns )
			return $current_columns;
			
		return $this->m_list_columns;
	}
	
	function SetMassVariables( $singular, $plural, $use_pre = false )
	{
		$this->SetTypeSlug( $singular, $use_pre );
	
		$this->m_name 				= ucwords( $plural );
		$this->m_singular_name 		= ucwords( $plural );
		$this->m_add_new 			= 'Add New '.ucwords( $singular );
		$this->m_add_new_item 		= 'Add New '.ucwords( $singular );
		$this->m_edit_item 			= 'Edit '.ucwords( $singular );
		$this->m_new_item 			= 'New '.ucwords( $singular );
		$this->m_view_item 			= 'View '.ucwords( $singular );
		$this->m_search_items 		= 'Search '.ucwords( $plural );
		$this->m_not_found 			= 'No '.strtolower( $plural ).' found';
		$this->m_not_found_in_trash	= 'No '.strtolower( $plural ).' found in Trash';
	}
	
	function SetTypeSlug( $singular, $use_pre = false )
	{
		$slug = $singular;
		
		if( $use_pre )
			$slug = $this->GetPre().' '.$slug;
			
		$this->m_type_slug = strtolower( str_replace( ' ', '-', $slug ) );
	}
	
	function AppendToPostSave( $post_id )
	{
		global $post;
		
		if( $post->post_type != $this->m_type_slug )
			return;
			
		if( method_exists( $this, 'PostSaveInsert' ) )
			$this->PostSaveInsert( $post_id, $post );
	}
	
	function AppendToAfterPostSave( $post_id )
	{
		global $post;
		
		if( $post->post_type != $this->m_type_slug )
			return;
			
		if( method_exists( $this, 'AfterPostSaveInsert' ) )
			$this->AfterPostSaveInsert( $post_id, $post );
	}
	
	function BuildColumns()
	{
		
	}
	
	function register_post_type()
	{
		// hide posttype menu items if the OIN key isn't entered
		if( !$this->PassApiCheck() )
			$this->m_show_in_menu = false;
			
		$labels = array();
		$labels['name'] = _x( $this->m_name, 'post type general name' );
		$labels['singular_name'] = _x( $this->m_singular_name, 'post type singular name' );
		$labels['add_new'] = _x( $this->m_add_new, 'portfolio item' );
		$labels['add_new_item'] = __( $this->m_add_new_item );
		$labels['edit_item'] = __( $this->m_edit_item );
		$labels['new_item'] = __( $this->m_new_item );
		$labels['view_item'] = __( $this->m_view_item );
		$labels['search_items'] = __( $this->m_search_items );
		$labels['not_found'] = __( $this->m_not_found );
		$labels['not_found_in_trash'] = __( $this->m_not_found_in_trash );
		$labels['parent_item_colon'] = '';
	 
		$args = array();
		$args['labels'] = $labels;
		$args['public'] = true;
		$args['publicly_queryable'] = true;
		$args['show_ui'] = true;
		$args['query_var'] = true;
		$args['menu_icon'] = null;
		$args['rewrite'] = $this->m_rewrite;
		$args['capability_type'] = 'post';
		$args['hierarchical'] = $this->m_hierarchical;
		$args['menu_position'] = $this->m_priority;
		$args['show_in_menu'] = $this->m_show_in_menu;
		$args['supports'] = $this->m_supports;
		
		//if( $this->PassApiCheck() )
		//{
			$result = register_post_type( $this->m_type_slug , $args );
			
			//$this->ItemDebug( $result );
		//}
	}
}
?>