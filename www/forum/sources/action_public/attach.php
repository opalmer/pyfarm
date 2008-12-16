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
|   > $Date: 2006-05-05 21:58:19 +0100 (Fri, 05 May 2006) $
|   > $Revision: 246 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Attachment Handler module
|   > Module written by Matt Mecham
|   > Date started: 10th March 2002
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

class attach {

	/*-------------------------------------------------------------------------*/
	//
	// AUTO RUN
	//
	/*-------------------------------------------------------------------------*/
	
    function auto_run()
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$this->ipsclass->input['id']  = intval($this->ipsclass->input['id']);
        $this->ipsclass->input['tid'] = intval($this->ipsclass->input['tid']);
        
		//-----------------------------------------
		// Get the attach class
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
		$this->class_attach           =  new class_attach();
		$this->class_attach->ipsclass =& $this->ipsclass;
		
        //-----------------------------------------
		// Got attachment types?
		//-----------------------------------------
		
		if ( ! isset( $this->ipsclass->cache['attachtypes'] ) OR ! is_array( $this->ipsclass->cache['attachtypes'] ) )
		{
			$this->ipsclass->cache['attachtypes'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img',
														  'from'   => 'attachments_type',
														  'where'  => "atype_photo=1 OR atype_post=1" ) );
			$this->ipsclass->DB->simple_exec();
	
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
			}
		}
		
		//-----------------------------------------
		// What to do..
		//-----------------------------------------
		
        switch( $this->ipsclass->input['code'] )
        {
			case 'attach_upload_show':
				$this->attach_upload_show();
				break;
			case 'attach_upload_process':
				$this->attach_upload_process();
				break;
			case 'attach_upload_remove':
				$this->attach_upload_remove();
				break;
        	case 'showtopic':
        		$this->show_topic_attachments();
        		break;
        	default:
        		$this->show_post_attachment();
        		break;
        }
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove an upload
	/*-------------------------------------------------------------------------*/
	
	function attach_upload_remove()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$attach_post_key      = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['attach_post_key'] ) );
		$attach_rel_module    = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['attach_rel_module'] ) );
		$attach_rel_id        = intval( $this->ipsclass->input['attach_rel_id'] );
		$attach_id            = intval( $this->ipsclass->input['attach_id'] );
			
		//-----------------------------------------
		// INIT module
		//-----------------------------------------
		
		$this->class_attach->type            = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->attach_rel_id   = $attach_rel_id;
		$this->class_attach->attach_id       = $attach_id;
		$this->class_attach->init();
		
		//-----------------------------------------
		// Process upload
		//-----------------------------------------
		
		$this->class_attach->remove_attachment();
		
		//-----------------------------------------
		// Show form again
		//-----------------------------------------
		
		$this->attach_upload_show("attach_removed");
	}
	
	/*-------------------------------------------------------------------------*/
	// Perform the actual upload
	/*-------------------------------------------------------------------------*/
	
	function attach_upload_process()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$attach_post_key      = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['attach_post_key'] ) );
		$attach_rel_module    = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['attach_rel_module'] ) );
		$attach_rel_id        = intval( $this->ipsclass->input['attach_rel_id'] );
		$attach_current_items = '';
		
		//-----------------------------------------
		// INIT module
		//-----------------------------------------
		
		$this->class_attach->type            = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->attach_rel_id   = $attach_rel_id;
		$this->class_attach->init();
		
		//-----------------------------------------
		// Process upload
		//-----------------------------------------
		
		$this->class_attach->process_upload();
	
		//-----------------------------------------
		// Got an error?
		//-----------------------------------------
		
		if ( $this->class_attach->error )
		{
			$this->attach_upload_show( $this->class_attach->error, 1 );
			return;
		}
		else
		{
			$this->attach_upload_show( 'upload_ok', 0 );
			return;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show the attach upload field
	/*-------------------------------------------------------------------------*/
	
	function attach_upload_show( $msg="ready", $is_error=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$attach_post_key       = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['attach_post_key'] ) );
		$attach_rel_module     = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['attach_rel_module'] ) );
		$attach_rel_id         = intval( $this->ipsclass->input['attach_rel_id'] );
		$attach_current_items  = '';
		$extra_upload_form_url = '';
		
		//-----------------------------------------
		// Get extra form fields
		//-----------------------------------------
		
		foreach( $this->ipsclass->input as $k => $v )
		{
			if ( preg_match( "#^--ff--#", $k ) )
			{
				$extra_upload_form_url .= '&amp;' . str_replace( '--ff--', '', $k ) . '='.$v;
				$extra_upload_form_url .= '&amp;' . $k . '='.$v;
			}
		}
					
		//-----------------------------------------
		// INIT module
		//-----------------------------------------
		
		$this->class_attach->type = $attach_rel_module;
		$this->class_attach->attach_post_key = $attach_post_key;
		$this->class_attach->init();
		$this->class_attach->get_upload_form_settings();
		
		//-----------------------------------------
		// Load language and skin
		//-----------------------------------------
		
		$this->ipsclass->load_template( 'skin_post' );
		$this->ipsclass->load_language( 'lang_post' );
		
		//-----------------------------------------
		// Generate current items...
		//-----------------------------------------

		$_more = ( $attach_rel_id ) ? ' OR c.attach_rel_id='.$attach_rel_id : '';
			
		$this->ipsclass->DB->build_query( array( 'select'  => 'c.*',
												 'from'    => array( 'attachments' => 'c' ),
												 'where'    => "c.attach_rel_module='".$attach_rel_module."' AND c.attach_post_key='".$attach_post_key."'".$_more,
												 'add_join' => array( 0 => array(
																				  'select' => 't.*',
																				  'from'   => array( 'attachments_type' => 't' ),
																				  'where'  => 't.atype_extension=c.attach_ext',
																				  'type'   => 'left' ) )
												
										)      );
										
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$attach_current_items .= $this->ipsclass->compiled_templates['skin_post']->attach_current_item( $row['attach_id']  ,
																	  										$row['attach_file'],
																											$this->ipsclass->size_format( $row['attach_filesize'] ),
																											$row['atype_img'] );
		}
		
		//-----------------------------------------
		// Show..
		//-----------------------------------------
		
		$html = $this->ipsclass->compiled_templates['skin_post']->attach_wrapper( $attach_current_items, $attach_rel_module, $attach_rel_id, $attach_post_key, $this->class_attach->attach_stats, $msg, $is_error, $extra_upload_form_url );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->ipsclass->skin['_macros'][] = array( 'macro_value' => '__body_extra__', 'macro_replace' => " style='background:transparent;'" );
		$this->ipsclass->print->pop_up_window( "", $html );
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// SHOW TOPIC ATTACHMENTS ( MULTIPLE )
	//
	/*-------------------------------------------------------------------------*/
	
	function show_topic_attachments()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topic_id = intval( $this->ipsclass->input['tid'] );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $topic_id )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
        }
        
        //-----------------------------------------
        // get topic..
        //-----------------------------------------
        
        $topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$topic_id ) );
        
        if ( ! $topic['topic_hasattach'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
        }
        
        //-----------------------------------------
        // Check forum..
        //-----------------------------------------
        
        if ( ! $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}
		
		//-----------------------------------------
		// Get forum skin and lang
		//-----------------------------------------
		
		$this->ipsclass->load_language('lang_forum');
		$this->ipsclass->load_language('lang_topic');
		
        $this->ipsclass->load_template('skin_forum');
		
		//-----------------------------------------
		// aight.....
		//-----------------------------------------
		
		$_queued = ( ! $this->ipsclass->can_queue_posts( $topic['forum_id'] ) ) ? ' AND p.queued=0' : '';
		
		$this->output .= $this->ipsclass->compiled_templates['skin_forum']->forums_attachments_top($topic['title']);
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.pid, p.topic_id',
												 'from'     => array( 'posts' => 'p' ),
												 'where'    => 'p.topic_id='.$topic_id . $_queued,
												 'add_join' => array( 0 => array(
																				  'select' => 'a.*',
																				  'from'   => array( 'attachments' => 'a' ),
																				  'where'  => "a.attach_rel_id=p.pid AND a.attach_rel_module='post'",
																				  'type'   => 'left' ) )
												
										)      );
										
		$this->ipsclass->DB->exec_query();
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ]['read_perms']) != TRUE )
			{
				continue;
			}
			
			if ( ! $row['attach_id'] )
			{
				continue;
			}
			
			$row['image']       = $this->ipsclass->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'];
			
			$row['short_name']  = $this->ipsclass->txt_truncate( $row['attach_file'], 30 );
															  
			$row['attach_date'] = $this->ipsclass->get_date( $row['attach_date'], 'SHORT' );
			
			$row['real_size']   = $this->ipsclass->size_format( $row['attach_filesize'] );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_forum']->forums_attachments_row( $row );
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_forum']->forums_attachments_bottom();
		
		$this->ipsclass->print->pop_up_window($this->ipsclass->lang['attach_title'], $this->output);
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// SHOW POST ATTACHMENT ( SINGLE )
	//
	/*-------------------------------------------------------------------------*/
	
	function show_post_attachment()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$attach_id = intval( $this->ipsclass->input['id'] );
		
		//-----------------------------------------
		// INIT module
		//-----------------------------------------
		
		$this->class_attach->init();
		
		//-----------------------------------------
		// Process upload
		//-----------------------------------------
		
		$this->class_attach->show_attachment( $attach_id );
		
		exit();
		
		/*if ( ! $this->ipsclass->input['id'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
        }
        
        //-----------------------------------------
        // get attachment
        //-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_id=".intval($this->ipsclass->input['id']) ) );
        $this->ipsclass->DB->simple_exec();
        
        if ( ! $attach = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
		}
		
        //-----------------------------------------
        // Handle post attachments.
        //-----------------------------------------
        
        if ( $this->ipsclass->input['type'] == 'post' )
        {
        	//-----------------------------------------
        	// TheWalrus inspired fix for previewing
        	// the post and clicking the attachment...
        	//-----------------------------------------
        		
        	if ( $attach['attach_pid'] == 0 AND $attach['attach_member_id'] == $this->ipsclass->member['id'] )
        	{
        		# We're OK (Further checking, maybe post key?
        	}
        	else
        	{
        		//-----------------------------------------
        		// Get post thingy majiggy to check perms
        		//-----------------------------------------
        	
        		$this->ipsclass->DB->cache_add_query( 'attach_get_perms', array( 'apid' => $attach['attach_pid'] ) );
        		$this->ipsclass->DB->cache_exec_query();
        	
				if ( ! $post = $this->ipsclass->DB->fetch_row() )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
				}
				
				if ( ! $this->ipsclass->forums->forum_by_id[ $post['forum_id'] ] )
				{
					//-----------------------------------------
					// TheWalrus inspired fix for previewing
					// the post and clicking the attachment...
					//-----------------------------------------
					
					if ( $attach['attach_pid'] == 0 AND $attach['attach_member_id'] == $this->ipsclass->member['id'] )
					{
						# We're ok.
					}
					else
					{
						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
					}
				}

				if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $post['forum_id'] ]['read_perms']) == FALSE )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
				}

		        if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $post['forum_id'] ]['download_perms']) == FALSE )
		        {
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
				}
			}
        }
        else if ( $this->ipsclass->input['type'] == 'msg' and $attach['attach_msg'] )
        {
        	$this->ipsclass->DB->simple_construct( array( 'select' => 'mt_id, mt_owner_id', 'from' => 'message_topics', 'where' => 'mt_owner_id='.$this->ipsclass->member['id'].' AND mt_msg_id='.$attach['attach_msg'] ) );
        	$this->ipsclass->DB->simple_exec();
        	
        	if ( ! $post = $this->ipsclass->DB->fetch_row() )
        	{
        		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
			}
			
        }
        else
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
        }
        
        //-----------------------------------------
        // Show attachment
        //-----------------------------------------
        
        $file = $this->ipsclass->vars['upload_dir']."/".$attach['attach_location'];
        	
		if ( file_exists( $file ) and ( $this->ipsclass->cache['attachtypes'][ $attach['attach_ext'] ]['atype_mimetype'] != "" ) )
		{
			//-----------------------------------------
			// Update the "hits"..
			//-----------------------------------------
			$this->ipsclass->DB->simple_construct( array( 'update' => 'attachments', 'set' =>"attach_hits=attach_hits+1", 'where' => "attach_id=".$this->ipsclass->input['id'] ) );
			$this->ipsclass->DB->simple_exec();
			//print $attach['attach_hits'];
			//-----------------------------------------
			// If this is a TXT / HTML file, force an
			// odd extension to prevent IE from opening
			// it inline.
			//-----------------------------------------
			
			$file_extension = preg_replace( "#^.*\.(.+?)$#s", "\\1", $attach['attach_file'] );
			$safe_array     = array( 'txt', 'html', 'htm' );
			
			if ( in_array( strtolower($file_extension), $safe_array ) )
			{
				//$attach['attach_file'] .= '-rename';
			}
			
			//-----------------------------------------
			// Set up the headers..
			//-----------------------------------------
			
			header( "Content-Type: ".$this->ipsclass->cache['attachtypes'][ $attach['attach_ext'] ]['atype_mimetype'] );
			header( "Content-Disposition: inline; filename=\"".$attach['attach_file']."\"" );
			header( "Content-Length: ".(string)(filesize( $file ) ) );
			
			//-----------------------------------------
			// Open and display the file..
			//-----------------------------------------
			
			$fh = fopen( $file, 'rb' );  // <{%dyn.down.var.md5p1%}>, Set binary for Win even if it's an ascii file, it won't hurt.
			fpassthru( $fh );
			fclose( $fh );
			exit();
		}
		else
		{
			//-----------------------------------------
			// File does not exist..
			//-----------------------------------------
			
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
		}*/
        
    }
        
       
}

?>