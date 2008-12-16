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

require_once( ROOT_PATH   . 'converge_local/apis/server_functions.php' );
require_once( KERNEL_PATH . 'class_api_server.php' );

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$server     = new class_api_server();
$webservice = new Converge_Server( $ipsclass );
$webservice->class_api_server =& $server;
$api        = $server->decode_request();

$server->add_object_map( $webservice, 'UTF-8' );

//-----------------------------------------
// Process....
//-----------------------------------------

$server->get_xml_rpc();



exit;


?>