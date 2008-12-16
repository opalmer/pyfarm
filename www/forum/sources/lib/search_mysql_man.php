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
|   > $Date: 2007-10-17 16:29:37 -0400 (Wed, 17 Oct 2007) $
|   > $Revision: 1133 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > MySQL Manual Search Library
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
     
    //-----------------------------------------
	// Constructor
	//-----------------------------------------
    	
    function search_lib(&$that)
    {
		$this->is          = &$that; // hahaha!
    	$this->resultlimit = $this->is->resultlimit;
 	}
 	
 	//-----------------------------------------
	// Main Board Search-e-me-doo-daa
	//-----------------------------------------
 

	function do_main_search()
	{
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
		
		$type = 'postonly';
		
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
		
		$check_keywords = trim($keywords);
		
		$check_keywords = str_replace( "%", "", $check_keywords );
		
		if ( (! $check_keywords) or ($check_keywords == "") or (! isset($check_keywords) ) )
		{
			if ($type != 'nameonly')
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_words') );
			}
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->input['search_in'] == 'titles')
		{
			$this->is->search_in = 'titles';
		}
		
		//-----------------------------------------
		
		$forums = $this->is->get_searchable_forums();
		
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
			if (isset($this->ipsclass->input['sort_key']) AND $this->ipsclass->input['sort_key'] == $v)
			{
				$this->is->sort_key = $v;
			}
		}
		
		//-----------------------------------------
		
		foreach ( array( 1, 7, 30, 60, 90, 180, 365, 0 ) as $v )
		{
			if (isset($this->ipsclass->input['prune']) AND $this->ipsclass->input['prune'] == $v)
			{
				$this->is->prune = $v;
			}
		}
		
		//-----------------------------------------
		
		if (isset($this->ipsclass->input['sort_order']) AND $this->ipsclass->input['sort_order'] == 'asc')
		{
			$this->is->sort_order = 'asc';
		}
		
		//-----------------------------------------
		
		if (isset($this->ipsclass->input['result_type']) AND $this->ipsclass->input['result_type'] == 'posts')
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
			
			$topics_datecut = "t.last_post $gt_lt $time AND";
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
	   
		// Is this a membername search?
		
		$name_filter = trim( $name_filter );
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
			
			// Error out if we matched no members
			
			if ($member_string == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_name_search_results') );
			}
			
			$posts_name  = " AND p.author_id IN ($member_string)";
			$topics_name = " AND t.starter_id IN ($member_string)";
			
		}
		
		if ( $this->ipsclass->txt_mb_strlen(trim($keywords)) > 100 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_long', 'EXTRA' => 100) );
		}
		
		if ( $type != 'nameonly' )
		{
			if (preg_match( "/ and|or /", $keywords) )
			{
				preg_match_all( "/(^|and|or)\s{1,}(\S+?)\s{1,}/", $keywords, $matches );
				
				$title_like = "(";
				$post_like  = "(";
				
				for ($i = 0 ; $i < count($matches[0]) ; $i++ )
				{
					$boolean = $matches[1][$i];
					$word    = trim($matches[2][$i]);
					
					if (strlen($word) < $this->ipsclass->vars['min_search_word'])
					{
						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
					}
					
					if ($boolean)
					{
						$boolean = " $boolean";
					}
					
					$title_like .= "$boolean LOWER(t.title) LIKE '%$word%' ";
					$post_like  .= "$boolean LOWER(p.post) LIKE '%$word%' ";
				}
				
				$title_like .= ")";
				$post_like  .= ")";
			
			}
			else
			{
				if (strlen(trim($keywords)) < $this->ipsclass->vars['min_search_word'])
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_word_short', 'EXTRA' => $this->ipsclass->vars['min_search_word']) );
				}
				
				$title_like = " LOWER(t.title) LIKE '%".trim($keywords)."%' ";
				$post_like  = " LOWER(p.post) LIKE '%".trim($keywords)."%' ";
			}
		}
			
		//$posts_datecut $topics_datecut $post_like $title_like $posts_name $topics_name
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$my_invisible_forums_t = array();
		$my_invisible_forums_p = array();
		
		if( isset($this->ipsclass->cache['moderators']) AND is_array($this->ipsclass->cache['moderators']) AND count($this->ipsclass->cache['moderators']) )
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
		
		$t_forum_query = $this->ipsclass->member['g_is_supmod'] ? "t.forum_id IN ($forums)" :
						 ( count($my_invisible_forums_t) ? "( (t.forum_id IN ($forums) AND t.approved=1) OR t.forum_id IN(" . implode( ",", $my_invisible_forums_t ) . ") )" :
						 "( t.forum_id IN ($forums) AND t.approved=1)" );

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
		
		if ($type != 'nameonly')
		{
			if( $this->is->topic_search_only == 1 )
			{
				$topic_filter = " t.tid={$this->is->topic_id} AND ";
			}
			else
			{
				$topic_filter = "";
			}
			
			$topics_query = "SELECT t.tid, t.approved, t.forum_id
							FROM ".SQL_PREFIX."topics t
							WHERE {$topic_filter}{$topics_datecut} $t_forum_query
							$topics_name AND ($title_like)
							ORDER BY t.last_post {$this->is->sort_order}
							LIMIT {$this->resultlimit}";
		
		
			$posts_query = "SELECT p.pid, p.queued, t.approved, t.forum_id
						    FROM ".SQL_PREFIX."posts p
						     LEFT JOIN ".SQL_PREFIX."topics t on (t.tid=p.topic_id)
						    WHERE {$topic_filter}{$posts_datecut} $p_forum_query
						     $posts_name AND ($post_like)
						     ORDER BY p.post_date {$this->is->sort_order}
						     LIMIT {$this->resultlimit}";
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
						    LEFT JOIN ".SQL_PREFIX."topics t on (t.tid=p.topic_id)
						    WHERE $posts_datecut $p_forum_query
						    AND p.queued=0
						     $posts_name
						     ORDER BY p.post_date {$this->is->sort_order}
						     LIMIT {$this->resultlimit}";
		}
					   
		//-----------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//-----------------------------------------
		
		$topics = "";
		$posts  = "";
		$t_cnt  = 0;
		$p_cnt  = 0;
		$more   = "";
		
		//-----------------------------------------
		
		$this->ipsclass->DB->query($topics_query);
	
		$topic_max_hits = $this->ipsclass->DB->get_num_rows();
		
		if( $topic_max_hits == $this->resultlimit )
		{
			$more = 1;
		}

		while ($row = $this->ipsclass->DB->fetch_row() )
		{
			/*if( !$row['approved'] )
			{
				if( !$this->ipsclass->member['g_is_supmod'] )
				{
					if( !in_array( $row['forum_id'], $my_invisible_forums_t ) )
					{
						$topic_max_hits--;
						continue;
					}
				}
			}
					
			$t_cnt++;
				
			if ( $t_cnt > $this->resultlimit )
			{
				$more = 1;
				break;
			}*/
				
			$topics .= $row['tid'].",";
		}
		
		$this->ipsclass->DB->free_result();
		
		//-----------------------------------------
		
		$this->ipsclass->DB->query($posts_query);
	
		$post_max_hits = $this->ipsclass->DB->get_num_rows();
		
		if( $post_max_hits == $this->resultlimit )
		{
			$more = 1;
		}

		while ($row = $this->ipsclass->DB->fetch_row() )
		{
			/*if( $row['queued'] )
			{
				if( !$this->ipsclass->member['g_is_supmod'] )
				{
					if( !in_array( $row['forum_id'], $my_invisible_forums_p ) )
					{
						$post_max_hits--;
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
						$post_max_hits--;
						continue;
					}
				}
			}			
						
			$p_cnt++;
				
			if ( $p_cnt > $this->resultlimit )
			{
				$more = 1;
				break;
			}*/
			
			$posts .= $row['pid'].",";
		}
		
		$this->ipsclass->DB->free_result();
		
		//-----------------------------------------
		
		$topics = preg_replace( "/,$/", "", $topics );
		$posts  = preg_replace( "/,$/", "", $posts );
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ($topics == "" and $posts == "")
		{
			//$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//-----------------------------------------
		// If we are still here, return data like a good
		// boy (or girl). Yes Reg; or girl.
		// What have the Romans ever done for us?
		//-----------------------------------------
		
		return array(
					  'topic_id'  => $topics,
					  'post_id'   => $posts,
					  'topic_max' => $topic_max_hits,
					  'post_max'  => $post_max_hits,
					  'keywords'  => $keywords,
					  'query_cache' => $more,
					);
		
	}
	

}

?>