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
|   > Import functions
|   > Module written by Matt Mecham
|   > Date started: 22nd April 2002
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

class ad_modlogs {

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
	var $perm_child = "modlog";

	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Moderator Logs' );
		
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
		
			case 'view':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->view();
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
	// Remove archived files
	//-----------------------------------------
	
	function view()
	{
		$start = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		$this->ipsclass->admin->page_detail = "Viewing all actions by a moderator";
		$this->ipsclass->admin->page_title  = "Moderator Logs Manager";
	
		if ( !isset($this->ipsclass->input['search_string']) OR $this->ipsclass->input['search_string'] == "")
		{
			if (! $this->ipsclass->input['mid'] )
			{
				// Empty search form...sneaky...
				$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(id) as count', 'from' => 'moderator_logs' ) );
				$this->ipsclass->DB->simple_exec();
			
				$row = $this->ipsclass->DB->fetch_row();
			
				$row_count = $row['count'];
			
				$query = "&act=modlog&code=view";
			
				$this->ipsclass->DB->cache_add_query( 'modlogs_view_three', array( 'start' => $start ) );
				$this->ipsclass->DB->cache_exec_query();				
			}
			else
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(id) as count', 'from' => 'moderator_logs', 'where' => "member_id=".intval($this->ipsclass->input['mid']) ) );
				$this->ipsclass->DB->simple_exec();
				
				$row = $this->ipsclass->DB->fetch_row();
				
				$row_count = $row['count'];
				
				$query = "&act=modlog&mid={$this->ipsclass->input['mid']}&code=view";
				
				$this->ipsclass->DB->cache_add_query( 'modlogs_view_one', array( 'mid' => intval($this->ipsclass->input['mid']), 'start' => $start ) );
				$this->ipsclass->DB->cache_exec_query();
			}
		}
		else
		{
			$this->ipsclass->input['search_string'] = urldecode($this->ipsclass->input['search_string']);
			
			if ( ($this->ipsclass->input['search_type'] == 'topic_id') or ($this->ipsclass->input['search_type'] == 'forum_id') )
			{
				$dbq = "m.".$this->ipsclass->input['search_type']."='".$this->ipsclass->input['search_string']."'";
			}
			else
			{
				$dbq = "m.".$this->ipsclass->input['search_type']." LIKE '%".$this->ipsclass->input['search_string']."%'";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(m.id) as count', 'from' => 'moderator_logs m', 'where' => $dbq ) );
			$this->ipsclass->DB->simple_exec();
		
			$row = $this->ipsclass->DB->fetch_row();
			
			$row_count = $row['count'];
			
			$query = "&act=modlog&code=view&search_type={$this->ipsclass->input['search_type']}&search_string=".urlencode($this->ipsclass->input['search_string']);
			
			$this->ipsclass->DB->cache_add_query( 'modlogs_view_two', array( 'dbq' => $dbq, 'start' => $start ) );
			$this->ipsclass->DB->cache_exec_query();
		}
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $row_count,
														  'PER_PAGE'    => 20,
														  'CUR_ST_VAL'  => $start,
														  'L_SINGLE'    => "Single Page",
														  'L_MULTI'     => "Pages: ",
														  'BASE_URL'    => $this->ipsclass->base_url.'&'.$this->ipsclass->form_code.$query,
														)
												 );
									  
		$this->ipsclass->admin->page_detail = "You may view and remove actions performed by your moderators";
		$this->ipsclass->admin->page_title  = "Moderator Logs Manager";
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Member Name"            , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Action Performed"        , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Forum"                  , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Topic Title"            , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Time of action"         , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP address"             , "10%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Saved Moderator Logs" );
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic($links, 'right', 'tablesubheader');
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				$row['ctime'] = $this->ipsclass->admin->get_date( $row['ctime'], 'LONG' );
				
				if ( $row['topic_id'] )
				{
					$topicid = "<br />Topic ID: ".$row['topic_id'];
				}
				else
				{
					$topicid = "&nbsp;";
				}
				
				$sess_id             = preg_replace( "/^.+?s=(\w{32}).+?$/" , "\\1", $row['http_referer'] );
				$row['http_referer'] = preg_replace( "/s=(\w){32}/" , ""  , $row['http_referer'] );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$row['members_display_name']}</b>",
																					 "<span style='font-weight:bold;color:red'>{$row['action']}</span>",
																					 "<b>{$row['name']}</b>",
																					 "{$row['topic_title']}".$topicid,
																					 "{$row['ctime']}",
																					 "{$row['ip_address']}",
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
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'moderator_logs', 'where' => "member_id=".intval($this->ipsclass->input['mid']) ) );
		
		$this->ipsclass->admin->save_log("Removed Moderator Logs");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}&act=modlog");
		exit();
	
	
	}
	
	

	
	
	//-----------------------------------------
	// SHOW ALL LANGUAGE PACKS
	//-----------------------------------------
	
	function list_current()
	{
		$form_array = array();
	
		$this->ipsclass->admin->page_detail = "You may view and remove actions performed by your moderators";
		$this->ipsclass->admin->page_title  = "Moderator Logs Manager";

		
		//-----------------------------------------
		// VIEW LAST 5
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'modlogs_list_current_last_five', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		$this->ipsclass->adskin->td_header[] = array( "Member Name"            , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Action Performed"        , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Forum"                  , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Topic Title"            , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Time of action"         , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP address"             , "10%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Last 5 Moderation Actions" );

		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
			
				$row['ctime'] = $this->ipsclass->admin->get_date( $row['ctime'], 'LONG' );
				
				$topicid = "";
				
				if ( $row['topic_id'] )
				{
					$topicid = "<br />Topic ID: ".$row['topic_id'];
				}
				
				$sess_id             = preg_replace( "/^.+?s=(\w{32}).+?$/" , "\\1", $row['http_referer'] );
				$row['http_referer'] = preg_replace( "/s=(\w){32}/" , ""  , $row['http_referer'] );
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$row['members_display_name']}</b>",
																					 "<span style='font-weight:bold;color:red'>{$row['action']}</span>",
																					 "<b>{$row['name']}</b>",
																					 "{$row['topic_title']}".$topicid,
																					 "{$row['ctime']}",
																					 "{$row['ip_address']}",
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
		$this->ipsclass->adskin->td_header[] = array( "Actions Performed"       , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "View all by member"     , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Remove all by member"   , "30%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Saved Moderator Logs" );
		
		$this->ipsclass->DB->cache_add_query( 'modlogs_list_current_show_all', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$r['members_display_name']}</b>",
																				 "<center>{$r['act_count']}</center>",
																				 "<center><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&act=modlog&code=view&mid={$r['member_id']}'>View</a></center>",
																				 "<center><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&act=modlog&code=remove&mid={$r['member_id']}'>Remove</a></center>",
																		)      );
		}
			
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'view'     ),
																 2 => array( 'act'   , 'modlog'       ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														)      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Search Moderator Logs" );
		
		$form_array = array(
							  0 => array( 'topic_title', 'Topic Title' ),
							  1 => array( 'ip_address',  'IP Address'  ),
							  2 => array( 'member_name', 'Member Name' ),
							  3 => array( 'topic_id'   , 'Topic ID'    ),
							  4 => array( 'forum_id'   , 'Forum ID'    )
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