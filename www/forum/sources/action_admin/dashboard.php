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
|   > $Date: 2007-05-01 19:00:44 +0100 (Tue, 01 May 2007) $
|   > $Revision: 958 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin "welcome" screen functions
|   > Module written by Matt Mecham
|   > Date started: 1st march 2002
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
class ad_dashboard
{
	# Global
	var $ipsclass;
	var $html;
	
	var $mysql_version = "";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "dashboard";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "dashboard";
		
	function auto_run()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		define( 'IPS_NEWS_URL'			, '' );
		define( 'IPS_BULLETINS_URL'		, '' );
		define( 'IPS_VERSION_CHECK_URL'	, '' );
		
		#$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
		
		$content         	= array();
		$thiscontent     	= "";
   		$latest_version  	= array();
   		$reg_end         	= "";
   		$sm_install      	= 0;
		$lock_file       	= 0;
		$converter		 	= 0;
		$fulltext_a		 	= 0;
		$fulltext_b		 	= 0;
		$unfinished_upgrade	= 0;
		$urls               = array( 'news'          => IPS_NEWS_URL,
									 'keiths_bits'   => IPS_BULLETINS_URL,
									 'version_check' => IPS_VERSION_CHECK_URL,
									 'blogs'         => '' );
		
		if ( @file_exists( ROOT_PATH . 'install/index.php' ) )
		{
			$sm_install = 1;
		}
		
		if ( @file_exists( ROOT_PATH . 'install/installfiles/lock.php' ) )
		{
			$lock_file = 1;
		}
		
		if ( @file_exists( ROOT_PATH . 'convert/index.php' ) )
		{
			$converter = 1;
		}
		
		if( $this->ipsclass->DB->sql_can_fulltext() )
		{
			if( ! $this->ipsclass->DB->sql_is_currently_fulltext( 'posts' ) )
			{
				$fulltext_a = 1;
			}
			
			if( $this->ipsclass->vars['search_sql_method'] != 'ftext' )
			{
				$fulltext_b = 1;
			}
		}
		
		if ( @file_exists( ROOT_PATH . 'upgrade/core/class_installer.php' ) )
		{
			define( 'INS_ROOT_PATH', ROOT_PATH.'upgrade/' );
			
			require_once ROOT_PATH . 'upgrade/core/class_installer.php';
			require_once ROOT_PATH . 'upgrade/custom/app.php';
			
			$upgrade = new application_installer();
			$upgrade->ipsclass =& $this->ipsclass;
			
			$upgrade->get_version_latest();
			
			if( $upgrade->last_poss_id > $upgrade->current_version )
			{
				$unfinished_upgrade = 1;
			}
		}
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_dashboard');
		
		//-----------------------------------------
		// continue...
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "";
		$this->ipsclass->admin->page_detail = "";
		
		//-----------------------------------------
		// Get MySQL & PHP Version
		//-----------------------------------------
		
		$this->ipsclass->DB->sql_get_version();
   		
   		//-----------------------------------------
   		// Upgrade history?
   		//-----------------------------------------
   		
