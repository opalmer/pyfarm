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
|   > $Date: 2007-09-17 18:05:43 -0400 (Mon, 17 Sep 2007) $
|   > $Revision: 1106 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Forum functions
|   > Module written by Matt Mecham
|   > Date started: 1st march 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class ad_member
{
	# Global
	var $ipsclass;
	
	#Html
	var $html;
	var $edit_html;
	var $editor_loaded = 0;
	
	var $base_url;
	var $modules = "";
	
	var $trash_forum = 0;

	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "content";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "mem";
	
	function auto_run()
	{
		//-----------------------------------------
    	// Get the sync module
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			$this->modules->ipsclass =& $this->ipsclass;
		}
		
		$this->ipsclass->admin->nav[] = array( "{$this->ipsclass->form_code}&code=edit", 'Member Management Home' );
		
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_member');
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		$this->ipsclass->acp_load_language( 'acp_lang_member' );
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'doform':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_do_edit_form();
				break;
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_do_edit();
				break;
			//-----------------------------------------
			case 'unsuspend':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':suspend' );
				$this->member_unsuspend();
				break;
			//-----------------------------------------
			case 'add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->member_add_form();
				break;
			case 'doadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->member_do_add();
				break;
			//-----------------------------------------
			case 'doprune':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->member_doprune();
				break;
			//-----------------------------------------
			// ranks / titles
			//-----------------------------------------
			case 'title':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':title-view' );
				$this->titles_start();
				break;
			case 'rank_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':title-edit' );
				$this->titles_rank_setup('edit');
				break;
			case 'rank_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':title-add' );
				$this->titles_rank_setup('add');
				break;
			case 'do_add_rank':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':title-add' );
				$this->titles_add_rank();
				break;
			case 'do_rank_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':title-edit' );
				$this->titles_edit_rank();
				break;
			case 'rank_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':title-remove' );
				$this->titles_delete_rank();
				break;
			

			//-----------------------------------------
			case 'changename':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_change_name_start();
				break;
			case 'dochangename':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_change_name_complete();
				break;
			case 'change_display_name':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_change_display_name();
				break;
			case 'change_display_name_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_change_display_name_do();
				break;
			case 'deleteposts':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_delete_posts_start();
				break;
			case 'deleteposts_process':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_delete_posts_process();
				break;
			//-----------------------------------------	
			
			case 'banmember':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':ban' );
				$this->member_suspend_start();
				break;
				
			case 'dobanmember':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':ban' );
				$this->member_suspend_complete();
				break;
			//-----------------------------------------
			// Change Passy
			//-----------------------------------------
			case 'changepassword':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_password_start();
				break;
			case 'dochangepassword':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->member_password_complete();
				break;
			//-----------------------------------------
			// Member search
			//-----------------------------------------
			case 'search':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->search_form();
				break;
			case 'searchresults':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->search_results();
				break;
			//-----------------------------------------
			// Delete / Prune
			//-----------------------------------------
			case 'member_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->member_delete();
				break;
				
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->search_form();
				break;
		}
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Member Delete posts, PROCESS
	/*-------------------------------------------------------------------------*/
	
	function member_delete_posts_process()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id       = intval( $this->ipsclass->input['mid'] );
		$delete_posts    = intval( $this->ipsclass->input['dposts'] );
		$delete_topics   = intval( $this->ipsclass->input['dtopics'] );
		$end             = intval( $this->ipsclass->input['dpergo'] ) ? intval( $this->ipsclass->input['dpergo'] ) : 50;
		$init            = intval( $this->ipsclass->input['init'] );
		$done            = 0;
		$start           = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$forums_affected = array();
		$topics_affected = array();
		$img             = '<img src="'.$this->ipsclass->skin_acp_url.'/images/aff_tick_small.png" border="0" alt="-" /> ';
		$posts_deleted   = 0;
		$topics_deleted  = 0;
		
		//--------------------------------------------
		// NOT INIT YET?
		//--------------------------------------------
		
		if ( ! $init )
		{
			$url = $this->ipsclass->base_url.'&'.$this->ipsclass->form_code_js."&code=deleteposts_process&dpergo={$this->ipsclass->input['dpergo']}"
																			  ."&st=0"
																			  ."&init=1"
																			  ."&dposts={$delete_posts}"
																			  ."&dtopics={$delete_topics}"
																			  ."&use_trash_can=".intval($this->ipsclass->input['use_trash_can'])
																			  ."&mid={$member_id}";
																			  
			$this->ipsclass->admin->output_multiple_redirect_init( $url );
		}
		
		//--------------------------------------------
		// Not loaded the func?
		//--------------------------------------------
		
		if ( ! is_object( $this->func_mod ) )
		{
			require_once( ROOT_PATH.'sources/lib/func_mod.php' );
			$this->func_mod           =  new func_mod();
			$this->func_mod->ipsclass =& $this->ipsclass;
		}
		
        //-----------------------------------------
        // Trash-can set up
        //-----------------------------------------
        
        $trash_append = '';
        
        if ( $this->ipsclass->vars['forum_trash_can_enable'] and $this->ipsclass->vars['forum_trash_can_id'] )
        {
        	if ( $this->ipsclass->cache['forum_cache'][ $this->ipsclass->vars['forum_trash_can_id'] ]['sub_can_post'] )
        	{
        		if ( $this->ipsclass->input['use_trash_can'] )
        		{
        			$this->trash_forum = $this->ipsclass->vars['forum_trash_can_id'];
        			$trash_append = " AND forum_id<>{$this->trash_forum}";
        		}
        	}
        }
        
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.$member_id ) );
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'posts',
													  'where'  => "author_id={$member_id}",
													  'order'  => 'pid ASC',
													  'limit'  => array( $start, $end ) ) );
		$outer = $this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// Process...
		//-----------------------------------------
		
		while( $r = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			$done++;

			//-----------------------------------------
			// Get topic...
			//-----------------------------------------
			
			$topic = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	   'from'   => 'topics',
																	   'where'  => 'tid='.$r['topic_id'].$trash_append ) );
			
			//-----------------------------------------
			// No longer a topic?
			//-----------------------------------------
			
			if ( ! $topic['tid'] )
			{
				continue;
			}
			
			//-----------------------------------------
			// Get number of MID posters
			//-----------------------------------------
			
			$topic_i_posted = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count',
																				'from'   => 'posts',
																				'where'  => 'author_id='.$member_id.' AND topic_id='.$r['topic_id'] ) );
			
			//-----------------------------------------
			// Aready deleted this topic?
			//-----------------------------------------
			
			if ( ! $topic_i_posted['count'])
			{
				continue;
			}
			
			//-----------------------------------------
			// First check: Our topic and no other replies?
			//-----------------------------------------
			
			if ( $topic['starter_id'] == $member_id AND $topic_i_posted['count'] == ( $topic['posts'] + 1 ) )
			{
				//-----------------------------------------
				// Ok, deleting topics or posts?
				//-----------------------------------------
				
				if ( ( $delete_posts OR $delete_topics ) AND ( $this->trash_forum and $this->trash_forum != $topic['forum_id'] ) )
				{
					//-----------------------------------------
					// Move, don't delete
					//-----------------------------------------
					
					$this->func_mod->topic_move($r['topic_id'], $topic['forum_id'], $this->trash_forum);
					
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$forums_affected[ $this->trash_forum ] = $this->trash_forum;
					
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}				
				else if ( $delete_posts OR $delete_topics )
				{
					$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'posts',
																	  'where'  => 'author_id='.$member_id.' AND topic_id='.$r['topic_id'] ) );
																	  
					$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'topics',
																	  'where'  => 'tid='.$r['topic_id'] ) );
																	  
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}
			}
			
			//-----------------------------------------
			// Is this a topic we started?
			//-----------------------------------------
			
			else if ( $topic['starter_id'] == $member_id AND $delete_topics )
			{
				if ( $this->trash_forum and $this->trash_forum != $topic['forum_id'] )
				{
					//-----------------------------------------
					// Move, don't delete
					//-----------------------------------------
					
					$this->func_mod->topic_move($r['topic_id'], $topic['forum_id'], $this->trash_forum);
					
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$forums_affected[ $this->trash_forum ] = $this->trash_forum;
					
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}				
				else
				{				
					$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'posts',
																	  'where'  => 'topic_id='.$r['topic_id'] ) );
																		  
					$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'topics',
																	  'where'  => 'tid='.$r['topic_id'] ) );
																	  
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_deleted++;
					$posts_deleted += $topic['posts'] + 1;
				}
					
			}
			
			//-----------------------------------------
			// Just delete the post, then
			//-----------------------------------------
			
			else if ( $delete_posts AND ! $r['new_topic'] )
			{
				if ( $this->trash_forum and $this->trash_forum != $topic['forum_id'] )
				{
					//-----------------------------------------
					// Set up and pass to split topic handler
					//-----------------------------------------
					
					$new_title   = "ACP - Posts Deleted From ".$topic['title'];
					$new_desc    = "ACP - Posts Deleted From TID: ".$topic['tid'];
					
					//-----------------------------------------
					// Is first post queued?
					//-----------------------------------------
					
					$topic_approved = 1;
					
					$this->ipsclass->DB->build_query( array( 'select'   => 'pid, queued',
															 'from'     => 'posts',
															 'where'    => "pid=".$r['pid'],
													)      );
					
					$this->ipsclass->DB->exec_query();
					
					$first_post = $this->ipsclass->DB->fetch_row();
					
					if( $first_post['queued'] )
					{
						$topic_approved = 0;
						$this->ipsclass->DB->do_update( 'posts', array( 'queued' => 0 ), 'pid='.$first_post['pid'] );
					}
					
					//-----------------------------------------
					// Complete a new dummy topic
					//-----------------------------------------
					
					$this->ipsclass->DB->do_insert( 'topics',  array(
													 'title'            => $new_title,
													 'description'      => $new_desc,
													 'state'            => 'open',
													 'posts'            => 0,
													 'starter_id'       => $member_id,
													 'starter_name'     => $member['members_display_name'],
													 'start_date'       => time(),
													 'last_poster_id'   => $member_id,
													 'last_poster_name' => $member['members_display_name'],
													 'last_post'        => time(),
													 'icon_id'          => 0,
													 'author_mode'      => 1,
													 'poll_state'       => 0,
													 'last_vote'        => 0,
													 'views'            => 0,
													 'forum_id'         => $this->trash_forum,
													 'approved'         => $topic_approved,
													 'pinned'           => 0,
									)               );
										
					$new_topic_id = $this->ipsclass->DB->get_insert_id();
			
					//-----------------------------------------
					// Move the posts
					//-----------------------------------------
					
					$this->ipsclass->DB->do_update( 'posts', array( 'topic_id' => $new_topic_id, 'new_topic' => 0, 'queued' => 0 ), "pid={$r['pid']}" ); 
					
					$this->ipsclass->DB->do_update( 'posts', array( 'new_topic' => 0 ), "topic_id={$topic['tid']}" );

					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$forums_affected[ $this->trash_forum ] = $this->trash_forum;
					$topics_affected[ $topic['tid']      ] = $topic['tid'];
					$topics_affected[ $new_topic_id      ] = $new_topic_id;
					
					$posts_deleted++;
				}
				else
				{
					$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'posts',
																	  'where'  => 'pid='.$r['pid'] ) );
																	  
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_affected[ $topic['tid']      ] = $topic['tid'];
					
					$posts_deleted++;
				}
			}
		}
		
		//-----------------------------------------
		// Rebuild topics and forums
		//-----------------------------------------
		
		if ( count( $topics_affected ) )
		{
			foreach( $topics_affected as $tid )
			{
				$this->func_mod->rebuild_topic( $tid, 0 );
			}
		}
		
		if ( count( $forums_affected ) )
		{
			foreach( $forums_affected as $fid )
			{
				$this->func_mod->forum_recount( $fid );
			}
		}
		
		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------
		
		if ( ! $done )
		{
		 	//-----------------------------------------
			// Recount stats..
			//-----------------------------------------
			
			$this->func_mod->stats_recount();
			
			//-----------------------------------------
			// Reset member's posts
			//-----------------------------------------
			
			$forums = array();
			
			foreach( $this->ipsclass->cache['forum_cache'] as $data )
			{
				if ( ! $data['inc_postcount'] )
				{
					$forums[] = $data['id'];
				}
			}
			

			if ( ! count( $forums ) )
			{
				$count = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'posts', 'where' => 'queued != 1 AND author_id='.$member_id ) );
			}
			else
			{
				$this->ipsclass->DB->build_query( array( 'select' 	=> 'count(p.pid) as count',
														 'from'		=> array( 'posts' => 'p' ),
														 'where'	=> 'p.queued <> 1 AND p.author_id='.$member_id.' AND t.forum_id NOT IN ('.implode(",",$forums).')',
														 'add_join'	=> array( 1 => array( 'type'	=> 'left',
														 								  'from'	=> array( 'topics' => 't' ),
														 								  'where'	=> 't.tid=p.topic_id'
														 					)			)
												)		);
				$this->ipsclass->DB->exec_query();
							 
				$count = $this->ipsclass->DB->fetch_row();
			}
			
			$new_post_count = intval( $count['count'] );
			
			$this->ipsclass->DB->do_update( 'members', array( 'posts' => $new_post_count ), 'id='.$member_id );
	
			$this->ipsclass->admin->output_multiple_redirect_done( $this->ipsclass->acp_lang['mem_posts_process_done'] );
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			
			$next = $start + $end;
			
			$url = $this->ipsclass->base_url.'&'.$this->ipsclass->form_code_js."&code=deleteposts_process&dpergo={$end}"
																			  ."&st={$next}"
																			  ."&init=1"
																			  ."&dposts={$delete_posts}"
																			  ."&dtopics={$delete_topics}"
																			  ."&use_trash_can=".intval($this->ipsclass->input['use_trash_can'])
																			  ."&mid={$member_id}";
																			  
			$text = sprintf( $this->ipsclass->acp_lang['mem_posts_process_more'], $end, $posts_deleted, $topics_deleted );
			
			$this->ipsclass->admin->output_multiple_redirect_hit( $url, $img.' '.$text );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Member Delete posts, start
	/*-------------------------------------------------------------------------*/
	
	function member_delete_posts_start()
	{
		//-----------------------------------------
		// Page set up
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = $this->ipsclass->acp_lang['mem_delete_title'];
		$this->ipsclass->admin->page_detail = $this->ipsclass->acp_lang['mem_delete_title_desc'];
		$this->ipsclass->admin->nav[] 		= array( '', 'Delete Member Posts' );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval($this->ipsclass->input['mid']);
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.$member_id ) );
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		//-----------------------------------------
		// Get number of topics member has started
		//-----------------------------------------
		
		$topics = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as count',
																	'from'   => 'topics',
																	'where'  => 'starter_id='.$member_id ) );
																	
		$posts  = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as count',
																	'from'   => 'posts',
																	'where'  => 'author_id='.$member_id ) );
		
		//-----------------------------------------
		// Got any posts?
		//-----------------------------------------
		
		if ( ! $posts['count'] )
		{
			$this->ipsclass->main_msg = "There are no posts to delete!";
			$this->search_results();
		}
		
		//-----------------------------------------
		// Get number of topics member has started
		//-----------------------------------------
		
		$this->ipsclass->acp_lang['mem_delete_delete_posts']  = sprintf( $this->ipsclass->acp_lang['mem_delete_delete_posts'] , intval($posts['count']) );
		$this->ipsclass->acp_lang['mem_delete_delete_topics'] = sprintf( $this->ipsclass->acp_lang['mem_delete_delete_topics'], intval($topics['count']) );
		
		$this->ipsclass->html .= $this->html->member_delete_posts_start( $member, intval($topics['count']), intval($posts['count']) );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// PASS: COMPLETE
	/*-------------------------------------------------------------------------*/
	
	function member_password_complete()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['password'] )
		{
			$this->ipsclass->main_msg = "You must enter a password!";
			$this->member_password_start();
		}
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Grab converge...
		//-----------------------------------------

		if ( $this->ipsclass->vars['ipbli_key'] == 'ipconverge' )
		{
			$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		  'from'   => 'converge_local',
																		  'where'  => 'converge_active=1' ) );

			//-----------------------------------------
			// Grab API class...
			//-----------------------------------------

			if ( ! is_object( $this->api_server ) )
			{
				require_once( KERNEL_PATH . 'class_api_server.php' );
				$this->api_server = new class_api_server();
			}

			if ( ! $converge['converge_active'] )
			{
				$this->ipsclass->main_msg = "Action failed. Could not locate IP.Converge API details. Recommend that you re-peform a handshake request from the IP.Converge control panel";
				$this->member_password_start();
			}
		}
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		if ( $this->ipsclass->txt_mb_strlen( $_POST['password'] ) > 32)
		{
			$this->ipsclass->admin->error("The new password is too long.  The maximum length is 32 characters.");
		}
		
		$salt = $this->ipsclass->converge->generate_password_salt(5);
		$salt = str_replace( '\\', "\\\\", $salt );
		
		$key  = $this->ipsclass->converge->generate_auto_log_in_key();
		
		$md5_once = md5( trim($this->ipsclass->input['password']) );
		
		$converge_member = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'members_converge', 'where' => "converge_email='{$member['email']}'" ) );
		
		if( !$converge_member )
		{
			$this->ipsclass->admin->error("There was an error loading the user's account");
		}
		
		$save_array = array();
		
		if ( $this->ipsclass->input['newsalt'] )
		{
			$save_array['converge_pass_salt'] = $salt;
			$save_array['converge_pass_hash'] = md5( md5($salt) . $md5_once );
		}
		else
		{
			$save_array['converge_pass_hash'] = md5( md5( $converge_member['converge_pass_salt'] ) . $md5_once );
		}
		
		//-----------------------------------------
		// Check Converge: Password
		//-----------------------------------------

        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	$this->han_login->change_pass( $member['email'], $md5_once );
    	
    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
    	{
			$this->ipsclass->main_msg = "The password could not be changed. IP.Converge returned an error.";
			$this->member_password_start();
			return;
    	}
		
		//-----------------------------------------
		// Local DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'members_converge', $save_array, "converge_email='{$member['email']}'" );
		
		if ( $this->ipsclass->input['newkey'] )
		{
			$this->ipsclass->DB->do_update( 'members', array( 'member_login_key' => $key ), 'id='.intval($this->ipsclass->input['id']) );
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "";
		
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_query .= '&'.$bit.'='.trim($this->ipsclass->input[ $bit ]);
		}
		
		$this->ipsclass->admin->save_log("Members Password Changed ( id: {$this->ipsclass->input['id']} )");
		
		$this->ipsclass->admin->done_screen("Password Changed", "Member Search", "{$this->ipsclass->form_code}".$page_query, "redirect" );
	}
	
	//-----------------------------------------
	//
	// PASS: START
	//
	//-----------------------------------------
	
	function member_password_start()
	{
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_array = array( 1 => array( 'code'  , 'dochangepassword'  ),
							 2 => array( 'act'   , 'mem'       ),
							 3 => array( 'id'    , $this->ipsclass->input['id']  ),
							 4 => array( 'section', $this->ipsclass->section_code ),
						   );
									     				
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_array[] = array( $bit, trim($this->ipsclass->input[ $bit ]) );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( $page_array );
									     		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Change password for member: {$member['members_display_name']}" );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<strong>Enter the new password</strong>" ,
												  			     $this->ipsclass->adskin->form_input('password' ),
									     			    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Create new password salt?</b><div style='color:gray'>If set to 'yes', a new password salt will be generated. Useful if a member is having trouble logging in.</div>" ,
												  				 $this->ipsclass->adskin->form_yes_no( "newsalt", 1 )
									     				)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Create new log in key?</b><div style='color:gray'>If set to 'yes', a new cookie log in key will be generated. Useful if a member is having trouble logging in. Any current cookies will not work.</div>" ,
												  				 $this->ipsclass->adskin->form_yes_no( "newkey", 1 )
									     				)      );
									     									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Change Password");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->nav[] 		= array( '', 'Change Member Password' );
		
		$this->ipsclass->admin->output();
	}
	
	
	
	/*-------------------------------------------------------------------------*/
	//
	// TEMP BANNING
	//
	/*-------------------------------------------------------------------------*/
	
	function member_suspend_start()
	{
		$this->ipsclass->admin->page_title = "Account Suspension";
		$this->ipsclass->admin->page_detail = "Automated temporary member suspension. Simply choose the duration of the suspension and submit the form below";
		$this->ipsclass->admin->nav[] 		= array( '', 'Suspend Member' );
		
		$contents = "{membername},\nYour member account at {$this->ipsclass->vars['board_name']} has been temporarily suspended.\n\nYour account will not be functional until {date_end} (depending on your timezone). This is an automated process and you do not need to do anything to expediate the unsuspension process.\n\nBoard Address: {$this->ipsclass->vars['board_url']}/index.php";
		
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".intval($this->ipsclass->input['mid']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $member = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("We could not match that ID in the members database");
		}
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_array = array( 1 => array( 'code'  , 'dobanmember'  ),
							 2 => array( 'act'   , 'mem'       ),
							 3 => array( 'mid'   , $this->ipsclass->input['mid']  ),
							 4 => array( 'section', $this->ipsclass->section_code ),
						   ) ;
									     				
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_array[] = array( $bit, trim($this->ipsclass->input[ $bit ]) );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( $page_array );
									     		
		$ban = $this->ipsclass->hdl_ban_line( $member['temp_ban'] );
		
		$units = array( 0 => array( 'h', 'Hours' ), 1 => array( 'd', 'Days' ) );
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Member Account Suspension", "Note: If this member is already suspended, any new setting will restart the ban" );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<strong>Suspend {$member['members_display_name']} for...</strong>" ,
												                 $this->ipsclass->adskin->form_input('timespan', $ban['timespan'], "text", "", '5' ) . '&nbsp;' . $this->ipsclass->adskin->form_dropdown('units', $units, $ban['units'] ),
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Email notification to this member?</b><br>(If so, you may edit the email below)" ,
												                 $this->ipsclass->adskin->form_yes_no( "send_email", 0 )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Email contents</b><br>(Tags: {membername} = member's name, {date_end} = ban end)" ,
												                 $this->ipsclass->adskin->form_textarea( "email_contents", $contents )
									                    ), "", 'top'       );
									     									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Suspend This Account");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// SUSPEND COMPLETE
	//
	/*-------------------------------------------------------------------------*/
	
	function member_suspend_complete()
	{
		$this->ipsclass->admin->page_title = "Account Suspension";
		
		$this->ipsclass->admin->page_detail = "Automated temporary member suspension. Confirmation and information";
		
		$this->ipsclass->input['mid'] = intval($this->ipsclass->input['mid']);
		
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".intval($this->ipsclass->input['mid']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $member = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("We could not match that ID in the members database");
		}
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		//-----------------------------------------
		// Work out end date
		//-----------------------------------------
		
		$this->ipsclass->input['timespan'] = intval($this->ipsclass->input['timespan']);
		
		if ( $this->ipsclass->input['timespan'] == "" )
		{
			$new_ban = "";
		}
		else
		{
			$new_ban = $this->ipsclass->hdl_ban_line( array( 'timespan' => intval($this->ipsclass->input['timespan']), 'unit' => $this->ipsclass->input['units']  ) );
		}
		
		$show_ban = $this->ipsclass->hdl_ban_line( $new_ban );
			
		//-----------------------------------------
		// Update and show confirmation
		//-----------------------------------------
			
		$this->ipsclass->DB->do_update( 'members', array( 'temp_ban' => $new_ban ), "id=".$this->ipsclass->input['mid'] );
		
		// I say, did we choose to email 'dis member?
		
		if ($this->ipsclass->input['send_email'] == 1)
		{
			// By golly, we did!
			
			require_once( ROOT_PATH . "sources/classes/class_email.php" );
		
			$this->email           =  new emailer( ROOT_PATH );
			$this->email->ipsclass =& $this->ipsclass;
			$this->email->email_init();
			
			$msg = trim($this->ipsclass->txt_stripslashes($_POST['email_contents']));
			
			$msg = str_replace( "{membername}", $member['members_display_name']       , $msg );
			$msg = str_replace( "{date_end}"  , $this->ipsclass->admin->get_date( $show_ban['date_end'], 'LONG') , $msg );
			
			$this->email->message = $this->email->clean_message($msg);
			$this->email->subject = "Account Suspension Notification";
			$this->email->to      = $member['email'];
			$this->email->send_mail();
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "";
		
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_query .= '&'.$bit.'='.trim($this->ipsclass->input[ $bit ]);
		}
		
		$this->ipsclass->admin->save_log("Suspended Member(s) ( {$member['members_display_name']} )");
		
		$this->ipsclass->admin->done_screen("Suspended Member(s)", "Member Search", "{$this->ipsclass->form_code}".$page_query, "redirect" );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Unsuspend
	//
	/*-------------------------------------------------------------------------*/
	
	function member_unsuspend()
	{
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		if ($this->ipsclass->input['mid'] == 'all')
		{
			$this->ipsclass->DB->do_update( 'members', array( 'temp_ban' => $new_ban ), "" );
			
			$this->ipsclass->admin->save_log("Unsuspended all member accounts");
		
			$msg = "All Accounts Unsuspended";
		}
		else
		{
			$mid = intval($this->ipsclass->input['mid']);
			
			$this->ipsclass->DB->do_update( 'members', array( 'temp_ban' => $new_ban ), "id=$mid" );
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => "id=$mid" ) );
			$this->ipsclass->DB->simple_exec();
			
			$member = $this->ipsclass->DB->fetch_row();
			
			$this->ipsclass->admin->save_log("Unsuspended {$member['members_display_name']}");
		
			$msg = "{$member['members_display_name']} Unsuspended";
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "&members_display_name=".$member['members_display_name'];
		
		$this->ipsclass->admin->done_screen($msg, "Member Search", "{$this->ipsclass->form_code}".$page_query, "redirect" );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// CHANGE MEMBER DISPLAY NAME
	/*-------------------------------------------------------------------------*/
	
	function member_change_display_name_do()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mid          = intval( $this->ipsclass->input['mid'] );
		$display_name = $this->ipsclass->input['new_name'];
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $mid )
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		if ($this->ipsclass->input['new_name'] == "")
		{
			$this->member_change_display_name( "You must enter a new name for this member" );
			exit();
		}
		
		//-----------------------------------------
		// Select
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".$mid ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $member = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("We could not match that ID in the members database");
		}
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $display_name == $member['members_display_name'] )
		{
			$this->member_change_display_name("The new name is the same as the old name, that is illogical captain");
			exit();
		}
		
		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
		}
		
		//-----------------------------------------
		// Check for missing fields / chars.
		//-----------------------------------------
		
		if ( ! $display_name )
		{
			$this->member_change_display_name("You must enter a name to use");
			exit();
		}
		
		$unicode_dname = preg_replace_callback('/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $display_name);
		$unicode_dname = str_replace( "'" , '&#39;', $unicode_dname );
		$unicode_dname = str_replace( "\\", '&#92;', $unicode_dname );

		if ( preg_match( "#[\[\];,\|]#", str_replace('&#39;', "'", str_replace('&amp;', '&', $unicode_dname) ) )  )
		{
			$this->member_change_display_name("The new name contains illegal characters");
			exit();
		}
		
		//-----------------------------------------
		// Are they banned [NAMES]?
		//-----------------------------------------
		
		if ( is_array( $banfilters['name'] ) and count( $banfilters['name'] ) )
		{
			foreach ( $banfilters['name'] as $n )
			{
				if ( $n == "" )
				{
					continue;
				}
				
				$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
				
				if ( preg_match( "/^{$n}$/i", $display_name ) )
				{
					$found_name = 1;
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Check for existing name.
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
    											 'from'   => 'members',
    											 'where'  => "members_l_display_name='{$display_name}' AND id != ".$mid,
    											 'limit'  => array( 0,1 ) ) );
    											 
    	$this->ipsclass->DB->exec_query();
    	
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->DB->get_num_rows() )
 		{
	 		$check = $this->ipsclass->DB->fetch_row();
	 		
	 		if( $check['id'] != $member['id'] )
	 		{
    			$found_name = 1;
			}
    	}
    	
    	//-----------------------------------------
		// Check for existing LOG IN name.
		//-----------------------------------------
    	
    	if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
    	{
    		$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
													 'from'   => 'members',
													 'where'  => "members_l_username='{$display_name}' AND id != ".$mid,
													 'limit'  => array( 0,1 ) ) );
    											 
    		$this->ipsclass->DB->exec_query();
    		
    		if ( $this->ipsclass->DB->get_num_rows() )
    		{
	    		$check = $this->ipsclass->DB->fetch_row();
	    		
	    		if( $check['id'] != $member['id'] )
	    		{
    				$found_name = 1;
				}
			}
    	}
    	
    	//-----------------------------------------
    	// Got a name?
    	//-----------------------------------------
    	
    	if ( $found_name )
    	{
    		$this->member_change_display_name("That name is already taken by another member");
			exit();
    	}
    	
    	//-----------------------------------------
    	// Insert into change log
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->force_data_type = array( 'dname_previous' => 'string',
    												  'dname_current'  => 'string' );
    	
    	$this->ipsclass->DB->do_insert( 'dnames_change', array( 'dname_member_id'  => $member['id'],
    														    'dname_date'       => time(),
    														    'dname_ip_address' => $this->ipsclass->ip_address,
    														    'dname_previous'   => $member['members_display_name'],
    														    'dname_current'    => $display_name ) );
		
		//-----------------------------------------
		// Still here? Change it then
		//-----------------------------------------
		
    	$this->ipsclass->DB->force_data_type = array( 'members_display_name' => 'string', 'members_l_display_name' => 'string' );	
		$this->ipsclass->DB->do_update( 'members'       , array( 'members_display_name' => $display_name, 'members_l_display_name' => strtolower($display_name) ), "id="            .$member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'contact_name' => 'string' );
		$this->ipsclass->DB->do_update( 'contacts'      , array( 'contact_name'         => $display_name ), "contact_id="    .$member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );
		$this->ipsclass->DB->do_update( 'forums'        , array( 'last_poster_name'     => $display_name ), "last_poster_id=".$member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
		$this->ipsclass->DB->do_update( 'sessions'      , array( 'member_name'          => $display_name ), "member_id="     .$member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'starter_name' => 'string' );
		$this->ipsclass->DB->do_update( 'topics'        , array( 'starter_name'         => $display_name ), "starter_id="    .$member['id'] );
		
		$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );
		$this->ipsclass->DB->do_update( 'topics'        , array( 'last_poster_name'     => $display_name ), "last_poster_id=".$member['id'] );
		
		//-----------------------------------------
		// Recache moderators
		//-----------------------------------------
		
		require_once( ROOT_PATH .'sources/action_admin/moderator.php' );
		$admod = new ad_moderator();
		$admod->ipsclass =& $this->ipsclass;
		
		$admod->rebuild_moderator_cache();
		
		//-----------------------------------------
		// Recache announcements
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/announcements.php' );
		$announcements = new announcements();
		$announcements->ipsclass =& $this->ipsclass;
		$announcements->announce_recache();
				
		//-----------------------------------------
		// Recache forums
		//-----------------------------------------
		
		$this->ipsclass->update_forum_cache();
		
		//-----------------------------------------
		// Stats to Update?
		//-----------------------------------------
		
		$this->ipsclass->init_load_cache( array( 'stats' ) );
		$stats = $this->ipsclass->cache['stats'];

		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, members_display_name',
										  'from'   => 'members',
										  'where'  => "mgroup <> '".$this->ipsclass->vars['auth_group']."'",
										  'order'  => "id DESC",
										  'limit'  => array(0,1) ) );
		$this->ipsclass->DB->simple_exec();
			
		$r = $this->ipsclass->DB->fetch_row();
		$stats['last_mem_name'] = $r['members_display_name'];
		$stats['last_mem_id']   = $r['id'];
		
		if ( count($stats) > 0 )
		{
			$this->ipsclass->cache['stats'] =& $stats;
			$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
		}			
		
		//-----------------------------------------
		// LOG
		//-----------------------------------------
				
		$this->ipsclass->admin->save_log("Changed Member's Display Name '{$member['members_display_name']}' to '$display_name'");
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->ipsclass->input['members_display_name'] = urlencode($display_name);
		
		$this->ipsclass->admin->done_screen("Member's Display Name Changed", "Member Search", "{$this->ipsclass->form_code}".$this->_generate_page_string_url(), "redirect" );
	}
	
	
	
	/*-------------------------------------------------------------------------*/
	// Change name (display name)
	/*-------------------------------------------------------------------------*/
	
	function member_change_display_name($message="")
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mid = intval($this->ipsclass->input['mid']);
		
		//-----------------------------------------
		// Page titles
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Change Member Name";
		$this->ipsclass->admin->page_detail = "You may enter a new name for this member.";
		$this->ipsclass->admin->nav[] 		= array( '', 'Edit Member Display Name' );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $mid )
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".$mid ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $member = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("We could not match that ID in the members database");
		}
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		$contents = "{old_name},\nAn administrator has changed your member name on {$this->ipsclass->vars['board_name']}.\n\nYour new name is: {new_name}\n\nPlease remember this as you may need to use this new name when you log in next time.\nBoard Address: {$this->ipsclass->vars['board_url']}/index.php";
		
		//-----------------------------------------
		// FORM
		//-----------------------------------------
		
		$page_array = array( 1 => array( 'code'   , 'change_display_name_do'  ),
							 2 => array( 'act'    , 'mem'       ),
							 3 => array( 'mid'    , $mid  ),
							 4 => array( 'section', $this->ipsclass->section_code ),
						   );
									     				
		$page_array = array_merge( $page_array, $this->_generate_page_string() );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( $page_array );
		
		//-----------------------------------------
		// start form
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Change Member's Display Name" );
		
		if ($message != "")
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Error Message:</b>" ,
																				 "<b><span style='color:red'>$message</span></b>",
																		)      );
		}
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Current Member's Name</b>" ,
																			 $member['members_display_name'],
																	)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>New Member's Display Name</b><div class='desctext'>Illegal characters: [ ] | ; &#036;<br />Max. Chars: {$this->ipsclass->vars['max_user_name_length']}</div>" ,
												                 			$this->ipsclass->adskin->form_input( "new_name", $this->ipsclass->input['new_name'] )
									                    			)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Change this member's display name");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// CHANGE MEMBER NAME
	//
	/*-------------------------------------------------------------------------*/
	
	function member_change_name_complete()
	{
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		$this->ipsclass->input['new_name'] = str_replace( '|', '&#124;', $this->ipsclass->input['new_name'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		if ($this->ipsclass->input['new_name'] == "")
		{
			$this->member_change_name_start("You must enter a new name for this member");
			exit();
		}
		
		if ( strlen( $this->ipsclass->input['new_name'] ) > $this->ipsclass->vars['max_user_name_length'] )
		{
			$this->member_change_name_start("The new name must be shorter than " . $this->ipsclass->vars['max_user_name_length'] . " characters" );
			exit();
		}
		
		//-----------------------------------------
		// Select
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".intval($this->ipsclass->input['mid']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $member = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("We could not match that ID in the members database");
		}
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		$mid = intval($this->ipsclass->input['mid']); // Save me poor ol' carpels
		
		if ($this->ipsclass->input['new_name'] == $member['name'])
		{
			$this->member_change_name_start("The new name is the same as the old name, that is illogical captain");
			exit();
		}
		
		//-----------------------------------------
		// Check to ensure that his member name hasn't already been taken.
		//-----------------------------------------
		
		$new_name = trim($this->ipsclass->input['new_name']);
		
		$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => strtolower($new_name) ) );
		$this->ipsclass->DB->cache_exec_query();
	
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$check = $this->ipsclass->DB->fetch_row();
			
			if( $check['id'] != $member['id'] )
			{
				$this->member_change_name_start("The name '$new_name' already exists, please choose another");
				exit();
			}
		}
		
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
														 'from'   => 'members',
														 'where'  => "members_l_display_name='".strtolower($new_name)."' AND id<>{$member['id']}",
														 'limit'  => array( 0,1 ) ) );
													 
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->member_change_name_start("The name '$new_name' is already being used as someone's display name, please choose another");
					exit();
				}
			}
		}		
		
		//-----------------------------------------
		// If one gets here, one can assume that the new name is correct for one, er...one.
		// So, lets do the converteroo
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'name' => 'string', 'members_l_username' => 'string' );
		
		$this->ipsclass->DB->do_update( 'members'       , array( 'name' => $new_name, 'members_l_username' => strtolower( $new_name ) ), 'id=' . $mid );
		
		$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
		$this->ipsclass->DB->do_update( 'moderators'      , array( 'member_name'     => $new_name ), "member_id="    .$mid );
			
		if ( ! $this->ipsclass->vars['auth_allow_dnames'] )
		{
			//-----------------------------------------
			// Not using sep. display names?
			//-----------------------------------------
			
			$this->ipsclass->DB->force_data_type = array( 'members_display_name' => 'string', 'members_l_display_name' => 'string' );
			
			$this->ipsclass->DB->do_update( 'members' , array( 'members_display_name' => $new_name, 'members_l_display_name' => strtolower( $new_name ) ), 'id=' . $mid );
			
			# Not using Display names? Then update....
			
			$this->ipsclass->DB->force_data_type = array( 'contact_name' => 'string' );
			$this->ipsclass->DB->do_update( 'contacts'      , array( 'contact_name'     => $new_name ), "contact_id="    .$mid );
			
			$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );
			$this->ipsclass->DB->do_update( 'forums'        , array( 'last_poster_name' => $new_name ), "last_poster_id=".$mid );
			
			$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
			$this->ipsclass->DB->do_update( 'sessions'      , array( 'member_name'      => $new_name ), "member_id="     .$mid );
			
			$this->ipsclass->DB->force_data_type = array( 'starter_name' => 'string' );
			$this->ipsclass->DB->do_update( 'topics'        , array( 'starter_name'     => $new_name ), "starter_id="    .$mid );
			
			$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string' );
			$this->ipsclass->DB->do_update( 'topics'        , array( 'last_poster_name' => $new_name ), "last_poster_id=".$mid );
			
			//-----------------------------------------
			// Recache moderators
			//-----------------------------------------
			
			require_once( ROOT_PATH .'sources/action_admin/moderator.php' );
			$admod = new ad_moderator();
			$admod->ipsclass =& $this->ipsclass;
			
			$admod->rebuild_moderator_cache();
			
			//-----------------------------------------
			// Recache announcements
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/action_public/announcements.php' );
			$announcements = new announcements();
			$announcements->ipsclass =& $this->ipsclass;
			$announcements->announce_recache();
		}
		
		//-----------------------------------------
		// Stats to Update?
		//-----------------------------------------
		
		$this->ipsclass->init_load_cache( array( 'stats' ) );
		
		$stats = $this->ipsclass->cache['stats'];
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, members_display_name',
										  'from'   => 'members',
										  'where'  => "mgroup <> '".$this->ipsclass->vars['auth_group']."'",
										  'order'  => "id DESC",
										  'limit'  => array(0,1) ) );
		$this->ipsclass->DB->simple_exec();
			
		$r = $this->ipsclass->DB->fetch_row();
		$stats['last_mem_name'] = $r['members_display_name'];
		$stats['last_mem_id']   = $r['id'];
		
		if ( count($stats) > 0 )
		{
			$this->ipsclass->cache['stats'] =& $stats;
			$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
		}			
		
		//-----------------------------------------
		// I say, did we choose to email 'dis member?
		//-----------------------------------------
		
		if ($this->ipsclass->input['send_email'] == 1)
		{
			//-----------------------------------------
			// By golly, we did!
			//-----------------------------------------
			
			require_once( ROOT_PATH."sources/classes/class_email.php" );
		
			$this->email           = new emailer( ROOT_PATH );
			$this->email->ipsclass =& $this->ipsclass;
			$this->email->email_init();
			
			$msg = trim($_POST['email_contents']);
			
			$msg = str_replace( "{old_name}", $member['name'], $msg );
			$msg = str_replace( "{new_name}", $new_name      , $msg );
			
			$this->email->message = stripslashes($this->email->clean_message($msg));
			$this->email->subject = "Member Name Change Notification";
			$this->email->to      = $member['email'];
			$this->email->send_mail();
		}
		
		$this->ipsclass->admin->save_log("Changed Member Name '{$member['name']}' to '$new_name'");
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			$this->modules->on_name_change($mid, $new_name );
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "";
		
		$this->ipsclass->input['name'] = urlencode($new_name);
		
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_query .= '&'.$bit.'='.trim($this->ipsclass->input[ $bit ]);
		}
		
		$this->ipsclass->admin->done_screen("Member's Name Changed", "Member Search", "{$this->ipsclass->form_code}".$page_query, "redirect" );
	}
	
	
	
	/*-------------------------------------------------------------------------*/
	//
	// Change name complete
	//
	/*-------------------------------------------------------------------------*/
	
	function member_change_name_start($message="")
	{
		$this->ipsclass->admin->page_title = "Change Member Name";
		$this->ipsclass->admin->page_detail = "You may enter a new name for this member.";
		$this->ipsclass->admin->nav[] 		= array( '', 'Edit Member Name' );
		
		//-----------------------------------------
		// check
		//-----------------------------------------
		
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("You must specify a valid member id, please go back and try again");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".intval($this->ipsclass->input['mid']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $member = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("We could not match that ID in the members database");
		}
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
		}
		
		$contents = "{old_name},\nAn administrator has changed your member name on {$this->ipsclass->vars['board_name']}.\n\nYour new name is: {new_name}\n\nPlease remember this as you may need to use this new name when you log in next time.\nBoard Address: {$this->ipsclass->vars['board_url']}/index.php";
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_array = array( 1 => array( 'code'  , 'dochangename'  ),
							 2 => array( 'act'   , 'mem'       ),
							 3 => array( 'mid'   , $this->ipsclass->input['mid']  ),
							 4 => array( 'section', $this->ipsclass->section_code ),
						   );
									     				
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_array[] = array( $bit, trim($this->ipsclass->input[ $bit ]) );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( $page_array );
		
		//-----------------------------------------
		// start form
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Change Member Name" );
		
		if ($message != "")
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Error Message:</b>" ,
												                 	  "<b><span style='color:red'>$message</span></b>",
									                    	 )      );
		}
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Current Member's Name</b>" ,
												                 $member['name'],
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>New Members Name</b>" ,
												                 $this->ipsclass->adskin->form_input( "new_name", $this->ipsclass->input['new_name'] )
									                    )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Email notification to this member?</b><br>(If so, you may edit the email below)" ,
												                 $this->ipsclass->adskin->form_yes_no( "send_email", 1 )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Email contents</b><br>(Tags: {old_name} = current name, {new_name} = new name)" ,
												                 $this->ipsclass->adskin->form_textarea( "email_contents", $contents )
									                    )      );
									     									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Change this members name");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	


	//-----------------------------------------
	//
	// MEMBER RANKS...
	//
	//-----------------------------------------
	
	function titles_recache()
	{
		$this->ipsclass->cache['ranks'] = array();
        	
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, title, pips, posts',
													  'from'   => 'titles',
													  'order'  => "posts DESC",
											)      );
							
		$this->ipsclass->DB->simple_exec();
					
		while ($i = $this->ipsclass->DB->fetch_row())
		{
			$this->ipsclass->cache['ranks'][ $i['id'] ] = array(
																 'TITLE' => $i['title'],
																 'PIPS'  => $i['pips'],
																 'POSTS' => $i['posts'],
															   );
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'ranks', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	
	
	function titles_start()
	{
		$this->ipsclass->admin->page_title = "Member Ranking Set Up";
		$this->ipsclass->admin->page_detail = "This section allows you to modify, delete or add extra ranks.<br>If you wish to display pips below the members name, enter the number of pips. If you wish to use a custom image, simply enter the image name in the pips box. Note, these custom images must reside in the 'style_images/{img_dir}/folder_team_icons' directory of your installation";
		$this->ipsclass->admin->nav[] = array( '', 'Member Rank Set Up' );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Title"      , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Min Posts"  , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Pips"       , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"     , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"     , "20%" );
		
		//-----------------------------------------
		// Parse macro
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_default=1" ) );
		$this->ipsclass->DB->simple_exec();
		
		$mid = $this->ipsclass->DB->fetch_row();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'macro_replace', 'from' => 'skin_macro', 'where' => "macro_set=1 AND macro_value='A_STAR'" ) );
		$this->ipsclass->DB->simple_exec();
    	           
    	$row = $this->ipsclass->DB->fetch_row();

    	$row['A_STAR'] = str_replace( "<#IMG_DIR#>", $mid['set_image_dir'], $row['macro_replace'] );
    	$row['A_STAR'] = preg_replace( "#style_images#", $this->ipsclass->vars['board_url'].'/style_images', $row['A_STAR'] );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Member Titles/Ranks" );
		
		//-----------------------------------------
		// Lets get on with it...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'titles', 'order' => "posts" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$img = "";
			
			if ( preg_match( "/^\d+$/", $r['pips'] ) )
			{
				for ($i = 1; $i <= $r['pips']; $i++)
				{
					$img .= $row['A_STAR'];
					
				}
			}
			else
			{
				$img = "<img src='{$this->ipsclass->vars['board_url']}/style_images/{$mid['set_image_dir']}/folder_team_icons/{$r['pips']}' border='0'>";
			}
				
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>".$r['title']."</b>" ,
																	 $r['posts'],
																	 $img,
																	 "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=rank_edit&id={$r['id']}'>Edit</a>",
																	 "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=rank_delete&id={$r['id']}'>Delete</a>",
															)      );
		}
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'do_add_rank'  ),
												 				 2 => array( 'act'   , 'mem'       ),
												 				 4 => array( 'section', $this->ipsclass->section_code ),
									   				    )      );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Add a Member Rank" );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rank Title</b>" ,
												  $this->ipsclass->adskin->form_input( "title" )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Minimum number of posts needed</b>" ,
												  $this->ipsclass->adskin->form_input( "posts" )
									     )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of pips</b><div class='graytext'>Or pip image - image must be uploaded into 'style_images/{img_dir}/folder_team_icons'</div>" ,
												  $this->ipsclass->adskin->form_input( "pips" )
									     )      );
									     									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Add this rank");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	
	function titles_add_rank()
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		foreach( array( 'posts', 'title', 'pips' ) as $field )
		{
			if ($this->ipsclass->input[ $field ] == "")
			{
				$this->ipsclass->admin->error("You must complete the form fully");
			}
		}
		
		//-----------------------------------------
		// Add it to the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'titles', array(
										 'posts'  => trim($this->ipsclass->input['posts']),
										 'title'  => trim($this->ipsclass->input['title']),
										 'pips'   => trim($this->ipsclass->input['pips']),
							  )       );
		
		$this->titles_recache();
												  
		$this->ipsclass->admin->done_screen("Rank Added", "Member Ranking Control", "{$this->ipsclass->form_code}&code=title", 'redirect' );					
		
		
	}
	
	//-----------------------------------------
	
	function titles_delete_rank()
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("We could not match that ID");
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'titles', 'where' => "id=".intval($this->ipsclass->input['id']) ) );
		
		$this->titles_recache();
		
		$this->ipsclass->admin->save_log("Removed Rank Setting");
		
		$this->ipsclass->admin->done_screen("Rank Removed", "Member Ranking Control", "{$this->ipsclass->form_code}&code=title", 'redirect' );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit rank images / pips
	/*-------------------------------------------------------------------------*/
	
	function titles_edit_rank()
	{
		//-----------------------------------------
		// check for input
		//-----------------------------------------
		
		if ( $this->ipsclass->input['id'] == "" )
		{
			$this->ipsclass->admin->error("We could not match that ID");
		}
		
		//-----------------------------------------
		// Maximum number of pips...
		//-----------------------------------------
		
		if ( intval( $this->ipsclass->input['pips'] ) == $this->ipsclass->input['pips'] )
		{
			if ( $this->ipsclass->input['pips'] > 100 )
			{
				$this->ipsclass->admin->error( "The maximum number of pips you can have is 100." );
			}
		}
		
		//-----------------------------------------
		// Check pips and images
		//-----------------------------------------
		
		foreach( array( 'posts', 'title', 'pips' ) as $field )
		{
			if ( $this->ipsclass->input[ $field ] == "" )
			{
				$this->ipsclass->admin->error( "You must complete the form fully" );
			}
		}
		
		//-----------------------------------------
		// Add it to the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'titles', array ( 'posts'  => trim($this->ipsclass->input['posts']),
														  'title'  => trim($this->ipsclass->input['title']),
														  'pips'   => trim($this->ipsclass->input['pips']),
												        ) , "id=" . intval( $this->ipsclass->input['id'] )  );
								
		$this->titles_recache();
												  
		$this->ipsclass->admin->save_log("Edited Rank Setting");
		
		$this->ipsclass->admin->done_screen("Rank Edited", "Member Ranking Control", "{$this->ipsclass->form_code}&code=title", 'redirect' );					
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit rank titles
	/*-------------------------------------------------------------------------*/
	
	function titles_rank_setup($mode='edit')
	{
		$this->ipsclass->admin->page_title = "Member Rank Set Up";
		$this->ipsclass->admin->page_detail = "If you wish to display pips below the members name, enter the number of pips. If you wish to use a custom image, simply enter the image name in the pips box. Note, these custom images must reside in the 'style_images/{img_dir}/folder_team_icons' directory of your installation";
		$this->ipsclass->admin->nav[] 		= array( '', 'Member Rank Set Up' );
		
		if ( $mode == 'edit' )
		{
			$form_code = 'do_rank_edit';
			
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("No rank ID was set, please try again");
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'titles', 'where' => "id=".intval($this->ipsclass->input['id']) ) );
			$this->ipsclass->DB->simple_exec();
		
			$rank = $this->ipsclass->DB->fetch_row();
			
			$button = "Complete Edit";
		}
		else
		{
			$form_code = 'do_add_rank';
			$rank = array( 'posts' => "", 'title' => "", 'pips' => "");
			$button = "Add this rank";
		}
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $form_code  ),
																 			 2 => array( 'act'   , 'mem'       ),
																			 3 => array( 'id'    , $rank['id'] ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Member Ranks" );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rank Title</b>" ,
												  							 $this->ipsclass->adskin->form_input( "title", $rank['title'] )
									     							)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Minimum number of posts needed</b>" ,
												  							 $this->ipsclass->adskin->form_input( "posts", $rank['posts'] )
									     							)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of pips (Max: 100)</b><br>(Or pip image)" ,
												  							 $this->ipsclass->adskin->form_input( "pips", $rank['pips'] )
									     							)      );
									     									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($button);
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	
	function member_prune_confirm($ids=array(), $query)
	{
		$this->ipsclass->admin->nav[] = array( '', 'Prune Member(s)' );
		
		//-----------------------------------------
		// Got members?
		//-----------------------------------------
		
		if ( count($ids) < 101)
		{
			foreach( $ids as $n )
			{
				$member_arr[] = "<a href='{$this->ipsclass->vars['board_url']}/index.php?showuser={$n[0]}' target='_blank'>{$n[1]}</a>";
			}
		}
		
		$this->ipsclass->admin->page_title = "Member Pruning";
		
		$this->ipsclass->admin->page_detail = "Please confirm your action.";
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'doprune' ),
												  				 2 => array( 'act'   , 'mem'     ),
												  				 3 => array( 'query' , str_replace( "'", '&#39;', urlencode($query) ) ),
												  				 4 => array( 'section', $this->ipsclass->section_code ),
									     				 )      );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Member Prune Confirmation" );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of members to prune</b>" ,
												  				 count($ids)
									     				)      );
									     
		if ( count($member_arr) > 0 )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Members to prune</b>" ,
												    		  implode( '<br />', $member_arr )
											                )      );
		}
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Complete Member Pruning");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
		
	}
	
	
	//-----------------------------------------
	//
	// COMPLETE PRUNE
	//
	//-----------------------------------------
	
	function member_doprune()
	{
		//-----------------------------------------
		// Make sure we have *something*
		//-----------------------------------------
		
		$query = trim(urldecode($this->ipsclass->txt_stripslashes($_POST['query'])));
		
		$query = str_replace( "&lt;" , "<", $query );
		$query = str_replace( "&gt;" , ">", $query );
		$query = str_replace( '&#39;', "'", $query );
		
		if ($query == "")
		{
			$this->ipsclass->admin->error("Prune query error, no query to use");
		}
		
		//-----------------------------------------
		// Get the member ids...
		//-----------------------------------------
		
		$ids 	= array();
		$names	= array();
		
		$this->ipsclass->DB->cache_add_query( 'member_search_form_two', array( 'rq' => $query ) );
		$this->ipsclass->DB->cache_exec_query();

		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ($i = $this->ipsclass->DB->fetch_row())
			{
				if ( $i['memid'] )
				{
					$ids[] = $i['memid'];
				}
				else if ( $i['id'] )
				{
					$ids[] = $i['id'];
				}
				
				$names[] = $i['members_display_name'];
			}
		}
		else
		{
			$this->ipsclass->admin->error("Could not find any members that matched the prune criteria");
		}
		
		$this->member_delete_do($ids);
		#@here
		$this->ipsclass->admin->save_log("Deleted Member(s) ( ".implode(",",$names)." )");
		
		$this->ipsclass->admin->done_screen("Member Account(s) Deleted", "Member Control", "{$this->ipsclass->form_code}" );
		
	}
	
	
	
	/*-------------------------------------------------------------------------*/
	//
	// DELETE MEMBER(S)
	//
	/*-------------------------------------------------------------------------*/
	
	function member_delete_do($id)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$tmp_mids 	= array();
		$emails		= array();
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check( $_GET['_admin_auth_key'] );
		
		//-----------------------------------------
		// Sort out thingie
		//-----------------------------------------
		
		if ( is_array( $id ) )
		{
			$id = $this->ipsclass->clean_int_array( $id );
			
			$mids = ' IN ('.implode(",",$id).')';
		}
		else
		{
			$mids = ' = '.intval($id);
		}
		
		//-----------------------------------------
		// Get accounts and check IDS
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, mgroup,email', 'from' => 'members', 'where' => 'id'.$mids ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Non root admin attempting to edit root admin?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				if ( $r['mgroup'] == $this->ipsclass->vars['admin_group'] )
				{
					continue;
				}
			}
			
			$tmp_mids[] = $r['id'];
			
			$emails[]	= $r['email'];
		}
		
		if ( ! count( $tmp_mids ) )
		{
			$this->ipsclass->admin->error("No members to delete");
		}
		
		$mids = ' IN ('.implode(",",$tmp_mids).')';
		
		//-----------------------------------------
		// Get avatars / photo
		//-----------------------------------------
		
		$delete_files = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'member_extra', 'where' => 'id'.$mids ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['photo_type'] == 'upload' and $r['photo_location'] )
			{
				$delete_files[] = $r['photo_location'];
			}
			
			if ( $r['avatar_type'] == 'upload' and $r['avatar_location'] )
			{
				$delete_files[] = $r['avatar_location'];
			}
		}
		
		//-----------------------------------------
		// Convert their posts and topics
		// into guest postings..
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'posts' , array( 'author_id'  => 0 ), "author_id".$mids );
		$this->ipsclass->DB->do_update( 'topics', array( 'starter_id' => 0 ), "starter_id".$mids );
		
		//-----------------------------------------
		// Clean up profile/friends/comments/ratings...
		//-----------------------------------------
				
		$this->ipsclass->DB->do_update( 'profile_comments', array( 'comment_by_member_id' => 0 ), "comment_by_member_id".$mids );
		$this->ipsclass->DB->do_update( 'profile_ratings', array( 'rating_by_member_id' => 0 ), "rating_by_member_id".$mids );
		
		$this->ipsclass->DB->do_delete( 'profile_comments', "comment_for_member_id".$mids );
		$this->ipsclass->DB->do_delete( 'profile_ratings', "rating_for_member_id".$mids );
		
		$this->ipsclass->DB->do_delete( 'profile_portal', "pp_member_id".$mids );
		$this->ipsclass->DB->do_delete( 'profile_friends', "friends_member_id".$mids );
		$this->ipsclass->DB->do_delete( 'profile_friends', "friends_friend_id".$mids );
		
		//-----------------------------------------
		// Delete member...
		//-----------------------------------------
		
		$this->ipsclass->DB->do_delete( 'pfields_content' , "member_id".$mids );
		$this->ipsclass->DB->do_delete( 'member_extra'    , "id".$mids );
		$this->ipsclass->DB->do_delete( 'members_converge', "converge_email IN('". implode( "','", $emails ) ."')" );
		
		//-----------------------------------------
		// Delete ACP Restrictions...
		//-----------------------------------------		
		
		$this->ipsclass->DB->do_delete( 'admin_permission_rows', 'row_member_id'.$mids );
		
		//-----------------------------------------
		// Delete member messages...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => 'mt_msg_id', 'from' => 'message_topics', 'where' => "mt_owner_id".$mids ) );
		$this->ipsclass->DB->exec_query();
		
		$messages = array();
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$messages[] = $row['mt_msg_id'];
		}
		
		if( count($messages) > 0 )
		{
			$msgids = " IN(".implode( ",", $messages ).")";
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'message_text', 'where' => "msg_id".$msgids ) );
		}
		
		$this->ipsclass->DB->do_delete( 'message_topics', "mt_owner_id".$mids );
		$this->ipsclass->DB->do_delete( 'contacts'      , "member_id".$mids." or contact_id".$mids );
		
		//-----------------------------------------
		// Delete member subscriptions.
		//-----------------------------------------
		
		$this->ipsclass->DB->do_delete( 'tracker'      , "member_id".$mids );
		$this->ipsclass->DB->do_delete( 'forum_tracker', "member_id".$mids );
		$this->ipsclass->DB->do_delete( 'warn_logs'    , "wlog_mid" .$mids );
		
		//-----------------------------------------
		// Delete from validating..
		//-----------------------------------------
		
		$this->ipsclass->DB->do_delete( 'validating', "member_id".$mids );
		$this->ipsclass->DB->do_delete( 'members'   , "id".$mids );
		
		//-----------------------------------------
		// Delete avatars / photos
		//-----------------------------------------
		
		if ( count($delete_files) )
		{
			foreach( $delete_files as $file )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/".$file );
			}
		}
		
		//-----------------------------------------
		// Get current stats...
		//-----------------------------------------
		
		$this->ipsclass->init_load_cache( array( 'stats' ) );
		
		$r = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(id) as members', 'from' => 'members', 'where' => "mgroup <> '".$this->ipsclass->vars['auth_group']."'" ) );

		$this->ipsclass->cache['stats']['mem_count'] = intval($r['members']);		
		
		$r = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'id, members_display_name',
										    'from'   => 'members',
										    'where'  => "mgroup <> ".$this->ipsclass->vars['auth_group'],
										    'order'  => 'id DESC',
										    'limit'  => array( 0, 1 )
								   )      );
		
		$this->ipsclass->cache['stats']['last_mem_name'] = $r['members_display_name'];
		$this->ipsclass->cache['stats']['last_mem_id']   = $r['id'];
		
		if ( count($this->ipsclass->cache['stats']) > 0 )
		{
			$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
		}
			             
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			$this->modules->on_delete($id);
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Delete Members
	//
	/*-------------------------------------------------------------------------*/
	
	function member_delete()
	{
		//-----------------------------------------
		// Check input
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['mid'] )
		{
			$this->ipsclass->main_msg = "No member found";
			$this->search_form();
		}
		
		//-----------------------------------------
		// Single or more?
		//-----------------------------------------
		
		if ( strstr( $this->ipsclass->input['mid'], ',' ) )
		{
			$ids = explode( ',', $this->ipsclass->input['mid'] );
		}
		else
		{
			$ids = array( $this->ipsclass->input['mid'] );
		}
		
		$ids = $this->ipsclass->clean_int_array( $ids );
		
		//-----------------------------------------
		// Get accounts
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, mgroup', 'from' => 'members', 'where' => 'id IN ('.implode(",",$ids).')' ) );
		$this->ipsclass->DB->simple_exec();
		
		$names = array();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Non root admin attempting to edit root admin?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				if ( $r['mgroup'] == $this->ipsclass->vars['admin_group'] )
				{
					continue;
				}
			}
			
			$names[] = $r['name'];
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! count( $names ) )
		{
			$this->ipsclass->main_msg = "No member(s) found";
			$this->search_form();
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		$this->member_delete_do( $ids );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "";
		
		foreach( array('name','email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_query .= '&'.$bit.'='.trim($this->ipsclass->input[ $bit ]);
		}
		
		$this->ipsclass->admin->save_log("Deleted Member(s) ( ".implode(",",$names)." )");
		
		$this->ipsclass->admin->done_screen("Member(s) Deleted", "Member Search", "{$this->ipsclass->form_code}".$page_query, "redirect" );
		
	}
		
	
	//-----------------------------------------
	//
	// ADD MEMBER FORM
	//
	//-----------------------------------------
	
	function member_add_form()
	{
		//-----------------------------------------
		// Page details
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Pre Register a member";
		$this->ipsclass->admin->page_detail = "You may pre-register members using this form.";
		$this->ipsclass->admin->nav[] 		= array( '', 'Add Member' );
		
		//-----------------------------------------
		// Got admin restrictions?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['row_perm_cache'] )
		{
			$this->ipsclass->html .= "<div class='input-warn-content' style='color:black'><strong>Note: You have ACP Permission Restrictions.</strong><br />Any member you create with a group that
									  that has access to the ACP will inherit your permission restrictions automatically.</div><br />";
		}
		
		//-----------------------------------------
		// Groups
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => "g_title" ) );
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// If non-root: Remove root group
		//-----------------------------------------
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $this->ipsclass->vars['admin_group'] == $r['g_id'] )
			{
				if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
				{
					continue;
				}
			}
			
			$mem_group[] = array( $r['g_id'] , $r['g_title'] );
		}
		
		//-----------------------------------------
		// Start table
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		// Custom profile fields stuff
		//-----------------------------------------
		
		$required_output = "";
		$optional_output = "";
		$custom_output   = "";
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$this->ipsclass->init_load_cache( array( 'profilefields' ) );
    	
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	
    	$fields->init_data();
    	$fields->parse_to_register();
    	
    	if ( count( $fields->out_fields ) )
    	{
    		$custom_out = $this->ipsclass->adskin->add_td_basic( "Custom Profile Fields", "left", "tablesubheader", 2 );
    		
			foreach( $fields->out_fields as $id => $data )
			{
				if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
				{
					$form_element =  "<select class='dropdown' name='field_{$id}'>{$data}</select>";
				}
				else if ( $fields->cache_data[ $id ]['pf_type'] == 'area' )
				{
					$form_element = $this->ipsclass->adskin->form_textarea( 'field_'.$id, $data );
				}
				else
				{
					$form_element = $this->ipsclass->adskin->form_input( 'field_'.$id, $data );
				}
				
				$custom_out .= $this->ipsclass->adskin->add_td_row( array( "<b>{$fields->field_names[ $id ]}</b><div class='graytext'>{$fields->field_desc[ $id ]}</div>" , $form_element ) );
			}
		}
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'doadd' ),
																			 2 => array( 'act'   , 'mem'     ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																   )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Member Registration" );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Member Name</b>" ,
																			 $this->ipsclass->adskin->form_input( "name", isset($_POST['name']) ? $this->ipsclass->txt_stripslashes($_POST['name']) : '' )
																	)      );
																	
		if( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Member Display Name</b>" ,
																				 $this->ipsclass->adskin->form_input( "members_display_name", isset($_POST['members_display_name']) ?  $this->ipsclass->txt_stripslashes($_POST['members_display_name']) : '' )
																		)      );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Password</b>" ,
																			  $this->ipsclass->adskin->form_input( "password", isset($_POST['password']) ? $_POST['password'] : '', 'password' )
									     							)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Email Address</b>" ,
												  							$this->ipsclass->adskin->form_input( "email", isset($_POST['email']) ? $_POST['email'] : '' )
									     							)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Member Group</b>" ,
																			  $this->ipsclass->adskin->form_dropdown( "mgroup",
																									$mem_group,
												  													isset($_POST['mgroup']) ? $_POST['mgroup'] : $this->ipsclass->vars['member_group']
												 							 					  )
									   							    )      );
									   							    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>COPPA User?</b>" ,
												  							$this->ipsclass->adskin->form_yes_no( "coppa", isset($_POST['coppa']) ? $_POST['coppa'] : '' ) .
												  							'&nbsp&nbsp;&nbsp;' . $this->ipsclass->adskin->form_checkbox( "sendemail", isset($_POST['sendemail']) ? $_POST['sendemail'] : 1 ) . "Send Confirmation Email?"
									     							)      );
									     
		if ($custom_out != "")
		{
			$this->ipsclass->html .= $custom_out;
		}
									     						     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Register Member");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	//
	// Add member
	//
	//-----------------------------------------
	
	function member_do_add()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$in_username = str_replace( '|', '&#124;' , trim($this->ipsclass->input['name']) );
		$in_username = trim( preg_replace( "/\s{2,}/", " ", $in_username ) );
		$in_password = trim($this->ipsclass->input['password']);
		$in_email    = trim(strtolower($this->ipsclass->input['email']));
		
		$members_display_name = trim($this->ipsclass->input['members_display_name'] );
			
		//-----------------------------------------
		// Check form
		//-----------------------------------------
	
		foreach( array('name', 'password', 'email', 'mgroup') as $field )
		{
			if ( ! $_POST[ $field ] )
			{
				$this->ipsclass->admin->error("You must complete the form fully!");
			}
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['admin_group'] == $this->ipsclass->input['mgroup'] )
		{
			if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
			{
				$this->ipsclass->admin->error("Non root admins cannot create a member in the root admin group");
			}
		}
		
		if( preg_match( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", $in_email) )
		{
			$this->ipsclass->main_msg = "Email address cannot contain these characters: [ ] ; # & ! * ' &quot; &lt; &gt; % ( ) { } ? &#092;";
			$this->member_add_form();
			return;
		}
		else
		{
			$in_email = $this->ipsclass->clean_email($in_email);
			
			if( !$in_email OR strlen($in_email) < 6 )
			{
				$this->ipsclass->main_msg = "An invalid email address was entered.  Please choose another one.";
				$this->member_add_form();
				return;
			}
		}
		
		if ( $this->ipsclass->vars['strip_space_chr'] )
    	{
    		// use hexdec to convert between '0xAD' and chr
			$in_username          = str_replace( chr(160), ' ', $in_username );
			$in_username          = str_replace( chr(173), ' ', $in_username );
			$in_username          = str_replace( chr(240), ' ', $in_username );
			$members_display_name = str_replace( chr(160), ' ', $members_display_name );
			$members_display_name = str_replace( chr(173), ' ', $members_display_name );
			$members_display_name = str_replace( chr(240), ' ', $members_display_name );
		}
		
		if ( preg_match( "#[\[\];,\|]#", str_replace('&#39;', "'", str_replace('&amp;', '&', $members_display_name) ) ) )
		{
			$this->ipsclass->main_msg = "Display name cannot contain these characters: [ ] ; , |";
			$this->member_add_form();
			return;
		}	
		
		if( $this->ipsclass->vars['username_characters'] )
		{
			$check_against = preg_quote( $this->ipsclass->vars['username_characters'], "/" );
			
			if( !preg_match( "/^[".$check_against."]+$/i", $_POST['name'] ) && $this->ipsclass->vars['ipbli_usertype'] == 'username' )
			{
				$this->ipsclass->main_msg = 'Usernames can only contain these characters:' . ' ' . $this->ipsclass->vars['username_characters'];
				$this->member_add_form();
				return;
			}
			
			if( !preg_match( "/^[".$check_against."]+$/i", $_POST['members_display_name'] ) && $this->ipsclass->vars['auth_allow_dnames'] )
			{
				$this->ipsclass->main_msg = 'Display names can only contain these characters:' . ' ' . $this->ipsclass->vars['username_characters'];
				$this->member_add_form();
				return;
			}
		}
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	$this->han_login->email_exists_check( $email_one );
    	
    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'EMAIL_NOT_IN_USE' )
    	{
			$this->ipsclass->main_msg = "The selected email address is already in use.";
			$this->member_add_form();
			return;
    	}
		
		//-----------------------------------------
		// Do we already have such a member?
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => strtolower($this->ipsclass->input['name']) ) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->main_msg = "We already have a member by that name, please select another";
			$this->member_add_form();
			return;
		}
		
		if ( $members_display_name )
		{
			if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
														 'from'   => 'members',
														 'where'  => "members_l_display_name='".strtolower($this->ipsclass->input['name'])."'",
														 'limit'  => array( 0,1 ) ) );
													 
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->ipsclass->main_msg = "We already have a member using this user name as their display name, please select another";
					$this->member_add_form();
					return;
				}
			}
		}		
		
		if( $members_display_name )
		{
			$this->ipsclass->DB->cache_add_query( 'general_get_by_display_name', array( 'members_l_display_name' => strtolower($members_display_name) ) );
			$this->ipsclass->DB->cache_exec_query();
			
			$name_check = $this->ipsclass->DB->fetch_row();
			
			if ( $name_check['id'] )
			{
				$this->ipsclass->main_msg = "We already have a member using this name as their display name, please select another";
				$this->member_add_form();
				return;
			}
			
			//-----------------------------------------
			// DNAME: Check for existing LOG IN name.
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['auth_dnames_nologinname'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => "members_display_name, id",
														 'from'   => 'members',
														 'where'  => "members_l_username='".strtolower($members_display_name)."'",
														 'limit'  => array( 0,1 ) ) );
													 
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->ipsclass->main_msg = "We already have a member using this name as their display name, please select another";
					$this->member_add_form();
					return;
				}
			}
		}

		//-----------------------------------------
		// Is this email addy taken? CONVERGE THIS??
		//-----------------------------------------
		
		if ( $this->ipsclass->txt_mb_strlen( $in_username ) > 32 )
		{
			$this->ipsclass->main_msg = "The username cannot be longer than 32 characters!";
			$this->member_add_form();
			return;
		}		
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id', 'from' => 'members', 'where' => "email='".$in_email."'" ) );
		$this->ipsclass->DB->simple_exec();
		
		$email_check = $this->ipsclass->DB->fetch_row();
		
		if ($email_check['id'])
		{
			$this->ipsclass->main_msg = "We already have a member with that email address, please choose another email address";
			$this->member_add_form();
			return;
		}
		
		$member = array(
						 'name'                   => $in_username,
						 'members_l_username'     => strtolower( $in_username ),
						 'members_display_name'   => $members_display_name ? $members_display_name : $in_username,
						 'members_l_display_name' => strtolower( $members_display_name ? $members_display_name : $in_username ),
						 'member_login_key'       => $this->ipsclass->converge->generate_auto_log_in_key(),
						 'email'                  => $in_email,
						 'mgroup'                 => intval($this->ipsclass->input['mgroup']),
						 'posts'                  => 0,
						 'joined'                 => time(),
						 'ip_address'             => $this->ipsclass->ip_address,
						 'time_offset'            => $this->ipsclass->vars['time_offset'],
						 'view_sigs'              => 1,
						 'coppa_user'			  => intval($this->ipsclass->input['coppa']),
						 'email_pm'               => 1,
						 'view_img'               => 1,
						 'view_avs'               => 1,
						 'restrict_post'          => 0,
						 'view_pop'               => 1,
						 'msg_total'              => 0,
						 'new_msg'                => 0,
						 'members_editor_choice'  => $this->ipsclass->vars['ips_default_editor'],
						 'language'               => $this->ipsclass->vars['default_language'],
					   );
		
		$salt     = $this->ipsclass->converge->generate_password_salt(5);
		$passhash = $this->ipsclass->converge->generate_compiled_passhash( $salt, md5($in_password) );
					   
		$converge_array = array( 'converge_email'     => $in_email,
						   		 'converge_joined'    => time(),
						   		 'converge_pass_hash' => $passhash,
						   		 'converge_pass_salt' => str_replace( '\\', "\\\\", $salt )
						 );
		
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();

		//-----------------------------------------
		// Add Converge: Member
		//-----------------------------------------

   		$this->han_login->create_account( array(	'email'			=> $in_email,
   													'joined'		=> $member['joined'],
   													'password'		=> $in_password,
   													'ip_address'	=> $member['ip_address']
   										)		);

		if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		{
			$this->ipsclass->main_msg = "The member could not be added ({$this->han_login->return_code}).<br />" . $this->han_login->return_details;
			$this->member_add_form();
			return;
		}
		
		//-----------------------------------------
		// Insert: CONVERGE
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'members_converge', $converge_array );
		
		//-----------------------------------------
		// Get converges auto_increment user_id
		//-----------------------------------------
		
		$member_id    = $this->ipsclass->DB->get_insert_id();
		$member['id'] = $member_id;
		
		//-----------------------------------------
		// Insert: MEMBERS
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'name' => 'string', 'members_l_username' => 'string', 'members_display_name' => 'string', 'members_l_display_name' => 'string' );
		
		$this->ipsclass->DB->do_insert( 'members', $member );
		
		//-----------------------------------------
		// Insert: MEMBER EXTRA
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'member_extra', array( 'id'        => $member_id,
															   'vdirs'     => 'in:Inbox|sent:Sent Items',
															   'interests' => '',
															   'signature' => '' ) );
		
		//-----------------------------------------
		// Insert into the custom profile fields DB
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];

    	$this->ipsclass->init_load_cache( array( 'profilefields' ) );
    	
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$fields->init_data();
    	$fields->parse_to_save(1);
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array();
		
		foreach( $fields->out_fields as $_field => $_data )
		{
			$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
		}
						
		$fields->out_fields['member_id'] = $member['id'];
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'pfields_content', 'where' => 'member_id='.$member['id'] ) );
		
		$this->ipsclass->DB->do_insert( 'pfields_content', $fields->out_fields );
		
		//-----------------------------------------
		// Restriction permissions stuff
		//-----------------------------------------
		
		if ( $this->ipsclass->member['row_perm_cache'] )
		{
			if ( $this->ipsclass->cache['group_cache'][ intval($this->ipsclass->input['mgroup']) ]['g_access_cp'] )
			{
				//-----------------------------------------
				// Copy restrictions...
				//-----------------------------------------
				
				$this->ipsclass->DB->do_insert( 'admin_permission_rows', array( 'row_member_id'  => $member['id'],
																				'row_perm_cache' => $this->ipsclass->member['row_perm_cache'],
																				'row_updated'    => time() ) );
			}
		}
		
		if( $this->ipsclass->input['sendemail'] )
		{
			require ROOT_PATH."sources/classes/class_email.php";
			
			$email = new emailer( ROOT_PATH );
			$email->ipsclass =& $this->ipsclass;
			$email->email_init();
			
			$email->get_template("account_created");
			
			$email->build_message( array(
												'NAME'         => $member['name'],
												'EMAIL'        => $member['email'],
												'PASSWORD'	   => $in_password
											  )
										);
										
			$email->to      = $member['email'];
			
			$email->send_mail();
		}
		
		//-----------------------------------------
		// Stats
		//-----------------------------------------
		
		$this->ipsclass->init_load_cache( array( 'stats' ) );
		
		$this->ipsclass->cache['stats']['last_mem_name'] = $members_display_name ? $members_display_name : $in_username;
		$this->ipsclass->cache['stats']['last_mem_id']   = $member['id'];
		
		$r = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(id) as members', 'from' => 'members', 'where' => "mgroup <> '".$this->ipsclass->vars['auth_group']."'" ) );

		$this->ipsclass->cache['stats']['mem_count'] = intval($r['members']);		
		
		if ( count($this->ipsclass->cache['stats']) > 0 )
		{
			$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
		}
		
		//-----------------------------------------
		// SYNC modules
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			
			$this->modules->on_create_account( $member );
		}		
		
		
		//-----------------------------------------
		// Log and bog?
		//-----------------------------------------
		             
		$this->ipsclass->admin->save_log("Created new member account for '{$this->ipsclass->input['name']}'");
		
		$this->ipsclass->input['searchtype'] = 'normal';
		$this->ipsclass->input['gotcount']   = 1;
		
		$this->search_results();		
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// SEARCH FORM, SEARCH FOR MEMBER
	//
	/*-------------------------------------------------------------------------*/
	
	function search_form()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mem_group = array( 0 => array( 'all', 'Any Group') );
		
		$this->ipsclass->admin->page_title  = "Edit a member";
		$this->ipsclass->admin->page_detail = "Search for a member.";
		
		//-----------------------------------------
		// Saved results?
		//-----------------------------------------
		
		$this->ipsclass->input['gotcount'] 	= isset($this->ipsclass->input['gotcount']) ? intval($this->ipsclass->input['gotcount']) : 0;
		$this->ipsclass->input['fromdel']	= isset($this->ipsclass->input['fromdel'])  ? intval($this->ipsclass->input['fromdel'])  : 0;
		
		if ( ( $this->ipsclass->input['gotcount'] > 1 and $this->ipsclass->input['fromdel'] ) or ( $this->ipsclass->input['gotcount'] and ! $this->ipsclass->input['fromdel'] ) )
		{
			$this->ipsclass->input['searchtype'] = 'normal';
			$this->search_results();
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => "g_title" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$mem_group[] = array( $r['g_id'] , $r['g_title'] );
		}
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'searchresults' ),
																			 2 => array( 'act'   , 'mem'     ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		// Printy poos 
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Member Search" );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Member's Log In User Name</b><div class='graytext'>This can be left blank if you're using more options below</div>",
																			 $this->ipsclass->adskin->form_dropdown( 'namewhere', array( 0 => array( 'begin'   , 'Begins with' ),
																																		 1 => array( 'is'      , 'Is'          ),
																																		 2 => array( 'contains', 'Contains'    ),
																																		 3 => array( 'ends'    , 'Ends with'   )
																																	   ), isset($_POST['namewhere']) ? $_POST['namewhere'] : ''
																											 )
																			 .'&nbsp;'. $this->ipsclass->adskin->form_input( "name", isset($_POST['name']) ? $_POST['name'] : '' )
																	)      );
	
		if ( $this->ipsclass->vars['auth_allow_dnames'] )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b><u>OR</u> Member's Display Name</b><div class='graytext'>This can be left blank if you're using more options below</div>",
																			 $this->ipsclass->adskin->form_dropdown( 'dnamewhere', array( 0 => array( 'begin'   , 'Begins with' ),
																																		 1 => array( 'is'      , 'Is'          ),
																																		 2 => array( 'contains', 'Contains'    ),
																																		 3 => array( 'ends'    , 'Ends with'   )
																																	   ), isset($_POST['dnamewhere']) ? $_POST['dnamewhere'] : ''
																											 )
																			 .'&nbsp;'. $this->ipsclass->adskin->form_input( "members_display_name", isset($_POST['members_display_name']) ? $_POST['members_display_name'] : '' )
																	)      );
		}
								     				
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b><u>OR</u> Member's ID is...</b>" ,
												                 $this->ipsclass->adskin->form_input( "memberid", isset($_POST['mid']) ? $_POST['mid'] : '' )
									                    )      );
									     				
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Type of Search</b>" ,
												                 $this->ipsclass->adskin->form_dropdown( "searchtype", array( 0 => array( 'normal', 'Find Members to Edit or Delete' ),
												                 													    1 => array( 'prune' , 'Find Members to Prune (Mass Delete)' )
												                 													  ), isset($_POST['searchtype']) ? $_POST['searchtype'] : '' )
									                    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "Optional Search Parameters", "left", "tablesubheader" );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Email Address contains...</b>" ,
												                 $this->ipsclass->adskin->form_input( "email", isset($_POST['email']) ? $_POST['email'] : '' )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Member Suspended</b>" ,
												                 $this->ipsclass->adskin->form_dropdown( "suspended", array( 0=>array('0','Either'),1=>array('yes', 'Yes'),2=>array('no', 'No') ), isset($_POST['suspended']) ? $_POST['suspended'] : '' )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>IP Address contains...</b>" ,
												                 $this->ipsclass->adskin->form_input( "ip_address", isset($_POST['ip_address']) ? $_POST['ip_address'] : '' )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>AIM name contains...</b>" ,
												                 $this->ipsclass->adskin->form_input( "aim_name", isset($_POST['aim_name']) ? $_POST['aim_name'] : '' )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>ICQ Number contains...</b>" ,
												                 $this->ipsclass->adskin->form_input( "icq_number", isset($_POST['icq_number']) ? $_POST['icq_number'] : '' )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Yahoo! Identity contains...</b>" ,
												                 $this->ipsclass->adskin->form_input( "yahoo", isset($_POST['yahoo']) ? $_POST['yahoo'] : '' )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Signature contains...</b>" ,
												                 $this->ipsclass->adskin->form_input( "signature", isset($_POST['signature']) ? $_POST['signature'] : '' )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Less than <em>n</em> posts</b>" ,
												                 $this->ipsclass->adskin->form_input( "posts", isset($_POST['posts']) ? $_POST['posts'] : '' )
									                    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Registered Between (MM-DD-YYYY)</b><div class='graytext'>Leave the first box blank to range from the earliest record and leave the last box blank to range to the current time now</div>",
												                 $this->ipsclass->adskin->form_simple_input( "registered_first", isset($_POST['registered_first']) ? $_POST['registered_first'] : '', 10 ). ' to ' .$this->ipsclass->adskin->form_simple_input( "registered_last", isset($_POST['registered_last']) ? $_POST['registered_last'] : '', 10 )
									                    )      );
									                    						     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Last Post Between (MM-DD-YYYY)</b><div class='graytext'>Leave the first box blank to range from the earliest record and leave the last box blank to range to the current time now</div>" ,
												                 $this->ipsclass->adskin->form_simple_input( "last_post_first", isset($_POST['last_post_first']) ? $_POST['last_post_first'] : '', 10 ). ' to ' . $this->ipsclass->adskin->form_simple_input( "last_post_last", isset($_POST['last_post_last']) ? $_POST['last_post_last'] : '', 10 )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Last Active Between (MM-DD-YYYY)</b><div class='graytext'>Leave the first box blank to range from the earliest record and leave the last box blank to range to the current time now</div>" ,
												                 $this->ipsclass->adskin->form_simple_input( "last_activity_first", isset($_POST['last_activity_first']) ? $_POST['last_activity_first'] : '', 10 ). ' to ' . $this->ipsclass->adskin->form_simple_input( "last_activity_last", isset($_POST['last_activity_last']) ? $_POST['last_activity_last'] : '', 10 )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Is in group...</b>" ,
												                 $this->ipsclass->adskin->form_dropdown( "mgroup", $mem_group, isset($_POST['mgroup']) ? $_POST['mgroup'] : '' )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Is in secondary group...</b>" ,
												                 $this->ipsclass->adskin->form_dropdown( "mgroup_others", $mem_group, isset($_POST['mgroup_others']) ? $_POST['mgroup_others'] : '' )
									                    )      );									                    
									                    
		//-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------
		
    	require ( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$this->ipsclass->init_load_cache( array( 'profilefields' ) );
    	
    	$fields->cache_data = $this->ipsclass->cache['profilefields'];
    	$fields->init_data();
    	$fields->parse_to_edit();
    	
    	if ( count( $fields->out_fields ) )
    	{
    		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "Custom Profile Fields", "left", "tablesubheader" );
    		
			foreach( $fields->out_fields as $id => $data )
			{
				if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
				{
					$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>".$fields->field_names[ $id ]."</b>" ,
												                			 "<select class='dropdown' name='cm_field_{$id}'><option value=''>Any...</option>{$data}</select>"
									                    			)      );
				}
				else
				{
					$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>".$fields->field_names[ $id ]."</b>" ,
												                 			 $this->ipsclass->adskin->form_simple_input('cm_field_'.$id, isset($_POST['field_'.$id]) ? $_POST['field_'.$id] : '', 10 )
												                 	)      );
				}
			}
		}
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Find Member");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
		
		
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// SEARCH RESULTS
	//
	/*-------------------------------------------------------------------------*/
	
	function search_results()
	{
		$this->ipsclass->admin->nav[] = array( '', 'Search Results' );
		
		$page_query = "";
		$un_all     = "";
		
		$query = array();
		
		//-----------------------------------------
		// Member extra?
		//-----------------------------------------
		
		$member_extra = array( 'aim_name', 'icq_number', 'yahoo', 'signature' );
		$date_keys    = array( 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last' );
		
		//-----------------------------------------
		// Loopy loo
		//-----------------------------------------
		
		foreach( array('name', 'members_display_name', 'memberid', 'email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','mgroup_others') as $bit )
		{
			$this->ipsclass->input[ $bit ] = isset($this->ipsclass->input[ $bit ]) ? urldecode(trim($this->ipsclass->input[ $bit ])) : '';
			
			$page_query .= '&'.$bit.'='.urlencode($this->ipsclass->input[ $bit ]);
			
			//-----------------------------------------
			// Table?
			//-----------------------------------------
			
			$table_prefix = in_array( $bit, $member_extra ) ? 'me.' : 'm.';
			
			if ( $this->ipsclass->input[ $bit ] )
			{	
				//-----------------------------------------
				// Time / Date
				//-----------------------------------------
				
				if ( in_array( $bit, $date_keys ) )
				{
					list( $month, $day, $year ) = explode( '-', $this->ipsclass->input[ $bit ] );
					
					if ( ! checkdate( $month, $day, $year ) )
					{
						$this->ipsclass->main_msg = "Date out of range (Month: $month, Day: $day, Year: $year). Dates should be in MM-DD-YYYY";
						$this->search_form();
					}
					
					$time_int = mktime( 0, 0 ,0,$month, $day, $year );
					$tmp_bit  = str_replace( '_first'    , '', $bit );
					$tmp_bit  = str_replace( '_last'     , '', $tmp_bit );
					$tmp_bit  = str_replace( 'registered', 'joined', $tmp_bit );
					
					if ( strstr( $bit, '_first' ) )
					{
						$query[] = $table_prefix.$tmp_bit.' > '.$time_int;
					}
					else
					{
						$query[] = $table_prefix.$tmp_bit.' < '.$time_int;
					}
				}
				else if ($bit == 'mgroup')
				{
					if ($this->ipsclass->input['mgroup'] != 'all')
					{
						$query[] = $table_prefix."mgroup=".$this->ipsclass->input['mgroup'];
					}
				}
				else if ($bit == 'mgroup_others')
				{
					if ($this->ipsclass->input['mgroup_others'] != 'all')
					{
						$query[] = $table_prefix."mgroup_others LIKE '%,".$this->ipsclass->input['mgroup_others'].",%' OR " .
									$table_prefix."mgroup_others LIKE '".$this->ipsclass->input['mgroup_others'].",%' OR " .
									$table_prefix."mgroup_others LIKE '%,".$this->ipsclass->input['mgroup_others']."' OR " .
									$table_prefix."mgroup_others='".$this->ipsclass->input['mgroup_others']."'";
					}
				}				
				else if ($bit == 'posts')
				{
					$query[] = $table_prefix."posts <".$this->ipsclass->input[$bit];
				}
				else if ($bit == 'suspended')
				{
					if ( $this->ipsclass->input[$bit] == 'yes' )
					{
						$query[] = $table_prefix."temp_ban > 0";
					}
					else if ( $this->ipsclass->input[$bit] == 'no' )
					{
						$query[] = $table_prefix."temp_ban < 1 or temp_ban='' or temp_ban is null";
					}
				}
				else if ($bit == 'name')
				{
					$start_bit = '%';
					$end_bit   = '%';
					
					$this->ipsclass->input['namewhere'] = isset($this->ipsclass->input['namewhere']) ? $this->ipsclass->input['namewhere'] : '';
					if ( $this->ipsclass->input['namewhere'] == 'begin' )
					{
						$start_bit = '';
					}
					else if ( $this->ipsclass->input['namewhere'] == 'ends' )
					{
						$end_bit   = '';
					}
					else if ( $this->ipsclass->input['namewhere'] == 'is' )
					{
						$end_bit   = '';
						$start_bit = '';
					}
					
					$query[] = $table_prefix."members_l_username LIKE '".$start_bit.strtolower($this->ipsclass->input[$bit]).$end_bit."'";
				}
				else if ($bit == 'members_display_name')
				{
					$start_bit = '%';
					$end_bit   = '%';
					
					$this->ipsclass->input['dnamewhere'] = isset($this->ipsclass->input['dnamewhere']) ? $this->ipsclass->input['dnamewhere'] : '';
					
					if ( $this->ipsclass->input['dnamewhere'] == 'begin' )
					{
						$start_bit = '';
					}
					else if ( $this->ipsclass->input['dnamewhere'] == 'ends' )
					{
						$end_bit   = '';
					}
					else if ( $this->ipsclass->input['dnamewhere'] == 'is' )
					{
						$end_bit   = '';
						$start_bit = '';
					}
					
					$query[] = $table_prefix."members_l_display_name LIKE '".$start_bit.strtolower($this->ipsclass->input[$bit]).$end_bit."'";
				}
				else if ($bit == 'memberid')
				{
					$query[] = $table_prefix."id=".intval($this->ipsclass->input[$bit]);
				}
				else if ($bit == 'email')
				{
					$query[] = $table_prefix.$bit." LIKE '%".strtolower($this->ipsclass->input[$bit])."%'";
				}				
				else
				{
					$query[] = $table_prefix.$bit." LIKE '%".$this->ipsclass->input[$bit]."%'";
				}
			}
		}
		
		//-----------------------------------------
		// Custom fields...
		//-----------------------------------------
		
		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^cm_field_(\d+)$/", $key, $match ) )
 			{
 				if ( $this->ipsclass->input[ $match[0] ] )
 				{
 					$query[]     = 'p.field_'.intval($match[1])." LIKE '%".$this->ipsclass->input[ $match[0] ]."%'";
 					$page_query .= '&cm_field_'.intval($match[1]).'='.urlencode($this->ipsclass->input[ $match[0] ]);
 				}
 			}
 		}
		
		//-----------------------------------------
		// get 'owt?
		//-----------------------------------------
		
		$rq = count($query) ? ' WHERE '.implode( " AND ", $query ) : '';
		
		//-----------------------------------------
		// On with the show
		//-----------------------------------------
		
		$st = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'member_search_form_count', array( 'rq' => $rq ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$count = $this->ipsclass->DB->fetch_row();
		
		if ($count['count'] < 1)
		{
			$this->ipsclass->main_msg = "Your search query did not return any matches from the member database.";
			$this->search_form();
		}
		
		//-----------------------------------------
		// Prune you fookers?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['searchtype'] != 'normal' )
		{
			$ids = array();
			
			$this->ipsclass->DB->cache_add_query( 'member_search_form_two', array( 'rq' => $rq ) );
			$this->ipsclass->DB->cache_exec_query();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$ids[ $r['id'] ] = array( $r['id'], $r['name'] );
			}
			
			$this->member_prune_confirm($ids, $rq );
			exit();
		}
		
		$this->ipsclass->input['namewhere'] = isset($this->ipsclass->input['namewhere']) ? $this->ipsclass->input['namewhere'] : '';
		
		$page_query .= '&searchtype=normal&namewhere='.$this->ipsclass->input['namewhere'].'&gotcount='.$count['count'];
		
		$this->ipsclass->admin->page_title = "Your Member Search Results";
		
		$this->ipsclass->admin->page_detail = "Your search results.";
		
		//-----------------------------------------
		
		$this->ipsclass->input['showsusp'] = isset($this->ipsclass->input['showsusp']) ? $this->ipsclass->input['showsusp'] : '';
		
		$pages = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $count['count'],
														  'PER_PAGE'    => 25,
														  'CUR_ST_VAL'  => $this->ipsclass->input['st'],
														  'L_SINGLE'    => $un_all."Single Page",
														  'L_MULTI'     => $un_all."Multi Page",
														  'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&showsusp={$this->ipsclass->input['showsusp']}&code={$this->ipsclass->input['code']}".$page_query,
														)  );
		
		//-----------------------------------------
		// Run the query
		//-----------------------------------------
		
		$this->ipsclass->html .= "
							<div class='tableborder'>
							 <div class='tableheaderalt'>Member Search Results: {$count['count']} result(s) found</div>
							 <table cellpadding='4' cellspacing='0' border='0' width='100%'>
						   ";
						   
		$per_row  = 2;
		$td_width = 100 / $per_row;
		$count    = 0;
		$people   = "<tr align='center'>\n";
						   
		$this->ipsclass->DB->cache_add_query( 'member_search_form_one', array( 'rq' => $rq, 'st' => $st ) );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$count++;
			
			$r['id'] = $r['memid'];
			
			if ( ! $r['temp_ban'] )
			{
 				$suspend_html = " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=banmember&mid={$r['id']}{$page_query}' title='Suspend Member'>Suspend Member...</a>";
			}
			else
			{
				$s_ban        = $this->ipsclass->hdl_ban_line( $r['temp_ban'] );
				$suspend_html = " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=unsuspend&mid={$r['id']}{$page_query}'>Unsuspend Member (".$this->ipsclass->admin->get_date( $s_ban['date_end'], 'LONG') .$sus_link.")...</a>";
			}
			
			//-----------------------------------------
			// Mini photo?
			//-----------------------------------------
			
			if ( ! $r['pp_thumb_photo'] )
			{
				$r['pp_thumb_photo'] = $this->ipsclass->skin_acp_url.'/images/memsearch_head.gif';
			}
			else
			{
				$r['pp_thumb_photo'] = $this->ipsclass->vars['upload_url'] . '/' . $r['pp_thumb_photo'];
			}
			
			$joined = $this->ipsclass->get_date( $r['joined'], 'JOINED' );
			
			$people .= <<<EOF
						<td width='{$td_width}%' align='left' style='background-color:#F1F1F1;padding:6px;'>
						  <fieldset>
						  	<legend><strong>{$r['name']}</strong></legend>
						  	<div style='border:1px solid #BBB;background-color:#EEE;margin:2px;padding:1px'>
						  	<table cellpadding='4' cellspacing='0' border='0' width='100%'>
						  	<tr>
						  	 <td width='1%' align='center'><img src="{$r['pp_thumb_photo']}" width='25' height='25' /></td>
						  	 <td width='99%'>
						  	  <a style='font-size:12px;font-weight:bold' title='View this members profile' href='{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?showuser={$r['id']}' target='blank'>{$r['members_display_name']}</a>
						  	  &nbsp;<span style='font-size:10px' class='graytext'>({$r['ip_address']})</span>
						  	 </td>
						  	 <td width='1%' align='center'><img id='mid-{$r['id']}' src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
						  	</tr>
						    </table>
						   </div>
						   
						  	<div style='border:1px solid #BBB;background-color:#FFF;margin:2px;padding:1px'>
						  	<table cellpadding='2' cellspacing='0' border='0' width='100%'>
						  	<tr>
						  	 <td width='1%' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/memsearch_email.gif' border='0' /><td>
						  	 <td width='99%'><strong>{$r['email']}</strong></td>
						  	</tr>
						  	<tr>
						  	 <td width='1%' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/memsearch_group.gif' border='0' /><td>
						  	 <td width='99%'><strong>{$this->ipsclass->cache['group_cache'][$r['mgroup']]['g_title']}</strong> <span style='font-size:10px' class='graytext'>({$r['posts']} Posts)</span></td>
						  	</tr>
						  	<tr>
						  	 <td width='1%' align='center'><img src='{$this->ipsclass->skin_acp_url}/images/memsearch_posts.gif' border='0' /><td>
						  	 <td width='99%'><strong>Joined: {$joined}</strong></td>
						  	</tr>
						  	</table>
						  	</div>
						  </fieldset>
						  <script type="text/javascript">
						  menu_build_menu(
						  "mid-{$r['id']}",
EOF;
			if ( ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] ) AND ( $r['mgroup'] == $this->ipsclass->vars['admin_group'] ) )
			{
				$people .= "new Array( 'You are unable to edit a root administrator' ) );</script></td>";
			}
			else
			{
				$people .= <<<EOF
							new Array( img_edit + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=doform&mid={$r['id']}{$page_query}'>Edit Member's Profile...</a>",
						  			  img_edit + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=changename&mid={$r['id']}{$page_query}'>Edit Member's Log In User Name...</a>",
EOF;
				if ( $this->ipsclass->vars['auth_allow_dnames'] )
				{
					$people .= <<<EOF
										 img_edit + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=change_display_name&mid={$r['id']}{$page_query}'>Edit Member's Display Name...</a>",
EOF;
				}
			
				$people .= <<<EOF
						  				 img_password + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=changepassword&id={$r['id']}{$page_query}'>Change/Reset Password...</a>",
						  			     img_action + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=deleteposts&mid={$r['id']}{$page_query}'>Delete ALL Member's Posts/Topics...</a>",
						  				 img_action + "$suspend_html",
						  				 img_delete + " <a href='#' onclick='maincheckdelete(\"{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=member_delete&fromdel=1&_admin_auth_key={$this->ipsclass->_admin_auth_key}&mid={$r['id']}{$page_query}\"); return false;'>Delete Member...</a>"
										) );
							 </script>
							 </td>
