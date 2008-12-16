<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > LO-FI VERSION!
|   > Script written by Matt Mecham
|   > Date started: 11th March 2004
|   > Interesting fact: Wrote this while listening to the Stereophonic's
|   > 'Performance and Cocktails' CD. That was when they were good.
|   > Lo-fi feature took about 1.5 days to write. That's a lot of CD
|   > repeating...
+--------------------------------------------------------------------------
*/

//-----------------------------------------------
// USER CONFIGURABLE ELEMENTS
//-----------------------------------------------

define( 'LOFI_NAME'  , 'lofiversion' );

if ( substr(PHP_OS, 0, 3) == 'WIN' OR strstr( php_sapi_name(), 'cgi') OR php_sapi_name() == 'apache2filter' )  
{
	define( 'THIS_PATH', str_replace( '\\', '/', dirname( __FILE__ ) ) .'/' );
	define( 'ROOT_PATH', str_replace( LOFI_NAME . '/', '', THIS_PATH ) );
	define( 'SERVER'   , 'WIN' );
}
else
{
	define( 'THIS_PATH', str_replace( '\\', '/', dirname( __FILE__ ) ) .'/' );
	define( 'ROOT_PATH', str_replace( LOFI_NAME . '/', '', THIS_PATH ) );
	define( 'SERVER'   , 'UNX' );
}

define( 'KERNEL_PATH', ROOT_PATH.'ips_kernel/' );

//-----------------------------------------------
// NO USER EDITABLE SECTIONS BELOW
//-----------------------------------------------

define ( 'IN_IPB', 1 );
define ( 'IN_DEV', 0 );
 
error_reporting  (E_ERROR | E_WARNING | E_PARSE);
set_magic_quotes_runtime(0);

define ( 'USE_SHUTDOWN', 1 );

//===========================================================================
// DEBUG CLASS
//===========================================================================

class Debug
{
    function startTimer()
    {
        global $starttime;
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
    }
    function endTimer()
    {
        global $starttime;
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = round (($endtime - $starttime), 5);
        return $totaltime;
    }
}

//===========================================================================
// MAIN PROGRAM
//===========================================================================

$INFO = array();

//--------------------------------
// Load our classes
//--------------------------------

require ROOT_PATH   . "sources/ipsclass.php";
require ROOT_PATH   . "sources/classes/class_display.php";
require ROOT_PATH   . "sources/classes/class_session.php";
require ROOT_PATH   . "sources/classes/class_forums.php";
require KERNEL_PATH . "class_converge.php";
require ROOT_PATH   . "conf_global.php";

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
// Get cache...
//===========================================================================

//--------------------------------
// Start off the cache array
//--------------------------------

$ipsclass->cache_array = array('attachtypes','bbcode', 'multimod','ranks','profilefields','components','banfilters', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'moderators', 'stats', 'languages');

$ipsclass->init_load_cache( $ipsclass->cache_array );

//--------------------------------
//  Initialize the FUNC
//--------------------------------

$ipsclass->initiate_ipsclass();

//--------------------------------
//  Register Shutdown Function
//--------------------------------

if ( USE_SHUTDOWN )
{
	@chdir( ROOT_PATH );
	$ROOT_PATH = getcwd();
	
	register_shutdown_function( array( &$ipsclass, 'my_deconstructor') );
}

//--------------------------------
//  The rest :D
//--------------------------------

$ipsclass->member = $ipsclass->sess->authorise();

//--------------------------------
// Load the skin
//--------------------------------

$ipsclass->load_skin();

$ipsclass->vars['display_max_topics'] = 150;
$ipsclass->vars['display_max_posts']  = 50;

$ipsclass->load_language('lang_global');

//--------------------------------
//  Initialize the forums
//--------------------------------

$ipsclass->forums->strip_invisible = 1;
$ipsclass->forums->forums_init();

$ipsclass->session_id = "";
$ipsclass->base_url   = $ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?';

//--------------------------------
//  Banned?
//--------------------------------

if ( is_array( $ipsclass->cache['banfilters'] ) and count( $ipsclass->cache['banfilters'] ) )
{
	foreach ($ipsclass->cache['banfilters'] as $ip)
	{
		$ip = str_replace( '\*', '.*', preg_quote($ip, "/") );
		
		if ( preg_match( "/^$ip$/", $ipsclass->input['IP_ADDRESS'] ) )
		{
			fatal_error( $ipsclass->lang['lofi_noperm'] );
		}
	}
}
		
