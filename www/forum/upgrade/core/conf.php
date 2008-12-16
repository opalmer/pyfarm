<?php

/**
* Invision Power Board
*
* ROOT PATH
*
* If __FILE__ is not supported, try ./sitecontrol and
* turn off "USE_SHUTDOWN" or manually add in
* the full path
* @since 2.0.0.2005-01-01
*/
define( 'INS_ROOT_PATH', str_replace( "//", "/", preg_replace( "#/core$#is", "", str_replace( "\\", "/", dirname( __FILE__ ) ) ) ) . "/" );

/**
* DOC ROOT PATH
*
* If __FILE__ is not supported, try ./sitecontrol and
* turn off "USE_SHUTDOWN" or manually add in
* the full path
* @since 2.0.0.2005-01-01
*/
define( 'ROOT_PATH', preg_replace( "#/{1,}#", "/", str_replace( "/upgrade/core", "", str_replace( "\\", "/", dirname( __FILE__ ) ) ) ) . "/" );

/**
* SQL FILE
* Use this to add a SQL file
*/
define( 'INS_SQL_FILE', ROOT_PATH . '/sources/sql/<%driver%>_admin_queries.php' );

/**
* SQL DEFAULT PREFIX
*/
define( 'INS_DEFAULT_SQL_PREFIX', 'ibf_' );

/**
* IPS_KERNEL Location
*/
define( 'KERNEL_PATH', ROOT_PATH . "ips_kernel/" );

error_reporting ( E_ERROR | E_WARNING | E_PARSE );
/*@ini_set('display_errors', 1);*/

/*if( @ini_get('memory_limit') )
{
	@ini_set('memory_limit', '200M' );
}*/

set_magic_quotes_runtime( 0 );

define ( 'IN_ACP', 1 );
define ( 'IN_IPB', 1 );
define ( 'IN_DEV', 0 );
define ( 'USE_SHUTDOWN', 0 );
define ( 'SAFE_MODE_ON', 0 );
define ( 'IPB_THIS_SCRIPT', 'admin');
define ( 'IPB_INIT_DONE', 1 );

?>