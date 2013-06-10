<?php
class QodyDataType extends QodyOwnable
{
	var $m_table_slug = '';
	var $m_post_table = '';
	var $m_meta_table = '';
	
	function __construct()
	{
		$this->m_post_table = $this->m_table_slug.'p';
		$this->m_meta_table = $this->m_table_slug.'pm';
		
		parent::__construct();
	}
	
	function MakeTableIfItDoesntExist()
	{
		global $datatype_table_checks;
		
		if( !isset( $datatype_table_checks[ $this->m_post_table ] ) )
		{
			if( !$this->Helper('db')->TableExists( $this->m_post_table, $this ) )
			{
				$this->Log( "datatypes: creating ".$this->m_post_table." table because it doesn't exist", 'success' );
				
				$this->CreatePostTable();
			}
			
			$datatype_table_checks[ $this->m_post_table ] = true;
		}
		
		if( !isset( $datatype_table_checks[ $this->m_meta_table ] ) )
		{
			if( !$this->Helper('db')->TableExists( $this->m_meta_table, $this ) )
			{
				$this->Log( "datatypes: creating ".$this->m_meta_table." table because it doesn't exist", 'success' );
				
				$this->CreatePostMetaTable();
			}
			
			$datatype_table_checks[ $this->m_meta_table ] = true;
		}
	}
	
	function CreatePostTable()
	{
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
		
		$append = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		
		$this->Helper('db')->CreateTable( $this->m_post_table, $fields, $append, $this );
	}
	
	function CreatePostMetaTable()
	{
		$fields = array();
		$fields[] = "`meta_id` bigint(20) NOT NULL AUTO_INCREMENT";
		$fields[] = "`post_id` bigint(20) NOT NULL";
		$fields[] = "`meta_key` varchar(255) DEFAULT NULL";
		$fields[] = "`meta_value` longtext";
		$fields[] = "PRIMARY KEY (`meta_id`)";
		
		$append = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		
		$this->Helper('db')->CreateTable( $this->m_meta_table, $fields, $append, $this );
	}
	
	function ConvertFromPostType( $post_type, $delete_old = true )
	{
		global $qodys_framework;
		
		$still_going = false;
		
		$number_at_a_time = 125;
		
		$fields = array();
		$fields['post_type'] = $post_type;
		$fields['numberposts'] = $number_at_a_time; // can't do too many at a time
		$fields['post_status'] = 'all';
		
		$data = get_posts( $fields );

		if( !$data )
			return $still_going;
		
		$this->MakeTableIfItDoesntExist();
		
		if( count( $data ) == $number_at_a_time )
			$still_going = true;
			
		foreach( $data as $key => $value )
		{
			$custom = $qodys_framework->get_post_custom( $value->ID );
			
			$fields = array();
			$fields['post_title'] 	= $value->post_title;
			$fields['post_date'] 	= strtotime( $value->post_date );
			$fields['post_content'] = $value->post_content;
			$fields['post_excerpt'] = $value->post_excerpt;
			$fields['post_status'] 	= $value->post_status;
			$fields['post_parent'] 	= $value->post_parent;
			$fields['menu_order'] 	= $value->menu_order;
			
			$post_id = $this->wp_insert_post( $fields );
			
			$custom['old_id'] = $value->ID;
			
			if( $custom )
			{
				foreach( $custom as $key2 => $value2 )
				{
					$this->update_datatype_meta( $post_id, $key2, $value2 );
				}	
			}
			
			if( $delete_old )
				wp_delete_post( $value->ID, true );
		}
		
		if( $still_going )
		{
			echo "Converting post types to data types; please refresh to continue the process (".count( $data )." completed, more to go)";
			exit;
		}
		
		return $still_going;
	}
	
	
	
	// *****************************************************************
	// custom utility functions
	// *****************************************************************
	function Query( $query = '' )
	{
		$this->MakeTableIfItDoesntExist();
		
		if( !$query )
			$query = "ORDER BY post_date DESC";
		
		$data = $this->Helper('db')->Select( $this->m_post_table, $query, $this, '', OBJECT );
		
		return $data;
	}
	
	function GetWithMeta()
	{
		$post_table = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
		$meta_table = $this->Helper('db')->FixTableName( $this->m_meta_table, $this );
		
		$this->Helper('db')->Query('SET SESSION group_concat_max_len = 10000'); // necessary to get more than 1024 characters in the GROUP_CONCAT columns below
		
		$query = "
			SELECT
				p.*, 
				GROUP_CONCAT(pm.meta_key ORDER BY pm.meta_key DESC SEPARATOR '||') as meta_keys, 
				GROUP_CONCAT(pm.meta_value ORDER BY pm.meta_key DESC SEPARATOR '||') as meta_values 
			FROM 
				$post_table p 
			LEFT JOIN 
				$meta_table pm on pm.post_id = p.ID 
			GROUP BY 
				p.ID";
		
		$data = $this->Helper('db')->Query( $query, OBJECT );
		
		return $data;
	}
	
