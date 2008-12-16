<?php
/**
 * Invision Power Board
 * Invision Installer Framework
 */
 
class application_installer extends class_installer
{
	/**
	 * application_installer::set_requirements
	 * 
	 * Sets the requirements for this app
	 *
	 */		
	function set_requirements()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$wfiles = array();
		$wdirs  = array( 0 => INS_ROOT_PATH . 'installfiles' );
		
		//-----------------------------------------
		// Basics
		//-----------------------------------------
		
		$this->version_php_min   = '4.3.0';
		$this->version_mysql_min = '4.0.0';
		
		//-----------------------------------------
		// Grab XML file and check
		//-----------------------------------------
		
		if ( file_exists( INS_ROOT_PATH . 'installfiles/writeablefiles.xml' ) )
		{
			$config = implode( '', file( INS_ROOT_PATH . 'installfiles/writeablefiles.xml' ) );
			$xml = new class_xml();
	
			$config = $xml->xml_parse_document( $config );
			
			//-----------------------------------------
			// Loop through and sort out settings...
			//-----------------------------------------

			foreach( $xml->xml_array['installdata']['file'] as $id => $entry )
			{
				if ( preg_match( "#\.\w{3,}$#si", $entry['path']['VALUE'] ) )
				{
					$wfiles[] = INS_DOC_ROOT_PATH  . $entry['path']['VALUE'];
				}
				else
				{
					$wdirs[] = INS_DOC_ROOT_PATH  . $entry['path']['VALUE'];
				}
			}
		}
		
