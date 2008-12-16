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
|   > Messenger functions
|   > Module written by Matt Mecham
|   > Date started: 26th February 2002
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

class messenger
{
    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
    var $email      = "";
    
    var $msg_stats  = array();
    var $prefs      = "";
    
    var $member     = array();
    var $m_group    = array();
    
    var $to_mem     = array();
    
    var $jump_html  = "";
    var $show_form	= 0;
    
    var $vid        = "in";
    var $mem_groups = array();
    var $mem_titles = array();
    
    var $topiclib   = "";
    var $postlib    = "";
    var $parser     = "";
    
    var $cp_html    = "";
    var $edit_saved = "";
    
    /*-------------------------------------------------------------------------*/
    // Auto-run
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
		//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_msg');
    	$this->ipsclass->load_template('skin_msg');
    	
    	//-----------------------------------------
    	// Load classes
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/lib/func_msg.php' );
 		
 		$this->msglib = new func_msg();
    	$this->msglib->ipsclass =& $this->ipsclass;
    	
    	//-----------------------------------------
    	// Set up defaults
    	//-----------------------------------------
    	
    	$this->base_url        = $this->ipsclass->base_url;
    	$this->base_url_nosess = "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}";
    	
    	//-----------------------------------------
    	// Check viewing permissions, etc
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['g_use_pm'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_use_messenger' ) );
		}
		
		if ( $this->ipsclass->member['members_disable_pm'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_use_messenger' ) );
		}
		
		if ( ! $this->ipsclass->member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
		
		//-----------------------------------------
    	// Print menu
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output( $this->msglib->ucp_generate_menu() );
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case '01':
    			$this->msg_list();
    			break;
    		case '02':
    			$this->contact();
    			break;
    		case '03':
    			$this->view_msg();
    			break;
    		case '04';
    			$this->send();
    			break;
    		case '05':
    			$this->delete();
    			break;
    		case '06':
    			$this->multi_act();
    			break;
    		case '07':
    			$this->prefs();
    			break;
    		case '08':
    			$this->do_prefs();
    			break;
    		case '09':
    			$this->add_member();
    			break;
    		case '10':
    			$this->del_member();
    			break;
    		case '11':
    			$this->edit_member();
    			break;
    		case '12':
    			$this->do_edit();
    			break;
    		case '14':
    			$this->archive();
    			break;
    		case '15':
    			$this->do_archive();
    			break;
    			
    		case '20':
    			$this->view_saved();
    			break;
    			
    		case '21':
    			$this->edit_saved = 1;
    			$this->send();
    			break;
    			
    		case '30':
    			$this->show_tracking();
    			break;
    			
    		case '31':
    			$this->end_tracking();
    			break;
    			
    		case '32':
    			$this->del_tracked();
    			break;
    			
    		case 'delete':
    			$this->start_empty_folders();
    			break;
    		case 'dofolderdelete':
    			$this->end_empty_folders();
    			break;
    			
    		default:
    			$this->msg_list();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$fj = $this->ipsclass->build_forum_jump();
		$fj = str_replace( '#Forum Jump#', $this->ipsclass->lang['forum_jump'], $fj);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->CP_end();
		
		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->forum_jump($fj);
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'NAV' => $this->nav ) );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Empty PM folders:
 	//
 	// Interface for removing PM's on a folder by folder basis
 	/*-------------------------------------------------------------------------*/
 	
