<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.0.2 -> IPB 2.0.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	var $install;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function version_upgrade( & $install )
	{
		$this->install = & $install;
	}
	
	/*-------------------------------------------------------------------------*/
	// Auto run..
	/*-------------------------------------------------------------------------*/

	function auto_run()
	{
		//-----------------------------------------
		// Remove dupe categories
		//-----------------------------------------
		
		$title_id_to_keep    = array();
		$title_id_to_delete  = array();
		$title_deleted_count = 0;
		$msg                 = '';
		
		$this->install->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings_titles', 'order' => 'conf_title_id DESC' ) );
		$this->install->ipsclass->DB->simple_exec();
		
		while ( $r = $this->install->ipsclass->DB->fetch_row() )
		{
			if ( $title_id_to_keep[ $r['conf_title_title'] ] )
			{
				$title_id_to_delete[ $r['conf_title_id'] ] = $r['conf_title_id'];
			}
			else
			{
				$title_id_to_keep[ $r['conf_title_title'] ] = $r['conf_title_id'];
			}
		}
		
		if ( count( $title_id_to_delete ) )
		{
			$this->install->ipsclass->DB->simple_exec_query( array( 'delete' => 'conf_settings_titles', 'where' => 'conf_title_id IN ('.implode( ',', $title_id_to_delete ).')' ) );
		}
		
		$title_deleted_count = intval( count($title_id_to_delete) );
		
		//-----------------------------------------
		// Update this parachat component
		//-----------------------------------------
		
		if ( $this->install->ipsclass->vars['chat04_account_no'] )
		{
			require_once( ROOT_PATH . 'sources/api/api_core.php' );
			require_once( ROOT_PATH . 'sources/api/api_components.php' );
			
			$api           =  new api_components();
			$api->ipsclass =& $this->install->ipsclass;
			
			$fields = array( 'com_enabled'    => 1,
							 'com_menu_data'  => array( 0 => array( 'menu_text'    => 'Chat Settings',
																	'menu_url'     => 'code=chatsettings',
																	'menu_permbit' => 'edit' ) ) );
			
			$api->acp_component_update( 'chatpara', $fields );
		}
		
		$this->install->message = "$title_deleted_count duplicate settings deleted";
		
		if( $this->install->ipsclass->vars['chat04_account_no'] )
		{
			$this->install->message .= "<br /><br />Parachat component updated";
		}
		
		return true;
	}
	

}
	
	
?>