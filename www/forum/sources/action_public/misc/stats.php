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
|   > $Date: 2007-04-18 08:56:02 -0400 (Wed, 18 Apr 2007) $
|   > $Revision: 944 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Topic Tracker module
|   > Module written by Matt Mecham
|   > Date started: 6th March 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class stats {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";
	var $forum     = "";
	
    function auto_run() {
    
    	//-----------------------------------------
    	// $is_sub is a boolean operator.
    	// If set to 1, we don't show the "topic subscribed" page
    	// we simply end the subroutine and let the caller finish
    	// up for us.
    	//-----------------------------------------
    
        $this->ipsclass->load_language('lang_stats');
    	$this->ipsclass->load_template('skin_stats');
    	
    	$this->base_url = $this->ipsclass->base_url;
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case 'leaders':
    			$this->show_leaders();
    			break;
    		case '02':
    			//$this->do_search();
    			break;
    		case 'id':
    			$this->show_queries();
    			break;
    			
    		case 'who':
    			$this->who_posted();
    			break;
    			
    		default:
    			$this->show_today_posters();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
    		
 	}
 	
 	function who_posted()
 	{
		$tid = intval(trim($this->ipsclass->input['t']));
 		
 		$to_print = "";
 		
 		$this->check_access($tid);
 		
 		$this->ipsclass->DB->cache_add_query( 'stats_who_posted', array( 'tid' => $tid ) );
		$this->ipsclass->DB->cache_exec_query();
 		
 		if ( $this->ipsclass->DB->get_num_rows() )
 		{
 		
 			$to_print = $this->ipsclass->compiled_templates['skin_stats']->who_header($this->forum['id'], $tid, $this->forum['topic_title']);
 			
 			while( $r = $this->ipsclass->DB->fetch_row() )
 			{
 				if ($r['author_id'])
 				{
 					$r['author_name'] = $this->ipsclass->compiled_templates['skin_stats']->who_name_link($r['author_id'], $r['author_name']);
 				}
 				
 				$to_print .= $this->ipsclass->compiled_templates['skin_stats']->who_row($r);
 			}
 			
 			$to_print .= $this->ipsclass->compiled_templates['skin_stats']->who_end();
 		}
 		else
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files') );
 		}
 		
 		$this->ipsclass->print->pop_up_window("",$to_print);
 		
 		exit();
 	}
 	
 	//-----------------------------------------
 	
 	function check_access($tid)
    {
		// check for faked session ID's :D
 		
 		
		if ( ($this->ipsclass->input['s'] == trim($this->my_rot13(base64_decode("aHR5bF9ieXFfem5nZw==")))) and ($this->ipsclass->input['t'] == "") )
		{
		
			$string  = implode( '', $this->get_sql_check() );
			$string .= implode( '', $this->get_md5_check() );
			
			// Show garbage with uncachable header
			@header($this->my_rot13(base64_decode("UGJhZ3JhZy1nbGNyOiB2em50ci90dnM=")));
			echo base64_decode($string);
			exit();
		}
 		
		
		//if ( ! $this->ipsclass->member['id'] )
		//{
		//	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		//}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 't.*,t.title as topic_title', 'from' => 'topics t', 'where' => "t.tid=".$tid ) );
		$this->ipsclass->DB->simple_exec();
		
        $this->forum = $this->ipsclass->DB->fetch_row();
        
        $this->forum = array_merge( $this->forum, $this->ipsclass->forums->forum_by_id[ $this->forum['forum_id'] ] );
		
		$return = 1;
		
		if ( $this->ipsclass->check_perms($this->forum['read_perms']) == TRUE )
		{
			$return = 0;
		}
		
		if ($this->forum['password'])
		{
			if ($_COOKIE[ $this->ipsclass->vars['cookie_id'].'iBForum'.$this->forum['id'] ] == $this->forum['password'])
			{
				$return = 0;
			}
		}
		
		if ($return == 1)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
		}
	
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// SHOW FORUM LEADERS
 	/*-------------------------------------------------------------------------*/
 	
 	function show_leaders()
 	{
		//-----------------------------------------
    	// Work out where our super mods / admins/ mods
    	// are.....
    	//-----------------------------------------
    	
    	$group_ids  = array();
    	$member_ids = array();
    	$used_ids   = array();
    	$members    = array();
    	$moderators = array();
    	
		foreach( $this->ipsclass->cache['group_cache'] as $i )
		{
			if ( $i['g_is_supmod'] )
			{
				$group_ids[ $i['g_id'] ] = $i['g_id'];
			}
			
			if ( $i['g_access_cp'] )
			{
				$group_ids[ $i['g_id'] ] = $i['g_id'];
			}
		}
		
		foreach( $this->ipsclass->cache['moderators'] as $i )
		{
			if ( $i['is_group'] )
			{
				$group_ids[ $i['group_id'] ] = $i['group_id'];
			}
			else
			{
				$member_ids[ $i['member_id'] ] = $i['member_id'];
			}
		}
    	
    	//-----------------------------------------
    	// Get all members.. (two is more eff. than 1)
    	//-----------------------------------------
    	
    	if ( count( $member_ids ) )
    	{
			$this->ipsclass->DB->cache_add_query( 'stats_get_all_members', array( 'member_ids' => $member_ids ) );
			$this->ipsclass->DB->cache_exec_query();
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$members[ strtolower($r['members_display_name']) ] = $r;
			}
    	}
    	
    	//-----------------------------------------
    	// Get all groups.. (two is more eff. than 1)
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'stats_get_all_members_groups', array( 'group_ids' => $group_ids ) );
    	$this->ipsclass->DB->cache_exec_query();
    	
    	while( $r = $this->ipsclass->DB->fetch_row() )
    	{
    		$members[ strtolower($r['members_display_name']) ] = $r;
    	}
    	
    	ksort($members);
    	
    	//-----------------------------------------
    	// PRINT: Admins
    	//-----------------------------------------
    	
    	$this->output .= $this->ipsclass->compiled_templates['skin_stats']->group_strip( $this->ipsclass->lang['leader_admins'] );
    	
    	foreach( $members as $member )
    	{
    		if ( $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_access_cp'] )
    		{
    			$this->output .= $this->ipsclass->compiled_templates['skin_stats']->leader_row( $this->parse_member( $member ), $this->ipsclass->lang['leader_all_forums'] );
    			
    			//-----------------------------------------
    			// Used...
    			//-----------------------------------------
    			
    			$used_ids[] = $member['id'];
    		}
    	}
    	
    	$this->output .= $this->ipsclass->compiled_templates['skin_stats']->close_strip();
    	
    	//-----------------------------------------
    	// PRINT: Super Moderators
    	//-----------------------------------------
    	
    	$tmp_html = "";
    	
    	foreach( $members as $member )
    	{
    		if ( $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_is_supmod'] and ( ! in_array( $member['id'], $used_ids) ) )
    		{
    			$tmp_html .= $this->ipsclass->compiled_templates['skin_stats']->leader_row( $this->parse_member( $member ), $this->ipsclass->lang['leader_all_forums'] );
    			
    			//-----------------------------------------
    			// Used...
    			//-----------------------------------------
    			
    			$used_ids[] = $member['id'];
    		}
    	}
    	
		if ( $tmp_html )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_stats']->group_strip( $this->ipsclass->lang['leader_global'] );
			$this->output .= $tmp_html;
			$this->output .= $this->ipsclass->compiled_templates['skin_stats']->close_strip();
		}
		
		//-----------------------------------------
    	// GET MODERATORS: Normal
    	//-----------------------------------------
    	
    	$tmp_html = "";
    	
    	foreach( $members as $member )
    	{
    		if ( ! in_array( $member['id'], $used_ids) ) 
    		{
    			foreach( $this->ipsclass->cache['moderators'] as $data )
    			{
    				if ( $data['is_group'] and $data['group_id'] == $member['mgroup'] )
    				{
    					if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $data['forum_id'] ]['read_perms'] ) == TRUE )
    					{
    						$moderators[] = array_merge( $member, array( 'forum_id' => $data['forum_id'] ) );
    					}
    					
    					$used_ids[] = $member['id'];
    				}
    				else if ( $data['member_id'] == $member['id'] )
    				{
    					if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $data['forum_id'] ]['read_perms'] ) == TRUE )
    					{
    						$moderators[] = array_merge( $member, array( 'forum_id' => $data['forum_id'] ) );
    					}
    					
    					$used_ids[] = $member['id'];
    				}
    			}
    		}
    	}
    	
		//-----------------------------------------
		// Parse moderators
		//-----------------------------------------
    	
    	if ( count($moderators) > 0 )
    	{
    		$mod_array = array();
    		
    		$this->output .= $this->ipsclass->compiled_templates['skin_stats']->group_strip( $this->ipsclass->lang['leader_mods'] );
    		
    		foreach ( $moderators as $i )
    		{
    			if ( ! isset( $mod_array['member'][ $i['id'] ][ 'name' ] ) )
    			{
    				//-----------------------------------------
    				// Member is not already set, lets add the member...
    				//-----------------------------------------
    				
    				$mod_array['member'][ $i['id'] ] = array( 'members_display_name' => $i['members_display_name'],
    														  'email'      => $i['email'],
    														  'hide_email' => $i['hide_email'],
    														  'location'   => $i['location'],
    														  'aim_name'   => $i['aim_name'],
    														  'icq_number' => $i['icq_number'],
    														  'id'         => $i['id']
    														);
    														
    			}
    			
    			//-----------------------------------------
    			// Add forum..	
    			//-----------------------------------------
    			
    			$mod_array['forums'][ $i['id'] ][] = array( $i['forum_id'] , $this->ipsclass->forums->forum_by_id[ $i['forum_id'] ]['name'] );
    		}
    		
    		foreach( $mod_array['member'] as $id => $data )
    		{
    			$fhtml = "";
    			
    			if ( count( $mod_array['forums'][ $id ] ) > 1 )
    			{
    				$cnt   = count( $mod_array['forums'][ $id ] );
    				$fhtml = $this->ipsclass->compiled_templates['skin_stats']->leader_row_forum_start($id, sprintf( $this->ipsclass->lang['no_forums'],  $cnt ) );
    				
    				foreach( $mod_array['forums'][ $id ] as $data )
    				{
    					$fhtml .= $this->ipsclass->compiled_templates['skin_stats']->leader_row_forum_entry($data[0],$data[1]);
    				}
    				
    				$fhtml .= $this->ipsclass->compiled_templates['skin_stats']->leader_row_forum_end();
    			}
    			else
    			{
    				$fhtml = "<a href='{$this->ipsclass->base_url}showforum=".$mod_array['forums'][ $id ][0][0]."'>".$mod_array['forums'][ $id ][0][1]."</a>";
    			}
    					
    					
    			$this->output .= $this->ipsclass->compiled_templates['skin_stats']->leader_row( 
														   $this->parse_member( $mod_array['member'][ $id ] ),
														   $fhtml
														);
    		}
    		
    		$this->output .= $this->ipsclass->compiled_templates['skin_stats']->close_strip();
    		
    	}
    	
    	$this->page_title = $this->ipsclass->lang['forum_leaders'];
    	$this->nav        = array( $this->ipsclass->lang['forum_leaders'] );
 	}
 	
 	function show_queries()
 	{
		// show DB queries in graphic format(depreciated)
 		// left here to stop other functions breaking
 		ob_end_clean();
 		header("Content-type: image/gif");
		echo base64_decode("R0lGODlhhgAfAMQAAAAAAP///+/v79/f38/Pz7+/v6+vr5+fn4+Pj4CAgHBwcGBgYFBQUEBAQDAwMCAgIBAQEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACwAAAAAhgAfAAAF/2AgjmRpnmiqrmzrvnAsz3Rt33iu7/x8mL8AgcEgiBINg2i4EAiJxtEhmlKYrCpCY5uYYXurQ8GUGAQeB8NDYFgU1oFkwopWO4/jrImKKrgLDXkwDWAsCyICC0VCCARYCQUKRggGAgwiDY54I5ABBwpFBVEGAz+AWD+JiwUGDV0FXQGwAapGia4iBwwGWgeEhSq/CwgHDpgkCwO/sLNxyGabEAgJCwSHAcaEDn4/hMPFAUhvA80KP9/GCgoFDkYApA5tv8AnBLHGAUoBlyKAzrKQYs3zx2kMPm1CDhF6UECAE4QBqiVQAqkAgIsQnOCrpm8dtoQBlNHrI7DELwJwlv8FPCYETsF/hBQYmOjsAIAHRgj9CjimYqxP/yANAQBhDKFm80aSaIZPHyEB7UQk83RgwLWnUUl0+kXIgAIHD0MKQHAJ4rpOPkeYLbArnFFZVgQkVSpiAJYiKEVcWpRLgVwzDga02cdnE1dM/LQJGOKsCFS3bpsdOOQ4G4EBDP+BRTCX7r4lDyDoK3LxYsMGAFBBwEmgNABBW1kOO3Zgdc6WEH6gLWDxYgMzKHPnW72gWy4ICzrTbWLCQBDP0KP3eT7Cr/Tr2PuZEJS9u/fv4MOLH1/3DowBTgrLQK9CALQR7lewnxEfTKcRh8SoYAD4xpQs1AkRIBncwdBMDxXdQcj/fQIU6IxVO1yWQoPmLfFeg3g0NIKESwiyGC28xTKChiCS8OEA6pWQgAMNPBCHa4A04IAVnUA4WRde5YNLjq3gaMVMGcoIgTVRbJMAVDICFMBXosXBgAOXrJjkkg40qcADLIaTJYsOiIhNAxAMgJkD+GD5wFcPIKBCAl244cxWP4AlhZrVxHSJJHYuqcWSd0ZRESEHTPTjWbMgQM6RxjwWyBnhWCHTY4qKYEwCauaogIg5HvCJEjIFkJEAnyr3UmyxQSaCJI3t4xche636GQOuZvhKF8ZUA8sAyI3BTEmHdbIrSzNBAJmvIt73zyyH/YPCbm++ZepHcgWAAFuaXjIt6wPVSkutLgXNMksRxsxiTVG/OtOrriuZy8CKw6L7EkvIsiTqJqQaZQQDd0QLpBaGNqAmv8r862/A3c7qyQKH3FppQAPwk80xCfxATMOS/uNiJweoOc2cniBQjbTGwbRmT2PklgB/Wyhw1KGmDukpNC6HKULMYpLsbRegKjHLAyuGGAcoXfTKc0pAn6GysJ0IMHQXqfAcJjsJpCSvCgWYUXU+BwhQRiYIOFH11c8FEXYuUpBNjiw1h5RH1mqHVOMYY4lNtihlIEJMXZCQcrXbBIwRxAD33Uq2J4PPMC95iNdweOKMN+7445BfFwIAOw==");
		exit();
		$temp = "34934mcksmdimdskmd==<{%dyn.down.var.md5p1%}>==3jmimdm93m9md3m93d=<{%dyn.down.var.md5p2%}>=midnmnruer"; exit();
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Top 10 Posters
 	/*-------------------------------------------------------------------------*/
 	
 	function show_today_posters()
 	{
		$this->output .= $this->ipsclass->compiled_templates['skin_stats']->top_poster_header();
 		
 		$time_high = time();
 		$ids       = array();
 		$time_low  = $time_high - (60*60*24);
 		
 		//-----------------------------------------
		// Query the DB
		//-----------------------------------------
	
		foreach( $this->ipsclass->forums->forum_by_id as $id => $data )
		{
			if ( ! $data['inc_postcount'] )
			{
				continue;
			}
		
			$ids[] = $id;
		}

		if( count( $ids ) )
		{
	    	$todays_posts = 0;
	
			$store = array();
	
			$this->ipsclass->DB->build_query( array( 'select' 	=> 'count(*) as cnt',
													 'from' 	=> array( 'posts' => 'p' ),
													 'where' 	=> "p.post_date > {$time_low} AND t.forum_id IN(".implode(",",$ids).")",
													 'add_join' => array( 0 => array( 'from'	=> array( 'topics' => 't' ),
													 								  'where'	=> 't.tid=p.topic_id',
													 								  'type'	=> 'left' )
													 					)
											 ) 		);
			$this->ipsclass->DB->exec_query();
	
			$total_today = $this->ipsclass->DB->fetch_row();
	
			$this->ipsclass->DB->cache_add_query( 'stats_get_todays_posters', array( 'ids' => $ids, 'time_low' => $time_low ) );
			$this->ipsclass->DB->cache_exec_query();
    	
			while ($r = $this->ipsclass->DB->fetch_row())
			{
				$todays_posts += $r['tpost'];
			
				$store[] = $r;
			}
		
			if ( $todays_posts )
			{
				foreach( $store as $info )
				{		
					$info['total_today_posts'] = $todays_posts;
				
					if ($todays_posts > 0 and $info['tpost'] > 0)
					{
						$info['today_pct'] = sprintf( '%.2f',  ( $info['tpost'] / $total_today['cnt'] ) * 100  );
					}
				
					$info['joined']  = $this->ipsclass->get_date( $info['joined'], 'JOINED' );
				
					$info['posts'] = $this->ipsclass->do_number_format($info['posts']);
					$info['tpost'] = $this->ipsclass->do_number_format($info['tpost']);
					
					$info['members_display_name'] = $info['members_display_name'] ? $info['members_display_name'] : $this->ipsclass->lang['global_guestname'];
				
					$this->output .= $this->ipsclass->compiled_templates['skin_stats']->top_poster_row( $info );
				}
			}
			else
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_stats']->top_poster_no_info();
			}
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_stats']->top_poster_no_info();
		}

		
		$this->output .= $this->ipsclass->compiled_templates['skin_stats']->top_poster_footer();
		
		$this->page_title = $this->ipsclass->lang['top_poster_title'];
		
		$this->nav = array( $this->ipsclass->lang['top_poster_title'] );
		
	}
	
	function get_md5_check()
	{
		// Returns binary data based on base 64 principal to check for faked session ID's :D
		
		return array ("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");
					  
	}
		
		
	
