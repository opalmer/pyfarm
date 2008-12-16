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
|   > Post core module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|   > Module Version 1.0.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class post
{
	var $ipsclass;
	var $han_post;
	
	# Code remap
	var $do_into_code = array();
	
    /*-------------------------------------------------------------------------*/
	// Our constructor, load words, load skin, print the topic listing
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
        //-----------------------------------------
    	// Get the sync module
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			$this->modules->ipsclass =& $this->ipsclass;
		}
		
		if( $this->ipsclass->input['CODE'] == 'image' )
		{
			$this->show_captcha_image();
		}
		
		//-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        $this->ipsclass->input['t']  = intval($this->ipsclass->input['t']);
        $this->ipsclass->input['p']  = intval($this->ipsclass->input['p']);
        $this->ipsclass->input['f']  = intval($this->ipsclass->input['f']);
        $this->ipsclass->input['st'] = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
        
        //-----------------------------------------
        // At some point in the future, I want to
        // remove the CODE rubbish and move to a
        // more human-esque 'do' command.
        //-----------------------------------------
        
        $this->do_into_code = array( 'new_post'      => '00',
        							 'new_post_do'   => '01',
        							 'reply_post'    => '02',
        							 'reply_post_do' => '03',
        							 'edit_post'     => '08',
        							 'edit_post_do'  => '09',
        							 'poll_add'      => '14',
        							 'poll_add_do'   => '15' );
        
        if ( isset($this->ipsclass->input['do']) AND $this->ipsclass->input['do'] )
        {
        	$this->ipsclass->input['CODE'] = $this->do_into_code[ $this->ipsclass->input['do'] ];
        }
        
		//-----------------------------------------
		// Load post handler
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/handlers/han_post.php' );
        $this->han_post            =  new han_post();
        $this->han_post->ipsclass  =& $this->ipsclass;
 		$this->han_post->forum     =  $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ];
        $this->han_post->md5_check =  $this->ipsclass->return_md5_check();
        $this->han_post->modules   =  $this->modules;
        
        //-----------------------------------------
        // Set up object array
        //-----------------------------------------
        
        $this->han_post->obj['preview_post'] = isset($this->ipsclass->input['preview']) ? $this->ipsclass->input['preview'] : NULL;
        $this->han_post->obj['moderate']     = $this->ipsclass->member['g_avoid_q'] ? 0 : intval($this->han_post->forum['preview_posts']);
        
        //-----------------------------------------
        // Make sure member isn't moderated
        //-----------------------------------------
        
        $this->_check_post_mod();
        
        //-----------------------------------------
        // Is this forum switched off?
        //-----------------------------------------
        
        if ( ! $this->han_post->forum['status'] )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'forum_read_only') );
        }
        
        //-----------------------------------------
        // Check access
        //-----------------------------------------
        
        $this->ipsclass->forums->forums_check_access( $this->han_post->forum['id'], 1 );
		
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if ( ! $this->han_post->forum['id'] )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
        
        if ( ! $this->han_post->forum['sub_can_post'] )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }        
        
        //-----------------------------------------
        // Are we allowed to post at all?
        //-----------------------------------------
        
        if ( $this->ipsclass->member['id'] )
        {
        	if ( $this->ipsclass->member['restrict_post'] )
        	{
        		if ( $this->ipsclass->member['restrict_post'] == 1 )
        		{
        			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'posting_off') );
        		}
        		
        		$post_arr = $this->ipsclass->hdl_ban_line( $this->ipsclass->member['restrict_post'] );
        		
        		if ( time() >= $post_arr['date_end'] )
        		{
        			//-----------------------------------------
        			// Update this member's profile
        			//-----------------------------------------
        			
					$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
																  'set'    => 'restrict_post=0',
																  'where'  => "id=".intval($this->ipsclass->member['id'])
														)       );
										
					$this->ipsclass->DB->simple_exec();
        		}
        		else
        		{
        			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'posting_off_susp', 'EXTRA' => $this->ipsclass->get_date($post_arr['date_end'], 'LONG', 1) ) );
        		}
        	}
        	
        	//-----------------------------------------
        	// Flood check..
        	//-----------------------------------------
        	
        	if (     $this->ipsclass->input['CODE'] != "08"
        		 and $this->ipsclass->input['CODE'] != "09"
        		 and $this->ipsclass->input['CODE'] != "14"
        		 and $this->ipsclass->input['CODE'] != "15" )
        	{
				if ( $this->ipsclass->vars['flood_control'] > 0 )
				{
					if ($this->ipsclass->member['g_avoid_flood'] != 1)
					{
						if ( time() - $this->ipsclass->member['last_post'] < $this->ipsclass->vars['flood_control'] )
						{
							$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'flood_control' , 'EXTRA' => $this->ipsclass->vars['flood_control'] ) );
						}
					}
				}
			}
        }
        else if ( $this->ipsclass->is_bot == 1 )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'posting_off') );
        }
        
        //-----------------------------------------
        // Convert the code ID's into something
        // use mere mortals can understand....
        //-----------------------------------------
        
        $this->han_post->obj['action_codes'] = array ( '00'  => array( '0'  , 'new'     ),
													   '01'  => array( '1'  , 'new'     ),
													   '02'  => array( '0'  , 'reply'   ),
													   '03'  => array( '1'  , 'reply'   ),
													   '08'  => array( '0'  , 'edit'    ),
													   '09'  => array( '1'  , 'edit'    ),
													   '10'  => array( '0'  , 'poll'         ),
													   '11'  => array( '1'  , 'poll'         ),
													   '14'  => array( '0'  , 'poll_after'   ),
													   '15'  => array( '1'  , 'poll_after'   ),
													 );
        
        //-----------------------------------------								   
        // Make sure our input CODE element is legal.
        //-----------------------------------------
        
        if ( ! isset($this->han_post->obj['action_codes'][ $this->ipsclass->input['CODE'] ]) )
        {
        	$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
        }
      	
      	//-----------------------------------------
        // Init classes
        //-----------------------------------------
        
      	$this->han_post->method = $this->han_post->obj['action_codes'][ $this->ipsclass->input['CODE'] ][1];
      	$this->han_post->init();
      	
        //-----------------------------------------
        // Show form or process?
        //-----------------------------------------
        
        if ( $this->han_post->obj['action_codes'][ $this->ipsclass->input['CODE'] ][0] )
        {
        	//-----------------------------------------
        	// Make sure we have a valid auth key
        	//-----------------------------------------
        	
        	if ( $this->ipsclass->input['auth_key'] != $this->han_post->md5_check )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
			}
        	
        	//-----------------------------------------
        	// Make sure we have a "Guest" Name..
        	//-----------------------------------------
        	
        	$this->_check_guest_name();
        	$this->_check_double_post();
        	
        	$this->han_post->process_post();
        }
        else
        {
        	$this->han_post->show_form();
        }
	}
	
	/*-------------------------------------------------------------------------*/
	// Check for double post
	/*-------------------------------------------------------------------------*/
	
	function _check_double_post()
	{
		if ( $this->han_post->obj['preview_post'] == "" )
	   {
		   if ( preg_match( "/Post,.*,(01|03|07|11)$/", $this->ipsclass->location ) )
		   {
			   if ( time() - $this->ipsclass->lastclick < 2 )
			   {
				   if ( $this->ipsclass->input['CODE'] == '01' or $this->ipsclass->input['CODE'] == '11' )
				   {
					   //-----------------------------------------
					   // Redirect to the newest topic in the forum
					   //-----------------------------------------
					   
					   $this->ipsclass->DB->simple_construct( array( 'select' => 'tid',
																	 'from'   => 'topics',
																	 'where'  => "forum_id='".$this->han_post->forum['id']."' AND approved=1",
																	 'order'  => 'last_post DESC',
																	 'limit'  => array( 0, 1 )
														   )       );
										   
					   $this->ipsclass->DB->simple_exec();
			   
					   $topic = $this->ipsclass->DB->fetch_row();
			   
					   $this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$topic['tid']);
					   exit();
				   }
				   else
				   {
					   //-----------------------------------------
					   // It's a reply, so simply show the topic...
					   //-----------------------------------------
					   
					   $this->ipsclass->boink_it($this->ipsclass->base_url."showtopic=".$this->ipsclass->input['t']."&amp;view=getlastpost");
					   exit();
				   }
			   }
		   }
	   }
	}
	
	/*-------------------------------------------------------------------------*/
	// Check for guest's name
	/*-------------------------------------------------------------------------*/
	
	function _check_guest_name()
	{
		if ( ! $this->ipsclass->member['id'] )
		{
			$this->ipsclass->input['UserName'] = trim($this->ipsclass->input['UserName']);
			$this->ipsclass->input['UserName'] = str_replace( "<br />", "", $this->ipsclass->input['UserName']);
			$this->ipsclass->input['UserName'] = $this->ipsclass->input['UserName'] ? $this->ipsclass->input['UserName'] : $this->ipsclass->lang['global_guestname'];
			$this->ipsclass->input['UserName'] = (strlen($this->ipsclass->input['UserName']) > $this->ipsclass->vars['max_user_name_length']) ? $this->ipsclass->lang['global_guestname'] : $this->ipsclass->input['UserName'];
			
			if ($this->ipsclass->input['UserName'] != $this->ipsclass->lang['global_guestname'])
			{
				$this->ipsclass->DB->cache_add_query( 'login_getmember', array( 'username' => trim(strtolower($this->ipsclass->input['UserName'])) ) );
				$this->ipsclass->DB->cache_exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->ipsclass->input['UserName'] = $this->ipsclass->vars['guest_name_pre'].$this->ipsclass->input['UserName'].$this->ipsclass->vars['guest_name_suf'];
				}
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Check for member post queue
	/*-------------------------------------------------------------------------*/
	
	function _check_post_mod()
	{
		//-----------------------------------------
        // Does this member have mod_posts enabled?
		//-----------------------------------------
         
        if ( isset($this->ipsclass->member['mod_posts']) AND $this->ipsclass->member['mod_posts'] )
		{
			if ( $this->ipsclass->member['mod_posts'] == 1 )
			{
				$this->han_post->obj['moderate'] = 1;
			}
			else
			{
				$mod_arr = $this->ipsclass->hdl_ban_line( $this->ipsclass->member['mod_posts'] );
				
				if ( time() >= $mod_arr['date_end'] )
				{
					//-----------------------------------------
					// Update this member's profile
					//-----------------------------------------
					
					$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
												  				  'set'    => 'mod_posts=0',
												  				  'where'  => "id=".intval($this->ipsclass->member['id'])
													     )       );
										
					$this->ipsclass->DB->simple_exec();
					
					$this->han_post->obj['moderate'] = intval($this->han_post->forum['preview_posts']);
				}
				else
				{
					$this->han_post->obj['moderate'] = 1;
				}
			}
		}
	}
	
	
	function show_captcha_image()
	{
		if ( $this->ipsclass->input['rc'] == "" )
		{
			return false;
		}
	
		// Get the info from the db
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
									  				  'from'   => 'reg_antispam',
													  'where'  => "regid='".trim(addslashes($this->ipsclass->input['rc']))."'"
											 )      );
							 
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			return false;
		}
		
		//-----------------------------------------
		// Using GD?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['guest_captcha'] == 'gd' )
		{
			$this->ipsclass->show_gd_img($row['regcode']);
		}
		else
		{
			//-----------------------------------------
			// Using normal then, check for "p"
			//-----------------------------------------
			
			if ( $this->ipsclass->input['p'] == "" )
			{
				return false;
			}
			
			$p = intval($this->ipsclass->input['p']) - 1; //substr starts from 0, not 1 :p
			
			$this_number = substr( $row['regcode'], $p, 1 );
			
			$this->ipsclass->show_gif_img($this_number);
		}
	}		
	

	
}

?>