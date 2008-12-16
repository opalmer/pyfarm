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
|   > Core Module
|   > Module written by Matt Mecham
|   > Date started: Monday 28th February 2005 16:46 
|
|	> Module Version Number: 2.1.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Database Object Core
*
* Basic Usage Examples
* <code>
* $db = new db_driver();
* Update:
* $db->do_update( 'table', array( 'field' => 'value', 'field2' => 'value2' ), 'id=1' );
* Insert
* $db->do_insert( 'table', array( 'field' => 'value', 'field2' => 'value2' ) );
* Delete
* $db->build_and_exec_query( array( 'delete' => 'table', 'where' => 'id=1' ) );
* Select
* $db->build_query( array( 'select' => '*',
*						   'from'   => 'table',
*						   'where'  => 'id=2 and mid=1',
*						   'order'  => 'date DESC',
*						   'limit'  => array( 0, 30 ) ) );
* $db->exec_query();
* while( $row = $db->fetch_row() ) { .... }
* Select with join
* $db->build_query( array( 'select'   => 'd.*',
* 						   'from'     => array( 'dnames_change' => 'd' ),
* 						   'where'    => 'dname_member_id='.$id,
* 						   'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
* 													 'from'   => array( 'members' => 'm' ),
* 													 'where'  => 'm.id=d.dname_member_id',
* 													 'type'   => 'inner' ) ),
* 						   'order'    => 'dname_date DESC' ) );
*  $db->exec_query();
* </code>
*  		
* @package		IPS_KERNEL
* @subpackage	DatabaseAbstraction
* @author   	Matt Mecham
* @version		2.1
*/

/**
* DB Class: Core Methods
*
* Base class for database abstraction
*
* @package		IPS_KERNEL
* @subpackage	DatabaseAbstraction
* @author   	Matt Mecham
* @version		2.1
*/

//-----------------------------------------
// Allow SUB SELECTS?
//-----------------------------------------

/**
* This can be overridden by using
* $DB->allow_sub_select = 1;
* before any query construct
*/

define( 'IPS_DB_ALLOW_SUB_SELECTS', 0 );


class db_main
{
	# Global OBJECT
	
	/**
	* DB object array
	*
	* @var array
	*/
	var $obj = array ( "sql_database"         => ""         ,
					   "sql_user"             => "root"     ,
					   "sql_pass"             => ""         ,
					   "sql_host"             => "localhost",
					   "sql_port"             => ""         ,
					   "persistent"           => "0"        ,
					   "sql_tbl_prefix"       => "ibf_"     ,
					   "cached_queries"       => array()    ,
					   'shutdown_queries'     => array()    ,
					   'debug'                => 0          ,
					   'use_shutdown'         => 1          ,
					   'query_cache_file'     => ''         ,
					   'force_new_connection' => 0          ,
					   'error_log'            => ''         ,
					   'use_error_log'        => 0          ,
					   'use_debug_log'        => 0          ,
					 );
	
	/**
	* Error message
	*
	* @var string
	*/
	var $error 				= "";
	var $error_no			= "";
	
	/**
	* Return error message or die inline
	*
	* @var integer
	*/
	var $return_die        = 0;
	
	/**
	* DB query failed
	*
	* @var integer
	*/
	var $failed            = 0;
	
	/**
	* Work based SQL query
	*
	* @var string
	*/
	var $sql               = "";
	
	/**
	* Current sql query
	*
	* @var string
	*/
	var $cur_query         = "";
	
	/**
	* Current DB query ID
	*
	* @var object
	*/
	var $query_id          = "";
	
	/**
	* Current DB connection ID
	*
	* @var object
	*/
	var $connection_id     = "";
	
	/**
	* Number of queries used per page gen
	*
	* @var integer
	*/
	var $query_count       = 0;
	
	/**
	* Escape / don't escape slashes during insert ops
	*
	* @var integer
	*/
	var $manual_addslashes = 0;
	
	/**
	* Is a shutdown query
	*
	* @var integer
	*/
	var $is_shutdown       = 0;
	
	/**
	* Prefix handling
	*
	* @var integer
	*/
	var $prefix_changed    = 0;
	
	/**
	* Prefix already converted
	*
	* @var integer
	*/
	var $no_prefix_convert = 0;
	
	/**
	* DB record row
	*
	* @var array
	*/
	var $record_row        = array();
	
