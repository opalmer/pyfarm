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
|   > $Date: 2007-09-19 15:37:06 -0400 (Wed, 19 Sep 2007) $
|   > $Revision: 1107 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > MySQL DB Queries abstraction module
|   > Module written by Matt Mecham
|   > Date started: 26th November 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class sql_queries extends db_driver_mysql
{

     var $db  = "";
     var $tbl = "";
     
    /*========================================================================*/
    // Set up...             
    /*========================================================================*/  
                   
    function sql_queries( &$obj )
    {
    	$this->db = &$obj;
    	
    	if ( ! isset($this->db->obj['sql_tbl_prefix']) )
    	{
    		$this->db->obj['sql_tbl_prefix'] = SQL_PREFIX;
    	}
    	
    	$this->tbl = $this->db->obj['sql_tbl_prefix'];
    }
    
    /*========================================================================*/
    
    # NEW: 2.2
    function calendar_get_events( $a )
    {
	    return "SELECT * 
	    			FROM ".SQL_PREFIX."cal_events 
	    			WHERE event_calendar_id={$a['calendar_id']} AND {$a['approved']}
	    			AND ( (event_unix_to >= {$a['timenow']} AND event_unix_from <= {$a['timethen']} )
	    			OR ( event_unix_to=0 AND event_unix_from >= {$a['timenow']} AND event_unix_from <= {$a['timethen']} )
	    			OR ( event_recurring=3 AND FROM_UNIXTIME(event_unix_from,'%c') IN ({$a['month']}) AND event_unix_to <= {$a['timethen']} ) )";
    }
    
    function calendar_get_events_cache( $a )
    {
	    return "SELECT * 
	    			FROM ".SQL_PREFIX."cal_events 
	    			WHERE {$a['extra']} event_approved=1
	    			AND ( (event_unix_to >= {$a['timenow']} AND event_unix_from <= {$a['timethen']} )
	    				OR ( event_unix_to=0 AND event_unix_from >= {$a['timenow']} AND event_unix_from <= {$a['timethen']} )
	    				OR ( event_recurring=3 AND FROM_UNIXTIME(event_unix_from,'%c')={$a['month']} AND event_unix_to <= {$a['timethen']} ) )";
    }
    
    function session_get_count( $a )
    {
	    $to_return = "";
	    
	    $to_return = "SELECT COUNT(*) as total_sessions 
	    				FROM ".SQL_PREFIX."sessions s 
	    				WHERE s.running_time > {$a['time']} {$a['db_mem']}";
	    
	    if( $a['remove_anon'] )
	    {
		    $to_return .= " AND s.login_type <> 1";
	    }
	    
	    if( $a['remove_spiders'] )
	    {
		    $to_return .= " AND RIGHT(s.id, 8) != '_session'";
	    }
	    
	    return $to_return;
    }
    
    function session_get_sessions( $a )
    {
	    $to_return = "";
	    
	    $to_return = "SELECT * 
	    				FROM ".SQL_PREFIX."sessions s 
	    				WHERE s.running_time > {$a['time']} {$a['db_mem']}";
	    
	    if( $a['remove_anon'] )
	    {
		    $to_return .= " AND s.login_type <> 1";
	    }
	    
	    if( $a['remove_spiders'] )
	    {
		    $to_return .= " AND RIGHT(s.id, 8) != '_session'";
	    }
	    
	    $to_return .= " ORDER BY {$a['db_key']} {$a['db_order']} LIMIT {$a['start']}, {$a['finish']}";
	    
	    return $to_return;
    }    
		    
        
    # NEW: 2.1 (a5)
    function general_get_by_display_name( $a )
    {
    	return "SELECT id, members_display_name, name, email, mgroup, member_login_key, ip_address, login_anonymous
					FROM ".SQL_PREFIX."members
					WHERE members_l_display_name='{$a['members_display_name']}'";
    }
    
    
    function forums_get_replied_topics( $a )
    {
    	return  "SELECT COUNT(DISTINCT(p.topic_id)) as max 
    				FROM ".SQL_PREFIX."topics t
				  		LEFT JOIN ".SQL_PREFIX."posts p ON (p.topic_id=t.tid)
				 	WHERE t.forum_id={$a['fid']} AND p.author_id={$a['mid']} AND p.new_topic=0
				 	{$a['approved']} {$a['prune_filter']}";
    }
    
    function forums_get_replied_topics_actual( $a )
    {
    	return  "SELECT DISTINCT(p.author_id), t.* 
    				FROM ".SQL_PREFIX."topics t
				  		LEFT JOIN ".SQL_PREFIX."posts p ON (p.topic_id=t.tid AND p.author_id={$a['mid']})
				 	WHERE t.forum_id={$a['fid']} AND {$a['query']} AND p.new_topic=0
				 	ORDER BY t.pinned desc,{$a['topic_sort']} {$a['sort_key']} {$a['r_sort_by']}
				 	LIMIT {$a['limit_a']}, {$a['limit_b']}";
    }
    
    function topics_check_for_mod( $a )
    {
    	# topics.php
    	
    	return "SELECT * 
    				FROM ".SQL_PREFIX."moderators 
    				WHERE forum_id={$a['fid']} AND (member_id={$a['mid']} OR (is_group=1 AND group_id IN({$a['gid']})))";
    }
    
    
    function topics_get_posts( $a )
    {
    	# topics.php
    	
    	return "SELECT p.*, pp.*,
				m.id,m.name,m.mgroup,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title,m.hide_email, m.warn_level, m.warn_lastwarn,
				me.msnname,me.aim_name,me.icq_number,me.signature, me.website,me.yahoo,me.location, me.avatar_location, me.avatar_type, me.avatar_size, m.members_display_name
					FROM ".SQL_PREFIX."posts p
				  		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
						LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)
				  		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
					WHERE p.pid IN(".implode(',', $a['pids']).") 
					ORDER BY {$a['scol']} {$a['sord']}";
    }
    
    function topics_get_posts_with_join( $a )
    {
    	# topics.php
    	
    	return "SELECT p.*, pp.*,
				m.id,m.name,m.mgroup,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title,m.hide_email, m.warn_level, m.warn_lastwarn,
				me.msnname,me.aim_name,me.icq_number,me.signature, me.website,me.yahoo,me.location, me.avatar_location, me.avatar_type, me.avatar_size, m.members_display_name,
				pc.*
					FROM ".SQL_PREFIX."posts p
				  		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
				  		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
				  		LEFT JOIN ".SQL_PREFIX."pfields_content pc ON (pc.member_id=p.author_id)
					    LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)
					WHERE p.pid IN(".implode(',', $a['pids']).") 
					ORDER BY {$a['scol']} {$a['sord']}";
    }
    
    function topics_get_active_users( $a )
    {
    	# topics.php
    	
    	return "SELECT s.member_id, s.member_name, s.member_group, s.id, s.login_type, s.location, s.running_time
					FROM ".SQL_PREFIX."sessions s
					WHERE s.location_1_type='topic' AND s.location_1_id={$a['tid']}
					AND s.running_time > {$a['time']} AND s.in_error=0";
	}

	# Deleted			
	function topics_replace_topic_read( $a )
	{
		# topics.php
		# Not got REPLACE INTO? Use delete from .. where, then insert into ... set...
		
		return "REPLACE INTO ".SQL_PREFIX."topics_read 
					SET read_tid={$a['tid']},read_mid={$a['mid']},read_date={$a['date']}";	
	}

	
	function post_topic_tracker( $a )
	{
		#post
		
		return "SELECT tr.trid, tr.topic_id, tr.last_sent, m.members_display_name, m.email, m.id, m.email_full, m.language, m.org_perm_id, m.mgroup, m.mgroup_others, m.last_activity, t.title, t.forum_id, t.approved
					FROM ".SQL_PREFIX."tracker tr
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=tr.topic_id)
						LEFT JOIN ".SQL_PREFIX."members m ON (m.id=tr.member_id)
					WHERE tr.topic_id='{$a['tid']}' AND m.id <> {$a['mid']}
					AND ( ( tr.topic_track_type='delayed' AND m.last_activity < {$a['last_post']} ) OR tr.topic_track_type='immediate' )";
	
	}
	
	#REMOVED
	/*function post_forum_tracker( $a )
	{
		#post
		
		return "SELECT tr.frid, m.name, m.email, m.id, m.language, m.last_activity, m.org_perm_id, g.g_perm_id
				FROM ".SQL_PREFIX."forum_tracker tr,".SQL_PREFIX."members m, ".SQL_PREFIX."groups g
				WHERE tr.forum_id={$a['fid']}
				AND tr.member_id=m.id
				AND m.mgroup=g.g_id
				AND m.id <> {$a['mid']}
				AND ( ( tr.forum_track_type='delayed' AND m.last_activity < {$a['last_post']} ) OR tr.forum_track_type='immediate' )";
	}*/
	
	
	function post_get_quoted( $a )
	{
		return "SELECT p.*,t.forum_id 
					FROM ".SQL_PREFIX."posts p 
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
					WHERE pid IN (".implode(",", $a['quoted_pids']).")";
	
	}
	
	function msg_get_msg_poster( $a )
	{
		return "SELECT m.*, g.* 
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup) 
					WHERE m.id={$a['mid']}";
	}
	

	function msg_get_msg_to_show( $a )
	{
		return "SELECT m.id,m.name,m.members_disable_pm,m.mgroup,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title,m.hide_email, m.warn_level, m.warn_lastwarn,
				g.g_id, g.g_title, g.g_icon, g.g_dohtml, m.members_display_name,
				me.msnname,me.aim_name,me.icq_number,me.signature, me.website,me.yahoo,me.location, me.avatar_location, me.avatar_type, me.avatar_size,
				mt.*, msg.*, pp.*
					FROM ".SQL_PREFIX."message_topics mt
				 		LEFT JOIN ".SQL_PREFIX."message_text msg ON (msg.msg_id=mt.mt_msg_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=mt.mt_from_id)
				 		LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
						LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)
					WHERE mt.mt_id={$a['msgid']} AND mt.mt_owner_id={$a['mid']}";
	}
	
	function msg_get_saved_msg( $a )
	{
		return "SELECT m.id, m.name, m.members_display_name, m.members_disable_pm, mt.*, msg.*, mm.id as from_id, mm.members_display_name as from_name
					FROM ".SQL_PREFIX."message_topics mt
				 		LEFT JOIN ".SQL_PREFIX."message_text msg ON (msg.msg_id=mt.mt_msg_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=mt.mt_to_id)
				 		LEFT JOIN ".SQL_PREFIX."members mm ON (mm.id=mt.mt_from_id)
					WHERE mt.mt_id={$a['msgid']} AND mt.mt_owner_id={$a['mid']}";
	}
	
	#IPB 2.1: Changed m.members_display_name AND LOWER(m.members_display_name)
    function msg_get_cc_users( $a )
    {
            return "SELECT m.mgroup_others, m.id, m.name, m.members_disable_pm, m.members_display_name, m.msg_total, m.view_pop, m.email_pm, m.language, m.email, me.vdirs, g.g_max_messages, g.g_use_pm 
            			FROM ".SQL_PREFIX."members m
                    		INNER JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
                    		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
                       	WHERE m.members_l_display_name IN (".implode(",",$a['name_array']).")";
    }
	
	function msg_get_cc_blocked( $a )
	{
	
		return "SELECT m.name, m.members_display_name, m.members_disable_pm, c.allow_msg
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."contacts c ON (c.member_id=m.id)
					WHERE c.contact_id={$a['mid']} AND c.member_id IN (".implode(",",$a['cc_array']).")";
	}
	
	# Changed: mem.members_display_name as from_name
	function msg_get_sent_list( $a )
	{
		return "SELECT mem.members_display_name as from_name, mem.id as from_id, mt.*, msg.msg_id, msg.msg_cc_users
				 	FROM ".SQL_PREFIX."message_topics mt
				 		LEFT JOIN ".SQL_PREFIX."members mem ON ( mem.id=mt.mt_to_id )
				 		LEFT JOIN ".SQL_PREFIX."message_text msg ON ( msg.msg_id=mt.mt_msg_id )
					WHERE mt.mt_owner_id={$a['mid']} AND (mt.mt_from_id={$a['mid']} OR mt.mt_to_id={$a['mid']}) AND mt.mt_vid_folder='{$a['vid']}'
					ORDER BY {$a['sort']} 
					LIMIT {$a['limita']}, {$a['limitb']}";
	}
	
	# Changed: mem.members_display_name as from_name
	function msg_get_folder_list( $a )
	{
		return "SELECT mt.*,mem.members_display_name as from_name, mem.id as from_id
				 	FROM ".SQL_PREFIX."message_topics mt
				 		LEFT JOIN ".SQL_PREFIX."members mem ON ( mem.id=mt.mt_from_id )
					WHERE mt.mt_owner_id={$a['mid']} AND (mt.mt_from_id={$a['mid']} OR mt.mt_to_id={$a['mid']}) AND mt.mt_vid_folder='{$a['vid']}'
					ORDER BY {$a['sort']} 
					LIMIT {$a['limita']}, {$a['limitb']}";
	}
	
	# Changed: mp.members_display_name as to_name
	function msg_get_tracking( $a )
	{
		return "SELECT msg.*, mt.*, mp.members_display_name as to_name, mp.id as memid
				 	FROM ".SQL_PREFIX."message_topics mt
				  		LEFT JOIN ".SQL_PREFIX."message_text msg ON ( msg.msg_id= mt.mt_msg_id )
				  		LEFT JOIN ".SQL_PREFIX."members mp ON (mp.id=mt.mt_to_id)
					WHERE mt.mt_from_id={$a['mid']} AND mt.mt_tracking=1
					ORDER BY mt.mt_date DESC";
	
	}
	
	# Updated (msg_get_new_pm_notification) in v2.1 [changed LIMIT ADDED m.members_display_name ]
	function msg_get_new_pm_notification( $a )
	{
		return "SELECT m.id,m.name,m.mgroup,m.email,m.joined,m.posts, m.last_visit, m.last_activity,
				 m.warn_level, m.warn_lastwarn, m.members_display_name,
				 me.*,
				g.g_id, g.g_title, g.g_icon, g.g_dohtml, mt.*, msg.*
					FROM ".SQL_PREFIX."message_topics mt
				 		LEFT JOIN ".SQL_PREFIX."message_text msg ON (msg.msg_id=mt.mt_msg_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=mt.mt_from_id)
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=mt.mt_from_id)
				 		LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
					WHERE mt.mt_owner_id={$a['mid']} AND mt.mt_vid_folder='in' 
					ORDER BY mt_date DESC 
					LIMIT {$a['limit_a']},1";
	}
	
	function ucp_tracker_prune( $a )
	{
		return "SELECT tr.trid 
					FROM ".SQL_PREFIX."tracker tr
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=tr.topic_id) 
					WHERE t.last_post < {$a['time']}";
	}
	# Changed
	function profile_get_all( $a )
	{
		return "SELECT m.*, me.*, s.location_1_id, s.location_2_id, s.location_1_type, s.location_2_type, s.running_time, s.location as sesslocation, p.*
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."member_extra me ON ( me.id=m.id )
						LEFT JOIN ".SQL_PREFIX."sessions s ON (s.member_id=m.id)
						LEFT JOIN ".SQL_PREFIX."profile_portal p ON (p.pp_member_id=m.id)
					WHERE m.id={$a['mid']}";
	}
	
	function profile_get_favourite( $a )
	{
		return "SELECT t.forum_id, COUNT(p.author_id) as f_posts
    				FROM ".SQL_PREFIX."posts p
    				  	LEFT JOIN ".SQL_PREFIX."topics t ON ( t.tid=p.topic_id AND t.forum_id IN (".implode(",",$a['fid_array']).") )
    			    WHERE p.author_id={$a['mid']} AND t.tid IS NOT NULL
    			    GROUP BY t.forum_id
    			    ORDER BY f_posts DESC";
	}
	
	function usercp_get_attachments( $a )
	{
		return "SELECT a.*, t.*, p.topic_id
				 	FROM ".SQL_PREFIX."attachments a
				  		LEFT JOIN ".SQL_PREFIX."posts p ON ( p.pid=a.attach_rel_id )
				  		LEFT JOIN ".SQL_PREFIX."topics t ON ( t.tid=p.topic_id )
				 	WHERE a.attach_member_id={$a['mid']} AND a.attach_rel_module IN( 'post', 'msg' )
				 	ORDER BY {$a['order']}
				 	LIMIT {$a['limit_a']}, {$a['limit_b']}";
	}
	
	function usercp_get_to_delete( $a )
	{
		return "SELECT a.*, p.topic_id, p.pid, mt.mt_id
				 	FROM ".SQL_PREFIX."attachments a
				  		LEFT JOIN ".SQL_PREFIX."posts p ON ( p.pid=a.attach_rel_id AND a.attach_rel_module='post' )
				  		LEFT JOIN ".SQL_PREFIX."message_topics mt ON ( mt.mt_msg_id=a.attach_rel_id AND a.attach_rel_module='msg' )
				 	WHERE a.attach_id IN (".implode(",",$a['aid_array']).") AND a.attach_rel_module IN( 'post', 'msg' ) AND attach_member_id={$a['mid']}";
	}
	
	
	
	function stats_get_all_members( $a )
	{
		return "SELECT m.*, me.*
    			 	FROM ".SQL_PREFIX."members m
    					LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
    			 	WHERE m.id IN(".implode(',', $a['member_ids']).")
    			 	ORDER BY m.members_l_display_name";
	}
	
	function stats_get_all_members_groups( $a )
	{
		return "SELECT m.*, me.*
    			 	FROM ".SQL_PREFIX."members m
    					LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
    			 	WHERE m.mgroup IN (".implode( ',', $a['group_ids'] ).")
    			 	ORDER BY m.members_l_display_name";
	}
	
	function stats_get_todays_posters( $a )
	{
		return "SELECT COUNT(*) as tpost, m.id, m.name, m.members_display_name, m.joined, m.posts
				 	FROM ".SQL_PREFIX."posts p
						LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id )
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
					WHERE t.forum_id in(".implode(",", $a['ids']).") and p.post_date > {$a['time_low']}
					GROUP BY p.author_id
					ORDER BY tpost DESC 
					LIMIT 0,20";
	}
	
	
	# Changed: added m.members_display_name
	function ucp_get_all_announcements( $a )
	{
		return "SELECT a.*, m.id, m.name, m.members_display_name
				 	FROM ".SQL_PREFIX."announcements a
				  		LEFT JOIN ".SQL_PREFIX."members m on (m.id=a.announce_member_id)
				 	ORDER BY a.announce_end DESC";
	}
	
	function ucp_get_all_announcements_byid( $a )
	{
		return "SELECT a.*, pp.*, m.*, me.*
				 	FROM ".SQL_PREFIX."announcements a
				  		LEFT JOIN ".SQL_PREFIX."members m on (m.id=a.announce_member_id)
				  		LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)
				  		LEFT JOIN ".SQL_PREFIX."member_extra me on (me.id=m.id)
				 	WHERE a.announce_id={$a['id']}";
	}
	
	function ucp_get_forum_tracker( $a )
	{
		return "SELECT t.*, f.*
					FROM ".SQL_PREFIX."forum_tracker t
				 		LEFT JOIN ".SQL_PREFIX."forums f ON (f.id=t.forum_id)
					WHERE t.member_id={$a['mid']}
					ORDER BY f.position";
	}
	
	function ucp_get_topic_tracker( $a )
	{
		return "SELECT s.topic_track_type, s.trid, s.member_id, s.topic_id, s.last_sent, s.start_date as track_started, t.*, f.id as forum_id, f.name as forum_name
					FROM ".SQL_PREFIX."tracker s
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=s.topic_id)
						LEFT JOIN ".SQL_PREFIX."forums f ON (f.id=t.forum_id)
					WHERE s.member_id={$a['mid']} {$a['date_query']}
					ORDER BY f.id, t.last_post DESC";
	}
	
	# Changed, removed group stuff
	function mlist_count( $a )
	{
		# Attempt to optimize on the fly...
		
		$query_bit_1 = '';
		$query_bit_2 = '';
		
		if ( strstr( $a['query'], 'p.field_' ) )
		{
			$query_bit_2 = "LEFT JOIN ".SQL_PREFIX."pfields_content p ON (p.member_id=m.id)";
		}
		
		if ( strstr( $a['query'], 'me.' ) )
		{
			$query_bit_1 = "LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)";
		}
		
		if ( strstr( $a['query'], 'pp.' ) )
		{
			$query_bit_3 = "LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)";
		}
		
		if ( $a['query'] )
		{
			$a['query'] = 'WHERE '.$a['query'];
		}
		
		return "SELECT COUNT(*) as total_members FROM ".SQL_PREFIX."members m
				{$query_bit_1}
				{$query_bit_2}
				{$query_bit_3}
				{$a['query']}";
	}
	
	# Changed, removed group stuff
	function mlist_get_members( $a )
	{
		if ( $a['query'] )
		{
			$a['query'] = 'WHERE '.$a['query'];
		}
		
		if ( $a['sort'] == 'pp_profile_views' )
		{
			$a['sort'] 	= 'pp.' . $a['sort'];
			$q_extra	= '';
		}
		else
		{
			$a['sort'] 	= 'm.' . $a['sort'];
		}
		
		return "SELECT m.*,me.*,p.*,pp.* 
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
						LEFT JOIN ".SQL_PREFIX."pfields_content p ON (p.member_id=m.id)
						LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)
					{$a['query']}
					ORDER BY {$a['sort']} {$a['order']}
					LIMIT {$a['limit_a']}, {$a['limit_b']}";
	}
	
	function buddy_posts_last_visit( $a )
	{
		return "SELECT COUNT(*) as posts 
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id and t.forum_id IN({$a['forum_string']}))		 
				 	WHERE p.queued=0 AND p.post_date > {$a['last_visit']}";
 	}
	
	function generic_get_all_member( $a )
	{
		return "SELECT g.*, m.*, me.*, p.*
					FROM ".SQL_PREFIX."members m
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
				 		LEFT JOIN ".SQL_PREFIX."profile_portal p ON (m.id=p.pp_member_id)
					WHERE m.id={$a['mid']}";
	}
	
	function moderate_get_topics( $a )
	{
		return "SELECT p.*,t.forum_id 
					FROM ".SQL_PREFIX."posts p 
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id) 
					WHERE pid IN (".implode(",", $a['pids']).")";
	}
	
	function moderate_concat_title( $a )
	{
		return "UPDATE ".SQL_PREFIX."topics 
					SET title=CONCAT('{$a['pre']}', title, '{$a['end']}') 
					WHERE tid IN(".implode( ",", $a['tids'] ).")";
	}
	
	# UPDATE 2.1
	function mod_func_get_last_post( $a )
	{
		return "SELECT p.post_date, p.topic_id, p.author_id, p.author_name, p.pid, t.forum_id, m.id, m.members_display_name
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
					WHERE topic_id={$a['tid']} and queued=0
					ORDER BY {$a['orderby']} DESC 
					LIMIT 0,1";
	}
	
	function mod_func_get_attach_count( $a )
	{
		return "SELECT COUNT(*) as count 
					FROM ".SQL_PREFIX."attachments a
			     		LEFT JOIN ".SQL_PREFIX."posts p on (p.pid=a.attach_rel_id)
			    	WHERE p.topic_id={$a['tid']} AND a.attach_rel_module='post'";
	
	}
	
	function mod_func_get_topic_tracker( $a )
	{
		return "SELECT tr.*, m.id, m.mgroup, m.org_perm_id, t.tid, t.forum_id, g.g_id, g.g_perm_id
				 	FROM ".SQL_PREFIX."tracker tr
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=tr.topic_id)
				 		LEFT JOIN ".SQL_PREFIX."members m on (m.id=tr.member_id)
				 		LEFT JOIN ".SQL_PREFIX."groups g on (g.g_id=m.mgroup)
					WHERE tr.topic_id".$a['tid'];
	}
	
	function search_get_all_user_count( $a )
	{
		return "SELECT count(*) as count
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
					WHERE t.approved=1 AND t.forum_id IN({$a['forums']}) AND p.queued=0 AND p.author_id={$a['mid']}";
	}
	
	function search_get_all_user_query( $a )
	{
		return "SELECT p.*, t.*, t.posts as topic_posts, t.title as topic_title, m.*, me.*, pp.*
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=p.author_id)
				 		LEFT JOIN ".SQL_PREFIX."profile_portal pp ON (m.id=pp.pp_member_id)
					WHERE t.approved=1 AND t.forum_id IN({$a['forums']}) AND p.queued=0 AND p.author_id={$a['mid']}
					ORDER BY post_date DESC";
	}
	
	function search_get_last_ten( $a )
	{
		return "SELECT p.*, t.*, t.posts as topic_posts, t.title as topic_title, m.*, me.*
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=p.author_id)
					WHERE p.queued=0 AND t.forum_id IN({$a['forums']}) AND p.author_id={$a['mid']}
					ORDER BY post_date DESC
					LIMIT 0,10";
	}
	
	
	function poll_get_poll_with_topic( $a )
	{
		return "SELECT f.allow_pollbump, t.*, p.pid as poll_id,p.choices,p.starter_id,p.votes
					FROM ".SQL_PREFIX."polls p
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.tid)
						LEFT JOIN ".SQL_PREFIX."forums f ON (f.id=t.forum_id)
					WHERE t.tid={$a['tid']}";
	}
	
	/**
	* contact_member_report_get_mods
	* Changed: 2.1.0.BETA4
	*	Added: m.members_disable_pm
	*
	*/
	function contact_member_report_get_mods( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.members_disable_pm, m.email, m.mgroup, moderator.member_id, moderator.group_id
					FROM ".SQL_PREFIX."moderators moderator
						LEFT JOIN ".SQL_PREFIX."members m ON (m.id=moderator.member_id OR m.mgroup=moderator.group_id)
					WHERE moderator.forum_id={$a['fid']}";
	}
	
	/**
	* contact_member_report_get_cpaccess
	* Changed: 2.1.0.BETA4
	*	Added: m.members_disable_pm
	*
	*/
	function contact_member_report_get_cpaccess( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.email, m.members_disable_pm 
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
					WHERE g.g_access_cp=1";
	}
	
	/**
	* contact_member_report_get_supmod
	* Changed: 2.1.0.BETA4
	*	Added: m.members_disable_pm
	*
	*/
	function contact_member_report_get_supmod( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.email, m.members_disable_pm 
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
					WHERE g.g_is_supmod=1";
	}
	
	
	
	function print_page_get_members( $a )
	{
		return "SELECT g.*, m.* 
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
					WHERE m.id IN ({$a['mem_ids']})";
	}
	
	function stats_who_posted( $a )
	{
		return "SELECT COUNT(p.pid) as pcount, p.author_id, m.members_display_name as author_name 
					FROM ".SQL_PREFIX."posts p
					LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
 					WHERE p.topic_id={$a['tid']} AND p.queued=0 
 					GROUP BY p.author_id 
 					ORDER BY pcount DESC";
	}
	
	function warn_get_data( $a )
	{
		return "SELECT l.*,  p.id as punisher_id, p.members_display_name as punisher_name
				 	FROM ".SQL_PREFIX."warn_logs l
				  		LEFT JOIN ".SQL_PREFIX."members p ON ( p.id=l.wlog_addedby )
					WHERE l.wlog_mid={$a['mid']} 
					ORDER BY l.wlog_date DESC 
					LIMIT {$a['limit_a']}, {$a['limit_b']}";
	}
	
	function warn_get_forum( $a )
	{
		return "SELECT t.tid, t.title, f.id, f.name 
					FROM ".SQL_PREFIX."topics t
						LEFT JOIN ".SQL_PREFIX."forums f ON (f.id=t.forum_id)
					WHERE t.tid={$a['tid']}";
	}
	
	function portal_get_poll_join( $a )
	{
		return "SELECT t.tid, t.title, t.state, t.last_vote, p.*, v.member_id as member_voted
					FROM ".SQL_PREFIX."topics t
						LEFT JOIN ".SQL_PREFIX."polls p ON (p.tid=t.tid)
						LEFT JOIN ".SQL_PREFIX."voters v ON (v.member_id={$a['mid']} and v.tid=t.tid)
					WHERE t.tid={$a['tid']}";
	}
	
	function portal_get_monster_bitch( $a )
	{
		return "SELECT t.*, p.*, me.avatar_location, m.view_avs, me.avatar_size, me.avatar_type,
				m.id as member_id, m.members_display_name as member_name, m.mgroup
					FROM ".SQL_PREFIX."topics t
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=t.starter_id)
				 		LEFT JOIN ".SQL_PREFIX."member_extra me on (me.id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."posts p ON (p.pid=t.topic_firstpost)
					WHERE t.forum_id IN (-1{$a['csite_article_forum']}) {$a['qe']}
					AND t.approved=1 AND (t.moved_to IS NULL or t.moved_to='')
					ORDER BY t.pinned DESC, t.start_date DESC
					LIMIT 0,{$a['limit']}";
	}
	
	function help_search( $a )
	{
		return "SELECT id, title, description
				 	FROM ".SQL_PREFIX."faq
					WHERE LOWER(title) LIKE '%{$a['search_string']}%' or LOWER(text) LIKE '%{$a['search_string']}%'
					ORDER BY title";
	}
	
	#-- NEW FOR RC1 --#
	
	function login_getmember( $a )
	{
		return "SELECT id, name, members_display_name, members_created_remote, email, mgroup, member_login_key, ip_address, login_anonymous
					FROM ".SQL_PREFIX."members
					WHERE members_l_username='{$a['username']}'";
	}
	
	
	
	function post_get_topic_review( $a )
	{
		return "SELECT p.*, m.members_display_name, m.mgroup
				 	FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
					WHERE topic_id={$a['tid']} and queued=0
					ORDER BY pid DESC
					LIMIT 0,10";
	}
	
	# REMOVED
	/*function post_forum_tracker_all( $a )
	{
		#post
		
		return "SELECT m.name, m.email, m.id, m.language, m.last_activity, m.org_perm_id, g.g_perm_id
				FROM ".SQL_PREFIX."members m, ".SQL_PREFIX."groups g
				WHERE m.mgroup IN ({$a['groups']})
				AND m.mgroup=g.g_id
				AND m.id <> {$a['mid']}
				AND m.allow_admin_mails=1
				AND m.last_activity < {$a['last_post']}";
	}*/
	
    
} // end class


?>