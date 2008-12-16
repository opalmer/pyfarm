<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2005 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB CHAT 2004 INTEGRATION
|   > Script written by Matt Mecham
|   > Date started: 20th April 2004
|   > Interesting fact: Radiohead rock
|   > Which Radiohead track features the words "In a city of the future"?
+--------------------------------------------------------------------------
*/

/**
* Parachat auth file.
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

$ipsclass->cache_array = array('rss_calendar', 'rss_export','components','banfilters', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'moderators', 'stats', 'languages');


//===========================================================================
// Get cache...
//===========================================================================

$ipsclass->init_load_cache( $ipsclass->cache_array );

//--------------------------------
//  Initialize the FUNC
//--------------------------------

$ipsclass->initiate_ipsclass();

//===========================================================================
// AUTHORIZE...
//===========================================================================

$reply_success = 'Result=Success';
$reply_nouser  = 'Result=UserNotFound';
$reply_nopass  = 'Result=WrongPassword';
$reply_error   = 'Result=Error';

$in_user       = $ipsclass->parse_clean_value(urldecode(trim($_GET['user'])));
$in_pass       = $ipsclass->parse_clean_value(urldecode(trim($_GET['pass'])));
$in_cookie     = $ipsclass->parse_clean_value(urldecode(trim($_GET['cookie'])));
$access_groups = $ipsclass->vars['chat04_access_groups'];
$in_md5_pass   = "";
$nametmp       = "";
$in_userid     = 0;
$query         = 0;

if ( preg_match( "/^md5pass\((.+?)\)(.+?)$/", $in_pass, $match ) )
{
	$in_md5_pass = $match[1];
	$in_userid   = intval($match[2]);
}
	
//----------------------------------------------
// Did we pass a user ID?
//----------------------------------------------

if ( $in_userid )
{
	$query   = "m.id=".$in_userid;
	$in_user = 1;
}
else
{
	$in_user = str_replace( '-', '_', $in_user );
	$timeoff = time() - 3600;
	$query   = "m.members_display_name LIKE '".addslashes($in_user)."' AND last_activity > $timeoff";
}

//----------------------------------------------
// Continue..
//----------------------------------------------

if ( $in_user and ! $in_pass )
{
	show_message( $reply_nopass );
	//## EXIT ##
}

if ( $in_user and $in_pass )
{
	//------------------------------------------
	// Attempt to get member...
	//------------------------------------------
	
	$ipsclass->DB->build_query( array(	'select' 	=> 'm.mgroup, m.name, m.id, c.*',
										'from'		=> array( 'members' => 'm' ),
										'where'		=> $query,
										'limit'		=> array( 0, 1 ),
										'add_join'	=> array( 1 => array( 	'type' 	=> 'left',
																			'from'	=> array( 'members_converge' => 'c' ),
																			'where'	=> 'c.converge_email=m.email',
															)			)
								)		);
	$ipsclass->DB->exec_query();

	$member = $ipsclass->DB->fetch_row();
	
	if ( ! $member['id'] )
	{
		//--------------------------------------
		// Guest...
		//--------------------------------------
		
		test_for_guest();
	}
	
	//------------------------------------------
	// Test for MD5 (future proof)
	//------------------------------------------
	
	if ( ! $in_md5_pass )
	{
		$in_md5_pass = md5( md5( $member['converge_pass_salt'] ) . md5($in_pass) );
	}
	
	//------------------------------------------
	// PASSWORD?
	//------------------------------------------
	
	if ( $in_md5_pass == $member['converge_pass_hash'] )
	{
		//--------------------------------------
		// Check for access
		//--------------------------------------
		
		if ( ! preg_match( "/(^|,)".$member['mgroup']."(,|$)/", $access_groups ) )
		{
			show_message( $reply_error );
			//## EXIT ##
		}
		else
		{
			show_message( $reply_success );
			//## EXIT ##
		}
	}
	else
	{
		show_message( $reply_nopass );
		//## EXIT ##
	}
}
else
{
	//------------------------------------------
	// Guest...
	//------------------------------------------
	
	test_for_guest();
}

//===========================================================================
// YES TO GUEST OR NO TO PASS GO
//===========================================================================

function test_for_guest()
{
	global $reply_nouser, $reply_error;
	
	if ( preg_match( "/(^|,)".$ipsclass->vars['guest_group']."(,|$)/", $access_groups ) )
	{
		show_message( $reply_nouser );
		//## EXIT ##
	}
	else
	{
		show_message( $reply_error );
		//## EXIT ##
	}
}

//===========================================================================
// SHOW MESSAGE
//===========================================================================

function show_message($msg="Result=Error")
{
	@flush();
	echo $msg;
	exit;
}


?>