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
|   > $Date: 2007-08-21 17:48:41 -0400 (Tue, 21 Aug 2007) $
|   > $Revision: 1099 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Calendar functions library
|   > Module written by Matt Mecham
|   > Date started: 12th June 2002
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

class calendar
{
	# Classes
	var $ipsclass;
	var $post;
	
	# Others
    var $output     = "";
    var $base_url   = "";
    var $html       = "";
    var $page_title = "";
    var $nav;
    
    var $chosen_month    = "";
    var $chosen_year     = "";
    var $now_date        = "";
    var $now			 = array( 'mday' => '', 'mon' => '', 'year' => '' );
    var $our_datestamp   = "";
    var $offset          = "";
    var $start_date      = "";
    var $first_day_array = "";
    var $month_words       = array();
    var $day_words         = array();
    var $query_month_cache = array();
    var $query_bday_cache  = array();
    
    var $event_cache	   = array();
    var $shown_events	   = array();
    
    var $calendar_id       = 1;
    var $calendar          = array();
    var $calendar_jump     = "";
    var $calenar_cache     = array();
    
    var $parsed_members	   = array();
    
    # Permissions
    
    var $can_read        = 0;
    var $can_post        = 0;
    var $can_avoid_queue = 0;
    var $can_moderate    = 0;
    
    /*-------------------------------------------------------------------------*/
    // AUTO-RUN
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
        //-----------------------------------------
        // Load lang and templs
        //-----------------------------------------
        
        $this->ipsclass->load_language('lang_calendar');
        $this->ipsclass->load_template('skin_calendar');
       
       	$this->ipsclass->vars['bday_show_cal_max'] = 5;
		
		//-----------------------------------------
		// Get "this" calendar details
		//-----------------------------------------
		
		if ( $this->ipsclass->input['cal_id'] OR $this->ipsclass->input['calendar_id'] )
		{
			$this->calendar_id = intval($this->ipsclass->input['cal_id']) ? intval($this->ipsclass->input['cal_id']) : intval($this->ipsclass->input['calendar_id']);
		}
		else
		{
			$this->calendar_id = 1;
		}
		
		//-----------------------------------------
		// Sneaky cheaty
		//-----------------------------------------
		
		$this->ipsclass->input['_cal_id'] =& $this->calendar_id;
		
		//-----------------------------------------
		// Get all calendar details
		//-----------------------------------------
		
