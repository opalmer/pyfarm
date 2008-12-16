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
|   > $Date: 2007-05-02 17:29:12 -0400 (Wed, 02 May 2007) $
|   > $Revision: 959 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Topic display module
|   > Module written by Matt Mecham
|   > Date started: 18th February 2002
|
|	> Module Version Number: 1.1.0
|   > DBA Checked: Fri 21 May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class topic_display
{
	# Global
	var $ipsclass;
	
    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    var $moderator = array();
    var $forum     = array();
    var $topic     = array();
    var $mod_action = array();
    var $poll_html  = "";
    var $mimetypes  = "";
    var $nav_extra  = "";
    var $read_array = array();
    var $mod_panel_html = "";
    var $warn_range = 0;
    var $warn_done  = 0;
    var $pfields    = array();
    var $pfields_dd = array();
    var $md5_check  = "";
    var $post_count  = 0;
    var $cached_members = array();
    var $first_printed  = 0;
    var $pids           = array();
    
    /*-------------------------------------------------------------------------*/
	// Register class
	/*-------------------------------------------------------------------------*/
	
	function register_class(&$class)
	{
		$this->lib = &$class;
		
		$this->topic = $this->lib->topic;
        $this->forum = $this->lib->forum;
    }
    
    /*-------------------------------------------------------------------------*/
	//
	// Our constructor, load words, load skin, print the topic listing
	//
	/*-------------------------------------------------------------------------*/
	
    function auto_run()
    {
        require_once( ROOT_PATH.'sources/action_public/topics.php' );
        
        $this->lib = new topics();
        $this->lib->ipsclass =& $this->ipsclass;
        
        $this->lib->init();
        $this->lib->topic_set_up();
        
        $this->topic = &$this->lib->topic;
        $this->forum = &$this->lib->forum;
        
        //-----------------------------------------
        // Checky checky
        //-----------------------------------------
        
        if ( ! $this->topic['topic_firstpost'] )
        {
        	$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->topic['tid'].'&amp;mode=standard');
        }
        
        //-----------------------------------------
		// Print it
		//-----------------------------------------
		
		$this->output = str_replace( "<!--IBF.MOD_PANEL-->", $this->lib->moderation_panel(), $this->output );
		
		// Enable quick reply box?
		
		if (   ( $this->topic['quick_reply'] == 1 )
		   and ( $this->ipsclass->check_perms( $this->topic['reply_perms']) == TRUE )
		   and ( $this->topic['state'] != 'closed' ) )
		{
			$show = "none";
			
			$sqr = $this->ipsclass->my_getcookie("open_qr");
			
			if ( $sqr == 1 )
			{
				$show = "show";
			}
			$this->output = str_replace( "<!--IBF.QUICK_REPLY_CLOSED-->", $this->ipsclass->compiled_templates['skin_topic']->quick_reply_box_closed(), $this->output );
			$this->output = str_replace( "<!--IBF.QUICK_REPLY_OPEN-->"  , $this->ipsclass->compiled_templates['skin_topic']->quick_reply_box_open($this->topic['forum_id'], $this->topic['tid'], $show, $this->md5_check), $this->output );
		}
		
		$this->output = str_replace( "<!--IBF.TOPIC_OPTIONS_CLOSED-->", $this->ipsclass->compiled_templates['skin_topic']->topic_opts_closed(), $this->output );
		$this->output = str_replace( "<!--IBF.TOPIC_OPTIONS_OPEN-->"  , $this->ipsclass->compiled_templates['skin_topic']->topic_opts_open($this->topic['forum_id'], $this->topic['tid']), $this->output );
		
		$this->topic['id'] = $this->topic['forum_id'];
		
		$this->output = str_replace( "<!--IBF.FORUM_RULES-->", $this->ipsclass->print_forum_rules($this->topic), $this->output );
		
		//-----------------------------------------
		// Topic multi-moderation - yay!
		//-----------------------------------------
		
		$this->output = str_replace( "<!--IBF.MULTIMOD-->", $this->lib->multi_moderation(), $this->output );
		
		// Pass it to our print routine
		
		$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -> {$this->topic['title']}",
        					 	  'JS'       => 1,
        					 	  'NAV'      => $this->lib->nav,
        				 )      );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Show the damned topic batman
	//
	/*-------------------------------------------------------------------------*/
	
    function display_topic()
    {
		//-----------------------------------------
		// Grab the posts we'll need
		//-----------------------------------------
		
		$first = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		$query_type = 'topics_get_posts';
		
		if ( $this->ipsclass->vars['post_order_column'] != 'post_date' )
		{
			$this->ipsclass->vars['post_order_column'] = 'pid';
		}
		
		if ( $this->ipsclass->vars['post_order_sort'] != 'desc' )
		{
			$this->ipsclass->vars['post_order_sort'] = 'asc';
		}
		
		if ($this->ipsclass->vars['au_cutoff'] == "")
		{
			$this->ipsclass->vars['au_cutoff'] = 15;
		}
		
		if ( $this->ipsclass->vars['custom_profile_topic'] == 1 )
		{
			$query_type = 'topics_get_posts_with_join';
		}
		
		//-----------------------------------------
		// Moderator?
		//-----------------------------------------
		
		$queued_query_bit = ' and queued=0';
			
		if ( $this->ipsclass->can_queue_posts($this->topic['forum_id']) )
		{
			$queued_query_bit = '';
			
			if ( isset($this->ipsclass->input['modfilter']) AND  $this->ipsclass->input['modfilter'] == 'invisible_posts' )
			{
				$queued_query_bit = ' and queued=1';
			}
		}
			
		//-----------------------------------------
		// Using "new" mode?
		//-----------------------------------------
		
		if ( $this->lib->topic_view_mode == 'linearplus' and $this->topic['topic_firstpost'] )
		{
			$this->topic['new_mode_start'] = $first + 1;
			
			if ( $first )
			{
				$this->topic['new_mode_start']--;
			}
			
			if ( $first + $this->ipsclass->vars['display_max_posts'] > ( $this->topic['posts'] + 1 ) )
			{
				$this->topic['new_mode_end'] = $this->topic['posts'];
			}
			else
			{
				$this->topic['new_mode_end'] = $first + ($this->ipsclass->vars['display_max_posts'] - 1);
			}
		
			if ( $first )
			{
				$this->pids = array( 0 => $this->topic['topic_firstpost'] );
			}
			
			//-----------------------------------------
			// Get PIDS of this page/topic
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array (
														   'select' => 'pid,topic_id',
														   'from'   => 'posts',
														   'where'  => 'topic_id='.$this->topic['tid']. $queued_query_bit,
														   'order'  => 'pid',
														   'limit'  => array( $first, $this->ipsclass->vars['display_max_posts'] )
												)        );
								
			$this->ipsclass->DB->simple_exec();
			
			while( $p = $this->ipsclass->DB->fetch_row() )
			{
				$this->pids[] = $p['pid'];
			}
		}
		else
		{
			//-----------------------------------------
			// Run query
			//-----------------------------------------

			$this->lib->topic_view_mode = 'linear';
			
			# We don't need * but if we don't use it, it won't use the correct index
			$this->ipsclass->DB->simple_construct( array (
														   'select' => 'pid,topic_id',
														   'from'   => 'posts',
														   'where'  => 'topic_id='.$this->topic['tid']. $queued_query_bit,
														   'order'  => $this->ipsclass->vars['post_order_column'].' '.$this->ipsclass->vars['post_order_sort'],
														   'limit'  => array( $first, $this->ipsclass->vars['display_max_posts'] )
												)        );
								
			$this->ipsclass->DB->simple_exec();
			
			while( $p = $this->ipsclass->DB->fetch_row() )
			{
				$this->pids[] = $p['pid'];
			}
		}
		
		//-----------------------------------------
		// Do we have any PIDS?
		//-----------------------------------------
		
		if ( ! count( $this->pids ) )
		{
			if ( $first )
			{
				//-----------------------------------------
				// Add dummy PID, AUTO FIX
				// will catch this below...
				//-----------------------------------------
				
				$this->pids[] = 0;
			}
			
			if ( $this->ipsclass->input['modfilter'] == 'invisible_posts' )
			{
				$this->pids[] = 0;
			}
		}
		
		//-----------------------------------------
		// Attachment PIDS
		//-----------------------------------------
		
		$this->lib->attach_pids = $this->pids;
		
		//-----------------------------------------
		// Fail safe
		//-----------------------------------------
		
		if ( ! is_array( $this->pids ) or ! count( $this->pids ) )
		{
			$this->pids = array( 0 => 0 );
		}
		
		//-----------------------------------------
		// Get posts
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( $query_type, array( 'pids' => $this->pids, 'scol' => $this->ipsclass->vars['post_order_column'], 'sord' => $this->ipsclass->vars['post_order_sort'] ) );
										 					 
		$oq = $this->ipsclass->DB->simple_exec();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			if ($first >= $this->ipsclass->vars['display_max_posts'])
			{
				//-----------------------------------------
				// AUTO FIX: Get the correct number of replies...
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array(
															'select' => 'COUNT(*) as pcount',
															'from'   => 'posts',
															'where'  => "topic_id=".$this->topic['tid']." and queued !=1"
													)      );
								 
				$newq   = $this->ipsclass->DB->simple_exec();
				
				$pcount = $this->ipsclass->DB->fetch_row($newq);
				
				$pcount['pcount'] = $pcount['pcount'] > 0 ? $pcount['pcount'] - 1 : 0;
				
				//-----------------------------------------
				// Update the post table...
				//-----------------------------------------
				
				if ($pcount['pcount'] > 1)
				{
					$this->ipsclass->DB->simple_construct( array(
																 'update' => 'topics',
																 'set'    => "posts=".$pcount['pcount'],
																 'where'  => "tid=".$this->topic['tid']
														 )      );
								 
					$this->ipsclass->DB->simple_exec();
					
				}
				
				$this->ipsclass->boink_it($this->ipsclass->base_url."showtopic={$this->topic['tid']}&view=getlastpost");
			}
		}
		
		//-----------------------------------------
		// Render the page top
		//-----------------------------------------
		
		$this->topic['go_new'] = isset($this->topic['go_new']) ? $this->topic['go_new'] : '';
		
		if ( $this->lib->topic_view_mode == 'linearplus' and $this->topic['posts'] > 0 )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_topic']->topic_page_top( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ), 1 );
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_topic']->topic_page_top( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ), 0 );
		}
		
		//-----------------------------------------
		// Format and print out the topic list
		//-----------------------------------------
		
		while ( $row = $this->ipsclass->DB->fetch_row( $oq ) )
		{
			$return = $this->lib->parse_row( $row );
			
			$poster = $return['poster'];
			$row    = $return['row'];
			
			//-----------------------------------------
			// Print post row
			//-----------------------------------------
			
			$this->output .= $this->ipsclass->compiled_templates['skin_topic']->RenderRow( $row, $poster );
			
			//-----------------------------------------
			// Are we giving this bloke a good ignoring?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['ignored_users'] )
			{
				if ( strstr( $this->ipsclass->member['ignored_users'], ','.$poster['id'].',' ) and $this->ipsclass->input['p'] != $row['pid'] )
				{
					if ( ! strstr( $this->ipsclass->vars['cannot_ignore_groups'], ','.$poster['mgroup'].',' ) )
					{
						$this->output .= $this->ipsclass->compiled_templates['skin_topic']->render_row_hidden( $row, $poster );
						continue;
					}
				}
			}
			
			//-----------------------------------------
			// Show end first post
			//-----------------------------------------
			
			if ( $this->lib->topic_view_mode == 'linearplus' and $this->first_printed == 0 and $row['pid'] == $this->topic['topic_firstpost'] and $this->topic['posts'] > 0)
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_topic']->topic_end_first_post( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ) );
			}
		}
		
		//-----------------------------------------
		// Print the footer
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_topic']->TableFooter( array( 'TOPIC' => $this->topic, 'FORUM' => $this->forum ), 0, $this->ipsclass->return_md5_check() );
	}
	
}

?>