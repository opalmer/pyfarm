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
|   > $Date: 2007-03-07 16:47:18 -0500 (Wed, 07 Mar 2007) $
|   > $Revision: 872 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Board index module
|   > Module written by Matt Mecham
|   > Date started: 17th February 2002
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

class boards
{
	# Global
	var $ipsclass;
	
    var $output   = "";
    var $base_url = "";
    var $html     = "";
    var $forums   = array();
    var $mods     = array();
    var $cats     = array();
    var $children = array();
    var $nav;
    var $db_row   = array();
    
    var $news_topic_id = "";
    var $news_forum_id = "";
    var $news_title    = "";
    var $sep_char      = "";
    var $statfunc      = "";
    
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function init()
    {
    	$this->base_url = $this->ipsclass->base_url;

        // Get more words for this invocation!
        
        $this->ipsclass->load_language('lang_boards');
        
        $this->ipsclass->load_template('skin_boards');
    }
    
    /*-------------------------------------------------------------------------*/
    // Auto run function
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
        $this->init();
        
        $this->statfunc = $this->ipsclass->load_class( ROOT_PATH.'sources/lib/func_boardstats.php', 'func_boardstats' );
       
       	$this->statfunc->register_class( $this );
        
        if (! $this->ipsclass->member['id'] )
        {
        	$this->ipsclass->input['last_visit'] = time();
        }
        
        if ( $this->ipsclass->vars['ipbli_usertype'] != 'username' )
        {
        	$this->ipsclass->lang['qli_name'] = $this->ipsclass->lang['email_address'];
        }
        
        $this->output .= $this->ipsclass->compiled_templates['skin_boards']->PageTop( $this->ipsclass->get_date( $this->ipsclass->input['last_visit'], 'LONG' ) );
        
        //-----------------------------------------
        // Get DB markers
        //-----------------------------------------
       
        $this->boards_get_db_tracker();

        //-----------------------------------------
        // What are we doing?
        //-----------------------------------------
        
        $this->process_all_cats();
        
        //-----------------------------------------
		// Add in show online users
		//-----------------------------------------
		
		$stats_html  = "";
		$stats_html .= $this->statfunc->active_users();
			
		//-----------------------------------------
		// Are we viewing the calendar?
		//-----------------------------------------
		
		$stats_html .= $this->statfunc->show_calendar_events();
		
		//-----------------------------------------
		// Add in show stats
		//-----------------------------------------
		
		$stats_html .= $this->statfunc->show_totals();
		