	/**
	* Extra classes loaded
	*
	* @var array
	*/
	var $loaded_classes    = array();
	
	/**
	* Connection variables set when installed
	*
	* @var array
	*/
	var $connect_vars      = array();
	
	/**
	* Over-ride guessed data types in insert/update ops
	*
	* @var array
	*/
	var $force_data_type   = array();
	
	/**
	* Select which fields aren't escaped during insert/update ops
	*
	* @var array
	*/
	var $no_escape_fields  = array();
	
	/**
	* Name of queries file class
	*
	* @var string
	*/
	var $sql_queries_name  = 'sql_queries';
	
	
	var $mysql_version;
	var $true_version;
	
	/*
	* Allow sub selects for this query
	*
	* @var int
	*/
	var $allow_sub_select = 0;

	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/
	
	/**
	* db_driver constructor
	*
	*/
	
	function db_driver()
	{
		//--------------------------------------------
		// Set up any required connect vars here:
		//--------------------------------------------
		
     	$this->connect_vars = array();
	}
     
    /*-------------------------------------------------------------------------*/
    // Set debug mode
    /*-------------------------------------------------------------------------*/
    
    /**
	* Set debug mode flag
	*
	* @param	integer Boolean
	*/
	
    function set_debug_mode( $int=0 )
    {
    	$this->obj['debug'] = intval($int);
    	
    	//-----------------------------------------
     	// If debug, no shutdown....
     	//-----------------------------------------
     	
     	if ( $this->obj['debug'] )
     	{
     		$this->obj['use_shutdown'] = 0;
     	}
	}
	
	/*-------------------------------------------------------------------------*/
	// Set manual escape fields
	/*-------------------------------------------------------------------------*/
	
	/**
	* Set no escape fields via DB class
	*
	* @param	array SQL table fields
	*/
	
	function set_no_escape_fields( $array=array() )
	{
		$this->no_escape_fields = $array;
	}
    
    /*-------------------------------------------------------------------------*/
    // Load extra query cache file
    /*-------------------------------------------------------------------------*/
    
    /**
	* Load extra SQL query file (DBA)
	*
	* @param	string File
	* @param	string Classname of file
	*/
	
