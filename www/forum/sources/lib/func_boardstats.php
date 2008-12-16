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
|   > $Date: 2007-02-14 15:17:35 -0500 (Wed, 14 Feb 2007) $
|   > $Revision: 848 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Board index functions module
|   > Module written by Matt Mecham
|   > Date started: 18th November 2003
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Fri 21 May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}


class func_boardstats
{
	# Global
	var $ipsclass;
	
	var $class    = "";
	var $sep_char = '<{ACTIVE_LIST_SEP}>';
	
	var $users_online  = "";
	var $total_posts   = "";
	var $total_members = "";
	
	/*-------------------------------------------------------------------------*/
	// register_class
	// ------------------
	// Register a $this-> class with this module
	/*-------------------------------------------------------------------------*/
	
	function register_class()
	{
		
		// NO LONGER NEEDED
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// DISPLAY ACTIVE USERS
	//
	/*-------------------------------------------------------------------------*/
	
	function active_users()
	{
		$active = array( 'TOTAL'   => 0 ,
						 'NAMES'   => "",
						 'GUESTS'  => 0 ,
						 'MEMBERS' => 0 ,
						 'ANON'    => 0 ,
					   );
					   
		$stats_html = "";
		
		if ( $this->ipsclass->vars['show_active'] )
		{
			if ($this->ipsclass->vars['au_cutoff'] == "")
			{
				$this->ipsclass->vars['au_cutoff'] = 15;
			}
			
			//-----------------------------------------
			// Get the users from the DB
			//-----------------------------------------
			
			$cut_off = $this->ipsclass->vars['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			$rows    = array();
			$ar_time = time();
			
			if ( $this->ipsclass->member['id'] )
			{
				$rows = array( $ar_time.'.'.md5(microtime()) => array( 'id'			 => 0,
												  'login_type'   => substr($this->ipsclass->member['login_anonymous'],0, 1),
												  'running_time' => $ar_time,
												  'member_id'    => $this->ipsclass->member['id'],
												  'member_name'  => $this->ipsclass->member['members_display_name'],
												  'member_group' => $this->ipsclass->member['mgroup'] ) );
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, member_id, member_name, login_type, running_time, member_group',
														  'from'   => 'sessions',
														  'where'  => "running_time > $time",
														  //'order'  => "running_time DESC" // Sort in PHP to avoid filesort in SQL
												 )      );
			
			
			$this->ipsclass->DB->simple_exec();
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );
			
			//-----------------------------------------
			// Is this a root admin in disguise?
			// Is that kinda like a diamond in the rough?
			//-----------------------------------------
						
			$our_mgroups = array();
			
			if( isset($this->ipsclass->member['mgroup_others']) AND $this->ipsclass->member['mgroup_others'] )
			{
				$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
			}
			
			$our_mgroups[] = $this->ipsclass->member['mgroup'];
			
			//-----------------------------------------
			// cache all printed members so we
			// don't double print them
			//-----------------------------------------
			
			$cached = array();
			
			foreach ( $rows as $result )
			{
				$last_date = $this->ipsclass->get_time( $result['running_time'] );
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( strstr( $result['id'], '_session' ) )
				{
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------
					
					$botname = preg_replace( '/^(.+?)=/', "\\1", $result['id'] );
					
					if ( ! $cached[ $result['member_name'] ] )
					{
						if ( $this->ipsclass->vars['spider_anon'] )
						{
							if ( in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )  )
							{
								$active['NAMES'] .= "{$result['member_name']}*{$this->sep_char} \n";
							}
						}
						else
						{
							$active['NAMES'] .= "{$result['member_name']}{$this->sep_char} \n";
						}
						
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------
						
						$active['GUESTS']++;
					}
				}
				
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				
				else if ( ! $result['member_id'] )
				{
					$active['GUESTS']++;
				}
				
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = $this->ipsclass->make_name_formatted( $result['member_name'], $result['member_group'] );
						
						if ($result['login_type'])
						{
							if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
							{
								$active['NAMES'] .= "<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}' title='$last_date'>{$result['member_name']}</a>*{$this->sep_char} \n";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'] .= "<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}' title='$last_date'>{$result['member_name']}</a>{$this->sep_char} \n";
						}
					}
				}
			}
			
