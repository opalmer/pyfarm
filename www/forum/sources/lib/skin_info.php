<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+--------------------------------------------------------------------------
*/

$css_names = array(

'BODY' => "Web Page Body",
'TABLE, TR, TD' => "Default table",
'a:link, a:visited, a:active' => "Default link",
'a:hover' => "Default link hover",
'.googlebottom, .googlebottom a:link, .googlebottom a:visited, .googlebottom a:active' => "Simple Search Results: Dark",
'.googlish, .googlish a:link, .googlish a:visited, .googlish a:active' => "Simple Search Results: Topic Link",
'.googlesmall, .googlesmall a:link, .googlesmall a:active, .googlesmall a:visited' => "Simple Search Results: Small Text",
'option.sub' => "Search Form: Select forums box: subcat colour",
'.caldate' => "Calendar: Date",
'#ucpmenu' => "User CP: Menu colour",
'#ucpcontent' => "User CP: Content colour",
'#logostrip'  => "Board Header: Logo Strip",
'#submenu' => "Board Header: Icon Strip",
'#submenu a:link, #submenu a:visited, #submenu a:active' => "Board Header: Icon Strip link colours",
'#userlinks' => "Board Header: Member Bar",
'.activeuserstrip' => 'Forums & Topics: Active users bar',
'.tablesubheader'      => 'Global: Sub heading',
'.pformleft'       => 'Global: left side table cell',
'.pformleftw'      => 'Global: left side table cell wide',
'.pformright'      => 'Global: right side table cell',
'.post1'           => 'Post View: Alt colour #1',
'.post2'		   => 'Post View: Alt colour #2',
'.postlinksbar'     => 'Post View: Track topic, etc links',
'.row1'			  => 'Global: Alt row #1',
'.row2'			  => 'Global: Alt row #2',
'.row3'			  => 'Global: Alt row #3',
'.row4'			  => 'Global: Alt row #4',
'.darkrow1'		  => 'Global: Alt dark row #1',
'.darkrow2'		  => 'Global: Alt dark row #2',
'.darkrow3'		  => 'Global: Alt dark row #3',
'.hlight'		  => 'Msgr: Inbox row selected',
'.dlight'		  => 'Msgr: Inbox row not selected',
'.tablesubheader'	  => 'Global: Sub heading (minor)',
'.tablesubheader a:link, .tablesubheader a:visited, .tablesubheader a:active' =>'Global: Sub heading (minor) Links',
'.maintitle'     => 'Global: Main table heading',
'.maintitle a:link, .maintitle a:visited, .maintitle a:active' => 'Global: Main table heading Links',
'.plainborder'   => 'Global: Table alt #1',
'.tableborder'   => 'Global: Table cellspacing colour',
'.tablefill'     => 'Global: Table alt #2',
'.tablepad'		 => 'Global: Table alt #3',
'.desc'			 => 'Forum View: Last post info',
'.signature'	 => 'Post View: Signature',
'.normalname'	 => 'Post View: Member name (reg)',
'.unreg'		 => 'Post View: Unreg name',
'.searchlite'    => 'Global: Search highlighting',
'#QUOTE'         => 'Quote Box',
'#CODE'		     => 'Code Box',

);

