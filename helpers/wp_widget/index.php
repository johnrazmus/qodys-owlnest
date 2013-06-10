<?php
class qodyHelper_FrameworkWordpressWidget extends QodyHelper
{
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		parent::__construct();
	}
	
	function GetPostData( $postID )
	{
		global $wpdb;
		
		$queryString = "SELECT * FROM ".$wpdb->posts." WHERE ID = '$postID'";
		$data = $wpdb->get_row( $queryString );
		
		return $data;
	}
	
	function DisableCoreNextPrevLinks()
	{
		add_filter( 'index_rel_link', array( $this, 'disable_stuff' ) );
		add_filter( 'parent_post_rel_link', array( $this, 'disable_stuff' ) );
		add_filter( 'start_post_rel_link', array( $this, 'disable_stuff' ) );
		add_filter( 'previous_post_rel_link', array( $this, 'disable_stuff' ) );
		add_filter( 'next_post_rel_link', array( $this, 'disable_stuff' ) );
	}
	
	function disable_stuff( $data = '' )
	{
		return false;
	}
	
	function UserMetaMatch( $slug, $value, $user_id = '' )
	{
		if( !$user_id )
		{
			$user_data = $this->GetUserData();
			$user_id = $user_data['ID'];
		}

		$result = get_user_meta( $user_id, $slug, true );
		
		if( $result == $value )
			return true;
		
		return false;
	}
	
	function AddRole( $slug, $name, $capabilities = array() )
	{
		add_role( $this->GetPre().'-'.$slug, $name, $capabilities );
	}
	
	function GetRole( $slug )
	{
		return get_role( $this->GetPre().'-'.$slug );
	}
	
	function GetRoleSlug( $slug )
	{
		$role = $this->GetRole( $slug );
		
		return $role->name;
	}
	
	function RemoveRole( $slug )
	{
		remove_role( $this->GetPre().'-'.$slug );
	}
	
	function AddCapability( $role_slug, $cap_slug )
	{
		add_cap( $this->GetPre().'-'.$role_slug, $cap_slug );
	}
	
	function current_user_can( $slug )
	{
		$user_data = $this->GetUserdata();
		
		if( !$user_data )
			return false;
		//$this->ItemDebug( $slug );
		return current_user_can( $slug );
	}
	
	function GetFeaturedImage( $post_id, $size_slug = '', $format = '' )
	{
		$imageData = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size_slug );
		
		if( $format == 'url' )
			return $imageData[0];
		
		return $imageData;
	}
	
	function GetFirstPostImage( $post_id )
	{
		if( !$post_id )
			return;
			
		$post = get_post( $post_id );
		
		$first_img = '';
		
		ob_start();
		ob_end_clean();
		
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
		$first_img = $matches[1][0];
		
		return $first_img;
	}
	
	function FlushRewriteRules()
	{
		//Ensure the $wp_rewrite global is loaded
		global $wp_rewrite;
		
		if( !$wp_rewrite )
			return;
			
		//Call flush_rules() as a method of the $wp_rewrite object
		$wp_rewrite->flush_rules();
	}
	
	function wp_list_post_types( $args ) {
		$defaults = array(
			'numberposts'  => -1,
			'offset'       => 0,
			'orderby'      => 'menu_order, post_title',
			'post_type'    => 'page',
			'depth'        => 0,
			'show_date'    => '',
			'date_format'  => get_option('date_format'),
			'child_of'     => 0,
			'exclude'      => '',
				'include'      => '',
			'title_li'     => __('Pages'),
			'echo'         => 1,
			'link_before'  => '',
			'link_after'   => '',
			'exclude_tree' => '' );
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		$output = '';
		$current_page = 0;
	
		// sanitize, mostly to keep spaces out
		$r['exclude'] = preg_replace('/[^0-9,]/', '', $r['exclude']);
	
		// Allow plugins to filter an array of excluded pages (but don't put a nullstring into the array)
		$exclude_array = ( $r['exclude'] ) ? explode(',', $r['exclude']) : array();
		$r['exclude'] = implode( ',', apply_filters('wp_list_post_types_excludes', $exclude_array) );
	
		// Query pages.
		$r['hierarchical'] = 0;
		$pages = get_posts($r);
	
		if ( !empty($pages) ) {
			if ( $r['title_li'] )
				$output .= '<li class="pagenav">' . $r['title_li'] . '<ul>';
	
			global $wp_query;
			if ( ($r['post_type'] == get_query_var('post_type')) || is_attachment() )
				$current_page = $wp_query->get_queried_object_id();
			$output .= walk_page_tree($pages, $r['depth'], $current_page, $r);
	
			if ( $r['title_li'] )
				$output .= '</ul></li>';
		}
	
		$output = apply_filters('wp_list_pages', $output, $r);
	
		if ( $r['echo'] )
			echo $output;
		else
			return $output;
	}
	
	function get_excerpt( $text, $excerpt = '' )
	{
		if ($excerpt) return $excerpt;
	
		$text = strip_shortcodes( $text );
	
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', 55);
		$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
		$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
		if ( count($words) > $excerpt_length ) {
				array_pop($words);
				$text = implode(' ', $words);
				$text = $text . $excerpt_more;
		} else {
				$text = implode(' ', $words);
		}
	
		return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
	}
	
	function GetAccountsByType( $account_types = '', $exclude = array(), $require_emails = false )
	{
		global $wpdb;
		
		$account_types_query = '';
		
		if( isset( $exclude ) && count( $exclude ) > 0 )
		{
			if( !$where_included )
			{
				$account_types_query .= ' WHERE ';
				$where_included = true;
			}
			else
			{
				$account_types_query .= ' AND ';
			}
			
			$account_types_query .= ' ID NOT IN('.implode( ',', $exclude ).')';
		}
		
		if( $require_emails )
		{
			if( !$where_included )
			{
				$account_types_query .= ' WHERE ';
				$where_included = true;
			}
			else
			{
				$account_types_query .= ' AND ';
			}
			
			$account_types_query .= " user_email != ''";
		}
		
		$data = $wpdb->get_results( "SELECT * FROM $wpdb->users".$account_types_query );
		
		return $data;
	}
	
	function GetCommentsByType( $comment_types = '', $exclude = array(), $require_emails = false )
	{
		global $wpdb;
		
		$comment_types_query = '';
		
		if( isset( $comment_types ) )
		{
			if( !is_array( $comment_types ) )
				$comment_types = array( $comment_types );
				
			$comment_types_query = ' WHERE ';
			$where_included = true;
			
			$iter = 0;
			
			$comment_types_query .= '(';
			
			foreach( $comment_types as $key => $value )
			{
				$iter++;
				
				if( $iter > 1 )
					$comment_types_query .= ' OR ';
					
				$comment_types_query .= " comment_approved = '".$value."'";
			}
			
			$comment_types_query .= ')';
		}
		
		if( isset( $exclude ) && count( $exclude ) > 0 )
		{
			if( !$where_included )
			{
				$comment_types_query .= ' WHERE ';
				$where_included = true;
			}
			else
			{
				$comment_types_query .= ' AND ';
			}
			
			$comment_types_query .= " comment_author_email NOT IN('".implode( "','", $exclude )."')";
		}
		
		if( $require_emails )
		{
			if( !$where_included )
			{
				$comment_types_query .= ' WHERE ';
				$where_included = true;
			}
			else
			{
				$comment_types_query .= ' AND ';
			}
			
			$comment_types_query .= " comment_author_email != ''";
		}
		
		$data = $wpdb->get_results( "SELECT * FROM $wpdb->comments".$comment_types_query );
		
		return $data;
	}
	
	function GetAllUsers( $sort_by = 'display_name' )
	{
		global $wpdb;
		
		$data = $wpdb->get_results("SELECT * FROM $wpdb->users ORDER BY $sort_by ASC");
		
		return $this->Helper('tools')->ObjectToArray( $data );
	}
	
	function ManualWidgetShow( $slug, $options = array() )
	{
		if( $options['no_title'] == 1 )
		{
			$bw = '';
			$aw = '';
		}
		
		switch( $slug )
		{
			case 'recent_posts':
				$postsWidget = new WP_Widget_Recent_Posts();
				
				$args = array();
				$args['name'] = 'Footer 2';
				$args['id'] = 'sidebar-4';
				$args['description'] = '';
				$args['before_widget'] = '<div class="menu">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3>';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'recent-posts-3';
				$args['widget_name'] = 'Recent Posts';
				
				$instance = array();
				$instance['title'] = 'Recent Posts';
				$instance['number'] = 7;
			
				$postsWidget->widget( $args, $instance );
				break;
			case 'pages':
				$pageWidget = new WP_Widget_Pages();
				
				$args = array();
				$args['name'] = 'Sidebar';
				$args['id'] = 'sidebar-1';
				$args['description'] = '';
				$args['before_widget'] = '<div class="menu">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3>';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'pages-3';
				$args['widget_name'] = 'Pages';
				
				$instance = array();
				$instance['title'] = 'Pages';
				$instance['sortby'] = 'post_title';
				$instance['exclude'] = '';
				
				$pageWidget->widget( $args, $instance );
				break;
			
			case 'text':
				$textWidget = new WP_Widget_Text();
				
				$args = array();
				$args['name'] = 'Footer 2';
				$args['id'] = 'sidebar-4';
				$args['description'] = '';
				$args['before_widget'] = '<div class="text">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3>';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'text-1';
				$args['widget_name'] = 'Text';
				
				$siteUrl = str_replace( 'http://', '', get_bloginfo('url') );
				
				$maxLength = 45;
				if( strlen($siteUrl) > $maxLength )
					$siteUrl = substr( $siteUrl, 0, $maxLength ).'...';
					
				$instance = array();
				$instance['title'] = 'We sell through Amazon!';
				$instance['text'] = "<p>".$siteUrl." is a participant in the Amazon Services LLC Associates Program, 
				an affiliate advertising program designed to provide a means for sites to earn advertising fees by 
				advertising and linking to amazon.com.</p>";
				
				$textWidget->widget( $args, $instance );
				
				break;
				
			case 'links':
				$linksWidget = new WP_Widget_Links();
				
				$args = array();
				$args['name'] = 'Footer 2';
				$args['id'] = 'sidebar-4';
				$args['description'] = '';
				$args['before_widget'] = '<div class="menu">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3>';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'links-3';
				$args['widget_name'] = 'Links';
				
				$instance = array();
				$instance['images'] = 0;
				$instance['name'] = 1;
				$instance['description'] = 0;
				$instance['rating'] = 0;
				$instance['category'] = 0;
			
				$linksWidget->widget( $args, $instance );
				break;
			case 'brand_list':
				$productWidget = new Qody_Product_Widget();
				
				$args = array();
				$args['name'] = 'Footer 2';
				$args['id'] = 'sidebar-4';
				$args['description'] = '';
				$args['before_widget'] = '<div class="menu">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3>';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'everniche-products-7';
				$args['widget_name'] = 'EverNiche Products';
				
				$instance = array();
				$instance['title'] = 'Bestselling Brands';
				$instance['name'] = '';
				$instance['product_size'] = 'large';
				$instance['type'] = 'best_sellers_brand_list';
				$instance['product_count'] = 6;
				$instance['display_direction'] = 'verticle';
				
				$productWidget->widget( $args, $instance );
				break;
			case 'brand_nav':
				$widget = new Qody_Product_Widget();
	
				$args = array();
				$args['name'] = 'Navigation Bar';
				$args['id'] = 'sidebar-1';
				$args['description'] = '';
				$args['before_widget'] = '<div id="categories-bg">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '';		
				$args['after_title'] = ''; 		
				$args['widget_id'] = 'everniche-products-3';
				$args['widget_name'] = 'EverNiche Products';
				
				$instance = array();
				$instance['title'] = '';
				$instance['name'] = '';
				$instance['product_size'] = 'large';
				$instance['type'] = 'best_sellers_brand_list';
				$instance['product_count'] = 6;
				$instance['display_direction'] = 'verticle';
				
				$widget->widget( $args, $instance );
				break;
			case 'long_product_list':
				$widget = new Qody_Product_Widget();
		
				$args = array();
				$args['name'] = 'Sidebar';
				$args['id'] = 'sidebar-2';
				$args['description'] = '';
				$args['before_widget'] = '<div class="products">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3 class="widgettitle">';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'everniche-products-4';
				$args['widget_name'] = 'EverNiche Products';
				
				$instance = array();
				$instance['title'] = 'Recommended Items';
				$instance['name'] = '';
				$instance['product_size'] = 'large';
				$instance['type'] = 'random';
				$instance['product_count'] = 10;
				$instance['display_direction'] = 'verticle';
				
				$widget->widget( $args, $instance );
				break;
			case 'horizontal_products_medium':
				$widget = new Qody_Product_Widget();
	
				$args = array();
				$args['name'] = 'Footer 1';
				$args['id'] = 'sidebar-3';
				$args['description'] = '';
				$args['before_widget'] = '<div class="post box">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3 class="post-title">';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'everniche-products-5';
				$args['widget_name'] = 'EverNiche Products';
				
				$instance = array();
				$instance['title'] = 'Recommended Items';
				$instance['name'] = '';
				$instance['type'] = 'random';
				$instance['product_size'] = 'large';
				$instance['product_count'] = 8;
				$instance['display_direction'] = 'horizontal';
				
				$widget->widget( $args, $instance );
				break;
			case 'horizontal_products_small':
				$widget = new Qody_Product_Widget();
	
				$args = array();
				$args['name'] = 'Footer 1';
				$args['id'] = 'sidebar-3';
				$args['description'] = '';
				$args['before_widget'] = '<div class="post box">';
				$args['after_widget'] = '</div>'; 
				$args['before_title'] = '<h3 class="post-title">';		
				$args['after_title'] = '</h3>'; 		
				$args['widget_id'] = 'everniche-products-6';
				$args['widget_name'] = 'EverNiche Products';
				
				$instance = array();
				$instance['title'] = 'Other Related Items';
				$instance['name'] = '';
				$instance['product_size'] = 'large';
				$instance['type'] = 'random';
				$instance['product_count'] = 4;
				$instance['display_direction'] = 'horizontal';
				
				$widget->widget( $args, $instance );
				break;
		}
	}
	
	function GetPostContent( $size = 'full', $stripImages = false )
	{
		global $post;
		
		$content = $post->post_content;
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		
		if( $stripImages || $post->post_category == 4 )
			$content = preg_replace("/<img[^>]+\>/i", "", $content);
		
		if( $size != 'full' )
		{
			$bits = explode( '<!--more-->', $content );
			$content = str_replace( '<p></p>', '', $bits[0] );
			$content = $this->SafeSubstr( $content, $size );
		}
		
		
		return $content;
	}
	
	function GetUsersByRoles( $roles )
	{  
		global $wpdb;  
		if ( ! is_array( $roles ) ) {  
			$roles = explode( ",", $roles );  
			array_walk( $roles, 'trim' );  
		}  
		$sql = ' 
			SELECT  *, display_name 
			FROM        ' . $wpdb->users . ' INNER JOIN ' . $wpdb->usermeta . ' 
			ON      ' . $wpdb->users . '.ID              =       ' . $wpdb->usermeta . '.user_id 
			WHERE   ' . $wpdb->usermeta . '.meta_key     =       \'' . $wpdb->prefix . 'capabilities\' 
			AND     ( 
		';  
		$i = 1;  
		foreach ( $roles as $role ) {  
			$sql .= ' ' . $wpdb->usermeta . '.meta_value LIKE    \'%"' . $role . '"%\' ';  
			if ( $i < count( $roles ) ) $sql .= ' OR ';  
			$i++;  
		}  
		$sql .= ' ) ';  
		$sql .= ' ORDER BY display_name ';  
	
		$userIDs = $wpdb->get_results( $sql );  
		
		return $userIDs;  
	}  
}
?>