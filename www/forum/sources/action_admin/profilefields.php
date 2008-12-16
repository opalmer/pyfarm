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
|   > $Date: 2007-01-11 17:33:01 -0500 (Thu, 11 Jan 2007) $
|   > $Revision: 826 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Custom profile field functions
|   > Module written by Matt Mecham
|   > Date started: 24th June 2002
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


class ad_profilefields {

	var $base_url;
	var $func;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "content";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "field";
	
	function auto_run()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Custom Profile Fields' );
		
		//-----------------------------------------
		// get class
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
		$this->func = new custom_fields( $DB );
		
		//-----------------------------------------
		// switch-a-magoo
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->main_form('add');
				break;
				
			case 'doadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->main_save('add');
				break;
				
			case 'edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->main_form('edit');
				break;
				
			case 'doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->main_save('edit');
				break;
				
			case 'delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->delete_form();
				break;
				
			case 'dodelete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->do_delete();
				break;
						
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->main_screen();
				break;
		}
		
	}
	
	//-----------------------------------------
	//
	// Rebuild cache
	//
	//-----------------------------------------
	
	function rebuild_cache()
	{
		$this->ipsclass->cache['profilefields'] = array();
				
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'pfields_data', 'order' => 'pf_position' ) );
						 
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['profilefields'][ $r['pf_id'] ] = $r;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'profilefields', 'array' => 1, 'deletefirst' => 1 ) );	
	}
	
	//-----------------------------------------
	//
	// Delete a group
	//
	//-----------------------------------------
	
	function delete_form()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the group ID, please try again");
		}
		
		$this->ipsclass->admin->page_title = "Deleting a Custom Profile Field";
		
		$this->ipsclass->admin->page_detail = "Please check to ensure that you are attempting to remove the correct custom profile field as <b>all data will be lost!</b>.";
		
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $field = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not fetch the row from the database");
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'dodelete'  ),
																 2 => array( 'act'   , 'field'     ),
																 3 => array( 'id'    , $this->ipsclass->input['id']   ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														)      );
									     
		
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Removal Confirmation" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Custom Profile field to remove</b>" ,
												                 "<b>".$field['pf_title']."</b>",
									                   )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Delete this custom field");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	
	
	function do_delete()
	{
		if ($this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("Could not resolve the field ID, please try again");
		}
		
		//-----------------------------------------
		// Check to make sure that the relevant groups exist.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_id=".intval($this->ipsclass->input['id']) ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could not resolve the ID's passed to deletion");
		}
		
		$this->ipsclass->DB->sql_drop_field( 'pfields_content', "field_{$row['pf_id']}" );
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'pfields_data', 'where' => "pf_id=".intval($this->ipsclass->input['id']) ) );
		
		$this->rebuild_cache();
		
		$this->ipsclass->admin->done_screen("Profile Field Removed", "Custom Profile Field Control", "{$this->ipsclass->form_code}", 'redirect' );
		
	}
	
	
	//-----------------------------------------
	//
	// Save changes to DB
	//
	//-----------------------------------------
	
	function main_save($type='edit')
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		if ($this->ipsclass->input['pf_title'] == "")
		{
			$this->ipsclass->admin->error("You must enter a field title.");
		}
		
		//-----------------------------------------
		// check-da-motcha
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			if ($this->ipsclass->input['id'] == "")
			{
				$this->ipsclass->admin->error("Could not resolve the field id");
			}
			
		}
		
		$content = "";
		
		if ( $_POST['pf_content'] != "")
		{
			$content = $this->func->method_format_content_for_save( $_POST['pf_content'] );
		}
		
		$db_string = array( 'pf_title'        => $this->ipsclass->input['pf_title'],
						    'pf_desc'         => $this->ipsclass->input['pf_desc'],
						    'pf_content'      => $this->ipsclass->txt_stripslashes($content),
						    'pf_type'         => $this->ipsclass->input['pf_type'],
						    'pf_not_null'     => intval($this->ipsclass->input['pf_not_null']),
						    'pf_member_hide'  => intval($this->ipsclass->input['pf_member_hide']),
						    'pf_max_input'    => intval($this->ipsclass->input['pf_max_input']),
						    'pf_member_edit'  => intval($this->ipsclass->input['pf_member_edit']),
						    'pf_position'     => intval($this->ipsclass->input['pf_position']),
						    'pf_show_on_reg'  => intval($this->ipsclass->input['pf_show_on_reg']),
						    'pf_input_format' => $this->ipsclass->input['pf_input_format'],
						    'pf_admin_only'   => intval($this->ipsclass->input['pf_admin_only']),
						    'pf_topic_format' => $this->ipsclass->txt_stripslashes( $_POST['pf_topic_format']),
						  );
		
						  
		if ($type == 'edit')
		{
			$this->ipsclass->DB->do_update( 'pfields_data', $db_string, 'pf_id='.$this->ipsclass->input['id'] );
			
			$this->rebuild_cache();
			
			$this->ipsclass->main_msg = "Profile Field Edited";
			$this->main_screen();
			
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'pfields_data', $db_string );
			
			$new_id = $this->ipsclass->DB->get_insert_id();
			
			$this->ipsclass->DB->sql_add_field( 'pfields_content', "field_$new_id", 'text' );
			
			$this->ipsclass->DB->sql_optimize_table( 'pfields_content' );
			
			$this->rebuild_cache();
			
			$this->ipsclass->main_msg = "Profile Field Added";
			$this->main_screen();
		}
	}
	
	
	//-----------------------------------------
	//
	// Add / edit group
	//
	//-----------------------------------------
	
	function main_form($type='edit')
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit Custom Profile Field' );
		
		if ($type == 'edit')
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->admin->error("No group id to select from the database, please try again.");
			}
			
			$form_code = 'doedit';
			$button    = 'Complete Edit';
				
		}
		else
		{
			$form_code = 'doadd';
			$button    = 'Add Field';
		}
		
		//-----------------------------------------
		// get field from db
		//-----------------------------------------
		
		if ( $this->ipsclass->input['id'] )
		{
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_id=".intval($this->ipsclass->input['id']) ) );
			$this->ipsclass->DB->simple_exec();
		
			$fields = $this->ipsclass->DB->fetch_row();
		}
		else
		{
			$fields = array( 'pf_topic_format' => '{title}: {content}<br />' );
		}
		
		//-----------------------------------------
		// Top 'o 'the mornin'
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			$this->ipsclass->admin->page_title = "Editing Profile Field ".$fields['pf_title'];
		}
		else
		{
			$this->ipsclass->admin->page_title = 'Adding a new profile field';
			$fields = array( 'pf_title'			=> '',
							 'pf_content'		=> '',
							 'pf_desc'			=> '',
							 'pf_type'			=> '',
							 'pf_max_input'		=> '',
							 'pf_position'		=> '',
							 'pf_input_format' 	=> '',
							 'pf_topic_format'	=> '',
							 'pf_show_on_reg'	=> '',
							 'pf_not_null'		=> '',
							 'pf_member_edit'	=> '',
							 'pf_member_hide'	=> '',
							 'pf_admin_only'	=> '' );
		}
		
		//-----------------------------------------
		// Wise words
		//-----------------------------------------
		
		$this->ipsclass->admin->page_detail = "Please double check the information before submitting the form.";
		
		//-----------------------------------------
		// Start form
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $form_code  ),
												                 2 => array( 'act'   , 'field'     ),
												                 3 => array( 'id'    , $this->ipsclass->input['id']   ),
												                 4 => array( 'section', $this->ipsclass->section_code ),
									                    )     );
		
		//-----------------------------------------
		// Format...
		//-----------------------------------------
									     
		$fields['pf_content'] = $this->func->method_format_content_for_edit($fields['pf_content'] );
		
		//-----------------------------------------
		// Tbl (no ae?)
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Field Settings" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Field Title</b><div class='graytext'>Max characters: 200</div>" ,
												                 $this->ipsclass->adskin->form_input("pf_title", $fields['pf_title'] )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Description</b><div class='graytext'>Max Characters: 250<br />Can be used to note hidden/required status</div>" ,
												                 $this->ipsclass->adskin->form_input("pf_desc", $fields['pf_desc'] )
									                    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Field Type</b>" ,
																 $this->ipsclass->adskin->form_dropdown("pf_type",
																					  array(
																							   0 => array( 'text' , 'Text Input' ),
																							   1 => array( 'drop' , 'Drop Down Box' ),
																							   2 => array( 'area' , 'Text Area' ),
																						   ),
																					  $fields['pf_type'] )
														)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Maximum Input</b><div class='graytext'>For text input and text areas (in characters)</div>" ,
												                 $this->ipsclass->adskin->form_input("pf_max_input", $fields['pf_max_input'] )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Display order</b><div class='graytext'>When editing and displaying (numeric 1 lowest)</div>" ,
												                 $this->ipsclass->adskin->form_input("pf_position", $fields['pf_position'] )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Expected Input Format</b><div class='graytext'>Use: <b>a</b> for alpha characters<br />Use: <b>n</b> for numerics.<br />Example, for credit card numbers: nnnn-nnnn-nnnn-nnnn<br />Example, Date of Birth: nn-nn-nnnn<br />Leave blank to accept any input</div>" ,
												                 $this->ipsclass->adskin->form_input("pf_input_format", $fields['pf_input_format'] )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Option Content (for drop downs)</b><div class='graytext'>In sets, one set per line<br>Example for 'Gender' field:<br>m=Male<br>f=Female<br>u=Not Telling<br>Will produce:<br><select name='pants'><option value='m'>Male</option><option value='f'>Female</option><option value='u'>Not Telling</option></select><br>m,f or u stored in database. When showing field in profile, will use value from pair (f=Female, shows 'Female')</div>" ,
												                 $this->ipsclass->adskin->form_textarea("pf_content", $fields['pf_content'] )
									                    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Include on registration page?</b><div class='graytext'>If 'yes', the field will be shown upon registration.</div>" ,
												                 $this->ipsclass->adskin->form_yes_no("pf_show_on_reg", $fields["pf_show_on_reg"] )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Field MUST be completed and not left empty?</b><div class='graytext'>If 'yes', an error will be shown if this field is not completed.</div>" ,
												                 $this->ipsclass->adskin->form_yes_no("pf_not_null", $fields['pf_not_null'] )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Field can be edited by the member?</b><div class='graytext'>If 'no', the member cannot edit the field but Super Moderators and Admins will be able to.</div>" ,
												                 $this->ipsclass->adskin->form_yes_no("pf_member_edit", $fields['pf_member_edit'] )
									                    )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Make this a private profile field?</b><div class='graytext'>If yes, field only visible to profile owner, super moderators and admins. If 'no', members can search within this field.</div>" ,
												                 $this->ipsclass->adskin->form_yes_no("pf_member_hide", $fields['pf_member_hide'] )
									                    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Make Admin and Super Moderator Editable/Viewable Only?</b><div class='graytext'>If yes, will override the above options so only admins and super moderators can see and edit this field.</div>" ,
												                 $this->ipsclass->adskin->form_yes_no("pf_admin_only", $fields['pf_admin_only'] )
									                    )      );
									                    
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Topic View Format?</b><div class='graytext'>Leave blank if you do not wish to add this field in the author details when viewing a topic.<br />{title} is the title of the custom field, {content} is the user added content. {key} is the form select value of the selected item in a dropdown box.<br />Example: {title}:{content}&lt;br /&gt;<br />Example: {title}:&lt;img src='imgs/{key}'&gt;</div>" ,
												                 $this->ipsclass->adskin->form_textarea("pf_topic_format", $fields['pf_topic_format'] )
									                    )      );					     							     
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($button);
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
			
			
	}

	//-----------------------------------------
	//
	// Show "Management Screen
	//
	//-----------------------------------------
	
	function main_screen()
	{
		$this->ipsclass->admin->page_title   = "Custom Profile Fields";
		
		$this->ipsclass->admin->page_detail  = "Custom Profile fields can be used to add optional or required fields to be completed when registering or editing a profile. This is useful if you wish to record data from your members that is not already present in the base board.";
		
		$this->ipsclass->adskin->td_header[] = array( "Field Title"    , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Type"           , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "REQUIRED"       , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "NOT PUBLIC"     , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "SHOW REG"       , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "ADMIN ONLY"     , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Edit"           , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Delete"         , "10%" );
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Custom Profile Field Management" );
		
		$real_types = array( 'drop' => 'Drop Down Box',
							 'area' => 'Text Area',
							 'text' => 'Text Input',
						   );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'pfields_data', 'order' => 'pf_position' ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
			
				$hide   = '&nbsp;';
				$req    = '&nbsp;';
				$regi   = '&nbsp;';
				$admin  = '&nbsp;';
				
				//-----------------------------------------
				// Hidden?
				//-----------------------------------------
				
				if ($r['pf_member_hide'] == 1)
				{
					$hide = '<center><span style="color:red">Y</span></center>';
				}
				
				//-----------------------------------------
				// Required?
				//-----------------------------------------
				
				if ($r['pf_not_null'] == 1)
				{
					$req = '<center><span style="color:red">Y</span></center>';
				}
				
				//-----------------------------------------
				// Show on reg?
				//-----------------------------------------
				
				if ($r['pf_show_on_reg'] == 1)
				{
					$regi = '<center><span style="color:red">Y</span></center>';
				}
				
				//-----------------------------------------
				// Admin only...
				//-----------------------------------------
				
				if ($r['pf_admin_only'] == 1)
				{
					$admin = '<center><span style="color:red">Y</span></center>';
				}
				
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$r['pf_title']}</b><div class='graytext'>{$r['pf_desc']}</div>" ,
																		 "<center>{$real_types[$r['pf_type']]}</center>",
																		 $req,
																		 $hide,
																		 $regi,
																		 $admin,
																		 "<center><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=edit&id=".$r['pf_id']."'>Edit</a></center>",
																		 "<center><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=delete&id=".$r['pf_id']."'>Delete</a></center>",
															)      );
											 
			}
		}
		else
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("None found", "center", "tablerow1");
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic("<div class='fauxbutton-wrapper'><span class='fauxbutton'><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;code=add'>Add New Field</a></span></div>", 'center', 'tablefooter' );

		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		
		$this->ipsclass->admin->output();
		
		
	}
}


?>