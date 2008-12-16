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
|   > $Date: 2007-09-19 15:37:06 -0400 (Wed, 19 Sep 2007) $
|   > $Revision: 1107 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Msg Func module
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|   > Module Version 1.0.0
|   > DBA Checked: Fri 21 May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class func_msg
{
	# Classes
	var $ipsclass;
	
	# Others
	var $postlib      = "";
	var $class        = "";
	var $output       = "";
	var $can_upload   = 0;
	var $form_extra   = "";
	var $hidden_field = "";
	var $redirect_url = "";
	var $redirect_lang= "";
	
	var $jump_html	  = "";
	var $vid		  = "";
	
	var $force_pm     = 0;
	
	var $member		  = array();
	
	function register_class( &$class )
	{
		$this->class = &$class;
	}
	
	/*-------------------------------------------------------------------------*/
	// Initiate
	/*-------------------------------------------------------------------------*/
	
	function init()
	{
		//-----------------------------------------
		// Get post stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/post/class_post.php' );
		$this->postlib           =  new class_post();
		$this->postlib->ipsclass =& $this->ipsclass;
		
		$this->postlib->load_classes();
		
		if ( $this->ipsclass->member['g_attach_max'] != -1 and $this->ipsclass->member['g_can_msg_attach'] )
		{
			$this->can_upload   = 1;
			$this->form_extra   = " enctype='multipart/form-data'";
			$this->hidden_field = "<input type='hidden' name='MAX_FILE_SIZE' value='".($this->ipsclass->member['g_attach_max']*1024)."' />";
		}
		
		$this->postlib->can_upload = $this->can_upload;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Generate the UserCP menu
	/*-------------------------------------------------------------------------*/
	
	function ucp_generate_menu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$menu_html      = "";
		$component_html = "";
		$folder_links   = "";
		
		//-----------------------------------------
		// Get all member info..
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'generic_get_all_member', array( 'mid' => $this->ipsclass->member['id'] ) );
		$this->ipsclass->DB->cache_exec_query();
    			   
    	if ( ! $member = $this->ipsclass->DB->fetch_row() )
    	{
    		$this->ipsclass->DB->do_insert( 'member_extra', array( 'id' => $this->ipsclass->member['id'] ) );
    	}
    	else
    	{
    		$this->ipsclass->member = array_merge( $member, $this->ipsclass->member );
    	}
    	
    	//-----------------------------------------
    	// Get enabled components
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['components'] ) and count( $this->ipsclass->cache['components'] ) )
    	{
    		foreach( $this->ipsclass->cache['components'] as $data )
    		{
    			$file = ROOT_PATH.'sources/components_ucp/'.$data['com_filename'].'.php';
    			
    			if ( file_exists( $file ) )
    			{
    				require_once( $file );
    				$name = 'components_ucp_'.$data['com_filename'];
    				$tmp  = new $name();
    				$tmp->ipsclass =& $this->ipsclass;
    				
    				$component_html .= $tmp->ucp_build_menu();
    				
    				unset($tmp);
    			}
			}
		}
		
		//-----------------------------------------
    	// Print the top button menu
    	//-----------------------------------------
    	
    	$menu_html = $this->ipsclass->compiled_templates['skin_msg']->Menu_bar( $component_html );
    	
    	//-----------------------------------------
    	// Messenger
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->member['g_use_pm'] )
        {
        	//-----------------------------------------
			// Do a little set up, do a litle dance, get
			// down tonight! *boogie*
			//-----------------------------------------
			
			$this->jump_html = "<select name='VID' class='forminput'>\n";
			
			$this->ipsclass->member['dir_data'] = array();
			
			//-----------------------------------------
			// Do we have VID?
			// No, it's just the way we walk! Haha, etc.
			//-----------------------------------------
			
			if ( isset($this->ipsclass->input['VID']) AND $this->ipsclass->input['VID'] )
			{
				$this->vid = $this->ipsclass->input['VID'];
			}
			else
			{
				$this->vid = 'in';
			}
    	
        	//-----------------------------------------
    		// Print folder links
    		//-----------------------------------------
    		
			if ( ! $this->ipsclass->member['vdirs'] )
			{
				$this->ipsclass->member['vdirs'] = "in:Inbox|sent:Sent Items";
			}
			
			foreach( explode( "|", $this->ipsclass->member['vdirs'] ) as $dir )
			{
				list ($id  , $data)  = explode( ":", $dir );
				list ($real, $count) = explode( ";", $data );
				
				if ( ! $id )
				{
					continue;
				}
				
				$this->ipsclass->member['dir_data'][$id] = array( 'id' => $id, 'real' => $real, 'count' => $count );
    		
				if ($this->vid == $id)
				{
					$this->ipsclass->member['current_dir'] = $real;
					$this->ipsclass->member['current_id']  = $id;
					$this->jump_html .= "<option value='$id' selected='selected'>$real</option>\n";
				}
				else
				{
					$this->jump_html .= "<option value='$id'>$real</option>\n";
				}
				
				if ( $count )
				{
					$real .= " ({$count})";
				}
				
				$folder_links .= $this->ipsclass->compiled_templates['skin_msg']->menu_bar_msg_folder_link($id, $real);
			}
			
			if ( $folder_links != "" )
			{
				$menu_html = str_replace( "<!--IBF.FOLDER_LINKS-->", $folder_links, $menu_html );
			}
        }
        
        $this->jump_html .= "<!--EXTRA--></select>\n\n";
        
        return $menu_html;
	}	
	
	/*-------------------------------------------------------------------------*/
	// Send form stuff
	/*-------------------------------------------------------------------------*/
	
	function send_form($preview=0, $errors="")
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_msg_id            = 0;
		
		$this->form_mid     = intval($this->ipsclass->input['MID']);
 		$this->form_orig_id = intval($this->ipsclass->input['MSID']);
 		
 		$_POST['Post-NS']   = isset($_POST['Post']) ? $_POST['Post'] : '';
 		$_POST['Post']      = $this->ipsclass->remove_tags( $this->ipsclass->txt_raw2form( isset($_POST['Post']) ? $_POST['Post'] : '' ) );
 		
 		//-----------------------------------------
 		// Fix up errors
 		//-----------------------------------------
 		
 		$errors = preg_replace( "/^<br>/", "", $errors );
 		
    	//-----------------------------------------
    	// Preview post?
    	//-----------------------------------------
    	
    	if ( $preview )
    	{
    		$this->postlib->parser->parse_html    = $this->ipsclass->vars['msg_allow_html'];
			$this->postlib->parser->parse_nl2br   = 1;
			$this->postlib->parser->parse_smilies = 1;
			$this->postlib->parser->parse_bbcode  = $this->ipsclass->vars['msg_allow_code'];
			
			$this->ipsclass->vars['max_emos']	  = 0;
			
			$old_msg = $this->postlib->han_editor->process_raw_post( 'Post-NS' );
			$old_msg = $this->postlib->parser->pre_display_parse( $this->postlib->parser->pre_db_parse( $old_msg ) );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->preview($old_msg);
    	}
    	
    	if ( $errors != "" OR $this->postlib->parser->error != "" )
    	{
	    	$errors = $errors ? $errors : $this->ipsclass->lang[$this->postlib->parser->error];
	    	
    		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->pm_errors($errors);
    		$preview = 1;
    	}
    	
    	//-----------------------------------------
 		// Load the contacts
 		//-----------------------------------------
 		
 		$contacts = $this->build_contact_list();
 		
 		$name_to_enter = "";
 		$old_message   = "";
 		$old_title     = "";
    	
    	//-----------------------------------------
 		// Did we come from a button with a user ID?
 		//-----------------------------------------
 		
		if ( $this->form_mid  )
		{ 
			$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name, name, id', 'from' => 'members', 'where' => "id=".$this->form_mid ) );
			$this->ipsclass->DB->simple_exec();
			
			$name = $this->ipsclass->DB->fetch_row();

			if ( $name['id'] && !( isset($this->ipsclass->input['fwd']) AND $this->ipsclass->input['fwd'] == 1 ) )
			{
				$name_to_enter = $name['members_display_name'];
			}
		}
		else
		{
			$name_to_enter = isset($this->ipsclass->input['entered_name']) ? $this->ipsclass->input['entered_name'] : '';
		}
 		
 		//-----------------------------------------
 		// Are we quoting an old message?
 		//-----------------------------------------
 		
 		$footer_defaults = array( 'add_sent' => '', 'add_tracking' => '' );
 		
 		if ( $preview or $this->class->show_form )
 		{
 			$old_message = $_POST['Post-NS'];
 			$old_title   = str_replace( "'", "&#39;", str_replace( '"', '&#34;', $this->ipsclass->txt_stripslashes($_POST['msg_title']) ) );
 			
			// If we preview and check these boxes, they should default to checked
			$footer_defaults = array(
								 	 'add_sent' 	=> ( isset($this->ipsclass->input['add_sent']) AND $this->ipsclass->input['add_sent'] == 'yes' ) ? "checked='checked' " : "",
								 	 'add_tracking' => ( isset($this->ipsclass->input['add_tracking']) AND $this->ipsclass->input['add_tracking'] == '1' ) ? "checked='checked' " : "",
								 	);
 		}
 		else if ( $this->form_orig_id )
 		{
 			$this->ipsclass->DB->cache_add_query( 'msg_get_saved_msg', array( 'msgid' => $this->form_orig_id, 'mid' => $this->ipsclass->member['id'] ) );
 			$this->ipsclass->DB->simple_exec();
 			
 			$old_msg = $this->ipsclass->DB->fetch_row();
 			
 			if( $old_msg['from_id'] && !( isset($this->ipsclass->input['fwd']) AND $this->ipsclass->input['fwd'] == 1 ) )
 			{
	 			$name_to_enter = $old_msg['from_name'];
 			}
 			
 			if ( $old_msg['mt_title'] )
 			{
 				if ( $this->class->edit_saved ) 
				{
					$name_to_enter         = $old_msg['members_display_name'];
					$cc_text               = $old_msg['msg_cc_users'];
					$cc_hide			   = $old_msg['mt_hide_cc'];
					$old_track			   = $old_msg['mt_tracking'];
					$old_addsent		   = $old_msg['mt_addtosent'];
					$old_title             = $old_msg['mt_title'];
					$old_message           = $old_msg['msg_post'];//$this->postlib->parser->bad_words( $this->ipsclass->my_br2nl( $old_msg['msg_post'] ) );
					$_msg_id               = $old_msg['mt_msg_id'];
					
					$this->class->post_key        = $old_msg['msg_post_key'];
					$this->ipsclass->input['OID'] = $old_msg['mt_id'];
				}
 				else if ( isset($this->ipsclass->input['fwd']) AND $this->ipsclass->input['fwd'] == 1 )
 				{
 					$old_title     = "Fwd:".$old_msg['mt_title'];
 					$old_title     = preg_replace( "/^(?:Fwd\:){1,}/i", "Fwd:", $old_title );
 					$old_message   = '[QUOTE]'.sprintf($this->ipsclass->lang['vm_forward_text'], $name['members_display_name'])."\n\n".$old_msg['msg_post'].'[/QUOTE]'."\n";
 					//$old_message   = $this->postlib->parser->bad_words( $this->ipsclass->my_br2nl( $old_message ) );
 				}
 				else
 				{
 					$old_title   = "Re:".$old_msg['mt_title'];
 					$old_title   = preg_replace( "/^(?:Re\:){1,}/i", "Re:", $old_title );
 					$old_message = '[QUOTE]'.$old_msg['msg_post'].'[/QUOTE]'."\n";
 					//$old_message = $this->postlib->parser->bad_words( $this->ipsclass->my_br2nl( $old_message ) );
 				}
 			}
 			
			$footer_defaults = array(
								 	 'add_sent' 	=> ( isset($old_addsent) AND $old_addsent == '1' ) ? "checked='checked' " : "",
								 	 'add_tracking' => ( isset($old_track) AND $old_track == '1' ) ? "checked='checked' " : "",
								 	); 			
 		}
 		
 		//-----------------------------------------
 		// PM returns
 		//-----------------------------------------
 		
		if ( $this->postlib->han_editor->method == 'rte' AND $old_message AND $this->form_orig_id )
		{
			$old_message = $this->postlib->parser->convert_ipb_html_to_html( $this->ipsclass->my_nl2br( $old_message ) );	
		}
		else if( $this->postlib->han_editor->method == 'std' AND $old_message AND $this->form_orig_id )
		{
			$old_message = $this->postlib->parser->pre_edit_parse( $old_message );
		} 		
 		
 		//-----------------------------------------
 		// Build up the HTML for the send form
 		//-----------------------------------------

 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->Send_form( array (
																							 'CONTACTS'        => $contacts,
																							 'MEMBER'          => $this->member,
																							 'N_ENTER'         => $name_to_enter,
																							 'O_TITLE'         => $old_title,
																							 'OID'             => isset($this->ipsclass->input['OID']) ? $this->ipsclass->input['OID'] : 0, // Old unsent msg id for restoring saved msg - used to delete saved when sent
																							 'attach_post_key' => $this->class->post_key,
																							 'form_extra'      => $this->form_extra,
																							 'upload'          => $this->hidden_field,
																							 
																				   )       );
 		
 		$this->ipsclass->lang['the_max_length'] = $this->ipsclass->vars['max_post_length'] * 1024;
 		
 		//-----------------------------------------
 		// Remove side panel
 		//-----------------------------------------
 		
 		$this->postlib->han_editor->remove_side_panel = 1;
 		
 		//-----------------------------------------
 		// Is this RTE? If so, convert BBCode
 		//-----------------------------------------
 		
 		if ( $this->postlib->han_editor->method == 'rte' AND $old_message )
 		{
			if ( $errors or $preview )
			{
				$old_message = stripslashes( $old_message );
			}
			
 			$old_message = $this->postlib->parser->convert_ipb_html_to_html( $old_message );
 		}
 		else if ( $old_message )
 		{
 			$old_message = $this->ipsclass->txt_stripslashes( $old_message );
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->postbox_wrap( $this->postlib->han_editor->show_editor( $old_message, 'Post' ) );
 		
 		//-----------------------------------------
 		// Show upload stuff?
 		//-----------------------------------------
 		
 		if ( $this->can_upload )
		{
			$upload_field = $this->ipsclass->compiled_templates['skin_post']->Upload_field( $this->class->post_key, 'msg', $_msg_id );

			$this->output = str_replace( '<!--UPLOAD FIELD-->', $upload_field, $this->output );
		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->send_form_footer( $footer_defaults );
 		
		$this->class->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->class->nav        = array( "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
		
		//-----------------------------------------
 		// Do we have permission to mass PM peeps?
 		//-----------------------------------------
 		
 		$cc_box 	= "";
 		$cc_formbox = "";
 		
 		if ($this->ipsclass->member['g_max_mass_pm'] > 0)
 		{
 			$this->ipsclass->lang['carbon_copy_desc'] = sprintf( $this->ipsclass->lang['carbon_copy_desc'], $this->ipsclass->member['g_max_mass_pm'] );
 			
 			if ( isset($_POST['carbon_copy']) or isset($cc_text) )
 			{
 				$cc_text = isset($cc_text) ? $cc_text : $this->ipsclass->txt_htmlspecialchars($_POST['carbon_copy']);
 				
 				$cc_box = str_replace( "</textarea>", "", $this->ipsclass->txt_stripslashes($cc_text) );
 			}
 			
 			if ( ( isset($this->ipsclass->input['mt_hide_cc']) AND intval($this->ipsclass->input['mt_hide_cc']) == 1 ) or ( isset($cc_hide) AND $cc_hide == 1 ) )
 			{
 				$cc_formbox = $cc_hide ? $cc_hide : intval($this->ipsclass->input['mt_hide_cc']);
 				
 				$cc_formbox = $cc_formbox == 1 ? "checked='checked' " : "";
 			} 			
 			
 			$this->output = str_replace( "<!--IBF.MASS_PM_BOX-->", $this->ipsclass->compiled_templates['skin_msg']->mass_pm_box($cc_box, $cc_formbox), $this->output );
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Build contact listy poos
 	/*-------------------------------------------------------------------------*/
 	
 	function build_contact_list()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$contacts     = "";
		$from_contact = $this->ipsclass->input['from_contact'] ? intval($this->ipsclass->input['from_contact']) : intval($this->ipsclass->input['MID']);
 		$member_id    = intval( $this->ipsclass->member['id'] );
 		
		//-----------------------------------------
		// Clear out from_contact for fwd
		//-----------------------------------------
		
		if( $this->ipsclass->input['fwd'] )
		{
			$from_contact = 0;
		}

		//-----------------------------------------
		// Get 'em.
		//-----------------------------------------

		$this->ipsclass->DB->build_query( array( 'select'   => 'pf.friends_friend_id, pf.friends_approved',
												 'from'     => array( 'profile_friends' => 'pf' ),
												 'where'    => 'pf.friends_member_id='.$member_id. ' AND pf.friends_approved=1',
												 'order'    => 'm.members_display_name ASC',
												 'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => 'm.id=pf.friends_friend_id',
																				  'type'   => 'left' ),
 																	) 
 										) 		);
																				
		$this->ipsclass->DB->exec_query();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$contacts = "<select name='from_contact' class='forminput'><option value='-'>".$this->ipsclass->lang['other']."</option>\n<option value='-'>--------------------</option>\n";
			
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
	 			$selected  = ( $from_contact == $row['friends_friend_id'] ) ? ' selected="selected"' : '';
	 			$contacts .= "<option value='".$row['friends_friend_id']."'{$selected}>".$row['members_display_name']."</option>\n";
	 		}
	
			$contacts .= "</select>\n";
		}	
		else
 		{
 			$contacts = $this->ipsclass->lang['address_list_empty'];
 		}
 		
 		return $contacts;
 	}
 	
 	
 	//-----------------------------------------
 	// API for deleting messages
 	//-----------------------------------------
 	
 	function delete_messages($ids, $owner_id, $extra="")
 	{
		//-----------------------------------------
 		// Basic WHERE
 		//-----------------------------------------
 		
 		if ( ! $extra )
 		{
 			$extra = "mt_owner_id=$owner_id";
 		}
 		
 		$id_string = "";
 		
 		if ( is_array( $ids ) )
 		{
 			if ( ! count($ids) )
 			{
 				return;
 			}
 			
 			$id_string = 'IN ('.implode( ",", $ids ).')';
 		}
 		else
 		{
 			if ( ! $ids )
 			{
 				return;
 			}
 			
 			$id_string = '='.$ids;
 		}
 		
 		//-----------------------------------------
 		// Are these our messages?
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'mt_id, mt_msg_id, mt_read, mt_owner_id', 'from' => 'message_topics', 'where' => "$extra AND mt_id $id_string" ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		$final_ids = array();
 		$final_mts = array();
 		$unread	   = array();
 		
 		while ( $i = $this->ipsclass->DB->fetch_row() )
 		{
 			$final_ids[ $i['mt_id'] ] = $i['mt_msg_id'];
 			$final_mts[ $i['mt_id'] ] = $i['mt_id'];
 			
 			if( $i['mt_read'] == 0 AND $i['mt_owner_id'] > 0 )
 			{
	 			$unread[ $i['mt_owner_id'] ] = intval($unread[ $i['mt_owner_id'] ]) + 1;
 			}
 		}

 		//-----------------------------------------
 		// Delete MT topics
 		//-----------------------------------------
 		
 		if ( count($final_mts) )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'delete' => 'message_topics', 'where' => "mt_id IN (".implode( ',',$final_mts ).")" ) );
 			$this->ipsclass->DB->simple_exec();
 		}
 		
 		//-----------------------------------------
 		// Update delete count
 		//-----------------------------------------
 		
 		if ( count($final_ids) )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'update' => 'message_text', 'set' => "msg_deleted_count=msg_deleted_count+1", 'where' => "msg_id IN (".implode( ',',$final_ids ).")" ) );
 			$this->ipsclass->DB->simple_exec();
 		}
 		
 		//-----------------------------------------
 		// Update new PM notifications
 		//-----------------------------------------
 		
 		if ( count($unread) )
 		{
	 		$members = array();
	 		
	 		$this->ipsclass->DB->build_query( array( 'select' => 'new_msg,id', 'from' => 'members', 'where' => "id IN(" . implode( ',', array_keys($unread) ) .")" ) );
	 		$this->ipsclass->DB->exec_query();
	 		
	 		while( $mem_pm_cnts = $this->ipsclass->DB->fetch_row() )
	 		{
		 		$members[ $mem_pm_cnts['id'] ] = $mem_pm_cnts['new_msg'];
	 		}
	 		
	 		foreach( $unread as $mid => $cnt )
	 		{
		 		$cur = $members[ $mid ];
		 		
		 		if( $cur < $cnt )
		 		{
			 		$cnt = $cur;
		 		}
		 		
		 		$cnt = intval($cnt);

	 			$this->ipsclass->DB->simple_construct( array( 'update' => 'members', 'set' => "new_msg=new_msg-{$cnt}, show_popup=0", 'where' => "id={$mid}" ) );
	 			$this->ipsclass->DB->simple_exec();
 			}
 			
 			unset($members);
 		}
 		
 		//-----------------------------------------
 		// Run through and delete dead msgs
 		//-----------------------------------------
 		
 		$deleted_ids = array();
 		$attach_ids  = array();
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'msg_id', 'from' => 'message_text', 'where' => 'msg_deleted_count >= msg_sent_to_count' ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		while ( $r = $this->ipsclass->DB->fetch_row() )
 		{
 			$deleted_ids[] = $r['msg_id'];
 		}
 		
 		if ( count($deleted_ids) )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'delete' => 'message_text', 'where' => "msg_id IN (".implode( ',',$deleted_ids ).")") );
 			$this->ipsclass->DB->simple_exec();
 			
 			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'attachments',
														  'where'  => "attach_rel_module='msg' AND attach_rel_id IN (".implode( ',',$deleted_ids ).")") );
 			$this->ipsclass->DB->simple_exec();
 		
 			while ( $a = $this->ipsclass->DB->fetch_row() )
 			{
 				$attach_ids[] = $a['attach_id'];
 				
 				if ( $a['attach_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$a['attach_location'] );
				}
				if ( $a['attach_thumb_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$a['attach_thumb_location'] );
				}
 			}
 			
 			if ( count($attach_ids) )
 			{
 				$this->ipsclass->DB->simple_construct( array( 'delete' => 'attachments', 'where' => "attach_id IN (".implode( ',',$attach_ids ).")") );
 				$this->ipsclass->DB->simple_exec();
 			}
 		}
 	}

 	/*-------------------------------------------------------------------------*/
	// Send form stuff
	/*-------------------------------------------------------------------------*/
	
	function send_pm( $opts=array() )
 	{
		//-----------------------------------------
 		// INIT some vars
 		//-----------------------------------------
 		
 		if ( ! $this->to and $this->to_by_id )
 		{
 			//-----------------------------------------
 			// Just an id...
 			//-----------------------------------------
 			
 			$tmp = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'name, members_display_name', 'from' => 'members', 'where' => 'id='.$this->to_by_id ) );
 			
 			$this->to = $tmp['members_display_name'];
 		}
 		
 		$this->to = strtolower(str_replace( '|', '&#124;', $this->to) );
 		
 		$this->ipsclass->DB->cache_add_query( 'msg_get_cc_users', array( 'name_array' => array( 0 => "'".$this->to."'" ) ) );
		$this->ipsclass->DB->simple_exec();
			
 		if ( ! $this->send_to_member = $this->ipsclass->DB->fetch_row() )
 		{
 			$this->error = $this->ipsclass->lang['err_no_such_member'];
 			return;
 		}
 		
 		$this->error = "";
 		$this->save_only      = $opts['save_only'];
 		$this->orig_id        = $opts['orig_id'];
 		$this->preview        = $opts['preview'];
 		$this->add_tracking   = $opts['track'];
 		$this->add_sent       = $opts['add_sent'];
 		$this->hide_cc        = $opts['hide_cc'];
 		
		//-----------------------------------------
 		// Are we simply saving this for later?
 		//-----------------------------------------
 		
 		$this->_process_save_only();
 		
 		if ( $this->redirect_url )
 		{
 			return;
 		}
 		
 		if ( $this->force_pm != 1 )
 		{
 			//-----------------------------------------
			// Can the reciepient use the PM system?
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'msg_get_msg_poster', array( 'mid' => $this->send_to_member['id'] ) );
			$this->ipsclass->DB->simple_exec();
			
			$to_msg_stats = $this->ipsclass->DB->fetch_row();
			
			// Are they in a secondary group that can use pm?
			
			if ( $to_msg_stats['mgroup_others'] && ! $to_msg_stats['g_use_pm'] )
			{
				$groups_id = explode( ',', $to_msg_stats['mgroup_others'] );
                
				if ( count( $groups_id ) )
				{
					foreach( $groups_id as $pid )
					{
						if ( ! $this->ipsclass->cache['group_cache'][ $pid ]['g_id'] )
						{
							continue;
						}
                        
						if ( $this->ipsclass->cache['group_cache'][ $pid ]['g_use_pm'] )
						{
							$to_msg_stats['g_use_pm'] = 1;
							break;
						}
					}
				}
			}			
 		
			if ( $to_msg_stats['g_use_pm'] != 1 OR $to_msg_stats['members_disable_pm'] )
			{
				$this->ipsclass->input['MID'] = $this->send_to_member['id'];
				$this->error = $this->ipsclass->lang['no_usepm_member'];
				return;
			}
			
			//-----------------------------------------
			// Does the target member have enough room
			// in their inbox for a new message?
			//-----------------------------------------
			
			$to_msg_stats = $this->_get_real_allowance( $to_msg_stats );
			
			if ( (($to_msg_stats['msg_total']) >= $to_msg_stats['g_max_messages']) and ($to_msg_stats['g_max_messages'] > 0) )
			{
		 		if ( $this->ipsclass->vars['override_inbox_full'] )
		 		{
			 		$override = array();
			 		$override = explode( ",", $this->ipsclass->vars['override_inbox_full'] );
			 		
			 		$do_override = 0;
			 		
			 		$my_groups = array( $this->ipsclass->member['mgroup'] );
			 		
			 		if( $this->ipsclass->member['mgroup_others'] )
			 		{
				 		$my_other_groups = explode( ",", $this->ipsclass->member['mgroup_others'] );
				 		$my_groups = array_merge( $my_groups, $my_other_groups );
			 		}
			 		
			 		foreach( $my_groups as $member_group )
			 		{
				 		if( in_array( $member_group, $override ) )
				 		{
					 		$do_override = 1;
				 		}
				 	}
				 	
				 	if ( $do_override == 0 )
				 	{
					 	$this->error = $this->ipsclass->lang['no_usepm_member_full'];
					 	return;
				 	}
		 		}
		 		else
		 		{
					$this->error = $this->ipsclass->lang['no_usepm_member_full'];
					return;
				}
			}
 		
			//-----------------------------------------
			// Has the reciepient blocked us?
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'contact_id, allow_msg', 'from' => 'contacts', 'where' => "contact_id=".$this->from_member['id']." AND member_id=".$this->send_to_member['id'] ) );
			$this->ipsclass->DB->simple_exec();
			
			$can_msg = $this->ipsclass->DB->fetch_row();
 		
			if ( (isset($can_msg['contact_id'])) and ($can_msg['allow_msg'] != 1) )
			{
		 		if( $this->ipsclass->vars['unblockable_pm_groups'] )
		 		{
			 		$unblockable = array();
			 		$unblockable = explode( ",", $this->ipsclass->vars['unblockable_pm_groups'] );
			 		
			 		$do_override = 0;
			 		
			 		$my_groups = array( $this->ipsclass->member['mgroup'] );
			 		
			 		if( $this->ipsclass->member['mgroup_others'] )
			 		{
				 		$my_other_groups = explode( ",", $this->ipsclass->member['mgroup_others'] );
				 		$my_groups = array_merge( $my_groups, $my_other_groups );
			 		}
			 		
			 		foreach( $my_groups as $member_group )
			 		{
				 		if( in_array( $member_group, $unblockable ) )
				 		{
					 		$do_override = 1;
				 		}
				 	}
				 	
				 	if ( $do_override == 0 )
				 	{
					 	$this->ipsclass->input['MID'] = $this->send_to_member['id'];
					 	$this->error = $this->ipsclass->lang['msg_blocked'];
					 	return;
				 	}
		 		}
		 		else
		 		{ 
			 		$this->ipsclass->input['MID'] = $this->send_to_member['id'];
					$this->error = $this->ipsclass->lang['msg_blocked'];
					return;
				}				
			}
			
			//-----------------------------------------
			// Do we have enough room to store a
			// saved copy?
			//-----------------------------------------
			
			if (isset($this->ipsclass->input['add_sent']) AND $this->ipsclass->input['add_sent'] AND ($this->ipsclass->member['g_max_messages'] > 0) )
			{
				if ( ($this->ipsclass->member['msg_total'] + 1) >= $this->ipsclass->member['g_max_messages'] )
				{
					$this->error = $this->ipsclass->lang['max_message_from'];
					return;
				}
			}
 		}
 		
 		//-----------------------------------------
 		// CC PM stuff
 		//-----------------------------------------
 		
 		$this->can_mass_pm = 0;
 		
 		if ( $this->ipsclass->member['g_max_mass_pm'] > 0 or $this->force_pm )
 		{
 			$cc_array = $this->_process_cc();
 		}
 		
 		if ( $this->error != "" )
 		{
 			return;
 		}
 		
 		//-----------------------------------------
 		// Add our original ID
 		//-----------------------------------------
 		
 		$cc_array[ $this->send_to_member['id'] ] = $this->send_to_member;
 		
 		unset($to_member);
 		
 		//-----------------------------------------
 		// Insert the message body
 		//-----------------------------------------
 		
 		$count = count( $cc_array );
 		
 		if ( $this->add_sent )
 		{
 			// we're storing a copy locally, so
 			// add 1 to the "sent_to_count"
 			
 			$count++;
 		}
 		
 		$this->ipsclass->DB->do_insert( 'message_text', array(
															   'msg_date'	       => time(),
															   'msg_post'          => $this->ipsclass->remove_tags($this->msg_post),
															   'msg_cc_users'      => $this->cc_users,
															   'msg_sent_to_count' => $count,
															   'msg_post_key'      => $this->class->post_key,
															   'msg_author_id'     => $this->from_member['id'],
															   'msg_ip_address'    => $this->ipsclass->ip_address
													  )      );
			
			
		$msg_id = $this->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Make attachments permanent
		//-----------------------------------------
		
		$no_attachments = $this->postlib->pf_make_attachments_permanent( $this->class->post_key, $msg_id, 'msg' );
		
		//-----------------------------------------
		// If we have an original ID - delete it and 'move'
		// attachments
		//-----------------------------------------
		
		if ( $this->orig_id )
		{
			$this->ipsclass->DB->cache_add_query( 'msg_get_saved_msg', array( 'mid' => $this->from_member['id'], 'msgid' => $this->orig_id ) );
			$this->ipsclass->DB->simple_exec();
			
			if( $old = $this->ipsclass->DB->fetch_row() )
			{
				//-----------------------------------------
				// Update attachments
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'update' => 'attachments', 'set' => "attach_post_key='{$this->class->post_key}', attach_rel_id=$msg_id", 'where' => "attach_rel_id={$old['msg_id']} AND attach_rel_module='msg'" ) );
				$this->ipsclass->DB->simple_exec();
				
				$this->ipsclass->DB->simple_construct( array( 'delete' => 'message_topics', 'where' => "mt_id={$old['mt_id']}" ) );
				$this->ipsclass->DB->simple_exec();
				
				$this->ipsclass->DB->simple_construct( array ( 'update' => 'message_text', 'set' => 'msg_deleted_count=msg_deleted_count-1', 'where' => "msg_id={$old['msg_id']}" ) );
				$this->ipsclass->DB->simple_exec();
				
				$no_attachments = $old['mt_hasattach'];
			}
		}
 		
 		//-----------------------------------------
 		// loop....
 		//-----------------------------------------
 		
 		foreach ($cc_array as $to_member)
 		{
			//-----------------------------------------
			// Sort out tracking and pop us status
			//-----------------------------------------
			
			$show_popup =  $to_member['view_pop'];
			
			//-----------------------------------------
			// Enter the info into the DB
			// Target user side.
			//-----------------------------------------
			
			$this->ipsclass->DB->force_data_type = array( 'mt_title' => 'string' );
			
			$this->ipsclass->DB->do_insert( 'message_topics', array(
													 				 'mt_msg_id'     => $msg_id,
																	 'mt_date'       => time(),
																	 'mt_title'      => $this->msg_title,
																	 'mt_from_id'    => $this->from_member['id'],
																	 'mt_to_id'      => $to_member['id'],
																	 'mt_vid_folder' => 'in',
																	 'mt_tracking'   => intval( $this->add_tracking ),
																	 'mt_addtosent'	 => intval( $this->add_sent ),
																	 'mt_hasattach'  => intval($no_attachments),
																	 'mt_owner_id'   => $to_member['id'],
																	 'mt_hide_cc'    => intval( $this->hide_cc ),
													       )      );
			
			
			$mt_id = $this->ipsclass->DB->get_insert_id();
			
			//-----------------------------------------
			// Update profile
			//-----------------------------------------
			
			$inbox_count = $this->_get_dir_count( $to_member['vdirs'], 'in' );
			
			$new_vdir = $this->rebuild_dir_count( $to_member['id'],
												  "",
												  'in',
												  $inbox_count + 1,
												  'save',
												  "msg_total=msg_total+1,new_msg=new_msg+1,show_popup={$show_popup}"
												);
												
			//-----------------------------------------
			// Has this member requested a PM email nofity?
			//-----------------------------------------
			
			if ($to_member['email_pm'] == 1)
			{
				$to_member['language'] = $to_member['language'] == "" ? $this->ipsclass->vars['default_language'] : $to_member['language'];
				
				$this->postlib->email->get_template("pm_notify", $to_member['language']);
			
				$this->postlib->email->build_message( array(
													'NAME'   => $to_member['members_display_name'],
													'POSTER' => $this->from_member['members_display_name'],
													'TITLE'  => $this->msg_title,
													'LINK'   => "?act=Msg&CODE=03&VID=in&MSID=$mt_id",
													)       );
											
				$this->ipsclass->DB->do_insert( 'mail_queue', array( 'mail_to' => $to_member['email'], 'mail_date' => time(), 'mail_subject' => $this->postlib->email->lang_subject, 'mail_content' => $this->postlib->email->message ) );

				$this->ipsclass->cache['systemvars']['mail_queue'] += $count;
				$this->ipsclass->update_cache( array( 'array' => 1, 'name' => 'systemvars', 'donow' => 1, 'deletefirst' => 0 ) );
			}
		}
		
 		//-----------------------------------------
 		// Add the data to the current members DB if we are
 		// adding it to our "sent items" folder
 		//-----------------------------------------
 		
 		if ( $this->add_sent )
 		{
 			$sent_count = $this->_get_dir_count( $this->from_member['vdirs'], 'sent' );
			
			$this->rebuild_dir_count( $this->from_member['id'],
									  "",
									  'sent',
									  $sent_count + 1,
									  'save',
									  "msg_total=msg_total+1"
									);
									 
			$this->ipsclass->DB->do_insert( 'message_topics', array(
													 'mt_msg_id'     => $msg_id,
													 'mt_date'       => time(),
													 'mt_title'      => $this->msg_title,
													 'mt_from_id'    => $this->from_member['id'],
													 'mt_to_id'      => $this->send_to_member['id'],
													 'mt_vid_folder' => 'sent',
													 'mt_tracking'   => 0,
													 'mt_addtosent'	 => 0,
													 'mt_hasattach'  => intval($no_attachments),
													 'mt_owner_id'   => $this->from_member['id'],
													 'mt_hide_cc'    => $this->hide_cc,
									       )      );
			
		}
		
		$this->to_by_id = "";
		$this->to       = "";
		
		#unset($this->postlib);
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
	// Rebuild DIR count
	/*-------------------------------------------------------------------------*/
	
	function rebuild_dir_count($mid, $vdir, $cur_dir, $new_count, $nosave='save', $extra="")
	{
		$rebuild = array();

		if ( ! $vdir )
		{
			$this->ipsclass->DB->simple_construct( array( "select" => 'vdirs', 'from' => 'member_extra', 'where' => 'id='.$mid ) );
			$this->ipsclass->DB->simple_exec();
			
			$mem = $this->ipsclass->DB->fetch_row();
			
			$vdir = $mem['vdirs'] ? $mem['vdirs'] : 'in:Inbox;0|sent:Sent Items;0';
		}
		
		foreach( explode( "|", $vdir ) as $dir )
    	{
    		list ($id  , $data)  = explode( ":", $dir );
    		list ($real, $count) = explode( ";", $data );
    		
    		if ( ! $id )
    		{
    			continue;
    		}
    		
    		if ( $id == $cur_dir )
    		{
    			$count = $new_count;
    			$count = $count < 1 ? 0 : $count;
    		}
    		
    		$rebuild[$id] = $id.':'.$real.';'.intval($count);
    	}
    	
    	$final = implode( '|', $rebuild );
    	
    	if ( $nosave != 'nosave' )
    	{
			$this->ipsclass->DB->simple_construct( array( 'update' =>  'member_extra', 'set' => "vdirs='".$final."'", 'where' => 'id='.$mid ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( $extra )
			{
				$this->ipsclass->DB->simple_construct( array( 'update' =>  'members', 'set' => $extra, 'where' => 'id='.$mid ) );
				$this->ipsclass->DB->simple_exec();
			}
    	}
    	
    	return $final;
	}
	
 	
 	/*-------------------------------------------------------------------------*/
	// POST PROCESS CC
	/*-------------------------------------------------------------------------*/
	
	function format_cc_string($cc_users, $mid )
	{
		$cc_array = array();
		$final    = array();
		
		$cc_array = $this->get_cc_array( $cc_users );
		
		foreach( $cc_array as $id => $data )
		{
			if ( $id == $mid )
			{
				continue;
			}
			
			$final[] = $this->ipsclass->make_profile_link( $data['members_display_name'], $data['id'] );
		}
		
		return implode( ", ", $final );
	
	}
	
	/*-------------------------------------------------------------------------*/
	// PROCESS CC
	/*-------------------------------------------------------------------------*/
	
	function get_cc_array($cc_users)
	{
		$cc_array = array();
 		
		$cc_users = strtolower(str_replace( '|', '&#124;', $cc_users) );

		if ( $cc_users )
		{
			//-----------------------------------------
			// Sort out the array
			//-----------------------------------------
			
			$cc_users = str_replace(  "<br />", "<br>", $cc_users );
			$cc_users = str_replace(  "<br><br>", "<br>" , trim($cc_users) );
			$cc_users = preg_replace( "/^(<br>){1}/", "" , $cc_users );
			$cc_users = preg_replace( "/(<br>){1}$/", "" , $cc_users );
			$cc_users = preg_replace( "/<br>\s+/",  ","  , $cc_users );
			
			$temp_array = explode( "<br>", $cc_users );
			
			//-----------------------------------------
			// Make SQL'able
			//-----------------------------------------
			
			if ( is_array($temp_array) and count($temp_array) > 0 )
			{
				$new_array = array();
				
				foreach( $temp_array as $name )
				{
					$name  = "'".trim(strtolower($name))."'";
					
					if (in_array( $name, $new_array ) )
					{
						continue;
					}
					
					$new_array[] = $name;
				}
			}
			
			//-----------------------------------------
			// SQL it
			//-----------------------------------------
			
			if ( is_array($new_array) and count($new_array) > 0 )
			{
				$array_count = count($new_array);
				
				$this->ipsclass->DB->cache_add_query( 'msg_get_cc_users', array( 'name_array' => $new_array ) );
				$this->ipsclass->DB->simple_exec();
				
				while( $r = $this->ipsclass->DB->fetch_row() )
				{
					$cc_array[$r['id']] = $r;
				}
			}
		}
		
		return $cc_array;
	}
			

	/*-------------------------------------------------------------------------*/
	// PROCESS CC
	/*-------------------------------------------------------------------------*/
	
	function _process_cc()
	{
		$this->can_mass_pm = 1;
 		$cc_array = array();
 		
		$this->cc_users = strtolower(str_replace( '|', '&#124;', $this->cc_users) );
		
		if (isset($this->cc_users) and $this->cc_users != "")
		{
			//-----------------------------------------
			// Sort out the array
			//-----------------------------------------
			
			$this->cc_users = str_replace(  "<br>", "<br />" , trim($this->cc_users) );
			$this->cc_users = str_replace(  "<br /><br />", "<br />" , trim($this->cc_users) );
			$this->cc_users = preg_replace( "#^(<br />){1}#", "" , $this->cc_users );
			$this->cc_users = preg_replace( "#(<br />){1}$#", "" , $this->cc_users );
			$this->cc_users = preg_replace( "#<br />\s+#",  ","  , $this->cc_users );
			
			$temp_array = explode( "<br />", $this->cc_users );
			
			//-----------------------------------------
			// Make SQL'able
			//-----------------------------------------
			
			if ( is_array($temp_array) and count($temp_array) > 0 )
			{
				$new_array = array();
				
				foreach( $temp_array as $name )
				{
					$name  = "'".trim(strtolower($name))."'";
					
					if (in_array( $name, $new_array ) )
					{
						continue;
					}
					
					$new_array[] = $name;
				}
			}
			
			//-----------------------------------------
			// SQL it
			//-----------------------------------------
			
			if ( is_array($new_array) and count($new_array) > 0 )
			{
				$array_count = count($new_array);
				
				$this->ipsclass->DB->cache_add_query( 'msg_get_cc_users', array( 'name_array' => $new_array ) );
				$this->ipsclass->DB->simple_exec();
						   
				if ( ! $this->ipsclass->DB->get_num_rows() )
				{
					$this->error = $this->ipsclass->lang['pme_no_cc_user'];
					return;
				}
				else
				{
					while( $r = $this->ipsclass->DB->fetch_row() )
					{
						$cc_array[$r['id']] = $r;
					}
					
					//-----------------------------------------
					
					if ( $this->force_pm != 1 )
					{
						if ( count($cc_array) > $this->ipsclass->member['g_max_mass_pm'])
						{
							$this->ipsclass->input['MID'] = $this->send_to_member['id'];
							$this->error = $this->ipsclass->lang['pme_too_many'];
							return;
						}
					}
					
					//-----------------------------------------
					// Names exist?
					//-----------------------------------------
					
					$cc_error = "";
					
					if ( count($cc_array) != $array_count )
					{
						foreach( $new_array as $n )
						{
							$seen = 0;
							
							foreach( $cc_array as $cc_user )
							{
								$tmp = "'".strtolower($cc_user['members_display_name'])."'";
								
								if ($tmp == $n)
								{
									$seen = 1;
								}
							}
							
							if ($seen != 1)
							{
								$cc_error .= "<br>".sprintf( $this->ipsclass->lang['pme_failed_nomem'], $n, $n );
							}
						}
					}
					
					if ($cc_error != "")
					{
						$this->ipsclass->input['MID'] = $this->send_to_member['id'];
						$this->error = $cc_error;
						return;
					}
					
					//-----------------------------------------
					// Can use PM system?
					//-----------------------------------------
					
					$cc_error   = "";
					$cc_id_array = array();
				
					foreach($cc_array as $cc_user)
					{
						if ( $cc_user['g_use_pm'] != 1 OR $cc_user['members_disable_pm'] )
						{
							$cc_error .= "<br>".sprintf( $this->ipsclass->lang['pme_failed_nopm'], $cc_user['members_display_name'], $cc_user['members_display_name'] );
						}
						
						$cc_user = $this->_get_real_allowance($cc_user);
						
						if ($cc_user['g_max_messages'] > 0 and ($cc_user['msg_total'] + 1 > $cc_user['g_max_messages']) )
						{
					 		if ( $this->ipsclass->vars['override_inbox_full'] )
					 		{
						 		$override = array();
						 		$override = explode( ",", $this->ipsclass->vars['override_inbox_full'] );
						 		
						 		$do_override = 0;
						 		
						 		$my_groups = array( $this->ipsclass->member['mgroup'] );
						 		
						 		if( $this->ipsclass->member['mgroup_others'] )
						 		{
							 		$my_other_groups = explode( ",", $this->ipsclass->member['mgroup_others'] );
							 		$my_groups = array_merge( $my_groups, $my_other_groups );
						 		}
						 		
						 		foreach( $my_groups as $member_group )
						 		{
							 		if( in_array( $member_group, $override ) )
							 		{
								 		$do_override = 1;
							 		}
							 	}
							 	
							 	if ( $do_override == 0 )
							 	{
								 	$cc_error .= "<br>".sprintf( $this->ipsclass->lang['pme_failed_maxed'], $cc_user['members_display_name'], $cc_user['members_display_name'] );
							 	}
					 		}
					 		else
					 		{
								$cc_error .= "<br>".sprintf( $this->ipsclass->lang['pme_failed_maxed'], $cc_user['members_display_name'], $cc_user['members_display_name'] );
							}							
						}
						
						$cc_id_array[] = $cc_user['id'];
					}
					
					if ( $this->force_pm != 1 )
					{
						if ($cc_error != "")
						{
							$this->ipsclass->input['MID'] = $this->send_to_member['id'];
							$this->error = $cc_error;
							return;
						}
					}
					
					//-----------------------------------------
					// Check the block list..
					//-----------------------------------------
					
					$this->ipsclass->DB->cache_add_query( 'msg_get_cc_blocked', array( 'mid' => $this->from_member['id'], 'cc_array' => $cc_id_array ) );
					$this->ipsclass->DB->simple_exec();
					
					while ( $c = $this->ipsclass->DB->fetch_row() )
					{
						if ($c['allow_msg'] != 1)
						{
					 		if( $this->ipsclass->vars['unblockable_pm_groups'] )
					 		{
						 		$unblockable = array();
						 		$unblockable = explode( ",", $this->ipsclass->vars['unblockable_pm_groups'] );
						 		
						 		$do_override = 0;
						 		
						 		$my_groups = array( $this->ipsclass->member['mgroup'] );
						 		
						 		if( $this->ipsclass->member['mgroup_others'] )
						 		{
							 		$my_other_groups = explode( ",", $this->ipsclass->member['mgroup_others'] );
							 		$my_groups = array_merge( $my_groups, $my_other_groups );
						 		}
						 		
						 		foreach( $my_groups as $member_group )
						 		{
							 		if( in_array( $member_group, $unblockable ) )
							 		{
								 		$do_override = 1;
							 		}
							 	}
							 	
							 	if ( $do_override == 0 )
							 	{
								 	$cc_error .= "<br>".sprintf( $this->ipsclass->lang['pme_failed_block'], $c['name'], $c['name'] );
							 	}
					 		}
					 		else
					 		{ 
						 		$cc_error .= "<br>".sprintf( $this->ipsclass->lang['pme_failed_block'], $c['name'], $c['name'] );
							}								
						}
					}
					
					if ( $this->force_pm != 1 )
					{
						if ($cc_error != "")
						{
							$this->ipsclass->input['MID'] = $this->send_to_member['id'];
							$this->error = $cc_error;
							return;
						}
					}
				}
			}
		}
		
		return $cc_array;	
	}
	
	/*-------------------------------------------------------------------------*/
	// SAVE stuff
	/*-------------------------------------------------------------------------*/
	
	function _process_save_only()
 	{
		if ( $this->save_only )
 		{
 		
			$raw = array( 
						  'msg_date'	      => time(),
						  'msg_post'          => $this->ipsclass->remove_tags($this->msg_post),
						  'msg_cc_users'      => $this->cc_users,
						  'msg_sent_to_count' => 1,
						  'msg_post_key'      => $this->class->post_key,
						  'msg_author_id'     => $this->from_member['id']
						);
			
			$saved = 0;
			
			if ( $this->orig_id )
			{
				//-----------------------------------------
				// We have an OID which means that this message
				// is already from the unsent folder, lets check that
				// and if true, update rather than create a new unsent
				// row
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 'mt_id, mt_msg_id', 'from' => 'message_topics', 'where' => "mt_id=".$this->orig_id." AND mt_owner_id=".$this->from_member['id']." AND mt_vid_folder='unsent'" ) );
				$this->ipsclass->DB->simple_exec();
				
				if ( $omsg = $this->ipsclass->DB->fetch_row() )
				{
					$saved = 1;
					
					$this->ipsclass->DB->do_update( 'message_text', $raw, "msg_id=".$omsg['mt_msg_id'] );
					
					//-----------------------------------------
					// Make attachments permanent
					//-----------------------------------------
					
					$no_attachments = $this->postlib->pf_make_attachments_permanent( $this->class->post_key, "", "", $omsg['mt_msg_id'] );
					
					$this->ipsclass->DB->simple_construct( array( 'update' => 'message_topics', 'set' => "mt_hasattach=$no_attachments, mt_date=".time(), 'where' => 'mt_owner_id='.$this->ipsclass->member['id'].' AND mt_id='.$omsg['mt_id'] ) );
					$this->ipsclass->DB->simple_exec();
				}
			}
			
			if ($saved == 0)
			{
				$this->ipsclass->DB->do_insert( 'message_text', $raw);
				
				$msg_id = $this->ipsclass->DB->get_insert_id();
				
				//-----------------------------------------
				// Make attachments permanent
				//-----------------------------------------
				
				$no_attachments = $this->postlib->pf_make_attachments_permanent( $this->class->post_key, "", "", $msg_id );
				
				$this->ipsclass->DB->do_insert( 'message_topics', array(
														 'mt_msg_id'     => $msg_id,
														 'mt_date'       => time(),
														 'mt_title'      => $this->msg_title,
														 'mt_from_id'    => $this->from_member['id'],
														 'mt_to_id'      => $this->send_to_member['id'],
														 'mt_vid_folder' => 'unsent',
														 'mt_hide_cc'    => intval($this->ipsclass->input['mt_hide_cc']),
														 'mt_tracking'   => intval($this->ipsclass->input['add_tracking']),
														 'mt_addtosent'  => $this->ipsclass->input['add_sent'] == 'yes' ? 1 : 0,
														 'mt_hasattach'  => intval($no_attachments),
														 'mt_owner_id'   => $this->from_member['id'],
											   )      );
			}
			
			$this->redirect_url  = "&act=Msg&CODE=01";
			$this->redirect_lang = $this->ipsclass->lang['pms_redirect'];
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Return count of current VDIR (quickly)
 	/*-------------------------------------------------------------------------*/
 	
 	function _get_dir_count( $vdir, $vid )
 	{
 		preg_match( "#(?:^|\|)$vid:.+?;(\d+)(?:\||$)#i", $vdir, $match );
			
		return intval($match[1]);
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Get real allowance based on multi-groups
 	/*-------------------------------------------------------------------------*/
 	
 	function _get_real_allowance( $member )
 	{
		$groups_id = explode( ',', $member['mgroup_others'] );
 		
 		if ( count( $groups_id ) )
		{
			foreach( $groups_id as $pid )
			{
				if ( !isset($this->ipsclass->cache['group_cache'][ $pid ]['g_id']) OR !$this->ipsclass->cache['group_cache'][ $pid ]['g_id'] )
				{
					continue;
				}
				
				if ( $this->ipsclass->cache['group_cache'][ $pid ]['g_max_messages'] > $member['g_max_messages'] )
				{
					$member['g_max_messages'] = $this->ipsclass->cache['group_cache'][ $pid ]['g_max_messages'];
				}
			}
		}
		
		return $member;
 	}


}

?>