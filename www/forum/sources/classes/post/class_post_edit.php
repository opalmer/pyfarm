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
|   > $Date: 2007-09-28 10:16:31 -0400 (Fri, 28 Sep 2007) $
|   > $Revision: 1117 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Post Sub-Class
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 (15:23)
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class post_functions extends class_post
{
	var $nav               = array();
	var $title             = "";
	var $post              = array();
	var $upload            = array();
	var $moderator         = array( 'member_id' => 0, 'member_name' => "", 'edit_post' => 0 );
	var $orig_post         = array();
	var $edit_title        = 0;
	var $post_key		   = "";
	var $class             = "";
	
	var $poll_data			= array();
	var $poll_answers		= array();
	
	function main_init()
	{
		//-----------------------------------------
		// Load classes
		//-----------------------------------------
		
		$this->load_classes();
		$this->build_permissions();
		
		//-----------------------------------------
		// Lets load the topic from the database before we do anything else.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=".intval($this->ipsclass->input['t']) ) );
		$this->ipsclass->DB->simple_exec();
		
		$this->topic = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		// Is it legitimate?
		//-----------------------------------------
		
		if ( ! $this->topic['tid'] )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		if( $this->forum['id'] != $this->topic['forum_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
		}		
		
		//-----------------------------------------
		// Load the old post
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'posts', 'where' => "pid=".intval($this->ipsclass->input['p']) ) );
		$this->ipsclass->DB->simple_exec();
		
		$this->orig_post = $this->ipsclass->DB->fetch_row();
		
		if (! $this->orig_post['pid'])
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-----------------------------------------
		// Same topic?
		//-----------------------------------------
		
		if ( $this->orig_post['topic_id'] != $this->topic['tid'] )
		{
            $this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }

		//-----------------------------------------
		// Generate post key (do we have one?)
		//-----------------------------------------
		
		if ( ! $this->orig_post['post_key'] )
		{
			//-----------------------------------------
			// Generate one and save back to post and attachment
			// to ensure 1.3 < compatibility
			//-----------------------------------------
			
			$this->post_key = md5(microtime());
			
			$this->ipsclass->DB->do_update( 'posts', array( 'post_key' => $this->post_key ), 'pid='.$this->orig_post['pid'] );
			
			$this->ipsclass->DB->do_update( 'attachments', array( 'attach_post_key' => $this->post_key ), "attach_rel_module='post' AND attach_rel_id=".$this->orig_post['pid'] );
		}
		else
		{
			$this->post_key = $this->orig_post['post_key'];
		}
		
		//-----------------------------------------
		// Load the moderator
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] )
		{
			$other_mgroups = array();
			
			if( $this->ipsclass->member['mgroup_others'] )
			{
				$other_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
			}
			
			$other_mgroups[] = $this->ipsclass->member['mgroup'];
			
			$mgroups = implode( ",", $other_mgroups );
						
			$this->ipsclass->DB->simple_construct( array( 'select' => 'member_id, member_name, mid, edit_post, edit_topic',
										  				  'from'   => 'moderators',
										  				  'where'  => "forum_id=".$this->forum['id']." AND (member_id='".$this->ipsclass->member['id']."' OR (is_group=1 AND group_id IN(".$mgroups.")))" ) );
										  
			$this->ipsclass->DB->simple_exec();
		
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->moderator = $r;
				
				if( $r['member_id'] == $this->ipsclass->member['id'] )
				{
					// Let member permissions override group permissions
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to edit this topic
		//-----------------------------------------
		
		$can_edit = 0;
		
		if ($this->ipsclass->member['g_is_supmod'])
		{
			$can_edit = 1;
		}
		
		if ( $this->moderator['edit_post'] )
		{
			$can_edit = 1;
		}
		
		if ( ($this->orig_post['author_id'] == $this->ipsclass->member['id']) and ($this->ipsclass->member['g_edit_posts']) )
		{
			//-----------------------------------------
			// Have we set a time limit?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['g_edit_cutoff'] > 0 )
			{
				if ( $this->orig_post['post_date'] > ( time() - ( intval($this->ipsclass->member['g_edit_cutoff']) * 60 ) ) )
				{
					$can_edit = 1;
				}
			}
			else
			{
				$can_edit = 1;
			}
		}
		
		if ( $can_edit != 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'not_op') );
		}
		
		//-----------------------------------------
		// Check access
		//-----------------------------------------
		
		$this->check_for_edit($this->topic);
		
		//-----------------------------------------
		// Do we have edit topic abilities?
		//-----------------------------------------
		
		# For edit, this means there is a poll and we have perm to edit
		$this->can_add_poll_mod = 0;
		
		if ( $this->orig_post['new_topic'] == 1 )
		{
			if ( $this->ipsclass->member['g_is_supmod'] == 1 )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			else if ( $this->moderator['edit_topic'] == 1 )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			else if ( $this->ipsclass->member['g_edit_topic'] == 1 AND ($this->orig_post['author_id'] == $this->ipsclass->member['id']) )
			{
				$this->edit_title = 1;
			}
		}
		else
		{
			$this->can_add_poll = 0;
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
		// Did we remove an attachment?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['removeattachid'] )
		{
			if ( $this->ipsclass->input[ 'removeattach_'. $this->ipsclass->input['removeattachid'] ] )
			{
				$this->pf_remove_attachment( intval($this->ipsclass->input['removeattachid']), $this->post_key );
				$this->show_form();
			}
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		// overwrites saved post intentionally
		//-----------------------------------------
		
		$this->post = $this->compile_post();
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		if( $this->can_add_poll )
		{

			//-----------------------------------------
			// Load the poll from the DB
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'polls', 'where' => "tid=".$this->topic['tid'] ) );
			$this->ipsclass->DB->simple_exec();
	
    		$this->poll_data = $this->ipsclass->DB->fetch_row();
    		
    		$this->poll_answers = $this->poll_data['choices'] ? unserialize(stripslashes($this->poll_data['choices'])) : array();
		}
		
		$this->poll_questions = $this->compile_poll();

		//-----------------------------------------
		// Check for errors
		//-----------------------------------------

		if ( ($this->obj['post_errors'] != "") or ($this->obj['preview_post'] != "") )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			$this->show_form( );
		}
		else
		{
			$this->save_post( );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// COMPLETE EDIT THINGY
	/*-------------------------------------------------------------------------*/
	
	function save_post()
	{
		$time = $this->ipsclass->get_date( time(), 'LONG' );
				
		//-----------------------------------------
		// Reset some data
		//-----------------------------------------
		
		$this->post['ip_address']  = $this->orig_post['ip_address'];
		$this->post['topic_id']    = $this->orig_post['topic_id'];
		$this->post['author_id']   = $this->orig_post['author_id'];
		$this->post['post_date']   = $this->orig_post['post_date'];
		$this->post['author_name'] = $this->orig_post['author_name'];
		$this->post['queued']      = $this->orig_post['queued'];
		$this->post['edit_time']   = time();
		$this->post['edit_name']   = $this->ipsclass->member['members_display_name'];
		
		//-----------------------------------------
		// If the post icon has changed, update the topic post icon
		//-----------------------------------------
		
		if ( $this->orig_post['new_topic'] == 1 )
		{
			if ($this->post['icon_id'] != $this->orig_post['icon_id'])
			{
				$this->ipsclass->DB->do_update( 'topics', array( 'icon_id' => $this->post['icon_id'] ), 'tid='.$this->topic['tid'] );
			}
		}
		
		//-----------------------------------------
		// Update open and close times
		//-----------------------------------------
		
		if ( $this->orig_post['new_topic'] == 1 )
		{
			$times = array();
			
			if ( $this->can_set_open_time AND $this->times['open'] )
			{
				$times['topic_open_time'] = intval( $this->times['open'] );
				
				if( $this->topic['topic_open_time'] AND $this->times['open'] )
				{
					$times['state'] = "closed";
					
					if( time() > $this->topic['topic_open_time'] )
					{
						if( time() < $this->topic['topic_close_time'] )
						{
							$times['state'] = "open";
						}
					}
				}
				if ( ! $this->times['open'] AND $this->topic['topic_open_time'] )
				{
					if ( $this->topic['state'] == 'closed' )
					{
						$times['state'] = 'open';
					}
				}				
			}
						
			if ( $this->can_set_close_time AND $this->times['close'] )
			{
				$times['topic_close_time'] = intval( $this->times['close'] );
				
				//-----------------------------------------
				// Was a close time, but not now?
				//-----------------------------------------
				
				if ( ! $this->times['close'] AND $this->topic['topic_close_time'] )
				{
					if ( $this->topic['state'] == 'closed' )
					{
						$times['state'] = 'open';
					}
				}
			}
			
			if ( count( $times ) )
			{
				$this->ipsclass->DB->do_update( 'topics', $times, "tid=".$this->topic['tid'] );
			}
		}
		
		//-----------------------------------------
		// Update poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			if ( is_array( $this->poll_questions ) AND count( $this->poll_questions ) )
			{
				$poll_only = 0;
				
				if ( $this->ipsclass->vars['ipb_poll_only'] AND $this->ipsclass->input['poll_only'] == 1 )
				{
					$poll_only = 1;
				}
				
				if ( $this->topic['poll_state'] )
				{
					$this->ipsclass->DB->do_update( 'polls', array( 'votes'         => intval($this->poll_total_votes),
																	'choices'       => addslashes(serialize( $this->poll_questions )),
																	'poll_question' => $this->ipsclass->input['poll_question'],
																	'poll_only'		=> $poll_only,
																  ), 'tid='.$this->topic['tid']  );
							
					if( $this->poll_data['choices'] != serialize( $this->poll_questions ) OR $this->poll_data['votes'] != intval($this->poll_total_votes) )
					{
						$this->ipsclass->DB->do_insert( 'moderator_logs', array (
																				 'forum_id'    => $this->forum['id'],
																				 'topic_id'    => $this->topic['tid'],
																				 'post_id'     => $this->orig_post['pid'],
																				 'member_id'   => $this->ipsclass->member['id'],
																				 'member_name' => $this->ipsclass->member['members_display_name'],
																				 'ip_address'  => $this->ip_address,
																				 'http_referer'=> $this->ipsclass->my_getenv('HTTP_REFERER'),
																				 'ctime'       => time(),
																				 'topic_title' => $this->topic['title'],
																				 'action'      => "Edited poll",
																				 'query_string'=> $this->ipsclass->my_getenv('QUERY_STRING'),
																			 )    );
					}
				}
				else
				{
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
													
					$this->ipsclass->DB->do_insert( 'moderator_logs', array (
																			 'forum_id'    => $this->forum['id'],
																			 'topic_id'    => $this->topic['tid'],
																			 'post_id'     => $this->orig_post['pid'],
																			 'member_id'   => $this->ipsclass->member['id'],
																			 'member_name' => $this->ipsclass->member['members_display_name'],
																			 'ip_address'  => $this->ip_address,
																			 'http_referer'=> $this->ipsclass->my_getenv('HTTP_REFERER'),
																			 'ctime'       => time(),
																			 'topic_title' => $this->topic['title'],
																			 'action'      => "Added a poll to the topic titled '{$this->ipsclass->input['poll_question']}'",
																			 'query_string'=> $this->ipsclass->my_getenv('QUERY_STRING'),
																		 )    );
													
					$this->ipsclass->DB->do_update( 'topics', array( 'poll_state' => 1, 'last_vote' => 0, 'total_votes' => 0 ), 'tid='.$this->topic['tid'] );								
				}
			}
			else
			{
				//-----------------------------------------
				// Remove the poll
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'polls' , 'where' => "tid=".$this->topic['tid'] ) );
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'voters', 'where' => "tid=".$this->topic['tid'] ) );
				$this->ipsclass->DB->do_update( 'topics', array( 'poll_state' => 0, 'last_vote' => 0, 'total_votes' => 0 ), 'tid='.$this->topic['tid'] );
			}
		}
		
		//-----------------------------------------
		// Update topic title?
		//-----------------------------------------
		
		if ( $this->edit_title == 1 )
		{
			//-----------------------------------------
			// Update topic title
			//-----------------------------------------
			
			$this->ipsclass->input['TopicTitle'] = $this->pf_clean_topic_title( $this->ipsclass->input['TopicTitle'] );
			$this->ipsclass->input['TopicTitle'] = trim( $this->parser->bad_words( $this->ipsclass->input['TopicTitle'] ) );
			
			$this->ipsclass->input['TopicDesc']  = trim( $this->parser->bad_words( $this->ipsclass->input['TopicDesc']  ) );
			$this->ipsclass->input['TopicDesc'] = $this->ipsclass->txt_mbsubstr( $this->ipsclass->input['TopicDesc'], 0, 70 );
			
			if ( $this->ipsclass->input['TopicTitle'] != "" )
			{
				if ( ($this->ipsclass->input['TopicTitle'] != $this->topic['title']) or ($this->ipsclass->input['TopicDesc'] != $this->topic['description'])  )
				{
					$this->ipsclass->DB->do_update( 'topics', array( 'title'       => $this->ipsclass->input['TopicTitle'],
																	 'description' => $this->ipsclass->input['TopicDesc']
																   ) , "tid=".$this->topic['tid'] );
					
					if ($this->topic['tid'] == $this->forum['last_id'])
					{
						$this->ipsclass->DB->do_update( 'forums', array( 'last_title' => $this->ipsclass->input['TopicTitle'] ), 'id='.$this->forum['id'] );
						$this->ipsclass->update_forum_cache();
					}
					
					if ( ($this->moderator['edit_topic'] == 1) OR ( $this->ipsclass->member['g_is_supmod'] == 1 ) )
					{
						$this->ipsclass->DB->do_insert( 'moderator_logs', array (
																				 'forum_id'    => $this->forum['id'],
																				 'topic_id'    => $this->topic['tid'],
																				 'post_id'     => $this->orig_post['pid'],
																				 'member_id'   => $this->ipsclass->member['id'],
																				 'member_name' => $this->ipsclass->member['members_display_name'],
																				 'ip_address'  => $this->ip_address,
																				 'http_referer'=> $this->ipsclass->my_getenv('HTTP_REFERER'),
																				 'ctime'       => time(),
																				 'topic_title' => $this->topic['title'],
																				 'action'      => "Edited topic title or description '{$this->topic['title']}' to '{$this->ipsclass->input['TopicTitle']}' via post form",
																				 'query_string'=> $this->ipsclass->my_getenv('QUERY_STRING'),
																			 )    );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Reason for edit?
		//-----------------------------------------
		
		if ( $this->moderator['edit_post'] OR $this->ipsclass->member['g_is_supmod'] )
		{
			$this->post['post_edit_reason'] = trim( $this->ipsclass->input['post_edit_reason'] );
		}
		
		//-----------------------------------------
		// Update the database (ib_forum_post)
		//-----------------------------------------
		
		$this->post['append_edit'] = 1;
		
		if ( $this->ipsclass->member['g_append_edit'] )
		{
			if ( $this->ipsclass->input['add_edit'] != 1 )
			{
				$this->post['append_edit'] = 0;
			}
		}
		
		$this->ipsclass->DB->force_data_type = array( 'post_edit_reason' => 'string' );
		
		$this->ipsclass->DB->do_update( 'posts', $this->post, 'pid='.$this->orig_post['pid'] );
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		
		$this->pf_make_attachments_permanent( $this->post_key, $this->orig_post['pid'], 'post', array( 'topic_id' => $this->topic['tid'] ) );
		
		//-----------------------------------------
		// Make sure paperclip symbol is OK
		//-----------------------------------------
		
		$this->pf_recount_topic_attachments($this->topic['tid']);
		
		//-----------------------------------------
		// Not XML? Redirect them back to the topic
		//-----------------------------------------
		
		if ( $this->ipsclass->input['act'] == 'xmlout' )
		{
			return TRUE;
		}
		else
		{
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['post_edited'], "showtopic={$this->topic['tid']}&st={$this->ipsclass->input['st']}#entry{$this->orig_post['pid']}");
		}
	}

	/*-------------------------------------------------------------------------*/
	// SHOW FORM
	/*-------------------------------------------------------------------------*/

	function show_form()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$raw_post = "";
		
		//-----------------------------------------
		// Unconvert the saved post if required
		//-----------------------------------------
		
		if ( ! isset($_POST['Post']) )
		{
			//-----------------------------------------
			// If we're using RTE, then just clean up html
			//-----------------------------------------
			
			if ( $this->han_editor->method == 'rte' )
			{
				$raw_post = $this->parser->convert_ipb_html_to_html( $this->orig_post['post'] );

				if( intval($this->orig_post['post_htmlstate']) AND $this->forum['use_html'] AND $this->ipsclass->member['g_dohtml'] )
				{
					# Make EMO_DIR safe so the ^> regex works
					$raw_post = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $raw_post );
					
					# New emo
					$raw_post = preg_replace( "#(\s)?<([^>]+?)emoid=\"(.+?)\"([^>]*?)".">(\s)?#is", "\\1\\3\\5", $raw_post );
					
					# And convert it back again...
					$raw_post = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $raw_post );

					$raw_post = $this->parser->convert_std_to_rte( $raw_post );
				}

				if( isset($this->orig_post['post_htmlstate']) AND $this->orig_post['post_htmlstate'] == 2 )
				{
					$raw_post = str_replace( '&lt;br&gt;', "<br />", $raw_post );
					$raw_post = str_replace( '&lt;br /&gt;', "<br />", $raw_post );
				}
			}
			else
			{
				$this->orig_post['post_htmlstate'] = isset($this->orig_post['post_htmlstate']) ? $this->orig_post['post_htmlstate'] : 0;
				$this->parser->parse_html    = intval($this->orig_post['post_htmlstate']) AND $this->forum['use_html'] AND $this->ipsclass->member['g_dohtml'] ? 1 : 0;
				$this->parser->parse_nl2br   = (isset($this->orig_post['post_htmlstate']) AND $this->orig_post['post_htmlstate'] == 2) ? 1 : 0;
				$this->parser->parse_smilies = intval($this->orig_post['use_emo']);
				$this->parser->parse_bbcode  = $this->forum['use_ibc'];

				if( $this->parser->parse_html )
				{
					# Make EMO_DIR safe so the ^> regex works
					$this->orig_post['post'] = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $this->orig_post['post'] );
					
					# New emo
					$this->orig_post['post'] = preg_replace( "#(\s)?<([^>]+?)emoid=\"(.+?)\"([^>]*?)".">(\s)?#is", "\\1\\3\\5", $this->orig_post['post'] );
					
					# And convert it back again...
					$this->orig_post['post'] = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $this->orig_post['post'] );
				
					$this->orig_post['post'] = $this->parser->convert_ipb_html_to_html( $this->orig_post['post'] );
					
					$this->orig_post['post'] = htmlspecialchars( $this->orig_post['post'] );
					
					if( $this->parser->parse_nl2br )
					{
						$this->orig_post['post'] = str_replace( '&lt;br&gt;', "\n", $this->orig_post['post'] );
						$this->orig_post['post'] = str_replace( '&lt;br /&gt;', "\n", $this->orig_post['post'] );
					}
				}
				
				$raw_post = $this->parser->pre_edit_parse( $this->orig_post['post'] );
			}
		}
		else
		{
			if ( $this->ipsclass->input['_from'] == 'quickedit' )
			{
				$this->orig_post['post_htmlstatus'] = isset($this->orig_post['post_htmlstatus']) ? $this->orig_post['post_htmlstatus'] : 0;
				$this->parser->parse_html    = intval($this->orig_post['post_htmlstatus']) AND $this->forum['use_html'] AND $this->ipsclass->member['g_dohtml'] ? 1 : 0;
				$this->parser->parse_nl2br   = (isset($this->ipsclass->input['post_htmlstatus']) AND $this->ipsclass->input['post_htmlstatus'] == 2) ? 1 : 0;
				$this->parser->parse_smilies = intval($this->orig_post['use_emo']);
				$this->parser->parse_bbcode  = $this->forum['use_ibc'];

				if ( $this->han_editor->method == 'rte' )
				{
					$raw_post = $this->parser->convert_std_to_rte( $this->ipsclass->txt_stripslashes( $_POST['Post'] ) );
					
					foreach( $this->ipsclass->skin['_macros'] as $row )
			      	{
						if ( $row['macro_value'] != "" )
						{
							$raw_post = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $raw_post );
						}
					}

					$raw_post = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $raw_post );
					$raw_post = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $raw_post );
				}
				else
				{
					$raw_post = $this->ipsclass->txt_stripslashes( $_POST['Post'] );
				}
			}
			else
			{
				$raw_post = $this->ipsclass->txt_stripslashes( $_POST['Post'] );
			}
		}
		
		//-----------------------------------------
		// Is this the first post in the topic?
		//-----------------------------------------
		
		$topic_title = "";
		$topic_desc  = "";
		
		if ( $this->edit_title == 1 )
		{
			$topic_title = isset($_POST['TopicTitle']) ? $this->ipsclass->input['TopicTitle'] : $this->topic['title'];
			$topic_desc  = isset($_POST['TopicDesc'])  ? $this->ipsclass->input['TopicDesc']  : $this->topic['description'];
			
			$topic_title = $this->ipsclass->compiled_templates['skin_post']->topictitle_fields( array( 'TITLE' => $topic_title, 'DESC' => $topic_desc ) );
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
		
		$this->output .= $this->html_start_form( array( 1 => array( 'CODE'           , '09' ),
														2 => array( 't'              , $this->topic['tid']),
														3 => array( 'p'              , $this->ipsclass->input['p'] ),
														4 => array( 'st'             , $this->ipsclass->input['st'] ),
														5 => array( 'attach_post_key', $this->post_key )
											   )       );
														
		//-----------------------------------------
		// START TABLE
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_post']->table_structure();
		
		$start_table = $this->ipsclass->compiled_templates['skin_post']->table_top( "{$this->ipsclass->lang['top_txt_edit']} {$this->topic['title']}");
		
		$name_fields = $this->html_name_field();
		
		$post_box    = $this->html_post_body( $raw_post );
			
		$mod_options = $this->edit_title == 1 ? $this->mod_options('edit') : '';
		
		$end_form    = $this->ipsclass->compiled_templates['skin_post']->EndForm( $this->ipsclass->lang['submit_edit'] );
		
		$post_icons  = $this->html_post_icons($this->orig_post['icon_id']);
		
		$upload_field = $this->can_upload ? $this->html_build_uploads($this->post_key,'edit',$this->orig_post['pid']) : '';
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		$poll_box = "";
		
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Did someone hit preview / do we have
			// post info?
			//-----------------------------------------
			
			$poll_questions = "";
			$poll_choices   = "";
			$poll_votes     = "";
			$show_open      = 0;
			$is_mod         = 0;
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
				
				if ( is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
				{
					foreach( $_POST['choice'] as $id => $choice )
					{
						$poll_choices .= "\t'{$id}' : '".str_replace( "'", '&#39;', $choice )."',\n";
					}
				}
				
				if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
				{
					foreach( $_POST['multi'] as $id => $checked )
					{
						$poll_multi .= "\t{$id} : '{$checked}',\n";
					}
				}
				
				if ( is_array( $_POST['votes'] ) and count( $_POST['votes'] ) )
				{
					foreach( $_POST['votes'] as $id => $vote )
					{
						$poll_votes .= "\t'{$id}' : '".$vote."',\n";
					}
				}
				
				$poll_only = 0;
				
				if( $this->ipsclass->vars['ipb_poll_only'] AND $this->ipsclass->input['poll_only'] == 1 )
				{
					$poll_only = "checked='checked'";
				}
				
				$poll_questions = preg_replace( "#,(\n)?$#", "\\1", $poll_questions );
				$poll_choices   = preg_replace( "#,(\n)?$#", "\\1", $poll_choices );
				$poll_multi 	= preg_replace( "#,(\n)?$#", "\\1", $poll_multi );
				$poll_votes     = preg_replace( "#,(\n)?$#", "\\1", $poll_votes );
				
			}
			else
			{

				//-----------------------------------------
				// Load the poll from the DB
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'polls', 'where' => "tid=".$this->topic['tid'] ) );
				$this->ipsclass->DB->simple_exec();
		
	    		$this->poll_data = $this->ipsclass->DB->fetch_row();
	    		
	    		$this->poll_answers = $this->poll_data['choices'] ? unserialize(stripslashes($this->poll_data['choices'])) : array();

        		//-----------------------------------------
        		// Lezz go
        		//-----------------------------------------
        		
        		foreach( $this->poll_answers as $question_id => $data )
        		{
        			$poll_questions .= "\t{$question_id} : '".str_replace( "'", '&#39;', $data['question'] )."',\n";
        			
        			$data['multi']	 = isset($data['multi']) ? intval($data['multi']) : 0;
        			$poll_multi 	.= "\t{$question_id} : '".$data['multi']."',\n";
        			
        			foreach( $data['choice'] as $choice_id => $text )
					{
						$choice = $text;
						$votes  = intval($data['votes'][ $choice_id ]);
						
						$poll_choices .= "\t'{$question_id}_{$choice_id}' : '".str_replace( "'", '&#39;', $choice )."',\n";
						$poll_votes   .= "\t'{$question_id}_{$choice_id}' : '".$votes."',\n";
					}
				}
				
				$poll_only = 0;
				
				if ( $this->ipsclass->vars['ipb_poll_only'] AND $this->poll_data['poll_only'] == 1 )
				{
					$poll_only = "checked='checked'";
				}				
				
				//-----------------------------------------
				// Trim off trailing commas (Safari hates it)
				//-----------------------------------------
				
				$poll_questions = preg_replace( "#,(\n)?$#", "\\1", $poll_questions );
				$poll_choices   = preg_replace( "#,(\n)?$#", "\\1", $poll_choices );
				$poll_multi 	= preg_replace( "#,(\n)?$#", "\\1", $poll_multi );
				$poll_votes     = preg_replace( "#,(\n)?$#", "\\1", $poll_votes );
				
				$poll_question = $this->poll_data['poll_question'];
				$show_open     = $this->poll_data['choices'] ? 1 : 0;
				$is_mod        = $this->can_add_poll_mod;
			}
			
			//-----------------------------------------
			// Print poll box
			//-----------------------------------------
			
			$poll_box = $this->ipsclass->compiled_templates['skin_post']->poll_box( $this->max_poll_questions, $this->max_poll_choices_per_question, $poll_questions, $poll_choices, $poll_votes, $show_open, $poll_question, $is_mod, $poll_multi, $poll_only );
		}
		
		$edit_option = "";
		
		if ($this->ipsclass->member['g_append_edit'])
		{
			$checked     = "";
			$show_reason = 0;
			
			if ($this->orig_post['append_edit'])
			{
				$checked = "checked";
			}
			
			if ( $this->moderator['edit_post'] OR $this->ipsclass->member['g_is_supmod'] )
			{
				$show_reason = 1;
			}
			
			$edit_option = $this->ipsclass->compiled_templates['skin_post']->add_edit_box( $checked, $show_reason, $this->orig_post['post_edit_reason'] );
		}
		
		$this->output = str_replace( "<!--START TABLE-->" , $start_table  , $this->output );
		$this->output = str_replace( "<!--NAME FIELDS-->" , $name_fields  , $this->output );
		$this->output = str_replace( "<!--POST BOX-->"    , $post_box     , $this->output );
		$this->output = str_replace( "<!--POLL BOX-->"    , $poll_box     , $this->output );
		$this->output = str_replace( "<!--POST ICONS-->"  , $post_icons   , $this->output );
		$this->output = str_replace( "<!--END TABLE-->"   , $end_form     , $this->output );
		$this->output = str_replace( "<!--UPLOAD FIELD-->", $upload_field , $this->output );
		$this->output = str_replace( "<!--MOD OPTIONS-->" , $edit_option . $mod_options , $this->output );
		$this->output = str_replace( "<!--FORUM RULES-->" , $this->ipsclass->print_forum_rules($this->forum), $this->output );
		$this->output = str_replace( "<!--TOPIC TITLE-->" , $topic_title  , $this->output );
		
		//-----------------------------------------
		// Add in siggy buttons and such
		//-----------------------------------------
		
		$this->ipsclass->input['post_htmlstatus'] = $this->orig_post['post_htmlstate'];
		$this->ipsclass->input['enablesig']		  = $this->orig_post['use_sig'];
		$this->ipsclass->input['enableemo']		  = $this->orig_post['use_emo'];
		
		$this->html_checkboxes('edit', $this->topic['tid'], $this->forum['id']);
		
		$this->html_topic_summary( $this->topic['tid'] );
		
		$this->show_post_navigation();
						  
		$this->title = $this->ipsclass->lang['editing_post'].' '.$this->topic['title'];
		
		$this->ipsclass->print->add_output( $this->output );
		
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -> ".$this->title,
        					 	  'JS'       => 1,
        					 	  'NAV'      => $this->nav,
        					  ) );
	}
	

}

?>