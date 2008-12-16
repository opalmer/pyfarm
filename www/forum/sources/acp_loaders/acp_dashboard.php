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


class acp_dashboard
{
	# Globals
	var $ipsclass;
	
	/**
	* Main choice array
	*
	* @var	array
	*/
	var $another_choice = array();
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $section_title  = "Dashboard";
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function acp_admin()
	{
		
	}
	
	/*-------------------------------------------------------------------------*/
	// AUTO RUN
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		$this->ipsclass->html_title = "IPB: Administration";
		
		$another_choice = array(
								'dashboard'  => 'dashboard',
							  );
									
		if ( !isset($this->ipsclass->input['act']) OR !isset($another_choice[ $this->ipsclass->input['act'] ]) OR !$another_choice[ $this->ipsclass->input['act'] ] )
		{
			 $this->ipsclass->input['act'] = 'dashboard';
		}
		
		$this->ipsclass->form_code    = 'section=admin&amp;act=' . $this->ipsclass->input['act'];
		$this->ipsclass->form_code_js = 'section=admin&act='     . $this->ipsclass->input['act'];
		$this->ipsclass->section_code = 'dashboard';
	
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