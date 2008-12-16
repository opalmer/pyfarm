<?php

/*
+--------------------------------------------------------------------------
|   INVISION POWER BOARD INSTALLER v2.1
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
+--------------------------------------------------------------------------
|
|   > Script written by Matthew Mecham
|   > Date started: 12th August 2004
|   > MYSQL EXTRA CONFIG / INSTALL FILE
+--------------------------------------------------------------------------
*/

class install_extra
{
	var $errors     = array();
	var $info_extra = array();
	var $ipsclass   = "";
	
	function install_extra()
	{
	
	}
	
	/*-------------------------------------------------------------------------*/
	// process_query_create: Alter the query before it goes back $DB->query
	// table prefix already changed at this point: CREATE TABLE
	/*-------------------------------------------------------------------------*/
	
	function process_query_create( $query )
	{
		//-----------------------------------------
		// Tack on the end the chosen table type
		//-----------------------------------------
		
		$table_type = $this->ipsclass->vars['mysql_tbl_type'];
		
		return preg_replace( "#\);$#", ") TYPE=".$table_type.";", $query );
	}
	
	/*-------------------------------------------------------------------------*/
	// process_query_index: Alter the query before it goes back $DB->query
	// table prefix already changed at this point: INDEX
	/*-------------------------------------------------------------------------*/
	
	function process_query_index( $query )
	{
		return $query;
	}
	
	/*-------------------------------------------------------------------------*/
	// process_query_index: Alter the query before it goes back $DB->query
	// table prefix already changed at this point: INSERT
	/*-------------------------------------------------------------------------*/
	
	function process_query_insert( $query )
	{
		return $query;
	}
	
	/*-------------------------------------------------------------------------*/
	// WHEN SHOWING THE FORM....
	/*-------------------------------------------------------------------------*/
	
	function install_form_extra()
	{
		$extra = "<tr>
					<td class='title'><b>MySQL Table Type</b><div style='color:gray'>Use MyISAM if unsure</div></td>
					<td class='content'><select name='mysql_tbl_type' class='sql_form'><option value='MyISAM'>MYISAM</option><option value='INNODB'>INNODB</option></td>
				  </tr>";
	
		return $extra;
	
	}
	
	/*-------------------------------------------------------------------------*/
	// WHEN SAVING TO CONF GLOBAL
	// Return errors in $errors[]
	/*-------------------------------------------------------------------------*/
	
	function install_form_process()
	{
		//-----------------------------------------
		// When processed, return all vars to save
		// in conf_global in the array $this->info_extra
		// This will also be saved into $INFO[] for
		// the installer
		//-----------------------------------------
		
		if ( ! $_REQUEST['mysql_tbl_type'] )
		{
			$this->errors[] = 'You must complete the required SQL section!';
			return;
		}
		
		$this->info_extra['mysql_tbl_type'] = $_REQUEST['mysql_tbl_type'];
	}

}

?>