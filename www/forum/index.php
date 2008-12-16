<?php
#apd_set_pprof_trace();

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
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2007-05-11 17:54:11 -0400 (Fri, 11 May 2007) $
|   > $Revision: 994 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Wrapper script
|   > Script written by Matt Mecham
|   > Date started: 14th February 2002
|	> Date updated: IPB 2.1.0: Tue 12 July 2005
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

require_once( './init.php' );

//===========================================================================
// MAIN PROGRAM
//===========================================================================

$INFO = array();

//--------------------------------
// Load our classes
//--------------------------------

require_once ROOT_PATH   . "sources/ipsclass.php";
require_once ROOT_PATH   . "sources/classes/class_display.php";
require_once ROOT_PATH   . "sources/classes/class_session.php";
require_once ROOT_PATH   . "sources/classes/class_forums.php";
require_once KERNEL_PATH . "class_converge.php";

if ( file_exists( ROOT_PATH   . "conf_global.php" ) )
{
	require_once ROOT_PATH   . "conf_global.php";
}

# Are we installed?
if( ! $INFO['sql_user'] )
{
	$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : @getenv('HTTP_HOST');
	$self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : @getenv('PHP_SELF');
	@header("Location: http://".$host.rtrim(dirname($self), '/\\')."/install/index.php" );
}

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
// Set debug mode
//--------------------------------

$ipsclass->DB->set_debug_mode( ( IPS_SQL_DEBUG_MODE ) ? ( isset($_GET['debug']) ? intval($_GET['debug']) : 0 ) : 0 );

//--------------------------------
// INIT other classes
//--------------------------------

$ipsclass->print            =  new display();
$ipsclass->print->ipsclass  =& $ipsclass;

$ipsclass->sess             =  new session();
$ipsclass->sess->ipsclass   =& $ipsclass;

$ipsclass->forums           =  new forum_functions();
$ipsclass->forums->ipsclass =& $ipsclass;

//--------------------------------
//  Set up our vars
//--------------------------------

$ipsclass->parse_incoming();

//--------------------------------
//  Set converge
//--------------------------------

$ipsclass->converge = new class_converge( $ipsclass->DB );

//===========================================================================
// Generate choice array
//===========================================================================

