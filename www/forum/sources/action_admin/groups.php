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
|   > $Date: 2007-05-11 17:54:11 -0400 (Fri, 11 May 2007) $
|   > $Revision: 994 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Forum functions
|   > Module written by Matt Mecham
|   > Date started: 17th March 2002
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


class ad_groups
{
	# Global
	var $ipsclass;
	var $html;
	
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
	var $perm_child = "group";
	
	function auto_run()
	{
		$this->ipsclass->forums->forums_init();
		
		require ROOT_PATH.'sources/lib/admin_forum_functions.php';
		
		$this->forumfunc = new admin_forum_functions();
		$this->forumfunc->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_groups');

		//-----------------------------------------
		// To do
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'doadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->save_group('add');
				break;
				
			case 'add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->group_form('add');
				break;
				
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->group_form('edit');
				break;
			
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->save_group('edit');
				break;
			
			case 'delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->delete_form();
				break;
			
			case 'dodelete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->do_delete();
				break;
				
			//-----------------------------------------	
				
			case 'fedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->forum_perms();
				break;
				
			case 'pdelete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->delete_mask();
				break;
				
			case 'dofedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->do_forum_perms();
				break;
				
			case 'permsplash':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->permsplash();
				break;
				
			case 'view_perm_users':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->view_perm_users();
				break;
					
			case 'remove_mask':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->remove_mask();
				break;
				
			case 'preview_forums':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->preview_forums();
				break;
				
			case 'dopermadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->add_new_perm();
				break;
				
			case 'donameedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':perms' );
				$this->edit_name_perm();
				break;

			case 'master_xml_export':
				$this->master_xml_export();
				break;
							
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->main_screen();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Export Master XML
	/*-------------------------------------------------------------------------*/
	
	function master_xml_export()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];

