<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Services Kernel [DB Tools]
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
+---------------------------------------------------------------------------
|   THIS IS NOT FREE / OPEN SOURCE SOFTWARE
+---------------------------------------------------------------------------
|   > $Date: 2007-10-18 15:46:56 -0400 (Thu, 18 Oct 2007) $
|   > $Revision: 1137 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > MySQL DB Tools
|   > Module written by Remco Wilting
|   > Date started: Tuesday 1st March 2005 15:40
|
|	> Module Version Number: 2.1.001
+--------------------------------------------------------------------------
*/

class db_tools
{
	var $ipsclass;
	
	var $has_issues		= 0;
		
	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/
	function db_tools( & $ipsclass )
	{
		$this->ipsclass = & $ipsclass;
	}
	
	//-------------------------------------------------------------
	// Diagnose the table indexes
	// @input	sql_statements		array of create table/index statements to check
	// @output	array				array of results ( table, index, status, message, fixsql )
	//-------------------------------------------------------------

	function db_index_diag( & $sql_statements )
	{
		$indexes = array();
		$error_count = 0;
		if( is_array($sql_statements) && count($sql_statements) )
		{
			foreach( $sql_statements as $definition )
			{
				$table_name  	= "";
				$fields_str  	= "";
				$primary_key 	= "";
				$tablename   	= array();
				$fields		 	= array();
				$final_keys  	= array();
				$col_definition = "";
				$colmatch	 	= array();
				$final_primary 	= array();
				
		        if ( preg_match( "#CREATE TABLE\s+?(.+?)\s+?\(#ie", $definition, $tablename ) )
		        {
			        $table_name = $tablename[1];
			        if ( preg_match( "#\s+?PRIMARY\s+?KEY\s+?\((.*?)\)(?:(?:[,\s+?$])?\((.+?)\))?#is", $definition, $fields ) )
			        {
			        	$final_primary = array();
				        if( count( $fields ) )
				        {
					        $primary_key = trim($fields[1]);
					        
					        $col_definition = $this->sql_strip_ticks( $definition );
					        
					        if ( preg_match( "#^\s+?{$primary_key}\s+?(.+?)(?:[,$])#im", $col_definition, $colmatch ) )
					        {
					        	$col_definition = trim($colmatch[1]);
					        }
					        $final_primary = array( $primary_key, $col_definition );
	            		}
			        }
					$table_array[$i] = $table_name;
					if ( count( $final_primary ) )
					{
						$primary_array[$i] = $final_primary;
					}
			    }
		        				
		        if ( preg_match_all( "#(?<!PRIMARY)\s+?KEY\s+?(?:(\w+?)\s+?)?\((.*?)\)(?:(?:[,\s+?$])?\((.+?)\))?#is", $definition, $fields, PREG_PATTERN_ORDER ) )
		        {
			        if( count( $fields[2] ) )
			        {
				        $i = 0;
				        
				        foreach( $fields[2] as $index_cols )
				        {
		            		$index_cols = trim( $this->sql_strip_ticks( $index_cols ) );
		            		
		            		$index_name = $fields[1][$i] ? $fields[1][$i] : $index_cols;
		            		
		            		$final_keys[] = array( $index_name, implode( ",", array_map( 'trim', explode( ",", $index_cols ) ) ) );
		            		
		            		$i++;
	            		}
            		}
		        }

			    if( $table_name AND ( $primary_key OR count($final_keys) ) )
			    {
				    $indexes[] = array( 'table' 	=> $table_name,
				    					'primary'	=> $final_primary,
				    					'index'		=> $final_keys
				    				  );
			    }
		    }
	    }

	    if( !count($indexes) )
	    {
		   return false; 
		}
		
		$output = array();
	    
		foreach( $indexes as $data )
		{
			$table_name = str_replace( "ibf_", "", $data['table']);
			
			$row = $this->ipsclass->DB->sql_get_table_schematic( $table_name );
		
			$tbl = $this->sql_strip_ticks( $row['Create Table'] );
			
			if( isset( $data['primary'] ) && is_array($data['primary']) AND count($data['primary']) )
			{
				$index_name 		= $data['primary'][0];
				$column_definition	= $data['primary'][1];
				
				if ( preg_match( "#\s+?PRIMARY\s+?KEY\s+?\({$index_name}\)?[,\s+?$]?#is", $tbl, $match ) )
				{
					$output[] = array( 'table'		=> SQL_PREFIX.$table_name,
									   'index'		=> $index_name,
									   'status'		=> 'ok',
									   'message'	=> '',
									   'fixsql'		=> '' );
				}
				else
				{
					$query_needed = "ALTER TABLE ".SQL_PREFIX."$table_name CHANGE {$index_name} {$index_name} {$column_definition}, ADD PRIMARY KEY ({$index_name})";
					
					if( !$this->ipsclass->DB->field_exists( $index_name, $table_name ) )
					{
						$query_needed = str_replace( "CHANGE {$index_name}", "ADD", $query_needed );
					}

					$this->has_issues = 1;
					
					$output[] = array( 'table'		=> SQL_PREFIX.$table_name,
									   'index'		=> $index_name,
									   'status'		=> 'error',
									   'message'	=> 'Missing primary key',
									   'fixsql'		=> $query_needed );
					$error_count++;
				}
			}
		
			if ( isset( $data['index'] ) && is_array( $data['index'] ) and count( $data['index'] ) )
			{
				foreach( $data['index'] as $indexes )
				{
					$index_name = $indexes[0];
					$index_cols = $indexes[1] ? $indexes[1] : $index_name;
					$ok         = 0;
					
					if ( preg_match( "#(?<!PRIMARY)\s+?KEY\s+?{$index_name}\s+?(\((.+?)\))?#is", $tbl, $match ) )
					{
						$ok = 1;
		
						//-----------------------------------------
						// Multi index column?
						//-----------------------------------------
		
						if ( $index_cols != $table_cols )
						{
							foreach( explode( ',', $indexes[1] ) as $mc )
							{
								if ( ! strstr( $match[2], $mc ) )
								{
									$this->has_issues = 1;
									
									$output[] = array( 'table'		=> SQL_PREFIX.$table_name,
													   'index'		=> $index_name,
													   'status'		=> 'error',
													   'message'	=> 'Missing field \''.$mc.'\'',
													   'fixsql'		=> 'ALTER TABLE '.SQL_PREFIX.$table_name.' DROP INDEX '.$index_name.', ADD INDEX '.$index_name.' ('.$index_cols.')' );
									$error_count++;
									$ok       = 0;
								}
							}
						}
					}
					else
					{
						$output[] = array( 'table'		=> SQL_PREFIX.$table_name,
										   'index'		=> $index_name,
										   'status'		=> 'error',
										   'message'	=> 'Missing index \''.$index_name.'\'',
										   'fixsql'		=> 'ALTER TABLE '.SQL_PREFIX.$table_name.' ADD INDEX '.$index_name.'('.$index_cols.')' );
						$error_count++;
					}
		
					if ( $ok )
					{
						$output[] = array( 'table'		=> SQL_PREFIX.$table_name,
										   'index'		=> $index_name,
										   'status'		=> 'ok',
										   'message'	=> '',
										   'fixsql'		=> '' );
					}
				}
			}
		}
		
		return array( 'error_count'	=> $error_count, 'results' => $output );
	}