$skin_names = array(

					 'skin_boards'  => array(
					 						   'Board Index',
					 						   'Elements for the board index listing (shows you all the forums and is the first page you see when visiting the board) such as
					 						   forum link HTML, active user overview HTML, board statistics overview HTML, today\'s birthdays overview HTML',
					 						   'act=idx'
					 						),
					 						
					 'skin_buddy'   => array(
					 							'MyAssistant',
					 							'Elements for the myAssistant feature, including links and search boxes',
					 							'act=buddy',
					 						),
					 'skin_bugtracker'=> array(
					 							'BugTracker',
					 							'Elements for the IPB BugTracker',
					 							''
					 						),
					 'skin_calendar'=> array(
					 							'Calendar',
					 							'Elements for the calendar, including calendar view, view events',
					 							'act=calendar'
					 						),
				     'skin_chatpara'=> array(
					 							'Parachat',
					 							'Elements for the Parachat Chat Room',
					 							''
					 						),
					 'skin_chatsigma'=> array(
					 							'SigmaChat',
					 							'Elements for the SigmaChat Chat Room',
					 							''
					 						),
					 'skin_editors'  => array(
					 							'Post / PM Editor',
					 							'Elements from the standard and rich text editors used when creating a new PM or post.',
					 							'',
					 						),
					 'skin_emails'  => array(
					 							'Member Contact',
					 							'Elements from various contact methods, such as email form and report post to moderator form',
					 							'',
					 						),
					 'skin_forum'   => array(
					 							'Forum Index',
					 							'Elements for forum view (list all topics in forum). Includes forum log in window, forum rules',
					 							'act=SF&f=1',
					 						),
					 'skin_global'  => array(
					 							'All Global HTML',
					 							'Various HTML elements such as board header, redirect page, error page',
					 						),
					 'skin_help'	=> array(	
					 							'Help',
					 							'Elements for the help screen, including search boxes and view help files',
					 							'act=help'
					 						),
					 'skin_legends' => array(
					 							'Board Legends',
					 							'Elements for the "view all emoticons" windows, and the "Find User"',
					 							'act=legends'
					 						),
					 'skin_login'   => array(
					 							'Log In',
					 							'Elements for the log in form',
					 							'act=Login'
					 						),
					 'skin_mlist'   => array(
					 							'Member List',
					 							'Elements for the member list views',
					 							'act=Members'
					 						),
					 'skin_mod'		=> array(
					 							'Moderator Function',
					 							'Elements for the moderator tools such as delete topic, edit topic and the moderator CP',
					 						),
					 'skin_msg'     => array(
					 							'Messenger',
					 							'Elements for the messenger, such as view inbox, view message, archive messages, etc',
					 							'act=Msg',
					 						),
					 'skin_online'  => array (
					 							'Online List',
					 							'Elements for the "Show all online users" link',
					 							'act=Online',
					 						),
					 'skin_poll'    => array (
					 							'View Poll',
					 							'Elements for the poll view, includes vote form and vote results',
					 						),
					 'skin_post'    => array(
					 							'Post Screen',
					 							'Elements for the post screens such as new topic, reply to topic, quote post, edit post, some messenger new PM sections, some calender new event',
					 						),
					 'skin_printpage'=> array(
					 							'Printable Topic',
					 							'Elements for displaying the "printable topic" page',
					 						),
					 'skin_profile'	=> array(
					 							'Profile View',
					 							'Elements for viewing a members profile',
					 							'showuser=1',
					 						),
					 'skin_register' => array(
					 							'Register',
					 							'Elements for the register form, validate account form and COPPA forms',
					 							'act=Reg',
					 						),
					 'skin_search'   => array(
					 							'Search',
					 							'Elements for the search forum and the results view',
					 							'act=Search',
					 						),
					 'skin_stats'    => array(
					 							'Statistics',
					 							'Elements for various functions such as "View Moderating Team", "View todays top 10 posts", "View Active Topics"',
					 							'act=Stats',
					 						),
					 'skin_topic'	 => array(
					 							'Topic View',
					 							'Elements for the topic view screen, such as post view, inline attachment view, etc.',
					 							'showtopic=1'
					 						),
					 
					 'skin_modcp'	 => array(
					 							'Mod CP',
					 							'Elements for the moderators control panel sections.',
					 							'act=ModCP',
					 						),
					 						
					 'skin_portal'	 => array(
					 							'IPB Portal',
					 							'Elements for IPB Portal.',
					 						),
					 						
					 						
					 'skin_ucp'      => array(
					 							'User Control Panel',
					 							'Elements for the user control panel, such as the menu (also used in Messenger Views), edit profile, edit signature, edit avatar, etc.',
					 							'act=UserCP',
					 						),
					 						
					 						
					 'skin_subscriptions' => array(
					 							'Subscriptions Manager',
					 							'Elements for the subscription manager.',
					 						)
);


$bit_names = array();


?>