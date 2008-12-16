<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Dynamic v1.0.0
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER DYNAMIC IS NOT FREE SOFTWARE!
|   http://www.invisionpower.com/dynamic/
+---------------------------------------------------------------------------
|   > $Id$
|   > $Revision: 4 $
|   > $Date: 2005-10-10 14:21:32 +0100 (Mon, 10 Oct 2005) $
+---------------------------------------------------------------------------
|
|   > ATTACH FUNCTIONS
|   > Script written by Matt Mecham
|   > Date started: Monday 19th December 2005, 13:53
|
+---------------------------------------------------------------------------
*/

/**
* Attach Class
* Handles various uploading functions
*
* <code>
* Possible Error strings:
* - upload_no_file		 (No file was selected to upload)
* - upload_failed        (Upload failed for unspecified reason)
* - upload_too_big       (Upload is bigger than space left)
* - invalid_mime_type    (Upload is not allowed)
* - no_upload_dir        (Upload dir is not installed)
* - no_upload_dir_perms  (Upload dir is not writeable)
* </code>
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_attach
{
	/**
	* Global ipsclass
	* @var	object
	*/
	var $ipsclass;
	
	/**
	* Global html class
	* @var	object
	*/
	var $html;
	
	/**
	* Plugin Class
	* @var object
	*/
	var $type    = '';
	
	/**
	* Plugin Class
	* @var object
	*/
	var $plugin  = '';
	
	/**
	* Post key
	* @var string
	*/
	var $attach_post_key = '';
	
	/**
	* Relationship ID
	* @var string
	*/
	var $attach_rel_id   = '';
	
	/**
	* Return variables
	* @var array 	[ 'allow_uploads', 'space_allowed', 'space_allowed_human', 'space_used', 'space_used_human', 'space_left', 'space_left_human' ]
	*/
	var $attach_stats = array();
	
	/**
	* Lang array
	* Internal language array
	* @var array
	*/
	var $language    = array( 'unlimited'   => 'Unlimited',
	 						  'not_allowed' => 'Uploading is not allowed' );
	
	/**
	* Error array
	* @var string
	*/
	var $error = "";
	
	/**
	* Full upload path
	*/
	var $upload_path = '';
	
	/**
	* Upload part part (from /uploads)
	*/
	var $upload_dir  = '';
	
	/**
	* Extra upload form url
	*/
	var $extra_upload_form_url = '';
	
	/**
	* Custom settings
	*/
	var $settings = array( 'siu_thumb'                 => 0,
						   'siu_height'                => 0,
						   'siu_width'                 => 0,
						   'allow_monthly_upload_dirs' => 0,
						   'upload_dir'                => '' );
		
		
		
	
	/*-------------------------------------------------------------------------*/
	// INIT
	/*-------------------------------------------------------------------------*/
	/**
	* Initiates class
	*/
	function init()
	{
		//-----------------------------------------
		// Start the settings
		//-----------------------------------------
		
		$this->settings['siu_thumb'] 				 = $this->ipsclass->vars['siu_thumb'];
		$this->settings['siu_height'] 				 = $this->ipsclass->vars['siu_height'];
		$this->settings['siu_width'] 				 = $this->ipsclass->vars['siu_width'];
		$this->settings['allow_monthly_upload_dirs'] = SAFE_MODE_ON ? 0 : ( $this->ipsclass->vars['safe_mode_skins'] ? 0 : 1 );
		$this->settings['upload_dir'] 				 = $this->ipsclass->vars['upload_dir'];
		
		//-----------------------------------------
		// Load plug in
		//-----------------------------------------
		
		if ( $this->type )
		{
			$this->load_plugin();
		}
		
		//-----------------------------------------
		// Finalize the settings
		//-----------------------------------------
		
		foreach( $this->settings as $item => $value )
		{
			$this->settings[ $item ] = ( isset( $this->plugin->settings[ $item ] ) ) ? $this->plugin->settings[ $item ] : $value;
		}
		
		//-----------------------------------------
		// Got a different upload dir?
		//-----------------------------------------
		
		/*if ( $this->settings['upload_dir'] != $this->ipsclass->vars['upload_dir'] )
		{
			$this->settings['allow_monthly_upload_dirs'] = 0;
		}*/
		
		//-----------------------------------------
		// Fix up URL tokens
		//-----------------------------------------
		
		foreach( $this->ipsclass->input as $k => $v )
		{
			if ( preg_match( "#^--ff--#", $k ) )
			{
			 	$this->ipsclass->input[ str_replace( '--ff--', '', $k ) ] = $v;
			}
		}
		
		//-----------------------------------------
		// Sort out upload dir
		//-----------------------------------------
		
		$this->upload_path  = $this->settings['upload_dir'];
		
		# Preserve original path
		$this->_upload_path = $this->upload_path;
	}
	
	/*-------------------------------------------------------------------------*/
	// Show Attachment
	/*-------------------------------------------------------------------------*/
	/**
	* Show the attachment (or force download)
	*
	* @param	int		Attachment ID (The main attach id)
	* @return	void
	*/
	function show_attachment( $attach_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sql_data        = array();
		
		//-----------------------------------------
		// Get attach data...
		//-----------------------------------------
		
		$attachment = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		'from'   => 'attachments',
																		'where'  => 'attach_id='.intval( $attach_id ) ) );
																		
		if ( ! $attachment['attach_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
		}
	
		//-----------------------------------------
		// Load correct plug in...
		//-----------------------------------------
		
		$this->type = $attachment['attach_rel_module'];
		$this->load_plugin();
		
		//-----------------------------------------
		// Get SQL data from plugin
		//-----------------------------------------
		
		$attach = $this->plugin->show_attachment_get_sql_data( $attach_id );
		
		//-----------------------------------------
		// Got a reply?
		//-----------------------------------------
		
		if ( $attach === FALSE OR ! is_array( $attach ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}
		
		//-----------------------------------------
		// Got a rel id?
		//-----------------------------------------
		
		if ( ! $attach['attach_rel_id'] AND $attach['attach_member_id'] != $this->ipsclass->member['id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'err_attach_not_attached' ) );
		}
		
		if ( is_array( $attach ) AND count( $attach ) )
		{
			//-----------------------------------------
			// Got attachment types?
			//-----------------------------------------

			if ( ! is_array( $this->ipsclass->cache['attachtypes'] ) )
			{
				$this->ipsclass->cache['attachtypes'] = array();

				$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype', 'from' => 'attachments_type' ) );
				$this->ipsclass->DB->simple_exec();

				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
				}
			}
			
			//-----------------------------------------
	        // Show attachment
	        //-----------------------------------------

			$this->_upload_path = ( isset( $this->plugin->settings[ 'upload_dir' ] )  ) ? $this->plugin->settings[ 'upload_dir' ] : $this->settings[ 'upload_dir' ];

	        $file = $this->_upload_path."/".$attach['attach_location'];

			if ( file_exists( $file ) and ( $this->ipsclass->cache['attachtypes'][ $attach['attach_ext'] ]['atype_mimetype'] != "" ) )
			{
				//-----------------------------------------
				// Update the "hits"..
				//-----------------------------------------

				$this->ipsclass->DB->build_and_exec_query( array( 'update' => 'attachments',
															      'set'    => "attach_hits=attach_hits+1",
															      'where'  => "attach_id=".$attach_id ) );

				//-----------------------------------------
				// Open and display the file..
				//-----------------------------------------
				
				header( "Content-Type: ".$this->ipsclass->cache['attachtypes'][ $attach['attach_ext'] ]['atype_mimetype'] );
				header( "Content-Disposition: inline; filename=\"".$attach['attach_file']."\"" );
				header( "Content-Length: ".(string)(filesize( $file ) ) );
				
				//print $contents;
				//readfile( $file );
        		@ob_end_clean();
        
				if( $fh = fopen( $file, 'rb' ) )
				{
		            while( !feof($fh) )
		            {
		                echo fread( $fh, 4096 );
		                flush();
		                @ob_flush();
		            }
            
            		@fclose( $fh );
        		}

				exit();
			}
			else
			{
				//-----------------------------------------
				// File does not exist..
				//-----------------------------------------

				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'missing_files' ) );
			}
		}
		else
		{
			//-----------------------------------------
			// No permission?
			//-----------------------------------------
			
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Render attachments
	/*-------------------------------------------------------------------------*/
	/**
	* Swaps the HTML for the nice attachments.
	*
	* @param	array Array of HTML blocks to convert: [ rel_id => $html ]
	* @return	array Array of converted HTML blocks and attach code: [ id => array[ html => '', attach_html => '' ] ]
	*/
	function render_attachments( $html, $rel_ids=array(), $skin_name='skin_topic' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$attach_ids              = array();
		$map_attach_id_to_rel_id = array();
		$final_out               = array();
		$final_blocks            = array();
		$_seen                   = 0;
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! is_array( $rel_ids ) AND isset( $rel_ids ) )
		{
			$rel_ids = array( $rel_ids );
		}
		
		//-----------------------------------------
		// Parse HTML blocks for attach ids
		// [attachment=32:attachFail.gif]
		//-----------------------------------------
		
		preg_match_all( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", $html, $match );
			
		if ( is_array( $match[0] ) and count( $match[0] ) )
		{
			for ( $i = 0 ; $i < count( $match[0] ) ; $i++ )
			{
				if ( intval($match[1][$i]) == $match[1][$i] )
				{
					$attach_ids[ $match[1][$i] ] = $match[1][$i];
				}
			}
		}
		
		//-----------------------------------------
		// Get data from the plug in
		//-----------------------------------------
		
		$rows = $this->plugin->render_attachment_get_sql_data( $attach_ids, $rel_ids, $this->attach_post_key );
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( is_array( $rows ) AND count( $rows ) )
		{
			//-----------------------------------------
			// Got attachment types?
			//-----------------------------------------

			if ( ! is_array( $this->ipsclass->cache['attachtypes'] ) )
			{
				$this->ipsclass->cache['attachtypes'] = array();

				$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_img', 'from' => 'attachments_type' ) );
				$outer = $this->ipsclass->DB->simple_exec();

				while ( $r = $this->ipsclass->DB->fetch_row( $outer ) )
				{
					$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
				}
			}
			
			$_seen_rows = 0;
			
			//preg_match_all( "#\[attachment=(\d+)\:(?:[^\]]+?)[\n|\]]#is", $html, $matches );
		
			foreach( $rows as $_attach_id => $row )
			//foreach( $matches[1] as $html_attachid )
			{
				//-----------------------------------------
				// INIT
				//-----------------------------------------
				
				//$_attach_id = $html_attachid;
				$row		= $rows[ $_attach_id ];
				
				if( $this->attach_rel_id != $row['attach_rel_id'] )
				{
					// Reset if we are onto a new post..
					$_seen_rows = 0;
				}

				$this->attach_rel_id = $row['attach_rel_id'];
				
				if ( ! isset( $final_blocks[ $row['attach_rel_id'] ] ) )
				{
					$final_blocks[ $row['attach_rel_id'] ] = array( 'attach' => '', 'thumb' => '', 'image' => '' );
				}
				
				//-----------------------------------------
				// Is it an image, and are we viewing the image in the post?
				//-----------------------------------------
				
				if ( $this->ipsclass->vars['show_img_upload'] and $row['attach_is_image'] )
				{
					if ( $this->settings['siu_thumb'] AND $row['attach_thumb_location'] AND $row['attach_thumb_width'] )
					{
						//-----------------------------------------
						// Make sure we've not seen this ID
						//-----------------------------------------
						
						$row['_attach_id'] = $row['attach_id'] . '-' . preg_replace( "#[\.\s]#", "-", microtime() );
						
						$not_inline = $_seen_rows > 0 ? $_seen_rows%$this->ipsclass->vars['topic_attach_no_per_row'] : 1;
						
						$tmp = $this->ipsclass->compiled_templates[ $skin_name ]->Show_attachments_img_thumb( array( 't_location'  => $row['attach_thumb_location'],
																											  		 't_width'     => $row['attach_thumb_width'],
																											  		 't_height'    => $row['attach_thumb_height'],
																											         'o_width'     => $row['attach_img_width'],
																											  		 'o_height'    => $row['attach_img_height'],
																											  	     'attach_id'   => $row['attach_id'],
																													 '_attach_id'  => $row['_attach_id'],
																											    	 'file_size'   => $this->ipsclass->size_format( $row['attach_filesize'] ),
																											  		 'attach_hits' => $row['attach_hits'],
																											  		 'location'    => $row['attach_file'],
																													 'type'        => $this->type,
																													 'notinline'   => $not_inline,
																										)	);
						
						//-----------------------------------------
						// Convert HTML
						//-----------------------------------------
						
						$_count = substr_count( $html, '[attachment='.$row['attach_id'].':' );
						
						if ( $_count > 1 )
						{
							# More than 1 of the same thumbnail to show?
							$this->_current = array( 'type'      => $this->type,
													 'row'       => $row,
													 'skin_name' => $skin_name );
													
							$html = preg_replace_callback( "#\[attachment=".$row['attach_id']."\:(?:[^\]]+?)[\n|\]]#is", array( &$this, '_parse_thumbnail_inline' ), $html );
						}
						else if ( $_count )
						{
							# Just the one, then?
							$html = preg_replace( "#\[attachment=".$row['attach_id']."\:(?:[^\]]+?)[\n|\]]#is", $tmp, $html );
						}
						else
						{
							# None. :(
							$_seen++;
							
							if ( $_seen == $this->ipsclass->vars['topic_attach_no_per_row'] )
							{
								$tmp .= "\n<br />\n";
								$_seen = 0;
							}
								
							$final_blocks[ $row['attach_rel_id'] ]['thumb'] .= $tmp . ' ';
						}
					}
					else
					{
						//-----------------------------------------
						// Standard size..
						//-----------------------------------------
						
						$tmp = $this->ipsclass->compiled_templates[ $skin_name ]->Show_attachments_img( $row['attach_location'] );
															  
						if ( strstr( $html, '[attachment='.$row['attach_id'].':' ) )
						{
							$html = preg_replace( "#\[attachment=".$row['attach_id']."\:(?:[^\]]+?)[\n|\]]#is", $tmp, $html );
						}
						else
						{
							$final_blocks[ $row['attach_rel_id'] ]['image'] .= $tmp . ' ';
						}
					}
				}
				else
				{
					//-----------------------------------------
					// Full attachment thingy
					//-----------------------------------------
					
					$tmp = $this->ipsclass->compiled_templates[ $skin_name ]->Show_attachments( array (
																										'attach_hits'  => $row['attach_hits'],
																										'mime_image'   => $this->ipsclass->cache['attachtypes'][ $row['attach_ext'] ]['atype_img'],
																										'attach_file'  => $row['attach_file'],
																										'attach_id'    => $row['attach_id'],
																										'type'         => $this->type,
																										'file_size'    => $this->ipsclass->size_format( $row['attach_filesize'] ),
																							  )  	  );
																	  
					if ( strstr( $html, '[attachment='.$row['attach_id'].':' ) )
					{
						$html = preg_replace( "#\[attachment=".$row['attach_id']."\:(?:[^\]]+?)[\n|\]]#is", $tmp, $html );
					}
					else
					{
						$final_blocks[ $row['attach_rel_id'] ]['attach'] .= $tmp . ' ';
					}
				}
				
				$_seen_rows++;
			}
			
			//-----------------------------------------
			// Anthing to add?
			//-----------------------------------------
			
			if ( count( $final_blocks ) )
			{
				foreach( $final_blocks as $rel_id => $type )
				{
					$temp_out = "";
					
					if ( $final_blocks[ $rel_id ]['thumb'] )
					{
						$temp_out .= $this->ipsclass->compiled_templates[ $skin_name ]->show_attachment_title( $this->ipsclass->lang['attach_thumbs'], $final_blocks[ $rel_id ]['thumb'] );
					}

					if ( $final_blocks[ $rel_id ]['image'] )
					{
						$temp_out .= $this->ipsclass->compiled_templates[ $skin_name ]->show_attachment_title( $this->ipsclass->lang['attach_images'], $final_blocks[ $rel_id ]['image'] );
					}

					if ( $final_blocks[ $rel_id ]['attach'] )
					{
						$temp_out .= $this->ipsclass->compiled_templates[ $skin_name ]->show_attachment_title( $this->ipsclass->lang['attach_normal'], $final_blocks[ $rel_id ]['attach'] );
					}
					
					if ( $temp_out )
					{
						$html = str_replace( "<!--IBF.ATTACHMENT_". $rel_id. "-->", $temp_out, $html );
					}
				}
			}
		}
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove uploaded file (BULK)
	/*-------------------------------------------------------------------------*/
	/**
	* Removes an attachment.
	*/
	function bulk_remove_attachment( $attach_rel_ids=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$attachments = array();
		
		//-----------------------------------------
		// Got an ID?
		//-----------------------------------------
		
		if ( ! is_array( $attach_rel_ids ) or ! count( $attach_rel_ids ) )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Make sure we've got permission
		//-----------------------------------------
		
		if ( $this->plugin->return_bulk_remove_permission( $attach_rel_ids ) === TRUE )
		{
			//-----------------------------------------
			// Get stuff
			//-----------------------------------------

			$this->ipsclass->DB->build_query( array( 'select' => '*',
													 'from'   => 'attachments',
													 'where'  => 'attach_rel_id IN ('.implode(",",$attach_rel_ids).") AND attach_rel_module='".$this->type."'" ) );
			
			$this->ipsclass->DB->exec_query();
			
			while( $_row = $this->ipsclass->DB->fetch_row() )
			{
				$attachments[ $_row['attach_id'] ] = $_row;
			}
			
			foreach( $attachments as $attach_id => $attachment )
			{
				//-----------------------------------------
				// Remove from the filesystem
				//-----------------------------------------
			
				if ( $attachment['attach_location'] )
				{
					@unlink( $this->_upload_path."/".$attachment['attach_location'] );
				}
				if ( $attachment['attach_thumb_location'] )
				{
					@unlink( $this->_upload_path."/".$attachment['attach_thumb_location'] );
				}
			
				//-----------------------------------------
				// Allow the module to clean up any items
				//-----------------------------------------
															
				$this->plugin->remove_attachment_clean_up( $attachment );
			}
			
			//-----------------------------------------
			// Remove from the DB
			//-----------------------------------------
		
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'attachments',
															  'where'  => 'attach_rel_id IN ('.implode(",",$attach_rel_ids).") AND attach_rel_module='".$this->type."'" ) );
			
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove uploaded file
	/*-------------------------------------------------------------------------*/
	/**
	* Removes an attachment.
	*/
	function remove_attachment()
	{
		//-----------------------------------------
		// Got an ID?
		//-----------------------------------------
		
		if ( ! $this->attach_id )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Get DB row
		//-----------------------------------------
		
		$attachment = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		'from'   => 'attachments',
																		'where'  => 'attach_id='.$this->attach_id." AND attach_rel_module='".$this->type."'" ) );
																		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Make sure we've got permission
		//-----------------------------------------
		
		if ( $this->plugin->return_remove_permission( $attachment ) === TRUE )
		{
			//-----------------------------------------
			// Remove from the DB
			//-----------------------------------------
			
			$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'attachments',
															  'where'  => 'attach_id='.$attachment['attach_id'] ) );
			
			//-----------------------------------------
			// Remove from the filesystem
			//-----------------------------------------
			
			if ( $attachment['attach_location'] )
			{
				@unlink( $this->_upload_path."/".$attachment['attach_location'] );
			}
			if ( $attachment['attach_thumb_location'] )
			{
				@unlink( $this->_upload_path."/".$attachment['attach_thumb_location'] );
			}
			
			//-----------------------------------------
			// Allow the module to clean up any items
			//-----------------------------------------
															
			$this->plugin->remove_attachment_clean_up( $attachment );
			
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Post Process upload
	/*-------------------------------------------------------------------------*/
	/**
	* Converts post-key attachments into rel_id / rel_module attachments
	* by adding in the correct ID, etc
	*/
	function post_process_upload( $args=array() )
	{
		if ( ! $this->attach_post_key or ! $this->attach_rel_id )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Got any to update?
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'attachments', array( 'attach_rel_id'     => $this->attach_rel_id,
			  										 		  'attach_rel_module' => $this->type ), "attach_post_key='". $this->attach_post_key ."'" );
			
		//-----------------------------------------
		// Update module specific?
		//-----------------------------------------
		
		return $this->plugin->post_process_upload( $this->attach_post_key, $this->attach_rel_id, $args );
	}
	
	/*-------------------------------------------------------------------------*/
	// Process upload
	/*-------------------------------------------------------------------------*/
	/**
	* Uploads and saves file
	*/
	function process_upload()
	{
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->error = '';
		
		$this->get_upload_form_settings();
		
		//-----------------------------------------
		// Check upload dir
		//-----------------------------------------
		
		if ( ! $this->check_upload_dir() )
		{
			if ( $this->error )
			{
				return;
			}
		}

		//-----------------------------------------
		// Got attachment types?
		//-----------------------------------------
		
		if ( ! isset( $this->ipsclass->cache['attachtypes'] ) OR ! is_array( $this->ipsclass->cache['attachtypes'] ) )
		{
			$this->ipsclass->cache['attachtypes'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img',
														  'from'   => 'attachments_type',
														  'where'  => "atype_photo=1 OR atype_post=1" ) );
			$this->ipsclass->DB->simple_exec();
	
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
			}
		}
		
		//-----------------------------------------
		// Can upload?
		//-----------------------------------------
		
		if ( ! $this->attach_stats['allow_uploads'] )
		{
			$this->error = 'upload_too_big';
			return;
		}
		
		//-----------------------------------------
		// Set up array
		//-----------------------------------------
		
		$attach_data = array( 
							  'attach_ext'            => "",
							  'attach_file'           => "",
							  'attach_location'       => "",
							  'attach_thumb_location' => "",
							  'attach_hits'           => 0,
							  'attach_date'           => time(),
							  'attach_temp'           => 0,
							  'attach_post_key'       => $this->attach_post_key,
							  'attach_member_id'      => $this->ipsclass->member['id'],
							  'attach_rel_id'         => $this->attach_rel_id,
							  'attach_rel_module'     => $this->type,
							  'attach_filesize'       => 0,
							);
		
		//-----------------------------------------
		// Load the library
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_upload.php' );
		$upload = new class_upload();
		
		//-----------------------------------------
		// Set up the variables
		//-----------------------------------------
		
		$upload->out_file_name    = $this->type.'-'.$this->ipsclass->member['id'].'-'.time();
		$upload->out_file_dir     = $this->upload_path;
		$upload->max_file_size    = $this->attach_stats['max_single_upload'] ? $this->attach_stats['max_single_upload'] : 1000000000;
		$upload->make_script_safe = 1;
		$upload->force_data_ext   = 'ipb';
		
		//-----------------------------------------
		// Populate allowed extensions
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['attachtypes'] ) and count( $this->ipsclass->cache['attachtypes'] ) )
		{
			foreach( $this->ipsclass->cache['attachtypes'] as $idx => $data )
			{
				if ( $data['atype_post'] )
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
			switch( $upload->error_no )
			{
				case 1:
					// No upload
					$this->error = 'upload_no_file';
					return $attach_data;
					break;
				case 2:
					// Invalid file ext
					$this->error = 'invalid_mime_type';
					return $attach_data;
					break;
				case 3:
					// Too big...
					$this->error = 'upload_too_big';
					return $attach_data;
					break;
				case 4:
					// Cannot move uploaded file
					$this->error = 'upload_failed';
					return $attach_data;
					break;
				case 5:
					// Possible XSS attack (image isn't an image)
					$this->error = 'upload_failed';
					return $attach_data;
					break;
			}
		}
					
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		if ( $upload->saved_upload_name and @file_exists( $upload->saved_upload_name ) )
		{
			//-----------------------------------------
			// Strip off { } and [ ]
			//-----------------------------------------
			
			$upload->original_file_name = preg_replace( "#[\[\]\{\}]#", "", $upload->original_file_name );
			
			$attach_data['attach_filesize']   = @filesize( $upload->saved_upload_name  );
			$attach_data['attach_location']   = $this->upload_dir . $upload->parsed_file_name;
			$attach_data['attach_file']       = $upload->original_file_name;
			$attach_data['attach_is_image']   = $upload->is_image;
			$attach_data['attach_ext']        = $upload->real_file_extension;
			
			if ( $attach_data['attach_is_image'] == 1 )
			{
				require_once( KERNEL_PATH.'class_image.php' );
				$image = new class_image();

				$image->in_type        = 'file';
				$image->out_type       = 'file';
				$image->in_file_dir    = $this->upload_path;
				$image->in_file_name   = $upload->parsed_file_name;
				$image->desired_width  = $this->settings['siu_width'];
				$image->desired_height = $this->settings['siu_height'];
				$image->gd_version     = $this->ipsclass->vars['gd_version'];

				if ( $this->settings['siu_thumb'] )
				{
					$thumb_data = $image->generate_thumbnail();
				}
				
				if ( $thumb_data['thumb_location'] )
				{
					$attach_data['attach_img_width']      = $thumb_data['original_width'];
					$attach_data['attach_img_height']     = $thumb_data['original_height'];
					$attach_data['attach_thumb_width']    = $thumb_data['thumb_width'];
					$attach_data['attach_thumb_height']   = $thumb_data['thumb_height'];
					$attach_data['attach_thumb_location'] = $this->upload_dir . $thumb_data['thumb_location'];
				}
			}
			
			//-----------------------------------------
			// Add into Database
			//-----------------------------------------
			
			$this->ipsclass->DB->do_insert( 'attachments', $attach_data );
			
			$newid = $this->ipsclass->DB->get_insert_id();
			
			return $newid;
		}	
	}
	
	/*-------------------------------------------------------------------------*/
	// Upload form stuff
	/*-------------------------------------------------------------------------*/
	/**
	* Gets stuff required for the upload form
	*/
	function get_upload_form_settings()
	{
		//-----------------------------------------
		// Collect settings from the plug-in
		//-----------------------------------------
		
		$stats = $this->plugin->get_space_allowance( $this->attach_post_key );

		//-----------------------------------------
		// Format and return...
		//-----------------------------------------
		
		$this->attach_stats['space_used']                = $stats['space_used'];
		$this->attach_stats['space_used_human']          = $this->ipsclass->size_format( $stats['space_used'] );
		$this->attach_stats['total_space_allowed']       = $stats['total_space_allowed'] ? $stats['total_space_allowed'] : $stats['max_single_upload'];
		$this->attach_stats['max_single_upload']         = $stats['max_single_upload'];
		$this->attach_stats['max_single_upload_human']   = $this->attach_stats['max_single_upload']   ? $this->ipsclass->size_format( $stats['max_single_upload'] )   : $this->language[ 'unlimited' ];
		$this->attach_stats['total_space_allowed_human'] = $this->attach_stats['total_space_allowed'] ? $this->ipsclass->size_format( $this->attach_stats['total_space_allowed'] ) : $this->language[ 'unlimited' ];
		
		if ( $stats['space_allowed'] == 0 )
		{
			//-----------------------------------------
			// Unlimited...
			//-----------------------------------------
			
			$this->attach_stats['allow_uploads']             = 1;
			$this->attach_stats['space_allowed']             = 'unlimited';
			$this->attach_stats['space_allowed_human']       = $this->language[ 'unlimited' ];
			$this->attach_stats['space_left']                = 'unlimited';
			$this->attach_stats['space_left_human']          = $this->language[ 'unlimited' ];
			$this->attach_stats['total_space_allowed_human'] = $this->language[ 'unlimited' ];
			//$this->attach_stats['space_used_human']          = $this->language[ 'unlimited' ];
		}
		else if ( $stats['space_allowed'] == -1 )
		{
			//-----------------------------------------
			// None
			//-----------------------------------------
			
			$this->attach_stats['allow_uploads']       = 0;
			$this->attach_stats['space_allowed']       = 'not_allowed';
			$this->attach_stats['space_allowed_human'] = $this->language[ 'not_allowed' ];
			$this->attach_stats['space_left']          = 'not_allowed';
			$this->attach_stats['space_left_human']    = $this->language[ 'not_allowed' ];
		}
		else
		{
			//-----------------------------------------
			// Set figure
			//-----------------------------------------
			
			$this->attach_stats['allow_uploads']       = 1;
			$this->attach_stats['space_left']          = $stats['space_left'];
			$this->attach_stats['space_left_human']    = $this->ipsclass->size_format( $stats['space_left'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Check uploads dir
	/*-------------------------------------------------------------------------*/
	/**
	* Checks the upload dir
	* See above. It's not rocket science
	*
	* @return void
	*/
	function check_upload_dir()
	{
		//-----------------------------------------
		// Check dir exists...
		//-----------------------------------------
		
		if ( ! file_exists( $this->upload_path ) )
		{
			if ( @mkdir( $this->upload_path, 0777 ) )
			{
				@chmod( $this->upload_path, 0777 );
			}
			else
			{
				$this->error = "no_upload_dir";
				return false;
			}
		}
		else if ( ! is_writeable( $this->upload_path ) )
		{
			$this->error = "no_upload_dir_perms";
			return false;
		}
		
		//-----------------------------------------
		// Try and create a new monthly dir
		// eg: monthly_12_2005
		//-----------------------------------------
		
		$this_month = "monthly_" . gmdate( "m_Y", time() );
		
		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( $this->settings['allow_monthly_upload_dirs'] )
		{
			$path = $this->upload_path . "/" . $this_month;
			if ( ! file_exists( $path ) )
			{
				if ( @mkdir( $path, 0777 ) )
				{
					@chmod( $path, 0777 );
				
					# Set path and dir correct
					$this->upload_path .= "/" . $this_month;
					$this->upload_dir   = $this_month."/";
				}
				
				//-----------------------------------------
				// Was it really made or was it lying?
				//-----------------------------------------
				
				if ( ! file_exists( $path ) )
				{
					$this->upload_path = $this->_upload_path;
					$this->upload_dir  = '/';
				}
			}
			else
			{
				# Set path and dir correct
				$this->upload_path .= "/" . $this_month;
				$this->upload_dir   = $this_month."/";
			}
		}
		
		return true;
	}
	
	/*-------------------------------------------------------------------------*/
	// Load plug-in class
	/*-------------------------------------------------------------------------*/
	/**
	* Loads child extends class.
	*/
	function load_plugin()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->type = $this->ipsclass->txt_alphanumerical_clean( $this->type );
		
		//-----------------------------------------
		// Load...
		//-----------------------------------------
		
		$file  = ROOT_PATH . 'sources/classes/attach/plugin_'.$this->type.'.php';
		$class = 'plugin_'.$this->type;
		
		if ( ! is_object( $this->plugin ) )
		{
			if ( file_exists( $file ) )
			{
				require_once( $file );
				$this->plugin           =  new $class;
				$this->plugin->ipsclass =& $this->ipsclass;
				$this->plugin->get_settings();
			}
			else
			{
				print "Could not locate $file";
				exit();
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse thumbnail attachments inline
	/*-------------------------------------------------------------------------*/
	/**
	* Swaps the HTML for the nice attachments.
	*
	* @param	array  Array of matches from preg_replace_callback
	* @return	string HTML
	*/
	function _parse_thumbnail_inline( $matches )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$row       = $this->_current['row'];
		$skin_name = $this->_current['skin_name'];
		
		//-----------------------------------------
		// Generate random ID
		//-----------------------------------------
		
		$row['_attach_id'] = $row['attach_id'] . '-' . preg_replace( "#[\.\s]#", "-", microtime() );
		
		//-----------------------------------------
		// Build HTML
		//-----------------------------------------
		
		$tmp = $this->ipsclass->compiled_templates[ $skin_name ]->Show_attachments_img_thumb( array( 't_location'  => $row['attach_thumb_location'],
																							  		 't_width'     => $row['attach_thumb_width'],
																							  		 't_height'    => $row['attach_thumb_height'],
																							         'o_width'     => $row['attach_img_width'],
																							  		 'o_height'    => $row['attach_img_height'],
																							  	     'attach_id'   => $row['attach_id'],
																									 '_attach_id'  => $row['_attach_id'],
																							    	 'file_size'   => $this->ipsclass->size_format( $row['attach_filesize'] ),
																							  		 'attach_hits' => $row['attach_hits'],
																							  		 'location'    => $row['attach_file'],
																									 'type'        => $this->_current['type'],
																						)	);
		
		return $tmp;
	}
	
}

?>