 	function start_empty_folders()
 	{
		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->empty_folder_header();
 		
 		//-----------------------------------------
 		// Get the PM count - 1 query?
 		//-----------------------------------------
 		
 		$count = array( 'unsent' => 0 );
 		$names = array( 'unsent' => $this->ipsclass->lang['fd_unsent'] );
 		
 		foreach( $this->ipsclass->member['dir_data'] as $v )
 		{
 			$count[ $v['id'] ] = 0;
 			$names[ $v['id'] ] = $v['real'];
 		}
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'mt_id, mt_vid_folder, mt_msg_id', 'from' => 'message_topics', 'where' => 'mt_owner_id='.$this->ipsclass->member['id'] ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		while( $r = $this->ipsclass->DB->fetch_row() )
 		{
 			if ( $r['mt_vid_folder'] == "" )
 			{
 				$count['in']++;
 			}
 			else
 			{
 				$count[ $r['mt_vid_folder'] ]++;
 			}
 		}
 		
 		foreach( $names as $vid => $name )
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->empty_folder_row( $name, $vid, $count[$vid] );
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->empty_folder_save_unread();
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->empty_folder_footer();
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// DELETE emptied PMS
 	/*-------------------------------------------------------------------------*/
 	
 	function end_empty_folders()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$md5check = $this->ipsclass->txt_md5_clean( $this->ipsclass->input['md5check'] );
		$names	  = array( 'unsent' => $this->ipsclass->lang['fd_unsent'] );
 		$ids   	  = array();
 		$qe    	  = "";
 		
		//-----------------------------------------
		// Check MD5 auth key
		//-----------------------------------------
		
		if ( $md5check != $this->ipsclass->md5_check )
		{
			$this->ipsclass->Error( array(  'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}

		//-----------------------------------------
		// Do dir data dear dah-dag
		//-----------------------------------------

 		foreach( $this->ipsclass->member['dir_data'] as $v )
 		{
 			$names[ $v['id'] ] = $v['real'];
 		}
 		
 		//-----------------------------------------
 		// Did we check any boxes?
 		//-----------------------------------------
 		
 		foreach( $names as $vid => $name )
 		{
 			if ( isset($this->ipsclass->input['its_'.$vid]) AND $this->ipsclass->input['its_'.$vid] == 1 )
 			{
 				$ids[] = $vid;
 			}
 		}
 		
 		if ( count($ids) < 1 )
 		{
 			$this->ipsclass->Error( array(  'LEVEL' => 1, 'MSG' => 'fd_noneselected' ) );
 		}
 		
 		//-----------------------------------------
 		// Delete em!
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->input['save_unread'] )
 		{
 			$qe = ' AND mt_read=1';
 		}
 		
 		$mtids = array();
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'mt_id', 'from' => 'message_topics', 'where' => 'mt_owner_id='.$this->ipsclass->member['id']." AND mt_vid_folder IN('".implode("','", $ids)."')".$qe ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		while( $d = $this->ipsclass->DB->fetch_row() )
 		{
 			$mtids[] = $d['mt_id'];
 		}
 		
 		$this->msglib->delete_messages( $mtids, $this->ipsclass->member['id'] );
 		
 		$this->ipsclass->DB->simple_construct( array ( 'select' => 'COUNT(*) as msg_total', 'from' => 'message_topics', 'where' => "mt_owner_id=".$this->ipsclass->member['id']." AND mt_vid_folder <> 'unsent'" ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		$total = $this->ipsclass->DB->fetch_row();
 		
 		$total['msg_total'] = intval($total['msg_total']);
 		
 		$this->ipsclass->DB->simple_construct( array ( 'update'=> 'members', 'set' => "msg_total=".$total['msg_total'], 'where' => "id=".$this->ipsclass->member['id'] ) ) ;
 		$this->ipsclass->DB->simple_exec();
 		
 		//-----------------------------------------
 		// Update directory counts
 		//-----------------------------------------
 		
 		$current_dirs = $this->ipsclass->member['vdirs'];
 		 		
 		foreach( $ids as $v )
 		{
	 		$foldertotal['total'] = 0;
	 		
	 		$this->ipsclass->DB->simple_construct( array ( 'select' => 'COUNT(*) as total', 'from' => 'message_topics', 'where' => "mt_owner_id=".$this->ipsclass->member['id']." AND mt_vid_folder = '{$v}'" ) );
	 		$this->ipsclass->DB->simple_exec();
	 		
	 		$foldertotal = $this->ipsclass->DB->fetch_row();
	 		
	 		$current_dirs = $this->msglib->rebuild_dir_count( $this->ipsclass->member['id'],
															  $current_dirs,
															  $v,
															  $foldertotal['total'],
															  'save'
															);
		}
		
 		$this->ipsclass->boink_it($this->ipsclass->base_url."act=Msg&CODE=delete");
 	}
 	
 	
 	/*-------------------------------------------------------------------------*/
 	// ARCHIVE:
 	//
 	// Allows a user to archive and email a HTML file
 	/*-------------------------------------------------------------------------*/
 	
 	function archive() 
 	{
		$this->msglib->jump_html = str_replace("<!--EXTRA-->", "<option value='all'>".$this->ipsclass->lang['all_folders']."</option>", $this->msglib->jump_html );
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->archive_form( $this->msglib->jump_html );
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Process archive
 	/*-------------------------------------------------------------------------*/
 	
 	function do_archive()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
 		
 		$folder_query  = "";
 		$msg_ids       = array();
 		$older_newer   = '>';
 		$type          = 'html';
 		$file_name     = "pm_archive.html";
 		$ctype         = "text/html";
 		
 		//-----------------------------------------
 		// Get email library
 		//-----------------------------------------
 		
		require_once( ROOT_PATH."sources/classes/class_email.php" );
		$this->email = new emailer();
 		$this->email->ipsclass =& $this->ipsclass;
 		$this->email->email_init();
 		
 		//-----------------------------------------
 		// Did we specify a folder, or choose all?
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->input['oldnew'] == 'older' )
 		{
 			$older_newer = '<';
 		}
 		
 		if ($this->ipsclass->input['VID'] != 'all')
 		{
 			$folder_query = " AND mt.mt_vid_folder='".$this->ipsclass->input['VID']."'";
 		}
 		
 		if ( $this->ipsclass->input['dateline'] == 'all' )
 		{
 			$time_cut    = 0;
 			$older_newer = '>';
 		}
 		else
 		{
 			$time_cut = time() - ($this->ipsclass->input['dateline'] * 60 * 60 *24);
 		}
 		
 		//-----------------------------------------
 		// Check the input...
 		//-----------------------------------------
 		
 		$this->ipsclass->input['number'] = intval( $this->ipsclass->input['number'] );
 		
 		if ($this->ipsclass->input['number'] < 5)
 		{
 			$this->ipsclass->input['number'] = 5;
 		}
 		
 		if ($this->ipsclass->input['number'] > 50)
 		{
 			$this->ipsclass->input['number'] = 50;
 		}
 		
 		if ($this->ipsclass->input['type'] == 'xls')
 		{
 			$type      = 'xls';
 			$file_name = "xls_importable.txt";
 			$ctype     = "text/plain";
 		}
 		
 		$output = "";
 		
 		//-----------------------------------------
 		// Start the datafile..
 		//-----------------------------------------
 		
 		if ($type == 'html')
 		{
 			$output .= $this->ipsclass->compiled_templates['skin_msg']->archive_html_header();
 		}
 		
 		//-----------------------------------------
 		// Get the messages...
 		//-----------------------------------------
 		
 		//$this->ipsclass->DB->cache_add_query( 'msg_get_msg_archive', array( 'mid' => $this->ipsclass->member['id'], 'limit_b' => $this->ipsclass->input['number'], 'older_newer' => $older_newer, 'time_cut' => $time_cut, 'folder_query' => $folder_query ) );
 		//$this->ipsclass->DB->cache_exec_query();
 		
 		$this->ipsclass->DB->build_query( array(	'select'	=> 'mt.*',
 													'from'		=> array( 'message_topics' => 'mt' ),
 													'where'		=> "mt.mt_owner_id={$this->ipsclass->member['id']} AND mt.mt_date {$older_newer} {$time_cut} {$folder_query}",
 													'order'		=> 'mt.mt_date DESC',
 													'limit'		=> array( 0, $this->ipsclass->input['number'] ),
 													'add_join'	=> array(
 																		array(
 																				'select'	=> 'msg.msg_post',
 																				'from'		=> array( 'message_text' => 'msg' ),
 																				'where'		=> 'msg.msg_id=mt.mt_msg_id',
 																				'type'		=> 'left'
 																			),
 																		array(
 																				'select'	=> 'm.members_display_name, m.id',
 																				'from'		=> array( 'members' => 'm' ),
 																				'where'		=> 'm.id=mt.mt_from_id',
 																				'type'		=> 'left'
 																			), 
 																		array(
 																				'select'	=> 'mm.members_display_name as to_name',
 																				'from'		=> array( 'members' => 'mm' ),
 																				'where'		=> 'mm.id=mt.mt_to_id',
 																				'type'		=> 'left'
 																			),
 																		),
 										)		);
 		$this->ipsclass->DB->exec_query();
 		
 		//-----------------------------------------
 		// Repeat after me..
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			while ( $r = $this->ipsclass->DB->fetch_row() )
 			{
 				$info      = array();
 				$msg_ids[] = $r['mt_id'];
 				
 				$info['msg_date']    = $this->ipsclass->get_date( $r['mt_date'], 'LONG' );
 				$info['msg_title']   = $r['mt_title'];
 				
 				if( $r['mt_vid_folder'] == 'sent' )
 				{
 					$info['msg_sender']  = $r['to_name'];
				}
				else
				{
					$info['msg_sender']  = $r['members_display_name'];
				}	
		
				$info['msg_content'] = $r['msg_post'];
 				
 				if ($type == 'xls')
 				{
 					$output .= '"'.$this->strip_quotes($info['msg_title']).'","'.$this->strip_quotes($info['msg_date']).'","'.$this->strip_quotes($info['msg_sender']).'","'.$this->strip_quotes($info['msg_content']).'"'."\r";
 				}
 				else
 				{
 					if ( $r['mt_vid_folder'] == 'sent' )
 					{
 						$output .= $this->ipsclass->compiled_templates['skin_msg']->archive_html_entry_sent($info);
 					}
 					else
 					{
 						$output .= $this->ipsclass->compiled_templates['skin_msg']->archive_html_entry($info);
 					}
 				}
 			}
 			
 			if ($type == 'html')
			{
				$output .= $this->ipsclass->compiled_templates['skin_msg']->archive_html_footer();
			}
			
			$num_msg = count( $msg_ids );
			
			//-----------------------------------------
			// Delete?
			//-----------------------------------------
							
			if ($this->ipsclass->input['delete'] == 'yes')
			{
				$this->msglib->delete_messages( $msg_ids, $this->ipsclass->member['id'] );
				
				$this->ipsclass->DB->simple_construct( array ( 'select' => 'COUNT(*) as msg_total', 'from' => 'message_topics', 'where' => "mt_owner_id=".$this->ipsclass->member['id']." AND mt_vid_folder <> 'unsent'" ) );
				$this->ipsclass->DB->simple_exec();
				
				$total = $this->ipsclass->DB->fetch_row();
				
				$total['msg_total'] = intval($total['msg_total']);
				
				$this->ipsclass->DB->simple_construct( array ( 'update'=> 'members', 'set' => "msg_total=".$total['msg_total'], 'where' => "id=".$this->ipsclass->member['id'] ) ) ;
				$this->ipsclass->DB->simple_exec();
				
				if( $this->ipsclass->input['VID'] != 'all' )
				{
					$this->ipsclass->member['vdirs'] = $this->msglib->rebuild_dir_count( $this->ipsclass->member['id'], 
														$this->ipsclass->member['vdirs'], 
														$this->ipsclass->input['VID'], 
														($this->ipsclass->member['dir_data'][ $this->ipsclass->input['VID'] ]['count'] - $num_msg) > 0 ? ($this->ipsclass->member['dir_data'][ $this->ipsclass->input['VID'] ]['count'] - $num_msg) : 0,
														'save' 
													);
				}
				else
				{
			 		foreach( $this->ipsclass->member['dir_data'] as $k => $v )
			 		{
				 		$foldertotal['total'] = 0;
				 		
				 		$this->ipsclass->DB->simple_construct( array ( 'select' => 'COUNT(*) as total', 'from' => 'message_topics', 'where' => "mt_owner_id=".$this->ipsclass->member['id']." AND mt_vid_folder = '{$k}'" ) );
				 		$this->ipsclass->DB->simple_exec();
				 		
				 		$foldertotal = $this->ipsclass->DB->fetch_row();

				 		$updated_vdirs = $this->msglib->rebuild_dir_count( $this->ipsclass->member['id'],
																		  $this->ipsclass->member['vdirs'],
																		  $k,
																		  $foldertotal['total'],
																		  'save'
																		);
						$this->ipsclass->member['vdirs'] = $updated_vdirs;
					}
				}
													
			}
			
			//-----------------------------------------
			// Process & Print
			//-----------------------------------------
			
			$output = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $output );
			$output = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $output );
			
			$this->email->get_template("pm_archive");
			
			$this->email->build_message( array( 'NAME' => $this->ipsclass->member['members_display_name'] ) );
										
			$this->email->subject = $this->ipsclass->lang['arc_email_subject'];
			$this->email->to      = $this->ipsclass->member['email'];
			$this->email->add_attachment( $output, $file_name, $ctype );
			$this->email->send_mail();
			
			//-----------------------------------------
			// Done..
			//-----------------------------------------
			
			$this->ipsclass->lang['arc_complete'] = str_replace( "<#NUM#>", $num_msg, $this->ipsclass->lang['arc_complete'] );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->archive_complete();
 		
			$this->page_title = $this->ipsclass->lang['t_welcome'];
			$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
			
		}
		else
		{
			$this->ipsclass->Error( array(  'LEVEL' => 1, 'MSG' => 'no_archive_messages' ) );
		}
 	}