		if ($stats_html != "")
		{
			$collapsed_ids = ','.$this->ipsclass->my_getcookie('collapseprefs').',';
		
			$show['div_fo'] = '';
			$show['div_fc'] = 'none';
				
			if ( strstr( $collapsed_ids, ',stat,' ) )
			{
				$show['div_fo'] = 'none';
				$show['div_fc'] = '';
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_boards']->stats_header($this->statfunc->users_online, $this->statfunc->total_posts, $this->statfunc->total_members, $show);
			$this->output .= $stats_html;
			$this->output .= $this->ipsclass->compiled_templates['skin_boards']->stats_footer();
		}
		
		//-----------------------------------------
		// Add in board info footer
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_boards']->bottom_links();
		
		//-----------------------------------------
		// Check for news forum.
		//-----------------------------------------
		
		if ( isset($this->ipsclass->forums->forum_by_id[ $this->ipsclass->vars['news_forum_id'] ]['newest_id']) AND $this->ipsclass->forums->forum_by_id[ $this->ipsclass->vars['news_forum_id'] ]['newest_id'] AND $this->ipsclass->vars['index_news_link'] )
		{
			$t_html = $this->ipsclass->compiled_templates['skin_boards']->newslink( $this->news_forum_id, stripslashes($this->ipsclass->forums->forum_by_id[ $this->ipsclass->vars['news_forum_id'] ]['newest_title']) ,
											 										$this->ipsclass->forums->forum_by_id[ $this->ipsclass->vars['news_forum_id'] ]['newest_id']);
											 
			$this->output = str_replace( "<!-- IBF.NEWSLINK -->" , "$t_html" , $this->output );
		}
		
		//-----------------------------------------
		// Showing who's chatting NEW?
		// IPB3.0: To Do: move into components
		//-----------------------------------------
		
		$this->ipsclass->vars['chat04_account_no'] = $this->ipsclass->vars['chat04_account_no'] ? $this->ipsclass->vars['chat04_account_no'] : $this->ipsclass->vars['chat_account_no'];
		$this->ipsclass->vars['chat04_who_on']     = $this->ipsclass->vars['chat04_who_on']     ? $this->ipsclass->vars['chat04_who_on']     : $this->ipsclass->vars['chat_who_on'];
		
		if ( $this->ipsclass->vars['chat04_account_no'] and $this->ipsclass->vars['chat04_who_on'] )
		{
			require_once( ROOT_PATH.'sources/lib/func_chat.php' );
			
			$chat           =  new func_chat();
			$chat->ipsclass =& $this->ipsclass;
			
			$chat->register_class( $this );
			
			$chat_html = $chat->get_online_list();
			
			$this->output = str_replace( "<!--IBF.WHOSCHATTING-->", $chat_html, $this->output );
		}
		
		//-----------------------------------------
		// Print as normal
		//-----------------------------------------

        $this->ipsclass->print->add_output( $this->output );
        
        $cp = " (Powered by Invision Power Board)";
        
        if ($this->ipsclass->vars['ips_cp_purchase'])
        {
        	$cp = "";
        }
        
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->vars['board_name'].$cp, 'JS' => 0, 'NAV' => $this->nav ) );
        
	}

    /*-------------------------------------------------------------------------*/
	//
	// Display sub forums
	//
	/*-------------------------------------------------------------------------*/
     
	function show_subforums($fid)
	{
		$this->init();
		
		$temp_html 	= "";
		$sub_output = "";
		
		//-----------------------------------------
		// Get show / hide cookah
		//-----------------------------------------
		
		$collapsed_ids = ','.$this->ipsclass->my_getcookie('collapseprefs').',';
        
        $this->ipsclass->forums->register_class( $this );

		if ( isset($this->ipsclass->forums->forum_cache[ $fid ]) AND is_array( $this->ipsclass->forums->forum_cache[ $fid ] ) )
		{
			$cat_data = $this->ipsclass->forums->forum_by_id[ $fid ];
			
			$cat_data['div_fo'] = '';
			$cat_data['div_fc'] = 'none';
				
			if ( strstr( $collapsed_ids, ','.$fid.',' ) and ( $cat_data['sub_can_post'] == 1 ) )
			{
				$cat_data['div_fo'] = 'none';
				$cat_data['div_fc'] = '';
			}
			
			foreach( $this->ipsclass->forums->forum_cache[ $fid ] as $forum_data )
			{
				
				$forum_data['_queued_img'] 		= isset($forum_data['_queued_img'] ) 	? $forum_data['_queued_img'] 	: '';
				$forum_data['_queued_info']		= isset($forum_data['_queued_info'] ) 	? $forum_data['_queued_info'] 	: '';
				$forum_data['show_subforums'] 	= isset($forum_data['show_subforums'] ) ? $forum_data['show_subforums'] : '';
				$forum_data['last_unread'] 		= isset($forum_data['last_unread'] ) 	? $forum_data['last_unread'] 	: '';
				
				//-----------------------------------------
				// Get all subforum stats
				// and calculate
				//-----------------------------------------
				
				if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] )
				{
					$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
					$need_desc[] = $forum_data['id'];
				}
					
				if ( $forum_data['redirect_on'] )
				{
					$forum_data['redirect_hits']	= $this->ipsclass->do_number_format( $forum_data['redirect_hits'] );
					
					$forum_data['redirect_target'] 	= isset($forum_data['redirect_target']) ? $forum_data['redirect_target'] : '_parent';
					
					$temp_html .= $this->ipsclass->compiled_templates['skin_boards']->forum_redirect_row( $forum_data );
				}
				else
				{
					$temp_html .= $this->ipsclass->compiled_templates['skin_boards']->ForumRow( $this->ipsclass->forums->forums_format_lastinfo( $this->ipsclass->forums->forums_calc_children( $forum_data['id'], $forum_data ) ) );
				}
			}
		}
		
		if ( $temp_html )
		{
			$sub_output .= $this->ipsclass->compiled_templates['skin_boards']->subheader($cat_data);
			$sub_output .= $temp_html;
			$sub_output .= $this->ipsclass->compiled_templates['skin_boards']->end_this_cat();
		}
		else
		{
			return $sub_output;
		}
		
		unset($temp_html);
		
		$sub_output .= $this->ipsclass->compiled_templates['skin_boards']->end_all_cats();
		
		//-----------------------------------------
        // Get descriptions?
        //-----------------------------------------
        
        if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] AND count($need_desc) )
        {
        	$this->ipsclass->DB->simple_construct( array( 'select' => 'id,description', 'from' => 'forums', 'where' => 'id IN('.implode( ',', $need_desc ) .')' ) );
        	$this->ipsclass->DB->simple_exec();
        	
        	while( $r = $this->ipsclass->DB->fetch_row() )
        	{
        		$sub_output = str_replace( "<!--DESCRIPTION:{$r['id']}-->", $r['description'], $sub_output );
        	}
        }
		
		return $sub_output;
    }
    
    /*-------------------------------------------------------------------------*/
	//
	// PROCESS ALL CATEGORIES
	//
	/*-------------------------------------------------------------------------*/
	
	function process_all_cats()
	{
		$need_desc = array();
		$root      = array();
		$parent    = array();
		
		//-----------------------------------------
		// Want to view categories?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['c']) AND $this->ipsclass->input['c'] )
		{
			foreach( explode( ",", $this->ipsclass->input['c'] ) as $c )
			{
				$c = intval( $c );
				$i = $this->ipsclass->forums->forum_by_id[ $c ]['parent_id'];
				
				$root[ $i ]   = $i;
				$parent[ $c ] = $c;
			}
		}
		
		if ( ! count( $root ) )
		{
			$root[] = 'root';
		}
		
		//-----------------------------------------
		// Get show / hide cookah
		//-----------------------------------------
		
		$collapsed_ids = ','.$this->ipsclass->my_getcookie('collapseprefs').',';
		
		$this->ipsclass->forums->register_class( $this );
		
		foreach( $root as $root_id )
		{
			if ( is_array( $this->ipsclass->forums->forum_cache[ $root_id ] ) and count( $this->ipsclass->forums->forum_cache[ $root_id ] ) )
			{
				foreach( $this->ipsclass->forums->forum_cache[ $root_id ] as $id => $forum_data )
				{
					$temp_html = "";
					
					//-----------------------------------------
					// Only showing certain root forums?
					//-----------------------------------------
					
					if ( count( $parent ) )
					{
						if ( ! in_array( $id, $parent ) )
						{
							continue;
						}
					}
					
					$cat_data = $forum_data;
					
					$cat_data['div_fo'] = '';
					$cat_data['div_fc'] = 'none';
						
					if ( strstr( $collapsed_ids, ','.$cat_data['id'].',' ) )
					{
						$cat_data['div_fo'] = 'none';
						$cat_data['div_fc'] = '';
					}
					
					if ( isset($this->ipsclass->forums->forum_cache[ $forum_data['id'] ]) AND is_array( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
					{
						foreach( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							$forum_data['show_subforums'] 	= isset($forum_data['show_subforums']) 	? $forum_data['show_subforums'] : '';
							$forum_data['_queued_img'] 		= isset($forum_data['_queued_img']) 	? $forum_data['_queued_img'] 	: '';
							$forum_data['_queued_info'] 	= isset($forum_data['_queued_info']) 	? $forum_data['_queued_info'] 	: '';
							$forum_data['last_unread']		= isset($forum_data['last_unread'])		? $forum_data['last_unread']	: '';
							
							//-----------------------------------------
							// Get all subforum stats
							// and calculate
							//-----------------------------------------
							
							if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] )
							{
								$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
								$need_desc[] = $forum_data['id'];
							}
							
							if ( $forum_data['redirect_on'] )
							{
								$forum_data['redirect_target'] = isset($forum_data['redirect_target']) ? $forum_data['redirect_target'] : '_parent';
								
								$temp_html .= $this->ipsclass->compiled_templates['skin_boards']->forum_redirect_row( $forum_data );
							}
							else
							{
								$temp_html .= $this->ipsclass->compiled_templates['skin_boards']->ForumRow( $this->ipsclass->forums->forums_format_lastinfo( $this->ipsclass->forums->forums_calc_children( $forum_data['id'], $forum_data ) ) );
							}
							
						}
					}
					
					if ( $temp_html )
					{
						$this->output .= $this->ipsclass->compiled_templates['skin_boards']->CatHeader_Expanded($cat_data);
						$this->output .= $temp_html;
						$this->output .= $this->ipsclass->compiled_templates['skin_boards']->end_this_cat();
					}
					
					unset($temp_html);
				}
			}
		}
		
        $this->output .= $this->ipsclass->compiled_templates['skin_boards']->end_all_cats();
        
        //-----------------------------------------
        // Get descriptions?
        //-----------------------------------------
        
        if ( isset($this->ipsclass->vars['forum_cache_minimum']) AND $this->ipsclass->vars['forum_cache_minimum'] AND count($need_desc) )
        {
        	$this->ipsclass->DB->simple_construct( array( 'select' => 'id,description', 'from' => 'forums', 'where' => 'id IN('.implode( ',', $need_desc ) .')' ) );
        	$this->ipsclass->DB->simple_exec();
        	
        	while( $r = $this->ipsclass->DB->fetch_row() )
        	{
        		$this->output = str_replace( "<!--DESCRIPTION:{$r['id']}-->", $r['description'], $this->output );
        	}
        }
    }
    
    /*-------------------------------------------------------------------------*/
    // DB Tracker
    /*-------------------------------------------------------------------------*/
    
    function boards_get_db_tracker()
    {
    	if ( $this->ipsclass->vars['db_topic_read_cutoff'] and $this->ipsclass->member['id'] )
        {
        	$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'topic_markers',
														  'where'  => "marker_member_id=".$this->ipsclass->member['id'],
												)      );
								  
			$this->ipsclass->DB->simple_exec();
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->forums->forum_by_id[ $r['marker_forum_id'] ] =
					isset($this->ipsclass->forums->forum_by_id[ $r['marker_forum_id'] ]) 	?
					$this->ipsclass->forums->forum_by_id[ $r['marker_forum_id'] ]			:
					array( 'parent_id' => 0 );
					
				$this->ipsclass->forums->forum_cache[ $this->ipsclass->forums->forum_by_id[ $r['marker_forum_id'] ]['parent_id'] ][ $r['marker_forum_id'] ]['_db_row'] = $r;
				$this->ipsclass->forums->forum_by_id[ $r['marker_forum_id'] ]['_db_row'] = $r;
			}
        }
	}
        
}

?>