    function load_cache_file( $filepath, $classname='sql_extra_queries' )
    {
    	if ( ! file_exists( $filepath ) )
    	{
    		print "Cannot locate $filepath - exiting!";
    		exit();
    	}
    	else
    	{
    		require_once( $filepath );
    		{
    			$this->$classname       = new $classname( $this );
    			$this->loaded_classes[] = $classname;
    		}
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Simple elements
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for build_query
	*
	* @param	array SQL commands
	* @see		build_query
	* @since	2.1
	*/
	
    function simple_construct( $a )
    {
    	return $this->build_query( $a );
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Load cache query
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for build_query_from_cache
	*
	* @param	string	Name of query file function to use
	* @param	array	Optional arguments to be parsed inside query function
	* @param	string	Optional class  name
	* @see		build_query_from_cache
	* @since	2.1
	*/
	
    function cache_add_query( $q="", $args=array(), $method='sql_queries' )
    {
    	return $this->build_query_from_cache( $q, $args, $method );
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Simple elements
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for exec_query
	*
	* @see		exec_query
	* @since	2.1
	*/
	
    function cache_exec_query()
    {
    	return $this->exec_query();
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Execute a shutdown query from cache or build query
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for exec_shutdown_query
	*
	* @see		exec_shutdown_query
	* @since	2.1
	*/
	
    function cache_shutdown_exec()
    {
    	return $this->exec_shutdown_query();
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Simple elements
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for simple_exec
	*
	* @see		simple_exec
	* @since	2.1
	*/
	
    function simple_exec()
    {
    	return $this->exec_query();
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Shutdown store query
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for exec_shutdown_query
	*
	* @see		exec_shutdown_query
	* @since	2.1
	*/
	
    function simple_shutdown_exec()
    {
    	return $this->exec_shutdown_query();
    }
    
    /*-------------------------------------------------------------------------*/
    // ALIAS: Build and exec query
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for build_and_exec_query
	*
	* @see		build_and_exec_query
	* @since	2.1
	*/
	
    function simple_exec_query( $a )
    {
    	return $this->build_and_exec_query( $a );
    }
    
    /*-------------------------------------------------------------------------*/
    // Shutdown update
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for do_update
	*
	* @see		do_update
	* @since	2.1
	*/
	
    function do_shutdown_update( $tbl, $arr, $where="" )
    {
    	# Alias, redirect.
    	$this->do_update( $tbl, $arr, $where, TRUE );
    }
    
    /*-------------------------------------------------------------------------*/
    // Shutdown insert
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for do_insert
	*
	* @see		do_insert
	* @since	2.1
	*/
	
    function do_shutdown_insert( $tbl, $arr )
    {
    	# Alias, redirect.
    	$this->do_insert( $tbl, $arr, TRUE );
    }
    
    
    /*-------------------------------------------------------------------------*/
    // Shutdown replace into
    /*-------------------------------------------------------------------------*/
    
    /**
	* Alias for do_replace_into
	*
	* @see		do_update
	* @since	2.1
	*/
	
    function do_shutdown_replace_into( $tbl, $arr, $where )
    {
    	# Alias, redirect.
    	$this->do_replace_into( $tbl, $arr, $where, TRUE );
    }
    
    
    /*-------------------------------------------------------------------------*/
    // Quick function: DO DELETE
    /*-------------------------------------------------------------------------*/
        
	function do_delete( $table, $where="" )
	{
	    $this->build_and_exec_query( array( 'delete' => $table,
	                                        'where'  => $where ) );
	}    
	
    /*-------------------------------------------------------------------------*/
    // Simple elements
    /*-------------------------------------------------------------------------*/
    
    /**
	* Takes array of set commands and generates a SQL formatted query
	*
	* @param	array	Set commands (select, from, where, order, limit, etc)
	*/
	
    function build_query( $a )
    {
    	if ( isset($a['select']) && $a['select'] )
    	{
    		if ( isset($a['add_join']) && is_array( $a['add_join'] ) )
    		{
    			$this->simple_select_with_join( $a['select'], $a['from'], isset($a['where']) ? $a['where'] : '', $a['add_join'] );
    		}
    		else
    		{
    			$this->simple_select( $a['select'], $a['from'], isset($a['where']) ? $a['where'] : '' );
    		}
    	}
    	
    	if ( isset($a['update']) && $a['update'] )
    	{
    		$this->simple_update( $a['update'], $a['set'], isset($a['where']) ? $a['where'] : '', isset($a['lowpro']) ? $a['lowpro'] : '' );
    	}
    	
    	if ( isset($a['delete']) && $a['delete'] )
    	{
    		$this->simple_delete( $a['delete'], $a['where'] );
    	}
    	
    	if ( isset($a['group']) && $a['group'] )
    	{
    		$this->simple_group( $a['group'] );
    	}    	
    	
    	if ( isset($a['order']) && $a['order'] )
    	{
    		$this->simple_order( $a['order'] );
    	}
    	
    	if ( isset($a['limit']) && is_array( $a['limit'] ) )
    	{
    		$this->simple_limit( $a['limit'][0], $a['limit'][1] );
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // Build, execute and return the first row of a query
    /*-------------------------------------------------------------------------*/
    
    /**
	* Takes array of set commands and generates a SQL formatted query
	* and returns the first row automatically
	*
	* @param	array	Set commands (select, from, where, order, limit, etc)
	*
	* @return	array	Array from SQL server
	*/
	
    function build_and_exec_query( $a )
    {
    	$this->build_query( $a );

    	$ci = $this->exec_query();
    	
    	if ( isset($a['select']) AND $a['select'] )
    	{
    		return $this->fetch_row( $ci );
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // Load query from cache file
    /*-------------------------------------------------------------------------*/
    
    /**
	* Takes array of set commands and generates a SQL formatted query
	* and returns the first row automatically
	*
	* @param	string	Name of query file function to use
	* @param	array	Optional arguments to be parsed inside query function
	* @param	string	Optional class  name
	*/
	
    function build_query_from_cache( $q="", $args=array(), $method='sql_queries' )
    {
    	if ( $this->obj['query_cache_file'] and $method == 'sql_queries' )
    	{
    		$this->cur_query .= $this->sql->$q( $args );
    	}
    	else if ( in_array( $method, $this->loaded_classes ) )
    	{
    		$this->cur_query .= $this->$method->$q( $args );
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // SIMPLE: EXEC QUERY
    /*-------------------------------------------------------------------------*/
    
    /**
	* Executes stored SQL query
	*
	* @return	object	Query ID
	*/
	
    function exec_query()
    {
    	if ( $this->cur_query != "" )
    	{
    		$ci = $this->query( $this->cur_query );
    	}
    	
    	$this->cur_query   = "";
    	$this->is_shutdown = 0;
    	return $ci;
    }
    
    /*-------------------------------------------------------------------------*/
    // Exec shut down cache
    /*-------------------------------------------------------------------------*/
    
    /**
	* Executes stored SQL query
	*
	*/
	
    function exec_shutdown_query()
    {
    	if ( ! $this->obj['use_shutdown'] )
    	{
    		$this->is_shutdown = 1;
    		return $this->exec_query();
    	}
    	else
    	{
    		$this->obj['shutdown_queries'][] = $this->cur_query;
    		$this->cur_query = "";
    	}
    }
    
	/*-------------------------------------------------------------------------*/
    // Test to see if a field exists by forcing and trapping an error.
    // It ain't pretty, but it do the job don't it, eh?
    // Return 1 for exists, 0 for not exists and jello for the naked guy
    // Fun fact: The number of times I spelt 'field' as 'feild'in this part: 104
    /*-------------------------------------------------------------------------*/
    
    /**
	* Test to see whether a field exists in a table
	*
	* @param	string	Field name
	* @param	string	Table name
	*
	* @return	integer	Boolean (0,1)
	*/
	
    function field_exists($field, $table)
    {
		$this->return_die = 1;
		$this->error      = "";
		
		$this->simple_select( "COUNT($field) as count", $table );
		$this->simple_exec();
		
		$return = 1;
		
		if ( $this->failed )
		{
			$return = 0;
		}
		
		$this->error      = "";
		$this->return_die = 0;
		$this->error_no   = 0;
		$this->failed     = 0;
		
		return $return;
	}
	
    /*-------------------------------------------------------------------------*/
    // Return the amount of queries used
    /*-------------------------------------------------------------------------*/
    
    /**
	* Returns current number of parsed and run queries
	*
	* @return	integer	Value stored in var $query_count
	*/
	
    function get_query_cnt()
    {
        return $this->query_count;
    }
    
    
	/*-------------------------------------------------------------------------*/
	// Flushes the in memory query string
	/*-------------------------------------------------------------------------*/
	
	/**
	* Flushes the $this->cur_query string
	*
	* @return	void
	*/
	function flush_query()
	{
		$this->cur_query = "";
	}
	
    /*-------------------------------------------------------------------------*/
    // Basic error handler
    /*-------------------------------------------------------------------------*/
    
    /**
	* Prints SQL error message
	*
	* @param	string	Additional error message
	*
	* @return	integer	Optional, if var $return_die is set to 1
	*/
	
    function fatal_error($the_error="")
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$this->error    = $this->_get_error_string();
		$this->error_no = $this->_get_error_number();

    	if ( $this->return_die == 1 )
    	{
			$this->error  = ( $this->error == "" ? $the_error : $this->error );
    		$this->failed = 1;
    		return;
    	}
     	else if ( $this->obj['use_error_log'] AND $this->obj['error_log'] )
		{
			$_error_string  = "\n===================================================";
			$_error_string .= "\n Date: ". date( 'r' );
			$_error_string .= "\n Error Number: " . $this->error_no;
			$_error_string .= "\n Error: " . $this->error;
			$_error_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
			$_error_string .= "\n Page: " . $_SERVER['REQUEST_URI'];
			$_error_string .= "\n ".$the_error;
			
			if ( $FH = @fopen( $this->obj['error_log'], 'a' ) )
			{
				@fwrite( $FH, $_error_string );
				@fclose( $FH );
			}
			
			print "<html><head><title>IPS Driver Error</title>
					<style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
		    		   <blockquote><h1>IPS Driver Error</h1><b>There appears to be an error with the database.</b><br>
		    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>
				  </body></html>";
		}
		else
		{
    		$the_error .= "\n\nSQL error: ".$this->error."\n";
	    	$the_error .= "SQL error code: ".$this->error_no."\n";
	    	$the_error .= "Date: ".date("l dS \o\f F Y h:i:s A");
    	
	    	$out = "<html><head><title>IPS Driver Error</title>
	    		   <style>P,BODY{ font-family:arial,sans-serif; font-size:11px; }</style></head><body>
	    		   <blockquote><h1>IPS Driver Error</h1><b>There appears to be an error with the database.</b><br>
	    		   You can try to refresh the page by clicking <a href=\"javascript:window.location=window.location;\">here</a>.
	    		   <br><br><b>Error Returned</b><br>
	    		   <form name='mysql'><textarea rows=\"15\" cols=\"60\">".htmlspecialchars($the_error)."</textarea></form><br>We apologise for any inconvenience</blockquote></body></html>";
    		   
    
	       	print $out;
		}
		
        exit();
    }
    
    /*-------------------------------------------------------------------------*/
    // Create an array from a multidimensional array returning formatted
    // strings ready to use in an INSERT query, saves having to manually format
    // the (INSERT INTO table) ('field', 'field', 'field') VALUES ('val', 'val')
    /*-------------------------------------------------------------------------*/
    
    /**
	* Compiles SQL formatted insert fields
	*
	* @param	array	Array of field => value pairs
	*
	* @return	array	FIELD_NAMES (string) FIELD_VALUES (string)
	*/
	
    function compile_db_insert_string($data)
    {
    	$field_names  = "";
		$field_values = "";
		
		foreach ($data as $k => $v)
		{
			$add_slashes = 1;
			
			if ( $this->manual_addslashes )
			{
				$add_slashes = 0;
			}
			
			if ( isset($this->no_escape_fields[ $k ]) AND $this->no_escape_fields[ $k ] )
			{
				$add_slashes = 0;
			}
			
			if ( $add_slashes )
			{
				$v = $this->add_slashes( $v );
			}
			
			$field_names  .= "$k,";
			
			//-----------------------------------------
			// Forcing data type?
			//-----------------------------------------
			
			if ( isset($this->force_data_type[ $k ]) AND $this->force_data_type[ $k ] )
			{
				if ( $this->force_data_type[ $k ] == 'string' )
				{
					$field_values .= "'$v',";
				}
				else if ( $this->force_data_type[ $k ] == 'int' )
				{
					$field_values .= intval($v).",";
				}
				else if ( $this->force_data_type[ $k ] == 'float' )
				{
					$field_values .= floatval($v).",";
				}
			}
			
			//-----------------------------------------
			// No? best guess it is then..
			//-----------------------------------------
			
			else
			{
				if ( is_numeric( $v ) and intval($v) == $v )
				{
					$field_values .= $v.",";
				}
				else
				{
					$field_values .= "'$v',";
				}
			}
		}
		
		$field_names  = preg_replace( "/,$/" , "" , $field_names  );
		$field_values = preg_replace( "/,$/" , "" , $field_values );
		
		return array( 'FIELD_NAMES'  => $field_names,
					  'FIELD_VALUES' => $field_values,
					);
	}
	
	
	
	/*-------------------------------------------------------------------------*/
    // Create an array from a multidimensional array returning a formatted
    // string ready to use in an UPDATE query, saves having to manually format
    // the FIELD='val', FIELD='val', FIELD='val'
    /*-------------------------------------------------------------------------*/
    
    /**
	* Compiles SQL formatted update fields
	*
	* @param	array	Array of field => value pairs
	*
	* @return	string	SET .... update string
	*/
	
    function compile_db_update_string($data)
    {
		$return_string = "";
		
		foreach ($data as $k => $v)
		{
			//-----------------------------------------
			// Adding slashes?
			//-----------------------------------------
			
			$add_slashes = 1;
			
			if ( $this->manual_addslashes )
			{
				$add_slashes = 0;
			}
			
			if ( isset($this->no_escape_fields[ $k ]) && $this->no_escape_fields[ $k ] )
			{
				$add_slashes = 0;
			}
			
			if ( $add_slashes )
			{
				$v = $this->add_slashes( $v );
			}
			
			//-----------------------------------------
			// Forcing data type?
			//-----------------------------------------
			
			if ( isset($this->force_data_type[ $k ]) && $this->force_data_type[ $k ] )
			{
				if ( $this->force_data_type[ $k ] == 'string' )
				{
					$return_string .= $k . "='".$v."',";
				}
				else if ( $this->force_data_type[ $k ] == 'int' )
				{
					$return_string .= $k . "=".intval($v).",";
				}
				else if ( $this->force_data_type[ $k ] == 'float' )
				{
					$return_string .= $k . "=".floatval($v).",";
				}
			}
			
			//-----------------------------------------
			// No? best guess it is then..
			//-----------------------------------------
			
			else
			{
				if ( is_numeric( $v ) and intval($v) == $v )
				{
					$return_string .= $k . "=".$v.",";
				}
				else
				{
					$return_string .= $k . "='".$v."',";
				}
			}
		}
		
		$return_string = preg_replace( "/,$/" , "" , $return_string );
		
		return $return_string;
	}
	
	/*-------------------------------------------------------------------------*/
	// Use different escape method for different SQL engines
	/*-------------------------------------------------------------------------*/
	
	function add_slashes( $t )
	{
		# Driver specific
	}
	
	/*-------------------------------------------------------------------------*/
	// Use different escape method for different SQL engines
	/*-------------------------------------------------------------------------*/
	
	function remove_slashes( $t )
	{
		# Driver specific
	}
	
	/*-------------------------------------------------------------------------*/
	// Removes quotes from a DB query
	/*-------------------------------------------------------------------------*/
	
	function remove_all_quotes( $t )
	{
		//-----------------------------------------
		// Remove empty comments
		// We now check for comments in SQL classes
		//-----------------------------------------
		
		#$t = preg_replace( "#/\*(.??)\*/#s", " ", $t );
		#$t = preg_replace( "#/\*.+?\*/#s"  , " ", $t );
		
		//-----------------------------------------
		// Remove quotes
		//-----------------------------------------
		
		$t = preg_replace( "#\\\{1,}[\"']#s", "", $t );
		$t = preg_replace( "#'[^']*'#s"    , "", $t );
		$t = preg_replace( "#\"[^\"]*\"#s" , "", $t );
		$t = preg_replace( "#\"\"#s"        , "", $t );
		$t = preg_replace( "#''#s"          , "", $t );
		#print $t;
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
    // Basic error handler
    /*-------------------------------------------------------------------------*/
    
    /**
	* Prints SQL error message
	*
	* @param	string	Additional error message
	*
	* @return	integer	Optional, if var $return_die is set to 1
	*/
	
    function write_debug_log( $query, $data, $endtime )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		if ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] )
		{
			$_string  = "\n==============================================================================";
			$_string .= "\n Date: ". date( 'r' );
			$_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
			$_string .= "\n Time Taken: ".$endtime;
			$_string .= "\n ".$query;
			$_string .= "\n==============================================================================";
			$_string .= "\n".$data;
			
			if ( $FH = @fopen( $this->obj['debug_log'], 'a' ) )
			{
				@fwrite( $FH, $_string );
				@fclose( $FH );
			}
		}
    }

	/*-------------------------------------------------------------------------*/
    // INTERNAL: Get error number
    /*-------------------------------------------------------------------------*/
    
    function _get_error_number()
    {
    	# Driver specific
    }
    
    /*-------------------------------------------------------------------------*/
    // INTERNAL: Get error number
    /*-------------------------------------------------------------------------*/
    
    function _get_error_string()
    {
    	# Driver specific
    }
    
    /*-------------------------------------------------------------------------*/
	// Internal: CHECK AND SET PREFIX
	/*-------------------------------------------------------------------------*/
	
	/**
	* Set SQL table prefix
	*
	*/
	
	function _set_prefix()
	{
		if ( ! defined( 'SQL_PREFIX' ) )
     	{
     		$this->obj['sql_tbl_prefix'] = isset($this->obj['sql_tbl_prefix']) ? $this->obj['sql_tbl_prefix'] : 'ibf_';
     		
     		define( 'SQL_PREFIX', $this->obj['sql_tbl_prefix'] );
     	}
	}
	
	/*-------------------------------------------------------------------------*/
	// Internal: CHECK AND LOAD CACHE FILE
	/*-------------------------------------------------------------------------*/
	
	/**
	* Load cache file
	*
	*/
	
	function _load_cache_file()
	{
		if ( $this->obj['query_cache_file'] )
     	{
     		require_once( $this->obj['query_cache_file'] );
     	
			$sql_queries_name = $this->sql_queries_name ? $this->sql_queries_name : 'sql_queries';

     		$this->sql = new $sql_queries_name( $this );
     	}
	}
    
}


?>