$choice = array(
                 "idx"        => array( "boards"             , 'boards'       , array('chatting','birthdays', 'calendar') ),
                 "sc"         => array( "boards"             , 'boards'       , array('chatting','birthdays', 'calendar') ),
                 "sf"         => array( "forums"             , 'forums'       , array('announcements', 'multimod') ),
                 "sr"         => array( "forums"             , 'forums'       , array() ),
                 "st"         => array( "topics"             , 'topics'       , array('badwords','emoticons','attachtypes','bbcode', 'multimod','ranks','profilefields' ) ),
                 "announce"   => array( "announcements"      , 'announcements', array('ranks' ) ),
                 "login"      => array( "login"              , 'login'        , array() ),
                 "post"       => array( "post"               , 'post'         , array('attachtypes','badwords','bbcode','emoticons','ranks' ) ),
                 "reg"        => array( "register"           , 'register'     , array('profilefields') ),
                 "online"     => array( "online"             , 'online'       , array() ),
                 "members"    => array( "memberlist"         , 'memberlist'   , array('ranks','profilefields' ) ),
                 "help"       => array( "help"               , 'help'         , array() ),
                 "search"     => array( "search"             , 'search'       , array('badwords','attachtypes','multimod','ranks' ) ),
                 "mod"        => array( "moderate"           , 'moderate'     , array('attachtypes','multimod','bbcode','emoticons','badwords' ) ),
                 "print"      => array( "misc/print_page"    , 'printpage'    , array('attachtypes','multimod','ranks' ) ),
                 "forward"    => array( "misc/forward_page"  , 'forwardpage'  , array() ),
                 "mail"       => array( "misc/contact_member", 'contactmember', array() ),
                 "report"     => array( "misc/contact_member", 'contactmember', array() ),
                 "chat"       => array( "misc/contact_member", 'contactmember', array() ),
                 'boardrules' => array( "misc/contact_member", 'contactmember', array() ),
                 "msg"        => array( "messenger"          , 'messenger'    , array('ranks','profilefields','attachtypes','badwords','bbcode','emoticons' ) ),
                 "usercp"     => array( "usercp"             , 'usercp'       , array( 'attachtypes', 'badwords', 'bbcode', 'emoticons', 'profilefields' ) ),
                 "profile"    => array( "profile"            , 'profile'      , array('ranks','profilefields','badwords','bbcode','emoticons' ) ),
                 "track"      => array( "misc/tracker"       , 'tracker'      , array() ),
                 "stats"      => array( "misc/stats"         , 'stats'        , array() ),
                 "attach"     => array( "attach"             , 'attach'       , array('attachtypes' ) ),
                 'legends'    => array( 'misc/legends'       , 'legends'      , array('badwords','bbcode','emoticons' ) ),
                 'calendar'   => array( "calendar"           , 'calendar'     , array('attachtypes','bbcode', 'ranks', 'multimod', 'emoticons', 'badwords', 'calendars', 'profilefields' ) ),
                 'buddy'      => array( "browsebuddy"        , 'assistant'    , array() ),
                 'mmod'       => array( "misc/multi_moderate", 'mmod'         , array('multimod' ) ),
                 'warn'       => array( "misc/warn"          , 'warn'         , array('badwords','bbcode'  ,'emoticons'  ) ),
                 'home'       => array( 'portal'             , 'portal'       , array('portal','attachtypes','multimod','ranks','profilefields' ) ),
                 'module'     => array( 'modules'            , 'modules'      , array() ),
                 'task'       => array( 'taskloader'         , 'taskloader'   , array() ),
                 'findpost'   => array( 'findpost'           , 'findpost'     , array() ),
                 "xmlout"     => array( "xmlout"             , 'xmlout'       , array('attachtypes','multimod','bbcode','ranks','profilefields','emoticons','badwords' ) ),
                 'paysubs'    => array( 'paysubscriptions'   , 'paysubscriptions' , array() ),
                 'rssout'     => array( 'rssout'             , 'rssout'       , array() ),
                 'component'  => array( 'component'          , 'component'    , array() ),
               );

//===========================================================================
//  Short tags...
//===========================================================================

$ipsclass->input['act'] = isset($ipsclass->input['act']) ? $ipsclass->input['act'] : ( IPB_MAKE_PORTAL_HOMEPAGE ? 'home' : 'idx' );

if( is_array($ipsclass->input['act']) )
{
	$ipsclass->input['act'] = ( IPB_MAKE_PORTAL_HOMEPAGE ) ? 'home' : 'idx';
}

//---------------------------------------------------
// Check to make sure the array key exits..
//---------------------------------------------------

if ( ! isset($choice[ strtolower($ipsclass->input['act']) ][0]) )
{
	$ipsclass->input['act'] = ( IPB_MAKE_PORTAL_HOMEPAGE ) ? 'home' : 'idx';
}

$ipsclass->input['_low_act'] = strtolower( $ipsclass->input['act'] );

if ( isset($ipsclass->input['showforum']) && $ipsclass->input['showforum'] != "" )
{
	$ipsclass->input['act'] = "sf";
	$ipsclass->input['f']   = intval($ipsclass->input['showforum']);
}
else if ( isset($ipsclass->input['showtopic']) && $ipsclass->input['showtopic'] != "")
{
	$ipsclass->input['act'] = "st";
	$ipsclass->input['t']   = intval($ipsclass->input['showtopic']);
	
	//---------------------------------------------------
	// Grab and cache the topic now as we need the 'f' attr for
	// the skins...
	//---------------------------------------------------
	
	$ipsclass->DB->simple_construct( array( 'select' => '*',
											'from'   => 'topics',
											'where'  => "tid=".$ipsclass->input['t'],
								  )      );
						
	$ipsclass->DB->simple_exec();
                       
    $ipsclass->topic_cache = $ipsclass->DB->fetch_row();
    $ipsclass->input['f']  = $ipsclass->topic_cache['forum_id'];
}
else if ( isset($ipsclass->input['showuser']) && $ipsclass->input['showuser'] != "")
{
	$ipsclass->input['act'] = "profile";
	$ipsclass->input['MID'] = intval($ipsclass->input['showuser']);
}
else if ( isset($ipsclass->input['automodule']) && $ipsclass->input['automodule'] != "" )
{
	$ipsclass->input['act']    = 'module';
	$ipsclass->input['module'] = $ipsclass->input['automodule'];
}
else if ( isset($ipsclass->input['autocom']) && $ipsclass->input['autocom'] != "" )
{
	$ipsclass->input['act']    = 'component';
	$ipsclass->input['module'] = $ipsclass->input['autocom'];
}
else
{
	$ipsclass->input['act'] = ( ! isset($ipsclass->input['act']) || $ipsclass->input['act'] == '' ) ? "idx" : $ipsclass->input['act'];
}

