<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Services Kernel [DB Abstraction]
|	Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
+---------------------------------------------------------------------------
|   THIS IS NOT FREE / OPEN SOURCE SOFTWARE
+---------------------------------------------------------------------------
|
|   > MySQL Driver Module
|   > Module written by Matt Mecham
|   > Date started: Monday 28th February 2005 16:46 
|
|	> Module Version Number: 2.1.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Database Object Driver
*
* @package		IPS_KERNEL
* @subpackage	DatabaseAbstraction
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
 * Gateway for mysql/mysqli client
 *
 */
 
 //define( 'FORCE_MYSQL_ONLY', 1 );
 
//-----------------------------------------
// Define KERNEL_PATH if not
// defined - i.e. installation
//-----------------------------------------
 
if ( ! defined('KERNEL_PATH') )
{
 	if( defined('INS_KERNEL_PATH') )
 	{
	 	define( 'KERNEL_PATH', INS_KERNEL_PATH );
 	}
 	else
 	{
	 	define( 'KERNEL_PATH', str_replace( "//", "/", str_replace( "\\", "/", dirname( __FILE__ ) ) ) . "/" );
 	}
}

//-----------------------------------------
// MySQLi and PHP 5.0.5 don't get along well
//-----------------------------------------
 
$versions		= explode( ".", phpversion() );

if( $versions[0] == 5 AND $versions[1] == 0 AND $versions[2] == 5 )
{
	define( 'FORCE_MYSQL_ONLY', 1 );
}

if ( extension_loaded('mysqli') AND ! defined( 'FORCE_MYSQL_ONLY' ) )
{
	require( KERNEL_PATH."class_db_mysqli_client.php" );
}
else
{
	require( KERNEL_PATH."class_db_mysql_client.php" );
}

//-----------------------------------------
// Clean up
//-----------------------------------------

unset( $versions );

?>