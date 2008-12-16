<?php
/**
 * Invision Power Board
 * Action controller for EULA page
 */

class action_eula
{
	var $install;
	
	function action_eula( & $install )
	{
		$this->install =& $install;
	}
	
	function run()
	{
		/* Page Output */
		$this->install->template->append( $this->install->template->eula_page( nl2br($this->install->product_license) ) );
		$this->install->template->next_action = '?p=install';
	}
}

?>