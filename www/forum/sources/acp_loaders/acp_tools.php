<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > CP "MYCP" PAGE CLASS
|   > Script written by Matt Mecham
|   > Date started: Wed. 18th August 2004
|
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class acp_tools
{
	# Globals
	var $ipsclass;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function acp_tools()
	{
	}
	
	/*-------------------------------------------------------------------------*/
	// AUTO RUN
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		$this->ipsclass->html_title = "IPB: Tools &amp; Settings";
		
		$another_choice = array(
								 
								 'op'        => 'settings',
								 'pin'       => 'plugins',
								 'cache'     => 'cache',
								 'rebuild'   => 'rebuild',
								 'help'      => 'help',
								 'ips'       => 'ips',
								 'task'      => 'task_manager',
								 'admin'     => 'administration',
								 'postoffice'=> 'postoffice',
								 'loginauth' => 'login_auth',
								 'portal'    => 'portal',
							   );
							   
		if ( ! isset( $another_choice[ $this->ipsclass->input['act'] ] ) )
		{
			 $this->ipsclass->input['act'] = 'op';
		}
		
		$this->ipsclass->form_code    = 'section=tools&amp;act=' . $this->ipsclass->input['act'];
		$this->ipsclass->form_code_js = 'section=tools&act='     . $this->ipsclass->input['act'];
		$this->ipsclass->section_code = 'tools';
		
		//-----------------------------------------
		// Quick perm check
		//-----------------------------------------
		
		$this->ipsclass->admin->cp_permission_check( $this->ipsclass->section_code.'|' );
		
		//-----------------------------------------
		// Require and run (again)
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/'.$another_choice[ $this->ipsclass->input['act'] ].'.php' );
		$constructor          = 'ad_'.$another_choice[ $this->ipsclass->input['act'] ];
		$runmeagain           = new $constructor;
		$runmeagain->ipsclass =& $this->ipsclass;
		$runmeagain->auto_run();
	}
	
	
	
	
	
}


?>