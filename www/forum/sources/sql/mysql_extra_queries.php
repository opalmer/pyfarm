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
|   > $Date: 2006-11-20 14:46:36 -0500 (Mon, 20 Nov 2006) $
|   > $Revision: 736 $
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

class sql_extra_queries extends db_driver_mysql
{

     var $db  = "";
     var $tbl = "";
     
    /*========================================================================*/
    // Set up...             
    /*========================================================================*/  
                   
    function sql_extra_queries( &$obj )
    {
    	$this->db = &$obj;
    	
    	if ( ! isset($this->db->obj['sql_tbl_prefix']) )
    	{
    		$this->db->obj['sql_tbl_prefix'] = SQL_PREFIX;
    	}
    	
    	$this->tbl = $this->db->obj['sql_tbl_prefix'];
    }
    
    /*========================================================================*/
    
	// ---- Added in v2.1 ---- //
    
    function update_profile_views_get( $a )
    {
    	return "SELECT views_member_id, COUNT(*) as profile_views
    		   		FROM ".SQL_PREFIX."profile_portal_views
    		   		GROUP BY views_member_id";
    }

	// ---- Added in v2.2 ---- //

	function diag_distinct_skins( )
    {
	    return "SELECT DISTINCT(group_name) 
	    			FROM ".SQL_PREFIX."skin_templates 
	    			GROUP BY group_name";
    }

    // ---- Added in v2.1 ---- //
    
    function member_display_name_lookup( $a )
    {
    	return "SELECT members_display_name, name, id
    				FROM ".SQL_PREFIX."members
    				WHERE LOWER({$a['field']}) LIKE '{$a['name']}%'
    				ORDER BY LENGTH({$a['field']}) ASC
    				LIMIT 0,15";
    }
    
    // ---- Added in v2.1 ---- //
    
    function updateviews_get( $a )
    {
    	return "SELECT views_tid, COUNT(*) as topicviews
    		   		FROM ".SQL_PREFIX."topic_views
    		   		GROUP BY views_tid";
    }
    
    
    // ---- 2.0 Existing ----- //
    
    function digest_get_topics( $a )
    {
    	return "SELECT tr.trid, tr.topic_id, tr.member_id as trmid, m.members_display_name, m.email, m.id, m.email_full, m.language, m.last_activity, t.title, t.*
					FROM ".SQL_PREFIX."tracker tr
				 		LEFT JOIN ".SQL_PREFIX."topics t ON (t.tid=tr.topic_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=tr.member_id)
					WHERE tr.topic_track_type='{$a['type']}' AND t.approved=1
					AND t.last_post > {$a['last_time']}";
    }
    
    function digest_get_forums_topics( $a )
    {
    	return "SELECT *
			     	FROM ".SQL_PREFIX."topics
			     	WHERE forum_id={$a['forum_id']}
			      	AND last_post > {$a['last_time']}";
    }
    
    function digest_get_forums_topics_posts( $a )
    {
	    return "SELECT * FROM ".SQL_PREFIX."posts WHERE topic_id={$a['tid']} ORDER BY post_date DESC LIMIT 1";
    }
    
    function digest_get_forums( $a )
    {
    	return "SELECT ft.*, m.members_display_name, m.id, m.email, m.language
    			 	FROM ".SQL_PREFIX."forum_tracker ft
    			 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=ft.member_id)
    			 	WHERE ft.forum_track_type='{$a['type']}'";
    }
    
    function acp_postoffice_concat_bit($a)
    {
    	return "CONCAT(',',mgroup_others,',') LIKE '%,{$a['gid']},%'";
    }
    
	
    
} // end class


?>