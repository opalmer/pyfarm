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
|   > $Date: 2007-07-13 13:51:15 -0400 (Fri, 13 Jul 2007) $
|   > $Revision: 1087 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Email Error Logs Stuff
|   > Module written by Matt Mecham
|   > Date started: 7th April 2004
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/

/**
* ACP MODULE: ACP Permissions
*
* Manage ACP permissions
*
* @package		InvisionPowerBoard
* @subpackage	ActionAdmin
* @author  		Matt Mecham
* @version		2.1
* @since		2.1.0.2005-07-08
*/

/**
*
*/
if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
* ACP MODULE: ACP Permissions
*
* Manage ACP permissions
*
* @package		InvisionPowerBoard
* @subpackage	ActionAdmin
* @author  		Matt Mecham
* @version		2.1
* @since		2.1.0.2005-07-08
*/
class ad_acppermissions
{
	/**
	* Member info array
	*
	* @var array
	*/
	var $member_info = array();
	
	/*-------------------------------------------------------------------------*/
	// AUTO RUN
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		//-----------------------------------------
		// Make sure we're a root admin, or else!
		//-----------------------------------------
		
		if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
		{
			$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_admin');
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->ipsclass->input['code'] )
		{
			case 'acpperms-list':
				$this->acpperms_list();
				break;
				
			case 'acpperms-dev-source-to-xml':
				$this->acpperms_dev_source_to_xml();
				break;
			case 'acpperms-dev-report-missing':
				$this->acpperms_dev_report_missing();
				break;
			case 'acpperms-dev-report-language':
				$this->acpperms_dev_report_language();
				break;	
			
			case 'accperms-xml-import':
				$this->acpperms_xml_import();
				break;
				
			case 'acpperms-member-add':
				$this->acpperms_member_add();
				break;
			case 'acpperms-member-add-complete':
				$this->acpperms_member_add_complete();
				break;
			case 'accperms-member-remove':
				$this->accperms_member_remove();
				break;
				
			case 'acpperms-xml-display':
				$this->acpperms_xml_display();
				break;
				
			case 'acpperms-xml-save-tabs':
				$this->acpperms_xml_save( 'tabs' );
				break;
				
			case 'acpperms-xml-save-group':
				$this->acpperms_xml_save( 'group' );
				break;
				
			case 'acpperms-xml-save-mainbit':
				$this->acpperms_xml_save( 'mainbit' );
				break;
			case 'acpperms-xml-save-bits':
				$this->acpperms_xml_save( 'bits' );
				break;
				
			default:
				$this->acpperms_list();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: Remove member (and call him names)
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: XML display screen (depending on tab and other input)
	*
	* @return	void
	* @since	2.1.0.2005-7-11
	*/
	function accperms_member_remove()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$member_id = intval( $this->ipsclass->input['mid'] );
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $member_id )
		{
			$this->ipsclass->main_msg = "No member ID passed";
		}
		
		//-------------------------------
		// Remove member's row
		//-------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'admin_permission_rows', 'where' => 'row_member_id='.$member_id ) );
		
		//-------------------------------
		// Print
		//-------------------------------
		
		$this->ipsclass->main_msg = "Member's restrictions lifted";
		$this->acpperms_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: XML Display
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: XML display screen (depending on tab and other input)
	*
	* @return	void
	* @since	2.1.0.2005-7-11
	*/
	function acpperms_xml_save( $type='tabs' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$this->member_id   = intval( $this->ipsclass->input['member_id'] );
		$this->tab	       = trim( $this->ipsclass->input['tab'] ) ? trim( $this->ipsclass->input['tab'] ) : trim( $this->ipsclass->input['perm_main'] );
		$this->perm_main   = $this->tab;
		$this->perm_child  = trim( $this->ipsclass->input['perm_child'] );
		$this->member_info = array();
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $this->member_id )
		{
			exit();
		}
		
