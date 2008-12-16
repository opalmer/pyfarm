<?php
/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2006 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|   > $Id$
|   > $Revision: 102 $
|   > $Date: 2005-12-22 10:14:15 +0000 (Thu, 22 Dec 2005) $
+---------------------------------------------------------------------------
|
|   > CONVERGE SOAP: SERVER FUNCTIONS
|   > Script written by Josh Williams, Matt Mecham
|   > Date started: Friday 6th January 2006 (11:28)
|
+---------------------------------------------------------------------------
*/

class Converge_Server
{
   /**
    * Defines the service for WSDL
    * @access Private
    * @var array
    */			
	var $__dispatch_map = array();
	
   /**
    * IPS Global Class
    * @access Private
    * @var object
    */
	var $ipsclass;
	
	/**
	* IPS API SERVER Class
    * @access Private
    * @var object
    */
	var $class_api_server;
	
	/**
	 * Converge_Server::Converge_Server()
	 *
	 * CONSTRUCTOR
	 * 
	 * @return void
	 **/		
	function Converge_Server( & $ipsclass ) 
    {
		//-----------------------------------------
		// Set IPS CLASS
		//-----------------------------------------
		
		$this->ipsclass = $ipsclass;
		
    	//-----------------------------------------
    	// Load allowed methods and build dispatch
		// list
    	//-----------------------------------------
    	
		require_once( ROOT_PATH . 'converge_local/apis/allowed_methods.php' );
		
		if ( is_array( $_CONVERGE_ALLOWED_METHODS ) and count( $_CONVERGE_ALLOWED_METHODS ) )
		{
			foreach( $_CONVERGE_ALLOWED_METHODS as $_method => $_data )
			{
				$this->__dispatch_map[ $_method ] = $_data;
			}
		}
	}
	
	/**
	 * Converge_Server::requestData()
	 *
	 * Returns extra data from this application
	 *
	 * EACH BATCH MUST BE ORDERED BY ID ASC (low to high)
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  int	   $product_id  Product ID
	 * @param  int	   $limit_a		SQL limit a
	 * @param  int	   $limit_b		SQL limit b
	 * @return xml
	 **/	
	function requestData( $auth_key, $product_id, $email_address, $getdata_key )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$auth_key      = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id    = intval( $product_id );
		$email_address = $this->ipsclass->parse_clean_value( $email_address );
		$getdata_key   = $this->ipsclass->parse_clean_value( $getdata_key );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Grab local extension file
			//-----------------------------------------
			
			require_once( ROOT_PATH  . 'converge_local/apis/local_extension.php' );
			$extension = new local_extension( $this->ipsclass );
			
			if ( is_callable( array( $extension, $getdata_key ) ) )
			{
				$data = @call_user_func( array( $extension, $getdata_key), $email_address );
			}
			
			$return = array( 'data' => base64_encode( serialize( $data ) ) );
			
