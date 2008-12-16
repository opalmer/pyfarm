<?php
/**
 * Invision Power Board
 * Action controller for install page
 */

class action_install
{
	var $install;

	var $helpfiles	= 0;

	function action_install( & $install )
	{
		$this->install =& $install;
	}

	// SQL > FINAL > SETTINGS > ACPPERMS / HELP FILE > DB CHECKER
	// After all sets have run: SKIN REVERT > TEMPLATES > OTHER [ Email Templates? ] > Build Caches > DB Check

	function run()
	{
		$this->install->get_version_latest();

		$this->install->saved_data['helpfile'] 	= intval($this->install->ipsclass->input['helpfile']) 	? intval($this->install->ipsclass->input['helpfile']) 	: $this->install->saved_data['helpfile'];
		$this->install->saved_data['man'] 		= intval($this->install->ipsclass->input['man']) 		? intval($this->install->ipsclass->input['man']) 		: $this->install->saved_data['man'];

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

			case 'finish':
				$this->install_finish();
			break;

			case 'checkdb':
				$this->install_checkdb();
			break;

			case 'skinrevert':
				$this->install_skinrevert();
			break;

			case 'templates':
				$this->install_templates();
			break;

			case 'caches':
				$this->install_caches();
			break;

			default:
				/* Output */

				$count = $this->install->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as num', 'from' => 'posts' ) );

				if( $count['num'] > 100000 )
				{
					$do_manual = 1;
				}
				else
				{
					$do_manual = 0;
				}

				$this->install->template->append( $this->install->template->install_page( $do_manual ) );
				$this->install->template->next_action = '?p=install&sub=sql';
				$this->install->template->hide_next   = 1;
			break;
		}

		//----------------------------------------------
		// Log errors for tech support
		//----------------------------------------------

		if( count($this->install->error) > 0 )
		{
			$file_name = ROOT_PATH . 'cache/sql_upgrade_log_'.date('m_d_y').'.cgi';

			$_error_string  = "\n===================================================";
			$_error_string .= "\n Date: ". date( 'r' );
			$_error_string .= "\n IP Address: " . $_SERVER['REMOTE_ADDR'];
			$_error_string .= "\n Member ID: " . $this->install->ipsclass->member['id'];
			$_error_string .= "\n Version Folder: " .$this->install->current_upgrade;
			$_error_string .= "\n Current Sub Step: " .$this->install->ipsclass->input['sub'];
			$_error_string .= "\n Current workact: " .$this->install->saved_data['workact'];
			$_error_string .= "\n\n\n ".$this->install->ipsclass->my_br2nl(implode( "\n", $this->install->error ));

			$fh = fopen( $file_name, "a" );
			fwrite( $fh, $_error_string, strlen( $_error_string ) );
			fclose( $fh );
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
		if ( $this->install->current_upgrade AND $this->install->current_upgrade < 20000 )
		{
			// Jump right to the finish routine for 1.x

			$this->install_finish();
			return;
		}

		$output					= "";
		$message 				= array();
		$this->install->error   = array();

		$SQL = array();
		$cnt = 0;

		if ( file_exists( INS_ROOT_PATH.'installfiles/upg_'.$this->install->current_upgrade.'/'.strtolower($this->install->ipsclass->vars['sql_driver']).'_updates.php' ) )
		{
			require_once ( INS_ROOT_PATH.'installfiles/upg_'.$this->install->current_upgrade.'/'.strtolower($this->install->ipsclass->vars['sql_driver']).'_updates.php' );
		}

		// Create/Alter tables
		if ( count( $SQL ) > 0 )
		{
			$this->sqlcount = 0;

			$this->install->ipsclass->DB->return_die = 1;

			foreach( $SQL as $q )
			{
				$this->install->ipsclass->DB->allow_sub_select 	= 1;
				$this->install->ipsclass->DB->error				= '';

				$q = str_replace( "<%time%>", time(), $q );

				if( $this->install->ipsclass->vars['mysql_tbl_type'] )
				{
					if( preg_match( "/^create table(.+?)/i", $q ) )
					{
						$q = preg_replace( "/^(.+?)\);$/is", "\\1) TYPE={$this->install->ipsclass->vars['mysql_tbl_type']};", $q );
					}
				}

				if( $this->install->saved_data['man'] )
				{
					$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->install->ipsclass->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $q ) )."\n\n";
				}
				else
				{
					$this->install->ipsclass->DB->query( $q );

					if ( $this->install->ipsclass->DB->error )
					{
						$this->install->error[] = $q."<br /><br />".$this->install->ipsclass->DB->error;
					}
					else
					{
						$this->sqlcount++;
					}
				}
			}

