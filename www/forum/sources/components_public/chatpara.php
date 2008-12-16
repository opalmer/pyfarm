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
|   > $Date: 2007-05-15 17:56:01 -0400 (Tue, 15 May 2007) $
|   > $Revision: 997 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > MODULE PUBLIC FILE: EXAMPLE
|   > Module written by Matt Mecham
|   > Date started: Fri 12th August 2005 (17:16)
|
+--------------------------------------------------------------------------
*/

/**
* MODULE: Public Example File (IPB 3.0 Methods)
* "modules" is depreciated in IPB 3.0
*
* @package		InvisionPowerBoard
* @subpackage	Components
* @author  		Matt Mecham
* @version		2.1
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
* MODULE: Public Example File (IPB 3.0 Methods)
*
* This class must ALWAYS be called "component_public"
*
* @package		InvisionPowerBoard
* @subpackage	Components
* @author  		Matt Mecham
* @version		2.1
*/
class component_public
{
	/**
	* IPSclass object
	*
	* @var object
	*/
	var $ipsclass;
	
	
	/**
	* Main function that's run from index.php
	*
	*/
	function run_component()
	{
		switch( $this->ipsclass->input['code'] )
		{
			case 'show':
				$this->display_chat();
				break;
			case 'update':
				$this->update_session();
				break;
			default:
				$this->display_chat();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Update user's session
	/*-------------------------------------------------------------------------*/
	
	function update_session()
	{
		//-----------------------------------------
		// Got sess ID and mem ID?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['id'] )
		{
			print "no";
			exit();
		}
		
		//-----------------------------------------
		// Two hours of not doing anything...
		//-----------------------------------------
		
		if ( $this->ipsclass->lastclick < ( time() - 7200 ) )
		{
			print "no";
			exit();
		}
		
		$tmp_cache = $this->ipsclass->cache['chatting'];
		
		$this->ipsclass->cache['chatting'] = array();
		
		//-----------------------------------------
		// Goforit
		//-----------------------------------------
		
		if ( is_array( $tmp_cache ) and count( $tmp_cache ) )
		{
			foreach( $tmp_cache as $id => $data )
			{
				//-----------------------------------------
				// Not hit in 2 mins?
				//-----------------------------------------
				
				if ( $data['updated'] < ( time() - 120 ) )
				{
					continue;
				}
				
				if ( $id == $this->ipsclass->member['id'] )
				{
					continue;
				}
				
				$this->ipsclass->cache['chatting'][ $id ] = $data;
			}
		}
		
		//-----------------------------------------
		// Add in us
		//-----------------------------------------
		
		$this->ipsclass->cache['chatting'][ $this->ipsclass->member['id'] ] = array( 'updated' => time(), 'name' => $this->ipsclass->member['members_display_name'] );
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
														  
		$this->ipsclass->update_cache( array( 'name' => 'chatting', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		//-----------------------------------------
		// Something to return
		//-----------------------------------------
		
		print "ok";
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Display chat
	/*-------------------------------------------------------------------------*/
	
	function display_chat()
	{
		//-----------------------------------------
		// Load HTML and LANG
		//-----------------------------------------
		
		$this->ipsclass->load_language('lang_chatpara');
		$this->ipsclass->load_template('skin_chatpara');
		
		if ( ! $this->ipsclass->vars['chat04_account_no'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//-----------------------------------------
		// Get extra settings
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'conf_key,conf_value,conf_default', 'from' => 'conf_settings', 'where' => "conf_key LIKE 'chat04%'" ) );
    	$this->ipsclass->DB->simple_exec();
    	
    	while( $r = $this->ipsclass->DB->fetch_row() )
    	{
    		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
    		
    		$this->ipsclass->vars[ $r['conf_key'] ] = $value;
    	}
    	
    	if( $this->ipsclass->vars['chat04_access_groups'] )
    	{
	    	$access_groups = explode( ",", $this->ipsclass->vars['chat04_access_groups'] );
	    	
	    	$my_groups = array( $this->ipsclass->member['mgroup'] );
	    	
	    	if( $this->ipsclass->member['mgroup_others'] )
	    	{
		    	$my_groups = array_merge( $my_groups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) );
	    	}
	    	
	    	$access_allowed = 0;
	    	
	    	foreach( $my_groups as $group_id )
	    	{
		    	if( in_array( $group_id, $access_groups ) )
		    	{
			    	$access_allowed = 1;
		    	}
	    	}
	    	
	    	if( !$access_allowed )
	    	{
		    	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
	    	}
    	}
		
		//-----------------------------------------
		// Width and Height
		//-----------------------------------------
		
		$width  = $this->ipsclass->vars['chat04_width']  ? $this->ipsclass->vars['chat04_width']  : 600;
		$height = $this->ipsclass->vars['chat04_height'] ? $this->ipsclass->vars['chat04_height'] : 350;
		
		//-----------------------------------------
		// v6 < specifics
		//-----------------------------------------
		
		if ( intval($this->ipsclass->vars['parachat_version']) < 7 )
		{
			//-----------------------------------------
			// Got room?
			//-----------------------------------------
		
			if ( ! $this->ipsclass->vars['chat04_default_room'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
			}
		
			//-----------------------------------------
			// Got service type?
			//-----------------------------------------
		
			if ( ! $this->ipsclass->vars['chat04_servicetype'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
			}
		
			//-----------------------------------------
			// Make sure it has #
			//-----------------------------------------
		
			$this->ipsclass->vars['chat04_default_room'] = '#'.str_replace( '#', '', $this->ipsclass->vars['chat04_default_room'] );
		
			//-----------------------------------------
			// Get service library
			//-----------------------------------------
		
			require_once( ROOT_PATH.'retail/chatservice.php' );
		
			$server = $this->ipsclass->vars['parachat_codebase_url'] ? $this->ipsclass->vars['parachat_codebase_url'] : 'http://'. $CHAT_SERVER[ $this->ipsclass->vars['chat04_servicetype'] ].'/'. $CHAT_FOLDER[ $this->ipsclass->vars['chat04_servicetype'] ];
		
			//-----------------------------------------
			// Lang?
			//-----------------------------------------
		
			$this->ipsclass->vars['chat04_default_lang'] = ( $this->ipsclass->vars['chat04_default_lang'] == "" ) ? 'english.conf' : $this->ipsclass->vars['chat04_default_lang'];
		
			//-----------------------------------------
			// Text mode
			//-----------------------------------------
		
			$this->ipsclass->vars['chat04_plainmode'] = ( $this->ipsclass->vars['chat04_plainmode'] ) ? 'PlainText' : 'MegaText';
		
			//-----------------------------------------
			// Style options..
			//-----------------------------------------
		
			$style = array(
							'applet_bg' => $this->ipsclass->vars['chat04_style_applet_bg'] ? str_replace( '#', '', $this->ipsclass->vars['chat04_style_applet_bg'] ) : 'BCD0ED',
							'applet_fg' => $this->ipsclass->vars['chat04_style_applet_fg'] ? str_replace( '#', '', $this->ipsclass->vars['chat04_style_applet_fg'] ) : '345487',
							'window_bg' => $this->ipsclass->vars['chat04_style_window_bg'] ? str_replace( '#', '', $this->ipsclass->vars['chat04_style_window_bg'] ) : 'F5F9FD',
							'window_fg' => $this->ipsclass->vars['chat04_style_window_fg'] ? str_replace( '#', '', $this->ipsclass->vars['chat04_style_window_fg'] ) : '345487',
							'font_size' => $this->ipsclass->vars['chat04_style_font_size'] ? str_replace( '#', '', $this->ipsclass->vars['chat04_style_font_size'] ) : '11',
						  );
						
			//-----------------------------------------
			// Show chat..
			//-----------------------------------------

			$this->output .= $this->ipsclass->compiled_templates['skin_chatpara']->chat_inline( $server, $this->ipsclass->vars['chat04_account_no'], $this->ipsclass->vars['chat04_default_room'], $width, $height, $this->ipsclass->vars['chat04_default_lang'], $this->ipsclass->vars['chat04_plainmode'], $style );
		}
		else
		{
			//-----------------------------------------
			// Show chat..
			//-----------------------------------------
			
			$server = $this->ipsclass->vars['parachat_codebase_url'] ? $this->ipsclass->vars['parachat_codebase_url'] : "http://host9.parachat.com/pchat/applet";
			$room   = $this->ipsclass->vars['chat04_default_room']   ? $this->ipsclass->vars['chat04_default_room']   : "Lobby";
			
			if ( strstr( strtolower( $this->ipsclass->vars['chat04_default_room'] ), 'lobby_' ) )
			{
				$room = 'Lobby';
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_chatpara']->chat_inline_v7( $server, $this->ipsclass->vars['chat04_account_no'], $width, $height, $room );
		}
		
		//-----------------------------------------
		// Show chat..
		//-----------------------------------------
		
		$this->output = str_replace( '<!--AUTOLOGIN-->'  , $this->ipchat_auto_login()                  , $this->output );
		$this->output = str_replace( '<!--CUSTOMPARAM-->', $this->ipsclass->vars['chat04_customparams'], $this->output );
		
		if ( ! $this->ipsclass->input['pop'] )
		{
			$this->nav[]	  = $this->ipsclass->lang['live_chat'];
			$this->page_title = $this->ipsclass->lang['live_chat'];
			
			$this->ipsclass->print->add_output( $this->output );
			$this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
        }
        else
        {
        	$this->ipsclass->print->pop_up_window( 'Chat', $this->output );
        }
	}
	
	/*-------------------------------------------------------------------------*/
	// IPCHAT (NEW) Auto_login
	/*-------------------------------------------------------------------------*/
	
	function ipchat_auto_login()
	{
		if ( $this->ipsclass->member['id'] )
		{
			$converge_member = $this->ipsclass->converge->converge_load_member($this->ipsclass->member['email']);
			$pass = $this->ipsclass->converge->member['converge_pass_hash'];
			
			$tmpname   = $this->ipsclass->member['members_display_name'];
			$namearray = array();
			$name      = "";
			
			//-----------------------------------------
			// Okay, we need to safe format this name
			//-----------------------------------------
			
			$tmpname = preg_replace( "#\s#", "_", $tmpname );
			$tmpname = preg_replace( "#(?:[^\w\d\_])#is", "-", $tmpname );
			
			if ( intval( $this->ipsclass->vars['parachat_version'] ) > 6 )
			{
				$return ="<param name=\"Ctrl.AutoLogin\" value=\"true\">
						  <param name=\"Net.User\" value=\"".$tmpname."\">
						  <param name=\"Net.UserPass\" value=\"".urlencode("md5pass({$pass}){$this->ipsclass->member['id']}")."\">\n";
			
			}
			else
			{
				$return = "<param name='ctrl.LoginOnLoad' value='true'>\n".
	      				  "<param name='ctrl.Nickname' value='".$tmpname."'>\n".
	      				  "<param name='ctrl.RealName' value='".$this->ipsclass->member['members_display_name']."'>\n".
	      				  "<param name='ctrl.Password' value='".urlencode("md5pass({$pass}){$this->ipsclass->member['id']}")."'>\n";
			}
      				   
      		return $return;
		}
		else
		{
			return;
		}
	}
}

?>