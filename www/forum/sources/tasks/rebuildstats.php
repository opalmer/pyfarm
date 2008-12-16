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
|   > TASK SCRIPT: Test
|   > Script written by Matt Mecham
|   > Date started: 28th January 2004
|
+--------------------------------------------------------------------------
*/

//-----------------------------------------
// THIS TASKS OPERATIONS:
// Rebuilds topics, posts, forum, members, last reg. member counts
//+--------------------------------------------------------------------------

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
		// Get current stats...
		//-----------------------------------------
		
		$stats = array();
		
		$r = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='stats'" ) );
		
		$tmp = unserialize( $this->ipsclass->txt_safeslashes($r['cs_value']) );
		
		if ( is_array( $tmp ) and count( $tmp ) )
		{
			foreach( $tmp as $k => $v )
			{
				$stats[ $k ] = stripslashes($v);
			}
		}
		
		unset( $tmp );
		
		//-----------------------------------------
		// Rebuild stats
		//-----------------------------------------
		
		$topics = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as tcount',
																 'from'   => 'topics',
												 				 'where'  => 'approved=1' ) );
		
		$posts  = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'SUM(posts) as replies',
																 'from'   => 'topics',
																 'where'  => 'approved=1' ) );
																 
		$stats['total_topics']  = $topics['tcount'];
		$stats['total_replies'] = $posts['replies'];
	
		$r = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as members', 'from' => 'members', 'where' => "mgroup <> ".$this->ipsclass->vars['auth_group'] ) );
		$stats['mem_count'] = intval( $r['members'] );
		
		$r = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'id, name, members_display_name',
															'from'   => 'members',
															'where'  => "mgroup <> ".$this->ipsclass->vars['auth_group'],
															'order'  => 'id DESC',
															'limit'  => array(0,1)
												   )      );
		
		$stats['last_mem_name'] = $r['members_display_name'];
		$stats['last_mem_id']   = $r['id'];
		
		if ( count($stats) > 0 )
		{
			$this->ipsclass->DB->obj['use_shutdown']  = 0;
			$this->ipsclass->cache['stats'] = $stats;
			$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
		}
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->append_task_log( $this->task, 'Statistics rebuilt' );
		
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