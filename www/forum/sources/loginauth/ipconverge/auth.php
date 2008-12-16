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
	
	/**
	* Make admin use different auth?
	* @var int
	*/
	var $allow_admin_login = 1;
	
	/**
	* Is admin log in ?
	* @var int
	*/
	var $is_admin_auth     = 0;
	
	var $api_server;
	
	
	/*-------------------------------------------------------------------------*/
	// Authentication
	/*-------------------------------------------------------------------------*/
	
	function authenticate( $username, $password )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$md5_once_pass = md5( $password );
		
		//-----------------------------------------
		// ADMIN log in?
		//-----------------------------------------
		
		if ( $this->is_admin_auth AND $this->login_method['login_type'] == 'passthrough' )
		{
			// Try local first, so as to not block locally created admins
			$this->admin_auth_local( $username, $password );
			
  			if ( $this->return_code == 'SUCCESS' )
  			{
  				return;
  			}
		}
		
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( KERNEL_PATH . 'class_api_server.php' );
			$this->api_server = new class_api_server();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $username,
						  'md5_once_password' => $md5_once_pass
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->api_send_request( $url, 'convergeAuthenticate', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		else if( $this->api_server->params['response'] != 'SUCCESS' )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		$this->_load_member( $username );
		
		if ( !$this->member['id'] )
		{
			//-----------------------------------------
			// Got no member - but auth passed - create?
			//-----------------------------------------
			
			if ( $this->allow_create )
			{
				$this->create_local_member( $username, $password, $username );
			}
		}
		
		/*$this->ipsclass->DB->build_query( array(
												  'select'   => 'm.*',
												  'from'     => array( 'members' => 'm' ),
												  'where'    => "email='".strtolower($username)."'",
												  'add_join' => array( 0 => array( 'select' => 'g.*',
																				   'from'   => array( 'groups' => 'g' ),
																				   'where'  => 'm.mgroup=g.g_id',
																				   'type'   => 'inner'
																				 )
																	)
										 )     );
												 
		$this->ipsclass->DB->exec_query();

		$this->member      = $this->ipsclass->DB->fetch_row();*/
		
		$this->return_code = $this->api_server->params['response'];
		return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Load member from DB
	/*-------------------------------------------------------------------------*/
	
	function _load_member( $username )
	{
		$this->member = $this->ipsclass->DB->build_and_exec_query( array( 'select' 	=> 'id, name, joined, members_display_name, members_created_remote, email, mgroup, mgroup_others, member_login_key, member_login_key_expire, ip_address, login_anonymous',
																			'from'	=> 'members',
																			'where'	=> "email='" . strtolower($username) . "'"
																)		);

		if( is_array( $this->member ) )
		{
			$this->member = array_merge( $this->member, $this->ipsclass->cache['group_cache'][ $this->member['mgroup'] ] );
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// User Exists Check
	/*-------------------------------------------------------------------------*/
	
	function email_exists_check( $username )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( KERNEL_PATH . 'class_api_server.php' );
			$this->api_server = new class_api_server();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $username,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->api_send_request( $url, 'convergeCheckEmail', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		$this->return_code = $this->api_server->params['response'];
		return;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Change Email
	/*-------------------------------------------------------------------------*/
	
	function change_email( $old_email, $new_email )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( KERNEL_PATH . 'class_api_server.php' );
			$this->api_server = new class_api_server();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $new_email,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->api_send_request( $url, 'convergeCheckEmail', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		if( $this->api_server->params['response'] == 'EMAIL_NOT_IN_USE' )
		{
			//-----------------------------------------
			// Change email
			//-----------------------------------------
			
			$request = array( 'auth_key'          => $converge['converge_api_code'],
							  'product_id'        => $converge['converge_product_id'],
							  'old_email_address' => $old_email,
							  'new_email_address' => $new_email,
							);

			$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

			//-----------------------------------------
			// Send request
			//-----------------------------------------

			$this->api_server->api_send_request( $url, 'convergeChangeEmail', $request );

			//-----------------------------------------
			// Handle errors...
			//-----------------------------------------

			if ( count( $this->api_server->errors ) )
			{
				$this->return_code = 'WRONG_AUTH';
				return;
			}
		}
		
		$this->return_code = $this->api_server->params['response'];
		return;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Change Password
	/*-------------------------------------------------------------------------*/
	
	function change_pass( $email, $new_pass )
	{
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( KERNEL_PATH . 'class_api_server.php' );
			$this->api_server = new class_api_server();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $email,
						  'md5_once_password' => $new_pass,
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->api_send_request( $url, 'convergeChangePassword', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		$this->return_code = $this->api_server->params['response'];
		return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Create Account
	/*-------------------------------------------------------------------------*/
	
	function create_account( $member=array() )
	{
		if( !is_array( $member ) )
		{
			$this->return_code = 'FAIL';
			return;
		}
		
		//-----------------------------------------
		// Get product ID and code from API
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => 'converge_active=1' ) );
																	
		if ( ! $converge['converge_api_code'] )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		//-----------------------------------------
		// Auth against converge...
		//-----------------------------------------
		
		if ( ! is_object( $this->api_server ) )
		{
			require_once( KERNEL_PATH . 'class_api_server.php' );
			$this->api_server = new class_api_server();
		}
		
		$request = array( 'auth_key'          => $converge['converge_api_code'],
						  'product_id'        => $converge['converge_product_id'],
						  'email_address'     => $member['email'],
						);

		$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

		//-----------------------------------------
		// Send request
		//-----------------------------------------
		
		$this->api_server->auth_user = $converge['converge_http_user'];
		$this->api_server->auth_pass = $converge['converge_http_pass'];
		
		$this->api_server->api_send_request( $url, 'convergeCheckEmail', $request );

		//-----------------------------------------
		// Handle errors...
		//-----------------------------------------

		if ( count( $this->api_server->errors ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		if( $this->api_server->params['response'] == 'EMAIL_NOT_IN_USE' )
		{
			$request = array( 'auth_key'          => $converge['converge_api_code'],
							  'product_id'        => $converge['converge_product_id'],
							  'email_address'     => $member['email'],
							  'md5_once_password' => md5( $member['password'] ),
							  'ip_address'        => $member['ip_address'],
							  'unix_join_date'    => $member['joined']
							);

			$url     = $converge['converge_url'] . '/converge_master/converge_server.php';

			//-----------------------------------------
			// Send request
			//-----------------------------------------

			$this->api_server->api_send_request( $url, 'convergeAddMember', $request );

			//-----------------------------------------
			// Handle errors...
			//-----------------------------------------

			if ( count( $this->api_server->errors ) )
			{
				$this->return_details 	= implode( '<br />', $this->api_server->errors );
				$this->return_code 		= $this->api_server->params['response'];
				return;
			}
		}
		
		$this->return_code = $this->api_server->params['response'];
		return;
	}
}

?>