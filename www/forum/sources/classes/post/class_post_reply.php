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
|   > Post Sub-Class
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 (15:23)
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class post_functions extends class_post
{
	var $nav         = array();
	var $title       = "";
	var $post        = array();
	var $upload      = array();
	var $mod_topic   = array();
	var $m_group     = "";
	var $post_key    = "";
	var $quote_pids  = array();
	var $quote_posts = array();
	
	/*-------------------------------------------------------------------------*/
	// Sub class init
	/*-------------------------------------------------------------------------*/
	
	function main_init()
	{
		//-----------------------------------------
		// Load classes
		//-----------------------------------------
		
		$this->load_classes();
		$this->build_permissions();
		
		//-----------------------------------------
		// Set up post key
		//-----------------------------------------
		
		$this->post_key = ( isset($this->ipsclass->input['attach_post_key']) AND $this->ipsclass->input['attach_post_key'] != "" ) ? $this->ipsclass->input['attach_post_key'] : md5( microtime() );

		//-----------------------------------------
		// Lets load the topic from the database
		// before we do anything else.
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' 	=> 't.*, p.poll_only', 
												 'from' 	=> array( 'topics' => 't' ), 
												 'where' 	=> "t.forum_id=".intval($this->forum['id'])." AND t.tid=".intval($this->ipsclass->input['t']),
												 'add_join'	=> array( 1 => array( 'type'	=> 'left',
												 								  'from'	=> array( 'polls' => 'p' ),
												 								  'where'	=> 'p.tid=t.tid'
												 					)			)
										) 		);
		$this->ipsclass->DB->exec_query();
		
		$this->topic = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		// Check permissions, etc
		//-----------------------------------------
		
		if ( ! $this->topic['tid'] )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-----------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to reply to this topic
		//-----------------------------------------
		
		$this->check_for_reply($this->topic);
		
		//-----------------------------------------
		// POLL BOX ( Either topic starter or admin)
		// and without a current poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			$this->can_add_poll = 0;
			
			if ( ! $this->topic['poll_state'] )
			{
				if ( $this->ipsclass->member['id'] and ! $this->obj['moderate'] )
				{
					if ( $this->ipsclass->member['g_is_supmod'] == 1 )
					{
						$this->can_add_poll = 1;
					}
					else if ( $this->topic['starter_id'] == $this->ipsclass->member['id'] )
					{
						if ( ($this->ipsclass->vars['startpoll_cutoff'] > 0) AND ( $this->topic['start_date'] + ($this->ipsclass->vars['startpoll_cutoff'] * 3600) > time() ) )
						{
							$this->can_add_poll = 1;
						}
					}
				}
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// MAIN PROCESS FUNCTION
	/*-------------------------------------------------------------------------*/
	
	function process_post()
	{
		//-----------------------------------------
		// Convert times...
		//-----------------------------------------
		
		$this->convert_open_close_times();
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$this->post = $this->compile_post();
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compile_poll();
		
		if ( ($this->obj['post_errors'] != "") or ($this->obj['preview_post'] != "") )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			$this->show_form();
		}
		else
		{
			//-----------------------------------------
			// Guest w/ CAPTCHA?
			//-----------------------------------------
			
			if( $this->ipsclass->member['id'] == 0 AND $this->ipsclass->vars['guest_captcha'] )
			{
				//-----------------------------------------
				// Security code stuff
				//-----------------------------------------
				
				if( isset($this->ipsclass->input['fast_reply_used']) AND $this->ipsclass->input['fast_reply_used'] == 1 )
				{
					$this->obj['post_errors'] = 'reg_code_enter';
					$this->show_form();
					return;
				}					
				
				if ($this->ipsclass->input['imgid'] == "")
				{
					$this->obj['post_errors'] = 'err_reg_code';
					$this->show_form();
					return;
				}
				
				$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'reg_antispam',
															  'where'  => "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['imgid']))."'"
													 )      );
									 
				$this->ipsclass->DB->simple_exec();
				
				if ( ! $row = $this->ipsclass->DB->fetch_row() )
				{
					$this->obj['post_errors'] = 'err_reg_code';
					$this->show_form();
					return;
				}
				
				if ( trim( $this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['captcha']) ) != $row['regcode'] )
				{
					$this->obj['post_errors'] = 'err_reg_code';
					$this->show_form();
					return;
				}
				
				$this->ipsclass->DB->do_delete( 'reg_antispam', "regid='".trim($this->ipsclass->txt_alphanumerical_clean($this->ipsclass->input['imgid']))."'" );				
			}
						
			$this->save_post();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// ADD THE REPLY
	/*-------------------------------------------------------------------------*/
	
	function save_post()
	{
		//-----------------------------------------
		// Insert the post into the database to get the
		// last inserted value of the auto_increment field
		//-----------------------------------------
		
		$this->post['topic_id'] = $this->topic['tid'];
		
		//-----------------------------------------
		// Get the last post time of this topic not counting
		// this new reply
		//-----------------------------------------
		
		$this->last_post = $this->topic['last_post'];
		
		//-----------------------------------------
		// Are we a mod, and can we change the topic state?
		//-----------------------------------------
		
		$return_to_move = 0;
		
		$this->ipsclass->input['mod_options'] = isset($this->ipsclass->input['mod_options']) ? $this->ipsclass->input['mod_options'] : '';
		
		if ( ($this->ipsclass->input['mod_options'] != "") or ($this->ipsclass->input['mod_options'] != 'nowt') )
		{
			if ($this->ipsclass->input['mod_options'] == 'pin')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or $this->moderator['pin_topic'] == 1)
				{
					$this->topic['pinned'] = 1;
					
					$this->moderate_log( $this->ipsclass->lang['modlogs_pinned'], $this->topic['title']);
				}
			}
			else if ($this->ipsclass->input['mod_options'] == 'close')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or $this->moderator['close_topic'] == 1)
				{
					$this->topic['state'] = 'closed';
					
					$this->moderate_log( $this->ipsclass->lang['modlogs_closed'], $this->topic['title']);
				}
			}
			else if ($this->ipsclass->input['mod_options'] == 'move')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or $this->moderator['move_topic'] == 1)
				{
					$return_to_move = 1;
				}
			}
			else if ($this->ipsclass->input['mod_options'] == 'pinclose')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or ( $this->moderator['pin_topic'] == 1 AND $this->moderator['close_topic'] == 1 ) )
				{
					$this->topic['pinned'] = 1;
					$this->topic['state']  = 'closed';
					
					$this->moderate_log( $this->ipsclass->lang['modlogs_pinclose'], $this->topic['title']);
				}
			}
		}
		
		//-----------------------------------------
		// Check close times...
		//-----------------------------------------
		
		if ( $this->topic['state'] == 'open' AND ( $this->times['close'] AND $this->times['close'] <= time() ) )
		{
			$this->topic['state'] = 'closed';
		}
		else if ( $this->topic['state'] == 'closed' AND ( $this->times['open'] AND $this->times['open'] >= time() ) )
		{
			$this->topic['state'] = 'open';
		}
		
		//-----------------------------------------
		// Merge concurrent posts?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] AND $this->ipsclass->vars['post_merge_conc'] )
		{
			//-----------------------------------------
			// Get check time
			//-----------------------------------------
			
			$time_check = time() - ( $this->ipsclass->vars['post_merge_conc'] * 60 );
			
			//-----------------------------------------
			// Last to post?
			//-----------------------------------------
			
			if ( ( $this->topic['last_post'] > $time_check ) AND ( $this->topic['last_poster_id'] == $this->ipsclass->member['id'] ) )
			{
				//-----------------------------------------
				// Get the last post. 2 queries more efficient
				// than one... trust me
				//-----------------------------------------
				
				$last_pid = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'MAX(pid) as maxpid',
																			  'from'   => 'posts',
																			  'where'  => 'topic_id='.$this->topic['tid'],
																			  'limit'  => array( 0, 1 ) ) );
				
				$last_post = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																			   'from'   => 'posts',
																			   'where'  => 'pid='.$last_pid['maxpid'] ) );
				
				//-----------------------------------------
				// Sure we're the last poster?
				//-----------------------------------------
				
				if ( $last_post['author_id'] == $this->ipsclass->member['id'] )
				{
					$new_post  = $last_post['post'].'<br /><br />'.$this->post['post'];
					
					//-----------------------------------------
					// Make sure we don't have too many images
					//-----------------------------------------
										
					$test_post = $this->parser->pre_edit_parse( $new_post );
					$test_post = $this->parser->pre_db_parse( $test_post );
										
					if ( $this->parser->error )
					{
						$this->obj['post_errors'] = 'merge_'.$this->parser->error;
						$this->show_form($class);
						return;
					}
					
					//-----------------------------------------
					// Update post row
					//-----------------------------------------
					
					$this->ipsclass->DB->force_data_type = array( 'pid'  => 'int',
																  'post' => 'string' );
				
					$this->ipsclass->DB->do_update( 'posts', array( 'post' => $new_post, 'post_date' => time() ), 'pid='.$last_post['pid'] );
					
					$this->post['pid']      = $last_post['pid'];
					$this->post['post_key'] = $last_post['post_key'];
					$post_saved             = 1;
					$this->is_merging_posts = 1;
				}
			}
		}
		
		//-----------------------------------------
		// No?
		//-----------------------------------------
		
		if ( ! $this->is_merging_posts )
		{
			//-----------------------------------------
			// Add post to DB
			//-----------------------------------------
			
			$this->post['post_key']    = $this->post_key;
			$this->post['post_parent'] = isset($this->ipsclass->input['parent_id']) ? intval($this->ipsclass->input['parent_id']) : 0;
			
			//-----------------------------------------
			// Typecast
			//-----------------------------------------
			
			$this->ipsclass->DB->force_data_type = array( 'pid'  => 'int',
														  'post' => 'string' );
			
			$this->ipsclass->DB->do_insert( 'posts', $this->post );
			
			$this->post['pid'] = $this->ipsclass->DB->get_insert_id();
		}
		
		//-----------------------------------------
		// If we are still here, lets update the
		// board/forum/topic stats
		//-----------------------------------------
		
		$this->pf_update_forum_and_stats($this->topic['tid'], $this->topic['title'], 'reply');
		
		//-----------------------------------------
		// Get the correct number of replies
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} and queued != 1" ) );
		$this->ipsclass->DB->simple_exec();
		
		$posts = $this->ipsclass->DB->fetch_row();
		
		$pcount = intval( $posts['posts'] - 1 );
		
		//-----------------------------------------
		// Get the correct number of queued replies
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} and queued=1" ) );
		$this->ipsclass->DB->simple_exec();
		
		$qposts  = $this->ipsclass->DB->fetch_row();
		
		$qpcount = intval( $qposts['posts'] );
		
		//-----------------------------------------
		// UPDATE TOPIC
		//-----------------------------------------
		
		$poster_name = $this->ipsclass->member['id'] ? $this->ipsclass->member['members_display_name'] : $this->ipsclass->input['UserName'];
		
		$update_array = array(
							  'posts'			 => $pcount,
							  'topic_queuedposts'=> $qpcount
							 );
							 
		if ( $this->obj['moderate'] != 1 and $this->obj['moderate'] != 3 )
		{					 
			$update_array['last_poster_id']   = $this->ipsclass->member['id'];
			$update_array['last_poster_name'] = $poster_name;
			$update_array['last_post']        = time();
			$update_array['pinned']           = $this->topic['pinned'];
			$update_array['state']            = $this->topic['state'];
			
			if ( count( $this->poll_questions ) AND $this->can_add_poll )
			{
				$update_array['poll_state'] = 1;
			}
		}
		
		$this->ipsclass->DB->force_data_type = array( 'title'            => 'string',
													  'description'      => 'string',
													  'starter_name'     => 'string',
													  'last_poster_name' => 'string' );
													  
		$this->ipsclass->DB->do_update( 'topics', $update_array, "tid={$this->topic['tid']}"  );
		
		//-----------------------------------------
		// Add the poll to the polls table
		//-----------------------------------------
		
		if ( count( $this->poll_questions ) AND $this->can_add_poll )
		{
			$poll_only = 0;
			
			if( $this->ipsclass->vars['ipb_poll_only'] AND $this->ipsclass->input['poll_only'] == 1 )
			{
				$poll_only = 1;
			}
								
			$this->ipsclass->DB->do_insert( 'polls', 
											array (
													  'tid'           => $this->topic['tid'],
													  'forum_id'      => $this->forum['id'],
													  'start_date'    => time(),
													  'choices'       => addslashes(serialize( $this->poll_questions )),
													  'starter_id'    => $this->ipsclass->member['id'],
													  'votes'         => 0,
													  'poll_question' => $this->ipsclass->input['poll_question'],
													  'poll_only'	  => $poll_only,
											)     );
		}
		
		//-----------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-----------------------------------------
		
		if ( ! $this->is_merging_posts )
		{
			$this->pf_increment_user_post_count();
		}
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		 
		$this->pf_make_attachments_permanent( $this->post_key, $this->post['pid'], 'post', array( 'topic_id' => $this->topic['tid'] ) );
		
		//-----------------------------------------
		// MAKE SURE ATTACHMENTS FOR MERGED POSTS
		// ARE UPDATED
		//-----------------------------------------
		
		if ( $this->is_merging_posts )
		{
			//-----------------------------------------
			// Update attachments
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'attachments', array( 'attach_post_key' => $this->post['post_key'] ), "attach_rel_module='post' AND attach_rel_id=".$this->post['pid'] );
		}
		
		//-----------------------------------------
		// Moderating?
		//-----------------------------------------
		
		if ( ! $this->is_merging_posts AND ( $this->obj['moderate'] == 1 or $this->obj['moderate'] == 3) )
		{
			//-----------------------------------------
			// Boing!!!
			//-----------------------------------------
			
			$this->notify_new_topic_approval( $this->topic['tid'], $this->topic['title'], $this->topic['starter_name'], $this->post['pid'], 'reply' );
			
			$page = floor( $this->topic['posts'] / $this->ipsclass->vars['display_max_posts'] );
			$page = $page * $this->ipsclass->vars['display_max_posts'];
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['moderate_post'], "showtopic={$this->topic['tid']}&st=$page" );
		}
		
		//-----------------------------------------
		// Are we tracking topics we reply in 'auto_track'?
		//-----------------------------------------
		
		$this->pf_add_tracked_topic($this->topic['tid'], 1);
		
		//-----------------------------------------
		// Check for subscribed topics
		// Pass on the previous last post time of the topic
		// to see if we need to send emails out
		//-----------------------------------------
		
		$this->topic_tracker( $this->topic['tid'], $this->post['post'], $poster_name, $this->last_post );
		
		//-----------------------------------------
		// Redirect them back to the topic
		//-----------------------------------------
		
		if ($return_to_move == 1)
		{
			$this->ipsclass->boink_it($this->ipsclass->base_url."act=Mod&CODE=02&f={$this->forum['id']}&t={$this->topic['tid']}");
		}
		else
		{
			if ( $this->ipsclass->vars['post_order_sort'] == 'desc' )
			{
				$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic={$this->topic['tid']}&#entry{$this->post['pid']}");
			}
			else
			{
				$posts = $this->topic['posts'] + 1;
				
				if( $this->moderator['post_q'] OR $this->ipsclass->member['g_is_supmod'] )
				{
					$posts = $pcount + $qpcount;
				}
				
				$page = floor( ($posts + 1) / $this->ipsclass->vars['display_max_posts']);
				$page = $page * $this->ipsclass->vars['display_max_posts'];
				$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic={$this->topic['tid']}&st=$page&gopid={$this->post['pid']}&#entry{$this->post['pid']}");
			}
		}
	}

	/*-------------------------------------------------------------------------*/
	// SHOW FORM
	/*-------------------------------------------------------------------------*/

	function show_form()
	{
		//-----------------------------------------
		// Are we quoting posts?
		//-----------------------------------------
		
		$raw_post = $this->check_multi_quote();
		
		//-----------------------------------------
		// RTE? Convert RIGHT tags that QUOTE would
		// have put there
		//-----------------------------------------
		
		if ( $this->han_editor->method == 'rte' )
		{
			$raw_post = $this->parser->convert_ipb_html_to_html( $raw_post );
		}
		
		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if ( isset($this->obj['post_errors']) AND $this->obj['post_errors'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_post']->errors( $this->ipsclass->lang[ $this->obj['post_errors'] ]);
		}
		
		if ( isset($this->obj['preview_post']) AND $this->obj['preview_post'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_post']->preview( $this->show_post_preview( $this->post['post'], $this->post_key ) );
		}
		
		$this->output .= $this->html_start_form( array( 1 => array( 'CODE'            , '03' ),
														2 => array( 't'               , $this->topic['tid'] ),
														3 => array( 'attach_post_key' , $this->post_key     ),
														4 => array( 'parent_id'       , isset($this->ipsclass->input['parent_id']) ? intval($this->ipsclass->input['parent_id']) : 0 ),
											   )      );
														
		//-----------------------------------------
		// START TABLE
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_post']->table_structure();
		
		$start_table = $this->ipsclass->compiled_templates['skin_post']->table_top( "{$this->ipsclass->lang['top_txt_reply']} {$this->topic['title']}");
		
		$name_fields = $this->html_name_field();
		
		$post_box    = $this->html_post_body( $raw_post );
		
		$mod_options = $this->mod_options('reply');
		
		$end_form    = $this->ipsclass->compiled_templates['skin_post']->EndForm( $this->ipsclass->lang['submit_reply'] );
		
		$post_icons  = $this->html_post_icons();
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		$poll_box 		= "";
		$upload_field	= "";
		
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Did someone hit preview / do we have
			// post info?
			//-----------------------------------------
			
			$poll_questions = "";
			$poll_question	= "";
			$poll_choices   = "";
			$show_open      = 0;
			$poll_votes		= "";
			$poll_only		= "";
			$poll_multi		= "";			
			
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				foreach( $_POST['question'] as $id => $question )
				{
					$poll_questions .= "\t{$id} : '".str_replace( "'", '&#39;', $question )."',\n";
				}
				
				$poll_question = $this->ipsclass->input['poll_question'];
				$show_open     = 1;
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $checked )
				{
					$poll_multi .= "\t{$id} : '{$checked}',\n";
				}
			}			
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $id => $choice )
				{
					$poll_choices .= "\t'{$id}' : '".str_replace( "'", '&#39;', $choice )."',\n";
				}
			}
			
			$poll_only = 0;
			
			if( $this->ipsclass->vars['ipb_poll_only'] AND isset($this->ipsclass->input['poll_only']) AND $this->ipsclass->input['poll_only'] == 1 )
			{
				$poll_only = "checked='checked'";
			}			
			
			//-----------------------------------------
			// Trim off trailing commas (Safari hates it)
			//-----------------------------------------
			
			$poll_questions = preg_replace( "#,(\n)?$#", "\\1", $poll_questions );
			$poll_choices   = preg_replace( "#,(\n)?$#", "\\1", $poll_choices );
			$poll_multi 	= preg_replace( "#,(\n)?$#", "\\1", $poll_multi );
			
			$poll_box = $this->ipsclass->compiled_templates['skin_post']->poll_box( $this->max_poll_questions, $this->max_poll_choices_per_question, $poll_questions, $poll_choices, $poll_votes, $show_open, $poll_question, 0, $poll_multi, $poll_only );
		}
		
		if ( $this->can_upload )
		{
			$upload_field = $this->html_build_uploads( $this->post_key,'reply' );
		}
		
		$this->output = str_replace( "<!--START TABLE-->" , $start_table  , $this->output );
		$this->output = str_replace( "<!--NAME FIELDS-->" , $name_fields  , $this->output );
		$this->output = str_replace( "<!--POLL BOX-->"    , $poll_box     , $this->output );
		$this->output = str_replace( "<!--POST BOX-->"    , $post_box     , $this->output );
		$this->output = str_replace( "<!--POST ICONS-->"  , $post_icons   , $this->output );
		$this->output = str_replace( "<!--UPLOAD FIELD-->", $upload_field , $this->output );
		$this->output = str_replace( "<!--MOD OPTIONS-->" , $mod_options  , $this->output );
		$this->output = str_replace( "<!--END TABLE-->"   , $end_form     , $this->output );
		$this->output = str_replace( "<!--FORUM RULES-->" , $this->ipsclass->print_forum_rules($this->forum), $this->output );
		
		//-----------------------------------------
		// Add in siggy buttons and such
		//-----------------------------------------
		
		$this->html_checkboxes('reply', $this->topic['tid'], $this->forum['id']);
		
		$this->html_topic_summary($this->topic['tid']);
		
		$this->show_post_navigation();
						  
		$this->title = $this->ipsclass->lang['replying_in'].' '.$this->topic['title'];
		
		//-----------------------------------------
		// Reset multi-quote cookie
		//-----------------------------------------
		
		$this->ipsclass->my_setcookie('mqtids', ',', 0);
		
		$this->ipsclass->print->add_output( $this->output );
		
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -> ".$this->title,
												  'JS'       => 1,
												  'NAV'      => $this->nav,
										 )      );
		
	}
	
}

?>