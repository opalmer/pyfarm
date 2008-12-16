<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2007 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2006-09-22 11:28:54 +0100 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > API User Administration
|   > Module written by Matt Mecham
|   > Date started: Monday 25 June 2007
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_api
{
	/*-------------------------------------------------------------------------*/
	// Auto run
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		//-----------------------------------------
		// Make sure we're a root admin, or else!
		//-----------------------------------------
		
		if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
		{
			$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}
		
		//-----------------------------------------
		// Load skin...
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_api');
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'XML-RPC API Management' );
		
		//-----------------------------------------
		// What are we to do, today?
		//-----------------------------------------

		switch( $this->ipsclass->input['code'] )
		{
			//-----------------------------------------
			// Default:
			//-----------------------------------------
			case 'api_list':
			default:
				$this->api_list();
			break;
			case 'api_add':
				$this->api_form( 'add' );
			break;
			case 'api_add_save':
				$this->api_save( 'add' );
			break;
			case 'api_edit':
				$this->api_form( 'edit' );
			break;
			case 'api_edit_save':
				$this->api_save( 'edit' );
			break;
			case 'api_remove':
				$this->api_remove();
			break;
			
			case 'log_list':
				$this->log_list();
			break;
			case 'log_view_detail':
				$this->log_view_detail();
			break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// API Logs View
	/*-------------------------------------------------------------------------*/
	/**
	* API Logs View
	* View API Log
	*
	* @author Matt Mecham
	* @since  2.3.2
	*/
	/*
	CREATE TABLE ibf_api_log (
	  api_log_id 		int(10) unsigned NOT NULL auto_increment,
	  api_log_key 		VARCHAR(32) NOT NULL,
	  api_log_ip 		VARCHAR(16) NOT NULL,
	  api_log_date 		INT(10) NOT NULL,
	  api_log_query 	TEXT NOT NULL,
	  api_log_allowed 	TINYINT(1) unsigned NOT NULL,
	  PRIMARY KEY  (api_log_id)
	);*/
	
	function log_view_detail()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_log_id = intval( $this->ipsclass->input['api_log_id'] );
		
		//-----------------------------------------
		// Get data from the deebee
		//-----------------------------------------
		
		$log = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
															 	 'from'   => 'api_log',
															 	 'where'  => 'api_log_id='.$api_log_id ) );
															
		if ( ! $log['api_log_id'] )
		{
			$this->ipsclass->main_msg = "No log for that ID found";
			$this->log_list();
			return;
		}
		
		//-----------------------------------------
		// Display...
		//-----------------------------------------
		
		$log['_api_log_date'] 		= $this->ipsclass->get_date( $log['api_log_date'], 'LONG' );
		$log['_api_log_allowed']    = $log['api_log_allowed'] ? 'aff_tick.png' : 'aff_cross.png';
		$log['_api_log_query']      = htmlspecialchars( $log['api_log_query'] );
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->api_log_detail( $log );
		
		$this->ipsclass->admin->print_popup();
	}
	
	/*-------------------------------------------------------------------------*/
	// API Logs List
	/*-------------------------------------------------------------------------*/
	/**
	* API Logs List
	* List API Logs
	*
	* @author Matt Mecham
	* @since  2.3.2
	*/
	function log_list()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$start   = intval( $this->ipsclass->input['st'] );
		$perpage = 50;
		$logs    = array();
		
		//-----------------------------------------
		// Get log count
		//-----------------------------------------
		
		$count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as count',
																   'from'   => 'api_log' ) );
																
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => intval( $count['count'] ),
														  		  'PER_PAGE'    => $perpage,
														  		  'CUR_ST_VAL'  => $start,
														  		  'L_SINGLE'    => "",
														  		  'L_MULTI'     => "Pages: ",
														  		  'BASE_URL'    => $this->ipsclass->base_url.'&'.$this->ipsclass->form_code ) );
									  
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'api_log',
												 'order'  => 'api_log_date DESC',
												 'limit'  => array( $start, $perpage ) ) );
												
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['_api_log_date']     = $this->ipsclass->admin->get_date( $row['api_log_date'] );
			$row['_api_log_allowed']  = $row['api_log_allowed'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$logs[] = $row;
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( "XML-RPC API Logs", "This page shows all the recorded XML-RPC requests.<br />The red cross indicates an error during the request and a green tick indicates a successful request." ) . "<br />";
		
		$this->ipsclass->html .= $this->html->api_login_view( $logs, $links );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// API User Remove
	/*-------------------------------------------------------------------------*/
	/**
	* API User Remove
	* Removes an API User
	*
	* @author Brandon Farber
	* @since  2.3.2
	*/
	function api_remove()
	{
		$api_user_id   = isset($this->ipsclass->input['api_user_id']) ? intval($this->ipsclass->input['api_user_id']) : 0;
		
		if( !$api_user_id )
		{
			$this->ipsclass->main_msg = "Could not determine the user to remove";
			$this->api_list();
			return;
		}
		
		$api_user = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	  'from'   => 'api_users',
																	  'where'  => 'api_user_id='.$api_user_id ) );
		
		if ( ! $api_user['api_user_id'] )
		{
			$this->ipsclass->main_msg = "The API user could not be found.";
			$this->api_list();
			return;
		}
		
		$this->ipsclass->DB->do_delete( 'api_users', 'api_user_id='.$api_user_id );
		
		$this->ipsclass->main_msg = "API User successfully removed";
		$this->api_list();
	}
		
	/*-------------------------------------------------------------------------*/
	// API Form
	/*-------------------------------------------------------------------------*/
	/**
	* API Save
	* Save API user
	*
	* @author Matt Mecham
	* @since  2.3.2
	*/
	function api_save( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_user_id   = isset($this->ipsclass->input['api_user_id']) ? intval($this->ipsclass->input['api_user_id']) : 0;
		$api_user_key  = $this->ipsclass->input['api_user_key'];
		$api_user_name = $this->ipsclass->input['api_user_name'];
		$api_user_ip   = $this->ipsclass->input['api_user_ip'];
		$permissions = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( ! $api_user_name )
		{
			$this->ipsclass->main_msg = "You must enter a title";
			$this->api_form( $type );
			return;
		}
		
		//-----------------------------------------
		// More checking...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			if ( ! $api_user_key )
			{
				$this->ipsclass->main_msg = "No API user key was passed!";
				$this->api_form( $type );
				return;
			}
		}
		else
		{
			$api_user = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		  'from'   => 'api_users',
																		  'where'  => 'api_user_id='.$api_user_id ) );
			
			if ( ! $api_user['api_user_id'] )
			{
				$this->ipsclass->main_msg = "The API user could not be found.";
				$this->api_list();
				return;
			}
		}
		
		//-----------------------------------------
		// Save basics
		//-----------------------------------------
		
		$save = array( 'api_user_name' => $api_user_name,
					   'api_user_ip'   => $api_user_ip );
		
		//-----------------------------------------
		// Sort out permissions...
		//-----------------------------------------
		
		foreach( $this->ipsclass->input as $key => $value )
		{
			if ( preg_match( "#^_perm_([^_]+?)_(.*)$#", $key, $matches ) )
			{
				$module   = $matches[1];
				$function = $matches[2];
				
				if ( $value )
				{
					$permissions[ $module ][ $function ] = 1;
				}
			}
		}
	
		//-----------------------------------------
		// Add in perms
		//-----------------------------------------
		
		$save['api_user_perms'] = serialize( $permissions );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Add in key..
			//-----------------------------------------
			
			$save['api_user_key'] = $api_user_key;
			
			//-----------------------------------------
			// Save it...
			//-----------------------------------------
			
			$this->ipsclass->main_msg = "API User Added";
			
			$this->ipsclass->DB->do_insert( 'api_users', $save );
		}
		else
		{
			$this->ipsclass->main_msg = "API User Edit";
			
			$this->ipsclass->DB->do_update( 'api_users', $save, 'api_user_id=' . $api_user_id );
		}
		
		$this->api_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// API Form
	/*-------------------------------------------------------------------------*/
	/**
	* API LIST
	* List all currently stored API users
	*
	* @author Matt Mecham
	* @since  2.3.2
	*/
	function api_form( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_user_id = isset($this->ipsclass->input['api_user_id']) ? intval($this->ipsclass->input['api_user_id']) : 0;
		$form        = array();
		$permissions = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode  = 'api_add_save';
			$title     = "Create New API User";
			$button    = "Create New API User";
			$api_user  = array();
			$api_perms = array();
		}
		else
		{
			$api_user = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		  'from'   => 'api_users',
																		  'where'  => 'api_user_id='.$api_user_id ) );
			
			if ( ! $api_user['api_user_id'] )
			{
				$this->ipsclass->main_msg = "The API user could not be found.";
				$this->api_list();
				return;
			}
			
			$formcode = 'api_edit_save';
			$title    = "Edit API User: ".$api_user['api_user_name'];
			$button   = "Save Changes";
			
			$api_perms = unserialize( $api_user['api_user_perms'] );
		}
		
		//-----------------------------------------
		// Form
		//-----------------------------------------
		
		$form['api_user_name'] = $this->ipsclass->adskin->form_input( 'api_user_name', ( isset($_POST['api_user_name']) AND $_POST['api_user_name'] ) ? stripslashes($_POST['api_user_name']) : $api_user['api_user_name'] );
		$form['api_user_ip']   = $this->ipsclass->adskin->form_input( 'api_user_ip', ( isset($_POST['api_user_ip']) AND $_POST['api_user_ip'] ) ? stripslashes($_POST['api_user_ip']) : $api_user['api_user_ip'] );
		
		//-----------------------------------------
		// Get all modules and stuff and other things
		//-----------------------------------------
		
		$path   = ROOT_PATH . 'interface/board/modules';
		
		if ( is_dir( $path ) )
		{
			$handle = opendir( $path );

			while ( ( $file = readdir($handle) ) !== FALSE )
			{
				if ( is_dir( $path . '/' . $file ) )
				{
					if ( file_exists( $path . '/' . $file . '/config.php' ) )
					{
						$_name = $file;
				
						require_once( $path . "/" . $file . '/config.php' );
									
						if ( $CONFIG['api_module_title'] )
						{
							$permissions[ $_name ] = array(  'key'    => $api_module_title,
															 'title'  => $CONFIG['api_module_title'],
															 'desc'   => $CONFIG['api_module_desc'],
															 'path'   => $path . "/" . $file,
															 'perms'  => array() );
															
							//-----------------------------------------
							// Get all available methods
							//-----------------------------------------
							
							if ( file_exists( $path . '/' . $file . '/methods.php' ) )
							{
								require_once( $path . '/' . $file . '/methods.php' );
								
								$permissions[ $_name ]['perms'] = array_keys( $ALLOWED_METHODS );
							}
							
							//-----------------------------------------
							// Sort out form field
							//-----------------------------------------
							
							if ( is_array( $permissions[ $_name ]['perms'] ) )
							{
								foreach( $permissions[ $_name ]['perms'] as $perm )
								{
									$_checked = intval( $api_perms[ $_name ][ $perm ] );
									$permissions[ $_name ]['form_perms'][ $perm ] = array( 'title' => $perm,
																						   'form'  => $this->ipsclass->adskin->form_checkbox( '_perm_' . $_name . '_' . $perm, $_checked, 1 ) );
								}
							}
						}
						
						$CONFIG          = array();
						$ALLOWED_METHODS = array();
					}
				}
			}

			closedir( $handle );
		}
		
		//-----------------------------------------
		// Auto-generate API key
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$form['_api_user_key'] = md5( rand( 0, time() ) . $this->ipsclass->member['member_login_key'] . microtime() );
		}
		
		$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( "XML-RPC User Management", "You may create API users for use with the XML-RPC system which allows other applications to access IP.Board data" ) . "<br />";
		$this->ipsclass->html .= $this->html->api_form( $form, $title, $formcode, $button, $api_user, $type, $permissions );
		
		$this->ipsclass->admin->nav[]       = array( "", "Add/Edit API User" );
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// API List
	/*-------------------------------------------------------------------------*/
	/**
	* API LIST
	* List all currently stored API users
	*
	* @author Matt Mecham
	* @since  2.3.2
	*/
	function api_list()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$users = array();
		
		//-----------------------------------------
		// Get users from the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'	  => 'api_users',
												 'order'  => 'api_user_id' ) );
												
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$users[] = $row;
		}
		
		//-----------------------------------------
		// XML RPC Enabled?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['xmlrpc_enable'] )
		{
			$this->ipsclass->html .= $this->ipsclass->skin_acp_global->warning_box( "XML-RPC Sytem Disabled", "<strong>The XML-RPC system is not enabled!</strong><br />All API requests will fail. <a href='{$this->ipsclass->base_url}&amp;section=tools&amp;act=op&amp;code=setting_view&amp;conf_title_keyword=xmlrpcapi'>Click here to enable it</a>" ) .  "<br >";
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( "XML-RPC User Management", "You may create API users for use with the XML-RPC system which allows other applications to access IP.Board data" ) . "<br />";
		}
		
		//-----------------------------------------
		// Dun...
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->api_list( $users );
		
		//-----------------------------------------
		// PRINT
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
	}
}


?>