<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > CONTROL PANEL (COMPONENTS) PAGES FILE
|   > Script written by Matt Mecham
|   > Date started: Tue. 15th February 2005
|
+---------------------------------------------------------------------------
*/

//===========================================================================
// Simple library that holds all the links for the admin cp
// THIS PAGE CLASS: Generate menu from DB
//===========================================================================

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

global $ipsclass;

$CATS  = array();
$PAGES = array();

//--------------------------------
// Get info from DB
//--------------------------------

foreach( $ipsclass->menu_components as $r )
{
	//--------------------------------
	// Process data
	//--------------------------------
	
	$menu_data = unserialize( $r['com_menu_data'] );
	$tmp_pages = array();
	
	//--------------------------------
	// Do we have any menu links?
	//--------------------------------

	if ( is_array( $menu_data ) and count( $menu_data ) )
	{
		//--------------------------------
		// First item is title...
		//--------------------------------	

		$CATS[] = array( $r['com_title'] );

		foreach( $menu_data as $menu )
		{
			if ( $menu['menu_text'] AND $menu['menu_url'] )
			{
				if ( $menu['menu_redirect'] )
				{
					$tmp_pages[] = array( $menu['menu_text'], $menu['menu_url'], "", 0, 1 );
				}
				else
				{
					$tmp_pages[] = array( $menu['menu_text'], 'section=components&amp;act='.$r['com_section'].'&amp;'.$menu['menu_url'] );
				}
			}
		}
		
		$PAGES[] = $tmp_pages;
	}
}

?>