		//-------------------------------
		// Get Member info
		//-------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.*',
												 'from'     => array( 'admin_permission_rows' => 'p' ),
												 'where'    => 'p.row_member_id='.$this->member_id,
												 'add_join' => array( 0 => array(
												 								  'select' => 'm.*',
												 								  'from'   => array( 'members' => 'm' ),
												 								  'where'  => 'm.id=p.row_member_id',
												 								  'type'   => 'inner' ) ),
												 'order'    => 'm.members_display_name DESC' ) );
												 
		$this->ipsclass->DB->exec_query();
		
		$this->member_info = $this->ipsclass->DB->fetch_row();
		
		//-------------------------------
		// Unpack array
		//-------------------------------
		
		$this->member_info['_perm_cache'] = unserialize( $this->member_info['row_perm_cache'] );
		
		//-------------------------------
		// What up?
		//-------------------------------
		
		switch( $type )
		{
			case 'tabs':
				$this->member_info['_perm_cache'][ $this->perm_main ] = intval( $this->ipsclass->input['result'] );
				break;
			case 'mainbit':
				$this->member_info['_perm_cache'][ $this->perm_main.':'.$this->perm_child ] = intval( $this->ipsclass->input['result'] );
				break;
			case 'bits':
				//-------------------------------
				// Get bits to save...
				//-------------------------------
				
				foreach( $this->ipsclass->input as $key => $value )
				{
					if ( preg_match( "#^pbfs_(.+?)$#i", $key, $match ) )
					{	
						if ( $match[1] )
						{
							$_bit      = $match[1];
							$_bitvalue = intval( $this->ipsclass->input[ $match[0] ] );
							
							$this->member_info['_perm_cache'][ $this->perm_main.':'.$this->perm_child.':'.$_bit ] = intval( $_bitvalue );
						}
					}
				}
				
				break;
				case 'group':
					//-------------------------------
					// Allow perms to this bit
					//-------------------------------
					$this->member_info['_perm_cache'][ $this->perm_main.':'.$this->perm_child ] = intval( $this->ipsclass->input['result'] );
					
					//-------------------------------
					// Get bits from DB
					//-------------------------------
					
					$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'admin_permission_keys', 'where' => "perm_main='{$this->perm_main}' AND perm_child='{$this->perm_child}'" ) );
					$this->ipsclass->DB->exec_query();
					
					while ( $r = $this->ipsclass->DB->fetch_row() )
					{
						if ( $r['perm_main'] AND $r['perm_child'] AND $r['perm_bit'] )
						{
							$this->member_info['_perm_cache'][ $r['perm_main'].':'.$r['perm_child'].':'.$r['perm_bit'] ] = intval( $this->ipsclass->input['result'] );
						}
					}
					
				break;
		}
		
		$this->ipsclass->DB->do_update( 'admin_permission_rows', array( 'row_perm_cache' => serialize( $this->member_info['_perm_cache'] ),
																		'row_updated'    => time() ), 'row_member_id='.$this->member_id );
		
		//-------------------------------
		// Print
		//-------------------------------
		
		$this->acpperms_xml_display();
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: XML Display
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: XML display screen (depending on tab and other input)
	*
	* @return	void
	* @since	2.1.0.2005-7-11
	*/
	function acpperms_xml_display()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$this->member_id   = intval( $this->ipsclass->input['member_id'] );
		$this->tab	       = trim( $this->ipsclass->input['tab'] ) ? trim( $this->ipsclass->input['tab'] ) : trim( $this->ipsclass->input['perm_main'] );
		$this->func        = trim( $this->ipsclass->input['func'] );
		$this->perm_main   = $this->tab;
		$this->perm_child  = trim( $this->ipsclass->input['perm_child'] );
		$this->lang        = array();
		$this->member_info = array();
		$tab_html          = '';
		$content           = '';
		$tabinit           = array();
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $this->member_id )
		{
			exit();
		}
		
		//-------------------------------
		// Get lang: NEEDS SORTIN'
		//-------------------------------
		
		require_once( ROOT_PATH."cache/lang_cache/en/acp_lang_acpperms.php" );
		$this->lang = $lang;
		
		//-------------------------------
		// Get Member info
		//-------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.*',
												 'from'     => array( 'admin_permission_rows' => 'p' ),
												 'where'    => 'p.row_member_id='.$this->member_id,
												 'add_join' => array( 0 => array(
												 								  'select' => 'm.*',
												 								  'from'   => array( 'members' => 'm' ),
												 								  'where'  => 'm.id=p.row_member_id',
												 								  'type'   => 'inner' ) ),
												 'order'    => 'm.members_display_name DESC' ) );
												 
		$this->ipsclass->DB->exec_query();
		
		$this->member_info = $this->ipsclass->DB->fetch_row();
		
		//-------------------------------
		// Unpack array
		//-------------------------------
		
		$this->member_info['_perm_cache'] = unserialize( $this->member_info['row_perm_cache'] );
		
		//-------------------------------
		// Build tab HTML
		//-------------------------------
		
		$onoff['content']     = 'taboff';
		$onoff['lookandfeel'] = 'taboff';
		$onoff['tools']       = 'taboff';
		$onoff['components']  = 'taboff';
		$onoff['admin']       = 'taboff';
		$onoff['help']	 	  = 'taboff';
		
		$onoff[ $this->tab ] = 'tabon';
		
		foreach( $onoff as $t => $s )
		{
			$tabinit[ $t ] = intval($this->member_info['_perm_cache'][ $t ]);
		}
		
		//echo "<pre>";print_r($tabinit);echo "</pre>";
		
		$tab_html = $this->html->acp_xml_tabs( $onoff, $this->member_id, $tabinit );
		
		//print $tab_html;exit;
		
		//-------------------------------
		// What to do?
		//-------------------------------
		
		if ( $this->perm_main )
		{
			//-------------------------------
			// Show side menu w/ global opt
			//-------------------------------
			
			if ( $this->member_info['_perm_cache'][ $this->perm_main ] )
			{
				$content = $this->acpperms_xml_show_global();
			}
			else
			{
				$content = $this->html->acp_xml_tab_no_access( $this->member_info );
			}
			
		}
		else
		{
			//-------------------------------
			// Show welcome screen
			//-------------------------------
			
			$content = $this->acpperms_xml_show_welcome();
			
		}
		
		//-------------------------------
		// Print
		//-------------------------------
		
		print $this->html->acp_xml_wrap( $tab_html, $content );
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: XML Display: Global
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: XML display screen [ GLOBAL ]
	*
	* @return	void
	* @since	2.1.0.2005-7-8
	*/
	function acpperms_xml_show_global()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content          = '';
		$sidebar          = '';
		$sidebar_content  = '';
		$rows             = array();
		$bits             = array();
		$menu_data		  = array();
		$main			  = '';
		$main_content     = '';
		$main_img_classes = array( 'tick' => 'img-boxed-off', 'cross' => 'img-boxed-off' );
		$lang_map         = array( 'add'     => 'Allow ADD permission',
								   'edit'    => 'Allow EDIT permission',
								   'remove'  => 'Allow DELETE permission',
								   'import'  => 'Allow IMPORT permission',
								   'export'  => 'Allow EXPORT permission',
								   'rebuild' => 'Allow REBUILD permission',
								   'recount' => 'Allow RECOUNT permission',
								   'recache' => 'Allow RECACHE permission',
								   'view'    => 'Allow VIEW permission',
								   'search'  => 'Allow SEARCH permission',
								   'log'     => 'Allow MANAGE LOGS permission',
								   'show'    => 'Allow BASIC VIEW permission',
								   'upload'  => "Allow UPLOAD permission",
								   'do'		 => "Allow PERFORM permission",
								  );
		
		//-------------------------------
		// Not components?
		//-------------------------------
		
		if ( $this->perm_main != 'components' )
		{
			//-------------------------------
			// Get all groups with this tab
			//-------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'acpperms_get_main_groups', array( 'perm_main' => $this->perm_main ) );
			$this->ipsclass->DB->cache_exec_query();
				
			//-------------------------------
			// Loop through and build side
			//-------------------------------
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$rows[] = $r;
				
				if ( ! $this->perm_child )
				{
					//-------------------------------
					// Just set up the default one
					//-------------------------------
					
					$this->perm_child = $r['perm_child'];
				}
			}
			
		}
		else
		{
			//-------------------------------
			// Get component menus
			//-------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'components',
														  'where'  => 'com_enabled=1',
														  'order'  => 'com_position ASC' ) );
			$this->ipsclass->DB->simple_exec();
			
			while( $com = $this->ipsclass->DB->fetch_row() )
			{
				$menu_data[ $com['com_section'] ] = unserialize( $com['com_menu_data'] );
				
				if ( is_array( $menu_data[ $com['com_section'] ] ) and count( $menu_data[ $com['com_section'] ] ) )
				{
					if ( ! $this->perm_child )
					{
						$this->perm_child = $com['com_section'];
					}
					
					//-------------------------------
					// Populate rows
					//-------------------------------
					
					$rows[] = array( 'perm_main'  => 'components',
									 'perm_child' => $com['com_section'] );
									 
					$this->lang[ 'components:'.$com['com_section'] ] = $com['com_title'];
				}
			}
		}
		
		//-------------------------------
		// Go froo the rows
		//-------------------------------
		
		foreach( $rows as $r )
		{
			$key      = $r['perm_main'].':'.$r['perm_child'];
			$lang_bit = $this->lang[ $key  ];
			
			$sidebar_content .= $this->perm_child == $r['perm_child'] ? $this->html->acp_xml_global_sidebar_link_chosen( $lang_bit, $this->member_id, $r['perm_main'], $r['perm_child'], intval($this->member_info['_perm_cache'][ $key ]) )
																	  : $this->html->acp_xml_global_sidebar_link( $lang_bit, $this->member_id, $r['perm_main'], $r['perm_child'], intval($this->member_info['_perm_cache'][ $key ]) );
		}
		
		$sidebar = $this->html->acp_xml_global_sidebar_wrap( $sidebar_content );
		
		//-------------------------------
		// Get main content
		//-------------------------------
		
		$main_value = intval( $this->member_info['_perm_cache'][ $this->perm_main .':'. $this->perm_child ] );
		$main_class = $main_value ? 'perms-green' : 'perms-red';
		
		//-------------------------------
		// Not components?
		//-------------------------------
		
		if ( $this->perm_main != 'components' )
		{
			$this->ipsclass->DB->build_query( array( 'select' => '*',
													 'from'   => 'admin_permission_keys',
													 'where'  => "perm_main='{$this->perm_main}' AND perm_child='{$this->perm_child}'",
													 'order'  => 'perm_bit ASC' ) );
													 
			$this->ipsclass->DB->exec_query();
			
			while ( $bit = $this->ipsclass->DB->fetch_row() )
			{
				$bits[] = $bit;
			}
		}
		//-------------------------------
		// IS components
		//-------------------------------
		else
		{
			if( count($menu_data[ $this->perm_child ]) )
			{
				foreach( $menu_data[ $this->perm_child ] as $menu_array )
				{
					if ( $menu_array['menu_permbit'] )
					{
						$bits[] = array( 'perm_main'  => 'components',
										 'perm_child' => $this->perm_child,
										 'perm_bit'   => $menu_array['menu_permbit'] );
										 
						if ( $menu_array['menu_permlang'] )
						{
							$this->lang[ 'components:'.$this->perm_child.':'.$menu_array['menu_permbit'] ] = $menu_array['menu_permlang'];
						}
					}
				}
			}
		}
		
		//-------------------------------
		// Loop and print
		//-------------------------------
			
		foreach( $bits as $bit )
		{
			$img_classes   = array( 'tick' => 'img-boxed-off', 'cross' => 'img-boxed-off' );
			
			# Set up key  perm_main:perm_child:perm_bit
			$key           = $this->perm_main.':'.$this->perm_child.':'.$bit['perm_bit'];
			
			# Got a specific language entry? Use it
			$lang_bit      = $this->lang[ $key ] ? $this->lang[ $key ] : $lang_map[ $bit['perm_bit'] ];
			
			# Got a row in the DB matching KEY?
			$value         = intval( $this->member_info['_perm_cache'][ $key ] );
			
			# Set up BG class for perm bit
			$class         = $value      ? 'perms-green' : 'perms-red';
			
			# Set up BG class again... actually enabled this bit yet?
			$class		   = $main_value ? $class        : 'perms-gray';
			
			# Img classes - cross or ticked?
			$value         ? $img_classes['tick'] = 'img-boxed' : $img_classes['cross'] = 'img-boxed';
			
			$main_content .= $this->html->acp_xml_global_main_row( $lang_bit, $bit['perm_bit'], $value, $class, $img_classes );
		}
		
		//-------------------------------
		// Set up main-wrap contents
		//-------------------------------
		
		# Img classes - cross or ticked?
		$main_value ? $main_img_classes['tick'] = 'img-boxed' : $main_img_classes['cross'] = 'img-boxed';
		
		if( $main_content )
		{
			$main = $this->html->acp_xml_global_main_wrap( $main_content, $main_class, $main_value, $main_img_classes );
		}
		else
		{
			$main = $this->html->acp_xml_global_main_nocomponents( 'perm-gray' );
		}
		
		//-------------------------------
		// Print it
		//-------------------------------
		
		return $this->html->acp_xml_main_wrap( $sidebar, $main );
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: XML Display: Welcome
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: XML display screen [ WELCOME ]
	*
	* @return	void
	* @since	2.1.0.2005-7-8
	*/
	function acpperms_xml_show_welcome()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = '';
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		return $this->html->acp_xml_welcome( $this->member_info );
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: Add member complete
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: Add member complete
	*
	* Checks input, adds row to DB if OK
	*
	* @return	void
	* @since	2.1.0.2005-7-8
	*/
	function acpperms_member_add_complete()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$name = trim( $this->ipsclass->input['entered_name'] );
		$isok = 0;
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $name )
		{
			$this->ipsclass->main_msg = "You must enter a name before submitting the form.";
			$this->acpperms_member_add();
		}
		
		//-------------------------------
		// Get member...
		//-------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'login_getmember_by_dname', array( 'username' => strtolower($name) ) );
		$this->ipsclass->DB->cache_exec_query();
			
		$member = $this->ipsclass->DB->fetch_row();
		
		//-------------------------------
		// Check...
		//-------------------------------
		
		if ( ! $member['id'] )
		{
			$this->ipsclass->main_msg = "{$name} cannot be found.";
			$this->acpperms_member_add();
		}
		
		//-------------------------------
		// Already got 'em
		//-------------------------------
		
		$test = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'row_member_id', 'from' => 'admin_permission_rows', 'where' => 'row_member_id='.$member['id'] ) );
		
		if ( $test['row_member_id'] )
		{
			$this->ipsclass->main_msg = "{$name} already has restrictions in place.";
			$this->acpperms_member_add();
		}
		
		//-------------------------------
		// Is Root admin?
		//-------------------------------
		
		if ( $this->ipsclass->vars['admin_group'] == $member['mgroup'] )
		{
			$this->ipsclass->main_msg = "{$name}'s primary group is the Root Admin group and cannot be restricted.";
			$this->acpperms_member_add();
		}
		
		//-------------------------------
		// Primary ACP group?
		//-------------------------------
		
		if ( $this->ipsclass->cache['group_cache'][ $member['mgroup'] ]['g_access_cp'] )
		{
			$isok = 1;
		}
		//-------------------------------
		// Secondary ACP group?
		//-------------------------------
		else if ( $member['mgroup_others'] )
		{
			foreach( explode( ',', $member['mgroup_others'] ) as $gid )
			{
				if ( $this->ipsclass->cache['group_cache'][ $gid ]['g_access_cp'] )
				{
					$isok = 1;
					break;
				}
			}
		}
		
		//-------------------------------
		// Not oK?
		//-------------------------------
		
		if ( ! $isok )
		{
			$this->ipsclass->main_msg = "{$member['members_display_name']} does not have access to the ACP and cannot be added.";
			$this->acpperms_member_add();
		}
		
		//-------------------------------
		// A-OK
		//-------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'admin_permission_rows', 'where' => 'row_member_id='.$member['id'] ) );
		
		$this->ipsclass->DB->do_insert( 'admin_permission_rows', array( 'row_member_id'  => $member['id'],
																		'row_perm_cache' => serialize( array() ),
																		'row_updated'	 => time() ) );
																		
		$this->ipsclass->main_msg = "{$member['members_display_name']} successfully added and now has no ACP access until you allow it by managing his/her restrictions.";
		$this->acpperms_list();
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP Perms: Add member form
	/*-------------------------------------------------------------------------*/
	/**
	* ACP Perms: Add member form
	*
	* Shows form asking for input
	*
	* @return	void
	* @since	2.1.0.2005-7-7
	*/
	function acpperms_member_add()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'ACP Restrictions' );
		$this->ipsclass->admin->nav[] = array( ''                        , 'Add an administrator' );
		
		//-------------------------------
		// INIT
		//-------------------------------
		
		//-------------------------------
		// Show the form
		//-------------------------------
		
		$this->ipsclass->html .= $this->html->acp_perms_add_admin_form();
		
		$this->ipsclass->admin->page_title  = "Admin Restrictions";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your admin restriction permissions.";
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP: Import ACP Permissions file
	/*-------------------------------------------------------------------------*/
	/**
	* Import XML file
	*
	* Grabs XML file, parses it and updates missing entries
	*
	* @return	void
	* @since	2.1.0.2005-7-7
	*/
	function acpperms_xml_import( $noreturn=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$updated      = 0;
		$inserted     = 0;
		$cur_perms    = array();
		
		//-----------------------------------------
		// Get file
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// check and load from server
			//-----------------------------------------
			
			if ( ! $this->ipsclass->input['file_location'] )
			{
				$this->ipsclass->main_msg = "No upload file was found and no filename was specified.";
				$this->acpperms_list();
			}
			
			if ( ! file_exists( ROOT_PATH . $this->ipsclass->input['file_location'] ) )
			{
				$this->ipsclass->main_msg = "Could not find the file to open at: " . ROOT_PATH . $this->ipsclass->input['file_location'];
				$this->acpperms_list();
			}
			
			if ( preg_match( "#\.gz$#", $this->ipsclass->input['file_location'] ) )
			{
				if ( $FH = @gzopen( ROOT_PATH.$this->ipsclass->input['file_location'], 'rb' ) )
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
				if ( $FH = @fopen( ROOT_PATH.$this->ipsclass->input['file_location'], 'rb' ) )
				{
					$content = @fread( $FH, filesize(ROOT_PATH.$this->ipsclass->input['file_location']) );
					@fclose( $FH );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( "#\.gz$#", "", $tmp_name );
			
			$content  = $this->ipsclass->admin->import_xml( $tmp_name );
		}
		
		//-----------------------------------------
		// Get current permission keys.
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'admin_permission_keys' ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$cur_perms[ $r['perm_key'] ] = $r['perm_key'];
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Fix up...
		//-----------------------------------------
		
		if ( ! is_array( $xml->xml_array['permsexport']['permsgroup']['perm'][0]  ) )
		{
			//-----------------------------------------
			// Ensure [0] is populated
			//-----------------------------------------
			
			$xml->xml_array['permsexport']['permsgroup']['perm'] = array( 0 => $xml->xml_array['permsexport']['permsgroup']['perm'] );
		}
		
		//-----------------------------------------
		// Loop through and sort out settings...
		//-----------------------------------------
		
		foreach( $xml->xml_array['permsexport']['permsgroup']['perm'] as $entry )
		{
			//-----------------------------------------
			// Do we have a row matching this already?
			//-----------------------------------------
			
			$_perm_main  = $entry['acpperm_main']['VALUE'];
			$_perm_child = $entry['acpperm_child']['VALUE'];
			$_perm_bit   = $entry['acpperm_bit']['VALUE'];
			
			$_perm_key   = $_perm_main.':'.$_perm_child.':'.$_perm_bit;
			
			if ( ! $cur_perms[ $_perm_key ] )
			{
				$this->ipsclass->DB->do_insert( 'admin_permission_keys', array( 'perm_key'   => $_perm_key,
																				'perm_main'  => $_perm_main,
																				'perm_child' => $_perm_child,
																				'perm_bit'   => $_perm_bit ) );
																				
				$inserted++;
			}
		}
		
		$this->ipsclass->main_msg = "$inserted permissions keys inserted";
		
		if ( ! $noreturn )
		{
			$this->acpperms_list();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP: DEV: Build report: Language file
	/*-------------------------------------------------------------------------*/
	/**
	* Build language file basics
	*
	* Generate language files with empty keys
	*
	* @return	void
	* @since	2.1.0.2005-7-7
	*/
	function acpperms_dev_report_language()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'ACP Permissions: Developer Tools' );
		
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = "";
		$temp    = "";
		
		//-------------------------------
		// Pick through dir
		//-------------------------------
		
		$master = $this->_acpperms_parse_source_folder();
		
		//-------------------------------
		// ...Check for matches
		//-------------------------------
		
		foreach( $master as $_name => $data )
		{
			//-------------------------------
			// Got anything?
			//-------------------------------
			
			if ( $master[ $_name ]['perm_main'] AND $master[ $_name ]['perm_child'] AND count($master[ $_name ]['perm_bits']) )
			{
				$permkey = $master[ $_name ]['perm_main'].':'.$master[ $_name ]['perm_child'];
				
				$temp[ $permkey ][] = str_pad("'".$permkey."'", 45, " " ) . "=>\t" . '"'. $this->lang[ $permkey ]. '",'."\n";
				
				foreach( $master[ $_name ]['perm_bits'] as $_bit => $bit_data )
				{
					$bitkey = $master[ $_name ]['perm_main'].':'.$master[ $_name ]['perm_child'].':'.$_bit;
					$temp[ $bitkey ][] = str_pad("'".$bitkey."'", 45, " " ) . "=>\t" . '"'. $bit_data['langstring']. '",'."\n";
				}
			}
		}
		
		//-------------------------------
		// Re-order by key
		//-------------------------------
		
		ksort( $temp );
		
		//-------------------------------
		// Implode
		//-------------------------------
		
		foreach( $temp as $data )
		{
			foreach( $data as $line )
			{
				$content .= $line;
			}
		}
		
		//-------------------------------
		// Add in single entries
		//-------------------------------
		
		foreach( array( 'admin', 'content', 'lookandfeel', 'components', 'tools', 'help' ) as $bitkey )
		{
			$content .= str_pad("'".$bitkey."'", 45, " " ) . "=>\t" . '"'. $this->lang[ $bitkey ]. '",'."\n";
		}
		
		$this->ipsclass->html .= "<textarea style='width:100%;height:400px'><"."?php\n#Don't complete empty sections, they are done automatically\n\$lang = array(\n{$content}\n);\n?"."></textarea>";
		
		$this->ipsclass->admin->page_title  = "Admin Permissions";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your admin permissions.";
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// ACP: DEV: Build report: Missing sources
	/*-------------------------------------------------------------------------*/
	/**
	* Build missing files report
	*
	* Generate list of files which don't have permission info
	*
	* @return	void
	* @since	2.1.0.2005-7-7
	*/
	function acpperms_dev_report_missing()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'ACP Permissions: Developer Tools' );
		
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = "";
		
		//-------------------------------
		// Pick through dir
		//-------------------------------
		
		$master = $this->_acpperms_parse_source_folder();
		
		//-------------------------------
		// ...Check for matches
		//-------------------------------
		
		foreach( $master as $_name => $data )
		{
			//-------------------------------
			// Got anything?
			//-------------------------------
			
			if ( ! count($master[ $_name ]['perm_bits']) )
			{
				$content .= "<span style='color:red;font-weight:bold'>{$_name}.php has no permission information....</span><br />";
			}
			else
			{
				$content .= "<span style='color:green'>{$_name}.php has permission information....</span><br />";
			}
		}
		
		$this->ipsclass->html .= $content;
		
		$this->ipsclass->admin->page_title  = "Admin Permissions";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your admin permissions.";
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Go through sources, regex out permission checks, build XML document
	/*-------------------------------------------------------------------------*/
	/**
	* Source folder to XML
	*
	* Generate new XML of permission bits
	*
	* @return	void
	* @since	2.1.0.2005-7-7
	*/
	function acpperms_dev_source_to_xml()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = "";
		
		//-------------------------------
		// Pick through dir
		//-------------------------------
		
		$master = $this->_acpperms_parse_source_folder();
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------
		
		$xml->xml_set_root( 'permsexport', array( 'exported' => time(), 'versionid' => '2.1.0', 'type' => 'master' ) );
		
		//-----------------------------------------
		// Get group
		//-----------------------------------------
		
		$xml->xml_add_group( 'permsgroup' );
		
		//-------------------------------
		// ...Check for matches
		//-------------------------------
		
		foreach( $master as $_name => $data )
		{
			//-------------------------------
			// Got anything?
			//-------------------------------
			
			if ( $master[ $_name ]['perm_main'] AND $master[ $_name ]['perm_child'] AND count($master[ $_name ]['perm_bits']) )
			{
				foreach( $master[ $_name ]['perm_bits'] as $_bit => $bit_data )
				{
					$content = array();
					
					$content[] = $xml->xml_build_simple_tag( 'acpperm_bit'    , $bit_data['name']               );
					$content[] = $xml->xml_build_simple_tag( 'acpperm_main'   , $master[ $_name ]['perm_main']  );
					$content[] = $xml->xml_build_simple_tag( 'acpperm_child'  , $master[ $_name ]['perm_child'] );
					
					$entry[] = $xml->xml_build_entry( 'perm', $content );
				}
			}
		}
		
		$xml->xml_add_entry_to_group( 'permsgroup', $entry );
		
		$xml->xml_format_document();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
	
		$this->ipsclass->admin->show_download( $xml->xml_document, 'ipb_acpperms.xml', '', 0 );
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Go through sources, regex out permission checks
	/*-------------------------------------------------------------------------*/
	
	/**
	* Parse sources folder
	*
	* Parse sources folder, put all perm into $master array
	*
	* @return	array	$master array
	* @since	2.1.0.2005-7-7
	*/
	function _acpperms_parse_source_folder()
	{
		//-------------------------------
		// INIT
		//-------------------------------

		$dir     = ROOT_PATH . "sources/action_admin";
		$master  = array();
		
		//-------------------------------
		// Load lang file...
		//-------------------------------
		
		require_once( ROOT_PATH . 'cache/lang_cache/en/acp_lang_acpperms.php' );
		
		$this->lang = $lang;
		
		//-------------------------------
		// Loop....
		//-------------------------------
		
		$handle = opendir($dir);
			
		while ( ( $file = readdir($handle) ) !== false )
		{
			if ( ($file != ".") && ($file != "..") && ($file != '.DS_Store') )
			{
				if ( is_file( $dir."/".$file ) )
				{
					//-------------------------------
					// Open file and get perm rows
					//-------------------------------
					
					$code  = implode( '', file( $dir."/".$file ) );
					$_name = str_replace( ".php", "", $file );
					
					$master[ $_name ] = array();
					
					//-------------------------------
					// Get perm main....
					//-------------------------------
					
					preg_match( '#var\s+?\$perm_main\s+?=\s+?[\'"](.+?)[\'"];#', $code, $match );
					
					$master[ $_name ]['perm_main'] = $match[1];
					
					//-------------------------------
					// Get perm child....
					//-------------------------------
					
					preg_match( '#var\s+?\$perm_child\s+?=\s+?[\'"](.+?)[\'"];#', $code, $match );
					
					$master[ $_name ]['perm_child'] = $match[1];
					
					if ( $master[ $_name ]['perm_main'] AND $master[ $_name ]['perm_child'] )
					{
						//-------------------------------
						// Get perm rows
						//-------------------------------
						
						preg_match_all( '#cp_permission_check\(\s+?\$this->perm_main\.[\'"]\|[\'"]\.\$this->perm_child\.[\'"]\:(.+?)[\'"]\s+?\);#i', $code, $match );
						
						for ($i=0; $i < count($match[0]); $i++)
						{
							$_bit = trim($match[1][$i]);
							$master[ $_name ]['perm_bits'][ $_bit ]['name']       = $_bit;
							$master[ $_name ]['perm_bits'][ $_bit ]['langstring'] = $lang[ $master[ $_name ]['perm_main'].':'.$master[ $_name ]['perm_child'].':'.$_bit ];
						}
					}
				} 
			}
		}
		
		closedir($handle); 
		
		return $master;
	}
	
	/*-------------------------------------------------------------------------*/
	// List current members
	/*-------------------------------------------------------------------------*/
	
	/**
	* List members
	*
	* @return	array	$master array
	* @since	2.1.0.2005-7-7
	*/
	function acpperms_list()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'ACP Restrictions' );
		
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content      = "";
		$rows         = array();
		$admin_groups = array();
		
		//-------------------------------
		// Get current ACP listed members
		//-------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.*',
												 'from'     => array( 'admin_permission_rows' => 'p' ),
												 'add_join' => array( 0 => array(
												 								  'select' => 'm.members_display_name, m.id, m.mgroup',
												 								  'from'   => array( 'members' => 'm' ),
												 								  'where'  => 'm.id=p.row_member_id',
												 								  'type'   => 'inner' ) ),
												 'order'    => 'm.members_display_name DESC' ) );
												 
		$this->ipsclass->DB->exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-------------------------------
			// (Alex) Cross
			//-------------------------------
			
			$r['_date']       = $this->ipsclass->get_date( $r['row_updated'], 'SHORT', 1 );
			$r['_group_name'] = $this->ipsclass->cache['group_cache'][ $r['mgroup'] ]['g_title'];
			
			$content .= $this->html->acp_perms_row($r);
		}
		
		$this->ipsclass->html .= $this->html->acp_perms_overview( $content );
		
		$this->ipsclass->admin->page_title  = "Admin Restrictions";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your admin restriction permissions.";
		$this->ipsclass->admin->output();
	}
	
	
}


?>