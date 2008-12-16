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
|   > $Date: 2007-06-05 11:40:07 -0400 (Tue, 05 Jun 2007) $
|   > $Revision: 1013 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Help Control functions
|   > Module written by Matt Mecham
|   > Date started: 2nd April 2002
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


class ad_skin_sets
{
	var $ipsclass;
	var $html;
	var $master_set = 1;

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
	var $perm_child = "sets";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Skin Manager Home' );
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_lookandfeel');
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		$this->ipsclass->input['code'] = isset($this->ipsclass->input['code']) ? $this->ipsclass->input['code'] : NULL;
		
		switch($this->ipsclass->input['code'])
		{
			case 'addset':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->add_set();
				break;
				
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_form('edit');
				break;
			
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->save_skin('edit');
				break;
				
			case 'remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove_splash();
				break;
				
			case 'doremove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->do_remove();
				break;
			
			//-----------------------------------------
			
			case 'revertallform':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->revert_all_form();
				break;
				
			case 'dorevert':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_revert_all();
				break;
				
			case 'toggledefault':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->set_toggle_default();
				break;
				
			case 'togglevisible':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->set_toggle_visible();
				break;
				
			//-----------------------------------------
			// Export master
			//-----------------------------------------
			
			case 'exportmaster':
				$this->export_master();
				break;
				
			case 'exportmacro':
				$this->export_macro();
				break;
				
			//-----------------------------------------
			// Rebuild all
			//-----------------------------------------
			
			case 'rebuildalltemplates':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':rebuild' );
				$this->rebuild_all_templates();
				break;
				
			//-----------------------------------------
			// Export bits
			//-----------------------------------------
			
			case 'exportbitschoose':
				$this->export_bits_choose();
				break;
			
			case 'exportbitscomplete':
				$this->export_bits_complete();
				break;
				
			case 'master_xml_export':
				$this->master_xml_export();
				break;
				
