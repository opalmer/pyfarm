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
|   > $Date: 2007-01-18 23:02:56 +0000 (Thu, 18 Jan 2007) $
|   > $Revision: 831 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Skin Difference Engine
|   > Module written by Matt Mecham
|   > Date started: 22nd July 2005
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_skin_remap
{
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
	var $perm_child = "skinremap";


	function auto_run()
	{
		$this->ipsclass->admin->page_detail = "This section allows you to force a skin set to be used in conjunction with a URL.";
		$this->ipsclass->admin->page_title  = "Skin Remapping";
		
		$this->ipsclass->admin->nav[] 		= array( $this->ipsclass->form_code, 'Skin Remapping Home' );

		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_lookandfeel');
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'remap_remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_remap_remove();
			break;
			case 'remap_add_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_remap_save('add');
			break;
			case 'remap_edit_do':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_remap_save('edit');
			break;
			case 'remap_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_remap_form('edit');
			break;
			case 'remap_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_remap_form('add');
			break;
			case 'remap_list':
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->skin_remap_list();
			break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Skin Remap: Save
	/*-------------------------------------------------------------------------*/
	
	function skin_remap_remove()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$map_id = intval($this->ipsclass->input['map_id']);
		
		//-----------------------------------------
		// Geddit
		//-----------------------------------------
		
		$remap = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*',
																'from'   => 'skin_url_mapping',
																'where'  => 'map_id='.$map_id ) );
		
		
		if ( ! $remap['map_id'] )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again.";
			$this->skin_remap_list();
			return;
		}
		
		//-----------------------------------------
		// Remove it
		//-----------------------------------------
		
		$this->ipsclass->DB->do_delete( 'skin_url_mapping', 'map_id=' . $map_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->skin_remap_recache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->ipsclass->main_msg = "Remapping removed";
		$this->skin_remap_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Skin Remap: Save
	/*-------------------------------------------------------------------------*/
	
	function skin_remap_save( $type='add' )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$map_id              = intval($this->ipsclass->input['map_id']);
		$map_title           = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_htmlspecialchars($_POST['map_title'])) );
		$map_url             = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_UNhtmlspecialchars($_POST['map_url'])) );
		$map_match_type      = trim( $this->ipsclass->input['map_match_type'] );
		$map_skin_set_id     = intval($this->ipsclass->input['map_skin_set_id']);
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $map_id OR ! $map_title OR ! $map_url )
			{
				$this->ipsclass->main_msg = "You must complete the form fully";
				$this->skin_remap_form( $type );
				return;
			}
		}
		else
		{
			if ( ! $map_title OR ! $map_url )
			{
				$this->ipsclass->main_msg = "You must complete the entire form.";
				$this->skin_remap_form( $type );
				return;
			}
		}
	
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 'map_title'       => $map_title,
						'map_url'         => $map_url,
						'map_match_type'  => $map_match_type,
						'map_skin_set_id' => $map_skin_set_id,
					 );
					 
		if ( $type == 'add' )
		{
			$array['map_date_added'] = time();
			
			$this->ipsclass->DB->do_insert( 'skin_url_mapping', $array );
			
			$this->ipsclass->main_msg = 'Skin Remap Added';
		}
		else
		{
			
			$this->ipsclass->DB->do_update( 'skin_url_mapping', $array, 'map_id='.$map_id );
			
			$this->ipsclass->main_msg = 'Skin Remap Edited';
		}
		
		//-----------------------------------------
		// Rebuild skin cache...
		//-----------------------------------------
		
		$this->skin_remap_recache();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->skin_remap_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// Skin Remap: Form
	/*-------------------------------------------------------------------------*/
	
	function skin_remap_form( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$map_id         = intval( $this->ipsclass->input['map_id'] );
		$map_match_type = array( 0 => array( 'contains', 'Contains'   ),
								 1 => array( 'exactly' , 'Is Exactly' ) );
		$form           = array();
		$skins          = array();
		$remap          = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'remap_add_do';
			$title    = "Add New Skin Remap";
			$button   = "Add New Skin Remap";
		}
		else
		{
			$remap = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*',
																	'from'   => 'skin_url_mapping',
																	'where'  => 'map_id='.$map_id ) );
			
			
			if ( ! $remap['map_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->skin_remap_list();
				return;
			}
			
			$formcode = 'remap_edit_do';
			$title    = "Edit Remapping ".$remap['map_title'];
			$button   = "Save Changes";
		}
		
		//-----------------------------------------
		// Figure out skin
		//-----------------------------------------
		
		$_skin_id = ( $_POST['map_skin_set_id'] ) ? $_POST['map_skin_set_id'] : $remap['map_skin_set_id'];
		
		//-----------------------------------------
		// Get skins..
		//-----------------------------------------
		
		$tmp = $this->ipsclass->skin['_setid'];
		
		$this->ipsclass->skin['_setid'] = $_skin_id;
		
		require_once( ROOT_PATH.'sources/classes/class_display.php' );
		$display           =  new display();
		$display->ipsclass =& $this->ipsclass;
		
		$form['skin_list'] = $display->_build_skin_list();
		
		$this->ipsclass->skin['_setid'] = $tmp;
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['map_title']           = $this->ipsclass->adskin->form_input(    'map_title'           , $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['map_title']) AND $_POST['map_title'] ) ? $_POST['map_title'] : $remap['map_title'] ) );
		$form['map_match_type']      = $this->ipsclass->adskin->form_dropdown( 'map_match_type'      , $map_match_type, ( isset($_POST['map_match_type']) AND $_POST['map_match_type'] ) ? $_POST['map_match_type'] : $remap['map_match_type'] );
		$form['map_url']             = $this->ipsclass->adskin->form_input(    'map_url'             , $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['map_url']) AND $_POST['map_url'] ) ? $_POST['map_url'] : $remap['map_url'] ) );
		
		$this->ipsclass->html .= $this->html->skin_remap_form( $form, $title, $formcode, $button, $remap );

		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare skin differences [START]
	/*-------------------------------------------------------------------------*/
	/**
	* Compare skin differences (START)
	*
	*
	* @since	2.1.0.2005-07-22
	*/
	function skin_remap_list()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$content = '';
		$remaps  = array();
		
		//-----------------------------------------
		// Get sessions
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'skin_url_mapping',
												 'order'  => 'map_date_added DESC' ) );
		
		
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// Gen data
			//-----------------------------------------
			
			$row['_date'] = $this->ipsclass->get_date( $row['map_date_added'], 'TINY' );
			$row['_name'] = $this->ipsclass->cache['skin_id_cache'][ $row['map_skin_set_id'] ]['set_name'];
			
			//-----------------------------------------
			// Culmulate
			//-----------------------------------------
			
			$remaps[] = $row;
		}
		
		$this->ipsclass->html = $this->html->skin_remap_overview( $remaps );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Recache the skin stuff
	/*-------------------------------------------------------------------------*/
		
	function skin_remap_recache()
	{
		$this->ipsclass->cache['skin_remap'] = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'skin_url_mapping' ) );
		
		$this->ipsclass->DB->exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['skin_remap'][ $r['map_id'] ] = $r;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'skin_remap', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		return TRUE;
	}
	
}


?>