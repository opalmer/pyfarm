<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2006-02-01 18:16:26 +0000 (Wed, 01 Feb 2006) $
|   > $Revision: 132 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > CONVERGE Wrapper script
|   > Script written by Matt Mecham
|   > Date started: 6th March 2006
|
+--------------------------------------------------------------------------
*/

/**
* Main executable wrapper.
*
* Set-up and load module to run
*
* @package	InvisionPowerBoard
* @author   Matt Mecham
* @version	2.1
*/

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'public' );
define( 'IPB_LOAD_SQL'   , 'queries' );

require_once( '../init.php' );

//===========================================================================
// MAIN PROGRAM
//===========================================================================

$INFO = array();

//--------------------------------
// Load our classes
//--------------------------------

require_once ROOT_PATH   . "sources/ipsclass.php";
require_once KERNEL_PATH . "class_converge.php";
require_once ROOT_PATH   . "conf_global.php";

# Initiate super-class
$ipsclass       = new ipsclass();
$ipsclass->vars = $INFO;

//--------------------------------
// The clocks a' tickin'
//--------------------------------
		
$Debug = new Debug;
$Debug->startTimer();

//--------------------------------
// Load the DB driver and such
//--------------------------------

$ipsclass->init_db_connection();

# Turn off shutdown
$ipsclass->DB->obj['use_shutdown'] = 0;

//--------------------------------
//  Set up our vars
//--------------------------------

$ipsclass->parse_incoming();

//--------------------------------
//  Set converge
//--------------------------------

$ipsclass->converge = new class_converge( $ipsclass->DB );

//--------------------------------
// Start off the cache array
//--------------------------------

$ipsclass->cache_array = array('components','banfilters', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'moderators', 'stats', 'languages' );

//===========================================================================
// Get cache...
//===========================================================================

$ipsclass->init_load_cache( $ipsclass->cache_array );

//--------------------------------
// Set debug mode
//--------------------------------

$ipsclass->DB->set_debug_mode( $ipsclass->vars['sql_debug'] == 1 ? intval($_GET['debug']) : 0 );

//--------------------------------
//  Initialize the FUNC
//--------------------------------

$ipsclass->initiate_ipsclass();

//--------------------------------
//  Initialize the FUNC
//--------------------------------

if ( ! $ipsclass->vars['ipconverge_public_enable'] )
{
	@header( "Content-type: text/xml" );
	print"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<methodResponse>
			   <fault>
			      <value>
			         <struct>
			            <member>
			               <name>faultCode</name>
			               <value>
			                  <int>1</int>
			                  </value>
			               </member>
			            <member>
			               <name>faultString</name>
			               <value>
			                  <string>IP.Converge is not enabled from your ACP Control Panel. Log into your IP.Board ACP and visit: Tools &amp; Settings -&gt; IP.Converge Configuration and update &quot;Enable IP.Converge&quot;</string>
			               </value>
			               </member>
			            </struct>
			         </value>
			            </fault>
			   </methodResponse>";
	exit();
}

//===========================================================================
// Define Service
//===========================================================================

require_once( KERNEL_PATH . 'class_api_server.php' );

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$server     = new class_api_server();
$webservice = new handshake_server( $ipsclass );
$webservice->class_api_server =& $server;
$api        = $server->decode_request();

$server->add_object_map( $webservice, 'UTF-8' );

//-----------------------------------------
// Saying "info" or actually doing some
// work? Info is used by converge app to
// ensure this file exists and to grab the
// apps name.
// Codes:
// IPB : Invision Power Board
// IPD : Invision Power Dynamic
// IPN : Invision Power Nexus
//-----------------------------------------

