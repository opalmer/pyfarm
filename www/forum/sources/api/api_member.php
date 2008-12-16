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
|   > $Date: 2006-05-25 10:15:22 -0400 (Thu, 25 May 2006) $
|   > $Revision: 278 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > API: Members
|   > Module written by Matt Mecham
|   > Date started: Wed 27th June 2007
|
+--------------------------------------------------------------------------
*/

/**
* API: Member
*
* EXAMPLE USAGE
* <code>
* $api =  new api_member();
* # Optional - if $ipsclass is not passed, it'll init
* $api->ipsclass =& $this->ipsclass;
* $api->api_init();
* # Check for a member by email address
* $boolean = $api->check_for_member( 'email', 'foo@bar.com' );
* </code>
*
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
* API: Tasks
*
* This class deals with all available task insertion functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.2
* @since		2.3.2
*/
class api_member extends api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	//var $ipsclass;
	
	/*-------------------------------------------------------------------------*/
	// Fetch Member
	/*-------------------------------------------------------------------------*/
	/**
	* Return a member
	*
	* @param	string  Type of field to check
	* @param	string	String to check
	* @return 	boolean;
	*/
	function get_member( $type='email', $search_string='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$query  = '';
		$return = array();
		$skippy = array( 'legacy_password', 'member_login_key', 'member_login_key_expire', 'failed_logins', 'failed_login_count' );
		
		//-----------------------------------------
		// Decide what we're doing...
		//-----------------------------------------
		
		switch( $type )
		{
			case 'email':
			case 'emailaddress':
			case 'email_address':
				$email_address = $this->ipsclass->clean_email( $this->ipsclass->parse_clean_value( strtolower( $search_string ) ) );
				
				if ( $email_address )
				{
					$query = "m.email='" . $email_address . "'";
				}
				else
				{
					return FALSE;
				}
			break;
			case 'id':
				$query = "m.id='" . intval( $search_string ) . "'";
			break;
			case 'name':
			case 'username':
			case 'loginname':
				$query = "m.members_l_username='" . $this->ipsclass->parse_clean_value( strtolower( $search_string ) ) . "'";
			break;
			case 'displayname':
			case 'members_display_name':
				$query = "m.members_l_display_name='" . $this->ipsclass->parse_clean_value( strtolower( $search_string ) ) . "'";
			break;
		}
		
		//-----------------------------------------
		// Search
		//-----------------------------------------
		
		if ( $query )
		{
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
																					  'type'   => 'left' ) ) ) );
																					
			$this->ipsclass->DB->exec_query();
			
			$member = $this->ipsclass->DB->fetch_row();
																		
			if ( $member['id'] )
			{
				//-----------------------------------------
				// Copy across *most* details...
				//-----------------------------------------
				
				foreach( $member as $k => $v )
				{
					if ( ! in_array( $k, $skippy ) )
					{
						$return[ $k ] = $v;
					}
				}
				
				//-----------------------------------------
				// Format some stuff?
				//-----------------------------------------
				
				$return['_avatar'] = $this->ipsclass->get_avatar( $member['avatar_location'], 1, $member['avatar_size'], $member['avatar_type'] );
				$return['_joined'] = $this->ipsclass->get_date( $member['joined'], 'JOINED' );
				
				//-----------------------------------------
				// Check URL
				//-----------------------------------------

				$this->ipsclass->vars['img_url'] = ( ! $this->ipsclass->vars['img_url'] ) ?  $this->ipsclass->vars['board_url'] . '/style_images/' . $this->ipsclass->skin['_imagedir'] : $this->ipsclass->vars['img_url'];

				//-----------------------------------------
				// Main photo
				//-----------------------------------------

				if ( ! $return['pp_main_photo'] )
				{
					$return['pp_main_photo']  = $this->ipsclass->vars['img_url'].'/folder_profile_portal/pp-blank-large.png';;
					$return['pp_main_width']  = 150;
					$return['pp_main_height'] = 150;
					$return['_has_photo']     = 0;
				}
				else
				{
					$return['pp_main_photo'] = $this->ipsclass->vars['upload_url'] . '/' . $member['pp_main_photo'];
					$return['_has_photo']    = 1;
				}

				//-----------------------------------------
				// Thumbie
				//-----------------------------------------

				if ( ! $member['pp_thumb_photo'] )
				{
					if( $member['_has_photo'] )
					{
						$return['pp_thumb_photo']  = $member['pp_main_photo'];
					}
					else
					{
						$return['pp_thumb_photo']  = $this->ipsclass->vars['img_url'].'/folder_profile_portal/pp-blank-thumb.png';
					}

					$return['pp_thumb_width']  = 50;
					$return['pp_thumb_height'] = 50;
				}
				else
				{
					$return['pp_thumb_photo'] = $this->ipsclass->vars['upload_url'] . '/' . $member['pp_thumb_photo'];
				}

				//-----------------------------------------
				// Mini
				//-----------------------------------------

				$_data = $this->ipsclass->scale_image( array( 'max_height' => 25, 'max_width' => 25, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );

				$return['pp_mini_photo']  = $member['pp_thumb_photo'];
				$return['pp_mini_width']  = $_data['img_width'];
				$return['pp_mini_height'] = $_data['img_height'];
				
				return $return;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Check if member exists
	/*-------------------------------------------------------------------------*/
	/**
	* Checks to see if a member exists
	*
	* @param	string  Type of field to check
	* @param	string	String to check
	* @return 	boolean;
	*/
	function check_for_member( $type='email', $search_string='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$query = '';
		
		//-----------------------------------------
		// Decide what we're doing...
		//-----------------------------------------
		
		switch( $type )
		{
			case 'email':
			case 'emailaddress':
			case 'email_address':
				$email_address = $this->ipsclass->clean_email( $this->ipsclass->parse_clean_value( strtolower( $search_string ) ) );
				
				if ( $email_address )
				{
					$query = "email='" . $email_address . "'";
				}
				else
				{
					return FALSE;
				}
			break;
			case 'id':
				$query = "id='" . intval( $search_string ) . "'";
			break;
			case 'name':
			case 'username':
			case 'loginname':
				$query = "members_l_username='" . $this->ipsclass->parse_clean_value( strtolower( $search_string ) ) . "'";
			break;
			case 'displayname':
			case 'members_display_name':
				$query = "members_l_display_name='" . $this->ipsclass->parse_clean_value( strtolower( $search_string ) ) . "'";
			break;
		}
		
		//-----------------------------------------
		// Search
		//-----------------------------------------
		
		if ( $query )
		{
			$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'id',
																		'from'   => 'members',
																		'where'  => $query ) );
																		
			if ( $member['id'] )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
}


?>