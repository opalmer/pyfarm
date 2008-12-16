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
|   > CONTROL PANEL PAGES FILE
|   > Script written by Matt Mecham
|   > Date started: Fri 8th April 2005 (12:07)
|
+---------------------------------------------------------------------------
*/

//===========================================================================
// Simple library that holds all the links for the admin cp
//===========================================================================

// CAT_ID => array(  PAGE_ID  => (PAGE_NAME, URL ) )

// $PAGES[ $cat_id ][$page_id][0] = Page name
// $PAGES[ $cat_id ][$page_id][1] = Url
// $PAGES[ $cat_id ][$page_id][2] = Look for folder before showing
// $PAGES[ $cat_id ][$page_id][3] = URL type: 1 = Board URL 0 = ACP URL
// $PAGES[ $cat_id ][$page_id][4] = Item icon: 1= redirect 0 = Normal

$CATS[]  = array( 'System Settings' );

$PAGES[] = array(
					 1 => array( 'View All General Settings', 'section=tools&amp;act=op' ),
					 2 => array( 'Add New General Setting'  , 'section=tools&amp;act=op&amp;code=settingnew' ),
					 3 => array( 'Manage Portal Plug-ins', 'section=tools&amp;act=portal' ),
					 7 => array( 'Turn Board On / Off'      , 'section=tools&amp;act=op&amp;code=findsetting&amp;key='.urlencode('boardoffline/online'), '', 0, 1 ),
					 8 => array( 'Board Guidelines'         , 'section=tools&amp;act=op&amp;code=findsetting&amp;key='.urlencode('boardguidelines'), '', 0, 1 ),
					 9 => array( 'General Configuration'    , 'section=tools&amp;act=op&amp;code=findsetting&amp;key='.urlencode('generalconfiguration'), '', 0, 1 ),
					 10 => array( 'CPU Saving'              , 'section=tools&amp;act=op&amp;code=findsetting&amp;key='.urlencode('cpusaving'), '', 0, 1 ),
					// 11 => array( 'IP Chat'                 , 'section=tools&amp;act=pin&amp;code=ipchat'  ),
					 //12 => array( 'IPB License'             , 'section=tools&amp;act=pin&amp;code=reg'     ),
					 //14 => array( 'IPB Copyright Removal'   , 'section=tools&amp;act=pin&amp;code=copy'    ),
				);
			       
$CATS[]  = array( 'Maintenance' );

$PAGES[] = array(
					1 => array( 'Manage Help Files'     , 'section=tools&amp;act=help'                   ),
					2 => array( 'Cache Control'         , 'section=tools&amp;act=admin&amp;code=cache'       ),
					3 => array( 'Recount &amp; Rebuild'     , 'section=tools&amp;act=rebuild'                ),
					4 => array( 'Clean-up Tools'        , 'section=tools&amp;act=rebuild&amp;code=tools'     ),
			       );
			       
$CATS[]  = array( 'Post Office' );

$PAGES[] = array(
					1  => array( 'Manage Bulk Mail'      , 'section=tools&amp;act=postoffice'                    ),
			    	2  => array( 'Create New Email'      , 'section=tools&amp;act=postoffice&amp;code=mail_new'      ),
			    	3  => array( 'View Email Logs'       , 'section=admin&amp;act=emaillog', '', 0, 1 ),
			    	4  => array( 'View Email Error Logs' , 'section=admin&amp;act=emailerror', '', 0, 1 ),
			    	5  => array( 'Email Settings'        , 'section=tools&amp;act=op&amp;code=findsetting&amp;key=emailset-up', '', 0, 1 ),
			       );

$CATS[]  = array( 'Log In Manager' );

$PAGES[] = array(
					1 => array( 'Log In Manager'    , 'section=tools&amp;act=loginauth'                    ),
					2 => array( 'Create New Log In' , 'section=tools&amp;act=loginauth&amp;code=login_add' ),
			       );
			       
$CATS[]  = array( 'Task Manager' );

$PAGES[] = array(
					1 => array( 'Task Manager'        , 'section=tools&amp;act=task'                ),
					2 => array( 'View Task Logs'      , 'section=tools&amp;act=task&amp;code=log'       ),
			       );
			       
			  

?>