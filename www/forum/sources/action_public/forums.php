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
|   > Forum topic index module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class forums {

	# Global
	var $ipsclass;
	
    var $output       = "";
    var $base_url     = "";
    var $html         = "";
    var $moderator    = array();
    var $forum        = array();
    var $mods         = array();
    var $show_dots    = "";
    var $nav_extra    = "";
    var $read_array   = array();
    var $board_html   = "";
    var $sub_output   = "";
    var $pinned_print = 0;
    var $new_posts    = 0;
    var $is_mod       = 0;
    var $auth_key     = 0;
    var $announce_out = "";
    var $pinned_topic_count = 0;
    var $forum_has_unread_topics = 0;
    var $db_row;
    
    # Permission
    var $can_edit_topics  = 0;
    var $can_close_topics = 0;
    var $can_open_topics  = 0;
    
    # Update...
    var $update_topics_close = array();
    var $update_topics_open  = array();
    
    
    /*-------------------------------------------------------------------------*/
	// Init functions
	/*-------------------------------------------------------------------------*/
	
	function init( $do_not_skin_load=0 )
	{
		$this->ipsclass->load_language('lang_forum');
		
		if ( ! $do_not_skin_load )
		{
        	$this->ipsclass->load_template('skin_forum');
        }

        $this->auth_key = $this->ipsclass->return_md5_check();
        
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
        
        //-----------------------------------------
		// Multi TIDS?
		// If st is not defined then kill cookie
		// st will always be defined across pages
		//-----------------------------------------
		
		if ( ! isset( $this->ipsclass->input['st'] ) )
		{
			$this->ipsclass->my_setcookie('modtids', ',', 0);
			$this->ipsclass->input['selectedtids'] = "";
		}
		else
		{
			$this->ipsclass->input['selectedtids'] = $this->ipsclass->my_getcookie('modtids');
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Build permissions
	/*-------------------------------------------------------------------------*/
    
    function build_permissions()
    {
		if ( $this->ipsclass->member['g_is_supmod'] )
		{
			$this->can_edit_topics  = 1;
			$this->can_close_topics = 1;
			$this->can_open_topics  = 1;
		}
		else if ( isset($this->ipsclass->member['_moderator'][ $this->forum['id'] ]) AND is_array( $this->ipsclass->member['_moderator'][ $this->forum['id'] ] ) )
		{
			if ( $this->ipsclass->member['_moderator'][ $this->forum['id'] ]['edit_topic'] )
			{
				$this->can_edit_topics = 1;
			}
			
			if ( $this->ipsclass->member['_moderator'][ $this->forum['id'] ]['close_topic'] )
			{
				$this->can_close_topics = 1;
			}
			
			if ( $this->ipsclass->member['_moderator'][ $this->forum['id'] ]['open_topic'] )
			{
				$this->can_open_topics  = 1;
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Our constructor, load words, load skin, get DB forum/cat data
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
        //-----------------------------------------
        // Are we doing anything with "site jump?"
        //-----------------------------------------
        
        switch( $this->ipsclass->input['f'] )
        {
        	case 'sj_home':
        		$this->ipsclass->boink_it($this->ipsclass->base_url."act=idx");
        		break;
        	case 'sj_search':
        		$this->ipsclass->boink_it($this->ipsclass->base_url."act=Search");
        		break;
        	case 'sj_help':
        		$this->ipsclass->boink_it($this->ipsclass->base_url."act=Help");
        		break;
        	default:
        		$this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
        		break;
        }
        
        $this->init();
        
        //-----------------------------------------
        // Get the forum info based on the forum ID,
        // and get the category name, ID, etc.
        //-----------------------------------------
        
        $this->forum = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]; 
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if ( ! $this->forum['id'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
        }
        
        //-----------------------------------------
		// Build permissions
		//-----------------------------------------
		
		$this->build_permissions();
		
        //-----------------------------------------
        // Is it a redirect forum?
        //-----------------------------------------
        
        if ( isset($this->forum['redirect_on']) AND $this->forum['redirect_on'] )
        {
        	$redirect = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'redirect_url', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );
        	
        	if ( $redirect['redirect_url'] )
        	{
        		//-----------------------------------------
				// Update hits:
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_exec_query( array( 'update' => 'forums', 'set' => 'redirect_hits=redirect_hits+1', 'where' => "id=".$this->forum['id']) );
				
				//-----------------------------------------
				// Recache forum
				//-----------------------------------------
				
				$this->ipsclass->cache['forum_cache'][ $this->forum['id'] ][ 'redirect_hits' ] = $this->forum['redirect_hits'] + 1;
				
				//-----------------------------------------
				// Turn off shutdown queries to get this
				// parsed before the redirect
				//-----------------------------------------
				
				$this->ipsclass->DB->obj['use_shutdown'] = 0;
				
				$this->ipsclass->update_forum_cache();
				
				//-----------------------------------------
				// Boink!
				//-----------------------------------------
				
				$this->ipsclass->boink_it( $redirect['redirect_url'] );
				
				// Game over man!
        	}
        }
        
        //-----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //-----------------------------------------
        
        $this->nav = $this->ipsclass->forums->forums_breadcrumb_nav( $this->forum['id'] );
        
		$this->forum['FORUM_JUMP'] = $this->ipsclass->build_forum_jump(1,0,0);
		
		//-----------------------------------------
		// Check forum access perms
		//-----------------------------------------
		
		if ( ! isset($this->ipsclass->input['L']) OR !$this->ipsclass->input['L'] )
		{
			$this->ipsclass->forums->forums_check_access( $this->forum['id'], 1 );
		}
		
		//-----------------------------------------
        // Are we viewing the forum, or viewing the forum rules?
        //-----------------------------------------
        
        if ( $this->ipsclass->input['act'] == 'SR' )
        {
        	$this->show_rules();
        }
        else
        {
			$this->show_subforums();
			
			if ( $this->forum['sub_can_post'] )
			{
				$this->show_forum();
			}
			else
			{
				//-----------------------------------------
				// No forum to show, just use the HTML in $this->sub_output
				// or there will be no HTML to use in the str_replace!
				//-----------------------------------------
				
				$this->output  = $this->ipsclass->print_forum_rules($this->forum);
				$this->output .= $this->sub_output;
			}
        }
        
        //-----------------------------------------
		// Subforums
		//-----------------------------------------
		
		if ($this->sub_output != "")
		{
			$this->output = str_replace( "<!--IBF.SUBFORUMS-->", $this->sub_output, $this->output );
		}
		
		if ( $this->announce_out )
		{
			$this->output = str_replace( "<!--IBF.ANNOUNCEMENTS-->", $this->announce_out, $this->output );
		}
		
		$this->ipsclass->print->add_output($this->output);
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -> ".$this->forum['name'],
												  'JS'       => 0,
												  'NAV'      => $this->nav,
										 )      );
     }
     
     /*-------------------------------------------------------------------------*/
	 // Display any sub forums
	 /*-------------------------------------------------------------------------*/
     
     function show_subforums()
     {
		if ( $this->ipsclass->forums->read_topic_only == 1 )
		{
			//$this->sub_output = "";
			//return;
		}
		
		$boards = $this->ipsclass->load_class( ROOT_PATH.'sources/action_public/boards.php', 'boards' );
		
		//-----------------------------------------
		// Load DB tracked topics
		//-----------------------------------------
		
		$boards->boards_get_db_tracker();
		
		$this->sub_output = $boards->show_subforums($this->ipsclass->input['f']);
    }
    
    /*-------------------------------------------------------------------------*/
	// Show the forum rules on a separate page
	/*-------------------------------------------------------------------------*/
        
	function show_rules()
	{
		//-----------------------------------------
		// Do we have permission to view these rules?
		//-----------------------------------------
		
		$allow_access = $this->ipsclass->forums->forums_check_access( $this->forum['id'], 1 );
        
        if ( $allow_access === FALSE )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_topic') );
        }
        
        $tmp = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'rules_title, rules_text', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );
             
        if ( $tmp['rules_title'] )
        {
        	$rules['title'] = $tmp['rules_title'];
        	$rules['body']  = $tmp['rules_text'];
        	$rules['fid']   = $this->forum['id'];
        	
        	$this->output .= $this->ipsclass->compiled_templates['skin_forum']->show_rules($rules);
        	
			$this->ipsclass->print->add_output( $this->output );
			$this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -&gt; ".$this->forum['name'],
													  'JS'       => 0,
													  'NAV'      => array( 
																		   $this->forum['name']
																		 ),
												  ) );
		}
		else
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_view_topic') );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum view check for authentication
	/*-------------------------------------------------------------------------*/
   
	function show_forum()
	{
		// are we checking for user authentication via the log in form
		// for a private forum w/password protection?
		
		if ( $this->ipsclass->input['L'] > 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' =>'incorrect_use') );
		}
		
		$this->ipsclass->input['L'] == 1 ? $this->authenticate_user() : $this->render_forum();
	}
	
	/*-------------------------------------------------------------------------*/
	// Authenicate the log in for a password protected forum
	/*-------------------------------------------------------------------------*/
	
	function authenticate_user()
	{
		if ($this->ipsclass->input['f_password'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'pass_blank' ) );
		}
		
		if ( $this->ipsclass->input['f_password'] != $this->forum['password'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'wrong_pass' ) );
		}
		
		$this->ipsclass->my_setcookie( "ipbforumpass_".$this->forum['id'], md5($this->ipsclass->input['f_password']) );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['logged_in'] , "showforum=".$this->forum['id'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Main render forum engine
	/*-------------------------------------------------------------------------*/
	
	function render_forum()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		# If we've changed the filters, bounce back to page 1
		$this->ipsclass->input['st'] = isset($this->ipsclass->input['changefilters']) ? 0 : (intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0);
		
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
	    
		//-----------------------------------------
		// Announcements
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['announcements'] ) and count( $this->ipsclass->cache['announcements'] ) )
		{
			$announcements = array();
			
			foreach( $this->ipsclass->cache['announcements'] as $announce )
			{
				$order = $announce['announce_start'] ? $announce['announce_start'].','.$announce['announce_id'] : $announce['announce_id'];
				
				if (  $announce['announce_forum'] == '*' )
				{
					$announcements[ $order ] = $announce;
				}
				else if ( strstr( ','.$announce['announce_forum'].',', ','.$this->forum['id'].',' ) )
				{
					$announcements[ $order ] = $announce;
				}
			}
			
			if ( count( $announcements ) )
			{
				//-----------------------------------------
				// sort by start date
				//-----------------------------------------
				
				$announce_html = "";
				
				krsort( $announcements );
				
				foreach( $announcements as $announce )
				{
					if ( $announce['announce_start'] )
					{
						$announce['announce_start'] = gmdate( 'jS F Y', $announce['announce_start'] );
					}
					else
					{
						$announce['announce_start'] = '--';
					}
					
					$announce['announce_title'] = $this->ipsclass->txt_stripslashes($announce['announce_title']);
					$announce['forum_id']       = $this->forum['id'];
					$announce['announce_views'] = intval($announce['announce_views']);
					$announce_html .= $this->ipsclass->compiled_templates['skin_forum']->announcement_row( $announce );
				}
				
				$this->announce_out = $this->ipsclass->compiled_templates['skin_forum']->announcement_wrap($announce_html);
			}
		}
		
		//-----------------------------------------
		// Read topics
		//-----------------------------------------
		
		$First = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
		
		$this->ipsclass->member['members_markers']['board'] = isset($this->ipsclass->member['members_markers']['board']) ? $this->ipsclass->member['members_markers']['board'] : 0;
		
		$this->ipsclass->input['last_visit'] = $this->ipsclass->member['last_visit'] > $this->ipsclass->member['members_markers']['board'] ? $this->ipsclass->member['last_visit'] : $this->ipsclass->member['members_markers']['board'];
		
		//-----------------------------------------
		// Over ride with 'master' cookie?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->forum_read[0]) AND $this->ipsclass->forum_read[0] > $this->ipsclass->forum_read[ $this->ipsclass->input['f'] ] )
		{
			$this->ipsclass->forum_read[ $this->ipsclass->input['f'] ] = $this->ipsclass->forum_read[0];
		}
		
		//-----------------------------------------
		// Sort options
		//-----------------------------------------
		
		$cookie_prune = $this->ipsclass->my_getcookie( $this->forum['id']."_prune_day" );
		$cookie_sort  = $this->ipsclass->my_getcookie( $this->forum['id']."_sort_key" );
		$cookie_sortb = $this->ipsclass->my_getcookie( $this->forum['id']."_sort_by" );
		$cookie_fill  = $this->ipsclass->my_getcookie( $this->forum['id']."_topicfilter" );
		
		$prune_value = $this->ipsclass->select_var( array( 
												1 => isset($this->ipsclass->input['prune_day']) ? $this->ipsclass->input['prune_day'] : NULL,
												2 => !empty($cookie_prune) ? $cookie_prune : NULL,
												3 => $this->forum['prune']        ,
												4 => '100'                        )
									    );

		$sort_key    = $this->ipsclass->select_var( array(
												1 => isset($this->ipsclass->input['sort_key']) ? $this->ipsclass->input['sort_key'] : NULL,
												2 => !empty($cookie_sort) ? $cookie_sort : NULL,
												3 => $this->forum['sort_key'],
												4 => 'last_post'            )
									   );

		$sort_by     = $this->ipsclass->select_var( array(
												1 => isset($this->ipsclass->input['sort_by']) ? $this->ipsclass->input['sort_by'] : NULL,
												2 => !empty($cookie_sortb) ? $cookie_sortb : NULL,
												3 => $this->forum['sort_order'] ,
												4 => 'Z-A'                      )
									   );
									   
		$topicfilter     = $this->ipsclass->select_var( array(
												1 => isset($this->ipsclass->input['topicfilter']) ? $this->ipsclass->input['topicfilter'] : NULL,
												2 => !empty($cookie_fill) ? $cookie_fill : NULL,
												3 => $this->forum['topicfilter'] ,
												4 => 'all'                      )
									   );

		if( isset($this->ipsclass->input['remember']) AND $this->ipsclass->input['remember'] )
		{
			if( isset($this->ipsclass->input['prune_day']) AND $this->ipsclass->input['prune_day'] )
			{
				$this->ipsclass->my_setcookie( $this->forum['id']."_prune_day", $this->ipsclass->input['prune_day'] );
			}
			
			if( isset($this->ipsclass->input['sort_key']) AND $this->ipsclass->input['sort_key'] )
			{
				$this->ipsclass->my_setcookie( $this->forum['id']."_sort_key", $this->ipsclass->input['sort_key'] );
			}	
			
			if( isset($this->ipsclass->input['sort_by']) AND $this->ipsclass->input['sort_by'] )
			{
				$this->ipsclass->my_setcookie( $this->forum['id']."_sort_by", $this->ipsclass->input['sort_by'] );
			}	
			
			if( isset($this->ipsclass->input['topicfilter']) AND $this->ipsclass->input['topicfilter'] )
			{
				$this->ipsclass->my_setcookie( $this->forum['id']."_topicfilter", $this->ipsclass->input['topicfilter'] );
			}
		}
									  
		//-----------------------------------------
		// Figure out sort order, day cut off, etc
		//-----------------------------------------
		
		$Prune = $prune_value != 100 ? (time() - ($prune_value * 60 * 60 * 24)) : 0;

		$sort_keys   =  array( 'last_post'         => 'sort_by_date',
							   'last_poster_name'  => 'sort_by_last_poster',
							   'title'             => 'sort_by_topic',
							   'starter_name'      => 'sort_by_poster',
							   'start_date'        => 'sort_by_start',
							   'topic_hasattach'   => 'sort_by_attach',
							   'posts'             => 'sort_by_replies',
							   'views'             => 'sort_by_views',
							   
							 );

		$prune_by_day = array( '1'    => 'show_today',
							   '5'    => 'show_5_days',
							   '7'    => 'show_7_days',
							   '10'   => 'show_10_days',
							   '15'   => 'show_15_days',
							   '20'   => 'show_20_days',
							   '25'   => 'show_25_days',
							   '30'   => 'show_30_days',
							   '60'   => 'show_60_days',
							   '90'   => 'show_90_days',
							   '100'  => 'show_all',
							 );

		$sort_by_keys = array( 'Z-A'  => 'descending_order',
                         	   'A-Z'  => 'ascending_order',
                             );
                             
        $filter_keys  = array( 'all'    => 'topicfilter_all',
        					   'open'   => 'topicfilter_open',
        					   'hot'    => 'topicfilter_hot',
        					   'poll'   => 'topicfilter_poll',
        					   'locked' => 'topicfilter_locked',
        					   'moved'  => 'topicfilter_moved',
        					 );
        					 
        if ( $this->ipsclass->member['id'] )
        {
        	$filter_keys['istarted'] = 'topicfilter_istarted';
        	$filter_keys['ireplied'] = 'topicfilter_ireplied';
        }
        
        //-----------------------------------------
        // check for any form funny business by wanna-be hackers
		//-----------------------------------------
		
		if ( (!isset($filter_keys[$topicfilter])) or (!isset($sort_keys[$sort_key])) or (!isset($prune_by_day[$prune_value])) or (!isset($sort_by_keys[$sort_by])) )
		{
			   $this->ipsclass->Error( array( 'LEVEL' => 5, 'MSG' =>'incorrect_use') );
	    }
	    
	    $r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';
	    
	    //-----------------------------------------
	    // Additional queries?
	    //-----------------------------------------
	    
	    $add_query_array = array();
	    $add_query       = "";
	    
	    switch( $topicfilter )
	    {
	    	case 'all':
	    		break;
	    	case 'open':
	    		$add_query_array[] = "t.state='open'";
	    		break;
	    	case 'hot':
	    		$add_query_array[] = "t.state='open' AND t.posts + 1 >= ".intval($this->ipsclass->vars['hot_topic']);
	    		break;
	    	case 'locked':
	    		$add_query_array[] = "t.state='closed'";
	    		break;
	    	case 'moved':
	    		$add_query_array[] = "t.state='link'";
	    		break;
	    	case 'poll':
	    		$add_query_array[] = "(t.poll_state='open' OR t.poll_state=1)";
	    		break;
	    	default:
	    		break;
	    }
	    
	    if ( ! $this->ipsclass->member['g_other_topics'] or $topicfilter == 'istarted' )
		{
            $add_query_array[] = "t.starter_id='".$this->ipsclass->member['id']."'";
		}
		
		if ( count($add_query_array) )
		{
			$add_query = ' AND '. implode( ' AND ', $add_query_array );
		}
		
		//-----------------------------------------
		// Moderator?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['is_mod'] )
		{
			$approved = 'and t.approved=1';
		}
		else
		{
			$approved = 'and t.approved IN (0,1)';
		}
		
		//-----------------------------------------
		// Query the database to see how many topics there are in the forum
		//-----------------------------------------
		
		if ( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			//-----------------------------------------
			
			if ( $Prune )
			{
				$prune_filter = "and (t.pinned=1 or t.last_post > $Prune)";
			}
			else
			{
				$prune_filter = "";
			}
			
			$this->ipsclass->DB->cache_add_query( 'forums_get_replied_topics', array( 'mid'          => $this->ipsclass->member['id'],
																					  'fid'          => $this->forum['id'],
																					  'approved'     => $approved,
																					  'prune_filter' => $prune_filter ) );
			$this->ipsclass->DB->cache_exec_query();
			
			$total_possible = $this->ipsclass->DB->fetch_row();
		}
		else if ( ( $add_query or $Prune ) and ! $this->ipsclass->input['modfilter'] )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as max',
														  'from'   => 'topics t',
														  'where'  => "t.forum_id=".$this->forum['id']." {$approved} and (t.pinned=1 or t.last_post > $Prune)" . $add_query
												 )      );

			$this->ipsclass->DB->simple_exec();
			
			$total_possible = $this->ipsclass->DB->fetch_row();
		}
		else 
		{
			$total_possible['max'] = $this->ipsclass->member['is_mod'] ? $this->forum['topics'] + $this->forum['queued_topics'] : $this->forum['topics'];
			$Prune = 0;
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------
		
		$this->forum['SHOW_PAGES']
			= $this->ipsclass->build_pagelinks( array( 'TOTAL_POSS'  => $total_possible['max'],
													   'PER_PAGE'    => $this->ipsclass->vars['display_max_topics'],
													   'CUR_ST_VAL'  => $this->ipsclass->input['st'],
													   'L_SINGLE'    => $this->ipsclass->lang['single_page_forum'],
													   'BASE_URL'    => $this->ipsclass->base_url."showforum=".$this->forum['id']."&amp;prune_day=$prune_value&amp;sort_by=$sort_by&amp;sort_key=$sort_key&amp;topicfilter={$topicfilter}",
													 )
											  );
								   
								   
		//-----------------------------------------
		// Do we have any rules to show?
		//-----------------------------------------
		
		 $this->output .= $this->ipsclass->print_forum_rules($this->forum);
		
		//-----------------------------------------
		// Start printing the page
		//-----------------------------------------

		$this->output .= $this->ipsclass->compiled_templates['skin_forum']->PageTop( $this->forum, $this->can_edit_topics, $this->can_open_topics, $this->can_close_topics );
		
		//-----------------------------------------
		// Do we have any topics to show?
		//-----------------------------------------
		
		if ($total_possible['max'] < 1)
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_forum']->show_no_matches();
		}
		
		$total_topics_printed = 0;
		
		//-----------------------------------------
		// Get main topics
		//-----------------------------------------
		
		$topic_array = array();
		$topic_ids   = array();
		$topic_sort  = "";
        
        //-----------------------------------------
        // Mod filter?
        //-----------------------------------------
        
        $this->ipsclass->input['modfilter'] = isset($this->ipsclass->input['modfilter']) ? $this->ipsclass->input['modfilter'] : '';
        
		if ( $this->ipsclass->input['modfilter'] == 'invisible_topics' and $this->ipsclass->member['is_mod'] )
		{
			$topic_sort = 't.approved asc,';
		}
		else if ( $this->ipsclass->input['modfilter'] == 'invisible_posts' and $this->ipsclass->member['is_mod'] )
		{
			$topic_sort = 't.topic_queuedposts desc,';
		}
		else if ( $this->ipsclass->input['modfilter'] == 'all' and $this->ipsclass->member['is_mod'] )
		{
			$topic_sort = 't.approved asc, t.topic_queuedposts desc,';
		}
		
		//-----------------------------------------
		// Cut off?
		//-----------------------------------------
		
		$parse_dots = 1;
		
		if ( $Prune )
		{
			$query = "t.forum_id=".$this->forum['id']." AND t.pinned IN (0,1) {$approved} and (t.last_post > $Prune OR t.pinned=1)";
		}
		else
		{
			$query = "t.forum_id=".$this->forum['id']." AND t.pinned IN (0,1) {$approved}";
		}
		
		if ( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			// No point in getting dots again...
			//-----------------------------------------
			
			$parse_dots = 0;
			
			$this->ipsclass->DB->cache_add_query( 'forums_get_replied_topics_actual', array( 'mid'          => $this->ipsclass->member['id'],
																							 'fid'          => $this->forum['id'],
																							 'query'        => $query,
																							 'topic_sort'   => $topic_sort,
																							 'sort_key'     => "t.".$sort_key,
																							 'r_sort_by'    => $r_sort_by,
																							 'limit_a'      => intval($First),
																							 'limit_b'      => intval($this->ipsclass->vars['display_max_topics']) ) );
			$this->ipsclass->DB->cache_exec_query();
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'topics t',
														  'where'  =>  $query . $add_query,
														  'order'  => 't.pinned DESC, '.$topic_sort.' t.'.$sort_key .' '. $r_sort_by,
														  'limit'  => array( intval($First), $this->ipsclass->vars['display_max_topics'] )
												 )      );
			$this->ipsclass->DB->simple_exec();
		}
		
		while ( $t = $this->ipsclass->DB->fetch_row() )
		{
			$topic_array[ $t['tid'] ] = $t;
			$topic_ids[ $t['tid'] ]   = $t['tid'];
		}
			
		ksort($topic_ids);
		
		//-----------------------------------------
		// Are we dotty?
		//-----------------------------------------
		
		if ( ($this->ipsclass->vars['show_user_posted'] == 1) and ($this->ipsclass->member['id']) and ( count($topic_ids) ) and ( $parse_dots ) )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'author_id, topic_id',
														  'from'   => 'posts',
														  'where'  => "author_id=".$this->ipsclass->member['id']." AND topic_id IN(".implode( ",", $topic_ids ).")",
												)      );
									  
			$this->ipsclass->DB->simple_exec();
			
			while( $p = $this->ipsclass->DB->fetch_row() )
			{
				if ( is_array( $topic_array[ $p['topic_id'] ] ) )
				{
					$topic_array[ $p['topic_id'] ]['author_id'] = $p['author_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Get topic trackers table?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['db_topic_read_cutoff'] and count($topic_ids) )
		{
			if ( isset($this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_db_row']) AND 
					$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_db_row']
				)
			{
				$this->db_row = $this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_db_row'];
			}
			else
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'topic_markers',
															  'where'  => "marker_forum_id=".$this->forum['id']." AND marker_member_id=".$this->ipsclass->member['id'],
													)      );
										  
				$this->ipsclass->DB->simple_exec();
				
				$this->db_row   = $this->ipsclass->DB->fetch_row();
			}
			
			$this->my_topics_read  = unserialize(stripslashes($this->db_row['marker_topics_read']) );
			$this->read_topics_tid = array();
			
			//-----------------------------------------
			// Got read topics?
			//-----------------------------------------
			
			if ( is_array( $this->my_topics_read ) and count ( $this->my_topics_read ) )
			{
				foreach( $this->my_topics_read as $tid => $date )
				{
					if ( isset($topic_array[ $tid ]) AND is_array( $topic_array[ $tid ] ) )
					{
						$this->read_topics_tid[]        = $tid;
						$topic_array[ $tid ]['db_read'] = $date > $this->db_row['marker_last_cleared'] ? $date : $this->db_row['marker_last_cleared'];
					}
				}
			}
			
			//-----------------------------------------
			// No? Got a last cleared date?
			//-----------------------------------------
			
			else if ( $this->db_row['marker_last_cleared'] )
			{
				if ( is_array( $topic_array ) )
				{
					foreach( $topic_array as $tid => $data )
					{
						$topic_array[ $tid ]['db_read'] = $this->db_row['marker_last_cleared'];
					}
				}
			}
		}
		
		//-----------------------------------------
		// Show meh the topics!
		//-----------------------------------------
		
		foreach( $topic_array as $topic )
		{
			if ( $topic['pinned'] )
			{
				$this->pinned_topic_count++;
			}
			
			$this->output .= $this->render_entry( $topic );
			
			$total_topics_printed++;
		}
		
		//-----------------------------------------
		// Finish off the rest of the page  $filter_keys[$topicfilter]))
		//-----------------------------------------
		
		$sort_by_html 	= "";
		$sort_key_html	= "";
		$prune_day_html = "";
		$filter_html	= "";
		
		foreach ($sort_by_keys as $k => $v)
		{
			$sort_by_html   .= $k == $sort_by     ? "<option value='$k' selected='selected'>" . $this->ipsclass->lang[ $sort_by_keys[ $k ] ] . "</option>\n"
											      : "<option value='$k'>"                     . $this->ipsclass->lang[ $sort_by_keys[ $k ] ] . "</option>\n";
		}
	
		foreach ($sort_keys as  $k => $v)
		{
			$sort_key_html  .= $k == $sort_key    ? "<option value='$k' selected='selected'>" . $this->ipsclass->lang[ $sort_keys[ $k ] ]    . "</option>\n"
											      : "<option value='$k'>"                     . $this->ipsclass->lang[ $sort_keys[ $k ] ]    . "</option>\n";
		}
		
		foreach ($prune_by_day as  $k => $v)
		{
			$prune_day_html .= $k == $prune_value ? "<option value='$k' selected='selected'>" . $this->ipsclass->lang[ $prune_by_day[ $k ] ] . "</option>\n"
												  : "<option value='$k'>"                     . $this->ipsclass->lang[ $prune_by_day[ $k ] ] . "</option>\n";
		}
		
		foreach ($filter_keys as  $k => $v)
		{
			$filter_html    .= $k == $topicfilter ? "<option value='$k' selected='selected'>" . $this->ipsclass->lang[ $filter_keys[ $k ] ]  . "</option>\n"
												  : "<option value='$k'>"                     . $this->ipsclass->lang[ $filter_keys[ $k ] ]  . "</option>\n";
		}
	
		$this->ipsclass->show['sort_by']      = $sort_key_html;
		$this->ipsclass->show['sort_order']   = $sort_by_html;
		$this->ipsclass->show['sort_prune']   = $prune_day_html;
		$this->ipsclass->show['topic_filter'] = $filter_html;
		
		if( $this->ipsclass->member['is_mod'] )
		{
			$count = 0;
			$other_pages = 0;
			
			if( $this->ipsclass->input['selectedtids'] != "" )
			{
				$tids = explode( ",",$this->ipsclass->input['selectedtids'] );
				
				if( is_array($tids) AND count($tids) )
				{
					foreach( $tids as $tid )
					{
						if( $tid != '' )
						{
							if( !array_key_exists( $tid, $topic_array ) )
							{
								$other_pages++;
							}
							
							$count++;
						}
					}
				}
			}
			
			$this->ipsclass->lang['f_go'] .= " ({$count})";
			
			if( $other_pages )
			{
				$this->ipsclass->lang['f_go'] .= " ({$other_pages} {$this->ipsclass->lang['jscript_otherpage']})";
			}
		}
	
		$this->output .= $this->ipsclass->compiled_templates['skin_forum']->TableEnd($this->forum, $this->auth_key);
		
		//-----------------------------------------
		// Multi-moderation?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['is_mod'] )
		{
			$mm_html  = "";
			$mm_array = $this->ipsclass->get_multimod( $this->forum['id'] );
			
			if ( is_array( $mm_array ) and count( $mm_array ) )
			{
				foreach( $mm_array as $m )
				{
					$mm_html .= $this->ipsclass->compiled_templates['skin_forum']->mm_entry( $m[0], $m[1] );
				}
			}
			
			if ( $mm_html )
			{
				$this->output = str_replace( '<!--IBF.MMOD-->', $this->ipsclass->compiled_templates['skin_forum']->mm_start() . $mm_html . $this->ipsclass->compiled_templates['skin_forum']->mm_end(), $this->output );
			}
		}
		
		//-----------------------------------------
		// Need to update topics?
		//-----------------------------------------
		
		if ( count( $this->update_topics_open ) )
		{
			$this->ipsclass->DB->do_shutdown_update( 'topics', array( 'state' => 'open' ), 'tid IN ('.implode( ",", $this->update_topics_open ) .')' );
		}
		
		if ( count( $this->update_topics_close ) )
		{
			$this->ipsclass->DB->do_shutdown_update( 'topics', array( 'state' => 'closed' ), 'tid IN ('.implode( ",", $this->update_topics_close ) .')' );
		}
		
		//-----------------------------------------
		// Update forum read table?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['db_topic_read_cutoff'] AND $this->ipsclass->member['id'] AND count($topic_ids) )
		{
			//-----------------------------------------
			// More posts since last update?
			// OR: unread == 0 but we have unread topics
			// Can happen when no marker DB row and we
			// view a topic
			//-----------------------------------------
			
			$db_time = $this->db_row['marker_last_cleared'] > $this->ipsclass->member['members_markers']['board'] ? $this->db_row['marker_last_cleared'] : $this->ipsclass->member['members_markers']['board'];
				
			if ( ( $this->db_row['marker_unread'] <= 0 AND ( $this->db_row['marker_last_update'] < $this->forum['last_post'] ) )
			 OR  ( $this->forum['forum_last_deletion'] > $this->db_row['marker_last_update'] ) )
			{
				//-----------------------------------------
				// Get unread count
				//-----------------------------------------
				
				$notin = count($this->read_topics_tid) ? "AND t.tid NOT IN(0,".implode(",",$this->read_topics_tid).")" : "";
				
				$count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as cnt, MIN(last_post) as min_last_post',
																		   'from'   => 'topics t',
																		   'where'  => "t.forum_id={$this->forum['id']} {$approved} {$notin} AND t.last_post > ".intval($db_time) ) );
				$save_array = array();
				
				//-----------------------------------------
				// Update counter
				//-----------------------------------------
				
				$save_array['marker_unread'] 		= intval($count['cnt']);
				$save_array['marker_last_cleared']	= $this->db_row['marker_last_cleared'];
				
				//-----------------------------------------
				// Topics unread: Clean out old topics
				//-----------------------------------------
				
				if ( $save_array['marker_unread'] > 0 )
				{
					$this->ipsclass->vars['db_topic_read_cutoff'] = $count['min_last_post'] - 1;
					
					if ( is_array( $this->my_topics_read ) and count( $this->my_topics_read ) )
					{
						$this->my_topics_read = array_filter( $this->my_topics_read, array( 'ipsclass', "array_filter_clean_read_topics" ) );
					}
					else
					{
						$this->my_topics_read = array();
					}
					
					$save_array['marker_topics_read'] 	= serialize( $this->my_topics_read );
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
				
				$save_array['marker_last_update'] 	= time();
				$save_array['marker_member_id'] 	= $this->ipsclass->member['id'];
				$save_array['marker_forum_id']  	= $this->forum['id'];
					
				$this->ipsclass->DB->do_replace_into( 'topic_markers', $save_array, array('marker_member_id','marker_forum_id'), TRUE );
			}
		}
		
		//-----------------------------------------
		// Update forum read cookie, too
		//----------------------------------------- 
		
		if ( $this->forum_has_unread_topics < 1 and ! $this->ipsclass->input['st'] )
		{
			$this->ipsclass->forum_read[ $this->forum['id'] ] = time();
			
			$this->ipsclass->hdl_forum_read_cookie('set');
		}
		
		//-----------------------------------------
		// Process users active in this forum
		//-----------------------------------------
		
		if ($this->ipsclass->vars['no_au_forum'] != 1)
		{
			//-----------------------------------------
			// Get the users
			//-----------------------------------------
			
			$cut_off = ($this->ipsclass->vars['au_cutoff'] != "") ? $this->ipsclass->vars['au_cutoff'] * 60 : 900;
			$time    = time() - $cut_off;
			
			$this->ipsclass->DB->build_query( array( 'select'	=> 's.member_id, s.member_name, s.member_group, s.id, s.login_type, s.location, s.running_time',
													 'from'		=> array( 'sessions' => 's' ),
													 'where'	=> "s.location_2_type='forum' AND s.location_2_id={$this->forum['id']} AND s.running_time > {$time}	AND s.in_error=0",
													 'add_join'	=> array(
													 					array(
													 							'type'		=> 'left',
													 							'select'	=> 't.forum_id',
													 							'where'		=> 't.tid=s.location_1_id',
													 							'from'		=> array( 'topics' => 't' ),
													 						),
													 					),
											)		);
			$this->ipsclass->DB->exec_query();
			
			//-----------------------------------------
			// ACTIVE USERS
			//-----------------------------------------
			
			$ar_time = time();
			$cached  = array();
			$guests  = array();
			$active  = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => "");
			$rows    = array( $ar_time => array( 'login_type'   => substr($this->ipsclass->member['login_anonymous'],0, 1),
												'id'		   => $this->ipsclass->sess->session_id,
												'location'	   => 'sf',
												'running_time' => $ar_time,
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
						
						if ( strstr( $result['location'], 'post' ) )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
							$p_title = " title='".sprintf( $this->ipsclass->lang['au_posting'], $last_date )."' ";
						}
						
						if ($result['login_type'] == 1)
						{
							if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
							{
								$active['names'] .= "$p_start<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}'{$p_title}>{$result['member_name']}</a>*{$p_end}, ";
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
							$active['names'] .= "$p_start<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}'{$p_title}>{$result['member_name']}</a>{$p_end}, ";
						}
					}
				}
			}
			
			$active['names'] = preg_replace( "/,\s+$/", "" , $active['names'] );
			
			$this->ipsclass->lang['active_users_title']   = sprintf( $this->ipsclass->lang['active_users_title']  , ($active['members'] + $active['guests'] + $active['anon'] ) );
			$this->ipsclass->lang['active_users_detail']  = sprintf( $this->ipsclass->lang['active_users_detail'] , $active['guests'],$active['anon'] );
			$this->ipsclass->lang['active_users_members'] = sprintf( $this->ipsclass->lang['active_users_members'], $active['members'] );
			
			$this->output = str_replace( "<!--IBF.FORUM_ACTIVE-->", $this->ipsclass->compiled_templates['skin_forum']->forum_active_users($active), $this->output );
		}
		
		if ( ! $this->pinned_topic_count and $this->announce_out )
		{
			$this->announce_out .= $this->ipsclass->compiled_templates['skin_forum']->render_pinned_end();
		}
		
		return TRUE;
    }
    
    /*-------------------------------------------------------------------------*/
	// Parse data
	/*-------------------------------------------------------------------------*/
	
	function parse_data( $topic, $last_time_default=1 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->read_array[ $topic['tid'] ]                = isset( $this->read_array[ $topic['tid'] ] ) ? $this->read_array[ $topic['tid'] ] : 0;
		$this->ipsclass->forum_read[ $topic['forum_id'] ] = isset( $this->ipsclass->forum_read[ $topic['forum_id'] ] ) ? $this->ipsclass->forum_read[ $topic['forum_id'] ] : 0;
		
		//-----------------------------------------
		// Get a real ID so that moved
		// topic don't break owt
		//-----------------------------------------
		
		$topic['real_tid']   = $topic['tid'];
		$last_time           = 0;
		$topic['_last_post'] = $topic['last_post'];
		
		//-----------------------------------------
		// Need to update this topic?
		//-----------------------------------------
		
		if ( $topic['state'] == 'open' )
		{
			if( !$topic['topic_open_time'] OR $topic['topic_open_time'] < $topic['topic_close_time'] )
			{
				if ( $topic['topic_close_time'] AND ( $topic['topic_close_time'] <= time() AND ( time() >= $topic['topic_open_time'] OR !$topic['topic_open_time'] ) ) )
				{
					$topic['state'] = 'closed';
					
					$this->update_topics_close[] = $topic['real_tid'];
				}
			}
			else if( $topic['topic_open_time'] OR $topic['topic_open_time'] > $topic['topic_close_time'] )
			{
				if ( $topic['topic_close_time'] AND ( $topic['topic_close_time'] <= time() AND time() <= $topic['topic_open_time'] ) )
				{
					$topic['state'] = 'closed';
					
					$this->update_topics_close[] = $topic['real_tid'];
				}
			}				
		}
		else if ( $topic['state'] == 'closed' )
		{
			if( !$topic['topic_close_time'] OR $topic['topic_close_time'] < $topic['topic_open_time'] )
			{
				if ( $topic['topic_open_time'] AND ( $topic['topic_open_time'] <= time() AND ( time() >= $topic['topic_close_time'] OR !$topic['topic_close_time'] ) ) )
				{
					$topic['state'] = 'open';
					
					$this->update_topics_open[] = $topic['real_tid'];
				}
			}
			else if( $topic['topic_close_time'] OR $topic['topic_close_time'] > $topic['topic_open_time'] )
			{
				if ( $topic['topic_open_time'] AND ( $topic['topic_open_time'] <= time() AND time() <= $topic['topic_close_time'] ) )
				{
					$topic['state'] = 'open';
					
					$this->update_topics_open[] = $topic['real_tid'];
				}
			}					
		}
		
		//-----------------------------------------
		// Using DB?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] and $this->ipsclass->vars['db_topic_read_cutoff'] AND ($topic['last_post'] > intval($this->ipsclass->member['members_markers']['board'])) )
		{
			$db_topic_read_cutoff = time() - $this->ipsclass->vars['db_topic_read_cutoff'] * 86400;
			
			if ( $topic['last_post'] ) //> $db_topic_read_cutoff )
			{
				//-----------------------------------------
				// Have we read this topic before?
				//-----------------------------------------
				
				if ( isset($topic['db_read']) AND $topic['db_read'] )
				{
					$last_time = $topic['db_read'];
				}
				else if ( $this->db_row['marker_last_cleared'] )
				{
					$last_time = $this->db_row['marker_last_cleared'];
				}
				else
				{
					# $last_time_default is used because we don't have db tracking loaded
					# when we're using search
					$last_time = $last_time_default;
				}
			}
		}
		
		//-----------------------------------------
		// Not reading from DB or past out tracking limit
		// At this point: last_vist =
		// last_visit > board_marked ? last_visit : board_marked
		//-----------------------------------------

		if( $this->read_array[ $topic['tid'] ] > $last_time )
		{
			$last_time = $this->read_array[ $topic['tid'] ];
		}
		
		/*if( $this->ipsclass->input['last_visit'] > $last_time )
		{
			$last_time = $this->ipsclass->input['last_visit'];
		}*/
		
		if( $this->ipsclass->forum_read[ $topic['forum_id'] ] > $last_time )
		{
			$last_time = $this->ipsclass->forum_read[ $topic['forum_id'] ];
		}
		
		if( $this->ipsclass->member['members_markers']['board'] > $last_time )
		{
			$last_time = $this->ipsclass->member['members_markers']['board'];
		}
		
		/*if ( ! $last_time )
		{
			$last_time = $this->read_array[ $topic['tid'] ] > $this->ipsclass->input['last_visit'] ? $this->read_array[ $topic['tid'] ] : $this->ipsclass->input['last_visit'];
			
			if ( $this->ipsclass->forum_read[ $topic['forum_id'] ] > $last_time )
			{
				$last_time = $this->ipsclass->forum_read[ $topic['forum_id'] ];
			}
		}*/

		//-----------------------------------------
		// Attachy ment
		//-----------------------------------------
		
		if ( is_object( $this->ipsclass->compiled_templates['skin_forum'] ) )
		{
	 		$topic['attach_img'] = $topic['topic_hasattach'] ? $this->ipsclass->compiled_templates['skin_forum']->topic_attach_icon($topic['tid'], intval($topic['topic_hasattach'])) : '';
		}
		
		//-----------------------------------------
		// Yawn
		//-----------------------------------------
		
		$topic['last_text']   = $this->ipsclass->lang['last_post_by'];

		$topic['last_poster'] = $topic['last_poster_id'] ? $this->ipsclass->make_profile_link( $topic['last_poster_name'], $topic['last_poster_id']) : $this->ipsclass->vars['guest_name_pre'] . $topic['last_poster_name'] . $this->ipsclass->vars['guest_name_suf'];
								
		$topic['starter']     = $topic['starter_id']     ? $this->ipsclass->make_profile_link( $topic['starter_name'], $topic['starter_id']) : $this->ipsclass->vars['guest_name_pre'] . $topic['starter_name'] . $this->ipsclass->vars['guest_name_suf'];
	 
		$topic['prefix']  = $topic['poll_state'] ? $this->ipsclass->vars['pre_polls'].' ' : '';
		
		$show_dots = "";
		
		if ( $this->ipsclass->member['id'] and ( isset($topic['author_id']) AND $topic['author_id'] ) )
		{
			$show_dots = 1;
		}
	
		$topic['folder_img']     = $this->ipsclass->folder_icon( $topic, $show_dots, $last_time );
		
		$topic['topic_icon']     = $topic['icon_id']  ? '<img src="'.$this->ipsclass->vars['img_url'] . '/folder_post_icons/icon' . $topic['icon_id'] . '.gif" border="0" alt="" />'
													  : '&nbsp;';

		$topic['topic_icon'] = $topic['pinned'] ? '<{B_PIN}>' : $topic['topic_icon'];
		
		$topic['start_date'] = $this->ipsclass->get_date( $topic['start_date'], 'LONG' );
	
		//-----------------------------------------
		// Pages 'n' posts
		//-----------------------------------------
		
		$pages = 1;
		$topic['PAGES'] = "";
		
		if ( isset($this->ipsclass->member['is_mod']) AND $this->ipsclass->member['is_mod'] )
		{
			$topic['posts'] += intval($topic['topic_queuedposts']);
		}
		
		if ($topic['posts'])
		{
			$mode = $this->ipsclass->my_getcookie( 'topicmode' );
			
			if( $mode == 'threaded' )
			{
				$this->ipsclass->vars['display_max_posts'] = $this->ipsclass->vars['threaded_per_page'];
			}
			
			if ( (($topic['posts'] + 1) % $this->ipsclass->vars['display_max_posts']) == 0 )
			{
				$pages = ($topic['posts'] + 1) / $this->ipsclass->vars['display_max_posts'];
			}
			else
			{
				$number = ( ($topic['posts'] + 1) / $this->ipsclass->vars['display_max_posts'] );
				$pages = ceil( $number);
			}
		}
		
		if ( $pages > 1 AND is_object( $this->ipsclass->compiled_templates['skin_forum'] ) )
		{
			for ( $i = 0 ; $i < $pages ; ++$i )
			{
				$real_no = $i * $this->ipsclass->vars['display_max_posts'];
				$page_no = $i + 1;
				
				if ($page_no == 4 and $pages > 4)
				{
					$topic['PAGES'] .= $this->ipsclass->compiled_templates['skin_forum']->pagination_show_lastpage($topic['tid'], ($pages - 1) * $this->ipsclass->vars['display_max_posts'], $pages);
					break;
				}
				else
				{
					$topic['PAGES'] .= $this->ipsclass->compiled_templates['skin_forum']->pagination_show_page($topic['tid'], $real_no , $page_no);
				}
			}
			
			$topic['PAGES'] = $this->ipsclass->compiled_templates['skin_forum']->pagination_wrap_pages($topic['tid'], $topic['PAGES'], $topic['posts'] + 1, $this->ipsclass->vars['display_max_posts']);
		}
		
		//-----------------------------------------
		// Format some numbers
		//-----------------------------------------
		
		$topic['posts']  = $this->ipsclass->do_number_format( intval($topic['posts']) );
		$topic['views']	 = $this->ipsclass->do_number_format( intval($topic['views']) );
		
		//-----------------------------------------
		// Last time stuff...
		//-----------------------------------------
		
		if ($last_time  && ($topic['last_post'] > $last_time))
		{
			$this->forum_has_unread_topics++;
			$topic['go_new_post']  = "<a href='{$this->ipsclass->base_url}showtopic={$topic['tid']}&amp;view=getnewpost'><{NEW_POST}></a>";
		}
		else
		{	
			$topic['go_new_post']  = "";
		}
	
		$topic['last_post']  = $this->ipsclass->get_date( $topic['last_post'], 'SHORT' );
		
		//-----------------------------------------
		// Linky pinky!
		//-----------------------------------------
			
		if ($topic['state'] == 'link')
		{
			$t_array = explode("&", $topic['moved_to']);
			$topic['tid']       = $t_array[0];
			$topic['forum_id']  = $t_array[1];
			$topic['title']     = $topic['title'];
			$topic['views']     = '--';
			$topic['posts']     = '--';
			$topic['prefix']    = $this->ipsclass->vars['pre_moved']." ";
			$topic['go_new_post'] = "";
		}
		else if ( is_object( $this->ipsclass->compiled_templates['skin_forum'] ) )
		{
			$topic['posts'] = $this->ipsclass->compiled_templates['skin_forum']->who_link($topic['tid'], $topic['posts']);
		}
		
		$topic['_hasqueued'] = 0;
		
		if ( ( $this->ipsclass->member['g_is_supmod'] or
				(isset($this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['post_q']) AND $this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['post_q'] == 1) ) and 
				( $topic['topic_queuedposts'] ) 
			)
		{
			$topic['_hasqueued'] = 1;
		}
		
		//-----------------------------------------
		// Topic rating
		//-----------------------------------------
		
	    $topic['_rate_img']   = '';
	    
	    if ( isset($this->forum['forum_allow_rating']) AND $this->forum['forum_allow_rating'] AND is_object( $this->ipsclass->compiled_templates['skin_forum'] ) )
		{
			if ( $topic['topic_rating_total'] )
			{
				$topic['_rate_int'] = round( $topic['topic_rating_total'] / $topic['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $topic['topic_rating_hits'] >= $this->ipsclass->vars['topic_rating_needed'] ) AND ( $topic['_rate_int'] ) )
			{
				$topic['_rate_img']  = $this->ipsclass->compiled_templates['skin_forum']->topic_rating_image( $topic['_rate_int'] );
			}
		}
		
		//-----------------------------------------
		// Already switched on?
		//-----------------------------------------
		
		if (isset($this->ipsclass->member['is_mod']) AND $this->ipsclass->member['is_mod'] )
		{
			if ( $this->ipsclass->input['selectedtids'] )
			{
				if ( strstr( ','.$this->ipsclass->input['selectedtids'].',', ','.$topic['tid'].',' ) )
				{
					$topic['tidon'] = 1;
				}
				else
				{
					$topic['tidon'] = 0;
				}
			}
		}
		
		return $topic;
	}
	
	/*-------------------------------------------------------------------------*/
	// Crunches the data into pwetty html
	/*-------------------------------------------------------------------------*/

	function render_entry($topic)
	{
		$topic = $this->parse_data( $topic );
		
		$topic['PAGES']			= isset($topic['PAGES']) 		? $topic['PAGES'] 		: '';
		$topic['prefix']		= isset($topic['prefix'])		? $topic['prefix']		: '';
		$topic['attach_img'] 	= isset($topic['attach_img']) 	? $topic['attach_img'] 	: '';
		$topic['_hasqueued'] 	= isset($topic['_hasqueued']) 	? $topic['_hasqueued'] 	: '';
		$topic['tidon']			= isset($topic['tidon'])		? $topic['tidon']		: 0;
		
		$p_start    = "";
		$p_end      = "";
		$class1     = "row2";
		$class2     = "row1";
		$classposts = "row2";
		
		if ( $this->ipsclass->member['is_mod'] )
		{
			if ( ! $topic['approved'] )
			{
				$class1     = 'row4shaded';
				$class2     = 'row2shaded';
				$classposts = 'row4shaded';
			}
			else if ( isset($topic['_hasqueued']) AND $topic['_hasqueued'] )
			{
				$classposts = 'row4shaded';
			}
		}
		
		if ($topic['pinned'] == 1)
		{
			$topic['prefix'] = $this->ipsclass->vars['pre_pinned'];
			
			if ($this->pinned_print == 0)
			{
				// we've a pinned topic, but we've not printed the pinned
				// starter row, so..
				
				// We should always show this right?
				//$show    = $this->announce_out ? 1 : 0;
				$p_start = $this->ipsclass->compiled_templates['skin_forum']->render_pinned_start( 1 );
				
				$this->pinned_print = 1;
			}
			
			return $p_start . $this->ipsclass->compiled_templates['skin_forum']->render_forum_row( $topic, $class1, $class2, $classposts, 1 );
		}
		else
		{
			//-----------------------------------------
			// This is not a pinned topic, so lets check to see if we've
			// printed the footer yet.
			//-----------------------------------------
			
			if ($this->pinned_print == 1)
			{
				//-----------------------------------------
				// Nope, so..
				//-----------------------------------------
				
				$p_end = $this->ipsclass->compiled_templates['skin_forum']->render_pinned_end();
				
				$this->pinned_print = 0;
			}
			
			return $p_end . $this->ipsclass->compiled_templates['skin_forum']->render_forum_row( $topic, $class1, $class2, $classposts, 1 );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Returns the last action date
	/*-------------------------------------------------------------------------*/
	    
	function get_last_date($topic)
	{
		return $this->ipsclass->get_date( $topic['last_post'], 'SHORT' );
	}

}

?>