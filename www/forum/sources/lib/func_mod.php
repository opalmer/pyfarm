<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionboard.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@ibforums.com
|   Licence Info: http://www.invisionpower.com
+---------------------------------------------------------------------------
|
|   > Moderator Core Functions
|   > Module written by Matt Mecham
|   > DBA Checked: Fri 21 May 2004
|
+--------------------------------------------------------------------------
| NOTE:
| This module does not do any access/permission checks, it merely
| does what is asked and returns - see function for more info
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}
class func_mod
{
	var $ipsclass;
	
	var $topic 			= "";
	var $forum 			= "";
	var $error 			= "";
	
	var $auto_update 	= FALSE;
	
	var $stm   			= "";
	var $upload_dir 	= "./uploads";
	
	var $moderator  	= "";
	
	//-----------------------------------------
	// @modfunctions: constructor
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE)
	//-----------------------------------------	
	
	function func_mod()
	{
		$this->error = "";
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @init: initialize module (allows us to create new obj)
	// -----------
	// Accepts: References to @$forum [ @$topic , @$moderator ]
	// Returns: NOTHING (TRUE)
	//-----------------------------------------
	
	function init($forum="", $topic="", $moderator="")
	{
		$this->upload_dir = $this->ipsclass->vars['upload_dir'];
		
		$this->forum = $forum;
		
		if ( is_array($topic) )
		{
			$this->topic = $topic;
		}
		
		if ( is_array($moderator) )
		{
			$this->moderator = $moderator;
		}
		
		return TRUE;
	}
	
	
	//-----------------------------------------
	// @post_delete: delete post ID(s)
	// -----------
	// Accepts: $id (array | string) 
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function post_delete($id)
	{
		$posts      = array();
		$attach_tid = array();
		$topics     = array();
		
		$this->error = "";

		if ( is_array( $id ) )
		{
			// Better safe than sorry - this should have already been done though
			$id = $this->ipsclass->clean_int_array( $id );
			
			if ( count($id) > 0 )
			{
				$pid = " IN(".implode(",",$id).")";
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$pid   = "=$id";
			}
			else
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
		// Get Stuff
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pid, topic_id', 'from' => 'posts', 'where' => 'pid'.$pid ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$posts[ $r['pid'] ]       = $r['topic_id'];
			$topics[ $r['topic_id'] ] = 1;
		}
		
		//-----------------------------------------
		// Is there an attachment to this post?
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='post' AND attach_rel_id".$pid ) );
		$this->ipsclass->DB->simple_exec();
		
		$attach_ids = array();
		
		while ( $killmeh = $this->ipsclass->DB->fetch_row( ) )
		{
			if ( $killmeh['attach_location'] )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_location'] );
			}
			if ( $killmeh['attach_thumb_location'] )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
			}
			
			$attach_ids[] = $killmeh['attach_id'];
			$attach_tid[ $posts[ $killmeh['attach_pid'] ] ] = $posts[ $killmeh['attach_pid'] ];
		}
		
		if ( count($attach_ids) )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'attachments', 'where' => "attach_id IN(".implode(",",$attach_ids).")" ) );
			
			//-----------------------------------------
			// Recount topic upload marker
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/classes/post/class_post.php' );
			
			$postlib           =  new class_post();
			$postlib->ipsclass =& $this->ipsclass;
			
			foreach( $attach_tid as $tid )
			{
				$postlib->pf_recount_topic_attachments($tid);
			}
		}
		
		//-----------------------------------------
		// delete the post
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'posts', 'where' => "pid".$pid ) );
		
		//-----------------------------------------
		// Update the stats
		//-----------------------------------------
		
		$this->ipsclass->cache['stats']['total_replies'] -= count($posts);
		
		$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0 ) );
		
		//-----------------------------------------
		// Update all relevant topics
		//-----------------------------------------
		
		foreach( array_keys($topics) as $tid )
		{
			$this->rebuild_topic($tid);
		}
		
		$this->add_moderate_log("", "", "", $pid, "Deleted posts ($pid)");
		return TRUE;
	}
	
	//-----------------------------------------
	// @topic_add_reply: Appends topic with reply
	// -----------
	// Accepts: $post, $tids = array( 'tid', 'forumid' );
	//         
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function rebuild_topic($tid, $doforum=1)
	{
		$tid = intval($tid);
		
		if ( $this->ipsclass->vars['post_order_column'] != 'post_date' )
		{
			$this->ipsclass->vars['post_order_column'] = 'pid';
		}
		
		if ( $this->ipsclass->vars['post_order_sort'] != 'desc' )
		{
			$this->ipsclass->vars['post_order_sort'] = 'asc';
		}
				
		//-----------------------------------------
		// Get the correct number of replies
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id=$tid and queued != 1" ) );
		$this->ipsclass->DB->simple_exec();
		
		$posts = $this->ipsclass->DB->fetch_row();
		
		$pcount = intval( $posts['posts'] - 1 );
		
		//-----------------------------------------
		// Get the correct number of queued replies
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id=$tid and queued=1" ) );
		$this->ipsclass->DB->simple_exec();
		
		$qposts = $this->ipsclass->DB->fetch_row();
		
		$qpcount = intval( $qposts['posts'] );
		
		//-----------------------------------------
		// Get last post info
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'mod_func_get_last_post', array( 'tid' => $tid, 'orderby' => $this->ipsclass->vars['post_order_column'] ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$last_post = $this->ipsclass->DB->fetch_row();
		
		$last_poster_name = $last_post['members_display_name'] ? $last_post['members_display_name'] : $last_post['author_name'];
		
		//-----------------------------------------
		// Get first post info
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.post_date, p.author_id, p.author_name, p.pid',
												 'from'     => array( 'posts' => 'p' ),
												 'where'    => "p.topic_id=$tid",
												 'order'    => "p.{$this->ipsclass->vars['post_order_column']} ASC",
												 'limit'    => array(0,1),
												 'add_join' => array( 0 => array( 'select' => 'm.id, m.members_display_name',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => "p.author_id=m.id",
																				  'type'   => 'left' ) )
										)      );
		
		$this->ipsclass->DB->exec_query();
		
		$first_post = $this->ipsclass->DB->fetch_row();
		
		$first_poster_name = $first_post['members_display_name'] ? $first_post['members_display_name'] : $first_post['author_name'];
		
		//-----------------------------------------
		// Get number of attachments
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'mod_func_get_attach_count', array( 'tid' => $tid ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$attach = $this->ipsclass->DB->fetch_row();

		//-----------------------------------------
		// Update topic
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'starter_name'     => 'string',
													  'last_poster_name' => 'string' );		

		$this->ipsclass->DB->do_update( 'topics', array( 'last_post'         => intval($last_post['post_date'] ? $last_post['post_date'] : $first_post['post_date']),
														 'last_poster_id'    => intval($last_post['author_id'] ? $last_post['author_id'] : ( $pcount > 0 ? 0 : $first_post['author_id'] )),
														 'last_poster_name'  => $last_poster_name ? $last_poster_name : ( $pcount > 0 ? $this->ipsclass->lang['global_guestname'] : $first_poster_name ),
														 'topic_queuedposts' => intval($qpcount),
														 'posts'             => intval($pcount),
														 'starter_id'        => intval($first_post['author_id']),
														 'starter_name'      => $first_poster_name,
														 'start_date'        => intval($first_post['post_date']),
														 'topic_firstpost'   => intval($first_post['pid']),
														 'topic_hasattach'   => intval($attach['count'])
													   ), 'tid='.$tid );
									   
		//-----------------------------------------
		// Update first post
		//-----------------------------------------
		
		if ( (!isset($first_post['new_topic']) OR $first_post['new_topic'] != 1) and $first_post['pid'] )
		{
			$this->ipsclass->DB->do_shutdown_update( 'posts', array( 'new_topic' => 0 ), 'topic_id='.$tid );
			$this->ipsclass->DB->do_shutdown_update( 'posts', array( 'new_topic' => 1 ), 'pid='.$first_post['pid'] );
		}
		
		//-----------------------------------------
		// If we deleted the last post in a topic that was
		// the last post in a forum, best update that :D
		//-----------------------------------------
		
		if ( ($this->ipsclass->forums->forum_by_id[ $last_post['forum_id'] ]['last_id'] == $tid) AND ($doforum == 1) )
		{
			$tt = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'title, tid, last_post, last_poster_id, last_poster_name',
																 'from'   => 'topics',
																 'where'  => 'forum_id='.$last_post['forum_id'].' and approved=1',
																 'order'  => 'last_post desc',
																 'limit'  => array( 0,1 )
														)      );
			
			$dbs = array(
						 'last_title'       => $tt['title']            ? $tt['title']            : "",
						 'last_id'          => $tt['tid']              ? $tt['tid']              : 0,
						 'last_post'        => $tt['last_post']        ? $tt['last_post']        : 0,
						 'last_poster_name' => $tt['last_poster_name'] ? $tt['last_poster_name'] : "",
						 'last_poster_id'   => $tt['last_poster_id']   ? $tt['last_poster_id']   : 0,
						);
						
			$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string',
														  'last_title'		 => 'string' );
			
			$this->ipsclass->DB->do_update( 'forums', $dbs, "id=".intval($this->forum['id']) );
			
			if ( $this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['newest_id'] == $tid )
			{
				$sort_key = $this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['sort_key'];
				
				$tt = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'title, tid',
																	 'from'   => 'topics',
																	 'where'  => 'forum_id='.$this->forum['id'].' and approved=1',
																	 'order'  => 'start_date desc',
																	 'limit'  => array( 0,1 )
															)      );
															
				$dbs['newest_id'] = $tt['tid']		? $tt['tid'] 	: "";
				$dbs['newest_title'] = $tt['title'] ? $tt['title']	: "";
			}
			
			//-----------------------------------------
			// Update forum cache
			//-----------------------------------------
			
			foreach( $dbs as $k => $v )
			{
				$this->ipsclass->cache['forum_cache'][ $this->forum['id'] ][ $k ] = $v;
			}
			
			$this->ipsclass->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 0 ) );
		}
		return TRUE;
	}
	
	//-----------------------------------------
	// @topic_add_reply: Appends topic with reply
	// -----------
	// Accepts: $post, $tids = array( 'tid', 'forumid' );
	//         
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_add_reply($post="", $tids=array(), $incpost=0)
	{
		if ( $post == "" )
		{
			return FALSE;
		}
		
		if ( count( $tids ) < 1 )
		{
			return FALSE;
		}
		
		$post = array(
					  'author_id'   => $this->ipsclass->member['id'],
					  'use_sig'     => 1,
					  'use_emo'     => 1,
					  'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
					  'post_date'   => time(),
					  'icon_id'     => 0,
					  'post'        => $post,
					  'author_name' => $this->ipsclass->member['members_display_name'],
					  'topic_id'    => "",
					  'queued'      => 0,
					 );
					 
		//-----------------------------------------
		// Add posts...
		//-----------------------------------------
		 
		$seen_fids = array();
		$add_posts = 0;
		
		foreach( $tids as $row )
		{
			$tid = intval($row[0]);
			$fid = intval($row[1]);
			$pa  = array();
			$ta  = array();
			
			if ( ! in_array( $fid, $seen_fids ) )
			{
				$seen_fids[] = $fid;
			}
			
			if ( $tid and $fid )
			{
				$pa = $post;
				$pa['topic_id'] = $tid;
				
				$this->ipsclass->DB->do_insert( 'posts', $pa );
				
				$ta = array (
							  'last_poster_id'   => $this->ipsclass->member['id'],
							  'last_poster_name' => $this->ipsclass->member['members_display_name'],
							  'last_post'        => $pa['post_date'],
							);
							
				$db_string = $this->ipsclass->DB->compile_db_update_string( $ta );
				
				$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );		
				
				
				$this->ipsclass->DB->simple_exec_query( array( 'update' => 'topics', 'set' => $db_string.", posts=posts+1", 'where' => 'tid='.$tid ) );
		
				$add_posts++;
			}
		}
				
		if ( $this->auto_update != FALSE )
		{
			if ( count($seen_fids) > 0 )
			{
				foreach( $seen_fids as $id )
				{
					$this->forum_recount( $id );
				}
			}
		}
		
		if ( $add_posts > 0 )
		{
			$this->ipsclass->cache['stats']['total_replies'] += $add_posts;
		
			$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0 ) );
		
			//-----------------------------------------
			// Update current members stuff
			//-----------------------------------------
		
			$pcount = "";
			$mgroup = "";
			
			
			if ( ($this->forum['inc_postcount']) and ($incpost != 0) )
			{
				//-----------------------------------------
				// Increment the users post count
				//-----------------------------------------
				
				$pcount = "posts=posts+".$add_posts.", ";
			}
			
			//-----------------------------------------
			// Are we checking for auto promotion?
			//-----------------------------------------
			
			if ($this->ipsclass->member['g_promotion'] != '-1&-1')
			{
				list($gid, $gposts) = explode( '&', $this->ipsclass->member['g_promotion'] );
				
				if ( $gid > 0 and $gposts > 0 )
				{
					if ( $this->ipsclass->member['posts'] + $add_posts >= $gposts )
					{
						$mgroup = "mgroup='$gid', ";
					}
				}
			}
			
			$this->ipsclass->DB->simple_exec_query( array( 'update' => 'members', 'set' => $pcount.$mgroup."last_post=".time(), 'where' => "id=".$this->ipsclass->member['id'] ) );
		}
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @topic_close: close topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_close($id)
	{
		$this->stm_init();
		$this->stm_add_close();
		$this->stm_exec($id);
		return TRUE;
	}
	
	
	//-----------------------------------------
	// @topic_open: open topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_open($id)
	{
		$this->stm_init();
		$this->stm_add_open();
		$this->stm_exec($id);
		return TRUE;
	}
	
	//-----------------------------------------
	// @topic_pin: pin topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_pin($id)
	{
		$this->stm_init();
		$this->stm_add_pin();
		$this->stm_exec($id);
		return TRUE;
	}
	
	//-----------------------------------------
	// @topic_unpin: unpin topic ID's
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_unpin($id)
	{
		$this->stm_init();
		$this->stm_add_unpin();
		$this->stm_exec($id);
		return TRUE;
	}
	
	
	//-----------------------------------------
	// @topic_delete: deletetopic ID(s)
	// -----------
	// Accepts: $id (array | string) 
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_delete($id, $nostats=0)
	{
		$posts  = array();
		$attach = array();
		
		$this->error = "";

		if ( is_array( $id ) )
		{
			$id = $this->ipsclass->clean_int_array( $id );
			
			if ( count($id) > 0 )
			{
				$tid = " IN(".implode(",",$id).")";
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$tid   = "=$id";
			}
			else
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
		// Remove polls assigned to this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'polls', 'where' => "tid".$tid ) );
		
		//-----------------------------------------
		// Remove polls assigned to this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'voters', 'where' => "tid".$tid ) );
		
		//-----------------------------------------
		// Remove subscriptions to this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'tracker', 'where' => "topic_id".$tid ) );
		
		//-----------------------------------------
		// Remove ratings to this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topic_ratings', 'where' => "rating_tid".$tid ) );
		
		//-----------------------------------------
		// Remove topics read markers for this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topics_read', 'where' => "read_tid".$tid ) );		
		
		//-----------------------------------------
		// Remove stored views for this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topic_views', 'where' => "views_tid".$tid ) );		

		//-----------------------------------------
		// Remove polls assigned to this topic
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topics', 'where' => "tid".$tid ) );
		
		//-----------------------------------------
		// Get PIDS for attachment deletion
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pid', 'from' => 'posts', 'where' => "topic_id".$tid ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$posts[] = $r['pid'];
		}
		
		//-----------------------------------------
		// Remove the attachments
		//-----------------------------------------
		
		if ( count( $posts ) )
		{
			$this->ipsclass->DB->simple_construct( array( "select" => '*', 'from' => 'attachments', 'where' => "attach_rel_module='post' AND attach_rel_id IN (".implode(",",$posts).")" ) );
			$o = $this->ipsclass->DB->simple_exec();
			
			while ( $killmeh = $this->ipsclass->DB->fetch_row( $o ) )
			{
				if ( $killmeh['attach_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_location'] );
				}
				if ( $killmeh['attach_thumb_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
				}
				
				$attach[] = $killmeh['attach_id'];
			}
			
			if ( count( $attach ) )
			{
				$this->ipsclass->DB->simple_construct( array( 'delete' => 'attachments', 'where' => "attach_id IN (".implode(",",$attach).")" ) );
				$this->ipsclass->DB->simple_exec();
			}
		}
		
		//-----------------------------------------
		// Remove the posts
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'posts', 'where' => "topic_id".$tid ) );
		
		//-----------------------------------------
		// Recount forum...
		//-----------------------------------------
		
		if ( $nostats == 0 )
		{
			if ( $this->forum['id'] )
			{
				$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
				$this->forum_recount( $this->forum['id'] );
			}
			
			$this->stats_recount();
		}
		return TRUE;
	}
	
	
	//-----------------------------------------
	// @topic_move: move topic ID(s)
	// -----------
	// Accepts: $topics (array | string) $source,
	//          $moveto
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function topic_move($topics, $source, $moveto, $leavelink=0)
	{
		$this->error = "";
		
		$source = intval($source);
		$moveto = intval($moveto);
		
		if ( is_array( $topics ) )
		{
			$topics = $this->ipsclass->clean_int_array( $topics );
			
			if ( count($topics) > 0 )
			{
				$tid = " IN(".implode(",",$topics).")";
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( intval($topics) )
			{
				$tid   = "=$topics";
			}
			else
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
		// Update the topic
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'topics', array( 'forum_id' => $moveto ), "forum_id=$source AND tid".$tid );
		
		//-----------------------------------------
		// Update the polls
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'polls', array( 'forum_id' => $moveto ), "forum_id=$source AND tid".$tid );
		
		//-----------------------------------------
		// Are we leaving a stink er link?
		//-----------------------------------------
		
		if ( $leavelink != 0 )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => "tid".$tid ) );
			$oq = $this->ipsclass->DB->simple_exec();
			
			$this->ipsclass->DB->force_data_type = array( 'title'			 => 'string',
														  'starter_name'     => 'string',
														  'last_poster_name' => 'string' );
			
			while ( $row = $this->ipsclass->DB->fetch_row($oq) )
			{
				$this->ipsclass->DB->do_insert( 'topics', array (
												  'title'            => $row['title'],
												  'description'      => $row['description'],
												  'state'            => 'link',
												  'posts'            => 0,
												  'views'            => 0,
												  'starter_id'       => $row['starter_id'],
												  'start_date'       => $row['start_date'],
												  'starter_name'     => $row['starter_name'],
												  'last_post'        => $row['last_post'],
												  'forum_id'         => $source,
												  'approved'         => 1,
												  'pinned'           => 0,
												  'moved_to'         => $row['tid'].'&'.$moveto,
												  'last_poster_id'   => $row['last_poster_id'],
												  'last_poster_name' => $row['last_poster_name']
									  )        );
			}
		
		}
		
		//-----------------------------------------
		// Sort out subscriptions
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'mod_func_get_topic_tracker', array( 'tid' => $tid ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$trid_to_delete = array();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Match the perm group against forum_mask
			//-----------------------------------------
			
			$perm_id = $r['g_perm_id'];
			
			if ( $r['org_perm_id'] )
			{
				$perm_id = $r['org_perm_id'];
			}
			
			$pass = 0;
			
			$forum_perm_array = explode( ",", $this->ipsclass->forums->forum_by_id[ $r['forum_id'] ]['read_perms'] );
			
			foreach( explode( ',', $perm_id ) as $u_id )
			{
				if ( in_array( $u_id, $forum_perm_array ) )
				{
					$pass = 1;
				}
			}
			
			if ( $pass != 1 )
			{
				$trid_to_delete[] = $r['trid'];
			}		
		}
		
		if ( count($trid_to_delete) > 0 )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'tracker', 'where' => "trid IN(".implode(',', $trid_to_delete ).")" ) );
		}
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stats_recount: Recount all topics & posts
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stats_recount()
	{
		if ( ! is_array($this->ipsclass->cache['stats']) )
		{
			$stats = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='stats'" ) );
		
			$this->ipsclass->cache['stats'] = unserialize($this->ipsclass->txt_stripslashes($stats['cs_value']));
		}
		
		$topics = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as tcount',
																 'from'   => 'topics',
												 				 'where'  => 'approved=1' ) );
		
		$posts  = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'SUM(posts) as replies',
												 				 'from'   => 'topics',
												 				 'where'  => 'approved=1' ) );
												 				 
		$this->ipsclass->cache['stats']['total_topics']  = $topics['tcount'];
		$this->ipsclass->cache['stats']['total_replies'] = $posts['replies'];
		
		$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0, 'donow' => 1 ) );
		return TRUE;
	}
	
	
	//-----------------------------------------
	// @forum_recount: Recount topic & posts in a forum
	// -----------
	// Accepts: forum_id
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function forum_recount($fid="")
	{
		$fid = intval($fid);
		
		if ( ! $fid )
		{
			if ( $this->forum['id'] )
			{
				$fid = $this->forum['id'];
			}
			else
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
		// Get the topics..
		//-----------------------------------------
		
		$topics = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as count',
																 'from'   => 'topics',
																 'where'  => "approved=1 and forum_id=$fid" ) );
		
		//-----------------------------------------
		// Get the QUEUED topics..
		//-----------------------------------------
		
		$queued_topics = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as count',
																		'from'   => 'topics',
																		'where'  => "approved=0 and forum_id=$fid" ) );
		
		//-----------------------------------------
		// Get the posts..
		//-----------------------------------------
		
		$posts = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'SUM(posts) as replies',
																'from'   => 'topics',
																'where'  => "approved=1 and forum_id=$fid" ) );
		
		//-----------------------------------------
		// Get the QUEUED posts..
		//-----------------------------------------
		
		$queued_posts = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'SUM(topic_queuedposts) as replies',
													   				   'from'   => 'topics',
													   				   'where'  => "forum_id=$fid" ) );
		
		//-----------------------------------------
		// Get the forum last poster..
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid, title, last_poster_id, last_poster_name, last_post',
									 				  'from'   => 'topics',
									  				  'where'  => "approved=1 and forum_id=$fid",
									  				  'order'  => 'last_post DESC',
									  				  'limit'  => array( 0,1 ) ) );
		
		$this->ipsclass->DB->simple_exec();
		
		$last_post = $this->ipsclass->DB->fetch_row();
		

		$sort_key = $this->ipsclass->cache['forum_cache'][ $fid ]['sort_key'] ? 
					$this->ipsclass->cache['forum_cache'][ $fid ]['sort_key'] : 'last_post';
		
		$newest_topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'title, tid',
															 'from'   => 'topics',
															 'where'  => 'forum_id='.$fid.' and approved=1',
															 'order'  => 'start_date desc',
															 'limit'  => array( 0,1 )
													)      );
													
		//-----------------------------------------
		// Reset this forums stats
		//-----------------------------------------
		
		$dbs = array(
					  'last_poster_id'   => intval($last_post['last_poster_id']),
					  'last_poster_name' => $last_post['last_poster_name'],
					  'last_post'        => intval($last_post['last_post']),
					  'last_title'       => $last_post['title'],
					  'last_id'          => intval($last_post['tid']),
					  'topics'           => intval($topics['count']),
					  'posts'            => intval($posts['replies']),
					  'queued_topics'    => intval($queued_topics['count']),
					  'queued_posts'     => intval($queued_posts['replies']),
					  'newest_id'		 => intval($newest_topic['tid']),
					  'newest_title'	 => $newest_topic['title'],
					);
					
		if ( isset($this->ipsclass->forums->forum_by_id[ $fid ]['_update_deletion']) AND $this->ipsclass->forums->forum_by_id[ $fid ]['_update_deletion'] )
		{
			$dbs['forum_last_deletion'] = time();
		}
		
		$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string',
													  'last_title'		 => 'string',
													  'newest_title'	 => 'string' );		
												 
		$this->ipsclass->DB->do_update( 'forums', $dbs, "id=".$fid );
		
		//-----------------------------------------
		// Update forum cache
		//-----------------------------------------
		
		foreach( $dbs as $k => $v )
		{
			$this->ipsclass->cache['forum_cache'][ $fid ][ $k ] = $v;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 0 ) );
		
		return TRUE;
	}
	
	
	//-----------------------------------------
	// @stm_init: Clear statement ready for multi-actions
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_init()
	{
		$this->stm = array();
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_exec: Executes stored statement
	// -----------
	// Accepts: Array ID's | Single ID
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_exec($id)
	{
		if ( count($this->stm) < 1 )
		{
			return FALSE;
		}
		
		$final_array = array();
		
		foreach( $this->stm as $real_array )
		{
			foreach( $real_array as $k => $v )
			{
				$final_array[ $k ] = $v;
			}
		}
		
		$db_string = $this->ipsclass->DB->compile_db_update_string( $final_array );
		
		if ( is_array($id) )
		{
			$id = $this->ipsclass->clean_int_array( $id );
			
			if ( count($id) > 0 )
			{
				$this->ipsclass->DB->simple_exec_query( array( 'update' => 'topics', 'set' => $db_string, 'where' => "tid IN(".implode( ",", $id ).")" ) );
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else if ( intval($id) != "" )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'update' => 'topics', 'set' => $db_string, 'where' => "tid=".intval($id) ) );
		}
		else
		{
			return FALSE;
		}
	}
	
	
	//-----------------------------------------
	// @stm_add_pin: add pin command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_pin()
	{
		$this->stm[] = array( 'pinned' => 1 );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_unpin: add unpin command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_unpin()
	{
		$this->stm[] = array( 'pinned' => 0 );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_close: add close command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_close()
	{
		$this->stm[] = array( 'state' => 'closed' );
		$this->stm[] = array( 'topic_open_time' => 0 );
		$this->stm[] = array( 'topic_close_time' => 0 );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_open: add open command to statement
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_open()
	{
		$this->stm[] = array( 'state' => 'open' );
		$this->stm[] = array( 'topic_open_time' => 0 );
		$this->stm[] = array( 'topic_close_time' => 0 );
				
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_title: add edit title command to statement
	// -----------
	// Accepts: new_title
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_title($new_title='')
	{
		if ( $new_title == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'title' => $new_title );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_desc: add edit desc command to statement
	// -----------
	// Accepts: new_title
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_desc($new_desc='')
	{
		if ( $new_desc == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'description' => $new_desc );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_approve: Approve topic
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_approve()
	{
		$this->stm[] = array( 'approved' => 1 );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @stm_add_unapprove: Unapprove topic
	// -----------
	// Accepts: NOTHING
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function stm_add_unapprove()
	{
		$this->stm[] = array( 'approved' => 0 );
		
		return TRUE;
	}
	
	//-----------------------------------------
	// @sql_prune_create: returns formatted SQL statement
	// -----------
	// Accepts: forum_id, poss_starter_id, poss_topic_state, poss_post_min
	//			poss_date_expiration, poss_ignore_pin_state
	// Returns: formatted sql query
	//-----------------------------------------
	
	function sql_prune_create( $forum_id, $starter_id="", $topic_state="", $post_min="", $date_exp="", $ignore_pin="", $pergo=0, $limit=0 )
	{
		$this->ipsclass->DB->build_query( array( 'select' => 'tid', 'from' => 'topics', 'where' => 'forum_id='.intval($forum_id) ) );
		
		$sql = $this->ipsclass->DB->cur_query;
		
		if ( intval($date_exp) )
		{
			$sql .= " AND last_post < $date_exp";
		}
		
		if ( intval($starter_id) )
		{
			$sql .= " AND starter_id=$starter_id";
			
		}
		
		if ( intval($post_min) )
		{
			$sql .= " AND posts < $post_min";
		}
		
		if ($topic_state != 'all')
		{
			if ($topic_state)
			{
				$sql .= " AND state='$topic_state'";
			}
		}
		
		if ( $ignore_pin != "" )
		{
			$sql .= " AND pinned=0";
		}
		
		if ( $pergo )
		{
			$this->ipsclass->DB->cur_query = $sql;
			$this->ipsclass->DB->simple_limit( 0, $pergo );
			$sql = $this->ipsclass->DB->cur_query;
			$this->ipsclass->DB->flush_query();
		}
		
		return $sql;
	}
	
	//-----------------------------------------
	// @mm_authorize: Authorizes current member
	// -----------
	// Accepts: (NOTHING: Should already be passed to init)
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function mm_authorize()
	{
		$pass_go = FALSE;
		
		if ( $this->ipsclass->member['id'] )
		{
			if ( $this->ipsclass->member['g_is_supmod'] )
			{ 
				$pass_go = TRUE;
			}
			else if ( $this->moderator['can_mm'] == 1 )
			{
				$pass_go = TRUE;
			}
		}
		
		return $pass_go;
	}
	
	//-----------------------------------------
	// @mm_check_id_in_forum: Checks to see if mm_id is in
    //                        the forum saved topic_mm_id
	// -----------
	// Accepts: (forum_topic_mm_id , this_mm_id)
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function mm_check_id_in_forum( $fid, $mm_data)
	{
		$retval = FALSE;
		
		if (  $mm_data['mm_forums'] == '*' OR strstr( ",". $mm_data['mm_forums'].",", ",".$fid."," ) )
		{
			$retval = TRUE;
		}
		
		return $retval;	
	}
	
	//-----------------------------------------
	// @add_moderate_log: Adds entry to mod log
	// -----------
	// Accepts: (forum_id, topic_id, topic_title, post_id, title)
	// Returns: NOTHING (TRUE/FALSE)
	//-----------------------------------------
	
	function add_moderate_log($fid, $tid, $pid, $t_title, $mod_title='Unknown')
	{
		$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );	
		
		$this->ipsclass->DB->do_insert( 'moderator_logs', array (
												  'forum_id'    => intval($fid),
												  'topic_id'    => intval($tid),
												  'post_id'     => intval($pid),
												  'member_id'   => $this->ipsclass->member['id'],
												  'member_name' => $this->ipsclass->member['members_display_name'],
												  'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												  'http_referer'=> htmlspecialchars($this->ipsclass->my_getenv('HTTP_REFERER')),
												  'ctime'       => time(),
												  'topic_title' => substr( $t_title, 0, 128 ),
												  'action'      => substr( $mod_title, 0, 128 ),
												  'query_string'=> htmlspecialchars($this->ipsclass->my_getenv('QUERY_STRING')),
											  )  );
		return TRUE;
	}
	
	
}

?>