if ( !isset($ipsclass->input['_low_act']) OR !$ipsclass->input['_low_act'] OR $ipsclass->input['_low_act'] == 'idx' OR $ipsclass->input['_low_act'] == 'home' )
{
	$ipsclass->input['_low_act'] = strtolower($ipsclass->input['act']);
}

//--------------------------------
// Start off the cache array
//--------------------------------

$ipsclass->cache_array = array_merge( $choice[ $ipsclass->input['_low_act'] ][2], array('skin_remap', 'rss_calendar', 'rss_export', 'components', 'banfilters', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'moderators', 'stats', 'languages') );

//--------------------------------
// Module? Load INIT class
//--------------------------------

if ( ( $ipsclass->input['act'] == 'module' OR $ipsclass->input['act'] == 'component' ) and $ipsclass->input['module'] )
{
	$file = ROOT_PATH.'sources/components_init/'. $ipsclass->txt_alphanumerical_clean( $ipsclass->input['module'] ).'.php';
	
	if ( file_exists( $file ) )
	{
		require_once( $file );
		$init_class = new component_init();
		$init_class->ipsclass =& $ipsclass;
		$init_class->run_init();
	}
}

//===========================================================================
// Get cache...
//===========================================================================

$ipsclass->init_cache_setup();
$ipsclass->init_load_cache( $ipsclass->cache_array );

//--------------------------------
//  Initialize the FUNC
//--------------------------------

$ipsclass->initiate_ipsclass();

//--------------------------------
//  The rest :D
//--------------------------------

$ipsclass->member     = $ipsclass->sess->authorise();
$ipsclass->lastclick  = $ipsclass->sess->last_click;
$ipsclass->location   = $ipsclass->sess->location;
$ipsclass->session_id = $ipsclass->sess->session_id; // Used in URLs
$ipsclass->my_session = $ipsclass->sess->session_id; // Used in code

//-----------------------------------------
// Cache md5 check
//-----------------------------------------

$ipsclass->md5_check = $ipsclass->return_md5_check();
		
//--------------------------------
//  Initialize the forums
//--------------------------------

$ipsclass->forums->strip_invisible = 1;
$ipsclass->forums->forums_init();

//--------------------------------
// Load the skin
//--------------------------------

$ipsclass->load_skin();

$ppu = 0;
$tpu = 0;

if( isset($ipsclass->member['view_prefs']) )
{
	list($ppu,$tpu) = explode( "&", $ipsclass->member['view_prefs'] );
}
		
$ipsclass->vars['display_max_topics'] = ($tpu > 0) ? $tpu : $ipsclass->vars['display_max_topics'];
$ipsclass->vars['display_max_posts']  = ($ppu > 0) ? $ppu : $ipsclass->vars['display_max_posts'];

//===========================================================================
//  Set up the session ID stuff
//===========================================================================

if ( $ipsclass->session_type == 'cookie' )
{
	$ipsclass->session_id = "";
	$ipsclass->base_url   = $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?';
}
else
{
	$ipsclass->base_url = $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?s='.$ipsclass->session_id.'&amp;';
}

$ipsclass->js_base_url = $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?s='.$ipsclass->session_id.'&';

//--------------------------------
//  Set up the forum_read cookie
//--------------------------------

