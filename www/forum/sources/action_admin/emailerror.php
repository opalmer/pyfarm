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
|   > $Date: 2007-01-16 17:58:41 -0500 (Tue, 16 Jan 2007) $
|   > $Revision: 830 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Email Error Logs Stuff
|   > Module written by Matt Mecham
|   > Date started: 7th April 2004
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


class ad_emailerror
{
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "admin";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "emailerror";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Email Error Logs' );
		
		//-----------------------------------------
		// Make sure we're a root admin, or else!
		//-----------------------------------------
		
		if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
		{
			//$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}
		
		switch($this->ipsclass->input['code'])
		{
			case 'list':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_current();
				break;
				
			case 'remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove_entries();
				break;
				
		    case 'viewemail':
		    	$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
		    	$this->view_email();
		    	break;
				
				
			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_current();
				break;
		}
		
	}
	
	//-----------------------------------------
	// View a single email.
	//-----------------------------------------
	
	function view_email()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the email ID, please try again");
		}
		
		$id = intval($this->ipsclass->input['id']);
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'mail_error_logs', 'where' => "mlog_id=$id" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the email ID, please try again ($id)");
		}
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $row['mlog_subject'] );
	
		$row['mlog_date']    = $this->ipsclass->admin->get_date( $row['mlog_date'], 'LONG' );
		$row['mlog_content'] = nl2br($row['mlog_content']);
		
		$row['mlog_msg']        = $row['mlog_msg']        ? $row['mlog_msg']        : '<em>No Info</em>';
		$row['mlog_code']       = $row['mlog_code']       ? $row['mlog_code']       : '<em>No Info</em>';
		$row['mlog_smtp_error'] = $row['mlog_smtp_error'] ? $row['mlog_smtp_error'] : '<em>No Info</em>';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
																			  "<strong>From:</strong> &lt;{$row['mlog_from']}&gt;
																			  <br /><strong>To:</strong> &lt;{$row['mlog_to']}&gt;
																			  <br /><strong>Sent:</strong> {$row['mlog_date']}
																			  <br /><strong>Subject:</strong> {$row['mlog_subject']}
																			  <hr />
																			  <br />{$row['mlog_content']}....
																			  <hr />
																			  <br />IPB ERROR: {$row['mlog_msg']}
																			  <br />SMTP CODE: {$row['mlog_code']}
																			  <br />SMTP ERROR: {$row['mlog_smtp_error']}
																			  "
																   )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
	}
	
	//-----------------------------------------
	// Remove row(s)
	//-----------------------------------------
	
	function remove_entries()
	{
		if ( $this->ipsclass->input['type'] == 'all' )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'mail_error_logs' ) );
		}
		else
		{
			$ids = array();
		
			foreach ($this->ipsclass->input as $k => $v)
			{
				if ( preg_match( "/^id_(\d+)$/", $k, $match ) )
				{
					if ($this->ipsclass->input[ $match[0] ])
					{
						$ids[] = $match[1];
					}
				}
			}
			
			$ids = $this->ipsclass->clean_int_array( $ids );
			
			//-----------------------------------------
			
			if ( count($ids) < 1 )
			{
				$this->ipsclass->admin->error("You did not select any email log entries to approve or delete");
			}
			
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'mail_error_logs', 'where' => "mlog_id IN (".implode(',', $ids ).")" ) );
		}
		
		$this->ipsclass->admin->save_log("Removed email error log entries");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");
	}
	
	//-----------------------------------------
	// SHOW EMAIL ERROR LOGS
	//-----------------------------------------
	
	function list_current()
	{
		$this->ipsclass->html .= ""; // removed js popwin
		
		$form_array = array();
		
		$start = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
	
		$this->ipsclass->admin->page_detail = "Stored email error logs";
		$this->ipsclass->admin->page_title  = "Email Error Logs Manager";
		
		//-----------------------------------------
		// Check URL parameters
		//-----------------------------------------
		
		$url_query = array();
		$db_query  = array();
		
		if ( isset($this->ipsclass->input['type']) AND $this->ipsclass->input['type'] != "" )
		{
			$this->ipsclass->admin->page_title .= " (Search Results)";
		
			switch( $this->ipsclass->input['type'] )
			{
				case 'subject':
					$string = urldecode($this->ipsclass->input['string']);
					if ( $string == "" )
					{
						$this->ipsclass->admin->error("You must enter something to search by");
					}
					$url_query[] = 'type='.$this->ipsclass->input['type'];
					$url_query[] = 'string='.urlencode($string);
					$db_query[]  = $this->ipsclass->input['match'] == 'loose' ? "mlog_subject LIKE '%". preg_replace_callback( '/([=_\?\x00-\x1F\x80-\xFF])/', create_function( '$match', 'return "=" . strtoupper( dechex( ord( "$match[1]" ) ) );' ), $string ) ."%'" : "mlog_subject='{$string}'";
					break;
				case 'email_from':
					$string = urldecode($this->ipsclass->input['string']);
					if ( $string == "" )
					{
						$this->ipsclass->admin->error("You must enter something to search by");
					}
					$url_query[] = 'type='.$this->ipsclass->input['type'];
					$url_query[] = 'string='.urlencode($string);
					$db_query[]  = $this->ipsclass->input['match'] == 'loose' ? "mlog_from LIKE '%{$string}%'" : "mlog_from='{$string}'";
					break;
				case 'email_to':
					$string = urldecode($this->ipsclass->input['string']);
					if ( $string == "" )
					{
						$this->ipsclass->admin->error("You must enter something to search by");
					}
					$url_query[] = 'type='.$this->ipsclass->input['type'];
					$url_query[] = 'string='.urlencode($string);
					$db_query[]  = $this->ipsclass->input['match'] == 'loose' ? "mlog_to LIKE '%{$string}%'" : "mlog_to='{$string}'";
					break;
				case 'error':
					$string = urldecode($this->ipsclass->input['string']);
					if ( $string == "" )
					{
						$this->ipsclass->admin->error("You must enter something to search by");
					}
					$url_query[] = 'type='.$this->ipsclass->input['type'];
					$url_query[] = 'string='.urlencode($string);
					$db_query[]  = $this->ipsclass->input['match'] == 'loose' ? "mlog_msg LIKE '%{$string}%' or mlog_smtp_msg LIKE '%{$string}%'" : "mlog_msg='{$string} or mlog_smtp_msg='{$string}'";
					break;
				
				default:
					//
					break;
			}
		}

		//-----------------------------------------
		// LIST 'EM
		//-----------------------------------------
		
		$dbe = "";
		$url = "";
		
		if ( count($db_query) > 0 )
		{
			$dbe = implode(' AND ', $db_query );
		}
		
		if ( count($url_query) > 0 )
		{
			$url = '&'.implode( '&', $url_query);
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as cnt', 'from' => 'mail_error_logs', 'where' => $dbe ) );
		$this->ipsclass->DB->simple_exec();
		
		$count = $this->ipsclass->DB->fetch_row();
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $count['cnt'],
											   'PER_PAGE'    => 25,
											   'CUR_ST_VAL'  => $start,
											   'L_SINGLE'    => "Single Page",
											   'L_MULTI'     => "Pages: ",
											   'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}".$url,
											 )
									  );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'mail_error_logs', 'where' => $dbe, 'order' => 'mlog_date DESC', 'limit' => array( $start, 25 ) ) );
		$this->ipsclass->DB->simple_exec();
		
		$this->ipsclass->html .= "<script type='text/javascript'>
									function checkall( )
									{
										var formobj = document.getElementById('theAdminForm');
										var checkboxes = formobj.getElementsByTagName('input');
									
										for ( var i = 0 ; i <= checkboxes.length ; i++ )
										{
											var e = checkboxes[i];
											var docheck = formobj.checkme.checked;
											
											if ( e && (e.type == 'checkbox') && (! e.disabled) && (e.id != 'checkme') && (e.name != 'type') )
											{
												if( docheck == false )
												{
													e.checked = false;
												}
												else
												{
													e.checked = true;
												}
											}
										}
										
										return false;
									}
								  </script>";		
					
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'remove'     ),
																			 2 => array( 'act'   , 'emailerror' ),
																			 3 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
		$this->ipsclass->adskin->td_header[] = array( "<input type='checkbox' onclick='checkall();' id='checkme' />"         , "1%" );
		$this->ipsclass->adskin->td_header[] = array( "To"             , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Subject"        , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Error MSG"      , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Date"           , "20%" );

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Logged Emails Errors" );
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['mlog_date'] = $this->ipsclass->admin->get_date( $row['mlog_date'], 'SHORT' );
				
				$row['mlog_subject'] = ( strpos( $row['mlog_subject'], "=?{$this->ipsclass->vars['gb_char_set']}?Q?" ) !== FALSE ) ? 
										str_replace( "=?{$this->ipsclass->vars['gb_char_set']}?Q?", "", str_replace( "?=", "", preg_replace_callback( '/=([A-F0-9]{2})/', create_function( '$match', 'return chr( hexdec( "$match[1]" ) );' ), $row['mlog_subject'] ) ) )
										//base64_decode( str_replace( "=?utf-8?B?", "", str_replace( "=?=", "", str_replace( "=20", "", $row['mlog_subject'] ) ) ) )
										: $row['mlog_subject'];
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
																		"<center><input type='checkbox' class='checkbox' name='id_{$row['mlog_id']}' value='1' /></center>",
																		'<b>'.$row['mlog_to'].'</b>',
																		"<a href='javascript:pop_win(\"&{$this->ipsclass->form_code_js}&code=viewemail&id={$row['mlog_id']}\", \"{$row['mlog_id']}\",400,350)' title='Read email'>".$row['mlog_subject']."</a>",
																		$row['mlog_msg'].'<br />'.$row['mlog_code']. '&nbsp;'.$row['mlog_smtp_msg'],
																		$row['mlog_date'],
															   )      );
			
			
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<center>No results</center>");
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic('<div style="float:left;width:auto"><input type="submit" value="Remove Checked" class="realbutton" />&nbsp;<input type="checkbox" id="checkbox" name="type" value="all" />&nbsp;Remove all?</div><div align="right">'.$links.'</div></form>', 'left', 'tablesubheader');
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'list'       ),
																			 2 => array( 'act'   , 'emailerror' ),
																			 3 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Search Email Error Logs" );
		
		$form_array = array(
							  0 => array( 'subject'    , 'Email Subject'      ),
							  2 => array( 'email_from' , 'From Email Address' ),
							  3 => array( 'email_to'   , 'To Email Address'   ),
							  4 => array( 'error'      , 'Error Message'      ),
						   );
						   
		$type_array = array(
							  0 => array( 'exact'      , 'is exactly' ),
							  1 => array( 'loose'      , 'contains'   ),
						   );
			
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search where</b> &nbsp;"
																			 . $this->ipsclass->adskin->form_dropdown( "type" , $form_array, isset($_POST['type']) ? $_POST['type'] : '' )  ." "
																			 . $this->ipsclass->adskin->form_dropdown( "match", $type_array, isset($_POST['match']) ? $_POST['match'] : '') ." "
																			 . $this->ipsclass->adskin->form_input( "string", isset($_POST['string']) ? $_POST['string'] : '' ),
																   )      );
								 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Search");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
	
	}
	
	
	
}


?>