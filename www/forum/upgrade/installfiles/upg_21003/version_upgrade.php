<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.0.2 -> IPB 2.0.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	var $install;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function version_upgrade( & $install )
	{
		$this->install = & $install;
	}
	
	/*-------------------------------------------------------------------------*/
	// Auto run..
	/*-------------------------------------------------------------------------*/

	function auto_run()
	{
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->install->saved_data['workact'] )
		{
			case 'sql':
				$this->upgrade_sql(1);
				break;
			case 'sql1':
				$this->upgrade_sql(1);
				break;
			case 'sql2':
				$this->upgrade_sql(2);
				break;
			case 'sql3':
				$this->upgrade_sql(3);
				break;
			case 'sql4':
				$this->upgrade_sql(4);
				break;
			case 'polls':
				$this->convert_polls();
				break;
			case 'calevents':
				$this->convert_calevents();
				break;
			case 'skin':
				$this->add_skin();
				break;				
			
			default:
				$this->upgrade_sql(1);
				break;
		}
		
		if ( $this->install->saved_data['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// SQL: 0
	/*-------------------------------------------------------------------------*/
	
	function upgrade_sql( $id=1 )
	{
		$man     = 0; // Manual upgrade ? intval( $this->install->ipsclass->input['man'] );
		$cnt     = 0;
		$SQL     = array();
		$file    = '_updates_'.$id.'.php';
		$output  = "";
		
		if ( file_exists( ROOT_PATH . 'upgrade/installfiles/upg_21003/' . strtolower($this->install->ipsclass->vars['sql_driver']) . $file ) )
		{
			require_once( ROOT_PATH . 'upgrade/installfiles/upg_21003/' . strtolower($this->install->ipsclass->vars['sql_driver']) . $file );
		
			$this->install->error   = array();
			$this->sqlcount 		= 0;
			$output					= "";
			
			$this->install->ipsclass->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->install->ipsclass->DB->allow_sub_select 	= 1;
				$this->install->ipsclass->DB->error				= '';
				
				$query = str_replace( "<%time%>", time(), $query );
							
				if( $this->install->saved_data['man'] AND !in_array( $id, array(3,4) ) )
				{
					$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->install->ipsclass->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
				}
				else
				{			
					$this->install->ipsclass->DB->query( $query );
					
					if ( $this->install->ipsclass->DB->error )
					{
						$this->install->error[] = $query."<br /><br />".$this->install->ipsclass->DB->error;
					}
					else
					{
						$this->sqlcount++;
					}
				}
			}
		
			$this->install->message = "$this->sqlcount queries run....";
		}
		
		//--------------------------------
		// Next page...
		//--------------------------------
		
		$this->install->saved_data['st'] = 0;
		
		if ( $id != 4 )
		{
			$nextid = $id + 1;
			$this->install->saved_data['workact'] = 'sql'.$nextid;	
		}
		else
		{
			$this->install->saved_data['workact'] = 'polls';	
		}
		
		if( $this->install->saved_data['man'] AND $output )
		{
			$this->install->message .= "<br /><br /><h3><b>Please run these queries in your MySQL database before continuing..</b></h3><br />".nl2br(htmlspecialchars($output));
			$this->install->do_man	 = 1;
		}		
	}	
	
	
	/*-------------------------------------------------------------------------*/
	// POLLS
	/*-------------------------------------------------------------------------*/
	
	function convert_polls()
	{
		$start     = intval($this->install->saved_data['st']) > 0 ? intval($this->install->saved_data['st']) : 0;
		$lend      = 50;
		$end       = $start + $lend;
		$max       = intval($this->install->saved_data['max']);
		$converted = intval($this->install->saved_data['conv']);
		
		//-----------------------------------------
		// First off.. grab number of polls to convert
		//-----------------------------------------
		
		if ( ! $max )
		{
			$total = $this->install->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as max',
																	   'from'   => 'topics',
																	   'where'  => "poll_state IN ('open', 'close', 'closed')" ) );
																	   
			$max   = $total['max'];
		}
		
		//-----------------------------------------
		// In steps...
		//-----------------------------------------
		
		$this->install->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'topics',
													  'where'  => "poll_state IN ('open', 'close', 'closed' )",
													  'limit'  => array( 0, $lend ) ) );
		$o = $this->install->ipsclass->DB->simple_exec();
	
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		if ( $this->install->ipsclass->DB->get_num_rows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------
			
			while ( $r = $this->install->ipsclass->DB->fetch_row($o) )
			{
				$converted++;
				
				//-----------------------------------------
				// All done?
				//-----------------------------------------
				
				if ( $converted >= $max )
				{
					$done = 1;
				}				
				
				$new_poll  = array( 1 => array() );
				
				$poll_data = $this->install->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																			   'from'   => 'polls',
																			   'where'  => "tid=".$r['tid']
																	  )      );
				if ( ! $poll_data['pid'] )
				{
					continue;
				}
				
				if ( ! $poll_data['poll_question'] )
				{
					$poll_data['poll_question'] = $r['title'];
				}
				
				//-----------------------------------------
				// Kick start new poll
				//-----------------------------------------
				
				$new_poll[1]['question'] = $poll_data['poll_question'];
        
				//-----------------------------------------
				// Get OLD polls
				//-----------------------------------------
				
				$poll_answers = unserialize( stripslashes( $poll_data['choices'] ) );
        	
				reset($poll_answers);
				
				foreach ( $poll_answers as $entry )
				{
					$id     = $entry[0];
					$choice = $entry[1];
					$votes  = $entry[2];
					
					$total_votes += $votes;
					
					if ( strlen($choice) < 1 )
					{
						continue;
					}
					
					$new_poll[ 1 ]['choice'][ $id ] = $choice;
					$new_poll[ 1 ]['votes'][ $id  ] = $votes;
				}
				
				//-----------------------------------------
				// Got something?
				//-----------------------------------------
				
				if ( count( $new_poll[1]['choice'] ) )
				{
					$this->install->ipsclass->DB->do_update( 'polls' , array( 'choices'    => serialize( $new_poll ) ), 'tid='.$r['tid'] );
					$this->install->ipsclass->DB->do_update( 'topics', array( 'poll_state' => 1 ), 'tid='.$r['tid'] );
				}
			}
		}
		else
		{
			$done = 1;
		}
		
		
		if ( ! $done )
		{
			$this->install->message = "Polls: $start to $end completed....";
			$this->install->saved_data['workact'] 	= 'polls';	
			$this->install->saved_data['st'] 		= $end;
			$this->install->saved_data['max'] 		= $max;
			$this->install->saved_data['conv'] 		= $converted;
			return FALSE;			
		}
		else
		{
			$this->install->message = "Polls converted, proceeding to calendar events...";
			$this->install->saved_data['workact'] 	= 'calevents';	
			$this->install->saved_data['st'] 		= '0';	
			return FALSE;						
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// CALENDAR EVENTS
	/*-------------------------------------------------------------------------*/
	
	function convert_calevents()
	{
		$start     = intval($this->install->saved_data['st']) > 0 ? intval($this->install->saved_data['st']) : 0;
		$lend      = 50;
		$end       = $start + $lend;
	
		//-----------------------------------------
		// In steps...
		//-----------------------------------------
		
		$this->install->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'calendar_events',
													  'limit'  => array( $start, $lend ) ) );
		$o = $this->install->ipsclass->DB->simple_exec();
	
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		if ( $this->install->ipsclass->DB->get_num_rows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------
			
			while ( $r = $this->install->ipsclass->DB->fetch_row($o) )
			{
				$recur_remap = array( 'w' => 1,
									  'm' => 2,
									  'y' => 3 );
				
				$begin_date        = $this->install->ipsclass->date_getgmdate( $r['unix_stamp']     );
				$end_date          = $this->install->ipsclass->date_getgmdate( $r['end_unix_stamp'] );
				
				if ( ! $begin_date OR ! $end_date )
				{
					continue;
				}
				
				$day               = $begin_date['mday'];
				$month             = $begin_date['mon'];
				$year              = $begin_date['year'];
				
				$end_day           = $end_date['mday'];
				$end_month         = $end_date['mon'];
				$end_year          = $end_date['year'];
		
				$_final_unix_from  = gmmktime(0, 0, 0, $month, $day, $year );
				
				//-----------------------------------------
				// Recur or ranged...
				//-----------------------------------------
				
				if ( $r['event_repeat'] OR $r['event_ranged'] )
				{
					$_final_unix_to = gmmktime(11, 59, 59, $end_month, $end_day, $end_year);
				}
				else
				{
					$_final_unix_to = 0;
				}
				
				$new_event = array( 'event_calendar_id' => 1,
									'event_member_id'   => $r['userid'],
									'event_content'     => $r['event_text'],
									'event_title'       => $r['title'],
									'event_smilies'     => $r['show_emoticons'],
									'event_perms'       => $r['read_perms'],
									'event_private'     => $r['priv_event'],
									'event_approved'    => 1,
									'event_unixstamp'   => $r['unix_stamp'],
									'event_recurring'   => ( $r['event_repeat'] && $recur_remap[ $r['repeat_unit'] ] ) ? $recur_remap[ $r['repeat_unit'] ] : 0,
									'event_tz'          => 0,
									'event_unix_from'   => $_final_unix_from,
									'event_unix_to'     => $_final_unix_to );
				
				//-----------------------------------------
				// INSERT
				//-----------------------------------------
				
				$this->install->ipsclass->DB->do_insert( 'cal_events', $new_event );
			}
			
			$this->install->message = "Calendar events: $start to $end completed....";
			$this->install->saved_data['workact'] 	= 'calevents';	
			$this->install->saved_data['st'] 		= $end;
			return FALSE;		
		}
		else
		{
			$this->install->message = "Calendar events converted,  Creating new IPB 2.1 skin...";
			$this->install->saved_data['workact'] 	= 'skin';	
			return FALSE;		
		}
	}
		
		
	
	/*-------------------------------------------------------------------------*/
	// CALENDAR EVENTS
	/*-------------------------------------------------------------------------*/
	
	function add_skin()
	{
		$this->install->message = "Skipping 2.1 skin creation (latest skin will be inserted later)...";
		unset($this->install->saved_data['workact']);
		unset($this->install->saved_data['vid']);
		return TRUE;
		
		//-----------------------------------------
		// Get default wrapper
		//-----------------------------------------
		
		if ( file_exists( ROOT_PATH.'upgrade/installfiles/upg_21003/components.php' ) )
		{
			require_once( ROOT_PATH.'upgrade/installfiles/upg_21003/components.php' );
		}
		
		//-----------------------------------------
		// Turn off all other skins
		//-----------------------------------------
		
		$this->install->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 0 ) );
		
		//-----------------------------------------
		// Insert new skin...
		//-----------------------------------------
		
		$this->install->ipsclass->DB->return_die = 1;
		$this->install->ipsclass->DB->allow_sub_select 	= 1;
		
		$this->install->ipsclass->DB->do_insert( 'skin_sets', array(
															'set_name'            => 'IPB 2.1 Default',
															'set_image_dir'       => 1,
															'set_hidden'          => 0,
															'set_default'         => 1,
															'set_css_method'      => 0,
															'set_skin_set_parent' => -1,
															'set_author_email'    => '',
															'set_author_name'     => 'IPB 2.1 Default',
															'set_author_url'      => '',
															'set_css'             => $CSS,
															'set_cache_css'       => $CSS,
															'set_wrapper'         => $WRAPPER,
															'set_cache_wrapper'   => $WRAPPER,
															'set_emoticon_folder' => 'default',
									 )                    );
		
		$new_id = $this->install->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Remove member's choice
		//-----------------------------------------
		
		$this->install->ipsclass->DB->do_update( 'members', array( 'skin' => '' ) );	
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
			
		$this->install->message = "2.1 skin created...";
		unset($this->install->saved_data['workact']);
		unset($this->install->saved_data['vid']);

		return TRUE;	
	}
	



	
}
	
	
?>