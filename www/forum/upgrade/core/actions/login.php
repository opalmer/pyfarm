<?php
/**
 * Invision Power Board
 * Action controller for requirements page
 */

class action_login
{
	var $install;
	
	function action_login( & $install )
	{
		$this->install =& $install;
		
		$this->install->ipsclass->login_type = 'username';
		
		if( $this->install->ipsclass->DB->field_exists( "conf_id", "conf_settings" ) )
		{
			$this->install->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => "conf_key IN('ipbli_usertype','converge_login_method')", 'order' => 'conf_key ASC' ) );
			$this->install->ipsclass->DB->exec_query();
			
			while( $r = $this->install->ipsclass->DB->fetch_row() )
			{
				$r['conf_value'] = $r['conf_value'] ? $r['conf_value'] : $r['conf_default'];
				
				if( $r['conf_value'] )
				{
					$this->install->ipsclass->login_type = $r['conf_value'];
				}
			}
		}
	}
	
	function run()
	{
		/* Page Output */
		$this->install->template->append( $this->install->template->login_page( $this->install->template->message ) );		
		
		$this->install->template->next_action = '?p=overview';
	}
}

?>