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
|   > $Date: 2007-09-17 18:05:43 -0400 (Mon, 17 Sep 2007) $
|   > $Revision: 1106 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Registration functions
|   > Module written by Matt Mecham
|   > Date started: 16th February 2002
|
|	> Module Version Number: 1.0.0
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class register {

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
    var $email      = "";
    var $modules    = "";
    
    function auto_run()
    {
		//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_register');
    	$this->ipsclass->load_template('skin_register');
    	
    	$this->base_url        = $this->ipsclass->base_url;
    	$this->base_url_nosess = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}";
    	
		//-----------------------------------------
		// Fix up languages
		//-----------------------------------------
		
		foreach( array( 'dname_text', 'reg_error_dname_len', 'reg_error_username_none', 'reg_error_no_name', 'user_name_text' ) as $k )
		{
			$this->ipsclass->lang[ $k ] = sprintf( $this->ipsclass->lang[ $k ], $this->ipsclass->vars['max_user_name_length'] );
		}
		
    	//-----------------------------------------
    	// Get the emailer module
		//-----------------------------------------
		
		require ROOT_PATH."sources/classes/class_email.php";
		
		$this->email = new emailer();
		$this->email->ipsclass =& $this->ipsclass;
		$this->email->email_init();
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			$this->modules->ipsclass =& $this->ipsclass;
		}
		
		//-----------------------------------------
		// Board offline?
		//-----------------------------------------
		
		if ($this->ipsclass->vars['board_offline'] == 1)
		{
			if ($this->ipsclass->member['g_access_offline'] != 1)
			{
				$this->ipsclass->vars['no_reg'] = 1;
			}
		}
		
		$this->ipsclass->vars['username_characters'] = str_replace( '"', '\"', $this->ipsclass->vars['username_characters'] );
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case '02':
    			$this->create_account();
    			break;
    			
    		case '03':
    			$this->validate_user();
    			break;

    		case '05':
    			$this->show_manual_form();
    			break;
    			
    		case '06':
    			$this->show_manual_form('lostpass');
    			break;
    			
    		case 'lostpassform':
    			$this->show_manual_form('lostpass');
    			break;
    			
    		case '07':
    			$this->show_manual_form('newemail');
    			break;
    			
    		case '10':
    			$this->lost_password_start();
    			break;
    		case '11':
    			$this->lost_password_end();
    			break;
    			
    		case '12':
    			$this->coppa_perms_form();
    			break;
    			
    		case 'coppa_two':
    			$this->coppa_two();
    			break;
    			
    		case 'image':
    			$this->show_image();
    			break;
    			
    		case 'reval':
    			$this->revalidate_one();
    			break;
    			
    		case 'reval2':
    			$this->revalidate_two();
    			break;
			
			case 'complete_login':
				$this->complete_login_form();
				break;
			case 'complete_login_do':
				$this->complete_login_save();
				break;
				
    		default:
    			if ($this->ipsclass->vars['use_coppa'] == 1 and $this->ipsclass->input['coppa_pass'] != 1)
    			{
    				$this->coppa_start();
    			}
    			else
    			{
    				$this->show_reg_form();
    			}
    			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// Save login information
	/*-------------------------------------------------------------------------*/
	
	function complete_login_save()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mid                  = intval( $this->ipsclass->input['mid'] );
		$key                  = intval( $this->ipsclass->input['key'] );
		$in_email             = strtolower( trim($this->ipsclass->input['EmailAddress']) );
		$banfilters           = array();
		$form_errors          = array();
		$members_display_name = trim( $this->ipsclass->input['members_display_name'] );
		$poss_session_id      = "";
		
		//-----------------------------------------
		// Get DB row
		//-----------------------------------------
		
		$reg        = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id={$mid} AND partial_date={$key}" ) );
		$tmp_member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => "id={$mid}" ) );
		
		//-----------------------------------------
		// Got it?
		//-----------------------------------------
		
		if ( ! $reg['partial_id'] OR ! $tmp_member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
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
		// Check
		//-----------------------------------------
		
		/*if ( $this->ipsclass->vars['no_reg'] == 1 )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'reg_off' ) );
    	}*/
    	
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	
    	$fields->init_data();
    	$fields->parse_to_save( 1 );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count( $fields->error_fields['empty'] ) )
		{
			$form_errors['general'][] = $this->ipsclass->lang['err_complete_form'];
		}
		
		if ( count( $fields->error_fields['invalid'] ) )
		{
			$form_errors['general'][] = $this->ipsclass->lang['err_invalid'];
		}
		
		if ( count( $fields->error_fields['toobig'] ) )
		{
			$form_errors['general'][] = $this->ipsclass->lang['err_cf_to_long'];
		}
		
		//-----------------------------------------
		// Remove 'sneaky' spaces
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['strip_space_chr'] )
    	{
			$members_display_name = str_replace( chr(160), ' ', $members_display_name );
			$members_display_name = str_replace( chr(173), ' ', $members_display_name );
			$members_display_name = str_replace( chr(240), ' ', $members_display_name );
		}
		
		//-----------------------------------------
		// Test unicode name too
		//-----------------------------------------
		
		$unicode_dname = preg_replace_callback('/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $members_display_name);
		
		//-----------------------------------------
		// Testing email addresses?
		//-----------------------------------------
		
		if ( ! $reg['partial_email_ok'] )
		{
			//-----------------------------------------
			// Check the email address
			//-----------------------------------------
		
			$in_email = $this->ipsclass->clean_email($in_email);
		
			//-----------------------------------------
			// Test email address
			//-----------------------------------------
		
			$this->ipsclass->input['EmailAddress_two'] = strtolower( trim($this->ipsclass->input['EmailAddress_two']) );
		
			if ($this->ipsclass->input['EmailAddress_two'] != $in_email)
			{
				$form_errors['email'][] = $this->ipsclass->lang['reg_error_email_nm'];
			}
			
			//-----------------------------------------
			// Are they banned [EMAIL]?
			//-----------------------------------------

			if ( is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
			{
				foreach ( $banfilters['email'] as $email )
				{
					$email = str_replace( '\*', '.*' ,  preg_quote($email, "/") );

					if ( preg_match( "/^{$email}$/i", $in_email ) )
					{
						$form_errors['email'][] = $this->ipsclass->lang['reg_error_email_taken'];
						break;
					}
				}
			}
			
			//-----------------------------------------
			// Is this email addy taken?
			//-----------------------------------------

			if ( $this->ipsclass->converge->converge_check_for_member_by_email( $in_email ) == TRUE )
			{
				$form_errors['email'][] = $this->ipsclass->lang['reg_error_email_taken'];
			}
		}
	
		//-----------------------------------------
		// More unicode..
		//-----------------------------------------
	
		$len_d = preg_replace("/&#([0-9]+);/", "-", $members_display_name );
		
		//-----------------------------------------
		// Test dname
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			if ( ! $members_display_name OR strlen($len_d) < 3  OR strlen($len_d) > $this->ipsclass->vars['max_user_name_length'] )
			{
				$form_errors['dname'][] = $this->ipsclass->lang['reg_error_no_name'];
			}
		}
		
		//-----------------------------------------
		// CHECK 1: Any errors (missing fields, etc)?
		//-----------------------------------------
		
		if ( count( $form_errors ) )
		{
			$this->complete_login_form( $form_errors );
			return;
		}
		
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			//-----------------------------------------
			// DNAME: Illegal characters
			//-----------------------------------------
			
			if ( preg_match( "#[\[\];,\|]#", $members_display_name ) )
			{
				$form_errors['dname'][] = $this->ipsclass->lang['reg_error_chars'];
			}
		
			//-----------------------------------------
			// DNAME: Is this name already taken?
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'general_get_by_display_name', array( 'members_display_name' => strtolower($members_display_name) ) );
			$this->ipsclass->DB->cache_exec_query();
			
			$name_check = $this->ipsclass->DB->fetch_row();
			
			if ( $name_check['id'] AND $name_check['id'] != $mid )
			{
				$form_errors['dname'][] = $this->ipsclass->lang['reg_error_taken'];
			}
			
			//-----------------------------------------
			// DNAME: Special chars?
			//-----------------------------------------
			
			if ( $unicode_dname != $members_display_name )
			{
				$this->ipsclass->DB->cache_add_query( 'general_get_by_display_name', array( 'members_display_name' => $this->ipsclass->DB->add_slashes(strtolower($unicode_dname) ) ));
				$this->ipsclass->DB->cache_exec_query();
				
				$name_check = $this->ipsclass->DB->fetch_row();
				
				if ( $name_check['id'] AND $name_check['id'] != $mid )
				{
					$form_errors['dname'][] = $this->ipsclass->lang['reg_error_taken'];
				}
			}
			
			//-----------------------------------------
			// DNAME: Banned?
			//-----------------------------------------
			
			if ( is_array( $banfilters['name'] ) and count( $banfilters['name'] ) )
			{
				foreach ( $banfilters['name'] as $n )
				{
					$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
					
					if ( $n AND preg_match( "/^{$n}$/i", $members_display_name ) )
					{
						$form_errors['dname'][] = $this->ipsclass->lang['reg_error_taken'];
						break;
					}
				}
			}
			
			//-----------------------------------------
			// DNAME: GUEST
			//-----------------------------------------
			
			if (strtolower($members_display_name) == 'guest')
			{
				$form_errors['dname'][] = $this->ipsclass->lang['reg_error_taken'];
			}
		}
		
		//-----------------------------------------
		// CHECK 2: Any errors (duplicate names, etc)?
		//-----------------------------------------
		
		if ( count( $form_errors ) )
		{
			$this->complete_login_form( $form_errors );
			return;
		}
		
		//-----------------------------------------
		// Update: Members
		//-----------------------------------------
		
		$members_display_name = $this->ipsclass->vars['auth_allow_dnames'] ? $members_display_name : $tmp_member['name'];
		
		if ( ! $reg['partial_email_ok'] )
		{
			$this->ipsclass->DB->do_update( 'members', array( 'email'                  => $in_email,
															  'members_display_name'   => $members_display_name,
															  'members_l_display_name' => strtolower( $members_display_name ) ), 'id='.$mid );
			
			//-----------------------------------------
			// Update: Converge
			//-----------------------------------------
		
			$this->ipsclass->DB->do_update( 'members_converge', array( 'converge_email' => $in_email ), 'converge_id='.$mid );
		}
		else
		{
			$this->ipsclass->DB->do_update( 'members', array( 'members_display_name'   => $members_display_name,
															  'members_l_display_name' => strtolower( $members_display_name ) ), 'id='.$mid );
		}
		
		//-----------------------------------------
		// Delete: Partials row
		//-----------------------------------------
		
		$this->ipsclass->DB->do_delete( 'members_partial', 'partial_member_id='.$mid );
		
		//-----------------------------------------
		//  Update: Profile fields
		//-----------------------------------------

		$this->ipsclass->DB->force_data_type = array();
		
		foreach( $fields->out_fields as $_field => $_data )
		{
			$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
		}

		if ( is_array($fields->out_fields) and count($fields->out_fields) )
		{
			$this->ipsclass->DB->do_update( 'pfields_content', $fields->out_fields, 'member_id='.$mid );
		}	
		
		//-----------------------------------------
		// Send out admin email
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['new_reg_notify'] )
		{
			$date = $this->ipsclass->get_date( time(), 'LONG', 1 );
			
			$this->email->get_template("admin_newuser");
		
			$this->email->build_message( array( 'DATE'         => $date,
												'MEMBER_NAME'  => $members_display_name ) );
										
			$this->email->subject = $this->ipsclass->lang['new_registration_email'] . $this->ipsclass->vars['board_name'];
			$this->email->to      = $this->ipsclass->vars['email_in'];
			$this->email->send_mail();
		}
		
		//-----------------------------------------
		// Set cookies
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "id=$mid"
														   )      );
														   
		$this->ipsclass->my_setcookie("member_id"   , $member['id']              , 1);
		$this->ipsclass->my_setcookie("pass_hash"   , $member['member_login_key'], 1);
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
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
		
		//-----------------------------------------
		// Got a valid session ID...
		//-----------------------------------------
		
		if ($poss_session_id)
		{
			$session_id = $poss_session_id;
			
			//-----------------------------------------
			// Delete any old sessions with this users IP
			// addy that doesn't match our session ID.
			//-----------------------------------------
			
			$this->ipsclass->DB->do_delete( 'sessions', "ip_address='".$this->ipsclass->ip_address."' AND id != '$session_id'" );
			
			$this->ipsclass->DB->do_shutdown_update( 'sessions', array ( 'member_name'  => $member['members_display_name'],
																		 'member_id'    => $member['id'],
																		 'running_time' => time(),
																		 'member_group' => $member['mgroup'],
																		 'login_type'   => $this->ipsclass->input['Privacy'] ? 1 : 0 ),  "id='".$session_id."'" );
		}
		else
		{
			$session_id = md5( uniqid(microtime()) );
			
			if( $this->ipsclass->vars['match_ipaddress'] )
			{			
				//-----------------------------------------
				// Delete any old sessions with this users IP addy.
				//-----------------------------------------
				
				$this->ipsclass->DB->do_delete( 'sessions', "ip_address='".$this->ipsclass->ip_address."'" );
			}
			
			$this->ipsclass->DB->do_shutdown_insert( 'sessions', array ( 'id'           => $session_id,
																		 'member_name'  => $member['members_display_name'],
																		 'member_id'    => $member['id'],
																		 'running_time' => time(),
																		 'member_group' => $member['mgroup'],
																		 'ip_address'   => $this->ipsclass->ip_address,
																		 'browser'      => substr($this->ipsclass->clean_value($this->ipsclass->my_getenv('HTTP_USER_AGENT')), 0, 50),
																		 'login_type'   => $this->ipsclass->input['Privacy'] ? 1 : 0 ) );
		}
		
		$this->ipsclass->member     = $member;
		$this->ipsclass->session_id = $session_id;
		
		//-----------------------------------------
		// Update Stats
		//-----------------------------------------
				
		$this->ipsclass->cache['stats']['last_mem_name'] = $this->ipsclass->member['members_display_name'];
		$this->ipsclass->cache['stats']['last_mem_id']   = $this->ipsclass->member['id'];
		$this->ipsclass->cache['stats']['mem_count']    += 1;
		
		$this->ipsclass->update_cache(  array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0 ) );
		
		//-----------------------------------------
		// set cookie
		//-----------------------------------------
		
		$this->ipsclass->my_setcookie("session_id", $this->ipsclass->session_id, -1);
		
		//-----------------------------------------
		// Go to the board index
		//-----------------------------------------
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['clogin_done'], 'act=idx' );
	}
 	
 	/*-------------------------------------------------------------------------*/
	// Show "check revalidate form" er.. form. thing.
	/*-------------------------------------------------------------------------*/
	
	function complete_login_form( $form_errors=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mid          = intval( $this->ipsclass->input['mid'] );
		$key          = intval( $this->ipsclass->input['key'] );
		$final_errors = array();
		
		//-----------------------------------------
		// Get DB row
		//-----------------------------------------
		
		$reg = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																 'from'   => 'members_partial',
																 'where'  => "partial_member_id={$mid} AND partial_date={$key}" ) );
		
		//-----------------------------------------
		// Got it?
		//-----------------------------------------
		
		if ( ! $reg['partial_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
		}
		
		//-----------------------------------------
		// Custom profile fields stuff
		//-----------------------------------------
		
		$required_output = "";
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	
    	$fields->init_data();
    	$fields->parse_to_register();
    	
    	foreach( $fields->out_fields as $id => $data )
    	{
    		if ( $fields->cache_data[ $id ]['pf_not_null'] == 1 )
			{
				$ftype = 'required_output';
			}
			else
			{
				continue;
			}
    		
    		if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
			{
				$form_element = $this->ipsclass->compiled_templates['skin_register']->field_dropdown( 'field_'.$id, $data );
			}
			else if ( $fields->cache_data[ $id ]['pf_type'] == 'area' )
			{
				$data = $this->ipsclass->input['field_'.$id] ? $this->ipsclass->input['field_'.$id] : $data;
				$form_element = $this->ipsclass->compiled_templates['skin_register']->field_textarea( 'field_'.$id, $data );
			}
			else
			{
				$data = $this->ipsclass->input['field_'.$id] ? $this->ipsclass->input['field_'.$id] : $data;
				$form_element = $this->ipsclass->compiled_templates['skin_register']->field_textinput( 'field_'.$id, $data );
			}
			
			${$ftype} .= $this->ipsclass->compiled_templates['skin_register']->field_entry( $fields->field_names[ $id ], $fields->field_desc[ $id ], $form_element );
    	}
    	
    	//-----------------------------------------
    	// ERROR CHECK
    	//-----------------------------------------
    	
    	if ( is_array( $form_errors['general'] ) AND count( $form_errors['general'] ) )
    	{
    		$this->output .= $this->ipsclass->compiled_templates['skin_register']->errors( implode( "<br />", $form_errors['general'] ) );
    	}
    	
    	//-----------------------------------------
    	// Other errors
    	//-----------------------------------------
    	
    	foreach( array( 'username', 'dname', 'password', 'email' ) as $thing )
    	{
			if ( is_array( $form_errors[ $thing ] ) AND count( $form_errors[ $thing ] ) )
			{
				$final_errors[ $thing ] = implode( "<br />", $form_errors[ $thing ] );
			}
		}
		
		//-----------------------------------------
		// No display name?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['members_display_name'] )
		{
			$this->ipsclass->member['members_display_name'] = $this->ipsclass->member['email'];
		}
		
		//-----------------------------------------
		// Show the form (email and display name)
		//-----------------------------------------
		
		$this->output     .= $this->ipsclass->compiled_templates['skin_register']->reg_complete_login( $mid, $key, $required_output, $final_errors, $reg );
		$this->page_title  = $this->ipsclass->lang['clogin_title'];
		$this->nav         = array( $this->ipsclass->lang['clogin_title'] );
	}
	
 	/*-------------------------------------------------------------------------*/
	// Show "check revalidate form" er.. form. thing.
	/*-------------------------------------------------------------------------*/
	
	function revalidate_one($errors="")
	{
		if ($errors != "")
    	{
    		$this->output .= $this->ipsclass->compiled_templates['skin_register']->errors( $this->ipsclass->lang[$errors] );
    	}
    	
    	$name = $this->ipsclass->member['id'] == "" ? '' :
    			( $this->ipsclass->vars['ipbli_usertype'] == 'username' ? $this->ipsclass->member['name'] : $this->ipsclass->member['email'] );
		
		$this->output     .= $this->ipsclass->compiled_templates['skin_register']->show_revalidate_form($name);
		$this->page_title  = $this->ipsclass->lang['rv_title'];
		$this->nav         = array( $this->ipsclass->lang['rv_title'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Complete revalidation
	/*-------------------------------------------------------------------------*/
	
	function revalidate_two()
	{
		//-----------------------------------------
		// Check in the DB for entered member name
		//-----------------------------------------
		
		if ( $_POST['username'] == "" )
		{
			$this->revalidate_one('err_no_username');
			return;
		}
		
		$username = $this->ipsclass->input['username'];
		
		if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
		{
			$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => $username ) );
			$this->ipsclass->DB->cache_exec_query();
			
			$member = $this->ipsclass->DB->fetch_row();
								 
			//-----------------------------------------
			// Got a username?
			//-----------------------------------------
			
			if ( ! $member['id'] )
			{
				$this->revalidate_one('err_no_username');
				return;
			}
			
			$this->ipsclass->converge->converge_load_member( $member['email'] );
			
			if ( ! $this->ipsclass->converge->member['converge_id'] )
			{
				$this->revalidate_one('err_no_username');
				return;
			}
		}
		
		//-----------------------------------------
		// EMAIL LOG IN
		//-----------------------------------------
		
		else
		{
			$email = $username;
			
			$this->ipsclass->converge->converge_load_member( $email );
			
			if ( $this->ipsclass->converge->member['converge_id'] )
			{
				$this->ipsclass->DB->build_query( array(
														  'select'   => 'm.*',
														  'from'     => array( 'members' => 'm' ),
														  'where'    => "email='".strtolower($username)."'",
														  'add_join' => array( 0 => array( 'select' => 'g.*',
																						   'from'   => array( 'groups' => 'g' ),
																						   'where'  => 'g.g_id=m.mgroup',
																						   'type'   => 'inner'
																						 )
																			)
												 )     );

				$this->ipsclass->DB->exec_query();

				$member      = $this->ipsclass->DB->fetch_row();
				
				if ( ! $member['id'] )
				{
					$this->revalidate_one('err_no_username');
					return;
				}
			}
			else
			{
				$this->revalidate_one('err_no_username');
				return;
			}
		}		
		
		//-----------------------------------------
		// Check in the DB for any validations
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'validating', 'where' => "member_id=".intval($member['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $val = $this->ipsclass->DB->fetch_row() )
		{
			$this->revalidate_one('err_no_validations');
			return;
		}
		
		//-----------------------------------------
		// Which type is it then?
		//-----------------------------------------
		
		if ( $val['lost_pass'] == 1 )
		{
			$this->email->get_template("lost_pass");
				
			$this->email->build_message( array(
												'NAME'         => $member['members_display_name'],
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform&uid=".$member['id']."&aid=".$val['vid'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform",
												'EMAIL'        => $member['email'],
												'ID'           => $member['id'],
												'CODE'         => $val['vid'],
												'IP_ADDRESS'   => $this->ipsclass->input['IP_ADDRESS'],
											  )
										);
										
			$this->email->subject = $this->ipsclass->lang['lp_subject'].' '.$this->ipsclass->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
		}
		else if ( $val['new_reg'] == 1 )
		{
			$this->email->get_template("reg_validate");
					
			$this->email->build_message( array(
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&uid=".$member['id']."&aid=".$val['vid'],
												'NAME'         => $member['members_display_name'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=05",
												'EMAIL'        => $member['email'],
												'ID'           => $member['id'],
												'CODE'         => $val['vid'],
											  )
										);
										
			$this->email->subject = $this->ipsclass->lang['email_reg_subj']." ".$this->ipsclass->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
		}
		else if ( $val['email_chg'] == 1 )
		{
			$this->email->get_template("newemail");
				
			$this->email->build_message( array(
												'NAME'         => $member['members_display_name'],
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&type=newemail&uid=".$member['id']."&aid=".$val['vid'],
												'ID'           => $member['id'],
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=07",
												'CODE'         => $val['vid'],
											  )
										);
										
			$this->email->subject = $this->ipsclass->lang['ne_subject'].' '.$this->ipsclass->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
		}
		else
		{
			$this->revalidate_one('err_no_validations');
			return;
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_register']->show_revalidated();
		
		$this->page_title = $this->ipsclass->lang['rv_title'];
		$this->nav        = array( $this->ipsclass->lang['rv_title'] );
	}
 	
 	
 	/*-------------------------------------------------------------------------*/
	// Coppa Start
	/*-------------------------------------------------------------------------*/
	
	function coppa_perms_form()
	{
		echo($this->ipsclass->compiled_templates['skin_register']->coppa_form());
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Coppa form
	/*-------------------------------------------------------------------------*/
	
	function coppa_start()
	{
		$coppa_date = date( 'j-F y', mktime(0,0,0,date("m"),date("d"),date("Y")-13) );
		
		$this->ipsclass->lang['coppa_form_text'] = str_replace( "<#FORM_LINK#>", "<a href='{$this->ipsclass->base_url}act=Reg&amp;CODE=12'>{$this->ipsclass->lang['coppa_link_form']}</a>", $this->ipsclass->lang['coppa_form_text']);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_register']->coppa_start($coppa_date);
		
		$this->page_title = $this->ipsclass->lang['coppa_title'];
		
    	$this->nav        = array( $this->ipsclass->lang['coppa_title'] );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Coppa print
 	/*-------------------------------------------------------------------------*/
 	
 	function coppa_two()
	{
		if( !$this->ipsclass->input['m'] OR !$this->ipsclass->input['d'] OR !$this->ipsclass->input['y'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'coppa_form_fill' ) );
		}
		
		$birthday	= mktime( 0, 0, 0, intval($this->ipsclass->input['m']), intval($this->ipsclass->input['d']), intval($this->ipsclass->input['y']) );
		$coppa		= mktime( 0, 0, 0, date("m"), date("d"), date("Y")-13 );
		
		if( $birthday <= $coppa )
		{
			$this->show_reg_form();
			return;
		}
			
			
		$this->ipsclass->lang['coppa_form_text'] = str_replace( "<#FORM_LINK#>", "<a href='{$this->ipsclass->base_url}act=Reg&amp;CODE=12'>{$this->ipsclass->lang['coppa_link_form']}</a>", $this->ipsclass->lang['coppa_form_text']);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_register']->coppa_two();
		
		$this->page_title = $this->ipsclass->lang['coppa_title'];
		
    	$this->nav        = array( $this->ipsclass->lang['coppa_title'] );
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// lost_password_start
	/*-------------------------------------------------------------------------*/
	
	function lost_password_start($errors="")
	{
 		//-----------------------------------------
    	// Do we have another URL that one needs
    	// to visit to reset their password?
    	//-----------------------------------------
    	
    	$this->login_method = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    	
    	if ( $this->login_method['login_maintain_url'] )
    	{
    		$this->ipsclass->boink_it( $this->login_method['login_maintain_url'] );
    		exit();
    	}
    			
		if ($this->ipsclass->vars['bot_antispam'])
		{
			//-----------------------------------------
			// Sort out the security code
			//-----------------------------------------
			
			$r_date = time() - (60*60*6);
			
			//-----------------------------------------
			// Remove old reg requests from the DB
			//-----------------------------------------
			
			$this->ipsclass->DB->do_delete( 'reg_antispam', "ctime < '$r_date'" );
			
			//-----------------------------------------
			// Set a new ID for this reg request...
			//-----------------------------------------
			
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
			
			//-----------------------------------------
			// Insert into the DB
			//-----------------------------------------
			
			$this->ipsclass->DB->do_insert( 'reg_antispam', array (
										    'regid'      => $regid,
										    'regcode'    => $reg_code,
										    'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
										    'ctime'      => time(),
							     )       );
		}
		
		$this->page_title = $this->ipsclass->lang['lost_pass_form'];
		
    	$this->nav        = array( $this->ipsclass->lang['lost_pass_form'] );
    	
    	if ($errors != "")
    	{
    		$this->output .= $this->ipsclass->compiled_templates['skin_register']->errors( $this->ipsclass->lang[$errors]);
    	}

    	$this->output    .= $this->ipsclass->compiled_templates['skin_register']->lost_pass_form($regid);
    	
    	if ($this->ipsclass->vars['bot_antispam'] == 'gd')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->ipsclass->compiled_templates['skin_register']->bot_antispam_gd( $regid ), $this->output );
		}
		else if ($this->ipsclass->vars['bot_antispam'] == 'gif')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->ipsclass->compiled_templates['skin_register']->bot_antispam( $regid ), $this->output );
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // LOST PASSWORD: SEND
    /*-------------------------------------------------------------------------*/
    
    function lost_password_end()
    {
		if ($this->ipsclass->vars['bot_antispam'])
		{
			//-----------------------------------------
			// Security code stuff
			//-----------------------------------------
			
			if ($this->ipsclass->input['regid'] == "")
			{
				$this->lost_password_start('err_reg_code');
				return;
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'reg_antispam',
														  'where'  => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'"
												 )      );
								 
			$this->ipsclass->DB->simple_exec();
			
			if ( ! $row = $this->ipsclass->DB->fetch_row() )
			{
				$this->show_reg_form('err_reg_code');
				return;
			}
			
			if ( trim( $this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['reg_code']) ) != $row['regcode'] )
			{
				$this->lost_password_start('err_reg_code');
				return;
			}
		}
		
    	//-----------------------------------------
    	// Back to the usual programming! :o
    	//-----------------------------------------
    	
    	if ($_POST['member_name'] == "" AND $_POST['email_addy'] == "")
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_username' ) );
    	}
    	
    	//-----------------------------------------
		// Check for input and it's in a valid format.
		//-----------------------------------------
		
		$member_name = trim(strtolower($this->ipsclass->input['member_name']));
		$email_addy  = trim(strtolower($this->ipsclass->input['email_addy']));
		
		if ($member_name == "" AND $email_addy == "" )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_username' ) );
		}
    	
    	//-----------------------------------------
		// Attempt to get the user details from the DB
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
		{
			if( $member_name )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name, name, id, email, mgroup', 'from' => 'members', 'where' => "members_l_username='{$member_name}'" ) );
				$this->ipsclass->DB->simple_exec();
			}
			else if( $email_addy )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name, name, id, email, mgroup', 'from' => 'members', 'where' => "email='{$email_addy}'" ) );
				$this->ipsclass->DB->simple_exec();
			}				
		}
		else
		{
			// We don't use the 'email_addy' input if usertype is email
			$email_addy = "";

			$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name, name, id, email, mgroup', 'from' => 'members', 'where' => "email='{$member_name}'" ) );
			$this->ipsclass->DB->simple_exec();
		}
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		else
		{
			$member = $this->ipsclass->DB->fetch_row();

			//-----------------------------------------
			// Is there a validation key? If so, we'd better not touch it
			//-----------------------------------------
			
			if ($member['id'] == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
			}
			
			$validate_key = md5( $this->ipsclass->make_password() . uniqid( mt_rand(), TRUE ) );
			
			//-----------------------------------------
			// Get rid of old entries for this member
			//-----------------------------------------
			
			$this->ipsclass->DB->do_delete( 'validating', "member_id={$member['id']} AND lost_pass=1" );
			
			//-----------------------------------------
			// Update the DB for this member.
			//-----------------------------------------
			
			$db_str = array(
							'vid'         => $validate_key,
							'member_id'   => $member['id'],
							#'real_group'  => $member['mgroup'],
							'temp_group'  => $member['mgroup'],
							'entry_date'  => time(),
							'coppa_user'  => 0,
							'lost_pass'   => 1,
							'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
						   );
					
			// Are they already in the validating group?
			
			if( $member['mgroup'] != $this->ipsclass->vars['auth_group'] )
			{
				$db_str['real_group'] = $member['mgroup'];
			}
						   
			$this->ipsclass->DB->do_insert( 'validating', $db_str );
			
			//-----------------------------------------
			// Send out the email.
			//-----------------------------------------
			
    		$this->email->get_template("lost_pass");
				
			$this->email->build_message( array(
												'NAME'         => $member['members_display_name'],
												'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform&uid=".$member['id']."&aid=".$validate_key,
												'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=lostpassform",
												'EMAIL'        => $member['email'],
												'ID'           => $member['id'],
												'CODE'         => $validate_key,
												'IP_ADDRESS'   => $this->ipsclass->input['IP_ADDRESS'],
											  )
										);
										
			$this->email->subject = $this->ipsclass->lang['lp_subject'].' '.$this->ipsclass->vars['board_name'];
			$this->email->to      = $member['email'];
			
			$this->email->send_mail();
			
			$this->output = $this->ipsclass->compiled_templates['skin_register']->show_lostpasswait( $member );
		}
    	
    	$this->page_title = $this->ipsclass->lang['lost_pass_form'];
    }
 	
 	/*-------------------------------------------------------------------------*/
	// show_reg_form
	/*-------------------------------------------------------------------------*/   
    
    function show_reg_form($form_errors = array())
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$final_errors = array();
    	
		if ( $this->ipsclass->vars['no_reg'] == 1 )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'reg_off' ) );
    	}
    	
    	$coppa = $this->ipsclass->my_getcookie( 'coppa' );
    	//$coppa = (isset($this->ipsclass->input['coppa_user']) AND $this->ipsclass->input['coppa_user'] == 1) ? 1 : 0;
    	
    	if( $coppa == 'yes' )
    	{
	    	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'awaiting_coppa' ) );
    	}
    	
    	$this->ipsclass->vars['username_errormsg'] = str_replace( '{chars}', $this->ipsclass->vars['username_characters'], $this->ipsclass->vars['username_errormsg'] );
    	
    	//-----------------------------------------
    	// Read T&Cs yet?
    	//-----------------------------------------
    	
    	if ( !isset($this->ipsclass->input['termsread']) OR !$this->ipsclass->input['termsread'] )
    	{	
			if ( $this->ipsclass->member['id'] )
			{
				//-----------------------------------------
				// Log member out
				//-----------------------------------------
		
				require_once( ROOT_PATH.'sources/action_public/login.php' );
				$login           =  new login();
				$login->ipsclass =& $this->ipsclass;
				$login->do_log_out( 0 );
			}
			
			//-----------------------------------------
			// Continue
			//-----------------------------------------

    		$cache = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => "conf_key='reg_rules'" ) );
    		
    		$text  = $cache['conf_value'] ? $cache['conf_value'] : $cache['conf_default'];
    		
    		$this->page_title = $this->ipsclass->lang['registration_form'];
    		$this->nav        = array( $this->ipsclass->lang['registration_form'] );
    	
    		$this->output .= $this->ipsclass->compiled_templates['skin_register']->show_terms( $this->ipsclass->my_nl2br($text), $coppa );
    		return;
    	}
    	else
    	{
			//-----------------------------------------
			// Did we agree to the t&c?
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['agree_to_terms'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'reg_no_agree', 'EXTRA' => $this->ipsclass->base_url ) );
			}
    	}
    	
    	//-----------------------------------------
    	// Do we have another URL that one needs
    	// to visit to register?
    	//-----------------------------------------
    	
    	$this->login_method = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    	
    	if ( $this->login_method['login_register_url'] )
    	{
    		$this->ipsclass->boink_it( $this->login_method['login_register_url'] );
    		exit();
    	}
    	
    	//-----------------------------------------
    	// Continue...
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->vars['reg_auth_type'] )
    	{
	    	if ( $this->ipsclass->vars['reg_auth_type'] == 'admin_user' OR $this->ipsclass->vars['reg_auth_type'] == 'user' )
	    	{
    			$this->ipsclass->lang['std_text'] .= "<br />" . $this->ipsclass->lang['email_validate_text'];
			}
    		
    		//-----------------------------------------
    		// User then admin?
    		//-----------------------------------------
    		
    		if ( $this->ipsclass->vars['reg_auth_type'] == 'admin_user' )
    		{
    			$this->ipsclass->lang['std_text'] .= "<br />" . $this->ipsclass->lang['user_admin_validation'];
    		}
    		
    		if ( $this->ipsclass->vars['reg_auth_type'] == 'admin' )
    		{
	    		$this->ipsclass->lang['std_text'] .= "<br />" . $this->ipsclass->lang['just_admin_validation'];
    		}
    	}
    	
    	//-----------------------------------------
		// Clean out anti-spam stuffy
		//-----------------------------------------
		
		if ($this->ipsclass->vars['bot_antispam'])
		{
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
    	
    	//-----------------------------------------
		// Custom profile fields stuff
		//-----------------------------------------
		
		$required_output = "";
		$optional_output = "";
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	
    	$fields->init_data();
    	$fields->parse_to_register();
    	
    	foreach( $fields->out_fields as $id => $data )
    	{
	    	$error = "";
	    	
    		if ( $fields->cache_data[ $id ]['pf_not_null'] == 1 )
			{
				$ftype = 'required_output';
			}
			else
			{
				$ftype = 'optional_output';
			}
			
			if( isset($form_errors['cfield_'.$id]) AND count( $form_errors['cfield_'.$id] ) )
			{
				$error = implode( "<br />", $form_errors['cfield_'.$id] );
			}
    		
    		if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
			{
				$form_element = $this->ipsclass->compiled_templates['skin_register']->field_dropdown( 'field_'.$id, $data, $error );
			}
			else if ( $fields->cache_data[ $id ]['pf_type'] == 'area' )
			{
				$data = $this->ipsclass->input['field_'.$id] ? $this->ipsclass->input['field_'.$id] : $data;
				$form_element = $this->ipsclass->compiled_templates['skin_register']->field_textarea( 'field_'.$id, $data, $error );
			}
			else
			{
				$data = isset($this->ipsclass->input['field_'.$id]) ? $this->ipsclass->input['field_'.$id] : $data;
				$form_element = $this->ipsclass->compiled_templates['skin_register']->field_textinput( 'field_'.$id, $data, $error );
			}
			
			${$ftype} .= $this->ipsclass->compiled_templates['skin_register']->field_entry( $fields->field_names[ $id ], $fields->field_desc[ $id ], $form_element, $id, $error );
    	}
    	
    	$this->page_title = $this->ipsclass->lang['registration_form'];
    	$this->nav        = array( $this->ipsclass->lang['registration_form'] );
    	
    	//-----------------------------------------
    	// ERROR CHECK
    	//-----------------------------------------
    	
    	if ( isset($form_errors['general']) AND is_array( $form_errors['general'] ) AND count( $form_errors['general'] ) )
    	{
    		$this->output .= $this->ipsclass->compiled_templates['skin_register']->errors( implode( "<br />", $form_errors['general'] ) );
    	}
    	
    	//-----------------------------------------
    	// Other errors
    	//-----------------------------------------
    	
    	$final_errors = array( 'username' => NULL, 'dname' => NULL, 'password' => NULL, 'email' => NULL );
    	
    	foreach( array( 'username', 'dname', 'password', 'email' ) as $thing )
    	{
			if ( isset($form_errors[ $thing ]) AND is_array( $form_errors[ $thing ] ) AND count( $form_errors[ $thing ] ) )
			{
				$final_errors[ $thing ] = implode( "<br />", $form_errors[ $thing ] );
			}
		}
		
		$this->ipsclass->input['UserName'] 				= isset($this->ipsclass->input['UserName']) 			? $this->ipsclass->input['UserName'] 				: '';
		$this->ipsclass->input['PassWord'] 				= isset($this->ipsclass->input['PassWord']) 			? $this->ipsclass->input['PassWord'] 				: '';
		$this->ipsclass->input['EmailAddress'] 			= isset($this->ipsclass->input['EmailAddress']) 		? $this->ipsclass->input['EmailAddress'] 			: '';
		$this->ipsclass->input['EmailAddress_two']		= isset($this->ipsclass->input['EmailAddress_two'])		? $this->ipsclass->input['EmailAddress_two']		: '';
		$this->ipsclass->input['PassWord_Check'] 		= isset($this->ipsclass->input['PassWord_Check']) 		? $this->ipsclass->input['PassWord_Check'] 			: '';
		$this->ipsclass->input['members_display_name'] 	= isset($this->ipsclass->input['members_display_name'])	? $this->ipsclass->input['members_display_name']	: '';
		$this->ipsclass->input['time_offset'] 			= isset($this->ipsclass->input['time_offset']) 			? $this->ipsclass->input['time_offset'] 			: '';
		$this->ipsclass->input['allow_member_mail'] 	= isset($this->ipsclass->input['allow_member_mail'])	? $this->ipsclass->input['allow_member_mail']		: '';		
		$this->ipsclass->input['dst'] 					= isset($this->ipsclass->input['dst'])					? $this->ipsclass->input['dst']						: '';		
		
    	$this->output .= $this->ipsclass->compiled_templates['skin_register']->ShowForm( array( 'TEXT' => $this->ipsclass->lang['std_text'], 'coppa_user' => $coppa ), $final_errors );
    	
    	//-----------------------------------------
    	// Replace elements
    	//-----------------------------------------
    	
    	if ($this->ipsclass->vars['bot_antispam'] == 'gd')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->ipsclass->compiled_templates['skin_register']->bot_antispam_gd( $regid ), $this->output );
		}
		else if ($this->ipsclass->vars['bot_antispam'] == 'gif')
		{
			$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->ipsclass->compiled_templates['skin_register']->bot_antispam( $regid ), $this->output );
		}
    	
    	if ($required_output != "")
		{
			$this->output = str_replace( "<!--{REQUIRED.FIELDS}-->", "\n".$required_output, $this->output );
		}
		
		if ($optional_output != "")
		{
			$this->output = str_replace( "<!--{OPTIONAL.FIELDS}-->", $this->ipsclass->compiled_templates['skin_register']->optional_title()."\n".$optional_output, $this->output );
		}
		
		//-----------------------------------------
		// Time zone...
		//-----------------------------------------
		
		$this->ipsclass->load_language('lang_ucp');
		
		$offset = ( $this->ipsclass->input['time_offset'] != "" ) ? $this->ipsclass->input['time_offset'] : $this->ipsclass->vars['time_offset'];
 		
 		$time_select = "<select name='time_offset' class='forminput'>";
 		
 		foreach( $this->ipsclass->lang as $off => $words )
 		{
 			if (preg_match("/^time_([\d\.\-]+)$/", $off, $match))
 			{
				$time_select .= $match[1] == $offset ? "<option value='{$match[1]}' selected='selected'>$words</option>"
												     : "<option value='{$match[1]}'>$words</option>";
 			}
 		}
 		
 		$time_select .= "</select>";
 		
 		$this->output = str_replace( "<!--{TIME_ZONE}-->", "\n".$time_select, $this->output );
		
		//-----------------------------------------
		// Boxes checked?
		//-----------------------------------------
		
		$admin_checked = 'checked="checked"';
		
		if ( $this->ipsclass->input['CODE'] == '02' )
		{
			//-----------------------------------------
			// Form submitted...
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['allow_admin_mail'] )
			{
				$admin_checked = '';
			}
		}
		
		$member_checked = $this->ipsclass->input['allow_member_mail'] ? 'checked="checked"' : '';
		$dst_checked    = $this->ipsclass->input['dst']               ? 'checked="checked"' : '';
		
		$this->output = str_replace( "<!--[admin.checked]-->" , $admin_checked , $this->output );
		$this->output = str_replace( "<!--[member.checked]-->", $member_checked, $this->output );
		$this->output = str_replace( "<!--[dst.checked]-->"   , $dst_checked   , $this->output );
		
		//-----------------------------------------
		// Subscribe on register?
		//-----------------------------------------
		
		$all_currency = array();
		$def_currency = "";
		$subs         = array();
		$subs_output  = "";
		$desc_output  = "";
		
		if ( $this->ipsclass->vars['subsm_show_reg'] )
		{
			$this->ipsclass->load_language('lang_subscriptions');
			
			//-----------------------------------------
			// Get currency buns!
			// Ok, we did that joke in another module and it
			// wasn't funny then
			//-----------------------------------------
			
    		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'subscription_currency' ) );
    		$this->ipsclass->DB->simple_exec();
    		
			while ( $c = $this->ipsclass->DB->fetch_row() )
			{
				$all_currency[ $c['subcurrency_code'] ] = $c;
				
				if ( $c['subcurrency_default'] )
				{
					$def_currency = $c;
				}
			}
			
			//-----------------------------------------
			// Get subscription packages
			//-----------------------------------------
			
			$sub_output = $this->ipsclass->compiled_templates['skin_register']->subsm_start( $def_currency['subcurrency_code'] );
			
			//-----------------------------------------
			// Enforcing?
			//-----------------------------------------
			
			if ( ! $this->ipsclass->vars['subsm_enforce'] )
			{
				$sub_output .= $this->ipsclass->compiled_templates['skin_register']->subsm_row( '0', $this->ipsclass->lang['subsm_none'], '0.00', $this->ipsclass->lang['subsm_na'] );
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'subscriptions', 'order' => 'sub_cost' ) );
    		$this->ipsclass->DB->simple_exec();
    		
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$duration = $row['sub_length'];
			
				if ( $duration > 1 )
				{
					$duration .= ' '.$this->ipsclass->lang[ 'timep_'.$row['sub_unit'] ];
				}
				else
				{
					$duration .= ' '.$this->ipsclass->lang[ 'time_'.$row['sub_unit'] ];
				}
				
				$duration = ($row['sub_unit'] == 'x') ? $this->ipsclass->lang['no_expire'] : $duration;
				
				$sub_output .= $this->ipsclass->compiled_templates['skin_register']->subsm_row( $row['sub_id'],
													   $row['sub_title'],
													   sprintf( "%.2f", $row['sub_cost']  * $def_currency['subcurrency_exchange'] ),
													   $duration
													 );
				$desc_output .= "\n subdesc[{$row['sub_id']}] = '".str_replace( "'", "\\'", str_replace( "\n", '\n',$row['sub_desc'] ) )."';";
			}
			
			$sub_output .= $this->ipsclass->compiled_templates['skin_register']->subsm_end();
			
			//-----------------------------------------
			// Parse 'n show
			//-----------------------------------------
			
			if ( $sub_output )
			{
				$this->output = str_replace( '<!--{SUBS.MANAGER}-->', $sub_output , $this->output );
				$this->output = str_replace( '<!--{SUBS.JSCRIPT}-->', str_replace( "\r", "", $desc_output ), $this->output );
			}
			
		}
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
    		$this->modules->on_register_form();
   		}
   	}
    
   	/*-------------------------------------------------------------------------*/
	// create_account
	/*-------------------------------------------------------------------------*/ 
	
	function create_account()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if( $this->ipsclass->vars['ipbli_usertype'] == 'email' )
		{
			$this->ipsclass->input['UserName'] = $this->ipsclass->input['members_display_name'];
		}
		
		$form_errors          = array();
		$coppa                = ($this->ipsclass->input['coppa_user'] == 1) ? 1 : 0;
		$in_username          = str_replace( '|', '&#124;' , $this->ipsclass->input['UserName'] );
		$in_password          = trim($this->ipsclass->input['PassWord']);
		$in_email             = strtolower( trim($this->ipsclass->input['EmailAddress']) );
		$banfilters           = array();
		$members_display_name = trim( $this->ipsclass->input['members_display_name'] );
		
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
		// Check
		//-----------------------------------------
		
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		if ($this->ipsclass->vars['no_reg'] == 1)
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'reg_off' ) );
    	}
    	
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	
    	$fields->init_data();
    	$fields->parse_to_save( 1 );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count( $fields->error_fields['empty'] ) )
		{
			foreach(  $fields->error_fields['empty'] as $cfield )
			{
				$form_errors['cfield_'.$cfield['pf_id']][$this->ipsclass->lang['err_complete_form']] = $this->ipsclass->lang['err_complete_form'];
			}
		}
		
		if ( count( $fields->error_fields['invalid'] ) )
		{
			foreach( $fields->error_fields['invalid'] as $cfield )
			{
				$form_errors['cfield_'.$cfield['pf_id']][$this->ipsclass->lang['err_invalid']] = $this->ipsclass->lang['err_invalid'];
			}
		}
		
		if ( count( $fields->error_fields['toobig'] ) )
		{
			foreach( $fields->error_fields['toobig'] as $cfield )
			{
				$form_errors['cfield_'.$cfield['pf_id']][$this->ipsclass->lang['err_cf_to_long']] = $this->ipsclass->lang['err_cf_to_long'];
			}
		}
		
		//-----------------------------------------
		// Remove multiple spaces in the username
		//-----------------------------------------
		
		$in_username = preg_replace( "/\s{2,}/", " ", $in_username );
		
		//-----------------------------------------
		// Remove 'sneaky' spaces
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['strip_space_chr'] )
    	{
    		// use hexdec to convert between '0xAD' and chr
			$in_username          = str_replace( chr(160), ' ', $in_username );
			$in_username          = str_replace( chr(173), ' ', $in_username );
			$in_username          = str_replace( chr(240), ' ', $in_username );
			$members_display_name = str_replace( chr(160), ' ', $members_display_name );
			$members_display_name = str_replace( chr(173), ' ', $members_display_name );
			$members_display_name = str_replace( chr(240), ' ', $members_display_name );
		}
		
		//-----------------------------------------
		// Trim up..
		//-----------------------------------------
		
		$in_username = trim($in_username);
		
		//-----------------------------------------
		// Test unicode name too
		//-----------------------------------------
		
		$unicode_name  = preg_replace_callback('/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $in_username);
		$unicode_name  = str_replace( "'" , '&#39;', $unicode_name );
		$unicode_name  = str_replace( "\\", '&#92;', $unicode_name );
		
		$unicode_dname = preg_replace_callback('/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $members_display_name);
		$unicode_dname = str_replace( "'" , '&#39;', $unicode_dname );
		$unicode_dname = str_replace( "\\", '&#92;', $unicode_dname );
		
		//-----------------------------------------
		// Check the email address
		//-----------------------------------------
		
		$in_email = $this->ipsclass->clean_email($in_email);
		
		if ( ! $in_email OR strlen($in_email) < 6 )
		{
			$form_errors['email'][$this->ipsclass->lang['err_invalid_email']] = $this->ipsclass->lang['err_invalid_email'];
		}
		
		//-----------------------------------------
		// Test email address
		//-----------------------------------------
		
		$this->ipsclass->input['EmailAddress_two'] = strtolower( trim($this->ipsclass->input['EmailAddress_two']) );
		$this->ipsclass->input['EmailAddress']     = strtolower( trim($this->ipsclass->input['EmailAddress']) );
		
		if( preg_match( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", $this->ipsclass->input['EmailAddress_two']) )
		{
			$form_errors['email'][$this->ipsclass->lang['reg_error_email_invalid']] = $this->ipsclass->lang['reg_error_email_invalid'];
		}
		else
		{		
			if ( $in_email AND $this->ipsclass->input['EmailAddress_two'] != $in_email)
			{
				$form_errors['email'][$this->ipsclass->lang['reg_error_email_nm']] = $this->ipsclass->lang['reg_error_email_nm'];
			}
		}
		
		//-----------------------------------------
		// More unicode..
		//-----------------------------------------
		
		$len_u = preg_replace("/&#([0-9]+);/", "-", $in_username );
		$len_p = preg_replace("/&#([0-9]+);/", "-", $in_password );
		$len_d = preg_replace("/&#([0-9]+);/", "-", $members_display_name );
		
		//-----------------------------------------
		// Test dname
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			if ( ! $members_display_name OR strlen($len_d) < 3  OR strlen($len_d) > $this->ipsclass->vars['max_user_name_length'] )
			{
				$form_errors['dname'][$this->ipsclass->lang['reg_error_no_name']] = $this->ipsclass->lang['reg_error_no_name'];
			}
		}
		
		if( $this->ipsclass->vars['username_characters'] )
		{
			$check_against = preg_quote( $this->ipsclass->vars['username_characters'], "/" );
			
			if( !preg_match( "/^[".$check_against."]+$/i", $_POST['UserName'] ) && $this->ipsclass->vars['ipbli_usertype'] == 'username' )
			{
				$msg = str_replace( '{chars}', $this->ipsclass->vars['username_characters'], $this->ipsclass->vars['username_errormsg'] );
				$form_errors['username'][$msg] = $msg;
			}
			
			if( !preg_match( "/^[".$check_against."]+$/i", $_POST['members_display_name'] ) && $this->ipsclass->vars['auth_allow_dnames'] )
			{
				$msg = str_replace( '{chars}', $this->ipsclass->vars['username_characters'], $this->ipsclass->vars['username_errormsg'] );
				$form_errors['dname'][$msg] = $msg;
			}
		}
		
		//-----------------------------------------
		// Check for errors in the input.
		//-----------------------------------------
		
		if ( ! $in_username OR strlen($len_u) < 3  OR strlen($len_u) > $this->ipsclass->vars['max_user_name_length'] )
		{
			$form_errors['username'][$this->ipsclass->lang['reg_error_username_none']] = $this->ipsclass->lang['reg_error_username_none'];
		}
		
		if (! $in_password OR strlen($len_p) < 3  OR strlen($len_p) > $this->ipsclass->vars['max_user_name_length'] )
		{
			$form_errors['password'][$this->ipsclass->lang['reg_error_no_pass']] = $this->ipsclass->lang['reg_error_no_pass'];
		}
		
		if ($this->ipsclass->input['PassWord_Check'] != $in_password)
		{
			$form_errors['password'][$this->ipsclass->lang['reg_error_pass_nm']] = $this->ipsclass->lang['reg_error_pass_nm'];
		}
		
		//-----------------------------------------
		// CHECK 1: Any errors (missing fields, etc)?
		//-----------------------------------------
		
		if ( count( $form_errors ) )
		{
			$this->show_reg_form( $form_errors );
			return;
		}
		
		//-----------------------------------------
		// USERNAME: Is this name already taken?
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => strtolower($in_username) ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$name_check = $this->ipsclass->DB->fetch_row();
		
		if ( $name_check['id'] )
		{
			$form_errors['username'][$this->ipsclass->lang['reg_error_username_taken']] = $this->ipsclass->lang['reg_error_username_taken'];
		}
		
		//-----------------------------------------
		// USERNAME: Is this name already taken (display)?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
														 'from'   => 'members',
														 'where'  => "members_l_display_name='".strtolower($in_username)."'",
														 'limit'  => array( 0,1 ) ) );
													 
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$form_errors['username'][$this->ipsclass->lang['reg_error_username_taken']] = $this->ipsclass->lang['reg_error_username_taken'];
				}
			}
		}
		
		//-----------------------------------------
		// USERNAME: Special chars?
		//-----------------------------------------
		
		if ( $unicode_name != $in_username )
		{
			$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => $this->ipsclass->DB->add_slashes(strtolower($unicode_name) ) ));
			$this->ipsclass->DB->cache_exec_query();
			
			$name_check = $this->ipsclass->DB->fetch_row();
			
			if ($name_check['id'])
			{
				$form_errors['username'][$this->ipsclass->lang['reg_error_username_taken']] = $this->ipsclass->lang['reg_error_username_taken'];
			}
		}
		
		//-----------------------------------------
		// USERNAME: GUEST
		//-----------------------------------------
		
		if (strtolower($in_username) == 'guest')
		{
			$form_errors['username'][$this->ipsclass->lang['reg_error_username_taken']] = $this->ipsclass->lang['reg_error_username_taken'];
		}
		
		//-----------------------------------------
		// USERNAME: Banned?
		//-----------------------------------------

		if ( is_array( $banfilters['name'] ) and count( $banfilters['name'] ) )
		{
			foreach ( $banfilters['name'] as $n )
			{
				$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );

				if ( $n AND preg_match( "/^{$n}$/i", $in_username ) )
				{
					$form_errors['username'][$this->ipsclass->lang['reg_error_username_taken']] = $this->ipsclass->lang['reg_error_username_taken'];
					break;
				}
			}
		}
		
		//-----------------------------------------
		// DNAME
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			//-----------------------------------------
			// Illegal characters
			//-----------------------------------------
			
			if ( preg_match( "#[\[\];,\|]#", str_replace('&#39;', "'", str_replace('&amp;', '&', $unicode_dname) ) ) )
			{
				$form_errors['dname'][$this->ipsclass->lang['reg_error_chars']] = $this->ipsclass->lang['reg_error_chars'];
			}
		
			//-----------------------------------------
			// DNAME: Is this name already taken?
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'general_get_by_display_name', array( 'members_display_name' => strtolower($members_display_name) ) );
			$this->ipsclass->DB->cache_exec_query();
			
			$name_check = $this->ipsclass->DB->fetch_row();
			
			if ( $name_check['id'] )
			{
				$form_errors['dname'][$this->ipsclass->lang['reg_error_taken']] = $this->ipsclass->lang['reg_error_taken'];
			}
			
			//-----------------------------------------
			// DNAME: Check for existing LOG IN name.
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
														 'from'   => 'members',
														 'where'  => "members_l_username='".strtolower($members_display_name)."'",
														 'limit'  => array( 0,1 ) ) );
													 
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$form_errors['dname'][$this->ipsclass->lang['reg_error_taken']] = $this->ipsclass->lang['reg_error_taken'];
				}
			}
			
			//-----------------------------------------
			// DNAME: Special chars?
			//-----------------------------------------
			
			if ( $unicode_dname != $members_display_name )
			{
				$this->ipsclass->DB->cache_add_query( 'general_get_by_display_name', array( 'members_display_name' => $this->ipsclass->DB->add_slashes(strtolower($unicode_dname) ) ));
				$this->ipsclass->DB->cache_exec_query();
				
				$name_check = $this->ipsclass->DB->fetch_row();
				
				if ( $name_check['id'] )
				{
					$form_errors['dname'][$this->ipsclass->lang['reg_error_taken']] = $this->ipsclass->lang['reg_error_taken'];
				}
			}
			
			//-----------------------------------------
			// DNAME: Banned?
			//-----------------------------------------
			
			if ( is_array( $banfilters['name'] ) and count( $banfilters['name'] ) )
			{
				foreach ( $banfilters['name'] as $n )
				{
					$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
					
					if ( $n AND preg_match( "/^{$n}$/i", $members_display_name ) )
					{
						$form_errors['dname'][$this->ipsclass->lang['reg_error_taken']] = $this->ipsclass->lang['reg_error_taken'];
						break;
					}
				}
			}
			
			//-----------------------------------------
			// DNAME: GUEST
			//-----------------------------------------
			
			if (strtolower($members_display_name) == 'guest')
			{
				$form_errors['dname'][$this->ipsclass->lang['reg_error_taken']] = $this->ipsclass->lang['reg_error_taken'];
			}
		}
		
		//-----------------------------------------
		// Is this email addy taken? CONVERGE THIS??
		//-----------------------------------------
		
		if ( $this->ipsclass->converge->converge_check_for_member_by_email( $in_email ) == TRUE )
		{
			$form_errors['email'][$this->ipsclass->lang['reg_error_email_taken']] = $this->ipsclass->lang['reg_error_email_taken'];
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	$this->han_login->email_exists_check( $email );
    	
    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'EMAIL_NOT_IN_USE' )
    	{
	    	$form_errors['email'][$this->ipsclass->lang['reg_error_email_taken']] = $this->ipsclass->lang['reg_error_email_taken'];
    	}
		
		//-----------------------------------------
		// Are they banned [EMAIL]?
		//-----------------------------------------
		
		if ( is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
		{
			foreach ( $banfilters['email'] as $email )
			{
				$email = str_replace( '\*', '.*' ,  preg_quote($email, "/") );
				
				if ( preg_match( "/^{$email}$/i", $in_email ) )
				{
					$form_errors['email'][$this->ipsclass->lang['reg_error_email_ban']] = $this->ipsclass->lang['reg_error_email_ban'];
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Check the reg_code
		//-----------------------------------------
		
		if ($this->ipsclass->vars['bot_antispam'])
		{
			if ( $this->ipsclass->input['regid'] == "" )
			{
				$form_errors['general'][$this->ipsclass->lang['err_reg_code']] = $this->ipsclass->lang['err_reg_code'];
			}
			else
			{
				$this->ipsclass->DB->build_query( array( 'select' => '*',
															  'from'   => 'reg_antispam',
															  'where'  => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'"
													 )      );
									 
				$this->ipsclass->DB->exec_query();
				
				if ( ! $row = $this->ipsclass->DB->fetch_row() )
				{
					$form_errors['general'][$this->ipsclass->lang['err_reg_code']] = $this->ipsclass->lang['err_reg_code'];
				}
				else if ( trim( $this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['reg_code']) ) != $row['regcode'] )
				{
					$form_errors['general'][$this->ipsclass->lang['err_reg_code']] = $this->ipsclass->lang['err_reg_code'];
				}
				
				$this->ipsclass->DB->do_delete( 'reg_antispam', "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'" );
			}
		}
		
		//-----------------------------------------
		// CHECK 2: Any errors (duplicate names, etc)?
		//-----------------------------------------
		
		if ( count( $form_errors ) )
		{
			$this->show_reg_form( $form_errors );
			return;
		}
		
		//-----------------------------------------
		// Build up the hashes
		//-----------------------------------------
		
		$mem_group = $this->ipsclass->vars['member_group'];
		
		//-----------------------------------------
		// Are we asking the member or admin to preview?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['reg_auth_type'] )
		{
			$mem_group = $this->ipsclass->vars['auth_group'];
		}
		else if ($coppa == 1)
		{
			$mem_group = $this->ipsclass->vars['auth_group'];
		}
		else if ( $this->ipsclass->vars['subsm_enforce'] )
		{
			$mem_group = $this->ipsclass->vars['subsm_nopkg_group'];
		}
		
		$_mke_time   = ( $this->ipsclass->vars['login_key_expire'] ) ? ( time() + ( intval($this->ipsclass->vars['login_key_expire']) * 86400 ) ) : 0;
		
		$member = array(
						 'name'                   => $in_username,
						 'members_l_username'     => strtolower( $in_username ),
						 'members_display_name'   => $this->ipsclass->vars['auth_allow_dnames'] ? $members_display_name : $in_username,
						 'members_l_display_name' => strtolower( $this->ipsclass->vars['auth_allow_dnames'] ? $members_display_name : $in_username ),
						 'member_login_key'       => $this->ipsclass->converge->generate_auto_log_in_key(),
						 'member_login_key_expire' => $_mke_time,
						 'email'                  => $in_email,
						 'mgroup'                 => $mem_group,
						 'posts'                  => 0,
						 'joined'                 => time(),
						 'ip_address'             => $this->ipsclass->ip_address,
						 'time_offset'            => $this->ipsclass->input['time_offset'],
						 'view_sigs'              => 1,
						 'email_pm'               => 1,
						 'view_img'               => 1,
						 'view_avs'               => 1,
						 'restrict_post'          => 0,
						 'view_pop'               => 1,
						 'msg_total'              => 0,
						 'new_msg'                => 0,
						 'coppa_user'             => $coppa,
						 'language'               => $this->ipsclass->vars['default_language'],
						 'members_auto_dst'       => 1,
						 'members_editor_choice'  => $this->ipsclass->vars['ips_default_editor'],
						 'allow_admin_mails'      => intval( $this->ipsclass->input['allow_admin_mail'] ),
						 'hide_email'             => $this->ipsclass->input['allow_member_mail'] ? 0 : 1,
						 'subs_pkg_chosen'        => intval( $this->ipsclass->input['subspackage'] )
					   );
		
		//-----------------------------------------
		// Insert: CONVERGE
		//-----------------------------------------
		
		$salt     = $this->ipsclass->converge->generate_password_salt(5);
		$passhash = $this->ipsclass->converge->generate_compiled_passhash( $salt, md5($in_password) );
					   
		$converge = array( 'converge_email'     => $in_email,
						   'converge_joined'    => time(),
						   'converge_pass_hash' => $passhash,
						   'converge_pass_salt' => str_replace( '\\', "\\\\", $salt )
						 );
				
		//-----------------------------------------
		// Add Converge: Member
		//-----------------------------------------

   		$this->han_login->create_account( array(	'email'			=> $member['email'],
   													'joined'		=> $member['joined'],
   													'password'		=> $in_password,
   													'ip_address'	=> $this->ipsclass->ip_address
   										)		);

		if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' => 'han_login_create_failed', 'EXTRA' => $this->han_login->return_details ? $this->han_login->return_details : $this->han_login->return_code ) );
		}
		
		$this->ipsclass->DB->do_insert( 'members_converge', $converge );
		
		//-----------------------------------------
		// Get converges auto_increment user_id
		//-----------------------------------------
		
		$member_id    = $this->ipsclass->DB->get_insert_id();
		$member['id'] = $member_id;
		
		//-----------------------------------------
		// Insert: MEMBERS
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'name' => 'string', 'members_display_name' => 'string', 'members_l_username' => 'string', 'members_l_display_name' => 'string' );
									  
		$this->ipsclass->DB->do_insert( 'members', $member );
		
		//-----------------------------------------
		// Insert: MEMBER EXTRA
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'member_extra', array( 'id'        => $member_id,
															   'vdirs'     => 'in:Inbox|sent:Sent Items',
															   'interests' => '',
															   'signature' => '' ) );
		
		//-----------------------------------------
		// Insert into the custom profile fields DB
		//-----------------------------------------
		
		// Ensure deleted members profile fields are removed.
		
		$this->ipsclass->DB->do_delete( 'pfields_content', 'member_id='.$member['id'] );
		
		$this->ipsclass->DB->force_data_type = array();
		
		foreach( $fields->out_fields as $_field => $_data )
		{
			$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
		}

		$fields->out_fields['member_id'] = $member['id'];
				
		$this->ipsclass->DB->do_insert( 'pfields_content', $fields->out_fields );
				
		//-----------------------------------------
		// Use modules?
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			
			$member['password'] = trim($this->ipsclass->input['PassWord']);
			
    		$this->modules->on_create_account($member);
    		
    		if ( $this->modules->error == 1 )
    		{
    			return;
    		}
    		
    		$member['password'] = "";
   		}
   		
		//-----------------------------------------
		// Validation key
		//-----------------------------------------
		
		$validate_key = md5( $this->ipsclass->make_password() . time() );
		$time         = time();
		
		if ($coppa != 1)
		{
			if ( ($this->ipsclass->vars['reg_auth_type'] == 'user' ) or
				 ($this->ipsclass->vars['reg_auth_type'] == 'admin') or
				 ($this->ipsclass->vars['reg_auth_type'] == 'admin_user') )
			{
				//-----------------------------------------
				// We want to validate all reg's via email,
				// after email verificiation has taken place,
				// we restore their previous group and remove the validate_key
				//-----------------------------------------
				
				$this->ipsclass->DB->do_insert( 'validating', array (
													  'vid'         => $validate_key,
													  'member_id'   => $member['id'],
													  'real_group'  => $this->ipsclass->vars['subsm_enforce'] ? $this->ipsclass->vars['subsm_nopkg_group'] : $this->ipsclass->vars['member_group'],
													  'temp_group'  => $this->ipsclass->vars['auth_group'],
													  'entry_date'  => $time,
													  'coppa_user'  => $coppa,
													  'new_reg'     => 1,
													  'ip_address'  => $member['ip_address']
											)       );
				
				
				if ( $this->ipsclass->vars['reg_auth_type'] == 'user' OR $this->ipsclass->vars['reg_auth_type'] == 'admin_user' )
				{
					$this->email->get_template("reg_validate");
					
					$this->email->build_message( array(
														'THE_LINK'     => $this->base_url_nosess."?act=Reg&CODE=03&uid=".urlencode($member_id)."&aid=".urlencode($validate_key),
														'NAME'         => $member['members_display_name'],
														'MAN_LINK'     => $this->base_url_nosess."?act=Reg&CODE=05",
														'EMAIL'        => $member['email'],
														'ID'           => $member_id,
														'CODE'         => $validate_key,
													  )
												);
												
					$this->email->subject = $this->ipsclass->lang['new_registration_email'].$this->ipsclass->vars['board_name'];
					$this->email->to      = $member['email'];
					
					$this->email->send_mail();
					
					$this->output     = $this->ipsclass->compiled_templates['skin_register']->show_authorise( $member );
					
				}
				else if ( $this->ipsclass->vars['reg_auth_type'] == 'admin' )
				{
					$this->output     = $this->ipsclass->compiled_templates['skin_register']->show_preview( $member );
				}
				
				if ($this->ipsclass->vars['new_reg_notify'])
				{
					
					$date = $this->ipsclass->get_date( time(), 'LONG', 1 );
					
					$this->email->get_template("admin_newuser");
				
					$this->email->build_message( array(
														'DATE'         => $date,
														'MEMBER_NAME'  => $member['members_display_name'],
													  )
												);
												
					$this->email->subject = $this->ipsclass->lang['new_registration_email1'].$this->ipsclass->vars['board_name'];
					$this->email->to      = $this->ipsclass->vars['email_in'];
					$this->email->send_mail();
				}
				
				$this->page_title = $this->ipsclass->lang['reg_success'];
				
				$this->nav        = array( $this->ipsclass->lang['nav_reg'] );
			}
	
			else
			{
				//-----------------------------------------
				// We don't want to preview, or get them to validate via email.
				//-----------------------------------------
							 
				$this->ipsclass->cache['stats']['last_mem_name'] = $member['members_display_name'];
				$this->ipsclass->cache['stats']['last_mem_id']   = $member['id'];
				$this->ipsclass->cache['stats']['mem_count']    += 1;
				
				$this->ipsclass->update_cache(  array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0 ) );
				
				if ($this->ipsclass->vars['new_reg_notify'])
				{
					$date = $this->ipsclass->get_date( time(), 'LONG', 1 );
					
					$this->email->get_template("admin_newuser");
				
					$this->email->build_message( array(
														'DATE'         => $date,
														'MEMBER_NAME'  => $member['members_display_name'],
													  )
												);
												
					$this->email->subject = $this->ipsclass->lang['new_registration_email1'].$this->ipsclass->vars['board_name'];
					$this->email->to      = $this->ipsclass->vars['email_in'];
					$this->email->send_mail();
				}
				
				$this->ipsclass->no_print_header = 0;
				
				$this->ipsclass->my_setcookie("pass_hash"   , $member['member_login_key'], 1);
				$this->ipsclass->my_setcookie("member_id"   , $member['id']              , 1);
				$this->ipsclass->my_setcookie('session_id', '0', -1 );
				
				$this->ipsclass->stronghold_set_cookie( $member['id'], $member['member_login_key'] );
				
				$this->ipsclass->boink_it($this->ipsclass->base_url.'&act=login&CODE=autologin&fromreg=1');
			}
		}
		else
		{
			// This is a COPPA user, so lets tell them they registered OK and redirect to the form.
			
			$this->ipsclass->DB->do_insert( 'validating', array (
												  'vid'         => $validate_key,
												  'member_id'   => $member['id'],
												  'real_group'  => $this->ipsclass->vars['member_group'],
												  'temp_group'  => $this->ipsclass->vars['auth_group'],
												  'entry_date'  => $time,
												  'coppa_user'  => $coppa,
												  'new_reg'     => 1,
												  'ip_address'  => $member['ip_address']
										)       );
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['cp_success'], 'act=Reg&amp;CODE=12' );
		}
	} 
    
    /*-------------------------------------------------------------------------*/
	// validate_user
	/*-------------------------------------------------------------------------*/
	
	function validate_user()
	{
		//-----------------------------------------
		// Check for input and it's in a valid format.
		//-----------------------------------------
		
		$in_user_id      = intval(trim(urldecode($this->ipsclass->input['uid'])));
		$in_validate_key = trim(urldecode($this->ipsclass->input['aid']));
		$in_type         = trim($this->ipsclass->input['type']);
		
		if ($in_type == "")
		{
			$in_type = 'reg';
		}
		
		//-----------------------------------------
		// check input
		//-----------------------------------------
		
		if (! preg_match( "/^(?:[\d\w]){32}$/", $in_validate_key ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
		}
		
		if (! preg_match( "/^(?:\d){1,}$/", $in_user_id ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
		}
		
		//-----------------------------------------
		// Attempt to get the profile of the requesting user
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.$in_user_id ) );
			
		if ( ! $member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_mem' ) );
		}
		
		//-----------------------------------------
		// Get validating info..
		//-----------------------------------------
		
		if ( $in_type == 'lostpass' )
		{
			$validate = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id='.$in_user_id.' and lost_pass=1' ) );
		}
		else if ( $in_type == 'newemail' )
		{
			$validate = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id='.$in_user_id.' and email_chg=1' ) );
		}
		else
		{
			$validate = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id='.$in_user_id ) );
		}
		
		if ( ! $validate['member_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
		}
		
		if ( ($validate['new_reg'] == 1) && ($this->ipsclass->vars['reg_auth_type'] == "admin" ) ) 
		{ 
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key_not_allow' ) ); 
		} 
		
		if ($validate['vid'] != $in_validate_key)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_key_wrong' ) );
		}
		else
		{
			//-----------------------------------------
			// REGISTER VALIDATE
			//-----------------------------------------
			
			if ($in_type == 'reg')
			{
				if ( $validate['new_reg'] != 1 )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
				}
				
				if ( empty($validate['real_group']) )
				{
					$validate['real_group'] = $this->ipsclass->vars['member_group'];
				}
				
				//-----------------------------------------
				// SELF-VERIFICATION...
				//-----------------------------------------
				
				if ( $this->ipsclass->vars['reg_auth_type'] != 'admin_user' )
				{
					if( $validate['real_group'] )
					{
						$this->ipsclass->DB->do_update( 'members', array( 'mgroup' => intval($validate['real_group']) ), 'id='.intval($member['id']) );
					}
					
					if ( USE_MODULES == 1 )
					{
						$this->modules->register_class($this);
						$this->modules->on_group_change( $member['id'], $validate['real_group'] );
					}
					
					//-----------------------------------------
					// Update the stats...
					//-----------------------------------------
				
					$this->ipsclass->cache['stats']['last_mem_name'] = $member['members_display_name'];
					$this->ipsclass->cache['stats']['last_mem_id']   = $member['id'];
					$this->ipsclass->cache['stats']['mem_count']    += 1;
					
					$this->ipsclass->update_cache(  array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0 ) );
								 
					$this->ipsclass->my_setcookie("member_id"   , $member['id']              , 1);
					$this->ipsclass->my_setcookie("pass_hash"   , $member['member_login_key'], 1);
					
					//-----------------------------------------
					// Remove "dead" validation
					//-----------------------------------------
					
					$this->ipsclass->DB->do_delete( 'validating', "vid='".$validate['vid']."' OR (member_id={$member['id']} AND new_reg=1)" );
					
					$this->ipsclass->boink_it($this->ipsclass->base_url.'&act=login&CODE=autologin&fromreg=1');
				}
				
				//-----------------------------------------
				// ADMIN-VERIFICATION...
				//-----------------------------------------
				
				else
				{
					//-----------------------------------------
					// Update DB row...
					//-----------------------------------------
					
					$this->ipsclass->DB->do_update( 'validating', array( 'user_verified' => 1 ), "vid='".$validate['vid']."'" );
					
					//-----------------------------------------
					// Print message
					//-----------------------------------------
					
					$this->output = $this->ipsclass->compiled_templates['skin_register']->show_preview( $member );
				}
							 
			}
			
			//-----------------------------------------
			// LOST PASS VALIDATE
			//-----------------------------------------
			
			else if ($in_type == 'lostpass')
			{
				//-----------------------------------------
				// On the same page?
				//-----------------------------------------
				
				if ($validate['lost_pass'] != 1)
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'lp_no_pass' ) );
				}
				
				//-----------------------------------------
				// Test GD image
				//-----------------------------------------

				if ( $this->ipsclass->vars['bot_antispam'] )
				{
					
					if( $this->ipsclass->input['regid'] == '' )
					{
						$this->show_manual_form( 'lostpass', 'err_reg_code' );
						return;
					}

					$this->ipsclass->DB->simple_construct( array( 'select' => '*',
																  'from'   => 'reg_antispam',
																  'where'  => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'"
														 )      );
										 
					$this->ipsclass->DB->simple_exec();

					if ( ! $row = $this->ipsclass->DB->fetch_row() )
					{
						$this->show_manual_form( 'lostpass', 'err_reg_code' );
						return;
					}
					
					if ( trim( $this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['reg_code']) ) != $row['regcode'] )
					{
						$this->show_manual_form( 'lostpass', 'err_reg_code' );
						return;
					}
				}

				//-----------------------------------------
				// Send a new random password?
				//-----------------------------------------
				
				if ( $this->ipsclass->vars['lp_method'] == 'random' )
				{
					//-----------------------------------------
					// INIT
					//-----------------------------------------
					
					$save_array = array();
					
					//-----------------------------------------
					// Generate a new random password
					//-----------------------------------------
					
					$new_pass = $this->ipsclass->make_password();
					
					//-----------------------------------------
					// Generate a new salt
					//-----------------------------------------
					
					$salt = $this->ipsclass->converge->generate_password_salt(5);
					$salt = str_replace( '\\', "\\\\", $salt );
					
					//-----------------------------------------
					// New log in key
					//-----------------------------------------
					
					$key  = $this->ipsclass->converge->generate_auto_log_in_key();
					
					//-----------------------------------------
					// Update...
					//-----------------------------------------
					
					$save_array['converge_pass_salt'] = $salt;
					$save_array['converge_pass_hash'] = md5( md5($salt) . md5( $new_pass ) );
					
			        //-----------------------------------------
			    	// Load handler...
			    	//-----------------------------------------
			    	
			    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
			    	$this->han_login           =  new han_login();
			    	$this->han_login->ipsclass =& $this->ipsclass;
			    	$this->han_login->init();
			    	$this->han_login->change_pass( $member['email'], md5( $new_pass ) );
			    	
			    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
			    	{
						$this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' => 'han_login_pass_failed' ) );
			    	}
					
					$this->ipsclass->DB->do_update( 'members_converge', $save_array                        , "converge_email='" . $member['email'] . "'" );
					$this->ipsclass->DB->do_update( 'members'         , array( 'member_login_key' => $key ), 'id='.intval( $member['id'] ) );
					
					//-----------------------------------------
					// Send out the email...
					//-----------------------------------------
					
					$this->email->get_template("lost_pass_email_pass");

					$this->email->build_message( array(
														'NAME'         => $member['members_display_name'],
														'THE_LINK'     => $this->base_url_nosess."?act=usercp&CODE=28",
														'PASSWORD'     => $new_pass,
														'LOGIN'        => $this->base_url_nosess."?act=login",
														'USERNAME'     => $member['name'],
														'EMAIL'        => $member['email'],
														'ID'           => $member['id'],
													  )
												);

					$this->email->subject = $this->ipsclass->lang['lp_random_pass_subject'].' '.$this->ipsclass->vars['board_name'];
					$this->email->to      = $member['email'];

					$this->email->send_mail();

					$this->output = $this->ipsclass->compiled_templates['skin_register']->show_lostpasswait_random( $member );
					
					
				}
				//-----------------------------------------
				// Allow user to choose...
				//-----------------------------------------
				else
				{
					if ( $_POST['pass1'] == "" )
					{
						$this->ipsclass->Error( array( LEVEL => 1, MSG => 'pass_blank' ) );
					}
				
					if ( $_POST['pass2'] == "" )
					{
						$this->ipsclass->Error( array( LEVEL => 1, MSG => 'pass_blank' ) );
					}
				
					if ($this->ipsclass->input['regid'] == "" AND $this->ipsclass->vars['bot_antispam'] != 0)
					{
						$this->show_manual_form( 'lostpass', 'err_reg_code' );
						return;
					}
				
					$pass_a = trim($this->ipsclass->input['pass1']);
					$pass_b = trim($this->ipsclass->input['pass2']);
				
					if ( strlen($pass_a) < 3 )
					{
						$this->ipsclass->Error( array( LEVEL => 1, MSG => 'pass_too_short' ) );
					}
				
					if ( $pass_a != $pass_b )
					{
						$this->ipsclass->Error( array( LEVEL => 1, MSG => 'pass_no_match' ) );
					}
				
					$new_pass = md5($pass_a);
					
			        //-----------------------------------------
			    	// Load handler...
			    	//-----------------------------------------
			    	
			    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
			    	$this->han_login           =  new han_login();
			    	$this->han_login->ipsclass =& $this->ipsclass;
			    	$this->han_login->init();
			    	$this->han_login->change_pass( $member['email'], $new_pass );
			    	
			    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
			    	{
						$this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' => 'han_login_pass_failed' ) );
			    	}
				
					$this->ipsclass->converge->converge_update_password( $new_pass, $member['email'] );
				
					$this->ipsclass->my_setcookie("member_id"   , $member['id']              , 1);
					$this->ipsclass->my_setcookie("pass_hash"   , $member['member_login_key'], 1);
				
					//-----------------------------------------
					// Remove "dead" validation
					//-----------------------------------------
				
					$this->ipsclass->DB->do_delete( 'validating', "vid='".$validate['vid']."' OR (member_id={$member['id']} AND lost_pass=1)" );
					$this->ipsclass->DB->do_delete( 'reg_antispam', "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['regid']))."'" );
				
					$this->ipsclass->boink_it($this->ipsclass->base_url.'&act=login&CODE=autologin&frompass=1');
				}
			}
			
			//-----------------------------------------
			// EMAIL ADDY CHANGE
			//-----------------------------------------
			
			else if ($in_type == 'newemail')
			{
				if ( $validate['email_chg'] != 1 )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
				}
				
				if( $validate['real_group'] )
				{
					$this->ipsclass->DB->do_update( 'members', array( 'mgroup' => intval($validate['real_group']) ), 'id='.intval($member['id']) );
				}
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class($this);
    				$this->modules->on_group_change($member['id'], $validate['real_group']);
    			}
    			
				$this->ipsclass->my_setcookie("member_id"   , $member['id']              , 1);
				$this->ipsclass->my_setcookie("pass_hash"   , $member['member_login_key'], 1);
				
				//-----------------------------------------
				// Remove "dead" validation
				//-----------------------------------------
				
				$this->ipsclass->DB->do_delete( 'validating', "vid='".$validate['vid']."' OR (member_id={$member['id']} AND email_chg=1)" );
				
				$this->ipsclass->boink_it($this->ipsclass->base_url.'&act=login&CODE=autologin&fromemail=1');
			}
		} 
	} 

	/*-------------------------------------------------------------------------*/
	// Manual Lost Password Form
	/*-------------------------------------------------------------------------*/
	
	function show_manual_form($type='reg', $errors="")
	{
		if ( $type == 'lostpass' )
		{
	    	if ($errors != "")
	    	{
	    		$this->output .= $this->ipsclass->compiled_templates['skin_register']->errors( $this->ipsclass->lang[$errors]);
	    	}
	    	
			$this->output .= $this->ipsclass->compiled_templates['skin_register']->show_lostpass_form();
			
			//-----------------------------------------
			// Check for input and it's in a valid format.
			//-----------------------------------------
			
			if ( $this->ipsclass->input['uid'] AND $this->ipsclass->input['aid'] )
			{ 
				$in_user_id      = intval(trim(urldecode($this->ipsclass->input['uid'])));
				$in_validate_key = trim(urldecode($this->ipsclass->input['aid']));
				$in_type         = trim($this->ipsclass->input['type']);
				
				if ($in_type == "")
				{
					$in_type = 'reg';
				}
				
				//-----------------------------------------
				// Check and test input
				//-----------------------------------------
				
				if (! preg_match( "/^(?:[\d\w]){32}$/", $in_validate_key ) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
				}
				
				if (! preg_match( "/^(?:\d){1,}$/", $in_user_id ) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'data_incorrect' ) );
				}
				
				//-----------------------------------------
				// Attempt to get the profile of the requesting user
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=$in_user_id" ) );
		
				$this->ipsclass->DB->simple_exec();
		
				if ( ! $member = $this->ipsclass->DB->fetch_row() )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_mem' ) );
				}
				
				//-----------------------------------------
				// Get validating info..
				//-----------------------------------------
				
				$validate = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'validating', 'where' => "member_id=$in_user_id and vid='$in_validate_key' and lost_pass=1" ) );
				
				if ( ! $validate['member_id'] )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'auth_no_key' ) );
				}
				
				$this->output = str_replace( "<!--IBF.INPUT_TYPE-->", $this->ipsclass->compiled_templates['skin_register']->show_lostpass_form_auto($in_validate_key, $in_user_id), $this->output );
			}
			else
			{
				$this->output = str_replace( "<!--IBF.INPUT_TYPE-->", $this->ipsclass->compiled_templates['skin_register']->show_lostpass_form_manual(), $this->output );
			}
			
			if ($this->ipsclass->vars['bot_antispam'])
			{
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
							
	    	if ($this->ipsclass->vars['bot_antispam'] == 'gd')
			{
				$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->ipsclass->compiled_templates['skin_register']->bot_antispam_gd( $regid ), $this->output );
			}
			else if ($this->ipsclass->vars['bot_antispam'] == 'gif')
			{
				$this->output = str_replace( "<!--{REG.ANTISPAM}-->", $this->ipsclass->compiled_templates['skin_register']->bot_antispam( $regid ), $this->output );
			}
		}
		else
		{
			$this->output     = $this->ipsclass->compiled_templates['skin_register']->show_dumb_form($type);
		}
		
		$this->page_title = $this->ipsclass->lang['activation_form'];
		$this->nav        = array( $this->ipsclass->lang['activation_form'] );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Show reg image
	/*-------------------------------------------------------------------------*/
	
	function show_image()
	{
		if ( $this->ipsclass->input['rc'] == "" )
		{
			return false;
		}
	
		// Get the info from the db
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
									  				  'from'   => 'reg_antispam',
													  'where'  => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['rc']))."'"
											 )      );
							 
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
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