		$this->set_writeable( $wdirs, $wfiles );
	}
	
	/**
	 * application_installer::write_configuration
	 * 
	 * Writes the configuration files for this app
	 *
	 */		
	function write_configuration()
	{
		//-----------------------------------------
		// Safe mode?
		//-----------------------------------------
		
		$safe_mode = 0;

		if ( @get_cfg_var('safe_mode') )
		{
			$safe_mode = @get_cfg_var('safe_mode');
		}
		
		//-----------------------------------------
		// Set info array
		//-----------------------------------------
		
		$INFO = array( 
					   'sql_driver'     => $this->saved_data['sql_driver'],
					   'sql_host'       => $this->saved_data['db_host'],
					   'sql_database'   => $this->saved_data['db_name'],
					   'sql_user'       => $this->saved_data['db_user'],
					   'sql_pass'       => $this->saved_data['db_pass'],
					   'sql_tbl_prefix' => $this->saved_data['db_pre'],
					   'sql_debug'      => 1,

					   'board_start'    => time(),
					   'installed'      => 1,

					   'php_ext'        => 'php',
					   'safe_mode'      => $safe_mode,

					   'board_url'      => $this->saved_data['install_url'],
					   
					   'banned_group'   => '5',
					   'admin_group'	=> '4',
					   'guest_group'    => '2',
					   'member_group'   => '3',
					   'auth_group'		=> '1',
					 );
					
		//-----------------------------------
		// Any "extra" configs required for this driver?
		//-----------------------------------
		
		foreach( $this->saved_data as $k => $v )
		{
			if ( preg_match( "#^__sql__#", $k ) )
			{
				$k = str_replace( "__sql__", "", $k );
				
				$INFO[ $k ] = $v;
			}
		}
		
		//-----------------------------------
		// Write to disk
		//-----------------------------------

		$core_conf = "<"."?php\n";

		foreach( $INFO as $k => $v )
		{
			$core_conf .= '$INFO['."'".$k."'".']'."\t\t\t=\t'".$v."';\n";
		}

		$core_conf .= "\n".'?'.'>';

		/* Write Configuration Files */
		$output[] = 'Writing configuration files...<br />';
		
		$this->write_file( $this->saved_data['install_dir'] . '/conf_global.php'  , $core_conf );
		
		//-----------------------------------
		// Check that it wrote
		//-----------------------------------
		
		if( !file_exists( $this->saved_data['install_dir'] . '/conf_global.php' ) )
		{
			$this->template->hide_next = 1;
			$this->template->warning( array( "We were unable to write your configuration information to the conf_global.php file.  Please verify that this file has full read and write privileges." ) );
			$this->template->output( $this->product_name, $this->product_version );
			
			exit();
		}
		else
		{
			unset($INFO);
			
			require_once( $this->saved_data['install_dir'] . '/conf_global.php' );
			
			if( !is_array($INFO) )
			{
				$this->template->hide_next = 1;
				$this->template->warning( array( "We were unable to write your configuration information to the conf_global.php file.  Please verify that this file has full read and write privileges." ) );
				$this->template->output( $this->product_name, $this->product_version );
				
				exit();
			}
		}
	}
	
	
	/**
	 * application_installer::create_admin_account
	 * 
	 * Creates the local app admin entry
	 *
	 */	
	function create_admin_account()
	{
		/* Create Converge Entry */
		$converge_id = $this->create_admin_converge();
		
		//-----------------------------------
		// Members...
		//-----------------------------------

		$member_record = array(	    'id'				     =>	$converge_id,
									'name'				     =>	$this->saved_data['admin_user'],
									'members_l_display_name' =>	strtolower( $this->saved_data['admin_user'] ),
									'members_l_username'	 =>	strtolower( $this->saved_data['admin_user'] ),
									'members_display_name'   =>	$this->saved_data['admin_user'],
									'mgroup'			     =>	4,
									'email'				     =>	$this->saved_data['admin_email'],
									'joined'			     =>	time(),
									'ip_address'		     =>	$this->ipsclass->my_getenv('REMOTE_ADDR'),
									'posts'				     =>	1,
									'title'				     =>	'Administrator',
									'last_visit'		     =>	time(),
									'last_activity'		     =>	time(),
									'member_login_key'       =>	md5( uniqid( microtime) ),
								);

		$this->ipsclass->DB->do_insert( 'members', $member_record );

		//-----------------------------------
		// Member Extra...
		//-----------------------------------

		$member_extra_record = array	(	'id'		=>	1,
											'signature'	=>	'',
											'vdirs'		=>	'',
										);

		$this->ipsclass->DB->do_insert( 'member_extra', $member_extra_record );
	}
	
	/**
	 * application_installer::cache_and_cleanup
	 * 
	 * Final install step, allows for any remaining app specific functions
	 *
	 */		
	function cache_and_cleanup()
	{
		//-----------------------------------
		// Get ACP library
		//-----------------------------------
		
		if( !defined("CACHE_PATH") )
		{
			define( 'CACHE_PATH', INS_ROOT_PATH."../" );
		}		
		
		$output[] = "Rebuild skin templates...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/lib/admin_cache_functions.php' );
		$acp           =  new admin_cache_functions();
		$acp->ipsclass =& $this->ipsclass;
		$acp->_rebuild_all_caches( array(2) );
		unset( $acp );
		
		//-------------------------------------------------------------
		// Forum cache
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding forum cache...";
		
		$ignore_me = array( 'redirect_url', 'redirect_loc', 'rules_text', 'permission_custom_error', 'notify_modq_emails' );
		
		if ( $this->ipsclass->vars['forum_cache_minimum'] )
		{
			$ignore_me[] = 'description';
			$ignore_me[] = 'rules_title';
		}
		
		$this->ipsclass->cache['forum_cache'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
											'from'   => 'forums',
											'order'  => 'parent_id, position'
								   )      );
		$this->ipsclass->DB->simple_exec();
		
		while( $f = $this->ipsclass->DB->fetch_row() )
		{
			$fr = array();
			
			$perms = unserialize(stripslashes($f['permission_array']));
			
			//-----------------------------------------
			// Stuff we don't need...
			//-----------------------------------------
			
			foreach( $f as $k => $v )
			{
				if ( in_array( $k, $ignore_me ) )
				{
					continue;
				}
				else
				{
					if ( $v != "" )
					{
						$fr[ $k ] = $v;
					}
				}
			}
			
			$fr['read_perms']   = $perms['read_perms'];
			$fr['reply_perms']  = $perms['reply_perms'];
			$fr['start_perms']  = $perms['start_perms'];
			$fr['upload_perms'] = $perms['upload_perms'];
			$fr['show_perms']   = $perms['show_perms'];
			
			unset($fr['permission_array']);
			
			$this->ipsclass->cache['forum_cache'][ $fr['id'] ] = $fr;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );

		//-------------------------------------------------------------
		// Group Cache
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding group cache...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/action_admin/groups.php' );
		$lib           =  new ad_groups();
		$lib->ipsclass =& $this->ipsclass;
		$lib->rebuild_group_cache();
		unset( $lib );

		//-------------------------------------------------------------
		// Systemvars
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding system variables cache...";
		
		$this->ipsclass->cache['systemvars'] = array();

		$result = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as cnt', 'from' => 'mail_queue' ) );

		$this->ipsclass->cache['systemvars']['mail_queue']    = intval( $result['cnt'] );
		$this->ipsclass->cache['systemvars']['task_next_run'] = time() + 3600;

		$this->ipsclass->update_cache( array( 'name' => 'systemvars', 'array' => 1, 'deletefirst' => 1 ) );

		//-------------------------------------------------------------
		// Stats
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding statistics cache...";
		
		$this->ipsclass->cache['stats'] = array();

		$this->ipsclass->cache['stats']['total_replies'] = 0;
		$this->ipsclass->cache['stats']['total_topics']  = 1;
		$this->ipsclass->cache['stats']['mem_count']     = 1;

		$r = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'id, name',
													  'from'   => 'members',
													  'order'  => 'id DESC',
													  'limit'  => '0,1'
											 )      );

		$this->ipsclass->cache['stats']['last_mem_name'] = $r['name'];
		$this->ipsclass->cache['stats']['last_mem_id']   = $r['id'];

		$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );

		//-------------------------------------------------------------
		// Ranks
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding member ranks cache...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/action_admin/member.php' );
		$lib           =  new ad_member();
		$lib->ipsclass =& $this->ipsclass;
		$lib->titles_recache();
		unset( $lib );

		//-------------------------------------------------------------
		// SETTINGS
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding settings cache...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/action_admin/settings.php' );
		$lib           =  new ad_settings();
		$lib->ipsclass =& $this->ipsclass;
		$lib->setting_rebuildcache();
		unset( $lib );

		//-------------------------------------------------------------
		// EMOTICONS
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding emoticons cache...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/action_admin/emoticons.php' );
		$lib           =  new ad_emoticons();
		$lib->ipsclass =& $this->ipsclass;
		$lib->emoticon_rebuildcache();
		unset( $lib );

		//-------------------------------------------------------------
		// LANGUAGES
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding languages cache...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/action_admin/languages.php' );
		$lib           =  new ad_languages();
		$lib->ipsclass =& $this->ipsclass;
		$lib->rebuild_cache();
		unset( $lib );

		//-------------------------------------------------------------
		// ATTACHMENT TYPES
		//-------------------------------------------------------------
		
		$output[] = "Rebuilding attachment types cache...";
		
		require_once( INS_DOC_ROOT_PATH.'sources/action_admin/attachments.php' );
		$lib           =  new ad_attachments();
		$lib->ipsclass =& $this->ipsclass;
		$lib->attach_type_rebuildcache();
		unset( $lib );
		
		$output[] = "All caches rebuilt, click 'Next' below...";
		
		$this->saved_data['admin_url'] = $this->saved_data['install_url'] . '/index.php';
		
		return $output;
	}
}

?>