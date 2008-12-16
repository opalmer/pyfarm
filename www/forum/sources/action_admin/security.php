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
|   > $Date: 2006-06-08 17:11:50 +0100 (Thu, 08 Jun 2006) $
|   > $Revision: 289 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Logs Stuff
|   > Module written by Matt Mecham
|   > Date started: 11nd September 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_security
{

	var $base_url;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "admin";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "security";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'IPB Security Center' );
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_security');
		
		switch($this->ipsclass->input['code'])
		{
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->security_overview();
				break;
			case 'stronghold':
				$this->do_stronghold();
				break;
			case 'dynamic_images':
				$this->do_dynamic_images();
				break;
			case 'acplink':
				$this->do_acplink();
				break;
			case 'virus_check':
				$this->anti_virus_check();
				break;
			case 'deep_scan':
				$this->deep_scan();
				break;
			case 'list_admins':
				$this->list_admins();
				break;
			case 'htaccess':
				$this->do_htaccess();
				break;
			case 'confglobal':
				$this->do_confglobal();
				break;
			case 'acprename':
				$this->do_acprename();
				break;
				
			case 'acphtaccess':
				$this->acphtaccess_form();
				break;
			case 'acphtaccess_do':
				$this->acphtaccess_do();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP HTACCESS: Step two
	/*-------------------------------------------------------------------------*/
	
	function acphtaccess_do()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name = trim( $_POST['name'] );
		$pass = trim( $_POST['pass'] );
		
		$htaccess_pw   = "";
		$htaccess_auth = "";
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $name or ! $pass )
		{
			$this->ipsclass->main_msg = "You must complete the form";
			$this->acphtaccess_form();
			return;
		}
		
		//-----------------------------------------
		// Format files...
		//-----------------------------------------
		
		$htaccess_auth = "AuthType Basic\n"
					   . "AuthName \"IPB ACP\"\n"
					   . "AuthUserFile " . ROOT_PATH . IPB_ACP_DIRECTORY . "/.htpasswd\n"
				       . "Require valid-user\n";
				
		$htaccess_pw   = $name . ":" . crypt( $pass, base64_encode( $pass ) );
		
		if ( $FH = @fopen( ROOT_PATH . IPB_ACP_DIRECTORY . '/' . '.htpasswd', 'w' ) )
		{
			fwrite( $FH, $htaccess_pw );
			fclose( $FH );
			
			$FF = @fopen( ROOT_PATH . IPB_ACP_DIRECTORY . '/' . '.htaccess', 'w' );
			fwrite( $FF, $htaccess_auth );
			fclose( $FF );
			
			$this->ipsclass->main_msg = "Authentication files written";
			$this->security_overview();
		}
		else
		{
			$this->ipsclass->html .= $this->html->htaccess_data( $htaccess_pw, $htaccess_auth );

			$this->ipsclass->admin->nav[] = array( '', 'ACP .htaccess' );

			$this->ipsclass->admin->output();
		}
		
		
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP HTACCESS: Step One
	/*-------------------------------------------------------------------------*/
	
	function acphtaccess_form()
	{
		//-----------------------------------------
		// Show it
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->htaccess_form();
		
		$this->ipsclass->admin->nav[] = array( '', 'ACP .htaccess' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Rename ACP directory
	/*-------------------------------------------------------------------------*/
	
	function do_acprename()
	{
		//-----------------------------------------
		// Show it
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->rename_admin_dir();
		
		$this->ipsclass->admin->nav[] = array( '', 'Rename the admin directory' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Change conf global
	/*-------------------------------------------------------------------------*/
	
	function do_confglobal()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$done = 0;
		
		//-----------------------------------------
		// 	Try...
		//-----------------------------------------
		
		if ( @chmod( ROOT_PATH . 'conf_global.php', 0444) )
		{
			$done = 1;
		}
		
		//-----------------------------------------
		// Wow, that was really hard. I deserve a
		// payraise after this function...
		//-----------------------------------------
		
		if ( $done )
		{
			$this->ipsclass->main_msg = "CHMOD change completed.";
		}
		else
		{
			$this->ipsclass->main_msg = "<strong>Could not complete the process.</strong><br />Please use your FTP client to change the CHMOD value of 'conf_global.php' to 0444.";
		}
		
		$this->security_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// Add htaccess to non IPB dirs
	/*-------------------------------------------------------------------------*/
	
	function do_htaccess()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name = '.htaccess';
		$msg  = array();
		$dirs = array( ROOT_PATH . 'cache',
					   ROOT_PATH . 'skin_acp',
					   ROOT_PATH . 'style_avatars',
					   ROOT_PATH . 'style_emoticons',
					   ROOT_PATH . 'style_images',
					   ROOT_PATH . 'style_captcha',
					   ROOT_PATH . 'uploads' );

		$towrite = <<<EOF

#<ipb-protection>
<Files ~ "^.*\.(php|cgi|pl|php3|php4|php5|php6|phtml|shtml)">
    Order allow,deny
    Deny from all
</Files>
#</ipb-protection>
EOF;

		//-----------------------------------------
		// Do it!
		//-----------------------------------------
	
		foreach( $dirs as $directory )
		{
			if ( $FH = @fopen( $directory . '/'. $name, 'a+' ) )
			{
				fwrite( $FH, $towrite );
				fclose( $FH );
			
				$msg[] = "Written .htaccess to $directory...";
			}
			else
			{
				$msg[] = "Skipped $directory, could not write into it...";
			}
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->ipsclass->main_msg = implode( "<br />", $msg );
		$this->security_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// List admins
	/*-------------------------------------------------------------------------*/
	
	function list_admins()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content = "";
		$groups  = array();
		$query   = "";
		$members = array();
		
		//-----------------------------------------
		// Get all admin groups...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
											     'from'   => 'groups',
											  	 'where'  => 'g_access_cp > 0 AND g_access_cp IS NOT NULL' ) );
		
		$o = $this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row( $o ) )
		{
			$_gid = intval( $row['g_id'] );
			
			# I hate looped queries, but this should be OK.
			
			$this->ipsclass->DB->build_query( array( 'select' => '*',
												     'from'   => 'members',
												  	 'where'  => "mgroup=" . $_gid ." OR mgroup_others LIKE '%,". $_gid .",%' OR mgroup_others='".$_gid."' OR mgroup_others LIKE '".$_gid.",%' OR mgroup_others LIKE '%,".$_gid."'",
												     'order'  => 'joined DESC' ) );

			$b = $this->ipsclass->DB->exec_query();
			
			while( $member = $this->ipsclass->DB->fetch_row( $b ) )
			{
				if ( ! $member['mgroup'] AND ! $member['mgroup_others'] )
				{
					continue;
				}
				
				$members[ $member['id'] ] = $member;
			}
			
			$groups[ $row['g_id'] ] = $row;
		}
		
		//-----------------------------------------
		// Generate list
		//-----------------------------------------
		
		foreach( $members as $id => $member )
		{
			$member['members_display_name'] = $member['members_display_name'] ? $member['members_display_name'] : $member['name'];
			$member['_mgroup']				= $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_title'];
			$_tmp                           = array();
			$member['_joined']              = $this->ipsclass->get_date( $member['joined'], 'JOINED' );
			
			foreach( explode( ",", $member['mgroup_others'] ) as $gid )
			{
				if ( $gid )
				{
					$_tmp[] = $this->ipsclass->cache['group_cache'][ $gid ]['g_title'];
				}
			}
			
			$member['_mgroup_others'] = implode( ", ", $_tmp );
			
			$content .= $this->html->list_admin_row( $member );
		}
		
		//$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( "Members with ACP Access", "Below is a list of all members with access to your ACP.<br />If you do not recognize any, please remove their ACP access immediately." ) ."<br />";
		$this->ipsclass->html .= $this->html->list_admin_overview( $content );
		
		$this->ipsclass->admin->nav[] = array( '', 'List Administrators' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Deep scan
	/*-------------------------------------------------------------------------*/
	
	function deep_scan()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$filter          = trim( $this->ipsclass->input['filter'] );
		$file_count      = 0;
		$bad_count       = 0;
		$content         = "";
		$checked_content = "";
		$colors          = array( 0  => '#84ff00',
								  1  => '#84ff00',
								  2  => '#b5ff00',
								  3  => '#b5ff00',
								  4  => '#ffff00',
								  5  => '#ffff00',
								  6  => '#ffde00',
								  7  => '#ffde00',
								  8  => '#ff8400',
								  9  => '#ff8400',
								  10 => '#ff0000' );
							 
		//-----------------------------------------
		// Get class
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/class_virus_checker.php' );
		$class_virus_checker           = new class_virus_checker();
		$class_virus_checker->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Run it...
		//-----------------------------------------
		
		$class_virus_checker->anti_virus_deep_scan( ROOT_PATH, '(php|cgi|pl|perl|php3|php4|php5|php6)' );
		
		//-----------------------------------------
		// Update...
		//-----------------------------------------
		
		$cache_array =& $this->ipsclass->cache['systemvars'];
		
		$cache_array['last_deepscan_check'] = time();
		
		$this->ipsclass->update_cache( array( 'name'  => 'systemvars',
											  'value' => $cache_array,
											  'array' => 1,
											  'donow' => 1 ) );
											
		//-----------------------------------------
		// Got any bad files?
		//-----------------------------------------
		
		if ( is_array( $class_virus_checker->bad_files ) and count( $class_virus_checker->bad_files ) )
		{
			foreach( $class_virus_checker->bad_files as $idx => $data )
			{
				$file_count++;
				
				$_data = array();
				$_info = stat( $data['file_path'] );
				
				$_data['size']        = filesize( $data['file_path'] );
				$_data['human']       = ceil( $_data['size'] / 1024 );
				$_data['mtime']       = $this->ipsclass->get_date( $_info['mtime'], 'SHORT' );
				$_data['score']       = $data['score'];
				$_data['left_width']  = $data['score'] * 5;
				$_data['right_width'] = 50 - $_data['left_width'];
				$_data['color']       = $colors[ $data['score'] ];
				
				if ( $data['score'] >= 7 )
				{
					$bad_score++;
				}
				
				if ( strstr( $filter, 'score' ) )
				{
					$_filter = intval( str_replace( 'score-', '', $filter ) );
					
					if ( $data['score'] < $_filter )
					{
						continue;
					}
				}
				else if ( $filter == 'large' )
				{
					if ( $_data['human'] < 55 )
					{
						continue;
					}
				}
				else if ( $filter == 'recent' )
				{
					if ( $_info['mtime'] < time() - 86400 * 30 )
					{
						continue;
					} 
				}
				else if ( $filter == 'all' )
				{
					
				}
				else
				{
					$filter = "";
				}
				
				if ( strtoupper( substr(PHP_OS, 0, 3) ) == 'WIN' )
				{
					$file_path = str_replace( ROOT_PATH, "",  $data['file_path'] );
					$file_path = str_replace( "\\", "/", $file_path );
					
					$data['file_path'] = str_replace( "/\\", "\\", $data['file_path'] );
				}				
				else
				{
					$file_path         = str_replace( ROOT_PATH.'/', '', $data['file_path'] );
					$data['file_path'] = str_replace( ROOT_PATH.'/', '', $data['file_path'] );
				}
				
				$content .= $this->html->deep_scan_bad_files_row( $file_path, $data['file_path'], $_data );
			}
			
			if ( $bad_score )
			{
				$this->ipsclass->html .= $this->ipsclass->skin_acp_global->warning_box( 'All Executables', 'The deep scanner has found the following files.<br /><strong>'.$bad_score.'</strong> of '.$file_count.' files are rating 7/10 or more.<br />If you\'re unsure of their origin, please investigate them immediately.' ) . "<br />";
			}
			else
			{
				$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( 'All Executables', 'The deep scanner has found '.$file_count.' files.<br />If you\'re unsure of their origin, please investigate them immediately.' ) . "<br />";
			}
			
			$this->ipsclass->html .= $this->html->deep_scan_bad_files_wrapper( $content );
		}
		
		//-----------------------------------------
		// Fix filter...
		//-----------------------------------------
		
		if ( $filter )
		{
			$this->ipsclass->html = preg_replace( "#(value=[\"']".preg_quote( $filter, '#' )."['\"])#i", "\\1 selected='selected'", $this->ipsclass->html );
		}
		
		$this->ipsclass->admin->nav[] = array( '', 'Deep Scan' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Anti virus checker
	/*-------------------------------------------------------------------------*/
	
	function anti_virus_check()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content         = "";
		$checked_content = "";
		$colors          = array( 0  => '#84ff00',
								  1  => '#84ff00',
								  2  => '#b5ff00',
								  3  => '#b5ff00',
								  4  => '#ffff00',
								  5  => '#ffff00',
								  6  => '#ffde00',
								  7  => '#ffde00',
								  8  => '#ff8400',
								  9  => '#ff8400',
								  10 => '#ff0000' );
							 
		//-----------------------------------------
		// Get class
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/class_virus_checker.php' );
		$class_virus_checker           = new class_virus_checker();
		$class_virus_checker->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Run it...
		//-----------------------------------------
		
		$class_virus_checker->run_scan();
		
		//-----------------------------------------
		// Update...
		//-----------------------------------------
		
		$cache_array =& $this->ipsclass->cache['systemvars'];
		
		$cache_array['last_virus_check'] = time();
		
		$this->ipsclass->update_cache( array( 'name'  => 'systemvars',
											  'value' => $cache_array,
											  'array' => 1,
											  'donow' => 1 ) );
											
		//-----------------------------------------
		// Got any bad files?
		//-----------------------------------------
		
		if ( is_array( $class_virus_checker->bad_files ) and count( $class_virus_checker->bad_files ) )
		{
			foreach( $class_virus_checker->bad_files as $idx => $data )
			{
				$_data = array();
				$_info = stat( $data['file_path'] );
				
				$_data['size']        = filesize( $data['file_path'] );
				$_data['human']       = ceil( $_data['size'] / 1024 );
				$_data['mtime']       = $this->ipsclass->get_date( $_info['mtime'], 'SHORT' );
				$_data['score']       = $data['score'];
				$_data['left_width']  = $data['score'] * 5;
				$_data['right_width'] = 50 - $_data['left_width'];
				$_data['color']       = $colors[ $data['score'] ];
				
				if ( strtoupper( substr(PHP_OS, 0, 3) ) == 'WIN' )
				{
					$root_path = str_replace( "/", "\\", ROOT_PATH );
					$file_path = str_replace( $root_path, "",  $data['file_path'] );
					$file_path = str_replace( "\\", "/", $file_path );
				}				
				else
				{
					$file_path = str_replace( ROOT_PATH, '', $data['file_path'] );
				}
				
				$content .= $this->html->anti_virus_bad_files_row( $file_path, $data['file_path'], $_data );
			}
			
			$this->ipsclass->html .= $this->ipsclass->skin_acp_global->warning_box( 'Suspicious Files Detected', 'The unauthorized file scan located the following suspicious files.<br />If you\'re unsure of their origin, please remove them immediately.' ) . "<br />";
			
			$this->ipsclass->html .= $this->html->anti_virus_bad_files_wrapper( $content );
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->skin_acp_global->information_box( 'No Suspicious Files Detected', 'The unauthorized file scan did not identify any suspicious files.<br />Please scan regularly to ensure that your system is secure' ) . "<br />";
		}
		
		//-----------------------------------------
		// Show checked folders...
		//-----------------------------------------
		
		if ( is_array( $class_virus_checker->checked_folders ) and count( $class_virus_checker->checked_folders ) )
		{
			foreach( $class_virus_checker->checked_folders as $name )
			{
				$checked_content .= $this->html->anti_virus_checked_row( str_replace( ROOT_PATH, '', $name ) );
			}
			
			$this->ipsclass->html .= $this->html->anti_virus_checked_wrapper( $checked_content );
		}
		
		$this->ipsclass->admin->nav[] = array( '', 'Unauthorized File Check' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
    // ACP LINK
    /*-------------------------------------------------------------------------*/
	
	function do_acplink()
	{
		//-----------------------------------------
		// Update the setting...
		//-----------------------------------------
		
		$this->update_setting( 'security_remove_acp_link', $this->ipsclass->vars['security_remove_acp_link'] ? 0 : 1 );
		
		//-----------------------------------------
		// Done..
		//-----------------------------------------
		
		$lang = $this->ipsclass->vars['security_remove_acp_link'] == 0 ? 'restored' : 'removed';
		
		$this->ipsclass->main_msg = "ACP link display {$lang}";
		$this->security_overview();
	}
	
	/*-------------------------------------------------------------------------*/
    // DYNAMIC IMAGES
    /*-------------------------------------------------------------------------*/
	
	function do_dynamic_images()
	{
		//-----------------------------------------
		// Update the setting...
		//-----------------------------------------
		
		$this->update_setting( 'allow_dynamic_img', $this->ipsclass->vars['allow_dynamic_img'] ? 0 : 1 );
		
		//-----------------------------------------
		// Done..
		//-----------------------------------------
		
		$lang = $this->ipsclass->vars['allow_dynamic_img'] == 0 ? 'disabled' : 'enabled';
		
		$this->ipsclass->main_msg = "Dynamic images {$lang}";
		$this->security_overview();
	}
	
	/*-------------------------------------------------------------------------*/
    // STRONG HOLD COOKIE
    /*-------------------------------------------------------------------------*/
	
	function do_stronghold()
	{
		//-----------------------------------------
		// Update the setting...
		//-----------------------------------------
		
		$this->update_setting( 'cookie_stronghold', $this->ipsclass->vars['cookie_stronghold'] ? 0 : 1 );
		
		//-----------------------------------------
		// Done..
		//-----------------------------------------
		
		$lang = $this->ipsclass->vars['cookie_stronghold'] == 0 ? 'disabled' : 'enabled';
		
		$this->ipsclass->main_msg = "Cookie stronghold {$lang}";
		$this->security_overview();
	}
	
	/*-------------------------------------------------------------------------*/
    // Update setting
    /*-------------------------------------------------------------------------*/
	
	function update_setting( $key, $value )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $key )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Update DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $value ), "conf_key='".$key."'" );
		
		//-----------------------------------------
		// Rebuild settings cache
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_admin/settings.php' );
		$settings           =  new ad_settings();
		$settings->ipsclass =& $this->ipsclass;
		
		$settings->setting_rebuildcache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		return TRUE;
	}
	
	
	/*-------------------------------------------------------------------------*/
    // View current log in logs
    /*-------------------------------------------------------------------------*/
	
	function security_overview()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content     = array( 'bad' => '', 'good' => '', 'ok' => '' );
		$cache_array =& $this->ipsclass->cache['systemvars'];
		
		//-----------------------------------------
		// Virus checker link
		//-----------------------------------------
		
		if ( intval($cache_array['last_virus_check']) < time() - 7 * 86400 )
		{
			$content['bad'] .= $this->html->security_item_bad(  'IPB Unauthorized File Checker',
			 													'The IPB unauthorized file checker will check your IPB installation for suspicious files.<br />The unauthorized file checker has not been run in over a week',
																'Run Tool Now',
																$this->ipsclass->form_code_js.'&code=virus_check',
																'vchecker' );
														
		}
		else
		{
			$last_run 		  = $this->ipsclass->get_date( $cache_array['last_virus_check'], 'SHORT' );
			$content['good'] .= $this->html->security_item_good( 'IPB Unauthorized File Checker',
			 													 'The IPB unauthorized file checker will check your IPB installation for suspicious files.<br />The unauthorized file checker was last run: '.$last_run,
																 'Run Tool Now',
																 $this->ipsclass->form_code_js.'&code=virus_check',
																 'vchecker' );
		}
		
		//-----------------------------------------
		// Deep scan link
		//-----------------------------------------
		
		if ( intval($cache_array['last_deepscan_check']) < time() - 30 * 86400 )
		{
			$content['bad'] .= $this->html->security_item_bad(  'IPB Executables Deep Scan',
			 													'The IPB deep scanner will pick out and list every single executable file in your installation.<br />The scanner has not been run in over a month',
																'Run Tool Now',
																$this->ipsclass->form_code_js.'&code=deep_scan',
																'deepscan' );
														
		}
		else
		{
			$last_run 		  = $this->ipsclass->get_date( $cache_array['last_deepscan_check'], 'SHORT' );
			$content['good'] .= $this->html->security_item_good(  'IPB Executables Deep Scan',
			 													  'The IPB deep scanner will pick out and list every single executable file in your installation.<br />The scanner was last run: '.$last_run,
																  'Run Tool Now',
																   $this->ipsclass->form_code_js.'&code=deep_scan',
																  'deepscan' );
		}
									  
		//-----------------------------------------
		// Get .htaccess settings
		//-----------------------------------------
		
		if ( strtoupper( substr(PHP_OS, 0, 3) ) !== 'WIN' )
		{
			$_extra = '';
			
			if ( ! is_writeable( ROOT_PATH . IPB_ACP_DIRECTORY ) )
			{
				$_extra = "<div style='color:red;font-weight:bold'>IPB cannot write the .htaccess files into your '/admin/' directory. Please use your FTP client to CHMOD it to 0777.</div>";
			}
			
			if ( ! file_exists( ROOT_PATH . IPB_ACP_DIRECTORY . '/.htaccess' ) )
			{
				$content['ok'] .= $this->html->security_item_ok(    'IPB ACP .htaccess Protection',
				 													'To make your ACP even more secure, you can add HTTP authentication in your "/admin/" directory.<br />IPB cannot locate an ACP .htaccess file.'. $_extra,
																	'Learn More',
																	$this->ipsclass->form_code_js.'&code=acphtaccess',
																	'acphtaccess' );
			}
			else
			{
				$content['good'] .= $this->html->security_item_good( 'IPB ACP .htaccess Protection',
				 											 		 'To make your ACP even more secure, you can take add HTTP authentication in your "/admin/" directory.<br />IPB has located an ACP .htaccess file.'.$_extra,
																	 'Learn More',
																	 $this->ipsclass->form_code_js.'&code=acphtaccess',
																	 'acphtaccess' );
			}
			
			# Other htaccess protection
			if ( ! file_exists( ROOT_PATH . 'style_emoticons/.htaccess' ) )
			{
				$content['ok'] .= $this->html->security_item_ok( 'IPB PHP/CGI .htaccess Protection',
				 												 'IPB can write .htaccess files to non-PHP directories to prevent PHP and CGI files from executing.<br />IPB cannot locate any .htaccess files.',
																 'Run Tool Now',
																 $this->ipsclass->form_code_js.'&code=htaccess',
																 'htaccess' );
			}
			else
			{
				$content['good'] .= $this->html->security_item_good( 'IPB .htaccess Protection',
				 											 		 'IPB can write .htaccess files to non-PHP directories to prevent PHP and CGI files from executing.<br />IPB has located some .htaccess files.',
																	 'Run Tool Now',
																	 $this->ipsclass->form_code_js.'&code=htaccess',
																	 'htaccess' );
			}
			
			//-----------------------------------------
			// Conf global
			//-----------------------------------------
			
			if ( is_writeable( ROOT_PATH . 'conf_global.php' ) )
			{
				$content['bad'] .= $this->html->security_item_bad( 'Make "conf_global" un-writeable',
				 												   'After installation, you should change the CHMOD on the "conf_global.php" file to prevent others from reading and writing to it.<br />"conf_global.php" is writeable.',
																   'Run Tool Now',
																   $this->ipsclass->form_code_js.'&code=confglobal',
																   'confglobal' );
															
			}
			else
			{
				$content['good'] .= $this->html->security_item_good(  'Make "conf_global" un-writeable',
				 												 	  'After installation, you should change the CHMOD on the "conf_global.php" file to prevent others from reading and writing to it.<br />"conf_global.php" is NOT writeable.',
																	   'Learn More',
																	   $this->ipsclass->form_code_js.'&code=confglobal',
																	   'confglobal' );
			}
		}
		
		//-----------------------------------------
		// Dynamic images
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['allow_dynamic_img'] )
		{
			$content['good'] .= $this->html->security_item_good( 'Disable Dynamic Images',
			 												  	 'IPB can stop dynamic images being posted on your forums. Dynamic images pose a security risk as they allow javascript to run.<br />Dynamic images are already disabled.',
																 'Toggle Now',
																 $this->ipsclass->form_code_js.'&code=dynamic_images',
																 'dynamic_images' );
														
		}
		else
		{
			$content['bad'] .= $this->html->security_item_bad( 'Disable Dynamic Images',
			 											       'IPB can stop dynamic images being posted on your forums. Dynamic images pose a security risk ask they allow javascript to run.<br />Dynamic images are ENABLED.',
														       'Toggle Now',
														        $this->ipsclass->form_code_js.'&code=dynamic_images',
														       'dynamic_images' );
		}
		
		//-----------------------------------------
		// Strong hold cookie 
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['cookie_stronghold'] )
		{
			$content['bad'] .= $this->html->security_item_bad(  'Enable the Stronghold Cookie',
			 													'IPB can store a stronghold cookie in the user\'s browser which is used when automatically logging in to prevent successful cookie theft.<br />Stronghold cookies are disabled.',
																'Toggle Now',
																$this->ipsclass->form_code_js.'&code=stronghold',
																'stronghold' );
														
		}
		else
		{
			$content['good'] .= $this->html->security_item_good( 'Enable the Stronghold Cookie',
			 													 'IPB can store a stronghold cookie in the user\'s browser which is used when automatically logging in to prevent successful cookie theft.<br />Stronghold cookies are ENABLED.',
																 'Toggle Now',
																 $this->ipsclass->form_code_js.'&code=stronghold',
																 'stronghold' );
		}
		
		//-----------------------------------------
		// Remove ACP link
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['security_remove_acp_link'] )
		{
			$content['ok'] .= $this->html->security_item_ok( 'Remove ACP Link',
			 												 'IPB can remove the "Admin CP" link from the board\'s header. This is useful when renaming the default \'admin\' directory.<br />The ACP link is visible when logged in as an admin.',
															 'Toggle Now',
															 $this->ipsclass->form_code_js.'&code=acplink',
															 'acplink' );
														
		}
		else
		{
			$content['good'] .= $this->html->security_item_good( 'Remove ACP Link',
			 											 		'IPB can remove the "Admin CP" link from the board\'s header. This is useful when renaming the default \'admin\' directory.<br />The ACP link has been removed.',
																 'Toggle Now',
																 $this->ipsclass->form_code_js.'&code=acplink',
																 'acplink' );
		}
		
		//-----------------------------------------
		// ACP directory renamed
		//-----------------------------------------
		
		if ( IPB_ACP_DIRECTORY == 'admin' )
		{
			$content['ok'] .= $this->html->security_item_ok( 'Rename the \'admin\' directory',
			 												 'The default \'admin\' directory can be renamed to make it hard to find.<br />The admin directory has not been renamed.',
															 'Learn More',
															 $this->ipsclass->form_code_js.'&code=acprename',
															 'acprename' );
														
		}
		else
		{
			$content['good'] .= $this->html->security_item_good( 'Rename the \'admin\' directory',
			 													 'The default \'admin\' directory can be renamed to make it hard to find.<br />The admin directory HAS been renamed.',
																 'Learn More',
																 $this->ipsclass->form_code_js.'&code=acprename',
																 'acprename' );
		}
		
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
			
		$this->ipsclass->html .= $this->html->security_overview( $content );
		
		$this->ipsclass->admin->output();
	}
}


?>