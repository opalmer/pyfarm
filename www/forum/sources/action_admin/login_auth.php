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
|   > $Date: 2007-07-13 13:51:15 -0400 (Fri, 13 Jul 2007) $
|   > $Revision: 1087 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Components Functions
|   > Module written by Matt Mecham
|   > Date started: 12th April 2005 (13:09)
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_login_auth
{
	# Globals
	var $ipsclass;
	
	var $perm_main  = 'tools';
	var $perm_child = 'loginauth';
	
	/*-------------------------------------------------------------------------*/
	// Main handler
	/*-------------------------------------------------------------------------*/
	
	function auto_run() 
	{
		$this->html = $this->ipsclass->acp_load_template('cp_skin_tools');
		$this->ipsclass->admin->nav[]    = array( "{$this->ipsclass->form_code}", "Log In Manager" );
		
		switch($this->ipsclass->input['code'])
		{
			case 'manage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->login_list();
				break;
			
			case 'login_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->login_form('add');
				break;
			case 'login_add_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->login_save('add');
				break;
			case 'login_edit_details':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->login_form('edit');
				break;
			case 'login_edit_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->login_save('edit');
				break;
			
			case 'login_diagnostics':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':diagnostics' );
				$this->login_diagnostics();
				break;
			
			
			case 'components_export':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->components_export('single');
				break;
			case 'component_import':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->components_import();
				break;
				
			case 'component_move':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->components_move();
				break;
			
			case 'master_xml_export':
				$this->master_xml_export();
				break;
					
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->login_list();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Export Master XML
	/*-------------------------------------------------------------------------*/
	
	function master_xml_export()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];

		$xml->xml_set_root( 'export', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Set group
		//-----------------------------------------
		
		$xml->xml_add_group( 'group' );
		
		//-----------------------------------------
		// Get templates...
		//-----------------------------------------
	
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'login_methods' ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			if ( $r['login_folder_name'] == 'internal' )
			{
				$r['login_enabled'] = 1;
			}
			else if ( $r['login_folder_name'] == 'ipconverge' )
			{
				$r['login_maintain_url'] = '';
				$r['login_register_url'] = '';
				$r['login_login_url']    = '';
				$r['login_logout_url']   = '';
				$r['login_enabled']      = 0;
			}
			else
			{
				$r['login_enabled'] = 0;
			}
			
			//-----------------------------------------
			// Sort the fields...
			//-----------------------------------------
			
			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'row', $content );
		}
		
		$xml->xml_add_entry_to_group( 'group', $entry );
		
		$xml->xml_format_document();
		
		$doc = $xml->xml_document;
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $doc, 'loginauth.xml', '', 0 );
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Rebuild Cache
	/*-------------------------------------------------------------------------*/
	
	function components_rebuildcache()
	{
		$this->ipsclass->cache['components'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => 'com_id,com_enabled,com_section,com_filename,com_url_uri,com_url_title,com_position', 'from' => 'components', 'order' => 'com_position ASC' ) );
		$this->ipsclass->DB->simple_exec();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['components'][] = $r;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'components', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Import
	/*-------------------------------------------------------------------------*/
	
	function components_import()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$updated        = 0;
		$inserted       = 0;
		$cur_components = array();
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['file_location'] )
			{
				$this->ipsclass->main_msg = "No upload file was found and no filename was specified.";
				$this->components_list();
				return;
			}
			
			if ( ! file_exists( ROOT_PATH . $this->ipsclass->input['file_location'] ) )
			{
				$this->ipsclass->main_msg = "Could not find the file to open at: " . ROOT_PATH . $this->ipsclass->input['file_location'];
				$this->components_list();
				return;
			}
			
			if ( preg_match( "#\.gz$#", $this->ipsclass->input['file_location'] ) )
			{
				if ( $FH = @gzopen( ROOT_PATH.$this->ipsclass->input['file_location'], 'rb' ) )
				{
					while ( ! @gzeof( $FH ) )
					{
						$content .= @gzread( $FH, 1024 );
					}
					
					@gzclose( $FH );
				}
			}
			else
			{
				if ( $FH = @fopen( ROOT_PATH.$this->ipsclass->input['file_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize(ROOT_PATH.$this->ipsclass->input['lang_location']) );
					@fclose( $FH );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content  = $this->ipsclass->admin->import_xml( $tmp_name );
		}
		
		if( !$content )
		{
			$this->ipsclass->admin->error( "Import file was not in the correct format or was corrupt" );
		}
		
		//-----------------------------------------
		// Get current components.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'com_id, com_section',
													  'from'   => 'components',
													  'order'  => 'com_id' ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$cur_components[ $r['com_section'] ] = $r['com_id'];
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		$fields = array( 'com_title'   , 'com_description', 'com_author' , 'com_url', 'com_version', 'com_menu_data',
						 'com_enabled' , 'com_safemode'   , 'com_section', 'com_filename' );
		
		if ( ! is_array( $xml->xml_array['componentexport']['componentgroup']['component'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['componentexport']['componentgroup']['component'] = array( 0 => $xml->xml_array['componentexport']['componentgroup']['component'] );
		}
		
		foreach( $xml->xml_array['componentexport']['componentgroup']['component'] as $entry )
		{
			$newrow = array();
				
			foreach( $fields as $f )
			{
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->ipsclass->DB->force_data_type = array( 'com_version' => 'string' );
			
			if ( $cur_components[ $entry['com_section']['VALUE'] ] )
			{
				//-----------------------------------------
				// Update
				//-----------------------------------------
				
				$this->ipsclass->DB->do_update( 'components', $newrow, 'com_id='.$cur_components[ $entry['com_section']['VALUE'] ] );
				$updated++;
			}
			else
			{
				//-----------------------------------------
				// INSERT
				//-----------------------------------------
				
				$newrow['com_date_added'] = time();
				
				$this->ipsclass->DB->do_insert( 'components', $newrow );
				$inserted++;
			}
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->components_rebuildcache();
		
		$this->ipsclass->main_msg = "$updated components updated $inserted components inserted";
		
		$this->components_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Export
	/*-------------------------------------------------------------------------*/
	
	function components_export($type='single')
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$com_id = intval($this->ipsclass->input['com_id']);
		$rows   = array();
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'single' )
		{
			if ( ! $com_id )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->components_list();
				return;
			}
			
			//--------------------------------------------
			// Get DB row(s)
			//--------------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'components', 'where' => 'com_id='.$com_id ) );
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$rows[] = $r;
			}
		}
		else
		{
			//--------------------------------------------
			// Get DB row(s)
			//--------------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'components' ) );
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$rows[] = $r;
			}
		}
		
		//-------------------------------
		// Get XML class
		//-------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		$xml->xml_set_root( 'componentexport', array( 'exported' => time() ) );
		
		//-------------------------------
		// Add component
		//-------------------------------
		
		$xml->xml_add_group( 'componentgroup' );
		
		$entry = array();
		
		foreach( $rows as $r )
		{
			$content = array();
			
			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'component', $content );
		}
		
		$xml->xml_add_entry_to_group( 'componentgroup', $entry );
		
		$xml->xml_format_document();
		
		$doc = $xml->xml_document;

		//-------------------------------
		// Print to browser
		//-------------------------------
		
		$this->ipsclass->admin->show_download( $doc, 'ipd_components.xml', '', 0 );
	}
	
	
	
	/*-------------------------------------------------------------------------*/
	// Log in: Diagnostics
	/*-------------------------------------------------------------------------*/
	
	function login_diagnostics()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval($this->ipsclass->input['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id='.$login_id ) );
			
		if ( ! $login['login_id'] )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again.";
			$this->login_list();
			return;
		}
		
		//-----------------------------------------
		// Generate file
		//-----------------------------------------
		
		$mypath = ROOT_PATH.'sources/loginauth/'.$login['login_folder_name'];
		
		//-----------------------------------------
		// Generate General Info
		//-----------------------------------------
		
		$login['_enabled_img']   = $login['login_enabled']   ? 'aff_tick.png' : 'aff_cross.png';
		$login['_installed_img'] = $login['login_installed'] ? 'aff_tick.png' : 'aff_cross.png';
		$login['_has_settings']  = $login['login_settings']  ? 'aff_tick.png' : 'aff_cross.png';
		
		//-----------------------------------------
		// File based info
		//-----------------------------------------
		
		$login['_file_auth_exists'] = @file_exists( $mypath.'/auth.php' ) ? 'aff_tick.png' : 'aff_cross.png';
		$login['_file_conf_exists'] = @file_exists( $mypath.'/conf.php' ) ? 'aff_tick.png' : 'aff_cross.png';
		$login['_file_acp_exists']  = @file_exists( $mypath.'/acp.php' )  ? 'aff_tick.png' : 'aff_cross.png';
		
		$login['_file_conf_write']  = @file_exists( $mypath.'/conf.php' ) ? 'aff_tick.png' : 'aff_cross.png';
		
		$this->ipsclass->html .= $this->html->login_diagnostics( $login );
		
		$this->ipsclass->html_help_title = "Log In Manager";
		$this->ipsclass->html_help_msg   = "This section will allow you to manage your log in authentication methods.";
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Save
	/*-------------------------------------------------------------------------*/
	
	function login_save($type='add')
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id             = intval($this->ipsclass->input['login_id']);
		$login_title          = trim( $this->ipsclass->input['login_title'] );
		$login_description    = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_UNhtmlspecialchars($_POST['login_description'])) );
		$login_folder_name    = trim( $this->ipsclass->input['login_folder_name'] );
		$login_maintain_url   = trim( $this->ipsclass->input['login_maintain_url'] );
		$login_register_url   = trim( $this->ipsclass->input['login_register_url'] );
		$login_login_url      = trim( $this->ipsclass->input['login_login_url'] );
		$login_logout_url     = trim( $this->ipsclass->input['login_logout_url'] );
		$login_alt_login_html = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_UNhtmlspecialchars($_POST['login_alt_login_html'])) );
		$login_type           = trim( $this->ipsclass->input['login_type'] );
		$login_enabled        = intval($this->ipsclass->input['login_enabled']);
		$login_settings       = intval($this->ipsclass->input['login_settings']);
		$login_replace_form   = intval($this->ipsclass->input['login_replace_form']);
		$login_installed      = intval($this->ipsclass->input['login_installed']);
		$login_safemode       = intval($this->ipsclass->input['login_safemode']);
		$login_user_id        = trim($this->ipsclass->input['login_user_id']);
		
		if ( ! $login_title OR ! $login_folder_name OR ! $login_folder_name )
		{
			$this->ipsclass->main_msg = "You must complete the entire form.";
			$this->login_form( $type );
			return;
		}

		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $login_id )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->login_list();
				return;
			}
			
			//--------------------------------------------
			// Enabled this one?
			//--------------------------------------------
			
			if ( $login_enabled )
			{
				$this->ipsclass->DB->do_update( 'login_methods', array( 'login_enabled' => 0 ) );
			}
			
		}
		
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 'login_title'          => $login_title,
						'login_description'    => $login_description,
						'login_folder_name'    => $login_folder_name,
						'login_maintain_url'   => $login_maintain_url,
						'login_register_url'   => $login_register_url,
						'login_login_url'      => $login_login_url,
						'login_logout_url'     => $login_logout_url,
						'login_alt_login_html' => $login_alt_login_html,
						'login_type'           => $login_type,
						'login_enabled'        => $login_enabled,
						'login_settings'       => $login_settings,
						'login_replace_form'   => $login_replace_form,
						'login_allow_create'   => 1,
						'login_user_id'        => $login_user_id,
					 );
		
		//--------------------------------------------
		// In DEV?
		//--------------------------------------------
		
		if ( IN_DEV )
		{
			$array['login_installed'] = $login_installed;
			$array['login_safemode']  = $login_safemode;
		}
		
		//--------------------------------------------
		// Nike.. do it
		//--------------------------------------------
		
		if ( $type == 'add' )
		{
			$array['login_date'] = time();
			
			$this->ipsclass->DB->do_insert( 'login_methods', $array );
			$this->ipsclass->main_msg = 'New Log In Method Added';
		}
		else
		{
			
			$this->ipsclass->DB->do_update( 'login_methods', $array, 'login_id='.$login_id );
			$this->ipsclass->main_msg = 'Log In Method Edited';
		}
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_admin/settings.php' );
		$adsettings           =  new ad_settings();
		$adsettings->ipsclass =& $this->ipsclass;
		
		$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $login_folder_name ), "conf_key='ipbli_key'" );
		$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $login_user_id ),     "conf_key='ipbli_usertype'" );
		
		$adsettings->setting_rebuildcache();
		
		//-----------------------------------------
		// Return, at once.
		//-----------------------------------------
		
		$this->login_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Log in: Form
	/*-------------------------------------------------------------------------*/
	
	function login_form( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id  = intval($this->ipsclass->input['login_id']);
		$login_dd  = array( 0 => array( 'passthrough', 'Pass-Through' ), 1 => array( 'onfail', 'On-Fail' ) );
		$login_unt = array( 0 => array( 'username', 'User Name' ), 1 => array( 'email', 'Email Address' ) );
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'login_add_do';
			$title    = "Register New Log In Method";
			$button   = "Register New Log In Method";
		}
		else
		{
			$login = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id='.$login_id ) );
			
			if ( ! $login['login_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->login_list();
				return;
			}
			
			$formcode = 'login_edit_do';
			$title    = "Edit Log In Method ".$login['login_title'];
			$button   = "Save Changes";
		}
		
		//-------------------------------
		// Form elements
		//-------------------------------
		
		$form = array();
		
		$form['login_title']          = $this->ipsclass->adskin->form_input(    'login_title'         , $_POST['login_title']           ? $_POST['login_title']         : $login['login_title'] );
		$form['login_description']    = $this->ipsclass->adskin->form_input(    'login_description'   , $this->ipsclass->txt_htmlspecialchars( $_POST['login_description'] ? $_POST['login_description'] : $login['login_description'] ) );
		$form['login_folder_name']    = $this->ipsclass->adskin->form_input(    'login_folder_name'   , $_POST['login_folder_name']     ? $_POST['login_folder_name']   : $login['login_folder_name'] );
		$form['login_maintain_url']   = $this->ipsclass->adskin->form_input(    'login_maintain_url'  , $_POST['login_maintain_url']    ? $_POST['login_maintain_url']  : $login['login_maintain_url'] );
		$form['login_register_url']   = $this->ipsclass->adskin->form_input(    'login_register_url'  , $_POST['login_register_url']    ? $_POST['login_register_url']  : $login['login_register_url'] );
		$form['login_login_url']      = $this->ipsclass->adskin->form_input(    'login_login_url'     , $_POST['login_login_url']       ? $_POST['login_login_url']     : $login['login_login_url'] );
		$form['login_logout_url']     = $this->ipsclass->adskin->form_input(    'login_logout_url'    , $_POST['login_logout_url']      ? $_POST['login_logout_url']    : $login['login_logout_url'] );
		$form['login_alt_login_html'] = $this->ipsclass->adskin->form_textarea( 'login_alt_login_html', $this->ipsclass->txt_htmlspecialchars( $_POST['login_alt_login_html'] ? $_POST['login_alt_login_html'] : $login['login_alt_login_html'] ) );
		$form['login_enabled']        = $this->ipsclass->adskin->form_yes_no(   'login_enabled'       , $_POST['login_enabled']         ? $_POST['login_enabled']       : $login['login_enabled'] );
		$form['login_settings']       = $this->ipsclass->adskin->form_yes_no(   'login_settings'      , $_POST['login_settings']        ? $_POST['login_settings']      : $login['login_settings'] );
		$form['login_replace_form']   = $this->ipsclass->adskin->form_yes_no(   'login_replace_form'  , $_POST['login_replace_form']    ? $_POST['login_replace_form']  : $login['login_replace_form'] );
		$form['login_type']           = $this->ipsclass->adskin->form_dropdown( 'login_type'          , $login_dd , $_POST['login_type']    ? $_POST['login_type']      : $login['login_type'] );
		$form['login_user_id']        = $this->ipsclass->adskin->form_dropdown( 'login_user_id'       , $login_unt, $_POST['login_user_id'] ? $_POST['login_user_id']   : $login['login_user_id'] );
		
		if ( IN_DEV )
		{
			$form['login_safemode']  = $this->ipsclass->adskin->form_yes_no( 'login_safemode' , $_POST['login_safemode']  ? $_POST['login_safemode'] : $login['login_safemode'] );
			$form['login_installed'] = $this->ipsclass->adskin->form_yes_no( 'login_installed', $_POST['login_installed'] ? $_POST['login_installed']: $login['login_installed'] );
		}
		
		$this->ipsclass->html .= $this->html->login_form( $form, $title, $formcode, $button, $login );
		
		$this->ipsclass->html_help_title = "Log In Manager";
		$this->ipsclass->html_help_msg   = "This section will allow you to manage your log in authentication methods.";
		
		$this->ipsclass->admin->nav[]    = array( "", "Add/Edit Login Module" );
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// List current log in types
	/*-------------------------------------------------------------------------*/
	
	function login_list()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = "";
		
		//-------------------------------
		// Get components
		//-------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'login_methods', 'order' => 'login_title ASC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-------------------------------
			// Until IPB 3.0: hardcode some stuff
			//-------------------------------
			
			$r['login_installed'] = 1;
			
			//-------------------------------
			// (Alex) Cross
			//-------------------------------
			
			$r['_enabled_img']   = $r['login_enabled']   ? 'aff_tick.png' : 'aff_cross.png';
			$r['_installed_img'] = $r['login_installed'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$content .= $this->html->login_row($r);
		}
		
		$this->ipsclass->html .= $this->html->login_overview( $content );
		
		$this->ipsclass->admin->page_title  = "Log In Authentication Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your log in methods.";
		$this->ipsclass->admin->output();
	}

}


?>