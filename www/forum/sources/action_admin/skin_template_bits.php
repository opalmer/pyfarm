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
|   > $Date: 2007-06-29 15:38:02 -0400 (Fri, 29 Jun 2007) $
|   > $Revision: 1078 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Skin -> Templates functions
|   > Module written by Matt Mecham
|   > Date started: 15th April 2002
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


class ad_skin_template_bits
{
	var $base_url;
	var $template = "";
	var $functions = "";

	var $search_bits;

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
	var $perm_child = "templ";

	/**
	* Search template bits
	*
	* var array
	*/

	function auto_run()
	{
		//-----------------------------------------
		// Get the libraries
		//-----------------------------------------

		$this->template =& $this->ipsclass->cache_func->template;

		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------

		$this->html = $this->ipsclass->acp_load_template('cp_skin_lookandfeel');

		//-----------------------------------------

		require_once( ROOT_PATH.'sources/lib/admin_template_functions.php' );

		$this->functions           =  new admin_template_functions();
		$this->functions->ipsclass =& $this->ipsclass;

		//-----------------------------------------

		$this->unaltered    = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_unaltered.gif' border='0' alt='-' title='Unaltered from parent skin set' />&nbsp;";
		$this->altered      = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_altered.gif' border='0' alt='+' title='Altered from parent skin set' />&nbsp;";
		$this->inherited    = "<img src='{$this->ipsclass->skin_acp_url}/images/skin_item_inherited.gif' border='0' alt='|' title='Inherited from parent skin set' />&nbsp;";

		//-----------------------------------------

		switch($this->ipsclass->input['code'])
		{
			case 'template-sections-list':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->template_sections_list();
				break;

			case 'template-bits-list':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->template_bits_list();
				break;

			case 'template-edit-bit-complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->template_edit_bit_complete();
				break;

			case 'template_remove_bit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->template_revert_bit();
				break;

			case 'template-edit-bit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->template_edit_bit();
				break;

			case 'floateditor':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->functions->build_editor_area_floated();
				break;

			case 'addbit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->add_bit();
				break;

			case 'doadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->do_add_bit();
				break;

			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->template_sections_list();
				break;
		}
	}

	//-----------------------------------------
	// Show template bits for a group
	//-----------------------------------------

	function template_bits_list( $type='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$groups       = array();
		$group_bits   = array();
		$content      = "";
		$master_names = array();
		$group_name   = trim( $this->ipsclass->input['group_name'] );
		$id           = intval( $this->ipsclass->input['id'] );
		$p            = intval( $this->ipsclass->input['p'] );
		$type         = $type != '' ? $type : ( isset($this->ipsclass->input['type']) ? trim( $this->ipsclass->input['type'] ) : '' );
		$linked_bits  = array();

		//-----------------------------------------
		// Get $skin_names stuff
		//-----------------------------------------

		require_once( ROOT_PATH.'sources/lib/skin_info.php' );

		//-----------------------------------------
		// Get skin set
		//-----------------------------------------

		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_skin_set_id=".$id ) );

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $id )
		{
			$this->ipsclass->admin->error("You must specify an existing template set ID, go back and try again");
		}

		//-----------------------------------------
		// Parent?
		//-----------------------------------------

		if ( ! $p )
		{
			if ( $this_set['set_skin_set_parent'] )
			{
				$p = $this_set['set_skin_set_parent'];
			}
		}

		if ( $p > 0 )
		{
			$in = ','.$p;
		}

		//-----------------------------------------
		// Get template bits master names..
		//-----------------------------------------

		$this->ipsclass->DB->simple_construct( array( 'select' => 'func_name', 'from' => 'skin_templates', 'where' => "group_name='{$group_name}' AND set_id=1" ) );
		$this->ipsclass->DB->simple_exec();

		//-----------------------------------------
		// Compile...
		//-----------------------------------------
		
		while ( $m = $this->ipsclass->DB->fetch_row() )
		{
			$master_names[ $m['func_name'] ] = $m['func_name'];
		}

		$group['easy_name']  = $skin_names[ $group_name ][0];
		$group['group_name'] = $group_name;

		//-----------------------------------------
		// Get linked bits
		//-----------------------------------------

		$this->ipsclass->DB->build_query( array( 'select' => '*',
												 'from'   => 'skin_template_links',
												 'where'  => "link_set_id IN( 1, " . $id . ") AND link_used_in='". $group_name ."'" ) );

