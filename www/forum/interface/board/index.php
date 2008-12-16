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

/**
* Matches IP address of requesting API
* Set to 0 to not match with IP address
*/
define( 'CVG_IP_MATCH', 1 );

require_once( '../../init.php' );

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

$ipsclass->cache_array = array('badwords','emoticons','attachtypes','bbcode', 'multimod','ranks','profilefields','components','banfilters', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'moderators', 'stats', 'languages' );

//===========================================================================
// Get cache...
//===========================================================================

$ipsclass->init_load_cache( $ipsclass->cache_array );

//--------------------------------
//  Initialize the FUNC
//--------------------------------

$ipsclass->initiate_ipsclass();

//--------------------------------
//  Initialize the FUNC
//--------------------------------

if ( ! $ipsclass->vars['xmlrpc_enable'] )
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
			                  <string>IP.Board's XML-RPC API system is not enabled. Log into your IP.Board ACP and visit: Tools &amp; Settings -&gt; XML-RPC API and update &quot;Enable XML-RPC API System&quot;</string>
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
$api        = $server->decode_request();
$module     = $server->params['api_module'];
$user       = $ipsclass->txt_md5_clean( $server->params['api_key']);

//-----------------------------------------
// Check for module
//-----------------------------------------

if ( $module AND file_exists( ROOT_PATH . 'interface/board/modules/' . $module . '/api.php' ) )
{
	require_once( ROOT_PATH . 'interface/board/modules/' . $module . '/api.php' );
	
	$webservice = new API_Server( $ipsclass );
	$webservice->class_api_server =& $server;
}
else
{
	$server->api_send_reply( array( 'faultCode'   => '2',
									'faultString' => "IP.Board could not locate an API module called '{$module}'" ) );
	
	$ipsclass->DB->do_insert( 'api_log', array( 'api_log_key'     => $user,
												'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
												'api_log_date'    => time(),
												'api_log_query'   => $server->raw_request,
												'api_log_allowed' => 0 ) );
	exit();
}

//-----------------------------------------
// Check user...
//-----------------------------------------

if ( $user )
{
	$webservice->api_user = $ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		'from'   => 'api_users',
																		'where'  => "api_user_key='" . $user . "'" ) );
																		
	if ( ! $webservice->api_user['api_user_id'] )
	{
		$ipsclass->DB->do_insert( 'api_log', array( 'api_log_key'     => $user,
													'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
													'api_log_date'    => time(),
													'api_log_query'   => $server->raw_request,
													'api_log_allowed' => 0 ) );
													
		$server->api_send_reply( array( 'faultCode'   => '3',
										'faultString' => "IP.Board could not locate a valid API user with that API key" ) );
										
		exit();
	}
}
else
{
	$ipsclass->DB->do_insert( 'api_log', array( 'api_log_key'     => $user,
												'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
												'api_log_date'    => time(),
												'api_log_query'   => $server->raw_request,
												'api_log_allowed' => 0 ) );
												
	$server->api_send_reply( array( 'faultCode'   => '4',
									'faultString' => "No API Key was sent in the request" ) );
	exit();
}

//-----------------------------------------
// Check for IP address
//-----------------------------------------

if ( $webservice->api_user['api_user_ip'] )
{
	if ( $_SERVER['REMOTE_ADDR'] != $webservice->api_user['api_user_ip'] )
	{
		$ipsclass->DB->do_insert( 'api_log', array( 'api_log_key'     => $user,
													'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
													'api_log_date'    => time(),
													'api_log_query'   => $server->raw_request,
													'api_log_allowed' => 0 ) );
		
		$server->api_send_reply( array( 'faultCode'   => '5',
										'faultString' => "Incorrect IP Address ({$_SERVER['REMOTE_ADDR']}). You must update the API User Key with that IP Address." ) );

		exit();
	}
}

//-----------------------------------------
// Add web service
//-----------------------------------------

$server->add_object_map( $webservice, 'UTF-8' );

//-----------------------------------------
// Process....
//-----------------------------------------

$server->get_xml_rpc();

exit;


?>