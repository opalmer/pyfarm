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
|   > $Date: 2006-05-24 17:31:43 -0400 (Wed, 24 May 2006) $
|   > $Revision: 276 $
|   > $Author: bfarber $
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

class sql_api_queries extends db_driver_mysql
{

     var $db  = "";
     var $tbl = "";
     
    /*========================================================================*/
    // Set up...             
    /*========================================================================*/  
                   
    function sql_api_queries( &$obj )
    {
    	$this->db = &$obj;
    	
    	if ( ! isset($this->db->obj['sql_tbl_prefix']) )
    	{
    		$this->db->obj['sql_tbl_prefix'] = SQL_PREFIX;
    	}
    	
    	$this->tbl = $this->db->obj['sql_tbl_prefix'];
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
	
	function cache_templates_bits( $a )
	{
		return "SELECT *,
				 INSTR(',{$a['id']}{$a['parent_in']},{$a['root']},' , CONCAT(',',set_id,',') ) as theorder
					FROM ".SQL_PREFIX."skin_templates 
					WHERE set_id IN ({$a['id']}{$a['parent_in']},{$a['root']}) AND group_name='{$a['group']}' 
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
	
}
	
?>