	/*-------------------------------------------------------------------------*/
	// Strip Quotes
	/*-------------------------------------------------------------------------*/
	
	function strip_quotes($text) {
 	
 		return str_replace( '"', '\\\"', $text );
 	}	
 	
 	/*-------------------------------------------------------------------------*/
 	// PREFS:
 	//
 	// Create/delete/edit messenger folders
 	/*-------------------------------------------------------------------------*/
 	
 	function prefs()
 	{
		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->prefs_header();
 		
 		$max = 1;
 		
 		foreach( $this->ipsclass->member['dir_data'] as $v )
 		{
 			$extra = "";
 			
 			//-----------------------------------------
 			// Can't remove IN and SENT dirs
 			//-----------------------------------------
 			
 			if ( $v['id'] == 'in' or $v['id'] == 'sent' )
 			{
 				$extra = "&nbsp;&nbsp;( ".$v['real']." - ".$this->ipsclass->lang['cannot_remove']." )";
 			}
 			
 			//-----------------------------------------
 			// Can't remove folders w/messages
 			//-----------------------------------------
 			
 			if ( $this->msglib->_get_dir_count( $this->ipsclass->member['vdirs'], $v['id'] ) )
 			{
 				$extra  = "&nbsp;&nbsp;( ".$v['real']." - ".$this->ipsclass->lang['cannot_remove']." )";
 				$extra .= "<input type='hidden' name=\"noremove[{$v['id']}]\" value='1' />";
 			}
 			
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->prefs_row( array( 'ID' => $v['id'], 'REAL' => $v['real'], 'EXTRA' => $extra ) );
 			
 			if ( stristr( $v['id'], 'dir_' ) )
 			{
 				$max = intval( str_replace( 'dir_', "", $v['id'] ) ) + 1;
 			}
 		}
 		
 		$count = $max + 1;
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->prefs_add_dirs();
 		
 		for ($i = $count; $i < $count + 3; $i++)
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->prefs_row( array( 'ID' => 'dir_'.$i, 'REAL' => '', 'EXTRA' => '' ) );
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->prefs_footer();
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 		
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SAVE FOLDERS
 	/*-------------------------------------------------------------------------*/
 	
 	function do_prefs()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['in']   = $this->ipsclass->txt_alphanumerical_clean( $_POST['in'], " " );
		$this->ipsclass->input['sent'] = $this->ipsclass->txt_alphanumerical_clean( $_POST['sent'], " " );
	
		//-----------------------------------------
 		// Check to ensure than we've not tried to
 		// remove the inbox and sent items directories.
 		//-----------------------------------------
 	
 		if ( ($this->ipsclass->input['sent'] == "") or ($this->ipsclass->input['in'] == "") )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cannot_remove_dir' ) );
 		}
 		
 		//-----------------------------------------
 		// Did we remove a box we're not allowed to?
 		//-----------------------------------------
 		
 		if ( is_array( $_POST['noremove'] ) )
 		{
 			foreach( $_POST['noremove'] as $key => $value )
 			{
 				if ( $value )
 				{
 					if ( ! $this->ipsclass->input[ $key ] )
 					{
 						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cannot_remove_dir' ) );
 					}
 				}
 			}
 		}
 		
 		$cur_dir = array();
 		
 		foreach( explode( "|", $this->ipsclass->member['vdirs'] ) as $dir )
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
    		
