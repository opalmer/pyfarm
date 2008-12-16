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
|   > Admin Member Tool functions
|   > Module written by Matt Mecham
|   > Date started: 17th September 2003
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

$root_path = "";

class ad_member_tools
{

	var $base_url;
	var $modules = "";
	var $html;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "content";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "mtools";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Member Tools Home' );
		
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_member');
		
		//-----------------------------------------
    	// Get the sync module
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			$this->modules->ipsclass =& $this->ipsclass;
		}		
		
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Member Tool Box";
		$this->ipsclass->admin->page_detail = 'You can use the tools below to search for IP address.';

		switch($this->ipsclass->input['code'])
		{
			case 'showallips':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':view' );
				$this->show_ips();
				break;
				
			case 'learnip':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':view' );
				$this->learn_ip();
				break;
				
			//-----------------------------------------
			case 'mod':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':mod' );
				$this->member_view_moderation_queue();
				break;
			case 'domod':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':mod' );
				$this->member_do_moderation_queue();
				break;
			case 'unappemail':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':mod' );
				$this->member_do_email_unapprove();
				break;
			case 'lock':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':mod' );
				$this->member_view_locked_queue();
				break;				
			case 'unlock':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':mod' );
				$this->member_do_unlock();
				break;				
							
			
			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':view' );
				$this->show_index();
				break;
		}
		
	}
	
	
	//-----------------------------------------
	//
	// LEARN ABOUT THE IP. It's very good.
	//
	//-----------------------------------------
	
	
	function learn_ip()
	{
		if ( $this->ipsclass->input['ip'] == "" )
		{
			$this->show_index("You did not enter an IP address to search by");
		}
		
		$ip = trim($this->ipsclass->input['ip']);
		
		$resolved = 'N/A - Partial IP Address';
		$exact    = 0;
		
		if ( substr_count( $ip, '.' ) == 3 )
		{
			$exact = 1;
		}
		
		if ( strstr( $ip, '*' ) )
		{
			$exact = 0;
			$ip    = str_replace( "*", "", $ip );
		}
			
		if ( $exact != 0 )
		{
			$resolved = @gethostbyaddr($ip);
			$query    = "='".$ip."'";
		}
		else
		{
			$query    = " LIKE '".$ip."%'";
		}
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Host Address for {$this->ipsclass->input['ip']}" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>IP address resolves to</b>" ,
																 $resolved
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Find registered members
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Name"       , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Email"      , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Posts"      , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "IP"         , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Registered" , "20%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Members using that IP when REGISTERING" );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, members_display_name, email, posts, ip_address, joined',
									  'from'   => 'members',
									  'where'  => "ip_address{$query}",
									  'order'  => 'joined DESC',
									  'limit'  => array( 0,250) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $m['members_display_name'] ,
																		 $m['email'],
																		 $m['posts'],
																		 $m['ip_address'],
																		 $this->ipsclass->get_date( $m['joined'], 'SHORT' )
																)      );
			}
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Find Names posted under
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Name"       , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Email"      , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP"         , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "First Used"  , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "View Post"  , "15%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Members using that IP when POSTING" );
		
		$this->ipsclass->DB->cache_add_query( 'member_tools_learn_ip_one', array( 'query' => $query) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				$m['name'] = $m['name'] ? $m['name'] : "Guest";
				$m['email'] = $m['email'] ? $m['email'] : "<i>Not Available</i>";
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $m['name'] ,
																		 $m['email'],
																		 $m['ip_address'],
																		 $this->ipsclass->get_date( $m['post_date'], 'SHORT' ),
																		 "<center><a href='{$this->ipsclass->vars['board_url']}/index.php?showtopic={$m['topic_id']}&view=findpost&p={$m['pid']}' target='_blank'>View Post</a></center>",
																)      );
			}
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Find Names VOTED under
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Name"       , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Email"      , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP"         , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "First Used" , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "View Poll" , "15%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Members using that IP when VOTING" );
		
		$this->ipsclass->DB->cache_add_query( 'member_tools_learn_ip_two', array( 'query' => $query) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $m['name'] ,
																		 $m['email'],
																		 $m['ip_address'],
																		 $this->ipsclass->get_date( $m['vote_date'], 'SHORT' ),
																		 "<center><a href='{$this->ipsclass->vars['board_url']}/index.php?showtopic={$m['tid']}' target='_blank'>View Poll</a></center>",
																)      );
			}
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Find Names EMAILING under
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Name"       , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Email"      , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP"         , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "First Used"    , "20%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Members using that IP when EMAILING other members" );
		
		$this->ipsclass->DB->cache_add_query( 'member_tools_learn_ip_three', array( 'query' => $query) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $m['name'] ,
																		 $m['email'],
																		 $m['from_ip_address'],
																		 $this->ipsclass->get_date( $m['email_date'], 'SHORT' ),
																)      );
			}
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		//-----------------------------------------
		// Find Names VALIDATING under
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Name"       , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Email"      , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP"         , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "First Used" , "20%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Members using that IP while VALIDATING their accounts" );
		
		$this->ipsclass->DB->cache_add_query( 'member_tools_learn_ip_four', array( 'query' => $query) );
		$this->ipsclass->DB->cache_exec_query();
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No Matches Found", "center");
		}
		else
		{
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $m['name'] ,
														  $m['email'],
														  $m['ip_address'],
														  $this->ipsclass->get_date( $m['entry_date'], 'SHORT' ),
												 )      );
			}
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	//
	// SHOW ALL IPs
	//
	//-----------------------------------------
	
	
	function show_ips()
	{
		if ( $this->ipsclass->input['name'] == "" and $this->ipsclass->input['member_id'] == "" )
		{
			$this->show_index("You did not enter a name to search by");
		}
		
		if ( isset($this->ipsclass->input['member_id']) AND $this->ipsclass->input['member_id'] )
		{
			$id = intval($this->ipsclass->input['member_id']);
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, email, ip_address', 'from' => 'members', 'where' => "id=$id" ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( ! $member = $this->ipsclass->DB->fetch_row() )
			{
				$this->show_index("Could not locate a member with the id of '$id'");
			}
		}
		else
		{
			$name = addslashes($this->ipsclass->input['name']);
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, members_display_name as name, email, ip_address', 'from' => 'members', 'where' => "name='{$name}' OR members_display_name='{$name}'" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( ! $member = $this->ipsclass->DB->fetch_row() )
			{
				$this->show_index( "We could not find an exact match for that member name, some choices will be shown below", $name );
			}
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(distinct(ip_address)) as cnt', 'from' => 'posts', 'where' => "author_id={$member['id']}" ) );
		$this->ipsclass->DB->simple_exec();
			
		$count = $this->ipsclass->DB->fetch_row();
		
		$st  = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$end = 50;
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $count['cnt'],
														  'PER_PAGE'    => $end,
														  'CUR_ST_VAL'  => $st,
														  'L_SINGLE'    => "Single Page",
														  'L_MULTI'     => "Multiple Pages",
														  'BASE_URL'    => $this->ipsclass->base_url."&".$this->ipsclass->form_code."&code=showallips&member_id={$member['id']}",
												 )      );
		
		$master = array();
		$ips    = array();
		
		$this->ipsclass->DB->cache_add_query( 'member_tools_show_ips', array( 'mid' => $member['id'], 'st' => $st, 'end' => $end ) );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$master[] = $r;
			$ips[]    = "'".$r['ip_address']."'";
		}
		
		$reg = array();
		
		if ( count($ips) > 0 )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, members_display_name as name, ip_address', 'from' => 'members', 'where' => "ip_address IN (".implode(",",$ips).") AND id != {$member['id']}" ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $i = $this->ipsclass->DB->fetch_row() )
			{
				$reg[ $i['ip_address'] ][] = $i;
			}
		}
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "IP Address"          , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Times Used"          , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Date Used"           , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Used for other Reg." , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "IP Tool"             , "25%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "{$member['name']}'s IP addresses ({$count['cnt']}) matches" );
		
		foreach( $master as $r )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $r['ip_address'] ,
																	 $r['ip'] ,
																	 $this->ipsclass->get_date( $r['post_date'], 'SHORT' ),
																	 "<center>". intval( count($reg[ $r['ip_address'] ]) ). "</center>",
																	 "<center><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=learnip&ip={$r['ip_address']}'>Learn about this IP</a></center>"
															)      );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "$links", "center" );
									     							     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	//
	// Default Screen
	//
	//-----------------------------------------
	
	
	function show_index($msg="", $membername="")
	{
		if ($msg != "")
		{
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Message" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $msg ) );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		
		$this->ipsclass->html .= "<script type='text/javascript' src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
									<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:170px;display:none;z-index:100'></div>";
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'showallips'  ),
																 2 => array( 'act'   , 'mtools'     ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														)      );
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Show all IP Addresses a member has posted with" );
		
		if ( $membername == "" )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Enter the member's name</b>" ,
													  $this->ipsclass->adskin->form_input( "name", isset($_POST['name']) ? $this->ipsclass->txt_stripslashes($_POST['name']) : '', 'text', "id='name'" )
											 )      );
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name', 'from' => 'members', 'where' => "members_l_username LIKE '{$membername}%'" ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( ! $this->ipsclass->DB->get_num_rows() )
			{
				$this->show_index("There are no members with names that start with '$membername'");
			}
			
			$mem_array = array();
			
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				$mem_array[] = array( $m['id'], $m['name'] );
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Choose the member from the selection</b>" ,
													  $this->ipsclass->adskin->form_dropdown( "member_id", $mem_array )
											 )      );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Get IP Addresses");
									     							     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= "<script type='text/javascript'>
									// INIT find names
									init_js( 'theAdminForm', 'name');
									// Run main loop
									var tmp = setTimeout( 'main_loop()', 10 );
								</script>";
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'learnip'  ),
												  2 => array( 'act'   , 'mtools'     ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )      );
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "IP Multi-Tool" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Show me everything you know about this IP...</b>" ,
												   $this->ipsclass->adskin->form_input( "ip", isset($_POST['ip']) ? $this->ipsclass->txt_stripslashes($_POST['ip']) : '' )
										  )      );
										  
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Show me!");
									     							     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Process Moderation queue
	/*-------------------------------------------------------------------------*/
	
	function member_do_moderation_queue()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids = array();
		
		//-----------------------------------------
		// GET checkboxes
		//-----------------------------------------
		
		foreach ($this->ipsclass->input as $k => $v)
		{
			if ( preg_match( "/^mid_(\d+)$/", $k, $match ) )
			{
				if ($this->ipsclass->input[ $match[0] ])
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = $this->ipsclass->clean_int_array( $ids );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( count($ids) < 1 )
		{	
			$this->ipsclass->admin->error("You did not select any members to approve, delete, or resend the validation email to");
		}
		
		//-----------------------------------------
		// APPROVE
		//-----------------------------------------
		
		if ($this->ipsclass->input['type'] == 'approve')
		{
			//-----------------------------------------
			// Get email class
			//-----------------------------------------
			
			require ROOT_PATH."sources/classes/class_email.php";
			
			$email = new emailer( ROOT_PATH );
			$email->ipsclass =& $this->ipsclass;
			$email->email_init();
			
			$email->get_template("complete_reg");
			
			$approved = array();
			
			//-----------------------------------------
			// Get members
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'member_domod', array( 'ids' => $ids ) );
			$main = $this->ipsclass->DB->cache_exec_query();
			
			while( $row = $this->ipsclass->DB->fetch_row( $main ) )
			{
				$approved[] = $row['name'];

				if ($row['mgroup'] != $this->ipsclass->vars['auth_group'])
				{
					continue;
				}
				
				if ($row['real_group'] == "")
				{
					//$row['real_group'] = $this->ipsclass->vars['member_group'];
					continue;
				}
				
				$this->ipsclass->DB->do_update( 'members', array( 'mgroup' => $row['real_group'] ), "id=".$row['id'] );
				
				$email->build_message( "" );
				$email->subject = "Account: {$row['name']}, validated at ".$this->ipsclass->vars['board_name'];
				$email->to      = $row['email'];
				
				$email->send_mail();
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class($this);
					$this->modules->on_group_change($row['id'], $row['real_group']);
				}
			}
			
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'validating', 'where' => "member_id IN(".implode( ",",$ids ).")" ) );
			
			$this->ipsclass->admin->save_log( count($ids)." Member Registrations Approved: ".implode( ", ", $approved ) );
			
			//-----------------------------------------
			// Stats to Update?
			//-----------------------------------------
			
			$stats = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='stats'" ) );
			
			$stats = unserialize($this->ipsclass->txt_stripslashes($stats['cs_value']));
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'count(id) as members', 'from' => 'members', 'where' => "mgroup <> '".$this->ipsclass->vars['auth_group']."'" ) );
			$this->ipsclass->DB->simple_exec();
				
			$r = $this->ipsclass->DB->fetch_row();
			$stats['mem_count'] = intval($r['members']);

			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, members_display_name',
											  'from'   => 'members',
											  'where'  => "mgroup <> '".$this->ipsclass->vars['auth_group']."'",
											  'order'  => "id DESC",
											  'limit'  => array(0,1) ) );
			$this->ipsclass->DB->simple_exec();
				
			$r = $this->ipsclass->DB->fetch_row();
			$stats['last_mem_name'] = $r['members_display_name'] ? $r['members_display_name'] : $r['name'];
			$stats['last_mem_id']   = $r['id'];
			
			if ( count($stats) > 0 )
			{
				$this->ipsclass->cache['stats'] =& $stats;
				$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
			}			
			
			$this->ipsclass->main_msg = count($ids)." Member Registrations Approved";
			$this->member_view_moderation_queue();
		}
		
		//-----------------------------------------
		// Resend validation email
		//-----------------------------------------
		
		else if ($this->ipsclass->input['type'] == 'resend')
		{
			//-----------------------------------------
			// Get email class
			//-----------------------------------------
			
			require ROOT_PATH."sources/classes/class_email.php";
			
			$email = new emailer( ROOT_PATH );
			$email->ipsclass =& $this->ipsclass;
			$email->email_init();
			
			$reset 		= array();
			$cant		= array();
			$main_msgs	= array();
			
			//-----------------------------------------
			// Get members
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'member_domod', array( 'ids' => $ids ) );
			$main = $this->ipsclass->DB->cache_exec_query();
			
			while( $row = $this->ipsclass->DB->fetch_row( $main ) )
			{
				if ($row['mgroup'] != $this->ipsclass->vars['auth_group'])
				{
					continue;
				}
				
				if ( $row['lost_pass'] == 1 )
				{
					$email->get_template("lost_pass");
						
					$email->build_message( array(
														'NAME'         => $row['members_display_name'],
														'THE_LINK'     => $this->ipsclass->vars['board_url']."/index.php?act=Reg&CODE=lostpassform&uid=".$row['id']."&aid=".$val['vid'],
														'MAN_LINK'     => $this->ipsclass->vars['board_url']."/index.php?act=Reg&CODE=lostpassform",
														'EMAIL'        => $row['email'],
														'ID'           => $row['id'],
														'CODE'         => $row['vid'],
														'IP_ADDRESS'   => $row['ip_address'],
													  )
												);
												
					$email->subject = "Password recovery information from ".$this->ipsclass->vars['board_name'];
					$email->to      = $row['email'];
					
					$email->send_mail();
				}
				else if ( $row['new_reg'] == 1 )
				{
					if( $row['user_verified'] )
					{
						$cant[] = $row['members_display_name'];
						continue;
					}
					
					$email->get_template("reg_validate");
							
					$email->build_message( array(
														'THE_LINK'     => $this->ipsclass->vars['board_url']."/index.php?act=Reg&CODE=03&uid=".$row['id']."&aid=".$row['vid'],
														'NAME'         => $row['members_display_name'],
														'MAN_LINK'     => $this->ipsclass->vars['board_url']."/index.php?act=Reg&CODE=05",
														'EMAIL'        => $row['email'],
														'ID'           => $row['id'],
														'CODE'         => $row['vid'],
													  )
												);
												
					$email->subject = "Registration at ".$this->ipsclass->vars['board_name'];
					$email->to      = $row['email'];
					
					$email->send_mail();
				}
				else if ( $row['email_chg'] == 1 )
				{
					$email->get_template("newemail");
						
					$email->build_message( array(
														'NAME'         => $row['members_display_name'],
														'THE_LINK'     => $this->ipsclass->vars['board_url']."/index.php?act=Reg&CODE=03&type=newemail&uid=".$row['id']."&aid=".$row['vid'],
														'ID'           => $row['id'],
														'MAN_LINK'     => $this->ipsclass->vars['board_url']."/index.php?act=Reg&CODE=07",
														'CODE'         => $row['vid'],
													  )
												);
												
					$email->subject = "Email change request at ".$this->ipsclass->vars['board_name'];
					$email->to      = $row['email'];
					
					$email->send_mail();
				}
				
				$resent[] = $row['members_display_name'];
			}
			
			if( count($resent) )
			{
				$this->ipsclass->admin->save_log( count($resent)." Validation Emails Resent: ".implode( ", ", $resent) );
				$main_msgs[] = count($resent)." Validation Emails Resent: ".implode( ", ", $resent);
			}
			
			if( count($cant) )
			{
				$main_msgs[] = "Cannot resend validation emails to the following members who are awaiting admin validation: ".implode( ", ", $cant);
			}
			
			$this->ipsclass->main_msg = count($main_msgs) ? implode( "<br />", $main_msgs ) : '';
			
			$this->member_view_moderation_queue();
		}
		
		//-----------------------------------------
		// DELETE
		//-----------------------------------------
		
		else
		{
			$denied = array();
			
			$this->ipsclass->DB->build_query( array( 'select' => 'name', 'from' => 'members', 'where' => "id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->exec_query();
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$denied[] = $r['name'];
			}
			
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'members'         , 'where' => "id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'member_extra'    , 'where' => "id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'message_text'    , 'where' => "msg_author_id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'message_topics'  , 'where' => "mt_owner_id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'contacts'        , 'where' => "member_id IN(".implode( ",",$ids ).") or contact_id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'validating'      , 'where' => "member_id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'pfields_content' , 'where' => "member_id IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'warn_logs'       , 'where' => "wlog_mid IN(".implode( ",",$ids ).")" ) );
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'members_converge', 'where' => "converge_id IN(".implode( ",",$ids ).")" ) );
			
			$this->ipsclass->DB->do_update( 'posts' , array( 'author_id'  => 0 ), "author_id  IN(".implode( ",",$ids ).")" );
			$this->ipsclass->DB->do_update( 'topics', array( 'starter_id' => 0 ), "starter_id IN(".implode( ",",$ids ).")" );
			
			if ( USE_MODULES == 1 )
			{
				$this->modules->register_class($this);
				$this->modules->on_delete($ids);
			}
			
			$this->ipsclass->admin->save_log( count($ids)." Member Registrations Denied: ".implode( ", ", $denied) );
			
			$this->ipsclass->main_msg = count($ids)." Members Removed";
			$this->member_view_moderation_queue();
		}
	}
	
	
	function member_do_email_unapprove()
	{
		//-----------------------------------------
		// GET member
		//-----------------------------------------
		
		if( !isset($this->ipsclass->input['mid']) )
		{
			$this->ipsclass->admin->error("You did not select any members to unapprove");
		}
		
		$id = intval($this->ipsclass->input['mid']);
		
		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'validating', 'where' => 'member_id='.$id ) );
		
		if( !isset($member['vid']) OR !$member['vid'] )
		{
			$this->ipsclass->admin->error("We did not find any validations pending for this member");
		}
		
		if( !$member['email_chg'] )
		{
			$this->ipsclass->admin->error("You can only unapprove email change requests");
		}
		
		$this->ipsclass->DB->do_update( "members", array( 'email' => $member['prev_email'], 'mgroup' => $member['real_group'] ), "id=".$id );
			
		$this->ipsclass->DB->do_update( "members_converge", array( 'converge_email' => $member['prev_email'] ), "converge_id=".$id );
			
		$this->ipsclass->DB->do_delete( "validating", "vid='{$member['vid']}'" );
			

		if ( USE_MODULES == 1 )
		{
			$this->modules->register_class($this);
			$this->modules->on_group_change($member['member_id'], $member['real_group']);
		}

		$this->ipsclass->admin->save_log( "Member {$id} email change request un-approved");
			

		$this->ipsclass->main_msg = "Member {$id} email change request un-approved";
		$this->member_view_moderation_queue();
	}
	
	/*-------------------------------------------------------------------------*/
	// View locked user accounts due to failed login
	/*-------------------------------------------------------------------------*/
	
	function member_view_locked_queue()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$st       = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$content  = "";
		
		//-----------------------------------------
		// SET PAGE TITLE
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Manage Locked User Account Queue";
		$this->ipsclass->admin->page_detail = "This section allows you to unlock locked user accounts.  A user account is locked when they submit the wrong password to login based on your configurations in the ACP Security Settings.";
		$this->ipsclass->admin->nav[] 		= array( '', 'Locked Accounts' );
		
		//-----------------------------------------
		// Get count
		//-----------------------------------------
		
		if( $this->ipsclass->vars['ipb_bruteforce_attempts'] == 0 )
		{
			$content = $this->html->member_locked_no_rows( "You have not enabled account locking brute force protection" );
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as mcount', 'from' => 'members', 'where' => "failed_login_count>=".$this->ipsclass->vars['ipb_bruteforce_attempts'] ) );
			$this->ipsclass->DB->simple_exec();
			
			$row = $this->ipsclass->DB->fetch_row();
			$cnt = intval($row['mcount']);
			
			//-----------------------------------------
			// Pages...
			//-----------------------------------------
			
			$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $cnt,
															  'PER_PAGE'    => 75,
															  'CUR_ST_VAL'  => $st,
															  'L_SINGLE'    => "",
															  'L_MULTI'     => "",
															  'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=lock",
													 )      );
			
			//-----------------------------------------
			// Print...
			//-----------------------------------------
			
			if ($cnt > 0)
			{
				$this->ipsclass->DB->build_query( array( 'select' => 'name, mgroup, members_display_name, ip_address, id, email, posts, joined, failed_logins, failed_login_count',
														 'from'	  => 'members',
														 'where'  => "failed_login_count>={$this->ipsclass->vars['ipb_bruteforce_attempts']}",
														 'order'  => 'members_display_name ASC',
														 'limit'  => array( $st, 75 )
												)		);

				$this->ipsclass->DB->exec_query();
			
				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$used_ips 		= array();
					$this_attempt 	= array();
					$oldest			= 0;
					$newest			= 0;
					
					if( $r['failed_logins'] )
					{
						$failed_logins = explode( ",", $this->ipsclass->clean_perm_string( $r['failed_logins'] ) );
						
						if( is_array($failed_logins) AND count($failed_logins) )
						{
							sort($failed_logins);
							
							foreach( $failed_logins as $attempt )
							{
								$this_attempt = explode( "-", $attempt );
								$used_ips[] = $this_attempt[1];
							}
							
							$oldest = array_shift($failed_logins);
							$newest = array_pop($failed_logins);
						}
					}
					
					$newest = explode( "-", $newest );
					$oldest = explode( "-", $oldest );
					
					$r['group_title'] = $this->ipsclass->cache['group_cache'][ $r['mgroup'] ]['g_title'];
					
					$r['oldest_fail'] = $this->ipsclass->get_date( $oldest[0], 'SHORT' );
					$r['newest_fail'] = $this->ipsclass->get_date( $newest[0], 'SHORT' );
					$r['_joined'] = $this->ipsclass->get_date( $r['joined']    , 'TINY' );
					
					$r['ip_addresses'] = "";
					
					$used_ips = array_unique($used_ips);
					
					foreach( $used_ips as $ip_address )
					{
						$r['ip_addresses'] .= "IP: <a href='{$this->ipsclass->base_url}&section=content&act=mtools&code=learnip&ip={$ip_address}'>{$ip_address}</a><br />";
					}
					
					if ( !isset($r['name']) OR $r['name'] == "" )
					{
						$r['name'] = "<em>Deleted Member</em>";
					}
					
					//-----------------------------------------
					// Print row
					//-----------------------------------------
					
					$content .= $this->html->member_locked_row( $r );
				}
			}
			else
			{
				$content = $this->html->member_locked_no_rows( "No locked accounts were found" );
			}
		}
		
		$this->ipsclass->html .= $this->html->member_locked_wrapper($content, $st, $links);
		
		$this->ipsclass->admin->output();
	}	
	
	/*-------------------------------------------------------------------------*/
	// Process locked member queue
	/*-------------------------------------------------------------------------*/
	
	function member_do_unlock()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids = array();
		
		//-----------------------------------------
		// GET checkboxes
		//-----------------------------------------
		
		foreach ($this->ipsclass->input as $k => $v)
		{
			if ( preg_match( "/^mid_(\d+)$/", $k, $match ) )
			{
				if ($this->ipsclass->input[ $match[0] ])
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = $this->ipsclass->clean_int_array( $ids );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( count($ids) < 1 )
		{	
			$this->ipsclass->admin->error("You did not select any members to unlock or ban");
		}
		
		//-----------------------------------------
		// Unlock
		//-----------------------------------------
		
		if ($this->ipsclass->input['type'] == 'unlock')
		{
			$this->ipsclass->DB->do_update( "members", array( 'failed_logins' => '', 'failed_login_count' => 0 ), "id IN(".implode( ',', $ids ).')' );
			
			$this->ipsclass->admin->save_log( count($ids)." Members Unlocked");
			
			$this->ipsclass->main_msg = count($ids)." Members Unlocked";
			$this->member_view_locked_queue();
		}
		
		//-----------------------------------------
		// Ban
		//-----------------------------------------
		
		else if ($this->ipsclass->input['type'] == 'ban')
		{
			if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				// This isn't a root admin - make sure they can't ban a root admin
				
				$this->ipsclass->DB->build_query( array( 'select' => 'mgroup', 'from' => 'members', 'where' => "id IN(".implode( ',', $ids ).')' ) );
				$this->ipsclass->DB->exec_query();
				
				while( $mem = $this->ipsclass->DB->fetch_row() )
				{
					if( $mem['mgroup'] == $this->ipsclass->vars['admin_group'] )
					{
						$this->ipsclass->admin->error( "You cannot ban a root administrator" );
					}
				}
			}
			
			$five_is_present = 0;
			$banned_gid		 = isset( $this->ipsclass->vars['banned_group'] ) AND $this->ipsclass->vars['banned_group'] ? $this->ipsclass->vars['banned_group'] : 0;
			
			$this->ipsclass->DB->build_query( array( 'select' => 'g_view_board,g_id', 'from' => 'groups', 'where' => 'g_view_board=0' ) );
			$this->ipsclass->DB->exec_query();
			
			if ( ! $banned_gid )
			{
				while( $r = $this->ipsclass->DB->fetch_row() )
				{
					// Default banned group is 5 - if it's here, let's use that
					if( $r['g_id'] == 5 )
					{
						$five_is_present = 1;
					}
				
					$banned_gid = $r['g_id'];
				}
			
				if ( $five_is_present )
				{
					$banned_gid = 5;
				}
			}
			
			if ( ! $banned_gid )
			{
				$this->ipsclass->admin->error( "You do not have any groups configured who cannot view the board - we could not find a 'Banned' group to place the members in" );
			}
				
			$this->ipsclass->DB->do_update( "members", array( 'failed_logins' => '', 'failed_login_count' => 0, 'mgroup' => $banned_gid  ), "id IN(".implode( ',', $ids ).')' );
			
			$this->ipsclass->admin->save_log( count($ids)." Members Banned");
			
			$this->ipsclass->main_msg = count($ids)." Members Banned";
			$this->member_view_locked_queue();
		}
	}	
	
	/*-------------------------------------------------------------------------*/
	// View member moderation queue
	/*-------------------------------------------------------------------------*/
	
	function member_view_moderation_queue()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->ipsclass->input['ord'] = isset($this->ipsclass->input['ord']) ? $this->ipsclass->input['ord'] : '';
		$st       = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$ord      = $this->ipsclass->input['ord'] == 'asc' ? 'asc' : 'desc';
		$new_ord  = $ord  == 'asc' ? 'desc' : 'asc';
		$filter   = isset($this->ipsclass->input['filter']) ? $this->ipsclass->input['filter'] : '';
		$q_extra  = "";
		$content  = "";
		
		//-----------------------------------------
		// SET PAGE TITLE
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Manage User Registration/Email Change Queues";
		$this->ipsclass->admin->page_detail = "This section allows you to approve or deny registrations where you have requested that an administrator previews new accounts before allowing full membership. It will also allow you to complete or deny new email address changes, and resend validation emails.<br><br>This form will also allow you to complete the registrations for those who did not receive an email.";
		$this->ipsclass->admin->nav[] 		= array( '', 'Validating Members' );
		
		//-----------------------------------------
		// Add extra query
		//-----------------------------------------
		
		switch( $filter )
		{
			case 'reg_user_validate':
				if( $this->ipsclass->vars['reg_auth_type'] != 'admin' )
				{
					$q_extra = " AND v.new_reg=1 AND v.user_verified=0";
				}
				break;
			case 'reg_admin_validate':
				if( $this->ipsclass->vars['reg_auth_type'] == 'admin' )
				{
					$q_extra = " AND v.new_reg=1";
				}
				else
				{
					$q_extra = " AND v.new_reg=1 AND v.user_verified=1";
				}
				break;
			case 'email_chg':
				$q_extra = " AND v.email_chg=1";
				break;
				
			case 'coppa':
				$q_extra = " AND v.coppa_user=1";
				break;
		}
		
		//-----------------------------------------
		// Get count
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' 	=> 'COUNT(*) as mcount', 
												 'from' 	=> array( 'validating' => 'v' ), 
												 'where' 	=> "v.lost_pass=0 AND m.mgroup={$this->ipsclass->vars['auth_group']}".$q_extra,
												 'add_join'	=> array(
												 					array(
												 							'select' 	=> '',
												 							'from'		=> array( 'members' => 'm' ),
												 							'where'		=> 'm.id=v.member_id',
												 							'type'		=> 'left',
												 						),
												 					),
										) 		);
		$this->ipsclass->DB->exec_query();
		
		$row = $this->ipsclass->DB->fetch_row();
		$cnt = intval($row['mcount']);
		
		//-----------------------------------------
		// Sorted?
		//-----------------------------------------
		
		switch ($this->ipsclass->input['sort'])
		{
			case 'mem':
				$col = 'm.members_display_name';
				break;
			case 'email':
				$col = 'm.email';
				break;
			case 'sent':
				$col = 'v.entry_date';
				break;
			case 'posts':
				$col = 'm.posts';
				break;
			case 'reg':
				$col = 'm.joined';
				break;
			default:
				$col = 'v.entry_date';
				break;
		}					     
		
		//-----------------------------------------
		// Pages...
		//-----------------------------------------
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $cnt,
														  'PER_PAGE'    => 75,
														  'CUR_ST_VAL'  => $st,
														  'L_SINGLE'    => "",
														  'L_MULTI'     => "",
														  'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=mod&ord={$ord}&filter={$filter}",
												 )      );
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		if ($cnt > 0)
		{
			$this->ipsclass->DB->build_query( array( 'select' 	=> 'v.*', 
													 'from' 	=> array( 'validating' => 'v' ), 
													 'where' 	=> "v.lost_pass=0 AND m.mgroup={$this->ipsclass->vars['auth_group']}".$q_extra,
													 'order'	=> $col . ' ' . $ord,
													 'limit'	=> array( $st, 75 ),
													 'add_join'	=> array(
													 					array(
													 							'select' 	=> 'm.name, m.mgroup, m.members_display_name, m.ip_address, m.id, m.email, m.posts, m.joined',
													 							'from'		=> array( 'members' => 'm' ),
													 							'where'		=> 'm.id=v.member_id',
													 							'type'		=> 'left',
													 						),
													 					),
											) 		);
			//$this->ipsclass->DB->cache_add_query( 'member_view_mod', array( 'col' => $col, 'ord' => $ord, 'st' => $st, 'extra' => $q_extra ) );
			$this->ipsclass->DB->exec_query();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				if ($r['coppa_user'] == 1)
				{
					$r['_coppa'] = ' ( COPPA Request )';
				}
				else
				{
					$r['_coppa'] = "";
				}
				
				$r['_where'] = ( $r['lost_pass'] ? 'Lost Password' : ( $r['new_reg'] ? "Registering <strong>(User validation)</strong>" : ( $r['email_chg'] ? "Email Change" : 'N/A' ) ) );
				
				if( isset($r['email_chg']) AND $r['email_chg'] )
				{
					$r['_where'] .= " (<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=unappemail&mid={$r['member_id']}'>Unapprove?</a>)";
				}
				
				//-----------------------------------------
				// Update user_admin validation
				//-----------------------------------------
				
				if ( $r['new_reg'] AND ( $r['user_verified'] == 1 OR $this->ipsclass->vars['reg_auth_type'] == 'admin' ) )
				{
					$r['_where'] = "Registering: <strong>(Admin Validation)</strong>";
				}
				
				$r['_hours']  = floor( ( time() - $r['entry_date'] ) / 3600 );
				$r['_days']   = intval( $r['_hours'] / 24 );
				$r['_rhours'] = intval( $r['_hours'] - ($r['_days'] * 24) );
				$r['_joined'] = $this->ipsclass->get_date( $r['joined']    , 'TINY' );
				$r['_entry']  = $this->ipsclass->get_date( $r['entry_date'], 'TINY' );
				
				if ( !isset($r['name']) OR $r['name'] == "" )
				{
					$r['name'] = "<em>Deleted Member</em>";
				}
				
				//-----------------------------------------
				// Print row
				//-----------------------------------------
				
				$content .= $this->html->member_validating_row( $r );
			}
		}
		else
		{
			$content = $this->html->member_locked_no_rows( "No members awaiting validation" );
		}
		
		$this->ipsclass->html .= $this->html->member_validating_wrapper($content, $st, $new_ord, $links);
		
		$this->ipsclass->admin->output();
	}
		
			
}

?>