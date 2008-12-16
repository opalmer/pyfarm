<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2007-10-23 11:41:30 -0400 (Tue, 23 Oct 2007) $
|   > $Revision: 1141 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > INIT (Yes, it is)
|   > Basic class to initialize defines and other stuff
|   > Date started: Wednesday 19th July 2005 (14:57)
|
+--------------------------------------------------------------------------
*/

if ( @function_exists( 'memory_get_usage' ) )
{
	define( 'IPB_MEMORY_START', memory_get_usage() );
}

/**
* INIT File
*
* Sets up globals
*
* @package		InvisionPowerBoard
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

//-----------------------------------------------
// USER CONFIGURABLE ELEMENTS
//-----------------------------------------------
/**
* ROOT PATH
*
* If __FILE__ is not supported, try ./ and
* turn off "USE_SHUTDOWN" or manually add in
* the full path
* @since 2.0.0.2005-01-01
*/
define( 'ROOT_PATH', dirname( __FILE__ ) ."/" );

//-----------------------------------------------
// Security features
//-----------------------------------------------

/**
* Directory name for the admin folder
* @since 2.2.0.2006-11-06
*/
define( 'IPB_ACP_DIRECTORY', 'admin' );

/**
* DEV MODE
*
* Turns IPB into 'developers' mode which enables
* some debugging and other tools. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.0.0.2005-01-01
*/
define ( 'IN_DEV', 0 );

/**
* SQL DEBUG MODE
*
* Turns on SQL debugging mode. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
define ( 'IPS_SQL_DEBUG_MODE', 0 );

/**
* MEMORY DEBUG MODE
*
* Turns on MEMORY debugging mode. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
define ( 'IPS_MEMORY_DEBUG_MODE', 0 );

/**
* Write to debug file?
* Enter relative / full path into the constant below
* Remove contents to turn off debugging.
* WARNING: If you are passing passwords and such via XML_RPC
* AND wish to debug, ensure that the debug file ends with .php
* to prevent it loading as plain text via HTTP which would show
* the entire contents of the file.
* @since 2.2.0.2006-11-06
*/
define( 'IPS_XML_RPC_DEBUG_ON'  , 0 );
define( 'IPS_XML_RPC_DEBUG_FILE', ROOT_PATH . 'cache/xmlrpc_debug.cgi' );


/**
* Allow IP address matching when dealing with ACP sessions
* @since 2.2.0.2006-06-30
*/
define( 'IPB_ACP_IP_MATCH', 1 );

/**
* Allow exec.url="" tags
* Turning this on is a potential security risk as a malicious user
* could, with access to your ACP, add in an executable
* shell script which would allow root access to your
* server!
*/
define( 'IPB_ACP_ALLOW_EXEC_URL', 0 );

/**
* Allow PHP tags in template HTML?
* Turning this on is a potential security risk as a malicious user
* could, with access to your ACP, add in an executable
* shell script which would allow root access to your
* server!
*/
define( 'IPB_ACP_ALLOW_TEMPLATE_PHP', 0 );

/**
* Make the IPB portal your default forum page?
*/
define( 'IPB_MAKE_PORTAL_HOMEPAGE', 0 );

/**
* Use GZIP page compression in the ACP
* @since 2.2.0.2006-06-30
*/
define( 'IPB_ACP_USE_GZIP', 1 );

//-----------------------------------------------
// Other
//-----------------------------------------------

/**
* USE SHUT DOWN
*
* Enable shut down features?
* Uses PHPs register_shutdown_function to save
* low priority tasks until end of exec
* @since 2.0.0.2005-01-01
*/
define ( 'USE_SHUTDOWN', IPB_THIS_SCRIPT == 'public' ? 1 : 0 );

/**
* IPS KERNEL PATH
*
* @since 2.0.0.2005-01-01
*/
define( 'KERNEL_PATH'  , ROOT_PATH.'ips_kernel/' );

/**
* LEGACY MODE
*
* Legacy mode? Will enable hacks for 2.0 to work
* with a little modification
* @since 2.1.0.2005-07-12
*/
define( 'LEGACY_MODE', 0 );

/**
* USE MODULES
*
* Enable module usage?
* (Vital for some mods and IPB enhancements)
* @since 2.0.0.2005-01-01
*/
define ( 'USE_MODULES', 1 );

/**
* CUSTOM ERROR
*
* Enable custom error handling?
* Useful to trap skin errors, etc
* @since 2.0.0.2005-01-01
*/
define( 'CUSTOM_ERROR', 0 );