//--------------------------------
//  Do we have permission to view
//  the board?
//--------------------------------

if ($ipsclass->member['g_view_board'] != 1)
{ 
	$ipsclass->boink_it( $ipsclass->base_url );
}

//--------------------------------
//  Is the board offline?
//--------------------------------

if ($ipsclass->vars['board_offline'] == 1)
{
	if ($ipsclass->member['g_access_offline'] != 1)
	{
		$ipsclass->boink_it( $ipsclass->base_url );
	}
}

//--------------------------------
//  Is log in enforced?
//--------------------------------

if ( (! $ipsclass->member['id']) and ($ipsclass->vars['force_login'] == 1) )
{
	$ipsclass->boink_it( $ipsclass->base_url );
	
}

//===========================================================================
// DO STUFF!
//===========================================================================

//--------------------------------
// Require 'skin'
//--------------------------------

require_once( THIS_PATH.'lofi_skin.php' );

//--------------------------------
// Not index.php/ ? Redirect
// We do this so we can use relative
// links...
//--------------------------------

$main_string = $ipsclass->my_getenv('REQUEST_URI') ? $ipsclass->my_getenv('REQUEST_URI') : $ipsclass->my_getenv('PHP_SELF');

if ( SERVER == 'WIN' )
{
	$winpath     = $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php?';
	$main_string = $ipsclass->my_getenv('QUERY_STRING');
}
else
{
	if ( strpos( $main_string, '/'.LOFI_NAME.'/index.php/' ) === FALSE  )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php/' );
	}
	
	if ( strstr( $main_string, "/" ) )
	{
		$main_string = str_replace( "/", "", strrchr( $main_string, "/" ) );
	}
}

$main_string = str_replace( ".html", "", $main_string );

$action = 'index';
$id    = 0;
$st    = 0;

//--------------------------------
// Pages?
//--------------------------------

if ( strstr( $main_string, "-" ) )
{
	list( $main, $start ) = explode( "-", $main_string );
	
	$main_string = $main;
	$st          = $start;
}

$st = intval($st);

//--------------------------------
// What we doing?
//--------------------------------

if ( preg_match( "#t\d#", $main_string ) )
{
	$action = 'topic';
	$id    = intval( preg_replace( "#t(\d+)#", "\\1", $main_string ) );
}
if ( preg_match( "#f\d#", $main_string ) )
{
	$action = 'forum';
	$id    = intval( preg_replace( "#f(\d+)#", "\\1", $main_string ) );
}


//--------------------------------
// Do it!
//--------------------------------

$output = "";

switch ( $action )
{
	case 'forum':
		$ipsclass->real_link = $ipsclass->base_url.'showforum='.$id;
		$output = get_forum_page($id, $st);
		break;
	case 'topic':
		$ipsclass->real_link = $ipsclass->base_url.'showtopic='.$id;
		$output = get_topic_page($id, $st);
		break;
	default:
		$ipsclass->real_link = $ipsclass->base_url;
		$output = get_index_page();
		break;
}

print_it($output);


//--------------------------------
// Board index
//--------------------------------

function get_index_page()
{
	global $ipsclass, $std, $DB, $forums, $LOFISKIN;
	
	return LOFISKIN_forums( _get_forums() );
}

//--------------------------------
// Forums index
//--------------------------------

