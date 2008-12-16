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


class acp_lookandfeel
{
	# Globals
	var $ipsclass;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function acp_lookandfeel()
	{
	}
	
	/*-------------------------------------------------------------------------*/
	// AUTO RUN
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		$this->ipsclass->html_title = "IPB: Look &amp; Feel";
		
		$another_choice = array(
								 'wrap'      => 'skin_wrappers',
								 'style'     => 'skin_css',
								 'image'     => 'skin_macros',
								 'sets'      => 'skin_sets',
								 'templ'     => 'skin_template_bits',
								 'import'    => 'skin_import',
								 'lang'      => 'languages',
				 				 'skintools' => 'skintools',
				 				 'emoticons' => 'emoticons',
				 				 'skindiff'  => 'skin_diff',
				                 'skinremap' => 'skin_remap',
							   );
							   
		if ( ! isset( $another_choice[ $this->ipsclass->input['act'] ] ) )
		{
			 $this->ipsclass->input['act'] = 'sets';
		}
		
		$this->ipsclass->form_code    = 'section=lookandfeel&amp;act=' . $this->ipsclass->input['act'];
		$this->ipsclass->form_code_js = 'section=lookandfeel&act='     . $this->ipsclass->input['act'];
		$this->ipsclass->section_code = 'lookandfeel';
		
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