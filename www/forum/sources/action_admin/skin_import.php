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
|   > $Date: 2007-08-21 17:48:41 -0400 (Tue, 21 Aug 2007) $
|   > $Revision: 1099 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Import functions
|   > Module written by Matt Mecham
|   > Date started: 22nd April 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class ad_skin_import {

	var $base_url;
	
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
	var $perm_child = "import";

	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Skin Import/Export' );
		
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'export':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->do_export();
				break;
				
			case 'exportimages':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->do_export_images();
				break;
				
			case 'importtemplates':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->import_xml_templates();
				break;
			
			case 'importimages':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->import_xml_images();
				break;
				
			case 'importmacros':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->import_xml_macros();
				break;
				
			case 'exportmacros':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->export_xml_macros();
				break;				
				
			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->show_export_page();
				break;
		}
		
	}
	
	//-----------------------------------------
	// PERFORM IMPORT IMAGES
	//-----------------------------------------
	
	function import_xml_images()
	{
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['skin_location'] )
			{
				$this->ipsclass->main_msg = "No upload file was found and no filename was specified.";
				$this->show_export_page();
			}
			
			if ( ! file_exists( ROOT_PATH . $this->ipsclass->input['skin_location'] ) )
			{
				$this->ipsclass->main_msg = "Could not find the file to open at: " . ROOT_PATH . $this->ipsclass->input['skin_location'];
				$this->show_export_page();
			}
			
			if ( preg_match( "#\.gz$#", $this->ipsclass->input['skin_location'] ) )
			{
				if ( $FH = @gzopen( ROOT_PATH.$this->ipsclass->input['skin_location'], 'rb' ) )
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
				if ( $FH = @fopen( ROOT_PATH.$this->ipsclass->input['skin_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize(ROOT_PATH.$this->ipsclass->input['skin_location']) );
					@fclose( $FH );
				}
			}
			
			$tmp_name = str_replace( ".gz", '', $this->ipsclass->input['skin_location'] );
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content = $this->ipsclass->admin->import_xml( $tmp_name );
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		require_once( KERNEL_PATH.'class_xmlarchive.php' );

		$xmlarchive = new class_xmlarchive( KERNEL_PATH );
		
		$xmlarchive->xml_read_archive_data( $content );
		
		//-----------------------------------------
		// Set up.. 
		//-----------------------------------------
		
		$safename = $this->ipsclass->input['skin_name'] ? $this->ipsclass->input['skin_name'] : preg_replace( "#ipb_images-(.+?)\.xml#i", "\\1", $tmp_name );
		$safename = substr( str_replace( " ", "", strtolower( preg_replace( "[^a-zA-Z0-9]", "", $safename ) ) ), 0, 10 );
		$images   = array();
		
		foreach( $xmlarchive->file_array as $f )
		{
			if ( $f['content'] and $f['filename'] )
			{
				$images[] = array( 'content'  => $f['content'],
								   'path'     => $f['path'],
								   'filename' => $f['filename']
								 );
			}
		}
		
		//-----------------------------------------
		// Got owt?
		//-----------------------------------------
		
		if ( ! count($images) )
		{
			$this->ipsclass->main_msg = "There were no images to import from that XMLarchive.";
			$this->show_export_page();
		}
		
		//-----------------------------------------
		// Reet- test to see if we
		// can create dirs
		//-----------------------------------------
		
		if ( ! is_writable( CACHE_PATH.'style_images' ) )
		{
			$this->ipsclass->main_msg = 'We cannot create a new folder in the "style_images" folder - please check the CHMOD value of that folder and change to 0777 is required.';
			$this->show_export_page();
		}
		
		//-----------------------------------------
		// Check to make sure we're not
		// creating a DUPE!
		//-----------------------------------------
		
		if ( file_exists( CACHE_PATH.'style_images/'.$safename ) )
		{
			$safename .= time();
		}
		
		//-----------------------------------------
		// Create
		//-----------------------------------------
		
		if ( ! @mkdir( CACHE_PATH.'style_images/'.$safename, 0777 ) )
		{
			$this->ipsclass->main_msg = "We are unable to create a directory in the 'style_images' folder.";
			$this->show_export_page();
		}
		else
		{
			@chmod( CACHE_PATH.'style_images/'.$safename, 0777 );
		}
		
		foreach( $images as $id => $data )
		{
			//-----------------------------------------
			// Do we have a duuuur?
			//-----------------------------------------
			
			if ( $data['path'] )
			{
				if ( ! file_exists( CACHE_PATH.'style_images/'.$safename.'/'.$data['path'] ) )
				{
					@mkdir( CACHE_PATH.'style_images/'.$safename.'/'.$data['path'], 0777 );
					@chmod( CACHE_PATH.'style_images/'.$safename.'/'.$data['path'], 0777 );
				}
				
				$data['filename'] = $data['path'] . '/'. $data['filename'];
			}
			
			$content = $data['content'];
			
			if ( $content )
			{
				if ( $FH = @fopen( CACHE_PATH.'style_images/'.$safename.'/'.$data['filename'], 'wb' ) )
				{
					if ( @fwrite( $FH, $content ) )
					{
						@fclose( $FH );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Apply to a skin?
		//-----------------------------------------
				
		if( $this->ipsclass->input['skin_set'] )
		{
			$skin_id = intval($this->ipsclass->input['skin_set']);

			$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_image_dir' => $safename ), 'set_skin_set_id='.$skin_id );
			
			#$this->ipsclass->cache_func->_rebuild_skin_id_cache( array() );
			
			$this->ipsclass->cache_func->_rebuild_all_caches( array($skin_id) );
		}
		
		//-----------------------------------------
		// all done?
		//-----------------------------------------
		
		$this->ipsclass->main_msg = "Image set imported!";
		$this->show_export_page();
	}
	
	//-----------------------------------------
	// PERFORM IMPORT TEMPLATES
	//-----------------------------------------
	
	function import_xml_templates()
	{
		//-----------------------------------------
		// Get default skin
		//-----------------------------------------
		
		$default = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_default=1' ) );
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['skin_location'] )
			{
				$this->ipsclass->main_msg = "No upload file was found and no filename was specified.";
				$this->show_export_page();
			}
			
			if ( ! file_exists( ROOT_PATH . $this->ipsclass->input['skin_location'] ) )
			{
				$this->ipsclass->main_msg = "Could not find the file to open at: " . ROOT_PATH . $this->ipsclass->input['skin_location'];
				$this->show_export_page();
			}
			
			if ( preg_match( "#\.gz$#", $this->ipsclass->input['skin_location'] ) )
			{
				if ( $FH = @gzopen( ROOT_PATH.$this->ipsclass->input['skin_location'], 'rb' ) )
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
				if ( $FH = @fopen( ROOT_PATH.$this->ipsclass->input['skin_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize(ROOT_PATH.$this->ipsclass->input['skin_location']) );
					@fclose( $FH );
				}
			}
			
			$tmp_name = str_replace( ".gz", '', $this->ipsclass->input['skin_location'] );
			
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content = $this->ipsclass->admin->import_xml( $tmp_name );
		}
		
		if( !$content )
		{
			$this->ipsclass->admin->error( "There was no content in the file to process" );
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];
		$xml->use_doctype = 1;		
		
		require_once( KERNEL_PATH.'class_xmlarchive.php' );

		$xmlarchive = new class_xmlarchive( KERNEL_PATH );
		
		$xmlarchive->xml_read_archive_data( $content );
		
		//-----------------------------------------
		// Get the XML documents
		//-----------------------------------------
		
		$import_xml = array();
		
		foreach( $xmlarchive->file_array as $f )
		{
			$import_xml[ $f['filename'] ] = $f['content'];
		}
		
		//-----------------------------------------
		// Import INFO
		//-----------------------------------------
		
		if ( $import_xml[ 'ipb_info.xml' ] != '' )
		{
			$info_xml = $this->_extract_xml_info( $import_xml[ 'ipb_info.xml' ], $xml, $xmlarchive );
		}
		
		if ( ! is_array( $info_xml ) and ! count( $info_xml ) )
		{
			$this->ipsclass->main_msg = "The XMLarchive import doesn't appear to be valid - please check the file and try again.";
			$this->show_export_page();
		}
		
		//-----------------------------------------
		// Import Templates
		//-----------------------------------------
		
		if ( $import_xml[ 'ipb_templates.xml' ] != '' )
		{
			$templates_xml = $this->_extract_xml_templates( $import_xml[ 'ipb_templates.xml' ], $xml, $xmlarchive );
		}
		
		//("content-type: text/plain"); print_r($templates_xml); exit();
		
		//-----------------------------------------
		// Import CSS
		//-----------------------------------------
		
		if ( $import_xml[ 'ipb_css.xml' ] != '' )
		{
			$css_xml = $this->_extract_xml_css( $import_xml[ 'ipb_css.xml' ], $xml, $xmlarchive );
		}

		//-----------------------------------------
		// Import Macro
		//-----------------------------------------
		
		if ( $import_xml[ 'ipb_macro.xml' ] != '' )
		{
			$macro_xml = $this->_extract_xml_macros( $import_xml[ 'ipb_macro.xml' ], $xml, $xmlarchive );
		}
		
		//-----------------------------------------
		// Import WRAPPER
		//-----------------------------------------
		
		if ( $import_xml[ 'ipb_wrapper.xml' ] != '' )
		{
			$wrapper_xml = $this->_extract_xml_wrapper( $import_xml[ 'ipb_wrapper.xml' ], $xml, $xmlarchive );
		}
		
		//-----------------------------------------
		// Which image directory to use?
		//-----------------------------------------
				
		if( $this->ipsclass->input['image_set'] )
		{
			$img_dir = $this->ipsclass->input['image_set'];
		}
		else
		{
			$img_dir = $default['set_image_dir'];
		}		
		
		//-----------------------------------------
		// Add new skin!
		//-----------------------------------------
		
		$this->ipsclass->DB->allow_sub_select = 1;
		
		$this->ipsclass->DB->do_insert( 'skin_sets', array( 'set_name'            => $this->ipsclass->input['skin_name'] ? $this->ipsclass->input['skin_name'] : $info_xml['set_name'].' (Import)',
															'set_hidden'          => 0,
															'set_default'         => 0,
															'set_css_method'      => $default['set_css_method'],
															'set_skin_set_parent' => -1,
															'set_author_email'    => $info_xml['set_author_email'],
															'set_author_name'     => $info_xml['set_author_name'],
															'set_author_url'      => $info_xml['set_author_url'],
															'set_key'      		  => $info_xml['set_key'],
															'set_css'             => $css_xml,
															'set_wrapper'         => $wrapper_xml,
															'set_css_updated'     => time(),
															'set_emoticon_folder' => $default['set_emoticon_folder'],
															'set_image_dir'       => $img_dir
									 )                   );
					 
		$new_skin_id = $this->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Insert templates...
		//-----------------------------------------
		
		if ( is_array( $templates_xml ) and count( $templates_xml ) )
		{
			foreach( $templates_xml as $t )
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_insert( 'skin_templates', array( 'set_id'          => $new_skin_id,
													     'group_name'           => $t['group_name'],
													     'section_content'       => $t['section_content'],
													     'func_name'             => $t['func_name'],
													     'func_data'             => $t['func_data'],
													     'group_names_secondary' => $t['group_names_secondary'],
													     'updated'               => time(),
													     'can_remove'            => 1,
							  )                         );
			}
		}
		
		//-----------------------------------------
		// Insert Macros
		//-----------------------------------------
		
		if ( is_array( $macro_xml ) and count( $macro_xml ) )
		{
			foreach( $macro_xml as $t )
			{
				if ( ! $t['macro_value'] and ! $t['macro_replace'] )
				{
					continue;
				}
				
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_insert( 'skin_macro', array( 'macro_set'        => $new_skin_id,
																	 'macro_value'      => $t['macro_value'],
																	 'macro_replace'    => $t['macro_replace'],
																	 'macro_can_remove' => 1,
											  )                    );
			}
		}
		
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------
		
		$this->ipsclass->cache_func->_rebuild_all_caches( array($new_skin_id) );
		
		//-----------------------------------------
		// DONE!
		//-----------------------------------------
		
		$this->ipsclass->main_msg = 'Skin Set Imported! (id: '.$new_skin_id.')';
			
		$this->ipsclass->main_msg .= "<br />".implode( "<br />", $this->ipsclass->cache_func->messages );
		
		$this->show_export_page();
	}
	
	
	//-----------------------------------------
	// PERFORM IMPORT TEMPLATES
	//-----------------------------------------
	
	function import_xml_macros()
	{
		$skin_id = intval($this->ipsclass->input['skin_set']);
		
		$skin = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$skin_id ) );
		
		$current  = $skin['set_skin_set_id'];
		$parent   = $skin['set_skin_set_parent'] > 0 ? $skin['set_skin_set_parent'] : 1;

		//-----------------------------------------
		// Get default skin
		//-----------------------------------------
		
		$default = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_default=1' ) );
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['skin_location'] )
			{
				$this->ipsclass->main_msg = "No upload file was found and no filename was specified.";
				$this->show_export_page();
				return;
			}
			
			if ( ! file_exists( ROOT_PATH . $this->ipsclass->input['skin_location'] ) )
			{
				$this->ipsclass->main_msg = "Could not find the file to open at: " . ROOT_PATH . $this->ipsclass->input['skin_location'];
				$this->show_export_page();
				return;
			}
			
			if ( preg_match( "#\.gz$#", $this->ipsclass->input['skin_location'] ) )
			{
				if ( $FH = @gzopen( ROOT_PATH.$this->ipsclass->input['skin_location'], 'rb' ) )
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
				if ( $FH = @fopen( ROOT_PATH.$this->ipsclass->input['skin_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize(ROOT_PATH.$this->ipsclass->input['skin_location']) );
					@fclose( $FH );
				}
			}
			
			$tmp_name = str_replace( ".gz", '', $this->ipsclass->input['skin_location'] );
			
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content = $this->ipsclass->admin->import_xml( $tmp_name );
		}
		
		if( !$content )
		{
			$this->ipsclass->main_msg = "Could not open the xml file";
			$this->show_export_page();
			return;
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		$xml = new class_xml();
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];
		$xml->use_doctype = 1;		
		
		//-----------------------------------------
		// Get the XML documents
		//-----------------------------------------

		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Import Macros
		//-----------------------------------------
		
		if ( ! is_array( $xml->xml_array['macroexport']['macrogroup'] ) and ! count( $xml->xml_array['macroexport']['macrogroup'] ) )
		{
			$this->ipsclass->main_msg = "The XML import doesn't appear to be valid - please check the file and try again.";
			$this->show_export_page();
			return;
		}
		
		$this->ipsclass->DB->do_delete( 'skin_macro', "macro_set={$skin_id}" );
		
		foreach( $xml->xml_array['macroexport']['macrogroup']['macro'] as $id => $entry )
		{
			$newrow = array();

			$newrow['macro_value']   = $entry[ 'macro_value' ]['VALUE'];
			$newrow['macro_replace'] = $entry[ 'macro_replace' ]['VALUE'];
			$newrow['macro_set']     = $skin_id;

			$this->ipsclass->DB->do_insert( 'skin_macro', $newrow );
		}		
		
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------
		
		$this->ipsclass->cache_func->_rebuild_all_caches( array($skin_id) );
		
		//-----------------------------------------
		// DONE!
		//-----------------------------------------
		
		$this->ipsclass->main_msg = 'Macros Imported! (id: '.$skin_id.')';
			
		$this->ipsclass->main_msg .= "<br />".implode( "<br />", $this->ipsclass->cache_func->messages );
		
		$this->show_export_page();
	}	
	
	
	//-----------------------------------------
	// PERFORM EXPORT MACROS
	//-----------------------------------------
	
	function export_xml_macros()
	{
		$skin_id = intval($this->ipsclass->input['skin_id']);
		
		$skin = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$skin_id ) );
		
		$current  = $skin['set_skin_set_id'];
		$parent   = $skin['set_skin_set_parent'] > 0 ? $skin['set_skin_set_parent'] : 1;
				
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		$xml = new class_xml();
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_macro', 'where' => 'macro_set='.$parent ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$macros[ strtolower( $r['macro_value'] ) ] = $r;
		}

		//-----------------------------------------
		// Get this set macro
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_macro', 'where' => 'macro_set='.$skin['set_skin_set_id'] ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$macros[ strtolower( $r['macro_value'] ) ] = $r;
		}
		
		//-----------------------------------------
		// Format macros into XML
		//-----------------------------------------
		
		if ( count( $macros ) )
		{
			$xml->xml_set_root(  'macroexport', array( 'exported' => time() ) );
			$xml->xml_add_group( 'macrogroup' );
			
			foreach( $macros as $data )
			{
				$content = array();
				
				$content[] = $xml->xml_build_simple_tag( 'macro_value'  , $data['macro_value'] );
				$content[] = $xml->xml_build_simple_tag( 'macro_replace', $data['macro_replace'] );
				
				$entry[] = $xml->xml_build_entry( 'macro', $content );
			}
			
			$xml->xml_add_entry_to_group( 'macrogroup', $entry );
			
			$xml->xml_format_document();
		}
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $xml->xml_document, 'ipb_macro.xml', '', 0  );
	}	
	
	
	//-----------------------------------------
	// PERFORM EXPORT IMAGES THING YES!
	//-----------------------------------------
	
	function do_export_images()
	{
		$skin_dir = $this->ipsclass->input['skin_dirs'];
		
		if ( ! @file_exists( CACHE_PATH.'style_images/'.$skin_dir ) )
		{
			$this->ipsclass->main_msg = "We cannot locate the selected image directory - please try another";
			$this->show_export_page();
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		require_once( KERNEL_PATH.'class_xmlarchive.php' );

		$xmlarchive = new class_xmlarchive( KERNEL_PATH );
		
		$xmlarchive->strip_path = CACHE_PATH.'style_images/'.$skin_dir;
		
		$xmlarchive->xml_add_directory( CACHE_PATH.'style_images/'.$skin_dir );
		
		$xmlarchive->xml_create_archive();

		$contents = $xmlarchive->xml_get_contents();
		
		$this->ipsclass->admin->show_download( $contents, 'ipb_images-'.$skin_dir.'.xml' );
	}
	
	//-----------------------------------------
	// PERFORM EXPORT!
	//-----------------------------------------
	
	function do_export()
	{
		//-----------------------------------------
		// Get current skin
		//-----------------------------------------
		
		$skin = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.intval($this->ipsclass->input['skin_id']) ) );
		
		$current  = $skin['set_skin_set_id'];
		$parent   = $skin['set_skin_set_parent'];
		
		$safename = substr( str_replace( " ", "", strtolower( preg_replace( "[^a-zA-Z0-9]", "", $skin['set_name'] ) ) ), 0, 10 );
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		require_once( KERNEL_PATH.'class_xmlarchive.php' );

		$xmlarchive = new class_xmlarchive( KERNEL_PATH );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$templates_xml = $this->_export_get_templates( $skin, $xml, $parent );
		$css_xml       = $this->_export_get_css(       $skin, $xml, $parent );
		$macro_xml     = $this->_export_get_macro(     $skin, $xml, $parent );
		$wrapper_xml   = $this->_export_get_wrapper(   $skin, $xml, $parent );
		$info_xml      = $this->_export_get_info(      $skin, $xml, $parent );
		
		//header("Content-Type: text/plain");
		//print $templates_xml."\n\n".$css_xml."\n\n".$macro_xml."\n\n".$wrapper_xml."\n\n".$info_xml;
		//exit();
		
		//-----------------------------------------
		// Format XMLarchive
		//-----------------------------------------
		
		$xmlarchive->xml_add_file_contents( $info_xml     , 'ipb_info.xml');
		$xmlarchive->xml_add_file_contents( $templates_xml, 'ipb_templates.xml');
		$xmlarchive->xml_add_file_contents( $css_xml      , 'ipb_css.xml'      );
		$xmlarchive->xml_add_file_contents( $macro_xml    , 'ipb_macro.xml'    );
		$xmlarchive->xml_add_file_contents( $wrapper_xml  , 'ipb_wrapper.xml'  );
		
		$xmlarchive->xml_create_archive();
		
		$skin_xmlfile = $xmlarchive->xml_get_contents();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $skin_xmlfile, 'ipb_skin-'.$safename.'.xml' );
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXTRACT MACROS
	/*-------------------------------------------------------------------------*/
	
	function _extract_xml_macros( $content, $xml, $xmlarchive )
	{
		$return = array();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		if ( ! is_array( $xml->xml_array['macroexport']['macrogroup']['macro'][0] ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------
			
			$xml->xml_array['macroexport']['macrogroup']['macro'] = array( 0 => $xml->xml_array['macroexport']['macrogroup']['macro'] );
		}
		
		if ( is_array( $xml->xml_array['macroexport']['macrogroup']['macro'] ) and count( $xml->xml_array['macroexport']['macrogroup']['macro']  ) )
		{
			foreach( $xml->xml_array['macroexport']['macrogroup']['macro'] as $entry )
			{
				$return[] = array( 'macro_value'   => $entry['macro_value']['VALUE'],
								   'macro_replace' => $entry['macro_replace']['VALUE'],
								 );
			}
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXTRACT WRAPPER
	/*-------------------------------------------------------------------------*/
	
	function _extract_xml_wrapper( $content, $xml, $xmlarchive )
	{
		$return = array();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		if ( ! is_array( $xml->xml_array['wrapperexport']['wrappergroup']['wrapper'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['wrapperexport']['wrappergroup']['wrapper'] = array( 0 => $xml->xml_array['wrapperexport']['wrappergroup']['wrapper'] );
		}
		
		if ( is_array( $xml->xml_array['wrapperexport']['wrappergroup']['wrapper'] ) )
		{
			foreach( $xml->xml_array['wrapperexport']['wrappergroup']['wrapper'] as $entry )
			{
				$return = $entry['wrappercontent']['VALUE'];
			}
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXTRACT CSS
	/*-------------------------------------------------------------------------*/
	
	function _extract_xml_css( $content, $xml, $xmlarchive )
	{
		$return = array();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		if ( ! is_array( $xml->xml_array['cssexport']['cssgroup']['css'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['cssexport']['cssgroup']['css'] = array( 0 => $xml->xml_array['cssexport']['cssgroup']['css'] );
		}
		
		if ( is_array( $xml->xml_array['cssexport']['cssgroup']['css'] )  )
		{
			foreach( $xml->xml_array['cssexport']['cssgroup']['css'] as $entry )
			{
				$return = $entry['csscontent']['VALUE'];
			}
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXTRACT TEMPLATES
	/*-------------------------------------------------------------------------*/
	
	function _extract_xml_templates( $content, &$xml, &$xmlarchive )
	{
		$return = array();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------

		$xml->xml_parse_document( $content );
	
		if ( ! is_array( $xml->xml_array['templateexport']['templategroup']['template'][0] ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------
			
			$xml->xml_array['templateexport']['templategroup']['template'] = array( 0 => $xml->xml_array['templateexport']['templategroup']['template'] );
		}
		
		if ( is_array( $xml->xml_array['templateexport']['templategroup']['template'] ) )
		{
			foreach( $xml->xml_array['templateexport']['templategroup']['template'] as $entry )
			{
				if ( ! $entry[ 'func_name' ]['VALUE'] )
				{
					continue;
				}
				
				$return[] = array( 'group_name'            => $entry[ 'group_name' ]['VALUE'],
								   'section_content'       => $entry[ 'section_content' ]['VALUE'],
								   'func_name'             => $entry[ 'func_name' ]['VALUE'],
								   'func_data'             => $entry[ 'func_data' ]['VALUE'],
								   'group_names_secondary' => $entry[ 'group_names_secondary' ]['VALUE'],
								 );
			}
		}
		
		return $return;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// _EXTRACT INFO
	/*-------------------------------------------------------------------------*/
	
	function _extract_xml_info( $content, $xml, $xmlarchive )
	{
		$return = array();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------

		$xml->xml_parse_document( $content );
		
		if ( ! is_array( $xml->xml_array['infoexport']['infogroup']['info'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$xml->xml_array['infoexport']['infogroup']['info'] = array( 0 => $xml->xml_array['infoexport']['infogroup']['info'] );
		}
		
		if ( is_array( $xml->xml_array['infoexport']['infogroup']['info'] )  )
		{
			foreach( $xml->xml_array['infoexport']['infogroup']['info'] as $entry )
			{
				$return[ 'set_name' ]         = $entry['set_name']['VALUE'];
				$return[ 'set_author_email' ] = $entry['set_author_email']['VALUE'];
				$return[ 'set_author_name' ]  = $entry['set_author_name']['VALUE'];
				$return[ 'set_author_url' ]   = $entry['set_author_url']['VALUE'];
				$return[ 'set_key' ]  		  = $entry['set_key']['VALUE'];
			}
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXPORT INFO (internal)
	/*-------------------------------------------------------------------------*/
	
	function _export_get_info( $skin, $xml, $parent )
	{
		$xml->xml_set_root(  'infoexport', array( 'exported' => time() ) );
		$xml->xml_add_group( 'infogroup' );
		
		$content[] = $xml->xml_build_simple_tag( 'set_name'        , $skin['set_name'] );
		$content[] = $xml->xml_build_simple_tag( 'set_author_email', $skin['set_author_email'] );
		$content[] = $xml->xml_build_simple_tag( 'set_author_name' , $skin['set_author_name'] );
		$content[] = $xml->xml_build_simple_tag( 'set_author_url'  , $skin['set_author_url'] );
		$content[] = $xml->xml_build_simple_tag( 'set_key'  	   , $skin['set_key'] );
		
		$entry[]   = $xml->xml_build_entry( 'info', $content );
		
		$xml->xml_add_entry_to_group( 'infogroup', $entry );
		
		$xml->xml_format_document();

		$info_xml = $xml->xml_document;
		
		return $info_xml;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXPORT WRAPPER (internal)
	/*-------------------------------------------------------------------------*/
	
	function _export_get_wrapper( $skin, $xml, $parent )
	{
		$raw_wrapper = "";
		
		if ( $this->ipsclass->input['skin_options'] != 'noparent' )
		{
			if ( $parent > 1 )
			{
				$wrapper_parent = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$parent ) );
			}
			
			if ( $wrapper_parent['set_wrapper'] )
			{
				$raw_wrapper = $wrapper_parent['set_wrapper'];
			}
			
		}
		
		if ( $skin['set_wrapper'] )
		{
			$raw_wrapper = $skin['set_wrapper'];
		}
		
		$xml->xml_set_root(  'wrapperexport', array( 'exported' => time() ) );
		$xml->xml_add_group( 'wrappergroup' );
		
		$content[] = $xml->xml_build_simple_tag( 'wrappercontent', $raw_wrapper );
		
		$entry[]   = $xml->xml_build_entry( 'wrapper', $content );
		
		$xml->xml_add_entry_to_group( 'wrappergroup', $entry );
		
		$xml->xml_format_document();

		$wrapper_xml = $xml->xml_document;
		
		return $wrapper_xml;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXPORT MACRO (internal)
	/*-------------------------------------------------------------------------*/
	
	function _export_get_macro( $skin, $xml, $parent )
	{
		$macro_xml = "";
		$macros    = array();
		$entry     = array();
		
		if ( $this->ipsclass->input['skin_options'] != 'noparent' )
		{
			//-----------------------------------------
			// Get parent macros
			//-----------------------------------------
			
			if ( $parent > 1 )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_macro', 'where' => 'macro_set='.$parent ) );
				$this->ipsclass->DB->simple_exec();
				
				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$macros[ strtolower( $r['macro_value'] ) ] = $r;
				}
			}
		}
		
		//-----------------------------------------
		// Get this set macro
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_macro', 'where' => 'macro_set='.$skin['set_skin_set_id'] ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$macros[ strtolower( $r['macro_value'] ) ] = $r;
		}
		
		//-----------------------------------------
		// Format macros into XML
		//-----------------------------------------
		
		if ( count( $macros ) )
		{
			$xml->xml_set_root(  'macroexport', array( 'exported' => time() ) );
			$xml->xml_add_group( 'macrogroup' );
			
			foreach( $macros as $data )
			{
				$content = array();
				
				$content[] = $xml->xml_build_simple_tag( 'macro_value'  , $data['macro_value'] );
				$content[] = $xml->xml_build_simple_tag( 'macro_replace', $data['macro_replace'] );
				
				$entry[] = $xml->xml_build_entry( 'macro', $content );
			}
			
			$xml->xml_add_entry_to_group( 'macrogroup', $entry );
			
			$xml->xml_format_document();
	
			$macro_xml = $xml->xml_document;
		}
		
		return $macro_xml;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXPORT CSS (internal)
	/*-------------------------------------------------------------------------*/
	
	function _export_get_css( $skin, $xml, $parent )
	{
		$raw_css = "";
		
		if ( $this->ipsclass->input['skin_options'] != 'noparent' )
		{
			if ( $parent > 1 )
			{
				$css_parent = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$parent ) );
			}
			
			if ( $css_parent['set_css'] )
			{
				$raw_css = $css_parent['set_css'];
			}
		}
		
		if ( $skin['set_css'] )
		{
			$raw_css = $skin['set_css'];
		}
			
		$xml->xml_set_root(  'cssexport', array( 'exported' => time() ) );
		$xml->xml_add_group( 'cssgroup' );
		
		$content[] = $xml->xml_build_simple_tag( 'csscontent', $raw_css );
		
		$entry[]   = $xml->xml_build_entry( 'css', $content );
		
		$xml->xml_add_entry_to_group( 'cssgroup', $entry );
		
		$xml->xml_format_document();

		$css_xml = $xml->xml_document;
		
		return $css_xml;
	}
	
	/*-------------------------------------------------------------------------*/
	// _EXPORT TEMPLATES (internal)
	/*-------------------------------------------------------------------------*/
	
	function _export_get_templates( $skin, $xml, $parent )
	{
		$xml->xml_set_root( 'templateexport', array( 'exported' => time(), 'versionid' => '20000', 'type' => 'export' ) );
		
		$xml->xml_add_group( 'templategroup' );
		
		if ( $this->ipsclass->input['skin_options'] == 'noparent' )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => 'group_name, section_content, func_name, func_data', 'from' => 'skin_templates', 'where' => 'set_id='.intval($this->ipsclass->input['skin_id']) ) );
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$content = array();
				
				foreach ( $r as $k => $v )
				{
					$content[] = $xml->xml_build_simple_tag( $k, $v );
				}
				
				$entry[] = $xml->xml_build_entry( 'template', $content );
			}
			
			$xml->xml_add_entry_to_group( 'templategroup', $entry );
		}
		else
		{
			//-----------------------------------------
			// Get template parents
			//-----------------------------------------
			
			$all_templates = array();
			
			if ( $parent > 1 )
			{
				$this->ipsclass->DB->simple_construct( array( 'select' => 'group_name, section_content, func_name, func_data', 'from' => 'skin_templates', 'where' => 'set_id='.$parent ) );
				$this->ipsclass->DB->simple_exec();
				
				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$all_templates[ strtolower( $r['group_name'] ) .','. strtolower( $r['func_name'] ) ] = $r;
				}
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'group_name, section_content, func_name, func_data', 'from' => 'skin_templates', 'where' => 'set_id='.intval($this->ipsclass->input['skin_id']) ) );
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$all_templates[ strtolower( $r['group_name'] )  .','. strtolower( $r['func_name'] ) ] = $r;
			}
			
			if ( count( $all_templates ) )
			{
				foreach( $all_templates as $r )
				{
					$content = array();
					
					foreach ( $r as $k => $v )
					{
						$content[] = $xml->xml_build_simple_tag( $k, $v );
					}
					
					$entry[] = $xml->xml_build_entry( 'template', $content );
				}
				
				$xml->xml_add_entry_to_group( 'templategroup', $entry );
			}
		}
		
		$xml->xml_format_document();
		
		$templates_xml = $xml->xml_document;
		
		return $templates_xml;
	}
	
	
	//-----------------------------------------
	// SHOW EXPORT PAGE
	//-----------------------------------------
	
	function show_export_page()
	{
		$form_array   = array();
		$set_to_image = array();
		
		$this->ipsclass->admin->page_detail = "You can download skin sets by configuring the form below. The skin XML templates (HTML, Macros, CSS & Wrapper) are independent of the image set.<br />To download a 'full' skin set, you will need to download both the skin XML and the image set.";
		$this->ipsclass->admin->page_title  = "Export Skin Sets";
		
		//-----------------------------------------
		// Get skin list...
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_display.php' );
		$display   = new display();
		$display->ipsclass =& $this->ipsclass;
		
		$skin_list = $display->_build_skin_list();
		$skin_list = str_replace( "selected='selected'", "", $skin_list );
		$skin_list = str_replace( 'selected="selected"', "", $skin_list );

		//-----------------------------------------
		// Do we have an incoming ID?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['id'] )
		{
			$skin_list = str_replace( "value='{$this->ipsclass->input['id']}'", "value='{$this->ipsclass->input['id']}' selected='selected'", $skin_list );
		}
		
		//-----------------------------------------
		// Get skins...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_sets', 'order' => 'set_skin_set_id DESC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$set_to_image[ $r['set_image_dir'] ] = $r['set_name'];
		}
		
		//-----------------------------------------
		// Image dir
		//-----------------------------------------
		
		$dirs = array();
		
		$dh = opendir( CACHE_PATH.'style_images' );
		
 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
 			if (($file != ".") && ($file != ".."))
 			{
				if ( is_dir( CACHE_PATH.'style_images/'.$file ) )
				{
					$dirs[] = array( $file, 'Image Set: "'.$file.'" (used in skin: '.$set_to_image[ $file ].')' );
				}
 			}
 		}
 		
 		closedir( $dh );
 		
 		//-----------------------------------------
 		// start output
 		//-----------------------------------------
 		
		$start_form_a = $this->ipsclass->adskin->start_form( array( 1 => array( 'act' , 'import' ),
																	2 => array( 'code', 'export' ),
																	4 => array( 'section', $this->ipsclass->section_code ),
														   )      );
 												     
 		$start_form_b = $this->ipsclass->adskin->start_form( array( 1 => array( 'act' , 'import' ),
														      2 => array( 'code', 'exportimages' ),
														      4 => array( 'section', $this->ipsclass->section_code ),
 												     )      );
 												     
 		$start_form_c = $this->ipsclass->adskin->start_form( array( 1 => array( 'act' , 'import' ),
																	2 => array( 'code', 'importtemplates' ),
																	3 => array( 'MAX_FILE_SIZE', '10000000000' ),
																	4 => array( 'section', $this->ipsclass->section_code ),
														   ) , "uploadform", " enctype='multipart/form-data'"     );
													 
		$start_form_d = $this->ipsclass->adskin->start_form( array( 1 => array( 'act' , 'import' ),
															  2 => array( 'code', 'importimages' ),
															  3 => array( 'MAX_FILE_SIZE', '10000000000' ),
															  4 => array( 'section', $this->ipsclass->section_code ),
													 ) , "uploadform", " enctype='multipart/form-data'"     );


		$start_form_e = $this->ipsclass->adskin->start_form( array( 1 => array( 'act' , 'import' ),
														      2 => array( 'code', 'exportmacros' ),
														      4 => array( 'section', $this->ipsclass->section_code ),
 												     )      );

		$start_form_f = $this->ipsclass->adskin->start_form( array( 1 => array( 'act' , 'import' ),
															  2 => array( 'code', 'importmacros' ),
															  3 => array( 'MAX_FILE_SIZE', '10000000000' ),
															  4 => array( 'section', $this->ipsclass->section_code ),
													 ) , "uploadform", " enctype='multipart/form-data'"     );
													 
		$import_dirs[] 	= array( '', '-None-' );
		$import_dirs 	= array_merge( $import_dirs, $dirs );
		
		$import_skins 	= "<option value='0'>-None-</option>".$skin_list;
		
		$this->ipsclass->html .= "<div class='tableborder'>
							 <div class='tableheaderalt'>Exporting...</div>
							 <div class='tablepad' style='background-color:#EAEDF0'>
							 <br />
							 <fieldset>
							  <legend><strong>Export Skin Templates</strong>
							  $start_form_a
							  <div style='border:1px solid #D1DCEB'>
							  <table cellpadding='4' cellspacing='0' width='100%'>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Export Which Skin Template Set?</b><div class='graytext'>Please select which skin set (HTML templates, CSS, Macros & wrapper) you wish to export.</div></td>
							   <td class='tablerow2' width='60%'><select name='skin_id' class='dropdown'>{$skin_list}</select></td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b>Export Options</b><div class='graytext'>Please choose how deep this export should look for customizations.</div></td>
							   <td class='tablerow2' width='60%'>".$this->ipsclass->adskin->form_dropdown("skin_options",
																								   array( 0 => array( 'noparent'  , 'Export customizations in this skin only' ),
																										  1 => array( 'yesparent' , 'Export customizations in this skin and any parent skins' )
																										)
																								 ) ."</td>
							 </tr>
							 </table>
							 <div align='center' class='tablesubheader'><input type='submit' class='realbutton' value='EXPORT SKIN XML' /></div>
							 </div>
							 </form>
							 </fieldset>
							 <br />
							 
							 <fieldset>
							  <legend><strong>Export Skin Images</strong>
							  $start_form_b
							  <div style='border:1px solid #D1DCEB'>
							  <table cellpadding='4' cellspacing='0' width='100%'>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Export Which Image Set?</b><div class='graytext'>Image sets are exported into the XMLarchive format. This is not suitable for safemode users and in such a case we recommend you manually download via FTP and ZIP the images.</div></td>
							   <td class='tablerow2' width='60%'>". $this->ipsclass->adskin->form_dropdown( 'skin_dirs', $dirs )."</td>
							 </tr>
							 </table>
							 <div align='center' class='tablesubheader'><input type='submit' class='realbutton' value='EXPORT SKIN IMAGES' /></div>
							 </div>
							 </form>
							 </fieldset>
							 <br />
							 
							 <fieldset>
							  <legend><strong>Export Skin Macros</strong>
							  $start_form_e
							  <div style='border:1px solid #D1DCEB'>
							  <table cellpadding='4' cellspacing='0' width='100%'>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Export Macros From Which Skin Set?</b><div class='graytext'>Macro sets are exported into XML format.  This option may not work properly if your server has safemode enabled.</div></td>
							   <td class='tablerow2' width='60%'><select name='skin_id' class='dropdown'>{$skin_list}</select></td>
							 </tr>
							 </table>
							 <div align='center' class='tablesubheader'><input type='submit' class='realbutton' value='EXPORT SKIN MACROS' /></div>
							 </div>
							 </form>
							 </fieldset>							 
							</div>
							</div>
							
							<br />
							 
							<div class='tableborder'>
							 <div class='tableheaderalt'>Importing...</div>
							 <div class='tablepad' style='background-color:#EAEDF0'>
							 <br />
							 <fieldset>
							  <legend><strong>Import Skin Templates</strong>
							  $start_form_c
							  <div style='border:1px solid #D1DCEB'>
							  <table cellpadding='4' cellspacing='0' width='100%'>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Upload XML Template set</b><div style='color:gray'>The file must begin with 'ipb_skin-' and end with either '.xml' or '.xml.gz'</div></td>
							   <td class='tablerow2' width='60%'>". $this->ipsclass->adskin->form_upload(  ) ."</td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b><u>OR</u> enter the filename of the XML Template Set</b><div style='color:gray'>The file must be uploaded into the forum's root folder</div></td>
							   <td class='tablerow2' width='60%'>".$this->ipsclass->adskin->form_input( 'skin_location'  )."</td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b>New Skin Set Name?</b><div style='color:gray'>Leave blank to use the skin name from the XMLarchive</div></td>
							   <td class='tablerow2' width='60%'>".$this->ipsclass->adskin->form_input( 'skin_name'  )."</td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b>Use with which image set?</b><div style='color:gray'>If you have not yet uploaded the image set, leave on -None-</div></td>
							   <td class='tablerow2' width='60%'>". $this->ipsclass->adskin->form_dropdown( 'image_set', $import_dirs )."</td>
							 </tr>							 
							 </table>
							 <div align='center' class='tablesubheader'><input type='submit' class='realbutton' value='IMPORT SKIN XML' /></div>
							 </div>
							 </form>
							 </fieldset>
							 
							 <br />
							 <fieldset>
							  <legend><strong>Import Skin Images</strong>
							  $start_form_d
							  <div style='border:1px solid #D1DCEB'>
							  <table cellpadding='4' cellspacing='0' width='100%'>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Upload XMLarchive Image set</b><div style='color:gray'>The file must begin with 'ipb_images-' and end with either '.xml' or '.xml.gz'</div></td>
							   <td class='tablerow2' width='60%'>". $this->ipsclass->adskin->form_upload(  ) ."</td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b><u>OR</u> enter the filename of the XMLarchive Image Set</b><div style='color:gray'>The file must be uploaded into the forum's root folder</div></td>
							   <td class='tablerow2' width='60%'>".$this->ipsclass->adskin->form_input( 'skin_location'  )."</td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b>New Image Set Directory Name?</b><div style='color:gray'>Leave blank to use the set name from the XMLarchive</div></td>
							   <td class='tablerow2' width='60%'>".$this->ipsclass->adskin->form_input( 'skin_name'  )."</td>
							 </tr>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Use with which skin set?</b><div class='graytext'>If you do not want to apply this image set to any skin, leave on -None-.</div></td>
							   <td class='tablerow2' width='60%'><select name='skin_set' class='dropdown'>{$import_skins}</select></td>
							 </tr>							 
							 </table>
							 <div align='center' class='tablesubheader'><input type='submit' class='realbutton' value='IMPORT SKIN IMAGES' /></div>
							 </div>
							 </form>
							 </fieldset>
							 
							 <br />
							 <fieldset>
							  <legend><strong>Import Skin Macros</strong>
							  $start_form_f
							  <div style='border:1px solid #D1DCEB'>
							  <table cellpadding='4' cellspacing='0' width='100%'>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Upload XML Macro set</b><div style='color:gray'>The file must begin with 'ipb_macros-' and end with '.xml'</div></td>
							   <td class='tablerow2' width='60%'>". $this->ipsclass->adskin->form_upload(  ) ."</td>
							 </tr>
							 <tr>
							   <td class='tablerow1' width='40%'><b><u>OR</u> enter the filename of the XML Macro Set</b><div style='color:gray'>The file must be uploaded into the forum's root folder</div></td>
							   <td class='tablerow2' width='60%'>".$this->ipsclass->adskin->form_input( 'skin_location'  )."</td>
							 </tr>
							  <tr>
							   <td class='tablerow1' width='40%'><b>Use with which skin set?</b></td>
							   <td class='tablerow2' width='60%'><select name='skin_set' class='dropdown'>{$skin_list}</select></td>
							 </tr>							 
							 </table>
							 <div align='center' class='tablesubheader'><input type='submit' class='realbutton' value='IMPORT SKIN MACROS' /></div>
							 </div>
							 </form>
							 </fieldset>							 
							 </div>
							</div>";
																				
							   
		$this->ipsclass->admin->output();
	
	}
	
	
	
}


?>