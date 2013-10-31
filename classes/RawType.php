<?php
class QodyRawType extends QodyOwnable
{
	var $m_table_slug = '';
	var $m_table_design = '';
	
	function __construct()
	{
		$this->m_table_design = $this->GetTableDesign();
		
		parent::__construct();
	}
	
	function MakeTableIfItDoesntExist()
	{
		global $rawtype_table_checks;
		
		if( !isset( $rawtype_table_checks[ $this->m_table_slug ] ) )
		{
			if( !$this->Helper('db')->TableExists( $this->m_table_slug, $this ) )
			{
				$this->Log( "rawtypes: creating ".$this->m_table_slug." table because it doesn't exist", 'success' );
				
				$this->CreateTable();
			}
			
			$rawtype_table_checks[ $this->m_table_slug ] = true;
		}
	}
	
	// mapped out in each child
	function GetTableDesign()
	{
		// EXAMPLE
		
		/*
		$fields = array();
		$fields[] = "`ID` bigint(20) NOT NULL AUTO_INCREMENT";
		$fields[] = "`post_date` int(20) NOT NULL";
		$fields[] = "`post_content` longtext NOT NULL";
		$fields[] = "`post_title` text NOT NULL";
		$fields[] = "`post_excerpt` text NOT NULL";
		$fields[] = "`post_status` varchar(20) NOT NULL DEFAULT 'publish'";
		$fields[] = "`post_parent` bigint(20) unsigned NOT NULL";
		$fields[] = "`menu_order` int(11) NOT NULL";
		$fields[] = "PRIMARY KEY (`id`)";
		
		return $fields;*/
	}
	
	function CreateTable()
	{
		// array of columns
		$table_design = $this->m_table_design;
		
		$append = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		
		$this->Helper('db')->CreateTable( $this->m_table_slug, $table_design, $append, $this );
	}
	
	// *****************************************************************
	// custom utility functions
	// *****************************************************************
	function Query( $query = '' )
	{
		$this->MakeTableIfItDoesntExist();
		
		if( !$query )
			$query = "ORDER BY date DESC";
		
		$data = $this->Helper('db')->Select( $this->m_table_slug, $query, $this, '', OBJECT );
		
		return $data;
	}
	
	function PostCount()
	{
		global $wpdb;
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_table_slug, $this );
		
		$data = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table" );
		
