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
|   > $Date: 2007-09-11 12:37:52 -0400 (Tue, 11 Sep 2007) $
|   > $Revision: 1102 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > UserCP functions library
|   > Module written by Matt Mecham
|   > Date started: 20th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Fri 21 May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class func_usercp
{
	var $ipsclass;
	var $class;
	var $image;
	var $jump_html;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function func_usercp( )
	{
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate the UserCP menu
	/*-------------------------------------------------------------------------*/
	
	function ucp_generate_menu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$menu_html      = "";
		$component_html = "";
		$folder_links   = "";
		
		//-----------------------------------------
		// Get all member info..
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'generic_get_all_member', array( 'mid' => $this->ipsclass->member['id'] ) );
		$this->ipsclass->DB->cache_exec_query();
    			   
    	if ( ! $member = $this->ipsclass->DB->fetch_row() )
    	{
    		$this->ipsclass->DB->do_insert( 'member_extra', array( 'id' => $this->ipsclass->member['id'] ) );
    	}
    	else
    	{
    		$this->ipsclass->member = array_merge( $member, $this->ipsclass->member );
    	}
    	
    	//-----------------------------------------
    	// Get enabled components
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['components'] ) and count( $this->ipsclass->cache['components'] ) )
    	{
    		foreach( $this->ipsclass->cache['components'] as $data )
    		{
    			$file = ROOT_PATH.'sources/components_ucp/'.$data['com_filename'].'.php';
    			
    			if ( file_exists( $file ) )
    			{
    				require_once( $file );
    				$name = 'components_ucp_'.$data['com_filename'];
    				$tmp  = new $name();
    				$tmp->ipsclass =& $this->ipsclass;
    				
    				$component_html .= $tmp->ucp_build_menu();
    				
    				unset($tmp);
    			}
			}
		}
		
		//-----------------------------------------
    	// Print the top button menu
    	//-----------------------------------------
    	
    	$menu_html = $this->ipsclass->compiled_templates['skin_ucp']->Menu_bar( $component_html );
    	
    	//-----------------------------------------
    	// Messenger
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->member['g_use_pm'] )
        {
        	//-----------------------------------------
			// Do a little set up, do a litle dance, get
			// down tonight! *boogie*
			//-----------------------------------------
			
			$this->jump_html = "<select name='VID' class='forminput'>\n";
			
			$this->ipsclass->member['dir_data'] = array();
			
			//-----------------------------------------
			// Do we have VID?
			// No, it's just the way we walk! Haha, etc.
			//-----------------------------------------
			
			if ( isset($this->ipsclass->input['VID']) AND $this->ipsclass->input['VID'] )
			{
				$this->vid = $this->ipsclass->input['VID'];
			}
			else
			{
				$this->vid = 'in';
			}
    	
        	//-----------------------------------------
    		// Print folder links
    		//-----------------------------------------
    		
			if ( ! $this->ipsclass->member['vdirs'] )
			{
				$this->ipsclass->member['vdirs'] = "in:Inbox|sent:Sent Items";
			}
			
			foreach( explode( "|", $this->ipsclass->member['vdirs'] ) as $dir )
			{
				list ($id  , $data)  = explode( ":", $dir );
				list ($real, $count) = explode( ";", $data );
				
				if ( ! $id )
				{
					continue;
				}
				
				$this->ipsclass->member['dir_data'][$id] = array( 'id' => $id, 'real' => $real, 'count' => $count );
    		
				if ($this->vid == $id)
				{
					$this->ipsclass->member['current_dir'] = $real;
					$this->ipsclass->member['current_id']  = $id;
					$this->jump_html .= "<option value='$id' selected='selected'>$real</option>\n";
				}
				else
				{
					$this->jump_html .= "<option value='$id'>$real</option>\n";
				}
				
				if ( $count )
				{
					$real .= " ({$count})";
				}
				
				$folder_links .= $this->ipsclass->compiled_templates['skin_ucp']->menu_bar_msg_folder_link($id, $real);
			}
			
			if ( $folder_links != "" )
			{
				$menu_html = str_replace( "<!--IBF.FOLDER_LINKS-->", $folder_links, $menu_html );
			}
        }
        
        $this->jump_html .= "<!--EXTRA--></select>\n\n";
        
        return $menu_html;
	}
	
	/*-------------------------------------------------------------------------*/
	// HANDLE SUBSCRIPTION START
	/*-------------------------------------------------------------------------*/
	
	function subs_choose($save="")
	{
		//-----------------------------------------
		// Topic - forum - what?
		//-----------------------------------------
		
		$method = $this->ipsclass->input['method'] == 'forum' ? 'forum' : 'topic';
		$tid    = intval($this->ipsclass->input['tid']);
		$fid    = intval($this->ipsclass->input['fid']);
		$forum	= array();
		$topic	= array();
		
		if ( $method == 'topic' )
		{
			//-----------------------------------------
			// Get the details from the DB (TOPIC)
			//-----------------------------------------
		
			$topic = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$tid ) );
			
			if ( ! $topic['tid'] )
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
			}
			
			$forum = $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ];
		}
		else
		{
			//-----------------------------------------
			// Get the details (FORUM)
			//-----------------------------------------
		
			$forum = $this->ipsclass->forums->forum_by_id[ $fid ];
		}
		
		//-----------------------------------------
		// Permy check
		//-----------------------------------------
		
		if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $forum['id'] ]['read_perms'] ) != TRUE )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'missing_files') );
		}
		
		//-----------------------------------------
		// Passy check
		//-----------------------------------------
		
		if ( ! in_array( $this->ipsclass->member['mgroup'], explode(",", $forum['password_override']) ) AND ( isset($forum['password']) AND $forum['password'] != "" ) )
		{
			if ( $this->ipsclass->forums->forums_compare_password( $forum['id'] ) != TRUE )
			{
				$this->ipsclass->Error( array( LEVEL => 1, MSG => 'forum_no_access') );
			}
		}
		
		//-----------------------------------------
		// Have we already subscribed?
		//-----------------------------------------
		
		if ( $method == 'forum' )
		{
			$tmp = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'frid as tmpid',
												  'from'   => 'forum_tracker',
												  'where'  => "forum_id={$fid} AND member_id=".$this->ipsclass->member['id'] ) );
		}
		else
		{
			$tmp = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'trid as tmpid',
												  'from'   => 'tracker',
												  'where'  => "topic_id={$tid} AND member_id=".$this->ipsclass->member['id'] ) );
		}
		
		if ( $tmp['tmpid'] )
		{
			$this->ipsclass->Error( array( LEVEL => 1, MSG => 'already_sub') );
		}
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		if ( ! $save )
		{
			//-----------------------------------------
			// Okay, lets do the HTML
			//-----------------------------------------
			
			$this->class->output .= $this->ipsclass->compiled_templates['skin_ucp']->subs_show_choice_page( $forum, $topic, $method, $this->class->md5_check );
		}
		else
		{
			//-----------------------------------------
			// Auth check
			//-----------------------------------------
			
			if ( $this->ipsclass->input['auth_key'] != $this->class->md5_check )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
			}
			
			//-----------------------------------------
			// Method..
			//-----------------------------------------
			
			switch ($this->ipsclass->input['emailtype'])
			{
				case 'immediate':
					$this->method = 'immediate';
					break;
				case 'delayed':
					$this->method = 'delayed';
					break;
				case 'none':
					$this->method = 'none';
					break;
				case 'daily':
					$this->method = 'daily';
					break;
				case 'weekly':
					$this->method = 'weekly';
					break;
				default:
					$this->method = 'delayed';
					break;
			}
        
			//-----------------------------------------
			// Add it to the DB
			//-----------------------------------------
			
			if ( $method == 'forum' )
			{
				$this->ipsclass->DB->do_insert( 'forum_tracker', array (
														 'member_id'        => $this->ipsclass->member['id'],
														 'forum_id'         => $fid,
														 'start_date'       => time(),
														 'forum_track_type' => $this->method,
											  )       );
														  
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['sub_added'], "showforum=$fid" );	
			
			}
			else
			{
				$this->ipsclass->DB->do_insert( 'tracker',  array (
												   'member_id'        => $this->ipsclass->member['id'],
												   'topic_id'         => $tid,
												   'start_date'       => time(),
												   'topic_track_type' => $this->method,
										)       );
										
				$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['sub_added'], "showtopic=$tid&st={$this->ipsclass->input['st']}" );
	
			}
		}
		
		$this->class->page_title = $this->ipsclass->lang['t_welcome'];
 		$this->class->nav        = array( "<a href='".$this->ipsclass->base_url."act=usercp&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>" );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// HANDLE PHOTO OP'S
	/*-------------------------------------------------------------------------*/
	
	function do_photo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval( $this->ipsclass->member['id'] );
		
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
				
		if ( $_POST['act'] == "" )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ( $this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		// Do upload...
		//-----------------------------------------
		
		$photo = $this->lib_upload_photo();
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'pp_member_id',
													  'from'   => 'profile_portal',
													  'where'  => "pp_member_id=".$member_id ) );
		$this->ipsclass->DB->simple_exec();
	
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			# Update...
			$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_main_photo'                => $photo['final_location'],
												  				   	 'pp_main_width'                => intval($photo['final_width']),
																   	 'pp_main_height'               => intval($photo['final_height']),
																	 'pp_thumb_photo'               => $photo['t_final_location'],
																	 'pp_thumb_width'               => intval($photo['t_final_width']),
																	 'pp_thumb_height'              => intval($photo['t_final_height']),
																 ), 'pp_member_id='.$member_id );
		}
		else
		{
			# Insert
			$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_main_photo'                => $photo['final_location'],
												  				   	 'pp_main_width'                => intval($photo['final_width']),
																   	 'pp_main_height'               => intval($photo['final_height']),
																	 'pp_thumb_photo'               => $photo['t_final_location'],
																	 'pp_thumb_width'               => intval($photo['t_final_width']),
																	 'pp_thumb_height'              => intval($photo['t_final_height']),
																	 'pp_member_id'					=> $member_id,
																 ) );
		}
		
		if ( $photo['status'] == 'fail' )
		{
			$this->ipsclass->load_language( 'lang_profile' );
			$this->class->photo( $this->ipsclass->lang[ 'pp_' . $photo['error'] ] );
			return;
		}
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['photo_c_up'], "act=UserCP&CODE=photo" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Function to upload personal photo
	/*-------------------------------------------------------------------------*/
	/**
	* Upload personal photo function
	* Assumes all security checks have been performed by this point
	*
	* @return array  [ error (error message), status (status message [ok/fail] ) ]
	*/
	function lib_upload_photo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return		      = array( 'error'            => '',
								   'status'           => '',
								   'final_location'   => '',
								   'final_width'      => '',
								   'final_height'     => '',
								   't_final_location' => '',
								   't_final_width'    => '',
								   't_final_height'   => ''  );
		$delete_photo     = intval( $_POST['delete_photo'] );
		$member_id        = intval( $this->ipsclass->member['id'] );
		$real_name        = '';
		$upload_dir       = '';
		$final_location   = '';
		$final_width      = '';
		$final_height     = '';
		$t_final_location = '';
		$t_final_width    = '';
		$t_final_height   = '';
		$t_real_name      = '';
		$t_height		  = 50;
		$t_width          = 50;
		
		list( $p_max, $p_width, $p_height ) = explode( ":", $this->ipsclass->member['g_photo_max_vars'] );
		
		//-----------------------------------------
		// Sort out upload dir
		//-----------------------------------------
		
		$upload_path  = $this->ipsclass->vars['upload_dir'];
		
		# Preserve original path
		$_upload_path = $this->upload_path;
		
		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( ! is_dir( $upload_path . "/profile" ) )
		{
			if ( @mkdir( $upload_path . "/profile", 0777 ) )
			{
				@chmod( $upload_path . "/profile", 0777 );
				
				# Set path and dir correct
				$upload_path .= "/profile";
				$upload_dir   = "profile/";
			}
			else
			{
				$upload_dir   = "";
			}
		}
		else
		{
			# Set path and dir correct
			$upload_path .= "/profile";
			$upload_dir   = "profile/";
		}

		//-----------------------------------------
		// Deleting the photo?
		//-----------------------------------------
		
		if ( $delete_photo )
		{
			$this->bash_uploaded_photos( $member_id, $upload_path );
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'pp_member_id',
														  'from'   => 'profile_portal',
														  'where'  => "pp_member_id=".$member_id ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_main_photo'   => '',
													  				   	 'pp_main_width'   => '',
																	   	 'pp_main_height'  => '',
																		 'pp_thumb_photo'  => '',
																		 'pp_thumb_width'  => '',
																		 'pp_thumb_height' => '',
																	 ), 'pp_member_id='.$member_id );
			}
			else
			{
				$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_main_photo'   => '',
													  				   	 'pp_main_width'   => '',
																	   	 'pp_main_height'  => '',
																		 'pp_thumb_photo'  => '',
																		 'pp_thumb_width'  => '',
																		 'pp_thumb_height' => '',
																		 'pp_member_id'    => $member_id
																	 ) );
			}
			
			$return['status'] = 'deleted';
			return $return;
		}
		
		//-----------------------------------------
		// Lets check for an uploaded photo..
		//-----------------------------------------
	
		if ( $_FILES['upload_photo']['name'] != "" and ($_FILES['upload_photo']['name'] != "none" ) )
		{
			//-----------------------------------------
			// Are we allowed to upload this photo?
			//-----------------------------------------
			
			if ( $p_max < 0 )
			{
				$return['status'] = 'fail';
				$return['error']  = 'no_photo_upload_permission';
			}
			
			//-----------------------------------------
			// Remove any uploaded photos...
			//-----------------------------------------
			
			$this->bash_uploaded_photos( $member_id, $upload_path );
			
			$real_name = 'photo-'.$member_id;
			
			//-----------------------------------------
			// Load the library
			//-----------------------------------------
			
			require_once( KERNEL_PATH.'class_upload.php' );
			$upload    = new class_upload();
			
			require_once( KERNEL_PATH.'class_image.php' );
			$image_lib = new class_image();
			
			//-----------------------------------------
			// Set up the variables
			//-----------------------------------------
			
			$upload->out_file_name     = 'photo-'.$member_id;
			$upload->out_file_dir      = $upload_path;
			$upload->max_file_size     = ($p_max * 1024) * 8;  // Allow xtra for compression
			$upload->upload_form_field = 'upload_photo';
			
			//-----------------------------------------
			// Populate allowed extensions
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['photo_ext'] )
			{
				foreach( explode( ',', $this->ipsclass->vars['photo_ext'] ) as $data )
				{
					if ( trim( $data ) )
					{
						$upload->allowed_file_ext[] = trim( $data );
					}
				}
			}
			
			//-----------------------------------------
			// Upload...
			//-----------------------------------------
			
			$upload->upload_process();
			
			//-----------------------------------------
			// Error?
			//-----------------------------------------
			
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{
					case 1:
						// No upload
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
						break;
					case 2:
						// Invalid file ext
						$return['status'] = 'fail';
						$return['error']  = 'invalid_file_extension';
						break;
					case 3:
						// Too big...
						$return['status'] = 'fail';
						$return['error']  = 'upload_to_big';
						break;
					case 4:
						// Cannot move uploaded file
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
						break;
					case 5:
						// Possible XSS attack (image isn't an image)
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
						break;
				}
				
				return $return;
			}
						
			//-----------------------------------------
			// Still here?
			//-----------------------------------------
			
			$real_name   = $upload->parsed_file_name;
			$t_real_name = $upload->parsed_file_name;
			
			//-----------------------------------------
			// Check image size...
			//-----------------------------------------
			
			if ( ! $this->ipsclass->vars['disable_ipbsize'] )
			{
				//-----------------------------------------
				// Main photo
				//-----------------------------------------
				
				$image_lib->in_type        = 'file';
				$image_lib->out_type       = 'file';
				$image_lib->in_file_dir    = $upload_path;
				$image_lib->in_file_name   = $real_name;
				$image_lib->out_file_name  = 'photo-'.$member_id;
				$image_lib->desired_width  = $p_width;
				$image_lib->desired_height = $p_height;
				
				$return = $image_lib->generate_thumbnail();
	
				$im['img_width']  = $return['thumb_width'];
				$im['img_height'] = $return['thumb_height'];
				
				# Do we have an attachment?
				if ( isset( $return['thumb_location'] ) AND strstr( $return['thumb_location'], 'photos-' ) )
				{
					//-----------------------------------------
					// Kill old and rename new...
					//-----------------------------------------
					
					@unlink( $upload_path . "/" . $real_name );
					
					$real_name = 'photo-'.$member_id.'.'.$image_lib->file_extension;
					
					@rename( $upload_path . "/" . $return['thumb_location'], $upload_path . "/" . $real_name );
					@chmod(  $upload_path . "/" . $real_name, 0777 );
				}
				
				//-----------------------------------------
				// MINI photo
				//-----------------------------------------
				
				$image_lib->in_type        = 'file';
				$image_lib->out_type       = 'file';
				$image_lib->in_file_dir    = $upload_path;
				$image_lib->in_file_name   = $t_real_name;
				$image_lib->out_file_name  = 'photo-thumb-'.$member_id;
				$image_lib->desired_width  = $t_width;
				$image_lib->desired_height = $t_height;
				
				$return = $image_lib->generate_thumbnail();
	
				$t_im['img_width']    = $return['thumb_width'];
				$t_im['img_height']   = $return['thumb_height'];
				$t_im['img_location'] = $return['thumb_location'];
			}
			else
			{
				//-----------------------------------------
				// Main photo
				//-----------------------------------------
				
				$w = intval($this->ipsclass->input['man_width'])  ? intval($this->ipsclass->input['man_width'])  : $p_width;
				$h = intval($this->ipsclass->input['man_height']) ? intval($this->ipsclass->input['man_height']) : $p_height;
				$im['img_width']  = $w > $p_width  ? $p_width  : $w;
				$im['img_height'] = $h > $p_height ? $p_height : $h;
				
				//-----------------------------------------
				// Mini photo
				//-----------------------------------------
				
				$_data = $this->ipsclass->scale_image( array( 'max_height' => $t_height,
															  'max_width'  => $t_width,
															  'cur_width'  => $im['img_width'],
															  'cur_height' => $im['img_height'] ) );
				
				$t_im['img_width']  = $_data['img_width'];
				$t_im['img_height'] = $_data['img_height'];
			}
			
			//-----------------------------------------
			// Check the file size (after compression)
			//-----------------------------------------
			
			if ( filesize( $upload_path . "/" . $real_name ) > ( $p_max * 1024 ) )
			{
				@unlink( $upload_path . "/" . $real_name );
				
				// Too big...
				$return['status'] = 'fail';
				$return['error']  = 'upload_to_big';
				return $return;
			}
			
			//-----------------------------------------
			// Main photo
			//-----------------------------------------
			
			$final_location = $upload_dir . $real_name;
			$final_width    = $im['img_width'];
			$final_height   = $im['img_height'];
			
			//-----------------------------------------
			// Mini photo
			//-----------------------------------------
			
			$t_final_location = $upload_dir . $t_im['img_location'];
			$t_final_width    = $t_im['img_width'];
			$t_final_height   = $t_im['img_height'];
		}
		else
		{
			$return['status'] = 'ok';
			return $return;
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$return['final_location']   = $final_location;
		$return['final_width']      = $final_width;
		$return['final_height']     = $final_height;
		
		$return['t_final_location'] = $t_final_location;
		$return['t_final_width']    = $t_final_width;
		$return['t_final_height']   = $t_final_height;
		
		$return['status'] = 'ok';
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// REMOVE UPLOADED PICCIES
	/*-------------------------------------------------------------------------*/
	
	function bash_uploaded_photos( $id, $upload_dir='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$upload_dir = $upload_dir ? $upload_dir : $this->ipsclass->vars['upload_dir'];
		
		//-----------------------------------------
		// Go...
		//-----------------------------------------
		
		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $upload_dir."/photo-".$id.".".$ext ) )
			{
				@unlink( $upload_dir."/photo-".$id.".".$ext );
			}
			
			if ( @file_exists( $upload_dir."/photo-thumb-".$id.".".$ext ) )
			{
				@unlink( $upload_dir."/photo-thumb-".$id.".".$ext );
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// REMOVE UPLOADED AVATARS
	/*-------------------------------------------------------------------------*/
	
	function bash_uploaded_avatars($id)
	{
		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $this->ipsclass->vars['upload_dir']."/av-".$id.".".$ext ) )
			{
				@unlink( $this->ipsclass->vars['upload_dir']."/av-".$id.".".$ext );
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// SAVE SKIN/LANG PREFS
	/*-------------------------------------------------------------------------*/
	
	function do_skin_langs()
	{
		// Check input for 1337 h/\x0r nonsense
		
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		
		if ( preg_match( "/\.\./", $this->ipsclass->input['u_skin'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//-----------------------------------------
		if ( preg_match( "/\.\./", $this->ipsclass->input['u_language'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		
		//-----------------------------------------
		
		if ($this->ipsclass->vars['allow_skins'] == 1)
		{
		
			$this->ipsclass->DB->build_and_exec_query( array( 'select' => 'sid', 'from' => 'skins', 'where' =>  "hidden <> 1 AND sid='".$this->ipsclass->input['u_skin']."'" ) );
			
			if (! $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'skin_not_found' ) );
			}
			
			$db_string = array ( 'language'    => $this->ipsclass->input['u_language'],
								 'skin       ' => $this->ipsclass->input['u_skin'] );
		}
		else
		{
			$db_string = array ( 'language'    => $this->ipsclass->input['u_language'] );
		}
		
		//-----------------------------------------
		
		
		
		$this->ipsclass->DB->do_update( 'members', $db_string, "id='".$this->ipsclass->member['id']."'" );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['set_updated'], "act=UserCP&CODE=06" );
	
	}
	
	/*-------------------------------------------------------------------------*/
	// Board prefs
	/*-------------------------------------------------------------------------*/
	
	function do_board_prefs()
	{
		// Check the input for naughties :D
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
		// Timezone
		//-----------------------------------------
		
		if ( ! preg_match( "/^[\-\d\.]+$/", $this->ipsclass->input['u_timezone'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ($this->ipsclass->vars['postpage_contents'] == "")
		{
			$this->ipsclass->vars['postpage_contents'] = '5,10,15,20,25,30,35,40';
		}
		
		if ($this->ipsclass->vars['topicpage_contents'] == "")
		{
			$this->ipsclass->vars['topicpage_contents'] = '5,10,15,20,25,30,35,40';
		}
		
		$this->ipsclass->vars['postpage_contents']  .= ",-1,";
		$this->ipsclass->vars['topicpage_contents'] .= ",-1,";
		
		//-----------------------------------------
		// Post page
		//-----------------------------------------
		
		if (! preg_match( "/(^|,)".intval($this->ipsclass->input['postpage']).",/", $this->ipsclass->vars['postpage_contents'] ) )
		{
			$this->ipsclass->input['postpage'] = '-1';
		}
		
		//-----------------------------------------
		// Topic page
		//-----------------------------------------
		
		if (! preg_match( "/(^|,)".intval($this->ipsclass->input['topicpage']).",/", $this->ipsclass->vars['topicpage_contents'] ) )
		{
			$this->ipsclass->input['topicpage'] = '-1';
		}
		
		//-----------------------------------------
		// RTE
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['posting_allow_rte'] )
		{
			$this->ipsclass->input['editor_choice'] = 'std';
		}
		
		//-----------------------------------------
		// PMS (Childish? Yes. Funny? No)
		// 2 means admin says no. :o
		//-----------------------------------------
		
		if ( $this->ipsclass->member['members_disable_pm'] == 2 )
		{
			$this->ipsclass->member['members_disable_pm'] = 2;
		}
		else
		{
			$this->ipsclass->member['members_disable_pm'] = intval( $this->ipsclass->input['disable_messenger'] );
		}
		
		$this->ipsclass->DB->do_update( 'members',  array (  'time_offset'           => $this->ipsclass->input['u_timezone'],
															 'view_avs'              => intval($this->ipsclass->input['VIEW_AVS']),
															 'view_sigs'             => intval($this->ipsclass->input['VIEW_SIGS']),
															 'view_img'              => intval($this->ipsclass->input['VIEW_IMG']),
															 'view_pop'              => intval($this->ipsclass->input['DO_POPUP']),
															 'dst_in_use'            => ( isset($this->ipsclass->input['DST']) AND intval($this->ipsclass->input['DSTCHECK']) == 0 ) ? intval($this->ipsclass->input['DST']) : 0,
															 'members_auto_dst'      => intval($this->ipsclass->input['DSTCHECK']),
															 'members_disable_pm'    => intval($this->ipsclass->member['members_disable_pm']),
															 'members_editor_choice' => substr( $this->ipsclass->input['editor_choice'], 0, 3 ),
															 'view_prefs'            => intval($this->ipsclass->input['postpage'])."&".intval($this->ipsclass->input['topicpage']),
												 ) , 'id='.$this->ipsclass->member['id']  );
												 
		$this->ipsclass->pack_and_update_member_cache( $this->ipsclass->member['id'], array( 'qr_open' => intval($this->ipsclass->input['OPEN_QR']) ), $this->ipsclass->member['_cache'] );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['set_updated'], "act=UserCP&CODE=04" );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Complete email settings
	/*-------------------------------------------------------------------------*/
	
	function do_email_settings()
	{
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		// Check and set the rest of the info
		//-----------------------------------------
		
		foreach ( array('hide_email', 'admin_send', 'send_full_msg', 'pm_reminder', 'auto_track') as $v )
		{
			$this->ipsclass->input[ $v ] = isset($this->ipsclass->input[ $v ]) ? $this->ipsclass->input[ $v ] : NULL;
			
			$this->ipsclass->input[ $v ] = $this->ipsclass->is_number( $this->ipsclass->input[ $v ] );
			
			if ( $this->ipsclass->input[ $v ] < 1 )
			{
				$this->ipsclass->input[ $v ] = 0;
			}
		}
		
		//-----------------------------------------
		// Type of track
		//-----------------------------------------
		
		if ( $this->ipsclass->input['auto_track'] )
		{
			$allowed = array( 'none', 'immediate', 'delayed', 'daily', 'weekly' );
 			
 			if ( in_array( $this->ipsclass->input['trackchoice'], $allowed ) )
 			{
 				$this->ipsclass->input['auto_track'] = $this->ipsclass->input['trackchoice'];
 			}
 		}
		
		$this->ipsclass->DB->do_update( 'members', array ( 'hide_email'         => $this->ipsclass->input['hide_email'],
														   'email_full'         => $this->ipsclass->input['send_full_msg'],
														   'email_pm'           => $this->ipsclass->input['pm_reminder'],
														   'allow_admin_mails'  => $this->ipsclass->input['admin_send'],
														   'auto_track'         => $this->ipsclass->input['auto_track'],
									  )  ,'id='.$this->ipsclass->member['id']       );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['email_c_up'], "act=UserCP&CODE=02" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Set gallery avatar
	/*-------------------------------------------------------------------------*/
	
	function set_internal_avatar()
	{
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		
		$real_choice = 'noavatar';
		$real_dims   = '';
		$real_dir    = "";
		$save_dir    = "";
		
		//-----------------------------------------
		// Check incoming..
		//-----------------------------------------
		
		$current_folder  = preg_replace( "/[^\s\w_-]/"             , "", urldecode($this->ipsclass->input['current_folder']) );
		$selected_avatar = preg_replace( "/[^\s\w\._\-\[\]\(\)]/"  , "", urldecode($this->ipsclass->input['avatar']) );
		
		//-----------------------------------------
		// Are we in a folder?
		//-----------------------------------------
		
		if ($current_folder == 'root')
		{
			$current_folder = "";
		}
		
		if ($current_folder != "")
		{
			$real_dir = "/".$current_folder;
			$save_dir = $current_folder."/";
		}
		
		//-----------------------------------------
		// Check it out!
		//-----------------------------------------
		
		$avatar_gallery = array();
	
		$dh = opendir( CACHE_PATH.'style_avatars'.$real_dir );
		
		while ( false !== ( $file = readdir( $dh ) ) )
		{
			if ( !preg_match( "/^..?$|^index/i", $file ) )
			{
				$avatar_gallery[] = $file;
			}
		}
		closedir( $dh );
		
		if (!in_array( $selected_avatar, $avatar_gallery ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_avatar_selected' ) );
		}
		
		$final_string = $save_dir.$selected_avatar;
		
		// Update the DB
		
		$this->ipsclass->DB->do_update( 'member_extra', array( 'avatar_location' => $final_string, 'avatar_type' => 'local' ), 'id='.$this->ipsclass->member['id'] );
	
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['av_c_up'], "act=UserCP&CODE=24" );
	
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Save avatar
	/*-------------------------------------------------------------------------*/
	
	function do_avatar()
	{
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
				
		//-----------------------------------------
		// Got attachment types?
		//-----------------------------------------
		
		if ( ! is_array( $this->ipsclass->cache['attachtypes'] ) )
		{
			$this->ipsclass->cache['attachtypes'] = array();
				
			$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
			}
		}
		
		$real_type = "";
		
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		// Did we press "remove"?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['remove']) AND $this->ipsclass->input['remove'] )
		{
			$this->bash_uploaded_avatars($this->ipsclass->member['id']);
			
			$this->ipsclass->DB->do_update( 'member_extra', array( 'avatar_location' => '',
																   'avatar_size'     => '',
																   'avatar_type'     => '',
																 ), 'id='.$this->ipsclass->member['id'] );
			
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['av_c_up'], "act=UserCP&CODE=24" );
			
		}
		
		//-----------------------------------------
		// NO? CARRY ON!!
		//-----------------------------------------

		list($p_width, $p_height) = explode( "x", strtolower($this->ipsclass->vars['avatar_dims']) );
		
		//-----------------------------------------
		// Check to make sure we don't just have
		// http:// in the URL box..
		//-----------------------------------------
		
		if ( preg_match( "/^http:\/\/$/i", $this->ipsclass->input['url_avatar'] ) )
		{
			$this->ipsclass->input['url_avatar'] = "";
		}
		
		if ( preg_match( "#javascript:#is", $this->ipsclass->input['url_avatar'] ) )
		{
			$this->ipsclass->input['url_avatar'] = "";
		}
		
		//-----------------------------------------
		// Not so fast, big shot.
		//-----------------------------------------
		
		if ( $this->ipsclass->xss_check_url( $this->ipsclass->input['url_avatar'] ) !== TRUE )
		{
			$this->ipsclass->input['url_avatar'] = '';
		}
		
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		if ( empty($this->ipsclass->input['url_avatar']) or $this->ipsclass->input['url_avatar'] == "" )
		{
			//-----------------------------------------
			// Lets check for an uploaded photo..
			//-----------------------------------------
		
			if ($_FILES['upload_avatar']['name'] != "" and ($_FILES['upload_avatar']['name'] != "none") )
			{
				//-----------------------------------------
				// Are we allowed to upload this avatar?
				//-----------------------------------------
				
				if ( preg_match( "#javascript:#is", $_FILES['upload_avatar']['name'] ) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_upload' ) );
				}
				
				if ( ($this->ipsclass->member['g_avatar_upload'] != 1) or ($this->ipsclass->vars['avup_size_max'] < 1) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_upload' ) );
				}
				
				//-----------------------------------------
				// Remove any uploaded avatars..
				//-----------------------------------------
				
				$this->bash_uploaded_avatars($this->ipsclass->member['id']);
				
				$real_name = 'av-'.$this->ipsclass->member['id'];
				$real_type = 'upload';
				
				//-----------------------------------------
				// Load the library
				//-----------------------------------------
				
				require_once( KERNEL_PATH.'class_upload.php' );
				$upload = new class_upload();
				
				require_once( KERNEL_PATH.'class_image.php' );
				$this->image = new class_image();
				
				//-----------------------------------------
				// Set up the variables
				//-----------------------------------------
				
				$upload->out_file_name     = 'av-'.$this->ipsclass->member['id'];
				$upload->out_file_dir      = $this->ipsclass->vars['upload_dir'];
				$upload->max_file_size     = ($this->ipsclass->vars['avup_size_max'] * 1024) * 8;  // Allow xtra for compression
				$upload->upload_form_field = 'upload_avatar';
				
				//-----------------------------------------
				// Populate allowed extensions
				//-----------------------------------------
				
				if ( is_array( $this->ipsclass->cache['attachtypes'] ) and count( $this->ipsclass->cache['attachtypes'] ) )
				{
					foreach( $this->ipsclass->cache['attachtypes'] as $data )
					{
						if ( $data['atype_photo'] )
						{
							$upload->allowed_file_ext[] = $data['atype_extension'];
						}
					}
				}
				
				//-----------------------------------------
				// Upload...
				//-----------------------------------------
				
				$upload->upload_process();
				
				//-----------------------------------------
				// Error?
				//-----------------------------------------
				
				if ( $upload->error_no )
				{
					//-----------------------------------------
					// Remove it 'cos there's a problem
					//-----------------------------------------
					
					$this->ipsclass->DB->do_update( 'member_extra', array( 'avatar_location' => '', 'avatar_size' => '', 'avatar_type' => '' ), 'id='.$this->ipsclass->member['id'] );
					
					switch( $upload->error_no )
					{
						case 1:
							// No upload
							$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_failed' ) );
							break;
						case 2:
							// Invalid file ext
							$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_type' ) );
							break;
						case 3:
							// Too big...
							$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_to_big') );
							break;
						case 4:
							// Cannot move uploaded file
							$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_failed' ) );
							break;
						case 5:
							// Possible XSS attack (image isn't an image)
							$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_failed' ) );
							break;
					}
				}
				
				if ( ( $upload->file_extension == 'swf' ) AND ($this->ipsclass->vars['allow_flash'] != 1) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_flash_av' ) );
				}
							
				//-----------------------------------------
				// Still here?
				//-----------------------------------------
				
				$real_name = $upload->parsed_file_name;
				
				if ( ! $this->ipsclass->vars['disable_ipbsize'] and $upload->file_extension != '.swf' )
				{
					$this->image->in_type        = 'file';
					$this->image->out_type       = 'file';
					$this->image->in_file_dir    = $this->ipsclass->vars['upload_dir'];
					$this->image->in_file_name   = $real_name;
					$this->image->out_file_name  = 'avs-'.$this->ipsclass->member['id'];
					$this->image->desired_width  = $p_width;
					$this->image->desired_height = $p_height;
					
					$return = $this->image->generate_thumbnail();
		
					$im['img_width']  = $return['thumb_width'];
					$im['img_height'] = $return['thumb_height'];
					
					//-----------------------------------------
					// Do we have an attachment?
					//-----------------------------------------
					
					if ( isset($return['thumb_location']) AND strstr( $return['thumb_location'], 'avs-' ) )
					{
						//-----------------------------------------
						// Kill old and rename new...
						//-----------------------------------------
						
						@unlink( $this->ipsclass->vars['upload_dir']."/".$real_name );
						
						$real_name = 'av-'.$this->ipsclass->member['id'].'.'.$this->image->file_extension;
						
						@rename( $this->ipsclass->vars['upload_dir']."/".$return['thumb_location'], $this->ipsclass->vars['upload_dir']."/".$real_name );
						@chmod(  $this->ipsclass->vars['upload_dir']."/".$real_name, 0777 );
					}
				}
				else
				{	
					$w = intval($this->ipsclass->input['man_width'])  ? intval($this->ipsclass->input['man_width'])  : $p_width;
					$h = intval($this->ipsclass->input['man_height']) ? intval($this->ipsclass->input['man_height']) : $p_height;
					$im['img_width']  = $w > $p_width  ? $p_width  : $w;
					$im['img_height'] = $h > $p_height ? $p_height : $h;
				}
				
				//-----------------------------------------
				// Check the file size (after compression)
				//-----------------------------------------
				
				if ( filesize( $this->ipsclass->vars['upload_dir']."/".$real_name ) > ($this->ipsclass->vars['avup_size_max']*1024))
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$real_name );
					
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_to_big' ) );
				}
				
				//-----------------------------------------
				// Set the "real" avatar..
				//-----------------------------------------
					
				$real_choice = $real_name;
				$real_dims   = $im['img_width'].'x'.$im['img_height'];
			}
			else
			{
				//-----------------------------------------
				// URL field and upload field left blank.
				//-----------------------------------------
				
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_avatar_selected' ) );
			
			}
		}
		else
		{
			//-----------------------------------------
			// It's an entered URL 'ting man
			//-----------------------------------------
			
			$this->ipsclass->input['url_avatar'] = trim($this->ipsclass->input['url_avatar']);
			
			if ( empty($this->ipsclass->vars['allow_dynamic_img']) )
			{
				if ( preg_match( "/[?&;]/", $this->ipsclass->input['url_avatar'] ) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'avatar_invalid_url' ) );
				}
			}
			
			//-----------------------------------------
			// Check extension
			//-----------------------------------------
			
			$ext = explode ( ",", $this->ipsclass->vars['avatar_ext'] );
			$checked = 0;
			$av_ext = preg_replace( "/^.*\.(\S+)$/", "\\1", $this->ipsclass->input['url_avatar'] );
			
			foreach ($ext as $v )
			{
				if (strtolower($v) == strtolower($av_ext))
				{
					if ( ( $v == 'swf' ) AND ($this->ipsclass->vars['allow_flash'] != 1) )
					{
						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_flash_av' ) );
					}
					
					$checked = 1;
				}
			}
			
			if ($checked != 1)
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'avatar_invalid_ext' ) );
			}
			
			//-----------------------------------------
			// Check image size...
			//-----------------------------------------
			
			$im = array();
			
			if ( ! $this->ipsclass->vars['disable_ipbsize'] )
			{
				if ( ! $img_size = @GetImageSize( $this->ipsclass->input['url_avatar'] ) )
				{
					$img_size[0] = $p_width;
					$img_size[1] = $p_height;
				}
				
				$im = $this->ipsclass->scale_image( array(
												'max_width'  => $p_width,
												'max_height' => $p_height,
												'cur_width'  => $img_size[0],
												'cur_height' => $img_size[1]
									   )      );
			}
			else
			{	
				$w = intval($this->ipsclass->input['man_width'])  ? intval($this->ipsclass->input['man_width'])  : $p_width;
				$h = intval($this->ipsclass->input['man_height']) ? intval($this->ipsclass->input['man_height']) : $p_height;
				$im['img_width']  = $w > $p_width  ? $p_width  : $w;
				$im['img_height'] = $h > $p_height ? $p_height : $h;
			}
			
			//-----------------------------------------
			// Remove any uploaded images..
			//-----------------------------------------
			
			$this->bash_uploaded_avatars($this->ipsclass->member['id']);
			
			$real_choice = $this->ipsclass->input['url_avatar'];
			$real_dims   = $im['img_width'].'x'.$im['img_height'];
			$real_type   = 'url';
		}
		
		//-----------------------------------------
		// Update the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'member_extra', array( 'avatar_location' => $real_choice, 'avatar_size' => $real_dims, 'avatar_type' => $real_type ), 'id='.$this->ipsclass->member['id'] );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['av_c_up'], "act=UserCP&CODE=24" );
	
	}
	
	
	function do_profile()
	{
		//-----------------------------------------
		// Check to make sure that we can edit profiles..
		//-----------------------------------------
		
		if ( empty($this->ipsclass->member['g_edit_profile']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cant_use_feature' ) );
		}
				
		$this->class->init_parser();
		
		//-----------------------------------------
		// Check for bad entry
		//-----------------------------------------
		
		if ($_POST['act'] == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		
		//-----------------------------------------
        // Nawty, Nawty!
        //-----------------------------------------
        
        if ($this->ipsclass->input['auth_key'] != $this->class->md5_check )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields = new custom_fields( $this->ipsclass->DB );
    	
    	$fields->member_id   = $this->ipsclass->member['id'];
    	$fields->mem_data_id = $this->ipsclass->member['id'];
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	$fields->admin       = intval($this->ipsclass->member['g_access_cp']);
    	$fields->supmod      = intval($this->ipsclass->member['g_is_supmod']);
    	
    	$fields->init_data();
    	$fields->parse_to_save();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count( $fields->error_fields['empty'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form', 'EXTRA' => $fields->error_fields['empty'][0]['pf_title'] ) );
		}
		
		if ( count( $fields->error_fields['invalid'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form', 'EXTRA' => $fields->error_fields['invalid'][0]['pf_title'] ) );
		}
		
		if ( count( $fields->error_fields['toobig'] ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'cf_to_long', 'EXTRA' => $fields->error_fields['toobig'][0]['pf_title'] ) );
		}
		
		//-----------------------------------------
		
		if ( (strlen($_POST['Interests']) > $this->ipsclass->vars['max_interest_length']) and ($this->ipsclass->vars['max_interest_length']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'int_too_long' ) );
		}
		//-----------------------------------------
		if ( (strlen($_POST['Location']) > $this->ipsclass->vars['max_location_length']) and ($this->ipsclass->vars['max_location_length']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'loc_too_long' ) );
		}
		//-----------------------------------------
		if (strlen($_POST['WebSite']) > 150)
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'web_too_long' ) );
		}
		//-----------------------------------------
		if ( ($_POST['ICQNumber']) && ( ! preg_match( "/^[\d\-]+$/", $_POST['ICQNumber'] ) ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'not_icq_number' ) );
		}
		
		
		//-----------------------------------------
		// make sure that either we entered
		// all calendar fields, or we left them
		// all blank
		//-----------------------------------------
		
		$c_cnt = 0;
		
		foreach ( array('day','month','year') as $v )
		{
			if ( ! empty($this->ipsclass->input[$v] ) )
			{
				$c_cnt++;
			}
		}
		
		if ( ($c_cnt > 0) and ($c_cnt != 3) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'calendar_not_all' ) );
		}
		else if( ($c_cnt > 0) and ($c_cnt == 3) )
		{
			//-----------------------------------------
			// Make sure it's a legal date
			//-----------------------------------------
			
			$_year = $this->ipsclass->input['year'] ? $this->ipsclass->input['year'] : 1999;
			
			if ( ! checkdate( $this->ipsclass->input['month'], $this->ipsclass->input['day'], $_year ) )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'ucp_birthday_legal_date' ) );
			}
		}
		
		if ( ! preg_match( "#^http://#", $this->ipsclass->input['WebSite'] ) )
		{
			$this->ipsclass->input['WebSite'] = 'http://'.$this->ipsclass->input['WebSite'];
		}

		//-----------------------------------------
		// Start off our array
		//-----------------------------------------
		
		$set = array(  
					   'bday_day'    => $this->ipsclass->input['day'],
					   'bday_month'  => $this->ipsclass->input['month'],
					   'bday_year'   => $this->ipsclass->input['year'],
					);
					
		$bet = array(  'website'     => $this->ipsclass->input['WebSite'],
					   'icq_number'  => intval($this->ipsclass->input['ICQNumber']),
					   'aim_name'    => $this->ipsclass->input['AOLName'],
					   'yahoo'       => $this->ipsclass->input['YahooName'],
					   'msnname'     => $this->ipsclass->input['MSNName'],
					   'location'    => $this->class->parser->bad_words( $this->ipsclass->input['Location'] ),
					   'interests'   => $this->class->parser->bad_words( $this->ipsclass->input['Interests'] ),
					);

		$_gender = $this->ipsclass->input['gender'] == 'male' ? 'male' : ( $this->ipsclass->input['gender'] == 'female' ? 'female' : '' );
		
		//-----------------------------------------
		// check to see if we can enter a member title
		// and if one is entered, update it.
		//-----------------------------------------
		
		if ( (isset($this->ipsclass->input['member_title'])) and ( isset($this->ipsclass->vars['post_titlechange']) ) and ( $this->ipsclass->member['posts'] >= $this->ipsclass->vars['post_titlechange']) )
		{
			$set['title'] = $this->class->parser->bad_words( $this->ipsclass->input['member_title'] );
		}
		
		//-----------------------------------------
		// Update the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'title' => 'string' );
		
		$this->ipsclass->DB->do_update( 'members'     , $set, 'id='.$this->ipsclass->member['id'] );
		
		$this->ipsclass->DB->do_update( 'member_extra', $bet, 'id='.$this->ipsclass->member['id'] );
		
		$check = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'pp_member_id', 'from' => 'profile_portal', 'where' => 'pp_member_id=' . $this->ipsclass->member['id'] ) );
		
		if( $check['pp_member_id'] )
		{
			$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_gender' => $_gender ), 'pp_member_id=' . $this->ipsclass->member['id'] );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_gender' => $_gender, 'pp_member_id' => $this->ipsclass->member['id'] ) );
		}
		
		//-----------------------------------------
		// Save the profile stuffy wuffy
		//-----------------------------------------
	
		if ( count( $fields->out_fields ) )
		{
			//-----------------------------------------
			// Do we already have an entry in
			// the content table?
			//-----------------------------------------
			
			$test = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'member_id', 'from' => 'pfields_content', 'where' => 'member_id='.$this->ipsclass->member['id'] ) );
			
			if ( $test['member_id'] )
			{
				//-----------------------------------------
				// We have it, so simply update
				//-----------------------------------------
				
				$this->ipsclass->DB->force_data_type = array();
				
				foreach( $fields->out_fields as $_field => $_data )
				{
					$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
				}
				
				$this->ipsclass->DB->do_update( 'pfields_content', $fields->out_fields, 'member_id='.$this->ipsclass->member['id'] );
			}
			else
			{
				$this->ipsclass->DB->force_data_type = array();
				
				foreach( $fields->out_fields as $_field => $_data )
				{
					$this->ipsclass->DB->force_data_type[ $_field ] = 'string';
				}
				
				$fields->out_fields['member_id'] = $this->ipsclass->member['id'];
				
				$this->ipsclass->DB->do_insert( 'pfields_content', $fields->out_fields );
			}
		}
		
		//-----------------------------------------
 		// Use sync module?
 		//-----------------------------------------
 		
 		if ( USE_MODULES == 1 )
		{
			$bet['id'] = $this->ipsclass->member['id'];
			$bet['name'] = $this->ipsclass->member['members_display_name'];
			$this->class->modules->register_class($this);
    		$this->class->modules->on_profile_update( array_merge( $bet, $set, array( 'name' => $this->ipsclass->member['name'], 'id' => $this->ipsclass->member['id'] ) ) );
   		}
		
		// Return us!
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=usercp&amp;member_id='.$member_id.'&amp;CODE=01&amp;___msg=settings_updated&md5check='.$this->ipsclass->md5_check );
	}
	
	/*-------------------------------------------------------------------------*/
	// SIGNATURE: SAVE
	/*-------------------------------------------------------------------------*/
	
	function do_signature()
	{
		$this->class->init_parser();
		
		//-----------------------------------------
		// Check length
		//-----------------------------------------
		
		if ( (strlen($_POST['Post']) > $this->ipsclass->vars['max_sig_length']) and ($this->ipsclass->vars['max_sig_length']) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'sig_too_long' ) );
		}
		
		//-----------------------------------------
		// Check key
		//-----------------------------------------
		
		if ( $_POST['key'] != $this->ipsclass->return_md5_check() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post' ) );
		}
		
		//-----------------------------------------
		// Remove board tags
		//-----------------------------------------
		
		$this->ipsclass->input['Post'] = $this->ipsclass->remove_tags( $this->ipsclass->input['Post'] );
		
		//-----------------------------------------
		// Post process the editor
		// Now we have safe HTML and bbcode
		//-----------------------------------------
		
		$this->ipsclass->input['Post'] = $this->class->han_editor->process_raw_post( 'Post' );
		
		//-----------------------------------------
		// Parse post
		//-----------------------------------------
		
		$this->class->parser->parse_smilies     = 0;
		$this->class->parser->parse_html        = intval($this->ipsclass->vars['sig_allow_html']);
		$this->class->parser->parse_bbcode      = intval($this->ipsclass->vars['sig_allow_ibc']);
		$this->class->parser->parsing_signature = 1;

		$this->ipsclass->input['Post']          = $this->class->parser->bad_words( $this->class->parser->pre_display_parse( $this->class->parser->pre_db_parse( $this->ipsclass->input['Post'] ) ) );
		
		if ($this->class->parser->error != "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => $this->class->parser->error) );
		}
		
		//-----------------------------------------
		// Write it to the DB.
		//-----------------------------------------
		
		if ( $mem = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'id', 'from' => 'member_extra', 'where' => 'id='.$this->ipsclass->member['id'] ) ) )
		{
			$this->ipsclass->DB->do_update( 'member_extra', array( 'signature' => $this->ipsclass->input['Post'] ), 'id='.$this->ipsclass->member['id'] );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'member_extra', array( 'id' => $this->ipsclass->member['id'], 'signature' => $this->ipsclass->input['Post'] ) );
		}
		
		
		//-----------------------------------------
		// Member sync?
		//-----------------------------------------
		
		if ( USE_MODULES == 1 )
 		{
  			$this->class->modules->register_class($this);
     		$this->class->modules->on_signature_update($this->ipsclass->member, $this->ipsclass->input['Post']);
    	}
		
		//-----------------------------------------
		// Buh BYE:
		//-----------------------------------------
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."act=UserCP&CODE=22");
	}
	
	/*-------------------------------------------------------------------------*/
	// ADD IGNORE USERS
	/*-------------------------------------------------------------------------*/
	
	function ignore_user_add()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$temp_users       = array();
		$cant_find        = array();
		$lookup_meh_pants = array();
		
		//-----------------------------------------
 		// Stored as userid,userid,userid
 		//-----------------------------------------
 		
 		$ignored_users = explode( ',', $this->ipsclass->member['ignored_users'] );
 		
 		foreach( $ignored_users as $id )
 		{
 			if ( intval($id) )
 			{
 				$temp_users[] = $id;
 			}
 		}
 	
 		$final_string = ",".implode( ',', $temp_users ).",";
 		
 		$final_string = preg_replace( "/,{2,}/", ",", str_replace( " ", "", $final_string ) );
 		
 		//-----------------------------------------
 		// Get new names
 		//-----------------------------------------
 		
 		if ( $this->ipsclass->input['newbox_1'] )
 		{
 			$lookup_meh_pants[] = "'".strtolower(str_replace( '|', '&#124;', $this->ipsclass->input['newbox_1']))."'";
 		}
 		if ( $this->ipsclass->input['newbox_2'] )
 		{
 			$lookup_meh_pants[] = "'".strtolower(str_replace( '|', '&#124;', $this->ipsclass->input['newbox_2']))."'";
 		}
 		if ( $this->ipsclass->input['newbox_3'] )
 		{
 			$lookup_meh_pants[] = "'".strtolower(str_replace( '|', '&#124;', $this->ipsclass->input['newbox_3']))."'";
 		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count($lookup_meh_pants) )
		{
			//-----------------------------------------
			// See if we have any MEMBRES IN THE DB
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, members_l_display_name, mgroup',
														  'from'   => 'members',
														  'where'  => "members_l_display_name IN (".implode(",", $lookup_meh_pants ).")" ) );
			$this->ipsclass->DB->simple_exec();
			
			while( $s = $this->ipsclass->DB->fetch_row() )
			{
				if ( strstr( $this->ipsclass->vars['cannot_ignore_groups'], ','.$s['mgroup'].',' ) )
				{
					continue;
				}
				
				if ( strstr( $final_string, ','.$s['id'].',') )
				{
					continue;
				}
					
				if ( $s['id'] != $this->ipsclass->member['id'] )
				{
					$members[ $s['members_l_display_name'] ] = $s['id'];
				}
			}
			
			if ( count($members ) )
			{
				foreach( $members as $name => $id )
				{
					foreach( array( 1,2,3 ) as $hehe )
					{
						if ( strtolower($name) == strtolower($this->ipsclass->input[ 'newbox_'.$hehe ]) )
						{
							$this->ipsclass->input[ 'newbox_'.$hehe ] = "";
						}
					}
					
					$final_string .= $id.",";
				}
				
				$this->ipsclass->DB->do_update( 'members', array( 'ignored_users' => $final_string ), 'id='.$this->ipsclass->member['id'] );
			}
			
			//-----------------------------------------
			// Boxes not empty? Must be people!
			//-----------------------------------------
			
			foreach( array( 1,2,3 ) as $hehe )
			{
				if ( $this->ipsclass->input[ 'newbox_'.$hehe ] != "" )
				{
					$cant_find[] = $this->ipsclass->input[ 'newbox_'.$hehe ];
				}
			}
			
			if ( count($cant_find) )
			{
				$this->ipsclass->member['ignored_users'] = $final_string;
				
				$this->class->ignore_user_splash( sprintf( $this->ipsclass->lang['mi5_cantfind'], implode( ",", $cant_find ) ) );
				return;
			}
		}
		
		$this->ipsclass->member['ignored_users'] = $final_string;
		
		$this->class->ignore_user_splash();
		return;
	}
	
	
	function ignore_user_remove()
	{
		$temp_users = array();
		
		//-----------------------------------------
 		// Stored as userid,userid,userid
 		//-----------------------------------------
 		
 		$ignored_users = explode( ',', $this->ipsclass->member['ignored_users'] );
 		
 		foreach( $ignored_users as $id )
 		{
 			if ( intval($id) and ( $id != $this->ipsclass->input['id'] ) )
 			{
 				$temp_users[] = $id;
 			}
 		}
 		
 		$final_string = ",".implode( ',', $temp_users ).",";
 		
 		$final_string = preg_replace( "/,{2,}/", ",", str_replace( " ", "", $final_string ) );
 		
 		$this->ipsclass->DB->do_update( 'members', array( 'ignored_users' => $final_string ), 'id='.$this->ipsclass->member['id'] );
 		
 		$this->ipsclass->member['ignored_users'] = $final_string;
 		
		$this->class->ignore_user_splash();
		return true;
		
	}
}



?>