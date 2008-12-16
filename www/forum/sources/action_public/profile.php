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
|   > $Date: 2007-09-11 12:37:52 -0400 (Tue, 11 Sep 2007) $
|   > $Revision: 1102 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > User Profile functions
|   > Module written by Matt Mecham
|   > Date started: 28th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

/**
* Public Action Class: Profile
*
* @package		InvisionPowerBoard
* @subpackage	Public-Action
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IN_IPB' ) )
{
	/**
	* Error checking
	*/
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Public Action Class: Profile
*
* @package	InvisionPowerBoard
* @subpackage	Public-Action
* @author   Matt Mecham
* @version	2.1
*/

class profile
{
    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";

    var $member     = array();
    var $m_group    = array();
    
    var $jump_html  = "";
    var $parser     = "";
    
    var $links      = array();
    
    var $bio        = "";
    var $notes      = "";
    var $size       = "m";
    
    var $show_photo = "";
    var $show_width = "";
    var $show_height = "";
    var $show_name  = "";
    
    var $photo_member = "";
    
    var $has_photo   = FALSE;
    
    var $lib;
    
    function auto_run()
    {
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
    	
    	//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
    	$this->ipsclass->load_language('lang_profile');
    	$this->ipsclass->load_template('skin_profile');
    	
    	$this->ipsclass->base_url_nosess = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}";
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case '03':
				if ( $this->ipsclass->vars['pp_show_classic'] )
				{
    				$this->view_profile();
				}
				else
				{
					$this->personal_portal_view();
				}
    			break;
    			
    		case 'showphoto':
    			$this->show_photo();
    			break;
    			
    		case 'showcard':
    			$this->show_card();
    			break;
    			
    		case 'show-display-names':
    			$this->show_display_names();
    			break;
    		
			case 'personal_ajax_load_tab':
				$this->personal_ajax_load_tab();
				break;
				
			case 'personal_ajax_add_comment':
				$this->personal_ajax_add_comment();
				break;
			
			case 'personal_ajax_delete_comment':
				$this->personal_ajax_delete_comment();
				break;
				
			case 'personal_ajax_reload_comments':
				$this->personal_ajax_reload_comments();
				break;
				
    		case 'personal_portal_view':
				if ( $this->ipsclass->vars['pp_show_classic'] )
				{
    				$this->view_profile();
				}
				else
				{
					$this->personal_portal_view();
				}
				break;
			
			case 'personal_iframe_friends':
				$this->personal_iframe_friends();
				break;
				
			case 'personal_iframe_settings':
				$this->personal_iframe_settings();
				break;
				
			case 'personal_iframe_settings_save':
				$this->personal_iframe_settings_save();
				break;
				
			case 'personal_iframe_comments':
				$this->personal_iframe_comments();
				break;
			case 'personal_iframe_comments_save':
				$this->personal_iframe_comments_save();
				break;
				
			case 'friends_list_popup':
				switch( $this->ipsclass->input['do'] )
				{
					default:
					case 'list':
						$this->friends_list_list();
					break;
					case 'add':
						$this->friends_list_add();
					break;
					case 'remove':
						$this->friends_list_remove();
					break;
					case 'friends_list_moderation':
						$this->friends_list_moderation();
					break;
				}
				break;
				