$ipsclass->hdl_forum_read_cookie();

//===========================================================================
//  Set up defaults
//===========================================================================

$ipsclass->skin_id = $ipsclass->skin['_setid'];

$ipsclass->vars['img_url']       = $ipsclass->vars['ipb_img_url'] ? $ipsclass->vars['ipb_img_url'] . 'style_images/' . $ipsclass->skin['_imagedir'] : 'style_images/' . $ipsclass->skin['_imagedir'];
$ipsclass->vars['AVATARS_URL']   = $ipsclass->vars['ipb_img_url'] ? $ipsclass->vars['ipb_img_url'] . 'style_avatars' : 'style_avatars';
$ipsclass->vars['EMOTICONS_URL'] = $ipsclass->vars['ipb_img_url'] ? $ipsclass->vars['ipb_img_url'] . 'style_emoticons/<#EMO_DIR#>' : 'style_emoticons/<#EMO_DIR#>';
$ipsclass->vars['mime_img']      = $ipsclass->vars['ipb_img_url'] ? $ipsclass->vars['ipb_img_url'] . 'style_images/<#IMG_DIR#>' : 'style_images/<#IMG_DIR#>';

//--------------------------------
//  Set up our language choice
//--------------------------------

if ( !isset($ipsclass->vars['default_language']) OR $ipsclass->vars['default_language'] == "")
{
	$ipsclass->vars['default_language'] = 'en';
}

//--------------------------------
// Did we choose a language?
//--------------------------------

if ( (isset($ipsclass->input['setlanguage']) AND $ipsclass->input['setlanguage']) AND (isset($ipsclass->input['langid']) AND $ipsclass->input['langid']) AND $ipsclass->member['id'] )
{
	if ( is_array( $ipsclass->cache['languages'] ) and count( $ipsclass->cache['languages'] ) )
	{
		foreach( $ipsclass->cache['languages'] as $data )
		{
			if ( $data['ldir'] == $ipsclass->input['langid'] )
			{
				$ipsclass->DB->do_update( 'members', array( 'language' => $data['ldir'] ), 'id='.$ipsclass->member['id'] );
				$ipsclass->member['language'] = $data['ldir'];
			}
		}
	}
}
		
$ipsclass->load_language('lang_global');

//--------------------------------
// Legacy mode?
//--------------------------------

if ( LEGACY_MODE )
{
	$DB       =& $ipsclass->DB;
	$std      =& $ipsclass;
	$ibforums =& $ipsclass;
	$forums   =& $ipsclass->forums;
	$print    =& $ipsclass->print;
	$sess     =& $ipsclass->sess;
	
	$ipsclass->load_template('skin_global');
	$ipsclass->skin_global = $ipsclass->compiled_templates['skin_global'];
}

//===========================================================================
// DECONSTRUCTOR
//===========================================================================

if ( USE_SHUTDOWN and $ipsclass->input['act'] != 'task' )
{
	@chdir( ROOT_PATH );
	$ROOT_PATH = getcwd();
	
	register_shutdown_function( array( &$ipsclass, 'my_deconstructor') );
}

//===========================================================================
// Force log in / board offline?
//===========================================================================

