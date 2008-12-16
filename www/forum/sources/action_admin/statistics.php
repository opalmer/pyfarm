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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Statistical functions
|   > Module written by Matt Mecham
|   > Date started: 4th July 2002
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

class ad_statistics
{
	var $base_url;
	var $month_names = array();
	
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
	var $perm_child = "stats";
	
	
	function auto_run()
	{
		//-----------------------------------------
		
		$this->month_names = array( 1 => 'January', 'February', 'March'     , 'April'  , 'May'     , 'June',
										 'July'   , 'August'  , 'September' , 'October', 'November', 'December'
								  );

		switch($this->ipsclass->input['code'])
		{
			case 'show_reg':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->result_screen('reg');
				break;
				
			case 'show_topic':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->result_screen('topic');
				break;
					
			case 'topic':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->main_screen('topic');
				break;
			
			//-----------------------------------------
			
			case 'show_post':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->result_screen('post');
				break;
					
			case 'post':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->main_screen('post');
				break;
			
			//-----------------------------------------
			
			case 'show_msg':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->result_screen('msg');
				break;
					
			case 'msg':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->main_screen('msg');
				break;
				
				//-----------------------------------------
			
			case 'show_views':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->show_views();
				break;
					
			case 'views':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->main_screen('views');
				break;
			
			//-----------------------------------------
			
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':show' );
				$this->main_screen('reg');
				break;
		}
		
	}
	

	//-----------------------------------------
	//| Results screen
	//-----------------------------------------
	
	function show_views()
	{
		$this->ipsclass->admin->page_title = "Statistic Center Results";
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Statistics Overview' );
		$this->ipsclass->admin->nav[] = array( '', 'Topic Views' );
		$this->ipsclass->admin->page_detail = "Showing topic view statistics";
		
		//-----------------------------------------
		
		if ( ! checkdate($this->ipsclass->input['to_month']   ,$this->ipsclass->input['to_day']   ,$this->ipsclass->input['to_year']) )
		{
			$this->ipsclass->admin->error("The 'Date To:' time is incorrect, please check the input and try again");
		}
		
		if ( ! checkdate($this->ipsclass->input['from_month'] ,$this->ipsclass->input['from_day'] ,$this->ipsclass->input['from_year']) )
		{
			$this->ipsclass->admin->error("The 'Date From:' time is incorrect, please check the input and try again");
		}
		
		//-----------------------------------------
		
		$to_time   = mktime(12 ,0 ,0 ,$this->ipsclass->input['to_month']   ,$this->ipsclass->input['to_day']   ,$this->ipsclass->input['to_year']  );
		$from_time = mktime(12 ,0 ,0 ,$this->ipsclass->input['from_month'] ,$this->ipsclass->input['from_day'] ,$this->ipsclass->input['from_year']);
		
		
		$human_to_date   = getdate($to_time);
		$human_from_date = getdate($from_time);
		
		$this->ipsclass->DB->cache_add_query( 'statistics_show_views', array( 'from_time' => $from_time, 'to_time' => $to_time, 'sortby' => $this->ipsclass->input['sortby'] ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$running_total = 0;
		$max_result    = 0;
		
		$results       = array();
		
		$this->ipsclass->adskin->td_header[] = array( "Forum"   , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "Result"  , "50%" );
		$this->ipsclass->adskin->td_header[] = array( "Views"   , "10%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Topic Views"
										    ." ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} to"
										    ." {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})"
										  );
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
		
			while ($row = $this->ipsclass->DB->fetch_row() )
			{
			
				if ( $row['result_count'] >  $max_result )
				{
					$max_result = $row['result_count'];
				}
				
				$running_total += $row['result_count'];
			
				$results[] = array(
									 'result_name'     => $row['result_name'],
									 'result_count'    => $row['result_count'],
								  );
								  
			}
			
			foreach( $results as $data )
			{
			
    			$img_width = intval( ($data['result_count'] / $max_result) * 100 - 8);
    			
    			if ($img_width < 1)
    			{
    				$img_width = 1;
    			}
    			
    			$img_width .= '%';
    			
    			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $data['result_name'],
    													  "<img src='{$this->ipsclass->skin_acp_url}/images/bar_left.gif' border='0' width='4' height='11' align='middle' alt=''><img src='{$this->ipsclass->skin_acp_url}/images/bar.gif' border='0' width='$img_width' height='11' align='middle' alt=''><img src='{$this->ipsclass->skin_acp_url}/images/bar_right.gif' border='0' width='4' height='11' align='middle' alt=''>",
												  		  "<center>".$data['result_count']."</center>",
									             )      );
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( '&nbsp;',
													 "<div align='right'><b>Total</b></div>",
													 "<center><b>".$running_total."</b></center>",
											)      );
		
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No results found", "center" );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
		
	}
	
	//-----------------------------------------
	//| Results screen
	//-----------------------------------------
	
	function result_screen($mode='reg')
	{
		$this->ipsclass->admin->page_title = "Statistic Center Results";
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Statistics Overview' );
		$this->ipsclass->admin->page_detail = "&nbsp;";
		
		//-----------------------------------------
		
		if ( ! checkdate($this->ipsclass->input['to_month']   ,$this->ipsclass->input['to_day']   ,$this->ipsclass->input['to_year']) )
		{
			$this->ipsclass->admin->error("The 'Date To:' time is incorrect, please check the input and try again");
		}
		
		if ( ! checkdate($this->ipsclass->input['from_month'] ,$this->ipsclass->input['from_day'] ,$this->ipsclass->input['from_year']) )
		{
			$this->ipsclass->admin->error("The 'Date From:' time is incorrect, please check the input and try again");
		}
		
		//-----------------------------------------
		
		$to_time   = mktime(0 ,0 ,0 ,$this->ipsclass->input['to_month']   ,$this->ipsclass->input['to_day']   ,$this->ipsclass->input['to_year']  );
		$from_time = mktime(0 ,0 ,0 ,$this->ipsclass->input['from_month'] ,$this->ipsclass->input['from_day'] ,$this->ipsclass->input['from_year']);
		
		
		$human_to_date   = getdate($to_time);
		$human_from_date = getdate($from_time);
		
		//-----------------------------------------
		
		if ($mode == 'reg')
		{
			$table     = 'Registration Statistics';
			
			$sql_table = 'members';
			$sql_field = 'joined';
			
			$this->ipsclass->admin->page_detail = "Showing the number of users registered. (Note: All times based on GMT)";
			$this->ipsclass->admin->nav[] = array( '', 'Registered Users' );
		}
		else if ($mode == 'topic')
		{
			$table     = 'New Topic Statistics';
			
			$sql_table = 'topics';
			$sql_field = 'start_date';
			
			$this->ipsclass->admin->page_detail = "Showing the number of topics started. (Note: All times based on GMT)";
			$this->ipsclass->admin->nav[] = array( '', 'Topics Started' );
		}
		else if ($mode == 'post')
		{
			$table     = 'Post Statistics';
			
			$sql_table = 'posts';
			$sql_field = 'post_date';
			
			$this->ipsclass->admin->page_detail = "Showing the number of posts. (Note: All times based on GMT)";
			$this->ipsclass->admin->nav[] = array( '', 'Number of Posts' );
		}
		else if ($mode == 'msg')
		{
			$table     = 'PM Sent Statistics';
			
			$sql_table = 'message_topics';
			$sql_field = 'mt_date';
			
			$this->ipsclass->admin->page_detail = "Showing the number of sent messages. (Note: All times based on GMT)";
			$this->ipsclass->admin->nav[] = array( '', 'Sent Private Messages' );
		}
		
	  
	  	switch ($this->ipsclass->input['timescale'])
	  	{
	  		case 'daily':
	  			$sql_date = "%w %U %m %Y";
		  		$php_date = "F jS - Y";
		  		break;
		  		
		  	case 'monthly':
		  		$sql_date = "%m %Y";
		  	    $php_date = "F Y";
		  	    break;
		  	    
		  	default:
		  		// weekly
		  		$sql_date = "%U %Y";
		  		$php_date = " [F Y]";
		  		break;
		}
		
		$this->ipsclass->DB->cache_add_query( 'statistics_result_screen', array( 'from_time' => $from_time,
																 'to_time'   => $to_time,
																 'sortby'    => $this->ipsclass->input['sortby'],
																 'sql_field' => $sql_field,
																 'sql_table' => $sql_table,
																 'sql_date'  => $sql_date ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$running_total = 0;
		$max_result    = 0;
		
		$results       = array();
		
		$this->ipsclass->adskin->td_header[] = array( "Date"    , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Result"  , "70%" );
		$this->ipsclass->adskin->td_header[] = array( "Count"   , "10%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( ucfirst($this->ipsclass->input['timescale'])
										    ." ".$table
										    ." ({$human_from_date['mday']} {$this->month_names[$human_from_date['mon']]} {$human_from_date['year']} to"
										    ." {$human_to_date['mday']} {$this->month_names[$human_to_date['mon']]} {$human_to_date['year']})"
										  );
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
		
			while ($row = $this->ipsclass->DB->fetch_row() )
			{
			
				if ( $row['result_count'] >  $max_result )
				{
					$max_result = $row['result_count'];
				}
				
				$running_total += $row['result_count'];
			
				$results[] = array(
									 'result_maxdate'  => $row['result_maxdate'],
									 'result_count'    => $row['result_count'],
									 'result_time'     => $row['result_time'],
								  );
								  
			}
			
			foreach( $results as $data )
			{
			
    			$img_width = intval( ($data['result_count'] / $max_result) * 100 - 8);
    			
    			if ($img_width < 1)
    			{
    				$img_width = 1;
    			}
    			
    			$img_width .= '%';
    			
    			if ($this->ipsclass->input['timescale'] == 'weekly')
    			{
    				$date = "Week #".strftime("%W", $data['result_maxdate']) . date( $php_date, $data['result_maxdate'] );
    			}
    			else
    			{
    				$date = date( $php_date, $data['result_maxdate'] );
    			}
    			
    			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $date,
    													  "<img src='{$this->ipsclass->skin_acp_url}/images/bar_left.gif' border='0' width='4' height='11' align='middle' alt=''><img src='{$this->ipsclass->skin_acp_url}/images/bar.gif' border='0' width='$img_width' height='11' align='middle' alt=''><img src='{$this->ipsclass->skin_acp_url}/images/bar_right.gif' border='0' width='4' height='11' align='middle' alt=''>",
												  		  "<center>".$data['result_count']."</center>",
									             )      );
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( '&nbsp;',
													 "<div align='right'><b>Total</b></div>",
													 "<center><b>".$running_total."</b></center>",
											)      );
		
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No results found", "center" );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
		
	}
	
	//-----------------------------------------
	//| Date selection screen
	//-----------------------------------------
	
	function main_screen($mode='reg')
	{
		$this->ipsclass->admin->page_title = "Statistic Center";
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Statistics Overview' );
		$this->ipsclass->admin->page_detail = "Please define the date ranges and other options below.<br>Note: The statistics generated are based on the information currently held in the database, they do not take into account pruned forums or delete posts, etc.";
		
		if ($mode == 'reg')
		{
			$form_code = 'show_reg';
			
			$table     = 'Registration Statistics';
		}
		else if ($mode == 'topic')
		{
			$form_code = 'show_topic';
			
			$table     = 'New Topic Statistics';
		}
		else if ($mode == 'post')
		{
			$form_code = 'show_post';
			
			$table     = 'Post Statistics';
		}
		else if ($mode == 'msg')
		{
			$form_code = 'show_msg';
			
			$table     = 'PM Statistics';
		}
		else if ($mode == 'views')
		{
			$form_code = 'show_views';
			
			$table     = 'Topic View Statistics';
		}
		
		
		$old_date = getdate(time() - (3600 * 24 * 90));
		$new_date = getdate(time() + (3600 * 24));
		
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $form_code  ),
												  2 => array( 'act'   , 'stats'     ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )      );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $table );
		
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Date From</b>" ,
												  $this->ipsclass->adskin->form_dropdown( "from_month" , $this->make_month(), $old_date['mon']  ).'&nbsp;&nbsp;'.
												  $this->ipsclass->adskin->form_dropdown( "from_day"   , $this->make_day()  , $old_date['mday'] ).'&nbsp;&nbsp;'.
												  $this->ipsclass->adskin->form_dropdown( "from_year"  , $this->make_year() , $old_date['year'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Date To</b>" ,
												  $this->ipsclass->adskin->form_dropdown( "to_month" , $this->make_month(), $new_date['mon']  ).'&nbsp;&nbsp;'.
												  $this->ipsclass->adskin->form_dropdown( "to_day"   , $this->make_day()  , $new_date['mday'] ).'&nbsp;&nbsp;'.
												  $this->ipsclass->adskin->form_dropdown( "to_year"  , $this->make_year() , $new_date['year'] )
									     )      );
		
		if ($mode != 'views')
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Time scale</b>" ,
													  $this->ipsclass->adskin->form_dropdown( "timescale" , array( 0 => array( 'daily', 'Daily'), 1 => array( 'weekly', 'Weekly' ), 2 => array( 'monthly', 'Monthly' ) ) )
											 )      );
		}
						     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Result Sorting</b>" ,
												  $this->ipsclass->adskin->form_dropdown( "sortby" , array( 0 => array( 'asc', 'Ascending - Oldest dates first'), 1 => array( 'desc', 'Descending - Newest dates first' ) ), 'desc' )
									     )      );
									     									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Show");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
		
		
	}
	
	//-----------------------------------------
	
	function make_year()
	{
		$time_now = getdate();
		
		$return = array();
		
		$start_year = 2002;
		
		$latest_year = intval($time_now['year']);
		
		if ($latest_year == $start_year)
		{
			$start_year -= 1;
		}
		
		for ( $y = $start_year; $y <= $latest_year; $y++ )
		{
			$return[] = array( $y, $y);
		}
		
		return $return;
	}
	
	//-----------------------------------------
	
	function make_month()
	{
		$return = array();
		
		for ( $m = 1 ; $m <= 12; $m++ )
		{
			$return[] = array( $m, $this->month_names[$m] );
		}
		
		return $return;
	}
	
	//-----------------------------------------
	
	function make_day()
	{
		$return = array();
		
		for ( $d = 1 ; $d <= 31; $d++ )
		{
			$return[] = array( $d, $d );
		}
		
		return $return;
	}
	
	
		
}


?>