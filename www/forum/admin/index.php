<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2006-05-23 15:58:32 +0100 (Tue, 23 May 2006) $
|   > $Revision: 271 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin wrapper script
|   > Script written by Matt Mecham
|   > Date started: 1st March 2002
|
+--------------------------------------------------------------------------
*/

/**
* Main ACP executable wrapper.
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
define( 'IPB_THIS_SCRIPT', 'admin' );
define( 'IPB_LOAD_SQL'   , 'admin_queries' );

require_once( '../init.php' );

$INFO = array();

//===========================================================================
// NO USER EDITABLE SECTIONS BELOW
//===========================================================================

if (function_exists("set_time_limit") == 1 and SAFE_MODE_ON == 0)
{
	@set_time_limit(0);
}

//--------------------------------
// Load up our classes
//--------------------------------
 
require ROOT_PATH   . "sources/ipsclass.php";
require ROOT_PATH   . "sources/classes/class_session.php";
require ROOT_PATH   . "sources/classes/class_forums.php";
require KERNEL_PATH . "class_converge.php";
require ROOT_PATH   . "conf_global.php";
require ROOT_PATH   . "sources/lib/admin_functions.php";
require ROOT_PATH   . "sources/lib/admin_skin.php";

$ipsclass = new ipsclass();

$ipsclass->vars = $INFO;

//--------------------------------
// The clocks a' tickin'
//--------------------------------

$Debug = new Debug;
$Debug->startTimer();

//--------------------------------
// Additional set up
//--------------------------------

if ( isset($ipsclass->vars['safe_mode_skins']) )
{
	define( 'SAFE_MODE_SKINS', $ipsclass->vars['safe_mode_skins'] );
}
else
{
	define( 'SAFE_MODE_SKINS', SAFE_MODE_ON );
}
		
//===========================================================================
// Load up our database library
//===========================================================================
 
$ipsclass->init_db_connection();

//===========================================================================
// Get cache...
//===========================================================================

$ipsclass->init_cache_setup();
$ipsclass->init_load_cache( array('rss_export', 'bbcode', 'badwords', 'emoticons', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'moderators', 'stats') );

//--------------------------------
// Set up classes
//--------------------------------

$ipsclass->sess             =  new session();
$ipsclass->sess->ipsclass   =& $ipsclass;

$ipsclass->forums           =  new forum_functions();
$ipsclass->forums->ipsclass =& $ipsclass;

//--------------------------------
// Set up incoming array
//--------------------------------

$ipsclass->parse_incoming();

//--------------------------------
//  Initialize the FUNC
//--------------------------------

$ipsclass->initiate_ipsclass();

//--------------------------------
//  Set converge
//--------------------------------

$ipsclass->converge = new class_converge( $ipsclass->DB );

//-----------------------------------------
// Clean up ad session
//-----------------------------------------

$ipsclass->input['adsess'] = substr( preg_replace( "#[^\w\d]#", "", $ipsclass->input['adsess'] ), 0, 32 );

//--------------------------------
// Message in a bottle?
//--------------------------------

if ( isset($ipsclass->input['messageinabottleacp']) AND $ipsclass->input['messageinabottleacp'] )
{
	$ipsclass->input['messageinabottleacp'] = $ipsclass->clean_evil_tags( $ipsclass->txt_UNhtmlspecialchars( urldecode($ipsclass->input['messageinabottleacp']) ) );
	$ipsclass->main_msg                     = $ipsclass->input['messageinabottleacp'];
}

//--------------------------------
// Fix up base URLs
//--------------------------------

$ipsclass->skin_acp = 'IPB2_Standard';

$ipsclass->base_url     = $ipsclass->vars['board_url']."/" . IPB_ACP_DIRECTORY . "/index." . $ipsclass->vars['php_ext'].'?adsess='.$ipsclass->input['adsess'];
$ipsclass->skin_acp_url = $ipsclass->vars['board_url']."/skin_acp/".$ipsclass->skin_acp;

//--------------------------------
// Load global ACP skin
//--------------------------------

$ipsclass->skin_acp_global = $ipsclass->acp_load_template('cp_skin_global');

//--------------------------------
// Load public skin
//--------------------------------

$ipsclass->load_skin();

//--------------------------------
// Import Admin Functions
//--------------------------------
 
$ipsclass->admin           =  new admin_functions();
$ipsclass->admin->ipsclass =& $ipsclass;
$ipsclass->admin->img_url  =  $ipsclass->skin_acp_url;

//------------------------------------------------
// Load skin & lang
//------------------------------------------------

$ipsclass->vars['AVATARS_URL']   = $ipsclass->vars['board_url'].'/style_avatars';
$ipsclass->vars['EMOTICONS_URL'] = $ipsclass->vars['board_url'].'/style_emoticons/<#EMO_DIR#>';
$ipsclass->vars['mime_img']      = $ipsclass->vars['board_url'].'/style_images/<#IMG_DIR#>/folder_mime_types';

$ipsclass->load_language( 'lang_global' );

//------------------------------------------------
// Import Skinable elements
//------------------------------------------------
 
$ipsclass->adskin           = new admin_skin();
$ipsclass->adskin->ipsclass =& $ipsclass;
$ipsclass->adskin->init_admin_skin();

//------------------------------------------------------
// Require..
//------------------------------------------------------

require_once( ROOT_PATH.'sources/lib/admin_cache_functions.php' );
$ipsclass->cache_func = new admin_cache_functions();
$ipsclass->cache_func->ipsclass =& $ipsclass;

//------------------------------------------------------
// Legacy mode?
//------------------------------------------------------

if ( LEGACY_MODE )
{
	$DB       =& $ipsclass->DB;
	$std      =& $ipsclass;
	$ibforums =& $ipsclass;
	$forums   =& $ipsclass->forums;
	$print    =& $ipsclass->print;
	$sess     =& $ipsclass->sess;
}

//------------------------------------------------------
// ACP Session-ize the member
//------------------------------------------------------

$ipsclass->admin_session = array( '_session_validated' => 0 );

$ipsclass->admin->acp_session_validation();

//------------------------------------------------------
// Generate auth key
//------------------------------------------------------

$ipsclass->_admin_auth_key = $ipsclass->admin->security_auth_get();

//------------------------------------------------------
// What are we doing, then?
//------------------------------------------------------

$ipsclass->input['act'] = isset($ipsclass->input['act']) ? $ipsclass->input['act'] 		  : '';
$ipsclass->input['st']	= isset($ipsclass->input['st'])  ? intval($ipsclass->input['st']) : 0;

if ( ( $ipsclass->input['act'] != 'login' ) AND ( ! $ipsclass->admin_session['_session_validated'] ) )
{
	//------------------------------
	// Force log in
	//------------------------------
	
	$ipsclass->input['act']  = 'login';
	$ipsclass->input['code'] = 'login';
	
	require_once( ROOT_PATH."sources/action_admin/login.php" );
	
	$runme           = new ad_login();
	$runme->ipsclass =& $ipsclass;
	$runme->auto_run();
	
	exit();
}
else if ( strtolower($ipsclass->input['act']) == 'login' )
{
	//------------------------------
	// Ok - got a log in, kill section
	//------------------------------
	
	$ipsclass->input['section'] = '';
}

//------------------------------------------------------
// Fix up board url
//------------------------------------------------------

if ( $ipsclass->admin->session_type == 'cookie' )
{
	$ipsclass->base_url = $ipsclass->vars['board_url']."/" . IPB_ACP_DIRECTORY . "/index." . $ipsclass->vars['php_ext'].'?';
}
else
{
	$ipsclass->base_url = $ipsclass->vars['board_url']."/" . IPB_ACP_DIRECTORY . "/index." . $ipsclass->vars['php_ext'].'?adsess='.$ipsclass->input['adsess'];
}

//------------------------------------------------------
//  What do you want to require today?
//------------------------------------------------------

$choice = array(
				'content'     => array( 'acp_content'     , 'acp_content'     , 'content' ),
				'lookandfeel' => array( 'acp_lookandfeel' , 'acp_lookandfeel' , 'lookandfeel' ),
				'tools'       => array( 'acp_tools'       , 'acp_tools'       , 'tools' ),
				'components'  => array( 'acp_components'  , 'acp_components'  , 'components' ),
				'admin'       => array( 'acp_admin'       , 'acp_admin'       , 'admin' ),
				'help'        => array( 'acp_help'        , 'acp_help'        , 'help' ),
				'dashboard'   => array( 'acp_dashboard'   , 'acp_dashboard'   , 'dashboard' ),
				
				 # Non CP specific action
				 'xmlout'    => array( "xmlout"          , 'xmlout' ),
				 'prefs'     => array( "prefs"           , 'prefs' ),
				 'login'     => array( 'login'           , 'login' ),
				 'rtempl'    => array( 'remote_template' , 'remote_template' ),
				 'quickhelp' => array( "quickhelp"       , 'quickhelp' ),
			   );
				
//---------------------------------------------------
// Check to make sure the array key exits..
//---------------------------------------------------

if ( ( ! isset($ipsclass->input['section']) ) OR  ( ! isset($choice[ $ipsclass->input['section'] ][0]) ) OR ( ! $ipsclass->input['section'] ) )
{ 
	# Got an act?
	if ( isset($ipsclass->input['act']) AND isset($choice[ $ipsclass->input['act'] ][0]) AND $choice[ $ipsclass->input['act'] ][0] )
	{
		require_once( ROOT_PATH.'sources/action_admin/'.$choice[ $ipsclass->input['act'] ][0].'.php' );
		$constructor          = 'ad_'.$choice[ $ipsclass->input['act'] ][1];
		$runmeagain           = new $constructor;
		$runmeagain->ipsclass =& $ipsclass;
		$runmeagain->auto_run();
	}
	else
	{
		$ipsclass->input['section'] = 'dashboard';
	}
}

//---------------------------------------------------
// Menu type
//---------------------------------------------------

$ipsclass->menu_type = $ipsclass->input['section'];

//---------------------------------------------------
// Require and run
//---------------------------------------------------

require ROOT_PATH."sources/acp_loaders/".$choice[ $ipsclass->input['section'] ][0].".php";
	
$runme = new $choice[ $ipsclass->input['section'] ][1];
$runme->ipsclass =& $ipsclass;
$runme->auto_run();

//+-------------------------------------------------
// Skin emergency mode...
//+-------------------------------------------------

function skin_emergency()
{
	global $ipsclass;
	
	if ( $_GET['skinrebuild'] == 1 )
	{
		print "Attempted to rebuild the skins and failed.<br />Please contact technical support for more assistance.";
		exit();
	}
	
	//-----------------------------------------
	// Rebuild ID cache..
	//-----------------------------------------
	
	require_once( ROOT_PATH.'sources/lib/admin_cache_functions.php' );
    $adcache           = new admin_cache_functions();
    $adcache->ipsclass =& $this->ipsclass;
    
	$ipsclass->cache['skin_id_cache'] = $adcache->_rebuild_skin_id_cache();
	
	//-----------------------------------------
	// Attempt to recache the default skin
	//-----------------------------------------
	
	foreach( $ipsclass->cache['skin_id_cache'] as $data )
	{
		if ( $data['set_default'] )
		{
			$default_skin = $data['set_skin_set_id'];
		}
	}
	
	//-----------------------------------------
	// Load library
	//-----------------------------------------
	
	require_once( ROOT_PATH.'sources/lib/admin_cache_functions.php' );
	$ipsclass->cache_func           = new admin_cache_functions();
	$ipsclass->cache_func->ipsclass =& $this->ipsclass;
	$ipsclass->cache_func->_rebuild_all_caches( array($default_skin) );
	
	//-----------------------------------------
	// Try to turn on safe mode
	//-----------------------------------------
	
	if ( ! @file_exists( CACHE_PATH.'cache/skin_cache/cacheid_'.$default_skin.'/skin_global.php' ) )
	{
		if ( $ipsclass->vars['safe_mode_skins'] != 1 )
		{
			$ipsclass->DB->do_update( "conf_settings", array( 'conf_value' => 1 ), "conf_key='safe_mode_skins'" );
			
			require_once( ROOT_PATH.'sources/action_admin/settings.php' );
			$adsettings           = new ad_settings();
			$adsettings->ipsclass =& $this->ipsclass;
			$adsettings->setting_rebuildcache();
		}
	}
	
	//-----------------------------------------
	// Update panic message
	//-----------------------------------------
	
	$ipsclass->DB->simple_exec_query( array( "delete" => "cache_store", "where" => "cs_key='skinpanic'" ) );
	$ipsclass->DB->do_insert( 'cache_store', array( 'cs_value' => 'rebuildemergency', 'cs_key' => 'skinpanic' ) );
	
	$ipsclass->boink_it( $ipsclass->base_url.'&skinrebuild=1' );	
}


?>