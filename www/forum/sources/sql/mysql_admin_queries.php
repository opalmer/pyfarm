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
|   > $Date: 2007-03-01 11:52:31 -0500 (Thu, 01 Mar 2007) $
|   > $Revision: 866 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > MySQL ADMIN DB Queries abstraction module
|   > Module written by Matt Mecham
|   > Date started: 24th May 2004
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
    
    # 2.2 - Allow admin to specify display name
    function general_get_by_display_name( $a )
    {
    	return "SELECT id, members_display_name, name, email, mgroup, member_login_key, ip_address, login_anonymous
					FROM ".SQL_PREFIX."members
					WHERE members_l_display_name='{$a['members_l_display_name']}'";
    }
    
    function calendar_get_events_cache( $a )
    {
	    return "SELECT * 
	    			FROM ".SQL_PREFIX."cal_events WHERE {$a['extra']} event_approved=1
	    			AND ( (event_unix_to >= {$a['timenow']} AND event_unix_from <= {$a['timethen']} )
	    			OR ( event_unix_to=0 AND event_unix_from >= {$a['timenow']} AND event_unix_from <= {$a['timethen']} )
	    			OR ( event_recurring=3 AND FROM_UNIXTIME(event_unix_from,'%c')={$a['month']} AND event_unix_to <= {$a['timethen']} ) )";
    }
    
    # IPB 2.1 BETA 2: NEW NEW NEW!
	function acpperms_get_main_groups( $a )
	{
		return "SELECT perm_key, perm_main, perm_child 
					FROM ".SQL_PREFIX."admin_permission_keys 
					WHERE perm_main='{$a['perm_main']}' 
					GROUP BY perm_child";
	}
    
    # IPB 2.1 BETA 2: NEW NEW NEW!
	function login_getmember_by_dname( $a )
	{
		return "SELECT m.*, g.*
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
					WHERE members_l_display_name='{$a['username']}'";
	}
	
    function admin_emo_count( $a )
    {
    	return "SELECT id, count(id) as count, emo_set 
    				FROM ".SQL_PREFIX."emoticons 
    				GROUP BY emo_set 
    				ORDER BY emo_set";
    }
    
    function adminlogs_view_one( $a )
    {
    	return "SELECT m.*, mem.id, mem.name, mem.members_display_name 
    				FROM ".SQL_PREFIX."admin_logs m
    					LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=m.member_id)
					WHERE m.member_id={$a['mid']} 
					ORDER BY m.ctime DESC 
					LIMIT {$a['limit_a']}, 20";
    }
    
    function adminlogs_view_two( $a )
    {
    	return "SELECT m.*, mem.id, mem.name, mem.members_display_name 
    				FROM ".SQL_PREFIX."admin_logs m
    					LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=m.member_id)
				 	WHERE {$a['dbq']} 
				 	ORDER BY m.ctime DESC 
				 	LIMIT {$a['limit_a']}, 20";
    }
    
    function adminlogs_view_list_current( $a )
    {
    	return "SELECT m.*, mem.id, mem.members_display_name as name 
    				FROM ".SQL_PREFIX."admin_logs m
    					LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=m.member_id)
				 	ORDER BY m.ctime DESC 
				 	LIMIT 0, 5";
    }
    
    function adminlogs_view_list_current_two( $a )
    {
    	return "SELECT m.*, mem.members_display_name as name, count(m.id) as act_count
					FROM ".SQL_PREFIX."admin_logs m
						LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=m.member_id)
					GROUP BY m.member_id 
					ORDER BY act_count DESC";
    }
	
	
	
	function attachments_bulk_remove( $a )
	{
		return "SELECT a.*, p.pid, p.topic_id
					FROM ".SQL_PREFIX."attachments a
				 		LEFT JOIN ".SQL_PREFIX."posts p ON (p.pid=a.attach_rel_id AND attach_rel_module='post')
					WHERE a.attach_rel_id > 0 AND a.attach_id IN(".implode(",",$a['ids']).")";
	}
	
	
	function emaillogs_view_email( $a )
	{	
		return "SELECT email.*, m.id, m.members_display_name as name, mem.id as to_id, mem.members_display_name as to_name 
					FROM ".SQL_PREFIX."email_logs email
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=email.from_member_id)
				 		LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=email.to_member_id)
					WHERE email.email_id={$a['id']}";
	}
	
	function emaillogs_list_current( $a )
	{	
		return "SELECT email.*, m.id, m.members_display_name as name, mem.id as to_id, mem.members_display_name as to_name 
					FROM ".SQL_PREFIX."email_logs email
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=email.from_member_id)
				 		LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=email.to_member_id) {$a['dbe']}
					ORDER BY email_date DESC 
					LIMIT {$a['limit_a']},25";
	}
	
	function groups_permsplash( $a )
	{
		return "SELECT COUNT(id) as count, org_perm_id 
					FROM ".SQL_PREFIX."members 
					WHERE (org_perm_id IS NOT NULL AND org_perm_id != '') 
					GROUP by org_perm_id";
	}
	
	# Changed [ 2.1 ] Added OR m.mgroup_others LIKE CONCAT('%,',g.g_id,',%')
	function groups_main_screen( $a )
	{
		return "SELECT g.g_id, g.g_access_cp, g.g_is_supmod, g.g_title,g.prefix, g.suffix,
				  COUNT(m.id) as count
				 	FROM ".SQL_PREFIX."groups as g
		         		LEFT JOIN ".SQL_PREFIX."members m ON (m.mgroup = g.g_id OR m.mgroup_others LIKE CONCAT('%,',g.g_id,',%'))
		         	GROUP BY g.g_id 
		         	ORDER BY g.g_title";
	}
	
	function index_admin_logs( $a )
	{
		return "SELECT m.*, mem.id, mem.name, mem.members_display_name 
					FROM ".SQL_PREFIX."admin_logs m
						LEFT JOIN ".SQL_PREFIX."members mem ON (mem.id=m.member_id)
						WHERE m.member_id=mem.id 
						ORDER BY m.ctime DESC 
						LIMIT 0, 5";
	}
	
	function languages_list_current( $a )
	{
		return "SELECT l.*, COUNT(m.id) as mcount 
					FROM ".SQL_PREFIX."languages l
						LEFT JOIN ".SQL_PREFIX."members m ON (m.language=l.ldir)
					WHERE (m.language IS NOT NULL OR m.language = 'en')
					GROUP BY l.ldir
					ORDER BY l.lname";
	}
	
	function member_tools_learn_ip_one( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.email, m.posts, m.joined, p.pid, p.author_id, p.post_date, p.ip_address, p.topic_id
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.author_id)
					WHERE p.ip_address{$a['query']} 
					GROUP BY p.author_id 
					ORDER BY p.post_date DESC 
					LIMIT 250";
	}
	
	function member_tools_learn_ip_two( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.email, m.posts, m.joined, p.vote_date, p.ip_address, p.tid
					FROM ".SQL_PREFIX."voters p
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.member_id)
					WHERE p.ip_address{$a['query']} 
					GROUP BY p.member_id 
					ORDER BY p.vote_date DESC 
					LIMIT 250";
	}
	
	function member_tools_learn_ip_three( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.email, m.posts, m.joined, p.email_date, p.from_ip_address
					FROM ".SQL_PREFIX."email_logs p
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.from_member_id)
					WHERE p.from_ip_address{$a['query']} 
					GROUP BY p.from_member_id 
					ORDER BY p.email_date DESC 
					LIMIT 250";
	}
	
	function member_tools_learn_ip_four( $a )
	{
		return "SELECT m.id, m.members_display_name as name, m.email, m.posts, m.joined, p.entry_date, p.ip_address
					FROM ".SQL_PREFIX."validating p
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=p.member_id)
					WHERE p.ip_address{$a['query']} 
					GROUP BY p.member_id 
					ORDER BY p.entry_date DESC 
					LIMIT 250";
	}
	
	function member_tools_show_ips( $a )
	{
		return "SELECT count(ip_address) as ip, ip_address, pid, topic_id, post_date
					FROM ".SQL_PREFIX."posts
					WHERE author_id={$a['mid']}
					GROUP BY ip_address
					ORDER BY post_date DESC 
					LIMIT {$a['st']}, {$a['end']}";
	}
	
	function member_domod( $a )
	{
		return "SELECT m.id, m.name, m.members_display_name, m.email, m.mgroup, v.* 
					FROM ".SQL_PREFIX."validating v
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=v.member_id)
					WHERE m.id IN(".implode( ",",$a['ids'] ).")";
	}
	

	function member_search_form_one( $a )
	{
		return "SELECT m.*, me.*, m.id as memid, pp.*
					FROM ".SQL_PREFIX."members m
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."pfields_content p ON (p.member_id=m.id)
						LEFT JOIN ".SQL_PREFIX."profile_portal pp on (pp.pp_member_id=m.id)
					{$a['rq']} 
					ORDER BY m.members_l_display_name 
					LIMIT {$a['st']},25";
	}
	
	function member_search_form_two( $a )
	{
		return "SELECT m.*, me.*, m.id as memid, pp.*
					FROM ".SQL_PREFIX."members m
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."pfields_content p ON (p.member_id=m.id)
						LEFT JOIN ".SQL_PREFIX."profile_portal pp on (pp.pp_member_id=m.id)
					{$a['rq']}";
	}
	
	function member_search_form_count( $a )
	{
		return "SELECT COUNT(*) as count
					FROM ".SQL_PREFIX."members m
				 		LEFT JOIN ".SQL_PREFIX."member_extra me ON (me.id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."pfields_content p ON (p.member_id=m.id)
			   		{$a['rq']}";
	}
	
	# EDITED IPB 2.2: BETA 2
	function member_search_do_edit_form( $a )
	{
		return "SELECT m.*, me.*, p.*, pp.*, m.id as memid, g.g_access_cp 
					FROM ".SQL_PREFIX."members m
				 		LEFT JOIN ".SQL_PREFIX."member_extra me on (me.id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."pfields_content p on (p.member_id=m.id)
				 		LEFT JOIN ".SQL_PREFIX."groups g on (g.g_id=m.mgroup)
						LEFT JOIN ".SQL_PREFIX."profile_portal pp on (pp.pp_member_id=m.id)
					WHERE m.id=".$a['mid'];
	}
	
	function modlogs_view_one( $a )
	{
		return "SELECT mem.members_display_name, m.*, f.id as forum_id, f.name 
					FROM ".SQL_PREFIX."moderator_logs m
				  		LEFT JOIN ".SQL_PREFIX."members mem ON(mem.id=m.member_id)
				  		LEFT JOIN ".SQL_PREFIX."forums f ON(f.id=m.forum_id)
					WHERE m.member_id={$a['mid']} 
					ORDER BY m.ctime DESC 
					LIMIT {$a['start']}, 20";
	}
	
	function modlogs_view_two( $a )
	{
		return "SELECT mem.members_display_name, m.*, f.id as forum_id, f.name 
					FROM ".SQL_PREFIX."moderator_logs m
						LEFT JOIN ".SQL_PREFIX."members mem ON(mem.id=m.member_id)
						LEFT JOIN ".SQL_PREFIX."forums f ON(f.id=m.forum_id)
					WHERE {$a['dbq']} 
					ORDER BY m.ctime DESC 
					LIMIT {$a['start']}, 20";
	}
	
	function modlogs_view_three( $a )
	{
		return "SELECT mem.members_display_name, m.*, f.id as forum_id, f.name 
					FROM ".SQL_PREFIX."moderator_logs m
				  		LEFT JOIN ".SQL_PREFIX."members mem ON(mem.id=m.member_id)
				  		LEFT JOIN ".SQL_PREFIX."forums f ON(f.id=m.forum_id)
					ORDER BY m.ctime DESC 
					LIMIT {$a['start']}, 20";
	}	
	
	function modlogs_list_current_last_five( $a )
	{
		return "SELECT mem.members_display_name, m.*, f.id as forum_id, f.name 
					FROM ".SQL_PREFIX."moderator_logs m
						LEFT JOIN ".SQL_PREFIX."members mem ON(mem.id=m.member_id)
		            	LEFT JOIN ".SQL_PREFIX."forums f ON (f.id=m.forum_id)
		            ORDER BY m.ctime DESC 
		            LIMIT 0, 5";
	}
	
	function modlogs_list_current_show_all( $a )
	{
		return "SELECT mem.members_display_name, m.*, count(m.id) as act_count
					FROM ".SQL_PREFIX."moderator_logs m
						LEFT JOIN ".SQL_PREFIX."members mem ON(mem.id=m.member_id)
					GROUP BY m.member_id
					ORDER BY act_count DESC";
	}
	
	function spiderlogs_list_current( $a )
	{
		return "SELECT count(*) as cnt, bot, max(entry_date) as entry_date, query_string 
					FROM ".SQL_PREFIX."spider_logs 
					GROUP BY bot 
					ORDER BY entry_date DESC";
	}
	
	function statistics_show_views( $a )
	{
		return "SELECT SUM(t.views) as result_count, t.forum_id, f.name as result_name
					FROM ".SQL_PREFIX."topics t
						LEFT JOIN ".SQL_PREFIX."forums f ON (f.id=t.forum_id)
					WHERE t.start_date > '{$a['from_time']}'
					AND t.start_date < '{$a['to_time']}'
					GROUP BY t.forum_id
					ORDER BY result_count {$a['sortby']}";
	}
	
	function statistics_result_screen( $a )
	{
		return "SELECT MAX({$a['sql_field']}) as result_maxdate,
				 COUNT(*) as result_count,
				 DATE_FORMAT(from_unixtime({$a['sql_field']}),'{$a['sql_date']}') AS result_time
				 	FROM ".SQL_PREFIX."{$a['sql_table']}
				 	WHERE {$a['sql_field']} > '{$a['from_time']}'
				 	AND {$a['sql_field']} < '{$a['to_time']}'
				 	GROUP BY result_time
				 	ORDER BY {$a['sql_field']} {$a['sortby']}";
	}
	
	function stylesets_list_sets_templates( $a )
	{
		return "SELECT set_id 
					FROM ".SQL_PREFIX."skin_templates 
					GROUP BY set_id";
	}
	
	function stylesets_list_sets_macros( $a )
	{
		return "SELECT macro_set 
					FROM ".SQL_PREFIX."skin_macro 
					GROUP BY macro_set";
	}
	
	function stylesheets_do_form_concat( $a )
	{
		return "SELECT *, instr(',{$a['id']}{$a['parent']},1,', concat(',',set_skin_set_id,',') ) as theorder
				 	FROM ".SQL_PREFIX."skin_sets
				 	WHERE set_skin_set_id in ({$a['id']}{$a['parent']},1)
				 	ORDER BY theorder";
	}
	
	function warnlogs_view_note( $a )
	{
		return "SELECT l.*, m.id as a_id, m.members_display_name as a_name, p.id as p_id, p.members_display_name as p_name
					FROM ".SQL_PREFIX."warn_logs l
				  		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=l.wlog_mid)
				  		LEFT JOIN ".SQL_PREFIX."members p ON (p.id=l.wlog_addedby)
					WHERE l.wlog_id={$a['id']}";
	}
	
	function warnlogs_view( $a )
	{
		return "SELECT l.*, m.id as a_id, m.members_display_name as a_name, p.id as p_id, p.members_display_name as p_name
					FROM ".SQL_PREFIX."warn_logs l
				  		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=l.wlog_mid)
				  		LEFT JOIN ".SQL_PREFIX."members p ON (p.id=l.wlog_addedby)
					WHERE l.wlog_mid={$a['mid']}
					ORDER BY l.wlog_date DESC 
					LIMIT {$a['start']},30";
	}
	
	function warnlogs_view_two( $a )
	{
		return "SELECT l.*, m.id as a_id, m.members_display_name as a_name, p.id as p_id, p.members_display_name as p_name
					FROM ".SQL_PREFIX."warn_logs l
				  		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=l.wlog_mid)
				  		LEFT JOIN ".SQL_PREFIX."members p ON (p.id=l.wlog_addedby)
					WHERE {$a['dbq']}
					ORDER BY l.wlog_date DESC 
					LIMIT {$a['start']},30";
	}
	
	function warnlogs_list_current( $a )
	{
		return "SELECT l.*, m.id as a_id, m.members_display_name as a_name, p.id as p_id, p.members_display_name as p_name
					FROM ".SQL_PREFIX."warn_logs l
						LEFT JOIN ".SQL_PREFIX."members m ON (m.id=l.wlog_mid)
						LEFT JOIN ".SQL_PREFIX."members p ON (p.id=l.wlog_addedby)
			   		ORDER BY l.wlog_date DESC 
			   		LIMIT 0,10";
	}
	
	function warnlogs_list_current_two( $a )
	{
		return "SELECT l.*, m.name, m.members_display_name, count(l.wlog_mid) as act_count
					FROM ".SQL_PREFIX."warn_logs l
						LEFT JOIN ".SQL_PREFIX."members m ON (m.id=l.wlog_mid)
					GROUP BY l.wlog_mid
					ORDER BY act_count DESC";
	}
	
	
	function cache_wrapper( $a )
	{
		return "SELECT *, instr(',{$a['id']}{$a['parent_in']},{$a['root']},', concat(',',set_skin_set_id,',') ) as theorder
					FROM ".SQL_PREFIX."skin_sets
					WHERE set_wrapper != '' AND set_skin_set_id in ({$a['id']}{$a['parent_in']},{$a['root']})
					ORDER BY theorder
					LIMIT 0,1";
	}
	
	function cache_templates_titles( $a )
	{
		return "SELECT group_name, set_id, suid, func_name, func_data,
				 INSTR(',{$a['id']}{$a['parent_in']},{$a['root']},' , CONCAT(',',set_id,',') ) as theorder
					FROM ".SQL_PREFIX."skin_templates 
					WHERE set_id IN ({$a['id']}{$a['parent_in']},{$a['root']}) 
					ORDER BY group_name, theorder DESC";
	}
	
	# CHANGED IPB 2.2.3
	function cache_templates_bits( $a )
	{
		return "SELECT *,
				 INSTR(',{$a['id']}{$a['parent_in']},{$a['root']},' , CONCAT(',',set_id,',') ) as theorder
					FROM ".SQL_PREFIX."skin_templates 
					WHERE set_id IN ({$a['id']}{$a['parent_in']},{$a['root']})
					AND ( group_name='{$a['group']}' OR group_names_secondary LIKE '%,{$a['group']},%' )
					ORDER BY func_name,theorder DESC";
	}
	
	function cache_templates_all( $a )
	{
		return "SELECT *,
				 INSTR(',{$a['id']}{$a['parent_in']},{$a['root']},' , CONCAT(',',set_id,',') ) as theorder
					FROM ".SQL_PREFIX."skin_templates 
					WHERE set_id IN ({$a['id']}{$a['parent_in']},{$a['root']}) 
					ORDER BY group_name, func_name, theorder DESC";
	}
	
	function cache_templates_css( $a )
	{
		return "SELECT *, instr(',{$a['id']}{$a['parent_in']},{$a['root']},', concat(',',set_skin_set_id,',') ) as theorder
					FROM ".SQL_PREFIX."skin_sets
					WHERE set_css != '' AND set_skin_set_id in ({$a['id']}{$a['parent_in']},{$a['root']})
					ORDER BY theorder
					LIMIT 0,1";
	}
	
	function cache_templates_macros( $a )
	{
		return "SELECT *,
				 INSTR(',{$a['id']}{$a['parent_in']},{$a['root']},' , CONCAT(',',macro_set,',') ) as theorder
					FROM ".SQL_PREFIX."skin_macro
					WHERE macro_set IN ({$a['id']}{$a['parent_in']},{$a['root']})
					ORDER BY macro_value ASC,theorder DESC";
	}
	
	function cache_empty_css( $a )
	{
		return "SELECT * 
					FROM ".SQL_PREFIX."skin_sets 
					WHERE set_css like '' AND set_skin_set_parent={$a['parent_id']}";
	}
	
	function cache_empty_wrapper( $a )
	{
		return "SELECT * 
					FROM ".SQL_PREFIX."skin_sets 
					WHERE set_wrapper like '' AND set_skin_set_parent={$a['parent_id']}";
	}
	
	function mod_func_get_last_post( $a )
	{
		return "SELECT p.post_date, p.topic_id, p.author_id, p.author_name, p.pid, t.forum_id
					FROM ".SQL_PREFIX."posts p
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=p.topic_id)
					WHERE topic_id={$a['tid']} and queued <> 1
					ORDER BY pid DESC LIMIT 0,1";
	}
	
	function mod_func_get_attach_count( $a )
	{
		return "SELECT COUNT(*) as count 
					FROM ".SQL_PREFIX."attachments a
			     		LEFT JOIN ".SQL_PREFIX."posts p on (p.pid=a.attach_rel_id AND a.attach_rel_module='post')
			    	WHERE p.topic_id={$a['tid']}";
	
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
	
	function ucp_get_all_announcements( $a )
	{
		return "SELECT a.*, m.id, m.name, m.members_display_name
				 	FROM ".SQL_PREFIX."announcements a
				  		LEFT JOIN ".SQL_PREFIX."members m on (m.id=a.announce_member_id)
				 	ORDER BY announce_end DESC";
	}
	
	function ucp_get_all_announcements_byid( $a )
	{
		return "SELECT a.*, m.*, me.*
				 	FROM ".SQL_PREFIX."announcements a
				  		LEFT JOIN ".SQL_PREFIX."members m on (m.id=a.announce_member_id)
				  		LEFT JOIN ".SQL_PREFIX."member_extra me on (me.id=m.id)
				 	WHERE a.announce_id={$a['id']}";
	}
	
	function ucp_tracker_prune( $a )
	{
		return "SELECT tr.trid 
					FROM ".SQL_PREFIX."tracker tr
						LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=tr.topic_id) 
					WHERE t.last_post < {$a['time']}";
	}
	
	function login_getmember( $a )
	{
		return "SELECT m.*, g.*
					FROM ".SQL_PREFIX."members m
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=m.mgroup)
					WHERE members_l_username='{$a['username']}'";
	}
	
	function settings_search( $a )
	{
		return "SELECT c.*, ct.conf_title_noshow, ct.conf_title_title
					FROM ".SQL_PREFIX."conf_settings c
				 		LEFT JOIN ".SQL_PREFIX."conf_settings_titles ct ON (ct.conf_title_id=c.conf_group)
					WHERE LOWER(conf_title) LIKE '%{$a['keywords']}%' OR LOWER(conf_description) LIKE '%{$a['keywords']}%'
					ORDER BY conf_title
					LIMIT {$a['limit_a']}, {$a['limit_b']}";
	}
	
    
} // end class


?>