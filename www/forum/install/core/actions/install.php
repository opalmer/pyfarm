<?php
/**
 * Invision Power Board
 * Action controller for install page
 */

class action_install
{
	var $install;
	
	function action_install( & $install )
	{
		$this->install =& $install;
	}
	
	// SQL - ADMIN - CONF > SETTINGS > ACPPERMS > TEMPLATES > OTHER [ Email Templates ] > SKIN [ New Default ] > Build Caches
	
	function run()
	{
		//-----------------------------------------
		// Any "extra" configs required for this driver?
		//-----------------------------------------
		
		if ( is_array( $this->install->saved_data ) and count( $this->install->saved_data ) )
		{
			foreach( $this->install->saved_data as $k => $v )
			{
				if ( preg_match( "#^__sql__#", $k ) )
				{
					$k = str_replace( "__sql__", "", $k );
				
					$this->install->ipsclass->vars[ $k ] = $v;
				}
			}
		}
		
		/* Switch */
		switch( $this->install->ipsclass->input['sub'] )
		{
			case 'sql':
				$this->install_sql();
			break;
			
			case 'settings':
				$this->install_settings();
			break;
			
			case 'acpperms':
				$this->install_acpperms();
			break;
			
			case 'templates':
				$this->install_templates();
			break;
			
			case 'skin':
				$this->install_skins();
			break;
			
			case 'other':
				$this->install_other();
			break;
			
			case 'caches':
				$this->install_caches();
			break;
			
			default:
				/* Output */
				$this->install->template->append( $this->install->template->install_page() );		
				$this->install->template->next_action = '?p=install&sub=sql';
				$this->install->template->hide_next   = 1;
			break;	
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Installs the SQL
	/*-------------------------------------------------------------------------*/
	/**
	* Installs SQL schematic
	*
	* @return void
	*/
	function install_sql()
	{
		//-----------------------------------------
		// Write config
		//-----------------------------------------
			
		$this->install->write_configuration();
		
		//--------------------------------------------------
		// Any "extra" configs required for this driver?
		//--------------------------------------------------

		if ( file_exists( INS_ROOT_PATH.'sql/'.$this->install->saved_data['sql_driver'].'_install.php' ) )
		{
			require_once( INS_ROOT_PATH.'sql/'.$this->install->saved_data['sql_driver'].'_install.php' );

			$extra_install           =  new install_extra();
			$extra_install->ipsclass =& $this->install->ipsclass;
		}
		
		//-----------------------------------------
		// Run SQL commands
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );
													
		$this->install->ipsclass->converge = new class_converge( $this->install->ipsclass->DB );
				
		include( INS_ROOT_PATH . 'sql/' . $this->install->saved_data['sql_driver'] . '_tables.php' );
		$output[] = "Creating SQL Tables...";
		
		foreach( $TABLE as $q )
		{
			preg_match("/CREATE TABLE (\S+) \(/", $q, $match);

			if ( $match[1] AND $this->install->saved_data['_drop_tables'] )
			{
				$this->install->ipsclass->DB->sql_drop_table( str_replace( 'ibf_', '', $match[1] ) );
			}
				
			$q = preg_replace("/ibf_(\S+?)([\s\.,]|$)/", $this->install->saved_data['db_pre']."\\1\\2", $q);
			
			//-----------------------------------
			// Pass to handler
			//-----------------------------------

			if ( $extra_install AND method_exists( $extra_install, 'process_query_create' ) )
			{
				 $q = $extra_install->process_query_create( $q );
			}
			
			$this->install->ipsclass->DB->query( $q );
		}
		
		//-----------------------------------
		// Create the fulltext index...
		//-----------------------------------

		if ( $this->install->ipsclass->DB->sql_can_fulltext() )
		{
			include( INS_ROOT_PATH . 'sql/' . $this->install->saved_data['sql_driver'] . '_fulltext.php' );
			$output[] = "Building indexes...";		
			
			 foreach( $INDEX as $q )
			 {
			 	$q = preg_replace("/ibf_(\S+?)([\s\.,]|$)/", $this->install->saved_data['db_pre']."\\1\\2", $q);

		        //-----------------------------------
				// Pass to handler
				//-----------------------------------

				if ( $extra_install AND method_exists( $extra_install, 'process_query_index' ) )
				{
					 $q = $extra_install->process_query_index( $q );
				}

				//-----------------------------------
				// Pass query
				//-----------------------------------

		        if ( ! $this->install->ipsclass->DB->query($q) )
		        {
		        	$this->install->template->warning($q."<br /><br />".$this->install->ipsclass->DB->error);
		        }
			}
		}
		
		include( INS_ROOT_PATH . 'sql/' . $this->install->saved_data['sql_driver'] . '_inserts.php' );
		$output[] = "Populating SQL Tables...";		
		
		foreach( $INSERT as $q )
		{
			$q = preg_replace("/ibf_(\S+?)([\s\.,]|$)/", $this->install->saved_data['db_pre']."\\1\\2", $q);
			$q = str_replace( "<%time%>"      , time(), $q );
			
			# Admin's name
			$q = str_replace( "<%admin_name%>", $this->install->saved_data['admin_user'], $q );
			
			//-----------------------------------
			// Pass to handler
		 	//-----------------------------------

		 	if ( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
		 	{
				$q = $extra_install->process_query_insert( $q );
			}
			
			$this->install->ipsclass->DB->query( $q );
		}
		
		
		//-----------------------------------------
		// Create Admin account
		//-----------------------------------------
		
		$output[] = "Creating admin account...";
		
		$this->install->create_admin_account();
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
		$this->install->template->next_action = '?p=install&sub=settings';
		$this->install->template->hide_next   = 1;
	}
	
	/*-------------------------------------------------------------------------*/
	// Installs the settings
	/*-------------------------------------------------------------------------*/
	/**
	* Installs SQL schematic
	*
	* @return void
	*/
	function install_settings()
	{
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );	
		//-----------------------------------------
		// Install settings
		//-----------------------------------------
	
		$output[] = "Inserting settings...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/settings.xml' ) );
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Known settings
		//-----------------------------------------
		
		if ( substr( $this->install->saved_data['install_url'], -1 ) == '/' )
		{
			$this->install->saved_data['install_url'] = substr( $this->install->saved_data['install_url'], 0, -1 );
		}
		
		$_urls = parse_url( $this->install->saved_data['install_url'] );
		
		$known = array(  'email_in'          => $this->install->saved_data['admin_email'],
						 'email_out'         => $this->install->saved_data['admin_email'],
						 'base_dir'          => $this->install->saved_data['install_dir'],
						 'upload_dir'        => $this->install->saved_data['install_dir'] . '/uploads',
						 'upload_url'        => $this->install->saved_data['install_url'] . '/uploads',
						 'search_sql_method' => $this->install->ipsclass->DB->sql_can_fulltext() ? 'ftext' : 'man',
						 //'cookie_domain'     => $_urls['host'] != 'localhost' ? '.' . preg_replace( "#^(?:.+?\.)?([a-z0-9\-]{3,})\.(([a-z]{2,4})(\.([a-z]{2}))?|museum)$#is", "\\1.\\2", $_urls['host'] ) : '',
					 );

		$gd_check = function_exists('gd_info') ? gd_info() : array();
		
		if( strpos( $gd_check['GD Version'], '2.' ) )
		{
			$known['bot_antispam']  = 'gd';
			$known['guest_captcha'] = 'gd';
		}
		
		//-----------------------------------------
		// Parse
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

			$tmp = $xml->xml_array['settingexport']['settinggroup']['setting'];

			unset($xml->xml_array['settingexport']['settinggroup']['setting']);

			$xml->xml_array['settingexport']['settinggroup']['setting'][0] = $tmp;
		}

		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------

		foreach( $xml->xml_array['settingexport']['settinggroup']['setting'] as $id => $entry )
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
			foreach( $new_titles as $idx => $data )
			{
				if ( $data['conf_title_title'] AND $data['conf_title_keyword'] )
				{
					//-----------------------------------------
					// Get ID based on key
					//-----------------------------------------

					$save = array( 'conf_title_title'   => $data['conf_title_title'],
								   'conf_title_desc'    => $data['conf_title_desc'],
								   'conf_title_keyword' => $data['conf_title_keyword'],
								   'conf_title_noshow'  => $data['conf_title_noshow'],
								   'conf_title_module'  => $data['conf_title_module']  );

					//-----------------------------------------
					// Insert first
					//-----------------------------------------

					$this->install->ipsclass->DB->do_insert( 'conf_settings_titles', $save );

					$conf_id               = $this->install->ipsclass->DB->get_insert_id();
					$save['conf_title_id'] = $conf_id;

					//-----------------------------------------
					// Update settings cache
					//-----------------------------------------

					$setting_groups_by_key[ $save['conf_title_keyword'] ] = $save;
					$setting_groups[ $save['conf_title_id'] ]             = $save;

					$need_update[] = $conf_id;
				}
			}
		}

