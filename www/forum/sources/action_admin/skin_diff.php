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
|   > $Date: 2007-01-18 18:02:33 -0500 (Thu, 18 Jan 2007) $
|   > $Revision: 831 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Skin Difference Engine
|   > Module written by Matt Mecham
|   > Date started: 22nd July 2005
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_skin_diff
{
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "lookandfeel";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "skindiff";


	function auto_run()
	{
		$this->ipsclass->admin->page_detail = "Compare your template set differences.";
		$this->ipsclass->admin->page_title  = "Skin Template HTML Differences";
		$this->ipsclass->admin->nav[] 		= array( $this->ipsclass->form_code, 'Skin Differences Home' );

		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_lookandfeel');
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'skin_diff':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rundiff' );
				$this->skin_differences_start();
				break;
				
			case 'skin_diff_process':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rundiff' );
				$this->skin_differences_process();
				break;
				
			case 'skin_diff_view':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rundiff' );
				$this->skin_differences_view();
				break;
			
			case 'skin_diff_export':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->skin_differences_export();
				break;
				
			case 'skin_diff_view_diff':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rundiff' );
				$this->skin_differences_view_diff();
				break;
			
			case 'skin_diff_from_skin':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rundiff' );
				$this->skin_differences_start_from_skin();
				break;
				
			case 'skin_diff_remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->skin_differences_remove();
				break;
				
				
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_differences_list();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [VIEW]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (XML files)
	*
	* @since	2.1.0.2005-07-35
	*/
	function skin_differences_remove()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$diff_session_id = intval( $this->ipsclass->input['diff_session_id'] );
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$current_session = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'template_diff_session', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		if ( ! $current_session['diff_session_id'] )
		{
			$this->ipsclass->admin->error("Could not get the current template compare session.");
		}
		
		//-----------------------------------------
		// Delete from IMPORT
		//-----------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'templates_diff_import', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		//-----------------------------------------
		// Delete from SESSION
		//-----------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'template_diff_session', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		//-----------------------------------------
		// Delete from CHANGES
		//-----------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'template_diff_changes', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->ipsclass->main_msg = "Differences report removed";
		
		$this->skin_differences_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [EXPORT]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (EXPORT)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_export()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$diff_session_id = intval( $this->ipsclass->input['diff_session_id'] );
		$content         = '';
		$missing		 = 0;
		$changed		 = 0;
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$current_session = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'template_diff_session', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		if ( ! $current_session['diff_session_id'] )
		{
			$this->ipsclass->admin->error("Could not get the current template compare session.");
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'template_diff_changes',
												 'where'  => 'diff_session_id='.$diff_session_id,
												 'order'  => 'diff_change_func_group ASC, diff_change_func_name ASC' ) );
		
		
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Culmulate
			//-----------------------------------------
			
			$row['diff_change_type'] ? $changed++ : $missing++;
			
			$row['diff_change_content'] = str_replace( "\n", "<br>", $row['diff_change_content']);
			$row['diff_change_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$row['diff_change_content']);
			$row['diff_change_content'] = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$row['diff_change_content']);

			
			$content .= $this->html->skin_diff_export_row( $row['diff_change_func_name'], $row['diff_change_func_group'], $row['diff_change_content'] );
		}
		
		$content = $this->html->skin_diff_export_overview( $content, $missing, $changed, $current_session['diff_session_title'], gmdate( 'r' ) );
		
		$this->ipsclass->admin->show_download( $content, 'ipb-difference-export.html', "unknown/unknown", 0 );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [VIEW]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (XML files)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_view_diff()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$diff_key = $this->ipsclass->input['diff_key'];

		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$diff_row = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'template_diff_changes',
																	  'where'  => "diff_change_key='".$diff_key."'"  ) );
		
		
		if ( ! $diff_row['diff_change_key'] )
		{
			$this->ipsclass->admin->error( "No key found" );
		}
		
		$diff_row['diff_change_content'] = str_replace( "\n", "<br>", $diff_row['diff_change_content']);
		$diff_row['diff_change_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$diff_row['diff_change_content']);
		$diff_row['diff_change_content'] = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$diff_row['diff_change_content']);
		
		$this->ipsclass->html = $this->html->skin_diff_view_bit( $diff_row['diff_change_func_group'], $diff_row['diff_change_func_name'], $diff_row['diff_change_content'] );
		
		$this->ipsclass->admin->print_popup();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [VIEW]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (XML files)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_view()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$diff_session_id = intval( $this->ipsclass->input['diff_session_id'] );
		$content         = '';
		$missing		 = 0;
		$changed		 = 0;
		$last_group      = '';
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$current_session = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'template_diff_session', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		if ( ! $current_session['diff_session_id'] )
		{
			$this->ipsclass->admin->error("Could not get the current template compare session.");
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'template_diff_changes',
												 'where'  => 'diff_session_id='.$diff_session_id,
												 'order'  => 'diff_change_func_group ASC, diff_change_func_name ASC' ) );
		
		
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Gen data
			//-----------------------------------------
			
			$key  = $diff_session_id.':'.$row['diff_change_func_group'].':'.$row['diff_change_func_name'];
			$size = $this->ipsclass->size_format( $this->ipsclass->math_strlen_to_bytes( strlen( $row['diff_change_content'] ) ) );
			
			//-----------------------------------------
			// Diff type
			//-----------------------------------------
			
			if ( ! $row['diff_change_type'] )
			{
				$diff_is = '<span style="color:red">New</span>';
				$missing++;
			}
			else
			{
				$diff_is = '<span style="color:green">Changed</span>';
				$changed++;
			}
			
			//-----------------------------------------
			// New cat?
			//-----------------------------------------
			
			if ( $last_group != $row['diff_change_func_group'] )
			{
				$last_group = $row['diff_change_func_group'];
				
				$content .= $this->html->skin_diff_row_newgroup( $row['diff_change_func_group'] );
			}
			
			//-----------------------------------------
			// Culmulate
			//-----------------------------------------
			
			$content .= $this->html->skin_diff_row( $row['diff_change_func_name'], $size, $key, $diff_is, str_replace( ':', '_', $key ) );
		}
		
		$this->ipsclass->html = $this->html->skin_diff_overview( $content, $missing, $changed );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences: From 1 skin to another
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (XML files)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_start_from_skin()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$content = "";
		$skin_id = intval( $this->ipsclass->input['skin_id'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $skin_id )
		{
			$this->ipsclass->admin->error( "No skin ID passed!" );
		}
		
		//-----------------------------------------
		// Get skin set...
		//-----------------------------------------
		
		$skin_set = $this->ipsclass->DB->build_and_exec_query( array(  'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$skin_id ) );
		
		if ( ! $skin_set['set_skin_set_id'] )
		{
			$this->ipsclass->admin->error( "No skin ID passed (again)!" );
		}
		
		//-----------------------------------------
		// Get number template bits
		//-----------------------------------------
		
		$total_bits = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count', 'from' => 'skin_templates', 'where' => 'set_id=1' ) );
		
		//-----------------------------------------
		// Create session
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'template_diff_session', array( 'diff_session_togo'    		  => intval( $total_bits['count'] ),
																		'diff_session_done'    		  => 0,
																		'diff_session_title'   		  => "Comparison from skin set: ".$skin_set['set_name'],
																		'diff_session_updated'        => time(),
																		'diff_session_ignore_missing' => 1 ) );
																		
		$diff_session_id = $this->ipsclass->DB->get_insert_id();
		
		$seen_templates = array();
		
		//-----------------------------------------
		// Grab template bits from DB
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'set_id='.$skin_id ) );
		$outer = $this->ipsclass->DB->exec_query();
		
		while( $entry = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			$check = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'diff_key', 'from' => 'templates_diff_import', 'where' => "diff_key='".$diff_session_id.':'.$entry[ 'group_name' ].':'.$entry[ 'func_name' ]."'" ) );
			
			if( $this->ipsclass->DB->get_num_rows() == 0 )
			{
				$this->ipsclass->DB->do_insert( 'templates_diff_import', array( 'diff_key'             => $diff_session_id.':'.$entry[ 'group_name' ].':'.$entry[ 'func_name' ],
																	  		'diff_func_group'      => $entry[ 'group_name' ],
																	  		'diff_func_data'	   => $entry[ 'func_data' ],
																	 		'diff_func_name'       => $entry[ 'func_name' ],
																	 		'diff_func_content'    => $entry[ 'section_content' ],
																	 		'diff_session_id'      => $diff_session_id ) );
				$seen_templates[ $entry[ 'group_name' ].':'.$entry[ 'func_name' ] ] = $entry['updated'];
			}
			else
			{
				if( $seen_templates[ $entry[ 'group_name' ].':'.$entry[ 'func_name' ] ] < $entry['updated'] )
				{
					$this->ipsclass->DB->do_update( 'templates_diff_import', array( 'diff_func_group'      => $entry[ 'group_name' ],
																	  		'diff_func_data'	   => $entry[ 'func_data' ],
																	 		'diff_func_name'       => $entry[ 'func_name' ],
																	 		'diff_func_content'    => $entry[ 'section_content' ],
																	 		'diff_session_id'      => $diff_session_id ), 'diff_key='.$diff_session_id.':'.$entry[ 'group_name' ].':'.$entry[ 'func_name' ] );
				}
			}
		}
		
		$this->ipsclass->admin->output_multiple_redirect_init( $this->ipsclass->base_url.'&'.$this->ipsclass->form_code_js."&code=skin_diff_process&diff_session_id={$diff_session_id}&pergo=10" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (XML files)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_start()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$content = "";
		$seen    = array();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['diff_session_title'] )
		{
			$this->ipsclass->admin->error( "You must enter a title" );
		}
		
		//-----------------------------------------
		// Get uploaded file
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// Ut-oh....
			//-----------------------------------------
			
			$this->ipsclass->admin->error( "No file was uploaded" );
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content = $this->ipsclass->admin->import_xml( $tmp_name );
		}
		
		if( !$content )
		{
			$this->ipsclass->admin->error( "There was no content in the file to process" );
		}
		
		//-----------------------------------------
		// Get number missing template bits
		//-----------------------------------------
		
		$total_bits = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count', 'from' => 'skin_templates', 'where' => 'set_id=1' ) );
		
		//-----------------------------------------
		// Create session
		//-----------------------------------------
		
		$this->ipsclass->DB->allow_sub_select = 1;
		
		$this->ipsclass->DB->do_insert( 'template_diff_session', array( 'diff_session_togo'    		  => intval( $total_bits['count'] ),
																		'diff_session_done'    		  => 0,
																		'diff_session_title'   		  => $this->ipsclass->input['diff_session_title'],
																		'diff_session_updated'        => time(),
																		'diff_session_ignore_missing' => intval( $this->ipsclass->input['diff_session_ignore_missing'] ) ) );
																		
		$diff_session_id = $this->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		$xml = new class_xml();
		
		//-----------------------------------------
		// Check to see if its an archive...
		//-----------------------------------------
		
		if ( preg_match( "#<xmlarchive generator=\"IPB\"#si", $content ) )
		{
			//-----------------------------------------
			// It is an archive... expand...
			//-----------------------------------------
			
			require( KERNEL_PATH.'class_xmlarchive.php' );
			$xmlarchive = new class_xmlarchive( KERNEL_PATH );
		
			$xmlarchive->xml_read_archive_data( $content );
			
			//-----------------------------------------
			// Get the XML documents
			//-----------------------------------------
			
			$import_xml = array();
			
			foreach( $xmlarchive->file_array as $f )
			{
				$import_xml[ $f['filename'] ] = $f['content'];
			}
		
			//-----------------------------------------
			// Import Templates
			//-----------------------------------------
		
			$content = $import_xml['ipb_templates.xml'];
		}
		
		//-----------------------------------------
		// Parse document
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Import template bits...
		//-----------------------------------------
		
		if ( ! is_array( $xml->xml_array['templateexport']['templategroup']['template'] ) )
		{
			$this->ipsclass->admin->error("Error with ipb_templates.xml - could not process XML properly");
		}
	
		foreach( $xml->xml_array['templateexport']['templategroup']['template'] as $entry )
		{
			$diff_key = $diff_session_id.':'.$entry[ 'group_name' ]['VALUE'].':'.$entry[ 'func_name' ]['VALUE'];
			
			if ( ! $seen[ $diff_key ] )
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_insert( 'templates_diff_import', array( 'diff_key'             => $diff_session_id.':'.$entry[ 'group_name' ]['VALUE'].':'.$entry[ 'func_name' ]['VALUE'],
																				'diff_func_group'      => $entry[ 'group_name' ]['VALUE'],
																				'diff_func_data'	   => $entry[ 'func_data' ]['VALUE'],
																				'diff_func_name'       => $entry[ 'func_name' ]['VALUE'],
																				'diff_func_content'    => $entry[ 'section_content' ]['VALUE'],
																				'diff_session_id'      => $diff_session_id ) );
																				
				$seen[ $diff_key ] = 1;
			}
			
		}
		
		$this->ipsclass->admin->output_multiple_redirect_init( $this->ipsclass->base_url.'&'.$this->ipsclass->form_code_js."&code=skin_diff_process&diff_session_id={$diff_session_id}&pergo=10" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [PROCESS]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (PROCESS)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_process()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$pergo           = intval( $this->ipsclass->input['pergo'] ) ? intval( $this->ipsclass->input['pergo'] ) : 10;
		$diff_session_id = intval( $this->ipsclass->input['diff_session_id'] );
		$done            = 0;
		$img             = '<img src="'.$this->ipsclass->skin_acp_url.'/images/aff_tick_small.png" border="0" alt="-" /> ';
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$current_session = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'template_diff_session', 'where' => 'diff_session_id='.$diff_session_id ) );
		
		if ( ! $current_session['diff_session_id'] )
		{
			$this->ipsclass->admin->error("Could not get the current template compare session.");
		}
		
		//-----------------------------------------
		// Get Diff library
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_difference.php' );
		$class_difference = new class_difference();
		$class_difference->method = 'PHP';
		
		//-----------------------------------------
		// Get template bits to check
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'skin_templates',
												 'where'  => 'set_id=1',
												 'order'  => 'suid ASC',
												 'limit'  => array( intval( $current_session['diff_session_done'] ), intval( $pergo ) ) ) );
												 
		$outer = $this->ipsclass->DB->exec_query();
		
		if ( ! $this->ipsclass->DB->get_num_rows( $outer ) )
		{
			$done = 1;
		}
		else
		{
			while( $row = $this->ipsclass->DB->fetch_row( $outer ) )
			{
				//-----------------------------------------
				// Get corresponding row from diff table
				//-----------------------------------------
				
				$diff_row = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																			  'from'   => 'templates_diff_import',
																			  'where'  => "diff_func_group='{$row['group_name']}' AND diff_func_name='{$row['func_name']}' AND diff_session_id=".$diff_session_id ) );
																			  
				//-----------------------------------------
				// Got anything?
				//-----------------------------------------
				
				if ( $diff_row['diff_key'] )
				{
					//-----------------------------------------
					// Get difference
					//-----------------------------------------
		
					$difference = $class_difference->get_differences( $row['section_content'], $diff_row['diff_func_content'] );
					
					//-----------------------------------------
					// Got any differences?
					//-----------------------------------------
					
					if ( $class_difference->diff_found )
					{
						
						//-----------------------------------------
						// Get corresponding row from diff table
						//-----------------------------------------
						
						$diff_check = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'diff_change_key',
																					  'from'   => 'template_diff_changes',
																					  'where'  => "diff_change_key='" . $diff_session_id.':'.$row['group_name'].':'.$row['func_name'] . "'" ) );

						if( $diff_check['diff_change_key'] )
						{
							$this->ipsclass->DB->do_update( 'template_diff_changes', array( 'diff_change_func_group' => $row['group_name'],
																							'diff_change_func_name'  => $row['func_name'],
																							'diff_change_content'    => $difference,
																							'diff_change_type'       => 1,
																							'diff_session_id'        => $diff_session_id ),
																					"diff_change_key='" . $diff_session_id.':'.$row['group_name'].':'.$row['func_name'] . "'" );
						}
						else
						{
							$this->ipsclass->DB->do_insert( 'template_diff_changes', array( 'diff_change_key'        => $diff_session_id.':'.$row['group_name'].':'.$row['func_name'],
																							'diff_change_func_group' => $row['group_name'],
																							'diff_change_func_name'  => $row['func_name'],
																							'diff_change_content'    => $difference,
																							'diff_change_type'       => 1,
																							'diff_session_id'        => $diff_session_id ) );
						}
					}
				}
				else
				{
					if ( ! $current_session['diff_session_ignore_missing'] )
					{
						$this->ipsclass->DB->do_insert( 'template_diff_changes', array( 'diff_change_key'        => $diff_session_id.':'.$row['group_name'].':'.$row['func_name'],
																						'diff_change_func_group' => $row['group_name'],
																						'diff_change_func_name'  => $row['func_name'],
																						'diff_change_content'    => htmlspecialchars($row['section_content']),
																						'diff_change_type'       => 0,
																						'diff_session_id'        => $diff_session_id ) );
					}
				}
				
				//-----------------------------------------
				// Increment
				//-----------------------------------------
				
				$current_session['diff_session_done']++;
			}
		}
		
		//-----------------------------------------
		// Update current session
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'template_diff_session', array( 'diff_session_done' => intval( $current_session['diff_session_done'] ) ), 'diff_session_id='.$diff_session_id );
		
		//-----------------------------------------
		//  Done or more?
		//-----------------------------------------
		
		if ( ! $done )
		{
			$this->ipsclass->admin->output_multiple_redirect_hit( $this->ipsclass->base_url.'&'.$this->ipsclass->form_code_js."&code=skin_diff_process&diff_session_id={$diff_session_id}&pergo=".$pergo,
															  	  $img.' '.$current_session['diff_session_done'].' of '. $current_session['diff_session_togo'] .' template bits processed...' );
		}
		else
		{
			$this->ipsclass->admin->output_multiple_redirect_done( "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=skin_diff_view&diff_session_id={$diff_session_id}' target='_top'>View the differences results</a>");
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [START]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (START)
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_differences_list()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$content = '';
		
		//-----------------------------------------
		// Get sessions
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'template_diff_session',
												 'order'  => 'diff_session_updated DESC' ) );
		
		
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Gen data
			//-----------------------------------------
			
			$row['_date'] = $this->ipsclass->get_date( $row['diff_session_updated'], 'TINY' );
			
			//-----------------------------------------
			// Culmulate
			//-----------------------------------------
			
			$content .= $this->html->skin_diff_main_row( $row );
		}
		
		$this->ipsclass->html = $this->html->skin_diff_main_overview( $content );
		
		$this->ipsclass->admin->output();
	}
	
}


?>