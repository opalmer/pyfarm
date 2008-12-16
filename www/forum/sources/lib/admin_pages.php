<?php
//===========================================================================
// Simple library that holds all the links for the admin cp
// Invision Power Board
//===========================================================================

// CAT_ID => array(  PAGE_ID  => (PAGE_NAME, URL ) )

// $PAGES[ $cat_id ][$page_id][0] = Page name
// $PAGES[ $cat_id ][$page_id][1] = Url
// $PAGES[ $cat_id ][$page_id][2] = Look for folder before showing
// $PAGES[ $cat_id ][$page_id][3] = URL type: 1 = Board URL 0 = ACP URL
// $PAGES[ $cat_id ][$page_id][4] = Item icon: 1= redirect 0 = Normal

$PAGES = array(
				# SETTINGS
				
				100 => array (
							
							 1 => array( 'View All General Settings', 'act=op' ),
							 2 => array( 'Add New General Setting'  , 'act=op&code=settingnew' ),
							 7 => array( 'Turn Board On / Off'      , 'act=op&code=findsetting&key='.urlencode('boardoffline/online'), '', 0, 1 ),
							 8 => array( 'Board Guidelines'         , 'act=op&code=findsetting&key='.urlencode('boardguidelines'), '', 0, 1 ),
							 9 => array( 'General Configuration'    , 'act=op&code=findsetting&key='.urlencode('generalconfiguration'), '', 0, 1 ),
							 10 => array( 'CPU Saving'              , 'act=op&code=findsetting&key='.urlencode('cpusaving&optimization'), '', 0, 1 ),
							 11 => array( 'IP Chat'                 , 'act=pin&code=ipchat'  ),
							 12 => array( 'IPB License'             , 'act=pin&code=reg'     ),
							 14 => array( 'IPB Copyright Removal'   , 'act=pin&code=copy'    ),
						   ),
						   
			    # MEMBER MANAGEMENT
						   
				200 => array (
							 1 => array( 'New Forum'             , 'act=forum&code=new'       ),
							 2 => array( 'Manage Forums'         , 'act=forum'                ),
							 3 => array( 'Permission Masks'      , 'act=group&code=permsplash'),
							 6 => array( 'Moderators'            , 'act=mod'                  ),
							 7 => array( 'Topic Multi-Moderation', 'act=multimod'          ),
							 8 => array( 'Trash Can Set-Up'      , 'act=op&code=findsetting&key=trashcanset-up', '', 0, 1 ),
						   ),
						   
						   
				300 => array (
				            1  => array ( 'Manage Members'        , 'act=mem&code=search' ),
							2  => array ( 'Add New Member'        , 'act=mem&code=add'  ),
							6  => array ( 'Manage Ranks'          , 'act=mem&code=title'),
							7  => array ( 'Manage User Groups'    , 'act=group'         ),
							8  => array ( 'Manage Validating'     , 'act=mem&code=mod'  ),
							9  => array ( 'Custom Profile Fields' , 'act=field'         ),
							11 => array ( 'IP Member Tools'       , 'act=mtools'        ),
							12 => array ( 'Member Settings'       , 'act=op&code=findsetting&key=userprofiles', '', 0, 1 ),
						   ),
				
					   
				400 => array(
							 1 => array( 'Manage Payment Gateways'   , 'act=msubs&code=index-gateways' ),
							 2 => array( 'Manage Packages'           , 'act=msubs&code=index-packages' ),
							 3 => array( 'Manage Transactions'       , 'act=msubs&code=index-tools' ),
							 4 => array( 'Manage Currencies'         , 'act=msubs&code=currency' ,  ),
							 5 => array( 'Manually Add Transaction'  , 'act=msubs&code=addtransaction' ),
							 6 => array( 'Install Payment Gateways'  , 'act=msubs&code=install-index' ),
							 9 => array( 'Subscription Settings'     , 'act=op&code=findsetting&key='.urlencode('subscriptionsmanager'), '', 0, 1 ),
						   ),
				
				# POST MANAGEMENT
				
				500 => array (
							1 => array( 'Attachment Types'      , 'act=attach&code=types'  ),
							2 => array( 'Attachment Stats'      , 'act=attach&code=stats'  ),
							3 => array( 'Attachment Search'     , 'act=attach&code=search'  ),
				  			),
				  			
				  			
				600 => array(
							1 => array( 'Custom BBCode Manager' , 'act=admin&code=bbcode'        ),
							2 => array( 'Add New BBCode'        , 'act=admin&code=bbcode_add'    ),
						   ),
						   
				700 => array(
							1 => array( 'Emoticon Manager'      , 'act=admin&code=emo'               ),
							2 => array( 'Import/Export Packs'   , 'act=admin&code=emo_packsplash'    ),
						   ),		   
						   
				800 => array (
							1 => array( 'Manage Badword Filters', 'act=admin&code=badword'     ),
							6 => array( 'Manage Ban Filters'    , 'act=admin&code=ban'  ),
							),		
				
				# SKINS & LANGS
				
				900 => array (
							1 => array( 'Skin Manager'            , 'act=sets'        ),
							2 => array( 'Skin Tools'              , 'act=skintools'   ),
							3 => array( 'Skin Search & Replace'   , 'act=skintools&code=searchsplash'   ),
							4 => array( 'Skin Import/Export'      , 'act=import'      ),
							5 => array( 'Easy Logo Changer'       , 'act=skintools&code=easylogo'   ),
						   ),
						   			
				1000 => array (
							1 => array( 'Manage Languages'        , 'act=lang'             ),
							2 => array( 'Import a Language'       , 'act=lang&code=import' ),
						   ),
				
				
				# ADMIN
						   
				1100 => array (
							1 => array( 'Manage Help Files'     , 'act=help'                   ),
							2 => array( 'Cache Control'         , 'act=admin&code=cache'       ),
							3 => array( 'Recount & Rebuild'     , 'act=rebuild'                ),
							4 => array( 'Clean-up Tools'        , 'act=rebuild&code=tools'     ),
						   ),
						   
			    1200 => array(
			    			1  => array( 'Manage Bulk Mail'      , 'act=postoffice'                    ),
			    			2  => array( 'Create New Email'      , 'act=postoffice&code=mail_new'      ),
			    			3  => array( 'View Email Logs'       , 'act=emaillog', '', 0, 1 ),
			    			4  => array( 'View Email Error Logs' , 'act=emailerror', '', 0, 1 ),
			    			5  => array( 'Email Settings'        , 'act=op&code=findsetting&key=emailset-up', '', 0, 1 ),
			    		    ),
			    
			    1300 => array (
							 1 => array( 'Task Manager'        , 'act=task'                ),
							 2 => array( 'View Task Logs'      , 'act=task&code=log'       ),
						   ),
				
				
				1400 => array(
							 1 => array( 'Invision Gallery'        , 'act=gallery' ),
							 2 => array( '|-- Settings'            , 'act=op&code=findsetting&key='.urlencode('invisiongallerysettings'), '', 0, 0 ),
							 3 => array( '|-- Album Manager'       , 'act=gallery&code=albums'  , 'modules/gallery' ),
							 4 => array( '|-- Multimedia Manager'  , 'act=gallery&code=media'   , 'modules/gallery' ),
							 5 => array( '|-- Groups'              , 'act=gallery&code=groups'  , 'modules/gallery' ),  
							 6 => array( '|-- Stats'               , 'act=gallery&code=stats'   , 'modules/gallery' ),
							 7 => array( '|-- Tools'               , 'act=gallery&code=tools'   , 'modules/gallery' ),
							 8 => array( '&#039;-- Post Form'      , 'act=gallery&code=postform', 'modules/gallery' ),
						   ),
						   
				1450 => array(
							 1 => array( 'Community Blog'          , 'act=blog' ),
							 2 => array( 'Blog Settings'           , 'act=op&code=findsetting&key='.urlencode('communityblog'), '', 0, 1 ),
							 3 => array( 'Groups'				   , 'act=blog&amp;cmd=groups' ),
							 4 => array( 'Content Blocks'		   , 'act=blog&amp;cmd=cblocks' ),
							 5 => array( 'Tools'				   , 'act=blog&amp;cmd=tools' ),
						   ),
				
				1500 => array (
							1 => array( 'Registration Stats' , 'act=stats&code=reg'   ),
							2 => array( 'New Topic Stats'    , 'act=stats&code=topic' ),
							3 => array( 'Post Stats'         , 'act=stats&code=post'  ),
							4 => array( 'Personal Message'   , 'act=stats&code=msg'   ),
							5 => array( 'Topic Views'        , 'act=stats&code=views' ),
						   ),
						   
				1600 => array (
							1 => array( 'SQL Toolbox'     , 'act=mysql'           ),
							2 => array( 'SQL Back Up'     , 'act=mysql&code=backup'    ),
							3 => array( 'SQL Runtime Info', 'act=mysql&code=runtime'   ),
							4 => array( 'SQL System Vars' , 'act=mysql&code=system'    ),
							5 => array( 'SQL Processes'   , 'act=mysql&code=processes' ),
						   ),
				
				1700 => array(
							1 => array( 'View Moderator Logs'  , 'act=modlog'    ),
							2 => array( 'View Admin Logs'      , 'act=adminlog'  ),
							3 => array( 'View Email Logs'      , 'act=emaillog'  ),
							4 => array( 'View Email Error Logs', 'act=emailerror' ),
							5 => array( 'View Bot Logs'        , 'act=spiderlog' ),
							6 => array( 'View Warn Logs'       , 'act=warnlog'   ),
						   ),
			   );
			   
			   