			$message[] = $this->sqlcount." queries run...";
		}
		else
		{
			// If there are no SQL queries to run, jump to settings

			$this->install_finish();
			return;
		}


		if ( count( $this->install->error ) > 0 )
		{
			$this->install->message = count($message) ? implode( "<br />", $message ) : "Proceeding with update";

			$this->install->template->warning( array_merge( array( $this->install->message ),
															array( 'Error in upgrade '.$this->install->versions[ $this->install->current_upgrade ]. ' (' . $this->install->current_upgrade . ')' ),
															array( "<span style='color:red'>".count($this->install->error).' errors found</span>' ),
															$this->install->error ) );
			$this->install->template->in_error   = 1;

			$this->install->template->next_action = '?p=install&sub=finish';

			return;
		}

		if( $this->install->saved_data['man'] AND $output )
		{
			$output = "<h3><b>Please run these queries in your MySQL database before continuing..</b></h3><br />".nl2br(htmlspecialchars($output));

			$this->install->template->next_action = '?p=install&sub=finish';
			$this->install->template->append( $output );
		}
		else
		{
			$output[] = count($message) ? implode( "<br />", $message ) : "No queries, settings, or permissions to import...<br /><br />Proceeding with update";
			$this->install->template->next_action = '?p=install&sub=finish';
			$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
			$this->install->template->hide_next = 1;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Installs the Settings
	/*-------------------------------------------------------------------------*/
	/**
	* Installs Partial Settings
	*
	* @return void
	*/
	function install_settings()
	{
		if ( $this->install->current_upgrade AND $this->install->current_upgrade < 20000 )
		{
			// Jump right to the finish routine for 1.x
			// Reorganized - should never hit here

			$this->install_finish();
			return;
		}

		$message 				= array();
		$this->install->error   = array();

		//-------------------------------
		// Load module...
		//-------------------------------

		require_once( ROOT_PATH . 'sources/action_admin/settings.php' );
		$settings           =  new ad_settings();
		$settings->ipsclass =& $this->install->ipsclass;

		//-------------------------------
		// Set location
		//-------------------------------

		$this->install->ipsclass->input['file_location'] = 'resources/settings.xml';

		//-------------------------------
		// Run it
		//-------------------------------

		$settings->settings_do_import( 1 );

		$message[] = $this->install->ipsclass->main_msg;

		// 2.2.0 preserve login type

		if( $this->install->saved_data['ipbli_usertype'] )
		{
			$this->install->ipsclass->DB->do_update( "conf_settings", array( 'conf_value' => $this->install->saved_data['ipbli_usertype'] ), "conf_key='ipbli_usertype'" );

			$this->install->ipsclass->DB->do_update( "login_methods", array( 'login_user_id' => $this->install->saved_data['ipbli_usertype'] ), "login_folder_name='internal'" );
		}


		if ( count( $this->install->error ) > 0 )
		{
			$this->install->message = count($message) ? implode( "<br />", $message ) : "Proceeding with update";

			$this->install->template->warning( array_merge( array( $this->install->message ),
															array( 'Error in upgrade '.$this->install->versions[ $this->install->current_upgrade ]. ' (' . $this->install->current_upgrade . ')' ),
															array( "<span style='color:red'>".count($this->install->error).' errors found</span>' ),
															$this->install->error ) );
			$this->install->template->in_error   = 1;

			$this->install->template->next_action = '?p=install&sub=acpperms';

			return;
		}

		$output[] = count($message) ? implode( "<br />", $message ) : "No settings to import...<br /><br />Proceeding with update";
		$this->install->template->next_action = '?p=install&sub=acpperms';
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
		$this->install->template->hide_next = 1;
	}

	/*-------------------------------------------------------------------------*/
	// Installs the ACP Permissions
	/*-------------------------------------------------------------------------*/
	/**
	* Installs ACP Permissions
	*
	* @return void
	*/
	function install_acpperms()
	{
		if( $this->install->current_upgrade AND $this->install->current_upgrade < 20000 )
		{
			// Jump right to the finish routine for 1.x
			// Reorganized - should never hit here

			$this->install_finish();
			return;
		}

		$message 				= array();
		$this->install->error   = array();

		//-----------------------------------------
		// Do Tasks - Just stick it here shall we
		//-----------------------------------------

		require_once( ROOT_PATH . 'sources/api/api_tasks.php' );
		$api =  new api_tasks();
		$api->ipsclass =& $this->install->ipsclass;
		$api->api_init();

		$api->add_task();

		$message[] = $api->error ? "<span style='color:red;'>Could not rebuild tasks.  Please rebuild tasks in the ACP from the Task Manager page</span>" : "Rebuilt Tasks from XML...";

		//-------------------------------
		// Load module...
		//-------------------------------

		require_once( ROOT_PATH . 'sources/action_admin/acppermissions.php' );
		$settings           =  new ad_acppermissions();
		$settings->ipsclass =& $this->install->ipsclass;

		//-------------------------------
		// Set location
		//-------------------------------

		$this->install->ipsclass->input['file_location'] = 'resources/acpperms.xml';

		//-------------------------------
		// Run it
		//-------------------------------

		$settings->acpperms_xml_import( 1 );

		$message[] = $this->install->ipsclass->main_msg;

		//-----------------------------------------
		// Install FAQ
		//-----------------------------------------
		$message[] 	= "Updating FAQ information...";
		$xml 		= new class_xml();
		$xml->lite_parser = 1;

		$updatehelp = ( isset( $this->install->saved_data['helpfile'] ) && $this->install->saved_data['helpfile'] ) ? 1 : 0;

		$content = implode( "", file( ROOT_PATH . 'resources/faq.xml' ) );

		if ( $content )
		{
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

				if ( $newrow['title'] )
				{
					$cur_faq = $this->install->ipsclass->DB->build_and_exec_query( array( 'select'	=> 'id', 'from' => 'faq', 'where' => "title = '".$this->install->ipsclass->DB->add_slashes( $newrow['title'] )."'" ) );

					if ( $cur_faq['id'] )
					{
						if ( $updatehelp )
						{
							$this->install->ipsclass->DB->do_update( 'faq', $newrow, "id = ".$cur_faq['id'] );
						}
					}
					else
					{
						$this->install->ipsclass->DB->do_insert( 'faq', $newrow );
					}
				}
			}
		}

		//-----------------------------------------
		// XML: Help Information
		//-----------------------------------------

		$message[] = "Inserting ACP help files...";

		$keys		= array();

		$this->install->ipsclass->DB->build_query( array( 'select' => 'page_key', 'from' => 'acp_help' ) );
		$this->install->ipsclass->DB->exec_query();

		while( $r = $this->install->ipsclass->DB->fetch_row() )
		{
			$keys[] = $r['page_key'];
		}

		$xml = new class_xml();
		$xml->lite_parser = 1;

		$content = implode( "", file( ROOT_PATH . 'resources/help_sections.xml' ) );
		$xml->xml_parse_document( $content );

		foreach( $xml->xml_array['helpsectionsexport']['helpsectionsgroup']['helpsections'] as $id => $entry )
		{
			$newrow = array(
							'is_setting'	=> 0,
							'page_key'		=> $entry['key']['VALUE'],
							'help_title'	=> $entry['title']['VALUE'],
							'help_body'		=> $entry['helptext']['VALUE'],
							);

			if( in_array( $newrow['page_key'], $keys ) )
			{
				$this->install->ipsclass->DB->do_update( 'acp_help', $newrow, "page_key='{$newrow['page_key']}'" );
			}
			else
			{
				$this->install->ipsclass->DB->do_insert( 'acp_help', $newrow );
			}
		}

		$xml = new class_xml();
		$xml->lite_parser = 1;

		$content = implode( "", file( ROOT_PATH . 'resources/help_settings.xml' ) );
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

			if( in_array( $newrow['page_key'], $keys ) )
			{
				$this->install->ipsclass->DB->do_update( 'acp_help', $newrow, "page_key='{$newrow['page_key']}'" );
			}
			else
			{
				$this->install->ipsclass->DB->do_insert( 'acp_help', $newrow );
			}
		}


		if ( count( $this->install->error ) > 0 )
		{
			$this->install->message = count($message) ? implode( "<br />", $message ) : "Proceeding with update";

			$this->install->template->warning( array_merge( array( $this->install->message ),
															array( 'Error in upgrade '.$this->install->versions[ $this->install->current_upgrade ]. ' (' . $this->install->current_upgrade . ')' ),
															array( "<span style='color:red'>".count($this->install->error).' errors found</span>' ),
															$this->install->error ) );
			$this->install->template->in_error   = 1;

			$this->install->template->next_action = '?p=install&sub=finish';

			return;
		}

		$this->install->template->next_action = '?p=install&sub=checkdb';
		$this->install->template->append( $this->install->template->install_page_refresh( $message ) );
		$this->install->template->hide_next = 1;
	}

	/*-------------------------------------------------------------------------*/
	// Finishes the version
	/*-------------------------------------------------------------------------*/
	/**
	* Runs version upgrade script and finishes the version
	*
	* @return void
	*/
	function install_finish()
	{
		$continue = 0;

		if ( $this->install->current_upgrade < 20000 )
		{
			$driver = $this->install->ipsclass->vars['sql_driver'] ? trim(strtolower($this->install->ipsclass->vars['sql_driver'])) : 'mysql';
			
			$upg_file = INS_ROOT_PATH.'installfiles/upg_'.$this->install->current_upgrade.'/version_upgrade_' . $driver . '.php';
		}
		else
		{	
			$upg_file = INS_ROOT_PATH.'installfiles/upg_'.$this->install->current_upgrade.'/version_upgrade.php';
		}

		if ( file_exists( $upg_file ) )
		{
			require_once( $upg_file );
			$upgrade = new version_upgrade( $this->install );
			$result  = $upgrade->auto_run();

			if ( count( $this->install->error ) > 0 )
			{
				$this->install->template->warning( array_merge( array( $this->install->message ),
																array( 'Error in upgrade '.$this->install->versions[ $this->install->current_upgrade ]. ' (' . $this->install->current_upgrade . ')' ),
																array( "<span style='color:red'>".count($this->install->error).' errors found</span>' ),
																$this->install->error ) );
				$this->install->template->in_error   = 1;

				if ( ! $result )
				{
					$this->install->template->next_action = '?p=install&sub=finish';
				}
				elseif ( $this->install->current_upgrade >= $this->install->last_poss_id || $this->install->current_upgrade == 0 )
				{
					$this->install->template->next_action = '?p=install&sub=settings';
				}
				else
				{
					$this->install->template->next_action = '?p=install&sub=sql';
				}

				$in_error = 1;
			}

			//-----------------------------------------
			// 'version_upgrade.php' is now done
			//-----------------------------------------

			if ( $result )
			{
				// The individual upgrade files all shoot you to 2.0...

				if ( $this->install->current_upgrade < 20000 )
				{
					$this->install->current_upgrade = '10004';
					unset($this->install->saved_data['vid']);
				}

				//------------------------------------------
				// Update DB
				//------------------------------------------

				$this->install->ipsclass->DB->do_insert( 'upgrade_history', array(	'upgrade_version_id'    	=> $this->install->current_upgrade,
																				  		'upgrade_version_human' => $this->install->versions[ $this->install->current_upgrade ],
																				  		'upgrade_date'  		=> time(),
																				  		'upgrade_mid'   		=> $this->install->saved_data['mid'],
						     						   )                         	  );

				if ( $in_error == 1 )
				{
					return;
				}

				if ( $this->install->message )
				{
					$output[] = $this->install->message;
				}

				$output[] = "Succesfully upgraded to version {$this->install->versions[ $this->install->current_upgrade ]}";
			}
			else
			{
				if ( $in_error == 1 )
				{
					return;
				}

				if ( $this->install->message )
				{
					$output[] = $this->install->message;
				}
				else
				{
					$output[] = "Proceeding with update...";
				}

				$continue = 1;
			}
		}
		else
		{
			//------------------------------------------
			// Update DB
			//------------------------------------------

			if ( $this->install->current_upgrade )
			{
				$this->install->ipsclass->DB->do_insert( 'upgrade_history', array(	'upgrade_version_id'    	=> $this->install->current_upgrade,
																				  	'upgrade_version_human' => $this->install->versions[ $this->install->current_upgrade ],
																				  	'upgrade_date'  		=> time(),
																				  	'upgrade_mid'   		=> $this->install->saved_data['mid'],
						     						   )                         );

				$output[] = "Succesfully upgraded to version {$this->install->versions[ $this->install->current_upgrade ]}";
			}
		}

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		if ( $continue )
		{
			//-----------------------------------------
			// More to do?
			//-----------------------------------------

			$this->install->template->next_action = '?p=install&sub=finish';
		}
		elseif ( $this->install->current_upgrade >= $this->install->last_poss_id || $this->install->current_upgrade == 0 )
		{
			//-----------------------------------------
			// Last update?
			//-----------------------------------------

			$this->install->template->next_action = '?p=install&sub=settings';
		}
		else
		{
			//-----------------------------------------
			// Do SQL
			//-----------------------------------------

			$this->install->template->next_action = '?p=install&sub=sql';
		}

		if ( $this->install->do_man )
		{
			$this->install->template->append( implode( "<br />", $output ) );
		}
		else
		{
			$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
			$this->install->template->hide_next   = 1;
		}
	}


	/*-------------------------------------------------------------------------*/
	// Runs the DB Checker after install
	/*-------------------------------------------------------------------------*/
	/**
	* Checks DB for errors
	*
	* @return void
	*/
	function install_checkdb()
	{
		$message 				= array();
		$this->install->error   = array();

		if (  @file_exists( ROOT_PATH."/install/sql/{$this->install->ipsclass->vars['sql_driver']}_tables.php" ) )
		{
			//-------------------------------
			// Load ACP Skin...
			//-------------------------------

			require_once( ROOT_PATH   . "sources/lib/admin_skin.php" );
			$this->install->ipsclass->skin_acp 		= 'IPB2_Standard';
			$this->install->ipsclass->skin_acp_url 	= $this->install->ipsclass->vars['board_url'] . "/skin_acp/" . $this->install->ipsclass->skin_acp;

			$this->install->ipsclass->adskin           = new admin_skin();
			$this->install->ipsclass->adskin->ipsclass =& $this->install->ipsclass;
			$this->install->ipsclass->adskin->init_admin_skin();

			//-----------------------------------------
			// Fixing something?
			//-----------------------------------------

			$queries_to_run = array();

			foreach( $this->install->ipsclass->input as $k => $v )
			{
				if( preg_match( "/^query(\d+)$/", $k, $matches ) )
				{
					$queries_to_run[] = $v;
				}
			}

			if( isset($this->install->ipsclass->input['query']) AND $this->install->ipsclass->input['query'] )
			{
				$queries_to_run[] = $this->install->ipsclass->input['query'];
			}

			if( count($queries_to_run) > 0 )
			{
				foreach( $queries_to_run as $the_query )
				{
					$sql = trim( urldecode( base64_decode($the_query) ) );

					if ( preg_match( "/^(DROP|FLUSH)/i", trim($sql) ) )
					{
						$this->install->ipsclass->main_msg = "Sorry, those queries are not allowed for your safety";

						continue;
					}
					else if ( preg_match( "/^(DELETE|UPDATE|TRUNCATE)/i", preg_replace( "#\s{1,}#s", "", $sql ) ) and preg_match( "/admin_login_logs/i", preg_replace( "#\s{1,}#s", "", $sql ) ) )
					{
						$this->install->ipsclass->main_msg = "Sorry, those queries are not allowed for your safety";

						continue;
					}
					else
					{
						$this->install->ipsclass->DB->return_die = 1;

						$this->install->ipsclass->DB->query($sql,1);

						if( $this->install->ipsclass->DB->error != "" )
						{
							$this->install->ipsclass->main_msg .= "<span style='color:red;'>SQL Error</span><br />{$this->install->ipsclass->DB->error}<br />";
						}
						else
						{
							$this->install->ipsclass->main_msg .= "Query: ".htmlspecialchars($sql)."<br />Executed Successfully<br />";
						}
						
						$this->install->ipsclass->DB->error  = "";
						$this->install->ipsclass->DB->failed = 0;
					}
				}
			}
					
			//-------------------------------
			// Load module...
			//-------------------------------
			
			require_once( KERNEL_PATH . 'db_lib/' . strtolower($this->install->ipsclass->vars['sql_driver']) . '_tools.php' );
			require_once( ROOT_PATH . 'install/sql/' . strtolower($this->install->ipsclass->vars['sql_driver']) . '_tables.php' );

			$db_tools = new db_tools( $this->install->ipsclass );

			$output = array();
			$output = $db_tools->db_table_diag( $TABLE );
#print_r($output);
			$our_output = "		<style type='text/css' media='all'>
									.tableheader,
									.tableheaderalt
									{
										font-size:12px;
										vertical-align:middle;
										font-weight:bold;
										color:#FFF;
										padding:8px 0px 8px 5px;
										background-image: url({$this->install->ipsclass->vars['board_url']}/skin_acp/IPB2_Standard/images/folder_css_images/table_title_gradient.gif);
										background-repeat: repeat-x;
										background-color:#3363A1;
									}
									@import url('install.css');
								</style>
								<script type='text/javascript' src='dbchecker.js'></script>
						";

			if( $output['error_count'] > 0 )
			{
				$this->install->ipsclass->adskin->td_header[] = array( "{none}"    	, "50%" );
				$this->install->ipsclass->adskin->td_header[] = array( "{none}"    	, "50%" );

				$our_output .= "<script type='text/javascript' src='dbchecker.js'></script><script type='text/javascript'>var save_data = '".urlencode(serialize($this->install->saved_data))."';</script>";

				$our_output .= $this->install->ipsclass->adskin->start_table( "WARNING: ERRORS FOUND" );

				$our_output .= $this->install->ipsclass->adskin->add_td_row( array( array( "<span class='rss-feed-invalid'>There were errors found with your database.  Please review them below, or click <a href='#' onclick='fix_all_dberrors();'>here</a> to correct these errors.", 2 ) ) );

				$our_output .= $this->install->ipsclass->adskin->end_table();
			}
			else
			{
				$this->install->ipsclass->adskin->td_header[] = array( "{none}"    	, "50%" );
				$this->install->ipsclass->adskin->td_header[] = array( "{none}"    	, "50%" );

				$our_output .= $this->install->ipsclass->adskin->start_table( "No Errors Found" );

				$our_output .= $this->install->ipsclass->adskin->add_td_row( array( array( "<span class='rss-feed-valid'>There were no errors found with your database.", 2 ) ) );

				$our_output .= $this->install->ipsclass->adskin->end_table();
			}

			$this->install->ipsclass->adskin->td_header[] = array( "Table"    	, "30%" );
			$this->install->ipsclass->adskin->td_header[] = array( "Status"  	, "20%" );
			$this->install->ipsclass->adskin->td_header[] = array( "Fix"       	, "50%" );

			$our_output .= $this->install->ipsclass->adskin->start_table( "Database Table Results" );

			$good_img = "<img src='{$this->install->ipsclass->skin_acp_url}/images/aff_tick.png' border='0' alt='YN' class='ipd' />";
			$bad_img  = "<img src='{$this->install->ipsclass->skin_acp_url}/images/aff_cross.png' border='0' alt='YN' class='ipd' />";

			$i = 0;

			foreach( $output['results'] as $data )
			{
				if( $data['status'] == 'error' )
				{
					$popup_div = "<div style='border: 2px outset rgb(85, 85, 85); padding: 4px; background: rgb(238, 238, 238) none repeat scroll 0%; position: absolute; width: auto; display: none; text-align: center; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;' id='{$i}' align='center'>{$data['fixsql']}</div>";

					$our_output .= $this->install->ipsclass->adskin->add_td_row( array( "<span style='color:red'>{$data['table']}</span>",
																				"<span style='color:red'>{$good_img}</span>",
																				"<center><script type='text/javascript'>all_queries[{$i}] = '".base64_encode($data['fixsql'])."';</script><a href='index.php?p=install&sub=checkdb&saved_data=".urlencode(serialize($this->install->saved_data))."&query=".urlencode(base64_encode($data['fixsql']))."'><b>Fix Automatically</b></a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<a href'#' onclick=\"toggleview('{$i}');return false;\" style='cursor: pointer;'><b>Fix Manually</b></a><br />{$popup_div}</center>"
																	   ) 	  );
					$i++;
				}
				else
				{
					$our_output .=  $this->install->ipsclass->adskin->add_td_row( array( "<span style='color:green'>{$data['table']}</span>",
																				"<span style='color:green'>{$good_img}</span>",
																				"&nbsp;"
																	   ) 	  );
				}
			}

			$our_output .= $this->install->ipsclass->adskin->end_table();
		}
		else
		{
			// Can't run db checker as table def isn't there
			//$this->install_skinrevert();
			$this->install_templates();
			return;
		}

		//$this->install->template->next_action = '?p=install&sub=skinrevert';
		$this->install->template->next_action = '?p=install&sub=templates';
		$this->install->template->append( $our_output );
	}


	/*-------------------------------------------------------------------------*/
	// Install: Revert Skin Changes
	/*-------------------------------------------------------------------------*/
	/**
	* Install templates
	*
	* @return void
	*/
	function install_skinrevert()
	{
		$message 				= array();
		$this->install->error   = array();

		// First time around, we won't have this

		$this->install->saved_data['do'] = isset($this->install->ipsclass->input['do']) ? $this->install->ipsclass->input['do'] : $this->install->saved_data['do'];

		if( $this->install->saved_data['do'] == 'none' )
		{
			$this->install_templates();
			return;
		}

		if ( ! $this->install->saved_data['do'] )
		{
			$id = intval($this->install->saved_data['skinid']) ? intval($this->install->saved_data['skinid']) : 1;

			$default = $this->install->ipsclass->DB->simple_exec_query( array( 'select' => '*',
																			   'from'   => 'skin_sets',
																			   'where'  => "set_skin_set_id > {$id} AND set_key != 'ip.board_pro'",
																			   'order'  => 'set_skin_set_id ASC',
																			   'limit'  => array(0,1) ) );

			if ( ! $default['set_skin_set_id'] )
			{
				$this->install->template->next_action = '?p=install&sub=templates';
				$this->install->template->append( $this->install->template->install_page_refresh( array( 'No further skins to revert' ) ) );
				$this->install->template->hide_next   = 1;
				return;
			}
			else
			{
				$this->install->saved_data['skinid'] = $default['set_skin_set_id'];

				$this->install->template->next_action = '?p=install&sub=skinrevert';
				$this->install->template->append( $this->install->template->install_template_skinrevert( $default['set_name'] ) );
				return;
			}
		}
		else
		{
			$man     = intval( $this->install->saved_data['skinid'] );
			$cnt     = 0;

			$default = $this->install->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_skin_set_id=".$man." AND set_key != 'ip.board_pro'" ) );

			if( $default['set_skin_set_id'] )
			{
				$this->install->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "set_id=".$man ) );
				$outer = $this->install->ipsclass->DB->simple_exec();
	
				if( $this->install->ipsclass->DB->get_num_rows($outer) )
				{
					while( $r = $this->install->ipsclass->DB->fetch_row($outer) )
					{
						if( $r['set_id'] == 1 )
						{
							continue;
						}
						else
						{
							$this->install->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_templates', 'where' => 'suid='.$r['suid'] ) );
							$cnt++;
						}
					}
				}
	
				$this->install->ipsclass->DB->do_update( 'skin_sets', array( 'set_css' => '', 'set_wrapper' => '' ), "set_skin_set_id=".$man );
			}

			$next = $this->install->ipsclass->DB->simple_exec_query( array( 'select'  => '*',
																			'from'    => 'skin_sets',
																			'where'   => "set_skin_set_id > ".$man." AND set_key != 'ip.board_pro'",
																			'order'   => 'set_skin_set_id ASC',
																			'limit'   => array(0,1) ) );

			if( $next['set_skin_set_id'] )
			{
				if( $this->install->saved_data['do'] == 'all' )
				{
					$this->install->saved_data['skinid'] = $next['set_skin_set_id'];
					$this->install->saved_data['do']	 = 'all';
					$this->install->template->next_action = '?p=install&sub=skinrevert';
					$this->install->template->append( $this->install->template->install_page_refresh( array( "$cnt skin template bits from skin set '{$default['set_name']}' reverted, proceeding to next skin set...." ) ) );
					$this->install->template->hide_next   = 1;
					return;
				}
				else
				{
					$this->install->saved_data['skinid'] = $default['set_skin_set_id'];
					$this->install->template->next_action = '?p=install&sub=skinrevert';
					unset($this->install->saved_data['do']);
					$this->install->template->append( $this->install->template->install_page_refresh( array( "$cnt skin template bits from skin set '{$default['set_name']}' reverted, proceeding to next skin set...." ) ) );
					$this->install->template->hide_next   = 1;
					return;
				}
			}
			else
			{
				$this->install->template->next_action = '?p=install&sub=templates';
				$this->install->template->append( $this->install->template->install_page_refresh( array( "$cnt skin template bits from skin set '{$default['set_name']}' reverted, proceeding to rebuild templates...." ) ) );
				$this->install->template->hide_next   = 1;
				return;
			}
		}
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
		//-----------------------------------
		// Get XML
		//-----------------------------------

		$xml = new class_xml();
		$xml->lite_parser = 1;

		//-----------------------------------
		// Get XML file (TEMPLATES)
		//-----------------------------------

		$xmlfile = ROOT_PATH.'resources/ipb_templates.xml';

		$setting_content = implode( "", file($xmlfile) );

		//-------------------------------
		// Unpack the datafile (TEMPLATES)
		//-------------------------------

		$xml->xml_parse_document( $setting_content );

		//-------------------------------
		// (TEMPLATES)
		//-------------------------------

		if ( ! is_array( $xml->xml_array['templateexport']['templategroup']['template'] ) )
		{
			$this->install->template->in_error   = 1;
			$this->install->error[] = "Error with resources/ipb_templates.xml - could not process XML properly";

			$this->install->template->warning( $this->install->error );

			$this->install->template->next_action = '?p=install&sub=rebuild';
			return;
		}
		else
		{
			$output[] = "Master templates rebuilt, proceeding to recache templates...";

			foreach( $xml->xml_array['templateexport']['templategroup']['template'] as $id => $entry )
			{
				$row = $this->install->ipsclass->DB->simple_exec_query( array( 'select' => 'suid',
																	  'from'   => 'skin_templates',
																	  'where'  => "group_name='{$entry[ 'group_name' ]['VALUE']}' AND func_name='{$entry[ 'func_name' ]['VALUE']}' and set_id=1"
															 )      );

				$this->install->ipsclass->DB->free_result();

				$this->install->ipsclass->DB->allow_sub_select 	= 1;

				if ( $row['suid'] )
				{
					$this->install->ipsclass->DB->do_update( 'skin_templates', array( 'func_data'             => $entry[ 'func_data' ]['VALUE'],
																			 		  'section_content'       => $entry[ 'section_content' ]['VALUE'],
																					  'group_names_secondary' => $entry[ 'group_names_secondary' ]['VALUE'],
																					  'updated'               => time()
																					   )
																				, 'suid='.$row['suid'] );
				}
				else
				{
					$this->install->ipsclass->DB->do_insert( 'skin_templates', array( 'func_data'             => $entry[ 'func_data' ]['VALUE'],
																					  'func_name'             => $entry[ 'func_name' ]['VALUE'],
																					  'section_content'       => $entry[ 'section_content' ]['VALUE'],
																					  'group_names_secondary' => $entry[ 'group_names_secondary' ]['VALUE'],
																					  'group_name'            => $entry[ 'group_name' ]['VALUE'],
																					  'updated'               => time(),
																					  'set_id'                => 1
														 )                         );
				}
			}
		}

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		unset($xml);

		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
		$this->install->template->next_action = '?p=install&sub=caches';
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
		// Do Caches
		//-----------------------------------------

		require_once( ROOT_PATH . 'sources/api/api_skins.php' );
		$api =  new api_skins();
		$api->ipsclass =& $this->install->ipsclass;
		$api->api_init();

		$this->install->ipsclass->DB->allow_sub_select 	= 1;

		if ( isset( $this->install->ipsclass->input['sid'] ) )
		{
			if ( $this->install->ipsclass->input['sid'] == 0 )
			{
				$output = $this->install->cache_and_cleanup();
				$this->install->template->next_action = '?p=done';
			}
			else
			{
				$messages = $api->skin_rebuild_caches( intval( $this->install->ipsclass->input['sid'] ) );
				$output = $messages['messages'];
				$this->install->template->next_action = '?p=install&sub=caches&sid='.$messages['completed'];
			}
		}
		else
		{
				$messages = $api->skin_rebuild_caches( 0 );
				$output = $messages['messages'];
				$this->install->template->next_action = '?p=install&sub=caches&sid='.$messages['completed'];
		}

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		$this->install->template->hide_next   = 1;
		$this->install->template->append( $this->install->template->install_page_refresh( $output ) );
	}

}

?>