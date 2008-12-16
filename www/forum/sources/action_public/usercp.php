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
|   > $Date: 2007-09-05 18:02:27 -0400 (Wed, 05 Sep 2007) $
|   > $Revision: 1101 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > UserCP functions
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class usercp
{
	var $ipsclass;
	var $han_editor = "";
	
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
    
    var $email      = "";
    var $md5_check  = "";
    
    var $modules    = "";
    
    var $lib;
    
    /*-------------------------------------------------------------------------*/
    // Init Editor & Parser
    /*-------------------------------------------------------------------------*/
    
    function init_parser()
    {
	    if ( ! is_object( $this->han_editor ) )
	    {
	    	//-----------------------------------------
	        // Load and config the std/rte editors
	        //-----------------------------------------
	        
	        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
	        $this->han_editor           = new han_editor();
	        $this->han_editor->ipsclass =& $this->ipsclass;
	        $this->han_editor->init();
        }
 		
        if ( ! is_object( $this->parser ) )
        {
			//-----------------------------------------
	    	// Load parser
	    	//-----------------------------------------
	    	
	    	require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
	        $this->parser                      =  new parse_bbcode();
	        $this->parser->ipsclass            =& $this->ipsclass;
	        $this->parser->allow_update_caches = 1;
	        
	        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
        }
    }
    	    
    /*-------------------------------------------------------------------------*/
    // Run!
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
        //-----------------------------------------
        // Prep form check
        //-----------------------------------------
        
        $this->md5_check = $this->ipsclass->return_md5_check();
        
		//-----------------------------------------
    	// Get the sync module
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			$this->modules->ipsclass =& $this->ipsclass;
		}
        
    	//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
    	$this->ipsclass->load_language('lang_post');
		$this->ipsclass->load_language('lang_ucp');
    	$this->ipsclass->load_template('skin_ucp');
    	
    	$this->ipsclass->base_url_nosess = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}";
    	
    	//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
		
		//-----------------------------------------
		// INIT da func
		//-----------------------------------------
		
		require ROOT_PATH."sources/lib/func_usercp.php";
    	$this->lib  		 =  new func_usercp();
		$this->lib->class    =& $this;
    	$this->lib->ipsclass =& $this->ipsclass;
    	
    	//-----------------------------------------
    	// Print menu
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output( $this->lib->ucp_generate_menu() );
    	
    	//-----------------------------------------
		// Set sizes
		//-----------------------------------------
		
		$this->links = $this->ipsclass->member['links'];
		$this->notes = $this->ipsclass->member['notes'];
		$this->size  = $this->ipsclass->member['ta_size'] ? $this->ipsclass->member['ta_size'] : $this->size;
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case '00':
    			$this->splash();
    			break;
    		case '01':
    			$this->personal();
    			break;
    		//-----------------------------------------
    		case '02':
    			$this->email_settings();
    			break;
    		case '03':
    			$this->lib->do_email_settings();
    			break;
    		//-----------------------------------------
    		case '04':
    			$this->board_prefs();
    			break;
    		case '05':
    			$this->lib->do_board_prefs();
    			break;
    		//-----------------------------------------
    		case '08':
    			$this->email_change();
    			break;
    		case '09':
    			$this->do_email_change();
    			break;
    		//-----------------------------------------
    		case '21':
    			$this->lib->do_profile();
    			break;
    		case '20':
    			$this->update_notepad();
    			break;
    		//-----------------------------------------
    		case '22':
    			$this->signature();
    			break;
    		case '23':
    			$this->lib->do_signature();
    			break;
    		//-----------------------------------------
    		case '24':
    			$this->avatar();
    			break;
    		case '25':
    			$this->lib->do_avatar();
    			break;
    		//-----------------------------------------
    		case '26':
    			$this->tracker();
    			break;
    		case '27':
    			$this->do_update_tracker();
    			break;
    		//-----------------------------------------
    		case '28':
    			$this->pass_change();
    			break;
    		case '29':
    			$this->do_pass_change();
    			break;
    		//-----------------------------------------
    		case '50':
    			$this->forum_tracker();
    			break;
    		case '51':
    			$this->remove_forum_tracker();
    			break;
    		//-----------------------------------------
    		case 'ignore':
    			$this->ignore_user_splash();
    			break;
    		case 'ignoreadd':
    			$this->lib->ignore_user_add();
    			break;
    		case 'ignoreremove':
    			$this->lib->ignore_user_remove();
    			break;
    		//-----------------------------------------
    		case 'show_image':
    			$this->show_image();
    			break;
    		case 'photo':
    			$this->photo();
    			break;
    		case 'dophoto':
    			$this->lib->do_photo();
    			break;
    		case 'getgallery':
    			$this->avatar_gallery();
    			break;
    		case 'setinternalavatar':
    			$this->lib->set_internal_avatar();
    			break;
    		case 'attach':
    			$this->attachments();
    			break;
    		//-----------------------------------------
    		// Mod tools
    		//-----------------------------------------
    		case 'iptool':
    			$this->mod_ip_tool_start();
    			break;
    		case 'doiptool':
    			$this->mod_ip_tool_complete();
    			break;
    		case 'memtool':
    			$this->mod_find_user_start();
    			break;
    		case 'domemtool':
    			$this->mod_find_user_complete();
    			break;
    		case 'announce_start':
    			$this->mod_announce_start();
    			break;
    		case 'announce_add':
    			$this->mod_announce_form('add');
    			break;
    		case 'announce_save':
    			$this->mod_announce_save();
    			break;
    		case 'announce_edit':
    			$this->mod_announce_form('edit');
    			break;
    		case 'announce_delete':
    			$this->mod_announce_delete();
    			break;
    		//-----------------------------------------
    		// Subs(ways)?
    		//-----------------------------------------
    		case 'start_subs':
    			$this->lib->subs_choose();
    			break;
    		case 'end_subs':
    			$this->lib->subs_choose('save');
    			break;
    		//-----------------------------------------
    		// Display name change
    		//-----------------------------------------
    		case 'dname_start':
    			$this->display_name_change();
    			break;
    		case 'dname_complete':
    			$this->display_name_complete();
    			break;
    		//-----------------------------------------
    		// Personal portal form..
    		//-----------------------------------------
    		case 'personal_portal_form':
				$this->personal_portal_form();
				break;
			case 'personal_portal_save':
				$this->personal_portal_save();
				break;
			case 'manage_friends':
				$this->manage_friends();
				break;
    		default:
    			$this->splash();
    			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$fj = $this->ipsclass->build_forum_jump();
		$fj = str_replace( "#Forum Jump#", $this->ipsclass->lang['forum_jump'], $fj);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->CP_end();
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->forum_jump( $fj );
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 1, 'NAV' => $this->nav ) );
 	}

	/*-------------------------------------------------------------------------*/
 	// Manage friends
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Manage friends
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-17
 	*/
 	function manage_friends()
 	{
		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id = intval( $this->ipsclass->member['id'] );
		
		//-----------------------------------------
		// Get friends pending approval
		//-----------------------------------------
		
		$friends = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count',
																	 'from'   => 'profile_friends',
																	 'where'  => 'friends_friend_id='.$member_id.' AND friends_approved=0' ) );
		
		//-----------------------------------------
		// Ok.. show the settings
		//-----------------------------------------

		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->personal_manage_friends( intval( $friends['count'] ) );
		
		$this->page_title = $this->ipsclass->lang['m_manage_friends'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
	}

	/*-------------------------------------------------------------------------*/
 	// Personal portal save
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Personal portal save
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-17
 	*/
 	function personal_portal_save()
 	{
		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id 					  = intval( $this->ipsclass->member['id'] );
		$md5check  					  = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$pp_setting_notify_comments   = trim( substr( $this->ipsclass->input['pp_setting_notify_comments'], 0, 10 ) );
		$pp_setting_notify_friend     = trim( substr( $this->ipsclass->input['pp_setting_notify_friend'], 0, 10 ) );
		$pp_setting_moderate_comments = intval( $this->ipsclass->input['pp_setting_moderate_comments'] );
		$pp_setting_moderate_friends  = intval( $this->ipsclass->input['pp_setting_moderate_friends'] );
		
		if( $this->ipsclass->member['g_edit_profile'] )
		{
			$pp_bio_content				  = $this->ipsclass->txt_mbsubstr( $this->ipsclass->my_nl2br( $this->ipsclass->input['pp_bio_content'] ), 0, 300 );
		}
		
		//-----------------------------------------
		// Load profile lib
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/profile.php' );
		$lib_profile           =  new profile;
		$lib_profile->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->ipsclass->return_md5_check() )
    	{
    		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=usercp&amp;member_id='.$member_id.'&amp;CODE=personal_portal_form&amp;___msg=no_permission&md5check='.$this->ipsclass->md5_check );
			exit();
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $lib_profile->personal_function_load_member( $member_id );
    	
		
		if( !$this->ipsclass->member['g_edit_profile'] )
		{
			$pp_bio_content = $member['pp_bio_content'];
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['id'] )
    	{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=usercp&amp;member_id='.$member_id.'&amp;CODE=personal_portal_form&amp;___msg=no_permission&md5check='.$this->ipsclass->md5_check );
			exit();
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
			$this->ipsclass->DB->do_update( 'profile_portal', array( 
																	 'pp_bio_content'				=> $pp_bio_content,
																	 'pp_setting_notify_comments'   => $pp_setting_notify_comments,
																	 'pp_setting_notify_friend'     => $pp_setting_notify_friend,
																	 'pp_setting_moderate_comments' => $pp_setting_moderate_comments,
																	 'pp_setting_moderate_friends'  => $pp_setting_moderate_friends
																 ), 'pp_member_id='.$member_id );
		}
		else
		{
			# Insert
			$this->ipsclass->DB->do_insert( 'profile_portal', array( 
																	 'pp_bio_content'				=> $pp_bio_content,
																	 'pp_member_id'                 => $member_id,
																	 'pp_setting_notify_comments'   => $pp_setting_notify_comments,
																	 'pp_setting_notify_friend'     => $pp_setting_notify_friend,
																	 'pp_setting_moderate_comments' => $pp_setting_moderate_comments,
																	 'pp_setting_moderate_friends'  => $pp_setting_moderate_friends
																 ) );
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=usercp&member_id='.$member_id.'&CODE=personal_portal_form&_saved=1&___msg=settings_updated&md5check='.$this->ipsclass->md5_check );
		
	}
 	
	/*-------------------------------------------------------------------------*/
 	// Personal portal form
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Personal portal form
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-08-17
 	*/
 	function personal_portal_form()
 	{
		//-----------------------------------------
		// Load language
		//-----------------------------------------
		
		$this->ipsclass->load_language( 'lang_profile' );
		
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id = intval( $this->ipsclass->member['id'] );
		$content   = '';
		$types     = array( 'none'  => $this->ipsclass->lang['op_dd_none'],
					 		'email' => $this->ipsclass->lang['op_dd_email'],
							'pm'    => $this->ipsclass->lang['op_dd_pm'] );
							
		$yes_no    = array( '0'     => $this->ipsclass->lang['op_dd_disabled'],
							'1'     => $this->ipsclass->lang['op_dd_enabled'] );
		
		//-----------------------------------------
		// Load profile lib
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/profile.php' );
		$lib_profile           =  new profile;
		$lib_profile->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = $lib_profile->personal_function_load_member( $member_id );
    	
		//-----------------------------------------
		// Format settings...
		//-----------------------------------------
		
		foreach( $types as $key => $lang )
		{
			$_comments_selected = ( $key == $member['pp_setting_notify_comments'] ) ? ' selected="selected" ' : '';
			$_friends_selected  = ( $key == $member['pp_setting_notify_comments'] ) ? ' selected="selected" ' : '';
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
		// Personal statement
		//-----------------------------------------
		
		$member['_pp_bio_content']  = $this->ipsclass->my_br2nl( $member['pp_bio_content'] );
		$member['__pp_bio_content'] = $member['pp_bio_content'];
		
		//-----------------------------------------
		// Ok.. show the settings
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->personal_portal_settings( $member );
		
		$this->page_title = $this->ipsclass->lang['m_personal_portal'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
	}

 	/*-------------------------------------------------------------------------*/
 	// DISPLAY NAME: CHANGE COMPLETE
 	/*-------------------------------------------------------------------------*/
 	
 	function display_name_complete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$display_name = trim( $this->ipsclass->input['display_name'] );
		$display_pass = $this->ipsclass->input['display_password'];
		$error        = "";
		$found_name   = 0;
		$banfilters   = array();
		
		//-----------------------------------------
		// CHECK (please)
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['auth_allow_dnames'] OR $this->ipsclass->member['g_dname_changes'] < 1 OR $this->ipsclass->member['g_dname_date'] < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}
		
		//-----------------------------------------
		// Remove 'sneaky' spaces
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['strip_space_chr'] )
    	{
    		// Use hexdec to convert between '0xAD' and chr
			$display_name = str_replace( chr(160), ' ', $display_name );
			$display_name = str_replace( chr(173), ' ', $display_name );
			$display_name = str_replace( chr(240), ' ', $display_name );
			
			$display_name = trim($display_name);
		}
		
		//-----------------------------------------
		// Test unicode name too
		//-----------------------------------------
		
		$unicode_name = preg_replace_callback('/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $display_name);
		$unicode_name  = str_replace( "'" , '&#39;', $unicode_name );
		$unicode_name  = str_replace( "\\", '&#92;', $unicode_name );
		
		//-----------------------------------------
    	// Password Check: Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	
    	//-----------------------------------------
		// Check for current password.
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
		{
		   $this->han_login->login_password_check( $this->ipsclass->member['name'], $display_pass );
		}
		else
		{
		   $this->han_login->login_password_check( $this->ipsclass->member['email'], $display_pass );
		}

    	if ( $this->han_login->return_code != 'SUCCESS' )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
    	}
    	
		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
		}
		
		//-----------------------------------------
		// Grab # changes > 24 hours
		//-----------------------------------------
		
		$time_check = time() - 86400 * $this->ipsclass->member['g_dname_date'];
		
		$name_count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count, MIN(dname_date) as min_date', 'from' => 'dnames_change', 'where' => "dname_member_id={$this->ipsclass->member['id']} AND dname_date > $time_check" ) );
		
		$name_count['count']    = intval( $name_count['count'] );
		$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $time_check;
		
		if ( intval( $name_count['count'] ) >= $this->ipsclass->member['g_dname_changes'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}
		
		//-----------------------------------------
		// Check for missing fields / chars.
		//-----------------------------------------
		
		if ( ! $display_name OR strlen( $display_name ) < 3 AND strlen( $display_name ) > $this->ipsclass->vars['max_user_name_length'] )
		{
			$error .= $this->ipsclass->lang['dname_error_no_name']."<br />";
		}
		
		if ( ! $display_pass )
		{
			$error .= $this->ipsclass->lang['dname_error_no_pass']."<br />";
		}
		
		if ( preg_match( "#[\[\];,\|]#",  str_replace('&#39;', "'", $unicode_name ) ) )
		{
			$error .= $this->ipsclass->lang['dname_error_chars']."<br />";
		}
		
		if( $this->ipsclass->vars['username_characters'] )
		{
			$check_against = preg_quote( $this->ipsclass->vars['username_characters'], "/" );
			
			if( !preg_match( "/^[".$check_against."]+$/i", str_replace('&#39;', "'", $unicode_name ) ) )
			{
				$error .= $this->ipsclass->lang['dname_error_chars']."<br />";
			}
		}
		
		//-----------------------------------------
		// Are they banned [NAMES]?
		//-----------------------------------------
		
		if ( is_array( $banfilters['name'] ) and count( $banfilters['name'] ) )
		{
			foreach ( $banfilters['name'] as $n )
			{
				if ( $n == "" )
				{
					continue;
				}
				
				$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
				
				if ( preg_match( "/^{$n}$/i", $display_name ) )
				{
					$found_name = 1;
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Check for existing name.
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
    											 'from'   => 'members',
    											 'where'  => "members_l_display_name='".strtolower($display_name)."' AND id != ".$this->ipsclass->member['id'],
    											 'limit'  => array( 0,1 ) ) );
    											 
    	$this->ipsclass->DB->exec_query();
    	
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->DB->get_num_rows() )
 		{
    		$found_name = 1;
    	}
    	
    	//-----------------------------------------
		// Check for existing LOG IN name.
		//-----------------------------------------
    	
    	if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
    	{
    		$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
													 'from'   => 'members',
													 'where'  => "members_l_username='".strtolower($display_name)."' AND id != ".$this->ipsclass->member['id'],
													 'limit'  => array( 0,1 ) ) );
    											 
    		$this->ipsclass->DB->exec_query();
    		
    		if ( $this->ipsclass->DB->get_num_rows() )
    		{
    			$found_name = 1;
			}
    	}
    	
    	//-----------------------------------------
    	// Test for unicode name
    	//-----------------------------------------
    	
    	if ( $unicode_name != $display_name )
		{
			//-----------------------------------------
			// Check for existing name.
			//-----------------------------------------
			
			$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id, email",
													 'from'   => 'members',
													 'where'  => "members_l_display_name='".strtolower($unicode_name)."' AND id != ".$this->ipsclass->member['id'],
													 'limit'  => array( 0,1 ) ) );
													 
			$this->ipsclass->DB->exec_query();
			
			//-----------------------------------------
			// Got any results?
			//-----------------------------------------
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$found_name = 1;
			}
			
			//-----------------------------------------
			// Check for existing LOG IN name.
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
														 'from'   => 'members',
														 'where'  => "members_l_username='".strtolower($unicode_name)."' AND id != ".$this->ipsclass->member['id'],
														 'limit'  => array( 0,1 ) ) );
													 
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$found_name = 1;
				}
			}
		}
    	
    	//-----------------------------------------
    	// Got a name?
    	//-----------------------------------------
    	
    	if ( $found_name )
    	{
    		$error .= $this->ipsclass->lang['dname_error_taken']."<br />";
    	}
    	
    	//-----------------------------------------
    	// Got an error?
    	//-----------------------------------------
    	
    	if ( $error )
    	{
    		$this->display_name_change( $error );
    		return;
    	}
    	
    	//-----------------------------------------
    	// Insert into change log
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->force_data_type = array( 'dname_previous' => 'string',
    												  'dname_current'  => 'string' );
    	
    	$this->ipsclass->DB->do_insert( 'dnames_change', array( 'dname_member_id'  => $this->ipsclass->member['id'],
    														    'dname_date'       => time(),
    														    'dname_ip_address' => $this->ipsclass->ip_address,
    														    'dname_previous'   => $this->ipsclass->member['members_display_name'],
    														    'dname_current'    => $display_name ) );
		
		//-----------------------------------------
		// Still here? Change it then
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'members_display_name' => 'string', 'members_l_display_name' => 'string' );	
		$this->ipsclass->DB->do_update( 'members'       , array( 'members_display_name'   => $display_name,
		 														 'members_l_display_name' => strtolower( $display_name ) ), "id=" . $this->ipsclass->member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'contact_name' => 'string' );	
		$this->ipsclass->DB->do_update( 'contacts'      , array( 'contact_name'         => $display_name ), "contact_id="    .$this->ipsclass->member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );
		$this->ipsclass->DB->do_update( 'forums'        , array( 'last_poster_name'     => $display_name ), "last_poster_id=".$this->ipsclass->member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
		$this->ipsclass->DB->do_update( 'sessions'      , array( 'member_name'          => $display_name ), "member_id="     .$this->ipsclass->member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'starter_name' => 'string' );
		$this->ipsclass->DB->do_update( 'topics'        , array( 'starter_name'         => $display_name ), "starter_id="    .$this->ipsclass->member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );
		$this->ipsclass->DB->do_update( 'topics'        , array( 'last_poster_name'     => $display_name ), "last_poster_id=".$this->ipsclass->member['id'] );
		
		//-----------------------------------------
		// Recache moderators
		//-----------------------------------------
		
		if ( $this->ipsclass->member['is_mod'] )
		{
			require_once( ROOT_PATH .'sources/action_admin/moderator.php' );
			$admod = new ad_moderator();
			$admod->ipsclass =& $this->ipsclass;
			
			$admod->rebuild_moderator_cache();
		}
		
		//-----------------------------------------
		// Recache announcements
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/announcements.php' );
		$announcements = new announcements();
		$announcements->ipsclass =& $this->ipsclass;
		$announcements->announce_recache();
				
		//-----------------------------------------
		// Recache forums
		//-----------------------------------------
		
		$this->ipsclass->update_forum_cache();
		
		//-----------------------------------------
		// Bounce back
		//-----------------------------------------
		
		$this->display_name_change( "", $this->ipsclass->lang['dname_change_ok'] );
		return;
	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// DISPLAY NAME: CHANGE START
 	/*-------------------------------------------------------------------------*/
 	
 	function display_name_change( $error="", $okmessage="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$form = array();
		
		//-----------------------------------------
		// CHECK (please)
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['auth_allow_dnames'] OR $this->ipsclass->member['g_dname_changes'] < 1 OR $this->ipsclass->member['g_dname_date'] < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}
		
		$this->ipsclass->input['display_name'] = isset($this->ipsclass->input['display_name']) ? $this->ipsclass->input['display_name'] : '';
		
		$this->ipsclass->vars['username_errormsg'] = str_replace( '{chars}', $this->ipsclass->vars['username_characters'], $this->ipsclass->vars['username_errormsg'] );
		
		//-----------------------------------------
		// Grab # changes > 24 hours
		//-----------------------------------------
		
		$time_check = time() - 86400 * $this->ipsclass->member['g_dname_date'];
		
		$name_count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count, MIN(dname_date) as min_date', 'from' => 'dnames_change', 'where' => "dname_member_id={$this->ipsclass->member['id']} AND dname_date > $time_check" ) );
		
		$name_count['count']    = intval( $name_count['count'] );
		$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $time_check;
		
		//-----------------------------------------
		// Calculate # left
		//-----------------------------------------
		
		$form['_changes_left'] = $this->ipsclass->member['g_dname_changes'] - $name_count['count'];
		$form['_changes_done'] = $name_count['count'];
		
		# Make sure changes done isn't larger than allowed
		# This happens when changing via ACP
		
		if ( $form['_changes_done'] > $this->ipsclass->member['g_dname_changes'] )
		{
			$form['_changes_done'] = $this->ipsclass->member['g_dname_changes'];
		}
		
		$form['_first_change'] = $this->ipsclass->get_date( $name_count['min_date'], 'SHORT', 1 );
		$form['_lang_string']  = sprintf( $this->ipsclass->lang['dname_string'],
											$form['_changes_done'], $this->ipsclass->member['g_dname_changes'],
											$form['_first_change'], $this->ipsclass->member['g_dname_changes'],
											$this->ipsclass->member['g_dname_date'] );
		
		//-----------------------------------------
		// Print
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->ucp_dname_change( $form, $error, $okmessage );
		
		$this->page_title = $this->ipsclass->lang['m_dname_change'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		$this->nav[]      = $this->ipsclass->lang['m_dname_change'];
	}
	
 	/*-------------------------------------------------------------------------*/
 	// ANNOUNCEMENTS: DELETE
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_announce_delete()
	{
		if ( ! $this->ipsclass->member['g_is_supmod'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
		}
		
		$id = intval( $this->ipsclass->input['id'] );
		
		if ( $id )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'announcements', 'where' => 'announce_id='.$id ) );
		}
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/announcements.php' );
		$announcements = new announcements();
		$announcements->ipsclass =& $this->ipsclass;
		$announcements->announce_recache();
		
		$this->mod_announce_start();
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// ANNOUNCEMENTS: SAVE (new/edit)
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_announce_save()
	{
		$type           = $this->ipsclass->input['type'];
		$forums_to_save = "";
		
		if ( ! $this->ipsclass->member['g_is_supmod'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
		}
		
		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['announce_title'] or ! $this->ipsclass->input['announce_post'] )
		{
			$this->mod_announce_form( $type, $this->ipsclass->lang['announce_error_title'] );
			return;
		}
		
		//-----------------------------------------
		// Get forums to add announce in
		//-----------------------------------------
		
		if ( is_array( $_POST['announce_forum'] ) and count( $_POST['announce_forum'] ) )
		{
			if ( in_array( '*', $_POST['announce_forum'] ) )
			{
				$forums_to_save = '*';
			}
			else
			{
				$forums_to_save = implode( ",", $_POST['announce_forum'] );
			}
		}
		
		if ( ! $forums_to_save )
		{
			$this->mod_announce_form( $type, $this->ipsclass->lang['announce_error_forums'] );
			return;
		}
		
		//-----------------------------------------
		// check dates
		//-----------------------------------------
		
		$start_date = 0;
		$end_date   = 0;
		
		if ( strstr( $this->ipsclass->input['announce_start'], '-' ) )
		{
			$start_array = explode( '-', $this->ipsclass->input['announce_start'] );
			
			if ( $start_array[0] and $start_array[1] and $start_array[2] )
			{
				if ( ! checkdate( $start_array[0], $start_array[1], $start_array[2] ) )
				{
					$this->mod_announce_form( $type, $this->ipsclass->lang['announce_error_date'] );
					return;
				}
			}
			
			$start_date = $this->ipsclass->date_gmmktime( 0, 0, 1, $start_array[0], $start_array[1], $start_array[2] );
		}
		
		if ( strstr( $this->ipsclass->input['announce_end'], '-' ) )
		{
			$end_array = explode( '-', $this->ipsclass->input['announce_end']  );
			
			if ( $end_array[0] and $end_array[1] and $end_array[2] )
			{
				if ( ! checkdate( $end_array[0], $end_array[1], $end_array[2] ) )
				{
					$this->mod_announce_form( $type, $this->ipsclass->lang['announce_error_date'] );
					return;
				}
			}
			
			$end_date = $this->ipsclass->date_gmmktime( 23, 59, 59, $end_array[0], $end_array[1], $end_array[2] );
		}
		
		$this->init_parser();
		
		$this->ipsclass->input['announce_post'] = $this->han_editor->process_raw_post( 'announce_post' );

		$this->parser->parse_html 		= isset($this->ipsclass->input['announce_html_enabled']) ? $this->ipsclass->input['announce_html_enabled'] : 0;
		$this->parser->parse_nl2br		= isset($this->ipsclass->input['announce_nlbr_enabled']) ? $this->ipsclass->input['announce_nlbr_enabled'] : 0;
		$this->parser->parse_bbcode		= 1;
		$this->parser->parse_smilies	= 1;
		
		//-----------------------------------------
		// Build save array
		//-----------------------------------------
		
		$save_array = array( 'announce_title'        => $this->ipsclass->input['announce_title'],
							 'announce_post'         => $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->ipsclass->input['announce_post'] ) ),
							 'announce_active'       => isset($this->ipsclass->input['announce_active']) ? $this->ipsclass->input['announce_active'] : 0,
							 'announce_forum'        => $forums_to_save,
							 'announce_html_enabled' => isset($this->ipsclass->input['announce_html_enabled']) ? $this->ipsclass->input['announce_html_enabled'] : 0,
							 'announce_nlbr_enabled' => isset($this->ipsclass->input['announce_nlbr_enabled']) ? $this->ipsclass->input['announce_nlbr_enabled'] : 0,
							 'announce_start'        => $start_date,
							 'announce_end'          => $end_date
						   );
						   
		//-----------------------------------------
		// Save..
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$save_array['announce_member_id'] = $this->ipsclass->member['id'];
			
			$this->ipsclass->DB->do_insert( 'announcements', $save_array );
		}
		else
		{
			if ( $this->ipsclass->input['id'] )
			{
				$this->ipsclass->DB->do_update( 'announcements', $save_array, 'announce_id='.intval($this->ipsclass->input['id']) );
			}
		}
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/announcements.php' );
		$announcements           =  new announcements();
		$announcements->ipsclass =& $this->ipsclass;
		
		$announcements->announce_recache();
		
		$this->mod_announce_start();
		return;
	}
	
 	/*-------------------------------------------------------------------------*/
 	// ANNOUNCEMENTS: FORM (new/edit)
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_announce_form($type='add', $msg="")
	{
		$this->init_parser();
		
		if ( ! $this->ipsclass->member['g_is_supmod'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
		}
		
		$this->han_editor->remove_side_panel = 1;
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$button   = $this->ipsclass->lang['announce_button_add'];
			$announce = array( 'announce_active' => 1, 'announce_id' => 0 );
		
		}
		else
		{
			$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
			$button                = $this->ipsclass->lang['announce_button_edit'];
			$announce              = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'announcements', 'where' => 'announce_id='.$this->ipsclass->input['id'] ) );
			$announce['announce_forum'] = explode( ",", $announce['announce_forum'] );
			$announce['announce_start'] = $announce['announce_start'] ? gmdate( 'm-d-Y', $announce['announce_start'] ) : '';
			$announce['announce_end']   = $announce['announce_end']   ? gmdate( 'm-d-Y', $announce['announce_end'] ) : '';
		}
		
		//-----------------------------------------
		// Do we have _POST?
		//-----------------------------------------
		
		foreach( array( 'announce_html_enabled', 'announce_title', 'announce_post', 'announce_start', 'announce_end', 'announce_forum', 'announce_active' ) as $bit )
		{
			if ( isset($_POST[$bit]) AND $_POST[$bit] )
			{
				$announce[$bit] = $_POST[$bit];
			}
			else if( !isset($announce[$bit]) )
			{
				$announce[$bit] = NULL;
			}
		}
		
		//print_r($_POST);exit;
		
		if( $announce['announce_post'] )
		{
			if ( $this->han_editor->method == 'rte' )
			{
				#$announce['announce_post'] = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $announce['announce_post'] ) );
				$announce['announce_post'] = $this->parser->convert_ipb_html_to_html( $announce['announce_post'] );
			}
			else
			{
				$this->parser->parse_html    = $announce['announce_html_enabled'] ? $announce['announce_html_enabled'] : 0;
				$this->parser->parse_nl2br   = $announce['announce_nlbr_enabled'] ? $announce['announce_nlbr_enabled'] : 0;
				$this->parser->parse_smilies = 1;
				$this->parser->parse_bbcode  = 1;
				
				$announce['announce_post'] = $this->parser->pre_edit_parse( $announce['announce_post'] );
			}
		}
		
		//-----------------------------------------
		// Forums
		//-----------------------------------------
		
		$forum_html = "<option value='*'>{$this->ipsclass->lang['announce_form_allforums']}</option>" . $this->ipsclass->forums->forums_forum_jump(0,1,1);
		
		//-----------------------------------------
		// Save forums?
		//-----------------------------------------
		
		if ( is_array( $announce['announce_forum'] ) and count( $announce['announce_forum'] ) )
		{
			foreach( $announce['announce_forum'] as $f )
			{
				$forum_html = preg_replace( "#option\s+value=[\"'](".preg_quote($f,'#').")[\"']#i", "option value='\\1' selected='selected'", $forum_html );
			}
		}
		
		$announce['announce_active_checked'] = $announce['announce_active'] 	  ? 'checked="checked"'  : '';
		$announce['html_checkbox'] 			 = $announce['announce_html_enabled'] ? "checked='checked' " : '';
		$announce['nlbr_checkbox'] 			 = $announce['announce_nlbr_enabled'] ? "checked='checked' " : '';
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->ucp_announce_form($announce, $button, $forum_html, $type, $this->han_editor->show_editor( $announce['announce_post'], 'announce_post' ), $msg);
		
		$this->page_title = $this->ipsclass->lang['menu_announcements'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		$this->nav[]      = $this->ipsclass->lang['menu_announcements'];
	}
	
 	/*-------------------------------------------------------------------------*/
 	// ANNOUNCEMENTS: START (Show current)
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_announce_start()
	{
		if ( ! $this->ipsclass->member['g_is_supmod'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
		}
		
		//-----------------------------------------
		// Get announcements
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'ucp_get_all_announcements', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		$content = "";
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['announce_start'] )
			{
				$r['announce_starts_converted'] = gmdate( 'M-d-Y', $r['announce_start'] );
			}
			else
			{
				$r['announce_starts_converted'] = '-';
			}
			
			if ( $r['announce_end'] )
			{
				$r['announce_end_converted'] = gmdate( 'M-d-Y', $r['announce_end'] );
			}
			else
			{
				$r['announce_end_converted'] = '-';
			}
			
			if ( $r['announce_forum'] == '*' )
			{
				$r['announce_forum_show'] = $this->ipsclass->lang['announce_page_allforums'];
			}
			else
			{
				$tmp_forums = explode(",",$r['announce_forum']);
				
				if ( is_array( $tmp_forums ) and count($tmp_forums) )
				{
					if ( count($tmp_forums) > 5 )
					{
						$r['announce_forum_show'] = count($tmp_forums).' '.$this->ipsclass->lang['announce_page_numforums'];
					}
					else
					{
						$tmp2 = array();
						
						foreach( $tmp_forums as $id )
						{
							$tmp2[] = "<a href='{$this->ipsclass->base_url}showforum={$id}'>{$this->ipsclass->forums->forum_by_id[ $id ]['name']}</a>";
						}
						
						$r['announce_forum_show'] = implode( "<br />", $tmp2 );
					}
				}	
			}
			
			$r['announce_inactive'] = !$r['announce_active'] ? "<span class='desc'>{$this->ipsclass->lang['announce_page_disabled']}</span>" : '';
			
			$content .= $this->ipsclass->compiled_templates['skin_ucp']->ucp_announce_manage_row( $r );
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->ucp_announce_manage($content);
		
		$this->page_title = $this->ipsclass->lang['menu_announcements'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		$this->nav[]      = $this->ipsclass->lang['menu_announcements'];
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// MEMBER TOOL: START
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_find_user_start($msg="")
	{
		if ( ! $this->ipsclass->member['g_is_supmod'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
		}
		
		$this->ipsclass->input['name'] = isset($this->ipsclass->input['name']) ? $this->ipsclass->input['name'] : NULL;
		
		$this->page_title = $this->ipsclass->lang['cp_edit_user'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		$this->nav[]      = $this->ipsclass->lang['cp_edit_user'];			
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->mod_find_user($msg);
	}
	
	/*-------------------------------------------------------------------------*/
 	// MEMBER TOOL: COMPLETE
 	/*-------------------------------------------------------------------------*/
 	
	function mod_find_user_complete()
	{
		$this->ipsclass->input['name'] = trim(strtolower($this->ipsclass->input['name']));
		
		if ( $this->ipsclass->input['name'] == "" )
		{
			$this->mod_find_user_start($this->ipsclass->lang['cp_no_matches']);
			return;
		}
		
		//-----------------------------------------
		// Query the DB for possible matches
		//-----------------------------------------
		
		$this->start_val = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		$sql = "members_l_username LIKE '{$this->ipsclass->input['name']}%' OR members_l_display_name LIKE '%{$this->ipsclass->input['name']}%'";
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(id) as max', 'from' => 'members', 'where' => $sql ) );
		$this->ipsclass->DB->simple_exec();
		
		$total_possible = $this->ipsclass->DB->fetch_row();
		
		if ($total_possible['max'] < 1)
		{
			$this->mod_find_user_start( $this->ipsclass->lang['cp_no_matches'] );
			return;
		}
		
		$pages = $this->ipsclass->build_pagelinks( array( 'TOTAL_POSS'  => $total_possible['max'],
														  'PER_PAGE'    => 20,
														  'CUR_ST_VAL'  => $this->start_val,
														  'L_SINGLE'    => '&nbsp;',
														  'L_MULTI'     => $this->ipsclass->lang['tpl_pages'],
														  'BASE_URL'    => $this->ipsclass->base_url."act=usercp&amp;CODE=domemtool&amp;name={$this->ipsclass->input['name']}",
														)
												 );
									  
		$content = "";
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'name, members_display_name, id, ip_address, posts, joined, mgroup',
													  'from'   => 'members',
													  'where'  => $sql,
													  'order'  => "joined DESC",
													  'limit'  => array( $this->start_val,20 ) ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['joined']    = $this->ipsclass->get_date( $row['joined'], 'JOINED' );
			$row['groupname'] = $this->ipsclass->make_name_formatted( $this->ipsclass->cache['group_cache'][ $row['mgroup'] ]['g_title'], $row['mgroup'] );
							  
			if ( ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group']) and ($row['mgroup'] == $this->ipsclass->vars['admin_group']) )
			{
				$row['ip_address'] = '--';
			}
			
			$content .= $this->ipsclass->compiled_templates['skin_ucp']->mod_ip_member_row( $row, $this->ipsclass->return_md5_check() );
		}
		
		
		$this->page_title = $this->ipsclass->lang['cp_edit_user'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		$this->nav[]      = $this->ipsclass->lang['cp_edit_user'];		
		$this->mod_find_user_start( $this->ipsclass->compiled_templates['skin_ucp']->mod_ip_member_results($pages, $content) );
	}
	
 	/*-------------------------------------------------------------------------*/
 	// IP TOOL: Start
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_ip_tool_start($msg="")
 	{
		if ( ! $this->ipsclass->member['g_is_supmod'] )
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->mod_ip_start_form($this->ipsclass->input['ip'], $msg);
 		
 		$this->page_title = $this->ipsclass->lang['menu_ipsearch'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		$this->nav[]      = $this->ipsclass->lang['menu_ipsearch'];
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// IP TOOL: Complete
 	/*-------------------------------------------------------------------------*/
 	
 	function mod_ip_tool_complete()
 	{
		if ( ! $this->ipsclass->member['g_is_supmod'] )
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
 		}
 		
 		//-----------------------------------------
		// Remove trailing periods
		//-----------------------------------------
		
		$exact_match     = 1;
		$final_ip_string = trim( $this->ipsclass->input['ip'] );
		$this->start_val = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		if ( strstr( $final_ip_string, '*' ) )
		{
			$exact_match = 0;
			
			$final_ip_string = preg_replace( "/^(.+?)\*(.+?)?$/", "\\1", $final_ip_string ).'%';
		}
		
		//-----------------------------------------
		// H'okay, what have we been asked to do?
		// (that's a metaphorical "we" in a rhetorical question)
		//-----------------------------------------
		
		if ($this->ipsclass->input['iptool'] == 'resolve')
		{
			$resolved = @gethostbyaddr($final_ip_string);
			
			if ($resolved == "")
			{
				$this->mod_ip_tool_start( $this->ipsclass->lang['cp_safe_fail'] );
				return;
			}
			else
			{
				$this->mod_ip_tool_start( sprintf($this->ipsclass->lang['ip_resolve_result'], $final_ip_string, $resolved) );
			}
		}
		else if ($this->ipsclass->input['iptool'] == 'members')
		{
			if ($exact_match == 0)
			{
				$sql = "ip_address LIKE '$final_ip_string'";
			}
			else
			{
				$sql = "ip_address='$final_ip_string'";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'count(id) as max', 'from' => 'members', 'where' => $sql ) );
			$this->ipsclass->DB->simple_exec();
			
			$total_possible = $this->ipsclass->DB->fetch_row();
			
			if ($total_possible['max'] < 1)
			{
				$this->mod_ip_tool_start( $this->ipsclass->lang['cp_no_matches'] );
				return;
			}
			
			$pages = $this->ipsclass->build_pagelinks( array( 'TOTAL_POSS'  => $total_possible['max'],
															  'PER_PAGE'    => 20,
															  'CUR_ST_VAL'  => $this->start_val,
															  'L_SINGLE'    => '&nbsp;',
															  'L_MULTI'     => $this->ipsclass->lang['tpl_pages'],
															  'BASE_URL'    => $this->ipsclass->base_url."act=usercp&amp;CODE=doiptool&amp;iptool=members&amp;ip={$this->ipsclass->input['ip']}",
															)
													 );
										  
			$content = "";
			
			if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				$sql .= "AND mgroup != {$this->ipsclass->vars['admin_group']}";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'name, members_display_name,id, ip_address, posts, joined, mgroup',
														  'from'   => 'members',
														  'where'  => $sql,
														  'order'  => "joined DESC",
														  'limit'  => array( $this->start_val,20 ) ) );
			$this->ipsclass->DB->simple_exec();
		
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['joined']    = $this->ipsclass->get_date( $row['joined'], 'JOINED' );
				$row['groupname'] = $this->ipsclass->make_name_formatted( $this->ipsclass->cache['group_cache'][ $row['mgroup'] ]['g_title'], $row['mgroup'] );

				$content .= $this->ipsclass->compiled_templates['skin_ucp']->mod_ip_member_row( $row, $this->ipsclass->return_md5_check() );
			}
			
			$this->mod_ip_tool_start( $this->ipsclass->compiled_templates['skin_ucp']->mod_ip_member_results($pages, $content) );
		}
		else
		{
			// Find posts then!
			
			if ($exact_match == 0)
			{
				$sql = "ip_address LIKE '$final_ip_string'";
			}
			else
			{
				$sql = "ip_address='$final_ip_string'";
			}
			
			// Get forums we're allowed to view
			
			$aforum = array();
			
			foreach( $this->ipsclass->forums->forum_by_id as $data )
			{
				$data['read_perms'] = isset($data['read_perms']) ? $data['read_perms'] : NULL;
				
				if ( $this->ipsclass->check_perms($data['read_perms']) == TRUE )
				{
					$aforum[] = $data['id'];
				}
			}
			
			if ( count($aforum) < 1)
			{
				$this->mod_ip_tool_start( $this->ipsclass->lang['cp_no_matches'] );
				return;
			}
			
			$the_forums = implode( ",", $aforum);
			
			$this->ipsclass->DB->build_query( array(	'select'	=> 'p.pid',
														'from'		=> array( 'posts' => 'p' ),
														'where'		=> "t.forum_id IN({$the_forums}) AND {$sql}",
														'limit'		=> array( 0,500 ),
														'order'		=> 'pid DESC',
														'add_join'	=> array(
																			array(
																					'select'	=> 't.forum_id',
																					'from'		=> array( 'topics' => 't' ),
																					'where'		=> 't.tid=p.topic_id',
																					'type'		=> 'left'
																				)
																			)
											)		);

			$this->ipsclass->DB->exec_query();
			
			$max_hits = $this->ipsclass->DB->get_num_rows();
		
			$posts  = "";
			
			while ($row = $this->ipsclass->DB->fetch_row() )
			{
				$posts .= $row['pid'].",";
			}
			
			$posts  = preg_replace( "/,$/", "", $posts );
			
			//-----------------------------------------
			// Do we have any results?
			//-----------------------------------------
			
			if ($posts == "")
			{
				$this->mod_ip_tool_start( $this->ipsclass->lang['cp_no_matches'] );
				return;
			}
			
			//-----------------------------------------
			// If we are still here, store the data into the database...
			//-----------------------------------------
			
			$unique_id = md5(uniqid(microtime(),1));
			
			$this->ipsclass->DB->do_insert( 'search_results', array (
													  'id'         => $unique_id,
													  'search_date'=> time(),
													  'post_id'    => $posts,
													  'post_max'   => $max_hits,
													  'sort_key'   => 'p.post_date',
													  'sort_order' => 'desc',
													  'member_id'  => $this->ipsclass->member['id'],
													  'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
											 )        );
			
			$this->mod_ip_tool_start( $this->ipsclass->compiled_templates['skin_ucp']->mod_ip_post_results($unique_id, $max_hits) );
				
			return TRUE;
		}
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Attachments
 	/*-------------------------------------------------------------------------*/
 	
 	function attachments()
 	{
		$info     = array();
 		$start    = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
 		$perpage  = 15;
 		
 		$sort_key = "";
 		
 		switch ($this->ipsclass->input['sort'])
 		{
 			case 'date':
 				$sort_key = 'a.attach_date ASC';
 				$info['date_order'] = 'rdate';
 				$info['size_order'] = 'size';
 				break;
 			case 'rdate':
 				$sort_key = 'a.attach_date DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 			case 'size':
 				$sort_key = 'a.attach_filesize DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'rsize';
 				break;
 			case 'rsize':
 				$sort_key = 'a.attach_filesize ASC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 			default:
 				$sort_key = 'a.attach_date DESC';
 				$info['date_order'] = 'date';
 				$info['size_order'] = 'size';
 				break;
 		}
 		
 		$this->page_title = $this->ipsclass->lang['m_attach'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------
 		
 		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^attach_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		$affected_ids = count($ids);
 		
 		if ( $affected_ids > 0 )
 		{
 			$this->ipsclass->DB->cache_add_query( 'usercp_get_to_delete', array( 'mid' => $this->ipsclass->member['id'], 'aid_array' => $ids ) );

    		$o = $this->ipsclass->DB->cache_exec_query();
 			
			while ( $killmeh = $this->ipsclass->DB->fetch_row( $o ) )
			{
				if ( $killmeh['attach_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_location'] );
				}
				if ( $killmeh['attach_thumb_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
				}
				
				if ( $killmeh['topic_id'] )
				{
					$this->ipsclass->DB->simple_construct( array( 'update' => 'topics', 'set' => 'topic_hasattach=topic_hasattach-1', 'where' => 'tid='.$killmeh['topic_id'] ) );
					$this->ipsclass->DB->simple_shutdown_exec();
				}
				else if( $killmeh['mt_id'] )
				{
					$this->ipsclass->DB->simple_construct( array( 'update' => 'message_topics', 'set' => 'mt_hasattach=mt_hasattach-1', 'where' => 'mt_id='.$killmeh['mt_id'] ) );
					$this->ipsclass->DB->simple_shutdown_exec();
				}
			}
			
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'attachments', 'where' => 'attach_id IN ('.implode(",",$ids).') and attach_member_id='.$this->ipsclass->member['id'] ) );
 		}
 		
 		//-----------------------------------------
 		// Get some stats...
 		//-----------------------------------------
 		
 		$maxspace = intval($this->ipsclass->member['g_attach_max']);
 		
 		if ( $this->ipsclass->member['g_attach_max'] == -1 )
 		{
 			$this->ipsclass->Error( array( 'MSG' => 'no_permission', 'LEVEL' => 1 ) );
 		}
 		
 		//-----------------------------------------
 		// Limit by forums
 		//-----------------------------------------
 		
 		$stats = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count, sum(attach_filesize) as sum',
 																'from'   => 'attachments',
 																'where'  => 'attach_member_id='.$this->ipsclass->member['id'] . " AND attach_rel_module IN( 'post', 'msg' )" ) );
 		
 		if ( $maxspace > 0 )
 		{
			//-----------------------------------------
			// Figure out percentage used
			//-----------------------------------------
			
			$info['has_limit']    = 1;
			$info['full_percent'] = $stats['sum'] ? sprintf( "%.0f", ( ( $stats['sum'] / ($maxspace * 1024) ) * 100) ) : 0;
			
			if ( $info['full_percent'] > 100 )
			{
				$info['full_percent'] = 100;
			}
			
			$info['img_width']    = $info['full_percent'] > 0 ? intval($info['full_percent']) - 4 . "%" : 1;// * 2.4 : 1;
			
			//if ($info['img_width'] > 235)
			//{
			//	$info['img_width'] = 235;
			//}
			
			$this->ipsclass->lang['attach_space_count'] = sprintf( $this->ipsclass->lang['attach_space_count'], $stats['count'], $info['full_percent'] );
			$this->ipsclass->lang['attach_space_used']  = sprintf( $this->ipsclass->lang['attach_space_used'] , $this->ipsclass->size_format(intval($stats['sum'])), $this->ipsclass->size_format($maxspace * 1024) );
 		}
 		else
 		{
 			$info['has_limit'] = 0;
 			$this->ipsclass->lang['attach_space_used']  = sprintf( $this->ipsclass->lang['attach_space_unl'] , $this->ipsclass->size_format(intval($stats['sum'])) );
 		}
 		
 		//-----------------------------------------
 		// Pages
 		//-----------------------------------------
 		
 		$pages = $this->ipsclass->build_pagelinks( array(  'TOTAL_POSS'  => $stats['count'],
														   'PER_PAGE'    => $perpage,
														   'CUR_ST_VAL'  => $start,
														   'L_SINGLE'    => "",
														   'L_MULTI'     => $this->ipsclass->lang['tpl_pages'],
														   'BASE_URL'    => $this->ipsclass->base_url."act=usercp&amp;CODE=attach&amp;sort={$this->ipsclass->input['sort']}",
												  )      );
									  
 		//-----------------------------------------
 		// Get attachments...
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->cache_add_query( 'usercp_get_attachments', array( 'mid' => $this->ipsclass->member['id'], 'order' => $sort_key, 'limit_a' => $start, 'limit_b' => $perpage ) );
    	
    	$this->ipsclass->DB->cache_exec_query();
    	
    	$temp_html = "";
    	
		$this->ipsclass->load_language('lang_topic');
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $row['forum_id'] ]['read_perms']) != TRUE )
			{
				$row['title'] = $this->ipsclass->lang['attach_topicmoved'];
			}
			
			//-----------------------------------------
			// Full attachment thingy
			//-----------------------------------------
			
			if ( $row['attach_rel_module'] == 'post' )
			{
				$row['_type'] = 'post';
			}
			else if ( $row['attach_rel_module'] == 'msg' )
			{
				$row['_type'] = 'msg';
				$row['title'] = $this->ipsclass->lang['attach_inpm'];
			}
			
			$row['image']       = $this->ipsclass->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'];
			
			$row['short_name']  = $this->ipsclass->txt_truncate( $row['attach_file'], 30 );
															  
			$row['attach_date'] = $this->ipsclass->get_date( $row['attach_date'], 'SHORT' );
			
			$row['real_size']   = $this->ipsclass->size_format( $row['attach_filesize'] );
			
			$temp_html .= $this->ipsclass->compiled_templates['skin_ucp']->attachments_row( $row );
		}
    	
    	$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->attachments_top($info, $pages, $temp_html);
 		
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Ignore user.
 	/*-------------------------------------------------------------------------*/
 	
 	function ignore_user_splash($msg="")
 	{
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 		$final_users = array();
 		$temp_users  = array();
 		
 		//-----------------------------------------
 		// Do we have a MESSAGE FRROM GAWD???!@
 		//-----------------------------------------
 		
 		if ( $msg )
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->ucp_message( $this->ipsclass->lang['mi5_error'], $msg );
 		}	
 		
 		//-----------------------------------------
 		// Do we have incoming?
 		//-----------------------------------------
 		
 		if ( intval($this->ipsclass->input['uid']) )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, members_display_name', 'from' => 'members', 'where' => "id=".intval($this->ipsclass->input['uid']) ) );
			$this->ipsclass->DB->simple_exec();
 			
 			$newmem = $this->ipsclass->DB->fetch_row();
 			
 			$this->ipsclass->input['newbox_1'] = $newmem['members_display_name'];
 		}
 		
 		//-----------------------------------------
 		// Stored as userid,userid,userid
 		//-----------------------------------------
 		
 		$ignored_users = explode( ',', $this->ipsclass->member['ignored_users'] );
 		
 		//-----------------------------------------
 		// Get members and check to see if they've
 		// since been moved into a group that cannot
 		// be ignored
 		//-----------------------------------------
 		
 		foreach( $ignored_users as $id )
 		{
 			if ( intval($id) )
 			{
 				$temp_users[] = $id;
 			}
 		}
 		
 		if ( count($temp_users) )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, members_display_name, mgroup, posts',
														  'from'   => 'members',
														  'where'  => "id IN (".implode(",",$temp_users).")"
												 )      );
								 
			$this->ipsclass->DB->simple_exec();
		
 			while ( $m = $this->ipsclass->DB->fetch_row() )
 			{
 				$m['g_title'] = $this->ipsclass->make_name_formatted( $this->ipsclass->cache['group_cache'][ $m['mgroup'] ]['g_title'], $m['mgroup'] );
 				
 				if ( $this->ipsclass->vars['cannot_ignore_groups'] )
				{
					if ( strstr( $this->ipsclass->vars['cannot_ignore_groups'], ','.$m['mgroup'].',' ) )
					{
						continue;
					}
 				}
 				
 				$final_users[ $m['id'] ] = $m;
 			}
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->iu_start();
 		
 		foreach( $final_users as $member )
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->iu_populated_row($member);
 		}
 		

 		$this->ipsclass->input['newbox_1'] = isset($this->ipsclass->input['newbox_1']) ? $this->ipsclass->input['newbox_1'] : NULL;
 		$this->ipsclass->input['newbox_2'] = isset($this->ipsclass->input['newbox_2']) ? $this->ipsclass->input['newbox_2'] : NULL;
 		$this->ipsclass->input['newbox_3'] = isset($this->ipsclass->input['newbox_3']) ? $this->ipsclass->input['newbox_3'] : NULL;
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->iu_add_new();
 		
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// Photo:
 	//
 	// Change / Add / Edit Users Photo
 	/*-------------------------------------------------------------------------*/
 	
 	function photo( $error='' )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval( $this->ipsclass->member['id'] );
		list($p_max, $p_width, $p_height) = explode( ":", $this->ipsclass->member['g_photo_max_vars'] );
		$p_w       = "";
		$p_h       = "";
		$cur_photo = "";
 		$member    = array();
		$rand      = urlencode( microtime() );
		
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
		
		//-----------------------------------------
		// Set up page title
		//-----------------------------------------
		
		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
		//-----------------------------------------
		// Not allowed a photo
		//-----------------------------------------
		
 		if ( $this->ipsclass->member['g_photo_max_vars'] == "" or $this->ipsclass->member['g_photo_max_vars'] == "::" )
 		{
 			// Nothing set up yet...
 			
 			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->dead_section();
 			return;
 		}
 		
		//-----------------------------------------
		// Get profile as lib
		//-----------------------------------------

		require_once( ROOT_PATH . 'sources/action_public/profile.php' );
		$lib_profile 		   =  new profile();
		$lib_profile->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Get all member information
		//-----------------------------------------
		
		$member = $lib_profile->personal_portal_set_information( $lib_profile->personal_function_load_member( $member_id ) );
		
 		//-----------------------------------------
 		// SET DIMENSIONS
 		//-----------------------------------------
 		
 		if ( $p_max )
 		{
 			$this->ipsclass->lang['pph_max']  = sprintf( $this->ipsclass->lang['pph_max'], $p_max );
 			$this->ipsclass->lang['pph_max'] .= sprintf( $this->ipsclass->lang['pph_max2'], $p_width, $p_height );
 		}
 		else
 		{
 			$this->ipsclass->lang['pph_max'] = sprintf( $this->ipsclass->lang['pph_max2'], $p_width, $p_height );
 		}
 		
 		$show_size = "(".$member['pp_main_width'] .' x ' . $member['pp_main_height'].")";
 		
 		//-----------------------------------------
 		// TYPE?
 		//-----------------------------------------
 		
 		if ( $member['pp_main_photo'] )
 		{
 			$cur_photo = "<img src='".$member['pp_main_photo'].'?__rand='. $rand . "' width='". $member['pp_main_width'] ."' height='". $member['pp_main_height'] ."' alt='{$this->ipsclass->lang['pph_title']}' />";
 		}
 		
 		//-----------------------------------------
 		// SHOW THE FORM
 		//-----------------------------------------
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->photo_page( $error, $cur_photo, $show_size, $this->md5_check, $p_max, 500000, $rand );

		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$fj = $this->ipsclass->build_forum_jump();
		$fj = str_replace( "#Forum Jump#", $this->ipsclass->lang['forum_jump'], $fj);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->CP_end();
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->forum_jump( $fj );
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 1, 'NAV' => $this->nav ) );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Forum tracker
 	//
 	// What, you need a definition with that title?
 	// What are you doing poking around in the code for anyway?
 	/*-------------------------------------------------------------------------*/
 	
 	function remove_forum_tracker()
 	{
		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^id-(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		$allowed = array( 'none', 'immediate', 'delayed', 'daily', 'weekly' );
 		
 		//-----------------------------------------
 		// what we doing?
 		//-----------------------------------------
 		
 		if ( count($ids) > 0 )
 		{
 			if ( $this->ipsclass->input['trackchoice'] == 'unsubscribe' )
 			{
 				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'forum_tracker', 'where' => "member_id={$this->ipsclass->member['id']} and forum_id IN (".implode( ",", $ids ).")" ) );
 			}
 			else if ( in_array( $this->ipsclass->input['trackchoice'], $allowed ) )
 			{
 				$this->ipsclass->DB->do_update( 'forum_tracker', array( 'forum_track_type' => $this->ipsclass->input['trackchoice'] ), "member_id={$this->ipsclass->member['id']} and forum_id IN (".implode( ",", $ids ).")" );
 			}
 		}
 			
 	    $this->ipsclass->boink_it($this->ipsclass->base_url."act=UserCP&CODE=50");
 		
 	}
 	
 	function forum_tracker()
 	{
		//-----------------------------------------
 		// Remap...
 		//-----------------------------------------
 		
 		$remap = array( 'none'      => 'subs_none_title',
						'immediate' => 'subs_immediate',
						'delayed'   => 'subs_delayed',
						'daily'     => 'subs_daily',
						'weekly'    => 'subs_weekly'
					  );
					  
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->forum_subs_header();
 		
 		//-----------------------------------------
 		// Query the DB for the subby toppy-ics - at the same time
 		// we get the forum and topic info, 'cos we rule.
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->cache_add_query( 'ucp_get_forum_tracker', array( 'mid' => $this->ipsclass->member['id'] ) );
		$this->ipsclass->DB->cache_exec_query();
		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			while( $forum = $this->ipsclass->DB->fetch_row() )
 			{
 				//-----------------------------------------
 				// Got perms to see this forum?
 				//-----------------------------------------
 				
 				if ( ! $this->ipsclass->forums->forum_by_id[ $forum['id'] ] )
 				{
 					continue;
 				}
 				
 				$forum['folder_icon'] = $this->ipsclass->forums->forums_new_posts($forum);
 				
 				$forum['last_post'] = $this->ipsclass->get_date($forum['last_post'], 'LONG');
						
				$forum['last_topic'] = $this->ipsclass->lang['f_none'];
 				
 				$forum['last_title'] = str_replace( "&#33;" , "!", $forum['last_title'] );
				$forum['last_title'] = str_replace( "&quot;", "\"", $forum['last_title'] );
					
				if ( strlen($forum['last_title']) > 30 )
				{
					$forum['last_title'] = $this->ipsclass->txt_truncate( $forum['last_title'], 30 );
				}
				
				if ($forum['password'] != "")
				{
					$forum['last_topic'] = $this->ipsclass->lang['f_none'];
				}
				else
				{
					$forum['last_topic'] = "<a href='{$this->ipsclass->base_url}showtopic={$forum['last_id']}&amp;view=getlastpost'>{$forum['last_title']}</a>";
				}
			 
							
				if ( isset($forum['last_poster_name']))
				{
					$forum['last_poster'] = $forum['last_poster_id'] ? "<a href='{$this->ipsclass->base_url}showuser={$forum['last_poster_id']}'>{$forum['last_poster_name']}</a>"
																	 : $forum['last_poster_name'];
				}
				else
				{
					$forum['last_poster'] = $this->ipsclass->lang['f_none'];
				}
				
				$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->forum_subs_row($forum, $remap[ $forum['forum_track_type'] ]);
			}
			
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->forum_subs_none();
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->forum_subs_end();
 		
		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// pass change:
 	//
 	// Change the users password.
 	/*-------------------------------------------------------------------------*/
 	
 	function pass_change()
 	{
 		//-----------------------------------------
    	// Do we have another URL that one needs
    	// to visit to register?
    	//-----------------------------------------
    	
    	$this->login_method = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    	
    	if ( $this->login_method['login_maintain_url'] )
    	{
    		$this->ipsclass->boink_it( $this->login_method['login_maintain_url'] );
    		exit();
    	}
    	
		$this->output    .= $this->ipsclass->compiled_templates['skin_ucp']->pass_change();
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// PASSWORD CHAGE COMPLETE
 	/*-------------------------------------------------------------------------*/
 	
 	function do_pass_change()
 	{
		if ( $_POST['current_pass'] == "" or empty($_POST['current_pass']) )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		//-----------------------------------------
 		// Check and trim
 		//-----------------------------------------
 		
 		$cur_pass = trim($this->ipsclass->input['current_pass']);
 		$new_pass = trim($this->ipsclass->input['new_pass_1']);
 		$chk_pass = trim($this->ipsclass->input['new_pass_2']);
 		
 		if ( ( empty($new_pass) ) or ( empty($chk_pass) ) )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		if ($new_pass != $chk_pass)
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_no_match' ) );
 		}
 		
 		//-----------------------------------------
 		// Check password...
 		//-----------------------------------------
 		
 		$this->ipsclass->converge->converge_load_member($this->ipsclass->member['email']);
 		
 		if ( $this->ipsclass->converge->converge_authenticate_member( md5($cur_pass) ) != TRUE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
		}
		
		if ( $this->ipsclass->txt_mb_strlen( $_POST['PassWord'] ) > 32)
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'pass_too_long' ) );
		}
 		
 		//-----------------------------------------
 		// Create new password...
 		//-----------------------------------------
 		
 		$md5_pass = md5($new_pass);
 		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	$this->han_login->change_pass( $this->ipsclass->member['email'], $md5_pass );
    	
    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
    	{
			$this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' => 'han_login_pass_failed' ) );
    	}
 		
 		//-----------------------------------------
 		// Update the DB
 		//-----------------------------------------
 		
 		$this->ipsclass->converge->converge_update_password( $md5_pass, $this->ipsclass->member['email'] );
 		
 		//-----------------------------------------
 		// Update members log in key...
 		//-----------------------------------------
 		
 		$key  = $this->ipsclass->converge->generate_auto_log_in_key();
 		$this->ipsclass->DB->do_update( 'members', array( 'member_login_key' => $key ), 'id='.$this->ipsclass->member['id'] );
 		
 		//-----------------------------------------
 		// Use sync module?
 		//-----------------------------------------
 		
 		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
    		$this->modules->on_pass_change($this->ipsclass->member['id'], $new_pass);
   		}
 		
 		//-----------------------------------------
 		// Redirect...
 		//-----------------------------------------
 		
 		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['pass_redirect'], 'act=UserCP&amp;CODE=00' );
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// email change:
 	//
 	// Change the users email address
 	/*-------------------------------------------------------------------------*/
 	
 	function email_change($msg="")
 	{
 		//-----------------------------------------
    	// Do we have another URL that one needs
    	// to visit to register?
    	//-----------------------------------------
    	
    	$this->login_method = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    	
    	if ( $this->login_method['login_maintain_url'] )
    	{
    		$this->ipsclass->boink_it( $this->login_method['login_maintain_url'] );
    		exit();
    	}
    	
		$txt = $this->ipsclass->lang['ce_current'].$this->ipsclass->member['email'];
 		
 		if ($this->ipsclass->vars['reg_auth_type'])
 		{
 			$txt .= $this->ipsclass->lang['ce_auth'];
 		}
 		
 		if ($this->ipsclass->vars['bot_antispam'])
 		{
			//-----------------------------------------
			// Set up security code
			//-----------------------------------------
			
			// Set a new ID for this reg request...
			
			$regid = md5( uniqid(microtime()) );
			
			if( $this->ipsclass->vars['bot_antispam'] == 'gd' )
			{
				//-----------------------------------------
				// Get 6 random chars
				//-----------------------------------------
								
				$reg_code = strtoupper( substr( md5( mt_rand() ), 0, 6 ) );
			}
			else
			{
				//-----------------------------------------
				// Set a new 6 character numerical string
				//-----------------------------------------
				
				$reg_code = mt_rand(100000,999999);
			}
			
			// Insert into the DB
			
			$this->ipsclass->DB->do_insert( 'reg_antispam', array (
													 'regid'      => $regid,
													 'regcode'    => $reg_code,
													 'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
													 'ctime'      => time(),
										 )       );
		}
		
		$msg = $msg ? $this->ipsclass->lang[$msg] : '';
 		
 		$this->output    .= $this->ipsclass->compiled_templates['skin_ucp']->email_change($txt, $msg);
 		
 		if ($this->ipsclass->vars['bot_antispam'])
 		{
 		
			if ($this->ipsclass->vars['bot_antispam'] == 'gd')
			{
				$this->output = str_replace( "<!--ANTIBOT-->", $this->ipsclass->compiled_templates['skin_ucp']->email_change_gd($regid), $this->output );
			}
			else
			{
				$this->output = str_replace( "<!--ANTIBOT-->", $this->ipsclass->compiled_templates['skin_ucp']->email_change_img($regid), $this->output );
			}
 		
 		}
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// COMPLETE EMAIL ADDRESS CHANGE
 	/*-------------------------------------------------------------------------*/
 	
 	function do_email_change()
 	{
		//-----------------------------------------
 		// Check input
 		//-----------------------------------------
 		
 		if ($_POST['in_email_1'] == "")
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		if ($_POST['in_email_2'] == "")
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
 		}
 		
 		//-----------------------------------------
 		// Authorizing?
 		//-----------------------------------------
 		
 		/*if ($this->ipsclass->member['mgroup'] == $this->ipsclass->vars['auth_group'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'email_change_v' ) );
 		}*/
 		
 		//-----------------------------------------
 		// Check password...
 		//-----------------------------------------
 		
 		$this->ipsclass->converge->converge_load_member($this->ipsclass->member['email']);
 		
 		if ( $this->ipsclass->converge->converge_authenticate_member( md5($this->ipsclass->input['password']) ) != TRUE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
		}
		
 		//-----------------------------------------
 		// Test email addresses
 		//-----------------------------------------
 		
 		$email_one    = strtolower( trim($this->ipsclass->input['in_email_1']) );
 		$email_two    = strtolower( trim($this->ipsclass->input['in_email_2']) );
 		
 		if ($email_one != $email_two)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'email_addy_mismatch' ) );
		}
		
		$email_one = $this->ipsclass->clean_email($email_one);
		
		if ( $email_one == "" )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_email' ) );
		}
		
		//-----------------------------------------
		// Is this email addy taken?
		//-----------------------------------------
		
		if ( $this->ipsclass->converge->converge_check_for_member_by_email( $email_one ) == TRUE )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'email_exists' ) );
		}
		
		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
		}
		
		//-----------------------------------------
		// Check in banned list
		//-----------------------------------------
		
		if ( isset($banfilters['email']) AND is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
		{
			foreach ( $banfilters['email'] as $email )
			{
				$email = str_replace( '\*', '.*' ,  preg_quote($email, "/") );
				
				if ( preg_match( "/^{$email}$/i", $email_one ) )
				{
					$this->ipsclass->Error( array( LEVEL => 1, MSG => 'email_exists' ) );
				}
			}
		}
		
		//-----------------------------------------
		// Anti bot flood...
		//-----------------------------------------
		
		if ($this->ipsclass->vars['bot_antispam'])
 		{
			//-----------------------------------------
			// Check the security code:
			//-----------------------------------------
			
			if ($this->ipsclass->input['regid'] == "")
			{
				$this->email_change('err_security_code');
				return "";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'reg_antispam',
														  'where'  => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'"
												 )      );
								 
			$this->ipsclass->DB->simple_exec();
			
			if ( ! $row = $this->ipsclass->DB->fetch_row() )
			{
				$this->email_change('err_security_code');
				return "";
			}
			
			if ( trim( $this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['reg_code']) ) != $row['regcode'] )
			{
				$this->email_change('err_security_code');
				return "";
			}
			
			$this->ipsclass->DB->do_delete( 'reg_antispam', "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'" );
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	$this->han_login->change_email( $this->ipsclass->member['email'], $email_one );
    	
    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
    	{
	    	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'email_exists' ) );
    	}
		
		//-----------------------------------------
		// Update converge...
		//-----------------------------------------
		
		$this->ipsclass->converge->converge_update_member( $this->ipsclass->member['email'], $email_one );
		
		//-----------------------------------------
		// Update dupemail
		//-----------------------------------------
		
		if ( $this->ipsclass->member['bio'] == 'dupemail' )
		{
			$this->ipsclass->DB->do_update( 'member_extra', array( 'bio' => '' ), 'id='.$this->ipsclass->member['id'] );
		}
		
		//-----------------------------------------
 		// Use sync module?
 		//-----------------------------------------
 		
 		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
    		$this->modules->on_email_change($this->ipsclass->member['id'], $email_one);
   		}
   		
		//-----------------------------------------
		// Require new validation? NON ADMINS ONLY
		//-----------------------------------------
		
		if ($this->ipsclass->vars['reg_auth_type'] AND ! $this->ipsclass->member['g_access_cp'] )
		{
			$validate_key = md5( $this->ipsclass->make_password() . time() );
			
			//-----------------------------------------
			// Update the new email, but enter a validation key
			// and put the member in "awaiting authorisation"
			// and send an email..
			//-----------------------------------------
			
			$db_str = array(
							'vid'         => $validate_key,
							'member_id'   => $this->ipsclass->member['id'],
							#'real_group'  => $this->ipsclass->member['mgroup'],
							'temp_group'  => $this->ipsclass->vars['auth_group'],
							'entry_date'  => time(),
							'coppa_user'  => 0,
							'email_chg'   => 1,
							'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
							'prev_email'  => $this->ipsclass->member['email'],
						   );

			if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['auth_group'] )
			{
				$db_str['real_group'] = $this->ipsclass->member['mgroup'];
			}
			
			$this->ipsclass->DB->do_insert( 'validating', $db_str );
			
			$this->ipsclass->DB->do_update( 'members' , array(
												'mgroup' => $this->ipsclass->vars['auth_group'],
												'email'  => $email_one,
											 ), 'id='.$this->ipsclass->member['id']
						  );
			
			//-----------------------------------------
			// Update their session with the new member group
			//-----------------------------------------
			
			if ( $this->ipsclass->session_id )
			{
				$this->ipsclass->DB->do_update( 'sessions', array( 'member_name'  => '',
												   'member_id'    => 0,
												   'member_group' => $this->ipsclass->vars['guest_group']
												 ), "member_id=".$this->ipsclass->member['id']." and id='".$this->ipsclass->session_id."'"
							  );
			}
 			
 			//-----------------------------------------
 			// Kill the cookies to stop auto log in
 			//-----------------------------------------
 			
 			$this->ipsclass->my_setcookie( 'pass_hash'  , '-1', 0 );
 			$this->ipsclass->my_setcookie( 'member_id'  , '-1', 0 );
 			$this->ipsclass->my_setcookie( 'session_id' , '-1', 0 );
 			
	        //-----------------------------------------
	    	// Get the emailer module
			//-----------------------------------------
			
			require ROOT_PATH."sources/classes/class_email.php";
			
			$this->email = new emailer();
			$this->email->ipsclass =& $this->ipsclass;
			$this->email->email_init();
 			
 			//-----------------------------------------
 			// Dispatch the mail, and return to the activate form.
 			//-----------------------------------------
 			
 			$this->email->get_template("newemail");
				
			$this->email->build_message( array(
												'NAME'         => $this->ipsclass->member['members_display_name'],
												'THE_LINK'     => $this->ipsclass->base_url_nosess."?act=Reg&CODE=03&type=newemail&uid=".$this->ipsclass->member['id']."&aid=".$validate_key,
												'ID'           => $this->ipsclass->member['id'],
												'MAN_LINK'     => $this->ipsclass->base_url_nosess."?act=Reg&CODE=07",
												'CODE'         => $validate_key,
											  )
										);
										
			$this->email->subject = $this->ipsclass->lang['lp_subject'].' '.$this->ipsclass->vars['board_name'];
			$this->email->to      = $email_one;
			
			$this->email->send_mail();
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['ce_redirect'], 'act=Reg&amp;CODE=07' );
		}
		else
		{
			//-----------------------------------------
			// No authorisation needed, change email addy and return
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'members', array( 'email' => $email_one ), 'id='.$this->ipsclass->member['id'] );
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['email_changed_now'], 'act=UserCP&amp;CODE=00' );
		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// tracker:
 	//
 	// Print the subscribed topics listings
 	/*-------------------------------------------------------------------------*/
 	
 	function tracker()
 	{
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->subs_header();
 		
 		//-----------------------------------------
 		// Remap...
 		//-----------------------------------------
 		
 		$remap = array( 'none'      => 'subs_none_title',
						'immediate' => 'subs_immediate',
						'delayed'   => 'subs_delayed',
						'daily'     => 'subs_daily',
						'weekly'    => 'subs_weekly'
					  );
 		
 		//-----------------------------------------
 		// Get forums module
 		//-----------------------------------------
 		
 		require_once( ROOT_PATH.'sources/action_public/forums.php' );
 		$this->forums = new forums();
 		$this->forums->ipsclass =& $this->ipsclass;
 		
 		$this->forums->init();
 		
 		//-----------------------------------------
 		// Are we checking for auto-prune?
 		//-----------------------------------------
 		
 		$auto_explain = $this->ipsclass->lang['no_auto_prune'];
 		
 		if ($this->ipsclass->vars['subs_autoprune'] > 0)
 		{
			$auto_explain = sprintf( $this->ipsclass->lang['auto_prune'], $this->ipsclass->vars['subs_autoprune'] );
 		}
 		
 		//-----------------------------------------
 		// Do we have an incoming date cut?
 		//-----------------------------------------
 		
 		$this->ipsclass->input['datecut'] = isset($this->ipsclass->input['datecut']) ? intval($this->ipsclass->input['datecut']) : 0;
 		
 		$date_cut   = $this->ipsclass->input['datecut'] ? $this->ipsclass->input['datecut'] : 30;
 		
 		$date_query = $date_cut != 1000 ? " AND t.last_post > '".(time() - ($date_cut*86400))."' " : "";
 		
 		//-----------------------------------------
 		// Get read topic markers
 		//-----------------------------------------
 		 		
		$topic_array = array();
		$forum_array = array();

		if ( $this->ipsclass->vars['db_topic_read_cutoff'] )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'topic_markers',
														  'where'  => "marker_member_id=".$this->ipsclass->member['id'],
												)      );
									  
			$this->ipsclass->DB->simple_exec();
			
			while( $db_row = $this->ipsclass->DB->fetch_row() )
			{
				$markers_read = "";
				$markers_read = unserialize(stripslashes($db_row['marker_topics_read']) );
				
				//-----------------------------------------
				// Got read topics?
				//-----------------------------------------
				
				if ( is_array( $markers_read ) and count( $markers_read ) )
				{
					foreach( $markers_read as $tid => $date )
					{
						$topic_array[ $tid ]['db_read'] = $date > $db_row['marker_last_cleared'] ? $date : $db_row['marker_last_cleared'];
					}
				}
				else if( is_array( $markers_read ) and !count ( $markers_read ) )
				{
					$forum_array[] = $db_row['marker_forum_id'];
				}				
			}
		}
		 		
 		//-----------------------------------------
 		// Query the DB for the subby toppy-ics - at the same time
 		// we get the forum and topic info, 'cos we rule.
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->cache_add_query( 'ucp_get_topic_tracker', array( 'mid' => $this->ipsclass->member['id'], 'date_query' => $date_query ) );
		$this->ipsclass->DB->cache_exec_query();
		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			$last_forum_id = -1;
 		
 			while( $topic = $this->ipsclass->DB->fetch_row() )
 			{
	 			$topic['db_read'] = $topic_array[$topic['tid']]['db_read'] ? $topic_array[$topic['tid']]['db_read'] : 0;

	 			if( $topic['db_read'] == 0 )
	 			{
		 			$topic['db_read'] = in_array($topic['forum_id'], $forum_array ) ? time() : 0;
	 			}
	 			
 				//-----------------------------------------
 				// Got perms to see this forum?
 				//-----------------------------------------
 				
 				if ( ! $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ] )
 				{
 					continue;
 				}
 				
 				if ( $last_forum_id != $topic['forum_id'] )
 				{
 					$last_forum_id = $topic['forum_id'];
 					
 					$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->subs_forum_row($topic['forum_id'], $topic['forum_name']);
 				}
				
				$topic['last_post_date']  = $this->ipsclass->get_date( $topic['last_post'], 'LONG' );
				
				if ( $topic['description'] )
				{
					$topic['description'] .= "<br />";
				}
				
				$topic['track_started'] = $this->ipsclass->get_date( $topic['track_started'], 'LONG' );
				
				$topic = $this->forums->parse_data($topic);
				
				$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->subs_row($topic, $remap[ $topic['topic_track_type'] ]);
			}
			
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->subs_none();
		}
		
		// Build date box
		
		$date_box = "<option value='1'>".$this->ipsclass->lang['subs_today']."</option>\n";
		
		foreach( array( 1,7,14,21,30,60,90,365 ) as $day )
		{
			$selected = $day == $date_cut ? ' selected="selected"' : '';
				
			$date_box .= "<option value='$day'$selected>".sprintf( $this->ipsclass->lang['subs_day'], $day )."</option>\n";
		}
		
		if ( $date_cut == 1000 )
		{
			$date_box .= "<option value='1000' selected='selected'>".$this->ipsclass->lang['subs_all']."</option>\n";
		}
		else
		{
			$date_box .= "<option value='1000'>".$this->ipsclass->lang['subs_all']."</option>\n";
		}
			
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->subs_end($auto_explain, $date_box);
 		
		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// UPDATE TRACKER
 	/*-------------------------------------------------------------------------*/
 	
 	function do_update_tracker()
 	{
		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------
 		
 		if ($this->ipsclass->input['request_method'] != 'post')
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
 		}
 		
 		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^id-(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		$allowed = array( 'none', 'immediate', 'delayed', 'daily', 'weekly' );
 		
 		//-----------------------------------------
 		// what we doing?
 		//-----------------------------------------
 		
 		if ( count($ids) > 0 )
 		{
 			if ( $this->ipsclass->input['trackchoice'] == 'unsubscribe' )
 			{
 				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'tracker', 'where' => "member_id='".$this->ipsclass->member['id']."' and trid IN (".implode( ",", $ids ).")" ) );
 			}
 			else if ( in_array( $this->ipsclass->input['trackchoice'], $allowed ) )
 			{
 				$this->ipsclass->DB->do_update( 'tracker', array( 'topic_track_type' => $this->ipsclass->input['trackchoice'] ), "trid IN (".implode( ",", $ids ).")" );
 			}
 		}
 			
 	    $this->ipsclass->boink_it($this->ipsclass->base_url."act=UserCP&CODE=26");
 		
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// BOARD PREFS:
 	//
 	// Set up view avatar, sig, time zone, etc.
 	/*-------------------------------------------------------------------------*/
 	
 	function board_prefs()
 	{
		$time = $this->ipsclass->get_date( time(), 'LONG', 1 );
 		
 		//-----------------------------------------
 		// Do we have a user stored offset, or use the board default:
 		//-----------------------------------------
 		
 		$offset = ( $this->ipsclass->member['time_offset'] != "" ) ? $this->ipsclass->member['time_offset'] : $this->ipsclass->vars['time_offset'];
 		
 		$time_select = "<select name='u_timezone' class='forminput'>";
 		
 		//-----------------------------------------
 		// Loop through the langauge time offsets and names to build our
 		// HTML jump box.
 		//-----------------------------------------
 		
 		foreach( $this->ipsclass->lang as $off => $words )
 		{
 			if ( preg_match("/^time_(-?[\d\.]+)$/", $off, $match))
 			{
				$time_select .= $match[1] == $offset ? "<option value='{$match[1]}' selected='selected'>$words</option>\n"
												     : "<option value='{$match[1]}'>$words</option>\n";
 			}
 		}
 		
 		$time_select .= "</select>";
 		
 		//-----------------------------------------
 		// DST IN USE?
 		//-----------------------------------------
 		
 		if ($this->ipsclass->member['dst_in_use'])
 		{
 			$dst_check = 'checked="checked"';
 		}
 		else
 		{
 			$dst_check = '';
 		}
 		
 		//-----------------------------------------
 		// DST CORRECTION IN USE?
 		//-----------------------------------------
 		
 		if ($this->ipsclass->member['members_auto_dst'])
 		{
 			$dst_correction = 'checked="checked"';
 		}
 		else
 		{
 			$dst_correction = '';
 		}
 		
 		//-----------------------------------------
 		// Post page contents
 		//-----------------------------------------
 		
 		if ($this->ipsclass->vars['postpage_contents'] == "")
		{
			$this->ipsclass->vars['postpage_contents'] = '5,10,15,20,25,30,35,40';
		}
		
		if ($this->ipsclass->vars['topicpage_contents'] == "")
		{
			$this->ipsclass->vars['topicpage_contents'] = '5,10,15,20,25,30,35,40';
		}
 		
 		list($post_page, $topic_page) = explode( "&", $this->ipsclass->member['view_prefs'] );
 		
 		if ($post_page == "")
 		{
 			$post_page = -1;
 		}
 		if ($topic_page == "")
 		{
 			$topic_page = -1;
 		}
 		
 		$pp_a = array();
 		$tp_a = array();
 		$post_select  = "";
 		$topic_select = "";
 		
 		$pp_a[] = array( '-1', $this->ipsclass->lang['pp_use_default'] );
 		$tp_a[] = array( '-1', $this->ipsclass->lang['pp_use_default'] );
 		
 		foreach( explode( ',', $this->ipsclass->vars['postpage_contents'] ) as $n )
 		{
 			$n      = intval(trim($n));
 			$pp_a[] = array( $n, $n );
 		}
 		
 		foreach( explode( ',', $this->ipsclass->vars['topicpage_contents'] ) as $n )
 		{
 			$n      = intval(trim($n));
 			$tp_a[] = array( $n, $n );
 		}
 		
 		//-----------------------------------------
 		// Select boxes
 		//-----------------------------------------
 		
 		foreach( $pp_a as $data )
 		{
 			$post_select .= ($data[0] == $post_page) ? "<option value='{$data[0]}' selected='selected'>{$data[1]}</option>\n" : "<option value='{$data[0]}'>{$data[1]}</option>\n";
 		}
 		
 		foreach( $tp_a as $data )
 		{
 			$topic_select .= ($data[0] == $topic_page) ? "<option value='{$data[0]}' selected='selected'>{$data[1]}</option>\n" : "<option value='{$data[0]}'>{$data[1]}</option>\n";
 		}
 		
 		//-----------------------------------------
 		// Print header
 		//-----------------------------------------
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->settings_header($this->member, $time_select, $time, $dst_check, $this->md5_check, $dst_correction);
 		
 		//-----------------------------------------
 		// Cookie settings
 		//-----------------------------------------
 		
 		$hide_sess   = $this->ipsclass->my_getcookie('hide_sess');
 		$open_qreply = $this->ipsclass->member['_cache']['qr_open'];
 		
 		if ( $open_qreply == FALSE )
 		{
 			$open_qreply = 0;
 		}
 		
 		//-----------------------------------------
 		// RTE : STD
 		//-----------------------------------------
 		
 		$editor_choice = "<select name='editor_choice' class='forminput'>";
 		
 		if ( $this->ipsclass->member['members_editor_choice'] == 'rte' )
 		{
 			$editor_choice .= "<option value='rte' selected='selected'>{$this->ipsclass->lang['ucp_use_rte']}</option>\n<option value='std'>{$this->ipsclass->lang['ucp_use_std']}</option>";
 		}
 		else
 		{
 			$editor_choice .= "<option value='rte'>{$this->ipsclass->lang['ucp_use_rte']}</option>\n<option value='std' selected='selected'>{$this->ipsclass->lang['ucp_use_std']}</option>";
 		}
 		
 		$editor_choice .= "</select>";
 		
 		//-----------------------------------------
 		// View avatars, signatures and images..
 		//-----------------------------------------
 		
 		$view_ava   = "<select name='VIEW_AVS' class='forminput'>";
 		$view_sig   = "<select name='VIEW_SIGS' class='forminput'>";
 		$view_img   = "<select name='VIEW_IMG' class='forminput'>";
 		$view_pop   = "<select name='DO_POPUP' class='forminput'>";
 		$html_sess  = "<select name='HIDE_SESS' class='forminput'>";
 		$html_qr    = "<select name='OPEN_QR' class='forminput'>";
 		$disable_pm = "<select name='disable_messenger' class='forminput'>";
 		
 		$view_ava .= $this->ipsclass->member['view_avs'] ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 		
 		$view_sig .= $this->ipsclass->member['view_sigs'] ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 		
 		$view_img .= $this->ipsclass->member['view_img'] ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 											  
 		$view_pop .= $this->ipsclass->member['view_pop'] ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 		
 		$html_sess .= $hide_sess == 1          ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 		
 		$html_qr   .= $open_qreply == 1        ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 		
 		$disable_pm .= $this->ipsclass->member['members_disable_pm'] ? "<option value='1' selected='selected'>".$this->ipsclass->lang['yes']."</option>\n<option value='0'>".$this->ipsclass->lang['no']."</option>"
 											   : "<option value='1'>".$this->ipsclass->lang['yes']."</option>\n<option value='0' selected='selected'>".$this->ipsclass->lang['no']."</option>";
 		
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->settings_end( array ( 'IMG'  => $view_img."</select>",
																								'SIG'  => $view_sig."</select>",
																								'AVA'  => $view_ava."</select>",
																								'POP'  => $view_pop."</select>",
																								'SESS' => $html_sess."</select>",
																								'QR'   => $html_qr."</select>",
																								'PMS'  => $disable_pm."</select>",
																								'TPS'  => $topic_select,
																								'PPS'  => $post_select,
																								'editor' => $editor_choice,
																					  )       );
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// EMAIL SETTINGS:
 	//
 	// Set up the email stuff.
 	/*-------------------------------------------------------------------------*/
 	
 	function email_settings()
 	{
		// PM_REMINDER: First byte = Email PM when received new
 		//   			Second byte= Show pop-up when new PM received
 						
 		
 		$info = array();
 		
 		foreach ( array('hide_email', 'allow_admin_mails', 'email_full', 'email_pm', 'auto_track') as $k )
 		{
 			if (!empty($this->ipsclass->member[ $k ]))
 			{
 				$info[$k] = 'checked="checked"';
 			}
 			else
 			{
	 			$info[$k] = '';
 			}
 		}
 		
 		$info['key'] = $this->md5_check;
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->email($info);
 		
 		//-----------------------------------------
 		// Update select box
 		//-----------------------------------------
 		
 		$this->output = str_replace( "<option value=\"{$this->ipsclass->member['auto_track']}\">", "<option value='{$this->ipsclass->member['auto_track']}' selected='selected'>", $this->output );
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// custom sort routine:
 	//
 	// Like wot is seys on the tin
 	/*-------------------------------------------------------------------------*/
 	
 	function sort_avatars($a, $b)
 	{
 		$aa = strtolower($a[1]);
 		$bb = strtolower($b[1]);
 		
 		if ( $aa == $bb ) return 0;
 		
 		return ( $aa > $bb ) ? 1 : -1;
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// AVATAR:
 	//
 	// Displays the avatar choices
 	/*-------------------------------------------------------------------------*/
 	
 	function avatar_gallery()
 	{
		$avatar_gallery    = array();
 		$av_categories     = array( 0 => array( "root", $this->ipsclass->lang['av_root'] ) );
 		
 		$av_cat_selected   = preg_replace( "/[^\w\s_\-]/", "", $this->ipsclass->input['av_cat'] );
 		$av_cat_found      = FALSE;
 		$av_human_readable = "";
 		$av_cat_real	   = "";
 		
 		if ($av_cat_selected == 'root')
 		{
 			$av_cat_selected   = "";
 			$av_human_readable = $this->ipsclass->lang['av_root'];
 		}
 		
 		//-----------------------------------------
 		// Get the avatar categories
 		//-----------------------------------------
 		
 		$dh = opendir( CACHE_PATH.'style_avatars' );
 		
 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
			if ( $file != "." && $file != ".." )
			{
				if ( is_dir( CACHE_PATH.'style_avatars'."/".$file ) )
				{
					if ( $file == $av_cat_selected )
					{
						$av_cat_found      = TRUE;
						$av_human_readable = str_replace( "_", " ", $file );
					}
					
					$av_categories[] = array( $file, str_replace( "_", " ", $file ) );
				}
			}
 		}
 		
 		closedir( $dh );
 		
 		//-----------------------------------------
 		// SORT IT OUT YOU MUPPET!!
 		//-----------------------------------------
 		
 		usort( $av_categories, array( 'UserCP', 'sort_avatars' ) );
 		reset( $av_categories );
 		
 		//-----------------------------------------
 		// Did we find the directory?
 		//-----------------------------------------
 		
 		if ($av_cat_selected)
 		{
 			if ( $av_cat_found != TRUE )
 			{
 				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'av_no_gallery' ) );
 			}
 			
 			$av_cat_real = "/".$av_cat_selected;
 		}
 		
 		//-----------------------------------------
 		// Get the avatar images for this category
 		//-----------------------------------------
 		
 		$dh = opendir( CACHE_PATH.'style_avatars'.$av_cat_real);
 		
 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
 			if ( ! preg_match( "/^..?$|^index|^\.ds_store|^\.htaccess/i", $file ) )
 			{
 				if ( is_file( CACHE_PATH.'style_avatars'.$av_cat_real."/".$file) )
 				{
 					if ( preg_match( "/\.(gif|jpg|jpeg|png|swf)$/i", $file ) )
 					{
 						$av_gall_images[] = $file;
 					}
 				}
 			}
 		}
 		
 		//-----------------------------------------
 		// SORT IT OUT YOU PLONKER!!
 		//-----------------------------------------
 		
 		if ( is_array($av_gall_images) and count($av_gall_images) )
 		{
 			natcasesort($av_gall_images);
 			reset($av_gall_images);
 		}
 		
 		//-----------------------------------------
 		// Render drop down box..
 		//-----------------------------------------
 		
 		$av_gals = "<select name='av_cat' class='forminput'>\n";
 		
 		foreach( $av_categories as $cat )
 		{
 			$av_gals .= "<option value='".$cat[0]."'>".$cat[1]."</option>\n";
 		}
 		
 		$av_gals .= "</select>\n";
 		
 		closedir( $dh );
 		
 		$gal_cols = $this->ipsclass->vars['av_gal_cols'] == "" ? 5 : $this->ipsclass->vars['av_gal_cols'];
 		$gal_rows = !isset($this->ipsclass->vars['av_gal_rows']) OR $this->ipsclass->vars['av_gal_rows'] == "" ? 3 : $this->ipsclass->vars['av_gal_rows'];
 		
 		$gal_found = count($av_gall_images);
 		
 		//-----------------------------------------
 		// Produce the avatar gallery sheet
 		//-----------------------------------------
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_start_table($av_human_readable,$av_gals,urlencode($av_cat_selected), $this->md5_check);
 		
 		$c = 0;
 		
 		if ( is_array($av_gall_images) and count($av_gall_images) )
 		{
			foreach( $av_gall_images as $img )
			{
				$c++;
				
				if ($c == 1)
				{
					$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_start_row();
				}
				
				$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_cell_row(
																	  $av_cat_real."/".$img,
																	  str_replace( "_", " ", preg_replace( "/^(.*)\.\w+$/", "\\1", $img ) ),
																	  urlencode($img)
																	);
				
				
				if ($c == $gal_cols)
				{
					$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_end_row();
					
					$c = 0;
				}
				
			}
 		}
 		
 		if ($c != $gal_cols)
 		{
			for ($i = $c ; $i < $gal_cols ; ++$i)
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_blank_row();
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_end_row();
		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_gallery_end_table();
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SHOW AVATAR
 	/*-------------------------------------------------------------------------*/
 	
 	function avatar()
 	{
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
			 	
		//-----------------------------------------
 		// Organise the dimensions
 		//-----------------------------------------
 		
 		if( strpos( $this->ipsclass->member['avatar_size'], "x" ) )
 		{
 			list( $this->ipsclass->member['AVATAR_WIDTH'] , $this->ipsclass->member['AVATAR_HEIGHT']  ) = explode ( "x", strtolower($this->ipsclass->member['avatar_size']) );
		}
		
 		list( $this->ipsclass->vars['av_width']       , $this->ipsclass->vars['av_height']        ) = explode ( "x", strtolower($this->ipsclass->vars['avatar_dims']) );
 		list( $w, $h ) = explode ( "x", strtolower($this->ipsclass->vars['avatar_def']) );
 		
 		//-----------------------------------------
 		// Get the users current avatar to display
 		//-----------------------------------------
 		
 		$my_avatar = $this->ipsclass->get_avatar( $this->ipsclass->member['avatar_location'], 1, $this->ipsclass->member['avatar_size'], $this->ipsclass->member['avatar_type'], 1 );
 		
 		$my_avatar = $my_avatar ? $my_avatar : 'noavatar';
 		
 		//-----------------------------------------
 		// Get the avatar gallery
 		//-----------------------------------------
 		
 		$avatar_gallery = array();
 		$av_categories  = array( 0 => array( "root", $this->ipsclass->lang['av_root'] ) );
 		
 		//-----------------------------------------
 		// Get the avatar categories
 		//-----------------------------------------
 		
 		$dh = opendir( CACHE_PATH.'style_avatars' );
 		
 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
			if ( $file != "." && $file != ".." )
			{
				if ( is_dir( CACHE_PATH.'style_avatars'."/".$file ) )
				{
					/*if ( $file == $av_cat_selected )
					{
						$av_cat_found = TRUE;
					}*/
					
					$av_categories[] = array( $file, str_replace( "_", " ", $file ) );
				}
			}
 		}
 		
 		closedir( $dh );
 		
 		usort( $av_categories, array( 'UserCP', 'sort_avatars' ) );
 		reset( $av_categories );
 		
 		//-----------------------------------------
 		// Get the avatar gallery selected
 		//-----------------------------------------
 		
 		$url_avatar = "http://";
 		 		
 		$avatar_type = "na";
 		
 		if ( ($this->ipsclass->member['avatar_location'] != "") and ($this->ipsclass->member['avatar_location'] != "noavatar") )
 		{
 			if ( ! $this->ipsclass->member['avatar_type'] )
 			{
				if ( preg_match( "/^upload:/", $this->ipsclass->member['avatar'] ) )
				{
					$avatar_type = "upload";
				}
				else if ( ! preg_match( "/^http/i", $this->ipsclass->member['avatar'] ) )
				{
					$avatar_type = "local";
				}
				else
				{
					$url_avatar = $this->ipsclass->member['avatar'];
					$avatar_type = "url";
				}
			}
			else
			{
				switch ($this->ipsclass->member['avatar_type'])
				{
					case 'upload':
						$avatar_type = 'upload';
						break;
					case 'url':
						$avatar_type = 'url';
						$url_avatar  = $this->ipsclass->member['avatar_location'];
						break;
					default:
						$avatar_type = 'local';
						break;
				}
			}
 		}
 		
 		//-----------------------------------------
 		// Render drop down box..
 		//-----------------------------------------
 		
 		$av_gals = "<select name='av_cat' class='forminput'>\n";
 		
 		foreach( $av_categories as $cat )
 		{
 			$av_gals .= "<option value='".$cat[0]."'>".$cat[1]."</option>\n";
 		}
 		
 		$av_gals .= "</select>\n";
 		
 		
 		//-----------------------------------------
 		// Rest of the form..
 		//-----------------------------------------
 		
 		$formextra   = "";
 		$hidden_field = "";
 		
 		if ($this->ipsclass->member['g_avatar_upload'] == 1)
 		{
 			$formextra    = " enctype='multipart/form-data'";
			$hidden_field = "<input type='hidden' name='MAX_FILE_SIZE' value='9000000' />";
		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->avatar_main( array (
															'MEMBER'               => $this->member,
															'avatar_galleries'     => $av_gals,
															'current_url_avatar'   => $url_avatar,
															'current_avatar_image' => $my_avatar,
															'current_avatar_type'  => $this->ipsclass->lang['av_t_'.$avatar_type],
															'current_avatar_dims'  => $this->ipsclass->member['avatar_size'] == "x" ? "" : $this->ipsclass->member['avatar_size'],
												 )  , $formextra, $hidden_field, $this->md5_check     );
		
		//-----------------------------------------
 		// Autosizing or manual sizing?
 		//-----------------------------------------
 												 
		$size_html = $this->ipsclass->vars['disable_ipbsize'] ? $this->ipsclass->compiled_templates['skin_ucp']->avatar_mansize() : $this->ipsclass->compiled_templates['skin_ucp']->avatar_autosize();
 		
		//-----------------------------------------
 		// Can we use a URL avatar?
 		//-----------------------------------------
 		
 		if ($this->ipsclass->vars['avatar_url'])
 		{											 
 			$this->output = str_replace( "<!--IBF.EXTERNAL_TITLE-->",  $this->ipsclass->compiled_templates['skin_ucp']->avatar_external_title(), $this->output );
 			$this->output = str_replace( "<!--IBF.URL_AVATAR-->",  $this->ipsclass->compiled_templates['skin_ucp']->avatar_url_field($url_avatar), $this->output );
 			$this->output = str_replace( "<!--IPB.SIZE-->", $size_html, $this->output );
 			$this->ipsclass->lang['av_text_url'] = sprintf( $this->ipsclass->lang['av_text_url'], $this->ipsclass->vars['av_width'], $this->ipsclass->vars['av_height'] );
 		}
 		else
 		{
 			$this->ipsclass->lang['av_text_url'] = "";
 		}
 		
 		//-----------------------------------------
 		// Can we use an uploaded avatar?
 		//-----------------------------------------
 		
		if ($this->ipsclass->member['g_avatar_upload'] == 1)
		{
			$this->output = str_replace( "<!--IBF.EXTERNAL_TITLE-->",  $this->ipsclass->compiled_templates['skin_ucp']->avatar_external_title(), $this->output );
			$this->output = str_replace( "<!--IBF.UPLOAD_AVATAR-->", $this->ipsclass->compiled_templates['skin_ucp']->avatar_upload_field(), $this->output );
			$this->output = str_replace( "<!--IPB.SIZE-->", $size_html, $this->output );
			$this->ipsclass->lang['av_text_upload'] = sprintf( $this->ipsclass->lang['av_text_upload'], $this->ipsclass->vars['avup_size_max'] );
		}
		else
		{
			$this->ipsclass->lang['av_text_upload'] = "";
		}
		
		//-----------------------------------------
 		// If yes, show little thingy at top
 		//-----------------------------------------
 		
 		$this->ipsclass->lang['av_allowed_files'] = sprintf($this->ipsclass->lang['av_allowed_files'], implode (' .', explode( "|", $this->ipsclass->vars['avatar_ext'] ) ) );
 		
 		if ( $this->ipsclass->vars['allow_flash'] != 1 )
		{
			$this->ipsclass->lang['av_allowed_files'] = str_replace( ".swf", "", $this->ipsclass->lang['av_allowed_files'] );
		}
		
		$this->output = str_replace( "<!--IBF.LIMITS_AVATAR-->", $this->ipsclass->compiled_templates['skin_ucp']->avatar_limits(), $this->output );
		
 			
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SIGNATURE
 	/*-------------------------------------------------------------------------*/
 	
 	function signature()
 	{
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
			 	
	 	$this->init_parser();
	 	
 		//-----------------------------------------
 		// Set max length
 		//-----------------------------------------
 		
 		$this->ipsclass->lang['the_max_length'] = $this->ipsclass->vars['max_sig_length'] ? $this->ipsclass->vars['max_sig_length'] : 0;
		
		//-----------------------------------------
		// Unconvert for editing
		//-----------------------------------------
		
		if ( $this->han_editor->method == 'rte' )
		{
			$t_sig = $this->parser->convert_ipb_html_to_html( $this->ipsclass->member['signature'] );
		}
		else
		{
			$this->parser->parse_html        = intval($this->ipsclass->vars['sig_allow_html']);
			$this->parser->parse_nl2br       = 1;
			$this->parser->parse_smilies     = 0;
			$this->parser->parse_bbcode      = $this->ipsclass->vars['sig_allow_ibc'];
			$this->parser->parsing_signature = 1;
			
			$t_sig = $this->parser->pre_edit_parse( $this->ipsclass->member['signature'] );
		}
		
		$this->ipsclass->lang['override']    = 1;
		
		//-----------------------------------------
 		// Remove side panel
 		//-----------------------------------------
 		
 		$this->han_editor->remove_side_panel = 1;
 		$this->han_editor->remove_emoticons  = 1;

		//-----------------------------------------
		// Show
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->signature( $this->ipsclass->member['signature'], $this->han_editor->show_editor( $t_sig, 'Post' ), $this->ipsclass->return_md5_check());
		 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// PROFILE
 	/*-------------------------------------------------------------------------*/
 	
 	function personal()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
	 	$this->init_parser();
	 	$required_output = "";
		$optional_output = "";
		
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
		
		//-----------------------------------------
		// Format the birthday drop boxes..
		//-----------------------------------------
		
		$date = getdate();
		
		$day  = "<option value='0'>--</option>";
		$mon  = "<option value='0'>--</option>";
		$year = "<option value='0'>--</option>";
		
		for ( $i = 1 ; $i < 32 ; $i++ )
		{
			$day .= "<option value='$i'";
			
			$day .= $i == $this->ipsclass->member['bday_day'] ? "selected='selected'>$i</option>" : ">$i</option>";
		}
		
		for ( $i = 1 ; $i < 13 ; $i++ )
		{
			$mon .= "<option value='$i'";
			
			$mon .= $i == $this->ipsclass->member['bday_month'] ? "selected='selected'>{$this->ipsclass->lang['month'.$i]}</option>" : ">{$this->ipsclass->lang['month'.$i]}</option>";
		}
		
		$i = $date['year'] - 1;
		$j = $date['year'] - 100;
		
		for ( $i ; $j < $i ; $i-- )
		{
			$year .= "<option value='$i'";
			
			$year .= $i == $this->ipsclass->member['bday_year'] ? "selected='selected'>$i</option>" : ">$i</option>";
		}
		
		//-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------
		
    	require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];
    	$fields->mem_data_id = $this->ipsclass->member['id'];
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$fields->init_data();
    	$fields->parse_to_edit();
    	
    	foreach( $fields->out_fields as $id => $data )
    	{
    		if ( $fields->cache_data[ $id ]['pf_not_null'] == 1 )
			{
				$ftype = 'required_output';
			}
			else
			{
				$ftype = 'optional_output';
			}
    		
    		if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
			{
				$form_element = $this->ipsclass->compiled_templates['skin_ucp']->field_dropdown( 'field_'.$id, $data );
			}
			else if ( $fields->cache_data[ $id ]['pf_type'] == 'area' )
			{
				$form_element = $this->ipsclass->compiled_templates['skin_ucp']->field_textarea( 'field_'.$id, $data );
			}
			else
			{
				$form_element = $this->ipsclass->compiled_templates['skin_ucp']->field_textinput( 'field_'.$id, $data );
			}
			
			${$ftype} .= $this->ipsclass->compiled_templates['skin_ucp']->field_entry( $fields->field_names[ $id ], $fields->field_desc[ $id ], $form_element );
    	}
		
		//-----------------------------------------
		// Format the interest / location boxes
		//-----------------------------------------

		$this->ipsclass->member['location']  	= $this->parser->pre_edit_parse( $this->ipsclass->member['location']  );
 		$this->ipsclass->member['interests'] 	= $this->parser->pre_edit_parse( $this->ipsclass->member['interests'] );
 		$this->ipsclass->member['key']       	= $this->md5_check;
 		
 		$this->ipsclass->member['icq_number'] 	= $this->ipsclass->member['icq_number'] > 0 ? $this->ipsclass->member['icq_number'] : '';
 		
		//-----------------------------------------
		// Suck up the HTML and swop some tags if need be
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->personal_panel( $this->ipsclass->member, $required_output, $optional_output, $day, $mon, $year );
		
		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
	}
	
 	
 	/*-------------------------------------------------------------------------*/
 	// SPLASH (no, not the movie starring Tom Hanks)
 	/*-------------------------------------------------------------------------*/
 	
 	function splash()
 	{
		//-----------------------------------------
		// Format the basic data
		//-----------------------------------------
		
		$info['member_email']    = $this->ipsclass->member['email'];
		$info['date_registered'] = $this->ipsclass->get_date( $this->ipsclass->member['joined'], 'LONG' );
		$info['member_posts']    = $this->ipsclass->member['posts'];
		$info['topic_html']		 = "";
		$info['attach_html']	 = "";
		
		$info['daily_average']   = $this->ipsclass->lang['no_posts'];
		
		if ($this->ipsclass->member['posts'] > 0 )
		{
			$diff = time() - $this->ipsclass->member['joined'];
			$days = ($diff / 3600) / 24;
			$days = $days < 1 ? 1 : $days;
			$info['daily_average']  = sprintf('%.2f', ($this->ipsclass->member['posts'] / $days) );
		}
		
		//-----------------------------------------
		// Grab the last 5 read topics
		//-----------------------------------------
		
		$topic_array = array();
		$final_array = array();
		
		$topics = $this->ipsclass->my_getcookie( 'topicsread' );
		$topics = unserialize(stripslashes( $topics ) );
		
		$tmp = $this->ipsclass->vars['db_topic_read_cutoff'];
		$this->ipsclass->vars['db_topic_read_cutoff'] = 0;
		
		if ( is_array( $topics ) and count( $topics ) )
		{
			arsort($topics);
			
			$topic_array = array_slice( array_keys( $topics ), 0, 5 );
			$topic_array = $this->ipsclass->clean_int_array( $topic_array );
			
			if ( count( $topic_array ) )
			{
				$this->ipsclass->member['is_mod'] = isset($this->ipsclass->member['is_mod']) ? $this->ipsclass->member['is_mod'] : 0;
				
				//-----------------------------------------
				// Grab libraries
				//-----------------------------------------
				
				require_once( ROOT_PATH."sources/action_public/forums.php" );
				$this->forums           =  new forums();
				$this->forums->ipsclass =& $this->ipsclass;
				
				$this->forums->init( 1 );
				
				$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'topics',
															  'where'  => 'tid IN ('.implode(",",$topic_array).')',
															  'limit'  => array(0,5) ) );
				
				$this->ipsclass->DB->simple_exec();
											  
				while ( $row = $this->ipsclass->DB->fetch_row() )
				{
					if ( isset($this->ipsclass->forums->forum_by_id[ $row['forum_id'] ]) AND $this->ipsclass->forums->forum_by_id[ $row['forum_id'] ] )
					{
						$topic = $this->forums->parse_data( $row );
						$final_array[ $row['tid'] ] = $this->ipsclass->compiled_templates['skin_ucp']->render_forum_row( $topic );
					}
				}
				
				foreach( $topic_array as $tid )
				{
					$info['topic_html'] .= isset($final_array[ $tid ]) ? $final_array[ $tid ] : '';
				}
			}
		}
 		
 		$this->ipsclass->vars['db_topic_read_cutoff'] = $tmp;
 		
 		//-----------------------------------------
		// Grab the last 5 attachments
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'attachments',
													  'where'  => 'attach_member_id='.$this->ipsclass->member['id'],
													  'order'  => 'attach_date desc',
													  'limit'  => array( 0, 5 ) ) );
									  
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->load_language('lang_topic');
			
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				//-----------------------------------------
				// Full attachment thingy
				//-----------------------------------------
				
				$info['attach_html'] .= $this->ipsclass->compiled_templates['skin_ucp']->Show_attachments( array (
																													'attach_hits'  => $row['attach_hits'],
																													'mime_image'   => $this->ipsclass->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'],
																													'attach_file'  => $row['attach_file'],
																													'attach_id'    => $row['attach_id'],
																													'type'         => $row['attach_rel_module'],
																													'file_size'    => $this->ipsclass->size_format( $row['attach_filesize'] ),
																										   )  	  );
			}
		}
		
		//-----------------------------------------
		// Write the data..
		//-----------------------------------------
		
		$s_array = array( 's' => 5 ,
						  'm' => 7 ,
						  'l' => 15
						);
		
		$info['NOTES'] = $this->notes ? $this->notes : $this->ipsclass->lang['note_pad_empty'];
		
		$info['SIZE']  = $s_array[$this->size];
		
		$info['SIZE_CHOICE'] = "";
		
		//-----------------------------------------
		// If someone has cheated, fix it now.
		//-----------------------------------------
		
		if ( empty($info['SIZE']) )
		{
			$info['SIZE'] = '5';
		}
		
		//-----------------------------------------
		// Make the choice HTML.
		//-----------------------------------------
		
		foreach ($s_array as $k => $v)
		{
			if ($v == $info['SIZE'])
			{
				$info['SIZE_CHOICE'] .= "<option value='$k' selected='selected'>{$this->ipsclass->lang['ta_'.$k]}</option>";
			}
			else
			{
				$info['SIZE_CHOICE'] .= "<option value='$k'>{$this->ipsclass->lang['ta_'.$k]}</option>";
			}
		}
 		
 		$info['NOTES'] = $this->ipsclass->my_br2nl( $info['NOTES'] );
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_ucp']->splash($info);
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// UPDATE_NOTEPAD:
 	//
 	// Displays the intro screen
 	/*-------------------------------------------------------------------------*/
 	
 	function update_notepad()
 	{
		// Do we have an entry for this member?
 		
 		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'id', 'from' => 'member_extra', 'where' => "id=".$this->ipsclass->member['id'] ) );
		$this->ipsclass->DB->simple_exec();
 		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			$this->ipsclass->DB->do_update( 'member_extra', array( 'notes' => $this->ipsclass->input['notes'], 'ta_size' => $this->ipsclass->input['ta_size'] ), 'id='.$this->ipsclass->member['id'] );
 		}
 		else
 		{
 			$this->ipsclass->DB->do_insert( 'member_extra',  array( 'notes' => $this->ipsclass->input['notes'], 'ta_size' => $this->ipsclass->input['ta_size'], 'id' => $this->ipsclass->member['id'] ) );
 		}
 		
 		$this->ipsclass->boink_it($this->ipsclass->base_url."act=UserCP&CODE=00");
 	}
 	
 	
 	function show_image()
	{
		if ( $this->ipsclass->input['rc'] == "" )
		{
			return false;
		}
		
		// Get the info from the db
		
		$row = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'reg_antispam', 'where' => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['rc']))."'" ) );
		
		if ( ! $row['regid'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Using GD?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['bot_antispam'] == 'gd' )
		{
			$this->ipsclass->show_gd_img($row['regcode']);
		}
		else
		{
		
			//-----------------------------------------
			// Using normal then, check for "p"
			//-----------------------------------------
			
			if ( $this->ipsclass->input['p'] == "" )
			{
				return false;
			}
			
			$p = intval($this->ipsclass->input['p']) - 1; //substr starts from 0, not 1 :p
			
			$this_number = substr( $row['regcode'], $p, 1 );
			
			$this->ipsclass->show_gif_img($this_number);
		}
		
	}
        
}

?>