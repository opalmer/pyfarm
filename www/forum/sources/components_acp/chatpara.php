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
|   > $Date: 2007-04-24 17:35:04 -0400 (Tue, 24 Apr 2007) $
|   > $Revision: 952 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Framework for IPS Services
|   > Module written by Matt Mecham
|   > Date started: 17 February 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_chatpara
{
	var $ipsclass;
	var $base_url;
	
	/*-------------------------------------------------------------------------*/
	// IPB CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		//-----------------------------------------
		// Kill globals - globals bad, Homer good.
		//-----------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		//-----------------------------------------
		// Make sure we're a root admin, or else!
		//-----------------------------------------
		
		if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
		{
			$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}

		switch($this->ipsclass->input['code'])
		{
			case 'ipchat04':
				$this->chat_splash();
				break;
			case 'chatsettings':
				$this->chat04_config();
				break;
			case 'chatsave':
				$this->chat_save();
				break;
			case 'dochat':
				$this->chat_config_save();
				break;
			default:
				$this->ipsclass->input['code'] = 'show';
				$this->chat_splash();
				break;
		}
	}
		
	
	/*-------------------------------------------------------------------------*/
	// CHAT SPLASH
	/*-------------------------------------------------------------------------*/
	
	function chat_splash()
	{
		//-----------------------------------------
		// Do we have an order number
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['chat04_account_no'] )
		{
			$this->chat04_config();
		}
		else
		{
			$this->ipsclass->admin->page_title  = "Invision Power Chat (Parachat)";
			$this->ipsclass->admin->page_detail = "If you have already purchased IP Chat via Parachat, then simply enter your Site ID in the box below.";
			
			$this->ipsclass->html .= "<center><font color=red><b>Parachat Disabled!</b></font></center>";
									  
			$this->ipsclass->admin->show_inframe( '' );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// CHAT SAVE
	/*-------------------------------------------------------------------------*/
	
	function chat_save()
	{
		//-----------------------------------------
		// Load libby-do-dah
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/settings.php' );
		$adsettings           =  new ad_settings();
		$adsettings->ipsclass =& $this->ipsclass;
		
		$acc_number = $this->ipsclass->input['account_no'];
		
		if ( $acc_number == "" )
		{
			$this->ipsclass->admin->error("Sorry, that is not a valid IP Chat account number");
		}
		
		$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $acc_number ), "conf_key='chat04_account_no'" );
		$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => '' )         , "conf_key='chat_account_no'" );
		$adsettings->setting_rebuildcache();
		
		//-----------------------------------------
		// Update this component
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/api/api_core.php' );
		require_once( ROOT_PATH . 'sources/api/api_components.php' );
		
		$api           =  new api_components();
		$api->ipsclass =& $this->ipsclass;
		
		$fields = array( 'com_enabled'    => 1,
						 'com_menu_data'  => array( 0 => array( 'menu_text'    => 'Chat Settings',
						 										'menu_url'     => 'code=chatsettings',
						 										'menu_permbit' => 'edit' ) ) );
		
		$api->acp_component_update( 'chatpara', $fields );
		
		//-----------------------------------------
		// Show config
		//-----------------------------------------
		
		$this->chat04_config();
	}
	
	/*-------------------------------------------------------------------------*/
	// NEW CHAT
	/*-------------------------------------------------------------------------*/
	
	function chat04_config()
	{
		$this->ipsclass->admin->page_detail = "You may edit the configuration below to suit";
		$this->ipsclass->admin->page_title  = "Invision Power Chat Configuration";
		
		//-----------------------------------------
		// Load libby-do-dah
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/settings.php' );
		$settings           =  new ad_settings();
		$settings->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Did we reset the component?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['chat04_account_no'] )
		{
			$this->chat_splash();
		}
		else
		{
			//-----------------------------------------
			// Update version 6 or 7
			//-----------------------------------------
			
			if ( preg_match( "#^\d#", $this->ipsclass->vars['chat04_account_no'] ) )
			{
				$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 7 ), "conf_key='parachat_version'" );
			}
			else
			{
				$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 6 ), "conf_key='parachat_version'" );
			}
			
			$settings->setting_rebuildcache();
		}
		
		$settings->get_by_key        = 'chat04';
		$settings->return_after_save = 'section=components&act=chatpara&code=show';
		
		$settings->setting_view();
	}

}

?>