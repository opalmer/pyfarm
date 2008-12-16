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
|   > $Date: 2005-10-10 14:08:54 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 23 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > API: Languages
|   > Module written by Matt Mecham
|   > Date started: Wednesday 30th November 2005 (11:40)
|
+--------------------------------------------------------------------------
*/

/**
* API: Forums
*
* EXAMPLE USAGE
* <code>
* To follow
* </code>
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'api_core' ) )
{
	require_once( IPS_API_PATH.'/api_core.php' );
}

/**
* API: Languages
*
* This class deals with all available language functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/
class api_forums extends api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	//var $ipsclass;
	
	
	/*-------------------------------------------------------------------------*/
	// Returns a forum jump option list
	/*-------------------------------------------------------------------------*/
	/**
	* Returns a forum jump list WITHOUT the SELECT tag
	* NOTE: Returns ALL forums regardless of permission as
	* if viewed from the ACP.
	*
	* @param	array 	Array of selected IDs
	* @return   string	HTML <option> list of forums;
	*/
	function return_forum_jump_option_list( $selected=array(), $view_as_guest=0 )
	{
		//-----------------------------------------
		// Load up permissions...
		//-----------------------------------------
		
		if ( $view_as_guest )
		{
			$this->ipsclass->perm_id_array = explode( ',', $this->ipsclass->create_perms_from_group( $this->ipsclass->vars['guest_group'] ) );
			$this->ipsclass->forums->strip_invisible = 1;
		}
		
		//-----------------------------------------
		// Get forums...
		//-----------------------------------------
	
		$this->ipsclass->forums->forums_init();
		
		$content = $this->ipsclass->forums->forums_forum_jump( 0, 0, 1 );
		
		//-----------------------------------------
		// Splice in selected IDs
		//-----------------------------------------
		
		if ( is_array( $selected ) and count( $selected ) )
		{
			foreach( $selected as $id )
			{
				$content = preg_replace( "#value=([\"'])($id)[\"']#si", "value=\\1\\2\\1 selected='selected'", $content );
			}
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		return $content;
	}
	
	/*-------------------------------------------------------------------------*/
	// Returns all forum data
	/*-------------------------------------------------------------------------*/
	/**
	* Return forum data
	* NOTE: Returns ALL forums regardless of permission as
	* if viewed from the ACP.
	*
	* @param	array 	Array of forum IDs (Optional, if blank it will return all forums)
	* @return   string	HTML <option> list of forums;
	*/
	function return_forum_data( $forum_ids=array(), $view_as_guest=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$forums = array();
		
		//-----------------------------------------
		// Load up permissions...
		//-----------------------------------------
		
		if ( $view_as_guest )
		{
			$this->ipsclass->perm_id_array = explode( ',', $this->ipsclass->create_perms_from_group( $this->ipsclass->vars['guest_group'] ) );
			$this->ipsclass->forums->strip_invisible = 1;
		}
		
		//-----------------------------------------
		// Get forums...
		//-----------------------------------------
	
		$this->ipsclass->forums->forums_init();
		
		foreach( $this->ipsclass->forums->forum_by_id as $id => $data )
		{
			if ( is_array( $forum_ids ) AND count( $forum_ids ) )
			{
				if ( in_array( $id, $forum_ids ) )
				{
					if ( $view_as_guest )
					{
						if ( ! $this->ipsclass->forums->forums_quick_check_access( $id ) )
						{
							$forums[] = $data;
						}
					}
					else
					{
						$forums[] = $data;
					}
				}
			}
			else
			{
				if ( $view_as_guest )
				{
					if ( ! $this->ipsclass->forums->forums_quick_check_access( $id ) )
					{
						$forums[] = $data;
					}
				}
				else
				{
					$forums[] = $data;
				}
			}
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		return $forums;
	}
	
	
	
	
	
	
	
	
	
}



?>