EOF;
			}
			
			if ($count == $per_row )
			{
				$people .= "</tr>\n\n<tr align='center'>";
				$count   = 0;
			}
		}
		
		if ( $count > 0 and $count != $per_row )
		{
			for ($i = $count ; $i < $per_row ; ++$i)
			{
				$people .= "<td class='tablerow2'>&nbsp;</td>\n";
			}
			
			$people .= "</tr>";
		}
		
		
		$this->ipsclass->html .= $people;
		
		$this->ipsclass->html .= "</table>
							<div class='tablesubheader' align='right'>{$pages}</div></div>";
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit Form
	/*-------------------------------------------------------------------------*/
	
	function member_do_edit_form()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$mem        = array();
		$form       = array();
		$mem_group  = array();
		$show_fixed = FALSE;
		$units      = array( 0 => array( 'h', 'Hours' ), 1 => array( 'd', 'Days' ) );
		$lang_array = array();
		$perm_masks = array();
		$mod_arr    = array( 'timespan' => '', 'units' => '' );
		$post_arr  	= array( 'timespan' => '', 'units' => '' );
		
		//-----------------------------------------
		// Fix up langs	
		//-----------------------------------------
		
		$this->ipsclass->vars['default_language'] = ( ! $this->ipsclass->vars['default_language'] ) ? 'en' : $this->ipsclass->vars['default_language'];
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve member id");
		}
		
		//-----------------------------------------
		// Get member info
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'member_search_do_edit_form', array( 'mid' => intval($this->ipsclass->input['mid']) ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$mem = $this->ipsclass->DB->fetch_row();
		
		$mem['id'] = $mem['memid'];
		
		//-----------------------------------------
		// Check 
		//-----------------------------------------
		
		if ( ! $mem['id'] )
		{	
			$this->ipsclass->admin->error("Could not resolve member id");
		}
		
		//-----------------------------------------
		// Load profile lib
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/profile.php' );
		$lib_profile 			 =  new profile();
		$lib_profile->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Get all info
		//-----------------------------------------
		
		$mem = $lib_profile->personal_portal_set_information( $mem );
		
		//-----------------------------------------
		// Nav
		//-----------------------------------------
		
		$this->ipsclass->admin->nav[] = array( '', 'Edit Member' );
		
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);

		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $mem['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
			
			if ( $mem['mgroup_others'] )
			{
				$tmp_mgroup_others = explode( ",", $this->ipsclass->clean_perm_string( $mem['mgroup_others'] ) );
				
				if( count( $tmp_mgroup_others ) )
				{
					foreach( $tmp_mgroup_others as $other_mgroup )
					{
						if( $other_mgroup == $this->ipsclass->vars['admin_group'] )
						{
							$this->ipsclass->admin->error("You are not permitted to edit root administrators");
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Get groups (USE CACHE FOR CRAPS SAKE)
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title, g_access_cp', 'from' => 'groups', 'order' => "g_title" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Ensure only root admins can promote to root admin grou...
			// oh screw it, I can't be bothered explaining stuff tonight
			//-----------------------------------------
			
			if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
			{
				if ( $this->ipsclass->vars['admin_group'] == $r['g_id'] )
				{
					continue;
				}
				else if( $r['g_access_cp'] == 1 && ( $r['g_id'] != $this->ipsclass->member['mgroup'] AND !in_array( $r['g_id'], explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) ) ) )
				{
					continue;
				}
			}
			
			$mem_group[] = array( $r['g_id'] , $r['g_title'] );
		}
		
		//-----------------------------------------
		// is this a non root editing a root?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $mem['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$show_fixed = TRUE;
			}
			else if ( $mem['g_access_cp'] )
			{
				$show_fixed = TRUE;
			}
		}
		
		//-----------------------------------------
		// Get langs (USE CACHE DICKHEAD)
		//-----------------------------------------

		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'languages' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $l = $this->ipsclass->DB->fetch_row() )
		{
			$lang_array[] = array( $l['ldir'], $l['lname'] );
		}
 		
 		//-----------------------------------------
 		// Get Skins (CACHE)
 		//-----------------------------------------
 		
 		require ( ROOT_PATH.'sources/classes/class_display.php' );
 		$print = new display();
 		$print->ipsclass =& $this->ipsclass;
 		
 		$this->ipsclass->skin['_setid'] = $mem['skin'];
 		
 		$form['_skin_list'] = $print->_build_skin_list();
 		
		//-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------
		
    	require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];
    	$fields->member_data = $mem;
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$fields->init_data();
    	$fields->parse_to_edit();
    	
    	if ( count( $fields->out_fields ) )
    	{
			foreach( $fields->out_fields as $id => $data )
			{
				if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
				{
					$form_element =  "<select class='dropdown' name='field_{$id}'>{$data}</select>";
				}
				else if ( $fields->cache_data[ $id ]['pf_type'] == 'area' )
				{
					$form_element = $this->ipsclass->adskin->form_textarea( 'field_'.$id, $data );
				}
				else
				{
					$form_element = $this->ipsclass->adskin->form_input( 'field_'.$id, $data );
				}
				
				$form['_custom_fields'] .= $this->ipsclass->adskin->add_td_row( array( "<b>{$fields->field_names[ $id ]}</b><div class='graytext'>{$fields->field_desc[ $id ]}</div>" , $form_element ) );
			}
		}
		else
		{
			$form['_custom_fields'] = $this->ipsclass->adskin->add_td_row( array( array( "<b>No Custom Profile Fields Found", 2 ) ) );
		}
			
		
		//-----------------------------------------
		// Perms masks section
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$perm_masks[] = array( $r['perm_id'], $r['perm_name'] );
		}
		
		//-----------------------------------------
		// Signature
		//-----------------------------------------
		
		if ( ! $this->editor_loaded )
		{
	    	//-----------------------------------------
	        // Load and config the std/rte editors
	        //-----------------------------------------
	        
	        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
	        $this->han_editor           = new han_editor();
	        $this->han_editor->ipsclass =& $this->ipsclass;
	        $this->han_editor->from_acp         = 1;
	  		$this->han_editor->ed_width         = "550px";
	  		$this->han_editor->ed_height        = "200px";
	  		$this->ipsclass->vars['rte_width']  = "500px";
	  		$this->ipsclass->vars['rte_height'] = "200px";
	  		$this->han_editor->init();
	        $this->editor_loaded = 1;
        }

		if ( $this->han_editor->method == 'rte' )
		{
			$mem['signature'] = $this->parser->convert_ipb_html_to_html( $mem['signature'] );
		}
		else
		{
			$this->parser->parse_html    = 0;
			$this->parser->parse_nl2br   = 1;
			$this->parser->parse_smilies = 0;
			$this->parser->parse_bbcode  = $this->ipsclass->vars['sig_allow_ibc'];
			
			$mem['signature'] = $this->parser->pre_edit_parse( $mem['signature'] );
		}
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title = "Edit member: ".$mem['members_display_name']." (ID: ".$mem['id'].")";
		
		$this->ipsclass->admin->page_detail = "You may alter the members settings from here.";
		
		//-----------------------------------------
		// Mod posts bit
		//-----------------------------------------
		
		$form['_mod_tick'] 	= '';
		$form['_mod_extra']	= "";
		$form['_post_tick'] 	= '';
		$form['_post_extra']	= "";
		
		if ( $mem['mod_posts'] == 1 )
		{
			$form['_mod_tick'] = 'checked';
		}
		elseif ($mem['mod_posts'] > 0)
		{
			$mod_arr = $this->ipsclass->hdl_ban_line( $mem['mod_posts'] );
			
			$hours  = ceil( ( $mod_arr['date_end'] - time() ) / 3600 );
				
			if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
			{
				$mod_arr['units']    = 'd';
				$mod_arr['timespan'] = $hours / 24;
			}
			else
			{
				$mod_arr['units']    = 'h';
				$mod_arr['timespan'] = $hours;
			}
			
			$form['_mod_extra'] = "<br /><span style='color:red'>Restriction in progress - remaining time has been recalculated</span>";
		}
		
		//-----------------------------------------
		// Posting restriction
		//-----------------------------------------
		
		if ( $mem['restrict_post'] == 1 )
		{
			$form['_post_tick'] = 'checked';
		}
		else if( $mem['restrict_post'] > 0 )
		{
			$post_arr = $this->ipsclass->hdl_ban_line( $mem['restrict_post'] );
			
			$hours  = ceil( ( $post_arr['date_end'] - time() ) / 3600 );
				
			if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
			{
				$post_arr['units']    = 'd';
				$post_arr['timespan'] = $hours / 24;
			}
			else
			{
				$post_arr['units']    = 'h';
				$post_arr['timespan'] = $hours;
			}
			
			$form['_post_extra'] = "<br /><span style='color:red'>Restriction in progress - remaining time has been recalculated</span>";
		}
		
		//-----------------------------------------
		// Perm masks
		//-----------------------------------------
		
		foreach ($perm_masks as $d)
		{
			$form['_perm_masks_js'] .= " 		perms_$d[0] = '$d[1]';\n";
		}
		
		//-----------------------------------------
		// Form data..
		//-----------------------------------------
		
		# General
		$form['remove_photo']   = $this->ipsclass->adskin->form_checkbox( "remove_photo", 0 );
		$form['remove_avatar']  = $this->ipsclass->adskin->form_checkbox( "remove_avatar", 0 );
		$form['warn_level']     = $this->ipsclass->adskin->form_input( "warn_level", $mem['warn_level'], 'text', '', '5' );
		$form['member_title']   = $this->ipsclass->adskin->form_input( "title"     , $mem['title']);
		$form['email']          = $this->ipsclass->adskin->form_input( "email"     , $mem['email'] );
		$form['pp_gender']      = $this->ipsclass->adskin->form_dropdown( "pp_gender", array( 0 => array('none', 'Not Telling'), 1 => array( 'female', 'Female'), 2 => array( 'male', 'Male' ) ), $mem['pp_gender'] );
		$form['pp_bio_content'] = $this->ipsclass->adskin->form_textarea( "pp_bio_content", str_replace( '<br />', "\n",$mem['pp_bio_content'] ) );
		
		$form['_show_fixed']   = $show_fixed;
		$form['mgroup']        = $this->ipsclass->adskin->form_dropdown( "mgroup", $mem_group, $mem['mgroup'] );
		$form['_mgroup']       = $this->ipsclass->adskin->form_hidden( array( 1 => array( 'mgroup' , $mem['mgroup'] ) ) );
		$form['mgroup_others'] = $this->ipsclass->adskin->form_multiselect( "mgroup_others[]", $mem_group, explode( ",", $mem['mgroup_others'] ), 5 );
		$form['posts']         = $this->ipsclass->adskin->form_input( "posts", $mem['posts'] );
		
		$form['_permid_tick'] = ( $mem['org_perm_id'] ) ? 'checked="checked"' : '';
		$form['permid']     = $this->ipsclass->adskin->form_multiselect( "permid[]", $perm_masks, explode( ",",$mem['org_perm_id'] ), 5, 'onfocus="saveit(this)" onchange="saveit(this)"' );
		
		# Restrictions
		$form['mod_timespan']  = $this->ipsclass->adskin->form_input('mod_timespan', $mod_arr['timespan'], "text", "", '5' );
		$form['mod_units']     = $this->ipsclass->adskin->form_dropdown('mod_units', $units, $mod_arr['units'] );
		$form['post_timespan'] = $this->ipsclass->adskin->form_input('post_timespan', $post_arr['timespan'], "text", "", '5' );
		$form['post_units']    = $this->ipsclass->adskin->form_dropdown('post_units', $units, $post_arr['units'] );
					     
				
		# Settings
		$form['language']           = $this->ipsclass->adskin->form_dropdown( "language", $lang_array, $mem['language'] != "" ? $mem['language'] : $this->ipsclass->vars['default_language'] );
		$form['hide_email']         = $this->ipsclass->adskin->form_yes_no("hide_email", $mem['hide_email'] );
		$form['email_pm']           = $this->ipsclass->adskin->form_yes_no("email_pm", $mem['email_pm'] );
		$form['members_disable_pm'] = $this->ipsclass->adskin->form_dropdown("members_disable_pm", array( 0 => array( '0' , $this->ipsclass->acp_lang['mem_edit_pm_no'] ),
																			 							  1 => array( '1' , $this->ipsclass->acp_lang['mem_edit_pm_yes'] ),
																										  2 => array( '2' , $this->ipsclass->acp_lang['mem_edit_pm_yes_really'] ),
																										), $mem['members_disable_pm'] );
		
		# Contact Information
		$form['aim_name']   = $this->ipsclass->adskin->form_input( "aim_name"  , $mem['aim_name'] );
		$form['icq_number'] = $this->ipsclass->adskin->form_input( "icq_number", $mem['icq_number'] );
		$form['yahoo']      = $this->ipsclass->adskin->form_input( "yahoo"     , $mem['yahoo'] );
		$form['msnname']    = $this->ipsclass->adskin->form_input( "msnname"   , $mem['msnname'] );
		$form['website']    = $this->ipsclass->adskin->form_input( "website"   , $mem['website'] );
		
		# Signature
		$form['signature'] = $this->han_editor->show_editor( $mem['signature'], 'signature' );
		
		# Profile Information
		$form['avatar']      = $this->ipsclass->adskin->form_input( "avatar", $mem['avatar_location'] );
		$form['avatar_type'] = $this->ipsclass->adskin->form_dropdown("avatar_type", array( 0 => array( 'local'  , 'Avatar Gallery'  ),
																	  						1 => array( 'url'    , 'URL Avatar'      ),
																							2 => array( 'upload' , 'Uploaded Avatar' ),
																						  ), $mem['avatar_type'] );
		$form['avatar_size'] = $this->ipsclass->adskin->form_input( "avatar_size", $mem['avatar_size'] );
		$form['location']    = $this->ipsclass->adskin->form_input( "location", $mem['location'] );
		$form['interests']   = $this->ipsclass->adskin->form_textarea( "interests", str_replace( '<br />', "\n",$mem['interests'] ) );

		$bday_days 	= array( array( 0, 'Not Set' ) );
		$bday_year	= array( array( 0, 'Not Set' ) );
		
		$bday_mons	= array(	array( 0, 'Not Set' ),
								array( 1, 'Jan' ),
								array( 2, 'Feb' ),
								array( 3, 'Mar' ),
								array( 4, 'Apr' ),
								array( 5, 'May' ),
								array( 6, 'Jun' ),
								array( 7, 'Jul' ),
								array( 8, 'Aug' ),
								array( 9, 'Sep' ),
								array( 10, 'Oct' ),
								array( 11, 'Nov' ),
								array( 12, 'Dec' ),
							);
		
		for( $i=1; $i<=31; $i++ )
		{
			$bday_days[] = array( $i, $i );
		}
		
		for( $j=intval(date('Y')),$i=$j-80; $i<=$j; $j-- )
		{
			$bday_year[] = array( $j, $j );
		}
		
		$form['birthday'] = $this->ipsclass->adskin->form_dropdown("bday_month"	, $bday_mons, $mem['bday_month'])	.' '.
							$this->ipsclass->adskin->form_dropdown("bday_day"	, $bday_days, $mem['bday_day'])		.' '.
							$this->ipsclass->adskin->form_dropdown("bday_year"	, $bday_year, $mem['bday_year']);			

		
		//-----------------------------------------
		// Print
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->member_form($mem, $form);
		
		$this->ipsclass->admin->output();
	}


	/*-------------------------------------------------------------------------*/
	// Complete edit
	/*-------------------------------------------------------------------------*/
	
	function member_do_edit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['mid'] = intval($this->ipsclass->input['mid']);
		$remove_avatar				  = intval( $this->ipsclass->input['remove_avatar'] );
		$remove_photo				  = intval( $this->ipsclass->input['remove_photo'] );
		$avatar_type                  = $this->ipsclass->input['avatar_type'];
		$pp_gender					  = ( $this->ipsclass->input['pp_gender'] == 'male' ) ? 'male' : ( $this->ipsclass->input['pp_gender'] == 'female' ? 'female' : '' );
		$pp_bio_content				  = $this->ipsclass->txt_mbsubstr( $this->ipsclass->my_nl2br( $this->ipsclass->input['pp_bio_content'] ), 0, 300 );
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Got an email?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['email'] )
		{
			$this->ipsclass->main_msg = "You must enter a valid email address for this user.";
			$this->member_do_edit_form();
		}
		
		//-----------------------------------------
		// Load parser
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 0;
        
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        $this->ipsclass->load_skin();
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->from_acp = 1;
        
        $this->han_editor->init();
 		
        $this->parser->bypass_badwords = 1;		
        		
		//-----------------------------------------
		// Grab converge...
		//-----------------------------------------

		if ( $this->ipsclass->vars['ipbli_key'] == 'ipconverge' )
		{
			$converge = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		  'from'   => 'converge_local',
																		  'where'  => 'converge_active=1' ) );

			//-----------------------------------------
			// Grab API class...
			//-----------------------------------------

			if ( ! is_object( $this->api_server ) )
			{
				require_once( KERNEL_PATH . 'class_api_server.php' );
				$this->api_server = new class_api_server();
			}
			
			if ( ! $converge['converge_active'] )
			{
				$this->ipsclass->main_msg = "Action failed. Could not locate IP.Converge API details. Recommend that you re-peform a handshake request from the IP.Converge control panel";
				$this->member_do_edit_form();
			}
		}
		
        //-----------------------------------------
        // Get member
        //-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "id=".$this->ipsclass->input['mid'] ) );
		$this->ipsclass->DB->simple_exec();
		
		$memb = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		// Non root admin attempting to edit root admin?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			if ( $memb['mgroup'] == $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("You are not permitted to edit root administrators");
			}
			
			if( $memb['mgroup_others'] )
			{
				$tmp_mgroup_others = explode( ",", $this->ipsclass->clean_perm_string( $memb['mgroup_others'] ) );
				
				if( count( $tmp_mgroup_others ) )
				{
					foreach( $tmp_mgroup_others as $other_mgroup )
					{
						if( $other_mgroup == $this->ipsclass->vars['admin_group'] )
						{
							$this->ipsclass->admin->error("You are not permitted to edit root administrators");
						}
					}
				}
			}			
		}
		
		//-----------------------------------------
		// Convert sig
		//-----------------------------------------

		$signature = $this->han_editor->process_raw_post( 'signature' );
		$this->parser->parse_smilies = 0;
		$this->parser->parse_bbcode  = $this->ipsclass->vars['sig_allow_ibc'];
		
		$signature    					= $this->parser->pre_db_parse( $signature );

		//-----------------------------------------
		// Perms
		//-----------------------------------------
								   
		if ( isset($this->ipsclass->input['override']) AND $this->ipsclass->input['override'] == 1 AND is_array($_POST['permid']) AND count($_POST['permid']) )
		{
			$permid = ','.implode( ",", $_POST['permid'] ).',';
		}
		else
		{
			$permid = "";
		}
		
		$restrict_post = 0;
		$mod_queue     = 0;
		
		//-----------------------------------------
		// Q
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['mod_indef']) AND $this->ipsclass->input['mod_indef'] == 1 )
		{
			$mod_queue = 1;
		}
		elseif ( isset($this->ipsclass->input['mod_timespan']) AND $this->ipsclass->input['mod_timespan'] > 0 )
		{
			$mod_queue = $this->ipsclass->hdl_ban_line( array( 'timespan' => intval($this->ipsclass->input['mod_timespan']), 'unit' => $this->ipsclass->input['mod_units']  ) );
		}
		
		//-----------------------------------------
		// Post ban
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['post_indef']) AND $this->ipsclass->input['post_indef'] == 1 )
		{
			$restrict_post = 1;
		}
		elseif ( isset($this->ipsclass->input['post_timespan']) AND $this->ipsclass->input['post_timespan'] > 0 )
		{
			$restrict_post = $this->ipsclass->hdl_ban_line( array( 'timespan' => intval($this->ipsclass->input['post_timespan']), 'unit' => $this->ipsclass->input['post_units']  ) );
		}
		
		if ( strstr( $this->ipsclass->input['avatar'], 'http://' ) )
		{
			$avatar_type = 'url';
		}

		//-----------------------------------------
		// Throw to the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'members', array (
														  'restrict_post'      => $restrict_post,
														  'mgroup'             => $this->ipsclass->input['mgroup'],
														  'title'              => $this->ipsclass->input['title'],
														  'language'           => $this->ipsclass->input['language'],
														  'skin'               => $this->ipsclass->input['skin'],
														  'hide_email'         => $this->ipsclass->input['hide_email'],
														  'email_pm'           => $this->ipsclass->input['email_pm'],
														  'posts'              => $this->ipsclass->input['posts'],
														  'bday_day'		   => intval($this->ipsclass->input['bday_day']),
														  'bday_month'		   => intval($this->ipsclass->input['bday_month']),
														  'bday_year'		   => intval($this->ipsclass->input['bday_year']),
														  'mod_posts'          => $mod_queue,
														  'org_perm_id'        => $permid,
														  'warn_level'         => intval( $this->ipsclass->input['warn_level'] ),
														  'members_disable_pm' => intval( $this->ipsclass->input['members_disable_pm'] ),
														  'mgroup_others'      => $_POST['mgroup_others'] ? ','.implode( ",", $_POST['mgroup_others'] ).',' : '',
												) , 'id='.$this->ipsclass->input['mid']      );
												  
		$this->ipsclass->DB->do_update( 'member_extra', array (
																'aim_name'        => $this->ipsclass->input['aim_name'],
																'icq_number'      => intval($this->ipsclass->input['icq_number']),
																'yahoo'           => $this->ipsclass->input['yahoo'],
																'msnname'         => $this->ipsclass->input['msnname'],
																'website'         => $this->ipsclass->input['website'],
																'avatar_location' => $this->ipsclass->input['avatar'],
																'avatar_size'     => $this->ipsclass->input['avatar_size'],
																'avatar_type'     => $avatar_type,
																'location'        => $this->ipsclass->input['location'],
																'interests'       => $this->ipsclass->input['interests'],
																'signature'       => $signature,
															 ), 'id='.$this->ipsclass->input['mid'] );
		
		//-----------------------------------------
		// Restriction permissions stuff
		//-----------------------------------------
		
		if ( $this->ipsclass->member['row_perm_cache'] )
		{
			$is_admin = $this->ipsclass->cache['group_cache'][ intval($this->ipsclass->input['mgroup']) ]['g_access_cp'] ? 1 : 0;
			
			if( $is_admin == 0 )
			{
				if( is_array( $_POST['mgroup_others'] ) AND count( $_POST['mgroup_others'] ) )
				{
					foreach( $_POST['mgroup_others'] as $omg )
					{
						if( $this->ipsclass->cache['group_cache'][ intval($omg) ]['g_access_cp'] )
						{
							$is_admin = 1;
							break;
						}
					}
				}
			}

			if ( $is_admin )
			{
				//-----------------------------------------
				// Copy restrictions...
				//-----------------------------------------
				
				$this->ipsclass->DB->do_replace_into( 'admin_permission_rows', array( 'row_member_id'  => $this->ipsclass->input['mid'],
																				'row_perm_cache' => $this->ipsclass->member['row_perm_cache'],
																				'row_updated'    => time() ), "row_member_id={$this->ipsclass->input['mid']}"
													);
			}
		}
				
		//-----------------------------------------
		// Gender...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pp_member_id',
													  'from'   => 'profile_portal',
													  'where'  => "pp_member_id=".$this->ipsclass->input['mid'] ) );
		$this->ipsclass->DB->simple_exec();

		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_gender'      => $pp_gender,
																	 'pp_bio_content' => $pp_bio_content ), 'pp_member_id='.$this->ipsclass->input['mid'] );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_gender'      => $pp_gender,
																	 'pp_bio_content' => $pp_bio_content,
			 														 'pp_member_id'   => $this->ipsclass->input['mid'] ) );
		}
		
		//-----------------------------------------
		// Moved from validating group?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['curgroup'] == $this->ipsclass->vars['auth_group'] )
		{
			if ( $this->ipsclass->input['mgroup'] != $this->ipsclass->input['curgroup'] )
			{
				//-----------------------------------------
				// Yes...
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'validating', 'where' => "member_id={$this->ipsclass->input['mid']} AND new_reg=1" ) );
			}
		}
		
		//-----------------------------------------
		// Diff email?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['email'] != $this->ipsclass->input['curemail'] )
		{
			//-----------------------------------------
			// Is this email addy taken? CONVERGE THIS??
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'members', 'where' => "email='".strtolower($this->ipsclass->input['email'])."' and id <> {$this->ipsclass->input['mid']}" ) );
			$this->ipsclass->DB->simple_exec();
		
			$email_check = $this->ipsclass->DB->fetch_row();
			
			if ($email_check['id'])
			{
				$this->ipsclass->main_msg = "Cannot use this email address, another account is already using it";
				$this->member_do_edit_form();
			}
			
	        //-----------------------------------------
	    	// Load handler...
	    	//-----------------------------------------
	    	
	    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
	    	$this->han_login           =  new han_login();
	    	$this->han_login->ipsclass =& $this->ipsclass;
	    	$this->han_login->init();
	    	$this->han_login->change_email( trim(strtolower($this->ipsclass->input['curemail'])), trim(strtolower($this->ipsclass->input['email'])) );
	    	
	    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
	    	{
				$this->ipsclass->main_msg = "The selected email address is already in use.";
				$this->member_add_form();
				return;
	    	}
	    	
			//-----------------------------------------
			// Update converge...
			//-----------------------------------------
			
			$this->ipsclass->converge->converge_update_member( $this->ipsclass->input['curemail'], strtolower($this->ipsclass->input['email']) );
			
			//-----------------------------------------
			// Update member
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'members', array ( 'email' => strtolower($this->ipsclass->input['email']) ), 'id='.$this->ipsclass->input['mid'] );
			
			//-----------------------------------------
			// Update dupemail
			//-----------------------------------------
			
			if ( $memb['bio'] == 'dupemail' )
			{
				$this->ipsclass->DB->do_update( 'member_extra', array( 'bio' => '' ), 'id='.$this->ipsclass->input['mid'] );
			}
		}
		
		//-----------------------------------------
		// Remove photo?
		//-----------------------------------------
		
		if ( $remove_photo )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'profile_portal', 'where' => "pp_member_id=".intval($this->ipsclass->input['mid']) ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_main_photo'   => '',
													   				     'pp_main_width'   => 0,
													   				     'pp_main_height'  => 0,
																	     'pp_thumb_photo'  => '',
																	     'pp_thumb_width'  => 0,
																	     'pp_thumb_height' => 0 ), 'pp_member_id='.$this->ipsclass->input['mid'] );
			}
			else
			{
				$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_main_photo'   => '',
													   				     'pp_main_width'   => 0,
													   				     'pp_main_height'  => 0,
																	     'pp_thumb_photo'  => '',
																	     'pp_thumb_width'  => 0,
																	     'pp_thumb_height' => 0,
																	     'pp_member_id'    => $this->ipsclass->input['mid'] ) );
			}
			
			foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
			{
				if ( @file_exists( $this->ipsclass->vars['upload_dir']."/photo-".$this->ipsclass->input['mid'].".".$ext ) )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/photo-".$this->ipsclass->input['mid'].".".$ext );
				}
			}
		}
		
		//-----------------------------------------
		// Remove avatar
		//-----------------------------------------
		
		if ( $remove_avatar )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'member_extra', 'where' => "id=".intval($this->ipsclass->input['mid']) ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->DB->do_update( 'member_extra', array( 'avatar_location' => '',
																	   'avatar_size'     => '',
																	   'avatar_type'     => '' ), 'id='.$this->ipsclass->input['mid'] );
			}
			else
			{
				$this->ipsclass->DB->do_insert( 'member_extra', array( 'avatar_location' => '',
																	   'avatar_size'     => '',
																	   'avatar_type'     => '',
																	   'id'              => $this->ipsclass->input['mid'] ) );
			}
			
			foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
			{
				if ( @file_exists( $this->ipsclass->vars['upload_dir']."/av-".$id.".".$ext ) )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/av-".$id.".".$ext );
				}
			}
		}
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		$this->ipsclass->init_load_cache( array('profilefields') );
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];

    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$fields->init_data();
    	$fields->parse_to_save();
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		if ( count( $fields->out_fields ) )
		{
			//-----------------------------------------
			// Do we already have an entry in
			// the content table?
			//-----------------------------------------
			
			$test = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'member_id', 'from' => 'pfields_content', 'where' => 'member_id='.$this->ipsclass->input['mid'] ) );
			
			if ( $test['member_id'] )
			{
				//-----------------------------------------
				// We have it, so simply update
				//-----------------------------------------
				
				$this->ipsclass->DB->force_data_type = array();
				
				foreach( $fields->out_fields as $_field => $_data )
				{
					$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
				}
				
				$this->ipsclass->DB->do_update( 'pfields_content', $fields->out_fields, 'member_id='.$this->ipsclass->input['mid'] );
			}
			else
			{
				$this->ipsclass->DB->force_data_type = array();
				
				foreach( $fields->out_fields as $_field => $_data )
				{
					$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
				}
				
				$fields->out_fields['member_id'] = $this->ipsclass->input['mid'];
				
				$this->ipsclass->DB->do_insert( 'pfields_content', $fields->out_fields );
			}
		}
		
		//-----------------------------------------
		// SYNC modules
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			
			if ( $memb['mgroup'] != $this->ipsclass->input['mgroup'] )
			{
				$this->modules->on_group_change($this->ipsclass->input['mid'], $this->ipsclass->input['mgroup']);
			}
			
			if ( $memb['email'] != strtolower($this->ipsclass->input['email']) )
			{
				$this->modules->on_email_change($this->ipsclass->input['mid'], strtolower($this->ipsclass->input['email']));
			}
			
			if ( $memb['signature'] != $this->ipsclass->input['signature'] )
			{
				$this->modules->on_signature_update($memb, $this->ipsclass->input['signature']);
			}
			
			$mem_array = array(
							    'title'        => $this->ipsclass->input['title'],
								'aim_name'     => $this->ipsclass->input['aim_name'],
								'icq_number'   => $this->ipsclass->input['icq_number'],
								'yahoo'        => $this->ipsclass->input['yahoo'],
								'msnname'      => $this->ipsclass->input['msnname'],
								'website'      => $this->ipsclass->input['website'],
								'location'     => $this->ipsclass->input['location'],
								'interests'    => $this->ipsclass->input['interests'],
								'id'		   => $this->ipsclass->input['mid']
							  );
			
			$this->modules->on_profile_update($mem_array, $custom_fields);
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->ipsclass->admin->save_log("Edited Member '{$memb['members_display_name']}' account");
		
		$this->ipsclass->main_msg = "Member Edited";
		
		$this->ipsclass->admin->redirect_noscreen( $this->ipsclass->base_url . '&section=content&act=mem&code=searchresults&searchtype=normal&memberid='.$this->ipsclass->input['mid'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate page string
	/*-------------------------------------------------------------------------*/
	
	function _generate_page_string()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$page_array = array();
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		foreach( array('name', 'members_display_name', 'email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_array[] = array( $bit, trim($this->ipsclass->input[ $bit ]) );
		}
		
		return $page_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate page string url
	/*-------------------------------------------------------------------------*/
	
	function _generate_page_string_url()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$page_array = "";
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		foreach( array('name', 'members_display_name', 'email','ip_address','aim_name','icq_number','yahoo','signature','posts','suspended', 'registered_first', 'registered_last','last_post_first', 'last_post_last', 'last_activity_first', 'last_activity_last','mgroup','namewhere','gotcount', 'fromdel') as $bit )
		{
			$page_array .= '&'.$bit.'='.trim($this->ipsclass->input[ $bit ]);
		}
		
		return $page_array;
	}
	
	//-----------------------------------------
	// Do banline (internal
	//-----------------------------------------
	
	function _do_banline($raw)
	{
		$ban = trim($this->ipsclass->txt_stripslashes($raw));
		
		$ban = str_replace('|', "&#124;", $ban);
		
		$ban = preg_replace( "/\n/", '|', str_replace( "\n\n", "\n", str_replace( "\r", "\n", $ban ) ) );
		
		$ban = preg_replace( "/\|{1,}\s{1,}?/s", "|", $ban );
		
		$ban = preg_replace( "/^\|/", "", $ban );
		
		$ban = preg_replace( "/\|$/", "", $ban );
		
		$ban = str_replace( "'", '&#39;', $ban );
		
		return $ban;
	}
	

}


?>