<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Services Kernel [DB Abstraction]
|	Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
+---------------------------------------------------------------------------
|   THIS IS NOT FREE / OPEN SOURCE SOFTWARE
+---------------------------------------------------------------------------
|
|   > MySQLi Driver Module
|   > Module written by Brandon Farber
|   > Date started: Monday 28th February 2005 16:46 
|
|	> Module Version Number: 2.1.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Database Object Driver
*
* @package		IPS_KERNEL
* @subpackage	DatabaseAbstraction
* @author		Brandon Farber
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/


/**
 * Handle base class definitions
 *
 */

if ( ! defined('KERNEL_PATH') )
{
	define( 'KERNEL_PATH', dirname(__FILE__) . '/' );
}

if ( ! class_exists( 'db_main' ) )
{
	require_once( KERNEL_PATH.'class_db.php' );
}

/**
 * Allow < 4.3.0 PHP client access
 *
 */
if ( ! defined('IPS_MAIN_DB_CLASS_LEGACY') )
{
	define('IPS_MAIN_DB_CLASS_LEGACY', ( PHP_VERSION < '4.3.0' ) ? TRUE : FALSE );
}

/**
* DB Class: Driver Methods
*
* Base class for database abstraction
*
* @package		IPS_KERNEL
* @subpackage	DatabaseAbstraction
* @author   	Brandon Farber
* @version		2.1
*/
class db_driver_mysql extends db_main
{
	var $connect_failed	= 0;
	
	var $cached_fields	= array();
	var $cached_tables	= array();
	
	/*-------------------------------------------------------------------------*/
	// Set up required vars
	/*-------------------------------------------------------------------------*/
	 
	function db_driver_mysql()
	{
		if( !defined('MYSQLI_USED') )
		{
			// Stewart instantiates 2 drivers for conversions
			
			define( 'MYSQLI_USED', 1 );
		}
		
		//--------------------------
		// Set up any required connect
		// vars here:
		// Will be populated by obj
		// caller
		//--------------------------
		
     	$this->connect_vars['mysql_tbl_type'] = "";
	}
	
    /*-------------------------------------------------------------------------*/
    // Connect to the database  
    /*-------------------------------------------------------------------------*/  
                   
	function connect()
	{
		//-----------------------------------------
     	// Done SQL prefix yet?
     	//-----------------------------------------
     	
     	$this->_set_prefix();
     	
    	//-----------------------------------------
    	// Load query file
    	//-----------------------------------------
    	
    	$this->_load_cache_file();
     	
     	//-----------------------------------------
     	// Connect
     	//-----------------------------------------
     	
     	if( $this->obj['sql_port'] )
     	{
			$this->connection_id = @mysqli_connect( $this->obj['sql_host'] ,
													  $this->obj['sql_user'] ,
													  $this->obj['sql_pass'],
													  $this->obj['sql_database'],
													  $this->obj['sql_port']
													);
		}
		else
		{
			$this->connection_id = @mysqli_connect( $this->obj['sql_host'] ,
													  $this->obj['sql_user'] ,
													  $this->obj['sql_pass'],
													  $this->obj['sql_database']
													);
		}
		
		if ( ! $this->connection_id )
		{
			$this->connect_failed = 1;
			$this->fatal_error();
			return FALSE;
		}
		
        /*if ( ! mysqli_select_db( $this->connection_id, $this->obj['sql_database'] ) )
        {
        	$this->fatal_error();
        	return FALSE;
        }*/
        
        mysqli_autocommit( $this->connection_id, TRUE );
        
		unset( $this->obj['sql_host'] );
		unset( $this->obj['sql_user'] );
		unset( $this->obj['sql_pass'] );
       
        return TRUE;
    }
    
    function sql_can_subquery()
    {
		$this->sql_get_version();
		
		if ( $this->mysql_version >= 41000 )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}    
    
    /*-------------------------------------------------------------------------*/
    // Quick function: DO UPDATE
    /*-------------------------------------------------------------------------*/
    
