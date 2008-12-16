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
|   > Post Class
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 (15:23)
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_post
{
	# Classes
	var $ipsclass;
	var $parser;
	var $email;
	var $han_editor;
	var $class_attach;
	
    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $mem_groups = array();
    var $mem_titles = array();
    var $obj        = array();
    var $times      = array( 'open' => NULL, 'close' => NULL );
    var $root_path  = '';
    
    var $md5_check        = "";
    var $module           = "";
    var $attach_sum       = -1;
    var $is_merging_posts = 0;
    var $cur_post_attach  = array();
    
    # Permissions
	var $can_add_poll				   = 0;
	var $max_poll_questions            = 0;
	var $max_poll_choices_per_question = 0;
	var $can_upload                    = 0;
    var $can_edit_poll				   = 0;
 	var $poll_total_votes			   = 0;
 	var $can_set_close_time            = 0;
 	var $can_set_open_time             = 0;
 	
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function load_classes()
    {
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
        
        //-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->init();
        
        //-----------------------------------------
        // Load the email libby
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/classes/class_email.php" );
		$this->email = new emailer();
        $this->email->ipsclass =& $this->ipsclass;
        $this->email->email_init();
        
        //-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
        $this->ipsclass->load_language('lang_post');
        $this->ipsclass->load_template('skin_post');
        
    }
    
    /*-------------------------------------------------------------------------*/
	// Build permissions
	/*-------------------------------------------------------------------------*/
	
	function convert_open_close_times()
	{
		//-----------------------------------------
		// OPEN...
		//-----------------------------------------
		
		$_POST['open_time_date']  = isset($_POST['open_time_date']) ? $_POST['open_time_date'] : NULL;
		$_POST['open_time_time']  = isset($_POST['open_time_time']) ? $_POST['open_time_time'] : NULL;
		$_POST['close_time_date'] = isset($_POST['close_time_date']) ? $_POST['close_time_date'] : NULL;
		$_POST['close_time_time'] = isset($_POST['close_time_time']) ? $_POST['close_time_time'] : NULL;
		
		if ( $this->can_set_open_time AND $_POST['open_time_date'] AND $_POST['open_time_time'] )
		{
			list( $month, $day, $year ) = explode( "/", $_POST['open_time_date'] );
			list( $hour , $minute     ) = explode( ":", $_POST['open_time_time'] );
			
			if ( checkdate( $month, $day, $year ) )
			{
				$this->times['open'] = $this->ipsclass->convert_local_date_to_unix( array( 'month'  => intval($month),
																						   'day'    => intval($day),
																						   'year'   => intval($year),
																						   'hour'   => intval($hour),
																						   'minute' => intval($minute) ) );
			}
		}
		
		//-----------------------------------------
		// CLOSE...
		//-----------------------------------------
		
		if ( $this->can_set_open_time AND $_POST['close_time_date'] AND $_POST['close_time_time'] )
		{
			list( $month, $day, $year ) = explode( "/", $_POST['close_time_date'] );
			list( $hour , $minute     ) = explode( ":", $_POST['close_time_time'] );
			
			if ( checkdate( $month, $day, $year ) )
			{
				$this->times['close'] = $this->ipsclass->convert_local_date_to_unix( array( 'month'  => intval($month),
																							'day'    => intval($day),
																							'year'   => intval($year),
																							'hour'   => intval($hour),
																							'minute' => intval($minute) ) );
			}
		}
	}
	
    /*-------------------------------------------------------------------------*/
	// Build permissions
	/*-------------------------------------------------------------------------*/
	
	function build_permissions()
	{
		//-----------------------------------------
        // Can we upload files?
        //-----------------------------------------
        
        if ( $this->ipsclass->check_perms($this->forum['upload_perms']) == TRUE )
        {
        	if ( $this->ipsclass->member['g_attach_max'] != -1 )
        	{
        		$this->can_upload = 1;
				$this->obj['form_extra']   = " enctype='multipart/form-data'";
				$this->obj['hidden_field'] = "<input type='hidden' name='MAX_FILE_SIZE' value='".($this->ipsclass->member['g_attach_max']*1024)."' />";
        	}
        }
        
		//-----------------------------------------
		// Allowed poll?
		//-----------------------------------------
		
		$this->can_add_poll                  = intval($this->ipsclass->member['g_post_polls']);
		$this->max_poll_choices_per_question = intval($this->ipsclass->vars['max_poll_choices']);
		$this->max_poll_questions            = intval($this->ipsclass->vars['max_poll_questions']);
		$this->can_edit_poll                 = ( $this->ipsclass->member['g_is_supmod'] ) ? $this->ipsclass->member['g_is_supmod'] : ( isset($this->ipsclass->member['_moderator'][ $this->forum['id'] ]['edit_post']) ? intval( $this->ipsclass->member['_moderator'][ $this->forum['id'] ]['edit_post'] ) : 0 );
		
		if ( ! $this->max_poll_questions )
		{
			$this->can_add_poll = 0;
		}
		
		if ( ! $this->forum['allow_poll'] )
		{
			$this->can_add_poll = 0;
		}
		
		//-----------------------------------------
        // Are we a moderator?
        //-----------------------------------------
        
        if ( $this->ipsclass->member['id'] != 0 and $this->ipsclass->member['g_is_supmod'] == 0 )
        {
        	$this->moderator = isset($this->ipsclass->member['_moderator'][ $this->forum['id'] ]) ? $this->ipsclass->member['_moderator'][ $this->forum['id'] ] : NULL;
        }
		
		//-----------------------------------------
		// Set open and close time
		//-----------------------------------------
		
		$this->can_set_open_time  = ( $this->ipsclass->member['g_is_supmod'] ) ? $this->ipsclass->member['g_is_supmod'] : ( isset($this->ipsclass->member['_moderator'][ $this->forum['id'] ]['mod_can_set_open_time']) ? intval( $this->ipsclass->member['_moderator'][ $this->forum['id'] ]['mod_can_set_open_time'] ) : 0 );
		$this->can_set_close_time = ( $this->ipsclass->member['g_is_supmod'] ) ? $this->ipsclass->member['g_is_supmod'] : ( isset($this->ipsclass->member['_moderator'][ $this->forum['id'] ]['mod_can_set_close_time']) ? intval( $this->ipsclass->member['_moderator'][ $this->forum['id'] ]['mod_can_set_close_time'] ) : 0 );
	}
	
    /*-------------------------------------------------------------------------*/
    // Show post preview
    /*-------------------------------------------------------------------------*/
    
    function show_post_preview( $t="", $post_key='' )
    {
    	$this->parser->parse_html    = (intval($this->ipsclass->input['post_htmlstatus']) AND $this->forum['use_html'] AND $this->ipsclass->member['g_dohtml']) ? 1 : 0;
		$this->parser->parse_nl2br   = $this->ipsclass->input['post_htmlstatus'] == 2 ? 1 : 0;
		$this->parser->parse_smilies = intval($this->ipsclass->input['enableemo']);
		$this->parser->parse_bbcode  = $this->forum['use_ibc'];
		
		# Make sure we have the pre-edit look
		$t = $this->parser->pre_display_parse( $t );
		
		//-----------------------------------------
		// Attachments?
		//-----------------------------------------
		
		$attach_pids = array();
		
		preg_match_all( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", $t, $match );
			
		if ( is_array( $match[0] ) and count( $match[0] ) )
		{
			for ( $i = 0 ; $i < count( $match[0] ) ; $i++ )
			{
				if ( intval($match[1][$i]) == $match[1][$i] )
				{
					$attach_pids[ $match[1][$i] ] = $match[1][$i];
				}
			}
		}
		
		//-----------------------------------------
		// Got any?
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
				$this->class_attach->attach_post_key =  $post_key;
			}
		
			$this->ipsclass->load_template( 'skin_topic' );
			$this->class_attach->type  = 'post';
			$this->class_attach->init();

			$t = $this->class_attach->render_attachments( $t, $attach_pids );			
		}
		
		return $t;
    }
    
    /*-------------------------------------------------------------------------*/
    // Get navigation
    /*-------------------------------------------------------------------------*/
    
    function show_post_navigation()
    {
    	 $this->nav = $this->ipsclass->forums->forums_breadcrumb_nav( $this->forum['id'] );
    	 
    	 if ( isset($this->topic['tid']) AND $this->topic['tid'] )
    	 {
    	 	$this->nav[] = "<a href='{$this->ipsclass->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>";
    	 }
    }
        
    /*-------------------------------------------------------------------------*/
	// Notify new topic mod Q
	/*-------------------------------------------------------------------------*/
	
	function notify_new_topic_approval($tid, $title, $author, $pid=0, $type='new')
	{
		$tmp = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'notify_modq_emails', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );
		
		if ( $tmp['notify_modq_emails'] == "" )
		{ 
			return;
		}
		
		if ( $type == 'new' )
		{
			$this->email->get_template("new_topic_queue_notify");
		}
		else
		{
			$this->email->get_template("new_post_queue_notify");
		}
		
		$this->email->build_message( array(
											'TOPIC'  => $title,
											'FORUM'  => $this->forum['name'],
											'POSTER' => $author,
											'DATE'   => $this->ipsclass->get_date( time(), 'SHORT', 1 ),
											'LINK'   => $this->ipsclass->vars['board_url'].'/index.'.$this->ipsclass->vars['php_ext'].'?act=findpost&pid='.$pid,
										  )
									);
		
		$email_message = $this->email->message;
		
		foreach( explode( ",", $tmp['notify_modq_emails'] ) as $email )
		{
			$this->email->message = $email_message;
			$this->email->to      = trim($email);
			$this->email->send_mail();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// topic tracker
	// ------------------
	// Checks and sends out the emails as needed.
	/*-------------------------------------------------------------------------*/
	
	function topic_tracker($tid="", $post="", $poster="", $last_post="" )
	{
		if ($tid == "")
		{
			return TRUE;
		}
		
		$count = 0;
		
		//-----------------------------------------
		// Get the email addy's, topic ids and email_full stuff - oh yeah.
		// We only return rows that have a member last_activity of greater than the post itself
		// Ergo:
		//  Last topic post: 8:50am
		//  Last topic visit: 9:00am
		//  Next topic reply: 9:10am
		// if ( last.activity > last.topic.post ) { send.... }
		//  Next topic reply: 9:20am
		// if ( last.activity > last.topic.post ) { will fail as 9:10 > 8:50 }
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'post_topic_tracker', array( 'tid' => $tid, 'mid' => $this->ipsclass->member['id'], 'last_post' => $last_post ) );
		
		$outer = $this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows($outer) )
		{
			$trids = array();
			
			while ( $r = $this->ipsclass->DB->fetch_row($outer) )
			{
				//-----------------------------------------
				// Test for group permissions
				//-----------------------------------------
				
				$mgroup_others = "";
				$temp_mgroups  = array();
				$mgroup_perms  = array();
				
				if( $r['mgroup_others'] )
				{
					$r['mgroup_others'] = $this->ipsclass->clean_perm_string( $r['mgroup_others'] );
					$temp_mgroups = explode( ",", $r['mgroup_others'] );
					
					if( count($temp_mgroups) )
					{
						foreach( $temp_mgroups as $other_mgroup )
						{
							$mgroup_perms[] = $this->ipsclass->cache['group_cache'][ $other_mgroup ]['g_perm_id'];
						}
					}
					
					if( count($mgroup_perms) )
					{
						$mgroup_others = ",".implode( ",", $mgroup_perms ).",";
					}
				}
				
				$perm_id = ( $r['org_perm_id'] ) ? $r['org_perm_id'] : $this->ipsclass->cache['group_cache'][ $r['mgroup'] ]['g_perm_id'].$mgroup_others;
				
				if ( $this->forum['read_perms'] != '*' )
				{
					if ( ! preg_match("/(^|,)".str_replace( ",", '|', $perm_id )."(,|$)/", $this->forum['read_perms'] ) )
        			{
        				continue;
       				}
				}
				
				//-----------------------------------------
				// Test for approved/approve perms
				//-----------------------------------------
				
				if( $r['approved'] == 0 )
				{
					$mod = 0;
					
					if( $this->ipsclass->cache['group_cache'][$r['mgroup']]['g_is_supmod'] == 1 )
					{
						$mod = 1;
					}
					else if ( count($this->ipsclass->cache['moderators']) )
					{
						$other_mgroups = array();
						
						if( $this->member['mgroup_others'] )
						{
							$other_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->member['mgroup_others'] ) );
						}
						
						foreach( $this->ipsclass->cache['moderators'] as $moderators )
						{
							if ( $moderators['member_id'] == $r['id'] or $moderators['group_id'] == $r['mgroup'] )
							{
								$mod = 1;
							}
							else if( count($other_mgroups) AND in_array( $moderators['group_id'], $other_mgroups ) )
							{
								$mod = 1;
							}
						}
					}
					
					if( $mod == 0 )
					{
						continue;
					}
				}
				
				// Only send one email until user logs in again...
				// That is, unless they want the full post
				if( $r['last_sent'] > $r['last_activity'] AND !$r['email_full'] )
				{
					continue;
				}
				else
				{
					$this->ipsclass->DB->do_update( "tracker", array( 'last_sent' => time() ), "trid=".$r['trid'] );
				}
					
				
				$count++;
				
				$r['language'] = $r['language'] ? $r['language'] : 'en';
				
				if ($r['email_full'] == 1)
				{
					$this->email->get_template("subs_with_post", $r['language']);
			
					$this->email->build_message( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['members_display_name'],
														'POSTER'          => $poster,
														'POST'            => $post,
													  )
												);
					
				}
				else
				{
				
					$this->email->get_template("subs_no_post", $r['language']);
			
					$this->email->build_message( array(
														'TOPIC_ID'        => $r['topic_id'],
														'FORUM_ID'        => $r['forum_id'],
														'TITLE'           => $r['title'],
														'NAME'            => $r['members_display_name'],
														'POSTER'          => $poster,
													  )
												);
					
				}
				
				$trids[] = $r['trid'];
				
				//-----------------------------------------
				// Add to mail queue
				//-----------------------------------------
				
				$this->ipsclass->DB->do_insert( 'mail_queue', array( 'mail_to' => $r['email'], 'mail_date' => time(), 'mail_subject' => $this->email->lang_subject, 'mail_content' => $this->email->message ) );
			}

			$this->ipsclass->cache['systemvars']['mail_queue'] += $count;
			
			//-----------------------------------------
			// Update cache with remaning email count
			//-----------------------------------------
			
			$this->ipsclass->update_cache( array( 'array' => 1, 'name' => 'systemvars', 'donow' => 1, 'deletefirst' => 0 ) );
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum tracker
	/*-------------------------------------------------------------------------*/
	
	function forum_tracker($fid="", $this_tid="", $title="", $forum_name="", $post="", $mid=0, $mname="")
	{
		if ($this_tid == "")
		{
			return TRUE;
		}
		
		if ($fid == "")
		{
			return TRUE;
		}
		
		$mid 	= $mid > 0 ? $mid : $this->ipsclass->member['id'];
		$mname	= $mname != '' ? $mname : $this->ipsclass->member['members_display_name'];
		
		//-----------------------------------------
		// Work out the time stamp needed to "guess"
		// if the user is still active on the board
		// We will base this guess on a period of
		// non activity of time_now - 30 minutes.
		//-----------------------------------------
		
		$time_limit = time() - (30*60);
		$count      = 0;
		$gotem      = array();
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'tr.frid, tr.last_sent',
												 'from'     => array( 'forum_tracker' => 'tr' ),
												 'where'    => 'tr.forum_id='.$fid." AND ( ( tr.forum_track_type='delayed' AND m.last_activity < {$time_limit} ) OR tr.forum_track_type='immediate' )",
												 'add_join' => array( 0 => array( 'select' => 'm.members_display_name, m.email, m.id, m.language, m.last_activity, m.org_perm_id, m.mgroup_others',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => "tr.member_id=m.id AND m.id <> {$mid}",
																				  'type'   => 'inner' ),
																	  1 => array( 'select' => 'g.g_perm_id',
																				  'from'   => array( 'groups' => 'g' ),
																				  'where'  => "m.mgroup=g.g_id",
																				  'type'   => 'left' )  )
										)      );
		
		$outer = $this->ipsclass->DB->exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row($outer) )
		{
			$this->ipsclass->DB->do_update( "forum_tracker", array( 'last_sent' => time() ), "frid=".$r['frid'] );
			
			$gotem[ $r['id'] ] = $r;
		}
		
		//-----------------------------------------
		// Get "all" groups?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['autoforum_sub_groups'] )
		{
			$this->ipsclass->DB->build_query( array( 'select'   => 'm.name, m.email, m.id, m.language, m.last_activity, m.org_perm_id, m.mgroup_others',
													 'from'     => array( 'members' => 'm' ),
													 'where'    => "m.mgroup IN ({$this->ipsclass->vars['autoforum_sub_groups']})
																	AND m.id <> {$mid}
																	AND m.allow_admin_mails=1
																	AND m.last_activity < {$time_limit}",
													 'add_join' => array( 0 => array( 'select' => 'g.g_perm_id',
																					  'from'   => array( 'groups' => 'g' ),
																					  'where'  => "m.mgroup=g.g_id",
																					  'type'   => 'left' )  )
																		
											)      );
		
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$gotem[ $r['id'] ] = $r;
			}
		}
		
		//-----------------------------------------
		// Row, row and parse, parse
		//-----------------------------------------
		
		if ( count( $gotem ) )
		{
			foreach( $gotem as $mid => $r )
			{
				$count++;

				$mgroup_others = "";
				$temp_mgroups  = array();
				$mgroup_perms  = array();
				
				if( $r['mgroup_others'] )
				{
					$r['mgroup_others'] = $this->ipsclass->clean_perm_string( $r['mgroup_others'] );
					$temp_mgroups = explode( ",", $r['mgroup_others'] );
					
					if( count($temp_mgroups) )
					{
						foreach( $temp_mgroups as $other_mgroup )
						{
							$mgroup_perms[] = $this->ipsclass->cache['group_cache'][ $other_mgroup ]['g_perm_id'];
						}
					}
					
					if( count($mgroup_perms) )
					{
						$mgroup_others = ",".implode( ",", $mgroup_perms ).",";
					}
				}
				
				$perm_id = ( $r['org_perm_id'] ) ? $r['org_perm_id'] : $r['g_perm_id'].$mgroup_others;

				//$perm_id = ( $r['org_perm_id'] ) ? $r['org_perm_id'] : $r['g_perm_id'];
				
				// INIT
				$permissions 	= array();
				$tmp_perms 		= array();
				$forum_perms 	= array();
				
				$tmp_perms = explode( ",", $perm_id );

				if( is_array($tmp_perms) )
				{
					foreach( $tmp_perms as $v )
					{
						if( $v != "" )
						{
							$permissions[] = $v;
						}
					}
					
					unset($tmp_perms);
				}
				else
				{
					$permissions[] = $perm_id;
				}

				if ($this->forum['read_perms'] != '*')
				{
					$pass = 0;
					
					$forum_perms = explode( ",", $this->forum['read_perms'] ); 
					
					foreach( $permissions as $v )
					{
						if( in_array( $v, $forum_perms ) )
						{
							$pass = 1;
						}
					}
					
					if ( $pass == 0 )
        			{
        				continue;
       				}
				}
				
				unset($permissions);
				unset($forum_perms);
        
				$r['language'] = $r['language'] ? $r['language'] : 'en';
				
				$this->email->get_template("subs_new_topic", $r['language']);
		
				$this->email->build_message( array(
													'TOPIC_ID'        => $this_tid,
													'FORUM_ID'        => $fid,
													'TITLE'           => $title,
													'NAME'            => $r['members_display_name'],
													'POSTER'          => $mname,
													'FORUM'           => $forum_name,
													'POST'            => $post,
												  )
											);
				
				$this->ipsclass->DB->do_insert( 'mail_queue', array( 'mail_to' => $r['email'], 'mail_date' => time(), 'mail_subject' => $this->email->lang_subject, 'mail_content' => $this->email->message ) );
			}
		}
		
		$this->ipsclass->cache['systemvars']['mail_queue'] += $count;
			
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		$this->ipsclass->update_cache( array( 'array' => 1, 'name' => 'systemvars', 'donow' => 1, 'deletefirst' => 0 ) );
		
		return TRUE;
	}
    
    /*-------------------------------------------------------------------------*/
    // Compile poll
    /*-------------------------------------------------------------------------*/
    
    function compile_poll()
    {
    	//-----------------------------------------
		// Check poll
		//-----------------------------------------
		
		$questions     = array();
		$choices_count = 0;
		$is_mod        = $this->ipsclass->member['g_is_supmod'] ? $this->ipsclass->member['g_is_supmod'] : ( isset($this->moderator['edit_topic']) ? intval($this->moderator['edit_topic']) : 0);
		
		if ( $this->can_add_poll )
		{
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				$has_poll = 1;
				
				foreach( $_POST['question'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['question'] = $this->parser->bad_words( $this->ipsclass->parse_clean_value( $q ) );
				}
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['multi'] = intval($q);
				}
			}			
			
			//-----------------------------------------
			// Choices...
			//-----------------------------------------
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $mainid => $choice )
				{
					list( $question_id, $choice_id ) = explode( "_", $mainid );
					
					$question_id = intval( $question_id );
					$choice_id   = intval( $choice_id );
					
					if ( ! $question_id OR ! isset($choice_id) )
					{
						continue;
					}
					
					if ( ! $questions[ $question_id ]['question'] )
					{
						continue;
					}
					
					$questions[ $question_id ]['choice'][ $choice_id ] = $this->parser->bad_words( $this->ipsclass->parse_clean_value( $choice ) );
					
					if ( ! $is_mod )
					{
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval($this->poll_answers[ $question_id ]['votes'][ $choice_id ]);
					}
					else
					{
						$_POST['votes'] = isset($_POST['votes']) ? $_POST['votes'] : 0;
						
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval( $_POST['votes'][ $question_id.'_'.$choice_id ] );
					}
					
					$this->poll_total_votes += $questions[ $question_id ]['votes'][ $choice_id ];
				}
			}
			
			//-----------------------------------------
			// Make sure we have choices for each
			//-----------------------------------------
			
			foreach( $questions as $id => $data )
			{
				if ( ! is_array( $data['choice'] ) OR ! count( $data['choice'] ) )
				{
					unset( $questions[ $id ] );
				}
				else
				{
					$choices_count += intval( count( $data['choice'] ) );
				}
			}
			
			//-----------------------------------------
			// Error check...
			//-----------------------------------------
			
			if ( count( $questions ) > $this->max_poll_questions )
			{
				$this->obj['post_errors'] = 'poll_to_many';
			}
			
			if ( count( $choices_count ) > ( $this->max_poll_questions * $this->max_poll_choices_per_question ) )
			{
				$this->obj['post_errors'] = 'poll_to_many';
			}
		}
		
		return $questions;
    }
    
	/*-------------------------------------------------------------------------*/
	// compile post
	// ------------------
	// Compiles all the incoming information into an array
	// which is returned to the accessor
	/*-------------------------------------------------------------------------*/
	
	function compile_post()
	{
		$this->ipsclass->vars['max_post_length'] = $this->ipsclass->vars['max_post_length'] ? $this->ipsclass->vars['max_post_length'] : 2140000;
		
		//-----------------------------------------
		// Sort out some of the form data, check for posting length, etc.
		// THIS MUST BE CALLED BEFORE CHECKING ATTACHMENTS
		//-----------------------------------------
		
		$this->ipsclass->input['enablesig']   = (isset($this->ipsclass->input['enablesig']) AND $this->ipsclass->input['enablesig'])   == 'yes' ? 1 : 0;
		$this->ipsclass->input['enableemo']   = (isset($this->ipsclass->input['enableemo']) AND $this->ipsclass->input['enableemo'])   == 'yes' ? 1 : 0;
		$this->ipsclass->input['enabletrack'] = (isset($this->ipsclass->input['enabletrack']) AND intval($this->ipsclass->input['enabletrack']) != 0) ? 1 : 0;
		
		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if ( strlen( trim( $this->ipsclass->my_br2nl( $_POST['Post'] ) ) ) < 1 )
		{
			if ( ! $_POST['preview'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_post') );
			}
		}
		
		if ( strlen( $_POST['Post'] ) > ( $this->ipsclass->vars['max_post_length'] * 1024 ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'post_too_long') );
		}
		
		//-----------------------------------------
		// Remove board tags
		//-----------------------------------------
		
		$this->ipsclass->input['Post'] = $this->ipsclass->remove_tags( $this->ipsclass->input['Post'] );
		
		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
		
		if ( isset($_POST['fast_reply_used']) AND $_POST['fast_reply_used'] )
		{
			if ( $this->han_editor->method == 'rte' && $this->ipsclass->can_use_fancy_js )
			{
				//-----------------------------------------
				// Fast reply used.. and we've chosen the RTE
				// Convert STD to RTE first...
				//-----------------------------------------
				
				$_POST['Post'] = $this->parser->convert_std_to_rte( $_POST['Post'] );
			}
		}
		
		$this->ipsclass->input['Post'] = $this->han_editor->process_raw_post( 'Post' );

		//-----------------------------------------
		// Parse post
		//-----------------------------------------
		
		$this->parser->parse_smilies 	= $this->ipsclass->input['enableemo'];
		$this->parser->parse_html    	= (intval($this->ipsclass->input['post_htmlstatus']) AND $this->forum['use_html'] AND $this->ipsclass->member['g_dohtml']) ? 1 : 0;
		$this->parser->parse_nl2br   	= intval($this->ipsclass->input['post_htmlstatus']) == 2 ? 1 : 0;
		$this->parser->parse_bbcode  	= $this->forum['use_ibc'];
		$this->parser->bypass_badwords 	= intval($this->ipsclass->member['g_bypass_badwords']);
		
		$post = array(
						'author_id'   => $this->ipsclass->member['id'] ? $this->ipsclass->member['id'] : 0,
						'use_sig'     => $this->ipsclass->input['enablesig'],
						'use_emo'     => $this->ipsclass->input['enableemo'],
						'ip_address'  => $this->ipsclass->ip_address,
						'post_date'   => time(),
						'icon_id'     => isset($this->ipsclass->input['iconid']) ? $this->ipsclass->input['iconid'] : 0,
						'post'        => $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->ipsclass->input['Post'] ) ),
						'author_name' => $this->ipsclass->member['id'] ? $this->ipsclass->member['members_display_name'] : $this->ipsclass->input['UserName'],
						'topic_id'    => "",
						'queued'      => ( isset($this->obj['moderate']) AND ( $this->obj['moderate'] == 1 || $this->obj['moderate'] == 3 ) ) ? 1 : 0,
						'post_htmlstate' => isset($this->ipsclass->input['post_htmlstatus']) ? intval($this->ipsclass->input['post_htmlstatus']) : 0,
					 );

		//-----------------------------------------
	    // If we had any errors, parse them back to this class
	    // so we can track them later.
	    //-----------------------------------------
	    
	    $this->obj['post_errors'] = $this->parser->error;
					 
		return $post;
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: mod_options.
	// ------------------
	// Returns the HTML for the mod options drop down box
	/*-------------------------------------------------------------------------*/
	
	function mod_options($type='new')
	{
		$can_close = 0;
		$can_pin   = 0;
		$can_move  = 0;
		$html      = "";
		$mytimes   = array();
		
		$this->ipsclass->input['mod_options'] = isset($this->ipsclass->input['mod_options']) ? $this->ipsclass->input['mod_options'] : '';
		
		//-----------------------------------------
		// Mod options
		//-----------------------------------------
		
		if ( $type != 'edit' )
		{
			if ( $this->ipsclass->member['g_is_supmod'] )
			{
				$can_close = 1;
				$can_pin   = 1;
				$can_move  = 1;
			}
			else if ( $this->ipsclass->member['id'] != 0 )
			{
				if ( $this->moderator['mid'] != "" )
				{
					if ($this->moderator['close_topic'])
					{
						$can_close = 1;
					}
					if ($this->moderator['pin_topic'])
					{
						$can_pin   = 1;
					}
					if ($this->moderator['move_topic'])
					{
						$can_move  = 1;
					}
				}
			}
			else
			{
				// Guest
				return "";
			}
			
			if ( !($can_pin == 0 and $can_close == 0 and $can_move == 0) )
			{
				$selected = ($this->ipsclass->input['mod_options'] == 'nowt') ? " selected='selected'" : '';
				
				$html = "<select id='forminput' name='mod_options' class='forminput'>\n<option value='nowt'{$selected}>".$this->ipsclass->lang['mod_nowt']."</option>\n";
			}
			
			if ($can_pin)
			{
				$selected = ($this->ipsclass->input['mod_options'] == 'pin') ? " selected='selected'" : '';
				
				$html .= "<option value='pin'{$selected}>".$this->ipsclass->lang['mod_pin']."</option>";
			}
			if ($can_close)
			{
				$selected = ($this->ipsclass->input['mod_options'] == 'close') ? " selected='selected'" : '';
				
				$html .= "<option value='close'{$selected}>".$this->ipsclass->lang['mod_close']."</option>";
			}
			
			if ($can_close and $can_pin)
			{
				$selected = ($this->ipsclass->input['mod_options'] == 'pinclose') ? " selected='selected'" : '';
				
				$html .= "<option value='pinclose'{$selected}>".$this->ipsclass->lang['mod_pinclose']."</option>";
			}
			
			if ($can_move and $type != 'new' )
			{
				$selected = ($this->ipsclass->input['mod_options'] == 'move') ? " selected='selected'" : '';
				
				$html .= "<option value='move'{$selected}>".$this->ipsclass->lang['mod_move']."</option>";
			}
		}
		
		//-----------------------------------------
		// If we're replying, kill off time boxes
		//-----------------------------------------
		
		if ( $type == 'reply' )
		{
			$this->can_set_open_time  = 0;
			$this->can_set_close_time = 0;
		}
		else
		{
			//-----------------------------------------
			// Check dates...
			//-----------------------------------------
			
			$mytimes['open_time']  = isset($_POST['open_time_time'])  ? $_POST['open_time_time']  : '';
			$mytimes['open_date']  = isset($_POST['open_time_date'])  ? $_POST['open_time_date']  : '';
			$mytimes['close_time'] = isset($_POST['close_time_time']) ? $_POST['close_time_time'] : '';
			$mytimes['close_date'] = isset($_POST['close_time_date']) ? $_POST['close_time_date'] : '';
			
			if ( !isset($open_date) OR !$open_date )
			{
				if ( isset($this->topic['topic_open_time']) AND $this->topic['topic_open_time'] )
				{
					$date                 = $this->ipsclass->unixstamp_to_human($this->topic['topic_open_time']);
					$mytimes['open_date'] = sprintf("%02d/%02d/%04d", $date['month'], $date['day'], $date['year'] );
					$mytimes['open_time'] = sprintf("%02d:%02d"     , $date['hour'] , $date['minute'] );
				}
			}
			
			if ( !isset($close_date) OR !$close_date )
			{
				if ( isset($this->topic['topic_close_time']) AND $this->topic['topic_close_time'] )
				{
					$date                  = $this->ipsclass->unixstamp_to_human($this->topic['topic_close_time']);
					$mytimes['close_date'] = sprintf("%02d/%02d/%04d", $date['month'], $date['day'], $date['year'] );
					$mytimes['close_time'] = sprintf("%02d:%02d"     , $date['hour'] , $date['minute'] );
				}
			}
		}
		
		return $this->ipsclass->compiled_templates['skin_post']->mod_options($html, $this->can_set_open_time, $this->can_set_close_time, $mytimes);
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: start form.
	// ------------------
	// Returns the HTML for the <FORM> opening tag
	/*-------------------------------------------------------------------------*/
	
	function html_start_form($additional_tags=array())
	{
		$this->obj['hidden_field'] 	= isset($this->obj['hidden_field']) ? $this->obj['hidden_field'] : '';
		$this->obj['form_extra']	= isset($this->obj['form_extra'])	? $this->obj['form_extra']	 : '';
		
		$form = "<form id='postingform' action='{$this->ipsclass->base_url}' method='post' name='REPLIER' onsubmit='return ValidateForm()'".$this->obj['form_extra'].">".
				"<input type='hidden' name='st' value='".$this->ipsclass->input['st']."' />\n".
				"<input type='hidden' name='act' value='Post' />\n".
				"<input type='hidden' name='s' value='".$this->ipsclass->session_id."' />\n".
				"<input type='hidden' name='f' value='".$this->forum['id']."' />\n".
				"<input type='hidden' name='auth_key' value='".$this->md5_check."' />\n".
				"<input type='hidden' name='removeattachid' value='0' />\n".
				$this->obj['hidden_field'];
				
		// Any other tags to add?
		
		if (isset($additional_tags))
		{
			foreach($additional_tags as $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}
		
		return $form;
    }
		
	/*-------------------------------------------------------------------------*/
	// HTML: name fields.
	// ------------------
	// Returns the HTML for either text inputs or membername
	// depending if the member is a guest.
	/*-------------------------------------------------------------------------*/
	
	function html_name_field()
	{
		$html = "";
		$html =  $this->ipsclass->member['id'] ? $this->ipsclass->compiled_templates['skin_post']->nameField_reg() : $this->ipsclass->compiled_templates['skin_post']->nameField_unreg( isset($this->ipsclass->input['UserName']) ? $this->ipsclass->input['UserName'] : '' );
		
		if( $this->ipsclass->member['id'] == 0 AND $this->ipsclass->vars['guest_captcha'] )
		{
			$html = str_replace( "<!--GUEST.CAPTCHA-->", $this->ipsclass->compiled_templates['skin_post']->guest_captcha(), $html );
			
			$imgid = md5( uniqid( microtime() ) );

			if( $this->ipsclass->vars['bot_antispam'] == 'gd' )
			{
				//-----------------------------------------
				// Get 6 random chars
				//-----------------------------------------
								
				$img_code = strtoupper( substr( md5( mt_rand() ), 0, 6 ) );
			}
			else
			{
				//-----------------------------------------
				// Set a new 6 character numerical string
				//-----------------------------------------
				
				$img_code = mt_rand(100000,999999);
			}			
			
			$this->ipsclass->DB->do_insert( 'reg_antispam', array (
												   'regid'      => $imgid,
												   'regcode'    => $img_code,
												   'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
												   'ctime'      => time(),
									   )       );
									   
			if( $this->ipsclass->vars['guest_captcha'] == 'gd' )
			{
				$html = str_replace( "<!--CAPTCHA.IMAGE-->", $this->ipsclass->compiled_templates['skin_post']->bot_antispam_gd( $imgid ), $html );
			}
			else if ( $this->ipsclass->vars['guest_captcha'] == 'gif' )
			{
				$html = str_replace( "<!--CAPTCHA.IMAGE-->", $this->ipsclass->compiled_templates['skin_post']->bot_antispam_gif( $imgid ), $html );
			}
		}
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Post body.
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*-------------------------------------------------------------------------*/
	
	function html_post_body($raw_post="")
	{
		$this->ipsclass->lang['the_max_length'] = $this->ipsclass->vars['max_post_length'] * 1024;
		
		return $this->ipsclass->compiled_templates['skin_post']->postbox_wrap( $this->han_editor->show_editor( $raw_post, 'Post' ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Post Icons
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*-------------------------------------------------------------------------*/
	
	function html_post_icons($post_icon="")
	{
		$post_icon = ( $post_icon ) ? $post_icon : intval($this->ipsclass->input['iconid']);
		
		$html = $this->ipsclass->compiled_templates['skin_post']->PostIcons();
		
		if ( isset( $post_icon ) )
		{
			$html = preg_replace( "/name=[\"']iconid[\"']\s*value=[\"']0[\"']\s*checked=['\"]checked['\"]/i"  , "name='iconid' value='0'", $html );
			$html = preg_replace( "/name=[\"']iconid[\"']\s*value=[\"']".$post_icon."\s?[\"']/", "name='iconid' value='{$post_icon}' checked='checked'", $html );
		}
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: checkboxes
	// ------------------
	// Returns the HTML for sig/emo/track boxes
	/*-------------------------------------------------------------------------*/
	
	function html_checkboxes($type="", $tid="", $fid="") 
	{
		$default_checked = array(
								  'sig' => 'checked="checked"',
						  		  'emo' => 'checked="checked"',
						  		  'tra' => $this->ipsclass->member['auto_track'] ? 'checked="checked"' : ''
						        );
						        
		
		// Make sure we're not previewing them and they've been unchecked!
		
		if ( isset( $this->ipsclass->input['enablesig'] ) AND ( ! $this->ipsclass->input['enablesig'] ) )
		{
			$default_checked['sig'] = "";
		}
		
		if ( isset( $this->ipsclass->input['enableemo'] ) AND ( ! $this->ipsclass->input['enableemo'] ) )
		{
			$default_checked['emo'] = "";
		}
		
		if ( isset( $this->ipsclass->input['enabletrack'] ) AND ( ! $this->ipsclass->input['enabletrack'] ) )
		{
			$default_checked['tra'] = "";
		}
		else if ( isset( $this->ipsclass->input['enabletrack'] ) AND ( $this->ipsclass->input['enabletrack'] == 1 ) )
		{
			$default_checked['tra'] = 'checked="checked"';
		}
		
		$this->output = str_replace( '<!--IBF.EMO-->'  , $this->ipsclass->compiled_templates['skin_post']->get_box_enableemo( $default_checked['emo'] )  , $this->output );
		
		if ( $this->ipsclass->member['id'] )
		{
			$this->output = str_replace( '<!--IBF.SIG-->'  , $this->ipsclass->compiled_templates['skin_post']->get_box_enablesig( $default_checked['sig'] )  , $this->output );
		}
		
		if ( $this->ipsclass->cache['forum_cache'][$fid]['use_html'] and $this->ipsclass->cache['group_cache'][ $this->ipsclass->member['mgroup'] ]['g_dohtml'] )
		{
			$this->ipsclass->input['post_htmlstatus'] = isset($this->ipsclass->input['post_htmlstatus']) ? intval($this->ipsclass->input['post_htmlstatus']) : 0;
			
			$options_array = array( 0 => '', 1 => '', 2 => '' );
			$options_array[ $this->ipsclass->input['post_htmlstatus'] ] = ' selected="selected"';
			
			$this->output = str_replace( '<!--IBF.HTML-->' , $this->ipsclass->compiled_templates['skin_post']->get_box_html( $options_array ), $this->output );
		}
		
		if ( $type == 'reply' )
		{
			if ( $tid and $this->ipsclass->member['id'] )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'trid', 'from' => 'tracker', 'where' => "topic_id={$tid} AND member_id=".$this->ipsclass->member['id'] ) );
				$this->ipsclass->DB->simple_exec();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->output = str_replace( '<!--IBF.TRACK-->',$this->ipsclass->compiled_templates['skin_post']->get_box_alreadytrack(), $this->output );
				}
				else
				{
					$this->output = str_replace( '<!--IBF.TRACK-->', $this->ipsclass->compiled_templates['skin_post']->get_box_enabletrack( $default_checked['tra'] ), $this->output );
				}
			}
		}
		else if ( $type != 'edit' )
		{
			if ( $this->ipsclass->member['id'] )
			{
				$this->output = str_replace( '<!--IBF.TRACK-->', $this->ipsclass->compiled_templates['skin_post']->get_box_enabletrack( $default_checked['tra'] ), $this->output );
			}
		}
	}
	
    /*-------------------------------------------------------------------------*/
	// HTML: add smilie box.
	// ------------------
	// Inserts the clickable smilies box
	/*-------------------------------------------------------------------------*/
	
	function html_add_smilie_box($in_html="")
	{
		return $in_html;
		
		/*
		$show_table = 0;
		$count      = 0;
		$smilies    = "<tr align='center'>\n";
		$smilie_id  = 0;
		$total = 0;
		
		//-----------------------------------------
		// Get the smilies from the DB
		//-----------------------------------------
		
		if ( ! is_array( $this->ipsclass->cache['emoticons'] ) )
		{
			$this->ipsclass->cache['emoticons'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['emoticons'][] = $r;
			}
		}
		
		usort( $this->ipsclass->cache['emoticons'] , array( 'class_post', 'smilie_alpha_sort' ) );
		
		foreach( $this->ipsclass->cache['emoticons'] as $clickable )
		{
			if ( $clickable['emo_set'] != $this->ipsclass->skin['_emodir'] )
			{
				continue;
			}
						
			if( $clickable['clickable'] )
			{
				$total++;
			}
		}
		
		foreach( $this->ipsclass->cache['emoticons'] as $elmo )
		{
			if ( $elmo['emo_set'] != $this->ipsclass->skin['_emodir'] )
			{
				continue;
			}
			
			if ( ! $elmo['clickable'] )
			{
				continue;
			}
			
			$show_table++;
			$count++;
			$smilie_id++;
			
			
			//-----------------------------------------
			// Make single quotes as URL's with html entites in them
			// are parsed by the browser, so ' causes JS error :o
			//-----------------------------------------
			
			if (strstr( $elmo['typed'], "&#39;" ) )
			{
				$in_delim  = '"';
				$out_delim = "'";
			}
			else
			{
				$in_delim  = "'";
				$out_delim = '"';
			}
			
			$smilies .= "<td><a href={$out_delim}javascript:emoticon($in_delim".$elmo['typed']."$in_delim, {$in_delim}smid_$smilie_id{$in_delim}){$out_delim}><img id='smid_$smilie_id' src=\"".$this->ipsclass->vars['EMOTICONS_URL']."/".$elmo['image']."\" alt=$in_delim".$elmo['typed']."$in_delim border='0' /></a></td>\n";
			
			if ($count == $this->ipsclass->vars['emo_per_row'])
			{
				$smilies .= "</tr>\n\n";

				if( $smilie_id < $total )
				{
					$count = 0;
					$smilies.= "<tr align='center'>";
				}
			}
		}
		
		//-----------------------------------------
		// Write 'em
		//-----------------------------------------
		
		if ( $count != $this->ipsclass->vars['emo_per_row'] AND $count != 0 )
		{
			for ($i = $count ; $i < $this->ipsclass->vars['emo_per_row'] ; ++$i)
			{
				$smilies .= "<td>&nbsp;</td>\n";
			}
			$smilies .= "</tr>";
		}
		
		$table = $this->ipsclass->compiled_templates['skin_post']->smilie_table();
		
		if ($show_table != 0)
		{
			$table   = str_replace( "<!--THE SMILIES-->", $smilies, $table );
			$in_html = str_replace( "<!--SMILIE TABLE-->", $table, $in_html );
		}
		
		return $in_html;*/
	}
		
	/*-------------------------------------------------------------------------*/
	// HTML: topic summary.
	// ------------------
	// displays the last 10 replies to the topic we're
	// replying in.
	/*-------------------------------------------------------------------------*/
	
	function html_topic_summary($topic_id)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cached_members = array();
		$attach_pids	= array();
		$content        = "";
		
		//-----------------------------------------
		// CHECK
		//-----------------------------------------
		
		if (! $topic_id ) return;
		
		if( $this->ipsclass->vars['disable_summary'] )
		{
			return;
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_post']->TopicSummary_top();
		
		//-----------------------------------------
		// Get the posts
		// This section will probably change at some point
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'post_get_topic_review', array( 'tid' => $topic_id ) );
							 
		$post_query = $this->ipsclass->DB->cache_exec_query();
		
		while ( $row = $this->ipsclass->DB->fetch_row($post_query) )
		{
		    $row['author'] = $row['members_display_name'] ? $row['members_display_name'] : $row['author_name'];
			
			$row['date']   = $this->ipsclass->get_date( $row['post_date'], 'LONG' );
			
			if ( ! $this->ipsclass->member['view_img'])
			{
				// unconvert smilies first, or it looks a bit crap.
				
				$row['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $row['post'] );
				
				$row['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>)", $row['post'] );
			}
			
			//-----------------------------------------
			// Are we giving this bloke a good ignoring?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['ignored_users'] )
			{
				if ( strstr( $this->ipsclass->member['ignored_users'], ','.$row['author_id'].',' ) and $this->ipsclass->input['qpid'] != $row['pid'] )
				{
					if ( ! strstr( $this->ipsclass->vars['cannot_ignore_groups'], ','.$row['mgroup'].',' ) )
					{
						$content .= $this->ipsclass->compiled_templates['skin_post']->TopicSummary_body_hidden( $row );
						continue;
					}
				}
			}			
			
			$content .= $this->ipsclass->compiled_templates['skin_post']->TopicSummary_body( $row );
			
			$attach_pids[] = $row['pid'];
		}
		
		//-----------------------------------------
		// Got any attachments?
		//-----------------------------------------
		
		if ( count( $attach_pids ) )
		{
			//-----------------------------------------
			// Get topiclib
			//-----------------------------------------
			
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
		
			$content = $this->class_attach->render_attachments( $content, $attach_pids );
		}	
		
		$this->output .= $content . $this->ipsclass->compiled_templates['skin_post']->TopicSummary_bottom();
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Get used space so far
	//
	/*-------------------------------------------------------------------------*/
	
	function _get_attachment_sum__DEPRECATED()
	{
		if ( $this->attach_sum == -1 )
		{
			$stats = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'sum(attach_filesize) as sum',
																	'from'   => 'attachments',
																	'where'  => 'attach_member_id='.$this->ipsclass->member['id'] ) );
												    
			$this->attach_sum = intval( $stats['sum'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Build Upload Area - yay
	/*-------------------------------------------------------------------------*/
	
	function html_build_uploads( $post_key="", $type="", $pid="")
	{
		$upload_field = $this->ipsclass->compiled_templates['skin_post']->Upload_field( $post_key, 'post', $pid, $this->forum['id'] );
		
		return $upload_field;
		
		$this->_get_attachment_sum();
		
		if ( $this->ipsclass->member['g_attach_max'] > 0 )
		{
			$size = intval( ( $this->ipsclass->member['g_attach_max'] * 1024 ) - $this->attach_sum );
			$size = $size < 0 ? 0 : $size;
			$main_space_left = $this->ipsclass->size_format( $size );
		}
		else
		{
			$main_space_left = $this->ipsclass->lang['upload_unlimited'];
		}
												
		if ( $post_key != "" )
		{
			//-----------------------------------------
			// Check for current uploads based on temp
			// key
			//-----------------------------------------
			
			if ( ! is_array( $this->cur_post_attach ) or ! count( $this->cur_post_attach ) )
			{
				$this->ipsclass->DB->simple_construct( array( "select" => '*', 'from' => 'attachments', 'where' => "attach_post_key='$post_key'") );
				$this->ipsclass->DB->simple_exec();
				
				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$this->cur_post_attach[] = $r;
				}
			}
			
			if ( is_array( $this->cur_post_attach ) and count( $this->cur_post_attach ) )
			{ 
				$upload_tmp  = $this->ipsclass->compiled_templates['skin_post']->uploadbox_tabletop();
				$upload_size = 0;
				
				foreach( $this->cur_post_attach as $row )
				{
					$upload_size += $row['attach_filesize'];
					$row['image'] = $this->ipsclass->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'];
					$row['size']  = $this->ipsclass->size_format( $row['attach_filesize'] );
					
					if ( strlen( $row['attach_file'] ) > 40 )
					{
						$row['attach_file'] = substr( $row['attach_file'], 0, 35 ) .'...';
					}
					
					$upload_tmp .= $this->ipsclass->compiled_templates['skin_post']->uploadbox_entry($row);
				}
				
				$space_used  = $this->ipsclass->size_format( intval( $upload_size ) );
				
				if ( $this->ipsclass->member['g_attach_max'] > 0 )
				{
					if ( $this->ipsclass->member['g_attach_per_post'] )
					{
						//-----------------------------------------
						// Max + per post: show per post
						//-----------------------------------------
						
						// If you don't have enough space globally, let's take that into account
						if( $size < $upload_size )
						{
							$space_left = $main_space_left;
						}
						else
						{
							$space_left = $this->ipsclass->size_format( intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $upload_size ) );
						}
					}
					else
					{
						//-----------------------------------------
						// Max + no per post: Show max
						//-----------------------------------------
						
						$space_left = $this->ipsclass->size_format( intval( ( $this->ipsclass->member['g_attach_max'] * 1024 ) - $upload_size - $this->attach_sum ) );
					}
				}
				else
				{ 
					if ( $this->ipsclass->member['g_attach_per_post'] )
					{
						//-----------------------------------------
						// No Max + per post: show per post
						//-----------------------------------------
						
						$space_left = $this->ipsclass->size_format( intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $upload_size ) );
					}
					else
					{
						//-----------------------------------------
						// No Max + no per post: Show unlimited
						//-----------------------------------------
						
						$space_left = $this->ipsclass->lang['upload_unlimited'];
					}
				}
				
				$upload_text = sprintf( $this->ipsclass->lang['attach_space_left'], $space_used, $space_left );
				
				$upload_tmp .= $this->ipsclass->compiled_templates['skin_post']->uploadbox_tableend( $upload_text );
			}
		}
		
		if ( isset($upload_tmp) AND $upload_tmp )
		{
			$upload_field = str_replace( '<!--IBF.UPLOADED_ITEMS-->', $upload_tmp, $upload_field );
		}
		
		return $upload_field;
	}
	
	/*-------------------------------------------------------------------------*/
	// Moderators log
	// ------------------
	// Simply adds the last action to the mod logs
	/*-------------------------------------------------------------------------*/
	
	function moderate_log($title = 'unknown', $topic_title)
	{
		$this->ipsclass->DB->do_insert( 'moderator_logs', array (
												'forum_id'    => $this->ipsclass->input['f'],
												'topic_id'    => $this->ipsclass->input['t'],
												'post_id'     => $this->ipsclass->input['p'],
												'member_id'   => $this->ipsclass->member['id'],
												'member_name' => $this->ipsclass->member['members_display_name'],
												'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												'http_referer'=> htmlspecialchars($this->ipsclass->my_getenv('HTTP_REFERER')),
												'ctime'       => time(),
												'topic_title' => $topic_title,
												'action'      => substr( $title, 0, 255 ),
												'query_string'=> htmlspecialchars($this->ipsclass->my_getenv('QUERY_STRING')),
										     ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// perform: Check for new topic
	/*-------------------------------------------------------------------------*/
	
	function check_for_new_topic( $topic=array() )
	{
		if (! $this->ipsclass->member['g_post_new_topics'])
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_starting' ) );
		}
		
		if ( $this->ipsclass->check_perms($this->forum['start_perms']) == FALSE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_starting' ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// perform: Check for reply
	/*-------------------------------------------------------------------------*/
	
	function check_for_reply( $topic=array() )
	{
		if ($topic['poll_state'] == 'closed' and $this->ipsclass->member['g_is_supadmin'] != 1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_replies') );
		}
		
		if ($topic['starter_id'] == $this->ipsclass->member['id'])
		{
			if (! $this->ipsclass->member['g_reply_own_topics'])
			{
				$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_replies') );
			}
		}
		
		if ($topic['starter_id'] != $this->ipsclass->member['id'])
		{
			if (! $this->ipsclass->member['g_reply_other_topics'])
			{
				$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_replies') );
			}
		}

		if ( $this->ipsclass->check_perms($this->forum['reply_perms']) == FALSE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_replies') );
		}
		
		// Is the topic locked?
		
		if ($topic['state'] != 'open')
		{
			if ($this->ipsclass->member['g_post_closed'] != 1)
			{
				$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'locked_topic') );
			}
		}
		
		if( isset($topic['poll_only']) AND $topic['poll_only'] )
		{
			if ($this->ipsclass->member['g_post_closed'] != 1)
			{
				$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_poll_reply') );
			}
		}			
	}
	
	/*-------------------------------------------------------------------------*/
	// perform: Check for edit
	/*-------------------------------------------------------------------------*/
	
	function check_for_edit( $topic=array() )
	{
		//-----------------------------------------
		// Is the topic locked?
		//-----------------------------------------
		
		if ( ( $topic['state'] != 'open' ) and ( ! $this->ipsclass->member['g_is_supmod'] ) )
		{
			if ( $this->ipsclass->member['g_post_closed'] != 1 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'locked_topic' ) );
			}
		}
		
		//-----------------------------------------
		// Return OK if we're an admin or mod
		//-----------------------------------------
		
		if ( $this->ipsclass->member['g_is_supmod'] OR $this->moderator['edit_post'] )
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// Continue.
		//-----------------------------------------
		
		if ( $this->ipsclass->check_perms( $this->forum['reply_perms'] ) == FALSE )
		{
			$_ok = 0;
			
			//-----------------------------------------
			// Are we a member who started this topic
			// and are editing the topic's first post?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['id'] )
			{
				if ( $topic['topic_firstpost'] )
				{
					$_post = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'pid, author_id, topic_id',
																			   'from'   => 'posts',
																			   'where'  => 'pid=' . intval( $topic['topic_firstpost'] ) ) );
																			
					if ( $_post['pid'] AND $_post['topic_id'] == $topic['tid'] AND $_post['author_id'] == $this->ipsclass->member['id'] )
					{
						$_ok = 1;
					}
				}
			}
			
			if ( ! $_ok )
			{
				$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'not_op') );
			}
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// process upload
	// ------------------
	// checks for an entry in the upload field, and uploads
	// the file if it meets our criteria. This also inserts
	// a new row into the attachments database if successful
	/*-------------------------------------------------------------------------*/
	
	function __process_upload_DEPRECATED()
	{
		//-----------------------------------------
		// Got attachment types?
		//-----------------------------------------
		
		if ( ! is_array( $this->ipsclass->cache['attachtypes'] ) )
		{
			$this->ipsclass->cache['attachtypes'] = array();
				
			$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
			}
		}
		
		$this->ipsclass->input['post_key'] = isset($this->ipsclass->input['post_key']) ? $this->ipsclass->input['post_key'] : 0;
		
		//-----------------------------------------
		// Set up array
		//-----------------------------------------
		
		$attach_data = array( 
							  'attach_ext'            => "",
							  'attach_file'           => "",
							  'attach_location'       => "",
							  'attach_thumb_location' => "",
							  'attach_hits'           => 0,
							  'attach_date'           => time(),
							  'attach_temp'           => 0,
							  'attach_pid'            => 0,
							  'attach_post_key'       => $this->ipsclass->input['post_key'],
							  'attach_member_id'      => $this->ipsclass->member['id'],
							  'attach_filesize'       => 0,
							);
		
		if ( ($this->can_upload != 1) or ($this->ipsclass->member['g_attach_max'] == -1 ) )
		{
			return $attach_data;
		}
		
		//-----------------------------------------
		// Space left...
		//-----------------------------------------
		
		$this->_get_attachment_sum();
		
		$this->cur_post_attach = array();
		$this->per_post_count  = 0;
	
		if ( $this->ipsclass->input['post_key'] )
		{
			$this->ipsclass->DB->simple_construct( array( "select" => '*', 'from' => 'attachments', 'where' => "attach_post_key='{$this->ipsclass->input['post_key']}'") );
			$this->ipsclass->DB->simple_exec();
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->per_post_count   += $r['attach_filesize'];
				$this->cur_post_attach[] = $r;
			}
		}
		
		if ( $this->ipsclass->member['g_attach_max'] > 0 )
		{
			if ( $this->ipsclass->member['g_attach_per_post'] )
			{
				$main_space_left   = intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $this->per_post_count );
				$main_space_left_g = intval( ( $this->ipsclass->member['g_attach_max'] * 1024 ) - $this->attach_sum );
				
				if ( $main_space_left_g < $main_space_left )
				{
					$main_space_left = $main_space_left_g;
				}
			}
			else
			{
				$main_space_left = intval( ( $this->ipsclass->member['g_attach_max'] * 1024 ) - $this->attach_sum );
			}
		}
		else
		{
			if ( $this->ipsclass->member['g_attach_per_post'] )
			{
				$main_space_left = intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $this->per_post_count );
			}
			else
			{
				$main_space_left = 1000000000;
			}
		}
					
		//-----------------------------------------
		// Load the library
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_upload.php' );
		$upload = new class_upload();
		
		//-----------------------------------------
		// Set up the variables
		//-----------------------------------------
		
		$upload->out_file_name    = 'post-'.$this->ipsclass->member['id'].'-'.time();
		$upload->out_file_dir     = $this->ipsclass->vars['upload_dir'];
		$upload->max_file_size    = $main_space_left;
		$upload->make_script_safe = 1;
		$upload->force_data_ext   = 'ipb';
		
		//-----------------------------------------
		// Populate allowed extensions
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['attachtypes'] ) and count( $this->ipsclass->cache['attachtypes'] ) )
		{
			foreach( $this->ipsclass->cache['attachtypes'] as $data )
			{
				if ( $data['atype_post'] )
				{
					$upload->allowed_file_ext[] = $data['atype_extension'];
				}
			}
		}
		
		//-----------------------------------------
		// Upload...
		//-----------------------------------------
		
		$upload->upload_process();
		
		//-----------------------------------------
		// Error?
		//-----------------------------------------
		
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{
				case 1:
					// No upload
					return $attach_data;
				case 2:
					// Invalid file ext
					$this->obj['post_errors'] = 'invalid_mime_type';
					return $attach_data;
				case 3:
					// Too big...
					$this->obj['post_errors'] = 'upload_to_big';
					return $attach_data;
				case 4:
					// Cannot move uploaded file
					$this->obj['post_errors'] = 'upload_failed';
					return $attach_data;
				case 5:
					// Possible XSS attack (image isn't an image)
					$this->obj['post_errors'] = 'upload_failed';
					return $attach_data;
			}
		}
					
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		if ( $upload->saved_upload_name and @file_exists( $upload->saved_upload_name ) )
		{
			$attach_data['attach_filesize']   = @filesize( $upload->saved_upload_name  );
			$attach_data['attach_location']   = $upload->parsed_file_name;
			$attach_data['attach_file']       = $upload->original_file_name;
			$attach_data['attach_is_image']   = $upload->is_image;
			$attach_data['attach_ext']        = $upload->real_file_extension;
			
			if ( $attach_data['attach_is_image'] == 1 )
			{
				$thumb_data = $this->create_thumbnail( $attach_data );
				
				if ( $thumb_data['thumb_location'] )
				{
					$attach_data['attach_thumb_width']    = $thumb_data['thumb_width'];
					$attach_data['attach_thumb_height']   = $thumb_data['thumb_height'];
					$attach_data['attach_thumb_location'] = $thumb_data['thumb_location'];
				}
			}
			
			$this->ipsclass->DB->do_insert( 'attachments', $attach_data );
			
			$newid = $this->ipsclass->DB->get_insert_id();
			
			$attach_data['attach_id'] = $newid;
			
			$this->per_post_count    += $attach_data['attach_filesize'];
			$this->cur_post_attach[]  = $attach_data;
			
			return $newid;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Create thumbnail
	/*-------------------------------------------------------------------------*/
	
	function create_thumbnail( $data )
	{
		//-----------------------------------------
		// Load class
		//-----------------------------------------
		
		$return = array();
		
		require_once( KERNEL_PATH.'class_image.php' );
		$image = new class_image();
		
		$image->in_type        = 'file';
		$image->out_type       = 'file';
		$image->in_file_dir    = $this->ipsclass->vars['upload_dir'];
		$image->in_file_name   = $data['attach_location'];
		$image->desired_width  = $this->ipsclass->vars['siu_width'];
		$image->desired_height = $this->ipsclass->vars['siu_height'];
		$image->gd_version     = $this->ipsclass->vars['gd_version'];
		
		if ( $this->ipsclass->vars['siu_thumb'] )
		{
			$return = $image->generate_thumbnail();
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Increment user's post
	// ------------------
	// if +1 post, +1 member's cumulative
	/*-------------------------------------------------------------------------*/
	
	function pf_increment_user_post_count()
	{
		$pcount = "";
		$mgroup = "";
		
		if ($this->ipsclass->member['id'])
		{
			if ($this->forum['inc_postcount'])
			{
				// Increment the users post count
				
				$pcount = "posts=posts+1, ";
			
				// Are we checking for auto promotion?
				
				if ($this->ipsclass->member['g_promotion'] != '-1&-1')
				{
					list($gid, $gposts) = explode( '&', $this->ipsclass->member['g_promotion'] );
					
					if ( $gid > 0 and $gposts > 0 )
					{
						if ( $this->ipsclass->member['posts'] + 1 >= $gposts )
						{
							$mgroup = "mgroup='$gid', ";
							
							if ( USE_MODULES == 1 )
							{
								$this->modules->register_class($this);
								$this->modules->on_group_change($this->ipsclass->member['id'], $gid);
							}
						}
					}
				}
			}
			
			$this->ipsclass->member['last_post'] = time();
			
			$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
														  'set'    => $pcount.$mgroup." last_post=".intval($this->ipsclass->member['last_post']),
														  'where'  => 'id='.$this->ipsclass->member['id']
												 )      );
								 
			$this->ipsclass->DB->simple_exec();
		}	
	}
	
	/*-------------------------------------------------------------------------*/
	// Update forum's last information
	// ------------------
	// ^^ proper chrimbo!
	/*-------------------------------------------------------------------------*/
	
	function pf_update_forum_and_stats($tid, $title, $type='new')
	{
		$moderated = 0;
		
		//-----------------------------------------
		// Moderated?
		//-----------------------------------------
		
		$moderate = 0;
		
		if ( $this->obj['moderate'] )
		{
			if ( $type == 'new' and ( $this->obj['moderate'] == 1 or $this->obj['moderate'] == 2 ) )
			{
				$moderate = 1;
			}
			else if ( $type == 'reply' and ( $this->obj['moderate'] == 1 or $this->obj['moderate'] == 3 ) )
			{
				$moderate = 1;
			}
		}
		
		//-----------------------------------------
		// Add to forum's last post?
		//-----------------------------------------
		
		if ( ! $moderate )
		{
			$dbs = array( 'last_title'       => $title,
						  'last_id'          => $tid,
						  'last_post'        => time(),
						  'last_poster_name' => $this->ipsclass->member['id'] ?  $this->ipsclass->member['members_display_name'] : $this->ipsclass->input['UserName'],
						  'last_poster_id'   => $this->ipsclass->member['id'],
					   );
		
			if ( $type == 'new' )
			{
				$this->ipsclass->cache['stats']['total_topics']++;
				
				$this->forum['topics'] = intval($this->forum['topics']);
				$dbs['topics']         = ++$this->forum['topics'];
				
				$dbs['newest_id']	   = $tid;
				$dbs['newest_title']   = $title;
			}
			else
			{
				$this->ipsclass->cache['stats']['total_replies']++;
				
				$this->forum['posts'] = intval($this->forum['posts']);
				$dbs['posts']         = ++$this->forum['posts'];
			}
		}
		else
		{
			if ( $type == 'new' )
			{
				$this->forum['queued_topics'] = intval($this->forum['queued_topics']);
				$dbs['queued_topics']         = ++$this->forum['queued_topics'];
			}
			else
			{
				$this->forum['queued_posts'] = intval($this->forum['queued_posts']);
				$dbs['queued_posts']         = ++$this->forum['queued_posts'];
			}
		}
		
		//-----------------------------------------
		// Merging posts?
		// Don't update counter
		//-----------------------------------------
		
		if ( $this->is_merging_posts )
		{
			unset($dbs['posts']);
			unset($dbs['queued_posts']);
			
			$this->ipsclass->cache['stats']['total_replies'] -= 1;
		}
		
		//-----------------------------------------
		// Update
		//-----------------------------------------
		
		if( is_array($dbs) AND count($dbs) )
		{
			$this->ipsclass->DB->force_data_type = array( 'last_poster_name' => 'string',
														  'last_title'		 => 'string' );
	
			$this->ipsclass->DB->do_update( 'forums', $dbs, "id=".intval($this->forum['id']) );
		}
		
		//-----------------------------------------
		// Update forum cache
		//-----------------------------------------
		
		$this->ipsclass->update_forum_cache();
		
		$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 0, 'donow' => 0 ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment
	// ------------------
	// ^^ proper new year!
	/*-------------------------------------------------------------------------*/
	
	function __pf_remove_attachment_DEPRECATED($aid, $post_key)
	{
		$this->ipsclass->DB->simple_construct( array( "select" => '*', 'from' => 'attachments',  'where' => "attach_post_key='$post_key' AND attach_id=$aid") );
		$o = $this->ipsclass->DB->simple_exec();
		
		if ( $killmeh = $this->ipsclass->DB->fetch_row( $o ) )
		{
			if ( $killmeh['attach_location'] )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_location'] );
			}
			if ( $killmeh['attach_thumb_location'] )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
			}
			
			$this->ipsclass->DB->simple_construct( array( 'delete' => 'attachments', 'where' => "attach_id={$killmeh['attach_id']}" ) );
			$this->ipsclass->DB->simple_exec();
			
			//-----------------------------------------
			// Remove from post
			//-----------------------------------------
			
			$_POST['Post'] = str_replace( '[attachmentID='.$aid.']', '', $_POST['Post'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert temp uploads into permanent ones! YAY
	// ------------------
	// ^^ proper chinese new year!
	/*-------------------------------------------------------------------------*/
	
	function pf_make_attachments_permanent( $post_key="", $rel_id="", $rel_module="", $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Attachments: Re-affirm...
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
		$class_attach                  =  new class_attach();
		$class_attach->ipsclass        =& $this->ipsclass;
		$class_attach->type            =  $rel_module;
		$class_attach->attach_post_key =  $post_key;
		$class_attach->attach_rel_id   =  $rel_id;
		$class_attach->init();
		
		$return = $class_attach->post_process_upload( $args );
		
		return intval( $return['count'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Recount how many attachments a topic has
	// ------------------
	//
	/*-------------------------------------------------------------------------*/
	
	function pf_recount_topic_attachments($tid="")
	{
		if ( $tid == "" )
		{
			return;
		}
		
		//-----------------------------------------
		// GET PIDS
		//-----------------------------------------
		
		$pids  = array();
		$count = 0;
		
		/* This code causes baaaaddd things to happen when you have, say, 20000 posts in a topic
		
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pid',
													  'from'   => 'posts',
													  'where'  => "topic_id=$tid" ) );
		$this->ipsclass->DB->simple_exec();
				
		while ( $p = $this->ipsclass->DB->fetch_row() )
		{
			$pids[] = $p['pid'];
		}
		
		//-----------------------------------------
		// GET ATTACHMENT COUNT
		//-----------------------------------------
		
		if ( count($pids) )
		{
			$this->ipsclass->DB->simple_construct( array( "select" => 'count(*) as cnt',
														  'from'   => 'attachments',
														  'where'  => "attach_rel_module='post' AND attach_rel_id IN(".implode(",",$pids).")") );
			$this->ipsclass->DB->simple_exec();
			
			$cnt = $this->ipsclass->DB->fetch_row();
			
			$count = intval( $cnt['cnt'] );
		}*/
		
		$this->ipsclass->DB->build_query( array( 'select' 	=> 'count(*) as cnt',
												 'from'		=> array( 'attachments' => 'a' ),
												 'where'	=> "p.topic_id={$tid}",
												 'add_join'	=> array(
																		0 => array(
																					'from'	=> array( 'posts' => 'p' ),
																					'where' => "p.pid=a.attach_rel_id",
																					'type'	=> 'left'
																				)
																		)
										)		);
		$this->ipsclass->DB->exec_query();
		
		$cnt = $this->ipsclass->DB->fetch_row();
		
		$count = intval( $cnt['cnt'] );
		
		$this->ipsclass->DB->simple_construct( array( 'update' => 'topics', 'set' => "topic_hasattach=$count", 'where' => "tid={$tid}" ) );
		$this->ipsclass->DB->simple_exec();
	}
	
	/*-------------------------------------------------------------------------*/
	// Check out the tracker whacker
	// ------------------
	// ^^ proper er... May Day!
	/*-------------------------------------------------------------------------*/
	
	function pf_add_tracked_topic($tid="",$check_first=0)
	{
		if ( ! $tid )
		{
			return;
		}
		
		if ( $this->ipsclass->member['id'] AND $this->ipsclass->input['enabletrack'] == 1 )
		{
			if ( $check_first )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'trid', 'from' => 'tracker', 'where' => "topic_id=".intval($tid)." AND member_id=".$this->ipsclass->member['id'] ) );
				$this->ipsclass->DB->simple_exec();
				
				$test = $this->ipsclass->DB->fetch_row();
				
				if ( $test['trid'] )
				{
					//-----------------------------------------
					// Already tracking...
					//-----------------------------------------
					
					return;
				}
			}
				
			$this->ipsclass->DB->do_insert( 'tracker', array (
											  'member_id'        => $this->ipsclass->member['id'],
											  'topic_id'         => $tid,
											  'start_date'       => time(),
											  'topic_track_type' => $this->ipsclass->member['auto_track'] ? $this->ipsclass->member['auto_track'] : 'delayed' ,
									)       );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean topic title
	// ------------------
	// ^^ proper er... um
	/*-------------------------------------------------------------------------*/
	
	function pf_clean_topic_title($title="")
	{
		if ($this->ipsclass->vars['etfilter_punct'])
		{
			$title	= preg_replace( "/\?{1,}/"      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}
		
		if ($this->ipsclass->vars['etfilter_shout'])
		{
			$title = ucwords(strtolower($title));
		}
		
		return $title;
	}
	
	/*-------------------------------------------------------------------------*/
	// QUOTIN' DA' POSTAY IN DO HoooD'
	/*-------------------------------------------------------------------------*/
	
	function check_multi_quote()
	{
		$raw_post = '';
		
		if ( ! $this->ipsclass->input['qpid'] )
		{
			$this->ipsclass->input['qpid'] = $this->ipsclass->my_getcookie('mqtids');
			
			if ($this->ipsclass->input['qpid'] == ",")
			{
				$this->ipsclass->input['qpid'] = "";
			}
		}
		else
		{
			//-----------------------------------------
			// Came from reply button
			//-----------------------------------------
			
			$this->ipsclass->input['parent_id'] = $this->ipsclass->input['qpid'];
		}
		
		$this->ipsclass->input['qpid'] = preg_replace( "/[^,\d]/", "", trim($this->ipsclass->input['qpid']) );
		
		if ( $this->ipsclass->input['qpid'] )
		{
			$this->quoted_pids = preg_split( '/,/', $this->ipsclass->input['qpid'], -1, PREG_SPLIT_NO_EMPTY );
			
			//-----------------------------------------
			// Get the posts from the DB and ensure we have
			// suitable read permissions to quote them
			//-----------------------------------------
			
			if ( count($this->quoted_pids) )
			{
				$this->ipsclass->DB->cache_add_query( 'post_get_quoted', array( 'quoted_pids' => $this->quoted_pids ) );
				$this->ipsclass->DB->cache_exec_query();
				
				while ( $tp = $this->ipsclass->DB->fetch_row() )
				{
					if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $tp['forum_id'] ]['read_perms']) == TRUE )
					{
						if ( $this->han_editor->method == 'rte' && $this->ipsclass->can_use_fancy_js )
						{
							$tmp_post = $this->parser->convert_ipb_html_to_html(  $tp['post'] );
						}
						else
						{
							$tmp_post = trim( $this->parser->pre_edit_parse( $tp['post'] ) );
						}

						if ( $this->ipsclass->vars['strip_quotes'] )
						{
							$tmp_post = trim($this->_recursive_kill_quotes( $tmp_post ) );
							
							//$tmp_post = preg_replace( "#(?:\n|\r|\r\n){3,}#s", "\n", trim($tmp_post) );
						}
						
						$extra = "";
						
						if ( $tmp_post )
						{
							if ( $this->han_editor->method == 'rte' && $this->ipsclass->can_use_fancy_js )
							{
								$raw_post .= "[quote name='".$this->parser->make_quote_safe($tp['author_name'])."' date='".$this->parser->make_quote_safe($this->ipsclass->get_date( $tp['post_date'], 'LONG', 1 ))."' post='".$tp['pid']."']<br />{$tmp_post}<br />".$extra.'[/quote]'."<br /><br /><br />";
							}
							else
							{
								$raw_post .= "[quote name='".$this->parser->make_quote_safe($tp['author_name'])."' date='".$this->parser->make_quote_safe($this->ipsclass->get_date( $tp['post_date'], 'LONG', 1 ))."' post='".$tp['pid']."']\n$tmp_post\n".$extra.'[/quote]'."\n\n\n";
							}
						}
					}
				}
				
				$raw_post = trim($raw_post)."\n";
			}
		}
		
		//-----------------------------------------
		// Make raw POST safe for the text area
		//-----------------------------------------
		
		$raw_post .= $this->ipsclass->txt_raw2form( isset($_POST['Post']) ? $_POST['Post'] : '' );
		
		return $raw_post;
	}
	
	/**
	* Cheap and probably nasty way of killing quotes
	*
	*/
	function _recursive_kill_quotes( $t )
	{
		if ( preg_match( "#\[QUOTE([^\]]+?)?\](.+?)\[/QUOTE\]#is", $t ) )
		{
			$t = preg_replace( "#\[QUOTE([^\]]+?)?\](.+?)\[/QUOTE\]#is", "", $t );
			$t = $this->_recursive_kill_quotes( $t );
		}
		
		# Remove any extra closing quote tags
		return preg_replace( "#\[/quote\]#si", "", $t );
	}
	
	
	function smilie_alpha_sort($a, $b)
	{
		return strcmp( $a['typed'], $b['typed'] );
	}
}

?>