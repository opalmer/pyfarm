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

$CATS[]  = array( 'Users and Groups' );

$PAGES[] = array(
					 1  => array ( 'Manage Members'        , 'section=content&amp;act=mem&amp;code=search' ),
					 2  => array ( 'Add New Member'        , 'section=content&amp;act=mem&amp;code=add'  ),
					 3  => array ( 'Manage Ranks'          , 'section=content&amp;act=mem&amp;code=title'),
					 4  => array ( 'Manage User Groups'    , 'section=content&amp;act=group'         ),
					 5  => array ( 'Manage Validating'     , 'section=content&amp;act=mtools&amp;code=mod'  ),
					 6  => array ( 'Manage Locked'     	   , 'section=content&amp;act=mtools&amp;code=lock'  ),
					 9  => array ( 'Custom Profile Fields' , 'section=content&amp;act=field'         ),
					 11 => array ( 'IP Member Tools'       , 'section=content&amp;act=mtools'        ),
					 12 => array ( 'Member Settings'       , 'section=tools&amp;act=op&amp;code=findsetting&amp;key=userprofiles', '', 0, 1 ),
			       );
			       
$CATS[]  = array( 'Forum Control' );

$PAGES[] = array(
					 1 => array( 'Manage Forums'         	, 'section=content&amp;act=forum'                ),
					 2 => array( 'Add New Category'         , 'section=content&amp;act=forum&amp;code=new&amp;type=category'       ),
					 3 => array( 'Add New Forum'            , 'section=content&amp;act=forum&amp;code=new&amp;type=forum'       ),
					 4 => array( 'Manage Permissions'      	, 'section=content&amp;act=group&amp;code=permsplash'),
					 // 6 => array( 'Moderators'            , 'section=content&amp;act=mod'                  ),
					 7 => array( 'Topic Multi-Moderation'	, 'section=content&amp;act=multimod'          ),
					 8 => array( 'Trash Can Set-Up'      	, 'section=tools&amp;act=op&amp;code=findsetting&amp;key=trashcanset-up', '', 0, 1 ),
			       );
			       
$CATS[]  = array( 'Subscriptions' );

$PAGES[] = array(
					 1 => array( 'Manage Payment Gateways'   , 'section=content&amp;act=msubs&amp;code=index-gateways' ),
					 2 => array( 'Manage Packages'           , 'section=content&amp;act=msubs&amp;code=index-packages' ),
					 3 => array( 'Manage Transactions'       , 'section=content&amp;act=msubs&amp;code=index-tools' ),
					 4 => array( 'Manage Currencies'         , 'section=content&amp;act=msubs&amp;code=currency' ,  ),
					 5 => array( 'Manually Add Transaction'  , 'section=content&amp;act=msubs&amp;code=addtransaction' ),
					 6 => array( 'Install Payment Gateways'  , 'section=content&amp;act=msubs&amp;code=install-index' ),
					 9 => array( 'Subscription Settings'     , 'section=tools&amp;act=op&amp;code=findsetting&amp;key='.urlencode('subscriptionsmanager'), '', 0, 1 ),
				  
			       );
			       
$CATS[]  = array( 'Calendars' );

$PAGES[] = array(
					1 => array( 'Calendar Manager' , 'section=content&amp;act=calendars&amp;code=calendar_list' ),
					2 => array( 'Add New Calendar' , 'section=content&amp;act=calendars&amp;code=calendar_add'  ),
			       );
			       
$CATS[]  = array( 'RSS Management' );

$PAGES[] = array(
					1 => array( 'RSS Export Manager' , 'section=content&amp;act=rssexport&amp;code=rssexport_overview'        ),
					2 => array( 'RSS Import Manager' , 'section=content&amp;act=rssimport&amp;code=rssimport_overview'    ),
			       );
			       
$CATS[]  = array( 'Custom BBCode' );

$PAGES[] = array(
					1 => array( 'Custom BBCode Manager' , 'section=content&amp;act=bbcode&amp;code=bbcode'        ),
					2 => array( 'Add New BBCode'        , 'section=content&amp;act=bbcode&amp;code=bbcode_add'    ),
			       );
			       
$CATS[]  = array( 'Word &amp; Ban Filters' );

$PAGES[] = array(
					1 => array( 'Manage Badword Filters', 'section=content&amp;act=babw&amp;code=badword'     ),
					2 => array( 'Manage Ban Filters'    , 'section=content&amp;act=babw&amp;code=ban'  ),
			       );
			       
$CATS[]  = array( 'Attachments' );

$PAGES[] = array(
					1 => array( 'Attachment Types'      , 'section=content&amp;act=attach&amp;code=types'  ),
					2 => array( 'Attachment Stats'      , 'section=content&amp;act=attach&amp;code=stats'  ),
					3 => array( 'Attachment Search'     , 'section=content&amp;act=attach&amp;code=search'  ),
			       );
			  

?>