		//-----------------------------------------
		// Sort out settings
		//-----------------------------------------

		if ( is_array( $new_settings ) and count( $new_settings ) )
		{
			foreach( $new_settings as $idx => $data )
			{
				//-----------------------------------------
				// Insert known
				//-----------------------------------------

				if ( in_array( $data['conf_key'], array_keys( $known ) ) )
				{
					$data['conf_value']   = $known[ $data['conf_key'] ];
					#$data['conf_default'] = $known[ $data['conf_key'] ];
				}
				else
				{
					$data['conf_value'] = '';
				}

				//-----------------------------------------
				// Now assign to the correct ID based on
				// our title keyword...
				//-----------------------------------------

				$data['conf_group'] = $setting_groups_by_key[ $data['conf_title_keyword'] ]['conf_title_id'];

				//-----------------------------------------
				// Remove from array
				//-----------------------------------------

				unset( $data['conf_title_keyword'] );

				$this->install->ipsclass->DB->do_insert( 'conf_settings', $data );
			}
		}

		//-----------------------------------------
		// Update group counts...
		//-----------------------------------------

		if ( count( $need_update ) )
		{
			foreach( $need_update as $i => $idx )
			{
				$conf = $this->install->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count', 'from' => 'conf_settings', 'where' => 'conf_group='.$idx ) );

				$count = intval($conf['count']);

				$this->install->ipsclass->DB->do_update( 'conf_settings_titles', array( 'conf_title_count' => $count ), 'conf_title_id='.$idx );
			}
		}
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
		$this->install->template->next_action = '?p=install&sub=acpperms';
		$this->install->template->hide_next   = 1;
	}
	
	/*-------------------------------------------------------------------------*/
	// Installs the ACP Perms
	/*-------------------------------------------------------------------------*/
	/**
	* Installs SQL schematic
	*
	* @return void
	*/
	function install_acpperms()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cur_perms = array();
		
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );
		//-----------------------------------------
		// Install settings
		//-----------------------------------------
	
		$output[] = "Inserting ACP Permissions...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/acpperms.xml' ) );
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------

		foreach( $xml->xml_array['permsexport']['permsgroup']['perm'] as $id => $entry )
		{
			//-----------------------------------------
			// Do we have a row matching this already?
			//-----------------------------------------

			$_perm_main  = $entry['acpperm_main']['VALUE'];
			$_perm_child = $entry['acpperm_child']['VALUE'];
			$_perm_bit   = $entry['acpperm_bit']['VALUE'];

			$_perm_key   = $_perm_main.':'.$_perm_child.':'.$_perm_bit;

			if ( ! $cur_perms[ $_perm_key ] )
			{
				$this->install->ipsclass->DB->do_insert( 'admin_permission_keys', array( 'perm_key'   => $_perm_key,
																		  				 'perm_main'  => $_perm_main,
																		  				 'perm_child' => $_perm_child,
																		  				 'perm_bit'   => $_perm_bit ) );

				$inserted++;
			}
		}
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
		$this->install->template->next_action = '?p=install&sub=templates';
		$this->install->template->hide_next   = 1;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Install: Templates
	/*-------------------------------------------------------------------------*/
	/**
	* Install templates
	*
	* @return void
	*/
	function install_templates()
	{
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );
		//-----------------------------------------
		// Install settings
		//-----------------------------------------
	
		$output[] = "Inserting templates...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/ipb_templates.xml' ) );
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Install
		//-----------------------------------------
		
		foreach( $xml->xml_array['templateexport']['templategroup']['template'] as $id => $entry )
		{
			$newrow = array();

			$newrow['group_name']            = $entry[ 'group_name' ]['VALUE'];
			$newrow['section_content']       = $entry[ 'section_content' ]['VALUE'];
			$newrow['func_name']             = $entry[ 'func_name' ]['VALUE'];
			$newrow['func_data']             = $entry[ 'func_data' ]['VALUE'];
			$newrow['group_names_secondary'] = $entry[ 'group_names_secondary' ]['VALUE'];
			$newrow['set_id']                = 1;
			$newrow['updated']               = time();

			$this->install->ipsclass->DB->allow_sub_select = 1;
			$this->install->ipsclass->DB->do_insert( 'skin_templates', $newrow );
		}

		//-------------------------------
		// GET MACROS
		//-------------------------------

		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/macro.xml' ) );
		$xml->xml_parse_document( $content );

		//-------------------------------
		// (MACRO)
		//-------------------------------

		foreach( $xml->xml_array['macroexport']['macrogroup']['macro'] as $id => $entry )
		{
			$newrow = array();

			$newrow['macro_value']   = $entry[ 'macro_value' ]['VALUE'];
			$newrow['macro_replace'] = $entry[ 'macro_replace' ]['VALUE'];
			$newrow['macro_set']     = 1;

			$this->install->ipsclass->DB->do_insert( 'skin_macro', $newrow );
		}
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );	
		$this->install->template->next_action = '?p=install&sub=other';
		$this->install->template->hide_next   = 1;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Install: Skins
	/*-------------------------------------------------------------------------*/
	/**
	* Install other skins
	*
	* @return void
	*/
	function install_skins()
	{
		//-----------------------------------------
		// Skip for safe_mode peeps
		//-----------------------------------------
		
		$safe_mode = @ini_get("safe_mode") ? 1 : 0;
		
		if( $safe_mode )
		{
			$this->install->template->append( $this->install->template->install_page_refresh( array( 'We detected that safe mode is enabled on your server.  Skipping IP.Board Pro skin insertion...' ) ) );	
			$this->install->template->next_action = '?p=install&sub=caches';
			$this->install->template->hide_next   = 1;
		}
		
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );
		//-------------------------------
		// Other Skins
		//-------------------------------
		
		define( 'SQL_DRIVER', $this->install->saved_data['sql_driver'] );
		define( 'CACHE_PATH', ROOT_PATH );
		
		require_once( ROOT_PATH . 'sources/api/api_skins.php' );
		$api = new api_skins();
		$api->ipsclass =& $this->install->ipsclass;
		
		if( !$this->install->ipsclass->input['didskin'] )
		{
			$output[] = "Inserting IP.Board Pro skin templates...";
			
			$api->skin_add_set( ROOT_PATH . 'resources/ipb_skin-pro.xml.gz' );
			
			$this->install->template->append( $this->install->template->install_page_refresh( $output ) );	
			$this->install->template->next_action = '?p=install&sub=skin&didskin=1';
			$this->install->template->hide_next   = 1;
		}
		else
		{
			$output[] = "Inserting IP.Board Pro skin images...";
			$api->images_add_set( ROOT_PATH . 'resources/ipb_images-pro.xml.gz', 3 );
	
			$this->install->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 0 ) );
			$this->install->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 1 ), 'set_skin_set_id=3' );
		
			//-----------------------------------------
			// Next...
			//-----------------------------------------
			
			$this->install->template->append( $this->install->template->install_page_refresh( $output ) );	
			$this->install->template->next_action = '?p=install&sub=caches';
			$this->install->template->hide_next   = 1;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Install: Other
	/*-------------------------------------------------------------------------*/
	/**
	* Install Other stuff
	*
	* @return void
	*/
	function install_other()
	{
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );
		//-----------------------------------------
		// XML: COMPONENTS
		//-----------------------------------------
		
		$output[] = "Inserting components information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/components.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'com_id' )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'components', $newrow );
		}
		
		//-----------------------------------------
		// XML: LOG IN MODULES
		//-----------------------------------------
		
		$output[] = "Inserting log in modules information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_ROOT_PATH . 'installfiles/loginauth.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'login_id' )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'login_methods', $newrow );
		}
		
		//-----------------------------------------
		// XML: GROUPS
		//-----------------------------------------
		
		$output[] = "Inserting groups information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_ROOT_PATH . 'installfiles/groups.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'g_id' or preg_match( "#^g_blog_#is", $f ) )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'groups', $newrow );
		}
		
		//-----------------------------------------
		// XML: ATTACHMENTS
		//-----------------------------------------
		
		$output[] = "Inserting attachment type information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_ROOT_PATH . 'installfiles/attachments.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'atype_id' )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'attachments_type', $newrow );
		}
		
		//-----------------------------------------
		// XML: SKIN SETS
		//-----------------------------------------
	
		$output[] = "Inserting template set data...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/skinsets.xml' ) );
		$xml->xml_parse_document( $content );
	
		//-----------------------------------------
		// Fix up...
		//-----------------------------------------

		if ( ! is_array( $xml->xml_array['export']['group']['row'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$tmp = $xml->xml_array['export']['group']['row'];

			unset($xml->xml_array['export']['group']['row']);

			$xml->xml_array['export']['group']['row'][0] = $tmp;
		}
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'set_skin_set_id' )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->allow_sub_select = 1;
			$this->install->ipsclass->DB->do_insert( 'skin_sets', $newrow );
		}
		
		
		//-----------------------------------------
		// XML: TASKS :D
		//-----------------------------------------
		
		$output[] = "Inserting task manager information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/tasks.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'task_id' )
				{
					continue;
				}
				
				if ( $f == 'task_cronkey' )
				{
					$entry[ $f ]['VALUE'] = md5( uniqid( microtime() ) );
				}
				
				if ( $f == 'task_next_run' )
				{
					$entry[ $f ]['VALUE'] = time();
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'task_manager', $newrow );
		}
		
		//-----------------------------------------
		// XML: FAQ
		//-----------------------------------------
		
		$output[] = "Inserting FAQ information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/faq.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['export']['group']['row'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'id' )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'faq', $newrow );
		}
		
		//-----------------------------------------
		// XML: BBCode
		//-----------------------------------------
		
		$output[] = "Inserting custom BBCode information...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/bbcode.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['bbcodeexport']['bbcodegroup']['bbcode'] as $id => $entry )
		{
			$newrow = array();
			
			foreach( $entry as $f => $data )
			{
				if ( $f == 'VALUE' or $f == 'bbcode_id' )
				{
					continue;
				}
				
				$newrow[$f] = $entry[ $f ]['VALUE'];
			}
			
			$this->install->ipsclass->DB->do_insert( 'custom_bbcode', $newrow );
		}
		
	
		//-----------------------------------------
		// XML: Help Information
		//-----------------------------------------
		
		$output[] = "Inserting ACP help files...";
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/help_sections.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['helpsectionsexport']['helpsectionsgroup']['helpsections'] as $id => $entry )
		{
			$newrow = array(
							'is_setting'	=> 0,
							'page_key'		=> $entry['key']['VALUE'],
							'help_title'	=> $entry['title']['VALUE'],
							'help_body'		=> $entry['helptext']['VALUE'],
							);

			$this->install->ipsclass->DB->do_insert( 'acp_help', $newrow );
		}
		
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( INS_DOC_ROOT_PATH . 'resources/help_settings.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['helpsettingsexport']['helpsettingsgroup']['helpsettings'] as $id => $entry )
		{
			$newrow = array(
							'is_setting'	=> 1,
							'page_key'		=> $entry['key']['VALUE'],
							'help_title'	=> $entry['title']['VALUE'],
							'help_body'		=> $entry['helptext']['VALUE'],
							'help_mouseover'=> $entry['mouseover']['VALUE'],
							);

			$this->install->ipsclass->DB->do_insert( 'acp_help', $newrow );
		}
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );		
		$this->install->template->next_action = '?p=install&sub=skin';
		$this->install->template->hide_next   = 1;
	}
	
	/*-------------------------------------------------------------------------*/
	// Install: Caches
	/*-------------------------------------------------------------------------*/
	/**
	* Install Caches
	*
	* @return void
	*/
	function install_caches()
	{
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		require_once( INS_KERNEL_PATH . 'class_db_' . $this->install->saved_data['sql_driver'] . '.php' );		
		
		$this->install->ipsclass->init_db_connection( $this->install->saved_data['db_name'],
													  $this->install->saved_data['db_user'],
													  $this->install->saved_data['db_pass'],
													  $this->install->saved_data['db_host'],
													  $this->install->saved_data['db_pre'],
													  $this->install->saved_data['sql_driver'] );
		//-----------------------------------------
		// Do Caches
		//-----------------------------------------
	
		$output = $this->install->cache_and_cleanup();
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		$this->install->template->append( $this->install->template->install_progress( $output ) );		
		$this->install->template->next_action = '?p=done';
	}
}

?>