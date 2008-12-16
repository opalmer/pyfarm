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
|   > $Date: 2007-03-29 06:51:39 -0400 (Thu, 29 Mar 2007) $
|   > $Revision: 911 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Skin Tools
|   > Module written by Matt Mecham
|   > Date started: 22nd January 2004
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

class ad_skintools {

	var $base_url;
	var $db_html_files = "";
	var $ff_html_files = "";
	var $skin_id       = "";
	var $ff_fixes      = array();
	var $log           = array();
	
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
	var $perm_child = "skintools";


	function auto_run()
	{
		$this->ipsclass->admin->page_detail = "Please read the instructions for each tool carefully.";
		$this->ipsclass->admin->page_title  = "Skin Set Tools";
		$this->ipsclass->admin->nav[] 		= array( $this->ipsclass->form_code, 'Skin Tools' );

		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'rebuildcaches':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->rebuildcaches();
				break;
				
			case 'rewritemastercache':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->rewrite_master_cache();
				break;
			
			case 'rebuildmastermacros':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->rewrite_master_macros();
				break;
				
			case 'rebuildmaster':
				$this->rebuildmaster();
				break;
				
			case 'rebuildmasterhtml':
				$this->rebuildmaster_html();
				break;
				
			case 'rebuildmastercomponents':
				$this->rebuildmaster_components();
				break;
				
			case 'changemember':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':changemember' );
				$this->change_member();
				break;
				
			case 'changeforum':
				$this->change_forum();
				break;				
				
			//-----------------------------------------
			// Search stuff
			//-----------------------------------------
			
			case 'searchsplash':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->searchreplace_start();
				break;
				
			case 'simplesearch':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->simple_search();
				break;
				
			case 'searchandreplace':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->search_and_replace();
				break;
				
			//-----------------------------------------
			// Search stuff
			//-----------------------------------------
			
