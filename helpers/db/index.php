<?php
class qodyHelper_FrameworkDatabase extends QodyHelper
{
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		parent::__construct();
	}
	
	function Query( $query, $output_type = OBJECT )
	{
		global $wpdb;
		
		return $wpdb->get_results( $query, $output_type );
	}
	
	function AlterTable( $table_name, $the_query, $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$wpdb->query( "ALTER TABLE `$table_name` $the_query" );
	}
	
	function TableExists( $table_name, $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		if( $wpdb->get_results( "SHOW TABLES LIKE '". $table_name ."'" ) )
			return true;
		
		return false;
	}
	
	function ColumnExists( $table_name, $column_name, $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$query = mysql_query( "SELECT * FROM $table_name" );
		
		if( !$query )
			return false;
			
		$found = false;
		for( $i = 0; $i < mysql_num_fields( $query ); $i++ )
		{
			$data = mysql_fetch_field( $query, $i );
			
			if( $data->name == $column_name )
				$found = true;
		}
		
		return $found;
	}
	
	function FixTableName( $table_name, $caller = null )
	{
		global $wpdb;
		
		if( $caller )
		{
			$table_name = is_subclass_of($caller, 'QodyOwnable') ? $caller->Owner()->GetPre().'_'.$table_name : $caller->GetPre().'_'.$table_name;
		}
		
		$table_name = $wpdb->prefix.$table_name;
		
		return $table_name;
	}
	
	function CreateTable( $table_name, $fields, $append_config = '', $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (".implode( ',', $fields ).") ";
		
		if( $append_config )
			$sql .= $append_config;
		
		$sql .= ';';
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	
	function Select( $table_name, $query = '', $caller = null, $postfix = '', $return_type = ARRAY_A )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$query_connector = strpos( $query, '=' ) !== false ? '' : '1 = 1';
		$data = $wpdb->get_results( "SELECT * FROM ".$table_name.($query ? " WHERE ".$query_connector.' '.$query : '')." ".$postfix, $return_type );
		
		return $data;
	}

	function GetFromDatabase( $table_name, $field = '', $value = '', $single = false, $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$value = $wpdb->escape( $value );
		
		$query = "SELECT * FROM ".$table_name;
		
		if( $field && $value )
			$query .= " WHERE {$field} = '{$value}'";
		
		$results = $wpdb->get_results( $query, ARRAY_A);
		
		if( $single )
			$results = $results[0];
			
		return $results;
	}
	
	function ClearTable( $table_name, $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$wpdb->query( "DELETE FROM ".$table_name );
	}
	
	function DeleteFromDatabase( $table_name, $field, $value, $caller = null )
	{
		global $wpdb;
		
		$table_name = $this->FixTableName( $table_name, $caller );
		
		$value = $wpdb->escape( $value );
		
		$wpdb->query( "DELETE FROM ".$table_name." WHERE {$field} = '{$value}'" );
	}
	
	function UpdateDatabase( $fields, $table_name, $id, $option = 'id', $caller = null, $use_prefix = true )
	{
		global $wpdb;
		
		if( $use_prefix )			
			$table_name = $this->FixTableName( $table_name, $caller );
		
		$first = '';
		$looper = 0;
		
		foreach( $fields as $key => $value )
		{
			$looper++;
			
			if( $looper == 1 )
				$first .= $wpdb->escape( $key )." = '".$wpdb->escape( $value )."' ";
			else
				$first .= ",".$wpdb->escape( $key )." = '".$wpdb->escape( $value )."' ";
		}
		
		$wpdb->query( "UPDATE ".$table_name." SET ".$first." WHERE {$option} = '".$wpdb->escape( $id )."'" );
	}
	
	function InsertToDatabase( $fields, $table_name, $caller = null, $use_prefix = true, $return_id_of_insert = false )
	{
		global $wpdb;
		
		if( !$fields )
			return;

		$first = '';
		$second = '';
		$looper = 0;
		
		if( $caller )
		{
			$table_name = is_subclass_of($caller, 'QodyOwnable') ? $caller->Owner()->GetPre().'_'.$table_name : $caller->GetPre().'_'.$table_name;
		}
		
		if( $use_prefix )
			$table_name = $wpdb->prefix.$table_name;
		
		$bits = explode( '.', $table_name );
		
		if( count($bits) > 1 )
		{
			$table_name = '`'.$bits[0].'` . `'.$bits[1].'`';
		}
		else
		{
			$table_name = '`'.$table_name.'`';
		}
		
		foreach( $fields as $key => $value )
		{
			$looper++;
			
			if( $looper == 1 )
				$first .= "`".$wpdb->escape( $key )."`";
			else
				$first .= ",`".$wpdb->escape( $key )."`";
				
			if( $looper == 1 )
				$second .= "'".$wpdb->escape( $value )."'";
			else
				$second .= ",'".$wpdb->escape( $value )."'";			
		}
		
		$wpdb->query( "INSERT INTO ".$table_name." (".$first.") VALUES (".$second.")" );
		
		if( $return_id_of_insert )
		{
			$query = "SELECT id FROM $table_name ORDER BY id DESC LIMIT 0,1";
			$results = $wpdb->get_results( $query, ARRAY_A);
			
			return $results[0];
		}
	}

}
?>