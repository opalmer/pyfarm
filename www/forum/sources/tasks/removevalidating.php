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
|   > $Date: 2005-10-10 09:08:54 -0400 (Mon, 10 Oct 2005) $
|   > $Revision: 23 $
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
		$mids 	= array();
		$vids 	= array();
		$emails	= array();
		
		// If enabled, remove validating new_reg members & entries from members table
		
		if ( intval($this->ipsclass->vars['validate_day_prune']) > 0 )
		{
			$less_than = time() - $this->ipsclass->vars['validate_day_prune'] * 86400;
			
			$this->ipsclass->DB->build_query( array( 'select' => 'v.vid, v.member_id',
													 'from'	  => array( 'validating' => 'v' ),
													 'where'  => 'v.new_reg=1 AND v.coppa_user<>1 AND v.entry_date < '.$less_than.' AND v.lost_pass<>1',
													 'add_join' => array( 0 => array( 'select' 	=> 'm.posts, m.mgroup, m.email',
													 								  'from'	=> array( 'members' => 'm' ),
													 								  'where'	=> 'm.id=v.member_id',
													 								  'type'	=> 'left'
													 					)			)
											)		);
			
			$outer = $this->ipsclass->DB->exec_query();
		
			while( $i = $this->ipsclass->DB->fetch_row($outer) )
			{
				if( $i['mgroup'] != $this->ipsclass->vars['auth_group'] )
				{
					// No longer validating?
					
					$this->ipsclass->DB->do_delete( 'validating', "vid='{$i['vid']}'" );
					continue;
				}
				
				if ( intval($i['posts']) < 1 )
				{
					$mids[] 					= $i['member_id'];
					$emails[ $i['member_id'] ]	= $i['email'];
					$vids[] 					= "'".$i['vid']."'";
				}
			}
			
			// Remove non-posted validating members
			
			if ( count($mids) > 0 )
			{
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'members_converge', 'where' => "converge_email IN('".implode("','",$emails)."')" ) );
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'members'         , 'where' => "id IN(".implode(",",$mids).")" ) );
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'member_extra'    , 'where' => "id IN(".implode(",",$mids).")" ) );
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'pfields_content' , 'where' => "member_id IN(".implode(",",$mids).")" ) );
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'validating'      , 'where' => "vid IN(".implode(",",$vids).")" ) );
				
				if ( USE_MODULES == 1 )
				{
					require ROOT_PATH."modules/ipb_member_sync.php";
					
					$this->modules = new ipb_member_sync();
					$this->modules->ipsclass =& $this->ipsclass;
					$this->modules->register_class($this);
					$this->modules->on_delete($mids);
				}
			}		
		
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->append_task_log( $this->task, count($mids).' old validating members pruned' );
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