function get_forum_page($id, $st)
{
	global $ipsclass, $std, $DB, $forums, $LOFISKIN, $navarray, $winpath;
	
	$output = "";
	
	if ( $ipsclass->check_perms($ipsclass->forums->forum_by_id[$id]['read_perms']) != TRUE and ( ! $ipsclass->forums->forum_by_id[$id]['permission_showtopic'] ) )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	//--------------------------------
	// Passy?
	//--------------------------------
	
	if ( $ipsclass->forums->forum_by_id[$id]['password'] != '' )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	//--------------------------------
	// Nav array...
	//--------------------------------
	
	$navarray = _get_nav_array($id);
	
	$ipsclass->title = $ipsclass->forums->forum_by_id[ $id ]['name'];
	
	if ( ! $ipsclass->forums->forum_by_id[ $id ]['sub_can_post'] )
	{
		//--------------------------------
		// Show forums?
		//--------------------------------
		
		if ( is_array($ipsclass->forums->forum_cache[ $id ]) and count($ipsclass->forums->forum_cache[ $id ]) )
		{
			$html_string .= LOFISKIN_forums_entry_first($ipsclass->forums->forum_by_id[ $id ], $winpath);
			
			$depth_guide = "";
			
			foreach( $ipsclass->forums->forum_cache[ $id ] as $forum_data )
			{
				if( $forum_data['redirect_on'] )
				{
					continue;
				}
				
				$forum_data['total_posts'] = intval( $forum_data['topics'] + $forum_data['posts'] );
				
				$html_string .= LOFISKIN_forums_entry($depth_guide, $forum_data, $winpath );
				
				$html_string = _get_forums_internal( $forum_data['id'], $html_string, "   ".$depth_guide );
			}
			
			$html_string .= LOFISKIN_forums_entry_end($depth_guide);
		}
		
		$output = $html_string;
		
		//--------------------------------
		// Return..
		//--------------------------------
	
		return LOFISKIN_forums($output);
	}
	else
	{
		//--------------------------------
		// Show topics...
		//--------------------------------
		
		$ipsclass->pages = _get_pages( $ipsclass->forums->forum_by_id[ $id ]['topics'], $ipsclass->vars['display_max_topics'], 'f'.$id );
		
		if ( ! $ipsclass->member['g_other_topics'])
		{
			$query = " and starter_id=".$ipsclass->member['id'];
		}
		
		//--------------------------------
		// Topics...
		//--------------------------------
		
		$ipsclass->DB->simple_construct( array( 'select' => '*',
												'from'   => 'topics',
												'where'  => "approved=1 and forum_id=$id".$query,
												'order'  => 'pinned desc, last_post desc',
												'limit'  => array( $st, $ipsclass->vars['display_max_topics'] )
									   )      );
		$outer = $ipsclass->DB->simple_exec();
		
		while( $r = $ipsclass->DB->fetch_row($outer) )
		{
			if ( $r['pinned'] )
			{
				$r['_prefix'] = $ipsclass->vars['pre_pinned'] ? $ipsclass->vars['pre_pinned'] : $ipsclass->lang['lofi_pinned'];
			}
			else
			{
				$r['_prefix'] = "";
			}
			
			if( $r['posts'] < 0 )
			{
				$r['posts'] = 0;
			}
			
			if ($r['state'] == 'link')
			{
				$t_array = explode("&", $r['moved_to']);
				$r['tid']       = $t_array[0];
				$r['forum_id']  = $t_array[1];
				$r['title']     = $r['title'];
				$r['posts']     = '--';
				$r['_prefix']   = $ipsclass->vars['pre_moved'] ? $ipsclass->vars['pre_moved'] : $ipsclass->lang['lofi_moved'];
			}
			
			$r['lang_key'] = ($r['posts'] > 1 OR $r['posts'] == 0) ? 'lofi_replies' : 'lofi_reply';
			
			$output .= LOFISKIN_topics_entry($r, $winpath);
		}
		
		//--------------------------------
		// Return..
		//--------------------------------
	
		if( $output )
		{
			return LOFISKIN_topics($output);
		}
	}
}

//--------------------------------
// Topics index
//--------------------------------

