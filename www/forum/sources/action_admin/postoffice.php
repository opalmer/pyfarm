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
|   > $Date: 2007-09-17 18:05:43 -0400 (Mon, 17 Sep 2007) $
|   > $Revision: 1106 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > POST OFFICE Stuff
|   > Module written by Matt Mecham
|   > Date started: 1st April 2004 (April Fools!)
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


class ad_postoffice
{
	var $base_url;
	var $colours = array();
	var $root_path = './';
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "tools";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "postoffice";
	
	function auto_run()
	{
		if ( TRIAL_VERSION )
		{
			print "This feature is disabled in the trial version.";
			exit();
		}
		
		$this->root_path = ROOT_PATH ? ROOT_PATH : './';
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'IPB Post Office' );
		
		$this->ipsclass->admin->page_detail = "You may manage previous bulk emails and create new bulk emails in this section.";
		$this->ipsclass->admin->page_title  = "IPB Post Office";
		
		switch($this->ipsclass->input['code'])
		{
			case 'mail_new':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->mail_form('add');
				break;
			case 'mail_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->mail_form('edit');
				break;
			case 'mail_save':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->mail_save();
				break;
			case 'mail_preview':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->mail_preview_start();
				break;
			case 'mail_preview_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->mail_preview_complete();
				break;
			case 'mail_send_start':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':send' );
				$this->mail_send_start();
				break;
			case 'mail_send_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':send' );
				$this->mail_send_complete();
				break;
			case 'mail_send_cancel':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':send' );
				$this->mail_send_cancel();
				break;
			case 'mail_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->mail_delete();
				break;
			//-----------------------------------------
			// Default
			//-----------------------------------------
			
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->mail_start();
				break;
		}
	}
	
	//-----------------------------------------
	// DELETE MAIL - WOT IS SAYZ ON TEH TIN
	//-----------------------------------------
	
	function mail_delete()
	{
		$id = intval( $this->ipsclass->input['id'] );
		
		$active = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'mail_id', 'from' => 'bulk_mail', 'where' => 'mail_active=1 AND mail_id <>'.$id ) );
		
		if( !$active['mail_id'] )
		{
			$this->ipsclass->DB->do_update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='bulkmail'" );
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'bulk_mail', 'where' => 'mail_id='.$id ) );
											
		$this->ipsclass->main_msg = "Bulk email deleted";
		$this->mail_start();
	}
	
	//-----------------------------------------
	// SEND MAIL: Cancel sending in progress
	//-----------------------------------------
	
	function mail_send_cancel()
	{
		$this->ipsclass->DB->do_update( 'bulk_mail', array(  'mail_active'  => 0,
											 'mail_updated' => time(),
										  ), "mail_active=1" );
											
		$this->ipsclass->DB->do_update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='bulkmail'" );
		
		$this->ipsclass->main_msg = "Bulk email cancelled";
		$this->mail_start();
	}
	
	//-----------------------------------------
	// SEND MAIL: Send the mail mah-boy
	//-----------------------------------------
	
	function mail_send_process( $root_path="" )
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		if ( $root_path )
		{
			$this->root_path = $root_path;
		}
		else if ( ROOT_PATH )
		{
			$this->root_path = ROOT_PATH;
		}
		
		require_once( $this->root_path."sources/classes/class_email.php");
		
		$this->email = new emailer( $this->root_path );
		$this->email->ipsclass =& $this->ipsclass;
		$this->email->email_init();
		
		$done = 0;
		$sent = 0;
		
		//-----------------------------------------
		// Get it from the db
		//-----------------------------------------
		
		$mail = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_active=1' ) );
		
		if ( ! $mail['mail_subject'] and ! $mail['mail_content'] )
		{
			$done = 1;
		}
		
		//-----------------------------------------
		// Per go...
		//-----------------------------------------
		
		$pergo = intval($mail['mail_pergo']);
		
		if ( ! $pergo or $pergo > 1000 )
		{
			$pergo = 50;
		}
		
		//-----------------------------------------
		// So far...
		//-----------------------------------------
		
		$sofar = intval($mail['mail_sentto']);
		
		$mail['mail_content'] = $this->ipsclass->txt_stripslashes( $mail['mail_content'] );
		$mail['mail_subject'] = $this->ipsclass->txt_stripslashes( $mail['mail_subject'] );
		
		//-----------------------------------------
		// Unconvert options
		//-----------------------------------------
		
		$opts = unserialize(stripslashes( $mail['mail_opts'] ) );
		
		foreach( $opts as $k => $v )
		{
			$mail[ $k ] = $v;
		}
		
		//-----------------------------------------
 		// Format the query
 		//-----------------------------------------
 		
 		$query = $this->_build_members_query( $mail );
 		
		//-----------------------------------------
		// Now get members....
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'members',
													  'where'  => $query,
													  'order'  => 'id',
													  'limit'  => array( $sofar, $pergo ) ) );
		
		$o = $this->ipsclass->DB->simple_exec();
		
		if ( $mail['mail_html_on'] )
		{
			$this->email->html_email = 1;
		}
									  
		while ( $r = $this->ipsclass->DB->fetch_row( $o ) )
		{
			$sent++;
			
			$contents = $this->_convert_quick_tags( $mail['mail_content'], $r );
			
			$this->email->from    = $this->ipsclass->vars['email_out'];
			$this->email->to      = $r['email'];
			$this->email->message = str_replace( "\r\n", "\n", $contents);
			$this->email->subject = $mail['mail_subject'];
			
			$this->email->send_mail();
		}
		
		//-----------------------------------------
		// Did we send any?
		//-----------------------------------------
		
		if ( ! $sent )
		{
			$done = 1;
		}
		
		//-----------------------------------------
		// Save out..
		//-----------------------------------------
		
		if ( $done )
		{
			$this->ipsclass->DB->do_update( 'bulk_mail', array( 'mail_active'  => 0,
												'mail_updated' => time(),
												'mail_sentto'  => $sofar + $sent ), 'mail_id='.$mail['mail_id'] );
												
			$this->ipsclass->DB->do_update( 'task_manager', array( 'task_enabled' => 0 ), "task_key='bulkmail'" );
		}
		else
		{
			$this->ipsclass->DB->do_update( 'bulk_mail', array( 'mail_updated' => time(),
												'mail_sentto'  => $sofar + $sent ), 'mail_id='.$mail['mail_id'] );
		}			
	}
	
	//-----------------------------------------
	// SEND MAIL: Complete
	//-----------------------------------------
	
	function mail_send_complete()
	{
		$pergo = intval($this->ipsclass->input['pergo']);
		$id    = intval($this->ipsclass->input['id']);
		
		if ( ! $id )
		{
			$this->ipsclass->main_msg = "There was no ID passed to this function";
			$this->mail_start();
		}
		
		//-----------------------------------------
		// Get it from the db
		//-----------------------------------------
		
		$mail = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id='.$id ) );
		
		if ( ! $mail['mail_subject'] and ! $mail['mail_content'] )
		{
			$this->ipsclass->main_msg = "Cannot send - the email doesn't have a subject and content";
			$this->mail_start();
		}
		
		//-----------------------------------------
		// Update mail
		//-----------------------------------------
		
		if ( ! $pergo or $pergo > 1000 )
		{
			$pergo = 50;
		}
		
		$this->ipsclass->DB->do_update( 'bulk_mail', array( 'mail_active' => 1, 'mail_pergo' => $pergo, 'mail_sentto' => 0, 'mail_start' => time() ), 'mail_id='.$id );
		$this->ipsclass->DB->do_update( 'bulk_mail', array( 'mail_active' => 0 ) , 'mail_id != '.$id );
		
		//-----------------------------------------
		// Wake up task manager
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_taskmanager.php' );
		$task = new func_taskmanager();
		$task->ipsclass =& $this->ipsclass;
		
		$this->ipsclass->DB->do_update( 'task_manager', array( 'task_enabled' => 1 ), "task_key='bulkmail'" );
		
		$this_task = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key='bulkmail'" ) );
		$newdate = $task->generate_next_run($this_task);
		
		$this->ipsclass->DB->do_update( 'task_manager', array( 'task_next_run' => $newdate ), "task_id=".$this_task['task_id'] );
			
		$task->save_next_run_stamp();
		
		//-----------------------------------------
		// Sit back and watch the show
		//-----------------------------------------
		
		$this->ipsclass->main_msg = "Bulk Mail Initiated";
		
		$this->mail_start();
	}
	
	
	//-----------------------------------------
	// SEND MAIL: Start
	//-----------------------------------------
	
	function mail_send_start()
	{
		$id = intval($this->ipsclass->input['id']);
		
		if ( ! $id )
		{
			$this->ipsclass->main_msg = "There was no ID passed to this function";
			$this->mail_start();
		}
		
		//-----------------------------------------
		// Get it from the db
		//-----------------------------------------
		
		$mail = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id='.$id ) );
		
		if ( ! $mail['mail_subject'] and ! $mail['mail_content'] )
		{
			$this->ipsclass->main_msg = "Cannot send - the email doesn't have a subject and content";
			$this->mail_start();
		}
		
		//-----------------------------------------
		// Unconvert options
		//-----------------------------------------
		
		$opts = unserialize(stripslashes( $mail['mail_opts'] ) );
		
		foreach( $opts as $k => $v )
		{
			$mail[ $k ] = $v;
		}
		
		//-----------------------------------------
 		// Format the query
 		//-----------------------------------------
 		
 		$query = $this->_build_members_query( $mail );
								
		//-----------------------------------------
		// Count how many matches
		//-----------------------------------------
		
		$count = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as cnt', 'from' => 'members', 'where' => $query ) );
		
		$the_count = intval( $count['cnt'] );
		
		//-----------------------------------------
		// Print 'continue' screen
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'mail_send_complete'  ),
												                 2 => array( 'act'   , 'postoffice' ),
												                 3 => array( 'id'    , $this->ipsclass->input['id'] ),
												                 4 => array( 'section', $this->ipsclass->section_code ),
									                     )      );
		
		$this->ipsclass->html .= "<div class='tableborder'><div class='tableheaderalt'>Sending the bulk mail to {$the_count} members</div>";
		$this->ipsclass->html .= "<div class='tablerow2' style='padding:4px'>
							<fieldset>
							 <legend><strong>Email Details</strong></legend>
							 <strong>Subject: {$mail['mail_subject']}</strong>
							 <br />
							 <br />
							 <div style='height:200px;white-space:pre'><iframe width='100%' height='200px' scrollbars='auto' src='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=postoffice&code=mail_preview_do&id={$id}'></iframe></div>
							 <br />
							 <div align='center'><span class='fauxbutton'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=mail_edit&id={$id}'>Edit This Email</a></span></div>
							 <br />
							 Sending to <b>{$the_count}</b> &nbsp;members.
							</fieldset>
							<br />
							<fieldset>
							 <legend><strong>Sending the Email</strong></legend>
							 Clicking the 'Start Sending The Emails' button below will activate the task manager's send bulk email function which will send the email in batches until the all the mail
							 has been sent. You can watch the progress from the main Post Office screen and cancel the email from being sent.
							 <br />
							 We strongly recommend that you opt to send 50 emails per cycle as a maximum. Any more per cycle could severly affect performance of your server while the mail is being sent out.
							 <br />
							 <br />
							 As the mail is sent out in cycles of <em><b>n</b></em> &nbsp;per minute, it can take a while to process all the mail.
							 <br />
							 <br />
							 <div align='center'><b>Emails sent per cycle</b> <input type='text' class='realbutton' size='5' name='pergo' value='50' /> &nbsp; <input type='submit' value='Start Sending The Emails' class='realbutton' /></form></div>
							</fieldset>
						   </div>
						   </div>";
		
		$this->ipsclass->admin->output();	
	}
		
	//-----------------------------------------
	// PREVIEW MAIL: Show it - yo!
	//-----------------------------------------
	
	function mail_preview_complete()
	{
		
		$id = intval($this->ipsclass->input['id']);
		
		$content = "";
		
		if( $id )
		{		
			//-----------------------------------------
			// Get it from the db
			//-----------------------------------------
		
			$mail = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id='.$id ) );

			$content = $this->_convert_quick_tags( $this->ipsclass->txt_stripslashes($mail['mail_content']), $this->ipsclass->member );
			
			$mailopts = unserialize( $mail['mail_opts'] );

			if( $mailopts['mail_html_on'] == 0 )
			{
				$content = nl2br( htmlspecialchars( $content, ENT_QUOTES ) );
			}
		}
		else
		{
			if( $_POST['html'] )
			{
				$content = $this->_convert_quick_tags( $this->ipsclass->txt_stripslashes($_POST['text']), $this->ipsclass->member );
			}
			else
			{
				$content = nl2br( htmlspecialchars( $this->_convert_quick_tags( $this->ipsclass->txt_stripslashes($_POST['text']), $this->ipsclass->member ), ENT_QUOTES) );
			}
		}
		
		header("HTTP/1.0 200 OK");
		header("HTTP/1.1 200 OK");

		ob_end_clean();
		
		header("Content-type: text/html; charset={$this->ipsclass->vars['gb_char_set']}");
		header("Content-Disposition: inline");
		
		print "<html>
		   <body>
		   <div style='padding:6px;text-align:left;font-family:courier, monospace;font-size:12px'>
		   {$content}
		   </body></html>
			";
		
		exit();
	}
	
	//-----------------------------------------
	// PREVIEW MAIL: JS BOUNCE
	//-----------------------------------------
	
	function mail_preview_start()
	{
		header("HTTP/1.0 200 OK");
		header("HTTP/1.1 200 OK");
		header("Content-type: text/html");
				
		print "<html><body onload='doitdude()'><script type='text/javascript'>
			   posty = opener.thisval;
			   pisty = opener.thatval;
			   
			   function doitdude()
			   {
				document.peekaboo.action = '{$this->ipsclass->base_url}&{$this->ipsclass->form_code_js}&code=mail_preview_do';
				document.peekaboo.text.value = posty;
				document.peekaboo.html.value = pisty;
				document.peekaboo.submit();
			   }
			   </script>
			   <form name='peekaboo' method='post'>
			   <input type='hidden' name='text' />
			   <input type='hidden' name='html' />
			   </form>
			   </body></html>
		";
	}
	
	//-----------------------------------------
	// SAVE MAIL
	//-----------------------------------------
	
	function mail_save()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$ids = array();
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// Start
		//-----------------------------------------
		
		$type = $this->ipsclass->input['type'];
		
		if ( ! $this->ipsclass->input['mail_subject'] or ! $this->ipsclass->input['mail_content'] )
		{
			$this->ipsclass->main_msg = "You must enter an email subject and email content before submitting.";
			$this->mail_form( $type );
		}
		
		//-----------------------------------------
		// Groups...
		//-----------------------------------------
		
		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^sg_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[ $match[0] ])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		if ( ! count( $ids ) )
 		{
 			$this->ipsclass->main_msg = "You must select at least one group to send this email message to.";
 			$this->mail_form( $type );
 		}
 		
 		$this->ipsclass->input['mail_groups'] = implode( ",", $ids );
 		
 		//-----------------------------------------
 		// Format the query
 		//-----------------------------------------
 		
 		$query = $this->_build_members_query( array( 'mail_post_ltmt'     => $this->ipsclass->input['mail_post_ltmt'],
													 'mail_filter_post'   => $this->ipsclass->input['mail_filter_post'],
													 'mail_visit_ltmt'    => $this->ipsclass->input['mail_visit_ltmt'],
													 'mail_filter_visit'  => intval($this->ipsclass->input['mail_filter_visit']),
													 'mail_joined_ltmt'   => $this->ipsclass->input['mail_joined_ltmt'],
													 'mail_filter_joined' => intval($this->ipsclass->input['mail_filter_joined']),
													 'mail_honor'         => intval($this->ipsclass->input['mail_honor']),
													 'mail_groups'    	  => $this->ipsclass->input['mail_groups'],
											)      );

		//-----------------------------------------
		// Count how many matches
		//-----------------------------------------
		
		$count = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as cnt', 'from' => 'members', 'where' => $query ) );
		
		if ( ! $count['cnt'] )
		{
			$this->ipsclass->main_msg = "Your criteria didn't match any members - please adjust your current criteria";
			$this->mail_form( $type );
		}
		
		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		$save_array = array(
							 'mail_subject' => str_replace( "&#039;", "'", $this->ipsclass->txt_stripslashes( $_POST['mail_subject'] ) ),
							 'mail_content' => $this->ipsclass->txt_stripslashes( $_POST['mail_content'] ),
							 'mail_groups'  => implode( ",", $ids ),
							 'mail_honor'   => intval($this->ipsclass->input['mail_honor']),
							 'mail_start'   => time(),
							 'mail_updated' => time(),
							 'mail_sentto'  => 0,
							 'mail_opts'    => serialize( array( 'mail_post_ltmt'     => $_POST['mail_post_ltmt'],
																 'mail_filter_post'   => $_POST['mail_filter_post'],
																 'mail_visit_ltmt'    => $_POST['mail_visit_ltmt'],
																 'mail_filter_visit'  => $_POST['mail_filter_visit'],
																 'mail_joined_ltmt'   => $_POST['mail_joined_ltmt'],
																 'mail_filter_joined' => $_POST['mail_filter_joined'],
																 'mail_html_on'       => $_POST['mail_html_on'],
													    )      )
						 );
						 
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Save to DB
			//-----------------------------------------
			
			$this->ipsclass->DB->do_insert( 'bulk_mail', $save_array );
			
			$this->ipsclass->input['id'] = $this->ipsclass->DB->get_insert_id();
			
			$this->ipsclass->admin->save_log("Bulk mail ({$this->ipsclass->input['mail_subject']}) added");
			$this->mail_send_start();
		}
		else
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->main_msg = "Cannot save, no ID returned";
				$this->mail_form($type);
			}
			
			$this->ipsclass->DB->do_update( 'bulk_mail', $save_array, 'mail_id='.$this->ipsclass->input['id'] );
			
			$this->ipsclass->admin->save_log("Bulk mail ({$this->ipsclass->input['mail_subject']}) edited");
			
			$this->ipsclass->main_msg = "Bulk mail edited";
			$this->mail_start();
		}
	}
	
	//-----------------------------------------
	// SHOW MAIN MAIL SCREENIE-POOS
	//-----------------------------------------
	
	function mail_form($type='add')
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		$dd_ltmt = array(
						  0 => array( 'lt' , "less than" ),
						  1 => array( 'mt' , "more than" )
						);
		
		if ( $type == 'add' )
		{
			$mail   = array();
			$title  = "Step 1: Start New Bulk Email";
			$button =  "Proceed...";
			$honour_checked = 'checked="checked"';
			$html_checked   = '';
		}
		else
		{
			$mail   = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'bulk_mail', 'where' => 'mail_id='.$this->ipsclass->input['id'] ) );
			$title  = "Editing Stored Mail";
			$button = "Edit Mail";
			
			//-----------------------------------------
			// Unpack more..
			//-----------------------------------------
			
			$tmp = unserialize( stripslashes( $mail['mail_opts'] ) );
			
			if ( is_array( $tmp ) and count ( $tmp ) )
			{
				foreach( $tmp as $k => $v )
				{
					if ( ! $mail[ $k ] )
					{
						$mail[ $k ] = $v;
					}
				}
			}
			
			$honour_checked = $mail['mail_honor']   == 1 ? 'checked="checked"' : '';
			$html_checked   = $mail['mail_html_on'] == 1 ? 'checked="checked"' : '';
			
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'mail_save'  ),
																			 2 => array( 'act'   , 'postoffice' ),
																			 3 => array( 'type'  , $type        ),
																			 4 => array( 'id'    , $this->ipsclass->input['id'] ),
																			 5 => array( 'section', $this->ipsclass->section_code ),
																	 )      );
									                     
		$mail_subject = $_POST['mail_subject'] ? $this->ipsclass->txt_stripslashes($_POST['mail_subject']) : $mail['mail_subject'];
		$mail_content = $_POST['mail_content'] ? $this->ipsclass->txt_stripslashes($_POST['mail_content']) : $mail['mail_content'];
		$mail_subject = str_replace( "'", "&#039;", $mail_subject );
		
		$mail_content = preg_replace( "[^\r]\n", "\r\n", $mail_content );
		
		if ( ! $mail_content and $type == 'add' )
		{
			$mail_content = $this->_get_default_mail_contents();
		}
		
		if ( $this->ipsclass->input['mail_groups'] )
		{
			$mail['mail_groups'] = $this->ipsclass->input['mail_groups'];
		}
									                     
		$this->ipsclass->html .= "<script type='text/javascript'>
						    function runpreview()
						    {
						    	thisval = document.theAdminForm.mail_content.value;
						    	if( document.theAdminForm.mail_html_on.checked == true )
						    	{
							    	thatval = 1;
						    	}
						    	else
						    	{
							    	thatval = 0;
						    	}
								myWin   = window.open('{$this->ipsclass->base_url}&act=postoffice&section={$this->ipsclass->section_code}&code=mail_preview','newWin','width=500,height=500,resizable=yes,scrollbars=yes');
						    }
						    </script>
						    ";
		
		$this->ipsclass->html .= "<div class='tableborder'><div class='tableheaderalt'>{$title}</div>";
		$this->ipsclass->html .= "<div class='tablerow2' style='padding:4px'>
							<fieldset>
							 <legend><strong>Email Subject</strong></legend>
							 <input type='text' size='60' maxsize='250' class='textinput' style='width:100%' name='mail_subject' value='{$mail_subject}' />
							</fieldset>
							<br />
							<fieldset>
							 <legend><strong>Email Content</strong></legend>
							 <textarea cols='60' rows='20' class='textinput' style='width:100%' name='mail_content'>{$mail_content}</textarea>
							 <br />
							 <fieldset>
							  <legend><strong>Mail Sending Options</strong></legend>
							   <input type='checkbox' name='mail_honor'   value='1' {$honour_checked} />&nbsp; Honor 'Allow admin to email me' member setting to only email those who have opted in?
							   <br/ >
							   <input type='checkbox' name='mail_html_on' value='1' {$html_checked} />&nbsp; Send email in HTML format (full HTML formatting & tags allowed)
							 </fieldset>
							</fieldset>";
		
		$this->ipsclass->html .= "</div></div><br />";
		
		$this->ipsclass->html .= "<div class='tableborder'><div class='tableheaderalt'>Filters</div>";
		$this->ipsclass->html .= "<div class='tablerow2' style='padding:4px'>
							<table width='100%' cellpadding='2' cellspacing='0' border='0'>
							<tr>
							 <td width='30%'>
							  <fieldset>
							  <legend><strong>Send to Groups:</strong></legend>";
							  
		foreach( $this->ipsclass->cache['group_cache'] as $g )
		{
			if ( $g['g_id'] == $this->ipsclass->vars['guest_group'] )
			{
				continue;
			}
			
			$checked = "";
			
			if ( $mail['mail_groups'] )
			{
				if ( strstr( ','.$mail['mail_groups'].',', ','.$g['g_id'].',' ) )
				{
					$checked = 'checked="checked"';
				}
			}
			
			$this->ipsclass->html .= "<input type='checkbox' name='sg_{$g['g_id']}' value='1' $checked />&nbsp;&nbsp;<b>{$g['g_title']}</b><br />";
		}
							  
		$this->ipsclass->html .= "  </fieldset> 
							 </td>
							 <td width='70%' valign='top'>
							  <fieldset>
							  <legend><strong>Additional Filters</strong></legend>
							  <table width='100%' cellpadding='4' cellspacing='0' border='0'>
							  <tr>
							   <td width='60%'>Send to members with <em><b>n</b></em> posts<div class='graytext'>Leave blank to not filter</div></td>
							   <td width='40%'>". $this->ipsclass->adskin->form_dropdown('mail_post_ltmt', $dd_ltmt, $_POST['mail_post_ltml'] ? $_POST['mail_post_ltml'] : $mail['mail_post_ltmt'] ).' '.
							   					  $this->ipsclass->adskin->form_simple_input( "mail_filter_post", $_POST['mail_filter_post'] ? $_POST['mail_filter_post'] : $mail['mail_filter_post'], 7 )."</td>
							  </tr>
							  <tr>
							   <td width='60%'>Send to members who's last visit was <em><b>n</b></em> days ago<div class='graytext'>Leave blank to not filter</div></td>
							   <td width='40%'>". $this->ipsclass->adskin->form_dropdown('mail_visit_ltmt', $dd_ltmt, $_POST['mail_visit_ltml'] ? $_POST['mail_visit_ltml'] : $mail['mail_visit_ltmt'] ).' '.
							   					  $this->ipsclass->adskin->form_simple_input( "mail_filter_visit", $_POST['mail_filter_visit'] ? $_POST['mail_filter_visit'] : $mail['mail_filter_visit'], 7 )."</td>
							  </tr>
							  <tr>
							   <td width='60%'>Send to members who joined <em><b>n</b></em> days ago<div class='graytext'>Leave blank to not filter</div></td>
							   <td width='40%'>". $this->ipsclass->adskin->form_dropdown('mail_joined_ltmt', $dd_ltmt, $_POST['mail_joined_ltml'] ? $_POST['mail_joined_ltml'] : $mail['mail_joined_ltmt'] ).' '.
							   					  $this->ipsclass->adskin->form_simple_input( "mail_filter_joined", $_POST['mail_filter_joined'] ? $_POST['mail_filter_joined'] : $mail['mail_filter_joined'], 7 )."</td>
							  </tr>
							  </table>
							  </fieldset>
							 </td>
							</tr>
							</table>
							</div>
							<div align='center' class='tablesubheader'><input class='realbutton' onclick='runpreview()' type='button' value='Preview' /> &nbsp; &nbsp; <input class='realdarkbutton' type='submit' value='$button' /></form></div>
							</div>";
		
		$this->ipsclass->html .= "<br />
							<div class='tableborder'>
							<div class='tablesubheader'>Quick Tags</div>
							<div class='tablerow1' style='padding:4px'>You may use the following 'quick tags' in your email and they will be converted when the email is sent.</div>
							<table cellpadding='2' class='tablerow1' width='100%' cellspacing='0' border='0'>
							<tr>
							 <td><strong>{board_name}</strong></td>
							 <td><em>The name of your board</em></td>
							 <td><strong>{board_url}</strong></td>
							 <td><em>The URL to your board</em></td>
							</tr>
							<tr>
							 <td><strong>{reg_total}</strong></td>
							 <td><em>The total number of registered members</em></td>
							 <td><strong>{total_posts}</strong></td>
							 <td><em>The total number of posts</em></td>
							</tr>
							<tr>
							 <td><strong>{busy_count}</strong></td>
							 <td><em>The most number of users online</em></td>
							 <td><strong>{busy_time}</strong></td>
							 <td><em>The time that the most number of members were online</em></td>
							</tr>
							<tr>
							 <td><strong>{member_id}</strong></td>
							 <td><em>The member ID of the email recipient</em></td>
							 <td><strong>{member_name}</strong></td>
							 <td><em>The member name of the email recipient</em></td>
							</tr>
							<tr>
							 <td><strong>{member_joined}</strong></td>
							 <td><em>The member join date of the email recipient</em></td>
							 <td><strong>{member_posts}</strong></td>
							 <td><em>The number of posts for the email recipient</em></td>
							</tr>
							</table>
							</div>";
							
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	// SHOW MAIN MAIL SCREENIE-POOS
	//-----------------------------------------
	
	function mail_start()
	{
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"      , "1%" );
		$this->ipsclass->adskin->td_header[] = array( "Subject"     , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Sent On"     , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Sent To"     , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Time Taken"  , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"     , "1%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Your stored bulk email" );
		
		//-----------------------------------------
		// Get mail from DB
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'bulk_mail', 'order' => 'mail_start DESC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			
			$inprogress = "";
			
			if ( $r['mail_updated'] == $r['mail_start'] )
			{
				$time_taken = 'Not yet sent';
			}
			else
			{
				$time_taken = intval($r['mail_updated'] - $r['mail_start']);
				
				if ( $time_taken < 0 )
				{
					$time_taken = 0;
				}
				
				if ( $time_taken )
				{
					$time_taken = ceil( $time_taken / 60 );
				}
				
				$time_taken .= ' minutes';
			}
			
			if ( $r['mail_active'] )
			{
				$inprogress = " <em>( Sending in progress - <a href='#' onclick=\"maincheckdelete('{$this->ipsclass->base_url}&{$this->ipsclass->form_code_js}&code=mail_send_cancel', 'OK to cancel bulk mail?'); return false;\">Cancel</a> )</em>";
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
																				 "<img src='{$this->ipsclass->adskin->img_url}/images/skin_icon_email.gif' border='0' />",
																				 "<b>{$r['mail_subject']}</b> $inprogress",
																				 $this->ipsclass->get_date( $r['mail_start'], 'SHORT' ),
																				 $this->ipsclass->do_number_format( $r['mail_sentto'] ).' members',
																				 $time_taken,
																				 "<div align='center'><img id='mid-{$r['mail_id']}' src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></div>"
																		)      );
													    	
			$this->ipsclass->html .= <<<EOF
										 <script type="text/javascript">
										 menu_build_menu(
										 "mid-{$r['mail_id']}",
										 new Array(
										 	img_item   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=mail_send_start&id={$r['mail_id']}'>Re-send...</a>",
										 	img_edit   + " <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=mail_edit&id={$r['mail_id']}'>Edit...</a>",
										 	img_delete   + " <a href='#' onclick=\"maincheckdelete('{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=mail_delete&id={$r['mail_id']}');return false;\">Delete...</a>"
										 ) );
										 </script>
