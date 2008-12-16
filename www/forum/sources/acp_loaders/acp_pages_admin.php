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
			
$CATS[]  = array( 'Security Center' );

$PAGES[] = array(
					1 => array( 'Security Center'		 , 'section=admin&amp;act=security' ),
					2 => array( 'List All Administrators', 'section=admin&amp;act=security&amp;code=list_admins'  ),
					3 => array( 'Manage Restrictions' , 'section=admin&amp;act=acpperms&amp;code=acpp_list'   ),
			       );

$CATS[]  = array( 'Board Logs' );

$PAGES[] = array(
					1 => array( 'View Moderator Logs'  , 'section=admin&amp;act=modlog'    ),
					2 => array( 'View Admin Logs'      , 'section=admin&amp;act=adminlog'  ),
					3 => array( 'View Email Logs'      , 'section=admin&amp;act=emaillog'  ),
					4 => array( 'View Email Error Logs', 'section=admin&amp;act=emailerror' ),
					5 => array( 'View Bot Logs'        , 'section=admin&amp;act=spiderlog' ),
					6 => array( 'View Warn Logs'       , 'section=admin&amp;act=warnlog'   ),
					7 => array( 'View ACP Log In Logs' , 'section=admin&amp;act=loginlog'   ),
			       );
									
$CATS[]  = array( 'Components' );

$PAGES[] = array(
					1 => array( 'Manage Components'      , 'section=admin&amp;act=components'   ),
					2 => array( 'Register New Component' , 'section=admin&amp;act=components&amp;code=component_add' ),
			       );
			       
$CATS[]  = array( 'Statistic Center' );

$PAGES[] = array(
					1 => array( 'Registration Stats' , 'section=admin&amp;act=stats&amp;code=reg'   ),
					2 => array( 'New Topic Stats'    , 'section=admin&amp;act=stats&amp;code=topic' ),
					3 => array( 'Post Stats'         , 'section=admin&amp;act=stats&amp;code=post'  ),
					4 => array( 'Personal Message'   , 'section=admin&amp;act=stats&amp;code=msg'   ),
					5 => array( 'Topic Views'        , 'section=admin&amp;act=stats&amp;code=views' ),
			       );
			       
			       
$CATS[]  = array( 'SQL Management' );

$PAGES[] = array(
					1 => array( 'SQL Toolbox'     , 'section=admin&amp;act=sql'           ),
					2 => array( 'SQL Back Up'     , 'section=admin&amp;act=sql&amp;code=backup'    ),
					3 => array( 'SQL Runtime Info', 'section=admin&amp;act=sql&amp;code=runtime'   ),
					4 => array( 'SQL System Vars' , 'section=admin&amp;act=sql&amp;code=system'    ),
					5 => array( 'SQL Processes'   , 'section=admin&amp;act=sql&amp;code=processes' ),
			       );

$CATS[]  = array( 'API Management' );

$PAGES[] = array(
					1 => array( 'Manage XML-RPC Users', 'section=admin&amp;act=api&amp;code=api_list' ),
					2 => array( 'View XML-RPC Logs'   , 'section=admin&amp;act=api&amp;code=log_list' ),
			       ); 

?>