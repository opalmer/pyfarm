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
|
|   > IPB UPGRADE MODULE:: IPB 2.0.2 -> IPB 2.0.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	var $install;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function version_upgrade( & $install )
	{
		$this->install = & $install;
	}
	
	/*-------------------------------------------------------------------------*/
	// Auto run..
	/*-------------------------------------------------------------------------*/

	function auto_run()
	{
		if( !defined( 'SQL_DRIVER' ) )
		{
			define( 'SQL_DRIVER', $this->install->saved_data['sql_driver'] );
		}
		
		if( !defined('CACHE_PATH') )
		{
			define( 'CACHE_PATH', ROOT_PATH );
		}
		
		//-----------------------------------------
		// Skip for safe_mode peeps
		//-----------------------------------------
		
		$safe_mode = @ini_get("safe_mode") ? 1 : 0;
		
		if( $safe_mode )
		{
			$this->install->message = "We detected that safe mode is enabled on your server.  Skipping IP.Board Pro skin insertion...";
			return true;
		}
		
		require_once( ROOT_PATH . 'sources/api/api_skins.php' );
		$api = new api_skins();
		$api->ipsclass =& $this->install->ipsclass;
		
		if( !function_exists('gzopen') )
		{
			$this->install->error = "You must have zlib support in your PHP installation to be able to import skins.  The new default IP.Board Pro Skin has not been imported";
			return true;
		}
		
		if( !$this->install->saved_data['didskin'] )
		{
			$api->skin_add_set( ROOT_PATH . 'resources/ipb_skin-pro.xml.gz' );
			$this->install->saved_data['didskin'] = 1;
			$this->install->message = "IP.Board Pro skin templates inserted";
			return false;
		}
		else
		{
			$id = $this->install->ipsclass->DB->build_and_exec_query( array( 'select' => 'set_skin_set_id', 'from' => 'skin_sets', 'where' => "set_key='ip.board_pro'" ) );
			
			$api->images_add_set( ROOT_PATH . 'resources/ipb_images-pro.xml.gz', $id['set_skin_set_id'] );
			
			unset($this->install->saved_data['didskin']);
			$this->install->message = "IP.Board Pro skin images inserted";
		}
		
		return true;
	}
	
}
	
	
?>