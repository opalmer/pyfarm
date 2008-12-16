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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > PORTAL FUNCTIONS
|   > Module written by Matt Mecham
|   > Date started: Tuesday 2nd August 2005 (10:32)
+--------------------------------------------------------------------------
*/

/**
* ACP MODULE: Portal Plug-ins
*
* Manage portal plug-ins
*
* @package		InvisionPowerBoard
* @subpackage	ActionAdmin
* @author  		Matt Mecham
* @version		2.1
* @since		2.1.0.2005-08-02
*/

/**
*
*/
if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
* ACP MODULE: Portal Plug-ins
*
* Manage portal plug-ins
*
* @package		InvisionPowerBoard
* @subpackage	ActionAdmin
* @author  		Matt Mecham
* @version		2.1
* @since		2.1.0.2005-08-02
*/
class ad_portal
{
	/**
	* IPB object
	*
	* @var object
	*/
	var $ipsclass;
	
	/**
	* Perm main
	*
	* @var string
	*/
	var $perm_main  = 'tools';
	
	/**
	* Perm child
	*
	* @var string
	*/
	var $perm_child = 'portal';
	
	/**
	* Portal objects
	*
	* @var array
	*/
	var $portal_objects = array();
	
	/*-------------------------------------------------------------------------*/
	// Main handler
	/*-------------------------------------------------------------------------*/
	
	function auto_run() 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_tools');
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		$this->ipsclass->acp_load_language( 'acp_lang_portal' );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'manage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->portal_list();
				break;
			
			case 'portal_settings':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->portal_settings();
				break;
				
