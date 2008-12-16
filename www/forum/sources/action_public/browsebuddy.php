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
|   > $Date: 2007-05-01 14:00:21 -0400 (Tue, 01 May 2007) $
|   > $Revision: 958 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Browse Buddy Module
|   > Module written by Matt Mecham
|   > Date started: 2nd July 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class assistant
{
	# Classes
	var $ipsclass;
	
	# Others
    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
	var $ajaxml     = 0;
	
	/*-------------------------------------------------------------------------*/
	// AUTO RUN
	/*-------------------------------------------------------------------------*/
	
    function auto_run()
    {
    	//-----------------------------------------
    	// Ajax request?
    	//-----------------------------------------
    	
    	$this->ajaxml = intval( $this->ipsclass->input['xml'] );
    	
    	//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_buddy');
    	
    	$this->ipsclass->load_template('skin_buddy');
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['code'])
    	{
    		default:
    			$this->ajaxml ? $this->xml_splash() : $this->splash();
    			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->output = str_replace( "<!--CLOSE.LINK-->", $this->ipsclass->compiled_templates['skin_buddy']->closelink(), $this->output );
    	
    	if ( ! $this->ajaxml )
    	{
    		$this->ipsclass->print->pop_up_window($this->ipsclass->lang['page_title'], $this->ipsclass->compiled_templates['skin_buddy']->buddy_js().$this->output);
       	}
       	else
       	{
       		@header( "Content-type: text/plain;charset={$this->ipsclass->vars['gb_char_set']}" );
       		print $this->output;
       		exit();
       	}
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// Build menu tree
	/*-------------------------------------------------------------------------*/
	
	function xml_build_tabs()
	{
		$this->ipsclass->input['tab'] = $this->ipsclass->input['tab'] ? $this->ipsclass->input['tab'] : 'info';
		
		$onoff['info']   = 'pp-taboff';
		$onoff['posts']  = 'pp-taboff';
		$onoff['search'] = 'pp-taboff';
		$onoff['pms']    = 'pp-taboff';
		$onoff['newpms'] = 'pp-taboff';
		
		$onoff[ $this->ipsclass->input['tab'] ] = 'pp-tabon';
		
		return $this->ipsclass->compiled_templates['skin_buddy']->xml_tabs( $onoff );
	}
	
 	/*-------------------------------------------------------------------------*/
 	// XML: SPLASH
 	/*-------------------------------------------------------------------------*/
 	
 	function xml_splash()
 	{
 		//-----------------------------------------
 		// Is this a guest? If so, get 'em to log in.
 		//-----------------------------------------
 		
 		if ( ! $this->ipsclass->member['id'] )
 		{
 			//$this->output = $this->ipsclass->compiled_templates['skin_buddy']->login();
 			return;
 		}
 		
 		//-----------------------------------------
 		// What tab?
 		//-----------------------------------------
 		
 		switch( $this->ipsclass->input['tab'] )
 		{
 			case 'posts':
 				$this->xml_get_posts();
 				break;
 			case 'search':
 				$this->xml_get_search();
 				break;
 			case 'newpms':
 				$this->xml_get_new_pms();
 				break;
 			case 'pms':
 				$this->xml_get_unread_pms();
 				break;
 			default:
 				$this->xml_get_info();
 				break;
 		}
 		
 		$this->output = $this->ipsclass->compiled_templates['skin_buddy']->xml_wrap( $this->xml_build_tabs() . $this->output );
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// XML GET: UNREAD PMS
	/*-------------------------------------------------------------------------*/
	
	function xml_get_unread_pms()
	{
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['g_use_pm'] OR $this->ipsclass->member['members_disable_pm'] )
		{
			return;
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/xmlout.php' );
		$xmlout           = new xmlout();
		$xmlout->ipsclass =& $this->ipsclass;
		
		$this->output = $xmlout->get_new_messages( 1 );
	}
	
	/*-------------------------------------------------------------------------*/
	// XML GET: PMS
	/*-------------------------------------------------------------------------*/
	
	function xml_get_new_pms()
	{
		//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$limit = intval( $this->ipsclass->input['limit'] );
    	
    	//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['g_use_pm'] OR $this->ipsclass->member['members_disable_pm'] )
		{
			return;
		}
		
    	//-----------------------------------------
    	// Couldn't be easier....
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->member['new_msg'] )
    	{
    		$this->output = $this->ipsclass->get_new_pm_notification( $limit, 1 );
    	}
    	else
    	{
    		$this->output = $this->ipsclass->compiled_templates['skin_buddy']->xml_nonewpms();
    	}
	}
	
	/*-------------------------------------------------------------------------*/
	// XML GET: INFO
	/*-------------------------------------------------------------------------*/
	
	function xml_get_info()
	{
		$this->output = $this->ipsclass->compiled_templates['skin_buddy']->xml_showme();
	}
	
	/*-------------------------------------------------------------------------*/
	// XML GET: SEARCH
	/*-------------------------------------------------------------------------*/
	
	function xml_get_search()
	{
		$this->output = $this->ipsclass->compiled_templates['skin_buddy']->xml_search();
	}
	
	/*-------------------------------------------------------------------------*/
	// XML GET: POSTS
	/*-------------------------------------------------------------------------*/
	
	function xml_get_posts()
	{
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/xmlout.php' );
		$xmlout           = new xmlout();
		$xmlout->ipsclass =& $this->ipsclass;
		
		$this->output = $xmlout->get_new_posts( 1 );
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SPLASH
 	/*-------------------------------------------------------------------------*/
 	
 	function splash()
 	{
 		//-----------------------------------------
 		// Is this a guest? If so, get 'em to log in.
 		//-----------------------------------------
 		
 		if ( ! $this->ipsclass->member['id'] )
 		{
 			$this->output = $this->ipsclass->compiled_templates['skin_buddy']->login();
 			return;
 		}
 		else
 		{
 			//-----------------------------------------
 			// Get the forums we're allowed to search in
 			//-----------------------------------------
 			
 			$allow_forums   = array();
 			
 			$allow_forums[] = '0';
 			
 			foreach( $this->ipsclass->forums->forum_by_id as $data )
			{
				$allow_forums[] = $data['id'];
			}
 			
 			$forum_string = implode( ",", $allow_forums );
 			
 			//-----------------------------------------
 			// Get the number of posts since the last visit.
 			//-----------------------------------------
 			
 			if ( ! $this->ipsclass->member['last_visit'] )
 			{
 				$this->ipsclass->member['last_visit'] = time() - 3600;
 			}
 			
 			if ( $this->ipsclass->forum_read[0] > $this->ipsclass->member['last_visit'] )
			{
				$this->ipsclass->member['last_visit'] = $this->ipsclass->forum_read[0];
			}
 			
 			$this->ipsclass->DB->cache_add_query( 'buddy_posts_last_visit', array( 'last_visit' => $this->ipsclass->member['last_visit'], 'forum_string' => $forum_string ) );
			$this->ipsclass->DB->cache_exec_query();
		
 			$posts = $this->ipsclass->DB->fetch_row();
 			
 			$posts_total = intval($posts['posts']);
 			
 			//-----------------------------------------
 			// Get the number of posts since the last visit to topics we've started.
 			//-----------------------------------------
 			
 			$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as replies',
														  'from'   => 'topics',
														  'where'  => "last_post > {$this->ipsclass->member['last_visit']}
																		AND approved=1 AND forum_id IN($forum_string)
																		AND posts > 0
																		AND starter_id={$this->ipsclass->member['id']}" ) );
							
 			$this->ipsclass->DB->simple_exec();
 			
 			$topic = $this->ipsclass->DB->fetch_row();
 			
 			$topics_total = ($topic['replies'] < 1) ? ucfirst($this->ipsclass->lang['none']) : $topic['replies'];
 			
 			$text = $this->ipsclass->lang['no_new_posts'];
 			
 			if ($posts_total > 0)
 			{
 				$this->ipsclass->lang['new_posts']  = sprintf($this->ipsclass->lang['new_posts'] , $posts_total  );
 				$this->ipsclass->lang['my_replies'] = sprintf($this->ipsclass->lang['my_replies'], $topics_total );
 				
 				$this->ipsclass->lang['new_posts'] .= $this->ipsclass->compiled_templates['skin_buddy']->append_view("&amp;act=Search&amp;CODE=getnew");
 				
 				if ($topic['replies'] > 0)
 				{
 					$this->ipsclass->lang['my_replies'] .= $this->ipsclass->compiled_templates['skin_buddy']->append_view("&amp;act=Search&amp;CODE=getreplied");
 				}
 				
 				$text = $this->ipsclass->compiled_templates['skin_buddy']->build_away_msg();
 			}
 			
 			$this->output = $this->ipsclass->compiled_templates['skin_buddy']->main($text);
 		}
 	}
}

?>