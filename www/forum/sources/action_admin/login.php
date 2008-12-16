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
|   > $Date: 2007-10-03 11:33:02 -0400 (Wed, 03 Oct 2007) $
|   > $Revision: 1124 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Help Control functions
|   > Module written by Matt Mecham
|   > Date started: 2nd April 2002
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


class ad_login
{
	# Global
	var $ipsclass;
	
	# HTML
	var $html;

	function auto_run()
	{
		//-----------------------------------------
		// What to do?
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'login':
				$this->login_form();
				break;
			case 'login-out':
				// Do we have a session?
				$sess_cookie = $this->ipsclass->my_getcookie( "ipb_admin_session_id" );
				
				if( $sess_cookie )
				{
					$this->ipsclass->DB->do_delete( "admin_sessions", "session_id='{$sess_cookie}'" );
				}
				
				$this->ipsclass->my_setcookie("ipb_admin_session_id", "-1", -1);
				$this->login_form();
				break;
			
			case 'login-complete':
				$this->login_complete();
				break;
	
			default:
				$this->login_form();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Complete the log in
	/*-------------------------------------------------------------------------*/
	
	function login_complete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$username = str_replace( '|', '&#124;', $this->ipsclass->input['username'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( empty($this->ipsclass->input['username']) )
		{
			$this->login_form("You must enter a username before proceeding");
		}
		
		if ( empty($this->ipsclass->input['password']) )
		{
			$this->login_form("You must enter a password before proceeding");
		}
		
		//-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login                =  new han_login();
    	$this->han_login->ipsclass      =& $this->ipsclass;
		$this->han_login->is_admin_auth =  1;
    	$this->han_login->init();
		
		//-----------------------------------------
		// Check auth
		//-----------------------------------------
		
		$this->han_login->login_authenticate( $username, $this->ipsclass->input['password'] );
		
		//-----------------------------------------
		// Check return code...
		//-----------------------------------------
		
		$mem = $this->han_login->member;

		$username_incorrect = "Username or password incorrect";
		
		if( $this->ipsclass->vars['ipbli_usertype'] != 'username' )
		{
			$username_incorrect = "Email address or password incorrect";
		}
		
		if ( ( ! $mem['id'] ) or ( $this->han_login->return_code == 'NO_USER' ) )
		{
			$this->write_to_log( $this->ipsclass->input['username'], 'fail' );
			$this->login_form( $username_incorrect );
		}
		
		if ( $this->han_login->return_code != 'SUCCESS' )
		{
			if ( $this->han_login->return_code == 'ACCOUNT_LOCKED' )
			{
				$this->write_to_log( $this->ipsclass->input['username'], 'fail' );
				$this->login_form( "Your account has been locked due to the number of failed login attempts made" );
			}
			else
			{
				$this->write_to_log( $this->ipsclass->input['username'], 'fail' );
				$this->login_form( $username_incorrect );
			}
		}
		
		//-----------------------------------------
		// Get perms
		//-----------------------------------------
		
		$this->ipsclass->sess->member = $mem;
		$this->ipsclass->sess->build_group_permissions();
		$mem = $this->ipsclass->sess->member;
		
		if ( $mem['g_access_cp'] != 1 )
		{
			$this->write_to_log( $this->ipsclass->input['username'], 'fail' );
			$this->login_form("You do not have access to the administrative CP");
		}
		else
		{
			
			//-----------------------------------------
			// Fix up query string...
			//-----------------------------------------
			
			$extra_query = "";
			
			if ( $_POST['qstring'] )
			{
				$extra_query = urldecode( $_POST['qstring'] );
				$extra_query = str_replace( "{$this->ipsclass->vars['board_url']}"           , "" , $extra_query );
				$extra_query = preg_replace( "!/?admin\.{$this->ipsclass->vars['php_ext']}!i", "" , $extra_query );
				$extra_query = preg_replace( "!^\?!"                                         , "" , $extra_query );
				$extra_query = preg_replace( "!adsess=(\w){32}!"                             , "" , $extra_query );
				$extra_query = preg_replace( "!s=(\w){32}!"                                  , "" , $extra_query );
				$extra_query = preg_replace( "!act=login!"                                   , "" , $extra_query );
				$extra_query = preg_replace( "!code=template-edit-bit!"                      , "" , $extra_query );
				$extra_query = preg_replace( "!code=template-bits-list!"                     , "" , $extra_query );
				$extra_query = preg_replace( "!bitname=(\w)!"                		         , "" , $extra_query );
				$extra_query = $this->ipsclass->parse_clean_value( $extra_query );
			}
			
			//-----------------------------------------
			// Delete old sessions..
			//-----------------------------------------
			
			$this->ipsclass->DB->do_delete( 'admin_sessions', 'session_member_id='.$mem['id'] );
			
			//-----------------------------------------
			// All is good, rejoice as we set a
			// session for this user
			//-----------------------------------------
			
			$sess_id = md5( uniqid( microtime() ) );
			
			$this->ipsclass->DB->do_insert( 'admin_sessions', array (
																			   'session_id'                => $sess_id,
																			   'session_ip_address'        => $this->ipsclass->ip_address,
																			   'session_member_name'       => $mem['name'],
																			   'session_member_id'         => $mem['id'],
																			   'session_member_login_key'  => md5( $mem['joined'] . $mem['ip_address'] ),
																			   'session_location'          => 'index',
																			   'session_log_in_time'       => time(),
																			   'session_running_time'      => time(),
											)						);
		
			$this->ipsclass->input['adsess'] = $sess_id;
			
			//-----------------------------------------
			// Set a session ID cookie
			//-----------------------------------------
		   
			// Don't want it used for XSS...
			//$this->ipsclass->my_setcookie("ipb_admin_session_id", $sess_id, 0);
			
			$this->write_to_log( $this->ipsclass->input['username'], 'ok' );
			
			//-----------------------------------------
			// Lets add some data in here for the access logs...
			//-----------------------------------------
			
			$extra_query .= "&member_id=".$mem['id'].'&password=ok';
			
			//-----------------------------------------
			// Redirect...
			//-----------------------------------------
			
			$this->ipsclass->admin->redirect( $this->ipsclass->vars['board_url'].'/'.IPB_ACP_DIRECTORY."/index.".$this->ipsclass->vars['php_ext']."?adsess=".$this->ipsclass->input['adsess']."&".$extra_query, 'Log In Successful' );
	
			exit();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Write to the log in table
	/*-------------------------------------------------------------------------*/
	
	/**
	* Write to the admin log in loggy ma log
	*
	* @param	string	Username
	* @param	string	ok/fail flag
	*/
	function write_to_log( $username='', $flag='fail' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$username 			 = $username ? $username : $this->ipsclass->input['username'];
		$flag    			 = ( $flag == 'ok' ) ? 1 : 0;
		$admin_post_details  = array();
		
		//-----------------------------------------
		// Generate POST / GET details
		//-----------------------------------------
		
		foreach( $_GET as $k => $v )
		{
			$admin_post_details['get'][ htmlspecialchars( $k ) ] = htmlspecialchars( $v );
		}
		
		foreach( $_POST as $k => $v )
		{
			if ( $k == 'password' )
			{
				$v = str_repeat( '*', strlen( $v ) - 1 ) . substr( $v, -1, 1 );
			}
			
			$admin_post_details['post'][ htmlspecialchars( $k ) ] = htmlspecialchars( $v );
		}
		
		//-----------------------------------------
		// Write to disk...
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'admin_login_logs', array( 'admin_ip_address'   => $this->ipsclass->ip_address,
																   'admin_username'     => $username,
																   'admin_time'		    => time(),
																   'admin_success'      => $flag,
																   'admin_post_details' => serialize($admin_post_details) ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Show the log in form
	/*-------------------------------------------------------------------------*/
	
	function login_form($message='')
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$message = $message ? $message : (isset($this->ipsclass->admin_session['_session_message']) ? $this->ipsclass->admin_session['_session_message'] : '');
		
		//-------------------------------------------------------
		// Remove all out of date sessions, like a good boy. Woof.
		//-------------------------------------------------------
		
		$cut_off_stamp = time() - 60*60*2;
		
		$this->ipsclass->DB->do_delete( 'admin_sessions', "session_log_in_time < {$cut_off_stamp}" );
		
		//------------------------------------------------------
		// AUTO Log in ma-thingy?
		//------------------------------------------------------
		
		$name  = "";
		$extra = "";
						 
		$mid = intval( $this->ipsclass->my_getcookie('member_id') );
		
		if ( $mid > 0 )
		{
			$this->ipsclass->DB->build_query( array( 'select' 	=> 'm.id, m.name, m.mgroup, g.g_access_cp, m.email',
													 'from'		=> array( 'members' => 'm' ),
													 'where'	=> "m.id={$mid} AND g.g_access_cp=1",
													 'add_join'	=> array( 1 => array( 'type'	=> 'left',
													 								  'from'	=> array( 'groups' => 'g' ),
													 								  'where'	=> 'g.g_id=m.mgroup' )
													 					)
											)		);
			$this->ipsclass->DB->exec_query();
						
			if ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$name  = $this->ipsclass->vars['ipbli_usertype'] == 'username' ? $r['name'] : $r['email'];
				$extra = 'onload="document.theAdminForm.password.focus();"';
			}
		}
		
		//------------------------------------------------------
		// SHW DA FRM (txt msg stylee)
		//------------------------------------------------------
		
		$qs = str_replace( '&amp;'   , '&', $this->ipsclass->parse_clean_value( urldecode( $this->ipsclass->my_getenv('QUERY_STRING') ) ) );
		$qs = str_replace( 'adsess=' , 'old_adsess=', $qs );
		$qs = str_replace( 'act=menu', '', $qs );
		$qs = str_replace( '&lt;'    , '', $qs );
		$qs = str_replace( '&gt;'    , '', $qs );
		$qs = str_replace( '('       , '', $qs );
		$qs = str_replace( ')'       , '', $qs );
		
		$this->ipsclass->html_title = "IPB: ACP: Log in";
		$this->ipsclass->html = str_replace( '<%CONTENT%>', $this->ipsclass->skin_acp_global->log_in_form( $qs, $message, $name ), $this->ipsclass->skin_acp_global->global_main_wrapper() );
		$this->ipsclass->html = str_replace( '<%TITLE%>'  , $this->ipsclass->html_title, $this->ipsclass->html );
		$this->ipsclass->html = str_replace( "<body", "<body style='background-image:url({$this->ipsclass->skin_acp_url}/images/blank.gif)'", $this->ipsclass->html );
		
		@header("Content-type: text/html");
		print $this->ipsclass->html;
		exit();
	}
	
	
	
	
	
}


?>