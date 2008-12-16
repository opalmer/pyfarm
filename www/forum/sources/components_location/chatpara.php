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
|   > MODULE LOCATION FILE
|   > Module written by Matt Mecham
|   > Date started: Thu 14th April 2005 (17:25)
|
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/*
+--------------------------------------------------------------------------
|   This module has two functions:
|   get_session_variables: Return the session variables for the class_session functions
|   parse_online_entries: Parses an array from online.php
|   See each function for more information
+--------------------------------------------------------------------------
*/

//-----------------------------------------
// This must always be 'components_location_{component_key}'
//-----------------------------------------

class components_location_chatpara
{
	var $ipsclass;
	
	/*-------------------------------------------------------------------------*/
	// get_session_variables
	// Returns:
	// array( '1_type' => {location type #1} [ char(10) ]
	//        '1_id'   => {location ID #1}   [ int(10)  ]
	//        '2_type' => {location type #2} [ char(10) ]
	//        '2_id'   => {location ID #2}   [ int(10)  ]
	//		  '3_type' => {location type #3} [ char(10) ]
	//        '3_id'   => {location ID #3}   [ int(10)  ]
	//      );
	// All are optional.
	// Use this to populate the 'module_id_*' fields in the session table
	// so you can check in your own scripts it the member is active in your module
	// {variable} can be 30 chrs long and alpha numerical
	// "location" in the sessions table will be the name of the module called
	/*-------------------------------------------------------------------------*/
	
	function get_session_variables()
	{
		return array( '1_type' => 'chat',
					  '1_id'   => 1 );
	}
		
	/*-------------------------------------------------------------------------*/
	// parse_online_entries
	// INPUT: $array IS:
	// $array[ $session_id ] = $session_array;
	// Session array is DB row from ibf_sessions
	// EXPECTED RETURN ------------------------------------
	// $array[ $session_id ]['_parsed'] = 1;
	// $array[ $session_id ]['_url']    = {Location url}
	// $array[ $session_id ]['_text']   = {Location text}
	// $array[ $session_id ] = $session_array...
	//
	// YOU ARE RESPONSIBLE FOR PERMISSION CHECKS. IF THE MEMBER DOESN'T
	// HAVE PERMISSION RETURN '_url'    => $this->ipsclass->base_url,
	// 						  '_text'   => $this->ipsclass->lang['board_index'],
	//						  '_parsed' => 1 { as well as the rest of $session_array }
	/*-------------------------------------------------------------------------*/
	
	function parse_online_entries( $array=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = array();
		
		if ( ! isset( $this->ipsclass->lang['chat_chatpara'] ) )
		{
			$this->ipsclass->load_language( 'lang_chatpara' );
		}
		
		//-----------------------------------------
		// LOOP
		//-----------------------------------------
		
		if ( is_array( $array ) and count( $array ) )
		{
			foreach( $array as $session_id => $session_array )
			{
				if ( $session_array['location_1_type'] == 'chat' )
				{
					$location = $this->ipsclass->base_url.'autocom=chatpara';
					$text     = $this->ipsclass->lang['chat_online'];
				}
				else
				{
					$location = $this->ipsclass->base_url;
					$text     = $this->ipsclass->lang['board_index'];
				}
				
				$return[ $session_id ] = array_merge( $session_array, array( '_url' => $location, '_text' => $text, '_parsed' => 1 ) );
			}
		}
		
		return $return;
	}
	
	

}

?>