		if( ! count( $this->ipsclass->cache['calendars'] ) )
		{
			$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'cal_calendars', 'order' => 'cal_position ASC' ) );
			$this->ipsclass->DB->exec_query();
			
			while( $cal = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['calendars'][ $cal['cal_id'] ] = $cal;
			}
		}
			
		if ( count( $this->ipsclass->cache['calendars'] ) AND is_array( $this->ipsclass->cache['calendars'] ) )
		{
			foreach( $this->ipsclass->cache['calendars'] as $cal_id => $cal )
			{
				$selected = "";
				$perms    = unserialize( $cal['cal_permissions'] );
				
				//-----------------------------------------
				// Got a perm?
				//-----------------------------------------
				
				if ( $this->ipsclass->check_perms( $perms['perm_read'] ) != TRUE )
				{
					continue;
				}
								
				if ( $cal['cal_id'] == $this->calendar_id )
				{
					$this->calendar = array_merge( $cal, $perms);
					$selected       = " selected='selected'";
				}
				
				$this->calendar_cache[ $cal['cal_id'] ] = array_merge( $cal, $perms);
				

				
				$this->calendar_jump .= "<option value='{$cal['cal_id']}'{$selected}>{$cal['cal_title']}</option>\n";
			}
		}
		
		if( ! $this->calendar )
		{
			if( count( $this->calendar_cache ) )
			{
				$tmp_resort = $this->calendar_cache;
				ksort($tmp_resort);
				reset($tmp_resort);
				$default_calid = key( $tmp_resort );
				$this->calendar_id = $default_calid;
				$this->calendar = $tmp_resort[ $default_calid ];
				unset( $tmp_resort );
			}
		}

		//-----------------------------------------
		// Got viewing perms?
		//-----------------------------------------
		
		$this->build_permissions();
		
		if ( ! $this->can_read )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_perm') );
		}
		
		//-----------------------------------------
        // Prep our chosen dates
        //-----------------------------------------
        
        // There is something whacky with getdate and GMT
        // This handrolled method seems to take into account
        // DST where getdate refuses.
		
		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->ipsclass->get_time_offset() ) );
		
		$this->now_date = array(
								 'year'    => $a[0],
								 'mon'     => $a[1],
								 'mday'    => $a[2],
								 'hours'   => $a[3],
								 'minutes' => $a[4],
								 'seconds' => $a[5]
							   );
        
        if ( isset($this->ipsclass->input['year']) )
        {
        	$this->ipsclass->input['y'] = $this->ipsclass->input['year'];
        }
        
        $this->chosen_month = ( !isset($this->ipsclass->input['m']) OR !intval($this->ipsclass->input['m']) ) ? $this->now_date['mon']  : intval($this->ipsclass->input['m']);
        $this->chosen_year  = ( !isset($this->ipsclass->input['y']) OR !intval($this->ipsclass->input['y']) ) ? $this->now_date['year'] : intval($this->ipsclass->input['y']);
        
        //-----------------------------------------
        // Make sure the date is in range.
        //-----------------------------------------
        
        if ( ! checkdate( $this->chosen_month, 1 , $this->chosen_year ) )
        {
        	$this->chosen_month = $this->now_date['mon'];
        	$this->chosen_year  = $this->now_date['year'];
        }
        
        //-----------------------------------------
        // Get the timestamp for our chosen date
        //-----------------------------------------
        
        $this->our_datestamp   = mktime( 0,0,1, $this->chosen_month, 1, $this->chosen_year);
        $this->first_day_array = $this->ipsclass->date_getgmdate($this->our_datestamp);
        	
        //-----------------------------------------
        // Finally, build up the lang arrays
        //-----------------------------------------
        
        $this->month_words = array( $this->ipsclass->lang['M_1'] , $this->ipsclass->lang['M_2'] , $this->ipsclass->lang['M_3'] ,
        							$this->ipsclass->lang['M_4'] , $this->ipsclass->lang['M_5'] , $this->ipsclass->lang['M_6'] ,
        							$this->ipsclass->lang['M_7'] , $this->ipsclass->lang['M_8'] , $this->ipsclass->lang['M_9'] ,
        							$this->ipsclass->lang['M_10'], $this->ipsclass->lang['M_11'], $this->ipsclass->lang['M_12'] );
        		
		if( !$this->ipsclass->vars['ipb_calendar_mon'] )
		{
        	$this->day_words   = array( $this->ipsclass->lang['D_0'], $this->ipsclass->lang['D_1'], $this->ipsclass->lang['D_2'],
        								$this->ipsclass->lang['D_3'], $this->ipsclass->lang['D_4'], $this->ipsclass->lang['D_5'],
        								$this->ipsclass->lang['D_6'] );
    	}
    	else
    	{
        	$this->day_words   = array( $this->ipsclass->lang['D_1'], $this->ipsclass->lang['D_2'], $this->ipsclass->lang['D_3'],
        								$this->ipsclass->lang['D_4'], $this->ipsclass->lang['D_5'], $this->ipsclass->lang['D_6'],
        								$this->ipsclass->lang['D_0'] );
		}
        
        switch( $this->ipsclass->input['code'] )
        {
        	case 'newevent':
        		$this->cal_event_form('add');
        		break;
        	case 'addnewevent':
        		$this->cal_event_save('add');
        		break;
        	case 'edit':
        		$this->cal_event_form('edit');
        		break;
        	case 'doedit':
        		$this->cal_event_save('edit');
        		break;
        		
        	case 'event_approve':
        		$this->event_approve();
        		break;
        		
        	case 'showday':
        		$this->show_day();
        		break;
        		
        	case 'showevent':
        		$this->show_event();
        		break;
        		
        	case 'birthdays':
        		$this->show_birthdays();
        		break;
        	
        	case 'showweek':
        		$this->show_week();
        		break;
        		
        	case 'delete':
        		$this->cal_delete();
        		break;
        		
        	case 'find':
        		$this->find_date();
        		break;
        	
        	default:
        		$this->show_month();
        		break;
        }
        
        if ($this->page_title == "")
        {
        	$this->page_title = $this->ipsclass->vars['board_name']." ".$this->ipsclass->lang['page_title'];
        }
        
        if (! is_array($this->nav) )
        {
        	$this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
        	$this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
        }
        
        $this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Event Approve
 	/*-------------------------------------------------------------------------*/
	
	function cal_delete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cal_id    = intval( $this->ipsclass->input['cal_id'] );
		$event_id  = intval( $this->ipsclass->input['event_id'] );
		$md5check  = trim( $this->ipsclass->input['md5check'] );
		
		//-----------------------------------------
		// Get permissions
		//-----------------------------------------
		
		$this->build_permissions( $cal_id );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=$event_id AND event_calendar_id=$cal_id" ) );		
		$this->ipsclass->DB->exec_query();
		$memcheck = $this->ipsclass->DB->fetch_row();
		
		if ( ! $cal_id OR ! $event_id )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		if ( ! $this->can_moderate && ( $this->ipsclass->member['id'] > 0 && $this->ipsclass->member['id'] <> $memcheck['event_member_id'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Check MD5
		//-----------------------------------------
		
		if ( $md5check != $this->ipsclass->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Delete...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'cal_events', 'where' => "event_id=$event_id AND event_calendar_id=$cal_id" ) );
		
		//-----------------------------------------
		// Recache...
		//-----------------------------------------
		
		$this->_call_recache();
		
		//-----------------------------------------
		// Boing...
		//-----------------------------------------
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['cal_event_delete'] , "act=calendar&amp;cal_id={$cal_id}" );
	}
	
	/*-------------------------------------------------------------------------*/
 	// Event Approve
 	/*-------------------------------------------------------------------------*/
	
	function event_approve()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cal_id    = intval( $this->ipsclass->input['cal_id'] );
		$event_id  = intval( $this->ipsclass->input['event_id'] );
		$approve   = intval( $this->ipsclass->input['approve'] );
		$modfilter = trim( $this->ipsclass->input['modfilter'] );
		$quicktime = trim( $this->ipsclass->input['qt'] );
		$md5check  = trim( $this->ipsclass->input['md5check'] );
		
		list( $month, $day, $year ) = explode( "-", $quicktime );
		
		//-----------------------------------------
		// Get permissions
		//-----------------------------------------
		
		$this->build_permissions( $cal_id );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->can_moderate )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Check MD5
		//-----------------------------------------
		
		if ( $md5check != $this->ipsclass->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Check Dates
		//-----------------------------------------
		
		if ( ! $day OR ! $month OR ! $year )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Get Event
		//-----------------------------------------
		
		$event = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_calendar_id={$cal_id} and event_id={$event_id}" ) );
		
		if ( ! $event['event_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Update event...
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'cal_events', array( 'event_approved' => $event['event_approved'] ? 0 : 1 ), 'event_id='.$event_id );
		
		//-----------------------------------------
		// Recache...
		//-----------------------------------------
		
		$this->_call_recache();
		
		//-----------------------------------------
		// Boink...
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."act=calendar&cal_id={$cal_id}&modfilter={$modfilter}&code=showday&y={$year}&m={$month}&d={$day}");
	}
	
	/*-------------------------------------------------------------------------*/
 	// Build Permissions
 	/*-------------------------------------------------------------------------*/
	
	function build_permissions( $cal_id=0 )
	{
		$this->can_read        = 0;
		$this->can_post        = 0;
		$this->can_avoid_queue = 0;
		$this->can_moderate    = 0;
		
		//-----------------------------------------
		// Got an idea?
		//-----------------------------------------
		
		if ( ! $cal_id )
		{
			$cal_id = $this->calendar_id;
		}
		
		$calendar = $this->calendar_cache[ $cal_id ];
		
		//-----------------------------------------
		// Read
		//-----------------------------------------
		
		if ( $this->ipsclass->check_perms( $calendar['perm_read'] ) == TRUE )
		{
			$this->can_read = 1;
		}
		
		//-----------------------------------------
		// Post
		//-----------------------------------------
		
		if ( $this->ipsclass->check_perms( $calendar['perm_post'] ) == TRUE )
		{
			$this->can_post = 1;
		}
		
		//-----------------------------------------
		// Mod Queue
		//-----------------------------------------
		
		if ( $this->ipsclass->check_perms( $calendar['perm_nomod'] ) == TRUE )
		{
			$this->can_avoid_queue = 1;
		}
		
		//-----------------------------------------
		// Moderate
		//-----------------------------------------
		
		if ( $this->ipsclass->member['g_is_supmod'] )
		{
			$this->can_moderate = 1;
		}
		
	}
	
	/*-------------------------------------------------------------------------*/
 	// Find
 	/*-------------------------------------------------------------------------*/
	
	function find_date()
	{
		if ( $this->ipsclass->input['what'] )
		{
			if ( $this->ipsclass->input['what'] == 'thismonth' )
			{
				$this->ipsclass->boink_it( $this->ipsclass->base_url."act=calendar&amp;cal_id={$this->calendar_id}&amp;m={$this->now_date['mon']}&amp;y={$this->now_date['year']}" );
			}
			else
			{
				$time = time() + $this->ipsclass->get_time_offset();
				
				$this->ipsclass->boink_it( $this->ipsclass->base_url."act=calendar&amp;cal_id={$this->calendar_id}&amp;code=showweek&amp;week={$time}" );
			}
		}
		else
		{
			$this->show_month();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show WEEK
	/*-------------------------------------------------------------------------*/
	
	function show_week()
	{
        $in_week  = intval($this->ipsclass->input['week']);
        
        //-----------------------------------------
        // Get start of week
        //-----------------------------------------
        
        $startweek = $this->ipsclass->date_getgmdate( $in_week );
        
        if( !$this->ipsclass->vars['ipb_calendar_mon'] )
        {
	        //-----------------------------------------
	        // Not Sunday? Go back..
	        //-----------------------------------------
	        
	        $startweek['wday'] = intval($startweek['wday']);
	        
	        if ( $startweek['wday'] > 0 )
	        {
				while ( $startweek['wday'] != 0 )
				{
					$startweek['wday']--;
					$in_week -= 86400;
				}
				
				$startweek = $this->ipsclass->date_getgmdate( $in_week );
	        }
        }
        else
        {
	        //-----------------------------------------
	        // Not Monday, rewind...
	        // date_getgmdate will set weekday start to 1
	        // PHP 5.1 allows for 'N' which will fix
	        // this, but we support earlier versions..
	        //-----------------------------------------
	        
	        if ( $startweek['wday'] != 1 )
	        {
	            $startweek['wday'] = $startweek['wday'] == 0 ? 7 : $startweek['wday'];
	            
	            while ( $startweek['wday'] != 1 )
	            {
	                $startweek['wday']--;
	                $in_week -= 86400;
	            }
	            
	            $startweek = $this->ipsclass->date_getgmdate( $in_week );
	        }
        }	        
        
        //-----------------------------------------
        // Get end of week
        //-----------------------------------------
        
        $endweek       = $this->ipsclass->date_getgmdate( $in_week + 604800 );
        $our_datestamp = gmmktime( 0,0,0, $startweek['mon'], $startweek['mday'], $startweek['year']);
        $our_timestamp = $in_week;
        $seen_days     = array(); // Holds yday
		$seen_ids      = array();
		
        //-----------------------------------------
        // Figure out the next / previous links
        //-----------------------------------------
        
        $prev_month = $this->get_prev_month($this->chosen_month, $this->chosen_year);
        $next_month = $this->get_next_month($this->chosen_month, $this->chosen_year);
        
        $prev_week = $this->ipsclass->date_getgmdate( $in_week - 604800 );
        $next_week = $this->ipsclass->date_getgmdate( $in_week + 604800 );
        
        $this->output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_week_content( $startweek['mday'], $this->month_words[$startweek['mon'] - 1], $startweek['year'], $prev_week, $next_week);
        
        $last_month_id = -1;
        
		//-----------------------------------------
        // Get the events
        //-----------------------------------------
        
        $this->get_events_sql($startweek['mon'], $startweek['year']);
		
        //-----------------------------------------
        // Print each effing day :D
        //-----------------------------------------
        
        $cal_output = "";
        
        for ( $i = 0 ; $i <= 6 ; $i++ )
        {
        	$year   = gmdate('Y', $our_datestamp);
			$month  = gmdate('n', $our_datestamp);
			$day    = gmdate('j', $our_datestamp);
        	$today  = $this->ipsclass->date_getgmdate($our_datestamp);
        	$this_day_events = "";
        	
        	if ( $last_month_id != $today['mon'] )
        	{
        		$last_month_id = $today['mon'];
        	
        		$cal_output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_week_monthbar( $this->month_words[$today['mon'] - 1], $today['year'] );
        	
        		//-----------------------------------------
				// Get the birthdays from the database
				//-----------------------------------------
				
				if ( $this->ipsclass->vars['show_bday_calendar'] )
				{
					$birthdays = array();
					
					$this->get_birthday_sql($today['mon']);
					
					$birthdays = $this->query_bday_cache[ $today['mon'] ];
				}
				
				//-----------------------------------------
				// Get the events
				//-----------------------------------------
				
				$this->get_events_sql($month, $year);
			}
			
			$events       = $this->get_day_events( $month, $day, $year );

			$queued_event = 0;
			
			if ( is_array( $events ) AND count( $events ) )
			{
				foreach( $events as $event )
				{ 
					if ( !isset($this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ]) OR !$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] )
					{
						//-----------------------------------------
						// Recurring
						//-----------------------------------------
						
						if ( $event['recurring'] )
						{
							$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_recurring( $event );
						}
						else if ( $event['single'] )
						{
							$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap( $event );
						}
						else
						{
							$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_range( $event );
						}
						
						$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] = 1;
					}
					
					//-----------------------------------------
					// Queued events?
					//-----------------------------------------
					
					if ( ! $event['event_approved'] AND $this->can_moderate )
					{
						$queued_event = 1;
					}
				}
			}
			
			//-----------------------------------------
			// Birthdays
			//-----------------------------------------
			
			if ( $this->calendar['cal_bday_limit'] )
			{
				if ( isset($birthdays[ $today['mday'] ]) and count( $birthdays[ $today['mday'] ] ) > 0 )
				{
					$no_bdays = count($birthdays[ $today['mday'] ]);
					
					if ( $this->calendar['cal_bday_limit'] and $no_bdays <= $this->calendar['cal_bday_limit'] )
					{
						foreach( $birthdays[ $today['mday'] ] as $user )
						{
							$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_week_events_wrap(
																															"code=birthdays&amp;y=".$today['year']."&amp;m=".$today['mon']."&amp;d=".$today['mday'],
																															$user['members_display_name'].$this->ipsclass->lang['bd_birthday']
																														  );
						}
	
					}
					else
					{
						$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_week_events_wrap(
																													   "code=birthdays&amp;y=".$today['year']."&amp;m=".$today['mon']."&amp;d=".$today['mday'],
																													   sprintf( $this->ipsclass->lang['entry_birthdays'], count($birthdays[ $today['mday'] ]) )
																													 );
					}
				}
			}
			
			if ($this_day_events == "")
			{
				$this_day_events = '&nbsp;';
			}
			
			if( $this->ipsclass->vars['ipb_calendar_mon'] )
			{
				// Reset if Monday is first day
				
				$today['wday'] = $today['wday'] == 0 ? 6 : $today['wday'] - 1;
			}
			
        	$cal_output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_week_dayentry( $this->day_words[ $today['wday'] ], $today['mday'], $this->month_words[$today['mon'] - 1], $today['mon'], $today['year'], $this_day_events, $queued_event );
        	
        	$our_datestamp += 86400;
        	
        	unset($this_day_events);
        }
        
        //-----------------------------------------
        // Switch the HTML tags...
        //-----------------------------------------
       
        $this->output = str_replace( "<!--IBF.DAYS_CONTENT-->"  , $cal_output, $this->output );
        
        $this->output = str_replace( "<!--IBF.MONTH_BOX-->"     , $this->get_month_dropdown(), $this->output );
        $this->output = str_replace( "<!--IBF.YEAR_BOX-->"      , $this->get_year_dropdown() , $this->output );
        
        //-----------------------------------------
        // Get prev / this / next calendars
        //-----------------------------------------
        
        $this->output = str_replace( "<!--PREV.MONTH-->", $this->get_mini_calendar( $prev_month['month_id'], $prev_month['year_id'] ), $this->output );
        $this->output = str_replace( "<!--THIS.MONTH-->", $this->get_mini_calendar( $this->chosen_month    , $this->chosen_year     ), $this->output );
        $this->output = str_replace( "<!--NEXT.MONTH-->", $this->get_mini_calendar( $next_month['month_id'], $next_month['year_id'] ), $this->output );
        	
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
        $this->nav[] = $this->month_words[$this->chosen_month - 1]." ".$this->chosen_year;
        
    }
    
	/*-------------------------------------------------------------------------*/
	// SHOW MONTH
	/*-------------------------------------------------------------------------*/
	
	function show_month()
	{
        //-----------------------------------------
        // Figure out the next / previous links
        //-----------------------------------------
        
        $prev_month = $this->get_prev_month($this->chosen_month, $this->chosen_year);
        $next_month = $this->get_next_month($this->chosen_month, $this->chosen_year);
       
        $this->output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_main_content($this->month_words[$this->chosen_month - 1], $this->chosen_year, $prev_month, $next_month, $this->calendar_jump, $this->calendar_id);
        
        //-----------------------------------------
        // Print the days table top row
        //-----------------------------------------
        
        $day_output = "";
        $cal_output = "";
        
		foreach ($this->day_words as $day)
        {
        	$day_output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_day_bit($day);
        }
        
        $cal_output = $this->get_month_events($this->chosen_month, $this->chosen_year);
        
        //-----------------------------------------
        // Switch the HTML tags...
        //-----------------------------------------
       
        $this->output = str_replace( "<!--IBF.DAYS_TITLE_ROW-->", $day_output, $this->output );
        $this->output = str_replace( "<!--IBF.DAYS_CONTENT-->"  , $cal_output, $this->output );
        
        $this->output = str_replace( "<!--IBF.MONTH_BOX-->"     , $this->get_month_dropdown(), $this->output );
        $this->output = str_replace( "<!--IBF.YEAR_BOX-->"      , $this->get_year_dropdown() , $this->output );
        
        //-----------------------------------------
        // Get prev / this / next calendars
        //-----------------------------------------
        
        $this->output = str_replace( "<!--PREV.MONTH-->", $this->get_mini_calendar( $prev_month['month_id'], $prev_month['year_id'] ), $this->output );
        $this->output = str_replace( "<!--THIS.MONTH-->", $this->get_mini_calendar( $this->chosen_month    , $this->chosen_year     ), $this->output );
        $this->output = str_replace( "<!--NEXT.MONTH-->", $this->get_mini_calendar( $next_month['month_id'], $next_month['year_id'] ), $this->output );
        	
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
        $this->nav[] = $this->month_words[$this->chosen_month - 1]." ".$this->chosen_year;
    }
    
    /*-------------------------------------------------------------------------*/
    // POST NEW CALENDAR EVENT
    /*-------------------------------------------------------------------------*/
    
    function cal_event_form( $type='add' )
    {
    	//-----------------------------------------
        // Load post class
        //-----------------------------------------
        
        require_once( ROOT_PATH.'sources/classes/post/class_post.php' );
 		$this->post           =  new class_post();
 		$this->post->ipsclass =& $this->ipsclass;
 		$this->post->load_classes();
 			    
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$event_id      = isset($this->ipsclass->input['event_id']) ? intval( $this->ipsclass->input['event_id'] ) : 0;
    	$calendar_id   = isset($this->ipsclass->input['calendar_id']) ? intval( $this->ipsclass->input['calendar_id'] ) : 0;
    	$form_type     = $this->ipsclass->input['formtype'];
    	$recur_menu    = "";
    	$calendar_jump = "";
    	$divhide	   = "none";
    	
    	//-----------------------------------------
    	// CHECK
    	//-----------------------------------------
    	
		if ( ! $this->ipsclass->member['id'])
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Got permission to post to this calendar?
		//-----------------------------------------
		
		$this->build_permissions( $calendar_id );
		
		if ( ! $this->can_post )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Edit calendar option
		//-----------------------------------------
		
		foreach( $this->calendar_cache as $data )
		{
			if ( $this->ipsclass->check_perms( $data['perm_post'] ) == TRUE AND $this->ipsclass->check_perms( $data['perm_read'] ) == TRUE )
			{
				$selected       = $calendar_id == $data['cal_id'] ? ' selected="selected" ' : '';
				$calendar_jump .= "<option value='{$data['cal_id']}'{$selected}>{$data['cal_title']}</option>\n";
			}
		}
		
    	//-----------------------------------------
    	// WHICHISIT
    	//-----------------------------------------
    	
    	if ( $type == 'add' )
    	{
			$tmp = isset($this->ipsclass->input['nd']) ? explode( "-", $this->ipsclass->input['nd'] ) : array();
			
			$nd = isset($tmp[2]) ? intval( $tmp[2] ) : 0;
			$nm = isset($tmp[1]) ? intval( $tmp[1] ) : 0;
			$ny = isset($tmp[0]) ? intval( $tmp[0] ) : 0;
			
			$public  = "";
			$private = "";
			$event   = array( 'event_smilies' => 1, 'event_content' => '' );
			
			$fd = $nd = $nd ? $nd : $this->now['mday'];
			$fm = $nm = $nm ? $nm : $this->now['mon'];
			$fy = $ny = $ny ? $ny : $this->now['year'];
			
			$tz_offset  = ( $this->ipsclass->member['time_offset'] != "" ) ? $this->ipsclass->member['time_offset'] : $this->ipsclass->vars['time_offset'];
			
			$recur_menu = "<option value='1'>{$this->ipsclass->lang['fv_days']}</option><option value='2'>{$this->ipsclass->lang['fv_months']}</option><option value='3'>{$this->ipsclass->lang['fv_years']}</option>";
			$code       = 'addnewevent';
			$button     = $this->ipsclass->lang['calendar_submit'];
			
			$event['event_timeset'] = "";
		}
		else
		{
			if ( ! $event_id )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}
			
			//-----------------------------------------
			// Get the event
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=$event_id" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( ! $event = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}
			
			//-----------------------------------------
			// Do we have permission to edit this event?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['id'] == $event['event_member_id'] )
			{
				$can_edit = 1;
			}
			else if ( $this->ipsclass->member['g_is_supmod'] == 1 )
			{
				$can_edit = 1;
			}
			else
			{
				$can_edit = 0;
			}
			
			if ( $can_edit != 1 )
			{ 
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}
			
			//-----------------------------------------
			// Do we have permission to see the event?
			//-----------------------------------------
			
			if ( $event['event_perms'] != '*' )
			{
				$this_member_mgroups[] = $this->ipsclass->member['mgroup'];
				
				if( $this->ipsclass->member['mgroup_others'] )
				{
					$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) );
				}

				$check = 0;
				
				foreach( $this_member_mgroups as $this_member_mgroup )
				{
					if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $event['event_perms'] ) )
					{
						$check = 1;
					}
				}
				
				if( $check == 0 )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
				}
			}
			
			$tz_offset = $event['event_tz'];
			
			//-----------------------------------------
			// Date stuff
			//-----------------------------------------
			
			$convert_hours = 0;
			
			if( $event['event_timeset'] )
			{
				$divhide = "";
				$hour_min = explode(":", $event['event_timeset']);
				$convert_hours = ($hour_min[0] * 3600) + ($hour_min[1] * 60);
			}
			
			if( $tz_offset && $event['event_timeset'] AND !$event['event_all_day'] )
			{
				$convert_hours = $tz_offset * 3600 + $convert_hours;
			}
			
			$_unix_from = explode('-', gmdate('n-j-Y', $event['event_unix_from'] - $convert_hours ));
			
			if( $convert_hours && $event['event_unix_to'] )
			{
				$event['event_unix_to'] -= $convert_hours;
			}
			$_unix_to   = explode('-', gmdate('n-j-Y', $event['event_unix_to']  ));

			$nd = $_unix_from[1];
			$nm = $_unix_from[0];
			$ny = $_unix_from[2];
			
			$fd = $_unix_to[1];
			$fm = $_unix_to[0];
			$fy = $_unix_to[2];
			
			//-----------------------------------------
			// Form stuff
			//-----------------------------------------
			
			if ( $event['event_recurring'] )
			{
				$form_type = 'recur';
			}
			else if ( ! $event['event_recurring'] AND $event['event_unix_to'] )
			{
				$form_type = 'range';
			}
			else
			{
				$form_type = 'single';
			}
			
			//-----------------------------------------
			// Private?
			//-----------------------------------------
			
			if ( $event['event_private'] == 1 )
			{
				$private = ' selected';
			}
			else
			{
				$public = ' selected';
			}
			
			//-----------------------------------------
			// Recur stuff
			//-----------------------------------------
			
			$recur_menu .= $event['event_recurring'] == '1' ? "<option value='1' selected='selected'>{$this->ipsclass->lang['fv_days']}</option>"
															: "<option value='1'>{$this->ipsclass->lang['fv_days']}</option>";
														
			$recur_menu .= $event['event_recurring'] == '2' ? "<option value='2' selected='selected'>{$this->ipsclass->lang['fv_months']}</option>"
															: "<option value='2'>{$this->ipsclass->lang['fv_months']}</option>";
														
			$recur_menu .= $event['event_recurring'] == '3' ? "<option value='3' selected='selected'>{$this->ipsclass->lang['fv_years']}</option>"
															: "<option value='3'>{$this->ipsclass->lang['fv_years']}</option>";
			$code   = 'doedit';
			$button = $this->ipsclass->lang['calendar_edit_submit'];
		}
		
		//-----------------------------------------
		// Do TZ form
		//-----------------------------------------
		
		$this->ipsclass->load_language( 'lang_ucp' );
		
 		$time_select = "<select name='event_tz' class='forminput'>";
 		
 		//-----------------------------------------
 		// Loop through the langauge time offsets and names to build our
 		// HTML jump box.
 		//-----------------------------------------
 		
 		foreach( $this->ipsclass->lang as $off => $words )
 		{
 			if ( preg_match("/^time_(-?[\d\.]+)$/", $off, $match))
 			{
				$time_select .= $match[1] == $tz_offset ? "<option value='{$match[1]}' selected='selected'>$words</option>\n"
												        : "<option value='{$match[1]}'>$words</option>\n";
 			}
 		}
 		
 		$time_select .= "</select>";
		
		//-----------------------------------------
		// Start off nav
		//-----------------------------------------
		
		$this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
		$this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
		$this->nav[] = $this->ipsclass->lang['post_new_event'];
		
		//-----------------------------------------
		// Start off form
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_start_form( $code, $this->calendar_id, $form_type, $event_id );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->table_top($this->ipsclass->lang['post_new_event']);
		$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_event_title( isset($event['event_title']) ? $event['event_title'] : '' );
		
		//-----------------------------------------
		// Alright... Which form type?
		//-----------------------------------------
		
		if ( $form_type == 'single' )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_choose_date_single(
																			       $this->get_day_dropdown($nd), $this->get_month_dropdown($nm), $this->get_year_dropdown($ny) );
		}
		else if ( $form_type == 'range' )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_choose_date_range(
																				   $this->get_day_dropdown($nd), $this->get_month_dropdown($nm), $this->get_year_dropdown($ny),
																				   $this->get_day_dropdown($fd), $this->get_month_dropdown($fm), $this->get_year_dropdown($fy)  );
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_choose_date_recur(
																				   $this->get_day_dropdown($nd), $this->get_month_dropdown($nm), $this->get_year_dropdown($ny),
																				   $recur_menu,
																				   array(
																						  'd' => $this->get_day_dropdown($fd),
																						  'm' => $this->get_month_dropdown($fm),
																						  'y' => $this->get_year_dropdown($fy)
																						)  );
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_event_type( $public, $private, $time_select, $this->calendar_jump, 
								array('formtype' => $form_type, 'timestart' => $event['event_timeset'], 'divhide' => $divhide, 'checked' => $divhide == '' ? "checked='checked'" : '' ) );
		
		if ($this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'])
		{
			//-----------------------------------------
			// Get all the group ID's and names from
			// the DB and build the selection box
			//-----------------------------------------
			
			$group_choices = "";
			
			foreach( $this->ipsclass->cache['group_cache'] as $r )
			{
				$selected = "";
				
				if ( isset($event['event_perms']) AND preg_match( "/(^|,)".$r['g_id']."(,|$)/", $event['event_perms'] ) )
				{
					$selected = ' selected';
				}
				
				$group_choices .= "<option value='".$r['g_id']."'".$selected.">".$r['g_title']."</option>\n";
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_admin_group_box($group_choices);
		}
		
		//-----------------------------------------
		// Using RTE? Convert BBCode to HTML
		//-----------------------------------------
		
		if ( $event['event_content'] )
		{
			$this->post->parser->parse_html    = 0;
			$this->post->parser->parse_smilies = intval($event['event_smilies']);
			$this->post->parser->parse_bbcode  = 1;
							
			if ( $this->post->han_editor->method == 'rte' )
			{
				#$event['event_content'] = $this->post->parser->pre_edit_parse( $event['event_content'] );
				$event['event_content'] = $this->post->parser->convert_ipb_html_to_html( $event['event_content'] );	
			}
			else
			{
				$event['event_content'] = $this->post->parser->pre_edit_parse( $event['event_content'] );
			}
		}
		
		//-----------------------------------------
		// Generate text editor
		//-----------------------------------------
		
		$this->output .= $this->post->html_add_smilie_box( $this->post->html_post_body( $event['event_content'] ) );
		
		$this->output  = str_replace( '<!--IBF.EMO-->'  , $this->ipsclass->compiled_templates['skin_post']->get_box_enableemo( $event['event_smilies'] ? 'checked="checked"' : "" )  , $this->output );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->calendar_end_form( $button );
	}
	
	
    /*-------------------------------------------------------------------------*/
    // ADD NEW CALENDAR EVENT TO THE DB
    /*-------------------------------------------------------------------------*/
    
    function cal_event_save( $type='add' )
    {
    	//-----------------------------------------
        // Load post class
        //-----------------------------------------
        
        require_once( ROOT_PATH.'sources/classes/post/class_post.php' );
 		$this->post           =  new class_post();
 		$this->post->ipsclass =& $this->ipsclass;
 		$this->post->load_classes();
 			    
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$read_perms   = '*';
		$end_day      = "";
		$end_month    = "";
		$end_year     = "";
		$end_date     = "";
		$event_ranged = 0;
		$event_repeat = 0;
		$can_edit     = 0;
		
		$form_type         = $this->ipsclass->input['formtype'];
		$event_id          = intval($this->ipsclass->input['event_id']);
    	$calendar_id       = intval( $this->ipsclass->input['calendar_id'] );
    	$allow_emoticons   = $this->ipsclass->input['enableemo'] == 'yes'     ? 1 : 0;
		$private_event     = $this->ipsclass->input['e_type']    == 'private' ? 1 : 0;
		$event_title       = trim($this->ipsclass->input['event_title']);
		$day               = intval($this->ipsclass->input['e_day']);
		$month             = intval($this->ipsclass->input['e_month']);
		$year              = intval($this->ipsclass->input['e_year']);
		$end_day           = intval($this->ipsclass->input['end_day']);
		$end_month         = intval($this->ipsclass->input['end_month']);
		$end_year          = intval($this->ipsclass->input['end_year']);
		$recur_unit        = intval($this->ipsclass->input['recur_unit']);
		$event_tz          = intval($this->ipsclass->input['event_tz']);
		$offset            = 0; 
		$event_all_day	   = 0;
		$event_calendar_id = intval($this->ipsclass->input['event_calendar_id']);
		$set_time		   = intval($this->ipsclass->input['set_times']);
		$hour_min		   = array();

		if( $set_time )
		{
			$hour_min	   = strstr($this->ipsclass->input['event_timestart'], ":") ? explode(":", $this->ipsclass->input['event_timestart']) : 0;

			if( intval($hour_min[0]) < 0 || intval($hour_min[0]) > 23 )
			{
				$hour_min[0] = 0;
			}
			
			if( intval($hour_min[1]) < 0 || intval($hour_min[1]) > 59 )
			{
				$hour_min[1] = 0;
			}
			
			if( $hour_min[0] || $hour_min[1] )
			{
				$offset	= $event_tz * 3600;
			}
			else
			{
				$hour_min 	= array();
				$offset		= 0;
			}
		}
		else
		{
			$event_all_day	= 1;
		}
		
		$this->ipsclass->vars['max_post_length'] = $this->ipsclass->vars['max_post_length'] ? $this->ipsclass->vars['max_post_length'] : 2140000;
		
    	//-----------------------------------------
    	// CHECK
    	//-----------------------------------------
    	
		if ( ! $this->ipsclass->member['id'])
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// Got permission to post to this calendar?
		//-----------------------------------------
		
		$this->build_permissions( $event_calendar_id );
		
		if ( ! $this->can_post )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
		
		//-----------------------------------------
		// WHATDOWEDO?
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
		
		}
		else
		{
			if ( ! $event_id )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_post') );
			}
			
			//-----------------------------------------
			// Get the event
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=$event_id" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( ! $event = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}
			
			//-----------------------------------------
			// Do we have permission to edit this event?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['id'] == $event['event_member_id'] )
			{
				$can_edit = 1;
			}
			else if ( $this->ipsclass->member['g_is_supmod'] == 1 )
			{
				$can_edit = 1;
			}
			
			if ( $can_edit != 1 )
			{ 
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}
		}
		
		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if (strlen( trim($_POST['Post']) ) < 1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_post') );
		}
		
		if (strlen( $_POST['Post'] ) > ($this->ipsclass->vars['max_post_length']*1024))
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'post_too_long') );
		}
		
		//-----------------------------------------
		// Fix up the Event Title
		//-----------------------------------------
		
		if ( (strlen($event_title) < 2) or (!$event_title)  )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_title_none') );
		}
		
		if ( strlen($event_title) > 64 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_title_long') );
		}
		
		//-----------------------------------------
		// Are we an admin, and have we set w/groups
		// can see?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] )
		{
			if ( is_array( $_POST['e_groups'] ) )
			{
				$read_perms = implode( ",", $_POST['e_groups'] );
				$read_perms .= ",".$this->ipsclass->vars['admin_group'];
			}
			
			if ($read_perms == "")
			{
				$read_perms = '*';
			}
		}
		
		//-----------------------------------------
		// Check dates: Range
		//-----------------------------------------
		
		if ( $form_type == 'range' )
		{	
			if ( $end_year < $year )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_range_wrong') );
			}
			
			if ( $end_year == $year )
			{
				if ( $end_month < $month )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_range_wrong') );
				}
				
				if ( $end_month == $month AND $end_day <= $day )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_range_wrong') );
				}
			}
			
			$_final_unix_from = gmmktime(0 , 0, 0  , $month    , $day    , $year    ) + $offset;// + $offset; # Midday
			$_final_unix_to   = gmmktime(23, 59, 59, $end_month, $end_day, $end_year) + $offset;// + $offset; # End of the day
			
			$event_ranged = 1;
			$set_time 	  = 0;
			$hour_min 	  = array();
		}
		
		//-----------------------------------------
		// Check dates: Recur
		//-----------------------------------------
		
		elseif ( $form_type == 'recur' )
		{
			if ( $this->ipsclass->input['recur_unit'] )
			{
				$event_repeat = 1;
			}
			
			if ( $end_year < $year )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_range_wrong') );
			}
			
			if ( $end_year == $year )
			{
				if ( $end_month < $month )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_range_wrong') );
				}
				
				if ( $end_month == $month AND $end_day <= $day )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_range_wrong') );
				}
			}
			
			$hour = 0;
			$min  = 0;
			if( $set_time )
			{
				if( is_array( $hour_min ) )
				{
					$hour = $hour_min[0];
					$min  = $hour_min[1];
				}
			}
			
			$_final_unix_from = gmmktime($hour , $min , 0 , $month    , $day    , $year    ) + $offset;// + $offset;
			$_final_unix_to   = gmmktime($hour, $min, 0, $end_month, $end_day, $end_year) + $offset;// + $offset; # End of the day
			$event_recur      = 1;
		}
		
		//-----------------------------------------
		// Check dates: Single
		//-----------------------------------------
		
		else
		{
			$hour = 0;
			$min  = 0;
			if( $set_time )
			{
				if( is_array( $hour_min ) )
				{
					$hour = $hour_min[0];
					$min  = $hour_min[1];
				}
			}
			
			$_final_unix_from = gmmktime($hour, $min, 0, $month, $day, $year) + $offset;// + $offset;
			$_final_unix_to   = 0;
		}
		
		//-----------------------------------------
		// Do we have a sensible date?
		//-----------------------------------------
		
		if ( ! checkdate( $month, $day , $year ) )
        {
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_date_oor') );
		}
		
		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
		
		$this->post->parser->parse_html    = 0;
		$this->post->parser->parse_smilies = intval($allow_emoticons);
		$this->post->parser->parse_bbcode  = 1;
					
 		$this->ipsclass->input['Post'] = $this->post->han_editor->process_raw_post( 'Post' );

 		$this->ipsclass->input['Post'] = $this->post->parser->pre_db_parse( $this->ipsclass->input['Post'] );
 		$this->ipsclass->input['Post'] = $this->post->parser->pre_display_parse( $this->ipsclass->input['Post'] );
 		$this->ipsclass->input['Post'] = $this->post->parser->bad_words( $this->ipsclass->input['Post'] );
 		
 		//-----------------------------------------
 		// Event approved?
 		//-----------------------------------------
 		
 		$event_approved = $this->can_avoid_queue ? 1 : ( $this->calendar_cache[ $event_calendar_id ]['cal_moderate'] ? 0 : 1 );
 		
 		if( $private_event == 1 )
 		{
	 		$event_approved = 1;
 		}
 		
 		if ( $type == 'add' )
 		{
			//-----------------------------------------
			// Add it to the DB
			//-----------------------------------------
			
			$this->ipsclass->DB->do_insert( 'cal_events', array (
																 'event_calendar_id' => $event_calendar_id,
																 'event_member_id'   => $this->ipsclass->member['id'],
																 'event_content'     => $this->ipsclass->input['Post'],
																 'event_title'       => $event_title,
																 'event_smilies'     => $allow_emoticons,
																 'event_perms'       => $read_perms,
																 'event_private'     => $private_event,
																 'event_approved'    => $event_approved,
																 'event_unixstamp'   => time(),
																 'event_recurring'   => $recur_unit,
																 'event_tz'          => $event_tz,
																 'event_timeset'	 => count($hour_min) > 0 ? intval($hour_min[0]).":".intval($hour_min[1]) : 0,
																 'event_unix_from'   => $_final_unix_from,
																 'event_unix_to'     => $_final_unix_to,
																 'event_all_day'	 => $event_all_day ) );
															
			$this->_call_recache();
			
			if ( $event_approved )
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['new_event_redirect'] , "act=calendar&amp;cal_id={$event_calendar_id}" );
			}
			else
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['new_event_mod'] , "act=calendar&amp;cal_id={$event_calendar_id}" );
			}
		}
		else
		{
			//-----------------------------------------
			// Add it to the DB
			//-----------------------------------------
			
			$this->ipsclass->DB->do_update( 'cal_events', array (
																 'event_calendar_id' => $event_calendar_id,
																 'event_content'     => $this->ipsclass->input['Post'],
																 'event_title'       => $event_title,
																 'event_smilies'     => $allow_emoticons,
																 'event_perms'       => $read_perms,
																 'event_private'     => $private_event,
																 'event_approved'    => $event_approved,
																 'event_unixstamp'   => time(),
																 'event_recurring'   => $recur_unit,
																 'event_tz'          => $event_tz,
																 'event_timeset'	 => count($hour_min) > 0 ? intval($hour_min[0]).":".intval($hour_min[1]) : 0,																 
																 'event_unix_from'   => $_final_unix_from,
																 'event_unix_to'     => $_final_unix_to,
																 'event_all_day'	 => $event_all_day ), 'event_id='.$event_id );
															
			$this->_call_recache();
			
			if ( $event_approved )
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['edit_event_redirect'] , "act=calendar&amp;cal_id={$event_calendar_id}&amp;code=showevent&amp;event_id=$event_id" );
			}
			else
			{
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['new_event_mod'] , "act=calendar&amp;cal_id={$event_calendar_id}" );
			}
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
    // SHOW DAYS EVENTS
    /*-------------------------------------------------------------------------*/
    
    function show_day()
    {
        $day       = intval($this->ipsclass->input['d']);
		$month     = intval($this->ipsclass->input['m']);
		$year      = intval($this->ipsclass->input['y']);
		$seen_ids  = array();
		$timenow   = gmmktime( 0,0,0, $month, $day, $year);
		$day_array = $this->ipsclass->date_getgmdate( $timenow  );
		
		$printed = 0;
		 
		//-----------------------------------------
		// Do we have a sensible date?
		//-----------------------------------------
		
		if ( ! checkdate( $month, $day , $year ) )
        {
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_date_oor') );
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_page_events_start();
        
        //-----------------------------------------
        // Get the events
        //-----------------------------------------
        
        $this->get_events_sql($month, $year);
        
        $events = $this->get_day_events( $month, $day, $year );
        		
		if ( is_array( $events ) AND count( $events ) )
		{
			foreach( $events as $event )
			{ 
				if ( !isset($this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ]) OR !$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] )
				{
					//-----------------------------------------
					// Is it a private event?
					//-----------------------------------------
					
					if ( $event['event_private'] == 1 and $this->ipsclass->member['id'] != $event['event_member_id'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Do we have permission to see the event?
					//-----------------------------------------
					
					if ( $event['event_perms'] != '*' )
					{
						$this_member_mgroups[] = $this->ipsclass->member['mgroup'];
						
						if( $this->ipsclass->member['mgroup_others'] )
						{
							$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) );
						}
		
						$check = 0;
						
						foreach( $this_member_mgroups as $this_member_mgroup )
						{
							if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $event['event_perms'] ) )
							{
								$check = 1;
							}
						}
						
						if( $check == 0 )
						{
							continue;
						}
					}
					
					if ( !isset($seen_ids[ $event['eventid'] ]) OR !$seen_ids[ $event['eventid'] ] )
					{
						$this->output .= $this->make_event_html($event);
					
						$printed++;
						$seen_ids[ $event['event_id'] ] = 1;
					}
				}
			}
		}

        //-----------------------------------------
        // Do we have any printed events?
        //-----------------------------------------
        
        if ($printed > 0)
        {
        	$switch = 1;
        }
        else
        {
        	// Error if no birthdays
        	$switch = 0;
        }
        
        $this->output .= $this->make_birthday_html($month, $day, $switch);
        
        $this->output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_page_events_end();
        
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
		$this->nav[] = $day." ".$this->month_words[$this->chosen_month - 1]." ".$this->chosen_year;
    }
	
	/*-------------------------------------------------------------------------*/
    // SHOW A SINGLE EVENT BASED ON eventid
    /*-------------------------------------------------------------------------*/
    
    function show_event()
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		 
        $event_id = intval($this->ipsclass->input['event_id']);
		
		//-----------------------------------------
		// CHECK
		//-----------------------------------------
		
		if ( ! $event_id )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
		}
		
		//-----------------------------------------
		// Get it from the DB
		//-----------------------------------------
        
        $this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'cal_events', 'where' => "event_id=$event_id" ) );
		$this->ipsclass->DB->simple_exec();
        
        if ( ! $event = $this->ipsclass->DB->fetch_row() )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
        }
        
        $set_offset = 0;
        if( $event['event_timeset'] )
		{
			$set_offset = $this->ipsclass->member['time_offset'] * 3600;
		}        
        
        $event['event_unix_from'] = $event['event_unix_from'] - $set_offset;
        //$event['event_unix_to'] = $event['event_unix_to'] ? $event['event_unix_to'] + ( $this->ipsclass->member['time_offset'] * 3600 ) : 0;
        
        //-----------------------------------------
        // Is it a private event?
        //-----------------------------------------
        
        if ( $event['event_private'] == 1 and $this->ipsclass->member['id'] != $event['event_member_id'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
        }
        
        //-----------------------------------------
        // Do we have permission to see the event?
        //-----------------------------------------
        
        if ( $event['event_perms'] != '*' )
		{
			$this_member_mgroups[] = $this->ipsclass->member['mgroup'];
			
			if( $this->ipsclass->member['mgroup_others'] )
			{
				$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) );
			}

			$check = 0;
			
			foreach( $this_member_mgroups as $this_member_mgroup )
			{
				if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $event['event_perms'] ) )
				{
					$check = 1;
				}
			}
			
			if( $check == 0 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}			
		}
        
        $this->output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_page_events_start() . $this->make_event_html($event) . $this->ipsclass->compiled_templates['skin_calendar']->cal_page_events_end();
        
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
		$this->nav[] = $event['event_title'];
        
    }
    
    /*-------------------------------------------------------------------------*/
    // MAKE EVENT HTML (return HTML for bdays)
    /*-------------------------------------------------------------------------*/
    
    function make_event_html($event)
    {
    	$approve_button = "";
    	
		//-----------------------------------------
        // What kind of event is it?
        //-----------------------------------------
        
        $event_type = $this->ipsclass->lang['public_event'];
        
        if ($event['event_private'] == 1)
        {
        	$event_type = $this->ipsclass->lang['private_event'];
        }
        else if ($event['event_perms'] != '*')
        {
        	$event_type = $this->ipsclass->lang['restricted_event'];
        }
        
        //-----------------------------------------
        // Do we have an edit button?
        //-----------------------------------------
        
        $edit_button = "";
        
        //-----------------------------------------
        // Are we a super dooper moderator?
        //-----------------------------------------
        
        if ( $this->ipsclass->member['g_is_supmod'] == 1 )
        {
        	$edit_button = $this->ipsclass->compiled_templates['skin_calendar']->cal_edit_del_button($event['event_id'], $event['event_calendar_id']);
        }
        
        //-----------------------------------------
        // Are we the OP of this event?
        //-----------------------------------------
        
        else if ( $this->ipsclass->member['id'] == $event['event_member_id'] )
        {
        	$edit_button = $this->ipsclass->compiled_templates['skin_calendar']->cal_edit_del_button($event['event_id'], $event['event_calendar_id']);
        }
        
        //-----------------------------------------
        // Get the member details and stuff
        //-----------------------------------------
        
        if( $this->parsed_members[ $event['event_member_id'] ] )
        {
	        $member = $this->parsed_members[ $event['event_member_id'] ];
        }
        else
        {
	        $this->ipsclass->DB->cache_add_query( 'generic_get_all_member', array( 'mid' => $event['event_member_id'] ) );
			$this->ipsclass->DB->cache_exec_query();
			
	        $member = $this->ipsclass->DB->fetch_row();
	        
	        $member = $this->ipsclass->parse_member( $member, 0, 'skin_calendar' );
	        
	        $this->parsed_members[ $member['id'] ] = $member;
        }
        
		if ( $member['id'] )
		{
			$member['_members_display_name'] = "<a href='{$this->base_url}showuser={$member['id']}'>{$member['members_display_name_short']}</a>";
		}        
        
        //-----------------------------------------
        // Date
        //-----------------------------------------
        
        $set_offset = 0;
        if( $event['event_timeset'] AND !$event['event_all_day'] )
		{
			$set_offset = $this->ipsclass->member['time_offset'] * 3600;
		}	        
        
        $tmp  = explode( ',', gmdate( 'n,j,Y,G,i', $event['event_unix_from'] ) );
        
        $event['mday']       = $tmp[1];
        $event['month']      = $tmp[0];
        $event['year']       = $tmp[2];
        $event['month_text'] = $this->month_words[ $tmp[0] - 1 ];
        
        $this->ipsclass->input['d'] = $event['mday'];
        $this->ipsclass->input['m'] = $event['month'];
        $this->ipsclass->input['y'] = $event['year'];
        
        $type = $this->ipsclass->lang['se_normal'];
        $de   = "";
        
        if ( $event['event_recurring'] == 0 AND $event['event_unix_to'] )
        {
        	$type = $this->ipsclass->lang['se_range'];
        	$de   = $this->ipsclass->lang['se_ends'].' '.gmdate( $this->ipsclass->vars['clock_joined'], $event['event_unix_to'] );
        }
        else if ( $event['event_recurring'] == 1 )
        {
        	$type = $this->ipsclass->lang['se_recur'];
        	$de   = $this->ipsclass->lang['se_ends'].' '.gmdate( $this->ipsclass->vars['clock_joined'], $event['event_unix_to'] - $set_offset );
        }
        
        if( $type == $this->ipsclass->lang['se_normal'] )
        {
	        if( $tmp[3] > 0 )
	        {
		        $event['year'] .= " {$tmp[3]}:{$tmp[4]}";
	        }
        }
        
        //-----------------------------------------
        // Moderated?
        //-----------------------------------------
        
        $event['_quicktime'] = intval($event['month']).'-'.intval($event['mday']).'-'.intval( $event['year']);
        
        if ( $this->can_moderate )
        {
			if ( ! $event['event_approved'] )
			{
				$event['_event_css_1'] = 'row2shaded';
				$event['_event_css_2'] = 'row4shaded';
				$approve_button = $this->ipsclass->compiled_templates['skin_calendar']->cal_approve_button($event['event_id'], $event['event_calendar_id'], $event);
			}
			else
			{
				$event['_event_css_1'] = 'row1';
				$event['_event_css_2'] = 'row2';
				$approve_button = $this->ipsclass->compiled_templates['skin_calendar']->cal_unapprove_button($event['event_id'], $event['event_calendar_id'], $event);
			}
        }
        else
        {
        	$event['_event_css_1'] = 'row1';
			$event['_event_css_2'] = 'row2';
		}
		

        //-----------------------------------------
        // Show
        //-----------------------------------------
        
        return $this->ipsclass->compiled_templates['skin_calendar']->cal_show_event($event, $member, $event_type, $edit_button, $approve_button, $type, $de );
    }
    
    /*-------------------------------------------------------------------------*/
    // GET MINI CALENDAR
    /*-------------------------------------------------------------------------*/
    
    function get_mini_calendar($month, $year)
    {
	    $cal_output = "";
	    
        foreach ($this->day_words as $day)
        {
			$day = $this->ipsclass->txt_mbsubstr( $day, 0, 1 );
                       	        
        	$cal_output .= $this->ipsclass->compiled_templates['skin_calendar']->mini_cal_day_bit( $day );
        }
        
        //-----------------------------------------
        // Print the main calendar body
        //-----------------------------------------
        
        $cal_output .= $this->get_month_events( $month, $year, 1 );
        
        return $this->ipsclass->compiled_templates['skin_calendar']->mini_cal_mini_wrap($this->month_words[$month - 1], $month, $year, $cal_output);
    }
    
    /*-------------------------------------------------------------------------*/
    // Get BIRTHDAYS
    /*-------------------------------------------------------------------------*/
    
    function get_birthday_sql($month)
    {
    	if ( !isset($this->query_bday_cache[ $month ]) OR !is_array( $this->query_bday_cache[ $month ] ) )
		{
			// We are just going to query next and previous month
			// so let's do it in one query and cache it...
			
			$prev_month = $this->get_prev_month( $month, date("Y") );
			$next_month = $this->get_next_month( $month, date("Y") );
			
			$this->query_bday_cache[ $month ] 					= array();
			$this->query_bday_cache[ $next_month['month_id'] ] 	= array();
			$this->query_bday_cache[ $prev_month['month_id'] ] 	= array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'bday_day, bday_month, id, members_display_name', 'from' => 'members', 'where' => 'bday_month IN('.$prev_month['month_id'].','.$month.','.$next_month['month_id'].')' ) );
			$this->ipsclass->DB->simple_exec();
			
			while ($r = $this->ipsclass->DB->fetch_row())
			{
				$this->query_bday_cache[ $r['bday_month'] ][ $r['bday_day'] ][] = $r;
			}
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // Get EVENTS
    /*-------------------------------------------------------------------------*/
   
    function get_events_sql( $month=0, $year=0, $get_cached=array() )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	if ( ! count( $get_cached ) )
    	{
			//-----------------------------------------
	        // Mini-cal is going to call next month and
	        // previous month anyways....let's just pull
	        // it all in one query and cache it
	        //-----------------------------------------
        	    	
			$next_month = $this->get_next_month( $month, $year );
			$prev_month = $this->get_prev_month( $month, $year );
			$numberdays = date('t', mktime(0, 0, 0, $next_month['month_id'], 1, $next_month['year_id'] ) );
			$timenow    = gmmktime( 0, 0, 1   , $prev_month['month_id'], 1, $prev_month['year_id'] ) - (12 * 3600);
			$timethen   = gmmktime( 23, 59, 59, $next_month['month_id'], $numberdays, $next_month['year_id']) + (12 * 3600);
			$getcached  = 0;
        }
        else
        {
	        $next_month = array( 'month_id' => 0 );
	        $prev_month = array( 'month_id' => 0 );
       		$timenow    = $get_cached['timenow'];
       		$timethen   = $get_cached['timethen'];
       		list( $month, $day, $year ) = explode( ',', gmdate('n,j,Y', $get_cached['timenow']) );
       		$getcached  = 1;
		}
		
		//-----------------------------------------
        // Get the events
        //-----------------------------------------
        
        if ( !isset($this->event_cache[ $month ]) OR ! is_array( $this->event_cache[ $month ] ) )
		{
			//-----------------------------------------
			// Get for cache
			//-----------------------------------------
			
			if ( $getcached )
			{
				$extra = ( isset($get_cached['cal_id']) AND $get_cached['cal_id'] ) ? "event_calendar_id=".intval($get_cached['cal_id'])." AND " : '';

				$this->ipsclass->DB->cache_add_query( 'calendar_get_events_cache', array( 'extra'    => $extra,
																						  'timenow'  => $timenow,
																						  'timethen' => $timethen,
																						  'month'    => $month )
													 );
																
				/*$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'cal_events',
															  'where'  => "$extra event_approved=1
																		   AND ( (event_unix_to >= $timenow AND event_unix_from <= $timethen )
																			  OR ( event_unix_to=0 AND event_unix_from >= $timenow AND event_unix_from <= $timethen )
																			  OR ( event_recurring=3 AND FROM_UNIXTIME(event_unix_from,'%c')={$month} AND event_unix_to <= $timethen ) )" ) );*/
				$this->ipsclass->DB->cache_exec_query();
			}
			else
			{
				//-----------------------------------------
				// Get for display
				//-----------------------------------------

				$approved = $this->can_moderate ? "event_approved IN (0,1)" : "event_approved=1";
				
				$this->ipsclass->DB->cache_add_query( 'calendar_get_events', array( 'approved' => $approved,
																							'calendar_id' => $this->calendar_id,
																							'timenow' => $timenow,
																							'timethen' => $timethen,
																							'month' => $prev_month['month_id'].','.$month.','.$next_month['month_id'] ) 
													);
																				
				/*$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'cal_events',
															  'where'  => "event_calendar_id = {$this->calendar_id} AND {$approved}
																		   AND ( (event_unix_to >= $timenow AND event_unix_from <= $timethen )
																			  OR ( event_unix_to=0 AND event_unix_from >= $timenow AND event_unix_from <= $timethen )
																			  OR ( event_recurring=3 AND FROM_UNIXTIME(event_unix_from,'%c')={$month} AND event_unix_to <= $timethen ) )" ) );*/
				$this->ipsclass->DB->cache_exec_query();
			}
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				//-----------------------------------------
				// Private event?
				//-----------------------------------------
				
				if ( $r['event_private'] == 1 AND ! $getcached )
				{
					if ( ! $this->ipsclass->member['id'] )
					{
						continue;
					}
					
					if ( $this->ipsclass->member['id'] != $r['event_member_id'] )
					{
						continue;
					}
				}
				
				//-----------------------------------------
				// Got permission?
				//-----------------------------------------
				
				if ( $r['event_perms'] != '*' AND ! $getcached )
				{
					$this_member_mgroups[] = $this->ipsclass->member['mgroup'];
					
					if( $this->ipsclass->member['mgroup_others'] )
					{
						$this_member_mgroups = array_merge( $this_member_mgroups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) );
					}
	
					$check = 0;
					
					foreach( $this_member_mgroups as $this_member_mgroup )
					{
						if ( preg_match( "/(^|,)".$this_member_mgroup."(,|$)/", $r['event_perms'] ) )
						{
							$check = 1;
						}
					}
					
					if( $check == 0 )
					{
						continue;
					}					
				}
				
				//-----------------------------------------
				// Times
				//-----------------------------------------
				
				$set_offset = 0;
				if( $r['event_timeset'] AND !$r['event_all_day'] )
				{
					$set_offset = $this->ipsclass->member['time_offset'] * 3600;
				}
				
				$r['_unix_from'] = $r['event_unix_from'] ? $r['event_unix_from'] - $set_offset  : 0;
				$r['_unix_to']   = $r['event_unix_to'] > 0  ? $r['event_unix_to'] - $set_offset  : 0;

				
				//-----------------------------------------
				// Recurring event?
				//-----------------------------------------
				
				if ( $r['event_recurring'] > 0 )
				{
					$r['recurring'] = 1;

					// Get recurring months
					$r_month = 0;
					$r_stamp = $r['event_unix_from'];
					$r_month = gmdate('n', $r['_unix_from']);
					$r_year  = gmdate('Y', $r['_unix_from']);
									
					if( $r_month == $month && ($r_year == $year OR $r['event_recurring']==3) && $getcached == 0 )
					{
						$this->event_cache[ $r_month ]['recurring'][] = $r;
					}
					
					while( $r_stamp < $r['_unix_to'] )
					{
						// Stop Duplicates!
						$shouldpass = 1;
						if ( isset($this->event_cache[ $r_month ]['recurring']) AND count( $this->event_cache[ $r_month ]['recurring'] ) )
						{
							foreach ( $this->event_cache[ $r_month ]['recurring'] as $eventarray )
							{
								if ( $eventarray['event_id'] == $r['event_id'] )
								{
									$shouldpass = 0;
								}
							}
						}
						
						if( ( ( $r_month != $month AND $r_month != $next_month['month_id'] AND $r_month != $prev_month['month_id'] )
							 OR $r_year != $year) AND $getcached == 0 )
						{
							$shouldpass = 0;
						}

						if ( $shouldpass == 1 )
						{
							$this->event_cache[ $r_month ]['recurring'][] = $r;
						}
						
						if ( $r['event_recurring'] == 1 )
						{
							$r_stamp += 604800;
						}
						elseif ( $r['event_recurring'] == 2 )
						{
							$r_stamp += 86400 * 30;
						}
						else
						{
							// No need to check year, as month would then match anyways
							$r_stamp += 31536000;
						}

						if ( $r_month != gmdate('n', $r_stamp) )
						{
							$r_month = gmdate('n', $r_stamp);
						}
						
						if( $r_year != gmdate('Y', $r_stamp) )
						{
							$r_year  = gmdate('Y', $r_stamp);
						}						
					}
				}
				
				//-----------------------------------------
				// Ranged event?
				// OK, this is getting silly.....
				// _checkdate -> gmmtime( 0,0,0 $_checkdate..
				// was showing a day earlier than allowed
				//-----------------------------------------
				
				else if ( $r['event_recurring'] == 0 AND $r['_unix_to'] )
				{
					$_gotit         = array();
					$_begin         = gmdate( "z", $r['_unix_from'] );
					$_checkdate		= gmdate( "z", $r['_unix_to'] );
					$_checkts		= $r['_unix_from'];
										
					$_cur_mo		= $month; // Store to retrieve later if necessary
					
					$_tmp           = '';
					$r['ranged']    = 1;
					
					// Lapse over a year?

					if( $_checkdate < $_begin )
					{
						$tmp_difference = 365 + $_checkdate - $_begin;
						
						if( $tmp_difference > 0 )
						{
							$_checkdate = $_begin + $tmp_difference;
						}
						
						unset( $tmp_difference );
					}

					while ( $_begin <= $_checkdate  )
					{
						// Did we lapse over the month?
						// How about the year?  If so,
						// let's go ahead and reset ourselves

						$realday = gmdate( "j", $_checkts );
						$month = gmdate( "n", $_checkts );
						
						if ( !array_key_exists( $month, $_gotit ) )
						{
							$_gotit[ $month ] = array();
						}
						if ( !in_array( $realday, $_gotit[ $month ] ) )
						{
							$this->event_cache[ $month ]['ranged'][ $realday ][] = $r;
						
							$_count =  count( $this->event_cache[ $month ]['ranged'][ $realday ] ) - 1;
							$_tmp   =  $this->event_cache[ $month ]['ranged'][ $realday ][ $_count ];
							$_gotit[ $month ][] = $realday;
						}
						else
						{
							if ( $r['_unix_to'] != $r['event_unix_from'] )
							{
								$this->event_cache[ $month ]['ranged'][ $realday ][] = $_tmp;
							}
						}
						$_begin += 1;
						$_checkts += 86400;
					}
					$month = $_cur_mo;
				}
				
				//-----------------------------------------
				// Single event
				//-----------------------------------------
				
				else
				{
					$r['single'] = 1;
					
					# Make sure correct month is used for cached queries
					list( $_month, $_day, $_year ) = explode( ',', gmdate('n,j,Y', $r['_unix_from']  ) );
					
					$this->event_cache[ $_month ]['single'][ $_day ][] = $r;
				}
			}
		}
    }
    
	/*-------------------------------------------------------------------------*/
	// Get day's events
	/*-------------------------------------------------------------------------*/

	function get_day_events( $month="", $day="", $year="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = array();
		
		//-----------------------------------------
		// Ranged
		//-----------------------------------------
		
		if ( isset($this->event_cache[ $month ]['ranged'][ $day ]) AND is_array( $this->event_cache[ $month ]['ranged'][ $day ] ) and count( $this->event_cache[ $month ]['ranged'][ $day ] ) )
		{ 
			foreach( $this->event_cache[ $month ]['ranged'][ $day ] as $idx => $data )
			{ 
				$return[] = $this->event_cache[ $month ]['ranged'][ $day ][ $idx ];
			}
		}
		
		//-----------------------------------------
		// Recurring
		//-----------------------------------------
		
		if ( isset($this->event_cache[ $month ]['recurring']) AND is_array( $this->event_cache[ $month ]['recurring'] ) and count( $this->event_cache[ $month ]['recurring'] ) )
		{
			foreach( $this->event_cache[ $month ]['recurring'] as $idx => $data )
			{
				if ( $this->get_info_events( $data, $month, $day, $year ) )
				{ 
					$return[] = $this->event_cache[ $month ]['recurring'][ $idx ];
				}
			}
		}
		
		//-----------------------------------------
		// Single day
		//-----------------------------------------
		
		
		if ( isset($this->event_cache[ $month ]['single'][ $day ]) AND is_array( $this->event_cache[ $month ]['single'][ $day ] ) and count( $this->event_cache[ $month ]['single'][ $day ] ) )
		{
			foreach( $this->event_cache[ $month ]['single'][ $day ] as $idx => $data )
			{ 
				$return[] = $this->event_cache[ $month ]['single'][ $day ][ $idx ];
			}
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
    // Get event info
    /*-------------------------------------------------------------------------*/
	
	function get_info_events( $event, $month, $day, $year, $adj=1 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_start  = gmmktime(0 , 0 , 0 , $month, $day, $year);
		$_lunch  = gmmktime(12, 0 , 0 , $month, $day, $year);
		$_end    = gmmktime(23, 59, 59, $month, $day, $year) + 1;
		$_month  = gmmktime(0 , 0 , 0 , $month, 1   , $year);
		$_offset = 0; // - Set at time of save: $event['event_tz'] * 3600;
		
		//-----------------------------------------
		// Already seen it?
		//-----------------------------------------
		
		if ( $event['event_id'] AND isset($this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ]) AND $this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Check we're in range
		//-----------------------------------------
		
		if ( isset($event['single']) AND $event['single'] )
		{
			if ( $month.','.$day.','.$year == gmdate('n,j,Y', $event['_unix_from']) )
			{
				$this->shown_events[ $month.'-'.$day.'-'.$year ][ $event['event_id'] ] = 1;
				return TRUE;
			}
		}
				
		if ( ($event['_unix_to']) < $_start OR ($event['_unix_from'] ) > $_end )
		{ 
			return FALSE;
		}
		
		//-----------------------------------------
		// Check recurring
		//-----------------------------------------
		
		if ( $event['event_recurring'] )
		{
			if ( $adj AND gmdate('w', $event['_unix_from']) != gmdate('w', $event['event_unix_from']) )
			{
				if ( $event['_unix_from'] > $event['event_unix_from'])
				{
					$_lunch -= 86400;
				}
				else
				{
					$_lunch += 86400;
				}
			}
				
			//-----------------------------------------
			// Weekly
			//-----------------------------------------
			
			if ( $event['event_recurring'] == 1 )
			{ 
				if ( gmdate('w', $event['event_unix_from']) != gmdate('w', $_lunch ) )
				{
					return FALSE;
				}
				else
				{
					return TRUE;
				}
			}
			
			//-----------------------------------------
			// Monthly
			//-----------------------------------------
			
			else if ( $event['event_recurring'] == 2 )
			{
				if ( gmdate('j', $event['event_unix_from']) == gmdate('j', $_lunch ) )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			
			//-----------------------------------------
			// Yearly
			//-----------------------------------------
			
			else if ( $event['event_recurring'] == 3 )
			{
				if ( (gmdate('j', $event['event_unix_from']) == gmdate('j', $_lunch )) AND (gmdate('n', $event['event_unix_from']) == gmdate('n', $_lunch )) )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
    /*-------------------------------------------------------------------------*/
    // Get month stuff
    /*-------------------------------------------------------------------------*/
    
    function get_month_events( $month="", $year="", $minical=0 )
	{
		//-----------------------------------------
		// Reset shown events
		//-----------------------------------------
		
		$this->shown_events = array();
		
		//-----------------------------------------
		// Work out timestamps
		//-----------------------------------------
		
        $our_datestamp   = gmmktime( 0,0,0, $month, 1, $year);
        $first_day_array = $this->ipsclass->date_getgmdate($our_datestamp);
        
        if( $this->ipsclass->vars['ipb_calendar_mon'] )
        {
	        $first_day_array['wday'] = $first_day_array['wday'] == 0 ? 7 : $first_day_array['wday'];
        }
     
        //-----------------------------------------
        // Get the birthdays from the database
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['show_bday_calendar'] )
        {
			$birthdays = array();
			
			$this->get_birthday_sql($month);
			
			$birthdays = $this->query_bday_cache[ $month ];
		}
        
		//-----------------------------------------
        // Get the events
        //-----------------------------------------
        
        $this->get_events_sql($month, $year);

        //-----------------------------------------
        // Get events
        //-----------------------------------------
        
        $seen_days = array(); 
        $seen_ids  = array();
        $cal_output = "";
        
        for ( $c = 0 ; $c < 42; $c++ )
        {
        	//-----------------------------------------
			// Work out timestamps
			//-----------------------------------------
			
        	$_year      = gmdate('Y', $our_datestamp);
			$_month     = gmdate('n', $our_datestamp);
			$_day       = gmdate('j', $our_datestamp);
        	$day_array  = $this->ipsclass->date_getgmdate($our_datestamp);
        	
        	$check_against = $c;
        	
        	if( $this->ipsclass->vars['ipb_calendar_mon'] )
        	{
	        	$check_against = $c+1;
        	}
        	        	
        	if ( (($c) % 7 ) == 0 )
        	{
        		//-----------------------------------------
        		// Kill the loop if we are no longer on our month
        		//-----------------------------------------
        		
        		if ($day_array['mon'] != $month)
        		{
        			break;
        		}
        		
        		if ( $minical )
        		{
        			$cal_output .= $this->ipsclass->compiled_templates['skin_calendar']->mini_cal_new_row( $our_datestamp );
        		}
        		else
        		{
        			$cal_output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_new_row( $our_datestamp );
        		}
        	}
        	
        	//-----------------------------------------
        	// Run out of legal days for this month?
        	// Or have we yet to get to the first day?
        	//-----------------------------------------
        		
        	if ( ($check_against < $first_day_array['wday']) or ($day_array['mon'] != $month) )
        	{
        		$cal_output .= $minical ? $this->ipsclass->compiled_templates['skin_calendar']->mini_cal_blank_cell()
        								: $this->ipsclass->compiled_templates['skin_calendar']->cal_blank_cell();
        	}
        	else
        	{
        		if ( isset($seen_days[ $day_array['yday'] ]) AND $seen_days[ $day_array['yday'] ] == 1 )
				{
					continue;
				}
        	
        		$seen_days[ $day_array['yday'] ] = 1;
        		$tmp_cevents     = array();
        		$this_day_events = "";
        		$cal_date        = $day_array['mday'];
        		$queued_event    = 0;
        		$cal_date_queued = "";
        		
        		//-----------------------------------------
        		// Get events
        		//-----------------------------------------
        		
        		$events = $this->get_day_events( $_month, $_day, $_year );
        		
				if ( is_array( $events ) AND count( $events ) )
				{
					foreach( $events as $event )
					{ 
						if ( !isset($this->shown_events[ $_month.'-'.$_day.'-'.$_year ][ $event['event_id'] ]) OR !$this->shown_events[ $_month.'-'.$_day.'-'.$_year ][ $event['event_id'] ] )
						{
							//-----------------------------------------
							// Recurring
							//-----------------------------------------
							
							if ( isset($event['recurring']) )
							{
								$tmp_cevents[ $event['event_id'] ] = $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_recurring( $event );
							}
							else if ( isset($event['single']) )
							{
								$tmp_cevents[ $event['event_id'] ] = $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap( $event );
							}
							else
							{
								$tmp_cevents[ $event['event_id'] ] = $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_range( $event );
							}
							
							$this->shown_events[ $_month.'-'.$_day.'-'.$_year ][ $event['event_id'] ] = 1;
							
							//-----------------------------------------
							// Queued events?
							//-----------------------------------------
							
							if ( ! $event['event_approved'] AND $this->can_moderate )
							{
								$queued_event = 1;
							}
						}
					}
					
					//-----------------------------------------
					// How many events?
					//-----------------------------------------
					
					if ( count($tmp_cevents) >= $this->calendar['cal_event_limit'] )
					{
						$this_day_events = $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_manual(
																												  		"cal_id={$this->calendar_id}&amp;code=showday&amp;y=".$day_array['year']."&amp;m=".$day_array['mon']."&amp;d=".$day_array['mday'],
																												  		sprintf( $this->ipsclass->lang['show_n_events'], intval(count($tmp_cevents)) ) );
					}
					else if ( count( $tmp_cevents ) )
					{
						$this_day_events = implode( "\n", $tmp_cevents );
					}
					
					$tmp_cevents[] = array();
        		}
        		
				//-----------------------------------------
				// Birthdays
				//-----------------------------------------
				
				if ( $this->calendar['cal_bday_limit'] )
				{
					if ( isset($birthdays[ $day_array['mday'] ]) and count( $birthdays[ $day_array['mday'] ] ) > 0 )
					{
						$no_bdays = count($birthdays[ $day_array['mday'] ]);
						
						if ( $no_bdays )
						{
							if ( $this->calendar['cal_bday_limit'] and $no_bdays <= $this->calendar['cal_bday_limit'] )
							{
								foreach( $birthdays[ $day_array['mday'] ] as $user )
								{
									$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_manual(
																												"cal_id={$this->calendar_id}&amp;code=birthdays&amp;y=".$day_array['year']."&amp;m=".$day_array['mon']."&amp;d=".$day_array['mday'],
																												$user['members_display_name'].$this->ipsclass->lang['bd_birthday'] );
								}
			
							}
							else
							{
								$this_day_events .= $this->ipsclass->compiled_templates['skin_calendar']->cal_events_wrap_manual(
																															 "cal_id={$this->calendar_id}&amp;code=birthdays&amp;y=".$day_array['year']."&amp;m=".$day_array['mon']."&amp;d=".$day_array['mday'],
																															 sprintf( $this->ipsclass->lang['entry_birthdays'], $no_bdays ) );
							}
						}
					}
        		}
        		
        		//-----------------------------------------
        		// Show it
        		//-----------------------------------------
        		
        		if ($this_day_events != "")
        		{
        			$cal_date        = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}&amp;code=showday&amp;y=".$year."&amp;m=".$month."&amp;d=".$day_array['mday']."'>{$day_array['mday']}</a>";
        			$cal_date_queued = "{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}&amp;modfilter=queued&amp;code=showday&amp;y=".$year."&amp;m=".$month."&amp;d=".$day_array['mday'];
        			
        			$this_day_events = $this->ipsclass->compiled_templates['skin_calendar']->cal_events_start() . $this_day_events . $this->ipsclass->compiled_templates['skin_calendar']->cal_events_end();
        		}
        		
        		if ( ($day_array['mday'] == $this->now_date['mday']) and ($this->now_date['mon'] == $day_array['mon']) and ($this->now_date['year'] == $day_array['year']))
        		{
        			$cal_output .= $minical ? $this->ipsclass->compiled_templates['skin_calendar']->mini_cal_date_cell_today($cal_date, $this_day_events) : $this->ipsclass->compiled_templates['skin_calendar']->cal_date_cell_today($cal_date, $this_day_events, $cal_date_queued, $queued_event);
        		}
        		else
        		{
        			$cal_output .= $minical ? $this->ipsclass->compiled_templates['skin_calendar']->mini_cal_date_cell($cal_date, $this_day_events) : $this->ipsclass->compiled_templates['skin_calendar']->cal_date_cell($cal_date, $this_day_events, $cal_date_queued, $queued_event);
        		}
        		
        		unset($this_day_events);
        		
        		$our_datestamp += 86400;
        	}
        }
        
        return $cal_output;
    }
	
	/*-------------------------------------------------------------------------*/
    // SHOW BIRTHDAYS
    /*-------------------------------------------------------------------------*/
    
    function show_birthdays()
    {
        $day   = intval($this->ipsclass->input['d']);
		$month = intval($this->ipsclass->input['m']);
		$year  = intval($this->ipsclass->input['y']);
		
		//-----------------------------------------
		// Do we have a sensible date?
		//-----------------------------------------
		
		if ( ! checkdate( $month, $day , $year ) )
        {
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_date_oor') );
		}
        
        $this->output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_page_events_start() . $this->make_birthday_html($month, $day) . $this->ipsclass->compiled_templates['skin_calendar']->cal_page_events_end();
        
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar'>{$this->ipsclass->lang['page_title']}</a>";
        $this->nav[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;cal_id={$this->calendar_id}'>{$this->calendar['cal_title']}</a>";
		$this->nav[] = $this->ipsclass->lang['cal_birthdays']." ".$day." ".$this->month_words[$this->chosen_month - 1]." ".$this->chosen_year;
        
    }
    
    /*-------------------------------------------------------------------------*/
    // MAKE BIRTHDAY HTML (return HTML for bdays)
    /*-------------------------------------------------------------------------*/
    
    function make_birthday_html($month, $day, $switch=0)
    {
    	if ( ! $this->ipsclass->vars['show_bday_calendar'] )
    	{
    		return;
    	}
    	
    	//-----------------------------------------
        // Is it leapyear?
        //-----------------------------------------
        
        if( !date("L") )
        {
	        if( $month == "2" AND $day == "28" )
	        {
		        $where_string = "bday_month=".$month." AND (bday_day={$day} OR bday_day=29)";
	        }
	        else
	        {
		       $where_string = 'bday_month='.$month . " AND bday_day={$day}";
	        }	        
        }
        else
        {
	        $where_string = 'bday_month='.$month . " AND bday_day={$day}";
        }
            	
    	//-----------------------------------------
        // Get the birthdays from the database
        //-----------------------------------------
        
        $birthdays = array();
        
        $output    = "";
        
        $this->ipsclass->DB->simple_construct( array( 'select' => 'bday_day, bday_month, bday_year, id, members_display_name',
        							  				  'from'   => 'members',
        							  				  'where'  =>  $where_string ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			if ($switch == 1)
			{
				return;
			}
			else
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cal_no_events') );
			}
		}
		else
		{
			$output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_birthday_start();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$age = $this->chosen_year - $r['bday_year'];
				
				$output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_birthday_entry($r['id'], $r['members_display_name'], $age);
			}
			
			$output .= $this->ipsclass->compiled_templates['skin_calendar']->cal_birthday_end();
		}
		
		return $output;
	}
		
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function get_month_dropdown($month="")
    {
    	$return = "";
    	
    	if ($month == "")
    	{
    		$month = $this->chosen_month;
    	}
    	
    	for ( $x = 1 ; $x <= 12 ; $x++ )
    	{
    		$return .= "\t<option value='$x'";
    		$return .= ($x == $month) ? " selected='selected'" : "";
    		$return .= ">".$this->month_words[$x-1]."</option>\n";
    	}
    	
    	return $return;
    }
    
    
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function get_year_dropdown($year="")
    {
    	$return = "";
    	
    	$this->ipsclass->vars['start_year'] = (isset($this->ipsclass->vars['start_year'])) ? $this->ipsclass->vars['start_year'] : 2001;
		$this->ipsclass->vars['year_limit'] = (isset($this->ipsclass->vars['year_limit'])) ? $this->ipsclass->vars['year_limit'] : 5;
    	
    	if ($year == "")
    	{
    		$year = $this->chosen_year;
    	}
    	
    	for ( $x = $this->ipsclass->vars['start_year'] ; $x <= $this->now_date['year'] + $this->ipsclass->vars['year_limit'] ; $x++ )
    	{
    		$return .= "\t<option value='$x'";
    		$return .= ($x == $year) ? " selected='selected'" : "";
    		$return .= ">".$x."</option>\n";
    	}
    	
    	return $return;
    }
    
    
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function get_day_dropdown($day="")
    {
    	if ($day == "")
    	{
    		$day = $this->now_date['mday'];
    	}
    	
    	$return = "";
    	
    	for ( $x = 1 ; $x <= 31 ; $x++ )
    	{
    		$return .= "\t<option value='$x'";
    		$return .= ($x == $day) ? " selected='selected'" : "";
    		$return .= ">".$x."</option>\n";
    	}
    	
    	return $return;
    }
    
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function get_color_dropdown($name, $active="")
    {
    	$c = array( 'white', 'black', 'aliceblue', 'lightslategray', 'blue','gray', 'yellow', 'orange', 'red', 'lightblue', 'darkblue', 'aqua', 'green', 'lime', 'maroon', 'navy', 'silver', 'teal' );
    	
    	$return = "<select name='$name' class='forminput' onchange=\"document.REPLIER.style{$name}.style.backgroundColor = this.options[this.selectedIndex].value;\">";
    	
    	foreach( $c as $i )
    	{
    		$sel = "";
    		
    		if ( $active == $i )
    		{
    			$sel = ' selected="selected"';
    		}
    		
    		$return .= "<option value='$i'{$sel}>$i</option>\n";
    	}
    	
    	return $return."</select>";
    
    }
    
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function get_next_month($month, $year)
    {
    	//-----------------------------------------
        // Figure out the next  links
        //-----------------------------------------
        
        $next_month = array();
        
        $next_month['year_id']    = $year;
        
        $next_month['month_name'] = $this->month_words[$month];
        $next_month['month_id']   = $month + 1;
        
        if ($next_month['month_id'] > 12 )
        {
        	$next_month['month_name'] = $this->month_words[0];
            $next_month['month_id']   = 1;
            $next_month['year_id']    = $year + 1;
        }
    	
    	return $next_month;
    }
    
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function _call_recache()
    {
		require_once( ROOT_PATH . 'sources/action_admin/calendars.php' );
		$calendars           =  new ad_calendars();
		$calendars->ipsclass =& $this->ipsclass;
		
		$calendars->calendar_rebuildcache( 0 );
    }
    
    /*-------------------------------------------------------------------------*/
    // Internal
    /*-------------------------------------------------------------------------*/
    
    function get_prev_month($month, $year)
    {
    	//-----------------------------------------
        // Figure out the next / previous links
        //-----------------------------------------
        
        $prev_month = array();
        
        $prev_month['year_id']    = $year;
        
        $prev_month['month_id']   = $month - 1;
        $prev_month['month_name'] = $this->month_words[$month - 2];
        
        if ($this->chosen_month == 1)
        {
        	$prev_month['month_name'] = $this->month_words[11];
        	$prev_month['month_id']   = 12;
        	$prev_month['year_id']    = $year - 1;
        	
        }
    	
    	return $prev_month;
    }
}

?>