    		$cur_dir[$id] = intval($count);
    	}
 		
 		$v_dir = 'in:'.$this->ipsclass->input['in'].';'.intval($cur_dir['in']).'|sent:'.$this->ipsclass->input['sent'].';'.intval($cur_dir['sent']);
 		
 		//-----------------------------------------
 		// Fetch the rest of the dirs
 		//-----------------------------------------
 		
 		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^dir_(\d+)$/", $key, $match ) )
 			{
 				if ( $this->ipsclass->input[$match[0]] )
 				{
 					$count = isset($cur_dir[ $match[0] ]) ? intval( $cur_dir[ $match[0] ] ) : 0;
 					
 					$v_dir .= '|'.$match[0].':'.trim(str_replace( '|', '', str_replace( ";", "", $this->ipsclass->txt_alphanumerical_clean( $_POST[$match[0]], ' ' ) ) ) ).';'.$count;
 				}
 			}
 		}
 		
 		$this->ipsclass->DB->simple_construct( array('update' => 'member_extra', 'set' => "vdirs='$v_dir'", 'where' => 'id='.$this->ipsclass->member['id']) );
 		$this->ipsclass->DB->simple_exec();
 		
 		$this->ipsclass->boink_it($this->ipsclass->base_url."act=Msg&CODE=07");
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// DELETE_MEMBER:
 	//
 	// Removes a member from address book.
 	/*-------------------------------------------------------------------------*/
 	
 	function del_member()
 	{
	 	$this->ipsclass->input['MID'] = isset($this->ipsclass->input['MID']) ? intval($this->ipsclass->input['MID']) : 0;
	 	
		if (!$this->ipsclass->input['MID'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'contacts', 'where' => "member_id={$this->ipsclass->member['id']} AND contact_id={$this->ipsclass->input['MID']}" ) );
 		
 		$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=02");
	}
	
	/*-------------------------------------------------------------------------*/
 	// EDIT_MEMBER:
 	//
 	// Edit a member from address book.
 	/*-------------------------------------------------------------------------*/
 	
 	function edit_member()
 	{
	 	$this->ipsclass->input['MID'] = isset($this->ipsclass->input['MID']) ? intval($this->ipsclass->input['MID']) : 0;
	 	
		if (!$this->ipsclass->input['MID'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'contacts', 'where' => "member_id={$this->ipsclass->member['id']} AND contact_id={$this->ipsclass->input['MID']}" ) );
		$this->ipsclass->DB->simple_exec();

 		$memb = $this->ipsclass->DB->fetch_row();
 		
 		if (!$memb['contact_id'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		$html = "<select name='allow_msg' class='forminput'>";
 		
 		if ($memb['allow_msg'])
 		{
 			$html .= "<option value='yes' selected>{$this->ipsclass->lang['yes']}</option><option value='no'>{$this->ipsclass->lang['no']}";
 		}
 		else
 		{
 			$html .= "<option value='yes'>{$this->ipsclass->lang['yes']}</option><option value='no' selected>{$this->ipsclass->lang['no']}";
 		}
 		
 		$html .= "</select>";
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->address_edit( array( 'SELECT' => $html, 'MEMBER' => $memb ) );
 		
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."&amp;act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>",
 								   "<a href='".$this->base_url."act=Msg&CODE=02'>".$this->ipsclass->lang['t_book']."</a>"  );
 	}
 	
	/*-------------------------------------------------------------------------*/
 	// DO_EDIT_MEMBER:
 	//
 	// Edit a member from address book.
 	/*-------------------------------------------------------------------------*/
 	
 	function do_edit()
 	{
	 	$this->ipsclass->input['MID'] = isset($this->ipsclass->input['MID']) ? intval($this->ipsclass->input['MID']) : 0;
	 	
		if (!$this->ipsclass->input['MID'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		$this->ipsclass->input['allow_msg'] = $this->ipsclass->input['allow_msg'] == 'yes' ? 1 : 0;
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' 	=> 'c.*', 
 													  'from' 	=> array( 'contacts' => 'c'),
 													  'where' 	=> "c.member_id={$this->ipsclass->member['id']} AND c.contact_id={$this->ipsclass->input['MID']}",
 													  'add_join'	=> array( 0 => array( 'select' 	=> 'm.mgroup, m.mgroup_others',
 													  									  'from'	=> array( 'members' => 'm' ),
 													  									  'where'	=> 'm.id=c.contact_id',
 													  									  'type'	=> 'left'
 													  						)			)
 													  
 											 ) 		);
		$this->ipsclass->DB->simple_exec();
		
 		$memb = $this->ipsclass->DB->fetch_row();
 		
 		if (!$memb['contact_id'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		if( $this->ipsclass->vars['unblockable_pm_groups'] && $this->ipsclass->input['allow_msg'] == 'no' )
 		{
	 		$unblockable = array();
	 		$unblockable = explode( ",", $this->ipsclass->vars['unblockable_pm_groups'] );
	 		
	 		$do_override = 0;
	 		
	 		$my_groups = array( $memb['mgroup'] );
	 		
	 		if( $memb['mgroup_others'] )
	 		{
		 		$my_other_groups = explode( ",", $memb['mgroup_others'] );
		 		$my_groups = array_merge( $my_groups, $my_other_groups );
	 		}
	 		
	 		foreach( $my_groups as $member_group )
	 		{
		 		if( in_array( $member_group, $unblockable ) )
		 		{
			 		$do_override = 1;
		 		}
		 	}
		 	
		 	if( $do_override == 0 )
		 	{
			 	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cannot_block' ) );
		 	}
 		} 		
 		
 		$this->ipsclass->DB->do_update( 'contacts', array( 'contact_desc' => $this->ipsclass->input['mem_desc'],
 										   'allow_msg'    => $this->ipsclass->input['allow_msg'],
 										 ), 'id='.$memb['id'] );
 		
 		$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=02");
 	}
 		
 	/*-------------------------------------------------------------------------*/
 	// CONTACT:
 	//
 	// Shows the address book.
 	/*-------------------------------------------------------------------------*/
 	
 	function contact()
 	{
		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->Address_header();
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'contacts',
													  'where'  => "member_id={$this->ipsclass->member['id']}",
													  'order'  =>  "contact_name ASC" ) );
		$this->ipsclass->DB->simple_exec();
		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 		
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->Address_table_header();
 			while ( $row = $this->ipsclass->DB->fetch_row() )
 			{
 				$row['text'] = $row['allow_msg']
 							 ? $this->ipsclass->lang['can_contact']
 							 : $this->ipsclass->lang['cannot_contact'];
 							 
 				$this->output .= $this->ipsclass->compiled_templates['skin_msg']->render_address_row($row);
 			}
 			
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->end_address_table();
 		}
 		else
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->Address_none();
 			
 		}
 		
 		//-----------------------------------------
 		// Do we have a name to enter?
 		//-----------------------------------------
 		
 		$name_to_enter = "";
 		
 		$this->ipsclass->input['MID'] = isset($this->ipsclass->input['MID']) ? intval($this->ipsclass->input['MID']) : 0;
 		
 		if ($this->ipsclass->input['MID'])
 		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name,id', 'from' => 'members', 'where' => "id={$this->ipsclass->input['MID']}" ) );
			$this->ipsclass->DB->simple_exec();

			$memb = $this->ipsclass->DB->fetch_row();
			
			if ($memb['id'])
			{
				$name_to_enter = $memb['members_display_name'];
			}
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->address_add($name_to_enter);
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// ADD MEMBER:
 	//
 	// Adds a member to the addy book.
 	/*-------------------------------------------------------------------------*/
 	
 	function add_member()
 	{
		if (! $this->ipsclass->input['mem_name'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'members_display_name,name,id,mgroup,mgroup_others', 'from' => 'members', 'where' => "members_l_display_name='".$this->ipsclass->input['mem_name']."'" ) );
		$this->ipsclass->DB->simple_exec();
 		
 		$memb = $this->ipsclass->DB->fetch_row();
 		
 		if (! $memb['id'])
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_user' ) );
 		}
 		
 		if ( $memb['id'] == $this->ipsclass->member['id'] )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cannot_block' ) );
 		} 		
 		
 		if( $this->ipsclass->vars['unblockable_pm_groups'] )
 		{
	 		$unblockable = array();
	 		$unblockable = explode( ",", $this->ipsclass->vars['unblockable_pm_groups'] );
	 		
	 		$do_override = 0;
	 		
	 		$my_groups = array( $memb['mgroup'] );
	 		
	 		if( $memb['mgroup_others'] )
	 		{
		 		$my_other_groups = explode( ",", $memb['mgroup_others'] );
		 		$my_groups = array_merge( $my_groups, $my_other_groups );
	 		}
	 		
	 		foreach( $my_groups as $member_group )
	 		{
		 		if( in_array( $member_group, $unblockable ) )
		 		{
			 		$do_override = 1;
		 		}
		 	}
		 	
		 	if( $do_override == 1 )
		 	{
			 	// The member they are trying to add cannot be blocked
			 	
			 	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cannot_block' ) );
		 	}
 		}
 		
 		//-----------------------------------------
 		// Do we already have this member in our
 		// address book?
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'contacts', 'where' => "member_id={$this->ipsclass->member['id']} AND contact_id={$memb['id']}" ) );
		$this->ipsclass->DB->simple_exec();
 		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'member_in_add_book' ) );
 		}
 		
 		//-----------------------------------------
 		// Insert it into the DB
 		//-----------------------------------------
 		
 		$this->ipsclass->input['allow_msg'] = $this->ipsclass->input['allow_msg'] == 'yes' ? 1 : 0;
 		
 		$this->ipsclass->DB->do_insert( 'contacts', array( 
										  'member_id'      => $this->ipsclass->member['id'],
										  'contact_name'   => $memb['members_display_name'],
										  'allow_msg'      => $this->ipsclass->input['allow_msg'],
										  'contact_desc'   => $this->ipsclass->input['mem_desc'],
										  'contact_id'     => $memb['id']
								 )      );
		
		$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=02");
	}
 		
 	/*-------------------------------------------------------------------------*/
 	// Mutli Act:
 	//
 	// Removes or moves messages.
 	/*-------------------------------------------------------------------------*/
 	
 	function multi_act()
 	{
		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------
 		
 		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^msgid_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		$affected_ids = count($ids);
 		
 		if ( $affected_ids > 0 )
 		{
 			$id_string = implode( ",", $ids );
 			
 			if ($this->ipsclass->input['delete'])
 			{
 				$this->msglib->delete_messages( $ids, $this->ipsclass->member['id'] );
 				
 				if ( isset($this->ipsclass->input['saved']) AND $this->ipsclass->input['saved'] )
 				{
 					//-----------------------------------------
 					// Did we delete from the saved folder? If so, don't update the msg stats and
 					// redirect back to the saved folder.
 					//-----------------------------------------
 					
 					$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=20");
 				}
 				else
 				{
 					$this->msglib->rebuild_dir_count( $this->ipsclass->member['id'],
												   $this->ipsclass->member['vdirs'],
												   $this->msglib->vid,
												   $this->ipsclass->member['dir_data'][ $this->msglib->vid ]['count'] - $affected_ids,
												   'save',
												   "msg_total=msg_total-$affected_ids"
												 );
					
					$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=01&VID={$this->msglib->vid}&sort={$this->ipsclass->input['sort']}&st={$this->ipsclass->input['st']}");
					
 				}
 				
 			}
 			else if ($this->ipsclass->input['move'])
 			{
 				$this->ipsclass->DB->simple_construct( array( 'update' => 'message_topics', 'set' => "mt_vid_folder='{$this->msglib->vid}'", 'where' => "mt_vid_folder != '{$this->msglib->vid}' AND mt_owner_id=".$this->ipsclass->member['id']." AND mt_id IN ($id_string)" ) );
 				$this->ipsclass->DB->simple_exec();
				
				if ( $this->ipsclass->DB->get_affected_rows() )
				{			   
					$this->msglib->rebuild_dir_count( $this->ipsclass->member['id'],
												   $this->msglib->rebuild_dir_count( $this->ipsclass->member['id'],
																				  $this->ipsclass->member['vdirs'],
																				  $this->ipsclass->input['curvid'],
																				  $this->ipsclass->member['dir_data'][ $this->ipsclass->input['curvid'] ]['count'] - $affected_ids,
																				  'nosave'
																				),
												   $this->msglib->vid,
												   $this->ipsclass->member['dir_data'][ $this->msglib->vid ]['count'] + $affected_ids,
												   'save'
												 );
				}
												 	
 				$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=01&VID={$this->msglib->vid}&sort={$this->ipsclass->input['sort']}&st={$this->ipsclass->input['st']}");
 				
 			}
 			else
 			{
 				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_msg_chosen' ) );
 			}
 		}
 		else
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_msg_chosen' ) );
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// END TRACKING
 	//
 	// Removes read tracked messages
 	/*-------------------------------------------------------------------------*/
 	
 	function end_tracking()
 	{
		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------
 		
 		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^msgid_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		$affected_ids = count($ids);
 		
 		if ( $affected_ids > 0 )
 		{
 			$id_string = implode( ",", $ids );
 			
 			$this->ipsclass->DB->simple_construct( array( 'update' => 'message_topics', 'set' => 'mt_tracking=0', 'where' => "mt_tracking=1 AND mt_read=1 AND mt_from_id={$this->ipsclass->member['id']} AND mt_id IN ($id_string)" ) );
 			$this->ipsclass->DB->simple_exec();
 			 					
 			$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=30");
 		}
 		else
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_msg_chosen' ) );
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Delete tracked messages
 	/*-------------------------------------------------------------------------*/
 	
 	function del_tracked()
 	{
		//-----------------------------------------
 		// Get the ID's to delete
 		//-----------------------------------------
 		
 		$ids = array();
 		
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^msgid_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		$affected_ids = count($ids);
 		
 		if ( $affected_ids > 0 )
 		{
 			$id_string = implode( ",", $ids );
 			
 			# For directory counts....
 			$member = array();
 			$counts = array();
 			
	 		$this->ipsclass->DB->build_query( array( 'select'	=> 'mt.mt_id, mt.mt_msg_id, mt.mt_read, mt.mt_owner_id', 
	 												 'from' 	=> array( 'message_topics' => 'mt' ), 
	 												 'where' 	=> "mt.mt_read=0 and mt.mt_tracking=1 AND mt.mt_from_id=".$this->ipsclass->member['id']." AND mt.mt_id IN({$id_string})",
	 												 'add_join'	=> array(
	 												 					array(
	 												 							'select'	=> 'me.vdirs, me.id',
	 												 							'from'		=> array( 'member_extra' => 'me' ),
	 												 							'where'		=> 'me.id=mt.mt_owner_id',
	 												 							'type'		=> 'left'
	 												 						)
	 												 					)
	 										) 		);
	 		$this->ipsclass->DB->exec_query();
	 		
	 		while( $r = $this->ipsclass->DB->fetch_row() )
	 		{
		 		if( array_key_exists( $r['id'], $counts ) )
		 		{
			 		$counts[ $r['id'] ]++;
		 		}
		 		else
		 		{
			 		$counts[ $r['id'] ] = 1;
		 		}
		 		
		 		$member[ $r['id'] ] = $r['vdirs'];
	 		}

 			$this->msglib->delete_messages( $ids, $this->ipsclass->member['id'], "mt_read=0 and mt_tracking=1 AND mt_from_id=".$this->ipsclass->member['id'] );
 			
 			if( count($member) )
 			{
	 			foreach($member as $id => $vdirs )
	 			{
					$inbox_count = $this->msglib->_get_dir_count( $vdirs, 'in' );
					
					$new_vdir = $this->msglib->rebuild_dir_count( $id,
														  $vdirs,
														  'in',
														  $inbox_count - $counts[ $id ],
														  'save'
														);
				}
			}
 			
 			$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=30");
 		}
 		else
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_msg_chosen' ) );
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// DELETE MESSAGE:
 	/*-------------------------------------------------------------------------*/
 	
 	function delete()
 	{
		//-----------------------------------------
 		// check for a msg ID
 		//-----------------------------------------
 		
 		$this->ipsclass->input['MSID'] = intval($this->ipsclass->input['MSID']);
 		
 		if ( ! $this->ipsclass->input['MSID'] )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_msg_chosen' ) );
 		}
 		
 		//-----------------------------------------
 		// Delete it from the DB
 		//-----------------------------------------
 		
 		$this->msglib->delete_messages( $this->ipsclass->input['MSID'], $this->ipsclass->member['id'] );
 		
 		$this->msglib->rebuild_dir_count( $this->ipsclass->member['id'],
										  $this->ipsclass->member['vdirs'],
										  $this->msglib->vid,
										  $this->ipsclass->member['dir_data'][ $this->msglib->vid ]['count'] - 1,
										  'save',
										  "msg_total=msg_total-1"
										);
 		
 		$this->ipsclass->boink_it($this->base_url."act=Msg&CODE=01&VID={$this->msglib->vid}");
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// VIEW MESSAGE:
 	//
 	// Views a message, thats it. No, it doesn't do anything else
 	// I don't know why. It just does. Accept it and move on dude.
 	/*-------------------------------------------------------------------------*/
 	
 	function view_msg()
 	{
 		//-----------------------------------------
 		// check for a msg ID
 		//-----------------------------------------
 		
 		$this->ipsclass->input['MSID'] = intval($this->ipsclass->input['MSID']);
 		
 		if (! $this->ipsclass->input['MSID'] )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_msg' ) );
 		}
 		
 		$this->ipsclass->DB->cache_add_query( 'msg_get_msg_to_show', array( 'msgid' => $this->ipsclass->input['MSID'], 'mid' => $this->ipsclass->member['id'] ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		if ( ! $msg = $this->ipsclass->DB->fetch_row() )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_msg' ) );
 		}
 		
 		//-----------------------------------------
 		// Did we read this in the pop up?
 		// If so, reduce new count by 1 (this msg)
 		// 'cos if we went via inbox, we'd have
 		// no new msg
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->member['new_msg'] >= 1 )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'update' => 'members', 'set' => "new_msg=new_msg-1", 'where' => "id=".$this->ipsclass->member['id'] ) );
 			$this->ipsclass->DB->simple_exec();
 		}
 		
		//-----------------------------------------
 		// Is this an unread message?
 		//-----------------------------------------
 		
 		if ($msg['mt_read'] < 1)
 		{
 			$this->ipsclass->DB->simple_construct( array( 'update' => 'message_topics', 'set' => "mt_read=1, mt_user_read=".time(), 'where' => "mt_id=".$this->ipsclass->input['MSID'] ) );
 			$this->ipsclass->DB->simple_exec();
 		}
 		
 		//-----------------------------------------
		// Remove potential [attachmentid= tag in title
		//-----------------------------------------
		
		$msg['mt_title'] = str_replace( '[attachmentid=', '&#91;attachmentid=', $msg['mt_title'] );
		
 		$msg['msg_date'] = $this->ipsclass->get_date( $msg['msg_date'], 'LONG' );
 		
 		if ( $msg['id'] )
 		{
 			$member = $this->ipsclass->parse_member( $msg, 0, 'skin_msg' );
		}
		else
		{
			//-----------------------------------------
			// It's definitely a guest...
			//-----------------------------------------
			
			$member = $this->ipsclass->set_up_guest( $this->ipsclass->lang['deleted_user'] );
			$member['members_display_name'] = $this->ipsclass->lang['deleted_user'];
			$member['custom_fields']		= "";
			$member['warn_text']			= "";
			$member['warn_minus']			= "";
			$member['warn_img']				= "";
			$member['warn_add']				= "";
			$member['signature']			= "";
		}
 		
		if ( $this->ipsclass->member['view_sigs'] and $member['signature'] )
		{
			$member['signature'] = $this->ipsclass->compiled_templates['skin_global']->signature_separator($member['signature']);
		}
		else
		{
			$member['signature'] = "";
		}
		
		$member['VID'] = $this->ipsclass->member['current_id'];
		
		//-----------------------------------------
		// To , CC, etc?
		//-----------------------------------------
		
		$msg['show_cc_users'] = "";
		
		if ( ! $msg['mt_hide_cc'] )
		{
			$cc_users = $this->msglib->format_cc_string( $msg['msg_cc_users'], $this->ipsclass->member['id'] );
			
			if ( $cc_users )
			{
				$msg['show_cc_users'] = $this->ipsclass->compiled_templates['skin_msg']->render_msg_show_cc( $cc_users );
			}
		}
		
		//-----------------------------------------
		// IP Address: Is Admin not viewing other
		// admin message
		//-----------------------------------------
		
		$msg['_ip_address'] = "";
		
		if ( $msg['msg_ip_address'] AND $this->ipsclass->member['g_is_supmod'] == 1 AND $member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			$msg['_ip_address'] = $msg['msg_ip_address'];
		}
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$html = $this->ipsclass->compiled_templates['skin_msg']->Render_msg( $msg, $member, $this->msglib->jump_html );
		
		//-----------------------------------------
		// Attachments?
		//-----------------------------------------
		
		if ( $msg['mt_hasattach'] )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
				$this->class_attach           =  new class_attach();
				$this->class_attach->ipsclass =& $this->ipsclass;
				
				$this->ipsclass->load_language( 'lang_topic' );
			}
		
			$this->class_attach->type  = 'msg';
			$this->class_attach->init();
		
			$html = $this->class_attach->render_attachments( $html, array( $msg['msg_id'] ), 'skin_msg' );
		}
		
		$this->output .= $html;
		
		$this->page_title = $this->ipsclass->lang['t_welcome'];
		
		$this->nav        = array( "<a href='".$this->base_url."&amp;act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>",
								   "<a href='".$this->base_url."act=Msg&CODE=01&VID={$member['VID']}'>".$this->ipsclass->member['current_dir']."</a>",
								   $msg['mt_title']
								 );						   
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SEND MESSAGE:
 	//
 	// Sends a message. Yes, it's that simple. Why so much code?
 	// Because typing "send a message to member X" doesnt actually
 	// do anything.
 	/*-------------------------------------------------------------------------*/
 	
 	function send()
 	{
		//-----------------------------------------
 		// Set up and stuff
 		//-----------------------------------------
 		
 		$show_form = 0;
 		
 		$this->post_key = ( isset($this->ipsclass->input['attach_post_key']) AND $this->ipsclass->input['attach_post_key'] != '' ) ? $this->ipsclass->input['attach_post_key'] : md5(microtime()); 
 		
 		$this->msglib->init();
 		$this->msglib->register_class( $this );
 		
 		//-----------------------------------------
		// Did we remove an attachment?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['removeattachid']) AND $this->ipsclass->input['removeattachid'] )
		{
			if ( $this->ipsclass->input[ 'removeattach_'. $this->ipsclass->input['removeattachid'] ] )
			{
				$this->msglib->postlib->pf_remove_attachment( intval($this->ipsclass->input['removeattachid']), $this->post_key );
				$this->show_form = 1;
			}
		}
		
		//-----------------------------------------
		// Did we add an attachment?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['attachgo'] )
		{
			//$this->upload_id = $this->msglib->postlib->process_upload();
			$this->show_form = 1;
		}
		
		//-----------------------------------------
		// Did we preview?
		//-----------------------------------------
		
		if ($this->ipsclass->input['preview'] != "")
 		{
 			$this->show_form = 1;
 		}
 		
 		//-----------------------------------------
 		// Show form or...
 		//-----------------------------------------
 		
 		if ( isset($this->ipsclass->input['MODE']) AND $this->ipsclass->input['MODE'] and $this->show_form != 1 )
 		{
 			$this->send_msg();
 		}
 		else
 		{
 			$this->msglib->send_form($this->ipsclass->input['preview']);
 			
 			$this->output .= $this->msglib->output;
 		}
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SEND MESSAGE
 	/*-------------------------------------------------------------------------*/
 	
 	function send_msg()
 	{
		$this->ipsclass->load_language('lang_error');
 		
 		$this->ipsclass->input['from_contact'] = $this->ipsclass->input['from_contact'] ? $this->ipsclass->input['from_contact'] : '-';
 		
 		//-----------------------------------------
 		// Error checking
 		//-----------------------------------------
 		
 		if ( strlen(trim($this->ipsclass->input['msg_title'])) < 2 )
 		{
 			$this->msglib->send_form( 0, $this->ipsclass->lang['err_no_title'] );
 			$this->output .= $this->msglib->output;
 			return;
 		}
 		
 		if ( strlen( trim( $_POST['Post'] ) ) < 2 )
 		{
 			$this->msglib->send_form( 0, $this->ipsclass->lang['err_no_msg'] );
 			$this->output .= $this->msglib->output;
 			return;
 		}
 		
 		if ( $this->ipsclass->input['auth_key'] != $this->ipsclass->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
 		
 		if ($this->ipsclass->input['from_contact'] == '-' and $this->ipsclass->input['entered_name'] == "")
 		{
 			$this->msglib->send_form( 0, $this->ipsclass->lang['err_no_chosen_member'] );
 			$this->output .= $this->msglib->output;
 			return;
 		}
 		
 		//-----------------------------------------
 		// TO:
 		//-----------------------------------------
 		
 		if ($this->ipsclass->input['from_contact'] == '-')
 		{
 			$this->msglib->to = $this->ipsclass->input['entered_name'];
 		}
 		else
 		{
 			$this->msglib->to_by_id = intval($this->ipsclass->input['from_contact']);
 		}
 		
 		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
		
		$this->ipsclass->vars['max_emos']	  = 0;
		
 		$this->ipsclass->input['Post'] = $this->msglib->postlib->han_editor->process_raw_post( 'Post' );
 		
 		$this->msglib->postlib->parser->parse_smilies	= 1;
 		$this->msglib->postlib->parser->parse_nl2br   	= 1;
 		$this->msglib->postlib->parser->parse_html    	= $this->ipsclass->vars['msg_allow_html'];
 		$this->msglib->postlib->parser->parse_bbcode   	= $this->ipsclass->vars['msg_allow_code'];
 		
 		$this->ipsclass->input['Post'] = $this->msglib->postlib->parser->pre_db_parse( $this->ipsclass->input['Post'] );
 		$this->ipsclass->input['Post'] = $this->msglib->postlib->parser->pre_display_parse( $this->ipsclass->input['Post'] );
 		$this->ipsclass->input['Post'] = $this->msglib->postlib->parser->bad_words( $this->ipsclass->input['Post'] ); 
 		
 		if( $this->msglib->postlib->parser->error != "" )
 		{
	 		$this->msglib->send_form( 0, $this->ipsclass->lang[$this->msglib->postlib->parser->error] );
	 		$this->output .= $this->msglib->output;
	 		return;
 		}
 		
 		$this->ipsclass->input['msg_title'] = $this->msglib->postlib->parser->bad_words($this->ipsclass->input['msg_title']);
 		
 		//-----------------------------------------
 		// SEND
 		//-----------------------------------------
 		
 		//$this->upload_id           = $this->msglib->postlib->process_upload();
 		$this->msglib->cc_users    = $this->ipsclass->input['carbon_copy'];
 		$this->msglib->from_member = $this->ipsclass->member;
 		$this->msglib->msg_title   = $this->ipsclass->input['msg_title'];
 		$this->msglib->msg_post    = $this->ipsclass->input['Post'];
 		
 		$this->msglib->send_pm( array( 'save_only' => isset($this->ipsclass->input['save']) ? $this->ipsclass->input['save'] : 0,
									   'orig_id'   => isset($this->ipsclass->input['OID']) ? intval($this->ipsclass->input['OID']) : 0,
									   'preview'   => isset($this->ipsclass->input['preview']) ? $this->ipsclass->input['preview'] : 0,
									   'track'     => isset($this->ipsclass->input['add_tracking']) ? $this->ipsclass->input['add_tracking'] : 0,
									   'add_sent'  => isset($this->ipsclass->input['add_sent']) ? 1 : 0,
									   'hide_cc'   => isset($this->ipsclass->input['mt_hide_cc']) ? $this->ipsclass->input['mt_hide_cc'] : 0
							  )     );
 		
 		if ( $this->msglib->error != "" )
 		{
 			$this->msglib->send_form( 0,$this->msglib->error );
 			$this->output .= $this->msglib->output;
 			return;
 		}
		
		if ( $this->msglib->redirect_url )
		{
			$this->ipsclass->print->redirect_screen( $this->msglib->redirect_lang, $this->msglib->redirect_url );
		}
		
		//-----------------------------------------
		// Swap and serve...
		//-----------------------------------------
		
		$text = str_replace( "<#FROM_MEMBER#>"   , $this->ipsclass->member['members_display_name'] , $this->ipsclass->lang['sent_text'] );
		$text = str_replace( "<#MESSAGE_TITLE#>" , $this->ipsclass->input['msg_title'], $text );
		
		$this->ipsclass->print->redirect_screen( $text , "&act=Msg&CODE=01" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// MSG LIST:
 	//
 	// Views the inbox / folder of choice
 	/*-------------------------------------------------------------------------*/
 	
 	function msg_list()
 	{
		$sort_key = "";
 		
 		switch ($this->ipsclass->input['sort'])
 		{
 			case 'rdate':
 				$sort_key = 'mt.mt_date ASC';
 				break;
 			case 'title':
 				$sort_key = 'mt.mt_title ASC';
 				break;
 			case 'name':
 				$sort_key = 'mem.members_display_name ASC';
 				break;
 			default:
 				$sort_key = 'mt.mt_date DESC';
 				break;
 		}
 		
 		//-----------------------------------------
 		// Get the number of messages we have in total.
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->simple_construct( array ( 'select' => 'COUNT(*) as msg_total', 'from' => 'message_topics', 'where' => "mt_owner_id=".$this->ipsclass->member['id']." AND mt_vid_folder != 'unsent'" ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		$total = $this->ipsclass->DB->fetch_row();
 		
 		$total['msg_total'] = intval($total['msg_total']);
 		
 		if ( $total['msg_total'] != $this->ipsclass->member['msg_total'] )
 		{
 			$this->ipsclass->DB->simple_construct( array ( 'update'=> 'members', 'set' => "msg_total=".$total['msg_total'], 'where' => "id=".$this->ipsclass->member['id'] ) ) ;
 			$this->ipsclass->DB->simple_exec();
 		}
 		
 		//-----------------------------------------
 		// Get the number of messages in our curr folder.
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->simple_construct( array ( 'select' => 'COUNT(*) as msg_total', 'from' => 'message_topics', 'where' => "mt_owner_id=".$this->ipsclass->member['id']." AND mt_vid_folder='{$this->msglib->vid}'" ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		$total_current = $this->ipsclass->DB->fetch_row();
 		
 		$total_current['msg_total'] = intval($total_current['msg_total']);
 		
 		if ( $total_current['msg_total'] != $this->ipsclass->member['dir_data'][ $this->msglib->vid ]['count'] )
 		{
 			$this->msglib->rebuild_dir_count( $this->ipsclass->member['id'], $this->ipsclass->member['vdirs'], $this->msglib->vid, $total_current['msg_total'] );
 		}
 		
 		//-----------------------------------------
 		// Make sure we've not exceeded our alloted allowance.
 		//-----------------------------------------
 		
 		$info['full_messenger'] = "<br />";
 		$info['full_text']      = "";
 		$info['total_messages'] = $total['msg_total'];
 		$info['img_width']      = 1;
 		$info['vid']            = $this->msglib->vid;
 		$info['date_order']     = $sort_key == 'm.msg_date DESC' ? 'rdate' : 'msg_date';
 		
 		$amount_info            = sprintf( $this->ipsclass->lang['pmpc_info_string'], $total['msg_total'] ,$this->ipsclass->lang['pmpc_unlimited'] );
 		
 		if ($this->ipsclass->member['g_max_messages'] > 0)
 		{
 			$amount_info          = sprintf( $this->ipsclass->lang['pmpc_info_string'], $total['msg_total'] ,$this->ipsclass->member['g_max_messages'] );
 			
 			$info['full_percent'] = $total['msg_total'] ? sprintf( "%.0f", ( ($total['msg_total'] / $this->ipsclass->member['g_max_messages']) * 100) ) : 0;
 			$info['img_width']    = $info['full_percent'] > 0 ? intval($info['full_percent']) * 2.4 : 1;
 			
 			if ($info['img_width'] > 300)
 			{
 				$info['img_width'] = 300;
 			}
 			
 			if ($total_current['msg_total'] >=$this->ipsclass->member['g_max_messages'])
 			{
 				$info['full_messenger'] = "<span class='highlight'>".$this->ipsclass->lang['c_msg_full']."</span>";
 			}
 			else
 			{
 				$info['full_messenger'] = str_replace( "<#PERCENT#>", $info['full_percent'], $this->ipsclass->lang['pmpc_full_string'] );
 			}
 		}
 		
 		//-----------------------------------------
 		// Generate Pagination
 		//-----------------------------------------
 		
 		$start = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
 		$p_end = $this->ipsclass->vars['show_max_msg_list'] > 0 ? $this->ipsclass->vars['show_max_msg_list'] : 50;
 		
 		if ( $start >= $total_current['msg_total'] )
 		{
	 		$start = 0;
 		}
 		
 		$pages = $this->ipsclass->build_pagelinks( array( 'TOTAL_POSS'  => $total_current['msg_total'],
														  'PER_PAGE'    => $p_end,
														  'CUR_ST_VAL'  => $start,
														  'L_SINGLE'    => "",
														  'L_MULTI'     => $this->ipsclass->lang['msg_pages'],
														  'BASE_URL'    => $this->ipsclass->base_url."act=Msg&amp;CODE=1&amp;VID=".$this->msglib->vid."&amp;sort=".$this->ipsclass->input['sort'],
												 )      );
 		
 		//-----------------------------------------
 		// Print the header
 		//-----------------------------------------
 		
 		if ($this->msglib->vid == 'sent')
 		{
 			$this->ipsclass->lang['message_from'] = $this->ipsclass->lang['message_to'];
 			
 			$this->ipsclass->DB->cache_add_query( 'msg_get_sent_list', array( 'mid' => $this->ipsclass->member['id'], 'vid' => $this->msglib->vid, 'sort' => $sort_key, 'limita' => $start, 'limitb' => $p_end ) );
 			$this->ipsclass->DB->simple_exec();
  		}
 		else
 		{
 			$this->ipsclass->DB->cache_add_query( 'msg_get_folder_list', array( 'mid' => $this->ipsclass->member['id'], 'vid' => $this->msglib->vid, 'sort' => $sort_key, 'limita' => $start, 'limitb' => $p_end ) );
 			$this->ipsclass->DB->simple_exec();
  		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->inbox_table_header( $this->ipsclass->member['current_dir'], $info, $this->msglib->jump_html, $pages, $this->msglib->vid );
 		
 		//-----------------------------------------
 		// Get the messages
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			while( $row = $this->ipsclass->DB->fetch_row() )
 			{
				$row['attach_img'] = $row['mt_hasattach'] ? '<{ATTACH_ICON}>' : '';
				
				$row['icon'] = $this->msglib->vid == 'sent' ? '<{M_READ}>' : ( $row['mt_read'] == 1 ? '<{M_READ}>' : '<{M_UNREAD}>' );
 				
 				$row['date'] = $this->ipsclass->get_date( $row['mt_date'] , 'LONG' );
 				
				$row['add_to_contacts'] = $this->msglib->vid != 'sent' ? "[ <a href='{$this->ipsclass->base_url}act=Msg&amp;CODE=02&amp;MID={$row['from_id']}'>{$this->ipsclass->lang['add_to_book']}</a> ]" : '';

				$row['from_name'] = $row['from_name'] ? 
									"<a href='{$this->ipsclass->base_url}showuser={$row['from_id']}'>{$row['from_name']}</a> {$row['add_to_contacts']}" : 
									$this->ipsclass->lang['deleted_user'];

 				$this->output .= $this->ipsclass->compiled_templates['skin_msg']->inbox_row( $row );
 			}
 		}
 		else
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->No_msg_inbox();
 		}
 		
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->end_inbox($this->msglib->jump_html, $amount_info, $pages);
 		
 		//-----------------------------------------
 		// Update the message stats if we have to
 		//-----------------------------------------
 		
 		if ($this->ipsclass->member['current_id'] == 'in' and $this->ipsclass->member['new_msg'] > 0 )
 		{
 			$this->ipsclass->DB->simple_construct( array( 'update' => 'members', 'set' => 'new_msg=0', 'where' => 'id='.$this->ipsclass->member['id'] ) );
 			$this->ipsclass->DB->simple_exec();
 		}
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}

	/*-------------------------------------------------------------------------*/
 	// VIEW SAVED:
 	//
 	// View the saved folder stuff.
 	/*-------------------------------------------------------------------------*/
 	
 	function view_saved()
 	{
		//-----------------------------------------
 		// Print the header
 		//-----------------------------------------
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->unsent_table_header();
 		
 		$this->ipsclass->DB->cache_add_query( 'msg_get_sent_list', array( 'mid' => $this->ipsclass->member['id'], 'vid' => 'unsent', 'sort' => 'mt_date DESC', 'limita' => 0, 'limitb' => 5000 ) );
 		$this->ipsclass->DB->simple_exec();
 			
 		//-----------------------------------------
 		// Get the messages
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 			while( $row = $this->ipsclass->DB->fetch_row() )
 			{
				$row['attach_img'] = $row['mt_hasattach'] ? '<{ATTACH_ICON}>' : '';
				
 				$row['icon']     = "<{M_READ}>";
 				$row['date']     = $this->ipsclass->get_date( $row['mt_date'] , 'LONG' );
 				$row['cc_users'] = $row['msg_cc_users'] == "" ? $this->ipsclass->lang['no'] : $this->ipsclass->lang['yes'];
 				
				$row['from_name'] = $row['from_name'] ? 
									"<a href='{$this->ipsclass->base_url}showuser={$row['from_id']}'>{$row['from_name']}</a> {$row['add_to_contacts']}" : 
									$this->ipsclass->lang['deleted_user'];
 				
 				$d_array = array( 'msg' => $row, 'member' => $this->ipsclass->member );
 				
 				$this->output .= $this->ipsclass->compiled_templates['skin_msg']->unsent_row( $d_array );
 			}
 		}
 		else
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->unsent_empty_row();
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->unsent_end();
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SHOW TRACKED MESSAGE
 	/*-------------------------------------------------------------------------*/
 	
 	function show_tracking()
 	{
		//-----------------------------------------
 		// Get all tracked and read messages
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->cache_add_query( 'msg_get_tracking', array( 'mid' => $this->ipsclass->member['id'] ) );
		$this->ipsclass->DB->simple_exec();
		
		$read = array();
		$unread = array();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['mt_read'] )
			{
				$read[ $r['mt_user_read'].','.$r['mt_id'] ] = $r;
			}
			else
			{
				$unread[ $r['mt_user_read'].','.$r['mt_id'] ] = $r;
			}
		}
		
		krsort( $read );
		krsort( $unread );
		
		//-----------------------------------------
 		// READ MESSAGES
 		//-----------------------------------------
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->trackread_table_header();	

 		if ( count($read) )
 		{
 			foreach( $read as $row )
 			{
 				$row['icon']     = "<{M_READ}>";
 				$row['date']     = $this->ipsclass->get_date( $row['mt_user_read'] , 'LONG' );
 				$this->output .= $this->ipsclass->compiled_templates['skin_msg']->trackread_row( $row );
 			}
 		}
 		else
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->No_msg_inbox();
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->trackread_end();
 		
 		//-----------------------------------------
 		// UNREAD MESSAGES
 		//-----------------------------------------
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->trackUNread_table_header();
 		
 		if ( count($unread) )
 		{
 			foreach( $unread as $row )
 			{
 				$row['icon']     = "<{M_UNREAD}>";
 				$row['date']     = $this->ipsclass->get_date( $row['mt_date'] , 'LONG' );
 				$this->output .= $this->ipsclass->compiled_templates['skin_msg']->trackUNread_row( $row );
 			}
 		}
 		else
 		{
 			$this->output .= $this->ipsclass->compiled_templates['skin_msg']->No_msg_inbox();
 		}
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_msg']->trackUNread_end();
 		
 		$this->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->nav        = array( "<a href='".$this->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
 	}
        
}

?>