/**
* TRIAL VERSION
*
* Seriously, like, leave this alone
* @since 2.0.0.2005-01-01
*/
define( 'TRIAL_VERSION', 0 );

/**
* Version numbers
*
* @since 2.0.0.2005-01-01
*/
define ( 'IPBVERSION', '2.3.3' );
define ( 'IPB_LONG_VERSION', '23006' );

//-----------------------------------------------
// NO USER EDITABLE SECTIONS BELOW
//-----------------------------------------------

@set_magic_quotes_runtime(0);
error_reporting  (E_ERROR | E_WARNING | E_PARSE);

// This is for developing to catch notices - leave OFF!
//error_reporting( E_ALL | E_NOTICE);

/**
* IN IPB
*
* @since 2.0.0.2005-01-01
*/
define ( 'IN_IPB', 1 );

/**
* IN ACP
*
* @since 2.0.0.2005-01-01
*/
define ( 'IN_ACP', 1 );

/**
* SAFE MODE
*
* Seriously, like, leave this alone too
* @since 2.0.0.2005-01-01
*/
if ( IPB_THIS_SCRIPT != 'public' )
{
	if ( function_exists('ini_get') )
	{
		define ( 'SAFE_MODE_ON', @ini_get("safe_mode") ? 1 : 0 );
	}
	else
	{
		define ( 'SAFE_MODE_ON', 1 );
	}
}
else
{
	define ( 'SAFE_MODE_ON', 0 );
}

/**
* INITIATED
*
* Seriously, like, leave this alone too
* @since 2.1.0.2005-07-19
*/
define ( 'IPB_INIT_DONE', 1 );


/**
* Fix for PHP 5.1.x warning
*
* Sets default time zone to server time zone
* @since 2.2.0.2006-05-19
*/

if ( function_exists( 'date_default_timezone_set' ) )
{
    date_default_timezone_set( date_default_timezone_get() );
}


//===========================================================================
// DEBUG CLASS
//===========================================================================

/**
* Debug class
*
* @package	InvisionPowerBoard
* @author   Matt Mecham
* @version	2.1
*/
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

/*-------------------------------------------------------------------------*/
// GLOBAL ROUTINES
/*-------------------------------------------------------------------------*/

/**
* Fatal error
*
* @param	string	Message
* @param	string	Help
* @return	void
*/
function fatal_error($message="", $help="")
{
	echo("$message<br /><br />$help");
	exit;
}


/*-------------------------------------------------------------------------*/
// Custom error handler
/*-------------------------------------------------------------------------*/

/**
* Custom error handler
*
* @param	integer	Error number
* @param	string	Error string
* @param	string	Error file
* @param	string	Error line number
* @return	void
*/
function my_error_handler( $errno, $errstr, $errfile, $errline )
{
	global $ipsclass;
	
	// Did we turn off errors with @?
	
	if ( ! error_reporting() )
	{
		return;
	}
	
	$errfile = str_replace( @getcwd(), "", $errfile );
	
	switch ($errno)
	{
  		case E_ERROR:
   			echo "<b>IPB ERROR</b> [$errno] $errstr (Line: $errline of $errfile)<br />\n";
   			
   			if( is_object($ipsclass) )
   			{
	   			$ipsclass->DB->close_db();
   			}
   			
   			exit(1);
   		break;
  		case E_WARNING:
  			if ( strstr( $errstr, 'load_template(./skin_cache/cacheid_' ) )
  			{
  				if ( IPB_THIS_SCRIPT != 'admin' )
  				{
					echo "<div style='font-family:sans-serif'><b>IPB TEMPLATE ERROR:</b> Could not load the required template.
						  <br /><br />First, try and remove any custom skin settings by clicking <a href='index.php?setskin&id=0'>here</a>
						  <br /><br />Then, please visit your <a href='" . IPB_ACP_DIRECTORY . "/index.php'>Admin Control Panel</a> to repair this template.
						  <br /><br /><span style='font-size:90%;color:gray'>Error: $errstr</span></div>";
				}
				else
				{
					skin_emergency();
				}
  			}
  			else
  			{
   				echo "<b>IPB WARNING</b> [$errno] $errstr (Line: $errline of $errfile)<br />\n";
   			}
   		break;
 		default:
   			//Do nothing
   		break;
	}
}

//-----------------------------------------------
// Use custom handler?
// Moved due to possible php bug? Bug id# 1245
//-----------------------------------------------

if ( CUSTOM_ERROR )
{
	set_error_handler("my_error_handler");
}


?>