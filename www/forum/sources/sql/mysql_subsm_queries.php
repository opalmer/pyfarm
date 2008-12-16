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
|   > $Date: 2007-07-31 17:51:44 -0400 (Tue, 31 Jul 2007) $
|   > $Revision: 1094 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > MySQL DB Queries abstraction module
|   > Module written by Matt Mecham
|   > Date started: 12th August 2004
|   > SUBSCRIPTIONS MANAGER
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class sql_subsm_queries extends db_driver_mysql
{

     var $db  = "";
     var $tbl = "";
     
    /*========================================================================*/
    // Set up...             
    /*========================================================================*/  
                   
    function sql_subsm_queries( &$obj )
    {
    	$this->db = &$obj;
    	
    	if ( ! isset($this->db->obj['sql_tbl_prefix']) )
    	{
    		$this->db->obj['sql_tbl_prefix'] = SQL_PREFIX;
    	}
    	
    	$this->tbl = $this->db->obj['sql_tbl_prefix'];
    }
    
    /*========================================================================*/
    
    
	
    function intro_get_all( $a )
    {
    	return "SELECT COUNT(*) as total, SUM(subtrans_cumulative) as revenue, subtrans_method
		         	FROM ".SQL_PREFIX."subscription_trans 
		         	WHERE subtrans_state NOT IN ('failed', 'dead', 'pending')
		        	GROUP BY subtrans_method";
    }
    
    function intro_get_failed_dead( $a )
    {
    	return "SELECT COUNT(*) as total, SUM(subtrans_cumulative) as revenue, subtrans_method
				  	FROM ".SQL_PREFIX."subscription_trans 
				  	WHERE subtrans_state IN ('failed', 'dead')
					GROUP BY subtrans_method";
    }
    
    function intro_get_failed_pending( $a )
    {
    	return "SELECT COUNT(*) as total, SUM(subtrans_cumulative) as revenue, subtrans_method
				  	FROM ".SQL_PREFIX."subscription_trans 
				  	WHERE subtrans_state='pending'
					GROUP BY subtrans_method";
    }
    
    function intro_plans_a( $a )
    {
    	return "SELECT COUNT(*) as total, subtrans_sub_id
				  	FROM ".SQL_PREFIX."subscription_trans
				  	WHERE subtrans_end_date < {$a['time']} OR subtrans_state = 'expired'
					GROUP BY subtrans_sub_id";
    }
    
    function intro_plans_b( $a )
    {
    	return "SELECT COUNT(*) as total, subtrans_sub_id
				  	FROM ".SQL_PREFIX."subscription_trans
				  	WHERE subtrans_end_date > {$a['time']} AND subtrans_state != 'expired'
					GROUP BY subtrans_sub_id";
    }
    
    function do_search( $a )
    {
    	return "SELECT s.*, m.id, m.members_display_name as name, m.email, ss.* 
    				FROM ".SQL_PREFIX."subscription_trans s
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=s.subtrans_member_id)
				 		LEFT JOIN ".SQL_PREFIX."subscriptions ss ON (ss.sub_id=s.subtrans_sub_id) ".$a['query']."
				 	ORDER BY s.subtrans_end_date ASC 
				 	LIMIT {$a['st']}, {$a['end']}";
				 
    }
    
    function do_search_two( $a )
    {
    	return "SELECT * 
    				FROM ".SQL_PREFIX."subscription_logs ".$a['query']." 
    				ORDER BY sublog_date DESC LIMIT {$a['st']}, {$a['end']}";
    }
   
   	function get_lower_name( $a )
   	{
   		return "SELECT * 
   					FROM ".SQL_PREFIX."members 
   					WHERE members_l_username='{$a['name']}' OR members_l_display_name='{$a['name']}'";
   	}
   	
   	function get_lower_like( $a )
   	{
   		return "SELECT * 
   					FROM ".SQL_PREFIX."members 
   					WHERE members_l_username LIKE '%{$a['name']}%' OR members_l_display_name LIKE '%{$a['name']}%'";
   	}
   	
   	function edit_trans( $a )
   	{
   		return "SELECT s.*, ss.*, m.members_display_name as name, m.id 
   					FROM ".SQL_PREFIX."subscription_trans s
				 		LEFT JOIN ".SQL_PREFIX."subscriptions ss ON (ss.sub_id=s.subtrans_sub_id)
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=s.subtrans_member_id)
					WHERE s.subtrans_id={$a['id']}";
   	}
   	
   	function delete_trans( $a )
   	{
   		return "SELECT s.*, ss.*, m.* 
   					FROM ".SQL_PREFIX."subscription_trans s
				 		LEFT JOIN ".SQL_PREFIX."subscriptions ss ON ( ss.sub_id=s.subtrans_sub_id )
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=s.subtrans_member_id)
					WHERE s.subtrans_id IN (".implode(",", $a['ids']).")";
   	}
    
    function unsub_members( $a )
    {
    	return "SELECT s.*, ss.*, m.* 
    				FROM ".SQL_PREFIX."subscription_trans s
				 		LEFT JOIN ".SQL_PREFIX."subscriptions ss ON ( ss.sub_id=s.subtrans_sub_id )
				 		LEFT JOIN ".SQL_PREFIX."members m ON (m.id=s.subtrans_member_id)
					WHERE s.subtrans_sub_id={$a['id']}".$a['qe'];
    }
	
	/*
    	$this->ipsclass->DB->cache_add_query( 'intro_get_all', array(), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
	*/
	
	function mod_custom( $a )
	{
		return "SELECT s.*, ss.* 
					FROM ".SQL_PREFIX."subscription_methods s
				 		LEFT JOIN ".SQL_PREFIX."subscription_extra ss ON ( ss.subextra_method_id=s.submethod_id )
					WHERE submethod_name='{$a['type']}'";
	}
	
	function mod_payment_method( $a )
	{
		return "SELECT s.*, ss.* 
					FROM ".SQL_PREFIX."subscription_methods s
				 		LEFT JOIN ".SQL_PREFIX."subscription_extra ss ON ( ss.subextra_method_id=s.submethod_id )
					WHERE s.submethod_active=1";
	}
	
	function mod_do_index( $a )
	{
		return "SELECT s.submethod_id, ss.subextra_id, s.submethod_active, ss.subextra_can_upgrade 
					FROM ".SQL_PREFIX."subscription_methods s
				 		LEFT JOIN ".SQL_PREFIX."subscription_extra ss ON ( ss.subextra_method_id=s.submethod_id )
					WHERE s.submethod_active=1";
	}
	
	function mod_failed_member( $a )
	{
		return "SELECT t.*, g.g_id 
					FROM ".SQL_PREFIX."subscription_trans t
						LEFT JOIN ".SQL_PREFIX."groups g ON (g.g_id=t.subtrans_old_group)
					WHERE subtrans_id={$a['cur_trx_id']}";
	}
    
} // end class


?>