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
|   > $Date: 2007-05-08 16:34:38 -0400 (Tue, 08 May 2007) $
|   > $Revision: 981 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Help Control functions
|   > Module written by Matt Mecham
|   > Date started: 2nd April 2002
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


class ad_help
{
	var $base_url;
	var $html;
	var $parser;
	var $han_editor;
	var $image_dir;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "tools";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "help";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Manage Help Files' );
		
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->show_form('edit');
				break;
			case 'new':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->show_form('new');
				break;
			
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->doedit();
				break;
				
			case 'doreorder':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->doreorder();
				break;				
				
			case 'donew':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->doadd();
				break;
				
			case 'remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove();
				break;
				
			case 'master_xml_export':
				$this->master_xml_export();
				break;
				
			case 'acp_help':
				$this->rebuild_acp_help();
				break;
				
			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_files();
				break;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Rebuild ACP Help
	/*-------------------------------------------------------------------------*/
	
	function rebuild_acp_help()
	{
		$new		= 0;
		$updated	= 0;
		$keys		= array();
		
		$this->ipsclass->DB->build_query( array( 'select' => 'page_key', 'from' => 'acp_help' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$keys[] = $r['page_key'];
		}
		
		require_once( KERNEL_PATH . 'class_xml.php' );
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( ROOT_PATH . 'resources/help_sections.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['helpsectionsexport']['helpsectionsgroup']['helpsections'] as $id => $entry )
		{
			$newrow = array(
							'is_setting'	=> 0,
							'page_key'		=> $entry['key']['VALUE'],
							'help_title'	=> $entry['title']['VALUE'],
							'help_body'		=> $entry['helptext']['VALUE'],
							);

			if( in_array( $newrow['page_key'], $keys ) )
			{
				$this->ipsclass->DB->do_update( 'acp_help', $newrow, "page_key='{$newrow['page_key']}'" );
				
				$updated++;
			}
			else
			{	
				$this->ipsclass->DB->do_insert( 'acp_help', $newrow );
				
				$new++;
			}
		}
		
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( ROOT_PATH . 'resources/help_settings.xml' ) );
		$xml->xml_parse_document( $content );
		
		foreach( $xml->xml_array['helpsettingsexport']['helpsettingsgroup']['helpsettings'] as $id => $entry )
		{
			$newrow = array(
							'is_setting'	=> 1,
							'page_key'		=> $entry['key']['VALUE'],
							'help_title'	=> $entry['title']['VALUE'],
							'help_body'		=> $entry['helptext']['VALUE'],
							'help_mouseover'=> $entry['mouseover']['VALUE'],
							);

			if( in_array( $newrow['page_key'], $keys ) )
			{
				$this->ipsclass->DB->do_update( 'acp_help', $newrow, "page_key='{$newrow['page_key']}'" );
				
				$updated++;
			}
			else
			{	
				$this->ipsclass->DB->do_insert( 'acp_help', $newrow );
				
				$new++;
			}
		}
		
		$this->ipsclass->main_msg = "{$new} new ACP help files and {$updated} updated ACP help files imported";
		
		$this->list_files();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Export Master XML
	/*-------------------------------------------------------------------------*/
	
	function master_xml_export()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];

		$xml->xml_set_root( 'export', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Set group
		//-----------------------------------------
		
		$xml->xml_add_group( 'group' );
		
		//-----------------------------------------
		// Get templates...
		//-----------------------------------------
	
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'faq'  ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			//-----------------------------------------
			// Sort the fields...
			//-----------------------------------------
			
			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'row', $content );
		}
		
		$xml->xml_add_entry_to_group( 'group', $entry );
		
		$xml->xml_format_document();
		