if ( $_REQUEST['info'] )
{
	@header( "Content-type: text/plain" );
	print "<info>\n";
	print "\t<productname>" . htmlspecialchars( $ipsclass->vars['board_name'] ) . "</productname>\n";
	print "\t<productcode>IPB</productcode>\n";
	print "</info>";
	exit();
}
//-----------------------------------------
// Post log in:
// This is hit after a successful converge
// log in has been made. It's up the the local
// app to check the incoming data, and set
// cookies (optional)
//-----------------------------------------
else if ( $_REQUEST['postlogin'] )
{
	//-----------------------------------------
	// INIT
	//-----------------------------------------
	
	$session_id  = addslashes( substr( trim( $_GET['session_id'] ), 0, 32 ) );
	$key         = substr( trim( $_GET['key'] ), 0, 32 );
	$member_id   = intval( $_GET['member_id'] );
	$product_id  = intval( $_GET['product_id'] );
	$set_cookies = intval( $_GET['cookies'] );
	
	//-----------------------------------------
	// Get converge
	//-----------------------------------------
	
	$converge = $ipsclass->DB->build_and_exec_query( array( 'select' => '*',
															'from'   => 'converge_local',
															'where'  => "converge_active=1 AND converge_product_id=".$product_id ) );
	//-----------------------------------------
	// Get member....
	//-----------------------------------------
	
	$session = $ipsclass->DB->build_and_exec_query( array( 'select' => '*',
														   'from'   => 'sessions',
														   'where'  => "id='" . $session_id . "' AND member_id=".$member_id ) );
														
	if ( $session['member_id'] )
	{
		$member = $ipsclass->DB->build_and_exec_query( array( 'select' => '*',
															  'from'   => 'members',
															  'where'  => "id=".$member_id ) );
																	
		if ( md5( $member['member_login_key'] . $converge['converge_api_code'] ) == $key )
		{
			if ( $set_cookies )
			{
				$ipsclass->my_setcookie( "member_id" , $member['id']       , 1 );
				$ipsclass->my_setcookie( "pass_hash" , $member['member_login_key'], 1 );
			}
			
			$ipsclass->my_setcookie( "session_id", $session_id                , -1);
			$ipsclass->stronghold_set_cookie( $member['id'], $member['member_login_key'] );
		}
		
		//-----------------------------------------
		// Update session
		//-----------------------------------------
		
		$ipsclass->DB->do_update( 'sessions', array( 'browser'    => $ipsclass->user_agent,
													 'ip_address' => $ipsclass->ip_address ), "id='" . $session_id . "'" );
	}
	
	//-----------------------------------------
	// Is this a partial member?
	// Not completed their sign in?
	//-----------------------------------------
	
	if ( $member['members_created_remote'] )
	{
		$pmember = $ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id={$member['id']}" ) );
		
		if ( $pmember['partial_member_id'] )
		{
			$ipsclass->boink_it( $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'] . '?act=reg&CODE=complete_login&mid='.$member['id'].'&key='.$pmember['partial_date'] );
			exit();
		}
		else
		{
			//-----------------------------------------
			// Redirect...
			//-----------------------------------------
	
			$ipsclass->boink_it( $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'] );
		}
	}
	else
	{
		//-----------------------------------------
		// Redirect...
		//-----------------------------------------

		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'] );
	}
}
else
{
	$server->get_xml_rpc();
}


exit;


class handshake_server
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
	function handshake_server( & $ipsclass ) 
    {
		//-----------------------------------------
		// Set IPS CLASS
		//-----------------------------------------
		
		$this->ipsclass = $ipsclass;
		
    	//-----------------------------------------
    	// Build dispatch list
    	//-----------------------------------------
    
		$this->__dispatch_map[ 'handshakeStart' ] = array(
														   'in'  => array(
																		'reg_id'           => 'int',
																		'reg_code'         => 'string',
																		'reg_date'         => 'string',
																		'reg_product_id'   => 'int',
																		'converge_url'     => 'string',
																		'acp_email'        => 'string',
																		'acp_md5_password' => 'string',
																		'http_user'        => 'string',
																		'http_pass' 	   => 'string' ),
														   'out' => array( 'response' => 'xmlrpc' )
														 );
														
		$this->__dispatch_map[ 'handshakeEnd' ] = array(
														   'in'  => array(
																		'reg_id'              => 'int',
																		'reg_code'            => 'string',
																		'reg_date'            => 'string',
																		'reg_product_id'      => 'int',
																		'converge_url'        => 'string',
																		'handshake_completed' => 'int' ),
														   'out' => array( 'response' => 'xmlrpc' )
														 );
														
		$this->__dispatch_map[ 'handshakeRemove' ] = array(
														   'in'  => array(
																		'reg_product_id'      => 'int',
																		'reg_code'            => 'string' ),
														   'out' => array( 'response' => 'xmlrpc' )
														 );
			
		
	}
	
	/**
	 * handshake_server::handshake_start()
	 *
	 * Returns all data...
	 * 
	 * @param  integer  $reg_id  		Converge reg ID
	 * @param  string  	$reg_code   	Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @param  integer  $reg_date       Unix stamp of converge request start time
	 * @param  integer  $reg_product_id	Converge product ID (MUST BE PRESENT IN ALL RETURNED API REQUESTS)
	 * @param  string	$converge_url   Converge application base url (no slashes or paths)
	 * @return xml
	 **/	
	function handshakeStart( $reg_id='', $reg_code='', $reg_date='', $reg_product_id='', $converge_url='', $acp_email='', $acp_md5_password='', $http_user='', $http_pass='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$reg_id			  = intval( $reg_id );
		$reg_code         = $this->ipsclass->txt_md5_clean( $reg_code );
		$reg_date	      = intval( $reg_date );
		$reg_product_id	  = intval( $reg_product_id );
		$converge_url	  = $this->ipsclass->parse_clean_value( $converge_url );
		$acp_email	      = $this->ipsclass->parse_clean_value( $acp_email );
		$acp_md5_password = $this->ipsclass->txt_md5_clean( $acp_md5_password );
		
		//-----------------------------------------
		// Check ACP user
		//-----------------------------------------
		
		if ( ! $acp_email AND ! $acp_md5_password )
		{
			$this->class_api_server->api_send_error( 500, 'Missing ACP Email address and/or ACP Password' );
			return FALSE;
		}
		else
		{
			$this->ipsclass->converge->converge_load_member( $acp_email );
			
			if ( ! $this->ipsclass->converge->member['converge_id'] )
			{
				$this->class_api_server->api_send_error( 501, 'ACP Email or Password Incorrect' );
				return FALSE;
			}
			else
			{
				//-----------------------------------------
				// Get member
				//-----------------------------------------

				$this->ipsclass->DB->build_query( array(
														  'select'   => 'm.*',
														  'from'     => array( 'members' => 'm' ),
														  'where'    => "id=".intval($this->ipsclass->converge->member['converge_id']),
														  'add_join' => array( 0 => array( 'select' => 'g.*',
																						   'from'   => array( 'groups' => 'g' ),
																						   'where'  => 'm.mgroup=g.g_id',
																						   'type'   => 'inner'
																						 )
																			)
												 )     );
														 
				$this->ipsclass->DB->exec_query();
		
				$member = $this->ipsclass->DB->fetch_row();
				
				//-----------------------------------------
				// Are we an admin?
				//-----------------------------------------
				
				if ( $member['g_access_cp'] != 1 )
				{
					$this->class_api_server->api_send_error( 501, 'The ACP member does not have ACP access' );
					return FALSE;
				}
				
				//-----------------------------------------
				// Are we a root admin?
				//-----------------------------------------
				
				if ( $member['mgroup'] != $this->ipsclass->vars['admin_group'] )
				{
					$this->class_api_server->api_send_error( 501, 'The ACP member is not a root admin' );
					return FALSE;
				}
				
				//-----------------------------------------
				// Check password...
				//-----------------------------------------

				if ( $this->ipsclass->converge->converge_authenticate_member( $acp_md5_password ) != TRUE )
				{ 
					$this->class_api_server->api_send_error( 501, 'ACP Email or Password Incorrect' );
					return FALSE;
				}
			}
		}
		
		//-----------------------------------------
		// Just send it all back and start
		// A row in the converge_local table with
		// the info, but don't flag as active...
		//-----------------------------------------
		
		$reply = array( 'master_response' => 1,
						'reg_id'          => $reg_id,
						'reg_code'        => $reg_code,
						'reg_date'        => $reg_date,
						'reg_product_id'  => $reg_product_id,
						'converge_url'    => $converge_url );
						
		//-----------------------------------------
		// Add into DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'converge_local', array( 'converge_api_code'   => $reg_code,
															 	 'converge_product_id' => $reg_product_id,
																 'converge_added'      => $reg_date,
																 'converge_ip_address' => $this->ipsclass->my_getenv('REMOTE_ADDR'),
																 'converge_url'        => $converge_url,
																 'converge_active'	   => 0,
																 'converge_http_user'  => $http_user,
																 'converge_http_pass'  => $http_pass ) );
			
		//-----------------------------------------
		// Send reply...
		//-----------------------------------------
		
						
		$this->class_api_server->api_send_reply( $reply );
	}
	
	/**
	 * handshake_server::handshake_end()
	 *
	 * Returns all data...
	 * 
	 * @param  integer  $reg_id  		Converge reg ID
	 * @param  string  	$reg_code   	Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @param  integer  $reg_date       Unix stamp of converge request start time
	 * @param  integer  $reg_product_id	Converge product ID (MUST BE PRESENT IN ALL RETURNED API REQUESTS)
	 * @param  string	$converge_url   Converge application base url (no slashes or paths)
	 * @param  integer  $handshake_completed All done flag
	 * @return xml
	 **/	
	function handshakeEnd( $reg_id='', $reg_code='', $reg_date='', $reg_product_id='', $converge_url='', $handshake_completed='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$reg_id			     = intval( $reg_id );
		$reg_code            = $this->ipsclass->txt_md5_clean( $reg_code );
		$reg_date	         = intval( $reg_date );
		$reg_product_id	     = intval( $reg_product_id );
		$converge_url	     = $this->ipsclass->parse_clean_value( $converge_url );
		$handshake_completed = intval( $handshake_completed );
		
		//-----------------------------------------
		// Grab data from the DB
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => "converge_api_code='".$reg_code."' AND converge_product_id=".$reg_product_id ) );
		
		//-----------------------------------------
		// Got it?
		//-----------------------------------------
															
		if ( $converge['converge_api_code'] )
		{
			$this->ipsclass->DB->do_update( 'converge_local', array( 'converge_active' => 0 ) );
			$this->ipsclass->DB->do_update( 'converge_local', array( 'converge_active' => 1 ), "converge_api_code = '".$reg_code."'" );
			
			//-----------------------------------------
			// Update the settings...
			//-----------------------------------------

			require( ROOT_PATH . "sources/action_admin/settings.php" );
			$settings           =  new ad_settings();
			$settings->ipsclass =& $this->ipsclass;

			//-----------------------------------------
			// Sort out some vars
			//-----------------------------------------

			$_full_url = preg_replace( "#/$#", "", $converge_url ) . '/?p='.$reg_product_id;

			//-----------------------------------------
			// Update...
			//-----------------------------------------

			$this->ipsclass->DB->do_update( "conf_settings", array( "conf_value" => 1 )                 , "conf_key='ipconverge_enabled'" );
			$this->ipsclass->DB->do_update( "conf_settings", array( "conf_value" => $converge_url )     , "conf_key='ipconverge_url'" );
			$this->ipsclass->DB->do_update( "conf_settings", array( "conf_value" => $reg_product_id )   , "conf_key='ipconverge_pid'" );
			$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 'converge'      ), "conf_key='ipbli_key'" );
			$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 'email'     )    , "conf_key='ipbli_usertype'" );

			$settings->setting_rebuildcache();

			//-----------------------------------------
			// Switch over log in methods
			//-----------------------------------------

			$this->ipsclass->DB->do_update( "login_methods", array( "login_enabled"      => 0 ) );
			$this->ipsclass->DB->do_update( "login_methods", array( "login_enabled"      => 1,
																	"login_login_url"    => '',
			 														"login_maintain_url" => '',
			 														'login_user_id'		 => 'email',
																	"login_logout_url"	 => '',
																	"login_register_url" => '' ), "login_folder_name='ipconverge'" );

			$this->class_api_server->api_send_reply( array( 'handshake_updated' => 1 ) );
		}
		else
		{
			$this->class_api_server->api_send_error( 500, 'Could not locate a Converge handshake to update' );
			return FALSE;
		}
	}
	
	/**
	 * handshake_server::handshake_remove()
	 *
	 * Unconverges an application
	 * 
	 * @param  integer  $reg_id  		Converge reg ID
	 * @param  string  	$reg_code   	Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @return xml
	 **/	
	function handshakeRemove( $reg_product_id='', $reg_code='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$reg_product_id = intval( $reg_product_id );
		$reg_code       = $this->ipsclass->txt_md5_clean( $reg_code );
		
		//-----------------------------------------
		// Grab data from the DB
		//-----------------------------------------
		
		$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => "converge_api_code='".$reg_code."' AND converge_product_id=".$reg_product_id ) );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $converge['converge_active'] )
		{
			//-----------------------------------------
			// Remove app stuff
			//-----------------------------------------
															
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'converge_local',
															  'where'  => 'converge_product_id=' . intval( $reg_id ) ) );

			//-----------------------------------------
			// Update the settings...
			//-----------------------------------------
		
			require( ROOT_PATH . "sources/action_admin/settings.php" );
			$settings           =  new ad_settings();
			$settings->ipsclass =& $this->ipsclass;
		
			//-----------------------------------------
			// Sort out some vars
			//-----------------------------------------
		
			$_full_url = preg_replace( "#/$#", "", $converge_url ) . '/?p='.$reg_product_id;
		
			//-----------------------------------------
			// Update...
			//-----------------------------------------
		
			$this->ipsclass->DB->do_update( "conf_settings", array( "conf_value" => 0 )         , "conf_key='ipconverge_enabled'" );
			$this->ipsclass->DB->do_update( "conf_settings", array( "conf_value" => '' )        , "conf_key='ipconverge_url'" );
			$this->ipsclass->DB->do_update( "conf_settings", array( "conf_value" => 0 )         , "conf_key='ipconverge_pid'" );
			$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 'internal' ), "conf_key='ipbli_key'" );
			$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 'username' ), "conf_key='ipbli_usertype'" );
		
			$settings->setting_rebuildcache();
		
			//-----------------------------------------
			// Switch over log in methods
			//-----------------------------------------
		
			$this->ipsclass->DB->do_update( "login_methods", array( "login_enabled"      => 0 ) );
			$this->ipsclass->DB->do_update( "login_methods", array( "login_enabled"      => 1 ), "login_folder_name='internal'" );
																
			$this->class_api_server->api_send_reply( array( 'handshake_removed' => 1 ) );
		}
		else
		{
			$this->class_api_server->api_send_reply( array( 'handshake_removed' => 0 ) );
		}
	
	}

}
?>