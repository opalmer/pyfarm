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
|   > $Date: 2006-12-07 06:46:15 -0500 (Thu, 07 Dec 2006) $
|   > $Revision: 777 $
|   > $Author: matt $
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

class ad_skin_wrappers {

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
	var $perm_child = "wrap";
	
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
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->functions->build_editor_area_floated(1);
				break;
			
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_form('edit');
				break;
				
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->save_wrapper('edit');
				break;
				
			case 'export':
				$this->export();
			
			default:
				print "No action chosen"; exit();
				break;
				
			//case 'wrapper':
			//	$this->list_wrappers();
			//	break;
			//case 'add':
			//	$this->add_splash();
			//	break;
			//case 'doadd':
			//	$this->save_wrapper('add');
			//	break;
			//case 'remove':
			//	$this->remove();
			//	break;
		}
		
	}
	
	
	//-----------------------------------------
	// ADD / EDIT WRAPPERS
	//-----------------------------------------
	
	function save_wrapper( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id = intval( $this->ipsclass->input['id'] );
		
		//-----------------------------------------
		// Type?
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("You must specify an existing wrapper ID, go back and try again");
			}
		}
		
		if ($this->ipsclass->input['txtwrapper'] == "")
		{
			$this->ipsclass->admin->error("You can't have an empty template, can you?");
		}
		
		$tmpl = $this->ipsclass->admin->form_to_text( $this->ipsclass->txt_stripslashes($_POST['txtwrapper']) );
		$tmpl = str_replace( '&#46;&#46;/', '../' , $tmpl );
		
		if ( ! preg_match( "/<% BOARD %>/", $tmpl ) )
		{
			$this->ipsclass->admin->error("You cannot remove the &lt% BOARD %> tag silly!");
		}
		
		if ( ! preg_match( "/<% COPYRIGHT %>/", $tmpl ) )
		{
			$this->ipsclass->admin->error("You cannot remove the &lt% COPYRIGHT %> tag silly!");
		}
		
		$this->ipsclass->DB->do_update( 'skin_sets', array( 'set_wrapper' => $tmpl ), 'set_skin_set_id='.$id );
		
		$this->ipsclass->cache_func->_recache_wrapper( $this->ipsclass->input['id'] );
		
		//-----------------------------------------
		// Done
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['savereload'] )
		{
			$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code ,'Skin Manager Home' );
			$this->ipsclass->main_msg = "Board Header and Footer Wrapper Updated";
			$this->ipsclass->admin->done_screen("Board Header and Footer Wrapper Updated", "Skin Manager Home", 'section='.$this->ipsclass->section_code.'&act=sets', "redirect" );
		}
		else
		{
			//-----------------------------------------
			// Reload edit window
			//-----------------------------------------
			
			$this->ipsclass->main_msg = "Board Header and Footer Wrapper updated";
			$this->do_form('edit');
		}
		
	}
	
	//-----------------------------------------
	// FORM
	//-----------------------------------------
	
	function do_form( $type='add' )
	{
		//-----------------------------------------
		// Check input
		//-----------------------------------------
		
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing skin set ID, go back and try again");
		}
		
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
		
		$this->ipsclass->DB->cache_add_query( 'stylesheets_do_form_concat', array( 'id' => intval($this->ipsclass->input['id']), 'parent' => $in ) );
		$this->ipsclass->DB->cache_exec_query();
		
		//-----------------------------------------
		// check tree...
		//-----------------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			if ( $row['set_wrapper'] and ! $found_id )
			{
				$found_id      = $row['set_skin_set_id'];
				$found_content = $row['set_wrapper'];
			}
			
			if ( $this->ipsclass->input['id'] == $row['set_skin_set_id'] )
			{
				$this_set = $row;
			}
		}
		
		if ($type == 'add')
		{
			$code = 'doadd';
			$button = 'Create Wrapper';
		}
		else
		{
			$code = 'doedit';
			$button = 'Save Wrapper';
		}
		
		//-----------------------------------------
		// Header
		//-----------------------------------------
	
		$this->ipsclass->admin->page_detail = "You may use HTML fully when adding or editing wrappers.";
		$this->ipsclass->admin->page_title  = "Editing Board Header and Footer Wrapper";
		
		if ( $found_id == 1 )
		{
			$this->ipsclass->admin->page_detail .= "<br /><strong>This is a copy of the master wrapper, editing it below will copy the changes to a new wrapper unique to this skin set and any children of this set
											  will inherit this wrapper</strong>";
		}
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $code      ),
																			 2 => array( 'act'   , 'wrap'     ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']   ),
																			 4 => array( 'fid'   , $found_id  ),
																			 5 => array( 'section', $this->ipsclass->section_code ),
																	), "theform"     );
									     
		//-----------------------------------------
		// Stop /textarea murdering layout
		//-----------------------------------------
		
		$found_content = $this->ipsclass->admin->text_to_form( $found_content );
		
		//-----------------------------------------
		// Editor section
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->functions->build_generic_editor_area( array( 'section' => $this->ipsclass->section_code, 'act' => 'wrap', 'title' => '', 'textareaname' => 'wrapper', 'textareainput' => $found_content ) );
		
		$formbuttons = "<div align='center' class='tablesubheader'>
						<input type='submit' name='submit' value='$button' class='realdarkbutton'>
						<input type='submit' name='savereload' value='Save and Reload Wrapper' class='realdarkbutton'>
						</div></form>\n";
		
		$this->ipsclass->html = str_replace( '<!--IPB.EDITORBOTTOM-->', $formbuttons, $this->ipsclass->html );
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->skin_jump_menu_wrap();
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->ipsclass->admin->nav[] = array( 'section='.$this->ipsclass->section_code.'&act=sets' ,'Skin Manager Home' );
		$this->ipsclass->admin->nav[] = array( '' ,'Editing Board Wrapper in set '.$this_set['set_name'] );
		
		$this->ipsclass->admin->output();
	}
	
	
	
}


?>