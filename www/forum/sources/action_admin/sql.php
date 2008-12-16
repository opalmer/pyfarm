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
|   > SQL Admin Stuff
|   > Module written by Matt Mecham
|   > Date started: Friday 24 June 2005
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_sql
{
	/*-------------------------------------------------------------------------*/
	// Auto run
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		if ( TRIAL_VERSION )
		{
			print "This feature is disabled in the trial version.";
			exit();
		}
		
		//-----------------------------------------
		// Make sure we're a root admin, or else!
		//-----------------------------------------
		
		if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
		{
			$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}
		
		require_once( ROOT_PATH.'sources/action_admin/sql_'.strtolower($this->ipsclass->vars['sql_driver']).'.php' );
		$dbdriver           =  new ad_sql_module();
		$dbdriver->ipsclass =& $this->ipsclass;
		$dbdriver->auto_run();
	}
}


?>