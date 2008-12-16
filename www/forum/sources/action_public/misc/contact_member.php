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
|   > $Date: 2007-05-15 17:56:01 -0400 (Tue, 15 May 2007) $
|   > $Revision: 997 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > ICQ / AIM / EMAIL functions
|   > Module written by Matt Mecham
|   > Date started: 28th February 2002
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

class contactmember
{

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
    
    var $nav       = array();
    var $page_title= "";
    var $email     = "";
    var $forum     = "";

	var $int_error  = "";
	var $int_extra  = "";
	
    /*-------------------------------------------------------------------------*/
	//
	// Our constructor, load words, load skin
	//
	/*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
		// What to do?
        
        switch($this->ipsclass->input['act'])
        {
        	case 'Mail':
        		$this->mail_member();
        		break;
        	
        	case 'chat':
        		if ( $this->ipsclass->vars['chat_account_no'] )
				{
					$this->chat_display();
				}
				else if ( $this->ipsclass->vars['chat04_account_no'] )
				{
					if ( $this->ipsclass->input['CODE'] == 'update' )
					{
						$this->chat04_refresh();
					}
					else
					{
						$this->chat04_display();
					}
				}
        		break;
        	
        	case 'report':
        		if ($this->ipsclass->input['send'] != 1)
        		{
        			$this->report_form();
        		}
        		else
        		{
        			$this->send_report();
        		}
        		break;
        		
        	case 'boardrules':
        		$this->board_rules();
        		break;
        	
        	default:
        		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
        		break;
        }
        
        $this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
        
	}
	
	/*-------------------------------------------------------------------------*/
	// BOARD RULES
	//
	/*-------------------------------------------------------------------------*/
        
        
	function board_rules()
	{
		//-----------------------------------------
		// Get board rule (not cached)
		//-----------------------------------------
		
		$row = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => "conf_key='gl_guidelines'" ) );
		
		$this->ipsclass->load_language('lang_emails');
		$this->ipsclass->load_template('skin_emails');
		
		$row['conf_value'] = $this->ipsclass->my_nl2br(stripslashes($row['conf_value']));
		
		$this->nav[] = $this->ipsclass->vars['gl_title'];
        
        $this->page_title = $this->ipsclass->vars['gl_title'];
        
        $this->output .= $this->ipsclass->compiled_templates['skin_emails']->board_rules( $this->ipsclass->vars['gl_title'], $row['conf_value'] );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// IP CHAT04: Refresh useronline
	//
	/*-------------------------------------------------------------------------*/
        
	function chat04_refresh()
	{
		//-----------------------------------------
		// Okay: refresh every 90 seconds
		//-----------------------------------------
		
		if ( $this->ipsclass->lastclick > ( time() - 3600 ) )
		{
			//-----------------------------------------
			// Our last click was more recent than an hour!
			//-----------------------------------------
			
			if ( ! strstr( $this->ipsclass->location, 'chat' ) )
			{
				//-----------------------------------------
				// And we're no longer in chat
				// .... put 'em back!
				//-----------------------------------------
				
				$this->ipsclass->DB->do_update( 'sessions', array( 'location' => 'chat,' ), "id='".$this->ipsclass->my_session."'" );
			}
		}
		
		//-----------------------------------------
		// Stop cycling after 2 hours of no activity
		//-----------------------------------------
		
		if ( $this->ipsclass->lastclick > ( time() - 7200 ) )
		{
			//-----------------------------------------
			// Print out the 'blank' gif
			//-----------------------------------------
			
			@header( "Content-Type: text/html" );
			print "<html><head><meta http-equiv='refresh' content='90; url={$this->ipsclass->base_url}act=chat&CODE=update'></head><body></body></html>";
			exit();
		}
		
	}
	

	
	
	
	/*-------------------------------------------------------------------------*/
	// IP CHAT:
	//
	/*-------------------------------------------------------------------------*/
        
        
	function chat_display()
	{
		$this->ipsclass->load_language('lang_emails');

		$this->ipsclass->load_template('skin_emails');
		
		if ( ! $this->ipsclass->vars['chat_account_no'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		if ( ! $this->ipsclass->vars['chat_server_addr'] )
		{
			$this->ipsclass->vars['chat_server_addr'] = 'client1.invisionchat.com';
		}
		
		$this->ipsclass->vars['chat_server_addr'] = str_replace( 'http://', '', $this->ipsclass->vars['chat_server_addr'] );
		
		$width  = $this->ipsclass->vars['chat_width']    ? $this->ipsclass->vars['chat_width']  : 600;
		$height = $this->ipsclass->vars['chat_height']   ? $this->ipsclass->vars['chat_height'] : 350;
		
		$lang   = $this->ipsclass->vars['chat_language'] ? $this->ipsclass->vars['chat_language'] : 'en';
		
		$user = "";
		$pass = "";
		
		if ( $this->ipsclass->member['id'] )
		{
			$user = $this->ipsclass->member['members_display_name'];
			
			$converge_member = $this->ipsclass->converge->converge_load_member_by_id($this->ipsclass->member['id']);
			$pass = $this->ipsclass->converge->member['converge_pass_hash'];
		}
		
		if ( $this->ipsclass->input['pop'] )
		{
			$html = $this->ipsclass->compiled_templates['skin_emails']->chat_pop( $this->ipsclass->vars['chat_server_addr'], $this->ipsclass->vars['chat_account_no'], $lang, $width, $height, $user, $pass );
			
			$this->ipsclass->print->pop_up_window( "CHAT", $html );
			
			exit();
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_emails']->chat_inline( $this->ipsclass->vars['chat_server_addr'], $this->ipsclass->vars['chat_account_no'], $lang, $width, $height, $user, $pass);
		}
		
        $this->nav[] = $this->ipsclass->lang['live_chat'];
        
        $this->page_title = $this->ipsclass->lang['live_chat'];
		
	}
	
	
	
	
	/*-------------------------------------------------------------------------*/
	// REPORT POST FORM:
	//
	/*-------------------------------------------------------------------------*/
        
        
	function report_form()
	{
		$this->ipsclass->load_language('lang_emails');

		$this->ipsclass->load_template('skin_emails');
		
		$pid = intval($this->ipsclass->input['p']);
		$tid = intval($this->ipsclass->input['t']);
		$st  = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		if ( (!$pid) and (!$tid) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		// Do we have permission to do stuff in this forum? Lets hope so eh?!
		
		$this->check_access($tid);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_emails']->report_form($tid, $pid, $st, $this->topic['topic_title']);
		
        $this->nav[] = "<a href='".$this->ipsclass->base_url."showforum={$this->forum['id']}'>{$this->forum['name']}</a>";
        $this->nav[] = $this->ipsclass->lang['report_title'];
        
        $this->page_title = $this->ipsclass->lang['report_title'];
		
	}
	
	
	function send_report()
	{
		$this->ipsclass->load_language('lang_emails');

		$this->ipsclass->load_template('skin_emails');
		
		$pid = intval($this->ipsclass->input['p']);
		$tid = intval($this->ipsclass->input['t']);
		$fid = intval($this->ipsclass->input['f']);
		$st  = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
		
		if ( (!$pid) and (!$tid) and (!$fid) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//-----------------------------------------
		// Make sure we came in via a form.
		//-----------------------------------------
		
		if ( $_POST['message'] == "" )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form') );
		}
		
		//-----------------------------------------
		// Get the topic title
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'title', 'from' => 'topics', 'where' => "tid=".$tid ) );
		$this->ipsclass->DB->simple_exec();
		
		$topic = $this->ipsclass->DB->fetch_row();
		
		if ( ! $topic['title'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
		}
		
		//-----------------------------------------
		// Do we have permission to do stuff in this
		// forum? Lets hope so eh?!
		//-----------------------------------------
		
		$this->check_access($tid);
		
		$mods = array();
		$fid  = $this->forum['id'];
		
		//-----------------------------------------
		// Check for mods in this forum
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'contact_member_report_get_mods', array( 'fid' => $fid ) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$mods[ $r['id'] ] = $r;
			}
		}
		else
		{
			//-----------------------------------------
			// No mods? Get those super moderators
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'contact_member_report_get_supmod', array() );
			$this->ipsclass->DB->cache_exec_query();
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				while( $r = $this->ipsclass->DB->fetch_row() )
				{
					$mods[ $r['id'] ] = $r;
				}
			}
			else
			{
				//-----------------------------------------
				// No supmods? Get those with control panel access
				//-----------------------------------------
				
				$this->ipsclass->DB->cache_add_query( 'contact_member_report_get_cpaccess', array() );
				$this->ipsclass->DB->cache_exec_query();
				
				while( $r = $this->ipsclass->DB->fetch_row() )
				{
					$mods[ $r['id'] ] = $r;
				}
			}
		}
		
		//-----------------------------------------
    	// Get the emailer module
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/classes/class_email.php" );
		
		$this->email           = new emailer();
		$this->email->ipsclass =& $this->ipsclass;
		$this->email->email_init();
		
		require_once( ROOT_PATH.'sources/lib/func_msg.php' );
 		
		$this->lib           =  new func_msg();
		$this->lib->ipsclass =& $this->ipsclass;
		
		$this->lib->init();
 				
		//-----------------------------------------
		// Loop and send the mail
		//-----------------------------------------
		
		$report = trim(stripslashes( $this->ipsclass->my_br2nl( $this->ipsclass->input['message'] ) ) );

		$report = $this->ipsclass->my_nl2br( htmlspecialchars($report, ENT_COMPAT) );
		
		$report = str_replace( "&amp;quot;", "&quot;", $report );
		$report = str_replace( "&amp;amp;", "&amp;", $report );
		
		foreach( $mods as $data )
		{
			$this->email->get_template("report_post");
				
			$this->email->build_message( array(
												'MOD_NAME'     => $data['name'],
												'USERNAME'     => $this->ipsclass->member['members_display_name'],
												'TOPIC'        => $topic['title'],
												'LINK_TO_POST' => "{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}"."?showtopic={$tid}&st={$st}#entry{$pid}",
												'REPORT'       => $report,
											  )
			        					);
			        					
			//-----------------------------------------
			// Email?
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['reportpost_method'] == 'email' OR $data['members_disable_pm'] )
			{
				$this->email->message = $this->ipsclass->my_br2nl( $this->email->message );

				$this->email->subject = $this->ipsclass->lang['report_subject'].' '.$this->ipsclass->vars['board_name'];
				$this->email->to      = $data['email'];
				
				$this->email->send_mail();
			}
			
			//-----------------------------------------
			// PM?
			//-----------------------------------------
			
			else
			{
				$this->lib->to_by_id    = $data['id'];
 				$this->lib->from_member = $this->ipsclass->member;
 				$this->lib->msg_title   = $this->ipsclass->lang['report_subject'].' '.$topic['title'];
 				
 				$this->lib->postlib->parser->parse_bbcode 	= 1;
 				$this->lib->postlib->parser->parse_smilies 	= 0;
 				$this->lib->postlib->parser->parse_html 	= 0;
 				$this->lib->postlib->parser->parse_nl2br 	= 1;
 				$this->lib->msg_post    = $this->lib->postlib->parser->pre_display_parse( $this->lib->postlib->parser->pre_db_parse( htmlspecialchars( $this->email->message, ENT_QUOTES ) ) );
 				
				$this->lib->force_pm    = 1;
				
				$this->lib->send_pm();
				
				if ( $this->lib->error )
				{
					print $this->error;
					exit();
				}
			}
		}
			
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['report_redirect'], "showtopic={$tid}&amp;st={$st}&amp;#entry$pid");					   
	}
	
	//-----------------------------------------
	
     
    function check_access($tid)
    {
		if ( ! $this->ipsclass->member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Needs silly a. alias to keep oracle
		// happy
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'a.*,a.title as topic_title', 'from' => 'topics a', 'where' => "a.tid=".$tid ) );
		$this->ipsclass->DB->simple_exec();
        
        $this->topic = $this->ipsclass->DB->fetch_row();
        
        $this->forum = $this->ipsclass->forums->forum_by_id[ $this->topic['forum_id'] ];
		
		$return = 1;
		
		if ( $this->ipsclass->check_perms($this->forum['read_perms']) == TRUE )
		{
			$return = 0;
		}
		
		if ($this->forum['password'])
		{
			if ($_COOKIE[ $this->ipsclass->vars['cookie_id'].'iBForum'.$this->forum['id'] ] == $this->forum['password'])
			{
				$return = 0;
			}
		}
		
		if ($return == 1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// MAIL MEMBER:
	//
	// Handles the routines called by clicking on the "email" button when
	// reading topics
	/*-------------------------------------------------------------------------*/
	
	function mail_member()
	{
		require "./sources/classes/class_email.php";
		$this->email = new emailer();
		$this->email->ipsclass =& $this->ipsclass;
		$this->email->email_init();
		
		//-----------------------------------------
		
		$this->ipsclass->load_language('lang_emails');

		$this->ipsclass->load_template('skin_emails');
		
		//-----------------------------------------
	
		if (empty($this->ipsclass->member['id']))
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
		
		if ( ! $this->ipsclass->member['g_email_friend'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_member_mail' ) );
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->input['CODE'] == '01')
		{
		
			$this->mail_member_send();
			
		}
		else
		{
			// Show the form, booo...
			
			$this->mail_member_form();

		}
		
	}
	
	function mail_member_form($errors="", $extra = "")
	{
		$this->ipsclass->input['MID'] = intval($this->ipsclass->input['MID']);
		
		if ( $this->ipsclass->input['MID'] < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'name, members_display_name, id, email, hide_email', 'from' => 'members', 'where' => "id=".$this->ipsclass->input['MID'] ) );
		$this->ipsclass->DB->simple_exec();
		
		$member = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		
		if (! $member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		if ($member['hide_email'] == 1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'private_email' ) );
		}
		
		//-----------------------------------------
		
		if ( $errors != "" )
		{
			$msg = $this->ipsclass->lang[$errors];
			
			if ( $extra != "" )
			{
				$msg = str_replace( "<#EXTRA#>", $extra, $msg );
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_emails']->errors( $msg );
		}
		
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->vars['use_mail_form']
					  ? $this->ipsclass->compiled_templates['skin_emails']->send_form(
												  array(
														  'NAME'   => $member['members_display_name'],
														  'TO'     => $member['id'],
														  'subject'=> $this->ipsclass->input['subject'],
														  'content'=> stripslashes(htmlspecialchars($_POST['message'])),
													   )
											   )
					  : $this->ipsclass->compiled_templates['skin_emails']->show_address(
												  array(
														  'NAME'    => $member['members_display_name'],
														  'ADDRESS' => $member['email'],
													   )
												 );
												 
		$this->page_title = $this->ipsclass->lang['member_address_title'];
		$this->nav        = array( $this->ipsclass->lang['member_address_title'] );

		
	}
	
	//-----------------------------------------
	
	function mail_member_send()
	{
		$this->ipsclass->input['to'] = intval($this->ipsclass->input['to']);
	
		if ( $this->ipsclass->input['to'] == 0 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'invalid_use' ) );
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'name, members_display_name, id, email, hide_email', 'from' => 'members', 'where' => "id=".$this->ipsclass->input['to'] ) );
		$this->ipsclass->DB->simple_exec();
		
		$member = $this->ipsclass->DB->fetch_row();
		
		//-----------------------------------------
		// Check for schtuff
		//-----------------------------------------
		
		if (! $member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user' ) );
		}
		
		//-----------------------------------------
		
		if ($member['hide_email'] == 1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'private_email' ) );
		}
		
		//-----------------------------------------
		// Check for blanks
		//-----------------------------------------
		
		$check_array = array ( 
							   'message'   =>  'no_message',
							   'subject'   =>  'no_subject'
							 );
						 
		foreach ($check_array as $input => $msg)
		{
			if (empty($this->ipsclass->input[$input]))
			{
				$this->ipsclass->input['MID'] = $this->ipsclass->input['to'];
				$this->mail_member_form($msg);
				return;
			}
		}
		
		//-----------------------------------------
		// Check for spam / delays
		//-----------------------------------------
		
		$email_check = $this->_allow_to_email( $this->ipsclass->member['id'], $this->ipsclass->member['g_email_limit'] );
		
		if ( $email_check != TRUE )
		{
			$this->ipsclass->input['MID'] = $this->ipsclass->input['to'];
			$this->mail_member_form( $this->int_error, $this->int_extra);
			return;
		}
		
		//-----------------------------------------
		// Send the email
		//-----------------------------------------
		
		$this->email->get_template("email_member");
			
		$this->email->build_message( array(
											'MESSAGE'     => str_replace( "<br>", "\n", str_replace( "\r", "", $this->ipsclass->input['message'] ) ),
											'MEMBER_NAME' => $member['members_display_name'],
											'FROM_NAME'   => $this->ipsclass->member['members_display_name']
										  )
									);
									
		$this->email->subject = $this->ipsclass->input['subject'];
		$this->email->to      = $member['email'];
		$this->email->from    = $this->ipsclass->member['email'];
		$this->email->send_mail();
		
		//-----------------------------------------
		// Store email in the database
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'email_logs', array( 
											'email_subject'      => $this->ipsclass->input['subject'],
											'email_content'      => $this->ipsclass->input['message'],
											'email_date'         => time(),
											'from_member_id'     => $this->ipsclass->member['id'],
											'from_email_address' => $this->ipsclass->member['email'],
											'from_ip_address'	 => $this->ipsclass->input['IP_ADDRESS'],
											'to_member_id'		 => $member['id'],
											'to_email_address'	 => $member['email'],
					  )                   );
					
		//-----------------------------------------
		// Print the success page
		//-----------------------------------------
		
		$forum_jump = $this->ipsclass->build_forum_jump();
		
		$this->output  = $this->ipsclass->compiled_templates['skin_emails']->sent_screen($member['members_display_name']);
		
		$this->output .= $this->ipsclass->compiled_templates['skin_emails']->forum_jump($forum_jump);
		
		$this->page_title = $this->ipsclass->lang['email_sent'];
		$this->nav        = array( $this->ipsclass->lang['email_sent'] );
	}
	
	
	//-----------------------------------------
	// CHECK FLOOD LIMIT
	// Returns TRUE if able to email
	// FALSE if not
	//-----------------------------------------
	
	function _allow_to_email($member_id, $email_limit)
	{
		$member_id = intval($member_id);
		
		if ( ! $member_id )
		{
			$this->int_error = 'gen_error';
			return FALSE;
		}
		
		list( $limit, $flood ) = explode( ':', $email_limit );
		
		if ( ! $limit and ! $flood )
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// Get some stuff from the DB!
		// 1) FLOOD?
		//-----------------------------------------
		
		if ( $flood )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
										  'from'   => 'email_logs',
										  'where'  => "from_member_id=$member_id",
										  'order'  => 'email_date DESC',
										  'limit'  => array(0,1) ) );
			$this->ipsclass->DB->simple_exec();
		
			$last_email = $this->ipsclass->DB->fetch_row();

			if ( $last_email['email_date'] + ($flood * 60) > time() )
			{
				$this->int_error = 'exceeded_flood';
				$this->int_extra = $flood;
				return FALSE;
			}
		}
		
		if ( $limit )
		{
			$time_range = time() - 86400;
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'count(email_id) as cnt',
										  'from'   => 'email_logs',
										  'where'  => "from_member_id=$member_id AND email_date > $time_range",
								 )      );
			$this->ipsclass->DB->simple_exec();
			
			$quota_sent = $this->ipsclass->DB->fetch_row();
			
			if ( $quota_sent['cnt'] + 1 > $limit )
			{
				$this->int_error = 'exceeded_quota';
				$this->int_extra = limit;
				return FALSE;
			}
		}
		
		return TRUE; //<{%dyn.down.var.md5p2%}> If we get here...
        		
	}

}






?>