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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > MODULE INIT FILE
|   > Module written by Matt Mecham
|   > Date started: Wed 20th April 2005 (16:28)
|
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/*
+--------------------------------------------------------------------------
|   This module has one function:
|   run_init: Do any work you want to do before the caches are loaded and
|             processed
+--------------------------------------------------------------------------
*/

//-----------------------------------------
// This must always be 'component_init'
//-----------------------------------------

class component_init
{
	var $ipsclass;
	
	/*-------------------------------------------------------------------------*/
	// run_init
	// Do any work before the caches are loaded.
	// ADD to $this->ipsclass->cache_array()
	// DO NOT overwrite it or call $this->ipsclass->cache_array = array(...);
	// As the array has already been started off by IPB in index.php
	/*-------------------------------------------------------------------------*/
	
	function run_init()
	{
		$this->ipsclass->cache_array[] = 'chatting';
	}
		
	

}

?>