			case 'portal_viewtags':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->portal_viewtags();
				break;
				
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->portal_list();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	/**
	* Rebuild Portal Cache
	*
	* @return void
	* @since 2.1.0.2005.08.02
	*/
	function portal_rebuildcache()
	{
		$this->ipsclass->cache['portal'] = array();
			
		if ( ! is_array( $this->portal_objects ) or ! count( $this->portal_objects ) )
		{
			$this->get_portal_objects();
		}
		
		$this->ipsclass->cache['portal'] = $this->portal_objects;
		
		$this->ipsclass->update_cache( array( 'name' => 'portal', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Show portal settings
	/*-------------------------------------------------------------------------*/
	/**
	* Show portal settings
	*
	* @return void
	* @since 2.1.0.2005.08.02
	*/
	function portal_viewtags()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$pc_key  = $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['pc_key'] );
		$file    = ROOT_PATH . 'sources/portal_plugins/'.$pc_key.'-cfg.php';
		$content = "";
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( ! $pc_key OR ! file_exists( $file ) )
		{
			$this->ipsclass->main_msg = $this->ipsclass->acp_lang['error_no_key'];
			$this->portal_list();
		}
		
		//-------------------------------
		// Grab config file
		//-------------------------------
		
		require_once( $file );
		
		if ( is_array( $PORTAL_CONFIG['pc_exportable_tags'] ) AND count( $PORTAL_CONFIG['pc_exportable_tags'] ) )
		{
			foreach( $PORTAL_CONFIG['pc_exportable_tags'] as $tag => $tag_data )
			{
				$content .= $this->html->portal_pop_row( $tag, $tag_data[1] );
			}
		}
		
		$this->ipsclass->html .= $this->html->portal_pop_overview( $PORTAL_CONFIG['pc_title'], $content );
		
		//-------------------------------
		// Print
		//-------------------------------
		
		$this->ipsclass->admin->print_popup();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show portal settings
	/*-------------------------------------------------------------------------*/
	/**
	* Show portal settings
	*
	* @return void
	* @since 2.1.0.2005.08.02
	*/
	function portal_settings()
	{
		//-------------------------------
		// Page Data
		//-------------------------------
		
		$this->ipsclass->admin->nav[]       = array( $this->ipsclass->form_code, $this->ipsclass->acp_lang['main_nav'] );
		$this->ipsclass->admin->page_title  = $this->ipsclass->acp_lang['main_title'];
		$this->ipsclass->admin->page_detail = $this->ipsclass->acp_lang['main_help'];
		
		//-------------------------------
		// INIT
		//-------------------------------
		
		$pc_key = $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['pc_key'] );
		$file   = ROOT_PATH . 'sources/portal_plugins/'.$pc_key.'-cfg.php';
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( ! $pc_key OR ! file_exists( $file ) )
		{
			$this->ipsclass->main_msg = $this->ipsclass->acp_lang['error_no_key'];
			$this->portal_list();
		}
		
		//-------------------------------
		// Grab config file
		//-------------------------------
		
		require_once( $file );
		
		if ( ! $PORTAL_CONFIG['pc_settings_keyword'] )
		{
			$this->ipsclass->main_msg = $this->ipsclass->acp_lang['error_no_settings'];
			$this->portal_list();
		}
		
		//-------------------------------
		// Grab, init and load settings
		//-------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/settings.php' );
		$settings             =  new ad_settings();
		$settings->ipsclass   =& $this->ipsclass;
		
		$settings->get_by_key        = $PORTAL_CONFIG['pc_settings_keyword'];
		$settings->return_after_save = $this->ipsclass->form_code.'&code=portal_settings&pc_key='.$pc_key;
		
		$settings->setting_view();
	}
	
	/*-------------------------------------------------------------------------*/
	// List portal objects
	/*-------------------------------------------------------------------------*/
	/**
	* List portal objects
	*
	* @return void
	* @since 2.1.0.2005.08.02
	*/
	function portal_list()
	{
		//-------------------------------
		// Page Data
		//-------------------------------
		
		$this->ipsclass->admin->nav[]       = array( $this->ipsclass->form_code, $this->ipsclass->acp_lang['main_nav'] );
		$this->ipsclass->admin->page_title  = $this->ipsclass->acp_lang['main_title'];
		$this->ipsclass->admin->page_detail = $this->ipsclass->acp_lang['main_help'];
		
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = "";
		
		//-------------------------------
		// Get portal objects
		//-------------------------------
		
		$this->get_portal_objects();
		
		foreach( $this->portal_objects as $portal_data )
		{
			//-------------------------------
			// (Alex) Cross
			//-------------------------------
				
			$content .= $this->html->portal_row( $portal_data );
		}
		
		$this->ipsclass->html .= $this->html->portal_overview( $content );
		
		//-------------------------------
		// Update cache
		//-------------------------------
			
		$this->portal_rebuildcache();
		
		//-------------------------------
		// Print
		//-------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Return portal objects
	/*-------------------------------------------------------------------------*/
	/**
	* Return portal objects
    *
    * Picks through the portal objects directory
	*
	* @return void
	* @since 2.1.0.2005.08.02
	*/
	function get_portal_objects()
	{
		//-------------------------------
		// Got a directory?
		//-------------------------------
		
		if ( ! file_exists( ROOT_PATH.'sources/portal_plugins' ) )
		{
			$this->ipsclass->main_msg = $this->ipsclass->acp_lang['error_no_dir'];
			$this->portal_objects     = array();
			return;
		}
		
		//-------------------------------
		// Go loopy
		//-------------------------------
		
		$handle = opendir( ROOT_PATH.'sources/portal_plugins' );
			
		while ( ($file = readdir($handle) ) !== FALSE )
		{
			if ( ($file != ".") && ($file != "..") )
			{
				preg_match( "#^(.*)-cfg\.php$#", $file, $matches );
				
				if ( $matches[0] AND $matches[1] )
				{
					//-------------------------------
					// Include file...
					//-------------------------------
					
					$PORTAL_CONFIG = array();
					
					require_once( ROOT_PATH . 'sources/portal_plugins/' . $matches[0] );
					
					if ( is_array( $PORTAL_CONFIG ) AND count( $PORTAL_CONFIG ) )
					{
						$PORTAL_CONFIG['pc_key']             = $matches[1];
						
						$this->portal_objects[ $matches[1] ] = $PORTAL_CONFIG;
					}
				}
			}
		}
		
		closedir($handle); 
	}
	

}


?>