$CATS = array (   
				  100 => array( "System Settings"   , '#caf2d9;margin-bottom:12px;' ),
				  
				  200 => array( 'Forum Control'     , '#F9FFA2' ),
				  300 => array( 'Users and Groups'  , '#F9FFA2' ),
				  400 => array( "Subscriptions"     , '#F9FFA2;margin-bottom:12px;' ),
				  
				  500 => array( "Attachments"       , '#f5cdcd' ),
				  600 => array( "Custom BBCode"     , '#f5cdcd' ),
				  700 => array( "Emoticons"         , '#f5cdcd' ),
				  800 => array( "Word & Ban Filters", '#f5cdcd;margin-bottom:12px;' ),
				  
				  900 => array( 'Skins & Templates' , '#DFE6EF' ),
				  1000 => array( 'Languages'        , '#DFE6EF;margin-bottom:12px;' ),
				  
				  1100 => array( 'Maintenance'      , '#caf2d9' ),
				  1200 => array( 'Post Office'      , '#caf2d9' ),
				  1300 => array( 'Task Manager'     , '#caf2d9;margin-bottom:12px;' ),
				  
				  1400 => array( "Invision Gallery" , '#F9FFA2;' ),
				  1450 => array( "Community Blog"   , '#F9FFA2;margin-bottom:12px;' ),
				  
				  1500 => array( 'Statistic Center' , '#f5cdcd' ),
				  1600 => array( 'SQL Management'   , '#f5cdcd' ),
				  1700 => array( 'Board Logs'       , '#f5cdcd' ),
			  );
			  

			  
$DESC = array (
				  100 => "Edit forum settings such as cookie paths, security features, posting abilities, etc",
				  
				  200 => "Create, edit, remove and re-order categories, forums and moderators",
				  300 => "Manage members, groups and ranks",
				  400 => "Manage your members' subscriptions and more",
				  
				  500 => "Manage your attachments",
				  600 => "Manage your custom BBCode",
				  700 => "Manage your emoticons and upload/download emoticon packs",
				  800 => "Manage your badword and ban filters",
				  
				  900 => "Manage templates, skins, colours and images.",
				  1000 => "Manage language sets",
				  
				  1100 => "Manage Help Files, Bad Word Filters and Emoticons",
				  1200 => "Manage your email and bulk mail members",
				  1300 => "Manage your scheduled tasks.",
				  
				  1400 => "Manage your gallery",
				  1450 => "Manage your community blog",
				  1500 => "Get registration and posting statistics",
				  1600 => "Manage your SQL database; repair, optimize and export data",
				  1700 => "View admin, moderator and email logs (Root admin only)",
			  );
?>