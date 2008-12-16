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
|   > $Date: 2007-09-20 12:19:39 -0400 (Thu, 20 Sep 2007) $
|   > $Revision: 1108 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > MySQL FULL TEXT Search Library
|   > Module written by Matt Mecham
|   > Date started: 31st March 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class search_lib extends Search
{
    var $is          = "";
    var $resultlimit = "";
    
    var $class_attach;
    
    //-----------------------------------------
	// Constructor
	//-----------------------------------------
    	
    function search_lib(&$that)
    {
		$this->is          = &$that; // hahaha!
    	$this->resultlimit = $this->is->resultlimit;
 	}
 	
 	//-----------------------------------------
	// Simple search
	//-----------------------------------------

	function do_simple_search()
	{
		if ( ! $this->ipsclass->input['sid'] )
		{
			$boolean     = "";
			$topics_name = "";
			
			//-----------------------------------------
			// NEW SEARCH.. Check Keywords..
			//-----------------------------------------
			
			if ( $this->is->mysql_version >= 40010 )
			{
				$boolean  = 'IN BOOLEAN MODE';
				$keywords = $this->is->filter_ftext_keywords($this->ipsclass->input['keywords']);
			}
			else
			{
				$keywords = $this->is->filter_keywords($this->ipsclass->input['keywords']);
			}
			
			$check_keywords = trim($keywords);
			
			if ( (! $check_keywords) or ($check_keywords == "") or (! isset($check_keywords) ) )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_words') );
			}
			
			if (strlen(trim($keywords)) < $this->ipsclass->vars['min_search_word'])
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
			}
			else if ( $this->ipsclass->txt_mb_strlen(trim($keywords)) > 100 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_long', 'EXTRA' => 100) );
			}
			
			//-----------------------------------------
			// Check for filter abuse..
			//-----------------------------------------
			
			$tmp = explode( ' ', $keywords );
			
			foreach( $tmp as $t )
			{
				if ( ! $t )
				{
					continue;
				}
				
				$t = preg_replace( "#[\+\-\*\.]#", "", $t );
				
				//-----------------------------------------
				// Allow abc* but not a***
				//-----------------------------------------
				
				if ( strlen( $t ) < $this->ipsclass->vars['min_search_word'] )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
				}
			}
			
			//-----------------------------------------
			// Get forums...
			//-----------------------------------------
			
			$myforums = $this->is->get_searchable_forums();
			
			if ($myforums == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
			}
			
			//-----------------------------------------
			// Only allowed to see their own topics?
			//-----------------------------------------
			
			if ( ! $this->ipsclass->member['g_other_topics'] )
			{
				$topics_name = " AND t.starter_id=".$this->ipsclass->member['id'];
			}
	    
			//-----------------------------------------
			// Allowed to see queueueueueuueueueued?
			//-----------------------------------------
			
			if ( ! $this->ipsclass->member['g_is_supmod'] )
			{
				$approved = 'and p.queued=0 and t.approved=1';
			}
			else
			{
				$approved = 'and p.queued IN (0,1)';
			}
			
			//-----------------------------------------
			// bad magic quotes - bad
			//-----------------------------------------
			
			$check_keywords = $this->ipsclass->DB->add_slashes( $check_keywords );
		
			//-----------------------------------------
			// How many results?
			//-----------------------------------------
			
			$this->ipsclass->DB->build_query( array( 'select'  	=> 'COUNT(*) as dracula',
													 'from'		=> array( 'posts' => 'p' ),
													 'add_join'	=> array( 1 => array( 'type'	=> 'left',
													 								  'from'	=> array( 'topics' => 't' ),
													 								  'where'	=> 't.tid=p.topic_id',
													 					)			),
													 'where'	=> "t.forum_id IN ({$myforums}) {$topics_name} {$approved}
													 				AND MATCH(p.post) AGAINST ('{$check_keywords}' {$boolean})"
											)		);
			$this->ipsclass->DB->exec_query();
			
			$count = $this->ipsclass->DB->fetch_row();
			
			if ( $count['dracula'] < 1 ) // Tee-hee!
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_search']->search_error_page($this->ipsclass->input['keywords']);
				$this->ipsclass->print->add_output( $this->output );
				$this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->lang['g_simple_title'], 'JS' => 0, NAV => array( $this->ipsclass->lang['g_simple_title'] ) ) );
			}
			
			//-----------------------------------------
			// Store it daddy-o!
			//-----------------------------------------
			
			$cache = "SELECT MATCH(post) AGAINST ('$check_keywords' $boolean) as score, t.approved, t.tid, t.posts as topic_posts, t.title as topic_title, t.views, t.forum_id,
			                 p.post, p.author_id, p.author_name, p.post_date, p.queued, p.pid, p.post_htmlstate,m.*, me.*,pp.*
					  FROM ".SQL_PREFIX."posts p
					   LEFT JOIN ".SQL_PREFIX."topics t on (p.topic_id=t.tid)
					   LEFT JOIN ".SQL_PREFIX."members m on (m.id=p.author_id)
					   LEFT JOIN ".SQL_PREFIX."member_extra me on (me.id=p.author_id)
					   LEFT JOIN ".SQL_PREFIX."profile_portal pp on (pp.pp_member_id=p.author_id)
					  WHERE t.forum_id IN ($myforums) AND t.title IS NOT NULL {$topics_name} {$approved}
					  AND MATCH(post) AGAINST ('$check_keywords' $boolean)";
			
			if ( $this->ipsclass->input['sortby'] != "relevant" )
			{
				$cache .= " ORDER BY p.post_date {$this->is->sort_order}";
			}
					  
			$unique_id = md5(uniqid(microtime(),1));
		
			$str = array (
							'id'         => $unique_id,
							'search_date'=> time(),
							'topic_id'   => '00',
							'topic_max'  => $count['dracula'],
							'member_id'  => $this->ipsclass->member['id'],
							'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
							'post_id'    => '00',
							'query_cache'=> $cache,
						);
			
			$this->ipsclass->DB->do_insert( "search_results", $str );
			
			$hilight = str_replace( '&quot;', '', $this->ipsclass->input['keywords'] );
			$hilight = urlencode(trim(str_replace( '&amp;', '&', $hilight)));
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['search_redirect'] , "act=Search&CODE=simpleresults&sid=$unique_id&highlite=".$hilight );
		
		}
		else
		{
			//-----------------------------------------
			// Load up the topic stuff
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/action_public/topics.php' );
			$this->topics = new topics();
			$this->topics->ipsclass =& $this->ipsclass;
			
			$this->topics->topic_init();
			
			//-----------------------------------------
			// Get SQL schtuff
			//-----------------------------------------
			
			$this->unique_id = $this->ipsclass->input['sid'];
		
			if ($this->unique_id == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
			
			$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'search_results', 'where' => "id='".$this->unique_id."'" ) );
			$this->ipsclass->DB->exec_query();
			
			if ( ! $sr = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
		
			$query = stripslashes($sr['query_cache']);
		
			$check_keywords = preg_replace( '/&amp;(lt|gt|quot);/', "&\\1;", trim(urldecode($this->ipsclass->input['highlite'])) );
		
			//-----------------------------------------
			// Display
			//-----------------------------------------
			
			$this->links = $this->ipsclass->build_pagelinks(
						     array(
						      		'TOTAL_POSS'  => $sr['topic_max'],
						      		'leave_out'   => 10,
									'PER_PAGE'    => 25,
									'CUR_ST_VAL'  => $this->is->first,
									'L_SINGLE'    => $this->ipsclass->lang['sp_single'],
									'L_MULTI'     => "",
									'BASE_URL'    => $this->ipsclass->base_url."&amp;act=Search&amp;CODE=simpleresults&amp;sid=".$this->unique_id."&amp;highlite=".urlencode(str_replace('"', '', $check_keywords)),
								  )
						   					    );
		
			//-----------------------------------------
			// oh look, a query!
			//-----------------------------------------
			
			$last_tid = 0;
			
			$SQLtime = new Debug();
			
			$SQLtime->startTimer();
			
			$outer = $this->ipsclass->DB->query($query." LIMIT {$this->is->first},25");
			
			$ex_time = sprintf( "%.4f",$SQLtime->endTimer() );
			
			$show_end = 25;
			
			if ( $sr['topic_max'] < 25 )
			{
				$show_end = $sr['topic_max'];
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_search']->result_simple_header(array(
																											'links'   => $this->links,
																											'start'   => $this->is->first,
																											'end'     => $show_end + $this->is->first,
																											'matches' => $sr['topic_max'],
																											'ex_time' => $ex_time,
																											'keyword' => $check_keywords,
																									 )     );
			
						
			while ( $row = $this->ipsclass->DB->fetch_row($outer) )
			{
				$attach_pids = array();
				
				//-----------------------------------------
				// Listen up, this is relevant.
				// MySQL's relevance is a bit of a mystery. It's
				// based on many hazy variables such as placing, occurance
				// and such. The result is a floating point number, like 1.239848556
				// No one can really disect what this means in human terms, so I'm
				// going to simply assume that anything over 1.0 is 100%, and *100 any
				// other relevance result.
				//-----------------------------------------
				
				$member = $this->topics->parse_member( $row );
				
				$row['relevance'] = sprintf( "%3d", ( $row['score'] > 1.0 ) ? 100 : $row['score'] * 100 );
				
				$row['post_date'] = $this->ipsclass->get_date( $row['post_date'], 'LONG' );
				
				// Link member's name
				
				if ( $row['author_id'] )
				{
					$row['author_name'] = "<a href='{$this->ipsclass->base_url}showuser={$row['author_id']}'>{$row['author_name']}</a>";
				}
				
				if ( $member['id'] )
				{
					$member['_members_display_name'] = "<a href='{$this->base_url}showuser={$member['id']}'>{$member['members_display_name_short']}</a>";
				} 
				
				//-----------------------------------------
				// Attachments?
				//-----------------------------------------
				
				if ( strstr( $row['post'], '[attachment=' ) )
				{
					$attach_pids[] = $row['pid'];
				}
				
				//-----------------------------------------
				// Fix up quotes..
				//-----------------------------------------
				
				$row['class1']     = "row2";
				$row['class2']     = "row1";
				$row['classposts'] = "row2";
				
				if ( $this->ipsclass->input['highlite'] )
				{
					$row['post'] = $this->ipsclass->content_search_highlight( $row['post'], $this->ipsclass->input['highlite'] );
				}
				
				$row['forum_name'] = $this->ipsclass->forums->forum_by_id[ $row['forum_id'] ]['name'];
				
				$this->output .= $this->ipsclass->compiled_templates['skin_search']->RenderPostRow($row, $member);
				
				//-----------------------------------------
				// Add in attachments?
				// Sucks we have to do this inside the loop
				// however we have to check forum view perms
				//-----------------------------------------
				
				if ( count( $attach_pids ) )
				{
					if ( ! is_object( $this->class_attach ) )
					{
						//-----------------------------------------
						// Grab render attach class
						//-----------------------------------------
		
						require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
						$this->class_attach           =  new class_attach();
						$this->class_attach->ipsclass =& $this->ipsclass;
					}
					
					if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $row['forum_id'] ]['download_perms']) === FALSE )
					{
						$this->ipsclass->vars['show_img_upload'] = 0;
					}
								
					$this->class_attach->type  = 'post';
					$this->class_attach->init();
				
					$this->output = $this->class_attach->render_attachments( $this->output, $attach_pids );
				}				
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_search']->end_results_table( array( 'SHOW_PAGES' => $this->links ), 1 );
																  			
			$this->ipsclass->print->add_output("$this->output");
			$this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->lang['g_simple_title'], 'JS' => 0, NAV => array( $this->ipsclass->lang['g_simple_title'] ) ) );
    	
    	}
 	
 	}
 	
 	
 	//-----------------------------------------
	// Main Board Search-e-me-doo-daa
	//-----------------------------------------
 

	function do_main_search()
	{
		//-----------------------------------------
		
		$forums = $this->is->get_searchable_forums();
		
		//-----------------------------------------
		// Invisible topic-approval permissions?
		//-----------------------------------------		
		
		$my_invisible_forums_t = array();
		$my_invisible_forums_p = array();
		
		if( is_array($this->ipsclass->cache['moderators']) AND count($this->ipsclass->cache['moderators']) )
		{
			foreach( $this->ipsclass->cache['moderators'] as $v )
			{
				if( $v['member_id'] == $this->ipsclass->member['id'] AND $v['post_q'] )
				{
					if( in_array( $v['forum_id'], $this->is->searchable_forums ) )
					{
						$my_invisible_forums_p[] = $v['forum_id'];
					}
				}
				
				if( $v['member_id'] == $this->ipsclass->member['id'] AND $v['topic_q'] )
				{
					if( in_array( $v['forum_id'], $this->is->searchable_forums ) )
					{
						$my_invisible_forums_t[] = $v['forum_id'];
					}
				}
				
				if( $v['is_group'] && ( $v['group_id'] == $this->ipsclass->member['mgroup']
					 OR in_array( $v['group_id'], explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) ) ) && $v['post_q'] )
				{
					if( in_array( $v['forum_id'], $this->is->searchable_forums ) )
					{
						$my_invisible_forums_p[] = $v['forum_id'];
					}
				}
				
				if( $v['is_group'] && ( $v['group_id'] == $this->ipsclass->member['mgroup']
					 OR in_array( $v['group_id'], explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) ) ) && $v['topic_q'] )
				{
					if( in_array( $v['forum_id'], $this->is->searchable_forums ) )
					{
						$my_invisible_forums_t[] = $v['forum_id'];
					}
				}
			}
		}		
		//-----------------------------------------
		// Do we have any input?
		//-----------------------------------------
		
		$name_filter = (isset($this->ipsclass->input['namesearch']) AND $this->ipsclass->input['namesearch'] != "") ? $this->is->filter_keywords($this->ipsclass->input['namesearch'], 1) : '';
			
		if (isset($this->ipsclass->input['useridsearch']) AND $this->ipsclass->input['useridsearch'] != "")
		{
			$keywords = $this->is->filter_keywords($this->ipsclass->input['useridsearch']);
			$this->is->search_type = 'userid';
		}
		else
		{
			$keywords = $this->is->filter_keywords($this->ipsclass->input['keywords']);
			$this->is->search_type = 'posts';
		}
		
		$type = "postonly";
		
		if ( $name_filter != "" AND $this->ipsclass->input['keywords'] != "" )
		{
			$type = 'joined';
		}
		else if ( $name_filter == "" AND $this->ipsclass->input['keywords'] != "" )
		{
			$type= 'postonly';
		}
		else if ( $name_filter != "" AND $this->ipsclass->input['keywords'] == "" )
		{
			$type='nameonly';
		}
		
		//-----------------------------------------
		// USING FULL TEXT - Wooohoo!!
		//-----------------------------------------
		
		if ( $this->is->mysql_version >= 40010 )
		{
			$boolean  = 'IN BOOLEAN MODE';
			$keywords = $this->is->filter_ftext_keywords($this->ipsclass->input['keywords']);
		}
		else
		{
			$keywords = $this->is->filter_keywords($this->ipsclass->input['keywords']);
		}
		
		$check_keywords = trim($keywords);
		
		if ( (! $check_keywords) or ($check_keywords == "") or (! isset($check_keywords) ) )
		{
			if ($this->ipsclass->input['namesearch'] == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_words') );
			}
		}
		else
		{
			if ( strlen(trim($keywords)) < $this->ipsclass->vars['min_search_word'] and $type != 'nameonly' )
			{
				if ( $this->is->xml_out )
				{
					print sprintf( $this->ipsclass->lang['xml_char'], $this->ipsclass->vars['min_search_word'] );
					exit();
				}
				else
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
				}
			}
			else if ( strlen(trim($keywords)) > 100 )
			{
				if ( $this->is->xml_out )
				{
					print sprintf( $this->ipsclass->lang['xml_max_char'], 100 );
					exit();
				}
				else
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_long', 'EXTRA' => 100) );
				}
			}
		}
		
		//-----------------------------------------
		// Check for filter abuse..
		//-----------------------------------------
		
		$tmp = explode( ' ', $keywords );
		
		foreach( $tmp as $t )
		{
			if ( ! $t )
			{
				continue;
			}
			
			$t = preg_replace( "#[\+\-\*\.]#", "", $t );
			
			//-----------------------------------------
			// Allow abc* but not a***
			//-----------------------------------------
			
			if ( ( strlen( $t ) < $this->ipsclass->vars['min_search_word'] )  and $type != 'nameonly' )
			{
				if ( $this->is->xml_out )
				{
					print sprintf( $this->ipsclass->lang['xml_char'], $this->ipsclass->vars['min_search_word'] );
					exit();
				}
				else
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
				}
			}
		}
		
		//-----------------------------------------

		if ($this->ipsclass->input['search_in'] == 'titles')
		{
			$this->is->search_in = 'titles';
		}
		
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		if ($forums == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		//-----------------------------------------
		
		foreach( array( 'last_post', 'posts', 'starter_name', 'forum_id' ) as $v )
		{
			if ( $this->ipsclass->input['sort_key'] == $v )
			{
				$this->is->sort_key = $v;
			}
		}
		
		//-----------------------------------------
		
		foreach ( array( 1, 7, 30, 60, 90, 180, 365, 0 ) as $v )
		{
			if ($this->ipsclass->input['prune'] == $v)
			{
				$this->is->prune = $v;
			}
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->input['sort_order'] == 'asc')
		{
			$this->is->sort_order = 'asc';
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->input['result_type'] == 'posts')
		{
			$this->is->result_type = 'posts';
		}
		
		if ( $this->ipsclass->vars['min_search_word'] < 1 )
		{
			$this->ipsclass->vars['min_search_word'] = 4;
		}
		
		
		$topics_datecut = "";
		$posts_datecut	= "";
		$posts_name		= "";
		$topics_name	= "";
		
		//-----------------------------------------
		// Add on the prune days
		//-----------------------------------------
		
		if ($this->is->prune > 0)
		{
			$gt_lt = $this->ipsclass->input['prune_type'] == 'older' ? "<" : ">";
			$time = time() - ($this->ipsclass->input['prune'] * 86400);
			
			if ( $this->is->result_type == 'posts' )
			{
				$topics_datecut = "t.start_date $gt_lt $time AND";
			}
			else
			{
				$topics_datecut = "t.last_post $gt_lt $time AND";
			}
			
			$posts_datecut  = "p.post_date $gt_lt $time AND";
		}
		
		//-----------------------------------------
		// Only allowed to see their own topics?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['g_other_topics'] )
		{
			$name_filter = "";
			$posts_name  = " AND t.starter_id=".$this->ipsclass->member['id'];
			$topics_name = " AND t.starter_id=".$this->ipsclass->member['id'];
		}
		
		//-----------------------------------------
		// Is this a membername search?
		//-----------------------------------------
		
		$name_filter   = trim( $name_filter );
		$member_string = "";
		 
		if ( $name_filter != "" )
		{
			//-----------------------------------------
			// Get all the possible matches for the supplied name from the DB
			//-----------------------------------------
			
			$name_filter = str_replace( '|', "&#124;", $name_filter );
			
			if (isset($this->ipsclass->input['exactname']) AND $this->ipsclass->input['exactname'] == 1)
			{
				$sql_query = "SELECT id from ".SQL_PREFIX."members WHERE members_l_display_name='".$name_filter."'";
			}
			else
			{
				$sql_query = "SELECT id from ".SQL_PREFIX."members WHERE members_display_name like '%".$name_filter."%'";
			}
			
			
			$this->ipsclass->DB->query( $sql_query );
			
			
			while ($row = $this->ipsclass->DB->fetch_row())
			{
				$member_string .= "'".$row['id']."',";
			}
			
			$member_string = preg_replace( "/,$/", "", $member_string );
			
			// Error out of we matched no members
			
			if ($member_string == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_name_search_results') );
			}
			
			$posts_name  = " AND p.author_id IN ($member_string)";
			$topics_name = " AND t.starter_id IN ($member_string)";
		}
		
		if ( $type != 'nameonly' )
		{
			if (strlen(trim($keywords)) < $this->ipsclass->vars['min_search_word'])
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
			}
		}

		$unique_id = md5(uniqid(microtime(),1));
		
		$t_forum_query = $this->ipsclass->member['g_is_supmod'] ? "t.forum_id IN ($forums)" :
						 ( count($my_invisible_forums_t) ? "( (t.forum_id IN ($forums) AND t.approved=1) OR t.forum_id IN(" . implode( ",", $my_invisible_forums_t ) . ") )" :
						 "t.forum_id IN ($forums) AND t.approved=1" );

		if( $this->ipsclass->member['g_is_supmod'] )
		{
			$p_forum_query = "t.forum_id IN ($forums)";
		}
		else if( count($my_invisible_forums_p) AND count($my_invisible_forums_t) )
		{
			$my_invisible_forums_both = array();
			
			foreach( $my_invisible_forums_p as $p_fid )
			{
				if( in_array( $p_fid, $my_invisible_forums_t ) )
				{
					$my_invisible_forums_both[] = $p_fid;
				}
			}
			
			$p_forum_query = "( (t.forum_id IN ($forums) AND t.approved=1 AND p.queued=0) OR 
								t.forum_id IN(" . implode( ",", $my_invisible_forums_both ) . ") OR 
								(t.forum_id IN(" . implode( ",", $my_invisible_forums_p ) . ") AND t.approved=1) OR 
								(t.forum_id IN(" . implode( ",", $my_invisible_forums_t ) . ") AND p.queued=0) )";
		}
		else if( count($my_invisible_forums_t) )
		{
			$p_forum_query = "( (t.forum_id IN ($forums) AND t.approved=1 AND p.queued=0) OR 
								(t.forum_id IN(" . implode( ",", $my_invisible_forums_t ) . ") AND p.queued=0) )";
		}
		else if( count($my_invisible_forums_p) )
		{
			$p_forum_query = "( (t.forum_id IN ($forums) AND t.approved=1 AND p.queued=0) OR 
								(t.forum_id IN(" . implode( ",", $my_invisible_forums_p ) . ") AND t.approved=1) )";
		}
		else
		{
			$p_forum_query = "(t.forum_id IN ($forums) AND t.approved=1 AND p.queued=0)";
		}
		
		//-----------------------------------------
		// bad magic quotes - bad
		//-----------------------------------------
		
		$keywords = $this->ipsclass->DB->add_slashes( $keywords );

		if ($type != 'nameonly')
		{
			if ( ! $this->is->topic_search_only )
			{
				$topics_query = "SELECT t.tid, t.approved, t.forum_id
								FROM ".SQL_PREFIX."topics t
								WHERE $topics_datecut $t_forum_query
								$topics_name AND MATCH(title) AGAINST ('".trim($keywords)."' $boolean)
								ORDER BY t.last_post {$this->is->sort_order}
								LIMIT {$this->resultlimit}";
			
			
				$posts_query = "SELECT p.pid, p.queued, t.approved, t.forum_id
								FROM ".SQL_PREFIX."posts p
								 LEFT JOIN ".SQL_PREFIX."topics t ON ( p.topic_id=t.tid )
								WHERE $posts_datecut $p_forum_query
								 $posts_name AND MATCH(post) AGAINST ('".trim($keywords)."' $boolean)
								 ORDER BY p.post_date {$this->is->sort_order}
								 LIMIT {$this->resultlimit}";
			}
			else
			{
				//-----------------------------------------
				// Search in topic only
				//-----------------------------------------
				
				$posts_query = "SELECT p.pid, p.queued, t.approved, t.forum_id
								FROM ".SQL_PREFIX."posts p
								 LEFT JOIN ".SQL_PREFIX."topics t ON ( p.topic_id=t.tid )
								WHERE
								 p.topic_id={$this->is->topic_id}
								 AND $posts_datecut $p_forum_query
								 $posts_name AND MATCH(post) AGAINST ('".trim($keywords)."' $boolean)
								 ORDER BY p.post_date {$this->is->sort_order}
								 LIMIT {$this->resultlimit}";
			}
		}
		else
		{
			
			$topics_query = "SELECT t.tid, t.approved, t.forum_id
							FROM ".SQL_PREFIX."topics t
							WHERE $topics_datecut $t_forum_query
							$topics_name
							ORDER BY t.last_post {$this->is->sort_order}
							LIMIT {$this->resultlimit}";
		
			$posts_query = "SELECT p.pid, p.queued, t.approved, t.forum_id
						    FROM ".SQL_PREFIX."posts p
						     LEFT JOIN ".SQL_PREFIX."topics t ON ( p.topic_id=t.tid )
						    WHERE $posts_datecut $p_forum_query
						     $posts_name
						     ORDER BY p.post_date {$this->is->sort_order}
						     LIMIT {$this->resultlimit}";
		}
		//print $topics_query."<br />".$posts_query; exit();
		//-----------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//-----------------------------------------
		
		$topics = "";
		$posts  = "";
		$topic_array = array();
		$topic_tmp   = array();
		$post_array  = array();
		$post_tmp    = array();
		$t_cnt       = 0;
		$p_cnt       = 0;
		$more        = "";
		
		//-----------------------------------------
		// TID: Get 'em
		//-----------------------------------------
		
		if ( ! $this->is->topic_search_only )
		{
			$this->ipsclass->DB->query($topics_query);
			
			$total_t = $this->ipsclass->DB->get_num_rows();
		
			while ($row = $this->ipsclass->DB->fetch_row() )
			{
				/*if( !$row['approved'] )
				{
					if( !$this->ipsclass->member['g_is_supmod'] )
					{
						if( !in_array( $row['forum_id'], $my_invisible_forums_t ) )
						{
							continue;
						}
					}
				}*/
				
				//$topic_tmp[ $row['tid'] ] = $row['tid'];
				$topic_array[ $row['tid'] ] = $row['tid'];
			}
			
			$this->ipsclass->DB->free_result();
		}
		
		if( $total_t == $this->resultlimit )
		{
			$more = 1;
		}
		
		//-----------------------------------------
		// TID Sort 'em
		//-----------------------------------------
		
		/*krsort( $topic_tmp );
		
		foreach( $topic_tmp as $id => $tid )
		{
			$t_cnt++;
			
			if ( $t_cnt > $this->resultlimit )
			{
				$more = 1;
				break;
			}
			
			$topic_array[ $tid ] = $tid;
		}*/
		
		//-----------------------------------------
		// PID: Get 'em
		//-----------------------------------------
		
		if( $this->is->search_in != 'titles' )
		{
			$this->ipsclass->DB->query($posts_query);
			
			$total_p = $this->ipsclass->DB->get_num_rows();
			
			while ($row = $this->ipsclass->DB->fetch_row() )
			{
				/*if( $row['queued'] )
				{
					if( !$this->ipsclass->member['g_is_supmod'] )
					{
						if( !in_array( $row['forum_id'], $my_invisible_forums_p ) )
						{
							continue;
						}
					}
				}
				
				if( !$row['approved'] )
				{
					if( !$this->ipsclass->member['g_is_supmod'] )
					{
						if( !in_array( $row['forum_id'], $my_invisible_forums_t ) )
						{
							continue;
						}
					}
				}*/
							
				//$post_tmp[ $row['pid'] ] = $row['pid'];
				$post_array[ $row['pid'] ] = $row['pid'];
			}
			
			$this->ipsclass->DB->free_result();
			
			if( $total_p == $this->resultlimit )
			{
				$more = 1;
			}
			
			//-----------------------------------------
			// PID Sort 'em
			//-----------------------------------------
			
			/*krsort( $post_tmp );
			
			foreach( $post_tmp as $id => $pid )
			{
				$p_cnt++;
					
				if ( $p_cnt > $this->resultlimit )
				{
					$more = 1;
					break;
				}
					
				$post_array[ $pid ] = $pid;
			}*/
		}
		
		//-----------------------------------------
		
		$topics = implode( ",", $topic_array );
		$posts  = implode( ",", $post_array );
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ($topics == "" and $posts == "")
		{
			if ( $this->is->xml_out )
			{
				print sprintf( $this->ipsclass->lang['xml_nomatches'], str_replace( '"', "", $keywords ) );
				exit();
			}
			else
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_search']->search_error_page($this->ipsclass->input['keywords']);
				$this->ipsclass->print->add_output( $this->output );
				$this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->lang['g_simple_title'], 'JS' => 0, 'NAV' => array( $this->ipsclass->lang['g_simple_title'] ) ) );
			}
		}
		
		//-----------------------------------------
		// If we are still here, return data like a good
		// boy (or girl). Yes Reg; or girl.
		// What have the Romans ever done for us?
		//-----------------------------------------
		
		return array(
					  'topic_id'    => $topics,
					  'post_id'     => $posts,
					  'topic_max'   => intval( count( $topic_array ) ),
					  'post_max'    => intval( count( $post_array  ) ),
					  'keywords'    => str_replace( '"', "", $keywords ),
					  'query_cache' => $more,
					);
		
	}
	

}

?>