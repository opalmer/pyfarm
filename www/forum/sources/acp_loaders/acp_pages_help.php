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

// CAT_ID => array(  PAGE_ID  => (PAGE_NAME, URL ) )

// $PAGES[ $cat_id ][$page_id][0] = Page name
// $PAGES[ $cat_id ][$page_id][1] = Url
// $PAGES[ $cat_id ][$page_id][2] = Look for folder before showing
// $PAGES[ $cat_id ][$page_id][3] = URL type: 1 = Board URL 0 = ACP URL
// $PAGES[ $cat_id ][$page_id][4] = Item icon: 1= redirect 0 = Normal
// $PAGES[ $cat_id ][$page_id][5] = Regular link - open exact link in new window

global $ipsclass;

$CATS  = array();
$PAGES = array();

/*$CATS[]  = array( 'Help & Support' );

$PAGES[] = array(
					0 => array( 'Submit Support Ticket' , 'section=help&amp;act=support&amp;code=support'   ),
					1 => array( 'IPB Knowledgebase' 	, 'section=help&amp;act=support&amp;code=kb'   ),
					2 => array( 'IPB Documentation' 	, 'section=help&amp;act=support&amp;code=doctor'   ),
					3 => array( 'IPS Beyond - Resource Center' 	, 'section=help&amp;act=support&amp;code=ipsbeyond'   ),
					4 => array( 'Contact IPS' 			, 'section=help&amp;act=support&amp;code=contact'  ),
					5 => array( 'Feature Suggestions' 	, 'section=help&amp;act=support&amp;code=features'   ),
					6 => array( 'Bug Reports' 			, 'section=help&amp;act=support&amp;code=bugs'   ),
			       );*/

$CATS[]  = array( 'Diagnostics' );

$PAGES[] = array(
					0 => array( 'System Overview'		, "section=help&amp;act=diag' onclick='xmlobj = new ajax_request();xmlobj.show_loading()'"   ),
					//2 => array( 'Version Checker' 		, "section=help&amp;act=diag&amp;code=fileversions' onclick='xmlobj = new ajax_request();xmlobj.show_loading()'"   ),
					3 => array( 'Database Checker' 		, "section=help&amp;act=diag&amp;code=dbchecker' onclick='xmlobj = new ajax_request();xmlobj.show_loading()'"   ),
					4 => array( 'Database Index Checker' , "section=help&amp;act=diag&amp;code=dbindex' onclick='xmlobj = new ajax_request();xmlobj.show_loading()'"   ),
					5 => array( 'File Permissions Checker' , "section=help&amp;act=diag&amp;code=filepermissions' onclick='xmlobj = new ajax_request();xmlobj.show_loading()'"   ),
					6 => array( 'Whitespace Checker' 	, "section=help&amp;act=diag&amp;code=whitespace' onclick='xmlobj = new ajax_request();xmlobj.show_loading()'"   ),
					7 => array( 'Security Center' 		, "section=admin&amp;act=security", 0, 0, 1   ),
			       );	

?>