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
|   > $Date: 2007-03-08 17:35:29 -0500 (Thu, 08 Mar 2007) $
|   > $Revision: 875 $
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
		// Make sure we have a last click
		//-----------------------------------------
		
		$this->ipsclass->lastclick = $this->ipsclass->lastclick ? $this->ipsclass->lastclick : time();
		
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
		
		$this->ipsclass->load_language('lang_chatsigma');
		$this->ipsclass->load_template('skin_chatsigma');
		
		if ( ! $this->ipsclass->vars['chat_account_no'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//-----------------------------------------
		// Get extra settings
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'conf_key,conf_value,conf_default', 'from' => 'conf_settings', 'where' => "conf_key LIKE 'chat%'" ) );
    	$this->ipsclass->DB->simple_exec();
    	
    	while( $r = $this->ipsclass->DB->fetch_row() )
    	{
    		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
    		
    		$this->ipsclass->vars[ $r['conf_key'] ] = $value;
    	}
    	
		//-----------------------------------------
		// Can this group access chat?
		//-----------------------------------------
		    	
        if( $this->ipsclass->vars['chat_access_groups'] )
        {
            $group_access = explode( ",", $this->ipsclass->vars['chat_access_groups'] );
            
            if( !in_array( $this->ipsclass->member['mgroup'], $group_access ) )
            {
                $this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
            }
        }    	
		
		//-----------------------------------------
		// Got address?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['chat_server_addr'] )
		{
			$this->ipsclass->vars['chat_server_addr'] = '';
		}
		
		//-----------------------------------------
		// Server
		//-----------------------------------------
		
		$this->ipsclass->vars['chat_server_addr'] = str_replace( 'http://', '', $this->ipsclass->vars['chat_server_addr'] );
		
		//-----------------------------------------
		// Details
		//-----------------------------------------
		
		$width  = $this->ipsclass->vars['chat_width']    ? $this->ipsclass->vars['chat_width']  : 600;
		$height = $this->ipsclass->vars['chat_height']   ? $this->ipsclass->vars['chat_height'] : 350;
		
		$lang   = $this->ipsclass->vars['chat_language'] ? $this->ipsclass->vars['chat_language'] : 'en';
		
		$user = "";
		$pass = "";
		
		//-----------------------------------------
		// Got ID?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] )
		{
			$user            = $this->ipsclass->member['members_display_name'];
			
			$converge_member = $this->ipsclass->converge->converge_load_member( $this->ipsclass->member['email'] );
			$pass            = $this->ipsclass->converge->member['converge_pass_hash'];
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_chatsigma']->chat_inline( $this->ipsclass->vars['chat_server_addr'], $this->ipsclass->vars['chat_account_no'], $lang, $width, $height, $user, $pass);
		
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
}

?>