			# return complex data
			$this->class_api_server->api_send_reply( $return );
			exit();
		}
	}
	
	/**
	 * Converge_Server::onMemberDelete()
	 *
	 * Deletes the member.
	 * Keep in mind that the member may not be in the local DB
	 * if they've not yet visited this site.
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * 
	 * @param  int	   $product_id 	  	   			Product ID
	 * @param  string  $auth_key       	   			Authentication Key
	 * @param  string  $multiple_email_addresses	Comma delimited list of email addresses
	 * @return xml
	 **/	
	function onMemberDelete( $auth_key, $product_id, $multiple_email_addresses='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return     = 'FAILED';
		$emails     = explode( ",", $this->ipsclass->DB->add_slashes( $this->ipsclass->parse_clean_value( $multiple_email_addresses ) ) );
		$member_ids = array();
		$auth_key   = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id = intval( $product_id );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get member IDs
			//-----------------------------------------
			
			$this->ipsclass->DB->build_query( array( 'select' => 'id',
													 'from'   => 'members',
													 'where'  => "email IN ('" . implode( "','", $emails ) . "')" ) );
			
			$this->ipsclass->DB->exec_query();
			
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$member_ids[ $row['id'] ] = $row['id'];
			}
			
			//-----------------------------------------
			// Remove the members
			//-----------------------------------------
			
			if ( count( $member_ids ) )
			{
				//-----------------------------------------
				// Get the member class
				//-----------------------------------------
				
				require_once( ROOT_PATH . "sources/action_admin/member.php" );
				$lib           =  new ad_member();
				$lib->ipsclass =& $this->ipsclass;
				
				# Set up
				$this->ipsclass->member['mgroup'] = $this->ipsclass->vars['admin_group'];
				
				$lib->member_delete_do( $member_ids );
			}
			
			//-----------------------------------------
			// return
			//-----------------------------------------
			
			$return = 'SUCCESS';
		
			$this->class_api_server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::onPasswordChange()
	 *
	 * handles new password change
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * 
	 * @param  int	   $product_id 	  	   		Product ID
	 * @param  string  $auth_key       	   		Authentication Key
	 * @param  string  $email_address  	    	Email address
	 * @param  string  $md5_once_password		Plain text password hashed by MD5
	 * @return xml
	 **/	
	function onPasswordChange( $auth_key, $product_id, $email_address, $md5_once_password )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id        = intval( $product_id );
		$email_address	   = $this->ipsclass->parse_clean_value( $email_address );
		$md5_once_password = $this->ipsclass->txt_md5_clean( $md5_once_password );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Update: CONVERGE
			//-----------------------------------------

			$salt     = $this->ipsclass->converge->generate_password_salt(5);
			$passhash = $this->ipsclass->converge->generate_compiled_passhash( $salt, $md5_once_password );

			$converge = array( 
							   'converge_pass_hash' => $passhash,
							   'converge_pass_salt' => str_replace( '\\', "\\\\", $salt )
							 );

			$this->ipsclass->DB->do_update( 'members_converge', $converge, "converge_email='" . $this->ipsclass->DB->add_slashes( $email_address )  . "'" );
			
			$return = 'SUCCESS';
		
			$this->class_api_server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::onEmailChange()
	 *
	 * Updates the local app's DB
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * 
	 * @param  int	   $product_id 	  	   		Product ID
	 * @param  string  $auth_key       	   		Authentication Key
	 * @param  string  $old_email_address  	    Existing email address
	 * @param  string  $new_email_address  		NEW email address to change
	 * @return xml
	 **/	
	function onEmailChange( $auth_key, $product_id, $old_email_address, $new_email_address )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id        = intval( $product_id );
		$old_email_address = $this->ipsclass->parse_clean_value( $old_email_address );
		$new_email_address = $this->ipsclass->parse_clean_value( $new_email_address );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "email='" . $this->ipsclass->DB->add_slashes( $old_email_address ) . "'" ) );
																	
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			if ( $old_email_address AND $new_email_address )
			{
				$this->ipsclass->DB->do_update( 'members_converge', array( 'converge_email' => $new_email_address ), "converge_email='" . $this->ipsclass->DB->add_slashes( $old_email_address ) . "'" );
				
				$this->ipsclass->DB->do_update( 'members'         , array( 'email'          => $new_email_address ), "email='"          . $this->ipsclass->DB->add_slashes( $old_email_address ) . "'" );
				
				//-----------------------------------------
				// Update member's username?
				// This happens when a converge member is
				// created
				//-----------------------------------------
				
				if ( $member['email'] == $old_email_address )
				{
					$this->ipsclass->DB->do_update( 'members', array( 'name' => $new_email_address ), "id='" . $member['id'] . "'" );
				}
				
				$return = 'SUCCESS';
			}
		
			$this->class_api_server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => $return ) );
			exit();
		}
	}
	
	
	/**
	 * Converge_Server::importMembers()
	 *
	 * Returns a batch of members to import
	 * Important!
	 * Each member row must return the following:
	 * - email_address
	 * - pass_salt (5 chr salt)
	 * - password  (md5 hash of: md5( md5( $salt ) . md5( $raw_pass ) );
	 * - ip_address (optional)
	 * - join_date (optional)
	 *
	 * EACH BATCH MUST BE ORDERED BY ID ASC (low to high)
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  int	   $product_id  Product ID
	 * @param  int	   $limit_a		SQL limit a
	 * @param  int	   $limit_b		SQL limit b
	 * @return xml
	 **/	
	function importMembers( $auth_key, $product_id, $limit_a, $limit_b )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key   = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id = intval( $product_id );
		$limit_a    = intval( $limit_a );
		$limit_b    = intval( $limit_b );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// INIT
			//-----------------------------------------
			
			$members = array();
			$done    = 0;
			
			//-----------------------------------------
			// Get Data
			//-----------------------------------------
			
			/* - This causes all sorts of mass hysteria and mysql madness - no index gets properly used and server will crash
				 Let's just not send the IP for now.  We can worry about this later if it's needed, but converge doesn't use it.
			$this->ipsclass->DB->build_query( array( 'select' 	=> 'c.*',
													 'from'   	=> array( 'members_converge' => 'c' ),
													 'order'  	=> 'c.converge_id ASC',
													 'limit'  	=> array( $limit_a, $limit_b ),
													 'add_join'	=> array(
													 					array(
													 						'type'		=> 'left',
													 						'select'	=> 'm.ip_address',
													 						'where'		=> 'm.email=c.converge_email',
													 						'from'		=> array( 'members' => 'm' ),
													 					)
													 				)
											) 		);*/

			$this->ipsclass->DB->build_query( array( 'select' 	=> '*',
													 'from'   	=> 'members_converge',
													 'order'  	=> 'converge_id ASC',
													 'limit'  	=> array( $limit_a, $limit_b ),
											) 		);
													
			$this->ipsclass->DB->exec_query();
			
			
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$members[ $row['converge_id'] ] = array( 'email_address' => $row['converge_email'],
														 'pass_salt'     => $row['converge_pass_salt'],
														 'password'      => $row['converge_pass_hash'],
														 'ip_address'	 => $row['ip_address'],
														 'join_date'     => $row['converge_joined'] );
			}
			
			if ( ! count( $members ) )
			{
				$done = 1;
			}
			
			$return = array( 'complete' => $done,
							 'members'  => $members );
			
			# return complex data
			$this->class_api_server->api_send_reply( $return, 1 );
			exit();
		}
	}
	
	/**
	 * Converge_Server::getMembersInfo()
	 *
	 * IP.Converge uses this to gather how many users the local application has,
	 * and the last ID entered into the local application’s member table.
	 *
	 * Expected repsonse:
	 * count   => The number of users
	 * last_id => The last ID
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  int	  $product_id 	Product ID
	 * @return xml
	 **/	
	function getMembersInfo( $auth_key, $product_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key   = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id = intval( $product_id );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get Data
			//-----------------------------------------
			
			$member_count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count',
																			  'from'   => 'members' ) );
																			  
			$member_last  = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'MAX(id) as max',
																			  'from'   => 'members' ) );
			

			$this->class_api_server->api_send_reply( array( 'count'   => intval( $member_count['count'] ),
			 												'last_id' => intval( $member_last['max'] ) ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::convergeLogOut()
	 *
	 * Logs in the member out of local application
	 *
	 * This will return a param "response" with either
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 *
	 * @param  string  $auth_key       Authentication Key
	 * @param  int	   $product_id 	   Product ID
	 * @param  string  $email_address  Email address of user logged in
	 * @return xml
	 **/
	function convergeLogOut( $auth_key, $product_id, $email_address='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key      = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id    = intval( $product_id );
		$email_address = $this->ipsclass->parse_clean_value( $email_address );
		$update        = array();
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Get member
			//-----------------------------------------
			
			$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		'from'   => 'members',
																		'where'  => "email='" . $this->ipsclass->DB->add_slashes( $email_address ) . "'" ) );
			
			//-----------------------------------------
			// If we've got a member, delete their session
			// and change the log in key so that the members
			// auto-log in cookies won't work.
			//-----------------------------------------
			
			if ( $member['id'] )
			{
				$update['member_login_key'] = $this->ipsclass->converge->generate_auto_log_in_key();
				$update['login_anonymous']  = '0&0';
				$update['last_visit']       = time();
				$update['last_activity']    = time();
				
				$this->ipsclass->DB->do_update( 'members', $update, 'id=' . $member['id'] );
				
				//-----------------------------------------
				// Delete session
				//-----------------------------------------
				
				$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'sessions',
																  'where'  => 'member_id='.$member['id'] ) );
			}
			
			//-----------------------------------------
			// Add cookies
			//-----------------------------------------
			
			$this->class_api_server->api_add_cookie_data( array( 'name'   => $this->ipsclass->vars['cookie_id'] . 'member_id',
														  		 'value'  => 0,
														  		 'path'   => $this->ipsclass->vars['cookie_path'],
														  		 'domain' => $this->ipsclass->vars['cookie_domain'],
														  		 'sticky' => 1 ) );
														
			$this->class_api_server->api_add_cookie_data( array( 'name'   => $this->ipsclass->vars['cookie_id'] . 'pass_hash',
														  		 'value'  => 0,
														  		 'path'   => $this->ipsclass->vars['cookie_path'],
														  		 'domain' => $this->ipsclass->vars['cookie_domain'],
														  		 'sticky' => 1 ) );
														
			$this->class_api_server->api_add_cookie_data( array( 'name'   => $this->ipsclass->vars['cookie_id'] . 'session_id',
														  		 'value'  => 0,
														  		 'path'   => $this->ipsclass->vars['cookie_path'],
														  		 'domain' => $this->ipsclass->vars['cookie_domain'],
														  		 'sticky' => 0 ) );
														
			$this->class_api_server->api_send_reply( array( 'complete'   => 1,
			 												'response'   => 'SUCCESS' ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::convergeLogIn()
	 *
	 * Logs in the member to the local application
	 *
	 * This must return
	 * - complete   [ All done.. ]
	 * - session_id [ Session ID created ]*
	 * - member_id  [ Member's log in ID / email ]
	 * - log_in_key [ Member's log in key or password ]
	 * -- RESPONSE
	 * - FAILED    		 (Unknown failure)
	 * - SUCCESS    	 (Added OK)
	 *
	 * The session key and password/log in key will be posted to
	 * this apps handshake API so that the app can return cookies.
	 *
	 * @param  int	   $product_id 	  	   Product ID
	 * @param  string  $auth_key       	   Authentication Key
	 * @param  string  $email_address  	   Email address of user logged in
	 * @param  string  $md5_once_password  The plain text password, hashed once
	 * @param  string  $ip_address  	   IP Address of registree
	 * @param  string  $unix_join_date     The member's join date in unix format
	 * @param  string  $timezone     	   The member's timezone
	 * @param  string  $dst_autocorrect    The member's DST autocorrect settings
	 * @return xml
	 **/
	function convergeLogIn( $auth_key, $product_id, $email_address='', $md5_once_password='', $ip_address='', $unix_join_date='', $timezone=0, $dst_autocorrect=0, $extra_data='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$auth_key          = $this->ipsclass->txt_md5_clean( $auth_key );
		$product_id        = intval( $product_id );
		$email_address     = $this->ipsclass->parse_clean_value( $email_address );
		$md5_once_password = $this->ipsclass->txt_md5_clean( $md5_once_password );
		$ip_address        = $this->ipsclass->parse_clean_value( $ip_address );
		$unix_join_date    = intval( $unix_join_date );
		$timezone          = intval( $timezone );
		$dst_autocorrect   = intval( $dst_autocorrect );
		$extra_data        = $this->ipsclass->parse_clean_value( $extra_data );
		$return            = 'FAILED';
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $auth_key, $product_id ) !== FALSE )
		{
			//-----------------------------------------
			// Extra data?
			//-----------------------------------------
			
			if ( $exta_data )
			{
				$external_data = unserialize( base64_decode( $extra_data ) );
			}
			
			//-----------------------------------------
			// Get member
			//-----------------------------------------
			
			$this->ipsclass->member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																						'from'   => 'members',
																						'where'  => "email='" . $this->ipsclass->DB->add_slashes( $email_address ) . "'" ) );
			
			//-----------------------------------------
			// No such user? Create one!
			// FAIL SAFE
			//-----------------------------------------
			
			if ( ! $this->ipsclass->member['id'] )
			{
				$unix_join_date    = $unix_join_date    ? $unix_join_date    : time();
				$md5_once_password = $md5_once_password ? $md5_once_password : md5( $email_address . $unix_join_date . uniqid( microtime() ) );
				$ip_address        = $ip_address        ? $ip_address        : '127.0.0.1';
				
				$this->ipsclass->member = $this->__create_user_account( $email_address, $md5_once_password, $ip_address, $unix_join_date, $timezone, $dst_autocorrect );
				$return = 'SUCCESS';
			}
			else
			{
				$return = 'SUCCESS';
			}
			
			//-----------------------------------------
			// Start session
			//-----------------------------------------
			
			$session = $this->__create_user_session( $this->ipsclass->member );
			
			//-----------------------------------------
			// Add cookies
			//-----------------------------------------
			
			$this->class_api_server->api_add_cookie_data( array( 'name'   => $this->ipsclass->vars['cookie_id'] . 'member_id',
														  		 'value'  => $session['id'],
														  		 'path'   => $this->ipsclass->vars['cookie_path'],
														  		 'domain' => $this->ipsclass->vars['cookie_domain'],
														  		 'sticky' => 1 ) );
														
			$this->class_api_server->api_add_cookie_data( array( 'name'   => $this->ipsclass->vars['cookie_id'] . 'pass_hash',
														  		 'value'  => $session['member_login_key'],
														  		 'path'   => $this->ipsclass->vars['cookie_path'],
														  		 'domain' => $this->ipsclass->vars['cookie_domain'],
														  		 'sticky' => 1 ) );
														
			$this->class_api_server->api_add_cookie_data( array( 'name'   => $this->ipsclass->vars['cookie_id'] . 'session_id',
														  		 'value'  => $session['_session_id'],
														  		 'path'   => $this->ipsclass->vars['cookie_path'],
														  		 'domain' => $this->ipsclass->vars['cookie_domain'],
														  		 'sticky' => 0 ) );
			
			$this->class_api_server->api_send_reply( array( 'complete'   => 1,
															'response'   => $return,
			 												'session_id' => $session['_session_id'],
															'member_id'  => $session['id'],
			 												'log_in_key' => $session['member_login_key'] ) );
			exit();
		}
	}
	
	/**
	 * Converge_Server::__create_user_session()
	 *
	 * Has to return at least the member ID, member log in key and session ID
	 *
	 * 
	 * @param  array  $member  	Array of member information
	 * @return array  $session	Session information
	 **/
	function __create_user_session( $member )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$update = array();
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------

		if ( $this->ipsclass->vars['login_change_key'] OR ! $member['member_login_key'] )
		{
			$update['member_login_key'] = $this->ipsclass->converge->generate_auto_log_in_key();
		}
		
		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$update['login_anonymous'] = '0&1';
		
		//-----------------------------------------
		// Update member?
		//-----------------------------------------
		
		if ( is_array( $update ) and count( $update ) )
		{
			$this->ipsclass->DB->do_update( 'members', $update, 'id=' . $member['id'] );
		}
		
		//-----------------------------------------
		// Still here? Create a new session
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/class_session.php' );
		$session           =  new session();
		$session->ipsclass =& $this->ipsclass;
		$session->time_now =  time();
		$session->member   =  $member;
		
		$session->create_member_session();
		
		$session->member['_session_id'] = $session->session_id;
		
		return $session->member;
	}
	
	/**
	 * Converge_Server::__create_user_account()
	 *
	 * Routine to create a local user account
	 *
	 * 
	 * @param  string  $email_address  	   Email address of user logged in
	 * @param  string  $md5_once_password  The plain text password, hashed once
	 * @param  string  $ip_address  	   IP Address of registree
	 * @param  string  $unix_join_date     The member's join date in unix format
	 * @param  string  $timezone     	   The member's timezone
	 * @param  string  $dst_autocorrect    The member's DST autocorrect settings
	 * @return array   $member			   Newly created member array
	 **/
	function __create_user_account( $email_address='', $md5_once_password, $ip_address, $unix_join_date, $timezone=0, $dst_autocorrect=0 )
	{
		//-----------------------------------------
		// Check to make sure there's not already
		// a member registered.
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "email='" . $this->ipsclass->DB->add_slashes( $email_address ) . "'" ) );
		
		if ( $member['id'] )
		{
			return $member;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$unix_join_date = $unix_join_date ? $unix_join_date : time();
		$ip_address     = $ip_address     ? $ip_address     : $this->ipsclass->ip_address;
		
		//-----------------------------------------
		// Grab module..
		//-----------------------------------------
		
		require( ROOT_PATH . "sources/loginauth/login_core.php" );
		$login_core           = new login_core();
		$login_core->ipsclass = $this->ipsclass;
		
		//-----------------------------------------
		// Create member
		//-----------------------------------------
 		
		$member = $login_core->_create_local_member( $email_address, $md5_once_password, $email_address, $unix_join_date, $ip_address );
		
		return $member;
	}
	
	/**
	 * Converge_Server::__authenticate()
	 *
	 * Checks to see if the request is allowed
	 * 
	 * @param  string $key    		Authenticate Key
	 * @param  string $product_id   Product ID
	 * @access Private	 
	 * @return string         Error message, if any
	 **/	
	function __authenticate( $key, $product_id )
	{
		//-----------------------------------------
		// Check converge users API DB
		//-----------------------------------------
		
		$info = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																  'from'   => 'converge_local',
																  'where'  => "converge_product_id=" . intval($product_id) . " AND converge_active=1 AND converge_api_code='{$key}'" ) );
	
		//-----------------------------------------
		// Got a user?
		//-----------------------------------------
		
		if ( ! $info['converge_api_code'] )
		{
			$this->class_api_server->api_send_error( 100, 'Unauthorized User' );
			return FALSE;
		}
		else if ( CVG_IP_MATCH AND ( $this->ipsclass->my_getenv('REMOTE_ADDR') != $info['converge_ip_address'] ) )
		{
			$this->class_api_server->api_send_error( 101, 'IP ADDRESS not registered' );
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	

}
?>