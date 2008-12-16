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
|   > $Date: 2007-06-29 15:38:02 -0400 (Fri, 29 Jun 2007) $
|   > $Revision: 1078 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > PORTAL PLUG IN MODULE: CALENDAR
|   > Module written by Matt Mecham
|   > Date started: Tuesday 2nd August 2005 (15:52)
+--------------------------------------------------------------------------
*/

/**
* Portal Plug In Module
*
* Portal Calendar functions
*
* @package		InvisionPowerBoard
* @subpackage	PortalPlugIn
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
* Portal Plug In Module
*
* Portal Blog functions
* Each class name MUST be in the format of:
* ppi_{file_name_minus_dot_php}
*
* @package		InvisionPowerBoard
* @subpackage	PortalPlugIn
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_calendar
{
	/**
	* IPS Global object
	*
	* @var string
	*/
	var $ipsclass;

	/**
	* Array of portal objects including:
	* good_forum, bad_forum
	*
	* @var array
	*/
	var $portal_object = array();
	
	/*-------------------------------------------------------------------------*/
 	// INIT
	/*-------------------------------------------------------------------------*/
 	/**
	* This function must be available always
	* Add any set up here, such as loading language and skins, etc
	*
	*/
 	function init()
 	{
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// MAIN FUNCTION
	/*-------------------------------------------------------------------------*/
	/**
	* Main function
	*
	* @return VOID
	*/
	function calendar_show_current_month()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		//-----------------------------------------
		// Grab calendar class
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/calendar.php' );
		$calendar           =  new calendar();
		$calendar->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
        // Load lang and templs
        //-----------------------------------------
        
        $this->ipsclass->load_language('lang_calendar');
        $this->ipsclass->load_template('skin_calendar');
		
 		//-----------------------------------------
 		// DO some set up
 		//-----------------------------------------
 		
 		$calendar->calendar_id = 1; // CHANGE TO DEFAULT?
 		
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
					$calendar->calendar = array_merge( $cal, $perms);
					$selected       = " selected='selected'";
				}
				
				$calendar->calendar_cache[ $cal['cal_id'] ] = array_merge( $cal, $perms);
			}
		}
		
		if( ! $calendar->calendar )
		{
			if( count( $calendar->calendar_cache ) )
			{
				$tmp_resort = $calendar->calendar_cache;
				ksort($tmp_resort);
				reset($tmp_resort);
				$default_calid = key( $tmp_resort );
				$calendar->calendar_id = $default_calid;
				$calendar->calendar = $tmp_resort[ $default_calid ];
				unset( $tmp_resort );
			}
		}
 		
		if( !is_array($calendar->calendar) OR !count($calendar->calendar) )
		{
			return'';
		}
		
 		$calendar->calendar = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																				'from'   => 'cal_calendars',
																				'where'  => 'cal_id='.$calendar->calendar_id ) );
																				
		$calendar->calendar = array_merge( unserialize( $calendar->calendar['cal_permissions'] ), $calendar->calendar );
		
		if ( $this->ipsclass->check_perms($calendar->calendar['perm_read']) != TRUE )
		{
 			return '';
 		}
 		
 		//-----------------------------------------
        // Finally, build up the lang arrays
        //-----------------------------------------
        
        $calendar->month_words = array( $this->ipsclass->lang['M_1'] , $this->ipsclass->lang['M_2'] , $this->ipsclass->lang['M_3'] ,
										$this->ipsclass->lang['M_4'] , $this->ipsclass->lang['M_5'] , $this->ipsclass->lang['M_6'] ,
										$this->ipsclass->lang['M_7'] , $this->ipsclass->lang['M_8'] , $this->ipsclass->lang['M_9'] ,
										$this->ipsclass->lang['M_10'], $this->ipsclass->lang['M_11'], $this->ipsclass->lang['M_12'] );
        							
		if( !$this->ipsclass->vars['ipb_calendar_mon'] )
		{
        	$calendar->day_words   = array( $this->ipsclass->lang['D_0'], $this->ipsclass->lang['D_1'], $this->ipsclass->lang['D_2'],
        								$this->ipsclass->lang['D_3'], $this->ipsclass->lang['D_4'], $this->ipsclass->lang['D_5'],
        								$this->ipsclass->lang['D_6'] );
    	}
    	else
    	{
        	$calendar->day_words   = array( $this->ipsclass->lang['D_1'], $this->ipsclass->lang['D_2'], $this->ipsclass->lang['D_3'],
        								$this->ipsclass->lang['D_4'], $this->ipsclass->lang['D_5'], $this->ipsclass->lang['D_6'],
        								$this->ipsclass->lang['D_0'] );
		}
 		
 		//-----------------------------------------
 		// What now?
 		//-----------------------------------------
 		
 		$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->ipsclass->get_time_offset() ) );
		
		$now_date = array(
						  'year'    => $a[0],
						  'mon'     => $a[1],
						  'mday'    => $a[2],
						  'hours'   => $a[3],
						  'minutes' => $a[4],
						  'seconds' => $a[5]
						);
							   
 		$content = $calendar->get_mini_calendar( $now_date['mon'], $now_date['year'] );
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_calendar_wrap( $content );
  	}
  	
  	
  	
  	
  	
  	

}

?>