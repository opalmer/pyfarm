<?php
/**
 * Invision Power Board
 * Action controller for Adresses page
 */

class action_address
{
	var $install;
	
	function action_address( & $install )
	{
		$this->install =& $install;
	}
	
	function run()
	{
		/* Check input? */
		if( $this->install->ipsclass->input['sub'] == 'check' )
		{
			/* Check Directory */
			if( ! $this->install->ipsclass->input['install_dir'] OR ! ( is_dir( $this->install->ipsclass->input['install_dir'] ) ) )
			{
				$errors[] = 'The specified directory does not exist';
			}
			
			/* Check URL */
			if( ! $this->install->ipsclass->input['install_url'] )
			{
				$errors[] = 'You did not specify a URL';	
			}

			if( is_array( $errors ) )
			{
				$this->install->template->warning( $errors );	
			}
			else 
			{
				/* Save Form Data */
				$this->install->saved_data['install_dir'] = preg_replace( "#(//)$#", "", str_replace( '\\', '/', $this->install->ipsclass->input['install_dir'] ) . '/' );
				$this->install->saved_data['install_url'] = preg_replace( "#(//)$#", "", str_replace( '\\', '/', $this->install->ipsclass->input['install_url'] ) . '/' );
				
				/* Next Action */
				$this->install->template->page_current = 'db';
				$this->install->ipsclass->input['sub'] = '';
				require_once( INS_ROOT_PATH . 'core/actions/db.php' );	
				$action = new action_db( &$this->install );
				$action->run();
				return;
			}
		}
		
		/* Guess at directory */
		$dir = str_replace( 'installer', '' , getcwd() );
		$dir = str_replace( 'install'  , '' , getcwd() );
		$dir = str_replace( '\\'       , '/', $dir );

		/* Guess at URL */
		$url = str_replace( "/installer/index.php"   , "", $this->install->ipsclass->my_getenv('HTTP_REFERER') );
		$url = str_replace( "/installer/"            , "", $url);
		$url = str_replace( "/installer"             , "", $url);
		$url = str_replace( "/install/index.php"     , "", $this->install->ipsclass->my_getenv('HTTP_REFERER') );
		$url = str_replace( "/install/"              , "", $url);
		$url = str_replace( "/installr"              , "", $url);
		$url = str_replace( "index.php"              , "", $url);
		$url = preg_replace( "!\?(.+?)*!"            , "", $url );	
		$url = "{$url}/";
		
		/* Page Output */
		$this->install->template->append( $this->install->template->address_page( $dir, $url ) );
		$this->install->template->next_action = '?p=address&sub=check';
	}
}

?>