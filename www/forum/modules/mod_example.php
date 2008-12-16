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
|   > $Date: 2005-10-10 14:03:20 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 22 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > MODULE FILE (EXAMPLE)
|   > Module written by Matt Mecham
|   > Date started: Thu 14th April 2005 (17:59)
|
+--------------------------------------------------------------------------
*/

//=====================================
// Define class, this must be the same
// in all modules
//=====================================

class module
{
	//=====================================
	// Define vars if required
	//=====================================
	
	var $ipsclass;
	var $class  = "";
	var $module = "";
	var $html   = "";
	
	var $result = "";
	
	//=====================================
	// Constructer, called and run by IPB
	//=====================================
	
	function run_module()
	{
		//=====================================
		// Do any set up here, like load lang
		// skin files, etc
		//=====================================
		
		$this->ipsclass->load_language('lang_boards');
        $this->ipsclass->load_template('skin_boards');
		
		//=====================================
		// Set up structure
		//=====================================
		
		switch( $this->ipsclass->input['cmd'] )
		{
			case 'dosomething':
				$this->do_something();
				break;
				
			default:
				$this->do_something();
				break;
		}
		
		print $this->result;
		
		exit();
	}
	
	//------------------------------------------
	// do_something
	// 
	// Test sub, show if admin or not..
	//
	//------------------------------------------
	
	function do_something()
	{
		if ( $this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] )
		{
			$this->result = "You're an admin!";
		}
		else
		{
			$this->result = "You're not an admin!";
		}
	}
	
	
}


?>