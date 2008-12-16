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
|   > $Date: 2007-05-02 17:29:12 -0400 (Wed, 02 May 2007) $
|   > $Revision: 959 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Components Functions
|   > Module written by Matt Mecham
|   > Date started: 12th April 2005 (13:09)
+--------------------------------------------------------------------------
*/

# CALENDAR SORTING!

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_calendars
{
	# Globals
	var $ipsclass;
	
	var $perm_main  = 'content';
	var $perm_child = 'calendars';
	
	/*-------------------------------------------------------------------------*/
	// Main handler
	/*-------------------------------------------------------------------------*/
	
	function auto_run() 
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Manage Calendars' );
		$this->html = $this->ipsclass->acp_load_template('cp_skin_management');
		
		switch($this->ipsclass->input['code'])
		{
			case 'calendar_list':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->calendar_list();
				break;
			
			case 'calendar_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->calendar_delete();
				break;
			
			case 'calendar_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->calendar_form('add');
				break;
			case 'calendar_add_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->calendar_save('add');
				break;
			
			case 'calendar_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->calendar_form('edit');
				break;
			case 'calendar_edit_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->calendar_save('edit');
				break;
			
			
			case 'calendar_move':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->calendar_move();
				break;
			case 'calendar_rebuildcache':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->calendar_rebuildcache( 1 );
				break;
			case 'calendar_rss_cache':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->calendar_rss_cache( intval($this->ipsclass->input['cal_id']), 1 );
				break;
				
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->calendar_list();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Components: Delete
	/*-------------------------------------------------------------------------*/
	
	function calendar_delete()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$cal_id = intval($this->ipsclass->input['cal_id']);
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( ! $cal_id )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->components_list();
			return;
		}
		
		//--------------------------------------------
		// Delete calendar events
		//--------------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'cal_events', 'where' => 'event_calendar_id='.$cal_id ) );
		
		//--------------------------------------------
		// Delete calendar
		//--------------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'cal_calendars', 'where' => 'cal_id='.$cal_id ) );
		
		//--------------------------------------------
		// Recache and re-RSS
		//--------------------------------------------
		
		$this->calendars_rebuildcache();
		$this->calendar_rebuildcache();
		$this->calendar_rss_cache();
		
		$this->ipsclass->main_msg = "Calendar Removed";
		$this->calendar_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Components: Position
	/*-------------------------------------------------------------------------*/
	
	function calendar_move()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$cal_id = intval($this->ipsclass->input['cal_id']);
		$move   = trim($this->ipsclass->input['move']);
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( ! $cal_id OR ! $move )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->calendar_list();
			return;
		}
		
		//--------------------------------------------
		// Get from database
		//--------------------------------------------
		
		$calendar = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'cal_calendars', 'where' => 'cal_id='.$cal_id ) );
		
		$new_position = ( $move == 'up' ) ? intval($calendar['cal_position']) - 1 : intval($calendar['cal_position']) + 1;
		
		$this->ipsclass->DB->do_update( 'cal_calendars', array( 'cal_position' => $new_position ), 'cal_id='.$cal_id );
		
		$this->calendars_rebuildcache();
		
		$this->ipsclass->main_msg = "Calendar repositioned";
		$this->calendar_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Rebuild Cache
	/*-------------------------------------------------------------------------*/
	
	function calendar_rss_cache( $calendar_id='all', $return=0 )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$seenids   = array();
		$calevents = "";
		
		//--------------------------------------------
		// Get classes
		//--------------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/calendar.php' );
		$calendar           =  new calendar();
		$calendar->ipsclass =& $this->ipsclass;
		
		//--------------------------------------------
		// Require classes
		//--------------------------------------------
		
		require_once( KERNEL_PATH . 'class_rss.php' );
		$class_rss              =  new class_rss();
		$class_rss->ipsclass    =& $this->ipsclass;
		$class_rss->use_sockets =  $this->use_sockets;
		$class_rss->doc_type    =  $this->ipsclass->vars['gb_char_set'];
		
		//-----------------------------------------
		// Make sure we have the HTML component
		//-----------------------------------------
		
		if ( ! is_object( $this->html ) )
		{
			$this->html = $this->ipsclass->acp_load_template('cp_skin_management');
		}
		
		//--------------------------------------------
		// Reset rss_export cache
		//--------------------------------------------
		
		$this->ipsclass->cache['rss_calendar'] = array();
		
		//--------------------------------------------
		// Get stuff
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'cal_calendars', 'where' => 'cal_rss_export_days > 0 AND cal_rss_export_max > 0 AND cal_rss_export=1' ) );
		$outer = $this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			if ( $row['cal_rss_export'] )
			{
				$this->ipsclass->cache['rss_calendar'][] = array( 'url'   => $this->ipsclass->vars['board_url'].'/index.php?act=rssout&amp;type=calendar&amp;id='.$row['cal_id'],
																  'title' => $row['cal_title'] );
			}
			
			if ( $calendar_id == $row['cal_id'] OR $calendar_id == 'all' )
			{
				//--------------------------------------------
				// Create Channel
				//--------------------------------------------
				
				$channel_id = $class_rss->create_add_channel( array( 'title'       => $row['cal_title'],
																	 'link'        => $this->ipsclass->vars['board_url'].'/index.php?act=calendar&amp;calendar_id='.$row['cal_id'],
																	 'pubDate'     => $class_rss->rss_unix_to_rfc( time() ),
																	 'ttl'         => $row['cal_rss_update'] * 60,
																	 'description' => $row['cal_title']
															)      );
															
				//--------------------------------------------
				// Check permissions
				//--------------------------------------------
				
				$_perms = unserialize( $row['cal_permissions'] );
				$pass   = 0;
				
				if ( $_perms['perm_read'] == '*' OR preg_match( "/(^|,)".$this->ipsclass->vars['guest_group']."(,|$)/", $_perms['perm_read'] ) )
				{
					$pass = 1;
				}
				
				if ( ! $pass )
				{
					continue;
				}
				
				//--------------------------------------------
				// Sort out dates
				//--------------------------------------------
				
				$row['cal_rss_export_days'] = intval($row['cal_rss_export_days']) + 1;
				
				list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', time()) );
				
				$timenow   = gmmktime( 0,0,0, $month, 1, $year );
				$timethen  = time() + ($row['cal_rss_export_days'] * 86400) + 86400;
				$nowtime   = time() - 86400;
				$items     = 0;
				
				//--------------------------------------------
				// Get events
				//--------------------------------------------
				
				$calendar->get_events_sql( 0, 0, array('timenow' => $timenow, 'timethen' => $timethen, 'cal_id' => $row['cal_id'] ), 0 );
				
				//--------------------------------------------
				// OK.. Go through days and check events
				//--------------------------------------------
				
				for( $i = 0 ; $i <= $row['cal_rss_export_days'] ; $i++ )
				{
					//--------------------------------------------
					// Get more then!
					//--------------------------------------------
					
					list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', $nowtime) );
						
					$eventcache = $calendar->get_day_events( $month, $day, $year );
					
					foreach( $eventcache as $event )
					{ 
						if ( ! in_array( $event['event_id'], $seenids ) )
						{
							//--------------------------------------------
							// Got enough?
							//--------------------------------------------
							
							if ( $row['cal_rss_export_max'] <= $items )
							{
								break;
							}
							
							if ( $calendar->get_info_events( $event, $month, $day, $year, 0 ) )
							{
								if ( ! $event['event_approved'] )
								{
									continue;
								}
								
								if ( $event['event_private'] )
								{
									continue;
								}
								
								if ( $event['event_perms'] != '*' AND ! preg_match( "/(^|,)".$this->ipsclass->vars['guest_group']."(,|$)/", $event['event_perms'] ) )
								{
									continue;
								}
								
								//--------------------------------------------
								// Get dates
								//--------------------------------------------
								
								list( $m , $d , $y  ) = explode( ",", gmdate('n,j,Y', $event['event_unix_from']  ) );
								list( $m1, $d1, $y1 ) = explode( ",", gmdate('n,j,Y', $event['event_unix_to']   ) );
								
								$event['_from_month'] = $m;
								$event['_from_day']   = $d;
								$event['_from_year']  = $y;
								$event['_to_month']   = $m1;
								$event['_to_day']     = $d1;
								$event['_to_year']    = $y1;

								if ( $event['recurring'] )
								{
									$event['event_content'] = $this->html->calendar_rss_recurring( $event );
								}
								else if ( $event['single'] )
								{
									$event['event_content'] = $this->html->calendar_rss_single( $event );
								}
								else
								{
									$event['event_content'] = $this->html->calendar_rss_range( $event );
								}
								
								$event['event_unix_from'] = $event['event_tz'] ? $event['event_unix_from'] : $event['event_unix_from'] + ( $this->ipsclass->vars['time_offset'] * 3600 );
						
								$class_rss->create_add_item( $channel_id, array( 'title'           => $event['event_title'],
																				 'link'            => $this->ipsclass->vars['board_url'].'/index.php?act=calendar&amp;code=showevent&amp;calendar_id='.$row['cal_id'].'&amp;event_id='.$event['event_id'],
																				 'description'     => $event['event_content'],
																				 'pubDate'	       => $class_rss->rss_unix_to_rfc( $event['event_unix_from'] ),
																				 'guid'            => $event['event_id']
														  )                    );
											
										}
							
							//--------------------------------------------
							// Increment
							//--------------------------------------------
							
							$seenids[ $event['event_id'] ] = $event['event_id'];
							$items++;
						}
					}
					
					$nowtime += 86400;
				}

				//--------------------------------------------
				// Compile and save RSS document
				//--------------------------------------------
		
				$class_rss->rss_create_document();
			
				//--------------------------------------------
				// Update the cache
				//--------------------------------------------
			
				$this->ipsclass->DB->do_update( 'cal_calendars', array( 'cal_rss_update_last'    => time(),
																		'cal_rss_cache' => $class_rss->rss_document ), 'cal_id='.$row['cal_id'] );
			}
		}
		
		//--------------------------------------------
		// Update cache
		//--------------------------------------------
		
		$this->ipsclass->update_cache( array( 'name' => 'rss_calendar', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		if ( $return )
		{
			$this->ipsclass->main_msg = "Calendar Events RSS Recached";
			$this->calendar_list();
		}
		else
		{
			return $class_rss->rss_document;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Rebuild Cache
	/*-------------------------------------------------------------------------*/
	
	function calendar_rebuildcache( $return=0 )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$this->ipsclass->vars['calendar_limit']  = intval($this->ipsclass->vars['calendar_limit']) < 2 ? 1 : intval($this->ipsclass->vars['calendar_limit']);
		
		//--------------------------------------------
		// Grab an extra day for the TZ diff
		//--------------------------------------------
		
		$this->ipsclass->vars['calendar_limit']++;
		
		list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', time() ) );
				
		$timenow   = gmmktime( 0,0,0, $month, 1, $year );
		$timethen  = time() + (intval($this->ipsclass->vars['calendar_limit']) * 86400);
		$seenids   = array();
		$nowtime   = time() - 86400;
		$birthdays = "";
		$calevents = "";
		$calendars = array();
		
		$a           = explode( ',', gmdate( 'Y,n,j,G,i,s', time() ) );
		$day         = $a[2];
		$month       = $a[1];
		$year        = $a[0];
		$daysinmonth = date( 't', time() );
		
		//-----------------------------------------
		// Get 24hr before and 24hr after to make
		// sure we don't break any timezones
		//-----------------------------------------
		
		$last_day   = $day - 1;
		$last_month = $month;
		$last_year  = $year;
		$next_day   = $day + 1;
		$next_month = $month;
		$next_year  = $year;
		
		//-----------------------------------------
		// Calculate dates..
		//-----------------------------------------
		
		if ( $last_day == 0 )
		{
			$last_month -= 1;
			$last_day   = gmdate( 't', time() );
		}
		
		if ( $last_month == 0 )
		{
			$last_month = 12;
			$last_year  -= 1;
		}
		
		if ( $next_day > gmdate( 't', time() ) )
		{
			$next_month += 1;
			$next_day   = 1;
		}
		
		if ( $next_month == 13 )
		{
			$next_month = 1;
			$next_year += 1;
		}
		
		//--------------------------------------------
		// Get classes
		//--------------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/calendar.php' );
		$calendar           =  new calendar();
		$calendar->ipsclass =& $this->ipsclass;
		
		//--------------------------------------------
		// Get stuff
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'cal_calendars' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$perms = unserialize( $row['cal_permissions'] );
			$row['_perm_read'] = $perms['perm_read'];
			
			$calendars[ $row['cal_id'] ] = $row;
		}
		
		$calendar->get_events_sql( 0, 0, array('timenow' => $timenow, 'timethen' => $timethen ) );

		//echo "<pre>";print_r($calendar->event_cache);exit;
		//--------------------------------------------
		// OK.. Go through days and check events
		//--------------------------------------------
		
		for( $i = 0 ; $i <= $this->ipsclass->vars['calendar_limit'] ; $i++ )
		{
			list( $_month, $tday, $year ) = explode( ',', gmdate('n,j,Y', $nowtime) );
			
			$eventcache = $calendar->get_day_events( $_month, $tday, $year );
			
			foreach( $eventcache as $event )
			{
				if ( ! in_array( $event['event_id'], $seenids ) )
				{ 
					if ( $calendar->get_info_events( $event, $_month, $tday, $year, 0 ) )
					{
						if ( ! $event['event_approved'] )
						{
							continue;
						}
						
						unset( $event['event_content'], $event['event_smilies'] );
						
						$event['_perm_read']             = $calendars[ $event['event_calendar_id'] ]['_perm_read'];
						$calevents[ $event['event_id'] ] = $event;
					}
					
					$seenids[ $event['event_id'] ] = $event['event_id'];
				}
			}
			
			$nowtime += 86400;
		}

		//-----------------------------------------
		// Grab birthdays
		//-----------------------------------------
		
		$append_string = "";
		
        if( !date("L") )
        {
	        if( $month == "2" AND ( $day == "28" OR $day == "27" ) )
	        {
		        $append_string = " or( bday_month=2 AND bday_day=29 )";
	        }
		}
        
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, members_display_name, mgroup, bday_day, bday_month, bday_year',
													  'from'   => 'members',
													  'where'  => "( bday_day=$last_day AND bday_month=$last_month )
																   or ( bday_day=$day AND bday_month=$month )
																   or ( bday_day=$next_day AND bday_month=$next_month ) {$append_string}"
											 )      );
							 
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$birthdays[ $r['id'] ] = $r;
		}
		
		//--------------------------------------------
		// Update calendar array
		//--------------------------------------------
		
		$this->ipsclass->cache['birthdays'] =& $birthdays;
		$this->ipsclass->cache['calendar'] =& $calevents;
		
		$this->ipsclass->update_cache( array( 'name' => 'calendar', 'array' => 1, 'deletefirst' => 1 ) );
		$this->ipsclass->update_cache( array( 'name' => 'birthdays', 'array' => 1, 'deletefirst' => 1 ) );

		if ( $return )
		{
			$this->ipsclass->main_msg = "Calendar Events Recached";
			$this->calendar_list();
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Components Save
	/*-------------------------------------------------------------------------*/
	
	function calendar_save($type='add')
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$cal_id              = intval($this->ipsclass->input['cal_id']);
		$cal_title           = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_htmlspecialchars($_POST['cal_title'])) );
		$cal_moderate        = intval($this->ipsclass->input['cal_moderate']);
		$cal_event_limit     = intval($this->ipsclass->input['cal_event_limit']);
		$cal_bday_limit      = intval($this->ipsclass->input['cal_bday_limit']);
		$cal_rss_export      = intval($this->ipsclass->input['cal_rss_export']);
		$cal_rss_export_days = intval($this->ipsclass->input['cal_rss_export_days']);
		$cal_rss_export_max  = intval($this->ipsclass->input['cal_rss_export_max']);
		$cal_rss_update      = intval($this->ipsclass->input['cal_rss_update']);
		$cal_perms			 = array( 'perm_read' => '', 'perm_post' => '', 'perm_nomod' => '' );
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $cal_id OR ! $cal_title )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->calendar_list();
				return;
			}
		}
		else
		{
			if ( ! $cal_title )
			{
				$this->ipsclass->main_msg = "You must complete the entire form.";
				$this->calendar_form( $type );
				return;
			}
		}
		
		//--------------------------------------------
		// Permission: Read
		//--------------------------------------------
		
		if ( $this->ipsclass->input['perm_read_all'] )
		{
			$cal_perms['perm_read'] = '*';
		}
		else if ( is_array( $_POST['perm_read'] ) )
		{
			$cal_perms['perm_read'] = implode( ',', $_POST['perm_read'] );
		}
		
		//--------------------------------------------
		// Permission: Start
		//--------------------------------------------
		
		if ( $this->ipsclass->input['perm_post_all'] )
		{
			$cal_perms['perm_post'] = '*';
		}
		else if ( is_array( $_POST['perm_post'] ) )
		{
			$cal_perms['perm_post'] = implode( ',', $_POST['perm_post'] );
		}
		
		//--------------------------------------------
		// Permission: No mod
		//--------------------------------------------
		
		if ( $this->ipsclass->input['perm_nomod_all'] )
		{
			$cal_perms['perm_nomod'] = '*';
		}
		else if ( is_array( $_POST['perm_nomod'] ) )
		{
			$cal_perms['perm_nomod'] = implode( ',', $_POST['perm_nomod'] );
		}
		
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 'cal_title'           => $cal_title,
						'cal_moderate'        => $cal_moderate,
						'cal_event_limit'     => $cal_event_limit,
						'cal_bday_limit'      => $cal_bday_limit,
						'cal_rss_export'      => $cal_rss_export,
						'cal_rss_export_days' => $cal_rss_export_days,
						'cal_rss_export_max'  => $cal_rss_export_max,
						'cal_rss_update'      => $cal_rss_update,
						'cal_permissions'     => serialize($cal_perms),
					 );
					 
		if ( $type == 'add' )
		{
			$this->ipsclass->DB->do_insert( 'cal_calendars', $array );
			$cal_id = $this->ipsclass->DB->get_insert_id();
			
			$this->ipsclass->main_msg = 'New Calendar Added';
		}
		else
		{
			
			$this->ipsclass->DB->do_update( 'cal_calendars', $array, 'cal_id='.$cal_id );
			$this->ipsclass->main_msg = 'Calendar Edited';
		}
		
		$this->calendars_rebuildcache();
		$this->calendar_rebuildcache( $cal_id, 0 );
		$this->calendar_rss_cache( $cal_id, 0 );
		$this->calendar_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Components: Form
	/*-------------------------------------------------------------------------*/
	
	function calendar_form( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$cal_id             	= isset($this->ipsclass->input['cal_id']) ? intval($this->ipsclass->input['cal_id']) : 0;
		$form               	= array();
		$form['perm_read']  	= "";
		$form['perm_post']  	= "";
		$form['perm_nomod'] 	= "";
		$form['perm_read_all'] 	= "";
		$form['perm_post_all']	= "";
		$form['perm_nomod_all']	= "";
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'calendar_add_do';
			$title    = "Add New Calendar";
			$button   = "Add New Calendar";
			
			$calendar = array( 'perm_read'			=> '',
								'perm_post'			=> '',
								'perm_nomod'		=> '',
								'cal_title'			=> '',
								'cal_moderate'		=> '',
								'cal_event_limit'	=> '',
								'cal_bday_limit'	=> '',
								'cal_rss_export'	=> '',
								'cal_rss_update'	=> '',
								'cal_rss_export_days' => '',
								'cal_rss_export_max' => '',
								'cal_id'			=> 0 );
		}
		else
		{
			$calendar = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'cal_calendars', 'where' => 'cal_id='.$cal_id ) );
			$calendar = array_merge( $calendar, unserialize( $calendar['cal_permissions'] ) );
			
			if ( ! $calendar['cal_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->components_list();
				return;
			}
			
			$formcode = 'calendar_edit_do';
			$title    = "Edit Calendar ".$calendar['cal_title'];
			$button   = "Save Changes";
		}
		
		//-----------------------------------------
		// Build Groups
		//-----------------------------------------
		
		$perm_read_array  = ( isset($_POST['perm_read'])  AND is_array( $_POST['perm_read'] ) )  ? $_POST['perm_read']  : explode( ',', $calendar['perm_read']  );
		$perm_post_array  = ( isset($_POST['perm_post'])  AND is_array( $_POST['perm_post'] ) )  ? $_POST['perm_post']  : explode( ',', $calendar['perm_post']  );
		$perm_nomod_array = ( isset($_POST['perm_nomod']) AND is_array( $_POST['perm_nomod'] ) ) ? $_POST['perm_nomod'] : explode( ',', $calendar['perm_nomod'] );
		$perm_read_all    = FALSE;
		$perm_post_all    = FALSE;
		$perm_nomod_all   = FALSE;
		
		if ( in_array( '*', $perm_read_array ) OR ( isset($_POST['perm_read_all']) AND $_POST['perm_read_all'] ) )
		{
			$perm_read_all         = TRUE;
			$form['perm_read_all'] = ' checked="checked"';
		}
		
		if ( in_array( '*', $perm_post_array ) OR ( isset($_POST['perm_post_all']) AND $_POST['perm_post_all'] ) )
		{
			$perm_post_all         = TRUE;
			$form['perm_post_all'] = ' checked="checked"';
		}
		
		if ( in_array( '*', $perm_nomod_array ) OR ( isset($_POST['perm_nomod_all']) AND $_POST['perm_nomod_all'] ) )
		{
			$perm_nomod_all         = TRUE;
			$form['perm_nomod_all'] = ' checked="checked"';
		}
		
		//-----------------------------------------
		// Perms masks section
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'forum_perms' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $data = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// READ
			//-----------------------------------------
			
			if ( is_array($perm_read_array) AND in_array( $data['perm_id'], $perm_read_array ) OR $perm_read_all == TRUE )
			{
				$form['perm_read'] .= "<option value='{$data['perm_id']}' selected='selected'>{$data['perm_name']}</option>";
			}
			else
			{
				$form['perm_read'] .= "<option value='{$data['perm_id']}'>{$data['perm_name']}</option>";
			}
			
			//-----------------------------------------
			// POST
			//-----------------------------------------
			
			if ( is_array($perm_post_array) AND in_array( $data['perm_id'], $perm_post_array ) OR $perm_post_all == TRUE )
			{
				$form['perm_post'] .= "<option value='{$data['perm_id']}' selected='selected'>{$data['perm_name']}</option>";
			}
			else
			{
				$form['perm_post'] .= "<option value='{$data['perm_id']}'>{$data['perm_name']}</option>";
			}
			
			//-----------------------------------------
			// NO MOD
			//-----------------------------------------
			
			if ( is_array($perm_nomod_array) AND in_array( $data['perm_id'], $perm_nomod_array ) OR $perm_nomod_all == TRUE )
			{
				$form['perm_nomod'] .= "<option value='{$data['perm_id']}' selected='selected'>{$data['perm_name']}</option>";
			}
			else
			{
				$form['perm_nomod'] .= "<option value='{$data['perm_id']}'>{$data['perm_name']}</option>";
			}
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['cal_title']           = $this->ipsclass->adskin->form_input(        'cal_title'           , $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['cal_title']) AND $_POST['cal_title'] ) ? $_POST['cal_title'] : $calendar['cal_title'] ) );
		$form['cal_moderate']        = $this->ipsclass->adskin->form_yes_no(       'cal_moderate'        , ( isset($_POST['cal_moderate']) 			AND $_POST['cal_moderate'] )         ? $_POST['cal_moderate']         : $calendar['cal_moderate'] );
		$form['cal_event_limit']     = $this->ipsclass->adskin->form_simple_input( 'cal_event_limit'     , ( isset($_POST['cal_event_limit']) 		AND $_POST['cal_event_limit'] )      ? $_POST['cal_event_limit']      : $calendar['cal_event_limit'], 5 );
		$form['cal_bday_limit']      = $this->ipsclass->adskin->form_simple_input( 'cal_bday_limit'      , ( isset($_POST['cal_bday_limit']) 		AND $_POST['cal_bday_limit'] )       ? $_POST['cal_bday_limit']       : $calendar['cal_bday_limit'], 5 );
		$form['cal_rss_export']      = $this->ipsclass->adskin->form_yes_no(       'cal_rss_export'      , ( isset($_POST['cal_rss_export']) 		AND $_POST['cal_rss_export'] )       ? $_POST['cal_rss_export']       : $calendar['cal_rss_export'] );
		$form['cal_rss_update']      = $this->ipsclass->adskin->form_simple_input( 'cal_rss_update'      , ( isset($_POST['cal_rss_update']) 		AND $_POST['cal_rss_update'] )       ? $_POST['cal_rss_update']       : $calendar['cal_rss_update'], 5 );
		$form['cal_rss_export_days'] = $this->ipsclass->adskin->form_simple_input( 'cal_rss_export_days' , ( isset($_POST['cal_rss_export_days']) 	AND $_POST['cal_rss_export_days'] )  ? $_POST['cal_rss_export_days']  : $calendar['cal_rss_export_days'], 5 );
		$form['cal_rss_export_max']  = $this->ipsclass->adskin->form_simple_input( 'cal_rss_export_max'  , ( isset($_POST['cal_rss_export_max']) 	AND $_POST['cal_rss_export_max'] )   ? $_POST['cal_rss_export_max']   : $calendar['cal_rss_export_max'], 5 );
		
		$this->ipsclass->html .= $this->html->calendar_form( $form, $title, $formcode, $button, $calendar );
		
		$this->ipsclass->html_help_title = "Calendar Manager";
		$this->ipsclass->html_help_msg   = "This section will allow you to manage your calendars.";
		
		$this->ipsclass->admin->nav[]    = array( '', "Add/Edit Calendar" );
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// List current calendars
	/*-------------------------------------------------------------------------*/
	
	function calendar_list()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content     = "";
		$seen_count  = 0;
		$total_items = 0;
		$rows        = array();
		
		//-------------------------------
		// Get components
		//-------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'cal_calendars', 'order' => 'cal_position ASC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$total_items++;
			$rows[] = $r;
		}
		
		foreach( $rows as $r )
		{
			//-------------------------------
			// Work out position images
			//-------------------------------
			
			$r['_pos_up']   = $this->html->calendar_position_blank($r['cal_id']);
			$r['_pos_down'] = $this->html->calendar_position_blank($r['cal_id']);
			
			//-------------------------------
			// Work out position images
			//-------------------------------
			
			if ( $total_items > 1 )
			{
				if ( ($seen_count + 1) == $total_items )
				{
					# Show up only
					$r['_pos_up']   = $this->html->calendar_position_up($r['cal_id']);
				}
				else if ( $seen_count > 0 AND $seen_count < $total_items )
				{
					# Show both...
					$r['_pos_up']   = $this->html->calendar_position_up($r['cal_id']);
					$r['_pos_down'] = $this->html->calendar_position_down($r['cal_id']);
				}
				else
				{
					# Show down only
					$r['_pos_down'] = $this->html->calendar_position_down($r['cal_id']);
				}
			}
			
			$seen_count++;
				
			$content .= $this->html->calendar_row($r);
		}
		
		$this->ipsclass->html .= $this->html->calendar_overview( $content );
		
		$this->ipsclass->admin->page_title  = "Calendar Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your calendars.";
		$this->ipsclass->admin->output();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Builds a cache of the current calendars
	/*-------------------------------------------------------------------------*/
		
	function calendars_rebuildcache()
	{
		$this->ipsclass->cache['calendars'] = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'cal_calendars', 'order' => 'cal_position ASC' ) );
		
		$this->ipsclass->DB->exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['calendars'][ $r['cal_id'] ] = $r;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'calendars', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		return TRUE;
	}
}


?>