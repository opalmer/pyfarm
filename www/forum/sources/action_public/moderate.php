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
|   > Moderation core module
|   > Module written by Matt Mecham
|   > Date started: 19th February 2002
|
|   > Module Version 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class moderate
{
    var $output    = "";
    var $base_url  = "";
    var $html      = "";

    var $moderator = "";
    var $modfunc   = "";
    var $forum     = array( 'id' => 0 );
    var $topic     = array();
    
    var $upload_dir  = "";
	var $trash_forum = 0;
	var $trash_inuse = 0;
    
    /*-------------------------------------------------------------------------*/
	// Our constructor, load words, load skin, print the topic listing
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
		$post_array      = array( '04', '02', '20', '22', 'resync', 'prune_start', 'prune_finish', 'prune_move', 'editmember' );
        $not_forum_array = array( 'editmember' );
        
        //-----------------------------------------
        // Make sure this is a POST request
        // not a naughty IMG redirect
        //-----------------------------------------
        
        if ( ! in_array( $this->ipsclass->input['CODE'], $post_array ) )
        {
			if ( $_POST['act'] == '')
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'incorrect_use') );
			}
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['CODE'] != '02' and $this->ipsclass->input['CODE'] != '05')
        {
			if ($this->ipsclass->input['auth_key'] != $this->ipsclass->return_md5_check() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
			}
		}
        
        //-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
        $this->ipsclass->load_language('lang_mod');
        $this->ipsclass->load_template('skin_mod');
        
        //-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        if ( ! in_array( $this->ipsclass->input['CODE'], $not_forum_array ) )
        {
        	//-----------------------------------------
        	// t
        	//-----------------------------------------
        	
			if ($this->ipsclass->input['t'])
			{
				$this->ipsclass->input['t'] = intval($this->ipsclass->input['t']);
				
				if ( ! $this->ipsclass->input['t'] )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
				}
				else
				{
					$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.intval($this->ipsclass->input['t']) ) );
					$this->ipsclass->DB->simple_exec();
					
					$this->topic = $this->ipsclass->DB->fetch_row();
					
					if (empty($this->topic['tid']))
					{
						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
					}
					
					if ( $this->ipsclass->input['f'] AND ( $this->topic['forum_id'] != $this->ipsclass->input['f'] ) ) 
					{ 
						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
					}
				}
			}
			
			//-----------------------------------------
			// p
			//-----------------------------------------
			
			if ($this->ipsclass->input['p'])
			{
				$this->ipsclass->input['p'] = intval($this->ipsclass->input['p']);
				
				if (! $this->ipsclass->input['p'] )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
				}
			}
			
			//-----------------------------------------
			// F?
			//-----------------------------------------
			
			$this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
			
			if ( ! $this->ipsclass->input['f'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1,'MSG' => 'missing_files') );
			}
			
			$this->ipsclass->input['st'] = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
			
			//-----------------------------------------
			// Get the forum info based on the forum ID,
			//-----------------------------------------
			
			$this->forum = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ];
			
			$this->base_url = $this->ipsclass->base_url;
			
			//-----------------------------------------
			// Are we a moderator?
			//-----------------------------------------
			
			if ( isset( $this->ipsclass->member['_moderator'][ $this->ipsclass->input['f'] ]) AND $this->ipsclass->member['_moderator'][ $this->ipsclass->input['f'] ] )
			{
				$this->moderator = $this->ipsclass->member['_moderator'][ $this->ipsclass->input['f'] ];
			}
        }
        
        //-----------------------------------------
        // Load mod module...
        //-----------------------------------------
        
        require( ROOT_PATH.'sources/lib/func_mod.php');
        
        $this->modfunc = new func_mod();
        $this->modfunc->ipsclass =& $this->ipsclass;
        
        $this->modfunc->init($this->forum);
        
        $this->upload_dir = $this->ipsclass->vars['upload_dir'];
        
        //-----------------------------------------
        // Trash-can set up
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['forum_trash_can_enable'] and $this->ipsclass->vars['forum_trash_can_id'] )
        {
        	if ( $this->ipsclass->cache['forum_cache'][ $this->ipsclass->vars['forum_trash_can_id'] ]['sub_can_post'] )
        	{
        		if ( $this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] )
        		{
        			$this->trash_forum = $this->ipsclass->vars['forum_trash_can_use_radmin'] ? $this->ipsclass->vars['forum_trash_can_id'] : 0;
        		}
        		else if ( $this->ipsclass->member['g_access_cp'] )
        		{
        			$this->trash_forum = $this->ipsclass->vars['forum_trash_can_use_admin'] ? $this->ipsclass->vars['forum_trash_can_id'] : 0;
        		}
        		else if ( $this->ipsclass->member['g_is_supmod'] )
        		{
        			$this->trash_forum = $this->ipsclass->vars['forum_trash_can_use_smod'] ? $this->ipsclass->vars['forum_trash_can_id'] : 0;
        		}
        		else if ( $this->ipsclass->member['is_mod'] )
        		{
        			$this->trash_forum = $this->ipsclass->vars['forum_trash_can_use_mod'] ? $this->ipsclass->vars['forum_trash_can_id'] : 0;
        		}
        		else
        		{
	        		$this->trash_forum = $this->ipsclass->vars['forum_trash_can_id'];
        		}
        	}
        }
      
        //-----------------------------------------
        // Convert the code ID's into something
        // use mere mortals can understand....
        //-----------------------------------------
        
        switch ($this->ipsclass->input['CODE'])
        {
        	case '02':
        		$this->move_form();
        		break;
        	case '03':
        		$this->delete_form();
        		break;
        	case '04':
        		$this->delete_post();
        		break;
        	case '05':
        		$this->edit_form();
        		break;
        	case '00':
        		$this->close_topic();
        		break;
        	case '01':
        		$this->open_topic();
        		break;
        	case '08':
        		$this->delete_topic();
        		break;
        	case '12':
        		$this->do_edit();
        		break;
        	case '14':
        		$this->do_move();
        		break;
        	case '15':
        		$this->pin_topic();
        		break;
        	case '16':
        		$this->unpin_topic();
        		break;
        	case '17':
        		$this->rebuild_topic();
        		break;
        	//-----------------------------------------
        	// Unsubscribe
        	//-----------------------------------------
        	case '30':
        		$this->unsubscribe_all_form();
        		break;
        	case '31':
        		$this->unsubscribe_all();
        		break;
        	//-----------------------------------------
        	// Merge Start
        	//-----------------------------------------
        	case '60':
        		$this->merge_start();
        		break;
        	case '61':
        		$this->merge_complete();
        		break;
        	//-----------------------------------------
        	// Topic History
        	//-----------------------------------------
        	case '90':
        		$this->topic_history();
        		break;
        	//-----------------------------------------
        	// Multi---
        	//-----------------------------------------	
        	case 'topicchoice':
        		$this->multi_topic_modify();
        		break;
        	//-----------------------------------------
        	// Multi---
        	//-----------------------------------------	
        	case 'postchoice':
        		$this->multi_post_modify();
        		break;
        	//-----------------------------------------
        	// Resynchronize Forum
        	//-----------------------------------------
        	case 'resync':
        		$this->resync_forum();
        		break;
        	//-----------------------------------------
        	// Prune / Move Topics
        	//-----------------------------------------
        	case 'prune_start':
        		$this->prune_start();
        		break;
        	case 'prune_finish':
        		$this->prune_finish();
        		break;
        	case 'prune_move':
        		$this->prune_move();
        		break;
        	//-----------------------------------------
        	// Add. topic view func.
        	//-----------------------------------------
        	case 'topic_approve':
        		$this->topic_approve_alter('approve');
        		break;
        	case 'topic_unapprove':
        		$this->topic_approve_alter('unapprove');
        		break;
        	//-----------------------------------------
        	// Edit member
        	//-----------------------------------------
        	case 'editmember':
        		$this->edit_member();
        		break;
        	default:
        		$this->moderate_error();
        		break;
        }
        
        // If we have any HTML to print, do so...
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit member
	/*-------------------------------------------------------------------------*/
	
	function edit_member()
	{
		$mid = intval($this->ipsclass->input['mid']) ? intval($this->ipsclass->input['mid']) : intval($this->ipsclass->input['member']);
		
		//-----------------------------------------
		// Check Permissions
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['g_is_supmod'] )
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $parser           = new parse_bbcode();
        $parser->ipsclass = $this->ipsclass;
		
		$parser->parse_html    = intval($this->ipsclass->vars['sig_allow_html']);
		$parser->parse_nl2br   = 1;
		$parser->parse_smilies = 0;
		$parser->parse_bbcode  = $this->ipsclass->vars['sig_allow_ibc'];
		
		$this->ipsclass->load_language( 'lang_post' );
			
		//-----------------------------------------
		// Got anyfink?
		//-----------------------------------------
		 
		if ( ! $mid )
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'profile_get_all' , array( 'mid' => $mid ) );
		
		$this->ipsclass->DB->cache_exec_query();
	
		$member = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		// Show Form?
		//-----------------------------------------
		
		if ( !isset($this->ipsclass->input['checked']) OR !$this->ipsclass->input['checked'] )
		{
			$this->output .= $this->html_start_form( array( 1 => array( 'CODE'   , 'editmember'),
															4 => array( 'mid'    , $mid        ),
															5 => array( 'checked', 1           ),
												   )      );
												   
    		//-----------------------------------------
			// No editing of admins!
			//-----------------------------------------
			
			if ( ! $this->ipsclass->member['g_access_cp'] )
			{
				if ( $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_access_cp'] )
				{ 
					$this->moderate_error('cp_admin_user');
					return;
				}
			}
			
			$editable['signature']  			= $parser->pre_edit_parse( $member['signature'] );
			$editable['location']   			= $member['location'];
			$editable['interests']  			= $this->ipsclass->my_br2nl($member['interests']);
			$editable['website']    			= $member['website'];
			$editable['id']         			= $member['id'];
			$editable['members_display_name']   = $member['members_display_name'];
			$editable['aim_name']   			= $member['aim_name'];
			$editable['icq_number'] 			= $member['icq_number'];
			$editable['msnname']    			= $member['msnname'];
			$editable['yahoo']      			= $member['yahoo'];
			
			$optional_output = "";
			
			//-----------------------------------------
			// Profile fields
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
			$fields = new custom_fields( $this->ipsclass->DB );
			
			$fields->member_id   = $this->ipsclass->member['id'];
			$fields->mem_data_id = $member['id'];
			$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
			$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
			
			$fields->init_data();
			$fields->parse_to_edit();
			
			foreach( $fields->out_fields as $id => $data )
			{
				if ( $fields->cache_data[ $id ]['pf_type'] == 'drop' )
				{
					$form_element = $this->ipsclass->compiled_templates['skin_mod']->field_dropdown( 'field_'.$id, $data );
				}
				else if ( $fields->cache_data[ $id ]['pf_type'] == 'area' )
				{
					$form_element = $this->ipsclass->compiled_templates['skin_mod']->field_textarea( 'field_'.$id, $data );
				}
				else
				{
					$form_element = $this->ipsclass->compiled_templates['skin_mod']->field_textinput( 'field_'.$id, $data );
				}
				
				$optional_output .= $this->ipsclass->compiled_templates['skin_mod']->field_entry( $fields->field_names[ $id ], $fields->field_desc[ $id ], $form_element );
			}
			
			//-----------------------------------------
			// Show?
			//-----------------------------------------
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->edit_user_form($editable, $optional_output);
    	
			$this->page_title = $this->ipsclass->lang['cp_em_title'];
			$this->nav[]      = "<a href='{$this->ipsclass->base_url}showuser={$mid}'>{$this->ipsclass->lang['cp_vp_title']}</a>";
			$this->nav[]      = $this->ipsclass->lang['cp_em_title'];
			
		}
		//-----------------------------------------
		// Do edit
		//-----------------------------------------
		else
		{
			$this->ipsclass->input['signature'] = $parser->pre_display_parse( $parser->pre_db_parse( $this->ipsclass->input['signature'] ) );
										   
			if ($parser->error != "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => $parser->error) );
			}
			
			$bet = array(  'website'     => $this->ipsclass->input['website'],
						   'icq_number'  => $this->ipsclass->input['icq_number'],
						   'aim_name'    => $this->ipsclass->input['aim_name'],
						   'yahoo'       => $this->ipsclass->input['yahoo'],
						   'msnname'     => $this->ipsclass->input['msnname'],
						   'location'    => $this->ipsclass->input['location'],
						   'interests'   => $this->ipsclass->input['interests'],
						   'signature'   => $this->ipsclass->input['signature'],
						);
			
			if ( $this->ipsclass->input['avatar'] == 1 )
			{
				$bet['avatar_location'] = "";
				$bet['avatar_size']     = "";
				$this->bash_uploaded_avatars($mid);
			}
			
			if ( $this->ipsclass->input['photo'] == 1 )
			{
				$this->bash_uploaded_photos($mid);
				
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'profile_portal', 'where' => "pp_member_id=".intval($mid) ) );
				$this->ipsclass->DB->simple_exec();

				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_main_photo'   => '',
														   				     'pp_main_width'   => '',
														   				     'pp_main_height'  => '',
																		     'pp_thumb_photo'  => '',
																		     'pp_thumb_width'  => '',
																		     'pp_thumb_height' => '' ), 'pp_member_id='.$mid );
				}
				else
				{
					$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_main_photo'   => '',
														   				     'pp_main_width'   => '',
														   				     'pp_main_height'  => '',
																		     'pp_thumb_photo'  => '',
																		     'pp_thumb_width'  => '',
																		     'pp_thumb_height' => '',
																		     'pp_member_id'    => $mid ) );
				}
				
			}
			
			//-----------------------------------------
			// Write it to the DB.
			//-----------------------------------------
			
			if ( $mem = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'id', 'from' => 'member_extra', 'where' => 'id='.$mid ) ) )
			{
				$this->ipsclass->DB->do_update( 'member_extra', $bet, 'id='.$mid );
			}
			else
			{
				$bet['id'] = $mid;
				$this->ipsclass->DB->do_insert( 'member_extra', $bet );
			}
			
			//-----------------------------------------
			// Custom profile field stuff
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
			$fields = new custom_fields( $this->ipsclass->DB );
			
			$fields->member_id   = $this->ipsclass->member['id'];
	
			$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
			$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
			
			$fields->init_data();
			$fields->parse_to_save();
			
			//-----------------------------------------
			// Custom profile field stuff
			//-----------------------------------------
			
			if ( count( $fields->out_fields ) )
			{
				//-----------------------------------------
				// Do we already have an entry in
				// the content table?
				//-----------------------------------------
				
				$test = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'member_id', 'from' => 'pfields_content', 'where' => 'member_id='.$mid ) );
				
				if ( $test['member_id'] )
				{
					//-----------------------------------------
					// We have it, so simply update
					//-----------------------------------------
					
					$this->ipsclass->DB->do_update( 'pfields_content', $fields->out_fields, 'member_id='.$mid );
				}
				else
				{
					$fields->out_fields['member_id'] = $mid;
					
					$this->ipsclass->DB->do_insert( 'pfields_content', $fields->out_fields );
				}
			}
			
			//-----------------------------------------
			// Member sync?
			//-----------------------------------------
			
			if ( USE_MODULES == 1 )
			{
				require ROOT_PATH."modules/ipb_member_sync.php";
			
				$this->modules = new ipb_member_sync();
				$this->modules->ipsclass =& $this->ipsclass;
				
				$bet['id'] = $mid;
				$this->modules->register_class($this);
				$this->modules->on_profile_update($bet, $custom_fields);
			}
			
			$this->moderate_log("{$this->ipsclass->lang['acp_edited_profile']} {$member['members_display_name']}");
			
			$this->ipsclass->boink_it( $this->ipsclass->base_url.'act=mod&CODE=editmember&auth_key='.$this->ipsclass->return_md5_check().'&mid='.$mid.'&tid='.time() );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Post act
	/*-------------------------------------------------------------------------*/
	
	function multi_post_modify()
	{
		$this->pids  = $this->get_pids();
		
		if ( count( $this->pids ) )
		{
			switch ( $this->ipsclass->input['tact'] )
			{
				case 'approve':
					$this->multi_approve_post(1);
					break;
				case 'unapprove':
					$this->multi_approve_post(0);
					break;
				case 'delete':
					$this->multi_delete_post();
					break;
				case 'merge':
					$this->multi_merge_post();
					break;
				case 'split':
					$this->multi_split_topic();
					break;
				case 'move':
					$this->multi_move_post();
					break;
				default:
					
					break;
			}
		}
		
		$this->ipsclass->my_setcookie('modpids', '', 0);
		
		if ( $this->topic['tid'] )
		{
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['cp_redirect_posts'], "showtopic=".$this->topic['tid'].'&amp;st='.intval($this->ipsclass->input['st']) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Move Posts
	/*-------------------------------------------------------------------------*/
	
	function multi_move_post()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['split_merge'] == 1)
		{
			$passed = 1;
		}
		else {
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		 
		if ( ! $this->topic['tid'] )
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Get post parser
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 0;
        
		if ( $this->ipsclass->input['checked'] != 1 )
		{
			$jump_html = $this->ipsclass->build_forum_jump(0,1,1);
		
			$this->output .= $this->html_start_form( array( 1 => array( 'CODE'   , 'postchoice'        ),
															2 => array( 't'      , $this->topic['tid'] ),
															3 => array( 'f'      , $this->forum['id']  ),
															4 => array( 'tact'   , 'move' ),
															5 => array( 'checked', 1      ),
												   )      );
												  
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['cmp_title'].": ".$this->forum['name']." -&gt; ".$this->topic['title'] );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->move_post_body();
			
			//-----------------------------------------
			// Display the posty wosty's
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array(
										  'select' => 'post, pid, post_date, author_id, author_name',
										  'from'   => 'posts',
										  'where'  => "pid IN (".implode(",", $this->pids).")",
										  'order'  => 'post_date'
								 )      );
								 
			$post_query = $this->ipsclass->DB->simple_exec();
			
			$post_count = 0;
			
			while ( $row = $this->ipsclass->DB->fetch_row($post_query) )
			{
				//-----------------------------------------
				// Limit posts to 200 chars to stop shite
				// loads of pages
				//-----------------------------------------
				
				if ( strlen($row['post']) > 800 )
				{
					$row['post']   = $this->parser->pre_edit_parse($row['post']);
					$row['post']   = substr($row['post'], 0, 800) . '...';
				}
				
				$row['date']   = $this->ipsclass->get_date( $row['post_date'], 'LONG' );
				
				$row['st_top_bit'] = sprintf( $this->ipsclass->lang['st_top_bit'], $row['author_name'], $row['date'] );
					
				$row['post_css'] = $post_count % 2 ? 'row1' : 'row2';
					
				$this->output .= $this->ipsclass->compiled_templates['skin_mod']->split_row( $row );
				
				$post_count++;
			}
			
			//-----------------------------------------
			// print my bottom, er, the bottom
			//-----------------------------------------
					
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->split_end_form( $this->ipsclass->lang['cmp_submit'] );
			
			$this->page_title = $this->ipsclass->lang['cmp_title'].": ".$this->topic['title'];
			
			$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
								 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
							   );
							   
			$this->ipsclass->print->add_output( $this->output );
        	$this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
		}
		else
		{
			//-----------------------------------------
			// PROCESS Check the input
			//-----------------------------------------
			
			if ( ! intval($this->ipsclass->input['topic_url']) )
			{
				preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $this->ipsclass->input['topic_url'], $match );
		
				$old_id = intval(trim($match[3]));
			}
			else
			{
				$old_id = intval($this->ipsclass->input['topic_url']);
			}
			
			if ($old_id == "")
			{
				$this->ipsclass->input['checked'] = 0;
				$this->output = $this->ipsclass->compiled_templates['skin_mod']->warn_errors( $this->ipsclass->lang['cmp_notopic'] );
				$this->multi_move_post();
			}
			
			//-----------------------------------------
			// Grab topic
			//-----------------------------------------
			
			$move_to_topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$old_id ) );
			
			if ( ! $move_to_topic['tid'] or ! $this->ipsclass->forums->forum_by_id[ $move_to_topic['forum_id'] ]['id'] )
			{
				$this->ipsclass->input['checked'] = 0;
				$this->output = $this->ipsclass->compiled_templates['skin_mod']->warn_errors( $this->ipsclass->lang['cmp_notopic'] );
				$this->multi_move_post();
			}
			
			//-----------------------------------------
			// Get the post ID's to split
			//-----------------------------------------
	
			$ids = array();
			
			foreach ($this->ipsclass->input as $key => $value)
			{
				if ( preg_match( "/^post_(\d+)$/", $key, $match ) )
				{	
					if ($this->ipsclass->input[$match[0]])
					{
						$ids[] = $match[1];
					}
				}
			}
			
			$ids = $this->ipsclass->clean_int_array( $ids );
			
			$affected_ids = count($ids);
			
			//-----------------------------------------
			// Do we have enough?
			//-----------------------------------------
			
			if ($affected_ids < 1)
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'split_not_enough' ) );
			}
			
			//-----------------------------------------
			// Do we choose too many?
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}" ) );
			$this->ipsclass->DB->simple_exec();
			
			$count = $this->ipsclass->DB->fetch_row();
			
			if ( $affected_ids >= $count['cnt'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'split_too_much' ) );
			}
			
			//-----------------------------------------
			// Complete the PID string
			//-----------------------------------------
			
			$pid_string = implode( ",", $ids );
			
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'posts', array( 'topic_id' => $move_to_topic['tid'], 'new_topic' => 0 ), "pid IN($pid_string)" ); 
			
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" ); 
			
			//-----------------------------------------
			// Is first post queued for new topic?
			//-----------------------------------------
			
			$topic_approved = 1;
			
			$this->ipsclass->DB->build_query( array( 'select'   => 'pid, queued',
													 'from'     => 'posts',
													 'where'    => "topic_id={$move_to_topic['tid']}",
													 'order'    => 'pid ASC',
													 'limit'    => array(0,1),
											)      );
			
			$this->ipsclass->DB->exec_query();
			
			$first_post = $this->ipsclass->DB->fetch_row();
			
			if( $first_post['queued'] )
			{
				$this->ipsclass->DB->do_update( 'topics', array( 'approved' => 0 ), "tid={$move_to_topic['tid']}" );
				$this->ipsclass->DB->do_update( 'posts', array( 'queued' => 0 ), 'pid='.$first_post['pid'] );
			}
			
			//-----------------------------------------
			// Is first post queued for old topic?
			//-----------------------------------------
			
			$topic_approved = 1;
			
			$this->ipsclass->DB->build_query( array( 'select'   => 'pid, queued',
													 'from'     => 'posts',
													 'where'    => "topic_id={$this->topic['tid']}",
													 'order'    => 'pid ASC',
													 'limit'    => array(0,1),
											)      );
			
			$this->ipsclass->DB->exec_query();
			
			$other_first_post = $this->ipsclass->DB->fetch_row();
			
			if( $other_first_post['queued'] )
			{
				$this->ipsclass->DB->do_update( 'topics', array( 'approved' => 0 ), "tid={$this->topic['tid']}" );
				$this->ipsclass->DB->do_update( 'posts', array( 'queued' => 0 ), 'pid='.$other_first_post['pid'] );
			}	
			
			//-----------------------------------------
			// Rebuild the topics
			//-----------------------------------------
			
			$this->modfunc->rebuild_topic($move_to_topic['tid']);
			$this->modfunc->rebuild_topic($this->topic['tid']);
			
			//-----------------------------------------
			// Update the forum(s)
			//-----------------------------------------
			
			$this->modfunc->forum_recount($this->topic['forum_id']);
			
			if ($this->topic['forum_id'] != $move_to_topic['forum_id'])
			{
				$this->modfunc->forum_recount($move_to_topic['forum_id']);
			}
			
			$this->moderate_log( sprintf( $this->ipsclass->lang['acp_moved_posts'], $this->topic['title'], $move_to_topic['title'] ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Split topic
	/*-------------------------------------------------------------------------*/
	
	function multi_approve_post($approve=1)
	{
		$approve_topic = 1;
		$queued_post   = 0;
		
		if ( $approve != 1 )
		{
			$approve_topic = 0;
			$queued_post   = 1;
		}
		
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['post_q'] == 1)
		{
			$passed = 1;
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1)
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Did we get the first post too?
		//-----------------------------------------
		
		if ( strstr( ",".implode(",",$this->pids).",", ",".$this->topic['topic_firstpost']."," ) )
		{
			$this->ipsclass->DB->do_update( 'topics', array( 'approved' => $approve_topic ), 'tid='.$this->topic['tid'] );
			
			//-----------------------------------------
			// Don't actually un-approve first post
			// But allow approve
			//-----------------------------------------
			
			if ( $queued_post )
			{
				$tmp = $this->pids;
				
				$this->pids = array();
				
				foreach( $tmp as $t )
				{
					if ( $t != $this->topic['topic_firstpost'] )
					{
						$this->pids[] = $t;
					}
				}
			}
		}
		
		if ( count($this->pids) )
		{
			$this->ipsclass->DB->do_update( 'posts', array( 'queued' => $queued_post ), 'topic_id=' . $this->topic['tid'] . ' AND pid IN ('. implode(",", $this->pids) .')' );
		}
		
		if( $approve )
		{
			$this->moderate_log( sprintf( $this->ipsclass->lang['acp_approved_posts'], count($this->pids), $this->topic['title'] ) );
		}
		else
		{
			$this->moderate_log( sprintf( $this->ipsclass->lang['acp_unapproved_posts'], count($this->pids), $this->topic['title'] ) );
		}
		
		$this->modfunc->rebuild_topic( $this->topic['tid'] );
		$this->modfunc->forum_recount( $this->topic['forum_id'] );
		$this->modfunc->stats_recount();
	}
		
	/*-------------------------------------------------------------------------*/
	// Split topic
	/*-------------------------------------------------------------------------*/
	
	function multi_split_topic()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		else if ( $this->moderator['split_merge'] == 1 or $this->trash_inuse == 1 )
		{
			$passed = 1;
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1)
		{
			$this->moderate_error();
		}
		 
		if ( ! $this->topic['tid'] )
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Get post parser
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 0;
        
		if ( $this->ipsclass->input['checked'] != 1 )
		{
			$jump_html = $this->ipsclass->build_forum_jump(0,1,1);
		
			$this->output = $this->html_start_form( array( 1 => array( 'CODE', 'postchoice' ),
														   2 => array( 't' , $this->topic['tid'] ),
														   3 => array( 'f' , $this->forum['id']  ),
														   4 => array( 'tact', 'split' ),
														   5 => array( 'checked', 1    ),
														   
												  )      );
												  
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['st_top'].": ".$this->forum['name']." -&gt; ".$this->topic['title'] );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->split_body( $jump_html );
			
			//-----------------------------------------
			// Display the posty wosty's
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array(
										  'select' => 'post, pid, post_date, author_id, author_name',
										  'from'   => 'posts',
										  'where'  => 'topic_id=' . $this->topic['tid'] . ' AND pid IN (' . implode( ',', $this->pids) . ')',
										  'order'  => 'post_date'
								 )      );
								 
			$post_query = $this->ipsclass->DB->simple_exec();
			
			$post_count = 0;
			
			while ( $row = $this->ipsclass->DB->fetch_row($post_query) )
			{
				// Limit posts to 800 chars to stop shite loads of pages
				
				if ( strlen($row['post']) > 800 )
				{
					$row['post']   = $this->parser->pre_edit_parse($row['post']);
					$row['post']   = substr($row['post'], 0, 800) . '...';
				}
				
				$row['date']   = $this->ipsclass->get_date( $row['post_date'], 'LONG' );
				
				$row['st_top_bit'] = sprintf( $this->ipsclass->lang['st_top_bit'], $row['author_name'], $row['date'] );
					
				$row['post_css'] = $post_count % 2 ? 'row1' : 'row2';
					
				$this->output .= $this->ipsclass->compiled_templates['skin_mod']->split_row( $row );
				
				$post_count++;
			}
			
			//-----------------------------------------
			// print my bottom, er, the bottom
			//-----------------------------------------
					
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->split_end_form( $this->ipsclass->lang['st_submit'] );
			
			$this->page_title = $this->ipsclass->lang['st_top']." ".$this->topic['title'];
			
			$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
								 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
							   );
							   
			$this->ipsclass->print->add_output( $this->output );
        	$this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
		}
		else
		{
			//-----------------------------------------
			// PROCESS Check the input
			//-----------------------------------------
			
			if ($this->ipsclass->input['title'] == "")
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
			}
			
			//-----------------------------------------
			// Get the post ID's to split
			//-----------------------------------------
	
			$ids = array();
			
			foreach ($this->ipsclass->input as $key => $value)
			{
				if ( preg_match( "/^post_(\d+)$/", $key, $match ) )
				{	
					if ($this->ipsclass->input[$match[0]])
					{
						$ids[] = $match[1];
					}
				}
			}
			
			$ids = $this->ipsclass->clean_int_array( $ids );
			
			$affected_ids = count($ids);
			
			//-----------------------------------------
			// Do we have enough?
			//-----------------------------------------
			
			if ($affected_ids < 1)
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'split_not_enough' ) );
			}
			
			//-----------------------------------------
			// Do we choose too many?
			//-----------------------------------------
			
			$count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}" ) );
			
			if ( $affected_ids >= $count['cnt'] )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'split_too_much' ) );
			}
			
			//-----------------------------------------
			// Complete the PID string
			//-----------------------------------------
			
			$pid_string = implode( ",", $ids );
			
			//-----------------------------------------
			// Check the forum we're moving this too
			//-----------------------------------------
			
			$this->ipsclass->input['fid'] = intval($this->ipsclass->input['fid']);
			
			if ($this->ipsclass->input['fid'] != $this->forum['id'])
			{
				if ( $this->trash_inuse )
				{
					$f = $this->ipsclass->cache['forum_cache'][ $this->ipsclass->input['fid'] ];
				}
				else
				{
					$f = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['fid'] ];
				}
				
				if ( ! $f['id'] )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_no_forum' ) );
				}
			
				if ($f['sub_can_post'] != 1)
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'forum_no_post_allowed' ) );
				}
			}
			
			//-----------------------------------------
			// Is first post queued?
			//-----------------------------------------
			
			$topic_approved = 1;
			
			$this->ipsclass->DB->build_query( array( 'select'   => 'pid, queued',
													 'from'     => 'posts',
													 'where'    => 'topic_id=' . $this->topic['tid'] . " AND pid IN($pid_string)",
													 'order'    => 'pid ASC',
													 'limit'    => array(0,1),
											)      );
			
			$this->ipsclass->DB->exec_query();
			
			$first_post = $this->ipsclass->DB->fetch_row();
			
			if( $first_post['queued'] )
			{
				$topic_approved = 0;
				$this->ipsclass->DB->do_update( 'posts', array( 'queued' => 0 ), 'pid='.$first_post['pid'] );
			}
			
			//-----------------------------------------
			// Complete a new dummy topic
			//-----------------------------------------
			
			$this->ipsclass->DB->do_insert( 'topics',  array(
											 'title'            => $this->ipsclass->input['title'],
											 'description'      => $this->ipsclass->input['desc'] ,
											 'state'            => 'open',
											 'posts'            => 0,
											 'starter_id'       => 0,
											 'starter_name'     => 0,
											 'start_date'       => time(),
											 'last_poster_id'   => 0,
											 'last_poster_name' => 0,
											 'last_post'        => time(),
											 'icon_id'          => 0,
											 'author_mode'      => 1,
											 'poll_state'       => 0,
											 'last_vote'        => 0,
											 'views'            => 0,
											 'forum_id'         => $this->ipsclass->input['fid'],
											 'approved'         => $topic_approved,
											 'pinned'           => 0,
							)               );
								
			$new_topic_id = $this->ipsclass->DB->get_insert_id();
	
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'posts', array( 'topic_id' => $new_topic_id, 'new_topic' => 0 ), 'topic_id=' . $this->topic['tid'] . " AND pid IN($pid_string)" ); 
			
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			if ( $this->trash_inuse )
			{
				$this->ipsclass->DB->do_update( 'posts', array( 'queued' => 0 ), "topic_id=$new_topic_id" );
			}
			
			$this->ipsclass->DB->do_update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" );
			
			//-----------------------------------------
			// Rebuild the topics
			//-----------------------------------------
			
			$this->modfunc->rebuild_topic($new_topic_id);
			$this->modfunc->rebuild_topic($this->topic['tid']);
			
			//-----------------------------------------
			// Update the forum(s)
			//-----------------------------------------
			
			$this->modfunc->forum_recount($this->topic['forum_id']);
			
			if ($this->topic['forum_id'] != $this->ipsclass->input['fid'])
			{
				$this->modfunc->forum_recount($this->ipsclass->input['fid']);
			}
			
			if ( $this->trash_inuse )
			{
				$this->moderate_log("{$this->ipsclass->lang['acp_trashcan_post']} '{$this->topic['title']}'");
			}
			else
			{
				$this->moderate_log("{$this->ipsclass->lang['acp_split_topic']} '{$this->topic['title']}'");
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Multi merge post
	/*-------------------------------------------------------------------------*/
	
	function multi_merge_post()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		else if ($this->moderator['delete_post'] == 1)
		{
			$passed = 1;
		}
		else 
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if ( ! count( $this->pids ) )
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Load LIB
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
		
		//-----------------------------------------
		// Form or print?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['checked'] )
		{
			//-----------------------------------------
			// Get post data
			//-----------------------------------------
			
			$master_post = "";
			$dropdown    = "";
			$author      = "";
			$seen_author = array();
			$upload_html = "";
			
			//-----------------------------------------
			// MOVE INTO DB CLASS
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'moderate_get_topics', array( 'pids' => $this->pids ) );
			$outer = $this->ipsclass->DB->cache_exec_query();
		
			while ( $p = $this->ipsclass->DB->fetch_row( $outer ) )
			{
				if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $p['forum_id'] ]['read_perms']) == TRUE )
				{
					$master_post .= "\n\n".$this->parser->pre_edit_parse( trim($p['post']) );
					
					$dropdown    .= "\n<option value='{$p['pid']}'>".$this->ipsclass->get_date( $p['post_date'], 'LONG') ." (#{$p['pid']})</option>";
					
					if ( ! $seen_author[ $p['author_id'] ] )
					{
						$author .= "\n<option value='{$p['author_id']}'>{$p['author_name']} (#{$p['pid']})</option>";
						$seen_author[ $p['author_id'] ] = 1;
					}
				}
			}
			
			//-----------------------------------------
			// Get Attachment Data
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( "select" => '*',
														  'from'   => 'attachments',
														  'where'  => "attach_rel_module='post' AND attach_rel_id IN (".implode(",", $this->pids).")" ) );
			$this->ipsclass->DB->simple_exec();
			
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['image'] = $this->ipsclass->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'];
				$row['size']  = $this->ipsclass->size_format( $row['attach_filesize'] );
				
				if ( strlen( $row['attach_file'] ) > 40 )
				{
					$row['attach_file'] = substr( $row['attach_file'], 0, 35 ) .'...';
				}
				
				$upload_html .= $this->ipsclass->compiled_templates['skin_mod']->uploadbox_entry($row);
			}
			
			//-----------------------------------------
			// Print form
			//-----------------------------------------
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->merge_post_form( trim($master_post), $dropdown, $author, $this->ipsclass->return_md5_check(), $upload_html );
			
			if ( $this->topic['tid'] )
			{
				$this->nav[] = "<a href='{$this->ipsclass->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>";
			}
			
			$this->nav[]      = $this->ipsclass->lang['cm_title'];
			
			$this->page_title = $this->ipsclass->lang['cm_title'];
			
			$this->ipsclass->print->add_output( $this->output );
        	$this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, NAV => $this->nav ) );
		}
		else
		{
			//-----------------------------------------
			// DO THE THING, WITH THE THING!!
			//-----------------------------------------
			
			$this->ipsclass->input['postdate'] = intval($this->ipsclass->input['postdate']);
			
			if ( ! $this->ipsclass->input['selectedpids'] and ! $this->ipsclass->input['postdate'] and ! $this->ipsclass->input['postauthor'] and ! $this->ipsclass->input['Post'] )
			{
				$this->moderate_error();
			}
			
			$this->parser->parse_smilies = 1;
			$this->parser->parse_html    = 0;
			$this->parser->parse_bbcode  = 1;
		
			$post = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->ipsclass->input['Post'] ) );
								    
			//-----------------------------------------
			// Post to keep...
			//-----------------------------------------
			
			$posts          = array();
			$author         = array();
			$post_to_delete = array();
			$new_post_key   = md5(time());
			$topics         = array();
			$forums         = array();
			$append_edit	= 0;
			
			$this->ipsclass->DB->cache_add_query( 'moderate_get_topics', array( 'pids' => $this->pids ) );
			$this->ipsclass->DB->cache_exec_query();
			
			while ( $p = $this->ipsclass->DB->fetch_row() )
			{
				$posts[ $p['pid'] ] = $p;
				
				$topics[ $p['topic_id'] ] = $p['topic_id'];
				$forums[ $p['forum_id'] ] = $p['forum_id'];
				
				if ( $p['author_id'] == $this->ipsclass->input['postauthor'] )
				{
					$author = array( 'id' => $p['author_id'], 'name' => $p['author_name'] );
				}
				
				if ( $p['pid'] != $this->ipsclass->input['postdate'] )
				{
					$post_to_delete[] = $p['pid'];
				}
				
				if( $p['append_edit'] )
				{
					$append_edit = 1;
				}
			}
			
			//-----------------------------------------
			// Update main post...
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'posts', array( 'author_id'   => $author['id'],
											'author_name' => $author['name'],
											'post'        => $post,
											'post_key'    => $new_post_key,
											'post_parent' => 0, 
											'edit_time'	  => time(),
											'edit_name'	  => $this->ipsclass->member['members_display_name'],
											'append_edit' => ( $append_edit OR !$this->ipsclass->member['g_append_edit'] ) ? 1 : 0,
										  ), 'pid='.$this->ipsclass->input['postdate']
						 );
						 
			//-----------------------------------------
			// Fix attachments
			//-----------------------------------------
			
			$attach_keep = array();
			$attach_kill = array();
			
			foreach ($this->ipsclass->input as $key => $value)
			{
				if ( preg_match( "/^attach_(\d+)$/", $key, $match ) )
				{
					if ( $this->ipsclass->input[$match[0]] == 'keep' )
					{
						$attach_keep[] = $match[1];
					}
					else
					{
						$attach_kill[] = $match[1];
					}
				}
			}
			
			$attach_keep = $this->ipsclass->clean_int_array( $attach_keep );
			$attach_kill = $this->ipsclass->clean_int_array( $attach_kill );
			
			//-----------------------------------------
			// Keep
			//-----------------------------------------
			
			if ( count( $attach_keep ) )
			{
				$this->ipsclass->DB->do_update( 'attachments', array( 'attach_rel_id'    => $this->ipsclass->input['postdate'],
																	  'attach_post_key'  => $new_post_key,
																	  'attach_member_id' => $author['id'] ), 'attach_id IN('.implode(",",$attach_keep).')' );
			}
			
			//-----------------------------------------
			// Kill
			//-----------------------------------------
			
			if ( count( $attach_kill ) )
			{
				$this->ipsclass->DB->simple_construct( array( "select" => '*', 'from' => 'attachments',  'where' => 'attach_id IN('.implode(",",$attach_kill).')') );
				$this->ipsclass->DB->simple_exec();
				
				while ( $killmeh = $this->ipsclass->DB->fetch_row() )
				{
					if ( $killmeh['attach_location'] )
					{
						@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_location'] );
					}
					if ( $killmeh['attach_thumb_location'] )
					{
						@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
					}
				}
				
				$this->ipsclass->DB->simple_construct( array( 'delete' => 'attachments', 'where' => 'attach_id IN('.implode(",",$attach_kill).')' ) );
				$this->ipsclass->DB->simple_exec();
			}
			
			//-----------------------------------------
			// Kill old posts
			//-----------------------------------------
			
			if ( count($post_to_delete) )
			{
				$this->ipsclass->DB->simple_construct( array( 'delete' => 'posts', 'where' => 'pid IN('.implode(",",$post_to_delete).')' ) );
				$this->ipsclass->DB->simple_exec();
			}
			
			foreach( $topics as $t )
			{
				$this->modfunc->rebuild_topic($t, 0);
			}
			
			foreach( $forums as $f )
			{
				$this->modfunc->forum_recount($f);
			}
			
			$this->modfunc->stats_recount();
			
			$this->moderate_log( sprintf( $this->ipsclass->lang['acp_merged_posts'], implode( ", ", $this->pids ) ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Multi delete post
	/*-------------------------------------------------------------------------*/
	
	function multi_delete_post()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		else if ($this->moderator['delete_post'] == 1)
		{
			$passed = 1;
		}
		else 
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		//-----------------------------------------
		// Check to make sure that this isn't the first post in the topic..
		//-----------------------------------------
		
		foreach( $this->pids as $p )
		{
			if ( $this->topic['topic_firstpost'] == $p )
			{ 
				$this->moderate_error('no_delete_post');
			}
		}
		
		if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
		{
			//-----------------------------------------
			// Set up and pass to split topic handler
			//-----------------------------------------
			
			$this->ipsclass->input['checked'] = 1;
			$this->ipsclass->input['fid']     = $this->trash_forum;
			$this->ipsclass->input['title']   = $this->ipsclass->lang['mod_from']   ." ".$this->topic['title'];
			$this->ipsclass->input['desc']    = $this->ipsclass->lang['mod_from_id']." ".$this->topic['tid'];
			
			foreach( $this->pids as $p )
			{
				$this->ipsclass->input[ 'post_'.$p ] = 1;
			}
			
			$this->trash_inuse = 1;
			
			$this->multi_split_topic();
			
			$this->trash_inuse = 0;
		}
		else
		{
			$this->modfunc->post_delete( $this->pids );
			$this->modfunc->forum_recount( $this->topic['forum_id'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Alter approved thingy
	/*-------------------------------------------------------------------------*/
	
	function topic_approve_alter($type='approve')
	{
		$pass = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['post_q'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		$approve_int = $type == 'approve' ? 1 : 0;
		
		$this->ipsclass->DB->do_update( 'topics', array( 'approved' => $approve_int ), 'tid='.$this->topic['tid'] );
		
		$this->modfunc->forum_recount( $this->forum['id'] );
		$this->modfunc->stats_recount();
		
		$this->moderate_log( sprintf( $this->ipsclass->lang['acp_approve_topic'], $this->topic['tid'] ) );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['redirect_modified'], "showtopic=".$this->topic['tid']."&amp;st=".intval($this->ipsclass->input['st']) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Prune move
	/*-------------------------------------------------------------------------*/
	
	function prune_move()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		$pass = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['mass_move'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		///-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		$pergo      = intval( $this->ipsclass->input['pergo'] ) ? intval( $this->ipsclass->input['pergo'] ) : 50;
		$max        = intval( $this->ipsclass->input['max'] );
		$current    = intval($this->ipsclass->input['current']);
		$maxdone    = $pergo + $current;
		$tid_array  = array();
		$starter    = trim( $this->ipsclass->input['starter'] );
		$state      = trim( $this->ipsclass->input['state'] );
		$posts      = intval( $this->ipsclass->input['posts'] );
		$dateline   = intval( $this->ipsclass->input['dateline'] );
		$ignore_pin = intval( $this->ipsclass->input['ignore_pin'] );
		$source     = $this->forum['id'];
		$moveto     = intval($this->ipsclass->input['df']);
		
		//-----------------------------------------
		// Carry on...
		//-----------------------------------------
		
		$db_query = $this->modfunc->sql_prune_create( $this->forum['id'], $starter, $state, $posts, $dateline, $ignore_pin, $pergo, $current );
		
		$this->ipsclass->DB->query($db_query);
		
		if ( ! $num_rows = $this->ipsclass->DB->get_num_rows() )
		{
			if ( ! $current )
			{
				$this->moderate_error('cp_error_no_topics'); 
				return;
			}
		}
		
		//-----------------------------------------
		// Get tids
		//-----------------------------------------
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			$tid_array[] = $row['tid'];
		}
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		$f = $this->ipsclass->forums->forum_by_id[ $moveto ];
		
		if ( $f['sub_can_post'] != 1 )
		{
			$this->moderate_error('cp_error_no_subforum');
			return;
		}
		
		$this->modfunc->topic_move( $tid_array, $source, $moveto );
		
		$this->moderate_log( $this->ipsclass->lang['acp_mass_moved'] );
		
		//-----------------------------------------
		// Show results or refresh..
		//-----------------------------------------
		
		if ( ! $num_rows )
		{
			//-----------------------------------------
			// Update forum deletion
			//-----------------------------------------
			
			$this->ipsclass->forums->forum_by_id[ $moveto ]['_update_deletion'] = 1;
			$this->ipsclass->forums->forum_by_id[ $source ]['_update_deletion'] = 1;
			
			//-----------------------------------------
			// Resync the forums..
			//-----------------------------------------
			
			$this->modfunc->forum_recount($source);
			
			$this->modfunc->forum_recount($moveto);
		
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->ipsclass->input['check'] = 0;
			$this->ipsclass->input['dateline'] = $this->ipsclass->input['dateline'] > 0 ? round((time()-$this->ipsclass->input['dateline'])/24/60/60) : 0;
			$this->prune_start( $this->ipsclass->compiled_templates['skin_mod']->mod_simple_page( $this->ipsclass->lang['cp_results'], $this->ipsclass->lang['cp_result_move']. ($max) ) );
		}
		else
		{
			$link  = "act=mod&amp;f={$this->forum['id']}&amp;CODE=prune_move&amp;df={$this->ipsclass->input['df']}&amp;pergo={$pergo}&amp;current={$maxdone}&amp;max={$max}";
			$link .= "&amp;starter={$starter}&amp;state={$state}&amp;posts={$posts}&amp;dateline={$dateline}&amp;ignore_pin={$ignore_pin}";
			$link .= "&amp;auth_key=".$this->ipsclass->md5_check;
			$done  = $current + $num_rows;
			$text  = sprintf( $this->ipsclass->lang['cp_batch_done'], $done, $max - $done );
			
			$this->ipsclass->print->redirect_screen( $text, $link );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Do prune
	/*-------------------------------------------------------------------------*/
	
	function prune_finish()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$pass = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['mass_prune'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		//-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		$pergo      = intval( $this->ipsclass->input['pergo'] ) ? intval( $this->ipsclass->input['pergo'] ) : 50;
		$max        = intval( $this->ipsclass->input['max'] );
		$current    = intval($this->ipsclass->input['current']);
		$maxdone    = $pergo + $current;
		$tid_array  = array();
		$starter    = trim( $this->ipsclass->input['starter'] );
		$state      = trim( $this->ipsclass->input['state'] );
		$posts      = intval( $this->ipsclass->input['posts'] );
		$dateline   = intval( $this->ipsclass->input['dateline'] );
		$ignore_pin = intval( $this->ipsclass->input['ignore_pin'] );
		
		//-----------------------------------------
		// Carry on...
		//-----------------------------------------
		
		$db_query = $this->modfunc->sql_prune_create( $this->forum['id'], $starter, $state, $posts, $dateline, $ignore_pin, $pergo, $current );
													  
		$batch    = $this->ipsclass->DB->query($db_query);
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $num_rows = $this->ipsclass->DB->get_num_rows() )
		{
			if ( ! $current )
			{
				$this->moderate_error('cp_error_no_topics'); 
				return;
			}
		}
		
		//-----------------------------------------
		// Get tiddles
		//-----------------------------------------
		
		while ( $tid = $this->ipsclass->DB->fetch_row() )
		{
			$tid_array[] = $tid['tid'];
		}
		
		$this->modfunc->topic_delete($tid_array);
		
		//-----------------------------------------
		// Show results or refresh..
		//-----------------------------------------
		
		if ( ! $num_rows )
		{
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->moderate_log( $this->ipsclass->lang['acp_pruned_forum'] );
			
			$this->ipsclass->input['check'] = 0;
			$this->prune_start( $this->ipsclass->compiled_templates['skin_mod']->mod_simple_page( $this->ipsclass->lang['cp_results'], $this->ipsclass->lang['cp_result_del'].($max)  ) );
		}
		else
		{
			$link  = "act=mod&amp;f={$this->forum['id']}&amp;CODE=prune_finish&amp;pergo={$pergo}&amp;current={$maxdone}&amp;max={$max}";
			$link .= "&amp;starter={$starter}&amp;state={$state}&amp;posts={$posts}&amp;dateline={$dateline}&amp;ignore_pin={$ignore_pin}";
			$link .= "&amp;auth_key=".$this->ipsclass->md5_check;
			$done  = $current + $num_rows;
			$text  = sprintf( $this->ipsclass->lang['cp_batch_done'], $done, $max - $done );
			
			$this->ipsclass->print->redirect_screen( $text, $link );
		}
		
		$this->ipsclass->print->pop_up_window( "", $this->output );
	}
	
													  
	/*-------------------------------------------------------------------------*/
	// Prune / Move Start
	/*-------------------------------------------------------------------------*/
	
	function prune_start( $complete_html="" )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['mass_prune'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		$confirm_data = array( 'show' => '' );
		
		//-----------------------------------------
		// Check per go
		//-----------------------------------------
		
		$this->ipsclass->input['pergo']  	= isset($this->ipsclass->input['pergo'])  	 ? intval($this->ipsclass->input['pergo']) 	: 50;
		$this->ipsclass->input['posts']  	= isset($this->ipsclass->input['posts'])  	 ? intval($this->ipsclass->input['posts']) 	: '';
		$this->ipsclass->input['member'] 	= isset($this->ipsclass->input['member']) 	 ? $this->ipsclass->input['member'] 		: '';
		$this->ipsclass->input['determine'] = isset($this->ipsclass->input['determine']) ? $this->ipsclass->input['determine']		: '';
		$this->ipsclass->input['dateline']	= isset($this->ipsclass->input['dateline'])	 ? $this->ipsclass->input['dateline']	 	: '';
		
		//-----------------------------------------
		// Are we checking first?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['check']) AND $this->ipsclass->input['check'] == 1 )
		{
			$link      = "&amp;pergo=".$this->ipsclass->input['pergo'];
			$link_text = $this->ipsclass->lang['cp_prune_dorem'];
			
			$tcount = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as tcount', 'from' => 'topics', 'where' => "forum_id={$this->forum['id']}" ) );
			
			$db_query = "";
			
			//-----------------------------------------
			// date...
			//-----------------------------------------
		
			if ($this->ipsclass->input['dateline'])
			{
				$date      = time() - $this->ipsclass->input['dateline']*60*60*24;
				$db_query .= " AND last_post < $date";
				
				$link .= "&amp;dateline=$date";
			}
			
			//-----------------------------------------
			// Member...
			//-----------------------------------------
			
			if ( $this->ipsclass->input['member'] )
			{
				$this->ipsclass->DB->build_query( array( 'select' => 'id', 'from' => 'members', 'where' => "name='".$this->ipsclass->input['member']."'" ) );
				$this->ipsclass->DB->exec_query();
				
				if (! $mem = $this->ipsclass->DB->fetch_row() )
				{
					$this->moderate_error('cp_error_no_mem');
					return;
				}
				else
				{
					$db_query .= " AND starter_id='".$mem['id']."'";
					$link     .= "&amp;starter={$mem['id']}";
				}
			}
			
			//-----------------------------------------
			// Posts / Topic type
			//-----------------------------------------
			
			if ($this->ipsclass->input['posts'])
			{
				$db_query .= " AND posts < '".$this->ipsclass->input['posts']."'";
				$link     .= "&amp;posts={$this->ipsclass->input['posts']}";
			}
			
			if ($this->ipsclass->input['topic_type'] != 'all')
			{
				$db_query .= " AND state='".$this->ipsclass->input['topic_type']."'";
				$link     .= "&amp;state={$this->ipsclass->input['topic_type']}";
			}
			
			if ($this->ipsclass->input['ignore_pin'] == 1)
			{
				$db_query .= " AND pinned <> 1";
				$link     .= "&amp;ignore_pin=1";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as count',
										  				  'from'   => 'topics',
										  				  'where'  => "forum_id='".$this->forum['id']."'" . $db_query ) );
					
			$this->ipsclass->DB->simple_exec();
			
			$count = $this->ipsclass->DB->fetch_row();
			
			//-----------------------------------------
			// Prune?
			//-----------------------------------------
			
			if ($this->ipsclass->input['df'] == 'prune')
			{
				$link = "act=mod&amp;f={$this->forum['id']}&amp;CODE=prune_finish&amp;".$link;
			}
			else
			{
				if ( $this->ipsclass->input['df'] == $this->forum['id'] )
				{
					$this->moderate_error('cp_same_forum');
					return;
				}
				else if ($this->ipsclass->input['df'] == -1)
				{
					$this->moderate_error('cp_no_forum');
					return;
				}
				
				$link      = "act=mod&amp;f={$this->forum['id']}&amp;CODE=prune_move&amp;df=".$this->ipsclass->input['df'].$link;
				$link_text = $this->ipsclass->lang['cp_prune_domove'];
			}
			
			//-----------------------------------------
			// Build data
			//-----------------------------------------
			
			$confirm_data = array( 'tcount'    => $tcount['tcount'],
								   'count'     => $count['count'],
								   'link'      => $link . '&amp;max='.$count['count'],
								   'link_text' => $link_text,
								   'show'      => 1 );
		}
		
		$select = "<select name='topic_type' class='forminput'>";
		
		foreach( array( 'open', 'closed', 'link', 'all' ) as $type )
		{
			if (isset($this->ipsclass->input['topic_type']) AND $this->ipsclass->input['topic_type'] == $type)
			{
				$selected = ' selected';
			}
			else
			{
				$selected = '';
			}
			
			$select .= "<option value='$type'".$selected.">".$this->ipsclass->lang['cp_pday_'.$type]."</option>";
		}
		
		$select .= "</select>\n";
		
		$html_forums  = "<option value='prune'>{$this->ipsclass->lang['cp_ac_prune']}</option>";
		
		$tmp_jump_setting = $this->ipsclass->vars['short_forum_jump'];
		$this->ipsclass->vars['short_forum_jump'] = 0;
		$html_forums .= $this->ipsclass->build_forum_jump(0,0,1);
		$this->ipsclass->vars['short_forum_jump'] = $tmp_jump_setting;
		unset($tmp_jump_setting);		
		
		//-----------------------------------------
		// Make current destination forum this one if selected
		// before
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['df']) AND $this->ipsclass->input['df'] )
		{
			$html_forums = preg_replace( "/<option value=\"".intval($this->ipsclass->input['df'])."\"/", "<option value=\"".$this->ipsclass->input['df']."\" selected='selected'", $html_forums );
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->prune_splash($this->forum, $html_forums, $select, $this->ipsclass->return_md5_check(), $confirm_data, $complete_html );
		
		$this->ipsclass->print->pop_up_window( "", $this->output );
	}
	
	/*-------------------------------------------------------------------------*/
	// Resynchronise Forum
	/*-------------------------------------------------------------------------*/
	
	function resync_forum()
	{
		$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
		$this->modfunc->forum_recount( $this->forum['id'] );
		$this->modfunc->stats_recount();
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['cp_resync'], "showforum=".$this->forum['id'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Topic act
	/*-------------------------------------------------------------------------*/
	
	function multi_topic_modify()
	{
		$this->tids  = $this->get_tids();
		
		if ( count( $this->tids ) )
		{
			switch ( $this->ipsclass->input['tact'] )
			{
				case 'close':
					$this->multi_alter_topics('close_topic', "state='closed'");
					break;
				case 'open':
					$this->multi_alter_topics('open_topic', "state='open'");
					break;
				case 'pin':
					$this->multi_alter_topics('pin_topic', "pinned=1");
					break;
				case 'unpin':
					$this->multi_alter_topics('unpin_topic', "pinned=0");
					break;
				case 'approve':
					$this->multi_alter_topics('topic_q', "approved=1");
					break;
				case 'unapprove':
					$this->multi_alter_topics('topic_q', "approved=0");
					break;
				case 'delete':
					$this->multi_alter_topics('delete_topic');
					break;
				case 'move':
					$this->multi_start_checked_move();
					return;
					break;
				case 'domove':
					$this->multi_complete_checked_move();
					break;
				case 'merge':
					$this->multi_topic_merge();
					break;
				default:
					$this->multi_topic_mmod();
					break;
			}
		}
		
		$this->ipsclass->my_setcookie('modtids', '', 0);
		
		if ( $this->forum['id'] )
		{
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['cp_redirect_topics'], "showforum=".$this->forum['id'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Multi Merge Topics
	/*-------------------------------------------------------------------------*/
	
	function multi_topic_merge()
	{
		$pass = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['delete_topic'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		if ( count($this->tids) < 2 )
		{
			$this->moderate_error('mt_not_enough');  
			return;
		}
		
		//-----------------------------------------
		// Get the topics in ascending date order
		//-----------------------------------------
		
		$topics = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid IN ('.implode( ",",$this->tids ).')', 'order' => 'start_date asc' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$topics[] = $r;
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count($topics) < 2 )
		{
			$this->moderate_error();  ### NEEDS CUSTOM MESSAGE
			return;
		}
		
		//-----------------------------------------
		// Get topic ID for first topic 'master'
		//-----------------------------------------
		
		$first_topic = array_shift( $topics );
		
		$main_topic_id = $first_topic['tid'];
		
		$merge_ids = array();
		
		foreach( $topics as $t )
		{
			$merge_ids[] = $t['tid'];
		}
		
		//-----------------------------------------
		// Update the posts, remove old polls, subs and topic
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'posts', array( 'topic_id' => $main_topic_id ), 'topic_id IN ('.implode(",",$merge_ids).")" );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'polls', 'where' => "tid IN (".implode(",",$merge_ids).")") );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'voters', 'where' => "tid IN (".implode(",",$merge_ids).")") );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'tracker', 'where' => "topic_id IN (".implode(",",$merge_ids).")") );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topics', 'where' => "tid IN (".implode(",",$merge_ids).")") );
		
		//-----------------------------------------
		// Update the newly merged topic
		//-----------------------------------------
		
		$this->modfunc->rebuild_topic( $main_topic_id );
		
		$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
		
		$this->modfunc->forum_recount( $this->forum['id'] );
		$this->modfunc->stats_recount();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Complete move dUdE
	/*-------------------------------------------------------------------------*/
	
	function multi_complete_checked_move()
	{
		$pass = 0;
		$add_link = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['move_topic'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		if( $this->ipsclass->input['leave'] == 'y' )
		{
			$add_link = 1;
		}
		
		$dest_id   = intval($this->ipsclass->input['df']);
		$source_id = $this->forum['id'];
		
		$this->tids = array();
		
		//-----------------------------------------
		// Check for input..
		//-----------------------------------------
		
		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^TID_(\d+)$/", $key, $match ) )
 			{
 				if ( $this->ipsclass->input[$match[0]] )
 				{
 					$this->tids[] = $match[1];
 				}
 			}
 		}
 		
 		$this->tids = $this->ipsclass->clean_int_array( $this->tids );
 			
		//-----------------------------------------
		// Check for input..
		//-----------------------------------------
		
		if ($source_id == "")
		{
			$this->moderate_error('cp_error_move');
			return;
		}
		
		//-----------------------------------------
		
		if ($dest_id == "" or $dest_id == -1)
		{
			$this->moderate_error('cp_error_move');
			return;
		}
		
		//-----------------------------------------
		
		if ($source_id == $dest_id)
		{
			$this->moderate_error('cp_error_move');
			return;
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, sub_can_post, name, redirect_on', 'from' => 'forums', 'where' => "id IN(".$source_id.",".$dest_id.")" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() != 2 )
		{
			$this->moderate_error('cp_error_move');
			return;
		}
		
		$source_name = "";
		$dest_name   = "";
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		while ( $f = $this->ipsclass->DB->fetch_row() )
		{
			if ($f['id'] == $source_id)
			{
				$source_name = $f['name'];
			}
			else
			{
				$dest_name = $f['name'];
			}
			
			if ( ( $f['sub_can_post'] != 1 ) OR $f['redirect_on'] == 1 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'forum_no_post_allowed' ) );
			}
		}
		
		$this->modfunc->topic_move( $this->tids, $source_id, $dest_id, $add_link );
		
		//-----------------------------------------
		// Resync the forums..
		//-----------------------------------------
		
		$this->ipsclass->forums->forum_by_id[ $source_id ]['_update_deletion'] = 1;
		$this->ipsclass->forums->forum_by_id[  $dest_id  ]['_update_deletion'] = 1;
		
		$this->modfunc->forum_recount($source_id);
		$this->modfunc->forum_recount($dest_id);
		
		$this->moderate_log( sprintf( $this->ipsclass->lang['acp_moved_topics'], $source_name, $dest_name ) );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Start move form
	/*-------------------------------------------------------------------------*/
	
	function multi_start_checked_move()
	{
		$pass = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator['move_topic'] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		$tmp_jump_setting = $this->ipsclass->vars['short_forum_jump'];
		$this->ipsclass->vars['short_forum_jump'] = 0;
		$jump_html = $this->ipsclass->build_forum_jump(0,0,1);
		$this->ipsclass->vars['short_forum_jump'] = $tmp_jump_setting;
		unset($tmp_jump_setting);
		
		$this->output .= $this->html_start_form( array( 1 => array( 'CODE', 'topicchoice'     ),
														2 => array( 'f' , $this->forum['id']  ),
														3 => array( 'tact', 'domove'          ),
											   )      );
		 								      
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->move_checked_form_start( $this->forum['name'] );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'title, tid', 'from' => 'topics', 'where' => "forum_id=".$this->forum['id']." AND tid IN(".implode(",", $this->tids).")" ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->output .=  $this->ipsclass->compiled_templates['skin_mod']->move_checked_form_entry($row['tid'],$row['title']);
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->move_checked_form_end($jump_html);
		
		$this->page_title = $this->ipsclass->lang['cp_ttitle'];
		
		$this->nav = array ( "<a href='{$this->ipsclass->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>" );
	}
	
	/*-------------------------------------------------------------------------*/
	// MULTI-MOD!
	/*-------------------------------------------------------------------------*/
	
	function multi_topic_mmod()
	{
		//-----------------------------------------
		// Issit coz i is black?
		//-----------------------------------------
		
		if ( ! strstr( $this->ipsclass->input['tact'], 't_' ) )
		{
			$this->moderate_error('stupid_beggar');
		}
		
		$this->mm_id = intval( str_replace( 't_', "", $this->ipsclass->input['tact'] ) );
		
		//-----------------------------------------
		// Init modfunc module
		//-----------------------------------------
		
		$this->modfunc->init( $this->forum, "", $this->moderator );
        
        //-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		if ( $this->modfunc->mm_authorize() != TRUE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cp_no_perms') );
		}
        
       //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
        
        $this->mm_data = $this->ipsclass->cache['multimod'][ $this->mm_id ];
        
        if ( ! $this->mm_data )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_mmid') );
        }
        
		//-----------------------------------------
        // Does this forum have this mm_id
        //-----------------------------------------
		
		if ( $this->modfunc->mm_check_id_in_forum( $this->forum['id'], $this->mm_data ) != TRUE )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_mmid') );
		}
		
		//-----------------------------------------
        // Still here? We're damn good to go sir!
        //-----------------------------------------
        
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
        // Update what we have so far...
        //-----------------------------------------
        
        $this->modfunc->stm_exec( $this->tids );
        
        //-----------------------------------------
        // Topic title (1337 - I am!)
        //-----------------------------------------
        
        $pre = "";
		$end = "";
        
        if ( $this->mm_data['topic_title_st'] )
        {
        	$pre =  str_replace( "'", "\\\'", $this->mm_data['topic_title_st'] );
        }
        
        if ( $this->mm_data['topic_title_end'] )
        {
        	$end =  str_replace( "'", "\\\'", $this->mm_data['topic_title_end'] );
        }
        
        $this->ipsclass->DB->cache_add_query( 'moderate_concat_title', array( 'pre'  => $pre,
																			  'end'  => $end,
																			  'tids' => $this->tids ) );
		$this->ipsclass->DB->cache_exec_query();
		
        //-----------------------------------------
        // Add reply?
        //-----------------------------------------
        
        if ( $this->mm_data['topic_reply'] and $this->mm_data['topic_reply_content'] )
        {
       		$move_ids = array();
       		
       		foreach( $this->tids as $tid )
       		{
       			$move_ids[] = array( $tid, $this->forum['id'] );
       		}
       		
        	$this->modfunc->auto_update = FALSE;  // Turn off auto forum re-synch, we'll manually do it at the end
        	
        	$this->parser->parse_smilies = 1;
			$this->parser->parse_bbcode  = 1;
			
        	$this->modfunc->topic_add_reply( 
        									 $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->mm_data['topic_reply_content'] ) )
										    , $move_ids
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
        	
        	$this->ipsclass->DB->simple_construct( array( 'select' => 'id, sub_can_post, name', 'from' => 'forums', 'where' => "id=".$this->mm_data['topic_move'] ) );
			$outer = $this->ipsclass->DB->simple_exec();
		
        	if ( $r = $this->ipsclass->DB->fetch_row( $outer ) )
        	{
        		if ( $r['sub_can_post'] != 1 )
        		{
        			$this->ipsclass->DB->do_update( 'topic_mmod', array( 'topic_move' => 0 ), "mm_id=".$this->mm_id );
        		}
        		else
        		{
        			if ( $r['id'] != $this->forum['id'] )
        			{
        				$this->modfunc->topic_move( $this->tids, $this->forum['id'], $r['id'], $this->mm_data['topic_move_link'] );
        			
        				$this->modfunc->forum_recount( $r['id'] );
        			}
        		}
        	}
        	else
        	{
        		$this->ipsclass->DB->do_update( 'topic_mmod', array( 'topic_move' => 0 ), "mm_id=".$this->mm_id );
        	}
        }
        
        //-----------------------------------------
        // Recount root forum
        //-----------------------------------------
        
        $this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
        
        $this->modfunc->forum_recount( $this->forum['id'] );
        
        $this->moderate_log( sprintf( $this->ipsclass->lang['acp_multi_mod'], $this->mm_data['mm_title'], $this->forum['name'] ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Alter the topics, yay!
	/*-------------------------------------------------------------------------*/
	
	function multi_alter_topics($mod_action="", $sql="")
	{
		if ( ! $mod_action )
		{
			$this->moderate_error();
			return;
		}
	
		$pass = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$pass = 1;
		}
		else if ($this->moderator[$mod_action] == 1)
		{
			$pass = 1;
		}
		else
		{
			$pass = 0;
		}
		
		if ($pass == 0)
		{
			$this->moderate_error();
			return;
		}
		
		if ( $mod_action != 'delete_topic' )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'update' => 'topics', 'set' => $sql, 'where' => "tid IN(".implode(",",$this->tids).") AND state!='link'" ) );
			
			$this->moderate_log( sprintf( $this->ipsclass->lang['acp_altered_topics'], $sql, implode(",",$this->tids) ) );
			
		}
		else
		{
			if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
			{
				//-----------------------------------------
				// Move, don't delete
				//-----------------------------------------
				
				$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
				
				$this->modfunc->topic_move($this->tids, $this->forum['id'], $this->trash_forum);
				$this->modfunc->forum_recount($this->trash_forum);
				$this->moderate_log( $this->ipsclass->lang['acp_trashcan_topics']." ".implode(",",$this->tids) );
			}
			else
			{
				$this->modfunc->topic_delete($this->tids);
				$this->moderate_log( sprintf( $this->ipsclass->lang['acp_deleted_topics'], implode(",",$this->tids) ) );
			}
		}
		
		if ( $mod_action == 'delete_topic' or $mod_action == 'topic_q' and $this->forum['id'] )
		{
			$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
			
			$this->modfunc->forum_recount( $this->forum['id'] );
			$this->modfunc->stats_recount();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// TOPIC HISTORY:
	/*-------------------------------------------------------------------------*/
	
	function topic_history()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_access_cp'] == 1) {
			$passed = 1;
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		$tid = intval($this->ipsclass->input['t']);
		
		//-----------------------------------------
		// Get all info for this topic-y-poos
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.intval($tid) ) );
		$this->ipsclass->DB->simple_exec();
		
		$topic = $this->ipsclass->DB->fetch_row();
		
		if ($topic['last_post'] == $topic['start_date'])
		{
			$avg_posts = 1;
		}
		else
		{
			$avg_posts = round( ($topic['posts'] + 1) / ((( $topic['last_post'] - $topic['start_date']) / 86400)), 1);
		}
		
		if ($avg_posts < 0)
		{
			$avg_posts = 1;
		}
		
		if ($avg_posts > ( $topic['posts'] + 1) )
		{
			$avg_posts = $topic['posts'] + 1;
		}
		
		$data = array( 
					   'th_topic'      => $topic['title'],
					   'th_desc'       => $topic['description'],
					   'th_start_date' => $this->ipsclass->get_date($topic['start_date'], 'LONG'),
					   'th_start_name' => $this->ipsclass->make_profile_link($topic['starter_name'], $topic['starter_id'] ),
					   'th_last_date'  => $this->ipsclass->get_date($topic['last_post'], 'LONG'),
	    		 	   'th_last_name'  => $this->ipsclass->make_profile_link($topic['last_poster_name'], $topic['last_poster_id'] ),
					   'th_avg_post'   => $avg_posts,
					 );
					 
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->topic_history($data);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_log_start();
		
		// Do we have any logs in the mod-logs DB about this topic? eh? well?
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
									  'from'   => 'moderator_logs',
									  'where'  => 'topic_id='.intval($tid),
									  'order'  => 'ctime DESC' ) );
		$this->ipsclass->DB->simple_exec();
		
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_log_none();
		}
		else
		{
			while ($row = $this->ipsclass->DB->fetch_row())
			{
				$row['member'] = $this->ipsclass->make_profile_link($row['member_name'], $row['member_id'] );
				$row['date']   = $this->ipsclass->get_date($row['ctime'], 'LONG');
				$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_log_row($row);
			}
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_log_end();
		
		$this->page_title = $this->topic['title'];
		
		$this->nav 	 = $this->ipsclass->forums->forums_breadcrumb_nav( $topic['forum_id'] );
		$this->nav[] = "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>";
	}
	
	/*-------------------------------------------------------------------------*/
	// MERGE TOPICS:
	/*-------------------------------------------------------------------------*/
	
	function merge_start()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1) {
			$passed = 1;
		}
		
		else if ($this->moderator['split_merge'] == 1) {
			$passed = 1;
		}
		else {
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		
		$this->output = $this->html_start_form( array( 1 => array( 'CODE', '61' ),
												       2 => array( 't' , $this->topic['tid'] ),
												       3 => array( 'f' , $this->forum['id']  ),
		 								      )      );
		 								      
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['mt_top']." ".$this->forum['name']." &gt; ".$this->topic['title'] );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_exp( $this->ipsclass->lang['mt_explain'] );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->merge_body( $this->topic['title'], $this->topic['description'] );	
        		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->end_form( $this->ipsclass->lang['mt_submit'] );
		
		$this->page_title = $this->ipsclass->lang['mt_top']." ".$this->topic['title'];
		
		$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
							 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
						   );
	}
	
	/*-------------------------------------------------------------------------*/
	// Merge complete
	/*-------------------------------------------------------------------------*/
	
	function merge_complete()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['split_merge'] == 1)
		{
			$passed = 1;
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		if ($this->ipsclass->input['topic_url'] == "" or $this->ipsclass->input['title'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------
		
		preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $this->ipsclass->input['topic_url'], $match );
		
		$old_id = intval(trim($match[3]));
		
		if ($old_id == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'mt_no_topic' ) );
		}
		
		//-----------------------------------------
		// Get the topic from the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid, title, forum_id, last_post, last_poster_id, last_poster_name, posts, views, topic_hasattach', 'from' => 'topics', 'where' => 'tid='.intval($old_id) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $old_topic = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'mt_no_topic' ) );
		}
		
		//-----------------------------------------
		// Did we try and merge the same topic?
		//-----------------------------------------
		
		if ($old_id == $this->topic['tid'])
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'mt_same_topic' ) );
		}
		
		//-----------------------------------------
		// Do we have moderator permissions for this
		// topic (ie: in the forum the topic is in)
		//-----------------------------------------
		
		$pass = FALSE;
		
		if ( $this->topic['forum_id'] == $old_topic['forum_id'] )
		{
			$pass = TRUE;
		}
		else
		{
			if ( $this->ipsclass->member['g_is_supmod'] == 1 )
			{
				$pass = TRUE;
			}
			else
			{
				$other_mgroups = array();
				
				if( $this->ipsclass->member['mgroup_others'] )
				{
					$other_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
				}
				
				$other_mgroups[] = $this->ipsclass->member['mgroup'];
				
				$mgroups = implode( ",", $other_mgroups );
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 'mid',
											  'from'   => 'moderators',
											  'where'  => "forum_id=".$old_topic['forum_id']." AND (member_id='".$this->ipsclass->member['id']."' OR (is_group=1 AND group_id IN(".$mgroups.")))" ) );
											  
				$this->ipsclass->DB->simple_exec();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$pass = TRUE;
				}
			}
		}
		
		if ( $pass == FALSE )
		{
			// No, we don't have permission
			
			$this->moderate_error();
		}
		
		//-----------------------------------------
		// Update the posts, remove old polls, subs and topic
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'posts', array( 'topic_id' => $this->topic['tid'] ), 'topic_id='.$old_topic['tid'] );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'polls', 'where' => "tid=".$old_topic['tid'] ) );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'voters', 'where' => "tid=".$old_topic['tid'] ) );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'tracker', 'where' => "topic_id=".$old_topic['tid'] ) );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topics', 'where' => "tid=".$old_topic['tid'] ) );
		
		//-----------------------------------------
		// Update the newly merged topic
		//-----------------------------------------
		
		$updater = array(  'title'       => $this->ipsclass->input['title'],
						   'description' => $this->ipsclass->input['desc']
						);
						
		if ($old_topic['last_post'] > $this->topic['last_post'])
		{
			$updater['last_post']        = $old_topic['last_post'];
			$updater['last_poster_name'] = $old_topic['last_poster_name'];
			$updater['last_poster_id']   = $old_topic['last_poster_id'];
		}
		
		if( $old_topic['topic_hasattach'] )
		{
			$updater['topic_hasattach'] = $old_topic['topic_hasattach'];
		}
		
		// We need to now count the original post, which isn't in the "posts" field 'cos it was a new topic
		
		$old_topic['posts']++;
		
		$str = $this->ipsclass->DB->compile_db_update_string($updater);
		
		$this->ipsclass->DB->simple_exec_query( array( 'update' => 'topics', 'set' => "$str,views=views+{$old_topic['views']}", 'where' => 'tid='.$this->topic['tid'] ) );
		
		//-----------------------------------------
		// Fix up the "new_topic" attribute.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pid, author_name, author_id, post_date',
													  'from'   => 'posts',
													  'where'  => "topic_id=".$this->topic['tid'],
													  'order'  => 'post_date ASC',
													  'limit'  => array( 0,1 ) ) );
		
		$this->ipsclass->DB->simple_exec();
		
		if ( $first_post = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->DB->do_update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" );
			$this->ipsclass->DB->do_update( 'posts', array( 'new_topic' => 1 ), "pid={$first_post['pid']}" );
		}
		
		//-----------------------------------------
		// Reset the post count for this topic
		//-----------------------------------------
		
		$amode = $first_post['author_id'] ? 1 : 0;
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as posts',
													  'from'   => 'posts',
													  'where'  => "queued <> 1 AND topic_id=".$this->topic['tid'] ) );
		
		$this->ipsclass->DB->simple_exec();
		
		if ( $post_count = $this->ipsclass->DB->fetch_row() )
		{
			$post_count['posts']--; //Remove first post
			
			$this->ipsclass->DB->do_update( 'topics', array( 'posts'           => $post_count['posts'],
															 'starter_name'    => $first_post['author_name'],
															 'starter_id'      => $first_post['author_id'],
															 'start_date'      => $first_post['post_date'],
															 'author_mode'     => $amode,
															 'topic_firstpost' => $first_post['pid']
										  ) , 'tid='.$this->topic['tid'] );
		}
		
		//-----------------------------------------
		// Update the forum(s)
		//-----------------------------------------
		
		$this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ]['_update_deletion'] = 1;
		
		$this->recount($this->topic['forum_id']);
		
		if ($this->topic['forum_id'] != $old_topic['forum_id'])
		{
			$this->ipsclass->forums->forum_by_id[ $old_topic['forum_id'] ]['_update_deletion'] = 1;
			$this->recount($old_topic['forum_id']);
		}
		
		$this->moderate_log( sprintf( $this->ipsclass->lang['acp_merged_topic'], $old_topic['title'], $this->topic['title'] ) );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['mt_redirect'], "showtopic=".$this->topic['tid'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// UNSUBSCRIBE ALL FORM:
	/*-------------------------------------------------------------------------*/
	
	function unsubscribe_all_form()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(trid) as subbed', 'from' => 'tracker', 'where' => "topic_id=".$this->topic['tid'] ) );
		$this->ipsclass->DB->simple_exec();
		
		$tracker = $this->ipsclass->DB->fetch_row();
		
        if ( $tracker['subbed'] < 1 )
        {
        	$text = $this->ipsclass->lang['ts_none'];
        }
        else
        {
        	$text = sprintf($this->ipsclass->lang['ts_count'], $tracker['subbed']);
        }
		
		$this->output = $this->html_start_form( array( 1 => array( 'CODE', '31' ),
												       2 => array( 't' , $this->topic['tid'] ),
												       3 => array( 'f' , $this->forum['id']  ),
		 								      )      );
		 								      
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['ts_title']." &gt; ".$this->forum['name']." &gt; ".$this->topic['title'] );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_exp( $text );		
        		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->end_form( $this->ipsclass->lang['ts_submit'] );
		
		$this->page_title = $this->ipsclass->lang['ts_title']." &gt; ".$this->topic['title'];
		
		$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
							 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
						   );
	}
	
	/*-------------------------------------------------------------------------*/
	// Unsub all
	/*-------------------------------------------------------------------------*/
	
	function unsubscribe_all()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		// Delete the subbies based on this topic ID
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'tracker', 'where' => "topic_id=".$this->topic['tid'] ) );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['ts_redirect'], "showtopic=".$this->topic['tid']."&amp;st=".intval($this->ipsclass->input['st']) );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// MOVE FORM:
	/*-------------------------------------------------------------------------*/
	
	function move_form()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1) {
			$passed = 1;
		}
		
		else if ($this->moderator['move_topic'] == 1) {
			$passed = 1;
		}
		else {
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		$this->output = $this->html_start_form( array( 1 => array( 'CODE', '14' ),
												       2 => array( 'tid' , $this->topic['tid'] ),
												       3 => array( 'sf'  , $this->forum['id']  ),
		 								      )      );
		 								      
		$tmp_jump_setting = $this->ipsclass->vars['short_forum_jump'];
		$this->ipsclass->vars['short_forum_jump'] = 0;
		$jump_html = $this->ipsclass->build_forum_jump(0,0,1);
		$this->ipsclass->vars['short_forum_jump'] = $tmp_jump_setting;
		unset($tmp_jump_setting);
				 								
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['top_move']." ".$this->forum['name']." &gt; ".$this->topic['title'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_exp( $this->ipsclass->lang['move_exp'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->move_form( $jump_html , $this->forum['name']);
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->end_form( $this->ipsclass->lang['submit_move'] );
		
		$this->page_title = $this->ipsclass->lang['t_move'].": ".$this->topic['title'];
		
		$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
							 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
						   );
	}
	
	/*-------------------------------------------------------------------------*/
	// Complete move
	/*-------------------------------------------------------------------------*/
	
	function do_move()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1) {
			$passed = 1;
		}
		
		else if ($this->moderator['move_topic'] == 1) {
			$passed = 1;
		}
		else {
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		//-----------------------------------------
		// Check for input..
		//-----------------------------------------
		
		if ($this->ipsclass->input['sf'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_no_source' ) );
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->input['move_id'] == "" or $this->ipsclass->input['move_id'] == -1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_no_forum' ) );
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->input['move_id'] == $this->ipsclass->input['sf'])
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_same_forum' ) );
		}
		
		$source = intval($this->ipsclass->input['sf']);
		$moveto = intval($this->ipsclass->input['move_id']);
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, sub_can_post, name, redirect_on', 'from' => 'forums', 'where' => "id IN(".$source.",".$moveto.")" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ($this->ipsclass->DB->get_num_rows() != 2)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_no_forum' ) );
		}
		
		$source_name = "";
		$dest_name   = "";
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		while ( $f = $this->ipsclass->DB->fetch_row() )
		{
			if ($f['id'] == $this->ipsclass->input['sf'])
			{
				$source_name = $f['name'];
			}
			else
			{
				$dest_name = $f['name'];
			}
			
			if ( ($f['sub_can_post'] != 1) OR $f['redirect_on'] == 1 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'forum_no_post_allowed' ) );
			}
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.intval($this->ipsclass->input['tid']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $this->topic = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_no_forum' ) );
		}
		
		$this->ipsclass->input['leave'] = $this->ipsclass->input['leave'] == 'y' ? 1 : 0;
		
		$this->modfunc->topic_move($this->topic['tid'], $this->ipsclass->input['sf'], $this->ipsclass->input['move_id'], $this->ipsclass->input['leave']);
		
		$this->ipsclass->input['t'] = $this->topic['tid'];
		
		$this->moderate_log( sprintf( $this->ipsclass->lang['acp_moved_a_topic'], $source_name, $dest_name ) );
		
		// Resync the forums..
		
		$this->ipsclass->forums->forum_by_id[ $source ]['_update_deletion'] = 1;
		$this->ipsclass->forums->forum_by_id[ $moveto ]['_update_deletion'] = 1;
		
		$this->modfunc->forum_recount($source);
		$this->modfunc->forum_recount($moveto);
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_moved'], "showforum=".$this->forum['id'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Delete post
	/*-------------------------------------------------------------------------*/
	
	function delete_post()
	{
		// Get this post id.
		
		$this->ipsclass->input['p'] = intval($this->ipsclass->input['p']);
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pid, author_id, post_date, new_topic', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} and pid={$this->ipsclass->input['p']}" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $post = $this->ipsclass->DB->fetch_row() )
		{
			$this->moderate_error();
		}
		
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		else if ($this->moderator['delete_post'] == 1)
		{
			$passed = 1;
		}
		else if ( ($this->ipsclass->member['g_delete_own_posts'] == 1) and ( $this->ipsclass->member['id'] == $post['author_id'] ) )
		{
			$passed = 1;
		}
		else 
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		//-----------------------------------------
		// Check to make sure that this isn't the first post in the topic..
		//-----------------------------------------
		
		if ($post['new_topic'] == 1)
		{
			$this->moderate_error('no_delete_post');
		}
		
		if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
		{
			//-----------------------------------------
			// Set up and pass to split topic handler
			//-----------------------------------------
			
			$this->ipsclass->input['checked'] = 1;
			$this->ipsclass->input['fid']     = $this->trash_forum;
			$this->ipsclass->input['title']   = $this->ipsclass->lang['mod_from']." ".$this->topic['title'];
			$this->ipsclass->input['desc']    = $this->ipsclass->lang['mod_from_id']." ".$this->topic['tid'];
			$this->ipsclass->input[ 'post_'.$this->ipsclass->input['p'] ] = 1;
			
			$this->trash_inuse = 1;
			
			$this->multi_split_topic();
			
			$this->trash_inuse = 0;
		}
		else
		{
			$this->modfunc->post_delete( $this->ipsclass->input['p'] );
			$this->modfunc->forum_recount( $this->forum['id'] );
		}
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['post_deleted'], "showtopic=".$this->topic['tid']."&amp;st=".intval($this->ipsclass->input['st']) );
	}
	
	/*-------------------------------------------------------------------------*/
	// DELETE TOPIC:
	/*-------------------------------------------------------------------------*/
	
	function delete_form()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['delete_topic'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->topic['starter_id'] == $this->ipsclass->member['id'])
		{
			if ($this->ipsclass->member['g_delete_own_topics'] == 1)
			{
				$passed = 1;
			}
		}
		
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		$this->output = $this->ipsclass->compiled_templates['skin_mod']->delete_js();
		
		$this->output .= $this->html_start_form( array( 1 => array( 'CODE', '08' ),
												        2 => array( 't', $this->topic['tid'] )
		 								       )      );
		 								
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['top_delete']." ".$this->forum['name']." &gt; ".$this->topic['title'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_exp( $this->ipsclass->lang['delete_topic'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->end_form( $this->ipsclass->lang['submit_delete'] );
		
		$this->page_title = $this->ipsclass->lang['t_delete'].": ".$this->topic['title'];
		
		$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
							 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
						   );
	}
	
	/*-------------------------------------------------------------------------*/
	// Do delete topic
	/*-------------------------------------------------------------------------*/
	
	function delete_topic()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		else if ($this->moderator['delete_topic'] == 1)
		{
			$passed = 1;
		}
		else if ($this->topic['starter_id'] == $this->ipsclass->member['id'])
		{
			if ($this->ipsclass->member['g_delete_own_topics'] == 1)
			{
				$passed = 1;
			}
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		// Do we have a linked topic to remove?
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => "state='link' AND moved_to='".$this->topic['tid'].'&'.$this->forum['id']."'" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $linked_topic = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'topics', 'where' => "tid=".$linked_topic['tid'] ) );
			
			$this->modfunc->forum_recount($linked_topic['forum_id']);
		}
		
		if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
		{
			//-----------------------------------------
			// Move, don't delete
			//-----------------------------------------
			
			$this->modfunc->topic_move($this->topic['tid'], $this->forum['id'], $this->trash_forum);
			
			$this->ipsclass->forums->forum_by_id[ $this->forum['id'] ]['_update_deletion'] = 1;
			$this->ipsclass->forums->forum_by_id[ $this->trash_forum ]['_update_deletion'] = 1;
			
			$this->modfunc->forum_recount($this->forum['id']);
			$this->modfunc->forum_recount($this->trash_forum);
			
			$this->moderate_log("{$this->ipsclass->lang['acp_trashcan_a_topic']} ".$this->topic['tid']);
		}
		else
		{
			$this->modfunc->topic_delete($this->topic['tid']);
			$this->moderate_log( $this->ipsclass->lang['acp_deleted_a_topic'] );
		}
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_deleted'], "showforum=".$this->forum['id'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// EDIT TOPIC:
	/*-------------------------------------------------------------------------*/
	
	function edit_form()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['edit_topic'] == 1)
		{
			$passed = 1;
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		$this->output = $this->html_start_form( array( 1 => array( 'CODE', '12' ),
												       2 => array( 't', $this->topic['tid'] )
		 								      )      );
		 								
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->table_top( $this->ipsclass->lang['top_edit']." ".$this->forum['name']." &gt; ".$this->topic['title'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->mod_exp( $this->ipsclass->lang['edit_topic'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->topictitle_fields( $this->topic['title'], $this->topic['description'] );
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->end_form( $this->ipsclass->lang['submit_edit'] );
		
		$this->page_title = $this->ipsclass->lang['t_edit'].": ".$this->topic['title'];
		
		$this->nav = array ( "<a href='{$this->base_url}showforum={$this->forum['id']}'>{$this->forum['name']}</a>",
							 "<a href='{$this->base_url}showtopic={$this->topic['tid']}'>{$this->topic['title']}</a>"
						   );
	}
	
	/*-------------------------------------------------------------------------*/
	// DO edit
	/*-------------------------------------------------------------------------*/
	
	function do_edit()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['edit_topic'] == 1)
		{
			$passed = 1;
		}
		else
		{
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		if (empty($this->topic['tid']))
		{
			$this->moderate_error();
		}
		
		if ( trim($this->ipsclass->input['TopicTitle']) == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 2, 'MSG' => 'no_topic_title' ) );
		}
		
		$topic_title = str_replace( "'", "\\\'", $this->ipsclass->input['TopicTitle'] );
		$topic_desc  = str_replace( "'", "\\\'", $this->ipsclass->input['TopicDesc']  );
		
		$this->ipsclass->DB->do_update( 'topics', array( 'title' => $topic_title, 'description' => $topic_desc ), 'tid='.$this->topic['tid'] );
										 
		if ($this->topic['tid'] == $this->forum['last_id'])
		{
			$this->ipsclass->DB->do_update( 'forums', array( 'last_title' => $topic_title ), 'id='.$this->forum['id'] );
		}
		
		if ($this->topic['tid'] == $this->forum['newest_id'])
		{
			$this->ipsclass->DB->do_update( 'forums', array( 'newest_title' => $topic_title ), 'id='.$this->forum['id'] );
		}		
		
		$this->ipsclass->update_forum_cache();
		
		$this->moderate_log( sprintf( $this->ipsclass->lang['acp_edit_title'], $this->topic['tid'], $this->topic['title'], $topic_title ) );
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_edited'], "showforum=".$this->forum['id'] );
	}
		
	/*-------------------------------------------------------------------------*/
	// OPEN TOPIC:
	/*-------------------------------------------------------------------------*/
	
	function open_topic()
	{
		if ($this->topic['state'] == 'open')
		{
			$this->moderate_error();
		}
		
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->topic['starter_id'] == $this->ipsclass->member['id'])
		{
			if ($this->ipsclass->member['g_open_close_posts'] == 1)
			{
				$passed = 1;
			}
		}
		else
		{
			$passed = 0;
		}
		
		if ($this->moderator['open_topic'] == 1)
		{
			$passed = 1;
		}
		
		
		if ($passed != 1) $this->moderate_error();
		
		$this->modfunc->topic_open($this->topic['tid']);
		
		$this->moderate_log( $this->ipsclass->lang['acp_opened_topic'] );
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_opened'], "showtopic=".$this->topic['tid']."&amp;st=".intval($this->ipsclass->input['st']) );
	}
	
	/*-------------------------------------------------------------------------*/
	// CLOSE TOPIC:
	/*-------------------------------------------------------------------------*/
	
	function close_topic()
	{
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->topic['starter_id'] == $this->ipsclass->member['id'])
		{
			if ($this->ipsclass->member['g_open_close_posts'] == 1)
			{
				$passed = 1;
			}
		}
		else
		{
			$passed = 0;
		}
		
		if ($this->moderator['close_topic'] == 1)
		{
			$passed = 1;
		}
		
		
		if ($passed != 1) $this->moderate_error();
		
		$this->modfunc->topic_close($this->topic['tid']);
		
		$this->moderate_log( $this->ipsclass->lang['acp_locked_topic'] );
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_closed'], "showforum=".$this->forum['id'] );
	}

	/*-------------------------------------------------------------------------*/
	// PIN TOPIC:
	/*-------------------------------------------------------------------------*/
	
	function pin_topic()
	{
		if ($this->topic['PIN_STATE'] == 1)
		{
			$this->moderate_error();
		}
		
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['pin_topic'] == 1)
		{
			$passed = 1;
		}
		else {
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		$this->modfunc->topic_pin($this->topic['tid']);
		
		$this->moderate_log( $this->ipsclass->lang['acp_pinned_topic'] );
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_pinned'], "showtopic=".$this->topic['tid']."&amp;st=".intval($this->ipsclass->input['st']) );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// UNPIN TOPIC:
	/*-------------------------------------------------------------------------*/
	
	function unpin_topic()
	{
		if ($this->topic['pinned'] == 0)
		{
			$this->moderate_error();
		}
		
		$passed = 0;
		
		if ($this->ipsclass->member['g_is_supmod'] == 1)
		{
			$passed = 1;
		}
		
		else if ($this->moderator['unpin_topic'] == 1)
		{
			$passed = 1;
		}
		else {
			$passed = 0;
		}
		
		if ($passed != 1) $this->moderate_error();
		
		$this->modfunc->topic_unpin($this->topic['tid']);
		
		$this->moderate_log( $this->ipsclass->lang['acp_unpinned_topic'] );
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['p_unpinned'], "showtopic=".$this->topic['tid']."&amp;st=".intval($this->ipsclass->input['st']) );
	}
	
	/*-------------------------------------------------------------------------*/
	// GET TOPIC IDS
	/*-------------------------------------------------------------------------*/
	
	function get_tids()
	{
		$ids = array();
 		
 		$ids = explode( ',', $this->ipsclass->input['selectedtids'] );
 		
 		if ( count($ids) < 1 )
 		{
 			$this->moderate_error('cp_error_no_topics');
 			return;
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		return $ids;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get Pids
	/*-------------------------------------------------------------------------*/
	
	function get_pids()
	{
		$ids = array();
 		
 		$ids = explode( ',', $this->ipsclass->input['selectedpids'] );
 		
 		if ( count($ids) < 1 )
 		{
 			$this->moderate_error('cp_error_no_topics');
 			return;
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		return $ids;
	}
	
	/*-------------------------------------------------------------------------*/
	// MODERATE ERROR:
	/*-------------------------------------------------------------------------*/
	
	function moderate_error($msg = 'moderate_no_permission')
	{
		$this->ipsclass->Error( array( 'LEVEL' => 2, 'MSG' => $msg ) );
		
		// Make sure we exit..
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// MODERATE LOG:
	/*-------------------------------------------------------------------------*/
	
	function moderate_log($title = 'unknown')
	{
		$this->modfunc->add_moderate_log( $this->ipsclass->input['f'], $this->ipsclass->input['t'], $this->ipsclass->input['p'], $this->topic['title'], $title );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Re Count topics for the forums:
	/*-------------------------------------------------------------------------*/
	
	function recount($fid="")
	{
		if ($fid == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'move_no_source' ) );
		}
		
		$this->modfunc->forum_recount( $fid );
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: start form.
	/*-------------------------------------------------------------------------*/
	
	function html_start_form($additional_tags=array())
	{
		$form = "<form action='{$this->base_url}' method='POST' name='REPLIER'>".
				"<input type='hidden' name='st' value='".$this->ipsclass->input['st']."' />".
				"<input type='hidden' name='act' value='mod' />".
				"<input type='hidden' name='s' value='".$this->ipsclass->session_id."' />".
				"<input type='hidden' name='f' value='".$this->forum['id']."' />".
				"<input type='hidden' name='selectedpids' value='".$this->ipsclass->input['selectedpids']."' />".
				"<input type='hidden' name='auth_key' value='".$this->ipsclass->return_md5_check()."' />";
				
		// Any other tags to add?
		
		if ( count( $additional_tags ) )
		{
			foreach( $additional_tags as $v )
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}'>";
			}
		}
		
		return $form;
    }
    
    /*-------------------------------------------------------------------------*/
	// Faster Pussycat, Kill, Kill!
	/*-------------------------------------------------------------------------*/
	
	function bash_uploaded_photos($id)
	{
		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $this->ipsclass->vars['upload_dir']."/photo-".$id.".".$ext ) )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/photo-".$id.".".$ext );
			}
		}
	}
	
	function bash_uploaded_avatars($id)
	{
		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $this->ipsclass->vars['upload_dir']."/av-".$id.".".$ext ) )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/av-".$id.".".$ext );
			}
		}
	}
}

?>