	//-------------------------------------------------------------
	// Diagnose the DB tables
	// @input	sql_statements		array of create table statements to check
	// @output	array				array of results ( key, table, status, message, fixsql )
	//-------------------------------------------------------------

	function db_table_diag( & $sql_statements )
	{
		$queries_needed = array();
		$tables_needed = array();
		$error_count = 0;
				
		if( is_array( $sql_statements ) && count( $sql_statements ) )
		{
			foreach( $sql_statements as $the_table )
			{
				$expected_columns = array();
			
				if( preg_match("#CREATE TABLE\s+?(.+?)\s+?\(#ie", $the_table, $bits))
				{
					$tbl_name = $bits[1];
					$tbl_name = str_replace( "ibf_", "", $tbl_name );
					
					$table_defs[$tbl_name] = str_replace( "ibf_", SQL_PREFIX, $the_table );
					
					// Get the columns and lose the first line (it's the table name)
					$columns_array = explode( "\n", $the_table );
					array_shift($columns_array);
					
					// Get rid of the end junk
					if ( (strpos(end($columns_array), ");") == 0) || 
						 (strpos(end($columns_array), ")") == 0)  ||
						 (strpos(end($columns_array), ";") == 0) )
					{
						array_pop($columns_array);
					}
					
					reset($columns_array);
					
					foreach( $columns_array as $col )
					{
						$temp = preg_split( "/[\s]+/" , $col );
						$col_name = trim( next( $temp ) );
						
						if( $col_name != "PRIMARY" && 
							$col_name != "KEY" && 
							$col_name != "UNIQUE" && 
							$col_name != "" && 
							$col_name != "(" && 
							$col_name != ";" &&
							$col_name != ");" )
						{
							$expected_columns[] = $col_name;
							$this->columns_to_defs[$tbl_name][$col_name] = trim( str_replace( ',', ';', $col ) );
						}
					}
				}
				elseif ( preg_match("#ALTER TABLE ([a-z_]*) ADD ([a-z_]*) #is", $the_table, $bits) )
				{
					if( $bits[1] != "" && 
						$bits[2] != "" && 
						$bits[2] != 'INDEX' && 
						strpos($bits[2], 'TYPE') === false )
					{
						$tbl_name = trim($bits[1]);
						$tbl_name = str_replace( "ibf_", "", $tbl_name );
						$col_name = trim($bits[2]);
						
						$expected_columns[] = $col_name;
						$this->columns_to_defs[$tbl_name][$col_name] = str_replace( $bits[1], SQL_PREFIX . $tbl_name, $the_table ) . ";";
					}
				}
				else
				{
					continue;
				}
					
				// Get the current schema....
				$this->ipsclass->DB->return_die = 1;
	
				if ( ! $this->ipsclass->DB->table_exists( $tbl_name ) )
				{
					$output[] = array( 'key'		=> $tbl_name,
									   'table'		=> SQL_PREFIX.$tbl_name,
									   'status'		=> 'error',
									   'message'	=> 'missing table',
									   'fixsql'		=> $table_defs[$tbl_name] );
					$error_count++;
					$this->ipsclass->DB->failed = 0;
				}
				else
				{
					// Here we go...
					$missing 		= array();

					foreach( $expected_columns as $trymeout )
					{
						if( ! $this->ipsclass->DB->field_exists( $trymeout, $tbl_name ) )
						{
							$missing[] 		= $trymeout;
							$query_needed 	= "ALTER TABLE " . SQL_PREFIX . $tbl_name . " ADD " . $this->columns_to_defs[$tbl_name][$trymeout];

							if( preg_match( "/auto_increment;/", $query_needed ) )
							{
								$query_needed = substr( $query_needed, 0, -1 ).", ADD PRIMARY KEY( ". $trymeout . ");";
							}
							
							$output[] = array( 'key'		=> $tbl_name.$trymeout,
											   'table'		=> SQL_PREFIX.$tbl_name,
											   'status'		=> 'error',
											   'message'	=> 'Missing column \''.$trymeout.'\'',
											   'fixsql'		=> $query_needed );
							$error_count++;
						}
					}
					if( !count( $missing ) )
					{
						$output[] = array( 'key'		=> $tbl_name,
										   'table'		=> SQL_PREFIX.$tbl_name,
										   'status'		=> 'ok',
										   'message'	=> '',
										   'fixsql'		=> '' );
					}
				}
			}
		}
		
		return array( 'error_count'	=> $error_count, 'results' => $output );
	}	


	//-----------------------------------------
	// sql_strip_ticks from field names
	//-----------------------------------------
	
	function sql_strip_ticks($data)
	{
		return str_replace( "`", "", $data );
	}

}

?>