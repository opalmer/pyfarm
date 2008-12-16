<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2007-08-31 10:37:25 -0400 (Fri, 31 Aug 2007) $
|   > $Revision: 1100 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > mySQL Admin Stuff
|   > Module written by Matt Mecham
|   > Date started: 21st October 2002
|
|	> Module Version Number: 1.0.0
|   > Music listen to when coding this: Martin Grech - Open Heart Zoo
|   > Talk about useless information!
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

@set_time_limit(1200);


class ad_sql_module {

	var $base_url;
	var $mysql_version   = "";
	var $true_version    = "";
	var $str_gzip_header = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
	
	var $db_has_issues	 = false;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "admin";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "sql";
	
	/*-------------------------------------------------------------------------*/
	// Auto run module
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'SQL Toolbox' );
		
		//-----------------------------------------
		// Make sure we're a root admin, or else!
		//-----------------------------------------
		
		if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
		{
			$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}
		
		//-----------------------------------------
		// Get the mySQL version.
		//-----------------------------------------
		
		$this->ipsclass->DB->sql_get_version();
		
		$this->true_version  = $this->ipsclass->DB->true_version;
   		$this->mysql_version = $this->ipsclass->DB->mysql_version;
   		
		switch($this->ipsclass->input['code'])
		{
			case 'dotool':
				$this->run_tool();
				break;
				
			case 'runtime':
				$this->view_sql("SHOW STATUS");
				break;
				
			case 'system':
				$this->view_sql("SHOW VARIABLES");
				break;
				
			case 'processes':
				$this->view_sql("SHOW PROCESSLIST");
				break;
				
			case 'runsql':
				$_POST['query'] = isset($_POST['query']) ? $_POST['query'] : '';
				$q = $_POST['query'] == "" ? urldecode($_GET['query']) : $_POST['query'];
				$this->view_sql(trim(stripslashes($q)));
				break;
			
			case 'backup':
				$this->show_backup_form();
				break;
				
			case 'safebackup':
				$this->sbup_splash();
				break;
				
			case 'dosafebackup':
				$this->do_safe_backup();
				break;
				
			case 'export_tbl':
				$this->do_safe_backup(trim(urldecode(stripslashes($_GET['tbl']))));
				break;
			
			//-----------------------------------------
			default:
				$this->list_index();
				break;
		}
	}
	
	//-----------------------------------------
	// Back up baby, back up
	//-----------------------------------------
	
	function do_safe_backup($tbl_name="")
	{
		if ($tbl_name == "")
		{
			// Auto all tables
			$skip        = intval($this->ipsclass->input['skip']);
			$create_tbl  = intval($this->ipsclass->input['create_tbl']);
			$enable_gzip = intval($this->ipsclass->input['enable_gzip']);
			$filename    = 'ibf_dbbackup';
		}
		else
		{
			// Man. click export
			$skip        = 0;
			$create_tbl  = 0;
			$enable_gzip = 1;
			$filename    = $tbl_name;
		}
		
		$output = "";
		
		@header("Pragma: no-cache");
		
		$do_gzip = 0;
		
		if( $enable_gzip )
		{
			$phpver = phpversion();

			if($phpver >= "4.0")
			{
				if(extension_loaded("zlib"))
				{
					$do_gzip = 1;
				}
			}
		}
		
		if( $do_gzip != 0 )
		{
			@ob_start();
			@ob_implicit_flush(0);
			header("Content-Type: text/x-delimtext; name=\"$filename.sql.gz\"");
			header("Content-disposition: attachment; filename=$filename.sql.gz");
		}
		else
		{
			header("Content-Type: text/x-delimtext; name=\"$filename.sql\"");
			header("Content-disposition: attachment; filename=$filename.sql");
		}
		
		//-----------------------------------------
		// Get tables to work on
		//-----------------------------------------
		
		if ($tbl_name == "")
		{
			$tmp_tbl = $this->ipsclass->DB->get_table_names();
				
			foreach($tmp_tbl as $tbl)
			{
				// Ensure that we're only peeking at IBF tables
				
				if ( preg_match( "/^".$this->ipsclass->vars['sql_tbl_prefix']."/", $tbl ) )
				{
					// We've started our headers, so print as we go to stop
					// poss memory problems
					
					$this->get_table_sql($tbl, $create_tbl, $skip);
				}
			}
		}
		else
		{
			$this->get_table_sql($tbl_name, $create_tbl, $skip);
		}
		
		//-----------------------------------------
		// GZIP?
		//-----------------------------------------
		
		if($do_gzip)
		{
			$size     = ob_get_length();
			$crc      = crc32(ob_get_contents());
			$contents = gzcompress(ob_get_contents());
			ob_end_clean();
			echo $this->str_gzip_header
				.substr($contents, 0, strlen($contents) - 4)
				.$this->gzip_four_chars($crc)
				.$this->gzip_four_chars($size);
		}
		
		exit();
	}
	
	//-----------------------------------------
	// Internal handler to return content from table
	//-----------------------------------------
	
	function get_table_sql($tbl, $create_tbl, $skip=0)
	{
		if ($create_tbl)
		{
			// Generate table structure
			
			if ( $this->ipsclass->input['addticks'] )
			{
				$this->ipsclass->DB->query("SHOW CREATE TABLE `".$this->ipsclass->vars['sql_database'].".".$tbl."`");
			}
			else
			{
				$this->ipsclass->DB->query("SHOW CREATE TABLE ".$this->ipsclass->vars['sql_database'].".".$tbl);
			}
			
			$ctable = $this->ipsclass->DB->fetch_row();
			
			echo $this->sql_strip_ticks($ctable['Create Table']).";\n";
		}
		
		// Are we skipping? Woohoo, where's me rope?!
		
		if ($skip == 1)
		{
			if ($tbl == $this->ipsclass->vars['sql_tbl_prefix'].'admin_sessions'
				OR $tbl == $this->ipsclass->vars['sql_tbl_prefix'].'sessions'
				OR $tbl == $this->ipsclass->vars['sql_tbl_prefix'].'reg_anti_spam'
				OR $tbl == $this->ipsclass->vars['sql_tbl_prefix'].'search_results'
			   )
			{
				return $ret;
			}
		}
		
		// Get the data
		
		$this->ipsclass->DB->query("SELECT * FROM $tbl");
		
		// Check to make sure rows are in this
		// table, if not return.
		
		$row_count = $this->ipsclass->DB->get_num_rows();
		
		if ($row_count < 1)
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// Get col names
		//-----------------------------------------
		
		$f_list = "";
	
		$fields = $this->ipsclass->DB->get_result_fields();
		
		$cnt = count($fields);
		
		for( $i = 0; $i < $cnt; $i++ )
		{
			$f_list .= $fields[$i]->name . ", ";
		}
		
		$f_list = preg_replace( "/, $/", "", $f_list );
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Get col data
			//-----------------------------------------
			
			$d_list = "";
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				if ( ! isset($row[ $fields[$i]->name ]) )
				{
					$d_list .= "NULL,";
				}
				elseif ( $row[ $fields[$i]->name ] != '' )
				{
					$d_list .= "'".$this->sql_add_slashes($row[ $fields[$i]->name ]). "',";
				}
				else
				{
					$d_list .= "'',";
				}
			}
			
			$d_list = preg_replace( "/,$/", "", $d_list );
			
			echo "INSERT INTO $tbl ($f_list) VALUES($d_list);\n";
		}
		
		return TRUE;
		
	}
	
	//-----------------------------------------
	// sql_strip_ticks from field names
	//-----------------------------------------
	
	function sql_strip_ticks($data)
	{
		return str_replace( "`", "", $data );
	}
	
	//-----------------------------------------
	// Add slashes to single quotes to stop sql breaks
	//-----------------------------------------
	
	function sql_add_slashes($data)
	{
		$data = str_replace('\\', '\\\\', $data);
        $data = str_replace('\'', '\\\'', $data);
        $data = str_replace("\r", '\r'  , $data);
        $data = str_replace("\n", '\n'  , $data);
        
        return $data;
	}
	
	//-----------------------------------------
	// Almost there!
	//-----------------------------------------
	
	function sbup_splash()
	{
		$this->ipsclass->admin->page_detail = "This section allows you to backup your database.";
		$this->ipsclass->admin->nav[] = array( '', 'MySQL Database Backup' );
		$this->ipsclass->admin->page_title  = "mySQL ".$this->true_version." Back Up";
		
		// Check for mySQL version..
		// Might change at some point..
		
		if ( $this->mysql_version < 3232 )
		{
			$this->ipsclass->admin->error("Sorry, mySQL version of less than 3.23.21 are not support by this backup utility");
		}
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Simple Back Up" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
													"<b>Back Up mySQL Database</b><br><br>Once you have clicked the link below, please wait
													until your browser prompts you with a dialogue box. This may take some time depending on
													the size of the database you are backing up.
													<br><br>
													<b><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=dosafebackup&create_tbl={$this->ipsclass->input['create_tbl']}&addticks={$this->ipsclass->input['addticks']}&skip={$this->ipsclass->input['skip']}&enable_gzip={$this->ipsclass->input['enable_gzip']}'>Click here to start the backup</a></b>"
									     )      );
									     
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		$this->ipsclass->admin->output();
		
	
	}
	
	
	function show_backup_form()
	{
		$this->ipsclass->admin->nav[] = array( '', 'MySQL Database Backup' );
		
		$this->ipsclass->admin->page_detail = "This section allows you to backup your database.
							  <br><br><b>Simple Backup</b>
							  <br>This function compiles a single back up file and prompts a browser dialogue box for you to save 
							  the file. This is beneficial for PHP safe mode enabled hosts, but can only be used on small databases.
							  <!--<br><br>
							  <b>Advanced Backup</b>
							  <br>This function allows you to split the backup into smaller sections and saves the backup to disk.
							  <br>Note, this can only be used if you do not have PHP safe mode enabled.-->";

		$this->ipsclass->admin->page_title  = "mySQL ".$this->true_version." Back Up";
		
		// Check for mySQL version..
		// Might change at some point..
		
		if ( $this->mysql_version < 3232 )
		{
			$this->ipsclass->admin->error("Sorry, mySQL version of less than 3.23.21 are not support by this backup utility");
		}
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
			
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'sql' ),
																			 2 => array( 'code' , 'safebackup'),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
								   
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Simple Back Up" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
													"<b>Add 'CREATE TABLE' statements?</b><br>Add backticks around the table name?<br>(if you get a mySQL error, enable this) <input type='checkbox' name='addticks' value=1>",
													$this->ipsclass->adskin->form_yes_no( 'create_tbl', 1),
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
													"<b>Skip non essential data?</b><br>Will not produce insert rows for ibf_sessions, ibf_admin_sessions, ibf_search_results, ibf_reg_anti_spam.",
													$this->ipsclass->adskin->form_yes_no( 'skip', 1),
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
													"<b>GZIP Content?</b><br>Will produce a smaller file if GZIP is enabled.",
													$this->ipsclass->adskin->form_yes_no( 'enable_gzip', 0 ),
									     )      );
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Start Back Up");
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		$this->ipsclass->admin->output();
	}
	
	
	//-----------------------------------------
	// Run mySQL queries
	//-----------------------------------------
	
	
	function view_sql($sql)
	{
		$limit = 50;
		$start = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$pages = "";
		
		$this->ipsclass->admin->page_detail = "This section allows you to administrate your mySQL database.";
		$this->ipsclass->admin->page_title  = "mySQL ".$this->true_version." Tool Box";
		
		$map = array( 'processes' 	=> "SQL Processes",
					  'runtime'   	=> "SQL Runtime Information",
					  'system'    	=> "SQL System Variables",
					);
					
		if ( isset($map[ $this->ipsclass->input['code'] ]) AND $map[ $this->ipsclass->input['code'] ] != "" )
		{
			$tbl_title = $map[ $this->ipsclass->input['code'] ];
			$man_query = 0;
		}
		else
		{
			$tbl_title = "Manual Query";
			$man_query = 1;
		}
		
		//-----------------------------------------
		
		if ($man_query == 1)
		{
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'sql' ),
											      2 => array( 'code' , 'runsql'),
											      4 => array( 'section', $this->ipsclass->section_code ),
										 )      );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Run Query" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<center>".$this->ipsclass->adskin->form_textarea("query", $sql )."</center>" ) );
													 
			$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run a New Query");
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->return_die = 1;
		
		$the_queries = array();
		
		if( strstr( $sql, ";" ) )
		{
			$the_queries = preg_split( "/;[\r\n|\n]+/", $sql, -1, PREG_SPLIT_NO_EMPTY );
		}
		else
		{
			$the_queries[] = $sql;
		}
		
		if( !count($the_queries) )
		{
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Error" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array("No valid queries were found") );
		
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
			
			$this->ipsclass->admin->output(); // End output and script
		}
		
		foreach( $the_queries as $sql )
		{
			$links 	= "";
			$sql 	= trim($sql);
			// Check for drop, create and flush
			
			$test_sql = str_replace( "\'", "", $sql );
			$apos_count = substr_count( $test_sql, "'" );
			
			if( $apos_count%2 != 0 )
			{
				$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Error" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array("This query appears to be invalid: {$sql}") );
			
				$this->ipsclass->html .= $this->ipsclass->adskin->end_table();	
				
				unset( $apos_count, $test_sql );
				continue;
			}
			
			unset( $apos_count, $test_sql );
			
			if ( preg_match( "/^(DROP|FLUSH)/i",$sql ) )
			{
				$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Error" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array("Sorry, those queries are not allowed for your safety") );
			
				$this->ipsclass->html .= $this->ipsclass->adskin->end_table();	
				
				continue;			
			}
			else if ( preg_match( "/^(?!SELECT)/i", preg_replace( "#\s{1,}#s", "", $sql ) ) and preg_match( "/admin_login_logs/i", preg_replace( "#\s{1,}#s", "", $sql ) ) )
			{
				$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Error" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array("Sorry, you can't delete from or update that table") );
			
				$this->ipsclass->html .= $this->ipsclass->adskin->end_table();	
				
				continue;			
			}
					
			$this->ipsclass->DB->error = "";
				
			$this->ipsclass->DB->allow_sub_select = 1;
			
			$this->ipsclass->DB->query($sql,1);
			
			// Check for errors..
			
			if ( $this->ipsclass->DB->error != "")
			{
				$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "SQL Error" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array($this->ipsclass->DB->error) );
			
				$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
				
				continue;
				
			}
			
			if ( preg_match( "/^(INSERT|UPDATE|DELETE|ALTER|TRUNCATE|CREATE|REPLACE INTO)/i", $sql ) )
			{
				// We can't show any info, and if we're here, there isn't
				// an error, so we're good to go.
				
				$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "SQL Query Completed" );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array("Query: ".htmlspecialchars($sql)."<br>Executed Successfully") );
			
				$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
				
				continue;
				
			}
			else if ( preg_match( "/^SELECT/i", $sql ) )
			{
				// Sort out the pages and stuff
				// auto limit if need be
				
				if ( ! preg_match( "/LIMIT[ 0-9,]+$/i", $sql ) )
				{
					$rows_returned = $this->ipsclass->DB->get_num_rows();
				
					if ($rows_returned > $limit)
					{
						$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $rows_returned,
															   'PER_PAGE'    => $limit,
															   'CUR_ST_VAL'  => $start,
															   'L_SINGLE'    => "Single Page",
															   'L_MULTI'     => "Pages: ",
															   'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=runsql&query=".urlencode($sql),
															 )
													  );
													  
						if( substr( $sql, -1, 1 ) == ";" )
						{
							$sql = substr( $sql, 0, -1 );
						}
						
						$sql .= " LIMIT $start, $limit";
					
						// Re-run with limit
						
						$this->ipsclass->DB->query($sql, 1); /// bypass table swapping
					}
				}
				
			}
			
			$fields = $this->ipsclass->DB->get_result_fields();
			
			$cnt = count($fields);
			
			// Print the headers - we don't know what or how many so...
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				$this->ipsclass->adskin->td_header[] = array( $fields[$i]->name , "*" );
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Result: ".$tbl_title );
			
			if ($links != "")
			{
				$pages = $this->ipsclass->adskin->add_td_basic( $links, 'left', 'tablerow2' );
			
				$this->ipsclass->html .= $pages;
			}
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				// Grab the rows - we don't what or how many so...
				
				$rows = array();
				
				for( $i = 0; $i < $cnt; $i++ )
				{
					if ($man_query == 1)
					{
						// Limit output
						if ( strlen($r[ $fields[$i]->name ]) > 200 AND !preg_match( "/^SHOW/i", $sql ) )
						{
							$r[ $fields[$i]->name ] = substr($r[ $fields[$i]->name ], 0, 200) .'...';
						}
					}
					
					$rows[] = nl2br( htmlspecialchars( wordwrap( $r[ $fields[$i]->name ] , 50, "\n", 1 ) ) );
				}
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( $rows );
			
			}
		
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
			
			$this->ipsclass->DB->free_result();
		}
		
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
		
		
	}
	
	//-----------------------------------------
	// I'm A TOOL!
	//-----------------------------------------
	
	function run_tool()
	{
		$this->ipsclass->admin->page_detail = "This section allows you to administrate your mySQL database.$extra";
		$this->ipsclass->admin->page_title  = "mySQL ".$this->true_version." Tool Box";
		
		//-----------------------------------------
		// have we got some there tables me laddo?
		//-----------------------------------------
		
		$tables = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^tbl_(\S+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$tables[] = $match[1];
 				}
 			}
 		}
 		
 		if ( count($tables) < 1 )
 		{
 			$this->ipsclass->admin->error("You must choose some tables to run this tool on or it's just plain outright silly");
 		}
 		
 		//-----------------------------------------
		// What tool is one running?
		// optimize analyze check repair
		//-----------------------------------------
		
		if (strtoupper($this->ipsclass->input['tool']) == 'DROP' || strtoupper($this->ipsclass->input['tool']) == 'CREATE' || strtoupper($this->ipsclass->input['tool']) == 'FLUSH')
		{
			$this->ipsclass->admin->error("You can't do that, sorry");
		}
		
		foreach($tables as $table)
		{
			$this->ipsclass->DB->query(strtoupper($this->ipsclass->input['tool'])." TABLE $table");
			
			$fields = $this->ipsclass->DB->get_result_fields();
			
			$data = $this->ipsclass->DB->fetch_row();
			
			$cnt = count($fields);
			
			// Print the headers - we don't what or how many so...
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				$this->ipsclass->adskin->td_header[] = array( $fields[$i]->name , "*" );
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Result: ".$this->ipsclass->input['tool']." ".$table );
			
			// Grab the rows - we don't what or how many so...
			
			$rows = array();
			
			for( $i = 0; $i < $cnt; $i++ )
			{
				$rows[] = $data[ $fields[$i]->name ];
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( $rows );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
		
		
	}
	
	

	
	function db_table_diag( $print = 1 )
	{
		$good_img = "<img src='{$this->ipsclass->skin_acp_url}/images/aff_tick.png' border='0' alt='YN' class='ipd' />";
		$bad_img  = "<img src='{$this->ipsclass->skin_acp_url}/images/aff_cross.png' border='0' alt='YN' class='ipd' />";

		$separator = $print ? "|" : "<br />";		
		
		//-----------------------------------------		
		// Tool based on code by Stewart - thx :D
		//-----------------------------------------		
		
		//-----------------------------------------		
		// Fixing something?
		//-----------------------------------------
		
		$queries_to_run = array();
		
		foreach( $this->ipsclass->input as $k => $v )
		{
			if( preg_match( "/^query(\d+)$/", $k, $matches ) )
			{
				$queries_to_run[] = $v;
			}
		}
		
		if( isset($this->ipsclass->input['query']) AND $this->ipsclass->input['query'] )
		{
			$queries_to_run[] = $this->ipsclass->input['query'];
		}
				
		if( count($queries_to_run) > 0 )
		{
			foreach( $queries_to_run as $the_query )
			{
				$sql = trim( urldecode( base64_decode($the_query) ) );
				
				if ( preg_match( "/^(DROP|FLUSH)/i", trim($sql) ) )
				{
					$this->ipsclass->main_msg = "Sorry, those queries are not allowed for your safety";
					
					continue;
				}
				else if ( preg_match( "/^(?!SELECT)/i", preg_replace( "#\s{1,}#s", "", $sql ) ) and preg_match( "/admin_login_logs/i", preg_replace( "#\s{1,}#s", "", $sql ) ) )
				{
					$this->ipsclass->main_msg = "Sorry, those queries are not allowed for your safety";
					
					continue;			
				}
				else
				{
					$this->ipsclass->DB->return_die = 1;
				
					$this->ipsclass->DB->query($sql,1);
				
					if( $this->ipsclass->DB->error != "" )
					{
						$this->ipsclass->main_msg .= "<span style='color:red;'>SQL Error</span><br />{$this->ipsclass->DB->error}<br />";
					}
					else
					{
						$this->ipsclass->main_msg .= "Query: ".htmlspecialchars($sql)."<br />Executed Successfully<br />";
					}
					
					$this->ipsclass->DB->error  = "";
					$this->ipsclass->DB->failed = 0;
				}
			}
		}
		
		require ( ROOT_PATH.'/install/sql/mysql_tables.php' );
		
		$this->db_has_issues 	= false;
		$queries_needed 		= array();
		$tables_needed 			= array();
				
		if( is_array($TABLE) && count($TABLE) )
		{
			$table_html_count = 0;
			
			foreach( $TABLE as $the_table )
			{
				$expected_columns = array();
			
				if( preg_match("#CREATE TABLE\s+?(.+?)\s+?\(#ie", $the_table, $bits))
				{
					$tbl_name = $bits[1];
					$tbl_name = str_replace( "ibf_", "", $tbl_name );
					
					$table_defs[$tbl_name] = str_replace( $bits[1], SQL_PREFIX . $tbl_name, $the_table );
					
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
				else if ( preg_match("#ALTER TABLE ([a-z_]*) ADD ([a-z_]*) #is", $the_table, $bits) )
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
	
				if( !$this->ipsclass->DB->table_exists( $tbl_name ) )
				{
					$popup_div = "<div style='border: 2px outset rgb(85, 85, 85); padding: 4px; background: rgb(238, 238, 238) none repeat scroll 0%; position: absolute; width: auto; display: none; text-align: center; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;' id='{$tbl_name}' align='center'>{$table_defs[$tbl_name]}</div>";
					
					$output[] = $this->ipsclass->adskin->add_td_row( array( "<span style='color:red'>" . SQL_PREFIX . $tbl_name . "</span>",
																			"<center>{$bad_img}</center>",
																			"<center><script type='text/javascript'>all_queries[{$table_html_count}] = '".urlencode(base64_encode($table_defs[$tbl_name]))."';</script><a href='{$this->ipsclass->base_url}&amp;section=help&amp;act=diag&amp;code=dbchecker&amp;query=".urlencode(base64_encode($table_defs[$tbl_name]))."'><b>Fix Automatically</b></a>&nbsp;&nbsp;&nbsp;{$separator}&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"toggleview('{$tbl_name}');return false;\" style='cursor: pointer;'><b>Fix Manually</b></a><br />{$popup_div}</center>"
																   ) 	  );
					$this->ipsclass->DB->failed = 0;
					$this->db_has_issues		= true;
					
					$table_html_count++;
				}
				else
				{
					// Here we go...
					$missing 		= array();
					
					foreach( $expected_columns as $trymeout )
					{
						if( ! $this->ipsclass->DB->field_exists( $trymeout, $tbl_name ) )
						{
							$this->db_has_issues 	= true;
							$missing[] 				= $trymeout;
							$query_needed 			= "ALTER TABLE " . SQL_PREFIX . $tbl_name . " ADD " . $this->columns_to_defs[$tbl_name][$trymeout];
							
							if( preg_match( "/auto_increment;/", $query_needed ) )
							{
								$query_needed = substr( $query_needed, 0, -1 ).", ADD PRIMARY KEY( ". $trymeout . ");";
							}
							
							$popup_div = "<div style='border: 2px outset rgb(85, 85, 85); padding: 4px; background: rgb(238, 238, 238) none repeat scroll 0%; position: absolute; width: auto; display: none; text-align: center; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;' id='{$tbl_name}{$trymeout}' align='center'>{$query_needed}</div>";
							
							$output[] = $this->ipsclass->adskin->add_td_row( array( "<span style='color:red'>" . SQL_PREFIX . $tbl_name . " (missing column {$trymeout})</span>",
																					"<center>{$bad_img}</center>",
																					"<center><script type='text/javascript'>all_queries[{$table_html_count}] = '".urlencode(base64_encode($query_needed))."';</script><a href='{$this->ipsclass->base_url}&amp;section=help&amp;act=diag&amp;code=dbchecker&amp;query=".urlencode(base64_encode($query_needed))."'><b>Fix Automatically</b></a>&nbsp;&nbsp;&nbsp;{$separator}&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"toggleview('{$tbl_name}{$trymeout}');return false;\" style='cursor: pointer;'><b>Fix Manually</b></a><br />{$popup_div}</center>"
																		   ) 	  );						
							$table_html_count++;
						}
					}
					
					if( !count( $missing ) )
					{
						$output[] = $this->ipsclass->adskin->add_td_row( array( "<span style='color:green'>" . SQL_PREFIX . $tbl_name . "</span>",
																				"<center>{$good_img}</center>",
																				"&nbsp;"
																	   ) 	  );
					}
				}
			}
		}
		
		return $output;
	}	
	
	
	//-----------------------------------------
	// SHOW ALL TABLES AND STUFF!
	// 5 hours ago this seemed like a damned good idea.
	//-----------------------------------------
	
	function list_index()
	{
		$form_array = array();
		$extra 		= "";
		
		if ( $this->mysql_version < 3232 )
		{
			$extra = "<br><b>Note: your version of mySQL has a limited feature set and some tools have been removed</b>";
		}
	
		$this->ipsclass->admin->page_detail = "This section allows you to administrate your mySQL database.$extra";
		$this->ipsclass->admin->page_title  = "SQL ".$this->true_version." Tool Box";
		
		//-----------------------------------------
		// Show advanced stuff for mySQL > 3.23.03
		//-----------------------------------------
		
		$idx_size = 0;
		$tbl_size = 0;
		
		
		$this->ipsclass->html .= "
				     <script language='Javascript'>
                     <!--
                     function CheckAll(cb) {
                         var fmobj = document.theForm;
                         for (var i=0;i<fmobj.elements.length;i++) {
                             var e = fmobj.elements[i];
                             if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
                                 e.checked = fmobj.allbox.checked;
                             }
                         }
                     }
                     function CheckCheckAll(cb) {	
                         var fmobj = document.theForm;
                         var TotalBoxes = 0;
                         var TotalOn = 0;
                         for (var i=0;i<fmobj.elements.length;i++) {
                             var e = fmobj.elements[i];
                             if ((e.name != 'allbox') && (e.type=='checkbox')) {
                                 TotalBoxes++;
                                 if (e.checked) {
                                     TotalOn++;
                                 }
                             }
                         }
                         if (TotalBoxes==TotalOn) {fmobj.allbox.checked=true;}
                         else {fmobj.allbox.checked=false;}
                     }
                     //-->
                     </script>
                     ";
						  
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'sql' ),
																			 2 => array( 'code' , 'dotool'),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	) , "theForm"     );
		
		if ( $this->mysql_version >= 3230 )
		{
		
			$this->ipsclass->adskin->td_header[] = array( "Table"      , "20%" );
			$this->ipsclass->adskin->td_header[] = array( "Rows"       , "10%" );
			$this->ipsclass->adskin->td_header[] = array( "Export"     , "10%" );
			$this->ipsclass->adskin->td_header[] = array( '<input name="allbox" type="checkbox" value="Check All" onClick="CheckAll();">'     , "10%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Invision Power Board Tables" );
			
			$this->ipsclass->DB->query("SHOW TABLE STATUS FROM `".$this->ipsclass->vars['sql_database']."`");
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				// Check to ensure it's a table for this install...
				
				if ( ! preg_match( "/^".$this->ipsclass->vars['sql_tbl_prefix']."/", $r['Name'] ) )
				{
					continue;
				}
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b><span style='font-size:12px'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=runsql&query=".urlencode("SELECT * FROM {$r['Name']}")."'>{$r['Name']}</a></span></b>",
														  "<center>{$r['Rows']}</center>",
														  "<center><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=export_tbl&tbl={$r['Name']}'>Export</a></center></b>",
														  "<center><input name=\"tbl_{$r['Name']}\" value=1 type='checkbox' onClick=\"CheckCheckAll();\"></center>",
												 )      );
			}
		}
		else
		{
			// display a basic information table
			
			$this->ipsclass->adskin->td_header[] = array( "Table"      , "60%" );
			$this->ipsclass->adskin->td_header[] = array( "Rows"       , "30%" );
			$this->ipsclass->adskin->td_header[] = array( '<input name="allbox" type="checkbox" value="Check All" onClick="CheckAll();">'     , "10%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Invision Power Board Tables" );
			
			$tables = $this->ipsclass->DB->get_table_names();
			
			foreach($tables as $tbl)
			{
				// Ensure that we're only peeking at IBF tables
				
				if ( ! preg_match( "/^".$this->ipsclass->vars['sql_tbl_prefix']."/", $tbl ) )
				{
					continue;
				}
				
				$this->ipsclass->DB->query("SELECT COUNT(*) AS Rows FROM $tbl");
				
				$cnt = $this->ipsclass->DB->fetch_row();
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b><span style='font-size:12px'>$tbl</span></b>",
														  "<center>{$cnt['Rows']}</center>",
														  "<center><input name='tbl_$tbl' type='checkbox' onClick=\"CheckCheckAll(this);\"></center>",
												 )      );
												 
			}
			
		}
		
		//-----------------------------------------
		// Add in the bottom stuff
		//-----------------------------------------
											 
		if ( $this->mysql_version < 3232 )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "<select id='button' name='tool'>
													<option value='optimize'>Optimize Selected Tables</option>
												  </select>
												 <input type='submit' value='Go!' class='realbutton'></form>", "center", "tablerow2" );
		}
		else
		{
										 
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "<select id='button' name='tool'>
													<option value='optimize'>Optimize Selected Tables</option>
													<option value='repair'>Repair Selected Tables</option>
													<option value='check'>Check Selected Tables</option>
													<option value='analyze'>Analyze Selected Tables</option>
												  </select>
												 <input type='submit' value='Go!' class='realbutton'></form>", "center", "tablerow2" );
		}
			
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'sql' ),
											      2 => array( 'code' , 'runsql'),
											      4 => array( 'section', $this->ipsclass->section_code ),
										 )      );
		
		$this->ipsclass->adskin->td_header[] = array( "{none}"      , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}"      , "70%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Run a Query" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Manual Query</b><br>Advanced Users Only",
												  $this->ipsclass->adskin->form_textarea("query", "" ),
												 )      );
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run Query");
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
	
	}
	
    function gzip_four_chars($val)
	{
		for ($i = 0; $i < 4; $i ++)
		{
			$return .= chr($val % 256);
			$val     = floor($val / 256);
		}
		
		return $return;
	} 
}


?>