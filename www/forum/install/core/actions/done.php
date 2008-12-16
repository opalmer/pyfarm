<?php
/**
 * Invision Power Board
 * Action controller for done page
 */

class action_done
{
	var $install;
	
	function action_done( & $install )
	{
		$this->install =& $install;
	}
	
	function run()
	{
		//-----------------------------------------
		// Lock installer
		//-----------------------------------------
		
		$this->install->lock_installer();
		
		//-----------------------------------------
		// Show page
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_done( $this->install->saved_data['admin_url'], $this->install->check_lock() ) );
		$this->install->template->next_action = '';
		$this->install->template->hide_next   = 1;		
	}
}

?>