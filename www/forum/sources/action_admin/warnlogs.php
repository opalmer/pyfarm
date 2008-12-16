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
|   > $Date: 2007-05-08 16:34:38 -0400 (Tue, 08 May 2007) $
|   > $Revision: 981 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Warn Log functions
|   > Module written by Matt Mecham
|   > Date started: 4th June 2003
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

class ad_warnlogs {

	var $base_url;
	
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
	var $perm_child = "warnlog";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Warn Logs' );
		
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
		
			case 'view':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->view();
				break;
				
			case 'viewcontact':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->view_contact();
				break;
				
			case 'viewnote':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':view' );
				$this->view_note();
				break;
				
			case 'remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove();
				break;
				
				
			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_current();
				break;
		}
		
	}
	
	//-----------------------------------------
	// View NOTE in da pop up innit
	//-----------------------------------------
	
	function view_note()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the email ID, please try again");
		}
		
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
		
		$id = intval($this->ipsclass->input['id']);
		
		$this->ipsclass->DB->cache_add_query( 'warnlogs_view_note', array( 'id' => $id ) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the email ID, please try again ($id)");
		}
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
		
		$content = preg_match( "#<content>(.+?)</content>#is", $row['wlog_notes'], $cont );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Warn Notes" );
	
		$row['date']  = $this->ipsclass->admin->get_date( $row['wlog_date'], 'LONG' );
		
		$row['a_name'] = $row['a_name'] ? $row['a_name'] : 'Deleted Member (ID:' . $row['wlog_mid'] . ')';
		
		$this->parser->parse_html    = 0;
		$this->parser->parse_nl2br   = 1;
		$this->parser->parse_smilies = 1;
		$this->parser->parse_bbcode  = 1;
		
		$text = $this->parser->pre_display_parse( $this->parser->pre_db_parse($cont[1]) );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													"<strong>From:</strong> {$row['p_name']}
													<br /><strong>To:</strong> {$row['a_name']}
													<br /><strong>Sent:</strong> {$row['date']}
													<hr>
													<br />$text
												    "
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
	
	
	}
	
	
	//-----------------------------------------
	// View contact in da pop up innit
	//-----------------------------------------
	
	function view_contact()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the email ID, please try again");
		}
		
		$id = intval($this->ipsclass->input['id']);
		
		$this->ipsclass->DB->cache_add_query( 'warnlogs_view_note', array( 'id' => $id ) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the email ID, please try again ($id)");
		}
		
		$type = $row['wlog_contact'] == 'pm' ? "PM" : "EMAIL";
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "100%" );
		
		$subject = preg_match( "#<subject>(.+?)</subject>#is", $row['wlog_contact_content'], $subj );
		$content = preg_match( "#<content>(.+?)</content>#is", $row['wlog_contact_content'], $cont );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $type.": ".$subj[1] );
	
		
		
		$row['date'] = $this->ipsclass->admin->get_date( $row['wlog_date'], 'LONG' );
		
		$row['a_name'] = $row['a_name'] ? $row['a_name'] : 'Deleted Member (ID:' . $row['wlog_mid'] . ')';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													"<strong>From:</strong> {$row['p_name']}
													<br /><strong>To:</strong> {$row['a_name']}
													<br /><strong>Sent:</strong> {$row['date']}
													<br /><strong>Subject:</strong> $subj[1]
													<hr>
													<br />$cont[1]
												    "
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
	
	
	}
	
	
	
	
	//-----------------------------------------
	// Remove archived files
	//-----------------------------------------
	
	function view()
	{
		$start = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		$this->ipsclass->html .= ""; // removed js popwin
		
		$this->ipsclass->admin->page_detail = "Viewing all warn entries on a member";
		$this->ipsclass->admin->page_title  = "Warn Logs Manager";
	
		if ($this->ipsclass->input['search_string'] == "" and $this->ipsclass->input['mid'])
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(wlog_id) as count', 'from' => 'warn_logs', 'where' => "wlog_mid=".intval($this->ipsclass->input['mid']) ) );
			$this->ipsclass->DB->simple_exec();
		
			$row = $this->ipsclass->DB->fetch_row();
			
			$row_count = $row['count'];
			
			$query = "&{$this->ipsclass->form_code}&mid={$this->ipsclass->input['mid']}&code=view";
			
			$this->ipsclass->DB->cache_add_query( 'warnlogs_view', array( 'mid' => intval($this->ipsclass->input['mid']), 'start' => $start ) );
			$this->ipsclass->DB->cache_exec_query();
		}
		else
		{
			$this->ipsclass->input['search_string'] = urldecode($this->ipsclass->input['search_string']);
			
			if ( ($this->ipsclass->input['search_type'] == 'notes') )
			{
				$dbq = "l.wlog_notes LIKE '%".$this->ipsclass->input['search_string']."%'";
			}
			else
			{
				$dbq = "l.wlog_contact_content LIKE '%".$this->ipsclass->input['search_string']."%'";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(l.wlog_id) as count', 'from' => 'warn_logs l', 'where' => $dbq ) );
			$this->ipsclass->DB->simple_exec();
			
			$row = $this->ipsclass->DB->fetch_row();
			
			$row_count = $row['count'];
			
			$query = "&{$this->ipsclass->form_code}&code=view&search_type={$this->ipsclass->input['search_type']}&search_string=".urlencode($this->ipsclass->input['search_string']);
			
			$this->ipsclass->DB->cache_add_query( 'warnlogs_view_two', array( 'dbq' => $dbq, 'start' => $start ) );
			$this->ipsclass->DB->cache_exec_query();
		}
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $row_count,
														  'PER_PAGE'    => 30,
														  'CUR_ST_VAL'  => $start,
														  'L_SINGLE'    => "Single Page",
														  'L_MULTI'     => "Pages: ",
														  'BASE_URL'    => $this->ipsclass->base_url.$query,
														)
												 );
									  
		$this->ipsclass->admin->page_detail = "You may view warn entries added by your moderators";
		$this->ipsclass->admin->page_title  = "Warn Logs Manager";
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Type"        , "5%" );
		$this->ipsclass->adskin->td_header[] = array( "Member Name" , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Contacted"   , "5%" );
		$this->ipsclass->adskin->td_header[] = array( "MOD Q"       , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "SUSP"        , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "NO POST"     , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Date"        , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Warned By"   , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "View Note"   , "10%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Saved Warn Logs" );
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic($links, 'right', 'tablesubheader');
		
		$days = array( 'd' => "Days", 'h' => "Hours" );
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['a_name'] = $row['a_name'] ? $row['a_name'] : 'Deleted Member (ID:' . $row['wlog_mid'] . ')';
				
				$row['wlog_date'] = $this->ipsclass->admin->get_date( $row['wlog_date'], 'LONG' );
				
				$type = ( $row['wlog_type'] == 'pos' )      ? '<span style="color:green;font-weight:bold">-</span>' : '<span style="color:red;font-weight:bold">+</span>';
				$cont = ( $row['wlog_contact'] !=  'none' ) ? "<center><a href='javascript:pop_win(\"&{$this->ipsclass->form_code}&code=viewcontact&id={$row['wlog_id']}\",\"Log\", 400,400)'>View</a></center>" : '&nbsp;';
				
				$mod     = preg_match( "#<mod>(.+?)</mod>#is"        , $row['wlog_notes'], $mm );
				$post    = preg_match( "#<post>(.+?)</post>#is"      , $row['wlog_notes'], $pm );
				$susp    = preg_match( "#<susp>(.+?)</susp>#is"      , $row['wlog_notes'], $sm );
				$content = preg_match( "#<content>(.+?)</content>#is", $row['wlog_notes'], $cm );
				
				$content = $cm[1];
				
				$mod  = trim($mm[1]);
				$post = trim($pm[1]);
				$susp = trim($sm[1]);
				
				list($v, $u, $i) = explode(',', $mod);
				
				if ( $i == 1 )
				{
					$mod = 'INDEF';
				}
				else if ( $v == "" )
				{
					$mod = 'None';
				}
				else
				{
					$mod = $v.' '.$days[$u];
				}
				
				//-----------------------------------------
				
				list($v, $u, $i) = explode(',', $post);
				
				if ( $i == 1 )
				{
					$post = 'INDEF';
				}
				else if ( $v == "" )
				{
					$post = 'None';
				}
				else
				{
					$post = $v.' '.$days[$u];
				}
				
				list($v, $u) = explode(',', $susp);
				
				if ( $v == "" )
				{
					$susp = 'None';
				}
				else
				{
					$susp = $v.' '.$days[$u];
				}
				
				//-----------------------------------------
		
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
														  "<center>$type</center>",
														  "<b>{$row['a_name']}</b>",
														  $cont,
														  $mod,
														  $susp,
														  $post,
														  "{$row['wlog_date']}",
														  "<b>{$row['p_name']}</b>",
														  "<center><a href='javascript:pop_win(\"&{$this->ipsclass->form_code}&code=viewnote&id={$row['wlog_id']}\",\"Log\",400,400)'>View</a></center>"
												 )      );
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<center>No results</center>");
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic($links, 'right', 'tablesubheader');
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
		
	}
	
	//-----------------------------------------
	// Remove archived files
	//-----------------------------------------
	
	function remove()
	{
		if ($this->ipsclass->input['mid'] == "")
		{
			$this->ipsclass->admin->error("You did not select a member ID to remove by!");
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'warn_logs', 'where' => "wlog_mid=".intval($this->ipsclass->input['mid']) ) );
		
		$this->ipsclass->admin->save_log("Removed Warn Logs");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");	
	}
	
	
	//-----------------------------------------
	// SHOW LOGS
	//-----------------------------------------
	
	function list_current()
	{
		$form_array = array();
	
		$this->ipsclass->admin->page_detail = "You may view and remove warn actions performed by your staff.<br />Note: Removing the logs does not decrease the member's warn level";
		$this->ipsclass->admin->page_title  = "Warn Logs Manager";
		
		$this->ipsclass->html .= ""; // removed js popwin
		
		//-----------------------------------------
		// VIEW LAST 5
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'warnlogs_list_current', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		$this->ipsclass->adskin->td_header[] = array( "Type"            , "5%" );
		$this->ipsclass->adskin->td_header[] = array( "Warned Member"   , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Contacted?"      , "5%" );
		$this->ipsclass->adskin->td_header[] = array( "Date"            , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Warned By"       , "25%" );

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Last 10 Warn Entries" );

		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['a_name'] = $row['a_name'] ? $row['a_name'] : 'Deleted Member (ID:' . $row['wlog_mid'] . ')';
				
				$row['wlog_date'] = $this->ipsclass->admin->get_date( $row['wlog_date'], 'LONG' );
				
				$type = ( $row['wlog_type'] == 'pos' ) ? '<span style="color:green;font-weight:bold">-</span>' : '<span style="color:red;font-weight:bold">+</span>';
				$cont = ( $row['wlog_contact'] !=  'none' ) ? "<center><a title='Show message' href='javascript:pop_win(\"&{$this->ipsclass->form_code}&code=viewcontact&id={$row['wlog_id']}\",\"Log\",400,400)'><img src='{$this->ipsclass->skin_acp_url}/images/acp_check.gif' border='0' alt='X'></a></center>" : '&nbsp;';
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
																					 "<center>$type</center>",
																					 "<b>{$row['a_name']}</b>",
																					 $cont,
																					 "{$row['wlog_date']}",
																					 "<b>{$row['p_name']}</b>",
																			)      );
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<center>No results</center>");
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Member Name"            , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Times Warned"           , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "View all by member"     , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Remove all by member"   , "30%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Saved Warn Logs" );
		
		$this->ipsclass->DB->cache_add_query( 'warnlogs_list_current_two', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$r['members_display_name'] = $r['members_display_name'] ? $r['members_display_name'] : 'Deleted Member (ID:' . $r['wlog_mid'] . ')';
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$r['members_display_name']}</b>",
																				 "<center>{$r['act_count']}</center>",
																				 "<center><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=view&mid={$r['wlog_mid']}'>View</a></center>",
																				 "<center><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=remove&mid={$r['wlog_mid']}'>Remove</a></center>",
																		)      );
		}
			
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'view'     ),
												  2 => array( 'act'   , 'warnlog'       ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Search Warn Logs" );
		
		$form_array = array(
							  0 => array( 'notes'  , 'Entry Notes' ),
							  1 => array( 'contact', 'Email/PM Sent'  ),
						   );
			
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search for...</b>" ,
										  		  $this->ipsclass->adskin->form_input( "search_string")
								 )      );
								 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search in...</b>" ,
										  		  $this->ipsclass->adskin->form_dropdown( "search_type", $form_array)
								 )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Search");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
	
	}
	
	
	
}


?>