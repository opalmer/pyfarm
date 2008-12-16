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
|   > $Date: 2007-05-04 18:07:07 -0400 (Fri, 04 May 2007) $
|   > $Revision: 976 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin functions library
|   > Script written by Matt Mecham
|   > Date started: 1st march 2002
|
|   > DBA Checked: Tue 25th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_functions
{
	# Global
	var $ipsclass;
	
	var $img_url;
	var $session_type	= NULL;
	var $html;
	var $errors 		= "";
	var $nav    		= array();
	var $time_offset 	= 0;
	var $jump_menu 		= "";
	var $no_jump 		= 0;
	var $master_skin 	= array();
	var $depth_guide 	= '--';
	var $menu_ids    	= array();
	
	var $page_title		= "";
	var $page_detail	= "";
	
	/*-------------------------------------------------------------------------*/
	// Get security auth key
	/*-------------------------------------------------------------------------*/
	/**
	* Returns the admin's security AUTH key
	*
	* @return string  md5 auth key
	*/
	function security_auth_get()
	{
		return md5( $this->ipsclass->member['email'] . '^' . $this->ipsclass->member['joined'] . '^' . $this->ipsclass->member['ip_address'] . md5( $this->ipsclass->vars['sql_pass'] ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Check security key
	/*-------------------------------------------------------------------------*/
	/**
	* Checks the security key
	*
	* @param string  md5 auth key
	* @param int     return and not die?
	*/
	function security_auth_check( $auth_key='', $return_and_not_die=0 )
	{
		$auth_key = ( $auth_key ) ? $auth_key : trim( $_POST['_admin_auth_key'] );
		
		if ( $auth_key != $this->security_auth_get() )
		{
			if ( $return_and_not_die )
			{
				return FALSE;
			}
			else
			{
				$this->error( "Security Mismatch - please go back and reload the form before attempting to submit the form / press the button again", 0 );
				exit();
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP PERMISSION CHECK
	/*-------------------------------------------------------------------------*/
	
	function cp_permission_check( $perm)
	{
		//----------------------------------
		// Got actual restrictions?
		//----------------------------------
		
		if ( ! $this->ipsclass->member['row_perm_cache'] )
		{
			return TRUE;
		}
		
		$this->ipsclass->member['_perm_cache'] = $this->ipsclass->unpack_member_cache( $this->ipsclass->member['row_perm_cache'] );
		
		//-------------------------------
		// Get lang: NEEDS SORTIN'
		//-------------------------------
		
		if ( ! is_array( $this->perm_lang ) OR ! count( $this->perm_lang ) )
		{
			require_once( ROOT_PATH."cache/lang_cache/en/acp_lang_acpperms.php" );
			$this->perm_lang = $lang;
		}
		
		//----------------------------------
		// Got restrictions in place...
		// 1) Check tab root and parent
		//----------------------------------
		
		list( $perm_main , $_data )    = explode( '|', $perm );
		list( $perm_child, $perm_bit ) = explode( ':', $_data );
		
		if ( ! $this->ipsclass->member['_perm_cache'][ $perm_main ] )
		{
			$this->ipsclass->kill_menu = 1;
			$this->ipsclass->admin->error("You do not have permission to access the tab: ".$this->perm_lang[ $perm_main ] );
			exit();
		}
	
		//----------------------------------
		// Got restrictions in place...
		// 2) Check feature parent
		//----------------------------------
		
		if ( $perm_child )
		{
			if ( ! $this->ipsclass->member['_perm_cache'][ $perm_main .':'. $perm_child ] )
			{
				if ( $this->perm_lang[ $perm_main .':'. $perm_child ] )
				{
					$this->ipsclass->admin->error("You do not have permission to access the feature: ".$this->perm_lang[ $perm_main .':'. $perm_child ]);
				}
				else
				{
					$this->ipsclass->admin->error("You do not have permission to access that feature");
				}
				
				exit();
			}
		}
		
		//----------------------------------
		// Got restrictions in place...
		// 3) Check feature parent
		//----------------------------------
		
		if ( $perm_bit )
		{
			if ( ! $this->ipsclass->member['_perm_cache'][ $perm_main .':'. $perm_child .':'. $perm_bit ] )
			{
				$this->ipsclass->admin->error("You do not have permission to access that feature function");
				exit();
			}
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP_SESSION
	/*-------------------------------------------------------------------------*/
	
	function acp_session_validation()
	{
		//----------------------------------
		// Got a cookie wookey?
		// Small security risk currently
		// Until we add in the auth key to forms
		//----------------------------------
		
		/*
		$cookie['admin_session_id'] = $this->ipsclass->my_getcookie('ipb_admin_session_id');
		
		if ( $cookie['admin_session_id'] )
		{
			$this->ipsclass->input['adsess'] = $cookie['admin_session_id'];
			$this->session_type              = 'cookie';
		}*/
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['adsess'] )
		{
			//----------------------------------
			// No URL adsess found, lets log in.
			//----------------------------------
			
			$this->ipsclass->admin_session['_session_validated'] = 0;
			$this->ipsclass->admin_session['_session_message']   = "No administration session found";
			return FALSE;
		}
		else
		{
			//----------------------------------
			// We have a URL adsess, lets verify...
			//----------------------------------
			
			$row = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'admin_sessions', 'where' => "session_id='".$this->ipsclass->input['adsess']."'" ) );
			
			if ( $row['session_id'] == "" )
			{
				//----------------------------------
				// Fail-safe, no DB record found, lets log in..
				//----------------------------------
				
				$this->ipsclass->admin_session['_session_validated'] = 0;
				$this->ipsclass->admin_session['_session_message']   = "Could not retrieve session record";
				return FALSE;
			}
			else if ($row['session_member_id'] == "")
			{
				//----------------------------------
				// No member ID is stored, log in!
				//----------------------------------
				
				$this->ipsclass->admin_session['_session_validated'] = 0;
				$this->ipsclass->admin_session['_session_message']   = "Could not retrieve a valid member id";
				return FALSE;
			}
			else
			{
				//----------------------------------
				// Key is good, check the member details
				//----------------------------------
			
				$this->ipsclass->DB->build_query( array( 'select'   => 'm.*',
														 'from'     => array( 'members' => 'm' ),
														 'where'    => 'm.id='.intval($row['session_member_id']),
														 'add_join' => array( 0 => array(
																						  'select' => 'g.*',
																						  'from'   => array( 'groups' => 'g' ),
																						  'where'  => 'g.g_id=m.mgroup',
																						  'type'   => 'left' ),
																			 1 => array(
																						  'select' => 'p.*',
																						  'from'   => array( 'admin_permission_rows' => 'p' ),
																						  'where'  => 'm.id=p.row_member_id',
																						  'type'   => 'left' )
																			)
												)     );
														 
				$this->ipsclass->DB->exec_query();
		
				$this->ipsclass->member                = $this->ipsclass->DB->fetch_row();
				
				if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
				{
					$this->ipsclass->member['_perm_cache'] = isset($this->ipsclass->member['row_perm_cache']) AND is_string($this->ipsclass->member['row_perm_cache']) ? unserialize( $this->ipsclass->member['row_perm_cache'] ) : '';
				}
				else
				{
					// If we are a root admin, shouldn't be restricted

					$this->ipsclass->member['row_perm_cache'] = null;
				}
				
				//----------------------------------
				// Get perms
				//----------------------------------
				
				$this->ipsclass->sess->member     = $this->ipsclass->member;
				$this->ipsclass->sess->build_group_permissions();
				$this->ipsclass->member = $this->ipsclass->sess->member;
				
				if ($this->ipsclass->member['id'] == "")
				{
					//----------------------------------
					// Ut-oh, no such member, log in!
					//----------------------------------
					
					$this->ipsclass->admin_session['_session_validated'] = 0;
					$this->ipsclass->admin_session['_session_message']   = "Member ID invalid";
					return FALSE;
				}
				else
				{
					//----------------------------------
					// Member found, check passy
					//----------------------------------
				
					if ( $row['session_member_login_key'] != md5( $this->ipsclass->member['joined'] . $this->ipsclass->member['ip_address'] ) )
					{
						//----------------------------------
						// Passys don't match..
						//----------------------------------
						
						$this->ipsclass->admin_session['_session_validated'] = 0;
						$this->ipsclass->admin_session['_session_message']   = "Session member password mismatch";
						return FALSE;
					}
					else
					{
						//----------------------------------
						// Do we have admin access?
						//----------------------------------
						
						if ($this->ipsclass->member['g_access_cp'] != 1)
						{
							$this->ipsclass->admin_session['_session_validated'] = 0;
							$this->ipsclass->admin_session['_session_message']   = "You do not have access to the administrative CP";
							return FALSE;
						}
						else
						{
							$this->ipsclass->admin_session = $row;
							$this->ipsclass->admin_session['_session_validated'] = 1;
						}
					}
				}
			}
		}
		
		//----------------------------------
		// If we're here, we're valid...
		//----------------------------------
		
		if ( $this->ipsclass->admin_session['_session_validated'] == 1 )
		{
			if ( $this->ipsclass->admin_session['session_running_time'] < ( time() - 60*60*2) )
			{
				$this->ipsclass->admin_session['_session_validated'] = 0;
				$this->ipsclass->admin_session['_session_message']   = "This administration session has expired";
			}
			
			//------------------------------
			// Are we checking IP's?
			//------------------------------
			
			else if ( IPB_ACP_IP_MATCH == 1)
			{
				$first_ip  = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", $this->ipsclass->admin_session['session_ip_address'] );
				$second_ip = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", $this->ipsclass->ip_address               );
				
				if ( $first_ip != $second_ip )
				{
					$this->ipsclass->admin_session['_session_validated'] = 0;
					$this->ipsclass->admin_session['_session_message']   = "Your current IP address does not match the one in our records";
					return FALSE;
				}
			}
		
			//------------------------------
			// Lets update the sessions table:
			//------------------------------
			
			$to_update = array( 'session_running_time' => time() );
			
			if( $this->ipsclass->input['act'] != 'xmlout' )
			{
				$to_update['session_location'] = $this->ipsclass->input['section'] . ',' . $this->ipsclass->input['act'];
			}
			
			$this->ipsclass->DB->do_update( 'admin_sessions', $to_update,
											  'session_member_id='.intval($this->ipsclass->member['id'])." and session_id='".$this->ipsclass->input['adsess']."'" );
											  
			return TRUE;
		}

	}
	
	/*-------------------------------------------------------------------------*/
	// Get mysql version
	/*-------------------------------------------------------------------------*/
	
	function get_mysql_version()
	{
		$this->ipsclass->DB->sql_get_version();
		
		$this->ipsclass->true_version  = $this->ipsclass->DB->true_version;
		$this->ipsclass->mysql_version = $this->ipsclass->DB->mysql_version;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get mysql version
	/*-------------------------------------------------------------------------*/
	
	function get_fulltextindex_status()
	{
		if ( $this->ipsclass->DB->sql_is_currently_fulltext( 'posts' ) == TRUE )
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// make template / text data safe for forms
	/*-------------------------------------------------------------------------*/
	
	function text_to_form($t="")
	{
		// Use forward look up to only convert & not &#123;
		//$t = preg_replace("/&(?!#[0-9]+;)/s", '&#38;', $t );
		
		$t = str_replace("&", "&#38;"    , $t );
		$t = str_replace( "<" , "&#60;"  , $t );
		$t = str_replace( ">" , "&#62;"  , $t );
		$t = str_replace( '"' , "&#34;"  , $t );
		$t = str_replace( "'" , '&#039;' , $t );
		$t = str_replace( "\\", "&#092;" , $t );
		
		return $t; // A nice cup of?
	}
	
	/*-------------------------------------------------------------------------*/
	// Converts form data back into raw text
	/*-------------------------------------------------------------------------*/
	
	function form_to_text($t="")
	{
		$t = str_replace( '\\'  , '\\\\', $t );
		$t = str_replace( "&#38;"  , "&", $t );
		$t = str_replace( "&#60;"  , "<", $t );
		$t = str_replace( "&#62;"  , ">", $t );
		$t = str_replace( "&#34;"  , '"', $t );
		$t = str_replace( "&#039;" , "'", $t );
		$t = str_replace( '&#092;' ,'\\', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate skin list
	/*-------------------------------------------------------------------------*/
	
	function skin_get_skin_dropdown()
	{
		$skin_array  = array();
		
		foreach( $this->ipsclass->cache['skin_id_cache'] as $id => $data )
		{
			if ( $data['set_parent'] < 1 and $id > 1 )
			{
				 $data['set_parent'] = 'root';
			}
			
			$this->master_skin[ $data['set_parent'] ][ $id ] = $data;
		}
		
		foreach( $this->master_skin['root'] as $id => $data )
		{
			$skin_array[] = array( $id, $data['set_name'] );
			
			if ( isset($this->master_skin[ $id ]) AND is_array( $this->master_skin[ $id ] ) )
			{
				foreach( $this->master_skin[ $id ] as $id => $data )
				{
					$skin_array[] = array( $id, $this->depth_guide.$data['set_name'] );
					
					$skin_array = $this->skin_get_skin_dropdown_recurse( $id, $skin_array, $this->depth_guide.$this->depth_guide );
				}
			}
		}
		
		return $skin_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// Recurse
	/*-------------------------------------------------------------------------*/
	
	function skin_get_skin_dropdown_recurse( $root_id, $skin_array=array(), $depth_guide='' )
	{
		if ( is_array( $this->master_skin[ $root_id ] ) )
		{
			foreach( $this->master_skin[ $root_id ] as $id => $data )
			{
				$skin_array[] = array( $id, $this->depth_guide.$data['set_name'] );
				
				$skin_array = $this->skin_get_skin_dropdown_recurse( $id, $skin_array, $this->depth_guide.$this->depth_guide );
			}
		}
		
		return $skin_array;
	}
		
		
	/*-------------------------------------------------------------------------*/
	// IMPORT FUNCTION
	/*-------------------------------------------------------------------------*/
	
	function import_xml( $infilename, $postfield='FILE_UPLOAD' )
	{
		//-----------------------------------------
		// Allowed file-types
		//-----------------------------------------
		
		$allowed_files = array( 'xml', 'gz' );
		
		//-----------------------------------------
		// Upload
		//-----------------------------------------
		
		$FILE_NAME = $_FILES[ $postfield ]['name'];
		$FILE_SIZE = $_FILES[ $postfield ]['size'];
		$FILE_TYPE = $_FILES[ $postfield ]['type'];
		
		//-----------------------------------------
		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.
		//-----------------------------------------
		
		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
		
		$content = "";		
		
		//-----------------------------------------					
		// Naughty Mozilla likes to use "none" to indicate an empty upload field.
		// I love universal languages that aren't universal.
		//-----------------------------------------
		
		if ( $_FILES[ $postfield ]['name'] == "" or ! $_FILES[ $postfield ]['name'] or ($_FILES[ $postfield ]['name'] == "none") )
		{
			return $content;
		}
		
		//-----------------------------------------
		// Not a naughty file?
		//-----------------------------------------
		
		$file_extension = preg_replace( "#^.*\.(.+?)$#si", "\\1", strtolower( $_FILES[ $postfield ]['name'] ) );
		
		if ( ! in_array( $file_extension, $allowed_files ) )
		{
			@unlink( $_FILES[ $postfield ]['tmp_name'] );
			return '';
		}
		
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		if ( strstr( $FILE_NAME, $infilename ) )
		{
			if ( move_uploaded_file( $_FILES[ $postfield ]['tmp_name'], $this->ipsclass->vars['upload_dir']."/".$FILE_NAME) )
			{
				if ( $FILE_NAME == $infilename.'.gz' )
				{
					if ( $FH = @gzopen( $this->ipsclass->vars['upload_dir']."/".$FILE_NAME, 'rb' ) )
					{
					 	while ( ! @gzeof( $FH ) )
					 	{
					 		$content .= @gzread( $FH, 1024 );
					 	}
					 	
						@gzclose( $FH );
					}
				}
				else if ( $FILE_NAME == $infilename )
				{
					if ( $FH = @fopen( $this->ipsclass->vars['upload_dir']."/".$FILE_NAME, 'rb' ) )
					{
						$content = @fread( $FH, filesize($this->ipsclass->vars['upload_dir']."/".$FILE_NAME) );
						@fclose( $FH );
					}
				}
				
				@unlink( $this->ipsclass->vars['upload_dir']."/".$FILE_NAME );
			}
		}
		
		return $content;
	}
	
	/*-------------------------------------------------------------------------*/
	// Shows dialogue download box
	/*-------------------------------------------------------------------------*/
	
	function show_download( $data, $name, $type="unknown/unknown", $compress=1 )
	{
		if ( $compress and @function_exists('gzencode') )
		{
			$name .= '.gz';
		}
		else
		{
			$compress = 0;
		}
		
		header('Content-Type: '.$type);
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $name . '"');
		
		if ( ! $compress )
		{
			@header('Content-Length: ' . strlen($data) );
		}
		
		@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		@header('Pragma: public');
		
		if ( $compress )
		{
			print gzencode($data);
		}
		else
		{
			print $data;
		}
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Makes good raw form text
	/*-------------------------------------------------------------------------*/
	
	function make_safe($t)
	{
		$t = stripslashes($t);
		
		$t = preg_replace( "/\\\/", "&#092;", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Sets up time offset for ACP use
	/*-------------------------------------------------------------------------*/
	
	
	function get_date($date="", $method="")
	{
		$this->time_options = array( 'JOINED' => $this->ipsclass->vars['clock_joined'],
									 'SHORT'  => $this->ipsclass->vars['clock_short'],
									 'LONG'   => $this->ipsclass->vars['clock_long']
								   );
								   
		if (!$date)
        {
            return '--';
        }
        
        if (empty($method))
        {
        	$method = 'LONG';
        }
        
        $this->time_offset = (($this->ipsclass->member['time_offset'] != "") ? $this->ipsclass->member['time_offset'] : $this->ipsclass->vars['time_offset']) * 3600;
			
		if ($this->ipsclass->vars['time_adjust'] != "" and $this->ipsclass->vars['time_adjust'] != 0)
		{
			$this->time_offset += ($this->ipsclass->vars['time_adjust'] * 60);
		}
		
		if ($this->ipsclass->member['dst_in_use'])
		{
			$this->time_offset += 3600;
		}
        
        return gmdate($this->time_options[$method], ($date + $this->time_offset) );
	}
	
	/*-------------------------------------------------------------------------*/
	// save_log
	/*-------------------------------------------------------------------------*/
	
	function save_log($action="")
	{
		$this->ipsclass->DB->do_insert( 'admin_logs', array(
										'act'        => $this->ipsclass->input['act'],
										'code'       => $this->ipsclass->input['code'],
										'member_id'  => $this->ipsclass->member['id'],
										'ctime'      => time(),
										'note'       => $action,
										'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
							  )       );
		
		return true;
		
	}
	
	
	/*-------------------------------------------------------------------------*/
	// get_tar_names
	/*-------------------------------------------------------------------------*/
	
	function get_tar_names($start='lang-')
	{
		$files = array();
		
		$dir = $this->ipsclass->vars['base_dir']."archive_in";
			
		if ( is_dir($dir) )
		{
			$handle = opendir($dir);
			
			while (($filename = readdir($handle)) !== false)
			{
				if (($filename != ".") && ($filename != ".."))
				{
					if (preg_match("/^$start.+?\.tar$/", $filename))
					{
						$files[] = $filename;
					}
				}
			}
			
			closedir($handle);
			
		}
		
		return $files;
	}
	
	/*-------------------------------------------------------------------------*/
	// copy_dir
	/*-------------------------------------------------------------------------*/
	
	function copy_dir($from_path, $to_path, $mode = 0777)
	{
		// Strip off trailing slashes...
		
		$from_path = preg_replace( "#/$#", "", $from_path);
		$to_path   = preg_replace( "#/$#", "", $to_path);
	
		if ( ! is_dir($from_path) )
		{
			$this->errors = "Could not locate directory '$from_path'";
			return FALSE;
		}
	
		if ( ! is_dir($to_path) )
		{
			if ( ! @mkdir($to_path, $mode) )
			{
				$this->errors = "Could not create directory '$to_path' please check the CHMOD permissions and re-try";
				return FALSE;
			}
			else
			{
				@chmod($to_path, $mode);
			}
		}
		
		//$this_path = getcwd();
		
		if (is_dir($from_path))
		{
			//chdir($from_path);
			
			$handle = opendir($from_path);
			
			while (($file = readdir($handle)) !== false)
			{
				if (($file != ".") && ($file != ".."))
				{
					if ( is_dir( $from_path."/".$file ) )
					{
						$this->copy_dir($from_path."/".$file, $to_path."/".$file);
						//chdir($from_path);
					}
					
					if ( is_file( $from_path."/".$file ) )
					{
						copy($from_path."/".$file, $to_path."/".$file);
						@chmod($to_path."/".$file, 0777);
					} 
				}
			}
			closedir($handle); 
		}
		
		if ($this->errors == "")
		{
			return TRUE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// rm_dir
	/*-------------------------------------------------------------------------*/
	
	function rm_dir($file)
	{
		$errors = 0;
		
		// Remove trailing slashes..
		
		$file = preg_replace( "#/$#", "", $file );
		
		if ( file_exists($file) )
		{
			// Attempt CHMOD
			
			@chmod($file, 0777);
			
			if ( is_dir($file) )
			{
				$handle = opendir($file);
				
				while (($filename = readdir($handle)) !== false)
				{
					if (($filename != ".") && ($filename != ".."))
					{
						$this->rm_dir($file."/".$filename);
					}
				}
				
				closedir($handle);
				
				if ( ! @rmdir($file) )
				{
					$errors++;
				}
			}
			else
			{
				if ( ! @unlink($file) )
				{
					$errors++;
				}
			}
		}
		
		if ($errors == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// rebuild_config:
	/*-------------------------------------------------------------------------*/
	
	function rebuild_config( $new = "" )
	{
		//-----------------------------------------
		// Check to make sure this is a valid array
		//-----------------------------------------
		
		if (! is_array($new) )
		{
			$this->ipsclass->admin->error("Error whilst attempting to rebuild the board config file, attempt aborted");
		}
		
		//-----------------------------------------
		// Do we have anything to save out?
		//-----------------------------------------
		
		if ( count($new) < 1 )
		{
			return "";
		}
		
		//-----------------------------------------
		// Get an up to date copy of the config file
		// (Imports $INFO)
		//-----------------------------------------
		
		require ROOT_PATH.'conf_global.php';
		
		//-----------------------------------------
		// Rebuild the $INFO hash
		//-----------------------------------------
		
		foreach( $new as $k => $v )
		{
			// Update the old...
			
			$v = preg_replace( "/'/", "\\'" , $v );
			$v = preg_replace( "/\r/", ""   , $v );
			
			$INFO[ $k ] = $v;
		}	
		
		//-----------------------------------------
		// Rename the old config file
		//-----------------------------------------
		
		@rename( ROOT_PATH.'conf_global.php', ROOT_PATH.'conf_global-bak.php' );
		@chmod( ROOT_PATH.'conf_global-bak.php', 0777);
		
		//-----------------------------------------
		// Rebuild the old file
		//-----------------------------------------
		
		ksort($INFO);
		
		$file_string = "<?php\n";
		
		foreach( $INFO as $k => $v )
		{
			if ($k == 'skin' or $k == 'languages')
			{
				// Protect serailized arrays..
				$v = stripslashes($v);
				$v = addslashes($v);
			}
			
			$file_string .= '$INFO['."'".$k."'".']'."\t\t\t=\t'".$v."';\n";
		}
		
		$file_string .= "\n".'?'.'>';   // Question mark + greater than together break syntax hi-lighting in BBEdit 6 :p
		
		if ( $fh = fopen( ROOT_PATH.'conf_global.php', 'w' ) )
		{
			fwrite($fh, $file_string, strlen($file_string) );
			fclose($fh);
		}
		else
		{
			$this->ipsclass->admin->error("Fatal Error: Could not open conf_global for writing - no changes applied. Try changing the CHMOD to 0777");
		}
		
		// Pass back the new $INFO array to anyone who cares...
		
		return $this->ipsclass->vars;
		
	}
	
	/*-------------------------------------------------------------------------*/
	// compile_forum_perms:
	//
	// Returns the READ/REPLY/START DB strings
	//
	/*-------------------------------------------------------------------------*/
	
	function compile_forum_perms()
	{
		$r_array = array( 'READ' => '', 'REPLY' => '', 'START' => '', 'UPLOAD' => '', 'DOWNLOAD' => '', 'SHOW' => '' );
		
		if (isset($this->ipsclass->input['READ_ALL']) AND $this->ipsclass->input['READ_ALL'] == 1)
		{
			$r_array['READ'] = '*';
		}
		
		if (isset($this->ipsclass->input['REPLY_ALL']) AND $this->ipsclass->input['REPLY_ALL'] == 1)
		{
			$r_array['REPLY'] = '*';
		}
		
		if (isset($this->ipsclass->input['START_ALL']) AND $this->ipsclass->input['START_ALL'] == 1)
		{
			$r_array['START'] = '*';
		}
		
		if (isset($this->ipsclass->input['UPLOAD_ALL']) AND $this->ipsclass->input['UPLOAD_ALL'] == 1)
		{
			$r_array['UPLOAD'] = '*';
		}
		
		if (isset($this->ipsclass->input['DOWNLOAD_ALL']) AND $this->ipsclass->input['DOWNLOAD_ALL'] == 1)
		{
			$r_array['DOWNLOAD'] = '*';
		}		
		
		if (isset($this->ipsclass->input['SHOW_ALL']) AND $this->ipsclass->input['SHOW_ALL'] == 1)
		{
			$r_array['SHOW'] = '*';
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'perm_id, perm_name', 'from' => 'forum_perms', 'order' => "perm_id" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $data = $this->ipsclass->DB->fetch_row() )
		{
			if ($r_array['SHOW'] != '*')
			{
				if (isset($this->ipsclass->input[ 'SHOW_'.$data['perm_id'] ]) AND $this->ipsclass->input[ 'SHOW_'.$data['perm_id'] ] == 1)
				{
					$r_array['SHOW'] .= $data['perm_id'].",";
				}
			}
			//-----------------------------------------
			if ($r_array['READ'] != '*')
			{
				if (isset($this->ipsclass->input[ 'READ_'.$data['perm_id'] ]) AND $this->ipsclass->input[ 'READ_'.$data['perm_id'] ] == 1)
				{
					$r_array['READ'] .= $data['perm_id'].",";
				}
			}
			//-----------------------------------------
			if ($r_array['REPLY'] != '*')
			{
				if (isset($this->ipsclass->input[ 'REPLY_'.$data['perm_id'] ]) AND $this->ipsclass->input[ 'REPLY_'.$data['perm_id'] ] == 1)
				{
					$r_array['REPLY'] .= $data['perm_id'].",";
				}
			}
			//-----------------------------------------
			if ($r_array['START'] != '*')
			{
				if (isset($this->ipsclass->input[ 'START_'.$data['perm_id'] ]) AND $this->ipsclass->input[ 'START_'.$data['perm_id'] ] == 1)
				{
					$r_array['START'] .= $data['perm_id'].",";
				}
			}
			//-----------------------------------------
			if ($r_array['UPLOAD'] != '*')
			{
				if (isset($this->ipsclass->input[ 'UPLOAD_'.$data['perm_id'] ]) AND $this->ipsclass->input[ 'UPLOAD_'.$data['perm_id'] ] == 1)
				{
					$r_array['UPLOAD'] .= $data['perm_id'].",";
				}
			}
			//-----------------------------------------
			if ($r_array['DOWNLOAD'] != '*')
			{
				if (isset($this->ipsclass->input[ 'DOWNLOAD_'.$data['perm_id'] ]) AND $this->ipsclass->input[ 'DOWNLOAD_'.$data['perm_id'] ] == 1)
				{
					$r_array['DOWNLOAD'] .= $data['perm_id'].",";
				}
			}			
		}
		
		$r_array['START']   	= preg_replace( "/,$/", "", $r_array['START']    );
		$r_array['REPLY']   	= preg_replace( "/,$/", "", $r_array['REPLY']    );
		$r_array['READ']    	= preg_replace( "/,$/", "", $r_array['READ']     );
		$r_array['UPLOAD']  	= preg_replace( "/,$/", "", $r_array['UPLOAD']   );
		$r_array['DOWNLOAD']	= preg_replace( "/,$/", "", $r_array['DOWNLOAD'] );
		$r_array['SHOW']    	= preg_replace( "/,$/", "", $r_array['SHOW']     );
		
		return $r_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// OUTPUT FUNCTIONS
	/*-------------------------------------------------------------------------*/
	
	function print_popup()
	{
		//--------------------------------------
		// Message in a bottle?
		//--------------------------------------
		
		$html = $this->ipsclass->main_msg ? $this->ipsclass->skin_acp_global->global_message() : '';
		
		$html .= $this->ipsclass->skin_acp_global->global_popup( $this->ipsclass->html );
		
		if ( IPB_ACP_USE_GZIP == 1 )
		{
        	$buffer = ob_get_contents();
        	ob_end_clean();
        	@ob_start('ob_gzhandler');
        	print $buffer;
    	}

    	@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		@header("Cache-Control: no-cache, must-revalidate");
		@header("Pragma: no-cache");
		@header("Content-Type: text/html; charset={$this->ipsclass->vars['gb_char_set']}");		
		
		print $html;
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Multi-redirect
	/*-------------------------------------------------------------------------*/
	
	function output_multiple_redirect_init( $url, $text='', $addtotext=1 )
	{
		//$this->nav[] = array( $url, 'Redirecting...' );
		
		if ( $this->ipsclass->can_use_fancy_js )
		{
			$this->ipsclass->html .= "<script type='text/javascript'>ajax_refresh( '$url', '$text', $addtotext );</script>\n<div style='height:300px;overflow:auto;font-weight:bold;line-height:140%;padding:5px;border:1px solid #000' id='refreshbox'>Initializing...</div>";
		}
		else
		{
			$this->ipsclass->html .= "<iframe src='$url' scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='300'></iframe>";
		}
		
		$this->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Multi-redirect (hit)
	/*-------------------------------------------------------------------------*/
	
	function output_multiple_redirect_hit( $url, $text='', $addtotext=1 )
	{
		if ( $this->ipsclass->can_use_fancy_js )
		{
			print "ajax_refresh( '$url', '$text', $addtotext );";
			exit();
		}
		else
		{
			print $this->ipsclass->skin_acp_global->global_redirect_hit( $url, $text );
			exit();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Multi-redirect (hit)
	/*-------------------------------------------------------------------------*/
	
	function output_multiple_redirect_done($text='Completed!')
	{
		if ( $this->ipsclass->can_use_fancy_js )
		{
			$text = str_replace( "'", "\\'", $text );
			
			print "document.getElementById('refreshbox').innerHTML = '<span style=\"color:red\">$text</span>'  + '<br />' + document.getElementById('refreshbox').innerHTML;";
			exit();
		}
		else
		{
			print $this->ipsclass->skin_acp_global->global_redirect_done( $text );
			exit();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// OUTPUT
	/*-------------------------------------------------------------------------*/
	
	function output()
	{
		$html 		= "";
		$navigation = array();
		$message	= "";
		$help		= "";
		$member_bar	= "";
		$query_html = "";
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------

		if ($this->ipsclass->DB->obj['debug'])
        {
        	flush();
        	print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print "<h1 align='center'>SQL Total Time: {$this->ipsclass->DB->sql_time} for {$this->ipsclass->DB->query_cnt} queries</h1><br />".$this->ipsclass->DB->debug_html;
        	print "<br /><div align='center'><strong>Total SQL Time: {$this->ipsclass->DB->sql_time}</div></body></html>";
        	exit();
        }
        
		//-----------------------------------------
		// Start function proper
		//-----------------------------------------
	
		$html = str_replace( '<%CONTENT%>', $this->ipsclass->skin_acp_global->global_frame_wrapper(), $this->ipsclass->skin_acp_global->global_main_wrapper() );
		
		$navigation = array( "<a href='{$this->ipsclass->base_url}&act=index'>ACP Home</a>" );
		
		if ( count($this->nav) > 0 )
		{
			foreach ( $this->nav as $links )
			{
				if ($links[0] != "")
				{
					$navigation[] = "<a href='{$this->ipsclass->base_url}&{$links[0]}'>{$links[1]}</a>";
				}
				else
				{
					$navigation[] = $links[1];
				}
			}
		}
		
		//--------------------------------------
		// Navigation?
		//--------------------------------------
		
		if ( count($navigation) > 0 )
		{
			$html = str_replace( "<%NAV%>", $this->ipsclass->skin_acp_global->global_wrap_nav( implode( " &gt; ", $navigation ) ), $html );
		}
		
		//-----------------------------------------
		// Member bar..
		//-----------------------------------------
		
		$member_bar = $this->ipsclass->skin_acp_global->global_memberbar();
		
		$html       = str_replace( "<%MEMBERBAR%>", $member_bar, $html );
		
		//--------------------------------------
		// Message in a bottle?
		//--------------------------------------
		
		$message = $this->ipsclass->main_msg ? $this->ipsclass->skin_acp_global->global_message() : '';
		
		//--------------------------------------
		// Help?
		//--------------------------------------
		
		$this->ipsclass->html_help_msg   = $this->ipsclass->html_help_msg   ? $this->ipsclass->html_help_msg   : $this->page_detail;
		$this->ipsclass->html_help_title = $this->ipsclass->html_help_title ? $this->ipsclass->html_help_title : $this->page_title;
		
		$help = '';
		
		/*if ( $this->ipsclass->html_help_title AND $this->ipsclass->html_help_msg )
		{
			$help = $this->ipsclass->skin_acp_global->information_box( $this->ipsclass->html_help_title, $this->ipsclass->html_help_msg.'<br />&nbsp;' ) . "<br >";
		}*/
		
		if( $this->ipsclass->vars['acp_tutorial_mode'] )
		{
			//--------------------------------------
			// More Help? - *sigh* Keith
			//--------------------------------------
			
			if( $this->ipsclass->my_group_helpkey )
			{
				$check_key = "settinggroup_" . $this->ipsclass->my_group_helpkey;
			}
			else
			{
				$check_key = $this->ipsclass->input['section'] . '_' . $this->ipsclass->input['act'] . '_' . $this->ipsclass->input['code'];
			}
			
			$my_help = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'acp_help', 'where' => "page_key='{$check_key}' AND is_setting=0" ) );
			
			if( $my_help['page_key'] )
			{
				$my_help['help_title'] = str_replace( '{base_url}', $this->ipsclass->base_url, $my_help['help_title'] );
				$my_help['help_body'] = str_replace( '{base_url}', $this->ipsclass->base_url, $my_help['help_body'] );
				
				$my_help['help_title'] = str_replace( '{skin_url}', $this->ipsclass->skin_acp_url, $my_help['help_title'] );
				$my_help['help_body'] = str_replace( '{skin_url}', $this->ipsclass->skin_acp_url, $my_help['help_body'] );
				
				$collapsed_ids = ','.$this->ipsclass->my_getcookie('collapseprefs').',';
			
				$show['div_fo']		= '';
				$show['div_fc'] 	= 'none';
				$show['div_key']	= $check_key;
					
				if ( strstr( $collapsed_ids, ',' . $check_key . ',' ) )
				{
					$show['div_fo'] = 'none';
					$show['div_fc'] = '';
				}				

				$help .= $this->ipsclass->skin_acp_global->help_box( $show, $my_help['help_title'], $my_help['help_body'].'<br />&nbsp;' ) . "<br >";
			}
		}
		
		$this->ipsclass->DB->close_db();
		
		//-----------------------------------------
		// Quick Jump?
		//-----------------------------------------
		
		if ( $this->no_jump != 1 )
		{
			$html = str_replace( "<!--JUMP-->", $this->build_jump_menu(), $html );
		}
		
		//-----------------------------------------
		// Kill menu?
		// For when we don't want to show the menu
		// during a tab error from cp_permission_check
		//-----------------------------------------
		
		if ( $this->ipsclass->kill_menu )
		{
			$html .= "<script type='text/javascript'>\ntry {\ndocument.getElementById('leftblock').style.display = 'none';\n}\ncatch(e){}\n</script>";
		}
		
    	//-----------------------------------------
		// Show queries
		//-----------------------------------------
		
		if ( IN_DEV and count( $this->ipsclass->DB->obj['cached_queries']) )
		{
			$queries = "";
			
			foreach( $this->ipsclass->DB->obj['cached_queries'] as $q )
			{
				if ( strlen($q) > 300 )
				{
					$q = substr( $q, 0, 300 ).'...';
				}
				
				$queries .= htmlspecialchars($q).'<hr />';
			}
			
			$query_html .= $this->ipsclass->skin_acp_global->global_query_output($queries);
		}
		
		global $Debug;
		
		$query_html .= "<div align='center'><br />Time: ".$Debug->endTimer()."</div>";
		
		//-----------------------------------------
		// Other tags...
		//-----------------------------------------
		
		$html = str_replace( "<%TITLE%>"         , $this->ipsclass->html_title, $html );
		$html = str_replace( "<%MENU%>"          , $this->build_menu()  , $html );
		$html = str_replace( "<%TABS%>"          , $this->build_tabs()  , $html );
		$html = str_replace( "<%SECTIONCONTENT%>", $this->ipsclass->html, $html );
		$html = str_replace( "<%MSG%>"           , $message             , $html );
		$html = str_replace( "<%HELP%>"          , $help                , $html );
		$html = str_replace( "<%QUERIES%>"       , $query_html          , $html );
		
		//-----------------------------------------
		// Got BODY EXTRA?
		//-----------------------------------------
		
		if ( $this->ipsclass->body_extra )
		{
			$html = str_replace( "<body", "<body ".$this->ipsclass->body_extra, $html );
		}
		
		$html = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $html );
		$html = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $html );
		
		//-----------------------------------------
		// Gzip?
		//-----------------------------------------
		
		if ( IPB_ACP_USE_GZIP )
		{
			$buffer = "";
			
	        if( count( ob_list_handlers() ) )
	        {			
        		$buffer = ob_get_contents();
        		ob_end_clean();
    		}
    		
        	@ob_start('ob_gzhandler');
        	print $buffer;
    	}
    	
    	@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		@header("Cache-Control: no-cache, must-revalidate");
		@header("Pragma: no-cache");
		
    	print $html;
    	
    	exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Build menu tree
	/*-------------------------------------------------------------------------*/
	
	function build_tabs()
	{
		$onoff['content']     = 'taboff-main';
		$onoff['lookandfeel'] = 'taboff-main';
		$onoff['tools']       = 'taboff-main';
		$onoff['components']  = 'taboff-main';
		$onoff['admin']       = 'taboff-main';
		$onoff['help']        = 'taboff-main';
		$onoff['dashboard']   = 'taboff-main';
		
		$onoff[ $this->ipsclass->menu_type ] = 'tabon-main';
		
		return $this->ipsclass->skin_acp_global->global_tabs( $onoff );
	}
	
	/*-------------------------------------------------------------------------*/
	// BUILD MENU
	/*-------------------------------------------------------------------------*/
	
	function build_menu()
	{
		//--------------------------------
		// Catch log in pages, etc
		//--------------------------------
		
		if ( ! $this->ipsclass->menu_type OR $this->ipsclass->menu_type == 'dashboard' )
		{
			return;
		}
		
		//--------------------------------
		// Import $PAGES and $CATS
		//--------------------------------
		 
		require_once( ROOT_PATH."sources/acp_loaders/acp_pages_".$this->ipsclass->menu_type.".php" );
		
		
		$this->pages = isset($PAGES) 	? $PAGES : array();
		$this->cats  = isset($CATS) 	? $CATS	 : array();
		$this->desc  = isset($DESC)		? $DESC	 : array();
		
		
		$html = $this->build_tree();
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Show in frame
	/*-------------------------------------------------------------------------*/
	
	function show_inframe($url="", $html="")
	{
		if ( $url )
		{
			$this->ipsclass->html .= "<iframe src='$url' scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='500'></iframe>";
		}
		else
		{
			$this->ipsclass->html .= "<iframe scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='500'>{$html}</iframe>";
		}
		
		$this->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Redirect:
	/*-------------------------------------------------------------------------*/
	
	function redirect($url, $text, $is_popup=0, $time=2)
	{
		//--------------------------------
		// Got board URL in url?
		//--------------------------------
		
		if( !$url )
		{
			$url = '&';
		}
		
		if ( ! strstr( $url, $this->ipsclass->vars['board_url'] ) )
		{
			$url = $this->ipsclass->base_url.'&'.$url;
		}
		
		if ( $this->ipsclass->main_msg )
		{
			if( strlen($this->ipsclass->main_msg) > 1500 )
			{
				$this->ipsclass->main_msg = substr( $this->ipsclass->main_msg, 0, 1500 );
			}
			
			$url .= '&messageinabottleacp='.urlencode( $this->ipsclass->main_msg );
		}
		
		$this->ipsclass->main_msg   = "";
		$this->ipsclass->html_title = "IPB: ACP: Redirecting...";
	
		$html = $this->ipsclass->skin_acp_global->global_redirect( $url, $time, $text );
		
		$this->ipsclass->html = str_replace( '<%CONTENT%>', $html, $this->ipsclass->skin_acp_global->global_main_wrapper() );
		$this->ipsclass->html = str_replace( '<%TITLE%>'  , $this->ipsclass->html_title, $this->ipsclass->html );
		$this->ipsclass->html = str_replace( "<body", "<body style='background-image:url({$this->ipsclass->skin_acp_url}/images/blank.gif)'", $this->ipsclass->html );
		
		@header("Content-type: text/html; charset={$this->ipsclass->vars['gb_char_set']}");
		print $this->ipsclass->html;
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// No waiting redirect
	/*-------------------------------------------------------------------------*/
	
	function redirect_noscreen($url)
	{
		$extra = "";
		
		if ( $this->ipsclass->main_msg )
		{
			$extra = '&messageinabottleacp='.urlencode( $this->ipsclass->main_msg );
		}
		
		$url = str_replace( "&amp;", "&", $url ) . $extra;
		
		if ($this->ipsclass->vars['header_redirect'] == 'refresh')
		{
			@header("Refresh: 0;url=".$url);
		}
		else if ($this->ipsclass->vars['header_redirect'] == 'html')
		{
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			@header("Location: ".$url);
		}
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Error:
	/*-------------------------------------------------------------------------*/
	
	function error($error="", $is_popup=0)
	{
		$this->ipsclass->html .= $this->ipsclass->skin_acp_global->warning_box( "Admin CP Message", $error . '<br /><br />' );
		//$this->page_title  = "Admin CP Message";
		//$this->page_detail = $error;//"&nbsp;";
		
		/*$this->ipsclass->html .= "<div class='tableborder'>
								  <div class='tableheaderalt'>Admin CP Message</div>
								  <div class='tablerow1' style='padding:8px'>
								   <span style='font-size:12px'>$error</span>
								  </div>
								 </div>";*/
		
		if ( $is_popup == 0 )
		{
			$this->output();
		}
		else
		{
			$this->print_popup();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Done Screen:
	/*-------------------------------------------------------------------------*/
	
	function done_screen($title, $link_text="", $link_url="", $redirect="")
	{
		if ( $redirect )
		{
			$this->redirect( $this->ipsclass->base_url.'&'.$link_url, "<b>$title</b><br />Redirecting to: ".$link_text );
		}
		
		$this->page_title  = $title;
		$this->page_detail = "The action was executed successfully";
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table("Result");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "<a href='{$this->ipsclass->base_url}&{$link_url}'>Go to: $link_text</a>", "center" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "<a href='{$this->ipsclass->base_url}&act=index'>Go to: Administration Home</a>", "center" );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
			
		$this->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// INFO screen
	/*-------------------------------------------------------------------------*/
	
	function info_screen($text="", $title='Safe Mode Restriction Warning')
	{
		$this->page_title  = $title;
		$this->page_detail = "Please note the following:";
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table("Result");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( $text );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "<a href='{$this->ipsclass->base_url}&act=index'>Go to: Administration Home</a>", "center" );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
			
		$this->output();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Build menu tree
	/*-------------------------------------------------------------------------*/
	
	function build_tree()
	{
		//----------------------------------
		// INIT
		//----------------------------------
		
		$html  = "";

		//----------------------------------
		// Known menu stuff
		//----------------------------------
		
		#					  Section Module CODE =>  Real Perm Key
		$menu_limits = array( 'content:mem:add'   => 'content:mem:add',
							  'content:mem:title' => 'content:mem:title-view',
					 		);
		
		foreach($this->cats as $cid => $data)
		{
			$links = "";
			
			$name  = isset($data[0]) ? $data[0] : NULL;
			$color = isset($data[1]) ? $data[1] : NULL;
			$extra = isset($data[2]) ? $data[2] : NULL;
			
			$this->menu_ids[] = $cid;
		
			$this->ipsclass->admin->jump_menu .= "<optgroup label='$name'>\n";
			
			foreach($this->pages[ $cid ] as $pid => $pdata)
			{
				if ( isset($pdata[2]) AND $pdata[2] != "" )
				{
					if ( ! @is_dir( ROOT_PATH.$pdata[2] ) )
					{
						continue;
					}
				}
				
				if ( isset($pdata[4]) AND $pdata[4] )
				{
					$icon      = "<img src='{$this->ipsclass->skin_acp_url}/images/menu_shortcut.gif' border='0' alt='' valign='absmiddle'>";
					$extra_css = ';font-style:italic';
				}
				else
				{
					$icon      = "<img src='{$this->ipsclass->skin_acp_url}/images/item_bullet.gif' border='0' alt='' valign='absmiddle'>";
					$extra_css = "";
				}
				
				if ( isset($pdata[3]) AND $pdata[3] == 1 )
				{
					$theurl = $this->ipsclass->vars['board_url'].'/index.'.$this->ipsclass->vars['php_ext'].'?';
				}
				else
				{
					$theurl = $this->ipsclass->base_url.'&';
				}
				
				if( isset($pdata[5]) AND $pdata[5] == 1 )
				{
					$theurl = "";
					$extra_css = "' target='_blank";
				}
				
				//----------------------------------
				// Got actual restrictions?
				//----------------------------------
				
				$no_access = 0;
				
				if ( $this->ipsclass->member['row_perm_cache'] )
				{
					//-------------------------------
					// Yup.. so extract link info
					//-------------------------------
					
					$_tmp       = str_replace( '&amp;', '&', $pdata[1] );
					$perm_child = "";
					$perm_main  = "";
					$perm_bit   = "";
					
					foreach( explode( '&', $_tmp ) as $_urlbit )
					{
						list( $k, $v ) = explode( '=', $_urlbit );
						
						if ( $k == 'act' )
						{
							$perm_child = $v;
						}
						else if ( $k == 'section' )
						{
							$perm_main = $v;
						}
						else if ( $k == 'code' )
						{
							$perm_bit = $v;
						}
						
						if ( $perm_child AND $perm_main AND $perm_bit )
						{
							break;
						}
					}
					
					if ( $perm_child AND $perm_main AND $perm_bit AND $menu_limits[ $perm_main.':'.$perm_child.':'.$perm_bit ] )
					{
						if ( ! $this->ipsclass->member['_perm_cache'][ $menu_limits[ $perm_main.':'.$perm_child.':'.$perm_bit ] ] )
						{
							$no_access = 1;
						}
					}
					else if ( $perm_child AND $perm_main )
					{
						if ( ! $this->ipsclass->member['_perm_cache'][ $perm_main .':'. $perm_child ] )
						{
							$no_access = 1;
						}
					}
				}
				
				if ( $no_access )
				{
					$extra_css .= ";color:#777";
				}
				 
				$links .= $this->ipsclass->skin_acp_global->global_menu_cat_link( $cid, $pid, $icon, $theurl, $pdata[1], $extra_css, $pdata[0] );
			}
			 
			$html .= $this->ipsclass->skin_acp_global->global_menu_cat_wrap( $name, $links, $cid, isset($this->desc[$cid]) ? $this->desc[$cid] : '' );
			
			unset($links);
			
			$this->ipsclass->admin->jump_menu .= "</optgroup>\n";
		}
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// BUILDS JUMP MENU, yay!
	/*-------------------------------------------------------------------------*/
	
	function build_jump_menu()
	{
		return "";
		global $PAGES, $CATS, $DESC;
		
		$html = "<script type='text/javascript'>
				 function dojump()
				 {
				 	if ( document.jumpmenu.val.options[document.jumpmenu.val.selectedIndex].value != '' )
				 	{
				 		window.location.href = '{$this->ipsclass->base_url}' + '&' + document.jumpmenu.val.options[document.jumpmenu.val.selectedIndex].value;
				 	}
				 }
				 </script>
				 ";
		
		$html .= "<form name='jumpmenu'>\n<select class='jmenu' name='val'>";
		
		foreach($CATS as $cid => $name)
		{
			$html .= "<optgroup label='$name[0]'>\n";
			
			foreach($PAGES[ $cid ] as $pdata)
			{
				$html .= "<option value='$pdata[1]'>$pdata[0]</option>\n";
			}
			
			$html .= "</optgroup>\n";
		}
		
		$html .= "</select>&nbsp;<input type='button' class='jmenubutton' value='Go!' onclick='dojump();' />\n</form>";
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// BUILD SKIN JUMP MENU
	/*-------------------------------------------------------------------------*/
	
	function skin_jump_menu($set_id="")
	{
		if ( $set_id == "" )
		{
			$set_id = $this->ipsclass->input['id'];
		}
		
		$set_id = intval($set_id);
		
		$r = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$set_id ) );
		
		$html = "<form name='gobaakaachoo'>
		         <select name='chooseacardanycard' class='realbutton' onchange=\"autojumpmenu(this)\">
		         <option value=''>Set: {$r['set_name']} options</option>
		         <option value=''>-------------------</option>
		         <option value='{$this->ipsclass->adskin->base_url}&section=lookandfeel&act=wrap&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Board Header & Footer Wrapper</option>
				 <option value='{$this->ipsclass->adskin->base_url}&section=lookandfeel&act=templ&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Template HTML</option>
				 <option value='{$this->ipsclass->adskin->base_url}&section=lookandfeel&act=style&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit CSS (Advanced Mode)</option>
				 <option value='{$this->ipsclass->adskin->base_url}&section=lookandfeel&act=style&code=colouredit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit CSS (Easy Mode)</option>
				 <option value='{$this->ipsclass->adskin->base_url}&section=lookandfeel&act=image&code=edit&id={$r['set_skin_set_id']}&p={$r['set_skin_set_parent']}'>Edit Replacement Macros</option>
				 </select>
				 </form>";
				 
		return $html;
	}
}





?>