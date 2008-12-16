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
|   > LOG IN MODULE: LDAP (ACTIVE DIRECTORY)
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
	# Work
	var $connection_id;
	var $result;
	var $bind_id;
	var $fields;
	var $dn;
	
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
		// Get LDAP connection
		//-----------------------------------------
		
		$this->auth_errors = array();
		
		$this->_ldap_connect();
		
		//-----------------------------------------
		// OK?
		//-----------------------------------------
		
		if ( count($this->auth_errors) )
		{
			return FALSE;
		}
		
		// IPB replaces these characters, however they
		// may be allowed by the LDAP server as a
		// requirement for passwords.  Let's send the
		// actual raw password
		
		// Tested succesfully ticket 254173

		$password = html_entity_decode($password, ENT_QUOTES);
		$html_entities = array("&#33;", "&#036;", "&#092;");
		$replacement_char = array("!", "$", "\\");
		$password = str_replace($html_entities, $replacement_char, $password);
		
		//-----------------------------------------
		// Add suffix
		//-----------------------------------------
		
		if ( $this->login_conf['ldap_username_suffix'] )
		{
			$real_username = $username.$this->login_conf['ldap_username_suffix'];
		}
		else
		{
			$real_username = $username;
		}
		
		//-----------------------------------------
		// Add filter
		// - Donated by iCCT - thx!
		// concatenate the search for uid with the filter
		// string if the string is not empty - logical AND
		// as we are searching for uid match
		//-----------------------------------------
		
		if ( $this->login_conf['ldap_filter'] )
		{
			$filter = '(&(' . $this->login_conf['ldap_uid_field']. '=' . $real_username . ')(' . $this->login_conf['ldap_filter'] . '))';
		}
		else
		{
			$filter = $this->login_conf['ldap_uid_field']. '=' . $real_username;
		}		
		
		//-----------------------------------------
		// Throw search to bind
		//-----------------------------------------
		
		$search = @ldap_search( $this->connection_id,
								$this->login_conf['ldap_base_dn'],
								$filter,
								array( $this->login_conf['ldap_uid_field'] )
							  );
		//$result = ldap_get_entries($this->connection_id, $search);		print "<pre>"; print_r( $result );	 
		
		$this->result = @ldap_first_entry( $this->connection_id, $search);
		
		if ( ! $this->result )
		{
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		
		$this->fields = @ldap_get_attributes( $this->connection_id, $this->result );
		
		$this->dn     = @ldap_get_dn( $this->connection_id, $this->result );
		
		//-----------------------------------------
		// Got something?
		//-----------------------------------------
		
		if ( is_array( $this->fields ) AND count( $this->fields ) > 0 )
		{
			if ( ! $this->login_conf['ldap_user_requires_pass'] )
			{
				$real_password = "";
			}
			else
			{
				$real_password = $password;
			}
			
			//-----------------------------------------
			// Test bind
			//-----------------------------------------
			
			if ( @ldap_bind( $this->connection_id, $this->dn, $real_password) )
			{
				$this->_load_member( $username );
				
				if ( $this->member['id'] )
				{
					$this->return_code = 'SUCCESS';
				}
				else
				{
					//-----------------------------------------
					// Got no member - but auth passed - create?
					//-----------------------------------------
					
					if ( $this->allow_create )
					{
						$this->create_local_member( $username, $password );
					}
					else
					{
						$this->return_code = 'NO_USER';
					}
				}
			}
			else
			{
				$this->return_code = 'WRONG_AUTH';
			}
		}
		
		$this->_ldap_disconnect();
		
		return $this->return_code;
	}
	
	/*-------------------------------------------------------------------------*/
	// Load member from DB
	/*-------------------------------------------------------------------------*/
	
	function _load_member( $username )
	{
		$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => strtolower($username) ) );
		$this->ipsclass->DB->cache_exec_query();
	
		$this->member = $this->ipsclass->DB->fetch_row();
	}
	
	/*-------------------------------------------------------------------------*/
	// Get LDAP connection
	/*-------------------------------------------------------------------------*/
	
	function _ldap_connect()
	{
		//-----------------------------------------
		// LDAP compiled in PHP?
		//-----------------------------------------
		
		if ( ! extension_loaded('ldap') )
		{
			$this->auth_errors[] = 'LDAP extension not available';
			return;
		}
		
		//-----------------------------------------
		// Get connection
		//-----------------------------------------
		
		if ( $this->login_conf['ldap_port'] )
		{
			$this->connection_id = ldap_connect( $this->login_conf['ldap_server'], $this->login_conf['ldap_port'] );
		}
		else
		{
			$this->connection_id = ldap_connect( $this->login_conf['ldap_server'] );
		}
		
		if ( ! $this->connection_id  )
		{
			$this->auth_errors[] = 'LDAP could not connect';
			return;
		}
		
		//-----------------------------------------
		// Server version
		//-----------------------------------------
		
		if ( $this->login_conf['ldap_server_version'] )
		{
			@ldap_set_option($this->connection_id, LDAP_OPT_PROTOCOL_VERSION, $this->login_conf['ldap_server_version']);
		}
		
		//-----------------------------------------
		// Win2K3 AD with root DN
		//-----------------------------------------
		
		if ( $this->login_conf['ldap_opt_referrals'] )
		{
			@ldap_set_option($this->connection_id, LDAP_OPT_REFERRALS, true);
		}
		
		//-----------------------------------------
		// Bind
		//-----------------------------------------
		
		if ( $this->login_conf['ldap_server_username'] AND $this->login_conf['ldap_server_password'] )
		{
			$this->bind_id = @ldap_bind( $this->connection_id, $this->login_conf['ldap_server_username'], $this->login_conf['ldap_server_password'] );
		}
		else
		{
			# Anonymous bind
			
			$this->bind_id = @ldap_bind( $this->connection_id );
		}
		
		if ( ! $this->bind_id )
		{
			$this->auth_errors[] = 'LDAP could not bind to the server';
			return;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// LDAP disconnection
	/*-------------------------------------------------------------------------*/
	
	function _ldap_disconnect()
	{
		@ldap_close( $this->connection_id );
	}
	
}

?>