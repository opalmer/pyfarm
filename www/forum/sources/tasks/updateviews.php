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
|   > TASK SCRIPT: Update Views
|   > Script written by Matt Mecham
|   > Date started: 31st March 2005 (11:04)
|
+--------------------------------------------------------------------------
*/

//-----------------------------------------
// THIS TASKS OPERATIONS:
// Updates the topic views counter
//+----------------------------------------

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class task_item
{
	var $class     = "";
	var $root_path = "";
	var $task      = "";
	
	/*-------------------------------------------------------------------------*/
	// Our 'auto_run' function
	// ADD CODE HERE
	/*-------------------------------------------------------------------------*/
	
	function run_task()
	{
		//-----------------------------------------
		// Enabled?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['update_topic_views_immediately'] )
		{
			//-----------------------------------------
			// Load DB file
			//-----------------------------------------
			
			$this->ipsclass->DB->load_cache_file( $this->root_path.'sources/sql/'.SQL_DRIVER.'_extra_queries.php', 'sql_extra_queries' );
			
			//-----------------------------------------
			// Get SQL query
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'updateviews_get', array(), 'sql_extra_queries' );
			$o = $this->ipsclass->DB->cache_exec_query();
			
			while( $r = $this->ipsclass->DB->fetch_row( $o ) )
			{
				//-----------------------------------------
				// Update...
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'update' => 'topics',
															  'set'    => 'views=views+'.intval( $r['topicviews'] ),
															  'where'  => "tid=".intval($r['views_tid'])
													)      );
									
				$this->ipsclass->DB->simple_exec(); 
			}
			
			//-----------------------------------------
			// Delete from table
			//-----------------------------------------
			
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'topic_views' ) );
			
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->append_task_log( $this->task, 'Topic views counter updated' );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlock_task( $this->task );
	}
	
	/*-------------------------------------------------------------------------*/
	// register_class
	// LEAVE ALONE
	/*-------------------------------------------------------------------------*/
	
	function register_class(&$class)
	{
		$this->class     = &$class;
		$this->ipsclass  =& $class->ipsclass;
		$this->root_path = $this->class->root_path;
	}
	
	/*-------------------------------------------------------------------------*/
	// pass_task
	// LEAVE ALONE
	/*-------------------------------------------------------------------------*/
	
	function pass_task( $this_task )
	{
		$this->task = $this_task;
	}
	
	
}
?>