			case 'MOD_mod_xml':
				$this->generate_mod_xml();
				break;				
						
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->list_sets();
				break;
		}
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
													  'from'   => 'skin_sets',
													  'where'  => "set_skin_set_id IN (1,2)" ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Set up
			//-----------------------------------------
			
			if ( $r['set_skin_set_id'] == 2 )
			{
				$r['set_css']            = '';
				$r['set_cache_macro']	 = '';
				$r['set_wrapper'] 		 = '';
				$r['set_css_updated'] 	 = 0;
				$r['set_cache_css'] 	 = '';
				$r['set_cache_wrapper']  = '';
			}
			
			//-----------------------------------------
			// Fix up CSS
			//-----------------------------------------
			
			if ( $r['set_css'] )
			{
				$r['set_css'] = preg_replace( "#url\((style_images/)?\d+?/#i", "url(style_images/<#IMG_DIR#>/", $r['set_css'] );
			}
			
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
		
		$this->ipsclass->admin->show_download( $doc, 'skinsets.xml', '', 0 );
	}
	
	/*-------------------------------------------------------------------------*/
	// EXPORT SOME TEMPLATE BITS TO SQL FILE (COMPLETE)
	/*-------------------------------------------------------------------------*/
	
	function export_bits_complete()
	{
		$ids = array();
		
		//-----------------------------------------
		// get ids...
		//-----------------------------------------
		
		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^id_(\d+)$/", $key, $match ) )
			{
				if ($this->ipsclass->input[$match[0]])
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = $this->ipsclass->clean_int_array( $ids );
		
		//-----------------------------------------
		// Got any?
		//-----------------------------------------
		
		if ( ! count( $ids ) )
		{
			$this->ipsclass->main_msg = "You must select SOME template bits to export!";
			$this->export_bits_choose();
		}
		
		$final_sql = "";
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'suid IN ('.implode(",",$ids).')' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = preg_replace( "/'/", "\\'", $this->ipsclass->txt_safeslashes( $r['section_content'] ) );
			$datavar = preg_replace( "/'/", "\\'", $this->ipsclass->txt_safeslashes( $r['func_data']       ) );
			
			$content = str_replace( "\n", '\n', $content );
			
			$final_sql .= "REPLACE INTO ibf_skin_templates SET set_id=1, group_names_secondary='". $r['group_names_secondary']. "', group_name='{$r['group_name']}', func_name='{$r['func_name']}', section_content='$content', func_data='$datavar';\n";
		}
		
		//@header("Content-type: text/plain");
		//print $final_sql;
		//exit();
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $final_sql, 'templates_update.sql', '', 0 );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// EXPORT SOME TEMPLATE BITS TO SQL FILE
	/*-------------------------------------------------------------------------*/
	
	function export_bits_choose()
	{
		$all_templates = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'group_name,set_id,suid,func_name, group_names_secondary', 'from' => 'skin_templates', 'where' => 'set_id=1' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$all_templates[ $r['group_name'] ][] = $r;
		}
		
		ksort( $all_templates );
		
		//-----------------------------------------
		// Start output
		//-----------------------------------------
		
		$this->ipsclass->admin->page_title  = "Export Selected Template bits";
		$this->ipsclass->admin->page_detail = "Check the box of the bit you wish to export.";
		
		//-----------------------------------------
		// start form
		//-----------------------------------------
		
		$per_row  = 3;
		$td_width = 100 / $per_row;
		$count    = 0;
		$output   = "<tr align='center'>\n";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'exportbitscomplete' ),
															     2 => array( 'act'   , 'sets'      ),
															     4 => array( 'section', $this->ipsclass->section_code ),
													    )      );
													    
		$this->ipsclass->html .= "<div class='tableborder'>
							 <div class='tableheaderalt'>Template Bits</div>
							 ";
							 
		foreach( $all_templates as $group_name => $data )
		{
			//-----------------------------------------
			// Start secondary table
			//-----------------------------------------
			
			$count = 0;
			
			$output .= "<div class='tableborder'>
						 <div class='tablesubheader'>$group_name</div>
						 <table width='100%' cellspacing='1' cellpadding='4' border='0'>
						 <tr>";
			
			foreach( $all_templates[ $group_name ] as $r )
			{
				$count++;
			
				$class = $count == 2 ? 'tablerow2' : 'tablerow1';
				
				$output .= "<td width='{$td_width}%' align='left' class='$class'>
							 <input type='checkbox' style='checkbox' value='1' name='id_{$r['suid']}' /> <strong>{$r['func_name']}</strong>
							</td>";
							
				if ($count == $per_row )
				{
					$output .= "</tr>\n\n<tr align='center'>";
					$count   = 0;
				}
			}
			
			if ( $count > 0 and $count != $per_row )
			{
				for ($i = $count ; $i < $per_row ; ++$i)
				{
					$output .= "<td class='tablerow2'>&nbsp;</td>\n";
				}
				
				$output .= "</tr>";
			}
			
			$output .= "</tr>\n</table></div>";
		}
		
		$this->ipsclass->html .= $output;
		
		$this->ipsclass->html .= "<div class='tablesubheader' align='center'><input type='submit' class='realbutton' value='EXPORT SELECTED' /></form></div></div>";
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// REBUILD TEMPLATES
	/*-------------------------------------------------------------------------*/
	
	function rebuild_all_templates()
	{
		if ( $this->ipsclass->input['removewarning'] == 1 )
		{
			$this->ipsclass->update_cache( array( 'value' => '', 'name' => 'skinpanic', 'donow' => 1, 'deletefirst' => 1, 'array' => 0 ) );
		}
		
		$justdone = intval($this->ipsclass->input['justdone']);
		$justdone = $justdone ? $justdone : 1;
		
		//-----------------------------------------
		// Get skins
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
									  'from'   => 'skin_sets',
									  'where'  => 'set_skin_set_id > '.$justdone,
									  'order'  => 'set_skin_set_id',
									  'limit'  => array( 0, 1 )
						     )      );
						     
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// Got a biggun?
		//-----------------------------------------
		
		$r = $this->ipsclass->DB->fetch_row();
		
		if ( $r['set_skin_set_id'] )
		{
			$this->ipsclass->cache_func->_rebuild_all_caches( array($r['set_skin_set_id']) );
			
			$this->ipsclass->admin->redirect( "{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&code=rebuildalltemplates&justdone={$r['set_skin_set_id']}", "Rebuilt cache for skin set {$r['set_name']}<br />Proceeding to the next skin..." );
		}
		else
		{
			$this->ipsclass->main_msg = "All skin templates recached!";
			$this->list_sets();
		}
	}
	
	//-----------------------------------------
	// Export master skin set.
	//-----------------------------------------
	
	function export_master()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------
		
		$xml->xml_set_root( 'templateexport', array( 'exported' => time(), 'versionid' => '20000', 'type' => 'master' ) );
		
		//-----------------------------------------
		// Get emo group
		//-----------------------------------------
		
		$xml->xml_add_group( 'templategroup' );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'group_name, section_content, func_name, func_data, group_names_secondary', 'from' => 'skin_templates', 'where' => 'set_id=1' ) );
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
		
		$xml->xml_format_document();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $xml->xml_document, 'ipb_templates.xml', '', 0 );
	}
	
	//-----------------------------------------
	// Export master macros
	//-----------------------------------------
	
	function export_macro()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------
		
		$xml->xml_set_root( 'macroexport', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Get emo group
		//-----------------------------------------
		
		$xml->xml_add_group( 'macrogroup' );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'macro_value,macro_replace', 'from' => 'skin_macro', 'where' => 'macro_set=1' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			foreach ( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[$r['macro_value']] = $xml->xml_build_entry( 'macro', $content );
		}
		
		$xml->xml_add_entry_to_group( 'macrogroup', $entry );
		
		$xml->xml_format_document();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $xml->xml_document, 'macro.xml', '', 0 );
		
	}
	
	//-----------------------------------------
	// ADD SET
	//-----------------------------------------
	
	function add_set()
	{
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
	
		$new     = array();
		$message = array();
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		if ( $this->ipsclass->input['id'] == 1 )
		{
			$this->ipsclass->main_msg = "You cannot alter the master skin set";
			$this->list_sets();
		}
		
		if ( ! $this->ipsclass->input['set_name'] )
		{
			$this->ipsclass->main_msg = "You must enter a skin set name.";
			$this->list_sets();
		}
		
		if ( $this->ipsclass->input['id'] == -1 )
		{
			//-----------------------------------------
			// No parent...
			//-----------------------------------------
			
			$new['set_skin_set_parent'] = -1;
			$get_from_db = 1;
		}
		else
		{
			$new['set_skin_set_parent'] = $this->ipsclass->input['id'];
			$get_from_db = intval($this->ipsclass->input['id']);
		}
		
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$get_from_db ) );
		
		$new['set_name']          = $this->ipsclass->input['set_name'];
		$new['set_image_dir']     = $this_set['set_image_dir'];
		$new['set_hidden']        = intval( $this->ipsclass->input['hidden'] );
		$new['set_default']       = 0;
		$new['set_css_method']    = $this_set['set_css_method'];
		$new['set_cache_css']     = $this_set['set_cache_css'];
		$new['set_cache_macro']   = $this_set['set_cache_macro'];
		$new['set_cache_wrapper'] = $this_set['set_cache_wrapper'];
		
		$this->ipsclass->DB->do_insert( 'skin_sets', $new );
		
		$newid = $this->ipsclass->DB->get_insert_id();
			
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------
		
		$this->ipsclass->cache_func->_rebuild_all_caches( array( $newid ) );
			
		$this->ipsclass->main_msg = '<b>Skin Set Added</b>';
		
		$this->ipsclass->main_msg .= "<br />".implode("<br />", array_merge( $message, $this->ipsclass->cache_func->messages) );
		$this->list_sets();
	}
	
	//-----------------------------------------
	// Revert customizations > DO
	//-----------------------------------------
	
	function do_revert_all()
	{
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
		
		$message = array();
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		if ( $this->ipsclass->input['id'] == 1 )
		{
			$this->ipsclass->main_msg = "You cannot alter the master skin set";
			$this->list_sets();
		}
		
		$id = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// Delete Templates?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['html']) AND $this->ipsclass->input['html'] )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_templates', 'where' => 'set_id='.$id ) );
			$message[] = 'Removed all custom HTML template bits...';
		}
		
		//-----------------------------------------
		// Delete Macros?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['macro']) AND $this->ipsclass->input['macro'] )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_macro', 'where' => 'macro_set='.$id ) );
			$message[] = 'Removed all custom replacement macros...';
		}
		
		//-----------------------------------------
		// Delete Wrapper
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['wrapper']) AND $this->ipsclass->input['wrapper'] )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'update' => 'skin_sets', 'set' => "set_wrapper=''", 'where' => 'set_skin_set_id='.$id ) );
			$message[] = 'Removed custom wrapper...';
		}
		
		//-----------------------------------------
		// Delete Wrapper
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['css']) AND $this->ipsclass->input['css'] )
		{
			$this->ipsclass->DB->simple_exec_query( array( 'update' => 'skin_sets', 'set' => "set_css=''", 'where' => 'set_skin_set_id='.$id ) );
			$message[] = 'Removed custom CSS...';
		}
		
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------
		
		$this->ipsclass->cache_func->_rebuild_all_caches( array( $id ) );
			
		$this->ipsclass->main_msg = 'Skin set customizations removed';
		
		$this->ipsclass->main_msg .= "<br />".implode("<br />", array_merge( $message, $this->ipsclass->cache_func->messages) );
		$this->list_sets();
	}
	
	//-----------------------------------------
	// Revert customizations form
	//-----------------------------------------
	
	function revert_all_form()
	{
		$templates = 0;
		$macros    = 0;
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		if ( $this->ipsclass->input['id'] == 1 )
		{
			$this->ipsclass->main_msg = "You cannot alter the master skin set";
			$this->list_sets();
		}
		
		$this->ipsclass->admin->page_detail = "<strong>Please note that this change cannot be undone!</strong>";
		$this->ipsclass->admin->page_title  = "Revert Skin Set Customizations";
		
		//-----------------------------------------
		// Get macro / template info
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as aerosmith', 'from' => 'skin_templates', 'where' => "set_id={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->simple_exec();
		
		$r = $this->ipsclass->DB->fetch_row();
		$templates = intval($r['aerosmith']);
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as aerosmith', 'from' => 'skin_macro', 'where' => "macro_set={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->simple_exec();
		
		$r = $this->ipsclass->DB->fetch_row();
		$macros = intval($r['aerosmith']);
		
		//-----------------------------------------
		// Get the thingies
		//-----------------------------------------
		
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_skin_set_id=".$this->ipsclass->input['id'] ) );
		
		//-----------------------------------------
		// Start the form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code', 'dorevert'                  ),
																 2 => array( 'act' , 'sets'                      ),
																 3 => array( 'id'  , $this->ipsclass->input['id']      ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														), "theAdminForm"    );
		
		$none = "<em>No customizations to remove</em>";
													  
		$html    = $templates               ? $this->ipsclass->adskin->form_yes_no('html'   , 0) : $none;
		$macro   = $macros                  ? $this->ipsclass->adskin->form_yes_no('macro'  , 0) : $none;
		$wrapper = $this_set['set_wrapper'] ? $this->ipsclass->adskin->form_yes_no('wrapper', 0) : $none;
		$css     = $this_set['set_css']     ? $this->ipsclass->adskin->form_yes_no('css'    , 0) : $none;
		
		//-----------------------------------------
		// Start output
		//-----------------------------------------
		
		$this->ipsclass->html .= "<div class='tableborder'>
							<div class='tableheaderalt'>Reverting Customizations in set {$this_set['set_name']}</div>
							<div class='tablepad'>
							<fieldset class='tdfset'>
							 <legend><strong>Template HTML Customizations</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Revert all template bit HTML customizations?<br /><span style='color:gray'>You have {$templates} template customizations</span></td>
							   <td width='60%' class='tablerow1'>{$html}</td>
							 </tr>
							 </table>
							</fieldset>
							<br />
							<fieldset class='tdfset'>
							 <legend><strong>Macro Replacement Customizations</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Revert all macro customizations?<br /><span style='color:gray'>You have {$macros} macro customizations</span></td>
							   <td width='60%' class='tablerow1'>{$macro}</td>
							 </tr>
							 </table>
							</fieldset>
							<br />
							<fieldset class='tdfset'>
							 <legend><strong>Board Header and Footer Wrapper Customizations</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Revert Board Header and Footer Wrapper?</td>
							   <td width='60%' class='tablerow1'>{$wrapper}</td>
							 </tr>
							 </table>
							</fieldset>
							<br />
							<fieldset class='tdfset'>
							 <legend><strong>Stylesheet (CSS) Customizations</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Revert CSS?</td>
							   <td width='60%' class='tablerow1'>{$css}</td>
							 </tr>
							 </table>
							</fieldset>
							<div style='color:red;text-align:center;font-size:11px;padding:6px'>Please note that all customizations will be lost if set to 'yes'<br /><b>This cannot be undone and there are no more confirmation screens</b></div>
							</div>
							</div>";
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form_standalone("Process");
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code ,'Skin Manager Home' );
		$this->ipsclass->admin->nav[] = array( '' ,'Reverting all customizations in set '.$this_set['set_name'] );
	
		$this->ipsclass->admin->output();
	}
	
	
	//-----------------------------------------
	// REMOVE SKIN SET FORM
	//-----------------------------------------
	
	function remove_splash()
	{
		$this->ipsclass->admin->page_detail = "Please read this page carefully.";
		$this->ipsclass->admin->page_title  = "Removing Skin Set";
		
		//-----------------------------------------
		// Get this skin set...
		//-----------------------------------------
		
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.intval($this->ipsclass->input['id']) ) );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code', 'doremove'                  ),
																 2 => array( 'act' , 'sets'                      ),
																 3 => array( 'id'  , $this->ipsclass->input['id']      ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														)      );
													  
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Remove Skin Set {$this_set['set_name']}" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
																"<div style='color:red;font-weight:bold;font-size:11px'>
																PLEASE NOTE: This action cannot be undone</div><br />
																Continuing will permanently remove any customizations to this skin set, including template HTML, CSS, Wrappers and custom macros.
																<br /><br />
																Any children of this skin set will be set as 'root' skins without a parent.
																",
													  )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Permanently Remove Set {$this_set['set_name']}");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	// TOGGLE DEFAULT SKIN
	//-----------------------------------------
	
	function set_toggle_default()
	{
		$affected_ids = array();
		$children     = array();
		$message      = array();
		
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		if ( $this->ipsclass->input['id'] == 1 )
		{
			$this->ipsclass->main_msg = 'You cannot alter the master skin set';
			$this->list_sets();
		}
		
		//-----------------------------------------
		// Set as default
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 0 ), "" );
		$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 1, 'set_hidden' => 0 ), "set_skin_set_id =".intval($this->ipsclass->input['id']) );
		
		//-----------------------------------------
		// Rebuild caches and relationships?
		//-----------------------------------------
		
		$this->ipsclass->cache_func->_rebuild_all_caches( array( $this->ipsclass->input['id'] ) );
		
		$this->ipsclass->main_msg = 'Skin set to default';
		$this->list_sets();
	}
	
	//-----------------------------------------
	// TOGGLE VISIBILITY
	//-----------------------------------------
	
	function set_toggle_visible()
	{
		$affected_ids = array();
		$children     = array();
		$message      = array();
		
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		if ( $this->ipsclass->input['id'] == 1 )
		{
			$this->ipsclass->main_msg = 'You cannot alter the master skin set';
			$this->list_sets();
		}
		
		//-----------------------------------------
		// Get current skin
		//-----------------------------------------
		
		$skin   = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.intval($this->ipsclass->input['id']) ) );
		
		$hidden = 1;
		
		if ( $skin['set_hidden'] )
		{
			$hidden = 0;
		}
		
		//-----------------------------------------
		// We're not going to make all skins invisible?
		//-----------------------------------------
		
		if ( $hidden == 1 )
		{
			$count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count',
																	   'from'   => 'skin_sets',
																	   'where'  => 'set_hidden=0 AND set_skin_set_id NOT IN ( 1,'. intval($this->ipsclass->input['id']).')' ) );
																	   
			if ( ! intval($count['count']) )
			{
				$this->ipsclass->main_msg = 'You cannot turn your last visible skin set invisible';
				$this->list_sets();
				return;
			}
		}
		
		$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_hidden' => $hidden ), 'set_skin_set_id='.intval($this->ipsclass->input['id']) );
		
		//-----------------------------------------
		// Rebuild caches and relationships?
		//-----------------------------------------
		
		$this->ipsclass->cache_func->_rebuild_all_caches( array( $this->ipsclass->input['id'] ) );
		
		$this->ipsclass->main_msg = 'Skin set visibility changed';
		$this->list_sets();
	}
	
	//-----------------------------------------
	// DO REMOVE SKIN SET
	//-----------------------------------------
	
	function do_remove()
	{
		$affected_ids = array();
		$children     = array();
		$message      = array();
		
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		if ( $this->ipsclass->input['id'] == 1 )
		{
			$this->ipsclass->main_msg = 'You cannot alter the master skin set';
			$this->list_sets();
		}
		
		//-----------------------------------------
		// Get this skin set...
		//-----------------------------------------
		
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Can we remove?
		//-----------------------------------------
		
		if ( $this_set['set_default'] == 1 )
		{
			$this->ipsclass->main_msg = 'IPB cannot remove this skin as it is set as the default skin, please set another skin set to default and try again.';
			$this->list_sets();
		}
		
		$this_count = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as jazzyjeff', 'from' => 'skin_sets' ) );
		
		if ( $this_count['jazzyjeff'] == 2 )
		{
			$this->ipsclass->main_msg = 'IPB cannot remove this skin as it is the last editable skin set you currently have.';
			$this->list_sets();
		}
		
		//-----------------------------------------
		// Get any children
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'set_skin_set_id, set_skin_set_parent', 'from' => 'skin_sets', 'where' => "set_skin_set_parent=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$affected_ids[] = $r['set_skin_set_id'];
			$children[]     = $r['set_skin_set_id'];
		}
		
		//-----------------------------------------
		// Update children to root
		//-----------------------------------------
		
		if ( count($children) )
		{
			$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_skin_set_parent' => '-1' ), 'set_skin_set_id IN ('.implode(',',$children).')' );
		}
		
		//-----------------------------------------
		// Members using this skin?
		//-----------------------------------------
		
		$this->ipsclass->DB->do_update( 'members', array( 'skin' => 0 ), 'skin='.intval($this->ipsclass->input['id']) );
		
		//-----------------------------------------
		// Delete the skin...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_sets', 'where' => 'set_skin_set_id='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Remove macros...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_macro', 'where' => 'macro_set='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Remove templates...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_templates', 'where' => 'set_id='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Remove template cache...
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_templates_cache', 'where' => 'template_set_id='.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Remove CSS file...
		//-----------------------------------------
		
		@unlink( CACHE_PATH.'style_images/css_'.$this->ipsclass->input['id'].'.css' );
		$message[] = 'Clean up: removing CSS cache file...';
		
		//-----------------------------------------
		// Remove CACHE folder
		//-----------------------------------------
		
		$this->ipsclass->admin->rm_dir( CACHE_PATH.'cache/skin_cache/cacheid_'.$this->ipsclass->input['id'] );
		$message[] = 'Clean up: removing HTML templates cache folder...';
		
		//-----------------------------------------
		// Rebuild caches and relationships?
		//-----------------------------------------
		
		if ( count($affected_ids) )
		{
			$this->ipsclass->cache_func->_rebuild_all_caches($affected_ids);
		}
		
		$this->ipsclass->cache_func->_rebuild_skin_id_cache();
		
		$this->ipsclass->main_msg = 'Skin set removed';
		
		$this->ipsclass->main_msg .= "<br />".implode("<br />", array_merge( $message, $this->ipsclass->cache_func->messages) );
		$this->list_sets();
		
	}
	
	
	
	//-----------------------------------------
	// ADD / EDIT SKIN SETS
	//-----------------------------------------
	
	function save_skin( $type='add' )
	{
		//-----------------------------------------
		// Fix up incoming
		//-----------------------------------------
		
		//img / prt
		
		if ($type == 'edit')
		{
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
			}
		}
		
		if ($this->ipsclass->input['set_name'] == "")
		{
			$this->ipsclass->admin->error("You must specify a name for this skin pack ID");
		}
	
		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => 'set_skin_set_id = '.intval($this->ipsclass->input['id']) ) );
		
		//-----------------------------------------
		// Init var
		//-----------------------------------------
		
		$barney = array( 'set_name'            => $this->ipsclass->txt_stripslashes($_POST['set_name']),
						 'set_css_method'      => $this->ipsclass->input['set_css_method'],
						 'set_hidden'          => $this->ipsclass->input['set_hidden'],
						 'set_image_dir'       => $this->ipsclass->input['set_image_dir'],
						 'set_author_email'    => $this->ipsclass->input['set_author_email'],
						 'set_author_url'      => $this->ipsclass->input['set_author_url'],
						 'set_author_name'     => $this->ipsclass->input['set_author_name'],
						 'set_skin_set_parent' => $this->ipsclass->input['set_skin_set_parent'],
						 'set_emoticon_folder' => $this->ipsclass->input['set_emoticon_folder'],
						 'set_key'			   => substr( $this->ipsclass->input['set_key'], 0, 32 ),
					   );
					   
		if ($type == 'add')
		{
			
			
		}
		else
		{
			//-----------------------------------------
			// Did we set it to default?
			//-----------------------------------------
			
			if ( $this->ipsclass->input['set_default'] )
			{
				$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 0 ), 'set_skin_set_id <> '.intval($this->ipsclass->input['id']) );
				$barney['set_default'] = 1;
			}
			
			//-----------------------------------------
			// Did the parent change?
			//-----------------------------------------
			
			$affected_ids = array();
			
			if ( $this->ipsclass->input['prt'] != $this->ipsclass->input['set_skin_set_parent'] )
			{
				$affected_ids[ $this_set['set_skin_set_id'] ] = $this_set['set_skin_set_id'];
				
				//-----------------------------------------
				// Any kids?
				//-----------------------------------------
				
				$children = array();
				$child_id = array();
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 'set_skin_set_id', 'from' => 'skin_sets', 'where' => 'set_skin_set_parent='.$this_set['set_skin_set_id'] ) );
				$this->ipsclass->DB->simple_exec();
				
				while ( $r = $this->ipsclass->DB->fetch_row() )
				{
					$children[]      = $r;
					$child_id[]      = $r['set_skin_set_id'];
					$affected_ids[ $r['set_skin_set_id'] ]  = $r['set_skin_set_id'];
				}
				
				if ( count($children) )
				{
					//-----------------------------------------
					// Move children to direct root children
					//-----------------------------------------
					
					$this->ipsclass->DB->simple_exec_query( array( 'update' => 'skin_sets', 'set' => 'set_skin_set_parent = -1', 'where' => 'set_skin_set_id IN ('.implode(",",$child_id).')' ) );
				}
			}
			
			if ( $this->ipsclass->input['css'] != $this->ipsclass->input['set_css_method'] )
			{
				if ( $this->ipsclass->input['set_css_method'] )
				{
					//-----------------------------------------
					// Caching switched on...
					//-----------------------------------------
					
					$affected_ids[ $this_set['set_skin_set_id'] ] = $this_set['set_skin_set_id'];
				}
			}
			
			//-----------------------------------------
			// Img dir changed? recache css
			//-----------------------------------------
			
			if ( $this->ipsclass->input['img'] != $this->ipsclass->input['set_image_dir'] )
			{
				$affected_ids[ $this_set['set_skin_set_id'] ] = $this_set['set_skin_set_id'];
			}
			
			$this->ipsclass->DB->do_update( 'skin_sets', $barney, "set_skin_set_id=".intval($this->ipsclass->input['id']) );
			
			//-----------------------------------------
			// Rebuild caches and relationships?
			//-----------------------------------------
			
			$this->ipsclass->cache_func->_rebuild_all_caches($affected_ids);
			
			$this->ipsclass->main_msg = 'Skin Settings Updated';
			
			$this->ipsclass->main_msg .= "<br />".implode("<br />", $this->ipsclass->cache_func->messages);
			
			//$this->ipsclass->admin->redirect("{$this->ipsclass->form_code}", "Skin Set Updated" );
			$this->list_sets();
		}
	}
	
	//-----------------------------------------
	// ADD / EDIT SETS
	//-----------------------------------------
	
	function do_form( $type='add' )
	{
		//-----------------------------------------
		// Check for input
		//-----------------------------------------
		
		$sets     = array();
		$parents  = array( 0=> array( '-1', 'No parent' ) );
		$row      = array();
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_sets' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$sets[ $r['set_skin_set_id'] ] = $r;
			
			if ( ($r['set_skin_set_parent'] < 0 and $r['set_skin_set_id'] != 1 ) and ( $this->ipsclass->input['id'] != $r['set_skin_set_id'] ) )
			{
				$parents[] = array( $r['set_skin_set_id'], $r['set_name'] );
			}
			
			if ( $this->ipsclass->input['id'] == $r['set_skin_set_id'] )
			{
				$row = $r;
			}
		}
		
		
		//-----------------------------------------
		
		if ($type == 'add')
		{
			$code = 'doadd';
			$button = 'Create New Skin Set';
			$row['set_name']    = $row['set_name'];
			$row['set_default'] = 0;
		}
		else
		{
			$code = 'doedit';
			$button = 'Edit Skin Settings';
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
				if ( is_dir(CACHE_PATH.'style_images/'.$file) )
				{
					$dirs[] = array( $file, $file );
				}
 			}
 		}
 		closedir( $dh );
 		
 		//-----------------------------------------
		// Emoticons dir
		//-----------------------------------------
		
		$emodirs = array();
		
		$dh = opendir( CACHE_PATH.'style_emoticons' );
		
 		while ( false !== ( $file = readdir( $dh ) ) )
 		{
 			if (($file != ".") && ($file != ".."))
 			{
				if ( is_dir(CACHE_PATH.'style_emoticons/'.$file) )
				{
					$emodirs[] = array( $file, $file );
				}
 			}
 		}
 		closedir( $dh );
 		
 		
		if ( is_writeable( CACHE_PATH."style_images" ) )
		{
			$cssextra = $this->ipsclass->adskin->form_yes_no('set_css_method', $row['set_css_method']);
		}
		else
		{
			$cssextra = "<em>Unavailable, IPB cannot write into your 'style_images' folder</em>";
		}
		
		
		//-----------------------------------------
	
		$this->ipsclass->admin->page_detail = "Please configure the settings below.";
		$this->ipsclass->admin->page_title  = "Manage Skin Sets";
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code', $code                       ),
																 2 => array( 'act' , 'sets'                      ),
																 3 => array( 'id'  , $this->ipsclass->input['id']      ),
																 4 => array( 'img' , $row['set_image_dir']       ),
																 5 => array( 'prt' , $row['set_skin_set_parent'] ),
																 6 => array( 'css' , $row['set_css_method']      ),
																 7 => array( 'section', $this->ipsclass->section_code ),
														), "theAdminForm"    );
									     
		//-----------------------------------------
		// Start output
		//-----------------------------------------
		
		$this->ipsclass->html .= "<div class='tableborder'>
							<div class='tableheaderalt'>$button</div>
							<div class='tablerow2'>
							<fieldset class='tdfset'>
							 <legend><strong>Basics</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Set Title</td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('set_name', $row['set_name'])."</td>
							 </tr>
							 <tr>
							   <td width='40%' class='tablerow1'>Hide from members?</td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_yes_no('set_hidden', $row['set_hidden'])."</td>
							 </tr>";
							 
		if ( $row['set_default'] )
		{
			$this->ipsclass->html .= "<tr>
							    <td width='40%' class='tablerow1'>Set as default skin?</td>
							    <td width='60%' class='tablerow1'><i>Skin set as default already.</i></td>";
		}
		else
		{
			$this->ipsclass->html .= "<tr>
							    <td width='40%' class='tablerow1'>Set as default skin?</td>
							    <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_checkbox('set_default', $row['set_default'])."</td>";
		}
		
		$this->ipsclass->html .= "</tr>
							  <tr>
							   <td width='40%' class='tablerow1'>Skin Set Parent?</td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_dropdown('set_skin_set_parent', $parents, $row['set_skin_set_parent'])."</td>
							 </tr>
							  <tr>
							   <td width='40%' class='tablerow1'>Skin Set Key? (Optional)</td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('set_key', $row['set_key'])."</td>
							 </tr>
							 </table>
							</fieldset>
							<br />
							<fieldset class='tdfset'>
							 <legend><strong>CSS Options</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Cache CSS to text files?<br /><span style='color:gray'>This will reduce the size of the HTML as the CSS will be in a browser cachable file.</span>
							   								 </td>
							   <td width='60%' class='tablerow1'>".$cssextra."<br /><span style='color:red'>Warning: Changing this value will re-cache any cached stylesheet information. Please make sure that you have synchronized any cache files with the database.</span></td>
							 </tr>
							 </table>
							</fieldset>
							<br />
							<fieldset class='tdfset'>
							 <legend><strong>Image Options</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Use image directory?<br /><span style='color:gray'>The image directory will be used in place of <#IMG_DIR#> in CSS and macro replacements.</span></td>
							   <td width='60%' class='tablerow1'>style_images/ ".$this->ipsclass->adskin->form_dropdown('set_image_dir', $dirs, $row['set_image_dir'])."</td>
							 </tr>
							  <tr>
							   <td width='40%' class='tablerow1'>Use emoticons set?<br /><span style='color:gray'>Choose which set of emoticons to assign to this skin.</span></td>
							   <td width='60%' class='tablerow1'>style_emoticons/ ".$this->ipsclass->adskin->form_dropdown('set_emoticon_folder', $emodirs, $row['set_emoticon_folder'])."</td>
							 </tr>
							 </table>
							</fieldset>
							
							<br />
							<fieldset class='tdfset'>
							 <legend><strong>Set Author</strong></legend>
							 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
							 <tr>
							   <td width='40%' class='tablerow1'>Set Author Name<br /><span style='color:gray'>*Optional</span></td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('set_author_name', $row['set_author_name'])."</td>
							 </tr>
							 <tr>
							   <td width='40%' class='tablerow1'>Set Author Email Address<br /><span style='color:gray'>*Optional</span></td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('set_author_email', $row['set_author_email'])."</td>
							 </tr>
							 <tr>
							   <td width='40%' class='tablerow1'>Set Author Website Address<br /><span style='color:gray'>*Optional</span></td>
							   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('set_author_url', $row['set_author_url'])."</td>
							 </tr>
							 </table>
							</fieldset>
							</div>
							</div>";
		
		

		//-----------------------------------------
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form_standalone($button);
		
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
		
		
	}
	
	//-----------------------------------------
	// SHOW ALL SKIN SETS
	//-----------------------------------------
	
	function list_sets()
	{
		$form_array     = array();
		$this_set       = "";
		$forums         = array();
		$forum_skins    = array();
		$macro_array    = array();
		$template_array = array();
		$content        = "";
		
		$this->ipsclass->admin->page_detail = "Simply click on the button next to the skin set you wish to edit and select one of the options from the pop-up menu.";
		$this->ipsclass->admin->page_title  = "Manage Skin Sets";
		
		//-----------------------------------------
		// Get forum names
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, skin_id', 'from' => 'forums' ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $f = $this->ipsclass->DB->fetch_row() )
		{
			$forums[ $f['id'] ] = $f['name'];
			
			if ( $f['skin_id'] != "")
			{
				$forum_skins[ $f['skin_id'] ][] = $f['name'];
			}
		}
		
		//-----------------------------------------
		// Get macro / template info
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'stylesets_list_sets_templates', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$template_array[ $r['set_id'] ] = 1;
		}
		
		$this->ipsclass->DB->cache_add_query( 'stylesets_list_sets_macros', array() );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$macro_array[ $r['macro_set'] ] = 1;
		}
		
		//-----------------------------------------
		// GET SKINS
		//-----------------------------------------
		
		$skin_sets  = array();
		$last_id    = 0;
		$default_skin = "";
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_sets', 'order' => 'set_skin_set_parent, set_skin_set_id ASC' ) );
		$this->ipsclass->DB->simple_exec();
		
		$no_sets = 0;
		$i_sets  = 0;
		
		while ( $row = $this->ipsclass->DB->fetch_row() )
		{
			$skins[ $row['set_skin_set_id'] ] = $row;
			
			if ( $row['set_skin_set_parent'] == -1 )
			{
				$no_sets++;
			}
		}
		
		//-----------------------------------------
		// Loop-de-loop
		//-----------------------------------------
		
		foreach( $skins as $r )
		{	
			$i_sets++;
			
			$skin_sets[ $r['set_skin_set_id'] ] = $r;
			
			$skin_sets[ $r['set_skin_set_parent'] ]['_lastid']     = $r['set_skin_set_id'];
			$skin_sets[ $r['set_skin_set_parent'] ]['_children'][] = $r['set_skin_set_id'];
			
			$extra  = "";
			$forums = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_notforums.gif' border='0' alt='Not used in forums' title='Not used in forums' />";
			
			//-----------------------------------------
			// Used in forums?
			//-----------------------------------------
			
			if ( isset($forum_skins[ $r['set_skin_set_id'] ]) AND is_array($forum_skins[ $r['set_skin_set_id'] ]) )
			{
				if ( count($forum_skins[ $r['set_skin_set_id'] ]) > 0 )
				{
					$extra  = "Used in forums:".implode( ",", $forum_skins[ $r['set_skin_set_id'] ] );
					
					$forums = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_forums.gif' border='0' alt='' title='$extra' />";
				}
			}
			
			$this->unaltered    = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_unaltered.gif' border='0' alt='-' title='Unaltered from parent skin set' />&nbsp;";
			$this->altered      = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_altered.gif' border='0' alt='+' title='Altered from parent skin set' />&nbsp;";
			$this->inherited    = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_inherited.gif' border='0' alt='|' title='Inherited from parent skin set' />&nbsp;";
			
			//-----------------------------------------
			// Default / Hidden?
			//-----------------------------------------
			
			$default      = "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=toggledefault&id={$r['set_skin_set_id']}' title='Make this skin the default'><img src='{$this->ipsclass->skin_acp_url}/images/skin_notdefault.gif' border='0' alt='Not Default' /></a>";
			$hidden       = "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=togglevisible&id={$r['set_skin_set_id']}' title='Toggle visibility'><img src='{$this->ipsclass->skin_acp_url}/images/skin_visible.gif' border='0' alt='visible' title='Skin not hidden from members' /></a>";
			$folder_icon  = 'skin_folder.gif';
			$css_extra    = "";
			
			//-----------------------------------------
			// Child of master, middle skin
			// or last skin?
			//-----------------------------------------
			
			if ( $i_sets >= $no_sets )
			{
				$line_image = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_line_l.gif' border='0' />&nbsp;";
			}
			else
			{
				$line_image = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_line_t.gif' border='0' />&nbsp;";
			}
			
			//-----------------------------------------
			// Hidden?
			//-----------------------------------------
			
			if ($r['set_hidden'] == 1)
			{ 
				$hidden      = "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=togglevisible&id={$r['set_skin_set_id']}' title='Toggle visibility'><img src='{$this->ipsclass->skin_acp_url}/images/skin_invisible.gif' border='0' alt='Invisible' title='Skin hidden from members' /></a>";
				$folder_icon = 'skin_folder_hidden.gif';
				$css_extra   = 'color:#7F7FAA';
			}
			
			//-----------------------------------------
			// Default?
			//-----------------------------------------
			
			if ($r['set_default'] == 1)
			{
				$default      = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_default.gif' border='0' alt='Not Default' title='Default skin' />";
				$default_skin = $r['set_name'];
			}
			
			//-----------------------------------------
			// IPB Master?
			//-----------------------------------------
			
			if ( $r['set_skin_set_id'] == 1 )
			{
				$folder_icon  = 'skin_folder_master.gif';
				$line_image   = "";
				$css_extra    = "color:gray";
				$hidden       = "";
				$default      = "";
				$forums       = "";
			}
			else
			{
				//-----------------------------------------
				// Not..
				//-----------------------------------------
				
				if ( $r['set_skin_set_parent'] >= 0 )
				{
					//-----------------------------------------
					// Child though...
					//-----------------------------------------
					
					if ( $folder_icon != 'skin_folder_hidden.gif' )
					{
						$folder_icon = 'skin_folder_children.gif';
					}
					
					$line_image  = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_line_single.gif' border='0' />";
				}
			}
			
			//-----------------------------------------
			// Skin opts
			//-----------------------------------------
			
			if ( $r['set_skin_set_id'] == 1 AND ! IN_DEV )
			{
				$menulist = "\"This is the master skin and cannot be edited or removed.<br />If you wish to customize the default skin, please click"
							 ."<br />on the skin set '<!DEFAULT>' and choose from the options.\"";
			}
			else
			{
				$menulist = preg_replace( "#,$#", "", $this->html->skin_sets_overview_row_menulist( $r ) );
			}
			
			//-----------------------------------------
			// Add skin row
			//-----------------------------------------
			$skin_sets[ $r['set_skin_set_id'] ]['_html'] = isset($skin_sets[ $r['set_skin_set_id'] ]['_html']) ? $skin_sets[ $r['set_skin_set_id'] ]['_html'] : '';
			
			$skin_sets[ $r['set_skin_set_id'] ]['_html'] .= $this->html->skin_sets_overview_row( $r, $forums, $hidden, $default, $menulist, $i_sets, $no_sets, $folder_icon, $line_image, $css_extra );
			
			$form_array[] = array( $r['set_skin_set_id'], $r['set_name'] );
		}
		
		//-----------------------------------------
		// Show root forums
		//-----------------------------------------
		
		foreach( $skin_sets as $id => $data )
		{
			if ( isset($data['set_skin_set_parent']) AND $data['set_skin_set_parent'] == -1 )
			{
				$wrapper_icon   = $this->_get_status_of_parent( $data['set_wrapper'] );
				$css_icon       = $this->_get_status_of_parent( $data['set_css'] );
				$templates_icon = $this->_get_status_of_parent( isset($template_array[ $data['set_skin_set_id'] ]) ? $template_array[ $data['set_skin_set_id'] ] : 0 );
				$macro_icon     = $this->_get_status_of_parent( isset($macro_array[ $data['set_skin_set_id'] ]) ? $macro_array[ $data['set_skin_set_id'] ] : 0 );
				
				//-----------------------------------------
				// Fix n' stitch
				//-----------------------------------------
				
				$data['_html'] = str_replace( '<!--ALTERED.wrappper-->' , $wrapper_icon  , $data['_html'] );
				$data['_html'] = str_replace( '<!--ALTERED.templates-->', $templates_icon, $data['_html'] );
				$data['_html'] = str_replace( '<!--ALTERED.css-->'      , $css_icon      , $data['_html']);
				$data['_html'] = str_replace( '<!--ALTERED.macro-->'    , $macro_icon    , $data['_html'] );
				
				$content .= $data['_html']."\n<!--CHILDREN:{$id}-->";
			}
		}		
		
		//-----------------------------------------
		// Show any children
		//-----------------------------------------
		
		foreach( $skin_sets as $id => $data )
		{	
			if ( isset($data['_children']) AND is_array( $data['_children'] ) and count( $data['_children'] ) > 0 )
			{
				$html = "";
				
				foreach( $data['_children'] as $cid )
				{
					$image = "";
					
					if ( $cid == $data['_lastid'] )
					{
						//-----------------------------------------
						// Last skin, show L
						//-----------------------------------------
						
						$image = 'skin_line_l.gif';
					}
					else
					{
						//-----------------------------------------
						// First skin, show T
						//-----------------------------------------
						
						$image = 'skin_line_t.gif';
					}
					
					$skin_sets[ $cid ]['_html'] = str_replace( "<!--ID:{$cid}-->", "<img src='{$this->ipsclass->skin_acp_url}/images/{$image}' border='0' />&nbsp;", $skin_sets[ $cid ]['_html'] );
					
					//-----------------------------------------
					// (un)altered icons: 
					//-----------------------------------------
					
					$wrapper_icon   = $this->_get_status_of_child( isset($skin_sets[ $cid ]['set_wrapper']) ? $skin_sets[ $cid ]['set_wrapper'] : '' , isset($skin_sets[ $id ]['set_wrapper']) 	? $skin_sets[ $id ]['set_wrapper']	: '' );
					$css_icon       = $this->_get_status_of_child( isset($skin_sets[ $cid ]['set_css']) 	? $skin_sets[ $cid ]['set_css'] 	: '' , isset($skin_sets[ $id ]['set_css']) 		? $skin_sets[ $id ]['set_css'] 		: '' );
					$templates_icon = $this->_get_status_of_child( isset($template_array[ $cid ]) 			? $template_array[ $cid ] 			: '' , isset($template_array[ $id ])			? $template_array[ $id ]			: '' );
					$macro_icon     = $this->_get_status_of_child( isset($macro_array[ $cid ])				? $macro_array[ $cid ]              : '' , isset($macro_array[ $id ])				? $macro_array[ $id ]				: '' );
					
					//-----------------------------------------
					// Fix n' stitch
					//-----------------------------------------
					
					$skin_sets[ $cid ]['_html'] = str_replace( '<!--ALTERED.wrappper-->' , $wrapper_icon  , $skin_sets[ $cid ]['_html'] );
					$skin_sets[ $cid ]['_html'] = str_replace( '<!--ALTERED.templates-->', $templates_icon, $skin_sets[ $cid ]['_html'] );
					$skin_sets[ $cid ]['_html'] = str_replace( '<!--ALTERED.css-->'      , $css_icon      , $skin_sets[ $cid ]['_html'] );
					$skin_sets[ $cid ]['_html'] = str_replace( '<!--ALTERED.macro-->'    , $macro_icon    , $skin_sets[ $cid ]['_html'] );
					
					$html .= $skin_sets[ $cid ]['_html'];
				}
				
				$content = str_replace( "<!--CHILDREN:{$id}-->", $html, $content );
			}
		}
		
		//-----------------------------------------
		// Add in default skin name
		//-----------------------------------------
		
		$this->ipsclass->html = $this->html->skin_sets_overview( $content );
		
		$this->ipsclass->html = str_replace( '<!DEFAULT>', $default_skin, $this->ipsclass->html );
		
		if( IN_DEV )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 
							1 => array( 'section'  , 'lookandfeel'    ),
                            2 => array( 'act'   , 'sets'   ),
							3 => array( 'code'    , 'MOD_mod_xml'    ),
			)  ); 
                                     
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( 'Developers: Export Module Skin Files' );
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
				'<b>WHERE</b> group_name <b>LIKE</b> \'%' . $this->ipsclass->adskin->form_input( 'group_name' ) . '%\' <b>AND</b> set_id = ' . $this->ipsclass->adskin->form_input( 'set_id', 1, '', '', 2 ) ) );

			$this->ipsclass->html .= $this->ipsclass->adskin->end_form( 'Generate & Download XML' );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}		
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	// Get status of a child
	//-----------------------------------------
	
	function _get_status_of_child($this_item, $parent_item)
	{
		if ( $this_item )
		{
			return $this->altered;
		}
		else if ( $parent_item )
		{
			return $this->inherited;
		}
		else
		{
			return $this->unaltered;
		}
	}
	
	//-----------------------------------------
	// Get status of a parent
	//-----------------------------------------
	
	function _get_status_of_parent($this_item)
	{
		if ( ! $this_item )
		{
			return $this->unaltered;
		}
		else
		{
			return $this->altered;
		}
	}
	
	function generate_mod_xml()
	{
		if( empty( $this->ipsclass->input['group_name'] ) )
		{
			$this->list_sets();
			return;
		}
		
		/**
		 * Set ID
		 */
		$this->ipsclass->input['set_id'] = ( $this->ipsclass->input['set_id'] ) ? (int)$this->ipsclass->input['set_id'] : 1;
		
		/**
		 * Require the XML class
		 */
		require_once( KERNEL_PATH . 'class_xml.php' );
		$xml = new class_xml();
		
		/**
		 * Set the root tag
		 */
		$xml->xml_set_root( 'templateexport', array( 'exported' => time(), 'versionid' => '220', 'type' => 'master' ) );
		
		/**
		 * Create base group
		 */
		$xml->xml_add_group( 'templategroup' );
		
		//$content[] = $xml->xml_build_simple_tag( 'description', "This is a descrption" );
		//$entry[]   = $xml->xml_build_entry( 'product', $content, array( 'id' => '1.0' ) );
		//$xml->xml_add_entry_to_group( 'productgroup', $entry );
		//$xml->xml_format_document();
		
		/**
		 * SQL
		 */
		$this->ipsclass->DB->simple_select( '*', 'skin_templates', "group_name LIKE '%{$this->ipsclass->input['group_name']}%' AND set_id = {$this->ipsclass->input['set_id']}" );
		$this->ipsclass->DB->exec_query();
		
		if( $this->ipsclass->DB->get_num_rows() )
		{
			while( $row = $this->ipsclass->DB->fetch_row() )
			{
				unset( $content );
				
				$content[] = $xml->xml_build_simple_tag( 'group_name', $row['group_name'] );
				$content[] = $xml->xml_build_simple_tag( 'section_content', $row['section_content'] );
				$content[] = $xml->xml_build_simple_tag( 'func_name', $row['func_name'] );
				$content[] = $xml->xml_build_simple_tag( 'func_data', $row['func_data'] );
				
				$entry[] = $xml->xml_build_entry( 'template', $content );
			}
			
			$xml->xml_add_entry_to_group( 'templategroup', $entry );
		}
		
		/**
		 * Format
		 */
		$xml->xml_format_document();
		
		/**
		 * Send to browser
		 */
		$this->ipsclass->admin->show_download( $xml->xml_document, 'mod_templates.xml', '', 0 );
	}	
	
}


?>