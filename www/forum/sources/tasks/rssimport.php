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
|   > $Date: 2007-03-15 10:54:27 -0400 (Thu, 15 Mar 2007) $
|   > $Revision: 879 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > TASK SCRIPT: Update Views
|   > Script written by Matt Mecham
|   > Date started: Monday 9th May 2005 (12:06)
|
+--------------------------------------------------------------------------
*/

//-----------------------------------------
// THIS TASKS OPERATIONS:
// Imports awaiting RSS articles
//+----------------------------------------

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class task_item
{
	var $class;
	var $root_path 	= "";
	var $task;
	
	# I've actually seen a site with over 200 RSS imports
	# When they run all at once, it times out, locks the task, and that's that
	var $limit		= 10;
	
	/*-------------------------------------------------------------------------*/
	// Our 'auto_run' function
	// ADD CODE HERE
	/*-------------------------------------------------------------------------*/
	
	function run_task()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$feeds_to_update = array();
		
		$time       = time();
		$t_minus_30 = time() - ( 30 * 60 );
		
		//-----------------------------------------
		// Got any to update?
		// 30 mins is RSS friendly.
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' 	=> '*', 
												 'from' 	=> 'rss_import', 
												 'where' 	=> 'rss_import_enabled=1 AND rss_import_last_import <= '.$t_minus_30,
												 'order'	=> 'rss_import_last_import ASC',
												 'limit'	=> array( $this->limit )
										) 		);
		$rss_main_query = $this->ipsclass->DB->exec_query();
		
		if ( $this->ipsclass->DB->get_num_rows( $rss_main_query ) )
		{
			require ( ROOT_PATH . 'sources/action_admin/rssimport.php' );
			$admin            =  new ad_rssimport();
			$admin->ipsclass  =& $this->ipsclass;
			
			while( $rss_feed = $this->ipsclass->DB->fetch_row( $rss_main_query ) )
			{
				$this_check = time() - ( $rss_feed['rss_import_time'] * 60 );
				
				if ( $rss_feed['rss_import_last_import'] <= $this_check )
				{
					//-----------------------------------------
					// Set the feeds we need to update...
					//-----------------------------------------
					
					$feeds_to_update[] = $rss_feed['rss_import_id'];
				}
			}
			
			//-----------------------------------------
			// Do the update now...
			//-----------------------------------------
			if ( count( $feeds_to_update ) )
			{
				$admin->rssimport_rebuild_cache( implode( ",", $feeds_to_update), 0, 1 );
			}
		}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------

		$this->class->append_task_log( $this->task, 'RSS Import completed ('. count($feeds_to_update) .')' );
		
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