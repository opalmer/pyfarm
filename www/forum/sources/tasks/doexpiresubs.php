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
|   > TASK SCRIPT: Actually expire subs
|   > Script written by Matt Mecham
|   > Date started: 12th July 2005 (Tuesday)
|
+--------------------------------------------------------------------------
*/

/*-------------------------------------------------------------------------*/
// THIS TASKS OPERATIONS:
// Actually expire subscriptions
/*-------------------------------------------------------------------------*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class task_item
{
	var $class     = "";
	var $task      = "";
	
	/*-------------------------------------------------------------------------*/
	// Our 'auto_run' function
	// ADD CODE HERE
	/*-------------------------------------------------------------------------*/
	
	function run_task()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$expire_ids = array();
		
		//-----------------------------------------
		// Get all subs to expire
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'subscription_trans',
													  'where'  => "subtrans_state='paid' AND subtrans_end_date < ".time()
											 )      );
											 
		$outer = $this->ipsclass->DB->simple_exec();
		
		while ( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			$query = array( "sub_end" => 0 );
			
			if ( $row['subtrans_old_group'] > 0 )
			{
				//---------------------------------------
				// Group still exist?
				//---------------------------------------
				
				if ( is_array( $this->ipsclass->cache['group_cache'][ $row['subtrans_old_group'] ] ) )
				{
					$query['mgroup'] = $row['subtrans_old_group'];
				}
				else
				{
					//---------------------------------------
					// Group has been deleted, reset back to base member group
					//---------------------------------------
					
					$query['mgroup'] = $this->ipsclass->vars['member_group'];
				}
			}
			
			$expire_ids[ $row['subtrans_id'] ] = $row['subtrans_id'];
			
			//---------------------------------------
			// Update member
			//---------------------------------------
			
			$this->ipsclass->DB->do_update( 'members', $query, "id=".intval($row['subtrans_member_id']) );
		}
		
		//---------------------------------------
		// Update rows...
		//---------------------------------------
		
		if ( count( $expire_ids ) )
		{
			$this->ipsclass->DB->do_update( 'subscription_trans', array( 'subtrans_state' => "expired" ), "subtrans_id IN (".implode(",",$expire_ids ).")" );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->append_task_log( $this->task, intval(count($expire_ids))." members unsubscribed" );
		
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