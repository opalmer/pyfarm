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

$CATS[]  = array( 'Skins & Templates' );

$PAGES[] = array(
					1 => array( 'Skin Manager'            , 'section=lookandfeel&amp;act=sets'        ),
					2 => array( 'Skin Tools'              , 'section=lookandfeel&amp;act=skintools'   ),
					3 => array( 'Skin Search & Replace'   , 'section=lookandfeel&amp;act=skintools&amp;code=searchsplash'   ),
					4 => array( 'Skin Import/Export'      , 'section=lookandfeel&amp;act=import'      ),
					5 => array( 'Skin Differences'        , 'section=lookandfeel&amp;act=skindiff'      ),
					6 => array( 'Skin Remapping'          , 'section=lookandfeel&amp;act=skinremap'      ),
					7 => array( 'Easy Logo Changer'       , 'section=lookandfeel&amp;act=skintools&amp;code=easylogo'   ),
			       );
			       
$CATS[]  = array( 'Languages' );

$PAGES[] = array(
					 1 => array( 'Manage Languages'        , 'section=lookandfeel&amp;act=lang'             ),
					 2 => array( 'Import a Language'       , 'section=lookandfeel&amp;act=lang&amp;code=import' ),
			     );
			       
$CATS[]  = array( 'Emoticons' );

$PAGES[] = array(
					1 => array( 'Emoticon Manager'      , 'section=lookandfeel&amp;act=emoticons&amp;code=emo'               ),
					2 => array( 'Import/Export Packs'   , 'section=lookandfeel&amp;act=emoticons&amp;code=emo_packsplash'    ),
			       );
			       


?>