if ($ipsclass->input['_low_act']   != 'login'  and
	$ipsclass->input['_low_act']   != 'reg'    and
	$ipsclass->input['_low_act']   != 'xmlout' and
	$ipsclass->input['_low_act']   != 'rssout' and
	$ipsclass->input['_low_act']   != 'attach' and
	$ipsclass->input['_low_act']   != 'task'   and
	$ipsclass->input['_low_act']   != 'paysubs' )
{
	//-----------------------------------------
	// Do we have a display name?
	//-----------------------------------------
	
	if ( ! $ipsclass->member['members_display_name'] AND $ipsclass->member['members_created_remote'] )
	{
		$pmember = $ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id=" . $ipsclass->member['id'] ) );
		
		if ( $pmember['partial_member_id'] )
		{
			$ipsclass->boink_it( $ipsclass->base_url . 'act=reg&CODE=complete_login&mid='.$ipsclass->member['id'].'&key='.$pmember['partial_date'] );
		}
	}
	
	//--------------------------------
	//  Do we have permission to view
	//  the board?
	//--------------------------------
	
	if ( $ipsclass->member['g_view_board'] != 1 )
	{ 
		$ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_board') );
	}
	
	//--------------------------------
	//  Is the board offline?
	//--------------------------------
	
	if ($ipsclass->vars['board_offline'] == 1)
	{
		if ($ipsclass->member['g_access_offline'] != 1)
		{
			$ipsclass->vars['no_reg'] = 1;
			$ipsclass->board_offline();
		}
	}
	
	//--------------------------------
	//  Is log in enforced?
	//--------------------------------
	
	if ( (! $ipsclass->member['id']) and ($ipsclass->vars['force_login'] == 1) )
	{
		require ROOT_PATH."sources/action_public/login.php";
		$runme = new login();
		$runme->ipsclass =& $ipsclass;
		$runme->auto_run();
		
	}
	
	//--------------------------------
	// Show PURCHASE screen?
	// Not enforced
	//--------------------------------
	
	if ( !isset($ipsclass->member['sub_end']) OR !$ipsclass->member['sub_end'] )
	{
		//--------------------------------
		// 1: No enforce, chosen from reg
		//--------------------------------
		
		if ( ! $ipsclass->vars['subsm_enforce'] and (isset($ipsclass->member['subs_pkg_chosen']) AND $ipsclass->member['subs_pkg_chosen']) )
		{
			$ipsclass->input['act']     = 'paysubs';
			$ipsclass->input['CODE']    = 'paymentmethod';
			$ipsclass->input['sub']     = $ipsclass->member['subs_pkg_chosen'];
			$ipsclass->input['nocp']    = 1;
			$ipsclass->input['msgtype'] = 'fromreg';
		}
	
		//--------------------------------
		// Show PURCHASE screen?
		// Enforced
		//--------------------------------
		
		if ( $ipsclass->vars['subsm_enforce'] and $ipsclass->member['mgroup'] == $ipsclass->vars['subsm_nopkg_group'] )
		{
			$ipsclass->input['act']     = 'paysubs';
			$ipsclass->input['nocp']    = 1;
			$ipsclass->input['msgtype'] = 'force';
			
			if ( $ipsclass->member['subs_pkg_chosen'] )
			{
				$ipsclass->input['CODE']    = 'paymentmethod';
				$ipsclass->input['sub']     = $ipsclass->member['subs_pkg_chosen'];
			}
		}
	}
}

//===========================================================================
// REQUIRE AND RUN
//===========================================================================                

if ( $ipsclass->input['act'] == 'home' AND $ipsclass->vars['csite_on'] )
{
	require ROOT_PATH."sources/action_public/portal.php";
	$csite           =  new portal();
	$csite->ipsclass =& $ipsclass;
	$csite->auto_run();
}
else if ( $ipsclass->input['act'] == 'module' AND USE_MODULES )
{
	require ROOT_PATH."modules/module_loader.php";
	$loader           =  new module_loader();
	$loader->ipsclass =& $ipsclass;
	$loader->run_loader();
}
else if ( $ipsclass->input['act'] == 'component' )
{
	$file = ROOT_PATH.'sources/components_public/'. $ipsclass->txt_alphanumerical_clean( $ipsclass->input['module'] ).'.php';
	
	if ( file_exists( $file ) )
	{
		require_once( $file );
		$loader           =  new component_public();
		$loader->ipsclass =& $ipsclass;
		$loader->run_component();
	}
	else
	{
		@header( "Location: ".$ipsclass->base_url );
	}
}
else
{	 
	// Require and run
	$_pre_load = $ipsclass->memory_debug_make_flag();
	require( ROOT_PATH."sources/action_public/".$choice[ strtolower($ipsclass->input['act']) ][0].".php" );
	$runme = new $choice[ strtolower($ipsclass->input['act']) ][1];
	$runme->ipsclass =& $ipsclass;
	$ipsclass->memory_debug_add( "CORE: Loaded ".$choice[ strtolower($ipsclass->input['act']) ][0].".php", $_pre_load );
	$runme->auto_run();
}





?>