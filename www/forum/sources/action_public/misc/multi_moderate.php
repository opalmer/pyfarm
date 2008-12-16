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
|   > Multi Moderation Module
|   > Module written by Matt Mecham
|   > Date started: 16th May 2003
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class  mmod {

    var $output    = "";
    var $topic     = array();
    var $forum     = array();
    var $topic_id  = "";
    var $forum_id  = "";
    var $mm_id     = "";
    var $moderator = "";
    var $modfunc   = "";
    var $mm_data   = "";
    var $parser    = "";
    
    //-----------------------------------------
	// @constructor (no, not bob the builder)
	//-----------------------------------------
    
    function auto_run()
    {
		//-----------------------------------------
        // Load modules...
        //-----------------------------------------
        
        $this->ipsclass->load_language('lang_mod');
        
        require( ROOT_PATH.'sources/lib/func_mod.php');
        $this->modfunc           =  new func_mod();
        $this->modfunc->ipsclass =& $this->ipsclass;
        
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches =  1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
		
        //-----------------------------------------
		// Clean the incoming
		//-----------------------------------------
        
        $this->ipsclass->input['t'] = intval($this->ipsclass->input['t']);
        $this->mm_id                = intval($this->ipsclass->input['mm_id']);
        
        if ($this->ipsclass->input['t'] < 0 )
        {
            $this->ipsclass->Error( array( LEVEL => '1', MSG => 'missing_files') );
        }
        
        //-----------------------------------------
        // Get the topic id / forum id
        //-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=".intval($this->ipsclass->input['t']) ) );
		$this->ipsclass->DB->simple_exec();
		
        $this->topic = $this->ipsclass->DB->fetch_row();
        
        $this->forum = $this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ];
        					
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if (! $this->forum['id'])
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1,'MSG' => 'missing_files') );
        }
        
        //-----------------------------------------
        // Error out if we can not find the topic
        //-----------------------------------------
        
        if (! $this->topic['tid'])
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
        }
        
        //-----------------------------------------
        // Are we a moderator?
        //-----------------------------------------
		
		if ( ($this->ipsclass->member['id']) and ($this->ipsclass->member['g_is_supmod'] != 1) )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
										  				  'from'   => 'moderators',
										 				  'where'  => "forum_id=".$this->forum['id']." AND (member_id='".$this->ipsclass->member['id']."' OR (is_group=1 AND group_id='".$this->ipsclass->member['mgroup']."'))" ) );
										  
			$this->ipsclass->DB->simple_exec();
		
			$this->moderator = $this->ipsclass->DB->fetch_row();
		}
        
        //-----------------------------------------
		// Init modfunc module
		//-----------------------------------------
		
		$this->modfunc->init( $this->forum, $this->topic, $this->moderator );
        
        //-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		if ( $this->modfunc->mm_authorize() != TRUE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
		}
		
		//-----------------------------------------
		// Get MM data
		//-----------------------------------------
        
        $this->mm_data = $this->ipsclass->cache['multimod'][ $this->mm_id ];
        
        if ( ! $this->mm_data['mm_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'is_broken_link') );
		}
		
		//-----------------------------------------
        // Does this forum have this mm_id
        //-----------------------------------------
		
		if ( $this->modfunc->mm_check_id_in_forum( $this->forum['id'], $this->mm_data ) != TRUE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		
        $this->modfunc->stm_init();
        
        //-----------------------------------------
        // Open close?
        //-----------------------------------------
        
        if ( $this->mm_data['topic_state'] != 'leave' )
        {
        	if ( $this->mm_data['topic_state'] == 'close' )
        	{
        		$this->modfunc->stm_add_close();
        	}
        	else if ( $this->mm_data['topic_state'] == 'open' )
        	{
        		$this->modfunc->stm_add_open();
        	}
        }
        
        //-----------------------------------------
        // pin no-pin?
        //-----------------------------------------
        
        if ( $this->mm_data['topic_pin'] != 'leave' )
        {
        	if ( $this->mm_data['topic_pin'] == 'pin' )
        	{
        		$this->modfunc->stm_add_pin();
        	}
        	else if ( $this->mm_data['topic_pin'] == 'unpin' )
        	{
        		$this->modfunc->stm_add_unpin();
        	}
        }
        
        //-----------------------------------------
        // Approve / Unapprove
        //-----------------------------------------
        
        if ( $this->mm_data['topic_approve'] )
        {
        	if ( $this->mm_data['topic_approve'] == 1 )
        	{
        		$this->modfunc->stm_add_approve();
        	}
        	else if ( $this->mm_data['topic_approve'] == 2 )
        	{
        		$this->modfunc->stm_add_unapprove();
        	}
        }
        
        //-----------------------------------------
        // Topic title
        //-----------------------------------------
        
        $title = $this->topic['title'];
        
        if ( $this->mm_data['topic_title_st'] )
        {
        	// Tidy up...
        	
        	$title = preg_replace( "/^".preg_quote($this->mm_data['topic_title_st'], '/')."/", "", $title );
        }
        
        if ( $this->mm_data['topic_title_end'] )
        {
        	// Tidy up...
        	
        	$title = preg_replace( "/".preg_quote($this->mm_data['topic_title_end'], '/')."$/", "", $title );
        }
        
        $this->modfunc->stm_add_title($this->mm_data['topic_title_st'].$title.$this->mm_data['topic_title_end']);
        
        //-----------------------------------------
        // Update what we have so far...
        //-----------------------------------------
        
        $this->modfunc->stm_exec( $this->topic['tid'] );
        
        //-----------------------------------------
        // Add reply?
        //-----------------------------------------
        
        if ( $this->mm_data['topic_reply'] and $this->mm_data['topic_reply_content'] )
        {
       		$this->parser->parse_smilies = 1;
			$this->parser->parse_bbcode  = 1;
		
        	$this->modfunc->auto_update = FALSE;  // Turn off auto forum re-synch, we'll manually do it at the end
        
        	$this->modfunc->topic_add_reply( 
        									$this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->mm_data['topic_reply_content'] ) )
										    , array( 0 => array( $this->topic['tid'], $this->forum['id'] ) )
										    , $this->mm_data['topic_reply_postcount']
										   );
		}
		
		//-----------------------------------------
        // Move topic?
        //-----------------------------------------
        
        if ( $this->mm_data['topic_move'] )
        {
        	//-----------------------------------------
        	// Move to forum still exist?
        	//-----------------------------------------
        	
        	$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, sub_can_post', 'from' => 'forums', 'where' => "id=".$this->mm_data['topic_move'] ) );
			$this->ipsclass->DB->simple_exec();
		
        	if ( $r = $this->ipsclass->DB->fetch_row() )
        	{
        		if ( $r['sub_can_post'] != 1 )
        		{
        			$this->ipsclass->DB->do_update( 'topic_mmod', array( 'topic_move' => 0 ), 'mm_id='.$this->mm_id );
        		}
        		else
        		{
        			if ( $r['id'] != $this->forum['id'] )
        			{
        				$this->modfunc->topic_move( $this->topic['tid'], $this->forum['id'], $r['id'], $this->mm_data['topic_move_link']);
        			
        				$this->modfunc->forum_recount( $r['id'] );
        			}
        		}
        	}
        	else
        	{
        		$this->ipsclass->DB->do_update( 'topic_mmod', array( 'topic_move' => 0 ), 'mm_id='.$this->mm_id );
        	}
        }
        
        //-----------------------------------------
        // Recount root forum
        //-----------------------------------------
        
        $this->modfunc->forum_recount( $this->forum['id'] );
        
        //-----------------------------------------
        // Add mod log
        //-----------------------------------------
        
        $this->modfunc->add_moderate_log( $this->forum['id'], $this->topic['tid'], "", $this->topic['title'], "Applied multi-mod: ".$this->mm_data['mm_title'] );
        
        //-----------------------------------------
        // Redirect back with nice fluffy message
        //-----------------------------------------
        
        $this->ipsclass->print->redirect_screen( sprintf($this->ipsclass->lang['mm_applied'], $this->mm_data['mm_title'] ), "showforum=".$this->forum['id'] );
		          
	}
	
	
	
}

?>