		$doc = $xml->xml_document;
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $doc, 'faq.xml', '', 0 );
	}
	
	//-----------------------------------------
	// HELP FILE FUNCTIONS
	//-----------------------------------------
	
	function doedit()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must pass a valid emoticon id, silly!");
		}
		
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        $this->ipsclass->load_skin();
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->init();
 		
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      	=  new parse_bbcode();
        $this->parser->ipsclass            	=& $this->ipsclass;
        $this->parser->allow_update_caches 	= 1;
        
        $this->parser->bypass_badwords 		= 1;		
        		
		if ($this->ipsclass->input['title'] == "")
		{
			$this->ipsclass->admin->error("You must enter a title, silly!");
		}
		
		$text = $this->han_editor->process_raw_post( 'text' );
		$this->parser->parse_smilies   = 1;
		$this->parser->parse_html      = 1;
		$this->parser->parse_bbcode    = 1;
		$text        					= $this->parser->pre_display_parse( $this->parser->pre_db_parse( $text ) );
				
		//$text  = preg_replace( "/\n/", "<br>", stripslashes($_POST['text'] ) );
		//$title = preg_replace( "/\n/", "<br>", stripslashes($_POST['title'] ) );
		//$desc  = preg_replace( "/\n/", "<br>", stripslashes($_POST['description'] ) );
		
		$text  = preg_replace( "/\\\/", "&#092;", $text );
		
		$this->ipsclass->DB->do_update( 'faq', array( 'title'       => $this->ipsclass->input['title'],
													  'text'        => $text,
													  'description' => $this->ipsclass->my_nl2br( $this->ipsclass->input['description'] ),
											 ) , "id=".intval($this->ipsclass->input['id'])     );
		
		$this->ipsclass->admin->save_log("Edited help files");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");
		exit();

	}
	
	
	function doreorder()
	{
 		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^order_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[ $match[1] ] = $this->ipsclass->input[$match[0]];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
 		
 		//-----------------------------------------
 		// Save changes
 		//-----------------------------------------
 		
 		if ( count($ids) )
 		{ 
 			foreach( $ids as $this_id => $new_position )
 			{
 				$this->ipsclass->DB->do_update( 'faq', array( 'position' => intval($new_position) ), 'id='.$this_id );
 			}
 		}
 		
 		$this->ipsclass->boink_it( $this->ipsclass->base_url.'&'.$this->ipsclass->form_code );
	}
	
	//=====================================================
	
	
	function show_form($type='new')
	{
		$this->ipsclass->admin->page_detail = "You may add/edit and remove help files below.";
		$this->ipsclass->admin->page_title  = "Help File Management";
		$this->ipsclass->admin->nav[] 		= array( '', 'Add/Edit Help Files' );
		
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->from_acp = 1;
  		$this->han_editor->ed_width = "550px";
  		$this->han_editor->ed_height = "200px";
  		$this->ipsclass->vars['rte_width'] = "500px";
  		$this->ipsclass->vars['rte_height'] = "200px";          
        $this->han_editor->init(); 		

        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = 1;		
		
		//-----------------------------------------
		
		if ($type != 'new')
		{
		
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("You must pass a valid help file id, silly!");
			}
		
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'faq', 'where' => "id=".intval($this->ipsclass->input['id']) ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( ! $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->admin->error("We could not find that help file in the database");
			}
		
			if ( $this->han_editor->method == 'rte' )
			{
				$r['text'] = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $r['text'] ) );
				$r['text'] = $this->parser->convert_ipb_html_to_html( $r['text'] );
			}
			else
			{
				$this->parser->parse_html    = 1;
				$this->parser->parse_nl2br   = 1;
				$this->parser->parse_smilies = 1;
				$this->parser->parse_bbcode  = 1;
				
				$r['text'] = $this->parser->pre_edit_parse( $r['text'] );
			}
						
			//-----------------------------------------
			
			$button = 'Edit this Help File';
			$code   = 'doedit';
		}
		else
		{
			$r = array();
			$button = 'Add this Help File';
			$code   = 'donew';
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $code ),
																			 2 => array( 'act'   , 'help'     ),
																			 3 => array( 'id'    , $this->ipsclass->input['id'] ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	), "theAdminForm", "onclick='return ValidateForm()'", "postingform"    );
		
		
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "80%" );
		
		//$r['text'] = preg_replace( "/<br>/i", "\n", stripslashes($r['text']) );
 		
 		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $button );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "Help File Title",
												  $this->ipsclass->adskin->form_input('title'  , $r['title'] ),
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "Help File Description",
												  $this->ipsclass->adskin->form_textarea('description', $r['description'] ),
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "Help File Text",
												  //$this->ipsclass->adskin->form_textarea('text', $r['text'], "60", "10" ),
												  $this->han_editor->show_editor( $r['text'], 'text' )
										 )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($button);
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	
	}
	
	//=====================================================
	
	function remove()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must pass a valid help file id, silly!");
		}
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'faq', 'where' => "id=".intval($this->ipsclass->input['id']) ) );
	
		$this->ipsclass->admin->save_log("Removed a help file");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");
		exit();
			
		
	}
	
	//=====================================================
	
	function doadd()
	{
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        $this->ipsclass->load_skin();
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->init();
 		
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = 1;		
        		
		if ($this->ipsclass->input['title'] == "")
		{
			$this->ipsclass->admin->error("You must enter a title, silly!");
		}
		
		$text = $this->han_editor->process_raw_post( 'text' );
		$this->parser->parse_smilies   = 1;
		$this->parser->parse_html      = 1;
		$this->parser->parse_bbcode    = 1;
		$text        					= $this->parser->pre_display_parse( $this->parser->pre_db_parse( $text ) );
				
		
		//$text  = preg_replace( "/\n/", "<br>", stripslashes($_POST['text'] ) );
		//$title = preg_replace( "/\n/", "<br>", stripslashes($_POST['title'] ) );
		//$desc  = preg_replace( "/\n/", "<br>", stripslashes($_POST['description'] ) );
		
		$text  = preg_replace( "/\\\/", "&#092;", $text );
		
		$this->ipsclass->DB->do_insert( 'faq', array( 'title'       => $this->ipsclass->input['title'],
													  'text'        => $text,
													  'description' => $this->ipsclass->my_nl2br( $this->ipsclass->input['description'] ),
							 )      );
												  
		$this->ipsclass->admin->save_log("Added a help file");
		
		$this->ipsclass->boink_it($this->ipsclass->base_url."&{$this->ipsclass->form_code}");
		exit();
			
		
	}
	
	//=====================================================
	
	function list_files()
	{
		$this->ipsclass->admin->page_detail = "You may add/edit and remove help files below.";
		$this->ipsclass->admin->page_title  = "Help File Management";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'       , 'doreorder'              ),
																			 2 => array( 'act'        , 'help'                          ),
																			 5 => array( 'section'    , 'tools'                       ),
																	)     );		
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Title"  , "45%" );
		$this->ipsclass->adskin->td_header[] = array( "Edit"   , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Remove" , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Position" , "5%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Current Help Files" );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'faq', 'order' => "position" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			$count = $this->ipsclass->DB->get_num_rows();
			
			$order_values = array();
			
			for( $i=1; $i<=$count; $i++ )
			{
				$order_values[] = array( $i, $i );
			}
			
			$last = 1;
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>".stripslashes($r['title'])."</b><br>".stripslashes($r['description']),
														  "<center><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=edit&id={$r['id']}'>Edit</a></center>",
														  "<center><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=remove&id={$r['id']}'>Remove</a></center>",
														  "<center>".$this->ipsclass->adskin->form_dropdown( 'order_'.$r['id'], $order_values, $r['order'] > 0 ? $r['order'] : $last )."</center>",
												 )      );
				$last++;				
			}
		}
		
		$form_button = "<input value='Reorder' class='realbutton' accesskey='s' type='submit'></form>";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<div style='float:right;width:auto;'>{$form_button}</div><div class='fauxbutton-wrapper'><span class='fauxbutton'><a href='".$this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=new'>Add New Help File</a></span></div>", "center", "tablesubheader" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->html .= "<center><a href='{$this->ipsclass->base_url}&section=tools&act=help&code=acp_help'>Rebuild ACP Help Files</a></center>";
		
		$this->ipsclass->admin->output();
	
	}
	
	
}


?>