EOF;
		}						 
		
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	
	}
	
	//-----------------------------------------
	// INTERNAL: build members query
	//-----------------------------------------
	
	function _build_members_query( $args = array() )
	{
		$this->ipsclass->DB->load_cache_file( $this->root_path.'sources/sql/'.SQL_DRIVER.'_extra_queries.php', 'sql_extra_queries' );
		
		$query = array();
		
		if ( is_numeric($args['mail_filter_post']) )
		{
			$ltmt    = $args['mail_post_ltmt'] == 'lt' ? '<' : '>';
			$query[] = "posts ".$ltmt." ".intval($args['mail_filter_post']);
		}
		
		if ( $args['mail_filter_visit'] )
		{
			$ltmt    = $args['mail_visit_ltmt'] == 'lt' ? '>' : '<';
			$time    = time() - ( $args['mail_filter_visit'] * 86400 );
			$query[] = "last_visit ".$ltmt." ". $time;
		}
		
		if ( $args['mail_filter_joined'] )
		{
			$ltmt    = $args['mail_joined_ltmt'] == 'lt' ? '>' : '<';
			$time    = time() - ( $args['mail_filter_joined'] * 86400 );
			$query[] = "joined ".$ltmt." ". $time;
		}
		
		if ( $args['mail_honor'] )
		{
			$query[] = "allow_admin_mails=1";
		}
		
		if ( $args['mail_groups'] )
		{
			$tmp_q = '(mgroup IN ('. $args['mail_groups'] .')';
			
			$temp  = explode( ',', $args['mail_groups'] );
			
			if ( is_array( $temp ) and count( $temp ) )
			{
				$tmp = array();
				
				foreach( $temp as $id )
				{
					$this->ipsclass->DB->cache_add_query( 'acp_postoffice_concat_bit', array( 'gid' => $id ), 'sql_extra_queries' );
					$tmp[] = $this->ipsclass->DB->cur_query;
					$this->ipsclass->DB->cur_query = "";
				}
				
				$tmp_q .= " OR ( ".implode( ' OR ', $tmp ). " ) )";
			}
			else
			{
				$tmp_q .= ")";
			}
			
			$query[] = $tmp_q;
		}
	
		return implode( ' AND ', $query );
	}
	
	//-----------------------------------------
	// INTERNAL: convert quick tags
	//-----------------------------------------
	
	function _convert_quick_tags( $contents="", $member=array() )
	{
		$contents = str_replace( "{board_name}"   , str_replace( "&#39;", "'", $this->ipsclass->vars['board_name'] ) , $contents );
		$contents = str_replace( "{board_url}"    , $this->ipsclass->vars['board_url']."/index.".$this->ipsclass->vars['php_ext'] , $contents );
		$contents = str_replace( "{reg_total}"    , $this->ipsclass->cache['stats']['mem_count'] , $contents );
		$contents = str_replace( "{total_posts}"  , $this->ipsclass->cache['stats']['total_topics'] + $this->ipsclass->cache['stats']['total_replies'] , $contents );
		$contents = str_replace( "{busy_count}"   , $this->ipsclass->cache['stats']['most_count'] , $contents );
		$contents = str_replace( "{busy_time}"    , $this->ipsclass->get_date( $this->ipsclass->cache['stats']['most_date'], 'SHORT' ), $contents );
		$contents = str_replace( "{member_id}"    , $member['id'], $contents );
		$contents = str_replace( "{member_name}"  , $member['members_display_name'], $contents );
		$contents = str_replace( "{member_joined}", date( 'j-F y', $member['joined'] ), $contents );
		$contents = str_replace( "{member_posts}" , $member['posts'], $contents );
		
		return $contents;
	}
	
	//-----------------------------------------
	// INTERNAL: get default mail
	//-----------------------------------------
	
	function _get_default_mail_contents()
	{
		$mail = "{member_name},\n\n\n\n-------------------------------------\n{board_name} Statistics:\n"
			  ."-------------------------------------\nRegistered Users: {reg_total}\nTotal Posts: {total_posts}\n"
			  ."Busiest Time: {busy_count} users were online on {busy_time}\n\n"
			  ."-------------------------------------\nHandy Links\n"
			  ."-------------------------------------\nBoard Address: {board_url}\nLog In: {board_url}?act=Login&CODE=00\n"
			  ."Lost Password Recovery: {board_url}?act=Reg&CODE=10\n\n"
			  ."-------------------------------------\nHow to unsubscribe\n"
			  ."-------------------------------------\nVisit your email preferences ({board_url}?act=UserCP&CODE=02) and ensure "
			  ."that the box for 'Send me any updates sent by the board administrator' is unchecked and submit the form";
			  
		return $mail;
	
	}
	
	
}


?>