<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.1.2 -> 2.1.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	var $install;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function version_upgrade( & $install )
	{
		$this->install = & $install;
	}
	
	/*-------------------------------------------------------------------------*/
	// Auto run..
	/*-------------------------------------------------------------------------*/

	function auto_run()
	{
		if( $this->install->saved_data['helpfiles'] )
		{
			$UPDATES     = array();
			$file    = '_updates.php';
			$cnt     = 0;
		
			if ( file_exists( ROOT_PATH . 'upgrade/installfiles/upg_21003/' . strtolower($this->ipsclass->vars['sql_driver']) . $file  ) )
			{
				require_once( ROOT_PATH . 'upgrade/installfiles/upg_21003/' . strtolower($this->ipsclass->vars['sql_driver']) . $file  );
		
				$this->install->error   = array();
				$this->sqlcount = 0;
				
				$this->install->ipsclass->DB->return_die = 1;
				
				foreach( $UPDATES as $q )
				{
					$this->install->ipsclass->DB->allow_sub_select 	= 1;
					$this->install->ipsclass->DB->error				= '';
								
					$this->install->ipsclass->DB->query( $q );
					
					if ( $this->install->ipsclass->DB->error )
					{
						$this->install->error[] = $query."<br /><br />".$this->install->ipsclass->DB->error;
					}
					else
					{
						$this->sqlcount++;
					}
				}
			}
		
			$this->install->message = "$this->sqlcount help files updated....";
		}
		
		return true;

	}
	
}
	
	
?>