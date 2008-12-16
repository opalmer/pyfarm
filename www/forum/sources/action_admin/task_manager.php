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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Task Manager
|   > Module written by Matt Mecham
|   > Date started: 27th January 2004
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_task_manager
{
	# Global
	var $ipsclass;
	
	var $functions  = "";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "tools";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "task";
	
	function auto_run()
	{
		$this->ipsclass->form_code = "{$this->ipsclass->form_code}";
		
		//-----------------------------------------
		// Require and RUN !! THERES A BOMB
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_taskmanager.php' );
		
		$this->functions            = new func_taskmanager();
		$this->functions->ipsclass  =& $this->ipsclass;
		
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_taskmanager');
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->ipsclass->admin->page_detail = "The task manager contains all your scheduled tasks.<br />Please note that as these tasks are run when the board is accessed, the next run time is to be used as a guide only and depends on the traffic your board gets.";
		$this->ipsclass->admin->page_title  = "Task Manager";

		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Task Manager Home' );
		
		//-----------------------------------------
		// Using "do"?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['j_do']) AND $this->ipsclass->input['j_do'] )
		{
			$this->ipsclass->input['code'] = $this->ipsclass->input['j_do'];
		}
		else if ( isset($this->ipsclass->input['do']) AND $this->ipsclass->input['do'] )
		{
			$this->ipsclass->input['code'] = $this->ipsclass->input['do'];
		}
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'task_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->task_form('edit');
				break;
			case 'task_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->task_form('add');
				break;
				
			case 'task_edit_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->task_do_save('edit');
				break;
			case 'task_add_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->task_do_save('add');
				break;
				
				
			case 'task_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->task_delete_task();
				break;
				
			case 'task_run_now':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':runnow' );
				$this->task_run_task();
				break;
			case 'task_unlock':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->task_unlock();
				break;
				
			case 'log':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':log' );
				$this->task_log_setup();
				break;
				
			case 'showlog':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':log' );
				$this->task_log_show();
				break;
				
			case 'deletelog':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->task_log_delete();
				break;
				
			case 'master_xml_export':
				$this->master_xml_export();
				break;
			
			case 'task_rebuild_xml':
				$this->task_rebuild_xml();
				break;
				
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->task_show_tasks();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Rebuild XML
	/*-------------------------------------------------------------------------*/
	
	function task_rebuild_xml()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$file     = ROOT_PATH . 'resources/tasks.xml';
		$inserted = 0;
		$updated  = 0;
		$tasks    = array();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! file_exists( $file ) )
		{
			$this->ipsclass->main_msg = "$file could not be found. Please check, upload or try again";
			$this->show_intro();
		}
		
		//-----------------------------------------
		// Get current task info
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'task_manager' ) );
												
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$tasks[ $row['task_key'] ] = $row;
		}
		
		//-----------------------------------------
		// Get XML
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();		
				
		//-----------------------------------------
		// Get XML file (CSS/WRAPPERS)
		//-----------------------------------------
		
		$skin_content = implode( "", file($file) );
		
		//-----------------------------------------
		// Unpack the datafile (TEMPLATES)
		//-----------------------------------------
		
		$xml->xml_parse_document( $skin_content );
		
		//-----------------------------------------
		// TASKS
		//-----------------------------------------
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			$_key = $entry['task_key']['VALUE'];
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'task_id' )
				{
					continue;
				}
				
				if ( $f == 'task_cronkey' )
				{
					$entry[ $f ]['VALUE'] = $tasks[ $_key ]['task_cronkey'] ? $tasks[ $_key ]['task_cronkey'] : md5( uniqid( microtime() ) );
				}
				
				if ( $f == 'task_next_run' )
				{
					$entry[ $f ]['VALUE'] = $tasks[ $_key ]['task_next_run'] ? $tasks[ $_key ]['task_next_run'] : time();
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			if ( $tasks[ $_key ]['task_key'] )
			{
				$updated++;
				$this->ipsclass->DB->do_update( 'task_manager', $newrow, "task_key='" . $tasks[ $_key ]['task_key'] . "'" );
			}
			else
			{
				$inserted++;
				$this->ipsclass->DB->do_insert( 'task_manager', $newrow );
			}
		}
		
		$this->ipsclass->main_msg = "$inserted tasks added, $updated updated";
		$this->task_show_tasks();
	}
	
	/*-------------------------------------------------------------------------*/
	// Export Master XML
	/*-------------------------------------------------------------------------*/
	
	function master_xml_export()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];

		$xml->xml_set_root( 'export', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Set group
		//-----------------------------------------
		
		$xml->xml_add_group( 'group' );
		
		//-----------------------------------------
		// Get templates...
		//-----------------------------------------
	
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'task_manager'  ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			//-----------------------------------------
			// Sort the fields...
			//-----------------------------------------
			
			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'row', $content );
		}
		
		$xml->xml_add_entry_to_group( 'group', $entry );
		
		$xml->xml_format_document();
		
		$doc = $xml->xml_document;
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $doc, 'tasks.xml', '', 0 );
	}
	
	/*-------------------------------------------------------------------------*/
	// TASK LOG DELETE
	/*-------------------------------------------------------------------------*/
	
	function task_log_delete()
	{
		//-----------------------------------------
		// SHOW 'EM
		//-----------------------------------------
		
		$prune = $this->ipsclass->input['task_prune'] ? $this->ipsclass->input['task_prune'] : 30;
		$prune = time() - ( $prune * 86400 );
		
		if ( $this->ipsclass->input['task_id'] != -1 )
		{
			$where = "log_title='".$this->ipsclass->input['task_id']."' AND log_date < $prune";
		}
		else
		{
			$where = "log_date < $prune";
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'task_logs', 'where' => $where ) );
		
		$this->ipsclass->main_msg = 'Selected Task Logs Removed';
		$this->task_log_setup();
	}
	
	/*-------------------------------------------------------------------------*/
	// TASK LOG SHOW
	/*-------------------------------------------------------------------------*/
	
	function task_log_show()
	{
		$this->ipsclass->admin->nav[] = array( '', 'Viewing Task Logs' );
		
		//-----------------------------------------
		// SHOW 'EM
		//-----------------------------------------
		
		$limit = $this->ipsclass->input['task_count'] ? $this->ipsclass->input['task_count'] : 30;
		$limit = $limit > 150 ? 150 : intval($limit);
		
		if ( $this->ipsclass->input['task_id'] != -1 )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'task_logs', 'where' => "log_title='".$this->ipsclass->input['task_id']."'", 'order' => 'log_date DESC', 'limit' => array(0,$limit) ) );
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'task_logs', 'order' => 'log_date DESC', 'limit' => array(0,$limit) ) );
		}
		
		$this->ipsclass->DB->simple_exec();
		
		$this->ipsclass->adskin->td_header[] = array( "Task Run" , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Date Run" , "35%" );
		$this->ipsclass->adskin->td_header[] = array( "Log Info" , "45%" );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Selected Task Logs" );
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$row['log_title']}</b>",
																	   $this->ipsclass->admin->get_date( $row['log_date'], 'SHORT' ),
																	   "{$row['log_desc']}",
															  )      );
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<center>No results</center>");
		}
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	
	}
	
	/*-------------------------------------------------------------------------*/
	// TASK LOG START
	/*-------------------------------------------------------------------------*/
	
	function task_log_setup()
	{
		$this->ipsclass->admin->nav[] = array( '', 'Viewing Task Logs' );
		
		//-----------------------------------------
		// Some set up
		//-----------------------------------------
		
		$tasks = array( 0 => array( -1, 'All tasks' ) );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'task_manager', 'order' => 'task_title' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $pee = $this->ipsclass->DB->fetch_row() )
		{
			$tasks[] = array( $pee['task_title'], $pee['task_title'] );
		}
		
		//-----------------------------------------
		// LAST FIVE ACTIONS
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'task_logs', 'order' => 'log_date DESC', 'limit' => array(0,5) ) );

		$this->ipsclass->DB->simple_exec();
		
		$this->ipsclass->adskin->td_header[] = array( "Task Run" , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Date Run" , "35%" );
		$this->ipsclass->adskin->td_header[] = array( "Log Info" , "45%" );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Last 5 Tasks Run" );
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$row['log_title']}</b>",
																	   $this->ipsclass->admin->get_date( $row['log_date'], 'SHORT' ),
																	   "{$row['log_desc']}",
															  )      );
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<center>No results</center>");
		}
		
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Show more...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'task'    ),
															   2 => array( 'code' , 'showlog' ),
															   4 => array( 'section', $this->ipsclass->section_code ),
													  )      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "View Task Logs" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>View logs for task:</b>",
															  $this->ipsclass->adskin->form_dropdown( 'task_id', $tasks )
													 )      );
													 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Show how many log entries?</b>",
															  $this->ipsclass->adskin->form_input( 'task_count', '30' )
													 )      );
													 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form('View Logs');
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Delete...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'task'    ),
															   2 => array( 'code' , 'deletelog' ),
															   4 => array( 'section', $this->ipsclass->section_code ),
													  )      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "DELETE Task Logs" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Delete logs for task:</b>",
															  $this->ipsclass->adskin->form_dropdown( 'task_id', $tasks )
													 )      );
													 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Delete logs older than (in days)?</b>",
															  $this->ipsclass->adskin->form_input( 'task_prune', '30' )
													 )      );
													 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form('DELETE Logs');
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	
	}
	
	/*-------------------------------------------------------------------------*/
	// UNLOCK TASK
	/*-------------------------------------------------------------------------*/
	
	function task_unlock()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$task_id = intval( $this->ipsclass->input['task_id'] );
		
		$this->ipsclass->DB->do_update( 'task_manager', array( 'task_locked' => 0 ), "task_id=".$task_id );
		
		$this->ipsclass->main_msg = 'Task lock removed';
		$this->task_show_tasks();
	}
	
	/*-------------------------------------------------------------------------*/
	// RUN TASK
	/*-------------------------------------------------------------------------*/
	
	function task_run_task()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$task_id = intval( $this->ipsclass->input['task_id'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $task_id )
		{
			$this->ipsclass->main_msg = 'No ID was passed, cannot save';
			$this->task_show_tasks();
		}
		
		$this_task = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'task_manager', 'where' => 'task_id='.$task_id ) );
		
		if ( ! $this_task['task_id'] )
		{
			$this->ipsclass->main_msg = 'No task to run.';
			$this->show_tasks();
		}
		
		if ( ! $this_task['task_enabled'] )
		{
			$this->ipsclass->main_msg = "This task has been disabled. Please enable the task before running it.";
			$this->task_show_tasks();
		}
		
		//-----------------------------------------
		// Get new instance of functions
		//-----------------------------------------
		
		$func           =  new func_taskmanager();
		$func->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Locked?
		//-----------------------------------------
		
		if ( $this_task['task_locked'] > 0 )
		{
			$this->ipsclass->main_msg = "This task was locked at ". gmdate( 'j M Y - G:i', $this_task['task_locked'] ) ." and cannot be run until unlocked.";
			$this->task_show_tasks();
		}
		
		$newdate = $func->generate_next_run($this_task);
				
		$this->ipsclass->DB->do_update( 'task_manager', array( 'task_next_run' => $newdate, 'task_locked' => time() ), "task_id=".$this_task['task_id'] );
		
		$func->save_next_run_stamp();
		
		$func->root_path = ROOT_PATH;
		
		if ( file_exists( $func->root_path.'sources/tasks/'.$this_task['task_file'] ) )
		{
			require_once( $func->root_path.'sources/tasks/'.$this_task['task_file'] );
			$myobj = new task_item();
			$myobj->register_class( $func );
			$myobj->pass_task( $this_task );
			$myobj->run_task();
			
			$this->ipsclass->main_msg = 'Task run successfully';
			$this->task_show_tasks();
		}
		else
		{
			$this->ipsclass->main_msg = 'Cannot locate: '.$func->root_path.'sources/tasks/'.$this_task['task_file'];
			$this->task_show_tasks();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// DELETE TASK
	/*-------------------------------------------------------------------------*/
	
	function task_delete_task()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$task_id = intval( $this->ipsclass->input['task_id'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		$task = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id=$task_id" ) );
			
		if ( $task['task_safemode'] and ! IN_DEV )
		{
			$this->ipsclass->main_msg = "You are unable to delete this task.";
			$this->task_show_tasks();
			return;
		}
		
		//-----------------------------------------
		// Remove from the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'task_manager', 'where' => 'task_id='.$task_id ) );
		
		$this->functions->save_next_run_stamp();
		
		$this->ipsclass->main_msg = 'Task deleted';
		
		$this->task_show_tasks();
	}
	
	/*-------------------------------------------------------------------------*/
	// DO SAVE
	/*-------------------------------------------------------------------------*/
	
	function task_do_save($type='edit')
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$task_id      = intval($this->ipsclass->input['task_id']);
		$task_cronkey = $this->ipsclass->input['task_cronkey'];
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $task_id )
			{
				$this->ipsclass->main_msg = 'No ID was passed, cannot save';
				$this->task_form();
			}
		}
		
		if ( ! $this->ipsclass->input['task_title'] )
		{
			$this->ipsclass->main_msg = 'You must enter a task title.';
			$this->task_form();
		}
		
		if ( ! $this->ipsclass->input['task_file'] )
		{
			$this->ipsclass->main_msg = 'You must enter a filename for this task to run';
			$this->task_form();
		}
		
		//-----------------------------------------
		// Check the task file...
		//-----------------------------------------
		
		$this->ipsclass->input['task_file'] = preg_replace( '#\.{1,}#s', '.', $this->ipsclass->input['task_file'] );
		
		//-----------------------------------------
		// Compile task
		//-----------------------------------------
		
		$save = array( 'task_title'       => $this->ipsclass->input['task_title'],
					   'task_description' => $this->ipsclass->input['task_description'],
					   'task_file'        => $this->ipsclass->input['task_file'],
					   'task_week_day'    => $this->ipsclass->input['task_week_day'],
					   'task_month_day'   => $this->ipsclass->input['task_month_day'],
					   'task_hour'        => $this->ipsclass->input['task_hour'],
					   'task_minute'      => $this->ipsclass->input['task_minute'],
					   'task_log'		  => $this->ipsclass->input['task_log'],
					   'task_cronkey'     => $this->ipsclass->input['task_cronkey'] ? $task_cronkey : md5(microtime()),
					   'task_enabled'     => $this->ipsclass->input['task_enabled']
					 );
		
		if ( IN_DEV )
		{
			$save['task_key']      = $this->ipsclass->input['task_key'];
			$save['task_safemode'] = $this->ipsclass->input['task_safemode'];
		}
					 
		//-----------------------------------------
		// Get next run date...
		//-----------------------------------------
		
		$save['task_next_run'] = $this->functions->generate_next_run( $save );
		
		if ( $type == 'edit' )
		{
			$this->ipsclass->DB->do_update( 'task_manager', $save, 'task_id='.$task_id );
			$this->ipsclass->main_msg = 'Task Edited Successfully';
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'task_manager', $save );
			$this->ipsclass->main_msg = 'Task Saved Successfully';
		}
		
		$this->functions->save_next_run_stamp();
		
		$this->task_show_tasks();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Task Manager: Form
	/*-------------------------------------------------------------------------*/
	
	function task_form($type='edit')
	{
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit Task' );
		
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$form     = array();
		$task_id  = intval( $this->ipsclass->input['task_id'] );
		
		# Drop downs
		$dropdows = array();
		
		//-----------------------------------------
		// Edit or add?
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			$button  = "Edit Task";
			$formbit = "task_edit_do";
			$this->ipsclass->html_help_title = "";
			$this->ipsclass->html_help_msg   = "";
			
			$task  = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_id=$task_id" ) );
			
			if ( $task['task_safemode'] and ! IN_DEV )
			{
				$this->ipsclass->main_msg = "You are unable to edit this task.";
				$this->task_show_tasks();
				return;
			}
			
			$title = "Editing Task: ".$group['cb_group_name'];
		}
		else
		{
			$button  = "Create New Task";
			$formbit = "task_add_do";
			$this->ipsclass->html_help_title = "";
			$this->ipsclass->html_help_msg   = "";
			$task   = array();
			$title  = "Creating New Task";
		}
		
		//-----------------------------------------
		// Create drop downs
		//-----------------------------------------
		
		$dropdown['_minute'] = array( 0 => array( '-1', 'Every Minute'   ) );
		$dropdown['_hour']   = array( 0 => array( '-1', 'Every Hour'     ), 1 => array( '0', '0 - Midnight' ) ); 
		$dropdown['_wday']   = array( 0 => array( '-1', 'Every Week Day' ) );
		$dropdown['_mday']   = array( 0 => array( '-1', 'Every Day of the Month' ) );
		
		for( $i = 0 ; $i < 60; $i++ )
		{
			$dropdown['_minute'][] = array( $i, $i );
		}
		
		for( $i = 1 ; $i < 24; $i++ )
		{
			if ( $i < 12 )
			{
				$ampm = $i.' am';
			}
			else if ( $i == 12 )
			{
				$ampm = 'Midday';
			}
			else
			{
				$ampm = $i - 12 . ' pm';
			}
			
			$dropdown['_hour'][] = array( $i, $i. ' - ('.$ampm.')' );
		}
		
		for( $i = 1 ; $i < 32; $i++ )
		{
			$dropdown['_mday'][] = array( $i, $i );
		}
		
		$dropdown['_wday'][]  = array( '0', 'Sunday'     );
		$dropdown['_wday'][]  = array( '1', 'Monday'     );
		$dropdown['_wday'][]  = array( '2', 'Tuesday'    );
		$dropdown['_wday'][]  = array( '3', 'Wednesday'  );
		$dropdown['_wday'][]  = array( '4', 'Thursday'   );
		$dropdown['_wday'][]  = array( '5', 'Friday'     );
		$dropdown['_wday'][]  = array( '6', 'Saturday'   );
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['task_title']       = $this->ipsclass->adskin->form_input(        'task_title'      , $_POST['task_title']       ? $_POST['task_title']       : $task['task_title'] );
		$form['task_description'] = $this->ipsclass->adskin->form_input(        'task_description', $_POST['task_description'] ? $_POST['task_description'] : $task['task_description'] );
		$form['task_file']        = $this->ipsclass->adskin->form_simple_input( 'task_file'       , $_POST['task_file']        ? $_POST['task_file']        : $task['task_file']       , '20' );
		$form['task_minute']      = $this->ipsclass->adskin->form_dropdown(     'task_minute'     , $dropdown['_minute']       , $_POST['task_minute']      ? $_POST['task_minute']    : $task['task_minute']  ,  'onchange="updatepreview()"' );
		$form['task_hour']        = $this->ipsclass->adskin->form_dropdown(     'task_hour'       , $dropdown['_hour']         , $_POST['task_hour']        ? $_POST['task_hour']      : $task['task_hour']     , 'onchange="updatepreview()"' );
	    $form['task_week_day']    = $this->ipsclass->adskin->form_dropdown(     'task_week_day'   , $dropdown['_wday']         , $_POST['task_week_day']    ? $_POST['task_week_day']  : $task['task_week_day'] , 'onchange="updatepreview()"' );
		$form['task_month_day']   = $this->ipsclass->adskin->form_dropdown(     'task_month_day'  , $dropdown['_mday']         , $_POST['task_month_day']   ? $_POST['task_month_day'] : $task['task_month_day'], 'onchange="updatepreview()"' );
		$form['task_log']         = $this->ipsclass->adskin->form_yes_no(       'task_log'        , $_POST['task_log']         ? $_POST['task_log']         : $task['task_log'] );
		$form['task_enabled']     = $this->ipsclass->adskin->form_yes_no(       'task_enabled'    , $_POST['task_enabled']     ? $_POST['task_enabled']     : $task['task_enabled'] );
		
		if ( IN_DEV )
		{
			$form['task_key']      = $this->ipsclass->adskin->form_input(  'task_key'     , $_POST['task_key']      ? $_POST['task_key']      : $task['task_key'] );
			$form['task_safemode'] = $this->ipsclass->adskin->form_yes_no( 'task_safemode', $_POST['task_safemode'] ? $_POST['task_safemode'] : $task['task_safemode'] );
		}
		
		$this->ipsclass->html .= $this->html->task_manager_form( $form, $button, $formbit, $type, $title, $task );
		
		$this->ipsclass->admin->output();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Task Manager: Overview
	/*-------------------------------------------------------------------------*/
	
	function task_show_tasks()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$row     = array();
		$content = "";
		
		//-----------------------------------------
		// List tasks (pointless comment)
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'task_manager', 'order' => 'task_safemode, task_next_run' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['task_minute']    = $row['task_minute']    != '-1' ? $row['task_minute']    : '-';
			$row['task_hour']      = $row['task_hour']      != '-1' ? $row['task_hour']      : '-';
			$row['task_month_day'] = $row['task_month_day'] != '-1' ? $row['task_month_day'] : '-';
			$row['task_week_day']  = $row['task_week_day']  != '-1' ? $row['task_week_day']  : '-';
			
			if ( time() > $row['task_next_run'] )
			{
				$row['_image'] = 'task_run_now.gif';
			}
			else
			{
				$row['_image'] = 'task_run.gif';
			}
			
			$row['_next_run'] = gmdate( 'j M Y - G:i', $row['task_next_run'] );
			
			$row['_class']    = $row['task_enabled'] != 1 ? " style='color:gray'" : '';
			$row['_title']    = $row['task_enabled'] != 1 ? " (Disabled)" : '';
			$row['_next_run'] = $row['task_enabled'] != 1 ? "<span style='color:gray'><s>{$row['_next_run']}</s></span>" : $row['_next_run'];
			
			$content .= $this->html->task_manager_row( $row );
		}
		
		//-------------------------------
		// Print it
		//-------------------------------
		
		$this->ipsclass->html .= $this->html->task_manager_wrapper( $content, gmdate( 'jS F Y - h:i A' ) );
		$this->ipsclass->admin->output();
	}
	
}

?>