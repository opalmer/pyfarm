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
|   > $Date: 2007-08-21 17:48:41 -0400 (Tue, 21 Aug 2007) $
|   > $Revision: 1099 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > PORTAL PLUG IN MODULE: Recent topics
|   > Module written by Matt Mecham
|   > Date started: Monday 1st August 2005 (16:22)
+--------------------------------------------------------------------------
*/

/**
* Portal Plug In Module
*
* This module displays the recently posted topic title and
* first post.
*
* @package		InvisionPowerBoard
* @subpackage	PortalPlugIn
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
* Portal Plug In Module
*
* This module displays the recently posted topic title and
* first post.
* Each class name MUST be in the format of:
* ppi_{file_name_minus_dot_php}
*
* @package		InvisionPowerBoard
* @subpackage	PortalPlugIn
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_recent_topics
{
	/**
	* IPS Global object
	*
	* @var string
	*/
	var $ipsclass;

	/**
	* Array of portal objects including:
	* good_forum, bad_forum
	*
	* @var array
	*/
	var $portal_object = array();
	
	/*-------------------------------------------------------------------------*/
 	// INIT
	/*-------------------------------------------------------------------------*/
 	/**
	* This function must be available always
	* Add any set up here, such as loading language and skins, etc
	*
	*/
 	function init()
 	{
 	}
 	
	/*-------------------------------------------------------------------------*/
 	// SHOW RECENT DISCUSSIONS X
	/*-------------------------------------------------------------------------*/
 	
 	function recent_topics_discussions_last_x()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
 		
 		$html  = "";
 		$limit = $this->ipsclass->vars['recent_topics_discuss_number'] ? $this->ipsclass->vars['recent_topics_discuss_number'] : 5;
 		
 		if ( count( $this->portal_object['good_forum'] ) > 0 )
    	{
    		$qe = "forum_id IN(".implode(',', $this->portal_object['good_forum'] ).") AND ";
    	}
    	else
    	{
	    	return;
    	}
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid, title, posts, starter_id as member_id, starter_name as member_name, start_date as post_date, views',
													  'from'   => 'topics',
													  'where'  => "$qe approved=1 and state != 'closed' and (moved_to is null or moved_to = '')",
													  'order'  => 'start_date DESC',
													  'limit'  => array( 0, $limit ) ) );
		$this->ipsclass->DB->simple_exec();
		
 		while ( $row = $this->ipsclass->DB->fetch_row() )
 		{
 			$html .= $this->_tmpl_format_topic($row, 30);
 		}
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_latestposts($html);
 	}
 	
	
	/*-------------------------------------------------------------------------*/
	// SHOW RECENT TOPICS X
	/*-------------------------------------------------------------------------*/
	/**
	* Show the actual topics w/post
	*
	* @return VOID
	*/
	function recent_topics_last_x()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
 		
 		$html         = "";
 		$attach_pids  = array();
 		$attach_posts = array();
 		
 		//-----------------------------------------
    	// Grab articles new/recent in 1 bad ass query
    	//-----------------------------------------
    	
    	$limit = intval($this->ipsclass->vars['recent_topics_article_max']);
    	
    	if ( count( $this->portal_object['bad_forum'] ) > 0 )
    	{
    		$qe = " AND t.forum_id NOT IN(".implode(',', $this->portal_object['bad_forum'] ).") ";
    	}
    	
    	if ( count( $this->portal_object['good_forum'] ) > 0 )
		{
			$qe .= " AND t.forum_id IN(".implode(',', $this->portal_object['good_forum'] ).") ";
		}
        
        if ( $this->ipsclass->vars['recent_topics_article_forum'] )
        {
        	$this->ipsclass->vars['recent_topics_article_forum'] = ','.$this->ipsclass->vars['recent_topics_article_forum'];
        }
        
        $this->ipsclass->DB->cache_add_query( 'portal_get_monster_bitch', array( 'csite_article_forum' => $this->ipsclass->vars['recent_topics_article_forum'], 'qe' => $qe, 'limit' => $limit ) );
		$outer = $this->ipsclass->DB->cache_exec_query();
		
 		//-----------------------------------------
 		// Loop through..
 		//-----------------------------------------
 		
 		while( $entry = $this->ipsclass->DB->fetch_row($outer) )
 		{
 			//-----------------------------------------
 			// INIT
 			//-----------------------------------------
 			
 			$bottom_string  = "";
 			$read_more      = "";
 			$top_string     = "";
 			$got_these_attach = 0;
 			
 			//-----------------------------------------
 			// BASIC INFO
 			//-----------------------------------------
 			
 			$real_posts     = $entry['posts'];
 			$entry['title'] = strip_tags($entry['title']);
 			$entry['posts'] = $this->ipsclass->do_number_format(intval($entry['posts']));
 			$entry['views'] = $this->ipsclass->do_number_format($entry['views']);
 			
 			if( !$entry['author_id'] )
 			{
				$entry['member_name'] 	= $this->ipsclass->vars['guest_name_pre'] . $entry['author_name'] . $this->ipsclass->vars['guest_name_suf'];
				$entry['member_id']		= 0;
			}

 			//-----------------------------------------
 			// LINKS
 			//-----------------------------------------
 			
 			$comment_link   = $this->ipsclass->compiled_templates['skin_portal']->tmpl_comment_link($entry['tid']);
 			$profile_link   = $this->ipsclass->make_profile_link( $entry['last_poster_name'], $entry['last_poster_id'] );
 			
 			if ( $real_posts > 0 )
 			{
 				$bottom_string = sprintf( $this->ipsclass->lang['article_reply'], $entry['views'], $comment_link, $profile_link );
 			}
 			else
 			{
 				$bottom_string = sprintf( $this->ipsclass->lang['article_noreply'], $entry['views'], $comment_link );
 			}
 			
 			//-----------------------------------------
 			// Set up date
 			//-----------------------------------------
 			
 			$this->ipsclass->vars['csite_article_date'] = $this->ipsclass->vars['csite_article_date'] ? $this->ipsclass->vars['csite_article_date'] : 'm-j-y H:i';
 			
 			//-----------------------------------------
 			// Get Date
 			//-----------------------------------------
 			
 			$entry['date'] = gmdate( $this->ipsclass->vars['csite_article_date'], $entry['post_date'] + $this->ipsclass->get_time_offset() );
 			
 			$top_string = sprintf(
 								   $this->ipsclass->lang['article_postedby'],
 								   $this->ipsclass->make_profile_link( $entry['member_name'], $entry['member_id'] ),
 								   $entry['date'],
 								   $entry['posts']
 								 );
 			
 			$entry['post'] = str_replace( '<br>', '<br />', $entry['post'] );
 			
 			//-----------------------------------------
			// Attachments?
			//-----------------------------------------
			
			$attach_pids[ $entry['pid'] ] = $entry['pid'];
 			
 			//-----------------------------------------
 			// Avatar
 			//-----------------------------------------
 			
 			$entry['avatar'] = $this->ipsclass->get_avatar( $entry['avatar_location'], $this->ipsclass->member['view_avs'], $entry['avatar_size'], $entry['avatar_type'] );
 			
 			if ( $entry['avatar'] )
 			{
 				$entry['avatar'] = $this->ipsclass->compiled_templates['skin_portal']->tmpl_wrap_avatar( $entry['avatar'] );
 			}
 			
			if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $entry['forum_id'] ]['download_perms']) === FALSE )
			{
				$this->ipsclass->vars['show_img_upload'] = 0;
			} 
			
			//-----------------------------------------
			// View image...
			//-----------------------------------------
		 			
			if ( ! $this->ipsclass->member['view_img'] )
			{
				//-----------------------------------------
				// unconvert smilies first, or it looks a bit crap.
				//-----------------------------------------
				
				$entry['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $entry['post'] );
				
				$entry['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>) ", $entry['post'] );
			} 			
 			
 			$html .= $this->ipsclass->compiled_templates['skin_portal']->tmpl_articles_row($entry, $bottom_string, $top_string);
 		}
 		
 		//-----------------------------------------
 		// Process Attachments
 		//-----------------------------------------
 		
 		if ( count( $attach_pids ) )
 		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
				$this->class_attach                  =  new class_attach();
				$this->class_attach->ipsclass        =& $this->ipsclass;
				$this->class_attach->attach_post_key =  '';
				
				$this->ipsclass->load_template( 'skin_topic' );
				$this->ipsclass->load_language( 'lang_topic' );
			}
			
			$this->class_attach->attach_post_key =  '';
			$this->class_attach->type            = 'post';
			$this->class_attach->init();
		
			$html = $this->class_attach->render_attachments( $html, $attach_pids );
 		}
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_articles($html);
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Format topic entry
 	/*-------------------------------------------------------------------------*/
 	
 	function _tmpl_format_topic($entry, $cut)
 	{
 		$entry['title'] = strip_tags($entry['title']);
		$entry['title'] = str_replace( "&#33;" , "!" , $entry['title'] );
		$entry['title'] = str_replace( "&quot;", "\"", $entry['title'] );
		
		if (strlen($entry['title']) > $cut)
		{
			$entry['title'] = substr( $entry['title'],0,($cut - 3) ) . "...";
			$entry['title'] = preg_replace( '/&(#(\d+;?)?)?(\.\.\.)?$/', '...',$entry['title'] );
		}
		
		$entry['posts'] = $this->ipsclass->do_number_format($entry['posts']);
 		$entry['views'] = $this->ipsclass->do_number_format($entry['views']);
 		
 		$this->ipsclass->vars['csite_article_date'] = $this->ipsclass->vars['csite_article_date'] ? $this->ipsclass->vars['csite_article_date'] : 'm-j-y H:i';
 		
 		$entry['date']  = gmdate( $this->ipsclass->vars['csite_article_date'], $entry['post_date'] + $this->ipsclass->get_time_offset() );
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_topic_row($entry['tid'], $entry['title'], $entry['posts'], $entry['views'], $entry['member_id'], $entry['member_name'], $entry['date']);
 	}

}

?>