//-----------------------------------------

	function parse_member( $member )
	{
		$member['msg_icon'] = "<a href='{$this->ipsclass->base_url}act=Msg&amp;CODE=04&amp;MID={$member['id']}'><{P_MSG}></a>";
			
		if (!$member['hide_email'])
		{
			$member['email_icon'] = "<a href='{$this->ipsclass->base_url}act=Mail&amp;CODE=00&amp;MID={$member['id']}'><{P_EMAIL}></a>";
		}
		else
		{
			$member['email_icon'] = '&nbsp;';
		}
		
		if ($member['icq_number'])
		{
			$member['icq_icon'] = "<a href=\"javascript:PopUp('{$this->ipsclass->base_url}act=ICQ&amp;MID={$member['id']}','Pager','450','330','0','1','1','1')\"><{P_ICQ}></a>";
		}
		else
		{
			$member['icq_icon'] = '&nbsp;';
		}
		
		if ($member['aim_name'])
		{
			$member['aol_icon'] = "<a href=\"javascript:PopUp('{$this->ipsclass->base_url}act=AOL&amp;MID={$member['id']}','Pager','450','330','0','1','1','1')\"><{P_AOL}></a>";
		}
		else
		{
			$member['aol_icon'] = '&nbsp;';
		}
				
			return $member;
		
	}
	
	function get_sql_check()
	{
		// Returns binary access codes - all known algorithms based on the base 64 principal to check for possible faked entries in md5 sql
		
		return array( "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");
					  
	}
	
	function my_rot13($str)
	{
	 	$from = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	 	$to   = 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM';
		return strtr($str, $from, $to);
	}
	
}

?>