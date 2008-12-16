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
|   > $Date: 2006-12-04 15:32:05 -0500 (Mon, 04 Dec 2006) $
|   > $Revision: 759 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Administration Module
|   > Module written by Matt Mecham
|   > Date started: 27th January 2004
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

class ad_emoticons {

	var $functions = "";
	var $html;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "lookandfeel";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "emoticons";
	
	/**
	* Allowed file types
	*/
	var $allowed_files = array( 'png', 'jpeg', 'jpg', 'gif' );
	
	function auto_run()
	{
		//-----------------------------------------
		// Require and RUN !! THERES A BOMB
		//-----------------------------------------
		
		$this->ipsclass->admin->page_detail = "";
		$this->ipsclass->admin->page_title  = "Emoticons Manager";
		$this->ipsclass->admin->nav[] 		= array( $this->ipsclass->form_code, 'Emoticons Manager' );
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_lookandfeel');

		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			//-----------------------------------------
			// Emu?
			//-----------------------------------------
			
			case 'emo':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->emoticon_start();
				break;
				
			case 'emo_packsplash':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->emoticon_pack_splash();
				break;
				
			case 'emo_packexport':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->emoticon_pack_export();
				break;
			
			case 'emo_packimport':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->emoticon_pack_import();
				break;
				
			case 'emo_manage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->emoticon_manage();
				break;
				
			case 'emo_doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->emoticon_edit();
				break;
					
			case 'emo_doadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->emoticon_add();
				break;
				
			case 'emo_remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->emoticon_remove();
				break;
				
			case 'emo_setadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->emoticon_setalter($type='add');
				break;
				
			case 'emo_setedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->emoticon_setalter($type='edit');
				break;
				
			case 'emo_setremove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->emoticon_setremove();
				break;
			
			case 'emo_upload':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':upload' );
				$this->emoticon_upload();		
			
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->emoticon_start();
				break;
		}
	}
	
	//-----------------------------------------
	// EMOTICON Set add
	//-----------------------------------------
	
	function emoticon_setalter($type='add')
	{
		$name = preg_replace( "/[^a-zA-Z0-9\-_]/", "", $this->ipsclass->input['emoset'] );
		
		if ($name == "")
		{
			$this->ipsclass->main_msg = "No valid folder name was entered, please try again using only alphanumerics (A-Z, a-z, 0-9)";
			$this->emoticon_start();
		}
		
		//-----------------------------------------
		// Safe mode?
		//-----------------------------------------
		
		if ( SAFE_MODE_ON )
		{
			$this->ipsclass->main_msg = "SAFE MODE DETECTED: IPB cannot create or edit folders for you, please create or edit the folder manually using FTP in 'style_emoticons'";
			$this->emoticon_start();
		}
		
		//-----------------------------------------
		// Directory exists?
		//-----------------------------------------
		
		if ( file_exists( CACHE_PATH.'style_emoticons/'.$name ) )
		{
			$this->ipsclass->main_msg = "'style_emoticons/$name' already exists, please choose another name.";
			$this->emoticon_start();
		}
		
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Create directory?
			//-----------------------------------------
			
			if ( @mkdir( CACHE_PATH.'style_emoticons/'.$name, 0777 ) )
			{
				@chmod( CACHE_PATH.'style_emoticons/'.$name, 0777 );
				
				$dh = opendir( CACHE_PATH.'style_emoticons/default' );
				
		 		while ( FALSE !== ( $file = readdir( $dh ) ) )
		 		{
		 			if (($file != ".") && ($file != ".."))
		 			{
						@copy( CACHE_PATH.'style_emoticons/default/'.$file, CACHE_PATH.'style_emoticons/'.$name.'/'.$file );
						@chmod( CACHE_PATH.'style_emoticons/'.$name.'/'.$file, 0777 );
		 			}
		 		}
		 		
		 		closedir( $dh );
		 		
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='default'" ) );
				$outer = $this->ipsclass->DB->simple_exec();
			
				while( $r = $this->ipsclass->DB->fetch_row($outer) )
				{
					$this->ipsclass->DB->do_insert( "emoticons", array( 'clickable' => $r['clickable'], 'typed' => $r['typed'], 'emo_set' => $name, 'image' => $r['image'] ) );
				}

				$this->ipsclass->main_msg = "New Folder Added";
				$this->emoticon_start();
			}
			else
			{
				$this->ipsclass->main_msg = "IPB cannot create a new folder for you, please create the folder manually using FTP in 'style_emoticons'";
				$this->emoticon_start();
			}
		}
		else
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->main_msg = "Missing directory name, please try again.";
				$this->emoticon_start();
				return;
			}
			
			if( $this->ipsclass->input['id'] == 'default' )
			{
				$this->ipsclass->main_msg = "You cannot rename the default folder.";
				$this->emoticon_start();
				return;
			}
			
			//-----------------------------------------
			// Rename directory?
			//-----------------------------------------
			
			if ( @rename( CACHE_PATH.'style_emoticons/'.$this->ipsclass->input['id'], CACHE_PATH.'style_emoticons/'.$name ) )
			{
				if ( file_exists( CACHE_PATH.'style_emoticons/'.$name ) )
				{
					//-----------------------------------------
					// Update the emos
					//-----------------------------------------
					
					$this->ipsclass->DB->do_update( 'emoticons', array( 'emo_set' => $name ), "emo_set='".$this->ipsclass->input['id']."'" );
				}
				
				$this->emoticon_rebuildcache();
				
				//-----------------------------------------
				// Update the skins using this set
				//-----------------------------------------
				
				$rebuild_sets = array();
				
				$this->ipsclass->DB->build_query( array( 'select' => 'set_skin_set_id', 'from' => 'skin_sets', 'where' => "set_emoticon_folder='{$this->ipsclass->input['id']}'" ) );
				$outer = $this->ipsclass->DB->exec_query();
				
				while( $r = $this->ipsclass->DB->fetch_row($outer) )
				{
					$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_emoticon_folder' => $name ), 'set_skin_set_id='.$r['set_skin_set_id'] );
					$rebuild_sets[] = $r['set_skin_set_id'];
				}
				
				if( count($rebuild_sets) )
				{
					$this->ipsclass->cache_func->_rebuild_all_caches( $rebuild_sets );
				}
				
				$this->ipsclass->main_msg = "Folder renamed.";
				$this->emoticon_start();
			}
			else
			{
				$this->ipsclass->main_msg = "IPB cannot rename this folder for you.";
				$this->emoticon_start();
			}
		}
	}
	
	//-----------------------------------------
	// EMOTICON Edit
	//-----------------------------------------
	
	function emoticon_edit()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->main_msg = "No emoticon group ID was passed";
			$this->emoticon_start();
		}
		
		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^emo_id_(\d+)$/", $key, $match ) )
			{
				if ( $match[0] )
				{
					$typed = '';

					if( $this->ipsclass->input['id'] == 'default' )
					{
						$typed = str_replace( '&quot;', "", $this->ipsclass->input['emo_type_'.$match[1]] );
						$typed = str_replace( '&#092;', "", $typed );
					}
					
					$click = $this->ipsclass->input[ 'emo_click_'.$match[1] ];
					
					if ( $match[1] )
					{
						if( $typed )
						{
							$orig_typed = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'typed', 'from' => 'emoticons', 'where' => 'id='.intval($match[1]) ) );
							
							$this->ipsclass->DB->do_update( 'emoticons', array( 'clickable' => intval($click), 'typed' => $typed ), 'id='.intval($match[1]) );
							
							$this->ipsclass->DB->do_update( 'emoticons', array( 'typed' => $typed ), "typed='".$orig_typed['typed']."'" );
						}
						else
						{
							$this->ipsclass->DB->do_update( 'emoticons', array( 'clickable' => intval($click) ), 'id='.intval($match[1]) );
						}
					}
				}
			}
		}
		
		$this->emoticon_rebuildcache();
		
		$this->ipsclass->main_msg = "Emoticons updated";
		
		$this->emoticon_manage();
	
	}
	
	//-----------------------------------------
	// EMOTICON Remove
	//-----------------------------------------
	
	function emoticon_remove()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->main_msg = "No emoticon group ID was passed";
			$this->emoticon_start();
		}
		
		if ($this->ipsclass->input['id'] != "default" )
		{
			$this->ipsclass->main_msg = "You may only add, edit, and remove emoticons from the default set";
			$this->emoticon_start();
		}		
		
		if ($this->ipsclass->input['eid'] == "")
		{
			$this->ipsclass->main_msg = "No emoticon ID was passed";
			$this->emoticon_manage();
		}
		
		$emo_info = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'typed', 'from' => 'emoticons', 'where' => "id=".intval($this->ipsclass->input['eid']) ) );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'emoticons', 'where' => "typed='".$emo_info['typed']."'" ) );
		
		$this->emoticon_rebuildcache();
		
		$this->ipsclass->main_msg = "Emoticon removed";
		
		$this->emoticon_manage();
	}
	
	//-----------------------------------------
	// EMOTICON ADD
	//-----------------------------------------
	
	function emoticon_add()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->main_msg = "No emoticon group ID was passed";
			$this->emoticon_start();
		}
		
		if ($this->ipsclass->input['id'] != "default" )
		{
			$this->ipsclass->main_msg = "You may only add, edit, and remove emoticons from the default set";
			$this->emoticon_start();
		}		
		
		if ($this->ipsclass->input['id'] != "default")
		{
			$this->ipsclass->main_msg = "You may only add, edit, and remove emoticons from the default set.";
			$this->emoticon_start();
		}		
		
		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^emo_type_(\d+)$/", $key, $match ) )
			{
				if ( isset( $this->ipsclass->input[$match[0]]) )
				{
					$typed = str_replace( '&quot;', "", $this->ipsclass->input[$match[0]] );
					$click = $this->ipsclass->input['emo_click_'.$match[1] ];
					$add   = $this->ipsclass->input['emo_add_'.$match[1] ];
					$image = $this->ipsclass->input['emo_image_'.$match[1] ];
					$set   = trim($this->ipsclass->input['id']);
					
					$typed = str_replace( '&#092;', "", $typed );
					
					if ( $this->ipsclass->input['addall'] )
					{
						$add = 1;
					}
					
					if ( $add and $typed and $image )
					{
						$this->ipsclass->DB->do_insert( 'emoticons', array( 'clickable' => intval($click), 'typed' => $typed, 'image' => $image, 'emo_set' => $set ) );
						
						$emodirs = array( 0 => '');
						
						$dh = opendir( CACHE_PATH.'style_emoticons' );
						
				 		while ( FALSE !== ( $file = readdir( $dh ) ) )
				 		{
				 			if (($file != ".") && ($file != ".."))
				 			{
								if ( is_dir(CACHE_PATH.'style_emoticons/'.$file) )
								{
									if( $file == 'default' )
									{
										$emodirs[0] = $file;
									}
									else
									{
										$emodirs[] = $file;
									}
								}
				 			}
				 		}
				 		closedir( $dh );
				 		
				 		foreach( $emodirs as $directory )
				 		{
					 		if( $directory == $set )
					 		{
						 		continue;
					 		}
					 		
					 		$this->ipsclass->DB->do_insert( 'emoticons', array( 'clickable' => intval($click), 'typed' => $typed, 'image' => $image, 'emo_set' => $directory ) );						
				 		}
					}
				}
			}
		}
		
		$this->emoticon_rebuildcache();
		
		$this->ipsclass->main_msg = "Emoticons updated";
		
		$this->emoticon_manage();
	}
	
	
	//-----------------------------------------
	// EMOTICON Upload
	//-----------------------------------------
	
	function emoticon_upload()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$overwrite     = 1;
		
		//-----------------------------------------
		// Which folders?
		//-----------------------------------------
		
		$directories = array();
		$first_dir   = '';
		
		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^dir_(.*)$/", $key, $match ) )
			{
				if ( $this->ipsclass->input[$match[0]] == 1 )
				{
					$directories[] = $match[1];
				}
			}
		}
		
		if ( ! count( $directories ) )
		{
			$this->ipsclass->main_msg = "You must choose a folder other than 'default' to upload into.";
			$this->emoticon_start();
		}
		
		//-----------------------------------------
		// Excuse me, can you shift?
		//-----------------------------------------
		
		if ( ! in_array( 'default', $directories ) )
		{
			array_push( $directories, 'default' );
		}
		
		$first_dir = array_shift( $directories );
		
		$emodirs = array( 0 => '');
		
		$dh = opendir( CACHE_PATH.'style_emoticons' );
		
 		while ( FALSE !== ( $file = readdir( $dh ) ) )
 		{
 			if ( ($file != ".") && ($file != "..") )
 			{
				if ( is_dir( CACHE_PATH.'style_emoticons/'.$file ) )
				{
					if( $file == 'default' )
					{
						$emodirs[0] = $file;
					}
					else
					{
						$emodirs[] = $file;
					}
				}
 			}
 		}

 		closedir( $dh );
		
		//-----------------------------------------
		// Loopy loo?
		//-----------------------------------------
		
		foreach( array( 1,2,3,4 ) as $i )
		{
			$field     = 'upload_'.$i;
			
			$FILE_NAME = $_FILES[$field]['name'];
			$FILE_SIZE = $_FILES[$field]['size'];
			$FILE_TYPE = $_FILES[$field]['type'];
			
			//-----------------------------------------
			// Naughty Opera adds the filename on the end of the
			// mime type - we don't want this.
			//-----------------------------------------
			
			$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
			
			//-----------------------------------------					
			// Naughty Mozilla likes to use "none" to indicate an empty upload field.
			// I love universal languages that aren't universal.
			//-----------------------------------------
			
			if ( $_FILES[$field]['name'] == "" or ! $_FILES[$field]['name'] or ($_FILES[$field]['name'] == "none") )
			{
				continue;
			}
			
			//-----------------------------------------
			// Make sure it's not a NAUGHTY file
			//-----------------------------------------
			
			$file_extension = preg_replace( "#^.*\.(.+?)$#si", "\\1", strtolower( $_FILES[ $field ]['name'] ) );
		
			if ( ! in_array( $file_extension, $this->allowed_files ) )
			{
				$this->ipsclass->main_msg = "You can only upload image files (jpeg, jpg, gif and png)";
				$this->emoticon_start();
			} 
			
			//-----------------------------------------
			// Copy the upload to the uploads directory
			//-----------------------------------------
			
			if ( ! @move_uploaded_file( $_FILES[ $field ]['tmp_name'], CACHE_PATH.'style_emoticons/'.$first_dir."/".$FILE_NAME) )
			{
				$this->ipsclass->main_msg = "The upload failed, sorry!";
				$this->emoticon_start();
			}
			else
			{
				@chmod( CACHE_PATH.'style_emoticons/'.$first_dir."/".$FILE_NAME, 0777 );
				
				//-----------------------------------------
				// Copy to other folders
				//-----------------------------------------
				
				if ( is_array( $directories ) and count( $directories ) )
				{
					foreach ( $directories as $newdir )
					{
						if ( file_exists( CACHE_PATH.'style_emoticons/'.$newdir."/".$FILE_NAME ) )
						{
							if ( $overwrite != 1 OR $newdir == 'default' )
							{
								continue;
							}
						}
						
						if ( @copy( CACHE_PATH.'style_emoticons/'.$first_dir."/".$FILE_NAME, CACHE_PATH.'style_emoticons/'.$newdir."/".$FILE_NAME ) )
						{
							@chmod( CACHE_PATH.'style_emoticons/'.$newdir."/".$FILE_NAME, 0777 );
						}
					}
				}
				
				// Let's make sure this 'image' is available in all directories too
				if ( is_array( $emodirs ) and count( $emodirs ) )
				{
					foreach ( $emodirs as $newdir )
					{
						if ( file_exists( CACHE_PATH.'style_emoticons/'.$newdir."/".$FILE_NAME ) )
						{
							continue;
						}
						
						if( @copy( CACHE_PATH.'style_emoticons/'.$first_dir."/".$FILE_NAME, CACHE_PATH.'style_emoticons/'.$newdir."/".$FILE_NAME ) )
						{
							@chmod( CACHE_PATH.'style_emoticons/'.$newdir."/".$FILE_NAME, 0777 );
						}
					}
				}				
			}
		}
		
		$this->ipsclass->main_msg = "Uploads complete!";
		$this->emoticon_start();
	}
	
	
	//-----------------------------------------
	// EMOTICON Start
	//-----------------------------------------
	
	function emoticon_start()
	{
		if ( ! is_dir( CACHE_PATH. 'style_emoticons') )
		{
			$this->ipsclass->admin->error("Could not locate the emoticons directory - make sure the 'style_emoticons' path is set correctly");
			$this->ipsclass->admin->output();
		}
		
		//-----------------------------------------
		// Get emoticon count
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'admin_emo_count', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$emo_db[ $r['emo_set'] ] = $r;
		}
		
		//-----------------------------------------
		// Get emoticon folders
		//-----------------------------------------
		
		$emodirs = array( 0 => '');
		
		$dh = opendir( CACHE_PATH.'style_emoticons' );
		
 		while ( FALSE !== ( $file = readdir( $dh ) ) )
 		{
 			if (($file != ".") && ($file != ".."))
 			{
				if ( is_dir(CACHE_PATH.'style_emoticons/'.$file) )
				{
					if( $file == 'default' )
					{
						$emodirs[0] = $file;
					}
					else
					{
						$emodirs[] = $file;
					}
				}
 			}
 		}
 		closedir( $dh );
		
		//-----------------------------------------
		// Start output
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->html->emoticon_overview_wrapper_addform();
		
		$row_html = "";
		
		$i 		= 0;
		$total 	= count($emodirs);
		
		foreach( $emodirs as $dir )
		{
			$i++;

			$data = array();
			
			$files 			= $this->emoticon_get_folder_contents( $dir );
			$data['count'] 	= intval( count($files) );
			
			if( $dir == 'default' )
			{
				$data['line_image'] = '';
				$data['link_text'] = "Manage Emoticons";
			}
			else
			{
				$data['link_text'] = "Set Clickable";
				
				if( $i == $total )
				{
					$data['line_image'] = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_line_l.gif' border='0' />&nbsp;";
				}
				else
				{
					$data['line_image'] = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_line_t.gif' border='0' />&nbsp;";
				}
			}
			
			if ( is_writeable( CACHE_PATH . '/style_emoticons/'.$dir ) )
			{
				if( $dir == 'default' )
				{
					$checked_def = "checked='checked' disabled='disabled' ";
				}
				else
				{
					$checked_def = "";
				}
				
				$data['icon']     = 'icon_can_write.gif';
				$data['title']    = 'This folder is writeable and new emoticons can be added';
				$data['checkbox'] = "<input type='checkbox' name='dir_{$dir}' {$checked_def}value='1' />";
			}
			else
			{
				$data['icon']     = 'icon_cannot_write.gif';
				$data['title']    = 'This folder is NOT writeable and the CHMOD must be changed';
				$data['checkbox'] = "-";
			}
			
			$data['dir'] = $dir;
			$data['dir_count'] = intval($emo_db[ $dir ]['count']);
			
			$row_html .= $this->html->emoticon_overview_row( $data );
		}
		
		$this->ipsclass->html .= $this->html->emoticon_overview_wrapper( $row_html );
		
		$this->ipsclass->admin->page_detail = "You may add/edit or remove emoticons in this section.";
		$this->ipsclass->admin->page_title   = "Emoticon Control";
		
		$this->ipsclass->admin->output();
	
	}
	
	//-----------------------------------------
	// EMOTICON Remove set
	//-----------------------------------------
	
	function emoticon_setremove()
	{
		$this->ipsclass->admin->page_detail = "Remove an IPB emoticon pack.";
		$this->ipsclass->admin->page_title  = "Emoticon Management";
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->main_msg = "No emoticon set was passed.";
			$this->emoticon_start();
		}
		
		if( $this->ipsclass->input['id'] == 'default' )
		{
			$this->ipsclass->main_msg = "You cannot rename the default folder.";
			$this->emoticon_start();
			return;
		}
		
		$this->ipsclass->admin->rm_dir( CACHE_PATH.'style_emoticons/'.$this->ipsclass->input['id'] );
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'emoticons', 'where' => "emo_set='{$this->ipsclass->input['id']}'" ) );
		
		$this->emoticon_rebuildcache();
		
		$this->ipsclass->main_msg = "Emoticon folder removed.";
		$this->emoticon_start();
	}
	
	//-----------------------------------------
	// EMOTICON Import/Export Pack Splash
	//-----------------------------------------
	
	function emoticon_pack_splash()
	{
		$this->ipsclass->admin->page_detail = "Export or import IPB emoticon packs.";
		$this->ipsclass->admin->page_title  = "Emoticon Management";
		$this->ipsclass->admin->nav[] 		= array( '', 'Import/Export Emoticon Packs' );
		
		if ( ! is_dir( CACHE_PATH. 'style_emoticons') )
		{
			$this->ipsclass->admin->error("Could not locate the emoticons directory - make sure the 'style_emoticons' path is set correctly");
			$this->ipsclass->admin->output();
		}
		
		//-----------------------------------------
		// Get emoticon count
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'admin_emo_count', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$emo_db[ $r['emo_set'] ] = $r;
		}
		
		//-----------------------------------------
		// Get emoticon folders
		//-----------------------------------------
		
		$emodirs = array();
		$emodd   = array();
		
		$dh = opendir( CACHE_PATH.'style_emoticons' );
		
 		while ( FALSE !== ( $file = readdir( $dh ) ) )
 		{
 			if (($file != ".") && ($file != ".."))
 			{
				if ( is_dir(CACHE_PATH.'style_emoticons/'.$file) )
				{
					$emodirs[] = $file;
					$emodd[]   = array( $file, $file );
				}
 			}
 		}
 		closedir( $dh );
 		
		//-----------------------------------------
		// EXPORT: Start table
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		
		//-----------------------------------------
		// EXPORT: Start output
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'emo_packexport' ),
															     2 => array( 'act'   , 'emoticons'      ),
															     4 => array( 'section', $this->ipsclass->section_code ),
													    )      );
													  			
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Export an Emoticon Pack" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													            "<b>Export which emoticon group?</b><div style='color:gray'>An IPB Emoticon Pack is an XMLarchive of the images and activation words (i.e. :smile:)</div>",
													            $this->ipsclass->adskin->form_dropdown( 'emo_set', $emodd )
													   )      );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Export");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// IMPORT: Start table
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		
		//-----------------------------------------
		// IMPORT: Start output
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'emo_packimport' ),
																 2 => array( 'act'   , 'emoticons'      ),
																 3 => array( 'MAX_FILE_SIZE', '10000000000' ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														) , "uploadform", " enctype='multipart/form-data'"     );
													
													  			
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Import an Emoticon Pack" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													  		"<b>Import into which emoticon group?</b><div style='color:gray'>An IPB Emoticon Pack is an XMLarchive of the images and activation words (i.e. :smile:)</div>",
													  		$this->ipsclass->adskin->form_dropdown( 'emo_set', $emodd )
													   )      );
													   
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													  		"<b><u>OR</u> Import into a new group named:</b><div style='color:gray'>Enter the name of the new emoticon group.</div>",
													  		$this->ipsclass->adskin->form_input( 'new_emo_set' )
													   )      );
													   
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													 		 "<b>Overwrite existing images and activation words?</b><div style='color:gray'>If yes, new images replace old</div>",
													  		$this->ipsclass->adskin->form_yes_no( 'overwrite' )
													   )      );
													   
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													 		 "<b>Upload XML Emoticon Archive</b><div style='color:gray'>Browse your computer for 'ipb_emoticons.xml' or 'ipb_emoticons.xml.gz'</div>",
													  		$this->ipsclass->adskin->form_upload(  )
													   )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Import");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		$this->ipsclass->admin->output();
	
	}
	
	//-----------------------------------------
	// EMOTICON EXPORT! EXPORT! EX... oh, that's abort isn't it?
	//-----------------------------------------
	
	function emoticon_pack_export()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		require_once( KERNEL_PATH.'class_xmlarchive.php' );

		$xmlarchive = new class_xmlarchive();
		
		//-----------------------------------------
		// Checkdamoonah
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['emo_set'] )
		{
			$this->ipsclass->main_msg = "You must specify which emoticon group you wish to export";
		}
		
		//-----------------------------------------
		// Get emowticuns
		//-----------------------------------------
		
		$emo_db = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='".$this->ipsclass->input['emo_set']."'" ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$emo_db[ $r['image'] ] = $r;
		}
		
		//-----------------------------------------
		// Get ;) :D folders
		//-----------------------------------------
		
		$emodirs = array();
		$emodd   = array();
		
		$dh = opendir( CACHE_PATH.'style_emoticons/'.$this->ipsclass->input['emo_set'] );
		
 		while ( FALSE !== ( $file = readdir( $dh ) ) )
 		{
 			if (($file != ".") && ($file != ".."))
 			{
 				if ( $emo_db[ $file ] != "" )
 				{
					$files_to_add[] = CACHE_PATH.'style_emoticons/'.$this->ipsclass->input['emo_set'].'/'.$file;
				}
 			}
 		}
 		
 		closedir( $dh );
 		
		//-----------------------------------------
		// Add um into the ark-hive
		//-----------------------------------------
		
		foreach( $files_to_add as $f )
		{
			$xmlarchive->xml_add_file( $f );
		}
		
		//-----------------------------------------
		// Create the database archive...
		//-----------------------------------------
		
		$xml->xml_set_root( 'emoticonexport', array( 'exported' => time(), 'name' => $this->ipsclass->input['emo_set'] ) );
		
		//-----------------------------------------
		// Get emo group
		//-----------------------------------------
		
		$xml->xml_add_group( 'emogroup' );
		
		foreach( $emo_db as $r )
		{
			$content = array();
			
			$content[] = $xml->xml_build_simple_tag( 'typed'    , $r['typed'] );
			$content[] = $xml->xml_build_simple_tag( 'image'    , $r['image'] );
			$content[] = $xml->xml_build_simple_tag( 'clickable', $r['clickable'] );
			
			$entry[] = $xml->xml_build_entry( 'emoticon', $content );
		}
		
		$xml->xml_add_entry_to_group( 'emogroup', $entry );
		
		$xml->xml_format_document();
		
		//-----------------------------------------
		// Add in emoticons doc to archive
		//-----------------------------------------
		
		$xmlarchive->xml_add_file_contents( $xml->xml_document, 'emoticon_data.xml' );
		
		$xmlarchive->xml_create_archive();
		
		//-----------------------------------------
		// Create archive and send to
		// browser.
		//-----------------------------------------
		
		$imagearchive = $xmlarchive->xml_get_contents();
		
		$this->ipsclass->admin->show_download( $imagearchive, 'ipb_emoticons.xml' );
	
	}
	
	
	//-----------------------------------------
	// IMPORT THE EMOTICONS
	//-----------------------------------------
	
	function emoticon_pack_import()
	{
		$content = $this->ipsclass->admin->import_xml( 'ipb_emoticons.xml' );
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->ipsclass->main_msg = "Upload failed, ipb_emoticons.xml was either missing or empty";
			$this->emoticon_pack_splash();
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		require_once( KERNEL_PATH.'class_xmlarchive.php' );

		$xmlarchive = new class_xmlarchive();
		
		$xmlarchive->xml_read_archive_data( $content );
		
		//-----------------------------------------
		// Get the datafile
		//-----------------------------------------
		
		$emoticons     = array();
		$emoticon_data = array();
		
		foreach( $xmlarchive->file_array as $f )
		{
			if ( $f['filename'] == 'emoticon_data.xml' )
			{
				$emoticon_data = $f['content'];
			}
			else
			{
				$emoticons[ $f['filename'] ] = $f['content'];
			}
		}
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $emoticon_data );
		
		//-----------------------------------------
		//  New set, old set - we're set!
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['emo_set'] and ! $this->ipsclass->input['new_emo_set'] )
		{
			$this->ipsclass->main_msg = "You must specify which emoticon group you wish to import into";
		}
		
		$emo_set_dir = $this->ipsclass->input['emo_set'];
		
		$this->ipsclass->input['new_emo_set'] = preg_replace( "/[^a-zA-Z0-9\-_]/", "",$this->ipsclass->input['new_emo_set'] );
		
		if ( $this->ipsclass->input['new_emo_set'] )
		{
			$emo_set_dir = $this->ipsclass->input['new_emo_set'];
			
			//-----------------------------------------
			// Directory exists?
			//-----------------------------------------
			
			if ( file_exists( CACHE_PATH.'style_emoticons/'.$emo_set_dir ) )
			{
				$this->ipsclass->main_msg = "'style_emoticons/$emo_set_dir' already exists, please choose another name.";
				$this->emoticon_pack_splash();
			}
		
			//-----------------------------------------
			// Create directory?
			//-----------------------------------------
			
			if ( @mkdir( CACHE_PATH.'style_emoticons/'.$emo_set_dir, 0777 ) )
			{
				@chmod( CACHE_PATH.'style_emoticons/'.$emo_set_dir, 0777 );
			}
			else
			{
				$this->ipsclass->main_msg = "IPB cannot create a new folder for you, please create the folder manually using FTP in 'style_emoticons'";
				$this->emoticon_pack_splash();
			}
		}
		
		//-----------------------------------------
		// Are we over writing?
		//-----------------------------------------
		
		$emo_image = array();
		$emo_typed = array();
		
		if ( $this->ipsclass->input['overwrite'] != 1  )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='".$emo_set_dir."'" ) );
			$this->ipsclass->DB->simple_exec();
		
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$emo_image[ $r['image'] ] = 1;
				$emo_typed[ $r['typed'] ] = 1;
			}
		}
		
		foreach( $xml->xml_array['emoticonexport']['emogroup']['emoticon'] as $entry )
		{
			$image = $entry['image']['VALUE'];
			$typed = $entry['typed']['VALUE'];
			$click = $entry['clickable']['VALUE'];
			
			if ( $emo_image[ $image ] or $emo_typed[ $typed ] )
			{
				continue;
			}
			
			$file_extension = preg_replace( "#^.*\.(.+?)$#si", "\\1", strtolower( $image ) );
		
			if ( ! in_array( $file_extension, $this->allowed_files ) )
			{
				continue;
			}
			
			@unlink( CACHE_PATH.'style_emoticons/'.$emo_set_dir.'/'.$image );
			
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'emoticons', 'where' => "typed='$typed' and image='$image' and emo_set='$emo_set_dir'" ) );
			
			if ( $FH = fopen( CACHE_PATH.'style_emoticons/'.$emo_set_dir.'/'.$image, 'wb' ) )
			{
				if ( fwrite( $FH, $emoticons[ $image ] ) )
				{
					fclose( $FH );
					
					$this->ipsclass->DB->do_insert( 'emoticons', array( 'typed' => $typed, 'image' => $image, 'clickable' => $click, 'emo_set' => $emo_set_dir ) );
				}
			}
			
			// Let's add it to all other directories if
			// the image doesn't already exist
			
			$dh = opendir( CACHE_PATH.'style_emoticons' );
			
	 		while ( FALSE !== ( $file = readdir( $dh ) ) )
	 		{
	 			if (($file != ".") && ($file != ".."))
	 			{
					if ( is_dir(CACHE_PATH.'style_emoticons/'.$file) )
					{
						if( !file_exists( CACHE_PATH.'style_emoticons/'.$file.'/'.$image ) )
						{
							$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'emoticons', 'where' => "typed='$typed' and image='$image' and emo_set='$file'" ) );
							
							if ( $FH = fopen( CACHE_PATH.'style_emoticons/'.$file.'/'.$image, 'wb' ) )
							{
								if ( fwrite( $FH, $emoticons[ $image ] ) )
								{
									fclose( $FH );
									
									$this->ipsclass->DB->do_insert( 'emoticons', array( 'typed' => $typed, 'image' => $image, 'clickable' => $click, 'emo_set' => $file ) );
								}
							}
						}
					}
	 			}
	 		}
	 		closedir( $dh );
		}
		
		$this->emoticon_rebuildcache();
                    
		$this->ipsclass->main_msg = "Emoticon XMLarchive import completed";
		
		$this->emoticon_start();
	
	}
	
	
	//-----------------------------------------
	// EMOTICON Manage
	//-----------------------------------------
	
	function emoticon_manage()
	{
		$this->ipsclass->input['id'] = trim($this->ipsclass->input['id']);
		
		$this->ipsclass->admin->nav[] = array( '', 'Managing Set '.$this->ipsclass->input['id'] );
		
		if( $this->ipsclass->input['id'] == 'default' )
		{
			$this->ipsclass->admin->page_detail = "You may add/edit or remove emoticons in this section.<br>";
		}
		
		$this->ipsclass->admin->page_detail .= "Clickable refers to emoticons that are in the posting screens 'Clickable Emoticons' table.";
		
		if( $this->ipsclass->input['id'] == 'default' )
		{
			$this->ipsclass->admin->page_detail = "<br /><strong>You may NOT use the character &quot; in the emoticons code section.";
		}		

		$this->ipsclass->admin->page_title  = "Emoticon Control";
		
		//-----------------------------------------
		// Get emoticons for this group
		//-----------------------------------------
		
		$emo_db   = array();
		$emo_file = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'emoticons', 'where' => "emo_set='".$this->ipsclass->input['id']."'", 'order' => 'clickable DESC, image ASC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$emo_db[ $r['image'] ] = $r;
		}
		
		$emo_file  = array();
		$emo_rfile = $this->emoticon_get_folder_contents( $this->ipsclass->input['id'] );
		
		foreach( $emo_rfile as $ef )
		{
			$emo_file[ $ef ] = $ef;
		}
					
		//-----------------------------------------
		// Start output
		//-----------------------------------------
		
		$per_row  = 5;
		$td_width = 100 / $per_row;
		
		$this->ipsclass->html .= "<div class='tableborder'>
							 <div class='tableheaderalt'>Assigned Emoticons in set '{$this->ipsclass->input['id']}'</div>
							 <form action='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=emo_doedit&id={$this->ipsclass->input['id']}' method='post'>
							 <input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
							 <table cellpadding='4' cellspacing='0' border='0' width='100%'>
						   ";
						   
		$count      = 0;
		$smilies    = "<tr align='center'>\n";
		$poss_names = array();
		
		foreach( $emo_db as $image => $data )
		{
			$count++;
			
			unset( $emo_file[ $image ] );
			
			if ( $data['clickable'] )
			{
				$click = 'checked="checked"';
				$class = 'tablerow1';
			}
			else
			{
				$click = '';
				$class = 'tablerow2';
			}
			
			$smilies .= "<td width='{$td_width}%' align='center' class='$class'>
						  <fieldset>
						  	<legend><strong>{$image}</strong></legend>
						  	<input type='hidden' name='emo_id_{$data['id']}' value='{$data['id']}' />
						  	<img src='style_emoticons/{$this->ipsclass->input['id']}/{$image}' border='0' />&nbsp;&nbsp;&nbsp;&nbsp;";

			if( $this->ipsclass->input['id'] == 'default' )
			{
				$smilies .= "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=emo_remove&eid={$data['id']}&id={$this->ipsclass->input['id']}' title='Remove this emoticon'><img src='{$this->ipsclass->skin_acp_url}/images/emo_delete.gif' border='0' alt='Delete' /></a>
						  	<br />
						  	<input type='textinput' class='realbutton' size='10' name='emo_type_{$data['id']}' value='{$data['typed']}' />";
			}
			else
			{
				$smilies .= "<br /><br /><span style='font-family:Verdana,Arial;font-size:10px;font-weight:bold;'>{$data['typed']}</span>";
			}
			
			$smilies .= "<br /><br />Clickable? <input type='checkbox'  name='emo_click_{$data['id']}' value='1' {$click} />
						  </fieldset>
						 </td>";
			
			if ($count == $per_row )
			{
				$smilies .= "</tr>\n\n<tr align='center'>";
				$count = 0;
			}
			
			$poss_names[$data['typed']] = $data['typed'];
		}
		
		$smilies = preg_replace( "#style_emoticons#", $this->ipsclass->vars['board_url'].'/style_emoticons', $smilies );
		
		if ( $count > 0 and $count != $per_row )
		{
			for ($i = $count ; $i < $per_row ; ++$i)
			{
				$smilies .= "<td class='tablerow2'>&nbsp;</td>\n";
			}
			
			$smilies .= "</tr>";
		}
		
		
		$this->ipsclass->html .= $smilies;
		
		$this->ipsclass->html .= "</table>
							<div class='tablesubheader' align='center'><input type='submit' class='realbutton' value='Update Emoticons' /></form></div></div><br />";
		
		
		//-----------------------------------------
		// Images left in the dir?
		//-----------------------------------------
		
		if ( count( $emo_file ) && $this->ipsclass->input['id'] == 'default' )
		{
			$this->ipsclass->html .= "<div class='tableborder'>
								<div class='tableheaderalt'>Unassigned images in folder '{$this->ipsclass->input['id']}'</div>
								<form action='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=emo_doadd&id={$this->ipsclass->input['id']}' method='post'>
								<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
								<table cellpadding='4' cellspacing='0' border='0' width='100%'>
							  ";
							  
			$count   = 0;
			$smilies = "<tr align='center'>\n";
			
			$master_count = 0;
			
			foreach( $emo_file as $image )
			{
				$count++;
				$master_count++;
				
				$poss_name = ':'.preg_replace( "/(.*)(\..+?)$/", "\\1", $image ).':';
				
				if ( isset($poss_names[ $poss_name ]) AND $poss_names[ $poss_name ] )
				{
					$poss_name = preg_replace( "/:$/", "2:", $poss_name );
				}
				
				$smilies .= "<td width='{$td_width}%' align='center' class='tablerow1'>
							  <fieldset>
								<legend><strong>{$image}</strong></legend>
								<img src='style_emoticons/{$this->ipsclass->input['id']}/{$image}' border='0' />&nbsp;&nbsp;<b>Add</b> <input name='emo_add_{$master_count}' type='checkbox' value='1' />
								<br />
								Type: <input type='textinput' class='realbutton' size='10' name='emo_type_{$master_count}' value='$poss_name' />
								<br /><br />Clickable? <input type='checkbox' name='emo_click_{$master_count}' value='1' />
								<input type='hidden' name='emo_image_{$master_count}' value='{$image}' />
							  </fieldset>
							 </td>";
				
				if ($count == $per_row )
				{
					$smilies .= "</tr>\n\n<tr align='center'>";
					$count = 0;
				}
			}
			
			if ( $count > 0 and $count != $per_row )
			{
				for ($i = $count ; $i < $per_row ; ++$i)
				{
					$smilies .= "<td class='tablerow1'>&nbsp;</td>\n";
				}
				
				$smilies .= "</tr>";
			}
			
			$smilies = preg_replace( "#style_emoticons#", $this->ipsclass->vars['board_url'].'/style_emoticons', $smilies );
			
			
			$this->ipsclass->html .= $smilies;
			
			$this->ipsclass->html .= "</table>
								<div class='tablesubheader' align='center'><input type='submit' class='realbutton' value='Add Checked Emoticons' />&nbsp;&nbsp;<input type='submit' name='addall' class='realbutton' value='Add All Emoticons' /></form></div></div>";
		}
		
		$this->ipsclass->admin->output();
	
	}
	
	//-----------------------------------------
	// EMOTICON Rebuild Cache
	//-----------------------------------------
	
	function emoticon_rebuildcache()
	{
		require_once ROOT_PATH.'sources/classes/bbcode/class_bbcode_core.php';
		
		$this->ipsclass->cache['emoticons'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
		$this->ipsclass->DB->simple_exec();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['emoticons'][] = $r;
		}
		
		usort( $this->ipsclass->cache['emoticons'] , array( 'class_bbcode_core', 'smilie_length_sort' ) );
		
		$this->ipsclass->update_cache( array( 'name' => 'emoticons', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	//-----------------------------------------
	// EMOTICON Get folder contents
	//-----------------------------------------
	
	function emoticon_get_folder_contents($folder='default')
	{
		$files = array();
		
		//-----------------------------------------
		// Get emoticon folders
		//-----------------------------------------
		
		$dh = opendir( CACHE_PATH.'style_emoticons/'.$folder );
		
 		while ( FALSE !== ( $file = readdir( $dh ) ) )
 		{
 			if ( ($file != ".") && ($file != "..") )
 			{
				if ( preg_match( "/\.(?:gif|jpg|jpeg|png|swf)$/i", $file ) )
				{
					$files[] = $file;
				}
 			}
 		}
 		
 		closedir( $dh );
 		
 		return $files;
 	}

	
	
	function perly_length_sort($a, $b)
	{
		if ( strlen($a['typed']) == strlen($b['typed']) )
		{
			return 0;
		}
		return ( strlen($a['typed']) > strlen($b['typed']) ) ? -1 : 1;
	}
	
	function perly_word_sort($a, $b)
	{
		if ( strlen($a['type']) == strlen($b['type']) )
		{
			return 0;
		}
		return ( strlen($a['type']) > strlen($b['type']) ) ? -1 : 1;
	}
	
}


?>