			$active['NAMES'] = preg_replace( "/".preg_quote($this->sep_char)."$/", "", trim($active['NAMES']) );
			
			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			$this->users_online = $active['TOTAL'];
			
			//-----------------------------------------
			// Show a link?
			//-----------------------------------------
			
			if ($this->ipsclass->vars['allow_online_list'])
			{
				$active['links'] = $this->ipsclass->compiled_templates['skin_boards']->active_user_links();
			}
			
			$this->ipsclass->lang['active_users'] = sprintf( $this->ipsclass->lang['active_users'], $this->ipsclass->vars['au_cutoff'] );
			
			return $this->ipsclass->compiled_templates['skin_boards']->ActiveUsers($active, $this->ipsclass->vars['au_cutoff']);
		}
		
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// SHOW CALENDAR EVENTS
	//
	/*-------------------------------------------------------------------------*/
	
	function show_calendar_events()
	{
		$stats_html = "";
		
		if ($this->ipsclass->vars['show_birthdays'] or $this->ipsclass->vars['show_calendar'] )
		{
			$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->ipsclass->get_time_offset() ) );
		
			$day   = $a[2];
			$month = $a[1];
			$year  = $a[0];
			
			$birthstring = "";
			$count       = 0;
			$users       = array();
			
			if ( $this->ipsclass->vars['show_birthdays'] )
			{
				if ( is_array($this->ipsclass->cache['birthdays']) AND count( $this->ipsclass->cache['birthdays'] ) )
				{
					foreach( $this->ipsclass->cache['birthdays'] as $u )
					{
						if ( $u['bday_day'] == $day and $u['bday_month'] == $month )
						{
							$users[] = $u;
						}
						else if( $day == 28 && $month == 2 && !date("L") )
						{
							if ( $u['bday_day'] == "29" and $u['bday_month'] == $month )
							{
								$users[] = $u;
							}
						}
					}
				}
				
				//-----------------------------------------
				// Spin and print...
				//-----------------------------------------
				
				foreach ( $users as $user )
				{
					$birthstring .= "<a href='{$this->ipsclass->base_url}showuser={$user['id']}'>{$user['members_display_name']}</a>";
					
					if ($user['bday_year'])
					{
						$pyear = $year - $user['bday_year'];
						$birthstring .= "(<b>$pyear</b>)";
					}
					
					$birthstring .= $this->sep_char."\n";
					
					$count++;
				}
				
				//-----------------------------------------
				// Fix up string...
				//-----------------------------------------
				
				$birthstring = preg_replace( "/".$this->sep_char."$/", "", trim($birthstring) );
				
				$lang = $this->ipsclass->lang['no_birth_users'];
				
				if ($count > 0)
				{
					$lang = ($count > 1) ? $this->ipsclass->lang['birth_users'] : $this->ipsclass->lang['birth_user'];
					$stats_html .= $this->ipsclass->compiled_templates['skin_boards']->birthdays( $birthstring, $count, $lang  );
				}
				else
				{
					$count = "";
					
					if ( ! $this->ipsclass->vars['autohide_bday'] )
					{
						$stats_html .= $this->ipsclass->compiled_templates['skin_boards']->birthdays( $birthstring, $count, $lang  );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Are we viewing the calendar?
		//-----------------------------------------
		
		if ($this->ipsclass->vars['show_calendar'])
		{
			$this->ipsclass->vars['calendar_limit']  = intval($this->ipsclass->vars['calendar_limit']) < 2 ? 1 : intval($this->ipsclass->vars['calendar_limit']);
			
			$our_unix    = gmmktime( 0, 0, 0, $month, $day, $year);
			$max_date    = $our_unix + ($this->ipsclass->vars['calendar_limit'] * 86400);
			$events      = array();
			$show_events = array();
			
			if( $this->ipsclass->member['org_perm_id'] )
			{
				$member_permission_groups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['org_perm_id'] ) );
			}
			else
			{
				$member_permission_groups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['g_perm_id'] ) );
				
				if( isset($this->ipsclass->member['mgroup_others']) AND $this->ipsclass->member['mgroup_others'] )
				{
					$this->ipsclass->member['mgroup_others'] = $this->ipsclass->clean_perm_string($this->ipsclass->member['mgroup_others']);
					
					$mgroup_others = explode( ",", $this->ipsclass->member['mgroup_others'] );
					
					if( count($mgroup_others) )
					{
						foreach( $mgroup_others as $mgroup )
						{
							if( $mgroup )
							{
								$member_permission_groups = array_merge( $member_permission_groups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->cache['group_cache'][$mgroup]['g_perm_id'] ) ) );
							}
						}
					}
				}
			}
			
			if ( is_array($this->ipsclass->cache['calendar']) AND count( $this->ipsclass->cache['calendar'] ) )
			{
				foreach( $this->ipsclass->cache['calendar'] as $u )
				{
					$set_offset = 0;

					if( $u['event_timeset'] && !($u['event_recurring'] == 0 AND $u['event_unix_to']) )
					{
						$set_offset = isset($this->ipsclass->member['time_offset']) ? $this->ipsclass->member['time_offset'] * 3600 : 0;
					}
					
					$u['_unix_from'] = $u['event_unix_from'] - $set_offset;
					$u['_unix_to']   = $u['event_unix_to'] - $set_offset;
					
					//-----------------------------------------
					// Private?
					//-----------------------------------------
					
					if ( $u['event_private'] == 1 and $this->ipsclass->member['id'] != $u['event_member_id'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Got perms?
					//-----------------------------------------
					
					if ( $u['event_perms'] != "*" )
					{
						$event_perms = explode( ",", $this->ipsclass->clean_perm_string( $u['event_perms'] ) );
						
						$check = 0;
						
						if( count($event_perms) )
						{
							foreach( $event_perms as $mgroup_perm )
							{
								if( in_array( $mgroup_perm, $member_permission_groups ) )
								{
									$check = 1;
								}
							}
						}
						
						if( !$check )
						{
							continue;
						}
					}
						
					//-----------------------------------------
					// Got calendar perms?
					//-----------------------------------------
					
					if ( $u['_perm_read'] != "*" )
					{
						$read_perms = explode( ",", $this->ipsclass->clean_perm_string( $u['_perm_read'] ) );
						
						$check = 0;
						
						if( count($read_perms) )
						{
							foreach( $read_perms as $mgroup_perm )
							{
								if( in_array( $mgroup_perm, $member_permission_groups ) )
								{
									$check = 1;
								}
							}
						}
						
						if( !$check )
						{
							continue;
						}
					}
					
					//-----------------------------------------
					// In range?
					//-----------------------------------------
				
					if ( $u['event_recurring'] == 0 AND ( ( $u['event_unix_to'] >= $our_unix AND $u['event_unix_from'] <= $max_date )
						OR ( $u['event_unix_to'] == 0 AND $u['event_unix_from'] >= $our_unix AND $u['event_unix_from'] <= $max_date ) ) )
					{
						$u['event_activetime'] = $u['_unix_from'];
						$events[ str_pad( $u['event_unix_from'].$u['event_id'], 15, "0" ) ] = $u;
					}
					elseif( $u['event_recurring'] > 0 )
					{
						$cust_range_s = $u['event_unix_from'];

						while( $cust_range_s < $u['event_unix_to'])
						{
							if( $cust_range_s >= $our_unix AND $cust_range_s <= $max_date )
							{
								$u['event_activetime'] = $cust_range_s;
								$events[ str_pad( $cust_range_s.$u['event_id'], 15, "0" ) ] = $u;
							}

							if( $u['event_recurring'] == "1" )
							{
								$cust_range_s += 604800;
							}
							elseif ( $u['event_recurring'] == "2" )
							{
								$cust_range_s += 18144000;
							}
							else
							{
								$cust_range_s += 31536000;
							}
						}								
					}
				}
			}
			
			//-----------------------------------------
			// Print...
			//-----------------------------------------
			
			ksort($events);
			
			foreach( $events as $event )
			{
				//-----------------------------------------
				// Recurring?
				//-----------------------------------------

				$c_time = '';
				$c_time = gmdate( 'j-F-y', $event['event_activetime'] );
				
				$show_events[] = "<a href='{$this->ipsclass->base_url}act=calendar&amp;code=showevent&amp;calendar_id={$event['event_calendar_id']}&amp;event_id={$event['event_id']}' title='$c_time'>".$event['event_title']."</a>";
			}
			
			$this->ipsclass->lang['calender_f_title'] = sprintf( $this->ipsclass->lang['calender_f_title'], $this->ipsclass->vars['calendar_limit'] );
			
			if ( count($show_events) > 0 )
			{
				$event_string = implode( $this->sep_char.' ', $show_events );
				$stats_html .= $this->ipsclass->compiled_templates['skin_boards']->calendar_events( $event_string  );
			}
			else
			{
				if ( ! $this->ipsclass->vars['autohide_calendar'] )
				{
					$event_string = $this->ipsclass->lang['no_calendar_events'];
					$stats_html .= $this->ipsclass->compiled_templates['skin_boards']->calendar_events( $event_string  );
				}
			}
		}
	
		return $stats_html;
	
	}

	/*-------------------------------------------------------------------------*/
	//
	// SHOW TOTALS
	//
	/*-------------------------------------------------------------------------*/
	
	function show_totals()
	{
		$stats_html = "";
		
		if ($this->ipsclass->vars['show_totals'])
		{
			if ( ! is_array( $this->ipsclass->cache['stats'] ) )
			{
				$this->ipsclass->cache['stats'] = array();
				
				$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
			}
			
			$stats =& $this->ipsclass->cache['stats'];
			
			//-----------------------------------------
			// Update the most active count if needed
			//-----------------------------------------
			
			if ($this->users_online > $stats['most_count'])
			{
				$stats['most_count'] = $this->users_online;
				$stats['most_date']  = time();
				
				$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
					  
			}
			
			$most_time = $this->ipsclass->get_date( $stats['most_date'], 'LONG' );
			
			$this->ipsclass->lang['most_online'] = str_replace( "<#NUM#>" ,   $this->ipsclass->do_number_format($stats['most_count'])  , $this->ipsclass->lang['most_online'] );
			$this->ipsclass->lang['most_online'] = str_replace( "<#DATE#>",                   $most_time                    , $this->ipsclass->lang['most_online'] );
			
			$total_posts = $stats['total_replies'] + $stats['total_topics'];
			
			$total_posts        = $this->ipsclass->do_number_format($total_posts);
			$stats['mem_count'] = $this->ipsclass->do_number_format($stats['mem_count']);
			
			$this->total_posts    = $total_posts;
			$this->total_members  = $stats['mem_count'];
			
			$link = $this->ipsclass->base_url."showuser=".$stats['last_mem_id'];
			
			$this->ipsclass->lang['total_word_string'] = str_replace( "<#posts#>" , "$total_posts"          , $this->ipsclass->lang['total_word_string'] );
			$this->ipsclass->lang['total_word_string'] = str_replace( "<#reg#>"   , $stats['mem_count']     , $this->ipsclass->lang['total_word_string'] );
			$this->ipsclass->lang['total_word_string'] = str_replace( "<#mem#>"   , $stats['last_mem_name'] , $this->ipsclass->lang['total_word_string'] );
			$this->ipsclass->lang['total_word_string'] = str_replace( "<#link#>"  , $link                   , $this->ipsclass->lang['total_word_string'] );
			
			$stats_html .= $this->ipsclass->compiled_templates['skin_boards']->ShowStats($this->ipsclass->lang['total_word_string']);
			
		}

		return $stats_html;
		
	}



}




?>