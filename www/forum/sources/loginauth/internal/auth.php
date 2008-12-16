<?php
/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > LOG IN MODULE: INTERNAL (IPB AUTH)
|   > Script written by Matt Mecham
|   > Date started: 12:25 Fri. 4th February 2005 (AD)
|
+---------------------------------------------------------------------------
| NOTES:
| This module is part of the authentication suite of modules. It's designed
| to enable different types of authentication.
|
| RETURN CODES
| 'ERROR': Error, check array: $class->auth_errors
| 'NO_USER': No user found in LOCAL record set but auth passed in REMOTE dir
| 'WRONG_AUTH': Wrong password or username
| 'SUCCESS': Success, user and password matched
|
+---------------------------------------------------------------------------
| EXAMPLE USAGE
|
| $class = new login_method();
| $class->is_admin_auth = 0; // Boolean (0,1) Use different queries if desired
|							 // if logging into CP.
| $class->allow_create = 0;
| // $allow_create. Boolean flag (0,1) to tell the module whether its allowed
| // to create a member in the IPS product's database if the user passed authentication
| // but don't exist in the IPS product's database. Optional.
|
| $return_code = $class->authenticate( $username, $plain_text_password );
|
| if ( $return_code == 'SUCCESS' )
| {
|     print $class->member['member_name'];
| }
| else
| {
| 	  print "NO USER";
| }
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_method extends login_core
{
	# Globals
	var $ipsclass;
	
	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/
	
	function login_method()
	{
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Authentication
	/*-------------------------------------------------------------------------*/
	
	function authenticate( $username, $password )
	{
		$this->auth_local( $username, $password );
		return;
	}
}

?>