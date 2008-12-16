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
|   > $Date: 2007-03-28 18:08:28 -0400 (Wed, 28 Mar 2007) $
|   > $Revision: 910 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Topic Tracker module
|   > Module written by Matt Mecham
|   > Date started: 5th March 2002
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

class tracker {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";

    var $forum     = array();
    var $topic     = array();
    var $category  = array();
    var $type      = 'topic';
	var $method    = 'delayed';
    
    function auto_run($is_sub=0)
    {
    	if( !$this->ipsclass->input['t'] )
    	{
	    	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
    	}

    	//-----------------------------------------
    	// $is_sub is a boolean operator.
    	// If set to 1, we don't show the "topic subscribed" page
    	// we simply end the subroutine and let the caller finish
    	// up for us.
    	//-----------------------------------------
    
        $this->ipsclass->load_language('lang_emails');

        //-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        if ($this->ipsclass->input['type'] == 'forum')
        {
        	$this->type = 'forum';
        }
        
        //-----------------------------------------
        // Method..
        //-----------------------------------------
        
        switch ($this->ipsclass->input['method'])
        {
        	case 'immediate':
        		$this->method = 'immediate';
        		break;
        	case 'delayed':
        		$this->method = 'delayed';
        		break;
        	case 'none':
        		$this->method = 'none';
        		break;
        	case 'daily':
        		$this->method = 'daily';
        		break;
        	case 'weekly':
        		$this->method = 'weekly';
        		break;
        	default:
        		$this->method = 'delayed';
        		break;
        }
        
        
        $this->ipsclass->input['t'] = intval($this->ipsclass->input['t']);
        $this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
        
        //-----------------------------------------
        // Get the forum info based on the forum ID, get the category name, ID, and get the topic details
        //-----------------------------------------
        
        if ($this->type == 'forum')
        {
        	$this->topic = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ];
        }
        else
        {
        	$row = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => 'tid='.$this->ipsclass->input['t'] ) );
        	
        	if( !is_array($row) OR !count($row) )
        	{
	        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        	}

        	$this->topic = array_merge( $row, $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ] );
        }
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if ( ! $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ] )
        {
        	if ($is_sub != 1)
        	{
            	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
            }
            else
            {
            	return;
            }
        }
        
        //-----------------------------------------
        // Error out if we can not find the topic
        //-----------------------------------------
        
        if ($this->type != 'forum')
        {
			if ( ! $this->topic['tid'] )
			{
				if ($is_sub != 1)
				{
					$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
				}
				else
				{
					return;
				}
			}
        }
        
        $this->base_url    = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?s={$this->ipsclass->session_id}";
        
        $this->base_url_NS = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}";
		
        //-----------------------------------------
        // Check viewing permissions, private forums,
        // password forums, etc
        //-----------------------------------------
        
        if (! $this->ipsclass->member['id'] )
        {
        	if ($is_sub != 1)
        	{
            	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'no_guests') );
            }
            else
            {
            	return;
            }
        }
        
        if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['read_perms'] ) != TRUE )
        {
			if ($is_sub != 1)
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'forum_no_access') );
			}
			else
			{
				return;
			}
		}
		
		if ($this->topic['password'] != "")
		{
			if ( $this->ipsclass->forums->forums_compare_password($this->topic['fid']) != TRUE )
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'forum_no_access') );
			}
		}
		
		//-----------------------------------------
		// Have we already subscribed?
		//-----------------------------------------
		
		if ($this->type == 'forum')
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'frid',
										  'from'   => 'forum_tracker',
										  'where'  => "forum_id='".$this->topic['id']."' AND member_id='".$this->ipsclass->member['id']."'" ) );
			$this->ipsclass->DB->simple_exec();
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'trid',
										  'from'   => 'tracker',
										  'where'  => "topic_id='".$this->topic['tid']."' AND member_id='".$this->ipsclass->member['id']."'" ) );
			$this->ipsclass->DB->simple_exec();
		}
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			if ($is_sub != 1)
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'already_sub') );
			}
			else
			{
				return;
			}
		}
		
		//-----------------------------------------
		// Add it to the DB
		//-----------------------------------------
		
		if ($this->type == 'forum')
		{
		
			$this->ipsclass->DB->do_insert( 'forum_tracker', array (
													  'member_id'        => $this->ipsclass->member['id'],
													  'forum_id'         => $this->ipsclass->input['f'],
													  'start_date'       => time(),
													  'forum_track_type' => $this->method,
										   )       );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'tracker', array (
												'member_id'        => $this->ipsclass->member['id'],
												'topic_id'         => $this->topic['tid'],
												'start_date'       => time(),
												'topic_track_type' => $this->method,
									 )       );
		}
		
		if ($is_sub != 1)
		{
			if ($this->type == 'forum')
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['sub_added'], "showforum={$this->topic['id']}" );
			}
			else
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['sub_added'], "showtopic={$this->topic['tid']}&amp;st={$this->ipsclass->input['st']}" );
			}
		}
		else
		{
			return;
		}
	}
}

?>