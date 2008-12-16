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
|   > $Date: 2006-03-23 07:34:25 -0500 (Thu, 23 Mar 2006) $
|   > $Revision: 177 $
|   > $Author: brandon $
+---------------------------------------------------------------------------
|
|   > Support Module
|   > Module written by Brandon Farber
|   > Date started: 19th April 2006
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class ad_support
{
	var $base_url;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "help";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "support";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Support' );
		
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
				
			//-----------------------------------------
			default:
				$this->ipsclass->admin->page_detail = "If you are experiencing an issue with your Invision Power Services software and require official assistance or support, you may utilize our ticketing system to submit a ticket.  Please allow 24-48 hours for a response during normal business hours.<br /><br /><i>You must have an active support contract with us in order to submit a ticket.</i>";
				$this->ipsclass->admin->page_title  = "Help & Support";
			
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':view' );
				$this->ipsclass->admin->show_inframe( '' );
				break;
		}
	}
	
}


?>