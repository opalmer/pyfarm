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
|   > $Date: 2007-10-18 15:44:54 -0400 (Thu, 18 Oct 2007) $
|   > $Revision: 1135 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Topic display module
|   > Module written by Matt Mecham
|   > Date started: 18th February 2002
|
|	> Module Version Number: 1.1.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class topics
{
	# Global
	var $ipsclass;
	
    var $output         = "";
    var $base_url       = "";
    var $html           = "";
    var $moderator      = array();
    var $forum          = array();
    var $topic          = array();
    var $mem_titles     = array();
    var $mod_action     = array();
    var $poll_html      = "";
    var $parser         = "";
    var $mimetypes      = "";
    var $nav_extra      = "";
    var $read_array     = array();
    var $mod_panel_html = "";
    var $warn_range     = 0;
    var $warn_done      = 0;

    var $md5_check      = "";
    var $post_count     = 0;
    var $cached_members = array();
    var $first_printed  = 0;
    var $pids           = array();
    var $attach_pids    = array();
    var $first          = "";
    var $qpids          = "";
    var $custom_fields  = "";
    var $last_read_tid  = "";
    
    # Permissions
    var $can_vote		= 0;
	var $can_rate       = 0;
	var $poll_only		= 0;
	
	# Stop E_ALL moaning like an old fish wife
	var $class_attach = '';
	
    /*-------------------------------------------------------------------------*/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/*-------------------------------------------------------------------------*/
	
    function auto_run()
    {
    	//-----------------------------------------
    	// Build all permissions
    	//-----------------------------------------

    	$this->build_permissions();
    	
    	//-----------------------------------------
    	// Are we just adding a poll vote?
    	//-----------------------------------------
    	
    	if ( isset($this->ipsclass->input['addpoll']) AND $this->ipsclass->input['addpoll'] )
    	{
    		$this->topic_add_vote_to_poll();
    	}
    	
    	//-----------------------------------------
    	// INIT module
    	//-----------------------------------------
    	
        $this->init();
        
        //-----------------------------------------
    	// Are we just adding a rating vote?
    	//-----------------------------------------
    	
    	if ( isset($this->ipsclass->input['addrating']) AND $this->ipsclass->input['addrating'] )
    	{
    		$this->topic_add_vote_to_rating();
    	}
    	
        //-----------------------------------------
		// Process the topic
		//-----------------------------------------
        
        $this->topic_set_up();
        
        //-----------------------------------------
		// Which view are we using?
		// If mode='show' we're viewing poll results, don't change view mode
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['mode']) AND $this->ipsclass->input['mode'] AND $this->ipsclass->input['mode'] != 'show' )
		{
			$this->topic_view_mode = $this->ipsclass->input['mode'];
			$this->ipsclass->my_setcookie( 'topicmode', $this->ipsclass->input['mode'], 1 );
		}
		else
		{
			$this->topic_view_mode = $this->ipsclass->my_getcookie('topicmode');
		}
		
		if ( ! $this->topic_view_mode )
		{
			//-----------------------------------------
			// No cookie and no URL
			//-----------------------------------------
			
			$this->topic_view_mode = $this->ipsclass->vars['topicmode_default'] ? $this->ipsclass->vars['topicmode_default'] : 'linear';
		}
        
        //-----------------------------------------
        // VIEWS
        //-----------------------------------------

        $mode 	= $this->ipsclass->my_getcookie( 'topicmode' );
        
        $pre	= $mode != 'threaded' ? 'st' : 'start';
        
        if( $mode == 'threaded' )
        {
	        $this->ipsclass->vars['display_max_posts'] 	= $this->ipsclass->vars['threaded_per_page'];
	        $this->ipsclass->input['st']				= $this->ipsclass->input['start'];
        }

        if ( isset($this->ipsclass->input['view']) )
        {
        	if ($this->ipsclass->input['view'] == 'new')
        	{
        		//-----------------------------------------
        		// Newer 
        		//-----------------------------------------
        		
        		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid',
															  'from'   => 'topics',
															  'where'  => "forum_id=".$this->forum['id']." AND approved=1 AND state <> 'link' AND last_post > ".$this->topic['last_post'],
															  'order'  => 'last_post',
															  'limit'  => array( 0,1 )
													)      );
									
				$this->ipsclass->DB->simple_exec();
        		
        		if ( $this->ipsclass->DB->get_num_rows() )
        		{
        			$this->topic = $this->ipsclass->DB->fetch_row();
        			
        			$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid']);
        		}
        		else
        		{
        			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_newer') );
        		}
        	}
        	else if ($this->ipsclass->input['view'] == 'old')
        	{
        		//-----------------------------------------
        		// Older
        		//-----------------------------------------
        		
				$this->ipsclass->DB->simple_construct( array( 'select' => 'tid',
															  'from'   => 'topics',
															  'where'  => "forum_id=".$this->forum['id']." AND approved=1 AND state <> 'link' AND last_post < ".$this->topic['last_post'],
															  'order'  => 'last_post DESC',
															  'limit'  => array( 0,1 )
													)      );
									
				$this->ipsclass->DB->simple_exec();
					
				if ( $this->ipsclass->DB->get_num_rows() )
        		{
        			$this->topic = $this->ipsclass->DB->fetch_row();
        			
        			$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid']);
        		}
        		else
        		{
        			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_older') );
        		}
        	}
        	else if ($this->ipsclass->input['view'] == 'getlastpost')
        	{
        		//-----------------------------------------
        		// Last post
        		//-----------------------------------------
        		
        		$this->return_last_post();
			}
			else if ($this->ipsclass->input['view'] == 'getnewpost')
			{
				//-----------------------------------------
				// Newest post
				//-----------------------------------------
				
				$st  = 0;
				$pid = "";
				
				if ( $this->ipsclass->vars['db_topic_read_cutoff'] and $this->ipsclass->member['id'] )
				{
					$last_time = (isset($this->my_topics_read[ $this->topic['tid'] ]) AND intval( $this->my_topics_read[ $this->topic['tid'] ] )) ? intval( $this->my_topics_read[ $this->topic['tid'] ] ) : 
						( (isset($this->db_row['marker_last_cleared']) AND intval($this->db_row['marker_last_cleared']) ) ? intval( $this->db_row['marker_last_cleared'] ) : 
							( (isset( $this->ipsclass->member['members_markers']['board']) AND intval($this->ipsclass->member['members_markers']['board']) ) ? intval($this->ipsclass->member['members_markers']['board']) : 0 ) );
				}
				
				$last_time = $last_time ? $last_time : $this->ipsclass->input['last_visit'];
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 'MIN(pid) as pid',
															  'from'   => 'posts',
															  'where'  => "queued=0 AND topic_id=".$this->topic['tid']." AND post_date > ".intval($last_time),
															  'limit'  => array( 0,1 )
													)      );
						
				$this->ipsclass->DB->simple_exec();
				
				$post = $this->ipsclass->DB->fetch_row();
				
				if ( $post['pid'] )
				{
					$pid = "&amp;#entry".$post['pid'];
					
					$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts',
																  'from'   => 'posts',
																  'where'  => "topic_id=".$this->topic['tid']." AND pid <= ".$post['pid'],
														)      );
										
					$this->ipsclass->DB->simple_exec();
				
					$cposts = $this->ipsclass->DB->fetch_row();
					
					if ( (($cposts['posts']) % $this->ipsclass->vars['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->ipsclass->vars['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->ipsclass->vars['display_max_posts'] );
						$pages = ceil( $number);
					}
					
					$st = ($pages - 1) * $this->ipsclass->vars['display_max_posts'];
					
					if( $this->ipsclass->vars['post_order_sort'] == 'desc' )
					{
						$st = (ceil(($this->topic['posts']/$this->ipsclass->vars['display_max_posts'])) - $pages) * $this->ipsclass->vars['display_max_posts'];
					}						
					
					$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid']."&{$pre}={$st}".$pid);
				}
				else
				{
					$this->return_last_post();
				}
			}
			else if ($this->ipsclass->input['view'] == 'findpost')
			{
				//-----------------------------------------
				// Find a post
				//-----------------------------------------
				
				$pid = intval($this->ipsclass->input['p']);
				
				if ( $pid > 0 )
				{
					$sort_value = $pid;
					$sort_field = ($this->ipsclass->vars['post_order_column'] == 'pid') ? 'pid' : 'post_date';
					
					if($sort_field == 'post_date')
					{
						$date = $this->ipsclass->DB->build_and_exec_query( array( 'select'	=> 'post_date',
																					'from'	=> 'posts',
																					'where'	=> 'pid=' . $pid,
																		) 		);

						$sort_value = $date['post_date'];
					}

					$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts',
																  'from'   => 'posts',
																  'where'  => "topic_id=".$this->topic['tid']." AND {$sort_field} <=" . $sort_value,
														)      );
										
					$this->ipsclass->DB->simple_exec();
					
					$cposts = $this->ipsclass->DB->fetch_row();
					
					if ( (($cposts['posts']) % $this->ipsclass->vars['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->ipsclass->vars['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->ipsclass->vars['display_max_posts'] );
						$pages = ceil($number);
					}
					
					$st = ($pages - 1) * $this->ipsclass->vars['display_max_posts'];
					
					if( $this->ipsclass->vars['post_order_sort'] == 'desc' )
					{
						$st = (ceil(($this->topic['posts']/$this->ipsclass->vars['display_max_posts'])) - $pages) * $this->ipsclass->vars['display_max_posts'];
					}						

					$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid']."&{$pre}={$st}&p={$pid}"."&#entry".$pid);
				}
				else
				{
					$this->return_last_post();
				}
			}
		}
		
		//-----------------------------------------
		// UPDATE TOPIC?
		//-----------------------------------------
		
		if ( !isset($this->ipsclass->input['b']) OR !$this->ipsclass->input['b'] )
		{
			if ( $this->topic['topic_firstpost'] < 1 )
			{
				//--------------------------------------
				// No first topic set - old topic, update
				//--------------------------------------
				
				$this->ipsclass->DB->simple_construct( array (
												'select' => 'pid',
												'from'   => 'posts',
												'where'  => 'topic_id='.$this->topic['tid'].' AND new_topic=1'
									 )       );
									 
				$this->ipsclass->DB->simple_exec();
				
				$post = $this->ipsclass->DB->fetch_row();
				
				if ( ! $post['pid'] )
				{
					//-----------------------------------------
					// Get first post info
					//-----------------------------------------
					
					$this->ipsclass->DB->simple_construct( array( 'select' => 'pid',
																  'from'   => 'posts',
																  'where'  => "topic_id={$this->topic['tid']}",
																  'order'  => 'pid ASC',
																  'limit'  => array(0,1) ) );
					$this->ipsclass->DB->simple_exec();
					
					$first_post  = $this->ipsclass->DB->fetch_row();
					$post['pid'] = $first_post['pid'];
				}
				
				if ( $post['pid'] )
				{
					$this->ipsclass->DB->simple_construct( array (
																  'update' => 'topics',
																  'set'    => 'topic_firstpost='.$post['pid'],
																  'where'  => 'tid='.$this->topic['tid']
										 )       );
										 
					$this->ipsclass->DB->simple_exec();
				}
				
				//--------------------------------------
				// Reload "fixed" topic
				//--------------------------------------
				
				$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid']."&b=1&{$pre}={$this->ipsclass->input['st']}&p={$this->ipsclass->input['p']}"."&#entry".$this->ipsclass->input['p']);
			}
		}
		
		$find_pid = $this->ipsclass->input['pid'] == "" ? $this->ipsclass->input['p'] : $this->ipsclass->input['pid'];
		
		$threaded_pid = $find_pid ? '&amp;pid='.$find_pid : '';
		$linear_pid   = $find_pid ? '&amp;view=findpost&amp;p='.$find_pid : '';
		
		if ( $this->topic_view_mode == 'threaded' )
		{
			$require = 'topic_threaded.php';
			
			$this->topic['to_button_threaded'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_on(  "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=threaded".$threaded_pid, $this->ipsclass->lang['tom_outline'] );
			$this->topic['to_button_standard'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_off( "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=linear".$linear_pid, $this->ipsclass->lang['tom_standard'] );
			$this->topic['to_button_linearpl'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_off( "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=linearplus".$linear_pid, $this->ipsclass->lang['tom_linear'] );
			
		}
		else
		{
			$require = 'topic_linear.php';
			
			$this->topic['to_button_threaded'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_off( "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=threaded".$threaded_pid, $this->ipsclass->lang['tom_outline'] );
			
			if ( $this->topic_view_mode == 'linearplus' )
			{
				$this->topic['to_button_standard'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_off( "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=linear".$linear_pid, $this->ipsclass->lang['tom_standard'] );
				$this->topic['to_button_linearpl'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_on(  "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=linearplus".$linear_pid, $this->ipsclass->lang['tom_linear'] );
			}
			else
			{
				$this->topic['to_button_standard'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_on(  "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=linear".$linear_pid, $this->ipsclass->lang['tom_standard'] );
				$this->topic['to_button_linearpl'] = $this->ipsclass->compiled_templates['skin_topic']->toutline_mode_choice_off( "{$this->ipsclass->base_url}showtopic={$this->topic['tid']}&amp;mode=linearplus".$linear_pid, $this->ipsclass->lang['tom_linear'] );
			}
		}
		
		//-----------------------------------------
		// Remove potential [attachmentid= tag in title
		//-----------------------------------------
		
		$this->topic['title'] = str_replace( '[attachmentid=', '&#91;attachmentid=', $this->topic['title'] );
		
		//-----------------------------------------
		// Load and run lib
		//-----------------------------------------
		
		$_NOW = $this->ipsclass->memory_debug_make_flag();
		
		$this->func = $this->ipsclass->load_class( ROOT_PATH . 'sources/lib/func_'.$require, 'topic_display' );
		
		$this->func->register_class( $this );
		$this->func->display_topic();
		
		unset( $this->cached_members );
		
		$this->ipsclass->memory_debug_add( "TOPICS: Parsed Posts - Completed", $_NOW );
		
		$this->output .= $this->func->output;
		
		//-----------------------------------------
		// ATTACHMENTS!!!
		//-----------------------------------------
		
		if ( $this->topic['topic_hasattach'] )
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
			
			//-----------------------------------------
			// Not got permission to view downloads?
			//-----------------------------------------
			
			if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ]['download_perms']) === FALSE )
			{
				$this->ipsclass->vars['show_img_upload'] = 0;
			}
			
			//-----------------------------------------
			// Continue...
			//-----------------------------------------
			
			$this->class_attach->type  = 'post';
			$this->class_attach->init();
		
			$this->output = $this->class_attach->render_attachments( $this->output, $this->attach_pids );
		}
		
		//-----------------------------------------
		// Do we have a poll?
		//-----------------------------------------
		
		if ( $this->topic['poll_state'] )
		{
			$this->output = str_replace( "<!--{IBF.POLL}-->", $this->parse_poll(), $this->output );
		}
		else
		{
			// Can we start a poll? Is this our topic and is it still open?
			
			if ( $this->topic['state'] != "closed" AND $this->ipsclass->member['id'] AND $this->ipsclass->member['g_post_polls'] AND $this->forum['allow_poll'] )
			{
				if ( 
					 ( ($this->topic['starter_id'] == $this->ipsclass->member['id']) AND ($this->ipsclass->vars['startpoll_cutoff'] > 0) AND ( $this->topic['start_date'] + ($this->ipsclass->vars['startpoll_cutoff'] * 3600) > time() ) )
					 OR ( $this->ipsclass->member['g_is_supmod'] == 1 )
				   )
				{
					$this->output = str_replace( "<!--{IBF.START_NEW_POLL}-->", $this->ipsclass->compiled_templates['skin_topic']->start_poll_link($this->forum['id'], $this->topic['tid']), $this->output );
				}
			}
		}
		
		// Still seeing attachment tags?
		if ( stristr( $this->output, "[attachmentid=" ) )
		{
			$this->output = preg_replace( "#\[attachmentid=(\d+)\]#is", "", $this->output );
		}
		
		//-----------------------------------------
		// Process users active in this forum
		//-----------------------------------------
		
		if ($this->ipsclass->vars['no_au_topic'] != 1)
		{	
			//-----------------------------------------
			// Get the users
			//-----------------------------------------
			
			$cut_off = ($this->ipsclass->vars['au_cutoff'] != "") ? $this->ipsclass->vars['au_cutoff'] * 60 : 900;
			 
			$this->ipsclass->DB->cache_add_query( 'topics_get_active_users', 
								  array( 'tid'   => $this->topic['tid'],
										 'time'  => time() - $cut_off,
								)      );
									 
			$this->ipsclass->DB->simple_exec();
					   
			//-----------------------------------------
			// ACTIVE USERS
			//-----------------------------------------
			
			$ar_time = time();
			$cached = array();
			$guests = array();
			$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => "");
			$rows   = array( $ar_time => array( 'login_type'   => substr($this->ipsclass->member['login_anonymous'],0, 1),
												'running_time' => $ar_time,
												'id'		   => $this->ipsclass->sess->session_id,
												'location'	   => 'st',
												'member_id'    => $this->ipsclass->member['id'],
												'member_name'  => $this->ipsclass->member['members_display_name'],
												'member_group' => $this->ipsclass->member['mgroup'] ) );

			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ($r = $this->ipsclass->DB->fetch_row() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );
			
			//-----------------------------------------
			// Is this a root admin in disguise?
			// Is that kinda like a diamond in the rough?
			//-----------------------------------------
						
			$our_mgroups = array();
			
			if( $this->ipsclass->member['mgroup_others'] )
			{
				$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
			}
			
			$our_mgroups[] = $this->ipsclass->member['mgroup'];				
			
			//-----------------------------------------
			// PRINT...
			//-----------------------------------------
			
			foreach( $rows as $result )
			{
				$result['member_name'] = $this->ipsclass->make_name_formatted( $result['member_name'], $result['member_group'] );
				
				$last_date = $this->ipsclass->get_time( $result['running_time'] );
				
				if ($result['member_id'] == 0)
				{
					if( in_array( $result['id'], $guests ) )
					{
						continue;
					}
					
					$active['guests']++;
					
					$guests[] = $result['id'];
				}
				else
				{
					if (empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;
						
						$p_start = "";
						$p_end   = "";
						$p_title = " title='".sprintf( $this->ipsclass->lang['au_reading'], $last_date )."' ";
						
						if ( strstr( $result['location'], 'post' ) and $result['member_id'] != $this->ipsclass->member['id'] )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
							$p_title = " title='".sprintf( $this->ipsclass->lang['au_posting'], $last_date )."' ";
						}
						
						if ($result['login_type'] == 1)
						{
							if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
							{
								$active['names'] .= "{$p_start}<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}'{$p_title}>{$result['member_name']}</a>*{$p_end}, ";
								$active['anon']++;
							}
							else
							{
								$active['anon']++;
							}
						}
						else
						{
							$active['members']++;
							$active['names'] .= "{$p_start}<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}'{$p_title}>{$result['member_name']}</a>{$p_end}, ";
						}
					}
				}
			}
			
			$active['names'] = preg_replace( "/,\s+$/", "" , $active['names'] );
			
			$this->ipsclass->lang['active_users_title']   = sprintf( $this->ipsclass->lang['active_users_title']  , ($active['members'] + $active['guests'] + $active['anon'] ) );
			$this->ipsclass->lang['active_users_detail']  = sprintf( $this->ipsclass->lang['active_users_detail'] , $active['guests'],$active['anon'] );
			$this->ipsclass->lang['active_users_members'] = sprintf( $this->ipsclass->lang['active_users_members'], $active['members'] );
			
			
			$this->output = str_replace( "<!--IBF.TOPIC_ACTIVE-->", $this->ipsclass->compiled_templates['skin_topic']->topic_active_users($active), $this->output );
		}
	
		//-----------------------------------------
		// Print it
		//-----------------------------------------
		
		if ( $this->ipsclass->member['is_mod'] )
		{
			if( $mod_panel = $this->moderation_panel() )
			{
				$this->output = str_replace( "<!--IBF.MOD_FULL_WRAPPER-->", $this->ipsclass->compiled_templates['skin_topic']->mod_panel_wrapper( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ), $this->ipsclass->return_md5_check() ), $this->output );
				$this->output = str_replace( "<!--IBF.MOD_PANEL-->", $mod_panel, $this->output );
			}
		}
		else
		{
			$this->output = str_replace( "<!--IBF.MOD_PANEL_NO_MOD-->", $this->moderation_panel(), $this->output );
		}
		
		//-----------------------------------------
        // Get the reply, and posting buttons
        //----------------------------------------- 
										 
		$this->topic['REPLY_BUTTON']  = $this->reply_button();		
		
		//-----------------------------------------
		// Enable quick reply box?
		//-----------------------------------------
		
		if (   ( $this->forum['quick_reply'] == 1 )
		   and ( $this->ipsclass->check_perms( $this->forum['reply_perms']) == TRUE )
		   and ( $this->topic['state'] != 'closed' )
		   and ( ! $this->poll_only ) )
		{
			$show = "none";
			
			$sqr = isset($this->ipsclass->member['_cache']['qr_open']) ? $this->ipsclass->member['_cache']['qr_open'] : 0;
			
			if ( $sqr == 1 )
			{
				$show = "show";
			}
		
			$this->output = str_replace( "<!--IBF.QUICK_REPLY_CLOSED-->", $this->ipsclass->compiled_templates['skin_topic']->quick_reply_box_closed(), $this->output );
			$this->output = str_replace( "<!--IBF.QUICK_REPLY_OPEN-->"  , $this->ipsclass->compiled_templates['skin_topic']->quick_reply_box_open($this->topic['forum_id'], $this->topic['tid'], $show, $this->md5_check), $this->output );
		}
			
		$this->topic['id'] = $this->topic['forum_id'];
		
		$this->output = str_replace( "<!--IBF.FORUM_RULES-->", $this->ipsclass->print_forum_rules($this->forum), $this->output );
		
		//-----------------------------------------
		// Topic multi-moderation - yay!
		//-----------------------------------------
		
		$this->output = str_replace( "<!--IBF.MULTIMOD-->", $this->multi_moderation(), $this->output );
		
		// Pass it to our print routine
		
		$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->topic['title'] . ' - ' . $this->ipsclass->vars['board_name'],
												  'JS'       => $this->ipsclass->compiled_templates['skin_global']->get_rte_css(),
												  'NAV'      => $this->nav,
										 )      );
				        
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse post
	/*-------------------------------------------------------------------------*/
	
	function parse_row( $row = array() )
	{
		//-----------------------------------------
		// Memory Debug
		//-----------------------------------------
		
		$_NOW   = $this->ipsclass->memory_debug_make_flag();
		$poster = array();
		
		//-----------------------------------------
		// Cache member
		//-----------------------------------------
		
		if ($row['author_id'] != 0)
		{
			//-----------------------------------------
			// Is it in the hash?
			//-----------------------------------------
			
			if ( isset($this->cached_members[ $row['author_id'] ]) )
			{
				//-----------------------------------------
				// Ok, it's already cached, read from it
				//-----------------------------------------
				
				$poster = $this->cached_members[ $row['author_id'] ];
				$row['name_css'] = 'normalname';
			}
			else
			{
				$row['name_css'] = 'normalname';
				
				$this->ipsclass->topic =& $this->topic;
				$poster = $this->ipsclass->parse_member( $row, 1, 'skin_topic' );
				
				//-----------------------------------------
				// Add it to the cached list
				//-----------------------------------------
				
				$this->cached_members[ $row['author_id'] ] = $poster;
			}
		}
		else
		{
			//-----------------------------------------
			// It's definitely a guest...
			//-----------------------------------------
			
			$poster = $this->ipsclass->set_up_guest( $row['author_name'] );
			$poster['members_display_name'] = $this->ipsclass->vars['guest_name_pre'] . $row['author_name'] . $this->ipsclass->vars['guest_name_suf'];
			$poster['_members_display_name'] = $this->ipsclass->vars['guest_name_pre'] . $row['author_name'] . $this->ipsclass->vars['guest_name_suf'];
			$poster['custom_fields']		= "";
			$poster['warn_text']			= "";
			$poster['warn_minus']			= "";
			$poster['warn_img']				= "";
			$poster['warn_add']				= "";
			$poster['addresscard']			= "";
			$poster['message_icon']			= "";
			$poster['email_icon']			= "";
			$row['name_css']                = 'unreg';
		}
		
		# Memory Debug
		$this->ipsclass->memory_debug_add( "PID: ".$row['pid'] . " - Member Parsed", $_NOW );
		
		//-----------------------------------------
		// Queued
		//-----------------------------------------
		
		if ( $row['queued'] or ($this->topic['topic_firstpost'] == $row['pid'] and $this->topic['approved'] != 1) )
		{
			$row['post_css'] = $this->post_count % 2 ? 'post1shaded' : 'post2shaded';
			$row['altrow']   = 'row4shaded';
		}
		else
		{
			$row['post_css'] = $this->post_count % 2 ? 'post1' : 'post2';
			$row['altrow']   = 'row4';
		}
		
		//-----------------------------------------
		// Edit...
		//-----------------------------------------
		
		$row['edit_by'] = "";
		
		if ( ($row['append_edit'] == 1) and ($row['edit_time'] != "") and ($row['edit_name'] != "") )
		{
			$e_time = $this->ipsclass->get_date( $row['edit_time'] , 'LONG' );
			
			$row['edit_by'] = $this->ipsclass->compiled_templates['skin_topic']->edited_by( sprintf($this->ipsclass->lang['edited_by'], $row['edit_name'], $e_time) );
		}
		
		//-----------------------------------------
		// View image...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['view_img'] )
		{
			//-----------------------------------------
			// unconvert smilies first, or it looks a bit crap.
			//-----------------------------------------
			
			$row['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $row['post'] );
			
			$row['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>) ", $row['post'] );
		}
		
		//-----------------------------------------
		// Highlight...
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['hl']) AND $this->ipsclass->input['hl'] )
		{
			$row['post'] = $this->ipsclass->content_search_highlight( $row['post'], $this->ipsclass->input['hl'] );
		}
		
		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------
		
		$row['mq_start_image'] = $this->ipsclass->compiled_templates['skin_topic']->mq_image_add($row['pid']);
		
		if ( $this->qpids )
		{
			if ( strstr( ','.$this->qpids.',', ','.$row['pid'].',' ) )
			{
				$row['mq_start_image'] = $this->ipsclass->compiled_templates['skin_topic']->mq_image_remove($row['pid']);
			}
		}
		
		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['is_mod'] )
		{
			$row['pid_start_image'] = $this->ipsclass->compiled_templates['skin_topic']->pid_image_unselected($row['pid']);
			
			if ( $this->ipsclass->input['selectedpids'] )
			{
				if ( strstr( ','.$this->ipsclass->input['selectedpids'].',', ','.$row['pid'].',' ) )
				{
					$row['pid_start_image'] = $this->ipsclass->compiled_templates['skin_topic']->pid_image_selected($row['pid']);
				}
				
				$this->ipsclass->input['selectedpidcount'] = count( explode( ",", $this->ipsclass->input['selectedpids'] ) );

			}
		}
		
		//-----------------------------------------
		// Delete button..
		//-----------------------------------------
		
		$row['delete_button'] = $row['pid'] != $this->topic['topic_firstpost'] 
							  ? $this->delete_button($row['pid'], $poster) 
							  : '';
		
		$row['edit_button']   = $this->edit_button($row['pid'], $poster, $row['post_date']);
		
		$row['post_date']     = $this->ipsclass->get_date( $row['post_date'], 'LONG' );
		
		$row['post_icon']     = $row['icon_id']
							  ? $this->ipsclass->compiled_templates['skin_topic']->post_icon( $row['icon_id'] )
							  : '';
		
		$row['ip_address']    = $this->view_ip($row, $poster);
		
		$row['report_link']   = (($this->ipsclass->vars['disable_reportpost'] != 1) and ( $this->ipsclass->member['id'] ))
							  ? $this->ipsclass->compiled_templates['skin_topic']->report_link($row)
							  : '';
		
		//-----------------------------------------
		// Siggie stuff
		//-----------------------------------------
		
		$row['signature'] = "";
		
		if (isset($poster['signature']) AND $poster['signature'] AND  $this->ipsclass->member['view_sigs'])
		{
			if ($row['use_sig'] == 1)
			{
				$row['signature'] = $this->ipsclass->compiled_templates['skin_global']->signature_separator( $poster['signature'] );
			}
		}
		
		//-----------------------------------------
		// Fix up the membername so it links to the members profile
		//-----------------------------------------
		
		if ( $poster['id'] )
		{
			$poster['_members_display_name'] = "<a href='{$this->base_url}showuser={$poster['id']}'>{$poster['members_display_name_short']}</a>";
		}
		
		//-----------------------------------------
		// Post number
		//-----------------------------------------
		
		if ( $this->topic_view_mode == 'linearplus' and $this->topic['topic_firstpost'] == $row['pid'])
		{
			$row['post_count'] = 1;
			
			if ( ! $this->first )
			{
				$this->post_count++;
			}
		}
		else
		{
			$this->post_count++;
		
			$row['post_count'] = intval($this->ipsclass->input['st']) + $this->post_count;
		}
		
		$row['forum_id'] = $this->topic['forum_id'];
		
		//-----------------------------------------
		// Memory Debug
		//-----------------------------------------
	
		$this->ipsclass->memory_debug_add( "PID: ".$row['pid']. " - Completed", $_NOW );
		
		return array( 'row' => $row, 'poster' => $poster );
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse the member info
	/*-------------------------------------------------------------------------*/
	
	function parse_member( $member=array() )
	{
		return $this->ipsclass->parse_member( $member, 'skin_topic' );
	}
	
	/*-------------------------------------------------------------------------*/
	// Render the delete button
	/*-------------------------------------------------------------------------*/
	
	function delete_button($post_id, $poster)
	{
		if ($this->ipsclass->member['id'] == "" or $this->ipsclass->member['id'] == 0)
		{
			return "";
		}
		
		$button = $this->ipsclass->compiled_templates['skin_topic']->button_delete($this->forum['id'],$this->topic['tid'],$post_id,$this->md5_check );
		
		if ($this->ipsclass->member['g_is_supmod']) return $button;
		if ($this->moderator['delete_post']) return $button;
		if ($poster['id'] == $this->ipsclass->member['id'] and ($this->ipsclass->member['g_delete_own_posts'])) return $button;
		return "";
	}
	
	/*-------------------------------------------------------------------------*/
	// Render the edit button
	/*-------------------------------------------------------------------------*/
	
	function edit_button($post_id, $poster, $post_date)
	{
		if ($this->ipsclass->member['id'] == "" or $this->ipsclass->member['id'] == 0)
		{
			return "";
		}
		
		$button = $this->ipsclass->compiled_templates['skin_topic']->button_edit( $this->forum['id'],$this->topic['tid'],$post_id );
		
		if ($this->ipsclass->member['g_is_supmod']) return $button;
		
		if ($this->moderator['edit_post']) return $button;
		
		if ($poster['id'] == $this->ipsclass->member['id'] and ($this->ipsclass->member['g_edit_posts']))
		{
			// Have we set a time limit?
			
			if ($this->ipsclass->member['g_edit_cutoff'] > 0)
			{
				if ( $post_date > ( time() - ( intval($this->ipsclass->member['g_edit_cutoff']) * 60 ) ) )
				{
					return $button;
				}
				else
				{
					return "";
				}
			}
			else
			{
				return $button;
			}
		}
		
		return "";
	}
	
	/*-------------------------------------------------------------------------*/
	// Render the reply button
	/*-------------------------------------------------------------------------*/

	function reply_button()
	{
		if ($this->topic['state'] == 'closed' OR ($this->topic['poll_state'] AND $this->poll_only ) )
		{
			// Do we have the ability to post in
			// closed topics or is this a poll only?
			
			if ($this->ipsclass->member['g_post_closed'] == 1)
			{
				$replace = $this->ipsclass->compiled_templates['skin_topic']->button_posting( "{$this->ipsclass->base_url}act=post&amp;do=reply_post&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid'], "<{A_LOCKED_B}>" );
			}
			else
			{
				$replace = "<{A_LOCKED_B}>";
			}
		}
		else
		{
			if ( $this->topic['state'] == 'moved' )
			{
				$replace = "<{A_MOVED_B}>";
			}
			else
			{
				$replace = $this->ipsclass->compiled_templates['skin_topic']->button_posting( "{$this->ipsclass->base_url}act=post&amp;do=reply_post&amp;f=".$this->forum['id']."&amp;t=".$this->topic['tid'], "<{A_REPLY}>" );
			}
		}
		
		$this->output = str_replace( "<!--IBF.TOPIC_REPLY-->", $replace, $this->output );
	}
	
	/*-------------------------------------------------------------------------*/
	// Render the IP address
	/*-------------------------------------------------------------------------*/
	
	function view_ip($row, $poster)
	{
		if ($this->ipsclass->member['g_is_supmod'] != 1 && ( !isset($this->moderator['view_ip']) OR $this->moderator['view_ip'] != 1 ) )
		{
			return "";
		}
		else
		{
			$row['ip_address'] = $poster['mgroup'] == $this->ipsclass->vars['admin_group']
						  ? $this->ipsclass->compiled_templates['skin_topic']->ip_admin_hide()
						  : $this->ipsclass->compiled_templates['skin_topic']->ip_admin_show( $row['ip_address'] );
			return $this->ipsclass->compiled_templates['skin_topic']->ip_show($row['ip_address']);
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Render the topic multi-moderation
	/*-------------------------------------------------------------------------*/
	
	function multi_moderation()
	{
		$mm_html = "";
		
		$mm_array = $this->ipsclass->get_multimod( $this->forum['id'] );
		
		//-----------------------------------------
		// Print and show
		//-----------------------------------------
		
		if ( is_array( $mm_array ) and count( $mm_array ) )
		{
			foreach( $mm_array as $m )
			{
				$mm_html .= $this->ipsclass->compiled_templates['skin_topic']->mm_entry( $m[0], $m[1] );
			}
		}
		
		if ( $mm_html )
		{
			$mm_html = $this->ipsclass->compiled_templates['skin_topic']->mm_start($this->topic['tid']) . $mm_html . $this->ipsclass->compiled_templates['skin_topic']->mm_end();
		}
		
		return $mm_html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Render the moderator links
	/*-------------------------------------------------------------------------*/
	
	function moderation_panel()
	{
		$mod_links = "";
		
		if (!isset($this->ipsclass->member['id'])) return "";
		
		$skcusgej = 0;
		
		if ($this->ipsclass->member['id'] == $this->topic['starter_id'])
		{
			$skcusgej = 1;
		}
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$skcusgej = 1;
		}
		
		if ( isset($this->moderator['mid']) AND $this->moderator['mid'] != "" )
		{
			$skcusgej = 1;
		}
		
		if ( $skcusgej == 0 )
		{
		   	return "";
		}
		
		//-----------------------------------------
		// Add on approve/unapprove topic fing
		//-----------------------------------------
		
		if ( $this->ipsclass->can_queue_posts( $this->forum['id'] ) ) 
		{
			if ( $this->topic['approved'] != 1 )
			{
				$mod_links .= $this->ipsclass->compiled_templates['skin_topic']->mod_wrapper('topic_approve', $this->ipsclass->lang[ 'cpt_approvet' ]);
			}
			else
			{
				$mod_links .= $this->ipsclass->compiled_templates['skin_topic']->mod_wrapper('topic_unapprove', $this->ipsclass->lang[ 'cpt_unapprovet' ]);
			}
		}
		
		$actions = array( 'MOVE_TOPIC', 'CLOSE_TOPIC', 'OPEN_TOPIC', 'DELETE_TOPIC', 'EDIT_TOPIC', 'PIN_TOPIC', 'UNPIN_TOPIC', 'MERGE_TOPIC', 'UNSUBBIT' );
		
		foreach( $actions as $key )
		{
			if ($this->ipsclass->member['g_is_supmod'])
			{
				$mod_links .= $this->append_link($key);
			}
			elseif ( isset($this->moderator['mid']) AND $this->moderator['mid'])
			{
				if ($key == 'MERGE_TOPIC' or $key == 'SPLIT_TOPIC')
				{
					if ($this->moderator['split_merge'] == 1)
					{
						$mod_links .= $this->append_link($key);
					}
				}
				else if ( isset($this->moderator[ strtolower($key) ]) AND $this->moderator[ strtolower($key) ] )
				{
					$mod_links .= $this->append_link($key);
				}
				
				// What if member is a mod, but doesn't have these perms as a mod?
				
				elseif ($key == 'OPEN_TOPIC' or $key == 'CLOSE_TOPIC')
				{
					if ($this->ipsclass->member['g_open_close_posts'])
					{
						$mod_links .= $this->append_link($key);
					}
				}
				elseif ($key == 'DELETE_TOPIC')
				{
					if ($this->ipsclass->member['g_delete_own_topics'])
					{
						$mod_links .= $this->append_link($key);
					}
				}
			}
			elseif ($key == 'OPEN_TOPIC' or $key == 'CLOSE_TOPIC')
			{
				if ($this->ipsclass->member['g_open_close_posts'])
				{
					$mod_links .= $this->append_link($key);
				}
			}
			elseif ($key == 'DELETE_TOPIC')
			{
				if ($this->ipsclass->member['g_delete_own_topics'])
				{
					$mod_links .= $this->append_link($key);
				}
			}
		}
		
		if ($this->ipsclass->member['g_access_cp'] == 1)
		{
			$mod_links .= $this->append_link('TOPIC_HISTORY');
		}
		
		if ($mod_links != "")
		{
			return $this->ipsclass->compiled_templates['skin_topic']->Mod_Panel($mod_links, $this->forum['id'], $this->topic['tid'], $this->md5_check);
			
		}
		else
		{
			return "";
		}
	
	}
	
	/*-------------------------------------------------------------------------*/
	// Append mod links
	/*-------------------------------------------------------------------------*/
	
	function append_link( $key="" )
	{
		if ($key == "") return "";
		
		if ($this->topic['state'] == 'open'   and $key == 'OPEN_TOPIC') return "";
		if ($this->topic['state'] == 'closed' and $key == 'CLOSE_TOPIC') return "";
		if ($this->topic['state'] == 'moved'  and ($key == 'CLOSE_TOPIC' or $key == 'MOVE_TOPIC')) return "";
		if ($this->topic['pinned'] == 1 and $key == 'PIN_TOPIC')   return "";
		if ($this->topic['pinned'] == 0 and $key == 'UNPIN_TOPIC') return "";
		
		++$this->colspan;
		
		return $this->ipsclass->compiled_templates['skin_topic']->mod_wrapper($this->mod_action[$key], $this->ipsclass->lang[ $key ]);
	}
	
	/*-------------------------------------------------------------------------*/
	// Process and parse the poll
	/*-------------------------------------------------------------------------*/
	
	function parse_poll()
	{
	    $html        = "";
	    $check       = 0;
	    $poll_footer = "";
	    
        $this->ipsclass->load_template('skin_poll');
        
        //-----------------------------------------
        // Get the poll information...
        //-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'polls',
													  'where'  => "tid=".$this->topic['tid']
											 )      );
        					 
        $this->ipsclass->DB->simple_exec();
        
        $poll_data = $this->ipsclass->DB->fetch_row();
        
        //-----------------------------------------
        // check we have a poll
        //-----------------------------------------
        
        if ( ! $poll_data['pid'] )
        {
        	return;
        }
        
        //-----------------------------------------
        // Do we have a poll question?
        //-----------------------------------------
        
        if ( ! $poll_data['poll_question'] )
        {
        	$poll_data['poll_question'] = $this->topic['title'];
        }
        
        //-----------------------------------------
        // Poll only?
        //-----------------------------------------
        
        if( $poll_data['poll_only'] == 1 )
        {
	        $this->poll_only = 1;
        }
        
        //-----------------------------------------
        // Show the poll
        //-----------------------------------------
        
        $member_voted = 0;
        $total_votes  = 0;
        
        //-----------------------------------------
        // Have we voted in this poll?
        //-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => 'member_id',
													  'from'   => 'voters',
													  'where'  => "tid=".$this->topic['tid']
											 )      );
        					 
        $this->ipsclass->DB->simple_exec();
        
        while( $voter = $this->ipsclass->DB->fetch_row() )
        {
	        $total_votes++;
	        
	        if( $voter['member_id'] == $this->ipsclass->member['id'] )
	        {
		        $member_voted = 1;
	        }
        }
        
        //-----------------------------------------
        // Can we vote again?
        //-----------------------------------------
        
        if ( $member_voted )
        {
        	$check       = 1;
        	$poll_footer = $this->ipsclass->lang['poll_you_voted'];
        }
        
        if ( ($poll_data['starter_id'] == $this->ipsclass->member['id']) and ($this->ipsclass->vars['allow_creator_vote'] != 1) )
        {
        	$check       = 1;
        	$poll_footer = $this->ipsclass->lang['poll_you_created'];
        }
        	
        if ( ! $this->ipsclass->member['id'] )
        {
	        if ( !$this->ipsclass->vars['allow_result_view'] )
	        {
		        $check 		= 2;
	        }
	        else
	        {
        		$check      = 1;
    		}
    		
        	$poll_footer = $this->ipsclass->lang['poll_no_guests'];
        }
        
        //-----------------------------------------
        // is the topic locked?
        //-----------------------------------------
        
        if ( $this->topic['state'] == 'closed' )
        {
        	$check       = 1;
        	$poll_footer = '&nbsp;';
        }
        
        //-----------------------------------------
        // Can we see the poll before voting?
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['allow_result_view'] == 1 )
        {
        	if ( $this->ipsclass->input['mode'] == 'show' )
        	{
        		$check       = 1;
        		$poll_footer = "";
        	}
        }
        
        //-----------------------------------------
        // Stop the parser killing images
        // 'cos there are too many
        //-----------------------------------------
        
        $tmp_max_images                     = $this->ipsclass->vars['max_images'];
        $this->ipsclass->vars['max_images'] = 0;
        
        if ( $check == 1 )
        {
	        if( !is_object( $this->parser ) )
	        {
		        //-----------------------------------------
		        // Load and config the post parser
		        //-----------------------------------------
		        
				$_load = $this->ipsclass->memory_debug_make_flag();
		
		        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
		        $this->parser                      = new parse_bbcode();
		        $this->parser->ipsclass            = $this->ipsclass;
		        $this->parser->allow_update_caches = 1;
		        
		        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
		        
				$this->ipsclass->memory_debug_add( "TOPIC: Loaded han_parse_bbcode.php", $_load );
			}

        	//-----------------------------------------
        	// Show the results
        	//-----------------------------------------
        	
        	$html         	 = $this->ipsclass->compiled_templates['skin_poll']->poll_header( $this->topic['tid'], $poll_data['poll_question'] );
        	$poll_answers 	 = unserialize(stripslashes($poll_data['choices']));
        	
        	reset($poll_answers);
        	
        	foreach ( $poll_answers as $id => $data )
        	{
        		//-----------------------------------------
        		// Get the question
        		//-----------------------------------------
        		
        		$question    = $data['question'];
        		$choice_html = "";
        		$tv_poll     = 0;
        		
        		# Get total votes for this question
        		foreach( $poll_answers[ $id ]['votes'] as $number)
        		{
        			$tv_poll += intval( $number );
        		}
        			
        		//-----------------------------------------
        		// Get the choices for this question
        		//-----------------------------------------
        		
        		foreach( $data['choice'] as $choice_id => $text )
        		{
        			$choice  = $text;
        			
        			# Get total votes for this question -> choice
        			$votes   = intval($data['votes'][ $choice_id ]);
        			
					if ( strlen($choice) < 1 )
					{
						continue;
					}
        		
					if ( $this->ipsclass->vars['poll_tags'] )
					{
						$choice = $this->parser->parse_poll_tags($choice);
					}
        			
        			$percent = $votes == 0 ? 0 : $votes / $tv_poll * 100;
        			$percent = sprintf( '%.2f' , $percent );
        			$width   = $percent > 0 ? intval($percent * 2) : 0;
        			
        			$choice_html .= $this->ipsclass->compiled_templates['skin_poll']->poll_show_rendered_choice($choice_id, $votes, $id, $choice, $percent, $width);
        		}
        		
        		//-----------------------------------------
        		// Add HTML together
        		//-----------------------------------------
        		
        		$html .= $this->ipsclass->compiled_templates['skin_poll']->poll_show_rendered_question( $id, $question, $choice_html );
        	}

        	$html   .= $this->ipsclass->compiled_templates['skin_poll']->show_total_votes($total_votes);
        }
        else if ( $check == 2 )
        {
	        // Guest viewing poll results, but show results before voting is not allowed
	        
	        $html  = $this->ipsclass->compiled_templates['skin_poll']->poll_header($this->topic['tid'], $poll_data['poll_question']);
	        $html .= $this->ipsclass->compiled_templates['skin_poll']->poll_show_no_guest_view( );
	        $html .= $this->ipsclass->compiled_templates['skin_poll']->show_total_votes($total_votes);
        }
        else
        {
	        //-----------------------------------------
	        // Load and config the post parser
	        //-----------------------------------------
	        
			$_load = $this->ipsclass->memory_debug_make_flag();
	
	        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
	        $this->parser                      = new parse_bbcode();
	        $this->parser->ipsclass            = $this->ipsclass;
	        $this->parser->allow_update_caches = 1;
	        
	        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
	        
			$this->ipsclass->memory_debug_add( "TOPIC: Loaded han_parse_bbcode.php", $_load );

        	$poll_answers = unserialize(stripslashes($poll_data['choices']));
        	reset($poll_answers);
        	
        	//-----------------------------------------
        	// Show poll form
        	//-----------------------------------------
        	
        	$html = $this->ipsclass->compiled_templates['skin_poll']->poll_header($this->topic['tid'], $poll_data['poll_question']);
        	
        	foreach ( $poll_answers as $id => $data )
        	{
        		//-----------------------------------------
        		// Get the question
        		//-----------------------------------------
        		
        		$question    = $data['question'];
        		$choice_html = "";
        		
        		//-----------------------------------------
        		// Get the choices for this question
        		//-----------------------------------------
        		
        		foreach( $data['choice'] as $choice_id => $text )
        		{
        			$choice = $text;
        			$votes  = intval($data['votes'][ $choice_id ]);
        	
					//$total_votes += $votes;
					
					if ( strlen($choice) < 1 )
					{
						continue;
					}
        		
					if ($this->ipsclass->vars['poll_tags'])
					{
						$choice = $this->parser->parse_poll_tags($choice);
					}
        		
					if( isset($data['multi']) AND $data['multi'] == 1 )
					{
						$choice_html .= $this->ipsclass->compiled_templates['skin_poll']->poll_show_form_choice_multi($choice_id, $votes, $id, $choice);
					}
					else
					{
        				$choice_html .= $this->ipsclass->compiled_templates['skin_poll']->poll_show_form_choice($choice_id, $votes, $id, $choice);
    				}
        		}
        		
        		//-----------------------------------------
        		// Add HTML together
        		//-----------------------------------------
        		
        		$html .= $this->ipsclass->compiled_templates['skin_poll']->poll_show_form_question( $id, $question, $choice_html );
        	}
        	
        	$html   .= $this->ipsclass->compiled_templates['skin_poll']->show_total_votes($total_votes);
        }
        
        $html .= $this->ipsclass->compiled_templates['skin_poll']->poll_footer();
        
        if ( $poll_footer != "" )
        {
        	//-----------------------------------------
        	// Already defined..
        	//-----------------------------------------
        	
        	$html = str_replace( "<!--IBF.VOTE-->", $poll_footer, $html );
        }
        else
        {
        	//-----------------------------------------
        	// Not defined..
        	//-----------------------------------------
        	
        	if ( $this->ipsclass->vars['allow_result_view'] == 1 )
        	{
        		if ( $this->ipsclass->input['mode'] == 'show' )
        		{
        			// We are looking at results..
        			
        			$html = str_replace( "<!--IBF.SHOW-->", $this->ipsclass->compiled_templates['skin_poll']->button_show_voteable(), $html );
        		}
        		else
        		{
        			$html = str_replace( "<!--IBF.SHOW-->", $this->ipsclass->compiled_templates['skin_poll']->button_show_results(), $html );
        			$html = str_replace( "<!--IBF.VOTE-->", $this->ipsclass->compiled_templates['skin_poll']->button_vote(), $html );
        		}
        	}
        	else
        	{
        		//-----------------------------------------
        		// Do not allow result viewing
        		//-----------------------------------------
        		
        		$html = str_replace( "<!--IBF.VOTE-->", $this->ipsclass->compiled_templates['skin_poll']->button_vote(), $html );
        		$html = str_replace( "<!--IBF.SHOW-->", $this->ipsclass->compiled_templates['skin_poll']->button_null_vote(), $html );
        	}
        }
           
        $this->ipsclass->vars['max_images'] = $tmp_max_images;
        
        return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Build topic permissions
	/*-------------------------------------------------------------------------*/
	
	function build_permissions()
	{
		//-----------------------------------------
		// Polls
		//-----------------------------------------
		
		$this->can_vote = intval( $this->ipsclass->member['g_vote_polls'] );
		
		//-----------------------------------------
		// Topic rating: Rating
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] )
		{
			$this->can_rate = intval( $this->ipsclass->member['g_topic_rate_setting'] );
		}
		else
		{
			$this->can_rate = 0;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Add vote to rating
	/*-------------------------------------------------------------------------*/
	
	function topic_add_vote_to_rating()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topic_id  = intval($this->ipsclass->input['t']);
		$rating_id = intval($this->ipsclass->input['rating']);
		$vote_cast = array();
		
		$this->ipsclass->load_language('lang_topic');
		
		//-----------------------------------------
		// Permissions check
		//-----------------------------------------
		
		if ( ! $this->forum['forum_allow_rating'] )
		{
			$this->can_rate = 0;
		}
		
		if ( ! $this->can_rate )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'topic_rate_no_perm') );
		}
		
		//-----------------------------------------
		// Make sure we have a valid poll id
		//-----------------------------------------
		
		if ( ! $topic_id )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
   		
   		//-----------------------------------------
   		// No topic?
   		//-----------------------------------------
   		
   		if ( ! $this->topic['tid'] )
   		{
   			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'topic_rate_no_perm') );
   		}
		
		//-----------------------------------------
		// Locked topic?
		//-----------------------------------------
		
   		if ( $this->topic['state'] != 'open' )
   		{
   			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'locked_topic') );
   		}
   		
		//-----------------------------------------
		// Sneaky members rating topic more than 5?
		//-----------------------------------------
		   		
   		if( $rating_id > 5 )
   		{
	   		$rating_id = 5;
   		}
   		
   		if( $rating_id < 0 )
   		{
	   		$rating_id = 0;
   		}
   		
   		//-----------------------------------------
   		// Have we rated before?
		//-----------------------------------------
		
		$rating = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$this->topic['tid']} and rating_member_id=".$this->ipsclass->member['id'] ) );
		
		//-----------------------------------------
		// Already rated?
		//-----------------------------------------
		
		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['g_topic_rate_setting'] == 2 )
			{
				if ( $rating_id != $rating['rating_value'] )
				{
					$new_rating = $rating_id - $rating['rating_value'];
					
					$this->ipsclass->DB->do_update( 'topic_ratings', array( 'rating_value' => $rating_id ), 'rating_id='.$rating['rating_id'] );
					
					$this->ipsclass->DB->do_update( 'topics', array( 'topic_rating_total' => intval($this->topic['topic_rating_total']) + $new_rating ), 'tid='.$this->topic['tid'] );
				}
				
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['topic_rating_changed'] , "showtopic={$this->topic['tid']}&amp;st={$this->ipsclass->input['st']}" );
			}
			else
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['topic_rated_already'] , "showtopic={$this->topic['tid']}&amp;st={$this->ipsclass->input['st']}" );
				exit();
			}
		}
		
		//-----------------------------------------
		// NEW RATING!
		//-----------------------------------------
		
		else
		{
			$this->ipsclass->DB->do_insert( 'topic_ratings', array( 'rating_tid'        => $this->topic['tid'],
																    'rating_member_id'  => $this->ipsclass->member['id'],
																    'rating_value'      => $rating_id,
																    'rating_ip_address' => $this->ipsclass->ip_address ) );
																    
			$this->ipsclass->DB->do_update( 'topics', array( 'topic_rating_hits'  => intval($this->topic['topic_rating_hits'])  + 1,
															 'topic_rating_total' => intval($this->topic['topic_rating_total']) + $rating_id ), 'tid='.$this->topic['tid'] );
		}
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['topic_rating_done'] , "showtopic={$this->topic['tid']}&amp;st={$this->ipsclass->input['st']}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Add vote to poll
	/*-------------------------------------------------------------------------*/
	
	function topic_add_vote_to_poll()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topic_id  = intval($this->ipsclass->input['t']);
		$vote_cast = array();
		
		$this->ipsclass->load_language('lang_topic');
		
		//-----------------------------------------
		// Permissions check
		//-----------------------------------------
		
		if ( ! $this->can_vote )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_reply_polls') );
		}
		
		//-----------------------------------------
		// Make sure we have a valid poll id
		//-----------------------------------------
		
		if ( ! $topic_id )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
   		
   		//-----------------------------------------
   		// Load the topic and poll
   		//-----------------------------------------
   		
   		$this->ipsclass->DB->cache_add_query( 'poll_get_poll_with_topic', array( 'tid' => $topic_id ) );
		$this->ipsclass->DB->cache_exec_query();
		
   		$this->topic = $this->ipsclass->DB->fetch_row();
   		
   		//-----------------------------------------
   		// No topic?
   		//-----------------------------------------
   		
   		if ( ! $this->topic['tid'] )
   		{
   			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'poll_none_found') );
   		}
		
		//-----------------------------------------
		// Locked topic?
		//-----------------------------------------
		
   		if ( $this->topic['state'] != 'open' )
   		{
   			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'locked_topic') );
   		}

		//-----------------------------------------
		// Have reply permissions??
		//-----------------------------------------
		
   		if ( $this->ipsclass->check_perms( $this->ipsclass->cache['forum_cache'][ $this->topic['forum_id'] ]['reply_perms'] ) == FALSE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_replies') );
		}
   		
   		//-----------------------------------------
   		// Have we voted before?
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'member_id', 'from' => 'voters', 'where' => "tid={$this->topic['tid']} and member_id=".$this->ipsclass->member['id'] ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'poll_you_voted') );
		}
		
		//-----------------------------------------
		// Sort out the new array
		//-----------------------------------------
		
		$this->ipsclass->input['nullvote'] = isset($this->ipsclass->input['nullvote']) ? $this->ipsclass->input['nullvote'] : 0;
		
		if ( !$this->ipsclass->input['nullvote'] )
		{
			//-----------------------------------------
			// First, which choices and ID did we choose?
			// Single option poll...
			//-----------------------------------------
			
			if ( is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $question_id => $choice_id )
				{
					if ( ! $question_id or ! isset($choice_id) )
					{
						continue;
					}
					
					$vote_cast[ $question_id ][] = $choice_id;
				}
			}
			
			//-----------------------------------------
			// Multi vote poll
			//-----------------------------------------
			
			foreach( $this->ipsclass->input as $k => $v )
			{
				if( preg_match( "#^choice_(\d+)_(\d+)$#", $k, $matches ) )
				{
					if( $this->ipsclass->input[$k] == 1 )
					{
						$vote_cast[ $matches[1] ][] = $matches[2];
					}
				}
			}
			
			//-----------------------------------------
			// Unparse the choices
			//-----------------------------------------
			
			$poll_answers = unserialize( stripslashes( $this->topic['choices'] ) );
        	reset($poll_answers);
        	
        	//-----------------------------------------
			// Got enough votes?
			//-----------------------------------------
			
			if ( count( $vote_cast ) < count( $poll_answers ) )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_vote') );
			}
        	
        	//-----------------------------------------
        	// Add voter
        	//-----------------------------------------
        	
        	$this->ipsclass->DB->do_insert( 'voters', array( 'member_id'  => $this->ipsclass->member['id'],
															 'ip_address' => $this->ipsclass->ip_address,
															 'tid'        => $this->topic['tid'],
															 'forum_id'   => $this->topic['forum_id'],
															 'vote_date'  => time(),
															) );
										
        	//-----------------------------------------
        	// Loop
        	//-----------------------------------------
        	
        	foreach ( $vote_cast as $question_id => $choice_array )
        	{
	        	foreach( $choice_array as $choice_id )
	        	{
	        		$poll_answers[ $question_id ]['votes'][ $choice_id ]++;
	        		
	        		if ( $poll_answers[ $question_id ]['votes'][ $choice_id ] < 1 )
	        		{
	        			$poll_answers[ $question_id ]['votes'][ $choice_id ] = 1;
	        		}
        		}
        	}
        	
        	//-----------------------------------------
        	// Save...
        	//-----------------------------------------
        	
        	$this->topic['choices'] = addslashes( serialize( $poll_answers ) );
        	
        	$this->ipsclass->DB->simple_exec_query( array( 'update' => 'polls',
														   'set'    => "votes=votes+1,choices='{$this->topic['choices']}'",
														   'where'  => "pid={$this->topic['poll_id']}" ) );
		
        	//-----------------------------------------
        	// Go bump in the night?
        	//-----------------------------------------
        	
        	if ($this->topic['allow_pollbump'])
        	{
        		$this->topic['last_vote'] = time();
        		$this->topic['last_post'] = time();
				
				$this->ipsclass->DB->do_update( 'topics', array( 'last_vote' => $this->topic['last_vote'], 'last_post' => $this->topic['last_post'] ), 'tid='.$this->topic['tid'] );
        	}
        	else
        	{
        		$this->topic['last_vote'] = time();
        		
        		$this->ipsclass->DB->do_update( 'topics', array( 'last_vote' => $this->topic['last_vote'], 'last_post' => $this->topic['last_post'] ), 'tid='.$this->topic['tid'] );
        	}
        }
        else
        {
        	//-----------------------------------------
        	// Add null vote
        	//-----------------------------------------
        	
        	$this->ipsclass->DB->do_insert( 'voters', array( 'member_id'  => $this->ipsclass->member['id'],
															 'ip_address' => $this->ipsclass->ip_address,
															 'tid'        => $this->topic['tid'],
															 'forum_id'   => $this->topic['forum_id'],
															 'vote_date'  => time(),
															) );
		}
        
        $lang = $this->ipsclass->input['nullvote'] ? $this->ipsclass->lang['poll_viewing_results'] : $this->ipsclass->lang['poll_vote_added'];
		
		$this->ipsclass->print->redirect_screen( $lang , "showtopic={$this->topic['tid']}&amp;st={$this->ipsclass->input['st']}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Return last post
	/*-------------------------------------------------------------------------*/
	
	function return_last_post()
	{
		$st = 0;
		
        $mode 	= $this->ipsclass->my_getcookie( 'topicmode' );
        
        $pre	= $mode != 'threaded' ? 'st' : 'start';
        
        if( $mode == 'threaded' )
        {
	        $this->ipsclass->vars['display_max_posts'] 	= $this->ipsclass->vars['threaded_per_page'];
	        $this->ipsclass->input['st']				= $this->ipsclass->input['start'];
        }		
        	
		if ($this->topic['posts'])
		{
			if ( (($this->topic['posts'] + 1) % $this->ipsclass->vars['display_max_posts']) == 0 )
			{
				$pages = ($this->topic['posts'] + 1) / $this->ipsclass->vars['display_max_posts'];
			}
			else
			{
				$number = ( ($this->topic['posts'] + 1) / $this->ipsclass->vars['display_max_posts'] );
				$pages = ceil( $number);
			}
			
			$st = ($pages - 1) * $this->ipsclass->vars['display_max_posts'];
			
			if( $this->ipsclass->vars['post_order_sort'] == 'desc' )
			{
				$st = (ceil(($this->topic['posts']/$this->ipsclass->vars['display_max_posts'])) - $pages) * $this->ipsclass->vars['display_max_posts'];
			}
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pid',
													  'from'   => 'posts',
													  'where'  => "queued=0 AND topic_id=".$this->topic['tid'],
													  'order'  => $this->ipsclass->vars['post_order_column'].' DESC',
													  'limit'  => array( 0,1 )
											 )      );
        					 
        $this->ipsclass->DB->simple_exec();
        
		$post = $this->ipsclass->DB->fetch_row();
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid']."&pid={$post['pid']}&{$pre}={$st}&"."#entry".$post['pid']);
	}
	
	/*-------------------------------------------------------------------------*/
	// INIT, innit? IS IT?
	/*-------------------------------------------------------------------------*/
	
	function topic_init( $load_modules=0 )
	{
		//-----------------------------------------
		// Memory...
		//-----------------------------------------
		
		$_before = $this->ipsclass->memory_debug_make_flag();
		
		//-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
        $this->ipsclass->load_language('lang_topic');
		$this->ipsclass->load_language('lang_editors');
        $this->ipsclass->load_template('skin_topic');
	
        //-----------------------------------------
        // Custom Profile fields
        //-----------------------------------------
        
        /*if ( $this->ipsclass->vars['custom_profile_topic'] == 1 or $load_modules )
        {
			require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
			$this->custom_fields = new custom_fields( $this->ipsclass->DB );
			
			$this->custom_fields->member_id  = $this->ipsclass->member['id'];
			$this->custom_fields->cache_data = $this->ipsclass->cache['profilefields'];
			$this->custom_fields->admin      = intval($this->ipsclass->member['g_access_cp']);
			$this->custom_fields->supmod     = intval($this->ipsclass->member['g_is_supmod']);
        }*/
        
        //-----------------------------------------
 		// Get all the member groups and
 		// member title info
 		//-----------------------------------------
        
        if ( ! is_array( $this->ipsclass->cache['ranks'] ) )
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

		//-----------------------------------------
		// Memory debug
		//-----------------------------------------
		
		$this->ipsclass->memory_debug_add( 'TOPIC: Classes loaded (topics.php::topic_init)', $_before );
	}
	
	/*-------------------------------------------------------------------------*/
	// MAIN init
	/*-------------------------------------------------------------------------*/
	
	function init($topic="")
	{
		$this->md5_check = $this->ipsclass->return_md5_check();
		 
        if ( ! is_array($topic) )
        {
			//-----------------------------------------
			// Check the input
			//-----------------------------------------
			
			$this->ipsclass->input['t'] = intval($this->ipsclass->input['t']);
			
			if ( $this->ipsclass->input['t'] < 0  )
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
			}
			
			//-----------------------------------------
			// Get the forum info based on the forum ID,
			// get the category name, ID, and get the topic details
			//-----------------------------------------
			
			if ( !isset( $this->ipsclass->topic_cache['tid']) OR !$this->ipsclass->topic_cache['tid'] )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'topics',
															  'where'  => "tid=".$this->ipsclass->input['t'],
													)      );
									
				$this->ipsclass->DB->simple_exec();
				
				$this->topic = $this->ipsclass->DB->fetch_row();
			}
			else
			{
				$this->topic = $this->ipsclass->topic_cache;
			}
		}
		else
		{
			$this->topic = $topic;
		}
		
		$this->topic['forum_id'] = isset($this->topic['forum_id']) ? $this->topic['forum_id'] : 0;
        
        $this->forum = $this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ];
        
        $this->ipsclass->input['f'] = $this->forum['id'];
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if ( ! $this->forum['id'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
        }
        
        //-----------------------------------------
        // Error out if we can not find the topic
        //-----------------------------------------
        
        if ( ! $this->topic['tid'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
        }
        
        //-----------------------------------------
        // Error out if the topic is not approved
        //-----------------------------------------
        
        if ( ! $this->ipsclass->can_queue_posts($this->forum['id']) )
        {
			if ($this->topic['approved'] != 1)
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
			}
        }
        
        $this->ipsclass->forums->forums_check_access( $this->forum['id'], 1, 'topic' );
        
        //-----------------------------------------
        // Unserialize the read array and parse into
        // array
        //-----------------------------------------
        
        if ( $read = $this->ipsclass->my_getcookie('topicsread') )
        {
	        if( $read != "-1" )
	        {
        		$this->read_array = $this->ipsclass->clean_int_array( unserialize(stripslashes($read)) );
    		}
    		else
    		{
	    		$this->read_array = array();
    		}
        }
        
        $this->last_read_tid = isset($this->read_array[$this->topic['tid']]) ? $this->read_array[$this->topic['tid']] : 0;
        
        //-----------------------------------------
	    // Are we actually a moderator for this forum?
	    //-----------------------------------------
	    
	    if ( ! $this->ipsclass->member['g_is_supmod'] AND ! $this->ipsclass->member['g_access_cp'] )
	    {
	    	if ( !isset($this->ipsclass->member['_moderator'][ $this->forum['id'] ]) OR !is_array( $this->ipsclass->member['_moderator'][ $this->forum['id'] ] ) )
	    	{
	    		$this->ipsclass->member['is_mod'] = 0;
	    	}
	    }
	}
	
	/*-------------------------------------------------------------------------*/
	// Topic set up ya'll
	/*-------------------------------------------------------------------------*/
	
	function topic_set_up()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['show'] = isset($this->ipsclass->input['show']) ? intval( $this->ipsclass->input['show'] ) : 0;
		$this->ipsclass->input['st']   = intval( $this->ipsclass->input['st'] );
		
		$this->topic_init();
		
		//-----------------------------------------
		// Memory...
		//-----------------------------------------
		
		$_before = $this->ipsclass->memory_debug_make_flag();
		
		$this->base_url                = $this->ipsclass->base_url;
		$this->forum['JUMP']           = $this->ipsclass->build_forum_jump();
		$this->first                   = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
		$this->ipsclass->input['view'] = isset($this->ipsclass->input['view']) ? $this->ipsclass->input['view'] : NULL;
		
        //-----------------------------------------
        // Check viewing permissions, private forums,
        // password forums, etc
        //-----------------------------------------
        
        if ( ( ! $this->ipsclass->member['g_other_topics'] ) AND ( $this->topic['starter_id'] != $this->ipsclass->member['id'] ) )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_view_topic') );
        }
        
        //-----------------------------------------
        // Update the topic views counter
        //-----------------------------------------
        
        if ( ! $this->ipsclass->input['view'] AND $this->topic['state'] != 'link' )
        {
			if ( $this->ipsclass->vars['update_topic_views_immediately'] )
			{
				$this->ipsclass->DB->simple_construct( array( 'update' => 'topics',
															  'set'    => 'views=views+1',
															  'where'  => "tid=".$this->topic['tid'],
															  #'lowpro' => 1,
													)      );
									
				$this->ipsclass->DB->simple_shutdown_exec();
			}
			else
			{
				$this->ipsclass->DB->do_shutdown_insert( 'topic_views', array( 'views_tid' => $this->topic['tid'] ) );
			}
        }
        
        //-----------------------------------------
		// Need to update this topic?
		//-----------------------------------------
		
		if ( $this->topic['state'] == 'open' )
		{
			if( !$this->topic['topic_open_time'] OR $this->topic['topic_open_time'] < $this->topic['topic_close_time'] )
			{
				if ( $this->topic['topic_close_time'] AND ( $this->topic['topic_close_time'] <= time() AND ( time() >= $this->topic['topic_open_time'] OR !$this->topic['topic_open_time'] ) ) )
				{
					$this->topic['state'] = 'closed';
					
					$this->ipsclass->DB->do_shutdown_update( 'topics', array( 'state' => 'closed' ), 'tid='.$this->topic['tid'] );
				}
			}
			else if( $this->topic['topic_open_time'] OR $this->topic['topic_open_time'] > $this->topic['topic_close_time'] )
			{
				if ( $this->topic['topic_close_time'] AND ( $this->topic['topic_close_time'] <= time() AND time() <= $this->topic['topic_open_time'] ) )
				{
					$this->topic['state'] = 'closed';
					
					$this->ipsclass->DB->do_shutdown_update( 'topics', array( 'state' => 'closed' ), 'tid='.$this->topic['tid'] );
				}
			}				
		}
		else if ( $this->topic['state'] == 'closed' )
		{
			if( !$this->topic['topic_close_time'] OR $this->topic['topic_close_time'] < $this->topic['topic_open_time'] )
			{
				if ( $this->topic['topic_open_time'] AND ( $this->topic['topic_open_time'] <= time() AND ( time() >= $this->topic['topic_close_time'] OR !$this->topic['topic_close_time'] ) ) )
				{
					$this->topic['state'] = 'open';
					
					$this->ipsclass->DB->do_shutdown_update( 'topics', array( 'state' => 'open' ), 'tid='.$this->topic['tid'] );
				}
			}
			else if( $this->topic['topic_close_time'] OR $this->topic['topic_close_time'] > $this->topic['topic_open_time'] )
			{

				if ( $this->topic['topic_open_time'] AND ( $this->topic['topic_open_time'] <= time() AND time() <= $this->topic['topic_close_time'] ) )
				{
					$this->topic['state'] = 'open';
					
					$this->ipsclass->DB->do_shutdown_update( 'topics', array( 'state' => 'open' ), 'tid='.$this->topic['tid'] );
				}
			}				
		}
		
	    //-----------------------------------------
	    // Current topic rating value
	    //-----------------------------------------
	    
	    $this->topic['_rate_show']  = 0;
	    $this->topic['_rate_int']   = 0;
	    $this->topic['_rate_img']   = '';
	    
	    if ( $this->topic['state'] != 'open' )
	    {
	    	$this->topic['_allow_rate'] = 0;
	    }
	    else
	    {
	    	$this->topic['_allow_rate'] = $this->can_rate;
	    }
	    
	    if ( $this->forum['forum_allow_rating'] )
		{
			$rating = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$this->topic['tid']} and rating_member_id=".$this->ipsclass->member['id'] ) );
			
			if ( $rating['rating_value'] AND $this->ipsclass->member['g_topic_rate_setting'] != 2 )
			{
				$this->topic['_allow_rate'] = 0;
			}
			
			$this->topic['_rate_img']      = $this->ipsclass->compiled_templates['skin_topic']->topic_rating_image( 0 );
			$this->topic['_rating_value']  = $rating['rating_value'] ? $rating['rating_value'] : $this->ipsclass->lang['you_have_not_rated'];
			
			if ( $this->topic['topic_rating_total'] )
			{
				$this->topic['_rate_int'] = round( $this->topic['topic_rating_total'] / $this->topic['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $this->topic['topic_rating_hits'] >= $this->ipsclass->vars['topic_rating_needed'] ) AND ( $this->topic['_rate_int'] ) )
			{
				$this->topic['_rate_img']  = $this->ipsclass->compiled_templates['skin_topic']->topic_rating_image($this->topic['_rate_int'] );
				$this->topic['_rate_show'] = 1;
			}
		}
		else
		{
			$this->topic['_allow_rate'] = 0;
		}		
		
        //-----------------------------------------
        // Update the topic read cookie / counters
        //-----------------------------------------
        
        if ( !$this->ipsclass->input['view'] )
        {
			$this->read_array[ $this->topic['tid'] ] = time();
			
			$this->ipsclass->my_setcookie('topicsread', serialize($this->read_array), -1 );
		}
		
		if ( $this->ipsclass->vars['db_topic_read_cutoff'] AND $this->ipsclass->member['id'] )
        {	
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'topic_markers',
														  'where'  => "marker_forum_id=".$this->forum['id']." AND marker_member_id=".$this->ipsclass->member['id'],
												)      );
								  
			$this->ipsclass->DB->simple_exec();
		
			$this->db_row         = $this->ipsclass->DB->fetch_row();
			$this->my_topics_read = unserialize($this->db_row['marker_topics_read'] );
			$time_check           = ((isset($this->my_topics_read[ $this->topic['tid'] ]) AND $this->my_topics_read[ $this->topic['tid'] ]) > $this->db_row['marker_last_cleared']) ? $this->my_topics_read[ $this->topic['tid'] ] : $this->db_row['marker_last_cleared'];
			$save_array           = array();
			$read_topics_tid      = array( 0 => $this->topic['tid'] );
			
			$time_check           = $time_check > $this->ipsclass->member['members_markers']['board'] ? $time_check : $this->ipsclass->member['members_markers']['board'];
			
			//-----------------------------------------
			// Work out topics we've read and that haven't
			// been updated
			//-----------------------------------------
			
			if ( is_array( $this->my_topics_read ) )
			{
				foreach( $this->my_topics_read as $tid => $date )
				{
					if ( $date > $this->db_row['marker_last_cleared'] )
					{
						$read_topics_tid[] = $tid;
					}
				}
			}
			
			//-----------------------------------------
			// New post since last read / not read?
			// Yes: Update. No: Ignore
			//-----------------------------------------
			
			if ( ( $this->ipsclass->input['view'] != 'getnewpost' ) AND ( ( $time_check <= $this->topic['last_post'] ) OR ( $this->forum['forum_last_deletion'] > $this->db_row['marker_last_update'] ) ) )
			{
				$save_array['marker_unread'] 		= $this->db_row['marker_unread'] - 1;
				$save_array['marker_last_cleared']	= $this->db_row['marker_last_cleared'];
				
				$db_time = $this->db_row['marker_last_cleared'] > $this->ipsclass->member['members_markers']['board'] ? $this->db_row['marker_last_cleared'] : $this->ipsclass->member['members_markers']['board'];
			
				$this->my_topics_read[ $this->topic['tid'] ] = time();
				$read_topics_tid[]							 = $this->topic['tid'];
				
				//-----------------------------------------
				// All read? Recount and check
				//-----------------------------------------
				
				if ( $save_array['marker_unread'] <= 0 )
				{
					$approved = $this->ipsclass->member['is_mod'] ? ' AND approved IN (0,1) ' : ' AND approved=1 ';
					
					$count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as cnt, MIN(last_post) as min_last_post',
																		       'from'   => 'topics',
																		       'where'  => "forum_id={$this->forum['id']} {$approved} AND tid NOT IN(0,".implode(",",$read_topics_tid).") AND last_post > ".intval($db_time) ) );
					
					$save_array['marker_unread'] 		= intval($count['cnt']);
					
					if ( $save_array['marker_unread'] > 0 AND ( is_array( $this->my_topics_read ) and count( $this->my_topics_read ) ) )
					{
						$this->ipsclass->vars['db_topic_read_cutoff'] = $count['min_last_post'] - 1;
						$this->my_topics_read                         = array_filter( $this->my_topics_read, array( 'ipsclass', "array_filter_clean_read_topics" ) );
						$save_array['marker_topics_read']             = serialize( $this->my_topics_read );
					}
					
					//-----------------------------------------
					// Else, mark as read
					//-----------------------------------------
					
					else
					{
						$save_array['marker_topics_read']  = serialize( array() );
						$save_array['marker_last_cleared'] = time();
						$save_array['marker_unread']       = 0;
					}
				}
				else
				{
					$save_array['marker_topics_read'] = serialize($this->my_topics_read);
				}
				
				//-----------------------------------------
				// Update this topic...
				//-----------------------------------------
				
				$save_array['marker_last_update'] 	= time();
				$save_array['marker_member_id'] 	= $this->ipsclass->member['id'];
				$save_array['marker_forum_id']  	= $this->forum['id'];
					
				$this->ipsclass->DB->do_replace_into( 'topic_markers', $save_array, array('marker_member_id','marker_forum_id'), TRUE );
			}
		}
		
		//-----------------------------------------
		// Clean up
		//-----------------------------------------
		
		unset( $save_array, $read_topics_tid );
        
        //-----------------------------------------
        // If this forum is a link, then 
        // redirect them to the new location
        //-----------------------------------------
        
        if ( $this->topic['state'] == 'link' )
        {
        	$f_stuff = explode("&", $this->topic['moved_to']);
        	$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['topic_moved'], "showtopic={$f_stuff[0]}" );
        }
        
        //-----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //-----------------------------------------
        
       	$this->nav = $this->ipsclass->forums->forums_breadcrumb_nav( $this->forum['id'] );
        
        //-----------------------------------------
        // Are we a moderator?
        //-----------------------------------------
		
		if ( ($this->ipsclass->member['id']) and ($this->ipsclass->member['g_is_supmod'] != 1) )
		{
			$other_mgroups = array();
			
			if( $this->ipsclass->member['mgroup_others'] )
			{
				$other_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
			}
			
			$other_mgroups[] = $this->ipsclass->member['mgroup'];
			
			$mgroups = implode( ",", $other_mgroups );
			
			$this->ipsclass->DB->cache_add_query('topics_check_for_mod',  array( 'fid' => $this->forum['id'], 'mid' => $this->ipsclass->member['id'], 'gid' => $mgroups ) );
			$this->ipsclass->DB->simple_exec();
			
			$this->moderator = $this->ipsclass->DB->fetch_row();
		}
		
		$this->mod_action = array( 'CLOSE_TOPIC'   => '00',
								   'OPEN_TOPIC'    => '01',
								   'MOVE_TOPIC'    => '02',
								   'DELETE_TOPIC'  => '03',
								   'EDIT_TOPIC'    => '05',
								   'PIN_TOPIC'     => '15',
								   'UNPIN_TOPIC'   => '16',
								   'UNSUBBIT'      => '30',
								   'MERGE_TOPIC'   => '60',
								   'TOPIC_HISTORY' => '90',
								 );
		
		
		//-----------------------------------------
		// Hi! Light?
		//-----------------------------------------
		
		$hl = (isset($this->ipsclass->input['hl']) AND $this->ipsclass->input['hl']) ? '&amp;hl='.$this->ipsclass->input['hl'] : '';
		
		//-----------------------------------------
		// If we can see queued topics, add count
		//-----------------------------------------
		
		if ( $this->ipsclass->can_queue_posts($this->forum['id']) )
		{
			$this->topic['posts'] += intval( $this->topic['topic_queuedposts'] );
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------
		
		$this->topic['SHOW_PAGES']
			= $this->ipsclass->build_pagelinks( array( 'TOTAL_POSS'  => ($this->topic['posts']+1),
													   'PER_PAGE'    => $this->ipsclass->vars['display_max_posts'],
													   'CUR_ST_VAL'  => $this->first,
													   'L_SINGLE'    => "",
													   'BASE_URL'    => $this->base_url."showtopic=".$this->topic['tid'].$hl,
													 )
											  );
								   
		if ( ($this->topic['posts'] + 1) > $this->ipsclass->vars['display_max_posts'])
		{
			$this->topic['go_new'] = $this->ipsclass->compiled_templates['skin_topic']->golastpost_link($this->forum['id'], $this->topic['tid'] );
		}
								   
		
		//-----------------------------------------
		// Fix up some of the words
		//-----------------------------------------
		
		$this->topic['TOPIC_START_DATE'] = $this->ipsclass->get_date( $this->topic['start_date'], 'LONG' );
		
		$this->ipsclass->lang['topic_stats'] = str_replace( "<#START#>", $this->topic['TOPIC_START_DATE'], $this->ipsclass->lang['topic_stats']);
		$this->ipsclass->lang['topic_stats'] = str_replace( "<#POSTS#>", $this->topic['posts']           , $this->ipsclass->lang['topic_stats']);
		
		if ($this->topic['description'])
		{
			$this->topic['description'] = ', '.$this->topic['description'];
		}
		
		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------
		
		$this->qpids = $this->ipsclass->my_getcookie('mqtids');
		
		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------
		
		$this->ipsclass->input['selectedpids']     = $this->ipsclass->my_getcookie('modpids');
		$this->ipsclass->input['selectedpidcount'] = 0;
		
		$this->ipsclass->my_setcookie('modpids', '', 0);
		
		$this->ipsclass->memory_debug_add( "TOPIC: topics.php::topic_set_up", $_before );
	}
	
}

?>