	function PostCount()
	{
		global $wpdb;
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
		
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
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
		$meta_table = $this->Helper('db')->FixTableName( $this->m_meta_table, $this );
		
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
			$orderby = "$post_table.post_date ".$q['order'];
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
						$orderby = "$post_table.ID";
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
				$orderby = "$post_table.post_date ".$q['order'];
			else
				$orderby .= " {$q['order']}";
		}
		
		// ************************
		// Meta Values
		// ************************
		if ( !empty( $q['meta_key'] ) && (!empty( $q['meta_value'] ) || $q['orderby'] == 'meta_value') )
		{
			$join = " $meta_table ON ($post_table.ID = $meta_table.post_id)";
			$where .= " AND $meta_table.meta_key = '".$q['meta_key']."' ";
			
			if( !empty( $q['meta_value'] ) )
				$where .= " AND $meta_table.meta_value = '".$q['meta_value']."'";
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
	
	function get_by_title( $title )
	{
		global $wpdb;
		
		$this->MakeTableIfItDoesntExist();
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
		
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $post_table WHERE post_title = %s", $title ) );
		
		if ( $id )
			return $this->get_post( $id );
		
		return null;
	}
	
	function Get( $how_many = -1, $meta_key = '', $meta_value = '', $order_by = '', $order = '', $fields_to_get = 'ID,post_title,post_date' )
	{
		$mini_query = '';
		
		$this->MakeTableIfItDoesntExist();
		
		$post_table = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
		$meta_table = $this->Helper('db')->FixTableName( $this->m_meta_table, $this );
		
		$bits = explode( ',', $fields_to_get );
		foreach( $bits as $key => $value )
		{
			$mini_query .= 'p.'.trim($value);
			
			if( $key < count( $bits ) - 1 )
				$mini_query .= ', ';
		}
		
		$query = "SELECT DISTINCT $mini_query FROM ".$post_table." p";
		
		if( $meta_key && $meta_value )
			$query .= ", ".$meta_table." m";
		
		if( $meta_key && $meta_value )
			$query .= " WHERE p.ID = m.post_id AND m.meta_key = '$meta_key' AND m.meta_value = '$meta_value'";
			
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
		
		if( $post_data['content'] )
			$post_data['post_content'] = $post_data['content'];
			
		foreach( $post_data as $key => $value )
		{
			if( strpos( $key, 'field_' ) === false )
			{
				if( $key == 'post_id' || $key == 'success_url' )
					continue;
					
				$fields = array();
				$fields['ID'] = $post_id;
				$fields[ $key ] = $value;
				
				$resulting_post_id = $this->wp_insert_post( $fields );
				
				continue;
			}
				
			$key = str_replace( 'field_', '', $key );
			
			$this->update_datatype_meta( $post_id, $key, $value );
		}
		
		return $post_id ? $post_id : $resulting_post_id;
	}
	
	// *****************************************************************
	// wordpress function look-a-likes
	// *****************************************************************
	function wp_insert_post( $fields = array() )
	{
		$this->MakeTableIfItDoesntExist();
		
		// if creating a new post with missing data, fill it
		if( !$fields['ID'] )
		{
			if( !$fields['post_date'] )
				$fields['post_date'] = $this->time();
			
			if( !$fields['post_title'] )
				$fields['post_title'] = 'empty';
			
			if( !$fields['post_content'] )
				$fields['post_content'] = 'empty';
			
			$this->Helper('db')->InsertToDatabase( $fields, $this->m_post_table, $this );
			
			$table_name = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
			
			$query = mysql_query( "SELECT ID FROM ".$table_name." ORDER BY ID DESC LIMIT 0,1" );
			$data = mysql_fetch_array( $query );
			
			// get last created item to return as post_id
			//$data = $this->Helper('db')->Select( $this->m_post_table, "ORDER BY ID DESC", $this, '', ARRAY_A, 'ID' );
			$post_id = $data['ID'];
		}
		else
		{
			$this->Helper('db')->UpdateDatabase( $fields, $this->m_post_table, $fields['ID'], 'ID', $this );
			$post_id = $fields['ID'];
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
			$this->Helper('db')->DeleteFromDatabase( $this->m_post_table, 'ID', $post_id, $this );
			$this->Helper('db')->DeleteFromDatabase( $this->m_meta_table, 'post_id', $post_id, $this );
		}
		
		$this->clean_post_cache( $post_id );
	}
	
	function get_post( $post_id )
	{
		$this->MakeTableIfItDoesntExist();
		
		if( !$data = $this->wp_cache_get( $post_id ) )
		{
			$query = "ID = $post_id";
			$data = $this->Helper('db')->Select( $this->m_post_table, $query, $this, '', OBJECT );
			
			if( !$data )
				return;
			
			$data = $data[0];
			$data->post_title = $this->FW()->Helper('tools')->Clean( $data->post_title );
			$data->post_content = $this->FW()->Helper('tools')->Clean( $data->post_content );
			
			$this->wp_cache_add( $data->ID, $data );
		}
		
		return $data;
	}
	
	function get_posts( $fields )
	{
		// TODO
	}
	
	function get_datatype_custom( $post_id, $data_type = '' )
	{
		if( $data_type )
		{
			return $this->DataType( $data_type )->get_datatype_custom( $post_id );
		}
		
		$this->MakeTableIfItDoesntExist();
		
		$data = wp_cache_get( $post_id, $this->m_meta_table );
		
		if( !$data )
		{
			$data = $this->update_meta_cache( array( $post_id ) );
			$data = $data[$post_id];
		}
		
		return $data;
		
		/*if( current_user_can( 'administrator' ) )
		{
			
		}
		else
		{
		
			$query = "post_id = $post_id";
			
			$tmp_data = $this->Helper('db')->Select( $this->m_meta_table, $query, $this );
			
			if( !$tmp_data )
				return;
			
			$data = array();
			
			foreach( $tmp_data as $key => $value )
			{
				$data[ $value['meta_key'] ] = maybe_unserialize( $value['meta_value'] );
				
				if( !is_array( $data[ $value['meta_key'] ] ) )
					$data[ $value['meta_key'] ] = $this->Helper( 'tools' )->Clean( $data[ $value['meta_key'] ] );
			}
		}
		
		return $data;*/
	}
	
	function get_all_meta()
	{
		$data = $this->Helper('db')->Select( $this->m_meta_table, 'ORDER BY post_id ASC', $this );
		
		return $data;
	}
	
	function get_post_meta( $post_id, $key = '', $single = false )
	{
		$this->MakeTableIfItDoesntExist();
		
		$query = "post_id = $post_id AND meta_key = '$key'";
		
		$data = $this->Helper('db')->Select( $this->m_meta_table, $query, $this );
		
		return $data;
	}
	
	function get_the_title( $post_id )
	{
		$data = $this->get_post( $post_id );
		
		return $data->post_title;
	}
	
	function update_datatype_meta( $post_id, $meta_key, $meta_value, $data_type = '' )
	{
		if( $data_type )
		{
			return $this->DataType( $data_type )->update_datatype_meta( $post_id, $meta_key, $meta_value );
		}
		
		$fields = array();
		$fields['post_id'] = $post_id;
		$fields['meta_key'] = $meta_key;
		$fields['meta_value'] = is_array( $meta_value ) || is_object( $meta_value ) ? serialize( $meta_value ) : $meta_value;
		
		// first check to see if we need to create a new meta
		$data = $this->Helper('db')->Select( $this->m_meta_table, "post_id = '$post_id' AND meta_key = '$meta_key'", $this );
		
		if( !$data )
		{
			$this->Helper('db')->InsertToDatabase( $fields, $this->m_meta_table, $this );
		}
		else
		{
			$this->Helper('db')->UpdateDatabase( $fields, $this->m_meta_table, $data[0]['meta_id'], 'meta_id', $this );
		}
		
		$this->update_meta_cache( $post_id, $force_update=true );
		
	}
	
	// ****************************************************************
	// Cacheing functions to make meta retreivals faster
	// ****************************************************************
	
	/**
	 * Updates metadata cache for list of post IDs.
	 *
	 * Performs SQL query to retrieve the metadata for the post IDs and updates the
	 * metadata cache for the posts. Therefore, the functions, which call this
	 * function, do not need to perform SQL queries on their own.
	 *
	 * @package WordPress
	 * @subpackage Cache
	 * @since 2.1.0
	 *
	 * @uses $wpdb
	 *
	 * @param array $post_ids List of post IDs.
	 * @return bool|array Returns false if there is nothing to update or an array of metadata.
	 */
	function update_postmeta_cache($post_ids) {
		return $this->update_meta_cache($post_ids);
	}
	
	/**
	 * Update the metadata cache for the specified objects.
	 *
	 * @since 2.9.0
	 * @uses $wpdb WordPress database object for queries.
	 *
	 * @param string $meta_type Type of object metadata is for (e.g., comment, post, or user)
	 * @param int|array $object_ids array or comma delimited list of object IDs to update cache for
	 * @return mixed Metadata cache for the specified objects, or false on failure.
	 */
	function update_meta_cache( $object_ids, $force_update = false ) {
		if ( empty( $object_ids ) )
			return false;
		
		$table = $this->Helper('db')->FixTableName( $this->m_meta_table, $this );
	
		/*if ( ! $table = _get_meta_table($meta_type) )
			return false;*/
	
		$column = 'post_id';
	
		global $wpdb;
	
		if ( !is_array($object_ids) ) {
			$object_ids = preg_replace('|[^0-9,]|', '', $object_ids);
			$object_ids = explode(',', $object_ids);
		}
	
		//$object_ids = array_map('intval', $object_ids);
	//ItemDebug( $object_ids );
		$cache_key = $this->m_meta_table;//$meta_type . '_meta';
		$ids = array();
		$cache = array();
		foreach ( $object_ids as $id ) {
			$cached_object = wp_cache_get( $id, $cache_key );
			if ( false === $cached_object || $force_update )
				$ids[] = $id;
			else
				$cache[$id] = $cached_object;
		}
	
		if ( empty( $ids ) )
			return $cache;
	
		// Get meta info
		$id_list = join(',', $ids);
		if( $id_list )
			$meta_list = $wpdb->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list)", ARRAY_A );
	
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow) {
				$mpid = intval($metarow[$column]);
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];
	
