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
|   > $Date: 2006-06-08 17:11:50 +0100 (Thu, 08 Jun 2006) $
|   > $Revision: 289 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Logs Stuff
|   > Module written by Matt Mecham
|   > Date started: 11nd September 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_logs_login
{

	var $base_url;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "admin";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "loginlog";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'ACP Log-in Logs' );
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_admin');
		
		switch($this->ipsclass->input['code'])
		{
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->login_logs_view();
				break;
			case 'view_detail':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->login_logs_view_detail();
				break;
		}
	}
	
	//*-------------------------------------------------------------------------*/
    // View detail
    /*-------------------------------------------------------------------------*/
	
	function login_logs_view_detail()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$admin_id = intval( $this->ipsclass->input['detail'] );
		
		//-----------------------------------------
		// Get data from the deebee
		//-----------------------------------------
		
		$log = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
															 	 'from'   => 'admin_login_logs',
															 	 'where'  => 'admin_id='.$admin_id ) );
															
		if ( ! $log['admin_id'] )
		{
			$this->ipsclass->main_msg = "No log for that ID found";
			$this->login_logs_view();
			return;
		}
		
		//-----------------------------------------
		// Display...
		//-----------------------------------------
		
		$log['_admin_time'] 		= $this->ipsclass->get_date( $log['admin_time'], 'LONG' );
		$log['_admin_post_details'] = unserialize( $log['admin_post_details'] );
		$log['_admin_img']          = $log['admin_success'] ? 'aff_tick.png' : 'aff_cross.png';
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->acp_last_logins_detail( $log );
		
		$this->ipsclass->admin->print_popup();
	}
	
	//*-------------------------------------------------------------------------*/
    // View current log in logs
    /*-------------------------------------------------------------------------*/
	
	function login_logs_view()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start   = intval( $this->ipsclass->input['st'] );
		$perpage = 50;
			
		//-----------------------------------------
		// Get log count
		//-----------------------------------------
		
		$count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as count',
																   'from'   => 'admin_login_logs' ) );
																
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => intval( $count['count'] ),
														  		  'PER_PAGE'    => $perpage,
														  		  'CUR_ST_VAL'  => $start,
														  		  'L_SINGLE'    => "",
														  		  'L_MULTI'     => "Pages: ",
														  		  'BASE_URL'    => $this->ipsclass->base_url.'&'.$this->ipsclass->form_code ) );
									  
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'admin_login_logs',
												 'order'  => 'admin_time DESC',
												 'limit'  => array( $start, $perpage ) ) );
												
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['_admin_time'] = $this->ipsclass->admin->get_date( $row['admin_time'] );
			$row['_admin_img']  = $row['admin_success'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$logins .= $this->html->acp_last_logins_row( $row );
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		//$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( "ACP Log-in Attempts", "This page shows all the recorded ACP log in attempts.<br />The red cross indicates an error when logging in and a green tick indicates a successful log in." ) . "<br />";
		
		$this->ipsclass->html .= $this->html->acp_last_logins_wrapper( $logins, $links );
		
		$this->ipsclass->admin->output();
	}
}


?>