		return $data;
	}
	
	function GetPosts( $q = array() )
	{
		global $wpdb;
		
		// ************************************************************************
		// Setup the table we'll be working with
		// ************************************************************************
		$this->MakeTableIfItDoesntExist();
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_table_slug, $this );
		
		// ************************************************************************
		// Process the query to get some posts
		// ************************************************************************
		
		// First let's clear some variables
		$distinct = '';
		$where = '';
		$limits = '';
		$join = '';
		$groupby = '';
		$fields = '';
		$post_status_join = false;
		$page = 1;
		
		// *********************
		// Paging
		// *********************
		$paging = isset($q['page']) ? true : false;
		$page = isset($q['page']) ? $q['page'] : 1;
		$per_page = isset($q['per_page']) ? $q['per_page'] : 10;
		
		if( $paging )
		{
			if( empty($q['offset']) )
			{
				$pgstrt = ($page - 1) * $q['per_page'] . ', ';
			} 
			else
			{ // we're ignoring $page and using 'offset'
				$q['offset'] = absint($q['offset']);
				$pgstrt = $q['offset'] . ', ';
			}
			
			$limits = 'LIMIT ' . $pgstrt . $q['per_page'];
		}
		
		// *********************
		// Order By
		// *********************
		if ( empty($q['order']) || ((strtoupper($q['order']) != 'ASC') && (strtoupper($q['order']) != 'DESC')) )
			$q['order'] = 'DESC';

		if( empty($q['orderby']) )
		{
			$orderby = "$post_table.date ".$q['order'];
		}
		else if( 'none' == $q['orderby'] )
		{
			$orderby = '';
		} 
		else 
		{
			// Used to filter values
			$allowed_keys = array('name', 'author', 'date', 'title', 'modified', 'menu_order', 'parent', 'ID', 'rand', 'comment_count');
			if( !empty($q['meta_key']) )
			{
				$allowed_keys[] = $q['meta_key'];
				$allowed_keys[] = 'meta_value';
				$allowed_keys[] = 'meta_value_num';
			}
			
			$q['orderby'] = urldecode($q['orderby']);
			$q['orderby'] = addslashes_gpc($q['orderby']);

			$orderby_array = array();
			foreach ( explode( ' ', $q['orderby'] ) as $i => $orderby ) {
				// Only allow certain values for safety
				if ( ! in_array($orderby, $allowed_keys) )
					continue;

				switch ( $orderby )
				{
					case 'menu_order':
						$orderby = "$post_table.menu_order";
						break;
					case 'ID':
					case 'id':
						$orderby = "$post_table.id";
						break;
					case 'rand':
						$orderby = 'RAND()';
						break;
					case $q['meta_key']:
					case 'meta_value':
						$orderby = "$meta_table.meta_value";
						break;
					case 'meta_value_num':
						$orderby = "$meta_table.meta_value+0";
						break;
					case 'comment_count':
						$orderby = "$post_table.comment_count";
						break;
					default:
						$orderby = "$post_table.post_" . $orderby;
				}

				$orderby_array[] = $orderby;
			}
			
			$orderby = implode( ',', $orderby_array );

			if ( empty( $orderby ) )
				$orderby = "$post_table.date ".$q['order'];
			else
				$orderby .= " {$q['order']}";
		}
		
		$pieces = array( 'where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits' );
		
		$clauses = compact( $pieces );
		foreach ( $pieces as $piece )
			$$piece = isset( $clauses[ $piece ] ) ? $clauses[ $piece ] : '';
		
		if ( ! empty($groupby) )
			$groupby = 'GROUP BY ' . $groupby;
		if ( !empty( $orderby ) )
			$orderby = 'ORDER BY ' . $orderby;
		if ( !empty( $join ) )
			$join = 'INNER JOIN ' . $join;
		
		$found_rows = '';
		//if ( !$q['no_found_rows'] && !empty($limits) )
		//	$found_rows = 'SQL_CALC_FOUND_ROWS';
		
		$query = "SELECT $found_rows $distinct $post_table.ID FROM $post_table $join WHERE 1=1 $where $groupby $orderby $limits";
		//if( current_user_can( 'administrator' ) ) ItemDebug( $query );
		$ids = $wpdb->get_col( $query );
		
		if( !$ids )
			return;
			
		$this->_prime_post_caches( $ids );
		
		$data = array_map( array( $this, 'get_post' ), $ids );
		
		return $data;
	}
	
	function Get( $how_many = -1, $meta_key = '', $meta_value = '', $order_by = '', $order = '', $fields_to_get = 'ID,post_title,post_date' )
	{
		$mini_query = '';
		
		$this->MakeTableIfItDoesntExist();
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_table_slug, $this );
		
		$bits = explode( ',', $fields_to_get );
		foreach( $bits as $key => $value )
		{
			$mini_query .= 'p.'.trim($value);
			
			if( $key < count( $bits ) - 1 )
				$mini_query .= ', ';
		}
		
		$query = "SELECT DISTINCT $mini_query FROM ".$post_table." p";
		
		if( $order_by && $order )
			$query .= " ORDER BY $order_by $order";
		else
			$query .= " ORDER BY p.post_date ASC, p.ID ASC";
		
		if( $how_many != -1 && $how_many > 0 )
			$query .= " LIMIT 0, ".$how_many;
		
		$data = $this->Helper('db')->Query( $query, OBJECT );
		
		return $data;
	}
	
	function DeletePosts( $post_ids )
	{
		if( !$post_ids )
			return false;

		if( !is_array( $post_ids ) )
			$post_ids = array( $post_ids );
		
		$this->wp_delete_post( $post_ids );
		
		return true;
	}
	
	function SavePost( $post_data, $post_id = '' )
	{
		$post_id = $post_id ? $post_id : $this->wp_insert_post();
		
		//do_action( 'qody_save_post', $post_id, $post );
		
		foreach( $post_data as $key => $value )
		{
			if( $key == 'post_id' || $key == 'success_url' )
					continue;
					
			$fields = array();
			$fields['id'] = $post_id;
			$fields[ $key ] = $value;
			
			$resulting_post_id = $this->wp_insert_post( $fields );
		}
		
		return $post_id ? $post_id : $resulting_post_id;
	}
	
	function Update( $post_data, $post_id = '' )
	{
		return $this->SavePost( $post_data, $post_id = '' );
	}
	
	function Insert( $fields = array() )
	{
		return $this->wp_insert_post( $fields );
	}
	
	// *****************************************************************
	// wordpress function look-a-likes
	// *****************************************************************
	function wp_insert_post( $fields = array() )
	{
		$this->MakeTableIfItDoesntExist();
		
		// if creating a new post with missing data, fill it
		if( !$fields['id'] )
		{
			if( !$fields['date'] )
				$fields['date'] = $this->time();
			
			$this->Helper('db')->InsertToDatabase( $fields, $this->m_table_slug, $this );
			
			$table_name = $this->Helper('db')->FixTableName( $this->m_table_slug, $this );
			
			$query = mysql_query( "SELECT id FROM ".$table_name." ORDER BY id DESC LIMIT 0,1" );
			$data = mysql_fetch_array( $query );
			
			// get last created item to return as post_id
			//$data = $this->Helper('db')->Select( $this->m_table_slug, "ORDER BY ID DESC", $this, '', ARRAY_A, 'ID' );
			$post_id = $data['id'];
		}
		else
		{
			$this->Helper('db')->UpdateDatabase( $fields, $this->m_table_slug, $fields['id'], 'id', $this );
			$post_id = $fields['id'];
		}
		
		return $post_id;
	}
	
	function wp_delete_post( $post_id )
	{
		$this->MakeTableIfItDoesntExist();
		
		if( is_array( $post_id ) )
		{
			foreach( $post_id as $key => $value )
			{
				$this->wp_delete_post( $value );
			}
		}
		else
		{		
			$this->Helper('db')->DeleteFromDatabase( $this->m_table_slug, 'id', $post_id, $this );
		}
		
		$this->clean_post_cache( $post_id );
	}
	
	function get_post( $post_id )
	{
		$this->MakeTableIfItDoesntExist();
		
		if( !$data = $this->wp_cache_get( $post_id ) )
		{
			$query = "id = $post_id";
			$data = $this->Helper('db')->Select( $this->m_table_slug, $query, $this, '', OBJECT );
			
			if( !$data )
				return;
			
			$data = $data[0];
			
			$this->wp_cache_add( $data->id, $data );
		}
		
		return $data;
	}
	
	function get_posts( $fields )
	{
		// TODO
	}
	
	
	// ****************************************************************
	// Cacheing functions to make meta retreivals faster
	// ****************************************************************
	
	/**
	 * Call major cache updating functions for list of Post objects.
	 *
	 * @package WordPress
	 * @subpackage Cache
	 * @since 1.5.0
	 *
	 * @uses $wpdb
	 * @uses update_post_cache()
	 * @uses update_object_term_cache()
	 * @uses update_postmeta_cache()
	 *
	 * @param array $posts Array of Post objects
	 * @param string $post_type The post type of the posts in $posts. Default is 'post'.
	 * @param bool $update_term_cache Whether to update the term cache. Default is true.
	 * @param bool $update_meta_cache Whether to update the meta cache. Default is true.
	 */
	function update_post_caches(&$posts) {
		// No point in doing all this work if we didn't match any posts.
		if ( !$posts )
			return;
	
		$this->update_post_cache($posts);
	
		$post_ids = array();
		foreach ( $posts as $post )
			$post_ids[] = $post->ID;

	}
	
	/**
	 * Updates posts in cache.
	 *
	 * @package WordPress
	 * @subpackage Cache
	 * @since 1.5.1
	 *
	 * @param array $posts Array of post objects
	 */
	function update_post_cache( &$posts ) {
		if ( ! $posts )
			return;
	
		foreach ( $posts as $post )
			$this->wp_cache_add( $post->ID, $post );
	}
	
	/**
	 * Adds any posts from the given ids to the cache that do not already exist in cache
	 *
	 * @since 3.4.0
	 *
	 * @access private
	 *
	 * @param array $post_ids ID list
	 * @param bool $update_term_cache Whether to update the term cache. Default is true.
	 * @param bool $update_meta_cache Whether to update the meta cache. Default is true.
	 */
	function _prime_post_caches( $ids, $update_term_cache = true ) {
		global $wpdb;
	
		$table = $this->Helper('db')->FixTableName( $this->m_table_slug, $this );
	
		$non_cached_ids = _get_non_cached_ids( $ids, $this->m_table_slug );
		
		//ItemDebug( $non_cached_ids );
		if ( !empty( $non_cached_ids ) ) {
			$fresh_posts = $wpdb->get_results( sprintf( "SELECT $table.* FROM $table WHERE id IN (%s)", join( ",", $non_cached_ids ) ) );
	
			$this->update_post_caches( $fresh_posts );
		}
	}
	
	/**
	 * Will clean the post in the cache.
	 *
	 * Cleaning means delete from the cache of the post. Will call to clean the term
	 * object cache associated with the post ID.
	 *
	 * clean_post_cache() will call itself recursively for each child post.
	 *
	 * This function not run if $_wp_suspend_cache_invalidation is not empty. See
	 * wp_suspend_cache_invalidation().
	 *
	 * @package WordPress
	 * @subpackage Cache
	 * @since 2.0.0
	 *
	 * @uses do_action() Calls 'clean_post_cache' on $id before adding children (if any).
	 *
	 * @param object|int $post The post object or ID to remove from the cache
	 */
	function clean_post_cache( $post_id ) {
		global $wpdb;
	
		wp_cache_delete( $post_id, $this->m_table_slug );
	// continue here;
		//clean_object_term_cache( $post_id, $post->post_type );
	
		wp_cache_delete( 'wp_get_archives', 'general' );
	
		do_action( 'clean_post_cache', $post_id );
	
		/*if ( 'page' == $post->post_type ) {
			wp_cache_delete( 'all_page_ids', 'posts' );
			wp_cache_delete( 'get_pages', 'posts' );
			do_action( 'clean_page_cache', $post_id );
		}*/
	
		if ( is_multisite() )
			wp_cache_delete( $wpdb->blogid . '-' . $post_id, 'global-posts' );
	}
	
	function wp_cache_get( $post_id )
	{
		$data = wp_cache_get($post_id, $this->m_table_slug);
		
		return $data;
	}
	
	function wp_cache_add( $post_id, $data )
	{
		wp_cache_set( $post_id, $data, $this->m_table_slug );
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}







?>