    function do_update( $tbl, $arr, $where="", $shutdown=FALSE )
    {
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------
    	
    	$dba   = $this->compile_db_update_string( $arr );
    	$query = "UPDATE ".$this->obj['sql_tbl_prefix']."$tbl SET $dba";
    	
    	if ( $where )
    	{
    		$query .= " WHERE ".$where;
    	}
    	
    	//-----------------------------------------
    	// Shut down query?
    	//-----------------------------------------
    	
    	$this->no_prefix_convert = 1;
    	
    	if ( $shutdown )
    	{
    		if ( ! $this->obj['use_shutdown'] )
			{
				$this->is_shutdown = 1;
				$return = $this->query( $query );
				$this->no_prefix_convert = 0;
				$this->is_shutdown = 0;
				return $return;
			}
			else
			{
				$this->obj['shutdown_queries'][] = $query;
				$this->no_prefix_convert = 0;
				$this->cur_query = "";
			}
    	}
    	else
    	{
    		$return = $this->query( $query );
    		$this->no_prefix_convert = 0;
    		return $return;
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // Quick function: DO INSERT
    /*-------------------------------------------------------------------------*/
    
    function do_insert( $tbl, $arr, $shutdown=FALSE )
    {
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------
    	
    	$dba   = $this->compile_db_insert_string( $arr );
    	
		$query = "INSERT INTO ".$this->obj['sql_tbl_prefix']."$tbl ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})";
    	
    	//-----------------------------------------
    	// Shut down query?
    	//-----------------------------------------
    	
    	$this->no_prefix_convert = 1;
    	
    	if ( $shutdown )
    	{
    		if ( ! $this->obj['use_shutdown'] )
			{
				$this->is_shutdown = 1;
				$return = $this->query( $query );
				$this->no_prefix_convert = 0;
				$this->is_shutdown = 1;
				return $return;
			}
			else
			{
				$this->obj['shutdown_queries'][] = $query;
				$this->no_prefix_convert = 0;
				$this->cur_query = "";
			}
    	}
    	else
    	{
    		$return = $this->query( $query );
    		$this->no_prefix_convert = 0;
    		return $return;
    	}
    }
    
    
    /*-------------------------------------------------------------------------*/
    // Quick function: DO REPLACE INTO
    /*-------------------------------------------------------------------------*/
    
    function do_replace_into( $tbl, $arr, $where='', $shutdown=FALSE )
    {
    	//-----------------------------------------
    	// Form query
    	//-----------------------------------------
    	
    	$dba   = $this->compile_db_insert_string( $arr );
    	
		$query = "REPLACE INTO ".$this->obj['sql_tbl_prefix']."$tbl ({$dba['FIELD_NAMES']}) VALUES({$dba['FIELD_VALUES']})";
    	
    	//-----------------------------------------
    	// Shut down query?
    	//-----------------------------------------
    	
    	$this->no_prefix_convert = 1;
    	
    	if ( $shutdown )
    	{
    		if ( ! $this->obj['use_shutdown'] )
			{
				$this->is_shutdown = 1;
				$return = $this->query( $query );
				$this->no_prefix_convert = 0;
				$this->is_shutdown = 1;
				return $return;
			}
			else
			{
				$this->obj['shutdown_queries'][] = $query;
				$this->no_prefix_convert = 0;
				$this->cur_query = "";
			}
    	}
    	else
    	{
    		$return = $this->query( $query );
    		$this->no_prefix_convert = 0;
    		return $return;
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: UPDATE
    /*-------------------------------------------------------------------------*/
    
    function simple_update( $tbl, $set, $where='', $low_pro='' )
    {
    	if ( $low_pro )
    	{
    		$low_pro = ' LOW_PRIORITY ';
    	}
    	
    	$this->cur_query .= "UPDATE ". $low_pro . $this->obj['sql_tbl_prefix']."$tbl SET $set";
    	
    	if ( $where )
    	{
    		$this->cur_query .= " WHERE $where";
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: DELETE
    /*-------------------------------------------------------------------------*/
    
    function simple_delete( $tbl, $where='' )
    {
	    if( !$where )
	    {
		    $this->cur_query .= "TRUNCATE TABLE ".$this->obj['sql_tbl_prefix']."$tbl";
	    }
	    else
	    {
    		$this->cur_query .= "DELETE FROM ".$this->obj['sql_tbl_prefix']."$tbl WHERE $where";
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: ORDER
    /*-------------------------------------------------------------------------*/
    
    function simple_order( $a )
    {
    	if ( $a )
    	{
    		$this->cur_query .= ' ORDER BY '.$a;
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: GROUP
    /*-------------------------------------------------------------------------*/
    
    function simple_group( $a )
    {
    	if ( $a )
    	{
    		$this->cur_query .= ' GROUP BY '.$a;
    	}
    }     
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: LIMIT WITH CHECK
    /*-------------------------------------------------------------------------*/
    
    function simple_limit_with_check( $offset, $limit="" )
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$offset = intval( $offset );
		$offset = ( $offset < 0 ) ? 0 : $offset;
		$limit  = intval( $limit );
		#$limit  = ( $limit < 0 ) ? 0 : $limit;
		
    	if ( ! preg_match( "#LIMIT\s+?\d+,#i", $this->cur_query ) )
		{
			$this->simple_limit( $offset, $limit );
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: LIMIT
    /*-------------------------------------------------------------------------*/
    
    function simple_limit( $offset, $limit="" )
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$offset = intval( $offset );
		$offset = ( $offset < 0 ) ? 0 : $offset;
		$limit  = intval( $limit );
		#$limit  = ( $limit < 0 ) ? 0 : $limit;
		
    	if ( $limit )
    	{
    		$this->cur_query .= ' LIMIT '.$offset.','.$limit;
    	}
    	else
    	{
    		$this->cur_query .= ' LIMIT '.$offset;
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: SELECT
    /*-------------------------------------------------------------------------*/
    
    function simple_select( $get, $table, $where="" )
    {
    	$this->cur_query .= "SELECT $get FROM ".$this->obj['sql_tbl_prefix']."$table";
    	
    	if ( $where != "" )
    	{
    		$this->cur_query .= " WHERE ".$where;
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: SELECT WITH JOIN
    /*-------------------------------------------------------------------------*/
    
    function simple_select_with_join( $get, $table, $where="", $add_join=array() )
    {
    	//-----------------------------------------
    	// OK, here we go...
    	//-----------------------------------------
    	
    	$select_array   = array();
    	$from_array     = array();
    	$joinleft_array = array();
    	$where_array    = array();
    	$final_from     = array();
    	
    	$select_array[] = $get;
    	$from_array[]   = $table;
    	
    	if ( $where )
    	{
    		$where_array[]  = $where;
    	}
    	
    	//-----------------------------------------
    	// Loop through JOINs and sort info
    	//-----------------------------------------
    	
    	if ( is_array( $add_join ) and count( $add_join ) )
    	{
    		foreach( $add_join as $join )
    		{
    			# Push join's select to stack
    			if ( isset($join['select']) AND $join['select'] )
    			{
    				$select_array[] = $join['select'];
    			}
    			
    			if ( $join['type'] == 'inner' )
    			{
    				# Join is inline
    				$from_array[]  = $join['from'];
    				
    				if ( $join['where'] )
    				{
    					$where_array[] = $join['where'];
    				}
    			}
    			else if ( $join['type'] == 'left' )
    			{
    				# Join is left
    				$tmp = " LEFT JOIN ";
    				
    				foreach( $join['from'] as $tbl => $alias )
					{
						$tmp .= $this->obj['sql_tbl_prefix'].$tbl.' '.$alias;
					}
		
    				if ( $join['where'] )
    				{
    					$tmp .= " ON ( ".$join['where']." ) ";
    				}
    				
    				$joinleft_array[] = $tmp;
    				
    				unset( $tmp );
    			}
    			else
    			{
    				# Not using any other type of join
    			}
    		}
    	}
    	
    	//-----------------------------------------
    	// Build it..
    	//-----------------------------------------
    	
    	foreach( $from_array as $i )
		{
			foreach( $i as $tbl => $alias )
			{
				$final_from[] = $this->obj['sql_tbl_prefix'].$tbl.' '.$alias;
			}
		}
    	
    	$get   = implode( ","     , $select_array   );
    	$table = implode( ","     , $final_from     );
    	$where = implode( " AND " , $where_array    );
    	$join  = implode( "\n"    , $joinleft_array );
    	
    	$this->cur_query .= "SELECT $get FROM $table";
    	
    	if ( $join )
    	{
    		$this->cur_query .= " ".$join." ";
    	}
    	
    	if ( $where != "" )
    	{
    		$this->cur_query .= " WHERE ".$where;
    	}
    }
   
    
    /*-------------------------------------------------------------------------*/
    // Process a manual query
    /*-------------------------------------------------------------------------*/
    
    function query($the_query, $bypass=0)
    {
    	//-----------------------------------------
        // Change the table prefix if needed
        //-----------------------------------------
        
        if ( $this->no_prefix_convert )
        {
        	$bypass = 1;
        }
        
        if ( ! $bypass )
        {
			if ( $this->obj['sql_tbl_prefix'] != "ibf_" and ! $this->prefix_changed )
			{
			   $the_query = preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->obj['sql_tbl_prefix']."\\1\\2", $the_query);
			}
        }
        
        //-----------------------------------------
        // Debug?
        //-----------------------------------------
        
        if ( $this->obj['debug'] OR ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) )
        {
    		global $Debug;
    		
    		$Debug->startTimer();
    	}
    	
		//-----------------------------------------
		// Stop sub selects? (UNION)
		//-----------------------------------------
		
		if ( ! IPS_DB_ALLOW_SUB_SELECTS )
		{
			# On the spot allowance?
			if ( ! $this->allow_sub_select )
			{
				$_tmp = strtolower( $this->remove_all_quotes($the_query) );
				
				if ( preg_match( "#(?:/\*|\*/)#i", $_tmp ) )
				{
					$this->fatal_error( "You are not allowed to use comments in your SQL query.\nAdd \$this->ipsclass->DB->allow_sub_select=1; before any query construct to allow them" );
					return false;
				}
				
				if ( preg_match( "#[^_a-zA-Z]union[^_a-zA-Z]#s", $_tmp ) )
				{
					$this->fatal_error( "UNION query joins are not allowed.\nAdd \$this->ipsclass->DB->allow_sub_select=1; before any query construct to allow them" );
					return false;
				}
				else if ( preg_match_all( "#[^_a-zA-Z](select)[^_a-zA-Z]#s", $_tmp, $matches ) )
				{
					if ( count( $matches ) > 1 )
					{
						$this->fatal_error( "SUB SELECT query joins are not allowed.\nAdd \$this->ipsclass->DB->allow_sub_select=1; before any query construct to allow them" );
						return false;
					}
				}
			}
		}
		
    	//-----------------------------------------
    	// Run the query
    	//-----------------------------------------
    	
        $this->query_id = mysqli_query($this->connection_id, $the_query );

      	//-----------------------------------------
      	// Reset array...
      	//-----------------------------------------
      	
      	$this->force_data_type  = array();
      	$this->allow_sub_select = 0;

        if (! $this->query_id )
        {
            $this->fatal_error("mySQL query error: $the_query");
        }
        
        //-----------------------------------------
        // Debug?
        //-----------------------------------------
        
		if ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] )
		{
			$endtime  = $Debug->endTimer();
			
			if ( preg_match( "/^(?:\()?select/i", $the_query ) )
        	{
        		$eid = mysqli_query($this->connection_id, "EXPLAIN $the_query");
        		
				while( $array = mysqli_fetch_array($eid) )
				{
					$_data .= "\n+------------------------------------------------------------------------------+";
					$_data .= "\n|Table: ". $array['table'];
					$_data .= "\n|Type: ". $array['type'];
					$_data .= "\n|Possible Keys: ". $array['possible_keys'];
					$_data .= "\n|Key: ". $array['key'];
					$_data .= "\n|Key Len: ". $array['key_len'];
					$_data .= "\n|Ref: ". $array['ref'];
					$_data .= "\n|Rows: ". $array['rows'];
					$_data .= "\n|Extra: ". $array['extra'];
					$_data .= "\n+------------------------------------------------------------------------------+";
				}
			
				$this->write_debug_log( $the_query, $_data, $endtime );
			}
			else
			{
				$this->write_debug_log( $the_query, $_data, $endtime );
			}
		}
        else if ($this->obj['debug'])
        {
        	$endtime  = $Debug->endTimer();
        	
        	$shutdown = $this->is_shutdown ? 'SHUTDOWN QUERY: ' : '';
        	
        	if ( preg_match( "/^(?:\()?select/i", $the_query ) )
        	{
        		$eid = mysqli_query( $this->connection_id, "EXPLAIN $the_query" );
        		
        		$this->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FFE8F3' align='center'>
										   <tr>
										   	 <td colspan='8' style='font-size:14px' bgcolor='#FFC5Cb'><b>{$shutdown}Select Query</b></td>
										   </tr>
										   <tr>
										    <td colspan='8' style='font-family:courier, monaco, arial;font-size:14px;color:black'>$the_query</td>
										   </tr>
										   <tr bgcolor='#FFC5Cb'>
											 <td><b>table</b></td><td><b>type</b></td><td><b>possible_keys</b></td>
											 <td><b>key</b></td><td><b>key_len</b></td><td><b>ref</b></td>
											 <td><b>rows</b></td><td><b>Extra</b></td>
										   </tr>\n";
				while( $array = mysqli_fetch_array($eid) )
				{
					$type_col = '#FFFFFF';
					
					if ($array['type'] == 'ref' or $array['type'] == 'eq_ref' or $array['type'] == 'const')
					{
						$type_col = '#D8FFD4';
					}
					else if ($array['type'] == 'ALL')
					{
						$type_col = '#FFEEBA';
					}
					
					$this->debug_html .= "<tr bgcolor='#FFFFFF'>
											 <td>$array[table]&nbsp;</td>
											 <td bgcolor='$type_col'>$array[type]&nbsp;</td>
											 <td>$array[possible_keys]&nbsp;</td>
											 <td>$array[key]&nbsp;</td>
											 <td>$array[key_len]&nbsp;</td>
											 <td>$array[ref]&nbsp;</td>
											 <td>$array[rows]&nbsp;</td>
											 <td>$array[Extra]&nbsp;</td>
										   </tr>\n";
				}
				
				$this->sql_time += $endtime;
				
				if ($endtime > 0.1)
				{
					$endtime = "<span style='color:red'><b>$endtime</b></span>";
				}
				
				$this->debug_html .= "<tr>
										  <td colspan='8' bgcolor='#FFD6DC' style='font-size:14px'><b>MySQL time</b>: $endtime</b></td>
										  </tr>
										  </table>\n<br />\n";
			}
			else
			{
			  $this->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FEFEFE'  align='center'>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>{$shutdown}Non Select Query</b></td>
										 </tr>
										 <tr>
										  <td style='font-family:courier, monaco, arial;font-size:14px'>$the_query</td>
										 </tr>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>MySQL time</b>: $endtime</span></td>
										 </tr>
										</table><br />\n\n";
			}
		}
		
		$this->query_count++;
        
        $this->obj['cached_queries'][] = $the_query;
        
        return $this->query_id;
    }
    
    /*-------------------------------------------------------------------------*/
    // Fetch a row based on the last query
    /*-------------------------------------------------------------------------*/
    
    function fetch_row($query_id = "")
    {
    	if ( ! $query_id )
    	{
    		$query_id = $this->query_id;
    	}
    	
        $this->record_row = mysqli_fetch_array($query_id, MYSQLI_ASSOC);
        
        return $this->record_row;
    }
    
	/*-------------------------------------------------------------------------*/
	// DROP TABLE
	/*-------------------------------------------------------------------------*/
	
	function sql_drop_table( $table )
	{
		$this->query( "DROP TABLE if exists ".$this->obj['sql_tbl_prefix']."{$table}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// DROP FIELD
	/*-------------------------------------------------------------------------*/
	
	function sql_drop_field( $table, $field )
	{
		$this->query( "ALTER TABLE ".$this->obj['sql_tbl_prefix']."{$table} DROP $field" );
	}
	
	/*-------------------------------------------------------------------------*/
	// ADD FIELD
	/*-------------------------------------------------------------------------*/
	
	function sql_add_field( $table, $field_name, $field_type, $field_default='' )
	{
		$default = 'NULL';
		
		if( $field_default !== '' )
		{
			$default = "default {$field_default}";
		}
		
		$this->query( "ALTER TABLE ".$this->obj['sql_tbl_prefix']."{$table} ADD {$field_name} {$field_type} {$default}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// CHANGE FIELD
	/*-------------------------------------------------------------------------*/
	
	function sql_change_field( $table, $original_field, $field_name, $field_type, $field_default="''" )
	{
		$this->query( "ALTER TABLE ".$this->obj['sql_tbl_prefix']."{$table} CHANGE $original_field $field_name $field_type default {$field_default}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// OPTIMIZE TABLE
	/*-------------------------------------------------------------------------*/
	
	function sql_optimize_table( $table )
	{
		$this->query( "OPTIMIZE TABLE ".$this->obj['sql_tbl_prefix']."{$table}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// ADD FULLTEXT INDEX
	/*-------------------------------------------------------------------------*/
	
	function sql_add_fulltext_index( $table, $field )
	{
		$this->query( "alter table ".$this->obj['sql_tbl_prefix']."{$table} ADD FULLTEXT({$field})" );
	}
	
	/*-------------------------------------------------------------------------*/
	// GET TABLE SCHEMATIC
	/*-------------------------------------------------------------------------*/
	
	function sql_get_table_schematic( $table )
	{
		$this->return_die = 1;
		
		$qid = $this->query( "SHOW CREATE TABLE ".$this->obj['sql_tbl_prefix']."{$table}" );
		
		$this->return_die = 0;
		
		if( $qid )
		{
			return $this->fetch_row($qid);
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// IS ALREADY TABLE FULLTEXT?
	/*-------------------------------------------------------------------------*/
	
	function sql_is_currently_fulltext( $table )
	{
		$result = $this->sql_get_table_schematic( $table );
		
		if ( preg_match( "/FULLTEXT KEY/i", $result['Create Table'] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Return the version number of the SQL server
	// Should return 'true' version string (ie: 3.23.0)
	// And formatted string (ie: 3230 )
	/*-------------------------------------------------------------------------*/
	
	function sql_get_version()
	{
		if ( ! $this->mysql_version and ! $this->true_version )
		{
			$version = mysqli_get_server_info($this->connection_id);
			
			$this->true_version = $version;
			$tmp                = explode( '.', preg_replace( "#[^\d\.]#", "\\1", $version ) );
			
			$this->mysql_version = sprintf('%d%02d%02d', $tmp[0], $tmp[1], $tmp[2] );
   		}
	}
	
	/*-------------------------------------------------------------------------*/
	// sql_can_fulltext
	// returns whether SQL engine has fulltext abilities
	// returns TRUE or FALSE
	/*-------------------------------------------------------------------------*/
	
	function sql_can_fulltext()
	{
		$this->sql_get_version();
		
		if ( $this->mysql_version >= 32323 AND strtolower($this->connect_vars['mysql_tbl_type']) == 'myisam' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// sql_can_fulltext_boolean
	// returns whether SQL engine has boolean fulltext abilities
	// (+word -word, etc)
	// returns TRUE or FALSE
	/*-------------------------------------------------------------------------*/
	
	function sql_can_fulltext_boolean()
	{
		$this->sql_get_version();
		
		if ( $this->mysql_version >= 40010 AND strtolower($this->connect_vars['mysql_tbl_type']) == 'myisam' )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
    // Fetch the number of rows affected by the last query
    /*-------------------------------------------------------------------------*/
    
    function get_affected_rows()
    {
        return mysqli_affected_rows($this->connection_id);
    }
    
    /*-------------------------------------------------------------------------*/
    // Fetch the number of rows in a result set
    /*-------------------------------------------------------------------------*/
    
    function get_num_rows( $query_id="" )
    {
		if ( ! $query_id )
   		{
    		$query_id = $this->query_id;
    	}

        return mysqli_num_rows( $query_id );
    }
    
    /*-------------------------------------------------------------------------*/
    // Fetch the last insert id from an sql autoincrement
    /*-------------------------------------------------------------------------*/
    
    function get_insert_id()
    {
        return mysqli_insert_id($this->connection_id);
    }  
    
    /*-------------------------------------------------------------------------*/
    // Free the result set from mySQLs memory
    /*-------------------------------------------------------------------------*/
    
    function free_result( $query_id="" )
    {
   		if ( ! $query_id )
   		{
    		$query_id = $this->query_id;
    	}
    	
    	@mysqli_free_result( $query_id );
    }
    
    /*-------------------------------------------------------------------------*/
    // Shut down the database
    /*-------------------------------------------------------------------------*/
    
    function close_db()
    { 
    	if ( $this->connection_id )
    	{
        	return @mysqli_close( $this->connection_id );
        }
    }
    
    /*-------------------------------------------------------------------------*/
    // Return an array of tables
    /*-------------------------------------------------------------------------*/
    
    function get_table_names()
    {
	    if ( is_array( $this->cached_tables ) AND count( $this->cached_tables ) )
	    {
		    return $this->cached_tables;
	    }
	    
	    $_tmp = $this->return_die;
	    $this->return_die = 1;
	    
	    $tbl = $this->query( "SHOW TABLES FROM `{$this->obj['sql_database']}`" );
	    
	    $this->return_die = $_tmp;
	    
	    if( $tbl AND $this->get_num_rows($tbl) )
	    {
		    while( $result = mysqli_fetch_row($tbl) )
		    {
				$this->cached_tables[] = $result[0];
			}
		}
		
		mysqli_free_result($tbl);
		
		return $this->cached_tables;
   	}
   	
   	
    /*-------------------------------------------------------------------------*/
    // Check if table exists
    /*-------------------------------------------------------------------------*/
    
    function table_exists( $table )
    {
	    $table_names = $this->get_table_names();
	    
	    $return = 0;
	    
	    if ( in_array( trim( SQL_PREFIX . $table ), $table_names ) )
	    {
		    $return = 1;
	    }
	    
	    unset($table_names);
	    
	    return $return;
    }  
    
    /*-------------------------------------------------------------------------*/
    // Check if field exists
    /*-------------------------------------------------------------------------*/
    
    function field_exists($field, $table)
    {
	    if( array_key_exists( $table, $this->cached_fields ) )
	    {
		    if( in_array( $field, $this->cached_fields[ $table ] ) )
		    {
			    return 1;
		    }
		    else
		    {
			    return 0;
		    }
	    }
	    
		$this->return_die = 1;
		$this->error      = "";
		$return 		  = 0;
		
		$q = $this->query( "SHOW fields FROM " . SQL_PREFIX . $table );
		
		if( $q AND $this->get_num_rows($q) )
		{
			while( $check = $this->fetch_row($q) )
			{
				$this->cached_fields[ $table ][] = $check['Field'];
			}
		}
		
		if ( !$this->failed AND in_array( $field, $this->cached_fields[ $table ] ) )
		{
			$return = 1;
		}
		
		$this->error      = "";
		$this->return_die = 0;
		$this->error_no   = 0;
		$this->failed     = 0;
		
		return $return;
	}
   	
   	/*-------------------------------------------------------------------------*/
    // Return an array of fields
    /*-------------------------------------------------------------------------*/
    
    function get_result_fields($query_id="")
    {
    	$Fields = array();
    	
   		if ( !$query_id )
   		{
    		$query_id = $this->query_id;
    	}
    
		while ($field = mysqli_fetch_field($query_id))
		{
            $Fields[] = $field;
		}
		
		return $Fields;
   	}
    
    /*-------------------------------------------------------------------------*/
    // INTERNAL: Get error number
    /*-------------------------------------------------------------------------*/
    
    function _get_error_number()
    {
	    if( $this->connect_failed )
	    {
		    return mysqli_connect_errno( );
	    }
	    else
	    {
    		return mysqli_errno( $this->connection_id );
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // INTERNAL: Get error number
    /*-------------------------------------------------------------------------*/
    
    function _get_error_string()
    {
	    if( $this->connect_failed )
	    {
		    return mysqli_connect_error( );
	    }
	    else
	    {
    		return mysqli_error( $this->connection_id );
		}
    }
    
	/*-------------------------------------------------------------------------*/
	// Use different escape method for different SQL engines
	/*-------------------------------------------------------------------------*/
	
	function add_slashes( $t )
	{
		return mysqli_real_escape_string( $this->connection_id, $t );
	}
	
	/*-------------------------------------------------------------------------*/
	// Use different escape method for different SQL engines
	/*-------------------------------------------------------------------------*/
	
	function remove_slashes( $t )
	{
		# Not required for MySQL because we use the mysql_real_escape_string
		
		/*if ( get_magic_quotes_gpc() )
		{
    		$t = stripslashes($t);
    	}*/
    	
    	return $t;
	}
	
	
    
} // end class


?>