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
|   > $Date: 2007-04-03 11:00:46 -0400 (Tue, 03 Apr 2007) $
|   > $Revision: 920 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > CSS management functions
|   > Module written by Matt Mecham
|   > Date started: 4th April 2002
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


class ad_skin_css
{

	var $base_url;
	var $template = "";
	var $functions = "";
	
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
	var $perm_child = "style";


	function auto_run()
	{
		//-----------------------------------------
		// Get the libraries
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/admin_template_functions.php' );
		
		$this->functions = new admin_template_functions();
		$this->functions->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'floateditor':
				$this->functions->build_editor_area_floated(1);
				break;
				
			
			case 'edit2':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_form('edit');
				break;
				
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_form('edit');
				break;
				
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->save_css('edit');
				break;
				
			case 'optimize':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->optimize();
				break;
				
			case 'easyedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->easy_edit();
				break;
				
			case 'doresync':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_resynch();
				break;
			
			case 'colouredit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->colouredit();
				break;
				
			case 'docolour':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_colouredit();
				break;
			
			default:
				print "No action taken"; exit();
				break;
				
			//case 'wrapper':
			//	$this->list_sheets();
			//	break;
			//case 'add':
			//	$this->do_form('add');
			//	break;
			//case 'doadd':
			//	$this->save_css('add');
			//	break;
			//case 'remove':
			//	$this->remove();
			//	break;
			//case 'css_upload':
			//	$this->css_upload('new');
			//	break;
			//case 'export':
			//	$this->export();
			//	break;
		}
		
	}
	
	//-----------------------------------------
	// RESYNCH STYLE SHEETS
	//-----------------------------------------
	
	function do_resynch()
	{
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' 	=> 'cssid, css_text, css_name, css_comments',
												 'from'		=> 'skin_css',
												 'where' 	=> 'cssid='.intval($this->ipsclass->input['id'])
										)		);
		$this->ipsclass->DB->exec_query();
		
		if ( ! $cssinfo = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not query the CSS details from the database");
		}
		
		if ( $this->ipsclass->input['favour'] == 'cache' )
		{
			$cache_file = ROOT_PATH."cache/css_".$this->ipsclass->input['id'].".css";
			
			if ( file_exists( $cache_file ) )
			{
				$FH = fopen( $cache_file, 'r' );
				$cache_data = fread( $FH, filesize($cache_file) );
				fclose($FH);
			}
			else
			{
				$this->ipsclass->admin->error("Could not locate cached CSS file @ $cache_file");
			}
			
			$this->ipsclass->DB->do_update( 'skin_css', array( 'css_text' => $cache_data ), 'cssid='.intval($this->ipsclass->input['id']) );
		}
		else
		{
			$cache_file = ROOT_PATH."cache/css_".$this->ipsclass->input['id'].".css";
			
			$FH = fopen( $cache_file, 'w' );
			fputs( $FH, $cssinfo['css_text'], strlen($cssinfo['css_text']) );
			fclose($FH);
		}
		
		if ( $this->ipsclass->input['return'] != 'colouredit' )
		{
			$this->do_form('edit');
		}
		else
		{
			$this->colouredit();
		}
	}
	
	
	
	//-----------------------------------------
	// RESYNCH SPLASH
	//-----------------------------------------
	
	function resync_splash($db_length, $cache_length, $cache_mtime, $db_mtime, $id, $return="")
	{
		//-----------------------------------------
	
		$this->ipsclass->admin->page_detail = "A mismatch has been found between the cached style sheet and the style sheet stored in the database";
		$this->ipsclass->admin->page_title  = "Resynchronise Style Sheet";
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "50%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "50%" );

		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'doresync'  ),
																			 2 => array( 'act'   , 'style'     ),
																			 3 => array( 'id'    , $id         ),
																			 4 => array( 'return', $return     ),
																			 5 => array( 'section', $this->ipsclass->section_code ),
																	)    );
									     
		$favour = 'db';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Resynch CSS before editing..." );
		
		if ( intval($cache_mtime) > intval($db_mtime) )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
														"<b>CSS in database last updated:</b> ".$this->ipsclass->admin->get_date($db_mtime, 'LONG'),
														"<b>CSS in database, # characters:</b> $db_length",
											 )      );
											 
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
														"<span style='color:red'><b>CSS in CACHE last updated:</b> ".$this->ipsclass->admin->get_date($cache_mtime, 'LONG')."</span>",
														"<span style='color:red'><b>CSS in CACHE, # characters:</b> $cache_length</span>",
											 )      );
			$favour = 'cache';
											 
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
														"<span style='color:red'><b>CSS in database last updated:</b> ".$this->ipsclass->admin->get_date($db_mtime, 'LONG')."</span>",
														"<span style='color:red'><b>CSS in database, # characters:</b> $db_length</span>",
											 )      );
											 
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
														"<b>CSS in CACHE last updated:</b> ".$this->ipsclass->admin->get_date($cache_mtime, 'LONG'),
														"<b>CSS in CACHE, # characters:</b> $cache_length",
											 )      );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
														"<b>Resynchronise using....</b>",
														$this->ipsclass->adskin->form_dropdown( 'favour', array(
																							    0 => array( 'cache', 'Overwrite database version with cached version'),
																							    1 => array( 'db'   , 'Update cached version from the database' ),
																							 ), $favour ),
											 )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Resynchronise");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	
	//-----------------------------------------
	// OPTIMIZE STYLE SHEET
	//-----------------------------------------
	
	function optimize()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing CSS ID, go back and try again");
		}
		
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'skin_css', 'where' => 'cssid='.intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->exec_query();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not query the information from the database");
		}
		
		//-----------------------------------------
		
		$orig_size = strlen($row['css_text']);
		
		$orig_text = str_replace( "\r\n", "\n", $row['css_text']);
		$orig_text = str_replace( "\r"  , "\n", $orig_text);
		$orig_text = str_replace( "\n\n", "\n", $orig_text);
		
		$parsed = array();
		
		//-----------------------------------------
		// Remove comments
		//-----------------------------------------
		
		$orig_text = preg_replace( "#/\*(.+?)\*/#s", "", $orig_text );
		
		//-----------------------------------------
		// Grab all the definitions
		//-----------------------------------------
		
		preg_match_all( "/(.+?)\{(.+?)\}/s", $orig_text, $match, PREG_PATTERN_ORDER );
		
		for ( $i = 0 ; $i < count($match[0]); $i++ )
		{
			$match[1][$i] = trim($match[1][$i]);
			$parsed[ $match[1][$i] ] = trim($match[2][$i]);
		}
		
		//-----------------------------------------
		
		if ( count($parsed) < 1)
		{
			$this->ipsclass->admin->error("The stylesheet is in a format that Invision Power Board cannot understand, no optimization done.");
		}
		
		//-----------------------------------------
		// Clean them up
		//-----------------------------------------
		
		$final = "";
		
		foreach( $parsed as $name => $p )
		{
			//-----------------------------------------
			// Ignore comments
			//-----------------------------------------
			
			if ( preg_match( "#^//#", $name) )
			{
				continue;
			}
			
			//-----------------------------------------
			// Split up the components
			//-----------------------------------------
			
			$parts = explode( ";", $p);
			$defs  = array();
			
			foreach( $parts as $part )
			{
				if ($part != "")
				{
					list($definition, $data) = explode( ":", $part );
					$defs[]   = trim($definition).": ".trim($data);
				}
			}
			
			$final .= $name . " { ".implode("; ", $defs). " }\n";
		}
		
		$final_size = strlen($final);
		
		if ($final_size < 1000)
		{
			$this->ipsclass->admin->error("The stylesheet is in a format that Invision Power Board cannot understand, no optimization done.");
		}
		
		//-----------------------------------------
		// Update the DB
		//-----------------------------------------
		
		$dbs = $this->ipsclass->DB->compile_db_update_string(  );
		
		$this->ipsclass->DB->do_update( 'skin_css', array( 'css_text' => $final ), 'cssid='.intval($this->ipsclass->input['id']) );
		
		$saved    = $orig_size - $final_size;
		$pc_saved = 0;
		
		if ($saved > 0)
		{
			$pc_saved = sprintf( "%.2f", ($saved / $orig_size) * 100);
		}
		
		$this->ipsclass->admin->done_screen("Stylesheet updated: Characters Saved: $saved ($pc_saved %)", "Manage Style Sheets", "{$this->ipsclass->form_code}" );
	}
	
	
	//-----------------------------------------
	// ADD / EDIT WRAPPERS
	//-----------------------------------------
	
	function save_css( $type='add' )
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// Check input
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("You must specify an existing CSS ID, go back and try again");
			}
			
		}
		
		if ($this->ipsclass->input['txtcss'] == "")
		{
			$this->ipsclass->admin->error("You can't have an empty stylesheet, can you?");
		}
		
		
		$css = $this->ipsclass->txt_stripslashes($_POST['txtcss']);
		$css = str_replace( '&#46;&#46;/', '../' , $css );
		$css = str_replace( '\\'         , '\\\\', $css );
		$css = str_replace( '&#60;#IMG_DIR#&#62;', '<#IMG_DIR#>', $css );
		
		$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_css' => $css, 'set_css_updated' => time() ), 'set_skin_set_id='.$this->ipsclass->input['id'] );
		
		//-----------------------------------------
		// Update cache?
		//-----------------------------------------
		
		$extra = "<b>Stylesheet cache file updated</b>";
		
		$message = $this->ipsclass->cache_func->_write_css_to_cache( $this->ipsclass->input['id'] );
		
		//-----------------------------------------
		// Back to it...
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['savereload'] )
		{
			$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code ,'Skin Manager Home' );
			$this->ipsclass->main_msg = "Stylesheet updated : $extra";
			$this->ipsclass->admin->redirect( $this->ipsclass->base_url.'&section='.$this->ipsclass->section_code.'&act=sets', "Stylesheet updated, returning to the skin manager" );
		}
		else
		{
			//-----------------------------------------
			// Reload edit window
			//-----------------------------------------
			
			$this->ipsclass->main_msg = "Stylesheet updated : $extra";
			$this->do_form('edit');
		}
	}
	
	
	//-----------------------------------------
	// Show Add/Edit form
	//-----------------------------------------
	
	function do_form( $type='add' )
	{
		//-----------------------------------------
		
		if ( $this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing wrapper ID, go back and try again");
		}
		
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		$found_id      = "";
		$found_content = "";
		$this_set      = "";
		
		$in = $this->ipsclass->input['p'] > 0 ? ','.intval($this->ipsclass->input['p']) : '';
		
		//-----------------------------------------
		// Query
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'stylesheets_do_form_concat', array( 'id' => $this->ipsclass->input['id'], 'parent' => $in ) );
		$this->ipsclass->DB->cache_exec_query();
		
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{ 
			if ( $row['set_css'] and ! $found_id )
			{
				$found_id      = $row['set_skin_set_id'];
				$found_content = $row['set_css'];
				$found_time    = $row['set_css_updated'];
			}
			
			if ( $this->ipsclass->input['id'] == $row['set_skin_set_id'] )
			{
				$this_set = $row;
			}
		}
		
		//-----------------------------------------
		
		$css    = $found_content;
		
		$code   = 'doedit';
		$button = 'Save Stylesheet';
		
		//-----------------------------------------
		// Preserve <#IMG_DIR#>
		//-----------------------------------------
		
		$css = str_replace( '<#IMG_DIR#>', '&#60;#IMG_DIR#&#62;', $css );
		
		//-----------------------------------------
		// COLURS!ooO!
		//-----------------------------------------
		
		//.class { definitions }
		//#id { definitions }
		
		$css_elements = array();
		
		preg_match_all( "/(\.|\#)(\S+?)\s{0,}\{.+?\}/s", $css, $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			$type = trim($match[1][$i]);
			
			$name = trim($match[2][$i]);
			
			if ($type == '.')
			{
				$css_elements[] = array( 'class|'.$name, $type.$name );
			}
			else
			{
				$css_elements[] = array( 'id|'.$name, $type.$name );
			}
		}
			
		//-----------------------------------------
	
		$this->ipsclass->admin->page_detail = "You may use CSS fully when adding or editing stylesheets.<br />Click <a href='#' onclick='ipsclass.pop_up_window(\"{$this->ipsclass->base_url}&act=rtempl&code=css_diff&id={$this->ipsclass->input['id']}\", 800, 600 )'>here</a> to view the changes between this CSS and the default skin CSS.";
		$this->ipsclass->admin->page_title  = "Manage Style Sheets";
		
		//-----------------------------------------
		
		$this->ipsclass->html .= "<script language='javascript'>
		                 <!--
		                 function cssSearch(theID)
		                 {
		                 	cssChosen = document.cssForm.csschoice.options[document.cssForm.csschoice.selectedIndex].value;
		                 	
		                 	window.open('{$this->ipsclass->base_url}&act=rtempl&code=css_search&id='+theID+'&element='+cssChosen,'CSSSEARCH','width=400,height=500,resizable=yes,scrollbars=yes');
		                 }
		                 
		                 function cssPreview(theID)
		                 {
		                 	cssChosen = document.cssForm.csschoice.options[document.cssForm.csschoice.selectedIndex].value;
		                 	
		                 	window.open('{$this->ipsclass->base_url}&act=rtempl&code=css_preview&id='+theID+'&element='+cssChosen,'CSSSEARCH','width=400,height=500,resizable=yes,scrollbars=yes');
		                 }
		                 
		                 //-->
		                 </script>";
		
		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $code      ),
																			 2 => array( 'act'   , 'style'      ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']   ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	), "theform"     );
									     
		//-----------------------------------------
		// Editor section
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->functions->build_generic_editor_area( array( 'section' => $this->ipsclass->section_code, 'act' => 'style', 'title' => '', 'textareaname' => 'css', 'textareainput' => $css ) );
		
		$formbuttons = "<div align='center' class='tablesubheader'>
						<input type='submit' name='submit' value='$button' class='realdarkbutton'>
						<input type='submit' name='savereload' value='Save and Reload Stylesheet' class='realdarkbutton'>
						</div></form>\n";
		
		$this->ipsclass->html = str_replace( '<!--IPB.EDITORBOTTOM-->', $formbuttons, $this->ipsclass->html );
		
		$this->ipsclass->html .= "<br />";
		
		//-----------------------------------------
		// CSS search form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'   , 'css_search' ),
																			 2 => array( 'act'    , 'style'      ),
																			 3 => array( 'id'     , $this->ipsclass->input['id']  ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	), "cssForm" );
		
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "80%" );

		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Find CSS Usage" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( 
																			   "Show me where...",
																			   $this->ipsclass->adskin->form_dropdown('csschoice', $css_elements).' ... is used within the templates &nbsp;'
																			  .'<input type="button" value="Go!" onClick="cssSearch(\''.$this->ipsclass->input['id'].'\');" id="editbutton">'
																			  .'&nbsp;<input type="button" value="Preview CSS Style" onClick="cssPreview(\''.$this->ipsclass->input['id'].'\');" id="editbutton">'
																	)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form();
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= $this->ipsclass->adskin->skin_jump_menu_wrap();
										 
		//-----------------------------------------
		
		$this->ipsclass->admin->nav[] = array( 'section='.$this->ipsclass->section_code.'&act=sets' ,'Skin Manager Home' );
		$this->ipsclass->admin->nav[] = array( '' ,'Editing Style Sheet in set '.$this_set['set_name'] );
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	// EDIT COLOURS START
	//-----------------------------------------
	
	function colouredit()
	{
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing CSS ID, go back and try again");
		}
		
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		$found_id      = "";
		$found_content = "";
		$this_set      = "";
		
		if ( $this->ipsclass->input['p'] > 0 )
		{
			$in = ','.intval($this->ipsclass->input['p']);
		}
		
		//-----------------------------------------
		// Query
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'stylesheets_do_form_concat', array( 'id' => $this->ipsclass->input['id'], 'parent' => $in ) );
		$this->ipsclass->DB->cache_exec_query();
			        
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			if ( $row['set_css'] and ! $found_id )
			{
				$found_id      = $row['set_skin_set_id'];
				$found_content = $row['set_css'];
				$found_time    = $row['set_css_updated'];
			}
			
			if ( $this->ipsclass->input['id'] == $row['set_skin_set_id'] )
			{
				$this_set = $row;
			}
		}
		
		//-----------------------------------------
		
		$css = $found_content;
		$css = preg_replace( "#/\*.+?\*/#s", "", $css );
		//print "<pre>"; print $css; exit();
		//-----------------------------------------
		// Start the CSS matcher thingy
		//-----------------------------------------
		
		//.class { definitions }
		//#id { definitions }
		
		$colours = array();
		
		//-----------------------------------------
		// Make http:// safe..
		//-----------------------------------------
		
		$css = str_replace( 'http://', 'http|//', $css );
		
		preg_match_all( "/([\:\.\#\w\s,\-]+)\{(.+?)\}/s", $css, $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			$name    = trim($match[1][$i]);
			$content = trim($match[2][$i]);
			
			$defs    = explode( ';', $content );
			
			if ( count( $defs ) > 0 )
			{
				foreach( $defs as $a )
				{
					$a = trim($a);
					
					if ( $a != "" )
					{
						list( $property, $value ) = explode( ":", $a, 2 );
						
						$property = trim($property);
						$value    = trim( str_replace( 'http|//', 'http://', $value) );
						
						if ( $property )
						{
							if ( $property == 'color' or $property == 'background-color' )
							{
								$colours[ $name ][$property] = $value;
							}
							else
							{
								$colours[ $name ]['_extra'] .= $property.':'.$value.';'."\n";
							}
						}
					}
				}
			}
		}
		
		//print "<pre>"; print_r( $colours ); exit();
		
		if ( count($colours) < 1 )
		{
			$this->ipsclass->admin->error("CSS all gone wonky! No colours to edit");
		}
		
		//-----------------------------------------
		
		// Get $skin_names stuff
		
		require ROOT_PATH .'sources/lib/skin_info.php';
	
		$this->ipsclass->admin->page_detail = "You edit the existing colours below. <strong><a href='{$this->ipsclass->vars['board_url']}/skin_acp/IPB2_Standard/colours.html' target='_blank'>Launch Colour Picker</a></center></strong>";
		$this->ipsclass->admin->page_title  = "Manage Style Sheets [ Colours ]";
		
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'docolour'   ),
																			 2 => array( 'act'   , 'style'      ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']    ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)    );
									     
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "{none}" , "100%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= "<input type='hidden' name='initcol' value='' />
							<input type='hidden' name='initformval' value='' />
							<script type='text/javascript'>
							function updatecolor( id )
							{
								itm = my_getbyid( id );
								
								if ( itm )
								{
									eval(\"newcol = document.theAdminForm.f\"+id+\".value\");
									itm.style.backgroundColor = newcol;
								}
								
							}
							function poppicker( initcolor, formfield )
							{
								if ( initcolor )
								{
									document.theAdminForm.initcol.value = initcolor;
								}
								
								document.theAdminForm.initformval.value = formfield;
								
								PopUp( '{$this->ipsclass->vars['board_url']}/skin_acp/IPB2_Standard/colours.html', 'PopPicker', 400, 500 );
							}
						    </script>";
						    
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "CSS Colours" );
		$this->ipsclass->html .= "<td class='tablerow2'>";
		
		foreach ( $colours as $prop => $val )
		{
			$tbl_colour = "";
			$tbl_bg     = "";
			$tbl_html   = "";
			
			$desc = $css_names[ $prop ];
			
			if ( $desc == "" )
			{
				$desc = 'None available';
			}
			
			$name = $prop;
			
			$md5 = md5($name);
			
			if ( strlen($name) > 80 )
			{
				$name = substr( $name, 0, 80 ) .'...';
			}
			
			$font_box  = $this->ipsclass->adskin->form_simple_input('f'.$md5.'color'           , $val['color'], "14");
			$bgcol_box = $this->ipsclass->adskin->form_simple_input('f'.$md5.'backgroundcolor' , $val['background-color'], "14");
			
			$this->ipsclass->html .= "<div class='tablerow1'>
								 <fieldset>
								  <legend><strong style='font-size:14px'>{$name}</strong></legend>
								  <table width='100%' border='0' cellpadding='4' cellspacing='0'>
								  <tr>
								   <td width='40%' valign='top'>
								    <fieldset>
								     <legend><strong>Font Color</strong></legend>
										{$font_box}&nbsp;&nbsp;<input type='text' id='{$md5}color' onclick=\"updatecolor('{$md5}color')\" size='6' style='border:1px solid black;background-color:{$val['color']}' readonly='readonly'>&nbsp;<a href='#' title='launch color picker' onclick=\"poppicker('{$val['color']}', '{$md5}color'); return false;\"><img src='{$this->ipsclass->skin_acp_url}/images/colorselect.png' border='0' /></a>
									</fieldset>
									<br />
									<fieldset>
									 <legend><strong>Background Color</strong></legend>
			 						    {$bgcol_box}&nbsp;&nbsp;<input type='text' id='{$md5}backgroundcolor'  onclick=\"updatecolor('{$md5}backgroundcolor')\" size='6' style='border:1px solid black;background-color:{$val['background-color']}' readonly='readonly'>&nbsp;<a href='#' title='launch color picker' onclick=\"poppicker('{$val['background-color']}', '{$md5}backgroundcolor'); return false;\"><img src='{$this->ipsclass->skin_acp_url}/images/colorselect.png' border='0' /></a>
			 						</fieldset>
			 					   </td>
			 					   <td width='60%' valign='top'>
			 					   <fieldset>
									 <legend><strong>Other CSS Attributes</strong></legend>
			 						    <textarea class='textinput' cols='40' rows='5' style='width:100%' name='f{$md5}extra'>{$val['_extra']}</textarea>
			 						</fieldset>
			 					   </td>
			 					  </tr>
			 					  </table>
			 					 </fieldset>
			 					 </div>";
		}
		
		$this->ipsclass->html .= "</td>";
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Edit");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
		
		
	}
	
	//-----------------------------------------
	// EDIT COLOURS START
	//-----------------------------------------
	
	function do_colouredit()
	{
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing CSS ID, go back and try again");
		}
		
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		$found_id      = "";
		$found_content = "";
		$this_set      = "";
		
		if ( $this->ipsclass->input['p'] > 0 )
		{
			$in = ','.intval($this->ipsclass->input['p']);
		}
		
		//-----------------------------------------
		// Query
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'stylesheets_do_form_concat', array( 'id' => $this->ipsclass->input['id'], 'parent' => $in ) );
		$this->ipsclass->DB->cache_exec_query();
			        
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			if ( $row['set_css'] and ! $found_id )
			{
				$found_id      = $row['set_skin_set_id'];
				$found_content = $row['set_css'];
				$found_time    = $row['set_css_updated'];
			}
			
			if ( $this->ipsclass->input['id'] == $row['set_skin_set_id'] )
			{
				$this_set = $row;
			}
		}
		
		//-----------------------------------------
		
		$css = $found_content;
		$css = preg_replace( "#/\*.+?\*/#s", "", $css );
		
		//-----------------------------------------
		// Start the CSS matcher thingy
		//-----------------------------------------
		
		$css     = str_replace( 'http://', 'http|//', $css );
		
		$colours = array();
		
		preg_match_all( "/([\:\.\#\w\s,\-]+)\{(.+?)\}/s", $css, $match );
		
		for ($i=0; $i < count($match[0]); $i++)
		{
			$name    = trim($match[1][$i]);
			$content = trim($match[2][$i]);
			
			$md5     = md5($name);
			
			$defs    = explode( ';', $content );
			
			if ( count( $defs ) > 0 )
			{
				foreach( $defs as $a )
				{
					$a = trim($a);
					
					if ( $a != "" )
					{
						list( $property, $value ) = explode( ":", $a, 2 );
						
						$property = trim($property);
						$value    = trim( str_replace( 'http|//', 'http://', $value) );
						
						if ( $property )
						{
							$colours[ $name ][$property] = $value;
						}
					}
				}
			}
			
			foreach( array( 'color', 'backgroundcolor' ) as $prop )
			{
				if ( strlen($_POST['f'.$md5.$prop]) >= 1 )
				{
					$field = $prop == 'backgroundcolor' ? 'background-color' : $prop;
					
					$colours[ $name ][$field] = stripslashes($_POST['f'.$md5.$prop]);
				}
			}
			
			if ( isset( $_POST['f'.$md5.'extra'] ) )
			{
				$tmp = str_replace( "\n", "", $_POST['f'.$md5.'extra'] );
				$tmp = str_replace( "\r", "", $tmp );
				
				$extra_attr = explode( ";", $tmp );
				
				if ( is_array( $extra_attr ) and count( $extra_attr ) )
				{
					foreach( $extra_attr as $l )
					{
						$l = str_replace( 'http://', 'http|//', $l );
						
						list( $p, $v ) = explode( ":", $l );
						
						$colours[ $name ][ trim($p) ] = trim( str_replace( 'http|//', 'http://', $v) );
					}
				}
			}
		}
		
		if ( count($colours) < 1 )
		{
			$this->ipsclass->admin->error("CSS all gone wonky! No colours to edit");
		}
		
		//-----------------------------------------
		
		unset($name);
		unset($property);
		
		$final = "";
		
		foreach( $colours as $name => $property )
		{
			$final .= $name."\n{\n";
			
			if ( is_array($property) and count($property) > 0 )
			{
				foreach( $property as $key => $value )
				{
					if ( $key AND isset($value) )
					{
						$final .= "\t".$key.": ".$value.";\n";
					}
				}
			}
			
			$final .= "}\n\n";
		
		}
		
		$this->ipsclass->input['txtcss']     = $final;
		$_POST['txtcss']                     = $final;
		$this->ipsclass->input['savereload'] = 0;
		$this->save_css('edit');
	}
	
	
	
}


?>