			case 'easylogo':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->easy_logo_start();
				break;
			case 'easylogo_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->easy_logo_complete();
				break;
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->show_intro();
				break;
		}
	}
	
	//-----------------------------------------
	// REBUILD MASTER MACROS
	//-----------------------------------------
	
	function rewrite_master_macros()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$file     = ROOT_PATH . 'resources/macro.xml';
		$macros   = array();
		$updated  = 0;
		$inserted = 0;
		
		//-----------------------------------------
		// CHECK
		//-----------------------------------------
		
		if ( ! file_exists( $file ) )
		{
			$this->ipsclass->main_msg = "$file could not be found. Please check, upload or try again";
			$this->show_intro();
		}
		
		//-----------------------------------------
		// Get current macros
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'skin_macro',
												 'where'  => 'macro_set=1' ) );
												
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$macros[ $row['macro_value'] ] = $row['macro_replace'];
		}
		
		//-----------------------------------------
		// Get XML
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();		
				
		//-----------------------------------------
		// Get XML file
		//-----------------------------------------
		
		$skin_content = implode( "", file($file) );
		
		//-----------------------------------------
		// Unpack the datafile (TEMPLATES)
		//-----------------------------------------
		
		$xml->xml_parse_document( $skin_content );
		
		//-----------------------------------------
		// Check macros
		//-----------------------------------------
		
		if ( ! is_array( $xml->xml_array['macroexport']['macrogroup']['macro'] ) )
		{
			$this->ipsclass->main_msg = "Error with macros.xml - could not process XML properly";
			$this->show_intro();
		}
	
		foreach( $xml->xml_array['macroexport']['macrogroup']['macro'] as $entry )
		{
			$_key = $entry[ 'macro_value' ]['VALUE'];
			$_val = $entry[ 'macro_replace' ]['VALUE'];
			
			if ( $macros[ $_key ] )
			{
				$updated++;
				
				$this->ipsclass->DB->do_update( 'skin_macro', array( 'macro_value'   => $_key,
																	 'macro_replace' => $_val ), "macro_set=1 AND macro_value='".$this->ipsclass->DB->add_slashes( $_key )."'" );
			}
			else
			{
				$inserted++;
				
				$this->ipsclass->DB->do_insert( 'skin_macro', array( 'macro_set'     => 1,
																	 'macro_value'   => $_key,
																	 'macro_replace' => $_val  ) );
			}
		}
		
		$this->ipsclass->cache_func->_recache_macros( 1, -1 );

		$this->ipsclass->main_msg = "$updated macros updated, $inserted added.";
		$this->show_intro();
	}
	
	/*-------------------------------------------------------------------------*/
	// Rebuild Master System Skin Set
	/*-------------------------------------------------------------------------*/
	
	/**
	* Rebuild Master System Templates from cacheid_1 directory
	*
	* @return	void
	*/
	function rewrite_master_cache()
	{
		$this->ipsclass->cache_func->_recache_templates( 1, -1, 0, 1, 1 );
		
		$this->ipsclass->main_msg .= implode("<br />", $this->ipsclass->cache_func->messages);
		
		$this->show_intro();
	}
	
	//-----------------------------------------
	// EASY LOGO CHANGER (COMPLETE)
	//-----------------------------------------
	
	function easy_logo_complete()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$master = array();
		
		//-----------------------------------------
		// Check id
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['set_skin_set_id'] )
		{
			$this->ipsclass->main_msg = "No skin set ID was passed. Please ensure you actually chose a skin set to edit";
			$this->easy_logo_start();
		}
		
		//-----------------------------------------
		// Grab the default template bit
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "group_name='skin_global' AND func_name='global_board_header'" ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$master[ $r['set_id'] ] = $r;
		}
		
		if ( !isset($master[ $this->ipsclass->input['set_skin_set_id'] ]) OR !is_array($master[ $this->ipsclass->input['set_skin_set_id'] ]) )
		{
			$final_html = $master[1]['section_content'];
		}
		else
		{
			$final_html = $master[ $this->ipsclass->input['set_skin_set_id'] ]['section_content'];
		}
		
		if ( ! strstr( $final_html, '<!--ipb.logo.end-->' ) )
		{
			$this->ipsclass->main_msg = "Cannot locate the logo image tags for this skin set - please make sure your templates are up to date.";
			$this->easy_logo_start();
		}
		
		//-----------------------------------------
		// Upload or new logo?
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			if ( ! $_POST['logo_url'] )
			{
				$this->ipsclass->main_msg = "You must either upload a new logo or enter a URL";
				$this->easy_logo_start();
			}
			
			$newlogo = $_POST['logo_url'];
		}
		else
		{
			if ( ! is_writable( CACHE_PATH.'style_images' ) )
			{
				$this->ipsclass->main_msg = "You must ensure that 'style_images' has the correct CHMOD value to allow PHP to write into it. Try 0777 if all else fails.";
				$this->easy_logo_start();
			}
			
			//-----------------------------------------
			// Upload
			//-----------------------------------------
			
			$FILE_NAME = $_FILES['FILE_UPLOAD']['name'];
			$FILE_SIZE = $_FILES['FILE_UPLOAD']['size'];
			$FILE_TYPE = $_FILES['FILE_UPLOAD']['type'];
			
			//-----------------------------------------
			// Silly spaces
			//-----------------------------------------
			
			$FILE_NAME = preg_replace( "/\s+/", "_", $FILE_NAME );
			
			//-----------------------------------------
			// Naughty Opera adds the filename on the end of the
			// mime type - we don't want this.
			//-----------------------------------------
			
			$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
			
			//-----------------------------------------
			// Correct file type?
			//-----------------------------------------
			
			if ( ! preg_match( "#\.(?:gif|jpg|jpeg|png)$#is", $FILE_NAME ) )
			{
				$this->ipsclass->main_msg = "The file you uploaded is not in the correct format. It has to be either a GIF, JPEG or PNG image.";
				$this->easy_logo_start();
			}
			
			if ( move_uploaded_file( $_FILES[ 'FILE_UPLOAD' ]['tmp_name'], CACHE_PATH."style_images/{$this->ipsclass->input['set_skin_set_id']}_".$FILE_NAME) )
			{
				@chmod( CACHE_PATH."style_images/{$this->ipsclass->input['set_skin_set_id']}_".$FILE_NAME, 0777 );
			}
			else
			{
				$this->ipsclass->main_msg = "The upload failed. Please check permissions on the 'style_images' directory and make sure the uploaded file is less that 2mb in size.";
				$this->easy_logo_start();
			}
			
			$newlogo = "style_images/{$this->ipsclass->input['set_skin_set_id']}_".urlencode($FILE_NAME);
		}
		
		//-----------------------------------------
		// Convert back stuff
		//-----------------------------------------
		
		foreach( array( 'headerhtml', 'javascripthtml', 'leftlinkshtml', 'rightlinkshtml' ) as $mail )
		{
			//$_POST[ $mail ] = $this->ipsclass->admin->form_to_text( $_POST[ $mail ] );
			//$_POST[ $mail ] = str_replace( "\r\n", "\n", $_POST[ $mail ] );
		}
		
		//-----------------------------------------
		// Okay! Form the template
		//-----------------------------------------
		
		//$final_html = $_POST['headerhtml'];
		//$final_html = str_replace( "<{BOARD_LOGO}>", "<!--ipb.logo.start--><img src='$newlogo' alt='IPB' style='vertical-align:top' border='0' /><!--ipb.logo.end-->"      , $final_html );
		//$final_html = str_replace( "<{JAVASCRIPT}>", "<!--ipb.javascript.start-->\n{$_POST['javascripthtml']}\n<!--ipb.javascript.end-->"       , $final_html );
		//$final_html = str_replace( "<{LEFT_HAND_SIDE_LINKS}>", "<!--ipb.leftlinks.start-->{$_POST['leftlinkshtml']}<!--ipb.leftlinks.end-->"    , $final_html );
		//$final_html = str_replace( "<{RIGHT_HAND_SIDE_LINKS}>", "<!--ipb.rightlinks.start-->{$_POST['rightlinkshtml']}<!--ipb.rightlinks.end-->", $final_html );
		
		$final_html = preg_replace( "#<!--ipb.logo.start-->.+?<!--ipb.logo.end-->#si", "<!--ipb.logo.start--><img src='$newlogo' alt='IPB' style='vertical-align:top' border='0' /><!--ipb.logo.end-->"      , $final_html );
		
		//-----------------------------------------
		// Update the DeeBee
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_templates', 'where' => "set_id=".intval($this->ipsclass->input['set_skin_set_id'])." AND group_name='skin_global' AND func_name='global_board_header'" ) );
		
		$this->ipsclass->DB->do_insert( 'skin_templates', array( 'section_content' => $final_html,
																 'set_id'          => $this->ipsclass->input['set_skin_set_id'],
																 'group_name'      => 'skin_global',
																 'func_name'       => 'global_board_header',
																 'func_data'       => '$component_links=""'
									 )                         );
		
		$this->ipsclass->cache_func->_rebuild_all_caches(array($this->ipsclass->input['set_skin_set_id']));
		
		$this->ipsclass->main_msg = 'Logo Changed and Skin Set Caches Rebuilt (id: '.$this->ipsclass->input['set_skin_set_id'].')';
			
		$this->ipsclass->main_msg .= "<br />".implode("<br />", $this->ipsclass->cache_func->messages);
		
		$this->easy_logo_start();
	}
	
	//-----------------------------------------
	// EASY LOGO CHANGER (START)
	//-----------------------------------------
	
	function easy_logo_start()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$master    = array();
		$skin_list = "";
		$html      = array();
		
		//-----------------------------------------
		// Grab the default template bit
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "group_name='skin_global' AND func_name='global_board_header'" ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$master[ $r['set_id'] ] = $r;
		}
		
		if ( ! $master[1]['section_content'] )
		{
			$this->ipsclass->main_msg = "Cannot locate the master template bit 'global_board_header'";
			$this->show_intro();
		}
		
		if ( ! strstr( $master[1]['section_content'], '<!--ipb.logo.end-->' ) )
		{
			$this->ipsclass->main_msg = "Cannot locate the logo image tags - please make sure your templates are up to date.";
			$this->show_intro();
		}
		
		//-----------------------------------------
		// Get Skin Names
		//-----------------------------------------
		
		$skin_list = $this->_get_skinlist( 1 );
		
		//-----------------------------------------
		// get URL
		//-----------------------------------------
		
		preg_match( "#<!--ipb.logo.start--><img src=[\"'](.+?)[\"'].+?<!--ipb.logo.end-->#si", $master[1]['section_content'], $match );
		
		$current_img_url = $match[1];
		
		//-----------------------------------------
		// get current HTML
		//-----------------------------------------
		
		$current_html = $master[1]['section_content'];
		
		$current_html = preg_replace( "#<!--ipb.javascript.start-->.+?<!--ipb.javascript.end-->#is"               , "<{JAVASCRIPT}>"                   , $current_html );
		$current_html = preg_replace( "#<!--ipb.logo.start--><img src=[\"'](.+?)[\"'].+?<!--ipb.logo.end-->#si"   , "<{BOARD_LOGO}>"                   , $current_html );
		$current_html = preg_replace( "#<!--ipb.leftlinks.start-->.+?<!--ipb.leftlinks.end-->#si"                 , "<{LEFT_HAND_SIDE_LINKS}>"         , $current_html );
		$current_html = preg_replace( "#<!--ipb.rightlinks.start-->.+?<!--ipb.rightlinks.end-->#si"               , "<{RIGHT_HAND_SIDE_LINKS}>"        , $current_html );
		
		//-----------------------------------------
		// Regex out me bits
		//-----------------------------------------
		
		preg_match( "#<!--ipb.javascript.start-->(.+?)<!--ipb.javascript.end-->#si", $master[1]['section_content'], $match );
		$html['javascript'] = $this->ipsclass->admin->text_to_form($match[1]);
		
		preg_match( "#<!--ipb.leftlinks.start-->(.+?)<!--ipb.leftlinks.end-->#si"  , $master[1]['section_content'], $match );
		$html['leftlinks']  = $this->ipsclass->admin->text_to_form($match[1]);
		
		preg_match( "#<!--ipb.rightlinks.start-->(.+?)<!--ipb.rightlinks.end-->#si"  , $master[1]['section_content'], $match );
		$html['rightlinks']  = $this->ipsclass->admin->text_to_form($match[1]);
		
		$current_html        = $this->ipsclass->admin->text_to_form($current_html);
		
		//-----------------------------------------
		// Can we upload into style_images?
		//-----------------------------------------
		
		$warning = ! is_writable( CACHE_PATH.'style_images' ) ? "<div class='redbox' style='padding:4px'><strong>WARNING: Unable to upload into 'style_images'. If you wish to upload a file, please CHMOD that directory now!</strong></div>" : '';
		
		//-----------------------------------------
		// Start the form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'          ),
															     2 => array( 'code' , 'easylogo_complete'  ),
															     3 => array( 'MAX_FILE_SIZE', '10000000000' ),
															     4 => array( 'section', $this->ipsclass->section_code ),
													 ) , "uploadform", " enctype='multipart/form-data'"     );
													 
									     
		$this->ipsclass->html .= "<div class='tableborder'>
							<div class='tableheaderalt'>Easy Logo Changer</div>
							<div class='tablepad' style='background-color:#EAEDF0'>
							$warning
							<fieldset class='tdfset'>
							 <legend><strong>Configuration</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Apply to which skin set?<div class='graytext'>If you've already modified the board header via the template editing section, this will overwrite your modifications</div></td>
							   <td width='60%' class='tablerow1'>$skin_list</td>
							 </tr>
							 <tr>
							   <td width='40%' class='tablerow1'>URL to new logo<div class='graytext'>You can use a relative URL or a full URL starting with http://</div></td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_simple_input('logo_url', ( isset($_POST['logo_url']) AND $_POST['logo_url'] ) ? $_POST['logo_url'] : $current_img_url, '60' )."</td>
							 </tr>
							 <tr>
							   <td width='40%' class='tablerow1'><b><u>OR</u></b> upload a new logo<div class='graytext'>Browse your computer for a logo to upload. Filename must end in .gif, .jpg, .jpeg or .png</div></td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_upload()."</td>
							 </tr>
							</table>
							</fieldset>
							</div>
							</div>";
							
		//-----------------------------------------
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form_standalone("Complete Edit");
		
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	// REBUILD MASTER COMPONENTS
	//-----------------------------------------
	
	function rebuildmaster_components()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$file    = ROOT_PATH . 'resources/skinsets.xml';
		
		if ( ! file_exists( $file ) )
		{
			$this->ipsclass->main_msg = "$file could not be found. Please check, upload or try again";
			$this->show_intro();
		}
		
		//-----------------------------------------
		// Get XML
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();		
				
		//-----------------------------------------
		// Get XML file (CSS/WRAPPERS)
		//-----------------------------------------
		
		$skin_content = implode( "", file($file) );
		
		//-----------------------------------------
		// Unpack the datafile (TEMPLATES)
		//-----------------------------------------
		
		$xml->xml_parse_document( $skin_content );
		
		//-----------------------------------------
		// (TEMPLATES)
		//-----------------------------------------

		if ( ! $xml->xml_array['export']['group']['row'][0]['set_css']['VALUE'] OR ! $xml->xml_array['export']['group']['row'][0]['set_wrapper']['VALUE'] )
		{
			$this->ipsclass->main_msg = "Error with resources/ipb_templates.xml - could not process XML properly";
			$this->show_intro();
		}
		else
		{		
			$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_css'           => $xml->xml_array['export']['group']['row'][0]['set_css']['VALUE'],
																'set_cache_css'     => $xml->xml_array['export']['group']['row'][0]['set_css']['VALUE'],
																'set_wrapper'       => $xml->xml_array['export']['group']['row'][0]['set_wrapper']['VALUE'],
																'set_cache_wrapper' => $xml->xml_array['export']['group']['row'][0]['set_wrapper']['VALUE'],
															  ), 'set_skin_set_id=1' );
		}
		
		$this->ipsclass->main_msg = "Master Template Components Updated";
		$this->show_intro();
	}
	
	//-----------------------------------------
	// REBUILD MASTER HTML
	//-----------------------------------------
	
	function rebuildmaster_html()
	{
		$master  = array();
		$inserts = 0;
		$updates = 0;
		
		//-----------------------------------------
		// Template here?
		//-----------------------------------------
		
		if ( ! file_exists( ROOT_PATH.'resources/ipb_templates.xml' ) )
		{
			$this->ipsclass->main_msg = "resources/ipb_templates.xml cannot be found in the forums root directory. Please check, upload or try again";
			$this->show_intro();
		}
		
		//-----------------------------------------
		// First, get all the default bits
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'suid,group_name,func_name', 'from' => 'skin_templates', 'where' => 'set_id=1' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$master[ strtolower( $r['group_name'] ) ][ strtolower( $r['func_name'] ) ] = $r['suid'];
		}
		
		//-----------------------------------------
		// Get XML
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		//-----------------------------------------
		// Get XML file (TEMPLATES)
		//-----------------------------------------
		
		$xmlfile = ROOT_PATH.'resources/ipb_templates.xml';
		
		$setting_content = implode( "", file($xmlfile) );
		
		//-----------------------------------------
		// Unpack the datafile (TEMPLATES)
		//-----------------------------------------
		
		$xml->xml_parse_document( $setting_content );
		
		//-----------------------------------------
		// (TEMPLATES)
		//-----------------------------------------
		
		if ( ! is_array( $xml->xml_array['templateexport']['templategroup']['template'] ) )
		{
			$this->ipsclass->main_msg = "Error with resources/ipb_templates.xml - could not process XML properly";
			$this->show_intro();
		}
	
		foreach( $xml->xml_array['templateexport']['templategroup']['template'] as $entry )
		{
			$newrow = array();
			
			$newrow['group_name']            = $entry[ 'group_name' ]['VALUE'];
			$newrow['section_content']       = $entry[ 'section_content' ]['VALUE'];
			$newrow['func_name']             = $entry[ 'func_name' ]['VALUE'];
			$newrow['func_data']             = $entry[ 'func_data' ]['VALUE'];
			$newrow['group_names_secondary'] = $entry[ 'group_names_secondary' ]['VALUE'];
			$newrow['set_id']                = 1;
			$newrow['updated']               = time();
			
			if ( $master[ strtolower( $newrow['group_name'] ) ][ strtolower( $newrow['func_name'] ) ] )
			{
				//-----------------------------------------
				// Update
				//-----------------------------------------
				
				$updates++;
				
				$this->ipsclass->DB->do_update( 'skin_templates', $newrow, 'suid='.$master[ strtolower( $newrow['group_name'] ) ][ strtolower( $newrow['func_name'] ) ] );
			}
			else
			{
				//-----------------------------------------
				// Insert
				//-----------------------------------------
				
				$inserts++;
				
				$this->ipsclass->DB->do_insert( 'skin_templates', $newrow );
			}
		}
		
		$this->ipsclass->main_msg = "Master template set rebuilt!<br />$updates updated template bits, $inserts new template bits";
		
		$this->show_intro();
	}
	
	//-----------------------------------------
	// COMPLEX SEARCH
	//-----------------------------------------
	
	function search_and_replace()
	{
		//-----------------------------------------
		// Get $skin_names stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/skin_info.php' );
		
		$SEARCH_set  = intval( $this->ipsclass->input['set_skin_set_id'] );
		$SEARCH_all  = intval( $this->ipsclass->input['searchall'] );
		
		//-----------------------------------------
		// Get set stuff
		//-----------------------------------------
		
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$SEARCH_set ) ); 
		
		//-----------------------------------------
		// Clean up before / after
		//-----------------------------------------
		
		$before = $this->ipsclass->txt_stripslashes($_POST['searchfor']);
		$after  = $this->ipsclass->txt_stripslashes($_POST['replacewith']);
		$before = str_replace( '"', '\"', $before );
		$after  = str_replace( '"', '\"', $after  );
		
		if ( ! $before )
		{
			$this->ipsclass->main_msg = "You must enter a 'search for' string before continuing.";
			$this->searchreplace_start();
		}
		
		//-----------------------------------------
		// Clean up regex
		//-----------------------------------------
		
		if ( $this->ipsclass->input['regexmode'] )
		{
			$before = str_replace( '#', '\#', $before );
			
			//-----------------------------------------
			// Test to ensure they are legal
			// - catch warnings, etc
			//-----------------------------------------
			
			ob_start();
			eval( "preg_replace( \"#{$before}#i\", \"{$after}\", '' );");
			$return = ob_get_contents();
			ob_end_clean();
			
			if ( $return )
			{
				$this->ipsclass->main_msg = "There was an error processing the 'search for' and 'replace with' variables - please ensure that they are legal regular expressions before continuing.";
				$this->searchreplace_start();
			}
		}
		
		//-----------------------------------------
		// we're here, so it's good
		//-----------------------------------------
		
		$templates = array();
		$the_templates = array();
		$matches   = 0;
		
		if ( $SEARCH_all )
		{
			$the_templates = $this->ipsclass->cache_func->_get_templates( $this_set['set_skin_set_id'], $this_set['set_skin_set_parent'], 'all' );
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'set_id='.$SEARCH_set ) );
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$the_templates[ $r['group_name'] ][ strtolower($r['func_name']) ] = $r;
			}
		}
		
		if( count($the_templates) && is_array($the_templates) )
		{
			foreach( $the_templates as $group_name => $group_data )
			{
				foreach( $group_data as $func_name => $template_data )
				{
					if ( $this->ipsclass->input['regexmode'] )
					{
						if ( preg_match( "#{$before}#i", $template_data['section_content'] ) )
						{
							$templates[ $group_name ][ $func_name ] = $template_data;
							$matches++;
						}
					}
					else if ( strstr( $template_data['section_content'], $before ) )
					{
						$templates[ $group_name ][ $func_name ] = $template_data;
						$matches++;
					}
				}
			}
		}
		

		/*$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'set_id='.$SEARCH_set ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $this->ipsclass->input['regexmode'] )
			{
				if ( preg_match( "#{$before}#i", $r['section_content'] ) )
				{
					$templates[ $r['group_name'] ][ strtolower($r['func_name']) ] = $r;
					$matches++;
				}
			}
			else if ( strstr( $r['section_content'], $before ) )
			{
				$templates[ $r['group_name'] ][ strtolower($r['func_name']) ] = $r;
				$matches++;
			}
		}*/
		
		//-----------------------------------------
		// No matches...
		//-----------------------------------------
		
		if ( ! count($templates) )
		{
			$this->ipsclass->html .= "<div class='tableborder'>
								 <div class='tableheaderalt'>Search & Replace Results</div>
								 <div class='tablepad'>
								  <b>You searched for: ".stripslashes(htmlspecialchars($before))."</b>
								  <br />
								  <br />
								  Unfortunately your search didn't return any matches. Please try again and broaden your search terms.
								 </div>
								</div>";
			
			$this->ipsclass->admin->output();
		}
		
		//-----------------------------------------
		// Swapping or showing?
		//-----------------------------------------
		
		if ( $this->ipsclass->input['testonly'] )
		{
			$this->ipsclass->html .= "<div class='tableborder'>
								 <div class='tableheaderalt'>Search & Replace Results</div>
								 <div class='tablepad' style='padding:5px'><b style='font-size:12px'>{$matches} matches for '".htmlentities($before)."' to be replaced with '".htmlentities($after)."'</b><br /><br />";
								 
			//-----------------------------------------
			// Go fru dem all and print..
			//-----------------------------------------
			
			foreach( $templates as $group => $d )
			{
				foreach( $templates[ $group ] as $tmp_data )
				{
					if ( isset($skin_names[ $group ]) )
					{
						$group_name = $skin_names[ $group ][0];
					}
					else
					{
						$group_name = $group;
					}
					
					$html = $tmp_data['section_content'];
					
					//-----------------------------------------
					// Decode...
					//-----------------------------------------
					
					$hl    = $before;
					$after = str_replace( '\\\\', '\\\\\\', $after );
					
					if ( ! $after )
					{
						$hl   = preg_replace( "#\((.+?)\)#s", "(?:\\1)", $hl );
						$html = preg_replace( "#({$hl})#si" , '{#-^--opentag--^-#}'."\\1".'{#-^--closetag--^-#}', $html );
					}
					else
					{
						//-----------------------------------------
						// Wrap tags (so we don't use
						// < >, etc )
						//-----------------------------------------
						
						$html = preg_replace( "#{$hl}#si", '{#-^--opentag--^-#}'.$after.'{#-^--closetag--^-#}', $html );
					}
					
					//-----------------------------------------
					// Clean up..
					//-----------------------------------------
					
					$html = str_replace( "{#-^--opentag--^-#}\\", '{#-^--opentag--^-#}', $html );
					
					//-----------------------------------------
					// convert to printable html
					//-----------------------------------------
					
					$html = str_replace( "<" , "&lt;"  , $html);
					$html = str_replace( ">" , "&gt;"  , $html);
					$html = str_replace( "\"", "&quot;", $html);
					
					$html = preg_replace( "!&lt;\!--(.+?)(//)?--&gt;!s"              , "&#60;&#33;<span style='color:red'>--\\1--\\2</span>&#62;", $html );
					$html = preg_replace( "#&lt;([^&<>]+)&gt;#s"                     , "<span style='color:blue'>&lt;\\1&gt;</span>"             , $html );   //Matches <tag>
					$html = preg_replace( "#&lt;([^&<>]+)=#s"                        , "<span style='color:blue'>&lt;\\1</span>="                , $html );   //Matches <tag
					$html = preg_replace( "#&lt;/([^&]+)&gt;#s"                      , "<span style='color:blue'>&lt;/\\1&gt;</span>"            , $html );   //Matches </tag>
					$html = preg_replace( "!=(&quot;|')([^<>])(&quot;|')(\s|&gt;)!s" , "=\\1<span style='color:purple'>\\2</span>\\3\\4"         , $html );   //Matches ='this'
					
					//-----------------------------------------
					// convert back wrap tags
					//-----------------------------------------
					
					$html = str_replace( '{#-^--opentag--^-#}' , "<span style='color:red;font-weight:bold;background-color:yellow'>", $html );
					$html = str_replace( '{#-^--closetag--^-#}', "</span>", $html );
			
					$this->ipsclass->html .= "<div class='tableborder'>
										 <div class='tableheaderalt'>{$group_name} &middot; {$tmp_data['func_name']}</div>
										 <div class='tablerow2' style='height:100px;overflow:auto'><pre>{$html}</pre></div>
										</div>
										<br />";
				}
			}
			
			$this->ipsclass->html .= "</div></div>";
			
			$this->ipsclass->admin->nav[] = array( "", "Search results from set ".$this_set['set_name'] );
			
			$this->ipsclass->admin->output();
		}
		else
		{
			//-----------------------------------------
			// Jus' do iiit
			//-----------------------------------------
			
			$after  = str_replace( '\\\\', '\\\\\\', $after );
			$report = array();
			
			foreach( $templates as $group => $d )
			{
				foreach( $templates[ $group ] as $tmp_data )
				{
					if ( $this->ipsclass->input['regexmode'] )
					{
						$tmp_data['section_content'] = preg_replace( "#{$before}#si", $after, $tmp_data['section_content'] );
						
					}
					else
					{
						$tmp_data['section_content'] = str_replace( $before, $after, $tmp_data['section_content'] );
					}
					
					$do_insert = 0;
					$insert_array = array();
					
					// Protect master templates...
					if( $tmp_data['set_id'] == 1 )
					{
						$tmp_data['set_id'] = $SEARCH_set;
					
						$quick_check = $this->ipsclass->DB->simple_exec_query( array( 'select' => "COUNT(*) as thecnt", 'from' => 'skin_templates', 
														'where' => "group_name='{$tmp_data['group_name']}' AND func_name='{$tmp_data['func_name']}' AND set_id='{$tmp_data['set_id']}'" ) );

						if( $quick_check['thecnt'] == 0 )
						{
							$do_insert = 1;
						}
					}

					if( !$do_insert )
					{
						//-----------------------------------------
						// Update DB
						//-----------------------------------------
						
						$this->ipsclass->DB->do_update( 'skin_templates', array( 'section_content' => $tmp_data['section_content'] ), 'suid='.$tmp_data['suid'] );
					}
					else
					{
						$insert_array = array( 'set_id' 			=> $tmp_data['set_id'],
												'group_name' 		=> $tmp_data['group_name'],
												'func_name' 		=> $tmp_data['func_name'],
												'section_content' 	=> $tmp_data['section_content'],
												'func_data' 		=> $tmp_data['func_data'],
												'updated' 			=> time(),
												'can_remove' 		=> 1
											 );
						
						$this->ipsclass->DB->do_insert( 'skin_templates', $insert_array );
					}
					
					$report[] = $tmp_data['func_name'].' updated...';
				}
			}
			
			//-----------------------------------------
			// Recache skin template..
			//-----------------------------------------
			
			$this->ipsclass->cache_func->_recache_templates( $SEARCH_set, $this_set['set_skin_set_parent'] );
			$report[] = "Templates recached for set {$this_set['set_name']}";
			
			$this->ipsclass->main_msg = implode( "<br />", $report );
			$this->searchreplace_start();
		}
	}
	
	//-----------------------------------------
	// SIMPLE SEARCH
	//-----------------------------------------
	
	function simple_search()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rawword   = $_GET['searchkeywords'] ? urldecode( $_GET['searchkeywords'] ) : $_POST['searchkeywords'];
 		$templates = array();
		$final     = array();
		$matches   = array();
		
		//-----------------------------------------
		// CLEAN UP
		//-----------------------------------------
		
		$SEARCH_word = trim( $this->ipsclass->txt_safeslashes( $rawword ) );
		$SEARCH_safe = urlencode( $SEARCH_word );
		$SEARCH_all  = intval( $this->ipsclass->input['searchall'] );
		$SEARCH_set  = intval( $this->ipsclass->input['set_skin_set_id'] );
		
		//-----------------------------------------
		// check (please?)
		//-----------------------------------------
		
		if ( ! $SEARCH_word )
		{
			$this->ipsclass->main_msg = "You must enter a search word";
			$this->searchreplace_start();
		}
		
		//-----------------------------------------
		// Get set stuff
		//-----------------------------------------
		
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$SEARCH_set ) ); 
		
		if ( ! $this_set['set_skin_set_id'] )
		{
			$this->ipsclass->main_msg = "No such set was found in the DB";
			$this->searchreplace_start();
		}
		
		//-----------------------------------------
		// Get templates from DB
		//-----------------------------------------
		
		if ( $SEARCH_all )
		{
			$templates = $this->ipsclass->cache_func->_get_templates( $this_set['set_skin_set_id'], $this_set['set_skin_set_parent'], 'all' );
		}
		else
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'set_id='.$SEARCH_set ) );
			$this->ipsclass->DB->simple_exec();
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$templates[ $r['group_name'] ][ strtolower($r['func_name']) ] = $r;
			}
		}
		
		if ( ! count( $templates ) )
		{
			$this->ipsclass->main_msg = "Couldn't locate any templates to search in!";
			$this->searchreplace_start();
		}
		
		//-----------------------------------------
		// Go fru dem all and search
		//-----------------------------------------
		
		foreach( $templates as $group => $d )
		{
			foreach( $templates[ $group ] as $tmp_data )
			{
				if ( strstr( strtolower( $tmp_data['section_content'] ), strtolower( $SEARCH_word ) ) )
				{
					$final[ $group ][] = $tmp_data;
				}
			}
		}
		
		//-----------------------------------------
		// Print..
		//-----------------------------------------
		
		if ( ! count($final) )
		{
			$this->ipsclass->html .= "<div class='tableborder'>
								 <div class='tableheaderalt'>Search Results</div>
								 <div class='tablepad'>
								  <b>You searched for: ".htmlentities($SEARCH_word)."</b>
								  <br />
								  <br />
								  Unfortunately your search didn't return any matches. Please try again and broaden your search terms.
								 </div>
								</div>";
								
			$this->ipsclass->admin->output();
		}
		
		//-----------------------------------------
		// SET ids right
		//-----------------------------------------
		
		$this->ipsclass->input['id']   = $SEARCH_set;
		$this->ipsclass->input['p']    = $this_set['set_skin_set_parent'];
		$this->ipsclass->input['code'] = 'template-sections-list';
		$this->ipsclass->input['act']  = 'templ';
		$this->ipsclass->form_code     = 'section=lookandfeel&amp;act=templ';
		$this->ipsclass->form_code_js  = str_replace( '&amp;', '&', $this->ipsclass->form_code );
		
		//-----------------------------------------
		// Pass array
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/action_admin/skin_template_bits.php" );
		$temp              =  new ad_skin_template_bits();
		$temp->ipsclass    =& $this->ipsclass;
		$temp->search_bits =  $final;
		$temp->auto_run();
	}
	
	//-----------------------------------------
	// SEARCH & REPLACE SPLASH
	//-----------------------------------------
	
	function searchreplace_start()
	{
		$skin_list = $this->_get_skinlist( 1 );
		
		$this->ipsclass->admin->page_detail = "These tools will allow you to search for keywords and bulk replace HTML.";
		$this->ipsclass->admin->page_title  = "Skin Search & Replace";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'     ),
																			 2 => array( 'code' , 'simplesearch'  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Simple Search" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search for...</b><br /><span style='color:gray'>Enter a simple keyword or block of HTML to search for</span>",
															       $this->ipsclass->adskin->form_simple_input( 'searchkeywords', '', 30 )
													    )      );
													  
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search in set...</b>",
															     $skin_list
															     ."<br /><input type='checkbox' name='searchall' value='1'> Search in selected set and all parents including the master set."
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Search");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Search and replace
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'     ),
															     			 2 => array( 'code' , 'searchandreplace'  ),
															     	 		 4 => array( 'section', $this->ipsclass->section_code ),
													    )      );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Search and Replace" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search for...</b><br /><span style='color:gray'>Enter a keyword or a block of HTML to search for.<br />If enabling 'regex mode' you may enter a regular expression here.</span>",
															      $this->ipsclass->adskin->form_textarea( 'searchfor', $_POST['searchfor'] )
													    )      );
													  
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Replace with...</b><br /><span style='color:gray'>Enter the replacement block of HTML<br />If enabling 'regex mode' you may enter a regular expression here.</span>",
															     $this->ipsclass->adskin->form_textarea( 'replacewith', $_POST['replacewith'] )
													    )      );
													    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Search in set...</b><br /><span style='color:gray'>NOTE: The search and replace will only work on the specified skin set. The parent and master skin sets will NOT be searched or any replacements made on them.</span>",
															     $skin_list
															     ."<br /><input type='checkbox' name='searchall' value='1'> Search in selected set and all parents including the master set."
													    )      );
													    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Test Search and Replace Only?</b><br /><span style='color:gray'>If yes, no replacements will be made and you will be able to preview the changes.</span>",
															      $this->ipsclass->adskin->form_yes_no( 'testonly', 1 )
													    )      );
													    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Enable 'regex' mode?</b><br /><span style='color:gray'>If yes, you may use 'regex' in your search and replacements.
																 <br />Example:- Replace all &lt;br&gt; or &lt;br /&gt; with &lt;br clear='all' /&gt;
																 <br />Search for: <b>&lt;(br)&#92;s?/?&gt;</b>
																 <br />Replace with: <b>&lt;&#92;&#92;1 clear='all' /&gt;</b></span>",
															      $this->ipsclass->adskin->form_yes_no( 'regexmode', 0 )
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Search");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	// Swap members...
	//-----------------------------------------
	
	function change_member()
	{
		if( is_array($this->ipsclass->input['set_skin_set_id']) AND count($this->ipsclass->input['set_skin_set_id']) )
		{
			$this->ipsclass->input['set_skin_set_id'] = $this->ipsclass->clean_int_array($this->ipsclass->input['set_skin_set_id']);
			
			$query_bit = " IN (".implode(",",$this->ipsclass->input['set_skin_set_id']).")";
		}
		else
		{
			$this->ipsclass->main_msg = "You did not choose any skin(s) to remove from the member's choice";
			$this->show_intro();
			return;
		}
		
		$new_id = intval($this->ipsclass->input['set_skin_set_id2']);
		
		if ($new_id == 'n')
		{
			$this->ipsclass->DB->do_update( 'members', array( 'skin' => '' ), 'skin'.$query_bit );
		}
		else
		{
			$this->ipsclass->DB->do_update( 'members', array( 'skin' => $new_id ), 'skin'.$query_bit );
		}
		
		$this->ipsclass->main_msg = "Members updated";
		
		$this->show_intro();
	}
	
	//-----------------------------------------
	// Swap forums...
	//-----------------------------------------
	
	function change_forum()
	{
		if( is_array($this->ipsclass->input['set_skin_set_id']) AND count($this->ipsclass->input['set_skin_set_id']) )
		{
			$this->ipsclass->input['set_skin_set_id'] = $this->ipsclass->clean_int_array($this->ipsclass->input['set_skin_set_id']);
			
			$query_bit = " IN (".implode(",",$this->ipsclass->input['set_skin_set_id']).")";
		}
		else
		{
			$this->ipsclass->main_msg = "You did not choose any skin(s) to remove from the member's choice";
			$this->show_intro();
			return;
		}
		
		$new_id = intval($this->ipsclass->input['set_skin_set_id2']);
		
		if ($new_id == 'n')
		{
			$this->ipsclass->DB->do_update( 'forums', array( 'skin_id' => '' ), 'skin_id'.$query_bit );
		}
		else
		{
			$this->ipsclass->DB->do_update( 'forums', array( 'skin_id' => $new_id ), 'skin_id'.$query_bit );
		}
		
		$this->ipsclass->update_forum_cache();
		
		$this->ipsclass->main_msg = "Forums updated";
		
		$this->show_intro();
	}	
	
	//-----------------------------------------
	// REBUILD MASTER
	//-----------------------------------------
	
	function rebuildmaster()
	{
		$pid = intval($this->ipsclass->input['phplocation']);
		$cid = intval($this->ipsclass->input['csslocation']);
		
		if ( $this->ipsclass->input['phpyes'] )
		{
			if ( ! file_exists( CACHE_PATH.'cache/skin_cache/cacheid_'.$pid ) )
			{
				$this->ipsclass->main_msg = 'IPB cannot rebuild the master templates as the folder "cacheid_$pid" does not exist';
			}
			
			$this->ipsclass->cache_func->_rebuild_templates_from_php($pid);
			
			$this->ipsclass->main_msg = 'Attempting to rebuild master set from PHP cache files...';
				
			$this->ipsclass->main_msg .= "<br />".implode("<br />", $this->ipsclass->cache_func->messages);
		}
		
		if ( $this->ipsclass->input['cssyes'] )
		{
			if ( ! file_exists( CACHE_PATH.'style_images/css_'.$cid.'.css' ) )
			{
				$this->ipsclass->main_msg = 'IPB cannot rebuild the master CSS as the CSS "css_$cid" does not exist';
			}
			
			$css = @file_get_contents( CACHE_PATH.'style_images/css_'.$cid.'.css' );
			
			if ( ! $css )
			{
				$this->ipsclass->main_msg = 'IPB cannot rebuild the master CSS as the CSS "css_$cid" appears to be empty.';
			}
			
			$css = trim( preg_replace( "#^.*\*~START CSS~\*/#s", "", $css ) );
			
			//-----------------------------------------
			// Attempt to rearrange style_images dir stuff
			//-----------------------------------------
			
			$this->ipsclass->main_msg = 'Attempting to rebuild master CSS from CSS cache files...';
			
			$css = preg_replace( "#url\(([\"'])?(.+?)/(.+?)([\"'])?\)#is", "url(\\1style_images/1/\\3\\4)", $css );
			
			$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_css' => $css, 'set_cache_css' => $css, 'set_css_updated' => time() ), 'set_skin_set_id=1' );
			
			$this->ipsclass->cache_func->_write_css_to_cache(1);
			
			$this->ipsclass->main_msg .= "<br />".implode("<br />", $this->ipsclass->cache_func->messages);
		}
		
		$this->show_intro();
	}
	
	//-----------------------------------------
	// REBUILD CACHES
	//-----------------------------------------
	
	function rebuildcaches()
	{
		$this->ipsclass->cache_func->_rebuild_all_caches(array($this->ipsclass->input['set_skin_set_id']));
		
		$this->ipsclass->main_msg = 'Skin Set Caches Rebuilt (id: '.$this->ipsclass->input['set_skin_set_id'].')';
			
		$this->ipsclass->main_msg .= "<br />".implode("<br />", $this->ipsclass->cache_func->messages);
		
		$this->show_intro();
	}
	
	//-----------------------------------------
	// SHOW MAIN SCREEN
	//-----------------------------------------
	
	function show_intro()
	{
		$skin_list = $this->_get_skinlist();
		
		//-----------------------------------------
		// REBUILD MASTER TEMPLATES
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																			 2 => array( 'code' , 'rebuildmasterhtml'  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
													    			)      );
													    			
		//-----------------------------------------
		// Attempt to get filemtime
		//-----------------------------------------
		
		$filemtime  = 0;
		$file       = ROOT_PATH . 'resources/ipb_templates.xml';
		$error      = "";
		$notice     = "";
		$extra_html = "";
		
		if ( @file_exists( $file ) )
		{
			if ( $filemtime = @filemtime( $file ) )
			{
				$notice = "resources/ipb_templates.xml last updated: " . $this->ipsclass->get_date( $filemtime, 'JOINED' );
			}
			
			if ( $filemtime2 = @filemtime( ROOT_PATH . 'sources/ipsclass.php' ) )
			{
				if ( ( $filemtime2 - (86400 * 7) ) > $filemtime )
				{
					$error = "Please check resources/ipb_templates.xml - 'ipsclass.php' is more than a week newer.";
				}
			}
		}
		else
		{
			$error = "Cannot locate '{$file}' - please make sure a copy has been uploaded to the root forum directory";
		}
		
		//-----------------------------------------
		// Got notices?
		//-----------------------------------------
		
		if ( $notice )
		{
			$extra_html .= "<div class='input-ok-content'>$notice</div>";
		}
		
		if ( $error )
		{
			$extra_html .= "<div class='input-warn-content'>$error</div>";
		}
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rebuild Master Templates" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Running this tool will rebuild your master HTML templates that all your skins inherit from.</b>
																			  <br />After running, you may wish to rebuild your skin set caches to update them with the changes.
																			  $extra_html",
																	)      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// REBUILD MASTER CSS and BOARDWRAPPER
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																			 2 => array( 'code' , 'rebuildmastercomponents'  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
													    			)      );
													    			
		//-----------------------------------------
		// Attempt to get filemtime
		//-----------------------------------------
		
		$filemtime  = 0;
		$file       = ROOT_PATH . 'resources/skinsets.xml';
		$error      = "";
		$notice     = "";
		$extra_html = "";
		
		if ( @file_exists( $file ) )
		{
			if ( $filemtime = @filemtime( $file ) )
			{
				$notice = "resources/skinsets.xml last updated: " . $this->ipsclass->get_date( $filemtime, 'JOINED' );
			}
			
			if ( $filemtime2 = @filemtime( ROOT_PATH . 'sources/ipsclass.php' ) )
			{
				if ( ( $filemtime2 - (86400 * 7) ) > $filemtime )
				{
					$error = "Please check resources/skinsets.xml - 'ipsclass.php' is more than a week newer.";
				}
			}
		}
		else
		{
			$error = "Cannot locate '{$file}' - please make sure that the 'install' directory exists.";
		}
		
		//-----------------------------------------
		// Got notices?
		//-----------------------------------------
		
		if ( $notice )
		{
			$extra_html .= "<div class='input-ok-content'>$notice</div>";
		}
		
		if ( $error )
		{
			$extra_html .= "<div class='input-warn-content'>$error</div>";
		}
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rebuild Master Skin Components" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Running this tool will rebuild your master HTML wrapper and CSS.</b>
																			  <br />After running, you may wish to rebuild your skin set caches to update them with the changes.
																			  $extra_html",
																	)      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// REBUILD MASTER MACROS
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																			 2 => array( 'code' , 'rebuildmastermacros'  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
													    			)      );
													    			
		//-----------------------------------------
		// Attempt to get filemtime
		//-----------------------------------------
		
		$filemtime  = 0;
		$file       = ROOT_PATH . 'resources/macro.xml';
		$error      = "";
		$notice     = "";
		$extra_html = "";
		
		if ( @file_exists( $file ) )
		{
			if ( $filemtime = @filemtime( $file ) )
			{
				$notice = "resources/macro.xml last updated: " . $this->ipsclass->get_date( $filemtime, 'JOINED' );
			}
			
			if ( $filemtime2 = @filemtime( ROOT_PATH . 'sources/ipsclass.php' ) )
			{
				if ( ( $filemtime2 - (86400 * 7) ) > $filemtime )
				{
					$error = "Please check resources/macro.xml - 'ipsclass.php' is more than a week newer.";
				}
			}
		}
		else
		{
			$error = "Cannot locate '{$file}' - please make sure that the 'install' directory exists.";
		}
		
		//-----------------------------------------
		// Got notices?
		//-----------------------------------------
		
		if ( $notice )
		{
			$extra_html .= "<div class='input-ok-content'>$notice</div>";
		}
		
		if ( $error )
		{
			$extra_html .= "<div class='input-warn-content'>$error</div>";
		}
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "{none}"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rebuild Master Skin Macros" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Running this tool will rebuild your master macros.</b>
																			  <br />After running, you may wish to rebuild your skin set caches to update them with the changes.
																			  $extra_html",
																	)      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// REBUILD CACHES
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
															     			 2 => array( 'code' , 'rebuildcaches'  ),
															     			 4 => array( 'section', $this->ipsclass->section_code ),
													    )      );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rebuild Skin Set Cache" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rebuild skin set cache on set...</b><br /><span style='color:gray'>This option will rebuild the template HTML, wrapper, macro and css caches of this set and any children.</span><br />[ <a href='{$this->ipsclass->base_url}&section={$this->ipsclass->section_code}&act=sets&code=rebuildalltemplates'>Rebuild All</a> ]",
															     $skin_list
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// CHANGE MEMBERS 
		//-----------------------------------------
		
		$dd_two = str_replace( "select name='set_skin_set_id'", "select name='set_skin_set_id2'", $skin_list );
		$dd_two = str_replace( "<!--DD.OPTIONS-->", "<option value='n'>None - use the admin defaults</option>", $dd_two );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																			 2 => array( 'code' , 'changemember'  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
													   			    )      );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Update Members Skin Choice" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Where the member currently uses...</b>",
																			 str_replace( "select name='set_skin_set_id'", "select name='set_skin_set_id[]' multiple='multiple' size='6'", $skin_list )
																	)      );
													  
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Make them use...</b>",
															     $dd_two
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																			 2 => array( 'code' , 'changeforum'  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
													   			    )      );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Update Forum Skin Options" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Where the forum currently uses...</b>",
																			 str_replace( "select name='set_skin_set_id'", "select name='set_skin_set_id[]' multiple='multiple' size='6'", $skin_list )
																	)      );
													  
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Change forum skin option to...</b>",
															     $dd_two
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();		
		
		//-----------------------------------------
		// REBUILD MASTER
		//-----------------------------------------
		
		if ( IN_DEV )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																				 2 => array( 'code' , 'rebuildmaster'  ),
																				 4 => array( 'section', $this->ipsclass->section_code ),
																		)      );
											 
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rebuild Master Skin Set" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rebuild 'IPB Master Skin Set' FROM CSS AND PHP files.</b><br /><span style='color:gray'>This option will rebuild the template HTML for the master skin set. USE VERY CAREFULLY!</span>",
																				 "<input type='checkbox' name='phpyes' value='1' /> PHP cache dir.: skin_cache/cacheid_ ".$this->ipsclass->adskin->form_simple_input( 'phplocation', '1', 3 )."<br />".
																				 "<input type='checkbox' name='cssyes' value='1' /> CSS cache file: style_images/css_ ".$this->ipsclass->adskin->form_simple_input( 'csslocation', '1',3 )
																		)      );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
			
			//-----------------------------------------
			// Rewrite cache files to directory
			//-----------------------------------------

			$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'act'  , 'skintools'      ),
																     			 2 => array( 'code' , 'rewritemastercache'  ),
																     			 4 => array( 'section', $this->ipsclass->section_code ),
														    )      );

			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );

			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Rewrite cacheid_1 master skins from the DB" );

			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Rebuild cacheid_1 master skins...</b><br /><span style='color:gray'>This option will rewrite all your master cache skin files from the DB.</span>",
														    			)      );

			$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Run tool...");

			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
	
	}
	
	//-----------------------------------------
	// Get dropdown of skin
	//-----------------------------------------
	
	function _get_skinlist( $check_default=0 )
	{
		$skin_sets = array();
		$skin_list = "<select name='set_skin_set_id' class='dropdown'><!--DD.OPTIONS-->";
		
		//-----------------------------------------
		// Get formatted list of skin sets
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_sets', 'order' => 'set_skin_set_parent, set_skin_set_id' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $s = $this->ipsclass->DB->fetch_row() )
		{
			$skin_sets[ $s['set_skin_set_id'] ] = $s;
			$skin_sets[ $s['set_skin_set_parent'] ]['_children'][] = $s['set_skin_set_id'];
		}
		
		//-----------------------------------------
		// Roots
		//-----------------------------------------
		
		foreach( $skin_sets as $id => $data )
		{
			if ( isset($data['set_skin_set_parent']) AND $data['set_skin_set_parent'] < 1 and $id > 1 )
			{
				if( $check_default )
				{
					$default = $data['set_default'] ? " selected='selected'" : '';
				}
				
				$skin_list .= "\n<option value='$id'{$default}>{$data['set_name']}</option><!--CHILDREN:{$id}-->";
			}
		}
		
		//-----------------------------------------
		// Kids...
		//-----------------------------------------
		
		foreach( $skin_sets as $id => $data )
		{	
			if ( isset($data['_children']) AND is_array( $data['_children'] ) and count( $data['_children'] ) > 0 )
			{
				$html = "";
				
				foreach( $data['_children'] as $cid )
				{
					if( $check_default )
					{
						$default = $skin_sets[ $cid ]['set_default'] ? " selected='selected'" : '';
					}
					
					$html .= "\n<option value='$cid'{$default}>---- {$skin_sets[ $cid ]['set_name']}</option>";
				}
				
				$skin_list = str_replace( "<!--CHILDREN:{$id}-->", $html, $skin_list );
			}
		}
		
		$skin_list .= "</select>";
		
		return $skin_list;
	}
	
	//-----------------------------------------
	// Sort by group name
	//-----------------------------------------
	
	function perly_alpha_sort($a, $b)
	{
		return strcmp($a['easy_name'], $b['easy_name']);
	}
	
}


?>