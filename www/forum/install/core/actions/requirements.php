<?php
/**
 * Invision Power Board
 * Action controller for requirements page
 */

class action_requirements
{
	var $install;
	
	function action_requirements( & $install )
	{
		$this->install =& $install;
	}
	
	function run()
	{
		/* Set App Specific Requirements */
		$this->install->set_requirements();
		
		/* Page Output */
		$this->install->template->append( $this->install->template->requirements_page( $this->install->version_php_min, $this->install->version_mysql_min ) );		
		
		/* Check Requirements */
		$errors = $this->install->check_requirements();

		/* Check for errors */	
		if( count( $errors ) )
		{
			$this->install->template->warning( $errors );	
			$this->install->template->next_action = 'disabled';
		}
		else 
		{
			$this->install->template->next_action = '?p=eula';
		}
	}
}

?>