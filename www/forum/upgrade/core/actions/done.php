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
		// Show page
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_done( $this->install->ipsclass->vars['board_url'].'/index.php' ) );
		$this->install->template->next_action = '';
		$this->install->template->hide_next   = 1;		
	}
}

?>