function get_topic_page($id, $st)
{
	global $ipsclass, $std, $DB, $forums, $LOFISKIN, $navarray, $winpath;
	
	$output = "";
	
	//--------------------------------
	// get topic
	//--------------------------------
	
	$topic = $ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from'   => 'topics', 'where'  => "tid=".$id." and approved=1" ) );
	
	if ( ! $ipsclass->member['g_other_topics'] AND $topic['starter_id'] != $ipsclass->member['id'] )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	if ( ! $topic['tid'] )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	if ( ! $ipsclass->forums->forum_by_id[ $topic['forum_id'] ] )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	if ( !$ipsclass->forums->forums_check_access( $topic['forum_id'], 0, 'forum', 1 ) )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	if ( $ipsclass->forums->read_topic_only )
	{
		$ipsclass->boink_it( $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/index.php' );
	}
	
	$ipsclass->pages = _get_pages( $topic['posts'] + 1, $ipsclass->vars['display_max_posts'], 't'.$id );
	
	$ipsclass->title = $topic['title'];
	
	//--------------------------------
	// get posts...
	//--------------------------------
	
	$ipsclass->DB->build_query(      array( 'select'   => 'p.*',
											'from'     => array( 'posts' => 'p' ),
											'where'    => "p.topic_id={$id} AND p.queued=0",
											'add_join' => array( 0 => array( 'select' => 'm.members_display_name, m.mgroup',
																			 'from'   => array( 'members' => 'm' ),
																			 'where'  => 'm.id=p.author_id',
																			 'type'   => 'left' ) ),
											'order'    => 'pid',
											'limit'    => array( $st, $ipsclass->vars['display_max_posts'] )
								   )     );
						  
	$outer = $ipsclass->DB->simple_exec();
	
	while( $r = $ipsclass->DB->fetch_row($outer) )
	{
		$r['post_date']   = $ipsclass->get_date( $r['post_date'], 'LONG', 1 );
		
		$r['author_name'] = $r['members_display_name'] ? $r['members_display_name'] : $r['author_name'];
		
		//--------------------------------
		// Manage POST / TOPIC tags index.php?act=findpost&pid=415
		// <a href='index.php?showtopic=100'>
		//--------------------------------
	
		$r['post'] = preg_replace( "#([\"'])index\.{$ipsclass->vars['php_ext']}\?showtopic=#i"               , "\\1".$ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?showtopic='       , $r['post'] );
		$r['post'] = preg_replace( "#([\"'])index\.{$ipsclass->vars['php_ext']}\?act=findpost&(amp;)?pid=#is", "\\1".$ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?act=findpost&pid=', $r['post'] );
		
		//--------------------------------
		// Convert attach links
		//--------------------------------
		
		$r['post'] = preg_replace( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", '<a href="'.$ipsclass->vars['board_url'].'/index.'.$ipsclass->vars['php_ext'].'?act=attach&amp;type=post&amp;id='."\\1".'">'.$ipsclass->lang['lofi_attach'].'</a>', $r['post'] );
		
		$output .= LOFISKIN_posts_entry($r, $winpath);
	}
	
	//--------------------------------
	// Nav array...
	//--------------------------------
	
	$navarray = _get_nav_array( $topic['forum_id'] );
	
	return $output;
}

//--------------------------------
// Print it
//--------------------------------

function print_it($content, $title='')
{
	global $ipsclass, $std, $DB, $forums, $LOFISKIN, $navarray, $print;
	
	$fullurl   = $ipsclass->vars['board_url'].'/'.LOFI_NAME.'/';
	
	$copyright = "Invision Power Board &copy; 2001-".date("Y")." Invision Power Services, Inc.";
	
	if ( $ipsclass->vars['ipb_copy_number'] )
	{
		$copyright = "";
	}
        
	//--------------------------------
	// Nav
	//--------------------------------
	
	$nav = "<a href='./'>".$ipsclass->vars['board_name']."</a>";
	
	if ( count($navarray) )
	{
		$nav .= " &gt; " . implode( " &gt; ", $navarray );
	}
	
	$title = $ipsclass->title ? $ipsclass->vars['board_name'].' &gt; '.$ipsclass->title : $ipsclass->vars['board_name'];
	
	$pages = "";
	
	if ( $ipsclass->pages )
	{
		$pages = LOFISKIN_pages( $ipsclass->pages );
	}
	
	$output = str_replace( '<% TITLE %>'    , $title    , $LOFISKIN['wrapper'] );
	$output = str_replace( '<% CONTENT %>'  , $content  , $output );
	$output = str_replace( '<% FULL_URL %>' , $fullurl  , $output );
	$output = str_replace( '<% COPYRIGHT %>', $copyright, $output );
	$output = str_replace( '<% NAV %>'      , $nav      , $output );
	$output = str_replace( '<% LINK %>'     , $ipsclass->real_link, $output );
	$output = str_replace( '<% LARGE_TITLE %>', $ipsclass->title ? $ipsclass->title : $ipsclass->vars['board_name'], $output );
	$output = str_replace( '<% PAGES %>'     , $pages, $output );
	$output = str_replace( "<% CHARSET %>"   , $ipsclass->vars['gb_char_set'], $output);
	
	//-----------------------------------------
	// Macros
	//-----------------------------------------
	
	$ipsclass->print->_unpack_macros();
	
	if ( is_array( $ipsclass->print->macros ) )
	{
		foreach( $ipsclass->print->macros as $row )
		{
			if ( $row['macro_value'] != "" )
			{
				$output = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $output );
			}
		}
	}
	
	$output = preg_replace( "#([^/])style_images/(<\#IMG_DIR\#>|".preg_quote($ipsclass->skin['_imagedir'], '/').")#is", "\\1".$ipsclass->vars['board_url']."/style_images/\\2", $output );
	$output = preg_replace( "#([\"'])style_emoticons/#is", "\\1".$ipsclass->vars['board_url']."/style_emoticons/", $output );
		
	$output = str_replace( "<#IMG_DIR#>", $ipsclass->skin['_imagedir'], $output );
	$output = str_replace( "<#EMO_DIR#>", $ipsclass->skin['_emodir']  , $output );
	
	
		
	print $output;
}






//--------------------------------
// Recursively get forums
//--------------------------------

function _get_forums()
{
	global $ipsclass, $forums, $LOFISKIN, $winpath;
	
	foreach( $ipsclass->forums->forum_cache['root'] as $forum_data )
	{
		if ( is_array($ipsclass->forums->forum_cache[ $forum_data['id'] ]) and count($ipsclass->forums->forum_cache[ $forum_data['id'] ]) )
		{
			$html_string .= LOFISKIN_forums_entry_first($forum_data, $winpath);
			
			$depth_guide = "";
			
			if ( is_array( $ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					if ( ! $forum_data['redirect_on'] )
					{
						$forum_data['total_posts'] = intval( $forum_data['topics'] + $forum_data['posts'] );
						
						$html_string .= LOFISKIN_forums_entry($depth_guide, $forum_data, $winpath );
						
						$html_string = _get_forums_internal( $forum_data['id'], $html_string, "   ".$depth_guide );
					}
				}
			}
			
			$html_string .= LOFISKIN_forums_entry_end($depth_guide);
		}
	}
	
	return $html_string;
}

function _get_forums_internal($root_id, $html_string="", $depth_guide="")
{
	global $ipsclass, $forums, $LOFISKIN, $winpath;
	
	if ( is_array( $ipsclass->forums->forum_cache[ $root_id ] ) )
	{
		$html_string .=  LOFISKIN_forums_entry_start($depth_guide);
	
		foreach( $ipsclass->forums->forum_cache[ $root_id ] as $forum_data )
		{
			if ( ! $forum_data['redirect_on'] )
			{
				$forum_data['total_posts'] = intval( $forum_data['topics'] + $forum_data['posts'] );
				
				$html_string .= LOFISKIN_forums_entry($depth_guide, $forum_data, $winpath );
				
				$html_string = _get_forums_internal( $forum_data['id'], $html_string, "    ".$depth_guide );
			}
		}
		
		$html_string .= LOFISKIN_forums_entry_end($depth_guide);
	}
	
	return $html_string;
}



function _get_nav_array($id)
{
	global $ipsclass, $forums, $LOFISKIN, $winpath;
	
	$navarray[] = "<a href='{$winpath}f{$id}.html'>{$ipsclass->forums->forum_by_id[$id]['name']}</a>";
	
	$ids = $ipsclass->forums->forums_get_parents( $id );
	
	if ( is_array($ids) and count($ids) )
	{
		foreach( $ids as $id )
		{
			$data = $ipsclass->forums->forum_by_id[$id];
		
			$navarray[] = "<a href='{$winpath}f{$data['id']}.html'>{$data['name']}</a>";
		}
	}
	
	return array_reverse($navarray);
}


function _get_pages( $total, $pp, $id )
{
	global $ipsclass, $forums, $LOFISKIN, $navarray, $winpath;
	
	$page_array = array();
	
	//-----------------------------------------------
	// Get the number of pages
	//-----------------------------------------------
	
	$pages = ceil( $total / $pp );
	
	$pages = $pages ? $pages : 1;
	
	if ( $pages < 2 )
	{
		return "";
	}
	
	//-----------------------------------------------
	// Loppy loo
	//-----------------------------------------------
	
	if ($pages > 1)
	{
		for( $i = 0; $i <= $pages - 1; ++$i )
		{
			$RealNo = $i * $pp;
			$PageNo = $i+1;
			
			$page_array[] = "<a href='{$winpath}{$id}-{$RealNo}.html'>{$PageNo}</a>";
		}
		
	}
	
	return implode( ", ", $page_array );
}

//+-------------------------------------------------
// GLOBAL ROUTINES
//+-------------------------------------------------

function fatal_error($message="", $help="")
{
	echo("$message<br><br>$help");
	exit;
}




?>