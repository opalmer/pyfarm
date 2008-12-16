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
|   > $Date: 2007-08-21 17:48:41 -0400 (Tue, 21 Aug 2007) $
|   > $Revision: 1099 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Forum functions
|   > Module written by Matt Mecham
|   > Date started: 1st march 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_forums
{
	# Global
	var $ipsclass;
	var $html;
	var $forumfunc;
	
	var $base_url;
	
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
	var $perm_child = "forum";
	
	/*-------------------------------------------------------------------------*/
	// RUN!
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		$this->ipsclass->input['showall'] = isset($this->ipsclass->input['showall']) ? $this->ipsclass->input['showall'] : 0;
		
		$this->ipsclass->forums->forums_init();
		
		//-----------------------------------------
		// Load class
		//-----------------------------------------
		
		require ROOT_PATH.'sources/lib/admin_forum_functions.php';
		
		$this->forumfunc = new admin_forum_functions();
		$this->forumfunc->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html            = $this->ipsclass->acp_load_template('cp_skin_forums');
		$this->forumfunc->html =& $this->html;
		
		//-----------------------------------------
		// To do...
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'new':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->forum_form('new');
				break;
			case 'donew':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->forum_save('new');
				break;
			//-----------------------------------------
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->forum_form('edit');
				break;
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->forum_save('edit');
				break;
			//-----------------------------------------
			case 'pedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':permedit' );
				$this->perm_edit_form();
				break;
			case 'pdoedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':permedit' );
				$this->perm_do_edit();
				break;
			//-----------------------------------------
			case 'reorder':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':reorder' );
				$this->reorder_form();
				break;
			case 'doreorder':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':reorder' );
				$this->do_reorder();
				break;
			case 'doreordercat':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':reorder' );
				$this->do_reorder();
				break;
			//-----------------------------------------
			case 'delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->delete_form();
				break;
			case 'dodelete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->do_delete();
				break;
			//-----------------------------------------
			case 'recount':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recount' );
				$this->recount();
				break;
			//-----------------------------------------
			case 'empty':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':empty' );
				$this->empty_form();
				break;
			case 'doempty':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':empty' );
				$this->do_empty();
				break;
			//-----------------------------------------
			case 'frules':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rules' );
				$this->show_rules();
				break;
			case 'dorules':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rules' );
				$this->do_rules();
				break;
			//-----------------------------------------
			case 'skinedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':skin' );
				$this->skin_edit();
				break;
			case 'doskinedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':skin' );
				$this->do_skin_edit();
				break;
			//-----------------------------------------	
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->show_forums();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit forum skins
	/*-------------------------------------------------------------------------*/
	
	function skin_edit()
	{
		if ($this->ipsclass->input['f'] == "")
		{
			$this->ipsclass->admin->error("Could not determine the forum ID to empty.");
		}
		
		$forum = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ];
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( ! $forum['id'] )
		{
			$this->ipsclass->admin->error("Could not resolve that forum ID");
		}
		
		if ( ! $forum['skin_id'] )
		{
			$forum['skin_id'] = -1;
		}
		
		//-----------------------------------------
		// Get skins..
		//-----------------------------------------
		
		$tmp = $this->ipsclass->skin['_setid'];
		
		$this->ipsclass->skin['_setid'] = $forum['skin_id'];
		
		require_once( ROOT_PATH.'sources/classes/class_display.php' );
		$display           =  new display();
		$display->ipsclass =& $this->ipsclass;
		
		$skin_list = $display->_build_skin_list();
		
		$this->ipsclass->skin['_setid'] = $tmp;
		
		//-----------------------------------------
		// Do form..
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Forum Skin Options";
		$this->ipsclass->admin->page_detail = "You may choose to either add or remove a skin set to this forum. The skin choice will override the users choice.";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , 'doskinedit'),
																			 2 => array( 'act'    , 'forum'  ),
																			 3 => array( 'section', 'content'  ),
																			 4 => array( 'f'      , $this->ipsclass->input['f'] ),
																	   ) );
		
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Skin choices for forum: {$forum['name']}" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Apply which skin to this forum?</b>" ,
																 "<select class='dropdown' name='fsid'><option value='-1'>--None / Remove All--</option>{$skin_list}</select>"
														 )      );
														 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Apply to all children of this forum (all sub-forums)</b>" ,
																 $this->ipsclass->adskin->form_yes_no( 'apply_to_children' )
														 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Edit forum skin options");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Complete forum skin edit
	/*-------------------------------------------------------------------------*/
	
	function do_skin_edit()
	{
		if ($this->ipsclass->input['f'] == "")
		{
			$this->ipsclass->admin->error("Could not determine the forum ID to apply this skin to.");
		}
		
		$forum = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ];
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'forums', array( 'skin_id' => $this->ipsclass->input['fsid'] ), 'id='.$this->ipsclass->input['f'] );
		
		//-----------------------------------------
		// Find children?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['apply_to_children'] )
		{
			//-----------------------------------------
			// Get children!
			//-----------------------------------------
			
			$ids = $this->ipsclass->forums->forums_get_children( $this->ipsclass->input['f'] );
			
			if ( count( $ids ) )
			{
				$this->ipsclass->DB->do_update( 'forums', array( 'skin_id' => $this->ipsclass->input['fsid'] ), 'id IN ('.implode(",",$ids).')' );
			}
		}
		
		$this->ipsclass->main_msg = "Forum skin updated";
		
		$this->recache_forums();
		
		$this->ipsclass->forums->forums_init();
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		$this->ipsclass->input['f'] = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'];
		$this->show_forums();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show forum rules
	/*-------------------------------------------------------------------------*/
	
	function show_rules()
	{
		if ($this->ipsclass->input['f'] == "")
		{
			$this->ipsclass->admin->error("Could not determine the forum ID to empty.");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, show_rules, rules_title, rules_text', 'from' => 'forums', 'where' => "id=".$this->ipsclass->input['f'] ) );
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->admin->error("Could not resolve that forum ID");
		}
		
		$forum = $this->ipsclass->DB->fetch_row();
		
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
		$this->han_editor->from_acp = 1;
        $this->han_editor->init();
        
  		$this->han_editor->ed_width = "550px";
  		$this->han_editor->ed_height = "200px";
  		$this->ipsclass->vars['rte_width'] = "500px";
  		$this->ipsclass->vars['rte_height'] = "200px";
 		
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = 1;				
		
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Forum Rules";
		$this->ipsclass->admin->page_detail = "You may edit, add, remove or change the state of the forum rules display";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , 'dorules'),
																			 2 => array( 'act'    , 'forum'  ),
																			 3 => array( 'section', 'content'  ),
																			 4 => array( 'f'      , $this->ipsclass->input['f'] ),
																	   ), "theAdminForm", "onclick='return ValidateForm();'", "postingform"  );
		
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Forum Rules set up" );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Display method</b>" ,
																 $this->ipsclass->adskin->form_dropdown( "show_rules",
																					   array( 
																							   0 => array( '0' , 'Don\'t Show' ),
																							   1 => array( '1' , 'Show Link Only' ),
																							   2 => array( '2' , 'Show full text' )
																							),
																					   $forum['show_rules']
																					 )
														)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rules Title</b>" ,
																 $this->ipsclass->adskin->form_input("title", $this->ipsclass->txt_stripslashes(str_replace( "'", '&#039;', $forum['rules_title'])))
														)      );
									     
		if ( $this->han_editor->method == 'rte' )
		{
			$forum['rules_text'] = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $forum['rules_text'] ) );
			$forum['rules_text'] = $this->parser->convert_ipb_html_to_html( $forum['rules_text'] );
		}
		else
		{
			$this->parser->parse_html    = 1;
			$this->parser->parse_nl2br   = 1;
			$this->parser->parse_smilies = 0;
			$this->parser->parse_bbcode  = 1;
			
			$forum['rules_text'] = $this->parser->pre_edit_parse( $forum['rules_text'] );
		}
		
		$form_element = $this->han_editor->show_editor( $forum['rules_text'], 'body' );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rules Text</b><br>(HTML Editing Mode)" ,
																 $form_element
														)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Edit forum rules");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Save forum rules
	/*-------------------------------------------------------------------------*/
	
	function do_rules()
	{
		if ($this->ipsclass->input['f'] == "")
		{
			$this->ipsclass->admin->error("Could not determine the forum ID to empty.");
		}
		
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->from_acp = 1;
        $this->han_editor->init();
 		
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = 1;
			        
        $_POST[ 'body' ] = $this->han_editor->process_raw_post( 'body' );
		$this->parser->parse_smilies    = 0;
		$this->parser->parse_html       = 1;
		$this->parser->parse_bbcode     = 1;
		$_POST[ 'body' ]        			= $this->parser->pre_display_parse( $this->parser->pre_db_parse( $_POST[ 'body' ] ) );		
		
		$rules = array( 
						'rules_title'    => $this->ipsclass->admin->make_safe($this->ipsclass->txt_stripslashes($_POST['title'])),
						'rules_text'     => $this->ipsclass->admin->make_safe($_POST['body']),
						'show_rules'     => $this->ipsclass->input['show_rules']
					  );
					  
		$this->ipsclass->DB->do_update( 'forums', $rules, 'id='.$this->ipsclass->input['f'] );
		
		$this->recache_forums();
		$this->ipsclass->main_msg = "Forum rules updated";
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		$this->ipsclass->input['f'] = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'];
		$this->show_forums();
	}
	
	/*-------------------------------------------------------------------------*/
	// RECOUNT FORUM: Recounts topics and posts
	/*-------------------------------------------------------------------------*/
	
	function recount($f_override="")
	{
		if ($f_override != "")
		{
			// Internal call, remap
			
			$this->ipsclass->input['f'] = $f_override;
		}
		
		require_once( ROOT_PATH.'sources/lib/func_mod.php' );
		$modfunc = new func_mod();
		$modfunc->ipsclass =& $this->ipsclass;
		
		$modfunc->forum_recount($this->ipsclass->input['f']);

		$this->recache_forums();
		
		$this->ipsclass->admin->save_log("Recounted posts in forum '{$this->ipsclass->forums->forum_by_id[$this->ipsclass->input['f']]['name']}'");
		
		$this->ipsclass->main_msg = "Forum resynschronized";
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		$this->ipsclass->input['f'] = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'];
		$this->show_forums();
	}
	
	/*-------------------------------------------------------------------------*/
	// EMPTY FORUM: Removes all topics and posts, etc.
	/*-------------------------------------------------------------------------*/
	
	function empty_form()
	{
		$form_array = array();
		
		if ($this->ipsclass->input['f'] == "")
		{
			$this->ipsclass->admin->error("Could not determine the forum ID to empty.");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name', 'from' => 'forums', 'where' => "id=".$this->ipsclass->input['f'] ) );
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( !$this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->admin->error("Could not resolve that forum ID");
		}
		
		$forum = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title = "Empty Forum '{$forum['name']}'";
		
		$this->ipsclass->admin->page_detail = "This WILL DELETE ALL TOPICS, POSTS AND POLLS.<br>The forum itself will not be deleted - please ensure you wish to carry out this action before continuing.";
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'doempty'      ),
																			 2 => array( 'act'     , 'forum'        ),
																			 3 => array( 'section' , 'content'      ),
																			 4 => array( 'f'       , $this->ipsclass->input['f']  ),
																			 5 => array( 'name'    , $forum['name'] ),
																	   ) );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Empty Forum '{$forum['name']}'" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Forum to empty: </b>" , $forum['name'] )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Empty this forum");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Do empty
	/*-------------------------------------------------------------------------*/
	
	function do_empty()
	{
		//-----------------------------------------
		// Get module
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_mod.php' );
		$modfunc           =  new func_mod();
		$modfunc->ipsclass =& $this->ipsclass;
		
		if ($this->ipsclass->input['f'] == "")
		{
			$this->ipsclass->admin->error("Could not determine the source forum ID.");
		}
		
		//-----------------------------------------
		// Check to make sure its a valid forum.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, posts, topics', 'from' => 'forums', 'where' => "id=".$this->ipsclass->input['f'] ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $forum = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not get the forum details for the forum to empty");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid', 'from' => 'topics', 'where' => "forum_id=".$this->ipsclass->input['f'] ) );
		$outer = $this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// What to do..
		//-----------------------------------------
		
		while( $t = $this->ipsclass->DB->fetch_row($outer) )
		{
			$modfunc->topic_delete($t['tid']);
		}
		
		//-----------------------------------------
		// Rebuild stats
		//-----------------------------------------
		
		$modfunc->forum_recount($this->ipsclass->input['f']);
		$modfunc->stats_recount();
		
		//-----------------------------------------
		// Rebuild forum cache
		//-----------------------------------------
		
		$this->recache_forums();
		
		$this->ipsclass->admin->save_log("Emptied forum '{$this->ipsclass->input['name']}' of all posts");
		
		$this->ipsclass->input['f'] =  $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'];
		$this->ipsclass->main_msg   = "Forum emptied";
		$this->show_forums();
	}
	
	/*-------------------------------------------------------------------------*/
	// REMOVE FORUM
	/*-------------------------------------------------------------------------*/
	
	function delete_form()
	{
		$form_array = array();
		
		$this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
		
		if ( ! $this->ipsclass->input['f'] )
		{
			$this->ipsclass->admin->error("Could not determine the forum or category ID to delete.");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, parent_id', 'from' => 'forums', 'order' => 'position' ) );
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// Make sure we have more than 1
		// forum..
		//-----------------------------------------
		
		if ( $this->ipsclass->DB->get_num_rows() < 2 )
		{
			$this->ipsclass->admin->error("Can not remove this forum, please create another before attempting to remove this one");
		}
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ($r['id'] == $this->ipsclass->input['f'])
			{
				$name 	= $r['name'];
				$is_cat	= $r['parent_id'] > 0 ? 0 : 1;
				continue;
			}
		}
		
		$form_array = $this->forumfunc->ad_forums_forum_list(1);
		
		//-----------------------------------------
		// Count the number of topics
		//-----------------------------------------
		
		$posts = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'topics', 'where' => 'forum_id='.$this->ipsclass->input['f'] ) );
		
		//-----------------------------------------
		// Count the number of children
		//-----------------------------------------
		
		$children = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'forums', 'where' => 'parent_id='.$this->ipsclass->input['f'] ) );
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$text = $is_cat ? "Category" : "Forum";
		
		$this->ipsclass->admin->page_title = "Removing {$text} '{$name}'";
		
		$this->ipsclass->admin->page_detail = "Before we remove this {$text}, if this {$text} is not empty, we need to determine what to do with any topics and posts you may have left in this {$text}.";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , 'dodelete'),
																			 2 => array( 'act'    , 'forum'     ),
																			 3 => array( 'section', 'content'     ),
																			 4 => array( 'f'      , $this->ipsclass->input['f']  ),
																	   ) );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		// Main form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Required" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$text} to remove: </b>" , $name )      );
		
		if ( $posts['count'] )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Move all <i>existing topics and posts in this {$text}</i> to which forum?</b>" ,
																	$this->ipsclass->adskin->form_dropdown( "MOVE_ID", $form_array )
														  )      );
			
		}
		
		if ( $children['count'] )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Move all <i>children forums</i> to which forum or category?</b>" ,
																	$this->ipsclass->adskin->form_dropdown( "new_parent_id", $form_array )
														  )      );
		}
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Remove {$text}" );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// PROCESS DELETE
	/*-------------------------------------------------------------------------*/
	
	function do_delete()
	{
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->ipsclass->input['f']             = intval($this->ipsclass->input['f']);
		$this->ipsclass->input['MOVE_ID']       = intval($this->ipsclass->input['MOVE_ID']);
		$this->ipsclass->input['new_parent_id'] = intval($this->ipsclass->input['new_parent_id']);
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forums', 'where' => "id=".$this->ipsclass->input['f'] ) );
		$this->ipsclass->DB->simple_exec();
		
		$forum = $this->ipsclass->DB->fetch_row();
		
		if ( ! $this->ipsclass->input['f'] )
		{
			$this->ipsclass->admin->error("Could not determine the source forum ID.");
		}
		
		if ( ! $this->ipsclass->input['new_parent_id'] )
		{
			$this->ipsclass->input['new_parent_id'] = -1;
		}
		else
		{
			if ( $this->ipsclass->input['new_parent_id'] == $this->ipsclass->input['f'] )
			{
				$this->ipsclass->main_msg = "You cannot move children forums to the forum you're removing!";
				$this->delete_form();
			}
		}
		
		//-----------------------------------------
		// Get library
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_mod.php' );
		$modfunc           =  new func_mod();
		$modfunc->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Move stuff
		//-----------------------------------------
		
		if ( $this->ipsclass->input['MOVE_ID'] )
		{
			if ( $this->ipsclass->input['MOVE_ID'] == $this->ipsclass->input['f'] )
			{
				$this->ipsclass->main_msg = "You cannot move topics into the forum you're removing!";
				$this->delete_form();
			}
			
			//-----------------------------------------
			// Move topics...
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'topics', array( 'forum_id' => $this->ipsclass->input['MOVE_ID'] ), 'forum_id='.$this->ipsclass->input['f'] );
			
			//-----------------------------------------
			// Move polls...
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'polls', array( 'forum_id' => $this->ipsclass->input['MOVE_ID'] ), 'forum_id='.$this->ipsclass->input['f'] );
			
			//-----------------------------------------
			// Move voters...
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'voters', array( 'forum_id' => $this->ipsclass->input['MOVE_ID'] ), 'forum_id='.$this->ipsclass->input['f'] );
			
			$modfunc->forum_recount( $this->ipsclass->input['MOVE_ID'] );
		}
		
		//-----------------------------------------
		// Delete the forum
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'forums', 'where' => "id=".$this->ipsclass->input['f'] ) );
		
		//-----------------------------------------
		// Delete any moderators, if any..
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'moderators', 'where' => "forum_id=".$this->ipsclass->input['f'] ) );
		
		//-----------------------------------------
		// Delete forum subscriptions
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'forum_tracker', 'where' => "forum_id=".$this->ipsclass->input['f'] ) );
		
		//-----------------------------------------
		// Update children
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'forums', array( 'parent_id' => $this->ipsclass->input['new_parent_id'] ), "parent_id={$this->ipsclass->input['f']}" );
		
		//-----------------------------------------
		// Rebuild forum cache
		//-----------------------------------------
		
		$this->recache_forums();
		
		//-----------------------------------------
		// Rebuild moderator cache
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/moderator.php' );
		$moderator = new ad_moderator();
		$moderator->ipsclass =& $this->ipsclass;
		
		$moderator->rebuild_moderator_cache();
		
		$this->ipsclass->admin->save_log("Removed forum '{$forum['name']}'");
		
		$this->ipsclass->admin->done_screen("Forum Removed", "Forum Control", $this->ipsclass->form_code, 'redirect' );
	}

	

	/*-------------------------------------------------------------------------*/
	// ADD / EDIT FORUM
	/*-------------------------------------------------------------------------*/

	function forum_form( $type='edit', $changetype=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$addnew_type = ( isset($this->ipsclass->input['type']) AND $this->ipsclass->input['type'] ) ? $this->ipsclass->input['type'] : 'forum';
		
		$form        = array();
		$forum       = array();
		$forum_id    = isset($this->ipsclass->input['f']) ? intval($this->ipsclass->input['f']) : 0;
		$parentid    = intval($this->ipsclass->input['p']) ? intval($this->ipsclass->input['p']) : -1;
		$cat_id      = isset($this->ipsclass->input['c']) ? intval($this->ipsclass->input['c']) : 0;
		$f_name      = isset($this->ipsclass->input['name']) ? $this->ipsclass->input['name'] : '';
		$subcanpost  = ( $cat_id == 1 ) ? 0 : 1;
		$perm_matrix = "";
		$dd_state    = array( 0 => array( 1, 'Active' ), 1 => array( 0, 'Read Only Archive' ) );
		$dd_moderate = array(
							 0 => array( 0, 'No' ),
							 1 => array( 1, 'Moderate all new topics and all replies' ),
							 2 => array( 2, 'Moderate new topics but don\'t moderate replies' ),
							 3 => array( 3, 'Moderate replies but don\'t moderate new topics' ),
							);
		$dd_prune    = array( 
							 0 => array( 1, 'Today' ),
							 1 => array( 5, 'Last 5 days'  ),
							 2 => array( 7, 'Last 7 days'  ),
							 3 => array( 10, 'Last 10 days' ),
							 4 => array( 15, 'Last 15 days' ),
							 5 => array( 20, 'Last 20 days' ),
							 6 => array( 25, 'Last 25 days' ),
							 7 => array( 30, 'Last 30 days' ),
							 8 => array( 60, 'Last 60 days' ),
							 9 => array( 90, 'Last 90 days' ),
							 10=> array( 100,'Show All'     ),
							);
		
		$dd_order    = array( 
							 0 => array( 'last_post', 'Date of the last post' ),
							 1 => array( 'title'    , 'Topic Title' ),
							 2 => array( 'starter_name', 'Topic Starters Name' ),
							 3 => array( 'posts'    , 'Topic Posts' ),
							 4 => array( 'views'    , 'Topic Views' ),
							 5 => array( 'start_date', 'Date topic started' ),
							 6 => array( 'last_poster_name'   , 'Name of the last poster' )
							);
																							
																							
																							
		$dd_by       = array( 
							 0 => array( 'Z-A', 'Descending (Z - A, 0 - 10)' ),
							 1 => array( 'A-Z', 'Ascending (A - Z, 10 - 0)'  )
							);
							
		$dd_filter	 = array(
							 0 => array( 'all', 	'All Topics' ),
							 1 => array( 'open', 	'Open Topics' ),
							 2 => array( 'hot',		'Hot Topics' ),
							 3 => array( 'poll',	'Polls' ),
							 4 => array( 'locked',	'Locked Topics' ),
							 5 => array( 'moved',	'Moved Topics' ),
							 6 => array( 'istarted', 'Topics Reader Started' ),
							 7 => array( 'ireplied', 'Topics Reader Replied' ),
							);
							 
																									 
		//-----------------------------------------
		// Set up title, desc
		//-----------------------------------------
		
		$this->ipsclass->admin->page_detail = "This section will allow you to add or edit an existing {$addnew_type}. If you wish to adjust the forum permissions (who has the ability to
							   			 	   start, reply and read topics) click on 'Edit Permissions on the Forums and Categories overview.";
		
		//-----------------------------------------
		// EDIT
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ( ! $forum_id )
			{
				$this->ipsclass->admin->error("You didn't choose a forum to edit, duh!");
			}
			
			//-----------------------------------------
			// Do not show forum in forum list
			//-----------------------------------------
			
			$this->forumfunc->exclude_from_list = $forum_id;
			
			//-----------------------------------------
			// Get this forum
			//-----------------------------------------
			
			$forum = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'forums', 'where' => 'id='.$this->ipsclass->input['f'] ) );
			
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ($forum['id'] == "")
			{
				$this->ipsclass->admin->error("Could not retrieve the forum data based on ID {$this->ipsclass->input['f']}");
			}
			
			//-----------------------------------------
			// Set up code buttons
			//-----------------------------------------
			
			$addnew_type	= $forum['parent_id'] == -1 ? 'category' : 'forum';
			
			if( $changetype )
			{
				$addnew_type = $addnew_type == 'category' ? 'forum' : 'category';
			}
			
			$title  		= "Editing {$addnew_type}: {$forum['name']}";
			$button 		= "Edit {$addnew_type}";
			$code   		= "doedit";
			
			if( $addnew_type == 'category' )
			{
				$perms 		= unserialize(stripslashes($forum['permission_array']));
				$forum 		= array_merge( $forum, $perms );
				
				$convert	= "<input type='submit' class='realbutton' onclick='do_convert()' value='Change to Forum' />";
			}
			else
			{
				$convert	= "<input type='submit' class='realbutton' onclick='do_convert()' value='Change to Category' />";
			}
			
			//-----------------------------------------
			// Basic title
			//-----------------------------------------
			
			$basic_title = "<table cellpadding='0' cellspacing='0' border='0' width='100%'>
							<tr>
							 <td align='left' width='40%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>Basic Settings for {$forum['name']}</td>
							 <td align='right' width='60%'>".
							 $this->ipsclass->adskin->js_make_button("Edit {$addnew_type} Rules"  , $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=frules&f={$this->ipsclass->input['f']}")."&nbsp;".
						     $this->ipsclass->adskin->js_make_button("Edit Skin Settings", $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=skinedit&f={$this->ipsclass->input['f']}")."&nbsp;".
						     $this->ipsclass->adskin->js_make_button("Recount {$addnew_type}"     , $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=recount&f={$this->ipsclass->input['f']}")
							 ."&nbsp;&nbsp;</td>
							</tr>
							</table>";
		}
		
		//-----------------------------------------
		// NEW
		//-----------------------------------------
		
		else
		{
			# Ensure there is an ID
			$this->ipsclass->input['f'] = 0;
			
			if( $changetype )
			{
				$addnew_type = $addnew_type == 'category' ? 'forum' : 'category';
			}
			
			$forum = array(
							'sub_can_post'				=> $subcanpost,
							'name'						=> $f_name ? $f_name : 'New '.ucwords($addnew_type),
							'parent_id'					=> $parentid,
							'use_ibc'					=> 1,
							'quick_reply'				=> 1,
							'allow_poll'				=> 1,
							'prune'						=> 100,
							'topicfilter'				=> 'all',
							'sort_key'					=> 'last_post',
							'sort_order'				=> 'Z-A',
							'inc_postcount'				=> 1,
							'description'				=> '',
							'status'					=> 0,
							'redirect_url'				=> '',
							'password'					=> '',
							'password_override'			=> '',
							'redirect_on'				=> 0,
							'redirect_hits'				=> 0,
							'permission_showtopic'		=> '',
							'permission_custom_error'	=> '',
							'use_html'					=> 0,
							'allow_pollbump'			=> 0,
							'forum_allow_rating'		=> 0,
							'preview_posts'				=> 0,
							'notify_modq_emails'		=> 0,
							'show_perms'				=> '',
							'read_perms'				=> '',
							'start_perms'				=> '',
							'reply_perms'				=> '',
							'upload_perms'				=> '',
							'download_perms'			=> '',
							
						  );
						  
			$title       = "Add a ".ucwords($addnew_type);
			$button      = "Add ".ucwords($addnew_type);
			$code        = "donew";
			$basic_title = 'Basic Settings';
			
			if( $addnew_type == 'category' )
			{
				$convert	= "<input type='submit' class='realbutton' onclick='do_convert()' value='Change to Forum' />";
			}
			else
			{
				$convert	= "<input type='submit' class='realbutton' onclick='do_convert()' value='Change to Category' />";
			}
		}

		//-----------------------------------------
		// Build forumlist
		//-----------------------------------------
		
		$forumlist = $this->forumfunc->ad_forums_forum_list();
		
		//-----------------------------------------
		// Build group list
		//-----------------------------------------		
		
		$mem_group = array();
		
		foreach( $this->ipsclass->cache['group_cache'] as $g_id => $group )
		{
			$mem_group[] = array( $g_id , $group['g_title'] );
		}		
		
		//-----------------------------------------
		// Page title...
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title = $title;
		
		//-----------------------------------------
		// Generate form items
		//-----------------------------------------
		
		# Main settings
		$form['name']         = $this->ipsclass->adskin->form_input(   'name'        , ( isset($_POST['name']) AND $_POST['name'] ) ? $this->ipsclass->parse_clean_value( $_POST['name'] ) : $forum['name'] );
		$form['description']  = $this->ipsclass->adskin->form_textarea("description" , $this->ipsclass->my_br2nl( ( isset($_POST['description']) AND $_POST['description']  )? $_POST['description'] : $forum['description'] ) );
		$form['parent_id']    = $this->ipsclass->adskin->form_dropdown("parent_id"   , $forumlist, ( isset($_POST['parent_id']) AND $_POST['parent_id'] ) 	? $_POST['parent_id']    : $forum['parent_id'] );
		$form['status']       = $this->ipsclass->adskin->form_dropdown("status"      , $dd_state , ( isset($_POST['status']) AND $_POST['status'] )    		? $_POST['status']       : $forum['status'] );
		$form['sub_can_post'] = $this->ipsclass->adskin->form_yes_no(  'sub_can_post', ( isset($_POST['sub_can_post']) AND $_POST['sub_can_post'] )         ? $_POST['sub_can_post'] : ( $forum['sub_can_post'] == 1 ? 0 : 1 ) );
		
		# Redirect options
		$form['redirect_url']  = $this->ipsclass->adskin->form_input( 'redirect_url' , ( isset($_POST['redirect_url']) 	AND $_POST['redirect_url'] )  ? $_POST['redirect_url']  : $forum['redirect_url']  );
		$form['redirect_on']   = $this->ipsclass->adskin->form_yes_no('redirect_on'  , ( isset($_POST['redirect_on']) 	AND $_POST['redirect_on'] )   ? $_POST['redirect_on']   : $forum['redirect_on']   );
		$form['redirect_hits'] = $this->ipsclass->adskin->form_input( 'redirect_hits', ( isset($_POST['redirect_hits']) AND $_POST['redirect_hits'] ) ? $_POST['redirect_hits'] : $forum['redirect_hits'] );
		
		# Permission settings
		$form['permission_showtopic']    = $this->ipsclass->adskin->form_yes_no(  'permission_showtopic'   , ( isset($_POST['permission_showtopic']) AND $_POST['permission_showtopic'] ) ? $_POST['permission_showtopic'] : $forum['permission_showtopic'] );
		$form['permission_custom_error'] = $this->ipsclass->adskin->form_textarea("permission_custom_error", $this->ipsclass->my_br2nl( ( isset($_POST['permission_custom_error']) AND $_POST['permission_custom_error'] ) ? $_POST['permission_custom_error'] : $forum['permission_custom_error'] ) );
		
		# Forum settings
		$form['use_html']           = $this->ipsclass->adskin->form_yes_no('use_html'          , ( isset($_POST['use_html']) 			AND $_POST['use_html'] )           	? $_POST['use_html']            : $forum['use_html'] );
		$form['use_ibc']            = $this->ipsclass->adskin->form_yes_no('use_ibc'           , ( isset($_POST['use_ibc']) 			AND $_POST['use_ibc'] )            	? $_POST['use_ibc']             : $forum['use_ibc']  );
		$form['quick_reply']        = $this->ipsclass->adskin->form_yes_no('quick_reply'       , ( isset($_POST['quick_reply']) 		AND $_POST['quick_reply'] )         ? $_POST['quick_reply']         : $forum['quick_reply']  );
		$form['allow_poll']         = $this->ipsclass->adskin->form_yes_no('allow_poll'        , ( isset($_POST['allow_poll']) 			AND $_POST['allow_poll'] )          ? $_POST['allow_poll']          : $forum['allow_poll']  );
		$form['allow_pollbump']     = $this->ipsclass->adskin->form_yes_no('allow_pollbump'    , ( isset($_POST['allow_pollbump']) 		AND $_POST['allow_pollbump'] )      ? $_POST['allow_pollbump']      : $forum['allow_pollbump']  );
		$form['inc_postcount']      = $this->ipsclass->adskin->form_yes_no('inc_postcount'     , ( isset($_POST['inc_postcount']) 		AND $_POST['inc_postcount'] )       ? $_POST['inc_postcount']       : $forum['inc_postcount']  );
		$form['forum_allow_rating'] = $this->ipsclass->adskin->form_yes_no('forum_allow_rating', ( isset($_POST['forum_allow_rating']) 	AND $_POST['forum_allow_rating'] )  ? $_POST['forum_allow_rating']  : $forum['forum_allow_rating']  );
		
		# Mod settings
		$form['preview_posts']      = $this->ipsclass->adskin->form_dropdown(		"preview_posts"    		, $dd_moderate, ( isset($_POST['preview_posts']) AND $_POST['preview_posts'] ) ? $_POST['preview_posts'] 	: $forum['preview_posts'] );
		$form['notify_modq_emails'] = $this->ipsclass->adskin->form_input(  		'notify_modq_emails'	, ( isset($_POST['notify_modq_emails']) AND $_POST['notify_modq_emails'] ) ? $_POST['notify_modq_emails'] 	: $forum['notify_modq_emails'] );
		$form['password']           = $this->ipsclass->adskin->form_input(  		'password'          	, ( isset($_POST['password']) 			AND $_POST['password'] )           ? $_POST['password']           	: $forum['password'] );
		$form['password_override']  = $this->ipsclass->adskin->form_multiselect(  	'password_override[]'	, $mem_group, ( isset($_POST['password_override']) AND $_POST['password_override'] ) ? $_POST['password_override'] : explode( ",", $forum['password_override'] ), 5 );
		
		# Sorting settings
		$form['prune']      		= $this->ipsclass->adskin->form_dropdown("prune"     , $dd_prune, ( isset($_POST['prune']) 			AND $_POST['prune'] )		? $_POST['prune']		: $forum['prune'] );
		$form['sort_key']   		= $this->ipsclass->adskin->form_dropdown("sort_key"  , $dd_order, ( isset($_POST['sort_key']) 		AND $_POST['sort_key'] )	? $_POST['sort_key']	: $forum['sort_key'] );
		$form['sort_order'] 		= $this->ipsclass->adskin->form_dropdown("sort_order", $dd_by   , ( isset($_POST['sort_order']) 	AND $_POST['sort_order'] )	? $_POST['sort_order'] 	: $forum['sort_order'] );
		$form['topicfilter'] 		= $this->ipsclass->adskin->form_dropdown("topicfilter", $dd_filter, ( isset($_POST['topicfilter']) 	AND $_POST['topicfilter'] ) ? $_POST['topicfilter'] : $forum['topicfilter'] );
		
		# Trim the form for categories...
		$form['addnew_type']			= $addnew_type;
		$this->ipsclass->input['type'] 	= $addnew_type;
		$form['addnew_type_upper']		= ucwords($addnew_type);
		
		$form['convert_button'] 		=& $convert;
		
		//-----------------------------------------
		// Show permission matrix
		//-----------------------------------------
		
		if ( $type != 'edit' OR $addnew_type == 'category' )//&& $addnew_type != 'category' )
		{
			if( $addnew_type == 'category' )
			{
				$perm_matrix = $this->build_group_cat_perms( $forum['show_perms'] );
			}
			else
			{	
				$perm_matrix = $this->build_group_perms( $forum['show_perms'], $forum['read_perms'], $forum['start_perms'], $forum['reply_perms'], $forum['upload_perms'], $forum['download_perms'] );
			}
		}
		
		//-----------------------------------------
		// Show form...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->forum_form( $form, $button, $code, $title, $button, $forum, $perm_matrix );
		
		//-----------------------------------------
		// Nav and print
		//-----------------------------------------
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Manage Forums' );
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit '.ucwords($addnew_type) );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Do save form
	/*-------------------------------------------------------------------------*/
	
	function forum_save($type='new')
	{
		//-----------------------------------------
		// Converting the type?
		//-----------------------------------------

		if( $this->ipsclass->input['convert'] )
		{
			$this->forum_form( $type, 1 );
			return;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['name'] = trim($this->ipsclass->input['name']);
		$this->ipsclass->input['f']    = intval($this->ipsclass->input['f']);
		
		$forum_cat_lang = intval($this->ipsclass->input['parent_id']) == -1 ? 'Category' : 'Forum';
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->ipsclass->input['name'] == "" )
		{
			$this->ipsclass->main_msg = "You must enter a ".strtolower($forum_cat_lang)." title";
			$this->forum_form( $type );
			return;
		}
		
		//-----------------------------------------
		// Are we trying to do something stupid
		// like running with scissors or moving
		// the parent of a forum into itself
		// spot?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['parent_id'] != $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'] )
		{
			$ids   = $this->ipsclass->forums->forums_get_children( $this->ipsclass->input['f'] );
			$ids[] = $this->ipsclass->input['f'];
			
			if ( in_array( $this->ipsclass->input['parent_id'], $ids ) )
			{
				$this->ipsclass->main_msg = "Sorry, that is not possible. You are attempting to move a parent forum or category into its own child structure. Please go back and choose a different parent forum.";
				$this->forum_form( $type );
				return;
			}
		}
		
		if( $this->ipsclass->input['parent_id'] < 1 )
		{
			$this->ipsclass->input['sub_can_post'] = 1;
		}
				
		//-----------------------------------------
		// Save array
		//-----------------------------------------
		
		$save = array (  'name'                    => $this->ipsclass->input['name'],
						 'description'             => $this->ipsclass->xss_html_clean( $this->ipsclass->my_nl2br( $this->ipsclass->txt_stripslashes( $_POST['description'] ) ) ),
						 'use_ibc'                 => intval($this->ipsclass->input['use_ibc']),
						 'use_html'                => intval($this->ipsclass->input['use_html']),
						 'status'                  => intval($this->ipsclass->input['status']),
						 'password'                => $this->ipsclass->input['password'],
						 'password_override'	   => is_array($this->ipsclass->input['password_override']) ? implode( ",", $this->ipsclass->input['password_override'] ) : '',
						 'sort_key'                => $this->ipsclass->input['sort_key'],
						 'sort_order'              => $this->ipsclass->input['sort_order'],
						 'prune'                   => intval($this->ipsclass->input['prune']),
						 'topicfilter'             => $this->ipsclass->input['topicfilter'],
						 'preview_posts'           => intval($this->ipsclass->input['preview_posts']),
						 'allow_poll'              => intval($this->ipsclass->input['allow_poll']),
						 'allow_pollbump'          => intval($this->ipsclass->input['allow_pollbump']),
						 'forum_allow_rating'      => intval($this->ipsclass->input['forum_allow_rating']),
						 'inc_postcount'           => intval($this->ipsclass->input['inc_postcount']),
						 'parent_id'               => intval($this->ipsclass->input['parent_id']),
						 'sub_can_post'            => ( intval($this->ipsclass->input['sub_can_post']) == 1 ? 0 : 1 ),
						 'quick_reply'             => intval($this->ipsclass->input['quick_reply']),
						 'redirect_on'             => intval($this->ipsclass->input['redirect_on']),
						 'redirect_hits'           => intval($this->ipsclass->input['redirect_hits']),
						 'redirect_url'            => $this->ipsclass->input['redirect_url'],
						 'redirect_loc'		       => isset($this->ipsclass->input['redirect_loc']) ? $this->ipsclass->input['redirect_loc'] : '',
						 'notify_modq_emails'      => $this->ipsclass->input['notify_modq_emails'],
						 'permission_showtopic'    => intval($this->ipsclass->input['permission_showtopic']),
						 'permission_custom_error' => $this->ipsclass->my_nl2br( $this->ipsclass->txt_stripslashes($_POST['permission_custom_error']) ) );
						 
		//-----------------------------------------
		// ADD
		//-----------------------------------------
		
		if ( $type == 'new' )
		{
			 $this->ipsclass->DB->simple_construct( array( 'select' => 'MAX(id) as top_forum', 'from' => 'forums' ) );
			 $this->ipsclass->DB->simple_exec();
			 
			 $row = $this->ipsclass->DB->fetch_row();
			 
			 if ( $row['top_forum'] < 1 )
			 {
			 	$row['top_forum'] = 0;
			 }
			 
			 $row['top_forum']++;
			 
			 $perms = array();
			 
			 if( $this->ipsclass->input['parent_id'] == -1 )
			 {
				 if( $this->ipsclass->input['show_all'] )
				 {
					 $perms['SHOW'] = '*';
				 }
				 else
				 {
					if( is_array( $this->ipsclass->input['show_permissions'] ) )
					{					 
						$this->ipsclass->DB->simple_construct( array( 'select' => 'perm_id, perm_name', 'from' => 'forum_perms', 'order' => "perm_id" ) );
						$this->ipsclass->DB->simple_exec();
					
						while ( $data = $this->ipsclass->DB->fetch_row() )
						{
							if ( in_array( $data['perm_id'], $this->ipsclass->input['show_permissions'] ) )
							{
								$perms['SHOW'] .= $data['perm_id'].",";
							}
						}
						
						$perms['SHOW']    = preg_replace( "/,$/", "", $perms['SHOW']    );
					}
				}
			}
			else
			{
			 	$perms = $this->ipsclass->admin->compile_forum_perms();
		 	}
			 
			 $perm_array = addslashes(serialize(array(
													   'start_perms'    => $perms['START'],
													   'reply_perms'    => $perms['REPLY'],
													   'read_perms'     => $perms['READ'],
													   'upload_perms'   => $perms['UPLOAD'],
													   'download_perms' => $perms['DOWNLOAD'],
													   'show_perms'     => $perms['SHOW']
									 )		  )     );
									 
			//-----------------------------------------
			// Add to save array
			//-----------------------------------------
			
			$save['id']               = $row['top_forum'];
			$save['position']         = $row['top_forum'];
			$save['topics']           = 0;
			$save['posts']            = 0;
			$save['last_post']        = 0;
			$save['last_poster_id']   = 0;
			$save['last_poster_name'] = "";
			$save['permission_array'] = $perm_array;
			
			$this->ipsclass->DB->do_insert( 'forums', $save );
			
			$this->ipsclass->main_msg = $forum_cat_lang." Created";
			
			$this->ipsclass->admin->save_log($forum_cat_lang." '{$this->ipsclass->input['name']}' created");
		}
		else
		{
			 if( $this->ipsclass->input['parent_id'] == -1 )
			 {
				 if( $this->ipsclass->input['show_all'] )
				 {
					 $perms['SHOW'] = '*';
				 }
				 else
				 {
					if( is_array( $this->ipsclass->input['show_permissions'] ) )
					{					 
						$this->ipsclass->DB->simple_construct( array( 'select' => 'perm_id, perm_name', 'from' => 'forum_perms', 'order' => "perm_id" ) );
						$this->ipsclass->DB->simple_exec();
					
						while ( $data = $this->ipsclass->DB->fetch_row() )
						{
							if ( in_array( $data['perm_id'], $this->ipsclass->input['show_permissions'] ) )
							{
								$perms['SHOW'] .= $data['perm_id'].",";
							}
						}
						
						$perms['SHOW']    = preg_replace( "/,$/", "", $perms['SHOW']    );
					}
				}
				
				$perm_array = addslashes(serialize(array(
														   'start_perms'    => '',
														   'reply_perms'    => '',
														   'read_perms'     => '',
														   'upload_perms'   => '',
														   'download_perms' => '',
														   'show_perms'     => $perms['SHOW']
										 )		  )     );
										 
				$save['permission_array'] = $perm_array;				
			}

		 				
			$this->ipsclass->DB->do_update( 'forums', $save, "id={$this->ipsclass->input['f']}"  );
			
			$this->ipsclass->main_msg = $forum_cat_lang." Edited";
			
			$this->ipsclass->admin->save_log($forum_cat_lang." '{$this->ipsclass->input['name']}' edited");
		}
		
		$this->recache_forums();
		
		$this->ipsclass->input['f']		= '';
		if( $save['parent_id'] > 0 )
		{
			$this->ipsclass->input['f'] = $save['parent_id'];
		}
		
		$this->ipsclass->forums->forums_init();
		
		$this->show_forums();
	}
	
	/*-------------------------------------------------------------------------*/
	// EDIT FORUM
	/*-------------------------------------------------------------------------*/
	
	function perm_edit_form()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['f'] = intval($this->ipsclass->input['f']);
		
		//-----------------------------------------
		// check..
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['f'] )
		{
			$this->ipsclass->admin->error("You didn't choose a forum to edit, duh!");
		}
		
		//-----------------------------------------
		// Get this forum details
		//-----------------------------------------
		
		$forum = $this->ipsclass->forums->forum_by_id[$this->ipsclass->input['f']];

		//-----------------------------------------
		// Next id...
		//-----------------------------------------
		
		$relative = $this->get_next_id( $this->ipsclass->input['f'] );
		
		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( ! $forum['id'] )
		{
			$this->ipsclass->admin->error("Could not retrieve the forum data based on ID {$this->ipsclass->input['f']}");
		}
		
		$this->ipsclass->admin->page_title = "Edit permissions for ".$forum['name'];
		
		//-----------------------------------------
		// HTML
		//-----------------------------------------

		if( $forum['parent_id'] != 'root' )
		{
			$perm_matrix = $this->build_group_perms(  $forum['show_perms'], $forum['read_perms'], $forum['start_perms'], $forum['reply_perms'], $forum['upload_perms'], $forum['download_perms'], 
												  $this->ipsclass->forums->forum_by_id[ $forum['parent_id'] ]['name'].' &gt; '.$forum['name'].' &gt; '."Permission Access Levels" );
												  
			$this->ipsclass->admin->page_detail = "<b>Forum access permissions</b><br>(Check box for access, uncheck to not allow access)<br>If you deny read access for a permission set, they will not see the forum";
		}
		else
		{
			$perm_matrix = $this->build_group_cat_perms( $forum['show_perms'], $forum['name'].' &gt; '."Permission Access Levels" );
			
			$this->ipsclass->admin->page_detail = "<b>Category access permissions</b><br>(Select permission set for access, deselect to not allow access)<br>If you do not select a permission set, they will not be able to see any forums in that category.";
		}
		
		$this->ipsclass->html .= $this->html->forum_permission_form( $forum, $relative, $perm_matrix );
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Manage Forums' );
		$this->ipsclass->admin->nav[] = array( '', 'Permissions for forum '.$forum['name'] );
		
		$this->ipsclass->admin->output();
			
			
	}
	
	/*-------------------------------------------------------------------------*/
	// Get next forum ID
	/*-------------------------------------------------------------------------*/
	
	function get_next_id($fid)
	{
		$nextid = 0;
		$ids    = array();
		$index  = 0;
		$count  = 0;
		
		foreach( $this->ipsclass->forums->forum_cache['root'] as $forum_data )
		{
			$ids[ $count ] = $forum_data['id'];
			
			if ( $forum_data['id'] == $fid )
			{
				$index = $count;
			}
			
			$count++;
			
			if ( isset($this->ipsclass->forums->forum_cache[ $forum_data['id'] ]) AND is_array( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->ipsclass->forums->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$children = $this->ipsclass->forums->forums_get_children( $forum_data['id'] );
					
					$ids[ $count ] = $forum_data['id'];
			
					if ( $forum_data['id'] == $fid )
					{
						$index = $count;
					}
					
					$count++;
					
					if ( is_array($children) and count($children) )
					{
						foreach( $children as $kid )
						{
							$ids[ $count ] = $kid;
			
							if ( $kid == $fid )
							{
								$index = $count;
							}
							
							$count++;
						}
					}
				}
			}
		}
		
		return array( 'next' => $ids[ $index + 1 ], 'previous' => $ids[ $index - 1 ] );
	}
	
	/*-------------------------------------------------------------------------*/
	// RECACHE FORUMS
	/*-------------------------------------------------------------------------*/

	function recache_forums()
	{
		$this->ipsclass->update_forum_cache();
	}

	/*-------------------------------------------------------------------------*/
	// SAVE PERM CHANGES
	/*-------------------------------------------------------------------------*/

	function perm_do_edit()
	{
		$perms = array();
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------

		if( $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'] == -1
			OR $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ]['parent_id'] == 'root' )
		{
			if( $this->ipsclass->input['show_all'] )
			{
				$perms['SHOW'] = '*';
			}
			else
			{
				if( is_array( $this->ipsclass->input['show_permissions'] ) )
				{					 
					$this->ipsclass->DB->simple_construct( array( 'select' => 'perm_id, perm_name', 'from' => 'forum_perms', 'order' => "perm_id" ) );
					$this->ipsclass->DB->simple_exec();
				
					while ( $data = $this->ipsclass->DB->fetch_row() )
					{
						if ( in_array( $data['perm_id'], $this->ipsclass->input['show_permissions'] ) )
						{
							$perms['SHOW'] .= $data['perm_id'].",";
						}
					}
					
					$perms['SHOW']    = preg_replace( "/,$/", "", $perms['SHOW']    );
				}
			}
		}		
		else
		{
			$perms = $this->ipsclass->admin->compile_forum_perms();
		}
		
		$this->ipsclass->DB->do_update( 'forums', array( 'permission_array' => addslashes(serialize(array(
																						   'start_perms'  	=> $perms['START'],
																						   'reply_perms'  	=> $perms['REPLY'],
																						   'read_perms'   	=> $perms['READ'],
																						   'upload_perms' 	=> $perms['UPLOAD'],
																						   'download_perms'	=> $perms['DOWNLOAD'],
																						   'show_perms'   	=> $perms['SHOW']
							    		)		  						 )         )      ), 'id='.$this->ipsclass->input['f']);
												  
		
		
		$this->ipsclass->admin->save_log("Forum access permission edited in '{$this->ipsclass->input['name']}'");
		
		$this->recache_forums();
		
		if ( isset($this->ipsclass->input['doprevious']) AND $this->ipsclass->input['doprevious'] and $this->ipsclass->input['previd'] > 0 )
		{
			$this->ipsclass->main_msg = 'Forum permissions edited';
			
			$this->ipsclass->input['f'] = $this->ipsclass->input['previd'];
			
			$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=pedit&f={$this->ipsclass->input['f']}" );
		}
		else if ( isset($this->ipsclass->input['donext']) AND $this->ipsclass->input['donext'] and $this->ipsclass->input['nextid'] > 0 )
		{
			$this->ipsclass->main_msg = 'Forum permissions edited';
			
			$this->ipsclass->input['f'] = $this->ipsclass->input['nextid'];
			
			$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=pedit&f={$this->ipsclass->input['f']}" );
		}
		else if ( isset($this->ipsclass->input['reload']) AND $this->ipsclass->input['reload'] )
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=pedit&f={$this->ipsclass->input['f']}" );
		}
		else
		{
			$this->ipsclass->admin->done_screen("Forum Access Permissions Edited", "Forum Control", $this->ipsclass->form_code, 'redirect' );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// RE-ORDER FORUMS
	/*-------------------------------------------------------------------------*/
	
	function reorder_form()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$temp_html   = "";
		$depth_guide = "";
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['f'] )
		{
			$this->ipsclass->admin->error("Cannot go any further, not F passed");
		}
		
		$this->ipsclass->admin->page_detail = "Simply select the position you require for each forum and submit the form to complete the re-order.";
		
		$this->forumfunc->type = 'reorder';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , 'doreorder' ),
																			 2 => array( 'act'    , 'forum'     ),
																			 3 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
			
		$cat_data = $this->ipsclass->forums->forum_by_id[ $this->ipsclass->input['f'] ];
		
		if ( is_array( $this->ipsclass->forums->forum_cache[ $this->ipsclass->input['f'] ] ) )
		{
			foreach( $this->ipsclass->forums->forum_cache[ $this->ipsclass->input['f'] ] as $forum_data )
			{
				
				$temp_html .= $this->forumfunc->render_forum($forum_data, $depth_guide);
				
				$temp_html = $this->forumfunc->forum_build_children( $forum_data['id'], $temp_html, $depth_guide . $this->ipsclass->forums->depth_guide );
			}
		}
			
		$this->ipsclass->html .= $this->forumfunc->forum_show_cat($temp_html, $cat_data);
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form_standalone("Re-order");
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Re order the root forums
	/*-------------------------------------------------------------------------*/
	
	function do_reorder()
	{
		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^f_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[ $match[1] ] = $this->ipsclass->input[$match[0]];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		//-----------------------------------------
 		// Save changes
 		//-----------------------------------------
 		
 		if ( count($ids) )
 		{ 
 			foreach( $ids as $forum_id => $new_position )
 			{
 				$this->ipsclass->DB->do_update( 'forums', array( 'position' => intval($new_position) ), 'id='.$forum_id );
 			}
 		}
 		
 		$this->recache_forums();
 		
 		$this->ipsclass->boink_it( $this->ipsclass->base_url.'&'.$this->ipsclass->form_code );
	}
	
	/*-------------------------------------------------------------------------*/
	// SHOW THE FORUMS WOOHOO, ETC
	/*-------------------------------------------------------------------------*/
	
	function show_forums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title   = "Category and Forums Overview";
		$this->ipsclass->admin->page_detail  = "You can manage your forums from here. Click on the icons for more information.";
		
		//-----------------------------------------
		// Nav
		//-----------------------------------------
		
		if ( $this->ipsclass->input['f'] )
		{
			$nav = $this->ipsclass->forums->forums_breadcrumb_nav($this->ipsclass->input['f'], '&'.$this->ipsclass->form_code.'&f=');
			
			if ( is_array($nav) and count($nav) > 1 )
			{
				array_shift($nav);
				
				$this->ipsclass->html .= "<div class='navstrip'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}'>Forums</a> &gt; ".implode( " &gt; ", $nav )."</div><br />";
			}
		}
		
		//-----------------------------------------
		// Grab the moderators
		//-----------------------------------------
		
		$this->forumfunc->moderators = array();
		
		$this->ipsclass->DB->build_query( array( 'select' 	=> 'm.*', 
												 'from' 	=> array( 'moderators' => 'm' ),
												 'add_join'	=> array(
												 					array( 'select' => 'mm.members_display_name',
												 						 	'from'	=> array( 'members' => 'mm' ),
												 						 	'where'	=> 'mm.id=m.member_id AND m.is_group=0',
												 						 	'type'	=> 'left'
												 						 )
												 					)
										) 		);
		$this->ipsclass->DB->exec_query();
		
		while ($r = $this->ipsclass->DB->fetch_row())
		{
			$this->forumfunc->moderators[] = $r;
		}
		
		//-----------------------------------------
		// Print screen
		//-----------------------------------------
		
		$this->forumfunc->type = 'manage';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , 'doreordercat'),
													             			 2 => array( 'act'    , 'forum'     ),
													             			 3 => array( 'section', $this->ipsclass->section_code ),
													    			)      );
		
		$this->ipsclass->html .= $this->html->render_forum_header();
		
		$this->forumfunc->forums_list_forums();
		
		$choose = "<select name='roots' class='realbutton'>";
		
		foreach( $this->ipsclass->forums->forum_cache['root'] as $fid => $fdata )
		{
			$choose .= "<option value='{$fid}'>{$fdata['name']}</option>\n";
		}
		
		$choose .= "</select>";
		
		//-----------------------------------------
		// Member groups
		//-----------------------------------------
		
		$mem_group = "<select name='group' class='realbutton'>";
			
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => "g_title" ) );
		$this->ipsclass->DB->simple_exec();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
		 	$mem_group .= "<option value='{$r['g_id']}'>{$r['g_title']}</option>\n";
		}
		
		$mem_group .= "</select>";
		
		//-----------------------------------------
		// Add footer
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->render_forum_footer( $choose, $mem_group );
		
		$this->ipsclass->admin->nav[] = array( '', 'Manage Forums' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Build group permissions
	/*-------------------------------------------------------------------------*/
	
	function build_group_perms( $show='*', $read='*', $write='*', $reply='*', $upload='*', $download='*', $title="Permission Access Levels" )
	{
		//-----------------------------------------
		// Load skin if required
		//-----------------------------------------
		
		if ( ! $this->html )
		{
			$this->html = $this->ipsclass->acp_load_template('cp_skin_forums');
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$global  = array();
		$perm    = array();
		$data    = array();
		$checked = "";
		$check   = " checked='checked'";
		$content = "";
		
		//-----------------------------------------
		// GLOBALS
		//-----------------------------------------
		
		# SHOW FORUM
		$checked             = ($show == '*') ? $check : "";
		$global['html_show'] = "<input type='checkbox' onclick='check_all(\"SHOW\")' name='SHOW_ALL' id='SHOW_ALL' value='1' {$checked}>\n";
		
		# READ FORUM
		$checked             = ($read == '*') ? $check : "";
		$global['html_read'] = "<input type='checkbox' onclick='check_all(\"READ\")' name='READ_ALL' id='READ_ALL' value='1' {$checked}>\n";
		
		# REPLY FORUM
		$checked             = ($reply == '*') ? $check : "";
		$global['html_reply'] = "<input type='checkbox' onclick='check_all(\"REPLY\")' name='REPLY_ALL' id='REPLY_ALL' value='1' {$checked}>\n";
		
		# START TOPICS
		$checked             = ($write == '*') ? $check : "";
		$global['html_start'] = "<input type='checkbox' onclick='check_all(\"START\")' name='START_ALL' id='START_ALL' value='1' {$checked}>\n";
		
		# UPLOAD
		$checked             = ($upload == '*') ? $check : "";
		$global['html_upload'] = "<input type='checkbox' onclick='check_all(\"UPLOAD\")' name='UPLOAD_ALL' id='UPLOAD_ALL' value='1' {$checked}>\n";
		
		# DOWNLOAD
		$checked             = ($download == '*') ? $check : "";
		$global['html_download'] = "<input type='checkbox' onclick='check_all(\"DOWNLOAD\")' name='DOWNLOAD_ALL' id='DOWNLOAD_ALL' value='1' {$checked}>\n";

		//-----------------------------------------
		// Per mask settings
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'order' => "perm_name ASC" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $data = $this->ipsclass->DB->fetch_row() )
		{
			# SHOW FORUM
			$checked           = ($show == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $show ) ) ? $check : "";
			$perm['html_show'] = "<input type='checkbox' name='SHOW_{$data['perm_id']}' id='SHOW_{$data['perm_id']}' value='1' {$checked} onclick=\"obj_checked('SHOW', '{$data['perm_id']}')\">";
		
			# READ FORUM
			$checked           = ($read == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $read ) ) ? $check : "";
			$perm['html_read'] = "<input type='checkbox' name='READ_{$data['perm_id']}' id='READ_{$data['perm_id']}' value='1' {$checked} onclick=\"obj_checked('READ', '{$data['perm_id']}')\">";
			
			# REPLY FORUM
			$checked            = ($reply == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $reply ) ) ? $check : "";
			$perm['html_reply'] = "<input type='checkbox' name='REPLY_{$data['perm_id']}' id='REPLY_{$data['perm_id']}' value='1' {$checked} onclick=\"obj_checked('REPLY', '{$data['perm_id']}')\">";
			
			# WRITE FORUM
			$checked            = ($write == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $write ) ) ? $check : "";
			$perm['html_start'] = "<input type='checkbox' name='START_{$data['perm_id']}' id='START_{$data['perm_id']}' value='1' {$checked} onclick=\"obj_checked('START', '{$data['perm_id']}')\">";
			
			# UPLOAD
			$checked             = ($upload == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $upload ) ) ? $check : "";
			$perm['html_upload'] = "<input type='checkbox' name='UPLOAD_{$data['perm_id']}' id='UPLOAD_{$data['perm_id']}' value='1' {$checked} onclick=\"obj_checked('UPLOAD', '{$data['perm_id']}')\">";
			
			# DOWNLOAD
			$checked             = ($download == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $download ) ) ? $check : "";
			$perm['html_download'] = "<input type='checkbox' name='DOWNLOAD_{$data['perm_id']}' id='DOWNLOAD_{$data['perm_id']}' value='1' {$checked} onclick=\"obj_checked('DOWNLOAD', '{$data['perm_id']}')\">";

			// Stupid work around - browsers don't like seeing the same id more than once
			$data['perm_id1'] = $data['perm_id']."_1";
			$data['perm_id2'] = $data['perm_id']."_2";
			$data['perm_id3'] = $data['perm_id']."_3";
			$data['perm_id4'] = $data['perm_id']."_4";
			$data['perm_id5'] = $data['perm_id']."_5";
			
			$content .= $this->html->render_forum_permissions_row( $perm, $data );
		}
		
		//-----------------------------------------
		// Wrapper...
		//-----------------------------------------
		
		return $this->html->render_forum_permissions( $global, $content, $title );
	}
	
	
	function build_group_cat_perms( $show='*', $title='Permission Access Levels' )
	{
		//-----------------------------------------
		// Load skin if required
		//-----------------------------------------
		
		if ( ! $this->html )
		{
			$this->html = $this->ipsclass->acp_load_template('cp_skin_forums');
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if( $show == '*' )
		{
			$select_all = "checked='checked'";
		}
		else
		{
			$select_all = "";
		}
		
		$perms = array();
		
		//-----------------------------------------
		// Per mask settings
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'order' => "perm_name ASC" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $data = $this->ipsclass->DB->fetch_row() )
		{
			$checked = ($show == '*' OR preg_match( "/(^|,)".$data['perm_id']."(,|$)/", $show ) ) ? "selected='selected'" : "";
			
			$data['perm_selected'] = $checked;
			
			$perms[] = $data;
		}
		
		//-----------------------------------------
		// Wrapper...
		//-----------------------------------------
		
		return $this->html->render_cat_permissions( $perms, $select_all, $title );
	}	
		
}


?>