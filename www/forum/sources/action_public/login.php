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
|   > $Date: 2007-08-06 11:00:52 -0400 (Mon, 06 Aug 2007) $
|   > $Revision: 1097 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Log in / log out module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class login
{
	# Classes
	var $ipsclass;
	
	# Others
    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $login_html = "";
    var $modules    = "";
    var $han_login  = "";
    
    function auto_run()
    {
		$this->ipsclass->load_language('lang_login');
    	$this->ipsclass->load_template('skin_login');
    	
    	if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			$this->modules->ipsclass =& $this->ipsclass;
		}

    	//-----------------------------------------
    	// Are we enforcing log ins?
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->vars['force_login'] == 1 )
    	{
    		$msg = 'admin_force_log_in';
    	}
    	else
    	{
    		$msg = "";
    	}
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch( $this->ipsclass->input['CODE'] )
    	{
    		case '01':
    			$this->do_log_in();
    			break;
    		case '02':
    			$this->log_in_form();
    			break;
    		case '03':
    			$this->do_log_out();
    			break;
    			
    		case '04':
    			$this->markforum();
    			break;
    			
    		case '05':
    			$this->markboard();
    			break;
    			
    		case '06':
    			$this->delete_cookies();
    			break;
    			
    		case 'autologin':
    			$this->auto_login();
    			break;
    			
    		default:
    			$this->log_in_form($msg);
    			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// AUTO LOG IN
 	/*-------------------------------------------------------------------------*/
 	
 	function auto_login()
 	{
		//-----------------------------------------
 		// Universal routine.
 		// If we have cookies / session created, simply return to the index screen
 		// If not, return to the log in form
 		//-----------------------------------------
 		
 		$this->ipsclass->member = $this->ipsclass->sess->authorise();
 		
 		//-----------------------------------------
 		// If there isn't a member ID set, do a quick check ourselves.
 		// It's not that important to do the full session check as it'll
 		// occur when they next click a link.
 		//-----------------------------------------
 		
 		if ( ! $this->ipsclass->member['id'] )
 		{
			$mid = intval( $this->ipsclass->my_getcookie('member_id') );
			$pid = substr( $this->ipsclass->my_getcookie('pass_hash'), 0, 32 );
			
			If ( $mid and $pid )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'members',
															  'where'  => "id=$mid and member_login_key='$pid'"
													 )      );
				$this->ipsclass->DB->simple_exec();
				
				if ( $member = $this->ipsclass->DB->fetch_row() )
				{
					$this->ipsclass->member = $member;
					$this->ipsclass->session_id = "";
					$this->ipsclass->my_setcookie('session_id', '0', -1 );
				}
			}
 		}
 		
 		$true_words  = $this->ipsclass->lang['logged_in'];
 		$false_words = $this->ipsclass->lang['not_logged_in'];
 		$method = 'no_show';
 		
 		if ($this->ipsclass->input['fromreg'] == 1)
 		{
 			$true_words  = $this->ipsclass->lang['reg_log_in'];
 			$false_words = $this->ipsclass->lang['reg_not_log_in'];
 			$method = 'show';
 		}
 		else if ($this->ipsclass->input['fromemail'] == 1)
 		{
 			$true_words  = $this->ipsclass->lang['email_log_in'];
 			$false_words = $this->ipsclass->lang['email_not_log_in'];
 			$method = 'show';
 		}
 		else if ($this->ipsclass->input['frompass'] == 1)
 		{
 			$true_words  = $this->ipsclass->lang['pass_log_in'];
 			$false_words = $this->ipsclass->lang['pass_not_log_in'];
 			$method = 'show';
 		}
 		
 		if ($this->ipsclass->member['id'])
 		{
			$this->ipsclass->my_setcookie('session_id', '0', -1 );
	
 			if ($method == 'show')
 			{
 				$this->ipsclass->print->redirect_screen( $true_words, "" );
 			}
 			else
 			{
 				$this->ipsclass->boink_it($this->ipsclass->vars['board_url'].'/index.'.$this->ipsclass->vars['php_ext']);
 			}
 		}
 		else
 		{
 			if ($method == 'show')
 			{
 				$this->ipsclass->print->redirect_screen( $false_words, 'act=login&amp;CODE=00' );
 			}
 			else
 			{
 				$this->ipsclass->boink_it($this->ipsclass->base_url.'&act=login&CODE=00');
 			}
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// DELETE IPB COOKIES
 	/*-------------------------------------------------------------------------*/
 	
 	function delete_cookies( $check_key=1 )
 	{
	 	if( $check_key )
	 	{
			$key = $this->ipsclass->input['k'];
			
			# Check for funny business
			if ( $key != $this->ipsclass->md5_check )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
			}
		}

		if (is_array($_COOKIE))
 		{
	 		$this->ipsclass->vars['cookie_id'] = preg_quote( $this->ipsclass->vars['cookie_id'] );
	 		
 			foreach( $_COOKIE as $cookie => $value)
 			{
 				if (preg_match( "/^(".$this->ipsclass->vars['cookie_id']."ipbforum.*$)/i", $cookie, $match))
 				{
 					$this->ipsclass->my_setcookie( str_replace( $this->ipsclass->vars['cookie_id'], "", $match[0] ) , '-', -1 );
 				}
 			}
 		}
 		
 		$this->ipsclass->my_setcookie('pass_hash' , '-1');
 		$this->ipsclass->my_setcookie('member_id' , '-1');
 		$this->ipsclass->my_setcookie('session_id', '-1');
 		$this->ipsclass->my_setcookie('topicsread', '-1');
 		$this->ipsclass->my_setcookie('anonlogin' , '-1');
 		$this->ipsclass->my_setcookie('forum_read', '-1');
 		
		$this->ipsclass->boink_it($this->ipsclass->base_url);
		exit();
	}  
	
 	/*-------------------------------------------------------------------------*/
 	// MARK ALL AS READ
 	/*-------------------------------------------------------------------------*/
 	
 	function markboard()
 	{
		//-----------------------------------------
        // Reset board marker
        //-----------------------------------------
        
        if ( $this->ipsclass->member['id'] )
        {
			$this->ipsclass->member['members_markers']['board'] = time();
			
			$this->ipsclass->DB->do_update( 'members', array( 'members_markers' => serialize( $this->ipsclass->member['members_markers'] ) ), 'id='.$this->ipsclass->member['id'] );
			
			//-----------------------------------------	
			// Update forum marker rows
			//-----------------------------------------	
			
			$this->ipsclass->DB->do_update( 'topic_markers', array( 'marker_unread'       => 0,
																	'marker_last_update'  => time(),
																	'marker_last_cleared' => time(),
																	'marker_topics_read'  => serialize(array()) ), 'marker_member_id='.$this->ipsclass->member['id'] );
		}
		else
		{
			$this->ipsclass->forum_read[ 0 ] = time();
			$this->ipsclass->hdl_forum_read_cookie('set');
		}
		
		$this->ipsclass->boink_it($this->ipsclass->base_url.'act=idx');
	}  
    
    /*-------------------------------------------------------------------------*/
    // MARK FORUM AS READ
    /*-------------------------------------------------------------------------*/
    
    function markforum()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$forum_id      = intval($this->ipsclass->input['f']);
        $from_forum_id = intval($this->ipsclass->input['fromforum']);
        $forum_data    = $this->ipsclass->forums->forum_by_id[ $forum_id ];
        $children      = $this->ipsclass->forums->forums_get_children( $forum_data['id'] );
        $save          = array();
        
        //-----------------------------------------
        // Check
        //-----------------------------------------
        
        if ( ! $forum_data['id'] )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files' ) );
        }
        
        //-----------------------------------------
        // Come from the index? Add kids
        //-----------------------------------------
       
        if ( $this->ipsclass->input['i'] )
        {
			if ( is_array( $children ) and count($children) )
			{
				foreach( $children as $id )
				{
					$this->ipsclass->forum_read[ $id ] = time();
					
					$save[ $id ] = array( 'marker_forum_id'     => $id,
										  'marker_member_id'    => $this->ipsclass->member['id'],
										  'marker_last_update'  => time(),
										  'marker_unread'       => 0,
										  'marker_last_cleared' => time() );
				}
			}
        }
        
        //-----------------------------------------
        // Add in the current forum...
        //-----------------------------------------
        
        $this->ipsclass->forum_read[ $forum_data['id'] ] = time();
        
		$save[ $forum_data['id'] ] = array( 'marker_forum_id'     => $forum_data['id'],
											'marker_member_id'    => $this->ipsclass->member['id'],
											'marker_last_update'  => time(),
											'marker_unread'       => 0,
											'marker_last_cleared' => time() );
        
        //-----------------------------------------
        // Reset topic markers
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['db_topic_read_cutoff'] and $this->ipsclass->member['id'] )
        {
        	if ( count( $save ) )
        	{
        		foreach( $save as $data )
        		{
	        		$this->ipsclass->DB->do_replace_into( 'topic_markers', $data, array('marker_member_id','marker_forum_id'), TRUE );
        		}
        	}
        }
		
		//-----------------------------------------
		// Reset cookie
		//-----------------------------------------
		
		$this->ipsclass->hdl_forum_read_cookie('set');
		
		//-----------------------------------------	
        // Where are we going back to?
        //-----------------------------------------
        
        if ( $from_forum_id )
        {
        	//-----------------------------------------
        	// Its a sub forum, lets go redirect to parent forum
        	//-----------------------------------------
        	
        	$this->ipsclass->boink_it($this->ipsclass->base_url."showforum=".$from_forum_id);
        }
        else
        {
        	$this->ipsclass->boink_it($this->ipsclass->base_url.'act=idx');
        }
    }
    
    /*-------------------------------------------------------------------------*/
    // LOG IN FORM
    /*-------------------------------------------------------------------------*/
    
    function log_in_form($message="")
    {
        //-----------------------------------------
        // INIT
        //-----------------------------------------
        
        $extra_form = "";
        $show_form  = 1;
        
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	
        //-----------------------------------------
		// Are they banned?
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['banfilters'] ) and count( $this->ipsclass->cache['banfilters'] ) )
		{
			foreach ($this->ipsclass->cache['banfilters'] as $ip)
			{
				$ip = str_replace( '\*', '.*', preg_quote($ip, "/") );
				
				if ( preg_match( "/^$ip$/", $this->ipsclass->ip_address ) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'you_are_banned', 'INIT' => 1 ) );
				}
			}
		}
        
        if ( $message != "" )
        {
        	$message = $this->ipsclass->lang[ $message ];
        	$message = str_replace( "<#NAME#>", "<b>{$this->ipsclass->input['UserName']}</b>", $message );
        
			$this->output .= $this->ipsclass->compiled_templates['skin_login']->errors($message);
		}
		
		//-----------------------------------------
		// Using an alternate log in form?
		//-----------------------------------------
		
		if ( $this->han_login->login_method['login_login_url'] )
		{
			//-----------------------------------------
			// Simply redirect to the form now
			//-----------------------------------------
			
			$this->ipsclass->boink_it( $this->han_login->login_method['login_login_url'] );
			exit();
		}
		
		//-----------------------------------------
		// Extra  HTML?
		//-----------------------------------------
		
		if ( $this->han_login->login_method['login_alt_login_html'] )
		{
			if ( ! $this->han_login->login_method['login_replace_form'] )
			{
				$extra_form = $this->han_login->login_method['login_alt_login_html'];
				$show_form  = 1;
			}
			else
			{
				$this->output .= $this->han_login->login_method['login_alt_login_html'];
				$show_form     = 0;
			}
		}
		
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		if ( $show_form )
		{
			if ( !$this->ipsclass->my_getenv('HTTP_REFERER') OR !preg_match( "/" . preg_quote( $this->ipsclass->vars['board_url'], '/' ) . "/i", $this->ipsclass->my_getenv('HTTP_REFERER') ) )
			{
				// HTTP_REFERER isn't set when force_login is enabled
				// This method will piece together the base url, and the querystring arguments
				// This is not anymore secure/insecure than IPB, as IPB will have to process
				// those arguments whether force_login is enabled or not.
				
				$argv = (is_array($this->ipsclass->my_getenv('argv')) && count($this->ipsclass->my_getenv('argv')) > 0) ? $this->ipsclass->my_getenv('argv') : array();
				
				$http_referrer = $this->ipsclass->base_url.@implode( "&amp;", $argv );
			}
			else
			{
				$http_referrer = $this->ipsclass->my_getenv('HTTP_REFERER');
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_login']->ShowForm( $this->ipsclass->lang['please_log_in'], htmlentities(urldecode($http_referrer)), $extra_form );
		}
		
		$this->nav        = array( $this->ipsclass->lang['log_in'] );
	 	$this->page_title = $this->ipsclass->lang['log_in'];
		
		$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav, 'OVERRIDE' => $this->ipsclass->vars['board_offline'] ) );
        
        exit();
    }
    
    /*-------------------------------------------------------------------------*/
    // DO LOG IN
    /*-------------------------------------------------------------------------*/
    
    function do_log_in()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$url    = "";
    	$member = array();
    	
    	//-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	
    	//-----------------------------------------
    	// Make sure the username and password were entered
    	//-----------------------------------------
    	
    	if ( $_POST['UserName'] == "" )
    	{
	    	if( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
	    	{
    			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_username' ) );
			}
			else
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_email_login' ) );
			}
    	}
    
     	if ( $_POST['PassWord'] == "" )
     	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_blank' ) );
    	}   
		
		//-----------------------------------------
		// Check for input length
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
		{
			if ( $this->ipsclass->txt_mb_strlen( $_POST['UserName'] ) > 32 )
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'username_long' ) );
			}
			
			$username = strtolower(str_replace( '|', '&#124;', $this->ipsclass->input['UserName']) );
		}
		else
		{
			$username = strtolower( trim( $this->ipsclass->input['UserName'] ) );
		}
		
		if ( $this->ipsclass->txt_mb_strlen( $_POST['PassWord'] ) > 32)
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'pass_too_long' ) );
		}
		
		$password = md5( $this->ipsclass->input['PassWord'] );
		
		//-----------------------------------------
		// Check auth
		//-----------------------------------------
		
		$this->han_login->login_authenticate( $username, $this->ipsclass->input['PassWord'] );
		
		//-----------------------------------------
		// Check return code...
		//-----------------------------------------
		
		$member = $this->han_login->member;
		
		if ( ( ! $member['id'] ) or ( $this->han_login->return_code == 'NO_USER' ) )
		{
			$this->log_in_form( 'wrong_name' );
		}
		
		if ( $this->han_login->return_code != 'SUCCESS' )
		{
			if ( $this->han_login->return_code == 'ACCOUNT_LOCKED' )
			{
				$extra = "<!-- -->";
				
				if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
				{
					if( $this->han_login->account_unlock )
					{
						$time = time() - $this->han_login->account_unlock;

						$time = ( $this->ipsclass->vars['ipb_bruteforce_period'] - ceil( $time / 60 ) > 0 ) ? $this->ipsclass->vars['ipb_bruteforce_period'] - ceil( $time / 60 ) : 1;
						
						$extra = sprintf( $this->ipsclass->lang['bruteforce_account_unlock'], $time );
					}
				}
					
				$this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' => 'bruteforce_account_lock', 'EXTRA' => $extra ) );
			}
			else
			{
				$this->log_in_form( 'wrong_auth' );
			}
		}
		
		//-----------------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//-----------------------------------------
		
		if ( $member['members_created_remote'] )
		{
			$pmember = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id={$member['id']}" ) );
			
			if ( $pmember['partial_member_id'] )
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['partial_login'], 'act=reg&amp;CODE=complete_login&amp;mid='.$member['id'].'&amp;key='.$pmember['partial_date'] );
				exit();
			}
		}
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------
		
		$_ok     = 1;
		$_time   = ( $this->ipsclass->vars['login_key_expire'] ) ? ( time() + ( intval($this->ipsclass->vars['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->ipsclass->vars['login_key_expire'] : 365;
		
		if ( $this->ipsclass->vars['login_change_key'] OR ! $member['member_login_key'] OR ( $this->ipsclass->vars['login_key_expire'] AND ( time() > $member['member_login_key_expire'] ) ) )
		{
			$member['member_login_key'] = $this->ipsclass->converge->generate_auto_log_in_key();
			
			$this->ipsclass->DB->do_update( 'members', array( 'member_login_key' 		=> $member['member_login_key'],
			 												  'member_login_key_expire' => $_time ), 'id='.$member['id'] );
		}
	
		//-----------------------------------------
		// Strong hold cookie?
		//-----------------------------------------
		
		$this->ipsclass->stronghold_set_cookie( $member['id'], $member['member_login_key'], 1 );
		
		//-----------------------------------------
		// Cookie me softly?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['CookieDate'] )
		{
			$this->ipsclass->my_setcookie("member_id"   , $member['id']              , 1 );
			$this->ipsclass->my_setcookie("pass_hash"   , $member['member_login_key'], $_sticky, $_days );
		}
		
		//-----------------------------------------
		// Remove any COPPA cookies previously set
		//-----------------------------------------
		
		$this->ipsclass->my_setcookie("coppa", '0', 0);
		
		//-----------------------------------------
		// Update profile if IP addr missing
		//-----------------------------------------
		
		if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
		{
			$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
														  'set'    => "ip_address='{$this->ipsclass->ip_address}'",
														  'where'  => "id={$member['id']}"
												 )      );
								 
			$this->ipsclass->DB->simple_exec();
		}
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
		$poss_session_id = "";
		
		if ( $cookie_id = $this->ipsclass->my_getcookie('session_id') )
		{
			$poss_session_id = $this->ipsclass->my_getcookie('session_id');
		}
		else if ( $this->ipsclass->input['s'] )
		{
			$poss_session_id = $this->ipsclass->input['s'];
		}
		
		//-----------------------------------------
		// Clean...
		//-----------------------------------------
		
		$poss_session_id = preg_replace("/([^a-zA-Z0-9])/", "", $poss_session_id);
		
		if ( $poss_session_id )
		{
			$session_id = $poss_session_id;
			
			if( $this->ipsclass->vars['match_ipaddress'] )
			{
				//-----------------------------------------
				// Delete any old sessions with this users IP
				// addy that doesn't match our session ID.
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'delete' => 'sessions',
															  'where'  => "ip_address='".$this->ipsclass->ip_address."' AND id <> '$session_id'"
													 )      );
									 
				$this->ipsclass->DB->simple_shutdown_exec();
			}
			
			if( $this->ipsclass->vars['disable_anonymous'] )
			{
				$privacy = 0;
			}
			else
			{
				$privacy = ( isset($this->ipsclass->input['Privacy']) AND $this->ipsclass->input['Privacy']) ? 1 : 0;
			}
			
			$this->ipsclass->DB->do_shutdown_update( 'sessions',
													 array (
															 'member_name'  => $member['members_display_name'],
															 'member_id'    => $member['id'],
															 'running_time' => time(),
															 'member_group' => $member['mgroup'],
															 'login_type'   => $privacy
														   ),
													 "id='".$session_id."'"
												 );
		}
		else
		{
			$session_id = md5( uniqid(microtime()) );
			
			if( $this->ipsclass->vars['disable_anonymous'] )
			{
				$privacy = 0;
			}
			else
			{
				$privacy = $this->ipsclass->input['Privacy'] ? 1 : 0;
			}			
			
			if( $this->ipsclass->vars['match_ipaddress'] )
			{
				//-----------------------------------------
				// Delete any old sessions with this users IP addy.
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'delete' => 'sessions',
															  'where'  => "ip_address='".$this->ipsclass->ip_address."'"
													 )      );
									 
				$this->ipsclass->DB->simple_shutdown_exec();
			}
			
			$this->ipsclass->DB->do_shutdown_insert( 'sessions',
													 array (
															 'id'           => $session_id,
															 'member_name'  => $member['members_display_name'],
															 'member_id'    => $member['id'],
															 'running_time' => time(),
															 'member_group' => $member['mgroup'],
															 'ip_address'   => $this->ipsclass->ip_address,
															 'browser'      => substr($this->ipsclass->clean_value($this->ipsclass->my_getenv('HTTP_USER_AGENT')), 0, 50),
															 'login_type'   => $privacy
												  )       );
		}
		
		$this->ipsclass->member           = $member;
		$this->ipsclass->session_id       = $session_id;
		
		if (isset($this->ipsclass->input['referer']) AND $this->ipsclass->input['referer'] && ($this->ipsclass->input['act'] != 'Reg'))
		{
			$url = str_replace( '&amp;', '&', $this->ipsclass->input['referer'] );
			$url = str_replace( "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}", "", $url );
			$url = preg_replace( "!^\?!"       , ""   , $url );
			$url = preg_replace( "!s=(\w){32}!", ""   , $url );
			$url = preg_replace( "!act=(login|reg|lostpass)!i", "", $url );
		}
		
		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
													  'set'    => "login_anonymous='".intval($privacy)."&1', failed_logins='', failed_login_count=0",
													  'where'  => "id={$member['id']}"
											 )      );
							 
		$this->ipsclass->DB->simple_shutdown_exec();
			
		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'delete' => 'validating',
													  'where'  => "member_id={$this->ipsclass->member['id']} AND lost_pass=1"
											 )      );
							 
		$this->ipsclass->DB->simple_shutdown_exec();
		
		//-----------------------------------------
		// Redirect them to either the board
		// index, or where they came from
		//-----------------------------------------
		
		$this->ipsclass->my_setcookie("session_id", $this->ipsclass->session_id, -1);
		
		$this->logged_in = 1;
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			$this->modules->on_login($member);
		}
		
		if ( isset($this->ipsclass->input['return']) AND $this->ipsclass->input['return'] != "" )
		{
			$return = urldecode($this->ipsclass->input['return']);
			
			if ( preg_match( "#^http://#", $return ) )
			{
				$this->ipsclass->boink_it($return);
			}
		}
		
		//-----------------------------------------
		// Check for dupemail
		//-----------------------------------------
		
		$member_extra = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'bio', 'from' => 'member_extra', 'where' => 'id='.$member['id'] ) );
		
		if ( $member_extra['bio'] == 'dupemail' )
		{
			$this->ipsclass->print->redirect_screen( "{$this->ipsclass->lang['thanks_for_login']} {$this->ipsclass->member['members_display_name']}", 'act=usercp&amp;CODE=00' );
		}
		else
		{
			$this->ipsclass->print->redirect_screen( "{$this->ipsclass->lang['thanks_for_login']} {$this->ipsclass->member['members_display_name']}", $url );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// DO LOG OUT
	/*-------------------------------------------------------------------------*/

	function do_log_out( $return=1 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if ( $return )
		{
			$key = $this->ipsclass->input['k'];
			
			# Check for funny business
			if ( $key != $this->ipsclass->md5_check )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
			}
		}
		
		//-----------------------------------------
		// Set some cookies
		//-----------------------------------------
		
		$this->ipsclass->my_setcookie( "member_id" , "0"  );
		$this->ipsclass->my_setcookie( "pass_hash" , "0"  );
		$this->ipsclass->my_setcookie( "anonlogin" , "-1" );
		
		if ( is_array($_COOKIE) )
 		{
	 		$this->ipsclass->vars['cookie_id'] = preg_quote( $this->ipsclass->vars['cookie_id'] );
	 		
 			foreach( $_COOKIE as $cookie => $value )
 			{
 				if ( preg_match( "/^(".$this->ipsclass->vars['cookie_id']."ipbforumpass_.*$)/i", $cookie, $match) )
 				{
 					$this->ipsclass->my_setcookie( str_replace( $this->ipsclass->vars['cookie_id'], "", $match[0] ) , '-', -1 );
 				}
 			}
 		}

		//-----------------------------------------
		// Remote log out?
		//-----------------------------------------
	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();

		//-----------------------------------------
		// Using an alternate log in form?
		//-----------------------------------------
		
		if ( $this->han_login->login_method['login_logout_url'] )
		{
			//-----------------------------------------
			// Simply redirect to the form now
			//-----------------------------------------
			
			$this->ipsclass->boink_it( $this->han_login->login_method['login_logout_url'] );
			exit();
		}
		
		//-----------------------------------------
		// Do it..
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'update' => 'sessions',
													  'set'    => "member_name='',member_id='0',login_type='0',member_group={$this->ipsclass->vars['guest_group']}",
													  'where'  => "id='". $this->ipsclass->sess->session_id ."'"
											 )      );
							 
		$this->ipsclass->DB->simple_shutdown_exec();
		
		list( $privacy, $loggedin ) = explode( '&', $this->ipsclass->member['login_anonymous'] );
		
		
		$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
													  'set'    => "login_anonymous='{$privacy}&0', last_visit=".time().", last_activity=".time(),
													  'where'  => "id=".$this->ipsclass->member['id']
											 )      );
							 
		$this->ipsclass->DB->simple_shutdown_exec();
		
		# Horrid hack: IPB 3.0: Separate out log out functions into class / function
		if ( $return )
		{
			//-----------------------------------------
			// Redirect...
			//-----------------------------------------
			
			$url = "";
			
			if ( isset($this->ipsclass->input['return']) AND $this->ipsclass->input['return'] != "" )
			{
				$return = urldecode($this->ipsclass->input['return']);
				
				if ( preg_match( "#^http://#", $return ) )
				{
					$this->ipsclass->boink_it($return);
				}
			}
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['thanks_for_logout'], "" );
		}
		else
		{
			return TRUE;
		}
	}

}

?>