		$this->ipsclass->DB->exec_query();

		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$linked_bits[ $row['link_template_name'] ] = $row['link_template_name'];
		}

		//-----------------------------------------
		// Get group bits
		//-----------------------------------------

		$group_bits = $this->ipsclass->cache_func->_get_templates($id, $p, 'groups', $group_name );

		$add_button = "<div class='realbutton' style='padding:4px;width:100px'>
						<a style='text-decoration:none' href='#' onclick=\"parent.template_add_bit('{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=addbit&id={$this->ipsclass->input['id']}&p={$this->ipsclass->input['p']}&expand={$group['group_name']}', event)\">Add Template Bit</a>
					   </div>";

		//-----------------------------------------
		// Add to HTML
		//-----------------------------------------

		$temp     = "";
		$sec_arry = array();

		//-----------------------------------------
		// Stuff array to sort on name
		//-----------------------------------------

		foreach( $group_bits as $i )
		{
			//-----------------------------------------
			// Linked bit?
			//-----------------------------------------

			if ( in_array( $i['func_name'], $linked_bits ) )
			{
				continue;
			}

			$sec_arry[ $i['suid'] ] = $i;
			$sec_arry[ $i['suid'] ]['easy_name'] = $i['func_name'];
		}

		//-----------------------------------------
		// Sort by easy_name
		//-----------------------------------------

		usort($sec_arry, array( 'ad_skin_template_bits', 'perly_alpha_sort' ) );

		//-----------------------------------------
		// Loop and print main display
		//-----------------------------------------

		foreach( $sec_arry as $sec )
		{
			$custom_bit = "";

			$sec['_p']  = intval( $p );
			$sec['_id'] = intval( $this->ipsclass->input['id'] );

			//-----------------------------------------
			// Altered?
			//-----------------------------------------

			if ( $sec['set_id'] == $id )
			{
				$altered_image = $this->html->template_bits_bit_row_image( $sec['suid'], 'skin_item_altered.gif' );
			}
			else if ( $sec['set_id'] == 1 )
			{
				$altered_image = $this->html->template_bits_bit_row_image( $sec['suid'], 'skin_item_unaltered.gif' );
			}
			else
			{
				$altered_image = $this->html->template_bits_bit_row_image( $sec['suid'], 'skin_item_inherited.gif' );
			}

			$remove_button = "<a id='link-remove-{$sec['suid']}' title='Revert Customization' href=\"javascript:confirm_action('{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=template_remove_bit&bitname={$sec['func_name']}&id={$this->ipsclass->input['id']}&p={$this->ipsclass->input['p']}&group_name={$sec['group_name']}&type=frame')\"><img id='img-remove-{$sec['suid']}' src='{$this->ipsclass->skin_acp_url}/images/blank.gif' alt='' border='0' width='1' height='1' /></a>&nbsp;";

			if ( $sec['set_id'] == $id )
			{
				if ( $master_names[ $sec['func_name'] ] )
				{
					$remove_button = "<a id='link-remove-{$sec['suid']}' title='Revert Customization' href=\"javascript:confirm_action('{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=template_remove_bit&bitname={$sec['func_name']}&id={$this->ipsclass->input['id']}&p={$this->ipsclass->input['p']}&group_name={$sec['group_name']}&type=frame')\"><img id='img-remove-{$sec['suid']}'src='{$this->ipsclass->skin_acp_url}/images/te_revert.gif' alt='X' border='0' /></a>&nbsp;";
				}
				else
				{
					$custom_bit    = ' (custom bit)';
					$remove_button = "<a id='link-remove-{$sec['suid']}' title='Remove Custom Bit' href=\"javascript:confirm_action('{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=template_remove_bit&bitname={$sec['func_name']}&id={$this->ipsclass->input['id']}&p={$this->ipsclass->input['p']}&group_name={$sec['group_name']}&type=frame&custombit=1')\"><img id='img-remove-{$sec['suid']}' src='{$this->ipsclass->skin_acp_url}/images/te_remove.gif' alt='X' border='0' /></a>&nbsp;";
				}
			}

			$content .= $this->html->template_bits_bit_row( $sec, $custom_bit, $remove_button, $altered_image );
		}

		//-----------------------------------------
		// Just showing the XML?
		//-----------------------------------------

		if ( $type == 'xml' )
		{
			@header("Content-Type: text/plain");
			print $content;
			exit();
		}
		else
		{
			$this->ipsclass->html =  $this->html->template_bits_bit_overview( $group, $content, $add_button );

			$this->ipsclass->admin->print_popup();
		}
	}

	//-----------------------------------------
	// Show template groups/categories
	//-----------------------------------------

	function template_sections_list()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$groups     = array();
		$group_bits = array();
		$content    = "";
		$javascript = '';
		$js_groups  = '';
		$js_bits    = '';
		$js_matches = '';

		$this->ipsclass->input['id'] = intval( $this->ipsclass->input['id'] );
		$this->ipsclass->input['p']  = intval( $this->ipsclass->input['p'] );

		//-----------------------------------------
		// Get $skin_names stuff
		//-----------------------------------------

		require_once( ROOT_PATH.'sources/lib/skin_info.php' );

		//-----------------------------------------
		// Get skin set
		//-----------------------------------------

		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_skin_set_id=".$this->ipsclass->input['id'] ) );

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( $this->ipsclass->input['id'] == "")
		{
			$this->ipsclass->admin->error("You must specify an existing template set ID, go back and try again");
		}

		//-----------------------------------------
		// Parent?
		//-----------------------------------------

		if ( ! $this->ipsclass->input['p'] )
		{
			if ( $this_set['set_skin_set_parent'] )
			{
				$this->ipsclass->input['p'] = $this_set['set_skin_set_parent'];
			}
		}

		if ( $this->ipsclass->input['p'] > 0 )
		{
			$in = ','.$this->ipsclass->input['p'];
		}

		$this->ipsclass->admin->page_detail = "Please choose which section you wish to edit below.";
		$this->ipsclass->admin->page_title  = "Edit Template sets";

		//-----------------------------------------
		// Generate inline JS
		//-----------------------------------------

		if ( is_array( $this->search_bits ) and count( $this->search_bits ) )
		{
			foreach( $this->search_bits as $group => $idx )
			{
				$js_matches .= "\t'$group' : ". count( $this->search_bits[ $group ] ).",\n";
				$js_groups  .= "\t'$group' : 1,\n";

				foreach( $this->search_bits[ $group ] as $data )
				{
					$js_bits .= "\t'{$group}_{$data['func_name']}' : 1,\n";
				}
			}

			//-----------------------------------------
			// Trim off trailing commas
			//-----------------------------------------

			$js_matches = preg_replace( "#,\n?$#", "", $js_matches );
			$js_groups  = preg_replace( "#,\n?$#", "", $js_groups  );
			$js_bits    = preg_replace( "#,\n?$#", "", $js_bits    );

			$javascript = "var template_search = 1;\n".
					      "var search_template_sections = {\n". $js_groups ."\n};\n".
					      "var search_template_bits = {\n"    . $js_bits   ."\n};\n".
					      "var search_template_matches = {\n" . $js_matches."\n};\n";

		}
		else
		{
			$javascript = "var template_search = 0;\nvar search_template_sections = {};\nvar search_template_bits = {};\nvar search_template_matches = {};\n";
		}

		//-----------------------------------------
		// Get the groups...
		//-----------------------------------------

		$group_titles = $this->ipsclass->cache_func->_get_templates($this->ipsclass->input['id'], $this->ipsclass->input['p'], 'groups');

		foreach( $group_titles as $g )
		{
			//-----------------------------------------
			// Fix up names
			//-----------------------------------------

			$g['easy_name'] = "<b>".$g['group_name']."</b>";
			$g['easy_desc'] = "";

			//-----------------------------------------
			// If available, change group name to easy name
			//-----------------------------------------

			if ( isset($skin_names[ $g['group_name'] ]) )
			{
				$g['easy_name'] = "<b>".$skin_names[ $g['group_name'] ][0]."</b>";
				$g['easy_desc'] = str_replace( '"', '&quot;', $skin_names[ $g['group_name'] ][1] );
			}
			else
			{
				$g['easy_name'] = "<b>".$g['group_name']."</b> (Non-Default Group)";
				$g['easy_desc'] = "This group is not part of the standard Invision Power Board installation and no description is available";
			}

			if ( isset($skin_names[ $g['group_name'] ][2]) )
			{
				$g['easy_preview'] = "<a title='New window: Show relevant IPB page' href='{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?{$skin_names[ $g['group_name'] ][2]}' target='_blank'><img src='{$this->ipsclass->skin_acp_url}/images/te_previewon.gif' alt='Preview' border='0' /></a>";
			}
			else
			{
				$g['easy_preview'] = "<img src='{$this->ipsclass->skin_acp_url}/images/te_previewoff.gif' alt='No preview available' border='0' title='No preview available' />";
			}

			$groups[] = $g;
		}

		//-----------------------------------------
		// Sort by easy_name
		//-----------------------------------------

		usort($groups, array( 'ad_skin_template_bits', 'perly_alpha_sort' ) );

		//-----------------------------------------
		// Loop and print
		//-----------------------------------------

		foreach( $groups as $group )
		{
			$eid    = $group['suid'];
			$exp_content = "";

			$this->ipsclass->cache_func->template_count[$this->ipsclass->input['p']][ $group['group_name'] ]['count'] =
				isset($this->ipsclass->cache_func->template_count[$this->ipsclass->input['p']][ $group['group_name'] ]['count']) ?
				$this->ipsclass->cache_func->template_count[$this->ipsclass->input['p']][ $group['group_name'] ]['count']		 : 0;

			$this->ipsclass->cache_func->template_count[$this->ipsclass->input['id']][ $group['group_name'] ]['count'] =
				isset($this->ipsclass->cache_func->template_count[$this->ipsclass->input['id']][ $group['group_name'] ]['count']) ?
				$this->ipsclass->cache_func->template_count[$this->ipsclass->input['id']][ $group['group_name'] ]['count']		  : 0;

			$altered      = sprintf( '%02d', intval($this->ipsclass->cache_func->template_count[$this->ipsclass->input['id']][ $group['group_name'] ]['count']) );
			$original     = sprintf( '%02d', intval($this->ipsclass->cache_func->template_count[1][ $group['group_name'] ]['count']) );
			$inherited    = sprintf( '%02d', intval($this->ipsclass->cache_func->template_count[$this->ipsclass->input['p']][ $group['group_name'] ]['count']) );
			$count_string = "";

			if ( $this->ipsclass->input['p'] > 0 )
			{
				$count_string = "$original {$this->unaltered} $inherited {$this->inherited} $altered {$this->altered}";
			}
			else
			{
				$count_string = "$original {$this->unaltered} $altered {$this->altered}";
			}

			//-----------------------------------------
			// Folder blob
			//-----------------------------------------

			if ( $altered > 0 )
			{
				$folder_blob = $this->altered;
			}
			else if ( $this->ipsclass->input['p'] > 0 and $inherited > 0 )
			{
				$folder_blob = $this->inherited;
			}
			else
			{
				$folder_blob = $this->unaltered;
			}

			$group['_p']  = intval( $this->ipsclass->input['p'] );
			$group['_id'] = intval( $this->ipsclass->input['id'] );

			//-----------------------------------------
			// Print normal rows
			//-----------------------------------------

			$content .= $this->html->template_bits_overview_row_normal( $group, $folder_blob, $count_string );
		}

		$this->ipsclass->html .= $this->html->template_bits_overview( $content, $javascript );

		$this->ipsclass->html .= $this->ipsclass->adskin->skin_jump_menu_wrap();

		$this->ipsclass->admin->nav[] = array( 'section='.$this->ipsclass->section_code.'&act=sets' ,'Skin Manager Home' );
		$this->ipsclass->admin->nav[] = array( '' ,'Managing Template Set "'.$this_set['set_name'].'"' );

		$this->ipsclass->admin->output();
	}

	//-----------------------------------------
	// Sneaky sorting.
	// We use the format "1: name". without this hack
	// 1: name, 2: other name, 11: other name
	// will sort as 1: name, 11: other name, 2: other name
	// There is natsort and such in PHP, but it causes some
	// problems on older PHP installs, this is hackish but works
	// by simply adding '0' to a number less than 2 characters long.
	// of course, this won't work with three numerics in the hundreds
	// but we don't have to worry about more that 99 bits in a template
	// at this stage.
	//-----------------------------------------

	function perly_word_sort($a, $b)
	{
		$nat_a = intval( $a['easy_name'] );
		$nat_b = intval( $b['easy_name'] );

		if (strlen($nat_a) < 2)
		{
			$nat_a = '0'.$nat_a;
		}
		if (strlen($nat_b) < 2)
		{
			$nat_b = '0'.$nat_b;
		}

		return strcmp($nat_a, $nat_b);
	}

	//-----------------------------------------
	// Sort by group name
	//-----------------------------------------

	function perly_alpha_sort($a, $b)
	{
		return strcmp( strtolower($a['easy_name']), strtolower($b['easy_name']) );
	}


	//-----------------------------------------
	// ADD TEMPLATE BIT
	//-----------------------------------------

	function add_bit()
	{
		$this->ipsclass->admin->page_detail = "You may add a template bit using this section.";
		$this->ipsclass->admin->page_title  = "Template Editing";

		$groupname = $this->ipsclass->input['expand'];

		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_skin_set_id=".intval($this->ipsclass->input['id']) ) );

		//-----------------------------------------
		// Sort out group titles
		//-----------------------------------------

		$group_titles = $this->ipsclass->cache_func->_get_templates( $this->ipsclass->input['id'], $this->ipsclass->input['p'], 'groups' );

		$formatted_groups = array();

		foreach ( $group_titles as $d )
		{
			$formatted_groups[] = array( $d['group_name'], $d['group_name'] );
		}

		//-----------------------------------------
		// Good form old boy
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'doadd'    ),
																			 2 => array( 'act'   , 'templ'     ),
																			 3 => array( 'id'    , $this->ipsclass->input['id']   ),
																			 4 => array( 'p'     , $this->ipsclass->input['p']    ),
																			 5 => array( 'expand', $this->ipsclass->input['expand'] ),
																			 6 => array( 'section', $this->ipsclass->section_code ),
																	)  , "theform"    );

		//-----------------------------------------
		// Editor prefs strip
		//-----------------------------------------



		$options .= "<div class='tableheaderalt'>New Template Bit Specifics</div>
					 <div class='tablerow1'>
					 <table width='100%' cellpadding='5' cellspacing='0' border='0'>
					 <tr>
					   <td width='40%' class='tablerow1'>New Template Bit Name<br /><span style='color:gray'>Alphanumerics and underscores only, no spaces.</span></td>
					   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('func_name', $this->ipsclass->txt_stripslashes($_POST['func_name']))."</td>
					 </tr>
					 <tr>
					   <td width='40%' class='tablerow1'>New Template Bit Incoming Data Variables<br /><span style='color:gray'>Define the variables passed to this template bit.</span></td>
					   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_input('func_data', str_replace( "'", '&#039;', $this->ipsclass->txt_stripslashes($_POST['func_data']) ) )."</td>
					 </tr>
					  <tr>
					   <td width='40%' class='tablerow1'>New Template Bit Group...</td>
					   <td width='60%' class='tablerow1'>".$this->ipsclass->adskin->form_dropdown('group_name', $formatted_groups, $this->ipsclass->input['group_name'] ? $this->ipsclass->input['group_name'] : $this->ipsclass->input['expand'] )."</td>
					 </tr>
					 <tr>
					   <td width='40%' class='tablerow1'>Or Create New Group...<br /><span style='color:gray'>Leave empty to use above group. Alphanumerics and underscores only, no spaces</span></td>
					   <td width='60%' class='tablerow1'>skin_".$this->ipsclass->adskin->form_input('new_group_name', $this->ipsclass->txt_stripslashes($_POST['new_group_name']))."</td>
					 </tr>
					 </table>
					</div>
					<div class='tablesubheader' align='center' style='padding:4px'><input type='submit' name='submit' value='Continue...' class='realdarkbutton'></div>
					</form>";

		$this->ipsclass->html .= $options;

		$this->ipsclass->admin->print_popup();
	}

	/*-------------------------------------------------------------------------*/
	// DO ADD NEW BIT
	/*-------------------------------------------------------------------------*/

	function do_add_bit()
	{
		//-----------------------------------------
		// Check incoming
		//-----------------------------------------

		$this_set = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_sets', 'where' => "set_skin_set_id=".intval($this->ipsclass->input['id']) ) );

		if ( $_POST['new_group_name'] )
		{
			if ( preg_match( "#[^\w_]#s", $_POST['new_group_name'] ) )
			{
				$this->ipsclass->main_msg = 'The new template bit group name must only contain alphanumerics and underscores.';
				$this->add_bit();
			}
		}

		if ( ! $_POST['func_name'] )
		{
			$this->ipsclass->main_msg = 'The new template bit name cannot be empty.';
			$this->add_bit();
		}
		else
		{
			if ( preg_match( "#[^\w_]#s", $_POST['func_name'] ) )
			{
				$this->ipsclass->main_msg = 'The new template bit name must only contain alphanumerics and underscores.';
				$this->add_bit();
			}
		}


		$new_group_name = strtolower(str_replace( 'skin_', '', trim($this->ipsclass->input['new_group_name']) ));
		$func_name      = strtolower(trim($this->ipsclass->input['func_name']));
		$group_name     = $new_group_name ? 'skin_'.$new_group_name : $this->ipsclass->input['group_name'];
		$func_data      = preg_replace( "#,$#", "", str_replace( '&#039', "'", trim($this->ipsclass->txt_stripslashes($_POST['func_data'])) ) );
		$text           = '';

		//-----------------------------------------
		// Is parent master template set?
		//-----------------------------------------

		if( $this_set['set_skin_set_parent'] == "-1" )
		{
			$parent_set = "1";
		}
		else
		{
			$parent_set = $this_set['set_skin_set_parent'];
		}

		//-----------------------------------------
		// Make sure bit doesn't exist
		//-----------------------------------------

		if ( $row = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'suid', 'from' => 'skin_templates', 'where' => "(set_id=".intval($this->ipsclass->input['id'])." OR set_id='{$parent_set}') AND group_name='$group_name' AND func_name='$func_name'" ) ) )
		{
			$this->ipsclass->main_msg = "The new template bit '$func_name' already exists in group '$group_name'.";
			$this->add_bit();
		}

		//-----------------------------------------
		// Make sure it's not called "end"
		//-----------------------------------------

		if ( strtolower($func_name) == 'end' )
		{
			$this->ipsclass->main_msg = "You cannot name a template bit 'end'";
			$this->add_bit();
		}

		//-----------------------------------------
		// INSERT NEW BIT
		//-----------------------------------------

		$this->ipsclass->DB->do_insert( 'skin_templates', array (
												  'set_id'		    => intval($this->ipsclass->input['id']),
												  'group_name'      => $group_name,
												  'section_content' => $text,
												  'func_name' 		=> $func_name,
												  'func_data'		=> $func_data,
												  'updated'         => time(),
										)      );

		$new_id = $this->ipsclass->DB->get_insert_id();

		//-----------------------------------------
		// Rebuild the PHP file
		//-----------------------------------------

		$this->ipsclass->cache_func->_recache_templates( $this->ipsclass->input['id'], $this->ipsclass->input['p'] );

		//-----------------------------------------
		// Back we go...
		//-----------------------------------------

		$this->ipsclass->input[ 'cb_'.$new_id ] = 1;
		$this->ipsclass->input[ 'suid' ]        = $new_id;
		$this->ipsclass->input['type']          = 'single';
		$this->template_edit_bit( $reload=1 );
	}

	/*-------------------------------------------------------------------------*/
	// EDIT TEMPLATE BIT
	/*-------------------------------------------------------------------------*/

	function template_edit_bit( $reload=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$suid       = isset($this->ipsclass->input['suid']) ? intval( $this->ipsclass->input['suid'] ) 	: 0;
		$type       = isset($this->ipsclass->input['type']) ? trim( $this->ipsclass->input['type'] )	: '';
		$id         = isset($this->ipsclass->input['id'])	? intval( $this->ipsclass->input['id'] )	: 0;
		$p          = isset($this->ipsclass->input['p'])	? intval( $this->ipsclass->input['p'] )		: 0;
		$bitname    = isset($this->ipsclass->input['bitname'])			? $this->ipsclass->input['bitname'] : '';
		$group_name = isset($this->ipsclass->input['group_name']) ? trim( $this->ipsclass->input['group_name'] ) : '';

		$template_bit_ids = array();
		$ids              = array();

		//-----------------------------------------
		// PAGE HEADER
		//-----------------------------------------

		$this->ipsclass->admin->page_detail = "You may edit the HTML of this template.";
		$this->ipsclass->admin->page_title  = "Template Editing";

		//-----------------------------------------
		// Get $skin_names stuff
		//-----------------------------------------

		require ROOT_PATH.'sources/lib/skin_info.php';

		$this->functions->type = $type;

		//-----------------------------------------
		// Group bits
		//-----------------------------------------

		$group_bits = $this->ipsclass->cache_func->_get_templates($id, $p, 'groups', $group_name );

		//-----------------------------------------
		// Check for valid input...
		//-----------------------------------------

		if ( $type == 'single' )
		{
			if ( $suid == "" )
			{
				//-----------------------------------------
				// Grab relevant SUID based on IDs
				//-----------------------------------------

				if ( $bitname == "" )
				{
					$this->ipsclass->admin->error("You must specify an existing template set ID, go back and try again");
				}

				foreach( $group_bits as $i )
				{
					if ( $bitname == $i['func_name'] )
					{
						$suid = $i['suid'];
					}
				}
			}

			$ids[] = $suid;
		}
		else
		{
			//-----------------------------------------
			// Bit names?
			//-----------------------------------------

			if ( $bitname )
			{
				foreach( explode( '|', $bitname ) as $name )
				{
					foreach( $group_bits as $i )
					{
						if ( $name == $i['func_name'] )
						{
							$ids[] = $i['suid'];
						}
					}
				}
			}
			else
			{
				foreach ( $this->ipsclass->input as $key => $value )
				{
					if ( preg_match( "/^cb_(\d+)$/", $key, $match ) )
					{
						if ($this->ipsclass->input[$match[0]])
						{
							$ids[] = $match[1];
						}
					}
				}
			}
 		}

 		$ids = $this->ipsclass->clean_int_array( $ids );

 		if ( count($ids) < 1 )
 		{
 			$this->ipsclass->admin->error("No ids selected, please go back and select some before submitting the form");
 		}

		//-----------------------------------------
		// Build form
		//-----------------------------------------

		$this->ipsclass->html .= $this->ipsclass->adskin->js_template_tools();

		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'      , 'template-edit-bit-complete'    ),
																			 2 => array( 'act'       , 'templ'     ),
																			 3 => array( 'suid'      , $suid ),
																			 4 => array( 'type'      , $type  ),
																			 5 => array( 'id'        , $id   ),
																			 6 => array( 'p'         , $p    ),
																			 7 => array( 'section'   , $this->ipsclass->section_code ),
																			 8 => array( 'bitname'   , $bitname ),
																			 9 => array( 'group_name', $group_name ),
																	)  , "theform", "", 'template-bits-form'    );


		//-----------------------------------------
		// Get template bits
		//-----------------------------------------

		$sec_arry = array();
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "suid IN (".implode(",",$ids).")" ) );
		$this->ipsclass->DB->simple_exec();

		while ( $i = $this->ipsclass->DB->fetch_row() )
		{
			$sec_arry[ $i['suid'] ] = $i;
			$sec_arry[ $i['suid'] ]['easy_name'] = $i['func_name'];

			//-----------------------------------------
			// If easy name is available, use it
			//-----------------------------------------

			if ( isset($bit_names[ $i['group_name'] ][ $i['func_name'] ]) AND $bit_names[ $i['group_name'] ][ $i['func_name'] ] != "" )
			{
				$sec_arry[ $i['suid'] ]['easy_name'] = $bit_names[ $i['group_name'] ][ $i['func_name'] ];
			}
		}

		//-----------------------------------------
		// Sort by easy_name
		//-----------------------------------------

		usort($sec_arry, array( 'ad_skin_template_bits', 'perly_alpha_sort' ) );

		//-----------------------------------------
		// Editor prefs strip
		//-----------------------------------------

		$this->ipsclass->html .= $this->functions->html_build_editor_top();

		//-----------------------------------------
		// Loop and print
		//-----------------------------------------

		foreach( $sec_arry as $template )
		{
			//-----------------------------------------
			// Swop < and > into ascii entities
			// to prevent textarea breaking html
			//-----------------------------------------

			$setid     = $template['set_id'];
			$groupname = $template['group_name'];

			if ( !isset($this->ipsclass->input['error_raw_'.$template['suid']]) OR !$this->ipsclass->input['error_raw_'.$template['suid']] )
			{
				$templ = $template['section_content'];
			}
			else
			{
				$templ = $this->ipsclass->input['error_raw_'.$template['suid']];
			}

			$templ = str_replace( "&" , "&#38;"  , $templ );
			$templ = str_replace( "<" , "&#60;"  , $templ );
			$templ = str_replace( ">" , "&#62;"  , $templ );
			$templ = str_replace( '\n', '&#092;n', $templ );

			//-----------------------------------------
			// Altered?
			//-----------------------------------------

			if ( $template['set_id'] == $id )
			{
				$altered_image = $this->altered;
			}
			else if ( $template['set_id'] == 1 )
			{
				$altered_image = $this->unaltered;
			}
			else
			{
				$altered_image = $this->inherited;
			}

			$this->ipsclass->html .= $this->functions->build_editor_area( $templ, $template, $altered_image );

			$template_bit_ids[] = "t{$template['suid']}";
		}

		$this->ipsclass->html .= $this->functions->html_build_editor_bottom() . "</form>";

		if ( $type != 'single' AND $type != 'multiple' )
		{
			$formbuttons = "<div align='center' class='tablesubheader'>
							<input type='submit' name='submit' value='Save Template Bit(s)' class='realdarkbutton'>
							<input type='submit' name='savereload' value='Save and Reload Template Bit(s)' class='realdarkbutton'>
							</div>\n";

			$this->ipsclass->html = str_replace( '<!--IPB.EDITORBOTTOM-->', $formbuttons, $this->ipsclass->html );
		}

		//-----------------------------------------
		// Let the JS know which IDs to
		// look for (clever, no?)
		//-----------------------------------------

		$this->ipsclass->html = str_replace( "<!--IPB.TEMPLATE_BIT_IDS-->", implode(",",$template_bit_ids), $this->ipsclass->html );

		//-----------------------------------------
		// Find easy name for group
		//-----------------------------------------

		$old_groupname = $groupname;

		if ( $skin_names[ $groupname ][0] != "" )
		{
			$groupname = $skin_names[ $groupname ][0];
		}

		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code ,'Skin Manager Home' );
		$this->ipsclass->admin->nav[] = array( "{$this->ipsclass->form_code}&code=edit&id={$id}&groupname={$old_groupname}", $groupname );

		//-----------------------------------------
		// Force reload of template bits?
		//-----------------------------------------

		if ( $reload )
		{
			$this->ipsclass->html .= "<script type='text/javascript'>
									  try
									  {
									  	parent.iframe_template_bits.src          = parent.iframe_template_bits.src;
									  	parent.iframe_template_edit.style.height = '300px';
									  	parent.div_template_edit.style.height    = '300px';
									  }
									  catch(e){ alert(e) }
									  </script>\n";
		}

		//-----------------------------------------
		// Type of output
		//-----------------------------------------

		if ( $type != 'single' AND $type != 'multiple' )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->skin_jump_menu_wrap();
			$this->ipsclass->admin->output();
		}
		else
		{
			$this->ipsclass->admin->print_popup();
		}
	}

	/*-------------------------------------------------------------------------*/
	// COMPLETE EDIT
	/*-------------------------------------------------------------------------*/

	function template_edit_bit_complete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ids        = array();
		$cb_ids     = array();
		$error_bits = array();
		$type       = trim( $this->ipsclass->input['type'] );

		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		$this->ipsclass->input['p']  = intval($this->ipsclass->input['p']);

		//-----------------------------------------
		// CHECK FOR INPUT
		//-----------------------------------------

		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^txt(\d+)$/", $key, $match ) )
			{
				if ( $this->ipsclass->input[ $match[0] ] )
				{
					//-----------------------------------------
					// If multiple - only save clicked bit
					//-----------------------------------------

					$cb_ids[ $match[1] ] = 'cb_'.$match[1];

					if ( $type == 'multiple' )
					{
						if ( $this->ipsclass->input[ 'edited-'.$match[1] ] )
						{
							$ids[] = $match[1];
						}
					}

					//-----------------------------------------
					// Not multiple - continue
					//-----------------------------------------

					else
					{
						$ids[] = $match[1];
					}
				}
			}
		}

		$ids = $this->ipsclass->clean_int_array( $ids );

 		if ( count($ids) < 1 )
 		{
 			$this->ipsclass->main_msg = "Nothing to save";

			foreach( $cb_ids as $cb )
			{
				$this->ipsclass->input[ $cb ] = 1;
			}

			$this->template_edit_bit();
 		}

 		$rebuild = array();
 		
 		//-----------------------------------------
		// Get the group name, etc
		//-----------------------------------------

		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'skin_templates', 'where' => "suid IN (".implode(",",$ids).")" ) );
		$this->ipsclass->DB->simple_exec();

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$template[ $r['suid'] ] = $r;
			$real_name = $r['group_name'];
			$rebuild[] = $r['group_name'];
			
			if( $r['group_names_secondary'] )
			{
				$rebuild = array_merge( $rebuild, explode( ',', $this->ipsclass->clean_perm_string( $r['group_names_secondary'] ) ) );
			}
		}

		//-----------------------------------------
		// Process my bits :o
		//-----------------------------------------

		foreach( $ids as $id )
		{
			$text = $this->ipsclass->txt_stripslashes($_POST['txt'.$id]);

			//-----------------------------------------
			// Sw(o|a)p back < & >
			//-----------------------------------------

			$text = str_replace("&#60;", "<", $text);
			$text = str_replace("&#62;", ">", $text);
			$text = str_replace("&#38;", "&", $text);
			$text = str_replace( '&#092;n', '\n',$text );
			//$text = str_replace( '\\n'    , '\\\\\\n', $text );
			$text = str_replace( '&#46;&#46;/', '../' , $text );
			$text = str_replace( '&#092;' , '\\',$text );

			//-----------------------------------------
			// Convert \r to nowt
			//-----------------------------------------

			$text = str_replace("\r", "", $text);

			$func = preg_replace( "#,$#", "", str_replace( '&#039;', "'", trim($this->ipsclass->txt_stripslashes($_POST['funcdata_'.$id])) ) );

			//-----------------------------------------
			// Test to ensure they are legal
			// - catch warnings, etc
			//-----------------------------------------

			ob_start();
			eval( $this->template->convert_html_to_php( $template[ $id ]['func_name'], $func, $text ) );
			$return = ob_get_contents();
			ob_end_clean();

			if ( $return )
			{
				$error_bits[] = $id;
				continue;
			}

			//-----------------------------------------
			// Is this in our template id group?
			//-----------------------------------------

			if ( $template[ $id ]['set_id'] == $this->ipsclass->input['id'] )
			{
				//-----------------------------------------
				// Okay, update...
				//-----------------------------------------

				$this->ipsclass->DB->do_update( 'skin_templates', array( 'section_content' => $text, 'updated' => time(), 'func_data' => $func ), 'suid='.$id );
			}
			else
			{
				//-----------------------------------------
				// No? OK - best add it as a 'new' bit
				//-----------------------------------------

				$this->ipsclass->DB->do_insert( 'skin_templates', array (
																		  'set_id'		    		=> $this->ipsclass->input['id'],
																		  'group_name'      		=> $template[ $id ]['group_name'],
																		  'section_content' 		=> $text,
																		  'func_name' 				=> $template[ $id ]['func_name'],
																		  'func_data'				=> $func,
																		  'updated'         		=> time(),
																		  'group_names_secondary'	=> $template[ $id ]['group_names_secondary'],
																)      );

				if ( $this->ipsclass->input['type'] == 'single' )
				{
					$this->ipsclass->input['suid'] = $this->ipsclass->DB->get_insert_id();
				}
				else
				{
					$cb_ids[ $id ] = 'cb_'.$this->ipsclass->DB->get_insert_id();
					unset($this->ipsclass->input['cb_'.$id ]);
				}
			}
		}

		//-----------------------------------------
		// Rebuild the PHP file(?:s)
		//-----------------------------------------

		foreach( $rebuild as $file_name )
		{
			$this->ipsclass->cache_func->_recache_templates( $this->ipsclass->input['id'], $this->ipsclass->input['p'], $file_name );
		}

		//-----------------------------------------
		// Back we go...
		//-----------------------------------------

		if ( count( $error_bits ) )
		{
			foreach( $error_bits as $id )
			{
				$this->ipsclass->input['cb_'.$id ] = 1;
				$this->ipsclass->input['error_raw_'.$id] = $this->ipsclass->txt_stripslashes($_POST['txt'.$id]);
				$this->ipsclass->main_msg = "These template bits could not be saved because they cause an error when parsed. Please check the data including any HTML logic used and any input data variables.";

				$this->template_edit_bit();
			}
		}
		else
		{
			if ( ( $type != 'single' AND $type != 'multiple' ) AND ( ! $this->ipsclass->input['savereload'] ) )
			{
				$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code ,'Skin Manager Home' );
				$this->ipsclass->admin->redirect( "{$this->ipsclass->form_code}&code=template-edit-bit&id={$this->ipsclass->input['id']}&group_name={$real_name}", "Template bit(s) updated, returning to template selection screen" );
			}
			else
			{
				//-----------------------------------------
				// Reload edit window
				//-----------------------------------------

				if( count($this->ipsclass->cache_func->messages) )
				{
					array_pop($this->ipsclass->cache_func->messages);

					$this->ipsclass->cache_func->messages[] = "Template bit(s) saved to database";

					$this->ipsclass->main_msg = implode( "<br />", $this->ipsclass->cache_func->messages );
				}
				else
				{
					$this->ipsclass->main_msg = "Template bit(s) updated";
				}

				foreach( $cb_ids as $cb )
				{
					$this->ipsclass->input[ $cb ] = 1;
				}

				$this->template_edit_bit();
			}
		}
	}

	//-----------------------------------------
	// REMOVE CUSTOMIZATION
	//-----------------------------------------

	function template_revert_bit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$suid       = $this->ipsclass->input['suid'];
		$type       = $this->ipsclass->input['type'];
		$bitname    = $this->ipsclass->input['bitname'];
		$group_name = trim( $this->ipsclass->input['group_name'] );
		$id         = intval( $this->ipsclass->input['id'] );
		$p          = intval( $this->ipsclass->input['p'] );

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $suid )
		{
			if ( $bitname == "" )
			{
				$this->ipsclass->admin->error("You must enter a corrent template bit ID");
			}

			$group_bits = $this->ipsclass->cache_func->_get_templates($id, $p, 'groups', $group_name );

			foreach( $group_bits as $i )
			{
				if ( $bitname == $i['func_name'] )
				{
					$suid = $i['suid'];
				}
			}
		}

		if( !$suid AND $type=='frame' )
		{
			$this->template_bits_list();
		}

		$row = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'suid='.$suid ) );

		if ( $row['set_id'] == 1 )
		{
			$this->ipsclass->admin->error("You cannot remove a template bit from the master set.");
		}

		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'skin_templates', 'where' => 'suid='.$suid ) );

		//-----------------------------------------
		// Rebuild the PHP file
		//-----------------------------------------

		$rebuild = array( $row['group_name'] );
		
		if( $row['group_names_secondary'] )
		{
			$rebuild = array_merge( $rebuild, explode( ',', $this->ipsclass->clean_perm_string( $row['group_names_secondary'] ) ) );
		}
		
		foreach( $rebuild as $file_name )
		{
			$this->ipsclass->cache_func->_recache_templates( $this->ipsclass->input['id'], $this->ipsclass->input['p'], $file_name );
		}

		//-----------------------------------------
		// Reload template list or redirect?
		//-----------------------------------------

		if ( $type == 'xml' )
		{
			@header( "Content-Type: text/html; charset={$this->ipsclass->vars['gb_char_set']}" );
			print "done";
			exit();
		}
		else if ( $type == 'frame' )
		{
			$this->template_bits_list();
		}
		else
		{
			$this->ipsclass->admin->redirect( "{$this->ipsclass->form_code}&code=edit&id={$row['set_id']}&p={$this->ipsclass->input['p']}&group_name={$row['group_name']}&#{$row['group_name']}", "Template bit(s) reverted, returning to template selection screen" );
		}
	}

	//====================================================================================================================
	// OLD - DEPRECIATED
	//====================================================================================================================





}


?>