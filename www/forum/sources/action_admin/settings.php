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
|   > $Date: 2007-10-04 11:35:56 -0400 (Thu, 04 Oct 2007) $
|   > $Revision: 1126 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Setting functions
|   > Module written by Matt Mecham
|   > Date started: 20th March 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_settings {

	var $base_url;
	var $in_group  			= array();
	var $key_array 			= array();

	var $get_by_key 		= "";
	var $return_after_save 	= "";

	var $html;
	var $parser;
	var $han_editor;
	var $image_dir			= null;
	var $editor_loaded 		= 0;
	var $help_settings		= array();

	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main 			= "tools";

	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child 		= "op";

	function auto_run()
	{
		$this->ipsclass->admin->nav[]       = array( "{$this->ipsclass->form_code}", "View General Settings" );

		//-----------------------------------------
		// Load template
		//-----------------------------------------

		$this->html = $this->ipsclass->acp_load_template( 'cp_skin_settings' );

		//-----------------------------------------
		// Get XML Class for help keys
		//-----------------------------------------

		require_once( KERNEL_PATH . 'class_xml.php' );

		//-----------------------------------------

		$this->ipsclass->DB->sql_get_version();

		$this->true_version  = $this->ipsclass->DB->true_version;
		$this->mysql_version = $this->ipsclass->DB->mysql_version;

		switch($this->ipsclass->input['code'])
		{
			case 'settinggroup_resync':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->settinggroup_resync();
				break;

			case 'settinggroup_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->settinggroup_delete();
				break;

			case 'settinggroup_new':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->settinggroup_form('add');
				break;

			case 'settinggroup_showedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->settinggroup_form('edit');
				break;

			case 'settinggroup_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->settinggroup_save('add');
				break;

			case 'settinggroup_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->settinggroup_save('edit');
				break;

			case 'settingnew':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->setting_form('add');
				break;

			case 'setting_showedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->setting_form('edit');
				break;

			case 'setting_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->setting_save('add');
				break;

			case 'setting_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->setting_save('edit');
				break;

			case 'setting_view':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->setting_view();
				break;

			case 'setting_help':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->setting_help();
				break;

			case 'setting_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->setting_delete();
				break;

			case 'setting_revert':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->setting_revert();
				break;

			case 'setting_update':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->setting_update();
				break;

			case 'setting_allexport':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->setting_allexport();
				break;

			case 'findsetting':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->setting_findgroup();
				break;

			case 'setting_someexport_start':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->setting_someexport_start();
				break;

			case 'setting_someexport_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->setting_someexport_complete();
				break;

			case 'settings_do_import':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->settings_do_import();
				break;

			//-----------------------------------------
			// Full text
			//-----------------------------------------

			case 'dofulltext':
				$this->do_fulltext();
				break;

			case 'phpinfo':
				phpinfo();
				exit;

			case 'MOD_export_setting':
				$this->settinggroup_export();
				break;

			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->setting_start();
				break;
		}

	}

	/*-------------------------------------------------------------------------*/
	// Check differences
	/*-------------------------------------------------------------------------*/

	function settings_check_differences( $content='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$settings         = array();
		$xml_settings     = array();
		$fields           = array( 'conf_title'   , 'conf_description', 'conf_group'    , 'conf_type'    , 'conf_key'        , 'conf_default',
					 	           'conf_extra'   , 'conf_evalphp'    , 'conf_protected', 'conf_position', 'conf_start_group', 'conf_end_group',
						           'conf_add_cache'  , 'conf_title_keyword' );
		$upload_new       = array();
		$upload_missing   = array();
		$original_new     = array();
		$original_missing = array();

		//-----------------------------------------
		// Get current settings
		//-----------------------------------------

		$this->ipsclass->DB->build_query( array( 'select' => 's.*',
												 'from'   => array( 'conf_settings' => 's' ),
												 'add_join' => array( 0 => array( 'select' => 'c.*',
																				  'from'   => array( 'conf_settings_titles' => 'c' ),
																				  'where'  => 'c.conf_title_id=s.conf_group',
																				  'type'   => 'inner' ) ) ) );

		$this->ipsclass->DB->exec_query();

		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$settings[ $row['conf_key'] ] = $row;
		}

		//-----------------------------------------
		// Sort out XML file
		//-----------------------------------------

		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();

		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------

		$xml->xml_parse_document( $content );

		//-----------------------------------------
		// Fix up...
		//-----------------------------------------

		if ( ! is_array( $xml->xml_array['settingexport']['settinggroup']['setting'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['settingexport']['settinggroup']['setting'] = array( 0 => $xml->xml_array['settingexport']['settinggroup']['setting'] );
		}

		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------

		foreach( $xml->xml_array['settingexport']['settinggroup']['setting'] as $entry )
		{
			//-----------------------------------------
			// Is setting?
			//-----------------------------------------

			if ( ! $entry['conf_is_title']['VALUE'] )
			{
				foreach( $fields as $f )
				{
					$xml_settings[ $entry['conf_key']['VALUE'] ] = $entry[ $f ]['VALUE'];
				}
			}
		}

		//-----------------------------------------
		// Original: Missing
		//-----------------------------------------

		foreach( $xml_settings as $key => $data )
		{
			if ( ! in_array( $key, array_keys( $settings ) ) )
			{
				$original_missing[ $key ] = $data;
			}
		}

		//-----------------------------------------
		// Original: New
		//-----------------------------------------

		foreach( $settings as $key => $data )
		{
			if ( ! in_array( $key, array_keys( $xml_settings ) ) )
			{
				$original_new[ $key ] = $data;
			}
		}

		$this->ipsclass->html .= $this->html->settings_check_differences( $original_missing, $original_new );

		$this->ipsclass->admin->output();
	}

	/*-------------------------------------------------------------------------*/
	// IMPORT PARTIAL SETTINGS
	/*-------------------------------------------------------------------------*/

	function settings_do_import( $noreturn=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$updated      = 0;
		$inserted     = 0;
		$need_update  = array();
		$cur_settings = array();
		$new_titles   = array();
		$new_settings = array();
		$tool         = $this->ipsclass->input['tool'];

		$this->setting_titles_check();

		//-----------------------------------------
		// Get file
		//-----------------------------------------

		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------

			if ( ! $this->ipsclass->input['file_location'] )
			{
				$this->ipsclass->main_msg = "No upload file was found and no filename was specified.";
				$this->setting_start();
			}

			if ( ! file_exists( ROOT_PATH . $this->ipsclass->input['file_location'] ) )
			{
				$this->ipsclass->main_msg = "Could not find the file to open at: " . ROOT_PATH . $this->ipsclass->input['file_location'];
				$this->setting_start();
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
					$content = @fread( $FH, filesize(ROOT_PATH.$this->ipsclass->input['file_location']) );
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

		if ( ! $content )
		{
			$this->ipsclass->admin->error( "Import file is not in the correct format or is corrupt" );
		}

		if ( $tool == 'check' )
		{
			$this->settings_check_differences( $content );
			return;
		}

		//-----------------------------------------
		// Get current settings.
		//-----------------------------------------

		$this->ipsclass->DB->simple_construct( array( 'select' => 'conf_id, conf_key',
													  'from'   => 'conf_settings',
													  'order'  => 'conf_id' ) );

		$this->ipsclass->DB->simple_exec();

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$cur_settings[ $r['conf_key'] ] = $r['conf_id'];
		}

		//-----------------------------------------
		// Get current titles
		//-----------------------------------------

		$this->setting_get_groups();

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

		$fields = array( 'conf_title'   , 'conf_description', 'conf_group'    , 'conf_type'    , 'conf_key'        , 'conf_default',
						 'conf_extra'   , 'conf_evalphp'    , 'conf_protected', 'conf_position', 'conf_start_group', 'conf_end_group',
						 'conf_add_cache'  , 'conf_title_keyword' );

		$setting_fields = array( 'conf_title_keyword', 'conf_title_title', 'conf_title_desc', 'conf_title_noshow', 'conf_title_module' );

		//-----------------------------------------
		// Fix up...
		//-----------------------------------------

		if ( ! is_array( $xml->xml_array['settingexport']['settinggroup']['setting'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['settingexport']['settinggroup']['setting'] = array( 0 => $xml->xml_array['settingexport']['settinggroup']['setting'] );
		}

		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------

		foreach( $xml->xml_array['settingexport']['settinggroup']['setting'] as $entry )
		{
			$newrow = array();

			//-----------------------------------------
			// Is setting?
			//-----------------------------------------

			if ( ! $entry['conf_is_title']['VALUE'] )
			{
				foreach( $fields as $f )
				{
					$newrow[$f] = $entry[ $f ]['VALUE'];
				}

				$new_settings[] = $newrow;
			}

			//-----------------------------------------
			// Is title?
			//-----------------------------------------

			else
			{
				foreach( $setting_fields as $f )
				{
					$newrow[$f] = $entry[ $f ]['VALUE'];
				}

				$new_titles[] = $newrow;
			}
		}

		//-----------------------------------------
		// Sort out titles...
		//-----------------------------------------

		if ( is_array( $new_titles ) and count( $new_titles ) )
		{
			foreach( $new_titles as $data )
			{
				if ( $data['conf_title_title'] AND $data['conf_title_keyword'] )
				{
					//-----------------------------------------
					// Get ID based on key
					//-----------------------------------------

					$conf_id = $this->setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];

					$save = array( 'conf_title_title'   => $data['conf_title_title'],
								   'conf_title_desc'    => $data['conf_title_desc'],
								   'conf_title_keyword' => $data['conf_title_keyword'],
								   'conf_title_noshow'  => $data['conf_title_noshow'],
								   'conf_title_module'  => $data['conf_title_module'] );

					//-----------------------------------------
					// Not got a row, insert first!
					//-----------------------------------------

					if ( ! $conf_id )
					{
						$this->ipsclass->DB->do_insert( 'conf_settings_titles', $save );
						$conf_id = $this->ipsclass->DB->get_insert_id();

					}
					else
					{
						//-----------------------------------------
						// Update...
						//-----------------------------------------

						$this->ipsclass->DB->do_update( 'conf_settings_titles', $save, 'conf_title_id='.$conf_id );
					}

					//-----------------------------------------
					// Update settings cache
					//-----------------------------------------

					$save['conf_title_id']                                      = $conf_id;
					$this->setting_groups_by_key[ $save['conf_title_keyword'] ] = $save;
					$this->setting_groups[ $save['conf_title_id'] ]             = $save;

					//-----------------------------------------
					// Remove need update...
					//-----------------------------------------

					$need_update[] = $conf_id;
				}
			}
		}

		//-----------------------------------------
		// Sort out settings
		//-----------------------------------------

		if ( is_array( $new_settings ) and count( $new_settings ) )
		{
			foreach( $new_settings as $data )
			{
				//-----------------------------------------
				// Make PHP slashes safe
				// Not needed 2.1.0 'cos mysql_escape_slashes does a good job
				//-----------------------------------------

				//$data['conf_evalphp'] = str_replace( '\\', '\\\\', $data['conf_evalphp'] );

				//-----------------------------------------
				// Now assign to the correct ID based on
				// our title keyword...
				//-----------------------------------------

				$data['conf_group'] = $this->setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];

				//-----------------------------------------
				// Remove from array
				//-----------------------------------------

				unset( $data['conf_title_keyword'] );

				if ( $cur_settings[ $data['conf_key'] ] )
				{
					//-----------------------------------------
					// Update
					//-----------------------------------------

					$this->ipsclass->DB->do_update( 'conf_settings', $data, 'conf_id='.$cur_settings[ $data['conf_key'] ] );
					$updated++;
				}
				else
				{
					//-----------------------------------------
					// INSERT
					//-----------------------------------------

					$this->ipsclass->DB->do_insert( 'conf_settings', $data );
					$inserted++;
				}
			}
		}

		//-----------------------------------------
		// Update group counts...
		//-----------------------------------------

		if ( count( $need_update ) )
		{
			foreach( $need_update as $idx )
			{
				$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'conf_settings', 'where' => 'conf_group='.$idx ) );

				$count = intval($conf['count']);

				$this->ipsclass->DB->do_update( 'conf_settings_titles', array( 'conf_title_count' => $count ), 'conf_title_id='.$idx );
			}
		}

		//-----------------------------------------
		// Update cache
		//-----------------------------------------

		$this->ipsclass->DB->do_delete( 'cache_store', "cs_key='in_dev_setting_update'" );
		$this->ipsclass->DB->do_insert( 'cache_store', array( 'cs_value' => time(), 'cs_key' => "in_dev_setting_update" ) );

		//-----------------------------------------
		// Resync
		//-----------------------------------------

		$this->ipsclass->main_msg = "$updated settings updated $inserted settings inserted";

		if ( ! $noreturn )
		{
			$this->setting_rebuildcache();

			$this->setting_start();
		}
	}

	//-----------------------------------------
	//
	// EXPORT Some Settings. DO IT NOW YES
	//
	//-----------------------------------------

	function setting_someexport_complete()
	{
		$ids    = array();
		$groups = array();

		//-----------------------------------------
		// get ids...
		//-----------------------------------------

		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^id_(\d+)$/", $key, $match ) )
			{
				if ($this->ipsclass->input[$match[0]])
				{
					$ids[] = $match[1];
				}
			}
		}

		$ids = $this->ipsclass->clean_int_array( $ids );

		//-----------------------------------------
		// Got any?
		//-----------------------------------------

		if ( ! count( $ids ) )
		{
			$this->ipsclass->main_msg = "You must select SOME settings to export!";
			$this->setting_someexport_start();
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------

		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();

		$xml->xml_set_root( 'settingexport', array( 'exported' => time() ) );

		//-----------------------------------------
		// Get groups
		//-----------------------------------------

		$xml->xml_add_group( 'settinggroup' );

		$this->setting_get_groups();

		$entry = array();

		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'conf_settings',
													  'where'  => "conf_id IN (".implode(",",$ids).")",
													  'order'  => 'conf_position, conf_title' ) );

		$this->ipsclass->DB->simple_exec();

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content                    = array();
			$groups[ $r['conf_group'] ] = $r['conf_group'];
			$r['conf_value']            = '';

			//-----------------------------------------
			// Add in setting key
			//-----------------------------------------

			$r['conf_title_keyword'] = $this->setting_groups[ $r['conf_group'] ]['conf_title_keyword'];

			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}

			$entry[] = $xml->xml_build_entry( 'setting', $content );
		}

		//-----------------------------------------
		// Add in groups...
		//-----------------------------------------

		if ( is_array( $groups ) AND count( $groups ) )
		{
			foreach( $groups as $conf_group_id )
			{
				$content  = array();

				$thisconf = array( 'conf_is_title'      => 1,
								   'conf_title_keyword' => $this->setting_groups[ $conf_group_id ]['conf_title_keyword'],
								   'conf_title_title'   => $this->setting_groups[ $conf_group_id ]['conf_title_title'],
								   'conf_title_desc'    => $this->setting_groups[ $conf_group_id ]['conf_title_desc'],
								   'conf_title_noshow'  => $this->setting_groups[ $conf_group_id ]['conf_title_noshow'],
								   'conf_title_module'  => $this->setting_groups[ $conf_group_id ]['conf_title_module'] );

				foreach( $thisconf as $k => $v )
				{
					$content[] = $xml->xml_build_simple_tag( $k, $v );
				}

				$entry[] = $xml->xml_build_entry( 'setting', $content );
			}
		}

		$xml->xml_add_entry_to_group( 'settinggroup', $entry );

		$xml->xml_format_document();

		$doc = $xml->xml_document;

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------

		$this->ipsclass->admin->show_download( $doc, 'ipb_settings_partial.xml', '', 0 );
	}

	//-----------------------------------------
	//
	// EXPORT Some Settings. Co-co-ca-choo
	//
	//-----------------------------------------

	function setting_someexport_start()
	{
		$this->ipsclass->admin->page_title  = "Export Selected System Settings";
		$this->ipsclass->admin->page_detail = "Check the box of the setting you wish to export.";
		$this->ipsclass->admin->nav[]       = array( '', "Export Settings" );

		//-----------------------------------------
		// start form
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'setting_someexport_complete' ),
															     2 => array( 'act'   , 'op'      ),
															     4 => array( 'section', $this->ipsclass->section_code ),
													    )      );

		$this->ipsclass->html .= "<div class='tableborder'>
								  <div class='tableheaderalt'>System Settings</div>
								  <table width='100%' cellspacing='1' cellpadding='4' border='0'>";

		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings', 'order' => 'conf_id' ) );
		$this->ipsclass->DB->simple_exec();

		$per_row  = 3;
		$td_width = 100 / $per_row;
		$count    = 0;
		$output   = "<tr align='center'>\n";

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$count++;

			$class = $count == 2 ? 'tablerow2' : 'tablerow1';

			$output .= "<td width='{$td_width}%' align='left' class='$class'>
						 <input type='checkbox' style='checkbox' value='1' name='id_{$r['conf_id']}' /> <strong>{$r['conf_key']}</strong> - {$r['conf_id']}
						</td>";

			if ($count == $per_row )
			{
				$output .= "</tr>\n\n<tr align='center'>";
				$count   = 0;
			}
		}

		if ( $count > 0 and $count != $per_row )
		{
			for ($i = $count ; $i < $per_row ; ++$i)
			{
				$output .= "<td class='tablerow2'>&nbsp;</td>\n";
			}

			$output .= "</tr>";
		}


		$this->ipsclass->html .= $output;

		$this->ipsclass->html .= "</table>
						    <div class='tablesubheader' align='center'><input type='submit' class='realbutton' value='EXPORT SELECTED' /></form></div></div>";

		$this->ipsclass->admin->output();
	}

	//-----------------------------------------
	//
	// Find setting group (don't rely on IDs)
	//
	//-----------------------------------------

	function setting_findgroup()
	{
		if ( ! $this->ipsclass->input['key'] )
		{
			$this->setting_start();
		}

		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings_titles' ) );
		$this->ipsclass->DB->simple_exec();

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['conf_title_keyword'] == $this->ipsclass->input['key'] OR strtolower( str_replace( " ", "", trim($r['conf_title_title']) ) ) == urldecode(trim($this->ipsclass->input['key'])) )
			{
				$this->ipsclass->boink_it( $this->ipsclass->base_url.'&'.$this->ipsclass->form_code.'&code=setting_view&conf_group='.$r['conf_title_id'] );
				break;
			}
		}

		$this->setting_start();
	}

	//-----------------------------------------
	//
	// Export all to to XML (DEV ONLY)
	//
	//-----------------------------------------

	function setting_allexport()
	{
		if ( ! IN_DEV )
		{
			$this->setting_start();
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------

		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();

		$xml->xml_set_root( 'settingexport', array( 'exported' => time() ) );

		//-----------------------------------------
		// Get groups
		//-----------------------------------------

		$xml->xml_add_group( 'settinggroup' );

		$this->setting_get_groups();

		$entry = array();

		foreach( $this->setting_groups as $roar )
		{
			//-----------------------------------------
			// First, add in setting group title
			//-----------------------------------------

			$content = array();

			$thisconf = array( 'conf_is_title'      => 1,
							   'conf_title_keyword' => $roar['conf_title_keyword'],
							   'conf_title_title'   => $roar['conf_title_title'],
							   'conf_title_desc'    => $roar['conf_title_desc'],
							   'conf_title_noshow'  => $roar['conf_title_noshow'],
							   'conf_title_module'  => $roar['conf_title_module'] );

			foreach( $thisconf as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}

			$entry[] = $xml->xml_build_entry( 'setting', $content );

			//-----------------------------------------
			// Get settings...
			//-----------------------------------------

			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'conf_settings',
														  'where'  => "conf_group='{$roar['conf_title_id']}'",
														  'order'  => 'conf_position, conf_title' ) );

			$this->ipsclass->DB->simple_exec();

			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$content = array();

				//-----------------------------------------
				// Empty user value
				//-----------------------------------------

				$r['conf_value'] = '';

				//-----------------------------------------
				// Add in setting key
				//-----------------------------------------

				$r['conf_title_keyword'] = $roar['conf_title_keyword'];

				//-----------------------------------------
				// Set :is setting flag
				//-----------------------------------------

				$r['conf_is_title']      = 0;

				//-----------------------------------------
				// Sort the rest...
				//-----------------------------------------

				foreach( $r as $k => $v )
				{
					$content[] = $xml->xml_build_simple_tag( $k, $v );
				}

				$entry[] = $xml->xml_build_entry( 'setting', $content );
			}
		}

		$xml->xml_add_entry_to_group( 'settinggroup', $entry );

		$xml->xml_format_document();

		$doc = $xml->xml_document;

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------

		$this->ipsclass->admin->show_download( $doc, 'settings.xml', '', 0 );
	}

	//-----------------------------------------
	//
	// Delete setting group
	//
	//-----------------------------------------

	function settinggroup_delete()
	{
		if ( $this->ipsclass->input['id'] )
		{
			$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'conf_settings', 'where' => 'conf_group='.intval($this->ipsclass->input['id']) ) );

			$count = intval($conf['count']);

			if ( $count > 0 )
			{
				$this->ipsclass->main_msg = "Cannot remove this setting group as it still contains active settings";
			}
			else
			{
				$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'conf_settings_titles', 'where' => 'conf_title_id='.intval($this->ipsclass->input['id']) ) );

				$this->ipsclass->main_msg = "Setting Group Removed";
			}

		}

		$this->setting_start();
	}

	//-----------------------------------------
	//
	// Recount settings
	//
	//-----------------------------------------

	function settinggroup_resync()
	{
		if ( $this->ipsclass->input['id'] )
		{
			$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'conf_settings', 'where' => 'conf_group='.intval($this->ipsclass->input['id']) ) );

			$count = intval($conf['count']);

			$this->ipsclass->DB->do_update( 'conf_settings_titles', array( 'conf_title_count' => $count ), 'conf_title_id='.intval($this->ipsclass->input['id']) );
		}

		$this->setting_start();
	}

	//-----------------------------------------
	//
	// New setting group form
	//
	//-----------------------------------------

	function settinggroup_form( $type='add' )
	{
		$this->ipsclass->admin->page_title  = "System Configuration Settings";
		$this->ipsclass->admin->page_detail = "This section contains all the configuration options for your IPB.";
		$this->ipsclass->admin->nav[]       = array( '', "Add/Edit Settings" );

		if ( $type == 'add' )
		{
			$formcode = 'settinggroup_add';
			$title    = "Create New Board Setting Group";
			$button   = "Create New Setting Group";
			$conf	  = array( 'conf_title_title' 	=> '',
								'conf_title_desc' 	=> '',
								'conf_title_keyword' => '',
								'conf_title_noshow' => '',
							 );
		}
		else
		{
			$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings_titles', 'where' => 'conf_title_id='.intval($this->ipsclass->input['id']) ) );

			if ( ! $conf['conf_title_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->setting_start();
			}

			$formcode = 'settinggroup_edit';
			$title    = "Edit Setting ".$conf['conf_title'];
			$button   = "Save Changes";
		}

		$this->ipsclass->admin->page_detail = '&nbsp;';
		$this->ipsclass->admin->page_title  = $title;

		//-----------------------------------------
		// start form
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $formcode ),
															     2 => array( 'act'   , 'op'      ),
															     3 => array( 'id'    , $this->ipsclass->input['id'] ),
															     4 => array( 'section', $this->ipsclass->section_code ),
													    )      );

		//-----------------------------------------
		// start table
		//-----------------------------------------

		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "60%" );

		//-----------------------------------------
		// um..
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $title );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Group title?</b>" ,
												  			     $this->ipsclass->adskin->form_input( 'conf_title_title', ( isset($_POST['conf_title_title']) AND $_POST['conf_title_title'] ) ? $_POST['conf_title_title'] : $conf['conf_title_title'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Group Description?</b>" ,
												  			     $this->ipsclass->adskin->form_textarea( 'conf_title_desc', ( isset($_POST['conf_title_desc']) AND $_POST['conf_title_desc'] ) ? $_POST['conf_title_desc'] : $conf['conf_title_desc'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Group Keyword?</b><div class='graytext'>Used to pull this from the DB without relying on an ID</div>" ,
																			 $this->ipsclass->adskin->form_input( 'conf_title_keyword', ( isset($_POST['conf_title_keyword']) AND $_POST['conf_title_keyword'] ) ? $_POST['conf_title_keyword'] : $conf['conf_title_keyword'] )
																	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Group Module?</b><div class='desctext'>Enter the filename of the PHP module file (eg: example.php) for pre and post setting parsing. The file name must be uploaded into the /sources/components_acp/setting_plugins directory." ,
												  			     $this->ipsclass->adskin->form_input( 'conf_title_module', ( isset($_POST['conf_title_module']) AND $_POST['conf_title_module'] ) ? $_POST['conf_title_module'] : $conf['conf_title_module'] )
										 		    	)      );

		//-----------------------------------------
		// er....
		//-----------------------------------------

		if ( IN_DEV )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Hide from main settings list?</b>" ,
																				 $this->ipsclass->adskin->form_yes_no( 'conf_title_noshow', ( isset($_POST['conf_title_noshow']) AND $_POST['conf_title_noshow'] ) ? $_POST['conf_title_noshow'] : $conf['conf_title_noshow'] )
																		)      );
		}


		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( $button );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();

		$this->ipsclass->admin->output();

	}

	//-----------------------------------------
	// Settings Group Save Form
	//-----------------------------------------

	function settinggroup_save($type='add')
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$conf_title_keyword = $this->ipsclass->txt_stripslashes( $_POST['conf_title_keyword'] );

		//-----------------------------------------
		// Check...
		//-----------------------------------------

		if ( ! $conf_title_keyword )
		{
			$this->ipsclass->main_msg = "You must enter a unique keyword for: Setting Group Keyword";
			$this->settinggroup_form();
			return;
		}

		//-----------------------------------------
		// Make sure another setting doesn't have
		// this keyword
		//-----------------------------------------

		$test = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'conf_settings_titles', 'where' => "conf_title_keyword='{$conf_title_keyword}'" ) );

		if ( $type == 'edit' )
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->settinggroup_form();
				return;
			}

			if ( $test['conf_title_title'] AND $test['conf_title_id'] != $this->ipsclass->input['id'] )
			{
				$this->ipsclass->main_msg = "You are already using this keyword for another setting group";
				$this->settinggroup_form();
				return;
			}


		}
		else
		{
			if ( $test['conf_title_title'] )
			{
				$this->ipsclass->main_msg = "You are already using this keyword for another setting group";
				$this->settinggroup_form();
				return;
			}
		}

		//-----------------------------------------
		// check...
		//-----------------------------------------

		$array = array( 'conf_title_title'   => $this->ipsclass->input['conf_title_title'],
						'conf_title_desc'    => $this->ipsclass->txt_stripslashes( $_POST['conf_title_desc'] ),
						'conf_title_keyword' => $conf_title_keyword,
						'conf_title_noshow'  => $this->ipsclass->input['conf_title_noshow'],
						'conf_title_module'  => $this->ipsclass->input['conf_title_module']
					 );


		if ( $type == 'add' )
		{
			$this->ipsclass->DB->do_insert( 'conf_settings_titles', $array );
			$this->ipsclass->main_msg = 'New Setting Group Added';
		}
		else
		{
			$this->ipsclass->DB->do_update( 'conf_settings_titles', $array, 'conf_title_id='.intval($this->ipsclass->input['id']) );
			$this->ipsclass->main_msg = 'Setting Group Edited';
		}

		$this->setting_rebuildcache();

		$this->setting_start();
	}

	//-----------------------------------------
	//
	// New setting form
	//
	//-----------------------------------------

	function setting_form( $type='add' )
	{
		$this->ipsclass->admin->page_title  = "System Configuration Settings";
		$this->ipsclass->admin->page_detail = "This section contains all the configuration options for your IPB.";
		$this->ipsclass->admin->nav[]       = array( '', "Add/Edit Settings" );

		if ( $type == 'add' )
		{
			$formcode = 'setting_add';
			$title    = "Create New Board Setting";
			$button   = "Create New Setting";
			$conf     = array( 'conf_group' 	=> $this->ipsclass->input['conf_group'],
							   'conf_add_cache' => 1,
							   'conf_title'		=> '',
							   'conf_description' => '',
							   'conf_type'		=> '',
							   'conf_key'		=> '',
							   'conf_value'		=> '',
							   'conf_default'	=> '',
							   'conf_extra'		=> '',
							   'conf_evalphp'	=> '',
							   'conf_start_group' => '',
							   'conf_end_group'	=> '' );

			if ( IN_DEV )
			{
				$conf['conf_protected'] = 1;
			}

			if ( $this->ipsclass->input['conf_group'] )
			{
				$max = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'max(conf_position) as max', 'from' => 'conf_settings', 'where' => 'conf_group='.$this->ipsclass->input['conf_group'] ) );
			}
			else
			{
				$max = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'max(conf_position) as max', 'from' => 'conf_settings' ) );
			}

			$conf['conf_position'] = $max['max'] + 1;
		}
		else
		{
			$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => 'conf_id='.intval($this->ipsclass->input['id']) ) );

			if ( ! $conf['conf_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->setting_start();
			}

			$formcode = 'setting_edit';
			$title    = "Edit Setting ".$conf['conf_title'];
			$button   = "Save Changes";
		}

		$this->ipsclass->admin->page_detail = '&nbsp;';
		$this->ipsclass->admin->page_title  = $title;

		//-----------------------------------------
		// Get groups
		//-----------------------------------------

		$this->setting_get_groups();

		$groups = array();

		foreach( $this->setting_groups as $r )
		{
			$groups[] = array( $r['conf_title_id'], $r['conf_title_title'] );
		}

		//-----------------------------------------
		// Type
		//-----------------------------------------

		$types = array( 0 => array( 'input'   , 'Text Input' ),
						1 => array( 'dropdown', 'Drop Down'  ),
						2 => array( 'yes_no'  , 'Yes/No Radio Buttons'),
						3 => array( 'textarea', 'Textarea'   ),
						4 => array( 'editor'  , 'Full Editor'   ),
						5 => array( 'multi'   , 'Multi Select' ),
					 );

		//-----------------------------------------
		// start form
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , $formcode ),
																			 2 => array( 'act'    , 'op'      ),
																			 3 => array( 'id'     , $this->ipsclass->input['id'] ),
																			 4 => array( 'section', 'tools' ),
																	)      );

		//-----------------------------------------
		// start table
		//-----------------------------------------

		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "60%" );

		//-----------------------------------------
		// um..
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $title );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting title?</b>" ,
												  			   $this->ipsclass->adskin->form_input( 'conf_title', ( isset($_POST['conf_title']) AND $_POST['conf_title'] ) ? $_POST['conf_title'] : $conf['conf_title'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Position?</b>" ,
												  			   $this->ipsclass->adskin->form_input( 'conf_position', ( isset($_POST['conf_position']) AND $_POST['conf_position'] ) ? $_POST['conf_position'] : $conf['conf_position'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Description?</b>" ,
												  			   $this->ipsclass->adskin->form_textarea( 'conf_description', ( isset($_POST['conf_description']) AND $_POST['conf_description'] ) ? $_POST['conf_description'] : $conf['conf_description'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Group?</b>" ,
												  			   $this->ipsclass->adskin->form_dropdown( 'conf_group', $groups, ( isset($_POST['conf_group']) AND $_POST['conf_group'] ) ? $_POST['conf_group'] : $conf['conf_group'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Type?</b>" ,
												  			   $this->ipsclass->adskin->form_dropdown( 'conf_type', $types, ( isset($_POST['conf_type']) AND $_POST['conf_type'] ) ? $_POST['conf_type'] : $conf['conf_type'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Key?</b>" ,
												  			   $this->ipsclass->adskin->form_input( 'conf_key', ( isset($_POST['conf_key']) AND $_POST['conf_key'] ) ? $_POST['conf_key'] : $conf['conf_key'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Current Value?</b>" ,
												  			   $this->ipsclass->adskin->form_textarea( 'conf_value', $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['conf_value']) AND $_POST['conf_value'] ) ? $_POST['conf_value'] : $conf['conf_value'] ) )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Default Value?</b>" ,
												  			   $this->ipsclass->adskin->form_textarea( 'conf_default', $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['conf_default']) AND $_POST['conf_default'] ) ? $_POST['conf_default'] : $conf['conf_default'] ) )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Setting Extra?</b><div style='color:gray'>Use for creating form element extras.<br />Drop down box use: Key=Value; one per line.</div>" ,
												  			   $this->ipsclass->adskin->form_textarea( 'conf_extra', ( isset($_POST['conf_extra']) AND $_POST['conf_extra'] ) ? $_POST['conf_extra'] : $conf['conf_extra'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Raw PHP code to eval before showing and saving?</b><div style='color:gray'>\$show = 1; is set when showing setting.<br />\$save = 1; is set when saving the setting.<br />Use \$key and \$value when writing PHP code.</div>" ,
												  			   $this->ipsclass->adskin->form_textarea( 'conf_evalphp', ( isset($_POST['conf_evalphp']) AND $_POST['conf_evalphp'] ) ? $_POST['conf_evalphp'] : $conf['conf_evalphp'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Start setting group?</b><div style='color:gray'>Enter title here or leave blank to not start a setting group</div>" ,
												  			   $this->ipsclass->adskin->form_input( 'conf_start_group', ( isset($_POST['conf_start_group']) AND $_POST['conf_start_group'] ) ? $_POST['conf_start_group'] : $conf['conf_start_group'] )
										 		    	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>End setting group?</b><div style='color:gray'>End an opened setting group</div>" ,
												  			   $this->ipsclass->adskin->form_yes_no( 'conf_end_group', ( isset($_POST['conf_end_group']) AND $_POST['conf_end_group'] ) ? $_POST['conf_end_group'] : $conf['conf_end_group'] )
										 		    	)      );

		if ( IN_DEV )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Make a default settings (cannot be removed by user)?</b>" ,
												  			       $this->ipsclass->adskin->form_yes_no( 'conf_protected', ( isset($_POST['conf_protected']) AND $_POST['conf_protected'] ) ? $_POST['conf_protected'] : $conf['conf_protected'] )
										 		    	  )      );
		}

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Add this option into the settings cache?</b>" ,
																  $this->ipsclass->adskin->form_yes_no( 'conf_add_cache', ( isset($_POST['conf_add_cache']) AND $_POST['conf_add_cache'] ) ? $_POST['conf_add_cache'] : $conf['conf_add_cache'] )
														 )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( $button );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();

		$this->ipsclass->admin->output();

	}

	/*-------------------------------------------------------------------------*/
	// VIEW SETTINGS TO EDIT
	/*-------------------------------------------------------------------------*/
	/**
	* Grabs a group of settings by direct ID, search key or via
	* $this->get_by_key
	*
	* @return void;
	*/
	function setting_view( $conf_fields=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$search_key         = isset($this->ipsclass->input['search']) ? trim( urldecode( $this->ipsclass->input['search'] ) ) : '';
		$conf_group         = $this->ipsclass->input['conf_group'];
		$conf_title_keyword = $this->ipsclass->input['conf_title_keyword'];
		$conf_titles        = array();
		$in_group           = array();
		$last_conf_id       = -1;
		$start              = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$end                = 150;
		$this->ipsclass->input['search'] = isset($this->ipsclass->input['search']) ? $this->ipsclass->input['search'] : '';
		$load_modules       = array();
		$key_array          = array();
		$_show_error        = 0;
		$html               = '';

		//-----------------------------------------
		// Sort out key stuff
		//-----------------------------------------

		$this->get_by_key = $this->get_by_key  ? $this->get_by_key : ( $conf_title_keyword  ? $conf_title_keyword : '' );

    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------

        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->from_acp = 1;
        $this->han_editor->init();

        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------

        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;

        $this->parser->bypass_badwords = 1;

		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'acp_help', 'where' => "is_setting=1" ) );
		$this->ipsclass->DB->exec_query();

		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->help_settings[ $r['page_key'] ] = $r;
		}

		//-----------------------------------------
		// Page headers
		//-----------------------------------------

		$this->ipsclass->admin->page_title  = $this->ipsclass->admin->page_title  ? $this->ipsclass->admin->page_title  : "System Configuration Settings";
		$this->ipsclass->admin->page_detail = $this->ipsclass->admin->page_detail ? $this->ipsclass->admin->page_detail : "This section contains all the configuration options for your IPB.<br />If you wish to leave an entry blank, please use the keyword: <b>{blank}</b> or enter a zero: <b>0</b>.";

		//-----------------------------------------
		// Already got our fields?
		//-----------------------------------------

		if ( is_array( $conf_fields ) AND count( $conf_fields ) )
		{
			foreach( $conf_fields as $_id => $_data )
			{
				if ( ! $conf_group )
				{
					$conf_group = $_data['conf_title_id'];
					$conf_title = $_data['conf_title_title'];
				}

				if ( $_data['conf_title_module'] )
				{
					$load_modules[ $r['conf_title_module'] ] = $_data['conf_title_module'];
				}

				$this->ipsclass->my_group_helpkey = $_data['conf_title_keyword'];
			}

			$title      = "Settings for group: {$group_title}";
			$conf_entry = $conf_fields;
		}
		else
		{
			//-----------------------------------------
			// Grabbing by key?
			//-----------------------------------------

			if ( $this->get_by_key )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings_titles' ) );
				$this->ipsclass->DB->simple_exec();

				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					if ( $r['conf_title_keyword'] == $this->get_by_key )
					{
						$conf_group = $r['conf_title_id'];
						$conf_title = $r['conf_title_title'];

						$this->ipsclass->my_group_helpkey = $r['conf_title_keyword'];

						if ( $r['conf_title_module'] )
						{
							$load_modules[ $r['conf_title_module'] ] = $r['conf_title_module'];
						}
					}
				}
			}

			//-----------------------------------------
			// Check...
			//-----------------------------------------

			if ( ! $conf_group and ! $search_key )
			{
				$this->ipsclass->main_msg = "No group was passed, please try again.";
				$this->setting_start();
			}

			//-----------------------------------------
			// Get Groups
			//-----------------------------------------

			$this->setting_get_groups();

			//-----------------------------------------
			// Did we search?
			//-----------------------------------------

			if ( $search_key )
			{
				$keywords = strtolower($search_key);

				$this->ipsclass->DB->cache_add_query( 'settings_search', array( 'keywords' => $keywords, 'limit_a' => $start, 'limit_b' => $end ) );
	    		$this->ipsclass->DB->cache_exec_query();

				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					if ( $r['conf_title_noshow'] == 1 )
					{
						continue;
					}

					if ( $r['conf_title_module'] )
					{
						$load_modules[ $r['conf_title_module'] ] = $r['conf_title_module'];
					}

					$r['conf_start_group']       = "";
					$r['conf_end_group']         = "";
					$conf_entry[ $r['conf_id'] ] = $r;
				}

				if ( ! count( $conf_entry ) )
				{
					$this->ipsclass->main_msg = "Your search for '$keywords' produced no matches.";
					$this->setting_start();
				}

				$title = "Searched for: ".$keywords;
			}

			//-----------------------------------------
			// Or not...
			//-----------------------------------------

			else
			{
				$in_g = "";

				$this->ipsclass->DB->simple_construct( array( 'select'   => 'c.*',
															  'from'     => array( 'conf_settings' => 'c' ),
															  'where'    => "c.conf_group='{$conf_group}'",
															  'order'    => 'c.conf_position, c.conf_title',
															  'limit'    => array( $start,$end ),
															  'add_join' => array( 0 => array(    'select' => 'cc.conf_title_title, cc.conf_title_module, cc.conf_title_keyword',
																								  'from'   => array( 'conf_settings_titles' => 'cc' ),
																								  'where'  => 'cc.conf_title_id=c.conf_group',
																								  'type'   => 'left'
																					)           )
															   ) );

				$this->ipsclass->DB->simple_exec();

				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$group_title = $r['conf_title_title'];

					$conf_entry[ $r['conf_id'] ] = $r;

					$this->ipsclass->my_group_helpkey = $r['conf_title_keyword'];

					if ( $r['conf_end_group'] )
					{
						$in_g = 0;
					}

					if ( $in_g )
					{
						$this->in_group[] = $r['conf_id'];
					}

					if ( $r['conf_start_group'] )
					{
						$in_g = 1;
					}

					if ( $r['conf_title_module'] )
					{
						$load_modules[ $r['conf_title_module'] ] = $r['conf_title_module'];
					}
				}

				$title = "Settings for group: {$group_title}";
			}
		}

		//-----------------------------------------
		// start form
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'       , 'setting_update'              ),
																			 2 => array( 'act'        , 'op'                          ),
																			 3 => array( 'id'         , $conf_group                   ),
																			 4 => array( 'search'     , $search_key                   ),
																			 5 => array( 'section'    , 'tools'                       ),
																			 6 => array( 'bounceback' , $this->return_after_save      ),
																	), "theAdminForm", "onclick='return ValidateForm()'", "postingform"      );

		//-----------------------------------------
		// Get settings in group
		//-----------------------------------------

		$pages = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $this->setting_groups[$conf_group]['conf_title_count'],
														  	  	  'PER_PAGE'    => $end,
																  'CUR_ST_VAL'  => $start,
																  'L_SINGLE'    => "",
																  'L_MULTI'     => "Multi Page",
																  'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=setting_view&search={$search_key}",
																  'search'      => $search_key,
														 )     );

		//-----------------------------------------
		// start table
		//-----------------------------------------

		$html .=  "<div class='tableborder'>
				   <div class='tableheaderalt'>
				   <table cellpadding='0' cellspacing='0' border='0' width='100%'>
				   <tr>
					<td align='left' width='70%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>$title</td>
					<td align='right' nowrap='nowrap' width='30%'>";

		if ( ! $search_key AND ! $this->get_by_key )
		{
			$html .=  $this->ipsclass->adskin->js_make_button("Add New Setting"  , $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=settingnew&conf_group=".$conf_group).'&nbsp;';
			$html .=  "<input type='submit' name='reorder' value='Reorder' class='realdarkbutton' />";
		}

		$html .= "&nbsp;&nbsp;</td>
								 </tr>
								 </table>
								 </div>
						         ";

		//-----------------------------------------
		// Pre-parse...
		//-----------------------------------------

		if ( is_array( $load_modules ) AND count( $load_modules ) )
		{
			foreach( $load_modules as $_module )
			{
				$_module = $this->ipsclass->txt_filename_clean( $_module );
				$_name   = 'setting_' . str_replace( '.php', '', $_module );
				$_file   = ROOT_PATH . 'sources/components_acp/setting_plugins/'.$_module;

				if ( file_exists( $_file ) )
				{
					require_once( $_file );
					$_plugin           =  new $_name;
					$_plugin->ipsclass =& $this->ipsclass;

					$conf_entry = $_plugin->settings_pre_parse( $conf_entry );
				}
			}
		}

		//-----------------------------------------
		// Continue...
		//-----------------------------------------

		if ( is_array( $conf_entry ) and count( $conf_entry ) )
		{
			foreach( $conf_entry as $r )
			{
				if ( ! $_show_error )
				{
					if ( isset($r['_error']) AND $r['_error'] )
					{
						$_show_error = 1;
					}
				}

				$html .= $this->_setting_process_entry( $r );
			}
		}

		if ( $_show_error )
		{
			$this->ipsclass->html .= $this->ipsclass->skin_acp_global->warning_box( "Settings Error", "One or more of the settings returned an error.<br /><strong>The settings were NOT updated.</strong>" ) . "<br />";
		}

		$this->ipsclass->html .= $html;

		$this->ipsclass->html .= "<input type='hidden' name='settings_save' value='".implode(",",$this->key_array)."' />";

		$this->ipsclass->html .= "<div class='tablesubheader' align='center'><input type='submit' value='Update Settings' class='realdarkbutton' /></div></div></form>";

		$this->ipsclass->html .= "<br /><br /><div align='right'><b><em>Settings Quick Jump</em></b>".$this->setting_make_dropdown()."</div>";
		$this->ipsclass->admin->output();
	}


	function setting_help()
	{
		$key = trim( $this->ipsclass->input['key'] );

        //-----------------------------------------
        // Load and parse help key file
        //-----------------------------------------

		$help = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'acp_help', 'where' => "page_key='{$key}' AND is_setting=1" ) );

		if( !$help['page_key'] )
		{
			$this->ipsclass->html = $this->ipsclass->skin_acp_global->warning_box( "Help text not found", "We're sorry - the help text you were looking for could not be found" );
		}
		else
		{
			$help['title'] = str_replace( '{base_url}', $this->ipsclass->base_url, $help['help_title'] );
			$help['helptext'] = str_replace( '{base_url}', $this->ipsclass->base_url, $help['help_body'] );

			$help['title'] = str_replace( '{skin_url}', $this->ipsclass->skin_acp_url, $help['title'] );
			$help['helptext'] = str_replace( '{skin_url}', $this->ipsclass->skin_acp_url, $help['helptext'] );

			$this->ipsclass->html = $this->html->popup_help( $help['title'], $help['helptext'] );
		}

		$this->ipsclass->admin->print_popup();
	}

	//-----------------------------------------
	// Settings show - core routine...
	//-----------------------------------------

	function _setting_process_entry($r)
	{
		$form_element  = "";
		$dropdown      = array();
		$start         = "";
		$end           = "";
		$revert_button = "";
		$tablerow1        = "tablerow1";
		$tablerow2        = "tablerow2";

		$key   = $r['conf_key'];
		$value = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];

		$show  = 1;

		$r['conf_evalphp'] = str_replace("&#092;", "\\", stripslashes($r['conf_evalphp']));

		//-----------------------------------------
		// Default?
		//-----------------------------------------

		$css = "";

		if ( $r['conf_value'] != "" and ( $r['conf_value'] != $r['conf_default'] ) )
		{
			$tablerow1        = "tablerow1shaded";
			$tablerow2        = "tablerow2shaded";
			$revert_button = "<div style='width:auto;float:right;padding-top:2px;padding-bottom:3px;'><a href='{$this->ipsclass->base_url}&section=tools&code=op&code=setting_revert&id={$r['conf_id']}&conf_group={$r['conf_group']}&search={$this->ipsclass->input['search']}' title='Revert to default value'><img src='{$this->ipsclass->skin_acp_url}/images/te_revert.gif' alt='X' border='0' /></a></div>";

			if( $r['conf_type'] == 'editor' )
			{
				$revert_button .= "<br clear='all' />";
			}
		}

		//-----------------------------------------
		// Evil eval
		//-----------------------------------------

		if ( $r['conf_evalphp'] )
		{
			if ( $this->ipsclass->hax_check_for_executable_code( $r['conf_evalphp'] ) === TRUE )
			{
				$this->ipsclass->main_msg = "Setting PHP code not evaluated. EVAL code contains PHP command keywords.";
			}
			else
			{
				$save = 0;
				$show = 1;
				eval( $r['conf_evalphp'] );
			}
		}

		switch( $r['conf_type'] )
		{
			case 'input':
				$form_element = $this->ipsclass->adskin->form_input( $key, str_replace( "'", "&#39;", str_replace( '"', "&quot;", $value ) ) );
				break;

			case 'textarea':
				$form_element = $this->ipsclass->adskin->form_textarea( $key, $value, 45, 5 );
				break;

			case 'editor':
				$this->parser->parse_html    = 1;
				$this->parser->parse_nl2br   = 1;
				$this->parser->parse_smilies = 1;
				$this->parser->parse_bbcode  = 1;

				if ( $this->han_editor->method == 'rte' )
				{
					$value = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $value ) );
					$value = $this->parser->convert_ipb_html_to_html( $value );
				}
				else
				{
					$value = $this->parser->pre_edit_parse( $value );
				}

				$form_element = $this->han_editor->show_editor( $value, $key );

				break;

			case 'yes_no':
				$form_element = $this->ipsclass->adskin->form_yes_no( $key, $value );
				break;

			default:

				if ( $r['conf_extra'] )
				{
					if ( $r['conf_extra'] == '#show_forums#' )
					{
						//-----------------------------------------
						// Require the library
						// (Not a building with books)
						//-----------------------------------------

						$this->ipsclass->forums->forums_init();

						require_once( ROOT_PATH.'sources/lib/admin_forum_functions.php' );

						$aff = new admin_forum_functions();
						$aff->ipsclass =& $this->ipsclass;

						$dropdown = $aff->ad_forums_forum_list(1);
					}
					else if ( $r['conf_extra'] == '#show_groups#' )
					{
						$this->ipsclass->DB->simple_construct( array( 'select' => 'g_id, g_title', 'from' => 'groups' ) );
						$this->ipsclass->DB->simple_exec();

						while( $row = $this->ipsclass->DB->fetch_row() )
						{
							if( $row['g_id'] == $this->ipsclass->vars['admin_group'] AND $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
							{
								continue;
							}
							
							$dropdown[] = array( $row['g_id'], $row['g_title'] );
						}
					}
					else if ( $r['conf_extra'] == '#show_skins#' )
					{
						$dropdown = $this->ipsclass->admin->skin_get_skin_dropdown();
					}
					else
					{
						foreach( explode( "\n", $r['conf_extra'] ) as $l )
						{
							list ($k, $v) = explode( "=", $l );
							if ( $k != "" and $v != "" )
							{
								$dropdown[] = array( trim($k), trim($v) );
							}
						}
					}
				}

				if ( $r['conf_type'] == 'dropdown' )
				{
					$form_element = $this->ipsclass->adskin->form_dropdown( $key, $dropdown, $value );
				}
				else
				{
					$form_element = $this->ipsclass->adskin->form_multiselect( $key, $dropdown, explode( ",", $value ), 5 );
				}

				break;
		}

		$help_key = '';

		if( array_key_exists( $r['conf_key'], $this->help_settings ) )
		{
			$help_key = "&nbsp;&nbsp;<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_help&key={$r['conf_key']}' onclick=\"ipsclass.pop_up_window( '{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_help&key={$r['conf_key']}', 400, 500, 'SettingHelp' );return false;\" title='{$this->help_settings[ $r['conf_key'] ]['help_mouseover']}'><img src='{$this->ipsclass->skin_acp_url}/images/about.png' border='0' alt='{$this->help_settings[ $r['conf_key'] ]['help_mouseover']}' /></a>";
		}

		//-----------------------------------------
		// Error?
		//-----------------------------------------

		if ( isset($r['_error']) AND $r['_error'] )
		{
			$form_element = "<div class='input-warn-content'>".$r['_error']."</div>" . $form_element;
		}

		//-----------------------------------------
		// Continue
		//-----------------------------------------

		$delete  = "&#0124; <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_delete&id={$r['conf_id']}' title='key: {$r['conf_key']}'>Delete</a>";
		$edit    = "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_showedit&id={$r['conf_id']}' title='id: {$r['conf_id']}'>Edit</a>";
		$reorder = 1;

		if ( $r['conf_protected'] and ! IN_DEV )
		{
			$delete  = "";
			$edit    = "";
			$reorder = 0;
		}

		if ( $r['conf_start_group'] )
		{
			$start  = "<div style='background-color:#EEF2F7;padding:5px'>
						<div class='tableborder'>
						<div class='tablesubheader'>{$r['conf_start_group']}</div>";
		}
		else
		{
			if ( ! in_array( $r['conf_id'], $this->in_group ) and ! $r['conf_end_group'] )
			{
				$start  = "<div style='background-color:#EEF2F7;padding:5px'>
							<div style='border:1px solid #8394B2'>
							";
			}
		}

		if ( $r['conf_end_group'] )
		{
			$end = "</div></div>";
		}
		else
		{
			if ( ! in_array( $r['conf_id'], $this->in_group ) and ! $r['conf_start_group'] )
			{
				$end  = "</div></div>";
			}
		}

		//-----------------------------------------
		// Search hi-lite
		//-----------------------------------------

		$this->ipsclass->input['search'] = isset($this->ipsclass->input['search']) ? $this->ipsclass->input['search'] : '';

		if ( $this->ipsclass->input['search'] )
		{
			$this->ipsclass->input['search'] = $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['search'] );

			$r['conf_title']       = preg_replace( "/(".$this->ipsclass->input['search'].")/i", "<span style='background:#FCFDD7'>\\1</span>", $r['conf_title'] );

			$r['conf_description'] = preg_replace( "/(".$this->ipsclass->input['search'].")/i", "<span style='background:#FCFDD7'>\\1</span>", $r['conf_description'] );
			$r['conf_description'] .= "<br /><br /><i><b>Setting Group:</b> {$r['conf_title_title']}</i>";
		}

		$html = "$start
							<table cellpadding='5' cellspacing='0' border='0' width='100%'>
							 <tr>
							 <td width='30%' class='$tablerow1'><b>{$r['conf_title']}</b>{$help_key}<div style='color:gray'>{$r['conf_description']}</div></td>
							 <td width='55%' class='$tablerow2'>{$revert_button}<div align='left' style='width:auto;'>{$form_element}</div></td>
							 ";

		if ( ! $this->get_by_key AND ( $edit or $delete ) )
		{
			$html .= "<td width='10%' class='$tablerow1' align='center'>
								   {$edit}
								   {$delete}
								</td>";
		}

		if ( ! $this->ipsclass->input['search'] and $reorder AND ! $this->get_by_key )
		{
			$html .= "<td width='5%' class='$tablerow2' align='center'><input type='text' size='2' name='cp_{$r['conf_id']}' value='{$r['conf_position']}' class='realdarkbutton' /></td>";
		}

		$html .= "</tr>
				  </table>
				  $end
				 ";

		$this->key_array[] = preg_replace( "/\[\]$/", "", $key );

		return $html;
	}

	//-----------------------------------------
	// Settings Start
	//-----------------------------------------

	function setting_start()
	{
		$this->ipsclass->admin->page_title  = "System Configuration Settings";
		$this->ipsclass->admin->page_detail = "This section contains all the configuration options for your IPB.";

		//-----------------------------------------
		// Are we, like, in dev or what?
		//-----------------------------------------

		if ( IN_DEV )
		{
			$last_update = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																			 'from'   => 'cache_store',
																			 'where'  => "cs_key='in_dev_setting_update'" ) );

			if ( ! $last_update['cs_value'] )
			{
				$this->ipsclass->DB->do_delete( 'cache_store', "cs_key='in_dev_setting_update'" );
				$this->ipsclass->DB->do_insert( 'cache_store', array( 'cs_value' => time(), 'cs_key' => "in_dev_setting_update" ) );
				$last_update = time();
			}

			$last_settings_save = intval( @filemtime( ROOT_PATH . 'resources/settings.xml' ) );

			if ( $last_settings_save > $last_update['cs_value'] )
			{
				$_mtime  = $this->ipsclass->get_date( $last_settings_save     , 'JOINED' );
				$_dbtime = $this->ipsclass->get_date( $last_update['cs_value'], 'JOINED' );

				$_html = $this->ipsclass->skin_acp_global->warning_box( "settings.xml File Updated",
																		"The 'resources/settings.xml' file has been updated. Please visit <a href='{$this->ipsclass->base_url}&amp;section=tools'>this page</a> to re-import it to make sure your settings are up-to-date
																		<br />Last modified time for 'settings.xml': $_mtime.
																		<br />Last import run: $_dbtime" ) . "<br />";

				$this->ipsclass->html .= $_html;
			}
		}

		//$this->setting_titles_check();

		//-----------------------------------------
		// start table
		//-----------------------------------------

		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "2%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "88%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "10%" );

		$basic_title = "<table cellpadding='0' cellspacing='0' border='0' width='100%'>
						<tr>
						 <td align='left' width='40%' style='font-size:12px; vertical-align:middle;font-weight:bold; color:#FFF;'>System Settings</td>
						 <td align='right' width='60%'><form method='post' action='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_view'><input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' /><input type='text' size='25' onclick='this.value=\"\"' value='Search Settings...' name='search' class='realbutton' />&nbsp;<input type='submit' class='realdarkbutton' value='Go' /></form>"
						 ."&nbsp;&nbsp;</td>
						</tr>
						</table>";

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table($basic_title);

		//-----------------------------------------
		// Get groups
		//-----------------------------------------

		$this->setting_get_groups();

		foreach( $this->setting_groups as $r )
		{
			if ( $r['conf_title_noshow'] )
			{
				$hidden = ' (Hidden)';
			}
			else
			{
				$hidden = '';
			}

			if( IN_DEV )
			{
				$in_dev_extra = "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=MOD_export_setting&conf_group={$r['conf_title_id']}' title='Export Group'><img src='{$this->ipsclass->adskin->img_url}/images/icons_menu/export.png' border='0' alt='Export' /></a>";
			}
			else
			{
				$in_dev_extra = '';
			}

			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<div align='center'><img src='{$this->ipsclass->adskin->img_url}/images/settings_folder.gif' border='0' alt='Folder' /></div>",
																   "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_view&conf_group={$r['conf_title_id']}'><b>{$r['conf_title_title']}</b></a>$hidden <span style='color:gray'>(".intval($r['conf_title_count'])." settings)</span><div style='color:gray'>{$r['conf_title_desc']}</div>" ,
												  				   array("<div align='center' style='white-space:nowrap'>
												  				          <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=settinggroup_showedit&id={$r['conf_title_id']}' title='Edit this setting groups details'><img src='{$this->ipsclass->adskin->img_url}/images/icons_menu/edit.gif' border='0' alt='Edit'  /></a>
																          <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=settinggroup_delete&id={$r['conf_title_id']}' title='Delete this setting group'><img src='{$this->ipsclass->adskin->img_url}/images/icons_menu/delete.gif' border='0' alt='Delete'  /></a>
																          <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=settinggroup_resync&id={$r['conf_title_id']}' title='Recount this setting groups options'><img src='{$this->ipsclass->adskin->img_url}/images/acp_resync.gif' border='0' alt='Recount'  /></a>
																          {$in_dev_extra}
																          </div>", 1, 'tablerow3' )
										 					  )      );
		}

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( array("<div align='center' style='white-space:nowrap'>".
																	  $this->ipsclass->adskin->js_make_button("Add Setting Group", $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=settinggroup_new")."</div>", 3, 'tablesubheader' )
										 					  )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();


		//-----------------------------------------
		// Import partial settings?
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'          , 'settings_do_import'    ),
																 2 => array( 'act'           , 'op'        ),
																 3 => array( 'MAX_FILE_SIZE' , '10000000000' ),
																 4 => array( 'section', $this->ipsclass->section_code ),
													 ) , "uploadform", " enctype='multipart/form-data'"      );

		//-----------------------------------------

		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "50%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "50%" );

		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "XML settings file tools" );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Upload XML settings file from your computer</b><div style='color:gray'>Duplicate entries will not be overwritten but the default setting and other options will be updated. The file must end with either '.xml' or '.xml.gz'</div>" ,
										  				        			 $this->ipsclass->adskin->form_upload(  )
											                        )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b><u>OR</u> enter the filename of the XML settings file</b><div style='color:gray'>The file must be uploaded into the forum's root folder</div>" ,
										  				         			$this->ipsclass->adskin->form_input( 'file_location', 'resources/settings.xml'  )
											                        )      );

		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Tool To Run</b><div style='color:gray'>Please choose the tool you wish to run</div>" ,
										  				         			  $this->ipsclass->adskin->form_dropdown( 'tool', array( 0 => array( 'import', 'Import Settings' ), 1 => array( 'check', 'Show New &amp; Deleted Settings' ) )  )
																	)      );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run Tool");

		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();

		//-----------------------------------------
		// Other & Dev options
		//-----------------------------------------

		$this->ipsclass->html .= "<br /><br/ ><div align='center'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_someexport_start'>Export Selected Settings</a></div>";

		if ( IN_DEV )
		{
			$this->ipsclass->html .= "<br /><div align='center'>Developer Options: <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=setting_allexport'>Export all to XML</a></div>";
		}

		$this->ipsclass->admin->output();
	}

	//-----------------------------------------
	// Settings Update
	//-----------------------------------------

	function setting_update( $donothing="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$bounceback   = str_replace( '&amp;', '&', $this->ipsclass->input['bounceback'] );
		$load_modules = array();
		$db_fields    = array();

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $this->ipsclass->input['id'] and ! $this->ipsclass->input['search'] )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->setting_start();
		}

		//-----------------------------------------
		// Reorder?
		//-----------------------------------------

		if ( isset($this->ipsclass->input['reorder']) AND $this->ipsclass->input['reorder'] )
		{
			foreach ($this->ipsclass->input as $key => $value)
			{
				if ( preg_match( "/^cp_(\d+)$/", $key, $match ) )
				{
					if ( isset( $this->ipsclass->input[$match[0]]) )
					{
						$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_position' => $this->ipsclass->input[$match[0]] ), 'conf_id='.intval($match[1]) );
					}
				}
			}

			$this->ipsclass->main_msg = "Settings reordered";

			$this->ipsclass->input['conf_group'] = $this->ipsclass->input['id'];

			$this->setting_view();
		}

		//-----------------------------------------
		// check...
		//-----------------------------------------

		$fields = explode(",", trim($this->ipsclass->input['settings_save']) );

		if ( ! count($fields ) )
		{
			$this->ipsclass->main_msg = "No fields were passed to be saved";
			$this->ipsclass->settings_view();
		}

		//-----------------------------------------
		// Get info from DB
		//-----------------------------------------

		$this->ipsclass->DB->build_query( array( 'select' => 's.*',
												 'from'   => array( 'conf_settings' => 's' ),
												 'where'  => "conf_key IN ('".implode( "','", $fields )."')",
												 'add_join' => array( 0 => array( 'select' => 'c.*',
																				  'from'   => array( 'conf_settings_titles' => 'c' ),
																				  'where'  => 'c.conf_title_id=s.conf_group',
																				  'type'   => 'inner' ) ) ) );

		$this->ipsclass->DB->exec_query();

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['conf_title_module'] )
			{
				$load_modules[ $r['conf_title_module'] ] = $r['conf_title_module'];
			}

			$r['_value'] = $_POST[ $r['conf_key'] ];

			$db_fields[ $r['conf_id']  ] = $r;
		}

		//-----------------------------------------
		// Post-parse...
		//-----------------------------------------

		if ( is_array( $load_modules ) AND count( $load_modules ) )
		{
			foreach( $load_modules as $_module )
			{
				$_module = $this->ipsclass->txt_filename_clean( $_module );
				$_name   = 'setting_' . str_replace( '.php', '', $_module );
				$_file   = ROOT_PATH . 'sources/components_acp/setting_plugins/'.$_module;

				if ( file_exists( $_file ) )
				{
					require_once( $_file );
					$_plugin           =  new $_name;
					$_plugin->ipsclass =& $this->ipsclass;

					$db_fields = $_plugin->settings_post_parse( $db_fields );
				}
			}

			//-----------------------------------------
			// Got any errors?
			//-----------------------------------------

			$_error = 0;

			foreach( $db_fields as $_id => $data )
			{
				if ( $data['_error'] )
				{
					$_error = 1;
					break;
				}
			}

			if ( $_error )
			{
				$this->setting_view( $db_fields );
				return;
			}
		}

		//-----------------------------------------
		// Continue
		//-----------------------------------------

		foreach( $db_fields as $id => $data )
		{
			//-----------------------------------------
			// INIT
			//-----------------------------------------

			$key = $data['conf_key'];

			//-----------------------------------------
			// Evil eval
			//-----------------------------------------

			if ( $data['conf_evalphp'] )
			{
				if ( $this->ipsclass->hax_check_for_executable_code( $data['conf_evalphp'] ) === TRUE )
				{
					$this->ipsclass->main_msg = "Setting PHP code not evaluated. EVAL code contains PHP command keywords.";
				}
				else
				{
					$value = $data['_value'];

					$show = 0;
					$save = 1;
					eval( $data['conf_evalphp'] );
					$data['_value'] = $_POST[ $key ];
				}
			}

			if ( $data['conf_type'] == 'editor' )
			{
				if ( ! $this->editor_loaded )
				{
			    	//-----------------------------------------
			        // Load and config the std/rte editors
			        //-----------------------------------------

			        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
			        $this->han_editor           = new han_editor();
			        $this->han_editor->ipsclass =& $this->ipsclass;
			        $this->han_editor->from_acp = 1;
			        $this->han_editor->init();

			        //-----------------------------------------
			        // Load and config the post parser
			        //-----------------------------------------

			        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
			        $this->parser                      =  new parse_bbcode();
			        $this->parser->ipsclass            =& $this->ipsclass;
			        $this->parser->allow_update_caches = 1;

			        $this->parser->bypass_badwords = 1;

			        $this->editor_loaded = 1;
		        }

		        $data['_value'] = $this->han_editor->process_raw_post( $key );

				$this->parser->parse_smilies   = 1;
				$this->parser->parse_html      = 1;
				$this->parser->parse_bbcode    = 1;

				$data['_value']				   = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $data['_value'] ) );
			}

			$data['_value'] = $this->ipsclass->txt_stripslashes( str_replace( "\r", "", $data['_value'] ) );

			if ( ($data['_value'] != $data['conf_default']) )
			{
				$value = str_replace( "&#39;", "'", $this->ipsclass->txt_stripslashes($data['_value']) );

				$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $value ), 'conf_id='.$data['conf_id'] );
			}
			else if ( $this->ipsclass->input[ $key ] != "" and ( $this->ipsclass->input[ $key ] == $data['conf_default'] ) and $data['conf_value'] != '' )
			{
				$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => '' ), 'conf_id='.$data['conf_id'] );
			}
            else if ( isset($this->ipsclass->input[ $key ]) AND empty($this->ipsclass->input[ $key ]) )
            {
                $this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => '' ), 'conf_id='.$data['conf_id'] );
            }
		}

		$this->ipsclass->input['conf_group'] = $this->ipsclass->input['id'];

		$this->ipsclass->main_msg = "Settings updated";

		$this->setting_rebuildcache();

		//-----------------------------------------
		// We're bouncing back (Boing boing)
		//-----------------------------------------

		if ( $bounceback )
		{
			$this->ipsclass->admin->redirect_noscreen( $this->ipsclass->base_url.'&'.$bounceback );
		}

		//-----------------------------------------
		// Still here?
		//-----------------------------------------

		if ( ! $donothing )
		{
			$this->setting_view();
		}
	}

	//-----------------------------------------
	// Settings Save Form
	//-----------------------------------------

	function setting_save($type='add')
	{
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->setting_form( $type );
			}
		}

		//-----------------------------------------
		// EVAL check
		//-----------------------------------------

		if ( $this->ipsclass->hax_check_for_executable_code( $_POST['conf_evalphp'] ) === TRUE )
		{
			$this->ipsclass->main_msg = "You cannot use executable PHP function keywords such as (include, require, include_once, require_once, exec, system and passthru).";
			$this->setting_form( $type );
		}

		//-----------------------------------------
		// check...
		//-----------------------------------------

		$conf_group = ( isset($this->ipsclass->input['conf_newgroup']) ANd $this->ipsclass->input['conf_newgroup'] ) ? $this->ipsclass->input['conf_newgroup'] : $this->ipsclass->input['conf_group'];

		$array = array( 'conf_title'       => $this->ipsclass->input['conf_title'],
						'conf_description' => $this->ipsclass->txt_stripslashes( $_POST['conf_description'] ),
						'conf_group'       => $this->ipsclass->input['conf_group'],
						'conf_type'        => $this->ipsclass->input['conf_type'],
						'conf_key'         => $this->ipsclass->input['conf_key'],
						'conf_value'       => $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_UNhtmlspecialchars($_POST['conf_value']) ),
						'conf_default'     => $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_UNhtmlspecialchars($_POST['conf_default']) ),
						'conf_extra'       => $this->ipsclass->txt_stripslashes( $_POST['conf_extra'] ),
						'conf_evalphp'     => ($this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] ) ? $this->ipsclass->txt_stripslashes( $_POST['conf_evalphp'] ) : '',
						'conf_protected'   => intval( $this->ipsclass->input['conf_protected'] ),
						'conf_position'    => intval( $this->ipsclass->input['conf_position'] ),
						'conf_start_group' => $this->ipsclass->input['conf_start_group'],
						'conf_end_group'   => $this->ipsclass->input['conf_end_group'],
						'conf_add_cache'   => intval( $this->ipsclass->input['conf_add_cache'] ),
					 );


		if ( $type == 'add' )
		{
			$this->ipsclass->DB->do_insert( 'conf_settings', $array );
			$this->ipsclass->main_msg = 'New Setting Added';

			$this->ipsclass->DB->simple_exec_query( array( 'update' => 'conf_settings_titles', 'set' => 'conf_title_count=conf_title_count+1', 'where' => 'conf_title_id='.$this->ipsclass->input['conf_group'] ) );

		}
		else
		{
			$this->ipsclass->DB->do_update( 'conf_settings', $array, 'conf_id='.intval($this->ipsclass->input['id']) );
			$this->ipsclass->main_msg = 'Setting Edited';
		}

		$this->setting_rebuildcache();

		$this->setting_view();
	}

	//-----------------------------------------
	// Settings Revert
	//-----------------------------------------

	function setting_revert()
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);

		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->setting_form();
		}

		$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => 'conf_id='.$this->ipsclass->input['id'] ) );

		//-----------------------------------------
		// Revert...
		//-----------------------------------------

		$this->ipsclass->DB->simple_exec_query( array( 'update' => 'conf_settings', 'set' => "conf_value=''", 'where' => 'conf_id='.$this->ipsclass->input['id'] ) );

		$this->ipsclass->main_msg = "Configuration setting reverted back to default.";

		$this->setting_rebuildcache();

		$this->setting_view();

	}

	//-----------------------------------------
	// Settings Delete
	//-----------------------------------------

	function setting_delete()
	{
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->setting_form();
		}

		$conf = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => 'conf_id='.intval($this->ipsclass->input['id']) ) );

		//-----------------------------------------
		// Delete...
		//-----------------------------------------

		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'conf_settings', 'where' => 'conf_id='.intval($this->ipsclass->input['id']) ) );

		$this->ipsclass->DB->simple_exec_query( array( 'update' => 'conf_settings_titles', 'set' => 'conf_title_count=conf_title_count-1', 'where' => 'conf_title_id='.$conf['conf_group'] ) );

		$this->ipsclass->main_msg = "Configuration Setting Deleted";

		$this->setting_rebuildcache();

		$this->ipsclass->input['conf_group'] = $conf['conf_group'];
		$this->setting_view();
	}


	//-----------------------------------------
	// BBCODE Rebuild Cache
	//-----------------------------------------

	function setting_rebuildcache()
	{
		$this->ipsclass->cache['settings'] = array();

		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings', 'where' => 'conf_add_cache=1' ) );
		$info = $this->ipsclass->DB->simple_exec();

		while ( $r = $this->ipsclass->DB->fetch_row($info) )
		{
			$value = $r['conf_value'] != "" ?  $r['conf_value'] : $r['conf_default'];

			if ( $value == '{blank}' )
			{
				$value = '';
			}

			$this->ipsclass->cache['settings'][ $r['conf_key'] ] = $this->ipsclass->txt_stripslashes($value);

			$this->ipsclass->vars[ $r['conf_key'] ] = $this->ipsclass->cache['settings'][ $r['conf_key'] ];
		}

		$this->ipsclass->update_cache( array( 'name' => 'settings', 'array' => 1, 'deletefirst' => 1 ) );
	}

	//-----------------------------------------
	// Setting get cache
	//-----------------------------------------

	function setting_get_groups()
	{
		$this->setting_groups = array();

		if ( IN_DEV )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings_titles', 'order' => 'conf_title_title' ) );
			$this->ipsclass->DB->simple_exec();
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'conf_settings_titles', 'where' => 'conf_title_noshow=0', 'order' => 'conf_title_title' ) );
			$this->ipsclass->DB->simple_exec();
		}

		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->setting_groups[ $r['conf_title_id'] ]             = $r;
			$this->setting_groups_by_key[ $r['conf_title_keyword'] ] = $r;
		}
	}

	//-----------------------------------------
	// Make drop down of available titles
	//-----------------------------------------

	function setting_make_dropdown()
	{
		if ( ! is_array( $this->setting_groups ) )
		{
			$this->setting_get_groups();
		}

		$ret = "<form method='post' action='{$this->ipsclass->base_url}&section=tools&code=setting_view'>
		        <select name='conf_group' class='dropdown'>";

		foreach( $this->setting_groups as $id => $data )
		{
			$ret .= ( $id == $this->ipsclass->input['conf_group'] )
				  ? "<option value='{$id}' selected='selected'>{$data['conf_title_title']}</option>"
				  : "<option value='{$id}'>{$data['conf_title_title']}</option>";
		}

		return $ret."\n</select><input type='submit' id='button' value='Go' /></form>";
	}

	//-----------------------------------------
	// Save full text options
	//-----------------------------------------

	function do_fulltext()
	{
		$this->ipsclass->admin-> get_mysql_version();

		if ( $this->ipsclass->DB->sql_can_fulltext() )
		{
			// How many posts do we have?

			$this->ipsclass->DB->simple_construct( array( 'select' => 'COUNT(*) as cnt', 'from' => 'posts' ) );
			$this->ipsclass->DB->simple_exec();

			$result = $this->ipsclass->DB->fetch_row();

			// If over 15,000 posts...

			if ( $result['cnt'] > 15000 )
			{
				// Explain how, why and what to do..

				$this->ipsclass->admin->page_detail = "";
				$this->ipsclass->admin->page_title  = "Unable to continue";

				$this->ipsclass->html .= $this->return_sql_no_no_cant_do_it_sorry_text();

				$this->ipsclass->admin->output();
			}
			else
			{
				// Index away!

				$this->ipsclass->DB->sql_add_fulltext_index( 'topics', 'title' );
				$this->ipsclass->DB->sql_add_fulltext_index( 'posts' , 'post' );
			}
		}
		else
		{
			$this->ipsclass->admin->error("Sorry, the version of MySQL that you are using is unable to use FULLTEXT searches");
		}

		$this->ipsclass->admin->save_log("Full Text Options Updated");

		$query = urlencode( 'Type of search to use' );
		$this->ipsclass->admin->done_screen("Full Text Indexes Rebuilt", "Full Text Settings", "{$this->ipsclass->form_code}&code=setting_view&search={$query}", "redirect" );
	}

	/*-------------------------------------------------------------------------*/
	// Checks all the settings titles to ensure they have a key associated.
	/*-------------------------------------------------------------------------*/

	function setting_titles_check()
	{
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------

		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'conf_settings_titles' ) );
		$outer = $this->ipsclass->DB->exec_query();

		while( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			if ( ! $row['conf_title_keyword'] )
			{
				$new_keyword = strtolower( preg_replace( "#[^\d\w]#", "", $row['conf_title_title'] ) );
				$this->ipsclass->DB->do_update( 'conf_settings_titles', array( 'conf_title_keyword' => $new_keyword ), 'conf_title_id='.$row['conf_title_id'] );
			}
		}
	}


	//-----------------------------------------
	//
	// Save config. Does the hard work, so you don't have to.
	//
	//-----------------------------------------

	/**
	* @deprecated
	*/
	function save_config( $new )
	{
		$master = array();

		if ( is_array($new) )
		{
			if ( count($new) > 0 )
			{
				foreach( $new as $field )
				{
					// Handle special..

					if ($field == 'img_ext' or $field == 'avatar_ext' or $field == 'photo_ext')
					{
						$_POST[ $field ] = preg_replace( "/[\.\s]/", "" , $_POST[ $field ] );
						$_POST[ $field ] = str_replace('|', "&#124;", $_POST[ $field ]);
						$_POST[ $field ] = preg_replace( "/,/"     , '|', $_POST[ $field ] );
					}
					else if ($field == 'coppa_address')
					{
						$_POST[ $field ] = nl2br( $_POST[ $field ] );
					}

					if ( $field == 'gd_font' OR $field == 'html_dir' OR $field == 'upload_dir')
					{
						$_POST[ $field ] = str_replace( "'", "&#39;", $_POST[ $field ] );
					}
					else
					{
						$_POST[ $field ] = str_replace( "'", "&#39;", stripslashes($_POST[ $field ]) );
					}

					$master[ $field ] = stripslashes($_POST[ $field ]);
				}

				$this->ipsclass->admin->rebuild_config($master);
			}
		}

		$this->ipsclass->admin->save_log("Board Settings Updated, Back Up Written");

		$this->ipsclass->admin->done_screen("Forum Configurations updated", "Administration CP Home", "act=index" );
	}




	function return_sql_no_no_cant_do_it_sorry_text()
	{
return "
<div style='line-height:150%'>
<span style='font-weight:bold;font-size:14px;'>Unable to automatically create the FULLTEXT indexes</span>
<br /><br />
You have too many posts for an automatic FULLTEXT index creation. It is more than likely that PHP will
time out before the indexes are complete which could cause some index corruption.
<br />
Creating FULLTEXT indexes is a relatively slow process but it's one that's worth doing as it will save you
a lot of time and CPU power when your members search.
<br />
On average, a normal webserver is capable of indexing about 80,000 posts an hour but it is a relatively intense process. If you
are using MySQL 4.0.12+ then this time is reduced substaintially.
<br />
<br />
<strong style='color:red;font-size:14px'>How to manually create the indexes</strong>
<br />
If you have shell (SSH / Telnet) access to mysql, the process is very straightforward. If you do not have access to shell, then you will
have to contact your webhost and ask them to do this for you.
<br /><br />
<strong>Step 1: Initiate mysql</strong>
<br />
In shell type:
<br />
<pre>mysql -u{your_sql_user_name} -p{your_sql_password}</pre>
<br />
Your MySQL username and password can be found in your conf_global.php file
<br />
<br />
<strong>Step 2: Select your database</strong>
<br />
In mysql type:
<br />
<pre>use {your_database_name_here};</pre>
<br />
Make sure you use a trailing semi-colon. Your MySQL database name can be found in conf_global.php
<br /><br />
<strong>Step 3: Indexing the topics table</strong>
<br />
In mysql type:
<br />
<pre>alter table " . SQL_PREFIX . "topics add fulltext(title);</pre>
<br />
This query can take a while depending on the number of topics you have.
<br />
<br />
<strong>Step 4: Indexing the posts table</strong>
<br />
In mysql type:
<br />
<pre>alter table " . SQL_PREFIX . "posts add fulltext(post);</pre>
<br />
This query can take a while depending on the number of posts you have. On average MySQL can index 80,000 posts an hour. If you are using MySQL 4, the time is greatly reduced.
</div>
";
	}


	function settinggroup_export()
	{
		if( empty( $this->ipsclass->input['conf_group'] ) )
		{
			return;
		}

		$this->ipsclass->input['conf_group'] = intval($this->ipsclass->input['conf_group']);

		require_once( KERNEL_PATH . 'class_xml.php' );
		$xml = new class_xml();

		$xml->xml_set_root( 'settingexport', array( 'exported' => time() ) );

		$xml->xml_add_group( 'settinggroup' );

		$conf_title = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'conf_title_title, conf_title_desc, conf_title_noshow, conf_title_keyword, conf_title_module', 'from' => 'conf_settings_titles', 'where' => "conf_title_id = {$this->ipsclass->input['conf_group']}" ) );

		if ( ! $conf_title )
		{
			return;
		}

		foreach( $conf_title as $field_name=>$field_value )
		{
			$content[] = $xml->xml_build_simple_tag( $field_name, $field_value );
		}

		$content[] = $xml->xml_build_simple_tag( 'conf_is_title', 1 );

		$entry[] = $xml->xml_build_entry( 'setting', $content );

		$this->ipsclass->DB->simple_select( '*', 'conf_settings', "conf_group = {$this->ipsclass->input['conf_group']}" );
		$this->ipsclass->DB->exec_query();

		if( $this->ipsclass->DB->get_num_rows() )
		{
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				unset( $content );

				foreach( $row as $field_name=>$field_value )
				{
					$content[] = $xml->xml_build_simple_tag( $field_name, $field_value );
				}

				$content[] = $xml->xml_build_simple_tag( 'conf_title_keyword', $conf_title['conf_title_keyword'] );
				$content[] = $xml->xml_build_simple_tag( 'conf_is_title', 0 );

				$entry[] = $xml->xml_build_entry( 'setting', $content );
			}

			$xml->xml_add_entry_to_group( 'settinggroup', $entry );
		}

		$xml->xml_format_document();

		$this->ipsclass->admin->show_download( $xml->xml_document, 'mod_settings.xml', '', 0 );
	}

}


?>