   		$latest_version = array( 'upgrade_version_id' => NULL );
   		
   		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'upgrade_history', 'order' => 'upgrade_version_id DESC', 'limit' => array(1) ) );
   		$this->ipsclass->DB->simple_exec();
   		
   		while( $r = $this->ipsclass->DB->fetch_row() )
   		{
			$latest_version = $r;
   		}

		//-----------------------------------------
		// Resetting security image?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['reset_security_flag']) AND $this->ipsclass->input['reset_security_flag'] == 1 AND $this->ipsclass->input['new_build'] )
		{
			$new_build   = intval( $this->ipsclass->input['new_build'] );
			$new_reason  = trim( substr( $this->ipsclass->input['new_reason'], 0, 1 ) );
			$new_version = $latest_version['upgrade_version_id'].'.'.$new_build.'.'.$new_reason;
			
			$this->ipsclass->DB->do_update( 'upgrade_history', array( 'upgrade_notes' => $new_version ), 'upgrade_version_id='.$latest_version['upgrade_version_id'] );
		
			$latest_version['upgrade_notes'] = $new_version;
		}
		
		//-----------------------------------------
		// Got real version number?
		//-----------------------------------------
		
		$this->ipsclass->version = 'v'.$latest_version['upgrade_version_human'];
		$this->ipsclass->vn_full = ( isset($latest_version['upgrade_notes']) AND $latest_version['upgrade_notes'] ) ? $latest_version['upgrade_notes'] : $this->ipsclass->vn_full;
		
		//-----------------------------------------
		// Licensed?
		//-----------------------------------------
		
		$urls['keiths_bits'] = IPS_BULLETINS_URL . '?v=' . $this->ipsclass->vn_full;
		
		//-----------------------------------------
		// Notepad
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['save']) AND $this->ipsclass->input['save'] == 1 )
		{
			$this->ipsclass->update_cache( array( 'value' => $this->ipsclass->txt_stripslashes($_POST['notes']), 'name' => 'adminnotes', 'donow' => 1, 'deletefirst' => 0, 'array' => 0 ) );
		}
		
		$text = "You can use this section to keep notes for yourself and other admins, etc.";
		
		$this->ipsclass->init_load_cache( array( 'adminnotes', 'skinpanic' ) );
		
		if ( !isset($this->ipsclass->cache['adminnotes']) OR !$this->ipsclass->cache['adminnotes'] )
		{
			$this->ipsclass->update_cache( array( 'value' => $text, 'name' => 'adminnotes', 'donow' => 1, 'deletefirst' => 0, 'array' => 0 ) );
		
			$this->ipsclass->cache['adminnotes'] = $text;
		}
		
		$this->ipsclass->cache['adminnotes'] = htmlspecialchars($this->ipsclass->cache['adminnotes'], ENT_QUOTES);
		$this->ipsclass->cache['adminnotes'] = str_replace( "&amp;#", "&#", $this->ipsclass->cache['adminnotes'] );
		
		$content['ad_notes'] = $this->html->acp_notes( $this->ipsclass->cache['adminnotes'] );
		
		//-----------------------------------------
		// ADMINS USING CP
		//-----------------------------------------
		
		$t_time    = time() - 60*10;
		$time_now  = time();
		$seen_name = array();
		$acponline = "";
		
		$this->ipsclass->DB->build_query( array( 'select'   => 's.session_member_name, s.session_member_id, s.session_location, s.session_log_in_time, s.session_running_time, s.session_ip_address',
												 'from'     => array( 'admin_sessions' => 's' ),
												 'add_join' => array( 0 => array( 'select' => 'm.*',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => "m.id=s.session_member_id",
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=m.id',
																				  'type'   => 'left' ) ) ) );
		
		$this->ipsclass->DB->exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( isset($seen_name[ $r['session_member_name'] ]) AND $seen_name[ $r['session_member_name'] ] == 1 )
			{
				continue;
			}
			else
			{
				$seen_name[ $r['session_member_name'] ] = 1;
			}
			
			$r['_log_in'] = $time_now - $r['session_log_in_time'];
			$r['_click']  = $time_now - $r['session_running_time'];
			
			if ( ($r['_log_in'] / 60) < 1 )
			{
				$r['_log_in'] = sprintf("%0d", $r['_log_in']) . " seconds ago";
			}
			else
			{
				$r['_log_in'] = sprintf("%0d", ($r['_log_in'] / 60) ) . " minutes ago";
			}
			
			if ( ($r['_click'] / 60) < 1 )
			{
				$r['_click'] = sprintf("%0d", $r['_click']) . " seconds ago";
			}
			else
			{
				$r['_click'] = sprintf("%0d", ($r['_click'] / 60) ) . " minutes ago";
			}
			
			$r['session_location'] = ( $r['session_location'] == ',' ) ? 'dashboard,' : $r['session_location'];
			
			$sessionbits = explode( ",", $r['session_location'] );
			
			$r['session_location'] = $r['session_location'] ? "<a href='{$this->ipsclass->base_url}&section=" . $sessionbits[0] . "&act=" . $sessionbits[1] . "'>{$r['session_location']}</a>" : 'Index';
			
			$acponline .= $this->html->acp_onlineadmin_row( $this->ipsclass->member_set_information( $r ) );
		}
		
		$content['acp_online'] = $this->html->acp_onlineadmin_wrapper( $acponline );
		
		//-----------------------------------------
		// Stats
		//-----------------------------------------
		
		$reg	= $this->ipsclass->DB->simple_exec_query( array( 'select' 	=> 'COUNT(*) as reg'  , 
																 'from' 	=> array( 'validating' => 'v' ), 
																 'where' 	=> 'v.lost_pass <> 1 AND m.mgroup=' . $this->ipsclass->vars['auth_group'],
																 'add_join'	=> array(
																 					array( 'from'	=> array( 'members' => 'm' ),
																 							'where'	=> 'm.id=v.member_id',
																 							'type'	=> 'left'
																 						)
																 					)
														) 		);
		
		if( $this->ipsclass->vars['ipb_bruteforce_attempts'] )
		{
			$lock	= $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as mems'  , 'from' => 'members', 'where' => 'failed_login_count >= ' . $this->ipsclass->vars['ipb_bruteforce_attempts'] ) );
		}
		
		$coppa	 = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as coppa', 'from' => 'validating', 'where' => 'coppa_user=1' ) );

		$my_timestamp = time() - $this->ipsclass->vars['au_cutoff'] * 60;
				
		$online	 = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'COUNT(*) as sessions', 'from' => 'sessions', 'where' => 'running_time>' . $my_timestamp ) );
		
		$pending = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'SUM(queued_topics) as topics, SUM(queued_posts) as posts', 'from' => 'forums' ) );

		$content['stats'] = $this->html->acp_stats_wrapper( array( 'topics'      => intval($this->ipsclass->cache['stats']['total_topics']),
																   'replies'     => intval($this->ipsclass->cache['stats']['total_replies']),
																   'topics_mod'	 => intval($pending['topics']),
																   'posts_mod'	 => intval($pending['posts']),
																   'members'     => intval($this->ipsclass->cache['stats']['mem_count']),
																   'validate'    => intval( $reg['reg'] ),
																   'locked'		 => intval( $lock['mems'] ),
																   'coppa'       => intval( $coppa['coppa'] ),
																   'sql_driver'  => strtoupper(SQL_DRIVER),
																   'sql_version' => $this->ipsclass->DB->true_version,
																   'php_version' => phpversion(),
																   'sessions'	 => intval($online['sessions']),
																   'php_sapi'    => @php_sapi_name(),
																   'ipb_version' => $this->ipsclass->version,
																   'ipb_id'      => $this->ipsclass->vn_full ) );
		
		//-----------------------------------------
		// Members awaiting admin validation?
		//-----------------------------------------
		
		if( $this->ipsclass->vars['reg_auth_type'] == 'admin_user' OR $this->ipsclass->vars['reg_auth_type'] == 'admin' )
		{
			$where_extra = $this->ipsclass->vars['reg_auth_type'] == 'admin_user' ? ' AND user_verified=1' : '';

			$admin_reg	= $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as reg'  , 'from' => 'validating', 'where' => 'new_reg=1' . $where_extra ) );

			if( $admin_reg['reg'] > 0 )
			{
				// We have some member's awaiting admin validation
				$data = null;
				
				$this->ipsclass->DB->build_query( array( 'select' 	=> 'v.*',
														 'from'		=> array( 'validating' => 'v' ),
														 'where'	=> 'new_reg=1' . $where_extra,
														 'limit'	=> array( 3 ),
														 'add_join'	=> array(
														 					array( 'type'		=> 'left',
														 							'select'	=> 'm.members_display_name, m.email, m.ip_address',
														 							'from'		=> array( 'members' => 'm' ),
														 							'where'		=> 'm.id=v.member_id'
														 						 )
														 					)
												)		);
				$this->ipsclass->DB->exec_query();
				
				while( $r = $this->ipsclass->DB->fetch_row() )
				{
					if ($r['coppa_user'] == 1)
					{
						$r['_coppa'] = ' ( COPPA )';
					}
					else
					{
						$r['_coppa'] = "";
					}
					
					$r['_entry']  = $this->ipsclass->get_date( $r['entry_date'], 'TINY' );
				
					$data .= $this->html->acp_validating_block( $r );
					
					
				}
				
				$content['validating'] = $this->html->acp_validating_wrapper( $data );
			}
		}

		//-----------------------------------------
		// Forum and group dropdowns
		//-----------------------------------------
		$this->ipsclass->forums->forums_init();
		
		$forums 		= $this->ipsclass->forums->forums_forum_jump( 1 );

		$groups			= array();
		$groups_html 	= '';
		
		foreach( $this->ipsclass->cache['group_cache'] as $k => $v )
		{
			$groups[ $v['g_title'] ] = "<option value='{$k}'>{$v['g_title']}</option>";
		}
		
		ksort( $groups );
		
		$groups_html = implode( "\n", $groups );
		
		//-----------------------------------------
		// Piece it together
		//-----------------------------------------
		
		$urls['version_check'] = IPS_VERSION_CHECK_URL . '?' . base64_encode( $this->ipsclass->vn_full.'|^|'.$this->ipsclass->vars['board_url'] );
		
		$this->ipsclass->html .= $this->html->acp_main_template( $content, $forums, $groups_html, $urls );
		
		//-----------------------------------------
		// Trashed skin?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->cache['skinpanic']) AND $this->ipsclass->cache['skinpanic'] == 'rebuildemergency' )
		{
			$skinpanic = $this->html->warning_box( "Warning: A skin error occured", $this->html->warning_rebuild_emergency() ) . "<br />";
			
			$this->ipsclass->html = str_replace( '<!--warningskin-->', $skinpanic, $this->ipsclass->html );
		}
		
		if ( isset($this->ipsclass->cache['skinpanic']) AND $this->ipsclass->cache['skinpanic'] == 'rebuildupgrade' )
		{
			$skinupgrade = $this->html->warning_box( "An upgrade has been performed", $this->html->warning_rebuild_upgrade() ) . "<br />";

			$this->ipsclass->html = str_replace( '<!--warningskin-->', $skinupgrade, $this->ipsclass->html );
		}
		
		//-----------------------------------------
		// IN DEV stuff...
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
				
				$this->ipsclass->html = str_replace( '<!--in_dev_check-->', $_html, $this->ipsclass->html );	
			}
			
			if ( @file_exists( ROOT_PATH . '_dev_notes.txt' ) )
			{
				$_notes = @file_get_contents( ROOT_PATH . '_dev_notes.txt' );
				
				if ( $_notes )
				{
					$_html = $this->ipsclass->skin_acp_global->information_box( "Developers' Notes", nl2br($_notes) ) . "<br />";
					$this->ipsclass->html = str_replace( '<!--in_dev_notes-->', $_html, $this->ipsclass->html );
				}
			}
		}
		
		//-----------------------------------------
		// INSTALLER PRESENT?
		//-----------------------------------------
		
		if ( $sm_install == 1 ) 
		{
			if ( $lock_file != 1 )
			{
				$installer = $this->html->warning_box( "Unlocked Installer", $this->html->warning_unlocked_installer() ) . "<br />";
	
				$this->ipsclass->html = str_replace( '<!--warninginstaller-->', $installer, $this->ipsclass->html );
			}
			else
			{
				$installer = $this->html->warning_box( "Installer Present", $this->html->warning_installer() ) . "<br />";
	
				$this->ipsclass->html = str_replace( '<!--warninginstaller-->', $installer, $this->ipsclass->html );
			}
		}
		else if( $converter )
		{
			$installer = $this->html->warning_box( "Converter Present", $this->html->warning_converter() ) . "<br />";

			$this->ipsclass->html = str_replace( '<!--warninginstaller-->', $installer, $this->ipsclass->html );
		}		
		
		//-----------------------------------------
		// UNFINISHED UPGRADE?
		//-----------------------------------------
		
		if ( $unfinished_upgrade == 1 ) 
		{
			$upgrade = $this->html->warning_box( "Unfinished Upgrade", $this->html->warning_upgrade() ) . "<br />";

			$this->ipsclass->html = str_replace( '<!--warningupgrade-->', $upgrade, $this->ipsclass->html );
		}		
		
		//-----------------------------------------
		// INSUFFICIENT PHP VERSION?
		//-----------------------------------------
		
		if ( PHP_VERSION < '4.3.0' )
		{
			$version = $this->html->warning_box( "Your PHP Version (" . PHP_VERSION . ") is insufficient",
																	$this->html->acp_php_version_warning() ) . "<br />";

			$this->ipsclass->html = str_replace( '<!--phpversioncheck-->', $version, $this->ipsclass->html );
		}
		
		//-----------------------------------------
		// FULLTEXT NOT ENABLED?
		//-----------------------------------------
		
		if ( $fulltext_a OR $fulltext_b )
		{
			$hide = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'conf_value', 'from' => 'conf_settings', 'where' => "conf_key='hide_ftext_note'" ) );

			if( !$hide['conf_value'] )
			{
				$fulltext = $this->html->warning_box( "Not Using Full Text", $this->html->acp_ftext_warning( $fulltext_a, $fulltext_b ) ) . "<br />";
	
				$this->ipsclass->html = str_replace( '<!--warningftext-->', $fulltext, $this->ipsclass->html );
			}
		}
		
		//-----------------------------------------
		// BOARD OFFLINE?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['board_offline'] )
		{
			$offline = $this->html->warning_box( "Board Offline", "Your board is currently offline<br /><br />&raquo; <a href='{$this->ipsclass->base_url}&section=tools&act=op&code=findsetting&key=boardoffline'>Turn Board Online</a>" ) . "<br />";

			$this->ipsclass->html = str_replace( '<!--boardoffline-->', $offline, $this->ipsclass->html );
		}
		
		//-----------------------------------------
		// ROOT ADMIN?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] )
		{
			//-----------------------------------------
			// Last 5 log in attempts
			//-----------------------------------------
			
			$this->ipsclass->DB->build_query( array( 'select' => '*',
													 'from'   => 'admin_login_logs',
													 'order'  => 'admin_time DESC',
													 'limit'  => array( 0, 5 ) ) );
			
			$this->ipsclass->DB->exec_query();
			
			while ( $rowb = $this->ipsclass->DB->fetch_row() )
			{
				$rowb['_admin_time'] = $this->ipsclass->admin->get_date( $rowb['admin_time'] );
				$rowb['_admin_img']  = $rowb['admin_success'] ? 'aff_tick.png' : 'aff_cross.png';
				
				$logins .= $this->html->acp_last_logins_row( $rowb );
			}
			
			$this->ipsclass->html = str_replace( '<!--acplogins-->', $this->html->acp_last_logins_wrapper( $logins ), $this->ipsclass->html );
		}
		
		$this->ipsclass->admin->output();
	}
	
}


?>