		$xml->xml_set_root( 'export', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Set group
		//-----------------------------------------
		
		$xml->xml_add_group( 'group' );
		
		//-----------------------------------------
		// Get templates...
		//-----------------------------------------
	
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'groups',
													  'order'  => 'g_id ASC',
													  'limit'  => array( 0, 6 ) ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			$r['g_icon'] = '';
			
			//-----------------------------------------
			// Sort the fields...
			//-----------------------------------------
			
			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'row', $content );
		}
		
		$xml->xml_add_entry_to_group( 'group', $entry );
		
		$xml->xml_format_document();
		
		$doc = $xml->xml_document;
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $doc, 'groups.xml', '', 0 );
	}
	
	/*-------------------------------------------------------------------------*/
	// Member group /forum mask permission form thingy doodle do yes. Viewing Perm users
	/*-------------------------------------------------------------------------*/
	
	function delete_mask()
	{
		//-----------------------------------------
		// Check for a valid ID
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the permission set ID, please try again");
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'forum_perms', 'where' => "perm_id=".intval($this->ipsclass->input['id']) ) );
		
		$old_id = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// Remove from forums...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, permission_array', 'from' => 'forums' ) );
		$get = $this->ipsclass->DB->simple_exec();
		
		while( $f = $this->ipsclass->DB->fetch_row($get) )
		{
			$d_str = "";
			$d_arr = unserialize(stripslashes( $f['permission_array'] ) );
			
			$perms = unserialize(stripslashes( $f['permission_array'] ) );
			
			foreach( array( 'read_perms', 'reply_perms', 'start_perms', 'upload_perms', 'show_perms' ) as $perm_bit )
			{
				if ($perms[ $perm_bit ] != '*')
				{
					if ( preg_match( "/(^|,)".$old_id."(,|$)/", $perms[ $perm_bit ]) )
					{
						$perms[ $perm_bit ] = preg_replace( "/(^|,)".$old_id."(,|$)/", "\\1\\2", $perms[ $perm_bit ] );
						
						$d_arr[ $perm_bit ] = $this->clean_perms( $perms[ $perm_bit ] );
					}
				}
			}
			
			//-----------------------------------------
			// Do we have anything to save?
			//-----------------------------------------
			
			if ( count($d_arr) > 0 )
			{
				//-----------------------------------------
				// Sure?..
				//-----------------------------------------
				
				$string = addslashes(serialize( $d_arr ) );
				
				if ( strlen($string) > 5)
				{
					$this->ipsclass->DB->do_update( 'forums', array( 'permission_array' => $string ), 'id='.$f['id'] );
				}
			}
		}
		
		//-----------------------------------------
		// Recache forums
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/forums.php' );
		$ad_forums = new ad_forums();
		$ad_forums->ipsclass =& $this->ipsclass;
		$ad_forums->recache_forums();
		
		$this->permsplash();
	}
	
	/*-------------------------------------------------------------------------*/
	// Add new perm mask
	/*-------------------------------------------------------------------------*/
	
	function add_new_perm()
	{
		$this->ipsclass->input['new_perm_name'] = trim($this->ipsclass->input['new_perm_name']);
		
		if ($this->ipsclass->input['new_perm_name'] == "")
		{
			$this->ipsclass->admin->error("You must enter a name");
		}
		
		$copy_id = $this->ipsclass->input['new_perm_copy'];
		
		//-----------------------------------------
		// UPDATE DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'forum_perms', array( 'perm_name' => $this->ipsclass->input['new_perm_name'] ) );
		
		$new_id = $this->ipsclass->DB->get_insert_id();
		
		if ( $copy_id != 'none' )
		{
			//-----------------------------------------
			// Add new mask to forum accesses
			//-----------------------------------------
		
			$old_id = intval($copy_id);
			
			if ( ($new_id > 0) and ($old_id > 0) )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'id, permission_array', 'from' => 'forums' ) );
				$get = $this->ipsclass->DB->simple_exec();
				
				while( $f = $this->ipsclass->DB->fetch_row($get) )
				{
					$d_str = "";
					$d_arr = unserialize(stripslashes( $f['permission_array'] ) );
					
					$perms = unserialize(stripslashes( $f['permission_array'] ) );
			
					foreach( array( 'read_perms', 'reply_perms', 'start_perms', 'upload_perms', 'show_perms', 'download_perms' ) as $perm_bit )
					{
						if ( $perms[ $perm_bit ] != '*')
						{
							if ( preg_match( "/(^|,)".$old_id."(,|$)/", $perms[ $perm_bit ]) )
							{
								$d_arr[ $perm_bit ] = $this->clean_perms( $perms[ $perm_bit ] ) . ",".$new_id;
							}
						}
					}
					
					//-----------------------------------------
					// Do we have anything to save?
					//-----------------------------------------
					
					if ( count($d_arr) > 0 )
					{
						$string = addslashes(serialize( $d_arr ) );
						
						//-----------------------------------------
						// Sure?..
						//-----------------------------------------
						
						if ( strlen($string) > 5)
						{
							$this->ipsclass->DB->do_update( 'forums', array( 'permission_array' => $string ), 'id='.$f['id'] );
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Recache forums
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/forums.php' );
		$ad_forums = new ad_forums();
		$ad_forums->ipsclass =& $this->ipsclass;
		$ad_forums->recache_forums();
		
		$this->ipsclass->main_msg = "The permission set '{$this->ipsclass->input['new_perm_name']}' has been added";
		$this->permsplash();
	}
	
	/*-------------------------------------------------------------------------*/
	// Preview masks
	/*-------------------------------------------------------------------------*/
	
	function preview_forums()
	{
		//-----------------------------------------
		// Check for a valid ID
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the permission set ID, please try again");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $perms = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the permission set ID, please try again");
		}
		
		//-----------------------------------------
		// What we doin'?
		//-----------------------------------------
		
		switch( $this->ipsclass->input['t'] )
		{
			case 'start':
				$human_type = '<b>Start Topics</b> in this forum';
				$code_word  = 'start_perms';
				break;
				
			case 'reply':
				$human_type = '<b>Reply to Topics</b> in this forum';
				$code_word  = 'reply_perms';
				break;
			
			case 'show':
				$human_type = '<b>See</b> this forum';
				$code_word  = 'show_perms';
				break;
				
			case 'upload':
				$human_type = '<b>Upload Attachments</b> to this forum';
				$code_word  = 'upload_perms';
				break;
				
			case 'download':
				$human_type = '<b>Download Attachments</b> in this forum';
				$code_word  = 'download_perms';
				break;				
				
			default:
				$human_type = '<b>Read Topics</b> in this forum';
				$code_word  = 'read_perms';
				break;
		}
		
		//-----------------------------------------
		// Get all members using that ID then!
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "$human_type" , "100%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Preview using: " . $perms['perm_name'] );
		
		$the_html   = "";
		
		$perm_id    = intval($this->ipsclass->input['id']);
		
		$theforums  = $this->forumfunc->ad_forums_forum_list(1);
		
		foreach( $theforums as $v )
		{
			$id   = $v[0];
			$name = $v[1];
			
			$this->ipsclass->forums->forum_by_id[$id][ $code_word ] = isset($this->ipsclass->forums->forum_by_id[$id][ $code_word ]) ? $this->ipsclass->forums->forum_by_id[$id][ $code_word ] : '';
			
			if ($this->ipsclass->forums->forum_by_id[$id][ $code_word ] == '*')
			{
				$the_html[] = "<span style='color:green;font-weight:bold''>".$name."</span>";
			}
			else if (preg_match( "/(^|,)".$perm_id."(,|$)/", $this->ipsclass->forums->forum_by_id[$id][ $code_word ]) )
			{
				$the_html[] = "<span style='color:green;font-weight:bold''>".$name."</span>";
			}
			else
			{
				if( $code_word != 'show_perms' AND $this->ipsclass->forums->forum_by_id[$id]['parent_id'] == 'root' )
				{
					//-----------------------------------------
					// CATEGORY
					//-----------------------------------------
										
					$the_html[] = "<span style='color:grey;'>".$name."</span>";
				}
				else
				{
					//-----------------------------------------
					// CAN'T ACCESS
					//-----------------------------------------
					
					$the_html[] = "<span style='color:red;font-weight:bold''>".$name."</span>";
				}
			}
		}
			
		$html = implode( "<br />", $the_html );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $html ) );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'preview_forums' ),
																			 2 => array( 'act'   , 'group'   ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']      ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Legend & Info" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													"Can $human_type",
													"<input type='text' readonly='readonly' style='border:1px solid black;background-color:green;size=30px' name='blah'>"
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													"CANNOT $human_type",
													"<input type='text' readonly='readonly' style='border:1px solid gray;background-color:red;size=30px' name='blah'>"
										 )      );

		if( $code_word != "show_perms" )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
														"Category (doesn't use this permission)",
														"<input type='text' readonly='readonly' style='border:1px solid gray;background-color:grey;size=30px' name='blah'>"
											 )      );
		}
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													"Test with...",
													$this->ipsclass->adskin->form_dropdown( 't',
																		array( 0 => array( 'start', 'Start Topics'    ),
																			   1 => array( 'reply', 'Reply To Topics' ),
																			   2 => array( 'read' , 'Read Topics'      ),
																			   3 => array( 'show' , 'See Forum'      ),
																			   4 => array( 'upload', 'Upload to Forum'   ),
																			   5 => array( 'download', 'Download From Forum'   ),
																			  ), $this->ipsclass->input['t'] )
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Update" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
							   
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove mask
	/*-------------------------------------------------------------------------*/
	
	function remove_mask()
	{
		//-----------------------------------------
		// Check for a valid ID
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the member ID, please try again");
		}
		
		//-----------------------------------------
		// Get, check and reset
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, org_perm_id', 'from' => 'members', 'where' => "id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $mem = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the member ID, please try again");
		}
		
		if ( $this->ipsclass->input['pid'] == 'all' )
		{
			$this->ipsclass->DB->do_update( 'members', array( 'org_perm_id' => 0 ), 'id='.intval($this->ipsclass->input['id']));
		}
		else
		{
			$this->ipsclass->input['pid'] = intval($this->ipsclass->input['pid']);
			
			$pid_array = explode( ",", $this->ipsclass->clean_perm_string($mem['org_perm_id']) );
			
			if ( count($pid_array) < 2 )
			{
				$this->ipsclass->DB->do_update( 'members', array( 'org_perm_id' => 0 ), 'id='.intval($this->ipsclass->input['id']));
			}
			else
			{
				$new_arr = array();
				
				foreach( $pid_array as $sid )
				{
					if ( $sid != $this->ipsclass->input['pid'] )
					{
						$new_arr[] = $sid;
					}
				}
				
				$this->ipsclass->DB->do_update( 'members', array( 'org_perm_id' => implode(",",$new_arr) ), 'id='.intval($this->ipsclass->input['id']));
			}	
		}
			
		//-----------------------------------------
		// Get all members using that ID then!
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Result" );
		
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "Removed the custom set of permissions from <b>{$mem['name']}</b>." )      );
	
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
	}
	
	/*-------------------------------------------------------------------------*/
	// View perm users
	/*-------------------------------------------------------------------------*/
	
	function view_perm_users()
	{
		//-----------------------------------------
		// Check for a valid ID
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the permission set ID, please try again");
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $perms = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the permission set ID, please try again");
		}
		
		//-----------------------------------------
		// Get all members using that ID then!
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "User Details" , "50%" );
		$this->ipsclass->adskin->td_header[] = array( "Action"       , "50%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= "<script language='javascript' type='text/javascript'>
						 <!--
						  function pop_close_and_stop( id )
						  {
						  	opener.location = \"{$this->ipsclass->base_url}&section=content&act=mem&code=doform&mid=\" + id;
						  	self.close();
						  }
						  //-->
						  </script>";
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Members using: " . $perms['perm_name'] );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, email, posts, org_perm_id',
													  'from'   => 'members',
													  'where'  => "(org_perm_id IS NOT NULL AND org_perm_id != '')",
													  'order'  => 'name' ) );
		$outer = $this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row($outer) )
		{
			$exp_pid = explode( ",", $r['org_perm_id'] );
			
			foreach( explode( ",", $r['org_perm_id'] ) as $pid )
			{
				if ( $pid == $this->ipsclass->input['id'] )
				{
					if ( count($exp_pid) > 1 )
					{
						$extra = "<li>Also using: <em style='color:red'>";
						
						$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id IN (".$this->ipsclass->clean_perm_string($r['org_perm_id']).") AND perm_id <> {$this->ipsclass->input['id']}" ) );
						$this->ipsclass->DB->simple_exec();
						
						while ( $mr = $this->ipsclass->DB->fetch_row() )
						{
							$extra .= $mr['perm_name'].",";
						}
						
						$extra = preg_replace( "/,$/", "", $extra );
						
						$extra .= "</em>";
					}
					else
					{
						$extra = "";
					}
					
					$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<div style='font-weight:bold;font-size:11px;padding-bottom:6px;margin-bottom:3px;border-bottom:1px solid #000'>{$r['name']}</div>
																						  <li>Posts: {$r['posts']}
																						  <li>Email: {$r['email']}
																						  $extra" ,
																						 "&#149;&nbsp;<a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=remove_mask&amp;id={$r['id']}&amp;pid=$pid' title='Remove this permission set from the user (will not remove all if they use more than one)'>Remove This Permission Set</a>
																						  <br />&#149;&nbsp;<a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=remove_mask&amp;id={$r['id']}&amp;pid=all' title='Remove all custom permission sets'>Remove All Custom Permission Sets</a>
																						  <br /><br />&#149;&nbsp;<a href='javascript:pop_close_and_stop(\"{$r['id']}\");'>Edit Member</a>",
																				)      );
				}
			}
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Member Group Perms: Called in "Forums" menu block
	/*-------------------------------------------------------------------------*/
	
	function permsplash()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$perms   = array();
		$mems    = array();
		$groups  = array();
		$dlist   = "";
		$content = "";
		
		//-----------------------------------------
		// Page title & desc
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Forum Permission Management [ HOME ]";
		$this->ipsclass->admin->page_detail = "You can manage your forum permissions from this section.";
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=permsplash', 'Manage Permissions' );
								
		//-----------------------------------------
		// Get the names for the perm masks w/id
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name ASC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$perms[ $r['perm_id'] ] = $r['perm_name'];
		}
		
		//-----------------------------------------
		// Get the number of members using this mask
		// as an over ride
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'groups_permsplash', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( strstr( $r['org_perm_id'] , "," ) )
			{
				foreach( explode( ",", $r['org_perm_id'] ) as $pid )
				{
					$mems[ $pid ]  = !isset($mems[ $pid ]) ? 0 : $mems[ $pid ];
					$mems[ $pid ] += $r['count'];
				}
			}
			else
			{
				$mems[ $r['org_perm_id'] ] += $r['count'];
			}
		}
	
		//-----------------------------------------
		// Get the member group names and the mask
		// they use
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title, g_perm_id', 'from' => 'groups' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( strstr( $r['g_perm_id'] , "," ) )
			{
				foreach( explode( ",", $r['g_perm_id'] ) as $pid )
				{
					$groups[ $pid ][] = $r['g_title'];
				}
			}
			else
			{
				$groups[ $r['g_perm_id'] ][] = $r['g_title'];
			}
		}
		
		//-----------------------------------------
		// Print the splash screen
		//-----------------------------------------
		
		foreach( $perms as $id => $name )
		{
			$groups_used = "";
			$mems_used   = 0;
			$is_active   = 0;
			$dlist      .= "<option value='$id'>$name</option>\n";
			
			if ( isset($groups[ $id ]) AND is_array( $groups[ $id ] ) )
			{
				foreach( $groups[ $id ] as $g_title )
				{
					$groups_used .= '&middot; ' . $g_title . "<br />";
				}
				
				$is_active = 1;
			}
			else
			{
				$groups_used = "<center><i>None</i></center>";
			}			
			
			if ( isset($mems[ $id ]) AND $mems[ $id ] > 0 )
			{
				$is_active = 1;
			}
			
			$r['id']       = $id;
			$r['name']     = $name;
			$r['isactive'] = $is_active;
			$r['groups']   = $groups_used;
			$r['mems']     = isset($mems[ $id ]) ? intval( $mems[ $id ] ) : 0;
			
			$content .= $this->html->groups_perm_splash_row( $r );
		}
		
		$this->ipsclass->html .= $this->html->groups_perm_splash_wrapper( $content, $dlist );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Forum permissions
	/*-------------------------------------------------------------------------*/
	
	function forum_perms()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the group ID, please try again");
		}
		
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title = "Forum Permission Management [ EDIT ]";
		$this->ipsclass->admin->page_detail = "You can edit a set of permissions from this section.";
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=permsplash', 'Manage Permissions' );
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit Permissions' )		;
		
		$this->ipsclass->admin->page_detail .= "<br />Simply check the boxes to allow permission for that action, or uncheck the box to deny permission for that action.
							   <br /><b>Global</b> indicates that all present and future permission sets have access to that action and as such, cannot be changed.
							   <br />Categories only use \"Show\" permissions, and as such the rest of the permission settings for categories are marked <b>not used</b>.";
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		$group = $this->ipsclass->DB->fetch_row();
		
		$gid   = $group['perm_id'];
		$gname = $group['perm_name'];
		
		//-----------------------------------------
		//| EDIT NAME
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'donameedit' ),
																			 2 => array( 'act'   , 'group'   ),
																			 3 => array( 'id'    , $gid      ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	),  "nameForm"     );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"   , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rename Permission Set: ".$group['perm_name'] );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Permission Set Name</b>" ,
												                 $this->ipsclass->adskin->form_input("perm_name", $gname )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Edit Name");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		//| MAIN FORM
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'dofedit' ),
																			 2 => array( 'act'   , 'group'   ),
																			 3 => array( 'id'    , $gid      ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)    	);
		
		$this->ipsclass->adskin->td_header[] = array( "Forum Name"   , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Show<br /><input id='show' type='checkbox' onclick='checkcol(\"show\", this.checked );' />"         , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Read<br /><input id='read' type='checkbox' onclick='checkcol(\"read\", this.checked );' />"         , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Reply<br /><input id='reply' type='checkbox' onclick='checkcol(\"reply\", this.checked );' />"        , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Start<br /><input id='start' type='checkbox' onclick='checkcol(\"start\", this.checked );' />"        , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Upload<br /><input id='upload' type='checkbox' onclick='checkcol(\"upload\", this.checked );' />"       , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Download<br /><input id='download' type='checkbox' onclick='checkcol(\"download\", this.checked );' />"       , "10%" );
		
		$forum_data = $this->forumfunc->ad_forums_forum_data();
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Forum Access Permissions for ".$group['perm_name'] );
		
		foreach( $forum_data as $r )
		{
			$show   = "";
			$read   = "";
			$start  = "";
			$reply  = "";
			$upload = "";
			
			$global = '<center><i>Global</i></center>';
			
			if ($r['show_perms'] == '*')
			{
				$show = $global;
			}
			else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['show_perms'] ) )
			{
				$show = "<center><input type='checkbox' name='show_".$r['id']."' id='show_".$r['id']."' onclick=\"obj_checked('show', {$r['id']} );\" value='1' checked></center>";
			}
			else
			{
				$show = "<center><input type='checkbox' name='show_".$r['id']."' id='show_".$r['id']."' onclick=\"obj_checked('show', {$r['id']} );\" value='1'></center>";
			}
			
			//-----------------------------------------
			
			$global = '<center><i>Global</i></center>';
			
			if ($r['read_perms'] == '*')
			{
				$read = $global;
			}
			else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['read_perms'] ) )
			{
				$read = "<center><input type='checkbox' name='read_".$r['id']."' id='read_".$r['id']."' onclick=\"obj_checked('read', {$r['id']} );\" value='1' checked></center>";
			}
			else
			{
				$read = "<center><input type='checkbox' name='read_".$r['id']."' id='read_".$r['id']."' onclick=\"obj_checked('read', {$r['id']} );\" value='1'></center>";
			}
			
			//-----------------------------------------
			
			$global = '<center><i>Global</i></center>';
			
			if ($r['start_perms'] == '*')
			{
				$start = $global;
			}
			else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['start_perms'] ) )
			{
				$start = "<center><input type='checkbox' name='start_".$r['id']."' id='start_".$r['id']."' onclick=\"obj_checked('start', {$r['id']} );\" value='1' checked></center>";
			}
			else
			{
				$start = "<center><input type='checkbox' name='start_".$r['id']."' id='start_".$r['id']."' onclick=\"obj_checked('start', {$r['id']} );\" value='1'></center>";
			}
			
			//-----------------------------------------
			
			$global = '<center><i>Global</i></center>';
			
			if ($r['reply_perms'] == '*')
			{
				$reply = $global;
			}
			else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['reply_perms'] ) )
			{
				$reply = "<center><input type='checkbox' name='reply_".$r['id']."' id='reply_".$r['id']."' onclick=\"obj_checked('reply', {$r['id']} );\" value='1' checked></center>";
			}
			else
			{
				$reply = "<center><input type='checkbox' name='reply_".$r['id']."' id='reply_".$r['id']."' onclick=\"obj_checked('reply', {$r['id']} );\" value='1'></center>";
			}
			
			//-----------------------------------------
			
			$global = '<center><i>Global</i></center>';
			
			if ($r['upload_perms'] == '*')
			{
				$upload = $global;
			}
			else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['upload_perms'] ) )
			{
				$upload = "<center><input type='checkbox' name='upload_".$r['id']."' id='upload_".$r['id']."' onclick=\"obj_checked('upload', {$r['id']} );\" value='1' checked></center>";
			}
			else
			{
				$upload = "<center><input type='checkbox' name='upload_".$r['id']."' id='upload_".$r['id']."' onclick=\"obj_checked('upload', {$r['id']} );\" value='1'></center>";
			}
			
			//-----------------------------------------
			
			$global = '<center><i>Global</i></center>';
			
			if ($r['download_perms'] == '*')
			{
				$download = $global;
			}
			else if ( preg_match( "/(^|,)".$gid."(,|$)/", $r['download_perms'] ) )
			{
				$download = "<center><input type='checkbox' name='download_".$r['id']."' id='download_".$r['id']."' onclick=\"obj_checked('download', {$r['id']} );\" value='1' checked></center>";
			}
			else
			{
				$download = "<center><input type='checkbox' name='download_".$r['id']."' id='download_".$r['id']."' onclick=\"obj_checked('download', {$r['id']} );\" value='1'></center>";
			}			
			
			//-----------------------------------------
			
			if ( $r['root_forum'] )
			{
				$css = 'tablerow4';
				$download = $upload = $reply = $start = $read = "<center><i>Not Used</i></center>";
			}
			else
			{
				$css = '';
			}
			 
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
																	 "<div style='float:right;width:auto;'>
																	 	<input type='button' id='button' value='+' onclick='checkrow({$r['id']},true)' />&nbsp;<input type='button' id='button' value='-' onclick='checkrow({$r['id']},false)' />
																	  </div>
																	  <b>".$r['depthed_name']."</b>",
																	 "<div style='background-color:#ecd5d8; padding:4px;'>".$show."</div>",
																	 "<div style='background-color:#dbe2de; padding:4px;'>".$read."</div>",
																	 "<div style='background-color:#dbe6ea; padding:4px;'>".$reply."</div>",
																	 "<div style='background-color:#d2d5f2; padding:4px;'>".$start."</div>",
																	 "<div style='background-color:#ece6d8; padding:4px;'>".$upload."</div>",
																	 "<div style='background-color:#dfdee9; padding:4px;'>".$download."</div>",
										 					) ,$css  );
		
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Update Forum Permissions");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= $this->html->permissions_js();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit perm name
	/*-------------------------------------------------------------------------*/
	
	function edit_name_perm()
	{
		//-----------------------------------------
		// Check for legal ID
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve that group ID");
		}
		
		if ( $this->ipsclass->input['perm_name'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a name");
		}
		
		$gid = $this->ipsclass->input['id'];
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $gr = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Not a valid group ID");
		}
		
		$this->ipsclass->DB->do_update( 'forum_perms', array( 'perm_name' => $this->ipsclass->input['perm_name'] ), 'perm_id='.intval($this->ipsclass->input['id']) );
		
		$this->ipsclass->admin->save_log("Forum Access Permissions Name Edited for Set: '{$gr['perm_name']}'");
		
		$this->ipsclass->main_msg = "Permission Set Name Updated";
		
		$this->forum_perms( );
	}
	
	/*-------------------------------------------------------------------------*/
	// Save forum perms
	/*-------------------------------------------------------------------------*/
	
	function do_forum_perms()
	{
		//-----------------------------------------
		// Check for legal ID
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve that group ID");
		}
		
		$gid = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms', 'where' => "perm_id=".$gid ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $gr = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Not a valid group ID");
		}
		
		//-----------------------------------------
		// Pull the forum data..
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forums', 'order' => "position ASC" ) );
		$forum_q = $this->ipsclass->DB->simple_exec();
		
		while ( $row = $this->ipsclass->DB->fetch_row( $forum_q ) )
		{
			$perms = unserialize(stripslashes( $row['permission_array'] ) );
			
			$read   = "";
			$reply  = "";
			$start  = "";
			$upload = "";
			$download = "";
			$show   = "";
			
			//-----------------------------------------
			// Is this global?
			//-----------------------------------------
			
			if ($perms['read_perms'] == '*')
			{
				$read = '*';
				
			}
			else
			{
				//-----------------------------------------
				// Split the set IDs
				//-----------------------------------------
				
				$read_ids = explode( ",", $perms['read_perms'] );
				
				if ( is_array($read_ids) )
				{
				   foreach ($read_ids as $i)
				   {
					   //-----------------------------------------
					   // If it's the current ID, skip
					   //-----------------------------------------
					   
					   if ($gid == $i)
					   {
						   continue;
					   }
					   else
					   {
						   $read .= $i.",";
					   }
				   }
				}
				//-----------------------------------------
				// Was the box checked?
				//-----------------------------------------
				
				if ($this->ipsclass->input[ 'read_'.$row['id'] ] == 1)
				{
					// Add our group ID...
					
					$read .= $gid.",";
				}
				
				// Tidy..
				
				$read = preg_replace( "/,$/", "", $read );
				$read = preg_replace( "/^,/", "", $read );
				
			}
			
			//-----------------------------------------
			// Reply topics..
			//-----------------------------------------
				
			if ($perms['reply_perms'] == '*')
			{
				$reply = '*';
			}
			else
			{
				$reply_ids = explode( ",", $perms['reply_perms'] );
				
				if ( is_array($reply_ids) )
				{
					foreach ($reply_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$reply .= $i.",";
						}
					}
				
				}
				
				if ($this->ipsclass->input[ 'reply_'.$row['id'] ] == 1)
				{
					$reply .= $gid.",";
				}
				
				$reply = preg_replace( "/,$/", "", $reply );
				$reply = preg_replace( "/^,/", "", $reply );
			}
			
			//-----------------------------------------
			// Start topics..
			//-----------------------------------------
				
			if ($perms['start_perms'] == '*')
			{
				$start = '*';
			}
			else
			{
				$start_ids = explode( ",", $perms['start_perms'] );
				
				if ( is_array($start_ids) )
				{
				
					foreach ($start_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$start .= $i.",";
						}
					}
				
				}
				
				if ($this->ipsclass->input[ 'start_'.$row['id'] ] == 1)
				{
					$start .= $gid.",";
				}
				
				$start = preg_replace( "/,$/", "", $start );
				$start = preg_replace( "/^,/", "", $start );
			}
			
			//-----------------------------------------
			// Upload topics..
			//-----------------------------------------
				
			if ($perms['upload_perms'] == '*')
			{
				$upload = '*';
			}
			else
			{
				$upload_ids = explode( ",", $perms['upload_perms'] );
				
				if ( is_array($upload_ids) )
				{
				
					foreach ($upload_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$upload .= $i.",";
						}
					}
				
				}
				
				if ($this->ipsclass->input[ 'upload_'.$row['id'] ] == 1)
				{
					$upload .= $gid.",";
				}
				
				$upload = preg_replace( "/,$/", "", $upload );
				$upload = preg_replace( "/^,/", "", $upload );
			}
			
			//-----------------------------------------
			// Download attach..
			//-----------------------------------------
				
			if ($perms['download_perms'] == '*')
			{
				$download = '*';
			}
			else
			{
				$download_ids = explode( ",", $perms['download_perms'] );
				
				if ( is_array($download_ids) )
				{
					foreach ($download_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$download .= $i.",";
						}
					}
				
				}
				
				if ($this->ipsclass->input[ 'download_'.$row['id'] ] == 1)
				{
					$download .= $gid.",";
				}
				
				$download = preg_replace( "/,$/", "", $download );
				$download = preg_replace( "/^,/", "", $download );
			}			
			
			//-----------------------------------------
			// Show topics..
			//-----------------------------------------
				
			if ($perms['show_perms'] == '*')
			{
				$show = '*';
			}
			else
			{
				$show_ids = explode( ",", $perms['show_perms'] );
				
				if ( is_array($show_ids) )
				{
					foreach ($show_ids as $i)
					{
						if ($gid == $i)
						{
							continue;
						}
						else
						{
							$show .= $i.",";
						}
					}
				
				}
				
				if ($this->ipsclass->input[ 'show_'.$row['id'] ] == 1)
				{
					$show .= $gid.",";
				}
				
				$show = preg_replace( "/,$/", "", $show );
				$show = preg_replace( "/^,/", "", $show );
			}
			
			//-----------------------------------------
			// Update the DB...
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'forums', array( 'permission_array' => addslashes(serialize(array(
																						   'start_perms'  => $start,
																						   'reply_perms'  => $reply,
																						   'read_perms'   => $read,
																						   'upload_perms' => $upload,
																						   'show_perms'   => $show,
																						   'download_perms' => $download,
							    		)		  						 )         )      ), 'id='.$row['id']);
			
		}
		
		//-----------------------------------------
		// Recache forums
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/forums.php' );
		$adforums = new ad_forums();
		$adforums->ipsclass =& $this->ipsclass;
		
		$adforums->recache_forums();
		
		$this->ipsclass->admin->save_log("Forum Access Permissions Edited for Set: '{$gr['perm_name']}'");
		
		$this->ipsclass->main_msg = "Forum Access Permissions Updated";
		$this->permsplash( );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Delete a group
	/*-------------------------------------------------------------------------*/
	
	function delete_form()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the group ID, please try again");
		}
		
		if ($this->ipsclass->input['id'] < 5)
		{
			$this->ipsclass->admin->error("You can not move the preset groups. You can rename them and edit the functionality");
		}
		
		$this->ipsclass->admin->page_title = "Deleting a User Group";
		
		$this->ipsclass->admin->page_detail = "Please check to ensure that you are attempting to remove the correct group.";
		
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(id) as users', 'from' => 'members', 'where' => "mgroup=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		$black_adder = $this->ipsclass->DB->fetch_row();
		
		if ($black_adder['users'] < 1)
		{
			$black_adder['users'] = 0;
		}

		$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(id) as users', 'from' => 'members', 'where' => "mgroup_others LIKE '%".intval($this->ipsclass->input['id'])."%'" ) );
		$this->ipsclass->DB->simple_exec();
		
		$extra_group = $this->ipsclass->DB->fetch_row();
		
		if ($extra_group['users'] < 1)
		{
			$extra_group['users'] = 0;
		}

		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_title', 'from' => 'groups', 'where' => "g_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		$group = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'where' => "g_id <> ".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		$mem_groups = array();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Leave out root admin group
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['admin_group'] == $r['g_id'] )
			{
				if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
				{
					continue;
				}
			}
			
			$mem_groups[] = array( $r['g_id'], $r['g_title'] );
		}
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'dodelete'  ),
																			 2 => array( 'act'   , 'group'     ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']   ),
																			 4 => array( 'name'  , $group['g_title'] ),
																			 5 => array( 'section', $this->ipsclass->section_code ),
																	)      );
									     
		
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Removal Confirmation: ".$group['g_title'] );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of users in this group</b>" ,
												  "<b>".$black_adder['users']."</b>",
									     )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of users with this group as their <u>secondary</u> group</b><br /><i>This secondary group will be removed for these users.</i>" ,
												  "<b>".$extra_group['users']."</b>",
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Move users in this group to...</b>" ,
												  $this->ipsclass->adskin->form_dropdown("to_id", $mem_groups )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Delete this group");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// DO DELETE
	/*-------------------------------------------------------------------------*/
	
	function do_delete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['id']    = intval($this->ipsclass->input['id']);
		$this->ipsclass->input['to_id'] = intval($this->ipsclass->input['to_id']);
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------

		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("Could not resolve the group ID, please try again");
		}
		
		if ( ! $this->ipsclass->input['to_id'] )
		{
			$this->ipsclass->admin->error("No move to group ID was specified. /me cries.");
		}
		
		//-----------------------------------------
		// Ensure we didn't choose the root admin
		// group if we're not a root admin
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['admin_group'] == $this->ipsclass->input['id'] )
		{
			if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->admin->error("Sorry, you do not have permission to move into that group");
			}
		}
		
		//-----------------------------------------
		// Check to make sure that the relevant groups exist.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id', 'from' => 'groups', 'where' => "g_id IN(".$this->ipsclass->input['id'].",".$this->ipsclass->input['to_id'].")" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() != 2 )
		{
			$this->ipsclass->admin->error("Could not resolve the ID's passed to group deletion");
		}
		
		$this->ipsclass->DB->do_update( 'members', array( 'mgroup' => $this->ipsclass->input['to_id'] ), 'mgroup='.$this->ipsclass->input['id'] );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'groups', 'where' => "g_id=".$this->ipsclass->input['id'] ) );
		
		//-----------------------------------------
		// Look for promotions in case we have members to be promoted to this group...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id', 'from' => 'groups', 'where' => "g_promotion LIKE '{$this->ipsclass->input['id']}&%'" ) );
		$prq = $this->ipsclass->DB->simple_exec();
		
		while ( $row = $this->ipsclass->DB->fetch_row($prq) )
		{
			$this->ipsclass->DB->do_update( 'groups', array( 'g_promotion' => '-1&-1' ), 'g_id='.$row['g_id'] );
		}
		
		//-----------------------------------------
		// Remove from moderators table
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'moderators', 'where' => "is_group=1 AND group_id=".$this->ipsclass->input['id'] ) );

		//-----------------------------------------
		// Remove as a secondary group
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'mgroup,mgroup_others,id', 'from' => 'members', 'where' => "mgroup_others LIKE '%".$this->ipsclass->input['id']."%'" ) );
		$exg = $this->ipsclass->DB->simple_exec();
		
		while( $others = $this->ipsclass->DB->fetch_row($exg) )
		{
			$extra = array();
			$extra = explode( ",", $others['mgroup_others'] );
			$to_insert = array();
			if( count( $extra ) )
			{
				foreach( $extra as $mgroup_other )
				{
					if( $mgroup_other != $this->ipsclass->input['id'] )
					{
						if( $mgroup_other != "" )
						{
							$to_insert[] = $mgroup_other;
						}
					}
				}

				if( count( $to_insert ) )
				{
					$new_others = ','. implode( ',', $to_insert ) .',';
				}
				else
				{
					$new_others = "";
				}

				$this->ipsclass->DB->do_update( 'members', array( 'mgroup_others' => $new_others ), 'id='.$others['id'] );
			}
		}

		$this->rebuild_group_cache();
		
		// Make sure deleted group is not still listed as a moderator
		$this->ipsclass->cache['moderators'] = array();
		
		require_once( ROOT_PATH.'sources/action_admin/moderator.php' );
		$this->mod           =  new ad_moderator();
		$this->mod->ipsclass =& $this->ipsclass;
		
		$this->mod->rebuild_moderator_cache();
	
		$this->ipsclass->admin->save_log("Member Group '{$this->ipsclass->input['name']}' removed");
		
		$this->ipsclass->main_msg = "Group Removed";
		$this->main_screen();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Save changes to DB
	/*-------------------------------------------------------------------------*/
	
	function save_group($type='edit')
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------

		$this->ipsclass->admin->security_auth_check();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ($this->ipsclass->input['g_title'] == "")
		{
			$this->ipsclass->admin->error("You must enter a group title.");
		}
		
		if ($type == 'edit')
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->admin->error("Could not resolve the group id");
			}
			
			if ($this->ipsclass->input['id'] == $this->ipsclass->vars['admin_group'] and $this->ipsclass->input['g_access_cp'] != 1)
			{
				$this->ipsclass->admin->error("You can not remove the ability to access the admin control panel for this group");
			}
		}
		
		//-----------------------------------------
		// Sort out the perm mask id things
		//-----------------------------------------
		
		if ( is_array( $_POST['permid'] ) )
		{
			$perm_id = implode( ",", $_POST['permid'] );
		}
		else
		{
			$this->ipsclass->admin->error("No permission sets chosen");
		}
		
		// Build up the hashy washy for the database ..er.. wase.
		
		$prefix = str_replace( "&#39;", "'" , $this->ipsclass->txt_safeslashes($_POST['prefix']) );
		$prefix = str_replace( "&lt;" , "<" , $prefix          );
		$suffix = str_replace( "&#39;", "'" , $this->ipsclass->txt_safeslashes($_POST['suffix']) );
		$suffix = str_replace( "&lt;" , "<" , $suffix          );
		
		$promotion_a = '-1'; //id
		$promotion_b = '-1'; // posts
		
		if (isset($this->ipsclass->input['g_promotion_id']) AND $this->ipsclass->input['g_promotion_id'] > 0)
		{
			$promotion_a = $this->ipsclass->input['g_promotion_id'];
			$promotion_b = $this->ipsclass->input['g_promotion_posts'];
		}
		
		if ( $this->ipsclass->input['g_attach_per_post'] and $this->ipsclass->input['g_attach_max'] > 0 )
		{
			if ( $this->ipsclass->input['g_attach_per_post'] > $this->ipsclass->input['g_attach_max'] )
			{
				$this->ipsclass->main_msg = "You cannot specify a per post limit greater than the globally allowed limit.";
				$this->group_form('edit');
			}
		}
		
		$this->ipsclass->input['p_max']    = str_replace( ":", "", $this->ipsclass->input['p_max'] );
		$this->ipsclass->input['p_width']  = str_replace( ":", "", $this->ipsclass->input['p_width'] );
		$this->ipsclass->input['p_height'] = str_replace( ":", "", $this->ipsclass->input['p_height'] );
		
		$db_string = array(
							 'g_view_board'         => $this->ipsclass->input['g_view_board'],
							 'g_mem_info'           => $this->ipsclass->input['g_mem_info'],
							 'g_other_topics'       => $this->ipsclass->input['g_other_topics'],
							 'g_use_search'         => $this->ipsclass->input['g_use_search'],
							 'g_email_friend'       => $this->ipsclass->input['g_email_friend'],
							 'g_invite_friend'      => isset($this->ipsclass->input['g_invite_friend']) ? intval( $this->ipsclass->input['g_invite_friend'] ) : 0,
							 'g_edit_profile'       => $this->ipsclass->input['g_edit_profile'],
							 'g_post_new_topics'    => $this->ipsclass->input['g_post_new_topics'],
							 'g_reply_own_topics'   => $this->ipsclass->input['g_reply_own_topics'],
							 'g_reply_other_topics' => $this->ipsclass->input['g_reply_other_topics'],
							 'g_edit_posts'         => $this->ipsclass->input['g_edit_posts'],
							 'g_edit_cutoff'        => $this->ipsclass->input['g_edit_cutoff'],
							 'g_delete_own_posts'   => $this->ipsclass->input['g_delete_own_posts'],
							 'g_open_close_posts'   => $this->ipsclass->input['g_open_close_posts'],
							 'g_delete_own_topics'  => $this->ipsclass->input['g_delete_own_topics'],
							 'g_post_polls'         => $this->ipsclass->input['g_post_polls'],
							 'g_vote_polls'         => $this->ipsclass->input['g_vote_polls'],
							 'g_use_pm'             => $this->ipsclass->input['g_use_pm'],
							 'g_is_supmod'          => $this->ipsclass->input['g_is_supmod'],
							 'g_access_cp'          => $this->ipsclass->input['g_access_cp'],
							 'g_title'              => trim($this->ipsclass->input['g_title']),
							 'g_can_remove'         => isset($this->ipsclass->input['g_can_remove']) ? intval( $this->ipsclass->input['g_can_remove'] ) : 0,
							 'g_append_edit'        => $this->ipsclass->input['g_append_edit'],
							 'g_access_offline'     => $this->ipsclass->input['g_access_offline'],
							 'g_avoid_q'            => $this->ipsclass->input['g_avoid_q'],
							 'g_avoid_flood'        => $this->ipsclass->input['g_avoid_flood'],
							 'g_icon'               => trim($this->ipsclass->txt_safeslashes($_POST['g_icon'])),
							 'g_attach_max'         => $this->ipsclass->input['g_attach_max'],
							 'g_avatar_upload'      => $this->ipsclass->input['g_avatar_upload'],
							 'g_max_messages'       => $this->ipsclass->input['g_max_messages'],
							 'g_max_mass_pm'        => $this->ipsclass->input['g_max_mass_pm'],
							 'g_search_flood'       => $this->ipsclass->input['g_search_flood'],
							 'prefix'               => $prefix,
							 'suffix'               => $suffix,
							 'g_promotion'          => $promotion_a.'&'.$promotion_b,
							 'g_hide_from_list'     => $this->ipsclass->input['g_hide_from_list'],
							 'g_post_closed'        => $this->ipsclass->input['g_post_closed'],
							 'g_perm_id'			=> $perm_id,
							 'g_photo_max_vars'	    => $this->ipsclass->input['p_max'].':'.$this->ipsclass->input['p_width'].':'.$this->ipsclass->input['p_height'],
							 'g_dohtml'			    => $this->ipsclass->input['g_dohtml'],
							 'g_edit_topic'			=> $this->ipsclass->input['g_edit_topic'],
							 'g_email_limit'		=> intval($this->ipsclass->input['join_limit']).':'.intval($this->ipsclass->input['join_flood']),
							 'g_bypass_badwords'    => $this->ipsclass->input['g_bypass_badwords'],
							 'g_can_msg_attach'     => $this->ipsclass->input['g_can_msg_attach'],
							 'g_attach_per_post'    => $this->ipsclass->input['g_attach_per_post'],
							 'g_topic_rate_setting' => intval($this->ipsclass->input['g_topic_rate_setting']),
							 'g_dname_changes'      => intval($this->ipsclass->input['g_dname_changes']),
							 'g_dname_date'         => intval($this->ipsclass->input['g_dname_date']),
						  );
						  
    	$this->ipsclass->DB->force_data_type = array( 'g_title' => 'string' );						  
						  
		if ($type == 'edit')
		{
			$this->ipsclass->DB->do_update( 'groups', $db_string, 'g_id='.$this->ipsclass->input['id'] );
			
			// Update the title of the group held in the mod table incase it changed.
			
			$this->ipsclass->DB->do_update( 'moderators', array( 'group_name' => trim($this->ipsclass->input['g_title']) ), 'group_id='.$this->ipsclass->input['id'] );
			
			$this->ipsclass->admin->save_log("Edited Group '{$this->ipsclass->input['g_title']}'");
			
			$this->rebuild_group_cache();
			
			$this->ipsclass->main_msg = "Group Edited";
			$this->main_screen();
			
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'groups', $db_string );
			
			$this->ipsclass->admin->save_log("Added Group '{$this->ipsclass->input['g_title']}'");
			
			$this->rebuild_group_cache();
			
			$this->ipsclass->main_msg = "Group Added";
			$this->main_screen();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Rebuild group cache
	/*-------------------------------------------------------------------------*/
	
	function rebuild_group_cache()
	{
		$this->ipsclass->cache['group_cache'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => "*",
													  'from'   => 'groups'
											 )      );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $i = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['group_cache'][ $i['g_id'] ] = $i;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'group_cache', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean Perm string
	/*-------------------------------------------------------------------------*/
	
	function clean_perms($str)
	{
		$str = preg_replace( "/,$/", "", $str );
		$str = str_replace(  ",,", ",", $str );
		
		return $str;
	}
	
	/*-------------------------------------------------------------------------*/
	// Add / edit group
	/*-------------------------------------------------------------------------*/
	
	function group_form($type='edit')
	{
		$all_groups = array( 0 => array ('none', 'Don\'t Promote') );
		
		if ($type == 'edit')
		{
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("No group id to select from the database, please try again.");
			}
			
			if ( $this->ipsclass->vars['admin_group'] == $this->ipsclass->input['id'] )
			{
				if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
				{
					$this->ipsclass->admin->error("Sorry, you are unable to edit that group as it's the root admin group");
				}
			}
			
			$form_code = 'doedit';
			$button    = 'Complete Edit';
				
		}
		else
		{
			$form_code = 'doadd';
			$button    = 'Add Group';
		}
		
		if ($this->ipsclass->input['id'] != "")
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'groups', 'where' => "g_id=".intval($this->ipsclass->input['id']) ) );
			$this->ipsclass->DB->simple_exec();
		
			$group = $this->ipsclass->DB->fetch_row();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title',
														  'from'   => 'groups',
														  'where'  => "g_id <> ".intval($this->ipsclass->input['id']),
														  'order'  => 'g_title' ) );
		}
		else
		{
			$group = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title',
														  'from'   => 'groups',
														  'order'  => 'g_title' ) );
		}
		
		//-----------------------------------------
		// sort out the promotion stuff
		//-----------------------------------------
		
		list($group['g_promotion_id'], $group['g_promotion_posts']) = explode( '&', $group['g_promotion'] );
		
		if ($group['g_promotion_posts'] < 1)
		{
			$group['g_promotion_posts'] = '';
		}
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['g_id'] == $this->ipsclass->vars['admin_group'] )
			{
				continue;
			}
			
			$all_groups[] = array( $r['g_id'], $r['g_title'] );
		}
		
		//-----------------------------------------
		
		$perm_masks = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$perm_masks[] = array( $r['perm_id'], $r['perm_name'] );
		}
		
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			$this->ipsclass->admin->page_title = "Editing User Group ".$group['g_title'];
		}
		else
		{
			$this->ipsclass->admin->page_title = 'Adding a new user group';
			$group['g_title'] = 'New Group';
		}
		
		$guest_legend = "";
		
		if ($group['g_id'] == $this->ipsclass->vars['guest_group'])
		{
			$guest_legend = "</b><br><i>(Does not apply to guests)</i>";
		}
		
		$this->ipsclass->admin->page_detail = "Please double check the information before submitting the form.";
		
		
		//-----------------------------------------
		
		$this->ipsclass->html .= "<script language='javascript'>
						 <!--
						  function checkform() {
						  
						  	isAdmin = document.forms[0].g_access_cp;
						  	isMod   = document.forms[0].g_is_supmod;
						  	
						  	msg = '';
						  	
						  	if (isAdmin[0].checked == true)
						  	{
						  		msg += 'Members in this group can access the Admin Control Panel\\n\\n';
						  	}
						  	
						  	if (isMod[0].checked == true)
						  	{
						  		msg += 'Members in this group are super moderators.\\n\\n';
						  	}
						  	
						  	if (msg != '')
						  	{
						  		msg = 'Security Check\\n--------------\\nMember Group Title: ' + document.forms[0].g_title.value + '\\n--------------\\n\\n' + msg + 'Is this correct?';
						  		
						  		formCheck = confirm(msg);
						  		
						  		if (formCheck == true)
						  		{
						  			return true;
						  		}
						  		else
						  		{
						  			return false;
						  		}
						  	}
						  }
						 //-->
						 </script>\n";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $form_code  ),
																			 2 => array( 'act'   , 'group'     ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']   ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	) , 'adform', "onSubmit='return checkform()'" );
									     
		
		list($p_max, $p_width, $p_height) = explode( ":", $group['g_photo_max_vars'] );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$prefix = str_replace( "'", "&#39;", $group['prefix'] );
		$prefix = str_replace( '"', "&quot;", $prefix );
		$prefix = str_replace( "<", "&lt;" , $prefix          );
		$suffix = str_replace( "'", "&#39;", $group['suffix'] );
		$suffix = str_replace( '"', "&quot;", $suffix );
		$suffix = str_replace( "<", "&lt;" , $suffix          );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Global Settings", "Basic Group Settings" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Group Title</b>" ,
												  $this->ipsclass->adskin->form_input("g_title", $group['g_title'] )
									     )      );
									     
		//-----------------------------------------
		// Sort out default array
		//-----------------------------------------
		
		$this->ipsclass->html .=
		"<script type='text/javascript'>
			
			var show   = '';
		";
		
		foreach ($perm_masks as $d)
		{
			$this->ipsclass->html .= " 		perms_$d[0] = '$d[1]';\n";
		}
		
		$this->ipsclass->html .=
		"	
			var show = '';
			
		 	function saveit(f)
		 	{
		 		show = '';
		 		
		 		for (var i = 0 ; i < f.options.length; i++)
				{
					if (f.options[i].selected)
					{
						tid  = f.options[i].value;
						show += '\\n' + eval('perms_'+tid);
					}
				}
			}
			
			function show_me()
			{
				if (show == '')
				{
					show = 'No change detected\\nClick on the multi-select box to activate';
				}
				
				alert('Selected Permission Sets\\n---------------------------------\\n' + show);
			}
			
		</script>";
		
		$arr = explode( ",", $group['g_perm_id'] );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Use which permission sets...</b><br>You may choose more than one" ,
												  $this->ipsclass->adskin->form_multiselect("permid[]", $perm_masks, $arr, 5, 'onfocus="saveit(this)"; onchange="saveit(this)";' )."<br><input style='margin-top:5px' id='editbutton' type='button' onclick='show_me();' value='Show me selected permissions'>"
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Group Icon Image</b><div style='color:gray'>Can be a relative link, i.e. <b>style_images/1/folder_team_icons/admin.gif</b><br />or it can a full URL starting with <b>'http://'</b><br/ >Use <b>style_images/&lt;#IMG_DIR#&gt;/folder_team_icons/{image}</b> (replace {image} with the image name) to dynamically load the image from the style_image folder based on the member's skin choice.</div>" ,
												  $this->ipsclass->adskin->form_textarea("g_icon", htmlspecialchars($group['g_icon']) )
									     )      );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Online List Format [Prefix]</b><br>(Can be left blank)<br>(Example:&lt;span style='color:red'&gt;)" ,
												  $this->ipsclass->adskin->form_input("prefix", $prefix )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Online List Format [Suffix]</b><br>(Can be left blank)<br>(Example:&lt;/span&gt;)" ,
												  $this->ipsclass->adskin->form_input("suffix", $suffix )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Hide this group from the member list?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_hide_from_list", $group['g_hide_from_list'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Upload Permissions", "Manage permissions for PM and post uploads, etc" );
		
		if( $type == 'edit' AND $group['g_attach_max'] == 0 )
		{
			$group['g_attach_maxdis'] = "<i>unlimited</i>";
		}
		else if( $type == 'edit' AND $group['g_attach_max'] == -1 )
		{
			$group['g_attach_maxdis'] = "<i>disabled</i>";
		}
		else
		{
			$group['g_attach_maxdis'] = $this->ipsclass->size_format( $group['g_attach_max'] * 1024 );
		}
		
		$ini_max = @ini_get( 'upload_max_filesize' ) ? @ini_get( 'upload_max_filesize' ) : '<i>cannot obtain</i>';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>GLOBAL: Max total global file space for all uploads (Inc. PMs and posts) (in KB)</b>".$this->ipsclass->adskin->js_help_link('mg_upload')."<div class='graytext'>Enter -1 to disable uploads or enter 0 to disable this limit.</div>" ,
																 $this->ipsclass->adskin->form_input("g_attach_max", $group['g_attach_max'] ). ' (currently: '.$group['g_attach_maxdis'].')' . "<br /><b>Note that single file uploads are also limited by your PHP configuration to {$ini_max}</b>"
														)      );
										
		if( $type == 'edit' AND $group['g_attach_per_post'] == 0 )
		{
			$group['g_attach_per_postdis'] = "<i>unlimited</i>";
		}
		else if( $type == 'edit' AND $group['g_attach_per_post'] == -1 )
		{
			$group['g_attach_per_postdis'] = "<i>disabled</i>";
		}
		else
		{
			$group['g_attach_per_postdis'] = $this->ipsclass->size_format( $group['g_attach_per_post'] * 1024 );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>PER POST: Max total file space allowed in each post or PM (in KB)</b>".$this->ipsclass->adskin->js_help_link('mg_upload')."<div class='graytext'>Enter 0 to disable a per post limit. This number must be less than the global amount.</div>" ,
																 $this->ipsclass->adskin->form_input("g_attach_per_post", $group['g_attach_per_post'] ). ' (currently: '.$group['g_attach_per_postdis'].')' . "<br /><b>Note that single file uploads are also limited by your PHP configuration to {$ini_max}</b>"
														)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>PERSONAL PHOTO: Max upload file size (in KB)</b><br>(Leave blank to disallow uploads)" ,
																 $this->ipsclass->adskin->form_input("p_max", $p_max )."<br />"
																 ."Max Width (px): <input type='text' size='3' class='textinput' name='p_width' value='{$p_width}'> "
																 ."Max Height (px): <input type='text' size='3' class='textinput' name='p_height' value='{$p_height}'>"
														)      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>AVATARS: Allow avatar uploads?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_avatar_upload", $group['g_avatar_upload'] )
									     )      );
									     						     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>PMs: Allow PM attachments?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_can_msg_attach", $group['g_can_msg_attach'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Global Permissions", "Restricting what this group can do" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can view board?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_view_board", $group['g_view_board'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can view OFFLINE board?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_access_offline", $group['g_access_offline'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can view member profiles and the member list?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_mem_info", $group['g_mem_info'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can view other members topics?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_other_topics", $group['g_other_topics'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can use search?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_use_search", $group['g_use_search'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of seconds for search flood control</b><br>Stops search abuse, enter 0 or leave blank for no flood control" ,
												  $this->ipsclass->adskin->form_input("g_search_flood", $group['g_search_flood'] )
									     )      );
									     
		list( $limit, $flood ) = explode( ":", $group['g_email_limit'] );					     
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can email members from the board?</b><br />Leave bottom section blank to remove limits $guest_legend</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_email_friend", $group['g_email_friend'] )
												 ."<br />Only allow ". $this->ipsclass->adskin->form_simple_input("join_limit", $limit, 2 )." emails in a 24hr period"
												 ."<br />...and only allow 1 email every ".$this->ipsclass->adskin->form_simple_input("join_flood", $flood, 2 )." minutes"
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can edit own profile info?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_edit_profile", $group['g_edit_profile'] )
									     )      );							     
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can use PM system?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_use_pm", $group['g_use_pm'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Max. Number users allowed to mass PM?$guest_legend<br>(Enter 0 or leave blank to disable mass PM)" ,
												  $this->ipsclass->adskin->form_input("g_max_mass_pm", $group['g_max_mass_pm'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Max. Number of storable messages?$guest_legend" ,
												  $this->ipsclass->adskin->form_input("g_max_messages", $group['g_max_messages'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$dd_topic_rate = array( 0 => array( 0, 'No' ), 1 => array( 1, 'Yes (Not allowed to change vote)' ), 2 => array( 2, 'Yes (Allowed to change vote)' ) );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Posting Permissions", "Restrict where this group can post" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can post new topics (where allowed)?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_post_new_topics", $group['g_post_new_topics'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can rate topics (in forums where allowed)?</b>" ,
												  $this->ipsclass->adskin->form_dropdown("g_topic_rate_setting", $dd_topic_rate, $group['g_topic_rate_setting'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can reply to OWN topics?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_reply_own_topics", $group['g_reply_own_topics'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can reply to OTHER members topics (where allowed)?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_reply_other_topics", $group['g_reply_other_topics'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can edit own posts?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_edit_posts", $group['g_edit_posts'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Edit time restriction (in minutes)?$guest_legend<br>Denies user edit after the time set has passed. Leave blank or enter 0 for no restriction" ,
												  $this->ipsclass->adskin->form_input("g_edit_cutoff", $group['g_edit_cutoff'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Allow user to remove 'Edited by' legend?$guest_legend</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_append_edit", $group['g_append_edit'] )
									     )      );							     
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can delete own posts?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_delete_own_posts", $group['g_delete_own_posts'] )
									     )      );
									     						     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can open/close own topics?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_open_close_posts", $group['g_open_close_posts'] )
									     )      );							     
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can edit own topic title & description?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_edit_topic", $group['g_edit_topic'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can delete own topics?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_delete_own_topics", $group['g_delete_own_topics'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can start new polls (where allowed)?$guest_legend</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_post_polls", $group['g_post_polls'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can vote in polls (where allowed)?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_vote_polls", $group['g_vote_polls'] )
									     )      );							     
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can avoid flood control?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_avoid_flood", $group['g_avoid_flood'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can avoid moderation queues?</b>" ,
												  $this->ipsclass->adskin->form_yes_no("g_avoid_q", $group['g_avoid_q'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can post HTML?$guest_legend</b><br />".$this->ipsclass->adskin->js_help_link('mg_dohtml') ,
												  $this->ipsclass->adskin->form_yes_no("g_dohtml", $group['g_dohtml'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can bypass the bad word filter?$guest_legend</b><br />" ,
												  $this->ipsclass->adskin->form_yes_no("g_bypass_badwords", $group['g_bypass_badwords'] )
									     )      );
									     					     							     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// DISPLAY NAME OPTIONS
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Display Name Permissions", "Only valid when allowing display names" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<strong>Display Name Change: Limit Days</strong><div class='desctext'>This is the number of days in which the number of changes are made. For example \"30\" would mean that the user could only change their name X amount of times in a 30 day period</div>" ,
												  $this->ipsclass->adskin->form_input("g_dname_date", $group['g_dname_date'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<strong>Display Name Change: Max changes per X days</strong><div class='desctext'>This relates to the maximum number of changes a user can make to their display name within the X day period set. Use \"0\" to disallow users from changing their own display name.</div>" ,
												  $this->ipsclass->adskin->form_input("g_dname_changes", $group['g_dname_changes'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// MODERATOR OPTIONS
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Moderation Permissions", "Allow or deny this group moderation abilities" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Is Super Moderator (can moderate anywhere)?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_is_supmod", $group['g_is_supmod'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can access the Admin CP?$guest_legend" ,
												  $this->ipsclass->adskin->form_yes_no("g_access_cp", $group['g_access_cp'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Allow user group to post in 'closed' topics?" ,
												  $this->ipsclass->adskin->form_yes_no("g_post_closed", $group['g_post_closed'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Group Promotion" );
		
		if ($group['g_id'] == $this->ipsclass->vars['admin_group'])
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Choose 'Don't Promote' to disable promotions</b><br>".$this->ipsclass->adskin->js_help_link('mg_promote') ,
													  "Feature disabled for the root admin group, after all - if you're at the top where can you be promoted to?"
											 )      );
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Choose 'Don't Promote' to disable promotions</b>$guest_legend<br>".$this->ipsclass->adskin->js_help_link('mg_promote') ,
													  'Promote members of this group to: '.$this->ipsclass->adskin->form_dropdown("g_promotion_id", $all_groups, $group['g_promotion_id'] )
													 .'<br>when they reach '.$this->ipsclass->adskin->form_simple_input('g_promotion_posts', $group['g_promotion_posts'] ).' posts'
											 )      );
		}
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($button);
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
			
			
	}

	/*-------------------------------------------------------------------------*/
	// Show "Management Screen
	/*-------------------------------------------------------------------------*/
	
	function main_screen()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$g_array = array();
		$content = "";
		$form    = array();
		
		//-----------------------------------------
		// Page details
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title = "User Groups";
		$this->ipsclass->admin->page_detail = "User Grouping is a quick and powerful way to organise your members. There are 4 preset groups that you cannot remove (Validating, Guest, Member and Admin) although you may edit these at will. A good example of user grouping is to set up a group called 'Moderators' and allow them access to certain forums other groups do not have access to.<br>Forum access allows you to make quick changes to that groups forum read, write and reply settings. You may do this on a forum per forum basis in forum control.";
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Manage User Groups' );
		
		//-----------------------------------------
		// Get groups
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'groups_main_screen', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Set up basics
			//-----------------------------------------
			
			$row['_can_delete'] = ( $row['g_id'] > 4 ) ? 1 : 0;
			$row['_can_acp']    = ( $row['g_access_cp'] == 1 ) ? 1 : 0;
			$row['_can_supmod'] = ( $row['g_is_supmod'] == 1 ) ? 1 : 0;
			$row['_title']      = $row['prefix'].$row['g_title'].$row['suffix'];
			
			if ( $this->ipsclass->vars['admin_group'] == $row['g_id'] )
			{
				$row['_title'] .= " ( ROOT )";
			}
			
			//-----------------------------------------
			// IMAGES
			//-----------------------------------------
			
			$row['_can_acp_img']    = $row['_can_acp']    ? 'aff_tick.png' : 'aff_cross.png';
			$row['_can_supmod_img'] = $row['_can_supmod'] ? 'aff_tick.png' : 'aff_cross.png';
		
			//-----------------------------------------
			// Add
			//-----------------------------------------
			
			$content .= $this->html->groups_overview_wrapper_row( $row );
			
			//-----------------------------------------
			// Add to array
			//-----------------------------------------
									     
			$g_array[] = array( $row['g_id'], $row['g_title'] );
		}
		
		//-----------------------------------------
		// Add form
		//-----------------------------------------
		
		$form['_new_dd'] = $this->ipsclass->adskin->form_dropdown("id", $g_array, 3 );
		
		$this->ipsclass->html .= $this->html->groups_overview_wrapper( $content, $form );
		
		$this->ipsclass->admin->output();
	}
	
		
}


?>