				// Force subkeys to be array type:
				if ( !isset($cache[$mpid]) || !is_array($cache[$mpid]) )
					$cache[$mpid] = array();
				if ( !isset($cache[$mpid][$mkey]) || !is_array($cache[$mpid][$mkey]) )
					$cache[$mpid][$mkey] = array();
	
				// Add a value to the current pid/key:
				//$cache[$mpid][$mkey][] = $mval;
				$cache[$mpid][$mkey] = maybe_unserialize( $mval );
				
				if( !is_array($cache[$mpid][$mkey]) )
					$cache[$mpid][$mkey] = $this->FW()->Helper('tools')->Clean( $cache[$mpid][$mkey] );
			}
		}
	
		foreach ( $ids as $id ) {
			if ( ! isset($cache[$id]) )
				$cache[$id] = array();
			wp_cache_set( $id, $cache[$id], $cache_key );
		}
	
		return $cache;
	}
	
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
	function update_post_caches(&$posts, $update_meta_cache = true) {
		// No point in doing all this work if we didn't match any posts.
		if ( !$posts )
			return;
	
		$this->update_post_cache($posts);
	
		$post_ids = array();
		foreach ( $posts as $post )
			$post_ids[] = $post->ID;
	
		if ( $update_meta_cache )
			$this->update_postmeta_cache($post_ids);
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
	function _prime_post_caches( $ids, $update_term_cache = true, $update_meta_cache = true ) {
		global $wpdb;
	
		$table = $this->Helper('db')->FixTableName( $this->m_post_table, $this );
	
		$non_cached_ids = _get_non_cached_ids( $ids, $this->m_post_table );
		
		//ItemDebug( $non_cached_ids );
		if ( !empty( $non_cached_ids ) ) {
			$fresh_posts = $wpdb->get_results( sprintf( "SELECT $table.* FROM $table WHERE ID IN (%s)", join( ",", $non_cached_ids ) ) );
	
			$this->update_post_caches( $fresh_posts, $update_meta_cache );
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
	
		wp_cache_delete( $post_id, $this->m_post_table );
		wp_cache_delete( $post_id, $this->m_meta_table );
	// continue here;
		//clean_object_term_cache( $post_id, $post->post_type );
	
		wp_cache_delete( 'wp_get_archives', 'general' );
	
		do_action( 'clean_post_cache', $post_id );
	
		/*if ( 'page' == $post->post_type ) {
			wp_cache_delete( 'all_page_ids', 'posts' );
			wp_cache_delete( 'get_pages', 'posts' );
			do_action( 'clean_page_cache', $post_id );
		}*/
	
		if ( $children = $wpdb->get_results( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = %d", $post_id) ) ) {
			foreach ( $children as $child ) {
				// Loop detection
				if ( $child->ID == $post_id )
					continue;
				clean_post_cache( $child );
			}
		}
	
		if ( is_multisite() )
			wp_cache_delete( $wpdb->blogid . '-' . $post_id, 'global-posts' );
	}
	
	function wp_cache_get( $post_id )
	{
		$data = wp_cache_get($post_id, $this->m_post_table);
		
		return $data;
	}
	
	function wp_cache_add( $post_id, $data )
	{
		wp_cache_set( $post_id, $data, $this->m_post_table );
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}







?>