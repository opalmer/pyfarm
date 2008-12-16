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
|   > $Date: 2006-05-25 10:15:22 -0400 (Thu, 25 May 2006) $
|   > $Revision: 278 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > API: Skins
|   > Module written by Brandon Farber
|   > Date started: Tuesday June 6th 2006 (11:12)
|
+--------------------------------------------------------------------------
*/

/**
* API: Skins
*
* EXAMPLE USAGE
* <code>
* $api =  new api_skins();
* # Optional - if $ipsclass is not passed, it'll init
* $api->ipsclass =& $this->ipsclass;
* $api->api_init();
* $api->skin_add_bits( $path_to_xml_file );
* $api->skin_add_macros( $path_to_xml_file );
* $messages = $api->skin_rebuild_caches( 0 );
* print implode( "<br />", $messages[1] );
* $messages = $api->skin_rebuild_caches( $messages[0] );
* </code>
*
* Macros for use with this file can be exported via the ACP
* 	Skin Import/Export page
*
* Templates for use with this file can be exported via the ACP
*	Skin Sets Overview page when IN_DEV mode is enabled - it is
*	recommended to add the name of your skin file under the
*	"Developers: Export Module Skin Files" section to export
*	all templates for your skin group.
*
* Example - if you make a skin_links skin template file, enter
*	"links" in the form field and download the xml file.
*
* The skin_rebuild_caches rebuilds one skin set at a time - it
*	rebuilds the next skin set id higher than the id you pass it.
*	On first function call, pass a 0.  On subsequent function
*	calls pass it the 1st array value (index 0) returned from
*	the previous call.  It rebuilds one skin set at a time to
*	prevent time outs - it is recommended in your script that
*	you redirect to a new page passing the appropriate skin id
*	value after each rebuild - when this function returns a 0
*	for index 0, all skin sets will be rebuild.  Index 1 of the
	array returned will be an array of messages.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'api_core' ) )
{
	require_once( IPS_API_PATH.'/api_core.php' );
}

/**
* API: Skins
*
* This class deals with all available skin insertion functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Brandon Farber
* @version		2.2
* @since		2.2.0
*/
class api_skins extends api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	//var $ipsclass;
	
	var $xml;
	var $xmlarchive;
	var $cache_func;
	var $skinadmin;
	
	var $skin_id = 0;
	
	
	function get_import_libs()
	{
		if( !is_object($this->xml) )
		{
			require_once( KERNEL_PATH.'class_xml.php' );
	
			$this->xml = new class_xml();
			$this->xml->doc_type = $this->ipsclass->vars['gb_char_set'];
			$this->xml->use_doctype = 1;
			$this->xml->lite_parser = 1;
		}
		
		if( !is_object($this->xmlarchive) )
		{
			require_once( KERNEL_PATH.'class_xmlarchive.php' );
			$this->xmlarchive = new class_xmlarchive( KERNEL_PATH );
		}
		
		if( !is_object($this->skinadmin) )
		{
			require_once( ROOT_PATH . 'sources/action_admin/skin_import.php' );
			$this->skinadmin = new ad_skin_import();
			$this->skinadmin->ipsclass =& $this->ipsclass;
			
		}
		
		if( !is_object($this->cache_func) )
		{
			$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_api_queries.php', 'sql_api_queries' );
			
			require_once( ROOT_PATH.'sources/lib/admin_cache_functions.php' );
			$this->cache_func = new admin_cache_functions();
			$this->cache_func->ipsclass =& $this->ipsclass;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Add skin set to IPB
	/*-------------------------------------------------------------------------*/
	/**
	* Adds an entire skin set to IPB
	*
	* @param	string	Path to xml or xml.gz file containing skin set
	* @return 	void;
	*/
	function skin_add_set( $xml_file_path )
	{
		//-------------------------------
		// Check?
		//-------------------------------
		
		if ( ! $xml_file_path OR !file_exists($xml_file_path) )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		if ( preg_match( "#\.gz$#", $xml_file_path ) )
		{
			if ( $FH = @gzopen( $xml_file_path, 'rb' ) )
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
			if ( $FH = @fopen( $xml_file_path, 'rb' ) )
			{
				$content = @fread( $FH, filesize($xml_file_path) );
				@fclose( $FH );
			}
		}
		
		if( !$content )
		{
			$this->api_error[] = "xml_file_not_valid";
			return;
		}
		
		$tmp_name = str_replace( ".gz", '', $xml_file_path );
			
		$this->get_import_libs();

		$this->xmlarchive->xml_read_archive_data( $content );
		
		//-------------------------------
		// Get file contents
		//-------------------------------		
		
		$import_xml = array();
		
		foreach( $this->xmlarchive->file_array as $f )
		{
			$import_xml[ $f['filename'] ] = $f['content'];
		}
		
		if ( $import_xml[ 'ipb_info.xml' ] != '' )
		{
			$info_xml = $this->skinadmin->_extract_xml_info( $import_xml[ 'ipb_info.xml' ], $this->xml, $this->xmlarchive );
		}
		
		if ( ! is_array( $info_xml ) and ! count( $info_xml ) )
		{
			$this->api_error[] = "xml_file_not_valid";
			return;
		}
		
		if ( $import_xml[ 'ipb_templates.xml' ] != '' )
		{
			$templates_xml = $this->skinadmin->_extract_xml_templates( $import_xml[ 'ipb_templates.xml' ], $this->xml, $this->xmlarchive );
		}
		
		if ( $import_xml[ 'ipb_css.xml' ] != '' )
		{
			$css_xml = $this->skinadmin->_extract_xml_css( $import_xml[ 'ipb_css.xml' ], $this->xml, $this->xmlarchive );
		}
		
		if ( $import_xml[ 'ipb_macro.xml' ] != '' )
		{
			$macro_xml = $this->skinadmin->_extract_xml_macros( $import_xml[ 'ipb_macro.xml' ], $this->xml, $this->xmlarchive );
		}
		
		if ( $import_xml[ 'ipb_wrapper.xml' ] != '' )
		{
			$wrapper_xml = $this->skinadmin->_extract_xml_wrapper( $import_xml[ 'ipb_wrapper.xml' ], $this->xml, $this->xmlarchive );
		}
		
		$default = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_default=1' ) );
		$img_dir = $default['set_image_dir'];
		
		//-----------------------------------------
		// Add new skin!
		//-----------------------------------------
		
		$this->ipsclass->DB->allow_sub_select = 1;
		
		$this->ipsclass->DB->do_insert( 'skin_sets', array( 'set_name'            => $info_xml['set_name'],
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
					 
		$this->skin_id = $this->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Insert templates...
		//-----------------------------------------
		
		if ( is_array( $templates_xml ) and count( $templates_xml ) )
		{
			foreach( $templates_xml as $t )
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_insert( 'skin_templates', array( 'set_id'          => $this->skin_id,
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
				
				$this->ipsclass->DB->do_insert( 'skin_macro', array( 'macro_set'        => $this->skin_id,
																	 'macro_value'      => $t['macro_value'],
																	 'macro_replace'    => $t['macro_replace'],
																	 'macro_can_remove' => 1,
											  )                    );
			}
		}
		
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_api_queries.php', 'sql_api_queries' );
		

		
		$this->cache_func->_rebuild_all_caches( array($this->skin_id) );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Add image set to IPB
	/*-------------------------------------------------------------------------*/
	/**
	* Adds an image set to IPB, using $skin_id or $this->skin_id as skin
	*
	* @param	string	Path to xml or xml.gz file containing image set
	* @return 	void;
	*/
	function images_add_set( $xml_file_path, $skin_id=0 )
	{
		//-------------------------------
		// Check?
		//-------------------------------
		
		if( $skin_id )
		{
			$this->skin_id = $skin_id;
		}
		
		if ( ! $xml_file_path OR !file_exists($xml_file_path) )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		if ( preg_match( "#\.gz$#", $xml_file_path ) )
		{
			if ( $FH = @gzopen( $xml_file_path, 'rb' ) )
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
			if ( $FH = @fopen( $xml_file_path, 'rb' ) )
			{
				$content = @fread( $FH, filesize($xml_file_path) );
				@fclose( $FH );
			}
		}
		
		if( !$content )
		{
			$this->api_error[] = "xml_file_not_valid";
			return;
		}
		
		$tmp_name = str_replace( ".gz", '', $xml_file_path );
			
		$this->get_import_libs();

		$this->xmlarchive->xml_read_archive_data( $content );
		
		//-------------------------------
		// Get file contents
		//-------------------------------		
		
		$default = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id=' . $this->skin_id ) );
		
		$safename = substr( str_replace( " ", "", strtolower( preg_replace( "[^a-zA-Z0-9]", "", $default['set_name'] ) ) ), 0, 10 );
		$images   = array();
		
		foreach( $this->xmlarchive->file_array as $f )
		{
			if ( $f['content'] and $f['filename'] )
			{
				$images[] = array( 'content'  => $f['content'],
								   'path'     => $f['path'],
								   'filename' => $f['filename']
								 );
			}
		}
		
		if ( ! count($images) )
		{
			$this->api_error[] = "xml_file_not_valid";
			return;
		}
		
		if ( ! is_writable( CACHE_PATH.'style_images' ) )
		{
			$this->api_error[] = "cache_path_not_writable";
			return;
		}
		
		if ( file_exists( CACHE_PATH.'style_images/'.$safename ) )
		{
			$safename .= time();
		}
		
		if ( ! @mkdir( CACHE_PATH.'style_images/'.$safename, 0777 ) )
		{
			$this->api_error[] = "cache_path_not_writable";
			return;
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
				
		if( $this->skin_id )
		{
			$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_image_dir' => $safename ), 'set_skin_set_id='.$this->skin_id );
			
			$this->cache_func->_rebuild_all_caches( array($this->skin_id) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Add skin templates to IPB
	/*-------------------------------------------------------------------------*/
	/**
	* Adds skin templates to IPB master template set which will filter down to child skins
	*
	* @param	string	Path to xml file containing skin templates (Note: templates can
	*					be exported from ACP when IN_DEV mode is enabled with the
	*					Developer Export options at the bottom of the skin sets overview)
	* @return 	void;
	*/
	function skin_add_bits( $xml_file_path )
	{
		//-------------------------------
		// Check?
		//-------------------------------
		
		if ( ! $xml_file_path OR !file_exists($xml_file_path) )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$this->xml = new class_xml();
		
		//-------------------------------
		// Get file contents
		//-------------------------------		
		
		$skin_content = implode( "", file($xml_file_path) );
		
		//-------------------------------
		// Unpack the datafile (TEMPLATES)
		//-------------------------------

		$this->xml->xml_parse_document( $skin_content );		
		
		//-------------------------------
		// (TEMPLATES)
		//-------------------------------
		
		if ( ! is_array( $this->xml->xml_array['templateexport']['templategroup']['template'] ) )
		{
			$this->api_error[] = "xml_file_not_valid";
			return;
		}
		
		if ( ! is_array( $this->xml->xml_array['templateexport']['templategroup']['template'][0] ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------

			$this->xml->xml_array['templateexport']['templategroup']['template'] = array( 0 => $this->xml->xml_array['templateexport']['templategroup']['template'] );
		}
		
		foreach( $this->xml->xml_array['templateexport']['templategroup']['template'] as $id => $entry )
		{
			$this->ipsclass->DB->allow_sub_select = 1;
			
			$row = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'suid',
																  'from'   => 'skin_templates',
																  'where'  => "group_name='{$entry['group_name']['VALUE']}' AND func_name='{$entry['func_name']['VALUE']}' and set_id=1"
														 )      );

			if ( $row['suid'] )
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_update( 'skin_templates', array( 'func_data'       => $entry[ 'func_data' ]['VALUE'],
																		 'section_content' => $entry[ 'section_content' ]['VALUE'],
																		 'updated'         => time()
																	   )
											    , 'suid='.$row['suid'] );
			}
			else
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_insert( 'skin_templates', array( 'func_data'       => $entry[ 'func_data' ]['VALUE'],
																		 'func_name'       => $entry[ 'func_name' ]['VALUE'],
																		 'section_content' => $entry[ 'section_content' ]['VALUE'],
																		 'group_name'      => $entry[ 'group_name' ]['VALUE'],
																		 'updated'         => time(),
																		 'set_id'          => 1
											  )                        );
			}
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Add macros to IPB
	/*-------------------------------------------------------------------------*/
	/**
	* Adds macros to IPB master templates which will filter down to child skins
	*
	* @param	string	Path to xml file containing macros (Note: macros can
	*					be exported from ACP when IN_DEV mode is enabled)
	* @return 	void;
	*/
	function skin_add_macros( $xml_file_path )
	{
		//-------------------------------
		// Check?
		//-------------------------------
		
		if ( !$xml_file_path )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$this->xml = new class_xml();
		
		//-------------------------------
		// Get file contents
		//-------------------------------		
		
		$macro_content = implode( "", file($xml_file_path) );
		
		//-------------------------------
		// Unpack the datafile (MACROS)
		//-------------------------------

		$this->xml->xml_parse_document( $macro_content );	
		
		//-------------------------------
		// (MACRO)
		//-------------------------------

		if ( ! is_array( $this->xml->xml_array['macroexport']['macrogroup']['macro'] ) )
		{
			$this->api_error[] = "xml_file_not_valid";
			return;
		}
		
		if ( ! is_array( $this->xml->xml_array['macroexport']['macrogroup']['macro'][0] ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------
			
			$this->xml->xml_array['macroexport']['macrogroup']['macro'] = array( 0 => $this->xml->xml_array['macroexport']['macrogroup']['macro'] );
		}
		
		foreach( $this->xml->xml_array['macroexport']['macrogroup']['macro'] as $id => $entry )
		{
			$this->ipsclass->DB->allow_sub_select = 1;
			
			$row = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'macro_id',
																  'from'   => 'skin_macro',
																  'where'  => "macro_value='{$entry['macro_value']['VALUE']}' and macro_set=1"
										 )      );
			if ( $row['macro_id'] )
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_update( 'skin_macro', array( 'macro_replace' => $entry['macro_replace']['VALUE'] ), "macro_value='{$entry['macro_value']['VALUE']}' and macro_set=1" );
			}
			else
			{
				$this->ipsclass->DB->allow_sub_select = 1;
				
				$this->ipsclass->DB->do_insert( 'skin_macro', array( 'macro_value'		=> $entry['macro_value']['VALUE'],
																	 'macro_replace'	=> $entry['macro_replace']['VALUE'],
																	 'macro_set'		=> 1 ) );
			}
		}
	}	
	
	
	/*-------------------------------------------------------------------------*/
	// Rebuild skin caches
	/*-------------------------------------------------------------------------*/
	/**
	* Rebuilds template caches to cache new skins to .php files
	*
	* @param	int		Last skin id completed (returned upon completion of prior skin)
	* @return 	array	Returns: Skin id completed (which can be passed back to this
	*					function on next run) and an array of messages
	*/
	function skin_rebuild_caches( $completed=1 )
	{
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_api_queries.php', 'sql_api_queries' );
		
		//-----------------------------------
		// Get ACP library
		//-----------------------------------

		require_once( ROOT_PATH.'sources/lib/admin_cache_functions.php' );
		$this->cache_func = new admin_cache_functions();
		$this->cache_func->ipsclass =& $this->ipsclass;
		
		//-----------------------------------
		// Image cache url
		//-----------------------------------		
		
		$row = $this->ipsclass->DB->simple_exec_query ( array ( 'select' => 'conf_value, conf_default', 'from' => 'conf_settings', 'where' => "conf_key='ipb_img_url'" ) );
		
		$this->ipsclass->vars['ipb_img_url'] = $row['conf_value'] != "" ? $row['conf_value'] : $row['conf_default'];
		
		if ( $this->ipsclass->vars['ipb_img_url'] == "{blank}" )
		{
			$this->ipsclass->vars['ipb_img_url'] = "";
		}
		
		//-------------------------------
		// Next skin to do?
		//-------------------------------		
		
		$completed = $completed > 0 ? intval($completed) : 1;
		
		//-----------------------------------
		// Get skins
		//-----------------------------------

		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'skin_sets',
													  'where'  => 'set_skin_set_id > '.$completed,
													  'order'  => 'set_skin_set_id',
													  'limit'  => array( 0, 1 )
						     )      );

		$this->ipsclass->DB->simple_exec();

		//-----------------------------------
		// Got a biggun?
		//-----------------------------------

		$r = $this->ipsclass->DB->fetch_row();

		if ( $r['set_skin_set_id'] )
		{
			$this->cache_func->_rebuild_all_caches( array($r['set_skin_set_id']) );

			return array( 'completed' => $r['set_skin_set_id'], 'messages' => $this->cache_func->messages );
		}
		else
		{
			return array( 'completed' => 0, 'messages' => array( 'No more skins to rebuild' ) );
		}
	}		
	
	/*-------------------------------------------------------------------------*/
	// Rebuild update template bit
	/*-------------------------------------------------------------------------*/
	/**
	* Updates a template bit from a previous version to the current version
	* NOTE: This only changes the format of the data and doesn't update the
	* actual HTML.
	*
	* @param	string	Template HTML
	* @return 	string	Template HTML
	*/
	function skin_update_template_bit( $html='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$debug = 0;
		$_html = $html;
		
		//-----------------------------------------
		// Fix up basic tags
		//-----------------------------------------
		
		$html = preg_replace( "/{ipb\.script_url}/i", '{$this->ipsclass->base_url}'  , $html);
		$html = preg_replace( "/{ipb\.session_id}/i", '{$this->ipsclass->session_id}', $html);
		
		//-----------------------------------------
		// Fix up the IF statements
		//-----------------------------------------
		
		# IF / ELSE IF / ELSE
		$html = preg_replace_callback( "#(?:\s+?)?<(if=[\"'].+?[\"'])>(.+?)</if>\s+?<else (if=[\"'].+?[\"'])>(.+?)</if>\s+?<else>(.+?)</else>#is", array( &$this, '_func_fix_if_elseif_else' ), $html );
		# IF / ELSE IF
		$html = preg_replace_callback( "#(?:\s+?)?<(if=[\"'].+?[\"'])>(.+?)</if>\s+?<else (if=[\"'].+?[\"'])>(.+?)</if>#is", array( &$this, '_func_fix_if_elseif_else' ), $html );
		# IF / ELSE
		$html = preg_replace_callback( "#(?:\s+?)?<(if=[\"'].+?[\"'])>(.+?)</if>\s+?<else>(.+?)</else>#is", array( &$this, '_func_fix_if_elseif_else' ), $html );
		
		//-----------------------------------------
		// Sort out the IF content
		//-----------------------------------------
		
		$html = preg_replace_callback( "#<if=([\"'])(.+?)[\"']>#is", array( &$this, '_func_check_if_statement' ), $html );
		
		//-----------------------------------------
		// Sort out the rest of the tags...
		//-----------------------------------------
		
		$html = preg_replace( "#ipb\.(member|vars|skin|lang|input)#i", '$this->ipsclass->\\1', $html );
		
		#print "<pre>". htmlspecialchars( $html ); exit();
		
		if ( $debug )
		{
			$_string  = "\n===================================================";
			$_string .= "\n Date: ". date( 'r' );
			$_string .= "\n---ORIGINAL----------------------------------------";
			$_string .= "\n".$_html;
			$_string .= "\n---CONVERTED---------------------------------------";
			$_string .= "\n".$html;
			
			if ( $FH = @fopen( ROOT_PATH . 'cache/template_update_debug_log_'.date('m_d_y').'.cgi', 'a' ) )
			{
				@fwrite( $FH, $_string );
				@fclose( $FH );
			}
		}
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Fix up an if / else if / else statement
	/*-------------------------------------------------------------------------*/
	
	/*
	* <if="">
	*	MAIN IF
	* <else />
	*   <if="">
	*		ELSE IF
	*	<else />
	*		ELSE
	*	</if>
	* </if>
	*/
	function _func_fix_if_elseif_else( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$if           = '';
		$if_html      = '';
		$else_if      = '';
		$else_if_html = '';
		$else_html    = '';
		$formatted    = '';
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		if ( count( $matches ) == 6 )
		{
			$if           = trim( $matches[1] );
			$if_html      = trim( $matches[2] );
			$else_if      = trim( $matches[3] );
			$else_if_html = trim( $matches[4] );
			$else_html    = trim( $matches[5] );
		}
		else if ( count( $matches ) == 5 )
		{
			$if           = trim( $matches[1] );
			$if_html      = trim( $matches[2] );
			$else_if      = trim( $matches[3] );
			$else_if_html = trim( $matches[4] );
		}
		else
		{
			$if           = trim( $matches[1] );
			$if_html      = trim( $matches[2] );
			$else_html    = trim( $matches[3] );
		}
	
		//-----------------------------------------
		// OK...
		//-----------------------------------------
		
		if ( $if AND $else_if AND $else_html )
		{
			$formatted  = "<".$if.">\n";
			$formatted .= $if_html . "\n";
			$formatted .= "<else />\n";
			$formatted .= "\t<".$else_if.">\n";
			$formatted .= $else_if_html."\n";
			$formatted .= "\t<else />\n";
			$formatted .= $else_html . "\n";
			$formatted .= "\t</if>\n";
			$formatted .= "</if>\n";
		}
		else if ( $if AND $else_if )
		{
			$formatted  = "<".$if.">\n";
			$formatted .= $if_html . "\n";
			$formatted .= "<else />\n";
			$formatted .= "\t<".$else_if.">\n";
			$formatted .= $else_if_html."\n";
			$formatted .= "\t</if>\n";
			$formatted .= "</if>\n";
		}
		else if ( $if AND $else_html )
		{
			$formatted  = "<".$if.">\n";
			$formatted .= $if_html . "\n";
			$formatted .= "<else />\n";
			$formatted .= $else_html."\n";
			$formatted .= "</if>\n";
		}
		
		return $formatted;
	}
	
	//===================================================
	// Sort out left bit of comparison
	//===================================================
	
	function _func_fix_if_statement($left, $andor="", $fs="", $ls="")
	{
		$left = trim($this->_trim_slashes($left));
		
		if ( preg_match( "/^ipb\./", $left ) )
		{
			$left = preg_replace( "/^ipb\.(.+?)$/", '$this->ipsclass->'."\\1", $left );
		}
		else
		{
			$left = '$'.$left;
		}
		
		return $andor.$fs.$left.$ls;
	}
	
	//===================================================
	// Statement: Prep AND OR, etc
	//===================================================
	
	function _func_check_if_statement( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$quotes = $matches[1];
		$code   = $this->_trim_slashes( $matches[2] );
		
		$code = preg_replace( "/(^|and|or)(\s+)(.+?)(\s|$)/ise", "\$this->_func_fix_if_statement('\\3', '\\1', '\\2', '\\4')", ' '.$code );
		
		$code = preg_replace( '#\${1,}#i', '$', $code );
		$code = str_replace( '$($', '($', $code );
		
		return "<if=".$quotes.trim($code).$quotes.">";
	}
	
	//===================================================
	// Remove leading and trailing newlines
	//===================================================
	
	function _trim_slashes($code)
	{
		$code = str_replace( '\"' , '"', $code );
		$code = str_replace( "\\'", "'", $code );
		return $code;
	}
	
}

?>