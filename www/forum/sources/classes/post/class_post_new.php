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
	var $nav       = array();
	var $title     = "";
	var $post      = array();
	var $upload    = array();
	var $mod_topic = array();
	var $class     = "";
	var $m_group   = "";
	var $post_key  = "";
	
	var $has_poll       = 0;
	var $poll_questions = array();
	
	var $orig_post;
	
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
		// Check permissions
		//-----------------------------------------
		
		$this->post_key = ( isset($this->ipsclass->input['attach_post_key']) AND $this->ipsclass->input['attach_post_key'] != "" ) ? $this->ipsclass->input['attach_post_key'] : md5( microtime() );
				
		$this->check_for_new_topic();
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
		// check to make sure we have a valid topic title
		//-----------------------------------------
		
		$this->ipsclass->input['TopicTitle'] = str_replace( "<br />", "", $this->ipsclass->input['TopicTitle'] );
		
		$this->ipsclass->input['TopicTitle'] = trim($this->ipsclass->input['TopicTitle']);
		
		//-----------------------------------------
		// More unicode..
		//-----------------------------------------
		
		$temp = $this->ipsclass->txt_stripslashes($_POST['TopicTitle']);
		
		if ( $this->ipsclass->txt_mb_strlen($_POST['TopicTitle']) > $this->ipsclass->vars['topic_title_max_len'] )
		{
			$this->obj['post_errors'] = 'topic_title_long';
		}
		
		if ( (strlen($temp) < 2) or (!$this->ipsclass->input['TopicTitle'])  )
		{
			$this->obj['post_errors'] = 'no_topic_title';
		}
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compile_poll();
		
		if ( ($this->obj['post_errors'] != "") or ( $this->obj['preview_post'] != "" ) )
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
	// ADD TOPIC FUNCTION
	/*-------------------------------------------------------------------------*/
	
	function save_post()
	{
		//-----------------------------------------
		// Fix up the topic title
		//-----------------------------------------
		
		$this->ipsclass->input['TopicTitle'] = $this->pf_clean_topic_title( $this->ipsclass->input['TopicTitle'] );
		$this->ipsclass->input['TopicTitle'] = $this->parser->bad_words( $this->ipsclass->input['TopicTitle'] );
		
		$this->ipsclass->input['TopicDesc']  = trim( $this->parser->bad_words( $this->ipsclass->input['TopicDesc'] ) );
		$this->ipsclass->input['TopicDesc'] = $this->ipsclass->txt_mbsubstr( $this->ipsclass->input['TopicDesc'], 0, 70 );
		
		$pinned = 0;
		$state  = 'open';
		
		$this->ipsclass->input['mod_options'] = isset($this->ipsclass->input['mod_options']) ? $this->ipsclass->input['mod_options'] : '';
		
		if ( ($this->ipsclass->input['mod_options'] != "") or ($this->ipsclass->input['mod_options'] != 'nowt') )
		{
			if ($this->ipsclass->input['mod_options'] == 'pin')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or $this->moderator['pin_topic'] == 1)
				{
					$pinned = 1;
					
					$this->moderate_log( $this->ipsclass->lang['modlogs_pinned'], $this->ipsclass->input['TopicTitle']);
				}
			}
			else if ($this->ipsclass->input['mod_options'] == 'close')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or $this->moderator['close_topic'] == 1)
				{
					$state = 'closed';
					
					$this->moderate_log( $this->ipsclass->lang['modlogs_closed'], $this->ipsclass->input['TopicTitle']);
				}
			}
			else if ($this->ipsclass->input['mod_options'] == 'pinclose')
			{
				if ($this->ipsclass->member['g_is_supmod'] == 1 or ( $this->moderator['pin_topic'] == 1 AND $this->moderator['close_topic'] == 1 ) )
				{
					$pinned = 1;
					$state = 'closed';
					
					$this->moderate_log( $this->ipsclass->lang['modlogs_pinclose'], $this->ipsclass->input['TopicTitle']);
				}
			}
		}
		
		//-----------------------------------------
		// Check close times...
		//-----------------------------------------
		
		if ( $state == 'open' AND ( $this->times['open'] OR $this->times['close'] )
				AND ( $this->times['close'] <= time() OR ( $this->times['open'] > time() AND !$this->times['close'] ) ) )
		{
			$state = 'closed';
		}
		
		//-----------------------------------------
		// Build the master array
		//-----------------------------------------
		
		$this->topic = array(
							  'title'            => $this->ipsclass->input['TopicTitle'],
							  'description'      => $this->ipsclass->input['TopicDesc'] ,
							  'state'            => $state,
							  'posts'            => 0,
							  'starter_id'       => $this->ipsclass->member['id'],
							  'starter_name'     => $this->ipsclass->member['id'] ?  $this->ipsclass->member['members_display_name'] : $this->ipsclass->input['UserName'],
							  'start_date'       => time(),
							  'last_poster_id'   => $this->ipsclass->member['id'],
							  'last_poster_name' => $this->ipsclass->member['id'] ?  $this->ipsclass->member['members_display_name'] : $this->ipsclass->input['UserName'],
							  'last_post'        => time(),
							  'icon_id'          => intval($this->ipsclass->input['iconid']),
							  'author_mode'      => $this->ipsclass->member['id'] ? 1 : 0,
							  'poll_state'       => ( count( $this->poll_questions ) AND $this->can_add_poll ) ? 1 : 0,
							  'last_vote'        => 0,
							  'views'            => 0,
							  'forum_id'         => $this->forum['id'],
							  'approved'         => ( $this->obj['moderate'] == 1 || $this->obj['moderate'] == 2 ) ? 0 : 1,
							  'pinned'           => $pinned,
							  'topic_open_time'  => intval( $this->times['open'] ),
							  'topic_close_time' => intval( $this->times['close'] ),
							 );

		//-----------------------------------------
		// Insert the topic into the database to get the
		// last inserted value of the auto_increment field
		// follow suit with the post
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'title'            => 'string',
													  'description'      => 'string',
													  'starter_name'     => 'string',
													  'last_poster_name' => 'string' );
		
		$this->ipsclass->DB->do_insert( 'topics', $this->topic );
		
		$this->post['topic_id']  = $this->ipsclass->DB->get_insert_id();
		$this->topic['tid']      = $this->post['topic_id'];
		
		//-----------------------------------------
		// Update the post info with the upload array info
		//-----------------------------------------
		
		$this->post['post_key']  = $this->post_key;
		$this->post['new_topic'] = 1;
		
		//-----------------------------------------
		// Unqueue the post if we're starting a new topic
		//-----------------------------------------
		
		$this->post['queued'] = 0;
		
		//-----------------------------------------
		// Add post to DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'posts', $this->post );
	
		$this->post['pid'] = $this->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Update topic with firstpost ID
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'update' => 'topics',
													  'set'    => "topic_firstpost=".$this->post['pid'],
													  'where'  => "tid=".$this->topic['tid']
											 )      );
							 
		$this->ipsclass->DB->simple_exec();
		
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
		// If we are still here, lets update the
		// board/forum stats
		//----------------------------------------- 
		
		$this->pf_update_forum_and_stats($this->topic['tid'], $this->topic['title'], 'new');
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		
		$this->pf_make_attachments_permanent( $this->post_key, $this->post['pid'], 'post', array( 'topic_id' => $this->topic['tid'] ) );
		
		//-----------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-----------------------------------------
		
		$this->pf_increment_user_post_count();
		
		//-----------------------------------------
		// Are we tracking new topics we start 'auto_track'?
		//-----------------------------------------
		
		$this->pf_add_tracked_topic($this->topic['tid']);		
		
		//-----------------------------------------
		// Moderating?
		//-----------------------------------------
		
		if ( $this->obj['moderate'] == 1 OR $this->obj['moderate'] == 2 )
		{
			//-----------------------------------------
			// Redirect them with a message telling them the
			// post has to be previewed first
			//-----------------------------------------
			
			$this->notify_new_topic_approval( $this->topic['tid'], $this->topic['title'], $this->topic['starter_name'], $this->post['pid'] );
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['moderate_topic'], "act=SF&f={$this->forum['id']}" );
		}
		
		//-----------------------------------------
		// Are we tracking this forum? If so generate some mailies - yay!
		//-----------------------------------------
		
		$this->forum_tracker($this->forum['id'], $this->topic['tid'], $this->topic['title'], $this->forum['name'], $this->post['post'] );
		
		//-----------------------------------------
		// Redirect them back to the topic
		//-----------------------------------------
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic={$this->topic['tid']}");
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
		// Sort out the "raw" textarea input and make it safe incase
		// we have a <textarea> tag in the raw post var.
		//-----------------------------------------
		
		$topic_title = isset($_POST['TopicTitle']) ? $this->ipsclass->input['TopicTitle'] : "";
		$topic_desc  = isset($_POST['TopicDesc'])  ? $this->ipsclass->input['TopicDesc']  : "";
		
		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if (isset($this->obj['post_errors']) AND $this->obj['post_errors'])
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_post']->errors( $this->ipsclass->lang[ $this->obj['post_errors'] ]);
		}
		
		if ( $this->obj['preview_post'] )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_post']->preview( $this->show_post_preview( $this->post['post'], $this->post_key ) );
		}
		
		$this->output .= $this->html_start_form( array( 1 => array( 'CODE'           , '01' ),
														2 => array( 'attach_post_key', $this->post_key ),
											   )      );
		
		//-----------------------------------------
		// START TABLE
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_post']->table_structure();
		
		$topic_title = $this->ipsclass->compiled_templates['skin_post']->topictitle_fields( array( 'TITLE' => $topic_title, 'DESC' => $topic_desc ) );
		
		$start_table = $this->ipsclass->compiled_templates['skin_post']->table_top( "{$this->ipsclass->lang['top_txt_new']} {$this->forum['name']}");
		
		$name_fields = $this->html_name_field();
		
		$post_box    = $this->html_post_body( $raw_post );
		
		$mod_options = $this->mod_options();
		
		$end_form    = $this->ipsclass->compiled_templates['skin_post']->EndForm( $this->ipsclass->lang['submit_new'] );
		
		$post_icons  = $this->html_post_icons();
		
		//-----------------------------------------
		// POLL BOX
		//-----------------------------------------
		
		$poll_box = "";
		
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Did someone hit preview / do we have
			// post info?
			//-----------------------------------------
			
			$poll_questions = "";
			$poll_question  = "";
			$poll_votes		= "";
			$poll_choices   = "";
			$show_open      = 0;
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
			$poll_multi 	= preg_replace( "#,(\n)?$#", "\\1", $poll_multi );
			$poll_choices   = preg_replace( "#,(\n)?$#", "\\1", $poll_choices );
			
			$poll_box = $this->ipsclass->compiled_templates['skin_post']->poll_box( $this->max_poll_questions, $this->max_poll_choices_per_question, $poll_questions, $poll_choices, $poll_votes, $show_open, $poll_question, 0, $poll_multi, $poll_only );
		}
		
		//-----------------------------------------
		// UPLOAD BOX
		//-----------------------------------------
		
		$upload_field = $this->can_upload ? $this->html_build_uploads( $this->post_key, 'new' ) : '';
		
		$this->output = str_replace( "<!--START TABLE-->" , $start_table  , $this->output );
		$this->output = str_replace( "<!--NAME FIELDS-->" , $name_fields  , $this->output );
		$this->output = str_replace( "<!--POST BOX-->"    , $post_box     , $this->output );
		$this->output = str_replace( "<!--POLL BOX-->"    , $poll_box     , $this->output );
		$this->output = str_replace( "<!--POST ICONS-->"  , $post_icons   , $this->output );
		$this->output = str_replace( "<!--UPLOAD FIELD-->", $upload_field , $this->output );
		$this->output = str_replace( "<!--MOD OPTIONS-->" , $mod_options  , $this->output );
		$this->output = str_replace( "<!--END TABLE-->"   , $end_form     , $this->output );
		$this->output = str_replace( "<!--TOPIC TITLE-->" , $topic_title  , $this->output );
		$this->output = str_replace( "<!--FORUM RULES-->" , $this->ipsclass->print_forum_rules($this->forum), $this->output );
		
		//-----------------------------------------
		// Add in siggy buttons and such
		//-----------------------------------------
		
		$this->html_checkboxes('new', 0, $this->forum['id']);
		
		$this->title = $this->ipsclass->lang['posting_new_topic'];
		
		$this->show_post_navigation();
		
		//-----------------------------------------
		// Reset multi-quote cookie
		//-----------------------------------------
		
		$this->ipsclass->my_setcookie('mqtids', ',', 0);
		
		$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -> ".$this->title,
												  'JS'       => 1,
												  'NAV'      => $this->nav,
										 )     );
	}
}

?>