    		default:
    			if ( $this->ipsclass->vars['pp_show_classic'] )
				{
    				$this->view_profile();
				}
				else
				{
					$this->personal_portal_view();
				}
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 1, 'NAV' => $this->nav ) );
 	}
	
	/*-------------------------------------------------------------------------*/
 	// FRIENDS LIST: Remove a friend
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Remove a friend
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-09
 	*/
 	function friends_list_remove()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$friend_id 		  = intval( $this->ipsclass->input['member_id'] );
		$md5check  		  = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$tab        	  = substr( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['tab'] ), 0, 20 );
		$friend    		  = array();
		$member    		  = array();
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab='.$tab );
			exit();
    	}

		//-----------------------------------------
		// Get friend...
		//-----------------------------------------
		
		$friend = $this->personal_function_load_member( $friend_id );
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $this->ipsclass->member['id'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $friend['id'] OR ! $member['id'] )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab='.$tab );
			exit();
		}
		
		//-----------------------------------------
		// NOT PENDING...
		//-----------------------------------------
		
		if ( $tab != 'pending' )
		{
			//-----------------------------------------
			// Already a friend?
			//-----------------------------------------
		
			$friend_check = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'friends_id',
																			  'from'   => 'profile_friends',
																			  'where'  => "friends_member_id=".$this->ipsclass->member['id']." AND friends_friend_id=".$friend['id'] ) );
																		
			if ( ! $friend_check['friends_id'] )
			{
				$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab='.$tab );
				exit();
			}
		
			//-----------------------------------------
			// Remove from the DB
			//-----------------------------------------
		
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'profile_friends',
															  'where'  => 'friends_id='.$friend_check['friends_id'] ) );
		}
		//-----------------------------------------
		// PENDING...
		//-----------------------------------------
		else
		{
			//-----------------------------------------
			// Already a friend?
			//-----------------------------------------
		
			$friend_check = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'friends_id',
																			  'from'   => 'profile_friends',
																			  'where'  => "friends_member_id=".$friend['id']." AND friends_friend_id=".$this->ipsclass->member['id'] ) );
																		
			if ( ! $friend_check['friends_id'] )
			{
				$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab='.$tab );
				exit();
			}
		
			//-----------------------------------------
			// Remove from the DB
			//-----------------------------------------
		
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'profile_friends',
															  'where'  => 'friends_id='.$friend_check['friends_id'] ) );
		}
		
		//-----------------------------------------
		// Recache..
		//-----------------------------------------
		
		$this->personal_function_recache_members_friends( $member );
		$this->personal_function_recache_members_friends( $friend );
													
		//-----------------------------------------
		// Bounce
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=pp_friend_removed&tab='.$tab );
	}
	
	/*-------------------------------------------------------------------------*/
 	// FRIENDS LIST: Approve a friend
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Moderate pending friends
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-09
 	*/
 	function friends_list_moderation()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$md5check  		   = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$friends    	   = array();
		$friend_ids        = array();
		$friend_member_ids = array();
		$_friend_ids       = array();
		$friends_already   = array();
		$friends_update    = array();
		$member    		   = array();
		$pp_option         = trim( $this->ipsclass->input['pp_option'] );
		$message		   = '';
		$subject		   = '';
		$msg               = 'pp_friend_approved';
		
		//-----------------------------------------
    	// Get the emailer module
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/classes/class_email.php" );
		
		$email           =  new emailer();
		$email->ipsclass =& $this->ipsclass;
		$email->email_init();
		
		//-----------------------------------------
		// Get MSG library class
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_msg.php' );
 		
		$msg_lib           =  new func_msg();
		$msg_lib->ipsclass =& $this->ipsclass;
		$msg_lib->init();
		$this->ipsclass->load_language( 'lang_msg' );
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab=pending' );
			exit();
    	}

		//-----------------------------------------
		// Get friends...
		//-----------------------------------------
		
		if ( ! is_array( $_POST['pp_friend_id'] ) OR ! count( $_POST['pp_friend_id'] ) )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// Figure IDs
		//-----------------------------------------
		
		foreach( $_POST['pp_friend_id'] as $key => $value )
		{
			$_key = intval( $key );
			
			if ( $_key )
			{
				$_friend_ids[ $_key ] = $_key;
			}
		}
		
		if ( ! is_array( $_friend_ids ) OR ! count( $_friend_ids ) )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// Check our friends are OK
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'profile_friends',
												 'where'  => 'friends_friend_id='.$this->ipsclass->member['id'].' AND friends_approved=0 AND friends_member_id IN ('.implode(',',$_friend_ids ). ')' ) );
												
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$friend_ids[ $row['friends_id'] ]               = $row['friends_id'];
			$friend_member_ids[ $row['friends_member_id'] ] = $row['friends_member_id'];
		}
		
		if ( ! is_array( $friend_ids ) OR ! count( $friend_ids ) )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// Load friends...
		//-----------------------------------------
		
		$friends = $this->personal_function_load_member( $friend_member_ids );
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $this->ipsclass->member['id'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! is_array( $friends ) OR ! count( $friends ) OR ! $member['id'] )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error&tab=pending' );
			exit();
		}
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		if ( $pp_option == 'delete' )
		{
			//-----------------------------------------
			// Ok.. delete them in the DB.
			//-----------------------------------------
		
			$this->ipsclass->DB->do_delete( 'profile_friends', 'friends_id IN('.implode(',', $friend_ids ) . ')' );
			
			$msg = 'pp_friend_removed';
		}
		else
		{
			//-----------------------------------------
			// Ok.. approve them in the DB.
			//-----------------------------------------
		
			$this->ipsclass->DB->do_update( 'profile_friends', array( 'friends_approved' => 1 ), 'friends_id IN('.implode(',', $friend_ids ) . ')' );
			
			//-----------------------------------------
			// Reciprocal mode?
			//-----------------------------------------
			
			if ( $pp_option == 'add_reciprocal' )
			{
				//-----------------------------------------
				// Find out who isn't already on your list...
				//-----------------------------------------
				
				$this->ipsclass->DB->build_query( array( 'select' => '*',
														 'from'   => 'profile_friends',
														 'where'  => 'friends_member_id='.$this->ipsclass->member['id'].' AND friends_approved=1 AND friends_friend_id IN ('.implode(',',$_friend_ids ). ')' ) );

				$this->ipsclass->DB->exec_query();

				while( $row = $this->ipsclass->DB->fetch_row() )
				{
					$friends_already[ $row['friends_friend_id'] ] = $row['friends_friend_id'];
				}
				
				//-----------------------------------------
				// Check which aren't already members...	
				//-----------------------------------------
				
				foreach( $friend_member_ids as $id => $_id )
				{
					if ( in_array( $id, $friends_already ) )
					{
						continue;
					}
					
					$friends_update[ $id ] = $id;
				}
				
				//-----------------------------------------
				// Gonna do it?
				//-----------------------------------------
				
				if ( is_array( $friends_update ) AND count( $friends_update ) )
				{
					foreach( $friends_update as $id => $_id )
					{
						$this->ipsclass->DB->do_insert( 'profile_friends', array( 'friends_member_id' => $member['id'],
																				  'friends_friend_id' => $id,
																				  'friends_approved'  => 1,
																				  'friends_added'     => time() ) );
					}
				}
			}
			
			//-----------------------------------------
			// Send out message...
			//-----------------------------------------
			
			foreach( $friends as $friend )
			{
				//-----------------------------------------
				// INIT
				//-----------------------------------------
				
				$message = '';
				$subject = '';
				
				if ( $friend['pp_setting_notify_friend'] )
				{
					$email->get_template("new_friend_approved");
				
					$email->build_message( array( 'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
												  'FRIEND_NAME'          => $member['members_display_name'],
												  'LINK'				 => $this->ipsclass->vars['board_url'] . '/index.' . $this->ipsclass->vars['php_ext'] . '?act=profile&CODE=personal_portal_view&tab=settings&id='.$friend['id'] ) );
			 
					$message    = $email->message;
					$subject    = $email->lang_subject;
					$return_msg = '';
				}
		
				//-----------------------------------------
				// Got anything to send?
				//-----------------------------------------
		
				if ( $message AND $subject )
				{
					//-----------------------------------------
					// Email?
					//-----------------------------------------
			
					if ( $friend['pp_setting_notify_friend'] == 'email' OR $friend['members_disable_pm'] )
					{
						$email->subject = $subject;
						$email->message = $message;
						$email->to      = $friend['email'];
				
						$email->send_mail();
					}
			
					//-----------------------------------------
					// PM?
					//-----------------------------------------
			
					else
					{
						$msg_lib->to_by_id    = $friend['id'];
		 				$msg_lib->from_member = $member;
		 				$msg_lib->msg_title   = $subject;
		 				
		 				$msg_lib->postlib->parser->parse_bbcode 	= 1;
		 				$msg_lib->postlib->parser->parse_smilies 	= 0;
		 				$msg_lib->postlib->parser->parse_html 		= 0;
		 				$msg_lib->postlib->parser->parse_nl2br 		= 1;
		 				$msg_lib->msg_post    = $msg_lib->postlib->parser->pre_display_parse( $msg_lib->postlib->parser->pre_db_parse( $message ) );

						$msg_lib->force_pm    = 1;
				
						$msg_lib->send_pm();
					}
				}
			}
			
			$this->personal_function_recache_members_friends( $friend );
		}
		
		//-----------------------------------------
		// Recache..
		//-----------------------------------------
		
		$this->personal_function_recache_members_friends( $member );
		
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg='.$msg.'&tab=pending' );
	}
	
	/*-------------------------------------------------------------------------*/
 	// FRIENDS LIST: Add a friend
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Add a friend
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-09
 	*/
 	function friends_list_add()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$friend_id 		  = intval( $this->ipsclass->input['member_id'] );
		$md5check  		  = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$friend    		  = array();
		$member    		  = array();
		$friends_approved = 1;
		$message		  = '';
		$subject		  = '';
		$to               = array();
		$from             = array();
		$return_msg       = '';
		
		//-----------------------------------------
    	// Get the emailer module
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/classes/class_email.php" );
		
		$email           =  new emailer();
		$email->ipsclass =& $this->ipsclass;
		$email->email_init();
		
		//-----------------------------------------
		// Get MSG library class
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_msg.php' );
 		
		$msg_lib           =  new func_msg();
		$msg_lib->ipsclass =& $this->ipsclass;
		$msg_lib->init();
		$this->ipsclass->load_language( 'lang_msg' );
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error' );
			exit();
    	}

		//-----------------------------------------
		// Adding yourself?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] == $friend_id )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error' );
			exit();
    	}
		
		//-----------------------------------------
		// Get friend...
		//-----------------------------------------
		
		$friend = $this->personal_function_load_member( $friend_id );
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $this->ipsclass->member['id'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $friend['id'] OR ! $member['id'] )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=error' );
			exit();
		}
		
		//-----------------------------------------
		// Already a friend?
		//-----------------------------------------
		
		$friend_check = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'friends_id',
																		  'from'   => 'profile_friends',
																		  'where'  => "friends_member_id=".$this->ipsclass->member['id']." AND friends_friend_id=".$friend['id'] ) );
																		
		if ( $friend_check['friends_id'] )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg=pp_friend_already' );
			exit();
		}
		
		//-----------------------------------------
		// Friend requires approval?
		//-----------------------------------------
		
		if ( $friend['pp_setting_moderate_friends'] )
		{
			$friends_approved = 0;
		}
		
		//-----------------------------------------
		// Add to DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'profile_friends', array( 'friends_member_id' => $member['id'],
																  'friends_friend_id' => $friend['id'],
																  'friends_approved'  => $friends_approved,
																  'friends_added'     => time() ) );
																
		//-----------------------------------------
		// What are we doing? Sending out 'mod' or 'yay'
		// message?
		//-----------------------------------------
		
		if ( ! $friends_approved AND $friend['pp_setting_notify_friend'] )
		{
			$email->get_template("new_friend_request");
				
			$email->build_message( array( 'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
										  'FRIEND_NAME'          => $member['members_display_name'],
										  'LINK'				 => $this->ipsclass->vars['board_url'] . '/index.' . $this->ipsclass->vars['php_ext'] . '?act=profile&CODE=personal_portal_view&tab=settings&id='.$friend['id'] ) );
			 
			$message    = $email->message;
			$subject    = $email->lang_subject;
			$to         = $friend;
			$from       = $member;
			$return_msg = 'pp_friend_added_mod';
		}
		else if ( $friend['pp_setting_notify_friend'] != 'none' )
		{
			$email->get_template("new_friend_added");

			$email->build_message( array( 'MEMBERS_DISPLAY_NAME' => $friend['members_display_name'],
										  'FRIEND_NAME'          => $member['members_display_name'],
										  'LINK'				 => $this->ipsclass->vars['board_url'] . '/index.' . $this->ipsclass->vars['php_ext'] . '?act=profile&CODE=personal_portal_view&tab=settings&id='.$friend['id'] ) );

			$message    = $email->message;
			$subject    = $email->lang_subject;
			$to         = $friend;
			$from       = $member;
			$return_msg = 'pp_friend_added';
		}
		
		//-----------------------------------------
		// Got anything to send?
		//-----------------------------------------
		
		if ( $message AND $subject )
		{
			//-----------------------------------------
			// Email?
			//-----------------------------------------
			
			if ( $friend['pp_setting_notify_friend'] == 'email' OR ( $friend['pp_setting_notify_friend'] AND $friend['members_disable_pm'] ) )
			{
				$email->subject = $subject;
				$email->message = $message;
				$email->to      = $to['email'];
				
				$email->send_mail();
			}
			
			//-----------------------------------------
			// PM?
			//-----------------------------------------
			
			else if ( $friend['pp_setting_notify_friend'] )
			{
				$msg_lib->to_by_id    = $to['id'];
 				$msg_lib->from_member = $from;
 				$msg_lib->msg_title   = $subject;
 				
 				$msg_lib->postlib->parser->parse_bbcode 	= 1;
 				$msg_lib->postlib->parser->parse_smilies 	= 0;
 				$msg_lib->postlib->parser->parse_html 		= 0;
 				$msg_lib->postlib->parser->parse_nl2br 		= 1;
 				$msg_lib->msg_post    = $msg_lib->postlib->parser->pre_display_parse( $msg_lib->postlib->parser->pre_db_parse( $message ) );
		 				
				$msg_lib->force_pm    = 1;
				
				$msg_lib->send_pm();
			}
		}
		
		//-----------------------------------------
		// Recache..
		//-----------------------------------------
		
		$this->personal_function_recache_members_friends( $member );
		$this->personal_function_recache_members_friends( $friend );
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . '&act=profile&CODE=friends_list_popup&___msg='.$return_msg );
	}
	
	/*-------------------------------------------------------------------------*/
 	// FRIENDS LIST: List all friends
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* List all current friends.
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-08
 	*/
 	function friends_list_list()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content    	= '';
		$member_id  	= intval( $this->ipsclass->member['id'] );
		$friends    	= array();
		$tab        	= substr( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['tab'] ), 0, 20 );
		$friends_filter = substr( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['friends_filter'] ), 0, 20 );
		$_mutual_ids    = array( 0 => 0 );
		$query      	= '';
		$join_field	    = '';
		$time_limit     = time() - $this->ipsclass->vars['au_cutoff'] * 60;
		$per_page       = 25;
		$start          = intval( $this->ipsclass->input['st'] );
		
		//-----------------------------------------
		// Check we're a member
		//-----------------------------------------
		
		if ( ! $member_id )
		{
			print '';
			exit();
		}
		
		//-----------------------------------------
		// To what are we doing to whom?
		//-----------------------------------------
		
		if ( $tab == 'pending' )
		{
			$query      = 'f.friends_friend_id='.$member_id.' AND f.friends_approved=0';
			$join_field = 'f.friends_member_id';
		}
		else if ( $tab == 'mutual' AND $friends_filter == 'added' )
		{
			$query      = 'f.friends_friend_id='.$member_id.' AND f.friends_approved=1';
			$join_field = 'f.friends_member_id';
		}
		else if ( $tab == 'mutual' )
		{
			# My friends...
			$this->ipsclass->DB->build_query( array( 'select' => '*',
													 'from'   => 'profile_friends',
													 'where'  => 'friends_member_id='.$member_id.' AND friends_approved=1' ) );
													
			$this->ipsclass->DB->exec_query();
			
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$_mutual_ids[] = $row['friends_friend_id'];
			}
			
			$query      = 'f.friends_member_id IN ('.implode(',', $_mutual_ids).') AND f.friends_friend_id='.$member_id.' AND f.friends_approved=1';
			$join_field = 'f.friends_member_id';
		}
		else
		{
			$query      = 'f.friends_member_id='.$member_id;
			$join_field = 'f.friends_friend_id';
		}
		
		//-----------------------------------------
		// Filtered?
		//-----------------------------------------
		
		if ( $friends_filter == 'online' )
		{
			$query .= " AND ( ( m.last_visit > $time_limit OR m.last_activity > $time_limit ) AND m.login_anonymous='0&1' )";
		}
		else if ( $friends_filter == 'offline' )
		{
			$query .= " AND ( m.last_activity < $time_limit OR ( m.login_anonymous='0&0' OR m.login_anonymous='1&0' ) )";
		}
		
		//-----------------------------------------
		// Get count...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'COUNT(*) as count',
												 'from'     => array( 'profile_friends' => 'f' ),
												 'where'    => $query,
												 'add_join' => array( 0 => array( 'select' => '',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id='.$join_field,
																				  'type'   => 'inner' ) ) ) );
		$this->ipsclass->DB->exec_query();
		
		$count = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		// Pages...
		//----------------------------------------- 
		
		$pages = $this->ipsclass->build_pagelinks( array(  'TOTAL_POSS'  => intval( $count['count'] ),
														   'no_dropdown' => 1,
												   	 	   'PER_PAGE'    => $per_page,
														   'CUR_ST_VAL'  => $start,
														   'L_SINGLE'    => "",
														   'BASE_URL'    => $this->ipsclass->base_url . 'act=profile&member_id='.$member_id.'&CODE=friends_list_popup&tab='.$tab.'&friends_filter='.$friends_filter,
														 ) );
		//-----------------------------------------
		// Get current friends...	
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'f.*',
												 'from'     => array( 'profile_friends' => 'f' ),
												 'where'    => $query,
												 'order'    => 'm.members_l_display_name ASC',
												 'limit'    => array( $start, $per_page ),
												 'add_join' => array( 0 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id='.$join_field,
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id='.$join_field,
																				  'type'   => 'left' ),
																 	  2 => array( 'select' => 'm.*',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id='.$join_field,
																				  'type'   => 'left' ) ) ) );
		$this->ipsclass->DB->exec_query();
		
		//-----------------------------------------
		// Get and store...
		//-----------------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Others...
			//-----------------------------------------
			
			$row['_last_active']   = $this->ipsclass->get_date( $row['last_activity'], 'SHORT' );
			
			if( $row['login_anonymous']{0} == '1' )
			{
				// Member last logged in anonymous
				
				if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
				{
					$row['_last_active'] = $this->ipsclass->lang['private'];
				}
			}

			$row['_friends_added'] = $this->ipsclass->get_date( $row['friends_added'], 'SHORT' );
			$row['g_title']        = $this->ipsclass->cache['group_cache'][ $row['mgroup'] ]['g_title'];
			
			$row = $this->personal_portal_set_information( $row );
				
			//-----------------------------------------
			// Add row...
			//-----------------------------------------
			
			$friends[] = $row;
		}
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$content = $this->ipsclass->compiled_templates['skin_profile']->friends_list_list( $friends, $pages );
		
		$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['m_title_friends'], $content );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Recaches member's friends
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Recaches member's friends
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-09
 	*/
 	function personal_function_recache_members_friends( $member )
 	{
		//-----------------------------------------
		// INIT	
		//-----------------------------------------
		
		$friends = array();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $member['id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Get current friends...	
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => '*',
												 'from'     => 'profile_friends',
												 'where'    => 'friends_member_id='.$member['id'] ) );
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$friends[ $row['friends_friend_id'] ] = $row['friends_approved'];
		}
		
		//-----------------------------------------
		// Update DB
		//-----------------------------------------
		
		$this->ipsclass->pack_and_update_member_cache( $member['id'], array( 'friends' => $friends ) );
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
 	// Load member...
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Loads the member
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_function_load_member( $member_id=0 )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$query = '';
		
		//-----------------------------------------
		// What do we have?
		//-----------------------------------------
		
		if ( is_array( $member_id ) )
		{
			$query = 'm.id IN ('.implode( ',', $member_id ) . ')';
		}
		else
		{
			$query = 'm.id='.intval($member_id);
		}
		
		//-----------------------------------------
		// Load member
		//-----------------------------------------
	
		$this->ipsclass->DB->build_query( array( 'select'   => 'm.*',
												 'from'     => array( 'members' => 'm' ),
												 'where'    => $query,
												 'add_join' => array( 0 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id=m.id',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=m.id',
																				  'type'   => 'left' ),
														   			  2 => array( 'select' => 'g.*',
																				  'from'   => array( 'groups' => 'g' ),
																				  'where'  => 'g.g_id=m.mgroup',
																				  'type'   => 'left' ),
																	  3 => array( 'select' => 's.location_1_id, s.location_2_id, s.location_1_type, s.location_2_type, s.running_time, s.location as sesslocation',
																	 			  'from'   => array( 'sessions' => 's' ),
																				  'where'  => "s.member_id=m.id",
																				  'type'   => 'left' ),
																	  4 => array( 'select' => 'pc.*',
																				  'from'   => array( 'pfields_content' => 'pc' ),
																				  'where'  => 'pc.member_id=m.id',
																				  'type'   => 'left' ) ) ) );
		$this->ipsclass->DB->exec_query();
		
		if ( is_array( $member_id ) )
		{
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$member[ $row['id'] ] = $row;
			}
		}
		else
		{
			$member = $this->ipsclass->DB->fetch_row();
		
			$member['pp_setting_count_visitors'] = ( $member['pp_setting_count_visitors'] != 0 ) ? $member['pp_setting_count_visitors'] : 5;
			$member['pp_setting_count_comments'] = ( $member['pp_setting_count_comments'] != 0)  ? $member['pp_setting_count_comments'] : 5;
			$member['pp_setting_count_friends']  = ( $member['pp_setting_count_friends'] != 0 )  ? $member['pp_setting_count_friends']  : 5;
		}
		
		return $member;
	}
	
	/*-------------------------------------------------------------------------*/
 	// Updates comments
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Updates the comments
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-15
 	*/
 	function personal_iframe_comments_save()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id 		 = intval( $this->ipsclass->input['member_id'] );
		$md5check  		 = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$content   		 = '';
		$comment_ids     = array();
		$final_ids       = '';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		print '';
			exit();
    	}

		//-----------------------------------------
		// My tab?
		//-----------------------------------------
		
		if (  ( $member_id != $this->ipsclass->member['id'] ) AND ( ! $this->ipsclass->member['g_is_supmod'] ) )
    	{
    		print '';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			print '';
			exit();
    	}

		//-----------------------------------------
		// Grab comment_ids
		//-----------------------------------------
		
		if ( is_array( $_POST['pp-checked'] ) AND count( $_POST['pp-checked'] ) )
		{
			foreach( $_POST['pp-checked'] as $key => $value )
			{
				$key = intval( $key );
				
				if ( $value )
				{
					$comment_ids[ $key ] = $key;
				}
			}
		}
	
		//-----------------------------------------
		// Update the database...
		//-----------------------------------------
		
		if ( is_array( $comment_ids ) AND count( $comment_ids ) )
		{
			$final_ids = implode( ',', $comment_ids );
			
			//-----------------------------------------
			// Now update...
			//-----------------------------------------

			switch( $this->ipsclass->input['pp-moderation'] )
			{
				case 'approve':
					$this->ipsclass->DB->do_update( 'profile_comments', array( 'comment_approved' => 1 ), 'comment_id IN('.$final_ids.')' );
					break;
				case 'unapprove':
					$this->ipsclass->DB->do_update( 'profile_comments', array( 'comment_approved' => 0 ), 'comment_id IN('.$final_ids.')' );
					break;
				case 'delete':
					$this->ipsclass->DB->do_delete( 'profile_comments', 'comment_id IN('.$final_ids.')' );
					break;
			}
		}
		
		//-----------------------------------------
		// Bounce...
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=profile&member_id='.$member_id.'&CODE=personal_iframe_comments&_saved=1&___msg=pp_comments_updated&md5check='.$this->ipsclass->md5_check );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Load friends tab
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Loads the content for the friends tab
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-15
 	*/
 	function personal_iframe_friends()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id 		 = intval( $this->ipsclass->input['member_id'] );
		$md5check  		 = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$content   		 = '';
		$friends_perpage = 10;
		$pages           = '';
		$start			 = intval( $this->ipsclass->input['st'] );
		$friends         = array();
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		print '';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			print '';
			exit();
    	}

		//-----------------------------------------
		// How many comments must a man write down
		// before he is considered a spammer?
		//-----------------------------------------
		
		$friend_count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as friend_count',
																		  'from'   => 'profile_friends',
																		  'where'  => 'friends_member_id='.$member_id . ' AND friends_approved=1' ) );
																		
		//-----------------------------------------
		// Pages
		//-----------------------------------------
		
		$pages = $this->ipsclass->build_pagelinks( array(  'TOTAL_POSS'  => intval( $friend_count['friend_count'] ),
														   'no_dropdown' => 1,
												   	 	   'PER_PAGE'    => $friends_perpage,
														   'CUR_ST_VAL'  => $start,
														   'L_SINGLE'    => "",
														   'BASE_URL'    => $this->ipsclass->base_url . 'act=profile&member_id='.$member_id.'&CODE=personal_iframe_friends&md5check='.$this->ipsclass->md5_check,
														 ) );
														
		//-----------------------------------------
		// Grab the friends
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'f.*',
												 'from'     => array( 'profile_friends' => 'f' ),
												 'where'    => 'f.friends_member_id='.$member_id . ' AND f.friends_approved=1',
												 'limit'    => array( $start, $friends_perpage ),
												 'add_join' => array( 0 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id=f.friends_friend_id',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=f.friends_friend_id',
																				  'type'   => 'left' ),
																 	  2 => array( 'select' => 'm.*',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id=f.friends_friend_id',
																				  'type'   => 'left' ) ) ) );
		$this->ipsclass->DB->exec_query();
		
		//-----------------------------------------
		// Get and store...
		//-----------------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Others...
			//-----------------------------------------
			
			$row['_last_active']   = $this->ipsclass->get_date( $row['last_activity'], 'SHORT' );
			
			if( $row['login_anonymous']{0} == '1' )
			{
				// Member last logged in anonymous
				
				if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
				{
					$row['_last_active'] = $this->ipsclass->lang['private'];
				}
			}

			$row['_friends_added'] = $this->ipsclass->get_date( $row['friends_added'], 'SHORT' );
			$row['g_title']        = $this->ipsclass->cache['group_cache'][ $row['mgroup'] ]['g_title'];
			
			//-----------------------------------------
			// Add row...
			//-----------------------------------------
			
			$friends[ $row['members_display_name'] ] = $this->personal_portal_set_information( $row );
		}
		
		//-----------------------------------------
		// Sort
		//-----------------------------------------
		
		ksort( $friends );
		
		//-----------------------------------------
		// Ok.. show the friends
		//-----------------------------------------
		
		$content = $this->ipsclass->compiled_templates['skin_profile']->personal_portal_iframe_friends( $member, $friends, $pages );
		
		$this->ipsclass->print->pop_up_window( '', $content );
		
	}
	
	/*-------------------------------------------------------------------------*/
 	// Load comments tab
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Loads the content for the comments tab
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_iframe_comments()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id 		 = intval( $this->ipsclass->input['member_id'] );
		$md5check  		 = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$content   		 = '';
		$comment_perpage = 10;
		$pages           = '';
		$start			 = intval( $this->ipsclass->input['st'] );
		$sql_extra       = '';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		print '';
			exit();
    	}

		//-----------------------------------------
		// Not my tab? So no moderation...
		//-----------------------------------------
		
		if (  ( $member_id != $this->ipsclass->member['id'] ) AND ( ! $this->ipsclass->member['g_is_supmod'] ) )
    	{
    		$sql_extra = ' AND comment_approved=1';
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			print '';
			exit();
    	}

		//-----------------------------------------
		// How many comments must a man write down
		// before he is considered a spammer?
		//-----------------------------------------
		
		$comment_count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as count_comment',
																		   'from'   => 'profile_comments',
																		   'where'  => 'comment_for_member_id='.$member_id . $sql_extra ) );
																		
		//-----------------------------------------
		// Pages
		//-----------------------------------------
		
		$pages = $this->ipsclass->build_pagelinks( array(  'TOTAL_POSS'  => intval( $comment_count['count_comment'] ),
												   	 	   'PER_PAGE'    => $comment_perpage,
														   'CUR_ST_VAL'  => $start,
														   'L_SINGLE'    => "",
														   'BASE_URL'    => $this->ipsclass->base_url . 'act=profile&member_id='.$member_id.'&CODE=personal_iframe_comments&md5check='.$this->ipsclass->md5_check,
														 ) );
												
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'pc.*',
												 'from'     => array( 'profile_comments' => 'pc' ),
												 'where'    => 'pc.comment_for_member_id='.$member_id . $sql_extra,
												 'order'    => 'pc.comment_date DESC',
												 'limit'    => array( $start, $comment_perpage ),
												 'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id=pc.comment_by_member_id',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=m.id',
																				  'type'   => 'left' ),	
																	  2 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id=pc.comment_by_member_id',
																				  'type'   => 'left' ) ) ) );
																				
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['_comment_date']   = $this->ipsclass->get_date( $row['comment_date'], 'SHORT' );
			$row['_avatar']         = $this->ipsclass->get_avatar( $row['avatar_location'] , 1, $row['avatar_size'], $row['avatar_type'] );
			$row['_last_active']    = $this->ipsclass->get_date( $row['last_activity'], 'SHORT' );
			
			if( $row['login_anonymous']{0} == '1' )
			{
				// Member last logged in anonymous
				
				if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
				{
					$row['_last_active'] = $this->ipsclass->lang['private'];
				}
			}

			$row['comment_content'] = $this->ipsclass->txt_wordwrap( $row['comment_content'], '19', ' ' );
			
			$row = $this->personal_portal_set_information( $row, 0, 0 );
			
			$comments[] = $row;
		}

		//-----------------------------------------
		// Ok.. show the settings
		//-----------------------------------------
		
		$content = $this->ipsclass->compiled_templates['skin_profile']->personal_portal_iframe_comments( $member, $comments, $pages );
		
		$this->ipsclass->print->pop_up_window( '', $content );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Save settings tab
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Saves the content for the settings tab
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_iframe_settings_save()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id 					  = intval( $this->ipsclass->input['member_id'] );
		$md5check  					  = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$pp_setting_notify_comments   = trim( substr( $this->ipsclass->input['pp_setting_notify_comments'], 0, 10 ) );
		$pp_setting_notify_friend     = trim( substr( $this->ipsclass->input['pp_setting_notify_friend'], 0, 10 ) );
		$pp_setting_moderate_comments = intval( $this->ipsclass->input['pp_setting_moderate_comments'] );
		$pp_setting_moderate_friends  = intval( $this->ipsclass->input['pp_setting_moderate_friends'] );
		$pp_bio_content				  = $this->ipsclass->txt_mbsubstr( $this->ipsclass->my_nl2br( $this->ipsclass->input['pp_bio_content'] ), 0, 300 );
		$website					  = trim( $this->ipsclass->input['website'] );
		
		//-----------------------------------------
		// Settings...
		//-----------------------------------------
		
		foreach( array( 'pp_setting_count_friends', 'pp_setting_count_comments', 'pp_setting_count_visitors' ) as $item )
		{
			$_val = 0;
			
			switch( $this->ipsclass->input[ $item ] )
			{
				default:
				case '3':
					$_val = 3;
					break;
				case '5':
					$_val = 5;
					break;
				case '10':
					$_val = 10;
					break;
				case '0':
					$_val = -1;
					break;
			}
			
			${$item} = $_val;
		}
		
		//-----------------------------------------
		// Clean website...
		//-----------------------------------------
		
		$website = ( preg_match( "#^http://[a-z0-9\.\-].*$#i", $website ) ) ? $website : '';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=profile&amp;member_id='.$member_id.'&amp;CODE=personal_iframe_settings&amp;___msg=no_permission&md5check='.$this->ipsclass->md5_check );
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
		
		//-----------------------------------------
		// My tab?
		//-----------------------------------------
		
		if (  ( $member_id != $this->ipsclass->member['id'] ) AND ( ! $this->ipsclass->member['g_is_supmod'] OR ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] ) ) )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=profile&amp;member_id='.$member_id.'&amp;CODE=personal_iframe_settings&amp;___msg=no_permission&md5check='.$this->ipsclass->md5_check );
			exit();
    	}		
		
		if( !$this->ipsclass->member['g_edit_profile'] )
		{
			$website 		= ''; // Setting to nothing will prevent the query
			$pp_bio_content	= $member['pp_bio_content'];
		}
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=profile&amp;member_id='.$member_id.'&amp;CODE=personal_iframe_settings&amp;___msg=no_permission&md5check='.$this->ipsclass->md5_check );
			exit();
    	}
		
    	if ( $this->ipsclass->member['g_edit_profile'] )
    	{
			//-----------------------------------------
			// "Do" photo
			//-----------------------------------------
			
			require_once( ROOT_PATH . 'sources/lib/func_usercp.php' );
			$func_70s_style           =  new func_usercp;
			$func_70s_style->ipsclass =& $this->ipsclass;
			
			$photo = $func_70s_style->lib_upload_photo();
			
			if ( $photo['status'] == 'fail' )
			{
				//-----------------------------------------
				// Save...
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 'pp_member_id',
															  'from'   => 'profile_portal',
															  'where'  => "pp_member_id=".$member_id ) );
				$this->ipsclass->DB->simple_exec();
			
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					# Update...
					$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_main_photo'                => $photo['final_location'],
														  				   	 'pp_main_width'                => intval( $photo['final_width'] ),
																		   	 'pp_main_height'               => intval( $photo['final_height'] ),
																			 'pp_thumb_photo'               => $photo['t_final_location'],
																			 'pp_thumb_width'               => intval( $photo['t_final_width'] ),
																			 'pp_thumb_height'              => intval( $photo['t_final_height'] ),
																		 ), 'pp_member_id='.$member_id );
				}
				else
				{
					# Insert
					$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_main_photo'                => $photo['final_location'],
														  				   	 'pp_main_width'                => intval( $photo['final_width'] ),
																		   	 'pp_main_height'               => intval( $photo['final_height'] ),
																			 'pp_thumb_photo'               => $photo['t_final_location'],
																			 'pp_thumb_width'               => intval( $photo['t_final_width'] ),
																			 'pp_thumb_height'              => intval( $photo['t_final_height'] ),
																			 'pp_member_id'                 => $member_id,
																		 ) );
				}
						
				$this->personal_iframe_settings( $this->ipsclass->lang[ 'pp_' . $photo['error'] ] );
			}
		}
		
		# Preserve old settings?
		if ( ! $photo['final_location'] AND $photo['status'] != 'deleted' )
		{
			$photo['final_location']   = $member['pp_main_photo'];
			$photo['final_width']      = $member['pp_main_width'];
			$photo['final_height']     = $member['pp_main_height'];
			
			$photo['t_final_location'] = $member['pp_thumb_photo'];
			$photo['t_final_width']    = $member['pp_thumb_width'];
			$photo['t_final_height']   = $member['pp_thumb_height'];
		}
			
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pp_member_id',
													  'from'   => 'profile_portal',
													  'where'  => "pp_member_id=".$member_id ) );
		$this->ipsclass->DB->simple_exec();
	
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			# Update...
			$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_main_photo'                => $photo['final_location'],
												  				   	 'pp_main_width'                => intval( $photo['final_width'] ),
																   	 'pp_main_height'               => intval( $photo['final_height'] ),
																	 'pp_thumb_photo'               => $photo['t_final_location'],
																	 'pp_thumb_width'               => intval( $photo['t_final_width'] ),
																	 'pp_thumb_height'              => intval( $photo['t_final_height'] ),
																	 'pp_bio_content'				=> $pp_bio_content,
																	 'pp_setting_notify_comments'   => $pp_setting_notify_comments,
																	 'pp_setting_notify_friend'     => $pp_setting_notify_friend,
																	 'pp_setting_moderate_comments' => $pp_setting_moderate_comments,
																	 'pp_setting_moderate_friends'  => $pp_setting_moderate_friends,
																	 'pp_setting_count_friends'     => $pp_setting_count_friends,
																	 'pp_setting_count_comments'    => $pp_setting_count_comments,
																	 'pp_setting_count_visitors'    => $pp_setting_count_visitors
																 ), 'pp_member_id='.$member_id );
		}
		else
		{
			# Insert
			$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_main_photo'                => $photo['final_location'],
												  				   	 'pp_main_width'                => intval( $photo['final_width'] ),
																   	 'pp_main_height'               => intval( $photo['final_height'] ),
																	 'pp_thumb_photo'               => $photo['t_final_location'],
																	 'pp_thumb_width'               => intval( $photo['t_final_width'] ),
																	 'pp_thumb_height'              => intval( $photo['t_final_height'] ),
																	 'pp_bio_content'				=> $pp_bio_content,
																	 'pp_member_id'                 => $member_id,
																	 'pp_setting_notify_comments'   => $pp_setting_notify_comments,
																	 'pp_setting_notify_friend'     => $pp_setting_notify_friend,
																	 'pp_setting_moderate_comments' => $pp_setting_moderate_comments,
																	 'pp_setting_moderate_friends'  => $pp_setting_moderate_friends,
																	 'pp_setting_count_friends'     => $pp_setting_count_friends,
																	 'pp_setting_count_comments'    => $pp_setting_count_comments,
																	 'pp_setting_count_visitors'    => $pp_setting_count_visitors
																 ) );
		}
		
		//-----------------------------------------
		// Do website...
		//-----------------------------------------
		
		if ( $this->ipsclass->member['g_edit_profile'] AND $website != 'http://' )
		{
			$this->ipsclass->DB->do_update( 'member_extra', array( 'website' => $website ), 'id='. $member_id );
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=profile&member_id='.$member_id.'&CODE=personal_iframe_settings&_saved=1&___msg=settings_updated&md5check='.$this->ipsclass->md5_check );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Load settings tab
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Loads the content for the settings tab
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_iframe_settings( $error='' )
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
	
		$member_id = intval( $this->ipsclass->input['member_id'] );
		$md5check  = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$content   = '';
		$types     = array( 'none'  => $this->ipsclass->lang['op_dd_none'],
					 		'email' => $this->ipsclass->lang['op_dd_email'],
							'pm'    => $this->ipsclass->lang['op_dd_pm'] );
							
		$yes_no    = array( '0'     => $this->ipsclass->lang['op_dd_disabled'],
							'1'     => $this->ipsclass->lang['op_dd_enabled'] );
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		print '';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
		
		//-----------------------------------------
		// My tab?
		//-----------------------------------------
		
		if (  ( $member_id != $this->ipsclass->member['id'] ) AND ( ! $this->ipsclass->member['g_is_supmod'] OR ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] ) ) )
    	{
    		print '';
			exit();
    	}		
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			print '';
			exit();
    	}

		//-----------------------------------------
		// Format settings...
		//-----------------------------------------
		
		foreach( $types as $key => $lang )
		{
			$_comments_selected = ( $key == $member['pp_setting_notify_comments'] ) ? ' selected="selected" ' : '';
			$_friends_selected  = ( $key == $member['pp_setting_notify_friend'] ) ? ' selected="selected" ' : '';
			$member['_pp_setting_notify_comments'] .= "<option value='".$key."'".$_comments_selected.">".$lang."</option>";
			$member['_pp_setting_notify_friend']   .= "<option value='".$key."'".$_friends_selected.">".$lang."</option>";
		}
		
		foreach( $yes_no as $key => $lang )
		{
			$_comments_selected = ( $key == $member['pp_setting_moderate_comments'] ) ? ' selected="selected" ' : '';
			$_friends_selected  = ( $key == $member['pp_setting_moderate_friends'] )  ? ' selected="selected" ' : '';
			$member['_pp_setting_moderate_comments'] .= "<option value='".$key."'".$_comments_selected.">".$lang."</option>";
			$member['_pp_setting_moderate_friends']  .= "<option value='".$key."'".$_friends_selected.">".$lang."</option>";
		}
		
		//-----------------------------------------
		// Get friends pending approval
		//-----------------------------------------
		
		$friends = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count_friends',
																	 'from'   => 'profile_friends',
																	 'where'  => 'friends_friend_id='.$member['id'].' AND friends_approved=0' ) );

		//-----------------------------------------
		// Photo settings...
		//-----------------------------------------
		
		list( $size, $width, $height ) = explode( ':', $this->ipsclass->member['g_photo_max_vars'] );
		$photo_ext                     = str_replace( ',', '/', $this->ipsclass->vars['photo_ext'] );
		
		$this->ipsclass->lang['pp_photo_desc'] = sprintf( $this->ipsclass->lang['pp_photo_desc'], $width, $height, $size, $photo_ext );
		
		# Allow extra for compression
		$member['_max_file_size'] = ( $size * 1024 ) * 4;
		
		//-----------------------------------------
		// Personal statement
		//-----------------------------------------
		
		$member['_pp_bio_content']  = $this->ipsclass->my_br2nl( $member['pp_bio_content'] );
		$member['__pp_bio_content'] = $member['pp_bio_content'];
		
		//-----------------------------------------
		// Ok.. show the settings
		//-----------------------------------------
		
		$content = $this->ipsclass->compiled_templates['skin_profile']->personal_portal_iframe_settings( $member, $friends['count_friends'], $error );
		
		$this->ipsclass->print->pop_up_window( '', $content );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Builds comments
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Builds comments
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
	function personal_build_comments( $member, $new_id=0, $return_msg='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$comments        = array();
		$member_id       = intval( $member['id'] );
		$comment_perpage = intval( $member['pp_setting_count_comments'] );
		$comment_html    = 0;
		
		//-----------------------------------------
		// Choosing to show comments?
		//-----------------------------------------
		
		if ( $comment_perpage < 1 )
		{
			return '';
		}
		
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'pc.*',
												 'from'     => array( 'profile_comments' => 'pc' ),
												 'where'    => 'pc.comment_for_member_id='.$member_id.' AND pc.comment_approved=1',
												 'order'    => 'pc.comment_date DESC',
												 'limit'    => array( 0, $comment_perpage ),
												 'add_join' => array( 0 => array( 'select' => 'm.members_display_name, m.posts, m.last_activity',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id=pc.comment_by_member_id',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=m.id',
																				  'type'   => 'left' ),	
																	  2 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id=pc.comment_by_member_id',
																				  'type'   => 'left' ) ) ) );
																				
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['_comment_date']   = $this->ipsclass->get_date( $row['comment_date'], 'TINY' );
			$row['_avatar']         = $this->ipsclass->get_avatar( $row['avatar_location'] , 1, $row['avatar_size'], $row['avatar_type'] );
			$row['_last_active']    = $this->ipsclass->get_date( $row['last_activity'], 'TINY' );
			
			if( $row['login_anonymous']{0} == '1' )
			{
				// Member last logged in anonymous
				
				if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
				{
					$row['_last_active'] = $this->ipsclass->lang['private'];
				}
			}

			$row['comment_content'] = $this->ipsclass->txt_wordwrap( $row['comment_content'], '19', ' ' );
			
			$row = $this->personal_portal_set_information( $row, 0, 0 );
			
			$comments[] = $row;
		}
		
		$comment_html = $this->ipsclass->compiled_templates['skin_profile']->personal_portal_show_comment( $comments, $member, $new_id, $return_msg );
		
		//-----------------------------------------
		// Return it...
		//-----------------------------------------
		
		return $comment_html;
	}
	
	/*-------------------------------------------------------------------------*/
 	// Reload comments
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Reload comments
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-15
 	*/
 	function personal_ajax_reload_comments()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id       = intval( $this->ipsclass->input['member_id'] );
		$md5check        = substr( $this->ipsclass->input['md5check'], 0, 32 );
		
		//-----------------------------------------
		// Load XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_ajax.php' );
		$class_ajax           = new  class_ajax();
		$class_ajax->ipsclass =& $this->ipsclass;
		$class_ajax->class_init();
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}
		
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$class_ajax->return_html( $this->personal_build_comments( $member ) );
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
 	// Delete comment
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Deletes a comment on member's profile
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_ajax_delete_comment()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id       = intval( $this->ipsclass->input['member_id'] );
		$md5check        = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$comment_id      = intval( $this->ipsclass->input['comment_id'] );
		$comment_html    = "";
		$comment_perpage = 5;
		
		//-----------------------------------------
		// Load XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_ajax.php' );
		$class_ajax           = new  class_ajax();
		$class_ajax->ipsclass =& $this->ipsclass;
		$class_ajax->class_init();
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}

		//-----------------------------------------
		// Can remove?
		//-----------------------------------------
		
		if ( ( $member['id'] == $this->ipsclass->member['id'] ) OR $this->ipsclass->member['g_is_supmod'] )
		{
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'profile_comments',
															  'where'  => 'comment_id='.$comment_id ) );
		}
		
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$class_ajax->return_html( $this->personal_build_comments( $member ) );
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
 	// Save comment
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Saves a comment on member's profile
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_ajax_add_comment()
 	{
		//-----------------------------------------
		// Load XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_ajax.php' );
		$class_ajax           = new  class_ajax();
		$class_ajax->ipsclass =& $this->ipsclass;
		$class_ajax->class_init();
		
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id        = intval( $this->ipsclass->input['member_id'] );
		$md5check         = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$comment          = '';
		$comment_approved = 1;
		$message		  = '';
		$subject		  = '';
		$to               = array();
		$from             = array();
		$return_msg       = '';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'nopermission';
			exit();
    	}
    	
		//-----------------------------------------
		// Member allowed to post at all?
		//-----------------------------------------
		
    	if ( ! $this->ipsclass->member['g_reply_other_topics'] )
    	{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'nopermission';
			exit();
    	}
    	
		if ( $this->ipsclass->member['restrict_post'] )
        {
			if ( $this->ipsclass->member['restrict_post'] == 1 )
         	{
				@header( "Content-type: text/plain" );
				$class_ajax->print_nocache_headers();
				print 'nopermission';
				exit();
			}
         
			$post_arr = $this->ipsclass->hdl_ban_line( $this->ipsclass->member['restrict_post'] );

			if ( time() >= $post_arr['date_end'] )
			{
				//-----------------------------------------
         		// Update this member's profile
				//-----------------------------------------
         
				$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
															  'set'    => 'restrict_post=0',
															  'where'  => "id=".intval($this->ipsclass->member['id'])
													)       );
				$this->ipsclass->DB->simple_exec();
         	}
         	else
         	{
				@header( "Content-type: text/plain" );
				$class_ajax->print_nocache_headers();
				print 'nopermission';
				exit();
			}
		}         

		//-----------------------------------------
		// Does this member have mod_posts enabled?
		//-----------------------------------------
          
		if ( isset($this->ipsclass->member['mod_posts']) AND $this->ipsclass->member['mod_posts'] )
		{
			if ( $this->ipsclass->member['mod_posts'] == 1 )
			{
				$comment_approved = 0;
			}
			else
			{
				$mod_arr = $this->ipsclass->hdl_ban_line( $this->ipsclass->member['mod_posts'] );
				
				if ( time() >= $mod_arr['date_end'] )
				{
					//-----------------------------------------
					// Update this member's profile
					//-----------------------------------------

					$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
																  'set'    => 'mod_posts=0',
																  'where'  => "id=".intval($this->ipsclass->member['id'])
													     )       );
					$this->ipsclass->DB->simple_exec();
				}
				else
				{
					$comment_approved = 0;
				}
			}
		}
 
		//-----------------------------------------
    	// Get the emailer module
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/classes/class_email.php" );
		
		$email           =  new emailer();
		$email->ipsclass =& $this->ipsclass;
		$email->email_init();
		
		//-----------------------------------------
		// Get MSG library class
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_msg.php' );
 		
		$msg_lib           =  new func_msg();
		$msg_lib->ipsclass =& $this->ipsclass;
		$msg_lib->init();
		$this->ipsclass->load_language( 'lang_msg' );
		
		//-----------------------------------------
		// Finish up
		//-----------------------------------------
		
		$_POST['comment'] = $class_ajax->convert_unicode( $_POST['comment'] );
		$_POST['comment'] = $class_ajax->convert_html_entities( $_POST['comment'] );
		
   	   	$comment = $this->ipsclass->parse_clean_value( $_POST['comment'] );
		$comment = $class_ajax->ipsclass->txt_mbsubstr( $comment, 0, 400 );
		$comment = preg_replace( "#(\r\n|\r|\n|<br />|<br>){1,}#s", "\n", $comment );
		
		//-----------------------------------------
		// Bad words
		//-----------------------------------------
		
		$comment = trim( $this->parser->bad_words( $comment ) );
		
		//-----------------------------------------
		// Got a comment?
		//-----------------------------------------
		
		if ( ! $comment )
    	{
    		@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error-no-comment';
			exit();
    	}

		//-----------------------------------------
		// Friend requires approval?
		//-----------------------------------------
		
		if ( $member['pp_setting_moderate_comments'] AND $member['id'] != $this->ipsclass->member['id'] )
		{
			$comment_approved = 0;
		}
		
		//-----------------------------------------
		// Member is ignoring you!
		//-----------------------------------------
		
		if ( $comment_approved )
		{ 
			$_you_are_being_ignored = explode( ",", $member['ignored_users'] );
		
			if ( is_array( $_you_are_being_ignored ) and count( $_you_are_being_ignored ) )
			{
				if ( in_array( $this->ipsclass->member['id'], $_you_are_being_ignored ) )
				{
					$comment_approved = 0;
				}
			}
		}
	
		//-----------------------------------------
		// Add comment to the DB...
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'profile_comments', array( 'comment_for_member_id' => $member_id,
																   'comment_by_member_id'  => $this->ipsclass->member['id'],
																   'comment_date'		   => time(),
																   'comment_ip_address'    => $this->ipsclass->ip_address,
																   'comment_approved'      => $comment_approved,
																   'comment_content'	   => $this->ipsclass->my_nl2br( $comment ) ) );
		
		$new_id = $this->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// What are we doing? Sending out 'mod' or 'yay'
		// message?
		//-----------------------------------------
		
		if ( ! $comment_approved AND $member['pp_setting_notify_comments'] AND ( $member['id'] != $this->ipsclass->member['id'] ) )
		{
			$email->get_template("new_comment_request");
				
			$email->build_message( array( 'MEMBERS_DISPLAY_NAME' => $member['members_display_name'],
										  'COMMENT_NAME'         => $this->ipsclass->member['members_display_name'],
										  'LINK'				 => $this->ipsclass->vars['board_url'] . '/index.' . $this->ipsclass->vars['php_ext'] . '?act=profile&CODE=personal_portal_view&tab=comments&id='.$member_id ) );
			 
			$message    = $email->message;
			$subject    = $email->lang_subject;
			$to         = $member;
			$from       = $this->ipsclass->member;
			$return_msg = 'pp_comment_added_mod';
		}
		else if ( $member['pp_setting_notify_comments'] AND ( $member['id'] != $this->ipsclass->member['id'] ) )
		{
			$email->get_template("new_comment_added");

			$email->build_message( array( 'MEMBERS_DISPLAY_NAME' => $member['members_display_name'],
										  'COMMENT_NAME'         => $this->ipsclass->member['members_display_name'],
										  'LINK'				 => $this->ipsclass->vars['board_url'] . '/index.' . $this->ipsclass->vars['php_ext'] . '?act=profile&CODE=personal_portal_view&tab=comments&id='.$member_id ) );

			$message    = $email->message;
			$subject    = $email->lang_subject;
			$to         = $member;
			$from       = $this->ipsclass->member;
			$return_msg = '';
		}
		
		//-----------------------------------------
		// Got anything to send?
		//-----------------------------------------
		
		if ( $message AND $subject )
		{
			//-----------------------------------------
			// Email?
			//-----------------------------------------
			
			if ( $member['pp_setting_notify_comments'] == 'email' OR ( $member['pp_setting_notify_comments'] AND $member['members_disable_pm'] ) )
			{
				$email->subject = $subject;
				$email->message = $message;
				$email->to      = $to['email'];
				
				$email->send_mail();
			}
			
			//-----------------------------------------
			// PM?
			//-----------------------------------------
			
			else if ( $member['pp_setting_notify_comments'] != 'none' )
			{
				$msg_lib->to_by_id    = $to['id'];
 				$msg_lib->from_member = $from;
 				$msg_lib->msg_title   = $subject;

 				$msg_lib->postlib->parser->parse_bbcode 	= 1;
 				$msg_lib->postlib->parser->parse_smilies 	= 0;
 				$msg_lib->postlib->parser->parse_html 		= 0;
 				$msg_lib->postlib->parser->parse_nl2br 		= 1;
 				$msg_lib->msg_post    = $msg_lib->postlib->parser->pre_display_parse( $msg_lib->postlib->parser->pre_db_parse( $message ) );

				$msg_lib->force_pm    = 1;
				
				$msg_lib->send_pm();
			}
		}
		
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$class_ajax->return_html( $this->personal_build_comments( $member, $new_id, $return_msg ) );
		exit();
	}

	/*-------------------------------------------------------------------------*/
 	// Load personal portal tab content
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Loads the content for the desired tab
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-02
 	*/
 	function personal_ajax_load_tab()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id = intval( $this->ipsclass->input['member_id'] );
		$tab       = substr( $this->ipsclass->txt_alphanumerical_clean( str_replace( '..', '', trim( $this->ipsclass->input['tab'] ) ) ), 0, 20 );
		$md5check  = substr( $this->ipsclass->input['md5check'], 0, 32 );
		
		//-----------------------------------------
		// Load XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_ajax.php' );
		$class_ajax           = new  class_ajax();
		$class_ajax->ipsclass =& $this->ipsclass;
		$class_ajax->class_init();
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
    	}
		
		//-----------------------------------------
		// Load config
		//-----------------------------------------
		
		if( !file_exists( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.conf.php' ) )
		{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
		}
		
		require( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.conf.php' );
		
		//-----------------------------------------
		// Active?
		//-----------------------------------------
		
		if ( ! $CONFIG['plugin_enabled'] )
		{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
		}
		
		//-----------------------------------------
		// Load main class...
		//-----------------------------------------
		
		if( !file_exists( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.php' ) )
		{
			@header( "Content-type: text/plain" );
			$class_ajax->print_nocache_headers();
			print 'error';
			exit();
		}
		
		require( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.php' );
		$_func_name       = 'profile_'.$tab;
		$plugin           =  new $_func_name;
		$plugin->ipsclass =& $this->ipsclass;
		
		$html = $plugin->return_html_block( $member );
		
		//-----------------------------------------
		// Return it...
		//-----------------------------------------
		
		//$html = htmlentities( $html );
		
		$class_ajax->return_html( $html );
	
		exit();
		
	}
	
	/*-------------------------------------------------------------------------*/
 	// View personal portal
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Show's the personal portal for the member
 	*
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-7-28 and most likely vB4.0.0-2006-x-x
 	*/
 	function personal_portal_view()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id       = intval( $this->ipsclass->input['id'] ) ? intval( $this->ipsclass->input['id'] ) : intval( $this->ipsclass->input['MID'] );
		$member_id       = $member_id ? $member_id : $this->ipsclass->member['id'];
		$tab             = substr( $this->ipsclass->txt_alphanumerical_clean( str_replace( '..', '', trim( $this->ipsclass->input['tab'] ) ) ), 0, 20 );
		$tab             = $tab ? $tab : 'topics';
		$member          = array();
		$comments        = array();
		$comments_html   = "";
		$friends         = array();
		$visitors        = array();
		$comment_perpage = 5;
		$pips            = 0;
		$tabs            = array();
		$_tabs           = array();
		$_positions      = array( 0 => 0 );
		$custom_path     = ROOT_PATH . 'sources/components_public/profile';
		$_member_ids     = array();
		$sql_extra       = '';
		$pass            = 0;
    	$mod             = 0;
		$_todays_date    = getdate();
		
		$time_adjust = $this->ipsclass->vars['time_adjust'] == "" ? 0 : $this->ipsclass->vars['time_adjust'];
		$board_posts = $this->ipsclass->cache['stats']['total_topics'] + $this->ipsclass->cache['stats']['total_replies'];
	
		//-----------------------------------------
		// Do we have permission to view profiles?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['g_mem_info'] != 1 )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//-----------------------------------------
    	// Check input..
    	//-----------------------------------------

    	if ( ! $member_id )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url );
    	}

		//-----------------------------------------
		// Configure tabs
		//-----------------------------------------
		
		$handle  = opendir( $custom_path );
		
		while ( ( $file = readdir($handle) ) !== FALSE )
		{
			if ( preg_match( "#\.conf\.php$#i", $file ) )
			{
				$classname = str_replace( ".conf.php", "", $file );
				
				require( $custom_path . "/" . $file );
				
				//-------------------------------
				// Allowed to use?
				//-------------------------------
			
				if ( $CONFIG['plugin_enabled'] )
				{
					if ( $classname != 'posts' && $classname != 'topics' )
					{
						$CONFIG['plugin_order'] += 10;
					}
					
					$_position           = ( in_array( $CONFIG['plugin_order'], $_positions ) ) ? count( $_positions ) + 1 : $CONFIG['plugin_order'];
					$_tabs[ $_position ] = $CONFIG;
				
					$_positions[ $_position ] = $_position;
				}
			}
		}
		
		closedir( $handle );
		
		ksort( $_tabs );
		
		foreach( $_tabs as $_pos => $data )
		{
			$data['_lang']               = array_key_exists( $data['plugin_lang_bit'], $this->ipsclass->lang ) ? $this->ipsclass->lang[ $data['plugin_lang_bit'] ] : $data['plugin_name'];
			$tabs[ $data['plugin_key'] ] = $data;
		}
		
		if( $tab != 'comments' AND $tabl != 'settings' AND !file_exists( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.php' ) )
		{
			$tab = 'topics';
		}

		//-----------------------------------------
		// Grab all data...
		//-----------------------------------------
		
		$member = $this->personal_function_load_member( $member_id );
	
		//-----------------------------------------
		// Got stuff?
		//-----------------------------------------
		
		if ( ! $member['id'] )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url );
    	}
		
		//-----------------------------------------
		// Recent visitor?
		//-----------------------------------------
		
		if ( $member['id'] != $this->ipsclass->member['id'] )
		{
			list( $be_anon, $loggedin ) = explode( '&', $this->ipsclass->member['login_anonymous'] );
			
			if ( ! $be_anon )
			{
				$this->personal_portal_add_recent_visitor( $member, $this->ipsclass->member['id'] );
			}
		}
		
		//-----------------------------------------
		// Custom fields
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
		$custom_fields = new custom_fields( $this->ipsclass->DB );
		
		$custom_fields->member_id  = $this->ipsclass->member['id'];
		$custom_fields->cache_data = $this->ipsclass->cache['profilefields'];
		$custom_fields->admin      = intval($this->ipsclass->member['g_access_cp']);
		$custom_fields->supmod     = intval($this->ipsclass->member['g_is_supmod']);
		
		$custom_fields->member_data = $member;
		$custom_fields->init_data();
		$custom_fields->parse_to_view( 0 );

		if ( count( $custom_fields->out_fields ) )
		{
			foreach( $custom_fields->out_fields as $id => $data )
	    	{
	    		if ( ! $data )
	    		{
	    			$data = $this->ipsclass->lang['no_info'];
	    		}
	    		
	    		$data = $this->ipsclass->txt_wordwrap( $data, '25', ' ' );
    		
				$member['custom_fields'][] = array( 'name' => $custom_fields->field_names[ $id ], 'data' => $data );
	    	}
		}
		
		//-----------------------------------------
		// DST?
		//-----------------------------------------
		
		if ( $member['dst_in_use'] == 1 )
    	{
    		$member['time_offset'] += 1;
    	}

		//-----------------------------------------
		// Format extra user data
		//-----------------------------------------
		
		$member['_age']             = ( $member['bday_year'] ) ? date( 'Y' ) - $member['bday_year'] : 0;
		
		if( $member['bday_month'] > date( 'n' ) )
		{
			$member['_age'] -= 1;
		}
		else if( $member['bday_month'] == date( 'n' ) )
		{
			if( $member['bday_day'] > date( 'j' ) )
			{
				$member['_age'] -= 1;
			}
		}
		
		$member['_bday_month'] = $this->get_month_name( $member['bday_month'] );
		
		$member['_last_active']      = $this->ipsclass->get_date( $member['last_activity'], 'SHORT' );
		
		if( $member['login_anonymous']{0} == '1' )
		{
			// Member last logged in anonymous
			
			if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
			{
				$member['_last_active'] = $this->ipsclass->lang['private'];
			}
		}
		
    	$member['_local_time']       = $member['time_offset'] != "" ? gmdate( $this->ipsclass->vars['clock_long'], time() + ($member['time_offset']*3600) + ($time_adjust * 60) ) : '';
    	$member['_avatar']           = $this->ipsclass->get_avatar( $member['avatar_location'] , 1, $member['avatar_size'], $member['avatar_type'] );
		$member['_email']            = $member['hide_email'] ? $this->ipsclass->lang['private'] : "<a href='{$this->ipsclass->base_url}act=Mail&amp;CODE=00&amp;MID={$member['id']}'>{$this->ipsclass->lang['email']}</a>";
		$member['_pp_rating_real']   = intval( $member['pp_rating_real'] );
		$member['_interests']        = $this->ipsclass->txt_wordwrap( $member['interests']  ? $member['interests']  : $this->ipsclass->lang['no_info'], '25', ' ' );
		$member['_posts']			 = $this->ipsclass->do_number_format( $member['posts'] );
		$member['_website'] 		 = ( preg_match( "/^http:\/\/\S+$/", $member['website'] ) ) ? $member['website'] : '';
		$member['_title']   		 = $member['title'];

		$member['g_title']			 = $this->ipsclass->make_name_formatted( $member['g_title'], $member['g_id'], $member['g_prefix'], $member['g_suffix'] );
		
		//-----------------------------------------
		// BIO
		//-----------------------------------------
		
		$member['pp_bio_content'] = preg_replace( "#\[b\](.+?)\[/b\]#is", "<b>\\1</b>", $member['pp_bio_content'] );
		$member['pp_bio_content'] = preg_replace( "#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $member['pp_bio_content'] );
		$member['pp_bio_content'] = preg_replace( "#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $member['pp_bio_content'] );
		$member['pp_bio_content'] = $this->parser->bad_words( $member['pp_bio_content'] );
		$member['pp_bio_content'] = $this->ipsclass->txt_wordwrap( $member['pp_bio_content'], '25', ' ' );

		//-----------------------------------------
		// PHOTO
		//-----------------------------------------
		
		$member = $this->personal_portal_set_information( $member );
		
		//-----------------------------------------
		// Contact info
		//-----------------------------------------
		
		$member['icq_number']  = $member['icq_number'] > 0 ? $member['icq_number'] : '';		
		
		$member['_aim_name']   = $member['aim_name']   ? $member['aim_name']   : $this->ipsclass->lang['no_info'];
    	$member['_icq_number'] = $member['icq_number'] ? $member['icq_number'] : $this->ipsclass->lang['no_info'];
    	$member['_yahoo']      = $member['yahoo']      ? $member['yahoo']      : $this->ipsclass->lang['no_info'];
       	$member['_msn_name']   = $member['msnname']    ? $member['msnname']    : $this->ipsclass->lang['no_info'];
		$member['_joined']     = $this->ipsclass->get_date( $member['joined'], 'JOINED' );
		$member['_posts_day']  = 0;
		$member['_total_pct']  = 0;
		
		//-----------------------------------------
		// Format the birthday drop boxes..
		//-----------------------------------------
		
		$member['_birthday_day']    = "<option value='0'>--</option>";
		$member['_birthday_month']  = "<option value='0'>--</option>";
		$member['_birthday_year']   = "<option value='0'>--</option>";
		
		for ( $i = 1 ; $i < 32 ; $i++ )
		{
			$member['_birthday_day'] .= "<option value='$i'";
			
			$member['_birthday_day'] .= $i == $member['bday_day'] ? "selected='selected'>$i</option>" : ">$i</option>";
		}
		
		for ( $i = 1 ; $i < 13 ; $i++ )
		{
			$member['_birthday_month'] .= "<option value='$i'";
			
			$member['_birthday_month'] .= $i == $member['bday_month'] ? "selected='selected'>" . $this->ipsclass->lang['M_'.$i] ."</option>" : ">" . $this->ipsclass->lang['M_'.$i] ."</option>";
		}
		
		$i = $_todays_date['year'] - 1;
		$j = $_todays_date['year'] - 100;
		
		for ( $i ; $j < $i ; $i-- )
		{
			$member['_birthday_year'] .= "<option value='$i'";
			
			$member['_birthday_year'] .= $i == $member['bday_year'] ? "selected='selected'>$i</option>" : ">$i</option>";
		}
		
		//-----------------------------------------
		// Total posts
		//-----------------------------------------
		
		if ( $member['posts'] and $board_posts  )
    	{
    		$member['_posts_day'] = round( $member['posts'] / (((time() - $member['joined']) / 86400)), 0);
    
    		# Fix the issue when there is less than one day
    		$member['_posts_day'] = ( $member['_posts_day'] > $member['posts'] ) ? $member['posts'] : $member['_posts_day'];
    		$member['_total_pct'] = sprintf( '%.2f', ( $member['posts'] / $board_posts * 100 ) );
    	}
    	
    	//-----------------------------------------
    	// Pips / Icon
    	//-----------------------------------------
    	
    	if( !count($this->ipsclass->cache['ranks']) )
    	{
	    	$this->ipsclass->cache['ranks'] = array();
    	}
    	
		foreach( $this->ipsclass->cache['ranks'] as $k => $v )
		{
			if ( $member['posts'] >= $v['POSTS'] )
			{
				if ( ! $member['title'] )
				{
					$member['_title'] = $this->ipsclass->cache['ranks'][ $k ]['TITLE'];
				}
				
				$pips = $v['PIPS'];
				break;
			}
		}
		
		if ( $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_icon'] )
		{
			$member['_member_rank_img'] = $this->ipsclass->compiled_templates['skin_profile']->member_rank_img( $member['g_icon'] );
		}
		else if ($pips)
		{
			if ( is_numeric( $pips ) )
			{
				for ($i = 1; $i <= $pips; ++$i)
				{
					$member['_member_rank_img'] .= "<{A_STAR}>";
				}
			}
			else
			{
				$member['_member_rank_img'] = $this->ipsclass->compiled_templates['skin_profile']->member_rank_img('style_images/<#IMG_DIR#>/folder_team_icons/'.$pips);
			}
		}
		
		//-----------------------------------------
		// Comments
		//-----------------------------------------
		
		$comment_html = $this->personal_build_comments( $member );
		
		//-----------------------------------------
		// Visitors
		//-----------------------------------------
		
		if ( $member['pp_setting_count_visitors'] > 0 )
		{
			$_pp_last_visitors = unserialize( $member['pp_last_visitors'] );
			$_visitor_info     = array();
			$_count            = 0;
		
			if ( is_array( $_pp_last_visitors ) )
			{
				krsort( $_pp_last_visitors );
				$_ids = implode( ',', array_values( $_pp_last_visitors ) );
		
				$this->ipsclass->DB->build_query( array( 'select'   => 'm.*',
														 'from'     => array( 'members' => 'm' ),
														 'where'    => 'm.id IN ('.$_ids.')',
														 'add_join' => array( 
																			  0 => array( 'select' => 'pp.*',
																						  'from'   => array( 'profile_portal' => 'pp' ),
																						  'where'  => 'pp.pp_member_id=m.id',
																						  'type'   => 'left' ),	
																			  1 => array( 'select' => 'me.*',
																						  'from'   => array( 'member_extra' => 'me' ),
																						  'where'  => 'me.id=m.id',
																						  'type'   => 'left' ) ) ) );
																				
				$this->ipsclass->DB->exec_query();
		
				while( $row = $this->ipsclass->DB->fetch_row() )
				{
					$row['_avatar']       = $this->ipsclass->get_avatar( $row['avatar_location'] , 1, $row['avatar_size'], $row['avatar_type'] );
					$row['_last_active']  = $this->ipsclass->get_date( $row['last_activity'], 'SHORT' );
				
					if( $row['login_anonymous']{0} == '1' )
					{
						// Member last logged in anonymous
					
						if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
						{
							$row['_last_active'] = $this->ipsclass->lang['private'];
						}
					}
			
					$row = $this->personal_portal_set_information( $row );
			
					$row['members_display_name_short'] = $this->ipsclass->txt_truncate( $row['members_display_name'], 13 );

					$_visitor_info[ $row['id'] ] = $row;
				}
			
				foreach( $_pp_last_visitors as $_time => $_id )
				{
					if ( $_count + 1 > $member['pp_setting_count_visitors'] )
					{
						break;
					}
				
					$_count++;
				
					$_visitor_info[ $_id ]['_visited_date'] 				= $this->ipsclass->get_date( $_time, 'TINY' );
					$_visitor_info[ $_id ]['members_display_name_short']	= $_visitor_info[ $_id ]['members_display_name_short'] ? $_visitor_info[ $_id ]['members_display_name_short'] : $this->ipsclass->lang['global_guestname'];

					$visitors[] = $_visitor_info[ $_id ];
				}
			}
		}
		//-----------------------------------------
		// Friends
		//-----------------------------------------
		
		# Get random number from member's friend cache... grab 10 random. array_rand( array, no.)
		# also fall back on last 10 if no cache
		
		if ( $member['pp_setting_count_friends'] > 0 )
		{
			$member['_cache'] = $this->ipsclass->unpack_member_cache( $member['members_cache'] );
		
			if ( is_array( $member['_cache']['friends'] ) AND count( $member['_cache']['friends'] ) )
			{
				foreach( $member['_cache']['friends'] as $id => $approved )
				{
					$id = intval( $id );
				
					if ( $approved AND $id )
					{
						$_member_ids[] = $id;
					}
				}

				if ( is_array( $_member_ids ) AND count( $_member_ids ) )
				{
					$_max      = count( $_member_ids ) > $member['pp_setting_count_friends'] ? $member['pp_setting_count_friends'] : count( $_member_ids );
					$_rand     = array_rand( $_member_ids, $_max );
					$_final    = array();
				
					if ( is_array( $_rand ) AND count( $_rand ) )
					{
						foreach( $_rand as $_id )
						{
							$_final[] = $_member_ids[ $_id ];
						}
					}
				
					if ( count( $_final ) )
					{
						$sql_extra = ' AND pf.friends_friend_id IN (' . implode( ',', $_final ) . ')';
					}
				}
			}
		
			$this->ipsclass->DB->build_query( array( 'select'   => 'pf.*',
													 'from'     => array( 'profile_friends' => 'pf' ),
													 'where'    => 'pf.friends_member_id='.$member_id. ' AND pf.friends_approved=1' . $sql_extra,
													 'limit'    => array( 0, 10 ),
													 'add_join' => array( 0 => array( 'select' => 'm.*',
																					  'from'   => array( 'members' => 'm' ),
																					  'where'  => 'm.id=pf.friends_friend_id',
																					  'type'   => 'left' ),
																		  1 => array( 'select' => 'pp.*',
																					  'from'   => array( 'profile_portal' => 'pp' ),
																					  'where'  => 'pp.pp_member_id=m.id',
																					  'type'   => 'left' ),
																		  2 => array( 'select' => 'me.*',
																					  'from'   => array( 'member_extra' => 'me' ),
																					  'where'  => 'me.id=pf.friends_friend_id',
																					  'type'   => 'left' ) ) ) );
																				
			$this->ipsclass->DB->exec_query();
		
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['_friends_added'] = $this->ipsclass->get_date( $row['friends_added'], 'SHORT' );
				$row['_avatar']        = $this->ipsclass->get_avatar( $row['avatar_location'] , 1, $row['avatar_size'], $row['avatar_type'] );
				$row['_last_active']   = $this->ipsclass->get_date( $row['last_activity'], 'DATE' );
			
				if( $row['login_anonymous']{0} == '1' )
				{
					// Member last logged in anonymous
				
					if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
					{
						$row['_last_active'] = $this->ipsclass->lang['private'];
					}
				}

				$row['_location']	   = $row['location'] ? $row['location'] : $this->ipsclass->lang['no_info'];
			
				$row = $this->personal_portal_set_information( $row );
			
				$row['members_display_name_short'] = $this->ipsclass->txt_truncate( $row['members_display_name'], 13 );
				
				$friends[] = $row;
			}
		}
	
		//-----------------------------------------
    	// Warning stuff
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->vars['warn_on'] and ( ! stristr( ','.$this->ipsclass->vars['warn_protected'].',', ','.$member['mgroup'].',' ) ) )
		{
			if ( $this->ipsclass->member['id'] )
			{
				if ( $this->ipsclass->member['g_is_supmod'] == 1 )
				{
					$pass = 1;
					$mod  = 1;
				}
				
				if ( $pass == 0 and ( $this->ipsclass->vars['warn_show_own'] and ( $member['id'] == $this->ipsclass->member['id'] ) ) )
				{
					$pass = 1;
				}
				
				if ( $pass == 1 )
				{
					if ( ! $this->ipsclass->vars['warn_show_rating'] )
					{
						if ( $member['warn_level'] < 1 )
						{
							$member['warn_img'] = '<{WARN_0}>';
						}
						else if ( $member['warn_level'] >= $this->ipsclass->vars['warn_max'] )
						{
							$member['warn_img']     = '<{WARN_5}>';
							$member['warn_percent'] = 100;
						}
						else
						{
							$member['warn_percent'] = $member['warn_level'] ? sprintf( "%.0f", ( ($member['warn_level'] / $this->ipsclass->vars['warn_max']) * 100) ) : 0;
							
							if ( $member['warn_percent'] > 100 )
							{
								$member['warn_percent'] = 100;
							}
							
							if ( $member['warn_percent'] >= 81 )
							{
								$member['warn_img'] = '<{WARN_5}>';
							}
							else if ( $member['warn_percent'] >= 61 )
							{
								$member['warn_img'] = '<{WARN_4}>';
							}
							else if ( $member['warn_percent'] >= 41 )
							{
								$member['warn_img'] = '<{WARN_3}>';
							}
							else if ( $member['warn_percent'] >= 21 )
							{
								$member['warn_img'] = '<{WARN_2}>';
							}
							else if ( $member['warn_percent'] >= 1 )
							{
								$member['warn_img'] = '<{WARN_1}>';
							}
							else
							{
								$member['warn_img'] = '<{WARN_0}>';
							}
						}
						
						if ( !isset($member['warn_percent']) OR $member['warn_percent'] < 1 )
						{
							$member['warn_percent'] = 0;
						}
						
						if ( $mod == 1 )
						{
							$member['_warn_data'] = $this->ipsclass->compiled_templates['skin_profile']->warn_level($member['id'], $member['warn_img'], $member['warn_percent']);
						}
						else
						{
							$member['_warn_data'] = $this->ipsclass->compiled_templates['skin_profile']->warn_level_no_mod($member['id'], $member['warn_img'], $member['warn_percent']);
						}
					}
					else
					{
						if ( $mod == 1 )
						{
							$member['_warn_data'] = $this->ipsclass->compiled_templates['skin_profile']->warn_level_rating($member['id'], $member['warn_level'], $this->ipsclass->vars['warn_min'], $this->ipsclass->vars['warn_max']);
						}
						else
						{
							$member['_warn_data'] = $this->ipsclass->compiled_templates['skin_profile']->warn_level_rating_no_mod($member['id'], $member['warn_level'], $this->ipsclass->vars['warn_min'], $this->ipsclass->vars['warn_max']);
						}
					}	
				}
			}
    	}
		
		//-----------------------------------------
		// Online location
		//-----------------------------------------
		
		$member = $this->personal_portal_get_user_location( $member );
		
		//-----------------------------------------
		// Add profile view (if we've not seen it in
		// the last 3 mins)
		//-----------------------------------------
		
		$_test_spam = 0;//intval( $this->ipsclass->my_getcookie( 'ipb-profile-view-' . $member['id'] ) );
		
		if ( ! $_test_spam OR ( $_test_spam < time() - 180 ) )
		{
			//$this->ipsclass->my_setcookie( 'ipb-profile-view-' . $member['id'], time(), 0 );
			
			$this->ipsclass->DB->do_shutdown_insert( 'profile_portal_views', array( 'views_member_id' => $member['id'] ) );
		}
		
		//-----------------------------------------
		// Grab default tab...
		//-----------------------------------------
		
		if ( $tab != 'comments' AND $tab != 'settings' )
		{
			if( file_exists( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.php' ) )
			{
				require( ROOT_PATH . 'sources/components_public/profile/'.$tab.'.php' );
				$_func_name       = 'profile_'.$tab;
				$plugin           =  new $_func_name;
				$plugin->ipsclass =& $this->ipsclass;
			
				$tab_html = $plugin->return_html_block( $member );
			}
			else
			{
				$tab_html = '';
			}
		}
		else
		{
			$tab_html = '';
		}
		
		//-----------------------------------------
		// Add to output
		//-----------------------------------------
		
		$this->nav        = array( $this->ipsclass->lang['page_title_pp'] );
		$this->page_title = $member['members_display_name'] . ' - ' . $this->ipsclass->lang['page_title_pp'];
		$this->output     = $this->ipsclass->compiled_templates['skin_profile']->personal_portal_main( $tabs, $member, $comment_html, $friends, $visitors, $tab, $tab_html );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Add recent visitor to a profile
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Adds a recent visitor to ones profile
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-7-31
 	*/
 	function personal_portal_add_recent_visitor( $member=array(), $member_id_to_add=0 )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id_to_add = intval( $member_id_to_add );
		$found			  = 0;
		$_recent_visitors = array();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $member_id_to_add )
		{
			return false;
		}
		
		//-----------------------------------------
		// Sort out data...
		//-----------------------------------------
		
		$recent_visitors = unserialize( $member['pp_last_visitors'] );
		
		if ( ! is_array( $recent_visitors ) OR ! count( $recent_visitors ) )
		{
			$recent_visitors = array();
		}
		
		foreach( $recent_visitors as $_time => $_id )
		{
			if ( $_id == $member_id_to_add )
			{
				$found  = 1;
				continue;
			}
			else
			{
				$_recent_visitors[ $_time ] = $_id;
			}
		}
		
		$recent_visitors = $_recent_visitors;
	
		krsort( $recent_visitors );
	
		//-----------------------------------------
		// Pop one off if we didn't update...
		//-----------------------------------------
	
		if ( ! $found )
		{
			# Over 10? Pop one off...
			if ( count( $recent_visitors ) > 10 )
			{
				$_tmp = array_pop( $recent_visitors );
			}
		}
		
		# Add in ours..	
		$recent_visitors[ time() ] = $member_id_to_add;
		
		krsort( $recent_visitors );
		
		//-----------------------------------------
		// Update profile...
		//-----------------------------------------
	
		if ( $member['pp_member_id'] )
		{
			$this->ipsclass->DB->do_update( 'profile_portal ', array( 'pp_last_visitors' => serialize( $recent_visitors ) ), 'pp_member_id='.$member['id'] );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'profile_portal ', array( 'pp_member_id'      => $member['id'],
																	  'pp_profile_update' => time(),
																	  'pp_last_visitors' => serialize( $recent_visitors ) ) );
		}
		
		return true;
	}
	
	/*-------------------------------------------------------------------------*/
 	// Set photos
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Sets the personal photos up
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-7-31
 	*/
 	function personal_portal_set_information( $member, $noids=0, $use_parsed=1 )
 	{
		return $this->ipsclass->member_set_information( $member, $noids, $use_parsed );
	}
	
	function personal_portal_get_user_location( $member=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->load_language( 'lang_online' );
		
    	//-----------------------------------------
    	// Build up our language hash
    	//-----------------------------------------
    	
		$where_lang = array();
		
    	foreach ($this->ipsclass->lang as $k => $v)
    	{
    		if ( preg_match( "/^WHERE_(\w+)$/", $k, $match ) )
    		{
    			$where_lang[ $match[1] ] = $this->ipsclass->lang[$k];
    		}
    	}
    	
    	unset($match);		
		
		$bypass_anon = 0;
		$our_mgroups = array();
		$where       = "";
		$cut_off     = ($this->ipsclass->vars['au_cutoff'] != "") ? $this->ipsclass->vars['au_cutoff'] * 60 : 900;
		$time_limit  = time() - $cut_off;
		
		list( $be_anon, $loggedin ) = explode( '&', $member['login_anonymous'] );
		$member['_online_location'] = '';
		
		//-----------------------------------------
		// Get other groups...
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup_others'] )
		{
			$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
		}
		
		$our_mgroups[] = $this->ipsclass->member['mgroup'];
					
		if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
		{
			$bypass_anon = 1;
		}
		
		$member['sesslocation'] = strpos( $member['sesslocation'], "," ) ? strtolower( substr( $member['sesslocation'], 0, strpos( $member['sesslocation'], "," ) ) ) : $member['sesslocation'];

		//-----------------------------------------
		// DO it
		//-----------------------------------------
		
		if ( ( $member['last_visit'] > $time_limit or $member['last_activity'] > $time_limit ) AND $loggedin == 1 AND ( $be_anon != 1 OR $bypass_anon == 1 ) )
		{
			//-----------------------------------------
			// Module?
			//-----------------------------------------
			
			if ( strstr( $member['sesslocation'], 'mod:' ) )
			{
				$module = str_replace( 'mod:', '', $member['sesslocation'] );
				
				$filename = ROOT_PATH.'sources/components_location/'.$this->ipsclass->txt_alphanumerical_clean( $module ).'.php';
				
				if ( file_exists( $filename ) )
				{
					$real_loc           = $member['location'];
					$member['location'] = $member['sesslocation'];
					
					require_once( $filename );
					$toload           =  'components_location_'.$module;
					$loader           =  new $toload;
					$loader->ipsclass =& $this->ipsclass;
					
					$tmp = $loader->parse_online_entries( array( 1 => $member ) );
					
					if ( is_array( $tmp ) and count( $tmp ) )
					{
						$where = "<a href='{$tmp[1]['_url']}'>{$tmp[1]['_text']}</a>";
					}
					
					$member['location'] = $real_loc;
				}
			}
			else if( $member['sesslocation'] == 'post' )
			{
				if ( $member['location_1_type'] == 'topic' AND $member['location_1_id'] )
				{
					// We have a topic id, must be a topic..
					
					$topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$member['location_1_id'] ) );
					
					if ( $topic['tid'] )
					{
						if ( ! $this->ipsclass->forums->forums_quick_check_access( $member['location_2_id'] ) )
						{ 
							$where = $this->ipsclass->lang['WHERE_postrep'].' '."<a href='{$this->ipsclass->base_url}showtopic={$topic['tid']}'>{$topic['title']}</a>";
						}
					}
				}
				else if( $member['location_2_type'] == 'forum' AND $member['location_2_id'] )
				{
					if ( ! $this->ipsclass->forums->forums_quick_check_access( $member['location_2_id'] ) )
					{
						$where = $this->ipsclass->lang['WHERE_postnew'].' '."<a href='{$this->ipsclass->base_url}showforum={$this->ipsclass->cache['forum_cache'][ $member['location_2_id'] ]['id']}'>{$this->ipsclass->cache['forum_cache'][ $member['location_2_id'] ]['name']}</a>";
					}
				}
			}			
			else if ( $member['location_1_type'] == 'topic' AND $member['location_1_id'] )
			{
				$topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$member['location_1_id'] ) );
				
				if ( $topic['tid'] )
				{
					if ( ! $this->ipsclass->forums->forums_quick_check_access( $member['location_2_id'] ) )
					{ 
						$where = $this->ipsclass->lang['WHERE_st'].' '."<a href='{$this->ipsclass->base_url}showtopic={$topic['tid']}'>{$topic['title']}</a>";
					}
				}
			}
			else if ( $member['location_2_type'] == 'forum' AND $member['location_2_id'] )
			{
				if ( ! $this->ipsclass->forums->forums_quick_check_access( $member['location_2_id'] ) )
				{
					$where = $this->ipsclass->lang['WHERE_sf'].' '."<a href='{$this->ipsclass->base_url}showforum={$this->ipsclass->cache['forum_cache'][ $member['location_2_id'] ]['id']}'>{$this->ipsclass->cache['forum_cache'][ $member['location_2_id'] ]['name']}</a>";
				}
			}
			else if( isset( $where_lang[$member['sesslocation']] ) AND $where_lang[$member['sesslocation']] )
			{
				if( in_array( $member['sesslocation'], array( 'members', 'help', 'calendar', 'online', 'boardrules' ) ) )
				{
					$where = "<a href='{$this->ipsclass->base_url}act={$member['sesslocation']}'>{$where_lang[$member['sesslocation']]}</a>";
				}
				else
				{
					$where = $where_lang[$member['sesslocation']];
				}
			}
			
			if ( ! $where )
			{
				$where = "<a href='{$this->ipsclass->base_url}'>{$this->ipsclass->lang['board_index']}</a>";
			}
					
			$member['_online_location'] = $where;
		}
		
		return $member;
	}
	
 	/*-------------------------------------------------------------------------*/
 	// VIEW CONTACT CARD:
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Display member display name change history
 	*
 	* Prints a pop-up window of the member's display name
 	* history
 	*
 	* @return	void
 	* @since	IPB 2.1.0.2005-7-5
 	*/
 	function show_display_names()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
 		
 		$id      = intval( $this->ipsclass->input['id'] );
 		$member  = array();
 		$html    = "";
 		$content = "";
 		
 		//-----------------------------------------
 		// Display name feature on?
 		//-----------------------------------------
 		
 		if ( ! $this->ipsclass->vars['auth_allow_dnames'] )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
 		}
 		
		//-----------------------------------------
		// Permission check
		//-----------------------------------------
 		
 		if ( $this->ipsclass->member['g_mem_info'] != 1 )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		if ( ! $id )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
 		//-----------------------------------------
		// Get member info
		//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'generic_get_all_member', array( 'mid' => $id ) );
		$this->ipsclass->DB->cache_exec_query();
		
    	$member = $this->ipsclass->DB->fetch_row();
    	
    	//-----------------------------------------
    	// Get Dname history
    	//-----------------------------------------
 		
 		$this->ipsclass->DB->build_query( array( 'select'   => 'd.*',
 												 'from'     => array( 'dnames_change' => 'd' ),
 												 'where'    => 'dname_member_id='.$id,
 												 'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id=d.dname_member_id',
																				  'type'   => 'inner' ) ),
 												 'order'    => 'dname_date DESC' ) );
 		$this->ipsclass->DB->exec_query();
    	
    	while( $row = $this->ipsclass->DB->fetch_row() )
    	{
    		//-----------------------------------------
    		// Format some info
    		//-----------------------------------------
    		
    		$date = $this->ipsclass->get_date( $row['dname_date'], 'SHORT' );
    		
    		//-----------------------------------------
    		// Compile HTML
    		//-----------------------------------------
    		
    		$content .= $this->ipsclass->compiled_templates['skin_profile']->dname_content_row( $row['dname_previous'], $row['dname_current'], $date );
    	}
    	
    	//-----------------------------------------
    	// No changes? Add in a default row
    	//-----------------------------------------
    	
    	if ( ! $content )
    	{
    		$content .= $this->ipsclass->compiled_templates['skin_profile']->dname_content_row( '--', $member['members_display_name'], $this->ipsclass->get_date( $member['joined'], 'SHORT' ) );
    	}
    	
    	//-----------------------------------------
    	// Print the pop-up window
    	//-----------------------------------------
    	
    	$html = $this->ipsclass->compiled_templates['skin_profile']->dname_wrapper( $member['members_display_name'], $content );
    	
    	$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['dname_title'], $html );
    }
 	
 	/*-------------------------------------------------------------------------*/
 	// VIEW CONTACT CARD:
 	/*-------------------------------------------------------------------------*/
 	/**
	* @depricated	2006-08-16
	*/
 	function show_card()
 	{
		$info = array();
 		
 		if ($this->ipsclass->member['g_mem_info'] != 1)
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//-----------------------------------------
    	// Check input..
    	//-----------------------------------------
    	
    	$id = intval($this->ipsclass->input['MID']);
    	
    	if ( empty($id) )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	$this->ipsclass->DB->cache_add_query( 'generic_get_all_member', array( 'mid' => $id ) );
		$this->ipsclass->DB->cache_exec_query();
		
    	$member = $this->ipsclass->DB->fetch_row();
    	
    	$member['website'] = $member['website'] == 'http://' ? '' : $member['website'];
    
    	$info['aim_name']    = $member['aim_name']   ? $member['aim_name']   : $this->ipsclass->lang['no_info'];
    	$info['icq_number']  = $member['icq_number'] ? $member['icq_number'] : $this->ipsclass->lang['no_info'];
    	$info['yahoo']       = $member['yahoo']      ? $member['yahoo']      : $this->ipsclass->lang['no_info'];
    	$info['location']    = $member['location']   ? $member['location']   : $this->ipsclass->lang['no_info'];
    	$info['interests']   = $member['interests']  ? $member['interests']  : $this->ipsclass->lang['no_info'];
    	$info['msn_name']    = $member['msnname']    ? $member['msnname']    : $this->ipsclass->lang['no_info'];
    	$info['website']     = $member['website']    ? "<a href='{$member['website']}' target='_blank'>{$member['website']}</a>" : $this->ipsclass->lang['no_info'];
    	$info['mid']         = $member['id'];
    	$info['has_blog']    = $member['has_blog'];
    	$info['has_gallery'] = isset($member['has_gallery']) ? $member['has_gallery'] : 0;
    	
    	if (!$member['hide_email'])
    	{
			$info['email'] = "<a href='javascript:redirect_to(\"&amp;act=Mail&amp;CODE=00&amp;MID={$member['id']}\",1);'>{$this->ipsclass->lang['click_here']}</a>";
		}
		else
		{
			$info['email'] = $this->ipsclass->lang['private'];
		}
    	
    	$this->load_photo($id);
    	
    	if ( $this->show_photo )
    	{
    		$photo = $this->ipsclass->compiled_templates['skin_profile']->get_photo( $this->show_photo, $this->show_width, $this->show_height );
    	}
    	else
    	{
    		$photo = "<{NO_PHOTO}>";
    	}
    	
    	if ( isset($this->ipsclass->input['download']) AND $this->ipsclass->input['download'] )
    	{
    		$photo = str_replace( "<{NO_PHOTO}>", $this->ipsclass->lang['no_photo_avail'], $photo );
    		$html  = $this->ipsclass->compiled_templates['skin_profile']->show_card_download( $member['members_display_name'], $photo, $info );
    		$html  = str_replace( "<!--CSS-->", $this->ipsclass->skin['_css'], $html );
    		
    		//-----------------------------------------
    		// Macros
    		//-----------------------------------------
    		
    		$macros = unserialize(stripslashes($this->ipsclass->skin['_macro']));
    		
    		if ( is_array( $macros ) )
			{
				foreach( $macros as $row )
				{
					if ($row['macro_value'] != "")
					{
						$html = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $html );
					}
				}
			}
			
			//-----------------------------------------
			// Images
			//-----------------------------------------
			
			$html = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $html );
			
    		if ( ! $this->ipsclass->vars['ipb_img_url'] )
			{
				$this->ipsclass->vars['ipb_img_url'] = preg_replace( "#/$#", "", $this->ipsclass->vars['board_url'] ) . '/';
			}
			
			$html = preg_replace( "#img\s+?src=[\"']style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\"".$this->ipsclass->vars['ipb_img_url']."style_\\1\\2\"\\3>", $html );
    		
    		//-----------------------------------------
    		// Download
    		//-----------------------------------------
    		
			@header("Content-type: unknown/unknown");
			@header("Content-Disposition: attachment; filename={$member['members_display_name']}.html");
			print $html;
			exit();
    	}
    	else
    	{
			$html  = $this->ipsclass->compiled_templates['skin_profile']->show_card( $member['members_display_name'], $photo, $info );
			
			$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['photo_title'], $html );
    	}
    }
 	
 	/*-------------------------------------------------------------------------*/
 	// VIEW PHOTO:
 	/*-------------------------------------------------------------------------*/
 	
 	function show_photo()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$info = array();
 		$id   = intval($this->ipsclass->input['MID']);

 		if ($this->ipsclass->member['g_mem_info'] != 1)
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//-----------------------------------------
    	// Check input..
    	//-----------------------------------------
    	
    	if ( empty($id) )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	$this->load_photo($id);
    	
    	$photo = $this->ipsclass->compiled_templates['skin_profile']->get_photo( $this->show_photo, $this->show_width, $this->show_height );
    	$html  = $this->ipsclass->compiled_templates['skin_profile']->show_photo( $this->photo_member['members_display_name'], $photo );
    	
    	$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['photo_title'], $html );
    }
    
    /*-------------------------------------------------------------------------*/
 	// FUNC: RETURN PHOTO
 	/*-------------------------------------------------------------------------*/
    
    function load_photo($id, $member=array())
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->show_photo  = "";
    	$this->show_height = "";
    	$this->show_width  = "";
    	
    	if ( ! isset( $member['pp_member_id'] ) )
    	{
			$this->photo_member = $this->personal_function_load_member( $id );
    	}
    	else
    	{
    		$this->photo_member = $member;
    	}

		//-----------------------------------------
		// Set it up...
		//-----------------------------------------
		
		$this->photo_member = $this->personal_portal_set_information( $this->photo_member );
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$this->show_photo  = $this->photo_member['pp_main_photo'];
    	$this->show_width  = "width='"  . $this->photo_member['pp_main_width']  . "'";
		$this->show_height = "height='" . $this->photo_member['pp_main_height'] . "'";
    }
    
 	/*-------------------------------------------------------------------------*/
 	// VIEW MAIN PROFILE:
 	/*-------------------------------------------------------------------------*/
 	
 	function view_profile()
 	{
 		$info = array();
 		
 		if ($this->ipsclass->member['g_mem_info'] != 1)
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
 		
 		//-----------------------------------------
    	// Check input..
    	//-----------------------------------------
    	
    	$id = intval($this->ipsclass->input['MID']);
    	
    	if ( ! $id )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url );
    	}
    	
    	//-----------------------------------------
    	// Get all member information
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'profile_get_all', array( 'mid' => $id ) );
    	
    	$this->ipsclass->DB->cache_exec_query();
    	
    	$member = $this->ipsclass->DB->fetch_row();
    	
    	if ( empty( $member['id'] ) )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use' ) );
    	}
    	
    	//-----------------------------------------
    	// Most posted forum
    	//-----------------------------------------
    	
    	$forum_ids = array('0');
    	
    	foreach( $this->ipsclass->forums->forum_by_id as $r )
    	{
	    	$r['read_perms'] = isset($r['read_perms']) ? $r['read_perms'] : '';
	    	
    		if ( $this->ipsclass->check_perms($r['read_perms']) == TRUE )
    		{
    			$forum_ids[] = $r['id'];
    		}
    	}
    	
    	$this->ipsclass->DB->cache_add_query( 'profile_get_favourite', array( 'mid' => $member['id'], 'fid_array' => $forum_ids ) );
    	
    	$this->ipsclass->DB->cache_exec_query();
    	
    	$favourite   = $this->ipsclass->DB->fetch_row();
    	
    	//-----------------------------------------
    	// Post count stats
    	//-----------------------------------------
    	
    	$percent = 0;
    	
    	$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as total_posts',
													  'from'   => 'posts',
													  'where'  => "author_id={$member['id']}" ) );
		$this->ipsclass->DB->simple_exec();
		
    	$total_posts = $this->ipsclass->DB->fetch_row();
    	
    	$board_posts = $this->ipsclass->cache['stats']['total_topics'] + $this->ipsclass->cache['stats']['total_replies'];
    	
    	if ($total_posts['total_posts'] > 0)
    	{
    		$percent = round( $favourite['f_posts'] / $total_posts['total_posts'] * 100 );
    	}
    	
    	if ($member['posts'] and $board_posts)
    	{
    		$info['posts_day'] = round( $member['posts'] / (((time() - $member['joined']) / 86400)), 1);
    		
    		$info['total_pct'] = sprintf( '%.2f', ( $member['posts'] / $board_posts * 100 ) );
    	}
    	
    	if ($info['posts_day'] > $member['posts'])
    	{
    		$info['posts_day'] = $member['posts'];
    	}
    	
    	//-----------------------------------------
    	// Pips / Icon
    	//-----------------------------------------
    	
    	$pips = 0;
		
		foreach($this->ipsclass->cache['ranks'] as $k => $v)
		{
			if ($member['posts'] >= $v['POSTS'])
			{
				if (!$member['title'])
				{
					$member['title'] = $this->ipsclass->cache['ranks'][ $k ]['TITLE'];
				}
				
				$pips = $v['PIPS'];
				break;
			}
		}
		
		$member['member_rank_img'] = "";
		
		if ($this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_icon'])
		{
			$member['member_rank_img'] = $this->ipsclass->compiled_templates['skin_profile']->member_rank_img($this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_icon']);
		}
		else if ($pips)
		{
			if ( is_numeric( $pips ) )
			{
				for ($i = 1; $i <= $pips; ++$i)
				{
					$member['member_rank_img'] .= "<{A_STAR}>";
				}
			}
			else
			{
				$member['member_rank_img'] = $this->ipsclass->compiled_templates['skin_profile']->member_rank_img('style_images/<#IMG_DIR#>/folder_team_icons/'.$pips);
			}
		}
    	
    	//-----------------------------------------
    	// More info...
    	//-----------------------------------------
    	
    	$info['posts']       = $member['posts'] ? $member['posts'] : 0;
    	$info['name']        = $member['members_display_name'];
    	$info['mid']         = $member['id'];
    	
    	if( isset($favourite['forum_id']) )
    	{
    		$info['fav_forum']   = $this->ipsclass->cache['forum_cache'][ $favourite['forum_id'] ]['name'] ? $this->ipsclass->cache['forum_cache'][ $favourite['forum_id'] ]['name'] : $this->ipsclass->lang['no_info'];
		}
		else
		{
			$info['fav_forum']	 = $this->ipsclass->lang['no_info'];
		}
		
    	$info['fav_id']      = intval($favourite['forum_id']);
    	$info['fav_posts']   = intval($favourite['f_posts']);
    	$info['percent']     = $percent;
    	$info['group_title'] = $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_title'];
    	$info['board_posts'] = $board_posts;
    	$info['joined']      = $this->ipsclass->get_date( $member['joined'], 'JOINED' );
    	$info['last_active'] = $this->ipsclass->get_date( $member['last_activity'], 'SHORT' );
    	
		if( $info['login_anonymous']{0} == '1' )
		{
			// Member last logged in anonymous
			
			if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] OR $this->ipsclass->vars['disable_admin_anon'] )
			{
				$info['last_active'] = $this->ipsclass->lang['private'];
			}
		}
    	
    	$info['member_title'] = $member['title']     ? $member['title']      : $this->ipsclass->lang['no_info'];
    	
    	$info['aim_name']             = $member['aim_name']   ? $member['aim_name']   : $this->ipsclass->lang['no_info'];
    	$info['icq_number']           = $member['icq_number'] ? $member['icq_number'] : $this->ipsclass->lang['no_info'];
    	$info['yahoo']                = $member['yahoo']      ? $member['yahoo']      : $this->ipsclass->lang['no_info'];
    	$info['location']             = $member['location']   ? $member['location']   : $this->ipsclass->lang['no_info'];
    	$info['interests']			  = $this->ipsclass->txt_wordwrap( $member['interests']  ? $member['interests']  : $this->ipsclass->lang['no_info'], '25', ' ' );
    	$info['msn_name']             = $member['msnname']    ? $member['msnname']    : $this->ipsclass->lang['no_info'];
    	$info['member_rank_img']      = $member['member_rank_img'];
    	$info['has_blog']             = isset($member['has_blog']) ? $member['has_blog'] : 0;
    	$info['has_gallery'] 		  = isset($member['has_gallery']) ? $member['has_gallery'] : 0;
    	$info['members_display_name'] = $member['members_display_name'];
    	
    	//-----------------------------------------
		// Online, offline?
		//-----------------------------------------
		
		$cut_off = ($this->ipsclass->vars['au_cutoff'] != "") ? $this->ipsclass->vars['au_cutoff'] * 60 : 900;
		$time_limit    = time() - $cut_off;
		
		$info['online_status_indicator'] = '<{PB_USER_OFFLINE}>';
		$info['online_extra']            = '('.$this->ipsclass->lang['online_offline'].')';
		
		list( $be_anon, $loggedin ) = explode( '&', $member['login_anonymous'] );
		
		$bypass_anon = 0;
		
		$our_mgroups = array();
		
		if( $this->ipsclass->member['mgroup_others'] )
		{
			$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
		}
		
		$our_mgroups[] = $this->ipsclass->member['mgroup'];
					
		if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
		{
			$bypass_anon = 1;
		}
		
		//-----------------------------------------
		// DO it
		//-----------------------------------------
		
		if ( ( $member['last_visit'] > $time_limit or $member['last_activity'] > $time_limit ) AND $loggedin == 1 AND ( $be_anon != 1 OR $bypass_anon == 1 ) )
		{
			$info['online_status_indicator'] = '<{PB_USER_ONLINE}>';
			
			//-----------------------------------------
			// Where?
			//-----------------------------------------
			
			$where = "";
			
			//-----------------------------------------
			// Module?
			//-----------------------------------------
			
			if ( strstr( $member['sesslocation'], 'mod:' ) )
			{
				$module = str_replace( 'mod:', '', $member['sesslocation'] );
				
				$filename = ROOT_PATH.'sources/components_location/'.$this->ipsclass->txt_alphanumerical_clean( $module ).'.php';
				
				if ( file_exists( $filename ) )
				{
					$real_loc           = $member['location'];
					$member['location'] = $member['sesslocation'];
					
					require_once( $filename );
					$toload           =  'components_location_'.$module;
					$loader           =  new $toload;
					$loader->ipsclass =& $this->ipsclass;
					
					$tmp = $loader->parse_online_entries( array( 1 => $member ) );
					
					if ( is_array( $tmp ) and count( $tmp ) )
					{
						$where = "<a href='{$tmp[1]['_url']}'>{$tmp[1]['_text']}</a>";
					}
					
					$member['location'] = $real_loc;
				}
			}
			
			else if ( $member['location_1_type'] == 'topic' AND $member['location_1_id'] )
			{
				$topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$member['location_1_id'] ) );
				
				if ( $topic['tid'] )
				{
					if ( ! $this->ipsclass->forums->forums_quick_check_access( $member['location_2_id'] ) )
					{ 
						$where = $this->ipsclass->lang['wol_topic'].': '."<a href='{$this->ipsclass->base_url}showtopic={$topic['tid']}'>{$topic['title']}</a>";
					}
				}
			}
			else if ( $member['location_2_type'] == 'forum' AND $member['location_2_id'] )
			{
				if ( ! $this->ipsclass->forums->forums_quick_check_access( $member['location_2_id'] ) )
				{
					$where = $this->ipsclass->lang['wol_forum'].' '.$this->ipsclass->cache['forum_cache'][ $member['location_2_id'] ]['name'];
				}
			}
			else if ( strstr( strtolower($member['sesslocation']), 'usercp' ) or strstr( strtolower($member['sesslocation']), 'msg' ) )
			{	
				$where = $this->ipsclass->lang['wol_ucp'];
			}
			else if ( strstr( strtolower($member['sesslocation']), 'profile' )  )
			{	
				$where = $this->ipsclass->lang['wol_profile'];
			}
			else if ( strstr( strtolower($member['sesslocation']), 'search' )  )
			{	
				$where = $this->ipsclass->lang['wol_search'];
			}
			
			if ( ! $where )
			{
				$where = $this->ipsclass->lang['wol_index'];
			}
					
			$info['online_extra'] = '('.$where.')';
		}
		
    	//-----------------------------------------
    	// Time...
    	//-----------------------------------------
    	
    	$this->ipsclass->vars['time_adjust'] = $this->ipsclass->vars['time_adjust'] == "" ? 0 : $this->ipsclass->vars['time_adjust'];
    	
    	if ($member['dst_in_use'] == 1)
    	{
    		$member['time_offset'] += 1;
    	}
    	
    	$info['local_time']  = $member['time_offset'] != "" ? gmdate( $this->ipsclass->vars['clock_long'], time() + ($member['time_offset']*3600) + ($this->ipsclass->vars['time_adjust'] * 60) ) : $this->ipsclass->lang['no_info'];
    	
    	$info['avatar']      = $this->ipsclass->get_avatar( $member['avatar_location'] , 1, $member['avatar_size'], $member['avatar_type'] );
    	
    	//-----------------------------------------
    	// Siggy
    	//-----------------------------------------
    	
    	$info['signature']   = $member['signature'];

    	//-----------------------------------------
    	// site
    	//-----------------------------------------
    	
    	if ( $member['website'] and preg_match( "/^http:\/\/\S+$/", $member['website'] ) )
    	{
			$info['homepage'] = "<a href='{$member['website']}' target='_blank'>{$member['website']}</a>";
		}
		else
		{
			$info['homepage'] = $this->ipsclass->lang['no_info'];
		}
		
    	//-----------------------------------------
    	// Birthday
    	//-----------------------------------------
    	
    	if ($member['bday_month'])
    	{
    		$info['birthday'] = $member['bday_day']." ".$this->ipsclass->lang[ 'M_'.$member['bday_month'] ]." ".$member['bday_year'];
    	}
    	else
    	{
    		$info['birthday'] = $this->ipsclass->lang['no_info'];
    	}
    	
    	//-----------------------------------------
    	// Email
    	//-----------------------------------------
    	
    	if ( ! $member['hide_email'] )
    	{
			$info['email'] = "<a href='{$this->ipsclass->base_url}act=Mail&amp;CODE=00&amp;MID={$member['id']}'>{$this->ipsclass->lang['email']}</a>";
		}
		else
		{
			$info['email'] = $this->ipsclass->lang['private'];
		}
		
		//-----------------------------------------
		// Get photo and show profile:
		//-----------------------------------------
		
		$this->load_photo( $member['id'], $member );
		
		if ( $this->show_photo )
    	{
    		$info['photo'] = $this->ipsclass->compiled_templates['skin_profile']->get_photo( $this->show_photo, $this->show_width, $this->show_height );
    	}
    	else
    	{
    		$info['photo'] = "";
    	}
    	
    	$info['base_url'] = $this->ipsclass->base_url;
    	
    	$info['posts'] = $this->ipsclass->do_number_format($info['posts']);
    	
    	//-----------------------------------------
    	// Output
    	//-----------------------------------------
    	
    	$this->output .= $this->ipsclass->compiled_templates['skin_profile']->show_profile( $info, $this->ipsclass->return_md5_check() );
    	
    	//-----------------------------------------
    	// Is this our profile?
    	//-----------------------------------------
  	
    	if ($member['id'] == $this->ipsclass->member['id'])
    	{
    		$this->output = str_replace( "<!--MEM OPTIONS-->", $this->ipsclass->compiled_templates['skin_profile']->user_edit($info), $this->output );
    	}
    	
        //-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------
    	
    	$custom_out = "";
    	
    	require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];
    	$fields->mem_data_id = $member['id'];
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$fields->init_data();
    	$fields->parse_to_view();
    	
    	foreach( $fields->out_fields as $id => $data )
    	{
    		if ( ! $data )
    		{
    			$data = $this->ipsclass->lang['no_info'];
    		}
    		
    		$data = $this->ipsclass->txt_wordwrap( $data, '25', ' ' );
    		
			$custom_out .= $this->ipsclass->compiled_templates['skin_profile']->custom_field( $fields->field_names[ $id ], $data );
    	}
    	
    	if ($custom_out != "")
    	{
    		$this->output = str_replace( "<!--{CUSTOM.FIELDS}-->", $custom_out, $this->output );
    	}
    	else
    	{
    		$this->output = str_replace( "<!--{CUSTOM.FIELDS}-->", $this->ipsclass->compiled_templates['skin_profile']->no_custom_information(), $this->output );
    	}
    	
    	//-----------------------------------------
    	// Warning stuff!!
    	//-----------------------------------------
    	
    	$pass = 0;
    	$mod  = 0;
    	
    	if ( $this->ipsclass->vars['warn_on'] and ( ! stristr( ','.$this->ipsclass->vars['warn_protected'].',', ','.$member['mgroup'].',' ) ) )
		{
			if ($this->ipsclass->member['id'])
			{
				if ( $this->ipsclass->member['g_is_supmod'] == 1 )
				{
					$pass = 1;
					$mod  = 1;
				}
				
				if ( $pass == 0 and ( $this->ipsclass->vars['warn_show_own'] and ( $member['id'] == $this->ipsclass->member['id'] ) ) )
				{
					$pass = 1;
				}
				
				if ( $pass == 1 )
				{
					// Work out which image to show.
					
					if ( ! $this->ipsclass->vars['warn_show_rating'] )
					{
						if ( $member['warn_level'] < 1 )
						{
							$member['warn_img'] = '<{WARN_0}>';
						}
						else if ( $member['warn_level'] >= $this->ipsclass->vars['warn_max'] )
						{
							$member['warn_img']     = '<{WARN_5}>';
							$member['warn_percent'] = 100;
						}
						else
						{
							$member['warn_percent'] = $member['warn_level'] ? sprintf( "%.0f", ( ($member['warn_level'] / $this->ipsclass->vars['warn_max']) * 100) ) : 0;
							
							if ( $member['warn_percent'] > 100 )
							{
								$member['warn_percent'] = 100;
							}
							
							if ( $member['warn_percent'] >= 81 )
							{
								$member['warn_img'] = '<{WARN_5}>';
							}
							else if ( $member['warn_percent'] >= 61 )
							{
								$member['warn_img'] = '<{WARN_4}>';
							}
							else if ( $member['warn_percent'] >= 41 )
							{
								$member['warn_img'] = '<{WARN_3}>';
							}
							else if ( $member['warn_percent'] >= 21 )
							{
								$member['warn_img'] = '<{WARN_2}>';
							}
							else if ( $member['warn_percent'] >= 1 )
							{
								$member['warn_img'] = '<{WARN_1}>';
							}
							else
							{
								$member['warn_img'] = '<{WARN_0}>';
							}
						}
						
						if ( !isset($member['warn_percent']) OR $member['warn_percent'] < 1 )
						{
							$member['warn_percent'] = 0;
						}
						
						if ( $mod == 1 )
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->ipsclass->compiled_templates['skin_profile']->warn_level($member['id'], $member['warn_img'], $member['warn_percent']), $this->output );
						}
						else
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->ipsclass->compiled_templates['skin_profile']->warn_level_no_mod($member['id'], $member['warn_img'], $member['warn_percent']), $this->output );
						}
					}
					else
					{
						// Rating mode:
						
						if ( $mod == 1 )
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->ipsclass->compiled_templates['skin_profile']->warn_level_rating($member['id'], $member['warn_level'], $this->ipsclass->vars['warn_min'], $this->ipsclass->vars['warn_max']), $this->output );
						}
						else
						{
							$this->output = str_replace( "<!--{WARN_LEVEL}-->", $this->ipsclass->compiled_templates['skin_profile']->warn_level_rating_no_mod($member['id'], $member['warn_level'], $this->ipsclass->vars['warn_min'], $this->ipsclass->vars['warn_max']), $this->output );
						}
					}	
				}
			}
    	}
    	
 		$this->page_title = $this->ipsclass->lang['page_title_pp'];
 		$this->nav        = array( $this->ipsclass->lang['page_title_pp'] );
 	}
 	
 	function get_month_name( $month=0 )
 	{
	 	$month = intval($month);
	 	
	 	return ( isset($this->ipsclass->lang['M_'.$month]) ) ? $this->ipsclass->lang['M_'.$month] : false;
 	}
 	
}

?>