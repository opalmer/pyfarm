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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Topic Multi-Moderation
|   > Module written by Matt Mecham
|   > Date started: 14th May 2003
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_multi_moderate
{

	var $base_url;
	var $forumfunc = "";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "content";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "multimod";
	
	function auto_run()
	{
		$this->ipsclass->forums->forums_init();
		
		require ROOT_PATH.'sources/lib/admin_forum_functions.php';
		
		$this->forumfunc = new admin_forum_functions();
		$this->forumfunc->ipsclass =& $this->ipsclass;
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Topic multi-moderation home' );
		
		switch($this->ipsclass->input['code'])
		{
		
			case 'list':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_current();
				break;
				
			case 'new':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->do_form('new');
				break;
				
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_form('edit');
				break;
				
			case 'donew':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->do_save('new');
				break;
				
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_save('edit');
				break;
				
			case 'delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->do_delete();
				break;
				
			//-----------------------------------------
			
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_current();
				break;
		}
		
	}
	
	//-----------------------------------------
	// Rebuild Cache
	//-----------------------------------------
	
	function rebuild_cache()
	{
		$this->ipsclass->cache['multimod'] = array();
        	
		$this->ipsclass->DB->simple_construct( array(
								 'select' => '*',
								 'from'   => 'topic_mmod',
								 'order'  => 'mm_title'
						 )      );
							
		$this->ipsclass->DB->simple_exec();
					
		while ($i = $this->ipsclass->DB->fetch_row())
		{
			$this->ipsclass->cache['multimod'][ $i['mm_id'] ] = $i;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'multimod', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	//-----------------------------------------
	// DELETE!
	//-----------------------------------------
	
	function do_delete()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the MMOD ID, please try again");
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topic_mmod', 'where' => "mm_id=".intval($this->ipsclass->input['id']) ) );
		
		$this->rebuild_cache();
		
		$this->ipsclass->admin->save_log("Topic Multi-Mod removed");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");
		
	}
	
	//-----------------------------------------
	// SAVE!
	//-----------------------------------------
	
	function do_save($type='new')
	{
		$forums = array();
		
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		if ( $type == 'edit' )
		{
			if ( $this->ipsclass->input['id'] < 1 )
			{
				$this->ipsclass->admin->error("You must use a valid id");
			}
		}
		
		if ( $this->ipsclass->input['mm_title'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a valid title");
		}
		
		//-----------------------------------------
		// Check for forums...
		//-----------------------------------------
		
		$forums = $this->get_activein_forums();
		
		if ( ! $forums )
		{
			$this->ipsclass->admin->error("You must select some forums to activate with this multi-moderation suite");
		}
		
		if ( $this->ipsclass->input['topic_move'] == 'n' )
		{
			$this->ipsclass->admin->error("Incorrect forum chosen in the 'move to' section of the topic multi-moderation. Please note that you cannot choose to move the topic to a category");
		}
			
		$save = array(
						'mm_title'              => $this->ipsclass->input['mm_title'],
						'mm_enabled'            => 1,
						'topic_state'           => $this->ipsclass->input['topic_state'],
						'topic_pin'	            => $this->ipsclass->input['topic_pin'],
						'topic_move'            => $this->ipsclass->input['topic_move'],
						'topic_move_link'       => $this->ipsclass->input['topic_move_link'],
						'topic_title_st'        => $this->ipsclass->admin->make_safe($_POST['topic_title_st']),
						'topic_title_end'       => $this->ipsclass->admin->make_safe($_POST['topic_title_end']),
						'topic_reply'           => $this->ipsclass->input['topic_reply'],
						'topic_reply_content'   => $this->ipsclass->admin->make_safe($_POST['topic_reply_content']),
						'topic_reply_postcount' => $this->ipsclass->input['topic_reply_postcount'],
						'mm_forums'             => $forums,
						'topic_approve'         => $this->ipsclass->input['topic_approve'],
					 );
					 
		if ( $type == 'edit' )
		{
			$mm_id = $this->ipsclass->input['id'];
			
			$this->ipsclass->DB->do_update( 'topic_mmod', $save, 'mm_id='.$mm_id );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'topic_mmod', $save );
			
			$mm_id = $this->ipsclass->DB->get_insert_id();
		}
		
		$this->ipsclass->admin->save_log("Update topic multi-moderation entries ($type)");
		
		$this->rebuild_cache();
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");
		
		
	}
	
	//-----------------------------------------
	// SHOW MM FORM
	//-----------------------------------------
	
	function do_form($type='new')
	{
		$this->ipsclass->admin->page_detail = "Multi moderation allows you to combine moderation actions to create easy to use shortcuts to several moderation options.";
		$this->ipsclass->admin->page_title  = "Topic Multi-Moderation";
		
		$form_code   = 'donew';
		$description = 'Add a new topic multi-moderation';
		$button      = "Add New Multi-Moderation";
		$id			 = 0;
		$topic_mm	 = array( 'mm_forums' => '', 'mm_title' => '', 'topic_title_st' => '',
								'topic_title_end' => '', 'topic_state' => '', 'topic_pin' => '',
								'topic_approve' => '', 'topic_move' => '', 'topic_move_link' => '',
								'topic_reply' => '', 'topic_reply_content' => '', 'topic_reply_postcount' => '' );
		
		if ( $type == 'edit' )
		{
			$id = intval($this->ipsclass->input['id']);
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topic_mmod', 'where' => "mm_id=$id" ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( ! $topic_mm = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->admin->error("Could not retrieve the information ($id)");
			}
			
			$form_code   = 'doedit';
			$description = 'Edit the topic multi-moderation';
			$button      = "Edit Multi-Moderation";
		}
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $form_code ),
																 2 => array( 'act'   , 'multimod' ),
																 3 => array( 'id'    , $id        ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														)      );
		
		//-----------------------------------------
		
		$state_dd = array(
						  0 => array( 'leave', 'Leave' ),
						  1 => array( 'close', 'Close' ),
						  2 => array( 'open' , 'Open'  ),
					   );
					  
		$pin_dd   = array(
						  0 => array( 'leave', 'Leave' ),
						  1 => array( 'pin'  , 'Pin'   ),
						  2 => array( 'unpin', 'Unpin' ),
					    );
					    
		$app_dd   = array(
						  0 => array( '0', 'Leave' ),
						  1 => array( '1', 'Approve (Set Visible)'   ),
						  2 => array( '2', 'Unapprove (Set Invisibe)' ),
					    );
					  
		//-----------------------------------------
		
		
		
		$forum_html = "<select name='forums[]' class='textinput' size='15' multiple='multiple'>\n";
		
		$forum_html .= $topic_mm['mm_forums'] == '*'
				     ? "<option value='all' selected='selected'>-- ALL FORUMS --</option>\n"
					 : "<option value='all'>-- ALL FORUMS --</option>\n";		    
		
		$forum_jump = $this->forumfunc->ad_forums_forum_data();
			
		foreach ( $forum_jump as $i )
		{
			if ( strstr( ",".$topic_mm['mm_forums'].",", ",".$i['id']."," ) and $topic_mm['mm_forums'] != '*' )
			{
				$selected = ' selected="selected"';
			}
			else
			{
				$selected = "";
			}
			
			if ( isset($i['redirect_on']) AND $i['redirect_on'] == 1 )
			{
				continue;
			}
			
			$fporum_jump[] = array( $i['id'], $i['depthed_name'] );
			
			$forum_html  .= "<option value=\"{$i['id']}\" $selected>{$i['depthed_name']}</option>\n";

		}
		
		$forum_html  .= "</select>";
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "60%" );

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Topic Multi-Moderation", $description );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Title for this Multi-Moderation Suite?</b>" ,
												  $this->ipsclass->adskin->form_input("mm_title", $topic_mm['mm_title'] )
									     )      );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Active in Forums...</b><br>You may choose more than one" ,
												  $forum_html
									     )      );							     
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( 'Moderation Options', 'left', 'tablesubheader' );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Add to <i>START</i> of topic title?</b>" ,
												  $this->ipsclass->adskin->form_input("topic_title_st", $topic_mm['topic_title_st'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Add to <i>END</i> of topic title?</b>" ,
												  $this->ipsclass->adskin->form_input("topic_title_end", $topic_mm['topic_title_end'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Alter topic state?</b>" ,
												  $this->ipsclass->adskin->form_dropdown("topic_state", $state_dd, $topic_mm['topic_state'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Alter pinned state?</b>" ,
												  $this->ipsclass->adskin->form_dropdown("topic_pin", $pin_dd, $topic_mm['topic_pin'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Alter approved state?</b>" ,
												  $this->ipsclass->adskin->form_dropdown("topic_approve", $app_dd, $topic_mm['topic_approve'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Move topic?</b>" ,
					    						  $this->ipsclass->adskin->form_dropdown("topic_move", array_merge( array( 0 => array('-1', 'Don\'t Move' ) ), $fporum_jump ), $topic_mm['topic_move'] )
					    						  ."<br />".$this->ipsclass->adskin->form_checkbox('topic_move_link', $topic_mm['topic_move_link'] )."<strong>Leave a link to the source topic?</strong>"
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( 'Post Options', 'left', 'tablesubheader' );
	
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Add a reply to the topic?</b><br>HTML enabled" ,
												  "Enable this reply? &nbsp;".$this->ipsclass->adskin->form_yes_no('topic_reply', $topic_mm['topic_reply'] )
												  ."<br />"
												  . $this->ipsclass->adskin->form_textarea("topic_reply_content", $topic_mm['topic_reply_content'] )
												  ."<br />".$this->ipsclass->adskin->form_checkbox('topic_reply_postcount', $topic_mm['topic_reply_postcount'] )."<strong>Increment poster's post count?</strong>"
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($button);
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		
		$this->ipsclass->admin->output();
	
	}
	
	
	//-----------------------------------------
	// SHOW ALL AVAILABLE MM's
	//-----------------------------------------
	
	function list_current()
	{
		$this->ipsclass->admin->page_detail = "Multi moderation allows you to combine moderation actions to create easy to use shortcuts to several moderation options.";
		$this->ipsclass->admin->page_title  = "Topic Multi-Moderation";
		
		
		$this->ipsclass->adskin->td_header[] = array( "Title"  , "50%" );
		$this->ipsclass->adskin->td_header[] = array( "Edit"   , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Remove" , "25%" );

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Current Topic Multi-Moderation" );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topic_mmod', 'order' => "mm_title" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
			
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
																		 "<strong>{$row['mm_title']}</strong>",
																		 "<center><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=edit&amp;id={$row['mm_id']}'>Edit</a></center>",
																		 "<center><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=delete&amp;id={$row['mm_id']}'>Remove</a></center>",
																)      );
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<center>None set up</center>");
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<div class='fauxbutton-wrapper'><span class='fauxbutton'><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=new'>Add New</a></span></div>", 'center', 'tablefooter' );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	
	}
	
	
	//-----------------------------------------
    // Get the active in forums
    //-----------------------------------------    
        
    function get_activein_forums()
    {
		$forumids = array();
    	
    	//-----------------------------------------
    	// Check for an array
    	//-----------------------------------------
    	
    	if ( is_array( $_POST['forums'] )  )
    	{
    	
    		if ( in_array( 'all', $_POST['forums'] ) )
    		{
    			//-----------------------------------------
    			// Searching all forums..
    			//-----------------------------------------
    			
    			return '*';
    		}
    		else
    		{
				//-----------------------------------------
				// Go loopy loo
				//-----------------------------------------
				
				foreach( $_POST['forums'] as $l )
				{
					if ( $this->ipsclass->forums->forum_by_id[ $l ] )
					{
						$forumids[] = intval($l);
					}
				}
				
				//-----------------------------------------
				// Do we have cats? Give 'em to Charles!
				//-----------------------------------------
				
				if ( count( $forumids  ) )
				{
					foreach( $forumids  as $f )
					{
						$children = $this->ipsclass->forums->forums_get_children( $f );
						
						if ( is_array($children) and count($children) )
						{
							$forumids  = array_merge( $forumids , $children );
						}
					}
				}
				else
				{
					//-----------------------------------------
					// No forums selected / we have available
					//-----------------------------------------
					
					return;
				}
    		}
		}
		else
		{
			//-----------------------------------------
			// Not an array...
			//-----------------------------------------
			
			if ( $this->ipsclass->input['forums'] == 'all' )
			{
				return '*';
			}
			else
			{
				if ( $this->ipsclass->input['forums'] != "" )
				{
					$l = intval($this->ipsclass->input['forums']);
					
					//-----------------------------------------
					// Single forum
					//-----------------------------------------
					
					if ( $this->ipsclass->forums->forum_by_id[ $l ] )
					{
						$forumids[] = intval($l);
					}
					
					if ( $this->ipsclass->input['searchsubs'] == 1 )
					{
						$children = $this->ipsclass->forums->forums_get_children( $f );
						
						if ( is_array($children) and count($children) )
						{
							$forumids  = array_merge( $forumids , $children );
						}
					}
				}
			}
		}
		
		return implode( ",", $forumids );
    }
}


?>